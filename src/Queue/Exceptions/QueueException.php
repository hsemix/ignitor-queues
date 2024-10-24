<?php 

namespace Igniter\Queues\Queue\Exceptions;

use Exception;

class QueueException extends Exception
{
	public static function forFailGetQueueDatabase($table)
    {
        return new static("An error occurred while getting the queue database table: {$table}");
    }
}