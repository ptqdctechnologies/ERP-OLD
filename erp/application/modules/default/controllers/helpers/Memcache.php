<?php
class Zend_Controller_Action_Helper_Memcache extends
                Zend_Controller_Action_Helper_Abstract
{
    private $memcache;
    private $today;
    private $expire;

    function  __construct() {
        $this->memcache = Zend_Registry::get('Memcache');

        $this->today = new DateTime(date("Y-m-d H:i:s"));
        $this->expire = new DateTime(date("Y-m-d H:i:s"));
        $this->expire->add(new DateInterval("PT30M"));
    }

    public function save($data,$id='',$tags='')
    {
        if ($data == '')
            return '';

        if ($id == '')
            $id = md5(date('Y-m-d H:i:s') . rand(1,100000));

        $cacheTimeID = $id . "_TIME";

        $this->memcache->save($data,$id,array($tags));
        //cache time generated...
        $time = array(
            "generate" => $this->today->format("d M Y H:i:s"),
            "expire" => $this->expire->format("d M Y H:i:s")
        );
        $this->memcache->save($time,$cacheTimeID,array($tags));

        return array(
            "id" => $id,
            "tags" => $tags,
            "expire" =>  $this->expire->format("d M Y H:i:s"),
            "generate" => $this->today->format("d M Y H:i:s"),
            "data" => $data
        );
    }
}
?>