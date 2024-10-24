<?php

namespace Igniter\Queues\Queue\Connectors;

use Igniter\Queues\Queue\Queues\WaitQueue;


class WaitConnector implements ConnectorInterface
{

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Igniter\Queue\Contracts\QueueInterface
     */
    public function connect(array $config)
    {
        // return new WaitQueue;
    }

}
