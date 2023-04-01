<?php 

namespace Neko\Framework\View;

use Neko\Framework\App;
use Neko\Framework\Http\Response;
use Neko\Framework\Provider;
use Neko\Framework\View\View;

class ViewServiceProvider extends Provider {

    public function register()
    {
        $app = $this->app;
        $app['view:Neko\Framework\View\View'] = $app->container->singleton(function($container) use ($app) {
            $theme="";
            foreach ($app->request->route()->getMiddlewares() as $key => $val) {
                if (strpos($val, 'theme') !== FALSE) {
                    $theme=explode(":",$val)[1];
                }
            }
            if($theme!="")
            {
                $theme=$theme;
            }else{
                $theme=$app->config->get('user_theme');
            }
            $view_path = app_path()."/themes/".$theme;
            $engine = $app->config->get('view.engine');

            if(!$engine) {
                $engine = new BasicViewEngine($view_path);
            } elseif(is_string($engine)) {
                $engine = $container->make($engine, [$view_path]);
            }

            return new View($container['app'], $engine);
        });
    }

    public function boot()
    {
        $app = $this->app;
        $app->response->macro('view', function($file, array $data = array()) use ($app) {
            $rendered = $app->view->render($file, $data);
            return $app->response->html($rendered);
        });
    }

    public function shutdown()
    {
        
    }

}
