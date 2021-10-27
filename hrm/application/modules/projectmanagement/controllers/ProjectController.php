<?php
class ProjectManagement_ProjectController extends Zend_Controller_Action
{
    private $db;
    private $session;
    private $project;
    private $site;
    
    
    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->project = new Default_Models_MasterProject();
        $this->site = new Default_Models_MasterSite();
    }
    
    public function showAction()
    {
    	
    }
    
    public function showsiteAction()
    {
    	
    }
    
    public function createAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$posts = $this->getRequest()->getParam('posts');
    	Zend_Loader::loadClass('Zend_Json');
    	$jsonData =  Zend_Json::decode($posts);
    	
    	$arrayInsert = array("Prj_kode" => $jsonData['prj_kode'],"Prj_nama" => $jsonData['prj_nama'],"cus_kode" => $jsonData['cus_kode'],"keter" => $jsonData['keter']);
    	
    	$lastID = $this->project->insert($arrayInsert);
    	$jsonData['id'] = $lastID;
    	$return = array('success' => true,'message' => 'Project has been created','posts' => $jsonData);
		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    	
    }

    public function createsiteAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$posts = $this->getRequest()->getParam('posts');
    	Zend_Loader::loadClass('Zend_Json');
    	$jsonData =  Zend_Json::decode($posts);
        $cek = $jsonData['stsoverhead'];
        if ($cek == "on"){
        $cek = str_replace("on", "Y", $cek);
        }
        else{
        $cek = "N";}
        $jsonData['stsoverhead'] = $cek;
    	$arrayInsert = array("sit_kode"=> $jsonData['sit_kode'],"sit_nama"=> $jsonData['sit_nama'],"prj_kode" => $jsonData['prj_kode'],"ket" => $jsonData['ket'], "stsoverhead" => $cek);

    	$lastID = $this->site->insert($arrayInsert);
    	$jsonData['id'] = $lastID;
    	$return = array('success' => true,'message' => 'Site has been created','posts' => $jsonData);
		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function doinsertbudgetperiodenonprojectAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$posts = $this->getRequest()->getParam('posts');
    	Zend_Loader::loadClass('Zend_Json');
    	$jsonData =  Zend_Json::decode($posts);
        $cek = $jsonData['stsoverhead'];
        if ($cek == "on"){
        $cek = str_replace("on", "Y", $cek);
        }
        else{
        $cek = "N";}
        $jsonData['stsoverhead'] = $cek;
    	$arrayInsert = array("sit_kode"=> $jsonData['sit_kode'],
                             "sit_nama"=> $jsonData['sit_nama'],
                             "prj_kode" => $jsonData['prj_kode'],
                             "ket" => $jsonData['ket'],
                             "stsoverhead" => $cek,
                             "type" => 'O'   );

    	$lastID = $this->site->insert($arrayInsert);
    	$jsonData['id'] = $lastID;
    	$return = array('success' => true,'message' => 'Site has been created','posts' => $jsonData);
		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }
}
?>
