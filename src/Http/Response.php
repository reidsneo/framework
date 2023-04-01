<?php 

namespace Neko\Framework\Http;

use Neko\Framework\App;
use Neko\Framework\Bag;
use Neko\Framework\MacroableTrait;
use Neko\Framework\Router\Route;
use Neko\Framework\Util\Arr;

class Response {

    use MacroableTrait;

    const CONTENT_TYPE_HTML = "text/html";
    const CONTENT_TYPE_JSON = "application/json";
    
    protected $http_status_messages = array(
        //Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',

        //Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',

        //Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',

        //Client Error 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        418 => '418 I\'m a teapot',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',

        //Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported'
    );

    public $app;
    public $body = "";

    public $dump_output = "";

    protected $has_sent = false;
    protected $status = "";
    protected $headers = "";

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->headers = new ResponseHeaderBag;
        $this->reset();
    }

    /**
     * set http status code
     * 
     * @param int $status http status to set
     */
    public function setStatus($status)
    {
        $status = (int) $status;
        if(!array_key_exists($status, $this->http_status_messages)) return $this;

        $this->status = $status;
        return $this;
    }

    /**
     * get response status code
     *
     * @return int
     */
    public function getStatus()
    {
        return (int) $this->status;
    }

    /**
     * Check response status code
     *
     * @param string|int status code, can be 4xx, 2xx, 40x, 20x, etc
     * @return boolean
     */
    public function isStatus($status_code)
    {
        $status = (string) $this->getStatus();
        $regex = '/'.str_replace('x', '[0-9]', $status_code).'/'; // it should be /4[0-9][0-9]/

        return (preg_match($regex, $status) != 0);
    }

    /**
     * set response content type
     * 
     * @param string $type response content type
     */
    public function setContentType($type)
    {
       $this->headers["CONTENT_TYPE"] = $type;
        return $this;
    }

    public function setHeader($key,$value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function setHeaders($headers)
    {
        foreach ($headers as $k => $v) {
            header($k.$v);
        }
    }

    /**
     * get setted response content type
     *
     * @return string response content type
     */
    public function getContentType()
    {
        return $this->headers["CONTENT_TYPE"];
    }

    public function json(array $data, $status = null, $content_type = null)
    {
        $json = json_encode($data);
        $this->setContentType($content_type ? $content_type : static::CONTENT_TYPE_JSON);
        $this->setStatus($status);
        $this->body = $json;
        app()->hook->apply('debug_json',[$json]);
        return $this;
    }

    public function html($content, $status = null, $content_type = null)
    {
        $this->setContentType($content_type ? $content_type : static::CONTENT_TYPE_HTML);
        $this->setStatus($status);
        $this->body = $content;

        return $this;
    }

    public function isJson()
    {
        return ($this->getContentType() == static::CONTENT_TYPE_JSON);
    }

    public function isHtml()
    {
        return ($this->getContentType() == static::CONTENT_TYPE_HTML);
    }

    public function reset()
    {
        $this->has_sent = false;

        return $this
            ->setStatus(200)
            ->clean();
    }

    public function clean()
    {
        $this->body = "";
        $this->dump_output = "";
        return $this;
    }

    public function send($output = null, $status = null)
    {
        if($this->has_sent) return;

        if($output) {
            $this->body .= $output;
        }

        if($status) {
            $this->setStatus($status);
        }

        $status_str = (string) $this->status;

        $this->app->hook->apply($status_str[0].'xx', [$this, $this->app]);
        $this->app->hook->apply($status_str[0].$status_str[1].'x', [$this, $this->app]);
        $this->app->hook->apply($this->status, [$this, $this->app]);
        $this->app->hook->apply("response.before_send", [$this, $this->app]);

        if(!headers_sent()) $this->writeHeaders();
        
        if('HEAD' != $this->app->request->server['REQUEST_METHOD']) {
            echo $this->body;
        } 

        $this->has_sent = true;
        $this->app->hook->apply("response.after_send", [$this, $this->app]);
    }

    public function getStatusMessage($status_code)
    {
        if(array_key_exists($status_code, $this->http_status_messages)) {
            return $this->http_status_messages[$status_code];
        } else {
            return null;
        }
    }

    protected function writeHeaders()
    {
        $headers = $this->headers->all(false);
        $content_type = ($type = $this->getContentType()) ? $type : static::CONTENT_TYPE_HTML;

        // http://stackoverflow.com/questions/6163970/set-response-status-code
        header("HTTP/1.1 ".$this->getStatusMessage($this->status), true, $this->status);
        header('Content-type: '.$content_type);
        foreach($headers as $key => $value) {
            $header = $this->normalizeHeaderKey($key).': '.$value;
            header($header);
        }
    }

    // http://en.wikipedia.org/wiki/List_of_HTTP_header_fields#Response_fields
    protected function normalizeHeaderKey($key)
    {
        return $this->headers->unresolveKey($key);
    }


}