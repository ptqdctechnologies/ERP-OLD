<?php

class Procurement_ProcurementController extends Zend_Controller_Action
{
    private $procurement;
    private $procurementH;
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
    private $projectG;
    private $barang;
    private $const;
    private $purchase;
    private $purchaseH;
    private $rpi;
    private $rpiH;
    private $asf;
    private $asfc;
    private $asfD;
    private $asfH;
    private $trans;
    private $arfh;
    private $arfd;
    private $pmeal;
    private $pmealH;
    private $error;
    private $upload;
    private $files;
    private $budget;
    private $quantity;
    private $reimbursH;
    private $reimbursD;
    private $log;
    private $paymentreimbursH;
    private $requestcancel;
    
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->const = Zend_Registry::get('constant');
        //$this->leadHelper = $this->_helper->getHelper('chart');
        $this->upload = $this->_helper->getHelper('uploadfile');
		$this->workflow = $this->_helper->getHelper('workflow');
        $this->session = new Zend_Session_Namespace('login');
		$this->error = $this->_helper->getHelper('error');
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
        $this->arfh = new Default_Models_AdvanceRequestFormH();
        $this->arfd = new Default_Models_AdvanceRequestFormD();
        $this->reimbursH = new Default_Models_ReimbursH();
        $this->reimbursD = new Default_Models_ReimbursD();
        $this->procurement = new Default_Models_ProcurementRequest();
        $this->procurementH = new Default_Models_ProcurementRequestH();
        $this->purchase = new Default_Models_ProcurementPod();
        $this->purchaseH = new Default_Models_ProcurementPoh();
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->rpi = new Default_Models_RequestPaymentInvoice();
        $this->rpiH = new Default_Models_RequestPaymentInvoiceH();
        $this->asf = new Default_Models_AdvanceSettlementForm();
        $this->asfc = new Default_Models_AdvanceSettlementFormCancel();
        $this->asfD = new Default_Models_AdvanceSettlementFormD();
        $this->asfH = new Default_Models_AdvanceSettlementFormH();
        $this->pmeal = new Default_Models_PieceMeal();
        $this->pmealH = new Default_Models_PieceMealH();
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->trans = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Model_Workflow();
        $this->files = new Default_Models_Files();
//        $this->budget = $this->_helper->getHelper('budget');
        $this->budget = new Default_Models_Budget();
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->log = new Admin_Model_Logtransaction();
        $this->paymentreimbursH = new Procurement_Model_PaymentReimbursH();
        $this->requestcancel = new Procurement_Model_RequestCancel();
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
            case 'prh':
                $procurement = new Default_Models_ProcurementRequestH();
                $return['posts'] = $procurement->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
                $return['count'] = $procurement->fetchAll()->count();
            break;            
            case 'noprd':
                 $sql = "SELECT * FROM procurement_prd p order by tgl desc,trano desc limit 1";
                 $fetch = $this->db->query($sql);
                 $return['posts'] = $fetch->fetch();
                 $return['count'] = 1;
                break;
            case 'prd':
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
            case 'arfd':
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
                $sp = $this->db->prepare("call sp_boq3arf('$prj_kode','$sit_kode','$workid','$kode_brg')");
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

    public function pohAction()
    {
        
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

   public function showpoAction()
   {

   }

   public function showprAction()
   {

   }

   public function getpoAction()
   {

   }

   public function deletepoAction()
   {
       
   }

   public function insertprAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $file = $this->getRequest()->getParam('file');
       $etc = $this->getRequest()->getParam('etc');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);
       $jsonFile = Zend_Json::decode($file);
	   
//       $counter = new Default_Models_MasterCounter();
//
//	   $lastTrans = $counter->getLastTrans('PR');
//	   $last = abs($lastTrans['urut']);
//	   $last = $last + 1;
//	   $trano = 'PRF-' . $last;

       $urut = 1;

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "PR",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

//       $result = $this->workflow->setWorkflowTrans($trano,'PR','',$this->const['DOCUMENT_SUBMIT'],$items);
//       $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//       if (is_numeric($result))
//       {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//       }
//       elseif (is_array($result) && count($result) > 0)
//       {
//
//           $hasil = Zend_Json::encode($result);
//           $this->getResponse()->setBody("{success: true, user:$hasil}");
//           return false;
//       }

//       $ada = false;
//        while(!$ada)
//        {
//            $cek = $this->procurement->fetchRow("trano = '$trano'");
//            if ($cek)
//            {
//                $lastTrans = $counter->getLastTrans('PR');
//               $last = abs($lastTrans['urut']);
//               $last = $last + 1;
//               $trano = 'PRF-' . $last;
//            }
//            elseif (!$cek)
//                $ada = true;
//        }

//        $counter->update(array("urut" => $last),"id=".$lastTrans['id']);
       foreach($jsonData as $key => $val)
       {
       		if ($val['val_kode'] == 'IDR')
       			$harga = $val['hargaIDR'];
       		else
       			$harga = $val['hargaUSD'];	
       	
       		$total = floatval($val['qty']) * floatval($harga);
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
				"qty" => $val['qty'],
				"harga" => $harga,
				"jumlah" => $total,
				"ket" => $val['ket'],
				"petugas" => $this->session->userName,
				"val_kode" => $val['val_kode'],
                "myob" => $val['net_act'],
                "cfs_kode" => $val['net_act'],
			);
               $urut++;
			
            $this->procurement->insert($arrayInsert);
       }
            $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];       
            $arrayInsert = array (
            	"trano" => $trano,
                "tgl" => date('Y-m-d'),
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
				"budgettype" => $jsonEtc[0]['budgettype'],
				"petugas" => $this->session->userName,
				"cus_kode" => $cusKode,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s')
            );
            $this->procurementH->insert($arrayInsert);
       if (count($jsonFile) > 0)
        {
            foreach ($jsonFile as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
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

	public function updateprAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
       $file = $this->getRequest()->getParam('file');
   	   $etc = str_replace("\\","",$etc);
             
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);
       $jsonFile = Zend_Json::decode($file);
	   
       $trano = $jsonEtc[0]['trano'];
       $urut = 1;
       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano,'PR','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
        $log['pr-detail-before'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();
       $this->procurement->delete("trano = '$trano'");
       foreach($jsonData as $key => $val)
       {
       		if ($val['val_kode'] == 'IDR')
       			$harga = $val['hargaIDR'];
       		else
       			$harga = $val['hargaUSD'];

       		$total = $val['qty'] * $harga;
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
				"qty" => $val['qty'],
				"harga" => $harga,
				"jumlah" => $total,
				"ket" => $val['ket'],
				"petugas" => $this->session->userName,
				"val_kode" => $val['val_kode'],
                "myob" => $val['net_act'],
                "cfs_kode" => $val['net_act']
			);
               $urut++;

            $this->procurement->insert($arrayInsert);

       }
            $log2['pr-detail-after'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();
            $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
            $arrayInsert = array (
            	"trano" => $trano,
                "tgl" => date('Y-m-d'),
                "revisi" => $jsonEtc[0]['rev'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
				"budgettype" => $jsonEtc[0]['budgettype'],
				"petugas" => $this->session->userName,
				"cus_kode" => $cusKode,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s')
            );
            $log['pr-header-before'] = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
            $this->procurementH->delete("trano = '$trano'");
            $this->procurementH->insert($arrayInsert);
            $log2['pr-header-after'] = $this->procurementH->fetchRow("trano = '$trano'")->toArray();

            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "UPDATE",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);
            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonEtc[0]['prj_kode'],
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

    public function insertprbudgetAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
       $file = $this->getRequest()->getParam('file');
       $sales = $this->getRequest()->getParam('sales');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);
       $jsonFile = Zend_Json::decode($file);

       $counter = new Default_Models_MasterCounter();

       if($sales)
        {
           $lastTrans = $counter->getLastTrans('PR');
           $last = abs($lastTrans['urut']);
           $last = $last + 1;
           $trano = 'PRF-' . $last;
           $tipe = 'S';
        }
        else
        {
           $lastTrans = $counter->getLastTrans('PRO');
           $last = abs($lastTrans['urut']);
           $last = $last + 1;
           $trano = 'PR02-' . $last;
           $tipe = 'O';
        }

       $urut = 1;

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'PRO','',$this->const['DOCUMENT_SUBMIT'],$items);
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
       foreach($jsonData as $key => $val)
       {
       		
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" =>$jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
				"workid" => $val['budgetid'],
				"workname" => $val['budgetname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"qty" => $val['qty'],
				"harga" => $val['harga'],
				"jumlah" => $val['totalPrice'],
				"ket" => $val['ket'],
				"petugas" => $this->session->userName,
				"val_kode" => $val['val_kode'],
//                "coa_kode" => $val['coa_kode'],
                "cfs_kode" => $val['net_act'],
                "tipe" => $tipe
			);
               $urut++;

            $this->procurement->insert($arrayInsert);
       }
//            $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
//            $cusKode = $cusKode[0]['cus_kode'];
            $arrayInsert = array (
            	"trano" => $trano,
                "tgl" => date('Y-m-d'),
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
				"budgettype" => $jsonEtc[0]['budgettype'],
				"petugas" => $this->session->userName,
//				"cus_kode" => $cusKode,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
                "tipe" => $tipe
            );
            $this->procurementH->insert($arrayInsert);
        
        if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonEtc[0]['prj_kode'],
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

	public function updateprbudgetAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $sales = $this->getRequest()->getParam('sales');
       $etc = $this->getRequest()->getParam('etc');
       $file = $this->getRequest()->getParam('file');
   	   $etc = str_replace("\\","",$etc);

       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);
       $jsonFile = Zend_Json::decode($file);

       $trano = $jsonEtc[0]['trano'];
       $urut = 1;

       if($sales)
       {
         $tipe = 'S';  
       }
       else
       {
         $tipe = 'O';
       }

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano,'PRO','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
       $log['pr-detail-before'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();
       $this->procurement->delete("trano = '$trano'");
       foreach($jsonData as $key => $val)
       {

			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
				"workid" => $val['budgetid'],
				"workname" => $val['budgetname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"qty" => $val['qty'],
				"harga" => $val['harga'],
				"jumlah" => $val['totalPrice'],
				"ket" => $val['ket'],
				"petugas" => $this->session->userName,
				"val_kode" => $val['val_kode'],
                "myob" => $val['net_act'],
                "cfs_kode" => $val['net_act'],
                "tipe" => $tipe
			);
               $urut++;

            $this->procurement->insert($arrayInsert);
       }
            $log2['pr-detail-after'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();
//            $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
//            $cusKode = $cusKode[0]['cus_kode'];
            $arrayInsert = array (
//            	"trano" => $trano,
                "tgl" => date('Y-m-d'),
                "revisi" => $jsonEtc[0]['rev'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
				"budgettype" => $jsonEtc[0]['budgettype'],
				"petugas" => $this->session->userName,
//				"cus_kode" => $cusKode,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s')
            );
//            $this->procurementH->delete("trano = '$trano'");
            $log['pr-header-before'] = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
            $this->procurementH->update($arrayInsert, "trano = '$trano'");
            $log2['pr-header-after'] = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "UPDATE",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);

       if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonEtc[0]['prj_kode'],
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

    public function updaterpiAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);

       $trano = $jsonEtc[0]['trano'];

       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
       $items['prj_kode'] = $jsonEtc[0]['prj_kode'];
       
        $result = $this->workflow->setWorkflowTrans($trano,'RPI','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
       $urut = 1;
       $gtotal = 0;

       //insert to Log....
      $log['rpi-detail-before'] = array();
       $fetch = $this->rpi->fetchAll("trano = '$trano'");
       if ($fetch)
       {
           $fetch = $fetch->toArray();
           $log['rpi-detail-before'] = $fetch;
       }
       //done

       $this->rpi->delete("trano = '$trano'");
       $log2['rpi-detail-after'] = array();
       
       foreach($jsonData as $key => $val)
       {
            $harga = $val['harga'];
       		$total = $val['qty'] * $harga;

       		$gtotal += $total;
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
				"sup_kode" => $jsonEtc[0]['sup_kode'],
				"cus_kode" => $jsonEtc[0]['cus_kode'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"po_no" => $val['po_no'],
				"pr_no" => $val['pr_no'],
				"ppn" => $val['ppn'],
				"qty" => $val['qty'],
				"harga" => $harga,
				"total" => $total,
				"ket" => $val['ket'],
				"petugas" => $this->session->userName,
				"val_kode" => $val['val_kode'],

			);
           $log2['rpi-detail-after'][] = $arrayInsert;
            $this->rpi->insert($arrayInsert);
            $urut++;

       }

            $radio = array();
            $radio['invoice-radio'] = $jsonEtc[0]['invoice_radio'];
            $radio['vat-radio'] = $jsonEtc[0]['vat_radio'];
            $radio['do-radio'] = $jsonEtc[0]['do_radio'];
            $radio['sign-radio'] = $jsonEtc[0]['sign_radio'];

            $supplier = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
            $bank = $supplier['reknamabank'] . "\n" . $supplier['rekbank'] . "\n" . $supplier['namabank'];

            $radios = Zend_Json::encode($radio);
            $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
           if ($jsonEtc[0]['ppn'] > 0)
               $ppn = $gtotal * 0.1;
            else
                $ppn = 0;

            $result = $this->rpiH->fetchRow("trano = '$trano'");
            if ($result)
            {
                $result = $result->toArray();
                $log['rpi-header-before'] = $result;
            }

            $arrayInsert = array (                              
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "tglinvoice" => date('Y-m-d'),
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
				"sup_kode" => $jsonEtc[0]['sup_kode'],
				"sup_nama" => $jsonEtc[0]['sup_nama'],
                "rateidr" => $jsonEtc[0]['rate_idr'],
                "val_kode" => $jsonData[0]['val_kode'],
                "namabank" => $supplier['namabank'],
                "rekbank" => $supplier['rekbank'],
                "reknamabank" => $supplier['reknamabank'],
				"ket" => $jsonEtc[0]['rpi_ket'],
				"ketin" => $jsonEtc[0]['ketin'],
				"po_no" => $jsonEtc[0]['po_no'],
                "totalpo" => $jsonEtc[0]['totalPO'],
				"petugas" => $this->session->userName,
                "invoice_no" => $jsonEtc[0]['sup_invoice'],
                "ppn" => $ppn,
                "ppnpo" => $jsonEtc[0]['ppn'],
                "total" => $gtotal,
                "gtotal" => $gtotal + ($ppn),
				"cus_kode" => $cusKode,
                "document_valid" => $radios
            );

            $log2['rpi-header-after'] = $arrayInsert;

            $this->rpiH->delete("trano = '$trano'");
            $this->rpiH->insert($arrayInsert);

            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "UPDATE",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);

            $this->getResponse()->setBody("{success: true}");
    }

    public function updaterpibudgetAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $sales = $this->getRequest()->getParam('sales');
       $etc = $this->getRequest()->getParam('etc');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);

       $trano = $jsonEtc[0]['trano'];
       if($sales)
       {
         $tipe = 'S';
       }
       else
       {
         $tipe = 'O';
       }

       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
       $items['prj_kode'] = $jsonEtc[0]['prj_kode'];

        $result = $this->workflow->setWorkflowTrans($trano,'RPIO','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
       $urut = 1;
       $gtotal = 0;

       //insert to Log....
      $log['rpi-detail-before'] = array();
       $fetch = $this->rpi->fetchAll("trano = '$trano'");
       if ($fetch)
       {
           $fetch = $fetch->toArray();
           $log['rpi-detail-before'] = $fetch;
       }
       //done

       $this->rpi->delete("trano = '$trano'");
       $log2['rpi-detail-after'] = array();

       foreach($jsonData as $key => $val)
       {
            $harga = $val['harga'];
       		$total = $val['qty'] * $harga;

       		$gtotal += $total;
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
				"sup_kode" => $jsonEtc[0]['sup_kode'],
				"cus_kode" => $jsonEtc[0]['cus_kode'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"po_no" => $val['po_no'],
				"pr_no" => $val['pr_no'],
				"ppn" => $val['ppn'],
				"qty" => $val['qty'],
				"harga" => $harga,
				"total" => $total,
				"ket" => $val['ket'],
				"petugas" => $this->session->userName,
				"val_kode" => $val['val_kode'],
                "tipe" => $tipe
			);
           $log2['rpi-detail-after'][] = $arrayInsert;
            $this->rpi->insert($arrayInsert);
            $urut++;

       }

            $radio = array();
            $radio['invoice-radio'] = $jsonEtc[0]['invoice_radio'];
            $radio['vat-radio'] = $jsonEtc[0]['vat_radio'];
            $radio['do-radio'] = $jsonEtc[0]['do_radio'];
            $radio['sign-radio'] = $jsonEtc[0]['sign_radio'];

            $supplier = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
            $bank = $supplier['reknamabank'] . "\n" . $supplier['rekbank'] . "\n" . $supplier['namabank'];

            $radios = Zend_Json::encode($radio);
            $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
           if ($jsonEtc[0]['ppn'] > 0)
               $ppn = $gtotal * 0.1;
            else
                $ppn = 0;

            $result = $this->rpiH->fetchRow("trano = '$trano'");
            if ($result)
            {
                $result = $result->toArray();
                $log['rpi-header-before'] = $result;
            }

            $arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "tglinvoice" => date('Y-m-d'),
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
				"sup_kode" => $jsonEtc[0]['sup_kode'],
				"sup_nama" => $jsonEtc[0]['sup_nama'],
                "rateidr" => $jsonEtc[0]['rate_idr'],
                "val_kode" => $jsonData[0]['val_kode'],
                "namabank" => $supplier['namabank'],
                "rekbank" => $supplier['rekbank'],
                "reknamabank" => $supplier['reknamabank'],
				"ket" => $jsonEtc[0]['rpi_ket'],
				"ketin" => $jsonEtc[0]['ketin'],
				"po_no" => $jsonEtc[0]['po_no'],
                "totalpo" => $jsonEtc[0]['totalPO'],
				"petugas" => $this->session->userName,
                "invoice_no" => $jsonEtc[0]['sup_invoice'],
                "ppn" => $ppn,
                "ppnpo" => $jsonEtc[0]['ppn'],
                "total" => $gtotal,
                "gtotal" => $gtotal + ($ppn),
				"cus_kode" => $cusKode,
                "document_valid" => $radios,
                "tipe" => $tipe
            );

            $log2['rpi-header-after'] = $arrayInsert;

            $this->rpiH->delete("trano = '$trano'");
            $this->rpiH->insert($arrayInsert);

            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "UPDATE",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);

            $this->getResponse()->setBody("{success: true}");
    }

     public function insertpoAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
       $file = $this->getRequest()->getParam('file');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);
       $jsonFile = Zend_Json::decode($file);

        $totals = 0;
        $totalsSupp = 0;
    	$pajak=0;
        $pajakSupp=0;
    	$grandtotals=0;
        $grandtotalsSupp=0;

      foreach($jsonData as $key => $val)
    		{
          $total = $val['qty'] * $val['price'];
          $totals += $total;
          $totalSupp = $val['qtySupp'] * $val['priceSupp'];
          $totalsSupp += $totalSupp;
      }
        $tax = $jsonEtc[0]['tax'];

      if ($tax == 'Y'){
    			$pajakh = ($totals * 0.1);
    			$grandtotals = $totals + $pajakh;
                $pajakhSupp = ($totalsSupp * 0.1);
    			$grandtotalsSupp = $totalsSupp + $pajakhSupp;
    		}else {
    			$pajakh = 0;
    			$grandtotals = $totals;
                $pajakhSupp = null;
    			$grandtotalsSupp = $totalsSupp;
    		}

       $counter = new Default_Models_MasterCounter();
		
	   $lastTrans = $counter->getLastTrans('PO');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'PO01-' . $last;
	   $where = "id=".$lastTrans['id'];

       $urut = 1;


       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'PO', '', $this->const['DOCUMENT_SUBMIT'],$items);
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
       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

      if ($jsonEtc[0]['tgldeliesti'] != '')
        $tgldeliesti = date('Y-m-d', strtotime($jsonEtc[0]['tgldeliesti']));
      else
          $tgldeliesti = $tgl;

        $ada = false;
        while(!$ada)
        {
            $cek = $this->purchase->fetchRow("trano = '$trano'");
            if ($cek)
            {
                $lastTrans = $counter->getLastTrans('PO');
               $last = abs($lastTrans['urut']);
               $last = $last + 1;
               $trano = 'PO01-' . $last;
               $where = "id=".$lastTrans['id'];
            }
            elseif (!$cek)
                $ada = true;
        }

        $counter->update(array("urut" => $last),$where);
      
       foreach($jsonData as $key => $val)
       {
           $total = $val['qty'] * $val['price'];
           $totalSupp = $val['qtySupp'] * $val['priceSupp'];
           
          if ($tax == 'Y'){
    			$pajak = ($total * 0.1);
                $pajakSupp = ($totalSupp * 0.1);

    		}else {
    			$pajak = 0;
                $pajakSupp = null;
    		}
       			
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"pr_no" => $val['pr_number'],
				"tglpr" => $val['tgl_pr'],
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
                "qtyspl" => $val['qtySupp'],
				"harga" => $val['price'],
                "hargaspl" => $val['priceSupp'],
				"ppn" => $pajak,
                "ppnspl" => $pajakSupp,
				"total" => $total,
                "totalspl" => $totalSupp,
				"ket" => $val['ket'],
			
				"petugas" => $this->session->userName,
				"sup_kode" => $val['sup_kode'],
				"sup_nama" => $val['sup_nama'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['currency'],
//				"typepo" => $val['po_type'],
				"myob" => $val['net_act'],
				"cfs_kode" => $val['net_act']
			);
            $urut++;

            $harga = floatval($val['price']);
           $kode_brg = $val['kode_brg'];
//           if ($harga > 0)
//                $this->barang->update(array("harga_beli" => $harga, "hargaavg" => $harga), "kode_brg = '$kode_brg'");

           $this->purchase->insert($arrayInsert);
       }
        	$arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "pr_no" => $jsonData[0]['pr_number'],
                "tglpr" => $jsonData[0]['tgl_pr'],
				"prj_kode" => $jsonData[0]['prj_kode'],
				"prj_nama" => $jsonData[0]['prj_nama'],
