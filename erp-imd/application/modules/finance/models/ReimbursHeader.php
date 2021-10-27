<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/5/11
 * Time: 8:48 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_ReimbursHeader extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_reimbursementh';

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

    public function viewheaderreimburs ($offset=0,$limit=0,$dir='ASC',$sort='trano',$search)
    {

        if ($search != "")
            $where = "WHERE $search";

        $query ="select SQL_CALC_FOUND_ROWS trano,prj_kode,prj_nama,sit_kode,sit_nama
                from procurement_reimbursementh $where order by $sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query($query);
        $datadetail['data'] = $fetch->fetchAll();
        $datadetail['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        
        return $datadetail;
    }

    public function ViewFormPayReimburs ($trano)
    {
        $query = "select RH.trano,RH.tgl,RH.user,RH.prj_kode,RH.prj_nama,RH.sit_kode,RH.sit_nama,workid,workname,RH.cus_kode,cus_nama,RH.ket,RH.total,RH.val_kode
                from (procurement_reimbursementh RH left join procurement_reimbursementd RD on RH.trano = RD.trano)
                left join master_customer MC on RH.cus_kode = MC.cus_kode where RH.trano = '$trano'";
        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll();
        return $data;
    }




}
 
