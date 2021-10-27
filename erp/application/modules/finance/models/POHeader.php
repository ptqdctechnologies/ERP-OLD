<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/8/11
 * Time: 3:17 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_POHeader extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_poh';

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

    public function finalpolist($offset=0,$limit=0,$dir='DESC',$sort='trano',$search)
    {
        if ($search != "")
        {
            $where = " AND $search";
//            $where2= " AND PO.$search";
            $where2= " WHERE PO.$search";
            $where3= " AND siPO.$search";
        }
//        $query2 = "SELECT SQL_CALC_FOUND_ROWS trano,tgl,PH.prj_kode,PH.prj_nama,petugas,sup_kode,sup_nama,val_kode,total FROM procurement_poh PH LEFT JOIN workflow_trans WT ON PH.trano = WT.item_id
//                  $where AND WT.final = 1 GROUP BY trano ORDER BY $sort $dir LIMIT $offset,$limit ";

        $query = "
                DROP TEMPORARY TABLE IF EXISTS final_workflow;
                CREATE TEMPORARY TABLE final_workflow
                    SELECT final,item_id from workflow_trans
                    WHERE final = 1;";
        $this->db->query($query);

        $query = "CREATE INDEX idx_item ON final_workflow(`item_id`);";
        $this->db->query($query);

//        $query = "DROP TEMPORARY TABLE IF EXISTS po_to_rpi;
//                CREATE TEMPORARY TABLE po_to_rpi
//                    SELECT PO.trano,PO.tgl,PO.prj_kode,PO.prj_nama,PO.petugas,PO.sup_kode,PO.sup_nama,PO.val_kode,PO.total
//                    FROM procurement_poh PO
//                    LEFT JOIN procurement_rpih RPI
//                    ON PO.trano = RPI.po_no
//                    WHERE RPI.trano is null $where2
//                    /*GROUP BY PO.trano
//                    ORDER BY PO.trano desc*/;";
        $query = "DROP TEMPORARY TABLE IF EXISTS po_to_rpi;
                CREATE TEMPORARY TABLE po_to_rpi
                    SELECT PO.trano,PO.tgl,PO.prj_kode,PO.prj_nama,PO.petugas,PO.sup_kode,PO.sup_nama,PO.val_kode,PO.total
                    FROM procurement_poh PO
                    $where2
                    /*GROUP BY PO.trano
                    ORDER BY PO.trano desc*/;";
        $this->db->query($query);
        $query = "CREATE INDEX idx_trano ON po_to_rpi(`trano`);";
        $this->db->query($query);


        $query = "SELECT SQL_CALC_FOUND_ROWS siPO.* FROM po_to_rpi siPO LEFT JOIN final_workflow FW
                ON siPO.trano = FW.item_id WHERE item_id is not null $where3
                GROUP BY siPO.trano ORDER BY $sort $dir LIMIT $offset,$limit ;";

        $fetch = $this->db->query($query);
        $data['data'] = $fetch->fetchAll();
        $data['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        return $data;
    }

}