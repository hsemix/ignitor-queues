<?php 

namespace Igniter\Queues\Queue\Connectors;

use CodeIgniter\I18n\Time;
use CodeIgniter\Events\Events;
use Igniter\Queues\Queue\CanFailInterface;

/**
 * Base Queue handler.
 */
abstract class BaseConnector
{
	/**
	 * @var string
	 */
	protected $defaultQueue;

	/**
	 * when the message will be available for
	 * execution
	 *
	 * @var DateTime
	 */
	protected $availableAt;

	/**
	 * constructor.
	 *
	 * @param array         $groupConfig
	 * @param \Config\Queue $config
	 */
	public function __construct($groupConfig, $config)
	{   
		$this->defaultQueue = $config['default'];

		$this->availableAt = new Time;
	}

	/**
	 * send message to queueing system.
	 *
	 * @param array  $data
	 * @param string $queue
	 */
	abstract public function send($data, ?string $queue = '');

	/**
	 * Fetch message from queueing system.
	 * When there are no message, this method will return (won't wait).
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	abstract public function fetch(callable $callback, string $queue = '') : bool;

	/**
	 * Receive message from queueing system.
	 * When there are no message, this method will wait.
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	abstract public function receive(callable $callback, string $queue = '') : bool;

	abstract public function reset();

	/**
	 * Set the delay in minutes
	 *
	 * @param  integer $min
	 * @return $this
	 */
	public function delay($min = 0, $delayType = 'minutes')
	{
		$this->availableAt = (new Time)->modify('+' . $min . ' ' . $delayType);

		return $this;
	}

	/**
	 * run a command from the queue
	 *
	 * @param string $command the command to run
	 */
	public function command(string $command)
	{
		$data = [
			'command' => $command,
		];

		return $this->send($data);
	}

	/**
	 * run an anonymous function from the queue.
	 *
	 * @param callable $closure function to run
	 *
	 * TODO: this currently doesn't work with database
	 * as you can't serialize a closure. May need
	 * to implement something like laravel does to get
	 * around this.
	 */
	public function closure(callable $closure)
	{
		$data = [
			'closure' => $closure,
		];

		return $this->send($data);
	}

	/**
	 * run a job from the queue
	 *
	 * @param string $job  the job to run
	 * @param mixed  $data data for the job
	 */
	public function job(string|object $job, $data = [])
	{
		$data = [
			'job'  => $job,
			'data' => $data,
		];

		$queueName = null;	

		if (is_object($job)) {
			$queueName = $job->queue;
		} 
		
		// else if (is_string($job)) {
		// 	$queueName = $job;
		// }

		return $this->send($data, $queueName);
	}

	/**
	 * run a job from the queue
	 *
	 * @param string $job  the job to run
	 * @param array  $data data for the job
	 */
	protected function fireOnFailure(\Throwable $e, $data, $queueJob)
	{
		$job = unserialize($data['data']['job']);

		if ($job instanceof CanFailInterface) {
			$job->onFailure($queueJob, $e->getMessage());
		}
		Events::trigger('queue:failure', $job, $queueJob, $e->getMessage());
	}

	/**
	 * run a job from the queue
	 *
	 * @param string $job  the job to run
	 * @param array  $data data for the job
	 */
	protected function fireOnSuccess($data)
	{
		Events::trigger('queue:successful', $data);
	}

	/**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     */
    public function createPayload($job, $data = [], $queue = null)
    {
        if (is_object($job)) {
            $payload = $this->createObjectPayload($job, $data);

			$jobName = get_class($payload);

        	$job = serialize(clone $payload);

			$this->send([
				'job'  => 'Igniter\Queue\CallQueuedHandler@call',
				'data' => compact('jobName', 'job'),
			], $payload->queue ?? $queue);

        	return json_encode($payload);
		}
    }

	/**
     * Create a payload string for the given Closure job.
     *
     * @param  object  $jobObject
     * @param  mixed   $data
     * @return object
     */
    protected function createObjectPayload($jobObject, $data = [])
    {
		$this->delay($jobObject->delay, $jobObject->delayType);

		return $jobObject;
    }
}
