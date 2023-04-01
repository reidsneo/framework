<?php 

namespace Neko\Framework;
use Neko\Framework\Util\File;
use Neko\Framework\Util\Str;
use Neko\Framework\Util\Arr;
use RuntimeException;

class Language {

    /**
     * Berisi key dari baris bahasa yang sedang diambil.
     *
     * @var string
     */
    protected $key;

    /**
     * Berisi pengganti yang untuk baris bahasa saat ini.
     *
     * @var array
     */
    protected $replacements;

    /**
     * Dari bahasa apa barisnya harus diambil?
     *
     * @var string
     */
    protected $language;

    /**
     * Berisi seluruh baris bahasa yang telah dimuat.
     * Key array-nya mengikuti pola [$package][$language][$file].
     *
     * @var array
     */
    protected static $lines = [];

    /**
     * Buat instance kelas Lang baru.
     *
     * @param string $key
     * @param array  $replacements
     * @param string $language
     */
    protected function __construct($key, array $replacements = [], $language = null)
    {
        $this->key = $key;
        $this->language = $language;
        $this->replacements = $replacements;
    }

    /**
     * Buat instance language line baru.
     *
     * <code>
     *
     *      // Buat sebuah instance language line baru untuk baris yang diberikan
     *      $line = Lang::line('validation.required');
     *
     *      // Buat sebuah instance language line baru untuk baris yang diberikan (milik paket)
     *      $line = Lang::line('admin::messages.welcome');
     *
     *      // Ganti atribut milik language line yang diberikan
     *      $line = Lang::line('validation.required', ['attribute' => 'email']);
     *
     * </code>
     *
     * @param string $key
     * @param array  $replacements
     * @param string $language
     *
     * @return Lang
     */
    public static function line($key, array $replacements = [], $language = null)
    {
        global $app;
        $language = is_null($language) ? $app->config['app.language'] : $language;
        return new static($key, $replacements, $language);
    }

    /**
     * Cek apakah language line ada atau tidak.
     *
     * @param string $key
     * @param string $language
     *
     * @return bool
     */
    public static function has($key, $language = null)
    {
        return static::line($key, [], $language)->get() !== $key;
    }

    /**
     * Ambil language line sebagai string.
     *
     * <code>
     *
     *      // Ambil language line
     *      $line = Lang::line('validation.required')->get();
     *
     *      // Ambil language line milik bahasa tertentu
     *      $line = Lang::line('validation.required')->get('en'); // en = english
     *
     *      // Return default value jika language line tidak ketemu
     *      $line = Lang::line('validation.required')->get(null, 'Default');
     *
     * </code>
     *
     * @param string $language
     * @param string $default
     *
     * @return string
     */
    public function get($language = null, $default = null)
    {
        $default = is_null($default) ? $this->key : $default;
        $language = is_null($language) ? $this->language : $language;

        list($package, $file, $line) = $this->parse($this->key);

        if (! static::load($package, $language, $file)) {
            return $default;
        }

        $lines = static::$lines[$package][$language][$file];
        $line = Arr::get($lines, $line, $default);

        if (is_string($line)) {
            foreach ($this->replacements as $key => $value) {
                $line = str_replace(':'.$key, $value, $line);
            }
        }

        return $line;
    }

    /**
     * Parse language key menjadi segmen paket, file dan linenya
     * Pemanggilan language line mengikuti konvensi berikut:
     * [nama_paket]::[nama_file].[language_linenya].
     *
     * @param string $key
     *
     * @return array
     */
    protected function parse($key)
    {
        global $app;
        $is_specific = Str::contains($key, '::');
        if($is_specific) {
            $package = explode('::',$key)[0];
            $segments = explode('.', explode('::',$key)[1]);
            if (count($segments) >= 2) {
                $line = implode('.', array_slice($segments, 1));
                return [$package, $segments[0], $line];
            }
            return [$package, $segments[0], null];

        } else {
            $segments = explode('.', $key);
            if(isset($app->language_map[$segments[0]]))
            {
                $package = $app->language_map[$segments[0]][$this->language];
                if (count($segments) >= 2) {
                    $line = implode('.', array_slice($segments, 1));
                    return [$package, $segments[0], $line];
                }
                return [$package, $segments[0], null];
            }
            
        }
    }

    /**
     * Muat seluruh language line dari sebuah file.
     *
     * @param string $package
     * @param string $language
     * @param string $file
     *
     * @return bool
     */
    public static function load($package, $language, $file)
    {
        if (isset(static::$lines[$package][$language][$file])) {
            return true;
        }

        $lines = self::file($package,$language,$file);
        static::$lines[$package][$language][$file] = $lines;
        return count($lines) > 0;
    }

    /**
     * Muat array language dari sebuah file.
     *
     * @param string $package
     * @param string $language
     * @param string $file
     *
     * @return array
     */
    public static function file($package, $language, $file)
    {
        $file = static::path($package, $language, $file);
        return is_file($file) ? (require $file) : [];
    }

    /**
     * Get the path to a package's language file.
     *
     * @param string $package
     * @param string $language
     * @param string $file
     *
     * @return string
     */
    protected static function path($package, $language, $file)
    {
        global $app;
        if($package == "app")
        {
            $path = $app->path.DS.'app'.DS.'language'.DS.$language.DS.$file.'.php';
        }else{
            $path = Plugin::path($package).DS.'language'.DS.$language.DS.$file.'.php';
        }
        return $path;
    }

    /**
     * Ambil konten (string) language line.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->get();
    }
}
