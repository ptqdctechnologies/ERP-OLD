<?php
/**
 * JasperReportController
 * 
 * @author = Wisu
 * @version
 */

class JasperController extends Zend_Controller_Action
{
    private $conn;
    private $reportPath; 
    private $javaOutputStream; 
    private $dbhost,$dbname,$dbuser,$dbpass;
    private $project;
    private $workflow;
    private $session;
    private $budget;
    private $transaction;
    private $nota_debit_detail;
    private $nota_debit_header;
    private $MsProject;
    private $logprint;
    private $utility;
    private $arfh;
    private $arfd;
    private $rpih;
    private $rpid;
    private $paymentarf;
    private $paymentrpi;
    private $voucher;
    private $token;
    private $db;
    
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
       	$this->session = new Zend_Session_Namespace('login');

        $this->project = $this->_helper->getHelper('project');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->utility = $this->_helper->getHelper('utility');
        $this->token = $this->_helper->getHelper('token');

        
		$options = $this->getInvokeArg('bootstrap')->getOptions();
		
		$this->reportPath = $options['jasper']['reportPath'];
		$this->javaBridge = $options['jasper']['javaBridge'];
		
		$this->dbhost = $options['resources']['db']['params']['host'];
		$this->dbname = $options['resources']['db']['params']['dbname'];
		$this->dbuser = $options['resources']['db']['params']['username'];
		$this->dbpass = $options['resources']['db']['params']['password'];

		include_once($this->javaBridge."java/Java.inc");
		
		$this->javaOutputStream = new java("java.io.ByteArrayOutputStream");
//		$this->budget = $this->_helper->getHelper('budget');
        $this->budget = new Default_Models_Budget();
		$this->transaction = $this->_helper->getHelper('transaction');

        $this->arfh = new Procurement_Model_Procurementarfh();
        $this->arfd = new Procurement_Model_Procurementarfd();
        $this->rpid = new Default_Models_RequestPaymentInvoice();
        $this->rpih = new Default_Models_RequestPaymentInvoiceH();

        $this->paymentarf = new Finance_Models_PaymentARF();
        $this->paymentrpi = new Finance_Models_PaymentRPI();

        $this->voucher = new Finance_Models_BankPaymentVoucher();
        $this->db = Zend_Registry::get('db');
    }
    
    private function getParam($params)
    {
    	$newParam = new Java("java.util.HashMap");
    	foreach ($params as $param => $value)
    	{
            $newParam->put($param, $value);
    	}
    	$newParam->put('SUBREPORT_DIR',$this->reportPath);
		return $newParam;
    }

    private function getReport($type='pdf',$params,$filename='report.jrmxl',$outputname='report')
    {
    	try
    	{
    		$Conn = new Java("org.altic.jasperReports.JdbcConnection"); 
			$Conn->setDriver("com.mysql.jdbc.Driver");
			$Conn->setConnectString("jdbc:mysql://" . $this->dbhost . "/" . $this->dbname);
			$Conn->setUser($this->dbuser);
			$Conn->setPassword($this->dbpass);
			
			$arrayParam = $this->getParam($params);
			$compileManager = new JavaClass("net.sf.jasperreports.engine.JasperCompileManager");
			$report = $compileManager->compileReport($this->reportPath . $filename);
			
			$fillManager = new JavaClass("net.sf.jasperreports.engine.JasperFillManager");
			$jasperPrint = $fillManager->fillReport($report, $arrayParam,$Conn->getConnection());
			
			$outputPath =$this->reportPath . $outputname . '.' . $type;
			
			set_time_limit(120); 
 
        	java_set_file_encoding("ISO-8859-1"); 
 			if ($type == 'pdf')
 			{
		        $exporter = new java("net.sf.jasperreports.engine.export.JRPdfExporter"); 
		        $exParm = java("net.sf.jasperreports.engine.JRExporterParameter"); 
		        $exporter->setParameter($exParm->JASPER_PRINT, $jasperPrint); 
		        $exporter->setParameter($exParm->OUTPUT_STREAM, $this->javaOutputStream); 
		        $exporter->exportReport(); 
		 
		        header('Content-Type: application/pdf'); 
		        header('Content-Transfer-Encoding: binary'); 
		        header('Content-disposition: attachment; filename="'.$outputname.'.pdf"'); 
		        header('Pragma: no-cache'); 
		        header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); 
		        header('Expires: 0'); 
		 
		        echo java_cast($this->javaOutputStream->toByteArray(),"S"); 
		
 			}
 			elseif ($type == 'xls')
 			{
 			$exporter = new java("net.sf.jasperreports.engine.export.JExcelApiExporter");
		        $exParm = java("net.sf.jasperreports.engine.JRExporterParameter"); 
		        $exXlsParm = java("net.sf.jasperreports.engine.export.JRXlsExporterParameter");
		        $exporter->setParameter($exParm->JASPER_PRINT, $jasperPrint); 
		        $exporter->setParameter($exParm->OUTPUT_STREAM, $this->javaOutputStream); 
		        $exporter->setParameter($exXlsParm->IS_ONE_PAGE_PER_SHEET, false);
		        $exporter->setParameter($exXlsParm->IS_REMOVE_EMPTY_SPACE_BETWEEN_ROWS, true);
		        $exporter->setParameter($exXlsParm->IS_REMOVE_EMPTY_SPACE_BETWEEN_COLUMNS, true);
		        $exporter->setParameter($exXlsParm->IS_DETECT_CELL_TYPE, true);
		        $exporter->setParameter($exXlsParm->IS_WHITE_PAGE_BACKGROUND, false);
		        $exporter->exportReport(); 
		 
		        header('Content-Type: application/xls'); 
		        header('Content-Transfer-Encoding: binary'); 
		        header('Content-disposition: attachment; filename="'.$outputname.'.xls"'); 
		        header('Pragma: no-cache'); 
		        header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); 
		        header('Expires: 0'); 
 
        echo java_cast($this->javaOutputStream->toByteArray(),"S"); 
 			}			
    	}catch( JavaException $e){
			echo $e;
			die();
		}	
    }
    

	private function getReportNoDataSource($type='pdf',$data,$params,$filename='report.jrmxl',$outputname='report')
   	{
    	try
    	{
    		$ds = new java("net.sf.jasperreports.engine.data.JRBeanCollectionDataSource",$data);
			$arrayParam = $this->getParam($params);
			$compileManager = new JavaClass("net.sf.jasperreports.engine.JasperCompileManager");
			$report = $compileManager->compileReport($this->reportPath . $filename);
			
			$fillManager = new JavaClass("net.sf.jasperreports.engine.JasperFillManager");
			$jasperPrint = $fillManager->fillReport($report, $arrayParam,$ds);
			
			$outputPath =$this->reportPath . $outputname . '.' . $type;
			
			set_time_limit(120); 
 
        	java_set_file_encoding("ISO-8859-1"); 
 			if ($type == 'pdf')
 			{
		        $exporter = new java("net.sf.jasperreports.engine.export.JRPdfExporter"); 
		        $exParm = java("net.sf.jasperreports.engine.JRExporterParameter"); 
		        $exporter->setParameter($exParm->JASPER_PRINT, $jasperPrint); 
		        $exporter->setParameter($exParm->OUTPUT_STREAM, $this->javaOutputStream); 
		        $exporter->exportReport(); 
		 
		        header('Content-Type: application/pdf'); 
		        header('Content-Transfer-Encoding: binary'); 
		        header('Content-disposition: attachment; filename="'.$outputname.'.pdf"'); 
		        header('Pragma: no-cache'); 
		        header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); 
		        header('Expires: 0'); 
		 
		        echo java_cast($this->javaOutputStream->toByteArray(),"S"); 
		
 			}
 			elseif ($type == 'xls')
 			{
 				$exporter = new java("net.sf.jasperreports.engine.export.JExcelApiExporter"); 
		        $exParm = java("net.sf.jasperreports.engine.JRExporterParameter"); 
		        $exXlsParm = java("net.sf.jasperreports.engine.export.JRXlsExporterParameter");
		        $exporter->setParameter($exParm->JASPER_PRINT, $jasperPrint); 
		        $exporter->setParameter($exParm->OUTPUT_STREAM, $this->javaOutputStream); 
		        $exporter->setParameter($exXlsParm->IS_ONE_PAGE_PER_SHEET, false);
		        $exporter->setParameter($exXlsParm->IS_REMOVE_EMPTY_SPACE_BETWEEN_ROWS, true);
		        $exporter->setParameter($exXlsParm->IS_DETECT_CELL_TYPE, true);
		        $exporter->setParameter($exXlsParm->IS_WHITE_PAGE_BACKGROUND, false);
		        $exporter->exportReport(); 
		 
		        header('Content-Type: application/xls'); 
		        header('Content-Transfer-Encoding: binary'); 
		        header('Content-disposition: attachment; filename="'.$outputname.'.xls"'); 
		        header('Pragma: no-cache'); 
		        header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); 
		        header('Expires: 0'); 
 
        echo java_cast($this->javaOutputStream->toByteArray(),"S"); 
 			}			
    	}catch( JavaException $e){
			echo $e;
			die();
		}	
    }
    
    public function boq3Action()
    {
    	
	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');	
        $type = $request->getParam('type');	
    	if ($type == '')
    		$type == 'pdf';	
	        
        $projectDetail = $this->project->getProjectDetail($prjKode);
        $siteDetail = $this->project->getSiteDetail($prjKode,$sitKode);
        $cusDetail = $this->project->getCustomerDetail($projectDetail['cus_kode']);
        
    	$params = array('prjKode' => $projectDetail['Prj_Kode'],
    					'sitKode' => $siteDetail['sit_kode'],
    					'prjNama' => $projectDetail['Prj_Nama'],
    					'sitNama' => $siteDetail['sit_nama'],
    					'cusCode' => $cusDetail['cus_kode'],
    					'cusNama' => $cusDetail['cus_nama'],
    					'user' => $this->session->userName,
    					'date' => date('d-m-Y'),
    					'time' => date('h:i:s'),
    					'signature' => md5($this->session->userName.date('d-m-Y').date('h:i:s'))
    				);		
		$outputPath = $this->getReport($type,$params,'boq3.jrxml','boq3');
	    	
    }

    //boq3 revisi
    public function boq3revisiAction()
    {
    	
	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');	
        $type = $request->getParam('type');	
    	if ($type == '')
    		$type == 'pdf';	
	        
        $projectDetail = $this->project->getProjectDetail($prjKode);
        $siteDetail = $this->project->getSiteDetail($prjKode,$sitKode);
        $cusDetail = $this->project->getCustomerDetail($projectDetail['cus_kode']);
        
    	$params = array('prjKode' => $projectDetail['Prj_Kode'],
    					'sitKode' => $siteDetail['sit_kode'],
    					'prjNama' => $projectDetail['Prj_Nama'],
    					'sitNama' => $siteDetail['sit_nama'],
    					'cusCode' => $cusDetail['cus_kode'],
    					'cusNama' => $cusDetail['cus_nama'],
    					'user' => $this->session->userName,
    					'date' => date('d-m-Y'),
    					'time' => date('h:i:s'),
    					'signature' => md5($this->session->userName.date('d-m-Y').date('h:i:s'))
    				);		
		$outputPath = $this->getReport($type,$params,'boq3revisi.jrxml','boq3revisi');
	    	
    }

    //ARF Summary Report
    public function arfsummaryAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $requestor = $request->getParam('requestor');
        if ($prjKode != '')
        	$where = "a.prj_kode = '$prjKode'";
        if ($sitKode != '' && $prjKode != '')
        	$where .= " AND a.sit_kode='$sitKode' ";
        if ($requestor != '')
        {
        	if ($where != '')
        		$where .= " AND b.request='$requestor' ";
        	else
        		$where = " b.request='$requestor' ";
        }
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $data = new Java("java.util.ArrayList");
        
        $sql = "SELECT
                    p.trano,
                    DATE_FORMAT(p.tgl, '%m/%d/%Y') as tgl,
                    p.prj_kode,
                    p.prj_nama,
                    p.sit_kode,
                    p.sit_nama,
                    p.workid,
                    p.workname,
                    p.val_kode,
                    SUM(p.total) as total,
                    p.pc_nama
                FROM(
                        SELECT
                            a.trano,
                            a.tgl,
                            a.prj_kode,
                            a.prj_nama,
                            a.sit_kode,
                            a.sit_nama,
                            a.workid,
                            a.workname,
                            a.val_kode,
                            a.total,
                            (SELECT uid FROM master_login WHERE id = b.request) as pc_nama
                        FROM
                            procurement_arfd a
                        LEFT JOIN
                            procurement_arfh b
                        ON
                           (a.trano = b.trano AND
                            a.prj_kode = b.prj_kode AND
                            a.sit_kode = b.sit_kode)
                        WHERE
                        	$where
                            ) p
                GROUP BY p.trano
                ORDER BY p.trano ";
        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();
        
        $total = 0;
        foreach($result as $key => $val)
        {
	        $data->add(array('trano' => $val['trano'],
							'tgl' => $val['tgl'],
							'prj_kode' => $val['prj_kode'],
							'prj_nama' => $val['prj_nama'],
							'sit_kode' => $val['sit_kode'],
							'sit_nama' => $val['sit_nama'],
							'workid' => $val['workid'],
							'workname' => $val['workname'],
							'val_kode' => $val['val_kode'],
							'pc_nama' => $val['pc_nama'],
							'total' => number_format($val['total'])
						));
			$sitKode = $val['sit_kode'];
			$total += $val['total'];
        }
