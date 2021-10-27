<?php

class Logistic_LogisticController extends Zend_Controller_Action
{
    private $logistic;
    private $logisticH;
    private $logisticDo;
    private $logisticDoH;
    private $db;
    private $request;
    private $json;
    private $util;
    private $token;
    private $session;
    private $workflow;
    private $workflowClass;
    private $workflowTrans;
    private $project;
    private $barang;
    private $ican;
    private $icanH;
    private $ilov;
    private $ilovH;
    private $const;
    private $supplier;
    private $isupp;
    private $isuppH;
    private $error;
    private $trans;
    private $files;
    private $counter;
    
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->const = Zend_Registry::get('constant');
        //$this->leadHelper = $this->_helper->getHelper('chart');
		$this->workflow = $this->_helper->getHelper('workflow');
		$this->error = $this->_helper->getHelper('error');
        $this->session = new Zend_Session_Namespace('login');
        $this->request = $this->getRequest();
        $this->json = $this->request->getParam('posts');
        if (isset($this->json))
        {
                //Fix unknown JSON format (Bugs on Firefox 3.6)
                $this->json = str_replace("\\","",$this->json);
                if (substr($this->json,0,1) != '[')
                {
                        $this->json = "[" . $this->json . "]";
                }
        }

        $this->logistic  = new Logistic_Models_LogisticDord();
        $this->logisticH = new Logistic_Models_LogisticDorh();
        $this->logisticDo  = new Logistic_Models_LogisticDod();
        $this->logisticDoH = new Logistic_Models_LogisticDoh();
        $this->ican = new Logistic_Models_LogisticMaterialCancel();
        $this->icanH = new Logistic_Models_LogisticMaterialCancelH();
        $this->ilov = new Logistic_Models_LogisticMaterialReturn();
        $this->ilovH = new Logistic_Models_LogisticMaterialReturnH();
        $this->isupp = new Logistic_Models_LogisticInputSupplier();
        $this->isuppH = new Logistic_Models_LogisticInputSupplierH();
        $this->supplier = new Default_Models_MasterSuplier();
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->trans = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Model_Workflow();
        $this->files = new Default_Models_Files();
        $this->counter = new Default_Models_MasterCounter();
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
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        switch($listType)
        {
            case 'poh':
                $procurement = new Default_Models_ProcurementPoh();
                $return['posts'] = $procurement->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
                $return['count'] = $procurement->fetchAll()->count();
            break;
            case 'pod':
                $trano = $request->getParam('trano');
                $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_pod WHERE trano='$trano' ORDER BY urut";
                $fetch = $this->db->query($sql);
                $return['posts'] = $fetch->fetchAll();
                $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
            break;        
            case 'dorh':
                $logistic = new Default_Models_ProcurementRequestH();
                $return['posts'] = $procurement->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
                $return['count'] = $procurement->fetchAll()->count();
            break;            
            case 'nodord':
                 $sql = "SELECT * FROM procurement_pointd p order by tgl desc,trano desc limit 1";
                 $fetch = $this->db->query($sql);
                 $return['posts'] = $fetch->fetch();
                 $return['count'] = 1;
                break;
            case 'dord':
                //$trano = $request->getParam('trano');
                //$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_prd WHERE trano='$trano' ORDER BY urut";

                $prj_kode = $request->getParam('prj_kode');
                $sit_kode = $request->getParam('sit_kode');
                $workid = $request->getParam('workid');
                $kode_brg = $request->getParam('kode_brg');
                
                //$sql = "call sp_boq3pr('$prj_kode','$sit_kode','$workid','$kode_brg')";
                //$fetch = $this->db->query($sql);
                //$return['posts'] = $fetch->fetchAll();
                //$return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
                //break;

                // call store procedure
                $sp = $this->db->prepare("call sp_boq3pr('$prj_kode','$sit_kode','$workid','$kode_brg')");
                $sp->execute();
                $return['posts'] = $sp->fetchAll();
                $return['count'] = count($return['posts']);
                $sp->closeCursor();

             break;
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
        $joinToPod = $request->getParam('joinToPod');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if ($joinToPod)
        {
            $prj_kode = $request->getParam('Prj_Kode');
            $sql = 'SELECT DISTINCT SQL_CALC_FOUND_ROWS a.trano,a.prj_kode,a.tgl,a.tglpr FROM procurement_poh a INNER JOIN procurement_pod b ON a.prj_kode = b.prj_kode WHERE b.' . $columnName . ' LIKE \'%' . $columnValue . '%\' AND b.Prj_Kode LIKE \'%' . $prj_kode . '%\' ORDER BY a.' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        }
        else
            $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_poh WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    
        
    public function chartAction()
    {
        $request = $this->getRequest();

//        $chartType = $request->getParam('type');
//        if ($chartType == 'byProject')
//        {
//            $this->render('chart');
//        }
    }

   public function getchartAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       $request = $this->getRequest();

       $chartType = $request->getParam('data');
       if (isset($chartType))
       {
           $sql = "SELECT SUM(a.total) AS total,a.tgl FROM procurement_poh a WHERE a.prj_kode='$chartType' GROUP BY CONCAT(a.tgl);";

           $fetch = $this->db->query($sql);
            $return = $fetch->fetchAll();
            $data = array();
            error_reporting(0);
            for ($i = 0;$i<count($return);$i++)
            {
                foreach ($return[$i] as $key => $value)
                {
                    $nama_key = $return[$i]['tgl'];
                    $data[$nama_key]= ceil($return[$i]['total']);
                }
            }

            error_reporting(E_ALL ^ E_NOTICE);
            $xml= $this->leadHelper->buildChart($data,'Detail Nilai Project','Tgl','Nilai Project');
       }
       else
       {
           $sql = "SELECT SUM(a.total) AS total,a.prj_kode,b.prj_nama FROM procurement_poh a LEFT JOIN master_project b on a.prj_kode = b.prj_kode GROUP BY prj_kode;";

           $fetch = $this->db->query($sql);
            $return = $fetch->fetchAll();
            $data = array();
            error_reporting(0);
            for ($i = 0;$i<count($return);$i++)
            {
                foreach ($return[$i] as $key => $value)
                {
                    $nama_key = $return[$i]['prj_kode'] . "-" . $return[$i]['prj_nama'];
                    $data[$nama_key]= ceil($return[$i]['total']);
                }
            }

            error_reporting(E_ALL ^ E_NOTICE);
            $xml= $this->leadHelper->buildChart($data,'Total Nilai Project','Project Name','Nilai Project','JavaScript:showDrillDown',true);
       }
        $this->getResponse()->setHeader('Content-Type', 'text/xml');
        $this->getResponse()->setBody($xml);

     
   }

  

   public function showdorAction()
   {

   }

 

   public function dorAction()
   {
       
   }

   public function doAction()
   {

   }

    public function alldoAction()
    {

    }

    public function alldorAction()
    {
        
    }

