<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 9/8/11
 * Time: 3:12 PM
 * To change this template use File | Settings | File Templates.
 */
class Finance_ReportController extends Zend_Controller_Action {

    private $arfh;
    private $arfd;
    private $brfh;
    private $brfd;
    private $rpih;
    private $rpid;
    private $payment_arf;
    private $payment_rpi;
    private $db;
    private $voucher;
    private $jurnalap;
    private $remh;
    private $remd;
    private $taxrecon;
    private $session;
    private $utility;
    private $FINANCE;
    private $DEFAULT;
    private $ADMIN;
    private $files;
    private $pmeald;
    private $pmealh;

    public function init() {
        $this->arfh = new Procurement_Models_Procurementarfh();
        $this->arfd = new Procurement_Models_Procurementarfd();
        $this->brfh = new Procurement_Models_BusinessTripHeader();
        $this->brfd = new Procurement_Models_BusinessTripDetail();
        $this->rpid = new Default_Models_RequestPaymentInvoice();
        $this->rpih = new Default_Models_RequestPaymentInvoiceH();
        $this->payment_arf = new Finance_Models_PaymentARF();
        $this->payment_rpi = new Finance_Models_PaymentRPI();
        $this->db = Zend_Registry::get('db');
        $this->voucher = new Finance_Models_BankPaymentVoucher();
        $this->jurnalap = new Finance_Models_AccountingCloseAP();
        $this->remh = new Default_Models_ReimbursH();
        $this->remd = new Default_Models_ReimbursD();
        $this->taxrecon = new Finance_Models_AccountingTaxRecon();
        $this->session = new Zend_Session_Namespace('login');
        $this->files = new Default_Models_Files();
        $this->pmeald = new Default_Models_PieceMeal();
        $this->pmealh = new Default_Models_PieceMealH();

        $this->utility = Zend_Controller_Action_HelperBroker::getStaticHelper('utility');

        $models = array(
            "BankPaymentVoucher",
            "BankPaymentVoucherD",
            "ArfAging",
            "AdjustingJournal",
            "AccountingJurnalBank",
            "AccountingJurnalSettlement",
            "AccountingCloseAP",
            "AccountingSaldoCoa",
            "AccountingSaldoRL",
            "PaymentRPI",
            "PaymentARF",
            "PaymentReimbursH",
            "PaymentReimbursD",
            "KasBank",
            "paymentNDreimbursH",
            "paymentNDreimbursD",
            "NDreimbursHeader",
            "NDreimbursDetail",
            "Invoice",
            "InvoiceDetail",
            "PaymentInvoice",
            "RequestInvoice",
            "MasterCoa",
            "BankSpendMoney",
            "BankReceiveMoney",
            "MasterKategoriAsset",
            "MasterPeriode",
            "LayoutProfitloss",
            "AccountingCancelInvoice"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);

        $models = array(
            "MasterUser",
            "MasterCustomer",
            "ProcurementArfh",
            "ProcurementPoh",
            "AdvanceSettlementFormD",
            "AdvanceSettlementFormCancel",
            "AdvanceSettlementForm",
            "AdvanceRequestFormD",
            "RequestPaymentInvoice",
            "RequestPaymentInvoiceH",
            "MasterSuplier",
            "ReimbursH",
            "Budget",
            "MasterProject",
            "MasterSite",
            "MasterPt",
            "ExchangeRate",
        );
        $this->DEFAULT = QDC_Model_Default::init($models);

        $models = array(
            "Workflowtrans"
        );
        $this->ADMIN = QDC_Model_Admin::init($models);
    }

    public function bankpaymentvoucherAction() {
        $trano = $this->getRequest()->getParam('trano');
        if ($trano) {
            $this->view->trano = $trano;
        }
    }

    public function getpaymentarfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else if ($textsearch != null && $option == 1) {
            $search = "trano like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 2) {
            $search = "prj_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 3) {
            $search = "prj_nama like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 4) {
            $search = "sit_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 5) {
            $search = "sit_nama like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 6) {
            $search = "doc_trano like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->payment_arf->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->payment_arf->fetchAll()->count();

//        $data['data'] = $this->arfh->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
//        $data['total'] = $this->arfh->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpaymentrpiAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else if ($textsearch != null && $option == 1) {
            $search = "trano like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 2) {
            $search = "prj_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 3) {
            $search = "prj_nama like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 4) {
            $search = "sit_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 5) {
            $search = "sit_nama like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 6) {
            $search = "doc_trano like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->payment_rpi->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->payment_rpi->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getvoucherAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $option_type = $this->getRequest()->getParam('option_type');
        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = "";
        } else if ($textsearch != null && $option == 1) {
            $search = "trano like '%$textsearch%' AND deleted=0 ";
        } else if ($textsearch != null && $option == 2) {
            $search = "tgl like '%$textsearch%' AND deleted=0 ";
        } else if ($textsearch != null && $option == 3) {
            $search = "ref_number like '%$textsearch%' AND deleted=0 ";
        } else if ($textsearch != null && $option == 4) {
            $search = "prj_kode like '%$textsearch%' AND deleted=0 ";
        }

        if ($type) {
            if ($search)
                $search .= " AND item_type = '$type' AND deleted=0 ";
            else
                $search = "item_type = '$type' AND deleted=0 ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        if ($search) {
            $search = $search . " AND (status_bpv_ppn = 0) AND (status_bpv_wht = 0) AND deleted=0 ";
        } else
            $search = "status_bpv_ppn = 0 AND status_bpv_wht= 0 AND deleted=0 ";

        $sub = $this->db->select()
                ->from(array('finance_payment_voucher'))
                ->where($search)
                ->order(array($sort . ' ' . $dir))
                ->group(array("trano"));

        $sql = $this->db->select()
                ->from(array("a" => $sub), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS a.*")
                ))
                ->limit($limit, $offset);

//        if ($search == '')
//            $sql->reset(Zend_Db_Select::WHERE);

        $data['data'] = $this->db->fetchAll($sql);

//        $data['data'] = $this->voucher->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showinvoicesummaryAction() {
        
    }
    
    public function invoicesummaryAction() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $cusKode = $this->getRequest()->getParam("cus_kode");
        $startDate = $this->getRequest()->getParam("start_date");
        $endDate = $this->getRequest()->getParam("end_date");
        $all = ($this->getRequest()->getParam("all")) ? true : false;
        $print = ($this->getRequest()->getParam("print")) ? true : false;

        $current = $this->getRequest()->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->getRequest()->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->getRequest()->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * 20;


        $subselect = $this->db->select()
                ->from(array("a" => $this->FINANCE->RequestInvoice->__name()), array(
                    "trano",
                    "prj_kode",
                    "sit_kode",
                    "cus_kode",
                    "prj_nama",
                    "sit_nama",
                    "co_no",
                    "total",
                    "tgl",
                ))
                ->joinLeft(array("b" => $this->FINANCE->InvoiceDetail->name()), "a.trano = b.riv_no", array(
                    "total_invoice" => "(IF(b.val_kode = 'USD',(b.total*b.rateidr),b.total))",
                    "total_invoice_USD" => "(IF(b.val_kode = 'USD',b.total,0))",
                    "total_ori"=>"total",
                    "nama_brg",
                    "val_kode",
                    "rateidr",
                    "jumlah" => "(IF(b.val_kode = 'IDR',b.jumlah,(b.jumlah*b.rateidr)))",
                    "jumlah_USD" => "(IF(b.val_kode = 'USD',b.jumlah,0))",
                    "inv_no" => "trano",
                    "tgl_invoice" => "tgl",
                    "ppn" => "(IF(b.statusppn = 'Y',(IF(b.val_kode = 'USD',b.qty*b.harga*0.1*b.rateidr,b.qty*b.harga*0.1)),0))",
                    "ppn_USD" => "(IF(b.statusppn = 'Y',(IF(b.val_kode = 'USD',b.qty*b.harga*0.1,0)),0))",
                    "holding_tax_val_USD" => "(IF(b.val_kode = 'USD',COALESCE(b.holding_tax_val,0),0))",
                    "holding_tax_val" => "(IF(b.val_kode = 'USD',COALESCE(b.holding_tax_val*b.rateidr,0),COALESCE(b.holding_tax_val,0)))",
                    "deduction_USD" => "(IF(b.val_kode = 'USD',COALESCE(b.deduction,0),0))",
                    "deduction" => "(IF(b.val_kode = 'USD',COALESCE(b.deduction*b.rateidr,0),COALESCE(b.deduction,0)))",
                ))
                ->joinLeft(array("c" => $this->FINANCE->AccountingCancelInvoice->__name()), "b.trano = c.invoice_no", array())
                ->where("b.trano IS NOT NULL")
                ->where("(b.total - coalesce(c.total_cancel,0)) > 0");
