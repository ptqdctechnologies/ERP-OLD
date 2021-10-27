<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 9/14/11
 * Time: 3:04 PM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_BankPaymentVoucher extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_payment_voucher';
    protected $_primary = 'trano';
    protected $db;
    protected $const;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function name()
    {
        return $this->_name;
    }

    public function __name()
    {
        return $this->_name;
    }

    public function viewpaymentvoucher ($offset=0,$limit=0,$dir='ASC',$sort='trano',$type,$search)
    {
        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM finance_payment_voucher WHERE item_type = '$type' $search AND deleted=0 GROUP BY trano ORDER BY $sort $dir LIMIT $offset,$limit";echo $query;die;
        $fetch = $this->db->query($query);
        $data['data'] = $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        return $data;
    }

    public function sendMessageFinalApproval($rpiNo = '')
    {
        $bpv = $this->fetchRow("ref_number = '$rpiNo'");
        if ($bpv)
        {
            $bpv = $bpv->toArray();
            $bpvTrano = $bpv['trano'];

            $text = "RPI Document : <b>$rpiNo</b> has been Final Approved, Please review Bank Payment Voucher : <a href='#' onclick=\"goToPage('/finance/report/bankpaymentvoucher/trano/$bpvTrano','',true);Ext.getCmp('d-form-panel').close();Ext.getCmp('msg-form-panel').close();\";\'> <b>$bpvTrano</b></a>.";

            $rpi = new Default_Models_RequestPaymentInvoiceH();
            $rpih = $rpi->fetchRow("trano = '$rpiNo'");
            if ($rpih)
            {
                $rpih = $rpih->toArray();
                $uidSender = $rpih['petugas'];
            }

            $arrayInsert = array(
                "uid_sender" => $uidSender,
                "uid_receiver" => 'iva',
                "message" => $text,
                "date" => date('Y-m-d H:i:s'),
                "trano" => $bpvTrano
            );

            $conversation = new Default_Models_Conversation();
            $conversation->insert($arrayInsert);
        }
    }

    public function cekTranoWht($trano='')
    {
        $bpv = $this->fetchRow("trano = '$trano'");
        if ($bpv)
        {
            return true;
        }
        return false;
    }

    public function createNewWHT($trano='')
    {
        $bpv = $this->fetchRow("trano = '$trano'");
        if (!$bpv || $bpv['trano_wht'] != '')
            return false;

        $counter = new Default_Models_MasterCounter();
        $tranoWHT = $counter->setNewTrans('BPV');

        $bpv = $bpv->toArray();

        $insertdata = array(
            'trano' => $tranoWHT,
            'trano_ppn' => '',
            'tgl' => $bpv['tgl'],
            'ref_number' => $bpv['ref_number'],
            'item_type' => $bpv['item_type'],
            'total_bayar' => $bpv['holding_tax_val'],
            'statusppn' => 'N',
            'valueppn' => 0,
            'valuta' => $bpv['valuta'],
            'prj_kode' => $bpv['prj_kode'],
            'sit_kode' => $bpv['sit_kode'],
            'coa_kode' => $bpv['coa_holding_tax'],
            'ketin' => $bpv['ketin'],
            'uid' => $bpv['uid'],
            'requester' => $bpv['requester'],
            'total' => $bpv['holding_tax_val'],
            'deduction' => 0,
            'statuspulsa' => 0,
            'total_value' => $bpv['holding_tax_val'],
            'status_bpv_wht' => 1,
            'type' => $bpv['type']
        );

        $this->insert($insertdata);

        $theWht = $this->fetchRow("trano='$tranoWHT'")->toArray();

        $this->updateOldBPVWithWHT($trano,$tranoWHT);

        $logs['bpv-insert-wht-origin'] = $bpv;
        $logs['bpv-insert-wht-new'] = $theWht;
        $arrayLog = array (
            "trano" => $trano,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $bpv['prj_kode'],
            "sit_kode" => $bpv['sit_kode'],
            "action" => "INSERT-BPV-WHT",
            "data_before" => Zend_Json::encode($logs),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $log = new Admin_Models_Logtransaction();
        $log->insert($arrayLog);

        return $tranoWHT;
    }

    public function updateOldBPVWithWHT($trano='',$tranoWHT='')
    {
        $bpv = $this->fetchRow("trano = '$trano'");
        if (!$bpv || $bpv['trano_wht'] != '')
            return false;

        $this->update(array(
            "trano_wht" => $tranoWHT
        ),"trano='$trano'");

        return true;
    }

}