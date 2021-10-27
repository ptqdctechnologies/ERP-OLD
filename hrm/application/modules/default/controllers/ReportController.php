<?php
/* 
	Created @ Mar 22, 2010 10:49:11 AM
 */

class ReportController extends Zend_Controller_Action
{

    private $db;
    private $budget;
    private $transaction;
    private $project;
    private $ARF;
    private $ASF;
    private $memcache;
    private $logtrans;
   
    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->memcache = Zend_Registry::get('Memcache');
        $this->db->getConnection()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); 
        $session = new Zend_Session_Namespace('login');
       
        $this->budget = new Default_Models_Budget();
        $this->transaction = $this->_helper->getHelper('transaction');
        $this->project = $this->_helper->getHelper('project');
        $this->ARF = new Default_Models_AdvanceRequestForm();
        $this->ASF = new Default_Models_AdvanceSettlementForm();
        $this->logtrans = new Procurement_Model_Logtransaction();
    }
    
    public function showboq3Action()
    {
    	
    }
    
    public function boq3Action()
    {
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->budget->getBoq3('all-ori-by-workid',$prjKode,$sitKode);
        $this->view->result = $result;
    }
	
    public function showboq3revisiAction()
    {
    	
    }
    
    public function boq3revisiAction()
    {
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->budget->getBoq3('all-current-by-workid',$prjKode,$sitKode);
        $this->view->result = $result;
    }
    
    public function showbudgetAction()
    {
    	$this->view->columnHeader = $columnView;
    }

    public function compareboqAction()
    {
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->budget->compareBoq($prjKode,$sitKode);
        $this->view->result = $result;
        
        //Get updated exchange rate from database
       $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			FROM exchange_rate
       			WHERE val_kode='USD'
       			ORDER BY tgl DESC
       			LIMIT 0,1";
       
       $fetch = $this->db->query($sql);
       $data = $fetch->fetch();     
       
       $this->view->rateidr = $data['rateidr'];     
       $this->view->tgl = $data['tgl_rate'];
    }
    
    public function showcompareboqAction()
    {
    	
    }
    
    public function budgetAction()
    {
        //$this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $formula = $request->getParam('formula');

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
		
        $current = $request->getParam('current');
        if ($current == '')
        	$current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
        	$currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
        	$requested = 0;
        
        $offset = ($currentPage - 1) * 20;
        $allSite = false;
        
        $all = $request->getParam('all');
        
        
        $detail = false;
        $show = $request->getParam('show');
        if ($show == 'Detail')
        	$detail=true;
        $rate = $request->getParam('userate');
        if ($rate == '')
        	$rate=false;

        $today = new DateTime(date("Y-m-d H:i:s"));
        $expire = new DateTime(date("Y-m-d H:i:s"));
        $expire->add(new DateInterval("PT30M"));

        $fromCache = false;

        if ($formula == 'OLD' || $formula == '')
        {
            if (!$all)
            {
                $cacheID = "REPORT_BUDGET_$prjKode" . "_$sitKode" . "_" . $offset;
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID))
                {
                    $result = $this->budget->getBudgetProject(false,$prjKode,$sitKode,$detail,$offset);
                    $resultAll = $result;
                    $this->memcache->save($result,$cacheID,array('REPORT'));
                    //cache time generated...
                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time,$cacheTimeID,array('REPORT'));
                }
                else
                {
                    $result = $this->memcache->load($cacheID);
                    $time = $this->memcache->load($cacheTimeID);
                    $resultAll = $result;
                    $fromCache = true;
                }
            }
            else
            {
                $cacheID = "REPORT_BUDGET_ALL_$prjKode" . "_$offset";
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID))
                {
                    $result = $this->budget->getBudgetProject(true,'','',$detail,$offset);
                    $this->memcache->save($result,$cacheID,array('REPORT'));
                    //cache time generated...
                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time,$cacheTimeID,array('REPORT'));
                }
                else
                {
                    $result = $this->memcache->load($cacheID);
                    $time = $this->memcache->load($cacheTimeID);
                    $resultAll = $result;
                    $fromCache = true;
                }
            }


            if ($sitKode == '')
            {
                $siteCount = $this->project->getSiteCount($prjKode);
                $allSite = true;
                $cacheID = "REPORT_BUDGET_$prjKode" . "_ALL_SITE";
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID))
                {
                    $resultAll = $this->budget->getBudgetProjectAll($prjKode,true);
                    $this->memcache->save($resultAll,$cacheID,array('REPORT'));
                    //cache time generated...
                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time,$cacheTimeID,array('REPORT'));
                }
                else
                    $resultAll = $this->memcache->load($cacheID);
            }
            else
            {
                $siteCount = 1;
            }
        }
        else if ($formula == 'CFS')
        {
            if (!$all)
            {
                $cacheID = "REPORT_BUDGET_CFS_$prjKode" . "_$sitKode" . "_" . "$offset";
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID))
                {
                    $result = $this->budget->getBudgetProjectCFS(false,$prjKode,$sitKode,$detail,$offset);
                    $resultAll = $result; 
                    $this->memcache->save($result,$cacheID,array('REPORT'));
                    //cache time generated...
                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time,$cacheTimeID,array('REPORT'));
                }
                else
                {
                    $result = $this->memcache->load($cacheID);
                    $time = $this->memcache->load($cacheTimeID);
                    $resultAll = $result;
                    $fromCache = true;
                }
            }
            else
            {
                $cacheID = "REPORT_BUDGET_CFS_ALL_$prjKode" . "_$offset";
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID))
                {
                    $result = $this->budget->getBudgetProjectCFS(true,'','',$detail,$offset);
                    $this->memcache->save($result,$cacheID,array('REPORT'));
                    //cache time generated...
                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time,$cacheTimeID,array('REPORT'));
                }
                else
                {
                    $result = $this->memcache->load($cacheID);
                    $time = $this->memcache->load($cacheTimeID);
                    $resultAll = $result;
                    $fromCache = true;
                }
            }


            if ($sitKode == '')
            {

                $siteCount = count($this->budget->getBoq3All('all-current-by-cfskode',$prjKode));
                    $allSite = true;
                    $cacheID = "REPORT_BUDGET_CFS_$prjKode" . "_ALL_SITE";
                    $cacheTimeID = $cacheID . "_TIME";
                    if (!$this->memcache->test($cacheID))
                    {
                        $resultAll = $this->budget->getBudgetProjectAllCFS($prjKode);
                        $this->memcache->save($resultAll,$cacheID,array('REPORT'));
                        //cache time generated...
                        
                        $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                        );
                        $this->memcache->save($time,$cacheTimeID,array('REPORT'));
                    }
                    else
                        $resultAll = $this->memcache->load($cacheID);
            }
            else
            {
                $siteCount = 1;
            }

            $this->view->isCFS = true;
        }
       //Get updated exchange rate from database
       $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			FROM exchange_rate
       			WHERE val_kode='USD'
       			ORDER BY tgl DESC
       			LIMIT 0,1";
       $fetch = $this->db->query($sql);
       $data = $fetch->fetch();     

       if ($fromCache)
        $this->view->time = $time;

       $this->view->rateidr = $data['rateidr'];
       $this->view->tgl = $data['tgl_rate'];
       $this->view->result = $result;
       $this->view->resultAll = $resultAll;
       $this->view->detail = $detail;
       $this->view->rate = $rate;
       $this->view->formula = $formula;
        $this->view->limitPerPage = 20; 
        $this->view->totalResult = $siteCount;
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();

    }
    
    public function budgetbyperprojectAction()
    {
        
    }

   public function showprAction()
   {
   	
   }

   public function showoutprpoAction()
   {
   	
   }
   public function showoutprpoprjAction()
   {

   }

    public function showlbarangAction()
   {

   }

   public function showrmdiAction()
   {
   	
   }

    public function showdorAction()
   {

   }

    public function arfasfAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $param = $request->getParam('param');

