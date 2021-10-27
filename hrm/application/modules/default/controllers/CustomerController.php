<?php

class CustomerController extends Zend_Controller_Action
{

    private $db;
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');
    }

    public function indexAction()
    {
        // action body
    }

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'cus_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $customer = new Default_Models_MasterCustomer();
         
        $return['posts'] = $customer->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $return['count'] = $customer->fetchAll()->count();
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'cus_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS cus_kode,cus_nama FROM master_customer WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function getcustomerbyprojectAction()
    {
        $this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam('prj_kode');
    	$sql = "SELECT a.cus_kode,a.cus_nama FROM master_customer a LEFT JOIN master_project b ON b.prj_kode='" . $prjKode . "'";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetch();
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    
    }
}

?>