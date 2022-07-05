<?php

namespace Omniphx\Forrest\Providers\Laravel;


use Illuminate\Contracts\Config\Repository as Config;
use Omniphx\Forrest\Exceptions\MissingKeyException;
use Omniphx\Forrest\Interfaces\StorageInterface;

class LaravelCache implements StorageInterface
{
    protected $path;
    protected $seconds = 600; // 10 minutes
    protected $storeForever;
    protected $instanceKey = null;

    public function __construct(Config $config)
    {
        $this->path = $config->get('forrest.storage.path');
        $this->storeForever = $config->get('forrest.storage.store_forever');
        $this->expirationConfig = $config->get('forrest.storage.expire_in');
        $this->setSeconds();
    }

    public function setInstanceKey($key){
        $this->instanceKey = $key;
    }

    public function checkInstanceKey(){
        if(!$this->instanceKey){
            dd("Instance key not set");
        }
    }

    public function getStorageKey($key){
        return $this->instanceKey . '_' . $this->path . '_' . $key;
    }

    public function put($key, $value)
    {
        $this->checkInstanceKey();

        if ($this->storeForever) {
            return cache()->forever($this->getStorageKey($key), $value);
        } else {
            return cache()->put($this->getStorageKey($key), $value, $this->seconds);
        }
    }

    public function get($key)
    {
        $this->checkInstanceKey();

        $this->checkForKey($key);

        return cache()->get($this->getStorageKey($key));
    }


    public function has($key)
    {
        return cache()->has($this->getStorageKey($key));
    }


    protected function setSeconds()
    {
        if (!$this->checkIfPositiveInteger($this->expirationConfig)) return;
        $this->seconds = $this->expirationConfig;
    }


    protected function checkForKey($key)
    {
        if (cache()->has($this->getStorageKey($key))) return;

        throw new MissingKeyException(sprintf('No value for requested key: %s', $key));
    }

    protected function checkIfPositiveInteger($integer)
    {
        return $this->checkIfInteger($integer) && $this->checkIfPositive($integer);
    }

    protected function checkIfInteger($integer)
    {
        return filter_var($integer, FILTER_VALIDATE_INT) !== false;
    }

    protected function checkIfPositive($integer)
    {
        return $integer > 0;
    }
}
