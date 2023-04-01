<?php 

namespace Neko\Framework\Util;

/**
 * Array utilities
 * ---------------------------------------------------------------------
 * most function here adapted from Illuminate\Support\Arr class Laravel
 *
 */
class Arr 
{

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public static function add($array, $key, $value)
    {
        if (is_null(static::get($array, $key)))
        {
            static::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * Build a new array using a callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function build($array, callable $callback)
    {
        $results = [];

        foreach ($array as $key => $value)
        {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

            $results[$innerKey] = $innerValue;
        }

        return $results;
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @param  array  $array
     * @return array
     */
    public static function divide($array)
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  array   $array
     * @param  string  $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $results = array_merge($results, static::dot($value, $prepend.$key.'.'));
            }
            else
            {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get all of the given array except for a specified array of items.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function except($array, $keys)
    {
        return array_diff_key($array, array_flip((array) $keys));
    }

    /**
     * Fetch a flattened array of a nested array element.
     *
     * @param  array   $array
     * @param  string  $key
     * @return array
     */
    public static function fetch($array, $key)
    {
        foreach (explode('.', $key) as $segment)
        {
            $results = [];

            foreach ($array as $value)
            {
                if (array_key_exists($segment, $value = (array) $value))
                {
                    $results[] = $value[$segment];
                }
            }

            $array = array_values($results);
        }

        return array_values($results);
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function first($array, callable $callback, $default = null)
    {
        foreach ($array as $key => $value)
        {
            if (call_user_func($callback, $key, $value)) return $value;
        }

        return $default;
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function last($array, callable $callback, $default = null)
    {
        return static::first(array_reverse($array), $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  array  $array
     * @return array
     */
    public static function flatten($array)
    {
        $return = [];

        array_walk_recursive($array, function($x) use (&$return) { $return[] = $x; });

        return $return;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original =& $array;

        foreach ((array) $keys as $key)
        {
            $parts = explode('.', $key);

            while (count($parts) > 1)
            {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part]))
                {
                    $array =& $array[$part];
                }
            }

            unset($array[array_shift($parts)]);

            // clean up after each pass
            $array =& $original;
        }
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (is_null($key)) return $array;

        if (isset($array[$key])) return $array[$key];

        foreach (explode('.', $key) as $segment)
        {
            if ( ! is_array($array) || ! array_key_exists($segment, $array))
            {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Check if an item exists in an array using "dot" notation.
     *
     * @param  array   $array
     * @param  string  $key
     * @return bool
     */
    public static function has($array, $key)
    {
        if (empty($array) || is_null($key)) return false;

        if (array_key_exists($key, $array)) return true;

        foreach (explode('.', $key) as $segment)
        {
            if ( ! is_array($array) || ! array_key_exists($segment, $array))
            {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }

    public static function searchkeyr($search_value, $array, $id_path) { 
      
        if(is_array($array) && count($array) > 0) { 
              
            foreach($array as $key => $value) {
                $temp_path = $id_path;
                array_push($temp_path, $key);
                if(is_array($value) && count($value) > 0) { 
                    $res_path = self::searchkeyr($search_value, $value, $temp_path);
                    if ($res_path != null) { 
                        return $res_path; 
                    } 
                }else if($value == $search_value) { 
                    return $temp_path; 
                } 
            } 
        }      
        return null; 
    }

    public static function user_breadcrumb($search_value, $arraysource,$index) {
        $segments = explode("/",$search_value);
        $segments = array_filter($segments);
        return $segments;
    }
    
    public static function parentvalr($search_value, $arraysource,$index) {
        $parent = self::searchkeyr($search_value,$arraysource,array());
        if($parent == null)
        {
            $parent = "";
        }
        $search = str_replace("url",$index,$parent);
        if(is_string($search) && $search == "")
        {
            $current = Str::title(basename($search_value));
            $path = [];
        }else{
            $current = array_reduce($search, function($a, $b) {
                return $a[$b];
            }, $arraysource);
            
            $path = array_values(array_filter($parent, function($v) {
                return strlen($v) > 3;
            }));
        }

        $breadcrumb = array_merge($path,array($current));
        return $breadcrumb;
    }

    public static function parentvalc($routename, $arraysource,$index,$showsub=false) {
        global $app;
        $parent = self::searchkeyr($app->router->findRouteByName($routename)->getPath(),$arraysource,array());
        if($parent == null)
        {
            $parent = "";
        }
        $search = str_replace("url",$index,$parent);
        if($search == "")
        {
            $search = [];
        }
        $current = array_reduce($search, function($a, $b) {
            return $a[$b];
        }, $arraysource);
        if($showsub==true)
        {
            $path = array_values(array_filter($parent, function($v) {
                return strlen($v) > 3;
            }));
            $current = array_merge($path,array($current));                
            $current = array_unique($current);
            $current = join(" -> ",$current);
        }
        return $current;
    }

    public static function inarray($array,$coloumn,$val)
    {
        if(is_array($array) && in_array($val, array_column($array, $coloumn))) {
            return true;
        }else{
            return false;
        }
    }

    public static function inarraykey($array,$coloumn,$val)
    {
        if(is_array($array))
        {
            $key = array_search($val, array_column($array, $coloumn));
            return $key;
        }else{
            return false;
        }        
    }

    public static function multiKeyExists(array $arr, $key) {

        // is in base array?
        if (array_key_exists($key, $arr)) {
            return true;
        }
    
        // check arrays contained in this array
        foreach ($arr as $element) {
            if (is_array($element)) {
                if (self::multiKeyExists($element, $key)) {
                    return true;
                }
            }
    
        }
    
        return false;
    }

    public static function sortbycol(&$arr, $col, $dir = SORT_ASC,$method=null) {
        $sort_col = array();
        foreach ($arr as $key=> $row) {
            $sort_col[$key] = $row[$col];
        }
    
        array_multisort($sort_col, $dir, $arr,$method);
    }

    
    public static function sortmulti($array, $index, $order, $natsort=FALSE, $case_sensitive=FALSE) {
        $sorted = [];
        if(is_array($array) && count($array)>0) {
            foreach(array_keys($array) as $key) {
                @$temp[$key]=$array[$key][$index];
            }
            if(!$natsort) {
                if ($order=='asc') {
                    asort($temp);
                } else {    
                    arsort($temp);
                }
            }
            else 
            {
                if ($case_sensitive===true) {
                    natsort($temp);
                } else {
                    natcasesort($temp);
                }
            if($order!='asc') { 
                $temp=array_reverse($temp,TRUE);
            }
            }
            foreach(array_keys($temp) as $key) { 
                if (is_numeric($key)) {
                    $sorted[]=$array[$key];
                } else {    
                    $sorted[$key]=$array[$key];
                }
            }
            return $sorted;
        }
        return $sorted;
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param  array   $array
     * @param  string  $value
     * @param  string  $key
     * @return array
     */
    public static function pluck($array, $value, $key = null)
    {
        $results = array();

        foreach ($array as $item) {
            $itemValue = is_object($item) ? $item->{$value} : $item[$value];
            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = is_object($item) ? $item->{$key} : $item[$key];
                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }
    /**
     * Get a value from the array, and remove it.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) return $array = $value;

        $keys = explode('.', $key);

        while (count($keys) > 1)
        {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if ( ! isset($array[$key]) || ! is_array($array[$key]))
            {
                $array[$key] = [];
            }

            $array =& $array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function where($array, callable $callback)
    {
        $filtered = [];

        foreach ($array as $key => $value)
        {
            if (call_user_func($callback, $key, $value)) $filtered[$key] = $value;
        }

        return $filtered;
    }

    public static function remove(array &$array, $key)
    {
        $keys = explode('.', $key);
        
        while(count($keys) > 1) {
            $key = array_shift($keys);

            if(!isset($array[$key]) OR !is_array($array[$key])) {
                $array[$key] = array();
            }

            $array =& $array[$key];
        }

        unset($array[array_shift($keys)]);
    }
    
    public static function query($array)
    {
        return http_build_query($array, null, '&', PHP_QUERY_RFC3986);
    }


    public static function replaceNumericKeys(array $input) {
        $return = array();
        foreach ($input as $key => $value) {
            if (!is_numeric($key)) {
                $key = preg_replace("/[^a-zA-Z]+/", "", $key);
            }          
    
            if (is_array($value))
                $value = self::replaceNumericKeys($value); 
    
            $return[$key] = $value;
        }
        return $return;
    }

}
