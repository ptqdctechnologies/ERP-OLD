<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 9/14/11
 * Time: 3:04 PM
 * To change this template use File | Settings | File Templates.
 */

class Extjs4_Models_TempGantt extends Zend_Db_Table_Abstract
{
    protected $_name = 'temp_gantt';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function save($data,$cacheID)
    {
        $cek = $this->fetchRow("cache_id = '$cacheID'");
        if (is_array($data))
        {
            $data = Zend_Json::encode($data);
        }
        if ($cek)
        {
            $update = array(
                "data" => $data,
                "tgl" => date("Y-m-d H:i:s")
            );
            $this->update($update,"cache_id = '$cacheID'");
        }
        else
        {
            $insert = array(
                "data" => $data,
                "cache_id" => $cacheID,
                "tgl" => date("Y-m-d H:i:s")
            );
            $this->insert($insert);
        }
    }

    public function load($cacheID)
    {
        $cek = $this->fetchRow("cache_id = '$cacheID'");
        if ($cek)
        {
            $cek = $cek->toArray();
            $cek = $cek['data'];
            if ($cek != '')
            {
                $tmp = Zend_Json::decode($cek);
                if ($tmp != '')
                    $cek = $tmp;
            }
            return $cek;
        }
        else
        {
            return false;
        }
    }

    public function test($cacheID)
    {
        $cek = $this->fetchRow("cache_id = '$cacheID'");
        if ($cek)
            return true;
        else
            return false;
    }
}
