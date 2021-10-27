<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Finance_MarkupController extends Zend_Controller_Action {

    private $session;
    private $request;
    private $json;
    private $const;
    private $token;
    private $utility;
    private $db;
    private $FINANCE;

    public function init() {
        Zend_Loader::loadClass('Zend_Json');
        $this->session = new Zend_Session_Namespace('login');
        $this->sessionID = Zend_Session::getId();
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');

        $this->utility = Zend_Controller_Action_HelperBroker::getStaticHelper('utility');

        $this->const = Zend_Registry::get('constant');
        $this->db = Zend_Registry::get('db');
        $this->request = $this->getRequest();
        $this->json = $this->request->getParam('data');

        $models = array(
            "MasterMarkup"
        );

        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function markupAction() {
        $this->view->userID = $this->session->idUser;
    }

//    $this->FINANCE->MasterMarkup->insert

    public function markuplistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data['data'] = $this->FINANCE->MasterMarkup->getmarkup();
        
        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addmarkupAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($this->json);
        $markup = $jsonData['markup_limit'];

        $uid = $this->session->userName;

        $insert = array(
            "markup_limit" => $markup,
            "uid" => $uid,
            "active" => 1
        );

        $this->FINANCE->MasterMarkup->insert($insert);

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatemarkupAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($this->json);
        $markup = $jsonData['markup_limit'];
        $id = $jsonData['id'];
        $uid = $this->session->userName;

        $insert = array(
            "markup_limit" => $markup,
            "uid" => $uid,
            "active" => 1
        );

        $this->FINANCE->MasterMarkup->update($insert, "id = $id");

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function deletemarkupAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($this->json);
        $id = $jsonData;
        $uid = $this->session->userName;

        $insert = array(
            "markup_limit" => $markup,
            "uid" => $uid,
            "active" => 1
        );

        $this->FINANCE->MasterMarkup->delete("id = $id");

        $return = array("success" => true);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}

?>