//        print_r(java_values($data));die;
        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode,
	    					'total' => number_format($total));
	    			//'basePath' => $basePath);
//	    	$outputPath = $this->getReport($type,$params,'rpt_sum_arf.jrxml','ARF Summary Report');
		$outputPath = $this->getReportNoDataSource($type,$data,$params,'rpt_sum_arf.jrxml','ARF Summary Report');	
   
    }
    
    //ASF Summary Report
    public function asfsummaryAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode);
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_sum_asf.jrxml','ASF Summary Report');
    }

    //PR Summary Report
    public function prsummaryAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode);
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_sum_pr.jrxml','PR Summary Report');
    }

    //PO Summary Report
    public function posummaryAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        $supKode = $request->getParam('sup_kode');
        if ($request->getParam('tgl1') != '')
            $tgl1 = date("Y-m-d",strtotime(urldecode($request->getParam('tgl1'))));
        if ($request->getParam('tgl2') != '')
            $tgl2 = date("Y-m-d",strtotime(urldecode($request->getParam('tgl2'))));
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $data = new Java("java.util.ArrayList");

        $hasil = $this->transaction->getPoSummary($prjKode,$sitKode,$supKode,$tgl1,$tgl2);
        $totIDR = 0;
        $totUSD = 0;
        foreach($hasil as $key => $val)
        {
            if ($val['pc_nama'] == '')
                $val['pc_nama'] = 'NONAME';
            $data->add(array("sit_kode" => $val['sit_kode']
		    			,"sit_nama" => $val['sit_nama']
		    			,'trano' => $val['trano']
		    			,'tgl' => date('d-m-Y',strtotime($val['tgl']))
                        ,'workid' => $val['workid']
                        ,'workname' => $val['workname']
                        ,'total_IDR' => number_format($val['total_IDR'],2)
                        ,'total_USD' => number_format($val['total_USD'],2)
                        ,'pc_nama' => $val['pc_nama']    
		    			));
            $totIDR += $val['total_IDR'];
            $totUSD += $val['total_USD'];
        }
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'prjNama'  => $hasil[0]['prj_nama'],
                                'totIDR' => number_format($totIDR,2),
                                'totUSD' => number_format($totUSD,2));
	    			//'basePath' => $basePath);
        $outputPath = $this->getReportNoDataSource($type,$data,$params,'rpt_sum_po.jrxml','PO Summary Report');
//	    	$outputPath = $this->getReport($type,$params,'rpt_sum_po.jrxml','PO Summary Report');
    }

    //PO PPN Summary Report
    public function poppnAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $tgl1 = $request->getParam('tgl1');
        $tgl2 = $request->getParam('tgl2');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('tgl1'  => $tgl1,
                                'tgl2'  => $tgl2);
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_poppn.jrxml','PO PPN Summary Report');
    }

    //RPI Summary Report
    public function rpisummaryAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode);
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_sum_rpi.jrxml','RPI Summary Report');
    }

    //ARF Detail Report
    public function arfdetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        //$prjKode = $request->getParam('prj_kode');
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        $basePath = $this->reportPath . '/images/Qdc21.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
        
	    	$params = array('noTrans'  => $noTrans,
	    			'basePath' => $basePath);
        if ($type == 'pdf') {  

	    	$outputPath = $this->getReport($type,$params,'rpt_detail_arf.jrxml','ARF Detail Report');
        }else{
                $outputPath = $this->getReport($type,$params,'rpt_detail_arf_excel.jrxml','ARF Detail Report');
        }
    }

    //ASF Detail Report
    public function asfdetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'noTrans'  => $noTrans);
	    			//'basePath' => $basePath);
        if ($type == 'pdf') {

	    	$outputPath = $this->getReport($type,$params,'rpt_detail_asf.jrxml','ASF Detail Report');
        }else{
                $outputPath = $this->getReport($type,$params,'rpt_detail_asf_excel.jrxml','ASF Detail Report');
        }	    	
    }

    //PR Detail Report
    public function prdetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        $basePath = $this->reportPath . '/images/Qdc21.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'noTrans'  => $noTrans,
	    			'basePath' => $basePath);
        if ($type == 'pdf') {

	    	$outputPath = $this->getReport($type,$params,'rpt_detail_pr.jrxml','PR Detail Report');
        }else{
                $outputPath = $this->getReport($type,$params,'rpt_detail_pr_excel.jrxml','PR Detail Report');
        }
	    	
    }

    //PO Detail Report
    public function podetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
        $ldapdir = new Default_Models_Ldap();
    	$request = $this->getRequest();
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');

        //get alamat from master pt table
        $sql ="SELECT CONCAT(nama,'  ',alamat1,'  ',alamat2,' - ',master_kota,'  ',negara,' Telp: ',tlp,' Fax: ',fax) as invoiceTo FROM master_pt";
        $fetch = $this->db->query($sql);
        $data = $fetch->fetchAll();

        $sqls = "SELECT * FROM procurement_poh WHERE trano = '$noTrans'";
        $fetchs = $this->db->query($sqls);
        $datas = $fetchs->fetch();
        $pic = $datas['petugas'];
        $tglKirim = $datas['tgldeliesti'];

        if ($tglKirim == '2000-01-01' || $tglKirim == '1970-01-01')
        {
            $tglKirim = '';
        }
        else
        {
            $tglKirim = date("d M Y",strtotime($tglKirim));
        }
        $picname = $ldapdir->getAccount($pic);
        $pic = $picname['displayname'][0];

        $printname = $ldapdir->getAccount($this->session->userName);
        $print = $printname['displayname'][0];
        
        $basePath = $this->reportPath . '/images/Qdc22.jpg';

        $type = $request->getParam('type');
        //approval history
        $sqls = "SELECT * FROM workflow_trans WHERE (approve = 200 OR approve = 400) AND item_id = '$noTrans' GROUP BY item_id,workflow_id,uid ORDER BY date DESC";
        $fetchs = $this->db->query($sqls);
        $datas = $fetchs->fetchAll();


        foreach ($datas as $key => $val)
        {
            if ($val['uid'] == 'jonhar')
            {
                $approve['od'] = date('d M Y',strtotime($val['date']));
                $approve['odsign'] = $val['signature'];
                $name = $ldapdir->getAccount($val['uid']);
                $approve['odname'] = $name['displayname'][0];
            }
            else
            {
                $roleType = $this->workflow->getUserCreByWorkflowItemId($val['workflow_id']);
                if ($roleType)
                {
                    switch($roleType['display_name'])
                    {
                        case 'Project Manager':
                            $approve['pm'] = date('d M Y',strtotime($val['date']));
                            $approve['pmsign'] = $val['signature'];
                            $name = $ldapdir->getAccount($val['uid']);
                            $approve['pmname'] = $name['displayname'][0];
                        break;
                        case 'Procurement Manager':
                            $approve['prom'] = date('d M Y',strtotime($val['date']));
                            $approve['promsign'] = $val['signature'];
                            $name = $ldapdir->getAccount($val['uid']);
                            $approve['promname'] = $name['displayname'][0];
                        break;
                        case 'Operations Director':
                            $approve['od'] = date('d M Y',strtotime($val['date']));
                            $approve['odsign'] = $val['signature'];
                            $name = $ldapdir->getAccount($val['uid']);
                            $approve['odname'] = $name['displayname'][0];
                        break;
                        case 'Construction GM':
                            $approve['gm'] = date('d M Y',strtotime($val['date']));
                            $approve['gmsign'] = $val['signature'];
                            $name = $ldapdir->getAccount($val['uid']);
                            $approve['gmname'] = $name['displayname'][0];
                        break;
                        case 'GM Finance & Accounting':
                            $approve['fm'] = date('d M Y',strtotime($val['date']));
                            $approve['fmsign'] = $val['signature'];
                            $name = $ldapdir->getAccount($val['uid']);
                            $approve['fmname'] = $name['displayname'][0];
                        break;
                        case 'President Director':
                            $approve['dir'] = date('d M Y',strtotime($val['date']));
                            $approve['dirsign'] = $val['signature'];
                            $name = $ldapdir->getAccount($val['uid']);
                            $approve['dirname'] = $name['displayname'][0];
                        break;
                    }
                }
            }
        }

    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'noTrans'  => $noTrans,
                                'invQdc'   => $data[0]['invoiceTo'],
                                'basePath' => $basePath,
                                'pic' => $pic,
                                'print' => $print,
                                'tgldeliesti' => $tglKirim,
                                'pmsign' => $approve['pmsign'],
                                'fmsign' => $approve['fmsign'],
                                'promsign' => $approve['promsign'],
                                'gmsign' => $approve['gmsign'],
                                'dirsign' => $approve['dirsign'],
                                'odsign' => $approve['odsign'],
                                'pm' => $approve['pm'],
                                'fm' => $approve['fm'],
                                'prom' => $approve['prom'],
                                'gm' => $approve['gm'],
                                'dir' => $approve['dir'],
                                'od' => $approve['od'],
                                'pmname' => $approve['pmname'],
                                'fmname' => $approve['fmname'],
                                'promname' => $approve['promname'],
                                'gmname' => $approve['gmname'],
                                'dirname' => $approve['dirname'],
                                'odname' => $approve['odname']
            );
        if ($type == 'pdf') {
//	    	$outputPath = $this->getReport($type,$params,'rpt_detail_po.jrxml','PO Report');
            QDC_Jasper_Report::factory(
                array(
                    "reportType" => 'pdf',
                    "fileName" => 'rpt_detail_po.jrxml',
                    "outputName" => 'PO Report',
                    "arrayParams" => $params
                )
            )->generate();
        }else{
            $outputPath = $this->getReport($type,$params,'rpt_detail_po_excel.jrxml','PO Detail Report');
        }

   }

    //RPI Detail Report
    public function rpidetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'noTrans'  => $noTrans);
	    			//'basePath' => $basePath);
    if ($type == 'pdf') {

	    	$outputPath = $this->getReport($type,$params,'rpt_detail_rpi_pdf.jrxml','RPI Report');
        }else{
                $outputPath = $this->getReport($type,$params,'rpt_detail_rpi.jrxml','RPI Report');
        }
    }

    //MDI Detail Report
    public function mdidetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('noTrans'  => $noTrans);
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_detail_mdi.jrxml','MDI Detail Report');
    }

    //MDO Detail Report
    public function mdodetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('noTrans'  => $noTrans);
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_detail_mdo.jrxml','MDO Detail Report');
    }

    //DO Detail Report
    public function dodetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('noTrans'  => $noTrans);
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_detail_do.jrxml','DO Detail Report');
    }

    //ARF&ASF Outstanding Report
    public function arfasfAction()
    { 
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $type = $request->getParam('type');
        $basePath = $this->reportPath . '/images/Qdc2.jpg';
        
        $type = $request->getParam('type');	
    	if ($type == '')
    		$type == 'pdf';	
	    	$this->_helper->viewRenderer->setNoRender();
	    	      
	    	$params = array('prjKode'  => $prjKode,
	    			'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_arfhasfd.jrxml','ARF & ASF Report');
    }
    
    //PO & RPI Report 
    public function porpiAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
	    			'sitKode'  => $sitKode,
	    			'basePath' => $basePath);
    	$hasil = $this->transaction->getPorpi($prjKode,$sitKode,'','','detail-rpi');

    	$data = new Java("java.util.ArrayList");
    	
        $jum = 0;
        $prevPO_no = '';
        $prevWorkid = '';
        $prevKode_brg = '';
        $prevTotal_IDR = 0;
        $prevTotal_USD = 0;
        $prevBalance_IDR = 0;
        $prevBalance_USD = 0;
        
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
        	
