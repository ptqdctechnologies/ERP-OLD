<?php

class Default_Models_Conversation extends Zend_Db_Table_Abstract
{
    protected $_name = 'conversation';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function sendMessageFromSystem($uidSender='',$uidReceiver='',$msg='',$trano='',$print=0,$trano_print='')
    {
        if (!$uidSender)
            $uidSender = 'SYSTEM';

        $arrayInsert = array(
            "id_reply" => 0,
            "workflow_item_id" => 0,
            "uid_sender" => $uidSender,
            "uid_receiver" => $uidReceiver,
            "message" => $msg,
            "date" => date('Y-m-d H:i:s'),
            "trano" => $trano,
            "prj_kode" => '',
            "print" => $print,
            "trano_print" => $trano_print,
        );

        $this->insert($arrayInsert);
    }
}
?>