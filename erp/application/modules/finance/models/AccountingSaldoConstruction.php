<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_Models_AccountingSaldoConstruction extends Zend_Db_Table_Abstract {

    protected $_name = 'accounting_saldo_construction';
    protected $db;
    protected $const;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name() {
        return $this->_name;
    }

    public function getProjectProgress($prj_kode = '', $sit_kode = '') {
        $prog = new ProjectManagement_Models_ProjectProgress();
        $pros = $prog->getAllLastProgress($prj_kode, $sit_kode);

        $budget = new Default_Models_Budget();
        $p = new Default_Models_MasterProject();
        $s = new Default_Models_MasterSite();
        $data = array();
        foreach ($pros as $k => $v) {
            $prj = $v['prj_kode'];
            $sit = $v['sit_kode'];

            $co = $budget->getBoq2('summary-current', $prj, $sit);
            $total = $co['totalCurrent'] * ($v['progress'] / 100);

            $data[$prj]['total'] += $total;
            $data[$prj]['total_co'] += $co['totalCurrent'];
            if ($data[$prj]['date'] == '')
                $data[$prj]['date'] = $v['date'];
            else {
                $d1 = strtotime($v['date']);
                $d2 = strtotime($data[$prj]['date']);

                if ($d1 >= $d2) {
                    $data[$prj]['date'] = $v['date'];
                }
            }
        }

        $return = array();
        foreach ($data as $k => $v) {
            $return[] = array(
                "prj_kode" => $k,
                "prj_nama" => $p->getProjectName($k),
                "total" => $v['total'],
                "total_co" => $v['total_co'],
                "persen" => ($v['total'] / $v['total_co']) * 100,
                "last_update" => date("d M Y", strtotime($v['date']))
            );
        }

        return $return;
    }

    public function isExistPeriode($bulan = '', $tahun = '') {
        $select = $this->db->select()
                ->from(array($this->_name))
                ->where("periode=?", $bulan)
                ->where("tahun=?", $tahun);

        $cek = $this->db->fetchRow($select);
        if ($cek)
            return true;

        return false;
    }

    public function truncateExist($bulan = '', $tahun = '') {
        if ($this->isExistPeriode($bulan, $tahun)) {
            $this->delete("periode = '$bulan' AND tahun = '$tahun'");
        }

        return true;
    }

    public function getSaldoWithPeriode($prjKode = '', $tglawal = '', $tglakhir = '') {
        $periode = new Finance_Models_MasterPeriode();

        $where = "";
        
        
        //Get Periode from date range! 

        if ($tglawal && $tglakhir)
            $where = "('$tglawal' between tgl_awal and tgl_akhir) OR ('$tglakhir' between tgl_awal and tgl_akhir)";
        else if ($tglawal && !$tglakhir)
            $where = "'$tglawal' between tgl_awal and tgl_akhir";
        else if (!$tglawal && $tglakhir)
            $where = "'$tglakhir' between tgl_awal and tgl_akhir";

        if ($where)
            $date = $periode->fetchRow($where);

        $condition = '';
        if ($date) {
            $perBulan = $date['bulan'];
            $perTahun = $date['tahun'];
            $condition = "periode ='$perBulan' and tahun ='$perTahun'";
        } else {
            $perBulan = '';
            $perTahun = '';
        }

        if ($condition) {
            if ($prjKode)
                $condition .= " and prj_kode = '$prjKode'";
        }else {
            if ($prjKode)
                $condition = "prj_kode = '$prjKode'";
        }

        $where = '';
        if ($condition)
            $where = "Where " . $condition;


        $sql = "select *, sum(total)totals from accounting_saldo_construction $where group by prj_kode, coa_kode";
        $data = $this->db->fetchAll($sql);


        if ($data)
            return $data;
        else
            return false;
    }

}
