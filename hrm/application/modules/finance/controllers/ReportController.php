<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 9/8/11
 * Time: 3:12 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_ReportController extends Zend_Controller_Action
{
    private $arfh;
    private $arfd;
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
    private $FINANCE;
    private $DEFAULT;

    public function init()
    {
        $this->arfh = new Procurement_Model_Procurementarfh();
        $this->arfd = new Procurement_Model_Procurementarfd();
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

        $models = array(
            "BankPaymentVoucher",
            "BankPaymentVoucherD",
            "ArfAging",
            "AdjustingJournal"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);
        $models = array(
            "MasterUser",
            "ProcurementArfh",
            "AdvanceSettlementFormD",
            "AdvanceSettlementForm"
        );
        $this->DEFAULT = QDC_Model_Default::init($models);
    }

    public function bankpaymentvoucherAction ()
    {
        $trano = $this->getRequest()->getParam('trano');
        if ($trano)
        {
            $this->view->trano = $trano;
        }
    }

    public function getpaymentarfAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null)
        {
            $search = null;
        }else if ($textsearch != null && $option == 1)
        {
            $search = "trano like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 2)
        {
            $search = "prj_kode like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 3)
        {
            $search = "prj_nama like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 4)
        {
            $search = "sit_kode like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 5)
        {
            $search = "sit_nama like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 6)
        {
            $search = "doc_trano like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->payment_arf->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->payment_arf->fetchAll()->count();

//        $data['data'] = $this->arfh->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
//        $data['total'] = $this->arfh->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpaymentrpiAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null)
        {
            $search = null;
        }else if ($textsearch != null && $option == 1)
        {
            $search = "trano like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 2)
        {
            $search = "prj_kode like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 3)
        {
            $search = "prj_nama like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 4)
        {
            $search = "sit_kode like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 5)
        {
            $search = "sit_nama like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 6)
        {
            $search = "doc_trano like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->payment_rpi->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->payment_rpi->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getvoucherAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null)
        {
            $search = "";
        }else if ($textsearch != null && $option == 1)
        {
            $search = "trano like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 2)
        {
            $search = "tgl like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 3)
        {
            $search = "ref_number like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = $this->db->select()
                   ->from(array('finance_payment_voucher'))
                   ->where($search)
                   ->order(array($sort . ' ' . $dir))
                       ->group(array("trano"))
                   ->limit($limit,$offset);

        if ($search == '')
            $sql->reset(Zend_Db_Select::WHERE);

        $data['data'] = $this->db->fetchAll($sql);

//        $data['data'] = $this->voucher->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->voucher->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showinvoicesummaryAction()
    {
        
    }

    public function invoicesummaryAction()
    {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");

        if ($prjKode != '')
            $where = " AND a.prj_kode = '$prjKode'";
        if ($sitKode != '')
            $where .= " AND a.sit_kode = '$sitKode'";

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

        $sql = "
            SELECT
                SQL_CALC_FOUND_ROWS
                d.co_no,
                d.inv_no,
                d.paid_no,
                d.val_kode,
                d.tgl,
                d.tgl_invoice,
                d.tgl_payment,
                d.nama_brg,
                d.total,
                d.total_invoice,
                d.total_payment,
                d.aging,
                d.aging_payment,
                SUM(d.jumlah) AS jumlah,
                SUM(d.ppn) AS ppn,
                SUM(d.holding_tax_val) AS holding_tax,
                SUM(d.deduction) AS deduction
            FROM (
                SELECT
                  a.trano,
                  a.co_no,
                  a.total,
                  a.tgl,
                  b.total AS total_invoice,
                  b.nama_brg,
                  b.val_kode,
                  b.jumlah,
                  b.trano as inv_no,
                  b.tgl as tgl_invoice,
                  (IF(b.tgl IS NOT NULL,DATEDIFF(b.tgl,a.tgl),0)) AS aging,
                  (IF(b.statusppn = 'Y',(b.qty*b.harga*0.1),0)) AS ppn,
                  COALESCE(b.holding_tax_val,0) AS holding_tax_val,
                  COALESCE(b.deduction,0) AS deduction,
                  c.trano AS paid_no,
                  c.total AS total_payment,
                  c.tgl AS tgl_payment,
                  (IF(c.tgl IS NULL,DATEDIFF(NOW(),b.tgl),'')) AS aging_payment
                FROM (finance_request_invoice a
                LEFT JOIN finance_invoiced b
                ON a.trano = b.riv_no)
                LEFT JOIN finance_payment_invoice c
                ON b.trano = c.inv_no
                WHERE b.trano IS NOT NULL
                $where
                ORDER BY a.trano ASC
            ) d 
                GROUP BY d.trano,d.inv_no,d.paid_no LIMIT $offset,20 
        ";

        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
        $data = array();
        if($fetch)
        {
            foreach($fetch as $k => $v)
            {
                $coNo = $v['co_no'];
                $data[$coNo][] = array(
                    "inv_no" => $v['inv_no'],
                    "tgl" => date('d M Y',strtotime($v['tgl'])),
                    "tglInvoice" => date('d M Y',strtotime($v['tgl_invoice'])),
                    "tglPaymentInvoice" => ($v['tgl_payment'] != '') ? date('d M Y',strtotime($v['tgl_payment'])) : '',
                    "nama_brg" => $v['nama_brg'],
                    "totalRequestInvoice" => $v['total'],
                    "totalInvoice" => $v['total_invoice'],
                    "totalPaymentInvoice" => $v['total_payment'],
                    "ppn" => $v['ppn'],
                    "holding_tax" => $v['holding_tax'],
                    "deduction" => $v['deduction'],
                    "val_kode" => $v['val_kode'],
                    "aging" => $v['aging'],
                    "agingPayment" => $v['aging_payment'],
                );
            }
        }

//        $view = new Zend_View();
//        $this->view->setHelperPath(APPLICATION_PATH . '/modules/default/views/helpers', 'Zend_View_Helper');
        
        $this->view->data = $data;
        $this->view->limitPerPage = 20;
        $this->view->totalResult = $count;
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    public function jurnalapAction ()
    {
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

    public function paymentAction ()
    {
        
    }

    public function getstoretranoAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $search = '';

        if ($textsearch == "" || $textsearch == null)
        {
            $search = null;
        }
        else
        {
            $search[] = "$option LIKE '%$textsearch%'";
        }
        if (count($search) > 0)
            $search = implode(" AND ",$search);

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 40;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        if ($type == 'ARF')
        {
            $hasil = $this->arfh->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
            $data['data'] = array();
            foreach($hasil as $k => $v)
            {
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
            $data['total'] = $this->arfh->fetchAll()->count();
        }else if ($type == 'RPI')
        {
            $hasil = $this->rpih->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
            $data['data'] = array();
            foreach($hasil as $k => $v)
            {
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
            $data['total'] = $this->rpih->fetchAll()->count();
        }else if ($type == 'REM')
        {
            $hasil = $this->remh->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
            $data['data'] = array();
            foreach($hasil as $k => $v)
            {
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
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewpaymentreportAction ()
    {
        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $supplier = $this->getRequest()->getParam('supplier');

        $this->view->type = $type;
        $this->view->trano = $trano;
        $this->view->prj_kode = $prj_kode;
        $this->view->sit_kode = $sit_kode;
        $this->view->supplier = $supplier;
    }

    public function getstorepaymentAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $supplier = $this->getRequest()->getParam('supplier');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 40;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'PV.ref_number';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        if ($trano != '')
        {
            $search[] = "PV.ref_number = '$trano'";
            $search2[] = "trano = '$trano'";
        }

        if ($prj_kode != '')
        {
            $search[] = "PV.prj_kode = '$prj_kode'";
            $search2[] = "prj_kode = '$prj_kode'";
        }

        if ($sit_kode != '')
        {
            $search[] = "PV.sit_kode = '$sit_kode'";
            $search2[] = "sit_kode = '$sit_kode'";
        }

        if ($supplier != '')
        {
            $search2[] = "sup_kode = '$supplier'";
        }

        if (count($search) > 0)
            $searchdunk = " WHERE " . implode(" AND ",$search);

        if ($type == 'RPI')
        {
            if (count($search2) > 0)
                $searchdunk = " WHERE " . implode(" AND ",$search2);
            $query = "
              DROP TEMPORARY TABLE IF EXISTS `rpiku`;
              CREATE TEMPORARY TABLE `rpiku`
              SELECT SQL_CALC_FOUND_ROWS * FROM procurement_rpih $searchdunk
                
              ORDER BY trano DESC
              LIMIT $offset,$limit;
            ";
            $this->db->query($query);

            $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');
            $query = "
              SELECT SQL_CALC_FOUND_ROWS rpi.trano AS ref_number,rpi.prj_kode,rpi.sit_kode,rpi.sup_nama,rpi.total as trano_value,sum(PV.total) as total_bpv,sum(PR.total) as total_payment
                FROM rpiku rpi LEFT JOIN finance_payment_voucher PV ON rpi.trano = PV.ref_number LEFT JOIN finance_payment_rpi PR ON PV.ref_number= PR.doc_trano
                GROUP BY rpi.trano
                ORDER BY rpi.tgl DESC;
            ";

            $fetch = $this->db->query($query);
            if ($fetch)
            {
                $fetch = $fetch->fetchAll();
//                $data['data'] = $fetch;

            }
        }else if ($type == 'ARF')
        {
            $query = "  SELECT SQL_CALC_FOUND_ROWS PV.ref_number,PV.prj_kode,PV.sit_kode,PV.total_value as trano_value,sum(PV.total) as total_bpv,sum(PR.total) as total_payment
                        FROM finance_payment_voucher PV LEFT JOIN finance_payment_arf PR ON PV.ref_number= PR.doc_trano
                        WHERE PV.item_type = '$type' $searchtrano $searhprj_kode $searchsit_kode
                        GROUP BY PV.ref_number
                        ORDER BY $sort $dir LIMIT $offset,$limit";

            $fetch = $this->db->query($query);
            if ($fetch)
            {
                $fetch = $fetch->fetchAll();
//                $data['data'] = $fetch;

                $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');
            }
        }else if ($type == 'REM')
        {
            $query = "  SELECT SQL_CALC_FOUND_ROWS PV.ref_number,PV.prj_kode,PV.sit_kode,PV.total_value as trano_value,sum(PV.total) as total_bpv,sum(PR.total) as total_payment
                        FROM finance_payment_voucher PV LEFT JOIN finance_payment_reimbursement PR ON PV.ref_number= PR.rem_no
                        WHERE PV.item_type = '$type' $searchtrano $searhprj_kode $searchsit_kode
                        GROUP BY PV.ref_number
                        ORDER BY $sort $dir LIMIT $offset,$limit";

            $fetch = $this->db->query($query);
            if ($fetch)
            {
                $fetch = $fetch->fetchAll();
//                $data['data'] = $fetch;

                $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');
            }
        }


//        $sql = "
//            SELECT SQL_CALC_FOUND_ROWS * FROM finance_payment_voucher fv LEFT JOIN accounting_close_ap ap ON fv.trano = ap.trano AND fv.ref_number = ap.ref_number
//            WHERE $where AND ap.stsclose = 0 group by fv.ref_number ORDER BY $sort $dir LIMIT $offset,$limit
//        ";7587"
//
//        $fetch = $this->db->query($sql);
//        if ($fetch)
//        {
//            $fetch = $fetch->fetchAll();
//            foreach($fetch as $k => $v)
//            {
//                $type = $v['item_type'];
//                $trano = $v['trano'];
//                $ref_number = $v['ref_number'];
//                $tots = 0;
//                switch ($type)
//                {
//                    case 'RPI':
//                        $payment = $this->paymentrpi->fetchAll("voc_trano = '$trano' AND doc_trano = '$ref_number'");
//                        $payment = $payment->toArray();
//                        if ($payment)
//                        {
//                            foreach ($payment as $k2 => $v2)
//                            {
//                                $tots += $v2['total_bayar'];
//                            }
//                        }
//                        else
//                        {
//                            $payment = $this->paymentrpi->fetchAll("doc_trano = '$ref_number'");
//                            $payment = $payment->toArray();
//                            if ($payment)
//                            {
//                                foreach ($payment as $k2 => $v2)
//                                {
//                                    $tots += $v2['total_bayar'];
//                                }
//                            }
//                        }
//                        break;
//                    case 'ARF':
//                        $payment = $this->paymentarf->fetchAll("voc_trano = '$trano' AND doc_trano = '$ref_number'");
//                        $payment = $payment->toArray();
//                        if ($payment)
//                        {
//                            foreach ($payment as $k2 => $v2)
//                            {
//                                $tots += $v2['total_bayar'];
//                            }
//                        }
//                        else
//                        {
//                            $payment = $this->paymentarf->fetchAll("doc_trano = '$ref_number'");
//                            $payment = $payment->toArray();
//          $this->db->query($query);                  if ($payment)
//                            {
//                                foreach ($payment as $k2 => $v2)
//                                {
//                                    $tots += $v2['total_bayar'];
//                                }
//                            }
//                        }
//                        break;
//                    case 'REM':
//                        $payment = $this->paymentrem->fetchAll("voc_trano = '$trano' AND rem_no = '$ref_number'");
//                        $payment = $payment->toArray();
//                        if ($payment)
//                        {
//                            foreach ($payment as $k2 => $v2)
//                            {
//                                $tots += $v2['total'];
//                            }
//                        }
//                        else
//                        {
//                            $payment = $this->paymentrem->fetchAll("rem_no = '$ref_number'");
//                            $payment = $payment->toArray();
//                            if ($payment)
//                            {
//                                foreach ($payment as $k2 => $v2)
//                                {
//                                    $tots += $v2['total'];
//                                }
//                            }
//          $this->db->query($query);              }
//
//                        break;
//                }
//
//                $fetch[$k]['total_payment'] = $tots;
//                $fetch[$k]['checkjurnal'] = 0;
//            }
//        }
//
        $data['data'] = $fetch;

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function pettycashreceivemoneyAction ()
    {
        
    }

    public function balancesheetAction()
    {

    }

    public function getyearperiodeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data['data'] = QDC_Finance_Periode::factory()->getAllYearPeriode();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getmonthperiodeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $year = $this->getRequest()->getParam("year");

        $data['data'] = QDC_Finance_Periode::factory()->getAllMonthPeriode($year);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewbalancesheetAction()
    {
        $year = $this->getRequest()->getParam("year");
        $month = $this->getRequest()->getParam("month");
        $depth = $this->getRequest()->getParam("depth");

        if (!$depth)
            $depth = 3;
        $params = array(
            "leveldepth" => $depth,
            "year" => $year,
            "month" => $month,
            "number_format" => true
        );

        $BL = QDC_Finance_BalanceSheet::factory($params)->generateBalanceSheet();

        $this->view->result = $BL;
        $this->view->periode = date("F",strtotime($year."-".$month.'-'.'01')) . " " . $year;
    }

    public function taxreconAction ()
    {
        
    }

    public function viewtaxreconAction ()
    {
        
    }

    public function gettaxAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch != null || $textsearch != '')
        {
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
                    LEFT JOIN finance_payment_rpi pr ON pv.trano = pr.voc_trano
                    WHERE ppn_ref_number IS NOT NULL ) a LEFT JOIN finance_recontax r ON r.voc_trano = a.tax_trano
                    WHERE r.voc_trano IS NULL $search
                    ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query($query);

        if ($fetch)
        {
            $fetch = $fetch->fetchAll();
        }

        $data['data'] = $fetch;
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinserttaxreconAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $taxrecondata = Zend_Json::decode($this->getRequest()->getParam('jsonData'));

//        var_dump($taxrecondata);die;

        foreach ($taxrecondata as $key => $val)
        {
            $stspost = 0;

            if ($val['stspost'] == true)
            {
                $stspost = 1;
            }

            $inserttaxrecon = array(

                "voc_trano" => $val['tax_trano'],
                "voc_date" => date('Y-m-d H:i:s',strtotime($val['tax_date'])),
                "voc_total" => floatval($val['tax_total']),
                "doc_trano" => $val['tax_refnumber'],
                "requester" => $val['tax_requester'],
                "val_kode" => $val['valuta'],
                "ppn_ref_number" => $val['tax_ppnrefnumber'],
                "payment_trano" => $val['payment_trano'],
                "payment_date" => date('Y-m-d H:i:s',strtotime($val['payment_date'])),
                "payment_total" => floatval($val['payment_totalbayar']),
                "uid" => $this->session->userName,
                "close_date" => date('Y-m-d H:i:s'),
                "stspost" => $stspost

            );

            $this->taxrecon->insert($inserttaxrecon);

            $updatebpv = array(
                "ppn_ref_number" => $val['tax_ppnrefnumber']
            );

            $this->voucher->update($updatebpv,"trano = '{$val['tax_trano']}'");
        }

        $return = array("success" => true);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getrecontaxAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

         $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'close_date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'asc';

        $data['data'] = $this->taxrecon->fetchAll()->toArray(null,array($sort . " " . $dir),$limit,$offset);
        $data['total'] = $this->taxrecon->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function profitlossAction()
    {

    }

    public function viewprofitlossAction()
    {
        $year = $this->getRequest()->getParam("year");
        $month = $this->getRequest()->getParam("month");

        $params = array(
            "year" => $year,
            "month" => $month,
            "number_format" => true
        );

        $RL = QDC_Finance_ProfitLoss::factory($params)->generateProfitLoss();

        $this->view->result = $RL;
        $this->view->periode = date("F",strtotime($year."-".$month.'-'.'01')) . " " . $year;
    }

    public function gridbpvAction()
    {
        $trano = $this->getRequest()->getParam("trano");

        $this->view->trano = $trano;
    }

    public function getbpvitemAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");

        $data = $this->FINANCE->BankPaymentVoucher->fetchAll("trano = '$trano'");

        $bpv = array();
        if ($data)
        {
            $data = $data->toArray();
            $i = 0;
            foreach($data as $k => $v)
            {
                $valKode = $v['valuta'];
                if($v['holding_tax_status'] == 'Y')
                {
                    if ($v['grossup_status'] == 'Y')
                    {
                        $total = $v['total_bayar'] + $v['holding_tax_val'];
                    }
                }
                else
                    $total = $v['total_bayar'];

                $bpv[$i] = array(
                    "id" => ($i + 1),
                    "name" => $v['ketin'],
                    "total" => $total,
                    "trano" => $v['trano'],
                    "trano" => $v['trano'],
                    "ref_number" => $v['ref_number'],
                    "val_kode" => $valKode
                );
                $i++;
                if ($v['statusppn'] == 'Y' || $v['tranoppn'] != '')
                {
                    if ($v['tranoppn'] != '')
                    {
                        $ppn = $this->FINANCE->BankPaymentVoucher->fetchRow("trano = '{$v['tranoppn']}'");
                        if ($ppn)
                        {
                            $ppn = $ppn->toArray();
                            $ppnval = $ppn['total_bayar'];
                        }
                    }
                    else
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
                if($v['holding_tax_status'] == 'Y')
                {
                    $bpv[$i] = array(
                        "id" => ($i + 1),
                        "name" => $v['holding_tax_text'],
                        "total" => -1 * $v['holding_tax_val'],
                        "trano" => $v['trano'],
                        "ref_number" => $v['ref_number'],
                        "val_kode" => $valKode
                    );
                    $i++;
                }
                if($v['deduction'] > 0)
                {
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
            }
        }

        $return['data'] = $bpv;
        $return['success'] = true;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function arfagingAction()
    {

    }

    public function viewarfagingAction()
    {
        $start = $this->getRequest()->getParam("start_date");
        $end = $this->getRequest()->getParam("end_date");
        $start2 = $this->getRequest()->getParam("start_date2");
        $end2 = $this->getRequest()->getParam("end_date2");
        $trano = $this->getRequest()->getParam("trano");
        $uid = $this->getRequest()->getParam("uid");

        $tmp = array();

        if ($uid == '')
            $users = $this->FINANCE->ArfAging->getUid();
        else
            $users = array(
                "uid" => $uid
            );

        if ($trano != '')
        {
            $tmp[] = "arf_no = '$trano'";
        }
        if ($start != '')
        {
            $start = date("Y-m-d",strtotime($start));
            if ($end == '')
                $tmp[] = "tgl = '$start'";
            else
            {
                $end = date("Y-m-d",strtotime($end));
                $tmp[] = "(tgl BETWEEN '$start' AND '$end')";
            }
        }

        if ($start2 != '')
        {
            $start2 = date("Y-m-d",strtotime($start2));
            if ($end2 == '')
                $tmp[] = "tgl = '$start2'";
            else
            {
                $end2 = date("Y-m-d",strtotime($end2));
                $tmp[] = "(tgl_akhir BETWEEN '$start2' AND '$end2')";
            }
        }
        if (count($tmp) > 0)
            $where = " AND " . implode(" AND ",$tmp);

        $arfs = array();

        foreach($users as $k => $v)
        {
            $uid = $v['uid'];
            $fetch= $this->FINANCE->ArfAging->fetchAll("uid = '{$v['uid']}' AND statussettle = 0 $where",array("uid ASC","tgl DESC"))->toArray();
            if ($fetch)
            {
                foreach($fetch as $k2 => $v2)
                {
                    $cek = $this->DEFAULT->ProcurementArfh->fetchRow("trano = '{$v2['arf_no']}'");
                    $fetch[$k2]['bt'] = true;
                    if ($cek)
                    {
                        if ($cek['bt'] == 'N')
                        {
                            $fetch[$k2]['uid'] = $cek['request'];
                            $fetch[$k2]['bt'] = false;
                        }
                    }
//                    $fetch[$k2]['total_settle'] = 0;
//                    $select = $this->db->select()
//                        ->from(
//                            array(
//                                "a" => $this->DEFAULT->AdvanceSettlementFormD->__name()
//                            ),
//                            array(
//                                "SUM(totalasf) AS totalASF"
//                            )
//                        )
//                        ->joinLeft(array('b'=>'workflow_trans'),
//                        'a.trano = b.item_id',
//                        array(
//                            'final'
//                        ))
//                        ->where("a.arf_no = '{$v2['arf_no']}' AND b.final = 1")
//                        ->group("a.arf_no")
//                        ->having("b.final = 1");
//
//                    $asfd = $this->db->fetchRow($select);
//                    if ($asfd)
//                    {
//                        $fetch[$k2]['total_settle'] = $asfd['totalASF'];
//                    }
                    $fetch[$k2]['balance'] = $fetch[$k2]['total_bayar'] - ($fetch[$k2]['total_settle'] + $fetch[$k2]['total_settle_cancel']);
                    $fetch[$k2]['username'] = QDC_User_Ldap::factory(array("uid" => $fetch[$k2]['uid']))->getName();
                    $arfs[] = $fetch[$k2];
                }
            }
        }
        $return['data'] = $arfs;

        $json = Zend_Json::encode($return);
        $this->view->json = $json;
//        $this->getResponse()->setHeader('Content-Type','text/javascript');
//        $this->getResponse()->setBody($json);

    }

    public function generaljournalAction ()
    {
        
    }

    public function viewgeneraljournalAction ()
    {
        $type = $this->getRequest()->getParam('type');
        $startdate = $this->getRequest()->getParam('startdate');
        $enddate = $this->getRequest()->getParam('enddate');
        $ref_number = $this->getRequest()->getParam('ref_number');

        $this->view->type = $type;
        $this->view->startdate = $startdate;
        $this->view->enddate = $enddate;
        $this->view->ref_number = $ref_number;
    }

}
