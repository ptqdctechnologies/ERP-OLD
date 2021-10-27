<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/1/11
 * Time: 11:05 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_Invoice extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_invoice';

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

    public function viewallrequest ($offset=0,$limit=0,$dir='ASC',$sort='trano',$search)
    {

        if ($search != "")
            $where = "WHERE $search";

        $query = "SELECT SQL_CALC_FOUND_ROWS trano,riv_no,coa_kode,tgl,cus_kode,prj_kode,sit_kode,prj_nama,sit_nama,SUM(COALESCE(total,0)) as total,"
                . "val_kode,tax_number,rateidr FROM " . $this->_name . " $where
                    GROUP BY trano ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query ($query);

        $data['data'] = $fetch->fetchAll ();
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');
        
        return $data;
    }

    public function name()
    {
        return $this->_name;
    }

    public function getTotalInvoice($prj_kode='',$sit_kode='')
    {
        $subselect = $this->db->select()
            ->from(array($this->_name),array(
                new Zend_Db_Expr("(CASE val_kode WHEN 'IDR' THEN total ElSE 0.00 END) AS totalIDR"),
                new Zend_Db_Expr("(CASE val_kode WHEN 'USD' THEN total ElSE 0.00 END) AS totalUSD")
            ))
            ->where("prj_kode = '$prj_kode'");

        if ($sit_kode)
            $subselect = $subselect->where("sit_kode = '$sit_kode'");
        $select = $this->db->select()
            ->from(array(
                "a" => $subselect
            ),array(
                "SUM(a.totalIDR) AS totalIDR",
                "SUM(a.totalUSD) AS totalUSD",
            )
        );

        $invoice = $this->db->fetchRow($select);

        $ci = new Finance_Models_AccountingCancelInvoice();
        $subselect = $this->db->select()
            ->from(array($ci->__name()),array(
            new Zend_Db_Expr("(CASE val_kode WHEN 'IDR' THEN total_cancel ElSE 0.00 END) AS totalIDR"),
            new Zend_Db_Expr("(CASE val_kode WHEN 'USD' THEN total_cancel ElSE 0.00 END) AS totalUSD")
        ))
            ->where("prj_kode = '$prj_kode'");

        if ($sit_kode)
            $subselect = $subselect->where("sit_kode = '$sit_kode'");
        $select2 = $this->db->select()
            ->from(array(
                "a" => $subselect
            ),array(
                "SUM(a.totalIDR) AS totalIDR",
                "SUM(a.totalUSD) AS totalUSD",
            )
        );
        $cinvoice = $this->db->fetchRow($select2);

        return array(
            "invoice" => $invoice,
            "cancel_invoice" => $cinvoice,
        );
    }
     public function __name()
    {
        return $this->_name;
    }
}
?>