//				"sit_kode" => $jsonEtc[0]['sit_kode'],
//				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonEtc[0]['ket'],
				"petugas" => $this->session->userName,
                "myob" => $jsonData[0]['net_act'],
        		"statusppn" => $tax,
        		"jumlah" => $totals,
                "jumlahspl" => $totalsSupp,
        		"ppn" => $pajakh,
                "ppnspl" => $pajakhSupp,
        		"total" => $grandtotals,
                "totalspl" => $grandtotalsSupp,
                "val_kode" =>  $jsonEtc[0]['val_kode'],
                "sup_kode" => $jsonEtc[0]['sup_kode'],
        		"sup_nama" => $jsonEtc[0]['sup_nama'],
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
        
        		"rateidr" => $jsonEtc[0]['rateidr'],
        		"deliverytext" => $jsonEtc[0]['tujuan'],
        		"paymentterm" => $jsonEtc[0]['payterm'],
                "budgettype" => $jsonEtc[0]['budgettype'],

        		"tgldeliesti" => $tgldeliesti,
        		"invoiceto" => $jsonEtc[0]['invoiceto'],
                "ketin" => $jsonEtc[0]['ketin'],
                "typepo2" => $jsonData[0]['po_type']
				//"cus_kode" => $cusKode,
            );
            $this->purchaseH->insert($arrayInsert);

            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonData[0]['prj_kode'],
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

    public function insertpobudgetAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
       $file = $this->getRequest()->getParam('file');
       $sales = $this->getRequest()->getParam('sales');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);
       $jsonFile = Zend_Json::decode($file);

        $totals = 0;
        $totalsSupp = 0;
    	$pajak=0;
        $pajakSupp=0;
    	$grandtotals=0;
        $grandtotalsSupp=0;
    	$pajak=0;
    	$grandtotals=0;

      foreach($jsonData as $key => $val)
    		{
          $total = $val['qty'] * $val['price'];
          $totals += $total;
          $totalSupp = $val['qtySupp'] * $val['priceSupp'];
          $totalsSupp += $totalSupp;
      }
        $tax = $jsonEtc[0]['tax'];

      if ($tax == 'Y'){
    			$pajakh = ($totals * 0.1);
    			$grandtotals = $totals + $pajakh;
                $pajakhSupp = ($totalsSupp * 0.1);
    			$grandtotalsSupp = $totalsSupp + $pajakhSupp;
    		}else {
    			$pajakh = 0;
    			$grandtotals = $totals;
                $pajakhSupp = null;
    			$grandtotalsSupp = $totalsSupp;
    		}

       $counter = new Default_Models_MasterCounter();

      if ($sales)
      {
        $lastTrans = $counter->getLastTrans('PO');
        $last = abs($lastTrans['urut']);
	    $last = $last + 1;
	    $trano = 'PO01-' . $last;
	    $where = "id=".$lastTrans['id'];
        $tipe = 'S';
      }
       else
       {
	    $lastTrans = $counter->getLastTrans('POO');
	    $last = abs($lastTrans['urut']);
	    $last = $last + 1;
	    $trano = 'PO02-' . $last;
	    $where = "id=".$lastTrans['id'];
        $tipe = 'O';
       }

       $urut = 1;


       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'POO', '', $this->const['DOCUMENT_SUBMIT'],$items);
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
       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

      if ($jsonEtc[0]['tgldeliesti'] != '')
        $tgldeliesti = date('Y-m-d', strtotime($jsonEtc[0]['tgldeliesti']));
      else
          $tgldeliesti = $jsonEtc[0]['tgldeliesti'];

        $counter->update(array("urut" => $last),$where);
      
       foreach($jsonData as $key => $val)
       {
           $total = $val['qty'] * $val['price'];
           $totalSupp = $val['qtySupp'] * $val['priceSupp'];

          if ($tax == 'Y'){
    			$pajak = ($total * 0.1);
                $pajakSupp = ($totalSupp * 0.1);

    		}else {
    			$pajak = 0;
                $pajakSupp = null;

    		}

			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"pr_no" => $val['pr_number'],
				"tglpr" => $val['tgl_pr'],
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
                "qtyspl" => $val['qtySupp'],
				"harga" => $val['price'],
                "hargaspl" => $val['priceSupp'],
				"ppn" => $pajak,
                "ppnspl" => $pajakSupp,
				"total" => $total,
                "totalspl" => $totalSupp,
				"ket" => $val['ket'],

				"petugas" => $this->session->userName,
				"sup_kode" => $val['sup_kode'],
				"sup_nama" => $val['sup_nama'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['currency'],
//				"typepo" => $val['po_type'],
				"myob" => $val['net_act'],
				"cfs_kode" => $val['net_act'],
                "tipe" => $tipe
			);
            $urut++;
           $harga = $val['price'];
           $kode_brg = $val['kode_brg'];
//            $this->barang->update(array("harga_beli" => $harga, "hargaavg" => $harga), "kode_brg = '$kode_brg'");
            $this->purchase->insert($arrayInsert);
       }
        	$arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "pr_no" => $jsonData[0]['pr_number'],
                "tglpr" => $jsonData[0]['tgl_pr'],
				"prj_kode" => $jsonData[0]['prj_kode'],
				"prj_nama" => $jsonData[0]['prj_nama'],
//				"sit_kode" => $jsonEtc[0]['sit_kode'],
//				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonEtc[0]['ket'],
				"petugas" => $this->session->userName,
                "myob" => $jsonData[0]['net_act'],
        		"statusppn" => $tax,
        		"jumlah" => $totals,
                "jumlahspl" => $totalsSupp,
        		"ppn" => $pajakh,
                "ppnspl" => $pajakhSupp,
        		"total" => $grandtotals,
                "totalspl" => $grandtotalsSupp,
                "val_kode" =>  $jsonEtc[0]['val_kode'],
                "sup_kode" => $jsonEtc[0]['sup_kode'],
        		"sup_nama" => $jsonEtc[0]['sup_nama'],
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),

        		"rateidr" => $jsonEtc[0]['rateidr'],
        		"deliverytext" => $jsonEtc[0]['tujuan'],
        		"paymentterm" => $jsonEtc[0]['payterm'],
                "budgettype" => $jsonEtc[0]['budgettype'],

        		"tgldeliesti" => $tgldeliesti,
        		"invoiceto" => $jsonEtc[0]['invoiceto'],
                "ketin" => $jsonEtc[0]['ketin'],
                "typepo2" => $jsonData[0]['po_type'],
                "tipe" => $tipe
				//"cus_kode" => $cusKode,
            );
            $this->purchaseH->insert($arrayInsert);

            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonData[0]['prj_kode'],
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
    
	public function updatepoAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
       $file = $this->getRequest()->getParam('file');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);
       $jsonFile = Zend_Json::decode($file);

        $totals = 0;
        $totalsSupp = 0;
    	$pajak=0;
        $pajakSupp=0;
    	$grandtotals=0;
        $grandtotalsSupp=0;
    	$pajak=0;
    	$grandtotals=0;
    	$pajak=0;
    	$grandtotals=0;

      foreach($jsonData as $key => $val)
      {
          $total = $val['qty'] * $val['price'];
          $totals += $total;
          
          if ($val['qtySupp'] == 0 || $val['qtySupp'] == '')
          {
              $jsonData[$key]['qtySupp'] = null;
              $jsonData[$key]['priceSupp'] = null;
              $totalSupp = 0;
          }
          else
            $totalSupp = $val['qtySupp'] * $val['priceSupp'];
          $totalsSupp += $totalSupp;
      }
        $tax = $jsonEtc[0]['tax'];

      if ($tax == 'Y'){
    			$pajakh = ($totals * 0.1);
    			$grandtotals = $totals + $pajakh;
                $pajakhSupp = ($totalsSupp * 0.1);
    			$grandtotalsSupp = $totalsSupp + $pajakhSupp;
    		}else {
    			$pajakh = 0;
    			$grandtotals = $totals;
                $pajakhSupp = null;
    			$grandtotalsSupp = $totalsSupp;
    		}
	   
       $trano = $jsonData[0]['po_number'];
       $urut = 1;

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano,'PO','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));
       if ($jsonEtc[0]['tgldeliesti'] != '')
        $tgldeliesti = date('Y-m-d', strtotime($jsonEtc[0]['tgldeliesti']));
      else
          $tgldeliesti = $jsonEtc[0]['tgldeliesti'];

      //insert to Log....
      $log['po-detail-before'] = array();
       $fetch = $this->purchase->fetchAll("trano = '$trano'");
       if ($fetch)
       {
           $fetch = $fetch->toArray();
           $log['po-detail-before'] = $fetch;
       }
       //done

       $this->purchase->delete("trano = '$trano'");
       $log2['po-detail-after'] = array();
       foreach($jsonData as $key => $val)
       {
           $total = $val['qty'] * $val['price'];
           if ($val['qtySupp'] == '')
               $val['qtySupp'] = $val['qty'];
           if ($val['priceSupp'] == '')
               $val['priceSupp'] = $val['harga'];
           $totalSupp = $val['qtySupp'] * $val['priceSupp'];

           if ($totalSupp == 0)
               $totalSupp = 0;
          if ($tax == 'Y'){
    			$pajak = ($total * 0.1);
                $pajakSupp = ($totalSupp * 0.1);

    		}else {
    			$pajak = 0;
                $pajakSupp = 0;
    		}

			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"pr_no" => $val['pr_number'],
				"tglpr" => $val['tgl_pr'],
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
                "qtyspl" => $val['qtySupp'],
				"harga" => $val['price'],
                "hargaspl" => $val['priceSupp'],
				"ppn" => $pajak,
                "ppnspl" => $pajakSupp,
				"total" => $total,
                "totalspl" => $totalSupp,
				"ket" => $val['ket'],

				"petugas" => $this->session->userName,
				"sup_kode" => $val['sup_kode'],
				"sup_nama" => $val['sup_nama'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['currency'],
//				"typepo" => $val['po_type'],
				"myob" => $val['net_act'],
				"cfs_kode" => $val['net_act']
			);
            $urut++;
			
			$workid = $val['workid'];
			$kode_brg = $val['kode_brg'];
             $harga = floatval($val['price']);
//           if ($harga > 0)
//            $this->barang->update(array("harga_beli" => $harga, "hargaavg" => $harga), "kode_brg = '$kode_brg'");

            $this->purchase->insert($arrayInsert);
//            $this->purchase->update($arrayInsert, "trano = '$trano' AND workid = '$workid'  AND kode_brg = '$kode_brg'");

            $log2['po-detail-after'][] = $this->purchase->fetchRow("trano = '$trano'")->toArray();
          
       }
            $result = $this->purchaseH->fetchRow("trano = '$trano'");
            if ($result)
            {
                $result = $result->toArray();
                $log['po-header-before'] = $result;
            }

            if ($grandtotalsSupp == 0)
                $grandtotalsSupp = null;
            if ($totalsSupp == 0)
                $totalsSupp = null;
            $arrayInsert = array (
				"revisi" => $jsonEtc[0]['rev'],
                "statusppn" => $tax,
        		"jumlah" => $totals,
                "jumlahspl" => $totalsSupp,
        		"ppn" => $pajakh,
                "ppnspl" => $pajakhSupp,
        		"total" => $grandtotals,
                "totalspl" => $grandtotalsSupp,
//                "val_kode" => $jsonData[0]['val_kode'],
                "tgldeliesti" => $tgldeliesti,
                "sup_kode" => $jsonEtc[0]['sup_kode'],
                "sup_nama" => $jsonEtc[0]['sup_nama'],
                "paymentterm" => $jsonEtc[0]['payterm'],
                "deliverytext" => $jsonEtc[0]['tujuan'],
                "ket" => $jsonEtc[0]['ket'],
                "ketin" => $jsonEtc[0]['ketin'],
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s')
            );


            $result = $this->purchaseH->update($arrayInsert,"trano = '$trano'");
            $result = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
            $log2['po-header-after'] = $result;
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "UPDATE",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);

            $this->files->delete("trano = '$trano'");
            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonData[0]['prj_kode'],
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

    public function updatepobudgetAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
       $sales = $this->getRequest()->getParam('sales');
       $file = $this->getRequest()->getParam('file');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);
       $jsonFile = Zend_Json::decode($file);

       $totals = 0;
       $totalsSupp = 0;
       $pajak=0;
       $pajakSupp=0;
       $grandtotals=0;
       $grandtotalsSupp=0;
       $pajak=0;
       $grandtotals=0;
       $pajak=0;
       $grandtotals=0;


      foreach($jsonData as $key => $val)
    		{
          $total = $val['qty'] * $val['price'];
          $totals += $total;
          $totalSupp = $val['qtySupp'] * $val['priceSupp'];
          $totalsSupp += $totalSupp;
      }
        $tax = $jsonEtc[0]['tax'];

      if ($tax == 'Y'){
    			$pajakh = ($totals * 0.1);
    			$grandtotals = $totals + $pajakh;
                $pajakhSupp = ($totalsSupp * 0.1);
    			$grandtotalsSupp = $totalsSupp + $pajakhSupp;
    		}else {
    			$pajakh = 0;
    			$grandtotals = $totals;
                $pajakhSupp = null;
    			$grandtotalsSupp = $totalsSupp;
    		}

       $trano = $jsonEtc[0]['trano'];
       if($sales)
       {
           $tipe = 'S';
       }
       else
       {
           $tipe = 'O';
       }

       $urut = 1;
       
       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano,'POO','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));
       if ($jsonEtc[0]['tgldeliesti'] != '')
        $tgldeliesti = date('Y-m-d', strtotime($jsonEtc[0]['tgldeliesti']));
      else
          $tgldeliesti = $jsonEtc[0]['tgldeliesti'];

       $fetch = $this->purchase->fetchAll("trano = '$trano'");
       if ($fetch)
       {
           $fetch = $fetch->toArray();
           $log['po-detail-before'] = $fetch;
       }
       $this->purchase->delete("trano = '$trano'");

       $log2['po-detail-after'] = array();
       foreach($jsonData as $key => $val)
       {
           $total = $val['qty'] * $val['price'];
           $totalSupp = $val['qtySupp'] * $val['priceSupp'];

          if ($tax == 'Y'){
    			$pajak = ($total * 0.1);
                $pajakSupp = ($totalSupp * 0.1);

    		}else {
    			$pajak = 0;
                $pajakSupp = null;
    		}

			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"pr_no" => $val['pr_number'],
				"tglpr" => $val['tgl_pr'],
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
                "qtyspl" => $val['qtySupp'],
				"harga" => $val['price'],
                "hargaspl" => $val['priceSupp'],
				"ppn" => $pajak,
                "ppnspl" => $pajakSupp,
				"total" => $total,
                "totalspl" => $totalSupp,
				"ket" => $val['ket'],

				"petugas" => $this->session->userName,
				"sup_kode" => $val['sup_kode'],
				"sup_nama" => $val['sup_nama'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['currency'],
//				"typepo" => $val['po_type'],
				"myob" => $val['net_act'],
				"cfs_kode" => $val['net_act']
			);
            $urut++;

			$workid = $val['workid'];
			$kode_brg = $val['kode_brg'];
             $harga = $val['price'];
//           if ($harga > 0)
//            $this->barang->update(array("harga_beli" => $harga, "hargaavg" => $harga), "kode_brg = '$kode_brg'");

           $this->purchase->insert($arrayInsert);
//            $this->purchase->update($arrayInsert, "trano = '$trano' AND workid = '$workid'  AND kode_brg = '$kode_brg'");
            $log2['po-detail-after'][] = $this->purchase->fetchRow("trano = '$trano'")->toArray();
       }
            $result = $this->purchaseH->fetchRow("trano = '$trano'");
            if ($result)
            {
                $result = $result->toArray();
                $log['po-header-before'] = $result;
            }

            $arrayInsert = array (
				"revisi" => $jsonEtc[0]['rev'],
                "statusppn" => $tax,
        		"jumlah" => $totals,
                "jumlahspl" => $totalsSupp,
        		"ppn" => $pajakh,
                "ppnspl" => $pajakhSupp,
        		"total" => $grandtotals,
                "totalspl" => $grandtotalsSupp,
//                "val_kode" => $jsonData[0]['val_kode'],
                "tgldeliesti" => $tgldeliesti,
                "sup_kode" => $jsonEtc[0]['sup_kode'],
                "sup_nama" => $jsonEtc[0]['sup_nama'],
                "paymentterm" => $jsonEtc[0]['payterm'],
                "deliverytext" => $jsonEtc[0]['tujuan'],
                "ket" => $jsonEtc[0]['ket'],
                "ketin" => $jsonEtc[0]['ketin'],
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s')
            );

            $result = $this->purchaseH->update($arrayInsert,"trano = '$trano'");
            $result = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
            $log2['po-header-after'] = $result;
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "UPDATE",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);

            $this->files->delete("trano = '$trano'");
            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonData[0]['prj_kode'],
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

    public function insertrpiAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);
       $counter = new Default_Models_MasterCounter();

	   $lastTrans = $counter->getLastTrans('RPI');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'RPI01-' . $last;

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'RPI', '', $this->const['DOCUMENT_SUBMIT'],$items);
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
       $urut = 1;
       $gtotal = 0;
       foreach($jsonData as $key => $val)
       {
            $harga = $val['harga'];
       		$total = $val['qty'] * $harga;

       		$gtotal += $total;
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
				"sup_kode" => $jsonEtc[0]['sup_kode'],
				"cus_kode" => $jsonEtc[0]['cus_kode'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"po_no" => $val['po_no'],
				"pr_no" => $val['pr_no'],
				"ppn" => $val['ppn'],
				"qty" => $val['qty'],
				"harga" => $harga,
				"total" => $total,
				"ket" => $val['ket'],
				"petugas" => $this->session->userName,
				"val_kode" => $val['val_kode'],
                 "rateidr" => $jsonEtc[0]['rate_idr']
			);
            $this->rpi->insert($arrayInsert);
            $urut++;
       }
            $radio = array();
            $radio['invoice-radio'] = $jsonEtc[0]['invoice_radio'];
            $radio['vat-radio'] = $jsonEtc[0]['vat_radio'];
            $radio['do-radio'] = $jsonEtc[0]['do_radio'];
            $radio['sign-radio'] = $jsonEtc[0]['sign_radio'];

            $supplier = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
            $bank = $supplier['reknamabank'] . "\n" . $supplier['rekbank'] . "\n" . $supplier['namabank'];

            $radios = Zend_Json::encode($radio);
            $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
            if ($jsonEtc[0]['ppn'] > 0)
            {
                $ppn = $gtotal * 0.1;
                $statusppn = 'Y';
            }
            else
            {
                $ppn = 0;
                $statusppn = 'N';
            }

            $arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "tglinvoice" => date('Y-m-d'),
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
				"sup_kode" => $jsonEtc[0]['sup_kode'],
				"sup_nama" => $jsonEtc[0]['sup_nama'],
                "rateidr" => $jsonEtc[0]['rate_idr'],
                "val_kode" => $jsonData[0]['val_kode'],
                "namabank" => $supplier['namabank'],
                "rekbank" => $supplier['rekbank'],
                "reknamabank" => $supplier['reknamabank'],
				"ket" => $jsonEtc[0]['rpi_ket'],
				"ketin" => $jsonEtc[0]['ketin'],
				"po_no" => $jsonEtc[0]['po_no'],
                "totalpo" => $jsonEtc[0]['totalPO'],
				"petugas" => $this->session->userName,
                "invoice_no" => $jsonEtc[0]['sup_invoice'],
                "ppn" => $ppn,
                "statusppn" => $statusppn,
                "ppnpo" => $jsonEtc[0]['ppn'],
                "total" => $gtotal,
                "gtotal" => $gtotal + ($ppn),
				"cus_kode" => $cusKode,
                "document_valid" => $radios
            );
            $this->rpiH->insert($arrayInsert);
			$this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function insertrpibudgetAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $sales = $this->getRequest()->getParam('sales');
       $etc = $this->getRequest()->getParam('etc');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);
       $counter = new Default_Models_MasterCounter();

       if ($sales)
       {
       $lastTrans = $counter->getLastTrans('RPI');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'RPI01-' . $last;
        $tipe = 'S';
       }
       else
       {
	   $lastTrans = $counter->getLastTrans('RPIO');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'RPI02-' . $last;
       $tipe = 'O';
       }

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'RPIO', '', $this->const['DOCUMENT_SUBMIT'],$items);
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
       $urut = 1;
       $gtotal = 0;
       foreach($jsonData as $key => $val)
       {
            $harga = $val['harga'];
       		$total = $val['qty'] * $harga;

       		$gtotal += $total;
			$arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
				"sup_kode" => $jsonEtc[0]['sup_kode'],
				"cus_kode" => $jsonEtc[0]['cus_kode'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"po_no" => $val['po_no'],
				"pr_no" => $val['pr_no'],
				"ppn" => $val['ppn'],
				"qty" => $val['qty'],
				"harga" => $harga,
				"total" => $total,
				"ket" => $val['ket'],
				"petugas" => $this->session->userName,
				"val_kode" => $val['val_kode'],
                 "rateidr" => $jsonEtc[0]['rate_idr'],
                "tipe" => $tipe
			);
            $this->rpi->insert($arrayInsert);
            $urut++;
       }
            $radio = array();
            $radio['invoice-radio'] = $jsonEtc[0]['invoice_radio'];
            $radio['vat-radio'] = $jsonEtc[0]['vat_radio'];
            $radio['do-radio'] = $jsonEtc[0]['do_radio'];
            $radio['sign-radio'] = $jsonEtc[0]['sign_radio'];

            $supplier = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
            $bank = $supplier['reknamabank'] . "\n" . $supplier['rekbank'] . "\n" . $supplier['namabank'];

            $radios = Zend_Json::encode($radio);
            $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
           if ($jsonEtc[0]['ppn'] > 0)
               $ppn = $gtotal * 0.1;
            else
                $ppn = 0;

            $arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "tglinvoice" => date('Y-m-d'),
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
				"sup_kode" => $jsonEtc[0]['sup_kode'],
				"sup_nama" => $jsonEtc[0]['sup_nama'],
                "rateidr" => $jsonEtc[0]['rate_idr'],
                "val_kode" => $jsonData[0]['val_kode'],
                "namabank" => $supplier['namabank'],
                "rekbank" => $supplier['rekbank'],
                "reknamabank" => $supplier['reknamabank'],
				"ket" => $jsonEtc[0]['rpi_ket'],
				"po_no" => $jsonEtc[0]['po_no'],
                "totalpo" => $jsonEtc[0]['totalPO'],
				"petugas" => $this->session->userName,
                "invoice_no" => $jsonEtc[0]['sup_invoice'],
                "ppn" => $ppn,
                "ppnpo" => $jsonEtc[0]['ppn'],
                "total" => $gtotal,
                "gtotal" => $gtotal + ($ppn),
				"cus_kode" => $cusKode,
                "document_valid" => $radios,
                "tipe" => $tipe
            );
            $this->rpiH->insert($arrayInsert);
			$this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function insertasfAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
      $json2 = $this->getRequest()->getParam('posts2');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonData2 = Zend_Json::decode($json2);
       $jsonEtc = Zend_Json::decode($etc);

       $counter = new Default_Models_MasterCounter();

	   $lastTrans = $counter->getLastTrans('ASF');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'ASF01-' . $last;

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'ASF', '', $this->const['DOCUMENT_SUBMIT'],$items);
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

        $ada = false;
        while(!$ada)
        {
            $cek = $this->asf->fetchRow("trano = '$trano'");
            if ($cek)
            {
                $lastTrans = $counter->getLastTrans('ASF');
               $last = abs($lastTrans['urut']);
               $last = $last + 1;
               $trano = 'ASF01-' . $last;
            }
            elseif (!$cek)
                $ada = true;
        }

	   $where = "id=".$lastTrans['id'];
       $counter->update(array("urut" => $last),$where);
       $urut = 1;
       $urut2 = 1;

       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        $totalPriceArf = 0;

      $temp = array();
       if($jsonData)
       {
      foreach($jsonData as $key => $val)
      {

          $tranotemp = $val['arf_no'];

          if ($temp[$tranotemp] == '')
          {
              $temp[$tranotemp]['total'] = $val['totalPrice'];
               $temp[$tranotemp]['trano'] = $tranotemp;
              $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
              $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
              $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
          }
          else
              $temp[$tranotemp]['total'] += $val['totalPrice'];

          $arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"arf_no" => $val['arf_no'],
				"tglarf" => $val['tgl_arf'],
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

//				"petugas" => $this->session->userName,
                "petugas" => $val['petugas'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['rateidr'],
                "cfs_kode" => $val['cfs_kode']
			);
            $urut++;
            $this->asf->insert($arrayInsert);
      }
       }


      if($jsonData2)
      {
      foreach($jsonData2 as $key => $val)
      {
          $tranotemp = $val['arf_no'];

          if ($temp[$tranotemp] == '')
          {
              $temp[$tranotemp]['total'] = $val['totalPrice'];
               $temp[$tranotemp]['trano'] = $tranotemp;
              $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
              $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
              $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
          }
          else
              $temp[$tranotemp]['total'] += $val['totalPrice'];

          $arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"arf_no" => $val['arf_no'],
				"tglarf" => $val['tgl_arf'],
				"urut" => $urut2,
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

//				"petugas" => $this->session->userName,
                "petugas" => $val['petugas'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['rateidr'],
                "cfs_kode" => $val['cfs_kode']
			);
            $urut2++;
            $this->asfc->insert($arrayInsert);
      }
      }

       foreach($temp as $key => $val)
       {
        $balance = $val['totalPriceInArfh'] - $val['total'];
        $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

        $arrayD = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonData[0]['ket'],
                "total" => $balance,
				"petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
        		"totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],

            );
             $this->asfD->insert($arrayD);
      		          
       }
