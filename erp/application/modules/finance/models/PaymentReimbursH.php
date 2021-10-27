<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/14/11
 * Time: 4:54 PM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_PaymentReimbursH extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_payment_reimbursement';

    protected $_primary = 'trano';
    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function viewpaidlist ($trano)
    {
        $query = "select trano,tgl,user,statusppn,val_kode,total from finance_payment_reimbursement where rem_no = '$trano'";
        $fetch = $this->db->query ($query);
        $data = $fetch->fetchAll();

        return $data;
    }

    public function PaidListSum ($trano,$payment_trano)
    {
        $query = "select sum(total) as paidsum from finance_payment_reimbursement where rem_no = '$trano' AND trano != '$payment_trano'";
        $fetch = $this->db->query ($query);
        $data = $fetch->fetchAll ();

        return $data;
    }

    public function viewalltranspayment ($offset=0,$limit=0,$dir='ASC',$sort='trano',$search)
    {

        if ($search != "")
            $where = "WHERE $search";

        $query = "SELECT SQL_CALC_FOUND_ROWS trano,tgl,rem_no,prj_kode,sit_kode,prj_nama,sit_nama,total FROM finance_payment_reimbursement $where
                    ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query ($query);
        $data['data'] = $fetch->fetchAll ();
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

        return $data;
    }

    public function __name()
    {
        return $this->_name;
    }

}
