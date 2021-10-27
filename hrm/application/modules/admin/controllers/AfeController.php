<?php
class Admin_AfeController extends Zend_Controller_Action
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
    private $log;

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
        $this->afe = new Admin_Model_AFEn();
        $this->afes = new Admin_Model_AFESavingn();
        $this->afeh = new Admin_Model_AFEhn();
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Model_Workflow();
        $this->log = new Admin_Model_Logtransaction();
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
                $sql = "SELECT a.* FROM transengineer_afeh a LEFT JOIN transengineer_kboq3h b ON a.trano = b.afe_no WHERE b.afe_no IS NULL AND a.trano LIKE 'AFEN%'";
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

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_afeh WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' AND trano LIKE \'AFEN%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

               $afe[$key]['qty'] = $val['qtybaru'];
               $afe[$key]['price'] = $val['hargabaru'];
               $afe[$key]['priceori'] = $val['harga'];
               $afe[$key]['qtyori'] = $val['qty'];
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

                $afes[$key]['totalPurchase'] = intval($po['qty']) + intval($asfdd['qty']);
                $afes[$key]['totalPR'] = intval($pr['qty']) + (intval($arf['qty'])-intval($asfcancel['qty']));
                if ($afes[$key]['val_kode'] == 'IDR')
                {
                    $afes[$key]['totalPricePurchase'] = intval($po['totalIDR']) + intval($asfdd['totalIDR']);
                    $afes[$key]['totalPricePR'] = intval($pr['totalIDR'])+(intval($arf['totalIDR'])-intval($asfcancel['totalIDR']));
                }
                else
                {

                    $afes[$key]['totalPricePurchase'] = intval($po['totalUSD']) + intval($asfdd['totalUSD']);
                    $afes[$key]['totalPricePR'] = intval($pr['totalUSD'])+(intval($arf['totalUSD'])-intval($asfcancel['totalUSD']));
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
	   		$etc = str_replace("\\","",$etc);
	   		Zend_Loader::loadClass('Zend_Json');
	   		$jsonData = Zend_Json::decode($json);
	       	$jsonData2 = Zend_Json::decode($etc);
            $jsonData3 = Zend_Json::decode($json2);

            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $cus_kode = $cusKode[0]['cus_kode'];
            $cus_nama = $cusKode[0]['cus_kode'];

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
            if($jsonData2[0]['val_kode'] == 'IDR')
            {
               $totalOri = $boq2['total'];
               $totalCurrent = $totalOri + $boq2['totaltambah'];
               $totalBoq3_ori = $boq3_ori['totalIDR'];
               $totalBoq3_current = $boq3_current['totalIDR'];
               $totalMip = $mip['mip_currentIDR'];
            }
           else
           {
               $totalOri = $boq2['totalusd'];
               $totalCurrent = $totalOri + $boq2['totaltambahusd'];
               $totalBoq3_ori = $boq3_ori['totalUSD'];
               $totalBoq3_current = $boq3_current['totalUSD'];
               $totalMip = $mip['mip_currentUSD'];
           }

               $jsonData2[0]['cus_kode'] = $cus_kode;
               $jsonData2[0]['cus_nama'] = $cus_nama;
               $jsonData2[0]['boq2_ori'] = $totalOri;
               $jsonData2[0]['boq2_current'] = $totalCurrent;
               $jsonData2[0]['boq3_ori'] = $totalBoq3_ori;
               $jsonData2[0]['boq3_current'] = $totalBoq3_current;
               $jsonData2[0]['mip'] = $totalMip;

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
   				if ($user)
   				{
	   				$id = $docs['workflow_trans_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];
	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
		   			$afe = $this->afe->fetchAll("trano = '$approve'")->toArray();
                    $afes = $this->afes->fetchAll("trano = '$approve'")->toArray();
		   			$afeh = $this->afeh->fetchRow("trano = '$approve'");
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

                          $afe[$key]['qty'] = intval($val['qtybaru']);
                          $afe[$key]['price'] = intval($val['hargabaru']);
                          $afe[$key]['priceori'] = intval($val['harga']);
                          $afe[$key]['qtyori'] = intval($val['qty']);

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

                            $afes[$key]['qty'] = intval($val['qtybaru']);
                            $afes[$key]['price'] = intval($val['hargabaru']);
                            $afes[$key]['priceori'] = intval($val['harga']);
                            $afes[$key]['qtyori'] = intval($val['qty']);

		   				}
                    }

                        $cusKode = $this->project->getProjectAndCustomer($afeh['prj_kode']);
                        $cus_kode = $cusKode[0]['cus_kode'];
                        $cus_nama = $cusKode[0]['cus_kode'];

                        $jsonData2[0]["add_rev"] = intval($afeh['addrevenue']);
                        $jsonData2[0]["totalpocustomer"] = intval($afeh['totalpocustomer']);

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

                           $totalOriIDR = intval($boq2['total']);
                           $totalCurrentIDR = $totalOriIDR + intval($boq2['totaltambah']);
                           $totalBoq3_oriIDR = intval($boq3_ori['totalIDR']);
                           $totalBoq3_currentIDR = intval($boq3_current['totalIDR']);
                           $totalMipIDR = intval($mip['mip_currentIDR']);

                           $totalOriUSD = intval($boq2['totalusd']);
                           $totalCurrentUSD = intval($totalOriUSD + $boq2['totaltambahusd']);
                           $totalBoq3_oriUSD = intval($boq3_ori['totalUSD']);
                           $totalBoq3_currentUSD = intval($boq3_current['totalUSD']);
                           $totalMipUSD = intval($mip['mip_currentUSD']);

                           $totalMip = intval($mip['mip_current']);

                           $jsonData2[0]['cus_kode'] = intval($cus_kode);
                           $jsonData2[0]['cus_nama'] = intval($cus_nama);

                           $jsonData2[0]['boq2_oriIDR'] = intval($totalOriIDR);
                           $jsonData2[0]['boq2_currentIDR'] = intval($totalCurrentIDR);
                           $jsonData2[0]['boq3_oriIDR'] = intval($totalBoq3_oriIDR);
                           $jsonData2[0]['boq3_currentIDR'] = intval($totalBoq3_currentIDR);
//                           $jsonData2[0]['mipIDR'] = intval($totalMipIDR);

                           $jsonData2[0]['boq2_oriUSD'] = intval($totalOriUSD);
                           $jsonData2[0]['boq2_currentUSD'] = intval($totalCurrentUSD);
                           $jsonData2[0]['boq3_oriUSD'] = intval($totalBoq3_oriUSD);
                           $jsonData2[0]['boq3_currentUSD'] = intval($totalBoq3_currentUSD);
//                           $jsonData2[0]['mipUSD'] = intval($totalMipUSD);

                            $jsonData2[0]['mip'] = intval($totalMip);


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
   	   $etc = str_replace("\\","",$etc);
//       $jsonData = Zend_Json::decode($this->json);
       $jsonData = Zend_Json::decode($json);
       $jsonData2 = Zend_Json::decode($json2);
       $jsonEtc = Zend_Json::decode($etc);


       $counter = new Default_Models_MasterCounter();

	   $lastTrans = $counter->getLastTrans('AFEN');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'AFEN-' . $last;

//       $result = $this->workflow->setWorkflowTrans($trano,'AFE', '', $this->const['DOCUMENT_SUBMIT']);
//       $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//       if (is_numeric($result))
//       {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//       }

	   $where = "id=".$lastTrans['id'];
       $counter->update(array("urut" => $last),$where);
       $urut = 1;
       $urut2 = 1;

       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));
        $log['detail-add'] = array();
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
                    "is_workflow" => 'N'
                );
                $urut++;

                $this->afe->insert($arrayInsert);
                $log['detail-add'][] = $arrayInsert;
          }
       }


      if($jsonData2)
      {
        $log['detail-saving'] = array();
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
                    "is_workflow" => 'N'
                );
                $urut2++;
                $this->afes->insert($arrayInsert);
                $log['detail-saving'][] = $arrayInsert;
          }
      }

            $log['header'] = array();
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
                "jam" => time('H:i:s'),
                "totalpocustomer" => $jsonEtc[0]['totalpocustomer'],
                "pocustomer" => $jsonEtc[0]['pocustomer'],
                "addrevenue" => $jsonEtc[0]['add_rev'],
                "margin" => $jsonEtc[0]['margin'],
				"is_workflow" => 'N'
				//"cus_kode" => $cusKode,
            );
            $this->afeh->insert($arrayInsert);
            $log['header'][] = $arrayInsert;
			$this->getResponse()->setBody("{success: true, number : '$trano'}");
      Zend_Loader::loadClass('Zend_Json');
      $jsonLog = Zend_Json::encode($log);  
      $arrayLog = array (
          "trano" => $trano,
          "uid" => $this->session->userName,
          "tgl" => date('Y-m-d H:i:s'),
          "prj_kode" => $jsonEtc[0]['prj_kode'],
          "sit_kode" => $jsonEtc[0]['sit_kode'],
          "action" => "INSERT",
          "data_after" => $jsonLog,
          "ip" => $_SERVER["REMOTE_ADDR"],
          "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
      );
      $this->log->insert($arrayLog);  

    }

    public function updateafeAction()
    {
       $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $json = $this->getRequest()->getParam('posts');
       $etc = $this->getRequest()->getParam('etc');
       $json2 = $this->getRequest()->getParam('posts2');
   	   $etc = str_replace("\\","",$etc);
       $jsonData = Zend_Json::decode($json);
       $jsonData2 = Zend_Json::decode($json2);
       $jsonEtc = Zend_Json::decode($etc);

       $tgl= date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

       $trano = $jsonEtc[0]['trano'];

//       $result = $this->workflow->setWorkflowTrans($trano,'AFE','',$this->const['DOCUMENT_RESUBMIT']);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        if (is_numeric($result))
//        {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//        }
        $temp = array();
       if($jsonData)
       {
           $log['detail-add'] = array();
      foreach($jsonData as $key => $val)
      {

          $arrayInsert = array(

				"tgl" => date('Y-m-d'),												
                "ket" => $val['ket'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
                "qtybaru" => $val['qty'],
                "hargabaru" => $val['price'],
                "cfs_kode" => $val['cfs_kode'],

				"cfs_nama" => $val['cfs_nama'],
                "val_kode" => $jsonEtc[0]['val_kode']
			);
            $workid = $val['workid'];
            $kodeBrg = $val['kode_brg'];

        $cekRow = $this->afe->fetchRow($arrayInsert,"trano = '$trano' AND workid = '$workid' AND kode_brg = '$kodeBrg'");

        if ($cekRow)
        {
            $log['detail-add-before'][] = $cekRow->toArray();
            $log2['detail-add-after'][] = $arrayInsert;
        }

        $this->afe->update($arrayInsert,"trano = '$trano' AND workid = '$workid' AND kode_brg = '$kodeBrg'");
      }
       }

       if($jsonData2)
      {
      foreach($jsonData2 as $key => $val)
      {
          $arrayInsert = array(
				"tgl" => date('Y-m-d'),
                "ket" => $val['ket'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
                "qtybaru" => $val['qty'],
                "hargabaru" => $val['price'],
                "cfs_kode" => $val['cfs_kode'],
				"cfs_nama" => $val['cfs_nama'],
                "val_kode" => $jsonEtc[0]['val_kode']

			);
            $workid2 = $val['workid'];
            $kodeBrg2 = $val['kode_brg'];
            $cekRow = $this->afes->fetchRow($arrayInsert,"trano = '$trano' AND workid = '$workid2' AND kode_brg = '$kodeBrg2'");
            if ($cekRow)
            {
                $log['detail-saving-before'][] = $cekRow->toArray();
                $log2['detail-saving-after'][] = $arrayInsert;
            }
            $this->afes->update($arrayInsert,"trano = '$trano' AND workid = '$workid2' AND kode_brg = '$kodeBrg2'");

      }
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
                "jam" => time('H:i:s'),
                "totalpocustomer" => $jsonEtc[0]['totalpocustomer'],
                "pocustomer" => $jsonEtc[0]['pocustomer'],
                "addrevenue" => $jsonEtc[0]['add_rev'],
                "margin" => $jsonEtc[0]['margin'],
            );
            $cekRow = $this->afeh->fetchRow($arrayInsert,"trano = '$trano'");
            if ($cekRow)
            {
                $log['header-before'][] = $cekRow->toArray();
                $log2['header-after'][] = $arrayInsert;
            }
             $this->afeh->delete("trano = '$trano'");
             $this->afeh->insert($arrayInsert);
             Zend_Loader::loadClass('Zend_Json');
              $jsonLog = Zend_Json::encode($log);
              $jsonLog2 = Zend_Json::encode($log2); 
              $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $jsonEtc[0]['prj_kode'],
                  "sit_kode" => $jsonEtc[0]['sit_kode'],
                  "action" => "EDIT",
                  "data_after" => $jsonLog2,
                  "data_before" => $jsonLog,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
              );
              $this->log->insert($arrayLog);
             $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }
    
}
?>