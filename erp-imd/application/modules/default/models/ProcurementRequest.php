<?php

class Default_Models_ProcurementRequest extends Zend_Db_Table_Abstract {

    protected $_name = 'procurement_prd';
    protected $db;
    protected $const;
    protected $_primary = 'trano';

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getPrSum($prj_kode = '', $sit_kode = '', $kode_brg = '', $workid = '', $tgl1 = '', $tgl2 = '') {
        if ($prj_kode != '')
            $where[] = "a.prj_kode = '$prj_kode'";
        if ($sit_kode != '')
            $where[] = "a.sit_kode = '$sit_kode'";
        if ($kode_brg != '')
            $where[] = "a.kode_brg = '$kode_brg'";
        if ($workid != '')
            $where[] = "a.workid = '$workid'";

        if ($where)
            $where = implode(" AND ", $where);

        if ($tgl1 != '') {
            if ($tgl2 != '') {
                if ($where == '')
                    $where = "b.tgl BETWEEN '$tgl1' AND '$tgl2' ";
                else
                    $where .= " AND (b.tgl BETWEEN '$tgl1' AND '$tgl2')";
            }
            else {
                if ($where == '')
                    $where = "b.tgl = '$tgl1'";
                else
                    $where .= " AND b.tgl = '$tgl1'";
            }
        }

        if (!$where)
            $where = null;
        else
            $where = " WHERE $where ";


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
                    SUM(p.totalIDR) as total_IDR,
                    SUM(p.totalUSD) as total_USD,       
                    p.qty,
		    p.harga,                  
                    p.pc_nama
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
                            a.qty,
                            a.harga,
                           (CASE a.val_kode WHEN 'IDR' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalIDR,
                           (CASE a.val_kode WHEN 'USD'  THEN (a.harga*a.qty) ElSE 0.00 END) AS totalUSD,
                           (SELECT uid FROM master_login WHERE master_login = b.user) as pc_nama                     
                        FROM
                            procurement_prd a
                        LEFT JOIN
                            procurement_prh b
                        ON
                            a.trano = b.trano
                        $where
                        ) p
                GROUP BY p.trano
                ORDER BY p.trano ";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function getPrDetail($trano = '') {
        $sql = "SELECT
                    trano,
                    DATE_FORMAT(tgl, '%m/%d/%Y') as tgl,
                    prj_kode,
                    prj_nama,
                    sit_kode,
                    sit_nama,
                    workid,
                    workname,
                    kode_brg,
                    nama_brg,
                    qty,
                    harga,
                   (CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS total_IDR,
                   (CASE val_kode WHEN 'USD'  THEN (harga*qty) ElSE 0.00 END) AS total_USD,
                    val_kode
                FROM
                    procurement_prd
                WHERE             
                    trano = '$trano'
                ORDER BY
                    trano";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function __name() {
        return $this->_name;
    }

    public function getDataProject($trano) {

        $query = $this->db->prepare("call get_data_project(\"$trano\")");
        $query->execute();
        $query->closeCursor();
        if ($query) {

            $sql = 'Select * from pr';
            try {
                $data['posts'] = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $data['posts'] = null;
            }
            $sql = 'Select * from po';
            try {
                $data['dataPO'] = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $data['dataPO'] = null;
            }
            $sql = 'Select * from rpi';
            try {
                $data['dataRpi'] = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $data['dataRpi'] = null;
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
        }

        if (count($data['posts']) > 0) {
            foreach ($data as $key => $val) {
                if (count($val) > 0)
                    foreach ($val as $key2 => $val2) {
                        if (count($val2) > 0)
                            foreach ($val2 as $key3 => $val3) {
                                if ($val3 == '""') {
                                    $data[$key][$key2][$key3] = str_replace('""', '', $val3);
                                }
                            }
                    }
            }
        }

        $data['count'] = count($data['posts']);

        return $data;
    }

}

?>