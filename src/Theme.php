<?php 

namespace Neko\Framework;
use Neko\Framework\Util\File;
use Neko\Framework\Util\Str;
use RuntimeException;
class Theme {
    public function asset_a($assetpath)
    {
        if(Str::contains(app()->request->path(),"/thememanager/preview"))
        {
            return "/theme/a_".app()->request->segment(4)."/asset/".$assetpath;
        }else{
            return "/theme/a_".app()->config['admin_theme']."/asset/".$assetpath;
        }
    }

    public function asset($assetpath)
    {
        if(Str::contains(app()->request->path(),"/thememanager/preview"))
        {
            return "/theme/".app()->request->segment(4)."/asset/".$assetpath;
        }else{
            return "/theme/".app()->config['user_theme']."/asset/".$assetpath;
        } 
    }

    public function image_preview($theme,$type)
    {
        $path = "";
        if($type == "admin")
        {
            $path = "/theme/a_".$theme;
        }else{
            $path = "/theme/".$theme;
        }
        return $path."/preview.jpg";
    }

    public static function isadmin($name=false)
    {
        $theme_isadmin = false;
        if($name==false)
        {
            $name = app()->request->segment(4);
        }
        if(File::isExist(app()->path."/app/themes/a_".$name))
        {
            $theme_isadmin = true;
        }else if(File::isExist(app()->path."/app/themes/".$name)){
            $theme_isadmin = false;
        }else{
            throw new RuntimeException('Not valid theme was given: '.$name);
        }
        return $theme_isadmin;

    }
}