//         $this->asfD->insert($arrayD);

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];
        
        	$arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "arf_no" =>  $arfno,
                "tglarf" => $jsonEtc[0]['tgl_arf'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonEtc[0]['ket'],
				"petugas" => $jsonEtc[0]['petugas'],
                
        		"total" => $jsonEtc[0]['totalarfh'],

                "orangpic" => $jsonEtc[0]['pic'],
        		"orangfinance" => $jsonEtc[0]['finance'],

        		"requestv" => $totalPriceArf,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "request2" => $jsonEtc[0]['requester'],
        		
				//"cus_kode" => $cusKode,
            );
            $this->asfH->insert($arrayInsert);
			$this->getResponse()->setBody("{success: true, number : '$trano'}");

    }

    public function insertasfbudgetAction()
   {
      $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
      $json2 = $this->getRequest()->getParam('posts2');
       $sales = $this->getRequest()->getParam('sales');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonData2 = Zend_Json::decode($json2);
       $jsonEtc = Zend_Json::decode($etc);

       $counter = new Default_Models_MasterCounter();

       if($sales)
       {
           $lastTrans = $counter->getLastTrans('ASF');
           $last = abs($lastTrans['urut']);
           $last = $last + 1;
           $trano = 'ASF01-' . $last;
           $tipe = 'S';
       }
       else
       {
           $lastTrans = $counter->getLastTrans('ASFO');
           $last = abs($lastTrans['urut']);
           $last = $last + 1;
           $trano = 'ASF02-' . $last;
           $tipe = 'O';
       }

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'ASFO', '', $this->const['DOCUMENT_SUBMIT'],$items);
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
       $urut2 = 1;

       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        $totalPriceArf = 0;

      $temp = array();
       if($jsonData)
       {
      foreach($jsonData as $key => $val)
      {

          $tranotemp = $val['arf_no'];

          if ($temp[$tranotemp] == '')
          {
              $temp[$tranotemp]['total'] = $val['totalPrice'];
               $temp[$tranotemp]['trano'] = $tranotemp;
              $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
              $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
              $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
          }
          else
              $temp[$tranotemp]['total'] += $val['totalPrice'];

          $arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"arf_no" => $val['arf_no'],
				"tglarf" => $val['tgl_arf'],
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

//				"petugas" => $this->session->userName,
                "petugas" => $val['petugas'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['rateidr'],
                "cfs_kode" => $val['cfs_kode'],
                "tipe" => $tipe
			);
            $urut++;
            $this->asf->insert($arrayInsert);
      }
       }


      if($jsonData2)
      {
      foreach($jsonData2 as $key => $val)
      {
          $tranotemp = $val['arf_no'];

          if ($temp[$tranotemp] == '')
          {
              $temp[$tranotemp]['total'] = $val['totalPrice'];
               $temp[$tranotemp]['trano'] = $tranotemp;
              $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
              $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
              $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
          }
          else
              $temp[$tranotemp]['total'] += $val['totalPrice'];

          $arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"arf_no" => $val['arf_no'],
				"tglarf" => $val['tgl_arf'],
				"urut" => $urut2,
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

//				"petugas" => $this->session->userName,
                "petugas" => $val['petugas'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['rateidr'],
                "cfs_kode" => $val['cfs_kode'],
                "tipe" => $tipe
			);
            $urut2++;
            $this->asfc->insert($arrayInsert);
      }
      }

       foreach($temp as $key => $val)
       {
        $balance = $val['totalPriceInArfh'] - $val['total'];
        $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

        $arrayD = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonData[0]['ket'],
                "total" => $balance,
				"petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
        		"totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "tipe" => $tipe

            );
             $this->asfD->insert($arrayD);

       }
//         $this->asfD->insert($arrayD);

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        	$arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "arf_no" =>  $arfno,
                "tglarf" => $jsonEtc[0]['tgl_arf'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonEtc[0]['ket'],
				"petugas" => $jsonEtc[0]['petugas'],

        		"total" => $jsonEtc[0]['totalarfh'],

                "orangpic" => $jsonEtc[0]['pic'],
        		"orangfinance" => $jsonEtc[0]['finance'],

        		"requestv" => $totalPriceArf,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "request2" => $jsonEtc[0]['requester'],
                "tipe" => $tipe
				//"cus_kode" => $cusKode,
            );
            $this->asfH->insert($arrayInsert);
			$this->getResponse()->setBody("{success: true, number : '$trano'}");

    }

    public function updateasfAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
       $json2 = $this->getRequest()->getParam('posts2');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonData2 = Zend_Json::decode($json2);
       $jsonEtc = Zend_Json::decode($etc);

       $totalPriceArf = 0;
        $urut = 1;
        $urut2 = 1;

       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

       $trano = $jsonEtc[0]['trano'];

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');


       $result = $this->workflow->setWorkflowTrans($trano,'ASF','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
        $temp = array();
       if($jsonData)
       {
           $log['asfdd-detail-before'] =  $this->asf->fetchAll("trano = '$trano'")->toArray();
           $this->asf->delete("trano = '$trano'");
      foreach($jsonData as $key => $val)
      {

          $tranotemp = $val['arf_no'];

          if ($temp[$tranotemp] == '')
          {
              $temp[$tranotemp]['total'] = $val['totalPrice'];
               $temp[$tranotemp]['trano'] = $tranotemp;
              $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
              $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
              $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
          }
          else
              $temp[$tranotemp]['total'] += $val['totalPrice'];

          $arrayInsert = array(
                "trano" => $trano,
				"tgl" => date('Y-m-d'),
				"arf_no" => $val['arf_no'],
				"tglarf" => $val['tgl_arf'],
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

//				"petugas" => $this->session->userName,
                "petugas" => $val['petugas'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['rateidr'],
                "cfs_kode" => $val['cfs_kode']
			);
            $urut++;

            $this->asf->insert($arrayInsert);
      }
           $log2['asfdd-detail-after'] =  $this->asf->fetchAll("trano = '$trano'")->toArray();
       }

       if($jsonData2)
      {
          $log['asfddcancel-detail-before'] =  $this->asfc->fetchAll("trano = '$trano'")->toArray();
            $this->asfc->delete("trano = '$trano'");
      foreach($jsonData2 as $key => $val)
      {
          $tranotemp = $val['arf_no'];

          if ($temp[$tranotemp] == '')
          {
              $temp[$tranotemp]['total'] = $val['totalPrice'];
               $temp[$tranotemp]['trano'] = $tranotemp;
              $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
              $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
              $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
          }
          else
              $temp[$tranotemp]['total'] += $val['totalPrice'];

          $arrayInsert = array(
                "trano" => $trano,
				"tgl" => date('Y-m-d'),
				"arf_no" => $val['arf_no'],
				"tglarf" => $val['tgl_arf'],
				"urut" => $urut2,
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

//				"petugas" => $this->session->userName,
                "petugas" => $val['petugas'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['rateidr'],
                "cfs_kode" => $val['cfs_kode']
			);
            $urut2++;

            $this->asfc->insert($arrayInsert);

      }
          $log2['asfddcancel-detail-after'] =  $this->asfc->fetchAll("trano = '$trano'")->toArray();
      }
        $log['asfd-detail-before'] =  $this->asfD->fetchAll("trano = '$trano'")->toArray();
       $this->asfD->delete("trano = '$trano'");
       foreach($temp as $key => $val)
       {
        $balance = $val['totalPriceInArfh'] - $val['total'];
        $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

        $arrayD = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonData[0]['ket'],
                "total" => $balance,
				"petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
        		"totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],

            );
             $this->asfD->insert($arrayD);

       }
        $log2['asfd-detail-after'] =  $this->asfD->fetchAll("trano = '$trano'")->toArray();

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        	$arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "arf_no" =>  $arfno,
                "tglarf" => $jsonEtc[0]['tgl_arf'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonEtc[0]['ket'],
				"petugas" => $jsonEtc[0]['petugas'],

        		"total" => $jsonEtc[0]['totalarfh'],

                "orangpic" => $jsonEtc[0]['pic'],
        		"orangfinance" => $jsonEtc[0]['finance'],

        		"requestv" => $totalPriceArf,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "request2" => $jsonEtc[0]['requester'],

				//"cus_kode" => $cusKode,
            );
            $log['asf-header-before'] =  $this->asfH->fetchRow("trano = '$trano'")->toArray();
             $this->asfH->delete("trano = '$trano'");
             $this->asfH->insert($arrayInsert);
            $log2['asf-header-after'] =  $this->asfH->fetchRow("trano = '$trano'")->toArray();
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "UPDATE",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);
             $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateasfbudgetAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $sales = $this->getRequest()->getParam('sales');
       $etc = $this->getRequest()->getParam('etc');
       $json2 = $this->getRequest()->getParam('posts2');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonData2 = Zend_Json::decode($json2);
       $jsonEtc = Zend_Json::decode($etc);

       $totalPriceArf = 0;
        $urut = 1;
        $urut2 = 1;

       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));
       if($sales)
        {
           $tipe = 'S';
        }
        else
        {
            $tipe = 'O';
        }

       $trano = $jsonEtc[0]['trano'];

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');


       $result = $this->workflow->setWorkflowTrans($trano,'ASFO','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
        $temp = array();
       if($jsonData)
       {
           $log['asfdd-detail-before'] =  $this->asf->fetchAll("trano = '$trano'")->toArray();
           $this->asf->delete("trano = '$trano'");
      foreach($jsonData as $key => $val)
      {

          $tranotemp = $val['arf_no'];

          if ($temp[$tranotemp] == '')
          {
              $temp[$tranotemp]['total'] = $val['totalPrice'];
               $temp[$tranotemp]['trano'] = $tranotemp;
              $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
              $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
              $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
          }
          else
              $temp[$tranotemp]['total'] += $val['totalPrice'];

          $arrayInsert = array(
                "trano" => $trano,
				"tgl" => date('Y-m-d'),
				"arf_no" => $val['arf_no'],
				"tglarf" => $val['tgl_arf'],
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

//				"petugas" => $this->session->userName,
                "petugas" => $val['petugas'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['rateidr'],
                "cfs_kode" => $val['cfs_kode'],
                "tipe" => $tipe
			);
            $urut++;

            $this->asf->insert($arrayInsert);
      }
      $log2['asfdd-detail-after'] =  $this->asf->fetchAll("trano = '$trano'")->toArray();
      }

       if($jsonData2)
      {
          $log['asfddcancel-detail-before'] =  $this->asfc->fetchAll("trano = '$trano'")->toArray();
          $this->asfc->delete("trano = '$trano'");
      foreach($jsonData2 as $key => $val)
      {
          $tranotemp = $val['arf_no'];

          if ($temp[$tranotemp] == '')
          {
              $temp[$tranotemp]['total'] = $val['totalPrice'];
               $temp[$tranotemp]['trano'] = $tranotemp;
              $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
              $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
              $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
          }
          else
              $temp[$tranotemp]['total'] += $val['totalPrice'];

          $arrayInsert = array(
                "trano" => $trano,
				"tgl" => date('Y-m-d'),
				"arf_no" => $val['arf_no'],
				"tglarf" => $val['tgl_arf'],
				"urut" => $urut2,
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

//				"petugas" => $this->session->userName,
                "petugas" => $val['petugas'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['rateidr'],
                "cfs_kode" => $val['cfs_kode'],
                "tipe" => $tipe
			);
            $urut2++;

            $this->asfc->insert($arrayInsert);

      }
          $log2['asfddcancel-detail-after'] =  $this->asfc->fetchAll("trano = '$trano'")->toArray();
      
      }
       $log['asfd-detail-before'] =  $this->asfD->fetchAll("trano = '$trano'")->toArray();
       $this->asfD->delete("trano = '$trano'");
       foreach($temp as $key => $val)
       {
        $balance = $val['totalPriceInArfh'] - $val['total'];
        $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

        $arrayD = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonData[0]['ket'],
                "total" => $balance,
				"petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
        		"totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "tipe" => $tipe

            );
             $this->asfD->insert($arrayD);

       }
       $log2['asfd-detail-after'] =  $this->asfD->fetchAll("trano = '$trano'")->toArray();

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        	$arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "arf_no" =>  $arfno,
                "tglarf" => $jsonEtc[0]['tgl_arf'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonEtc[0]['ket'],
				"petugas" => $jsonEtc[0]['petugas'],

        		"total" => $jsonEtc[0]['totalarfh'],

                "orangpic" => $jsonEtc[0]['pic'],
        		"orangfinance" => $jsonEtc[0]['finance'],

        		"requestv" => $totalPriceArf,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "request2" => $jsonEtc[0]['requester'],
                "tipe" => $tipe

				//"cus_kode" => $cusKode,
            );
            $log['asf-header-before'] =  $this->asfH->fetchRow("trano = '$trano'")->toArray();
            $this->asfH->delete("trano = '$trano'");
             $this->asfH->insert($arrayInsert);
             $log2['asf-header-after'] =  $this->asfH->fetchRow("trano = '$trano'")->toArray();
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "UPDATE",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);
             $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateasfsalesAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $sales = $this->getRequest()->getParam('sales');
       $etc = $this->getRequest()->getParam('etc');
       $json2 = $this->getRequest()->getParam('posts2');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonData2 = Zend_Json::decode($json2);
       $jsonEtc = Zend_Json::decode($etc);

       $totalPriceArf = 0;
        $urut = 1;
        $urut2 = 1;

       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));
       if($sales)
        {
           $tipe = 'S';
        }
        else
        {
            $tipe = 'O';
        }

       $trano = $jsonEtc[0]['trano'];

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');


       $result = $this->workflow->setWorkflowTrans($trano,'ASF','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
        $temp = array();
       if($jsonData)
       {
           $this->asf->delete("trano = '$trano'");
      foreach($jsonData as $key => $val)
      {

          $tranotemp = $val['arf_no'];

          if ($temp[$tranotemp] == '')
          {
              $temp[$tranotemp]['total'] = $val['totalPrice'];
               $temp[$tranotemp]['trano'] = $tranotemp;
              $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
              $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
              $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
          }
          else
              $temp[$tranotemp]['total'] += $val['totalPrice'];

          $arrayInsert = array(
                "trano" => $trano,
				"tgl" => date('Y-m-d'),
				"arf_no" => $val['arf_no'],
				"tglarf" => $val['tgl_arf'],
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

//				"petugas" => $this->session->userName,
                "petugas" => $val['petugas'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['rateidr'],
                "tipe" => $tipe
			);
            $urut++;

            $this->asf->insert($arrayInsert);
      }
       }

       if($jsonData2)
      {
            $this->asfc->delete("trano = '$trano'");
      foreach($jsonData2 as $key => $val)
      {
          $tranotemp = $val['arf_no'];

          if ($temp[$tranotemp] == '')
          {
              $temp[$tranotemp]['total'] = $val['totalPrice'];
               $temp[$tranotemp]['trano'] = $tranotemp;
              $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
              $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
              $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
          }
          else
              $temp[$tranotemp]['total'] += $val['totalPrice'];

          $arrayInsert = array(
                "trano" => $trano,
				"tgl" => date('Y-m-d'),
				"arf_no" => $val['arf_no'],
				"tglarf" => $val['tgl_arf'],
				"urut" => $urut2,
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

//				"petugas" => $this->session->userName,
                "petugas" => $val['petugas'],
				"val_kode" => $val['val_kode'],
				"rateidr" => $val['rateidr'],
                "tipe" => $tipe
			);
            $urut2++;

            $this->asfc->insert($arrayInsert);

      }
      }

       $this->asfD->delete("trano = '$trano'");
       foreach($temp as $key => $val)
       {
        $balance = $val['totalPriceInArfh'] - $val['total'];
        $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

        $arrayD = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonData[0]['ket'],
                "total" => $balance,
				"petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
        		"totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "tipe" => $tipe

            );
             $this->asfD->insert($arrayD);

       }
//         $this->asfD->insert($arrayD);

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        	$arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),
                "arf_no" =>  $arfno,
                "tglarf" => $jsonEtc[0]['tgl_arf'],
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],


        		"ket" => $jsonEtc[0]['ket'],
				"petugas" => $jsonEtc[0]['petugas'],

        		"total" => $jsonEtc[0]['totalarfh'],

                "orangpic" => $jsonEtc[0]['pic'],
        		"orangfinance" => $jsonEtc[0]['finance'],

        		"requestv" => $totalPriceArf,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "request2" => $jsonEtc[0]['requester'],
                "tipe" => $tipe

				//"cus_kode" => $cusKode,
            );
             $this->asfH->delete("trano = '$trano'");
             $this->asfH->insert($arrayInsert);
             $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }
    
   public function prAction()
   {
       
   }

   public function poAction()
   {

   }
   
   public function rpiAction()
   {

   }
   
   public function arfAction()
   {

   }
   
   public function asfAction()
   {

   }

   public function afeAction()
   {

   }
	public function addasfAction()
   {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ARFtrano = $trano;   
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   		}
   }

    public function addasfbudgetAction()
    {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ARFtrano = $trano;
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   		}
    }

    public function addasfsalesAction()
    {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ARFtrano = $trano;
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   		}
    }
   
   public function addprAction()
   {
   		$isCancel = $this->getRequest()->getParam("returnback");
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   		}
   }

   public function addprbudgetAction()
   {
       
   }

   public function addprsalesAction()
   {

   }

   public function addarfAction()
   {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ARFtrano = $trano;
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   		}
   }
//   {
//   		$isCancel = $this->getRequest()->getParam("returnback");
//   		if ($isCancel)
//   		{
//   			$this->view->json = $this->getRequest()->getParam("posts");
//   		}
//   }
    public function addarfsalesAction()
    {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
    }

    public function addarfbudgetAction()
    {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
    }

	public function addrpiAction()
   {
   		$isCancel = $this->getRequest()->getParam("returnback");
        $this->view->no_po = $this->getRequest()->getParam("no_po");
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   			$this->view->etc = $this->getRequest()->getParam("etc");
   			$this->view->trano = $this->getRequest()->getParam("trano");
   		}
   }

    public function addrpibudgetAction()
   {
   		$isCancel = $this->getRequest()->getParam("returnback");
        $this->view->no_po = $this->getRequest()->getParam("no_po");
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   			$this->view->etc = $this->getRequest()->getParam("etc");
   			$this->view->trano = $this->getRequest()->getParam("trano");
   		}
   }

    public function addrpisalesAction()
   {
   		$isCancel = $this->getRequest()->getParam("returnback");
        $this->view->no_po = $this->getRequest()->getParam("no_po");
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   			$this->view->etc = $this->getRequest()->getParam("etc");
   			$this->view->trano = $this->getRequest()->getParam("trano");
   		}
   }
   
 	public function addpoAction()
   {
   		$isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->PRtrano = $trano;   
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   		}
   }

    public function addpobudgetAction()
   {
   		$isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->PRtrano = $trano;
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   		}
   }

    public function addposalesAction()
   {
   		$isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->PRtrano = $trano;
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
   		}
   }
   
   public function editprAction()
   {
   		$trano = $this->getRequest()->getParam("trano");
   		$prd = $this->procurement->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");
        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach($prh as $key => $val)
         {
          if ($val == '""')
              $prh[$key] = '';
        }


   		if ($prh['revisi'] == '' || $prh['revisi'] == '""')
   		{
   			$prh['revisi'] = 1;
   		}
   		else
	   		$prh['revisi'] = abs($prh['revisi']) + 1;
//   		if ($prd)
//   		{
//   			foreach($prd as $key => $val)
//   			{
//   				$prd[$key]['id'] = $key + 1;
//   				$kodeBrg = $val['kode_brg'];
//   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
//   				if ($barang)
//   				{
//   					$prd[$key]['uom'] = $barang['sat_kode'];
//   				}
//   				if ($val['val_kode'] == 'IDR')
//   					$prd[$key]['hargaIDR'] = $val['harga'];
//   				elseif ($val['val_kode'] == 'USD')
//   					$prd[$key]['hargaUSD'] = $val['harga'];
//
//                $prd[$key]['net_act'] = $val['myob'];
//                $prd[$key]['fromBoq3'] = 1;
//   			}
//
//   			Zend_Loader::loadClass('Zend_Json');
//   			$jsonData = Zend_Json::encode($prd);
	   		$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->jsonCancel = $this->getRequest()->getParam("posts");
	   		}
//	   		else
//	   			$this->view->json = $jsonData;
            $this->view->json = true;
	       	$this->view->trano = $trano;
	       	$this->view->tgl = $prh['tgl'];
	       	$this->view->revisi = $prh['revisi'];
	       	$this->view->budgetType = $prh['budgettype'];
	       	$this->view->prj_nama = $prh['prj_nama'];
	       	$this->view->sit_nama = $prh['sit_nama'];
	       	$this->view->prj_kode = $prh['prj_kode'];
	       	$this->view->sit_kode = $prh['sit_kode'];
      Zend_Loader::loadClass('Zend_Json');
      $file = Zend_Json::encode($file);
      $this->view->file = $file;
