<?php

namespace Igniter\Queues\Queue;

use Igniter\Queues\Queue\Config\Services;

trait DispatchableTrait
{
    /**
     * Properties on a Job
     */
    protected $properties = [];

    protected bool $queued = false;

    protected $encryptionService;

    protected array $primaryProperties = [
        'encryptionService', 
        'delayType', 
        'delay', 
        'queue', 
        'queued'
    ];

    /**
     * Delay the job by a given amount of seconds.
     *
     * @param int $delay
     *
     * @return mixed
     */
    public function delay(int $delay, string $type = 'minutes'): self
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
     */
    public function jsonSerialize(): array
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

    public function __serialize(): array
    {
        $properties = get_object_vars($this);

        if (!empty(env('encryption.key'))) {
            if (is_null($this->encryptionService)) {
                $this->encryptionService = Services::encryption();
            }
        }
        
        // Encrypt properties if the job implements IsEncryptedInterface
        if ($this->encryptionService) {
            if ($this instanceof IsEncryptedInterface) {
                foreach ($properties as $key => $value) {
                    if (in_array($key, ['encryptionService', 'delayType', 'delay', 'queue', 'queued'])) continue;
    
                    if (is_string($value)) {
                        $properties[$key] = base64_encode($this->encryptionService->encrypt($value));
                    }
    
                    if (is_array($value)) {
                        $properties[$key] = base64_encode($this->encryptionService->encrypt(json_encode($value)));
                    }
                }
            }
        }
        
        return $properties;
    }

    public function __unserialize(array $data): void
    {
        if (is_null($this->encryptionService)) {
            $this->encryptionService = Services::encryption();
        }

        // Decrypt properties if the job implements IsEncryptedInterface
        if ($this instanceof IsEncryptedInterface) {
            foreach ($data as $key => $value) {

                if (in_array($key, $this->primaryProperties)) continue;
                
                $decryptedValue = $this->encryptionService->decrypt(base64_decode($value));

                if (json_last_error() == JSON_ERROR_NONE) {
                    $data[$key] = json_decode($decryptedValue, true);
                } else {
                    $data[$key] = $decryptedValue;
                }
                
            }
        }

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
}
