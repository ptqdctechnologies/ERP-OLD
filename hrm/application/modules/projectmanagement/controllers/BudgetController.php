<?php
class ProjectManagement_BudgetController extends Zend_Controller_Action
{
    private $db;
    private $session;
    private $upload;
    private $phpexcel;
    private $projectHelper;
    private $util;
    private $barang;
    private $project;
    private $boq3;
    private $boq3H;
    private $cboq3;
    private $cboq3H;
    private $tempBOQ3;
    private $tempBOQ3h;
    private $trans;
    private $workflowTrans;
    private $workflow;
    private $const;
    private $error;
    private $budget;
    private $praco;
    private $files;
    private $budgetd;
    private $budgeth;
    private $projectTime;
    private $memcache;
    private $regisco;
    private $conversation;
    private $log_trans;

    //gantt
    private $memcacheGantt;
    private $gantt;
    private $ganttd;
    private $depend;
    private $tempGantt;
    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->upload = $this->_helper->getHelper('uploadfile');
        
//		$this->phpexcel = Zend_Registry::get('phpexcel');
//        
//		include_once($this->phpexcel."PHPExcel.php");
//		require_once $this->phpexcel.'PHPExcel/IOFactory.php';
        $this->phpexcel = $this->_helper->getHelper('phpexcel');
        $this->projectHelper = $this->_helper->getHelper('project');
        $this->memcache = Zend_Registry::get('Memcache');
        $this->memcacheGantt = Zend_Registry::get('MemcacheGantt');
		$this->error = $this->_helper->getHelper('error');
        $this->const = Zend_Registry::get('constant');
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');

        $this->trans = $this->_helper->getHelper('transaction');
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->boq3 = new Default_Models_MasterBoq3();
        $this->boq3H = new Default_Models_MasterBoq3H();
        $this->cboq3 = new Default_Models_MasterCboq3();
        $this->cboq3H = new Default_Models_MasterCboq3H();

        $this->workflowTrans = new Admin_Models_Workflowtrans();
		$this->tempBOQ3 = new ProjectManagement_Models_TemporaryBOQ3();
		$this->tempBOQ3h = new ProjectManagement_Models_TemporaryBOQ3h();
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->budget = new Default_Models_Budget();
        $this->projectTime = new ProjectManagement_Models_ProjectTime();

        $this->praco = new Default_Models_Praco();
        $this->files = new Default_Models_Files();
        $this->log_trans = new Procurement_Model_Logtransaction();

        $this->gantt = new Extjs4_Models_Gantt();
        $this->ganttd = new Extjs4_Models_Ganttd();
        $this->depend = new Extjs4_Models_Dependency();
        $this->regisco = new Sales_Models_Regisco();
        $this->conversation = new Default_Models_Conversation();
        $this->tempGantt = new Extjs4_Models_TempGantt();
    }

    public function showcreateboq3Action()
    {
    	
    }

    public function pracoAction()
    {

    }

    public function addpracoAction()
    {

    }

    public function apppracoAction()
    {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $view = $this->getRequest()->getParam("view");
     
   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/PRABOQ2';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $Jsondeletefile = $this->getRequest()->getParam("deletefile");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	       	$jsonData2 = Zend_Json::decode($etc);
             $file = Zend_Json::decode($files);

           $jsonData2[0]['tgl'] = date('Y-m-d H:i:s');
           $jsonData2[0]['uid'] = $this->session->userName;

	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonEtc = $etc;
            $this->view->jsonFile = $files;
	       	$this->view->file = $file;
            $this->view->jsonDeleteFile = $Jsondeletefile;

            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}
   		}
   		else
   		{
           $docID = $this->getRequest()->getParam("doc_id");
           if($docID != '')
           {
               $getDocument = $this->workflow->getDocumentLastStatusAll($docID);
               $approve = $getDocument['workflow_trans_id'];
          }
        
   			$docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

   			if ($docs)
   			{
   				$user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
   				if ($user)
   				{
	   				$id = $docs['workflow_trans_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;

		   			    $praco = $this->praco->fetchRow("trano = '$approve'");
                        $file = $this->files->fetchAll("trano = '$approve'");

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['trano'] = $praco['trano'];
                        $jsonData2[0]['cus_kode'] = $praco['cus_kode'];
		   				$jsonData2[0]['cus_nama'] = $praco['cus_nama'];
		   				$jsonData2[0]['mgr_kode'] = $praco['mgr_kode'];
		   				$jsonData2[0]['mgr_nama'] = $praco['mgr_nama'];
		   				$jsonData2[0]['ket'] = $praco['ket'];
				        $jsonData2[0]['po_cus'] = $praco['pocustomer'];
				        $jsonData2[0]['total_idr'] =$praco['total'];
                        $jsonData2[0]['total_usd'] = $praco['totalusd'];
                        $jsonData2[0]['source'] = $praco['sumberdoc'];
                        $jsonData2[0]['pm'] = $praco['assignto'];
                        $jsonData2[0]['message'] = $praco['message'];
                         
                        if ($view == 'true')
                        {
                            $this->view->view = true;
                        }

				        $this->view->etc = $jsonData2;

                        $this->view->file = $file;
		   				$allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
		   				$this->view->approve = true;
                       $this->view->trano = $approve;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;

	   			}
	   			else
	   			{
	   				$this->view->approve = false;
	   			}
	   		}
   			else
   			{
   				$this->view->approve = false;
   			}
   		}
    }

    public function insertpracoAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
       $file = $this->getRequest()->getParam('file');
   	   $etc = str_replace("\\","",$etc);
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
       $jsonFile = Zend_Json::decode($file);

       $counter = new Default_Models_MasterCounter();
	    $lastTrans = $counter->getLastTrans('PRABOQ2');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'PRABOQ2-' . $last;
      
       $items = $jsonData2[0];
       $items['uid_dest'] = $jsonData2[0]['mgr_kode'];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'PRABOQ2','',$this->const['DOCUMENT_SUBMIT'],$items, '',true);

       $this->getResponse()->setHeader('Content-Type', 'text/javascript');
       if (is_numeric($result))
       {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
       }
       elseif (is_array($result) && count($result) > 0)
       {

           $hasil = Zend_Json::encode($result);
           $this->getResponse()->setBody("{success: true, user:$hasil}");
           return false;
       }
       
        $counter->update(array("urut" => $last),"id=".$lastTrans['id']);
       
        	$arrayInsert = array (
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "ket" => $jsonData2[0]['ket'],
                "cus_kode" => $jsonData2[0]['cus_kode'],
                "cus_nama" => $jsonData2[0]['cus_nama'],
                "mgr_kode" => $jsonData2[0]['mgr_kode'],
                "mgr_nama" => $jsonData2[0]['mgr_nama'],
                "total" => $jsonData2[0]['total_idr'],
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
                "totalusd" => $jsonData2[0]['total_usd'],
                "pocustomer" => $jsonData2[0]['po_cus'],
				"statusfinal" => 'N',
				"sumberdoc" => $jsonData2[0]['source']
            );

            $this->praco->insert($arrayInsert);

            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
//                        "prj_kode" => $prjKode,
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => $this->session->userName,
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    $this->files->insert($arrayInsert);
                }
            }

	        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updatepracoAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');

       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
       $file = $this->getRequest()->getParam('file');

      $jsonFile = Zend_Json::decode($file);

       $trano = $jsonData2[0]['trano'];

       $items = $jsonData2[0];
       $items['uid_dest'] = $jsonData2[0]['mgr_kode'];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'PRABOQ2','',$this->const['DOCUMENT_RESUBMIT'],$items, '',true);
        
       $this->getResponse()->setHeader('Content-Type', 'text/javascript');
       if (is_numeric($result))
       {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
       }
       elseif (is_array($result) && count($result) > 0)
       {

           $hasil = Zend_Json::encode($result);
           $this->getResponse()->setBody("{success: true, user:$hasil}");
           return false;
       }
        	$arrayInsert = array (
//                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "ket" => $jsonData2[0]['ket'],
//                "cus_kode" => $jsonData2[0]['cus_kode'],
//                "cus_nama" => $jsonData2[0]['cus_nama'],
                "mgr_kode" => $jsonData2[0]['mgr_kode'],
                "mgr_nama" => $jsonData2[0]['mgr_nama'],
                "total" => $jsonData2[0]['total_idr'],
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
                "totalusd" => $jsonData2[0]['total_usd'],
                "pocustomer" => $jsonData2[0]['po_cus'],
//				"statusfinal" => 'N',
				"sumberdoc" => $jsonData2[0]['source']
            );

            $this->praco->update($arrayInsert,"trano='$trano'");
