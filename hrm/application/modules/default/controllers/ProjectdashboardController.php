<?php
class ProjectdashboardController extends Zend_Controller_Action
{
    private $const;
	private $workflow;
	private $workflowClass;
	private $session;
	private $budget;
	private $quantity;
    private $db;
	private $workflowTrans;
	private $error;
    private $memcache;
    private $tempProgress;
    private $tempActivity;

	public function init()
	{
		$this->const = Zend_Registry::get('constant');
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
    	$this->workflow = $this->_helper->getHelper('workflow');
        $this->budget = new Default_Models_Budget();
		$this->quantity = $this->_helper->getHelper('quantity');
		$this->error = $this->_helper->getHelper('error');
		$this->session = new Zend_Session_Namespace('login');
        $this->work = new Default_Models_MasterWork();
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Model_Workflow();
        $this->memcache = Zend_Registry::get('Memcache');
        $this->tempProgress = new Default_Models_TempProgress();
        $this->tempActivity = new Default_Models_TempActivity();
	}

    public function indexAction()
    {

    }

    public function listprojectAction()
    {
		$this->_helper->viewRenderer->setNoRender();
        $uid = $this->session->userName;
//        $data = $this->workflowClass->getWorkflowProjectByUserID($uid);
        $project = new Default_Models_MasterProject();
        $data = $project->fetchAll(null,"Prj_Kode ASC");
        $result = array();
        if ($data)
        {
            $data = $data->toArray();
            foreach($data as $k => $v)
            {
                $result[] = array(
                    "text" => $v['Prj_Kode'],
                    "leaf" => false,
                    "children" => array()
                );
            }
        }
        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function tabAction()
    {
        $this->view->prjKode = $this->getRequest()->getParam("prj_kode");
    }
    
    public function progressAction()
    {
        $projek = new Default_Models_MasterProject();
        $role = new Admin_Model_Masterrole();
        $ldapdir = new Default_Models_Ldap();

        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $result = $this->tempProgress->getProgress($prjKode,$sitKode);
        $projeks = $projek->getProjectAndCustomer($prjKode);
        $roles = $role->getRoleByProject($prjKode);
        foreach($roles as $key => $val)
        {
            if (strpos($val['display_name'],'Project Manager') !== false)
            {
                $account = $ldapdir->getAccount($val['uid']);
                $pm[] = $account['displayname'][0];
            }
            if (strpos($val['display_name'],'Project Control') !== false)
            {
                $account = $ldapdir->getAccount($val['uid']);
                $pc[] = $account['displayname'][0];
            }
        }
        $this->view->pc = implode(',',$pc);
        $this->view->pm = implode(',',$pm);
        $this->view->data = $result;
        $this->view->prjKode = $prjKode;
        $this->view->prjNama = $projeks[0]['prj_nama'];
        $this->view->cusNama = $projeks[0]['cus_nama'];
    }

    public function activityAction()
    {
        $projek = new Default_Models_MasterProject();
        $role = new Admin_Model_Masterrole();
        $ldapdir = new Default_Models_Ldap();

        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $result = $this->tempActivity->getActivity($prjKode,$sitKode);
        $projeks = $projek->getProjectAndCustomer($prjKode);
        $roles = $role->getRoleByProject($prjKode);
        foreach($roles as $key => $val)
        {
            if (strpos($val['display_name'],'Project Manager') !== false)
            {
                $account = $ldapdir->getAccount($val['uid']);
                $pm[] = $account['displayname'][0];
            }
            if (strpos($val['display_name'],'Project Control') !== false)
            {
                $account = $ldapdir->getAccount($val['uid']);
                $pc[] = $account['displayname'][0];
            }
        }
        $this->view->pc = implode(',',$pc);
        $this->view->pm = implode(',',$pm);
        $this->view->data = $result;
        $this->view->prjKode = $prjKode;
        $this->view->prjNama = $projeks[0]['prj_nama'];
        $this->view->cusNama = $projeks[0]['cus_nama'];
    }

    public function cronprogressAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $util = $this->_helper->getHelper('utility');
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $forceUpdate = $this->getRequest()->getParam("force");
        $debug = $this->getRequest()->getParam("debug");

        if ($debug != true)
            $debug = false;
        if ($forceUpdate != true)
            $forceUpdate = false;
        $start = $util->microtime_float();
        $result = $this->tempProgress->getProgress($prjKode,$sitKode,$forceUpdate);
        $end = $util->microtime_float();
        // Print results. if debug = true
        if ($debug)
            var_dump($result);
        echo 'Script Execution Time: ' . round($end - $start, 3) . " seconds\n";
    }

    public function cronactivityAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $util = $this->_helper->getHelper('utility');
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $forceUpdate = $this->getRequest()->getParam("force");
        $debug = $this->getRequest()->getParam("debug");
        $uid = $this->getRequest()->getParam("uid");

        if ($debug != true)
            $debug = false;
        if ($forceUpdate != true)
            $forceUpdate = false;
        $start = $util->microtime_float();
        $result = $this->tempActivity->getActivity($prjKode,$sitKode,$forceUpdate);
        $end = $util->microtime_float();
        // Print results. if debug = true
        if ($debug)
            var_dump($result);
        echo 'Script Execution Time: ' . round($end - $start, 3) . " seconds\n";
    }

    public function showtransAction()
    {
        $uid = $this->getRequest()->getParam("uid");
        $type = $this->getRequest()->getParam("item_type");
        $prjKode = $this->getRequest()->getParam("prj_kode");

        $result = $this->tempActivity->getActivity($prjKode,'',false);
        $data = $result['data']['detailactivity'];
        $myData = array();
        $found = false;
        foreach($data as $key => $val)
        {
            if ($key == $uid)
            {
                foreach($val as $key2 => $val2)
                {
                    if ($key2 == $type)
                    {
                        $myData = $val2;
                        $found = true;
                        break;
                    }
                }
                if ($found)
                    break;
            }
        }
        $limit = 30;
        $current = $this->getRequest()->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->getRequest()->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;

        $offset = $current - 1;
        $jum = 0;
        $hasil = array();
        $totalResult = count($myData);
        foreach($myData as $key => $val)
        {
            if (intval($key) == intval($offset))
            {
                if ($jum != $limit)
                {
                    $hasil[] = $val;
                    $jum++;
                    $offset++;
                }
                else
                    break;
            }
        }
        $this->view->prjKode = $prjKode;
        $this->view->uid = $uid;
        $this->view->itemType = $type;
        $this->view->totalResult = $totalResult;
        $this->view->current = $current;
        $this->view->limit = $limit;
        $this->view->currentPage = $currentPage;
        $this->view->totalPage = ceil($totalResult / $limit);
        $this->view->result = $hasil;
    }
    
}
?>