<?php 

namespace Neko\Framework;
use Neko\Framework\Util\Str;

abstract class Action {

    /**
     * @var int
     */
    protected $index;

    /**
     * @var Neko\Framework\App
     */
    protected $app;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $action;
    public $params;


    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(App $app, $type, $index, $action)
    {
        $this->app = $app;
        $this->type = $type;
        $this->index = $index;
        $this->action = $action;
    }

    /**
     * Get application instance
     *
     * @return Neko\Framework\App
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Get action index
     *
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Get action type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get defined action
     *
     * @return mixed
     */
    public function getDefinedAction()
    {
        return $this->action;
    }

    /**
     * Get defined Params
     *
     * @return mixed
     */
    public function getDefinedParams()
    {
        return $this;
    }

    /**
     * Abstract get resolved callable
     *
     * @return callable
     */
    abstract public function getCallable();

    
    /**
     * Abstract get resolved callable array
     *
     * @return callable
     */
    abstract public function getCallableArray();


    /**
     * Get next action
     *
     * @return Neko\Framework\Action | null
     */
    public function getNext()
    {
        $actions = $this->app['actions'];
        $next_index = $this->index+1;
        return array_key_exists($next_index, $actions)? $actions[$next_index] : null;
    }

    /**
     * Check if action is controller
     *
     * @return boolean
     */
    public function isController()
    {
        return $this->getType() == 'controller';   
    }

    /**
     * Check if action is middleware
     *
     * @return boolean
     */
    public function isMiddleware()
    {
        return $this->getType() == 'middleware';
    }

    /**
     * Check if defined action using string class method like ClassName@method
     *
     * @return boolean
     */
    public function useStringClassMethod()
    {
        $defined_action = (string) $this->getDefinedAction();
        return count(explode('@', $defined_action)) == 2;
    }

    /**
     * Run action
     *
     * @return Neko\Framework\Http\Response
     */
    public function run()
    {
        $app = $this->getApp();
        $callable = $this->getCallable();
        $returned = "";

        
        if (!is_callable($callable) && is_string($this->getDefinedAction())) {
            $defined_action = (string) $this->getDefinedAction();
            $reason = $defined_action." is not callable";

            if ($this->useStringClassMethod()) {
                list($class, $method) = explode('@', $defined_action, 2);
                if (!class_exists($class)) {
                    $reason = "Class {$class} doesn't exists";
                } elseif (!method_exists($class, $method)) {
                    $reason = "Method {$class}::{$method} doesn't exists";
                }
            }

            throw new \InvalidArgumentException("Cannot run action ".$this->getType()." '".$this->getDefinedAction()."'. <br><br>".$reason, 1);
        }else if (!is_callable($callable) && is_array($this->getDefinedAction())) {
            //throw new \InvalidArgumentException("You are using PHP 8.X version which is unsupported by this framework");
            list($class, $method) = $this->getDefinedAction();
            $calledController = new $class($this->app);
            $r = new \ReflectionMethod($calledController, $method);
            $params = [];
            $matched_route = $this->app->request->route();
            $parameters = $matched_route->params;
            //var_dump( phpversion());
            foreach ($r->getParameters() as $param) {
                $args = sprintf('%s',$param->getType());
                if (class_exists($args)) {
                    if(Str::contains($args, 'Http\Request')){
                        $params[] =  new $args($this->app);
                    }else{
                        $params[] =  new $args();
                    }
                } else if($args == 'array') {
                    $params[] = [];
                } else if($args == 'string') {
                    $params[] = "";
                }else{
                    $param_keys = array_keys($parameters);
                    $param_vals = array_values($parameters);
                    if (count($parameters) !== 0) {
                        $param_key = $param_keys[$param->getPosition()];
                        $param_val = $param_vals[$param->getPosition()];
                        $params[] = $param_val;
                    }else{
                        $params = [];
                    }
                }
            }
            $returned = call_user_func_array([$calledController, $method], $params);
        }else{
            $returned = call_user_func($callable);
        }


        if(is_array($returned)) {
            $app->response->json($returned, null, $app->response->getContentType());
        } elseif(is_string($returned)) {
            $app->response->html($returned, null, $app->response->getContentType());
        }

        return $returned;
    }

    /**
     * Invoke action
     *
     * @return Neko\Framework\Http\Response
     */
    public function __invoke()
    {
        return $this->run();
    }

}