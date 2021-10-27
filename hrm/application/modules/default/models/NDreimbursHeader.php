<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/27/11
 * Time: 10:58 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_NDreimbursHeader extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_nd_reimbursement';

    protected $_primary = 'trano';
    protected $db;

    public function getPrimaryKey ()
    {
        return $this->_primary;
    }

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function viewdebitnotelist ($offset=0,$limit=0,$dir='DESC',$sort='trano',$search)
    {
        if ($search != "")
            $where = "WHERE $search";

        $query = "SELECT SQL_CALC_FOUND_ROWS DH.trano,prem_no,rem_no,DH.prj_kode,DH.sit_kode,DH.cus_kode,DH.coa_kode
                    FROM $this->_name DH LEFT JOIN finance_payment_reimbursement PH ON DH.prem_no = PH.trano $where ORDER BY $sort $dir LIMIT $offset,$limit ";
        $fetch = $this->db->query ($query);
        $data['data'] = $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

        return $data;
    }

}
 