//   		}
   }

    public function editprbudgetAction()
   {
   		$trano = $this->getRequest()->getParam("trano");
   		$prd = $this->procurement->fetchRow("trano = '$trano'",array("urut ASC"))->toArray();
   		$prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach($prh as $key => $val)
         {
          if ($val == '""')
              $prh[$key] = '';
        }


   		if ($prh['revisi'] == '' || $prh['revisi'] == '""')
   		{
   			$prh['revisi'] = 1;
   		}
   		else
	   		$prh['revisi'] = abs($prh['revisi']) + 1;
   		if ($prd)
   		{
//   			foreach($prd as $key => $val)
//   			{
//   				$prd[$key]['id'] = $key + 1;
//   				$kodeBrg = $val['kode_brg'];
//                $workid = $val['workid'];
//                $sitKode = $val['sit_kode'];
//                $prjKode = $val['prj_kode'];
//
//   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
//   				if ($barang)
//   				{
//   					$prd[$key]['uom'] = $barang['sat_kode'];
//   				}
//   				if ($val['val_kode'] == 'IDR')
//   					$prd[$key]['hargaIDR'] = $val['harga'];
//   				elseif ($val['val_kode'] == 'USD')
//   					$prd[$key]['hargaUSD'] = $val['harga'];
//
//                $prd[$key]['dep_kode'] = $val['prj_kode'];
//                $prd[$key]['dep_nama'] = $val['prj_nama'];
//                $prd[$key]['per_kode'] = $val['sit_kode'];
//                $prd[$key]['per_nama'] = $val['sit_nama'];
//                $prd[$key]['budgetid'] = $val['workid'];
//                $prd[$key]['budgetname'] = $val['workname'];
//                $prd[$key]['totalPrice'] = $val['jumlah'];
//
//                $boq3 = $this->budget->getBudgetOverhead($prjKode,$sitKode,$workid);
//                if ($prd[$key]['val_kode'] == 'IDR')
//                {
//                    $prd[$key]['totalPriceBudget'] = $boq3[0]['totalIDR'];
//                }
//                else
//                {
//                    $prd[$key]['totalPriceBudget'] = $boq3[0]['totalHargaUSD'];
//                }
//
//                $pr = $this->quantity->getPrOverheadQuantity($prjKode,$sitKode,$workid);
//                    if ($pr != '')
//                    {
//
//                        $prd[$key]['totalPRraw'] = $pr['qty'];
//
//                        $prd[$key]['totalPricePRraw'] = $pr['total'];
//
//                    }
//
//                    else
//                    {
//                        $prd[$key]['totalPRraw'] = 0;
//
//                        $prd[$key]['totalPricePRraw'] = 0;
//                    }
//
//                    $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid);
//                    if ($arf != '')
//                    {
//
//                        $prd[$key]['totalARF'] = $arf['qty'];
//
//                        if ($prd[$key]['val_kode'] == 'IDR')
//                        {
//
//                            $prd[$key]['totalPriceARF'] = $arf['totalIDR'];
//                        }
//                        else
//                        {
//
//                            $prd[$key]['totalPriceARF'] = $arf['totalUSD'];
//                        }
//
//                    }
//
//                    else
//                    {
//                        $prd[$key]['totalARF'] = 0;
//
//                        $prd[$key]['totalPriceARF'] = 0;
//                    }
//                    $totalQtyPRARF = $prd[$key]['totalPRraw']+ $prd[$key]['totalARF'];
//                    $totalPricePRARF = $prd[$key]['totalPricePRraw']+ $prd[$key]['totalPriceARF'];
//
//                    $prd[$key]['totalPR'] = $totalQtyPRARF;
//                    $prd[$key]['totalPricePR'] = $totalPricePRARF;
//
//
//                $prd[$key]['net_act'] = $val['myob'];
//                $prd[$key]['fromBoq3'] = 1;
//   			}

//   			Zend_Loader::loadClass('Zend_Json');
//   			$jsonData = Zend_Json::encode($prd);
	   		$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->jsonCancel = $this->getRequest()->getParam("posts");
	   		}
//	   		else
//	   			$this->view->json = $jsonData;
            $this->view->json = true;
            $this->view->trano = $trano;
	       	$this->view->tgl = $prh['tgl'];
	       	$this->view->revisi = $prh['revisi'];
	       	$this->view->budgetType = $prh['budgettype'];
	       	$this->view->prj_nama = $prh['prj_nama'];
	       	$this->view->sit_nama = $prh['sit_nama'];
	       	$this->view->prj_kode = $prh['prj_kode'];
	       	$this->view->sit_kode = $prh['sit_kode'];
   		}
      Zend_Loader::loadClass('Zend_Json');
      $file = Zend_Json::encode($file);
      $this->view->file = $file;
   }

    public function editprsalesAction()
   {
   		$trano = $this->getRequest()->getParam("trano");
   		$prd = $this->procurement->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach($prh as $key => $val)
         {
          if ($val == '""')
              $prh[$key] = '';
        }


   		if ($prh['revisi'] == '' || $prh['revisi'] == '""')
   		{
   			$prh['revisi'] = 1;
   		}
   		else
	   		$prh['revisi'] = abs($prh['revisi']) + 1;
   		if ($prd)
   		{
   			foreach($prd as $key => $val)
   			{
   				$prd[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
                $workid = $val['workid'];
                $sitKode = $val['sit_kode'];
                $prjKode = $val['prj_kode'];

   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$prd[$key]['uom'] = $barang['sat_kode'];
   				}
   				if ($val['val_kode'] == 'IDR')
   					$prd[$key]['hargaIDR'] = $val['harga'];
   				elseif ($val['val_kode'] == 'USD')
   					$prd[$key]['hargaUSD'] = $val['harga'];

                $prd[$key]['dep_kode'] = $val['prj_kode'];
                $prd[$key]['dep_nama'] = $val['prj_nama'];
                $prd[$key]['per_kode'] = $val['sit_kode'];
                $prd[$key]['per_nama'] = $val['sit_nama'];
                $prd[$key]['budgetid'] = $val['workid'];
                $prd[$key]['budgetname'] = $val['workname'];
                $prd[$key]['totalPrice'] = $val['jumlah'];

                $boq3 = $this->budget->getBudgetOverhead($prjKode,$sitKode,$workid);
                if ($prd[$key]['val_kode'] == 'IDR')
                {
                    $prd[$key]['totalPriceBudget'] = $boq3[0]['totalIDR'];
                }
                else
                {
                    $prd[$key]['totalPriceBudget'] = $boq3[0]['totalHargaUSD'];
                }

                $pr = $this->quantity->getPrOverheadQuantity($prjKode,$sitKode,$workid);
                    if ($pr != '')
                    {

                        $prd[$key]['totalPRraw'] = $pr['qty'];

                        $prd[$key]['totalPricePRraw'] = $pr['total'];

                    }

                    else
                    {
                        $prd[$key]['totalPRraw'] = 0;

                        $prd[$key]['totalPricePRraw'] = 0;
                    }

                    $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid);
                    if ($arf != '')
                    {

                        $prd[$key]['totalARF'] = $arf['qty'];

                        if ($prd[$key]['val_kode'] == 'IDR')
                        {

                            $prd[$key]['totalPriceARF'] = $arf['totalIDR'];
                        }
                        else
                        {

                            $prd[$key]['totalPriceARF'] = $arf['totalUSD'];
                        }

                    }

                    else
                    {
                        $prd[$key]['totalARF'] = 0;

                        $prd[$key]['totalPriceARF'] = 0;
                    }
                    $totalQtyPRARF = $prd[$key]['totalPRraw']+ $prd[$key]['totalARF'];
                    $totalPricePRARF = $prd[$key]['totalPricePRraw']+ $prd[$key]['totalPriceARF'];

                    $prd[$key]['totalPR'] = $totalQtyPRARF;
                    $prd[$key]['totalPricePR'] = $totalPricePRARF;


                $prd[$key]['net_act'] = $val['myob'];
                $prd[$key]['fromBoq3'] = 1;
   			}

   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($prd);
	   		$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	   			$this->view->json = $jsonData;
	       	$this->view->trano = $trano;
	       	$this->view->tgl = $prh['tgl'];
	       	$this->view->revisi = $prh['revisi'];
	       	$this->view->budgetType = $prh['budgettype'];
	       	$this->view->prj_nama = $prh['prj_nama'];
	       	$this->view->sit_nama = $prh['sit_nama'];
	       	$this->view->prj_kode = $prh['prj_kode'];
	       	$this->view->sit_kode = $prh['sit_kode'];
   		}
      Zend_Loader::loadClass('Zend_Json');
      $file = Zend_Json::encode($file);
      $this->view->file = $file;
   }
   
    public function editarf2Action()
    {
     $this->view->uid = $this->session->userName;
     $this->view->nama = $this->session->name;

     $trano = $this->getRequest()->getParam("trano");
     $arfh = $this->arfh->fetchRow("trano = '$trano'");
     $arfd = $this->arfd->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
     $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

      if ($arfh)
          $arfh = $arfh->toArray();
      $tmp = array();

     foreach($arfd as $key => $val)
     {
      foreach ($val as $key2 => $val2)
      {
          if ($val2 == '""')
              $arfd[$key][$key2] = '';
      }
        $arfd[$key]['id'] = $key + 1;
        $kodeBrg = $val['kode_brg'];
        $workid = $val['workid'];
        $sitKode = $val['sit_kode'];
        $prjKode = $val['prj_kode'];
      
        $arfd[$key]['priceArf'] = $val['harga'];
        $arfd[$key]['totalARF'] = $val['total'];
        $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
//        $arfd[$key]['trano'] = $arfd[$key]['pr_no'];


//        if(!in_array($arfd[$key]['trano'],$tmp))
//          $tmp['trano'] = $arfd[$key]['trano'];
//        unset($arfd[$key]['pr_no']);
//        unset($arfd[$key]['harga']);
//        unset($arfd[$key]['total']);
         $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
         if ($barang)
         {
             $arfd[$key]['uom'] = $barang['sat_kode'];
         }

         $boq3 = $this->budget->getBoq3ByOne($prjKode,$sitKode,$workid,$kodeBrg);
         if ($arfd[$key]['val_kode'] == 'IDR')
        {
//            $arfd[$key]['priceBOQ3'] = $boq3['hargaIDR'];
            $arfd[$key]['totalBOQ3'] = $boq3['totalIDR'];
        }
        else
        {
//            $arfd[$key]['priceBOQ3'] = $boq3['hargaUSD'];
            $arfd[$key]['totalBOQ3'] = $boq3['totalUSD'];
        }
        $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$workid,$kodeBrg);
        $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid,$kodeBrg);
        $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$workid,$kodeBrg);