//        $result = $this->transaction->getArfasf($prjKode);
//        $this->view->result = $result;
//
//        $sp = $this->db->prepare("call sp_arfhasfd('$prjKode','grandtotal')");
//        $sp->execute();
//        $result = $sp->fetchAll();
//        $sp->closeCursor();
//        
//        $this->view->grandTotalARF = number_format($result[0]['grandTotalARF'],2);
//        $this->view->grandTotalASF = number_format($result[0]['grandTotalASF'],2);
//        $this->view->grandTotalBalance = number_format($result[0]['grandTotalBalance'],2);
        $this->view->prjKode = $prjKode;
        $this->view->param = $param;
        
    }
    
    public function getdataarfasfAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $param = $request->getParam('param');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'prj_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $hasil = $this->transaction->getArfasf($prjKode);

        $jum = 0;
        $prevARF_no = '';
        $prevKode_brg = '';
        $prevTotal_IDR = 0;
        $totalARFIDR = 0;
        $totalASFIDR = 0;
        $totalBalanceIDR = 0;
        $prevBalance = 0;
        
        foreach ($hasil as $key => $val)
        {
        	if ($key >= $offset && $jum < $limit)
        	{
        		if (!isset($val['kode_brg2']))
        		{
	        			$cekASF = $this->ASF->getasfDetailByArfNo($val['arf_num'],$val['prj_kode'],$val['sit_kode']);
	        			if (count($cekASF) > 0)
	        			{
        					$no_asf = '';
	        				foreach ($cekASF as $key2 => $val2)
	        				{
	        					$no_asf = $val2['trano'];
	        					$cekASF2 = $this->ASF->getasfDetail($no_asf);
	        					$val['asf_no'] = $cekASF2[0]['trano'];
			        			$val['arf_nbr'] = $cekASF2[0]['arf_no'];
			        			$val['tgl_asf'] = $cekASF2[0]['tgl'];
			        			$val['totalasf'] = ($cekASF2[0]['qty'] * $cekASF2[0]['harga']);
			        			$val['balance'] = $val['total'] - ($val['totalasf'] + $val['totalasfcancel']);
			        			$val['invalid'] = true;
		        				$val['invalid_ket'] = 'ARF is not match with ASF, this ARF (' . $val['arf_num'] . ') was paid with different ASF (' . $val['asf_no'] . ')!';
		        				
	        					if ($val['asfcancel_num'] != '')
				        		{	
				        			$val['cancel_ket'] = 'This ARF (' . $val['arf_num'] . ') was cancelled (' .  $val['asfcancel_num'] . ')!';
				        		}
				        		if ($prevARF_no != '' && $prevKode_brg != '' && $prevARF_no == $val['arf_num'] && $prevKode_brg == $val['kode_brg'])
				        		{
				        			if ($prevTotal_IDR == $val['total_arf'])
				        			{
				        				$val['total_arf'] = '';
				        				$val['balance'] = $prevBalance - $val['total_arf'] -  $val['total_asf']-  $val['total_asfcancel'];
				        				$prevBalance = $val['balance'];
				        			}
				        		}
				        		
				        		$prevARF_no = $val['arf_num'];
				        		$prevKode_brg = $val['kode_brg'];
				        		if ($val['total_arf'] != '')
				        		{
				        			$prevTotal_IDR = $val['total_arf'];
				        			$prevBalance =  $val['balance'];
				        		}
		        				
        						$result['posts'][] = $val;
	        				}
	        				continue;
		        			
	        			}
	        			else
	        			{	
	        				$val['invalid'] = true;
	        				$val['invalid_ket'] = 'This ARF is doesn\'t have ANY ASF!!';
	        			}
        		}
        		if ($val['asfcancel_num'] != '')
        		{	
        			$val['cancel_ket'] = 'This ARF (' . $val['arf_num'] . ') was cancelled (' .  $val['asfcancel_num'] . ')!';
        		}
        		if ($prevARF_no != '' && $prevKode_brg != '' && $prevARF_no == $val['arf_num'] && $prevKode_brg == $val['kode_brg'])
        		{
        			if ($prevTotal_IDR == $val['total_arf'])
        			{
        				$val['total_arf'] = '';
        				$val['balance'] = $prevBalance - $val['total_arf'] -  $val['total_asf']-  $val['total_asfcancel'];
        				$prevBalance = $val['balance'];
        			}
        		}
        		
        		$prevARF_no = $val['arf_num'];
        		$prevKode_brg = $val['kode_brg'];
        		if ($val['total_arf'] != '')
        		{
        			$prevTotal_IDR = $val['total_arf'];
        			$prevBalance =  $val['balance'];
        		}
//        		echo $val['arf_num'] . " = " . $prevARF_no . " | " . $val['total_arf'] . " > " . $prevTotal_IDR . " > ". $val['total_asf'] . " > ". $val['total_asfcancel']  . " | " . $val['balance'] . " >> " .$prevBalance . "<br/>";
        		
        		if ($val['kode_brg'] != $val['kode_brg2'] && $val['invalid'] == '')
        		{
        			$val['invalid'] = true;
        			$val['invalid_ket'] = 'ARF Product ID is not match with ASF, this Product ID (' . $val['kode_brg'] . ') was paid with different Product ID (' . $val['kode_brg2'] . ')!';
		        				
        		}
        		
        		$result['posts'][] = $val;
        		$jum++;
        	}
//        	$totalARFIDR += $val['total_arf'];
//        	$totalASFIDR += $val['total_asf'];
//        	$totalASFcancelIDR += $val['total_asfcancel'];
//        	$totalBalanceIDR += $val['balance'];
        }
        
        $resultArf = $this->budget->getArfd('summary',$prjKode);
        $totalARFIDR = $resultArf['totalARF'];
        
              
        $resultAsf = $this->budget->getAsfdd('summary',$prjKode);
    	$totalASFIDR = $resultAsf['totalASFDD'];
        
        
    	$resultAsfcancel = $this->budget->getAsfddCancel('summary',$prjKode);
    	$totalASFcancelIDR = $resultAsfcancel['totalAsfddCancel'];
        
        
        $totalBalanceIDR = $totalARFIDR - ($totalASFIDR + $totalASFcancelIDR);
        
    	if (count($result['posts'])>0)
        {
        	$result['posts'][0]['gTotalArf'] = $totalARFIDR;
        	$result['posts'][0]['gTotalAsf'] = $totalASFIDR;
        	$result['posts'][0]['gTotalAsfcancel'] = $totalASFcancelIDR;
        	$result['posts'][0]['gTotalBalance'] = $totalBalanceIDR;
        }
        $result['count'] = count($hasil);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);
