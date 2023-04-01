<?php 

namespace Neko\Framework\Util;

class File {


    public $name;

    public $extension;

    protected $tmp;

    protected $size;

    protected $error;

    protected $mimeType;

    static function getContent($file)
    {
        ob_end_clean();
        $data=(string) file_get_contents($file);
        return $data;
    }

    static function isExist($file)
    {
        if(file_exists($file))
        {
            return true;
        }else{
            return false;
        }
    }    

    static function getMimeType($file)
    {
        $mime_types = array(

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            
        );

        //$ext = strtolower(array_pop(explode('.',$file)));
        $ext = substr(substr($file, strrpos($file, '.', -1), strlen($file)),1);
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }
        elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $file);
            finfo_close($finfo);
            return $mimetype;
        }
        else {
            return 'application/octet-stream';
        }
    }


    static function preview($file)
    {
        global $app;
        $lifetime = 31556926; // One year in seconds
        $file_time = filemtime($file); // Get the last modified time for the file (Unix timestamp)
        $header_content_type = self::getMimeType($file);
        $header_content_length = filesize($file);
        $header_etag = md5($file_time . $file);
        $header_last_modified = gmdate('r', $file_time);
        $header_expires = gmdate('r', $file_time + $lifetime);
        $h1 = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $header_last_modified;
        $h2 = isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $header_etag;
        if ($h1 || $h2) {
            //fixme $app->response->setHeader('Content-Disposition','inline; filename="' . $file . '"');
            $app->response->setHeader('Last-Modified',$header_last_modified);
            $app->response->setHeader('Cache-Control','must-revalidate');
            $app->response->setHeader('Expires',$header_expires);
            $app->response->setHeader('Pragma','public');
            $app->response->setHeader('Etag',$header_etag);
            return $app->response->setStatus('304');
        }

       ////fixme $app->response->setHeader('Content-Disposition','inline; filename="' . $file . '"');
        $app->response->setHeader('Last-Modified',$header_last_modified);
        $app->response->setHeader('Cache-Control','must-revalidate');
        $app->response->setHeader('Expires',$header_expires);
        $app->response->setHeader('Pragma','public');
        $app->response->setHeader('Etag',$header_etag);
        $app->response->setHeader('Content-Type',$header_content_type);
        $app->response->setHeader('Content-Length',$header_content_length);
        return self::getContent($file);
    }

    static function findFiles($directory, $extensions = "*") {
        $directories = [];
        foreach(glob($directory."/".$extensions) as $folder) {
            $directories[] = $folder;
        }
        return $directories;
    }

    static function deleteDir($target) {
        if(is_dir($target)){
            $files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned
    
            foreach( $files as $file ){
                self::deleteDir($file);      
            }
    
            rmdir( $target );
        } elseif(is_file($target)) {
            unlink( $target );  
        }
    }

    public static function isfile($path)
    {
        return is_file($path);
    }

    public static function isdir($path)
    {
        return is_dir($path);
    }

    public static function get($path, $default = null)
    {
        return file_get_contents($path);
    }

    public static function put($path, $data, $options = LOCK_EX)
    {
        $put = file_put_contents($path, $data, $options);
        @chmod($path, 0664);
        static::protect($path);

        return $put;
    }

    public static function prepend($path, $data)
    {
        return static::put($path, $data.static::get($path));
    }

    public static function append($path, $data)
    {
        return static::put($path, $data, LOCK_EX | FILE_APPEND);
    }

    public static function delete($path)
    {
        if (! @unlink($path)) {
            return false;
        }

        return true;
    }

    public static function rmdir($directory, $preserve = false)
    {
        if (! static::isdir($directory)) {
            return false;
        }

        $items = new \FilesystemIterator($directory);

        foreach ($items as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                static::rmdir($item->getPathname());
            } else {
                static::delete($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }

        return true;
    }

    public static function cleandir($directory)
    {
        return static::rmdir($directory, true);
    }

    public static function move($path, $target)
    {
        $move = rename($path, $target);
        static::protect($path);

        return $move;
    }

    public static function mvdir($from, $to, $overwrite = false)
    {
        if ($overwrite && static::isdir($to) && ! static::rmdir($to)) {
            return false;
        }

        $rename = @rename($from, $to);

        if (true === $rename) {
            static::protect($to);
            return true;
        }

        return false;
    }

    public static function copy($path, $target)
    {
        $copy = copy($path, $target);
        static::protect($target);

        return $copy;
    }

    public static function cpdir($directory, $destination, $options = \FilesystemIterator::SKIP_DOTS)
    {
        if (! static::isdir($directory)) {
            return false;
        }

        if (! static::isdir($destination)) {
            static::mkdir($destination, 0777);
        }

        $items = new \FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            $target = $destination.DS.$item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! static::cpdir($path, $target, $options)) {
                    return false;
                }
            } else {
                if (! static::copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public static function type($path)
    {
        return filetype($path);
    }

    public static function size($path)
    {
        return filesize($path);
    }

    public static function modified($path)
    {
        return filemtime($path);
    }

    public static function chmod($path, $mode = null)
    {
        return $mode ? chmod($path, $mode) : substr(sprintf('%o', fileperms($path)), -4);
    }

    public static function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    public static function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    public static function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    public static function mime($path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    public static function mkdir($path, $chmod = 0755)
    {
        try {
            mkdir($path, $chmod, true);
            static::protect($path);
            return true;
        } catch (\Throwable $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    public static function hash($path)
    {
        return md5_file($path);
    }

    public static function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }

    public static function protect($path)
    {
        if (! is_file($path) && ! is_dir($path)) {
            return;
        }

        $path = is_file($path) ? rtrim(dirname($path), DS) : $path;

        if (! is_file($file = $path.DS.'index.html')) {
            static::put($file, 'No direct script access.'.PHP_EOL);
        }
    }
    

}