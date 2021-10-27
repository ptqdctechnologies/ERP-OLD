<?php

/**
 * JasperReportController
 * 
 * @author = Wisu
 * @version
 */
class JasperController extends Zend_Controller_Action {

    private $conn;
    private $reportPath;
    private $javaOutputStream;
    private $dbhost, $dbname, $dbuser, $dbpass;
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
    private $bankout;

    public function init() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->session = new Zend_Session_Namespace('login');

        $this->project = $this->_helper->getHelper('project');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->utility = $this->_helper->getHelper('utility');
        $this->token = $this->_helper->getHelper('token');

        $this->bankout = new Finance_Models_BankSpendMoney();

        $options = $this->getInvokeArg('bootstrap')->getOptions();

        $this->reportPath = $options['jasper']['reportPath'];
        $this->javaBridge = $options['jasper']['javaBridge'];

        $this->dbhost = $options['resources']['db']['params']['host'];
        $this->dbname = $options['resources']['db']['params']['dbname'];
        $this->dbuser = $options['resources']['db']['params']['username'];
        $this->dbpass = $options['resources']['db']['params']['password'];

        include_once($this->javaBridge . "java/Java.inc");
        $this->javaOutputStream = new java("java.io.ByteArrayOutputStream");
//		$this->budget = $this->_helper->getHelper('budget');
        $this->budget = new Default_Models_Budget();
        $this->transaction = $this->_helper->getHelper('transaction');

        $this->arfh = new Procurement_Models_Procurementarfh();
        $this->arfd = new Procurement_Models_Procurementarfd();
        $this->rpid = new Default_Models_RequestPaymentInvoice();
        $this->rpih = new Default_Models_RequestPaymentInvoiceH();

        $this->paymentarf = new Finance_Models_PaymentARF();
        $this->paymentrpi = new Finance_Models_PaymentRPI();

        $this->voucher = new Finance_Models_BankPaymentVoucher();
        $this->db = Zend_Registry::get('db');
    }

    private function getParam($params) {
        $newParam = new Java("java.util.HashMap");
        foreach ($params as $param => $value) {
            $newParam->put($param, $value);
        }
        $newParam->put('SUBREPORT_DIR', $this->reportPath);
        return $newParam;
    }

    private function getReport($type = 'pdf', $params, $filename = 'report.jrmxl', $outputname = 'report') {
        try {
            $Conn = new Java("org.altic.jasperReports.JdbcConnection");
            $Conn->setDriver("com.mysql.jdbc.Driver");
            $Conn->setConnectString("jdbc:mysql://" . $this->dbhost . "/" . $this->dbname);
            $Conn->setUser($this->dbuser);
            $Conn->setPassword($this->dbpass);

            $arrayParam = $this->getParam($params);
            $compileManager = new JavaClass("net.sf.jasperreports.engine.JasperCompileManager");
            $report = $compileManager->compileReport($this->reportPath . $filename);

            $fillManager = new JavaClass("net.sf.jasperreports.engine.JasperFillManager");
            $jasperPrint = $fillManager->fillReport($report, $arrayParam, $Conn->getConnection());

            $outputPath = $this->reportPath . $outputname . '.' . $type;

            set_time_limit(120);

            java_set_file_encoding("ISO-8859-1");
            if ($type == 'pdf') {
                $exporter = new java("net.sf.jasperreports.engine.export.JRPdfExporter");
                $exParm = java("net.sf.jasperreports.engine.JRExporterParameter");
                $exporter->setParameter($exParm->JASPER_PRINT, $jasperPrint);
                $exporter->setParameter($exParm->OUTPUT_STREAM, $this->javaOutputStream);
                $exporter->exportReport();

                header('Content-Type: application/pdf');
                header('Content-Transfer-Encoding: binary');
                header('Content-disposition: attachment; filename="' . $outputname . '.pdf"');
                header('Pragma: no-cache');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Expires: 0');

                echo java_cast($this->javaOutputStream->toByteArray(), "S");
            } elseif ($type == 'xls') {
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
                header('Content-disposition: attachment; filename="' . $outputname . '.xls"');
                header('Pragma: no-cache');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Expires: 0');

                echo java_cast($this->javaOutputStream->toByteArray(), "S");
            }
        } catch (JavaException $e) {
            echo $e;
            die();
        }
    }

    private function getReportNoDataSource($type = 'pdf', $data, $params, $filename = 'report.jrmxl', $outputname = 'report') {
        try {
            $ds = new java("net.sf.jasperreports.engine.data.JRBeanCollectionDataSource", $data);
            $arrayParam = $this->getParam($params);
            $compileManager = new JavaClass("net.sf.jasperreports.engine.JasperCompileManager");
            $report = $compileManager->compileReport($this->reportPath . $filename);

            $fillManager = new JavaClass("net.sf.jasperreports.engine.JasperFillManager");
            $jasperPrint = $fillManager->fillReport($report, $arrayParam, $ds);

            $outputPath = $this->reportPath . $outputname . '.' . $type;

            set_time_limit(120);

            java_set_file_encoding("ISO-8859-1");
            if ($type == 'pdf') {
                $exporter = new java("net.sf.jasperreports.engine.export.JRPdfExporter");
                $exParm = java("net.sf.jasperreports.engine.JRExporterParameter");
                $exporter->setParameter($exParm->JASPER_PRINT, $jasperPrint);
                $exporter->setParameter($exParm->OUTPUT_STREAM, $this->javaOutputStream);
                $exporter->exportReport();

                header('Content-Type: application/pdf');
                header('Content-Transfer-Encoding: binary');
                header('Content-disposition: attachment; filename="' . $outputname . '.pdf"');
                header('Pragma: no-cache');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Expires: 0');

                echo java_cast($this->javaOutputStream->toByteArray(), "S");
            } elseif ($type == 'xls') {
                $exporter = new java("net.sf.jasperreports.engine.export.JExcelApiExporter");
                $exParm = java("net.sf.jasperreports.engine.JRExporterParameter");
                $exXlsParm = java("net.sf.jasperreports.engine.export.JRXlsExporterParameter");
                $exporter->setParameter($exParm->JASPER_PRINT, $jasperPrint);
                $exporter->setParameter($exParm->OUTPUT_STREAM, $this->javaOutputStream);
                $exporter->setParameter($exXlsParm->IS_ONE_PAGE_PER_SHEET, false);
                $exporter->setParameter($exXlsParm->IS_REMOVE_EMPTY_SPACE_BETWEEN_ROWS, true);
                $exporter->setParameter($exXlsParm->IS_REMOVE_EMPTY_SPACE_BETWEEN_COLUMNS, true);
                $exporter->setParameter($exXlsParm->IS_IGNORE_CELL_BORDER, false);
                $exporter->setParameter($exXlsParm->IS_DETECT_CELL_TYPE, true);
                $exporter->setParameter($exXlsParm->IS_WHITE_PAGE_BACKGROUND, false);
                $exporter->exportReport();

                header('Content-Type: application/xls');
                header('Content-Transfer-Encoding: binary');
                header('Content-disposition: attachment; filename="' . $outputname . '.xls"');
                header('Pragma: no-cache');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Expires: 0');

                echo java_cast($this->javaOutputStream->toByteArray(), "S");
            }
        } catch (JavaException $e) {
            echo $e;
            die();
        }
    }

    public function boq3Action() {

        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';

        $projectDetail = $this->project->getProjectDetail($prjKode);
        $siteDetail = $this->project->getSiteDetail($prjKode, $sitKode);
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
            'signature' => md5($this->session->userName . date('d-m-Y') . date('h:i:s'))
        );
        $outputPath = $this->getReport($type, $params, 'boq3.jrxml', 'boq3');
    }

    //boq3 revisi
    public function boq3revisiAction() {

        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';

        $projectDetail = $this->project->getProjectDetail($prjKode);
        $siteDetail = $this->project->getSiteDetail($prjKode, $sitKode);
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
            'signature' => md5($this->session->userName . date('d-m-Y') . date('h:i:s'))
        );
        $outputPath = $this->getReport($type, $params, 'boq3revisi.jrxml', 'boq3revisi');
    }

    //ARF Summary Report
    public function arfsummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $requestor = $request->getParam('requestor');
        if ($prjKode != '')
            $where = "a.prj_kode = '$prjKode'";
        if ($sitKode != '' && $prjKode != '')
            $where .= " AND a.sit_kode='$sitKode' ";
        if ($requestor != '') {
            if ($where != '')
                $where .= " AND b.request='$requestor' ";
            else
                $where = " b.request='$requestor' ";
        }
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

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
        foreach ($result as $key => $val) {
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

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode,
            'total' => number_format($total));
        //'basePath' => $basePath);
//	    	$outputPath = $this->getReport($type,$params,'rpt_sum_arf.jrxml','ARF Summary Report');
        $outputPath = $this->getReportNoDataSource($type, $data, $params, 'rpt_sum_arf.jrxml', 'ARF Summary Report');
    }

    //ASF Summary Report
    public function asfsummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode);
        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_sum_asf.jrxml', 'ASF Summary Report');
    }

    //PR Summary Report
    public function prsummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode);
        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_sum_pr.jrxml', 'PR Summary Report');
    }

    //PO Summary Report
    public function posummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        $supKode = $request->getParam('sup_kode');
        if ($request->getParam('tgl1') != '')
            $tgl1 = date("Y-m-d", strtotime(urldecode($request->getParam('tgl1'))));
        if ($request->getParam('tgl2') != '')
            $tgl2 = date("Y-m-d", strtotime(urldecode($request->getParam('tgl2'))));
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $data = new Java("java.util.ArrayList");

        $hasil = $this->transaction->getPoSummary($prjKode, $sitKode, $supKode, $tgl1, $tgl2);
        $totIDR = 0;
        $totUSD = 0;
        foreach ($hasil as $key => $val) {
            if ($val['pc_nama'] == '')
                $val['pc_nama'] = 'NONAME';
            $data->add(array("sit_kode" => $val['sit_kode']
                , "sit_nama" => $val['sit_nama']
                , 'trano' => $val['trano']
                , 'tgl' => date('d-m-Y', strtotime($val['tgl']))
                , 'workid' => $val['workid']
                , 'workname' => $val['workname']
                , 'total_IDR' => number_format($val['total_IDR'], 2)
                , 'total_USD' => number_format($val['total_USD'], 2)
                , 'pc_nama' => $val['pc_nama']
            ));
            $totIDR += $val['total_IDR'];
            $totUSD += $val['total_USD'];
        }
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'prjNama' => $hasil[0]['prj_nama'],
            'totIDR' => number_format($totIDR, 2),
            'totUSD' => number_format($totUSD, 2));
        //'basePath' => $basePath);
        $outputPath = $this->getReportNoDataSource($type, $data, $params, 'rpt_sum_po.jrxml', 'PO Summary Report');
//	    	$outputPath = $this->getReport($type,$params,'rpt_sum_po.jrxml','PO Summary Report');
    }

    //PO PPN Summary Report
    public function poppnAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $tgl1 = $request->getParam('tgl1');
        $tgl2 = $request->getParam('tgl2');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('tgl1' => $tgl1,
            'tgl2' => $tgl2);
        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_poppn.jrxml', 'PO PPN Summary Report');
    }

    //RPI Summary Report
    public function rpisummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode);
        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_sum_rpi.jrxml', 'RPI Summary Report');
    }

    //ARF Detail Report
    public function arfdetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        //$prjKode = $request->getParam('prj_kode');
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        $basePath = $this->reportPath . '/images/imd21.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';

        $params = array('noTrans' => $noTrans,
            'basePath' => $basePath);
        if ($type == 'pdf') {

            $outputPath = $this->getReport($type, $params, 'rpt_detail_arf.jrxml', 'ARF Detail Report');
        } else {
            $outputPath = $this->getReport($type, $params, 'rpt_detail_arf_excel.jrxml', 'ARF Detail Report');
        }
    }

    //ASF Detail Report
    public function asfdetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'noTrans' => $noTrans);
        //'basePath' => $basePath);
        if ($type == 'pdf') {

            $outputPath = $this->getReport($type, $params, 'rpt_detail_asf.jrxml', 'ASF Detail Report');
        } else {
            $outputPath = $this->getReport($type, $params, 'rpt_detail_asf_excel.jrxml', 'ASF Detail Report');
        }
    }

    //PR Detail Report
    public function prdetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        $basePath = $this->reportPath . '/images/imd21.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'noTrans' => $noTrans,
            'basePath' => $basePath);
        if ($type == 'pdf') {

            $outputPath = $this->getReport($type, $params, 'rpt_detail_pr.jrxml', 'PR Detail Report');
        } else {
            $outputPath = $this->getReport($type, $params, 'rpt_detail_pr_excel.jrxml', 'PR Detail Report');
        }
    }

    //PO Detail Report
    public function podetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $ldapdir = new Default_Models_Ldap();
        $request = $this->getRequest();
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');

        //get alamat from master pt table
        $sql = "SELECT CONCAT(nama,'  ',alamat1,'  ',alamat2,' - ',master_kota,'  ',negara,' Telp: ',tlp,' Fax: ',fax) as invoiceTo FROM master_pt";
        $fetch = $this->db->query($sql);
        $data = $fetch->fetchAll();

        $sqls = "SELECT * FROM procurement_poh WHERE trano = '$noTrans'";
        $fetchs = $this->db->query($sqls);
        $datas = $fetchs->fetch();
        $pic = $datas['petugas'];
        $tglKirim = $datas['tgldeliesti'];

        if ($tglKirim == '2000-01-01' || $tglKirim == '1970-01-01') {
            $tglKirim = '';
        } else {
            $tglKirim = date("d M Y", strtotime($tglKirim));
        }
