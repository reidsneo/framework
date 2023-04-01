<?php 

namespace Neko\Framework;

abstract class Provider {

    public $app;
    
    public function __construct(App $app)
    {
        $this->app = $app;        
    }

    abstract public function register();

    abstract public function boot();

}