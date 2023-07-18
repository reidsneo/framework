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
        
    }

    public function register()
	{

    }
    
	public function boot()
	{
        
    }

    public function pluginProviders()
    {
        return [];
    }
    
}