//        $picname = $ldapdir->getAccount($pic);
//        $pic = $picname['displayname'][0];
        $pic = QDC_User_Ldap::factory(array("uid" => $pic))->getName();

//        $printname = $ldapdir->getAccount($this->session->userName);
//        $print = $printname['displayname'][0];
        $print = QDC_User_Session::factory()->getCurrentName();

        $basePath = $this->reportPath . '/images/imd.jpg';

        $type = $request->getParam('type');
        //approval history
        $sqls = "SELECT * FROM workflow_trans WHERE (approve = 200 OR approve = 400) AND item_id = '$noTrans' GROUP BY item_id,workflow_id,uid ORDER BY date DESC";
        $fetchs = $this->db->query($sqls);
        $datas = $fetchs->fetchAll();


        foreach ($datas as $key => $val) {
            if ($val['uid'] == 'jonhar') {
                $approve['od'] = date('d M Y', strtotime($val['date']));
                $approve['odsign'] = $val['signature'];
//                $name = $ldapdir->getAccount($val['uid']);
//                $approve['odname'] = $name['displayname'][0];
                $approve['odname'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
            } else {
                $roleType = $this->workflow->getUserCreByWorkflowItemId($val['workflow_id']);
                if ($roleType) {
                    switch ($roleType['display_name']) {
                        case 'Project Manager':
                            $approve['pm'] = date('d M Y', strtotime($val['date']));
                            $approve['pmsign'] = $val['signature'];
//                            $name = $ldapdir->getAccount($val['uid']);
//                            $approve['pmname'] = $name['displayname'][0];
                            $approve['pmname'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
                            break;
                        case 'Procurement Manager':
                            $approve['prom'] = date('d M Y', strtotime($val['date']));
                            $approve['promsign'] = $val['signature'];
//                            $name = $ldapdir->getAccount($val['uid']);
//                            $approve['promname'] = $name['displayname'][0];
                            $approve['promname'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
                            break;
                        case 'Operations Director':
                            $approve['od'] = date('d M Y', strtotime($val['date']));
                            $approve['odsign'] = $val['signature'];
//                            $name = $ldapdir->getAccount($val['uid']);
//                            $approve['odname'] = $name['displayname'][0];
                            $approve['odname'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
                            break;
                        case 'Construction GM':
                            $approve['gm'] = date('d M Y', strtotime($val['date']));
                            $approve['gmsign'] = $val['signature'];
//                            $name = $ldapdir->getAccount($val['uid']);
//                            $approve['gmname'] = $name['displayname'][0];
                            $approve['gmname'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
                            break;
                        case 'GM Finance & Accounting':
                            $approve['fm'] = date('d M Y', strtotime($val['date']));
                            $approve['fmsign'] = $val['signature'];
//                            $name = $ldapdir->getAccount($val['uid']);
//                            $approve['fmname'] = $name['displayname'][0];
                            $approve['fmname'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
                            break;
                        case 'President Director':
                            $approve['dir'] = date('d M Y', strtotime($val['date']));
                            $approve['dirsign'] = $val['signature'];
//                            $name = $ldapdir->getAccount($val['uid']);
//                            $approve['dirname'] = $name['displayname'][0];
                            $approve['dirname'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
                            break;
                    }
                }
            }
        }

        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'noTrans' => $noTrans,
            'invQdc' => $data[0]['invoiceTo'],
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
        } else {
            $outputPath = $this->getReport($type, $params, 'rpt_detail_po_excel.jrxml', 'PO Detail Report');
        }
    }

    //RPI Detail Report
    public function rpidetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'noTrans' => $noTrans);
        //'basePath' => $basePath);
        if ($type == 'pdf') {

            $outputPath = $this->getReport($type, $params, 'rpt_detail_rpi_pdf.jrxml', 'RPI Report');
        } else {
            $outputPath = $this->getReport($type, $params, 'rpt_detail_rpi.jrxml', 'RPI Report');
        }
    }

    //MDI Detail Report
    public function mdidetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('noTrans' => $noTrans);
        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_detail_mdi.jrxml', 'MDI Detail Report');
    }

    //MDO Detail Report
    public function mdodetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('noTrans' => $noTrans);
        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_detail_mdo.jrxml', 'MDO Detail Report');
    }

    //DO Detail Report
    public function dodetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('noTrans' => $noTrans);
        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_detail_do.jrxml', 'DO Detail Report');
    }

    //ARF&ASF Outstanding Report
    public function arfasfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $type = $request->getParam('type');
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_arfhasfd.jrxml', 'ARF & ASF Report');
    }

    //PO & RPI Report 
    public function porpiAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $supKode = $request->getParam('sup_kode');
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode,
            'supKode' => $supKode,
            'basePath' => $basePath);
        $hasil = $this->transaction->getPorpi($prjKode, $sitKode,$supKode, '', 'detail-rpi');


        $data = new Java("java.util.ArrayList");

        $jum = 0;
        $prevPO_no = '';
        $prevWorkid = '';
        $prevKode_brg = '';
        $prevTotal_IDR = 0;
        $prevTotal_USD = 0;
        $prevBalance_IDR = 0;
        $prevBalance_USD = 0;

        foreach ($hasil as $key => $val) {

            if ($prevPO_no != '' && $prevKode_brg != '' && $prevPO_no == $val['po_no'] && $prevKode_brg == $val['kode_brg']) {
                if ($prevTotal_IDR == $val['totalPO_IDR']) {
                    $val['totalPO_IDR'] = '';
                    $val['balanceIDR'] = $prevBalance_IDR - $val['totalRPI_IDR'];
                    $prevBalance_IDR = $val['balanceIDR'];
//        				echo $val['po_no'] . " " . $prevTotal_IDR . " RPI:" .  $val['totalRPI_IDR'] . " prevbalance:" . $prevBalance_IDR . "->" . $val['balanceIDR'] . "<br/>" ; 
                } elseif ($prevTotal_USD == $val['totalPO_USD']) {
                    $val['totalPO_USD'] = '';
                    $val['balanceUSD'] = $prevBalance_USD - $val['totalRPI_USD'];
                }
            }

            $prevPO_no = $val['po_no'];
            $prevWorkid = $val['workid'];
            $prevKode_brg = $val['kode_brg'];
            if ($val['totalPO_IDR'] != '') {
                $prevTotal_IDR = $val['totalPO_IDR'];
                $prevBalance_IDR = $val['balanceIDR'];
            }
            if ($val['totalPO_USD'] != '') {
                $prevTotal_USD = $val['totalPO_USD'];
                $prevBalance_USD = $val['balanceUSD'];
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
        $outputPath = $this->getReportNoDataSource($type, $data, $params, 'rpt_porpi.jrxml', 'PO & RPI Report');

//	    	$outputPath = $this->getReport($type,$params,'rpt_porpi.jrxml','PO & RPI Report');
    }

    //MDI to MDO Report per Site
    public function mdimdoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode,
            'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_mdimdo.jrxml', 'MDI to MDO Report');
    }

    //MDO to DO Report per Site
    public function mdodoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode,
            'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_mdodo.jrxml', 'MDO to DO Report');
    }

    //PR 
    public function prAction() {

        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';

        $projectDetail = $this->project->getProjectDetail($prjKode);
        $siteDetail = $this->project->getSiteDetail($prjKode, $sitKode);

        $params = array('PMprj_kode' => $prjKode,
            'PMsit_kode' => $sitKode
        );
        $outputPath = $this->getReport($type, $params, 'pr.jrxml', 'pr');
    }

    //Outstanding pr to po report
    public function outprpoAction() {

        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';

        $projectDetail = $this->project->getProjectDetail($prjKode);
        $siteDetail = $this->project->getSiteDetail($prjKode, $sitKode);
        $cusDetail = $this->project->getCustomerDetail($projectDetail['cus_kode']);

        $params = array('PMprj_kode' => $prjKode,
            'PMsit_kode' => $sitKode,
            'userName' => $this->session->userName
        );
        $outputPath = $this->getReport($type, $params, 'outprpo.jrxml', 'outprpo');
    }

    public function outprpodetailAction() {

        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';

        //$projectDetail = $this->project->getProjectDetail($prjKode);
        //$siteDetail = $this->project->getSiteDetail($prjKode, $sitKode);
        //$cusDetail = $this->project->getCustomerDetail($projectDetail['cus_kode']);

        $params = array('PMprj_kode' => $prjKode,
            'PMsit_kode' => $sitKode,
            'userName' => $this->session->userName
        );

        $outputPath = $this->getReport($type, $params, 'outprpodetail.jrxml', 'outprpo');
    }

    public function outprpoprjAction() {

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
        $outputPath = $this->getReport($type, $params, 'outprpoprj.jrxml', 'outprpo');
    }

    public function outprpoprjdetailAction() {

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
        $this->getReport($type, $params, 'outprpoprjdetail.jrxml', 'outprpo');
    }

    public function outprpodetAction() {

        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';

        $projectDetail = $this->project->getProjectDetail($prjKode);
        $siteDetail = $this->project->getSiteDetail($prjKode, $sitKode);
        $cusDetail = $this->project->getCustomerDetail($projectDetail['cus_kode']);

        $params = array('PMprj_kode' => $prjKode,
            'PMsit_kode' => $sitKode
        );
        $outputPath = $this->getReport($type, $params, 'outprpodet.jrxml', 'outprpo');
    }

    public function outmdimdoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $group = $request->getParam('group');
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        //$this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode,
            'basePath' => $basePath);

        $data = new Java("java.util.ArrayList");

        $result = $this->transaction->getdortodo($prjKode, $sitKode, $group);
        foreach ($result as $k => $v) {
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
        $outputPath = $this->getReportNoDataSource($type, $data, $params, 'rpt_mdimdo.jrxml', 'DOR to DO Report');
    }

    public function outmdodoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode,
            'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_mdodo.jrxml', 'MDO to DO Report');
    }

    //wh return
    public function whreturnAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
        //$stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => "$prjKode",
            'sitKode' => "$sitKode");
        // 'stgl1'    => $stgl1,
        // 'stgl2'    => $stgl2);
        //'basePath' => $basePath);

        $outputPath = $this->getReport($type, $params, 'rpt_whreturn.jrxml', 'Material Return Report');
    }

    //wh bringback
    public function whbringbackAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $stgl1 = date('Y-m-d', strtotime($request->getParam('tgl1')));
        $stgl2 = date('Y-m-d', strtotime($request->getParam('tgl2')));
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode,
            'stgl1' => $stgl1,
            'stgl2' => $stgl2);
        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_whbringback.jrxml', 'Material Cancel Report');

        //var_dump($params);
    }

    //wh supplier
    public function whsupplierAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $param = $request->getParam('param');
        $tgl = $request->getParam('tgl');

        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode,
            'supKode' => $supKode,
            'param' => $param,
            'tgl' => $tgl
        );
        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_whsupplier.jrxml', 'Material Delivery Instruction Report');

        //var_dump($params);
    }

    public function gencardstockAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $gdg_kode = $this->_getParam("gdg_kode");

        $perkode = $this->_getParam("periode");
        $nama_brg = $this->_getParam("nama_brg");
        $kode_brg = $this->_getParam("kode_brg");
        $user = $this->view->uid = $this->session->userName;

        if ($this->getRequest()->getParam("all_product") == 'true' || $this->getRequest()->getParam("all_product") == '1')
            $all_product = true;
        else
            $all_product = false;


        $data = QDC_Inventory_Stock::factory()->getStockAll(array(
            "kode_brg" => $kode_brg,
            "gdg_kode" => $gdg_kode,
            "nama_brg" => $nama_brg,
            "periode" => $perkode,
            "all_product" => $all_product
        ));

        $datas = new Java("java.util.ArrayList");
        $totalin = 0;
        $totalout = 0;
        $totalbalance = 0;
        $m = new Default_Models_MasterBarang();

        foreach ($data['data'] as $k => $v) {
            $kode_brg = $v['kode_brg'];
            $satuan = $m->getUom("$kode_brg");
            $datas->add(array(
                "periode" => date("F / Y", strtotime($perkode)),
                "id" => $v['id'],
                "kode_brg" => $v['kode_brg'],
                "nama_brg" => $v['nama_brg'],
                "gdg_kode" => $v['gdg_kode'],
                "uom" => $satuan,
                "masuk" => number_format($v['masuk'], 2),
                "keluar" => number_format($v['keluar'], 2),
                "saldo" => number_format($v['saldo'], 2),
                "balance" => number_format($data['data'][$k]['balance'], 2)
            ));
        }

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('gudang_kode' => $gdg_kode,
            'tanggal' => $perkode,
            'username' => $user,
            'type' => $type
        );
        //'basePath' => $basePath);
        $outputPath = $this->getReportNoDataSource($type, $datas, $params, 'r_cardstock.jrxml', 'General Card Stock Report');

        //var_dump($params);
    }

    public function detcardstockAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $gdg_kode = $request->getParam("gdg_kode");
        $kode_brg = $request->getParam("kode_brg");
        $perkode = $this->_getParam("periode");
        $user = $this->view->uid = $this->session->userName;

        $data = QDC_Inventory_Stock::factory()->getStock(array(
            "kode_brg" => $kode_brg,
            "gdg_kode" => $gdg_kode,
            "periode" => $perkode
        ));

        $nama_brg = $data['data']['0']['nama_brg'];

        foreach ($data['data']as $k => $v) {

            if ($v['trano'] == '' && $v['urut'] == '0') {
                $data['data'][$k]['balance'] = $saldo + $v['saldo'];
                $saldo = $data['data'][$k]['balance'];
            }
            if ($v['saldo'] == '0') {

                if ($v['masuk'] !== '0') {
                    $data['data'][$k]['balance'] = $saldo + $v['masuk'];
                    $saldo = $data['data'][$k]['balance'];
                }

                if ($v['keluar'] !== '0') {
                    $data['data'][$k]['balance'] = $saldo - $v['keluar'];
                    $saldo = $data['data'][$k]['balance'];
                }
            }
        }

        $datas = new Java("java.util.ArrayList");
        $totalin = 0;
        $totalout = 0;
        $lastbalance = 0;

        foreach ($data['data'] as $k => $v) {
            if ($v['trano'] == '' && $v['urut'] == '0') {
                $v['trano'] = 'Beginning Balance';
//                $data['data'][$k]['balance'] = $v['saldo'];
            }

            $datas->add(array(
                "period" => date("F / Y", strtotime($perkode)),
                "id" => $v['id'],
                "trano" => $v['trano'],
                "kode_brg" => $v['kode_brg'],
                "nama_brg" => $v['nama_brg'],
                "gdg_kode" => $v['gdg_kode'],
                "masuk" => number_format($v['masuk'], 2),
                "keluar" => number_format($v['keluar'], 2),
                "saldo" => number_format($v['saldo'], 2),
                "tgl" => ($v['tgl'] != '' ? date("Y-m-d", strtotime($v['tgl'])) : '-'),
                "urut" => $v['urut'],
                "balance" => number_format($data['data'][$k]['balance'], 2)
            ));

            $totalin += $v['masuk'];
            $totalout += $v['keluar'];
            $lastbalance = $data['data'][$k]['balance'];
        }

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';

        $this->_helper->viewRenderer->setNoRender();

        $params = array('gudang_kode' => $gdg_kode,
            'kode_brg' => $kode_brg,
            'nama_brg' => $nama_brg,
            'tanggal' => $perkode,
            'username' => $user,
            "type" => $type,
            "totalin" => number_format($totalin, 2),
            "totalout" => number_format($totalout, 2),
            "lastbalance" => number_format($lastbalance, 2)
        );

        $outputPath = $this->getReportNoDataSource($type, $datas, $params, 'r_cardstockdetail.jrxml', 'Detail Card Stock Report');
    }

    public function doAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
