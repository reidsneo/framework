<?php 

namespace Neko\Framework;

use ArrayAccess;
use Closure;
use ErrorException;
use Exception;
use InvalidArgumentException;
use Neko\Framework\Dumper\CliDumper;
use Neko\Framework\Dumper\HtmlDumper;
use Neko\Framework\Exceptions\FatalErrorException;
use Neko\Framework\Exceptions\HttpErrorException;
use Neko\Framework\Exceptions\HttpNotFoundException;
use Neko\Framework\Http\Request;
use Neko\Framework\Http\Response;
use Neko\Framework\Router\Route;
use Neko\Framework\Router\Router;
use Neko\Framework\Util\Str;
use Neko\Framework\View\View;
use Neko\Framework\Shortcode\ShortcodeFacade;

class App implements ArrayAccess {

    use MacroableTrait;

    const VERSION = '1.1.0';

    protected static $instances = [];

    protected static $default_instance = 'default';

    public $app;
    public $container;

    public $plugin_list = array();
    public $plugin_active = array();
    public $language_map = array();

    protected $name;

    protected $booted = false;

    protected $middlewares = array();

    protected $global_middlewares = array();

    protected $waiting_list_providers = array();

    protected $providers = array();

    protected $exception_handlers = array();
    
    public $kernel = __DIR__."/Kernel.php";

    /**
     * Constructor
     *
     * @param   string $name
     * @param   array $configs
     * @return  void
     */
    public function __construct($name, array $configs = array())
    {
        $app = $this;
        $this->name = $name;
        $default_configs = [];
        $configs = array_merge($default_configs, $configs);

        $this->container = new Container;
        $this['app'] = $this;
        $this['config'] = new Configurator($configs);
        $this['router'] = new Router($this);
        $this['hook'] = new Hook($this);
        $this['request'] = new Request($this);
        $this['response'] = new Response($this);
        $this['shortcode'] = new ShortcodeFacade;

        static::$instances[$name] = $this;

        if(count(static::$instances) == 1) {
            static::setDefaultInstance($name);
        }

        $this->registerErrorHandlers();
        $this->registerBaseHooks();
        $this->registerDefaultMacros();
        $this->registerBaseProviders();
        
        foreach (glob(str_replace("/", DS, rtrim(str_replace('public','app',getcwd()), '/'))."/language/*/*.php") as $filename) {
            $lang =  Str::GetStringBetween(str_replace("/", DS,$filename),str_replace("/", DS,"language".DS),str_replace("/", DS,DS.basename($filename)));
            $file = str_replace(".php","",basename($filename));
            $name = "app";
            $this->language_map[$file][$lang] = $name;
        }
    }

    /**
     * Register a Service Provider into waiting lists
     *
     * @param   string $class
     */
    public function provide($class)
    {
        $this->providers[$class] = $provider = $this->container->make($class);
        if(false === $provider instanceof Provider) {
            throw new InvalidArgumentException("Provider {$class} must be instance of Neko\\Framework\\Provider", 1);
        }
        $provider->register();
    }

    /**
     * Register hook
     *
     * @param   string $event
     * @param   Closure $callable
     */
    public function on($event, Closure $callable)
    {
        return $this->hook->on($event, $callable);
    }

    /**
     * Register hook once
     *
     * @param   string $event
     * @param   Closure $callable
     */
    public function once($event, Closure $callable)
    {
        return $this->hook->once($event, $callable);
    }

    /**
     * Set middleware
     *
     * @param   string $name
     * @param   mixed $callable
     * @return  void
     */
    public function setMiddleware($name, $callable)
    {
        $this->middlewares[$name] = $callable;
    }

    /**
     * Check middleware is registered or not
     *
     * @param   string $name
     * @return  void
     */
    public function hasMiddleware($name)
    {
        return isset($this->middlewares[$name]);
    }

