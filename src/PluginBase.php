<?php 
namespace Neko\Framework;

use Neko\Framework\App;
use Neko\Framework\Provider;
use Neko\Menu\Menu;
use Neko\Menu\Link;
use Neko\Menu\Html;
use Neko\Facade\Session;
use Neko\Framework\Http\Request;
use Neko\Framework\Util\Arr;
use Neko\Framework\Util\Str;
use Neko\Database\DB;
class PluginBase extends Provider {

    public $menu_admin;
    public $menu;

    function __construct() {
        global $app;
        $this->menu_admin = null;
        $this->menu = null;
        $menu = null;
        
        if(method_exists($this,"pluginMenuAdmin") &&  is_array($this->pluginMenuAdmin()))
        {
            $app->menu_admin_raw = array_merge_recursive($app->menu_admin_raw,$this->pluginMenuAdmin());
        }

        if(method_exists($this,"pluginMenu") && is_array($this->pluginMenu()))
        {
            if (Arr::multiKeyExists($this->pluginMenu(),'database')) {
               $dbtable = Arr::pluck($this->pluginMenu(),'database')[0];
               $dbbaseurl = Arr::pluck($this->pluginMenu(),'url')[0];
               $dbmenu = db::table($dbtable)->select("name","auth","enabled")->get();
               $dbmenuraw = array();
               foreach ($dbmenu as $key => $val) {
                   $auth = "";
                   if($val['auth']==0)
                   {
                       $auth = "noauth";
                   }
                   if($val['enabled']==1)
                   {
                    $dbmenuraw[] = array(
                        "title" => Str::title($val['name']),
                        "url" => $dbbaseurl."/".Str::slug($val['name']),
                        "icon" => "",
                        "type" => $auth
                     );
                   }
                   
               }
            //    var_dump($app->menu_user_raw);
            //    var_dump($dbmenu);
            //    var_dump($dbmenuraw);
               $module = array_key_first($this->pluginMenu());
               $menu = array($module => array_merge_recursive($this->pluginMenu()[$module],$dbmenuraw));
            }else{
                $menu = $this->pluginMenu();
            }
            $app->menu_user_raw = array_merge_recursive($app->menu_user_raw,$menu);
        }

        if(method_exists($this,'pluginCronJobs') && is_array($this->pluginCronJobs()))
        {
            $app->plugin_cron = array_merge_recursive($app->plugin_cron,$this->pluginCronJobs());
        }

      //  if(is_array($this->pluginMenuAdmin()))
      //  {
      //      $items = $this->pluginMenuAdmin();
      //      //echo "========================================================================================";
      //      foreach ($items as $key => $val) {
      //          if(array_key_exists('title',$val))
      //          {
      //              $app->menu_admin = $app->menu_admin->addIf($this->app->request->can("acc",$val['url']),Link::to($val['url'], '<i class="icon-'.$val['icon'].'"></i>'.' <span>'.$val['title'].'</span>')->addClass('nav-link'));
      //              //$this->app->debug["messages"]->addMessage($val['url']."  ".$this->app->request->can("acc",$val['url']));
      //          }else{
      //              self::recursivemenu($app->menu_admin,$key,$val);
      //          }
      //      }
      //  }

        ////$app->debug["messages"]->addMessage(Session::get("user")['access']);
        ////$app->debug["messages"]->addMessage(self::isacc());//$app->router->getRoutes());
    }

//   function recursivemenu($menu,$key,$array)
//   {
//       if(is_array($array) && count($array)>0 && !array_key_exists('title',$array) && $key!="icon")
//       {
//           $subitem = $array;
//           unset($subitem['icon']);
//           
//           $this->menu_admin = $menu
//               ->submenuif(
//                   $this->app->request->countsub($subitem),
//                   Link::to('#', '<i class="icon-'.$array['icon'].'"></i>'.' <span>'.$key.'</span>')
//                   ->addClass('nav-link'), function (Menu $menu) use ($array,$key,$subitem) {
//                       $this->app->debug["messages"]->addMessage($key ." ".$this->app->request->countcan("acc",$subitem));
//                       $menu
//                           ->addParentClass('nav-item-submenu')
//                           ->addItemParentClass('nav-item')
//                           ->addClass('nav nav-group-sub')
//                           ->setActiveClass('nav-item-expanded nav-item-open')
//                           ->setAttribute('data-submenu-title',$key);
//                       
//                       foreach ($array as $k => $v) {
//                           if(is_array($v) && array_key_exists('title',$v))
//                           {
//                            $menu
//                               ->addIf($this->app->request->can("acc",$v['url']),Link::to($v['url'], '<i class="icon-'.$v['icon'].'"></i>'.' <span>'.$v['title'].'</span>')
//                               ->addClass('nav-link'));
//                               //$this->app->debug["messages"]->addMessage($v['url']."  ".$this->app->request->can("acc",$v['url']));
//                           }else{
//                               self::recursivemenu($menu,$k,$v);
//                           }
//                       }
//                   }
//               );
//       }
//   }
//
    public function register()
	{

    }
    
	public function boot()
	{
        echo "aye";
    }

    public function pluginProviders()
    {
        return [];
    }
    
/*
        //echo "constructed<br>";
        //echo get_class($this);
        //var_dump($app);
        //parent::__construct($app);
        //var_dump($app->router);
        //var_dump(get_declared_classes());
        if (class_exists(get_class($this))) {
            echo "<br>exists<br>";
            //$this->boot($app);
        }else{
            
        }
        //self::doregister();
        //echo "<br>!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!";
        //echo "<br>!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!";
        //$this->boot();
       // $this->doregister();
       // self::register();
    }
*/
    //public function doregister()
    //{
        //echo "<br>register pluginbase";
        
        /*
        var_dump($this->pluginProviders());
        echo "<br>";*/
        //$example = $this->pluginProviders();
        //foreach ($example as $key => $val) {
        //    $this->providers[$val] = new $key($this);
        //}
                
        //$this->providers[hellobase::class] =   new \Hero\Hello\Controller\hellobase($this);
        //$class_methods = get_class_methods('Hero\Hello\Controller\hellobase');
        //var_dump($class_methods);
      // $this->register(hellobase::class,new \Hero\Hello\Controller\hellobase($app));
  //  $this[hellobase::class] = new \Hero\Hello\Controller\hellobase($this);
    //$cara2 = $this[hellobase::class];
       // $this->hellobase = new \Hero\Hello\Controller\hellobase($this);

        //$this->bind(hellobase::class,new \Hero\Hello\Controller\hellobase($this));
        
    //}
}
