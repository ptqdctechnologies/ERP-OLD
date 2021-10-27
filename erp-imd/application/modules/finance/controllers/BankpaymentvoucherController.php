<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 9/13/11
 * Time: 8:55 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_BankpaymentvoucherController extends Zend_Controller_Action
{
    private $arfh;
    private $arfd;
    private $brfh;
    private $brfd;
    private $rpih;
    private $rpid;
    private $coa;
    private $voucher;
    private $session;
    private $db;
    private $log_trans;
    private $file;
    private $remh;
    private $remd;
    private $workflow_trans;
    private $voucherd;
    private $jurnalap;
    private $pulsa;
//    private $jurnalcip;

//    private $payment_arf;
//    private $payment_rpi;

    public function init()
    {
        $this->session = new Zend_Session_Namespace('login');
        $this->arfh = new Procurement_Models_Procurementarfh();
        $this->arfd = new Procurement_Models_Procurementarfd();
        $this->brfh = new Procurement_Models_BusinessTripHeader();
        $this->brfd = new Procurement_Models_BusinessTripDetail();
        $this->rpid = new Default_Models_RequestPaymentInvoice();
        $this->rpih = new Default_Models_RequestPaymentInvoiceH();
        $this->coa = new Finance_Models_MasterCoa();
        $this->voucher = new Finance_Models_BankPaymentVoucher();
        $this->db = Zend_Registry::get('db');
        $this->log_trans = new Procurement_Model_Logtransaction();
        $this->file = new Default_Models_Files();
        $this->remh = new Default_Models_ReimbursH();
        $this->remd = new Default_Models_ReimbursD();
        $this->workflow_trans = new Admin_Models_Workflowtrans();
        $this->voucherd = new Finance_Models_BankPaymentVoucherD();
        $this->jurnalap = new Finance_Models_AccountingCloseAP();
        $this->pulsa = new Admin_Models_KodePulsa();
//        $this->jurnalcip = new Finance_Models_AccountingCloseCip();
//        $this->payment_arf = new Finance_Models_PaymentARF();
//        $this->payment_rpi = new Finance_Models_PaymentRPI();
    }

    public function paymentvoucherAction ()
    {
        
    }

    public function insertvoucherAction ()
    {
        
    }

    public function getarfAction ()
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
            $search = "WHERE trano like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 2)
        {
            $search = "WHERE prj_kode like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 3)
        {
            $search = "WHERE prj_nama like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 4)
        {
            $search = "WHERE sit_kode like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 5)
        {
            $search = "WHERE sit_nama like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';


        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_arfh $search GROUP BY trano ORDER BY $sort $dir LIMIT $offset,$limit ";
        $fetch = $this->db->query ($sql);
        $data['data'] = $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

//        $data['data'] = $this->arfh->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        foreach($data['data'] as $k => $v)
        {
            $trano = $v['trano'];
            $sql = "SELECT COALESCE(SUM(COALESCE(total_bayar,0)),0) as total FROM finance_payment_voucher WHERE ref_number = '$trano' AND deleted=0 GROUP BY ref_number";
            $fetch = $this->db->query($sql);
            $fetch = $fetch->fetch();
            if ($fetch){
                $data['data'][$k]['totalBalance'] = floatval($v['total']) - floatval($fetch['total']);
                $data['data'][$k]['totalpayment'] = floatval($fetch['total']);
            }else{
                $data['data'][$k]['totalBalance'] = floatval($v['total']);
                $data['data'][$k]['totalpayment'] = floatval($fetch['total']);
            }

        }

//        $data['total'] = $this->arfh->fetchAll()->count();

//        $data['data'] = $this->arfh->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
//        $data['total'] = $this->arfh->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getrpiAction ()
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
            $search = "WHERE trano like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 2)
        {
            $search = "WHERE prj_kode like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 3)
        {
            $search = "WHERE prj_nama like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 4)
        {
            $search = "WHERE sit_kode like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 5)
        {
            $search = "WHERE sit_nama like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 6)
        {
            $search = "WHERE doc_trano like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_rpih $search AND deleted=0 GROUP BY trano ORDER BY $sort $dir LIMIT $offset,$limit ";
        $fetch = $this->db->query ($sql);
        $data['data'] = $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

//        $data['data'] = $this->rpih->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        foreach($data['data'] as $k => $v)
        {
            $trano = $v['trano'];
            $sql = "SELECT COALESCE(SUM(COALESCE(total_bayar,0)),0) as total FROM finance_payment_voucher WHERE ref_number = '$trano' AND deleted=0 GROUP BY ref_number";
            $fetch = $this->db->query($sql);
            $fetch = $fetch->fetch();
            if ($fetch){
                $data['data'][$k]['totalBalance'] = floatval($v['total']) - floatval($fetch['total']);
                $data['data'][$k]['totalpayment'] = floatval($fetch['total']);
            }else{
                $data['data'][$k]['totalBalance'] = floatval($v['total']);
                $data['data'][$k]['totalpayment'] = floatval($fetch['total']);
            }
        }

//        $data['total'] = $this->rpih->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getcoalistAction ()
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
            $search = "coa_kode like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 2)
        {
            $search = "coa_nama like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 3)
        {
            $search = "tipe like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->coa->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();

        $data['total'] = $this->coa->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertvoucherAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $voucherdata = Zend_Json::decode($this->getRequest()->getParam('voucherdata'));
        $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));

//        var_dump($voucherdata);die;
//
        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans('BPV');
        $tgl = date('Y-m-d H:i:s');
        $uid = $this->session->userName;

        foreach ($voucherdata as $key => $val)
        {
            $insertdata = array(
                'trano' => $trano,
                'tgl' => $tgl,
                'ref_number' => $val['trano'],
                'item_type' => $val['type'],
                'total_bayar' => str_replace(",","",$val['amount']),
                'statusppn' => $val['ppn'],
                'valueppn' => str_replace(",","",$val['ppnval']),
                'holding_tax_status' => $val['ht'],
                'holding_tax' => str_replace(",","",$val['ht_com']),
                'holding_tax_val' => str_replace(",","",$val['ht_val']),
                'holding_tax_text' => $val['ht_text'],
                'valuta' => $val['valuta'],
                'prj_kode' => $val['prj_kode'],
                'sit_kode' => $val['sit_kode'],
                'coa_kode' => $val['acc_no'],
                'ketin' => $val['description'],
                'uid' => $uid,
                'requester' => $val['requester'],
                'total' => str_replace(",","",$val['amount_total']),
                'deduction' => str_replace(",","",$val['deduction'])
            );

            $this->voucher->insert($insertdata);

        }

        if (count($filedata) > 0)
        {
            foreach ($filedata as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => $voucherdata[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->file->insert($arrayInsert);
            }
        }

        $json = "{success: true, number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editvoucherAction ()
    {
        
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
            $search = "trano like '%$textsearch%' AND deleted=0 ";
        }else if ($textsearch != null && $option == 2)
        {
            $search = "tgl like '%$textsearch%' AND deleted=0 ";
        }else if ($textsearch != null && $option == 3)
        {
            $search = "item_type like '%$textsearch%' AND deleted=0 ";
        }

        if ($search != "")
            $search .= " AND status_bpv_ppn = 0 AND deleted=0";
        else
            $search = "status_bpv_ppn = 0 AND deleted=0";
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

        foreach ($data['data'] as $key => $val)
        {
//            $statuspulsa = $val['statuspulsa'];
//
            if ($val['statuspulsa'] == '1')
            {
                $data['data'][$key]['statuspulsa'] = 'pulsa';
            }
        }

//        var_dump($data);die;

//        $data['data'] = $this->voucher->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->voucher->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function gettransAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $select = $this->db->select()
            ->from(array($this->jurnalap->__name()),array(
                "rateidr"
            ))
            ->where("trano = ?",$trano)
            ->group(array("trano"));

        $rate = $this->db->fetchOne($select);

        if ($rate == '' || $rate == 0)
        {
            $r = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
            $rate = $r['rateidr'];
        }

        $data = $this->voucher->fetchAll("trano = '$trano'")->toArray();
        foreach ($data as $key => $val)
        {
//            $statuspulsa = $val['statuspulsa'];
//
            if ($val['statuspulsa'] == '1')
            {
                $data[$key]['statuspulsa'] = 'pulsa';
            }

            if ($val['trano_ppn'] != '')
            {
                $data[$key]['have_bpv_ppn'] = 1;
                $cekPPN = $this->voucher->fetchRow("trano = '{$val['trano_ppn']}'");
                if ($cekPPN)
                {
                    $totalPPN = $cekPPN['total'];
                }
                $data[$key]['statusppn']  = 'Y';
                $data[$key]['valueppn'] = $totalPPN;
                $data[$key]['ppn_ref_number'] = $cekPPN['ppn_ref_number'];
            }
            $data[$key]['tgl'] = date("d-m-Y",strtotime($val['tgl']));
            $data[$key]['rateidr'] = $rate;
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdatevoucherAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $voucherdata = Zend_Json::decode($this->getRequest()->getParam('voucherdata'));
        $jsonFile = Zend_Json::decode($this->getRequest()->getParam('filedata'));
        $jsonDeletedFile = Zend_Json::decode($this->getRequest()->getParam('deletedfile'));
        $trano = $this->getRequest()->getParam('trano');
//        var_dump($file);die;
        
        $tgl = date('Y-m-d H:i:s');
        $uid = $this->session->userName;

        $log['voucher-before'] = $this->voucher->fetchAll("trano = '$trano'")->toArray();

        $tglAsli = $log['voucher-before'][0]['tgl'];

        $this->voucher->delete("trano = '$trano'");

        foreach ($voucherdata as $key => $val)
        {
            $insertdata = array(
                'trano' => $trano,
                'tgl' => $tglAsli,
                'ref_number' => $val['ref_number'],
                'item_type' => $val['item_type'],
                'total_bayar' => str_replace(",","",$val['total_bayar']),
                'statusppn' => $val['statusppn'],
                'valueppn' => str_replace(",","",$val['valueppn']),
                'holding_tax_status' => $val['holding_tax_status'],
                'holding_tax' => str_replace(",","",$val['holding_tax']),
                'holding_tax_val' => str_replace(",","",$val['holding_tax_val']),
                'holding_tax_text' => $val['holding_tax_text'],
                'valuta' => $val['valuta'],
                'prj_kode' => $val['prj_kode'],
                'sit_kode' => $val['sit_kode'],
                'coa_kode' => $val['coa_kode'],
                'ketin' => $val['ketin'],
                'uid' => $uid,
                'requester' => $val['requester'],
                'total' => str_replace(",","",$val['total']),
                'deduction' => str_replace(",","",$val['deduction'])
            );

            $this->voucher->insert($insertdata);

        }

        $log2['voucher-after'] = $this->voucher->fetchAll("trano = '$trano' ")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

        $arrayLog = array (
              "trano" => $trano,
              "uid" => $this->session->userName,
              "tgl" => date('Y-m-d H:i:s'),
              "prj_kode" => $voucherdata[0]['prj_kode'],
              "sit_kode" => $voucherdata[0]['sit_kode'],
              "action" => "UPDATE",
              "data_before" => $jsonLog,
              "data_after" => $jsonLog2,
              "ip" => $_SERVER["REMOTE_ADDR"],
              "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log_trans->insert($arrayLog);

        if (count($jsonFile) > 0)
        {
            foreach ($jsonFile as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => $voucherdata[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->file->insert($arrayInsert);
            }
        }

        if (count($jsonDeletedFile) > 0)
        {
            foreach ($jsonDeletedFile as $key => $val)
            {
                $this->file->delete("id = {$val['id']}");
            }
        }

        $json = "{success: true, number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getvalidasiAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $type = $this->getRequest()->getParam('type');
        $voc_trano = $this->getRequest()->getParam('voc_trano');

        if ($type == 'ARF')
        {
            $data = $this->arfh->fetchAll("trano = '$trano'")->toArray();

            foreach($data as $k => $v)
            {
                $tranoarf = $v['trano'];
                $sql = "SELECT COALESCE(SUM(COALESCE(total_bayar,0)),0) as total FROM finance_payment_voucher WHERE ref_number = '$tranoarf' AND trano != '$voc_trano' AND deleted=0 GROUP BY ref_number";
                $fetch = $this->db->query($sql);
                $fetch = $fetch->fetch();
                
                if ($fetch){
                    $data[$k]['totalBalance'] = floatval($v['total']) - floatval($fetch['total']);
                    $data[$k]['totalpayment'] = floatval($fetch['total']);
                }
                else{
                    $data[$k]['totalBalance'] = floatval($v['total']);
                    $data[$k]['totalpayment'] = floatval($fetch['total']);
                }
            }
            

        }else if ($type == 'RPI')
        {
            $data = $this->rpih->fetchAll("trano = '$trano'")->toArray();

            foreach($data as $k => $v)
            {
                $tranorpi = $v['trano'];
                $sql = "SELECT COALESCE(SUM(COALESCE(total_bayar,0)),0) as total FROM finance_payment_voucher WHERE ref_number = '$tranorpi' AND trano != '$voc_trano' AND deleted=0 GROUP BY ref_number";
                $fetch = $this->db->query($sql);
                $fetch = $fetch->fetch();
                if ($fetch){
                    $data[$k]['totalBalance'] = floatval($v['total']) - floatval($fetch['total']);
                    $data[$k]['totalpayment'] = floatval($fetch['total']);
                }
                else{
                    $data[$k]['totalBalance'] = floatval($v['total']);
                    $data[$k]['totalpayment'] = floatval($fetch['total']);
                }
            }
        }

//        var_dump($data);die;

        $return = array("success" => true,"data" => $data);

//        var_dump($total);die;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }


    public function getfilesAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $data = $this->file->fetchAll("trano = '$trano'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function insertpaymentvoucherAction ()
    {
        $user = $this->session->userName;
        $tgl = date('d-m-Y');

        $this->view->user = $user;
        $this->view->date = $tgl;
    }

    public function getstorerefnumberAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $pulsa = $this->getRequest()->getParam('trans');
        $data = '';

        if ($textsearch == "" || $textsearch == null)
        {
            $search = null;
        }
        else
        {
            if ($pulsa != null)
                $option = "a." . $option;
            $search[] = "$option LIKE '%$textsearch%'";
        }
        if (count($search) > 0)
            $search = implode(" AND ",$search);

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 40;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        if ($type == 'ARF' && $pulsa == null)
        {
            $search = "trano LIKE 'ARF%' " . (($search != '') ? "AND " . $search : '');
            $hasil = $this->arfh->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
            $data['data'] = array();
            $total =0;
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
                
                $total++;
            }
            $data['total'] = $total;
//            $data['total'] = $this->arfh->fetchAll()->count();
        }else if ($type == 'ARF' && $pulsa == 'pulsa')
        {
            $data = $this->pulsa->fetchAll()->toArray();

//        var_dump($data);die;

            $kode = array();
    
            foreach($data as $key => $val)
            {
                $kode[] = $val['kode'];

            }

            $hasil = implode(',',$kode);

            //'2001398','2000218','2000782','2000269','2000143','2001371','2000270','2000231','2000218'
            if ($search)
                $search = " AND " . $search;
            $query = "SELECT SQL_CALC_FOUND_ROWS a.trano as trano,a.prj_kode as prj_kode,a.sit_kode as sit_kode,request2,b.ket,a.total,a.val_kode,a.tgl as tgl
                    FROM procurement_arfh a LEFT JOIN procurement_arfd b on a.trano = b.trano
                    WHERE a.trano LIKE 'ARF%' AND b.kode_brg IN ($hasil) $search group by a.trano ORDER BY $sort $dir LIMIT $offset,$limit";
            $fetch = $this->db->query($query);
            $hasil = $fetch->fetchAll();
            $data['data'] = array();
            foreach($hasil as $k => $v)
            {
                $data['data'][] = array(
                    "trano" => $v['trano'],
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "request" => $v['request2'],
                    "description" => $v['ket'],
                    "total" => $v['total'],
                    "valuta" => $v['val_kode'],
                    "ppn" => $v['ppn']
                );
            }
            $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');


        }else if ($type == 'RPI')
        {
            $hasil = $this->rpih->fetchAll($search." AND deleted=0",array($sort . " " . $dir),$limit,$offset)->toArray();
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
            $data['total'] = count($hasil);//$this->rpih->fetchAll()->count();
        }
        else if ($type == 'REM')
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
        else if ($type == 'BRFP')
        {
            $search = "trano LIKE 'BRF%' " . (($search != '') ? "AND " . $search : '');
            $hasil = $this->arfh->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
            $data['data'] = array();
            foreach($hasil as $k => $v)
            {
                $data['data'][] = array(
                    "trano" => $v['trano'],
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "requester" => $v['request'],
                    "description" => $v['ketin'],
                    "total" => $v['total'],
                    "valuta" => $v['val_kode']
                );
            }
            $data['total'] = $this->arfh->fetchAll($search)->count();
        }
        else if ($type == 'BRF')
        {
            $search = "trano LIKE 'BRF%' AND travel_arrangement='long' AND totalsequence=1 or trano LIKE 'BRF%' AND travel_arrangement!='long'" . (($search != '') ? "AND " . $search : '');
            $hasil = $this->brfh->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
            $data['data'] = array();
            foreach($hasil as $k => $v)
            {
                $data['data'][] = array(
                    "trano" => $v['trano'],
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "request" => $v['requester'],
                    "description" => $v['ketin'],
                    "total" => $v['total'],
                    "valuta" => $v['val_kode']
                );
            }
            $data['total'] = $this->brfh->fetchAll($search)->count();
        }
        else if ($type == 'PPNREM')
        {
            $ppn = new Finance_Models_PpnReimbursementH();
            $hasil = $ppn->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
//            $hasil = $this->remh->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
            $data['data'] = array();
            foreach($hasil as $k => $v)
            {
                $data['data'][] = array(
                    "trano" => $v['trano'],
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "request" => $v['uid'],
                    "description" => $v['ket'],
                    "total" => $v['total'],
                    "valuta" => $v['val_kode']
                );
            }
            $data['total'] = $ppn->fetchAll()->count();
        }


        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getstorevoucherAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $voc_number = $this->getRequest()->getParam('voc_number');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 0;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        if ($voc_number != null || $voc_number != '')
        {
            $search = "ref_number = '$trano' AND trano != '$voc_number' AND status_bpv_ppn = 0";
        }else
        {
            $search = "ref_number = '$trano'";
        }

        $data['data'] = $this->voucher->fetchAll($search,array($sort . " " . $dir))->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertpaymentvoucherAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $voucherdata = Zend_Json::decode($this->getRequest()->getParam('voucherdata'));
        $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));
        $voc_type = $this->getRequest()->getParam('voc_type');
        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans('BPV');
        $tgl = date('Y-m-d H:i:s');
        $uid = $this->session->userName;

        //total_bayar == total yg diinput (sebelum PPN, holding tax, deduction)
        //total == total nilai BPV

        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
            "valuta" => "USD"
        ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];

        foreach ($voucherdata as $key => $val)
        {
            $adaPPN = false;
            $statuspulsa = $val['statuspulsa'];
            $statusppn = $val['statusppn'];
            $statustax = $val['holding_tax_status'];
            $ref_number = $val['ref_number'];
            $item_type = $val['item_type'];
            $coa_kode = $val['coa_kode'];
            $grossup = $val['grossup_status'];

            if ($statuspulsa == 'pulsa')
            {
                $statuspulsa = 1;
            }else
            {
                $statuspulsa = 0;
            }

            if ($statusppn == 'Y')
            {
                $coa_ppn = '1-4400';
                $adaPPN = true;
            }else{
                $coa_ppn = '';
            }

            if ($statustax == 'Y')
            {
                $coa_tax = '2-2100';
            }else{
                $coa_tax = '';
            }

            if ($grossup == true)
            {
                $grossup = 'Y';
            }else{
                $grossup = 'N';
            }


            $total_bayar = str_replace(",","",$val['total_bayar']);
            $ht_value = str_replace(",","",$val['holding_tax_val']);

            if ($grossup == 'Y')
            {
                $total_bayar = floatval($total_bayar) + floatval($ht_value);
            }

            $tranoPPN = '';
            $statusppnInsert = $val['statusppn'];
            $totalInsert = floatval(str_replace(",","",$val['total']));
            $valueppnInsert = str_replace(",","",$val['valueppn']);
            $coappnInsert = $coa_ppn;

            if ($item_type == 'ARF')
            {
                $arfdata = $this->arfd->fetchAll("trano = '$ref_number'")->toArray();

                if ($arfdata)
                {
                    foreach($arfdata as $keyarf => $valarf)
                    {
                        $insertarf = array(
                            'trano' => $trano,
                            'tgl' => $tgl,
                            'uid' => $uid,
                            'ref_number' => $valarf['trano'],
                            'prj_kode' => $valarf['prj_kode'],
                            'prj_nama' => $valarf['prj_nama'],
                            'sit_kode' => $valarf['sit_kode'],
                            'sit_nama' => $valarf['sit_nama'],
                            'workid' => $valarf['workid'],
                            'workname' => $valarf['workname'],
                            'kode_brg' => $valarf['kode_brg'],
                            'nama_brg' => $valarf['nama_brg'],
                            'qty' => $valarf['qty'],
                            'harga' => $valarf['harga'],
                            'total' => $valarf['total'],
                            'val_kode' => $valarf['val_kode'],
                            'rateidr' => $valarf['rateidr'],
                            'cfs_kode' => $valarf['cfs_kode'],
                            'coa_kode' => $coa_kode
                        );

                        $this->voucherd->insert($insertarf);
                    }
                }

            }else if ($item_type == 'RPI')
            {
                $rpidata = $this->rpid->fetchAll("trano = '$ref_number'")->toArray();

                if ($rpidata)
                {
                    foreach($rpidata as $keyrpi => $valrpi)
                    {
                        $insertrpi = array(
                            'trano' => $trano,
                            'tgl' => $tgl,
                            'uid' => $uid,
                            'ref_number' => $valrpi['trano'],
                            'prj_kode' => $valrpi['prj_kode'],
                            'prj_nama' => $valrpi['prj_nama'],
                            'sit_kode' => $valrpi['sit_kode'],
                            'sit_nama' => $valrpi['sit_nama'],
                            'workid' => $valrpi['workid'],
                            'workname' => $valrpi['workname'],
                            'kode_brg' => $valrpi['kode_brg'],
                            'nama_brg' => $valrpi['nama_brg'],
                            'qty' => $valrpi['qty'],
                            'harga' => $valrpi['harga'],
                            'total' => $valrpi['total'],
                            'val_kode' => $valrpi['val_kode'],
                            'rateidr' => $valrpi['rateidr'],
                            'cfs_kode' => $valrpi['cfs_kode'],
                            'coa_kode' => $coa_kode
                        );

                        $this->voucherd->insert($insertrpi);
                    }
                }

                 //input jurnal ap

                if ($adaPPN)
                {
                    $tranoPPN = $counter->setNewTrans('BPV');
                    $statusppnInsert = 'N';
                    $totalInsert = floatval(str_replace(",","",$val['total'])) - floatval(str_replace(",","",$val['valueppn']));
                    $valueppnInsert = 0;
                    $coappnInsert = '';
                    $total_bayar = $total_bayar; // - floatval(str_replace(",","",$val['valueppn']));
                }

                $coa = $this->coa->fetchRow("coa_kode = '$coa_kode'");

                $insertpayment = array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $val['coa_kode'],
                    "coa_nama" => $coa['coa_nama'],
                    "debit" => $total_bayar,
                    "credit" => 0,
                    'prj_kode' => $val['prj_kode'],
                    'val_kode' => $val['valuta'],
                    'sit_kode' => $val['sit_kode'],
                    'rateidr' => $currentRateidr
                );

                $this->jurnalap->insert($insertpayment);

                if ($val['valuta'] != 'IDR')
                {
                    $insertpayment = array(
                        "trano" => $trano,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $val['coa_kode'],
                        "coa_nama" => $coa['coa_nama'],
                        "debit" => (($total_bayar * $currentRateidr) - $total_bayar),
                        "credit" => 0,
                        'prj_kode' => $val['prj_kode'],
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode'],
                        'rateidr' => $currentRateidr
                    );
                    $this->jurnalap->insert($insertpayment);
                }

                if ($statustax == 'Y')
                {
                    $coatax = $this->coa->fetchRow("coa_kode = '$coa_tax'");

                    $inserttax = array(

                    "trano" => $tranoPPN,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coa_tax,
                    "coa_nama" => $coatax['coa_nama'],
                    "debit" => 0,
                    "credit" => str_replace(",","",$val['holding_tax_val']),
                    'prj_kode' => $val['prj_kode'],
                    'val_kode' => $val['valuta'],
                    'sit_kode' => $val['sit_kode'],
                    'rateidr' => $currentRateidr

                    );

                    $this->jurnalap->insert($inserttax);
                }


//                $coaap = '2-1100';
                if ($val['valuta'] == 'IDR')
                {
                    $coaap = '2-1110';
                    $coaap2 = '';
                }
                elseif($val['valuta'] == 'USD')
                {
                    $coaap = '2-1121';
                    $coaap2 = '2-1122';
                }
                $coa_ap = $this->coa->fetchRow("coa_kode = '$coaap'");

                if ($adaPPN)
                {
                    $totalInsert = floatval(str_replace(",","",$val['total'])) - floatval(str_replace(",","",$val['valueppn']));
                }
                else
                {
                    $totalInsert = floatval(str_replace(",","",$val['total']));
                }
                $insertap = array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coaap,
                    "coa_nama" => $coa_ap['coa_nama'],
                    "debit" => 0,
                    "credit" => $totalInsert,
                    'prj_kode' => $val['prj_kode'],
                    'val_kode' => $val['valuta'],
                    'sit_kode' => $val['sit_kode'],
                    'rateidr' => $currentRateidr
                );
                $this->jurnalap->insert($insertap);

                //kalau valuta selain IDR,
                if ($coaap2 != '')
                {
                    $coa_ap2 = $this->coa->fetchRow("coa_kode = '$coaap2'");
                    $insertap = array(
                        "trano" => $trano,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coaap2,
                        "coa_nama" => $coa_ap2['coa_nama'],
                        "debit" => 0,
                        "credit" => (($totalInsert * floatval($currentRateidr)) - $totalInsert),
                        'prj_kode' => $val['prj_kode'],
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode'],
                        'rateidr' => $currentRateidr
                    );

                    $this->jurnalap->insert($insertap);

                    if ($statustax == 'Y')
                    {
                        $coatax = $this->coa->fetchRow("coa_kode = '$coa_tax'");
                        $holdingTaxVal = str_replace(",","",$val['holding_tax_val']);
                        $inserttax = array(

                        "trano" => $trano,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coa_tax,
                        "coa_nama" => $coatax['coa_nama'],
                        "debit" => 0,
                        "credit" => (($holdingTaxVal * floatval($currentRateidr)) - $holdingTaxVal),
                        'prj_kode' => $val['prj_kode'],
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode'],
                        'rateidr' => $currentRateidr

                        );

                        $this->jurnalap->insert($inserttax);
                    }

                    $coa_ap2 = '';
                }
            }
            elseif ($item_type == 'PPNREM')
            {
                $ppn = new Finance_Models_PpnReimbursementH();
                $ppndata = $ppn->fetchRow("trano = '$ref_number'")->toArray();

                if ($ppndata)
                {
                    foreach($ppndata as $keyrem => $valrem)
                    {
                        $insertrem = array(
                            'trano' => $trano,
                            'tgl' => $tgl,
                            'uid' => $uid,
                            'ref_number' => $valrem['trano'],
                            'prj_kode' => $valrem['prj_kode'],
                            'prj_nama' => $valrem['prj_nama'],
                            'sit_kode' => $valrem['sit_kode'],
                            'sit_nama' => $valrem['sit_nama'],
//                            'workid' => $valrem['workid'],
//                            'workname' => $valrem['workname'],
//                            'kode_brg' => $valrem['kode_brg'],
//                            'nama_brg' => $valrem['nama_brg'],
                            'qty' => 1,
                            'harga' => $valrem['total'],
                            'total' => $valrem['total'],
                            'val_kode' => $valrem['val_kode'],
                            'rateidr' => $valrem['rateidr'],
                            'cfs_kode' => $valrem['cfs_kode'],
                            'coa_kode' => $coa_kode
                        );

                        $this->voucherd->insert($insertrem);
                    }
                }
            }
            else
            {
                $remdata = $this->remd->fetchAll("trano = '$ref_number'")->toArray();

                if ($remdata)
                {
                    foreach($remdata as $keyrem => $valrem)
                    {
                        $insertrem = array(
                            'trano' => $trano,
                            'tgl' => $tgl,
                            'uid' => $uid,
                            'ref_number' => $valrem['trano'],
                            'prj_kode' => $valrem['prj_kode'],
                            'prj_nama' => $valrem['prj_nama'],
                            'sit_kode' => $valrem['sit_kode'],
                            'sit_nama' => $valrem['sit_nama'],
                            'workid' => $valrem['workid'],
                            'workname' => $valrem['workname'],
                            'kode_brg' => $valrem['kode_brg'],
                            'nama_brg' => $valrem['nama_brg'],
                            'qty' => $valrem['qty'],
                            'harga' => $valrem['harga'],
                            'total' => $valrem['jumlah'],
                            'val_kode' => $valrem['val_kode'],
                            'rateidr' => $valrem['rateidr'],
                            'cfs_kode' => $valrem['cfs_kode'],
                            'coa_kode' => $coa_kode
                        );

                        $this->voucherd->insert($insertrem);
                    }
                }
            }

            $hol = str_replace(",","",$val['holding_tax']);
            $hol = ($hol == "") ? 0 : $hol;

            $hol2 = str_replace(",","",$val['holding_tax_val']);
            $hol2 = ($hol2 == "") ? 0 : $hol2;

            $insertdata = array(
                'trano' => $trano,
                'trano_ppn' => $tranoPPN,
                'tgl' => $tgl,
                'ref_number' => $val['ref_number'],
                'item_type' => $val['item_type'],
                'total_bayar' => str_replace(",","",$val['total_bayar']),
                'statusppn' => $statusppnInsert,
                'valueppn' => $valueppnInsert,
                'holding_tax_status' => $val['holding_tax_status'],
                'holding_tax' => $hol,
                'holding_tax_val' => $hol2,
                'holding_tax_text' => $val['holding_tax_text'],
                'valuta' => $val['valuta'],
                'prj_kode' => $val['prj_kode'],
                'sit_kode' => $val['sit_kode'],
                'coa_kode' => $val['coa_kode'],
                'ketin' => $val['ketin'],
                'uid' => $uid,
                'requester' => $val['requester'],
                'total' => $totalInsert,
                'deduction' => str_replace(",","",$val['deduction']),
                'statuspulsa' => $statuspulsa,
                'total_value' => str_replace(",","",$val['total_value']),
                'coa_ppn' => $coappnInsert,
                'coa_holding_tax' => $coa_tax,
                'grossup_status' => $grossup,
                'type' => $voc_type
            );

            $this->voucher->insert($insertdata);

             //insert jurnal AP

            if ($adaPPN)
            {
                $coa_ppn = '1-4400';
                $coappn = $this->coa->fetchRow("coa_kode = '$coa_ppn'");

                $insertppn = array(
                    "trano" => $tranoPPN,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coa_ppn,
                    "coa_nama" => $coappn['coa_nama'],
                    "debit" => str_replace(",","",$val['valueppn']),
                    "credit" => 0,
                    'prj_kode' => $val['prj_kode'],
                    'rateidr' => $currentRateidr,
                    'val_kode' => $val['valuta'],
                    'sit_kode' => $val['sit_kode']
                );

                $this->jurnalap->insert($insertppn);
                if($val['valuta'] == 'USD')
                {
                    $valPPN = str_replace(",","",$val['valueppn']);
                    $insertppn = array(
                        "trano" => $tranoPPN,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coa_ppn,
                        "coa_nama" => $coappn['coa_nama'],
                        "debit" => (($valPPN * $currentRateidr) - $valPPN),
                        "credit" => 0,
                        'prj_kode' => $val['prj_kode'],
                        'rateidr' => $currentRateidr,
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode']
                    );

                    $this->jurnalap->insert($insertppn);
                }

//                $coaap = '2-1100';
                if ($val['valuta'] == 'IDR')
                {
                    $coaap = '2-1110'; //Coa AP PPN IDR
                }
                elseif($val['valuta'] == 'USD')
                {
                    $coaap = '2-1121'; //Coa AP PPN USD
                }
                $coa_ap = $this->coa->fetchRow("coa_kode = '$coaap'");

                $insertap = array(
                    "trano" => $tranoPPN,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coaap,
                    "coa_nama" => $coa_ap['coa_nama'],
                    "debit" => 0,
                    "credit" => str_replace(",","",$val['valueppn']),
                    'prj_kode' => $val['prj_kode'],
                    'val_kode' => $val['valuta'],
                    'sit_kode' => $val['sit_kode'],
                    'rateidr' => $currentRateidr
                );

                $this->jurnalap->insert($insertap);

                //Valuta selain IDR
                if ($coaap2 != '')
                {
                    $coa_ap2 = $this->coa->fetchRow("coa_kode = '$coaap2'");
                    $insertap = array(
                        "trano" => $tranoPPN,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coaap2,
                        "coa_nama" => $coa_ap2['coa_nama'],
                        "debit" => 0,
                        "credit" => ((floatval(str_replace(",","",$val['valueppn'])) * floatval($currentRateidr)) - floatval(str_replace(",","",$val['valueppn']))),
                        'prj_kode' => $val['prj_kode'],
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode'],
                        'rateidr' => $currentRateidr
                    );

                    $this->jurnalap->insert($insertap);

                    $coa_ap2 = '';
                }

                $insertdata = array(
                    'trano' => $tranoPPN,
                    'trano_ppn' => '',
                    'tgl' => $tgl,
                    'ref_number' => $val['ref_number'],
                    'item_type' => $val['item_type'],
                    'total_bayar' => str_replace(",","",$val['valueppn']),
                    'statusppn' => 'N',
                    'valueppn' => 0,
                    'valuta' => $val['valuta'],
                    'prj_kode' => $val['prj_kode'],
                    'sit_kode' => $val['sit_kode'],
                    'coa_kode' => $val['coa_kode'],
                    'ketin' => $val['ketin'],
                    'uid' => $uid,
                    'requester' => $val['requester'],
                    'total' => str_replace(",","",$val['valueppn']),
                    'deduction' => 0,
                    'statuspulsa' => $statuspulsa,
                    'total_value' => str_replace(",","",$val['valueppn']),
                    'coa_ppn' => $coa_ppn,
                    'ppn_ref_number' => $val['ppn_ref_number'],
                    'status_bpv_ppn' => 1,
                    'type' => $voc_type
                );

                $this->voucher->insert($insertdata);
            }

        }

        if (count($filedata) > 0)
        {
            foreach ($filedata as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => $voucherdata[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->file->insert($arrayInsert);
            }
        }

        $json = "{success: true, number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getcheckdocumentAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $ref_number = $this->getRequest()->getParam('ref_number');

        $workflow = $this->workflow_trans->fetchAll("item_id = '$ref_number'")->toArray();

        if ($type == 'ARF')
        {
            $cekarf = $this->arfh->fetchRow("trano = '$ref_number' ")->toArray();

            $statrevisi = $cekarf['statrevisi'];
        }

        $workflow_id = $workflow[0]['workflow_trans_id'];

//        var_dump($workflow_id);die;
        $json = "{success: true, number : '$workflow_id',statrevisi:'$statrevisi'}";

        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editpaymentvoucherAction ()
    {
        $user = $this->session->userName;
        $tgl = date('d-m-Y');

        $this->view->user = $user;
        $this->view->date = $tgl;

        $rate = QDC_Common_ExchangeRate::factory(array(
            "valuta" => "USD"
        ))->getExchangeRate();

        $this->view->rateidr = $rate['rateidr'];
    }

    public function doupdatepaymentvoucherAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        //total_bayar == total yg diinput (sebelum PPN, holding tax, deduction)
        //total == total nilai BPV

        $voucherdata = Zend_Json::decode($this->getRequest()->getParam('voucherdata'));
        $jsonFile = Zend_Json::decode($this->getRequest()->getParam('filedata'));
        $jsonDeletedFile = Zend_Json::decode($this->getRequest()->getParam('deletedfile'));
        $voc_type = $this->getRequest()->getParam('voc_type');

        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
            "valuta" => "USD"
        ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];

//        $tgl = date('Y-m-d H:i:s');
        $tgl = $this->_getParam("tgl");
        $rateidr = $this->_getParam("rateidr");
        if ($rateidr != '')
            $currentRateidr = $rateidr;

        $uid = $this->session->userName;

        $trano = $voucherdata[0]['trano'];
        $counter = new Default_Models_MasterCounter();

        $log['voucher-before'] = $this->voucher->fetchAll("trano = '$trano'")->toArray();
        $log['voucher-detail-before'] = $this->voucherd->fetchAll("trano = '$trano'")->toArray();

        $cek = $this->voucher->fetchAll("trano = '$trano'");

        $oldtgl = $cek[0]['tgl'];
        if ($tgl == '')
            $tgl = $oldtgl;
        else
            $tgl = date("Y-m-d H:i:s",strtotime($tgl));

        $adaPPNTrano = false;
        if($cek)
        {
            foreach($cek as $k => $v)
            {
                if ($v['trano_ppn'] != '')
                {
//                    $adaPPNTrano = true;
                    $ppnTrano = $v['trano_ppn'];
                    $this->voucher->delete("trano = '$ppnTrano'");
                    $this->jurnalap->delete("trano = '$ppnTrano'");
                }
            }
        }

        $this->voucher->delete("trano = '$trano'");
        $this->voucherd->delete("trano = '$trano'");

        $this->jurnalap->delete("trano = '$trano'");
        foreach ($voucherdata as $key => $val)
        {
            $adaPPN = false;
            $statuspulsa = $val['statuspulsa'];
            $statusppn = $val['statusppn'];
            $statustax = $val['holding_tax_status'];
            $ref_number = $val['ref_number'];
            $item_type = $val['item_type'];
            $coa_kode = $val['coa_kode'];
            $grossup = $val['grossup_status'];

            $total_bayar = str_replace(",","",$val['total_bayar']);
            $ht_value = str_replace(",","",$val['holding_tax_val']);

            if ($statuspulsa == 'pulsa')
            {
                $statuspulsa = 1;
            }

            if ($statusppn == 'Y')
            {
                $coa_ppn = '1-4400';
                $adaPPN = true;

                if ($val['valueppn'] == '')
                {
                    $val['valueppn'] = 0.1 * $total_bayar;
                }

            }else{
                $coa_ppn = '';
            }

            if ($statustax == 'Y')
            {
                $statustax = 'Y';
                $coa_tax = '2-2100';
            }else{
                $statustax = 'N';
                $coa_tax = '';
            }

            if ($grossup == 'Y')
            {
                $grossup = 'Y';
            }else{
                $grossup = 'N';
            }

//            if ($grossup == 'Y')
//            {
//                $total_bayar = floatval($total_bayar) + floatval($ht_value);
//            }

            $tranoPPN = '';
            $statusppnInsert = $val['statusppn'];
            $totalInsert = floatval(str_replace(",","",$val['total']));
            $valueppnInsert = str_replace(",","",$val['valueppn']);
            $coappnInsert = $coa_ppn;

            if ($item_type == 'ARF')
            {
                $arfdata = $this->arfd->fetchAll("trano = '$ref_number'")->toArray();

                if ($arfdata)
                {
                    foreach($arfdata as $keyarf => $valarf)
                    {
                        $insertarf = array(
                            'trano' => $trano,
                            'tgl' => $tgl,
                            'uid' => $uid,
                            'ref_number' => $valarf['trano'],
                            'prj_kode' => $valarf['prj_kode'],
                            'prj_nama' => $valarf['prj_nama'],
                            'sit_kode' => $valarf['sit_kode'],
                            'sit_nama' => $valarf['sit_nama'],
                            'workid' => $valarf['workid'],
                            'workname' => $valarf['workname'],
                            'kode_brg' => $valarf['kode_brg'],
                            'nama_brg' => $valarf['nama_brg'],
                            'qty' => $valarf['qty'],
                            'harga' => $valarf['harga'],
                            'total' => $valarf['total'],
                            'val_kode' => $valarf['val_kode'],
                            'rateidr' => $valarf['rateidr'],
                            'cfs_kode' => $valarf['cfs_kode'],
                            'coa_kode' => $coa_kode
                        );

                        $this->voucherd->insert($insertarf);
                    }
                }

            }
            else if ($item_type == 'RPI')
            {
                $rpidata = $this->rpid->fetchAll("trano = '$ref_number'")->toArray();

                if ($rpidata)
                {
                    foreach($rpidata as $keyrpi => $valrpi)
                    {
                        $insertrpi = array(
                            'trano' => $trano,
                            'tgl' => $tgl,
                            'uid' => $uid,
                            'ref_number' => $valrpi['trano'],
                            'prj_kode' => $valrpi['prj_kode'],
                            'prj_nama' => $valrpi['prj_nama'],
                            'sit_kode' => $valrpi['sit_kode'],
                            'sit_nama' => $valrpi['sit_nama'],
                            'workid' => $valrpi['workid'],
                            'workname' => $valrpi['workname'],
                            'kode_brg' => $valrpi['kode_brg'],
                            'nama_brg' => $valrpi['nama_brg'],
                            'qty' => $valrpi['qty'],
                            'harga' => $valrpi['harga'],
                            'total' => $valrpi['total'],
                            'val_kode' => $valrpi['val_kode'],
                            'rateidr' => $valrpi['rateidr'],
                            'cfs_kode' => $valrpi['cfs_kode'],
                            'coa_kode' => $coa_kode
                        );

                        $this->voucherd->insert($insertrpi);
                    }
                }

             //insert jurnal AP
                if ($adaPPN)
                {
                    if (!$adaPPNTrano && !$ppnTrano)
                        $tranoPPN = $counter->setNewTrans('BPV');
                    else
                        $tranoPPN = $ppnTrano;

                    $statusppnInsert = 'N';
                    $totalInsert = floatval(str_replace(",","",$val['total'])) - floatval(str_replace(",","",$val['valueppn']));
                    $valueppnInsert = 0;
                    $coappnInsert = '';
                    $total_bayar = $total_bayar;// - floatval(str_replace(",","",$val['valueppn']));
                }


                $coa = $this->coa->fetchRow("coa_kode = '$coa_kode'");

                $insertpayment = array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $val['coa_kode'],
                    "coa_nama" => $coa['coa_nama'],
                    "debit" => $total_bayar,
                    "credit" => 0,
                    'prj_kode' => $val['prj_kode'],
                    'sit_kode' => $val['sit_kode'],
                    'val_kode' => $val['valuta'],
                    'rateidr' => $currentRateidr
                );

                $this->jurnalap->insert($insertpayment);

                if ($val['valuta'] != 'IDR')
                {
                    $insertpayment = array(
                        "trano" => $trano,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $val['coa_kode'],
                        "coa_nama" => $coa['coa_nama'],
                        "debit" => (($total_bayar * $currentRateidr) - $total_bayar),
                        "credit" => 0,
                        'prj_kode' => $val['prj_kode'],
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode'],
                        'rateidr' => $currentRateidr
                    );
                    $this->jurnalap->insert($insertpayment);
                }

                if ($statustax == 'Y')
                {
                    $coatax = $this->coa->fetchRow("coa_kode = '$coa_tax'");

                    $inserttax = array(

                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coa_tax,
                    "coa_nama" => $coatax['coa_nama'],
                    "debit" => 0,
                    "credit" => str_replace(",","",$val['holding_tax_val']),
                    'prj_kode' => $val['prj_kode'],
                    'sit_kode' => $val['sit_kode'],
                    'val_kode' => $val['valuta'],
                    'rateidr' => $currentRateidr

                    );

                    $this->jurnalap->insert($inserttax);

                    if ($grossup == 'Y')
                    {
                        $insertpayment = array(
                            "trano" => $trano,
                            "ref_number" => $val['ref_number'],
                            "tgl" => $tgl,
                            "uid" => $uid,
                            "coa_kode" => $val['coa_kode'],
                            "coa_nama" => $coa['coa_nama'],
                            "debit" => str_replace(",","",$val['holding_tax_val']),
                            "credit" => 0,
                            'prj_kode' => $val['prj_kode'],
                            'sit_kode' => $val['sit_kode'],
                            'val_kode' => $val['valuta'],
                            'rateidr' => $currentRateidr
                        );
                        $this->jurnalap->insert($insertpayment);
                    }
                }

//                $coaap = '2-1100';
                $rpiHdata = $this->rpih->fetchRow("trano = '$ref_number'")->toArray();
                if ($val['valuta'] == 'IDR')
                {
                    $coas = QDC_Finance_Coa::factory()->getCoaAPIDR();
                    $coaap = $coas[0];
                    $coaap2 = '';
                }
                elseif($val['valuta'] == 'USD')
                {
                    $coas = QDC_Finance_Coa::factory()->getCoaAPUSD();
                    $coaap = $coas[0];
                    $coaap2 = $coas[1];
                }
                $coa_ap = $this->coa->fetchRow("coa_kode = '$coaap'");

//                if ($adaPPN)
//                {
//                    $totalInsert = floatval($total_bayar) - floatval(str_replace(",","",$val['valueppn']));
//                }
//                else
//                {
//                    $totalInsert = floatval($total_bayar);
//                }

                if ($statustax == 'Y' && $grossup == 'N')
                {
                    $total_bayarAP = $total_bayar - $ht_value;
                }
                else{
                    $total_bayarAP = $total_bayar;
                }

                $deduction = 0;
                if (floatval(str_replace(",","",$val['deduction'])) > 0)
                {
                    $deduction = floatval(str_replace(",","",$val['deduction']));
                    $total_bayarAP = $total_bayarAP - $deduction;

                    $insertpayment = array(
                        "trano" => $trano,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $val['coa_kode'],
                        "coa_nama" => $coa['coa_nama'],
                        "credit" => $deduction,
                        "debit" => 0,
                        'prj_kode' => $val['prj_kode'],
                        'sit_kode' => $val['sit_kode'],
                        'val_kode' => $val['valuta'],
                        'rateidr' => $currentRateidr
                    );
                    $this->jurnalap->insert($insertpayment);
                }

                $insertap = array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coaap,
                    "coa_nama" => $coa_ap['coa_nama'],
                    "debit" => 0,
                    "credit" => $total_bayarAP,
                    'prj_kode' => $val['prj_kode'],
                    'sit_kode' => $val['sit_kode'],
                    'val_kode' => $val['valuta'],
                    'rateidr' => $currentRateidr
                );

                $this->jurnalap->insert($insertap);

                //kalau valuta selain IDR,
                if ($coaap2 != '')
                {
                    $coa_ap2 = $this->coa->fetchRow("coa_kode = '$coaap2'");
                    $insertap = array(
                        "trano" => $trano,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coaap2,
                        "coa_nama" => $coa_ap2['coa_nama'],
                        "debit" => 0,
                        "credit" => (($total_bayarAP * floatval($currentRateidr)) - $total_bayarAP),
                        'prj_kode' => $val['prj_kode'],
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode'],
                        'rateidr' => $currentRateidr
                    );

                    $this->jurnalap->insert($insertap);

                    if ($statustax == 'Y')
                    {
                        $coatax = $this->coa->fetchRow("coa_kode = '$coa_tax'");
                        $holdingTaxVal = str_replace(",","",$val['holding_tax_val']);
                        $inserttax = array(

                            "trano" => $trano,
                            "ref_number" => $val['ref_number'],
                            "tgl" => $tgl,
                            "uid" => $uid,
                            "coa_kode" => $coa_tax,
                            "coa_nama" => $coatax['coa_nama'],
                            "debit" => 0,
                            "credit" => (($holdingTaxVal * floatval($currentRateidr)) - $holdingTaxVal),
                            'prj_kode' => $val['prj_kode'],
                            'val_kode' => $val['valuta'],
                            'sit_kode' => $val['sit_kode'],
                            'rateidr' => $currentRateidr

                        );

                        $this->jurnalap->insert($inserttax);

                        if ($grossup == 'Y')
                        {
                            $insertpayment = array(
                                "trano" => $trano,
                                "ref_number" => $val['ref_number'],
                                "tgl" => $tgl,
                                "uid" => $uid,
                                "coa_kode" => $val['coa_kode'],
                                "coa_nama" => $coa['coa_nama'],
                                "debit" => (($holdingTaxVal * floatval($currentRateidr)) - $holdingTaxVal),
                                "credit" => 0,
                                'prj_kode' => $val['prj_kode'],
                                'sit_kode' => $val['sit_kode'],
                                'val_kode' => $val['valuta'],
                                'rateidr' => $currentRateidr
                            );
                            $this->jurnalap->insert($insertpayment);
                        }
                    }

                    if ($deduction > 0)
                    {
                        $insertpayment = array(
                            "trano" => $trano,
                            "ref_number" => $val['ref_number'],
                            "tgl" => $tgl,
                            "uid" => $uid,
                            "coa_kode" => $val['coa_kode'],
                            "coa_nama" => $coa['coa_nama'],
                            "credit" => (($deduction * floatval($currentRateidr)) - $deduction),
                            "debit" => 0,
                            'prj_kode' => $val['prj_kode'],
                            'sit_kode' => $val['sit_kode'],
                            'val_kode' => $val['valuta'],
                            'rateidr' => $currentRateidr
                        );
                        $this->jurnalap->insert($insertpayment);
                    }

                    $coa_ap2 = '';
                }
            }

            elseif ($item_type == 'PPNREM')
            {
                $ppn = new Finance_Models_PpnReimbursementH();
                $ppndata = $ppn->fetchRow("trano = '$ref_number'")->toArray();

                if ($ppndata)
                {
                    foreach($ppndata as $keyrem => $valrem)
                    {
                        $insertrem = array(
                            'trano' => $trano,
                            'tgl' => $tgl,
                            'uid' => $uid,
                            'ref_number' => $valrem['trano'],
                            'prj_kode' => $valrem['prj_kode'],
                            'prj_nama' => $valrem['prj_nama'],
                            'sit_kode' => $valrem['sit_kode'],
                            'sit_nama' => $valrem['sit_nama'],
//                            'workid' => $valrem['workid'],
//                            'workname' => $valrem['workname'],
//                            'kode_brg' => $valrem['kode_brg'],
//                            'nama_brg' => $valrem['nama_brg'],
                            'qty' => 1,
                            'harga' => $valrem['total'],
                            'total' => $valrem['total'],
                            'val_kode' => $valrem['val_kode'],
                            'rateidr' => $valrem['rateidr'],
                            'cfs_kode' => $valrem['cfs_kode'],
                            'coa_kode' => $coa_kode
                        );

                        $this->voucherd->insert($insertrem);
                    }
                }
            }
            else
            {
                $remdata = $this->remd->fetchAll("trano = '$ref_number'")->toArray();

                if ($remdata)
                {
                    foreach($remdata as $keyrem => $valrem)
                    {
                        $insertrem = array(
                            'trano' => $trano,
                            'tgl' => $tgl,
                            'uid' => $uid,
                            'ref_number' => $valrem['trano'],
                            'prj_kode' => $valrem['prj_kode'],
                            'prj_nama' => $valrem['prj_nama'],
                            'sit_kode' => $valrem['sit_kode'],
                            'sit_nama' => $valrem['sit_nama'],
                            'workid' => $valrem['workid'],
                            'workname' => $valrem['workname'],
                            'kode_brg' => $valrem['kode_brg'],
                            'nama_brg' => $valrem['nama_brg'],
                            'qty' => $valrem['qty'],
                            'harga' => $valrem['harga'],
                            'total' => $valrem['jumlah'],
                            'val_kode' => $valrem['val_kode'],
                            'rateidr' => $valrem['rateidr'],
                            'cfs_kode' => $valrem['cfs_kode'],
                            'coa_kode' => $coa_kode
                        );

                        $this->voucherd->insert($insertrem);
                    }
                }
            }

            $insertdata = array(
                'trano' => $trano,
                'trano_ppn' => $tranoPPN,
                'tgl' => $tgl,
                'ref_number' => $val['ref_number'],
                'item_type' => $val['item_type'],
                'total_bayar' => str_replace(",","",$val['total_bayar']),
                'statusppn' => $statusppnInsert,
                'valueppn' => $valueppnInsert,
                'holding_tax_status' => $val['holding_tax_status'],
                'holding_tax' => str_replace(",","",$val['holding_tax']),
                'holding_tax_val' => str_replace(",","",$val['holding_tax_val']),
                'holding_tax_text' => $val['holding_tax_text'],
                'valuta' => $val['valuta'],
                'prj_kode' => $val['prj_kode'],
                'sit_kode' => $val['sit_kode'],
                'coa_kode' => $val['coa_kode'],
                'ketin' => $val['ketin'],
                'uid' => $uid,
                'requester' => $val['requester'],
                'total' => $totalInsert,
                'deduction' => str_replace(",","",$val['deduction']),
                'statuspulsa' => $statuspulsa,
                'total_value' => str_replace(",","",$val['total_value']),
                'coa_ppn' => $coappnInsert,
                'coa_holding_tax' => $coa_tax,
                'grossup_status' => $grossup,
                'type' => $voc_type
            );

            $this->voucher->insert($insertdata);

            if ($adaPPN)
            {
                $totalPPN = floatval(str_replace(",","",$val['valueppn']));
                $coa_ppn = '1-4400';
                $coappn = $this->coa->fetchRow("coa_kode = '$coa_ppn'");
//            if ($statusppn == 'Y')
//                            {

                $insertppn = array(

//                "trano" => $tranoPPN,
                "trano" => $trano,
                "ref_number" => $val['ref_number'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $coa_ppn,
                "coa_nama" => $coappn['coa_nama'],
                "debit" => $totalPPN,
                "credit" => 0,
                'prj_kode' => $val['prj_kode'],
                'sit_kode' => $val['sit_kode'],
                'val_kode' => $val['valuta'],
                'rateidr' => $currentRateidr

                );

                $this->jurnalap->insert($insertppn);

                if($val['valuta'] == 'USD')
                {
                    $insertppn = array(

//                "trano" => $tranoPPN,
                        "trano" => $trano,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coa_ppn,
                        "coa_nama" => $coappn['coa_nama'],
                        "debit" => ($totalPPN * $currentRateidr) - $totalPPN,
                        "credit" => 0,
                        'prj_kode' => $val['prj_kode'],
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode'],
                        'rateidr' => $currentRateidr

                    );

                    $this->jurnalap->insert($insertppn);
                }

                if ($val['valuta'] == 'IDR')
                {
                    $coas = QDC_Finance_Coa::factory()->getCoaAPIDR();
                    $coaap = $coas[0];
                    $coaap2 = '';

                    $coa_ap = $this->coa->fetchRow("coa_kode = '$coaap'");

                    $insertap = array(
//                "trano" => $tranoPPN,
                        "trano" => $trano,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coaap,
                        "coa_nama" => $coa_ap['coa_nama'],
                        "debit" => 0,
                        "credit" => $totalPPN,
                        'prj_kode' => $val['prj_kode'],
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode'],
                        'rateidr' => $currentRateidr
                    );

                    $this->jurnalap->insert($insertap);
                }
                elseif($val['valuta'] == 'USD')
                {
                    $coas = QDC_Finance_Coa::factory()->getCoaAPUSD();
                    $coaap = $coas[0];
                    $coaap2 = $coas[1];

                    $coa_ap = $this->coa->fetchRow("coa_kode = '$coaap'");

                    $insertap = array(
//                "trano" => $tranoPPN,
                        "trano" => $trano,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coaap,
                        "coa_nama" => $coa_ap['coa_nama'],
                        "debit" => 0,
                        "credit" => $totalPPN,
                        'prj_kode' => $val['prj_kode'],
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode'],
                        'rateidr' => $currentRateidr
                    );

                    $this->jurnalap->insert($insertap);



                    $coa_ap = $this->coa->fetchRow("coa_kode = '$coaap2'");

                    $insertap = array(
//                "trano" => $tranoPPN,
                        "trano" => $trano,
                        "ref_number" => $val['ref_number'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coaap2,
                        "coa_nama" => $coa_ap['coa_nama'],
                        "debit" => 0,
                        "credit" => ($totalPPN * $currentRateidr) - $totalPPN,
                        'prj_kode' => $val['prj_kode'],
                        'val_kode' => $val['valuta'],
                        'sit_kode' => $val['sit_kode'],
                        'rateidr' => $currentRateidr
                    );

                    $this->jurnalap->insert($insertap);

                    $coaap2 = '';
                }


                $insertdata = array(
                    'trano' => $tranoPPN,
                    'trano_ppn' => '',
                    'tgl' => $tgl,
                    'ref_number' => $val['ref_number'],
                    'item_type' => $val['item_type'],
                    'total_bayar' => str_replace(",","",$val['valueppn']),
                    'statusppn' => 'N',
                    'valueppn' => 0,
                    'valuta' => $val['valuta'],
                    'prj_kode' => $val['prj_kode'],
                    'sit_kode' => $val['sit_kode'],
                    'coa_kode' => $val['coa_kode'],
                    'ketin' => $val['ketin'],
                    'uid' => $uid,
                    'requester' => $val['requester'],
                    'total' => str_replace(",","",$val['valueppn']),
                    'deduction' => 0,
                    'statuspulsa' => $statuspulsa,
                    'total_value' => str_replace(",","",$val['valueppn']),
                    'coa_ppn' => $coa_ppn,
                    'ppn_ref_number' => $val['ppn_ref_number'],
                    'status_bpv_ppn' => 1,
                    'type' => $voc_type
                );

                $this->voucher->insert($insertdata);
            }
            if ($item_type == 'PPNREM' || $item_type == 'ARF' || $item_type == 'REM')
            {
                $j = new Finance_Models_AccountingJurnalBank();

                $select = $this->db->select()
                    ->from(array($j->__name()))
                    ->where("trano = ?",$trano)
                    ->where("ref_number = ?",$ref_number);

                $cek = $this->db->fetchAll($select);
                if ($cek)
                {
                    $j->update(array(
                        "tgl" => $tgl
                    ),"trano = '$trano' AND ref_number = '$ref_number'");
                }
            }

        }



        $log2['voucher-after'] = $this->voucher->fetchAll("trano = '$trano' ")->toArray();
        $log2['voucher-detail-after'] = $this->voucherd->fetchAll("trano = '$trano' ")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

        $arrayLog = array (
              "trano" => $trano,
              "uid" => $this->session->userName,
              "tgl" => date('Y-m-d H:i:s'),
              "prj_kode" => $voucherdata[0]['prj_kode'],
              "sit_kode" => $voucherdata[0]['sit_kode'],
              "action" => "UPDATE",
              "data_before" => $jsonLog,
              "data_after" => $jsonLog2,
              "ip" => $_SERVER["REMOTE_ADDR"],
              "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log_trans->insert($arrayLog);

        if (count($jsonFile) > 0)
        {
            foreach ($jsonFile as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => $voucherdata[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->file->insert($arrayInsert);
            }
        }

        if (count($jsonDeletedFile) > 0)
        {
            foreach ($jsonDeletedFile as $key => $val)
            {
                $this->file->delete("id = {$val['id']}");
            }
        }

        $json = "{success: true, number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);    
    }



}