//            $this->supplier->insert($arrayInsert);

            $this->files->delete("trano='$trano'");
            if (count($jsonFile) > 0)
            {

                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
//                        "prj_kode" => $prjKode,
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => $this->session->userName,
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    $this->files->insert($arrayInsert);
                }
            }
            $this->getResponse()->setBody("{success: true , number : '$trano'}");
    }

    public function editpracoAction()
    {
        $trano = $this->getRequest()->getParam("trano");
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

   		$praco = $this->praco->fetchRow("trano = '$trano'")->toArray();

                $this->view->trano = $trano;
                $this->view->cusKode = $praco['cus_kode'];
                $this->view->cusNama = $praco['cus_nama'];
                $this->view->mgrKode = $praco['mgr_kode'];
                $this->view->mgrNama = $praco['mgr_nama'];
                $this->view->ket = $praco['ket'];
                $this->view->totalIDR = $praco['total'];
                $this->view->totalUSD = $praco['totalusd'];
                $this->view->poCus = $praco['pocustomer'];
                $this->view->source = $praco['sumberdoc'];
                

            Zend_Loader::loadClass('Zend_Json');
   			$file = Zend_Json::encode($file);
	       	$this->view->file = $file;
    }

    public function allpracoAction()
    {

    }

    public function createboq3Action()
    {
    	$this->view->uid = $this->session->userName;
    }
    public function createbudgetnonprojectAction()
    {
    	$this->view->uid = $this->session->userName;
    }

    public function uploadboq3Action()
    {
    	$this->_helper->viewRenderer->setNoRender();
	
		$result = $this->upload->uploadFile($_FILES,'file-path'); //process file untuk material/service
//        $resultOH = $this->upload->uploadFile($_FILES,'file-path-oh'); //process file untuk overhead

        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
//		if ($result && $resultOH)
		if ($result)
		{
			Zend_Loader::loadClass('Zend_Json');
			$boq3 = $this->phpexcel->readBOQ3FilePerRow($result['save_file'],$result['id_file']);

            $workid = array();
            foreach($boq3 as $k => $v)
            {
                $boq3[$k]['invalid'] = false;
                if ($v['cfs_kode'] == '')
                    $boq3[$k]['invalid'] = true;
                $tmp = $v['workid'] . " - " .  $v['workname'];
                $days = intval($v['days']);
                $workid[$tmp] += $days;
            }

            $tmp = array();
            $depend = array();
            $id = 100;
            $jum = 1;
            $idParent = 1;
            $totalDays = 0;
            $max = 0;
            foreach($workid as $k => $v)
            {
                if ($v <= 0)
                    $v = 1;
                $now = new DateTime('now');
                if ($v > $max)
                    $max = $v;
                $now->add(new DateInterval("P" . $v . "D"));
                $tmp[] = array(
                    "Name" => $k,
//                    "BaselineStartDate" => date("Y-m-d"),
//                    "BaselineEndDate" => $now->format("Y-m-d"),
                    "StartDate" => date("Y-m-d"),
                    "EndDate" => $now->format("Y-m-d"),
                    "ParentId" => $idParent,
                    "Id" => $id,
                    "Priority" => 1,
                    "Responsible" => "",
                    "PercentDone" => 0,
                    "leaf" => true
                );
//                if ($jum > 1)
//                {
//                    $depend[] = array(
//                        "From" => $id-1,
//                        "To" => $id,
//                        "Type" => 2
//                    );
//                }
//                $totalDays += $v;
                $jum++;
                $id++;
            }
            $now = new DateTime('now');
            $now->add(new DateInterval("P" . $max . "D"));
            $parent[] = array(
                "Name" => "Planning for $prjKode - $sitKode",
//                "BaselineStartDate" => date("Y-m-d"),
//                "BaselineEndDate" => $now->format("Y-m-d"),
                "StartDate" => date("Y-m-d"),
                "EndDate" => $now->format("Y-m-d"),
                "Id" => $idParent,
                "Priority" => 1,
                "Responsible" => "",
                "PercentDone" => 100,
                "expanded" => true,
                "children" => $tmp
            );
//            $boq3OH = $this->phpexcel->readBOQ3FilePerRow($resultOH['save_file'],$resultOH['id_file']);
            $json =  Zend_Json::encode($boq3);
            $cacheID = "GANTT_" . $this->session->userName . "_" . $prjKode . "_" . $sitKode;
            $jsons = array(
                "task" => Zend_Json::encode($parent),
                "depend" => Zend_Json::encode($depend)
            );

//            $this->memcacheGantt->save($jsons,$cacheID);
            $this->tempGantt->save($jsons,$cacheID);
            
            $fields = array(array("name" => "id"),array("name" => "workid","type" => "string"),array("name" => "workname"),array("name" => "nama_brg"),array("name" => "kode_brg","type" => "string"),array( "name" => "qty","type" => "float"),array( "name" => "harga", "type" => "double"),array( "name" => "val_kode"),array( "name" => "total", "type" => "double"),array( "name" => "rateidr","type" => "int"),array("name" => "id_file"),array("name" => "stspmeal"),array("name" => "cfs_kode"),array("name" => "cfs_nama"),array("name" => "days"),array("name" => "invalid"));
			$fields =  Zend_Json::encode($fields);
            if (file_exists($result['save_file']))
			{
				unlink($result['save_file']);
			}
//            if (file_exists($resultOH['save_file']))
//			{
//				unlink($resultOH['save_file']);
//			}
//			echo "{success:true}";
//			echo "{success:true, file:\"" . $result['origin_name'] . "\", newfile:\"" . $result['origin_name'] . "\",RESULT:{\"posts\" : $json,\"postsOH\" : $jsonOH,fields:$fields }}";
//            echo "{success:true, file:\"" . $result['origin_name'] . "\", newfile:\"" . $result['origin_name'] . "\",RESULT:{\"posts\" : $json,fields:$fields }, WORKID:{\"posts\": $jsonWork,\"depend\": $jsonDepend, fields: $fields}}";
            echo "{success:true, file:\"" . $result['origin_name'] . "\", newfile:\"" . $result['origin_name'] . "\",RESULT:{\"posts\" : $json,fields:$fields }}";
		}
		else
			echo "{success:false}";	
    }

     public function uploadbudgetAction()
    {
    	$this->_helper->viewRenderer->setNoRender();

		$result = $this->upload->uploadFile($_FILES,'file-path');

		if ($result)
		{
			Zend_Loader::loadClass('Zend_Json');
			$boq3 = $this->phpexcel->readBudgetFilePerRow($result['save_file'],$result['id_file']);
            $json =  Zend_Json::encode($boq3);
			$fields = array(array("name" => "id","type" => "string"),array("name" => "budgetid","type" => "string"),array("name" => "budgetname"),array( "name" => "val_kode"),array( "name" => "total", "type" => "double"),array("name" => "id_file"),array("name" => "coa_kode"),array("name" => "coa_nama"));
     
			$fields =  Zend_Json::encode($fields);
			if (file_exists($result['save_file']))
			{
				unlink($result['save_file']);
			}
//			echo "{success:true}";
			echo "{success:true, file:\"" . $result['origin_name'] . "\", newfile:\"" . $result['origin_name'] . "\",RESULT:{\"posts\" : $json,fields:$fields }}";
		}
		else
			echo "{success:false}";
    }
    
    public function submitboq3Action()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	Zend_Loader::loadClass('Zend_Json');
    	$json = $this->getRequest()->getParam("posts");
    	$rate = $this->getRequest()->getParam("rateidr");
    	$ket = $this->getRequest()->getParam("ket");
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
        $cacheID = "GANTT_" . $this->session->userName . "_" . $prjKode . "_" . $sitKode;

//        if ($this->memcache->test($cacheID))
//        {
//            $gantt = $this->memcache->load($cacheID);
//            $task = Zend_Json::decode($gantt['task']);
//            $depend = Zend_Json::decode($gantt['depend']);
//            var_dump($task);var_dump($depend);
//            die;
//        }
        $tgl_awal = date('Y-m-d');
        $tgl_akhir = date('Y-m-d',strtotime($this->getRequest()->getParam("end_date")));

    	$uid = $this->getRequest()->getParam("uid");
		$boq3 =  Zend_Json::decode($json);
		$rateidr =  Zend_Json::decode($rate);