//        	if ($prevPO_no != '' && $prevKode_brg != '' && $prevPO_no == $val['po_no'] && $prevKode_brg == $val['kode_brg'])
//        		{
//        			if ($prevTotal_IDR == $val['totalPO_IDR'])
//        			{
//        				$val['totalPO_IDR'] = '';
//        			}
//        			elseif ($prevTotal_USD == $val['totalPO_USD'])
//        			{
//        				$val['totalPO_USD'] = '';
//        			}
//        		}
//        		
//        		$prevPO_no = $val['po_no'];
//        		$prevKode_brg = $val['kode_brg'];
//        		$prevTotal_IDR = $val['totalPO_IDR'];
//        		$prevTotal_USD = $val['totalPO_USD'];
        		
        		$val['totalPO_USD'] = (double) $val['totalPO_USD'];
        		$val['totalPO_IDR'] = (double) $val['totalPO_IDR'];
        		$val['totalRPI_IDR'] = (double) $val['totalRPI_IDR'];
        		$val['totalRPI_USD'] = (double) $val['totalPO_USD'];
        		$val['balanceUSD'] = (double) $val['balanceUSD'];
        		$val['balanceIDR'] = (double) $val['balanceIDR'];
        		
        		$data->add($val);
        		
        }
	    $outputPath = $this->getReportNoDataSource	($type,$data,$params,'rpt_porpi.jrxml','PO & RPI Report');
	    	
//	    	$outputPath = $this->getReport($type,$params,'rpt_porpi.jrxml','PO & RPI Report');
    }

    //MDI to MDO Report per Site
    public function mdimdoAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode,
	    			'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_mdimdo.jrxml','MDI to MDO Report');
    }

    //MDO to DO Report per Site
    public function mdodoAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode,
	    			'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_mdodo.jrxml','MDO to DO Report');
    }

    //PR 
    public function prAction()
    {
    	
	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');	
        $type = $request->getParam('type');	
    	if ($type == '')
    		$type == 'pdf';	
	        
        $projectDetail = $this->project->getProjectDetail($prjKode);
        $siteDetail = $this->project->getSiteDetail($prjKode,$sitKode);
        
    	$params = array('PMprj_kode' => $prjKode,
    					'PMsit_kode' => $sitKode
    				);		
		$outputPath = $this->getReport($type,$params,'pr.jrxml','pr');
	    	
    } 
    

    //Outstanding pr to po report
    public function outprpoAction()
    {
    	
	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');	
        $type = $request->getParam('type');	
    	if ($type == '')
    		$type == 'pdf';	
	        
        $projectDetail = $this->project->getProjectDetail($prjKode);
        $siteDetail = $this->project->getSiteDetail($prjKode,$sitKode);
        $cusDetail = $this->project->getCustomerDetail($projectDetail['cus_kode']);
        
    	$params = array('PMprj_kode' => $prjKode,
    					'PMsit_kode' => $sitKode,
    					'userName' => $this->session->userName
    				);		
		$outputPath = $this->getReport($type,$params,'outprpo.jrxml','outprpo');
	    	
    }
    public function outprpoprjAction()
    {

	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';

        $projectDetail = $this->project->getProjectDetail($prjKode);
//        $siteDetail = $this->project->getSiteDetail($prjKode,$sitKode);
//        $cusDetail = $this->project->getCustomerDetail($projectDetail['cus_kode']);

    	$params = array('PMprj_kode' => $prjKode,
    					'userName' => $this->session->userName
    				);
		$outputPath = $this->getReport($type,$params,'outprpoprj.jrxml','outprpo');

    }

    public function outprpodetAction()
    {

	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';

        $projectDetail = $this->project->getProjectDetail($prjKode);
        $siteDetail = $this->project->getSiteDetail($prjKode,$sitKode);
        $cusDetail = $this->project->getCustomerDetail($projectDetail['cus_kode']);

    	$params = array('PMprj_kode' => $prjKode,
    					'PMsit_kode' => $sitKode
    					
    				);
		$outputPath = $this->getReport($type,$params,'outprpodet.jrxml','outprpo');

    }

    public function outmdimdoAction()
     {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $group = $request->getParam('group');
        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode,
	    			'basePath' => $basePath);

        $data = new Java("java.util.ArrayList");

        $result = $this->transaction->getdortodo($prjKode,$sitKode,$group);
        foreach($result as $k => $v)
        {
            $data->add(array(
                "sit_kode" => $sitKode,
                "prj_kode" => $prjKode,
                "tgl_dor" => $v['tgl'],
                "tgl_do" => $v['tgl_do'],
                "qty_dor" => $v['qty_dor'],
                "qty_do" => $v['qty_do'],
                "balance" => $v['balance'],
                "workid" => $v['workid'],
                "kode_brg" => $v['kode_brg'],
                "nama_brg" => $v['nama_brg'],
                "prj_nama" => $v['prj_nama'],
                "sit_nama" => $v['sit_nama']
            ));
            
        }
//	    	$outputPath = $this->getReport($type,$params,'rpt_mdimdo.jrxml','MDI to MDO Report');
		$outputPath = $this->getReportNoDataSource($type,$data,$params,'rpt_mdimdo.jrxml','DOR to DO Report');
    }

    public function outmdodoAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode,
	    			'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_mdodo.jrxml','MDO to DO Report');
     }


    //wh return
    public function whreturnAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
        //$stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();
                
	    	$params = array('prjKode'  => "$prjKode",
                                'sitKode'  => "$sitKode");
                               // 'stgl1'    => $stgl1,
                               // 'stgl2'    => $stgl2);
	    			//'basePath' => $basePath);
                
	    	$outputPath = $this->getReport($type,$params,'rpt_whreturn.jrxml','Material Return Report');
    
    }
    
    //wh bringback
    public function whbringbackAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode,
                                'stgl1'    => $stgl1,
                                'stgl2'    => $stgl2);
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_whbringback.jrxml','Material Cancel Report');
    
                //var_dump($params);
    }
    
    //wh supplier
    public function whsupplierAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $param = $request->getParam('param');
        $tgl = $request->getParam('tgl');

        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode,
                                'supKode'  => $supKode,
                                'param'    => $param,
                                'tgl' => $tgl
                                );
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_whsupplier.jrxml','Material Delivery Instruction Report');
    
                //var_dump($params);
    }

    public function doAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
//        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
//        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

//	    	$params = array('prjKode'  => $prjKode,
//                                'sitKode'  => $sitKode,
//                                'stgl1'    => $stgl1,
//                                'stgl2'    => $stgl2);

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode);

                //'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_do.jrxml','Delivery Order Report');

                //var_dump($params);
    }

     public function dorAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
//        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
//        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

