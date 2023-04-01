<?php

namespace Neko\Framework\Widget;

// use Illuminate\Contracts\Cache\Repository;
// use Illuminate\Contracts\Container\Container;
use Neko\Framework\Container;
use Neko\Framework\Util\Str;
use Neko\Framework\Widget;

class WidgetManager
{
    const WIDGET_CONTAINER_PREFIX = '';
    const CACHE_PREFIX = 'widget_';

    protected $container;

    protected static $widgets = [];

    /**
     * @var Repository
     */
    private $cache;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function render($widget)
    {
        $args = func_get_args();
        $configs = count($args) > 1 && is_array($args[1]) ? $args : [];
        try {
            return $this->buildWidget($widget)->withConfig($configs)->handle();
        }
        catch (\exception $e) {
        }
    }

    /**
     * @param $widget
     * @return Widget
     */
    protected function buildWidget($widget)
    {
        if ($widget instanceof Widget) {
            return $widget;
        }

        if (is_string($widget) === false) {
            throw new \InvalidArgumentException(sprintf('Widget name must be string'));
        }
        //cleanup fix for directive
        $widget = str_replace(array('"',"'"),array("",""),$widget);
        $widget = class_exists($widget) ? $widget : $this->generateContainerTag($widget);

        if (isset(static::$widgets[$widget]) === false) {
            throw new \RuntimeException(sprintf('Widget %s is not exists', $widget));
        }
        return $this->container->make(static::$widgets[$widget]);
    }

    /**
     * @param $abstract
     * @param null $instance
     * @return $this
     */
    public function registerWidget($abstract, $instance = null)
    {
        if (is_string($abstract) === false) {
            throw new \InvalidArgumentException(sprintf('Widget name must be string'));
        }

        if (class_exists($abstract)) {
            $instance = $instance !== null ? $instance : $abstract;

            static::$widgets[$abstract] = $instance;

            return $this;
        }
        if ($instance === null || class_exists($instance) === false) {
            throw new \InvalidArgumentException('%s Could not resolved');
        }

        static::$widgets[$this->generateContainerTag($abstract)] = $instance;
        // echo "up ====================================\n";
        // var_dump(static::$widgets);
        // var_dump($this->generateContainerTag($abstract));
        // echo "down ====================================\n";
        return $this;
    }

    public function registerWidgets(array $widgets = [])
    {
        global $app;
        foreach ($widgets as $key => $value) {
            $name = is_numeric($key) ? $value : $key;
            $app['widget']->registerWidget($name, $value);
        }
    }


    protected function generateContainerTag($widget)
    {
        return static::WIDGET_CONTAINER_PREFIX . Str::snake_case($widget);
    }

}