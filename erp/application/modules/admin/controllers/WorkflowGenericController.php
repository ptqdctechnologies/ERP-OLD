<?php

class Admin_WorkflowGenericController extends Zend_Controller_Action
{
    private $ADMIN;
    private $workflow;
    private $db;

    public function init()
    {
        $this->ADMIN = QDC_Model_Admin::init(array(
            "Workflowgeneric",
            "Workflowtrans"
        ));

        $this->db = Zend_Registry::get("db");
        $this->workflow = $this->_helper->getHelper('workflow');
    }

    public function overrideAction()
    {

    }

}

?>