//	    	$params = array('prjKode'  => $prjKode,
//                                'sitKode'  => $sitKode,
//                                'stgl1'    => $stgl1,
//                                'stgl2'    => $stgl2);

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode);

                //'basePath' => $basePath);
	    	$outputPath = $this->getReport($type,$params,'rpt_dor.jrxml','Delivery Order Summary Report');

                //var_dump($params);
    }

    public function mdiAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('PMprj_kode'  => $prjKode,
                                'PMsit_kode'  => $sitKode);
	    	$outputPath = $this->getReport($type,$params,'rmdi.jrxml','Material Delivery Instruction Report');

    }


    //MDO Summary Report
    public function mdosummaryAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$basePath = $this->reportPath . '/images/Qdc2.jpg';

        $type = $request->getParam('type');
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array('prjKode'  => $prjKode,
                                'sitKode'  => $sitKode);
	    	$outputPath = $this->getReport($type,$params,'rpt_sum_mdo.jrxml','MDO Summary Report');

    }

    
    public function progressprojectbudgetAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        
        $type = $request->getParam('type');
        
        $data = new Java("java.util.ArrayList");
    	$totalBoq2_ori=0;
    	$totalBoq2_current=0;
    	$totalBoq3_ori=0;
    	$totalBoq3_current=0;
    	$totalBoq4_current=0;
    	$totalFinal = 0;
    	
    	//Get updated exchange rate from database
       $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			FROM exchange_rate
       			WHERE val_kode='USD'
       			ORDER BY tgl DESC
       			LIMIT 0,1";
       
       $fetch = $this->db->query($sql);
       $datas = $fetch->fetch();     
       
        $grandTotalBoq2 = 0;
    	$grandTotalBoq3 = 0;
    	$grandTotalBoq4 = 0;
    	$grandTotalRet = 0;
    	$grandTotalMip = 0;
    	$grandTotalFinal = 0;
    	$grandTotalBoq2Rate = 0;
    	$grandTotalBoq3Rate = 0;
    	$grandTotalBoq4Rate = 0;
    	$grandTotalRetRate = 0;
    	$grandTotalMipRate = 0;
    	$grandTotalFinalRate = 0;
       
       $rateidr = $datas['rateidr']; 
       $tgl = $datas['tgl_rate']; 
       if ($sitKode == '')
        {
	        $sql = "SELECT
	                    p.prj_kode,
	                    p.prj_nama,
	                    p.tglaw,
	                    s.sit_kode,
	                    s.sit_nama
	                FROM master_project p
	                LEFT JOIN master_site s
	                ON (p.prj_kode = s.prj_kode)
	                WHERE p.Prj_Kode = '$prjKode'
	                ORDER BY s.sit_kode ASC;";
	        $fetch = $this->db->query($sql);
	        $master = $fetch->fetchAll();
	        
	        for ($i=0;$i<count($master);$i++)
        	{
		        $sitKode = $master[$i]['sit_kode'];
		        $result = $this->budget->compareBoq($prjKode,$sitKode);
		        $final = 0;
	    		$finalIDR = 0;
	    		$finalRate = 0;
	    		$grossIDR = 0;
	    		$grossRate = 0;
        		foreach ($result as $key => $val)
		    	{
		    		$sit_kode = $result[$key]['sit_kode'];
	                $sit_nama = $result[$key]['sit_nama'];
	                $prj_nama = $result[$key]['prj_nama'];
		    		$grandTotalBoq2 += ($val['boq2_current']);
		    		$grandTotalBoq3 += ($val['boq3_current']);
		    		$grandTotalBoq4 += ($val['boq4_current']);
		    		$grandTotalMip += ($val['mip_current']);
		    		$grandTotalRet += ($val['return']);
		    		$totBoq2 = ($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr));
		    		$totBoq3 = ($val['boq3_currentHargaIDR'] + ($val['boq3_currentHargaUSD'] * $rateidr));
		    		$totBoq4 = ($val['boq4_currentHargaIDR'] + ($val['boq4_currentHargaUSD'] * $rateidr));
		    		$totRet = ($val['returnHargaIDR'] + ($val['returnHargaUSD'] * $rateidr));
					$totMip = ($val['mip_currentHargaIDR'] + ($val['mip_currentHargaUSD'] * $rateidr));
		    		
					$grandTotalBoq2Rate += $totBoq2;
					$grandTotalBoq3Rate += $totBoq3;
					$grandTotalBoq4Rate += $totBoq4;
					$grandTotalMipRate += $totMip;
					$grandTotalRetRate += $totRet;
					
					$finalIDR = ($val['boq2_current'] - $val['boq3_current'] - $val['return']);
		    		$finalRate = (($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr)) - ($val['boq3_currentHargaIDR'] + ($val['boq3_currentHargaUSD'] * $rateidr)) - ($val['returnHargaIDR'] + ($val['returnHargaUSD'] * $rateidr)));
					
					if ($val['boq2_current'] > 0)
					{
						$grossIDR = number_format(((($$val['boq2_current'] - $finalIDR) / ($val['boq2_current']))*100),2);
		    			$grossRate = number_format((((($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr)) - $finalRate) / (($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr))))*100),2);
					}
		    		else 
		    		{
		    			$grossIDR = 0;
		    			$grossRate = 0;
		    		}
		    		
		    		$data->add(array("sit_kode" => $sit_kode
		    			,"sit_nama" => $sit_nama
		    			,'boq2_currentHargaIDR' => number_format($val['boq2_currentHargaIDR'],2)
		    			,'boq2_currentHargaUSD' => number_format($val['boq2_currentHargaUSD'],2)
		    			,'boq3_currentHargaIDR' => number_format($val['boq3_currentHargaIDR'],2)
		    			,'boq3_currentHargaUSD' => number_format($val['boq3_currentHargaUSD'],2)
		    			,'boq4_currentHargaIDR' => number_format($val['boq4_currentHargaIDR'],2)
		    			,'boq4_currentHargaUSD' => number_format($val['boq4_currentHargaUSD'],2) 
		    			,'mip_currentIDR' => number_format($val['mip_currentHargaIDR'],2)
		    			,'mip_currentUSD' => number_format($val['mip_currentHargaUSD'],2)
		    			,'returnHargaIDR' => number_format($val['returnHargaIDR'],2)
		    			,'returnHargaUSD' => number_format($val['returnHargaUSD'],2)
		    			,'final_rate' => number_format($finalRate,2)
		    			,'gross' => number_format($grossRate,2) . '%'
		    			,'totBoq2' => number_format($totBoq2,2)
		    			,'totBoq3' => number_format($totBoq3,2)
		    			,'totBoq4' => number_format($totBoq4,2)
		    			,'totMip' => number_format($totMip,2)
		    			,'totReturn' => number_format($totRet,2)
		    			));
		    			
		    		$grandTotalFinal +=$finalIDR;
		    		$grandTotalFinalRate +=$finalRate;
		    	}
        	}
        	if ($grandTotalBoq2Rate > 0 && $grandTotalFinalRate > 0)
    		$totalGross = number_format((($grandTotalBoq2Rate - $grandTotalFinalRate) / ($grandTotalBoq2Rate)*100),2);
        }
        else
        {
        	$result = $this->budget->compareBoq($prjKode,$sitKode);
	        $final = 0;
    		$finalIDR = 0;
    		$finalRate = 0;
    		$grossIDR = 0;
    		$grossRate = 0;
        	foreach ($result as $key => $val)
	    	{
	    		
	    		$sit_kode = $result[$key]['sit_kode'];
                $sit_nama = $result[$key]['sit_nama'];
                $prj_nama = $result[$key]['prj_nama'];
	    		
	    		$grandTotalBoq2 += ($val['boq2_current']);
	    		$grandTotalBoq3 += ($val['boq3_current']);
	    		$grandTotalBoq4 += ($val['boq4_current']);
	    		$grandTotalMip += ($val['mip_current']);
	    		$grandTotalRet += ($val['return']);
	    		$totBoq2 = ($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr));
	    		$totBoq3 = ($val['boq3_currentHargaIDR'] + ($val['boq3_currentHargaUSD'] * $rateidr));
	    		$totBoq4 = ($val['boq4_currentHargaIDR'] + ($val['boq4_currentHargaUSD'] * $rateidr));
	    		$totRet = ($val['returnHargaIDR'] + ($val['returnHargaUSD'] * $rateidr));
				$totMip = ($val['mip_currentHargaIDR'] + ($val['mip_currentHargaUSD'] * $rateidr));
	    		
				$grandTotalBoq2Rate += $totBoq2;
				$grandTotalBoq3Rate += $totBoq3;
				$grandTotalBoq4Rate += $totBoq4;
				$grandTotalMipRate += $totMip;
				$grandTotalRetRate += $totRet;
				
				$finalIDR = ($val['boq2_current'] - $val['boq3_current'] - $val['return']);
	    		$finalRate = (($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr)) - ($val['boq3_currentHargaIDR'] + ($val['boq3_currentHargaUSD'] * $rateidr)) - ($val['returnHargaIDR'] + ($val['returnHargaUSD'] * $rateidr)));
				
				if ($val['boq2_current'] > 0)
				{
					$grossIDR = number_format(100 - ((($val['boq2_current'] - $val['boq4_current'] + $totRet) / ($val['boq2_current']))*100),2);
					$grossRate = number_format(100 -(((($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $this->rateidr)) - ($val['boq3_currentHargaIDR'] + ($val['boq3_currentHargaUSD'] * $rateidr)) + ($val['returnHargaIDR'] + ($val['returnHargaUSD'] * $rateidr))) / (($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr))))*100),2);
