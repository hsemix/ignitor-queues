<?php

namespace Igniter\Queues\Queue\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Igniter\Queue\Connectors\QueueInterface
     */
    public function connect(array $config);
}