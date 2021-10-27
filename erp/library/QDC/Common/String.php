<?php

class QDC_Common_String {

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

    public function wordWrap($str='',$len=20)
    {
        return nl2br(wordwrap($str, $len, "\n", true));
    }
}

?>