//					$grossIDR = number_format(((($val['boq2_current'] - $finalIDR) / ($val['boq2_current']))*100),2);
//	    			$grossRate = number_format((((($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $this->rateidr)) - $finalRate) / (($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr))))*100),2);
				}
	    		else 
	    		{
	    			$grossIDR = 0;
	    			$grossRate = 0;
	    		}
	    		
	    		$data->add(array("sit_kode" => $sit_kode
		    			,"sit_nama" => $sit_nama
		    			,'boq2_currentHargaIDR' => number_format($val['boq2_currentHargaIDR'],2)
		    			,'boq2_currentHargaUSD' => number_format($val['boq2_currentHargaUSD'],2)
		    			,'boq3_currentHargaIDR' => number_format($val['boq3_currentHargaIDR'],2)
		    			,'boq3_currentHargaUSD' => number_format($val['boq3_currentHargaUSD'],2)
		    			,'boq4_currentHargaIDR' => number_format($val['boq4_currentHargaIDR'],2)
		    			,'boq4_currentHargaUSD' => number_format($val['boq4_currentHargaUSD'],2) 
		    			,'mip_currentIDR' => number_format($val['mip_currentHargaIDR'],2)
		    			,'mip_currentUSD' => number_format($val['mip_currentHargaUSD'],2)
		    			,'returnHargaIDR' => number_format($val['returnHargaIDR'],2)
		    			,'returnHargaUSD' => number_format($val['returnHargaUSD'],2)
		    			,'final_rate' => number_format($finalRate,2)
		    			,'gross' => $grossRate . '%'
		    			,'totBoq2' => number_format($totBoq2,2)
		    			,'totBoq3' => number_format($totBoq3,2)
		    			,'totBoq4' => number_format($totBoq4,2)
		    			,'totMip' => number_format($totMip,2)
		    			,'totReturn' => number_format($totRet,2)));
	    		
	    		$grandTotalFinal +=$finalIDR;
	    		$grandTotalFinalRate +=$finalRate;
	    	}
    		if ($grandTotalBoq2Rate > 0 && $grandTotalFinalRate > 0)
    		$totalGross = number_format(100 - (($grandTotalBoq2Rate - $grandTotalBoq3Rate + $grandTotalRetRate) / ($grandTotalBoq2Rate)*100),2);
       
        }
        
        $exchange_rate = "<b>USD Exchange Rate : Rp. $rateidr, Last updated : $tgl</b>";
        $ket_project = "<b>$prjKode - $prj_nama</b><br><b>IDR* = Use updated exchange rate</b>";
	    $params = array(
	    			"gtotalBoq2" => number_format($grandTotalBoq2Rate,2),
	    			"gtotalBoq3" => number_format($grandTotalBoq3Rate,2),
	    			"gtotalBoq4" => number_format($grandTotalBoq4Rate,2),
	    			"gtotalMip" => number_format($grandTotalMipRate,2),
	    			"gtotalReturn" => number_format($grandTotalRetRate,2),
	    			"gtotalFinal" => number_format($grandTotalFinalRate,2),
	    			"gtotalGross" => $totalGross . '%',
	    			"exchange_rate" => $exchange_rate,
	    			"ket_project" => $ket_project
	    		);
		$outputPath = $this->getReportNoDataSource($type,$data,$params,'progressprojectbudget.jrxml','Progress Project Budget');	
   
    }
    
    
    public function projectbudgetAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();
		$show = $request->getParam('show');
        if ($show == 'Detail')
        	$detail=true;
        $rate = $request->getParam('userate');
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $userRate = $request->getParam('userate');
        if ($userRate == '')
        	$userRate = false;
        else
        	$userRate = true;
        $type = $request->getParam('type');
        
        $data = new Java("java.util.ArrayList");
    	$totalBoq2_ori=0;
    	$totalBoq2_current=0;
    	$totalBoq3_ori=0;
    	$totalBoq3_current=0;
    	$totalBoq4_current=0;
    	$totalFinal = 0;
    	
    	//Get updated exchange rate from database
       $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			FROM exchange_rate
       			WHERE val_kode='USD'
       			ORDER BY tgl DESC
       			LIMIT 0,1";
       
       $fetch = $this->db->query($sql);
       $datas = $fetch->fetch();     
       
       $rateidr = $datas['rateidr']; 
       $tgl = $datas['tgl_rate']; 
    	
        if ($sitKode == '')
        {
	        $sql = "SELECT
	                    p.prj_kode,
	                    p.prj_nama,
	                    p.tglaw,
	                    s.sit_kode,
	                    s.sit_nama
	                FROM master_project p
	                LEFT JOIN master_site s
	                ON (p.prj_kode = s.prj_kode)
	                WHERE p.Prj_Kode = '$prjKode'
	                ORDER BY s.sit_kode ASC;";
	        $fetch = $this->db->query($sql);
	        $master = $fetch->fetchAll();
	    	
	        for ($i=0;$i<count($master);$i++)
        	{
		        $sitKode = $master[$i]['sit_kode'];
		        
		    	$result = $this->budget->getBudgetProject(false,$prjKode,$sitKode,$detail);
		    	foreach ($result as $key => $val)
		    	{
		    		if ($result[$key]['stsoverhead'] == 'Y')
	            	{
	            		$result[$key]['boq2_ori'] = 0;
	            		$result[$key]['boq2_current'] = 0;
	            		$result[$key]['boq2_oriIDR'] = 0;
	            		$result[$key]['boq2_oriUSD'] = 0;
	            		$result[$key]['boq2_currentIDR'] = 0;
	            		$result[$key]['boq2_currentUSD'] = 0;
	            	}
		    			if (!$userRate)
		    			{
			    			$boq2_ori = ($result[$key]['boq2_ori'] );
			                $boq2_current = ($result[$key]['boq2_current'] );
			                $boq3_current = ($result[$key]['boq3_current'] );
			                $boq3_ori = ($result[$key]['boq3_ori'] );
			                $mip_current = ($result[$key]['mip_current']);
			                $boq4_current = ($result[$key]['boq4_current']);
			                $finalCost = ($result[$key]['finalCost']);
		    			}
		                else
		                {
		                	$boq2_ori = ($result[$key]['boq2_oriHargaIDR'] + ($result[$key]['boq2_oriHargaUSD'] * $rateidr));
		                	$boq2_current = ($result[$key]['boq2_currentHargaIDR'] + ($result[$key]['boq2_currentHargaUSD'] * $rateidr));
		                	$boq3_current = ($result[$key]['boq3_currentHargaIDR'] + ($result[$key]['boq3_currentHargaUSD'] * $rateidr));
		                	$boq3_ori = ($result[$key]['boq3_oriHargaIDR'] + ($result[$key]['boq3_oriHargaUSD'] * $rateidr));
		                	$mip_current = ($result[$key]['mip_currentHargaIDR'] + ($result[$key]['mip_currenHargatUSD'] * $rateidr));
		                	$boq4_current = ($result[$key]['boq4_currentHargaIDR'] + ($result[$key]['boq4_currentHargaUSD'] * $rateidr));
		               		$finalCost = $result[$key]['finalCost'];
		                	
		                }   
		                $totalBoq2_ori += $boq2_ori;
		                $totalBoq2_current += $boq2_current;
		                $totalBoq3_ori += $boq3_ori;
		                $totalBoq3_current += $boq3_current;
		                $totalBoq4_current += $boq4_current;
		                $totalFinal += $finalCost;
		                
		                $sit_kode = $result[$key]['sit_kode'];
		                $sit_nama = $result[$key]['sit_nama'];
	                	$prj_nama = $result[$key]['prj_nama'];
		                
		    			if (($boq3_ori > 0 || $boq3_ori !='') && ($boq2_ori > 0 || $boq2_ori != ''))
			                {
			                     $jPersen = number_format((($boq2_ori -$boq3_ori) / $boq2_ori) * 100,2,'.','');
			                }
			                else
			                {
			                    $jPersen = 0;
			                }
		    			if (($boq2_current > 0 || $boq2_current!='') && ($boq3_current > 0 || $boq3_current != ''))
			                {
			                    $lPersen =  number_format(((($boq2_current - $boq3_current) / $boq2_current) * 100),2,'.','');
			                }
			                else
			                {
			                    $lPersen = 0;
			                }
		    			if (($boq2_current > 0 || $boq2_current !='') && ($boq3_current > 0 || $boq3_current != '') )
			                {
			                    $nPersen = number_format((($finalCost / $boq2_current) * 100),2,',','');
			                }
			                else
			                {
			                    $nPersen  = 0;
			                }    
		
					$data->add(array("sit_kode" => $sit_kode,"sit_nama" =>$sit_nama,"boq2_ori" => number_format($boq2_ori),"boq2_current" => number_format($boq2_current),"boq3_ori" => number_format($boq3_ori),"boq3_current" => number_format($boq3_current),"boq4_current" => number_format($boq4_current),"gross_ori" => number_format($jPersen,2) . '%',"gross_current" => number_format($lPersen,2). '%'));           
			    }
	        }
        }
        else
        {	
        	$result = $this->budget->getBudgetProject(false,$prjKode,$sitKode,$detail);
	    	foreach ($result as $key => $val)
	    	{
	    			if ($result[$key]['stsoverhead'] == 'Y')
	            	{
	            		$result[$key]['boq2_ori'] = 0;
	            		$result[$key]['boq2_current'] = 0;
	            		$result[$key]['boq2_oriIDR'] = 0;
	            		$result[$key]['boq2_oriUSD'] = 0;
	            		$result[$key]['boq2_currentIDR'] = 0;
	            		$result[$key]['boq2_currentUSD'] = 0;
	            		$result[$key]['boq2_oriHargaIDR'] = 0;
	            		$result[$key]['boq2_oriHargaUSD'] = 0;
	            		$result[$key]['boq2_currentHargaIDR'] = 0;
	            		$result[$key]['boq2_currentHargaUSD'] = 0;
	            	}
	    				if (!$userRate)
		    			{
			    			$boq2_ori = ($result[$key]['boq2_ori'] );
			                $boq2_current = ($result[$key]['boq2_current'] );
			                $boq3_current = ($result[$key]['boq3_current'] );
			                $boq3_ori = ($result[$key]['boq3_ori'] );
			                $mip_current = ($result[$key]['mip_current']);
			                $boq4_current = ($result[$key]['boq4_current']);
			                $finalCost = ($result[$key]['finalCost']);
		    			}
		                else
		                {
		                	$boq2_ori = ($result[$key]['boq2_oriHargaIDR'] + ($result[$key]['boq2_oriHargaUSD'] * $rateidr));
		                	$boq2_current = ($result[$key]['boq2_currentHargaIDR'] + ($result[$key]['boq2_currentHargaUSD'] * $rateidr));
		                	$boq3_current = ($result[$key]['boq3_currentHargaIDR'] + ($result[$key]['boq3_currentHargaUSD'] * $rateidr));
		                	$boq3_ori = ($result[$key]['boq3_oriHargaIDR'] + ($result[$key]['boq3_oriHargaUSD'] * $rateidr));
		                	$mip_current = ($result[$key]['mip_currentHargaIDR'] + ($result[$key]['mip_currenHargatUSD'] * $rateidr));
		                	$boq4_current = ($result[$key]['boq4_currentHargaIDR'] + ($result[$key]['boq4_currentHargaUSD'] * $rateidr));
		               		$finalCost = $result[$key]['finalCost'];
		                	
		                }
	                
	                $totalBoq2_ori += $boq2_ori;
	                $totalBoq2_current += $boq2_current;
	                $totalBoq3_ori += $boq3_ori;
	                $totalBoq3_current += $boq3_current;
	                $totalBoq4_current += $boq4_current;
	                $totalFinal += $finalCost;
	                
	                $sit_kode = $result[$key]['sit_kode'];
	                $sit_nama = $result[$key]['sit_nama'];
	                $prj_nama = $result[$key]['prj_nama'];
	                
	    			if (($boq3_ori > 0 || $boq3_ori !='') && ($boq2_ori > 0 || $boq2_ori != ''))
		                {
		                     $jPersen = number_format((($boq2_ori -$boq3_ori) / $boq2_ori) * 100,2,'.','');
		                }
		                else
		                {
		                    $jPersen = 0;
		                }
	    			if (($boq2_current > 0 || $boq2_current!='') && ($boq3_current > 0 || $boq3_current != ''))
		                {
		                    $lPersen =  number_format(((($boq2_current - $boq3_current) / $boq2_current) * 100),2,'.','');
		                }
		                else
		                {
		                    $lPersen = 0;
		                }
	    			if (($boq2_current > 0 || $boq2_current !='') && ($boq3_current > 0 || $boq3_current != '') )
		                {
		                    $nPersen = number_format((($finalCost / $boq2_current) * 100),2,',','');
		                }
		                else
		                {
		                    $nPersen  = 0;
		                }    
	
				$data->add(array("sit_kode" => $sit_kode,"sit_nama" =>$sit_n100ama,"boq2_ori" => number_format($boq2_ori),"boq2_current" => number_format($boq2_current),"boq3_ori" => number_format($boq3_ori),"boq3_current" => number_format($boq3_current),"boq4_current" => number_format($boq4_current),"gross_ori" => number_format($jPersen,2) . '%',"gross_current" => number_format($lPersen,2). '%'));
		    }
        }
	    $oriPersen = (($totalBoq2_ori - $totalBoq3_ori) / $totalBoq2_ori) * 100;
        $currentPersen = (($totalBoq2_current - $totalBoq3_current) / $totalBoq2_current) * 100;
        if ($userRate)
        	$exchange_rate = "<b>USD Exchange Rate : Rp. $rateidr, Last updated : $tgl</b>";
        else
        	$exchange_rate = "<b>This report is not using updated exchange rate.</b>";
        $ket_project = "<b>$prjKode - $prj_nama</b>";
	    $params = array(
	    			"totBoq2_ori" => number_format($totalBoq2_ori),
	    			"totBoq3_ori" => number_format($totalBoq3_ori),
	    			"totBoq2_current" => number_format($totalBoq2_current),
	    			"totBoq3_current" => number_format($totalBoq3_current),
	    			"totBoq4_current" => number_format($totalBoq4_current),
	    			"totGross_ori" => number_format($oriPersen,2) . '%',
	    			"totGross_current" => number_format($currentPersen,2) . '%',
	    			"exchange_rate" => $exchange_rate,
	    			"ket_project" => $ket_project
	    		);
		$outputPath = $this->getReportNoDataSource($type,$data,$params,'projectbudget.jrxml','Project Budget');	
    }

    //DO Printout from Warehouse
    public function doprintAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();
        $quantity = $this->_helper->getHelper('quantity');
        $type = $request->getParam('type');
        $trano = $request->getParam('trano');

        $data = new Java("java.util.ArrayList");

        $hasil = $this->db->query("SELECT * FROM procurement_whoh WHERE trano = '$trano'");
        if ($hasil)
        {
            $header = $hasil->fetchAll();
            $hasil = $this->db->query("SELECT * FROM procurement_whod WHERE trano = '$trano'");
            if ($hasil)
            {
                $hasil = $hasil->fetchAll();
            }
        }
        foreach($hasil as $key => $val)
        {
            $data->add(array(
                         'qty' => number_format($val['qty'],0)
                        ,'kode_brg' => $val['kode_brg']
                        ,'nama_brg' => $val['nama_brg']
                        ,'ket' => $val['ket']
                        ,'uom' => $quantity->getUOMByProductID($val['kode_brg'])
		    			));
        }
    	if ($type == '')
    		$type == 'pdf';
	    	$this->_helper->viewRenderer->setNoRender();

	    	$params = array(    'trano'  => $header[0]['trano'],
                                'prj_kode'  => $header[0]['prj_kode'],
                                'prj_nama'  => $header[0]['prj_nama'],
                                'dest_nama' => $header[0]['deliveryto'],
                                'tgl' => date('d M Y'),
                                'receiver_nama' => '',
                                'receiver_tlp' => '',
                                'receiver_fax' => ''
                    );
        $outputPath = $this->getReportNoDataSource($type,$data,$params,'doprint2.jrxml','Delivery Order Printout (' . $trano . ')');
    }

  //timesheet Report
    public function timesheetAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
        
        $uid = rawurldecode($this->getRequest()->getParam("uid"));
        $prjKode = rawurldecode($this->getRequest()->getParam("prj_kode"));
        $periode = rawurldecode($this->getRequest()->getParam("periode"));
        $types = rawurldecode($this->getRequest()->getParam("type"));
        $type = 'xls';

        $data = new Java("java.util.ArrayList");

        if ($uid)
            $where = " uid = '$uid'";
        if ($prjKode)
        {
            if ($where)
                $where .= " AND prj_kode = '$prjKode'";
            else
                $where = "prj_kode = '$prjKode'";
        }
        if ($periode)
        {
            $periodeHR = new HumanResource_Model_SetPeriode();
            $fetch = $periodeHR->fetchRow("id = $periode");
            $totalHour = $fetch['jumlah_jam_bulan'];
            $timestamp = mktime(0, 0, 0, intval($fetch['periode']),1,floatval(date("Y")));
            $month = date("F",$timestamp);
            $year = $fetch['tahun'] . " - " . $month;
            $tgl1 = $fetch['tgl_aw'];
            $tgl2 = $fetch['tglak'];
            if ($where)
                $where .= " AND (start BETWEEN '$tgl1' AND '$tgl2') AND (end BETWEEN '$tgl1' AND '$tgl2')";
            else
                $where = "(start BETWEEN '$tgl1' AND '$tgl2') AND (end BETWEEN '$tgl1' AND '$tgl2')";
        }
        $ret = array();

        if ($types == 'final')
            $where .= " AND is_final_approve = 1";
        else
            $where .= " AND is_submit = 1";

        if ($where != '')
        {
            $whereas = "WHERE $where";
            $where = " AND $where";
        }
        $sql = "
        SELECT
            *,
            COALESCE(((z.hour / z.totalhour) * 100),0) as persen,
            COALESCE(((z.hour / $totalHour) * 100),0) as persen2
        FROM
        (
            SELECT
                ML.Name as name,
                ML.npk,
                TS.prj_kode,
                (SELECT Prj_Nama FROM master_project WHERE prj_kode = TS.prj_kode) AS prj_nama,
                sum(hour) AS hour,
                (COALESCE
                    (
                    (SELECT
                        SUM(hour)
                    FROM
                        projectmanagement_timesheet
                    WHERE
                        uid = TS.uid $where),
                    (SELECT
                        SUM(hour)
                    FROM
                        projectmanagement_timesheet
                    WHERE
                        behalfof = TS.uid $where)
                    )
                ) AS totalhour
            FROM
            (
                (SELECT
                    (IF ((behalfof IS NOT NULL AND behalfof != ''),behalfof, uid)) as uid,
                    hour,
                    prj_kode
                FROM
                    projectmanagement_timesheet
                $whereas
            ) TS
            LEFT JOIN
                master_login ML
            ON
                TS.uid = ML.uid
        )
         GROUP BY TS.uid,TS.prj_kode) z
        ";
        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        if ($hasil)
        {
            foreach($hasil as $key => $val)
            {
                $data->add(array(
                             'npk' => $val['npk']
                            ,'prj_kode' => $val['prj_kode']
                            ,'prj_nama' => $val['prj_nama']
                            ,'name' => $val['name']
                            ,'hour' => floatval($val['hour'])
                            ,'totalhour' => floatval($val['totalhour'])
                            ,'totalhour1' => floatval($totalHour)
                            ,'persen' => floatval($val['persen'])
                            ,'persen2' => floatval($val['persen2'])
                            ));
            }
        }

    	if ($type == '')
    		$type == 'pdf';

	    	$params = array('tgl1'  => $tgl1,
                                'tgl2'  => $tgl2);
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReportNoDataSource($type,$data,$params,'timesheetreport.jrxml','Timesheet Report');
    }  

    //timesheet detail Report
    public function timesheetdetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $tgl1 = $request->getParam('tgl1');
        $tgl2 = $request->getParam('tgl2');

        $uid = rawurldecode($this->getRequest()->getParam("uid"));
        $prjKode = rawurldecode($this->getRequest()->getParam("prj_kode"));
        $tgl1 = rawurldecode($this->getRequest()->getParam("tgl1"));
        $tgl2 = rawurldecode($this->getRequest()->getParam("tgl2"));
        $types = rawurldecode($this->getRequest()->getParam("type"));
        $type = 'xls';

        $data = new Java("java.util.ArrayList");

        if ($uid)
            $where = " uid = '$uid'";
        if ($prjKode)
        {
            if ($where)
                $where .= " AND prj_kode = '$prjKode'";
            else
                $where = "prj_kode = '$prjKode'";
        }
        if ($tgl1 && $tgl2)
        {
            $tgl1 = date("Y-m-d H:i:s",strtotime($tgl1));
            $tgl2 = date("Y-m-d H:i:s",strtotime($tgl2));
            if ($where)
                $where .= " AND (start BETWEEN '$tgl1' AND '$tgl2') AND (end BETWEEN '$tgl1' AND '$tgl2')";
            else
                $where = "(start BETWEEN '$tgl1' AND '$tgl2') AND (end BETWEEN '$tgl1' AND '$tgl2')";
        }
        $ret = array();

        if ($types == 'final')
            $where .= " AND is_final_approve = 1";
        else
            $where .= " AND is_submit = 1";

        if ($where != '')
        {
            $whereas = "WHERE $where";
            $where = " AND $where";
        }

        $sql=  "SELECT
                    trano,
                    name,
                    npk,
                    start,
                    end,
                    hour,
                    prj_kode,
                    (SELECT Prj_Nama FROM master_project WHERE prj_kode = TS.prj_kode) AS prj_nama
                FROM projectmanagement_timesheet TS
                LEFT JOIN master_login  ML
                ON (IF((TS.behalfof IS NOT NULL AND TS.behalfof != ''),TS.behalfof,TS.uid)) = ML.uid
                WHERE
                    trano IS NOT NULL $where
                ORDER BY name asc";

    	if ($type == '')
    		$type == 'pdf';

        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        if ($hasil)
        {
            foreach($hasil as $key => $val)
            {
                $data->add(array(
                             'npk' => $val['npk']
                            ,'prj_kode' => $val['prj_kode']
                            ,'prj_nama' => $val['prj_nama']
                            ,'name' => $val['name']
                            ,'start' => date('d-m-Y H:i:s',strtotime($val['start']))
                            ,'end' => date('d-m-Y H:i:s',strtotime($val['end']))
                            ,'trano' => $val['trano']
                            ,'hour' => floatval($val['hour'])
                            ));
            }
        }

	    	$params = array('tgl1'  => $tgl1,
                                'tgl2'  => $tgl2);
	    			//'basePath' => $basePath);
	    	$outputPath = $this->getReportNoDataSource($type,$data,$params,'timesheetdetailreport.jrxml','Timesheet Detail Report');
    }

    public function reimbursreportAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $ldapdir = new Default_Models_Ldap();
    	$request = $this->getRequest();
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        $sign = $request->getParam('sign');
        $uid = $this->session->userName;

        $this->nota_debit_detail = new Finance_Models_NDreimbursDetail();
        $this->nota_debit_header = new Finance_Models_NDreimbursHeader();
        $this->MsProject = new Default_Models_MasterProject();
        $this->logprint = new Default_Models_LogPrint();

        $where = "trano = '$noTrans'";

        $detaildata = $this->nota_debit_header->fetchRow($where);

