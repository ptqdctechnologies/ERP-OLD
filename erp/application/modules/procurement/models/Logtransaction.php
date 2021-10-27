<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/12/11
 * Time: 9:55 AM
 * To change this template use File | Settings | File Templates.
 */

class Procurement_Model_Logtransaction extends Zend_Db_Table_Abstract
{
    protected $_name = 'log_transaction';
    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function ViewLogTransaction ($trano)
    {
        $query = "select trano,tgl,uid,action,data_before,data_after from log_transaction where trano = '$trano' order by tgl desc";
        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll();


        $return = array();
        foreach($data as $k => $v)
        {
            $json = $v['data_before'];
            $json2 = $v['data_after'];
            $uid = $v['uid'];
            $tgl = $v['tgl'];
            $action = $v['action'];
            $total = 0;
            $total2 = 0;

            $data1 = Zend_Json::decode($json);
            foreach($data1['arf-detail-before'] as $k2 => $v2)
            {
                $total += floatval($v2['qty'] * $v2['harga']);
            }

            $data2 = Zend_Json::decode($json2);
            foreach($data2['arf-detail-after'] as $k2 => $v2)
            {
                $total2 += floatval($v2['qty'] * $v2['harga']);
            }

            $return[] = array(
                "trano" => $v['trano'],
                "uid" => $uid,
                "tgl" => $tgl,
                "action" => $action,
                "totalbefore" => $total,
                "totalafter" => $total2
            );
        }
        
        return $return;
    }

    public function ViewLogProductListBefore ($trano,$tgl)
    {
        $query = "select data_before from log_transaction where trano = '$trano' and tgl = '$tgl' ";
        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll ();



        foreach($data as $key => $val )
        {
            $json = $val['data_before'];
            $data2 = Zend_Json::decode($json);

            foreach($data2['arf-detail-before'] as $key2 => $val2)
            {
                $return[] = $val2;
            }

        }

        return $return;
    }

    public function ViewLogProductListAfter ($trano,$tgl)
    {
        $query = "select data_after from log_transaction where trano = '$trano' and tgl = '$tgl' ";
        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll ();



        foreach ($data as $key => $val)
        {
            $json = $val['data_after'];
            $data2 = Zend_Json::decode($json);

            foreach ($data2['arf-detail-after'] as $key2 => $val2 )
            {
                $return[] = $val2;
            }

        }

        return $return;
    }

}
