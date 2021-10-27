<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/2/11
 * Time: 1:48 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Default_Models_UserNotification extends Zend_Db_Table_Abstract
{
    protected $_name = 'user_notification';

    protected $db;

    public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function __name()
    {
        return $this->_name;
    }

    public function getExist($uid,$item_type='')
    {
        $select = $this->db->select()
            ->from(array($this->_name))
            ->where("active = 1")
            ->where("uid = '$uid'");
        if ($item_type)
            $select = $select->where("item_type = '$item_type'");

        $cek = $this->db->fetchAll($select);
        if ($cek)
        {
            return true;
        }

        return false;

    }

    public function sendNotificationFinalApproval($item_type='',$trano='',$msg='',$print=0,$trano_print='',$submitterOnly=false,$uid='')
    {
        $wt = new Admin_Models_Workflowtrans();
        $notif = new Default_Models_Conversation();

        if (!$submitterOnly)
        {
//            $person = $wt->getPersonInWorkflow($trano);
//            if(count($person) > 0 && $uid == '')
//            {
//                foreach($person as $k => $v)
//                {
//                    $uid = $v;
//                    if ($this->getExist($uid,$item_type))
//                    {
//                        $notif->sendMessageFromSystem('SYSTEM',$uid,$msg,$trano,$print,$trano_print);
//                    }
//                }
//            }
//            else
//            {
            if ($uid != '')
                $notif->sendMessageFromSystem('SYSTEM',$uid,$msg,$trano,$print,$trano_print);
//            }
        }
        else
        {
            $submitter = $wt->getDocumentSubmitter($trano);
            if ($submitter)
            {
                $notif->sendMessageFromSystem('SYSTEM',$submitter['uid'],$msg,$trano,$print,$trano_print);
            }
        }
    }

    public function sendEmailNotificationFinalApproval($item_type='',$trano='',$prjKode='', $addMsg='', $tranoPrint='')
    {
        $select = $this->db->select()
            ->from(array($this->_name))
            ->where("item_type=?",$item_type)
//            ->where("(type_notification = 'EMAIL' OR type_notification = 'ALL')")
            ->where("(prj_kode=? OR prj_kode is null OR prj_kode = '')",$prjKode)
            ->group(array("uid"));

        $wt = new Admin_Models_Workflowtrans();
        $final = $wt->getFinalApproval($trano);
        if ($final)
        {
            $personFinal = QDC_User_Ldap::factory(array("uid" => $final['uid']))->getName();
            $tglFinal = date("d M Y H:i",strtotime($final['date']));
        }
        $data = $this->db->fetchAll($select);
        if ($data)
        {
            foreach($data as $k => $v)
            {
                if ($v['type_notification'] == 'EMAIL' || $v['type_notification'] == 'ALL')
                {
                    $url = QDC_Document_Model::factory(array(
                        "trano" => $trano,
                        "item_type" => $item_type,
                        "useHash" => true
                    ))->getApprovalForm();

                    if ($url==false)
                    {
                        continue;
                    }
                    $email = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getEmail();
                    $name = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();

                    $err = QDC_Adapter_Mail::factory(array(
                        "sender" => "qdcerp-no-reply@qdc.co.id",
                        "subject" => "QDC ERP Approval for " . $trano,
                        "recipient" => $email,
                        "msgText" => "Dear " . $name . "," . "\n\n" .
                            "Document with Trano : $trano, has been final Approved by $personFinal @ $tglFinal." . "\n\n" .
                            ($addMsg) ? $addMsg . "\n\n" : '' .
                            "Click or open in Browser : $url for transaction detail." . "\n\n\n\n" .
                            "Best Regards," . "\n\n" .
                            "IT Administrator",
                        "html" => "Dear " . $name . "," . "<br><br>" .
                            "Document with Trano : $trano, has been final Approved by $personFinal @ $tglFinal." . "<br>" .
                            ($addMsg) ? $addMsg . "<br>" : '' .
                            "Click <a href=\"$url\">Here</a> for transaction detail." . "<br><br>" .
                            "Best Regards," . "<br><br>" .
                            "IT Administrator",
                        "noAuth" => true,
                        "useHtml" => true,
                        "useTemplate" => false
                    ))->send();
                    if ($err != 1)
                    {
                        $msg = $html= "ERP Notification Email error, description : " . $err . " trano : " . $trano . ", emailrecv: " . $email . ", uidsender: " . QDC_User_Session::factory()->getCurrentUID();
                        $this->sendErrorEmail($msg,$html);
                    }
                }

                if ($v['type_notification'] == 'ALL' || $v['type_notification'] == 'ERP')
                {
                    if ($tranoPrint == '')
                        $tranoPrint = $trano;
                    $this->sendNotificationFinalApproval($item_type,$trano,"Document with trano : $trano has been Final Approved." . $addMsg,1,$tranoPrint,false,$v['uid']);
                }
            }
        }
    }

    protected  function sendErrorEmail($msg='',$html='')
    {
        QDC_Adapter_Mail::factory(array(
            "sender" => "qdcerp-no-reply@qdc.co.id",
            "subject" => "QDC ERP Notification Error",
            "recipient" => "bherly.novrandy@qdc.co.id",
            "msgText" => $msg,
            "html" => $html,
            "noAuth" => true,
            "useHtml" => true,
            "useTemplate" => false
        ))->send();
    }

}