//        $json = str_replace("\\",'',$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function porpiAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $param = $request->getParam('param');

        $result = $this->transaction->getPorpi($prjKode,$sitKode,$param);
        $this->view->result = $result;
    }

    public function porpisumdtlAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $trano = $request->getParam('trano');

        $param = $request->getParam('param');

        if ($prjKode != '' && $sitKode != '')
            $this->view->url = "/default/report/getdataporpi/prj_kode/$prjKode/sit_kode/$sitKode/param/detail-rpi";
        if ($supKode != '')
            $this->view->url = "/default/report/getdataporpi/sup_kode/$supKode/param/detail-rpi";
        if ($trano != '')
            $this->view->url = "/default/report/getdataporpi/trano/$trano/param/detail-rpi";
    }

    public function mdimdosumdtlAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$param = $request->getParam('param');
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
        //$this->view->param = $param;
    }

    public function mdodosumdtlAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
  
    }

    public function getdataporpiAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $trano = $request->getParam('trano');
        $param = $request->getParam('param');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $hasil = $this->transaction->getPorpi($prjKode,$sitKode,$supKode,$trano,$param);

        $jum = 0;
        $prevPO_no = '';
        $prevWorkid = '';
        $prevKode_brg = '';
        $prevTotal_IDR = 0;
        $prevTotal_USD = 0;
        $totalPOIDR = 0;
        $totalPOUSD = 0;
        $totalRPIIDR = 0;
        $totalRPIUSD = 0;
        $totalBalanceIDR = 0;
        $totalBalanceUSD = 0;
        
        $prevBalance_IDR = 0;
        $prevBalance_USD = 0;
        $jumPO = 0;
        $indeks = 0;
        foreach ($hasil as $key => $val)
        {
        		if ($prevPO_no != '' && $prevKode_brg != '' && $prevPO_no == $val['po_no'] && $prevKode_brg == $val['kode_brg'])
        		{
        			if ($prevTotal_IDR == $val['totalPO_IDR'])
        			{
        				$val['totalPO_IDR'] = '';
        				$val['balanceIDR'] = $prevBalance_IDR - $val['totalRPI_IDR'];
        				$prevBalance_IDR = $val['balanceIDR'];
//        				echo $val['po_no'] . " " . $prevTotal_IDR . " RPI:" .  $val['totalRPI_IDR'] . " prevbalance:" . $prevBalance_IDR . "->" . $val['balanceIDR'] . "<br/>" ; 
        			}
        			elseif ($prevTotal_USD == $val['totalPO_USD'])
        			{
        				$val['totalPO_USD'] = '';
        				$val['balanceUSD'] = $prevBalance_USD - $val['totalRPI_USD'];
        			}
        		}
        		if ($prevPO_no != $val['po_no'])
        			$jumPO++;
        		
        		$prevPO_no = $val['po_no'];
        		$prevWorkid = $val['workid'];
        		$prevKode_brg = $val['kode_brg'];
        		if ($val['totalPO_IDR'] != '')
        		{
        			$prevTotal_IDR = $val['totalPO_IDR'];
        			$prevBalance_IDR =  $val['balanceIDR'];
        		}
        		if ($val['totalPO_USD'] != '')
        		{
        			$prevTotal_USD = $val['totalPO_USD'];
        			$prevBalance_USD =  $val['balanceUSD'];
        		}
        	if ($key >= $offset && $jum < $limit)
        	{
        		$val['id'] = $indeks+1;
        		$result['posts'][] = $val;
        		$jum++;
        		$indeks++;
        	}
        	
        		
//        	$totalPOIDR += $val['totalPO_IDR'];
//        	$totalPOUSD += $val['totalPO_USD'];
//        	$totalRPIIDR += $val['totalRPI_IDR'];
//        	$totalRPIUSD += $val['totalRPI_USD'];
//        	$totalBalanceIDR += $val['balanceIDR'];
//        	$totalBalanceUSD += $val['balanceUSD'];	
        }
        
        $resultPod = $this->budget->getPodByOne('summary',$prjKode,$sitKode,$supKode,$trano);
        $totalPOIDR = $resultPod['totalIDR'];
        $totalPOUSD = $resultPod['totalHargaUSD'];
        
              
        $resultRpid = $this->budget->getRpidByOne('summary',$prjKode,$sitKode,$supKode,'',$trano);
        $totalRPIIDR = $resultRpid['totalIDR'];
        $totalRPIUSD = $resultRpid['totalHargaUSD'];
        
        if (count($result['posts'])>0)
        {
        	$result['posts'][0]['gtotalPO_IDR'] = $totalPOIDR;
        	$result['posts'][0]['gtotalPO_USD'] = $totalPOUSD;
        	$result['posts'][0]['gtotalRPI_IDR'] = $totalRPIIDR;
        	$result['posts'][0]['gtotalRPI_USD'] = $totalRPIUSD;
        	$result['posts'][0]['gtotalBalance_IDR'] = $totalPOIDR - $totalRPIIDR;
        	$result['posts'][0]['gtotalBalance_USD'] = $totalPOUSD - $totalRPIUSD;
        }
