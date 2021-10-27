<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/1/11
 * Time: 11:05 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_MasterPeriode extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_periode';

    protected $_primary = 'perkode';
    public $db;
    public $name;

    public function getPrimaryKey ()
    {
        return $this->_primary;
    }

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->name = $this->_name;
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function viewallrequest ($offset=0,$limit=0,$dir='ASC',$sort='trano',$search)
    {

        if ($search != "")
            $where = "WHERE $search";

        $query = "SELECT SQL_CALC_FOUND_ROWS trano,riv_no,coa_kode,tgl,cus_kode,prj_kode,sit_kode,prj_nama,sit_nama,(COALESCE(total,0)) as total FROM " . $this->_name . " $where
                    ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query ($query);

        $data['data'] = $fetch->fetchAll ();
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');
        
        return $data;
    }
}
?>