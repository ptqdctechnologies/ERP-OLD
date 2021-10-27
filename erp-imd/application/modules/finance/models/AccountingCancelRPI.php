<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 5/1/12
 * Time: 1:47 PM
 * To change this template use File | Settings | File Templates.
 */
class Finance_Models_AccountingCancelRPI extends Zend_Db_Table_Abstract {

    protected $_name = 'finance_cancel_rpi';
    public $name;
    public $db;
    protected $const;
    protected $session;
    protected $workflowtrans;
    protected $workflow;
    protected $creTrans;
    protected $log;
    protected $rpi;
    protected $rpiH;
    protected $closeAP;
    protected $bpvD;
    protected $bpvH;
    protected $token;
    protected $_primary = 'trano';

    public function __construct() {
        parent::__construct($this->_option);
        $this->name = $this->_name;
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
        $this->session = new Zend_Session_Namespace('login');
        $this->workflowtrans = new Finance_Models_WorkflowTrans();

        $this->creTrans = new Admin_Model_CredentialTrans();
        $this->log = new Admin_Models_Logtransaction();

        $this->rpi = new Default_Models_RequestPaymentInvoice();
        $this->rpiH = new Default_Models_RequestPaymentInvoiceH();

        $this->closeAP = new Finance_Models_AccountingCloseAP();
        $this->bpvD = new Finance_Models_BankPaymentVoucherD();
        $this->bpvH = new Finance_Models_BankPaymentVoucher();
        
        
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
    }

    public function __name() {
        return $this->_name;
    }

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function docancelrpi($trano = '') {

        $request = $this->fetchRow("trano = '$trano'");
        $trano_rpi = $request['ref_number'];

        $myUid = $this->session->userName;

        $token = $this->token->getDocumentSignatureByUserID($myUid);
        $tgl = $token['date'];
        $sign = $token['signature'];
        $fetch = $this->workflowtrans->fetchAll("item_id = '$trano'");

        if ($fetch) {
            $hasil = $fetch->toArray();
            $prjKode = $hasil[0]['prj_kode'];
            Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($hasil);

            $arrayInsert = array(
                "trano" => $trano_rpi,
                "tgl" => $tgl,
                "prj_kode" => $prjKode,
                "uid" => $myUid,
                "uid_requestor" => $request['uid'],
                "sign" => $sign,
                "reason" => 'RPI CANCEL',
                "data" => $json,
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->creTrans->insert($arrayInsert);
            $this->workflowtrans->delete("item_id = '$trano_rpi'");
        }


        $log['rpi-detail-before'] = $this->rpi->fetchAll("trano = '$trano_rpi'")->toArray();
        $updateRPIdetail = array(
            "qty" => 0
        );
        $this->rpi->update($updateRPIdetail, "trano = '$trano_rpi'");
        $log2['rpi-detail-after'] = $this->rpi->fetchAll("trano = '$trano_rpi'")->toArray();

        $rpih = $this->rpiH->fetchAll("trano = '$trano_rpi'")->toArray();

        $log['rpi-header-before'] = $rpih;
        $updateRPIheader = array(
            "total" => 0,
            "gtotal" => 0,
            "jumlah" => 0,
            "ppn" => 0
        );
        $this->rpiH->update($updateRPIheader, "trano = '$trano_rpi'");
        $log2['rpi-header-after'] = $this->rpiH->fetchAll("trano = '$trano_rpi'")->toArray();


        // set jurnal balik ap
        $rpiAP = $this->closeAP->fetchAll("ref_number = '$trano_rpi'")->toArray();
        $log['rpi-ap-detail-before'] = $rpiAP;

        $debit = 0;
        $credit = 0;

        foreach ($rpiAP as $key => $v) {
            if ($v['debit'] != 0)
                $credit = $v['debit'];
            else
                $debit = $v['credit'];

            $insertJurnalBalikAP = array(
                "trano" => $v['trano'],
                "ref_number" => $v['ref_number'],
                "prj_kode" => $v['prj_kode'],
                "sit_kode" => $v['sit_kode'],
                "tgl" => date('Y-m-d H:i:s'),
                "uid" => $request['uid'],
                "coa_kode" => $v['coa_kode'],
                "coa_nama" => $v['coa_nama'],
                "debit" => floatval($debit),
                "credit" => floatval($credit),
                "stsclose" => $v['stsclose'],
                "uidclose" => $v['uidclose'],
                "val_kode" => $v['val_kode'],
                "rateidr" => $v['rateidr'],
                "tglclose" => $v['tglclose'],
                "stspost" => $v['stspost'],
                "uidpost" => $v['uidpost'],
                "job_number" => $v['job_number'],
                "memo" => "Reversing Entries",
                "ref_number_accounting" => $v['ref_number_accounting'],
                "tgl_ref_number_accounting" => $v['tgl_ref_number_accounting']
            );

            $this->closeAP->insert($insertJurnalBalikAP);

            $credit = 0;
            $debit = 0;
        }

        $log2['rpi-ap-detail-after'] = $this->closeAP->fetchAll("ref_number = '$trano_rpi'")->toArray();


        //bpv 0
        $log['rpi-bpv-detail-before'] = $this->bpvD->fetchAll("ref_number = '$trano_rpi'")->toArray();
        $updateRpiBpvdetail = array(
            "qty" => 0,
            "total" => 0
        );
        $this->bpvD->update($updateRpiBpvdetail, "ref_number = '$trano_rpi'");
        $log2['rpi-bpv-detail-after'] = $this->bpvD->fetchAll("ref_number = '$trano_rpi'")->toArray();

        $rpiBpvH = $this->bpvH->fetchAll("ref_number = '$trano_rpi'")->toArray();

        $log['rpi-bpv-header-before'] = $rpiBpvH;
        $updateRPIBPVheader = array(
            "total_value" => 0,
            "total_bayar" => 0,
            "total" => 0,
            "total2" => 0,
            "deduction" => 0,
            "holding_tax_val" => 0,
            "holding_tax" => 0,
            "valueppn" => 0
        );
        $this->bpvH->update($updateRPIBPVheader, "ref_number = '$trano_rpi'");
        $log2['rpi-bpv-header-after'] = $this->bpvH->fetchAll("ref_number = '$trano_rpi'")->toArray();

     
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

        $arrayLog = array(
            "trano" => $trano_rpi,
            "uid" => $request['uid'],
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $rpih['prj_kode'],
            "sit_kode" => $rpih['sit_kode'],
            "action" => "RPI CANCEL",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        $updateRequest = array(
            "stsproses" => 1
        );

        $this->update($updateRequest, "trano = '$trano'");

        $myUid = $this->session->userName;
        $ldap = new Default_Models_Ldap();
        $myName = QDC_User_Ldap::factory(array("uid" => $myUid))->getName();

        $conversation = new Default_Models_Conversation();
        $arrayInsert = array(
            "id_reply" => 0,
            "workflow_item_id" => 0,
            "uid_sender" => $myUid,
            "uid_receiver" => $request['uid'],
            "message" => "Your request for cancel RPI with number : $trano has been Approved by $myName. Use Edit RPI Transaction for Editing & Resubmit this RPI to Workflow.",
            "date" => date('Y-m-d H:i:s'),
            "trano" => $trano,
            "prj_kode" => ''
        );

        $conversation->insert($arrayInsert);

    }

}
