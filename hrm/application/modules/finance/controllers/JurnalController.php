<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bherly
 * Date: 03/28/12
 * Time: 9:32 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_JurnalController extends Zend_Controller_Action
{
    private $FINANCE;
    private $db;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $models = array(
            "MasterCoa",
            "MasterCoaBank",
            "AccountingCloseAR",
            "AccountingCloseAP",
            "AccountingJurnalBank",
            "AdjustingJournal"
        );

        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function getjurnalarAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $ref_number = $this->getRequest()->getParam("ref_number");

        $coaAR = $this->FINANCE->AccountingCloseAR->getjurnalar($trano,$ref_number);

        $return = array("success" => true, "data" => $coaAR);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getjurnalbankAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $ref_number = $this->getRequest()->getParam("ref_number");
        $type = $this->getRequest()->getParam("type");

        $coaAR = $this->FINANCE->AccountingJurnalBank->getjurnalbank($trano,$ref_number,$type);

        $return = array("success" => true, "data" => $coaAR);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }
}
?>