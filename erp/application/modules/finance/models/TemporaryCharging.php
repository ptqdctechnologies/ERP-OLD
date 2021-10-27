<?php

class Finance_Models_TemporaryCharging extends Zend_Db_Table_Abstract {

    protected $_name = 'finance_temporary_charging';
    protected $_primary = 'trano';
    protected $db;
    protected $const;

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function __name() {
        return $this->_name;
    }

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function transferOverheadCost($trano = '') {
        $counter = new Default_Models_MasterCounter();
        $asfh = new Default_Models_AdvanceSettlementFormH();
        $asfd = new Default_Models_AdvanceSettlementFormD();
        $asfdd = new Default_Models_AdvanceSettlementForm();
        $bankOut = new Finance_Models_BankSpendMoney();

        $arrayTrano = array();
        $cek = $this->fetchRow("trano='$trano'");
        if ($cek) {
            $cek = $cek->toArray();
            $uidSubmit = $cek['uid'];
            $trano = $cek['trano'];
            $prj_kode = $cek['prj_kode'];
            $data = Zend_Json::decode($cek['data']);
            $isEdit = $cek['edit'];
            $isEdit = ($isEdit == 1 ? true : false);

            $tglChange = date("Y-m-d", strtotime($cek['tgl_change']));
            $tgl = date("Y-m-d", strtotime($cek['tgl']));

            if ($tglChange != $tgl) {
                $tglInsert = $tglChange;
            } else
                $tglInsert = date('Y-m-d');

            $rate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
            if ($isEdit) {
                $before = $asfd->fetchAll("trano = '$trano'");
                if ($before) {
                    $log['asfd-detail-before'] = $before->toArray();
                    $asfd->delete("trano = '$trano'");
                }
                $before = $asfdd->fetchAll("trano = '$trano'");
                if ($before) {
                    $log['asfdd-detail-before'] = $before->toArray();
                    $asfdd->delete("trano = '$trano'");
                }
                $before = $asfh->fetchAll("trano = '$trano'");
                if ($before) {
                    $log['asfh-detail-before'] = $before->toArray();
                    $asfh->delete("trano = '$trano'");
                }
            }
            foreach ($data as $k => $val) {
                if ($val['workid'] == '')
                    continue;

                $total = $val['debit'];
                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => $tglInsert,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => 1,
                    "harga" => $total,
                    "total" => $total,
                    "ket" => $val['ket'],
                    "petugas" => $uidSubmit,
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $rate['rateidr'],
                    "arf_no" => $val['ref_number']
                );
                $asfdd->insert($arrayInsert);
            }

            $dd = $this->groupCostByProjectSite($data);
            foreach ($dd as $k => $v) {
                foreach ($v as $key => $val) {

                    $total = $val['total'];
                    $arrayInsert = array(
                        "trano" => $trano,
                        "tgl" => $tglInsert,
                        "prj_kode" => $k,
                        "prj_nama" => $val['prj_nama'],
                        "sit_kode" => $key,
                        "sit_nama" => $val['sit_nama'],
                        "total" => 0,
                        "petugas" => $uidSubmit,
                        "requestv" => 0,
                        "totalasf" => $total,
                        "val_kode" => $val['val_kode'],
                        "rateidr" => $rate['rateidr'],
                    );
                    $asfd->insert($arrayInsert);

                    $arrayInsert = array(
                        "trano" => $trano,
                        "tgl" => $tglInsert,
                        "prj_kode" => $k,
                        "prj_nama" => $val['prj_nama'],
                        "sit_kode" => $key,
                        "sit_nama" => $val['sit_nama'],
                        "petugas" => $uidSubmit,
                        "total" => $total,
                        "requestv" => 0,
                        "user" => $uidSubmit,
                        "tglinput" => date('Y-m-d'),
                        "jam" => date('H:i:s'),
                        "val_kode" => $val['val_kode'],
                        "rateidr" => $rate['rateidr'],
                    );
                    $asfh->insert($arrayInsert);
                }
            }

            // bank section
            ///
            if ($isEdit) {
                $bankbefore = $bankOut->fetchAll("ref_number = '$trano'")->toArray();
                $logbank['bank-detail-before'] = $bankbefore;
                $bankOut->delete("ref_number = '$trano'");
            }

            $jj = $this->groupCostByBank($data);

            foreach ($jj as $k => $v) {

                $tranoBankBefore = '';
                $foundTrano = false;
                $bank = $k;

                if ($isEdit) {
                    foreach ($bankbefore as $key => $val) {
                        if (substr($val['trano'], 0, 3) == $bank) {
                            $tranoBankBefore = $val['trano'];
                            $foundTrano = true;
                        }
                    }
                }

                if ($foundTrano)
                    $tranoBank = $tranoBankBefore;
                else
                    $tranoBank = $counter->setNewTrans($bank);

                $jurnals = $v;

                $arrayTrano[] = $tranoBank;
                foreach ($jurnals as $k2 => $val) {

                    $insertbankout = array(
                        "trano" => $tranoBank,
                        "ref_number" => $trano,
                        "tgl" => $tglInsert,
                        "uid" => $uidSubmit,
                        "prj_kode" => $val['prj_kode'],
                        "sit_kode" => $val['sit_kode'],
                        "rateidr" => $rate['rateidr'],
                        "val_kode" => $val['val_kode'],
                    );

                    if ($val['debit'] != 0 || $val['debit_coa'] != '') {
                        $insertbankout["coa_kode"] = $val['debit_coa'];
                        $insertbankout["coa_nama"] = $val['debit_coa_nama'];
                        $insertbankout["debit"] = $val['debit'];
                        $insertbankout["credit"] = 0;
                        $bankOut->insert($insertbankout);
                    } else {
                        $insertbankout["coa_kode"] = $val['credit_coa'];
                        $insertbankout["coa_nama"] = $val['credit_coa_nama'];
                        $insertbankout["credit"] = $val['credit'];
                        $insertbankout["debit"] = 0;
                        $bankOut->insert($insertbankout);
                    }
                }
            }

            if ($isEdit) {

                $log2['asfd-detail-after'] = $asfd->fetchAll("trano = '$trano'")->toArray();
                $log2['asfdd-detail-after'] = $asfdd->fetchAll("trano = '$trano'")->toArray();
                $log2['asfh-detail-after'] = $asfh->fetchAll("trano = '$trano'")->toArray();

                $arrayLog = array(
                    "trano" => $trano,
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "tgl" => date('Y-m-d H:i:s'),
                    "prj_kode" => $prj_kode,
                    "sit_kode" => '',
                    "action" => "UPDATE",
                    "data_before" => Zend_Json::encode($log),
                    "data_after" => Zend_Json::encode($log2),
                    "ip" => $_SERVER["REMOTE_ADDR"],
                    "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
                );
                $log = new Admin_Models_Logtransaction();
                $log->insert($arrayLog);


                $logbank2['bank-detail-after'] = $bankOut->fetchAll("ref_number = '$trano'")->toArray();
                $arrayLog = array(
                    "trano" => $trano,
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "tgl" => date('Y-m-d H:i:s'),
                    "prj_kode" => $prj_kode,
                    "sit_kode" => '',
                    "action" => "UPDATE",
                    "data_before" => Zend_Json::encode($logbank),
                    "data_after" => Zend_Json::encode($logbank2),
                    "ip" => $_SERVER["REMOTE_ADDR"],
                    "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
                );
                $logbank = new Admin_Models_Logtransaction();
                $logbank->insert($arrayLog);
            }
        }

        return implode(", ", $arrayTrano);
    }

    public function groupCostByProjectSite($data = array()) {
        $newData = array();
        foreach ($data as $k => $v) {
            $prjKode = $v['prj_kode'];
            $sitKode = $v['sit_kode'];

            $newData[$prjKode][$sitKode]['prj_nama'] = $v['prj_nama'];
            $newData[$prjKode][$sitKode]['sit_nama'] = $v['sit_nama'];

            if ($v['workid'] != '')
                $newData[$prjKode][$sitKode]['total'] += $v['debit'];

            $newData[$prjKode][$sitKode]['val_kode'] = $v['val_kode'];
            $newData[$prjKode][$sitKode]['workid'] = $v['workid'];
        }

        return $newData;
    }

    public function groupCostByBank($data = array()) {
        $newData = array();
        foreach ($data as $k => $v) {
            $bank = $v['bank_type'];
            $newData[$bank][] = $v;
        }

        return $newData;
    }

}

?>