//        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
//        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

//	    	$params = array('prjKode'  => $prjKode,
//                                'sitKode'  => $sitKode,
//                                'stgl1'    => $stgl1,
//                                'stgl2'    => $stgl2);

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode);

        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_do.jrxml', 'Delivery Order Report');

        //var_dump($params);
    }

    public function dorAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
//        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
//        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

//	    	$params = array('prjKode'  => $prjKode,
//                                'sitKode'  => $sitKode,
//                                'stgl1'    => $stgl1,
//                                'stgl2'    => $stgl2);

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode);

        //'basePath' => $basePath);
        $outputPath = $this->getReport($type, $params, 'rpt_dor.jrxml', 'Delivery Order Summary Report');

        //var_dump($params);
    }

    public function mdiAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('PMprj_kode' => $prjKode,
            'PMsit_kode' => $sitKode);
        $outputPath = $this->getReport($type, $params, 'rmdi.jrxml', 'Material Delivery Instruction Report');
    }

    //MDO Summary Report
    public function mdosummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$basePath = $this->reportPath . '/images/imd2.jpg';

        $type = $request->getParam('type');
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('prjKode' => $prjKode,
            'sitKode' => $sitKode);
        $outputPath = $this->getReport($type, $params, 'rpt_sum_mdo.jrxml', 'MDO Summary Report');
    }

    public function progressprojectbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $type = $request->getParam('type');

        $data = new Java("java.util.ArrayList");
        $totalBoq2_ori = 0;
        $totalBoq2_current = 0;
        $totalBoq3_ori = 0;
        $totalBoq3_current = 0;
        $totalBoq4_current = 0;
        $totalFinal = 0;

        //Get updated exchange rate from database
        $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			-- FROM exchange_rate
                        FROM finance_exchange_rate
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
        if ($sitKode == '') {
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

            for ($i = 0; $i < count($master); $i++) {
                $sitKode = $master[$i]['sit_kode'];
                $result = $this->budget->compareBoq($prjKode, $sitKode);
                $final = 0;
                $finalIDR = 0;
                $finalRate = 0;
                $grossIDR = 0;
                $grossRate = 0;
                foreach ($result as $key => $val) {
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

                    if ($val['boq2_current'] > 0) {
                        $grossIDR = number_format(((($$val['boq2_current'] - $finalIDR) / ($val['boq2_current'])) * 100), 2);
                        $grossRate = number_format((((($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr)) - $finalRate) / (($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr)))) * 100), 2);
                    } else {
                        $grossIDR = 0;
                        $grossRate = 0;
                    }

                    $data->add(array("sit_kode" => $sit_kode
                        , "sit_nama" => $sit_nama
                        , 'boq2_currentHargaIDR' => number_format($val['boq2_currentHargaIDR'], 2)
                        , 'boq2_currentHargaUSD' => number_format($val['boq2_currentHargaUSD'], 2)
                        , 'boq3_currentHargaIDR' => number_format($val['boq3_currentHargaIDR'], 2)
                        , 'boq3_currentHargaUSD' => number_format($val['boq3_currentHargaUSD'], 2)
                        , 'boq4_currentHargaIDR' => number_format($val['boq4_currentHargaIDR'], 2)
                        , 'boq4_currentHargaUSD' => number_format($val['boq4_currentHargaUSD'], 2)
                        , 'mip_currentIDR' => number_format($val['mip_currentHargaIDR'], 2)
                        , 'mip_currentUSD' => number_format($val['mip_currentHargaUSD'], 2)
                        , 'returnHargaIDR' => number_format($val['returnHargaIDR'], 2)
                        , 'returnHargaUSD' => number_format($val['returnHargaUSD'], 2)
                        , 'final_rate' => number_format($finalRate, 2)
                        , 'gross' => number_format($grossRate, 2) . '%'
                        , 'totBoq2' => number_format($totBoq2, 2)
                        , 'totBoq3' => number_format($totBoq3, 2)
                        , 'totBoq4' => number_format($totBoq4, 2)
                        , 'totMip' => number_format($totMip, 2)
                        , 'totReturn' => number_format($totRet, 2)
                    ));

                    $grandTotalFinal +=$finalIDR;
                    $grandTotalFinalRate +=$finalRate;
                }
            }
            if ($grandTotalBoq2Rate > 0 && $grandTotalFinalRate > 0)
                $totalGross = number_format((($grandTotalBoq2Rate - $grandTotalFinalRate) / ($grandTotalBoq2Rate) * 100), 2);
        }
        else {
            $result = $this->budget->compareBoq($prjKode, $sitKode);
            $final = 0;
            $finalIDR = 0;
            $finalRate = 0;
            $grossIDR = 0;
            $grossRate = 0;
            foreach ($result as $key => $val) {

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

                if ($val['boq2_current'] > 0) {
                    $grossIDR = number_format(100 - ((($val['boq2_current'] - $val['boq4_current'] + $totRet) / ($val['boq2_current'])) * 100), 2);
                    $grossRate = number_format(100 - (((($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $this->rateidr)) - ($val['boq3_currentHargaIDR'] + ($val['boq3_currentHargaUSD'] * $rateidr)) + ($val['returnHargaIDR'] + ($val['returnHargaUSD'] * $rateidr))) / (($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr)))) * 100), 2);
//					$grossIDR = number_format(((($val['boq2_current'] - $finalIDR) / ($val['boq2_current']))*100),2);
//	    			$grossRate = number_format((((($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $this->rateidr)) - $finalRate) / (($val['boq2_currentHargaIDR'] + ($val['boq2_currentHargaUSD'] * $rateidr))))*100),2);
                } else {
                    $grossIDR = 0;
                    $grossRate = 0;
                }

                $data->add(array("sit_kode" => $sit_kode
                    , "sit_nama" => $sit_nama
                    , 'boq2_currentHargaIDR' => number_format($val['boq2_currentHargaIDR'], 2)
                    , 'boq2_currentHargaUSD' => number_format($val['boq2_currentHargaUSD'], 2)
                    , 'boq3_currentHargaIDR' => number_format($val['boq3_currentHargaIDR'], 2)
                    , 'boq3_currentHargaUSD' => number_format($val['boq3_currentHargaUSD'], 2)
                    , 'boq4_currentHargaIDR' => number_format($val['boq4_currentHargaIDR'], 2)
                    , 'boq4_currentHargaUSD' => number_format($val['boq4_currentHargaUSD'], 2)
                    , 'mip_currentIDR' => number_format($val['mip_currentHargaIDR'], 2)
                    , 'mip_currentUSD' => number_format($val['mip_currentHargaUSD'], 2)
                    , 'returnHargaIDR' => number_format($val['returnHargaIDR'], 2)
                    , 'returnHargaUSD' => number_format($val['returnHargaUSD'], 2)
                    , 'final_rate' => number_format($finalRate, 2)
                    , 'gross' => $grossRate . '%'
                    , 'totBoq2' => number_format($totBoq2, 2)
                    , 'totBoq3' => number_format($totBoq3, 2)
                    , 'totBoq4' => number_format($totBoq4, 2)
                    , 'totMip' => number_format($totMip, 2)
                    , 'totReturn' => number_format($totRet, 2)));

                $grandTotalFinal +=$finalIDR;
                $grandTotalFinalRate +=$finalRate;
            }
            if ($grandTotalBoq2Rate > 0 && $grandTotalFinalRate > 0)
                $totalGross = number_format(100 - (($grandTotalBoq2Rate - $grandTotalBoq3Rate + $grandTotalRetRate) / ($grandTotalBoq2Rate) * 100), 2);
        }

        $exchange_rate = "<b>USD Exchange Rate : Rp. $rateidr, Last updated : $tgl</b>";
        $ket_project = "<b>$prjKode - $prj_nama</b><br><b>IDR* = Use updated exchange rate</b>";
        $params = array(
            "gtotalBoq2" => number_format($grandTotalBoq2Rate, 2),
            "gtotalBoq3" => number_format($grandTotalBoq3Rate, 2),
            "gtotalBoq4" => number_format($grandTotalBoq4Rate, 2),
            "gtotalMip" => number_format($grandTotalMipRate, 2),
            "gtotalReturn" => number_format($grandTotalRetRate, 2),
            "gtotalFinal" => number_format($grandTotalFinalRate, 2),
            "gtotalGross" => $totalGross . '%',
            "exchange_rate" => $exchange_rate,
            "ket_project" => $ket_project
        );
        $outputPath = $this->getReportNoDataSource($type, $data, $params, 'progressprojectbudget.jrxml', 'Progress Project Budget');
    }

    public function projectbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $show = $request->getParam('show');
        if ($show == 'Detail')
            $detail = true;
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
        $totalBoq2_ori = 0;
        $totalBoq2_current = 0;
        $totalBoq3_ori = 0;
        $totalBoq3_current = 0;
        $totalBoq4_current = 0;
        $totalFinal = 0;

        //Get updated exchange rate from database
        $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			-- FROM exchange_rate
                        FROM finance_exchange_rate
       			WHERE val_kode='USD'
       			ORDER BY tgl DESC
       			LIMIT 0,1";

        $fetch = $this->db->query($sql);
        $datas = $fetch->fetch();

        $rateidr = $datas['rateidr'];
        $tgl = $datas['tgl_rate'];

        if ($sitKode == '') {
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

            for ($i = 0; $i < count($master); $i++) {
                $sitKode = $master[$i]['sit_kode'];

                $result = $this->budget->getBudgetProject(false, $prjKode, $sitKode, $detail);
                foreach ($result as $key => $val) {
                    if ($result[$key]['stsoverhead'] == 'Y') {
                        $result[$key]['boq2_ori'] = 0;
                        $result[$key]['boq2_current'] = 0;
                        $result[$key]['boq2_oriIDR'] = 0;
                        $result[$key]['boq2_oriUSD'] = 0;
                        $result[$key]['boq2_currentIDR'] = 0;
                        $result[$key]['boq2_currentUSD'] = 0;
                    }
                    if (!$userRate) {
                        $boq2_ori = ($result[$key]['boq2_ori'] );
                        $boq2_current = ($result[$key]['boq2_current'] );
                        $boq3_current = ($result[$key]['boq3_current'] );
                        $boq3_ori = ($result[$key]['boq3_ori'] );
                        $mip_current = ($result[$key]['mip_current']);
                        $boq4_current = ($result[$key]['boq4_current']);
                        $finalCost = ($result[$key]['finalCost']);
                    } else {
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

                    if (($boq3_ori > 0 || $boq3_ori != '') && ($boq2_ori > 0 || $boq2_ori != '')) {
                        $jPersen = number_format((($boq2_ori - $boq3_ori) / $boq2_ori) * 100, 2, '.', '');
                    } else {
                        $jPersen = 0;
                    }
                    if (($boq2_current > 0 || $boq2_current != '') && ($boq3_current > 0 || $boq3_current != '')) {
                        $lPersen = number_format(((($boq2_current - $boq3_current) / $boq2_current) * 100), 2, '.', '');
                    } else {
                        $lPersen = 0;
                    }
                    if (($boq2_current > 0 || $boq2_current != '') && ($boq3_current > 0 || $boq3_current != '')) {
                        $nPersen = number_format((($finalCost / $boq2_current) * 100), 2, ',', '');
                    } else {
                        $nPersen = 0;
                    }

                    $data->add(array("sit_kode" => $sit_kode, "sit_nama" => $sit_nama, "boq2_ori" => number_format($boq2_ori), "boq2_current" => number_format($boq2_current), "boq3_ori" => number_format($boq3_ori), "boq3_current" => number_format($boq3_current), "boq4_current" => number_format($boq4_current), "gross_ori" => number_format($jPersen, 2) . '%', "gross_current" => number_format($lPersen, 2) . '%'));
                }
            }
        } else {
            $result = $this->budget->getBudgetProject(false, $prjKode, $sitKode, $detail);
            foreach ($result as $key => $val) {
                if ($result[$key]['stsoverhead'] == 'Y') {
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
                if (!$userRate) {
                    $boq2_ori = ($result[$key]['boq2_ori'] );
                    $boq2_current = ($result[$key]['boq2_current'] );
                    $boq3_current = ($result[$key]['boq3_current'] );
                    $boq3_ori = ($result[$key]['boq3_ori'] );
                    $mip_current = ($result[$key]['mip_current']);
                    $boq4_current = ($result[$key]['boq4_current']);
                    $finalCost = ($result[$key]['finalCost']);
                } else {
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

                if (($boq3_ori > 0 || $boq3_ori != '') && ($boq2_ori > 0 || $boq2_ori != '')) {
                    $jPersen = number_format((($boq2_ori - $boq3_ori) / $boq2_ori) * 100, 2, '.', '');
                } else {
                    $jPersen = 0;
                }
                if (($boq2_current > 0 || $boq2_current != '') && ($boq3_current > 0 || $boq3_current != '')) {
                    $lPersen = number_format(((($boq2_current - $boq3_current) / $boq2_current) * 100), 2, '.', '');
                } else {
                    $lPersen = 0;
                }
                if (($boq2_current > 0 || $boq2_current != '') && ($boq3_current > 0 || $boq3_current != '')) {
                    $nPersen = number_format((($finalCost / $boq2_current) * 100), 2, ',', '');
                } else {
                    $nPersen = 0;
                }

                $data->add(array("sit_kode" => $sit_kode, "sit_nama" => $sit_n100ama, "boq2_ori" => number_format($boq2_ori), "boq2_current" => number_format($boq2_current), "boq3_ori" => number_format($boq3_ori), "boq3_current" => number_format($boq3_current), "boq4_current" => number_format($boq4_current), "gross_ori" => number_format($jPersen, 2) . '%', "gross_current" => number_format($lPersen, 2) . '%'));
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
            "totGross_ori" => number_format($oriPersen, 2) . '%',
            "totGross_current" => number_format($currentPersen, 2) . '%',
            "exchange_rate" => $exchange_rate,
            "ket_project" => $ket_project
        );
        $outputPath = $this->getReportNoDataSource($type, $data, $params, 'projectbudget.jrxml', 'Project Budget');
    }

    //DO Printout from Warehouse
    public function doprintAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $quantity = $this->_helper->getHelper('quantity');
        $type = $request->getParam('type');
        $trano = $request->getParam('trano');

        $data = new Java("java.util.ArrayList");

        $hasil = $this->db->query("SELECT * FROM procurement_whoh WHERE trano = '$trano'");
        if ($hasil) {
            $header = $hasil->fetchAll();
            $hasil = $this->db->query("SELECT * FROM procurement_whod WHERE trano = '$trano'");
            if ($hasil) {
                $hasil = $hasil->fetchAll();
            }
        }
        foreach ($hasil as $key => $val) {
            $data->add(array(
                'qty' => number_format($val['qty'], 0)
                , 'kode_brg' => $val['kode_brg']
                , 'nama_brg' => $val['nama_brg']
                , 'ket' => $val['ket']
                , 'uom' => $quantity->getUOMByProductID($val['kode_brg'])
            ));
        }
        if ($type == '')
            $type == 'pdf';
        $this->_helper->viewRenderer->setNoRender();

        $params = array('trano' => $header[0]['trano'],
            'prj_kode' => $header[0]['prj_kode'],
            'prj_nama' => $header[0]['prj_nama'],
            'dest_nama' => $header[0]['deliveryto'],
            'tgl' => date('d M Y'),
            'receiver_nama' => '',
            'receiver_tlp' => '',
            'receiver_fax' => ''
        );
        $outputPath = $this->getReportNoDataSource($type, $data, $params, 'doprint2.jrxml', 'Delivery Order Printout (' . $trano . ')');
    }

    //timesheet Report
    public function timesheetAction() {
        $this->_helper->viewRenderer->setNoRender();

        $uid = rawurldecode($this->getRequest()->getParam("uid"));
        $prjKode = rawurldecode($this->getRequest()->getParam("prj_kode"));
        $periode = rawurldecode($this->getRequest()->getParam("periode"));
        $types = rawurldecode($this->getRequest()->getParam("type"));
        $type = 'xls';

        $data = new Java("java.util.ArrayList");

        if ($uid)
            $where = " uid = '$uid'";
        if ($prjKode) {
            if ($where)
                $where .= " AND prj_kode = '$prjKode'";
            else
                $where = "prj_kode = '$prjKode'";
        }
        if ($periode) {
            $periodeHR = new HumanResource_Model_SetPeriode();
            $fetch = $periodeHR->fetchRow("id = $periode");
            $totalHour = $fetch['jumlah_jam_bulan'];
            $timestamp = mktime(0, 0, 0, intval($fetch['periode']), 1, floatval(date("Y")));
            $month = date("F", $timestamp);
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

        if ($where != '') {
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

        if ($hasil) {
            foreach ($hasil as $key => $val) {
                $data->add(array(
                    'npk' => $val['npk']
                    , 'prj_kode' => $val['prj_kode']
                    , 'prj_nama' => $val['prj_nama']
                    , 'name' => $val['name']
                    , 'hour' => floatval($val['hour'])
                    , 'totalhour' => floatval($val['totalhour'])
                    , 'totalhour1' => floatval($totalHour)
                    , 'persen' => floatval($val['persen'])
                    , 'persen2' => floatval($val['persen2'])
                ));
            }
        }

        if ($type == '')
            $type == 'pdf';

        $params = array('tgl1' => $tgl1,
            'tgl2' => $tgl2);
        //'basePath' => $basePath);
        $outputPath = $this->getReportNoDataSource($type, $data, $params, 'timesheetreport.jrxml', 'Timesheet Report');
    }

    //timesheet detail Report
    public function timesheetdetailAction() {
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
        if ($prjKode) {
            if ($where)
                $where .= " AND prj_kode = '$prjKode'";
            else
                $where = "prj_kode = '$prjKode'";
        }
        if ($tgl1 && $tgl2) {
            $tgl1 = date("Y-m-d H:i:s", strtotime($tgl1));
            $tgl2 = date("Y-m-d H:i:s", strtotime($tgl2));
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

        if ($where != '') {
            $whereas = "WHERE $where";
            $where = " AND $where";
        }

        $sql = "SELECT
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

        if ($hasil) {
            foreach ($hasil as $key => $val) {
                $data->add(array(
                    'npk' => $val['npk']
                    , 'prj_kode' => $val['prj_kode']
                    , 'prj_nama' => $val['prj_nama']
                    , 'name' => $val['name']
                    , 'start' => date('d-m-Y H:i:s', strtotime($val['start']))
                    , 'end' => date('d-m-Y H:i:s', strtotime($val['end']))
                    , 'trano' => $val['trano']
                    , 'hour' => floatval($val['hour'])
                ));
            }
        }

        $params = array('tgl1' => $tgl1,
            'tgl2' => $tgl2);
        //'basePath' => $basePath);
        $outputPath = $this->getReportNoDataSource($type, $data, $params, 'timesheetdetailreport.jrxml', 'Timesheet Detail Report');
    }

    public function reimbursreportAction() {
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

        $dd = new Finance_Models_NDreimbursDetailAdditional();
        $totalDeductionBefore = $totalDeductionAfter = 0;
        //before total
        $select = $this->db->select()
                ->from(array($dd->__name()), array(
                    "ket",
                    "total",
                    "type",
                    "val_kode",
                ))
                ->where("trano=?", $noTrans)
                ->where("pos='BEFORE_TOTAL'");

        $res = $this->db->fetchAll($select);
        $theListB = new Java("java.util.ArrayList");
        $theListB->clear();
        if ($res) {
            foreach ($res as $k2 => $v2) {
                if ($v2['type'] == 'ADDITION') {
                    $totalDeductionBefore += $v2['total'];
                    $res[$k2]['total'] = number_format($v2['total'], 2);
                }
                if ($v2['type'] == 'DEDUCTION') {
                    $totalDeductionBefore -= $v2['total'];
                    $res[$k2]['total'] = "(" . number_format($v2['total'], 2) . ")";
                }
                $curr = $v2['val_kode'];

                if ($curr == 'IDR') {
                    $curr = 'IDR';
                } else if ($curr == 'USD') {
                    $curr = 'USD';
                }
                $res[$k2]['val_kode'] = $curr;
            }

            foreach ($res as $k2 => $v2) {
                $theListB->add($v2);
            }
        }

        $theArray['value_before'] = $theListB;

        //after total
        $select = $this->db->select()
                ->from(array($dd->__name()), array(
                    "ket",
                    "total",
                    "type",
                    "val_kode"
                ))
                ->where("trano=?", $noTrans)
                ->where("pos='AFTER_TOTAL'");

        $res = $this->db->fetchAll($select);
        $theListA = new Java("java.util.ArrayList");
        $theListA->clear();
        if ($res) {
            foreach ($res as $k2 => $v2) {
                if ($v2['type'] == 'ADDITION') {
                    $totalDeductionAfter += $v2['total'];
                    $res[$k2]['total'] = number_format($v2['total'], 2);
                }
                if ($v2['type'] == 'DEDUCTION') {
                    $totalDeductionAfter -= $v2['total'];
                    $res[$k2]['total'] = "(" . number_format($v2['total'], 2) . ")";
                }
                $curr = $v2['val_kode'];

                if ($curr == 'IDR') {
                    $curr = 'IDR';
                } else if ($curr == 'USD') {
                    $curr = 'USD';
                }
                $res[$k2]['val_kode'] = $curr;
            }

            foreach ($res as $k2 => $v2) {
                $theListA->add($v2);
            }
        }

        $theArray['value_after'] = $theListA;

        $where = "trano = '$noTrans'";

        $detaildata = $this->nota_debit_header->fetchRow($where)->toArray();
        $paymentValue = floatval($detaildata['total']);

        if ($detaildata) {
            if ($detaildata['statusppn'] == 'Y') {
                $pajak = ($paymentValue) * 0.1;
            } else {
                $pajak = 0;
            }
            $gtotal = ($paymentValue + ($pajak + $totalDeductionBefore)) + $totalDeductionAfter;
        }

        $dntotal = $paymentValue;
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

        if ($hasil) {
            $ldapdir = new Default_Models_Ldap();
            foreach ($hasil as $key => $val) {
//                $account = $ldapdir->getAccount($val['uid_manager']);
//                $hasil[$key]['display_name'] = $account['displayname'][0];
                $hasil[$key]['display_name'] = QDC_User_Ldap::factory(array("uid" => $val['uid_manager']))->getName();
                $hasil[$key]['role_name'] = $val['display_name'];
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
            'trano' => $noTrans,
            'pajak' => number_format($pajak, 2),
            'gtotal' => number_format($gtotal, 2),
            'dntotal' => number_format($dntotal, 2),
            'paymentnotes' => $paymentnotes,
            'paymentterm' => $detaildata['paymentterm'],
            'cus_nama' => $cus_nama,
            'logoPath' => $this->reportPath . '/images/imd.jpg',
            'basePath' => $this->reportPath,
            'cus_alamat' => $cus_alamat,
            'signname' => $signname,
            'signtitle' => $signtitle,
            'terbilang' => $terbilang . " Indonesia Rupiah and 0 Cents Only",
            'destination' => $destination,
            'destinationaddress' => $destinationaddress,
            "signature" => $signature,
            "val_kode" => $detaildata['val_kode'],
            "bnk_nama" => $detaildata['bnk_nama'],
            "bnk_norek" => $detaildata['bnk_norek'],
            "bnk_noreknama" => $detaildata['bnk_noreknama'],
            "bnk_cabang" => $detaildata['bnk_cabang'],
            "bnk_alamat" => $detaildata['bnk_alamat'],
            "bnk_kota" => $detaildata['bnk_kota'],
        );

        $detaildata["value_before"] = $theListB;
        $detaildata["value_after"] = $theListA;
        $detaildata['dntotal'] = number_format($dntotal, 2);
        $detaildata['total_amount'] = number_format(($paymentValue + $totalDeductionBefore), 2);

        if ($pajak > 0) {
            $detaildata['ppn_text'] = 'VAT 10%';
            $detaildata['ppn'] = number_format($pajak, 2);
        } else {
            $detaildata['ppn_text'] = null;
            $detaildata['ppn'] = null;
        }

        QDC_Jasper_Report::factory(
                array(
                    "reportType" => $type,
                    "arrayData" => array($detaildata),
                    "arrayParams" => $params,
                    "fileName" => 'reimburs_nota_debet.jrxml',
                    "outputName" => 'Reimbursement Debit Note',
                    "dataSource" => 'NoDataSource'
                )
        )->generate();
//        $outputPath = $this->getReport($type, $params, 'reimburs_nota_debet.jrxml', 'Reimbursement Debit Note');
    }

    public function invoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $ldapdir = new Default_Models_Ldap();
        $request = $this->getRequest();
        $noTrans = $request->getParam('trano');
        $type = $request->getParam('type');
        $sign = $request->getParam('sign');
        $rateidr = $request->getParam('rateidr');
        $noFaktur = $request->getParam('no_faktur_pajak');
        $lembar = $request->getParam('lembar');
        $notation = ($request->getParam('notation') == "true") ? true : false;

        $uid = QDC_User_Session::factory()->getCurrentUID();

        $data = new Java("java.util.ArrayList");

        $detail = new Finance_Models_InvoiceDetail();
        $dd = new Finance_Models_InvoiceDetailDeduction();
        $header = new Finance_Models_Invoice();
        $MsProject = new Default_Models_MasterProject();
        $logprint = new Default_Models_LogPrint();
        $customer = new Logistic_Model_LogisticCustomer();

        $where = "trano = '$noTrans'";


        $detaildata = $header->fetchRow($where);

//        $detaildata['alamatpajak'] = str_replace("\n","<br>",$detaildata['alamatpajak']);
        $hasil = $detail->fetchAll($where);

        $cus_kode = $hasil[0]['cus_kode'];
        $isUSD = false;

        $arrayData = array();
        if ($hasil) {
            $totalInvoice = 0;
            $totalPPN = 0;
            foreach ($hasil as $k => $val) {
                $totalDetail = $totalAmount = $totalDeductionBefore = $totalDeductionAfter = 0;

                $before = array();

                //deduction_before total
                $select = $this->db->select()
                        ->from(array($dd->__name()), array(
                            "ket",
                            "total",
                            "val_kode"
                        ))
                        ->where("trano=?", $val['trano'])
                        ->where("riv_no=?", $val['riv_no'])
                        ->where("id_invoiced=?", $val['id'])
                        ->where("type='DEDUCTION'")
                        ->where("pos='BEFORE_TOTAL'");

                $res = $this->db->fetchAll($select);
                if ($res) {
                    foreach ($res as $k2 => $v2) {
                        $totalDeductionBefore += $v2['total'];
                    }
                    $before = $res;
                }

                $ppn = $val['statusppn'];
                $ht = $val['holding_tax_status'];
                $totalDetail = $val['total'];
                if ($ppn == 'Y') {
                    $ppn = 'Add VAT 10%';
                    $ppnvalue = number_format((floatval($totalDetail) - $totalDeductionBefore) * 0.1, 2);
                    $totalPPN += ((floatval($totalDetail) - $totalDeductionBefore) * 0.1);
                } else {
                    $ppn = null;
                    $ppnvalue = "";
                }

                if ($ht == 'Y') {
                    $ht_text = $val['holding_tax_text'];
                    $ht_persen = floatval($val['holding_tax'] * 100);

                    if ($ht_persen == 0) {
                        $ht_persen = null;
                    } else {
                        $ht_text = ($ht_text == '') ? 'With Holding Tax' : $ht_text;
                        $ht_text .= " " . $ht_persen . '%';
                    }

                    $ht_value = '(' . number_format(floatval($val['holding_tax_val']), 2) . ')';
                } else {
                    $ht_text = null;
                    $ht_persen = '';
                    $ht_value = '';
                }

                $gtotal += floatval($val['jumlah']);

                $valuta = $val['val_kode'];

                if ($valuta == 'IDR') {
                    $valuta = 'Rp.';
                } else if ($valuta == 'USD') {
                    $isUSD = true;
                    $valuta = 'US$';
                }

                $deduction_text = '';
                $deduction = floatval($val['deduction']);

                if ($deduction != 0) {
                    $deduction = '(' . number_format($deduction, 2) . ')';
                    $deduction_text = 'Deduction';
                } else {
                    $deduction = '';
                    $deduction_text = null;
                }

                $theArray = array(
                    'nama_brg' => $val['nama_brg'],
                    'ket' => ($val['ket'] == '') ? null : $val['ket'],
                    'total' => number_format($totalDetail, 2),
                    'total_with_deduction' => $valuta . '      ' . number_format((floatval($totalDetail) - $totalDeductionBefore), 2),
                    'ppn' => $ppn,
                    'ppnvalue' => $ppnvalue,
                    'ht_text' => $ht_text,
                    'ht_persen' => $ht_persen,
                    'ht_value' => $ht_value,
                    'val_kode' => $valuta
//                    'deduction' => $deduction,
//                    'deduction_text' => $deduction_text
                );

                $theListB = new Java("java.util.ArrayList");
                $theListB->clear();
                if ($before) {
                    foreach ($before as $k2 => $v2) {
                        $before[$k2]['total'] = "(" . number_format($v2['total']) . ")";
                        $curr = $v2['val_kode'];

                        if ($curr == 'IDR') {
                            $curr = 'Rp.';
                        } else if ($curr == 'USD') {
                            $curr = '$';
                        }
                        $before[$k2]['val_kode'] = $curr;
                    }

                    foreach ($before as $k2 => $v2) {
                        $theListB->add($v2);
                    }
                }

                $theArray['deduction_before'] = $theListB;

                //deduction_after total
                $select = $this->db->select()
                        ->from(array($dd->__name()), array(
                            "ket",
                            "total",
                            "val_kode"
                        ))
                        ->where("trano=?", $val['trano'])
                        ->where("riv_no=?", $val['riv_no'])
                        ->where("id_invoiced=?", $val['id'])
                        ->where("type='DEDUCTION'")
                        ->where("pos='AFTER_TOTAL'");

                $res = $this->db->fetchAll($select);
                $theListA = new Java("java.util.ArrayList");
                $theListA->clear();
                if ($res) {
                    foreach ($res as $k2 => $v2) {
                        $totalDeductionAfter += $v2['total'];
                        $res[$k2]['total'] = "(" . number_format($v2['total']) . ")";
                        $curr = $v2['val_kode'];

                        if ($curr == 'IDR') {
                            $curr = 'Rp.';
                        } else if ($curr == 'USD') {
                            $curr = '$';
                        }
                        $res[$k2]['val_kode'] = $curr;
                    }

                    foreach ($res as $k2 => $v2) {
                        $theListA->add($v2);
                    }
                }

                $theArray['deduction_after'] = $theListA;
                $theArray['total_amount'] = number_format(($totalDetail - $totalDeductionBefore), 2);

                $theArray['with_detail'] = "0";
                if ($val['invoice_detail'] != null && $val['invoice_detail'] != 'Array')
                    $dataDetailInvoice = Zend_Json::decode($val['invoice_detail']);
//                
                $dataDetail = new Java("java.util.ArrayList");
                if ($dataDetailInvoice) {
                    foreach ($dataDetailInvoice as $key => $v) {

                        $dataDetail->add(array(
                            'invoice_detail_desc' => $v['ket'],
                            'invoice_detail_total' => number_format($v['total'], 2),
                            'invoice_detail_totalUSD' => number_format(floatval($v['total'] * $val['rateidr']), 2),
                            'val_kode' => $valuta,
                            'val_kode_ori' => 'Rp.' 
                        ));
                    }

                    $theArray['invoice_detail'] = $dataDetail;
                    $theArray['with_detail'] = "1";
                }

                $arrayData[] = $theArray;

                $totalInvoice += (floatval($totalDetail) - $totalDeductionBefore);
            }
        }

        $prj_kode = $detaildata['prj_kode'];
        $rateidr = $detaildata['rateidr'];

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

        if ($hasil) {
            $ldapdir = new Default_Models_Ldap();
            foreach ($hasil as $key => $val) {
                $hasil[$key]['display_name'] = QDC_User_Ldap::factory(array("uid" => $val['uid_manager']))->getName();
                $hasil[$key]['role_name'] = $val['display_name'];
            }
        }

        $signname = $hasil[0]['display_name'];
        $signtitle = $hasil[0]['role_name'];

        $date = date('Y-m-d H:i:s');

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

        $valutaInvoice = $detaildata['val_kode'];
        if ($detaildata['val_kode'] == 'IDR')
            $valuta = 'Rp.';
        else
            $valuta = 'US$ ';
        $terbilang = $this->utility->convert_number($gtotal);

        $tgl_local = QDC_Common_Date::factory()->formatDateUsingCustomLanguage("d F Y", $detaildata['tgl']);

        $params = array(
            'noTrans2' => $noTrans . $detaildata['suffix_trano'],
            'noTrans' => $noTrans,
            'gtotal' => $gtotal,
//            'invTotal' => $invTotal,
            'terbilang' => ($notation) ? $terbilang . " Indonesia Rupiah and 0 Cents Only" : null,
            'cus_nama' => $cus_nama,
            'logoPath' => $this->reportPath . '/images/.jpg',
            'basePath' => $this->reportPath,
            'cus_alamat' => $cus_alamat,
            'signname' => $signname,
            'signtitle' => $signtitle,
            'paymentterm' => $detaildata['paymentterm'],
            'val_kode' => $valuta,
            'signature' => $signature,
            'bnk_noreknama' => $detaildata['bnk_noreknama'],
            'bnk_nama' => $detaildata['bnk_nama'],
            'bnk_alamat' => $detaildata['bnk_alamat'],
            'bnk_cabang' => $detaildata['bnk_cabang'],
            'bnk_kota' => $detaildata['bnk_kota'],
            'bnk_norek' => $detaildata['bnk_norek'],
            'bank_curr' => ($valutaInvoice == 'IDR') ? "IDR A/C No : " : "USD A/C No : ",
            'tgl_invoice' => $tgl_local,
            'tgl_invoice_header' => date('d/m/Y', strtotime($detaildata['tgl'])),
        );

        $data_customer = $customer->fetchRow("cus_kode = '$cus_kode'");

        $file_invoice = 'invoice.jrxml';

        if (!$isUSD)
            $file_faktur_pajak = 'faktur_pajak.jrxml';
        else
            $file_faktur_pajak = 'faktur_pajak_usd.jrxml';

        $dataPrint[] = array(
            "data" => $arrayData,
            "arrayParams" => $params,
            "fileName" => $file_invoice,
            "dataSource" => 'NoDataSource',
            "useSubReport" => true,
            "subReport" => array(
                array(
                    "fileName" => 'invoice_deduction.jrxml',
                )
            )
        );

        //nomorFakturSection
        if ($detaildata['tax_number']) {
            if ($noFaktur == $detaildata['tax_number'])
                $noFaktur = $detaildata['tax_number'];
            else {
                $update = array(
                    "tax_number" => $noFaktur
                );
                $header->update($update, $where);
            }
        } else {
            $update = array(
                "tax_number" => $noFaktur
            );
            $header->update($update, $where);
        }

//        $theArray['with_detail'] = "0";
//                if ($val['invoice_detail'] != null && $val['invoice_detail'] != 'Array')
//                    $dataDetailInvoice = Zend_Json::decode($val['invoice_detail']);
////                
//                $dataDetail = new Java("java.util.ArrayList");
//                if ($dataDetailInvoice) {
//                    foreach ($dataDetailInvoice as $key => $v) {
//
//                        $dataDetail->add(array(
//                            'invoice_detail_desc' => $v['ket'],
//                            'invoice_detail_total' => number_format($v['total'], 2),
//                            'val_kode' => $valuta
//                        ));
//                    }
//
//                    $theArray['invoice_detail'] = $dataDetail;
//                    $theArray['with_detail'] = "1";
//                }
        //
        //Loop for Faktur Pajak
        $lembar = (int) $lembar;
        for ($i = 1; $i <= $lembar; $i++) {

            $totalInvoiceFaktur = $totalInvoice;
            $totalPPNFaktur = $totalPPN;
            $valutaFaktur = $valuta;
            if ($valutaInvoice == 'USD') {
                $valutaFakturIDR = 'Rp.';
                $valutaFaktur = 'US$';
                $totalInvoiceFakturIDR = $rateidr * $totalInvoice;
                $totalPPNFakturIDR = $rateidr * $totalPPN;
            }

            $t = '';
            $ket = '';
            switch ($i) {
                case 1:
                    $t = 'Untuk Pembeli';
                    $ket = 'Masukan';
                    break;
                case 2:
                    $t = 'Untuk Penjual';
                    $ket = 'Keluaran';
                    break;
                case 3:
                    $t = 'Untuk Arsip';
                    break;
            }
            $lembarText = "Lembar ke $i : $t ";

            $params_pajak = array(
                'customer_name' => "{$data_customer['cus_nama']}",
                'customer_tax_add' => strip_tags($data_customer['alamatpajak']),
                'customer_npwp' => $data_customer['npwp'],
                'valuta' => $valutaFaktur,
                'total_invoice' => $valutaFaktur . '     ' . number_format($totalInvoiceFaktur, 2),
                'total_ppn' => $valutaFaktur . '       ' . number_format($totalPPNFaktur, 2),
                'signname' => $signname,
                'signtitle' => $signtitle,
                'rateidr' => number_format($rateidr, 2),
                'no_faktur_pajak' => $noFaktur,
                'lembar' => $lembarText,
                'keterangan' => $ket,
                'tanggal' => $tgl_local,
                'tgl_invoice' => $tgl_local,
                'basePath' => $this->reportPath,
            );

            if ($isUSD) {
                $params_pajak['total_invoice_idr'] = $valutaFakturIDR . '     ' . number_format($totalInvoiceFakturIDR, 2);
                $params_pajak['total_ppn_idr'] = $valutaFakturIDR . '     ' . number_format($totalPPNFakturIDR, 2);
            }

            foreach ($arrayData as $k => $v) {
                if (strpos($v['nama_brg'], $noTrans) > 0)
                    break;
                $arrayData[$k]['nama_brg'] .= "\nNomor Invoice : " . $noTrans . $detaildata['suffix_trano'];
                
                $arrayData[$k]['with_detail'] = "0";
                if ($v['invoice_detail'] != null) {
                    $arrayData[$k]['with_detail'] = "1";
                    $arrayData[$k]['invoice_detail'] = $v['invoice_detail'];
                }
            }
//   
            if (!$isUSD)
                $file_detail = 'invoice_detail_pajak.jrxml';
            else
                $file_detail = 'invoice_detail_pajakusd.jrxml';


            $dataPrint[] = array(
                "data" => $arrayData,
                "arrayParams" => $params_pajak,
                "fileName" => $file_faktur_pajak,
                "dataSource" => 'NoDataSource',
                "useSubReport" => true,
                "subReport" => array(
                    array(
                        "fileName" => $file_detail,
                    )
                )
            );
        }
        
        QDC_Jasper_Report::factory(
                array(
                    "reportType" => $type,
                    "arrayMultiData" => $dataPrint,
                    "outputName" => 'Invoice ' . $noTrans . $detaildata['suffix_trano']
                )
        )->generateCombined();
    }

    public function paymentvoucherreportAction() {
        $this->_helper->viewRenderer->setNoRender();
        $voc_trano = $this->getRequest()->getParam('trano');
        $type_doc = $this->getRequest()->getParam('type_doc');
        $prepared = $this->session->userName;

        $data = new Java("java.util.ArrayList");

        $ldap = new Default_Models_Ldap();

        $voucher = $this->voucher->fetchAll("trano = '$voc_trano' AND deleted=0");
        $rpi = $this->rpih->fetchRow("trano = '{$voucher[0]['ref_number']}' AND deleted=0");
        $desc = $rpi['invoice_no'];
        $desc_text = 'Invoice No: ' . $desc;
        $tgl = date('d/m/Y', strtotime($voucher[0]['tgl']));
        $itungData = 0;
        $voc_type = $voucher[0]['type'];

        $arrayTextbox = array();

        $arrayPPN = array();
        $arrayWHT = array();
        $arrayData = array();
        $withWHT = false;
        $tranoWHT = '';
        $totalWHT = 0;
        $combined = false;

        $pic = QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName();
        $printed = "Printed by " . $pic . " @ " . date("d M Y H:i");

        if ($voucher) {
            if ($voucher[0]['item_type'] == 'RPI') {
                $tmp = array();

                foreach ($voucher as $key => $val) {
                    $itungData++;

                    $statuspulsa = $val['statuspulsa'];

                    $tmp['prj_kode'][] = $val['prj_kode'];
                    $tmp['ref-number'][] = $val['ref_number'];
                    $tmp['requester'][] = $val['requester'];
                    $tmp['sit_kode'][] = $val['sit_kode'];
                    $ppn = $val['statusppn'];
                    $ht = $val['holding_tax_status'];

                    //Cek kalo ada ppn
                    if ($val['trano_ppn'] != '') {
                        $arrayPPN[] = $val['trano_ppn'];
                        $combined = true;
                    }

                    if ($ppn == 'Y') {
                        $ppn = 'Add VAT 10%';
                        $ppnvalue = number_format(floatval($val['valueppn']), 2);
                        $totalppnvalue += $ppnvalue;
//                        $ppnvalue = floatval($val['valueppn']);
                    } else {
                        $ppn = null;
                        $ppnvalue = "";
//                        $ppnvalue = 0;
                    }

                    if ($ht == 'Y') {
                        $ht_text = $val['holding_tax_text'];
                        $ht_persen = floatval($val['holding_tax'] * 100);

                        if ($ht_persen == 0) {
                            $ht_persen = null;
                        } else {
                            $ht_persen = $ht_persen . '%';
                        }

                        $ht_value = '(' . number_format(floatval($val['holding_tax_val']), 2) . ')';
                        $total_ht_value += floatval($val['holding_tax_val']);
//                        $ht_value = floatval($val['holding_tax_val']);

                        $tranoWHT = $val['trano_wht'];

                        // Cek Trano WHT sudah ada apa blum... BPV WHT baru ada since 27 Jan 2014, sebelum itu tidak ada trano WHT di BPV lama
                        if (!$this->voucher->cekTranoWht($tranoWHT)) {
                            $tranoWHT = $this->voucher->createNewWHT($voc_trano);
                        }

                        $arrayWHT[$tranoWHT] = $tranoWHT;
                        $withWHT = true;
                        $combined = true;
                    } else {
                        $ht_text = null;
                        $ht_persen = '';
                        $ht_value = '';
//                        $ht_value = 0;
                    }

                    $gtotal += floatval($val['total']);

                    $valuta = $val['valuta'];

                    if ($valuta == 'IDR') {
                        $valuta = 'Rp';
                    } else if ($valuta == 'USD') {
                        $valuta = '$';
                    }

                    $doc_trano = $val['ref_number'];
                    $deduction_text = '';
                    $deduction = floatval($val['deduction']);
                    $total_deduction += $deduction;

                    if ($deduction != 0) {
                        $deduction = '(' . number_format($deduction, 2) . ')';
//                        $deduction = $deduction;
                        $deduction_text = 'Deduction';
                    } else {
                        $deduction = '';
//                        $deduction = 0;
                        $deduction_text = null;
                    }

                    $grossup = $val['grossup_status'];
                    $invoice = floatval($val['total_bayar']);
                    $grossup_value = floatval($val['holding_tax_val']);
                    if ($grossup == 'Y') {
                        $invoice = $invoice + $grossup_value;
                    }
                    $total_grossup_value += $grossup_value;
                    $total_invoice += $invoice;

                    $sqls = "SELECT uid,date,signature,approve FROM workflow_trans WHERE (approve = 200 OR approve = 400) AND item_id = '$doc_trano' GROUP BY item_id,workflow_id,uid ORDER BY date ASC";
                    $fetchs = $this->db->query($sqls);
                    $datas = $fetchs->fetchAll();


//                    if ($itungData <=4)
//                    {
                    $app = array();
                    foreach ($datas as $key2 => $val2) {
                        $uid = $val2['uid'];
//                        $acc = $ldap->getAccount($uid);
//                        $nama = $acc['displayname'][0];
                        $nama = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                        ;
                        $app[] = "<li>" . $nama . " " . date("d/M/Y", strtotime($val2['date'])) . "</li>";
                    }
                    $arrayTextbox[1] = "<b><u>$doc_trano</u></b><ul>" . implode("", $app) . "</ul>";
//                    }
//                    $arrayData[] = array(
//                        'prj_kode' => $prj_kode,
//                        'coa' => $val['coa_kode'],
//                        'description' => $val['ketin'],
//                        'amount' => $invoice,
//                        'ppn' => $ppn,
//                        'ppnvalue' => $ppnvalue,
//                        'ht_text' => $ht_text,
//                        'ht_persen' => $ht_persen,
//                        'ht_value' => $ht_value,
//                        'deduction' => $deduction,
//                        'deduction_text' =>$deduction_text,
//                        'total' => floatval($val['total'])
//                    );

                    $grand_total += floatval($val['total']);

                    $tranoRPI = $val['ref_number'];
                }

                if ($total_deduction != 0) {
//                    $total_deduction = '(' . number_format($total_deduction, 2) . ')';
                    $deduction_text = 'Deduction';
                } else {
                    $total_deduction = '';
                    $deduction_text = null;
                }

                if ($total_ht_value != 0) {
                    $total_ht_value = '(' . number_format($total_ht_value, 2) . ')';
                    $ht_persen = '';
                    $ht_text = 'Less Witholding Tax';
                } else {
                    $ht_text = null;
                    $ht_persen = '';
                    $total_ht_value = '';
                }

                $select = $this->db->select()
                        ->from(array($this->rpih->__name()))
                        ->where("trano=?", $tranoRPI)
                        ->where("deleted=?",0);
                $cek = $this->db->fetchRow($select);
                if ($cek) {
                    if ($cek['with_materai'] == 'Y' && $cek['materai'] > 0) {
                        $materai = $cek['materai'];
                        $grand_total = $grand_total + $materai;
                        $materai_text = 'Stamp Duty';
                    } else {
                        $materai = '';
                        $materai_text = null;
                    }
                }


                $arrayData[] = array(
                    'prj_kode' => $prj_kode,
                    'coa' => '',
                    'description' => $desc_text,
                    'amount' => floatval($total_invoice),
                    'ppn' => $ppn,
                    'ppnvalue' => floatval($totalppnvalue),
                    'ht_text' => $ht_text,
                    'ht_persen' => $ht_persen,
                    'ht_value' => $total_ht_value,
                    'deduction' => floatval($total_deduction),
                    'deduction_text' => $deduction_text,
                    'stamp_duty' => floatval($materai),
                    'stamp_dutytext' => $materai_text,
                    'total' => $grand_total
                );

                if ($statuspulsa == '1') {
                    $prj_kode = implode('/', $tmp['prj_kode']);
                } else {
                    $prj_kode = $tmp['prj_kode'][0];
                }

                //            var_dump($prj_kode);die;
//                $ref_number = implode('/',$tmp['ref-number']);
//                $requester = implode('/',$tmp['requester']);
//                $sit_kode = implode('/',$tmp['sit_kode']);

                $ref_number = $val['ref_number'];
                $requester = $val['requester'];
                $sit_kode = $val['sit_kode'];
            } else {

                $tmp = array();

                foreach ($voucher as $key => $val) {
                    $itungData++;

                    $statuspulsa = $val['statuspulsa'];


                    $tmp['prj_kode'][] = $val['prj_kode'];
                    $tmp['ref-number'][] = $val['ref_number'];
                    $tmp['requester'][] = $val['requester'];
                    $tmp['sit_kode'][] = $val['sit_kode'];
                    $ppn = $val['statusppn'];
                    $ht = $val['holding_tax_status'];

                    //Cek kalo ada ppn
                    if ($val['trano_ppn'] != '') {
                        $arrayPPN[] = $val['trano_ppn'];
                        $combined = true;
                    }

                    if ($ppn == 'Y') {
                        $ppn = 'Add VAT 10%';
                        $ppnvalue = number_format(floatval($val['valueppn']), 2);
                    } else {
                        $ppn = null;
                        $ppnvalue = "";
                    }

                    if ($ht == 'Y') {
                        $ht_text = $val['holding_tax_text'];
                        $ht_persen = floatval($val['holding_tax'] * 100);

                        if ($ht_persen == 0) {
                            $ht_persen = null;
                        } else {
                            $ht_persen = $ht_persen . '%';
                        }

                        $ht_value = '(' . number_format(floatval($val['holding_tax_val']), 2) . ')';
                    } else {
                        $ht_text = null;
                        $ht_persen = '';
                        $ht_value = '';
                    }

                    $gtotal += floatval($val['total']);

                    $valuta = $val['valuta'];

                    if ($valuta == 'IDR') {
                        $valuta = 'Rp';
                    } else if ($valuta == 'USD') {
                        $valuta = '$';
                    }

                    $doc_trano = $val['ref_number'];
                    $deduction_text = '';
                    $deduction = floatval($val['deduction']);

                    if ($deduction != 0) {
                        $deduction = '(' . number_format($deduction, 2) . ')';
                        $deduction_text = 'Deduction';
                    } else {
                        $deduction = '';
                        $deduction_text = null;
                    }

                    $grossup = $val['grossup_status'];
                    $invoice = floatval($val['total_bayar']);
                    $grossup_value = floatval($val['holding_tax_val']);

                    if ($grossup == 'Y') {
                        $invoice = $invoice + $grossup_value;
                    }

                    if ($val['item_type'] == 'BRFP') {
                        $brfp = new Procurement_Models_BusinessTripPayment();
                        $data_brfp = $brfp->fetchRow("trano = '$doc_trano'");

                        if ($data_brfp->toArray()) {
                            $data_brfp = $data_brfp->toArray();
                            if ($data_brfp['sequence'] == '1')
                                $doc_trano = $data_brfp['trano_ref'];
                        }
                    }

                    $sqls = "SELECT uid,date,signature,approve FROM workflow_trans WHERE (approve = 200 OR approve = 400) AND item_id = '$doc_trano' GROUP BY item_id,workflow_id,uid ORDER BY date ASC";
                    $fetchs = $this->db->query($sqls);
                    $datas = $fetchs->fetchAll();


//                    if ($itungData <=4)
//                    {
                    $app = array();
                    foreach ($datas as $key2 => $val2) {
                        $uid = $val2['uid'];
//                        $acc = $ldap->getAccount($uid);
//                        $nama = $acc['displayname'][0];
                        $nama = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                        ;
                        $app[] = "<li>" . $nama . " " . date("d/M/Y", strtotime($val2['date'])) . "</li>";
                    }
                    $arrayTextbox[$itungData] = "<b><u>$doc_trano</u></b><ul>" . implode("", $app) . "</ul>";
//                    }

                    $arrayData[] = array(
                        'prj_kode' => $prj_kode,
                        'coa' => $val['coa_kode'],
                        'description' => $val['ketin'],
                        'amount' => floatval($invoice),
                        'ppn' => $ppn,
                        'ppnvalue' => floatval($ppnvalue),
                        'ht_text' => $ht_text,
                        'ht_persen' => $ht_persen,
                        'ht_value' => floatval($ht_value),
                        'deduction' => floatval($deduction),
                        'deduction_text' => $deduction_text,
                        'total' => floatval($val['total'])
                    );
                }

                if ($statuspulsa == '1') {
                    $prj_kode = implode('/', $tmp['prj_kode']);
                } else {
                    $prj_kode = $tmp['prj_kode'][0];
                }

                //            var_dump($prj_kode);die;

                $ref_number = implode('/', $tmp['ref-number']);
                $requester = implode('/', $tmp['requester']);
                $sit_kode = implode('/', $tmp['sit_kode']);
            }
        }

//        $username = $ldap->getAccount($prepared);
//        $namauser = $username['displayname'][0];
        $namauser = QDC_User_Ldap::factory(array("uid" => $prepared))->getName();
        ;


        $l = new Default_Models_LogPrint();

        $l->insertLog(array(
            "trano" => $voc_trano,
            "type" => $type_doc
        ));

        $print_count = $l->getNumberPrinted($voc_trano, $type_doc);

        $params = array(
            'trano' => $voc_trano,
            'ref-number' => $ref_number,
            'requester' => $requester,
            'sit_kode' => $sit_kode,
            'prj_kode' => $prj_kode,
            'gtotal' => number_format($gtotal, 2),
            'valuta' => $valuta,
            'teksbox1' => $arrayTextbox[1],
            'teksbox2' => $arrayTextbox[2],
            'teksbox3' => $arrayTextbox[3],
            'teksbox4' => $arrayTextbox[4],
            'prepared' => $namauser,
            'basePath' => $this->reportPath . '/images/imd.jpg',
            'tgl' => $tgl,
            'printed' => $printed,
            'print_count' => $print_count++
        );

        if ($voc_type == 'pettycash') {
            $file_bankvoucer = 'pettycash_payment_voucher_report.jrxml';
        } else {
//            $file_bankvoucer = 'pettycash_payment_voucher_report.jrxml';
            $file_bankvoucer = 'bank_payment_voucher_report.jrxml';
        }

        if ($combined) {
            $dataPrint[] = array(
                "data" => $arrayData,
                "arrayParams" => $params,
                "fileName" => $file_bankvoucer,
                "dataSource" => 'NoDataSource'
            );
            if (count($arrayPPN) > 0) {
                if ($voucher[0]['item_type'] == 'RPI') {
                    $dataPPN = $this->getDataBPVPPNCombine($arrayPPN);
                } else {
                    $dataPPN = $this->getDataBPVPPN($arrayPPN);
                }
                foreach ($dataPPN as $k => $v) {
                    $dataPPN[$k]['arrayParams']['print_count'] = $print_count - 1;
                }
                foreach ($dataPPN as $k => $v) {
                    $p = $v['arrayParams'];
                    $p['printed'] = $printed;
                    $dataPrint[] = array(
                        "data" => $v['data'],
                        "arrayParams" => $p,
                        "fileName" => $file_bankvoucer,
                        "dataSource" => 'NoDataSource'
                    );
                }
            }

            if ($withWHT) {
                foreach ($arrayWHT as $k => $v) {
                    $tranoWHT = $v;
                }
                $dataWHT = $this->getDataWHTCombine($tranoWHT);
                foreach ($dataWHT as $k => $v) {
                    $dataWHT[$k]['arrayParams']['print_count'] = $print_count - 1;
                }
                foreach ($dataWHT as $k => $v) {
                    $p = $v['arrayParams'];
                    $p['printed'] = $printed;
                    $dataPrint[] = array(
                        "data" => $v['data'],
                        "arrayParams" => $p,
                        "fileName" => $file_bankvoucer,
                        "dataSource" => 'NoDataSource'
                    );
                }
            }

            QDC_Jasper_Report::factory(
                    array(
                        "reportType" => $type_doc,
                        "arrayMultiData" => $dataPrint,
                        "outputName" => 'Bank Payment Voucher'
                    )
            )->generateCombined();
        } else {
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

    public function getDataBPVPPN($arrayPPN = '') {
        $arrayData = array();
        $prepared = $this->session->userName;
        $ldap = new Default_Models_Ldap();
        $jum = 0;

        foreach ($arrayPPN as $k => $v) {

            $voucher = $this->voucher->fetchRow("trano = '$v'");

            if ($voucher) {
                $val = $voucher->toArray();
                $tgl = date('d/m/Y', strtotime($val['tgl']));
                $ppn = 'Add VAT 10%';
                $ppnvalue = number_format(floatval($val['valueppn']), 2);
                $gtotal = floatval($val['total']);
                $voc_trano = $val['trano'];
                $valuta = $val['valuta'];

                if ($valuta == 'IDR') {
                    $valuta = 'Rp';
                } else if ($valuta == 'USD') {
                    $valuta = '$';
                }

                $doc_trano = $val['ref_number'];
                $invoice = floatval($val['total_bayar']);
                $sqls = "SELECT uid,date,signature,approve FROM workflow_trans WHERE (approve = 200 OR approve = 400) AND item_id = '$doc_trano' GROUP BY item_id,workflow_id,uid ORDER BY date ASC";
                $fetchs = $this->db->query($sqls);
                $datas = $fetchs->fetchAll();
                $app = array();
                foreach ($datas as $key2 => $val2) {
                    $uid = $val2['uid'];
//                    $acc = $ldap->getAccount($uid);
//                    $nama = $acc['displayname'][0];
                    $nama = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                    ;
                    $app[] = "<li>" . $nama . " " . date("d/M/Y", strtotime($val2['date'])) . "</li>";
                }
                $arrayTextbox[] = "<b><u>$doc_trano</u></b><ul>" . implode("", $app) . "</ul>";


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



//                $username = $ldap->getAccount($prepared);
//                $namauser = $username['displayname'][0];
                $namauser = QDC_User_Ldap::factory(array("uid" => $prepared))->getName();

                $arrayData[$jum]['arrayParams'] = array(
                    'trano' => $voc_trano,
                    'ref-number' => $ref_number,
                    'requester' => $requester,
                    'sit_kode' => $sit_kode,
                    'prj_kode' => $prj_kode,
                    'gtotal' => number_format($gtotal, 2),
                    'valuta' => $valuta,
                    'teksbox1' => $arrayTextbox[0],
                    'prepared' => $namauser,
                    'basePath' => $this->reportPath . '/images/imd.jpg',
                    'tgl' => $tgl
                );
                $jum++;
            }
        }
        return $arrayData;
    }

    public function getDataBPVPPNCombine($arrayPPN = array()) {
        $arrayData = array();
        $prepared = $this->session->userName;
        $ldap = new Default_Models_Ldap();
        $jum = 0;



        $voucher = $this->voucher->fetchRow("trano = '{$arrayPPN[0]}'");

        if ($voucher) {
            $voucherData = $voucher->toArray();
        }

        $tgl = date('d/m/Y', strtotime($voucherData['tgl']));
        $ppn = 'Add VAT 10%';
        $arrayTrano = array();
        $totalVoucer = 0;
        $grandTotal = 0;

        $doc_trano = $voucherData['ref_number'];
        $sqls = "SELECT uid,date,signature,approve FROM workflow_trans WHERE (approve = 200 OR approve = 400) AND item_id = '$doc_trano' GROUP BY item_id,workflow_id,uid ORDER BY date ASC";
        $fetchs = $this->db->query($sqls);
        $datas = $fetchs->fetchAll();
        $app = array();
        foreach ($datas as $key2 => $val2) {
            $uid = $val2['uid'];
//            $acc = $ldap->getAccount($uid);
//            $nama = $acc['displayname'][0];
            $nama = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
            ;
            $app[] = "<li>" . $nama . " " . date("d/M/Y", strtotime($val2['date'])) . "</li>";
        }
        $arrayTextbox[] = "<b><u>$doc_trano</u></b><ul>" . implode("", $app) . "</ul>";
        $valuta = $voucherData['valuta'];

        if ($valuta == 'IDR') {
            $valuta = 'Rp';
        } else if ($valuta == 'USD') {
            $valuta = '$';
        }

        foreach ($arrayPPN as $k => $v) {

            $voucher = $this->voucher->fetchRow("trano = '$v'");

            if ($voucher) {
                $val = $voucher->toArray();
                $tgl = date('d/m/Y', strtotime($val['tgl']));

                $ppnvalue = number_format(floatval($val['valueppn']), 2);
                $gtotal = floatval($val['total']);
                $voc_trano = $val['trano'];
                $arrayTrano[] = $voc_trano;

                $invoice = floatval($val['total_bayar']);

                $totalVoucer += $invoice;
                $grandTotal += $gtotal;
            }
        }

        $arrayData[$jum]['data'][] = array(
            'prj_kode' => $voucherData['prj_kode'],
            'coa' => $voucherData['coa_kode'],
            'description' => $ppn,
            'amount' => $totalVoucer,
            'total' => $grandTotal
        );

        $ref_number = $voucherData['ref_number'];
        $requester = $voucherData['requester'];
        $sit_kode = $voucherData['sit_kode'];
        $prj_kode = $voucherData['prj_kode'];

//        $username = $ldap->getAccount($prepared);
//        $namauser = $username['displayname'][0];
        $namauser = QDC_User_Ldap::factory(array("uid" => $prepared))->getName();
        ;

        $arrayData[$jum]['arrayParams'] = array(
            'trano' => implode(",", $arrayTrano),
            'ref-number' => $ref_number,
            'requester' => $requester,
            'sit_kode' => $sit_kode,
            'prj_kode' => $prj_kode,
            'gtotal' => number_format($grandTotal, 2),
            'valuta' => $valuta,
            'teksbox1' => $arrayTextbox[0],
            'prepared' => $namauser,
            'basePath' => $this->reportPath . '/images/imd.jpg',
            'tgl' => $tgl
        );

        return $arrayData;
    }

    public function getDataWHTCombine($trano = '') {
        $arrayData = array();
        $prepared = $this->session->userName;
        $ldap = new Default_Models_Ldap();
        $jum = 0;

        $voucher = $this->voucher->fetchRow("trano = '{$trano}'");

        if ($voucher) {
            $voucherData = $voucher->toArray();
        }

        $tgl = date('d/m/Y', strtotime($voucherData['tgl']));
        $ppn = 'With Holding Tax';
        $arrayTrano = array();
        $totalVoucer = 0;
        $grandTotal = 0;

        $doc_trano = $voucherData['ref_number'];
        $sqls = "SELECT uid,date,signature,approve FROM workflow_trans WHERE (approve = 200 OR approve = 400) AND item_id = '$doc_trano' GROUP BY item_id,workflow_id,uid ORDER BY date ASC";
        $fetchs = $this->db->query($sqls);
        $datas = $fetchs->fetchAll();
        $app = array();
        foreach ($datas as $key2 => $val2) {
            $uid = $val2['uid'];
//            $acc = $ldap->getAccount($uid);
//            $nama = $acc['displayname'][0];
            $nama = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
            ;
            $app[] = "<li>" . $nama . " " . date("d/M/Y", strtotime($val2['date'])) . "</li>";
        }
        $arrayTextbox[] = "<b><u>$doc_trano</u></b><ul>" . implode("", $app) . "</ul>";
        $valuta = $voucherData['valuta'];

        if ($valuta == 'IDR') {
            $valuta = 'Rp';
        } else if ($valuta == 'USD') {
            $valuta = '$';
        }

        $arrayData[$jum]['data'][] = array(
            'prj_kode' => $voucherData['prj_kode'],
            'coa' => $voucherData['coa_kode'],
            'description' => $ppn,
            'amount' => floatval($voucherData['total_bayar']),
            'total' => floatval($voucherData['total_bayar'])
        );

        $ref_number = $voucherData['ref_number'];
        $requester = $voucherData['requester'];
        $sit_kode = $voucherData['sit_kode'];
        $prj_kode = $voucherData['prj_kode'];

//        $username = $ldap->getAccount($prepared);
//        $namauser = $username['displayname'][0];
        $namauser = QDC_User_Ldap::factory(array("uid" => $prepared))->getName();
        ;

        $arrayData[$jum]['arrayParams'] = array(
            'trano' => $trano,
            'ref-number' => $ref_number,
            'requester' => $requester,
            'sit_kode' => $sit_kode,
            'prj_kode' => $prj_kode,
            'gtotal' => number_format($voucherData['total_bayar'], 2),
            'valuta' => $valuta,
            'teksbox1' => $arrayTextbox[0],
            'prepared' => $namauser,
            'basePath' => $this->reportPath . '/images/imd.jpg',
            'tgl' => $tgl
        );

        return $arrayData;
    }

    public function bankchargesAction() {
        $this->_helper->viewRenderer->setNoRender();

        $trano = str_replace('_', "/", $this->_getParam("trano"));
        $user = $this->view->uid = $this->session->userName;

        $select = $this->db->select()
                ->where("trano  = '$trano'")
                ->order(array("id ASC"));

        $select = $select->from(array($this->bankout->__name()));
        $data = $this->db->fetchAll($select);

        $datas = new Java("java.util.ArrayList");

        $total = 0;
        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
        }

        $datas->add(array(
            "trano" => $data[0]['trano'],
            "ref_number" => $data[0]['ref_number'],
            "tgl" => date('Y-m-d', strtotime($data[0]['tgl'])),
            "coa_kode" => $data[0]['coa_kode'],
            "coa_nama" => $data[0]['coa_nama'],
            "valuta" => $data[0]['val_kode'],
            "debit" => $data[0]['debit'],
            "credit" => $data[0]['credit'],
            "trans" => $data[0]['trans']
        ));

        $this->_helper->viewRenderer->setNoRender();

        $params = array(
            'username' => $user,
            'type' => $type,
            'total' => number_format($total, 2)
        );

        $outputPath = $this->getReportNoDataSource("pdf", $datas, $params, 'bank_charges_report.jrxml', 'Bank Charges Report');
    }

    public function bankReceiveMoneyAction() {
        $this->_helper->viewRenderer->setNoRender();

        $trano = str_replace('_', "/", $this->_getParam("trano"));
        $user = QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName();

        $b = new Finance_Models_BankReceiveMoney();

        $select = $this->db->select()
                ->where("trano  = ?", $trano)
                ->order(array("id ASC"));

        $select = $select->from(array($b->__name()));
        $data = $this->db->fetchAll($select);

        $datas = new Java("java.util.ArrayList");

        $total = 0;
        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
        }

        $tgl = date('d M Y', strtotime($data[0]['tgl']));
        $val = ($data[0]['val_kode'] == 'IDR') ? 'Rp.' : '$';
        $dataSum = array(
            "trano" => $data[0]['trano'],
            "ref_number" => $data[0]['coa_nama'] . " " . $data[0]['ref_number'],
            "tgl" => $tgl,
            "coa_kode" => $data[0]['coa_kode'],
            "coa_nama" => $data[0]['coa_nama'],
            "valuta" => $val,
            "total" => number_format($total, 2)
        );

        $params = array(
            'username' => $user,
            "printed" => $user . " @ " . date("d M Y H:i"),
            'total' => number_format($total, 2),
            "trano" => $trano,
            "ref_number" => $data[0]['ref_number'],
            "tgl" => $tgl,
            "valuta" => $val,
            "signature" => $this->token->generateDocumentSignature()
        );

        //Page 1
        $dataPrint[] = array(
            "data" => array($dataSum),
            "arrayParams" => $params,
            "fileName" => "bank_receive_money.jrxml",
            "dataSource" => 'NoDataSource'
        );

        //Page 2

        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
            $data[$k]['trano'] = $v['ref_number'];
            $data[$k]['debit'] = floatval($v['debit']);
            $data[$k]['credit'] = floatval($v['credit']);
        }
        $signature = $this->_helper->getHelper('token')->generateDocumentSignature();

        $m = new Default_Models_MasterPt();
        $pt = $m->fetchRow()->toArray();
        $params = array(
            "nama" => $pt['nama'],
            "alamat1" => $pt['alamat1'],
            "alamat2" => $pt['alamat2'],
            "title" => "Bank Receive Money Journal",
            "sub_title" => $trano,
            "date" => date("d M Y"),
            "time" => date("H:i:s A"),
            "signature" => $signature
        );

        $dataPrint[] = array(
            "data" => $data,
            "arrayParams" => $params,
            "fileName" => "jurnal.jrxml",
            "dataSource" => 'NoDataSource'
        );

        QDC_Jasper_Report::factory(
                array(
                    "reportType" => "pdf",
                    "arrayMultiData" => $dataPrint,
                    "outputName" => 'Bank Receive Money ' . $trano
                )
        )->generateCombined();

//        $outputPath = $this->getReportNoDataSource("pdf", $datas, $params, 'bank_receive_money.jrxml', 'Bank Receive Money ' . $trano);
    }

    public function pettycashinAction() {
        $this->_helper->viewRenderer->setNoRender();

        $trano = str_replace('_', "/", $this->_getParam("trano"));
        $user = QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName();

        $b = new Finance_Models_PettyCashIn();

        $select = $this->db->select()
                ->where("trano  = ?", $trano)
                ->order(array("id ASC"));

        $select = $select->from(array($b->__name()));
        $data = $this->db->fetchAll($select);

        $datas = new Java("java.util.ArrayList");

        $total = 0;
        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
        }

        $tgl = date('d M Y', strtotime($data[0]['tgl']));
        $val = ($data[0]['val_kode'] == 'IDR') ? 'Rp.' : '$';
        $dataSum = array(
            "trano" => $data[0]['trano'],
            "ref_number" => $data[0]['coa_nama'] . " " . $data[0]['ref_number'],
            "tgl" => $tgl,
            "coa_kode" => $data[0]['coa_kode'],
            "coa_nama" => $data[0]['coa_nama'],
            "valuta" => $val,
            "total" => number_format($total, 2)
        );

        $params = array(
            'username' => $user,
            "printed" => $user . " @ " . date("d M Y H:i"),
            'total' => number_format($total, 2),
            "trano" => $trano,
            "ref_number" => $data[0]['ref_number'],
            "tgl" => $tgl,
            "valuta" => $val,
            "signature" => $this->token->generateDocumentSignature()
        );

        //Page 1
        $dataPrint[] = array(
            "data" => array($dataSum),
            "arrayParams" => $params,
            "fileName" => "bank_receive_money.jrxml",
            "dataSource" => 'NoDataSource'
        );

        //Page 2

        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
            $data[$k]['trano'] = $v['ref_number'];
            $data[$k]['debit'] = floatval($v['debit']);
            $data[$k]['credit'] = floatval($v['credit']);
        }
        $signature = $this->_helper->getHelper('token')->generateDocumentSignature();

        $m = new Default_Models_MasterPt();
        $pt = $m->fetchRow()->toArray();
        $params = array(
            "nama" => $pt['nama'],
            "alamat1" => $pt['alamat1'],
            "alamat2" => $pt['alamat2'],
            "title" => "Petty Cash In Journal",
            "sub_title" => $trano,
            "date" => date("d M Y"),
            "time" => date("H:i:s A"),
            "signature" => $signature
        );

        $dataPrint[] = array(
            "data" => $data,
            "arrayParams" => $params,
            "fileName" => "jurnal.jrxml",
            "dataSource" => 'NoDataSource'
        );

        QDC_Jasper_Report::factory(
                array(
                    "reportType" => "pdf",
                    "arrayMultiData" => $dataPrint,
                    "outputName" => 'Petty Cash In ' . $trano
                )
        )->generateCombined();

//        $outputPath = $this->getReportNoDataSource("pdf", $datas, $params, 'bank_receive_money.jrxml', 'Bank Receive Money ' . $trano);
    }

    public function pettycashoutAction() {
        $this->_helper->viewRenderer->setNoRender();

        $trano = str_replace('_', "/", $this->_getParam("trano"));
        $user = QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName();

        $b = new Finance_Models_PettyCashOut();

        $select = $this->db->select()
                ->where("trano  = ?", $trano)
                ->order(array("id ASC"));

        $select = $select->from(array($b->__name()));
        $data = $this->db->fetchAll($select);

        $datas = new Java("java.util.ArrayList");

        $total = 0;
        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
        }

        $tgl = date('d M Y', strtotime($data[0]['tgl']));
        $val = ($data[0]['val_kode'] == 'IDR') ? 'Rp.' : '$';
        $dataSum = array(
            "trano" => $data[0]['trano'],
            "ref_number" => $data[0]['coa_nama'] . " " . $data[0]['ref_number'],
            "tgl" => $tgl,
            "coa_kode" => $data[0]['coa_kode'],
            "coa_nama" => $data[0]['coa_nama'],
            "valuta" => $val,
            "total" => number_format($total, 2)
        );

        $params = array(
            'username' => $user,
            "printed" => $user . " @ " . date("d M Y H:i"),
            'total' => number_format($total, 2),
            "trano" => $trano,
            "ref_number" => $data[0]['ref_number'],
            "tgl" => $tgl,
            "valuta" => $val,
            "signature" => $this->token->generateDocumentSignature()
        );

        //Page 1
        $dataPrint[] = array(
            "data" => array($dataSum),
            "arrayParams" => $params,
            "fileName" => "bank_receive_money.jrxml",
            "dataSource" => 'NoDataSource'
        );

        //Page 2

        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
            $data[$k]['trano'] = $v['ref_number'];
            $data[$k]['debit'] = floatval($v['debit']);
            $data[$k]['credit'] = floatval($v['credit']);
        }
        $signature = $this->_helper->getHelper('token')->generateDocumentSignature();

        $m = new Default_Models_MasterPt();
        $pt = $m->fetchRow()->toArray();
        $params = array(
            "nama" => $pt['nama'],
            "alamat1" => $pt['alamat1'],
            "alamat2" => $pt['alamat2'],
            "title" => "Petty Cash Out Journal",
            "sub_title" => $trano,
            "date" => date("d M Y"),
            "time" => date("H:i:s A"),
            "signature" => $signature
        );

        $dataPrint[] = array(
            "data" => $data,
            "arrayParams" => $params,
            "fileName" => "jurnal.jrxml",
            "dataSource" => 'NoDataSource'
        );

        QDC_Jasper_Report::factory(
                array(
                    "reportType" => "pdf",
                    "arrayMultiData" => $dataPrint,
                    "outputName" => 'Petty Cash Out ' . $trano
                )
        )->generateCombined();

//        $outputPath = $this->getReportNoDataSource("pdf", $datas, $params, 'bank_receive_money.jrxml', 'Bank Receive Money ' . $trano);
    }

    public function inventoryJournalAction() {
        $this->_helper->viewRenderer->setNoRender();

        $trano = str_replace("_", "/", $this->_getParam("trano"));
        $type = $this->_getParam("type");
        $user = QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName();

        if ($type == 'inventory_in')
            $b = new Finance_Models_AccountingInventoryIn();
        elseif ($type == 'inventory_out')
            $b = new Finance_Models_AccountingInventoryOut();
        elseif ($type == 'inventory_return')
            $b = new Finance_Models_AccountingInventoryReturn();

        $select = $this->db->select()
                ->where("trano  = ?", $trano)
                ->order(array("id ASC"));

        $select = $select->from(array($b->__name()));
        $data = $this->db->fetchAll($select);

        $datas = new Java("java.util.ArrayList");

        $total = 0;
        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
        }

        $tgl = date('d M Y', strtotime($data[0]['tgl']));
        $val = ($data[0]['val_kode'] == 'IDR') ? 'Rp.' : '$';

        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
            $data[$k]['debit'] = floatval($v['debit']);
            $data[$k]['credit'] = floatval($v['credit']);
        }
        $signature = $this->_helper->getHelper('token')->generateDocumentSignature();

        $m = new Default_Models_MasterPt();
        $pt = $m->fetchRow()->toArray();
        $params = array(
            "nama" => $pt['nama'],
            "alamat1" => $pt['alamat1'],
            "alamat2" => $pt['alamat2'],
            "title" => "Inventory Journal",
            "sub_title" => $trano,
            "date" => date("d M Y"),
            "time" => date("H:i:s A"),
            "signature" => $signature
        );

        QDC_Jasper_Report::factory(
                array(
                    "reportType" => 'pdf',
                    "arrayData" => $data,
                    "arrayParams" => $params,
                    "fileName" => 'jurnal.jrxml',
                    "outputName" => 'Inventory Journal ' . $trano,
                    "dataSource" => 'NoDataSource'
                )
        )->generate();
    }

    public function arJournalAction() {
        $this->_helper->viewRenderer->setNoRender();

        $trano = str_replace("_", "/", $this->_getParam("trano"));
        $type = $this->_getParam("type");
        $user = QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName();

        $b = new Finance_Models_AccountingCloseAR();

        $select = $this->db->select()
                ->where("trano  = ?", $trano)
                ->order(array("id ASC"));

        $select = $select->from(array($b->__name()));
        $data = $this->db->fetchAll($select);

        $datas = new Java("java.util.ArrayList");

        $total = 0;
        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
        }

        $tgl = date('d M Y', strtotime($data[0]['tgl']));
        $val = ($data[0]['val_kode'] == 'IDR') ? 'Rp.' : '$';

        foreach ($data as $k => $v) {
            $total = $total + $v['debit'];
            $data[$k]['debit'] = floatval($v['debit']);
            $data[$k]['credit'] = floatval($v['credit']);
        }
        $signature = $this->_helper->getHelper('token')->generateDocumentSignature();

        $m = new Default_Models_MasterPt();
        $pt = $m->fetchRow()->toArray();
        $params = array(
            "nama" => $pt['nama'],
            "alamat1" => $pt['alamat1'],
            "alamat2" => $pt['alamat2'],
            "title" => "AR Journal",
            "sub_title" => $trano,
            "date" => date("d M Y"),
            "time" => date("H:i:s A"),
            "signature" => $signature
        );

        QDC_Jasper_Report::factory(
                array(
                    "reportType" => 'pdf',
                    "arrayData" => $data,
                    "arrayParams" => $params,
                    "fileName" => 'jurnal.jrxml',
                    "outputName" => 'AR Journal ' . $trano,
                    "dataSource" => 'NoDataSource'
                )
        )->generate();
    }

}

?>
