<?php
class Procurement_PurchaseorderController extends Zend_Controller_Action
{
    private $db;
    private $log;
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->log = new Admin_Model_Logtransaction();
    }

    public function lastlogAction()
    {
        $trano = $this->getRequest()->getParam("trano");
        $cek = $this->log->fetchRow("trano = '$trano'",array("tgl DESC"),1,0);
        if ($cek)
        {
            $cek = $cek->toArray();
            $ldap = new Default_Models_Ldap();

            $dataBefore = Zend_Json::decode($cek['data_before']);
            $this->view->result = $dataBefore['po-detail-before'];
            $acc = $ldap->getAccount($cek['uid']);
            $cek['name'] = $acc['displayname'][0];
            $this->view->ket = $cek;
        }
    }
}
?>