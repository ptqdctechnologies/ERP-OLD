<?php

class Boq2Controller extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	private $db;
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');
    }
    
	public function indexAction() {
		// TODO Auto-generated Boq2Controller::indexAction() default action
	}
	
	public function listAction() {
		
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_boq2h ORDER BY $sort $dir LIMIT $offset,$limit";
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
	
	public function listaddAction() {
		
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_kboq2h ORDER BY $sort $dir LIMIT $offset,$limit";
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
	
	public function listbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $param1 = $request->getParam('param1');
        $param2 = $request->getParam('param2');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_boq2h WHERE prj_kode LIKE \'%' . $param1 . '%\' AND sit_nama LIKE \'%' . $param2 . '%\'   ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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
    
	public function listbyprjkodeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $param1 = $request->getParam('param1');
        
//        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
//        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
//        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
//        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_boq2h WHERE prj_kode = '$param1' ORDER BY tgl DESC";

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
    
	public function listbyprjkodeaddAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $param1 = $request->getParam('param1');
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_kboq2h WHERE prj_kode LIKE \'%' . $param1 . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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
    
	public function cekboq2existAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        
        
        $sql = "SELECT sit_kode,sit_nama FROM transengineer_boq2h WHERE prj_kode = '$prjKode' AND sit_kode ='$sitKode'";
        
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
    
    public function getcontractvalueAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();
    	
    	$prjKode = $request->getParam('prj_kode');
    	$sitKode = $request->getParam('sit_kode');
    	
    	$sql = "SELECT 
    			a.total,a.totalusd,COALESCE(b.adtotal,0) AS prev_totalidr,COALESCE(b.adtotalusd,0) AS prev_totalusd 
    			FROM transengineer_boq2h a LEFT JOIN  transengineer_kboq2h b ON a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode 
    			WHERE a.prj_kode = '$prjKode' AND a.sit_kode = '$sitKode'  
    			ORDER BY b.trano DESC,b.tgl DESC LIMIT 1";
    	
    	$fetch = $this->db->query($sql);
    	$result = $fetch->fetchAll();
    	Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    	
    }

    public function listprevaddcoAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $param1 = $request->getParam('param1');
        $param2 = $request->getParam('param2');

        $sql = "SELECT
    	        a.sit_kode, a.sit_nama,b.tgl, a.total, a.totalusd, COALESCE(b.adtotal,0) AS adtotal,COALESCE(b.adtotalusd,0) AS adtotalusd, COALESCE(b.totaltambah, a.total) AS totaltambah, COALESCE(b.totaltambahusd, a.totalusd) AS totaltambahusd, b.ket
    			FROM transengineer_boq2h a LEFT JOIN  transengineer_kboq2h b ON a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode
    			WHERE a.prj_kode = '$param1' AND a.sit_kode = '$param2'  
    			ORDER BY b.trano,b.tgl";

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

}