    /**
     * Add global middleware
     *
     * @param   string|callable $name_or_callable
     * @return  void
     */
    public function useMiddleware($name_or_callable)
    {
        if (!is_callable($name_or_callable)) {
            if (is_string($name_or_callable) AND $this->hasMiddleware($name_or_callable)) {
                $callable = $this->middlewares[$name_or_callable];
            } else {
                throw new InvalidArgumentException("Cannot use global middleware. Middleware must be callable or registered middleware", 1);
            }
        } else {
            $callable = $name_or_callable;
        }

        $this->global_middlewares[] = $callable;
    }

    /**
     * Register GET route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Neko\Framework\Routing\Route
     */
    public function get($path, $action)
    {
        return $this->route('GET', $path, $action);
    }

    /**
     * Register POST route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Neko\Framework\Routing\Route
     */
    public function post($path, $action)
    {
        return $this->route('POST', $path, $action);
    }

    /**
     * Register PUT route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Neko\Framework\Routing\Route
     */
    public function put($path, $action)
    {
        return $this->route('PUT', $path, $action);
    }

    /**
     * Register PATCH route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Neko\Framework\Routing\Route
     */
    public function patch($path, $action)
    {
        return $this->route('PATCH', $path, $action);
    }

    /**
     * Register DELETE route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Neko\Framework\Routing\Route
     */
    public function delete($path, $action)
    {
        return $this->route('DELETE', $path, $action);
    }

    /**
     * Register OPTIONS route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Neko\Framework\Routing\Route
     */
    public function options($path, $action)
    {
        return $this->route('OPTIONS', $path, $action);
    }

    /**
     * Register Group route
     *
     * @param   string $path
     * @param   Closure $grouper
     * @return  Neko\Framework\Routing\Route
     */
    public function group($prefix, Closure $grouper)
    {
        return $this->router->group($prefix, $grouper);
    }

    /**
     * Registering a route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Neko\Framework\Routing\Route
     */
    public function route($methods, $path, $action)
    {
        $route = $this->router->add($methods, $path, $action);
        if (!empty($this->global_middlewares)) {
            $route->middleware($this->global_middlewares);
        }
        return $route;
    }

    /**
     * Handle specified exception
     */
    public function handle($exception_class, Closure $fn)
    {
        $this->exception_handlers[$exception_class] = $fn;
    }

    /**
     * Booting app
     *
     * @return  boolean
     */
    public function boot()
    {
        if($this->booted) return false;

        $app = $this;

        $providers = $this->providers;
        foreach($providers as $provider) {
            $provider->boot();
        }

        // reset providers, we don't need them anymore moved to shutdown
        //$this->providers = [];

        return $this->booted = true;
    }


    public function shutdown()
    {
        $providers = $this->providers;
        foreach($providers as $provider) {
            $provider->shutdown();
        }
        $this->providers = [];
    }

    /**
     * Run application
     *
     * @param   string $path
     * @param   string $method
     * @return  void
     */
    public function run($method = null, $path = null)
    {
        try {
            $this->boot();

            $path = $path ?: $this->request->path();

            // if(app()->config['general']['config']['maintenance'] == "true")
            // {
            //     if(!Str::contains(url_path(), app()->admin_url) AND !Str::contains(url_path(), "/js") AND !Str::contains(url_path(), "/asset") AND !Str::contains(url_path(), "/theme") AND !Str::contains(url_path(), "maintenance"))
            //     {
            //         $this->hook->apply('maintenance_mode', [$path]);
            //     }
            // }

            $method = $method ?: $this->request->server['REQUEST_METHOD'];

            /**
             * for HEAD request
             * instead to add some code in router that will slow down performance
             * we trick it by change it to GET for dispatching only
             */
            $matched_route = $this->router->findMatch($path, $method == 'HEAD'? 'GET' : $method);

            if(!$matched_route) {
                return $this->notFound();
            }

            $this->request->defineRoute($matched_route);
            $this->hook->apply(strtoupper($method), [$matched_route, $this]);

            $middlewares = $matched_route->getMiddlewares();
            $action = $matched_route->getAction();
    

            $actions = $this->makeActions($middlewares, $action);
            $this->shutdown();
            if(isset($actions[0])) {
                $actions[0]();
            }

            $this->response->send();
            
            return $this;
        } catch (Exception $e) {
            return $this->exception($e);
        }
    }