//		$ket =  Zend_Json::decode($ket);
		
		$tempBOQ3 = new ProjectManagement_Models_TemporaryBOQ3();
		$tempBOQ3h = new ProjectManagement_Models_TemporaryBOQ3h();
        $BOQ3h = new ProjectManagement_Models_BOQ3h();
		$BOQ3 = new ProjectManagement_Models_BOQ3();
		$counter = new Default_Models_MasterCounter();

        $cek = $BOQ3h->fetchRow("prj_kode ='$prjKode' AND sit_kode = '$sitKode'");
        $cek2 = $tempBOQ3h->fetchRow("prj_kode ='$prjKode' AND sit_kode = '$sitKode'");
        if ($cek || $cek2)
        {
            echo "{success:false, msg:\"Budget for project $prjKode and site $sitKode is exist!\"}";
            return false;
        }

        $lastTrans = $counter->getLastTrans('PRABOQ3');
		$lastTrans['urut'] = $lastTrans['urut'] + 1;
		$trano = 'PRABOQ3-' . $lastTrans['urut'];

        $items = array(
            "prj_kode" => $prjKode,
            "sit_kode" => $sitKode
        );


        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $result = $this->workflow->setWorkflowTrans($trano,'PRABOQ3','',$this->const['DOCUMENT_SUBMIT'],$items);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result))
        {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }
        elseif (is_array($result) && count($result) > 0)
        {

            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }

        $insert = array(
            "trano" => $trano,
            "prj_kode" => $prjKode,
            "sit_kode" => $sitKode,
            "start_date" => $tgl_awal,
            "end_date" => $tgl_akhir,
            "uid" => $this->session->userName
        );
        $this->projectTime->insert($insert);

        $count = 0;
		$jumMasuk = count($boq3);

		$jumIDR = 0;
		$jumUSD = 0;
		
		foreach ($boq3 as $key => $val)
		{
			unset($boq3[$key]['id_file']);
			$boq3[$key]['trano'] = $trano;
			$boq3[$key]['urut'] = $boq3[$key]['id'];
			unset($boq3[$key]['id']);

			$boq3[$key]['uid'] = $this->session->userName;
			$boq3[$key]['tgl'] = date('Y-m-d');
//			$boq3[$key]['id_user'] = $this->session->idUser;
			$boq3[$key]['prj_kode'] = $prjKode;
			$prjNama = $this->projectHelper->getProjectDetail($prjKode);
			$boq3[$key]['prj_nama'] = $prjNama['Prj_Nama'];
			if ($sitKode != '')
			{	
				$boq3[$key]['sit_kode'] = $sitKode;
				$sitNama = $this->projectHelper->getSiteDetail($prjKode,$sitKode);			
				$boq3[$key]['sit_nama'] = $sitNama['sit_nama'];
			}
			if($boq3[$key]['val_kode'] == 'IDR')
			{
				$jumIDR += $boq3[$key]['qty'] * $boq3[$key]['harga'];
			}
			else
			{
				$boq3[$key]['rateidr'] = $rateidr . '.00';
				$jumUSD += $boq3[$key]['qty'] * $boq3[$key]['harga'];
			}
			$insertID = $tempBOQ3->insert($boq3[$key]);
			if ($insertID)
				$count++;
		}
		

		$customer = new Default_Models_MasterCustomer();
		
		$cusKode = $customer->getCustomerFromProject($prjKode);
		
		$cusKode = $cusKode['cus_kode'];
		
		$insertArray = array(
						"trano" => $trano,
						"tgl" => date('Y-m-d'),
						"tglinput" => date('Y-m-d'),
						"jam" => date('H:i:s'),
						"prj_kode" => $prjKode,
						"prj_nama" => $prjNama['Prj_Nama'],
						"sit_kode" => $sitKode,
						"sit_nama" => $sitNama['sit_nama'],
						"cus_kode" => $cusKode,
						"ket" => $ket,
						"rateidr" => $rateidr,
						"cus_kode" => $cusKode,
						"total" => $jumIDR,
						"totalusd" => $jumUSD,
						"uid" => $this->session->userName,
        );
		
//		$result = $BOQ3h->insert($insertArray);
		$result = $tempBOQ3h->insert($insertArray);

//        if ($this->memcacheGantt->test($cacheID))
        if ($this->tempGantt->test($cacheID))
        {
//            $gantt = $this->memcacheGantt->load($cacheID);
            $gantt = $this->tempGantt->load($cacheID);
            $task = Zend_Json::decode($gantt['task']);
            $depend = Zend_Json::decode($gantt['depend']);

            foreach($task as $k => $v)
            {
                $arrayInsert = array(
                    "prj_kode" => $prjKode,
                    "sit_kode" => $sitKode,
                    "start_date" => $v['StartDate'],
                    "end_date" => $v['EndDate'],
                    "name" => $v['Name'],
                    "gantt_id" => $v['Id'],
                    "uid" => $this->session->userName,
                    "percent_done" => 0,
                    "tgl" => date("Y-m-d H:i:s"),
                    "boq_no" => $trano
                );

                $this->gantt->insert($arrayInsert);
                if ($v['children'] != '')
                {
                    foreach($v['children'] as $k2 => $v2)
                    {
                        $split = explode(" - ",$v2['Name']);
                        if ($v2['ParentId'] != '')
                            $parentID = $v2['ParentId'];
                        elseif($v2['parentId'] != '')
                            $parentID = $v2['parentId'];
                        
                        $arrayInsert = array(
                            "prj_kode" => $prjKode,
                            "sit_kode" => $sitKode,
                            "start_date" => $v2['StartDate'],
                            "end_date" => $v2['EndDate'],
                            "workid" => $split[0],
                            "workname" => $split[1],
                            "name" => $v2['Name'],
                            "gantt_id" => $v2['Id'],
                            "parent_id" => $parentID,
                            "uid" => $this->session->userName,
                            "percent_done" => 0,
                            "tgl" => date("Y-m-d H:i:s"),
                            "boq_no" => $trano
                        );

                        $this->ganttd->insert($arrayInsert);
                    }
                }
            }

            foreach($depend as $k => $v)
            {
                $arrayInsert = array(
                    "prj_kode" => $prjKode,
                    "sit_kode" => $sitKode,
                    "from" => $v['From'],
                    "to" => $v['To'],
                    "type" => $v['Type'],
                    "uid" => $this->session->userName,
                    "tgl" => date("Y-m-d H:i:s"),
                    "boq_no" => $trano
                );

                $this->depend->insert($arrayInsert);
            }
            $this->tempGantt->delete("cache_id = '$cacheID'");
        }

		$counter->update(array("urut" => $lastTrans['urut']),"id=".$lastTrans['id']);
		
		if (count($boq3) == 0)
			echo "{success:false, msg:\"Error in Database\"}";
		else
			echo "{success:true, count:$count, of:$jumMasuk, number:'$trano'}";	
    }

    public function submitbudgetAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	Zend_Loader::loadClass('Zend_Json');
    	$json = $this->getRequest()->getParam("posts");
    	$rate = $this->getRequest()->getParam("rateidr");
    	$ket = $this->getRequest()->getParam("ket");
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");

    	$uid = $this->getRequest()->getParam("uid");
		$boq3 =  Zend_Json::decode($json);
		$rateidr =  Zend_Json::decode($rate);
		$ket =  Zend_Json::decode($ket);

		$tempBOQ3 = new ProjectManagement_Models_TemporaryBOQ3();
		$tempBOQ3h = new ProjectManagement_Models_TemporaryBOQ3h();
        $BOQ3h = new ProjectManagement_Models_BOQ3h();
		$BOQ3 = new ProjectManagement_Models_BOQ3();
		$counter = new Default_Models_MasterCounter();

//        $budgeth = new ProjectManagement_Models_NonProjectBOQ3h();
//        $budgetd = new ProjectManagement_Models_NonProjectBOQ3();

        $budgeth = new ProjectManagement_Models_TemporaryOverHeadBOQ3h();
        $budgetd = new ProjectManagement_Models_TemporaryOverHeadBOQ3d();

//        $cek = $BOQ3h->fetchRow("prj_kode ='$prjKode' AND sit_kode = '$sitKode'");
//        $cek2 = $tempBOQ3h->fetchRow("prj_kode ='$prjKode' AND sit_kode = '$sitKode'");
//        if ($cek || $cek2)
//        {
//            echo "{success:false, message:\"Budget for project $prjKode and site $sitKode is exist!\"}";
//            return false;
//        }

        $lastTrans = $counter->getLastTrans('PRABGO');
		$lastTrans['urut'] = $lastTrans['urut'] + 1;
		$trano = 'PRABGO-' . $lastTrans['urut'];


        /* cek workflow */
        $items = array(
            "prj_kode" => $prjKode,
//            "sit_kode" => $sitKode
        );
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $result = $this->workflow->setWorkflowTrans($trano,'PRABGO','',$this->const['DOCUMENT_SUBMIT'],$items,'',false);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result))
        {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }
        elseif (is_array($result) && count($result) > 0)
        {

            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }
        /* end cek workflow */


        $count = 0;
		$jumMasuk = count($boq3);

		$jumIDR = 0;
		$jumUSD = 0;
        $jumlah = 0;


		foreach ($boq3 as $key => $val)
		{
			unset($boq3[$key]['id_file']);
			$boq3[$key]['trano'] = $trano;
			$boq3[$key]['urut'] = $boq3[$key]['id'];
			unset($boq3[$key]['id']);

			$boq3[$key]['petugas'] = $this->session->userName;
			$boq3[$key]['tgl'] = date('Y-m-d');
//			$boq3[$key]['id_user'] = $this->session->idUser;
            $boq3[$key]['prj_kode'] = $prjKode;
			$prjNama = $this->projectHelper->getProjectDetail($prjKode);
			$boq3[$key]['prj_nama'] = $prjNama['Prj_Nama'];
			if ($sitKode != '')
			{
				$boq3[$key]['sit_kode'] = $sitKode;
				$sitNama = $this->projectHelper->getSiteDetail($prjKode,$sitKode);
				$boq3[$key]['sit_nama'] = $sitNama['sit_nama'];
			}

            if($boq3[$key]['val_kode'] == 'IDR')
			{
				$jumIDR += $boq3[$key]['total'];
			}
			else
			{
				$boq3[$key]['rateidr'] = $rateidr . '.00';
				$jumUSD += $boq3[$key]['total'];
			}
        
			$insertID = $budgetd->insert($boq3[$key]);
			if ($insertID)
				$count++;

		}

		$insertArray = array(
						"trano" => $trano,
						"tgl" => date('Y-m-d'),
						"tglinput" => date('Y-m-d'),
						"jam" => date('H:i:s'),
						"prj_kode" => $prjKode,
						"prj_nama" => $prjNama['Prj_Nama'],
						"sit_kode" => $sitKode,
						"sit_nama" => $sitNama['sit_nama'],
//						"cus_kode" => $cusKode,
						"ket" => $ket,
						"rateidr" => $rateidr,
//						"cus_kode" => $cusKode,
						"total" => $jumIDR,
						"totalusd" => $jumUSD,
						"user" => $this->session->userName,
                        "petugas" => $this->session->userName
        );

