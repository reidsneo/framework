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
    

    function menuRecursive($menu,$key,$array,$params,$admin=0)
    {
        global $app;
        if( is_array($array) && count($array)>0 && !array_key_exists('title',$array) && $key!="icon"  || is_array($array) && count($array)>0 && array_key_exists('0',$array) )  //&& !array_key_exists('title',$array) && $key!="icon") ||    //array_key_exists('0',$array) 
        {
            $subitem = $array;
            unset($subitem['icon']);
            if($admin==1)
            {
                $icon = "";
                if($array['icon']!="")
                {
                    $icon = '<i class="'.$array['icon'].'"></i>';
                }
                $app->menu_admin = $menu
                    ->submenuif(
                        $app->request->countsub($subitem),
                        \Neko\Menu\Link::to('#'.str_replace(" ","",strtolower($key)), $icon.' <span>'.$key.'</span>')
                        ->addClass('nav-link menu-link')
                        ->setAttributes(['data-bs-toggle' => 'collapse', 'role' => 'button', 'aria-controls' => str_replace(" ","",strtolower($key)), 'aria-expanded' => 'false'])
                        ,
                        function (\Neko\Menu\Menu $menu) use ($app,$array,$key,$subitem,$params) {
                            $menu
                            ->addItemParentClass('nav nav-sm flex-column')
                            ->setParentTag('ul')
                            ->addClass('collapse')
                            ->addClass('menu-dropdown')
                            ->setAttribute('id',str_replace(" ","",strtolower($key)))
                            ->setWrapperTag("div");
                            
                            foreach ($array as $k => $v) {
                                if(is_array($v) && array_key_exists('title',$v))
                                {
                                    $icon = "";
                                    if($v['icon']!="")
                                    {
                                        $icon = '<i class="'.$v['icon'].'"></i>';
                                    }
                                $menu
                                    ->addIf($app->request->can("acc",$v['url']),\Neko\Menu\Link::to($v['url'], $icon.' <span>'.$v['title'].'</span>')
                                    ->addClass('nav-link menu-link'));
                                }else{
                                    $this->menuRecursive($menu,$k,$v,$params,1);
                                }
                            }
                        }
                    );
            }else{
                $sub_url = "#";
                if($params['mode']=="mobile")
                {
                    $sub_url = "#";
                }else{
                    $sub_url = $subitem['url'];
                }
                $app->menu_user = $menu
                ->submenuif($app->request->countsub($subitem),\Neko\Menu\Link::to($sub_url, sprintf($params['submenu']['link'],$key))
                    ->setAttribute('role', 'menuitem'), function (\Neko\Menu\Menu $menu) use ($app,$array,$key,$subitem,$params) {
                        //$app->debug["messages"]->addMessage($key ." ".$app->request->countcan("acc",$subitem));
                        $menu
                            ->addParentClass($params['submenu']['parent_class'])
                            ->addClass($params['submenu']['class'])
                            ->setActiveClass($params['submenu']['active_class']);

                        foreach ($array as $k => $v) {
                            if(is_array($v) && array_key_exists('title',$v))
                            {
                                $can = false;
                                if(array_key_exists('type',$v) && $v['type']=="noauth")
                                {
                                    $can = true;
                                }else{
                                    $can = $app->request->can("acc",$v['url']);
                                }
                            $menu
                                ->addIf($can,\Neko\Menu\Link::to($v['url'],  sprintf($params['submenu']['link'],$v['title']))
                                ->setAttribute('role', $params['submenu']['role']))
                                ->setAttribute('style', $params['submenu']['style']);
                                //->addClass('nav-link'));
                                ////$app->debug["messages"]->addMessage($v['url']."  ".$app->request->can("acc",$v['url']));
                            }else{
                                $this->menuRecursive($menu,$k,$v,$params);
                            }
                        }
                    }
                );
            }
            
        }
    }

    public function getMenuAdmin()
    {
        $menu_admin = \Neko\Menu\Menu::new()->addClass('navbar-nav')->setAttribute('id', 'navbar-nav');

        foreach (app()->menu_admin_raw as $key => $val) {
            if(array_key_exists('title',$val))
            {
                $menu_admin = $menu_admin
                ->addItemParentClass("nav-item")
                ->addIf(app()->request->can("acc",$val['url']),\Neko\Menu\Link::to($val['url'], '<i class="'.$val['icon'].'"></i>'.' <span>'.$val['title'].'</span>'));
                
                if(array_key_exists('type',$val))
                {
                    if($val['type']=="noauth")
                    {
                        $menu_admin = $menu_admin->add(\Neko\Menu\Link::to($val['url'], '<i class="'.$val['icon'].'"></i>'.' <span>'.$val['title'].'</span>')
                        ->addClass('nav-link menu-link'));
                    }
                }
            }else{
                $this->menuRecursive($menu_admin,$key,$val,null,1);
            }
        }
        
        $menu_admin->setActiveClass("active")->setActive(function (\Neko\Menu\Link $link) {
            return $link->getUrl() === app()->request->path();
        });

        $menu_admin->each(function(\Neko\Menu\Menu $menu){
            if($menu->isActive())
            {
                try {
                    $menu->each(function($menu){
                        if($menu->isActive())
                        {
                            $menu->setActiveClassOnLink(true)->addClass("show");
                        }
                    });
                } catch (\Throwable $th) {
                    
                }
                
                try {
                    $menu->setActiveClassOnLink(true)->addClass("show");
                } catch (\Throwable $th) {

                }
            }
        });
        
        return $menu_admin;
    }
    
    public function getList()
    {
        return app()->menulist;
    }

}
