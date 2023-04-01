<?php 

namespace Neko\Framework\Util;
use Neko\Database\DB;

/**
 * Group utilities
 * ---------------------------------------------------------------------
 * most function here adapted from Illuminate\Support\Arr class Laravel
 *
 */
class Group 
{

    public static function getList()
    {
        $list_group = db::table('tb_user_group')->select("id","nm_group")->get();
        return $list_group;
    }

}