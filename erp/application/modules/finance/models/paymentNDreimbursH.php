<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/10/11
 * Time: 10:45 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_paymentNDreimbursH extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_payment_reimbursement_nd';

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

    public function viewpaiddebitnote($offset=0,$limit=0,$dir='DESC',$sort='trano',$search)
    {
        if ($search != "")
            $where = "WHERE $search";

        $query = "SELECT SQL_CALC_FOUND_ROWS PDN.trano,PDN.tgl,PDN.dn_no,PDN.prj_kode,PDN.sit_kode,rem_no,deduction_list FROM (finance_payment_reimbursement_nd PDN LEFT JOIN finance_nd_reimbursement DN ON PDN.dn_no = DN.trano)
                  LEFT JOIN finance_payment_reimbursement PAY ON PAY.trano = DN.prem_no $where ORDER BY $sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query ($query);
        $data['data']= $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

        return $data;
    }

    public function __name()
    {
        return $this->_name;
    }

}