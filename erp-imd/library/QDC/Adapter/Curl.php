<?php

class QDC_Adapter_Curl {

    private $adapter;
    private $client;
    public function __construct($params = '')
    {
        $this->adapter = new Zend_Http_Client_Adapter_Curl();
        $this->client = new Zend_Http_Client();

        if ($params != '')
        {
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }
    }

    public static function factory($params=array())
    {
        return new self($params);
    }

    public function forkProcess($urlFork='')
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $this->url);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);  // Follow the redirects (needed for mod_rewrite)
        curl_setopt($c, CURLOPT_HEADER, false);         // Don't retrieve headers
        curl_setopt($c, CURLOPT_NOBODY, true);          // Don't retrieve the body
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);  // Return from curl_exec rather than echoing
        curl_setopt($c, CURLOPT_FRESH_CONNECT, true);   // Always ensure the connection is fresh
//        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

        if ($this->params)
        {
            curl_setopt ($c, CURLOPT_POST, true);
            curl_setopt ($c, CURLOPT_POSTFIELDS, $this->params);
        }

        // Timeout super fast once connected, so it goes into async.
        curl_setopt( $c, CURLOPT_TIMEOUT, 1 );

        curl_exec( $c );
    }
}

?>