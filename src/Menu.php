<?php

namespace Neko\Framework;
use Neko\Framework\Util\Arr;
class Menu
{
    public function admin_breadcrumb()
    {
        return Arr::parentvalr(app()->request->path(), app()->menu_admin_raw,'title');
    }

    public function user_breadcrumb()
    {
        return Arr::user_breadcrumb(app()->request->path(), app()->menu_user_raw,'title');
    }

    public function getTitle($name,$showsub=false)
    {
        $title = Arr::parentvalc($name, app()->menu_admin_raw,'title',$showsub);
        if($title==null)
        {
            return $name;
        }else{
            return $title;
        }
    }

    public function getMenuAdmin()
    {
        return app()->menu_admin;
    }
    
    public function getList()
    {
        return app()->menulist;
    }

}
