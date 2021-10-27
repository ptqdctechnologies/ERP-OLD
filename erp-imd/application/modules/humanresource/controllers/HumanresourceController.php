<?php

class HumanResource_HumanresourceController extends Zend_Controller_Action
{
	private $db;
	private $session;
    private $upload;
    private $phpexcel;
    private $projectHelper;
    private $masterWork;
    private $masterBarang;
    private $workflow;
    private $workflowTrans;
    private $const;
    private $error;
    private $budget;

	
	public function init()
	{
        $this->db = Zend_Registry::get('db');
		Zend_Loader::loadClass('Zend_Json');
		$this->session = new Zend_Session_Namespace('login');
        $this->upload = $this->_helper->getHelper('uploadfile');
        $this->phpexcel = $this->_helper->getHelper('phpexcel');
        $this->projectHelper = $this->_helper->getHelper('project');
        $this->masterWork = new Default_Models_MasterWork();
        $this->masterBarang = new Default_Models_MasterBarang();
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->const = Zend_Registry::get('constant');
        $this->error = $this->_helper->getHelper('error');
        $this->budget = new Default_Models_Budget();
	}

    public function attendancelistAction()
    {
        
    }

    public function listohpAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');
        if ($columnName != '')
            $where = "WHERE tipe = '$columnName'";
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = "SELECT SQL_CALC_FOUND_ROWS trano,prj_kode,prj_nama,sit_kode,sit_nama,ket FROM procurement_praohpd Group By $sort ORDER BY $sort $dir LIMIT $offset , $limit";

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

    public function listohpbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');
        $tipe = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_praohpd WHERE $columnName LIKE '%$columnValue%' GROUP BY $sort ORDER BY $sort $dir LIMIT $offset,$limit";

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

    public function ohpAction()
    {
        
    }

    public function addohpAction()
    {
        
    }

    public function editohpAction()
    {
        $request = $this->getRequest();
        $trano = $request->getParam('trano');
        $tempOHP = new HumanResource_Models_TemporaryOHP();

        $data = $tempOHP->fetchAll("trano = '$trano'");

        $this->view->trano = $trano;

        $this->view->ket = $data[0]['ket'];
        
    }

