<?php

class Procurement_Models_Procurementprh extends Zend_Db_Table_Abstract{

    protected $_name = 'procurement_prh';

    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }
    
     public function ViewPrFinalApprove ($offset=0,$limit=0,$dir='ASC',$sort='AH.trano',$search,$type)
    {
        $query =    "select SQL_CALC_FOUND_ROWS trano,prj_kode,prj_nama,sit_kode,sit_nama,tipe
                    from procurement_prh
                    where trano LIKE 'PR%' and approve =400 $search or approve =300 and revisi!=0 $search
                    group by trano order by $sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query($query);
        $data['posts'] = $fetch->fetchAll();
        $data['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        return $data;
    }
}

