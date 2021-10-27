<?php
class Admin_BudgetController extends Zend_Controller_Action
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
    private $log;

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
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->boq3 = new Default_Models_MasterBoq3();
        $this->boq3H = new Default_Models_MasterBoq3H();
        $this->cboq3 = new Admin_Model_CBOQ3n();
        $this->cboq3H = new Admin_Model_CBOQ3hn();
        $this->log = new Admin_Models_Logtransaction();
    }

    public function showcreateboq3Action()
    {

    }
    public function createboq3Action()
    {
    	$this->view->uid = $this->session->userName;
    }
    public function uploadboq3Action()
    {
    	$this->_helper->viewRenderer->setNoRender();


		$result = $this->upload->uploadFile($_FILES,'file-path');
		if ($result)
		{
			Zend_Loader::loadClass('Zend_Json');
			$boq3 = $this->phpexcel->readBOQ3FilePerRow($result['save_file'],$result['id_file']);
            $json =  Zend_Json::encode($boq3);
			$fields = array(array("name" => "id","type" => "string"),array("name" => "workid","type" => "string"),array("name" => "workname"),array("name" => "nama_brg"),array("name" => "kode_brg","type" => "string"),array( "name" => "qty","type" => "float"),array( "name" => "harga", "type" => "double"),array( "name" => "val_kode"),array( "name" => "total", "type" => "double"),array( "name" => "rateidr","type" => "int"),array("name" => "id_file"),array("name" => "stspmeal"),array("name" => "cfs_kode"),array("name" => "cfs_nama"),array("name" => "trano"),array("name" => "tranorev"));
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
    	$uid = $this->getRequest()->getParam("uid");
		$boq3 =  Zend_Json::decode($json);
		$rateidr =  Zend_Json::decode($rate);
		$ket =  Zend_Json::decode($ket);

		$tempBOQ3 = new ProjectManagement_Models_BOQ3();
		$counter = new Default_Models_MasterCounter();

//        $cek = $BOQ3h->fetchRow("prj_kode ='$prjKode' AND sit_kode = '$sitKode'");
//        if ($cek)
//        {
//            echo "{success:false, message:\"Budget for project $prjKode and site $sitKode is exist!\"}";
//            return false;
//        }

//		$lastTrans = $counter->getLastTrans('CBOQ3N2');
//		$lastTrans['urut'] = $lastTrans['urut'] + 1;
//		$trano = 'CBOQ3N2-' . $lastTrans['urut'];
		$count = 0;
		$jumMasuk = count($boq3);

//		$counter->update(array("urut" => $lastTrans['urut']),"id=".$lastTrans['id']);
		$jumIDR = 0;
		$jumUSD = 0;

		foreach ($boq3 as $key => $val)
		{
			unset($boq3[$key]['id_file']);
//			$boq3[$key]['tranorev'] = $trano;
//			$boq3[$key]['trano'] = "";
			$boq3[$key]['urut'] = $boq3[$key]['id'];
			unset($boq3[$key]['id']);
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
            $log['boq3d'][] = $boq3[$key];


//            $boq3[$key]['trano'] = $trano;
//            unset($boq3[$key]['tranorev']);
			$boq3[$key]['afe_no'] = "";
//            $this->cboq3->insert($boq3[$key]);
            $log['kboq3d'][] = $boq3[$key];
			if ($insertID)
				$count++;
		}

        $trano = $boq3[0]['trano'];

        Zend_Loader::loadClass('Zend_Json');
        $jsonLog = Zend_Json::encode($log);
        $arrayLog = array (
          "trano" => $trano,
          "uid" => $this->session->userName,
          "tgl" => date('Y-m-d H:i:s'),
          "prj_kode" => $prjKode,
          "sit_kode" => $sitKode,
          "action" => "INSERT",
          "data_after" => $jsonLog,
          "ip" => $_SERVER["REMOTE_ADDR"],
          "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
      );
      $this->log->insert($arrayLog);  

//		$customer = new Default_Models_MasterCustomer();
//
//		$cusKode = $customer->getCustomerFromProject($prjKode);
//
//		$cusKode = $cusKode['cus_kode'];
//
//		$insertArray = array(
//						"trano" => $trano,
//						"tgl" => date('Y-m-d'),
//						"prj_kode" => $prjKode,
//						"prj_nama" => $prjNama['Prj_Nama'],
//						"sit_kode" => $sitKode,
//						"sit_nama" => $sitNama['sit_nama'],
//						"ket" => $ket,
//						"rateidr" => $rateidr,
//						"cus_kode" => $cusKode,
//						"total" => $jumIDR,
//						"totalusd" => $jumUSD,
//						"user" => $uid,
//						);
//
//		$result = $BOQ3h->insert($insertArray);


		if (count($boq3) == 0)
			echo "{success:false, message:\"Error in Database\"}";
		else
			echo "{success:true, count:$count, of:$jumMasuk}";
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

	   $lastTrans = $counter->getLastTrans('CBOQ3N');
	   $last = abs($lastTrans['urut']);
	   $last = $last + 1;
	   $trano = 'CBOQ3N-' . $last;
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

				"total" => $total,
				"ket" => $val['ket'],

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

				"total" => $total,
				"ket" => $val['ket'],

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
       		$this->getResponse()->setHeader('Content-Type', 'text/javascript');
        	$this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateboq3Action()
    {

    }
}