//        $reimburs = $this->quantity->getReimbursementQuantity($prjKode,$sitKode,$workid,$kodeBrg);
//                var_dump($po);die;
        if ($po != '' )
        {
                $arfd[$key]['totalqtyPO'] = $po['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $arfd[$key]['totalPO'] = $po['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyPO'] = 0;
                $arfd[$key]['totalPO'] = 0;
        }
        if ($arf != '' )
        {
                $arfd[$key]['totalqtyARF'] = $arf['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalInARF'] = $arf['totalIDR'];
                else
                    $arfd[$key]['totalInARF'] = $arf['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyARF'] = 0;
                $arfd[$key]['totalARF'] = 0;
        }

        if ($asfcancel != '' )
        {
                $arfd[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                else
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyASFCancel'] = 0;
                $arfd[$key]['totalASFCancel'] = 0;
        }

//        if ($reimburs != '' )
//                {
//                        $arfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
//                        if ($arfd[$key]['val_kode'] == 'IDR')
//                            $arfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
//                        else
//                            $arfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
//                }
//                else
//                {
//                        $arfd[$key]['totalqtyReimburs'] = 0;
//                        $arfd[$key]['totalReimburs'] = 0;
//                }
        $totalpoarfasfc = (($arfd[$key]['totalPO'] +  $arfd[$key]['totalInARF']) -  $arfd[$key]['totalASFCancel'] ) ;
        $arfd[$key]['totalPoArfAsfc'] = $totalpoarfasfc;

     }
//                 var_dump($arfd);die;

        foreach($arfh as $key => $val)
        {
            if ($val == '""')
                $arfh[$key] = '';
        }

        if ($arfh['bt'] == 'Y')
            $arfh['bt'] = 1;
        else
            $arfh['bt'] = 0;
        $tmp2 = $arfh;
        unset($arfh);
        $arfh[0] = $tmp2;
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($arfd);
        $jsonData2 = Zend_Json::encode($arfh);

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
         $this->view->tgl = date('d-m-Y',strtotime($arfh[0]['tgl']));
      $this->view->pr_no = $arfh[0]['pr_no'];
      $this->view->val_kode = $arfh[0]['val_kode'];
      $this->view->request = $arfh[0]['request'];
      $this->view->orangfinance = $arfh[0]['orangfinance'];
      $this->view->ket = $arfh[0]['ket'];
      $this->view->ketin = $arfh[0]['ketin'];

      Zend_Loader::loadClass('Zend_Json');
      $file = Zend_Json::encode($file);
      $this->view->file = $file;
    }

    public function editarfbudgetAction()
    {
     $this->view->uid = $this->session->userName;
     $this->view->nama = $this->session->name;

     $trano = $this->getRequest()->getParam("trano");
     $arfh = $this->arfh->fetchRow("trano = '$trano'");
     $arfd = $this->arfd->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
     $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

      if ($arfh)
          $arfh = $arfh->toArray();
      $tmp = array();

     foreach($arfd as $key => $val)
     {
      foreach ($val as $key2 => $val2)
      {
          if ($val2 == '""')
              $arfd[$key][$key2] = '';
      }
        $arfd[$key]['id'] = $key + 1;
        $kodeBrg = $val['kode_brg'];
        $workid = $val['workid'];
        $sitKode = $val['sit_kode'];
        $prjKode = $val['prj_kode'];

        $arfd[$key]['priceArf'] = $val['harga'];
        $arfd[$key]['totalARF'] = $val['total'];
        $arfd[$key]['budgetid'] = $val['workid'];
        $arfd[$key]['budgetname'] = $val['workname'];
         $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
         $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
         if ($barang)
         {
             $arfd[$key]['uom'] = $barang['sat_kode'];
         }

         $boq3 = $this->budget->getBudgetOverhead($prjKode,$sitKode,$workid);

         if ($arfd[$key]['val_kode'] == 'IDR')
        {
            $arfd[$key]['totalBOQ3'] = $boq3[0]['totalIDR'];
        }
        else
        {
            $arfd[$key]['totalBOQ3'] = $boq3[0]['totalUSD'];
        }
        $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$workid);
        $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid);
        $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$workid);
        $reimburs = $this->quantity->getReimbursementQuantity($prjKode,$sitKode,$workid);

        if ($po != '' )
        {
                $arfd[$key]['totalqtyPO'] = $po['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $arfd[$key]['totalPO'] = $po['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyPO'] = 0;
                $arfd[$key]['totalPO'] = 0;
        }
        if ($arf != '' )
        {
                $arfd[$key]['totalqtyARF'] = $arf['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalInARF'] = $arf['totalIDR'];
                else
                    $arfd[$key]['totalInARF'] = $arf['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyARF'] = 0;
                $arfd[$key]['totalARF'] = 0;
        }

        if ($asfcancel != '' )
        {
                $arfd[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                else
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyASFCancel'] = 0;
                $arfd[$key]['totalASFCancel'] = 0;
        }

        if ($reimburs != '' )
                {
                        $arfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
                        if ($arfd[$key]['val_kode'] == 'IDR')
                            $arfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
                        else
                            $arfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
                }
                else
                {
                        $arfd[$key]['totalqtyReimburs'] = 0;
                        $arfd[$key]['totalReimburs'] = 0;
                }
        $totalpoarfasfc = (($arfd[$key]['totalPO'] +  $arfd[$key]['totalInARF']) -  $arfd[$key]['totalASFCancel'] ) ;
        $arfd[$key]['totalPoArfAsfc'] = $totalpoarfasfc;

     }

      foreach($arfh as $key => $val)
         {
          if ($val == '""')
              $arfh[$key] = '';
      }
        if ($arfh['bt'] == 'Y')
            $arfh['bt'] = 1;
        else
            $arfh['bt'] = 0;
      $tmp2 = $arfh;
      unset($arfh);
      $arfh[0] = $tmp2;
         Zend_Loader::loadClass('Zend_Json');
         $jsonData = Zend_Json::encode($arfd);
         $jsonData2 = Zend_Json::encode($arfh);

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
         $this->view->tgl = date('d-m-Y',strtotime($arfh[0]['tgl']));
      $this->view->pr_no = $arfh[0]['pr_no'];
      $this->view->val_kode = $arfh[0]['val_kode'];
      $this->view->request = $arfh[0]['request'];
      $this->view->orangfinance = $arfh[0]['orangfinance'];
      $this->view->ket = $arfh[0]['ket'];
      $this->view->ketin = $arfh[0]['ketin'];

      Zend_Loader::loadClass('Zend_Json');
      $file = Zend_Json::encode($file);
      $this->view->file = $file;
    }

    public function editarfsalesAction()
    {
     $this->view->uid = $this->session->userName;
     $this->view->nama = $this->session->name;

     $trano = $this->getRequest()->getParam("trano");
     $arfh = $this->arfh->fetchRow("trano = '$trano'");
     $arfd = $this->arfd->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
     $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

      if ($arfh)
          $arfh = $arfh->toArray();
      $tmp = array();

     foreach($arfd as $key => $val)
     {
      foreach ($val as $key2 => $val2)
      {
          if ($val2 == '""')
              $arfd[$key][$key2] = '';
      }
        $arfd[$key]['id'] = $key + 1;
        $kodeBrg = $val['kode_brg'];
        $workid = $val['workid'];
        $sitKode = $val['sit_kode'];
        $prjKode = $val['prj_kode'];

        $arfd[$key]['priceArf'] = $val['harga'];
        $arfd[$key]['totalARF'] = $val['total'];
        $arfd[$key]['budgetid'] = $val['workid'];
        $arfd[$key]['budgetname'] = $val['workname'];
         $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
         $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
         if ($barang)
         {
             $arfd[$key]['uom'] = $barang['sat_kode'];
         }

         $boq3 = $this->budget->getBoq3ByOne($prjKode,$sitKode,$workid,$kodeBrg);
         if ($arfd[$key]['val_kode'] == 'IDR')
        {
            $arfd[$key]['totalBOQ3'] = $boq3['totalIDR'];
        }
        else
        {
            $arfd[$key]['totalBOQ3'] = $boq3['totalUSD'];
        }
        $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$workid,$kodeBrg);
        $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid,$kodeBrg);
        $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$workid,$kodeBrg);
        $reimburs = $this->quantity->getReimbursementQuantity($prjKode,$sitKode,$workid,$kodeBrg);

        if ($po != '' )
        {
                $arfd[$key]['totalqtyPO'] = $po['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $arfd[$key]['totalPO'] = $po['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyPO'] = 0;
                $arfd[$key]['totalPO'] = 0;
        }
        if ($arf != '' )
        {
                $arfd[$key]['totalqtyARF'] = $arf['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalInARF'] = $arf['totalIDR'];
                else
                    $arfd[$key]['totalInARF'] = $arf['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyARF'] = 0;
                $arfd[$key]['totalARF'] = 0;
        }

        if ($asfcancel != '' )
        {
                $arfd[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                else
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyASFCancel'] = 0;
                $arfd[$key]['totalASFCancel'] = 0;
        }

        if ($reimburs != '' )
                {
                        $arfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
                        if ($arfd[$key]['val_kode'] == 'IDR')
                            $arfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
                        else
                            $arfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
                }
                else
                {
                        $arfd[$key]['totalqtyReimburs'] = 0;
                        $arfd[$key]['totalReimburs'] = 0;
                }
        $totalpoarfasfc = (($arfd[$key]['totalPO'] +  $arfd[$key]['totalInARF']) -  $arfd[$key]['totalASFCancel'] ) ;
        $arfd[$key]['totalPoArfAsfc'] = $totalpoarfasfc;

     }

      foreach($arfh as $key => $val)
     {
          if ($val == '""')
              $arfh[$key] = '';
      }
        if ($arfh['bt'] == 'Y')
            $arfh['bt'] = 1;
        else
            $arfh['bt'] = 0;
      $tmp2 = $arfh;
      unset($arfh);
      $arfh[0] = $tmp2;
         Zend_Loader::loadClass('Zend_Json');
         $jsonData = Zend_Json::encode($arfd);
         $jsonData2 = Zend_Json::encode($arfh);

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
         $this->view->tgl = date('d-m-Y',strtotime($arfh[0]['tgl']));
      $this->view->pr_no = $arfh[0]['pr_no'];
      $this->view->val_kode = $arfh[0]['val_kode'];
      $this->view->request = $arfh[0]['request'];
      $this->view->orangfinance = $arfh[0]['orangfinance'];
      $this->view->ket = $arfh[0]['ket'];

      Zend_Loader::loadClass('Zend_Json');
      $file = Zend_Json::encode($file);
      $this->view->file = $file;
    }

   public function editpoAction()
   {
   	    $trano = $this->getRequest()->getParam("trano");

   		$pod = $this->purchase->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$poh = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach($poh as $key => $val)
        {
          if ($val == "\"\"")
              $poh[$key] = '';
        }

   		if ($poh['revisi'] == '' || $poh['revisi'] == '""')
   		{
   			$poh['revisi'] = 1;
   		}
   		else
	   		$poh['revisi'] = abs($poh['revisi']) + 1;

           
        if ($pod)
   		{
   			foreach($pod as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $pod[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $pod[$key][$key2] = str_replace("\""," inch",$pod[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $pod[$key][$key2] = str_replace("'"," inch",$pod[$key][$key2]);
                }
   				$pod[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$pod[$key]['uom'] = $barang['sat_kode'];
   				}
   				
   			}
   			
   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($pod);
   			$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	       		$this->view->json = $jsonData;
        }
           
           if ($poh['budgettype'] == '' || $poh['budgettype'] == '""')
               $poh['budgettype'] = 'Project';
	       		
	       	$this->view->trano = $trano;
            $this->view->prjKode = $poh['prj_kode'];
	       	$this->view->tgl = $poh['tgl'];
	       	$this->view->revisi = $poh['revisi'];
	       	$this->view->prjKode = $poh['prj_kode'];
	       	$this->view->ppn = $poh['ppn'];
            $this->view->statusppn = $poh['statusppn'];
	        $this->view->sup_kode = $poh['sup_kode'];
	       	$this->view->sup_nama = $poh['sup_nama'];
	       	$this->view->val_kode = $poh['val_kode'];

	       	$this->view->rateidr = $poh['rateidr'];
	       	$this->view->type_po = $poh['typepo2'];
            $this->view->paymentterm = trim($poh['paymentterm']);
            $poh['deliverytext'] = preg_replace("[^A-Za-z0-9-.,]","",$poh['deliverytext']);
	       	$this->view->tujuan =preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['deliverytext']);
	       	$this->view->tgl_kirim = trim($poh['tgldeliesti']);
            $poh['ket'] = preg_replace("[^A-Za-z0-9-.,]","",$poh['ket']);
            $this->view->ket =preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['ket']);
            $this->view->ketin =trim($poh['ketin']);

//            $this->view->ket = trim($poh['ket']);

	       	
	       	$this->view->oripo = trim($poh['budgettype']);
	       	$this->view->invoiceto = trim($poh['invoiceto']);
            Zend_Loader::loadClass('Zend_Json');
   			$file = Zend_Json::encode($file);
	       	$this->view->file = $file;
   }

    public function editpobudgetAction()
   {
   	    $trano = $this->getRequest()->getParam("trano");

   		$pod = $this->purchase->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$poh = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach($poh as $key => $val)
        {
          if ($val == "\"\"")
              $poh[$key] = '';
        }

   		if ($poh['revisi'] == '' || $poh['revisi'] == '""')
   		{
   			$poh['revisi'] = 1;
   		}
   		else
	   		$poh['revisi'] = abs($poh['revisi']) + 1;


        if ($pod)
   		{
   			foreach($pod as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $pod[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $pod[$key][$key2] = str_replace("\""," inch",$pod[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $pod[$key][$key2] = str_replace("'"," inch",$pod[$key][$key2]);
                }
   				$pod[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$pod[$key]['uom'] = $barang['sat_kode'];
   				}

   			}

   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($pod);
   			$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	       		$this->view->json = $jsonData;
        }

           if ($poh['budgettype'] == '' || $poh['budgettype'] == '""')
               $poh['budgettype'] = 'Project';

	       	$this->view->trano = $trano;
            $this->view->prjKode = $poh['prj_kode'];
	       	$this->view->tgl = $poh['tgl'];
	       	$this->view->revisi = $poh['revisi'];
	       	$this->view->prjKode = $poh['prj_kode'];
	       	$this->view->ppn = $poh['ppn'];
            $this->view->statusppn = $poh['statusppn'];
	        $this->view->sup_kode = $poh['sup_kode'];
	       	$this->view->sup_nama = $poh['sup_nama'];
	       	$this->view->val_kode = $poh['val_kode'];

	       	$this->view->rateidr = $poh['rateidr'];
	       	$this->view->type_po = $poh['typepo2'];
            $this->view->paymentterm = trim($poh['paymentterm']);
            $poh['deliverytext'] = preg_replace("[^A-Za-z0-9-.,]","",$poh['deliverytext']);
	       	$this->view->tujuan =preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['deliverytext']);
	       	$this->view->tgl_kirim = trim($poh['tgldeliesti']);
            $poh['ket'] = preg_replace("[^A-Za-z0-9-.,]","",$poh['ket']);
            $this->view->ket =preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['ket']);
            $this->view->ketin =trim($poh['ketin']);

//            $this->view->ket = trim($poh['ket']);


	       	$this->view->oripo = trim($poh['budgettype']);
	       	$this->view->invoiceto = trim($poh['invoiceto']);
            Zend_Loader::loadClass('Zend_Json');
   			$file = Zend_Json::encode($file);
	       	$this->view->file = $file;
   }

    public function editposalesAction()
   {
   	    $trano = $this->getRequest()->getParam("trano");

   		$pod = $this->purchase->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$poh = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach($poh as $key => $val)
        {
          if ($val == "\"\"")
              $poh[$key] = '';
        }

   		if ($poh['revisi'] == '' || $poh['revisi'] == '""')
   		{
   			$poh['revisi'] = 1;
   		}
   		else
	   		$poh['revisi'] = abs($poh['revisi']) + 1;


        if ($pod)
   		{
   			foreach($pod as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $pod[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $pod[$key][$key2] = str_replace("\""," inch",$pod[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $pod[$key][$key2] = str_replace("'"," inch",$pod[$key][$key2]);
                }
   				$pod[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$pod[$key]['uom'] = $barang['sat_kode'];
   				}

   			}

   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($pod);
   			$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	       		$this->view->json = $jsonData;
        }

           if ($poh['budgettype'] == '' || $poh['budgettype'] == '""')
               $poh['budgettype'] = 'Project';

	       	$this->view->trano = $trano;
            $this->view->prjKode = $poh['prj_kode'];
	       	$this->view->tgl = $poh['tgl'];
	       	$this->view->revisi = $poh['revisi'];
	       	$this->view->prjKode = $poh['prj_kode'];
	       	$this->view->ppn = $poh['ppn'];
            $this->view->statusppn = $poh['statusppn'];
	        $this->view->sup_kode = $poh['sup_kode'];
	       	$this->view->sup_nama = $poh['sup_nama'];
	       	$this->view->val_kode = $poh['val_kode'];

	       	$this->view->rateidr = $poh['rateidr'];
	       	$this->view->type_po = $poh['typepo2'];
            $this->view->paymentterm = trim($poh['paymentterm']);
            $poh['deliverytext'] = preg_replace("[^A-Za-z0-9-.,]","",$poh['deliverytext']);
	       	$this->view->tujuan =preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['deliverytext']);
	       	$this->view->tgl_kirim = trim($poh['tgldeliesti']);
            $poh['ket'] = preg_replace("[^A-Za-z0-9-.,]","",$poh['ket']);
            $this->view->ket =preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['ket']);
            $this->view->ketin =trim($poh['ketin']);

//            $this->view->ket = trim($poh['ket']);


	       	$this->view->oripo = trim($poh['budgettype']);
	       	$this->view->invoiceto = trim($poh['invoiceto']);
            Zend_Loader::loadClass('Zend_Json');
   			$file = Zend_Json::encode($file);
	       	$this->view->file = $file;
   }

   public function editrpiAction()
   {
   		$trano = $this->getRequest()->getParam("trano");

//        $finance = new Finance_Models_Payment();

   		$prd = $this->rpi->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$prh = $this->rpiH->fetchRow("trano = '$trano'");

//   		if ($prh['revisi'] == '' || $prh['revisi'] == '""')
//   		{
//   			$prh['revisi'] = 1;
//   		}
//   		else
//	   		$prh['revisi'] = abs($prh['revisi']) + 1;
   		if ($prd)
   		{
   			foreach($prd as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {
                  
                    if ($val2 == "\"\"")
                        $prd[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $prd[$key][$key2] = str_replace("\""," inch",$prd[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $prd[$key][$key2] = str_replace("'"," inch",$prd[$key][$key2]);
                }

   				$prd[$key]['id'] = $key + 1;
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

                $pod = $this->quantity->getPoSummary($prd[$key]['po_no'],$prd[$key]['prj_kode'],$prd[$key]['sit_kode'],$prd[$key]['workid'],$prd[$key]['kode_brg'],$prd[$key]['pr_no']);
                if ($pod != '')
                {
                    $prd[$key]['qtyPO'] = $pod['qty'];
                    $prd[$key]['pricePO'] = $pod['harga'];
                    $prd[$key]['totalPricePO'] = $pod['qty']*$pod['harga'];
                }
                else
                {
                    $prd[$key]['qtyPO'] = 0;
                    $prd[$key]['pricePO'] = 0;
                    $prd[$key]['totalPricePO'] = 0; 
                }

                 $rpid = $this->quantity->getPoRPIQuantity($prd[$key]['po_no'],$prd[$key]['prj_kode'],$prd[$key]['sit_kode'],$prd[$key]['workid'],$prd[$key]['kode_brg'],$prd[$key]['pr_no']);
                if ($rpid != '')
                {
                        $prd[$key]['totalRPI'] = $rpid['qty'];
                        if ($prd[$key]['val_kode'] == 'IDR')
                                $prd[$key]['totalPriceRPI'] = $rpid['totalIDR'];
                        else
                                $prd[$key]['totalPriceRPI'] = $rpid['totalUSD'];

                }
                else
                {
                        $prd[$key]['totalRPI'] = 0;
                        $prd[$key]['totalPriceRPI'] = 0;
                }
   			}
               
   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($prd);
	   		$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	   			$this->view->json = $jsonData;
               
            $radio = Zend_Json::decode($prh['document_valid']);

            if ($radio != '' && count($radio) > 0)
            {
//                $etc[0] = $radio;
                $etc[0]['invoice_radio'] = $radio['invoice-radio'];
                $etc[0]['vat_radio'] = $radio['vat-radio'];
                $etc[0]['do_radio'] = $radio['do-radio'];
                $etc[0]['sign_radio'] = $radio['sign-radio'];
            }
            else
            {
                $etc[0]['invoice_radio'] = 1;
                $etc[0]['vat_radio'] = 1;
                $etc[0]['do_radio'] = 1;
                $etc[0]['sign_radio'] = 1;
            }
            $etc[0]['sup_invoice'] = $prh['invoice_no'];
            $etc[0]['ketin'] = $prh['ketin'];
            $etc[0]['rpi_ket'] = $prh['ket'];
            $this->view->etc = Zend_Json::encode($etc);
	       	$this->view->trano = $trano;
	       	$this->view->po_no = $prh['po_no'];
	       	$this->view->tgl = $prh['tgl'];
	       	$this->view->prj_nama = $prh['prj_nama'];
	       	$this->view->sit_nama = $prh['sit_nama'];
   		}
   }

    public function editrpibudgetAction()
   {
   		$trano = $this->getRequest()->getParam("trano");

//        $finance = new Finance_Models_Payment();

   		$prd = $this->rpi->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$prh = $this->rpiH->fetchRow("trano = '$trano'");

//   		if ($prh['revisi'] == '' || $prh['revisi'] == '""')
//   		{
//   			$prh['revisi'] = 1;
//   		}
//   		else
//	   		$prh['revisi'] = abs($prh['revisi']) + 1;
   		if ($prd)
   		{
   			foreach($prd as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {

                    if ($val2 == "\"\"")
                        $prd[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $prd[$key][$key2] = str_replace("\""," inch",$prd[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $prd[$key][$key2] = str_replace("'"," inch",$prd[$key][$key2]);
                }

   				$prd[$key]['id'] = $key + 1;
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

                $pod = $this->quantity->getPoSummary($prd[$key]['po_no'],$prd[$key]['prj_kode'],$prd[$key]['sit_kode'],$prd[$key]['workid'],$prd[$key]['kode_brg'],$prd[$key]['pr_no']);
                if ($pod != '')
                {
                    $prd[$key]['qtyPO'] = $pod['qty'];
                    $prd[$key]['pricePO'] = $pod['harga'];
                    $prd[$key]['totalPricePO'] = $pod['qty']*$pod['harga'];
                }
                else
                {
                    $prd[$key]['qtyPO'] = 0;
                    $prd[$key]['pricePO'] = 0;
                    $prd[$key]['totalPricePO'] = 0;
                }

                 $rpid = $this->quantity->getPoRPIQuantity($prd[$key]['po_no'],$prd[$key]['prj_kode'],$prd[$key]['sit_kode'],$prd[$key]['workid'],$prd[$key]['kode_brg'],$prd[$key]['pr_no']);
                if ($rpid != '')
                {
                        $prd[$key]['totalRPI'] = $rpid['qty'];
                        if ($prd[$key]['val_kode'] == 'IDR')
                                $prd[$key]['totalPriceRPI'] = $rpid['totalIDR'];
                        else
                                $prd[$key]['totalPriceRPI'] = $rpid['totalUSD'];

                }
                else
                {
                        $prd[$key]['totalRPI'] = 0;
                        $prd[$key]['totalPriceRPI'] = 0;
                }
   			}

   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($prd);
	   		$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	   			$this->view->json = $jsonData;

            $radio = Zend_Json::decode($prh['document_valid']);
            if ($radio != '' && count($radio) > 0)
            {
//                $etc[0] = $radio;
                $etc[0]['invoice_radio'] = $radio['invoice-radio'];
                $etc[0]['vat_radio'] = $radio['vat-radio'];
                $etc[0]['do_radio'] = $radio['do-radio'];
                $etc[0]['sign_radio'] = $radio['sign-radio'];
            }
            else
            {
                $etc[0]['invoice_radio'] = 1;
                $etc[0]['var_radio'] = 1;
                $etc[0]['do_radio'] = 1;
                $etc[0]['sign_radio'] = 1;
            }
            $etc[0]['sup_invoice'] = $prh['invoice_no'];
            $etc[0]['ketin'] = $prh['ketin'];
            $etc[0]['rpi_ket'] = $prh['ket'];
            $this->view->etc = Zend_Json::encode($etc);
	       	$this->view->trano = $trano;
	       	$this->view->po_no = $prh['po_no'];
	       	$this->view->tgl = $prh['tgl'];
	       	$this->view->prj_nama = $prh['prj_nama'];
	       	$this->view->sit_nama = $prh['sit_nama'];
   		}
   }

    public function editrpisalesAction()
   {
   		$trano = $this->getRequest()->getParam("trano");

//        $finance = new Finance_Models_Payment();

   		$prd = $this->rpi->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$prh = $this->rpiH->fetchRow("trano = '$trano'");

//   		if ($prh['revisi'] == '' || $prh['revisi'] == '""')
//   		{
//   			$prh['revisi'] = 1;
//   		}
//   		else
//	   		$prh['revisi'] = abs($prh['revisi']) + 1;
   		if ($prd)
   		{
   			foreach($prd as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {

                    if ($val2 == "\"\"")
                        $prd[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $prd[$key][$key2] = str_replace("\""," inch",$prd[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $prd[$key][$key2] = str_replace("'"," inch",$prd[$key][$key2]);
                }

   				$prd[$key]['id'] = $key + 1;
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

                $pod = $this->quantity->getPoSummary($prd[$key]['po_no'],$prd[$key]['prj_kode'],$prd[$key]['sit_kode'],$prd[$key]['workid'],$prd[$key]['kode_brg'],$prd[$key]['pr_no']);
                if ($pod != '')
                {
                    $prd[$key]['qtyPO'] = $pod['qty'];
                    $prd[$key]['pricePO'] = $pod['harga'];
                    $prd[$key]['totalPricePO'] = $pod['qty']*$pod['harga'];
                }
                else
                {
                    $prd[$key]['qtyPO'] = 0;
                    $prd[$key]['pricePO'] = 0;
                    $prd[$key]['totalPricePO'] = 0;
                }

                 $rpid = $this->quantity->getPoRPIQuantity($prd[$key]['po_no'],$prd[$key]['prj_kode'],$prd[$key]['sit_kode'],$prd[$key]['workid'],$prd[$key]['kode_brg'],$prd[$key]['pr_no']);
                if ($rpid != '')
                {
                        $prd[$key]['totalRPI'] = $rpid['qty'];
                        if ($prd[$key]['val_kode'] == 'IDR')
                                $prd[$key]['totalPriceRPI'] = $rpid['totalIDR'];
                        else
                                $prd[$key]['totalPriceRPI'] = $rpid['totalUSD'];

                }
                else
                {
                        $prd[$key]['totalRPI'] = 0;
                        $prd[$key]['totalPriceRPI'] = 0;
                }
   			}

   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($prd);
	   		$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	   			$this->view->json = $jsonData;

            $radio = Zend_Json::decode($prh['document_valid']);
            if ($radio != '' && count($radio) > 0)
            {
//                $etc[0] = $radio;
                $etc[0]['invoice_radio'] = $radio['invoice-radio'];
                $etc[0]['vat_radio'] = $radio['vat-radio'];
                $etc[0]['do_radio'] = $radio['do-radio'];
                $etc[0]['sign_radio'] = $radio['sign-radio'];
            }
            else
            {
                $etc[0]['invoice_radio'] = 1;
                $etc[0]['var_radio'] = 1;
                $etc[0]['do_radio'] = 1;
                $etc[0]['sign_radio'] = 1;
            }
            $etc[0]['sup_invoice'] = $prh['invoice_no'];
            $etc[0]['ketin'] = $prh['ketin'];
            $etc[0]['rpi_ket'] = $prh['ket'];
            $this->view->etc = Zend_Json::encode($etc);
	       	$this->view->trano = $trano;
	       	$this->view->po_no = $prh['po_no'];
	       	$this->view->tgl = $prh['tgl'];
	       	$this->view->prj_nama = $prh['prj_nama'];
	       	$this->view->sit_nama = $prh['sit_nama'];
   		}
   }

    public function editasfAction()
   {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
   	    $trano = $this->getRequest()->getParam("trano");
   		$asfdd = $this->asf->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $asfddcancel = $this->asfc->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $asfd = $this->asfD->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$asfh = $this->asfH->fetchRow("trano = '$trano'")->toArray();

        $sql = "SELECT a.* FROM procurement_arfd a LEFT JOIN procurement_asfd b ON a.trano = b.arf_no WHERE b.trano = '$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();

        if($return)
        {
            foreach($return as $key => $val)
            {
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $return[$key][$key2] = str_replace("\""," inch",$return[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $return[$key][$key2] = str_replace("'"," inch",$return[$key][$key2]);
                }
                
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang)
                {
                    $return[$key]['uom'] = $barang['sat_kode'];
                }

                $asf = $this->quantity->getArfAsfQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
                if ($asf != '' )

                    {
                        $asfqty = $asf['qty'];
                        if ($return[$key]['val_kode'] == 'IDR')
                                $asftotal = $asf['totalIDR'];
                        else
                                $asftotal = $asf['totalUSD'];

                    }
                    else
                    {
                        $asfqty = 0;
                        $asftotal = 0;
                    }

                $asfcancel = $this->quantity->getArfAsfcancelQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
                    if ($asfcancel != '' )

                    {

                        $asfcancelqty = $asfcancel['qty'];
                        if ($return[$key]['val_kode'] == 'IDR')
                                $asfcanceltotal = $asfcancel['totalIDR'];
                        else
                                $asfcanceltotal = $asfcancel['totalUSD'];

                    }
                    else
                    {

                        $asfcancelqty = 0;
                        $asfcanceltotal = 0;
                    }

                $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);

                    if ($arfh != '')
                        $inarfhtotal = $arfh['total'];
                    else
                        $inarfhtotal = 0;


                 $return[$key]['id'] = $key + 1;
                    foreach ($return[$key] as $key2 => $val2)
                    {
                        if ($val2 == "\"\"")
                            $return[$key][$key2] = '';
                    }
                $return[$key]['price'] = $return[$key]['harga'];
                $return[$key]['totalPrice'] = $return[$key]['total'];
                unset($return[$key]['harga']);
                unset($return[$key]['total']);
                $return[$key]['totalASF'] = $asfqty;
                $return[$key]['totalPriceASF'] = $asftotal;
                $return[$key]['totalASFCancel'] = $asfcancelqty;
                $return[$key]['totalPriceASFCancel'] = $asfcanceltotal;
                $return[$key]['totalPriceInArfh'] = $inarfhtotal;

            }
        }
        else
          $asfddcancel = Array();


        foreach($asfh as $key => $val)
        {
          if ($val == "\"\"")
              $asfh[$key] = '';
        }

        if ($asfdd)
   		{
   			foreach($asfdd as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $asfdd[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $asfdd[$key][$key2] = str_replace("\""," inch",$asfdd[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $asfdd[$key][$key2] = str_replace("'"," inch",$asfdd[$key][$key2]);
                }

   				$asfdd[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$asfdd[$key]['uom'] = $barang['sat_kode'];
   				}

   			}


        }
        else
           $asfdd = Array();

        if ($asfddcancel)
   		{
   			foreach($asfddcancel as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $asfddcancel[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $asfddcancel[$key][$key2] = str_replace("\""," inch",$asfddcancel[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $asfddcancel[$key][$key2] = str_replace("'"," inch",$asfddcancel[$key][$key2]);
                }

   				$asfddcancel[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$asfddcancel[$key]['uom'] = $barang['sat_kode'];
   				}

   			}
        }
        else
           $asfddcancel = Array();

            Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($asfdd);
            $jsonData2 = Zend_Json::encode($asfddcancel);
            $arf = Zend_Json::encode($return);
   			$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
                $this->view->json2 = $this->getRequest()->getParam("posts2");
	   		}
	   		else
	       		$this->view->json = $jsonData;
                $this->view->json2 = $jsonData2;
                $this->view->arf = $arf;

	       	$this->view->trano = $trano;
	       	$this->view->tgl = $asfh['tgl'];

	       	$this->view->ket = trim($asfh['ket']);
	       	$this->view->requester = trim($asfh['petugas']);
            $this->view->requester2 = trim($asfh['request2']);
	       	$this->view->pic = trim($asfh['orangpic']);
	       	$this->view->finance = trim($asfh['orangfinance']);
       	    $this->view->val_kode = $asfh['val_kode'];
            $this->view->rateidr = $asfh['rateidr'];

   }

   public function editasfbudgetAction()
   {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
   	    $trano = $this->getRequest()->getParam("trano");
   		$asfdd = $this->asf->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $asfddcancel = $this->asfc->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $asfd = $this->asfD->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$asfh = $this->asfH->fetchRow("trano = '$trano'")->toArray();

        $sql = "SELECT a.* FROM procurement_arfd a LEFT JOIN procurement_asfd b ON a.trano = b.arf_no WHERE b.trano = '$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();

        if($return)
        {
            foreach($return as $key => $val)
            {
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $return[$key][$key2] = str_replace("\""," inch",$return[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $return[$key][$key2] = str_replace("'"," inch",$return[$key][$key2]);
                }

                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang)
                {
                    $return[$key]['uom'] = $barang['sat_kode'];
                }

                $asf = $this->quantity->getArfAsfQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
                if ($asf != '' )

                    {
                        $asfqty = $asf['qty'];
                        if ($return[$key]['val_kode'] == 'IDR')
                                $asftotal = $asf['totalIDR'];
                        else
                                $asftotal = $asf['totalUSD'];

                    }
                    else
                    {
                        $asfqty = 0;
                        $asftotal = 0;
                    }

                $asfcancel = $this->quantity->getArfAsfcancelQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
                    if ($asfcancel != '' )

                    {

                        $asfcancelqty = $asfcancel['qty'];
                        if ($return[$key]['val_kode'] == 'IDR')
                                $asfcanceltotal = $asfcancel['totalIDR'];
                        else
                                $asfcanceltotal = $asfcancel['totalUSD'];

                    }
                    else
                    {

                        $asfcancelqty = 0;
                        $asfcanceltotal = 0;
                    }

                $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);

                    if ($arfh != '')
                        $inarfhtotal = $arfh['total'];
                    else
                        $inarfhtotal = 0;


                 $return[$key]['id'] = $key + 1;
                    foreach ($return[$key] as $key2 => $val2)
                    {
                        if ($val2 == "\"\"")
                            $return[$key][$key2] = '';
                    }
                $return[$key]['price'] = $return[$key]['harga'];
                $return[$key]['totalPrice'] = $return[$key]['total'];
                unset($return[$key]['harga']);
                unset($return[$key]['total']);
                $return[$key]['totalASF'] = $asfqty;
                $return[$key]['totalPriceASF'] = $asftotal;
                $return[$key]['totalASFCancel'] = $asfcancelqty;
                $return[$key]['totalPriceASFCancel'] = $asfcanceltotal;
                $return[$key]['totalPriceInArfh'] = $inarfhtotal;

            }
        }
        else
          $asfddcancel = Array();


        foreach($asfh as $key => $val)
        {
          if ($val == "\"\"")
              $asfh[$key] = '';
        }

        if ($asfdd)
   		{
   			foreach($asfdd as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $asfdd[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $asfdd[$key][$key2] = str_replace("\""," inch",$asfdd[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $asfdd[$key][$key2] = str_replace("'"," inch",$asfdd[$key][$key2]);
                }

   				$asfdd[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$asfdd[$key]['uom'] = $barang['sat_kode'];
   				}

   			}


        }
        else
           $asfdd = Array();

        if ($asfddcancel)
   		{
   			foreach($asfddcancel as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $asfddcancel[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $asfddcancel[$key][$key2] = str_replace("\""," inch",$asfddcancel[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $asfddcancel[$key][$key2] = str_replace("'"," inch",$asfddcancel[$key][$key2]);
                }

   				$asfddcancel[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$asfddcancel[$key]['uom'] = $barang['sat_kode'];
   				}

   			}
        }
        else
           $asfddcancel = Array();

            Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($asfdd);
            $jsonData2 = Zend_Json::encode($asfddcancel);
            $arf = Zend_Json::encode($return);
   			$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
                $this->view->json2 = $this->getRequest()->getParam("posts2");
	   		}
	   		else
	       		$this->view->json = $jsonData;
                $this->view->json2 = $jsonData2;
                $this->view->arf = $arf;

	       	$this->view->trano = $trano;
	       	$this->view->tgl = $asfh['tgl'];

	       	$this->view->ket = trim($asfh['ket']);
	       	$this->view->requester = trim($asfh['petugas']);
            $this->view->requester2 = trim($asfh['request2']);
	       	$this->view->pic = trim($asfh['orangpic']);
	       	$this->view->finance = trim($asfh['orangfinance']);
       	    $this->view->val_kode = $asfh['val_kode'];
            $this->view->rateidr = $asfh['rateidr'];
   }

   public function editasfsalesAction()
   {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
   	    $trano = $this->getRequest()->getParam("trano");
   		$asfdd = $this->asf->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $asfddcancel = $this->asfc->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $asfd = $this->asfD->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$asfh = $this->asfH->fetchRow("trano = '$trano'")->toArray();

        $sql = "SELECT a.* FROM procurement_arfd a LEFT JOIN procurement_asfd b ON a.trano = b.arf_no WHERE b.trano = '$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();

        if($return)
        {
            foreach($return as $key => $val)
            {
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $return[$key][$key2] = str_replace("\""," inch",$return[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $return[$key][$key2] = str_replace("'"," inch",$return[$key][$key2]);
                }

                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang)
                {
                    $return[$key]['uom'] = $barang['sat_kode'];
                }

                $asf = $this->quantity->getArfAsfQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
                if ($asf != '' )

                    {
                        $asfqty = $asf['qty'];
                        if ($return[$key]['val_kode'] == 'IDR')
                                $asftotal = $asf['totalIDR'];
                        else
                                $asftotal = $asf['totalUSD'];

                    }
                    else
                    {
                        $asfqty = 0;
                        $asftotal = 0;
                    }

                $asfcancel = $this->quantity->getArfAsfcancelQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
                    if ($asfcancel != '' )

                    {

                        $asfcancelqty = $asfcancel['qty'];
                        if ($return[$key]['val_kode'] == 'IDR')
                                $asfcanceltotal = $asfcancel['totalIDR'];
                        else
                                $asfcanceltotal = $asfcancel['totalUSD'];

                    }
                    else
                    {

                        $asfcancelqty = 0;
                        $asfcanceltotal = 0;
                    }

                $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);

                    if ($arfh != '')
                        $inarfhtotal = $arfh['total'];
                    else
                        $inarfhtotal = 0;


                 $return[$key]['id'] = $key + 1;
                    foreach ($return[$key] as $key2 => $val2)
                    {
                        if ($val2 == "\"\"")
                            $return[$key][$key2] = '';
                    }
                $return[$key]['price'] = $return[$key]['harga'];
                $return[$key]['totalPrice'] = $return[$key]['total'];
                unset($return[$key]['harga']);
                unset($return[$key]['total']);
                $return[$key]['totalASF'] = $asfqty;
                $return[$key]['totalPriceASF'] = $asftotal;
                $return[$key]['totalASFCancel'] = $asfcancelqty;
                $return[$key]['totalPriceASFCancel'] = $asfcanceltotal;
                $return[$key]['totalPriceInArfh'] = $inarfhtotal;

            }
        }
        else
          $asfddcancel = Array();


        foreach($asfh as $key => $val)
        {
          if ($val == "\"\"")
              $asfh[$key] = '';
        }

        if ($asfdd)
   		{
   			foreach($asfdd as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $asfdd[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $asfdd[$key][$key2] = str_replace("\""," inch",$asfdd[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $asfdd[$key][$key2] = str_replace("'"," inch",$asfdd[$key][$key2]);
                }

   				$asfdd[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$asfdd[$key]['uom'] = $barang['sat_kode'];
   				}

   			}


        }
        else
           $asfdd = Array();

        if ($asfddcancel)
   		{
   			foreach($asfddcancel as $key => $val)
   			{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $asfddcancel[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $asfddcancel[$key][$key2] = str_replace("\""," inch",$asfddcancel[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $asfddcancel[$key][$key2] = str_replace("'"," inch",$asfddcancel[$key][$key2]);
                }

   				$asfddcancel[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$asfddcancel[$key]['uom'] = $barang['sat_kode'];
   				}

   			}
        }
        else
           $asfddcancel = Array();

            Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($asfdd);
            $jsonData2 = Zend_Json::encode($asfddcancel);
            $arf = Zend_Json::encode($return);
   			$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
                $this->view->json2 = $this->getRequest()->getParam("posts2");
	   		}
	   		else
	       		$this->view->json = $jsonData;
                $this->view->json2 = $jsonData2;
                $this->view->arf = $arf;

	       	$this->view->trano = $trano;
	       	$this->view->tgl = $asfh['tgl'];

	       	$this->view->ket = trim($asfh['ket']);
	       	$this->view->requester = trim($asfh['petugas']);
            $this->view->requester2 = trim($asfh['request2']);
	       	$this->view->pic = trim($asfh['orangpic']);
	       	$this->view->finance = trim($asfh['orangfinance']);
       	    $this->view->val_kode = $asfh['val_kode'];
            $this->view->rateidr = $asfh['rateidr'];
   }

   public function getlastprAction()
   {
   		$this->_helper->viewRenderer->setNoRender();
   		$number = $this->util->getLastNumber('PR');
   		$number++;
   		echo "{ pr:'$number' }";
   }
    public function getlastarfAction()
    {
            $this->_helper->viewRenderer->setNoRender();
            $number = $this->util->getLastNumber('ARF');
            $number++;
            echo "{ ARF:'$number' }";
    }

	public function getlastpoAction()
   {
   		$this->_helper->viewRenderer->setNoRender();
   		$number = $this->util->getLastNumber('PO');
   		$number++;
   		echo "{ po:'$number' }";
   }
   
   public function appprAction()
   {	
   		$type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
   		
   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/PR';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';
   		
   		$approve = $this->getRequest()->getParam("approve");
   		$preview = $this->getRequest()->getParam("preview");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            foreach($jsonData as $key => $val)
            {
                  foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $jsonData[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $jsonData[$key][$key2] = str_replace("\""," inch",$jsonData[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $jsonData[$key][$key2] = str_replace("'"," inch",$jsonData[$key][$key2]);
                }
            }
	       	
	        $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
	        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];      
	        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];        
	       	$this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = Zend_Json::encode($jsonData);
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
	   				$workflowId = $docs['workflow_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
		   			$prd = $this->procurement->fetchAll("trano = '$approve'")->toArray();
		   			$prh = $this->procurementH->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");
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

                            $prd[$key]['net_act'] = $val['myob'];
		   				}

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;   
		   				$jsonData2[0]['prj_kode'] = $prh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $prh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $prh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $prh['sit_nama'];
		   				$cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
				        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];      
                        $jsonData2[0]['budgettype'] = $prh['budgettype'];      
				        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
				        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $prh['petugas'];
		                $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;   
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $prd;
                        $this->view->trano = $approve;
		   				$this->view->approve = true;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
                        $this->view->preview = $preview;
                        $this->view->file = $file;
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

    public function appprbudgetAction()
   {
   		$type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
        $sales = $this->getRequest()->getParam("sales");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/PRO';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		$preview = $this->getRequest()->getParam("preview");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            foreach($jsonData as $key => $val)
            {
                  foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $jsonData[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $jsonData[$key][$key2] = str_replace("\""," inch",$jsonData[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $jsonData[$key][$key2] = str_replace("'"," inch",$jsonData[$key][$key2]);
                }
            }

//	        $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
//	        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//	        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
	       	$this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = Zend_Json::encode($jsonData);
            $this->view->jsonFile = $files;
            $this->view->file = $file;

	       	if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}

            if ($sales == 'true')
            {
                $this->view->sales = true;
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
	   				$workflowId = $docs['workflow_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
                    $potong = substr($approve,0,3);
                    if ($potong == 'PRF')
                        $this->view->sales = true;

		   			$prd = $this->procurement->fetchAll("trano = '$approve'")->toArray();
		   			$prh = $this->procurementH->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");
              
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

		   						$prd[$key]['totalPrice'] = $val['jumlah'];
                                $prd[$key]['budgetid'] = $val['workid'];
                                $prd[$key]['budgetname'] = $val['workname'];

		   				}

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
		   				$jsonData2[0]['prj_kode'] = $prh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $prh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $prh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $prh['sit_nama'];
//		   				$cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
//				        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['budgettype'] = $prh['budgettype'];
//				        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
				        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $prh['petugas'];
		                $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $prd;
                        $this->view->trano = $approve;
		   				$this->view->approve = true;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
                        $this->view->preview = $preview;
                        $this->view->file = $file;
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

	public function apprpiAction()
   {
   		$type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/RPI';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		$preview = $this->getRequest()->getParam("preview");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);
	       	$trano = $jsonData2[0]['po_no'];
            $rpino = $jsonData2[0]['trano'];
            $quantity = $this->_helper->getHelper('quantity');

            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
                 $po = $quantity->getDetailPoRPIQuantity($rpino,$trano);
                $jsonData2[0]['po_invoice'] = 0;
                if ($po != '')
                {
                $tmp = array();
                foreach($po as $key => $val )
                {
                    $tmp[$key]['total'] = number_format($val['total'],2);
                    $tmp[$key]['val_kode'] = $val['val_kode'];
                    $jsonData2[0]['po_invoice'] += $val['total'];
                }
                    $jsonData2[0]['po_invoice_detail'] = $tmp;
                }
	       	}
            else
            {
            $po = $quantity->getDetailPoRPIQuantity('',$trano);
            $jsonData2[0]['po_invoice'] = 0;
            if ($po != '')
            {
                $tmp = array();
                foreach($po as $key => $val )
                {
                    $tmp[$key]['total'] = number_format($val['total'],2);
                    $tmp[$key]['val_kode'] = $val['val_kode'];
                    $jsonData2[0]['po_invoice'] += $val['total'];
                }
                $jsonData2[0]['po_invoice_detail'] = $tmp;
            }
            }
	        $po = $this->purchase->fetchRow("trano='$trano'");
	        $poH = $this->purchaseH->fetchRow("trano='$trano'");
	        $cusKode = $this->project->getProjectAndCustomer($po['prj_kode']);
	        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
	        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
	        $jsonData2[0]['prj_nama'] = $po['prj_nama'];
	        $jsonData2[0]['prj_kode'] = $po['prj_kode'];
	        $jsonData2[0]['sit_nama'] = $po['sit_nama'];
	        $jsonData2[0]['sit_kode'] = $po['sit_kode'];
	        $jsonData2[0]['sup_nama'] = $poH['sup_nama'];
	        $jsonData2[0]['sup_kode'] = $poH['sup_kode'];
	        $jsonData2[0]['val_kode'] = $poH['val_kode'];
	        $jsonData2[0]['totalPO'] = $poH['jumlahspl'];
	        $jsonData2[0]['po_no'] = $poH['trano'];
            $pono = substr($poH['trano'],0,4);
            switch($pono)
            {
                case 'PO01':
                    $this->view->poType = 'PO';
                    break;
                case 'PO02':
                    $this->view->poType = 'POO';
                    break;
            }
	        if ($jsonData2[0]['ppn'] > 0)
                $this->view->isPPn = true;

	       	$this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = $json;
	       	$this->view->jsonEtc = Zend_Json::encode($jsonData2);



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
		   			$prd = $this->rpi->fetchAll("trano = '$approve'")->toArray();
		   			$prh = $this->rpiH->fetchRow("trano = '$approve'")->toArray();
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

                        $po_no = $prh['po_no'];
		                $quantity = $this->_helper->getHelper('quantity');
                        $po = $quantity->getDetailPoRPIQuantity($approve,$po_no);
                        $jsonData2[0]['po_invoice'] = 0;
                        $radio = Zend_Json::decode($prh['document_valid']);
                        $jsonData2[0] = $radio;
    	                if ($po != '')
                        {
                            $tmp = array();
                            foreach($po as $key => $val )
                            {
                                $tmp[$key]['total'] = number_format($val['total'],2);
                                $tmp[$key]['val_kode'] = $val['val_kode'];
                                $jsonData2[0]['po_invoice'] += $val['total'];
                            }
                            $jsonData2[0]['po_invoice_detail'] = $tmp;
                        }
                        $totalPO = 0; 
                        $poH = $this->purchaseH->fetchRow("trano='$po_no'");
                        if ($poH)
                        {
                            $poH = $poH->toArray();
                            if ($poH['ppn'] > 0)
                            {
                                $this->view->isPPn = true;
                            }
                            else
                                $this->view->isPPn = false;
                            $totalPO = $poH['jumlah'];
                            $valKode = $poH['val_kode'];
                        }
                        $userApp = $this->workflow->getAllApproval($approve);
                        
                        $radios = Zend_Json::decode($prh['document_valid']);
                        $jsonData2[0]['invoice_radio'] = $radios['invoice-radio'];
                        $jsonData2[0]['vat_radio'] = $radios['vat-radio'];
                        $jsonData2[0]['do_radio'] = $radios['do-radio'];
                        $jsonData2[0]['sign_radio'] = $radios['sign-radio'];

                        $jsonData2[0]['user_approval'] = $userApp;
		   				$jsonData2[0]['prj_kode'] = $prh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $prh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $prh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $prh['sit_nama'];
		   				$jsonData2[0]['sup_kode'] = $prh['sup_kode'];
		   				$jsonData2[0]['sup_nama'] = $prh['sup_nama'];
                        $jsonData2[0]['sup_invoice'] = $prh['invoice_no'];
		   				$jsonData2[0]['val_kode'] = $valKode;
                        $jsonData2[0]['totalPO'] = $totalPO;
                        $jsonData2[0]['balance'] = $totalPO - $jsonData2[0]['po_invoice'];
                        $jsonData2[0]['po_no'] = $prh['po_no'];
                        $jsonData2[0]['ppn'] = $poH['ppn'];
                        if ($jsonData2[0]['ppn'] > 0)
                            $this->view->isPPn = true;   
                        $jsonData2[0]['rpi_ket'] = $prh['ket'];
                        $jsonData2[0]['ketin'] = $prh['ketin'];
		   				$cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
				        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
				        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
				        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $prh['petugas'];

                        $pono = substr($prh['po_no'],0,4);
                        switch($pono)
                        {
                            case 'PO01':
                                $this->view->poType = 'PO';
                                break;
                            case 'PO02':
                                $this->view->poType = 'POO';
                                break;
                        }

                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->createdDate = date('d-m-Y',strtotime($prh['tgl']));
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
				        $this->view->etc = $jsonData2;
                        $this->view->trano = $approve;
		   				$this->view->result = $prd;
		   				$this->view->approve = true;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
                        $this->view->preview = $preview;
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

    public function apprpibudgetAction()
   {
   		$type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $sales = $this->getRequest()->getParam("sales");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/RPIO';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		$preview = $this->getRequest()->getParam("preview");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);
	       	$trano = $jsonData2[0]['po_no'];
            $rpino = $jsonData2[0]['trano'];
            $quantity = $this->_helper->getHelper('quantity');

            if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
                 $po = $quantity->getDetailPoRPIQuantity($rpino,$trano);
                $jsonData2[0]['po_invoice'] = 0;
                if ($po != '')
                {
                $tmp = array();
                foreach($po as $key => $val )
                {
                    $tmp[$key]['total'] = number_format($val['total'],2);
                    $tmp[$key]['val_kode'] = $val['val_kode'];
                    $jsonData2[0]['po_invoice'] += $val['total'];
                }
                    $jsonData2[0]['po_invoice_detail'] = $tmp;
                }
	       	}
            else
            {
            $po = $quantity->getDetailPoRPIQuantity('',$trano);
            $jsonData2[0]['po_invoice'] = 0;
            if ($po != '')
            {
                $tmp = array();
                foreach($po as $key => $val )
                {
                    $tmp[$key]['total'] = number_format($val['total'],2);
                    $tmp[$key]['val_kode'] = $val['val_kode'];
                    $jsonData2[0]['po_invoice'] += $val['total'];
                }
                $jsonData2[0]['po_invoice_detail'] = $tmp;
            }
            }

            if ($sales == 'true')
            {
                $this->view->sales = true;
            }
	        $po = $this->purchase->fetchRow("trano='$trano'");
	        $poH = $this->purchaseH->fetchRow("trano='$trano'");
//	        $cusKode = $this->project->getProjectAndCustomer($po['prj_kode']);
//
//	        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//	        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
	        $jsonData2[0]['prj_nama'] = $poH['prj_nama'];
	        $jsonData2[0]['prj_kode'] = $poH['prj_kode'];
	        $jsonData2[0]['sit_nama'] = $poH['sit_nama'];
	        $jsonData2[0]['sit_kode'] = $poH['sit_kode'];
	        $jsonData2[0]['sup_nama'] = $poH['sup_nama'];
	        $jsonData2[0]['sup_kode'] = $poH['sup_kode'];
	        $jsonData2[0]['val_kode'] = $poH['val_kode'];
	        $jsonData2[0]['totalPO'] = $poH['jumlah'];
	        $jsonData2[0]['po_no'] = $poH['trano'];
	        if ($jsonData2[0]['ppn'] > 0)
                $this->view->isPPn = true;

	       	$this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = $json;
	       	$this->view->jsonEtc = Zend_Json::encode($jsonData2);



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
                    $potong = substr($approve,0,5);
                    if ($potong == 'RPI01')
                        $this->view->sales = true;

		   			$prd = $this->rpi->fetchAll("trano = '$approve'")->toArray();
		   			$prh = $this->rpiH->fetchRow("trano = '$approve'")->toArray();
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

                        $po_no = $prh['po_no'];
		                $quantity = $this->_helper->getHelper('quantity');
                        $po = $quantity->getDetailPoRPIQuantity($approve,$po_no);
                        $jsonData2[0]['po_invoice'] = 0;
                        $radio = Zend_Json::decode($prh['document_valid']);
                        $jsonData2[0] = $radio;
    	                if ($po != '')
                        {
                            $tmp = array();
                            foreach($po as $key => $val )
                            {
                                $tmp[$key]['total'] = number_format($val['total'],2);
                                $tmp[$key]['val_kode'] = $val['val_kode'];
                                $jsonData2[0]['po_invoice'] += $val['total'];
                            }
                            $jsonData2[0]['po_invoice_detail'] = $tmp;
                        }
                        $totalPO = 0;
                        $poH = $this->purchaseH->fetchRow("trano='$po_no'");
                        if ($poH)
                        {
                            $poH = $poH->toArray();
                            if ($poH['ppn'] > 0)
                            {
                                $this->view->isPPn = true;
                            }
                            else
                                $this->view->isPPn = false;
                            $totalPO = $poH['jumlah'];
                            $valKode = $poH['val_kode'];
                        }
                        $userApp = $this->workflow->getAllApproval($approve);

                        $radios = Zend_Json::decode($prh['document_valid']);
                        $jsonData2[0]['invoice_radio'] = $radios['invoice-radio'];
                        $jsonData2[0]['vat_radio'] = $radios['vat-radio'];
                        $jsonData2[0]['do_radio'] = $radios['do-radio'];
                        $jsonData2[0]['sign_radio'] = $radios['sign-radio'];

                        $jsonData2[0]['user_approval'] = $userApp;
		   				$jsonData2[0]['prj_kode'] = $prh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $prh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $prh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $prh['sit_nama'];
		   				$jsonData2[0]['sup_kode'] = $prh['sup_kode'];
		   				$jsonData2[0]['sup_nama'] = $prh['sup_nama'];
		   				$jsonData2[0]['val_kode'] = $valKode;
                        $jsonData2[0]['totalPO'] = $totalPO;
                        $jsonData2[0]['balance'] = $totalPO - $jsonData2[0]['po_invoice'];
                        $jsonData2[0]['po_no'] = $prh['po_no'];
                        $jsonData2[0]['ppn'] = $poH['ppn'];
                        if ($jsonData2[0]['ppn'] > 0)
                            $this->view->isPPn = true;
                        $jsonData2[0]['rpi_ket'] = $prh['ket'];
                        $jsonData2[0]['ketin'] = $prh['ketin'];
		   				$cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
				        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
				        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
				        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $prh['petugas'];

                       $pono = substr($prh['po_no'],0,4);
                        switch($pono)
                        {
                            case 'PO01':
                                $this->view->poType = 'PO';
                                break;
                            case 'PO02':
                                $this->view->poType = 'POO';
                                break;
                        }

                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->createdDate = date('d-m-Y',strtotime($prh['tgl']));
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
				        $this->view->etc = $jsonData2;
                        $this->view->trano = $approve;
		   				$this->view->result = $prd;
		   				$this->view->approve = true;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
                        $this->view->preview = $preview;
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
   
    public function apppoAction()
   {
   		$type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
   		
   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/PO';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';
   	
   		$approve = $this->getRequest()->getParam("approve");
   		$preview = $this->getRequest()->getParam("preview");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
	   		$files = $this->getRequest()->getParam("file");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');

	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);
	   		$file = Zend_Json::decode($files);

            $sup = $this->trans->getAlamatSup($jsonData2[0]['sup_kode']);
            $alamat = $sup['alamat']. " " . $sup['alamat2'];
            $tlpsup = $sup['tlp'];
            $faxsup = $sup['fax'];
            $jsonData2[0]['alamat_sup'] = $alamat;
            $jsonData2[0]['tlp_sup'] = $tlpsup;
            $jsonData2[0]['fax_sup'] = $faxsup;
            $jsonData2[0]['ketin'] = trim($jsonData2[0]['ketin']);

            $temp = array();
            foreach($jsonData as $key => $val)
            {
                $prjKode = $val['prj_kode'];
                $jsonData[$key]['pr_no'] = $val['pr_number'];
                if ($jsonData[$key]['qtySupp'] == '')
                    $jsonData[$key]['qtySupp'] = $jsonData[$key]['qty'];
                if ($jsonData[$key]['priceSupp'] == '')
                    $jsonData[$key]['priceSupp'] = $jsonData[$key]['price'];
                if ($jsonData[$key]['totalPriceSupp'] == ''  || $jsonData[$key]['totalPriceSupp'] == 0)
                    $jsonData[$key]['totalPriceSupp'] = $jsonData[$key]['price'] * $jsonData[$key]['qty'];
                $temp[$prjKode][] = $jsonData[$key];
            }
            $jsonData = $temp;
//            var_dump($jsonData2[0]['alamat_sup']);die;
//	        $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
//	        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];      
//	        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];        
	       	$this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = $json;
	       	$this->view->jsonFile = $files;
	       	$this->view->file = $file;
   			if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}
   		}
   		else
   		{
            $this->view->approve = false;

            if (is_numeric($approve))
            {
   			    $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
                   if ($docs)
                   {
                      $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
                      if ($user || $show)
                      {
                          $id = $docs['workflow_trans_id'];
                          $approve = $docs['item_id'];

                          $this->view->approve = true;
                      }
                   }
            }    
            else
            {
                $pod = $this->purchase->fetchRow("trano = '$approve'");
                if ($pod)
                {
                    $docs = $this->workflow->getDocumentLastStatusAll($approve);
                    $this->view->approve = true;
                }
                else
                    $this->view->approve = false;
            }

           if ($this->view->approve)
           {
               $userApp = $this->workflow->getAllApproval($approve);
                $statApprove = $docs['approve'];
                if ($statApprove == $this->const['DOCUMENT_REJECT'])
                    $this->view->reject = true;
                $pod = $this->purchase->fetchAll("trano = '$approve'")->toArray();
                $poh = $this->purchaseH->fetchRow("trano = '$approve'");
                $file = $this->files->fetchAll("trano = '$approve'");
                if ($pod)
                {
                    foreach($pod as $key => $val)
                    {
                        $kodeBrg = $val['kode_brg'];
                        $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                        if ($barang)
                        {
                            $pod[$key]['uom'] = $barang['sat_kode'];
                        }

                            $pod[$key]['price'] = $val['harga'];
                            $pod[$key]['net_act'] = $val['myob'];
                            if ($val['qtyspl'] == '')
                                $pod[$key]['qtyspl'] = $val['qty'];
                            if ($val['hargaspl'] == '')
                                $pod[$key]['hargaspl'] = $val['harga'];

                            if ($val['qtyspl'] == '')
                                $pod[$key]['totalspl'] = $val['harga'] * $val['qty'];
                            $pod[$key]['qtySupp'] = $pod[$key]['qtyspl'];
                            $pod[$key]['priceSupp'] = $pod[$key]['hargaspl'];
                            $pod[$key]['totalPriceSupp'] = $pod[$key]['qtyspl'] * $pod[$key]['hargaspl'];
                    }

                    $sup = $this->trans->getAlamatSup($poh['sup_kode']);

                    $jsonData2[0]['user_approval'] = $userApp;
                    $alamat = $sup['alamat'] . " " . $sup['alamat2'];
                    $tlpsup = $sup['tlp'];
                    $faxsup = $sup['fax'];
                    $jsonData2[0]['alamat_sup'] = $alamat;
                    $jsonData2[0]['tlp_sup'] = $tlpsup;
                    $jsonData2[0]['fax_sup'] = $faxsup;

                    $jsonData2[0]['prj_kode'] = $poh['prj_kode'];
                    $jsonData2[0]['prj_nama'] = $poh['prj_nama'];
                    $jsonData2[0]['sit_kode'] = $poh['sit_kode'];
                    $jsonData2[0]['sit_nama'] = $poh['sit_nama'];
                    $jsonData2[0]['sup_kode'] = $poh['sup_kode'];
                    $jsonData2[0]['sup_nama'] = $poh['sup_nama'];

                    $jsonData2[0]['tgl'] = $poh['tgl'];
                    $jsonData2[0]['tgldeliesti'] = $poh['tgldeliesti'];
                    $jsonData2[0]['tujuan'] = $poh['deliverytext'];
                    $jsonData2[0]['payterm'] = $poh['paymentterm'];
                    $jsonData2[0]['ket'] = $poh['ket'];
                     $jsonData2[0]['rev'] = $poh['revisi'];
//		   				$jsonData2[0]['typepo'] = $poh['typepo'];
                    $jsonData2[0]['tax'] = $poh['statusppn'];
                    $jsonData2[0]['invoiceto'] = $poh['invoiceto'];
                    $jsonData2[0]['petugas'] = $poh['petugas'];
                    $jsonData2[0]['ketin'] = trim($poh['ketin']);


//		   				$cusKode = $this->project->getProjectAndCustomer($poh['prj_kode']);
//				        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//				        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];

                    $temp = array();
                    foreach($pod as $key => $val)
                    {
                        $prjKode = $val['prj_kode'];
                        $temp[$prjKode][] = $val;
                    }
                    $pod = $temp;
                    $jsonData2[0]['trano'] = $approve;
                    $allReject = $this->workflow->getAllReject($approve);
                    $lastReject = $this->workflow->getLastReject($approve);
                    $this->view->lastReject = $lastReject;
                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    $this->view->result = $pod;
                    $this->view->trano = $approve;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->file = $file;
                    $this->view->preview = $preview;
                }
           }
       }
   		$json = $this->getRequest()->getParam("posts");
   		
   		
   		Zend_Loader::loadClass('Zend_Json');
   		$jsonData = Zend_Json::decode($json);
       	

//       	$this->view->result = $jsonData;
//
//       	$this->view->jsonResult = $json;
   }

    public function apppobudgetAction()
   {
   		$type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $sales = $this->getRequest()->getParam("sales");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/POO';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		$preview = $this->getRequest()->getParam("preview");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
	   		$files = $this->getRequest()->getParam("file");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');

	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);
	   		$file = Zend_Json::decode($files);

            $sup = $this->trans->getAlamatSup($jsonData2[0]['sup_kode']);
            $alamat = $sup['alamat']. " " . $sup['alamat2'];
            $tlpsup = $sup['tlp'];
            $faxsup = $sup['fax'];
            $jsonData2[0]['alamat_sup'] = $alamat;
            $jsonData2[0]['tlp_sup'] = $tlpsup;
            $jsonData2[0]['fax_sup'] = $faxsup;
            $jsonData2[0]['ketin'] = trim($jsonData2[0]['ketin']);

            $temp = array();
            foreach($jsonData as $key => $val)
            {
                $prjKode = $val['prj_kode'];
                $jsonData[$key]['pr_no'] = $val['pr_number'];
                $temp[$prjKode][] = $jsonData[$key];
                if ($jsonData[$key]['qtySupp'] == '')
                    $jsonData[$key]['qtySupp'] = $jsonData[$key]['qty'];
                if ($jsonData[$key]['priceSupp'] == '')
                    $jsonData[$key]['priceSupp'] = $jsonData[$key]['price'];
                if ($jsonData[$key]['totalPriceSupp'] == '' || $jsonData[$key]['totalPriceSupp'] == 0)
                    $jsonData[$key]['totalPriceSupp'] = $jsonData[$key]['price'] * $jsonData[$key]['qty'];
            }
            $jsonData = $temp;
//            var_dump($jsonData2[0]['alamat_sup']);die;
//	        $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
//	        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//	        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
	       	$this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = $json;
	       	$this->view->jsonFile = $files;
	       	$this->view->file = $file;
   			if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}
            if ($sales == 'true')
            {
                $this->view->sales = true;
            }
   		}
   		else
   		{

            $this->view->approve = false;

            if (is_numeric($approve))
            {
   			    $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
                   if ($docs)
                   {
                      $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
                      if ($user || $show)
                      {
                          $id = $docs['workflow_trans_id'];
                          $approve = $docs['item_id'];

                          $this->view->approve = true;
                      }
                   }
            }
            else
            {
                $pod = $this->purchase->fetchRow("trano = '$approve'");
                if ($pod)
                {
                    $docs = $this->workflow->getDocumentLastStatusAll($approve);
                    $this->view->approve = true;
                }
                else
                    $this->view->approve = false;
            }

   			if($this->view->approve)
           {
               $userApp = $this->workflow->getAllApproval($approve);
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
                    $potong = substr($approve,0,4);
                    if ($potong == 'PO01')
                        $this->view->sales = true;

		   			$pod = $this->purchase->fetchAll("trano = '$approve'")->toArray();
		   			$poh = $this->purchaseH->fetchRow("trano = '$approve'");
		   			$file = $this->files->fetchAll("trano = '$approve'");
		   			if ($pod)
		   			{
		   				foreach($pod as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$pod[$key]['uom'] = $barang['sat_kode'];
		   					}

		   						$pod[$key]['price'] = $val['harga'];
                                $pod[$key]['net_act'] = $val['myob'];
                                if ($val['qtyspl'] == '')
                                    $pod[$key]['qtyspl'] = $val['qty'];
                                if ($val['hargaspl'] == '')
                                    $pod[$key]['hargaspl'] = $val['harga'];

                                if ($val['qtyspl'] == '' )
                                    $pod[$key]['totalspl'] = $val['harga'] * $val['qty'];
                                $pod[$key]['qtySupp'] = $pod[$key]['qtyspl'];
                                $pod[$key]['priceSupp'] = $pod[$key]['hargaspl'];
                                $pod[$key]['totalPriceSupp'] = $pod[$key]['qtyspl'] * $pod[$key]['hargaspl'];
		   				}

                        $sup = $this->trans->getAlamatSup($poh['sup_kode']);

                        $jsonData2[0]['user_approval'] = $userApp;
                        $alamat = $sup['alamat']. " " . $sup['alamat2'];
                        $tlpsup = $sup['tlp'];
                        $faxsup = $sup['fax'];
                        $jsonData2[0]['alamat_sup'] = $alamat;
                        $jsonData2[0]['tlp_sup'] = $tlpsup;
                        $jsonData2[0]['fax_sup'] = $faxsup;

		   				$jsonData2[0]['prj_kode'] = $poh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $poh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $poh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $poh['sit_nama'];
		   				$jsonData2[0]['sup_kode'] = $poh['sup_kode'];
		   				$jsonData2[0]['sup_nama'] = $poh['sup_nama'];

                        $jsonData2[0]['tgl'] = $poh['tgl'];
		   				$jsonData2[0]['tgldeliesti'] = $poh['tgldeliesti'];
                        $jsonData2[0]['tujuan'] = $poh['deliverytext'];
		   				$jsonData2[0]['payterm'] = $poh['paymentterm'];
                        $jsonData2[0]['ket'] = $poh['ket'];
                         $jsonData2[0]['rev'] = $poh['revisi'];
//		   				$jsonData2[0]['typepo'] = $poh['typepo'];
                        $jsonData2[0]['tax'] = $poh['statusppn'];
		   				$jsonData2[0]['invoiceto'] = $poh['invoiceto'];
                        $jsonData2[0]['petugas'] = $poh['petugas'];
                        $jsonData2[0]['ketin'] = trim($poh['ketin']);


//		   				$cusKode = $this->project->getProjectAndCustomer($poh['prj_kode']);
//				        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//				        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];

                        $temp = array();
                        foreach($pod as $key => $val)
                        {
                            $prjKode = $val['prj_kode'];
                            $temp[$prjKode][] = $val;
                        }
                        $pod = $temp;
				        $jsonData2[0]['trano'] = $approve;
		                $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $pod;
                        $this->view->trano = $approve;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
                        $this->view->file = $file;
                        $this->view->preview = $preview;
		   			}
           }
   		}

   		$json = $this->getRequest()->getParam("posts");


   		Zend_Loader::loadClass('Zend_Json');
   		$jsonData = Zend_Json::decode($json);

   }


    public function allprAction()
   {

    }
    public function allpoAction()
   {

    }
    public function allarfAction()
   {

    }


    public function insertarfAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json = $this->getRequest()->getParam('posts');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\","",$etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);
        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('ARF');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'ARF01-' . $last;

        $ada = false;
        while(!$ada)
        {
            $cek = $this->arfd->fetchRow("trano = '$trano'");
            if ($cek)
            {
                $lastTrans = $counter->getLastTrans('ARF');
                $last = abs($lastTrans['urut']);
                $last = $last + 1;
                $trano = 'ARF01-' . $last;
            }
            elseif (!$cek)
                $ada = true;
        }

        $urut = 1;
        $total = 0;
        $totals = 0;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        

        $result = $this->workflow->setWorkflowTrans($trano,'ARF', '', $this->const['DOCUMENT_SUBMIT'],$items);
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
        foreach($jsonData as $key => $val)
        {
                if ($val['val_kode'] == 'IDR')
                    $harga = $val['hargaIDR'];
                else
                    $harga = $val['hargaUSD'];

             $total = $val['qty'] * $val['priceArf'];
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
                 "qty" => $val['qty'],
                 "harga" => $val['priceArf'],
                 "total" => $total,
                 "ket" => $val['ket'],
                 "cfs_kode" => $val['net_act'],
                 "petugas" => $this->session->userName,
                 "requester" => $val['requester'],
                 "val_kode" => $val['val_kode']
             );
                $urut++;
                $totals = $totals + $total;

             $this->arfd->insert($arrayInsert);

        }

             $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
             $cusKode = $cusKode[0]['cus_kode'];
             $arrayInsert = array (
                 "trano" => $trano,
                 "tgl" => date('Y-m-d'),
                 "prj_kode" => $jsonEtc[0]['prj_kode'],
                 "prj_nama" => $jsonEtc[0]['prj_nama'],
                 "sit_kode" => $jsonEtc[0]['sit_kode'],
                 "sit_nama" => $jsonEtc[0]['sit_nama'],
                 "namabank" => $jsonEtc[0]['bank'],
                 "rekbank" => $jsonEtc[0]['bankaccountno'],
                 "reknamabank" => $jsonEtc[0]['bankaccountname'],
                 "val_kode" => $jsonEtc[0]['valuta'],
                 "penerima" => $jsonEtc[0]['penerima'],
                 "orangfinance" => $jsonEtc[0]['finance'],
                 "request" => $jsonEtc[0]['mgr_kode'],
//                 "request2" => $jsonEtc[0]['requester'],
                 "orangpic" => $jsonEtc[0]['pic_kode'],
				 "ketin" => $jsonEtc[0]['ketin'],
                 "total" => $totals,
                 "user" => $this->session->userName,
                 "tglinput" => date('Y-m-d'),
                 "budgettype" => $jsonEtc[0]['budgettype'],
                 "jam" =>date('H:i:s'),
                 "bt" => $jsonEtc[0]['bt'],


             );
             $this->arfh->insert($arrayInsert);

            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonEtc[0]['prj_kode'],
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

    public function  insertarfbudgetAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json = $this->getRequest()->getParam('posts');
        $sales = $this->getRequest()->getParam('sales');
        $file = $this->getRequest()->getParam('file');

        $etc = str_replace("\\","",$etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
         $jsonFile = Zend_Json::decode($file);
        $counter = new Default_Models_MasterCounter();

        if($sales)
        {
       
        $lastTrans = $counter->getLastTrans('ARF');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'ARF01-' . $last;
        $tipe = 'S';
        }
        else
        {
        $lastTrans = $counter->getLastTrans('ARFO');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'ARF02-' . $last;
        $tipe = 'O';
        }

        $ada = false;
        while(!$ada)
        {
            $cek = $this->arfd->fetchRow("trano = '$trano'");
            if ($cek)
            {
                if($sales)
                {
          
                $lastTrans = $counter->getLastTrans('ARF');
                $last = abs($lastTrans['urut']);
                $last = $last + 1;
                $trano = 'ARF01-' . $last;
                }
                else
                {
                $lastTrans = $counter->getLastTrans('ARFO');
                $last = abs($lastTrans['urut']);
                $last = $last + 1;
                $trano = 'ARF02-' . $last;
                }
            }
            elseif (!$cek)
                $ada = true;
        }
    

        $urut = 1;
        $total = 0;
        $totals = 0;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');


        $result = $this->workflow->setWorkflowTrans($trano,'ARFO', '', $this->const['DOCUMENT_SUBMIT'],$items);
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
        foreach($jsonData as $key => $val)
        {

             $total = $val['qty'] * $val['priceArf'];
             $arrayInsert = array(
                 "trano" => $trano,
                 "tgl" => date('Y-m-d'),
                 "urut" => $urut,
                 "prj_kode" => $val['prj_kode'],
                 "prj_nama" => $val['prj_nama'],
                 "sit_kode" => $val['sit_kode'],
                 "sit_nama" => $val['sit_nama'],
                 "workid" => $val['budgetid'],
                 "workname" => $val['budgetname'],
                 "kode_brg" => $val['kode_brg'],
                 "nama_brg" => $val['nama_brg'],
                 "qty" => $val['qty'],
                 "harga" => $val['priceArf'],
                 "total" => $total,
                 "ket" => $val['ket'],
                 "petugas" => $this->session->userName,
                 "val_kode" => $val['val_kode'],
                 "cfs_kode" => $val['net_act'],
                 "requester" => $val['requester'],
                 "tipe" => $tipe
             );
                $urut++;
                $totals = $totals + $total;

             $this->arfd->insert($arrayInsert);

        }

             $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
             $cusKode = $cusKode[0]['cus_kode'];
             $arrayInsert = array (
                 "trano" => $trano,
                 "tgl" => date('Y-m-d'),
                 "prj_kode" => $jsonEtc[0]['prj_kode'],
                 "prj_nama" => $jsonEtc[0]['prj_nama'],
                 "sit_kode" => $jsonEtc[0]['sit_kode'],
                 "sit_nama" => $jsonEtc[0]['sit_nama'],
                 "namabank" => $jsonEtc[0]['bank'],
                 "rekbank" => $jsonEtc[0]['bankaccountno'],
                 "reknamabank" => $jsonEtc[0]['bankaccountname'],
                 "val_kode" => $jsonEtc[0]['valuta'],
                 "penerima" => $jsonEtc[0]['penerima'],
                 "orangfinance" => $jsonEtc[0]['finance'],
                 "request" => $jsonEtc[0]['mgr_kode'],
//                 "request2" => $jsonEtc[0]['requester'],
                 "orangpic" => $jsonEtc[0]['pic_kode'],
				 "ketin" => $jsonEtc[0]['ketin'],
                 "total" => $totals,
                 "user" => $this->session->userName,
                 "tglinput" => date('Y-m-d'),
                 "budgettype" => $jsonEtc[0]['budgettype'],
                 "jam" =>date('H:i:s'),
                 "tipe" => $tipe

             );
             $this->arfh->insert($arrayInsert);

            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonEtc[0]['prj_kode'],
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

     public function updatearfAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
           $etc = str_replace("\\","",$etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;



        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano,'ARF','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
        $urut=1;
         $log['arf-detail-before'] =  $this->arfd->fetchAll("trano = '$trano'")->toArray();
          $this->arfd->delete("trano = '$trano'");
        foreach($jsonData as $key => $val)
        {
                if ($val['val_kode'] == 'IDR')
                    $harga = $val['hargaIDR'];
                else
                    $harga = $val['hargaUSD'];

             $total = $val['qty'] * $val['priceArf'];
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
                 "cfs_kode" => $val['net_act'],
                 "qty" => $val['qty'],
                 "harga" => $val['priceArf'],
                 "total" => $total,
                 "ket" => $val['ket'],
                 "requester" => $val['requester'],
                 "petugas" => $this->session->userName,
                 "val_kode" => $val['val_kode']
             );
                $urut++;
                $totals = $totals + $total;

             $this->arfd->insert($arrayInsert);

        }
         $log2['arf-detail-after'] =  $this->arfd->fetchAll("trano = '$trano'")->toArray();
            $arrayInsert = array (
//                 "trano" => $trano,
//                 "tgl" => date('Y-m-d'),
//                 "prj_kode" => $jsonEtc[0]['prj_kode'],
//                 "prj_nama" => $jsonEtc[0]['prj_nama'],
//                 "sit_kode" => $jsonEtc[0]['sit_kode'],
//                 "sit_nama" => $jsonEtc[0]['sit_nama'],
                 "namabank" => $jsonEtc[0]['bank'],
                 "rekbank" => $jsonEtc[0]['bankaccountno'],
                 "reknamabank" => $jsonEtc[0]['bankaccountname'],
//                 "val_kode" => $jsonEtc[0]['valuta'],
                 "penerima" => $jsonEtc[0]['penerima'],
				 "ketin" => $jsonEtc[0]['ketin'],
                 "orangfinance" => $jsonEtc[0]['finance'],
                 "request" => $jsonEtc[0]['mgr_kode'],
//                 "request2" => $jsonEtc[0]['requester'],
                 "orangpic" => $jsonEtc[0]['pic_kode'],
                 "total" => $totals,
                 "user" => $this->session->userName,
                 "tglinput" => date('Y-m-d'),
                 "budgettype" => $jsonEtc[0]['budgettype'],
                 "jam" =>date('H:i:s'),
                "bt" => $jsonEtc[0]['bt'],


             );
            $log['arf-header-before'] =  $this->arfh->fetchRow("trano = '$trano'")->toArray();
             $this->arfh->update($arrayInsert,"trano = '$trano'");
            $log2['arf-header-after'] =  $this->arfh->fetchRow("trano = '$trano'")->toArray();

            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "UPDATE",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);
            $this->files->delete("trano = '$trano'");
            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonEtc[0]['prj_kode'],
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

    public function updatearfbudgetAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $sales = $this->getRequest()->getParam('sales');
        $file = $this->getRequest()->getParam('file');
           $etc = str_replace("\\","",$etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;

        if($sales)
        {
           $tipe = 'S';
        }
        else
        {
            $tipe = 'O';
        }

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano,'ARFO','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
        $urut=1;
        $log['arf-detail-before'] =  $this->arfd->fetchAll("trano = '$trano'")->toArray();
          $this->arfd->delete("trano = '$trano'");
        foreach($jsonData as $key => $val)
        {
                if ($val['val_kode'] == 'IDR')
                    $harga = $val['hargaIDR'];
                else
                    $harga = $val['hargaUSD'];

             $total = $val['qty'] * $val['priceArf'];
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
                 "qty" => $val['qty'],
                 "harga" => $val['priceArf'],
                 "total" => $total,
                 "ket" => $val['ket'],
                 "petugas" => $this->session->userName,
                 "requester" => $val['requester'],
                 "val_kode" => $val['val_kode'],
                 "cfs_kode" => $val['net_act'],
                 "tipe" => $tipe
             );
                $urut++;
                $totals = $totals + $total;

             $this->arfd->insert($arrayInsert);

        }
        $log2['arf-detail-after'] =  $this->arfd->fetchAll("trano = '$trano'")->toArray();
            $arrayInsert = array (
//                 "trano" => $trano,
//                 "tgl" => date('Y-m-d'),
//                 "prj_kode" => $jsonEtc[0]['prj_kode'],
//                 "prj_nama" => $jsonEtc[0]['prj_nama'],
//                 "sit_kode" => $jsonEtc[0]['sit_kode'],
//                 "sit_nama" => $jsonEtc[0]['sit_nama'],
                 "namabank" => $jsonEtc[0]['bank'],
                 "rekbank" => $jsonEtc[0]['bankaccountno'],
                 "reknamabank" => $jsonEtc[0]['bankaccountname'],
//                 "val_kode" => $jsonEtc[0]['valuta'],
                 "penerima" => $jsonEtc[0]['penerima'],
                 "orangfinance" => $jsonEtc[0]['finance'],
                 "request" => $jsonEtc[0]['mgr_kode'],
//                 "request2" => $jsonEtc[0]['requester'],
                 "orangpic" => $jsonEtc[0]['pic_kode'],
				 "ketin" => $jsonEtc[0]['ketin'],
                 "total" => $totals,
                 "user" => $this->session->userName,
                 "tglinput" => date('Y-m-d'),
                 "budgettype" => $jsonEtc[0]['budgettype'],
                 "jam" =>date('H:i:s'),
                "bt" => $jsonEtc[0]['bt'],


             );
            $log['arf-header-before'] =  $this->arfh->fetchRow("trano = '$trano'")->toArray();
             $this->arfh->update($arrayInsert,"trano = '$trano'");
            $log2['arf-header-after'] =  $this->arfh->fetchRow("trano = '$trano'")->toArray();

            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "UPDATE",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);

            $this->files->delete("trano = '$trano'");
            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonEtc[0]['prj_kode'],
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



        public function  apparfAction()
    {
            $type = $this->getRequest()->getParam("type");
            $from = $this->getRequest()->getParam("from");
            $show = $this->getRequest()->getParam("show");
            $this->view->show = $show;
            $doc_file = $this->getRequest()->getParam("doc_file");
            $this->view->doc_file = $doc_file;

            if ($type != '')
                $this->view->urlBack = '/default/home/showprocessdocument/type/ARF';
            else
                $this->view->urlBack = '/default/home/showprocessdocument';

            $approve = $this->getRequest()->getParam("approve");


            if ($approve == '')
            {
                $json = $this->getRequest()->getParam("posts");
                $etc = $this->getRequest()->getParam("etc");
                $files = $this->getRequest()->getParam("file");
                $etc = str_replace("\\","",$etc);

                Zend_Loader::loadClass('Zend_Json');
                $jsonData = Zend_Json::decode($json);
                $jsonData2 = Zend_Json::decode($etc);
                $file = Zend_Json::decode($files);

                foreach($jsonData as $k => $v)
                {
                    $jsonData[$k]['cfs_kode'] = $v['net_act'];
                    $jsonData[$k]['cfs_nama'] = $v['net_act'];
                }

             $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
             $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
             $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                $this->view->result = $jsonData;
                $this->view->etc = $jsonData2;
                $this->view->jsonResult = $json;
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
                    if ($user  || $show)
                    {
                        $id = $docs['workflow_trans_id'];
                        $workflowId = $docs['workflow_id'];
                        $approve = $docs['item_id'];
                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;    
                        $statApprove = $docs['approve'];

                     $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

                        if ($statApprove == $this->const['DOCUMENT_REJECT'])
                            $this->view->reject = true;
                        $arfd = $this->arfd->fetchAll("trano = '$approve'")->toArray();
                        $arfh = $this->arfh->fetchRow("trano = '$approve'");
                        $file = $this->files->fetchAll("trano = '$approve'");

                        if ($arfd)
                        {
                            foreach($arfd as $key => $val)
                            {
                                $kodeBrg = $val['kode_brg'];
                                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                                if ($barang)
                                {
                                    $arfd[$key]['uom'] = $barang['sat_kode'];
                                }
//                                $arfd[$key]['priceArf'] = $val['priceArf'];
                                    $arfd[$key]['priceArf'] = $val['harga'];
                                    $arfd[$key]['totalARF'] = $val['total'];
                                    $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
                            }

                         $userApp = $this->workflow->getAllApproval($approve);
                         $jsonData2[0]['user_approval'] = $userApp;
                            $jsonData2[0]['prj_kode'] = $arfh['prj_kode'];
                            $jsonData2[0]['prj_nama'] = $arfh['prj_nama'];
                            $jsonData2[0]['sit_kode'] = $arfh['sit_kode'];
                            $jsonData2[0]['sit_nama'] = $arfh['sit_nama'];
                            $jsonData2[0]['budgettype'] = $arfh['budgettype'];
                            $jsonData2[0]['valuta'] = $arfh['val_kode'];
                            $jsonData2[0]['mgr_kode'] = $arfh['request'];
                            $jsonData2[0]['pic_kode'] = $arfh['orangpic'];
                            $jsonData2[0]['ketin'] = $arfh['ketin'];
                           
                            $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                            $jsonData2[0]['pic_nama'] = $picName['Name'];
                            $mgrName = $this->trans->getManagerName($approve);
                            $jsonData[0]['mgr_nama'] = $mgrName;
                            
                            $cusKode = $this->project->getProjectAndCustomer($arfh['prj_kode']);
                         $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                         $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                         $jsonData2[0]['trano'] = $approve;
                            
                            $allReject = $this->workflow->getAllReject($approve);
                            $lastReject = $this->workflow->getLastReject($approve);
                            $this->view->lastReject = $lastReject;
                            $this->view->allReject = $allReject;
                            $this->view->etc = $jsonData2;
                            $this->view->result = $arfd;                         
                            $this->view->file = $file;
                            $this->view->trano = $approve;
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

    public function apparfbudgetAction()
    {
            $type = $this->getRequest()->getParam("type");
            $from = $this->getRequest()->getParam("from");
            $sales = $this->getRequest()->getParam("sales");
            $show = $this->getRequest()->getParam("show");
            $this->view->show = $show;

            if ($type != '')
                $this->view->urlBack = '/default/home/showprocessdocument/type/ARFO';
            else
                $this->view->urlBack = '/default/home/showprocessdocument';

            $approve = $this->getRequest()->getParam("approve");


            if ($approve == '')
            {
                $json = $this->getRequest()->getParam("posts");
                $etc = $this->getRequest()->getParam("etc");
                $files = $this->getRequest()->getParam("file");
                $etc = str_replace("\\","",$etc);
                Zend_Loader::loadClass('Zend_Json');
                $jsonData = Zend_Json::decode($json);
                $jsonData2 = Zend_Json::decode($etc);
                $file = Zend_Json::decode($files);
                
                foreach($jsonData as $key => $val)
   			    {
                    $jsonData[$key]['cfs_kode'] = $val['net_act'];
                    $jsonData[$key]['cfs_nama'] = $val['net_act'];
                    foreach($val as $key2 => $val2)
                    {
                        if ($val2 == "\"\"")
                            $jsonData[$key][$key2] = '';
                        if (strpos($val2,"\"")!== false)
                            $jsonData[$key][$key2] = str_replace("\""," inch",$jsonData[$key][$key2]);
                        if (strpos($val2,"'")!== false)
                            $jsonData[$key][$key2] = str_replace("'"," inch",$jsonData[$key][$key2]);
                    }
   			    }
//             $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
//             $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//             $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                $this->view->result = $jsonData;
                $this->view->etc = $jsonData2;
                $this->view->jsonResult = $json;
                $this->view->jsonFile = $files;
	       	    $this->view->file = $file;

                if ($from == 'edit')
                {
                    $this->view->edit = true;
                }

                if ($sales == 'true')
                {
                    $this->view->sales = true;
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
                        $workflowId = $docs['workflow_id'];
                        $approve = $docs['item_id'];
                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $statApprove = $docs['approve'];

                     $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

                        if ($statApprove == $this->const['DOCUMENT_REJECT'])
                            $this->view->reject = true;

                        $potong = substr($approve,0,5);
                        if ($potong == 'ARF01')
                            $this->view->sales = true;

                        $arfd = $this->arfd->fetchAll("trano = '$approve'")->toArray();
                        $arfh = $this->arfh->fetchRow("trano = '$approve'");
                        $file = $this->files->fetchAll("trano = '$approve'");
                        
                        if ($arfd)
                        {
                            foreach($arfd as $key => $val)
                            {
                                $kodeBrg = $val['kode_brg'];
                                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                                if ($barang)
                                {
                                    $arfd[$key]['uom'] = $barang['sat_kode'];
                                }
//                                $arfd[$key]['priceArf'] = $val['priceArf'];
                                    $arfd[$key]['priceArf'] = $val['harga'];
                                    $arfd[$key]['totalARF'] = $val['total'];
                                    $arfd[$key]['budgetid'] = $val['workid'];
                                    $arfd[$key]['budgetname'] = $val['workname'];
                                    $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
                            }

                         $userApp = $this->workflow->getAllApproval($approve);
                         $jsonData2[0]['user_approval'] = $userApp;
                            $jsonData2[0]['prj_kode'] = $arfh['prj_kode'];
                            $jsonData2[0]['prj_nama'] = $arfh['prj_nama'];
                            $jsonData2[0]['sit_kode'] = $arfh['sit_kode'];
                            $jsonData2[0]['sit_nama'] = $arfh['sit_nama'];
                            $jsonData2[0]['budgettype'] = $arfh['budgettype'];
                            $jsonData2[0]['valuta'] = $arfh['val_kode'];
//                            $jsonData2[0]['requester'] = $arfh['request2'];
                            $jsonData2[0]['mgr_kode'] = $arfh['request'];
                            $jsonData2[0]['pic_kode'] = $arfh['orangpic'];
                            $jsonData2[0]['ketin'] = $arfh['ketin'];

                            $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                            $jsonData2[0]['pic_nama'] = $picName['Name'];
                            $mgrName = $this->trans->getManagerName($approve);
                            $jsonData[0]['mgr_nama'] = $mgrName;

//                            $cusKode = $this->project->getProjectAndCustomer($arfh['prj_kode']);
//                         $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//                         $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                         $jsonData2[0]['trano'] = $approve;

                            $allReject = $this->workflow->getAllReject($approve);
                            $lastReject = $this->workflow->getLastReject($approve);
                            $this->view->lastReject = $lastReject;
                            $this->view->allReject = $allReject;
                            $this->view->etc = $jsonData2;
                            $this->view->result = $arfd;
                            $this->view->file = $file;
                            $this->view->trano = $approve;
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

    public function appasfAction()
   {
   		$type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
            $show = $this->getRequest()->getParam("show");
            $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/ASF';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
            $json2 = $this->getRequest()->getParam("posts2");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);
            $jsonData3 = Zend_Json::decode($json2);

	       	$this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
            $this->view->result2 = $jsonData3;
	       	$this->view->jsonResult = $json;
            $this->view->jsonResult2 = $json2;

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
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
		   			$asfdd = $this->asf->fetchAll("trano = '$approve'")->toArray();
                    $asfddcancel = $this->asfc->fetchAll("trano = '$approve'")->toArray();
		   			$asfh = $this->asfH->fetchRow("trano = '$approve'");
		   			if ($asfdd)
		   			{
		   				foreach($asfdd as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$asfdd[$key]['uom'] = $barang['sat_kode'];
		   					}

                               $asfdd[$key]['price'] = $val['harga'];

		   				}
                    }

                   	if ($asfddcancel)
		   			{
		   				foreach($asfddcancel as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$asfddcancel[$key]['uom'] = $barang['sat_kode'];
		   					}

                               $asfddcancel[$key]['price'] = $val['harga'];

		   				}
                    }
		   				$jsonData2[0]['prj_kode'] = $asfh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $asfh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $asfh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $asfh['sit_nama'];
		   				$jsonData2[0]['ket'] = $asfh['ket'];
		   				$jsonData2[0]['petugas'] = $asfh['petugas'];
                        $jsonData2[0]['requester'] = $asfh['request2'];
                        $jsonData2[0]['arf_no'] = $asfh['arf_no'];

                        $jsonData2[0]['tgl'] = $asfh['tgl'];
                        $jsonData2[0]['tgl_arf'] = $asfh['tglarf'];
		   				$jsonData2[0]['pic'] = $asfh['orangpic'];
                        $jsonData2[0]['finance'] = $asfh['orangfinance'];
//		   				$jsonData2[0]['payterm'] = $asfh['paymentterm'];


				        $jsonData2[0]['trano'] = $approve;
                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;   
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $asfdd;
                        $this->view->trano = $approve;
                        $this->view->result2 = $asfddcancel;
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

   		$json = $this->getRequest()->getParam("posts");


   		Zend_Loader::loadClass('Zend_Json');
   		$jsonData = Zend_Json::decode($json);
   }

    public function appasfbudgetAction()
   {
   		$type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
        $sales = $this->getRequest()->getParam("sales");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/ASFO';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
            $json2 = $this->getRequest()->getParam("posts2");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);
            $jsonData3 = Zend_Json::decode($json2);

	       	$this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
            $this->view->result2 = $jsonData3;
	       	$this->view->jsonResult = $json;
            $this->view->jsonResult2 = $json2;

   			if ($from == 'edit')
	       	{
	       		$this->view->edit = true;
	       	}

           if($sales)
           {
               $this->view->sales = true;
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
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
                    $potong = substr($approve,0,5);
                    if ($potong == 'ASF01')
                        $this->view->sales = true;

		   			$asfdd = $this->asf->fetchAll("trano = '$approve'")->toArray();
                    $asfddcancel = $this->asfc->fetchAll("trano = '$approve'")->toArray();
		   			$asfh = $this->asfH->fetchRow("trano = '$approve'");
		   			if ($asfdd)
		   			{
		   				foreach($asfdd as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$asfdd[$key]['uom'] = $barang['sat_kode'];
		   					}

                               $asfdd[$key]['price'] = $val['harga'];

		   				}
                    }

                   	if ($asfddcancel)
		   			{
		   				foreach($asfddcancel as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$asfddcancel[$key]['uom'] = $barang['sat_kode'];
		   					}

                               $asfddcancel[$key]['price'] = $val['harga'];

		   				}
                    }
		   				$jsonData2[0]['prj_kode'] = $asfh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $asfh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $asfh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $asfh['sit_nama'];
		   				$jsonData2[0]['ket'] = $asfh['ket'];
		   				$jsonData2[0]['petugas'] = $asfh['petugas'];
                        $jsonData2[0]['requester'] = $asfh['request2'];
                        $jsonData2[0]['arf_no'] = $asfh['arf_no'];

                        $jsonData2[0]['tgl'] = $asfh['tgl'];
                        $jsonData2[0]['tgl_arf'] = $asfh['tglarf'];
		   				$jsonData2[0]['pic'] = $asfh['orangpic'];
                        $jsonData2[0]['finance'] = $asfh['orangfinance'];
//		   				$jsonData2[0]['payterm'] = $asfh['paymentterm'];

				        $jsonData2[0]['trano'] = $approve;
                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $asfdd;
                        $this->view->trano = $approve;
                        $this->view->result2 = $asfddcancel;
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

   		$json = $this->getRequest()->getParam("posts");


   		Zend_Loader::loadClass('Zend_Json');
   		$jsonData = Zend_Json::decode($json);
   }

    public function pmealAction()
    {

    }

    public function addpmealAction()
    {

    }

    public function editpmealAction()
    {
        $trano = $this->getRequest()->getParam("trano");
   		$pmeald = $this->pmeal->fetchAll("notran = '$trano'",array("urut ASC"))->toArray();
   		$pmealh = $this->pmealH->fetchRow("notran = '$trano'");

   		if ($pmeald)
   		{
   			foreach($pmeald as $key => $val)
   			{
   				$pmeald[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
   				if ($barang)
   				{
   					$pmeald[$key]['uom'] = $barang['sat_kode'];
                    $pmeald[$key]['nama_brg'] = $barang['nama_brg'];
   				}

                $pmeald[$key]['boq_no'] = $val['boqtran'];
                $pmeald[$key]['price'] = $val['harga_borong'];
                $pmeald[$key]['totalPrice'] = $val['qty']*$val['harga_borong'];   

   			}

   			Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($pmeald);
                              
	   		$isCancel = $this->getRequest()->getParam("returnback");
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
	   			$this->view->json = $jsonData;

	       	$this->view->trano = $trano;
	       	$this->view->tgl = $pmealh['tgl'];

            $pmealh['ket'] = preg_replace("[^A-Za-z0-9-.,]","",$pmealh['ket']);
            $this->view->ket =preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $pmealh['ket']);
	      
//	       	$this->view->ket = $pmealh['ket'];
	       	$this->view->prj_nama = $pmealh['prj_nama'];
	       	$this->view->sit_nama = $pmealh['sit_nama'];
       }
    }

    public function apppmealAction()
    {
        $type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/PMEAL';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		$preview = $this->getRequest()->getParam("preview");
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
	   				$workflowId = $docs['workflow_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
		   			$pmeald = $this->pmeal->fetchAll("notran = '$approve'")->toArray();
		   			$pmealh = $this->pmealH->fetchRow("notran = '$approve'");
		   			if ($pmeald)
		   			{
		   				foreach($pmeald as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$pmeald[$key]['uom'] = $barang['sat_kode'];
		   						$pmeald[$key]['nama_brg'] = $barang['nama_brg'];
		   					}
                            $pmeald[$key]['totalPrice'] = $pmeald[$key]['harga_borong'] * $pmeald[$key]['qty'];   
		   				}

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
		   				$jsonData2[0]['prj_kode'] = $pmealh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $pmealh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $pmealh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $pmealh['sit_nama'];

				        $jsonData2[0]['ket'] = $pmealh['ket'];
				        
				        $jsonData2[0]['trano'] = $approve;
                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;   
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $pmeald;
                        $this->view->trano = $approve;
		   				$this->view->approve = true;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
                        $this->view->preview = $preview;  
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

    public function insertpmealAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($this->json);
       $jsonEtc = Zend_Json::decode($etc);

       $counter = new Default_Models_MasterCounter();

	   $lastTrans = $counter->getLastTrans('PBOQ3');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'PBQ110-' . $last;

       $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'PBOQ3', '', $this->const['DOCUMENT_SUBMIT'],$items);
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
       $urut = 1;
       $totals = 0;
       foreach($jsonData as $key => $val)
       {
       		$harga = $val['price'];

       		$total = $val['qty'] * $harga;
			$arrayInsert = array(
				"notran" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
                "kode_brg" => $val['kode_brg'],
				
                "harga_borong" => $harga,
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
                "prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"qty" => $val['qty'],				
				
				"boqtran" => $val['boq_no'],
				"stspmeal" => $val['stspmeal']
			);
               $urut++;
               $totals = $totals + $total;
//                var_dump($arrayInsert);die;
            $this->pmeal->insert($arrayInsert);

       }

            $arrayInsert = array (
            	"notran" => $trano,
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
                "prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],

				"ket" => $jsonEtc[0]['ket'],
                "tgl" => date('Y-m-d'),
                "jumlah" => $totals,
				"boqtran" => $jsonEtc[0]['boq_no'],
				
            );
            $this->pmealH->insert($arrayInsert);
			$this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

	public function updatepmealAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $etc = $this->getRequest()->getParam('etc');
//       $json = $this->getRequest()->getParam('posts');
   	   $etc = str_replace("\\","",$etc);
//       $json = str_replace("\\","",$json);
       $jsonData = Zend_Json::decode($this->json);
//        $jsonData = Zend_Json::decode($json);
       $jsonEtc = Zend_Json::decode($etc);
     
       $trano = $jsonEtc[0]['trano'];
       
        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'PBOQ3', '', $this->const['DOCUMENT_RESUBMIT'],$items);
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

       $totals = 0;
       foreach($jsonData as $key => $val)
       {
            $harga = $val['price'];

       		$total = $val['qty'] * $harga;

			$arrayInsert = array(
				"qty" => $val['qty'],
				"harga_borong" => $harga,

			);
            $totals = $totals + $total;

			$kode_brg = $val['kode_brg'];

            $this->pmeal->update($arrayInsert,"notran = '$trano' AND kode_brg = '$kode_brg'");

       }
            $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];
            $arrayInsert = array (
				"jumlah" => $totals,

            );
            $result = $this->pmealH->update($arrayInsert,"notran = '$trano'");
            $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function deletefileAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $fileName = $this->getRequest()->getParam('filename');
        $id = $this->getRequest()->getParam('id');
        $savePath = Zend_Registry::get('uploadPath') . 'files';
        
        if ($fileName != '')
            $myFiles = $savePath . "/" . $fileName;

        if (file_exists($myFiles))
        {
            unlink($myFiles);
            $return = array('success' => true);
        }
        else
        {
            $return = array('success' => false, 'msg' => 'File Not Found!');
        }
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        echo $json;
    }


    public function uploadfileAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $type = $this->getRequest()->getParam('type');

        $success = 'false';
        $msg = '';

        if ($_FILES['file-path']['name'] != '' && $_FILES['file-path']['size'] != '')
        {
            if ($_FILES['file-path']['error'] === UPLOAD_ERR_INI_SIZE)
            {
                $return = array('success' => false, 'msg' => 'Error while uploading Your file, Your file is too large..');
            }
            else
            {

                $result = $this->upload->uploadFile($_FILES,'file-path',true, 'files');
                if ($result)
                {
                    $savePath = Zend_Registry::get('uploadPath') . 'files';
                    $myFiles = $savePath . "/" . $result['save_name'];

                    $name = explode(".",$result['origin_name']);
                    $fileName = $name[0];
                    $fileName = preg_replace("/[^a-zA-Z0-9\s]/", "_",$fileName );
                    $name[0] = $fileName;
                    $newName = implode(".",$name);
                    $return = array('success' => true,'filename' => $newName,'path' => $myFiles,'savename' => $result['save_name']);
                }
                else
                    $return = array('success' => false, 'msg' => 'Error while uploading Your file, Please contact Administrator or Support Team.');
            }
        }
        else
        {
            $return = array('success' => false);
        }
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        echo $json;
//        switch($type)
//        {
//            case 'PO':
//
//                break;
//            default:
//                $msg = 'No Such Transaction!';
//                break;
//        }
    }

    public function reimbursAction()
    {
        
    }

    public function addreimbursAction()
    {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
    }

    public function appreimbursAction()
    {

        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

            if ($type != '')
                $this->view->urlBack = '/default/home/showprocessdocument/type/Reimburs';
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
                $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));
                
             $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
             $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
             $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                $this->view->result = $jsonData;
                $this->view->etc = $jsonData2;
                $this->view->jsonResult = $json;
                $this->view->file = $filedata;
                $this->view->JsonFile = $this->getRequest()->getParam('filedata');
                $this->view->DeletedFile = $this->getRequest()->getParam('deletedfile');

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
                        $workflowId = $docs['workflow_id'];
                        $approve = $docs['item_id'];
                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $statApprove = $docs['approve'];

                     $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

                        if ($statApprove == $this->const['DOCUMENT_REJECT'])
                            $this->view->reject = true;
                        $reimbursd = $this->reimbursD->fetchAll("trano = '$approve'")->toArray();
                        $reimbursh = $this->reimbursH->fetchRow("trano = '$approve'");
                        if ($reimbursd)
                        {
                            foreach($reimbursd as $key => $val)
                            {
                                $kodeBrg = $val['kode_brg'];
                                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                                if ($barang)
                                {
                                    $reimbursd[$key]['uom'] = $barang['sat_kode'];
                                }
//                                $arfd[$key]['priceArf'] = $val['priceArf'];
                                    $reimbursd[$key]['harga'] = $val['harga'];
                            }

                         $userApp = $this->workflow->getAllApproval($approve);
                         $jsonData2[0]['user_approval'] = $userApp;
                            $jsonData2[0]['prj_kode'] = $reimbursh['prj_kode'];
                            $jsonData2[0]['prj_nama'] = $reimbursh['prj_nama'];
                            $jsonData2[0]['sit_kode'] = $reimbursh['sit_kode'];
                            $jsonData2[0]['sit_nama'] = $reimbursh['sit_nama'];
                            $jsonData2[0]['budgettype'] = $reimbursh['budgettype'];
                            $jsonData2[0]['valuta'] = $reimbursh['val_kode'];
                            $jsonData2[0]['requester'] = $reimbursh['request2'];
                            $jsonData2[0]['mgr_kode'] = $reimbursh['request'];
                            $jsonData2[0]['pic_kode'] = $reimbursh['orangpic'];

                            $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                            $jsonData2[0]['pic_nama'] = $picName['Name'];
                            $mgrName = $this->trans->getManagerName($approve);
                            $jsonData[0]['mgr_nama'] = $mgrName;

                            $cusKode = $this->project->getProjectAndCustomer($reimbursh['prj_kode']);
                         $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                         $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                         $jsonData2[0]['trano'] = $approve;

                            $filedata = $this->files->fetchAll("trano = '$approve'");

                            $allReject = $this->workflow->getAllReject($approve);
                            $lastReject = $this->workflow->getLastReject($approve);
                            $this->view->lastReject = $lastReject;
                            $this->view->allReject = $allReject;
                            $this->view->etc = $jsonData2;
                            $this->view->result = $reimbursd;
                            $this->view->trano = $approve;
                            $this->view->approve = true;
                            $this->view->uid = $this->session->userName;
                            $this->view->userID = $this->session->idUser;
                            $this->view->docsID = $id;
                            $this->view->file = $filedata;
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

    public function insertreimbursAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json = $this->getRequest()->getParam('posts');
        $etc = str_replace("\\","",$etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $counter = new Default_Models_MasterCounter();
        $filedata = Zend_Json::decode($this->getRequest()->getParam('file'));

        

        $lastTrans = $counter->getLastTrans('REM');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'REM01-' . $last;


        $ada = false;
        while(!$ada)
        {
            $cek = $this->reimbursD->fetchRow("trano = '$trano'");
            if ($cek)
            {
                $lastTrans = $counter->getLastTrans('REM');
                $last = abs($lastTrans['urut']);
                $last = $last + 1;
                $trano = 'REM01-' . $last;
            }
            elseif (!$cek)
                $ada = true;
        }
       
        $counter->update(array("urut" => $last),"id=".$lastTrans['id']);
        $urut = 1;
        $total = 0;
        $totals = 0;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');


        $result = $this->workflow->setWorkflowTrans($trano,'REM', '', $this->const['DOCUMENT_SUBMIT'],$items);
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

        foreach($jsonData as $key => $val)
        {
                if ($val['val_kode'] == 'IDR')
                    $harga = $val['hargaIDR'];
                else
                    $harga = $val['hargaUSD'];

             $total = $val['qty'] * $val['harga'];
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
                 "qty" => $val['qty'],
                 "harga" => $val['harga'],
                 "jumlah" => $total,
                 "ket" => $val['ket'],
                 "petugas" => $this->session->userName,
                 "val_kode" => $val['val_kode'],
                 "type" => 'P'
             );
                $urut++;
                $totals = $totals + $total;

//                var_dump($arrayInsert);die;
             $this->reimbursD->insert($arrayInsert);

        }

             $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
             $cusKode = $cusKode[0]['cus_kode'];
             $arrayInsert = array (
                 "trano" => $trano,
                 "tgl" => date('Y-m-d'),
                 "prj_kode" => $jsonEtc[0]['prj_kode'],
                 "prj_nama" => $jsonEtc[0]['prj_nama'],
                 "sit_kode" => $jsonEtc[0]['sit_kode'],
                 "sit_nama" => $jsonEtc[0]['sit_nama'],
                 "namabank" => $jsonEtc[0]['bank'],
                 "rekbank" => $jsonEtc[0]['bankaccountno'],
                 "reknamabank" => $jsonEtc[0]['bankaccountname'],
                 "val_kode" => $jsonEtc[0]['valuta'],
                 "penerima" => $jsonEtc[0]['penerima'],
                 "orangfinance" => $jsonEtc[0]['finance'],
                 "request" => $jsonEtc[0]['mgr_kode'],
                 "request2" => $jsonEtc[0]['requester'],
                 "orangpic" => $jsonEtc[0]['pic_kode'],
                 "total" => $totals,
                 "user" => $this->session->userName,
                 "tglinput" => date('Y-m-d'),
                 "budgettype" => $jsonEtc[0]['budgettype'],
                 "jam" =>date('H:i:s'),
                 "type" => 'P'

             );
             $this->reimbursH->insert($arrayInsert);


            if (count($filedata) > 0)
            {
                foreach ($filedata as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonEtc[0]['prj_kode'],
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

    public function updatereimbursAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
           $etc = str_replace("\\","",$etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($this->getRequest()->getParam('file'));
        $jsonDeletedFile = Zend_Json::decode($this->getRequest()->getParam('deletefile'));
        
        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano,'REM','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
        $urut=1;

          $this->reimbursD->delete("trano = '$trano'");
        foreach($jsonData as $key => $val)
        {
                if ($val['val_kode'] == 'IDR')
                    $harga = $val['hargaIDR'];
                else
                    $harga = $val['hargaUSD'];

             $total = $val['qty'] * $val['harga'];
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
                 "qty" => $val['qty'],
                 "harga" => $val['harga'],
                 "jumlah" => $total,
                 "ket" => $val['ket'],
                 "petugas" => $this->session->userName,
                 "val_kode" => $val['val_kode'],
                 "type" => 'P'
             );
                $urut++;
                $totals = $totals + $total;

             $this->reimbursD->insert($arrayInsert);

        }
            $arrayInsert = array (
//                 "trano" => $trano,
//                 "tgl" => date('Y-m-d'),
//                 "prj_kode" => $jsonEtc[0]['prj_kode'],
//                 "prj_nama" => $jsonEtc[0]['prj_nama'],
//                 "sit_kode" => $jsonEtc[0]['sit_kode'],
//                 "sit_nama" => $jsonEtc[0]['sit_nama'],
                 "namabank" => $jsonEtc[0]['bank'],
                 "rekbank" => $jsonEtc[0]['bankaccountno'],
                 "reknamabank" => $jsonEtc[0]['bankaccountname'],
//                 "val_kode" => $jsonEtc[0]['valuta'],
                 "penerima" => $jsonEtc[0]['penerima'],
                 "orangfinance" => $jsonEtc[0]['finance'],
                 "request" => $jsonEtc[0]['mgr_kode'],
                 "request2" => $jsonEtc[0]['requester'],
                 "orangpic" => $jsonEtc[0]['pic_kode'],
                 "total" => $totals,
                 "user" => $this->session->userName,
                 "tglinput" => date('Y-m-d'),
                 "budgettype" => $jsonEtc[0]['budgettype'],
                 "jam" =>date('H:i:s'),
                 "type" => 'P'

             );
             $this->reimbursH->update($arrayInsert,"trano = '$trano'");
//             $this->arfh->insert($arrayInsert);

            if (count($jsonFile) > 0)
            {
                foreach ($jsonFile as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => $jsonEtc[0]['prj_kode'],
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => $this->session->userName,
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    $this->files->insert($arrayInsert);
                }
            }

            if (count($jsonDeletedFile) > 0)
            {
                foreach ($jsonDeletedFile as $key => $val)
                {
                    $this->files->delete("id = {$val['id']}");
                }
            }


             $this->getResponse()->setBody("{success: true, number : '$trano'}");
     }

    public function editreimbursAction()
    {
     $this->view->uid = $this->session->userName;
     $this->view->nama = $this->session->name;

     $trano = $this->getRequest()->getParam("trano");
     $reimbursh = $this->reimbursH->fetchRow("trano = '$trano'");
     $reimbursd = $this->reimbursD->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();

        $where = "rem_no = '$trano'";
    $paymentquery = $this->paymentreimbursH->fetchAll ($where)->toArray();
    if ($paymentquery)
    {

     foreach ($paymentquery as $key => $val)
     {
         $total += $val['total'];
         $paymenttrano[] = $val['trano'];
     }
        $paymenttrano = implode(',',$paymenttrano);
    }
    else
    {
        $total = 0;
        $paymenttrano = '';
    }

      $this->view->totalpayment = $total;
    $this->view->tranopayment =  $paymenttrano;
      if ($reimbursh)
          $reimbursh =$reimbursh->toArray();
      $tmp = array();

     foreach($reimbursd as $key => $val)
     {
      foreach ($val as $key2 => $val2)
      {
          if ($val2 == '""')
              $reimbursd[$key][$key2] = '';
      }
        $reimbursd[$key]['id'] = $key + 1;
        $kodeBrg = $val['kode_brg'];
        $workid = $val['workid'];
        $sitKode = $val['sit_kode'];
        $prjKode = $val['prj_kode'];


         $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
         if ($barang)
         {
             $reimbursd[$key]['uom'] = $barang['sat_kode'];
         }
     }
      foreach($reimbursh as $key => $val)
      {
          if ($val == '""')
              $reimbursh[$key] = '';
      }
      $tmp2 = $reimbursh;
      unset($reimbursh);
      $reimbursh[0] = $tmp2;
         Zend_Loader::loadClass('Zend_Json');
         $jsonData = Zend_Json::encode($reimbursd);
         $jsonData2 = Zend_Json::encode($reimbursh);

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

        $files = $this->files->fetchAll("trano = '$trano'")->toArray();

        $JsonFile = Zend_Json::encode($files);

      $this->view->prNo = $tmp;
      $this->view->trano = $trano;
      $this->view->tgl = date('d-m-Y',strtotime($reimbursh[0]['tgl']));
      $this->view->pr_no = $reimbursh[0]['pr_no'];
      $this->view->val_kode = $reimbursh[0]['val_kode'];
      $this->view->request = $reimbursh[0]['request'];
      $this->view->orangfinance = $reimbursh[0]['orangfinance'];
      $this->view->ket = $reimbursh[0]['ket'];
      $this->view->jsonfile = $JsonFile;
    }

    public function cancelpoAction()
    {
        $this->view->user = $this->session->userName;
    }

    public function dorequestcancelpoAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $user = $this->getRequest()->getParam('user');
        $ponumber = $this->getRequest()->getParam('po-number');
        $reason = $this->getRequest()->getParam('cancel-reason');

        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER["REMOTE_ADDR"];
        $hostname = gethostbyaddr($_SERVER["REMOTE_ADDR"]);

        $insertcancel = array(
            "uid" => $user,
            "date" => $date,
            "trano" => $ponumber,
            "reason" => $reason,
            "ip" => $ip,
            "hostname" => $hostname
        );

        $this->requestcancel->insert($insertcancel);

        $return = array('success' => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

}

?>
