<?php

namespace Igniter\Queues\Commands;

use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;
use Igniter\Queues\Queue\Config\Services;
use Igniter\Queues\Queue\Connectors\BaseConnector;
use function Opis\Closure\{unserialize};

class QueueWork extends BaseCommand
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
    protected $name = 'queue:work';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Works the queue with multiple workers if pcntl is available, or works in single-worker mode otherwise.';

    /**
     * The number of concurrent workers to run
     *
     * @var int
     */
    protected $numWorkers = 1; // You can adjust this number as needed

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--queue' => 'Provide a name for which queue to work on', 
        '--retry' => 'Provide a number of retries for failed jobs',
        '--workers' => 'Provide a number of workers to run',
        '--restart' => 'Restart the queue automatically when it stops',
    ];

    protected bool $restart = false;

    /**
     * Queue connection instance
     *
     * @var mixed
     */
    public BaseConnector $connection;

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
        $queue = $this->getOption('queue') ?? 'default';

        $this->restart = $this->getOption('restart') ?? false;

        CLI::write('Working Queue: ' . $queue, 'yellow');

        // Check if pcntl extension is loaded
        if (extension_loaded('pcntl') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {

            // Set the number of workers
            $this->numWorkers = $this->getOption('workers') ?? 1;

            CLI::write('Running with multiple workers(' . $this->numWorkers . ') (PCNTL enabled)', 'green');

            // Start multiple worker processes
            for ($i = 0; $i < $this->numWorkers; $i++) {
                $this->startWorker($queue);
            }

            // Wait for all workers to finish
            while (pcntl_waitpid(0, $status) != -1);
        } else {
            CLI::write('PCNTL extension not available. Running in single-worker mode.', 'yellow');
            
            // Fallback to single-worker mode
            $this->workerLoop($queue);
        }
    }

    /**
     * Start a worker process if pcntl is available
     *
     * @param string $queue
     */
    private function startWorker($queue)
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            // Fork failed
            CLI::write('Failed to fork worker', 'red');
        } elseif ($pid === 0) {
            // Child process (worker)
            $this->workerLoop($queue);
            exit(0); // Exit the child process once it's done
        }
    }

    /**
     * Worker loop that handles jobs and restarts when needed.
     *
     * @param string $queue
     */
    private function workerLoop($queue)
    {
        $response      = true;
        $jobsProcessed = 0;
        $startTime     = time();

        do {
            try {
                if ($this->stopIfNecessary($startTime, $jobsProcessed)) {
                    // Restart the worker if necessary
                    $this->restartWorker($queue);
                }

                $response = $this->connection->fetch([$this, 'fire'], $queue);

                $jobsProcessed++;
            } catch (\Throwable $e) {
                CLI::write('Failed', 'red');
                CLI::write("Exception: {$e->getCode()} - {$e->getMessage()}\nfile: {$e->getFile()}:{$e->getLine()}");
                usleep(5 * 1000000);
            } finally {
                usleep(5 * 1000000); // Sleep between job processing
            }
        } while ($response === true);
    }

    /**
     * Handle an individual job in the queue.
     *
     * @param array $data
     */
    public function fire($data)
    {
        if ($data['job'] == 'Igniter\Queue\CallQueuedHandler@call') {
            $job = unserialize($data['data']['job']);
            CLI::write('Running Job #' . $data['data']['jobName'], 'yellow');
            $job->run();
            CLI::write('Finished Job #' . $data['data']['jobName'], 'green');
        } else {
            CLI::write('Failed to run Job', 'red');
        }
    }

    /**
     * Check if the worker should stop and restart.
     *
     * @param int $startTime
     * @param int $jobsProcessed
     * @return bool
     */
    protected function stopIfNecessary($startTime, $jobsProcessed)
    {
        $maxTime = ini_get('max_execution_time') - 5; // Max execution time minus buffer
        $maxMemory   = ($this->getMemoryLimit() / 1024 / 1024) - 10; // Max memory with buffer
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;

        $maxBatch = 50; // Set the maximum batch size for the worker

        // Check if time limit or memory limit has been reached
        if ($maxTime > 0 && time() - $startTime > $maxTime) {
            CLI::write('Exiting Worker: Time Limit Reached', 'yellow');
            return true;
        }

        if ($maxMemory > 0 && $memoryUsage > $maxMemory) {
            CLI::write('Exiting Worker: Memory Limit Reached', 'yellow');
            return true;
        }

        if ($maxBatch > 0 && $jobsProcessed >= $maxBatch) {
            CLI::write('Exiting Worker: Maximum Batch Size Reached', 'yellow');
            return true;
        }

        return false;
    }

    /**
     * Restart the worker by forking a new process.
     */
    protected function restartWorker($queue)
    {
        if ($this->restart) {
            CLI::write('Restarting Queue Worker...', 'yellow');
            exec("php " . ROOTPATH . "spark queue:work --restart --queue " . $queue . " --workers " . $this->workers); // Re-execute the queue worker
        }
        
        exit(0); // Ensure the current process terminates after restart
    }

    /**
     * Calculate the memory limit in bytes.
     *
     * @return int Memory limit in bytes
     */
    protected function getMemoryLimit()
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit == -1) {
            return 2 * 1024 * 1024 * 1024; // Default to 2GB if no limit is set
        }

        preg_match('/^(\d+)(.)$/', $memoryLimit, $matches);

        if (!isset($matches[2])) {
            throw new \Exception('Unknown Memory Limit');
        }

        switch ($matches[2]) {
            case 'G':
                return $matches[1] * 1024 * 1024 * 1024;
            case 'M':
                return $matches[1] * 1024 * 1024;
            case 'K':
                return $matches[1] * 1024;
            default:
                throw new \Exception('Unknown Memory Limit');
        }
    }
}
