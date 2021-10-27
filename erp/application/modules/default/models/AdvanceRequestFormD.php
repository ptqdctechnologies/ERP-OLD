<?php

class Default_Models_AdvanceRequestFormD extends Zend_Db_Table_Abstract {

    protected $_name = 'procurement_arfd';
    protected $_primary = 'trano';
    protected $db;
    protected $const;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
    
    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getArfSum($prj_kode = '', $sit_kode = '') {
        if ($prj_kode != '')
            $where = "a.prj_kode = '$prj_kode'";
        if ($sit_kode != '' && $prj_kode != '')
            $where .= " AND a.sit_kode='$sit_kode' ";
//        if ($requestor != '')
//        {
//        	if ($where != '')
//        		$where .= " AND b.request='$requestor' ";
//        	else
//        		$where = " b.request='$requestor' ";
//        }
//        var_dump ($prj_kode);die();

        $sql = "SELECT
                    p.trano,
                    DATE_FORMAT(p.tgl, '%m/%d/%Y') as tgl,
                    p.prj_kode,
                    p.prj_nama,
                    p.sit_kode,
                    p.sit_nama,
                    p.workid,
                    p.workname,
                    p.val_kode,
                    SUM(p.total) as total,
                    p.pc_nama,
                    p.ket
                FROM(
                        SELECT
                            a.trano,
                            a.tgl,
                            a.prj_kode,
                            a.prj_nama,
                            a.sit_kode,
                            a.sit_nama,
                            a.workid,
                            a.workname,
                            a.val_kode,
                            a.total,
                            (SELECT uid FROM master_login WHERE id = b.request) as pc_nama,
                            b.ket
                        FROM
                            procurement_arfd a
                        LEFT JOIN
                            procurement_arfh b
                        ON
                           (a.trano = b.trano AND
                            a.prj_kode = b.prj_kode AND
                            a.sit_kode = b.sit_kode)
                        WHERE
                        	$where
                            ) p
                GROUP BY p.trano
                ORDER BY p.trano ";
//        var_dump ($sql);die();

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();
        return $result;
    }

    public function getArfDetail($trano = '') {
        $sql = "SELECT
                    p.trano,
                    DATE_FORMAT(p.tgl, '%m/%d/%Y') as tgl,
                    p.prj_kode,
                    p.prj_nama,
                    p.sit_kode,
                    p.sit_nama,
                    p.workid,
                    p.workname,
                    p.kode_brg,
                    p.nama_brg,
                    p.qty,
                    p.harga,
                    p.val_kode,
                    p.total,
                    p.pc_nama,
                    p.ket
                FROM(
                        SELECT
                            a.trano,
                            a.tgl,
                            a.prj_kode,
                            a.prj_nama,
                            a.sit_kode,
                            a.sit_nama,
                            a.workid,
                            a.workname,
                            a.kode_brg,
                            a.nama_brg,
                            a.qty,
                            a.harga,
                            a.val_kode,
                            a.qty * a.harga as total,
                            (SELECT uid FROM master_login WHERE id = b.request) as pc_nama,                     
                            b.ket
                        FROM
                            procurement_arfd a
                        LEFT JOIN
                            procurement_arfh b
                        ON
                           (a.trano = b.trano AND
                            a.prj_kode = b.prj_kode AND
                            a.sit_kode = b.sit_kode)
                        WHERE
                            a.trano = '$trano'
                            ) p               
                ORDER BY p.kode_brg";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function __name() {
        return $this->_name;
    }

    public function totalSummary($trano = '') {
        $select = $this->select()
                ->from(array($this->_name), array(
                    "SUM(qty*harga) AS total"
                        )
                )
                ->where("trano = '$trano'")
                ->group(array("trano"));
        $data = $this->fetchRow($select);
        if ($data) {
            $data = $data['total'];
        } else
            $data = 0;

        return $data;
    }

    public function getDataAdvance($trano) {

        $query = $this->db->prepare("call get_data_advance(\"$trano\")");
        $query->execute();
        $query->closeCursor();
        if ($query) {

            $sql = 'Select * from arf';
            try {
                $data['posts'] = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $data['posts'] = null;
            }

            $sql = 'Select * from bpv_data';
            try {
                $data['dataBpv'] = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $data['dataBpv'] = null;
            }

            $sql = 'Select * from pay_data';
            try {
                $data['dataPayment'] = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $data['dataPayment'] = null;
            }

            $sql = 'Select * from asf';
            try {
                $data['dataAsf'] = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $data['dataAsf'] = null;
            }

            $sql = 'Select * from asfcancel';
            try {
                $data['dataAsfCancel'] = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $data['dataAsfCancel'] = null;
            }
        }
        if (count($data['posts']) > 0) {
            foreach ($data as $key => $val) {
                if (count($val) > 0)
                    foreach ($val as $key2 => $val2) {
                        if (count($val2 > 0))
                            foreach ($val2 as $key3 => $val3) {
                                if ($val3 == '""')
                                    $data[$key][$key2][$key3] = str_replace('""', '', $val3);
                            }
                    }
            }
        }

        $data['count'] = count($data['posts']);

        return $data;
    }

}

?>