//    var_dump($detaildata);die;

        if ($detaildata)
        {
            if ($detaildata['statusppn'] == 'Y')
            {
                $pajak = floatval($detaildata['total']) * 0.1;

            }else
            {
                $pajak = 0.00;
            }
            $gtotal = floatval($detaildata['total']) + $pajak;

        }

        $dntotal = floatval($detaildata['total']);
        $prj_kode = $detaildata['prj_kode'];
        $paymentnotes = $detaildata['paymentnotes'];
        $destination = $detaildata['destination'];
        $destinationaddress = $detaildata['destinationaddress'];

        $customerdata = $this->MsProject->getcustomer($prj_kode);

        $cus_nama = $customerdata[0]['cus_nama'];
        $cus_alamat = $customerdata[0]['alamat'];

        $sql = "
                SELECT ml.uid as uid_manager, mrt.display_name FROM
                    master_login ml
                LEFT JOIN
                    master_role mr
                ON
                    ml.id = mr.id_user
                LEFT JOIN
                    master_role_type mrt
                ON
                    mr.id_role = mrt.id
                WHERE
                    mrt.role_name = 'BOD'
                    AND
                    mrt.display_name != 'Executive Assistant'
                    AND
                    ml.uid = '$sign'
                GROUP BY ml.uid
        ";

        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        if($hasil)
        {
            $ldapdir = new Default_Models_Ldap();
            foreach ($hasil as $key => $val)
            {
                $account = $ldapdir->getAccount($val['uid_manager']);
                $hasil[$key]['display_name'] = $account['displayname'][0] ;
                $hasil[$key]['role_name'] =$val['display_name'];
            }
        }

        $signname = $hasil[0]['display_name'];
        $signtitle = $hasil[0]['role_name'];

        $date = date('Y-m-d H:i:s');

