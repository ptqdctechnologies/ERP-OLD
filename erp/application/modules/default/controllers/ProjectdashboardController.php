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
        $this->workflowClass = new Admin_Models_Workflow();
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
        $role = new Admin_Models_Masterrole();
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
//                $account = $ldapdir->getAccount($val['uid']);
//                $pm[] = $account['displayname'][0];
                $pm[] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
            }
            if (strpos($val['display_name'],'Project Control') !== false)
            {
//                $account = $ldapdir->getAccount($val['uid']);
//                $pc[] = $account['displayname'][0];
                $pc[] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
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
        $role = new Admin_Models_Masterrole();
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
//                $account = $ldapdir->getAccount($val['uid']);
//                $pm[] = $account['displayname'][0];
                $pm[] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
            }
            if (strpos($val['display_name'],'Project Control') !== false)
            {
//                $account = $ldapdir->getAccount($val['uid']);
//                $pc[] = $account['displayname'][0];
                $pc[] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
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
    
    public function managementAction() {
//        $bod = array("redi", "jonhar", "budi.raharja", "anna");
      
//        $check = in_array($this->session->userName, $bod) ? false : true;
        $check = ($this->session->userName == 'redi') ? false : true;
        $this->view->check = $check;
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
    }
    
    public function approvereportAction()
    {
        $uid = $this->getRequest()->getParam("uid");
        $username = $this->getRequest()->getParam("username");
        $tgl1 = $this->getRequest()->getParam('tgl1');
        $tgl2 = $this->getRequest()->getParam('tgl2');
        $type = $this->getRequest()->getParam('type');
        
//        if($uid == 'jonhar'){
//            $username = 'A';
//        } else if($uid == 'hasrul'){
//            $username = 'B';
//        } else if($uid == 'kiki'){
//            $username = 'C';
//        } else if($uid == 'emir'){
//            $username = 'D';
//        }
        
        if($type == '' || is_null($type)){
            $type = 'Minute(s)';
        }
        
        if($type == 'Minute(s)'){
            $convert = 1;
        } else if ($type == 'Hour(s)'){
            $convert = 60;
        } else if ($type == 'Day(s)'){
            $convert = 1440;
        } 
        
        if ($tgl1 != ''){
            $tgl1mod = date("Y-m-d", strtotime($tgl1));
            $tgl1view = date("d M Y", strtotime($tgl1));
        } else {
            $tgl1mod = date("Y-m-d", strtotime("14-12-2010"));
            $tgl1view = date("d M Y", strtotime("14-12-2010"));
        } 
            
        if ($tgl2 != ''){
            $tgl2mod = date("Y-m-d", strtotime($tgl2));
            $tgl2view = date("d M Y", strtotime($tgl2));
        }
        else {
            $tgl2mod = date("Y-m-d", strtotime("now"));
            $tgl2view = date("d M Y", strtotime("now"));
        }
        
        $result = $this->workflowTrans->getDateRange($uid, $tgl1mod, $tgl2mod);
        $arrayminutes = array();
        $sample = false;
        
        foreach($result as $key => $val){
//            var_dump($val['item_id']);
//            var_dump($val['pre_date']);
//            var_dump($val['post_date']);
            $minutes = $this->checkWorkdays($val['pre_date'], $val['post_date']);
            $arrayminutes[] = $minutes;
        }
        
        $total = floor((array_sum($arrayminutes))/$convert);
        $average = floor((array_sum($arrayminutes)/count($arrayminutes))/$convert);
        $max = floor((max($arrayminutes))/$convert);
        $min = floor((min(array_filter($arrayminutes, function($v) {return $v > 0; })))/$convert);
        $stdev = floor(($this->stdev($arrayminutes, $sample))/$convert);
        
        $stringtotal = $this->dateMod(floor(array_sum($arrayminutes)));
        $stringaverage = $this->dateMod(floor(array_sum($arrayminutes)/count($arrayminutes)));
                        
        $tglcomb = $tgl1view." s.d. ".$tgl2view;
                
        $arraypass = array("username" => $username, "dates" => $tglcomb, "totalminutes" => $total, 
            "totaltrans" => count($arrayminutes), "average" => $average, "max" => $max, "min" => $min, 
            "stdev" => $stdev, "type" => $type, "stringttl" => $stringtotal, "stringavg" => $stringaverage);
        $this->view->array = $arraypass;
    }
    
    public function checkWorkdays($from, $to){
        $fromdate1 = new DateTime($from);
        $fromdate2 = new DateTime($from);
        $todate1 = new DateTime($to);
        $todate2 = new DateTime($to);
        
        $days = 0;
        $countdays = 0;
        $hours = $fromdate1->diff($todate1)->h;
        $minutes = $fromdate1->diff($todate1)->i;
        $checker = 0;
        
        //var_dump($from);
        //var_dump($to);
//        var_dump((int)$fromdate1->format(H));
//        var_dump((int)$fromdate1->format(i));
       
        if($fromdate2->format(H) > $todate2->format(H)){
            $fromdate2 = $fromdate2->setTime(0, 0);
            $checker = -1;
        }
        
        if($fromdate2->format(H) == $todate2->format(H) && $fromdate2->format(I) > $todate2->format(I)){
            $fromdate2 = $fromdate2->setTime(0, 0);
            $checker = -1;
        }
        
        if($fromdate2->format(H) == $todate2->format(H) && $fromdate2->format(I) == $todate2->format(I)
                && $fromdate2->format(S) > $todate2->format(S)){
            $fromdate2 = $fromdate2->setTime(0, 0);
            $checker = -1;
        }
        
        while($fromdate2->diff($todate2)->days > 0) {
            $fromdate2->format('N') < 6 ? $days++ : $days;
            $fromdate2 = $fromdate2->add(new DateInterval("P1D"));
        }
                
//        var_dump($minutes);
//        var_dump($hours);
//        var_dump($days);
//        var_dump($fromdate1->format('N'));
//        var_dump($todate1->format('N'));
        
        if($todate1->format('N') < 6){
            if($fromdate1->format('N') > 5){
//                var_dump("A");
                $totalminutes = (60 * 24 * $days) + ((int)$todate1->format(H) * 60) + (int)$todate1->format(i);
            } else {
                if($fromdate1->diff($todate1)->days == 0){
//                    var_dump("B");
                    $totalminutes = ($minutes + ($hours * 60));
                } else {
//                    var_dump("C");
                    $totalminutes = ($minutes + ($hours * 60) + (60 * 24 * ($days+$checker)));
                } 
            }
        } else {
            if($fromdate1->format('N') < 6){
//                var_dump("D");
                $totalminutes = (60 * 24 * ($days-1)) + (60 * 24 - 
                        (((int)$fromdate1->format(H) * 60) + (int)$fromdate1->format(i))) - 1;
            } else {                
                if($fromdate1->diff($todate1)->days <= 1){
//                    var_dump("E");
                    $totalminutes = 0;
                } else {
//                    var_dump("F");
                    $totalminutes = (60 * 24 * $days);
                }
                
            }
        }
//        var_dump($totalminutes);
        return $totalminutes;
    }
    
    public function dateMod($ivalue){
        $imod = fmod($ivalue, 60);
        
        $hvalue = ($ivalue - $imod)/60;
        $hmod = fmod($hvalue, 24);
        
        $dvalue = ($hvalue - $hmod)/24;
        $dmod = fmod($dvalue, 30);
        
        $mvalue = ($dvalue - $dmod)/30;
        $mmod = fmod($mvalue, 12);
        
        $ymod = ($mvalue - $mmod)/12;
        
        return $ymod." years, ".$mmod." months, ".$dmod." days, ".$hmod." hours, ".$imod." minutes";
    }
    
    public function stdev(array $a, $sample = false) {
        
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
           --$n;
        }
        
        return sqrt($carry / $n);
    }
    
}
?>