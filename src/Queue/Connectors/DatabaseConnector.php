<?php 

namespace Igniter\Queues\Queue\Connectors;

use Config\Database;
use Igniter\Queues\Queue\Exceptions\QueueException;

/**
 * Queue handler for database.
 */
class DatabaseConnector extends BaseConnector
{
	protected const STATUS_WAITING   = 10;
	protected const STATUS_EXECUTING = 20;
	protected const STATUS_DONE      = 30;
	protected const STATUS_FAILED    = 40;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var integer
	 */
	protected $timeout;

	/**
	 * @var integer
	 */
	protected $maxRetries;

	/**
	 * @var integer
	 */
	protected $deleteDoneMessagesAfter;

	/**
	 * @var 
	 */
	protected $db;

	/**
	 * constructor.
	 *
	 * @param array $connectionConfig
	 * @param \Config\Queue $config
	 */
	public function __construct($connectionConfig, $config)
	{
		parent::__construct($connectionConfig, $config);
        $settings = $config['settings'];
		$this->table = $connectionConfig['table'];

		$this->timeout                 = $settings['timeout'];
		$this->maxRetries              = $settings['maxRetries'];
		$this->deleteDoneMessagesAfter = $settings['deleteDoneMessagesAfter'];

		$this->db = Database::connect();
	}

	/**
	 * send message to queueing system.
	 *
	 * @param array  $data
	 * @param string $queue
	 */
	public function send($data, ?string $queue = '')
	{       
		if ($queue === '' || $queue === null) {
			$queue = $this->defaultQueue;
		}

		$this->db->transStart();

		$datetime = date('Y-m-d H:i:s');

		$this->db->table($this->table)->insert([
			'queue'        => $queue,
			'status'       => self::STATUS_WAITING,
			'weight'       => 100,
			'attempts'     => 0,
			'available_at' => $this->availableAt->format('Y-m-d H:i:s'),
			'data'         => json_encode($data),
			'created_at'   => $datetime,
			'updated_at'   => $datetime,
		]);
		$this->db->transComplete();

		// return true;
	}

	/**
	 * Fetch message from queueing system.
	 * When there are no message, this method will return (won't wait).
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	public function fetch(callable $callback, string $queue = ''): bool
	{
		 // Start a transaction to ensure atomicity
		$this->db->transStart();

		// Try to select a job and lock it using FOR UPDATE SKIP LOCKED
		$row = $this->db->query(
			'SELECT * FROM ' . $this->table . ' 
			WHERE queue = ? AND status = ? AND available_at < ? 
			ORDER BY weight, id
			LIMIT 1
			FOR UPDATE SKIP LOCKED', 
			[$queue !== '' ? $queue : $this->defaultQueue, self::STATUS_WAITING, date('Y-m-d H:i:s')]
		)->getRow();

		if (!$row) {
			// No jobs found
			$this->db->transComplete();
			$this->houseKeeping();
			usleep(1000000); // Sleep for a second
			return true;
		}

		// Try to mark the job as executing
		$affectedRows = $this->db->table($this->table)
			->where('id', $row->id)
			->where('status', self::STATUS_WAITING)  // Only update if it's still waiting
			->update([
				'status' => self::STATUS_EXECUTING,
				'updated_at' => date('Y-m-d H:i:s')
			]);

		// Commit the transaction
		$this->db->transComplete();

		// If no rows were affected, another worker took the job
		if ($affectedRows === 0) {
			usleep(1000000); // Sleep for 1 second to avoid busy looping
			return true;
		}

		// Process the job
		$data = json_decode($row->data, true);

		try {
			$callback($data);  // Process the job

			// Mark the job as done
			$this->db->table($this->table)
				->where('id', $row->id)
				->update([
					'status'     => self::STATUS_DONE,
					'updated_at' => date('Y-m-d H:i:s')
				]);

			$this->fireOnSuccess($data);
		} catch (\Throwable $e) {
			// Handle the exception and log the error
			$error = (new \DateTime)->format('Y-m-d H:i:s') . "\n" .
					"{$e->getCode()} - {$e->getMessage()}\n\n" .
					"file: {$e->getFile()}:{$e->getLine()}\n" .
					"------------------------------------------------------\n\n";

			$this->db->table($this->table)
				->where('id', $row->id)
				->update(['error' => 'error: ' . $error]);

			$this->fireOnFailure($e, $data, $row);

			throw $e;  // Rethrow the exception
		}

		return true;
	}

	/**
	 * Receive message from queueing system.
	 * When there are no message, this method will wait.
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	public function receive(callable $callback, string $queue = ''): bool
	{
		while (!$this->fetch($callback, $queue)) {
			usleep(1000000);
		}

		return true;
	}

	/**
	 * housekeeping.
	 *
	 * clean up the database at the end of each run.
	 */
	public function houseKeeping()
	{
		//update executing statuses to waiting on timeout before max retry.
		$this->db->table($this->table)
			->set('attempts', 'attempts + 1', false)
			->set('status', self::STATUS_WAITING)
			->set('updated_at', date('Y-m-d H:i:s'))
			->where('status', self::STATUS_EXECUTING)
			->where('updated_at <', date('Y-m-d H:i:s', time() - $this->timeout))
			->where('attempts <', $this->maxRetries)
			->update();

		//update executing statuses to failed on timeout at max retry.
		$this->db->table($this->table)
			->set('attempts', 'attempts + 1', false)
			->set('status', self::STATUS_FAILED)
			->set('updated_at', date('Y-m-d H:i:s'))
			->where('status', self::STATUS_EXECUTING)
			->where('updated_at <', date('Y-m-d H:i:s', time() - $this->timeout))
			->where('attempts >=', $this->maxRetries)
			->update();

		//Delete messages after the configured period.
		if ($this->deleteDoneMessagesAfter !== false) {
			$this->db->table($this->table)
				->where('status', self::STATUS_DONE)
				->where('updated_at <', date('Y-m-d H:i:s', time() - $this->deleteDoneMessagesAfter))
				->delete();
		}
	}

	/**
	 * Reset all the failed jobs
	 */
	public function reset()
	{
		//set the status to executing if it hasn't already been taken.
		$this->db->table($this->table)
			->where('status', (int) self::STATUS_FAILED)
			->update([
				'status'     => self::STATUS_WAITING,
				'attempts' => 0,
				'updated_at' => date('Y-m-d H:i:s'),
			]);
	}
}