//        $signature = md5(date("Y-m-d H:i:s"). "|" . $uid);
        $signature = $this->token->generateDocumentSignature();
        
        $insertlog = array(
            'date' => $date,
            'uid' => $uid,
            'trano' => $noTrans,
            'type' => $type,
            "com_IP" => $_SERVER["REMOTE_ADDR"],
            "com_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"]),
            "signature" => $signature
        );

        $this->logprint->insert($insertlog);

        $terbilang = $this->utility->convert_number($gtotal);
        $params = array(
            'noTrans' => $noTrans,
            'pajak' => $pajak,
            'gtotal' => $gtotal,
            'dntotal' => $dntotal,
            'paymentnotes' => $paymentnotes,
            'cus_nama' => $cus_nama,
            'basePath' =>  $this->reportPath . '/images/Qdc22.jpg',
            'cus_alamat' => $cus_alamat,
            'signname' => $signname,
            'signtitle' => $signtitle,
            'terbilang' => $terbilang . " Indonesia Rupiah and 0 Cents Only",
            'destination' => $destination,
            'destinationaddress' => $destinationaddress,
            "signature" => $signature
        );

        $outputPath = $this->getReport($type,$params,'reimburs_nota_debet.jrxml','Reimbursement Debit Note');


    }

    public function invoiceAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $ldapdir = new Default_Models_Ldap();
    	$request = $this->getRequest();
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        $sign = $request->getParam('sign');
        $uid = $this->session->userName;

        $data = new Java("java.util.ArrayList");

        $detail = new Finance_Models_InvoiceDetail();
        $header = new Finance_Models_Invoice();
        $MsProject = new Default_Models_MasterProject();
        $logprint = new Default_Models_LogPrint();
        $customer = new Logistic_Model_LogisticCustomer();

        $where = "trano = '$noTrans'";


        $detaildata = $header->fetchRow($where);
        $hasil = $detail->fetchAll($where);

        $cus_kode = $hasil[0]['cus_kode'];

        $arrayData = array();
        if ($hasil)
        {
            $totalInvoice = 0;
            $totalPPN = 0;
            foreach($hasil as $k => $val)
            {
                $ppn = $val['statusppn'];
                $ht = $val['holding_tax_status'];
                if ($ppn == 'Y')
                {
                    $ppn = 'Add VAT 10%';
                    $ppnvalue = number_format(floatval($val['total']) * 0.1,2);
                    $totalPPN += (floatval($val['total']) * 0.1);
                }else
                {
                    $ppn = null;
                    $ppnvalue = "";
                }

                if ($ht == 'Y')
                {
                    $ht_text = $val['holding_tax_text'];
                    $ht_persen = floatval($val['holding_tax'] * 100);

                    if ($ht_persen == 0)
                    {
                        $ht_persen = null;
                    }else
                    {
                        $ht_persen = $ht_persen . '%';
                    }

                    $ht_value = '(' . number_format(floatval($val['holding_tax_val']),2) . ')';
                }else
                {
                    $ht_text = null;
                    $ht_persen = '';
                    $ht_value = '';
                }

                $gtotal += floatval($val['jumlah']);

                $valuta = $val['val_kode'];

                if ($valuta == 'IDR')
                {
                    $valuta = 'Rp.';
                }else if ($valuta == 'USD')
                {
                    $valuta = '$';
                }

                $deduction_text = '';
                $deduction = floatval($val['deduction']);

                if ($deduction != 0)
                {
                    $deduction = '(' . number_format($deduction,2) . ')';
                    $deduction_text = 'Deduction';
                }else
                {
                    $deduction = '';
                    $deduction_text = null;
                }

//                $data->add(array(
//                    'nama_brg' => $val['nama_brg'],
//                    'total' => number_format($val['total'],2),
//                    'ppn' => $ppn,
//                    'ppnvalue' => $ppnvalue,
//                    'ht_text' => $ht_text,
//                    'ht_persen' => $ht_persen,
//                    'ht_value' => $ht_value,
//                    'deduction' => $deduction,
//                    'deduction_text' =>$deduction_text
//                ));
                $arrayData[] = array(
                    'nama_brg' => $val['nama_brg'],
                    'total' => number_format($val['total'],2),
                    'ppn' => $ppn,
                    'ppnvalue' => $ppnvalue,
                    'ht_text' => $ht_text,
                    'ht_persen' => $ht_persen,
                    'ht_value' => $ht_value,
                    'deduction' => $deduction,
                    'deduction_text' =>$deduction_text
                );

                $totalInvoice += $val['total'];
            }
        }


        
        $prj_kode = $detaildata['prj_kode'];

        $customerdata = $MsProject->getcustomer($prj_kode);

        $cus_nama = $customerdata[0]['cus_nama'];
        $cus_alamat = $customerdata[0]['alamat'];

        $sql = "
                SELECT ml.uid as uid_manager, mrt.display_name FROM
                    master_login ml
                LEFT JOIN
                    master_role mr
                ON
                    ml.id = mr.id_user
                LEFT JOIN
                    master_role_type mrt
                ON
                    mr.id_role = mrt.id
                WHERE
                    mrt.role_name = 'BOD'
                    AND
                    mrt.display_name != 'Executive Assistant'
                    AND
                    ml.uid = '$sign'
                GROUP BY ml.uid
        ";

        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        if($hasil)
        {
            $ldapdir = new Default_Models_Ldap();
            foreach ($hasil as $key => $val)
            {
                $account = $ldapdir->getAccount($val['uid_manager']);
                $hasil[$key]['display_name'] = $account['displayname'][0] ;
                $hasil[$key]['role_name'] =$val['display_name'];
            }
        }

        $signname = $hasil[0]['display_name'];
        $signtitle = $hasil[0]['role_name'];

        $date = date('Y-m-d H:i:s');

//        $signature = md5(date("Y-m-d H:i:s"). "|" . $uid);
        $signature = $this->token->generateDocumentSignature();

        $insertlog = array(
            'date' => $date,
            'uid' => $uid,
            'trano' => $noTrans,
            'type' => $type,
            "com_IP" => $_SERVER["REMOTE_ADDR"],
            "com_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"]),
            "signature" => $signature
        );

        $logprint->insert($insertlog);

        if ($detaildata['val_kode'] == 'IDR')
            $val = 'Rp.';
        else
            $val = '$';
        $terbilang = $this->utility->convert_number($gtotal);
        $params = array(
            'noTrans2' => $noTrans . $detaildata['suffix_trano'],
            'noTrans' => $noTrans,
            'gtotal' => $gtotal,
//            'invTotal' => $invTotal,
            'terbilang' => $terbilang . " Indonesia Rupiah and 0 Cents Only",
            'cus_nama' => $cus_nama,
            'basePath' =>  $this->reportPath . '/images/Qdc22.jpg',
            'cus_alamat' => $cus_alamat,
            'signname' => $signname,
            'signtitle' => $signtitle,
            'paymentterm' => $detaildata['paymentterm'],
            'val_kode' => $val,
            'signature' => $signature,
            'bnk_noreknama' => $detaildata['bnk_noreknama'],
            'bnk_nama' => $detaildata['bnk_nama'],
            'bnk_alamat' => $detaildata['bnk_alamat'],
            'bnk_cabang' => $detaildata['bnk_cabang'],
            'bnk_kota' => $detaildata['bnk_kota'],
            'bnk_norek' => $detaildata['bnk_norek'],
        );

        $data_customer = $customer->fetchRow("cus_kode = '$cus_kode'");

//        var_dump($ppnvalue);die;

        $params_pajak = array(
            'customer_name' => "{$data_customer['cus_nama']}",
            'customer_tax_add' => $detaildata['alamatpajak'],
            'customer_npwp' => $detaildata['npwp'],
            'valuta' => $valuta,
            'total_invoice' => number_format($totalInvoice,2),
            'total_ppn' => number_format($totalPPN,2),
            'signname' => $signname,
            'signtitle' => $signtitle
        );

        $file_invoice = 'invoice.jrxml';
        $file_faktur_pajak = 'faktur_pajak.jrxml';

//        var_dump($arrayData);die;

        $dataPrint[] = array(
            "data" => $arrayData,
            "arrayParams" => $params,
            "fileName" => $file_invoice,
            "dataSource" => 'NoDataSource'
        );
//        foreach($dataPPN as $k => $v)
//        {
            $dataPrint[] = array(
                "data" => $arrayData,
                "arrayParams" => $params_pajak,
                "fileName" => $file_faktur_pajak,
                "dataSource" => 'NoDataSource'
            );
//        }
//
//        var_dump($dataPrint);die;

        QDC_Jasper_Report::factory(
            array(
                "reportType" => $type,
                "arrayMultiData" => $dataPrint,
                "outputName" => 'Invoice'
            )
        )->generateCombined();
//        $outputPath = $this->getReportNoDataSource($type,$data,$params,'invoice.jrxml','Invoice');