//        die;
//        echo '<pre>' . var_dump($result['posts']) . '</pre>';
        $result['count'] = count($hasil);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);
//        $json = str_replace("\\",'',$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    //mdimdo for grouping
    public function getdatamdimdoAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$param = $request->getParam('param');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $hasil = $this->transaction->getmdimdo($prjKode,$sitKode);

        $jum = 0;
        $prevMDI_no = '';
        $prevKode_brg = '';
        $qty_mdi = 0;
        $qty_mdo = 0;
        $balance = 0;

        foreach ($hasil as $key => $val)
        {
        		if ($prevMDI_no != '' && $prevKode_brg != '' && $prevMDI_no == $val['mdi_no'] && $prevKode_brg == $val['kode_brg'])
        		{
        			if ($qty_mdi == $val['qty_mdi'])
        			{
        				$val['qty_mdi'] = '';
        			}
        			elseif ($qty_mdo == $val['qty_mdo'])
        			{
        				$val['qty_mdo'] = '';
        			}
        		}
        		$prevMDI_no = $val['mdi_no'];
        		$prevKode_brg = $val['kode_brg'];
        		$qty_mdi = $val['qty_mdi'];
        		$qty_mdo = $val['qty_mdo'];
                        $balance = $val['balance'];

        	if ($key >= $offset && $jum < $limit)
        	{
        		$result['posts'][] = $val;
        		$jum++;
        	}
        	$qty_mdi += $val['qty_mdi'];
        	$qty_mdo += $val['qty_mdo'];
        	$balance += $val['balance'];
        	
        }
        
        if (count($result['posts'])>0)
        {
        	$result['posts'][0]['gqty_mdi'] = $qty_mdi;
        	$result['posts'][0]['gqty_mdo'] = $qty_mdo;
        	$result['posts'][0]['gbalance'] = $balance;
        }

        $result['count'] = count($hasil);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    //mdodo for grouping
    public function getdatamdodoAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$param = $request->getParam('param');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $hasil = $this->transaction->getmdodo($prjKode,$sitKode);

        $jum = 0;
        $prevMDO_no = '';
        $prevKode_brg = '';
        $qty_mdo = 0;
        $qty_do = 0;
        $balance = 0;

        foreach ($hasil as $key => $val)
        {
        		if ($prevMDO_no != '' && $prevKode_brg != '' && $prevMDO_no == $val['mdo_no'] && $prevKode_brg == $val['kode_brg'])
        		{
        			if ($qty_mdi == $val['qty_mdo'])
        			{
        				$val['qty_mdo'] = '';
        			}
        			elseif ($qty_mdo == $val['qty_do'])
        			{
        				$val['qty_do'] = '';
        			}
        		}
        		$prevMDI_no = $val['mdo_no'];
        		$prevKode_brg = $val['kode_brg'];
        		$qty_mdi = $val['qty_mdo'];
        		$qty_mdo = $val['qty_do'];
                        $balance = $val['balance'];

        	if ($key >= $offset && $jum < $limit)
        	{
        		$result['posts'][] = $val;
        		$jum++;
        	}
        	$qty_mdo += $val['qty_mdo'];
        	$qty_do += $val['qty_do'];
        	$balance += $val['balance'];

        }

        if (count($result['posts'])>0)
        {
        	$result['posts'][0]['gqty_mdi'] = $qty_mdo;
        	$result['posts'][0]['gqty_mdo'] = $qty_do;
        	$result['posts'][0]['gbalance'] = $balance;
        }

        $result['count'] = count($hasil);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function showporpisummaryAction()
    {
		
    }

    public function outstandingporpiAction()
    {
    	$prevPO_no = '';
        $prevKode_brg = '';
        $prevWorkid = '';
    	$prevPO = '';
    	$prevTotal_IDR = '';
    	$prevTotal_USD = '';
    	$request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = array();
        if ($sitKode != '')
        {
        	$hasil[$sitKode] = $this->transaction->getPorpi($prjKode,$sitKode,'detail-rpi');
        }
        else
        {
        	$hasil = array();
        	$sql = "SELECT sit_kode, sit_nama
        			FROM master_site
        			WHERE prj_kode ='$prjKode'";
     
        	$fetch = $this->db->query($sql);
            $master = $fetch->fetchAll();
            foreach ($master as $key => $val)
            {
            	$sitKode = $val['sit_kode'];
            	$hasil[$sitKode] = $this->transaction->getPorpi($prjKode,$sitKode,'detail-rpi');
            }
        }	
        $i = 0;
//        var_dunp($hasil);die;
        foreach ($hasil as $key => $val)
        {
        	foreach ($val as $key2 => $val2)
        	{
        		$po = $val2['po_no'];
//        		if ($prevPO_no == '')
//        		{
	        		$result[$po]['po_no'] = $val2['po_no'];
//	        		$result[$po]['totalPO_IDR'] = $val2['totalPO_IDR'];
//	        		$result[$po]['totalPO_USD'] = $val2['totalPO_USD'];
//	        		$result[$po]['totalRPI_IDR'] = $val2['totalRPI_IDR'];
//	        		$result[$po]['totalRPI_USD'] = $val2['totalRPI_USD'];
//        		}
//        		if ($prevPO_no != '' )
//                {
//                    if ($prevPO_no != $val2['po_no'])
//                    {
//                        $result[$po]['po_no'] = $val2['po_no'];
//                        $result[$po]['totalPO_IDR'] = $val2['totalPO_IDR'];
//                        $result[$po]['totalPO_USD'] = $val2['totalPO_USD'];
//                        $result[$po]['totalRPI_IDR'] = $val2['totalRPI_IDR'];
//                        $result[$po]['totalRPI_USD'] = $val2['totalRPI_USD'];
//                    }
//                    else
//                    {
//                        if ($prevTotal_IDR != $val2['totalPO_IDR'] || $prevKode_brg != $val2['kode_brg'])
//                        {
                            $result[$po]['totalPO_IDR'] = $result[$po]['totalPO_IDR'] + $val2['totalPO_IDR'];
                            $result[$po]['totalPO_USD'] = $result[$po]['totalPO_USD'] + $val2['totalPO_USD'];
//                        }
                        $result[$po]['totalRPI_IDR'] = $result[$po]['totalRPI_IDR'] + $val2['totalRPI_IDR'];
                        $result[$po]['totalRPI_USD'] = $result[$po]['totalRPI_USD'] + $val2['totalRPI_USD'];
//                    }
//
//                }
//                $prevPO_no = $val2['po_no'];
//                $prevKode_brg = $val2['kode_brg'];
//                $prevWorkid = $val2['workid'];
//                $prevTotal_IDR = $val2['totalPO_IDR'];
//                $prevTotal_USD = $val2['totalPO_USD'];
	        	$i++;
        	}
        }
        
        $this->view->result = $result;
    }

    public function outstandingmdimdoAction()
    {
    	$prevMDI_no = '';
        $prevKode_brg = '';
        $prevWorkid = '';
    	$prevMDI = '';
    	$qty = '';
    	$balance = '';
    	$request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = array();
//        if ($sitKode != '')
//        {
////            $hasil[$sitKode] = $this->transaction->getMdimdo($prjKode,$sitKode);
//            $hasil[$sitKode] = $this->transaction->getdortodo($prjKode,$sitKode);
//        }
//        else
//        {
//        	$hasil = array();
//        	$sql = "SELECT sit_kode, sit_nama
//        			FROM master_site
//        			WHERE prj_kode ='$prjKode' ";
//
//            $fetch = $this->db->query($sql);
//            $master = $fetch->fetchAll();
//            foreach ($master as $key => $val)
//            {
//            	$sitKode = $val['sit_kode'];
////            	$hasil[$sitKode] = $this->transaction->getMdimdo($prjKode,$sitKode);
//            	$hasil[$sitKode] = $this->transaction->getdortodo($prjKode,$sitKode);
//            }
//        }
//
//        $i = 0;
//        foreach ($hasil as $key => $val)
//        {
//        	foreach ($val as $key2 => $val2)
//        	{
////        		$mdi = $val2['mdi_no'];
//        		$mdi = $val2['dor_no'];
//        		if ($prevMDI_no == '')
//        		{
////	        		$result[$mdi]['mdi_no'] = $val2['mdi_no'];
////	        		$result[$mdi]['qty_mdi'] = $val2['qty_mdi'];
////	        		$result[$mdi]['qty_mdo'] = $val2['qty_mdo'];
//	        		$result[$mdi]['dor_no'] = $val2['dor_no'];
//	        		$result[$mdi]['qty_dor'] = $val2['qty_dor'];
//	        		$result[$mdi]['qty_do'] = $val2['qty_do'];
//	        		$result[$mdi]['balance'] = $val2['balance'];
//        		}
//        		if ($prevMDI_no != '' )
//	        		{
////	        			if ($prevMDI_no != $val2['mdi_no'])
//	        			if ($prevMDI_no != $val2['dor_no'])
//	        			{
////	        				$result[$mdi]['mdi_no'] = $val2['mdi_no'];
////			        		$result[$mdi]['qty_mdi'] = $val2['qty_mdi'];
////			        		$result[$mdi]['qty_mdo'] = $val2['qty_mdo'];
////			        		$result[$mdi]['balance'] = $val2['balance'];
//	        				$result[$mdi]['dor_no'] = $val2['dor_no'];
//			        		$result[$mdi]['qty_dor'] = $val2['qty_dor'];
//			        		$result[$mdi]['qty_do'] = $val2['qty_do'];
//			        		$result[$mdi]['balance'] = $val2['balance'];
//	        			}
//
//	        		}
//
////	        		$prevMDI_no = $val2['mdi_no'];
//	        		$prevMDI_no = $val2['dor_no'];
//	        		$prevKode_brg = $val2['kode_brg'];
//					$prevWorkid = $val2['workid'];
////					$prevTotal_IDR = $val2['qty_mdi'];
//					$prevTotal_IDR = $val2['qty_dor'];
//	        	$i++;
//        	}
//        }

//        $result = $this->transaction->getMdimdo($prjKode,$sitKode);
        $result = $this->transaction->getdortodo($prjKode,$sitKode);
        $this->view->result = $result;
    }
    
    public function mdimdoAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = $this->transaction->getMdimdo($prjKode,$sitKode);
        $this->view->result = $result;
    }

    public function outstandingmdodoAction()
    {
    	$prevMDO_no = '';
        $prevKode_brg = '';
        $prevWorkid = '';
    	$prevMDO = '';
    	$qty = '';
    	$balance = '';
    	$request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = array();
        if ($sitKode != '')
        {
            $hasil[$sitKode] = $this->transaction->getMdodo($prjKode,$sitKode);
        }
        else
        {
        	$hasil = array();
        	$sql = "SELECT sit_kode, sit_nama
        			FROM master_site
        			WHERE prj_kode ='$prjKode' ";

            $fetch = $this->db->query($sql);
            $master = $fetch->fetchAll();
            foreach ($master as $key => $val)
            {
            	$sitKode = $val['sit_kode'];
            	$hasil[$sitKode] = $this->transaction->getMdodo($prjKode,$sitKode);
            }
        }

        $i = 0;
        foreach ($hasil as $key => $val)
        {
        	foreach ($val as $key2 => $val2)
        	{
        		$mdi = $val2['mdo_no'];
        		if ($prevMDI_no == '')
        		{
	        		$result[$mdi]['mdo_no'] = $val2['mdo_no'];
	        		$result[$mdi]['qty_mdo'] = $val2['qty_mdo'];
	        		$result[$mdi]['qty_do'] = $val2['qty_do'];
	        		$result[$mdi]['balance'] = $val2['balance'];
        		}
        		if ($prevMDO_no != '' )
	        		{
	        			if ($prevMDO_no != $val2['mdo_no'])
	        			{
	        				$result[$po]['mdo_no'] = $val2['mdo_no'];
			        		$result[$po]['qty_mdo'] = $val2['qty_mdo'];
			        		$result[$po]['qty_do'] = $val2['qty_do'];
			        		$result[$po]['balance'] = $val2['balance'];
	        			}

	        		}

	        		$prevMDO_no = $val2['mdo_no'];
	        		$prevKode_brg = $val2['kode_brg'];
					$prevWorkid = $val2['workid'];
					$qty_mdo = $val2['qty_mdo'];
	        	$i++;
        	}
        }

        $result = $this->transaction->getMdodo($prjKode,$sitKode);
        $this->view->result = $result;
    }
    
    public function mdodoAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = $this->transaction->getMdodo($prjKode,$sitKode);
        $this->view->result = $result;
    }

    public function arfsummaryAction()
    {
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $requestor = $request->getParam('requestor');

        
        $current = $request->getParam('current');
        if ($current == '')
        	$current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
        	$currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
        	$requested = 0;
        
        $arf = new Default_Models_AdvanceRequestForm();
        $result = $arf->getArfSum($prjKode,$sitKode,$requestor);
        $this->view->limitPerPage = 100; 
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    public function asfsummaryAction()
    {
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $current = $request->getParam('current');
        if ($current == '')
        	$current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
        	$currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
        	$requested = 0;
        
        $asf = new Default_Models_AdvanceSettlementForm();
        $result = $asf->getasfSum($prjKode,$sitKode);
        $this->view->limitPerPage = 100; 
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    public function arfdetailAction()
    {
    	$request = $this->getRequest();
  
        $noTrans = $request->getParam('trano');
        $popup = $request->getParam('popup');

        if ($popup == '')
            $popup = false;

        $arf = new Default_Models_AdvanceRequestForm();
        $result = $arf->getArfDetail($noTrans);
        $this->view->result = $result;
        $this->view->popup = $popup;
    }

    public function asfdetailAction()
    {
    	$request = $this->getRequest();

        $noTrans = $request->getParam('trano');

        $asf = new Default_Models_AdvanceSettlementForm();
        $result = $asf->getasfDetail($noTrans);
        $this->view->result = $result;
    }

    public function prsummaryAction()
    {
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $current = $request->getParam('current');
        if ($current == '')
        	$current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
        	$currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
        	$requested = 0;
        	
        $pr = new Default_Models_ProcurementRequest();
        $result = $pr->getPrSum($prjKode,$sitKode);
        
        $this->view->limitPerPage = 100; 
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    public function posummaryAction()
    {
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        if ($request->getParam('tgl1') != '')
            $tgl1 = date("Y-m-d",strtotime(urldecode($request->getParam('tgl1'))));
        if ($request->getParam('tgl2') != '')
            $tgl2 = date("Y-m-d",strtotime(urldecode($request->getParam('tgl2'))));
        $current = $request->getParam('current');
        if ($current == '')
        	$current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
        	$currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
        	$requested = 0;
        	
        $po = new Default_Models_ProcurementPod();
        
        $result = $po->getPoSum($prjKode,$sitKode,$supKode,$tgl1,$tgl2);
     
        $this->view->limitPerPage = 100; 
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    //RPI Summary Report
    public function rpisummaryAction()
    {
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');

        $current = $request->getParam('current');
        if ($current == '')
        	$current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
        	$currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
        	$requested = 0;
        
        $rpi = new Default_Models_RequestPaymentInvoice();
        $result = $rpi->getrpiSum($prjKode,$sitKode,$supKode);
        
        $this->view->limitPerPage = 100; 
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    public function mdosummaryAction()
    {
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $mdo = new Default_Models_MaterialDeliveryOrder();
        $result = $mdo->getMdoSum($prjKode,$sitKode);
        $this->view->result = $result;
    }


    public function prdetailAction()
    {
    	$request = $this->getRequest();

        $noTrans = $request->getParam('trano');
        $popup = $request->getParam('popup');

        if ($popup == '')
            $popup = false;
        $pr = new Default_Models_ProcurementRequest();
        $result = $pr->getPrDetail($noTrans);
        $this->view->result = $result;
        $this->view->popup = $popup;
    }

    public function podetailAction()
    {
    	$request = $this->getRequest();
      
        $noTrans = $request->getParam('trano');
        $popup = $request->getParam('popup');
        
        if ($popup == '')
            $popup = false;
        $po = new Default_Models_ProcurementPod();
        $poh = new Default_Models_ProcurementPoh();
        $result = $po->getPoDetail($noTrans);
        $etc = $poh->getPohDetail($noTrans);
        $this->view->result = $result;
        $this->view->etc = $etc;
        $this->view->popup = $popup;
    }

    //PO PPN Report
    public function poppnAction()
    {
    	$request = $this->getRequest();

        $tgla = $request->getParam('tgl1');
        $tglb = $request->getParam('tgl2');

        $current = $request->getParam('current');
        if ($current == '')
        	$current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
        	$currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
        	$requested = 0;
        	
        $ppn = new Default_Models_ProcurementPoh();
        $result = $ppn->getPoPpn($tgla,$tglb);
        $gtotal = 0;
        $gtotalAll = 0;
        $gtotalPpn = 0;
        foreach ($result as $key => $val)
        {
            if ($val['val_kode'] != 'IDR')
            {
                $gtotalPpn += ($val['ppn'] * $val['rateidr']);
                $gtotal += ($val['jumlah'] * $val['rateidr']);
            }
            else
            {
                $gtotalPpn += $val['ppn'];
                $gtotal += $val['jumlah'];
            }


        }
        $gtotalAll += ($gtotal + $gtotalPpn);

        $this->view->grandTotal = $gtotal;
        $this->view->grandTotalPpn = $gtotalPpn;
        $this->view->grandTotalAll = $gtotalAll;
        $this->view->limitPerPage = 100; 
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    //RPI Detail Report
    public function rpidetailAction()
    {
    	$request = $this->getRequest();

        $noTrans = $request->getParam('trano');

        $rpi = new Default_Models_RequestPaymentInvoice();
        $result = $rpi->getrpiDetail($noTrans);
        $this->view->result = $result;
    }

    public function mdidetailAction()
    {
    	$request = $this->getRequest();

        $noTrans = $request->getParam('trano');

        $mdi = new Default_Models_MaterialDeliveryInstruction();
        $result = $mdi->getMdiDetail($noTrans);
        $this->view->result = $result;
    }

    public function mdodetailAction()
    {
    	$request = $this->getRequest();

        $noTrans = $request->getParam('trano');

        $mdo = new Default_Models_MaterialDeliveryOrder();
        $result = $mdo->getMdoDetail($noTrans);
        $this->view->result = $result;
    }

    public function dodetailAction()
    {
    	$request = $this->getRequest();

        $noTrans = $request->getParam('trano');

        $do = new Default_Models_DeliveryOrder();
        $result = $do->getDoDetail($noTrans);
        $this->view->result = $result;
    }

    public function showpodetailAction()
    {

    }

    public function showarfasfAction()
    {

    }

    public function showarfsummaryAction()
    {

    }

    public function showasfsummaryAction()
    {

    }

    public function showarfdetailAction()
    {

    }

    public function showasfdetailAction()
    {

    }

    public function showprsummaryAction()
    {

    }

    public function showposummaryAction()
    {

    }

    public function showpoppnAction()
    {

    }

    public function showrpisummaryAction()
    {

    }

    public function showrpidetailAction()
    {

    }

    public function showprdetailAction()
    {

    }

    public function showporpiAction()
    {

    }
    
    public function showmdimdoAction()
    {

    }

    public function showmdodoAction()
    {

    }

    public function showwhreturnAction()
    {

    }

    public function showmdosummaryAction()
    {

    }

    public function whreturnAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
       // $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));

        $result = $this->transaction->getWhreturn($prjKode,$sitKode);
        $this->view->result = $result;
    }
    
    public function showwhbringbackAction()
    {

    }

    public function whbringbackAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
       // $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));

        $result = $this->transaction->getWhbringback($prjKode,$sitKode);
        $this->view->result = $result;
    }
    
    public function showwhsupplierAction()
    {

    }
    public function showwhsupplierprjAction()
    {

    }
    public function whsupplierprjAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $result = $this->transaction->getWhsupplierprj($prjKode);
        $this->view->result = $result;
     }
    public function whsupplierAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $tgl = $request->getParam('tgl');
        $param = $request->getParam('param');
//        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
//        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));

//        $result = $this->transaction->getWhsupplier($prjKode,$sitKode,$stgl1,$stgl2);
        $result = $this->transaction->getWhsupplier($prjKode,$sitKode,$supKode,$tgl,$param);
        $this->view->result = $result;
    }

    
    public function showdetailprojectbudgetAction()
    {
    	
    }
    
    public function detailprojectbudgetAction()
    {
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
    	$result = $this->budget->getBoq3('boq3-gabung',$prjKode,$sitKode);
        $this->view->result = $result;
    }

    public function detailprAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
 
        $result = $this->transaction->getDetailpr($prjKode,$sitKode);
        $this->view->result = $result;
    }

    public function summaryprAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->transaction->getDetailpr($prjKode,$sitKode);
        $this->view->result = $result;
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
    }

    public function outprpoAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->transaction->getOutprpo($prjKode,$sitKode);
        $this->view->result = $result;
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;

