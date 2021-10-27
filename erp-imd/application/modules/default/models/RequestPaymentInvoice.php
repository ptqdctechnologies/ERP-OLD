<?php

class Default_Models_RequestPaymentInvoice extends Zend_Db_Table_Abstract {

    protected $_name = 'procurement_rpid';
    protected $_primary = 'trano';
    protected $db;
    protected $const;

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name() {
        return $this->_name;
    }

    public function getrpiSum($prj_kode = '', $sit_kode = '', $sup_kode = '', $ppn_ref_number = '') {
//    	if ($sit_kode != '')
//    		$where = "AND b.sit_kode = '$sit_kode'";

        if ($prj_kode != '')
            if ($where == '')
                $where = "a.prj_kode = '$prj_kode'";
            else
                $where .= " AND a.$prj_kode = '$prj_kode'";

        if ($sit_kode != '')
            if ($where == '')
                $where = "a.sit_kode = '$sit_kode'";
            else
                $where .= " AND a.sit_kode = '$sit_kode'";

        if ($ppn_ref_number != '')
            if ($where == '')
                $where = "c.ppn_ref_number LIKE '%$ppn_ref_number%'";
            else
                $where .= " AND c.ppn_ref_number LIKE '%$ppn_ref_number%'";


        if ($sup_kode != '') {
            if ($where == '')
                $where = "a.sup_kode = '$sup_kode'";
            else
                $where .= " AND a.sup_kode = '$sup_kode'";
        }
        $sql = "

                        SELECT
                            b.trano,
                            DATE_FORMAT(b.tgl, '%d/%m/%Y') as tgl,
                            b.prj_kode,
                            b.prj_nama,
                            b.sit_kode,
                            b.sit_nama,
                            b.workid,
                            b.workname,
                            b.val_kode,
                            b.po_no,
                            SUM((CASE b.val_kode WHEN 'IDR' THEN (b.harga*b.qty) ElSE 0.00 END)) AS total_IDR,
                            SUM((CASE b.val_kode WHEN 'USD' THEN (b.harga*b.qty) ElSE 0.00 END)) AS total_USD,
                           (SELECT uid FROM master_login WHERE master_login =  a.user) as pc_nama,
                            a.sup_kode,
                            a.sup_nama,
                            c.ref_number   
                        FROM
                            procurement_rpih a
                        LEFT JOIN
                           	 procurement_rpid b
                        ON
                            (a.trano = b.trano AND a.deleted=0 AND b.deleted=0)
                        LEFT JOIN
                           	 (select trano,ppn_ref_number,ref_number from finance_payment_voucher WHERE deleted=0 group by ref_number)as c
                     	ON
                            (a.trano = c.ref_number AND a.deleted=0)                       
                        WHERE
                            $where
                        GROUP BY b.trano
                        ORDER BY b.trano
                        ";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function getrpiDetail($trano = '') {
        $sql = "
                    SELECT
                            b.trano,
                            DATE_FORMAT(b.tgl, '%d/%m/%Y') as tgl,
                            b.prj_kode,
                            b.prj_nama,
                            b.sit_kode,
                            b.sit_nama,
                            b.workid,
                            b.workname,
                            b.val_kode,
                            b.kode_brg,
                            b.nama_brg,
                            b.qty,
                            b.harga,
                            b.po_no,
                            (CASE b.val_kode WHEN 'IDR' THEN (b.harga*b.qty) ElSE 0.00 END) AS total_IDR,
                            (CASE b.val_kode WHEN 'USD' THEN (b.harga*b.qty) ElSE 0.00 END) AS total_USD,
                            (SELECT uid FROM master_login WHERE master_login = a.user) as pc_nama
                        FROM
                            procurement_rpih a
                        LEFT JOIN
                           	 procurement_rpid b
                        ON
                            (a.trano = b.trano AND a.deleted=0 AND b.deleted=0)
                        WHERE
                            b.trano = '$trano'
            ";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function getDetailForPayment($trano) {
        $sql = "SELECT
                SUM(qty*harga) AS total,prj_kode,prj_nama,sit_kode,sit_nama
               FROM
                procurement_rpid
               WHERE
                trano = '$trano'
               GROUP BY
                prj_kode,sit_kode
                ";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function transferJurnalAP($trano, $itemType) {
        $temporary_jurnal_ap = new Finance_Models_AccountingTemporaryJurnalAP();
        $jurnal_ap = new Finance_Models_AccountingCloseAP();
        $temporary_bpv = new Finance_Models_AccountingTemporaryBPV();
        $bpvh = new Finance_Models_BankPaymentVoucher();
        $bpvd = new Finance_Models_BankPaymentVoucherD();
        $counter = new Default_Models_MasterCounter();
        $rpih = new Default_Models_RequestPaymentInvoiceH();
        $coa = new Default_Models_MasterCoa();

        $bpv = $temporary_bpv->fetchRow("trano = '$trano'");

        if (!$bpv)
            return true;

        $bpv = $bpv->toArray();
        $bpvdata = Zend_Json::decode($bpv['data']);

        $tranobpv = $counter->setNewTrans('BPV');
        $date = date('Y-m-d H:i:s');
        $uid = $bpvdata[0]['uid'];
        $coa_kode = $bpvdata[0]['coa_kode'];

        $totalWHT = 0;
        $adaWht = false;
        $tranoWHT = '';
        foreach ($bpvdata as $key => $val) {
            $adaPPN = false;

            $statusppn = $val['statusppn'];

            if ($statusppn == 'Y') {
                $coa_ppn = '1-4400';
                $adaPPN = true;
            } else {
                $coa_ppn = '';
            }

            $total_bayar = str_replace(",", "", $val['total_bayar']);
            $ht_value = str_replace(",", "", $val['holding_tax_val']);

//            if ($grossup == 'Y')
//            {
//                $total_bayar = floatval($total_bayar) + floatval($ht_value);
//            }

            $tranoPPN = '';
            $statusppnInsert = $val['statusppn'];
            $totalInsert = floatval(str_replace(",", "", $val['total']));
            $valueppnInsert = str_replace(",", "", $val['valueppn']);
            $coappnInsert = $coa_ppn;

            if ($adaPPN) {
                $tranoPPN = $counter->setNewTrans('BPV');
                $statusppnInsert = 'N';
                $totalInsert = floatval(str_replace(",", "", $val['total'])) - floatval(str_replace(",", "", $val['valueppn']));
                $valueppnInsert = 0;
                $coappnInsert = '';
                $total_bayar = $total_bayar; // - floatval(str_replace(",","",$val['valueppn']));
            }


            if ($val['holding_tax_status'] == 'Y') {
                $totalWHT += floatval($ht_value);
                if (!$adaWht) {
                    $tranoWHT = $counter->setNewTrans('BPV');
                    $adaWht = true;
                }
            }

            $insertbpvh = array(
                "trano" => $tranobpv,
                "trano_ppn" => $tranoPPN,
                "trano_wht" => $tranoWHT,
                "tgl" => $date,
                "item_type" => $val['item_type'],
                "total_value" => $val['total_value'],
                "total_bayar" => $val['total_bayar'],
                "statusppn" => $statusppnInsert,
                "valueppn" => $valueppnInsert,
                "coa_ppn" => $coappnInsert,
                "grossup_status" => $val['grossup_status'],
                "holding_tax_status" => $val['holding_tax_status'],
                "holding_tax" => $val['holding_tax'],
                "holding_tax_val" => $val['holding_tax_val'],
                "holding_tax_text" => $val['holding_tax_text'],
                "coa_holding_tax" => $val['coa_holding_tax'],
                "deduction" => $val['deduction'],
                "total" => $totalInsert,
                "valuta" => $val['valuta'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "ref_number" => $val['ref_number'],
                "coa_kode" => $val['coa_kode'],
                "ketin" => $val['ketin'],
                "requester" => $val['requester'],
                "uid" => $val['uid'],
                "statuspulsa" => '0',
                "ppn_ref_number" => '',
                "status_bpv_ppn" => '',
                "type" => $val['type']
            );

            $bpvh->insert($insertbpvh); //insert bank payment voucher header

            if ($adaPPN) {
                $insertdata = array(
                    'trano' => $tranoPPN,
                    'trano_ppn' => '',
                    'tgl' => $date,
                    'ref_number' => $val['ref_number'],
                    'item_type' => $val['item_type'],
                    'total_bayar' => str_replace(",", "", $val['valueppn']),
                    'statusppn' => 'N',
                    'valueppn' => 0,
                    'valuta' => $val['valuta'],
                    'prj_kode' => $val['prj_kode'],
                    'sit_kode' => $val['sit_kode'],
                    'coa_kode' => $val['coa_kode'],
                    'ketin' => $val['ketin'],
                    'uid' => $uid,
                    'requester' => $val['requester'],
                    'total' => str_replace(",", "", $val['valueppn']),
                    'deduction' => 0,
                    'statuspulsa' => 0,
                    'total_value' => str_replace(",", "", $val['valueppn']),
                    'coa_ppn' => $coa_ppn,
                    'ppn_ref_number' => $val['ppn_ref_number'],
                    'status_bpv_ppn' => 1,
                    'type' => $val['type']
                );

                $bpvh->insert($insertdata);
            }
        }

        // Jika ada WHT
        if ($adaWht) {
            $insertdata = array(
                'trano' => $tranoWHT,
                'trano_ppn' => '',
                'tgl' => $date,
                'ref_number' => $val['ref_number'],
                'item_type' => $val['item_type'],
                'total_bayar' => $totalWHT,
                'statusppn' => 'N',
                'valueppn' => 0,
                'valuta' => $val['valuta'],
                'prj_kode' => $val['prj_kode'],
                'sit_kode' => $val['sit_kode'],
                'coa_kode' => $val['coa_holding_tax'],
                'ketin' => $val['ketin'],
                'uid' => $uid,
                'requester' => $val['requester'],
                'total' => $totalWHT,
                'deduction' => 0,
                'statuspulsa' => 0,
                'total_value' => $totalWHT,
                'status_bpv_wht' => 1,
                'type' => $val['type']
            );

            $bpvh->insert($insertdata);
        }

        $rpidata = $this->fetchAll("trano = '$trano'")->toArray();

        if ($rpidata) {
            foreach ($rpidata as $keyrpi => $valrpi) {
                $insertrpi = array(
                    'trano' => $tranobpv,
                    'tgl' => $date,
                    'uid' => $uid,
                    'ref_number' => $valrpi['trano'],
                    'prj_kode' => $valrpi['prj_kode'],
                    'prj_nama' => $valrpi['prj_nama'],
                    'sit_kode' => $valrpi['sit_kode'],
                    'sit_nama' => $valrpi['sit_nama'],
                    'workid' => $valrpi['workid'],
                    'workname' => $valrpi['workname'],
                    'kode_brg' => $valrpi['kode_brg'],
                    'nama_brg' => $valrpi['nama_brg'],
                    'qty' => $valrpi['qty'],
                    'harga' => $valrpi['harga'],
                    'total' => $valrpi['total'],
                    'val_kode' => $valrpi['val_kode'],
                    'rateidr' => $valrpi['rateidr'],
                    'cfs_kode' => $valrpi['cfs_kode'],
                    'coa_kode' => ''
                );

                $bpvd->insert($insertrpi); // insert bank payment voucher detail
            }
        }

        $jurnal = $temporary_jurnal_ap->fetchRow("trano = '$trano'")->toArray();

        $jurnaldata = Zend_Json::decode($jurnal['jurnal']);
        
        $tranoAP = $counter->setNewTrans('AP');
        

        foreach ($jurnaldata as $key2 => $val2) {
            $insertjurnal = array(
                "trano" => $tranobpv,
                "ref_number" => $trano,
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $date,
                "uid" => $uid,
                "coa_kode" => $val2['coa_kode'],
                "coa_nama" => $val2['coa_nama'],
                "debit" => $val2['debit'],
                "credit" => $val2['credit'],
                "stsclose" => 0,
                "val_kode" => $val2['val_kode'],
                "rateidr" => $val2['rateidr'],
                "job_number" => $val2['job_number'],
                "ref_number_accounting" => $tranoAP
            );

            $jurnal_ap->insert($insertjurnal);
        }

        return $tranobpv;
//        $notif = new Default_Models_UserNotification();
//        $notif->sendNotificationFinalApproval($itemType,$trano,"RPI with trano : $trano has been Final Approved. Now you can print BPV Number : $tranobpv",1,$tranobpv);
    }

}

?>