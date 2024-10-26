<?php

namespace Igniter\Queues\Queue;

use Opis\Closure\SerializableClosure;
use Igniter\Queues\Queue\DispatchableTrait;
use Igniter\Queues\Queue\ShouldQueueInterface;

class ClosureJob implements ShouldQueueInterface, CanFailInterface
{
    use DispatchableTrait;

    /**
     * The queue to run the job on.
     *
     * @var string
     */
    public string $queue = 'default';

    /**
     * Delay the job by a given amount of seconds.
     *
     * @param int $delay
     *
     */
    public int $delay = 0;

    /**
     * Delay the job by a given amount of seconds.
     *
     * @param string $delayType
     *
     */
    public string $delayType = 'minutes';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public $job, public $data = [], $delay = 0, $delayType = 'minutes', $queue = 'default', $onFailure = null)
    {
        $this->job = new SerializableClosure($job);
        $this->delay = $delay;
        $this->delayType = $delayType;
        $this->queue = $queue;
        $this->onFailure = $onFailure;
    }

    /**
     * Run the job.
     *
     * @return mixed
     */
    public function run()
    {   
        return call_user_func($this->job, $this->data);
    }

    /**
     * Triggered when this job fails for whatever reason and only if this job is queueable
     * 
     * @param stdClass $job
     * @param string $message
     */
    public function onFailure($job, $message)
    {
        return $this->onFailure ? call_user_func($this->onFailure, $job, $message) : null;
    }
}
