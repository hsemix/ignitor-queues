<?php

namespace Igniter\Queues\Commands;

use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;
use Igniter\Queues\Queue\Config\Services;

class RetryFailedJobs extends BaseCommand
{
    use GeneratorTrait;
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Queue';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'queue:retry';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Resets all failed jobs to Waiting status so that the queue can retry them.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'queue:retry [arguments] [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        
    ];

    public $connection;

    public function __construct()
    {
        $this->connection = Services::queue();
    }

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {   
        CLI::write('Resetting Failed Jobs Queue...', 'yellow');

        $queue = $this->connection;

		$queue->reset();

		CLI::write('Completed Resetting Failed Jobs the queue can now retry them', 'green');
    }
}