  public function adddoAction()
   {
   		$isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->DORtrano = $trano;
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   			$this->view->etc = $this->getRequest()->getParam("etc");
   		}
   }

   public function adddorAction()
   {
   		$isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->PRtrano = $trano;
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   			$this->view->etc = $this->getRequest()->getParam("etc");
   		}
   }

   public function getlastdorAction()
   {
   		$this->_helper->viewRenderer->setNoRender();
   		$number = $this->util->getLastNumber('DOR');
   		$number++;
   		echo "{ dor:'$number' }";
   }
   
   public function appdoAction()
   {
   		$type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/DO';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);

            $this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonEtc = $etc;
	       	$this->view->jsonResult = $json;

            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}
   		}
   		else
   		{
   			$docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
   			if ($docs)
   			{
   				$user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
   				if ($user || $show)
   				{
	   				$id = $docs['workflow_trans_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
		   			$prd = $this->logisticDo->fetchAll("trano = '$approve'")->toArray();
		   			$prh = $this->logisticDoH->fetchRow("trano = '$approve'");
		   			if ($prd)
		   			{
		   				foreach($prd as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$prd[$key]['uom'] = $barang['sat_kode'];
		   					}
		   					if ($val['val_kode'] == 'IDR')
		   						$prd[$key]['hargaIDR'] = $val['harga'];
		   					elseif ($val['val_kode'] == 'USD')
		   						$prd[$key]['hargaUSD'] = $val['harga'];
		   				}

		   				$jsonData2[0]['prj_kode'] = $prh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $prh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $prh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $prh['sit_nama'];
		   				$cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
				        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
				        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
				        $jsonData2[0]['trano'] = $approve;

				        $this->view->etc = $jsonData2;
		   				$this->view->result = $prd;
		   				$this->view->approve = true;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
		   			}
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
   
   public function appdorAction()
   {	
   		$type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/DOR';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';
   		
   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);



            $this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = $json;
	       	$this->view->jsonEtc = $etc;

            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
                $this->view->trano = $this->getRequest()->getParam("trano");
	   		    $this->view->tgl = $this->getRequest()->getParam("tgl");
	       	}
   		}
   		else
   		{
   			$docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
   			if ($docs)
   			{
   				$user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
   				if ($user || $show)
   				{
	   				$id = $docs['workflow_trans_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
		   			$prd = $this->logistic->fetchAll("trano = '$approve'")->toArray();
		   			$prh = $this->logisticH->fetchRow("trano = '$approve'");
		   			if ($prd)
		   			{
		   				foreach($prd as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$prd[$key]['uom'] = $barang['sat_kode'];
		   					}
		   					if ($val['val_kode'] == 'IDR')
		   						$prd[$key]['hargaIDR'] = $val['harga'];
		   					elseif ($val['val_kode'] == 'USD')
		   						$prd[$key]['hargaUSD'] = $val['harga'];
		   				}
		
		   				$jsonData2[0]['prj_kode'] = $prh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $prh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $prh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $prh['sit_nama'];

                        $jsonData2[0]['tgl'] = $prh['tgl'];

                       $jsonData2[0]['deliver_to'] = $prh['deliver_to'];
                       $jsonData2[0]['dest_nama'] = $prh['dest_nama'];
                       $jsonData2[0]['from_nama'] = $prh['from_nama'];
                       $jsonData2[0]['alamat'] = $prh['alamat'];
                       $jsonData2[0]['alamat1'] = $prh['alamat1'];

                       $jsonData2[0]['receiver_nama'] = $prh['receiver_nama'];
                       $jsonData2[0]['receiver_tlp'] = $prh['receiver_tlp'];

		   				$cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
				        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];      
				        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];      
				        $jsonData2[0]['trano'] = $approve; 
		                $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;   
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $prd;
		   				$this->view->approve = true;
                        $this->view->trano = $approve;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
		   			}
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


   public function insertdorAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
   	   $etc = str_replace("\\","",$etc);

       $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

       $tgl = date('Y-m-d');
       $counter = new Default_Models_MasterCounter();
        
	   $lastTrans = $counter->getLastTrans('DOR');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'DOR-' . $last;

       $items = $jsonData2[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');


       $result = $this->workflow->setWorkflowTrans($trano,'DOR','',$this->const['DOCUMENT_SUBMIT'],$items);
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
	   $where = "id=".$lastTrans['id'];
       $counter->update(array("urut" => $last),$where);
       $urut = 1;

       foreach($jsonData as $key => $val)
       {
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => $tgl,
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "val_kode" => $val['val_kode'],
				"pr_no" => $val['pr_number']

			);
            $urut++;
			//var_dump($arrayInsert);
            $this->logistic->insert($arrayInsert);
       }
        $deliver_to = $jsonData2[0]['deliver_to'];
        $dest_kode = $jsonData2[0]['dest_kode'];
        $dest_nama = $jsonData2[0]['dest_nama'];
        $from_kode = $jsonData2[0]['from_kode'];
        $from_nama = $jsonData2[0]['from_nama'];
        $prno = $jsonData[0]['pr_number'];
        
        $recv_nama = $jsonData2[0]['receiver_nama'];
        $recv_tlp = $jsonData2[0]['receiver_tlp'];
        $alamat = $jsonData2[0]['alamat'];
        $alamat1 = $jsonData2[0]['alamat1'];
       
        	$arrayInsert = array (
                "trano" => $trano,
                "pr_no" => $prno,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['prj_nama'],
                "dest_kode" => $dest_kode,
                "dest_nama" => $dest_nama,
                "from_kode" => $from_kode,
                "from_nama" => $from_nama,
                "deliver_to" => $deliver_to,
                "receiver_nama" => $recv_nama,
                "receiver_tlp" => $recv_tlp,
                "alamat" => $alamat,
                "alamat1" => $alamat1,
				"tgl" => $tgl,
				"pr_no" => $val['pr_number']                    
            );
      
            $this->logisticH->insert($arrayInsert);
	        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

   public function editdorAction()
   {

   	    $trano = $this->getRequest()->getParam("trano");
   		$dord = $this->logistic->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$dorh = $this->logisticH->fetchRow("trano = '$trano'");
        if ($dorh)
            $dorh = $dorh->toArray();
        $tmp = array();   

       $prArray = array();
   			foreach($dord as $key => $val)
   			{
                foreach ($val as $key2 => $val2)
                {
                    if ($val2 == '""')
                        $dord[$key][$key2] = '';
                }
   				$dord[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
                $dord[$key]['trano'] = $dord[$key]['pr_no'];
                $dord[$key]['pr_number'] = $dord[$key]['pr_no'];
                if(!in_array($dord[$key]['trano'],$tmp))
                    $tmp['trano'] = $dord[$key]['trano'];
                $prArray[] = $dord[$key]['pr_no'];   
                unset($dord[$key]['pr_no']);
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$dord[$key]['uom'] = $barang['sat_kode'];
   				}

   			}
            foreach($dorh as $key => $val)
   			{
                if ($val == '""')
                    $dorh[$key] = '';
            }
            $tmp2 = $dorh;
            unset($dorh);
            $dorh[0] = $tmp2;
   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($dord);
   			$jsonData2 = Zend_Json::encode($dorh);

   			$isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel)
            {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
                $this->view->jsonEtc = $this->getRequest()->getParam("etc");
            }
            else
           {
                $this->view->json = $jsonData;
                $this->view->jsonEtc = $jsonData2;
           }
            $this->view->prNo = $tmp;
	       	$this->view->trano = $trano;
	       	$this->view->tgl = date('d-m-Y',strtotime($dorh[0]['tgl']));
//            $this->view->pr_no = $dorh[0]['pr_no'];
           $this->view->pr_no = $prArray;
            $dorh[0]['ket'] = preg_replace("[^A-Za-z0-9-.,]","",$dorh[0]['ket']);
            $this->view->ket =preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $dorh[0]['ket']);
//            $this->view->ket = $dorh[0]['ket'];

   }

   public function updatedorAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
   	   $etc = $jsonData = Zend_Json::decode($this->getRequest()->getParam('etc'));
       $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));

       $trano = $etc[0]['trano'];
       $items = $etc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
      
        $result = $this->workflow->setWorkflowTrans($trano,'DOR','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
       $tgl = $etc[0]['tgl'];
       $deliver_to = $etc[0]['deliver_to'];
        $dest_kode = $etc[0]['dest_kode'];
        $dest_nama = $etc[0]['dest_nama'];
        $from_kode = $etc[0]['from_kode'];
        $from_nama = $etc[0]['from_nama'];

        $recv_nama = $etc[0]['receiver_nama'];
        $recv_tlp = $etc[0]['receiver_tlp'];
        $alamat = $etc[0]['alamat'];
        $alamat1 = $etc[0]['alamat1'];
        $this->logistic->delete("trano='$trano'");
       foreach($jsonData as $key => $val)
       {
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['prj_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "val_kode" => $val['val_kode'],
				"pr_no" => $val['pr_number']

			);
			//var_dump($arrayInsert);
            $this->logistic->insert($arrayInsert);
       }
        	$arrayInsert = array (
                "trano" => $trano,
				"tgl" => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['prj_nama'],
                "dest_kode" => $dest_kode,
                "dest_nama" => $dest_nama,
                "from_kode" => $from_kode,
                "from_nama" => $from_nama,
                "deliver_to" => $deliver_to,
                "receiver_nama" => $recv_nama,
                "receiver_tlp" => $recv_tlp,
                "alamat" => $alamat,
                "alamat1" => $alamat1,
				"tgl" => $tgl,
				"pr_no" => $val['pr_number']
            );

            $this->logisticH->delete("trano='$trano'");
            $this->logisticH->insert($arrayInsert);
	        $this->getResponse()->setBody("{success: true}");
    }

   public function insertdoAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
   	   $etc = str_replace("\\","",$etc);
       $project = $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('project'); 
       $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

       $tgl = date('Y-m-d');
       $counter = new Default_Models_MasterCounter();

	   $lastTrans = $counter->getLastTrans('DO');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'DO01-' . $last;

       $where = "id=".$lastTrans['id'];
       $counter->update(array("urut" => $last),$where);
       $urut = 1;

        $transnama = $jsonData2[0]['trans_nama'];
        $transkode = $jsonData2[0]['trans_kode'];
        $transalamat = $jsonData2[0]['transalamat'];
        $transtlp = $jsonData2[0]['transtlp'];
        $transfax = $jsonData2[0]['transfax'];
        $transcontact = $jsonData2[0]['transcontact'];
        $transhp = $jsonData2[0]['transhp'];
        $prjKode = $jsonData[0]['prj_kode'];
        $deliverto = $jsonData2[0]['dest_nama'] . ", " . $jsonData2[0]['alamat'];
        $deliveryfrom = $jsonData2[0]['from_nama'];
        $receivernama = $jsonData2[0]['receiver_nama'];
       $customer = $project->getProjectDetail($prjKode);
       if ($customer)
       {
           $cus_kode = $customer['cus_kode'];
       }

       foreach($jsonData as $key => $val)
       {
           $dordata = $this->logisticH->fetchRow("trano = '{$val['dor_no']}'");
           if ($dordata)
           {
               $kode_gudang = $dordata['from_kode'];
           }
           $kodeBrg = $val['kode_brg'];
           $cekBarang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
           if ($cekBarang)
           {
               $hargaAVG = $cekBarang['hargaavg'];
           }
           else
               $hargaAVG = 0;

			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['prj_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "ket" => $val['ket'],
                "qty" => $val['qty'],
                "harga" => $hargaAVG,
                "total" => ($hargaAVG * floatval($val['qty'])),
                "petugas" => $this->session->userName,
                "mdi_no" => $val['dor_no'],
                "gdg_kode" => $kode_gudang

			);
            $this->logisticDo->insert($arrayInsert);
       }

        	$arrayInsert = array (
                "trano" => $trano,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['prj_nama'],
                "cus_kode" => $cus_kode,
                "trans_kode" => $transkode,
                "deliveryto" => $deliverto,
                "namatransporter" => $transnama,
                "transalamat" => $transalamat,
                "contactpersonto" => $receivernama,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => time('H:i:s'),
                "transtlp" => $transtlp,
                "transhp" => $transhp,
                "transfax" => $transfax,
                "transcontact" => $transcontact,
                "deliveryfrom" => $deliveryfrom,
				"tgl" => $tgl,
                "mdi_no" => $val['dor_no'],
                "gdg_kode" => $kode_gudang
            );

            $this->logisticDoH->insert($arrayInsert);
       	    $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

   public function editdoAction()
   {

   	    $trano = $this->getRequest()->getParam("trano");
   		$dod = $this->logisticDo->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$doh = $this->logisticDoH->fetchRow("trano = '$trano'");
        if ($doh)
            $doh = $doh->toArray();
        $tmp = array();

   			foreach($dod as $key => $val)
   			{
                foreach ($val as $key2 => $val2)
                {
                    if ($val2 == '""')
                        $dod[$key][$key2] = '';
                }
   				$dod[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
                $dod[$key]['trano'] = $dod[$key]['mdi_no'];
                if(!in_array($dod[$key]['trano'],$tmp))
                    $tmp['trano'] = $dod[$key]['trano'];
                unset($dod[$key]['mdi_no']);
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$dod[$key]['uom'] = $barang['sat_kode'];
   				}

   			}
            foreach($doh as $key => $val)
   			{
                if ($val == '""')
                    $doh[$key] = '';
            }
            $tmp2 = $doh;
            unset($doh);
            $doh[0] = $tmp2;
   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($dod);
   			$jsonData2 = Zend_Json::encode($doh);

   			$isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel)
            {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
                $this->view->jsonEtc = $this->getRequest()->getParam("etc");
            }
            else
           {
                $this->view->json = $jsonData;
                $this->view->jsonEtc = $jsonData2;
           }
            $this->view->dorNo = $tmp;
	       	$this->view->trano = $trano;
	       	$this->view->tgl = date('d-m-Y',strtotime($doh[0]['tgl']));
            $this->view->dor_no = $doh[0]['mdi_no'];

            $doh[0]['ket'] = preg_replace("[^A-Za-z0-9-.,]","",$doh[0]['ket']);
            $this->view->ket =preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $doh[0]['ket']);
