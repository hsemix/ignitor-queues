<?php

namespace Igniter\Queues\Queue;

interface CanFailInterface
{
    /**
     * method called when a job fails
     * 
     * @param static $job
     * @param string $message
     */
    public function onFailure($job, $message);
}
