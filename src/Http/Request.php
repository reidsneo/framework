<?php 

namespace Neko\Framework\Http;

use Neko\Framework\App;
use Neko\Framework\Bag;
use Neko\Framework\MacroableTrait;
use Neko\Framework\Router\Route;
use Neko\Framework\Util\Arr;
use Neko\Facade\Session;

class Request {

    use MacroableTrait;

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    protected $route;

    public $app;
    public $server = null;
    public $files = null;
    public $inputs = null;
    public $params = array();

    public function __construct(App $app, Route $route = null)
    {
        if($route) $this->defineRoute($route);
        $this->app = $app;

        parse_str(file_get_contents('php://input'), $_PATCH);
        if($this->isJson($_POST) || $this->isJson($_GET) || $this->isJson($_PATCH))
        {
            $_POST = $this->json()->all();
            $_GET = $this->json()->all();
            $_PATCH = $this->json()->all();
        }
        $this->inputs = new Bag((array) $_POST + (array) $_GET + (array) $_PATCH);
        $this->files = new Bag((array) $_FILES);
        $this->server = new Bag($_SERVER);
    }

    public function path()
    {
        $path_info = $this->server->get('PATH_INFO');

        if(!$path_info) {
            $request_uri = $this->server->get('REQUEST_URI');
            $script_name = $this->server->get('SCRIPT_NAME');
            if (pathinfo($script_name, PATHINFO_EXTENSION) == 'php') {
                $path_info = str_replace($script_name, '', $request_uri);
            }  else {
                $path_info = $request_uri;
            }
            
            $path_info = explode('?', $path_info, 2)[0];
        }

        return '/'.trim($path_info, '/');
    }

    public function headers()
    {
        return getallheaders();
    }

    public function getAuthToken()
    {
        return getallheaders();
    }

    public function segment($index)
    {
        $paths = explode('/', $this->path());
        return isset($paths[$index])? $paths[$index] : null;
    }

    public function all()
    {
        $all_inputs = $this->inputs->all();
        $all_files = $this->files->all();
        foreach($all_files as $key => $value) {
            $all_files[$key] = $this->hasMultiFiles($key)? $this->multiFiles($key) : $this->file($key);
        }
        return array_merge($all_inputs, $all_files);
    }

    public function current()
    {
        return $this->app->config['app.base_url'].$this->path();
    }


    public function has($key)
    {
        return $this->inputs->has($key);
    }

    public function get($key, $default = null)
    {
        return $this->inputs->get($key, $default);
    }

    public function only(array $keys)
    {
        return $this->inputs->only($keys, true);
    }

    public function except(array $keys)
    {
        return $this->inputs->except($keys, true);
    }

    public function file($key)
    {
        $_file = $this->files[$key];
        return $this->hasFile($key)? $this->makeInputFile($_file) : NULL;
    }

    public function multiFiles($key)
    {
        if(!$this->hasMultiFiles($key)) return array();

        $input_files = array();

        $files = $this->files[$key];

        $names = $files["name"];
        $types = $files["type"];
        $temps = $files["tmp_name"];
        $errors = $files["error"];
        $sizes = $files["size"];

        foreach($temps as $i => $tmp) {
            if(empty($tmp) OR !is_uploaded_file($tmp)) continue;

            $_file = array(
                'name' => $names[$i],
                'type' => $types[$i],
                'tmp_name' => $tmp,
                'error' => $errors[$i],
                'size' => $sizes[$i]
            );

            $input_files[] = $this->makeInputFile($_file);
        }

        return $input_files;
    }

    public function hasFile($key)
    {
        $file = $this->files[$key];

        if(!$file) return FALSE;

        $tmp = $file["tmp_name"];

        if(!is_string($tmp)) return FALSE;

        return is_uploaded_file($tmp);
    }

    public function hasMultiFiles($key)
    {
        $files = $this->files[$key];

        if(!$files) return FALSE;

        $uploaded_files = $files["tmp_name"];
        if(!is_array($uploaded_files)) return FALSE;

        foreach($uploaded_files as $tmp_file) {
            if(!empty($tmp_file) AND is_uploaded_file($tmp_file)) return TRUE;
        }

        return FALSE;
    }

    protected function makeInputFile(array $_file)
    {
        return new UploadedFile($_file);
    }

    public function defineRoute(Route $route)
    {
        if($this->route) return;

        $this->params = $route->params;
        $this->route = $route;
    }

    public function route()
    {
        return $this->route;
    }

    public function isMethod($method)
    {
        return strtoupper($this->method()) == strtoupper($method);
    }

    public function isMethodGet()
    {
        return $this->isMethod(static::METHOD_GET);
    }