    public function exception(Exception $e)
    {
        $status_code = $e->getCode();
        $status_message = $this->response->getStatusMessage($status_code);

        // if status message is null, 
        // that mean 'exception code' is not one of 'available http response status codes'
        // so, change it to 500
        if(!$status_message) {
            $status_code = 500;
        }

        $this->response->setStatus($status_code);

        // because we register exception by handle() method,
        // we will manually catch exception class
        // first we need to get exception class
        $exception_class = get_class($e);

        // then we need parent classes too
        $exception_classes = array_values(class_parents($exception_class));
        array_unshift($exception_classes, $exception_class);

        // now $exception_classes should be ['CatchedException', 'CatchedExceptionParent', ..., 'Exception']
        // next, we need to get exception handler
        $custom_handler = null;
        foreach($exception_classes as $ex_class) {
            if(array_key_exists($ex_class, $this->exception_handlers)) {
                $custom_handler = $this->exception_handlers[$ex_class];
            }

            $this->hook->apply($ex_class, [$e]);
        }

        
        
        if($this->config['app.debug'] == "true") {
            $this->debugException($e);
        } else {
            $this->hook->apply('error', [$e]);
            if($custom_handler) {
                $this->container->call($custom_handler, [$e]);
            } elseif($e instanceof HttpNotFoundException) {
                $this->response->html("Error 404! Page not found");
            }else {
                $this->response->html("Something went wrongs");
            }
        }

        $this->response->send();
        return $this;
    }

    protected function debugException(Exception $e)
    {
        $debugger = PHP_SAPI == 'cli'? new CliDumper : new HtmlDumper;
        $this->response->html($debugger->render($e));
    }

    /**
     * Stop application
     *
     * @return void
     */
    public function stop()
    {
        $this->hook->apply("app.exit", [$this]);
        exit();
    }

    /**
     * Not Found
     */
    public function notFound()
    {
        $method = $this->request->server['REQUEST_METHOD'];
        $path = $this->request->path();

        if($this->request->route()) {
            $message = "Error 404! Looks like you are throwing this manually";
        } else {
            $message = "Error 404! No route matched with '{$method} {$path}'";
        }

        $this->hook->apply('before.notfound', [substr($this->request->path(),1)]);
        $this->hook->apply('after.notfound', [substr($this->request->path(),1)]);

        throw new HttpNotFoundException($message);
    }

    /**
     * Abort app
     *
     * @param   int $status
     *
     * @return  void
     */
    public function abort($status, $message = null)
    {
        if($status == 404) {
            return $this->notFound();
        } else {
            throw new HttpErrorException;
        }
    }

    /**
     * Set default instance name
     *
     * @param   string $name
     */
    public static function setDefaultInstance($name)
    {
        static::$default_instance = $name;
    }

    /**
     * Getting an application instance
     *
     * @param   string $name
     */
    public static function getInstance($name = null)
    {
        if(!$name) $name = static::$default_instance;
        return static::$instances[$name];
    }

    /**
     * Make/build app actions
     *
     * @param   array $middlewares
     * @param   mixed $controller
     * @return  void
     */
    protected function makeActions(array $middlewares, $controller)
    {
        $app = $this;
        $actions = array_merge($middlewares, [$controller]);
        $index_controller = count($actions)-1;

        $actions = [];
        foreach($middlewares as $i => $action) {
            $actions[] = new ActionMiddleware($this, $i, $action);
        }

        $actions[] = new ActionController($this, count($middlewares), $controller);

        $this['actions'] = $actions;
        return $actions;
    }

