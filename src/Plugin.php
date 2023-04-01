<?php 

namespace Neko\Framework;
use Neko\Framework\Util\File;
use Neko\Framework\Util\Str;
use RuntimeException;
class Plugin {

    public static function path($package)
    {
        if($package !== null)
        {
            return app_path().DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.str_replace(".", DIRECTORY_SEPARATOR,$package);
        }
    }
}