//        $outputPath = $this->getReport($type,$params,'invoice.jrxml','Invoice');


    }

    public function paymentvoucherreportAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $voc_trano = $this->getRequest()->getParam('trano');
        $type_doc = $this->getRequest()->getParam('type_doc');
        $prepared = $this->session->userName;

        $data = new Java("java.util.ArrayList");

        $ldap = new Default_Models_Ldap();

        $voucher = $this->voucher->fetchAll("trano = '$voc_trano'" );
        $tgl = date('d/m/Y',strtotime($voucher[0]['tgl']));
        $itungData = 0;
        $voc_type = $voucher[0]['type'];

        $arrayTextbox = array();

        $arrayPPN = array();
        $arrayData = array();
        if ($voucher)
        {
            if ($voucher[0]['item_type'] == 'RPI')
            {
                $tmp = array();

                foreach($voucher as $key => $val)
                {
                    $itungData++;

                    $statuspulsa = $val['statuspulsa'];

                    $tmp['prj_kode'][] = $val['prj_kode'];
                    $tmp['ref-number'][] = $val['ref_number'];
                    $tmp['requester'][] = $val['requester'];
                    $tmp['sit_kode'][] = $val['sit_kode'];
                    $ppn = $val['statusppn'];
                    $ht = $val['holding_tax_status'];

                    //Cek kalo ada ppn
                    if ($val['trano_ppn'] != '')
                    {
                        $arrayPPN[] = $val['trano_ppn'];
                    }

                    if ($ppn == 'Y')
                    {
                        $ppn = 'Add VAT 10%';
                        $ppnvalue = number_format(floatval($val['valueppn']),2);
//                        $ppnvalue = floatval($val['valueppn']);
                    }else
                    {
                        $ppn = null;
                        $ppnvalue = "";
//                        $ppnvalue = 0;
                    }

                    if ($ht == 'Y')
                    {
                        $ht_text = $val['holding_tax_text'];
                        $ht_persen = floatval($val['holding_tax'] * 100);

                        if ($ht_persen == 0)
                        {
                            $ht_persen = null;
                        }else
                        {
                            $ht_persen = $ht_persen . '%';
                        }

                        $ht_value = '(' . number_format(floatval($val['holding_tax_val']),2) . ')';
//                        $ht_value = floatval($val['holding_tax_val']);
                    }else
                    {
                        $ht_text = null;
                        $ht_persen = '';
                        $ht_value = '';
//                        $ht_value = 0;
                    }

                    $gtotal += floatval($val['total']);

                    $valuta = $val['valuta'];

                    if ($valuta == 'IDR')
                    {
                        $valuta = 'Rp';
                    }else if ($valuta == 'USD')
                    {
                        $valuta = '$';
                    }

                    $doc_trano = $val['ref_number'];
                    $deduction_text = '';
                    $deduction = floatval($val['deduction']);

                    if ($deduction != 0)
                    {
                        $deduction = '(' . number_format($deduction,2) . ')';
//                        $deduction = $deduction;
                        $deduction_text = 'Deduction';
                    }else
                    {
                        $deduction = '';
//                        $deduction = 0;
                        $deduction_text = null;
                    }

                    $grossup = $val['grossup_status'];
                    $invoice = floatval($val['total_bayar']);
                    $grossup_value = floatval($val['holding_tax_val']);

                    if ($grossup == 'Y')
                    {
                        $invoice = $invoice + $grossup_value;
                    }

                    $sqls = "SELECT uid,date,signature,approve FROM workflow_trans WHERE (approve = 200 OR approve = 400) AND item_id = '$doc_trano' GROUP BY item_id,workflow_id,uid ORDER BY date ASC";
                    $fetchs = $this->db->query($sqls);
                    $datas = $fetchs->fetchAll();


//                    if ($itungData <=4)
//                    {
                        $app = array();
                        foreach($datas as $key2 => $val2)
                        {
                            $uid = $val2['uid'];
                            $acc = $ldap->getAccount($uid);
                            $nama = $acc['displayname'][0];
                            $app[] = "<li>" . $nama . " " . date("d/M/Y",strtotime($val2['date'])) . "</li>";
                        }
                        $arrayTextbox[1] = "<b><u>$doc_trano</u></b><ul>" . implode("",$app) . "</ul>";
//                    }

                    $arrayData[] = array(
                        'prj_kode' => $prj_kode,
                        'coa' => $val['coa_kode'],
                        'description' => $val['ketin'],
                        'amount' => $invoice,
                        'ppn' => $ppn,
                        'ppnvalue' => $ppnvalue,
                        'ht_text' => $ht_text,
                        'ht_persen' => $ht_persen,
                        'ht_value' => $ht_value,
                        'deduction' => $deduction,
                        'deduction_text' =>$deduction_text,
                        'total' => floatval($val['total'])
                    );
                }

                if ($statuspulsa == '1')
                {
                    $prj_kode = implode('/',$tmp['prj_kode']);
                }else
                {
                    $prj_kode = $tmp['prj_kode'][0];
                }

    //            var_dump($prj_kode);die;

//                $ref_number = implode('/',$tmp['ref-number']);
//                $requester = implode('/',$tmp['requester']);
//                $sit_kode = implode('/',$tmp['sit_kode']);

                $ref_number = $val['ref_number'];
                $requester = $val['requester'];
                $sit_kode = $val['sit_kode'];

                
            }else
            {
                
                $tmp = array();

                foreach($voucher as $key => $val)
                {
                    $itungData++;

                    $statuspulsa = $val['statuspulsa'];


                    $tmp['prj_kode'][] = $val['prj_kode'];
                    $tmp['ref-number'][] = $val['ref_number'];
                    $tmp['requester'][] = $val['requester'];
                    $tmp['sit_kode'][] = $val['sit_kode'];
                    $ppn = $val['statusppn'];
                    $ht = $val['holding_tax_status'];

                    //Cek kalo ada ppn
                    if ($val['trano_ppn'] != '')
                    {
                        $arrayPPN[] = $val['trano_ppn'];
                    }

                    if ($ppn == 'Y')
                    {
                        $ppn = 'Add VAT 10%';
                        $ppnvalue = number_format(floatval($val['valueppn']),2);
                    }else
                    {
                        $ppn = null;
                        $ppnvalue = "";
                    }

                    if ($ht == 'Y')
                    {
                        $ht_text = $val['holding_tax_text'];
                        $ht_persen = floatval($val['holding_tax'] * 100);

                        if ($ht_persen == 0)
                        {
                            $ht_persen = null;
                        }else
                        {
                            $ht_persen = $ht_persen . '%';
                        }

                        $ht_value = '(' . number_format(floatval($val['holding_tax_val']),2) . ')';
                    }else
                    {
                        $ht_text = null;
                        $ht_persen = '';
                        $ht_value = '';
                    }

                    $gtotal += floatval($val['total']);

                    $valuta = $val['valuta'];

                    if ($valuta == 'IDR')
                    {
                        $valuta = 'Rp';
                    }else if ($valuta == 'USD')
                    {
                        $valuta = '$';
                    }

                    $doc_trano = $val['ref_number'];
                    $deduction_text = '';
                    $deduction = floatval($val['deduction']);

                    if ($deduction != 0)
                    {
                        $deduction = '(' . number_format($deduction,2) . ')';
                        $deduction_text = 'Deduction';
                    }else
                    {
                        $deduction = '';
                        $deduction_text = null;
                    }

                    $grossup = $val['grossup_status'];
                    $invoice = floatval($val['total_bayar']);
                    $grossup_value = floatval($val['holding_tax_val']);

                    if ($grossup == 'Y')
                    {
                        $invoice = $invoice + $grossup_value;
                    }

                    $sqls = "SELECT uid,date,signature,approve FROM workflow_trans WHERE (approve = 200 OR approve = 400) AND item_id = '$doc_trano' GROUP BY item_id,workflow_id,uid ORDER BY date ASC";
                    $fetchs = $this->db->query($sqls);
                    $datas = $fetchs->fetchAll();


                    if ($itungData <=4)
                    {
                        $app = array();
                        foreach($datas as $key2 => $val2)
                        {
                            $uid = $val2['uid'];
                            $acc = $ldap->getAccount($uid);
                            $nama = $acc['displayname'][0];
                            $app[] = "<li>" . $nama . " " . date("d/M/Y",strtotime($val2['date'])) . "</li>";
                        }
                        $arrayTextbox[$itungData] = "<b><u>$doc_trano</u></b><ul>" . implode("",$app) . "</ul>";
                    }

                    $arrayData[] = array(
                        'prj_kode' => $prj_kode,
                        'coa' => $val['coa_kode'],
                        'description' => $val['ketin'],
                        'amount' => $invoice,
                        'ppn' => $ppn,
                        'ppnvalue' => $ppnvalue,
                        'ht_text' => $ht_text,
                        'ht_persen' => $ht_persen,
                        'ht_value' => $ht_value,
                        'deduction' => $deduction,
                        'deduction_text' =>$deduction_text,
                        'total' => floatval($val['total'])
                    );
                }

                if ($statuspulsa == '1')
                {
                    $prj_kode = implode('/',$tmp['prj_kode']);
                }else
                {
                    $prj_kode = $tmp['prj_kode'][0];
                }

    //            var_dump($prj_kode);die;

                $ref_number = implode('/',$tmp['ref-number']);
                $requester = implode('/',$tmp['requester']);
                $sit_kode = implode('/',$tmp['sit_kode']);

            }

        }

        $username = $ldap->getAccount($prepared);
        $namauser = $username['displayname'][0];

        $params = array(

            'trano' => $voc_trano,
            'ref-number' => $ref_number,
            'requester' => $requester,
            'sit_kode' => $sit_kode,
            'prj_kode' => $prj_kode,
            'gtotal' => number_format($gtotal,2),
            'valuta' => $valuta,
            'teksbox1' => $arrayTextbox[1],
            'teksbox2' => $arrayTextbox[2],
            'teksbox3' => $arrayTextbox[3],
            'teksbox4' => $arrayTextbox[4],
            'prepared' => $namauser,
            'basePath' =>  $this->reportPath . '/images/Qdc22.jpg',
            'tgl' => $tgl

        );

        if($voc_type == 'pettycash')
        {
            $file_bankvoucer = 'pettycash_payment_voucher_report.jrxml';
        }else{
//            $file_bankvoucer = 'pettycash_payment_voucher_report.jrxml';
            $file_bankvoucer = 'bank_payment_voucher_report.jrxml';
        }




//        $outputPath = $this->getReportNoDataSource($type_doc,$data,$params,'bank_payment_voucher_report.jrxml','Bank Payment Voucher Report');
        if (count($arrayPPN) > 0)
        {
            $dataPPN = $this->getDataBPVPPN($arrayPPN);
            $dataPrint[] = array(
                "data" => $arrayData,
                "arrayParams" => $params,
                "fileName" => $file_bankvoucer,
                "dataSource" => 'NoDataSource'
            );
            foreach($dataPPN as $k => $v)
            {
                $dataPrint[] = array(
                    "data" => $v['data'],
                    "arrayParams" => $v['arrayParams'],
                    "fileName" => $file_bankvoucer,
                    "dataSource" => 'NoDataSource'
                );
            }

            QDC_Jasper_Report::factory(
                array(
                    "reportType" => $type_doc,
                    "arrayMultiData" => $dataPrint,
                    "outputName" => 'Bank Payment Voucher'
                )
            )->generateCombined();
        }
        else
        {
            QDC_Jasper_Report::factory(
                array(
                    "reportType" => $type_doc,
                    "arrayData" => $arrayData,
                    "arrayParams" => $params,
                    "fileName" => $file_bankvoucer,
                    "outputName" => 'Bank Payment Voucher',
                    "dataSource" => 'NoDataSource'
                )
            )->generate();
        }
    }

    public function getDataBPVPPN ($arrayPPN='')
    {
        $arrayData = array();
        $prepared = $this->session->userName;
        $ldap = new Default_Models_Ldap();
        $jum = 0;

        foreach($arrayPPN as $k => $v)
        {

            $voucher = $this->voucher->fetchRow("trano = '$v'" );

            if ($voucher)
            {
                $val = $voucher->toArray();
                $tgl = date('d/m/Y',strtotime($val['tgl']));
                $ppn = 'Add VAT 10%';
                $ppnvalue = number_format(floatval($val['valueppn']),2);
                $gtotal = floatval($val['total']);
                $voc_trano = $val['trano'];
                $valuta = $val['valuta'];

                if ($valuta == 'IDR')
                {
                    $valuta = 'Rp';
                }else if ($valuta == 'USD')
                {
                    $valuta = '$';
                }

                $doc_trano = $val['ref_number'];
                $invoice = floatval($val['total_bayar']);
                $sqls = "SELECT uid,date,signature,approve FROM workflow_trans WHERE (approve = 200 OR approve = 400) AND item_id = '$doc_trano' GROUP BY item_id,workflow_id,uid ORDER BY date ASC";
                $fetchs = $this->db->query($sqls);
                $datas = $fetchs->fetchAll();
                $app = array();
                foreach($datas as $key2 => $val2)
                {
                    $uid = $val2['uid'];
                    $acc = $ldap->getAccount($uid);
                    $nama = $acc['displayname'][0];
                    $app[] = "<li>" . $nama . " " . date("d/M/Y",strtotime($val2['date'])) . "</li>";
                }
                $arrayTextbox[] = "<b><u>$doc_trano</u></b><ul>" . implode("",$app) . "</ul>";


                $arrayData[$jum]['data'][] = array(
                    'prj_kode' => $val['prj_kode'],
                    'coa' => $val['coa_kode'],
                    'description' => $ppn,
                    'amount' => $invoice,
                    'total' => $gtotal
                );
                $ref_number = $val['ref_number'];
                $requester = $val['requester'];
                $sit_kode = $val['sit_kode'];
                $prj_kode = $val['prj_kode'];



                $username = $ldap->getAccount($prepared);
                $namauser = $username['displayname'][0];

                $arrayData[$jum]['arrayParams'] = array(

                    'trano' => $voc_trano,
                    'ref-number' => $ref_number,
                    'requester' => $requester,
                    'sit_kode' => $sit_kode,
                    'prj_kode' => $prj_kode,
                    'gtotal' => number_format($gtotal,2),
                    'valuta' => $valuta,
                    'teksbox1' => $arrayTextbox[0],
                    'prepared' => $namauser,
                    'basePath' =>  $this->reportPath . '/images/Qdc22.jpg',
                    'tgl' => $tgl
                );
                $jum++;
            }
        }
        return $arrayData;
    }
}
?>