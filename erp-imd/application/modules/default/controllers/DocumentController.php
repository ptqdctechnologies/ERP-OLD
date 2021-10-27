<?php

class DocumentController extends Zend_Controller_Action
{
    private $db;
    public function init()
    {
        $this->db = Zend_Registry::get("db");
    }

    public function getDocumentWorkflowIdAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $w = new Admin_Models_Workflowtrans();
        $trano = $this->_getParam("trano");

        $return['success'] = false;

        $select = $this->db->select()
            ->from(array($w->__name()))
            ->where("item_id=?",$trano)
            ->order(array("date DESC"));

        $subselect = $this->db->select()
            ->from(array("a" => $select))
            ->group(array("item_id"));

        $data = $this->db->fetchRow($subselect);
        if ($data)
        {
            $return["data"] = $data;
            $return['success'] = true;
        }
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}

?>