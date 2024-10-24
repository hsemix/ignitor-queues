<?php 

namespace Igniter\Queues\Queue;

use Igniter\Queues\Queue\Exceptions\QueueException;

/**
 * Queue class.
 */
class Queue implements QueueInterface
{
	/**
	 * Config object.
	 *
	 * @var 
	 */
	protected $config;

	/**
	 * Config of the connection connection to use
	 *
	 * @var array
	 */
	protected $connectionConfig;

	/**
	 * Constructor.
	 *
	 * @param $config
	 * @param string|array  $connection The name of the connection to use,
	 *                              or an array of configuration settings.
	 */
	public function __construct()
	{
        
	}

	/**
	 * connecting queueing system.
	 *
	 * @return CodeIgniter\Queue\Handlers\BaseHandler
	 */
	public function connect()
	{
		$settings = [
			'queue' => [
				'default' => 'database',
				'connections' => [
					'wait' => [
						'driver' => 'wait',
						'queue'   => 'default',
					],
					'database' => [
						'driver'  => 'database',
						'table'   => 'queue_jobs',
						'queue'   => 'default',
						'expire'  => 60,
					],
					'file' => [
		
					]
				],
				'settings' => [
					'maxRetries'              => 3,
					'timeout'                 => 30,
					'deleteDoneMessagesAfter' => 30 * 3600 * 24,
					//the max number of queue entries to process at once.
					'maxWorkerBatch'          => 20,
				]
			]
		];
		return new \Igniter\Queues\Queue\Connectors\DatabaseConnector($settings['queue']['connections']['database'], $settings['queue']); 
	}
}