    /**
     * Resolving middleware action
     */
    public function resolveMiddleware($middleware_action, array $params = array())
    {
        if(is_string($middleware_action)) {
            $explode_params = explode(':', $middleware_action);

            $middleware_name = $explode_params[0];
            if(isset($explode_params[1])) {
                $params = array_merge($params, explode(',', $explode_params[1]));
            }

            // if middleware is registered, get middleware
            if(array_key_exists($middleware_name, $this->middlewares)) {
                // Get middleware. so now, callable should be string Foo@bar, Closure, or function name
                $callable = $this->middlewares[$middleware_name];
                $resolved_callable = $this->resolveCallable($callable, $params);
            } else {
                // otherwise, middleware_name should be Foo@bar or Foo
                $callable = $middleware_name;
                $resolved_callable = $this->resolveCallable($callable, $params);
            }
        } else {
            $resolved_callable = $this->resolveCallable($middleware_action, $params);
        }
        
        if(!is_callable($resolved_callable)) {
            if(is_string($middleware_action))
            {
                $invalid_middleware = 'String';
            }else if(is_array($middleware_action)) {
                $invalid_middleware = 'Array';
            } else {
                $invalid_middleware = $middleware_action;
            }

            throw new \Exception('Middleware "'.$invalid_middleware.'" is not valid middleware or it is not registered');
        }

        return $resolved_callable;
    }

    public function resolveController($controller_action, array $params = array())
    {
        return $this->resolveCallable($controller_action, $params);
    }

    /**
     * Register base hooks
     */
    protected function registerBaseHooks()
    {

    }

    /**
     * Register base providers
     */
    public function registerBaseProviders()
    {
        $base_providers = [
            'Neko\Framework\View\ViewServiceProvider',
            'Neko\Framework\Widget\WidgetServiceProvider'
        ];

        foreach($base_providers as $provider_class) {
            $this->provide($provider_class);
        }
    }

    /**
     * Register custom providers
     */
    public function registerProviders($providers)
    {
        foreach($providers as $provider_class) {
            $this->provide($provider_class);
        }
    }

    /**
     * Register custom providers
     */
    public function registerMiddlewares($middlewares)
    {
        if($middlewares!=null)
        {
            foreach ($middlewares as $name => $middleware_class) {
                //echo $name." ".$middleware_class;
                $this->setmiddleware($name,$middleware_class);
            }
        }
        
        // foreach($middlewares as $middleware_class) {
        //     $this->setmiddleware($middleware_class,$middleware_class);
        // }
    }

    public function registerName($name){

    }

    public function registerLang($name){
       // echo str_replace(".", DIRECTORY_SEPARATOR,$name);
    }

    /**
     * Register error handler
     */
    public function registerErrorHandlers()
    {
        $app = $this;

        // set error handler
        set_error_handler(function($severity, $message, $file, $line) use ($app) {
            if (!(error_reporting() & $severity)) {
                return;
            }


            $exception = new ErrorException($message, 500, $severity, $file, $line);
            $app->exception($exception);
            $app->stop();
        });

        // set fatal error handler
        register_shutdown_function(function() use ($app) {
            $error = error_get_last();
            if($error) {
                $errno   = $error["type"];
                $err_file = $error["file"];
                $err_line = $error["line"];
                $errstr  = $error["message"];

                $message = "[$errno] $errstr in $err_file line $err_line";

                if($_ENV['APP_DEBUG'] == "true")
                {
                    $exception = new FatalErrorException($message, 500, 1, $err_file, $err_line);
                    $app->exception($exception);
                }else{
                    $start = strpos($message, "Uncaught Error: ");
                    if ($start === false) {
                        return false;
                    }
                    $start += 16;
                    $end = strpos($message, " in ", $start);
                    if ($end === false) {
                        return false;
                    }
                    $this->response->html("[".substr($message, $start, $end-$start)."]");
                    $this->response->send();
                }
                $app->stop();

            }
        });
    }

