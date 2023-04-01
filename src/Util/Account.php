<?php 

namespace Neko\Framework\Util;
use Neko\Facade\Session;

/**
 * Array utilities
 * ---------------------------------------------------------------------
 * most function here adapted from Illuminate\Support\Arr class Laravel
 *
 */
class Account 
{
    public static function getId()
    {
        if(Session::get("user")!==null)
        {
            return Session::get("user")['account']['id'];
        }else{
            return false;
        }
    }

    public static function getUser()
    {
        return Session::get("user")['account']['username'];
    }

    public static function getEmail()
    {
        return Session::get("user")['account']['email'];
    }

    public static function getRealName()
    {
        return Session::get("user")['account']['realname'];
    }

    public static function getRole()
    {
        return Session::get("user")['role'][0];
    }

    public static function getPhone()
    {
        return Session::get("user")['account']['phone'];
    }

    public static function canEdit()
    {
       return app()->request->can('edt',app()->request->path());
    }
}