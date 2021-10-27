<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/8/11
 * Time: 1:35 PM
 * To change this template use File | Settings | File Templates.
 */


class Procurement_Model_Procurementarfh extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_arfh';

    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function ViewArfFinalApprove ($offset=0,$limit=0,$dir='ASC',$sort='AH.trano',$search,$type)
    {
        $query =    "select SQL_CALC_FOUND_ROWS AH.trano,AH.prj_kode,AH.prj_nama,AH.sit_kode,AH.sit_nama,AH.tipe
                    from (procurement_arfh AH left join workflow_trans WT on AH.trano = WT.item_id)
                    left join procurement_asfh SH on AH.trano = SH.arf_no
                    where AH.tipe = '$type' and WT.final = 1 $search and WT.item_id is not null and SH.trano is null
                    group by AH.trano order by $sort $dir LIMIT $offset,$limit";

        $sql = "CREATE TEMPORARY TABLE arffinal
                SELECT SQL_CALC_FOUND_ROWS AH.trano,AH.prj_kode,AH.prj_nama,AH.sit_kode,AH.sit_nama,AH.tipe
                FROM (procurement_arfh AH left join workflow_trans WT on AH.trano = WT.item_id)
                WHERE AH.tipe = '$type' and WT.final = 1 $search and WT.item_id is not null;
        ";
//        $fetch = $this->db->query($sql);
        $sql = "SELECT AH.* FROM arffinal AH LEFT JOIN procurement_asfh SH on AH.trano = SH.arf_no
                WHERE SH.trano is null;
        ";

        $fetch = $this->db->query($query);
        $data['posts'] = $fetch->fetchAll();
        $data['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        return $data;
    }

}
