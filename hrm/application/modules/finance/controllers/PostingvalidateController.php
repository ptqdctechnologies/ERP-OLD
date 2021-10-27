<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/17/11
 * Time: 11:14 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_PostingvalidateController extends Zend_Controller_Action
{
    private $session;
    private $db;
    private $FINANCE;

    public function init()
    {
        $this->session = new Zend_Session_Namespace('login');
        $this->db = Zend_Registry::get('db');
        //Pakai library QDC untuk autoload model...
        //Definisikan model yg akan dipakai, kosongkan jika ingin memakai semua model (not recommended)..
        $models = array(
            "PaymentRPI",
            "PaymentARF",
            "PaymentReimbursH",
            "BankPaymentVoucher",
            "BankPaymentVoucherD",
            "MasterCoa",
            "BankSpendMoney",
            "AccountingJurnalBank",
            "AccountingCloseAP",
            "AccountingCloseAR",
            "Invoice",
            "InvoiceDetail",
            "PaymentInvoice",
            "AccountingSaldoRL",
            "AccountingSaldoCoa"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function validatemenuAction ()
    {
        
    }

    public function createvalidateAction ()
    {
        $this->view->startdate = date("Y-m-d",strtotime("-7 days"));
        $this->view->enddate = date('Y-m-d');

    }

    public function createvalidatearAction ()
    {
        $this->view->startdate = date("Y-m-d",strtotime("-7 days"));
        $this->view->enddate = date('Y-m-d');
    }

    public function getvalidateAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $where = "DATE(tgl) = '2011-11-09'";

        $data['data'] = $this->FINANCE->PaymentRPI->fetchAll($where)->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getvoucherAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();


        $start = $this->getRequest()->getParam('start');
        $end = $this->getRequest()->getParam('end');
        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');

//        var_dump($type,$trano);die;

        if ($start != '' && $end != '')
        {
            $startdate = date('Y-m-d',strtotime($start));
            $enddate = date('Y-m-d',strtotime($end));
        }
        else
        {
            $startdate = date("Y-m-d",strtotime("-7 days"));
            $enddate = date('Y-m-d');
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';


        $where = "DATE(fv.tgl) between '$startdate' AND '$enddate'";

        if ($type != '')
        {
            $where .= " AND fv.ref_number LIKE '$type%' ";
        }

        if ($trano != '')
        {
            $where .= " AND (fv.ref_number LIKE '%$trano%') OR (fv.trano LIKE '%$trano%')  ";
        }

        if ($where != '')
            $where = $where . " AND ";

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS closing;
            CREATE TEMPORARY TABLE closing
            SELECT fv.*,SUM(fv.total_bayar) AS tot_total_bayar FROM finance_payment_voucher fv LEFT JOIN accounting_jurnal_bank ap ON fv.trano = ap.trano AND fv.ref_number = ap.ref_number
            WHERE $where item_type IN ('ARF','REM') AND ap.stspost = 0 group by fv.ref_number ORDER BY $sort $dir;

            INSERT INTO closing
            SELECT fv.*,SUM(fv.total_bayar) AS tot_total_bayar FROM finance_payment_voucher fv LEFT JOIN accounting_close_ap ap ON fv.trano = ap.trano AND fv.ref_number = ap.ref_number
            WHERE $where item_type = 'RPI' AND ap.stspost = 0 group by fv.ref_number ORDER BY $sort $dir;
        ";
        
        $this->db->query($sql);

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM closing ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query($sql);

        if ($fetch)
        {
            $fetch = $fetch->fetchAll();
            foreach($fetch as $k => $v)
            {
                $fetch[$k]['total_bayar'] = $v['tot_total_bayar'];
                $type = $v['item_type'];
                $trano = $v['trano'];
                $ref_number = $v['ref_number'];
                $tots = 0;
                switch ($type)
                {
                    case 'RPI':
                        $payment = $this->FINANCE->PaymentRPI->fetchAll("voc_trano = '$trano' AND doc_trano = '$ref_number'");
                        $payment = $payment->toArray();
                        if ($payment)
                        {
                            foreach ($payment as $k2 => $v2)
                            {
                                $tots += $v2['total_bayar'];
                            }
                        }
                        else
                        {
                            $payment = $this->FINANCE->PaymentRPI->fetchAll("doc_trano = '$ref_number'");
                            $payment = $payment->toArray();
                            if ($payment)
                            {
                                foreach ($payment as $k2 => $v2)
                                {
                                    $tots += $v2['total_bayar'];
                                }
                            }
                        }
                        break;
                    case 'ARF':
                        $payment = $this->FINANCE->PaymentARF->fetchAll("voc_trano = '$trano' AND doc_trano = '$ref_number'");
                        $payment = $payment->toArray();
                        if ($payment)
                        {
                            foreach ($payment as $k2 => $v2)
                            {
                                $tots += $v2['total_bayar'];
                            }
                        }
                        else
                        {
                            $payment = $this->FINANCE->PaymentARF->fetchAll("doc_trano = '$ref_number'");
                            $payment = $payment->toArray();
                            if ($payment)
                            {
                                foreach ($payment as $k2 => $v2)
                                {
                                    $tots += $v2['total_bayar'];
                                }
                            }
                        }
                        break;
                    case 'REM':
                        $payment = $this->FINANCE->PaymentReimbursH->fetchAll("voc_trano = '$trano' AND rem_no = '$ref_number'");
                        $payment = $payment->toArray();
                        if ($payment)
                        {
                            foreach ($payment as $k2 => $v2)
                            {
                                $tots += $v2['total'];
                            }
                        }
                        else
                        {
                            $payment = $this->FINANCE->PaymentReimbursH->fetchAll("rem_no = '$ref_number'");
                            $payment = $payment->toArray();
                            if ($payment)
                            {
                                foreach ($payment as $k2 => $v2)
                                {
                                    $tots += $v2['total'];
                                }
                            }
                        }

                        break;
                }

                $fetch[$k]['total_payment'] = $tots;
                if ($type == 'RPI')
                {
                    $fetch[$k]['checkjurnal'] = 0;
                    $fetch[$k]['checkjurnalbank'] = 0;
                }
                else
                {
                    $fetch[$k]['checkjurnal'] = 1;
                    $fetch[$k]['checkjurnalbank'] = 0;
                }
            }
        }

        $data['data'] = $fetch;
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');
//        $data['data'] = $this->bpv->fetchAll($where,array($sort . " " . $dir),$limit,$offset)->toArray();
//        $data['total'] = $this->bpv->fetchAll($where)->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertcloseapAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $closeapdata = Zend_Json::decode($this->getRequest()->getParam('jsonData'));


        $tgl = date('Y-m-d H:i:s');
        $uid = $this->session->userName;

        $periode = QDC_Finance_Periode::factory()->getCurrentPeriode();

        if ($periode == false)
        {
            echo "{success: false, msg: 'Current Periode is not Exists, Please create new Periode from Master Periode!'}";
            die;
        }

        $perKode = $periode['bulan'];
        $perTahun = $periode['tahun'];
        $models = QDC_Model_Finance::init(
            array(
                "MasterCoa",
                "AccountingSaldoRL",
                "AccountingSaldoCoa"
            )
        );

        foreach($closeapdata as $key => $val)
        {
            $stspost = $val['stspost'];
            $ref_number = $val['ref_number'];
            $trano = $val['trano'];
            if ($stspost == true)
            {
                $stspost = 1;
            }

            $updatestatus = array(
                "stspost" => $stspost,
                "uidpost" => $uid,
                "tglpost" => new Zend_Db_Expr("NOW()")
            );

            $this->FINANCE->AccountingCloseAP->update($updatestatus,"trano = '$trano' AND ref_number = '$ref_number'");
            $this->FINANCE->AccountingJurnalBank->update($updatestatus,"trano = '$trano' AND ref_number = '$ref_number'");

            $datajurnalbank = $this->FINANCE->AccountingJurnalBank->fetchAll("trano = '$trano' AND ref_number = '$ref_number'");

            foreach($datajurnalbank as $key2 => $val2)
            {
                $insertbankout = array(

                    "trano" => $val2['trano'],
                    "ref_number" => $val2['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $val2['coa_kode'],
                    "coa_nama" => $val2['coa_nama'],
                    "val_kode" => $val2['val_kode'],
                    "credit" => $val2['credit'],
                    "debit" => $val2['debit']

                );

                $this->FINANCE->BankSpendMoney->insert($insertbankout);

//                $coaKode = $val2['coa_kode'];
//                $coaNama = $val2['coa_nama'];
//                $valKode = $val2['val_kode'];
//                $cek = $models->MasterCoa->fetchRow("coa_kode = '$coaKode'");
//
//                $operand = '+';
//                $totInsert = 0;
//                if ($val2['credit'] != '' && $val2['credit'] > 0)
//                {
//                    if ($cek['dk'] == 'Debit')
//                        $operand = '-';
//                    $totInsert = floatval($val2['credit']);
//                }
//                else if ($val2['debit'] != '' && $val2['debit'] > 0)
//                {
//                    if ($cek['dk'] == 'Credit')
//                        $operand = '-';
//                    $totInsert = floatval($val2['debit']);
//                }
//
//                $cekSaldo = $models->AccountingSaldoCoa->fetchRow("coa_kode = '$coaKode' AND periode = '$perKode' AND tahun = '$perTahun'");
//                $exhangeRate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
//                $rateidr = $exhangeRate['rateidr'];
//
//                if (!$cekSaldo)
//                {
//                    $prevTotal = 0;
//                    $prevPeriode = QDC_Finance_Periode::factory()->getPreviousPeriode($periode);
//                    if ($prevPeriode)
//                    {
//                        $cekPrevSaldo = $models->AccountingSaldoCoa->fetchRow("coa_kode = '$coaKode' AND periode = '{$prevPeriode['bulan']}' AND tahun = '{$prevPeriode['tahun']}'");
//                        if ($cekPrevSaldo)
//                        {
//                            $prevTotal = floatval($cekPrevSaldo['total']);
//                        }
//                    }
//                    if ($operand == '-')
//                        $totInsert = -1 * $totInsert;
//                    $saldoInsert = array(
//                        "coa_kode" => $coaKode,
//                        "coa_nama" => $coaNama,
//                        "total" => $totInsert + $prevTotal,
//                        "periode" => $perKode,
//                        "tahun" => $perTahun,
//                        "val_kode" => $valKode,
//                        "rateidr" => $rateidr
//                    );
//
//
//                    $logInsert =$saldoInsert;
//                    $logInsert['ref_number'] = $trano;
//                    $logInsert['bulan'] = $periode['bulan'];
//                    $logInsert['perkode'] = $periode['perkode'];
//                    $logInsert['action'] = 'NEW';
//
//                    QDC_Log_Accounting::factory()->insert($logInsert);
//
//                    $models->AccountingSaldoCoa->insert($saldoInsert);
//                    $models->AccountingSaldoRL->insert($saldoInsert);
//
//                }
//                else
//                {
//                    $logInsert = array(
//                        "coa_kode" => $coaKode,
//                        "coa_nama" => $coaNama,
//                        "total" => (($operand == '-') ? -1 * floatval($totInsert) : $totInsert),
//                        "periode" => $perKode,
//                        "tahun" => $perTahun,
//                        "val_kode" => $valKode,
//                        "rateidr" => $rateidr
//                    );
//
//                    $logInsert['ref_number'] = $trano;
//                    $logInsert['bulan'] = $periode['bulan'];
//                    $logInsert['perkode'] = $periode['perkode'];
//                    $logInsert['action'] = 'UPDATE';
//
//                    QDC_Log_Accounting::factory()->insert($logInsert);
//                    $sql = "UPDATE
//                        {$models->AccountingSaldoCoa->name}
//                        SET total = total $operand $totInsert
//                        WHERE
//                            coa_kode = '$coaKode'
//                            AND periode = '$perKode'
//                            AND tahun = '$perTahun'";
//                    $models->AccountingSaldoCoa->db->query($sql);
//                    $sql = "UPDATE
//                        {$models->AccountingSaldoRL->name}
//                        SET total = total $operand $totInsert
//                        WHERE
//                            coa_kode = '$coaKode'
//                            AND periode = '$perKode'
//                            AND tahun = '$perTahun'";
//                    $models->AccountingSaldoRL->db->query($sql);
//                }
            }
        }

        $json = "{success: true}";
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertclosearAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $closeapdata = Zend_Json::decode($this->getRequest()->getParam('jsonData'));


        $tgl = date('Y-m-d H:i:s');
        $uid = $this->session->userName;

        $periode = QDC_Finance_Periode::factory()->getCurrentPeriode();

        if ($periode == false)
        {
            echo "{success: false, msg: 'Current Periode is not Exists, Please create new Periode from Master Periode!'}";
            die;
        }

        $perKode = $periode['bulan'];
        $perTahun = $periode['tahun'];
        $models = QDC_Model_Finance::init(
            array(
            )
        );

        foreach($closeapdata as $key => $val)
        {
            $stspost = $val['stspost'];
            $ref_number = $val['ref_number'];
            $trano = $val['trano'];
            if ($stspost == true)
            {
                $stspost = 1;
            }

            $updatestatus = array(
                "stspost" => $stspost,
                "uidpost" => $uid,
                "tglpost" => new Zend_Db_Expr("NOW()")
            );

            $this->FINANCE->AccountingCloseAR->update($updatestatus,"trano = '$trano' AND ref_number = '$ref_number'");
            $this->FINANCE->AccountingJurnalBank->update($updatestatus,"ref_number = '$trano' AND type = 'AR-INV'");

            $datajurnalbank = $this->FINANCE->AccountingJurnalBank->fetchAll("trano = '$trano' AND ref_number = '$ref_number'");

//            foreach($datajurnalbank as $key2 => $val2)
//            {
//                $insertbankout = array(
//
//                    "trano" => $val2['trano'],
//                    "ref_number" => $val2['ref_number'],
//                    "tgl" => $tgl,
//                    "uid" => $uid,
//                    "coa_kode" => $val2['coa_kode'],
//                    "coa_nama" => $val2['coa_nama'],
//                    "val_kode" => $val2['val_kode'],
//                    "credit" => $val2['credit'],
//                    "debit" => $val2['debit']
//
//                );
//
//                $this->FINANCE->BankSpendMoney->insert($insertbankout);
//
//
//            }
        }

        $json = "{success: true}";
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getjurnalapAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bpv_trano = $this->getRequest()->getParam('bpv_trano');
        $ref_number = $this->getRequest()->getParam('ref_number');

        $data['data'] = $this->FINANCE->AccountingCloseAP->fetchAll("ref_number = '$ref_number'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getjurnalarAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bpv_trano = $this->getRequest()->getParam('bpv_trano');
        $ref_number = $this->getRequest()->getParam('ref_number');

        $data['data'] = $this->FINANCE->AccountingCloseAR->fetchAll("ref_number = '$ref_number'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatecoaAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $jurnalapdata = Zend_Json::decode($this->getRequest()->getParam('datajurnal'));

        $trano = $jurnalapdata[0]['trano'];
        $ref_number = $jurnalapdata[0]['ref_number'];

//
//        var_dump($jurnalapdata);die;

        $id = '';

        $coaArray = array('coa_kode','coa_holding_tax','coa_ppn');

        foreach($jurnalapdata as $key => $val)
        {
            $coa = $val['coa_kode_old'];
            foreach($coaArray as $k2 => $v2)
            {
                $cekdata = $this->FINANCE->BankPaymentVoucher->fetchRow("trano = '$trano' AND ref_number = '$ref_number' AND $v2 = '$coa'");
                
                if ($cekdata)
                {
                    $cekdata = $cekdata->toArray();

                    $idEdit = $cekdata['id'];
                    $arrayUpdate = array(
                        "$v2" => $val['coa_kode']
                    );

                    $this->FINANCE->BankPaymentVoucher->update($arrayUpdate,"id = $idEdit");
                    if ($v2 == 'coa_kode')
                    {
                        $this->FINANCE->BankPaymentVoucherD->update($arrayUpdate,"trano = '$trano' AND ref_number = '$ref_number'");
                    }
                }
            }

            $id = $val['id'];

            $updatecoa = array(
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama']
            );

            $this->FINANCE->AccountingCloseAP->update($updatecoa,"id = '$id'");
        }



//        var_dump($jurnalapdata);die;

        $json = "{success: true}";
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getjurnalbankAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bpv_trano = $this->getRequest()->getParam('bpv_trano');
        $ref_number = $this->getRequest()->getParam('ref_number');

        $data['data'] = $this->FINANCE->AccountingJurnalBank->fetchAll("ref_number = '$ref_number' AND type = 'BPV'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatecoabankAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $jurnalbankdata = Zend_Json::decode($this->getRequest()->getParam('datajurnal'));

        $trano = $jurnalbankdata[0]['trano'];
        $ref_number = $jurnalbankdata[0]['ref_number'];

//
//        var_dump($jurnalapdata);die;

        $id = '';

        $coaArray = array('coa_kode','coa_holding_tax','coa_ppn');

        foreach($jurnalbankdata as $key => $val)
        {
//            $coa = $val['coa_kode_old'];
//            foreach($coaArray as $k2 => $v2)
//            {
//                $cekdata = $this->bpv->fetchRow("trano = '$trano' AND ref_number = '$ref_number' AND $v2 = '$coa'");
//
//                if ($cekdata)
//                {
//                    $cekdata = $cekdata->toArray();
//
//                    $idEdit = $cekdata['id'];
//                    $arrayUpdate = array(
//                        "$v2" => $val['coa_kode']
//                    );
//
//                    $this->bpv->update($arrayUpdate,"id = $idEdit");
//                    if ($v2 == 'coa_kode')
//                    {
//                        $this->bpvd->update($arrayUpdate,"trano = '$trano' AND ref_number = '$ref_number'");
//                    }
//                }
//            }
//
            $id = $val['id'];

            $updatecoa = array(
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama']
            );

            $this->FINANCE->AccountingJurnalBank->update($updatecoa,"id = '$id'");
        }

        $json = "{success: true}";
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getinvoiceAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();


        $start = $this->getRequest()->getParam('start');
        $end = $this->getRequest()->getParam('end');
        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');

        if ($start != '' && $end != '')
        {
            $startdate = date('Y-m-d',strtotime($start));
            $enddate = date('Y-m-d',strtotime($end));
        }
        else
        {
            $startdate = date("Y-m-d",strtotime("-7 days"));
            $enddate = date('Y-m-d');
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';


        $where = "DATE(inv.tgl) between '$startdate' AND '$enddate'";

        if ($type != '')
        {
            $where .= " AND inv.ref_number LIKE '$type%' ";
        }

        if ($trano != '')
        {
            $where .= " AND (inv.ref_number LIKE '%$trano%') OR (inv.trano LIKE '%$trano%')  ";
        }

        if ($where != '')
            $where = $where . " AND ";

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS closing;
            CREATE TEMPORARY TABLE closing
            SELECT inv.*,inv.riv_no AS ref_number FROM finance_invoice inv LEFT JOIN accounting_close_ar ar ON inv.trano = ar.trano AND inv.riv_no = ar.ref_number
            WHERE $where ar.stspost = 0 group by inv.riv_no ORDER BY $sort $dir;
        ";

        $this->db->query($sql);

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM closing ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query($sql);

        if ($fetch)
        {
            $fetch = $fetch->fetchAll();
            $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');
            foreach($fetch as $k => $v)
            {
                //$fetch[$k]['total_bayar'] = $v['tot_total'];
                $select = $this->db->select()
                    ->from(
                        array($this->FINANCE->InvoiceDetail->name()),
                        array("tot_total" => new Zend_Db_Expr("SUM(jumlah)"))
                    )
                    ->where("trano = '{$v['trano']}'");
                $fetch2 = $this->db->fetchRow($select);
                $fetch[$k]['tot_total'] = $fetch2['tot_total'];
                $trano = $v['trano'];
                $ref_number = $v['riv_no'];
                $select = $this->db->select()
                    ->from(
                        array($this->FINANCE->PaymentInvoice->name()),
                        array("total_bayar" => new Zend_Db_Expr("SUM(total)"))
                    )
                    ->where("inv_no = '$trano'")
                    ->group(array("inv_no"));

                $fetch2 = $this->db->fetchRow($select);

                $fetch[$k]['total_payment'] = $fetch2['total_bayar'];
                $fetch[$k]['total_bayar'] = $fetch2['total_bayar'];
                $fetch[$k]['checkjurnal'] = 0;
                $fetch[$k]['checkjurnalbank'] = 0;
            }
        }
        $data['data'] = $fetch;

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

}
