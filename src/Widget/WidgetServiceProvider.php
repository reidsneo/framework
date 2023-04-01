<?php 

namespace Neko\Framework\Widget;

use Neko\Framework\App;
use Neko\Framework\Http\Response;
use Neko\Framework\Provider;
use Neko\Framework\View\View;
use Neko\Framework\Widget\WidgetManager;
use Neko\facade\Widget;

class WidgetServiceProvider extends Provider {

    public function register()
    {
        $app = $this->app;
		$app['widget:Neko\Framework\Widget'] = $app->container->singleton(function() use ($app) {
			return new WidgetManager($app->container);
		});

    }


    public function boot()
    {
        // $app = $this->app;
    }

    public function shutdown()
    {
        // $app = $this->app;
        // $app->blade->directive('widget', function($exp) use ($app) {
        //     return $app['widget']->render($exp);
        // });
    }

}
