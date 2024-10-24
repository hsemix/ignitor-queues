<?php

namespace Igniter\Queues\Queue;

use Igniter\Queues\Queue\Config\Services;
use Igniter\Queues\Queue\ShouldQueueInterface;

trait DispatchableTrait
{
    /**
     * Properties on a Job
     */
    protected $properties = [];

    protected bool $queued = false;

    /**
     * Delay the job by a given amount of seconds.
     *
     * @param int $delay
     *
     * @return static
     */
    public function delay(int $delay, string $type = 'minutes'): static
    {
        $this->delay = $delay;

        $this->delayType = $type;

        $this->queueOrNot();
        return $this;
    }
    
    /**
     * Dispatch the job with the given arguments.
     *
     * @return mixed
     */
    public static function dispatch(): self
    {
        $args = func_get_args();
        
        $job = new self(...$args);
        $job->queueOrNot();
        return $job;
    }

    /**
     * Automatically queue the job when any undefined method is called (using __call).
     *
     * @param  string  $name
     * @param  array   $arguments
     * @return void
     */
    public function __call($name, $arguments)
    {
        $this->queueOrNot();
    }

    /**
     * Run the Job
     */
    protected function queueOrNot()
    {
        if (!$this->queued) {
            if ($this instanceof ShouldQueueInterface) {
                // Send job to queue system
                Services::queue()->createPayload($this);
            } else {
                // Directly execute the job if it shouldn't be queued
                $this->run();
            }

            // Mark as queued to avoid multiple dispatches
            $this->queued = true;
        }
    }

    /**
	 * get a variable and make an object point to it
     * 
     * @param null
     * 
     * @return void
	 */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

	/**
	 * Set a variable and make an object point to it
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return void
	 */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if a property exists on the model.
     *
     * @param  string  $key
     * 
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getAttribute($key));
    }

    /**
     * Unset a property on the model.
     *
     * @param  string  $key
     * 
     * @return void
     */
    public function __unset($key)
    {
        unset($this->properties[$key]);
    }

    /**
     * Set a model attribute
     * 
     * @param string $key
     * @param mixed
     * 
     * @return static
     */
    public function setAttribute($key, $value)
    {
        $this->properties[$key] = $value;
        return $this;
    }

    /**
     * Get a property from the model.
     *
     * @param  string  $key
     * 
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        }
    }

    /**
     * Change the job to a json string
     * 
     * @param int|null $options
     * 
     * @return string
     */
    public function toJson($options = [])
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Change the job to a string
     * 
     * @param null
     * 
     * @return void
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Implement a json serializer
     * 
     * @param null
     * 
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $attributes = (array) $this->properties;
    
        $attributes = array_map(function($attribute) {
            if (!is_array($attribute)) {
                if (!is_object($attribute)) {
                    $json_attribute = json_decode($attribute, true);
                    if (json_last_error() == JSON_ERROR_NONE)
                        return $json_attribute;
                } else {
                    return (array)$attribute;
                }
            }
            return $attribute;
        }, $attributes);
        return $attributes;
    }
}