    public function isMethodPost()
    {
        return $this->isMethod(static::METHOD_POST);
    }

    public function isMethodPut()
    {
        return $this->isMethod(static::METHOD_PUT);
    }

    public function isMethodPatch()
    {
        return $this->isMethod(static::METHOD_PATCH);
    }

    public function isMethodDelete()
    {
        return $this->isMethod(static::METHOD_DELETE);
    }

    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function isHttps()
    {
        if( isset($_SERVER['HTTPS'] ) ) {
            return true;
        } else {
            return false;
        }
    }

    public function isHttp()
    {
        return !$this->isHttps();
    }

    public function isAjax()
    {
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        } else {
            return false;
        }
    }

    public function isJson()
    {
        return $this->json() !== NULL;
    }

    public function json()
    {
        $raw_body = $this->body();
        $json = json_decode($raw_body, true);

        if(is_array($json)) {
            $data = new Bag($json);
        } else {
            $data = NULL;
        }

        return $data;
    }

    public function body()
    {
        return file_get_contents("php://input");
    }

    public function param($key, $default = null)
    {
        $params = $this->params;
        return (array_key_exists($key, $params))? $params[$key] : $default;
    }

    public function params()
    {
        return $this->params;
    }

    public function can($method,$path=null)
    {
        $name = "";
        if(method_exists($path,'getName') && $path==null)
        {
            $name = $this->app->router->findMatch($this->path(),"GET")->getName();
        }else{
            $name = $this->app->router->findMatch($path,"GET");
            if($name !==null && method_exists($name,'getName'))
            {
                $name = $name->getName();
            }
            if($name==null)
            {
                $segment = "";
                if($this->segment(4) !== null){
                    $segment = $this->segment(4);
                }
                $path = rtrim(str_replace($segment,"", $path),'/');
                $name = $this->app->router->findMatch($path,"GET");
                if($name !==null && method_exists($name,'getName'))
                {
                    $name = $name->getName();
                }
                if($name==null)
                {
                    $segment = "";
                    if($this->segment(3) !== null){
                        $segment = $this->segment(3);
                    }
                    $path = rtrim(str_replace($segment,"", $path),'/');
                    $name = $this->app->router->findMatch($path,"GET");
                    if($name !==null &&  method_exists($name,'getName'))
                    {
                        $name = $name->getName();
                    }
                    if($name==null)
                    {
                        $segment = "";
                        if($this->segment(2) !== null){
                            $segment = $this->segment(2);
                        }
                        $path = rtrim(str_replace($segment,"", $path),'/');
                        $name = $this->app->router->findMatch($path,"GET");
                        if($name !==null && method_exists($name,'getName'))
                        {
                            $name = $name->getName();
                        }
                        if($name==null)
                        {
                            $segment = "";
                            if($this->segment(1) !== null){
                                $segment = $this->segment(1);
                            }
                            $path = rtrim(str_replace($segment,"", $path),'/');
                            $name = $this->app->router->findMatch($path,"GET");
                            if($name !==null && method_exists($path,'getName') && $path==null)
                            {
                                $name = $name->getName();
                            }else{
                                return $this->checkcan($method,$name);
                            }
                        }else{
                            return $this->checkcan($method,$name);
                        }
                    }else{                    
                        return $this->checkcan($method,$name);
                    }
                }else{
                    return $this->checkcan($method,$name);
                }
            }else{
                return $this->checkcan($method,$name);
            }
        }
    }

    public function checkcan($method,$name)
    {
        if(Session::get("user")!=null && Arr::inarray(Session::get("user")['access'],"route",$name))
        {
            $key = Arr::inarraykey(Session::get("user")['access'],"route",$name);
            if(is_int($key) && Session::get("user")['access'][$key][$method] > 0)
            {
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function countcan($method,$array)
    {
        if(is_array($array))
        {
            $count = 0;
            foreach ($array as $key => $val) {
                if(is_array($val) && isset($val['url']))
                {
                    if($this->can($method,$val['url']))
                    {
                        $count = $count+1;
                    }
                }else{
                    if(is_array($val) && !is_int($key))
                    {
                        foreach ($val as $k => $v) {
                            if(is_array($v) && isset($v['url']))
                            {
                                if($this->can($method,$v['url']))
                                {
                                    $count = $count+1;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $count;
    }

    public function countsub($menuitem)
    {
        if(array_key_exists("type",$menuitem))
        {
            if($menuitem['type']=="noauth")
            {
                return true;
            }
        }else{
            if($this->countcan("acc",$menuitem) > 0)
            {
                return true;
            }else{
                return false;
            }
        }
    }

}
