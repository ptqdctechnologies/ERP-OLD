<?php

class QDC_Document_Render
{
    public $urlForm;
    public function __construct($params = '')
    {
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

    public function getHTML()
    {
        $url = QDC_Document_Model::factory(array(
            "trano" => $this->trano,
            "item_type" => $this->item_type,
            "useHash" => true
        ))->getApprovalForm();

        $html = file_get_contents($url);

        return $html;
    }
}

?>