    /**
     * Register default macros
     */
    protected function registerDefaultMacros()
    {
        $this->macro('resolveCallable', function($unresolved_callable, array $params = array()) {
            $app = $this;
            if(is_string($unresolved_callable)) {
                // in case "Foo@bar:baz,qux", baz and qux should be parameters, separate it!
                $explode_params = explode(':', $unresolved_callable);

                $unresolved_callable = $explode_params[0];
                if(isset($explode_params[1])) {
                    $params = array_merge($params, explode(',', $explode_params[1]));
                }

                // now $unresolved_callable should be "Foo@bar" or "foo",
                // if there is '@' character, transform it to array class callable
                $explode_method = explode('@', $unresolved_callable);
                if(isset($explode_method[1])) {
                    $callable = [$explode_method[0], $explode_method[1]];
                } else {
                    //11/14/2020, 16:41:22 
                    //allow load from custom middleware class folder
                    return !is_callable($unresolved_callable,"handle")? false : function() use ($app, $unresolved_callable, $params) {
                        if(class_exists($unresolved_callable))
                        {
                            $callable = new $unresolved_callable;
                            $closure = Closure::fromCallable ( [$callable, 'handle'] );
                            if ($callable instanceof Closure) {
                                $callable = Closure::bind($closure, $app, App::class);
                            }
                            return $app->container->call($closure, $params);
                        }else{
                            throw new \Exception('Middleware or Class "'.$unresolved_callable.'" is not valid class or middleware other hand it is not registered');
                        }
                    };
                }

            } else {
                $callable = $unresolved_callable;
            }
            
            // last.. wrap callable in Closure
            return !is_callable($callable)? false : function() use ($app, $callable, $params) {
                if ($callable instanceof Closure) {
                    $callable = Closure::bind($callable, $app, App::class);
                }
                return $app->container->call($callable, $params);
            };
        });

        $this->macro('baseUrl', function($path) {
            $path = '/'.trim($path, '/');
            $base_url = trim($this->config->get('app.base_url', 'http://localhost:8000'), '/');

            return $base_url.$path;
        });

        $this->macro('asset', function($path) {
            return $this->baseUrl($path);
        });

        $this->macro('indexUrl', function($path) {
            $path = trim($path, '/');
            $index_file = trim($this->config->get('app.index_file', ''), '/');
            return $this->baseUrl($index_file.'/'.$path);
        });

        $this->macro('routeUrl', function($route_name, array $params = array()) {
            if($route_name instanceof Route) {
                $route = $route_name;
            } else {
                $route = $this->router->findRouteByName($route_name);
                if(! $route) {
                    throw new \Exception("Trying to get url from unregistered route named '{$route_name}'");
                }
            }

            $path = $route->getPath();
            $path = str_replace(['(',')'], '', $path);
            foreach($params as $param => $value) {
                $path = preg_replace('/:'.$param.'\??/', $value, $path);
            }

            $path = preg_replace('/\/?\:[a-zA-Z0-9._-]+/','', $path);

            return $this->indexUrl($path);
        });

        $this->macro('redirect', function($defined_url) {
            if(preg_match('/http(s)?\:\/\//', $defined_url)) {
                $url = $defined_url;
            } elseif($this->router->findRouteByName($defined_url)) {
                $url = $this->routeUrl($defined_url);
            } else {
                $url = $this->indexUrl($defined_url);
            }

            $this->hook->apply('response.redirect', [$url, $defined_url]);

            header("Location: ".$url);
            exit();
        });


        $this->macro('dd', function() {
            var_dump(func_get_args());
            exit();
        });

        $app = $this;
        $this->response->macro('redirect', function($defined_url) use ($app) {
            return $app->redirect($defined_url);
        });
    }

    public function bind($key, $value)
    {
        if (is_string($value)) {
            if (!class_exists($value)) {
                throw new InvalidArgumentException("Cannot bind {$value}, class {$value} is not exists");
            }
            
            $value = function($container) use ($value) {
                return $container->getOrMake($value);
            };
        }

        $this->container->register($key, $value);
    }