//		$result = $BOQ3h->insert($insertArray);
		$result = $budgeth->insert($insertArray);

		$counter->update(array("urut" => $lastTrans['urut']),"id=".$lastTrans['id']);

		if (count($boq3) == 0)
			echo "{success:false, message:\"Error in Database\"}";
		else
			echo "{success:true, count:$count, of:$jumMasuk, number:'$trano'}";
    }

    public function cboq3Action()
    {

    }

    public function addcboq3Action()
    {

    }

    public function editcboq3Action()
    {

    }

    public function appcboq3Action()
    {
        
    }

    public function insertcboq3Action()
    {
        $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $jsonData = $this->getRequest()->getParam("posts");
       $jsonData = Zend_Json::decode($jsonData);
        foreach($jsonData as $key => $val)
            {
                if ($val == "\"\"")
                {
                    $jsonData[$key] = '';
                }
            }

//        var_dump($jsonData);die;

       $counter = new Default_Models_MasterCounter();

	   $lastTrans = $counter->getLastTrans('CBOQ3');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'CBOQ3-' . $last;
	   $where = "id=".$lastTrans['id'];
       $counter->update(array("urut" => $last),$where);
       $urut = 1;
        $total = 0;
        $totalUSD = 0;
        $totals = 0;
        $totalsUSD = 0;


      foreach($jsonData as $key => $val)
      {
          if ($val['val_kode'] == IDR)
            $total = $val['qtybaru']*$val['hargabaru'];
          else
             $totalUSD = $val['qtybaru']*$val['hargabaru'];

		  $masterBarang = new Default_Models_MasterBarang();
          $kode = $val['kode_brg'];
          $barang = $masterBarang->fetchRow("kode_brg = '$kode'");
            if ($barang)
            {
                if ($barang['stspmeal'] == 'Y' )
                {
                    $pmeal = 'Y';
                }
                else
                {
                    $pmeal = 'N';
                }
            }
          $arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"qty" => $val['qtybaru'],
				"harga" => $val['hargabaru'],
                "stspmeal" => $pmeal,
				"total" => $total,
				"ket" => $val['ket'],

                "cfs_kode" => $val['cfs_kode'],
                "cfs_nama" => $val['cfs_nama'],
				"petugas" => $this->session->userName,
                
//                "cus_kode" => $val['cus_kode'],
				"val_kode" => $val['val_kode'],
				"afe_no" => $val['trano'],
                "rateidr" => $val['rateidr'],
               
			);
            $urut++;
            $totals = $totals + $total;
            $totalsUSD = $totalsUSD + $totalUSD;
//            var_dump($arrayInsert);die;
            $this->cboq3->insert($arrayInsert);
      }

      foreach($jsonData as $key => $val)
      {
          $total = $val['qtybaru']*$val['hargabaru'];
          $masterBarang = new Default_Models_MasterBarang();
          $kode = $val['kode_brg'];
          $barang = $masterBarang->fetchRow("kode_brg = '$kode'");
            if ($barang)
            {
                if ($barang['stspmeal'] == 'Y' )
                {
                    $pmeal = 'Y';
                }
                else
                {
                    $pmeal = 'N';
                }
            }
          $arrayInsert = array(
				
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"qty" => $val['qtybaru'],
				"harga" => $val['hargabaru'],
                "stspmeal" => $pmeal,
				"total" => $total,
				"ket" => $val['ket'],

                "cfs_kode" => $val['cfs_kode'],
                "cfs_nama" => $val['cfs_nama'],
				"petugas" => $this->session->userName,
              
//                "cus_kode" => $val['cus_kode'],
                "tranorev" => $trano,
                "rev" => 'Y',
				"val_kode" => $val['val_kode'],
				
                "rateidr" => $val['rateidr'],

			);
            $urut++;
            $this->boq3->insert($arrayInsert);
      }

            $cusKode = $this->project->getProjectAndCustomer($jsonData[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];

        	$arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),

				"prj_kode" => $jsonData[0]['prj_kode'],
				"prj_nama" => $jsonData[0]['prj_nama'],
				"sit_kode" => $jsonData[0]['sit_kode'],
				"sit_nama" => $jsonData[0]['sit_nama'],
				"ket" => $jsonData[0]['ket'],
                "cus_kode" => $cusKode,

				"total" => $totals,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
				"totalusd" => $totalsUSD,
                "customercontract" => $jsonData[0]['customercontract'],

				"afe_no" => $jsonData[0]['trano'],
                "rateidr" => $jsonData[0]['rateidr']
       
            );
            $this->cboq3H->insert($arrayInsert);
//			$arrayInsert = $this->workflow->convertFormat($arrayInsert,'trano');
//            $result = $this->workflow->setWorkflowTrans($arrayInsert,'ASF', '', $this->const['DOCUMENT_SUBMIT']);

            $prjKode = $jsonData[0]['prj_kode'];
            $sitKode = $jsonData[0]['sit_kode'];

            $budget = new Default_Models_Budget();
            $boq2 = $budget->getBoq2("summary-current",$prjKode,$sitKode);
            $boq3 = $budget->getBoq3("summary-current",$prjKode,$sitKode);

            $utility = Zend_Controller_Action_HelperBroker::getStaticHelper('utility');
            $rate = $utility->getExchangeRate();
        
            $boq2curr = floatval($boq2['totalCurrentIDR']) + floatval($boq2['totalCurrentUSD']);
            $boq2curr2 = floatval($boq2['totalCurrentIDR']) + (floatval($boq2['totalCurrentHargaUSD']) * floatval($rate));
            $boq3curr = floatval($boq3['totalIDR']) + floatval($boq3['totalUSD']);
            $boq3curr2 = floatval($boq3['totalIDR']) + (floatval($boq3['totalHargaUSD']) * floatval($rate));

        
            if ($boq2curr > 0)
            {
                $gm = (((floatval($boq2curr) - floatval($boq3curr)) / floatval($boq2curr))) * 100;
                $gm2 = (((floatval($boq2curr2) - floatval($boq3curr2)) / floatval($boq2curr2))) * 100;
            }
            else
                $gm = 0;
            $logs = array(
                "nama" => "CURRENT_GM_FROM_CBOQ3",
                "tgl" => date('Y-m-d H:i:s'),
                "uid" => $this->session->userName,
                "data" => Zend_Json::encode(array(
                    "current_gm" => $gm,
                    "current_gm_with_rate" => $gm2,
                    "rateidr" => $rate,
                    "trano" => $trano
                ))
            );
            $log = new Default_Models_Log();
            $log->insert($logs);
       		$this->getResponse()->setHeader('Content-Type', 'text/javascript');
        	$this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateboq3Action()
    {
        
    }

    public function allafeAction()
    {

    }

    public function listapprovedafeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

//        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,\'%m/%d/%Y\') as tgl,pr_no,prj_kode,prj_nama,sit_kode,sit_nama,sup_kode,sup_nama FROM procurement_poh  ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        $sql = "SELECT SQL_CALC_FOUND_ROWS item_id, prj_kode FROM workflow_trans where approve = 400 and item_type = 'AFE' group by item_id";
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

    public function listapprovedafebyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
   
         $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