//             var_dump('Project dan Site');die();
//             $result = $this->transaction->getOutprpoprj($prjKode);
//             $this->view->result = $result;
//             $this->view->prjKode = $prjKode;     

    }
    public function outprpoprjAction()
    {
      
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $result = $this->transaction->getOutprpoprj($prjKode);
        $this->view->result = $result;
        $this->view->prjKode = $prjKode;

    }



    public function mdiAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
//        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
//        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
//        $result = $this->transaction->getMdi($prjKode,$sitKode,$stgl1,$stgl2);
        $result = $this->transaction->getMdi($prjKode,$sitKode);
        $this->view->result = $result;
//        $this->view->prjKode = $prjKode;
//        $this->view->sitKode = $sitKode;

    }

    public function dorAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = $this->transaction->getDor($prjKode,$sitKode);
        $this->view->result = $result;


    }

    public function showdoAction()
    {

    }

    public function showdosummaryAction()
    {

    }

    public function dosummaryAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = $this->transaction->getDo($prjKode,$sitKode);
        $this->view->result = $result;
    }

    public function showmdidetailAction()
    {

    }

    public function showmdodetailAction()
    {

    }

    public function showdodetailAction()
    {

    }


    public function doAction()
    {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
//        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
//        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
//        $result = $this->transaction->getMdi($prjKode,$sitKode,$stgl1,$stgl2);
        $result = $this->transaction->getDo($prjKode,$sitKode);
        $this->view->result = $result;
//        $this->view->prjKode = $prjKode;
//        $this->view->sitKode = $sitKode;

    }

   public function showbarangAction()
   {

   }

   public function showformbarangAction()
   {

   }

   public function showoutporpiAction()
   {

   }
	public function showoutprpodetAction()
   {

   }
      public function outprpodetAction()
   {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;

   }
    public function getdataprpodetAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $hasil = $this->transaction->getoutprpo($prjKode,$sitKode);
        $result['posts'] = $hasil;
        $result['count'] = count($result['posts']);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);
