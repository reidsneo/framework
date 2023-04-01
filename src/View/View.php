<?php 

namespace Neko\Framework\View;

use Neko\Framework\App;
use Neko\Facade\Session;
use Neko\Menu\Menu;
use Neko\Menu\Link;
use Neko\Menu\Html;
use Neko\Menu\url\url;

class View {

    protected $app;


    protected $engine;

    protected $theme;

    protected $data = array();

    public function __construct(App $app, ViewEngineInterface $engine)
    {
        $this->app = $app;
        $this->setEngine($engine);
    }

    public function setEngine(ViewEngineInterface $engine)
    {
        $this->engine = $engine;
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function get($key, $default = null)
    {
        return isset($this->data[$key])? $this->data[$key] : $default;
    }

    public function render($file, array $data = array(),$returnOnly=false)
    {
        // global $menulist;
        // $data = array_merge($this->data, $data);
        // $data = array_merge($this->app->listgroup, $data);
        // $account = array();
        // if(Session::get("user") !==null)
        // {
        //     $account = Session::get("user");
        // }
        
        // $data = array_merge(array("menulist"=>$menulist),$data);
        
        // $data = array_merge($account,$data);
        // $data['app'] = $this->app;

        return $this->engine->render($file, $data,$returnOnly);

    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->engine, $method], $params);
    }

}