//        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,\'%m/%d/%Y\') as tgl,pr_no,prj_kode,prj_nama,sit_kode,sit_nama,sup_kode,sup_nama FROM procurement_poh  ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        $sql = "SELECT SQL_CALC_FOUND_ROWS item_id, prj_kode FROM workflow_trans WHERE approve = 400 AND item_type = 'AFE' AND item_id LIKE '%$columnValue%' group by item_id";
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

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = "SELECT trano,prj_kode,sit_kode,prj_nama,sit_nama,id FROM transengineer_praboq3h ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = count($return['posts']);

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
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,prj_kode,sit_kode,prj_nama,sit_nama,id FROM transengineer_praboq3h WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function edittempboq3Action()
    {
        $request = $this->getRequest();
        $trano = $request->getParam('trano');

        $header = $this->tempBOQ3h->fetchRow("trano = '$trano'");
        if (!$header)
            $this->view->invalid = true;

        $header = $header->toArray();
        
        $detail = $this->tempBOQ3->fetchAll("trano = '$trano'");
        if ($detail)
        {
            $hasil = $detail->toArray();
            foreach ($hasil as $key => &$val)
            {
                foreach ($val as $key2 => $val2)
                {
                    $hasil[$key][$key2] = str_replace("\"","",$hasil[$key][$key2]);
                    $hasil[$key][$key2] = str_replace("'","",$hasil[$key][$key2]);
                    $hasil[$key][$key2] = str_replace("\r","",$hasil[$key][$key2]);
                    $hasil[$key][$key2] = str_replace("\n","",$hasil[$key][$key2]);
                }
                $hasil[$key]['total'] = $hasil[$key]['qty'] * $hasil[$key]['harga'];
                if ($hasil[$key]['kode_brg'] == '')
                    $hasil[$key]['kode_brg'] = 'XX';
            }
            $return['posts'] = $hasil;
        }
        else
            $return['posts'] = array();
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        $this->view->trano = $trano;
        $this->view->json = $json;
        $this->view->prjKode = $header['prj_kode'];
        $this->view->prjNama = $header['prj_nama'];
        $this->view->sitKode = $header['sit_kode'];
        $this->view->sitnama = $header['sit_nama'];

    }

    public function cekafeexistAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $trano = $this->getRequest()->getParam('trano');


        $cek = $this->trans->cekAfe($trano);

        if($cek == 'exist')
        {
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: false, msg:\"this AFE has already uploaded\"}");

        }
        else
        {
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: true}");
        }
    }

    public function detailpraboq3Action()
    {

        $approve = $this->getRequest()->getParam("trano");
        $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
        if ($docs)
        {
            $approve = $docs['item_id'];
            $tempBOQ3 = new ProjectManagement_Models_TemporaryBOQ3();
            $tempBOQ3h = new ProjectManagement_Models_TemporaryBOQ3h();
            $boq3 = $tempBOQ3->fetchAll("trano = '$approve'")->toArray();
            $boq3h = $tempBOQ3h->fetchRow("trano = '$approve'");
            if ($boq3)
            {
                $jumIDR = 0;
                $jumUSD = 0;
                $jumUSDRate = 0;
                $totJum = 0;
                foreach($boq3 as $key => &$val)
                {
                    $kodeBrg = $val['kode_brg'];
                    $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                    if ($barang)
                    {
                        $val['uom'] = $barang['sat_kode'];
                    }
                   else
                       $val['uom'] = '&nbsp;';

                  $val['qty'] = floatval($val['qty']);
                  $val['harga'] = floatval($val['harga']);

                }

            }

                $cusKode = $this->project->getProjectAndCustomer($boq3h['prj_kode']);
                $cus_kode = $cusKode[0]['cus_kode'];
                $cus_nama = $cusKode[0]['cus_nama'];

                $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                $jsonData2[0]['prj_kode'] = $boq3h['prj_kode'];
                $jsonData2[0]['prj_nama'] = $boq3h['prj_nama'];
                $jsonData2[0]['sit_kode'] = $boq3h['sit_kode'];
                $jsonData2[0]['sit_nama'] = $boq3h['sit_nama'];
                $jsonData2[0]['uid'] = $boq3h['uid'];

                $jsonData2[0]['trano'] = $approve;

                $this->view->etc = $jsonData2;
                $this->view->result = $boq3;
                $this->view->result2 = $boq3h;

                $this->view->trano = $boq3h['trano'];
        }

    }

    public function apppraboqAction()
    {
        $type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
   		$show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/PRABOQ3';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$trano = $this->getRequest()->getParam("trano");
	   		$etc = $this->getRequest()->getParam("etc");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);

            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $cus_kode = $cusKode[0]['cus_kode'];
            $cus_nama = $cusKode[0]['cus_kode'];

            $jsonData2[0]['cus_kode'] = $cus_kode;
            $jsonData2[0]['cus_nama'] = $cus_nama;
               
            $jumIDR = 0;
            $jumUSD = 0;
            $jumUSDRate = 0;
            $totJum = 0;

            foreach ($jsonData as $key => $val)
            {
                $jsonData[$key][qty] = floatval($jsonData[$key][qty]);
                $jsonData[$key][harga] = floatval($jsonData[$key][harga]);
                $jsonData[$key][rateidr] = floatval($jsonData[$key][rateidr]);
                $barang = $this->barang->fetchRow("kode_brg = '$val[kode_brg]'");
                if ($barang)
                {
                    $jsonData[$key][uom] = $barang['sat_kode'];
                }
                else
                    $jsonData[$key][uom] = '&nbsp;';

                if ($jsonData[$key]['val_kode'] == 'IDR')
                  {
                      $jumIDR += $jsonData[$key]['qty'] * $jsonData[$key]['harga'];
                  }
                  else
                  {
                      $jumUSD += $jsonData[$key]['qty'] * $jsonData[$key]['harga'];
                      $jumUSDRate += $jsonData[$key]['qty'] * $jsonData[$key]['harga'] * $jsonData[$key]['rateidr'];
                  }

                $this->view->jumIDR = $jumIDR;
                $this->view->jumUSD = $jumUSD;
                $totJum = floatval($jumIDR + $jumUSDRate);
                $this->view->totJum = floatval($totJum);
            }

            $boq2 = $this->budget->getBoq2('summary-ori', $jsonData2[0]['prj_kode'], $jsonData2[0]['sit_kode']);

            if ($boq2 != '')
            {
                $this->view->boq2IDR = floatval($boq2['totalOriginIDR']);
                $this->view->boq2USD = floatval($boq2['totalOriginUSD']);
                $this->view->boq2HargaUSD = floatval($boq2['totalOriginHargaUSD']);
                $totJumBOQ2 = floatval($boq2['totalOriginIDR']) +floatval( $boq2['totalOriginUSD']);
                $this->view->totJumBOQ2 = $totJumBOQ2;
            }

            if ($totJumBOQ2)
                $this->view->GM = (1 - ($totJum / $totJumBOQ2)) * 100;
            else
            {
                $this->view->GM = 0;
                $this->view->reason = "Customer Order has not been input yet!";
            }


	       	$this->view->result = $jsonData;
            $this->view->jsonResult = $json;
	       	$this->view->etc = $jsonData2;
            $this->view->jsonEtc = $etc;

            $gantt = new Extjs4_Models_Gantt();
            $cek = $gantt->fetchRow("boq_no = '$trano'");
            if ($cek)
            {
                $this->view->isCurve = true;
            }
           else
           {
               $cacheIDSubmit = "GANTT_SUBMIT_" . $this->session->userName . "_" . md5($trano);
               if ($this->tempGantt->test($cacheIDSubmit))
               {
                   $this->view->isCurve = true;
               }
               else
                   $this->view->isCurve = false;
           }
            $this->view->trano = $trano;

            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
                if ($this->view->isCurve)
                {
                    $this->saveGanttToTemp($trano,$json,$jsonData2[0]['prj_kode'], $jsonData2[0]['sit_kode']);
                }
	       	}

   		}
   		else
   		{
   			$docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            $this->view->transID = $approve;
            if ($docs)
   			{
   				$user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
   				if ($user)
   				{
                    $tempBOQ3 = new ProjectManagement_Models_TemporaryBOQ3();
		            $tempBOQ3h = new ProjectManagement_Models_TemporaryBOQ3h();
	   				$id = $docs['workflow_trans_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
		   			$boq3 = $tempBOQ3->fetchAll("trano = '$approve'")->toArray();
		   			$boq3h = $tempBOQ3h->fetchRow("trano = '$approve'");
		   			if ($boq3)
		   			{
                        $jumIDR = 0;
                        $jumUSD = 0;
                        $jumUSDRate = 0;
                        $totJum = 0;
		   				foreach($boq3 as $key => &$val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$val['uom'] = $barang['sat_kode'];
		   					}
                           else
                               $val['uom'] = '&nbsp;';    

                          $val['qty'] = floatval($val['qty']);
                          $val['harga'] = floatval($val['harga']);
                          if ($val['val_kode'] == 'IDR')
                          {
                              $jumIDR += $val['qty'] * $val['harga'];
                          }
                          else
                          {
                              $jumUSD += $val['qty'] * $val['harga'];
                              $jumUSDRate += $val['qty'] * $val['harga'] * $val['rateidr'];
                          }

		   				}
                        $this->view->jumIDR = $jumIDR;
                        $this->view->jumUSD = $jumUSD;
                        $totJum = floatval($jumIDR + $jumUSDRate);
                        $this->view->totJum = floatval($totJum);   
                    }


                        $cusKode = $this->project->getProjectAndCustomer($boq3h['prj_kode']);
                        $cus_kode = $cusKode[0]['cus_kode'];
                        $cus_nama = $cusKode[0]['cus_nama'];


                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['prj_kode'] = $boq3h['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $boq3h['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $boq3h['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $boq3h['sit_nama'];
                        $jsonData2[0]['uid'] = $boq3h['uid'];

				        $jsonData2[0]['trano'] = $approve;
                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;

                        $boq2 = $this->budget->getBoq2('summary-current', $jsonData2[0]['prj_kode'], $jsonData2[0]['sit_kode']);

                        if ($boq2 != '')
                        {
//                            $this->view->boq2IDR = floatval($boq2['totalOriginIDR']);
//                            $this->view->boq2USD = floatval($boq2['totalOriginUSD']);
//                            $this->view->boq2HargaUSD = floatval($boq2['totalOriginHargaUSD']);
//                            $totJumBOQ2 = floatval($boq2['totalOriginIDR']) +floatval( $boq2['totalOriginUSD']);
                            $this->view->boq2IDR = floatval($boq2['totalCurrentIDR']);
                            $this->view->boq2USD = floatval($boq2['totalCurrentUSD']);
                            $this->view->boq2HargaUSD = floatval($boq2['totalCurrentHargaUSD']);
                            $totJumBOQ2 = floatval($boq2['totalCurrentIDR']) +floatval( $boq2['totalCurrentUSD']);
                            $this->view->totJumBOQ2 = $totJumBOQ2;
                        }

                        if ($totJumBOQ2)
                            $this->view->GM = (1 - ($totJum / $totJumBOQ2)) * 100;
                        else
                        {
                            $this->view->GM = 0;
                            $this->view->reason = "Customer Order has not been input yet!";
                        }
                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $boq3;
                        $this->view->result2 = $boq3h;
		   				$this->view->approve = true;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
                        $this->view->trano = $boq3h['trano'];

                       $gantt = new Extjs4_Models_Gantt();
                        $cek = $gantt->fetchRow("boq_no = '$approve'");
                        if ($cek)
                        {
                            $this->view->isCurve = true;
                        }
                       else
                           $this->view->isCurve = false;
	   			}
	   			else
	   			{
	   				$this->view->approve = false;
	   			}
	   		}
   			else
   			{
   				$this->view->approve = false;
   			}
   		}

   		$json = $this->getRequest()->getParam("posts");


   		Zend_Loader::loadClass('Zend_Json');
   		$jsonData = Zend_Json::decode($json);
    }

    public function updatetempboqAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	Zend_Loader::loadClass('Zend_Json');
    	$json = $this->getRequest()->getParam("posts");
    	$etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($json);
        $jsonEtc = Zend_Json::decode($etc);

        $trano = $jsonEtc[0]['trano'];

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
       $result = $this->workflow->setWorkflowTrans($trano,'PRABOQ3','',$this->const['DOCUMENT_RESUBMIT'],$items);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result))
        {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }
        elseif (is_array($result) && count($result) > 0)
       {

           $hasil = Zend_Json::encode($result);
           $this->getResponse()->setBody("{success: true, user:$hasil}");
           return false;
       }

		$tempBOQ3 = new ProjectManagement_Models_TemporaryBOQ3();
		$tempBOQ3h = new ProjectManagement_Models_TemporaryBOQ3h();
		$counter = new Default_Models_MasterCounter();
        $count = 0;

		$jumIDR = 0;
		$jumUSD = 0;

        $tempBOQ3->delete("trano = '$trano'");
		foreach ($jsonData as $key => $val)
		{
            unset($jsonData[$key]['id']);
			if($val['val_kode'] == 'IDR')
			{
				$jumIDR += $val['qty'] * $val['harga'];
			}
			else
			{
				$jumUSD += $val['qty'] * $val['harga'];
			}
            if ($val['tgl'] == '')
                $val['tgl'] = date('Y-m-d H:i:s');
            if ($val['uid'] == '')
                $val['uid'] = $this->seession->userName;
            if ($val['kode_brg'] == '' && ($val['workid'] == 1100 || $val['workid'] == 2100 || $val['workid'] == 3100))
                $val['nama_brg'] = 'Others';
			$tempBOQ3->insert($jsonData[$key]);
		}


		$customer = new Default_Models_MasterCustomer();

		$cusKode = $customer->getCustomerFromProject($jsonEtc[0]['prj_kode']);

		$cusKode = $cusKode['cus_kode'];

		$insertArray = array(
						"total" => $jumIDR,
						"totalusd" => $jumUSD
        );

		$tempBOQ3h->update($insertArray, "trano='$trano'");

        $cacheID = "GANTT_SUBMIT_" . $this->session->userName . "_" . md5($trano);
        if ($this->tempGantt->test($cacheID))
        {
            $gantt = $this->tempGantt->load($cacheID);
            $task = $gantt['task'];
            $taskd = $gantt['taskd'];
            $depend = $gantt['depend'];

            $this->gantt->delete("boq_no = '$trano'");
            $this->gantt->insert($task);
            if ($taskd != '')
            {
                $this->ganttd->delete("boq_no = '$trano'");
                foreach($taskd as $k2 => $v2)
                {
                    $this->ganttd->insert($v2);
                }
            }

            $this->depend->delete("boq_no = '$trano'");
            foreach($depend as $k => $v)
            {
                $this->depend->insert($v);
            }

            $this->tempGantt->delete("cache_id = '$cacheID'");
            $cacheID = "GANTT_EDIT_" . $this->session->userName . "_" . md5($trano);
            $this->tempGantt->delete("cache_id = '$cacheID'");
        }
        echo "{success:true}";
    }

    public function cekbudgetexistAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $sql = "SELECT sit_kode,sit_nama FROM transengineer_boq3dnonproject WHERE prj_kode = '$prjKode' AND sit_kode='$sitKode'";

        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();
        if (count($return) > 0 && isset($return))
        {
        	$result = "{success:false}";
        }
        else
        {
        	$result = "{success:true}";
        }
        echo $result;
    }

    public function transferpraboq3Action()
    {
    	$this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('trano');

        $this->budget->transferTempBOQ3($trano);
    }

    public function apptemporaryohbudgetAction ()
    {
        $type = $this->getRequest()->getParam("type");
        $approve = $this->getRequest()->getParam("approve");
        $from = $this->getRequest()->getParam("from");
        $iduser = $this->session->idUser;
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/PRABGO';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';
        $preview = $this->getRequest()->getParam("preview");


        if ($approve != '' || $approve != null)
        {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve")->toArray();
            if($docs)
            {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$iduser);
                if ($user || $show)
                {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;

                    $this->budgeth = new ProjectManagement_Models_TemporaryOverHeadBOQ3h();
                    $this->budgetd = new ProjectManagement_Models_TemporaryOverHeadBOQ3d();

                    $tempOHbudgetd = $this->budgetd->fetchAll("trano = '$approve'")->toArray();
                    $tempOHbudgeth = $this->budgeth->fetchRow("trano = '$approve'");

                    if ($tempOHbudgetd)
                    {
//                        foreach ($tempOHbudgetd as $key => $val)
//                        {
//                            if ($val['val_kode'] == 'IDR')
//		   						$tempOHbudgetd[$key]['hargaIDR'] = $val['total'];
//		   					elseif ($val['val_kode'] == 'USD')
//		   						$tempOHbudgetd[$key]['hargaUSD'] = $val['total'];
//                        }



                        $userApp = $this->workflow->getAllApproval($approve);

                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $tempOHbudgeth['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $tempOHbudgeth['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $tempOHbudgeth['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $tempOHbudgeth['sit_nama'];
                        $cusKode = $this->project->getProjectAndCustomer($tempOHbudgeth['prj_kode']);
                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $tempOHbudgeth['petugas'];
                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);

                        $this->view->etc = $jsonData2;
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->preview = $preview;
                        $this->view->ohbudgeth = $tempOHbudgeth;
                        $this->view->ohbudgetd = $tempOHbudgetd;

                    }else
                    {
                        $this->view->approve = false;
                    }
                }else
                {
                    $this->view->approve = false;
                }
            }  
        }else
        {
            $etc = $this->getRequest()->getParam("etc");
            $json = $this->getRequest()->getParam("posts");
            $trano = $this->getRequest()->getParam("trano");
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);

//            foreach($jsonData as $key => $val)
//            {
//                  foreach($val as $key2 => $val2)
//                {
//                    if ($val2 == "\"\"")
//                        $jsonData[$key][$key2] = '';
//                    if (strpos($val2,"\"")!== false)
//                        $jsonData[$key][$key2] = str_replace("\""," inch",$jsonData[$key][$key2]);
//                    if (strpos($val2,"'")!== false)
//                        $jsonData[$key][$key2] = str_replace("'"," inch",$jsonData[$key][$key2]);
//                }
//            }

            $this->view->ohbudgetd = $jsonData;
            $this->view->etc = $jsonData2;
	       	$this->view->jsonResult = Zend_Json::encode($jsonData);
            $this->view->etcResult = Zend_Json::encode($jsonData2);
            
            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}
            
        }



    }

    public function edittemporaryohbudgetAction ()
    {
        $request = $this->getRequest();
        $trano = $this->getRequest()->getParam("trano");
        $this->view->trano = $trano;

        $this->budgeth = new ProjectManagement_Models_TemporaryOverHeadBOQ3h();
        $this->budgetd = new ProjectManagement_Models_TemporaryOverHeadBOQ3d();

        $trano = $this->getRequest()->getParam("trano");
        $where = "trano = '$trano'";


        $tempOHbudgetdetaildata = $this->budgetd->fetchAll($where)->toArray();
        $header = $this->budgeth->fetchRow($where)->toArray();
      

        foreach($tempOHbudgetdetaildata as $k => $v)
        {
            foreach($v as $k2 => $v2)
            {
                if($v2 == '""')
                    $tempOHbudgetdetaildata[$k][$k2] = '';
            }
        }

        $return['data'] = $tempOHbudgetdetaildata;

        $json = Zend_Json::encode($return);
        $this->view->json = $json;
        $this->view->prjKode = $header['prj_kode'];
        $this->view->prjNama = $header['prj_nama'];
        $this->view->sitKode = $header['sit_kode'];
        $this->view->sitNama = $header['sit_nama'];
        
    }

    public function updatetemporaryohbudgetAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $this->budgeth = new ProjectManagement_Models_TemporaryOverHeadBOQ3h();
        $this->budgetd = new ProjectManagement_Models_TemporaryOverHeadBOQ3d();

        $jsonData = $this->getRequest()->getParam("ohheader");
        $jsonData2 = $this->getRequest()->getParam("ohdetail");
    

        Zend_Loader::loadClass('Zend_Json');
        $ohheader = Zend_Json::decode($jsonData);
        $ohdetail = Zend_Json::decode($jsonData2);


     
        $tgl = date('Y-m-d');
        $urut = 1;

        $trano = $ohheader[0]['trano'];
        $petugas = $this->session->userName;

        $items = $ohheader[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
       $result = $this->workflow->setWorkflowTrans($trano,'PRABGO','',$this->const['DOCUMENT_RESUBMIT'],$items,'',false);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result))
        {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }
        elseif (is_array($result) && count($result) > 0)
       {

           $hasil = Zend_Json::encode($result);
           $this->getResponse()->setBody("{success: true, user:$hasil}");
           return false;
       }


        if ($trano != null)
        $this->budgetd->delete("trano = '$trano'");

        $grandTotal = 0;

        foreach ($ohdetail as $key => $val)
        {
            $insertBudget = array(
                "trano" => $trano,
                "tgl" => $tgl,
                "urut" => $urut,
                "prj_kode" => $ohheader[0]['prj_kode'],
                "prj_nama" => $ohheader[0]['prj_nama'],
                "sit_kode" => $ohheader[0]['sit_kode'],
                "sit_nama" => $ohheader[0]['sit_nama'],
                "budgetid" => $val['budgetid'],
                "budgetname" => $val['budgetname'],
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama'],
                "total" => $val['total'],
                "petugas" => $petugas,
                "val_kode" => $val['val_kode'],
                "rateidr" => $val['rateidr']

            );$urut++;

            $grandTotal += floatval($val['total']);
            $this->budgetd->insert ($insertBudget);
        }


        $UpdateHeader = array(
            "totalusd" => $grandTotal
        );

        $this->budgeth->update($UpdateHeader,"trano = '$trano'");



        $return = array("berhasilUpdate" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function appregiscoAction ()
    {
        $trano = $this->getRequest()->getParam('trano');

        $regiscodata = $this->regisco->fetchAll("trano = '$trano'")->toArray();

        $file = $this->files->fetchAll("trano = '$trano'")->toArray();

        $this->view->data = $regiscodata;
        $this->view->file = Zend_Json::encode($file);

    }

    public function doinsertregcoAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\","",$etc);
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
        $jsonFile = Zend_Json::decode($file);

        $po_cus = $jsonData2[0]['po_cus'];

        $cekpo_cus = $this->regisco->fetchRow("pocustomer = '$po_cus'");

        if ($cekpo_cus)
        {
            echo "{success:false,msg:'Sorry,PO customer <b><font color=red>$po_cus</font></b> has been exists'}";
            die;
        }

//        var_dump($jsonData2);die;

        $counter = new Default_Models_MasterCounter();
//        $lastTrans = $counter->getLastTrans('REGCO');
//        $last = abs($lastTrans['urut']);
//        $last = $last + 1;
//        $trano = 'REGCO-' . $last;

//        $counter->update(array("urut" => $last),"id=".$lastTrans['id']);

            $trano = $counter->setNewTrans('REGCO');


        	$arrayInsert = array (
                "trano" => $trano,
                "tgl" => date('Y-m-d H:i:s'),
                "ket" => $jsonData2[0]['ket'],
                "cus_kode" => $jsonData2[0]['cus_kode'],
                "cus_nama" => $jsonData2[0]['cus_nama'],
//                "mgr_kode" => $jsonData2[0]['mgr_kode'],
//                "mgr_nama" => $jsonData2[0]['mgr_nama'],
                "total" => $jsonData2[0]['total_idr'],
                "uid" => $this->session->userName,
//                "tglinput" => date('Y-m-d'),
//                "jam" => date('H:i:s'),
                "totalusd" => $jsonData2[0]['total_usd'],
                "pocustomer" => $jsonData2[0]['po_cus'],
//				"statusfinal" => 'N',
				"confirmation" => $jsonData2[0]['source'],
                "assignto" => $jsonData2[0]['uidpm'],
                "message" => $jsonData2[0]['message'],
                "stsproses" => 1
            );

            $this->regisco->insert($arrayInsert);

            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
//                        "prj_kode" => $prjKode,
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => $this->session->userName,
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    $this->files->insert($arrayInsert);
                }
            }

            $uid_sender = $this->session->userName;
            $pm = $jsonData2[0]['pm'];
            $message = $jsonData2[0]['message'];
            $uid_receive = $jsonData2[0]['uidpm'];;

            $arrayInsert = array(
    //            "id_reply" => null,
    //            "workflow_item_id" => $workflow_item_id,
                "uid_sender" => $uid_sender,
                "uid_receiver" => $uid_receive,
                "message" => $message,
                "date" => date('Y-m-d H:i:s'),
                "trano" => $trano
    //            "prj_kode" => $prj_kode
            );

            $this->conversation->insert($arrayInsert);

	        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function doinsertpmAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $pm = $this->getRequest()->getParam('pm');
        $message = $this->getRequest()->getParam('message');
        $trano = $this->getRequest()->getParam('trano');
        $uid_receive = $this->getRequest()->getParam('uid');

        $uid_sender = $this->session->userName;

        $insertpm = array(
            "assignto" => $pm,
            "message" => $message,
            "stsproses" => 1
        );

        $this->regisco->update($insertpm,"trano = '$trano'");

        $arrayInsert = array(
//            "id_reply" => null,
//            "workflow_item_id" => $workflow_item_id,
            "uid_sender" => $uid_sender,
            "uid_receiver" => $uid_receive,
            "message" => $message,
            "date" => date('Y-m-d H:i:s'),
            "trano" => $trano
//            "prj_kode" => $prj_kode
        );

        $this->conversation->insert($arrayInsert);

        $this->getResponse()->setBody("{success: true, number : '$trano',pm : '$pm'}");
    }

    public function savegantttotempAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $trano = $this->getRequest()->getParam("trano");
        $submit = $this->getRequest()->getParam("submit");
        $json = $this->getRequest()->getParam("json");

        if ($submit == '')
            $submit = false;
        else
            $submit = true;
        $this->saveGanttToTemp($trano,$json,$prjKode,$sitKode,$submit);

        echo "{success: true}";
    }

    private function saveGanttToTemp($trano,$json,$prjKode='',$sitKode='',$isSubmit=false)
    {
        if (!$isSubmit)
        {
            $cacheIDEdit = "GANTT_EDIT_" . $this->session->userName . "_" . md5($trano);
            $cacheIDSubmit = "GANTT_SUBMIT_" . $this->session->userName . "_" . md5($trano);
        }
        else
        {
            $cacheIDEdit = "GANTT_" . $this->session->userName . "_" . $prjKode . "_" . $sitKode;
            $cacheIDSubmit = "GANTT_SUBMIT_" . $this->session->userName . "_" . md5($prjKode . "_" . $sitKode);
        }
//                    $memcacheEdit = Zend_Registry::get('MemcacheGantt');
//                    if ($memcacheEdit->test($cacheIDEdit))
        if ($this->tempGantt->test($cacheIDEdit))
        {
//                        $load = $memcacheEdit->load($cacheIDEdit);
            $load = $this->tempGantt->load($cacheIDEdit);
            $submit['data'] = $json;

            $task = Zend_Json::decode($load['task']);

            $arrayTask = array();
            $arrayTaskd = array();
            foreach($task as $k => $v)
            {
                if ($v['children'] != '')
                {
                    $parentID = $v['Id'];
                    foreach($v['children'] as $k2 => $v2)
                    {
                        $split = explode(" - ",$v2['Name']);
                        if ($v2['ParentId'] != '')
                            $parentID = $v2['ParentId'];
                        elseif($v2['parentId'] != '')
                            $parentID = $v2['parentId'];

                        $arrayTaskd[] = array(
                            "prj_kode" => $prjKode,
                            "sit_kode" => $sitKode,
                            "start_date" => $v2['StartDate'],
                            "end_date" => $v2['EndDate'],
                            "workid" => $split[0],
                            "workname" => $split[1],
                            "name" => $v2['Name'],
                            "gantt_id" => $v2['Id'],
                            "parent_id" => $parentID,
                            "uid" => $this->session->userName,
                            "percent_done" => 0,
                            "tgl" => date("Y-m-d H:i:s"),
                            "boq_no" => $trano
                        );
                    }
                }
                $arrayTask = array(
                    "prj_kode" => $prjKode,
                    "sit_kode" => $sitKode,
                    "start_date" => $v['StartDate'],
                    "end_date" => $v['EndDate'],
                    "name" => $v['Name'],
                    "gantt_id" => $v['Id'],
                    "uid" => $this->session->userName,
                    "percent_done" => 0,
                    "tgl" => date("Y-m-d H:i:s"),
                    "boq_no" => $trano
                );
            }

            $submit['task'] = $arrayTask;
            $submit['taskd'] = $arrayTaskd;

            $depend = Zend_Json::decode($load['depend']);
            $arrayDepend = array();
            foreach($depend as $k => $v)
            {
                $arrayDepend[] = array(
                    "prj_kode" => $prjKode,
                    "sit_kode" => $sitKode,
                    "from" => $v['From'],
                    "to" => $v['To'],
                    "type" => $v['Type'],
                    "uid" => $this->session->userName,
                    "tgl" => date("Y-m-d H:i:s"),
                    "boq_no" => $trano
                );

            }
            $submit['depend'] = $arrayDepend;
//                        $memcacheEdit->save($submit,$cacheIDSubmit);
            $this->tempGantt->save($submit,$cacheIDSubmit);
        }
    }

    public function editregiscoAction ()
    {

        $trano = $this->getRequest()->getParam('trano');
        $cek = $this->regisco->fetchRow("trano = '$trano'");
        if ($cek)
        {
            $cek = $cek->toArray();
            $ldap = new Default_Models_Ldap();
            $name = $ldap->getAccount($cek['assignto']);
            $name = $name['displayname'][0];
            $cek['username'] = $name;
            $this->view->data = $cek;
            $this->view->trano = $trano;
        }
    }

    public function getregiscoAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null)
        {
            $search = null;
        }
        else
        {
            $search = "$option LIKE '%$textsearch%'";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $sql = "
        DROP TEMPORARY TABLE IF EXISTS gakadapraco;
        CREATE TEMPORARY TABLE gakadapraco
        SELECT RC.* FROM transengineer_registerco RC LEFT JOIN transengineer_praco PC ON RC.trano = PC.regis_no
                        WHERE PC.trano IS NULL;
        ";

        $this->db->query($sql);
        $sql = "
        DROP TEMPORARY TABLE IF EXISTS adapraco;
        CREATE TEMPORARY TABLE adapraco
        select * from (select * FROM (select a.*,b.approve FROM transengineer_praco a left join workflow_trans b on a.trano = b.item_id order by b.item_id,b.date desc) z
        group by z.trano ) y where (y.approve = 300 OR y.approve is null);

        INSERT INTO gakadapraco
        select a.* from transengineer_registerco a RIGHT JOIN adapraco b ON a.trano = b.regis_no;
        ";

        $this->db->query($sql);

        if ($search)
            $search = " WHERE " . $search;

        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM gakadapraco $search order by $sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query($query);
        $data['data'] = $fetch->fetchAll();

        $ldap = new Default_Models_Ldap();
        $name = $ldap->getAccount($data['data'][0]['assignto']);
        $name = $name['displayname'][0];

        $data['data'][0]['username'] = $name;

        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

//        $data['data'] = $this->regisco->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
//        $data['total'] = $this->regisco->fetchAll($search)->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getregisfileAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $trano_praco = $this->getRequest()->getParam('praco');
        $file_temp = '';

//        var_dump($trano,$trano_praco);die;

        if ($trano != "" || $trano != null )
        {
            $file_temp = $trano;
        }else{
            $file_temp = $trano_praco;
        }

//        var_dump($file_temp);die;

        $files = $this->files->fetchAll("trano = '$file_temp'")->toArray();

        $json = Zend_Json::encode($files);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdateregiscoAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $regisdata = Zend_Json::decode($this->getRequest()->getParam("etc"));
        $files = Zend_Json::decode($this->getRequest()->getParam("file"));
        $deletefile = Zend_Json::decode($this->getRequest()->getParam("deletefile"));

//        var_dump($regisdata);die;

        $trano = $regisdata[0]['trano'];
        $tgl = date('Y-m-d H:i:s');
        $uid = $this->session->userName;

        $log['regisco-before'] = $this->regisco->fetchAll("trano = '$trano'")->toArray();

//        var_dump($log['regisco-before']);die;

        $updateregisco = array(

            "cus_kode" => $regisdata[0]['cus_kode'],
            "cus_nama" => $regisdata[0]['cus_nama'],
            "total" => $regisdata[0]['total_idr'],
            "totalusd" => $regisdata[0]['total_usd'],
            "ket" => $regisdata[0]['ket'],
            "pocustomer" => $regisdata[0]['po_cus'],
            "confirmation" => $regisdata[0]['source'],
            "uid" => $uid,
            "tgl" => $tgl,
            "assignto" => $regisdata[0]['uidpm'],
            "message" => $regisdata[0]['message'],
            "stsproses" => 1

        );

        $this->regisco->update($updateregisco,"trano = '$trano' ");

        $log2['regisco-after'] = $this->regisco->fetchAll("trano = '$trano'")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

        $arrayLog = array (
              "trano" => $trano,
              "uid" => $this->session->userName,
              "tgl" => date('Y-m-d H:i:s'),
              "prj_kode" => "",
              "sit_kode" => "",
              "action" => "UPDATE",
              "data_before" => $jsonLog,
              "data_after" => $jsonLog2,
              "ip" => $_SERVER["REMOTE_ADDR"],
              "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log_trans->insert($arrayLog);

        if (count($deletefile) > 0)
        {
            foreach ($deletefile as $key => $val)
            {
                $this->files->delete("id = {$val['id']}");
            }
        }

        if (count($files) > 0)
        {
            foreach ($files as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => "",
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $uid_sender = $this->session->userName;
        $pm = $regisdata[0]['pm'];
        $message = $regisdata[0]['message'];
        $uid_receive = $regisdata[0]['uidpm'];
        $oldpm = $log['regisco-before'][0]['assignto'];

        $this->conversation->delete("trano = '$trano' AND uid_receiver = '$oldpm'");

        $arrayInsert = array(
//            "id_reply" => null,
//            "workflow_item_id" => $workflow_item_id,
            "uid_sender" => $uid_sender,
            "uid_receiver" => $uid_receive,
            "message" => $message,
            "date" => date('Y-m-d H:i:s'),
            "trano" => $trano
//            "prj_kode" => $prj_kode
        );

        $this->conversation->insert($arrayInsert);

        $this->getResponse()->setBody("{success: true, number : '$trano'}");

//        $json = Zend_Json::encode($files);
//        $this->getResponse()->setHeader('Content-Type','text/javascript');
//        $this->getResponse()->setBody($json);
    }

    public function rejectcoAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('trano');
        $reason = $this->getRequest()->getParam('reason');
        $id = $this->getRequest()->getParam('idmsg');

        $cek = $this->regisco->fetchRow("trano = '$trano'");
        if ($cek)
        {
            $oriMsg = $this->conversation->fetchRow("id = $id");
            if ($oriMsg)
            {
                $ldap = new Default_Models_Ldap();
                $name = $ldap->getAccount($this->session->userName);
                $name = $name['displayname'][0];
                $msgs = "<b>This REGCO has been declined by $name with reason : </b><br><p>$reason</p>";
                $uid_receive = $oriMsg['uid_sender'];
                $uid_sender = $this->session->userName;
                $arrayInsert = array(
                    "uid_sender" => $uid_sender,
                    "uid_receiver" => $uid_receive,
                    "message" => $msgs,
                    "date" => date('Y-m-d H:i:s'),
                    "trano" => $trano,
                    "data" => Zend_Json::encode(array(
                        "reject" => true
                    ))
                );
                $this->conversation->insert($arrayInsert);
                $success = true;

            }
        }
        else
        {
            $success = false;
            $msg = "Transaction not exists!";
        }

        $data = array("success" => $success, "msg" => $msg);
        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function reassigncoAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $newUid = $this->getRequest()->getParam('newUid');
        $trano = $this->getRequest()->getParam('trano');
        $text = $this->getRequest()->getParam('text');
        $id = $this->getRequest()->getParam('idmsg');

        $cek = $this->regisco->fetchRow("trano = '$trano'");
        if ($cek)
        {
            $arrayUpdate = array(
                "assignto" => $newUid
            );
            $this->regisco->update($arrayUpdate,"trano = '$trano'");
            $oriMsg = $this->conversation->fetchRow("id = $id");
            if ($oriMsg)
            {

                $this->conversation->delete("trano = '$trano'");
                $uid_receive = $newUid;
                $uid_sender = $this->session->userName;
                $arrayInsert = array(
                    "uid_sender" => $uid_sender,
                    "uid_receiver" => $uid_receive,
                    "message" => $text,
                    "date" => date('Y-m-d H:i:s'),
                    "trano" => $trano
                );
                $this->conversation->insert($arrayInsert);

                $success = true;

            }
        }
        else
        {
            $success = false;
            $msg = "Transaction not exists!";
        }

        $data = array("success" => $success, "msg" => $msg);
        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function createbudgetperiodenonprojectAction ()
    {
        
    }

    public function getcekpocustomerAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $pocustomer = $this->getRequest()->getParam('pocustomer');
        $trano = $this->getRequest()->getParam('trano');

        if($trano != 'edit' || $trano != null || $trano != '')
        {
            $regis = $this->regisco->fetchRow("trano = '$trano'");

            if ($pocustomer == $regis['pocustomer'])
            {
                $data = array("success" => true);
            }else
            {
                $cekpo_cus = $this->regisco->fetchRow("pocustomer = '$pocustomer'");

                if ($cekpo_cus)
                {
        //            echo "{success:false,msg:'Sorry,PO customer <b><font color=red>$po_cus</font></b> has been exists'}";
                    $data = array("success" => false,"msg" => "Sorry,PO customer <b><font color=red>$po_cus</font></b> has been exists");
                }else{
                    $data = array("success" => true);
        //            echo "{success:true}";
                }   
            }
        }else{
            $cekpo_cus = $this->regisco->fetchRow("pocustomer = '$pocustomer'");

            if ($cekpo_cus)
            {
    //            echo "{success:false,msg:'Sorry,PO customer <b><font color=red>$po_cus</font></b> has been exists'}";
                $data = array("success" => false,"msg" => "Sorry,PO customer <b><font color=red>$po_cus</font></b> has been exists");
            }else{
                $data = array("success" => true);
    //            echo "{success:true}";
            }
        }



        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

}