    public function title() {
        if($this->config['app.title_path']=="true")
        {
            if($this->request->segment(1)=="")
            {
                return $this->config['app.title'];
            }else{
                return $this->config['app.title']. " | ".str_replace("/"," > ", Str::title(ltrim($this->request->path(), "/")));
            }
        }else{
            return $this->config['app.title'];
        }
    }

    public function description() {
        if(isset($this->configs['general']['web']['description']))
        {
            return $this->configs['general']['web']['description'];
        }else{
            return null;
        }
    }

    public function meta() {
        return '<link rel="canonical" href="https://www.neoblackant.com/">
        <meta name="thumbnail" content="None" />
        <meta name="msvalidate.01" content="3D12095F1193FB34A99AEC3A6839D3C1" />
        <meta name="p:domain_verify" content="461cef7d1c33614f6741f1219779b382" />
    
        <meta property="twitter:account_id" content="15936194" />
        <link href="https://plus.google.com/102140742001641246827" rel="publisher" />
    
        <!-- https://developers.google.com/search/docs/guides/intro-structured-data Person,Website -->
        <script type="application/ld+json">
            {
                "@context": "http://schema.org",
                "@type": "Organization",
                "name": "Twilio",
                "url": "https://www.twilio.com",
                "sameAs": [
                    "https://twitter.com/twilio",
                    "https://www.facebook.com/TeamTwilio",
                    "https://plus.google.com/+twilio",
                    "https://www.linkedin.com/company/twilio-inc-",
                    "https://instagram.com/twilio/"
                ]
            }
        </script>
        <link rel="shortcut icon" href="/docs/static/img/favicons/favicon.188c4a31d.ico" />
        <link rel="apple-touch-icon" href="/docs/static/img/favicons/favicon_57.188c4a31d.png" />
        <link rel="apple-touch-icon" sizes="72x72" href="/docs/static/img/favicons/favicon_72.188c4a31d.png" />
        <link rel="apple-touch-icon" sizes="114x114" href="/docs/static/img/favicons/favicon_114.188c4a31d.png" />
    
        <meta property="og:description" content="" />
        <meta property="og:title" content="Twilio - Communication APIs for SMS, Voice, Video and Authentication">
        <meta property="og:site_name" content="Twilio" />
        <meta property="og:url" content="https://www.twilio.com/" />
        <meta property="og:type" content="website" />
        <meta property="og:image" content="https://www.twilio.com/marketing/bundles/company-brand/img/logos/red/twilio-logo-red.png" />';
    }

    function key() {
        $h78e6221f6393=false;
        $cc845c84a302a=base64_decode('QUVTLTI1Ni1DQkM=');
        $b73eeac3fa1a0=$_ENV[base64_decode('QVBQX1NFQ1JFVA==')];
        $c6c9f2b7b907a=$_ENV[base64_decode('QVBQX0tFWQ==')];
        $j3c6e0b8a9c15=hash(base64_decode('c2hhMjU2'),$b73eeac3fa1a0);
        $ff0b53b2da041=substr(hash(base64_decode('c2hhMjU2'),$c6c9f2b7b907a),0,16);
        $h78e6221f6393=openssl_encrypt($_ENV[base64_decode('QVBQX0tFWQ==')],$cc845c84a302a,$j3c6e0b8a9c15,0,$ff0b53b2da041);
        $h78e6221f6393=base64_encode($h78e6221f6393);
        return $h78e6221f6393;
    }

    /**
     * ---------------------------------------------------------------
     * Setter and getter
     * ---------------------------------------------------------------
     */
    public function __set($key, $value)
    {
        $this->container->register($key, $value);
    }

    public function __get($key)
    {
        return $this->container->get($key);
    }

    /**
     * ---------------------------------------------------------------
     * ArrayAccess interface methods
     * ---------------------------------------------------------------
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value) {
        return $this->container->register($key, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($key) {
        return $this->container->has($key);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key) {
        return $this->container->remove($key);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key) {
        return $this->container->get($key);
    }

}
