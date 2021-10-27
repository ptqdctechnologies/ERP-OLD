<?php

class ProjectManagement_ProjectController extends Zend_Controller_Action {

    private $db;
    private $session;
    private $project;
    private $site;

    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->project = new Default_Models_MasterProject();
        $this->site = new Default_Models_MasterSite();
    }

    public function showAction() {
        
    }

    public function showsiteAction() {
        
    }

    public function createAction() {
        $this->_helper->viewRenderer->setNoRender();
        $posts = $this->getRequest()->getParam('posts');
        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();
        
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($posts);

        $arrayInsert = array(
            "Prj_kode" => $jsonData['prj_kode'],
            "Prj_nama" => $jsonData['prj_nama'],
            "cus_kode" => $jsonData['cus_kode'],
            "keter" => $jsonData['keter'],
            "tgl" => date("Y-m-d H:i:s"),
            "uid" => $this->session->userName
        );

        $lastID = $this->project->insert($arrayInsert);
        $activityHead['master_project'][0]=$arrayInsert;
        
        $jsonData['id'] = $lastID;
        $return = array('success' => true, 'message' => 'Project has been created', 'posts' => $jsonData);
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        
        $activityLog = array(
            "menu_name" => "Create Project",
            "trano" => $lastID,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function createsiteAction() {
        $this->_helper->viewRenderer->setNoRender();
        $posts = $this->getRequest()->getParam('posts');
        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();
        
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($posts);
        $cek = $jsonData['stsoverhead'];
        if ($cek == "on") {
            $cek = str_replace("on", "Y", $cek);
        } else {
            $cek = "N";
        }
        $jsonData['stsoverhead'] = $cek;
        $arrayInsert = array(
            "sit_kode" => $jsonData['sit_kode'],
            "sit_nama" => $jsonData['sit_nama'],
            "prj_kode" => $jsonData['prj_kode'],
            "ket" => $jsonData['ket'],
            "stsoverhead" => $cek,
            "tgl" => date("Y-m-d H:i:s"),
            "uid" => $this->session->userName
        );

        $lastID = $this->site->insert($arrayInsert);
        $activityHead['master_site'][0]=$arrayInsert;
        
        $jsonData['id'] = $lastID;
        $return = array('success' => true, 'message' => 'Site has been created', 'posts' => $jsonData);
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        
         $activityLog = array(
            "menu_name" => "Create Site",
            "trano" => $lastID,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData['prj_kode'],
            "sit_kode" => $jsonData['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertbudgetperiodenonprojectAction() {
        $this->_helper->viewRenderer->setNoRender();
        $posts = $this->getRequest()->getParam('posts');
        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();
        
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($posts);
        $cek = $jsonData['stsoverhead'];
        if ($cek == "on") {
            $cek = str_replace("on", "Y", $cek);
        } else {
            $cek = "N";
        }
        $jsonData['stsoverhead'] = $cek;
        $arrayInsert = array("sit_kode" => $jsonData['sit_kode'],
            "sit_nama" => $jsonData['sit_nama'],
            "prj_kode" => $jsonData['prj_kode'],
            "ket" => $jsonData['ket'],
            "stsoverhead" => $cek,
            "tgl" => date("Y-m-d H:i:s"),
            "uid" => $this->session->userName,
            "type" => 'O');

        $lastID = $this->site->insert($arrayInsert);
          $activityHead['master_site'][0]=$arrayInsert;
        
        $jsonData['id'] = $lastID;
        $return = array('success' => true, 'message' => 'Site has been created', 'posts' => $jsonData);
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        
        
         $activityLog = array(
            "menu_name" => "Create Budget Periode Non Project",
            "trano" => $lastID,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData['prj_kode'],
            "sit_kode" => $jsonData['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}

?>
