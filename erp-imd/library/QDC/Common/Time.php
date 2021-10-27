<?php

class QDC_Common_Time{

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

    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public function stopwatch()
    {
        return $this->microtime_float();
    }
}

?>