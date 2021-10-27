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

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function viewpaymentvoucher ($offset=0,$limit=0,$dir='ASC',$sort='trano',$type,$search)
    {
        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM finance_payment_voucher WHERE item_type = '$type' $search GROUP BY trano ORDER BY $sort $dir LIMIT $offset,$limit";
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

}
