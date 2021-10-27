<?php

class ProjectController extends Zend_Controller_Action
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
        $showAll = $request->getParam('all');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';
        
        $project = new Default_Models_MasterProject();

        if ($listType == 'bycustomer')
        {
        	$return['posts'] = $project->getProjectAndCustomer('',$showAll);
        	$return['count'] = count($return['posts']);
        }
        else if($listType == 'overhead')
        {
            $return['posts'] = $project->getProjectOverhead($showAll,'',$sort,$dir);
        	$return['count'] = count($return['posts']);
        }
        else if($listType == 'project')
        {
            $return['posts'] = $project->getProjectNonOverhead($showAll,'',$sort,$dir);
            $return['count'] = count($return['posts']);
        }
        else
        {
            if ($showAll == '')
                $where = "stsclose=0";
	        $return['posts'] = $project->fetchAll($where, array($sort . ' ' . $dir), $limit, $offset)->toArray();
	        $return['count'] = $project->fetchAll($where)->count();
        }
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
        $showAll = $request->getParam('all');
        $listType = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';
        if ($showAll == '')
                $where = " AND stsclose=0";

        $project = new Default_Models_MasterProject();
        if($listType == 'overhead')
        {
            $return['posts'] = $project->getProjectOverhead($showAll,$columnName . ' LIKE \'%' . $columnValue . '%\' ');
        	$return['count'] = count($return['posts']);
        }
        else
        {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS Prj_Kode,Prj_Nama FROM master_project WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ' . $where . ' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

            $fetch = $this->db->query($sql);
            $return['posts'] = $fetch->fetchAll();
            $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        }
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function cekprojectexistAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        
        if ($sitKode == '')
        {
        	$sql = "SELECT Prj_Kode,Prj_Nama FROM master_project WHERE prj_kode = '$prjKode'";
        }
        else
        {
        	$sql = "SELECT sit_kode,sit_nama FROM master_site WHERE prj_kode = '$prjKode' AND sit_kode='$sitKode'";
        }
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();
        if (count($return) > 0 && isset($return))
        {
        	$result = "{success:true}";
        }
        else
        {
        	$result = "{success:false}";
        }
        echo $result;
    }

    public function cekperiodexistAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $sql = "SELECT sit_kode,sit_nama FROM master_period WHERE dep_kode = '$prjKode' AND perkode='$sitKode'";

        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();
        if (count($return) > 0 && isset($return))
        {
        	$result = "{success:true}";
        }
        else
        {
        	$result = "{success:false}";
        }
        echo $result;
    }
}
?>