    public function appohpAction()
   {
   		$type = $this->getRequest()->getParam("type");
   		$from = $this->getRequest()->getParam("from");
        $tempOHP = new HumanResource_Models_TemporaryOHP();
		$tempOHPh = new HumanResource_Models_TemporaryOHPH();

   		if ($type != '')
   			$this->view->urlBack = '/default/home/showprocessdocument/type/PRAOHP';
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

	       	$this->view->result = $jsonData;
	       	$this->view->etc = $jsonData2;
	       	$this->view->jsonResult = Zend_Json::encode($jsonData);

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
	   				$workflowId = $docs['workflow_id'];
	   				$approve = $docs['item_id'];
	   				$statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

	   				if ($statApprove == $this->const['DOCUMENT_REJECT'])
	   					$this->view->reject = true;
//                    $select = $tempOHP->select()
//                            ->where("trano = '$approve'")
//                            ->group(array("prj_kode"));
		   			$ohpd = $tempOHP->fetchAll("trano = '$approve'", array("prj_kode ASC","sit_kode ASC"))->toArray();
//		   			$prh = $this->procurementH->fetchRow("trano = '$approve'");
		   			if ($ohpd)
		   			{
//                           $arrays = array();
//                           $i = 0;
//                           foreach ($ohpd as $k => $v)
//                           {
//                               $arrays[$v['prj_kode']] = $v['prj_nama'];
//                               $arrays[$v['prj_kode']][$v['sit_kode']] = $v['sit_nama'];
//                               $arrays[$v['prj_kode']][$v['sit_kode']][] = array(
//                                   "workid" => $v['workid'],
//                                    "kode_brg" => $v['kode_brg'],
//                                    "total" => $v['total']
//                               );
//                               $i++;
//                           }
//		   				foreach($ohpd as $key => $val)
//		   				{
//		   					$kodeBrg = $val['kode_brg'];
//		   					$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
//		   					if ($barang)
//		   					{
//		   						$prd[$key]['uom'] = $barang['sat_kode'];
//		   					}
//		   					if ($val['val_kode'] == 'IDR')
//		   						$prd[$key]['hargaIDR'] = $val['harga'];
//		   					elseif ($val['val_kode'] == 'USD')
//		   						$prd[$key]['hargaUSD'] = $val['harga'];
//
//                            $prd[$key]['net_act'] = $val['myob'];
//		   				}

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;

				        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $ohpd['petugas'];
		                $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
				        $this->view->etc = $jsonData2;
		   				$this->view->result = $ohpd;
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

    public function uploadohpAction()
    {
    	$this->_helper->viewRenderer->setNoRender();

		$result = $this->upload->uploadFile($_FILES,'file-path');

		if ($result)
		{
			Zend_Loader::loadClass('Zend_Json');
			$boq3 = $this->phpexcel->readOhpFilePerRow($result['save_file'],$result['id_file']);
            foreach($boq3 as $k => $v)
            {
                if (!is_numeric($v['total']))
                {
                    $boq3[$k]['total'] = 0;
                }
            }
            $json =  Zend_Json::encode($boq3);
			$fields = array(array("name" => "id","type" => "string"),array("name" => "prj_kode","type" => "string"),array("name" => "prj_nama","type" => "string"),array("name" => "sit_kode","type" => "string"),array("name" => "sit_nama","type" => "string"),array("name" => "workid","type" => "string"),array("name" => "workname"),array("name" => "nama_brg"),array("name" => "kode_brg","type" => "string"),array( "name" => "total", "type" => "double"));
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

    public function submitohpAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	Zend_Loader::loadClass('Zend_Json');
    	$json = $this->getRequest()->getParam("posts");
//    	$rate = $this->getRequest()->getParam("rateidr");
    	$ket = $this->getRequest()->getParam("ket");
//    	$prjKode = $this->getRequest()->getParam("prj_kode");
//    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	$uid = $this->getRequest()->getParam("uid");
		$ohp =  Zend_Json::decode($json);
//		$rateidr =  Zend_Json::decode($rate);
		$ket =  Zend_Json::decode($ket);

		$tempOHP = new HumanResource_Models_TemporaryOHP();
		$tempOHPh = new HumanResource_Models_TemporaryOHPH();
		$counter = new Default_Models_MasterCounter();

        //Cek kalo sudah ada trano yg diinsert ke workflow_trans
        $tranoExist = $this->getRequest()->getParam('trano');
        $lastTrans = $counter->getLastTrans('PRAOHP');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'PRAOHP-' . $last;
        $where = "id=".$lastTrans['id'];
        if ($tranoExist != '' && $tranoExist != 'undefined')
        {
            $trano = $tranoExist;
            $last = substr($trano,7);
        }

//        $items = array(
//            "prj_kode" => $prjKode,
//            "sit_kode" => $sitKode
//        );
        $uid_next = $this->getRequest()->getParam('uid_next');
        $workflowItem = $this->getRequest()->getParam('workflow_item_id');
        $workflowItemtype = $this->getRequest()->getParam('workflow_item_type_id');
        $workflowId = $this->getRequest()->getParam('workflow_id');
       $t = array();
       $projek = array();
       $workError = array();
       $gagal = array();
       foreach($ohp as $k => $v)
       {
            $prjKode = $v['prj_kode'];
            $t[$prjKode][] = $v;
       }
        $bypass = false;
       foreach($t as $k => $v)
       {
           $cek = $this->workflowTrans->fetchRow("prj_kode = '$k' AND item_id = '$trano' AND approve=100");
           if ($cek)
           {
               unset($t[$k]);
               continue;
           }
            $items['prj_kode'] = $k;
//           $items['next'] = $this->getRequest()->getParam('next');
           $items['uid_next'] = $uid_next;
           $items['workflow_item_id'] = $workflowItem;
           $items['workflow_item_type_id'] = $workflowItemtype;
           $items['workflow_id'] = $workflowId;
           if (count($projek[$k]) == 0)
           {
               $projek[$k]['prj_kode'] = $k;
               $projek[$k]['uid_next'] = $uid_next;
               $projek[$k]['workflow_item_id'] = $workflowItem;
               $projek[$k]['workflow_item_type_id'] = $workflowItemtype;
               $projek[$k]['workflow_id'] = $workflowId;
           }
           else
           {
               $items['uid_next'] = $projek[$k]['uid_next'];
               $items['workflow_item_id'] = $projek[$k]['workflow_item_id'];
               $items['workflow_item_type_id'] = $projek[$k]['workflow_item_type_id'];
               $items['workflow_id'] = $projek[$k]['workflow_id'];
           }
           if (!$bypass)
           {
                $result = $this->workflow->setWorkflowTrans($trano,'PRAOHP','',$this->const['DOCUMENT_SUBMIT'],$items, '',true);
                $this->getResponse()->setHeader('Content-Type', 'text/javascript');
                if (is_numeric($result))
                {
                    if ($result == 300)
                        $msg = "You are not assigned to this workflow Or Document Receiver Role is not in this Project! Please contact IT Support!";
                    else
                        $msg = $this->error->getErrorMsg($result);
                    $gagal[$k] = $k;
                    $workError['msg'][] = "<b>Project: $k</b>, " . $msg;
                    continue;
    //                $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
    //                return false;
                }
                elseif (is_array($result) && count($result) > 0)
                {

                   $hasil = Zend_Json::encode($result);
                   $this->getResponse()->setBody("{success: true, user:$hasil, prjKode: '$k'}");
                   return false;
                }
               $bypass = true;
           }
            $uid_next = '';
            $workflowId = '';
            $workflowItem = '';
            $workflowItemtype = '';
            $count = 0;
            $jumMasuk = count($ohp);

            $jumTot = 0;
            $jumUSD = 0;
            foreach($v as $key2 => $val2)
            {
                $prj_kode = $v[$key2]['prj_kode'];
                if ($prj_kode == $k)
                {
                    unset($v[$key2]['id_file']);
                    $v[$key2]['trano'] = $trano;
                    $v[$key2]['urut'] = $v[$key2]['id'];
                    unset($v[$key2]['id']);
                    $workid = $v[$key2]['workid'];
                    $kodeBrg = $v[$key2]['kode_brg'];

                    $v[$key2]['uid'] = $this->session->userName;
                    $v[$key2]['tgl'] = date('Y-m-d');
        //			$boq3[$key]['id_user'] = $this->session->idUser;

                    $prjNama = $this->projectHelper->getProjectDetail($v[$key2]['prj_kode']);
                    $v[$key2]['prj_nama'] = $prjNama['Prj_Nama'];
                    if ($v[$key2]['sit_kode'] != '')
                    {

                        $sitNama = $this->projectHelper->getSiteDetail($v[$key2]['prj_kode'],$v[$key2]['sit_kode']);
                        $v[$key2]['sit_nama'] = $sitNama['sit_nama'];
                    }
                    $work = $this->masterWork->fetchRow("workid = '$workid'");
                    if ($work)
                        $workname = $work['workname'];
                    else
                        $workname = $v[$key2]['workid'];

                    $barang = $this->masterBarang->fetchRow("kode_brg = '$kodeBrg'");
                    if ($barang)
                    {
                        $barang['nama_brg'] = str_replace("\r","",$barang['nama_brg']);
                        $barang['nama_brg'] = str_replace("\n","",$barang['nama_brg']);
                        $barang['nama_brg'] = str_replace("\t","",$barang['nama_brg']);
                        $barang['nama_brg'] = str_replace("\""," inch",$barang['nama_brg']);
                        $barang['nama_brg'] = str_replace("'","",$barang['nama_brg']);
                        $namaBrg = $barang['nama_brg'];
                    }
                    else
                        $namaBrg = $v[$key2]['nama_brg'];

                        $insertArray = array(
                                "trano" => $trano,
                                "tgl" => date('Y-m-d'),
        //						"tglinput" => date('Y-m-d'),
        						"jam" => date('H:i:s'),
                                "prj_kode" => $val2['prj_kode'],
                                "prj_nama" => $val2['prj_nama'],
                                "sit_kode" => $val2['sit_kode'],
                                "sit_nama" => $val2['sit_nama'],
                                "workid" => $val2['workid'],
                                "workname" => $workname,
                                "kode_brg" => $val2['kode_brg'],
                                "nama_brg" => $namaBrg,
                                "qty" => '1',
                                "harga" => $val2['total'],
                                "total" => $val2['total'],
                                "ket" => $ket,
                                "petugas" => $this->session->userName
                        );
                        $jumTot += (float)$val2['total'];
                    $insertID = $tempOHP->insert($insertArray);
                    if ($insertID)
                        $count++;
                }
                $insertArray = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "tglinput" => date('Y-m-d'),
//						"tglinput" => date('Y-m-d'),
//						"jam" => date('H:i:s'),
                    "prj_kode" => $val2['prj_kode'],
                    "prj_nama" => $val2['prj_nama'],
                    "sit_kode" => $val2['sit_kode'],
                    "sit_nama" => $val2['sit_nama'],
                    "total" => $jumTot,
                    "petugas" => $this->session->userName
                    );
                    $jumTot += (float)$val2['total'];
                $tempOHPh->insert($insertArray);
            }

		$cekTrano = $counter->fetchRow($where . " AND urut=$last");
           if (!$cekTrano)
           {
               $counter->update(array("urut" => $last),$where);
           }

		}
       if (count($workError) == 0)
    	   $this->getResponse()->setBody("{success: true, number : '$trano'}");
        else
        {
            $jsonError = Zend_Json::encode($workError);
            $jumProjek = count($t);
            if ($workError == $jumProjek)
            {
                $this->getResponse()->setBody("{success: true, allfailed: true, error: '$jsonError'}");
            }
            else
            {
                $this->getResponse()->setBody("{success: true, number : '$trano', error: '$jsonError'}");
            }
        }
    }

    public function updateohpAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
    	$json = $this->getRequest()->getParam("posts");

       $etc = $this->getRequest()->getParam('etc');
       $ohp =  Zend_Json::decode($json);
        $jsonEtc = Zend_Json::decode($etc);

        $tempOHP = new HumanResource_Models_TemporaryOHP();
        $trano = $jsonEtc[0]['trano'];

       $items = $jsonEtc[0];

       $items['next'] = $this->getRequest()->getParam('next');
       $items['uid_next'] = $this->getRequest()->getParam('uid_next');
       $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
       $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
       $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

       $result = $this->workflow->setWorkflowTrans($trano,'PRAOHP','',$this->const['DOCUMENT_RESUBMIT'],$items, '',true);

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
        	$count = 0;
		$jumMasuk = count($ohp);

		$jumIDR = 0;
		$jumUSD = 0;


        $tempOHP->delete("trano = '$trano'");
		foreach ($ohp as $key => $val)
		{
			unset($ohp[$key]['id_file']);
			$ohp[$key]['trano'] = $trano;
			$ohp[$key]['urut'] = $ohp[$key]['id'];
			unset($boq3[$key]['id']);
            $workid = $ohp[$key]['workid'];
            $kodeBrg = $ohp[$key]['kode_brg'];

			$ohp[$key]['uid'] = $this->session->userName;
			$ohp[$key]['tgl'] = date('Y-m-d');
//			$boq3[$key]['id_user'] = $this->session->idUser;

			$prjNama = $this->projectHelper->getProjectDetail($ohp[$key]['prj_kode']);
			$ohp[$key]['prj_nama'] = $prjNama['Prj_Nama'];
			if ($ohp[$key]['sit_kode'] != '')
			{

				$sitNama = $this->projectHelper->getSiteDetail($ohp[$key]['prj_kode'],$ohp[$key]['sit_kode']);
				$ohp[$key]['sit_nama'] = $sitNama['sit_nama'];
			}
            $work = $this->masterWork->fetchRow("workid = '$workid'");
            if ($work)
                $workname = $work['workname'];
            else
                $workname = $ohp[$key]['workid'];

            $barang = $this->masterBarang->fetchRow("kode_brg = '$kodeBrg'");
            if ($barang)
            {
                $barang['nama_brg'] = str_replace("\r","",$barang['nama_brg']);
                $barang['nama_brg'] = str_replace("\n","",$barang['nama_brg']);
                $barang['nama_brg'] = str_replace("\t","",$barang['nama_brg']);
                $barang['nama_brg'] = str_replace("\""," inch",$barang['nama_brg']);
                $barang['nama_brg'] = str_replace("'","",$barang['nama_brg']);
                $namaBrg = $barang['nama_brg'];
            }
            else
                $namaBrg = $ohp[$key]['nama_brg'];

            	$insertArray = array(
						"trano" => $trano,
//						"tgl" => date('Y-m-d'),
//						"tglinput" => date('Y-m-d'),
//						"jam" => date('H:i:s'),
						"prj_kode" => $val['prj_kode'],
						"prj_nama" => $val['prj_nama'],
						"sit_kode" => $val['sit_kode'],
						"sit_nama" => $val['sit_nama'],
                        "workid" => $val['workid'],
                        "workname" => $workname,
                        "kode_brg" => $val['kode_brg'],
                        "nama_brg" => $namaBrg,
                        "qty" => '1',
                        "harga" => $val['total'],
                        "total" => $val['total'],
						"ket" => $jsonEtc[0]['ket'],
						"petugas" => $this->session->userName
                );

			$insertID = $tempOHP->insert($insertArray);
			if ($insertID)
				$count++;

		}

        if (count($ohp) == 0)
			echo "{success:false, msg:\"Error in Database\"}";
		else
			echo "{success:true, count:$count, of:$jumMasuk, number:'$trano'}";
    }

    public function cekbarangAction()
    {
        $this->_helper->viewRenderer->setNoRender();
    	Zend_Loader::loadClass('Zend_Json');
    	$json = $this->getRequest()->getParam("posts");
        $jsonData =  Zend_Json::decode($json);

        $wrong = array();
        foreach ($jsonData as $key => $val)
        {
            $prjKode = $jsonData[$key]['prj_kode'];
            $sitKode = $jsonData[$key]['sit_kode'];
            $workid = $jsonData[$key]['workid'];
            $kodeBrg = $jsonData[$key]['kode_brg'];

            $boq3 = $this->budget->getBoq3ByOne($prjKode,$sitKode,$workid,$kodeBrg);
            if (!$boq3)
            {
                $wrong[] = array(
                    "prj_kode" => $prjKode,
                    "sit_kode" => $sitKode,
                    "workid" => $workid,
                    "kode_brg" => $kodeBrg,
                );
            }
        }
        if(count($wrong) == 0)
            $this->getResponse()->setBody("{success:true}");
        else
        {
            $jsonWrong = Zend_Json::encode($wrong);
            $this->getResponse()->setBody("{success:false, wrong: $jsonWrong}");

        }


    }
}

?>