//            $this->view->ket = $doh[0]['ket'];

   }

   public function updatedoAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
        $project = $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('project'); 
       $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

       $trano = $jsonData2[0]['trano'];
       $tgl =$jsonData2[0]['tgl'];
       $transnama = $jsonData2[0]['trans_nama'];
        $transkode = $jsonData2[0]['trans_kode'];
        $transalamat = $jsonData2[0]['transalamat'];
        $transtlp = $jsonData2[0]['transtlp'];
        $transfax = $jsonData2[0]['transfax'];
        $transcontact = $jsonData2[0]['transcontact'];
        $transhp = $jsonData2[0]['transhp'];
        $prjKode = $jsonData[0]['prj_kode'];
        $deliverto = $jsonData2[0]['dest_nama'] . ", " . $jsonData2[0]['alamat'];
        $deliveryfrom = $jsonData2[0]['from_nama'];
        $receivernama = $jsonData2[0]['receiver_nama'];
       $customer = $project->getProjectDetail($prjKode);
       if ($customer)
       {
           $cus_kode = $customer['cus_kode'];
       }
        $this->logisticDo->delete("trano='$trano'");
       foreach($jsonData as $key => $val)
       {
           $dordata = $this->logisticH->fetchRow("trano = '{$val['dor_no']}'");
           if ($dordata)
           {
               $kode_gudang = $dordata['from_kode'];
           }
           $kodeBrg = $val['kode_brg'];
              $cekBarang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
              if ($cekBarang)
              {
                  $hargaAVG = $cekBarang['hargaavg'];
              }
              else
                  $hargaAVG = 0;
           
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['prj_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $hargaAVG,
                "total" => ($hargaAVG * floatval($val['qty'])),
                "harga" => $val['harga'],
                "val_kode" => $val['val_kode'],
				"mdi_no" => $val['dor_no'],
                "gdg_kode" => $kode_gudang

			);
            $this->logisticDo->insert($arrayInsert);
       }
        	$arrayInsert = array (
                "trano" => $trano,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['prj_nama'],
                "cus_kode" => $cus_kode,
                "trans_kode" => $transkode,
                "deliveryto" => $deliverto,
                "namatransporter" => $transnama,
                "transalamat" => $transalamat,
                "contactpersonto" => $receivernama,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => time('H:i:s'),
                "transtlp" => $transtlp,
                "transhp" => $transhp,
                "transfax" => $transfax,
                "transcontact" => $transcontact,
                "deliveryfrom" => $deliveryfrom,
				"tgl" => $tgl,
                "mdi_no" => $val['dor_no'],
                "gdg_kode" => $kode_gudang
            );

            $this->logisticDoH->delete("trano='$trano'");
            $this->logisticDoH->insert($arrayInsert);
       	    $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: true}");
    }

    public function inserticanAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
   	   $etc = str_replace("\\","",$etc);

       $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

       $tgl = date('Y-m-d');
       $counter = new Default_Models_MasterCounter();

	   $lastTrans = $counter->getLastTrans('iCAN');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'i-Can01-' . $last;

       $result = $this->workflow->setWorkflowTrans($trano,'iCAN','',$this->const['DOCUMENT_SUBMIT']);
       $this->getResponse()->setHeader('Content-Type', 'text/javascript');
       if (is_numeric($result))
       {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
       }

	   $where = "id=".$lastTrans['id'];
       $counter->update(array("urut" => $last),$where);
       $urut = 1;

       $totals = 0;

      foreach($jsonData as $key => $val)
    		{
          $total = $val['totalPrice'];
          $totals += $total;
      }

       foreach($jsonData as $key => $val)
       {
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['price'],
                "total" => $val['totalPrice'],
                "ket" => $val['ket'],
                "val_kode" => $val['val_kode'],
                "rateidr" => QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate()
			);
			//var_dump($arrayInsert);
            $this->ican->insert($arrayInsert);
       }
            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
        	$arrayInsert = array (
                "trano" => $trano,
                "tgl" => $tgl,
                "prj_kode" => $jsonData2[0]['prj_kode'],
                "prj_nama" => $jsonData2[0]['prj_nama'],
                "sit_kode" => $jsonData2[0]['sit_kode'],
                "sit_nama" => $jsonData2[0]['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $totals,
                "cus_kode" => $cusKode,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => time('H:i:s'),
				"delivery" => $jsonData2[0]['delivery'],
				"receive" => $jsonData2[0]['receive']
            );

            $this->icanH->insert($arrayInsert);
            $this->getResponse()->setBody("{success: true}");
    }

    public function updateicanAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');

       $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

       $trano = $jsonData2[0]['trano'];
         $result = $this->workflow->setWorkflowTrans($trano,'iCAN','',$this->const['DOCUMENT_RESUBMIT']);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result))
        {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }
       $totals = 0;

      foreach($jsonData as $key => $val)
    		{
          $total = $val['totalPrice'];
          $totals += $total;
      }

        $this->ican->delete("trano='$trano'");
       foreach($jsonData as $key => $val)
       {
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['price'],
                "total" => $val['totalPrice'],
                "ket" => $val['ket'],
                "val_kode" => $val['val_kode']

			);
			//var_dump($arrayInsert);
            $this->ican->insert($arrayInsert);
       }
            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
        	$arrayInsert = array (
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "prj_kode" => $jsonData2[0]['prj_kode'],
                "prj_nama" => $jsonData2[0]['prj_nama'],
                "sit_kode" => $jsonData2[0]['sit_kode'],
                "sit_nama" => $jsonData2[0]['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $totals,
                "cus_kode" => $cusKode,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => time('H:i:s'),
				"delivery" => $jsonData2[0]['delivery'],
				"receive" => $jsonData2[0]['receive']
            );

            $this->icanH->delete("trano='$trano'");
            $this->icanH->insert($arrayInsert);
            $this->getResponse()->setBody("{success: true}");
    }

    public function insertilovAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
   	   $etc = str_replace("\\","",$etc);

       $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

       $tgl = date('Y-m-d');
       $counter = new Default_Models_MasterCounter();

	   $lastTrans = $counter->getLastTrans('iLOV');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'i-Lov01-' . $last;
       $result = $this->workflow->setWorkflowTrans($trano,'iLOV','',$this->const['DOCUMENT_SUBMIT']);
       $this->getResponse()->setHeader('Content-Type', 'text/javascript');
       if (is_numeric($result))
       {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
       }

	   $where = "id=".$lastTrans['id'];
       $counter->update(array("urut" => $last),$where);
       $urut = 1;

       $totals = 0;

      foreach($jsonData as $key => $val)
    		{
          $total = $val['totalPrice'];
          $totals += $total;
      }

       foreach($jsonData as $key => $val)
       {
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['price'],
                "total" => $val['totalPrice'],
                "ket" => $val['ket'],
                "val_kode" => $val['val_kode'],
                "myob" => $val['net_act'],
                "rateidr" => QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate()
			);
			//var_dump($arrayInsert);
            $this->ilov->insert($arrayInsert);
       }
            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
        	$arrayInsert = array (
                "trano" => $trano,
                "tgl" => $tgl,
                "prj_kode" => $jsonData2[0]['prj_kode'],
                "prj_nama" => $jsonData2[0]['prj_nama'],
                "sit_kode" => $jsonData2[0]['sit_kode'],
                "sit_nama" => $jsonData2[0]['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $totals,
                "cus_kode" => $cusKode,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => time('H:i:s'),
				"delivery" => $jsonData2[0]['delivery'],
				"receive" => $jsonData2[0]['receive']
            );

            $this->ilovH->insert($arrayInsert);
            $this->getResponse()->setBody("{success: true}");
    }

    public function updateilovAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');

       $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

       $trano = $jsonData2[0]['trano'];
         $result = $this->workflow->setWorkflowTrans($trano,'iLOV','',$this->const['DOCUMENT_RESUBMIT']);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result))
        {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }
       $totals = 0;

      foreach($jsonData as $key => $val)
    		{
          $total = $val['totalPrice'];
          $totals += $total;
      }

        $this->ilov->delete("trano='$trano'");
       foreach($jsonData as $key => $val)
       {
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['price'],
                "total" => $val['totalPrice'],
                "ket" => $val['ket'],
                "val_kode" => $val['val_kode'],
                "myob" => $val['net_act']

			);
			//var_dump($arrayInsert);
            $this->ilov->insert($arrayInsert);
       }
            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
        	$arrayInsert = array (
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "prj_kode" => $jsonData2[0]['prj_kode'],
                "prj_nama" => $jsonData2[0]['prj_nama'],
                "sit_kode" => $jsonData2[0]['sit_kode'],
                "sit_nama" => $jsonData2[0]['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $totals,
                "cus_kode" => $cusKode,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => time('H:i:s'),
				"delivery" => $jsonData2[0]['delivery'],
				"receive" => $jsonData2[0]['receive']
            );

            $this->ilovH->delete("trano='$trano'");
            $this->ilovH->insert($arrayInsert);
	        $this->getResponse()->setBody("{success: true}");
    }

    public function ilovAction()
    {

    }

    public function icanAction()
    {
        
    }

    public function isuppAction()
    {

    }

    public function addilovAction()
    {

    }

    public function addicanAction()
    {
        
    }
    
    public function addisuppAction()
    {

    }

    public function editicanAction()
    {
       $trano = $this->getRequest()->getParam("trano");
   		$icand = $this->ican->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$icanh = $this->icanH->fetchRow("trano = '$trano'");

   if ($icand)
   		{


   			foreach($icand as $key => $val)
   			{
   				$icand[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$icand[$key]['uom'] = $barang['sat_kode'];
   				}
                   $icand[$key]['price'] = $val['harga'];
                   $icand[$key]['totalPrice'] = $val['total'];

   			}

   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($icand);
   			$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	       		$this->view->json = $jsonData;

	       	$this->view->trano = $trano;
	       	$this->view->tgl = $icanh['tgl'];
	       	$this->view->prjKode = $icanh['prj_kode'];
	       	$this->view->prjNama = $icanh['prj_nama'];
	       	$this->view->sitkode = $icanh['sit_kode'];
	        $this->view->sitNama = $icanh['sit_nama'];
	       	$this->view->delivery = $icanh['delivery'];
	       	$this->view->receive = $icanh['receive'];
   		}
    }

    public function editilovAction()
    {
       $trano = $this->getRequest()->getParam("trano");
   		$ilovd = $this->ilov->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$ilovh = $this->ilovH->fetchRow("trano = '$trano'");

   if ($ilovd)
   		{
   			foreach($ilovd as $key => $val)
   			{
   				$ilovd[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$ilovd[$key]['uom'] = $barang['sat_kode'];
   				}
                   $ilovd[$key]['price'] = $val['harga'];
                   $ilovd[$key]['totalPrice'] = $val['total'];
                   $ilovd[$key]['net_act'] = $val['myob'];

   			}

   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($ilovd);
   			$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	       		$this->view->json = $jsonData;

	       	$this->view->trano = $trano;
	       	$this->view->tgl = $ilovh['tgl'];
	       	$this->view->prjKode = $ilovh['prj_kode'];
	       	$this->view->prjNama = $ilovh['prj_nama'];
	       	$this->view->sitkode = $ilovh['sit_kode'];
	        $this->view->sitNama = $ilovh['sit_nama'];
	       	$this->view->delivery = $ilovh['delivery'];
	       	$this->view->receive = $ilovh['receive'];
   		}
    }

    public function appicanAction()
    {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/iCAN';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);

            $this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = $json;
	       	$this->view->jsonEtc = $etc;

            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}
   		}
   		else
   		{
   			$docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
   			if ($docs)
   			{
   				$user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
   				if ($user || $show)
   				{
	   				$id = $docs['workflow_trans_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
		   			$icand = $this->ican->fetchAll("trano = '$approve'")->toArray();
		   			$icanh = $this->icanH->fetchRow("trano = '$approve'");
		   			if ($icand)
		   			{
		   				foreach($icand as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$icand[$key]['uom'] = $barang['sat_kode'];
		   					}
		   					$icand[$key]['price'] = $val['harga'];
                            $icand[$key]['totalPrice'] = $val['total'];
		   				}

		   				$jsonData2[0]['prj_kode'] = $icanh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $icanh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $icanh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $icanh['sit_nama'];

				        $jsonData2[0]['tgl'] = $icanh['tgl'];
				        $jsonData2[0]['delivery'] = $icanh['delivery'];
                        $jsonData2[0]['receive'] = $icanh['receive'];
				        $jsonData2[0]['trano'] = $approve;
                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;   
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $icand;
		   				$this->view->approve = true;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
		   			}
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

    public function appilovAction()
    {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/iLOV';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);

            $this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = $json;
	       	$this->view->jsonEtc = $etc;

            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}
   		}
   		else
   		{
   			$docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
   			if ($docs)
   			{
   				$user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
   				if ($user || $show)
   				{
	   				$id = $docs['workflow_trans_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
		   			$ilovd = $this->ilov->fetchAll("trano = '$approve'")->toArray();
		   			$ilovh = $this->ilovH->fetchRow("trano = '$approve'");
		   			if ($ilovd)
		   			{
		   				foreach($ilovd as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$ilovd[$key]['uom'] = $barang['sat_kode'];
		   					}
		   					$ilovd[$key]['price'] = $val['harga'];
                            $ilovd[$key]['totalPrice'] = $val['total'];
		   				}

		   				$jsonData2[0]['prj_kode'] = $ilovh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $ilovh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $ilovh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $ilovh['sit_nama'];

				        $jsonData2[0]['tgl'] = $ilovh['tgl'];
				        $jsonData2[0]['delivery'] = $ilovh['delivery'];
                        $jsonData2[0]['receive'] = $ilovh['receive'];
				        $jsonData2[0]['trano'] = $approve;
                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;   
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $ilovd;
		   				$this->view->approve = true;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
		   			}
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

    public function supplierAction()
    {
        
    }

    public function viewsupplierAction ()
    {
        
    }

    public function getviewsupplierAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $txtsearch = $this->getRequest()->getParam('search');
        $option = $this->getRequest()->getParam('option');
        $search = null;

        if($txtsearch == "" || $txtsearch == null)
        {
            $search == null;
        }else if ($txtsearch != null && $option == 1)
        {
            $search = "sup_kode like '%$txtsearch%' ";
        }else if ($txtsearch != null && $option == 2)
        {
            $search = "sup_nama like '%$txtsearch%' ";
        }else if ($txtsearch != null && $option == 3)
        {
            $search = "alamat like '%$txtsearch%' ";
        }else if ($txtsearch != null && $option == 4)
        {
            $search = "tlp like '%$txtsearch%' ";
        }else if ($txtsearch != null && $option == 5)
        {
            $search = "email like '%$txtsearch%' ";
        }else if ($txtsearch != null && $option == 6)
        {
            $search = "fax like '%$txtsearch%' ";
        }else if ($txtsearch != null && $option == 7)
        {
            $search = "ket like '%$txtsearch%' ";
        }else if ($txtsearch != null && $option == 8)
        {
            $search = "statussupplier like '%$txtsearch%' ";
        }else
        {
            $search == null;
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 30;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $supplierdata = $this->supplier->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        $return['data'] = $supplierdata;
        $return['total'] = $this->supplier->fetchAll()->count();

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }


    public function listmaterialAction()
    {
        
    }

    public function addsuppAction()
    {
        $isCancel = $this->getRequest()->getParam("returnback");
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   			$this->view->file = $this->getRequest()->getParam("file");
          
//            if ($file)
//                $file = $file->toArray();
//            else
//                $file = array();
   		}
    }

    public function insertsuppAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
   	   $etc = str_replace("\\","",$etc);
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
      $jsonFile = Zend_Json::decode($file);

//       var_dump($jsonData2);die;

//       var_dump($jsonData2);die;

       $prjKode = 'SUP';
       $trano = 'VDR';

       $lastTrans = $this->counter->fetchRow("tra_no = '$trano'");

       $last = intval($lastTrans['urut']);
       $last = $last + 1;
       $trano = $trano . $last;

       $insertcounter = array(
           "urut" => $last
       );

       $this->counter->update($insertcounter,"tra_no = 'VDR'");

//	   $lastTrans = $this->supplier->getLastSupkode();
//	   $last = abs($lastTrans['last']);
//	   $last = $last + 1;
//	   $trano = 'VDR' . $last;

//       $items = $jsonData2[0];
       $items['prj_kode'] = $prjKode;
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'SUPP','',$this->const['DOCUMENT_SUBMIT'],$items);

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
                "sup_kode" => $trano,
                "sup_nama" => $jsonData2[0]['nama'],
                "alamat" => $jsonData2[0]['alamat'],
                "tlp" => $jsonData2[0]['telp'],
                "fax" => $jsonData2[0]['fax'],
                "ket" => $jsonData2[0]['ket'],
                "alamat2" => $jsonData2[0]['alamat2'],
                "master_kota" => $jsonData2[0]['city'],
                "negara" => $jsonData2[0]['country'],
                "orang" => $jsonData2[0]['contact'],
                "statussupplier" => $jsonData2[0]['status'],
				"namabank" => $jsonData2[0]['bank'],
				"rekbank" => $jsonData2[0]['account_no'],
                "reknamabank" => $jsonData2[0]['account_name'],
                "jenisupplier" => $jsonData2[0]['type'],
                "subjenisupplier" => $jsonData2[0]['spec'],
                "email" => $jsonData2[0]['email'],
                "tahunoperasi" => $jsonData2[0]['thn_operasi'],
                "tahunberdiri" => $jsonData2[0]['thn_berdiri'],
                "cabang" => $jsonData2[0]['branch_total'],
                "skala" => $jsonData2[0]['scale'],
                "daftarcabang" => $jsonData2[0]['branch_list'],
                "karyawan" => $jsonData2[0]['total_employee'],
                "aktif" => 'N',
                "npwp" => $jsonData2[0]['npwp'],
                "pkp" => $jsonData2[0]['pkp'],
                "tgl_pkp" => $jsonData2[0]['pkp_date'],
                "date" => date("Y-m-d H:i:s"),
                "uid" => $this->session->userName,
                "finance" => $jsonData2[0]['finance'],
                "direktur" => $jsonData2[0]['direktur']
                
            );

//            var_dump($arrayInsert);die();
            $this->supplier->insert($arrayInsert);

            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $prjKode,
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

    public function updatesuppAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');

       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
       $file = $this->getRequest()->getParam('file');

//       var_dump($jsonData2);die;

      $jsonFile = Zend_Json::decode($file);

       $trano = $jsonData2[0]['trano'];

       $prjKode = 'SUP';
       $items = $jsonData2[0];
       $items['prj_kode'] = $prjKode;
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'SUPP','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
//                "sup_kode" => $trano,
                "sup_nama" => $jsonData2[0]['nama'],
                "alamat" => $jsonData2[0]['alamat'],
                "tlp" => $jsonData2[0]['telp'],
                "fax" => $jsonData2[0]['fax'],
                "ket" => $jsonData2[0]['ket'],
                "alamat2" => $jsonData2[0]['alamat2'],
                "master_kota" => $jsonData2[0]['city'],
                "negara" => $jsonData2[0]['country'],
                "orang" => $jsonData2[0]['contact'],
                "statussupplier" => $jsonData2[0]['status'],
				"namabank" => $jsonData2[0]['bank'],
				"rekbank" => $jsonData2[0]['account_no'],
                "reknamabank" => $jsonData2[0]['account_name'],
                "jenisupplier" => $jsonData2[0]['type'],
                "subjenisupplier" => $jsonData2[0]['spec'],
                "email" => $jsonData2[0]['email'],
                "tahunoperasi" => $jsonData2[0]['thn_operasi'],
                "tahunberdiri" => $jsonData2[0]['thn_berdiri'],
                "cabang" => $jsonData2[0]['branch_total'],
                "skala" => $jsonData2[0]['scale'],
                "daftarcabang" => $jsonData2[0]['branch_list'],
                "karyawan" => $jsonData2[0]['total_employee'],
                "npwp" => $jsonData2[0]['npwp'],
                "pkp" => $jsonData2[0]['pkp'],
                "tgl_pkp" => $jsonData2[0]['pkp_date'],
                "aktif" => 'N',
                "finance" => $jsonData2[0]['finance'],
                "direktur" => $jsonData2[0]['direktur']
            );

            $this->supplier->update($arrayInsert,"sup_kode='$trano'");
//            $this->supplier->insert($arrayInsert);

            $this->files->delete("trano='$trano'");
            if (count($jsonFile) > 0)
            {

                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $prjKode,
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

    public function editsuppAction()
    {
        $trano = $this->getRequest()->getParam("sup_kode");
        $file = $this->files->fetchAll("trano = '$trano'");
      
        if ($file)
            $file = $file->toArray();
        else
            $file = array();

   		$supp = $this->supplier->fetchRow("sup_kode = '$trano'")->toArray();

                $this->view->trano = $trano;
                $this->view->supNama = $supp['sup_nama'];
                $this->view->address = $supp['alamat'];
                $this->view->telp = $supp['tlp'];
                $this->view->fax = $supp['fax'];
                $this->view->ket = $supp['ket'];
                $this->view->address2 = $supp['alamat2'];
                $this->view->city = $supp['master_kota'];
                $this->view->country = $supp['negara'];
                $this->view->contact = $supp['orang'];
                $this->view->status = $supp['statussupplier'];
                $this->view->bank = $supp['namabank'];
                $this->view->accountName = $supp['rekbank'];
                $this->view->accountNo = $supp['reknamabank'];
                $this->view->type = $supp['jenisupplier'];
                $this->view->spec = $supp['subjenisupplier'];

                 $this->view->email = $supp['email'];
                 $this->view->thnOperasi = $supp['tahunoperasi'];
                 $this->view->thnBerdiri = $supp['tahunberdiri'];
                 $this->view->branchTotal = $supp['cabang'];
                 $this->view->scale = $supp['skala'];
                 $this->view->branchList = $supp['daftarcabang'];
                 $this->view->totalEmployee = $supp['karyawan'];
                 $this->view->finance = $supp['finance'];
                 $this->view->direktur = $supp['direktur'];

                if ($supp['pkp'] == 'Y')
                {
                    $this->view->pkp = true;
                }else{
                    $this->view->pkp = false;
                }

                $this->view->npwp = $supp['npwp'];

                if($supp['tgl_pkp'] == '0000-00-00')
                {
                    $this->view->pkp_date = '';
                }else{
                    $this->view->pkp_date = $supp['tgl_pkp'];
                }
        
            Zend_Loader::loadClass('Zend_Json');
   			$file = Zend_Json::encode($file);
	       	$this->view->file = $file;
    }

    public function appsuppAction()
    {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/SUPP';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	       	$jsonData2 = Zend_Json::decode($etc);
             $file = Zend_Json::decode($files);

//               var_dump($jsonData2);die;

            if ($jsonData2[0]['pkp'] == 'true')
            {
                $jsonData2[0]['pkp'] = 'YES';
            }else{
                $jsonData2[0]['pkp'] = 'NO';
            }

            if ($jsonData2[0]['pkp_date'] == '')
            {
                $jsonData2[0]['pkp_date'] = '';
            }else{
                $jsonData2[0]['pkp_date'] = date('Y-m-d',strtotime($jsonData2[0]['pkp_date']));
            }

           $jsonData2[0]['tgl'] = date('d-m-Y');
           $jsonData2[0]['uid'] = $this->session->userName;


//            var_dump($jsonData2);die;

	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonEtc = $etc;
            $this->view->jsonFile = $files;
	       	$this->view->file = $file;


            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}
   		}
   		else
   		{
   			$docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
   			if ($docs)
   			{
   				$user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
   				if ($user || $show)
   				{
	   				$id = $docs['workflow_trans_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;

		   			    $supp = $this->supplier->fetchRow("sup_kode = '$approve'");
                        $file = $this->files->fetchAll("trano = '$approve'");

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                       $jsonData2[0]['trano'] = $supp['sup_kode'];
                       $jsonData2[0]['status'] = $supp['statussupplier'];
		   				$jsonData2[0]['nama'] = $supp['sup_nama'];
		   				$jsonData2[0]['alamat'] = $supp['alamat'];
		   				$jsonData2[0]['alamat2'] = $supp['alamat2'];
		   				$jsonData2[0]['city'] = $supp['master_kota'];

				        $jsonData2[0]['country'] = $supp['negara'];
				        $jsonData2[0]['telp'] =$supp['tlp'];
                        $jsonData2[0]['fax'] = $supp['fax'];
                        $jsonData2[0]['bank'] = $supp['namabank'];
                         $jsonData2[0]['account_no'] = $supp['rekbank'];
                         $jsonData2[0]['account_name'] = $supp['reknamabank'];
                         $jsonData2[0]['type'] = $supp['jenisupplier'];
                        $jsonData2[0]['spec'] = $supp['subjenisupplier'];
                         $jsonData2[0]['contact'] = $supp['orang'];
                         $jsonData2[0]['ket'] = $supp['ket'];
				        $jsonData2[0]['sup_kode'] = $approve;

                        $jsonData2[0]['email'] = $supp['email'];
		   				$jsonData2[0]['thn_operasi'] = $supp['tahunoperasi'];
		   				$jsonData2[0]['thn_berdiri'] = $supp['tahunberdiri'];
		   				$jsonData2[0]['branch_total'] = $supp['cabang'];
                        $jsonData2[0]['scale'] = $supp['skala'];
		   				$jsonData2[0]['branch_list'] = $supp['daftarcabang'];
		   				$jsonData2[0]['total_employee'] = $supp['karyawan'];

                        $jsonData2[0]['pkp'] = $supp['pkp'];
                        $jsonData2[0]['npwp'] = $supp['npwp'];
                       $jsonData2[0]['finance'] = $supp['finance'];
                       $jsonData2[0]['direktur'] = $supp['direktur'];

                        if ($supp['tgl_pkp'] == '0000-00-00')
                        {
                            $jsonData2[0]['pkp_date'] = '';
                        }else{
                            $jsonData2[0]['pkp_date'] = $supp['tgl_pkp'];
                        }

                        $jsonData2[0]['uid'] = $supp['uid'];

//                        var_dump($supp['date']);die;

                        if ($supp['date'] == null)
                        {
                            $jsonData2[0]['tgl'] = '';
                        }else
                        {
                            $jsonData2[0]['tgl'] = $supp['date'];
                        }


		   				                       
//                        var_dump($jsonData2);die;

                        $this->view->approval = $userApp;

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

    public function insertisuppAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');

       $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
       $jsonEtc = Zend_Json::decode($this->getRequest()->getParam('etc'));

       $tgl = date('Y-m-d');
       $counter = new Default_Models_MasterCounter();

//	   $lastTrans = $counter->getLastTrans('iSUP');
//	   $last = abs($lastTrans['urut']);
//	   $last = $last + 1;
//	   $trano = 'i-Sup01-' . $last;

        $trano = $counter->setNewTrans('iSUP');

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'iSUP','',$this->const['DOCUMENT_SUBMIT'],$items);
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

	   $where = "id=".$lastTrans['id'];
       $counter->update(array("urut" => $last),$where);
       $urut = 1;

       $totals = 0;
        foreach($jsonData as $key => $val)
    		{
           $qty = $val['qty'];
           $price = $val['price'];
          $total = $qty * $price;
          $totals += $total;
        }

       foreach($jsonData as $key => $val)
       {
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => $tgl,
                "po_no" => $val['po_no'],
                "tglpo" => $val['tgl_po'],
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['price'],
                "rateidr" => $val['rateidr'],
                "total" => $val['totalPrice'],                
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "sup_kode" => $val['sup_kode'],
                "sup_nama" => $val['sup_nama'],
                "val_kode" => $val['val_kode'],
                "pr_no" => $val['pr_no'],
                "tglpr" => $val['tgl_pr']

			);
            $urut++;
			//var_dump($arrayInsert);
            $this->isupp->insert($arrayInsert);
       }
            $cusKode = $this->project->getProjectAndCustomer($jsonData[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
        	$arrayInsert = array (
                "trano" => $trano,
                "tgl" => $tgl,
                "po_no" => $jsonEtc[0]['po_no'],
                "tglpo" => $jsonData[0]['tgl_po'],
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonData[0]['sit_kode'],
                "sit_nama" => $jsonData[0]['sit_nama'],
                "ket" => $jsonEtc[0]['ket'],
                "myob" => $jsonEtc[0]['net_act'],
                "pomyob" => $jsonEtc[0]['pomyob'],
                "statusppn" => $jsonData[0]['statusppn'],
                "jumlah" => $totals,
                "total" => $totals,
                "cus_kode" => $cusKode,
                "val_kode" => $jsonData[0]['val_kode'],
                "sup_kode" => $jsonEtc[0]['sup_kode'],
                "sup_nama" => $jsonEtc[0]['sup_nama'],
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => time('H:i:s'),
                "gdg_kode" => $jsonEtc[0]['wh_kode'],
				"pr_no" => $jsonData[0]['pr_no'],
				"tglpr" => $jsonData[0]['tgl_pr']
            );

            $this->isuppH->insert($arrayInsert);
            $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateisuppAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');

       $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
       $jsonEtc = Zend_Json::decode($this->getRequest()->getParam('etc'));

       $trano = $jsonEtc[0]['trano'];

        $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
      
        $result = $this->workflow->setWorkflowTrans($trano,'iSUP','',$this->const['DOCUMENT_RESUBMIT'],$items);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result))
        {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }
       $urut = 1;

       $totals = 0;
        foreach($jsonData as $key => $val)
        {
          $qty = $val['qty'];
          $price = $val['price'];
          $total = $qty * $price;
          $totals += $total;
        }

        $this->isupp->delete("trano='$trano'");
       foreach($jsonData as $key => $val)
       {
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "po_no" => $val['po_no'],
                "tglpo" => $val['tgl_po'],
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['price'],
                "total" => $val['totalPrice'],
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "sup_kode" => $val['sup_kode'],
                "sup_nama" => $val['sup_nama'],
                "val_kode" => $val['val_kode'],
                "pr_no" => $val['pr_no'],
                "tglpr" => $val['tgl_pr']

			);
            $urut++;
			//var_dump($arrayInsert);
            $this->isupp->insert($arrayInsert);
       }
            $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
        	$arrayInsert = array (
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "po_no" => $jsonEtc[0]['po_no'],
                "tglpo" => $jsonData[0]['tgl_po'],
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonData[0]['sit_kode'],
                "sit_nama" => $jsonData[0]['sit_nama'],
                "ket" =>$jsonEtc[0]['ket'],
                "myob" =>$jsonEtc[0]['net_act'],
                "pomyob" =>$jsonEtc[0]['pomyob'],
                "statusppn" => $jsonData[0]['statusppn'],
                "jumlah" => $totals,
                "total" => $totals,
                "cus_kode" => $cusKode,
                "val_kode" => $jsonData[0]['val_kode'],
                "sup_kode" => $jsonEtc[0]['sup_kode'],
                "sup_nama" => $jsonEtc[0]['sup_nama'],
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => time('H:i:s'),
                "gdg_kode" => $jsonEtc[0]['wh_kode'],
				"pr_no" => $jsonData[0]['pr_no'],
				"tglpr" => $jsonData[0]['tgl_pr']
            );

            $this->isuppH->delete("trano='$trano'");
            $this->isuppH->insert($arrayInsert);
            $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    

    public function editisuppAction()
    {
        $trano = $this->getRequest()->getParam("trano");
   		$isuppd = $this->isupp->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$isupph = $this->isuppH->fetchRow("trano = '$trano'");

   if ($isuppd)
   		{
   			foreach($isuppd as $key => $val)
   			{
   				$iisuppd[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$isuppd[$key]['uom'] = $barang['sat_kode'];
   				}
                   $isuppd[$key]['price'] = $val['harga'];
                   $isuppd[$key]['totalPrice'] = $val['total'];

   			}
        }
   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($isuppd);
   			$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	       		$this->view->json = $jsonData;

	       	$this->view->trano = $trano;
	       	$this->view->tgl = $isupph['tgl'];
	       	$this->view->poNo = $isupph['po_no'];
            $this->view->prjKode = $isupph['prj_kode'];
            $this->view->prjNama = $isupph['prj_nama'];
            $this->view->supKode = $isupph['sup_kode'];
            $this->view->supNama = $isupph['sup_nama'];
	       	$this->view->netAct = $isupph['myob'];
	       	$this->view->poMyob = $isupph['pomyob'];
            $isupph['ket'] = preg_replace("[^A-Za-z0-9-.,]","",$isupph['ket']);
            $this->view->ket =preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $isupph['ket']);
//	        $this->view->ket = $isupph['ket'];
	       	$this->view->whKode = $isupph['gdg_kode'];
	       	


    }

    public function appisuppAction()
    {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/iSUP';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
            $json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);

            $this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = $json;
	       	$this->view->jsonEtc = $etc;
            
            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}
   		}
   		else
   		{
   			$docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
   			if ($docs)
   			{
   				$user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
   				if ($user || $show)
   				{
	   				$id = $docs['workflow_trans_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
                        $isuppd = $this->isupp->fetchAll("trano = '$approve'")->toArray();
		   			    $isuppH = $this->isuppH->fetchRow("trano = '$approve'");
                        $whNama = $this->trans->getSupplierName($isuppH['gdg_kode']);

                   if ($isuppd)
                   {
                       foreach($isuppd as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$isuppd[$key]['uom'] = $barang['sat_kode'];
		   					}
		   					
		   				}
                   }
                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                       
                        $jsonData2[0]['po_no'] = $isuppH['po_no'];
		   				$jsonData2[0]['sup_kode'] = $isuppH['sup_kode'];
		   				$jsonData2[0]['sup_nama'] = $isuppH['sup_nama'];
		   				$jsonData2[0]['net_act'] = $isuppH['myob'];
		   				$jsonData2[0]['pomyob'] = $isuppH['pomyob'];

				        $jsonData2[0]['ket'] = $isuppH['ket'];
				        $jsonData2[0]['wh_kode'] =$isuppH['gdg_kode'];
                        $jsonData2[0]['wh_nama'] = $whNama['gdg_nama'];
				        $jsonData2[0]['trano'] = $approve;
                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;   
				        $this->view->etc = $jsonData2;
                        $this->view->result = $isuppd;
		   				$this->view->approve = true;
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

    public function addmaterialAction()
    {

    }

    public function editmaterialAction()
    {
        $trano = $this->getRequest()->getParam("kode_brg");
        $ToS = new Default_Models_MasterTypeOfSuply;

   		$barang = $this->barang->fetchRow("kode_brg = '$trano'")->toArray();


        $tosNama = $ToS->getTosData($barang['tos_kode']);      
        $ktgKode = $barang['ktg_kode'];
        $sktgKode = $barang['sktg_kode'];
        $satKode = $barang['sat_kode'];

        $sql = "SELECT ktg_nama FROM master_kategori WHERE ktg_kode ='$ktgKode'";
        $fetch = $this->db->query($sql);
        $ktgNama = $fetch->fetch();

        $sql = "SELECT sktg_nama FROM master_kategorisub WHERE ktg_kode = '$ktgKode' AND sktg_kode ='$sktgKode'";
        $fetch = $this->db->query($sql);
        $sktgNama = $fetch->fetch();

        $sql = "SELECT sat_nama FROM master_satuan WHERE sat_kode ='$satKode'";
        $fetch = $this->db->query($sql);
        $satNama = $fetch->fetch();
        
                $this->view->kodeBrg = $trano;
                $this->view->tosKode = $barang['tos_kode'];
                $this->view->ktgKode = $ktgKode;
                $this->view->sktgKode = $sktgKode;
                $this->view->namaBrg = $barang['nama_brg'];
                $this->view->satKode = $satKode;
                $this->view->tosNama = $tosNama['tos_nama'];
                $this->view->ktgNama = $ktgNama['ktg_nama'];
                $this->view->sktgNama = $sktgNama['sktg_nama'];
                $this->view->satNama = $satNama['sat_nama'];
                $this->view->pmeal = $barang['stspmeal'];
                
    }

    public function getlastmaterialAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $tos_kode = $request->getParam('tos_kode');

        $sql = "SELECT geno2009 FROM master_typeofsuply WHERE tos_kode='$tos_kode'";
        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();

        $last = abs($data['geno2009']);
        $last = $last+1;
        $trano = $tos_kode . substr($last,1,5);
        $return = array("success" => true,"kode_brg" => $trano);

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function insertmaterialAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

//        var_dump($jsonData2);die;

        $cek = false;
        $kode = $jsonData2[0]['kode_brg'];
        while(!$cek)
        {
            $fetch = $this->barang->fetchRow("kode_brg = '$kode'");
            if (!$fetch)
            {
                $cek = true;
            }
            else
                $kode++;
        }

        $counter = new Default_Models_MasterTypeOfSuply;
	   $lastTrans = $counter->getTosData($jsonData2[0]['tos_kode']);
	   $last = abs($lastTrans['geno2009']);
	   $last = $last + 1;

       $where = "tos_kode=".$jsonData2[0]['tos_kode'];
       $counter->update(array("geno2009" => $last),$where);

        	$arrayInsert = array (
                "kode_brg" => $jsonData2[0]['kode_brg'],
                "nama_brg" => $jsonData2[0]['ket'],
                "sat_kode" => $jsonData2[0]['uom'],
                "ktg_kode" => $jsonData2[0]['ktg_kode'],
                "sktg_kode" => $jsonData2[0]['sktg_kode'],
                "tos_kode" => $jsonData2[0]['tos_kode'],
                "stspmeal" => $jsonData2[0]['stspmeal'],
                "harga_borong" => floatval($jsonData2[0]['price'])

            );

//            var_dump($arrayInsert);die();
            $this->barang->insert($arrayInsert);
//	        $arrayInsert = $this->workflow->convertFormat($arrayInsert,'sup_kode');
//            $result = $this->workflow->setWorkflowTrans($arrayInsert,'SUPP');
       	    $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: true}");
    }

    public function updatematerialAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');

       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
        
       $kodeBrg = $jsonData2[0]['kode_brg'];

        	$arrayInsert = array (
//                "kode_brg" => $jsonData2[0]['kode_brg'],
                "nama_brg" => $jsonData2[0]['ket'],
                "sat_kode" => $jsonData2[0]['uom'],
                "ktg_kode" => $jsonData2[0]['ktg_kode'],
                "sktg_kode" => $jsonData2[0]['sktg_kode'],
//                "tos_kode" => $jsonData2[0]['tos_kode']
                "stspmeal" => $jsonData2[0]['stspmeal']
            );

            $this->barang->update($arrayInsert, "kode_brg = '$kodeBrg'");
//	        $arrayInsert = $this->workflow->convertFormat($arrayInsert,'sup_kode');
//            $result = $this->workflow->setWorkflowTrans($arrayInsert,'SUPP');
       	    $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: true}");
    }

}

?>
