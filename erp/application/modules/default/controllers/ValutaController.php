<?php

class ValutaController extends Zend_Controller_Action
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
       
    }

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'val_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $customer = new Default_Models_MasterValuta();
         
        $return['posts'] = $customer->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $return['count'] = $customer->fetchAll()->count();
 
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);    

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
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'val_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS val_kode,val_nama FROM master_valuta WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
   
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function getexchangerateAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $val_kode = $request->getParam('val_kode');
        
        $val_kode2 = ($val_kode=='' || $val_kode==null) ? '' : " AND val_kode='$val_kode' "; 
            
    	$sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
                        FROM finance_exchange_rate
       			WHERE DATE(tgl)=CURDATE() $val_kode2
       			ORDER BY tgl DESC
       			LIMIT 0,1";
       
       $fetch = $this->db->query($sql);
       $data = $fetch->fetch();     
       
       $data['rateidr'] = abs($data['rateidr']);
       $return = array("success" => true,"rate" => $data['rateidr'],"tgl" => $data['tgl_rate']);
       
       Zend_Loader::loadClass('Zend_Json');
       $json = Zend_Json::encode($return);
   
       $this->getResponse()->setHeader('Content-Type', 'text/javascript');
       $this->getResponse()->setBody($json);
    }

}

?>