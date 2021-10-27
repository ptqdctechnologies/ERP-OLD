<?php
class ProjectManagement_AfeController extends Zend_Controller_Action
{
    private $db;
    private $session;
    private $request;
    private $const;
    private $projectHelper;
    private $project;
    private $trans;
    private $util;
    private $barang;
    private $workflow;
    private $workflowTrans;
    private $workflowClass;
    private $error;
    private $afe;
    private $afeh;
    private $afes;
    private $budget;
    private $quantity;
    private $log;
    private $files;

    public function init()
    {        
        $this->db = Zend_Registry::get('db');
         $this->request = $this->getRequest();
        $this->const = Zend_Registry::get('constant');
        //$this->leadHelper = $this->_helper->getHelper('chart');
		$this->workflow = $this->_helper->getHelper('workflow');
//		$this->budget = $this->_helper->getHelper('budget');
        $this->budget = new Default_Models_Budget();
        $this->session = new Zend_Session_Namespace('login');
		$this->error = $this->_helper->getHelper('error');
        $this->projectHelper = $this->_helper->getHelper('project');
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->trans = $this->_helper->getHelper('transaction');
        $this->afe = new ProjectManagement_Models_AFE();
        $this->afes = new ProjectManagement_Models_AFESaving();
        $this->afeh = new ProjectManagement_Models_AFEh();
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Model_Workflow();
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->log = new Admin_Model_Logtransaction();
        $this->files = new Default_Models_Files();
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
            case 'AFE':
                $sql = "SELECT a.* FROM transengineer_afeh a LEFT JOIN transengineer_kboq3h b ON a.trano = b.afe_no WHERE b.afe_no IS NULL AND a.trano LIKE 'AFE-%'";
                $fetch = $this->db->query($sql);
                $return['posts'] = $fetch->fetchAll();                
                $return['count'] = count($return['posts']);
//                $return['posts'] = $this->afeh->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
//                $return['count'] = $this->afeh->fetchAll()->count();
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
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_afeh WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' AND trano LIKE \'AFE-%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function afeAction()
    {
        
    }

    public function addafeAction()
    {
        $isCancel = $this->getRequest()->getParam("returnback");
   		if ($isCancel)
   		{
   			$this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
   			$this->view->etc = $this->getRequest()->getParam("etc");
   		}
    }

    public function editafeAction()
    {
        $trano = $this->getRequest()->getParam("trano");


   		$afe = $this->afe->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $afes = $this->afes->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
   		$afeh = $this->afeh->fetchRow("trano = '$trano'");

        if ($afe)
   		{
   			foreach($afe as $key => $val)
   			{
   				$afe[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$prjKode = $val['prj_kode'];
   				$sitKode = $val['sit_kode'];
   				$workid = $val['workid'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");

                foreach ($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $afe[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $afe[$key][$key2] = str_replace("\""," inch",$afe[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $afe[$key][$key2] = str_replace("'"," inch",$afe[$key][$key2]);
                }

   				if ($barang)
   				{
   					$afe[$key]['uom'] = $barang['sat_kode'];
   				}
                $cekExist = $this->budget->getBoq3ByOne($prjKode,$sitKode,$workid,$kodeBrg);

                if (!$cekExist)
                {
                    $afe[$key]['type'] = "new";
                }
               else
                    $afe[$key]['type'] = "additional";

               $pr = $this->quantity->getPrQuantityLast($prjKode,$sitKode,$workid,$kodeBrg);

               if ($pr != '')
               {
                   $harga = $pr['harga'];
                   $qty = $pr['qty'];
               }
               else
               {
                   $harga = floatval($val['harga']);
                   $qty = floatval($val['qty']);
               }
               $afe[$key]['qty'] = $val['qtybaru'];
               $afe[$key]['price'] = $val['hargabaru'];
               $afe[$key]['priceori'] = $harga;
               $afe[$key]['qtyori'] = $qty;
   			}
               
        }
        else
            $afe = array();

        if ($afes)
   		{
   			foreach($afes as $key => $val)
   			{
   				$afes[$key]['id'] = $key + 1;
   				$kodeBrg = $val['kode_brg'];
   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                foreach ($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $afes[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $afes[$key][$key2] = str_replace("\""," inch",$afes[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $afes[$key][$key2] = str_replace("'"," inch",$afes[$key][$key2]);
                }
   				if ($barang)
   				{
   					$afes[$key]['uom'] = $barang['sat_kode'];
   				}

                $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$workid,$kodeBrg);
                if ($po == '')
                {
                    $po['qty'] = 0;
                    $po['totalIDR'] = 0;
                    $po['totalUSD'] = 0;
                }
                $asfdd = $this->quantity->getAsfddQuantity($prjKode,$sitKode,$workid,$kodeBrg);
                 if ($asfdd == '')
                {
                    $asfdd['qty'] = 0;
                    $asfdd['totalIDR'] = 0;
                    $asfdd['totalUSD'] = 0;
                }
                $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid,$kodeBrg);
                if ($arf == '')
                {
                    $arf['qty'] = 0;
                    $arf['totalIDR'] = 0;
                    $arf['totalUSD'] = 0;
                }
                $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$workid,$kodeBrg);
                 if ($asfcancel == '')
                {
                    $asfcancel['qty'] = 0;
                    $asfcancel['totalIDR'] = 0;
                    $asfcancel['totalUSD'] = 0;
                }
                $pr = $this->quantity->getPrQuantity($prjKode,$sitKode,$workid,$kodeBrg);
                 if ($pr == '')
                {
                    $pr['qty'] = 0;
                    $pr['totalIDR'] = 0;
                    $pr['totalUSD'] = 0;
                }
                $pmeal = $this->quantity->getPmealQuantity($prjKode,$sitKode,$kodeBrg);
                 if ($pmeal == '')
                {
                    $pmeal['qty'] = 0;

                }

                $afes[$key]['totalPurchase'] = floatval($po['qty']) + floatval($asfdd['qty']);
                $afes[$key]['totalPR'] = floatval($pmeal['qty']) + floatval($pr['qty']) + (floatval($arf['qty'])-floatval($asfcancel['qty']));
                if ($afes[$key]['val_kode'] == 'IDR')
                {
                    $afes[$key]['totalPricePurchase'] = floatval($po['totalIDR']) + floatval($asfdd['totalIDR']);
                    $afes[$key]['totalPricePR'] = floatval($pr['totalIDR'])+(floatval($arf['totalIDR'])-floatval($asfcancel['totalIDR']));
                }
                else
                {

                    $afes[$key]['totalPricePurchase'] = floatval($po['totalUSD']) + floatval($asfdd['totalUSD']);
                    $afes[$key]['totalPricePR'] = floatval($pr['totalUSD'])+(floatval($arf['totalUSD'])-floatval($asfcancel['totalUSD']));
                }

               $afes[$key]['type'] = "saving";
               $afes[$key]['qty'] = $val['qtybaru'];
               $afes[$key]['price'] = $val['hargabaru'];
               $afes[$key]['priceori'] = $val['harga'];
               $afes[$key]['qtyori'] = $val['qty'];

   			}
        }
        else
        {
            $afes = array();
        }

            Zend_Loader::loadClass('Zend_Json');
   			$jsonData = Zend_Json::encode($afe);
   			$jsonData2 = Zend_Json::encode($afes);

   			$isCancel = $this->getRequest()->getParam("returnback");
	   		
	   		if ($isCancel)
	   		{
	   			$this->view->cancel = true;
	   			$this->view->json = $this->getRequest()->getParam("posts");
	   		}
	   		else
            {
                $this->view->json2 = $jsonData2;  
	       		$this->view->json = $jsonData;
            }

	       	$this->view->trano = $trano;
	       	$this->view->tgl = $afeh['tgl'];

	       	$this->view->ket = $afeh['ket'];
	       	$this->view->prjKode = $afeh['prj_kode'];
	       	$this->view->prjNama = $afeh['prj_nama'];
	       	$this->view->sitKode = $afeh['sit_kode'];
       	    $this->view->sitNama = $afeh['sit_nama'];
            $this->view->valKode = $afeh['val_kode'];
             $this->view->addRev = $afeh['addrevenue'];
             $this->view->pocustomer = $afeh['pocustomer'];
             $this->view->totalpocustomer = $afeh['totalpocustomer'];
            $this->view->rateidr = $afeh['rateidr'];
    }

    public function appafeAction()
    {
        $type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/AFE';
   		else
   			$this->view->urlBack = '/default/home/showprocessdocument';

   		$approve = $this->getRequest()->getParam("approve");
   		if ($approve == '')
   		{
	   		$json = $this->getRequest()->getParam("posts");
	   		$etc = $this->getRequest()->getParam("etc");
            $json2 = $this->getRequest()->getParam("posts2");
            $files = $this->getRequest()->getParam("file");
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);
            $jsonData3 = Zend_Json::decode($json2);
            $file = Zend_Json::decode($files);

            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $cus_kode = $cusKode[0]['cus_kode'];
            $cus_nama = $cusKode[0]['cus_kode'];

            if ($jsonData2[0]['rateidr'] == '' || $jsonData2[0]['rateidr'] == 0)
            {
               $utility = $this->_helper->getHelper('utility');
               $jsonData2[0]['rateidr'] = $utility->getExchangeRate(); 
            }

            $jsonData2[0]["add_rev"] = intval($jsonData2[0]["add_rev"]);
            $jsonData2[0]["totalpocustomer"] = intval($jsonData2[0]["totalpocustomer"]);

            $stsoverhead = $this->trans->getSiteOverhead($jsonData2[0]['sit_kode'],$jsonData2[0]['prj_kode']);
            $stsoverhead = $stsoverhead['stsoverhead'];

            if($stsoverhead == 'Y')
            {
                $this->view->stsoverhead = true;
            }

            $boq2 = $this->trans->getKboq2($jsonData2[0]['prj_kode'],$jsonData2[0]['sit_kode']);

            $boq3_ori = $this->budget->getBoq3('summary-ori',$jsonData2[0]['prj_kode'],$jsonData2[0]['sit_kode']);
            $boq3_current = $this->budget->getBoq3('summary-current',$jsonData2[0]['prj_kode'],$jsonData2[0]['sit_kode']);
            $mip = $this->budget->getMIP($jsonData2[0]['prj_kode'],$jsonData2[0]['sit_kode']);

               $totalOriIDR = $boq2['total'];
               $totalCurrentIDR = $totalOriIDR + $boq2['totaltambah'];
               $totalBoq3_oriIDR = $boq3_ori['totalIDR'];
               $totalBoq3_currentIDR = $boq3_current['totalIDR'];
               $totalMipIDR = $mip['mip_currentIDR'];



               $totalOriUSD = $boq2['totalusd'];
               $totalCurrentUSD = $totalOriUSD + $boq2['totaltambahusd'];
               $totalBoq3_oriUSD = $boq3_ori['totalHargaUSD'];
               $totalBoq3_currentUSD = $boq3_current['totalHargaUSD'];
               $totalMipUSD = $mip['mip_currentUSD'];

               $totalMip = $mip['mip_current'];

               $jsonData2[0]['cus_kode'] = $cus_kode;
               $jsonData2[0]['cus_nama'] = $cus_nama;
               $jsonData2[0]['boq2_oriIDR'] = $totalOriIDR;
               $jsonData2[0]['boq2_currentIDR'] = $totalCurrentIDR;
         
               $jsonData2[0]['boq3_oriIDR'] = $totalBoq3_oriIDR;
               $jsonData2[0]['boq3_currentIDR'] = $totalBoq3_currentIDR;
               $jsonData2[0]['mipIDR'] = $totalMipIDR;
               $jsonData2[0]['boq2_oriUSD'] = $totalOriUSD;
               $jsonData2[0]['boq2_currentUSD'] = $totalCurrentUSD;
               $jsonData2[0]['boq3_oriUSD'] = $totalBoq3_oriUSD;
               $jsonData2[0]['boq3_currentUSD'] = $totalBoq3_currentUSD;
               $jsonData2[0]['mipUSD'] = $totalMipUSD;

               $jsonData2[0]['mip'] = $totalMip;

	       	$this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
            $this->view->result2 = $jsonData3;
	       	$this->view->jsonResult = $json;
            $this->view->jsonResult2 = $json2;
            $this->view->file = $file;
            $this->view->jsonFile = $files;

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
		   			$afe = $this->afe->fetchAll("trano = '$approve'")->toArray();
                    $afes = $this->afes->fetchAll("trano = '$approve'")->toArray();
		   			$afeh = $this->afeh->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");
		   			if ($afe)
		   			{
		   				foreach($afe as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$afe[$key]['uom'] = $barang['sat_kode'];
		   					}

                          $afe[$key]['qty'] = floatval($val['qtybaru']);
                          $afe[$key]['price'] = floatval($val['hargabaru']);
                          $afe[$key]['priceori'] = floatval($val['harga']);
                          $afe[$key]['qtyori'] = floatval($val['qty']);

		   				}
                    }
                   	if ($afes)
		   			{
		   				foreach($afes as $key => $val)
		   				{
		   					$kodeBrg = $val['kode_brg'];
		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
		   					if ($barang)
		   					{
		   						$afes[$key]['uom'] = $barang['sat_kode'];
		   					}

                            $afes[$key]['qty'] = floatval($val['qtybaru']);
                            $afes[$key]['price'] = floatval($val['hargabaru']);
                            $afes[$key]['priceori'] = floatval($val['harga']);
                            $afes[$key]['qtyori'] = floatval($val['qty']);

		   				}
                    }

                        $cusKode = $this->project->getProjectAndCustomer($afeh['prj_kode']);
                        $cus_kode = $cusKode[0]['cus_kode'];
                        $cus_nama = $cusKode[0]['cus_kode'];

                        $jsonData2[0]["add_rev"] = floatval($afeh['addrevenue']);
                        $jsonData2[0]["totalpocustomer"] = floatval($afeh['totalpocustomer']);

                        $stsoverhead = $this->trans->getSiteOverhead($afeh['sit_kode'],$afeh['prj_kode']);
                        $stsoverhead = $stsoverhead['stsoverhead'];
                      
                        if($stsoverhead == 'Y')
                        {
                            $this->view->stsoverhead = true;
                        }
                
                        $boq2 = $this->trans->getKboq2( $afeh['prj_kode'],$afeh['sit_kode']);
                        $boq3_ori = $this->budget->getBoq3('summary-ori',$afeh['prj_kode'],$afeh['sit_kode']);
                        $boq3_current = $this->budget->getBoq3('summary-current',$afeh['prj_kode'],$afeh['sit_kode']);
                        $mip = $this->budget->getMIP($afeh['prj_kode'],$afeh['sit_kode']);

                           $totalOriIDR = floatval($boq2['total']);
                           $totalCurrentIDR = $totalOriIDR + floatval($boq2['totaltambah']);
                           $totalBoq3_oriIDR = floatval($boq3_ori['totalIDR']);
                           $totalBoq3_currentIDR = floatval($boq3_current['totalIDR']);
                           $totalMipIDR = floatval($mip['mip_currentIDR']);

                           $totalOriUSD = floatval($boq2['totalusd']);
                           $totalCurrentUSD = floatval($totalOriUSD + $boq2['totaltambahusd']);
                           $totalBoq3_oriUSD = floatval($boq3_ori['totalHargaUSD']);
                           $totalBoq3_currentUSD = floatval($boq3_current['totalHargaUSD']);
                           $totalMipUSD = floatval($mip['mip_currentUSD']);

                           $totalMip = floatval($mip['mip_current']);

                           $jsonData2[0]['cus_kode'] = intval($cus_kode);
                           $jsonData2[0]['cus_nama'] = intval($cus_nama);
                       
                           $jsonData2[0]['boq2_oriIDR'] = floatval($totalOriIDR);
                           $jsonData2[0]['boq2_currentIDR'] = floatval($totalCurrentIDR);
                           $jsonData2[0]['boq3_oriIDR'] = floatval($totalBoq3_oriIDR);
                           $jsonData2[0]['boq3_currentIDR'] = floatval($totalBoq3_currentIDR);
//                           $jsonData2[0]['mipIDR'] = intval($totalMipIDR);

                           $jsonData2[0]['boq2_oriUSD'] = floatval($totalOriUSD);
                           $jsonData2[0]['boq2_currentUSD'] = floatval($totalCurrentUSD);
                           $jsonData2[0]['boq3_oriUSD'] = floatval($totalBoq3_oriUSD);
                           $jsonData2[0]['boq3_currentUSD'] = floatval($totalBoq3_currentUSD);
//                           $jsonData2[0]['mipUSD'] = intval($totalMipUSD);

                            $jsonData2[0]['mip'] = floatval($totalMip);


		   				$jsonData2[0]['prj_kode'] = $afeh['prj_kode'];
		   				$jsonData2[0]['prj_nama'] = $afeh['prj_nama'];
		   				$jsonData2[0]['sit_kode'] = $afeh['sit_kode'];
		   				$jsonData2[0]['sit_nama'] = $afeh['sit_nama'];
		   				$jsonData2[0]['ket'] = $afeh['ket'];
		   				$jsonData2[0]['add_rev'] = $afeh['addrevenue'];
                        $jsonData2[0]['val_kode'] = $afeh['val_kode'];

                        $jsonData2[0]['pocustomer'] = $afeh['pocustomer'];
                        $jsonData2[0]['totalpocustomer'] = $afeh['totalpocustomer'];
                        $jsonData2[0]['user'] = $afeh['user'];
                        $jsonData2[0]['rateidr'] = $afeh['rateidr'];

//		   				$jsonData2[0]['payterm'] = $asfh['paymentterm'];

				        $jsonData2[0]['trano'] = $approve;
                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;

                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;   
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $afe;
                        $this->view->result2 = $afes;
		   				$this->view->approve = true;
		   				$this->view->uid = $this->session->userName;
		   				$this->view->userID = $this->session->idUser;
		   				$this->view->docsID = $id;
                        $this->view->file = $file;

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

    public function insertafeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $json = $this->getRequest()->getParam('posts');
       $etc = $this->getRequest()->getParam('etc');
       $json2 = $this->getRequest()->getParam('posts2');
      $file = $this->getRequest()->getParam('file');
   	   $etc = str_replace("\\","",$etc);
//       $jsonData = Zend_Json::decode($this->json);
       $jsonData = Zend_Json::decode($json);
       $jsonData2 = Zend_Json::decode($json2);
       $jsonEtc = Zend_Json::decode($etc);
      $jsonFile = Zend_Json::decode($file);

//        var_dump($jsonData);die;

       $counter = new Default_Models_MasterCounter();

	   $lastTrans = $counter->getLastTrans('AFE');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'AFE-' . $last;

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id'); 

       $result = $this->workflow->setWorkflowTrans($trano,'AFE', '', $this->const['DOCUMENT_SUBMIT'],$items);
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

       if($jsonData)
       {
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
                "ket" => $val['ket'],
                "val_kode" => $jsonEtc[0]['val_kode'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"qty" => $val['qtyori'],
                "qtybaru" => $val['qty'],
				"harga" => $val['priceori'],
                "hargabaru" => $val['price'],
				"totalpocustomer" => $val['totalpocustomer'],
                "pocustomer" => $val['pocustomer'],

//				"petugas" => $this->session->userName,
                "cfs_kode" => $val['cfs_kode'],

				"cfs_nama" => $val['cfs_nama'],
              "rateidr" => $jsonEtc[0]['rateidr']

			);
            $urut++;

            $this->afe->insert($arrayInsert);
      }
       }


      if($jsonData2)
      {
      foreach($jsonData2 as $key => $val)
      {
           $arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
                "ket" => $val['ket'],
                "val_kode" => $jsonEtc[0]['val_kode'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"qty" => $val['qtyori'],
                "qtybaru" => $val['qty'],
				"harga" => $val['priceori'],
                "hargabaru" => $val['price'],
				"totalpocustomer" => $val['totalpocustomer'],
                "pocustomer" => $val['pocustomer'],

//				"petugas" => $this->session->userName,
                "cfs_kode" => $val['cfs_kode'],

				"cfs_nama" => $val['cfs_nama'],
               "rateidr" => $jsonEtc[0]['rateidr'],
			);
            $urut2++;
            $this->afes->insert($arrayInsert);
      }
      }
        if ($jsonEtc[0]['rateidr'] == '' || $jsonEtc[0]['rateidr'] == 0)
        {
           $utility = $this->_helper->getHelper('utility');
           $jsonEtc[0]['rateidr'] = $utility->getExchangeRate(); 
        }
        	$arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),

				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],

        		"ket" => $jsonEtc[0]['ket'],
				"val_kode" => $jsonEtc[0]['val_kode'],        		
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
                "totalpocustomer" => $jsonEtc[0]['totalpocustomer'],
                "pocustomer" => $jsonEtc[0]['pocustomer'],
                "addrevenue" => $jsonEtc[0]['add_rev'],
                "margin" => -1 * floatval($jsonEtc[0]['margin']),
                "margin_last" => floatval($jsonEtc[0]['margin2']),
                "rateidr" => $jsonEtc[0]['rateidr'],
				//"cus_kode" => $cusKode,
            );
            $this->afeh->insert($arrayInsert);
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

    public function updateafeAction()
    {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $json = $this->getRequest()->getParam('posts');
       $etc = $this->getRequest()->getParam('etc');
       $json2 = $this->getRequest()->getParam('posts2');
      $file = $this->getRequest()->getParam('file');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($json);
       $jsonData2 = Zend_Json::decode($json2);
       $jsonEtc = Zend_Json::decode($etc);
      $jsonFile = Zend_Json::decode($file);

       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

       $trano = $jsonEtc[0]['trano'];
        $urut = 1;
        $urut2 = 1;

       $items = $jsonEtc[0];
       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
       $result = $this->workflow->setWorkflowTrans($trano,'AFE','',$this->const['DOCUMENT_RESUBMIT'],$items);
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
           $log['afe-add-detail-before'] = array();
            $fetch = $this->afe->fetchAll("trano = '$trano'");
           if ($fetch)
           {
               $fetch = $fetch->toArray();
               $log['afe-add-detail-before'] = $fetch;
           }
            $this->afe->delete("trano = '$trano'");
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
                "ket" => $val['ket'],
                "val_kode" => $jsonEtc[0]['val_kode'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"qty" => $val['qtyori'],
                "qtybaru" => $val['qty'],
				"harga" => $val['priceori'],
                "hargabaru" => $val['price'],
				"totalpocustomer" => $val['totalpocustomer'],
                "pocustomer" => $val['pocustomer'],

//				"petugas" => $this->session->userName,
                "cfs_kode" => $val['cfs_kode'],

				"cfs_nama" => $val['cfs_nama'],
              "rateidr" => $jsonEtc[0]['rateidr']

			);

            $log['afe-add-detail-after'][] = $arrayInsert;
            $this->afe->insert($arrayInsert);
      }
       $urut++;
       }

       if($jsonData2)
      {
          $log['afe-save-detail-before'] = array();
            $fetch = $this->afe->fetchAll("trano = '$trano'");
           if ($fetch)
           {
               $fetch = $fetch->toArray();
               $log['afe-save-detail-before'] = $fetch;
           }
       $this->afes->delete("trano = '$trano'");
      foreach($jsonData2 as $key => $val)
      {
          $arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $jsonEtc[0]['prj_kode'],
				"prj_nama" => $jsonEtc[0]['prj_nama'],
				"sit_kode" => $jsonEtc[0]['sit_kode'],
				"sit_nama" => $jsonEtc[0]['sit_nama'],
                "ket" => $val['ket'],
                "val_kode" => $jsonEtc[0]['val_kode'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"qty" => $val['qtyori'],
                "qtybaru" => $val['qty'],
				"harga" => $val['priceori'],
                "hargabaru" => $val['price'],
				"totalpocustomer" => $val['totalpocustomer'],
                "pocustomer" => $val['pocustomer'],

//				"petugas" => $this->session->userName,
                "cfs_kode" => $val['cfs_kode'],

				"cfs_nama" => $val['cfs_nama'],
               "rateidr" => $jsonEtc[0]['rateidr']

			);

            $log['afe-save-detail-after'][] = $arrayInsert;
            $this->afes->insert($arrayInsert);

      }
      }
        if ($jsonEtc[0]['rateidr'] == '' || $jsonEtc[0]['rateidr'] == 0)
        {
           $utility = $this->_helper->getHelper('utility');
           $jsonEtc[0]['rateidr'] = $utility->getExchangeRate();
        }
            $result = $this->afeh->fetchRow("trano = '$trano'");
            if ($result)
            {
                $result = $result->toArray();
                $log['afe-header-before'] = $result;
            }
        	$arrayInsert = array (
//            	"trano" => $trano,
//				"tgl" => date('Y-m-d'),
//
//				"prj_kode" => $jsonEtc[0]['prj_kode'],
//				"prj_nama" => $jsonEtc[0]['prj_nama'],
//				"sit_kode" => $jsonEtc[0]['sit_kode'],
//				"sit_nama" => $jsonEtc[0]['sit_nama'],

        		"ket" => $jsonEtc[0]['ket'],
				"val_kode" => $jsonEtc[0]['val_kode'],
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => time('H:i:s'),
                "totalpocustomer" => $jsonEtc[0]['totalpocustomer'],
                "pocustomer" => $jsonEtc[0]['pocustomer'],
                "addrevenue" => $jsonEtc[0]['add_rev'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "margin" => -1 * floatval($jsonEtc[0]['margin']),
                "margin_last" => floatval($jsonEtc[0]['margin2']),
				//"cus_kode" => $cusKode,
            );
            $this->afeh->update($arrayInsert,"trano = '$trano'");
           
            $result = $this->afeh->fetchRow("trano = '$trano'")->toArray();
            $log2['afe-header-after'] = $result;

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

    public function getxmlafeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");
        $noData = $this->getRequest()->getParam("nodata");
   		if (!$noData)
        {
            $prd = $this->procurement->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
            if ($prd)
            {
                foreach($prd as $key => $val)
                {
                    foreach ($val as $key2 => $val2)
                    {
                        if ($val2 == '""' || $val2 == '')
                            unset($prd[$key][$key2]);
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

                    $prd[$key]['net_act'] = $val['myob'];
                    $prd[$key]['fromBoq3'] = 1;
                }

           }
            $jsonData = $prd;
        }
        else
        {
            $json = $this->getRequest()->getParam("posts");
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
        }

       $xmlOutput = $this->xml->getXml($jsonData);
       $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf-8');
       $this->getResponse()->setBody($xmlOutput);
    }

}
?>