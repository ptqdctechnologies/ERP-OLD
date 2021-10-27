<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/14/11
 * Time: 4:20 PM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_PpnReimbursementD extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_ppn_reimbursementd';

    protected $_primary = 'trano';
    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name()
    {
        return $this->_name;
    }

    public function transferJurnalAP($trano='',$item_type='')
    {
        $temporary_jurnal_ap = new Finance_Models_AccountingTemporaryJurnalAP();
        $jurnal_ap = new Finance_Models_AccountingCloseAP();
        $temporary_bpv = new Finance_Models_AccountingTemporaryBPV();
        $bpvh = new Finance_Models_BankPaymentVoucher();
        $bpvd = new Finance_Models_BankPaymentVoucherD();
        $counter = new Default_Models_MasterCounter();

        $bpv = $temporary_bpv->fetchRow("trano = '$trano'");

        if (!$bpv)
            return true;

        $bpv = $bpv->toArray();
        $bpvdata = Zend_Json::decode($bpv['data']);

        $tranobpv = $counter->setNewTrans('BPV');
        $date = date('Y-m-d H:i:s');
        $uid = $bpvdata[0]['uid'];

        foreach ($bpvdata as $key => $val)
        {
            $total_bayar = str_replace(",","",$val['total_bayar']);

            $totalInsert = floatval(str_replace(",","",$val['total']));
            $valueppnInsert = str_replace(",","",$val['valueppn']);

            $insertbpvh = array(
                "trano" => $tranobpv,
                "trano_ppn" => '',
                "tgl" => $date,
                "item_type" => $val['item_type'],
                "total_value" => $val['total_value'],
                "total_bayar" => $val['total_bayar'],
                "statusppn" => 'N',
                "valueppn" => 0,
                "coa_ppn" => '',
                "grossup_status" => 'N',
                "holding_tax_status" => 'N',
                "holding_tax" => 0,
                "holding_tax_val" => 0,
                "holding_tax_text" => '',
                "coa_holding_tax" => '',
                "deduction" => 0,
                "total" => $totalInsert,
                "valuta" => $val['valuta'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "ref_number" => $val['ref_number'],
                "coa_kode" => '',
                "ketin" => $val['ketin'],
                "requester" => $val['requester'],
                "uid" => $val['uid'],
                "statuspulsa" => '0',
                "ppn_ref_number" => '',
                "status_bpv_ppn" => '',
                "type" => $val['type']
            );

            $bpvh->insert($insertbpvh); //insert bank payment voucher header
        }

        $ppndata = $this->fetchAll("trano = '$trano'")->toArray();

        if ($ppndata)
        {
            foreach($ppndata as $keyrpi => $valrpi)
            {
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
                    'cfs_kode' => '',
                    'coa_kode' => ''
                );

                $bpvd->insert($insertrpi); // insert bank payment voucher detail
            }
        }

        $jurnal = $temporary_jurnal_ap->fetchRow("trano = '$trano'")->toArray();

        $jurnaldata = Zend_Json::decode($jurnal['jurnal']);

        foreach ($jurnaldata as $key2 => $val2)
        {
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

            );

            $jurnal_ap->insert($insertjurnal);
        }

        return $tranobpv;
    }
}