//        $json = str_replace("\\",'',$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }


   public function showoutmdimdoAction()
   {

   }

   public function showoutmdodoAction()
   {

   }

    public function historypriceAction()
    {
        $request = $this->getRequest();

        $kodeBrg = $request->getParam('kode_brg');
        $popup = $request->getParam('popup');

        if ($popup == '')
            $popup = false;

        $sql = "SELECT DATE_FORMAT(tgl,'%Y-%m-%d') as tgl,kode_brg,nama_brg,prj_kode,prj_nama,sit_kode,sit_nama,harga,petugas,sup_kode,sup_nama,val_kode FROM procurement_pod where kode_brg = '$kodeBrg' order by tgl desc LIMIT 5";
        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();
        $this->view->result = $result;
        $this->view->popup = $popup;
    }

    function super_unique($data)
    {
      $result = array_map("unserialize", array_unique(array_map("serialize", $data)));

      foreach ($result as $key => $value)
      {
        if ( is_array($value) )
        {
          $result[$key] = $this->super_unique($value);
        }
      }

      return $result;
    }

    public function historypoAction ()
    {
        $trano = $this->getRequest()->getParam('trano');

        $this->view->trano = $trano;
        $log = $this->logtrans->fetchAll("trano = '$trano'",array("tgl ASC"));

        if ($log)
        {
            $log = $log->toArray();
        }

//        var_dump($log);die;

        $hitung = 0;;

        foreach($log as $key => $val )
        {
            $json = $val['data_before'];
            $data2 = Zend_Json::decode($json);

            foreach($data2['po-detail-before'] as $key3 => $val3)
            {
                $ygtampil[$hitung][] = $val3;
            }
            if ($hitung > 0)
            {
                $json = $val['data_after'];
                $data2 = Zend_Json::decode($json);
                
                foreach($data2['po-detail-after'] as $key2 => $val2)
                {
                    $ygtampil[$hitung][] = $val2;
                }

            }
            else
            {
            }

            $hitung++;

        }

//        var_dump($ygtampil);die;
//        return $return;


    }
}
?>