;


        if ($prjKode)
            $subselect = $subselect->where("a.prj_kode = ?", $prjKode);
        if ($sitKode)
            $subselect = $subselect->where("a.sit_kode = ?", $sitKode);
        if ($cusKode)
            $subselect = $subselect->where("a.cus_kode = ?", $cusKode);

        if ($startDate) {
            $start = date("Y-m-d", strtotime($startDate));
            if ($endDate) {
                $end = date("Y-m-d", strtotime($endDate));
                $subselect = $subselect->where("b.tgl BETWEEN '$start' AND '$end'");
            } else
                $subselect = $subselect->where("b.tgl = '$start'");
        }

        $subselect2 = $this->db->select()
                ->from(array("d" => $subselect), array(
                    "*",
                    "aging" => "(IF(d.tgl_invoice IS NOT NULL,DATEDIFF(d.tgl_invoice,d.tgl),0))"
                ))
                ->joinLeft(array("c" => $this->FINANCE->PaymentInvoice->name()), "d.inv_no = c.inv_no", array(
                    "paid_no" => "trano",
                    "total_payment" => "COALESCE((IF(c.val_kode = 'USD',(c.total*c.rateidr),c.total)),0)",
                    "total_payment_USD" => "COALESCE((IF(c.val_kode = 'USD',c.total,0)),0)",
                    "tgl_payment" => "tgl",
                    "val_kode_payment" => "val_kode",
                    "rateidr_payment" => "rateidr",
                    "aging_payment" => "(IF(c.tgl IS NULL,DATEDIFF(NOW(),d.tgl_invoice),''))"
                ))
                ->order(array("d.trano ASC"));

        $subselect3 = $this->db->select()
                ->from(array("e" => $subselect2), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS e.*"),
                    "jumlah" => "SUM(jumlah)",
                    "jumlah_USD" => "SUM(jumlah_USD)",
                    "ppn" => "SUM(ppn)",
                    "ppn_USD" => "SUM(ppn_USD)",
                    "holding_tax" => "SUM(holding_tax_val)",
                    "holding_tax_USD" => "SUM(holding_tax_val_USD)",
                    "deduction" => "SUM(deduction)",
                    "deduction_USD" => "SUM(deduction_USD)",
                    "total_payment" => "SUM(total_payment)",
                    "total_payment_USD" => "SUM(total_payment_USD)",
                ))
                ->group(array("e.trano", "e.inv_no", "e.paid_no"));

        if (!$all)
            $subselect3 = $subselect3->limit(20, $offset);

        $fetch = $this->db->fetchAll($subselect3);

        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
        $data = array();

        $cus = new Default_Models_MasterCustomer();
        $pro = new Default_Models_MasterProject();
        $sit = new Default_Models_MasterSite();

        if ($fetch) {
            foreach ($fetch as $k => $v) {
                $trano = $v['co_no'];
                $arr = array(
                    "co_no" => $v['co_no'],
                    "inv_no" => $v['inv_no'],
                    "project" => $v['prj_kode'] . " - " . $v['prj_nama'],
                    "site" => $v['sit_kode'] . " - " . $v['sit_nama'],
                    "cus_kode" => $v['cus_kode'],
                    "tgl" => date('d M Y', strtotime($v['tgl'])),
                    "tglInvoice" => date('d M Y', strtotime($v['tgl_invoice'])),
                    "tglPaymentInvoice" => ($v['tgl_payment'] != '') ? date('d M Y', strtotime($v['tgl_payment'])) : '',
                    "nama_brg" => $v['nama_brg'],
                    "totalRequestInvoice" => $v['total'],
                    "totalInvoice" => $v['total_invoice'],
                    "totalInvoiceUSD" => $v['total_invoice_USD'],
                    "totalPaymentInvoice" => $v['total_payment'],
                    "totalPaymentInvoiceUSD" => $v['total_payment_USD'],
                    "ppn" => $v['ppn'],
                    "ppnUSD" => $v['ppn_USD'],
                    "holding_tax" => $v['holding_tax'],
                    "holding_taxUSD" => $v['holding_tax_USD'],
                    "deduction" => $v['deduction'],
                    "deductionUSD" => $v['deduction_USD'],
                    "val_kode" => $v['val_kode'],
                    "aging" => $v['aging'],
                    "agingPayment" => $v['aging_payment'],
                );

                if ($v['sit_nama'] == '')
                    $arr['site'] = $v['sit_kode'] . " - " . $sit->getSiteName($v['prj_kode'], $v['sit_kode']);
                if ($v['prj_nama'] == '')
                    $arr['project'] = $v['prj_kode'] . " - " . $pro->getProjectName($v['prj_kode']);

                if (!$print) {
                    $arr['customer_name'] = wordwrap($cus->getName($v['cus_kode']), 30, "<br>");
                    $arr['project'] = wordwrap($arr['project'], 30, "<br>");
                    $arr['site'] = wordwrap($arr['site'], 30, "<br>");
                    $arr['nama_brg'] = wordwrap($arr['nama_brg'], 30, "<br>");
                }

                $data[$trano][] = $arr;
            }
        }

        if (!$print) {
            $this->view->data = $data;
            if (!$all) {
                $this->view->limitPerPage = 20;
                $this->view->totalResult = $count;
                $this->view->current = $current;
                $this->view->currentPage = $currentPage;
                $this->view->requested = $requested;
                $this->view->pageUrl = $this->view->url();
            }
            $this->view->all = $all;
        } else {

            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            if ($fetch) {
                foreach ($fetch as $k => $v) {

                    $newData[] = array(
                        "CO Number" => $v['co_no'],
                        "Invoice Number" => $v['inv_no'],
                        "Customer" => $cus->getName($v['cus_kode']),
                        "Project" => $v['prj_kode'] . " - " . $pro->getProjectName($v['prj_kode']),
                        "Site" => $v['sit_kode'] . " - " . $sit->getSiteName($v['prj_kode'], $v['sit_kode']),
                        "Invoice Date" => date('d M Y', strtotime($v['tgl_invoice'])),
                        "Paid Date" => ($v['tgl_payment'] != '') ? date('d M Y', strtotime($v['tgl_payment'])) : '',
                        "Description" => $v['nama_brg'],
                        "Invoice Value (IDR)" => $v['total_invoice'],
                        "Invoice Value (USD)" => $v['total_invoice_USD'],
                        "Payment Value (IDR)" => $v['total_payment'],
                        "Payment Value (USD)" => $v['total_payment_USD'],
                        "Tax-10% VAT (IDR)" => number_format($v['ppn'], 2),
                        "Tax-10% VAT (USD)" => number_format($v['ppn_USD'], 2),
                        "Holding Tax (IDR)" => $v['holding_tax'],
                        "Holding Tax (USD)" => $v['holding_tax_USD'],
                        "Deduction (IDR)" => $v['deduction'],
                        "Deduction (USD)" => $v['deduction_USD'],
                        "Valuta" => $v['val_kode'],
                        "Payment Aging" => $v['aging_payment'],
                    );
                }
            }

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Invoice Summary"
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        11 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
        $this->view->all = $all;
    }

    
    public function invoicesummary2Action() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $cusKode = $this->getRequest()->getParam("cus_kode");
        $startDatePaid = $this->getRequest()->getParam("start_date");
        $endDatePaid = $this->getRequest()->getParam("end_date");
        $all = ($this->getRequest()->getParam("all")) ? true : false;
        $print = ($this->getRequest()->getParam("print")) ? true : false;

        $current = $this->getRequest()->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->getRequest()->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->getRequest()->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * 20;


        $subselect = $this->db->select()
                ->from(array("a" => $this->FINANCE->RequestInvoice->__name()), array(
                    "trano",
                    "prj_kode",
                    "sit_kode",
                    "cus_kode",
                    "prj_nama",
                    "sit_nama",
                    "co_no",
                    "total",
                    "tgl",
                ))
                ->joinLeft(array("b" => $this->FINANCE->InvoiceDetail->name()), "a.trano = b.riv_no", array(
                    "total_invoice" => "(IF(b.val_kode = 'USD',(b.total*b.rateidr),b.total))",
                    "total_invoice_USD" => "(IF(b.val_kode = 'USD',b.total,0))",
                    "total_ori"=>"total",
                    "nama_brg",
                    "val_kode",
                    "rateidr",
                    "jumlah" => "(IF(b.val_kode = 'IDR',b.jumlah,(b.jumlah*b.rateidr)))",
                    "jumlah_USD" => "(IF(b.val_kode = 'USD',b.jumlah,0))",
                    "inv_no" => "trano",
                    "tgl_invoice" => "tgl",
                    "ppn" => "(IF(b.statusppn = 'Y',(IF(b.val_kode = 'USD',b.qty*b.harga*0.1*b.rateidr,b.qty*b.harga*0.1)),0))",
                    "ppn_USD" => "(IF(b.statusppn = 'Y',(IF(b.val_kode = 'USD',b.qty*b.harga*0.1,0)),0))",
                    "holding_tax_val_USD" => "(IF(b.val_kode = 'USD',COALESCE(b.holding_tax_val,0),0))",
                    "holding_tax_val" => "(IF(b.val_kode = 'USD',COALESCE(b.holding_tax_val*b.rateidr,0),COALESCE(b.holding_tax_val,0)))",
                    "deduction_USD" => "(IF(b.val_kode = 'USD',COALESCE(b.deduction,0),0))",
                    "deduction" => "(IF(b.val_kode = 'USD',COALESCE(b.deduction*b.rateidr,0),COALESCE(b.deduction,0)))",
                ))
                ->joinLeft(array("c" => $this->FINANCE->AccountingCancelInvoice->__name()), "b.trano = c.invoice_no", array())
                ->where("b.trano IS NOT NULL")
                ->where("(b.total - coalesce(c.total_cancel,0)) > 0");
        


        if ($prjKode)
            $subselect = $subselect->where("a.prj_kode = ?", $prjKode);
        if ($sitKode)
            $subselect = $subselect->where("a.sit_kode = ?", $sitKode);
        if ($cusKode)
            $subselect = $subselect->where("a.cus_kode = ?", $cusKode);

        $subselect2 = $this->db->select()
                ->from(array("d" => $subselect), array(
                    "*",
                    "aging" => "(IF(d.tgl_invoice IS NOT NULL,DATEDIFF(d.tgl_invoice,d.tgl),0))"
                ))
                ->joinLeft(array("c" => $this->FINANCE->PaymentInvoice->name()), "d.inv_no = c.inv_no", array(
                    "paid_no" => "trano",
                    "total_payment" => "COALESCE((IF(c.val_kode = 'USD',(c.total*c.rateidr),c.total)),0)",
                    "total_payment_USD" => "COALESCE((IF(c.val_kode = 'USD',c.total,0)),0)",
                    "tgl_payment" => "tgl",
                    "val_kode_payment" => "val_kode",
                    "rateidr_payment" => "rateidr",
                    "aging_payment" => "(IF(c.tgl IS NULL,DATEDIFF(NOW(),d.tgl_invoice),''))"
                ))
                ->order(array("d.trano ASC"));
        
        if ($startDatePaid) {
            $start = date("Y-m-d", strtotime($startDatePaid));
            if ($endDatePaid) {
                $end = date("Y-m-d", strtotime($endDatePaid));
                $subselect2 = $subselect2->where("c.tgl BETWEEN '$start' AND '$end'");
            } else
                $subselect2 = $subselect2->where("c.tgl = '$start'");
        }

        $subselect3 = $this->db->select()
                ->from(array("e" => $subselect2), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS e.*"),
                    "jumlah" => "SUM(jumlah)",
                    "jumlah_USD" => "SUM(jumlah_USD)",
                    "ppn" => "SUM(ppn)",
                    "ppn_USD" => "SUM(ppn_USD)",
                    "holding_tax" => "SUM(holding_tax_val)",
                    "holding_tax_USD" => "SUM(holding_tax_val_USD)",
                    "deduction" => "SUM(deduction)",
                    "deduction_USD" => "SUM(deduction_USD)",
                    "total_payment" => "SUM(total_payment)",
                    "total_payment_USD" => "SUM(total_payment_USD)",
                ))
                ->group(array("e.trano", "e.inv_no", "e.paid_no"));

        if (!$all)
            $subselect3 = $subselect3->limit(20, $offset);

        $fetch = $this->db->fetchAll($subselect3);

        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
        $data = array();

        $cus = new Default_Models_MasterCustomer();
        $pro = new Default_Models_MasterProject();
        $sit = new Default_Models_MasterSite();

        if ($fetch) {
            foreach ($fetch as $k => $v) {
                $trano = $v['co_no'];
                $arr = array(
                    "co_no" => $v['co_no'],
                    "inv_no" => $v['inv_no'],
                    "project" => $v['prj_kode'] . " - " . $v['prj_nama'],
                    "site" => $v['sit_kode'] . " - " . $v['sit_nama'],
                    "cus_kode" => $v['cus_kode'],
                    "tgl" => date('d M Y', strtotime($v['tgl'])),
                    "tglInvoice" => date('d M Y', strtotime($v['tgl_invoice'])),
                    "tglPaymentInvoice" => ($v['tgl_payment'] != '') ? date('d M Y', strtotime($v['tgl_payment'])) : '',
                    "nama_brg" => $v['nama_brg'],
                    "totalRequestInvoice" => $v['total'],
                    "totalInvoice" => $v['total_invoice'],
                    "totalInvoiceUSD" => $v['total_invoice_USD'],
                    "totalPaymentInvoice" => $v['total_payment'],
                    "totalPaymentInvoiceUSD" => $v['total_payment_USD'],
                    "ppn" => $v['ppn'],
                    "ppnUSD" => $v['ppn_USD'],
                    "holding_tax" => $v['holding_tax'],
                    "holding_taxUSD" => $v['holding_tax_USD'],
                    "deduction" => $v['deduction'],
                    "deductionUSD" => $v['deduction_USD'],
                    "val_kode" => $v['val_kode'],
                    "aging" => $v['aging'],
                    "agingPayment" => $v['aging_payment'],
                );

                if ($v['sit_nama'] == '')
                    $arr['site'] = $v['sit_kode'] . " - " . $sit->getSiteName($v['prj_kode'], $v['sit_kode']);
                if ($v['prj_nama'] == '')
                    $arr['project'] = $v['prj_kode'] . " - " . $pro->getProjectName($v['prj_kode']);

                if (!$print) {
                    $arr['customer_name'] = wordwrap($cus->getName($v['cus_kode']), 30, "<br>");
                    $arr['project'] = wordwrap($arr['project'], 30, "<br>");
                    $arr['site'] = wordwrap($arr['site'], 30, "<br>");
                    $arr['nama_brg'] = wordwrap($arr['nama_brg'], 30, "<br>");
                }

                $data[$trano][] = $arr;
            }
        }

        if (!$print) {
            $this->view->data = $data;
            if (!$all) {
                $this->view->limitPerPage = 20;
                $this->view->totalResult = $count;
                $this->view->current = $current;
                $this->view->currentPage = $currentPage;
                $this->view->requested = $requested;
                $this->view->pageUrl = $this->view->url();
            }
            $this->view->all = $all;
        } else {

            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            if ($fetch) {
                foreach ($fetch as $k => $v) {

                    $newData[] = array(
                        "CO Number" => $v['co_no'],
                        "Invoice Number" => $v['inv_no'],
                        "Customer" => $cus->getName($v['cus_kode']),
                        "Project" => $v['prj_kode'] . " - " . $pro->getProjectName($v['prj_kode']),
                        "Site" => $v['sit_kode'] . " - " . $sit->getSiteName($v['prj_kode'], $v['sit_kode']),
                        "Invoice Date" => date('d M Y', strtotime($v['tgl_invoice'])),
                        "Paid Date" => ($v['tgl_payment'] != '') ? date('d M Y', strtotime($v['tgl_payment'])) : '',
                        "Description" => $v['nama_brg'],
                        "Invoice Value (IDR)" => $v['total_invoice'],
                        "Invoice Value (USD)" => $v['total_invoice_USD'],
                        "Payment Value (IDR)" => $v['total_payment'],
                        "Payment Value (USD)" => $v['total_payment_USD'],
                        "Tax-10% VAT (IDR)" => number_format($v['ppn'], 2),
                        "Tax-10% VAT (USD)" => number_format($v['ppn_USD'], 2),
                        "Holding Tax (IDR)" => $v['holding_tax'],
                        "Holding Tax (USD)" => $v['holding_tax_USD'],
                        "Deduction (IDR)" => $v['deduction'],
                        "Deduction (USD)" => $v['deduction_USD'],
                        "Valuta" => $v['val_kode'],
                        "Payment Aging" => $v['aging_payment'],
                    );
                }
            }

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Invoice Summary"
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        11 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
        $this->view->all = $all;
    }
    
    public function jurnalapAction() {
//        $this->_helper->viewRenderer->setNoRender();
//        $this->_helper->layout->disableLayout();

        $bpv_trano = $this->getRequest()->getParam('trano');

        $result = $this->jurnalap->fetchAll("trano = '$bpv_trano'")->toArray();

        $this->view->result = $result;

//        var_dump($bpv_trano);die;
//        $data = {};
//        $json = "{success: true}";
//        $json = Zend_Json::encode($data);
//        $this->getResponse()->setHeader('Content-Type','text/javascript');
//        $this->getResponse()->setBody($json);
    }

    public function paymentAction() {
        
    }

    public function getstoretranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $search = '';

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else {
            $search[] = "$option LIKE '%$textsearch%'";
        }
        if (count($search) > 0)
            $search = implode(" AND ", $search);

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 40;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        if ($type == 'ARF') {
            $hasil = $this->arfh->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
            $data['data'] = array();
            $total=0;
            foreach ($hasil as $k => $v) {
                $data['data'][] = array(
                    "trano" => $v['trano'],
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "request" => $v['request2'],
                    "description" => $v['ketin'],
                    "total" => $v['total'],
                    "valuta" => $v['val_kode'],
                    "ppn" => $v['ppn']
                );
                
                $total++;
            }
            $data['total'] = $total;
        } else if ($type == 'PBOQ3') {
            $search = "notran LIKE 'PBQ110%' " . (($search != '') ? "AND " . $search : '');
            $hasil = $this->pmealh->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
            $data['data'] = array();
            foreach ($hasil as $k => $v) {
                $data['data'][] = array(
                    "trano" => $v['notran'],
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "request" => '',
                    "description" => $v['ket'],
                    "total" => $v['jumlah'],
                    "valuta" => $v['val_kode'],
                    "ppn" => '',
                    "tgl" => $v['tgl']
                );
            }
            $data['total'] = $this->pmealh->fetchAll($search)->count(); //count($hasil);//$this->rpih->fetchAll()->count();
        }else if ($type == 'RPI') {
            $hasil = $this->rpih->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
            $data['data'] = array();
            foreach ($hasil as $k => $v) {
                $data['data'][] = array(
                    "trano" => $v['trano'],
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "request" => $v['sup_nama'],
                    "description" => $v['ketin'],
                    "total" => $v['total'],
                    "valuta" => $v['val_kode'],
                    "ppn" => $v['ppn']
                );
            }
            $data['total'] = count($hasil);//$this->rpih->fetchAll()->count();
        } else if ($type == 'REM') {
            $hasil = $this->remh->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
            $data['data'] = array();
            foreach ($hasil as $k => $v) {
                $data['data'][] = array(
                    "trano" => $v['trano'],
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "request" => $v['request'],
                    "description" => $v['ket'],
                    "total" => $v['total'],
                    "valuta" => $v['val_kode']
                );
            }
            $data['total'] = $this->remh->fetchAll()->count();
        } else if ($type == 'PPNREM') {
            $ppnremh = new Finance_Models_PpnReimbursementH();
            $hasil = $ppnremh->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
            $data['data'] = array();
            foreach ($hasil as $k => $v) {
                $data['data'][] = array(
                    "trano" => $v['trano'],
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "request" => QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName(),
                    "description" => $v['ket'],
                    "total" => $v['total'],
                    "valuta" => $v['val_kode']
                );
            }
            $data['total'] = $this->remh->fetchAll()->count();
        } else if ($type == 'BRF') {
            $hasil = $this->brfh->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
            $data['data'] = array();
            foreach ($hasil as $k => $v) {
                $data['data'][] = array(
                    "trano" => $v['trano'],
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "request" => $v['request2'],
                    "description" => $v['ketin'],
                    "total" => $v['total'],
                    "valuta" => $v['val_kode'],
                    "ppn" => $v['ppn']
                );
            }
            $data['total'] = $this->brfh->fetchAll()->count();
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewpaymentreportAction() {
        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $supplier = $this->getRequest()->getParam('supplier');
        $year = $this->getRequest()->getParam('year');
        $requester = $this->getRequest()->getParam('requester');

        $this->view->type = $type;
        $this->view->trano = $trano;
        $this->view->prj_kode = $prj_kode;
        $this->view->sit_kode = $sit_kode;
        $this->view->supplier = $supplier;
        $this->view->year = $year;
        $this->view->requester = $requester;
    }
    
    public function isPartial($trano) {
        $sql = "SELECT IF(totalsequence!='1','1','0')As exist  FROM erpdb.procurement_brfh WHERE trano='$trano'";

        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();

        return $data['exist'];
    }
    
    public function getstorepaymentAction() {

        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $supplier = $this->getRequest()->getParam('supplier');
        $year = $this->getRequest()->getParam('year');
        $requester = $this->getRequest()->getParam('requester');
        $export = ($this->_getParam("export") == 'true') ? true : false;
              
        
        if (!$export) {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
            $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 40;

            $limit_query = ' LIMIT '.$offset.','.$limit.' ;';
        }

        if ($trano != '' && $type=='PBOQ3') {$search1[] = "d.notran = '$trano'";$search2[] = "ref_number = '$trano'";$search3[] = "doc_trano = '$trano'";$search4[] = "bp.trano_ref = '$trano'";$search5[] = "rem_no = '$trano'";$search6[] = "bp.trano = '$trano'";}
        
        if ($trano != '' && $type!='PBOQ3') {$search1[] = "d.trano = '$trano'";$search2[] = "ref_number = '$trano'";$search3[] = "doc_trano = '$trano'";$search4[] = "bp.trano_ref = '$trano'";$search5[] = "rem_no = '$trano'";$search6[] = "bp.trano = '$trano'";}
        
        if ($prj_kode != '') {$search1[] = "d.prj_kode = '$prj_kode'";$search2[] = "prj_kode = '$prj_kode'";$search3[] = "prj_kode = '$prj_kode'";$search4[] = "bp.prj_kode = '$prj_kode'";$search5[] = "prj_kode = '$prj_kode'";$search6[] = "bp.prj_kode = '$prj_kode'";}

        if ($sit_kode != '') {$search1[] = "d.sit_kode ='$sit_kode'";$search2[] = "sit_kode ='$sit_kode'";$search3[] = "sit_kode ='$sit_kode'";$search4[] = "bp.sit_kode ='$sit_kode'";$search5[] = "sit_kode ='$sit_kode'";$search6[] = "bp.sit_kode = '$sit_kode'";}

        if ($supplier != '') {$search1[] = "h.sup_kode = '$supplier'";}
        
        if ($year != '') {$search1[] = "YEAR(d.tgl)='$year'";}
        
        if ($requester != '') {$search1[] = "d.requester = '$requester'";}

        if($trano=='' && $prj_kode=='' && $sit_kode=='' && $supplier=='' && $requester==''){return;}


        if (count($search1) > 0)
            $searchmain = " WHERE " . implode(" AND ", $search1);

        if (count($search2) > 0)
            $searchbpv = " WHERE " . implode(" AND ", $search2);

        if (count($search3) > 0)
            $searchpayment = " WHERE " . implode(" AND ", $search3);

        if (count($search4) > 0)
            $searchbrfp = implode(" AND ", $search4);

        if (count($search5) > 0)
            $searchpaymentrem = " WHERE " . implode(" AND ", $search5);
        
        if($type=='ARF')
        {
            $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT ref_number, SUM(COALESCE(total,0)) AS total_bpv
                    FROM erpdb.finance_payment_voucher
                    $searchbpv AND status_bpv_wht=0 AND deleted=0  AND item_type='ARF'
                    GROUP BY ref_number;";
            $this->db->query($query);
            
            $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT doc_trano, SUM(COALESCE(total_bayar,0)) AS total_payment
                    FROM erpdb.finance_payment_arf
                    $searchpayment
                    GROUP BY doc_trano;";
            $this->db->query($query);

            $query = "DROP TEMPORARY TABLE IF EXISTS `arf`;
                        CREATE TEMPORARY TABLE `arf`
                        SELECT trano AS ref_number,val_kode, prj_kode, sit_kode,SUM(COALESCE(qty*harga,0)) AS trano_value
                        FROM erpdb.procurement_arfd d
                        $searchmain AND d.deleted=0 AND d.trano NOT LIKE '%P%'
                        GROUP BY trano;";
            $this->db->query($query);

            $query = "SELECT arf.ref_number,arf.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,arf.trano_value,arf.prj_kode,arf.sit_kode FROM arf
                        LEFT JOIN bpv ON (arf.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (arf.ref_number = payment.doc_trano);";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        
        }
        
        if($type=='BRF')
        {
            $query = "DROP TEMPORARY TABLE IF EXISTS `brf`;
                        CREATE TEMPORARY TABLE `brf`
                        SELECT d.trano as ref_number, d.val_kode, d.prj_kode, d.sit_kode, SUM(COALESCE(d.qty*d.harga,0)) AS trano_value
                        FROM erpdb.procurement_brfd d
                        $searchmain AND d.deleted=0
                        GROUP BY d.trano;";
            $this->db->query($query);

            $query2 = "SELECT ref_number FROM brf ORDER BY ref_number DESC;";
            $fetch = $this->db->query($query2);
            $datas = $fetch->fetchAll();
            
            if($trano == '' && $prj_kode == '' && $sit_kode == ''){$searchbrfp="bp.trano_ref IN (SELECT ref_number FROM brf ) ";}
    
            
            foreach ($datas as $key => $val) {
                $tranos = $val['ref_number'];
                if ($this->isPartial($tranos) == "0") {
                    $query = "DROP TEMPORARY TABLE IF EXISTS `brf`;
                        CREATE TEMPORARY TABLE `brf`
                        SELECT a.*, b.tgl_awal, b.tgl_akhir
                        FROM (  
                            SELECT d.trano as ref_number, d.val_kode, d.prj_kode, d.sit_kode,
                            d.requester, d.approve, SUM(COALESCE(d.qty*d.harga,0)) AS trano_value
                            FROM erpdb.procurement_brfd d
                            WHERE d.trano = '$tranos' AND d.deleted = 0 GROUP BY d.trano ) a
                            INNER JOIN procurement_brfh b ON (a.ref_number = b.trano
                        );";
                    $this->db->query($query);

                    $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                        CREATE TEMPORARY TABLE `bpv`
                        SELECT bp.trano AS ref_number, SUM(COALESCE(pv.total,0)) AS total_bpv
                        FROM erpdb.finance_payment_voucher pv
                        INNER JOIN erpdb.procurement_brfd bp ON (bp.trano = pv.ref_number AND bp.deleted=0 AND bp.trano='$tranos')
                        WHERE pv.status_bpv_wht=0 AND pv.deleted=0 AND pv.item_type='BRF'
                        GROUP BY pv.ref_number;";
                    $this->db->query($query);

                    $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                        CREATE TEMPORARY TABLE `payment`
                        SELECT bp.trano AS doc_trano, SUM(COALESCE(parf.total_bayar,0)) AS total_payment
                        FROM erpdb.finance_payment_arf parf
                        INNER JOIN erpdb.procurement_brfd bp ON (bp.trano = parf.doc_trano AND bp.deleted=0 AND bp.trano='$tranos')
                        GROUP BY doc_trano;";
                    $this->db->query($query);
                        
                    $query = "SELECT brf.ref_number,brf.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,brf.trano_value,brf.prj_kode,brf.sit_kode, brf.requester, brf.approve, brf.tgl_awal, brf.tgl_akhir FROM brf
                        LEFT JOIN bpv ON (brf.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (brf.ref_number = payment.doc_trano) $limit_query;";
                    $query2 = "SELECT brf.ref_number,brf.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,brf.trano_value,brf.prj_kode,brf.sit_kode, brf.requester, brf.approve, brf.tgl_awal, brf.tgl_akhir FROM brf
                            LEFT JOIN bpv ON (brf.ref_number = bpv.ref_number)
                            LEFT JOIN payment ON (brf.ref_number = payment.doc_trano);";

                    $fetch = $this->db->query($query);
                    $fetch = $fetch->fetchAll();
                    $array = (object) array_merge((array) $array, (array) $fetch);
                    
                } else {
                    $query = "DROP TEMPORARY TABLE IF EXISTS `brf`;
                        CREATE TEMPORARY TABLE `brf`
                        SELECT a.*, b.tgl_awal, b.tgl_akhir
                        FROM (  
                            SELECT d.trano as ref_number, d.val_kode, d.prj_kode, d.sit_kode,
                            d.requester, d.approve, SUM(COALESCE(d.qty*d.harga,0)) AS trano_value
                            FROM erpdb.procurement_brfd d
                            WHERE d.trano = '$tranos' AND d.deleted = 0 GROUP BY d.trano ) a
                            INNER JOIN procurement_brfh b ON (a.ref_number = b.trano
                        );";
                    $this->db->query($query);

                    $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                        CREATE TEMPORARY TABLE `bpv`
                        SELECT bp.trano_ref AS ref_number, SUM(COALESCE(pv.total,0)) AS total_bpv
                        FROM erpdb.finance_payment_voucher pv
                        INNER JOIN erpdb.procurement_brfd_payment bp ON (bp.trano = pv.ref_number AND bp.deleted=0 AND bp.trano_ref='$tranos')
                        WHERE pv.status_bpv_wht=0 AND pv.deleted=0 AND pv.item_type='BRFP'
                        GROUP BY pv.ref_number;";
                    $this->db->query($query);

                    $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                        CREATE TEMPORARY TABLE `payment`
                        SELECT bp.trano_ref AS doc_trano, SUM(COALESCE(parf.total_bayar,0)) AS total_payment
                        FROM erpdb.finance_payment_arf parf
                        INNER JOIN erpdb.procurement_brfd_payment bp ON (bp.trano = parf.doc_trano AND bp.deleted=0 AND bp.trano_ref='$tranos')
                        GROUP BY doc_trano;";
                    $this->db->query($query);

                    $query = "SELECT brf.ref_number,brf.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,brf.trano_value,brf.prj_kode,brf.sit_kode, brf.requester, brf.approve, brf.tgl_awal, brf.tgl_akhir FROM brf
                        LEFT JOIN bpv ON (brf.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (brf.ref_number = payment.doc_trano) $limit_query;";
                    
                    $query2 = "SELECT brf.ref_number,brf.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,brf.trano_value,brf.prj_kode,brf.sit_kode, brf.requester, brf.approve, brf.tgl_awal, brf.tgl_akhir FROM brf
                            LEFT JOIN bpv ON (brf.ref_number = bpv.ref_number)
                            LEFT JOIN payment ON (brf.ref_number = payment.doc_trano);";
                    $fetch = $this->db->query($query);
                    $fetch = $fetch->fetchAll();
                    $array = (object) array_merge((array) $array, (array) $fetch);
                }
            }
            if (!is_null($array)) {
                $fetch = json_decode(json_encode($array), true);
                $data['total'] = count($fetch);
            } else {
                $data['total'] = 0;
            }
        
        }
         
        if($type=='PBOQ3')
        {
            $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT ref_number, SUM(COALESCE(total,0)) AS total_bpv
                    FROM erpdb.finance_payment_voucher
                    $searchbpv AND status_bpv_wht=0 AND deleted=0  AND item_type='PBOQ3'
                    GROUP BY ref_number;";
            $this->db->query($query);
            
            $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT doc_trano, SUM(COALESCE(total_bayar,0)) AS total_payment
                    FROM erpdb.finance_payment_pmeal
                    $searchpayment
                    GROUP BY doc_trano;";
            $this->db->query($query);

            $query = "DROP TEMPORARY TABLE IF EXISTS `pmeal`;
                        CREATE TEMPORARY TABLE `pmeal`
                        SELECT notran AS ref_number,val_kode, prj_kode, sit_kode,SUM(COALESCE(qty*harga_borong,0)) AS trano_value
                        FROM erpdb.boq_dboqpasang d
                        $searchmain AND d.deleted=0 
                        GROUP BY notran;";
            $this->db->query($query);
           
            $query = "SELECT pmeal.ref_number,pmeal.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,pmeal.trano_value,pmeal.prj_kode,pmeal.sit_kode FROM pmeal
                        LEFT JOIN bpv ON (pmeal.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (pmeal.ref_number = payment.doc_trano);";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        
        }
        
        if($type=='RPI')
        {
            $query = "DROP TEMPORARY TABLE IF EXISTS `rpi`;
                        CREATE TEMPORARY TABLE `rpi`
                        SELECT h.sup_nama,d.val_kode, d.trano AS ref_number, d.prj_kode, d.sit_kode,SUM(COALESCE(d.qty*d.harga-d.totalwht-d.total_deduction,0)) AS trano_value,SUM(COALESCE(d.ppn,0)) AS ppn_value
                        FROM erpdb.procurement_rpid d
                        INNER JOIN erpdb.procurement_rpih h ON (h.trano =d.trano)
                        $searchmain AND d.deleted=0
                        GROUP BY d.trano, d.prj_kode, d.sit_kode;";
  
            $this->db->query($query);
            
            if($trano ==''){$searchbpv='WHERE ref_number IN (SELECT ref_number FROM rpi) ';}
                    
            $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT ref_number, SUM(COALESCE(total,0)) AS total_bpv
                    FROM erpdb.finance_payment_voucher
                    $searchbpv AND status_bpv_wht=0 AND deleted=0 AND item_type='RPI'
                    GROUP BY ref_number;";
        
            $this->db->query($query);
        
            if($trano ==''){$searchpayment="WHERE doc_trano IN (SELECT ref_number FROM rpi) ";}
            
            $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT doc_trano, SUM(COALESCE(total_bayar,0)) AS total_payment
                    FROM erpdb.finance_payment_rpi
                    $searchpayment AND stspayment ='Y'
                    GROUP BY doc_trano;";
            $this->db->query($query);
        
            $query = "SELECT rpi.sup_nama,rpi.val_kode,rpi.ref_number, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,rpi.trano_value,rpi.prj_kode,rpi.sit_kode,rpi.ppn_value FROM rpi
                        LEFT JOIN bpv ON (rpi.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (rpi.ref_number = payment.doc_trano);";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        
        }

        if($type=='REM')
        {
        
            $query = "DROP TEMPORARY TABLE IF EXISTS `rem`;
                        CREATE TEMPORARY TABLE `rem`
                        SELECT d.trano AS ref_number,d.val_kode, d.prj_kode, d.sit_kode,SUM(COALESCE(d.qty*d.harga,0)) AS trano_value
                        FROM erpdb.procurement_reimbursementd d
                        $searchmain AND deleted=0
                        GROUP BY trano;";
            $this->db->query($query);
            
            $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT ref_number, SUM(COALESCE(total,0)) AS total_bpv
                    FROM erpdb.finance_payment_voucher
                    $searchbpv AND status_bpv_wht=0 AND deleted=0
                    GROUP BY ref_number;";
            $this->db->query($query);
            
            $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT rem_no, SUM(COALESCE(total,0)) AS total_payment
                    FROM erpdb.finance_payment_reimbursement
                    $searchpaymentrem
                    GROUP BY rem_no;";
            $this->db->query($query);
        
            $query = "SELECT rem.ref_number,rem.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,rem.trano_value,rem.prj_kode,rem.sit_kode FROM rem
                        LEFT JOIN bpv ON (rem.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (rem.ref_number = payment.rem_no);";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        
        }

        if($type=='PPNREM')
        {
            $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT ref_number, SUM(COALESCE(total,0)) AS total_bpv
                    FROM erpdb.finance_payment_voucher
                    $searchbpv AND status_bpv_wht=0 AND deleted=0
                    GROUP BY ref_number;";
            $this->db->query($query);
        
            $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT doc_trano, SUM(COALESCE(total_bayar,0)) AS total_payment
                    FROM erpdb.finance_payment_arf
                    $searchpayment
                    GROUP BY doc_trano;";

            $this->db->query($query);
        
            $query = "DROP TEMPORARY TABLE IF EXISTS `ppn`;
                        CREATE TEMPORARY TABLE `ppn`
                        SELECT d.trano AS ref_number,d.val_kode, d.prj_kode, d.sit_kode,SUM(COALESCE(d.total,0)) AS trano_value
                        FROM erpdb.finance_ppn_reimbursementh AS d
                        $searchmain
                        GROUP BY d.trano;";

            $this->db->query($query);

            $query = "SELECT ppn.ref_number,ppn.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,ppn.trano_value,ppn.prj_kode,ppn.sit_kode FROM ppn
                        LEFT JOIN bpv ON (ppn.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (ppn.ref_number = payment.doc_trano);";

            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        
        }
        
        if (!$export) {
            $data['data'] = $fetch;

            $json = Zend_Json::encode($data);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
        
        } else {
            $newData = array();
            $no = 1;
            $totalTrans = 0;
            $totalVAT = 0;
            $totalBPV = 0;
            $totalPaid = 0;
            $totalBal = 0;

            foreach ($fetch as $key => $val) {

                $newData[] = array(
                    "No" => $no,
                    "Trano" => $val['ref_number'],
                    "Project Code" => $val['prj_kode'],
                    "Site Code" => $val['sit_kode'],
                    "Supplier" => $val['sup_nama'],
                    "Trans. Value" => $val['trano_value'],
                    "VAT" => $val['ppn_value'],
                    "BPV Value" => $val['total_bpv'],
                    "Payment Value" => $val['total_payment'],
                    "Balance" => $val['balance'],
                );
                
                if($type=='BRF'){
                    
                    if($val['approve'] == 400){
                        $status = 'Approved';
                    } else if ($val['approve'] == 300){
                        $status = 'Rejected';
                    } else {
                        $status = 'On Going';
                    }
                    
                    $newData[$key]['Requester'] = $val['requester'];
                    $newData[$key]['Start Date'] = $val['tgl_awal'];
                    $newData[$key]['End Date'] = $val['tgl_akhir'];
                    $newData[$key]['Status'] = $status;
                }

                $totalTrans += $val['trano_value'];
                $totalVAT += $val['ppn_value'];
                $totalBPV += $val['total_bpv'];
                $totalPaid += $val['total_payment'];
                $totalBal += $val['balance'];

                $no++;
            }

            $newData[] = array(
                "No" => '',
                "Trano" => '',
                "Project Code" => '',
                "Site Code" => '',
                "Supplier" => '<b>Total</b>',
                "Trans. Value" => '<b>'.$totalTrans.'</b>',
                "VAT" => '<b>'.$totalVAT.'</b>',
                "BPV Value" => '<b>'.$totalBPV.'</b>',
                "Payment Value" => '<b>'.$totalPaid.'</b>',
                "Balance" => '<b>'.$totalBal.'</b>'
            );
            
            $newData['Requester'] = '';
            $newData['Start Date'] = '';
            $newData['End Date'] = '';
            $newData['Status'] = '';
            
            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Payment Report "
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
        
        
    }
    
    public function getstorepaymentOldAction() {


//        $type = $this->getRequest()->getParam('type');
//        $trano = $this->getRequest()->getParam('trano');
//        $prj_kode = $this->getRequest()->getParam('prj_kode');
//        $sit_kode = $this->getRequest()->getParam('sit_kode');
//        $supplier = $this->getRequest()->getParam('supplier');

//        $year = $this->getRequest()->getParam('year');
//        
//        $export = ($this->_getParam("export") == 'true') ? true : false;
//        
//        $limit_query = '';
//        if (!$export) {
//            $this->_helper->viewRenderer->setNoRender();
//            $this->_helper->layout->disableLayout();
//        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
//        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 40;
//            
//            $limit_query = ' LIMIT '.$offset.','.$limit.' ;';
//        }
//        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'PV.ref_number';
//        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';
//
//        if ($trano != '') {
//            $search[] = "ref_number = '$trano'";
//            if ($type == 'BRF')
//            {
//                $search2[] = "trano_ref = '$trano'";
//                $search4[] = "arf.trano_ref= '$trano'";
//            }
//            else
//            {
//                $search2[] = "trano = '$trano'";
//                $search3[] = "pv.ref_number= '$trano'";
//            }
//        }

//        if ($type == 'BRF')
//            $search2[] = "trano_ref like 'BRF%'";

//        if ($prj_kode != '') {
//            $search[] = "prj_kode = '$prj_kode'";
//            $search2[] = "prj_kode = '$prj_kode'";
//            $search3[] = "pv.prj_kode = '$prj_kode'";
//            $search4[] = "pv.prj_kode = '$prj_kode'";
//        }
//
//        if ($sit_kode != '') {
//            $search[] = "sit_kode ='$sit_kode'";
//            $search2[] = "sit_kode ='$sit_kode'";
//            $search3[] = "pv.sit_kode='$sit_kode'";
//            $search4[] = "pv.sit_kode='$sit_kode'";
//        }
//
//        if ($supplier != '') {
//            $search2[] = "sup_kode = '$supplier'";
//        }
//        if ($year != '') {
//            $search[] = "tgl like '$year%'";
//            $search2[] = "tgl like '$year%'";
//            $search3[] = "YEAR(pv.tgl)='$year%'";
//            $search4[] = "YEAR(pv.tgl)='$year%'";
//        }

        if (count($search) > 0){
            $searchdunk = " WHERE " . implode(" AND ", $search);
            $searchBPV =  " WHERE " . implode(" AND ", $search);

        }
        if ($type == 'RPI') {
            if (count($search2) > 0)
                $searchdunk = " WHERE " . implode(" AND ", $search2);
            
            $query = "
              DROP TEMPORARY TABLE IF EXISTS `rpiku`;
              CREATE TEMPORARY TABLE `rpiku`
              SELECT SQL_CALC_FOUND_ROWS * FROM procurement_rpih $searchdunk AND deleted=0
                
              ORDER BY trano DESC
              $limit_query
            ";
            $this->db->query($query);

             $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
            
            $query = " ALTER TABLE rpiku
            ADD INDEX `idx1`(`trano`),
            ADD INDEX `idx2`(`po_no`); ";
            $this->db->query($query);

            /*
             * temporary table for bpv             * 
             */

            $bpv = "drop temporary table if exists bpv;"
                    . "create temporary table bpv "
                    . "select "
                    . "trano bpv_num,total,holding_tax_val,ref_number "
                    . "from finance_payment_voucher "
                    . "$searchBPV ";
            $this->db->query($bpv);

            $query = " ALTER TABLE bpv
            ADD INDEX `idx1`(`bpv_num`),
            ADD INDEX `idx2`(`ref_number`); ";
            $this->db->query($query);
            
            
            /*
             * join rpi n bpv
             */
            
            $rpi_bpv = "drop temporary table if exists rpiBpv;"
                    . "create temporary table rpiBpv "
                    . " SELECT rpi.trano, rpi.tgl, rpi.prj_kode, rpi.sit_kode,"
                    . " rpi.sup_nama, rpi.total, rpi.totalwht, rpi.ppn, SUM(PV.total-PV.holding_tax_val) AS total_bpv,"
                    . "PV.ref_number FROM rpiku rpi LEFT JOIN  bpv PV "
                    . "ON rpi.trano = PV.ref_number "
                    . "GROUP BY rpi.trano "
                    . "ORDER BY rpi.tgl DESC";
           $this->db->query($rpi_bpv);   
            
            $query = " ALTER TABLE rpiBpv
            ADD INDEX `idx1`(`trano`),
            ADD INDEX `idx2`(`ref_number`); ";
            $this->db->query($query);   

            $query = "
            SELECT
                SQL_CALC_FOUND_ROWS a.trano AS ref_number,
                a.prj_kode,
                a.sit_kode,
                a.sup_nama,
                (a.total-a.totalwht) as trano_value,
                a.ppn as ppn_value,
                a.total_bpv,
                sum(PR.total_bayar) as total_payment
            FROM
               rpiBpv a
                LEFT JOIN
                finance_payment_rpi PR
            ON a.ref_number= PR.doc_trano
            GROUP BY a.trano
            ORDER BY a.prj_kode,a.tgl desc,a.trano;
            ";
            

            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
            }
        } else if ($type == 'ARF') {
            //$search2[] = "bt = 'N'";
            if (count($search3) > 0)
                $searchdunk = " WHERE " . implode(" AND ", $search3);
            /*$query = "
              DROP TEMPORARY TABLE IF EXISTS `arfku`;
              CREATE TEMPORARY TABLE `arfku`
              SELECT SQL_CALC_FOUND_ROWS * FROM procurement_arfh $searchdunk

              ORDER BY trano DESC
              $limit_query
            ";*/
            
            //$this->db->query($query);

            //$data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

            /*$query = "SELECT SQL_CALC_FOUND_ROWS a.trano AS ref_number,a.prj_kode,a.sit_kode,a.total as trano_value,sum(a.total_bpv) as total_bpv,sum(PR.total) as total_payment, 0 AS ppn_value FROM
                            (SELECT arf.trano,arf.tgl,arf.prj_kode,arf.sit_kode,arf.total, PV.ref_number,PV.total AS total_bpv FROM arfku arf LEFT JOIN finance_payment_voucher PV ON (arf.trano = PV.ref_number AND PV.deleted=0)) a LEFT JOIN finance_payment_arf PR ON a.ref_number= PR.doc_trano
                            GROUP BY a.trano
                            ORDER BY a.tgl DESC;
                ";*/
            $query = "SELECT pv.ref_number AS ref_number,pv.prj_kode AS prj_kode,pv.sit_kode AS sit_kode,arf.total AS trano_value,pv.total AS total_bpv, COALESCE(parf.total_bayar,0) AS total_payment  
                        FROM erpdb.finance_payment_voucher pv 
                        LEFT JOIN erpdb.procurement_arfh arf ON (arf.trano=pv.ref_number)
                        LEFT JOIN erpdb.finance_payment_arf parf ON (parf.voc_trano = pv.trano) $searchdunk";

            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        } else if ($type == 'REM') {
            if (count($search2) > 0)
                $searchdunk = " WHERE " . implode(" AND ", $search2);
            $query = "
              DROP TEMPORARY TABLE IF EXISTS `remku`;
              CREATE TEMPORARY TABLE `remku`
              SELECT SQL_CALC_FOUND_ROWS * FROM procurement_reimbursementh $searchdunk

              ORDER BY trano DESC
              $limit_query
            ";
            $this->db->query($query);

            $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

            $query = "SELECT SQL_CALC_FOUND_ROWS a.trano AS ref_number,a.prj_kode,a.sit_kode,a.total as trano_value,sum(a.total_bpv) as total_bpv,sum(PR.total) as total_payment, 0 AS ppn_value FROM
                        (SELECT rem.trano,rem.tgl,rem.prj_kode,rem.sit_kode,rem.total, PV.total AS total_bpv,PV.ref_number FROM remku rem LEFT JOIN finance_payment_voucher PV ON (rem.trano = PV.ref_number AND PV.deleted=0)) a LEFT JOIN finance_payment_reimbursement PR ON a.ref_number= PR.rem_no
                        GROUP BY a.trano
                        ORDER BY a.tgl DESC;
            ";



            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
            }
        } else if ($type == 'PPNREM') {
            if (count($search2) > 0)
                $searchdunk = " WHERE " . implode(" AND ", $search2);
            $query = "
              DROP TEMPORARY TABLE IF EXISTS `ppnremku`;
              CREATE TEMPORARY TABLE `ppnremku`
              SELECT SQL_CALC_FOUND_ROWS * FROM finance_ppn_reimbursementh $searchdunk

              ORDER BY trano DESC
              $limit_query
            ";
            $this->db->query($query);

            $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

            $query = "SELECT
              SQL_CALC_FOUND_ROWS a.trano AS ref_number,
              a.prj_kode,
              a.sit_kode,
              a.total as trano_value,
              sum(a.total_bpv) as total_bpv,
              sum(PR.total_bayar) as total_payment,
              0 AS ppn_value
              FROM
              (
                  SELECT
                    rem.trano,
                    rem.tgl,
                    rem.prj_kode,
                    rem.sit_kode,
                    rem.total,
                    PV.total AS total_bpv,
                    PV.ref_number
                  FROM ppnremku rem
                  LEFT JOIN finance_payment_voucher PV
                  ON (rem.trano = PV.ref_number AND PV.deleted=0)
              ) a
              LEFT JOIN finance_payment_ppn_reimbursement PR
              ON a.ref_number= PR.doc_trano
              GROUP BY a.trano
              ORDER BY a.tgl DESC;
            ";


            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
            }
        } else if ($type == 'BRF') {
            if (count($search2) > 0)
                $searchdunk = " WHERE " . implode(" AND ", $search4);

            /*$query = "
              DROP TEMPORARY TABLE IF EXISTS `brfku`;
              CREATE TEMPORARY TABLE `brfku`
              SELECT SQL_CALC_FOUND_ROWS * FROM procurement_arfh $searchdunk

              ORDER BY trano DESC
              $limit_query
            ";
            $this->db->query($query);

            $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

            $query = "SELECT SQL_CALC_FOUND_ROWS a.brf_num, a.trano AS ref_number,a.prj_kode,a.sit_kode,a.total as trano_value,sum(a.total_bpv) as total_bpv,sum(PR.total) as total_payment, 0 AS ppn_value FROM
                            (SELECT brf_ori.trano brf_num, brf.trano,brf.tgl,brf.prj_kode,brf.sit_kode,brf.total, PV.ref_number,PV.total AS total_bpv FROM brfku brf 
                            LEFT JOIN finance_payment_voucher PV ON (brf.trano = PV.ref_number AND PV.deleted=0)
                            LEFT JOIN procurement_brfh brf_ori ON brf_ori.trano = brf.trano_ref
                            )a 
                            LEFT JOIN finance_payment_arf PR ON a.ref_number= PR.doc_trano
                            GROUP BY a.trano
                            ORDER BY a.brf_num ASC,a.trano ASC, a.prj_kode ASC;
                ";*/
            $query = "SELECT pv.ref_number AS ref_number,pv.prj_kode AS prj_kode,pv.sit_kode AS sit_kode,arf.total AS trano_value,pv.total AS total_bpv, COALESCE(parf.total_bayar,0) AS total_payment 
                        FROM erpdb.finance_payment_voucher pv 
                        LEFT JOIN erpdb.procurement_arfh arf ON (arf.trano=pv.ref_number)
                        LEFT JOIN erpdb.finance_payment_arf parf ON (parf.voc_trano = pv.trano) $searchdunk";

            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        }

        if (!$export) {
        $data['data'] = $fetch;

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
        } else {
            $newData = array();
            $no = 1;
            $totalTrans = 0;
            $totalVAT = 0;
            $totalBPV = 0;
            $totalPaid = 0;
            
            foreach ($fetch as $key => $val) {

                $newData[] = array(
                    "No" => $no,
                    "Trano" => $val['ref_number'],
                    "Project Code" => $val['prj_kode'],
                    "Site Code" => $val['sit_kode'],
                    "Supplier" => $val['sup_nama'],
                    "Trans. Value" => $val['trano_value'],
                    "VAT" => $val['ppn_value'],
                    "BPV Value" => $val['total_bpv'],
                    "Payment Value" => $val['total_payment'],
                );
                
                $totalTrans += $val['trano_value'];
                $totalVAT += $val['ppn_value'];
                $totalBPV += $val['total_bpv'];
                $totalPaid += $val['total_payment'];
                
                $no++;
    }

            $newData[] = array(
                    "No" => '',
                    "Trano" => '',
                    "Project Code" => '',
                    "Site Code" => '',
                    "Supplier" => '<b>Total</b>',
                    "Trans. Value" => '<b>'.$totalTrans.'</b>',
                    "VAT" => '<b>'.$totalVAT.'</b>',
                    "BPV Value" => '<b>'.$totalBPV.'</b>',
                    "Payment Value" => '<b>'.$totalPaid.'</b>'
                );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Payment Report "
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function pettycashreceivemoneyAction() {
        
    }

    public function balancesheetAction() {
        $max = QDC_Finance_BalanceSheet::factory()->maxLevel();

        $level = array();
        for ($i = 1; $i <= $max; $i++) {
            $level[] = array(
                $i,
                $i
            );
        }
        $this->view->max = ($max != '') ? $max : 0;
        $this->view->levels = Zend_Json::encode($level);
    }

    public function getyearperiodeAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data['data'] = QDC_Finance_Periode::factory()->getAllYearPeriode();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getmonthperiodeAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $year = $this->getRequest()->getParam("year");

        $data['data'] = QDC_Finance_Periode::factory()->getAllMonthPeriode($year);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewbalancesheetAction() {
        $year = $this->getRequest()->getParam("year");
        $month = $this->getRequest()->getParam("month");
        $depth = $this->getRequest()->getParam("depth");
        $export = ($this->getRequest()->getParam("export") == 'true') ? true : false;

        if (!$depth)
            $depth = 3;

        $params = array(
            "leveldepth" => $depth,
            "year" => $year,
            "month" => $month,
            "number_format" => (!$export) ? true : false,
            "spaces" => $export
        );

        $BL = QDC_Finance_BalanceSheet::factory($params)->generateBalanceSheet();

        if (!$export) {
            $this->view->result = $BL;
            $this->view->periode = date("F", strtotime($year . "-" . $month . '-' . '01')) . " " . $year;
        } else {
            $title = date("F", strtotime($year . "-" . $month . '-' . '01')) . " " . $year;
            $newData = array();
            $no = 1;
            $total = 0;
            $totalusd = 0;

            foreach ($BL as $k => $v) {
                $text = $v['coa_nama'];
                $total = $v['total'];
                if ($v['val_kode'] == 'IDR') {
                    $totalusd = 0;
                    $total = $v['total'];
                } else if ($v['val_kode'] != 'IDR' || $v['val_kode'] != NULL) {
                    $totalusd = $v['total'];
                    $total = $v['total_conversi'];
                }

                if ($v['text']) {
                    $isTotalText = true;
                    $text = $v['text'];
                    $total = $v['grandtotal'];
                    $totalusd = $v['grandtotal_usd'];
                }
                $newData[] = array(
                    "No" => $no,
                    "COA Code" => $v['coa_kode'],
                    "COA Namex" => $text,
                    "Total (USD)" => $totalusd,
                    "Total (IDR)" => $total
                );
                $no++;
            }
            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Balance Sheet Closed " . $title
                    ))
                    ->setCellFormat(array(
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function taxreconAction() {
        
    }

    public function viewtaxreconAction() {
        
    }

    public function gettaxAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch != null || $textsearch != '') {
            $option = "a." . $option;
            $search = "AND $option LIKE '%$textsearch%'";
        }

//        var_dump($option,$search);die;

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'a.tax_trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'asc';

        $query = "  SELECT SQL_CALC_FOUND_ROWS a.* FROM (SELECT pv.trano as tax_trano,pv.tgl as tax_date,pv.total as tax_total,
                    pv.ref_number as tax_refnumber,pv.requester as tax_requester,pv.ppn_ref_number as tax_ppnrefnumber,pv.valuta as valuta,
                    pr.trano as payment_trano,pr.tgl as payment_date,pr.total_bayar as payment_totalbayar
                    FROM finance_payment_voucher pv
                    LEFT JOIN finance_payment_rpi pr ON (pv.trano = pr.voc_trano AND pv.deleted=0 AND pr.deleted=0)
                    WHERE ppn_ref_number IS NOT NULL ) a LEFT JOIN finance_recontax r ON r.voc_trano = a.tax_trano
                    WHERE r.voc_trano IS NULL $search
                    ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query($query);

        if ($fetch) {
            $fetch = $fetch->fetchAll();
        }

        $data['data'] = $fetch;
        $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinserttaxreconAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $taxrecondata = Zend_Json::decode($this->getRequest()->getParam('jsonData'));

//        var_dump($taxrecondata);die;

        foreach ($taxrecondata as $key => $val) {
            $stspost = 0;

            if ($val['stspost'] == true) {
                $stspost = 1;
            }

            $inserttaxrecon = array(
                "voc_trano" => $val['tax_trano'],
                "voc_date" => date('Y-m-d H:i:s', strtotime($val['tax_date'])),
                "voc_total" => floatval($val['tax_total']),
                "doc_trano" => $val['tax_refnumber'],
                "requester" => $val['tax_requester'],
                "val_kode" => $val['valuta'],
                "ppn_ref_number" => $val['tax_ppnrefnumber'],
                "payment_trano" => $val['payment_trano'],
                "payment_date" => date('Y-m-d H:i:s', strtotime($val['payment_date'])),
                "payment_total" => floatval($val['payment_totalbayar']),
                "uid" => $this->session->userName,
                "close_date" => date('Y-m-d H:i:s'),
                "stspost" => $stspost
            );

            $this->taxrecon->insert($inserttaxrecon);

            $updatebpv = array(
                "ppn_ref_number" => $val['tax_ppnrefnumber']
            );

            $this->voucher->update($updatebpv, "trano = '{$val['tax_trano']}'");
        }

        $return = array("success" => true);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getrecontaxAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'close_date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'asc';

        $data['data'] = $this->taxrecon->fetchAll()->toArray(null, array($sort . " " . $dir), $limit, $offset);
        $data['total'] = $this->taxrecon->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function profitlossAction() {
        
    }

    public function viewprofitlossAction() {
        $year = $this->getRequest()->getParam("year");
        $month = $this->getRequest()->getParam("month");
        $export = ($this->_getParam("export") != '') ? true : false;


        $select = $this->db->select()
                ->from(array("a" => $this->FINANCE->AccountingSaldoRL->name()), array(
                    "a.coa_kode",
                    "a.coa_nama",
                    "a.total" => "IF(a.total < 0,Concat('(', REPLACE(format(a.total,2), '-', ''),')'),format(a.total,2)) as total",
                    "a.periode",
                    "a.tahun",
                    "b.dk",
                    "b.hd",
                    "b.urut",
                    "b.level",
                    "totalfor" => "if(b.hd='Header' and level = 1,b.urut,null) ",
                    "total_header" => "(select count(*) from master_layout_rl 
		 where tahun = a.tahun and periode = a.periode and hd = 'Header' and level =1)",
                    "main_header" => "if(b.hd='Header' and level = '1',1,0)"
                ))
                ->joinLeft(array("b" => $this->FINANCE->LayoutProfitloss->name()), "a.coa_kode = b.coa_kode")
                ->order(array("abs(b.urut) asc", "b.id"));
        if ($year)
            $select->where("tahun = '$year'");
        if ($month)
            $select->where("periode = '$month'");
        $data = $this->db->fetchAll($select);

        $header = 0;
        foreach ($data as $k => $v) {
            if ($v['main_header'] == 1)
                $header++;
        }
        if ($data[0]['total_header'] == $header)
            $flag_manual_count = false;
        else
            $flag_manual_count = true;

//        $flag_manual_count = true;

        if ($flag_manual_count) {
            $params = array(
                "year" => $year,
                "month" => $month,
                "number_format" => true
            );
            $data = QDC_Finance_ProfitLoss::factory($params)->generateProfitLoss();
        } else {

            $data = QDC_Finance_ProfitLoss::factory(
                    )->getAllTotalGrossOperatingNet(array(
                "perBulan" => $month,
                "perTahun" => $year,
                "total_already_count" => true,
                "dataArray" => $data
            ));
        }

        if (!$export) {

            $this->view->year = $year;
            $this->view->month = $month;
            $this->view->result = $data;
            $this->view->periode = date("F", strtotime($year . "-" . $month . '-' . '01')) . " " . $year;
        } else {

            $title = " " . $month . '-' . $year;
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            foreach ($data as $k => $v) {
                if ($v['total'] == '') {
                    if ($v['grandtotal'] == '')
                        $total = 0;
                    else
                        $total = $v['grandtotal'];
                } else
                    $total = $v['total'];
                $newData[] = array(
                    "No" => $no,
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => ($v['coa_nama'] != '') ? $v['coa_nama'] : $v['text'],
                    "Total" => $total
                );
                $no++;
            }

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Profit & Loss Preview " . $title
                    ))
                    ->setCellFormat(array(
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewDetailProfitLossAction() {
        $year = $this->getRequest()->getParam("tahun");
        $month = $this->getRequest()->getParam("bulan");
        $coa_kode = $this->getRequest()->getParam("coa_kode");

        $where = "coa_kode = '$coa_kode' AND tahun = '$year'";
        if ($month)
            $where .= " AND periode = '$month'";

        $rlDetail = new Finance_Models_AccountingSaldoRLDetail();
        $result = $rlDetail->fetchAll($where);
        if ($result)
            $result = $result->toArray();

        $result = QDC_Finance_Jurnal::factory()->convertToIDR($result);
        $totaldebit = 0;
        $totalcredit = 0;

        foreach ($result as $k => $v) {
            if ($v['is_valueexchange'])
                continue;

            if ($v['credit_conversion'] != 0)
                $totalcredit += floatval($v['credit_conversion']);
            else
                $totalcredit += floatval($v['credit']);

            if ($v['debit_conversion'] != 0)
                $totaldebit += floatval($v['debit_conversion']);
            else
                $totaldebit += floatval($v['debit']);
        }

        $mastercoa = new Finance_Models_MasterCoa();
        $cek = $mastercoa->fetchRow("coa_kode = '$coa_kode'");
        $this->view->dk = $cek['dk'];
        $this->view->coaKode = $coa_kode;
        $this->view->coaNama = $cek['coa_nama'];
        $this->view->periode = date("F", strtotime($year . "-" . $month . '-' . '01')) . " " . $year;
        $this->view->result = $result;
        $this->view->total_debit = $totaldebit;
        $this->view->total_credit = $totalcredit;
    }

    public function gridbpvAction() {
        $trano = $this->getRequest()->getParam("trano");

        $this->view->trano = $trano;
    }

    public function getbpvitemAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");

        $data = $this->FINANCE->BankPaymentVoucher->fetchAll("trano = '$trano' AND deleted=0");

        $bpv = array();
        if ($data) {
            $data = $data->toArray();
            $i = 0;
            foreach ($data as $k => $v) {
                $valKode = $v['valuta'];
                if ($v['holding_tax_status'] == 'Y') {
                    if ($v['grossup_status'] == 'Y') {
                        $total = $v['total_bayar'] + $v['holding_tax_val'];
                    } else
                        $total = $v['total_bayar'];
                } else
                    $total = $v['total_bayar'];

                $bpv[$i] = array(
                    "id" => ($i + 1),
                    "name" => $v['ketin'],
                    "total" => $total,
                    "trano" => $v['trano'],
                    "ref_number" => $v['ref_number'],
                    "val_kode" => $valKode
                );
                $i++;
                if ($v['statusppn'] == 'Y' || $v['tranoppn'] != '') {
                    if ($v['tranoppn'] != '') {
                        $ppn = $this->FINANCE->BankPaymentVoucher->fetchRow("trano = '{$v['tranoppn']}' AND deleted=0");
                        if ($ppn) {
                            $ppn = $ppn->toArray();
                            $ppnval = $ppn['total_bayar'];
                        }
                    } else
                        $ppnval = $v['valueppn'];

                    $ppnRefNumber = $v['ppn_ref_number'];
                    if ($ppnRefNumber != '')
                        $ppnRefNumber = ',Ref Number: <b>' . $ppnRefNumber . "</b>";

                    $bpv[$i] = array(
                        "id" => ($i + 1),
                        "name" => 'PPN/VAT 10%',
                        "total" => $ppnval,
                        "trano" => $v['trano'],
                        "ref_number" => $v['ref_number'],
                        "val_kode" => $valKode
                    );
                    $i++;
                }
                if ($v['holding_tax_status'] == 'Y') {
                    $bpv[$i] = array(
                        "id" => ($i + 1),
                        "name" => $v['holding_tax_text'] . " (WHT)",
                        "total" => -1 * $v['holding_tax_val'],
                        "trano" => $v['trano'],
                        "ref_number" => $v['ref_number'],
                        "val_kode" => $valKode
                    );
                    $i++;
                }
                if ($v['deduction'] > 0) {
                    $bpv[$i] = array(
                        "id" => ($i + 1),
                        "name" => "Deduction",
                        "total" => -1 * $v['deduction'],
                        "trano" => $v['trano'],
                        "ref_number" => $v['ref_number'],
                        "val_kode" => $valKode
                    );
                    $i++;
                }

                $tranoRPI = $v['ref_number'];
            }

            $select = $this->db->select()
                    ->from(array($this->DEFAULT->RequestPaymentInvoiceH->__name()))
                    ->where("trano=?", $tranoRPI)
                    ->where("deleted", 0);
            $cek = $this->db->fetchRow($select);
            if ($cek) {
                if ($cek['with_materai'] == 'Y' && $cek['materai'] > 0) {
                    $bpv[$i] = array(
                        "id" => ($i + 1),
                        "name" => 'Stamp Duty',
                        "total" => $cek['materai'],
                        "trano" => $trano,
                        "ref_number" => $tranoRPI,
                        "val_kode" => 'IDR'
                    );
                    $i++;
                }
            }
        }

        $return['data'] = $bpv;
        $return['success'] = true;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

//    public function arfagingAction()
//    {
//
//    }
//    public function viewarfagingAction()
//    {
//        $start = $this->getRequest()->getParam("start_date");
//        $end = $this->getRequest()->getParam("end_date");
//        $start2 = $this->getRequest()->getParam("start_date2");
//        $end2 = $this->getRequest()->getParam("end_date2");
//        $trano = $this->getRequest()->getParam("trano");
//        $uid = $this->getRequest()->getParam("uid");
//
//        $tmp = array();
//
//        if ($uid == '')
//            $users = $this->FINANCE->ArfAging->getUid();
//        else
//            $users = array(
//                "uid" => $uid
//            );
//
//        if ($trano != '')
//        {
//            $tmp[] = "arf_no = '$trano'";
//        }
//        if ($start != '')
//        {
//            $start = date("Y-m-d",strtotime($start));
//            if ($end == '')
//                $tmp[] = "tgl = '$start'";
//            else
//            {
//                $end = date("Y-m-d",strtotime($end));
//                $tmp[] = "(tgl BETWEEN '$start' AND '$end')";
//            }
//        }
//
//        if ($start2 != '')
//        {
//            $start2 = date("Y-m-d",strtotime($start2));
//            if ($end2 == '')
//                $tmp[] = "tgl = '$start2'";
//            else
//            {
//                $end2 = date("Y-m-d",strtotime($end2));
//                $tmp[] = "(tgl_akhir BETWEEN '$start2' AND '$end2')";
//            }
//        }
//        if (count($tmp) > 0)
//            $where = " AND " . implode(" AND ",$tmp);
//
//        $arfs = array();
//
//        foreach($users as $k => $v)
//        {
//            $uid = $v['uid'];
//            $fetch= $this->FINANCE->ArfAging->fetchAll("uid = '{$v['uid']}' AND statussettle = 0 $where",array("uid ASC","tgl DESC"))->toArray();
//            if ($fetch)
//            {
//                foreach($fetch as $k2 => $v2)
//                {
//                    $cek = $this->DEFAULT->ProcurementArfh->fetchRow("trano = '{$v2['arf_no']}'");
//                    $fetch[$k2]['bt'] = true;
//                    if ($cek)
//                    {
//                        if ($cek['bt'] == 'N')
//                        {
//                            $fetch[$k2]['uid'] = $cek['request'];
//                            $fetch[$k2]['bt'] = false;
//                        }
//                    }
////                    $fetch[$k2]['total_settle'] = 0;
////                    $select = $this->db->select()
////                        ->from(
////                            array(
////                                "a" => $this->DEFAULT->AdvanceSettlementFormD->__name()
////                            ),
////                            array(
////                                "SUM(totalasf) AS totalASF"
////                            )
////                        )
////                        ->joinLeft(array('b'=>'workflow_trans'),
////                        'a.trano = b.item_id',
////                        array(
////                            'final'
////                        ))
////                        ->where("a.arf_no = '{$v2['arf_no']}' AND b.final = 1")
////                        ->group("a.arf_no")
////                        ->having("b.final = 1");
////
////                    $asfd = $this->db->fetchRow($select);
////                    if ($asfd)
////                    {
////                        $fetch[$k2]['total_settle'] = $asfd['totalASF'];
////                    }
//                    $fetch[$k2]['balance'] = $fetch[$k2]['total_bayar'] - ($fetch[$k2]['total_settle'] + $fetch[$k2]['total_settle_cancel']);
//                    $fetch[$k2]['username'] = QDC_User_Ldap::factory(array("uid" => $fetch[$k2]['uid']))->getName();
//                    $arfs[] = $fetch[$k2];
//                }
//            }
//        }
//        $return['data'] = $arfs;
//
//        $json = Zend_Json::encode($return);
//        $this->view->json = $json;
////        $this->getResponse()->setHeader('Content-Type','text/javascript');
////        $this->getResponse()->setBody($json);
//
//    }

    public function generaljournalAction() {
        
    }

    public function viewgeneraljournalAction() {
        $coaKode = $this->_getParam("coa_kode");
        $prjKode = $this->_getParam("prj_kode");
        $month = $this->_getParam("month");
        $export = ($this->_getParam("export") != '') ? true : false;
        if ($coaKode)
            $where[] = "coa_kode = '$coaKode'";

        if ($prjKode)
            $where[] = "prj_kode = '$prjKode'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($where)
            $where = implode(" AND ", $where);

        $res = QDC_Finance_Jurnal::factory()->getAllJurnal($where);
        $result = $res['jurnal'];

        $data = array();
        foreach ($result as $k => $v) {
            $coa = $v['coa_kode'];
            $coaNama = $v['coa_nama'];
            $data[$coa]['coa_kode'] = $coa;
            $data[$coa]['coa_nama'] = $coaNama;

            if ($v['is_valueexchange'])
                continue;

            if ($v['credit_conversion'] != 0)
                $data[$coa]['credit'] += floatval($v['credit_conversion']);
            else
                $data[$coa]['credit'] += floatval($v['credit']);

            if ($v['debit_conversion'] != 0)
                $data[$coa]['debit'] += floatval($v['debit_conversion']);
            else
                $data[$coa]['debit'] += floatval($v['debit']);
        }

        ksort($data);

        $title = "For ";
        if ($prjKode)
            $title .= "Project $prjKode ";
        if ($coaKode)
            $title .= "COA Code $coaKode ";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export) {
            $this->view->title = $title;
            $this->view->data = $data;
            $this->view->total_debit = $res['total_debit'];
            $this->view->total_credit = $res['total_credit'];
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => $v['coa_nama'],
                    "Debit" => $v['debit'],
                    "Credit" => $v['credit']
                );
                $no++;
                $totalCredit += $v['credit'];
                $totalDebit += $v['debit'];
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "COA Code" => "TOTAL",
                "COA Name" => "",
                "Debit" => $totalDebit,
                "Credit" => $totalCredit
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "General Journal " . $title
                    ))
                    ->setCellFormat(array(
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function depreciationassetAction() {
        
    }

    public function depreciationassetitemAction() {
        
    }

    public function depreciationassetperiodeAction() {
        
    }

    public function kasBankAction() {
        
    }

    public function viewKasBankAction() {
        $month = $this->getRequest()->getParam("month");
        $coa_kode = $this->getRequest()->getParam("coa_kode");

        $data = $this->FINANCE->KasBank->fetchAll($month, $coa_kode);

        $this->view->json = Zend_Json::encode($data);
    }

    public function printKasBankAction() {
        $this->_helper->viewRenderer->setNoRender();
        $month = $this->getRequest()->getParam("month");
        $coa_kode = $this->getRequest()->getParam("coa_kode");
        $type_doc = $this->getRequest()->getParam("type_doc");

        $data = $this->FINANCE->KasBank->fetchAll($month, $coa_kode);

        $params = array(
            "month" => $month
        );

        QDC_Jasper_Report::factory(
                array(
                    "reportType" => $type_doc,
                    "arrayData" => $data['data'],
                    "arrayParams" => $params,
                    "fileName" => "kas-bank.jrxml",
                    "outputName" => 'Kas Bank Report - ' . $month,
                    "dataSource" => 'NoDataSource'
                )
        )->generate();
    }

    public function arfAgingAction() {
        
    }

    public function rpcreportAction() {
        
    }

    public function viewArfAgingAction() {
        $uidPerson = $this->getRequest()->getParam("uid");
        $print = ($this->getRequest()->getParam("print") == "true") ? true : false;
        $detail = ($this->getRequest()->getParam("detail") == "true") ? true : false;

        $where = '';

        if ($uidPerson)
            $where .=" AND requester= '$uidPerson'";

        $query = $this->db->prepare("call outstanding_cip(\"$where\",\"\",\"all\")");

        $query->execute();
        $datas = $query->fetchAll();
        $query->closeCursor();

//cek CIP from journal

        foreach ($datas as $key => $val) {
            $total = 0;
            $arf = $val['trano'];
            $query = "select ref_number, sum(coalesce(credit,0))totalcredit,sum(coalesce(debit,0))totaldebit"
                    . " from (select * from procurement_asfdd where "
                    . " arf_no = '$arf' group by trano) b left join"
                    . " accounting_journal a on a.ref_number = b.trano "
                    . " and a.status_doc_cip = 1";

            $data = $this->db->fetchRow($query);
            if ($data) {
                if ($data['totalcredit'] > 0)
                    $total = $data['totalcredit'];
                else
                    $total = $data['totaldebit'];

                if ($total == $val['balance'])
                    unset($datas[$key]);
            }
        }

        //cek cip from oca
        foreach ($datas as $key => $val) {
            $total = 0;
            $arf = $val['trano'];

            $query = "select arf_no, sum(coalesce(total,0))total from procurement_asfdd "
                    . " where arf_no = '$arf' and trano like 'OCA%' ";

            $data = $this->db->fetchRow($query);
            if ($data) {

                $total = $data['total'];

                if ($total == $val['balance'])
                    unset($datas[$key]);
            }
        }

        $data = array();
        if ($datas) {

            foreach ($datas as $key => $val) {
                $tgl = date("Y-m-d", strtotime($val['tgl']));
                $uid = $val['requester'];
                $days = $this->utility->dates_diff($tgl, date("Y-m-d"));

                $data[$uid]['val_kode'] = $val['val_kode'];
                if ($days >= 0 && $days <= 30) {
                    $data[$uid]['0'] += $val['balance'];
                } elseif ($days >= 31 && $days <= 60) {
                    $data[$uid]['30'] += $val['balance'];
                } elseif ($days >= 61 && $days <= 90) {
                    $data[$uid]['60'] += $val['balance'];
                } else {
                    $data[$uid]['90'] += $val['balance'];
                }
            }

            $total = array();
            foreach ($data as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $name = QDC_User_Ldap::factory(array("uid" => $k))->getName();
                $data[$k]['total'] = $totalBalance;
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;
                $data[$k]['name'] = ($name == '') ? $k : $name;
            }

            $this->view->data = $data;
            $this->view->total = $total;

            if ($print) {
                $this->_helper->viewRenderer->setNoRender();
                $newData = array();
                $no = 1;

                if (!$detail) {
                    foreach ($data as $k => $v) {
                        $newData[] = array(
                            "No" => $no,
                            "Person Name" => $v['name'],
                            "Total Due" => $v['balance'],
                            "0 - 30" => $v['0'],
                            "31 - 60" => $v['30'],
                            "61 - 90" => $v['60'],
                            "90+" => $v['90'],
                        );
                        $no++;
                    }
                    $newData[] = array(
                        "No" => '',
                        "Person Name" => "Total",
                        "Total Due" => $total['grandTotal_all'],
                        "0 - 30" => $total['grandTotal_0'],
                        "31 - 60" => $total['grandTotal_30'],
                        "61 - 90" => $total['grandTotal_60'],
                        "90+" => $total['grandTotal_90'],
                    );

                    $title = '';
                    if ($uidPerson != '')
                        $title = " " . QDC_User_Ldap::factory(array("uid" => $uidPerson))->getName();

                    QDC_Adapter_Excel::factory(array(
                                "fileName" => "Outstanding CIP" . $title . "_" . date("dmY")
                            ))
                            ->setCellFormat(array(
                                2 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                3 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                4 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                5 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                6 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                )
                            ))
                            ->write($newData)->toExcel5Stream();
                }
                else {
                    $sql = "select requester from final where requester is not null;";
                    $fetchPerson = $this->db->fetchAll($sql);

                    $person = array();
                    foreach ($fetchPerson as $k => $v) {
                        $uid = $v['requester'];
                        $name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                        $person[$uid] = $name;
                    }

//                    $sql = "select *,(totalarf-totalasf)balance from final where requester is not null;";
//                    $fetch = $this->db->fetchAll($sql);

                    $total = $totalasf = $totalbalance = 0;
                    foreach ($datas as $k => $v) {
                        $uid = $v['requester'];
                        $total += $v['totalarf'];
                        $totalasf += $v['totalasf'];
                        $totalbalance += $v['balance'];
                        $newData[] = array(
                            "No" => $no,
                            "Person Name" => $person[$uid],
                            "Trano ARF" => $v['trano'],
                            "Date ARF" => date("d-m-Y", strtotime($v['tgl'])),
                            "Total ARF" => $v['totalarf'],
                            "Total ASF" => $v['totalasf'],
                            "Balance" => $v['balance'],
                        );
                        $no++;
                    }
                    $newData[] = array(
                        "No" => '',
                        "Person Name" => "Total",
                        "Trano ARF" => '',
                        "Date ARF" => '',
                        "Total ARF" => $total,
                        "Total ASF" => $totalasf,
                        "Balance" => $totalbalance,
                    );

                    $title = '';
                    if ($uidPerson != '')
                        $title = " " . QDC_User_Ldap::factory(array("uid" => $uidPerson))->getName();

                    QDC_Adapter_Excel::factory(array(
                                "fileName" => "Outstanding CIP" . $title . "_" . date("dmY")
                            ))
                            ->setCellFormat(array(
                                4 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                5 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                6 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                )
                            ))
                            ->write($newData)->toExcel5Stream();
                }
            }
        }
    }

    public function detailArfAgingAction() {
        $uid = $this->getRequest()->getParam("uid");
        $start = $this->getRequest()->getParam("start");

        $where_date = " Where requester is not null ";
        

        if ($start != '') {
            if ($start == 0)
                $start2 = 30;
            if ($start == 30) {
                $start = 31;
                $start2 = 60;
            }
            if ($start == 60) {
                $start = 61;
                $start2 = 90;
            }
            if ($start == 90) {
                $start2 = '';
                $start = 91;
            }

            if ($start != '')
                $start = "DATEDIFF(NOW(),tgl) >= $start";
            if ($start2 != '')
                $start2 = " AND DATEDIFF(NOW(),tgl) <= $start2";
            
            if (start)
                $where_date.= " and $start";
            if ($start2)
                $where_date.= " $start2";
        }

        $where = "";

        if ($uid)
            $where .= " AND requester = '$uid'";
        $query = $this->db->prepare("call outstanding_cip(\"$where\",\"$where_date\",\"detail\")");
        $query->execute();
        $fetch = $query->fetchAll();
        $query->closeCursor();

        //manual journal without ASF or Charging

        foreach ($fetch as $key => $val) {
            $total = 0;
            $arf = $val['trano'];
            $query = "select ref_number, sum(coalesce(credit,0))totalcredit,sum(coalesce(debit,0))totaldebit"
                    . " from (select * from procurement_asfdd where "
                    . " arf_no = '$arf' group by trano) b left join"
                    . " accounting_journal a on a.ref_number = b.trano "
                    . " and a.status_doc_cip = 1";

            $data = $this->db->fetchRow($query);
            if ($data) {
                if ($data['totalcredit'] > 0)
                    $total = $data['totalcredit'];
                else
                    $total = $data['totaldebit'];

                if ($total == $val['balance'])
                    unset($fetch[$key]);
            }
        }


        //Salary Deduction 

        foreach ($fetch as $key => $val) {
            $total = 0;
            $arf = $val['trano'];

            $query = "select arf_no, sum(coalesce(total,0))total from procurement_asfdd "
                    . " where arf_no = '$arf' and trano like 'OCA%' ";

            $data = $this->db->fetchRow($query);
            if ($data) {

                $total = $data['total'];

                if ($total == $val['balance'])
                    unset($fetch[$key]);
            }
        }

//        $seenFile = ')&nbsp;&nbsp;(&nbsp;<a class="tooltipDocs" href="#"><img src="/images/icons/fam/report_magnify.png"/><span class="blue">This User has seen all attachment files</span></a>&nbsp;';


        $data = array();
        if ($fetch) {
            $data = $fetch;
            foreach ($fetch as $key => $val) {
                $arf_no = $val['trano'];
                $fetchCek = $this->db->query("select *, if(b.final = '0', 'Document is still being processed', 'Final Approved')statusdoc "
                        . "from asf2 a "
                        . "left join workflow_trans b "
                        . "on a.asfno = b.item_id "
                        . "where arf_no = '$arf_no' "
                        . " group by item_id");
                $fetchCek = $fetchCek->fetchAll();
                $data[$key]['asfno'] = ($fetchCek[0]['asfno'] ? $fetchCek[0]['asfno'] : '-');
                $data[$key]['statusdoc'] = ($fetchCek[0]['statusdoc'] ? $fetchCek[0]['statusdoc'] : 'Not Yet Created');
                
                $asf = $this->db->query("select * from procurement_asfdd where arf_no = '$arf_no' group by trano;"); 
                $asf = $asf->fetchAll();
               
                if ($asf[0]['trano'] && !$fetchCek[0]['item_id'])
                    $data[$key]['statusdoc'] = 'Rejected By System [Requested by User]';

                $tgl = date("Y-m-d", strtotime($val['tgl']));
                $uid = $val['requester'];
                $days = $this->utility->dates_diff($tgl, date("Y-m-d"));
                $data[$key]['days'] = $days;
                $data[$key]['name'] = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                $this->view->name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
            }
        }

        $this->view->data = $data;
    }

    public function agingreportAction() {
        
    }

    public function viewdebitnoteagingreportAction() {
        $sql = "select aging.* from (select a.*,COALESCE(b.total_bayar,0) AS total_bayar,(a.total - COALESCE(b.total_bayar,0)) AS balance
                FROM (SELECT dn.trano,dn.tgl,mc.cus_nama,mc.cus_kode,dn.qty,dn.harga,dn.total,(DATEDIFF(NOW(),dn.tgl)) AS days FROM (finance_nd_reimbursementd dn
                LEFT JOIN master_project mp on dn.prj_kode = mp.prj_kode)
                LEFT JOIN master_customer mc ON mc.cus_kode = mp.cus_kode) a
                LEFT JOIN (select *,sum(total) AS total_bayar
                from finance_payment_reimbursementd_nd group by dn_no) b ON a.trano = b.dn_no) aging
                where aging.total_bayar < (aging.qty*aging.harga)";

        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();

        if ($fetch) {
            foreach ($fetch as $key => $val) {
                $cus_kode = $val['cus_nama'];
                $tgldebitnote = $val['tgl'];
                $arrayTanggal[$cus_kode]['cus_kode'] = $val['cus_kode'];
                $tglDebitNote = $val['tgl'];
//                $days = $this->utility->dates_diff($tglDebitNote,date("Y-m-d"));
                $days = $val['days'];
                if ($days >= 0 && $days <= 30) {
                    $arrayTanggal[$cus_kode]['0'] += $val['balance'];
                } elseif ($days >= 31 && $days <= 60) {
                    $arrayTanggal[$cus_kode]['30'] += $val['balance'];
                } elseif ($days >= 61 && $days <= 90) {
                    $arrayTanggal[$cus_kode]['60'] += $val['balance'];
                } else {
                    $arrayTanggal[$cus_kode]['90'] += $val['balance'];
                }
            }

            $total = array();
            foreach ($arrayTanggal as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;
                $arrayTanggal[$k]['total'] = $totalBalance;
            }
            $this->view->data = $arrayTanggal;
            $this->view->total = $total;
        }
    }

    public function detailagingdebitnoteAction() {
        $cus_kode = $this->getRequest()->getParam('cus_kode');
        $start = $this->getRequest()->getParam('start');

        if ($start != '') {
            if ($start == 0)
                $start2 = 30;
            if ($start == 30) {
                $start = 31;
                $start2 = 60;
            }
            if ($start == 60) {
                $start = 61;
                $start2 = 90;
            }
            if ($start == 90) {
                $start2 = '';
                $start = 91;
            }

            if ($start != '')
                $start = "DATEDIFF(NOW(),aging.tgl) >= $start";
            if ($start2 != '')
                $start2 = " AND DATEDIFF(NOW(),aging.tgl) <= $start2";
            $where = " AND ($start $start2)";
        }

        $sql = "select aging.* from (select a.*,COALESCE(b.total_bayar,0) AS total_bayar,(a.total - COALESCE(b.total_bayar,0)) AS balance
                FROM (SELECT dn.trano,dn.tgl,mc.cus_nama,mc.cus_kode,dn.nama_brg,dn.qty,dn.harga,dn.total FROM (finance_nd_reimbursementd dn
                LEFT JOIN master_project mp on dn.prj_kode = mp.prj_kode)
                LEFT JOIN master_customer mc ON mc.cus_kode = mp.cus_kode) a
                LEFT JOIN (select *,sum(total) AS total_bayar
                from finance_payment_reimbursementd_nd group by dn_no) b ON a.trano = b.dn_no) aging
                where aging.total_bayar < (aging.qty*aging.harga) AND aging.cus_kode = '$cus_kode' $where";

        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();

        if ($fetch) {
            $this->view->data = $fetch;
        }
    }

    public function rpiAgingAction() {
        
    }

    public function rpiAgingOcAction() {
        
    }

    public function rpiAgingTaxAction() {
        
    }

    public function viewRpiAgingAction() {
        $sup = $this->getRequest()->getParam("sup_kode");
        $export = ($this->_getParam("export") != '') ? true : false;
        $detail = ($this->_getParam("detail") == 'true') ? true : false;
        $tglend = $this->getRequest()->getParam("tgl");
        if ($tglend)
            $tglend = date("Y-m-d", strtotime($tglend)) . " 23:59:59";


        if ($sup) {
            $where[] = "sup_kode = '$sup'";
            $where2[] = "sup_kode = '$sup'";
        }
        if ($tglend) {
            $where[] = "tgl <= '$tglend'";
            $where3[] = "tgl <= '$tglend'";
        }
        if ($where)
            $where = implode(" AND ", $where);
        if ($where2)
            $where2 = implode(" AND ", $where2);
        if ($where3)
            $where3 = implode(" AND ", $where3);

        if (!$where)
            $where = null;
        else
            $where = " WHERE $where AND deleted=0";

        if (!$where2)
            $where2 = null;
        else
            $where2 = " WHERE $where2";

        if (!$where3)
            $where3 = null;
        else
            $where3 = " WHERE $where3 AND deleted=0";

        $query = $this->db->prepare("call rpi_aging(\"$where\",\"$where2\",\"$where3\")");
        $query->execute();
        $fetch = $query->fetchAll();
        $query->closeCursor();

        $data = array();
        if ($fetch) {
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $sup = $val['sup_kode'];
                $days = $val['days'];
                $data[$sup]['val_kode'] = $val['val_kode'];
                $data[$sup]['name'] = $val['sup_nama'];
                if ($days <= 0) {
                    $data[$sup]['current'] += $val['balance'];
                } elseif ($days >= 1 && $days <= 30) {
                    $data[$sup]['0'] += $val['balance'];
                } elseif ($days >= 31 && $days <= 60) {
                    $data[$sup]['30'] += $val['balance'];
                } elseif ($days >= 61 && $days <= 90) {
                    $data[$sup]['60'] += $val['balance'];
                } else {
                    $data[$sup]['90'] += $val['balance'];
                }
            }
            $total = array();
            foreach ($data as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $total['grandTotal_current'] += $v['current'];
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;
                $data[$k]['total'] = $totalBalance;
            }
        }

        if (!$export) {
            $this->view->data = $data;
            $this->view->total = $total;
            $this->view->tgl = $tglend;
            $this->view->show = $query;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            if (!$detail) {
                foreach ($data as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "Name" => $v['name'],
                        "Total Due" => $v['total'],
                        "Current" => $v['current'],
                        "1-30" => $v['0'],
                        "31-60" => $v['30'],
                        "61-90" => $v['60'],
                        "90+" => $v['90']
                    );
                    $no++;
                }
                //Total...
                $newData[] = array(
                    "No" => '',
                    "Name" => '',
                    "Total Due" => $total['grandTotal_all'],
                    "Current" => $total['grandTotal_current'],
                    "1-30" => $total['grandTotal_0'],
                    "31-60" => $total['grandTotal_30'],
                    "61-90" => $total['grandTotal_60'],
                    "90+" => $total['grandTotal_90']
                );


                QDC_Adapter_Excel::factory(array(
                            "fileName" => "AP Aging"
                        ))
                        ->setCellFormat(array(
                            2 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            3 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            4 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            5 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            6 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            7 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            )
                        ))
                        ->write($newData)->toExcel5Stream();
            } else {
                $total = $totalPay = $totalBal = 0;
                foreach ($fetch as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "Supplier Name" => $v['sup_nama'],
                        "Supplier Code" => $v['sup_kode'],
                        "Trano RPI" => $v['trano'],
                        "Total RPI" => $v['total'],
                        "Total Payment" => $v['total_bayar'],
                        "Balance" => $v['balance']
                    );

                    $total += $v['total'];
                    $totalPay += $v['total_bayar'];
                    $totalBal += $v['balance'];
                    $no++;
                }
                //Total...
                $newData[] = array(
                    "No" => '',
                    "Supplier Name" => '',
                    "Supplier Code" => '',
                    "Trano RPI" => '',
                    "Total RPI" => $total,
                    "Total Payment" => $totalPay,
                    "Balance" => $totalBal
                );


                QDC_Adapter_Excel::factory(array(
                            "fileName" => "AP Aging"
                        ))
                        ->setCellFormat(array(
                            4 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            5 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            6 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            )
                        ))
                        ->write($newData)->toExcel5Stream();
            }
        }
    }

    public function viewRpiAgingOcAction() {

        $sup = $this->getRequest()->getParam("sup_kode");
        $export = ($this->_getParam("export") != '') ? true : false;
        $detail = ($this->_getParam("detail") == 'true') ? true : false;
        $tglend = $this->getRequest()->getParam("tgl");
        $currencyP = $export ? $this->_getParam("currency") : $this->getRequest()->getParam("currency");

        $currency = $currencyP == 'USD' ? 'USD' : 'IDR';

        if ($tglend)
            $tglend = date("Y-m-d", strtotime($tglend)) . " 23:59:59";


        if ($sup) {
            $where[] = "sup_kode = '$sup'";
            $where2[] = "sup_kode = '$sup'";
        }
        if ($tglend) {
            $where[] = "tgl <= '$tglend'";
            $where3[] = "tgl <= '$tglend'";
        }
        if ($where)
            $where = implode(" AND ", $where);
        if ($where2)
            $where2 = implode(" AND ", $where2);
        if ($where3)
            $where3 = implode(" AND ", $where3);

        if (!$where)
            $where = null;
        else
            $where = " WHERE $where";

        if (!$where2)
            $where2 = null;
        else
            $where2 = " WHERE $where2";

        if (!$where3)
            $where3 = null;
        else
            $where3 = " WHERE $where3";



        $query = $this->db->prepare("call rpi_aging_oc( \"$where\",\"$where2\",\"$where3\",\"$currency\")");
        $query->execute();
        $fetch = $query->fetchAll();
        $query->closeCursor();

        $data = array();
        if ($fetch) {
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $sup = $val['sup_kode'];
                $days = $val['days'];
                $data[$sup]['val_kode'] = $val['val_kode'];
                $data[$sup]['name'] = $val['sup_nama'];
                if ($days <= 0) {
                    $data[$sup]['current'] += $val['balance'];
                } elseif ($days >= 1 && $days <= 30) {
                    $data[$sup]['0'] += $val['balance'];
                } elseif ($days >= 31 && $days <= 60) {
                    $data[$sup]['30'] += $val['balance'];
                } elseif ($days >= 61 && $days <= 90) {
                    $data[$sup]['60'] += $val['balance'];
                } else {
                    $data[$sup]['90'] += $val['balance'];
                }
            }
            $total = array();
            foreach ($data as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $total['grandTotal_current'] += $v['current'];
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;
                $data[$k]['total'] = $totalBalance;
            }
        }

        if (!$export) {
            $this->view->data = $data;
            $this->view->total = $total;
            $this->view->tgl = $tglend;
            $this->view->show = $query;
            $this->view->currency = $currency;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            if (!$detail) {
                foreach ($data as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "Name" => $v['name'],
                        "Total Due" => $v['total'],
                        "Current" => $v['current'],
                        "1-30" => $v['0'],
                        "31-60" => $v['30'],
                        "61-90" => $v['60'],
                        "90+" => $v['90']
                    );
                    $no++;
                }
                //Total...
                $newData[] = array(
                    "No" => '',
                    "Name" => '',
                    "Total Due" => $total['grandTotal_all'],
                    "Current" => $total['grandTotal_current'],
                    "1-30" => $total['grandTotal_0'],
                    "31-60" => $total['grandTotal_30'],
                    "61-90" => $total['grandTotal_60'],
                    "90+" => $total['grandTotal_90']
                );


                QDC_Adapter_Excel::factory(array(
                            "fileName" => "AP Aging"
                        ))
                        ->setCellFormat(array(
                            2 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            3 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            4 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            5 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            6 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            7 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            )
                        ))
                        ->write($newData)->toExcel5Stream();
            } else {
                $total = $totalPay = $totalBal = 0;
                foreach ($fetch as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "Supplier Name" => $v['sup_nama'],
                        "Supplier Code" => $v['sup_kode'],
                        "Trano RPI" => $v['trano'],
                        "Total RPI" => $v['total'],
                        "Total Payment" => $v['total_bayar'],
                        "Balance" => $v['balance']
                    );

                    $total += $v['total'];
                    $totalPay += $v['total_bayar'];
                    $totalBal += $v['balance'];
                    $no++;
                }
                //Total...
                $newData[] = array(
                    "No" => '',
                    "Supplier Name" => '',
                    "Supplier Code" => '',
                    "Trano RPI" => '',
                    "Total RPI" => $total,
                    "Total Payment" => $totalPay,
                    "Balance" => $totalBal
                );


                QDC_Adapter_Excel::factory(array(
                            "fileName" => "AP Aging"
                        ))
                        ->setCellFormat(array(
                            4 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            5 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            6 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            )
                        ))
                        ->write($newData)->toExcel5Stream();
            }
        }
    }

    public function detailRpiAgingAction() {
        $sup = $this->getRequest()->getParam("sup_kode");
        $start = $this->getRequest()->getParam("start");
        $export = ($this->_getParam("export") != '') ? true : false;
        $tempstart = $start;
        $tglend = $this->getRequest()->getParam("tgl");
        if ($tglend)
            $tglend = date("Y-m-d", strtotime($tglend)) . " 23:59:59";

        if ($start != '') {

            if ($start == 0)
                $start2 = 30;
            if ($start == 30) {
                $start = 31;
                $start2 = 60;
            }
            if ($start == 60) {
                $start = 61;
                $start2 = 90;
            }
            if ($start == 90) {
                $start2 = '';
                $start = 91;
            }


            if ($start != '')
                if ($start == 'current')
                    $start = "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) <= 0";
                else
                    $start = "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) >= $start";
            if ($start2 != '')
                $start2 = " AND DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) <= $start2";
            $where = "$start $start2";
        }

        $subselect = $this->db->select()
                ->from(array($this->DEFAULT->RequestPaymentInvoiceH->__name()), array(
            "trano",
            "total_ppn" => "(IF(val_kode != 'IDR',(if(statusppn = 'Y' OR ppn > 0, 
                                        (total+round(ppn,2)),round(total,2))*rateidr),
                                        if(statusppn = 'Y' or ppn > 0, (total+round(ppn,2)),round(total,2))))
                                          -
                                        IF(totalwht > 0, totalwht, 0)
                                          -
                                        IF(total_deduction > 0, total_deduction, 0)",
            "total" => "round(total,2)- IF(totalwht > 0, totalwht, 0)",
            "val_kode",
            "prj_kode",
            "prj_nama",
            "sup_kode",
            "po_no",
            "ppn_value" => "if(statusppn ='Y' or ppn > 0, ppn, 0)",
            "tgl",
            "rateidr"
        ));

        if ($sup)
            $subselect->where("sup_kode = '$sup' AND deleted=0");
        if ($tglend)
            $subselect->where("tgl <= '$tglend' AND deleted=0");

        $fetch = $this->db->fetchAll($subselect);

        $subselect3 = $this->db->select()
                ->from(array("a" => $subselect))
                ->joinLeft(array("b" => $this->DEFAULT->ProcurementPoh->__name()), "a.po_no = b.trano", array(
            "top"
        ));

        $subselect4 = $this->db->select()
                ->from(array($this->FINANCE->PaymentRPI->__name()), array(
                    "doc_trano",
                    "total_bayar" => "SUM(total_bayar)",
                    "val_kode"
                ))
                ->group(array("doc_trano"));
        if ($tglend)
            $subselect4->where("tgl <= '$tglend' AND deleted=0");

        $subselect5 = $this->db->select()
                ->from(array("c" => $subselect3))
                ->joinLeft(array("d" => $subselect4), "c.trano = d.doc_trano", array(
            "total_bayar" => "(IF(d.val_kode != 'IDR',(c.rateidr*coalesce(total_bayar,0)),coalesce(total_bayar,0)))",
            "total_bayar_ori" => "coalesce(total_bayar,0)"
        ));

        $subselect6 = $this->db->select()
                ->from(array("x" => $subselect5), array(
                    "trano",
                    "total",
                    "ppn_value",
                    "total_ppn",
                    "tgl",
                    "prj_kode",
                    "prj_nama",
                    "top",
                    "val_kode",
                    "sup_kode",
                    "tgl",
                    "days" => "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY))",
                    "total_bayar",
                    "balance" => "(total-total_bayar_ori)"
                ))
                ->joinLeft(array("y" => $this->DEFAULT->MasterSuplier->__name()), "x.sup_kode = y.sup_kode", array(
                    "sup_nama"
                ))
                ->where("total_bayar < total_ppn")
                ->order(array("y.sup_nama", "trano DESC"));

        //------------------------------------------

        if ($where)
            $subselect6->where($where);

        $fetch = $this->db->fetchAll($subselect6);
        $data = array();
        if ($fetch) {
            $data = $fetch;
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $sup = $val['sup_kode'];
                $days = $val['days'];
                $data[$key]['days'] = $days;
                $data[$key]['name'] = $this->DEFAULT->MasterSuplier->getName($sup);
                $this->view->name = $this->DEFAULT->MasterSuplier->getName($sup);
            }
        }

        if (!$export) {
            $this->view->data = $data;
            $this->view->supKode = $sup;
            $this->view->start = $tempstart;
            $this->view->tgl = $tglend;
        } else {

            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            $valueIDR = '';
            $valueUSD = '';
            $ppnIDR = '';
            $ppnUSD = '';

            foreach ($data as $k => $v) {
                if ($v['val_kode'] != 'IDR') {
                    $valueUSD = $v['balance'];
                    $ppnUSD = $v['ppn_value'];
                } else {
                    $valueIDR = $v['balance'];
                    $ppnIDR = $v['ppn_value'];
                }

                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date('d - M - Y', strtotime($v['tgl'])),
                    "Overdue (Days)" => $v['days'],
                    "Value (IDR)" => number_format($valueIDR, 2),
                    "Tax (IDR)" => number_format($ppnIDR, 2),
                    "Value (USD)" => number_format($valueUSD, 2),
                    "Tax (USD)" => number_format($ppnUSD, 2)
                );
                $no++;

                $valueIDR = '';
                $valueUSD = '';
                $ppnIDR = '';
                $ppnUSD = '';
            }


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "AP Aging Detail"
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewRpiAgingTaxAction() {
        $sup = $this->getRequest()->getParam("sup_kode");
        $export = ($this->_getParam("export") != '') ? true : false;
        $detail = ($this->_getParam("detail") == 'true') ? true : false;
        $tglend = $this->getRequest()->getParam("tgl");
        if ($tglend)
            $tglend = date("Y-m-d", strtotime($tglend)) . " 23:59:59";

        if ($sup) {
            $where[] = "sup_kode = '$sup'";
            $where2[] = "sup_kode = '$sup'";
        }
        if ($tglend) {
            $where[] = "tgl <= '$tglend'";
            $where3[] = "tgl <= '$tglend'";
        }
        if ($where)
            $where = implode(" AND ", $where);
        if ($where2)
            $where2 = implode(" AND ", $where2);
        if ($where3)
            $where3 = implode(" AND ", $where3);

        if (!$where)
            $where = null;
        else
            $where = " WHERE $where";

        if (!$where2)
            $where2 = null;
        else
            $where2 = " WHERE $where2";

        if (!$where3)
            $where3 = null;
        else
            $where3 = " WHERE $where3";



        $query = $this->db->prepare("call rpi_aging(\"$where\",\"$where2\",\"$where3\")");
        $query->execute();
        $fetch = $query->fetchAll();
        $query->closeCursor();

        $data = array();
        if ($fetch) {
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $sup = $val['sup_kode'];
                $days = $val['days'];
                $data[$sup]['val_kode'] = $val['val_kode'];
                $data[$sup]['name'] = $val['sup_nama'];
                if ($days <= 0) {
                    $data[$sup]['current'] += $val['balance'];
                } elseif ($days >= 1 && $days <= 30) {
                    $data[$sup]['0'] += $val['balance'];
                } elseif ($days >= 31 && $days <= 60) {
                    $data[$sup]['30'] += $val['balance'];
                } elseif ($days >= 61 && $days <= 90) {
                    $data[$sup]['60'] += $val['balance'];
                } else {
                    $data[$sup]['90'] += $val['balance'];
                }
            }
            $total = array();
            foreach ($data as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $total['grandTotal_current'] += $v['current'];
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;
                $data[$k]['total'] = $totalBalance;
            }
        }

        if (!$export) {
            $this->view->data = $data;
            $this->view->total = $total;
            $this->view->tgl = $tglend;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            if (!$detail) {
                foreach ($data as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "Name" => $v['name'],
                        "Total Due" => $v['total'],
                        "Current" => $v['current'],
                        "1-30" => $v['0'],
                        "31-60" => $v['30'],
                        "61-90" => $v['60'],
                        "90+" => $v['90']
                    );
                    $no++;
                }
                //Total...
                $newData[] = array(
                    "No" => '',
                    "Name" => '',
                    "Total Due" => $total['grandTotal_all'],
                    "Current" => $total['grandTotal_current'],
                    "1-30" => $total['grandTotal_0'],
                    "31-60" => $total['grandTotal_30'],
                    "61-90" => $total['grandTotal_60'],
                    "90+" => $total['grandTotal_90']
                );


                QDC_Adapter_Excel::factory(array(
                            "fileName" => "AP Aging"
                        ))
                        ->setCellFormat(array(
                            2 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            3 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            4 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            5 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            6 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            7 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            )
                        ))
                        ->write($newData)->toExcel5Stream();
            } else {
                $total = $totalPay = $totalBal = 0;
                foreach ($fetch as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "Supplier Name" => $v['sup_nama'],
                        "Supplier Code" => $v['sup_kode'],
                        "Trano RPI" => $v['trano'],
                        "Total RPI" => $v['total'],
                        "Total Payment" => $v['total_bayar'],
                        "Balance" => $v['balance']
                    );

                    $total += $v['total'];
                    $totalPay += $v['total_bayar'];
                    $totalBal += $v['balance'];
                    $no++;
                }
                //Total...
                $newData[] = array(
                    "No" => '',
                    "Supplier Name" => '',
                    "Supplier Code" => '',
                    "Trano RPI" => '',
                    "Total RPI" => $total,
                    "Total Payment" => $totalPay,
                    "Balance" => $totalBal
                );


                QDC_Adapter_Excel::factory(array(
                            "fileName" => "AP Aging"
                        ))
                        ->setCellFormat(array(
                            4 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            5 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            6 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            )
                        ))
                        ->write($newData)->toExcel5Stream();
            }
        }
    }

    public function detailRpiAgingTaxAction() {
        $sup = $this->getRequest()->getParam("sup_kode");
        $start = $this->getRequest()->getParam("start");
        $export = ($this->_getParam("export") != '') ? true : false;
        $tempstart = $start;
        $tglend = $this->getRequest()->getParam("tgl");
        if ($tglend)
            $tglend = date("Y-m-d", strtotime($tglend)) . " 23:59:59";

        if ($start != '') {

            if ($start == 0)
                $start2 = 30;
            if ($start == 30) {
                $start = 31;
                $start2 = 60;
            }
            if ($start == 60) {
                $start = 61;
                $start2 = 90;
            }
            if ($start == 90) {
                $start2 = '';
                $start = 91;
            }


            if ($start != '')
                if ($start == 'current')
                    $start = "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) <= 0";
                else
                    $start = "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) >= $start";
            if ($start2 != '')
                $start2 = " AND DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) <= $start2";
            $where = "$start $start2";
        }

        $subselect = $this->db->select()
                ->from(array($this->DEFAULT->RequestPaymentInvoiceH->__name()), array(
            "trano",
            "total_ppn" => "(IF(val_kode != 'IDR',(if(statusppn = 'Y' or ppn > 0, 
                                        (total+round(ppn,2)),round(total,2))*rateidr),
                                        if(statusppn = 'Y' or ppn > 0, (total+round(ppn,2)),round(total,2))))  
                                        -
                                        IF(totalwht > 0, totalwht, 0)
                                        - 
                                        IF(total_deduction > 0, total_deduction, 0)",
            "total" => "round(total,2)- IF(totalwht > 0, totalwht, 0)",
            "val_kode",
            "prj_kode",
            "prj_nama",
            "sup_kode",
            "po_no",
            "ppn_value" => "(IF(val_kode = 'USD',"
            . "(if(statusppn ='Y' or ppn > 0, (ppn*rateidr), 0))"
            . ","
            . "(if(statusppn ='Y' or ppn > 0, ppn, 0))))",
            "tgl",
            "rateidr"
        ));

        if ($sup)
            $subselect->where("sup_kode = '$sup'");
        if ($tglend)
            $subselect->where("tgl <= '$tglend'");

        $fetch = $this->db->fetchAll($subselect);
        $subselect3 = $this->db->select()
                ->from(array("a" => $subselect))
                ->joinLeft(array("b" => $this->DEFAULT->ProcurementPoh->__name()), "a.po_no = b.trano", array(
            "top"
        ));

        $subselect4 = $this->db->select()
                ->from(array($this->FINANCE->PaymentRPI->__name()), array(
                    "doc_trano",
                    "total_bayar" => "SUM(total_bayar)",
                    "val_kode"
                ))
                ->group(array("doc_trano"));
        if ($tglend)
            $subselect4->where("tgl <= '$tglend'");

        $subselect5 = $this->db->select()
                ->from(array("c" => $subselect3))
                ->joinLeft(array("d" => $subselect4), "c.trano = d.doc_trano", array(
            "total_bayar" => "(IF(d.val_kode != 'IDR',(c.rateidr*coalesce(total_bayar,0)),coalesce(total_bayar,0)))",
            "total_bayar_ori" => "coalesce(total_bayar,0)"
        ));


        $subselect6 = $this->db->select()
                ->from(array("x" => $subselect5), array(
                    "trano",
                    "total",
                    "ppn_value",
                    "total_ppn",
                    "tgl",
                    "top",
                    "val_kode",
                    "sup_kode",
                    "tgl",
                    "days" => "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY))",
                    "total_bayar",
                    "balance" => "(total-total_bayar_ori)"
                ))
                ->joinLeft(array("y" => $this->DEFAULT->MasterSuplier->__name()), "x.sup_kode = y.sup_kode", array(
                    "sup_nama"
                ))
                ->where("total_bayar < total_ppn")
                ->order(array("y.sup_nama", "trano DESC"));

        //------------------------------------------

        if ($where)
            $subselect6->where($where);

        $fetch = $this->db->fetchAll($subselect6);

        $data = array();
        if ($fetch) {
            $data = $fetch;
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $sup = $val['sup_kode'];
//                $days = $this->utility->dates_diff($tgl,date("Y-m-d"));
                $days = $val['days'];
                $data[$key]['days'] = $days;
                $data[$key]['name'] = $this->DEFAULT->MasterSuplier->getName($sup);
                $this->view->name = $this->DEFAULT->MasterSuplier->getName($sup);
            }
        }

        if (!$export) {
            $this->view->data = $data;
            $this->view->supKode = $sup;
            $this->view->start = $tempstart;
            $this->view->tgl = $tglend;
        } else {

            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            $valueIDR = '';
            $valueUSD = '';
            $ppnIDR = '';
            $ppnUSD = '';

            foreach ($data as $k => $v) {
                if ($v['val_kode'] != 'IDR') {
                    $valueUSD = $v['balance'];
                    $ppnUSD = $v['ppn_value'];
                } else {
                    $valueIDR = $v['balance'];
                    $ppnIDR = $v['ppn_value'];
                }

                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date('d - M - Y', strtotime($v['tgl'])),
                    "Overdue (Days)" => $v['days'],
                    "Value (IDR)" => number_format($valueIDR, 2),
                    "Tax (IDR)" => number_format($ppnIDR, 2),
                    "Value (USD)" => number_format($valueUSD, 2),
                    "Tax (USD)" => number_format($ppnUSD, 2)
                );
                $no++;

                $valueIDR = '';
                $valueUSD = '';
                $ppnIDR = '';
                $ppnUSD = '';
            }



            QDC_Adapter_Excel::factory(array(
                        "fileName" => "AP Aging Detail"
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function remAgingAction() {
        
    }

    public function viewRemAgingAction() {
        $cus = $this->getRequest()->getParam("cus_kode");
        
        if ($cus!=''){
             $select = "DROP TEMPORARY TABLE IF EXISTS  rem;
                    CREATE TEMPORARY TABLE rem
                    SELECT b.trano AS trano_rem, b.cus_kode, DATEDIFF(NOW(),a.tgl) AS days, a.trano, sum(a.total) as total, c.prem_no, a.tgl, b.prj_kode,a.val_kode
                    FROM erpdb.finance_payment_reimbursement a
                    LEFT JOIN erpdb.procurement_reimbursementh b ON (a.rem_no = b.trano)
                    LEFT JOIN erpdb.finance_nd_reimbursement c ON (a.trano = c.prem_no)
                    WHERE c.prem_no IS NULL AND b.cus_kode='$cus'
                    GROUP BY b.trano ORDER BY tgl DESC;";
        }else{    
        $select = "DROP TEMPORARY TABLE IF EXISTS  rem;
                    CREATE TEMPORARY TABLE rem
                    SELECT b.trano AS trano_rem, b.cus_kode, DATEDIFF(NOW(),a.tgl) AS days, a.trano, sum(a.total) as total, c.prem_no, a.tgl, b.prj_kode,a.val_kode
                    FROM erpdb.finance_payment_reimbursement a
                    LEFT JOIN erpdb.procurement_reimbursementh b ON (a.rem_no = b.trano)
                    LEFT JOIN erpdb.finance_nd_reimbursement c ON (a.trano = c.prem_no)
                    WHERE c.prem_no IS NULL
                    GROUP BY b.trano ORDER BY tgl DESC;";
        }
        $this->db->query($select);
        $sql = "SELECT * FROM rem;";
        $hasil = $this->db->query($sql);
        $fetch = $hasil->fetchAll();
        $data = array();
        if ($fetch) {
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $customer = $this->DEFAULT->MasterCustomer->getCustomerFromCuskode($val['cus_kode']);

                $cus = $customer['cus_kode'];
//                $days = $this->utility->dates_diff($tgl,date("Y-m-d"));
                $days = $val['days'];
                $data[$cus]['val_kode'] = $val['val_kode'];
                if ($days >= 0 && $days <= 30) {
                    $data[$cus]['0'] += $val['total'];
                } elseif ($days >= 31 && $days <= 60) {
                    $data[$cus]['30'] += $val['total'];
                } elseif ($days >= 61 && $days <= 90) {
                    $data[$cus]['60'] += $val['total'];
                } else {
                    $data[$cus]['90'] += $val['total'];
                }
            }

            $total = array();
            foreach ($data as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $data[$k]['total'] = $totalBalance;
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;
                $data[$k]['name'] = $this->DEFAULT->MasterCustomer->getName($k);
            }
        }

        $this->view->data = $data;
        $this->view->total = $total;
    }

    public function detailRemAgingAction() {
        $cus = $this->getRequest()->getParam("cus_kode");
        $start = $this->getRequest()->getParam("start");
        
        if ($start != '') {
            if ($start == 0)
                $start2 = 30;
            if ($start == 30) {
                $start = 31;
                $start2 = 60;
            }
            if ($start == 60) {
                $start = 61;
                $start2 = 90;
            }
            if ($start == 90) {
                $start2 = '';
                $start = 91;
            }

            if ($start != '')
                $start = "AND DATEDIFF(NOW(),a.tgl) >= $start";
            if ($start2 != '')
                $start2 = " AND DATEDIFF(NOW(),a.tgl) <= $start2";
            
            $where = "$start $start2";
        }

       $cus_null='""';
       if($cus !=''){
          
                     $select = "DROP TEMPORARY TABLE IF EXISTS  rem;
                    CREATE TEMPORARY TABLE rem
                    SELECT b.trano AS trano_rem, b.cus_kode, DATEDIFF(NOW(),a.tgl) AS days, a.trano, sum(a.total) as total, c.prem_no, a.tgl, b.prj_kode,a.val_kode
                    FROM erpdb.finance_payment_reimbursement a
                    LEFT JOIN erpdb.procurement_reimbursementh b ON (a.rem_no = b.trano)
                    LEFT JOIN erpdb.finance_nd_reimbursement c ON (a.trano = c.prem_no)
                    WHERE c.prem_no IS NULL and b.cus_kode='$cus' $where
                    GROUP BY a.trano ORDER BY tgl DESC;";
            
        }else{ 
                     $select = "DROP TEMPORARY TABLE IF EXISTS  rem;
                    CREATE TEMPORARY TABLE rem
                    SELECT b.trano AS trano_rem, b.cus_kode, DATEDIFF(NOW(),a.tgl) AS days, a.trano, sum(a.total) as total, c.prem_no, a.tgl, b.prj_kode,a.val_kode
                    FROM erpdb.finance_payment_reimbursement a
                    LEFT JOIN erpdb.procurement_reimbursementh b ON (a.rem_no = b.trano)
                    LEFT JOIN erpdb.finance_nd_reimbursement c ON (a.trano = c.prem_no)
                    WHERE c.prem_no IS NULL and  b.cus_kode='$cus_null' $where
                    GROUP BY a.trano ORDER BY tgl DESC;";
        }
        $this->db->query($select);
        $sql = "SELECT * FROM rem ORDER BY tgl;";
        $hasil = $this->db->query($sql);
        $fetch = $hasil->fetchAll();
                
        $data = array();
        if ($fetch) {
            $data = $fetch;
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $prj = $val['prj_kode'];
                $customer = $this->DEFAULT->MasterCustomer->getCustomerFromCuskode($cus);
                $cus = $customer['cus_kode'];
//                $days = $this->utility->dates_diff($tgl,date("Y-m-d"));
                $days = $val['days'];
                $data[$key]['days'] = $days;
                $data[$key]['name'] = $this->DEFAULT->MasterCustomer->getName($cus);
                $this->view->name = $data[$key]['name'];
            }
        }

        $this->view->data = $data;
    }

    public function invoiceAgingAction() {
        
    }

    public function invoiceAgingOcAction() {
        
    }

    public function invoiceAgingTaxAction() {
        
    }

    public function viewInvoiceAgingOcAction() {

        $export = $this->_getParam("export");
        $cus = $export == '' ? $this->getRequest()->getParam("cus_kode") : $this->_getParam("cus_kode");
        $tglend = $export == '' ? $this->getRequest()->getParam("tgl") : $this->_getParam("tanggal");
        $currencyP = $export == '' ? $this->getRequest()->getParam("currency") : $this->_getParam("currency");

        $currency = $currencyP == 'USD' ? 'USD' : 'IDR';

        if ($tglend)
            $tglend = date("Y-m-d", strtotime($tglend)) . " 23:59:59";

        $export = ($this->_getParam("export") != '') ? true : false;
        
        $query="DROP TEMPORARY TABLE IF EXISTS invh;
                CREATE TEMPORARY TABLE invh
                SELECT trano, tgl, SUM(total) AS total, rateidr,SUM(total_invoice) AS total_invoice,val_kode,cus_kode,prj_kode,sit_kode,prj_nama,top 
                FROM erpdb.finance_invoice WHERE cus_kode !='MIT' GROUP BY trano";
        $this->db->query($query);
        
        $query="DROP TEMPORARY TABLE IF EXISTS invd;
                CREATE TEMPORARY TABLE invd
                SELECT trano,val_kode,statusppn,rateidr,SUM(deduction) AS deduction 
                FROM erpdb.finance_invoiced WHERE cus_kode !='MIT' GROUP BY trano";
        $this->db->query($query);

        $subselect = $this->db->select()
                        //->from(array("a" => $this->FINANCE->Invoice->name()), array(
                        ->from(array("a" => "invh"), array(
                            "trano",
                            "tgl",
                            //"total" => "(IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
                            //            (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                            //            if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total)))",
                            "total" => "IF(b.deduction < 0, IF(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total),a.total_invoice)",
                            "total_ori" =>  "IF(b.deduction < 0, a.total,a.total_invoice) ",
                            "val_kode",
                            "cus_kode",
                            "prj_kode",
                            "prj_nama",
                            "top",
                            "rateidr"
                        //))->joinLeft(array("b" => $this->FINANCE->InvoiceDetail->name()), "a.trano = b.trano", array(
                            ))->joinLeft(array("b" => "invd"), "a.trano = b.trano", array(
                    "trano as trano_detail"
                ))->joinLeft(array("c" => $this->FINANCE->AccountingCancelInvoice->__name()), "a.trano = c.invoice_no", array());

        if ($cus)
            $subselect->where("a.cus_kode = '$cus'");
        if ($tglend)
            $subselect->where("a.tgl <= '$tglend'");
        $subselect->where("(a.total - coalesce(c.total_cancel,0)) > 0");


        $subselect2 = $this->db->select()
                ->from(array($this->FINANCE->PaymentInvoice->name()), array(
                    "total_payment" => "coalesce(sum(total),0)",
                    "inv_no",
                    "val_kode",
                ))
                ->group(array("inv_no", "val_kode"));
        if ($cus)
            $subselect2->where("cus_kode = '$cus'");

        $subselect3 = $this->db->select()
                ->from(array("a" => $subselect))
                ->joinLeft(array("b" => $subselect2), "a.trano = b.inv_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            //"total_payment" => "(IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0)))",
            "total_payment" => "coalesce(total_payment,0)",
        ));


        $subselectDN = $this->db->select()
                        ->from(array("a" => $this->FINANCE->NDreimbursHeader->__name()), array(
                            "trano",
                            "tgl",
                            //"total" => "(IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
                            //            (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                            //            if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total)))",
                            "total" => "IF(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total)",
                            "total_ori" => "total",
                            "val_kode",
                            "cus_kode",
                            "prj_kode",
                            "prj_nama",
                            "top",
                            "rateidr"
                        ))->joinLeft(array("b" => $this->FINANCE->NDreimbursDetail->__name()), "a.trano = b.trano", array(
            "trano as trano_detail"
        ));

        if ($cus)
            $subselectDN->where("a.cus_kode = '$cus'");
        if ($tglend)
            $subselectDN->where("a.tgl <= '$tglend'");

        $subselect2DN = $this->db->select()
                ->from(array($this->FINANCE->paymentNDreimbursH->__name()), array(
                    "total_payment" => "coalesce(sum(total),0)",
                    "dn_no",
                    "val_kode",
                ))
                ->group(array("dn_no", "val_kode"));
        if ($cus)
            $subselect2DN->where("cus_kode = '$cus'");

        $subselect3DN = $this->db->select()
                ->from(array("a" => $subselectDN))
                ->joinLeft(array("b" => $subselect2DN), "a.trano = b.dn_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            //"total_payment" => "(IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0)))",
            "total_payment" => "coalesce(total_payment,0)",
        ));
        $subselect3_ = (string) $subselect3;
        $subselect3DN_ = (string) $subselect3DN;

        $fetch = $this->db->query("drop temporary table if exists aging_oc;");
        $fetch = $this->db->query("create temporary table aging_oc
                    select trano, cus_kode, prj_kode,prj_nama, total,total_ori,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,total_payment_ori,(total-total_payment)balance 
                    from ($subselect3_)a
                    where total_payment < total
                    order by cus_kode;");
        $fetch = $this->db->query("insert into aging_oc  select trano, cus_kode, prj_kode,prj_nama, total,total_ori,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,total_payment_ori,(total-total_payment)balance 
                    from ($subselect3DN_)a
                    where total_payment < total
                    order by cus_kode; ");

        $fetch = $this->db->fetchAll("select * from aging_oc WHERE val_kode='$currency';");

        $data = array();
        if ($fetch) {
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $cus = $val['cus_kode'];

                if ($cus == '') {
                    $cus = $val['prj_kode'];
                    $data[$cus]['cus_empty'] = true;
                } else
                    $data[$cus]['cus_empty'] = false;

                $days = $val['days'];
                $data[$cus]['val_kode'] = $val['val_kode'];
                if ($days <= 0) {
                    $data[$cus]['current'] += $val['balance'];
                } elseif ($days >= 1 && $days <= 30) {
                    $data[$cus]['0'] += $val['balance'];
                } elseif ($days >= 31 && $days <= 60) {
                    $data[$cus]['30'] += $val['balance'];
                } elseif ($days >= 61 && $days <= 90) {
                    $data[$cus]['60'] += $val['balance'];
                } else {
                    $data[$cus]['90'] += $val['balance'];
                }
            }

            $total = array();
            foreach ($data as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $data[$k]['total'] = $totalBalance;
                $total['grandTotal_current'] += $v['current'];
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;

                if ($this->DEFAULT->MasterCustomer->getName($k) != '')
                    $data[$k]['name'] = $this->DEFAULT->MasterCustomer->getName($k);
                else
                    $data[$k]['name'] = $k;
            }
        }

        if (!$export) {
            $this->view->data = $data;
            $this->view->total = $total;
            $this->view->tgl = $tglend;
            $this->view->currency = $currency;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Name" => $v['name'],
                    "Total Due" => $v['total'],
                    "Current" => $v['current'],
                    "1-30" => $v['0'],
                    "31-60" => $v['30'],
                    "61-90" => $v['60'],
                    "90+" => $v['90']
                );
                $no++;
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Name" => '',
                "Total Due" => $total['grandTotal_all'],
                "Current" => $total['grandTotal_current'],
                "1-30" => $total['grandTotal_0'],
                "31-60" => $total['grandTotal_30'],
                "61-90" => $total['grandTotal_60'],
                "90+" => $total['grandTotal_90']
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "AR Aging"
                    ))
                    ->setCellFormat(array(
                        2 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewInvoiceAgingAction() {
        $cus = $this->getRequest()->getParam("cus_kode");
        $tglend = $this->getRequest()->getParam("tgl");

        if ($tglend)
            $tglend = date("Y-m-d", strtotime($tglend)) . " 23:59:59";

        $export = ($this->_getParam("export") != '') ? true : false;
        
        $query="DROP TEMPORARY TABLE IF EXISTS invh;
                CREATE TEMPORARY TABLE invh
                SELECT trano, tgl, SUM(total) AS total, rateidr,SUM(total_invoice) AS total_invoice,val_kode,cus_kode,prj_kode,sit_kode,prj_nama,top 
                FROM erpdb.finance_invoice WHERE cus_kode !='MIT' GROUP BY trano";
        $this->db->query($query);
        
        $query="DROP TEMPORARY TABLE IF EXISTS invd;
                CREATE TEMPORARY TABLE invd
                SELECT trano,val_kode,statusppn,rateidr,SUM(deduction) AS deduction 
                FROM erpdb.finance_invoiced WHERE cus_kode !='MIT' GROUP BY trano";
        $this->db->query($query);
            
        $subselect = $this->db->select()
                        //->from(array("a" => $this->FINANCE->Invoice->name()), array(
                        ->from(array("a" => "invh" ), array(
                            "trano",
                            "tgl",
                            "total" => "IF(b.deduction < 0, 
                                        (IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total))),
                                        (IF(b.val_kode != 'IDR',(a.total_invoice * coalesce(a.rateidr,b.rateidr)),a.total_invoice))) ",
                            "total_ori" => "IF(b.deduction < 0, a.total,a.total_invoice) ",
                            "val_kode",
                            "cus_kode",
                            "prj_kode",
                            "sit_kode",
                            "prj_nama",
                            "top",
                            "rateidr"
                        //))->joinLeft(array("b" => $this->FINANCE->InvoiceDetail->name()), "a.trano = b.trano", array(
                          ))->joinLeft(array("b" => "invd"), "a.trano = b.trano", array(
                    "trano as trano_detail"
                ))->joinLeft(array("c" => $this->FINANCE->AccountingCancelInvoice->__name()), "a.trano = c.invoice_no", array());

        if ($cus)
            $subselect->where("a.cus_kode = '$cus'");
        if ($tglend)
            $subselect->where("a.tgl <= '$tglend'");
        $subselect->where("(a.total - coalesce(c.total_cancel,0)) > 0");


        $subselect2 = $this->db->select()
                ->from(array($this->FINANCE->PaymentInvoice->name()), array(
                    "total_payment" => "coalesce(sum(total),0)",
                    "inv_no",
                    "val_kode",
                ))
                ->group(array("inv_no", "val_kode"));
        if ($cus)
            $subselect2->where("cus_kode = '$cus'");

        $subselect3 = $this->db->select()
                ->from(array("a" => $subselect))
                ->joinLeft(array("b" => $subselect2), "a.trano = b.inv_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            "total_payment" => "(IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0)))",
        ));


        $subselectDN = $this->db->select()
                        ->from(array("a" => $this->FINANCE->NDreimbursHeader->__name()), array(
                            "trano",
                            "tgl",
                            "total" => "(IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total)))",
                            "total_ori" => "total",
                            "val_kode",
                            "cus_kode",
                            "prj_kode",
                            "sit_kode",
                            "prj_nama",
                            "top",
                            "rateidr"
                        ))->joinLeft(array("b" => $this->FINANCE->NDreimbursDetail->__name()), "a.trano = b.trano", array(
            "trano as trano_detail"
        ));

        if ($cus)
            $subselectDN->where("a.cus_kode = '$cus'");
        if ($tglend)
            $subselectDN->where("a.tgl <= '$tglend'");

        $subselect2DN = $this->db->select()
                ->from(array($this->FINANCE->paymentNDreimbursH->__name()), array(
                    "total_payment" => "coalesce(sum(total),0)",
                    "dn_no",
                    "val_kode",
                ))
                ->group(array("dn_no", "val_kode"));
        if ($cus)
            $subselect2DN->where("cus_kode = '$cus'");

        $subselect3DN = $this->db->select()
                ->from(array("a" => $subselectDN))
                ->joinLeft(array("b" => $subselect2DN), "a.trano = b.dn_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            "total_payment" => "(IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0)))",
        ));
        $subselect3_ = (string) $subselect3;
        $subselect3DN_ = (string) $subselect3DN;

        $fetch = $this->db->query("drop temporary table if exists aging;");
        $fetch = $this->db->query("create temporary table aging
                    select trano, cus_kode, prj_kode,sit_kode,prj_nama, total,total_ori,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,total_payment_ori,(total-total_payment)balance 
                    from ($subselect3_)a
                    where total_payment < total
                    order by cus_kode;");
        $fetch = $this->db->query("insert into aging  select trano, cus_kode, prj_kode,sit_kode,prj_nama, total,total_ori,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,total_payment_ori,(total-total_payment)balance 
                    from ($subselect3DN_)a
                    where total_payment < total
                    order by cus_kode; ");

        $fetch = $this->db->fetchAll("select * from aging;");
        $data = array();
        if ($fetch) {
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $cus = $val['cus_kode'];

                if ($cus == '') {
                    $cus = $val['prj_kode'];
                    $data[$cus]['cus_empty'] = true;
                } else
                    $data[$cus]['cus_empty'] = false;

                $days = $val['days'];
                $data[$cus]['val_kode'] = $val['val_kode'];
                if ($days <= 0) {
                    $data[$cus]['current'] += $val['balance'];
                } elseif ($days >= 1 && $days <= 30) {
                    $data[$cus]['0'] += $val['balance'];
                } elseif ($days >= 31 && $days <= 60) {
                    $data[$cus]['30'] += $val['balance'];
                } elseif ($days >= 61 && $days <= 90) {
                    $data[$cus]['60'] += $val['balance'];
                } else {
                    $data[$cus]['90'] += $val['balance'];
                }
            }

            $total = array();
            foreach ($data as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $data[$k]['total'] = $totalBalance;
                $total['grandTotal_current'] += $v['current'];
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;

                if ($this->DEFAULT->MasterCustomer->getName($k) != '')
                    $data[$k]['name'] = $this->DEFAULT->MasterCustomer->getName($k);
                else
                    $data[$k]['name'] = $k;
            }
        }

        if (!$export) {
            $this->view->data = $data;
            $this->view->total = $total;
            $this->view->tgl = $tglend;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Name" => $v['name'],
                    "Total Due" => $v['total'],
                    "Current" => $v['current'],
                    "1-30" => $v['0'],
                    "31-60" => $v['30'],
                    "61-90" => $v['60'],
                    "90+" => $v['90']
                );
                $no++;
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Name" => '',
                "Total Due" => $total['grandTotal_all'],
                "Current" => $total['grandTotal_current'],
                "1-30" => $total['grandTotal_0'],
                "31-60" => $total['grandTotal_30'],
                "61-90" => $total['grandTotal_60'],
                "90+" => $total['grandTotal_90']
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "AR Aging"
                    ))
                    ->setCellFormat(array(
                        2 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function detailInvoiceAgingAction() {
        $cus = $this->getRequest()->getParam("cus_kode");
        $tglend = $this->getRequest()->getParam("tgl");
        $start = $this->getRequest()->getParam("start");
        $using_project = $this->getRequest()->getParam("with_project");
        $export = ($this->_getParam("export") != '') ? true : false;

        $tempstart = $start;

        if ($start != '') {

            if ($start == 0)
                $start2 = 30;
            if ($start == 30) {
                $start = 31;
                $start2 = 60;
            }
            if ($start == 60) {
                $start = 61;
                $start2 = 90;
            }
            if ($start == 90) {
                $start2 = '';
                $start = 91;
            }


            if ($start != '')
                if ($start == 'current')
                    $start = "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) <= 0";
                else
                    $start = "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) >= $start";
            if ($start2 != '')
                $start2 = " AND DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) <= $start2";
            $where = "$start $start2";
        }
        
        $query="DROP TEMPORARY TABLE IF EXISTS invh;
                CREATE TEMPORARY TABLE invh
                SELECT trano, tgl, SUM(total) AS total,rateidr,SUM(total_invoice) AS total_invoice,val_kode,cus_kode,prj_kode,sit_kode,prj_nama,sit_nama,top 
                FROM erpdb.finance_invoice WHERE cus_kode !='MIT' GROUP BY trano";
        $this->db->query($query);
        
        $query="DROP TEMPORARY TABLE IF EXISTS invd;
                CREATE TEMPORARY TABLE invd
                SELECT trano,val_kode,statusppn,rateidr,SUM(deduction) AS deduction 
                FROM erpdb.finance_invoiced WHERE cus_kode !='MIT' GROUP BY trano";
        $this->db->query($query);

        $subselect = $this->db->select()
                        //->from(array("a" => $this->FINANCE->Invoice->name()), array(
                ->from(array("a" => "invh"), array(
                            "trano",
                            "tgl",
                            "total" => "IF(b.deduction < 0, if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total), a.total_invoice)",
                            "total_with_ppn" => 
//                                round((IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
//                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
//                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total))),2)",
                            "round(IF(b.deduction < 0, 
                                        (IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total))),
                                        (IF(b.val_kode != 'IDR',(a.total_invoice * coalesce(a.rateidr,b.rateidr)),a.total_invoice))),2)",
                            "val_kode",
                            "prj_kode",
                            "sit_kode",
                            "prj_nama",
                            "sit_nama",
                            "cus_kode",
                            "top",
                            "rateidr",
                            "ppn" => "(if(b.statusppn ='Y', (a.total*0.1), 0))"
                        //))->joinLeft(array("b" => $this->FINANCE->InvoiceDetail->name()), "a.trano = b.trano", array("trano as trano_detail"
                        ))->joinLeft(array("b" => "invd"), "a.trano = b.trano", array("trano as trano_detail"
                ))->joinLeft(array("c" => $this->FINANCE->AccountingCancelInvoice->__name()), "a.trano = c.invoice_no", array());


        if ($cus) {
            if ($using_project)
                $subselect->where("a.prj_kode = '$cus'");
            else
                $subselect->where("a.cus_kode = '$cus'");
        }
        if ($tglend)
            $subselect->where("a.tgl <= '$tglend'");
        $subselect->where("(a.total - coalesce(c.total_cancel,0)) > 0");

         



        $subselect2 = $this->db->select()
                ->from(array($this->FINANCE->PaymentInvoice->name()), array(
                    "total_payment" => "coalesce(sum(total),0)",
                    "inv_no",
                    "val_kode"
                ))
                ->group(array("inv_no"));
        if ($cus)
            $subselect2->where("cus_kode = '$cus'");

        $subselect3 = $this->db->select()
                ->from(array("a" => $subselect))
                ->joinLeft(array("b" => $subselect2), "a.trano = b.inv_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            "total_payment" => "round((IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0))),2)",
        ));


        $subselectDN = $this->db->select()
                        ->from(array("a" => $this->FINANCE->NDreimbursHeader->__name()), array(
                            "trano",
                            "tgl",
                            "total" => "if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total)",
                            "total_with_ppn" => "round((IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total))),2)",
                            "val_kode",
                            "prj_kode",
                            "sit_kode",
                            "prj_nama",
                            "sit_nama",
                            "cus_kode",
                            "top",
                            "rateidr",
                            "ppn" => "(if(b.statusppn ='Y', (a.total*0.1), 0))"
                        ))->joinLeft(array("b" => $this->FINANCE->NDreimbursDetail->__name()), "a.trano = b.trano", array("trano as trano_detail"
        ));


        if ($cus) {
            if ($using_project)
                $subselectDN->where("a.prj_kode = '$cus'");
            else
                $subselectDN->where("a.cus_kode = '$cus'");
        }
        if ($tglend)
            $subselectDN->where("a.tgl <= '$tglend'");

        $subselect2DN = $this->db->select()
                ->from(array($this->FINANCE->paymentNDreimbursH->__name()), array(
                    "total_payment" => "coalesce(sum(total),0)",
                    "dn_no",
                    "val_kode"
                ))
                ->group(array("dn_no"));
        if ($cus)
            $subselect2DN->where("cus_kode = '$cus'");

        $subselect3DN = $this->db->select()
                ->from(array("a" => $subselectDN))
                ->joinLeft(array("b" => $subselect2DN), "a.trano = b.dn_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            "total_payment" => "round((IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0))),2)",
        ));

        $subselect3_ = (string) $subselect3;
        $subselect3DN_ = (string) $subselect3DN;

        $fetch = $this->db->query("drop temporary table if exists aging;");
        $fetch = $this->db->query("create temporary table aging
                    select trano, cus_kode, prj_kode,sit_kode,prj_nama,sit_nama, total,total_with_ppn,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,(total-total_payment_ori)balance,ppn 
                    from ($subselect3_)a
                    where round(total_payment,2) < round(total_with_ppn,2)
                    order by trano;");
        $fetch = $this->db->query("insert into aging  select trano, cus_kode, prj_kode,sit_kode,prj_nama,sit_nama, total,total_with_ppn,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,(total-total_payment_ori)balance,ppn 
                    from ($subselect3DN_)a
                    where round(total_payment,2) < round(total_with_ppn,2)
                    order by trano; ");
        $fetch = $this->db->fetchAll("select * from aging;");

        $data = array();
        if ($fetch) {
            $data = $fetch;
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $prj = $val['prj_kode'];
                $cus = $val['cus_kode'];
                $days = $val['days'];
                if ($days < 0)
                    $days = 0;
                $data[$key]['days'] = $days;
                $data[$key]['name'] = $this->DEFAULT->MasterCustomer->getName($cus);
                $this->view->name = $data[$key]['name'];
            }
        }

        if (!$export) {
            $this->view->data = $data;
            $this->view->cuskode = $cus;
            $this->view->start = $tempstart;
            $this->view->tgl = $tglend;
        } else {

            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $valueIDR = '';
            $valueUSD = '';
            $ppnIDR = '';
            $ppnUSD = '';

            foreach ($data as $k => $v) {
                if ($v['val_kode'] != 'IDR') {
                    $valueUSD = $v['balance'];
                    $ppnUSD = $v['ppn'];
                } else {
                    $valueIDR = $v['balance'];
                    $ppnIDR = $v['ppn'];
                }

                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date('d - M - Y', strtotime($v['tgl'])),
                    "Overdue (Days)" => $v['days'],
                    "Value (IDR)" => number_format($valueIDR, 2),
                    "Tax (IDR)" => number_format($ppnIDR, 2),
                    "Value (USD)" => number_format($valueUSD, 2),
                    "Tax (USD)" => number_format($ppnUSD, 2)
                );
                $no++;

                $valueIDR = '';
                $valueUSD = '';
                $ppnIDR = '';
                $ppnUSD = '';
            }

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "AR Aging Detail"
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function detailInvoiceAgingOcAction() {
        $cus = $this->getRequest()->getParam("cus_kode");
        $tglend = $this->getRequest()->getParam("tgl");
        $start = $this->getRequest()->getParam("start");
        $using_project = $this->getRequest()->getParam("with_project");
        $currency = $this->_getParam("currency");

        $export = ($this->_getParam("export") != '') ? true : false;

        $tempstart = $start;

        if ($start != '') {

            if ($start == 0)
                $start2 = 30;
            if ($start == 30) {
                $start = 31;
                $start2 = 60;
            }
            if ($start == 60) {
                $start = 61;
                $start2 = 90;
            }
            if ($start == 90) {
                $start2 = '';
                $start = 91;
            }


            if ($start != '')
                if ($start == 'current')
                    $start = "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) <= 0";
                else
                    $start = "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) >= $start";
            if ($start2 != '')
                $start2 = " AND DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) <= $start2";
            $where = "$start $start2";
        }

        $query="DROP TEMPORARY TABLE IF EXISTS invh;
                CREATE TEMPORARY TABLE invh
                SELECT trano, tgl, SUM(total) AS total,rateidr,SUM(total_invoice) AS total_invoice,val_kode,cus_kode,prj_kode,sit_kode,prj_nama,sit_nama,top 
                FROM erpdb.finance_invoice WHERE cus_kode !='MIT' GROUP BY trano";
        $this->db->query($query);
        
        $query="DROP TEMPORARY TABLE IF EXISTS invd;
                CREATE TEMPORARY TABLE invd
                SELECT trano,val_kode,statusppn,rateidr,SUM(deduction) AS deduction 
                FROM erpdb.finance_invoiced WHERE cus_kode !='MIT' GROUP BY trano";
        $this->db->query($query);
        
        $subselect = $this->db->select()
                        //->from(array("a" => $this->FINANCE->Invoice->name()), array(
                            ->from(array("a" => "invh"), array(
                            "trano",
                            "tgl",
                            "total" => "IF(b.deduction < 0, if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total), a.total_invoice)",
                            "total_with_ppn" => 
                            "round(IF(b.deduction < 0,(IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total))),a.total_invoice),2)",
                            "val_kode",
                            "prj_kode",
                            "prj_nama",
                            "cus_kode",
                            "top",
                            "rateidr",
                            "ppn" => "(if(b.statusppn ='Y', (a.total*0.1), 0))"
                       // ))->joinLeft(array("b" => $this->FINANCE->InvoiceDetail->name()), "a.trano = b.trano", array("trano as trano_detail"
                           ))->joinLeft(array("b" => "invd"), "a.trano = b.trano", array("trano as trano_detail"
                ))->joinLeft(array("c" => $this->FINANCE->AccountingCancelInvoice->__name()), "a.trano = c.invoice_no", array());


        if ($cus) {
            if ($using_project)
                $subselect->where("a.prj_kode = '$cus'");
            else
                $subselect->where("a.cus_kode = '$cus'");
        }
        if ($tglend)
            $subselect->where("a.tgl <= '$tglend'");
        $subselect->where("(a.total - coalesce(c.total_cancel,0)) > 0");

        $subselect2 = $this->db->select()
                ->from(array($this->FINANCE->PaymentInvoice->name()), array(
                    "total_payment" => "coalesce(sum(total),0)",
                    "inv_no",
                    "val_kode"
                ))
                ->group(array("inv_no"));
        if ($cus)
            $subselect2->where("cus_kode = '$cus'");

        $subselect3 = $this->db->select()
                ->from(array("a" => $subselect))
                ->joinLeft(array("b" => $subselect2), "a.trano = b.inv_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            "total_payment" => "round((IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0))),2)",
        ));


        $subselectDN = $this->db->select()
                        ->from(array("a" => $this->FINANCE->NDreimbursHeader->__name()), array(
                            "trano",
                            "tgl",
                            "total" => "if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total)",
                            "total_with_ppn" => "round((IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total))),2)",
                            "val_kode",
                            "prj_kode",
                            "prj_nama",
                            "cus_kode",
                            "top",
                            "rateidr",
                            "ppn" => "(if(b.statusppn ='Y', (a.total*0.1), 0))"
                        ))->joinLeft(array("b" => $this->FINANCE->NDreimbursDetail->__name()), "a.trano = b.trano", array("trano as trano_detail"
        ));


        if ($cus) {
            if ($using_project)
                $subselectDN->where("a.prj_kode = '$cus'");
            else
                $subselectDN->where("a.cus_kode = '$cus'");
        }
        if ($tglend)
            $subselectDN->where("a.tgl <= '$tglend'");

        $subselect2DN = $this->db->select()
                ->from(array($this->FINANCE->paymentNDreimbursH->__name()), array(
                    "total_payment" => "coalesce(sum(total),0)",
                    "dn_no",
                    "val_kode"
                ))
                ->group(array("dn_no"));
        if ($cus)
            $subselect2DN->where("cus_kode = '$cus'");

        $subselect3DN = $this->db->select()
                ->from(array("a" => $subselectDN))
                ->joinLeft(array("b" => $subselect2DN), "a.trano = b.dn_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            "total_payment" => "round((IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0))),2)",
        ));

        $subselect3_ = (string) $subselect3;
        $subselect3DN_ = (string) $subselect3DN;

        $fetch = $this->db->query("drop temporary table if exists aging_oc;");
        $fetch = $this->db->query("create temporary table aging_oc
                    select trano, cus_kode, prj_kode,prj_nama, total,total_with_ppn,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,(total-total_payment_ori)balance,ppn 
                    from ($subselect3_)a
                    where round(total_payment,2) < round(total_with_ppn,2)
                    order by trano;");
        $fetch = $this->db->query("insert into aging_oc  select trano, cus_kode, prj_kode,prj_nama, total,total_with_ppn,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,(total-total_payment_ori)balance,ppn 
                    from ($subselect3DN_)a
                    where round(total_payment,2) < round(total_with_ppn,2)
                    order by trano; ");
        $fetch = $this->db->fetchAll("select * from aging_oc WHERE val_kode='$currency';");

        $data = array();
        if ($fetch) {
            $data = $fetch;
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $prj = $val['prj_kode'];
                $cus = $val['cus_kode'];
                $days = $val['days'];
                if ($days < 0)
                    $days = 0;
                $data[$key]['days'] = $days;
                $data[$key]['name'] = $this->DEFAULT->MasterCustomer->getName($cus);
                $this->view->name = $data[$key]['name'];
            }
        }

        if (!$export) {
            $this->view->data = $data;
            $this->view->cuskode = $cus;
            $this->view->start = $tempstart;
            $this->view->tgl = $tglend;
        } else {

            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $valueIDR = '';
            $valueUSD = '';
            $ppnIDR = '';
            $ppnUSD = '';

            foreach ($data as $k => $v) {
                if ($v['val_kode'] != 'IDR') {
                    $valueUSD = $v['balance'];
                    $ppnUSD = $v['ppn'];
                } else {
                    $valueIDR = $v['balance'];
                    $ppnIDR = $v['ppn'];
                }

                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date('d - M - Y', strtotime($v['tgl'])),
                    "Overdue (Days)" => $v['days'],
                    "Value (IDR)" => number_format($valueIDR, 2),
                    "Tax (IDR)" => number_format($ppnIDR, 2),
                    "Value (USD)" => number_format($valueUSD, 2),
                    "Tax (USD)" => number_format($ppnUSD, 2)
                );
                $no++;

                $valueIDR = '';
                $valueUSD = '';
                $ppnIDR = '';
                $ppnUSD = '';
            }

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "AR Aging Detail"
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewInvoiceAgingTaxAction() {
        $cus = $this->getRequest()->getParam("cus_kode");
        $tglend = $this->getRequest()->getParam("tgl");
        if ($tglend)
            $tglend = date("Y-m-d", strtotime($tglend)) . " 23:59:59";
        $export = ($this->_getParam("export") != '') ? true : false;

        $subselect = $this->db->select()
                        ->from(array("a" => $this->FINANCE->Invoice->name()), array(
                            "trano",
                            "tgl",
                            "total" => "(IF(b.val_kode = 'USD',(if(b.statusppn = 'Y', 
                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total)))",
                            "total_ori" => "total",
                            "val_kode",
                            "cus_kode",
                            "top",
                            "prj_kode",
                            "prj_nama",
                            "rateidr"
                        ))->joinLeft(array("b" => $this->FINANCE->InvoiceDetail->name()), "a.trano = b.trano", array(
                    "trano as trano_detail"
                ))->joinLeft(array("c" => $this->FINANCE->AccountingCancelInvoice->__name()), "a.trano = c.invoice_no", array());

        if ($cus)
            $subselect->where("a.cus_kode = '$cus'");
        if ($tglend)
            $subselect->where("a.tgl <= '$tglend'");
        $subselect->where("(a.total - coalesce(c.total_cancel,0)) > 0");

        $subselect2 = $this->db->select()
                ->from(array($this->FINANCE->PaymentInvoice->name()), array(
                    "total_payment" => "coalesce(sum(total+total_deduction),0)",
                    "inv_no",
                    "val_kode",
                ))
                ->group(array("inv_no", "val_kode"));
        if ($cus)
            $subselect2->where("cus_kode = '$cus'");

        $subselect3 = $this->db->select()
                ->from(array("a" => $subselect))
                ->joinLeft(array("b" => $subselect2), "a.trano = b.inv_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            "total_payment" => "(IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0)))",
        ));


        $subselectDN = $this->db->select()
                        ->from(array("a" => $this->FINANCE->NDreimbursHeader->__name()), array(
                            "trano",
                            "tgl",
                            "total" => "(IF(b.val_kode = 'USD',(if(b.statusppn = 'Y', 
                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total)))",
                            "total_ori" => "total",
                            "val_kode",
                            "cus_kode",
                            "top",
                            "prj_kode",
                            "prj_nama",
                            "rateidr"
                        ))->joinLeft(array("b" => $this->FINANCE->NDreimbursDetail->__name()), "a.trano = b.trano", array(
            "trano as trano_detail"
        ));

        if ($cus)
            $subselectDN->where("a.cus_kode = '$cus'");
        if ($tglend)
            $subselectDN->where("a.tgl <= '$tglend'");

        $subselect2DN = $this->db->select()
                ->from(array($this->FINANCE->paymentNDreimbursH->__name()), array(
                    "total_payment" => "coalesce(sum(total),0)",
                    "dn_no",
                    "val_kode",
                ))
                ->group(array("dn_no", "val_kode"));
        if ($cus)
            $subselect2DN->where("cus_kode = '$cus'");

        $subselect3DN = $this->db->select()
                ->from(array("a" => $subselectDN))
                ->joinLeft(array("b" => $subselect2DN), "a.trano = b.dn_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            "total_payment" => "(IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0)))",
        ));

        $subselect3_ = (string) $subselect3;
        $subselect3DN_ = (string) $subselect3DN;

        $fetch = $this->db->query("drop temporary table if exists aging;");
        $fetch = $this->db->query("create temporary table aging
                    select trano, cus_kode, prj_kode,prj_nama, total,total_ori,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,total_payment_ori,(total-total_payment)balance 
                    from ($subselect3_)a
                    where total_payment < total
                    order by cus_kode;");
        $fetch = $this->db->query("insert into aging  select trano, cus_kode, prj_kode,prj_nama, total,total_ori,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,total_payment_ori,(total-total_payment)balance 
                    from ($subselect3DN_)a
                    where total_payment < total
                    order by cus_kode; ");

        $fetch = $this->db->fetchAll("select * from aging;");

        $data = array();
        if ($fetch) {
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $cus = $val['cus_kode'];

                if ($cus == '') {
                    $cus = $val['prj_kode'];
                    $data[$cus]['cus_empty'] = true;
                } else
                    $data[$cus]['cus_empty'] = false;

                $days = $val['days'];
                $data[$cus]['val_kode'] = $val['val_kode'];
                if ($days <= 0) {
                    $data[$cus]['current'] += $val['balance'];
                } elseif ($days >= 1 && $days <= 30) {
                    $data[$cus]['0'] += $val['balance'];
                } elseif ($days >= 31 && $days <= 60) {
                    $data[$cus]['30'] += $val['balance'];
                } elseif ($days >= 61 && $days <= 90) {
                    $data[$cus]['60'] += $val['balance'];
                } else {
                    $data[$cus]['90'] += $val['balance'];
                }
            }

            $total = array();
            foreach ($data as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $data[$k]['total'] = $totalBalance;
                $total['grandTotal_current'] += $v['current'];
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;

                if ($this->DEFAULT->MasterCustomer->getName($k) != '')
                    $data[$k]['name'] = $this->DEFAULT->MasterCustomer->getName($k);
                else
                    $data[$k]['name'] = $k;
            }
        }


        if (!$export) {
            $this->view->data = $data;
            $this->view->total = $total;
            $this->view->tgl = $tglend;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Name" => $v['name'],
                    "Total Due" => $v['total'],
                    "Current" => $v['current'],
                    "1-30" => $v['0'],
                    "31-60" => $v['30'],
                    "61-90" => $v['60'],
                    "90+" => $v['90']
                );
                $no++;
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Name" => '',
                "Total Due" => $total['grandTotal_all'],
                "Current" => $total['grandTotal_current'],
                "1-30" => $total['grandTotal_0'],
                "31-60" => $total['grandTotal_30'],
                "61-90" => $total['grandTotal_60'],
                "90+" => $total['grandTotal_90']
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "AR Aging"
                    ))
                    ->setCellFormat(array(
                        2 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function detailInvoiceAgingTaxAction() {
        $cus = $this->getRequest()->getParam("cus_kode");
        $start = $this->getRequest()->getParam("start");
        $tglend = $this->getRequest()->getParam("tgl");
        $export = ($this->_getParam("export") != '') ? true : false;
        $tempstart = $start;
        $using_project = $this->getRequest()->getParam("with_project");

        if ($start != '') {

            if ($start == 0)
                $start2 = 30;
            if ($start == 30) {
                $start = 31;
                $start2 = 60;
            }
            if ($start == 60) {
                $start = 61;
                $start2 = 90;
            }
            if ($start == 90) {
                $start2 = '';
                $start = 91;
            }


            if ($start != '')
                if ($start == 'current')
                    $start = "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) <= 0";
                else
                    $start = "DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) >= $start";
            if ($start2 != '')
                $start2 = " AND DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) <= $start2";
            $where = "$start $start2";
        }

        $subselect = $this->db->select()
                        ->from(array("a" => $this->FINANCE->Invoice->name()), array(
                            "trano",
                            "tgl",
                            "total",
                            "total_with_ppn" => "(IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total)))",
                            "val_kode",
                            "prj_kode",
                            "sit_kode",
                            "prj_nama",
                            "sit_nama",
                            "cus_kode",
                            "top",
                            "rateidr",
                            "ppn" => "(IF(b.val_kode = 'USD',"
                            . "(if(b.statusppn ='Y', (a.total*0.1*coalesce(a.rateidr,b.rateidr)), 0))"
                            . ","
                            . "(if(b.statusppn ='Y', (a.total*0.1), 0))))"
                        ))->joinLeft(array("b" => $this->FINANCE->InvoiceDetail->name()), "a.trano = b.trano", array("trano as trano_detail"
                ))->joinLeft(array("c" => $this->FINANCE->AccountingCancelInvoice->__name()), "a.trano = c.invoice_no", array());


        if ($cus) {
            if ($using_project)
                $subselect->where("a.prj_kode = '$cus'");
            else
                $subselect->where("a.cus_kode = '$cus'");
        }
        if ($tglend)
            $subselect->where("a.tgl <= '$tglend'");
        $subselect->where("(a.total - coalesce(c.total_cancel,0)) > 0");

        $subselect2 = $this->db->select()
                ->from(array($this->FINANCE->PaymentInvoice->name()), array(
                    "total_payment" => "coalesce(sum(total+total_deduction),0)",
                    "inv_no",
                    "val_kode"
                ))
                ->group(array("inv_no"));
        if ($cus)
            $subselect2->where("cus_kode = '$cus'");

        $subselect3 = $this->db->select()
                ->from(array("a" => $subselect))
                ->joinLeft(array("b" => $subselect2), "a.trano = b.inv_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            "total_payment" => "(IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0)))",
        ));


        $subselectDN = $this->db->select()
                        ->from(array("a" => $this->FINANCE->NDreimbursHeader->__name()), array(
                            "trano",
                            "tgl",
                            "total",
                            "total_with_ppn" => "(IF(b.val_kode != 'IDR',(if(b.statusppn = 'Y', 
                                        (a.total+round((a.total*0.1),2)),a.total)*coalesce(a.rateidr,b.rateidr)),
                                        if(b.statusppn = 'Y', (a.total+round((a.total*0.1),2)),a.total)))",
                            "val_kode",
                            "prj_kode",
                            "sit_kode",
                            "prj_nama",
                            "sit_nama",
                            "cus_kode",
                            "top",
                            "rateidr",
                            "ppn" => "(IF(b.val_kode = 'USD',"
                            . "(if(b.statusppn ='Y', (a.total*0.1*coalesce(a.rateidr,b.rateidr)), 0))"
                            . ","
                            . "(if(b.statusppn ='Y', (a.total*0.1), 0))))"
                        ))->joinLeft(array("b" => $this->FINANCE->NDreimbursDetail->__name()), "a.trano = b.trano", array("trano as trano_detail"
        ));


        if ($cus) {
            if ($using_project)
                $subselectDN->where("a.prj_kode = '$cus'");
            else
                $subselectDN->where("a.cus_kode = '$cus'");
        }
        if ($tglend)
            $subselectDN->where("a.tgl <= '$tglend'");


        $subselect2DN = $this->db->select()
                ->from(array($this->FINANCE->paymentNDreimbursH->__name()), array(
                    "total_payment" => "coalesce(sum(total),0)",
                    "dn_no",
                    "val_kode"
                ))
                ->group(array("dn_no"));
        if ($cus)
            $subselect2DN->where("cus_kode = '$cus'");

        $subselect3DN = $this->db->select()
                ->from(array("a" => $subselectDN))
                ->joinLeft(array("b" => $subselect2DN), "a.trano = b.dn_no", array(
            "total_payment_ori" => "COALESCE(total_payment,0)",
            "total_payment" => "(IF(b.val_kode != 'IDR',(a.rateidr*coalesce(total_payment,0)),coalesce(total_payment,0)))",
        ));

        $subselect3_ = (string) $subselect3;
        $subselect3DN_ = (string) $subselect3DN;

        $fetch = $this->db->query("drop temporary table if exists aging;");
        $fetch = $this->db->query("create temporary table aging
                    select trano, cus_kode, prj_kode,sit_kode,prj_nama,sit_nama, total,total_with_ppn,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,(total-total_payment_ori)balance,ppn 
                    from ($subselect3_)a
                    where round(total_payment,2) < round(total_with_ppn,2)
                    order by trano;");
        $fetch = $this->db->query("insert into aging  select trano, cus_kode, prj_kode,sit_kode,prj_nama,sit_nama, total,total_with_ppn,val_kode,tgl,DATEDIFF(DATE(NOW()),DATE_ADD(tgl,INTERVAL top DAY)) as days,
                    total_payment,(total-total_payment_ori)balance,ppn 
                    from ($subselect3DN_)a
                    where round(total_payment,2) < round(total_with_ppn,2)
                    order by trano; ");
        $fetch = $this->db->fetchAll("select * from aging;");

        $data = array();
        if ($fetch) {
            $data = $fetch;
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $prj = $val['prj_kode'];
                $cus = $val['cus_kode'];
                $days = $val['days'];
                if ($days < 0)
                    $days = 0;
                $data[$key]['days'] = $days;
                $data[$key]['name'] = $this->DEFAULT->MasterCustomer->getName($cus);
                $this->view->name = $data[$key]['name'];
            }
        }



        if (!$export) {
            $this->view->data = $data;
            $this->view->cuskode = $cus;
            $this->view->start = $tempstart;
            $this->view->tgl = $tglend;
        } else {

            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $valueIDR = '';
            $valueUSD = '';
            $ppnIDR = '';
            $ppnUSD = '';

            foreach ($data as $k => $v) {
                if ($v['val_kode'] != 'IDR') {
                    $valueUSD = $v['balance'];
                    $ppnUSD = $v['ppn'];
                } else {
                    $valueIDR = $v['balance'];
                    $ppnIDR = $v['ppn'];
                }

                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date('d - M - Y', strtotime($v['tgl'])),
                    "Overdue (Days)" => $v['days'],
                    "Value (IDR)" => number_format($valueIDR, 2),
                    "Tax (IDR)" => number_format($ppnIDR, 2),
                    "Value (USD)" => number_format($valueUSD, 2),
                    "Tax (USD)" => number_format($ppnUSD, 2)
                );
                $no++;

                $valueIDR = '';
                $valueUSD = '';
                $ppnIDR = '';
                $ppnUSD = '';
            }

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "AR Aging Detail"
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

//    public function trialBalancesheetAction()
//    {
//
//    }
//
//    public function viewTrialBalancesheetAction()
//    {
//        $month = $this->getRequest()->getParam("month");
//
//        $date = new DateTime($month . "-" . "01");
//        $date->add(new DateInterval('P1M'));
//        $date->sub(new DateInterval('P1D'));
//
//        $tglAwal = $month . "-" . "01";
//        $tglAkhir = $date->format('Y-m-d');
//        $periode = array(
//            "tgl_awal" => $tglAwal,
//            "tgl_akhir" => $tglAkhir,
//        );
//
//        $result = QDC_Finance_TrialBalance::factory()->getData($periode);
//        $this->view->data = $result;
//        $this->view->periode = date("M Y",strtotime($periode['tgl_awal']));
//        $this->view->tgl = date("d M Y",strtotime($tglAwal)) . " - " . date("d M Y",strtotime($tglAkhir));
//
//    }

    public function trialBalancesheetAction() {
        
    }

    public function viewTrialBalancesheetAction() {
        $prjKode = $this->_getParam("prj_kode");
        $month = $this->_getParam("month");
        $export = ($this->_getParam("export") == 'true') ? true : false;
        $detailTrans = ($this->_getParam("detail_trans") != '' && $this->_getParam("detail_trans") != 'false') ? true : false;

        $grouped = ($this->_getParam("group") == 'true') ? true : false;

        if ($prjKode)
            $wherePrj = "prj_kode = '$prjKode'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";

            $whereTgl = "BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($wherePrj) {
            $wherePosting = $wherePrj;
            $whereNonPosting = $wherePrj;
            if ($whereTgl) {
                $wherePosting.= " AND tglpost " . $whereTgl;
                $whereNonPosting.= " AND tgl " . $whereTgl;
            }
        } elseif ($whereTgl) {
//            $wherePosting = "tglpost " . $whereTgl;
            $whereNonPosting = "tgl " . $whereTgl;
        }

//        $whereJurnal = array(
//            "AP" => $wherePosting,
//            "AR" => $whereNonPosting,
//            "bank" => $wherePosting . " AND trano LIKE 'BPV%'",
//            "bank_non_bpv" => $whereNonPosting . " AND trano NOT LIKE 'BPV%'",
//            "general" => $whereNonPosting,
//            "bank_in" => $whereNonPosting,
//            "bank_out" => $whereNonPosting,
//            "petty_cash_in" => $whereNonPosting,
//            "petty_cash_out" => $whereNonPosting
//        );
//
//        $result = QDC_Finance_Jurnal::factory()->getAllJurnalTrialBalance($whereJurnal);

        QDC_Finance_Jurnal::factory(array(
            "useShape" => true,
            "useTempTable" => true,
            "tempTableName" => "all_jurnal_trial_balance"
        ))->getAllJurnal($whereNonPosting);

        if (!$grouped)
            $select = $this->db->select()
                    ->from(array("all_jurnal_trial_balance"));
        else
            $select = $this->db->select()
                    ->from(array("all_jurnal_trial_balance"), array(
                        "trano",
                        "ref_number",
                        "coa_kode",
                        "coa_nama",
                        "val_kode",
                        "rateidr",
                        "tgl",
                        "uid",
                        "debit" => "SUM(debit)",
                        "credit" => "SUM(credit)"
                    ))
                    ->group(array("trano", "coa_kode"));

        $select = $select->order(array("tgl ASC"));

        $result = $this->db->fetchAll($select);

        $data = array();
        if ($detailTrans) {
            foreach ($result as $k => $v) {
                $trano = $v['trano'];
                if ($trano == '')
                    $trano = 'NO TRANO';
                $v['ref_number'] = QDC_Common_String::factory()->wordWrap($v['ref_number']);
                $v['coa_nama'] = QDC_Common_String::factory()->wordWrap($v['coa_nama']);
                $data[$trano][] = $v;
            }
        }
        else {
            foreach ($result as $k => $v) {
                $coa = $v['coa_kode'];
                $coaNama = $v['coa_nama'];
                $data[$coa]['coa_kode'] = $coa;
                $data[$coa]['coa_nama'] = $coaNama;
                $data[$coa]['debit'] += $v['debit'];
                $data[$coa]['credit'] += $v['credit'];
            }
        }

        ksort($data);

        $title = "For ";
        if ($prjKode)
            $title .= "Project $prjKode ";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export) {
            $this->view->title = $title;

            $this->view->data = $data;

            if (!$detailTrans) {
                $this->render('view-trial-balancesheet');
            } else {
                $this->render('view-trial-balancesheet-trans');
            }
        } else {
            $newData = array();
            $no = 1;
            if (!$detailTrans) {
                $totalDebit = 0;
                $totalCredit = 0;
                foreach ($data as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "COA Code" => $v['coa_kode'] . " - " . $v['coa_nama'],
                        "Debit" => $v['debit'],
                        "Credit" => $v['credit']
                    );
                    $no++;
                    $totalCredit += $v['credit'];
                    $totalDebit += $v['debit'];
                }

                //Total...
                $newData[] = array(
                    "No" => '',
                    "COA Code" => "TOTAL",
                    "Debit" => $totalDebit,
                    "Credit" => $totalCredit
                );


                QDC_Adapter_Excel::factory(array(
                            "fileName" => "Trial Balance Sheet " . $title
                        ))
                        ->setCellFormat(array(
                            2 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            3 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            )
                        ))
                        ->write($newData)->toExcel5Stream();
            } else {
                $totalDebit = 0;
                $totalCredit = 0;

                foreach ($data as $key => $val) {
                    foreach ($val as $k => $v) {
                        $newData[] = array(
                            "No" => $no,
                            "Trano" => $v['trano'],
                            "Ref Number" => $v['ref_number'],
                            "Date Posting" => $v['tglpost'],
                            "Date Transaction" => $v['tgl'],
                            "COA Code" => $v['coa_kode'],
                            "COA Name" => $v['coa_nama'],
                            "Debit" => $v['debit'],
                            "Credit" => $v['credit']
                        );
                        $no++;
                        $totalCredit += $v['credit'];
                        $totalDebit += $v['debit'];
                    }
                }

                //Total...
                $newData[] = array(
                    "No" => '',
                    "Trano" => 'TOTAL',
                    "Ref Number" => '',
                    "Date Posting" => '',
                    "Date Transaction" => '',
                    "COA Code" => '',
                    "COA Name" => '',
                    "Debit" => $totalDebit,
                    "Credit" => $totalCredit
                );

                QDC_Adapter_Excel::factory(array(
                            "fileName" => "Trial Balance Sheet " . $title
                        ))
                        ->setCellFormat(array(
                            7 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            ),
                            8 => array(
                                "cell_type" => "numeric",
                                "cell_operation" => "setFormatCode",
                                "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                            )
                        ))
                        ->write($newData)->toExcel5Stream();
            }
        }
    }

    public function detailJournalAction() {
        
    }

    public function viewDetailJournalAction() {
        $coaKode = $this->_getParam("coa_kode");
        $prjKode = $this->_getParam("prj_kode");
        $trano = $this->_getParam("trano");
        $month = $this->_getParam("month");
        $export = ($this->_getParam("export") != '') ? true : false;
        if ($coaKode)
            $where[] = "coa_kode = '$coaKode'";

        if ($prjKode)
            $where[] = "prj_kode = '$prjKode'";

        if ($trano)
            $where[] = "ref_number = '$trano'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($where)
            $where = implode(" AND ", $where);

        $res = QDC_Finance_Jurnal::factory()->getAllJurnal($where);
        $result = $res['jurnal'];
        $data = array();
        foreach ($result as $k => $v) {
            $coa = $v['coa_kode'];
            $coaNama = $v['coa_nama'];
            $data[$coa]['coa_kode'] = $coa;
            $data[$coa]['coa_nama'] = $coaNama;
            if ($v['is_valueexchange'])
                continue;

            if ($v['credit_conversion'] != 0)
                $data[$coa]['credit'] += floatval($v['credit_conversion']);
            else
                $data[$coa]['credit'] += floatval($v['credit']);

            if ($v['debit_conversion'] != 0)
                $data[$coa]['debit'] += floatval($v['debit_conversion']);
            else
                $data[$coa]['debit'] += floatval($v['debit']);
        }

        ksort($data);

        $title = "For ";
        if ($prjKode)
            $title .= "Project $prjKode ";
        if ($coaKode)
            $title .= "COA Code $coaKode ";
        if ($trano)
            $title .= "Trano $trano";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export) {
            $this->view->title = $title;
            $this->view->data = $data;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => $v['coa_nama'],
                    "Debit" => $v['debit'],
                    "Credit" => $v['credit']
                );
                $no++;
                $totalCredit += $v['credit'];
                $totalDebit += $v['debit'];
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "COA Code" => "TOTAL",
                "COA Name" => "",
                "Debit" => $res['total_debit'],
                "Credit" => $res['total_credit']
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Detail Journal " . $title
                    ))
                    ->setCellFormat(array(
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewDetailJournalTransAction() {
        $coaKode = $this->_getParam("coa_kode");
        $prjKode = $this->_getParam("prj_kode");
        $tranoSearch = $this->_getParam("trano");
        $month = $this->_getParam("month");

        $export = ($this->_getParam("export") != '') ? true : false;

        if ($coaKode)
            $where[] = "coa_kode = '$coaKode'";

        if ($prjKode)
            $where[] = "prj_kode = '$prjKode'";

        if ($tranoSearch)
            $where[] = "ref_number = '$tranoSearch'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($where)
            $where = implode(" AND ", $where);

        $res = QDC_Finance_Jurnal::factory()->getAllJurnal($where);
        $result = $res['jurnal'];

        $data = array();
        foreach ($result as $k => $v) {
            $trano = $v['trano'];
            if ($trano == '')
                $trano = 'NO TRANO';
            $data[$trano][] = $v;
        }

        ksort($data);

        $title = "For ";
        if ($prjKode)
            $title .= "Project $prjKode ";
        if ($coaKode)
            $title .= "COA Code $coaKode ";
        if ($tranoSearch)
            $title .= "Trano $tranoSearch";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export) {
            $this->view->title = $title;
            $this->view->data = $data;
            $this->view->totalDebit = $res['total_debit'];
            $this->view->totalCredit = $res['total_credit'];
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($data as $key => $val) {
                foreach ($val as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "Trano" => $v['trano'],
                        "Ref Number" => $v['ref_number'],
                        "Date" => $v['tgl'],
                        "COA Code" => $v['coa_kode'],
                        "COA Name" => $v['coa_nama'],
                        "Debit" => $v['debit'],
                        "Credit" => $v['credit'],
                        "Debit Conversion" => $v['debit_conversion'],
                        "Credit Conversion" => $v['credit_conversion']
                    );
                    $no++;
                    if ($v['is_valueexchange'])
                        continue;

                    if ($v['credit_conversion'] != 0)
                        $totalcredit += floatval($v['credit_conversion']);
                    else
                        $totalcredit += floatval($v['credit']);

                    if ($v['debit_conversion'] != 0)
                        $totaldebit += floatval($v['debit_conversion']);
                    else
                        $totaldebit += floatval($v['debit']);
                }
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Trano" => "TOTAL",
                "Ref Number" => "",
                "Date" => "",
                "COA Code" => "",
                "COA Name" => "",
                "Debit" => $res['total_debit'],
                "Credit" => $res['total_credit']
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Detail Journal per Transaction " . $title
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function profitLossPreviewAction() {
        
    }

    public function viewProfitLossPreviewAction() {
        $prjKode = $this->_getParam("prj_kode");
        $where = array();
        if ($prjKode)
            $where[] = "prj_kode = '$prjKode'";

        $startdate = $this->_getParam("startdate");
        $enddate = $this->_getParam("enddate");
        $export = ($this->_getParam("export") != '') ? true : false;

        if ($startdate && $enddate) {
            $month = date("M", strtotime($startdate));
            $this->view->startdate = $startdate;
            $this->view->enddate = $enddate;
            $this->view->periode = date("d M Y", strtotime($startdate)) . " - " . date("d M Y", strtotime($enddate));
            $where[] = "tgl BETWEEN '$startdate 00:00:00' AND '$enddate 23:59:59'";
        }

        if ($where)
            $where = implode(" AND ", $where);

        //get all jurnal saldo
        $allJurnal = QDC_Finance_Jurnal::factory()->getAllJurnalWithStoreProcedure($where);
        $jurnal = $allJurnal['jurnal'];

        foreach ($jurnal as $k => $v) {
            if ($v['is_valueexchange'])
                continue;

            if ($v['credit_conversion'] != 0)
                $jurnal[$k]['credit'] = floatval($v['credit_conversion']);

            if ($v['debit_conversion'] != 0)
                $jurnal[$k]['debit'] = floatval($v['debit_conversion']);
        }

        //define & get P&L value
        $RL = new Finance_Models_AccountingSaldoRL();
        $RLJurnal = $RL->filterProfitLoss(array("data_coa" => $jurnal));

        //get construction project saldo
//        $accountingSaldoConst = new Finance_Models_AccountingSaldoConstruction();
//        $dataConstruct = $accountingSaldoConst->getSaldoWithPeriode($prjKode, $startdate, $enddate);
        //update construction saldo to P&L 
//        $newdata = $RL->updateCoaToFilteredSaldo($RLJurnal, $dataConstruct, false);


        $PL = QDC_Finance_ProfitLoss::factory();
        $newRLJurnal = $PL->getAllTotalGrossOperatingNet(array(
            "total_already_count" => true,
            "dataArray" => $RLJurnal,
            "total_from_array" => true,
            "fromArray" => true
        ));
        foreach ($newRLJurnal as $k => $v) {
            if ($v['coa_kode'] !== '')
                $newRLJurnal[$k]['total'] = $PL->numberFormatByValue($v['total']);
        }

        if (!$export) {
            $this->view->prjKode = $prjKode;
            $this->view->result = $newRLJurnal;
        } else {

            $title = $prjKode . " " . $startdate . '-' . $enddate;
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            foreach ($newRLJurnal as $k => $v) {
                if ($v['total'] == '') {
                    if ($v['grandtotal'] == '')
                        $total = 0;
                    else
                        $total = $v['grandtotal'];
                } else
                    $total = $v['total'];
                $newData[] = array(
                    "No" => $no,
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => ($v['coa_nama'] != '') ? $v['coa_nama'] : $v['text'],
                    "Total" => $total
                );
                $no++;
            }

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Profit & Loss Preview " . $title
                    ))
                    ->setCellFormat(array(
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewDetailProfitLossPreviewAction() {
        $prjKode = $this->_getParam("prj_kode");
        $coaKode = $this->_getParam("coa_kode");
        $where = array();
        if ($prjKode)
            $where[] = "prj_kode = '$prjKode'";

        if ($coaKode)
            $where[] = "coa_kode = '$coaKode'";

//        $month = $this->_getParam("month");
//        $export = ($this->_getParam("export") != '') ? true : false;
//
//        if ($month) {
//            $date = new DateTime($month . "-" . "01");
//            $date->add(new DateInterval('P1M'));
//            $date->sub(new DateInterval('P1D'));
//
//            $tglAwal = $month . "-" . "01 00:00:00";
//            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
//
//            $this->view->month = $month;
//            $this->view->periode = date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));
//            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
//        }
        $startdate = $this->_getParam("startdate");
        $enddate = $this->_getParam("enddate");
        $export = ($this->_getParam("export") != '') ? true : false;

        if ($startdate && $enddate) {
            $month = date("M", strtotime($startdate));
            $this->view->startdate = $startdate;
            $this->view->enddate = $enddate;
            $this->view->periode = date("d M Y", strtotime($startdate)) . " - " . date("d M Y", strtotime($enddate));
            $where[] = "tgl BETWEEN '$startdate 00:00:00' AND '$enddate 23:59:59'";
        }

        if ($where)
            $where = implode(" AND ", $where);


        $data = QDC_Finance_Jurnal::factory()->getAllJurnal($where);

        $RL = new Finance_Models_AccountingSaldoRL();
        $newdata = $data['jurnal'];
        $summary = $data;


//        $accountingSaldoConst = new Finance_Models_AccountingSaldoConstruction();
//        $dataConstruct = $accountingSaldoConst->getSaldoWithPeriode($prjKode, $tglAwal, $tglAkhir);
//        $detail = true;
//        $newdata = $RL->updateCoaToFilteredSaldo($allJurnal, $dataConstruct, $detail);

        $result = array();
        foreach ($newdata as $k => $v) {

            if ($v['tgl'] != '')
                $tgl = $v['tgl'];
            else
                $tgl = 0;

            $v['ref_number'] = QDC_Common_String::factory()->wordWrap($v['ref_number']);
            $result[$tgl][] = $v;
        }
        $coa = $this->FINANCE->MasterCoa->fetchRow("coa_kode = '$coaKode'");
        if ($coa) {
            $this->view->coaNama = $coa['coa_nama'];
            $this->view->dk = $coa['dk'];
        }

        ksort($result);

        if (!$export) {
            $this->view->prjKode = $prjKode;
            $this->view->coaKode = $coaKode;
            $this->view->result = $result;
            $this->view->totaldebit = $summary['total_debit'];
            $this->view->totalcredit = $summary['total_credit'];
        } else {
            $title = $prjKode . " " . $month;
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            $totDebit = 0;
            $totCredit = 0;
            foreach ($result as $key => $val) {
                foreach ($val as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "Date" => ($v['tgl'] != '') ? date("d M Y", strtotime($v['tgl'])) : "",
                        "Trano" => ($v['trano'] != '') ? $v['trano'] : "",
                        "Ref Number" => ($v['ref_number'] != '') ? $v['ref_number'] : "",
                        "Debit" => $v['debit'],
                        "Credit" => $v['credit'],
                    );

                    $totDebit += $v['debit'];
                    $totCredit += $v['credit'];
                    $no++;
                }
            }

            //Total
            $newData[] = array(
                "No" => "",
                "Date" => "TOTAL",
                "Trano" => "",
                "Ref Number" => "",
                "Debit" => $summary['total_debit'],
                "Credit" => $summary['total_credit'],
            );

            //PNL Value
            if ($coa['dk'] == 'Debit')
                $totCredit = -1 * $totCredit;
            else
                $totDebit = -1 * $totDebit;

            $total = $totCredit + $totDebit;
            $newData[] = array(
                "No" => "",
                "Date" => "Profit Loss Value",
                "Trano" => "",
                "Ref Number" => "",
                "Debit" => "",
                "Credit" => $total,
            );

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Profit & Loss Preview Detail " . $title
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function generalLedgerDetailAction() {
        
    }

    public function viewGeneralLedgerDetailAction() {
        $month = $this->_getParam("month");
        $coa_kode = $this->_getParam("coa_kode");
        $prj_kode = $this->_getParam("prj_kode");
        $tgl_awal = $this->_getParam("tgl_awal");
        $tgl_akhir = $this->_getParam("tgl_akhir");

        $coa = array();
        $coa = explode(",", $coa_kode);

        $limit = 50;

        //Paging Stuff
        $current = $this->_getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->_getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->_getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * $limit;

        //End of paging
        $newcoa = '';

        foreach ($coa as $key => $val) {
            $coa_kode = "'$val'";
            if ($newcoa != '')
                $newcoa = $newcoa . "," . $coa_kode;
            else
                $newcoa = $coa_kode;
        }

        if ($newcoa)
            $where[] = "coa_kode IN($newcoa)";
        if ($prj_kode)
            $where[] = "prj_kode = '$prj_kode'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($tgl_awal && $tgl_akhir) {
            $where[] = "tgl BETWEEN '$tgl_awal 00:00:00' AND '$tgl_akhir 23:59:59'";
        }

        if ($where)
            $where = implode(" AND ", $where);


        $result = QDC_Finance_Jurnal::factory()->getAllJurnalWithLimit($where, $offset, $limit);
        $data = $result['jurnal'];

        foreach ($data as $k => $v) {
            $data[$k]['transaction'] = QDC_Finance_Jurnal::factory()->getJurnalType($v['jenis_jurnal']);
        }

        $count = $result['count'];

        if ($month)
            $title = "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if ($tgl_awal && $tgl_akhir) {
            $title = "Periode " . date("d M Y", strtotime($tgl_awal)) . " - " . date("d M Y", strtotime($tgl_akhir));
        }

        $this->view->title = $title;
        $this->view->data = $data;
        $this->view->totalDebit = $result['total_debit'];
        $this->view->totalCredit = $result['total_credit'];

        //Paging stuff.
        $this->view->limitPerPage = $limit;
        $this->view->totalResult = $count;
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
        //End of paging
    }

    public function printGeneralLedgerDetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $month = $this->_getParam("month");
        $coa_kode = $this->_getParam("coa_kode");
        $prj_kode = $this->_getParam("prj_kode");
        $tgl_awal = $this->_getParam("tgl_awal");
        $tgl_akhir = $this->_getParam("tgl_akhir");
        $type = $this->_getParam("type");

        $coa = array();
        $coa = explode(",", $coa_kode);

        $newcoa = '';

        foreach ($coa as $key => $val) {
            $coa_kode = "'$val'";
            if ($newcoa != '')
                $newcoa = $newcoa . "," . $coa_kode;
            else
                $newcoa = $coa_kode;
        }

        if ($newcoa)
            $where[] = "coa_kode IN($newcoa)";

        if ($prj_kode)
            $where[] = "prj_kode = '$prj_kode'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01";
            $tglAkhir = $date->format('Y-m-d');
            $where[] = "tgl BETWEEN '$tglAwal 00:00:00' AND '$tglAkhir 23:59:59'";
        }

        if ($tgl_awal && $tgl_akhir) {
            $where[] = "tgl BETWEEN '$tgl_awal 00:00:00' AND '$tgl_akhir 23:59:59'";
        }

        if ($where)
            $where = implode(" AND ", $where);

        $result = QDC_Finance_Jurnal::factory()->getAllJurnal($where);
        $data = $result['jurnal'];


        $totaldebit = 0;
        $totalcredit = 0;
        $arrayData = array();
        foreach ($data as $k => $v) {
            $arrayData[] = array(
                "trano" => $v['trano'],
                "tgl" => date("d M Y", strtotime($v['tgl'])),
                "debit" => floatval($v['debit']),
                "credit" => floatval($v['credit']),
                "coa_kode" => $v['coa_kode'],
                "coa_nama" => $v['coa_nama'],
                "job_number" => $v['job_number'],
                "ref_number" => $v['ref_number']
            );
            if ($v['debit_conversion'] != 0 || $v['credit_conversion'] != 0)
                $arrayData[] = array(
                    "trano" => $v['trano'],
                    "tgl" => date("d M Y", strtotime($v['tgl'])),
                    "debit" => floatval($v['debit_conversion']),
                    "credit" => floatval($v['credit_conversion']),
                    "coa_kode" => $v['coa_kode'] . ' (Conversion)',
                    "coa_nama" => $v['coa_nama'],
                    "job_number" => $v['job_number'],
                    "ref_number" => $v['ref_number']
                );



//            if ($v['credit_conversion'] != 0)
//                $totalcredit += floatval($v['credit_conversion']);
//            else
//                $totalcredit += floatval($v['credit']);
//
//            if ($v['debit_conversion'] != 0)
//                $totaldebit += floatval($v['debit_conversion']);
//            else
//                $totaldebit += floatval($v['debit']);
        }

        if ($type == 'pdf') {
            if ($month)
                $title = "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

            if ($tgl_awal && $tgl_akhir) {
                $title = "Periode " . date("d M Y", strtotime($tgl_awal)) . " - " . date("d M Y", strtotime($tgl_akhir));
            }

            $signature = $this->_helper->getHelper('token')->generateDocumentSignature();

            $DEFAULT = QDC_Model_Default::init(array("MasterPt"));
            $pt = $DEFAULT->MasterPt->fetchRow()->toArray();
            $params = array(
                "nama" => $pt['nama'],
                "alamat1" => $pt['alamat1'],
                "alamat2" => $pt['alamat2'],
                "title" => "GENERAL LEDGER DETAIL",
                "sub_title" => $title,
                "date" => date("d M Y"),
                "time" => date("H:i:s A"),
                "signature" => $signature,
                "pic" => QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName()
            );

            QDC_Jasper_Report::factory(
                    array(
                        "reportType" => 'pdf',
                        "arrayData" => $arrayData,
                        "arrayParams" => $params,
                        "fileName" => "jurnal.jrxml",
                        "outputName" => 'GENERAL LEDGER DETAIL',
                        "dataSource" => 'NoDataSource'
                    )
            )->generate();
        } else if ($type == 'xls') {
            $arrayData[] = array(
                "trano" => "",
                "tgl" => "Total",
                "debit" => $result['total_debit'],
                "credit" => $result['total_credit'],
                "coa_kode" => "",
                "coa_nama" => "",
                "job_number" => "",
                "ref_number" => ""
            );
            QDC_Adapter_Excel::factory(array(
                        "fileName" => "General Ledger Detail "
                    ))
                    ->setCellFormat(array(
                        2 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($arrayData)->toExcel5Stream();
        }
    }
    
    public function viewDetailPaymentReportAction(){
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $filedata = $this->files->fetchAll("ref_number = '$trano'");
        
        $fetchmain=array();
        $fetchbpv = array();
        $fetchpayment = array();

        if($type=='ARF')
        {
           $query=" SELECT trano, tgl,val_kode, prj_kode, sit_kode,SUM(COALESCE(qty*harga,0)) AS value
                    FROM erpdb.procurement_arfd
                    WHERE trano='$trano' AND deleted=0";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchmain = $fetch->fetchAll();
            }

            $query="SELECT trano, valuta, COALESCE(total,0) AS total,holding_tax_val,deduction
                    FROM erpdb.finance_payment_voucher
                    WHERE ref_number='$trano' AND status_bpv_wht=0 AND deleted=0";

            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchbpv = $fetch->fetchAll();
            }

            $query="SELECT trano,tgl,val_kode, COALESCE(SUM(total_bayar),0) AS total_payment
                    FROM erpdb.finance_payment_arf WHERE doc_trano='$trano' GROUP BY trano";
            $fetch = $this->db->query($query);

            if ($fetch) {
                $fetchpayment = $fetch->fetchAll();
            }
        }

        if($type=='BRF')
        {           

            $query=" SELECT trano, tgl,val_kode, prj_kode, sit_kode,SUM(COALESCE(qty*harga,0)) AS value
                    FROM erpdb.procurement_brfd
                    WHERE trano='$trano' AND deleted=0";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchmain = $fetch->fetchAll();
            }
            
            
            if ($this->isPartial($trano) == 0) {

            $query = "SELECT pv.trano, pv.valuta, COALESCE(pv.total,0) AS total
                    FROM erpdb.finance_payment_voucher pv
                    INNER JOIN erpdb.procurement_brfd bp ON (bp.trano = pv.ref_number AND bp.deleted=0 AND bp.trano='$trano')
                    WHERE pv.status_bpv_wht=0 AND pv.deleted=0";

            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchbpv = $fetch->fetchAll();
            }

            $query = "SELECT parf.trano,parf.tgl,parf.val_kode, COALESCE(SUM(parf.total_bayar),0) AS total_payment
                    FROM erpdb.finance_payment_arf parf
                    INNER JOIN erpdb.procurement_brfd bp ON (bp.trano = parf.doc_trano AND bp.deleted=0 AND  bp.trano='$trano') GROUP BY parf.trano ";
            $fetch = $this->db->query($query);
            
            }else{
            
            $query = "SELECT pv.trano, pv.valuta, COALESCE(pv.total,0) AS total
                    FROM erpdb.finance_payment_voucher pv
                    INNER JOIN erpdb.procurement_brfd_payment bp ON (bp.trano = pv.ref_number AND bp.deleted=0 AND bp.trano_ref='$trano')
                    WHERE pv.status_bpv_wht=0 AND pv.deleted=0";

            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchbpv = $fetch->fetchAll();
            }

            $query="SELECT parf.trano,parf.tgl,parf.val_kode, COALESCE(SUM(parf.total_bayar),0) AS total_payment
                    FROM erpdb.finance_payment_arf parf
                    INNER JOIN erpdb.procurement_brfd_payment bp ON (bp.trano = parf.doc_trano AND bp.deleted=0 AND  bp.trano_ref='$trano') GROUP BY parf.trano ";
            $fetch = $this->db->query($query);
            }
            
            if ($fetch) {
                $fetchpayment = $fetch->fetchAll();
            }
        }

        if($type=='RPI')
        {
            $query=" SELECT trano, tgl,val_kode, prj_kode, sit_kode,SUM(COALESCE(qty*harga,0)) AS value,SUM(totalwht) AS holding_tax_val,SUM(ppn) AS ppn, SUM(total_deduction) AS deduction
                    FROM erpdb.procurement_rpid
                    WHERE trano='$trano' AND deleted=0 AND prj_kode='$prj_kode' AND sit_kode='$sit_kode'";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchmain = $fetch->fetchAll();
            }

            $query="SELECT trano, valuta, COALESCE(total,0) AS total
                    FROM erpdb.finance_payment_voucher
                    WHERE ref_number='$trano' AND status_bpv_wht=0 AND deleted=0";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchbpv = $fetch->fetchAll();
            }

            $query="SELECT trano,tgl,val_kode, COALESCE(SUM(total_bayar),0) AS total_payment
                    FROM erpdb.finance_payment_rpi WHERE doc_trano='$trano' GROUP BY trano";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchpayment = $fetch->fetchAll();
            }
        }

        if($type=='REM')
        {
            $query=" SELECT trano, tgl,val_kode, prj_kode, sit_kode,SUM(COALESCE(qty*harga,0)) AS value
                    FROM erpdb.procurement_reimbursementd
                    WHERE trano='$trano' AND deleted=0";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchmain = $fetch->fetchAll();
            }

            $query="SELECT trano, valuta, COALESCE(total,0) AS total
                    FROM erpdb.finance_payment_voucher
                    WHERE ref_number='$trano' AND status_bpv_wht=0 AND deleted=0";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchbpv = $fetch->fetchAll();
            }

            $query="SELECT trano,tgl,val_kode, COALESCE(SUM(total),0) AS total_payment
                    FROM erpdb.finance_payment_reimbursement WHERE rem_no='$trano' GROUP BY trano";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetchpayment = $fetch->fetchAll();
            }
        }

        $this->view->file = $filedata;
        $this->view->type = $type;

        $this->view->datamain = $fetchmain;
        $this->view->databpv = $fetchbpv;
        $this->view->datapayment = $fetchpayment;
        
    }

    public function viewDetailPaymentReportOldAction() {
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');

        if ($trano != '') {
            $search[] = "PV.ref_number = '$trano'";
            $search2[] = "trano = '$trano'";
            $search4[] = "pv.ref_number= '$trano'";
            $search3[] = "arf.trano_ref= '$trano'";
        }

        if (count($search) > 0)
            $searchdunk = " WHERE " . implode(" AND ", $search);

        if ($type == 'RPI') {
            if (count($search2) > 0)
                $searchdunk = " WHERE " . implode(" AND ", $search2);
            $query = "
              DROP TEMPORARY TABLE IF EXISTS `rpiku`;
              CREATE TEMPORARY TABLE `rpiku`
              SELECT SQL_CALC_FOUND_ROWS * FROM procurement_rpih $searchdunk AND deleted=0

              ORDER BY trano DESC
            ";
            $this->db->query($query);

            $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

            $query = "
            SELECT
                SQL_CALC_FOUND_ROWS a.trano AS ref_number,
                a.prj_kode,
                a.sit_kode,
                a.sup_nama,
                a.total as trano_value,
                a.ppn as ppn_value,
                a.total_bpv,
                a.ref_number,
                a.holding_tax_val,
                a.deduction,
                a.val_kode,
                a.val_kode_bpv,
                a.tgl,
                a.bpv_trano,
                sum(PR.total_bayar) as total_payment,
                PR.tgl AS tgl_payment,
                PR.val_kode AS val_kode_payment,
                PR.trano as payment_trano
            FROM
                (
                SELECT
                    rpi.trano,
                    rpi.tgl,
                    rpi.prj_kode,
                    rpi.sit_kode,
                    rpi.sup_nama,
                    rpi.total,
                    rpi.ppn,
                    rpi.val_kode,
                    SUM(PV.total) AS total_bpv,
                    PV.ref_number,
                    PV.trano as bpv_trano,
                    SUM(PV.holding_tax_val) AS holding_tax_val,
                    SUM(PV.deduction) AS deduction,
                    PV.valuta AS val_kode_bpv
                FROM
                    rpiku rpi
                LEFT JOIN
                    finance_payment_voucher PV
                ON (rpi.trano = PV.ref_number)
                WHERE PV.deleted=0
                GROUP BY rpi.trano
                ORDER BY rpi.tgl DESC
                ) a
            LEFT JOIN
                finance_payment_rpi PR
            ON (a.ref_number= PR.doc_trano and a.bpv_trano = PR.voc_trano)
            WHERE PR.deleted=0
            GROUP BY a.trano,PR.trano
            ORDER BY a.tgl DESC;
            ";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
            }
        } else if ($type == 'BRF') {
            
            $query = "DROP TEMPORARY TABLE IF EXISTS `brfku`;
                        CREATE TEMPORARY TABLE `brfku`
                        SELECT tgl, trano, SUM(total) AS total FROM erpdb.procurement_brfd WHERE trano='$trano'";
            $this->db->query($query);
            
            if (count($search2) > 0)
                $searchdunk = " WHERE " . implode(" AND ", $search3);
            
            $query = "SELECT brfku.trano AS ref_number,pv.prj_kode AS prj_kode,pv.sit_kode AS sit_kode,
                        brfku.total AS trano_value,pv.total AS total_bpv, COALESCE(parf.total_bayar,0) AS total_payment,brfku.tgl AS tgl,arf.val_kode AS val_kode,pv.trano AS bpv_trano,
                        parf.trano AS payment_trano,parf.tgl AS tgl_payment,arf.val_kode AS val_kode_bpv
                        FROM erpdb.finance_payment_voucher pv 
                        LEFT JOIN erpdb.procurement_arfh arf ON (arf.trano=pv.ref_number)
                        LEFT JOIN brfku ON (brfku.trano = arf.trano_ref)
                        LEFT JOIN erpdb.finance_payment_arf parf ON (parf.voc_trano = pv.trano) $searchdunk";
            
            $fetch = $this->db->query($query);
            if ($fetch) {
               $fetch = $fetch->fetchAll();
            }
            
        }else if ($type == 'ARF') {
                
            
            if (count($search2) > 0)
                $searchdunk = " WHERE " . implode(" AND ", $search4);
            /*$query = "
              DROP TEMPORARY TABLE IF EXISTS `arfku`;
              CREATE TEMPORARY TABLE `arfku`
              SELECT SQL_CALC_FOUND_ROWS * FROM procurement_arfh $searchdunk

              ORDER BY trano DESC;
            ";
            $this->db->query($query);

            $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

            $query = "
                SELECT
                    SQL_CALC_FOUND_ROWS a.trano AS ref_number,
                    a.prj_kode,
                    a.sit_kode,
                    a.total as trano_value,
                    sum(a.total_bpv) as total_bpv,
                    sum(PR.total) as total_payment,
                    0 AS ppn_value,
                    a.holding_tax_val,
                    a.deduction,
                    a.val_kode,
                    a.val_kode_bpv,
                    a.bpv_trano,
                    a.tgl,
                    PR.tgl AS tgl_payment,
                    PR.val_kode AS val_kode_payment,
                    PR.trano as payment_trano
                FROM
                (
                    SELECT
                        arf.trano,
                        arf.tgl,
                        arf.prj_kode,
                        arf.sit_kode,
                        arf.total,
                        arf.val_kode,
                        PV.trano as bpv_trano,
                        PV.valuta AS val_kode_bpv,
                        PV.ref_number,
                        PV.total AS total_bpv,
                        PV.holding_tax_val AS holding_tax_val,
                        PV.deduction AS deduction
                    FROM
                        arfku arf
                    LEFT JOIN
                        finance_payment_voucher PV
                    ON
                        (arf.trano = PV.ref_number AND PV.deleted=0)
                ) a
                LEFT JOIN
                    finance_payment_arf PR
                ON
                   a.ref_number= PR.doc_trano and a.bpv_trano = PR.voc_trano
                GROUP BY a.trano,PR.trano
                ORDER BY a.tgl DESC;
                ";*/
              $query = "SELECT pv.ref_number AS ref_number,pv.prj_kode AS prj_kode,pv.sit_kode AS sit_kode,
                        arf.total AS trano_value,pv.total AS total_bpv, COALESCE(parf.total_bayar,0) AS total_payment,arf.tgl AS tgl,arf.val_kode AS val_kode,pv.trano AS bpv_trano,
                        parf.trano AS payment_trano,parf.tgl AS tgl_payment,arf.val_kode AS val_kode_bpv
                        FROM erpdb.finance_payment_voucher pv 
                        LEFT JOIN erpdb.procurement_arfh arf ON (arf.trano=pv.ref_number)
                        LEFT JOIN erpdb.finance_payment_arf parf ON (parf.voc_trano = pv.trano) $searchdunk";
              
            $fetch = $this->db->query($query);
            if ($fetch) {
               $fetch = $fetch->fetchAll();
            }
            
        } else if ($type == 'REM') {
            if (count($search2) > 0)
                $searchdunk = " WHERE " . implode(" AND ", $search2);
            $query = "
              DROP TEMPORARY TABLE IF EXISTS `remku`;
              CREATE TEMPORARY TABLE `remku`
              SELECT SQL_CALC_FOUND_ROWS * FROM procurement_reimbursementh $searchdunk

              ORDER BY trano DESC;
            ";
            $this->db->query($query);

            $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

            $query = "
                SELECT
                    SQL_CALC_FOUND_ROWS a.trano AS ref_number,
                    a.prj_kode,
                    a.sit_kode,
                    a.bpv_trano,
                    a.total as trano_value,
                    sum(a.total_bpv) as total_bpv,
                    sum(PR.total) as total_payment,
                    0 AS ppn_value,
                    PR.trano as payment_trano
                FROM
                (
                    SELECT
                        rem.trano,
                        rem.tgl,
                        rem.prj_kode,
                        rem.sit_kode,
                        rem.total,
                        rem.val_kode,
                        PV.total AS total_bpv,
                        PV.valuta AS val_kode_bpv,
                        PV.ref_number,
                        PV.trano as bpv_trano
                    FROM
                        remku rem
                    LEFT JOIN
                        finance_payment_voucher PV
                    ON
                        (rem.trano = PV.ref_number AND PV.deleted=0)
                ) a
                LEFT JOIN
                    finance_payment_reimbursement PR
                ON 
                    a.trano= PR.rem_no and a.bpv_trano = PR.voc_trano
                GROUP BY a.trano,PR.trano
                ORDER BY a.tgl DESC;
            ";

            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
            }
        } else if ($type == 'PPNREM') {
            if (count($search2) > 0)
                $searchdunk = " WHERE " . implode(" AND ", $search2);
            $query = "
              DROP TEMPORARY TABLE IF EXISTS `ppnremku`;
              CREATE TEMPORARY TABLE `ppnremku`
              SELECT SQL_CALC_FOUND_ROWS * FROM finance_ppn_reimbursementh $searchdunk

              ORDER BY trano DESC;
            ";
            $this->db->query($query);

            $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

            $query = "
                SELECT
                    SQL_CALC_FOUND_ROWS a.trano AS ref_number,
                    a.prj_kode,
                    a.sit_kode,
                    a.bpv_trano,
                    a.total as trano_value,
                    sum(a.total_bpv) as total_bpv,
                    sum(PR.total) as total_payment,
                    0 AS ppn_value,
                    PR.trano as payment_trano
                FROM
                (
                    SELECT
                        rem.trano,
                        rem.tgl,
                        rem.prj_kode,
                        rem.sit_kode,
                        rem.total,
                        rem.val_kode,
                        PV.total AS total_bpv,
                        PV.valuta AS val_kode_bpv,
                        PV.ref_number,
                        PV.trano as bpv_trano
                    FROM
                        ppnremku rem
                    LEFT JOIN
                        finance_payment_voucher PV
                    ON
                        (rem.trano = PV.ref_number AND PV.deleted=0)
                ) a
                LEFT JOIN
                    finance_payment_ppn_reimbursement PR
                ON
                    a.trano= PR.doc_trano and a.bpv_trano = PR.voc_trano
                GROUP BY a.trano,PR.trano
                ORDER BY a.tgl DESC;
            ";

            $fetch = $this->db->query($query);
            if ($fetch) {
               $fetch = $fetch->fetchAll();
            }
        }

        $this->view->type = $type;
        if ($fetch) {
            $this->view->data = $fetch;
        }
    }

    public function coToInvoiceAction() {
        
    }

    public function viewCoToInvoiceAction() {
        $this->_helper->layout->disableLayout();

        $prjKode = $this->getRequest()->getParam('prj_kode');
        $sitKode = $this->getRequest()->getParam('sit_kode');
        $all = ($this->getRequest()->getParam('all') != '') ? true : false;
        $group = ($this->getRequest()->getParam('group') != '') ? true : false;

        $data = array();

        if (!$all) {
            $prjNama = $this->DEFAULT->MasterProject->getProjectName($prjKode);

            if ($sitKode == '') {
                $sites = $this->DEFAULT->MasterSite->getList($prjKode);
                foreach ($sites as $k => $v) {
                    $sitKode = $v['sit_kode'];
                    $sitNama = $this->DEFAULT->MasterSite->getSiteName($prjKode, $sitKode);
                    $result = $this->FINANCE->Invoice->getTotalInvoice($prjKode, $sitKode);
                    $boq2 = $this->DEFAULT->Budget->getBoq2('summary-current', $prjKode, $sitKode);

                    $data[] = array(
                        "prj_kode" => $prjKode,
                        "prj_nama" => $prjNama,
                        "sit_kode" => $sitKode,
                        "sit_nama" => $sitNama,
                        "boq2_totalIDR" => $boq2['totalCurrentHargaIDR'],
                        "boq2_totalUSD" => $boq2['totalCurrentHargaUSD'],
                        "invoice_totalIDR" => $result['invoice']['totalIDR'],
                        "invoice_totalUSD" => $result['invoice']['totalUSD'],
                        "cancel_invoice_totalIDR" => $result['cancel_invoice']['totalIDR'],
                        "cancel_invoice_totalUSD" => $result['cancel_invoice']['totalUSD']
                    );
                }
            } else {
                $sitNama = $this->DEFAULT->MasterSite->getSiteName($prjKode, $sitKode);
                $result = $this->FINANCE->Invoice->getTotalInvoice($prjKode, $sitKode);
                $boq2 = $this->DEFAULT->Budget->getBoq2('summary-current', $prjKode, $sitKode);

                $data[] = array(
                    "prj_kode" => $prjKode,
                    "prj_nama" => $prjNama,
                    "sit_kode" => $sitKode,
                    "sit_nama" => $sitNama,
                    "boq2_totalIDR" => $boq2['totalCurrentHargaIDR'],
                    "boq2_totalUSD" => $boq2['totalCurrentHargaUSD'],
                    "invoice_totalIDR" => $result['invoice']['totalIDR'],
                    "invoice_totalUSD" => $result['invoice']['totalUSD'],
                    "cancel_invoice_totalIDR" => $result['cancel_invoice']['totalIDR'],
                    "cancel_invoice_totalUSD" => $result['cancel_invoice']['totalUSD'],
                );
            }
        } else {
            $projects = $this->DEFAULT->MasterProject->getProjectNonOverhead();
            foreach ($projects as $k => $v) {
                $prjKode = $v['Prj_Kode'];
                $prjNama = $this->DEFAULT->MasterProject->getProjectName($prjKode);
                $sites = $this->DEFAULT->MasterSite->getList($prjKode);

                foreach ($sites as $k2 => $v2) {
                    $sitKode = $v2['sit_kode'];
                    $sitNama = $this->DEFAULT->MasterSite->getSiteName($prjKode, $sitKode);
                    $result = $this->FINANCE->Invoice->getTotalInvoice($prjKode, $sitKode);
                    $boq2 = $this->DEFAULT->Budget->getBoq2('summary-current', $prjKode, $sitKode);

                    $data[] = array(
                        "prj_kode" => $prjKode,
                        "prj_nama" => $prjNama,
                        "sit_kode" => $sitKode,
                        "sit_nama" => $sitNama,
                        "boq2_totalIDR" => $boq2['totalCurrentHargaIDR'],
                        "boq2_totalUSD" => $boq2['totalCurrentHargaUSD'],
                        "invoice_totalIDR" => $result['invoice']['totalIDR'],
                        "invoice_totalUSD" => $result['invoice']['totalUSD'],
                        "cancel_invoice_totalIDR" => $result['cancel_invoice']['totalIDR'],
                        "cancel_invoice_totalUSD" => $result['cancel_invoice']['totalUSD'],
                    );
                }
            }
        }

        if ($all || $group) {
            $tmp = array();
            foreach ($data as $k => $v) {
                $prjKode = $v['prj_kode'];
                if ($tmp[$prjKode] == '') {
                    $tmp[$prjKode] = array(
                        "prj_kode" => $v["prj_kode"],
                        "prj_nama" => $v["prj_nama"],
                        "sit_kode" => '',
                        "sit_nama" => '',
                        "boq2_totalIDR" => $v["boq2_totalIDR"],
                        "boq2_totalUSD" => $v["boq2_totalUSD"],
                        "invoice_totalIDR" => $v['invoice_totalIDR'],
                        "invoice_totalUSD" => $v['invoice_totalUSD'],
                        "cancel_invoice_totalIDR" => $v['cancel_invoice_totalIDR'],
                        "cancel_invoice_totalUSD" => $v['cancel_invoice_totalUSD'],
                    );
                } else {
                    $tmp[$prjKode]['boq2_totalIDR'] += $v['boq2_totalIDR'];
                    $tmp[$prjKode]['boq2_totalUSD'] += $v['boq2_totalUSD'];
                    $tmp[$prjKode]['invoice_totalIDR'] += $v['invoice_totalIDR'];
                    $tmp[$prjKode]['invoice_totalUSD'] += $v['invoice_totalUSD'];
                    $tmp[$prjKode]['cancel_invoice_totalIDR'] += $v['cancel_invoice_totalIDR'];
                    $tmp[$prjKode]['cancel_invoice_totalUSD'] += $v['cancel_invoice_totalUSD'];
                }
            }

            $data = $tmp;
        }

        //Balance
        foreach ($data as $key => $val) {
            $data[$key]['balance_invoice_totalIDR'] = $val['invoice_totalIDR'] - $val['cancel_invoice_totalIDR'];
            $data[$key]['balance_invoice_totalUSD'] = $val['invoice_totalUSD'] - $val['cancel_invoice_totalUSD'];
            $data[$key]['balance_co_invoice_totalIDR'] = $val['boq2_totalIDR'] - ($val['invoice_totalIDR'] - $val['cancel_invoice_totalIDR']);
            $data[$key]['balance_co_invoice_totalUSD'] = $val['boq2_totalUSD'] - ($val['invoice_totalUSD'] - $val['cancel_invoice_totalUSD']);
        }

        //Total
        $total = array();
        foreach ($data as $k => $v) {
            $total['boq2_totalIDR'] += $v['boq2_totalIDR'];
            $total['boq2_totalUSD'] += $v['boq2_totalUSD'];
            $total['invoice_totalIDR'] += $v['invoice_totalIDR'];
            $total['invoice_totalUSD'] += $v['invoice_totalUSD'];
            $total['cancel_invoice_totalIDR'] += $v['cancel_invoice_totalIDR'];
            $total['cancel_invoice_totalUSD'] += $v['cancel_invoice_totalUSD'];
            $total['balance_invoice_totalIDR'] += $v['balance_invoice_totalIDR'];
            $total['balance_invoice_totalUSD'] += $v['balance_invoice_totalUSD'];
            $total['balance_co_invoice_totalIDR'] += $v['balance_co_invoice_totalIDR'];
            $total['balance_co_invoice_totalUSD'] += $v['balance_co_invoice_totalUSD'];
        }

        $this->view->data = $data;
        $this->view->total = $total;
    }

    public function journalAction() {
        
    }

    public function settlejournalAction() {
        
    }

    public function getSettlementJurnalTranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        $trano = $this->_getParam("trano");
        $ref_number = $this->_getParam("ref_number");
        $type = $this->_getParam("jurnal_type");
        $noUid = ($this->_getParam("no_uid") != 'true') ? false : true;
        $order = ($this->_getParam("order_by") == '') ? "tgl DESC" : $this->_getParam("order_by");

        $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingJurnalSettlement->__name()), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS trano"),
                    "tgl",
                    "uid",
                    "debit" => "SUM(debit)",
                    "credit" => "SUM(credit)",
                    "ref_number"
                ))
                ->order(array($order))
                ->group(array("trano"))
                ->limit($limit, $offset);

        if ($trano) {
            $select = $select->where("trano LIKE '%$trano%'");
        }
        if ($type) {
            $select = $select->where("type LIKE '%$type%'");
        }

        if ($ref_number) {
            $select = $select->where("ref_number LIKE '%$ref_number%'");
        }

        $select = $select->where("stsclose = 0");

        $data = $this->db->fetchAll($select);
        $counter = new Default_Models_MasterCounter();
        foreach ($data as $k => $v) {
            $tranoData = $v['trano'];
            $type = $counter->getTransTypeFlip($tranoData);
            $data[$k]['tgl'] = date("d M Y", strtotime($v['tgl']));
            if ($type) {
                $data[$k]['type'] = $type;
                $data[$k]['name_type'] = $this->jenisJurnal[$type];
            }
            if (!$noUid)
                $data[$k]['person'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
        }

        $result['data'] = $data;
        $result['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        $result['count'] = $result['total'];

        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewJournalAction() {
//        $type = $this->getRequest()->getParam('type');
//        $startdate = $this->getRequest()->getParam('startdate');
//        $enddate = $this->getRequest()->getParam('enddate');
//        $ref_number = $this->getRequest()->getParam('ref_number');
//
//        $this->view->type = $type;
//        $this->view->startdate = $startdate;
//        $this->view->enddate = $enddate;
//        $this->view->ref_number = $ref_number;

        $trano = $this->_getParam("trano");
        $ref = $this->_getParam("ref_number");
        $month = $this->_getParam("month");
        $sd = $this->_getParam("startdate");
        $ed = $this->_getParam("enddate");
        $export = ($this->_getParam("export") != '') ? true : false;
        $print = ($this->_getParam("print") == 'true') ? true : false;
        $type = ($this->_getParam("type"));
        $rpc='';

        $jenisJurnal = ($this->getRequest()->getParam("jurnal_type") != '') ? array($this->getRequest()->getParam("jurnal_type")) : array();

        $current = $this->_getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->_getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->_getParam('requested');
        if ($requested == '')
            $requested = 0;

        $limit = 100;

        $offset = ($currentPage - 1) * $limit;

        $where = array();

        $trano = str_replace("_", "/", $trano);
        
        if ($trano)
            $where[] = "trano = '$trano'";
        if ($ref)
            $where[] = "ref_number = '$ref'";
        if ($type)
            $where[] = "type = '$type'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($sd) {
            $tglAwal = date("Y-m-d 00:00:00", strtotime($sd));
            if ($ed) {
                $tglAkhir = date("Y-m-d 23:59:59", strtotime($ed));
            } else
                $tglAkhir = date("Y-m-d 00:00:00", strtotime($sd));

            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if (count($where) > 0)
            $where = implode(" AND ", $where);

        if (!$export && !$print) {
            $res = QDC_Finance_Jurnal::factory()->getAllJurnalWithLimit($where, $offset, $limit, 'id ASC', '', $jenisJurnal);
            $result = $res['jurnal'];
            $count = $res['count'];
            $summary = $res;
        } elseif ($export || $print) {
            $res = QDC_Finance_Jurnal::factory()->getAllJurnal($where,"id ASC");
            $result = $res['jurnal'];
        }


        $title = "";
        if ($trano)
            $title .= "For $trano ";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export && !$print) {

            if (bccomp($summary['total_credit'], $summary['total_debit'], 2) != 0) {
                $selisih = $summary['total_credit'] - $summary['total_debit'];
                if ($selisih < 0) {
                    $selisih = -1 * $selisih;
                }
                $selisih = "&nbsp;(<b>Variance : " . number_format($selisih, 2) . "</b>)";
            }

            $data = array();
            foreach ($result as $k => $v) {
                $result[$k]['tgl'] = date("d M Y", strtotime($v['tgl']));
                $result[$k]['hash_tgl'] = date("Ymd", strtotime($v['tgl']));
                $result[$k]['ref_number'] = wordwrap($v['ref_number'], 50, "<br>");
                $rpc =$v['ref_number'];
            }

            foreach ($result as $k => $v) {
                $t = $v['trano'];
                $hash = $v['hash_tgl'];
                if ($t == '')
                    $t = 'NO TRANO';
                $data[$hash][$t][] = $v;
            }

            if($rpc !='' OR $rpc !=null)
            {
                $rpc='RPC Number : '.$rpc;
            }

            ksort($data);

            $this->view->title = $title;
            $this->view->data = $data;
            $this->view->rpc = $rpc;
            $this->view->totaldebit = $summary['total_debit'];
            $this->view->totalcredit = $summary['total_credit'];
            $this->view->totalselisih = $selisih;

            $this->view->limitPerPage = $limit;
//            $this->view->totalResult = $this->db->fetchOne("SELECT FOUND_ROWS()");
            $this->view->totalResult = $count;
            $this->view->current = $current;
            $this->view->currentPage = $currentPage;
            $this->view->requested = $requested;
            $this->view->pageUrl = $this->view->url();
            $this->view->pagingParam = array(
                "trano" => $trano,
                "ref_number" => $ref,
                "startdate" => $sd,
                "enddate" => $ed,
                "month" => $month
            );
        } elseif (!$export && $print) {
            $this->_helper->viewRenderer->setNoRender();
            $arrayData = array();

            $jType = '';
            $data = $result;
            foreach ($data as $k => $v) {
                $arrayData[] = array(
                    "trano" => $v['trano'],
                    "tgl" => date("d M Y", strtotime($v['tgl'])),
                    "debit" => floatval($v['debit']),
                    "credit" => floatval($v['credit']),
                    "debit_conersion" => floatval($v['debit_conversion']),
                    "credit_conversion" => floatval($v['credit_conversion']),
                    "coa_kode" => $v['coa_kode'],
                    "coa_nama" => $v['coa_nama'],
                    "job_number" => $v['job_number'],
                    "ref_number" => $v['ref_number']
                );
                $jType = $v['type'];
            }

            $signature = $this->_helper->getHelper('token')->generateDocumentSignature();

            $pt = $this->DEFAULT->MasterPt->fetchRow()->toArray();
            $params = array(
                "nama" => $pt['nama'],
                "alamat1" => $pt['alamat1'],
                "alamat2" => $pt['alamat2'],
                "title" => $this->FINANCE->AdjustingJournal->getJurnalType($type),
                "sub_title" => $trano,
                "date" => date("d M Y"),
                "time" => date("H:i:s A"),
                "signature" => $signature,
                "pic" => QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName()
            );

            QDC_Jasper_Report::factory(
                    array(
                        "reportType" => 'pdf',
                        "arrayData" => $arrayData,
                        "arrayParams" => $params,
                        "fileName" => "jurnal.jrxml",
                        "outputName" => $trano,
                        "dataSource" => 'NoDataSource'
                    )
            )->generate();
        } elseif ($export && !$print) {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totalDebit = 0;
            $totalCredit = 0;
            $totalDebit_conversion = 0;
            $totalCredit_conversion = 0;
            $data = $result;
            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date("d M Y", strtotime($v['tgl'])),
                    "Ref Number" => $v['ref_number'],
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => $v['coa_nama'],
                    "Currency/Rate" => $v['val_kode'].' / '.$v['rateidr'],
                    "Debit" => $v['debit'],
                    "Credit" => $v['credit'],
                    "Debit(Conversion)" => $v['debit_conversion'],
                    "Credit(Conversion)" => $v['credit_conversion'],
                    "Job Number" => $v['job_number']
                );
                $no++;
//                $totalCredit += $v['credit'];
//                $totalDebit += $v['debit'];
                
                if($v['debit_conversion'] > 0)
                    $totalDebit += $v['debit_conversion'];
                else
                    $totalDebit += $v['debit'];
                
                
                if($v['credit_conversion'] > 0)
                    $totalCredit += $v['credit_conversion'];
                else
                $totalCredit += $v['credit'];
                
//                $totalCredit_conversion += $v['credit_conversion'];
//                $totalDebit_conversion += $v['debit_conversion'];
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Trano" => '',
                "Date" => '',
                "Ref Number" => '',
                "COA Code" => "TOTAL",
                "COA Name" => "",
                "Currency/Rate" => "",
                "Debit" => $totalDebit,
                "Credit" => $totalDebit,
                "Debit (Conversion)" => "",
                "Credit (Conversion)" => "",
                "Job Number" => ''
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Transaction Journal " . $title
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewSettlementJournalAction() {
//        $type = $this->getRequest()->getParam('type');
//        $startdate = $this->getRequest()->getParam('startdate');
//        $enddate = $this->getRequest()->getParam('enddate');
//        $ref_number = $this->getRequest()->getParam('ref_number');
//
//        $this->view->type = $type;
//        $this->view->startdate = $startdate;
//        $this->view->enddate = $enddate;
//        $this->view->ref_number = $ref_number;

        $trano = $this->_getParam("trano");
        $ref = $this->_getParam("ref_number");
        $month = $this->_getParam("month");
        $sd = $this->_getParam("start_date");
        $ed = $this->_getParam("end_date");
        $export = ($this->_getParam("export") != '') ? true : false;
        $print = ($this->_getParam("print") == 'true') ? true : false;
        $type = ($this->_getParam("type"));

        $current = $this->_getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->_getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->_getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * 100;

        $where = array();

        $trano = str_replace("_", "/", $trano);

        if ($trano)
            $where[] = "trano = '$trano'";
        if ($ref)
            $where[] = "ref_number = '$ref'";
        if ($type)
            $where[] = "type = '$type'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($sd) {
            $tglAwal = date("Y-m-d 00:00:00", strtotime($sd));
            if ($ed) {
                $tglAkhir = date("Y-m-d 23:59:59", strtotime($ed));
            } else
                $tglAkhir = date("Y-m-d 00:00:00", strtotime($sd));

            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingJurnalSettlement->__name()), array(
            new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
        ));

        foreach ($where as $k => $v) {
            $select = $select->where($v);
        }

        if (!$export && !$print) {
            $select = $select->limit(100, $offset);
        }
        $select = $select->order(array("id", "tgl"));

        $result = $this->db->fetchAll($select);

        $data = array();
        foreach ($result as $k => $v) {
            $result[$k]['tgl'] = date("d M Y", strtotime($v['tgl']));
        }

        $data = $result;

        $title = "";
        if ($trano)
            $title .= "For $trano ";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export && !$print) {
            $this->view->title = $title;
            $this->view->data = $data;

            $this->view->limitPerPage = 100;
            $this->view->totalResult = $this->db->fetchOne("SELECT FOUND_ROWS()");
            $this->view->current = $current;
            $this->view->currentPage = $currentPage;
            $this->view->requested = $requested;
            $this->view->pageUrl = $this->view->url();
            $this->view->pagingParam = array(
                "trano" => $trano,
                "ref_number" => $ref,
                "start_date" => $sd,
                "end_date" => $ed,
                "month" => $month
            );
        } elseif (!$export && $print) {
            $this->_helper->viewRenderer->setNoRender();
            $arrayData = array();

            $jType = '';
            foreach ($data as $k => $v) {
                $arrayData[] = array(
                    "trano" => $v['trano'],
                    "tgl" => date("d M Y", strtotime($v['tgl'])),
                    "debit" => floatval($v['debit']),
                    "credit" => floatval($v['credit']),
                    "coa_kode" => $v['coa_kode'],
                    "coa_nama" => $v['coa_nama'],
                    "job_number" => $v['job_number'],
                    "ref_number" => $v['ref_number']
                );
                $jType = $v['type'];
            }

            $signature = $this->_helper->getHelper('token')->generateDocumentSignature();

            $pt = $this->DEFAULT->MasterPt->fetchRow()->toArray();
            $params = array(
                "nama" => $pt['nama'],
                "alamat1" => $pt['alamat1'],
                "alamat2" => $pt['alamat2'],
                "title" => $this->FINANCE->AccountingJurnalSettlement->getJurnalType($type),
                "sub_title" => $trano,
                "date" => date("d M Y"),
                "time" => date("H:i:s A"),
                "signature" => $signature,
                "pic" => QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName()
            );

            QDC_Jasper_Report::factory(
                    array(
                        "reportType" => 'pdf',
                        "arrayData" => $arrayData,
                        "arrayParams" => $params,
                        "fileName" => "jurnal.jrxml",
                        "outputName" => $trano,
                        "dataSource" => 'NoDataSource'
                    )
            )->generate();
        } elseif ($export && !$print) {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date("d M Y", strtotime($v['tgl'])),
                    "Ref Number" => $v['ref_number'],
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => $v['coa_nama'],
                    "Debit" => $v['debit'],
                    "Credit" => $v['credit'],
                    "Job Number" => $v['job_number']
                );
                $no++;
                $totalCredit += $v['credit'];
                $totalDebit += $v['debit'];
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Trano" => '',
                "Date" => '',
                "Ref Number" => '',
                "COA Code" => "TOTAL",
                "COA Name" => "",
                "Debit" => $totalDebit,
                "Credit" => $totalDebit,
                "Job Number" => ''
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Settlement Journal " . $title
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewGeneralJournalTransAction() {
        $coaKode = $this->_getParam("coa_kode");
        $prjKode = $this->_getParam("prj_kode");
        $month = $this->_getParam("month");

        $export = ($this->_getParam("export") != '') ? true : false;

        if ($coaKode)
            $where[] = "coa_kode = '$coaKode'";

        if ($prjKode)
            $where[] = "prj_kode = '$prjKode'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($where)
            $where = implode(" AND ", $where);

        $current = $this->_getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->_getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->_getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * 100;

        if (!$export) {
            $jurnal = QDC_Finance_Jurnal::factory()->getAllJurnalWithLimit($where, $offset, 100);
            $result = $jurnal['jurnal'];
        } else {
            $jurnal = QDC_Finance_Jurnal::factory()->getAllJurnal($where);
            $result = $jurnal['jurnal'];
        }

        $data = array();
        foreach ($result as $k => $v) {
            $trano = $v['trano'];
            if ($trano == '')
                $trano = 'NO TRANO';
            $data[$trano][] = $v;
        }

        ksort($data);

        $title = "For ";
        if ($prjKode)
            $title .= "Project $prjKode ";
        if ($coaKode)
            $title .= "COA Code $coaKode ";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export) {
            $this->view->title = $title;
            $this->view->data = $data;

            $this->view->totalDebit = $jurnal['total_debit'];
            $this->view->totalCredit = $jurnal['total_credit'];
            $this->view->limitPerPage = 100;
            $this->view->totalResult = $jurnal['count'];
            $this->view->current = $current;
            $this->view->currentPage = $currentPage;
            $this->view->requested = $requested;
            $this->view->pageUrl = $this->view->url();
            $this->view->pagingParam = array(
                "coa_kode" => $coaKode,
                "prj_kode" => $prjKode,
                "month" => $month,
            );
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totaldebit = 0;
            $totalcredit = 0;
            foreach ($data as $key => $val) {
                foreach ($val as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "Trano" => $v['trano'],
                        "Ref Number" => $v['ref_number'],
                        "Date" => $v['tgl'],
                        "COA Code" => $v['coa_kode'],
                        "COA Name" => $v['coa_nama'],
                        "Debit" => $v['debit'],
                        "Credit" => $v['credit']
                    );
                    if ($v['debit_conversion'] != 0 || $v['credit_conversion'] != 0)
                        $newData[] = array(
                            "No" => $no,
                            "Trano" => $v['trano'],
                            "Ref Number" => $v['ref_number'],
                            "Date" => $v['tgl'],
                            "COA Code" => $v['coa_kode'] . ' (Conversion)',
                            "COA Name" => $v['coa_nama'],
                            "Debit" => $v['debit_conversion'],
                            "Credit" => $v['credit_conversion']
                        );

                    $no++;
                    if ($v['is_valueexchange'])
                        continue;

                    if ($v['credit_conversion'] != 0)
                        $totalcredit += floatval($v['credit_conversion']);
                    else
                        $totalcredit += floatval($v['credit']);

                    if ($v['debit_conversion'] != 0)
                        $totaldebit += floatval($v['debit_conversion']);
                    else
                        $totaldebit += floatval($v['debit']);
                }
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Trano" => "TOTAL",
                "Ref Number" => "",
                "Date" => "",
                "COA Code" => "",
                "COA Name" => "",
                "Debit" => $jurnal['total_debit'],
                "Credit" => $jurnal['total_credit']
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "General Journal per Transaction " . $title
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function apAction() {
        
    }

    public function viewApAction() {
        $trano = $this->_getParam("trano");
        $print = ($this->_getParam("print") == 'true') ? true : false;
        $ap = $this->_getParam("ap_number");
        $start = $this->_getParam("start_date");
        $end = $this->_getParam("end_date");
        $grouped = ($this->_getParam("grouped") == 'true') ? true : false;

        $current = $this->_getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->_getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->_getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * 100;

        $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingCloseAP->__name()), array(
            new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
        ));

        if ($trano)
            $select->where("trano LIKE '%$trano%'")
                    ->orWhere("ref_number LIKE '%$trano%'");

        if ($ap)
            $select->where("ref_number_accounting LIKE '%$ap%'");

        if ($start != '') {
            $sd = date("Y-m-d", strtotime($start));
            if ($end == '') {
                $select->where("tgl BETWEEN '$sd 00:00:00' AND '$sd 23:59:59'");
                $select->orWhere("tgl_ref_number_accounting BETWEEN '$sd 00:00:00' AND '$sd 23:59:59'");
            } else {
                $ed = date("Y-m-d", strtotime($end));
                $select->where("tgl BETWEEN '$sd 00:00:00' AND '$ed 23:59:59'");
                $select->orWhere("tgl_ref_number_accounting BETWEEN '$sd 00:00:00' AND '$ed 23:59:59'");
            }
        }

        if ($grouped) {
            $select->reset(Zend_Db_Select::COLUMNS);
            $select->reset(Zend_Db_Select::FROM);
            $select->from(array($this->FINANCE->AccountingCloseAP->__name()), array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS trano"),
                "credit" => "SUM(credit)",
                "debit" => "SUM(debit)",
                "ref_number",
                "tgl",
                "tgl_ref_number_accounting",
                "ref_number_accounting",
                "coa_kode",
                "coa_nama",
                "prj_kode",
                "sit_kode",
                "uid",
            ));
            $select->group(array("coa_kode", "ref_number", "trano", "ref_number_accounting"));
        }

        if (!$print) {
            $select->order(array("tgl DESC", "trano DESC"))
                    ->limit(100, $offset);
            $res = $this->db->fetchAll($select);
            $data = array();
            foreach ($res as $k => $v) {
                $a = $v['ref_number_accounting'];
                if ($a == '') {
                    $a = 'N/A';
                    $v['ref_number_accounting'] = $a;
                }
                $data[$a][] = $v;
            }
            ksort($data);

            $this->view->data = $data;
            $this->view->limitPerPage = 100;
            $this->view->totalResult = $this->db->fetchOne("SELECT FOUND_ROWS()");
            $this->view->current = $current;
            $this->view->currentPage = $currentPage;
            $this->view->requested = $requested;
            $this->view->pageUrl = $this->view->url();
            $this->view->pagingParam = array(
                "trano" => $trano,
                "ap_number" => $ap,
                "start_date" => $start,
                "end_date" => $end,
                "grouped" => ($grouped) ? 'true' : 'false'
            );

            $select->reset(Zend_Db_Select::ORDER);
            $select->reset(Zend_Db_Select::LIMIT_COUNT);
            $select->reset(Zend_Db_Select::LIMIT_OFFSET);
            $select->reset(Zend_Db_Select::FROM);
            $select->reset(Zend_Db_Select::COLUMNS);
            $select->reset(Zend_Db_Select::FROM);
            $select->reset(Zend_Db_Select::GROUP);

            $select->from(array($this->FINANCE->AccountingCloseAP->__name()), array(
                "total_debit" => "SUM(debit)",
                "total_credit" => "SUM(credit)",
            ));

            $res = $this->db->fetchRow($select);
            $this->view->totalDebit = $res['total_debit'];
            $this->view->totalCredit = $res['total_credit'];
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $type = ($this->_getParam("type")) ? $this->_getParam("type") : 'pdf';
            $select->order(array("tgl DESC", "trano DESC"));
            $res = $this->db->fetchAll($select);
            $arrayData = array();

            foreach ($res as $k => $v) {
                $arrayData[] = array(
                    "AP Number" => ($v['ref_number_accounting'] == '') ? $v['trano'] : $v['ref_number_accounting'],
                    "Date Transaction" => date("d M Y", strtotime($v['tgl'])),
                    "coa_code" => $v['coa_kode'],
                    "coa_name" => $v['coa_nama'],
                    "Debit" => floatval($v['debit']),
                    "Credit" => floatval($v['credit']),
                    "job_number" => $v['job_number'],
                    "ref_number" => $v['ref_number'],
                    "Project Code" => $v['prj_kode']
                );
            }

            $signature = $this->_helper->getHelper('token')->generateDocumentSignature();

            $pt = $this->DEFAULT->MasterPt->fetchRow()->toArray();
            $params = array(
                "nama" => $pt['nama'],
                "alamat1" => $pt['alamat1'],
                "alamat2" => $pt['alamat2'],
                "title" => "AP Journal",
                "sub_title" => $ap, //. ($bpvPPN != '') ? " + Tax" : '',
                "date" => date("d M Y"),
                "time" => date("H:i:s A"),
                "signature" => $signature,
                "pic" => QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName()
            );

//            QDC_Jasper_Report::factory(
//                    array(
//                        "reportType" => $type,
//                        "arrayData" => $arrayData,
//                        "arrayParams" => $params,
//                        "fileName" => "jurnal.jrxml",
//                        "outputName" => 'AP Journal ' . $ap,
//                        "dataSource" => 'NoDataSource'
//                    )
//            )->generate();
            QDC_Adapter_Excel::factory(array(
                        "fileName" => "AP Report "
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )))
                    
                    ->write($arrayData)->toExcel5Stream();
        }
    }

    public function bankChargeAction() {
        
    }

    public function viewBankChargeAction() {
        $trano = $this->_getParam("trano");
        $trano = str_replace("_", "/", $trano);
        $month = $this->_getParam("month");
        $export = ($this->_getParam("export") != '') ? true : false;

        $where[] = "item_type = 'BCH'";

        if ($trano)
            $where[] = "trano = '$trano'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($where)
            $where = implode(" AND ", $where);

        $result = $this->FINANCE->BankSpendMoney->fetchAll($where);

        $data = array();
        foreach ($result as $k => $v) {
            $coa = $v['coa_kode'];
            $coaNama = $v['coa_nama'];
            $data[$coa]['trano'] = $v['trano'];
            $data[$coa]['ref_number'] = $v['ref_number'];
            $data[$coa]['tgl'] = date("d M Y", strtotime($v['tgl']));
            $data[$coa]['coa_kode'] = $coa;
            $data[$coa]['coa_nama'] = $coaNama;
            $data[$coa]['debit'] += $v['debit'];
            $data[$coa]['credit'] += $v['credit'];
        }

        ksort($data);


        $title = "For ";
        if ($trano)
            $title .= "$trano ";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export) {
            $this->view->title = $title;
            $this->view->data = $data;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date("d M Y", strtotime($v['tgl'])),
                    "Ref Number" => $v['ref_number'],
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => $v['coa_nama'],
                    "Debit" => $v['debit'],
                    "Credit" => $v['credit']
                );
                $no++;
                $totalCredit += $v['credit'];
                $totalDebit += $v['debit'];
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Trano" => '',
                "Date" => '',
                "Ref Number" => '',
                "COA Code" => "TOTAL",
                "COA Name" => "",
                "Debit" => $totalDebit,
                "Credit" => $totalDebit
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Bank Charge " . $title
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function bankReceiveMoneyAction() {
        
    }

    public function viewBankReceiveMoneyAction() {
        $trano = $this->_getParam("trano");
        $trano = str_replace("_", "/", $trano);
        $month = $this->_getParam("month");
        $export = ($this->_getParam("export") != '') ? true : false;

        if ($trano)
            $where[] = "trano = '$trano'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($where)
            $where = implode(" AND ", $where);

        $result = $this->FINANCE->BankReceiveMoney->fetchAll($where);

        $data = array();
        foreach ($result as $k => $v) {
            $coa = $v['coa_kode'];
            $coaNama = $v['coa_nama'];
            $data[$coa]['trano'] = $v['trano'];
            $data[$coa]['ref_number'] = $v['ref_number'];
            $data[$coa]['tgl'] = date("d M Y", strtotime($v['tgl']));
            $data[$coa]['coa_kode'] = $coa;
            $data[$coa]['coa_nama'] = $coaNama;
            $data[$coa]['debit'] += $v['debit'];
            $data[$coa]['credit'] += $v['credit'];
        }

        ksort($data);

        $title = "For ";
        if ($trano)
            $title .= "$trano ";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export) {
            $this->view->title = $title;
            $this->view->data = $data;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date("d M Y", strtotime($v['tgl'])),
                    "Ref Number" => $v['ref_number'],
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => $v['coa_nama'],
                    "Debit" => $v['debit'],
                    "Credit" => $v['credit']
                );
                $no++;
                $totalCredit += $v['credit'];
                $totalDebit += $v['debit'];
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Trano" => '',
                "Date" => '',
                "Ref Number" => '',
                "COA Code" => "TOTAL",
                "COA Name" => "",
                "Debit" => $totalDebit,
                "Credit" => $totalDebit
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Bank Receive Money " . $title
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewArReportAction() {
        $tgl = $this->getRequest()->getParam("tgl");
        $days = "";

        $subselect = $this->db->select()
                ->from(array($this->FINANCE->Invoice->name()), array(
            "trano",
            "tgl",
            "total" => "(CASE val_kode WHEN 'USD' THEN (total*rateidr) ElSE total END)",
            "val_kode",
            "cus_kode",
            "top"
        ));

//        if ($tgl)
//            $subselect->where("cus_kode = '$cus'");

        $subselect2 = $this->db->select()
                ->from(array($this->FINANCE->PaymentInvoice->name()), array(
                    "total_payment" => "(CASE val_kode WHEN 'USD' THEN (total*rateidr) ElSE total END)",
                    "inv_no"
                ))
                ->group(array("inv_no", "val_kode"));

        $subselect3 = $this->db->select()
                ->from(array("a" => $subselect))
                ->joinLeft(array("b" => $subselect2), "a.trano = b.inv_no", array(
            "total_payment" => "COALESCE(total_payment,0)"
        ));

        if ($tgl)
            $days = "DATEDIFF($tgl,tgl)";
        else
            $days = 0;

        $subselect4 = $this->db->select()
                ->from(array($subselect3), array(
                    "trano",
                    "cus_kode",
                    "total",
                    "val_kode",
                    "tgl",
                    "top",
                    "days" => $days,
                    "total_payment",
                    "balance" => "(total-total_payment)"
                ))
                ->where("total_payment < total")
                ->order(array("cus_kode"));

        $fetch = $this->db->fetchAll($subselect4);
        $data = array();
        if ($fetch) {
            foreach ($fetch as $key => $val) {
                $tgl = $val['tgl'];
                $cus = $val['cus_kode'];
//                $days = $this->utility->dates_diff($tgl,date("Y-m-d"));
                $daysbalance = $val['days'] - $val['top'];
                $data[$cus]['val_kode'] = $val['val_kode'];
                if ($daysbalance >= 0 && $daysbalance <= 30) {
                    $data[$cus]['0'] += $val['balance'];
                } elseif ($daysbalance >= 31 && $daysbalance <= 60) {
                    $data[$cus]['30'] += $val['balance'];
                } elseif ($daysbalance >= 61 && $daysbalance <= 90) {
                    $data[$cus]['60'] += $val['balance'];
                } else {
                    $data[$cus]['90'] += $val['balance'];
                }
            }

            $total = array();
            foreach ($data as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $data[$k]['total'] = $totalBalance;
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;
                $data[$k]['name'] = $this->DEFAULT->MasterCustomer->getName($k);
            }
        }

        $this->view->data = $data;
        $this->view->total = $total;
    }

    public function inventoryJournalAction() {
        
    }

    public function fixedAssetsJournalAction() {
        
    }

    public function arJournalAction() {
        
    }

    public function viewInventoryJournalAction() {
        $trano = $this->_getParam("trano");
        $ref = $this->_getParam("ref_number");
        $month = $this->_getParam("month");
        $sd = $this->_getParam("start_date");
        $ed = $this->_getParam("end_date");
        $type = $this->_getParam("type");
        $export = ($this->_getParam("export") != '') ? true : false;

        if ($trano)
            $where[] = "trano = '$trano'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($sd) {
            $tglAwal = date("Y-m-d 00:00:00", strtotime($sd));
            if ($ed) {
                $tglAkhir = date("Y-m-d 23:59:59", strtotime($ed));
            } else
                $tglAkhir = date("Y-m-d 00:00:00", strtotime($sd));

            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($type)
            $where[] = "jenis_jurnal = '$type'";

        if ($where)
            $where = implode(" AND ", $where);

        $result = QDC_Finance_Jurnal::factory()->getInventoryJournal($where);

        $data = array();
        foreach ($result as $k => $v) {
            $result[$k]['tgl'] = date("d M Y", strtotime($v['tgl']));
        }

        $data = $result;

        $title = "";
        if ($trano)
            $title .= "For $trano ";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export) {
            $this->view->title = $title;
            $this->view->data = $data;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date("d M Y", strtotime($v['tgl'])),
                    "Ref Number" => $v['ref_number'],
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => $v['coa_nama'],
                    "Debit" => $v['debit'],
                    "Credit" => $v['credit']
                );
                $no++;
                $totalCredit += $v['credit'];
                $totalDebit += $v['debit'];
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Trano" => '',
                "Date" => '',
                "Ref Number" => '',
                "COA Code" => "TOTAL",
                "COA Name" => "",
                "Debit" => $totalDebit,
                "Credit" => $totalDebit
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Inventory Journal " . $title
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewArJournalAction() {
        $trano = $this->_getParam("trano");
        $ref = $this->_getParam("ref_number");
        $month = $this->_getParam("month");
        $sd = $this->_getParam("start_date");
        $ed = $this->_getParam("end_date");
        $type = $this->_getParam("type");
        $export = ($this->_getParam("export") != '') ? true : false;

        if ($trano)
            $where[] = "trano = '$trano'";
        if ($ref)
            $where[] = "ref_number = '$ref'";

        if ($month) {
            $date = new DateTime($month . "-" . "01");
            $date->add(new DateInterval('P1M'));
            $date->sub(new DateInterval('P1D'));

            $tglAwal = $month . "-" . "01 00:00:00";
            $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($sd) {
            $tglAwal = date("Y-m-d 00:00:00", strtotime($sd));
            if ($ed) {
                $tglAkhir = date("Y-m-d 23:59:59", strtotime($ed));
            } else
                $tglAkhir = date("Y-m-d 00:00:00", strtotime($sd));

            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($type) {
            switch ($type) {
                case 'PAYMENT-AR-INV':
                    $where[] = "type = 'AR-INV'";
                    break;
                default:
                    $where[] = "type = '$type'";
                    break;
            }
        }

        if ($where)
            $where = implode(" AND ", $where);

        $result = QDC_Finance_Jurnal::factory()->getArJournal($where, $type);

        $data = array();
        foreach ($result as $k => $v) {
            $result[$k]['tgl'] = date("d M Y", strtotime($v['tgl']));
        }

        $data = $result;

        $title = "";
        if ($trano)
            $title .= "For $trano ";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export) {
            $this->view->title = $title;
            $this->view->data = $data;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date("d M Y", strtotime($v['tgl'])),
                    "Ref Number" => $v['ref_number'],
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => $v['coa_nama'],
                    "Debit" => $v['debit'],
                    "Credit" => $v['credit']
                );
                $no++;
                $totalCredit += $v['credit'];
                $totalDebit += $v['debit'];
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Trano" => '',
                "Date" => '',
                "Ref Number" => '',
                "COA Code" => "TOTAL",
                "COA Name" => "",
                "Debit" => $totalDebit,
                "Credit" => $totalDebit
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "AR Journal " . $title
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewFixedAssetJournalAction() {
//        $trano = $this->_getParam("trano");
//        $ref = $this->_getParam("ref_number");
        $month = $this->_getParam("month");
        $sd = $this->_getParam("start_date");
        $ed = $this->_getParam("end_date");
        $type = $this->_getParam("type");
        $periode = $this->_getParam("periode");
        $kategori = $this->_getParam("kategori");
        $export = ($this->_getParam("export") != '') ? true : false;
        $transaction;

        if ($type == 'transaction') {
            $transaction = true;
            if ($month) {
                $date = new DateTime($month . "-" . "01");
                $date->add(new DateInterval('P1M'));
                $date->sub(new DateInterval('P1D'));

                $tglAwal = $month . "-" . "01 00:00:00";
                $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
                $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
            }

            if ($sd) {
                $tglAwal = date("Y-m-d 00:00:00", strtotime($sd));
                if ($ed) {
                    $tglAkhir = date("Y-m-d 23:59:59", strtotime($ed));
                } else
                    $tglAkhir = date("Y-m-d 00:00:00", strtotime($sd));

                $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
            }
            if ($kategori)
                $where[] = "kode_kategori = '$kategori'";
        }
        else {

            if ($kategori) {
                $coa = $this->FINANCE->MasterKategoriAsset->fetchRow("kode_ktfa = '$kategori'");
                $coa_debit = $coa['coa_debit'];
                $coa_credit = $coa['coa_credit'];

                $where[] = "coa_kode IN ('$coa_debit','$coa_credit')";
            }
            if ($periode) {
                $periode_detail = $this->FINANCE->MasterPeriode->fetchRow("perkode = '$periode'");
                $bulan = $periode_detail['bulan'];
                $tahun = $periode_detail['tahun'];

                $where[] = "periode ='$bulan'";
                $where[] = "tahun='$tahun'";
            }
        }
        if ($where)
            $where = implode(" AND ", $where);

        $result = QDC_Finance_Jurnal::factory()->getFixedAssetsJournal($where, $type);

        $data = array();
        foreach ($result as $k => $v) {
            $result[$k]['tgl'] = date("d M Y", strtotime($v['tgl']));
        }

        $data = $result;

//        var_dump($result);die;

        $title = "";
        if ($trano)
            $title .= "For $trano ";
        if ($month)
            $title .= "Periode " . date("d M Y", strtotime($tglAwal)) . " - " . date("d M Y", strtotime($tglAkhir));

        if (!$export) {
            $this->view->title = $title;
            $this->view->data = $data;
            $this->view->transaction = $transaction;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($data as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['trano'],
                    "Date" => date("d M Y", strtotime($v['tgl'])),
                    "Ref Number" => $v['ref_number'],
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => $v['coa_nama'],
                    "Debit" => $v['debit'],
                    "Credit" => $v['credit']
                );
                $no++;
                $totalCredit += $v['credit'];
                $totalDebit += $v['debit'];
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Trano" => '',
                "Date" => '',
                "Ref Number" => '',
                "COA Code" => "TOTAL",
                "COA Name" => "",
                "Debit" => $totalDebit,
                "Credit" => $totalDebit
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Inventory Journal " . $title
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function exportAllJurnalAction() {
        $this->_helper->viewRenderer->setNoRender();
//        $all = QDC_Finance_Jurnal::factory()->getAllJurnal();

        $month = $this->_getParam("month");
        $where = null;
        if ($month) {
            if ($month) {
                $date = new DateTime($month);
                $date->add(new DateInterval('P1M'));
                $date->sub(new DateInterval('P1D'));

                $tglAwal = $month . " 00:00:00";
                $tglAkhir = $date->format('Y-m-d') . " 23:59:59";
                $where = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
            }
        }

        $filename = QDC_Adapter_File::factory()->getRandomFilename();

        $models = array(
            "AccountingCloseAP",
            "AccountingCloseAR",
            "AccountingInventoryIn",
            "AccountingInventoryOut",
            "AccountingInventoryReturn",
            "AccountingJurnalBank",
            "AccountingJurnalSettlement",
            "AdjustingJournal",
            "BankReceiveMoney",
            "BankSpendMoney",
            "PettyCashIn",
            "PettyCashOut",
        );
        $FINANCE = QDC_Model_Finance::init($models);
        $no = 1;
        $limit = 1000;
        $first = true;
        foreach ($models as $key => $val) {
            $newData = array();
            $s = $this->db->select()
                    ->from(array($FINANCE->{$val}->__name()), array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
            ));

            if ($where)
                $s = $s->where($where);

            $total = $this->db->fetchOne("SELECT FOUND_ROWS()");
            if ($total == 0)
                continue;
            $co = ceil($total / $limit);
            for ($i = 0; $i <= $co; $i++) {
                $j = $FINANCE->{$val}->fetchAll($where, null, $limit, $i * $limit);
                if ($j)
                    $j = $j->toArray();

                foreach ($j as $k => $v) {
                    $newData[] = array(
                        "No" => $no,
                        "Trano" => $v['trano'],
                        "Date" => date("d M Y", strtotime($v['tgl'])),
                        "Ref Number" => $v['ref_number'],
                        "COA Code" => $v['coa_kode'],
                        "COA Name" => $v['coa_nama'],
                        "Debit" => $v['debit'],
                        "Credit" => $v['credit']
                    );
                    $no++;
                }

                if ($first)
                    QDC_Adapter_Excel::factory(array(
                                "fileName" => $filename
                            ))
                            ->setCellFormat(array(
                                6 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                7 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                )
                            ))
                            ->write($newData, null, false)->toExcel5();
                else
                    QDC_Adapter_Excel::factory(array(
                                "fileName" => $filename
                            ))
                            ->setCellFormat(array(
                                6 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                7 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                )
                            ))
                            ->appendToFile($filename, $newData);

                $first = false;
            }
        }

        $res = QDC_Adapter_File::factory()->download($filename . ".xls", "All Journal ERP.xls", true);
    }

    public function balancesheetPreviewAction() {
        $max = QDC_Finance_BalanceSheet::factory()->maxLevel();

        $level = array();
        for ($i = 1; $i <= $max; $i++) {
            $level[] = array(
                $i,
                $i
            );
        }
        $this->view->max = ($max != '') ? $max : 0;
        $this->view->levels = Zend_Json::encode($level);
    }

    public function viewBalancesheetPreviewAction() {
        $month = $this->getRequest()->getParam("month");
        $start = $this->getRequest()->getParam("start_date");
        $end = $this->getRequest()->getParam("end_date");
        $depth = $this->getRequest()->getParam("depth");
        $export = ($this->getRequest()->getParam("export") == 'true') ? true : false;
        $detail = ($this->getRequest()->getParam("detail") == 'true') ? true : false;

        if (!$depth)
            $depth = 3;

        if ($month && ($start == '' && $end == '')) {
            $tgl = new DateTime(date("d-m-Y", strtotime($month)));
            $start = $tgl->format("Y-m-d");
            $tgl->add(new DateInterval("P1M"));
            $tgl->sub(new DateInterval("P1D"));
            $end = $tgl->format("Y-m-d");
        }

        $lastSaldo = $this->FINANCE->AccountingSaldoCoa->getLastSaldo($start);
//        $lastSaldo = $this->FINANCE->MasterPeriode->getLastPeriode($start);


        $params = array(
            "leveldepth" => $depth,
            "month" => $lastSaldo['periode'],
            "year" => $lastSaldo['tahun'],
            "start_date" => $start,
            "end_date" => $end,
            "number_format" => true,
            "preview" => true,
            "spaces" => $export
        );

        $data = QDC_Finance_BalanceSheet::factory($params)->generateBalanceSheet();
        if (!$export) {
            $this->view->lastSaldo = $lastSaldo;
            $this->view->result = $data;
            $this->view->detail = $detail;
            $this->view->periode = date("d/M/Y", strtotime($start)) . " - " . date("d/M/Y", strtotime($end));
        } else {
            $title = date("d-m-Y", strtotime($start)) . " - " . date("d-m-Y", strtotime($end));
            $newData = array();
            $no = 1;

            $totalDebit = 0;
            $totalCredit = 0;
            $text = '';
            $totalSaldo = '';
            foreach ($data as $k => $v) {

                if ($v['coa_kode']) {
                    $text = $v['coa_kode'];
                    $totalSaldo = $v['total'];
                } else {
                    $text = $v['text'];
                    $totalSaldo = $v['grandtotal'];
                }
                $newData[] = array(
                    "No" => $no,
                    "COA Code" => $v['coa_kode'],
                    "COA Name" => $text,
                    "Total" => $totalSaldo
                );
                $no++;
            }

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Balance Sheet " . $title
                    ))
                    ->setCellFormat(array(
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function viewRpcAction() {
        $uidPerson = $this->getRequest()->getParam("uid");
        $print = ($this->getRequest()->getParam("print") == "true") ? true : false;
        $detail = ($this->getRequest()->getParam("detail") == "true") ? true : false;

        $where = "";

        if ($uidPerson)
            $where .= " AND requester = '$uidPerson' ";

        $query = $this->db->prepare("call outstanding_rpc(\"$where\",\"\",\"all\")");

        $query->execute();
        $datas = $query->fetchAll();
        $query->closeCursor();

        $data = array();

        //cek cip from oca
        foreach ($datas as $key => $val) {
            $total = 0;
            $arf = $val['trano'];

            $query = "select arf_no, sum(coalesce(total,0))total from procurement_asfdd "
                    . " where arf_no = '$arf' and trano like 'OCA%' ";

            $data = $this->db->fetchRow($query);
            if ($data) {

                $total = $data['total'];

                if ($total == $val['balance'])
                    unset($datas[$key]);
            }
        }

        if ($datas) {

            foreach ($datas as $key => $val) {
                $tgl = date("Y-m-d", strtotime($val['tgl']));
                $uid = $val['requester'];
                $days = $val['days'];

                $data[$uid]['val_kode'] = $val['val_kode'];
                if ($days >= 0 && $days <= 30) {
                    $data[$uid]['0'] += $val['balance'];
                } elseif ($days >= 31 && $days <= 60) {
                    $data[$uid]['30'] += $val['balance'];
                } elseif ($days >= 61 && $days <= 90) {
                    $data[$uid]['60'] += $val['balance'];
                } else {
                    $data[$uid]['90'] += $val['balance'];
                }
            }

            $total = array();
            foreach ($data as $k => $v) {
                $totalBalance = 0;
                foreach ($v as $k2 => $v2) {
                    $totalBalance += floatval($v2);
                }
                $name = QDC_User_Ldap::factory(array("uid" => $k))->getName();
                $data[$k]['total'] = $totalBalance;
                $total['grandTotal_0'] += $v['0'];
                $total['grandTotal_30'] += $v['30'];
                $total['grandTotal_60'] += $v['60'];
                $total['grandTotal_90'] += $v['90'];
                $total['grandTotal_all'] += $totalBalance;
                $data[$k]['name'] = ($name == '') ? $k : $name;
            }

            $this->view->data = $data;
            $this->view->total = $total;

            if ($print) {
                $this->_helper->viewRenderer->setNoRender();
                $newData = array();
                $no = 1;

                if (!$detail) {
                    foreach ($data as $k => $v) {
                        $newData[] = array(
                            "No" => $no,
                            "Person Name" => $v['name'],
                            "Total Due" => $v['balance'],
                            "0 - 30" => $v['0'],
                            "31 - 60" => $v['30'],
                            "61 - 90" => $v['60'],
                            "90+" => $v['90'],
                        );
                        $no++;
                    }
                    $newData[] = array(
                        "No" => '',
                        "Person Name" => "Total",
                        "Total Due" => $total['grandTotal_all'],
                        "0 - 30" => $total['grandTotal_0'],
                        "31 - 60" => $total['grandTotal_30'],
                        "61 - 90" => $total['grandTotal_60'],
                        "90+" => $total['grandTotal_90'],
                    );

                    $title = '';
                    if ($uidPerson != '')
                        $title = " " . QDC_User_Ldap::factory(array("uid" => $uidPerson))->getName();

                    QDC_Adapter_Excel::factory(array(
                                "fileName" => "Outstanding RPC" . $title . "_" . date("dmY")
                            ))
                            ->setCellFormat(array(
                                2 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                3 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                4 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                5 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                6 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                )
                            ))
                            ->write($newData)->toExcel5Stream();
                }
                else {
                    $sql = "select requester from final where requester is not null;";
                    $fetchPerson = $this->db->fetchAll($sql);

                    $person = array();
                    foreach ($fetchPerson as $k => $v) {
                        $uid = $v['requester'];
                        $name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                        if ($name == '')
                            $name = $uid . ' [Deleted on LDAP]';

                        $person[$uid] = $name;
                    }

//                    $sql = "select *,(totalarf-totalasf)balance from final where requester is not null;";
//                    $fetch = $this->db->fetchAll($sql);

                    $total = $totalasf = $totalbalance = 0;
                    foreach ($datas as $k => $v) {
                        $uid = $v['requester'];
                        $total += $v['totalarf'];
                        $totalasf += $v['totalasf'];
                        $totalbalance += $v['balance'];
                        $newData[] = array(
                            "No" => $no,
                            "Person Name" => $person[$uid],
                            "Trano ARF" => $v['trano'],
                            "Date ARF" => date("d-m-Y", strtotime($v['tgl'])),
                            "Total ARF" => $v['totalarf'],
                            "Total ASF" => $v['totalasf'],
                            "Balance" => $v['balance'],
                        );
                        $no++;
                    }
                    $newData[] = array(
                        "No" => '',
                        "Person Name" => "Total",
                        "Trano ARF" => '',
                        "Date ARF" => '',
                        "Total ARF" => $total,
                        "Total ASF" => $totalasf,
                        "Balance" => $totalbalance,
                    );

                    $title = '';
                    if ($uidPerson != '')
                        $title = " " . QDC_User_Ldap::factory(array("uid" => $uidPerson))->getName();

                    QDC_Adapter_Excel::factory(array(
                                "fileName" => "Outstanding RPC" . $title . "_" . date("dmY")
                            ))
                            ->setCellFormat(array(
                                4 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                5 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                ),
                                6 => array(
                                    "cell_type" => "numeric",
                                    "cell_operation" => "setFormatCode",
                                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                                )
                            ))
                            ->write($newData)->toExcel5Stream();
                }
            }
        }
    }

    public function detailRpcAction() {
        $uid = $this->getRequest()->getParam("uid");
        $start = $this->getRequest()->getParam("start");

        if ($start != '') {
            if ($start == 0)
                $start2 = 30;
            if ($start == 30) {
                $start = 31;
                $start2 = 60;
            }
            if ($start == 60) {
                $start = 61;
                $start2 = 90;
            }
            if ($start == 90) {
                $start2 = '';
                $start = 91;
            }

            if ($start != '')
                $start = "DATEDIFF(NOW(),tgl) >= $start";
            if ($start2 != '')
                $start2 = " AND DATEDIFF(NOW(),tgl) <= $start2";
            $where_date = " Where $start $start2";
        }

        $where = "";

        if ($uid)
            $where .= " AND requester = '$uid'";

        $query = $this->db->prepare("call outstanding_rpc(\"$where\",\"$where_date\",\"detail\")");

        $query->execute();
        $datas = $query->fetchAll();
        $query->closeCursor();

        $data = array();

        foreach ($datas as $key => $val) {
            $total = 0;
            $arf = $val['trano'];

            $query = "select arf_no, sum(coalesce(total,0))total from procurement_asfdd "
                    . " where arf_no = '$arf' and trano like 'OCA%' ";

            $data = $this->db->fetchRow($query);
            if ($data) {

                $total = $data['total'];

                if ($total == $val['balance'])
                    unset($datas[$key]);
            }
        }

        if ($datas) {
            $data = $datas;
            foreach ($datas as $key => $val) {
                $tgl = date("Y-m-d", strtotime($val['tgl']));
                $uid = $val['requester'];
//                $days = $this->utility->dates_diff($tgl, date("Y-m-d"));
//                $data[$key]['days'] = $days;
                $data[$key]['name'] = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                $this->view->name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                
//                $asf = $val['asfno'];
//                
//                $fetchCek = $this->db->query("select *, if(b.final = '0', 'Document is still being processed', 'Final Approved')statusdoc "
//                        . "from asf a "
//                        . "left join workflow_trans b "
//                        . "on a.asfno = b.item_id "
//                        . "where a.trano = '$asf' "
//                        . " group by item_id");
//                $fetchCek = $fetchCek->fetchAll();
//                $data[$key]['asfno'] = ($fetchCek[0]['asfno'] ? $fetchCek[0]['asfno'] : '-');
//                $data[$key]['statusdoc'] = ($fetchCek[0]['statusdoc'] ? $fetchCek[0]['statusdoc'] : 'Not Yet Created');
//
//                $asfData = $this->db->query("select * from procurement_asfdd where arf_no = '$asf' group by trano;");
//                $asf = $asf->fetchAll();
//
//                if ($asf[0]['trano'] && !$fetchCek[0]['item_id'])
//                    $data[$key]['statusdoc'] = 'Rejected By System [Requested by User]';
            }
        }

        $this->view->data = $data;
    }

    public function requestInvoiceToInvoiceAction() {
        $trano = $this->getRequest()->getParam("trano");
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $cusKode = $this->getRequest()->getParam("cus_kode");
        $startDate = $this->getRequest()->getParam("start_date");
        $endDate = $this->getRequest()->getParam("end_date");
        $all = ($this->getRequest()->getParam("all")) ? true : false;
        $print = ($this->getRequest()->getParam("print")) ? true : false;

        $current = $this->getRequest()->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->getRequest()->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->getRequest()->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * 20;


        $subselect = $this->db->select()
                ->from(array("a" => $this->FINANCE->RequestInvoice->__name()), array(
                    "rinv" => "trano",
                    "prj_kode",
                    "sit_kode",
                    "cus_kode",
                    "prj_nama",
                    "sit_nama",
                    "val_kode",
                    "co_no",
                    "total_request" => "(IF(a.val_kode = 'IDR',a.total,(a.total*coalesce(b.rateidr,a.rateidr))))",
                    "total_request_usd" => "(IF(a.val_kode = 'USD',a.total,0))",
                    "tgl_request" => "tgl",
                    "requester" => "uid",
                    "paymentnotes"
                ))
                ->joinLeft(array("b" => $this->FINANCE->InvoiceDetail->name()), "a.trano = b.riv_no", array(
            "total_invoice" => "(IF(b.val_kode = 'USD',(coalesce(b.total,0)*b.rateidr),coalesce(b.total,0)))",
            "total_invoice_USD" => "(IF(b.val_kode = 'USD',coalesce(b.total,0),0))",
            "nama_brg",
            "val_kode_inv" => "val_kode",
            "rateidr",
            "jumlah" => "(IF(b.val_kode = 'IDR',b.jumlah,(b.jumlah*b.rateidr)))",
            "jumlah_USD" => "(IF(b.val_kode = 'USD',b.jumlah,0))",
            "inv_no" => "trano",
            "tgl_invoice" => "tgl",
            "ppn" => "(IF(b.statusppn = 'Y',(IF(b.val_kode = 'USD',b.qty*b.harga*0.1*b.rateidr,b.qty*b.harga*0.1)),0))",
            "ppn_USD" => "(IF(b.statusppn = 'Y',(IF(b.val_kode = 'USD',b.qty*b.harga*0.1,0)),0))",
            "holding_tax_val_USD" => "(IF(b.val_kode = 'USD',COALESCE(b.holding_tax_val,0),0))",
            "holding_tax_val" => "(IF(b.val_kode = 'USD',COALESCE(b.holding_tax_val*b.rateidr,0),COALESCE(b.holding_tax_val,0)))",
            "deduction_USD" => "(IF(b.val_kode = 'USD',COALESCE(b.deduction,0),0))",
            "deduction" => "(IF(b.val_kode = 'USD',COALESCE(b.deduction*b.rateidr,0),COALESCE(b.deduction,0)))",
        ));
//                ->where("b.trano IS NOT NULL");


        if ($trano)
            $subselect = $subselect->where("a.trano = ?", $trano);
        if ($prjKode)
            $subselect = $subselect->where("a.prj_kode = ?", $prjKode);
        if ($sitKode)
            $subselect = $subselect->where("a.sit_kode = ?", $sitKode);
        if ($cusKode)
            $subselect = $subselect->where("a.cus_kode = ?", $cusKode);

        if ($startDate) {
            $start = date("Y-m-d", strtotime($startDate));
            if ($endDate) {
                $end = date("Y-m-d", strtotime($endDate));
                $subselect = $subselect->where("a.tgl BETWEEN '$start' AND '$end'");
            } else
                $subselect = $subselect->where("a.tgl = '$start'");
        }

        $subselect2 = $this->db->select()
                ->from(array("d" => $subselect), array(
                    "*",
                    "aging" => "(IF(d.tgl_invoice IS NOT NULL,DATEDIFF(d.tgl_invoice,d.tgl_request),0))"
                ))
                ->joinLeft(array("c" => $this->FINANCE->PaymentInvoice->name()), "d.inv_no = c.inv_no", array(
                    "paid_no" => "trano",
                    "total_payment" => "COALESCE((IF(c.val_kode = 'USD',(c.total*c.rateidr),c.total)),0)",
                    "total_payment_USD" => "COALESCE((IF(c.val_kode = 'USD',c.total,0)),0)",
                    "tgl_payment" => "tgl",
                    "val_kode_payment" => "val_kode",
                    "rateidr_payment" => "rateidr",
                    "aging_payment" => "(IF(c.tgl IS NULL,DATEDIFF(NOW(),d.tgl_invoice),''))"
                ))
                ->order(array("d.rinv ASC"));


        $subselect3 = $this->db->select()
                ->from(array("e" => $subselect2), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS e.*"),
                    "jumlah" => "SUM(coalesce(jumlah,0))",
                    "jumlah_USD" => "SUM(coalesce(jumlah_USD,0))",
                    "ppn" => "SUM(coalesce(ppn,0))",
                    "ppn_USD" => "SUM(coalesce(ppn_USD,0))",
                    "holding_tax" => "SUM(coalesce(holding_tax_val,0))",
                    "holding_tax_USD" => "SUM(coalesce(holding_tax_val_USD,0))",
                    "deduction" => "SUM(coalesce(deduction,0))",
                    "deduction_USD" => "SUM(coalesce(deduction_USD,0))",
                    "total_payment" => "SUM(coalesce(total_payment,0))",
                    "total_payment_USD" => "SUM(coalesce(total_payment_USD,0))",
                ))
                ->group(array("e.rinv", "e.inv_no", "e.paid_no"));

        if (!$all)
            $subselect3 = $subselect3->limit(20, $offset);

        $fetch = $this->db->fetchAll($subselect3);

        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
        $data = array();

        $cus = new Default_Models_MasterCustomer();
        $pro = new Default_Models_MasterProject();
        $sit = new Default_Models_MasterSite();


        if ($fetch) {
            foreach ($fetch as $k => $v) {

                $fetch[$k]['project'] = $v['prj_kode'] . " - " . $v['prj_nama'];
                $fetch[$k]['site'] = $v['sit_kode'] . " - " . $v['sit_kode'];
                $fetch[$k]['cus_kode'] = $v['cus_kode'];
                $fetch[$k]['tgl_request'] = (!$v['tgl_request']) ? '' : date('d M Y', strtotime($v['tgl_request']));
                $fetch[$k]['tgl_invoice'] = (!$v['tgl_invoice']) ? '' : date('d M Y', strtotime($v['tgl_invoice']));
                $fetch[$k]['tgl_payment'] = (!$v['tgl_payment']) ? '' : date('d M Y', strtotime($v['tgl_payment']));

                if ($v['sit_nama'] == '')
                    $fetch[$k]['site'] = $v['sit_kode'] . " - " . $sit->getSiteName($v['prj_kode'], $v['sit_kode']);
                if ($v['prj_nama'] == '')
                    $fetch[$k]['project'] = $v['prj_kode'] . " - " . $pro->getProjectName($v['prj_kode']);

                if (!$print) {
                    $fetch[$k]['customer_name'] = wordwrap($cus->getName($v['cus_kode']), 30, "<br>");
                    $fetch[$k]['project'] = wordwrap($fetch[$k]['project'], 30, "<br>");
                    $fetch[$k]['site'] = wordwrap($fetch[$k]['site'], 30, "<br>");
                    $fetch[$k]['nama_brg'] = wordwrap($fetch[$k]['nama_brg'], 30, "<br>");
                }
            }
        }

        if (!$print) {
            $this->view->data = $fetch;
            if (!$all) {
                $this->view->limitPerPage = 20;
                $this->view->totalResult = $count;
                $this->view->current = $current;
                $this->view->currentPage = $currentPage;
                $this->view->requested = $requested;
                $this->view->pageUrl = $this->view->url();
            }
            $this->view->all = $all;
        } else {

            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            if ($fetch) {
                foreach ($fetch as $k => $v) {

                    $newData[] = array(
                        "RINV Number" => $v['rinv'],
                        "RINV Date" => (!$v['tgl_request']) ? '' : date('d M Y', strtotime($v['tgl_request'])),
                        "CO Number" => $v['co_no'],
                        "Invoice Number" => $v['inv_no'],
                        "Customer" => $cus->getName($v['cus_kode']),
                        "Project" => $v['prj_kode'] . " - " . $pro->getProjectName($v['prj_kode']),
                        "Site" => $v['sit_kode'] . " - " . $sit->getSiteName($v['prj_kode'], $v['sit_kode']),
                        "Invoice Date" => (!$v['tgl_invoice']) ? '' : date('d M Y', strtotime($v['tgl_invoice'])),
                        "Paid Date" => ($v['tgl_payment'] != '') ? date('d M Y', strtotime($v['tgl_payment'])) : '',
                        "Description" => $v['nama_brg'],
                        "Invoice Value (IDR)" => $v['total_invoice'],
                        "Invoice Value (USD)" => $v['total_invoice_USD'],
                        "Payment Value (IDR)" => $v['total_payment'],
                        "Payment Value (USD)" => $v['total_payment_USD'],
                        "Tax-10% VAT (IDR)" => number_format($v['ppn'], 2),
                        "Tax-10% VAT (USD)" => number_format($v['ppn_USD'], 2),
                        "Holding Tax (IDR)" => $v['holding_tax'],
                        "Holding Tax (USD)" => $v['holding_tax_USD'],
                        "Deduction (IDR)" => $v['deduction'],
                        "Deduction (USD)" => $v['deduction_USD'],
                        "Valuta" => $v['val_kode'],
                        "Payment Aging" => $v['aging_payment'],
                    );
                }
            }

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Request Invoice To Invoice"
                    ))
                    ->setCellFormat(array(
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        11 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        12 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        13 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        14 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        15 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        16 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        17 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        18 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        19 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        20 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
        $this->view->all = $all;
    }

    public function requestinvoiceAction() {
        
    }
    
     public function paymentfinaldetailAction() {
        
    }
    
    public function viewpaymentfinaldetailAction() {
        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $supplier = $this->getRequest()->getParam('supplier');
        $year = $this->getRequest()->getParam('year');

        $this->view->type = $type;
        $this->view->trano = $trano;
        $this->view->prj_kode = $prj_kode;
        $this->view->sit_kode = $sit_kode;
        $this->view->supplier = $supplier;
        $this->view->year = $year;
    }

    public function getstorepaymentfinaldetailAction() {
        
        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $supplier = $this->getRequest()->getParam('supplier');
        $year = $this->getRequest()->getParam('year');
        $export = ($this->_getParam("export") == 'true') ? true : false;
        
        
        if (!$export) {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();
            
            $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
            $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 40;
            
            $limit_query = ' LIMIT '.$offset.','.$limit.' ;';
        }

        if ($trano != '') {$search1[] = "d.trano = '$trano'";$search2[] = "ref_number = '$trano'";$search3[] = "doc_trano = '$trano'";$search4[] = "bp.trano_ref = '$trano'";$search5[] = "rem_no = '$trano'";}
        
        if ($prj_kode != '') {$search1[] = "d.prj_kode = '$prj_kode'";$search2[] = "prj_kode = '$prj_kode'";$search3[] = "prj_kode = '$prj_kode'";$search4[] = "bp.prj_kode = '$prj_kode'";$search5[] = "prj_kode = '$prj_kode'";}

        if ($sit_kode != '') {$search1[] = "d.sit_kode ='$sit_kode'";$search2[] = "sit_kode ='$sit_kode'";$search3[] = "sit_kode ='$sit_kode'";$search4[] = "bp.sit_kode ='$sit_kode'";$search5[] = "sit_kode ='$sit_kode'";}

        if ($supplier != '') {$search1[] = "h.sup_kode = '$supplier'";}
        
        if ($year != '') {$year = explode(" ", $year); $search1[] = "YEAR(d.tgl)='$year[1]'"; $search1[] = "MONTH(d.tgl)='$year[0]'";}
        
        
        if($trano=='' && $prj_kode=='' && $sit_kode=='' && $supplier=='' && $year=''){return;}
        
        
        if (count($search1) > 0)
                $searchmain = " WHERE " . implode(" AND ", $search1);
        
        if (count($search2) > 0)
                $searchbpv = " WHERE " . implode(" AND ", $search2);
        
        if (count($search3) > 0)
                $searchpayment = " WHERE " . implode(" AND ", $search3);
        
        if (count($search4) > 0)
                $searchbrfp = implode(" AND ", $search4);
        
        if (count($search5) > 0)
                $searchpaymentrem = " WHERE " . implode(" AND ", $search5);
        
        
        if($type=='ARF')
        {
            $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT ref_number,SUM(COALESCE(total,0)) as total_bpv
                    FROM erpdb.finance_payment_voucher
                    $searchbpv AND status_bpv_wht=0 AND deleted=0  AND item_type='ARF'
                    GROUP BY ref_number;";
            $this->db->query($query);
        
            $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT doc_trano, SUM(COALESCE(total_bayar,0)) AS total_payment
                    FROM erpdb.finance_payment_arf
                    $searchpayment
                    GROUP BY doc_trano;";
            $this->db->query($query);
        
            $query = "DROP TEMPORARY TABLE IF EXISTS `arf`;
                        CREATE TEMPORARY TABLE `arf`
                        SELECT trano AS ref_number,val_kode, prj_kode, prj_nama, ket, requester,sit_kode,SUM(COALESCE(qty*harga,0)) AS trano_value,
                        CASE
                        WHEN approve ='400' THEN 'Final Approve'
                        else ''
                        END as approve
                        FROM erpdb.procurement_arfd d
                        $searchmain AND d.deleted=0 and d.approve='400' AND d.trano NOT LIKE '%P%'
                        GROUP BY trano;";
            
            $this->db->query($query);

            $query = "SELECT arf.ref_number,arf.val_kode, arf.prj_nama,bpv.total_bpv, arf.ket,arf.approve, arf.requester,  COALESCE(payment.total_payment,0) AS total_payment,arf.prj_kode,arf.sit_kode,
                        CASE
                        WHEN bpv.total_bpv = payment.total_payment THEN 'Full Paid'
                        WHEN bpv.total_bpv > payment.total_payment  AND payment.total_payment!=0 THEN 'Paid'
                        WHEN payment.total_payment=0 OR payment.total_payment is null THEN 'Unpaid'
                        END as payment_status
                        FROM arf
                        LEFT JOIN bpv ON (arf.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (arf.ref_number = payment.doc_trano);";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        
        }
        
        if($type=='BRF')
        {
        
            $query= "DROP TEMPORARY TABLE IF EXISTS `brf`;
             CREATE TEMPORARY TABLE `brf`
             SELECT trano AS ref_number,val_kode, prj_kode, sit_kode, requester, prj_nama, SUM(COALESCE(qty*harga,0)) AS trano_value, ket,
             CASE
             WHEN approve ='400' THEN 'Final Approve'
             else ''
             END as approve
             FROM erpdb.procurement_brfd d
             $searchmain AND d.deleted=0  AND  d.approve='400'
             GROUP BY trano;";
            $this->db->query($query);
            
            $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT bp.trano_ref AS ref_number, SUM(COALESCE(pv.total,0)) AS total_bpv
                    FROM erpdb.finance_payment_voucher pv
                    INNER JOIN erpdb.procurement_brfd_payment bp ON (bp.trano = pv.ref_number AND bp.deleted=0 AND $searchbrfp )
                    WHERE pv.status_bpv_wht=0 AND pv.deleted=0
                    GROUP BY pv.ref_number;";
            $this->db->query($query);
        
            $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT bp.trano_ref AS doc_trano, SUM(COALESCE(parf.total_bayar,0)) AS total_payment
                    FROM erpdb.finance_payment_arf parf
                    INNER JOIN erpdb.procurement_brfd_payment bp ON (bp.trano = parf.doc_trano AND bp.deleted=0 AND $searchbrfp)
                    GROUP BY doc_trano;";
            $this->db->query($query);

            $query = "SELECT brf.ref_number,brf.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,brf.trano_value,brf.prj_kode,brf.sit_kode,brf.requester, brf.prj_nama, brf.approve, brf.ket,
                        CASE
                        WHEN bpv.total_bpv = payment.total_payment THEN 'Full Paid'
                        WHEN bpv.total_bpv > payment.total_payment  AND payment.total_payment!=0 THEN 'Paid'
                        WHEN payment.total_payment=0 OR payment.total_payment is null THEN 'Unpaid'
                        END as payment_status
                        FROM brf
                        LEFT JOIN bpv ON (brf.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (brf.ref_number = payment.doc_trano);";
                     $this->db->query($query);
                        
 $fetch = $this->db->query($query);
                     if ($fetch) {
                         $fetch = $fetch->fetchAll();
                         $data['total'] = count($fetch);
                     }else
                     {
                         $data['total'] =0;

                     }

                  
        
        }
        
        if($type=='RPI')
        {
            $query = "DROP TEMPORARY TABLE IF EXISTS `rpi`;
                        CREATE TEMPORARY TABLE `rpi`
                        SELECT h.sup_nama,d.val_kode, d.trano AS ref_number, d.prj_kode,d.prj_nama, d.sit_kode, h.ket, SUM(COALESCE(d.qty*d.harga-d.totalwht-d.total_deduction,0)) AS trano_value,SUM(COALESCE(d.ppn,0)) AS ppn_value,
                        CASE
                        WHEN  d.approve ='400' THEN 'Final Approve'
                        else ''
                        END as approve
                        FROM erpdb.procurement_rpid d
                        INNER JOIN erpdb.procurement_rpih h ON (h.trano =d.trano)
                        $searchmain AND d.approve='400' AND d.deleted=0
                        GROUP BY d.trano, d.prj_kode, d.sit_kode;";
  
            $this->db->query($query);
            
            
            if($trano ==''){$searchbpv='WHERE ref_number IN (SELECT ref_number FROM rpi) ';}
                    
            $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT ref_number, SUM(COALESCE(total,0)) AS total_bpv
                    FROM erpdb.finance_payment_voucher
                    $searchbpv AND status_bpv_wht=0 AND deleted=0 AND item_type='RPI'
                    GROUP BY ref_number;";
        
            $this->db->query($query);
            
            
            if($trano ==''){$searchpayment="WHERE doc_trano IN (SELECT ref_number FROM rpi) ";}
            
            $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT doc_trano, SUM(COALESCE(total_bayar,0)) AS total_payment
                    FROM erpdb.finance_payment_rpi
                    $searchpayment AND stspayment ='Y'
                    GROUP BY doc_trano;";
            $this->db->query($query);
        
            $query = "SELECT rpi.prj_kode, rpi.prj_nama, rpi.ket, rpi.approve,bpv.total_bpv, rpi.sup_nama as requester,rpi.val_kode,rpi.ref_number, COALESCE(payment.total_payment,0) AS total_payment, rpi.trano_value,rpi.prj_kode,rpi.sit_kode,rpi.ppn_value,
                      CASE
                        WHEN bpv.total_bpv = payment.total_payment THEN 'Full Paid'
                        WHEN bpv.total_bpv > payment.total_payment AND payment.total_payment!=0  THEN 'Paid'
                        else 'Unpaid'
                        END as payment_status
                        FROM rpi
                         LEFT JOIN bpv ON (rpi.ref_number = bpv.ref_number)
                      LEFT JOIN payment ON (rpi.ref_number = payment.doc_trano);";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        
        }
        
        if($type=='REM')
        {
        
            $query = "DROP TEMPORARY TABLE IF EXISTS `rem`;
                        CREATE TEMPORARY TABLE `rem`
                        SELECT d.trano AS ref_number,d.val_kode, d.prj_kode, d.prj_nama, d.sit_kode,SUM(COALESCE(d.qty*d.harga,0)) AS trano_value, h.request2 as requester, h.ket,
                        CASE
                        WHEN  h.approve ='400' THEN 'Final Approve'
                        else ''
                        END as approve
                        FROM erpdb.procurement_reimbursementd d
                        LEFT JOIN erpdb.procurement_reimbursementh h ON (d.trano=h.trano)
                        $searchmain AND d.deleted=0 AND h.approve='400'
                        GROUP BY d.trano;";
            $this->db->query($query);
            
            $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT ref_number, SUM(COALESCE(total,0)) AS total_bpv
                    FROM erpdb.finance_payment_voucher
                    $searchbpv AND status_bpv_wht=0 AND deleted=0
                    GROUP BY ref_number;";
            $this->db->query($query);
        
            $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT rem_no, SUM(COALESCE(total,0)) AS total_payment
                    FROM erpdb.finance_payment_reimbursement
                    $searchpaymentrem
                    GROUP BY rem_no;";
            $this->db->query($query);
        
            $query = "SELECT rem.ref_number,rem.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,rem.trano_value,rem.prj_kode,rem.prj_nama,rem.sit_kode,rem.requester,rem.approve,rem.ket,
                        CASE
                        WHEN bpv.total_bpv = COALESCE(payment.total_payment,0) THEN 'Full Paid'
                        WHEN bpv.total_bpv > COALESCE(payment.total_payment,0) AND payment.total_payment!=0  THEN 'Paid'
                        else 'Unpaid'
                        END as payment_status
                        FROM rem
                        LEFT JOIN bpv ON (rem.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (rem.ref_number = payment.rem_no);";
            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        
        }
        
        if($type=='PPNREM')
        {
            $query = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT ref_number, SUM(COALESCE(total,0)) AS total_bpv
                    FROM erpdb.finance_payment_voucher
                    $searchbpv AND status_bpv_wht=0 AND deleted=0
                    GROUP BY ref_number;";
            $this->db->query($query);
        
            $query = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT doc_trano, SUM(COALESCE(total_bayar,0)) AS total_payment
                    FROM erpdb.finance_payment_arf
                    $searchpayment
                    GROUP BY doc_trano;";

            $this->db->query($query);
        
            $query = "DROP TEMPORARY TABLE IF EXISTS `ppn`;
                        CREATE TEMPORARY TABLE `ppn`
                        SELECT d.trano AS ref_number,d.val_kode, d.prj_kode, d.prj_nama, d.sit_kode,SUM(COALESCE(d.total,0)) AS trano_value
                        CASE
                        WHEN  h.approve ='400' THEN 'Final Approve'
                        else ''
                        END as approve
                        FROM erpdb.finance_ppn_reimbursementh AS d
                        $searchmain
                        GROUP BY d.trano;";

            $this->db->query($query);

            $query = "SELECT ppn.ref_number,ppn.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,ppn.trano_value,ppn.prj_kode,ppn.sit_kode FROM ppn
                        LEFT JOIN bpv ON (ppn.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (ppn.ref_number = payment.doc_trano);";

            $fetch = $this->db->query($query);
            if ($fetch) {
                $fetch = $fetch->fetchAll();
                $data['total'] = count($fetch);
            }else
            {
                $data['total'] =0;
                
            }
        
        }
        
        if (!$export) {
            $data['data'] = $fetch;

            $json = Zend_Json::encode($data);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
        
        } else {
            $newData = array();
            $no = 1;
            $totalTrans = 0;
            $totalVAT = 0;
            $totalBPV = 0;
            $totalPaid = 0;
            
            foreach ($fetch as $key => $val) {

                $newData[] = array(
                    "No" => $no,
                    "Prject Code" => $val['prj_kode'],
                    "Project Name" => $val['prj_nama'],
                    "Document Number" => $val['ref_number'],
                    "Requester" => $val['requester'],
                    "Status" => $val['approve'],
                    "Description" => $val['ket'],
                    "Amount" => $val['trano_value'],
                    "Total Payment" => $val['total_payment'],
                    "Payment" => $val['payment_status'],
                    "Payment Date" => '',
                    "Note" => '',
                    "Paraf Treasury Off(Cashier)" => '',
                    "Bank Paid Date" => '',
                );
                
      
                
                $no++;
            }

            $newData[] = array(
                    "No" => '',
                    "Poject Code" => '',
                    "Project Name" => '',
                    "Document Number" => '',
                    "Requester" => '',
                    "Status" => '',
                    "Description" => '',
                    "Amount" => '',
                    "Total Payment" => '',
                    "Payment" => '',
                    "Payment Date" => '',
                    "Note" => '',
                    "Paraf Treasury Off(Cashier)" => '',
                    "Bank Paid Date" => '',
                );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Payment Report Detail "
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
        
        
    }

}
