<?php

class QDC_Common_Url
{
    public $url;
    public function __construct($params = '')
    {
        if ($params != '')
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
    }

    public static function factory($params='')
    {
        return new self($params);
    }

    public function hash()
    {
        return $this->base64url_encode($this->url);
    }

    public function unhash()
    {
        return $this->base64url_decode($this->url);
    }

    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function getContent()
    {
        return file_get_contents('http://' . $_SERVER['SERVER_NAME'] . $this->url);
    }
}

?>
