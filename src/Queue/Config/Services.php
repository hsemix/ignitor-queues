<?php

namespace Igniter\Queues\Queue\Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function queue($getShared = false)
    {
        if (!$getShared) {
            return (new \Igniter\Queues\Queue\Queue())->connect();
        }
    
        return static::getSharedInstance('queue')->connect();
    }

    public static function encryption($getShared = false)
    {
        if (!$getShared) {
            return service('encrypter');
        }
    
        return static::getSharedInstance('encryption');
    }
}
