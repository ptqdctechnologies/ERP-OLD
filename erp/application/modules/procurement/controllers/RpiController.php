<?php

class Procurement_RpiController extends Zend_Controller_Action
{
    private $DEFAULT;
    private $ADMIN;
    private $PROC;
    private $const;
    private $quantity;
    private $workflow;
    private $db;

    public function init()
    {
        $this->ADMIN = QDC_Model_Admin::init(array(
            "Workflowtrans"
        ));
        $this->DEFAULT = QDC_Model_Default::init(array(
            "MasterSite",
            "Budget",
            "MasterProject",
            "MasterWork",
            "MasterBarang",
            "Files",
            "MasterUser",
            "RequestPaymentInvoice",
            "RequestPaymentInvoiceH"
        ));
//        $this->PROC = QDC_Model_Procurement::init(array(
//        ));

        $this->db = Zend_Registry::get("db");
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->const = Zend_Registry::get('constant');
    }

    public function getDetailAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->_getParam("trano");
        $return = array();
        $return['success'] = false;
        $select = $this->db->select()
            ->from(array($this->DEFAULT->RequestPaymentInvoice->__name()))
            ->where("trano=?",$trano);

        $data = $this->db->fetchAll($select);

        if ($data)
        {
            $data = QDC_Document_Model::factory()->sanitize($data);
            $return['data'] = $data;
            $return['success'] = true;
        }

        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }



    public function getHeaderAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->_getParam("trano");
        $return = array();
        $return['success'] = false;
        $select = $this->db->select()
            ->from(array($this->DEFAULT->RequestPaymentInvoiceH->__name()))
            ->where("trano=?",$trano);

        $data = $this->db->fetchRow($select);

        if ($data)
        {
            $data = QDC_Document_Model::factory()->sanitize($data);
            $return['data'] = $data;
            $return['success'] = true;
        }

        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
?>