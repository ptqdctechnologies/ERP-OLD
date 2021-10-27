<?php
class Finance_PaymentController extends Zend_Controller_Action
{
    private $db;
//    private $COA;
    private $session;
    private $paymentRPI;
    private $paymentRPId;
    private $paymentARF;
    private $paymentARFd;
    private $paymentREM;
    private $paymentREMd;
    private $settled;
    private $settledDetail;
    private $settledCancel;
    private $settledCancelDetail;
//    private $accBankPayment;
    private $voucher;
    private $coa_bank;
    private $jurnal_bank;
    private $coa;

    private $FINANCE;
    
    public function init()
    {
        $this->db = Zend_Registry::get('db');
//        $this->COA = Zend_Registry::get('const_coa');
        $this->session = new Zend_Session_Namespace('login');
        $this->paymentRPI = new Finance_Models_PaymentRPI();
        $this->paymentRPId = new Finance_Models_PaymentRPID();
        $this->paymentARF = new Finance_Models_PaymentARF();
        $this->paymentARFd = new Finance_Models_PaymentARFD();
        $this->paymentREM = new Finance_Models_PaymentReimbursH();
        $this->paymentREMd = new Finance_Models_PaymentReimbursD();
        $this->settled = new Finance_Models_Settled();
        $this->settledDetail = new Finance_Models_SettledDetail();
        $this->settledCancel = new Finance_Models_SettledCancel();
        $this->settledCancelDetail = new Finance_Models_SettledCancelDetail();
        $this->voucher = new Finance_Models_BankPaymentVoucher();
        $this->coa_bank = new Finance_Models_MasterCoaBank();
        $this->jurnal_bank = new Finance_Models_AccountingJurnalBank();
        $this->coa = new Finance_Models_MasterCoa();

        $models = array(
            "ArfAging"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);

//        $this->accBankPayment = new Finance_Models_AccountingBankPayment();
    }
    
    public function paymentrpiAction()
    {
        
    }

    public function payrpiAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $rpiH = new Default_Models_RequestPaymentInvoiceH();

//    	$type = $this->getRequest()->getParam("type");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $invoice_no = $this->getRequest()->getParam("invoice_no");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");

        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);


        $doc_trano = $jsonEtc[0]['doc_trano'];

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($jsonEtc[0]['type']);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] .'-'. $last;

        $where = "id=".$lastTrans['id'];
        $counter->update(array("urut" => $last),$where);

        if ($jsonEtc[0]['val_kode'] == 'USD')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        $rpi = $rpiH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if($jsonEtc[0]['vatradio'] == 1)
        {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        }
        elseif($jsonEtc[0]['vatradio'] == 3)
        {
           $ppn = 0;
           $ppnwht = $jsonEtc[0]['pajak'];
        }
        else
        {
            $ppn = 0;
           $ppnwht = 0; 
        }


        $urut =1;
        foreach($jsonData as $key => $val)
       {
            $total = $val['qty']*$val['harga'];

           if($jsonEtc[0]['vatradio'] == 1)
            {
                $ppn_d = 0.1*$total;
                $ppnwht_d = 0;
                $gtotal = $total+$ppn_d;
            }
            elseif($jsonEtc[0]['vatradio'] == 3)
            {
               $ppn_d = 0;
               $ppnwht_d = 0.1*$total;
               $gtotal = $total+$ppnwht_d;
            }
            else
            {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $total;
            }


			$arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
                "urut" => $urut,
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'invoice_no' => $jsonEtc[0]['invoice_no'],
                'total' => $total,
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'RPI',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $jsonEtc[0]['doc_trano'],
                'stspayment' =>'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
			);
            $urut++;
     
            $this->paymentRPId->insert($arrayInsert);
       }
        
        	$arrayInsert = array (
                'trano' => $trano,
                'tgl' => $tgl,
                'prj_kode' => $rpi['prj_kode'],
                'prj_nama' => $rpi['prj_nama'],
                'sit_kode' => $rpi['sit_kode'],
                'sit_nama' => $rpi['sit_nama'],
                'uid' => $uid,
                'invoice_no' => $jsonEtc[0]['invoice_no'],
                'total' => $rpi['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'RPI',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $jsonEtc[0]['doc_trano'],
                'stspayment' =>'Y',

                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn,
                'ppnwht' => $ppnwht,
                'gtotal' => $jsonEtc[0]['gtotal'],
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $this->paymentRPI->insert($arrayInsert);
        
        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatepayrpiAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $rpiH = new Default_Models_RequestPaymentInvoiceH();

//    	$trano = $this->getRequest()->getParam("trano");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $invoice_no = $this->getRequest()->getParam("invoice_no");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $status = $this->getRequest()->getParam("status");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");

        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

       $trano = $jsonEtc[0]['trano'];
        $doc_trano = $jsonEtc[0]['doc_trano'];
        
        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;
        $rpi = $rpiH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['status'] == 'true')
            $status = 'Y';
        else
            $status = 'N';

        if($jsonEtc[0]['vatradio'] == 1)
        {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        }
        elseif($jsonEtc[0]['vatradio'] == 3)
        {
           $ppn = 0;
           $ppnwht = $jsonEtc[0]['pajak'];
        }
        else
        {
            $ppn = 0;
           $ppnwht = 0;
        }

        $urut = 1;
        $this->paymentRPId->delete("trano = '$trano'");
        foreach($jsonData as $key => $val)
       {
           if($jsonEtc[0]['vatradio'] == 1)
            {
                $ppn_d = 0.1*$val['total'];
                $ppnwht_d = 0;
                $gtotal = $val['total']+$ppn_d;
            }
            elseif($jsonEtc[0]['vatradio'] == 3)
            {
               $ppn_d = 0;
               $ppnwht_d = 0.1*$val['total'];
               $gtotal = $val['total']+$ppnwht_d;
            }
            else
            {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $val['total'];
            }


			$arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
                "urut" => $urut,
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'invoice_no' => $jsonEtc[0]['invoice_no'],
                'total' => $val['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'RPI',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $jsonEtc[0]['doc_trano'],
                'stspayment' =>'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
			);
            $urut++;
          
            $this->paymentRPId->insert($arrayInsert);
       }

        $arrayInsert = array(

            'tgl' => $tgl,
            'uid' => $uid,
            'total' => $rpi['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],

            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' =>$status,            
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date

        );
      

        $fetch = $this->paymentRPI->update($arrayInsert,"trano = '$trano'");

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatepayarfAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $arfH = new Default_Models_AdvanceRequestFormH();

//    	$trano = $this->getRequest()->getParam("trano");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $requester = $this->getRequest()->getParam("requester");
//        $pic = $this->getRequest()->getParam("pic");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $status = $this->getRequest()->getParam("status");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

         $trano = $jsonEtc[0]['trano'];
        $doc_trano = $jsonEtc[0]['doc_trano'];

        if ($jsonEtc[0]['val_kode'] == 'USD')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;
        
        $arf = $arfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['status'] == 'true')
            $status = 'Y';
        else
            $status = 'N';

        if($jsonEtc[0]['vatradio'] == 1)
        {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        }
        elseif($jsonEtc[0]['vatradio'] == 3)
        {
           $ppn = 0;
           $ppnwht = $jsonEtc[0]['pajak'];
        }
        else
        {
            $ppn = 0;
           $ppnwht = 0;
        }

        $urut = 1;
        $this->paymentARFd->delete("trano = '$trano'");
        foreach($jsonData as $key => $val)
       {
           if($jsonEtc[0]['vatradio'] == 1)
            {
                $ppn_d = 0.1*$val['total'];
                $ppnwht_d = 0;
                $gtotal = $val['total']+$ppn_d;
            }
            elseif($jsonEtc[0]['vatradio'] == 3)
            {
               $ppn_d = 0;
               $ppnwht_d = 0.1*$val['total'];
               $gtotal = $val['total']+$ppnwht_d;
            }
            else
            {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $val['total'];
            }

			$arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
                "urut" => $urut,
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $val['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ARF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'pic' => $jsonEtc[0]['pic'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $jsonEtc[0]['doc_trano'],
                'stspayment' =>'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,

			);
            $urut++;
            
            $this->paymentARFd->insert($arrayInsert);
       }

        $arrayInsert = array(

            'tgl' => $tgl,

            'uid' => $uid,
            'total' => $arf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],

            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'requester' => $jsonEtc[0]['requester'],
            'pic' => $jsonEtc[0]['pic'],
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' =>$status,
//            'coa_kode' => $coa_kode,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal']

            );


        $fetch = $this->paymentARF->update($arrayInsert,"trano = '$trano'");

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function paymentarfAction()
    {

    }

    public function payarfAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $arfH = new Default_Models_AdvanceRequestFormH();

//    	$type = $this->getRequest()->getParam("type");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $requester = $this->getRequest()->getParam("requester");
//        $pic = $this->getRequest()->getParam("pic");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $doc_trano = $jsonEtc[0]['doc_trano'];

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($jsonEtc[0]['type']);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] .'-'. $last;

        $where = "id=".$lastTrans['id'];
        $counter->update(array("urut" => $last),$where);

        if ($jsonEtc[0]['val_kode'] == 'USD')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;
        $arf = $arfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if($jsonEtc[0]['vatradio'] == 1)
        {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        }
        elseif($jsonEtc[0]['vatradio'] == 3)
        {
           $ppn = 0;
           $ppnwht = $jsonEtc[0]['pajak'];
        }
        else
        {
            $ppn = 0;
           $ppnwht = 0;
        }

        $urut =1;
        foreach($jsonData as $key => $val)
       {
            $total = $val['qty']*$val['harga'];

           if($jsonEtc[0]['vatradio'] == 1)
            {
                $ppn_d = 0.1*$total;
                $ppnwht_d = 0;
                $gtotal = $total+$ppn_d;
            }
            elseif($jsonEtc[0]['vatradio'] == 3)
            {
               $ppn_d = 0;
               $ppnwht_d = 0.1*$total;
               $gtotal = $total+$ppnwht_d;
            }
            else
            {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $total;
            }

			$arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
                "urut" => $urut,
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $total,
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ARF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'pic' => $jsonEtc[0]['pic'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $jsonEtc[0]['doc_trano'],
                'stspayment' =>'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,

			);
            $urut++;
      
            $this->paymentARFd->insert($arrayInsert);
       }

        $arrayInsert = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'prj_kode' => $arf['prj_kode'],
            'prj_nama' => $arf['prj_nama'],
            'sit_kode' => $arf['sit_kode'],
            'sit_nama' => $arf['sit_nama'],
            'uid' => $uid,
            'total' => $arf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'jenis_document' => 'ARF',
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'requester' => $jsonEtc[0]['requester'],
            'pic' => $jsonEtc[0]['pic'],
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' =>'Y',
//            'coa_kode' => $coa_kode,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal']
        );

        $fetch = $this->paymentARF->insert($arrayInsert);

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function settledasfAction()
    {

    }

    public function settledcancelasfAction()
    {

    }

    public function setasfAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

//    	$doc_trano = $this->getRequest()->getParam("doc_trano");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $requester = $this->getRequest()->getParam("requester");
//        $pic = $this->getRequest()->getParam("pic");
//        $arf_no = $this->getRequest()->getParam("arf_no");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $doc_trano = $jsonEtc[0]['doc_trano'];

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($jsonEtc[0]['type']);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] .'-'. $last;

        $where = "id=".$lastTrans['id'];
        $counter->update(array("urut" => $last),$where);

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        if($jsonEtc[0]['vatradio'] == 1)
        {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        }
        elseif($jsonEtc[0]['vatradio'] == 3)
        {
           $ppn = 0;
           $ppnwht = $jsonEtc[0]['pajak'];
        }
        else
        {
            $ppn = 0;
           $ppnwht = 0;
        }

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');
        
        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        $urut =1;
        foreach($jsonData as $key => $val)
       {
            $total = $val['qty']*$val['harga'];

           if($jsonEtc[0]['vatradio'] == 1)
            {
                $ppn_d = 0.1*$total;
                $ppnwht_d = 0;
                $gtotal = $total+$ppn_d;
            }
            elseif($jsonEtc[0]['vatradio'] == 3)
            {
               $ppn_d = 0;
               $ppnwht_d = 0.1*$total;
               $gtotal = $total+$ppnwht_d;
            }
            else
            {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $total;
            }


			$arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
                "urut" => $urut,
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ASF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' =>$doc_trano,
                'stspayment' =>'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
			);
            $urut++;
          
            $this->settledDetail->insert($arrayInsert);
       }

        $arrayInsert = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'prj_kode' => $asf['prj_kode'],
            'prj_nama' => $asf['prj_nama'],
            'sit_kode' => $asf['sit_kode'],
            'sit_nama' => $asf['sit_nama'],
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'jenis_document' => 'ASF',
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'requester' => $jsonEtc[0]['requester'],
            'orangpic' => $jsonEtc[0]['pic'],
            'arf_no' => $jsonData[0]['arf_no'],
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' =>$doc_trano,
            'stspayment' =>'Y',
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );

        $fetch = $this->settled->insert($arrayInsert);

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatesetasfAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

//    	$trano = $this->getRequest()->getParam("trano");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $invoice_no = $this->getRequest()->getParam("invoice_no");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $status = $this->getRequest()->getParam("status");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");

        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

       $trano = $jsonEtc[0]['trano'];
        $doc_trano = $jsonEtc[0]['doc_trano'];

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');


        if ($jsonEtc[0]['status'] == 'true')
            $status = 'Y';
        else
            $status = 'N';

        if($jsonEtc[0]['vatradio'] == 1)
        {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        }
        elseif($jsonEtc[0]['vatradio'] == 3)
        {
           $ppn = 0;
           $ppnwht = $jsonEtc[0]['pajak'];
        }
        else
        {
            $ppn = 0;
           $ppnwht = 0;
        }

        $urut = 1;
        $this->settledDetail->delete("trano = '$trano'");
        foreach($jsonData as $key => $val)
       {
           if($jsonEtc[0]['vatradio'] == 1)
            {
                $ppn_d = 0.1*$val['total'];
                $ppnwht_d = 0;
                $gtotal = $val['total']+$ppn_d;
            }
            elseif($jsonEtc[0]['vatradio'] == 3)
            {
               $ppn_d = 0;
               $ppnwht_d = 0.1*$val['total'];
               $gtotal = $val['total']+$ppnwht_d;
            }
            else
            {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $val['total'];
            }


			$arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
                "urut" => $urut,
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ASF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' =>$doc_trano,
                'stspayment' =>'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
			);
            $urut++;
          
            $this->settledDetail->insert($arrayInsert);
       }

        $arrayInsert = array(

            'tgl' => $tgl,
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],

            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' =>$status,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date

        );


        $fetch = $this->settled->update($arrayInsert,"trano = '$trano'");

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function cancelasfAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

//    	$doc_trano = $this->getRequest()->getParam("doc_trano");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $requester = $this->getRequest()->getParam("requester");
//        $pic = $this->getRequest()->getParam("pic");
//        $arf_no = $this->getRequest()->getParam("arf_no");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $doc_trano = $jsonEtc[0]['doc_trano'];

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($jsonEtc[0]['type']);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] .'-'. $last;

        $where = "id=".$lastTrans['id'];
        $counter->update(array("urut" => $last),$where);

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        if($jsonEtc[0]['vatradio'] == 1)
        {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        }
        elseif($jsonEtc[0]['vatradio'] == 3)
        {
           $ppn = 0;
           $ppnwht = $jsonEtc[0]['pajak'];
        }
        else
        {
            $ppn = 0;
           $ppnwht = 0;
        }

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        $urut =1;
        foreach($jsonData as $key => $val)
       {
            $total = $val['qty']*$val['harga'];

           if($jsonEtc[0]['vatradio'] == 1)
            {
                $ppn_d = 0.1*$total;
                $ppnwht_d = 0;
                $gtotal = $total+$ppn_d;
            }
            elseif($jsonEtc[0]['vatradio'] == 3)
            {
               $ppn_d = 0;
               $ppnwht_d = 0.1*$total;
               $gtotal = $total+$ppnwht_d;
            }
            else
            {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $total;
            }


			$arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
                "urut" => $urut,
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ASF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' =>$doc_trano,
                'stspayment' =>'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
			);
            $urut++;

            $this->settledCancelDetail->insert($arrayInsert);
       }

        $arrayInsert = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'prj_kode' => $asf['prj_kode'],
            'prj_nama' => $asf['prj_nama'],
            'sit_kode' => $asf['sit_kode'],
            'sit_nama' => $asf['sit_nama'],
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'jenis_document' => 'ASF',
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'requester' => $jsonEtc[0]['requester'],
            'orangpic' => $jsonEtc[0]['pic'],
            'arf_no' => $jsonData[0]['arf_no'],
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' =>$doc_trano,
            'stspayment' =>'Y',
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );

        $fetch = $this->settledCancel->insert($arrayInsert);

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatecancelasfAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

//    	$trano = $this->getRequest()->getParam("trano");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $invoice_no = $this->getRequest()->getParam("invoice_no");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $status = $this->getRequest()->getParam("status");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");

        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

       $trano = $jsonEtc[0]['trano'];
        $doc_trano = $jsonEtc[0]['doc_trano'];

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');


        if ($jsonEtc[0]['status'] == 'true')
            $status = 'Y';
        else
            $status = 'N';

        if($jsonEtc[0]['vatradio'] == 1)
        {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        }
        elseif($jsonEtc[0]['vatradio'] == 3)
        {
           $ppn = 0;
           $ppnwht = $jsonEtc[0]['pajak'];
        }
        else
        {
            $ppn = 0;
           $ppnwht = 0;
        }

        $urut = 1;
        $this->settledDetail->delete("trano = '$trano'");
        foreach($jsonData as $key => $val)
       {
           if($jsonEtc[0]['vatradio'] == 1)
            {
                $ppn_d = 0.1*$val['total'];
                $ppnwht_d = 0;
                $gtotal = $val['total']+$ppn_d;
            }
            elseif($jsonEtc[0]['vatradio'] == 3)
            {
               $ppn_d = 0;
               $ppnwht_d = 0.1*$val['total'];
               $gtotal = $val['total']+$ppnwht_d;
            }
            else
            {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $val['total'];
            }


			$arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
                "urut" => $urut,
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ASF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' =>$doc_trano,
                'stspayment' =>'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
			);
            $urut++;

            $this->settledCancelDetail->insert($arrayInsert);
       }

        $arrayInsert = array(

            'tgl' => $tgl,
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],

            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' =>$status,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date

        );


        $fetch = $this->settledCancel->update($arrayInsert,"trano = '$trano'");

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editpaymentrpiAction()
    {
        $request = $this->getRequest();

        $trano = $request->getParam('trano');
        $rpid = $this->paymentRPId->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $id = 1;
        foreach($rpid as $key => $val)
        {
            $rpid[$key]['id'] = $id;
            $id++;

          foreach ($val as $key2 => $val2)
          {
              if ($val2 == '""')
                  $rpid[$key][$key2] = '';
          }

            $rpid[$key]['uom'] = $val['sat_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($rpid);
        $this->view->json = $jsonData;

        $sql = "SELECT * FROM finance_payment_rpi WHERE trano ='$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $this->view->trano = $return['trano'];
        $this->view->docTrano = $return['doc_trano'];
        $this->view->valKode = $return['val_kode'];
        $this->view->total = $return['total'];
        $this->view->totalBayar = $return['total_bayar'];
        $this->view->status = $return['stspayment'];
        $this->view->ket = $return['ket'];
        $this->view->rate = $return['rateidr'];
//        $this->view->coa_kode = $return['coa_kode'];
        $this->view->vatradio = $return['vatradio'];
        $this->view->gtotal = $return['gtotal'];
        $this->view->ppn = $return['ppn'];
        $this->view->ppnwht = $return['ppnwht'];
        $this->view->payType = $return['pola_bayar'];
        $this->view->date = $return['tgl_jtt'];
        
    }

    public function editpaymentarfAction()
    {
        $request = $this->getRequest();

        $trano = $request->getParam('trano');
        $arfd = $this->paymentARFd->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $id = 1;
        foreach($arfd as $key => $val)
        {
            $arfd[$key]['id'] = $id;
            $id++;
          foreach ($val as $key2 => $val2)
          {
              if ($val2 == '""')
                  $arfd[$key][$key2] = '';
          }

            $arfd[$key]['uom'] = $val['sat_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($arfd);
        $this->view->json = $jsonData;

        $sql = "SELECT * FROM finance_payment_arf WHERE trano ='$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();
       
        $this->view->trano = $return['trano'];
        $this->view->docTrano = $return['doc_trano'];
        $this->view->valKode = $return['val_kode'];
        $this->view->total = $return['total'];
        $this->view->totalBayar = $return['total_bayar'];
        $this->view->status = $return['stspayment'];
        $this->view->ket = $return['ket'];
        $this->view->rate = $return['rateidr'];
//        $this->view->coa_kode = $return['coa_kode'];
        $this->view->vatradio = $return['vatradio'];
        $this->view->gtotal = $return['gtotal'];
        $this->view->ppn = $return['ppn'];
        $this->view->ppnwht = $return['ppnwht'];

    }

    public function editsettledasfAction()
    {
        $request = $this->getRequest();

        $trano = $request->getParam('trano');
        $asfd = $this->settledDetail->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $id = 1;
        foreach($asfd as $key => $val)
        {
            $asfd[$key]['id'] = $id;
            $id++;
          foreach ($val as $key2 => $val2)
          {
              if ($val2 == '""')
                  $asfd[$key][$key2] = '';
          }

            $asfd[$key]['uom'] = $val['sat_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfd);
        $this->view->json = $jsonData;

        $sql = "SELECT * FROM finance_settled WHERE trano ='$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $this->view->trano = $return['trano'];
        $this->view->docTrano = $return['doc_trano'];
        $this->view->valKode = $return['val_kode'];
        $this->view->total = $return['total'];
        $this->view->totalBayar = $return['total_bayar'];
        $this->view->status = $return['stspayment'];
        $this->view->ket = $return['ket'];
        $this->view->rate = $return['rateidr'];
//        $this->view->coa_kode = $return['coa_kode'];
        $this->view->vatradio = $return['vatradio'];
        $this->view->gtotal = $return['gtotal'];
        $this->view->ppn = $return['ppn'];
        $this->view->ppnwht = $return['ppnwht'];
        $this->view->payType = $return['pola_bayar'];
        $this->view->date = $return['tgl_jtt'];

    }

    public function editsettledcancelasfAction()
    {
        $request = $this->getRequest();

        $trano = $request->getParam('trano');
        $asfd = $this->settledCancelDetail->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
        $id = 1;
        foreach($asfd as $key => $val)
        {
            $asfd[$key]['id'] = $id;
            $id++;
          foreach ($val as $key2 => $val2)
          {
              if ($val2 == '""')
                  $asfd[$key][$key2] = '';
          }

            $asfd[$key]['uom'] = $val['sat_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfd);
        $this->view->json = $jsonData;

        $sql = "SELECT * FROM finance_settledcancel WHERE trano ='$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $this->view->trano = $return['trano'];
        $this->view->docTrano = $return['doc_trano'];
        $this->view->valKode = $return['val_kode'];
        $this->view->total = $return['total'];
        $this->view->totalBayar = $return['total_bayar'];
        $this->view->status = $return['stspayment'];
        $this->view->ket = $return['ket'];
        $this->view->rate = $return['rateidr'];
//        $this->view->coa_kode = $return['coa_kode'];
        $this->view->vatradio = $return['vatradio'];
        $this->view->gtotal = $return['gtotal'];
        $this->view->ppn = $return['ppn'];
        $this->view->ppnwht = $return['ppnwht'];
        $this->view->payType = $return['pola_bayar'];
        $this->view->date = $return['tgl_jtt'];

    }

    public function paymentrpilistAction()
    {
         $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,doc_trano,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_payment_rpi  ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function paymentrpilistbyparamAction()
    {
         $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('nilai');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM finance_payment_rpi  WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function paymentarflistAction()
    {
         $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,doc_trano,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_payment_arf  ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function paymentarflistbyparamAction()
    {
         $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('nilai');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM finance_payment_arf  WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function settledasflistAction()
    {
         $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,doc_trano,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_settled  ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function settledasflistbyparamAction()
    {
         $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('nilai');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM finance_settled  WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function settledasfcancellistAction()
    {
         $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,doc_trano,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_settledcancel  ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function settledasfcancellistbyparamAction()
    {
         $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('nilai');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM finance_settledcancel  WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function paymentvoucherrpiAction ()
    {
        $this->view->type = 'RPI';
    }

        public function paymentvoucherarfAction ()
    {
        $this->view->type = 'ARF';
    }

    public function getvoucherAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $type = $this->getRequest()->getParam('type');

        $search = "";

        if ($textsearch == "" || $textsearch == null)
        {
            $search = "";
        }else if ($textsearch != null && $option == 1)
        {
            $search = "AND trano like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 2)
        {
            $search = "AND tgl like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 3)
        {
            $search = "AND item_type like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 4)
        {
            $search = "AND prj_kode like '%$textsearch%' ";
        }else if ($textsearch != null && $option == 5)
        {
            $search = "AND valuta like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data = $this->voucher->viewpaymentvoucher($offset,$limit,$dir,$sort,$type,$search,$trano);

//        $sql = $this->db->select()
//                   ->from(array('finance_payment_voucher'))
//                   ->where($search)
//                   ->order(array($sort . ' ' . $dir))
//                       ->group(array("trano"))
//                   ->limit($limit,$offset);
//
//        if ($search == '')
//            $sql->reset(Zend_Db_Select::WHERE);
//
//        $data['data'] = $this->db->fetchAll($sql);
//
//        $data['total'] = $this->voucher->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getvoucherlistdetailAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $tranoedit = $this->getRequest()->getParam('tranoedit');
        $type = $this->getRequest()->getParam('type');

        $data['data'] = $this->voucher->fetchAll("trano = '$trano'")->toArray();


        if ($data['data'] != '')
        {
            foreach($data['data'] as $k => $v)
            {
                $tranoAsli = $v['trano'];
                $trano = $v['ref_number'];
                if ($tranoedit != '')
                {
                    $where = "trano = '$tranoedit' AND doc_trano = '$trano'";
                }
                else
                {
                    $where = "doc_trano = '$trano'";
                }
                if ($type == 'ARF')
                {
                    $fetch = $this->paymentARF->fetchAll($where . " AND voc_trano = '$tranoAsli'");
                }
                if ($type == 'RPI')
                {
//                    $fetch = $this->paymentRPI->fetchAll($where);
                    $isPPN = false;
                    $dataVoucher = $this->voucher->fetchRow("trano = '$tranoAsli'");
                    if ($dataVoucher['status_bpv_ppn'] == 1)
                        $isPPN = true;
                    $select = $this->db->select()
                        ->from(array("finance_payment_rpi"))
                        ->where($where);

                    $fetch = $this->db->fetchAll($select);
                    foreach($fetch as $k3 => $v3)
                    {
                        if ($v3['voc_trano'])
                        {
                            $cek = $this->voucher->fetchRow("trano = '{$v3['voc_trano']}'");
                            if ($cek)
                            {
                                if ($isPPN && $cek['status_bpv_ppn'] == 0)
                                    unset($fetch[$k3]);
                            }
                        }
                    }
                }
                if ($type == 'REM'){

                    if ($tranoedit != '')
                    $where = "trano = '$tranoedit' AND rem_no = '$trano'";
                else
                    $where = "rem_no = '$trano'";
//
//                    $sql = " SELECT *,total as total_bayar FROM finance_payment_reimbursement where  $where";
//                    $fetch = $this->db->query($sql);
//                    $fetch = $fetch->fetchAll();

                    $fetch = $this->paymentREM->fetchAll($where);
                }

                $tots = 0;
                if ($fetch)
                {
//                    $fetch = $fetch->toArray();

                    if ($tranoedit != '')
                    {
                        if ($type != 'REM')
                            $data['data'][$k]['total_payment'] = $fetch[0]['total_bayar'];
                        else
                            $data['data'][$k]['total_payment'] = $fetch[0]['total'];
                    }
                    else
                    {
                        foreach($fetch as $k2 => $v2)
                        {
                            if ($type != 'REM')
                                $tots += floatval($v2['total_bayar']);
                            else
                            {
                                if ($v2['voc_trano'] == '')
                                    continue;
                                $tots += floatval($v2['total']);
                            }
                        }
                        $data['data'][$k]['total_payment'] = floatval($v['total']) - $tots;
//                        $data['data'][$k]['total_payment']  = 0;
                    }
                }
                else
                    $data['data'][$k]['total_payment'] =  floatval($v['total']);
            }
        }
        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpaymentAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano'); //voucer trano
        $foredit = $this->getRequest()->getParam('foredit');
        $tranoedit = $this->getRequest()->getParam('tranoedit'); //voucher ref number (ARF/RPI/REM)
        $type = $this->getRequest()->getParam('type');

//        var_dump($trano,$foredit,$tranoedit,$type);die;

        $pay = array();

        if ($foredit != '')
            $foredit = true;

        if ($foredit)
        {
            if ($trano == '')
            {
                if ($type == 'ARF')
                {
                    $pays = $this->paymentARF->fetchAll("trano = '$tranoedit'");
                    if ($pays)
                    {
                        foreach($pays as $k => $v)
                        {
                            $arf = $v['doc_trano'];
                            $temp = $this->paymentARF->fetchAll("trano != '$tranoedit' AND doc_trano = '$arf'");
                            foreach($temp as $k2 => $v2)
                            {
                                $pay[] = $v2;
                            }
                        }
                    }
                    $temp = $this->paymentARF->fetchRow("trano = '$tranoedit'")->toArray();
                    $data[] = array(
                        "ref_number" => $temp['doc_trano'],
                        "prj_kode" => $temp['prj_kode'],
                        "sit_kode" => $temp['sit_kode'],
                        "valuta" => $temp['val_kode'],
                        "total" => $temp['total'],
                        "total_bayar" => $temp['total_bayar'],
                        "total_payment" => $temp['total_bayar'],
                    );
                }

                if ($type == 'RPI')
                {
                    $pays = $this->paymentRPI->fetchAll("trano = '$tranoedit'");
                    if ($pays)
                    {
                        foreach($pays as $k => $v)
                        {
                            $rpi = $v['doc_trano'];
                            $temp = $this->paymentRPI->fetchAll("trano != '$tranoedit' AND doc_trano = '$rpi'");
                            foreach($temp as $k2 => $v2)
                            {
                                $pay[] = $v2;
                            }
                        }
                    }
                    $temp = $this->paymentRPI->fetchRow("trano = '$tranoedit'")->toArray();
                    $data[] = array(
                        "ref_number" => $temp['doc_trano'],
                        "prj_kode" => $temp['prj_kode'],
                        "sit_kode" => $temp['sit_kode'],
                        "valuta" => $temp['val_kode'],
                        "total" => $temp['total'],
                        "total_bayar" => $temp['total_bayar'],
                        "total_payment" => $temp['total_bayar'],
                    );
                }

                if ($type == 'REM')
                {
                    $pays = $this->paymentREM->fetchAll("trano = '$tranoedit'");
                    if ($pays)
                    {
                        foreach($pays as $k => $v)
                        {
                            $rem = $v['rem_no'];
                            $temp = $this->paymentREM->fetchAll("trano != '$tranoedit' AND rem_no = '$rem'");
                            foreach($temp as $k2 => $v2)
                            {
                                $v2['total_bayar'] = $v2['total'];
                                $pay[] = $v2;
                            }
                        }
                    }
                    $temp = $this->paymentREM->fetchRow("trano = '$tranoedit'")->toArray();
                    $data[] = array(
                        "ref_number" => $temp['rem_no'],
                        "prj_kode" => $temp['prj_kode'],
                        "sit_kode" => $temp['sit_kode'],
                        "valuta" => $temp['val_kode'],
                        "total" => $temp['total'],
                        "total_bayar" => $temp['total'],
                        "total_payment" => $temp['total'],
                    );
                }
            }
            else
            {
                if ($type == 'ARF')
                {
                    $pays = $this->paymentARF->fetchAll("voc_trano = '$trano' AND trano != '$tranoedit'");
                    if ($pays)
                    {
                        $pay = $pays->toArray();
                    }
                }

                if ($type == 'RPI')
                {
                    $pays = $this->paymentRPI->fetchAll("voc_trano = '$trano' AND trano != '$tranoedit'");
                    if ($pays)
                    {
                        $pay = $pays->toArray();
                        $cek = $this->voucher->fetchRow("trano = '$trano'");
                        if ($cek)
                        {
                            $isPPN = $cek['status_bpv_ppn'];
                            if ($isPPN == 1)
                            {
                                foreach($pay as $k3 => $v3)
                                {
                                    $cek = $this->voucher->fetchRow("trano = '{$v3['voc_trano']}'");
                                    if ($cek['status_bpv_ppn'] == 0)
                                        unset($pay[$k3]);
                                }
                            }
                        }
                    }
                }

                if ($type == 'REM')
                {
                    $pays = $this->paymentREM->fetchAll("voc_trano = '$trano' AND trano != '$tranoedit'");
                    if ($pays)
                    {
                        $pay = $pays->toArray();
                    }

                    foreach($pay as $k3 => $v3)
                    {
                        $pay[$k3]['total_bayar'] = $v3['total'];
                    }
                }
                $data = $this->voucher->fetchAll("trano = '$trano'")->toArray();
            }
        }
        else
        {
            $pay = $this->getLastPayment($trano,$foredit,$tranoedit);
            $data = $this->voucher->fetchAll("trano = '$trano'")->toArray();
        }

        $pay_total = 0;

        foreach ($pay as $key => $val)
        {
            $pay_total += floatval($val['total_bayar']);

        }

        $gtotal = 0;

        foreach ($data as $key => $val)
        {
            $gtotal += floatval($val['total']);
        }

        $return = array("success" => true,"gtotal" => $gtotal,"voc_data" => $data,"paid" => $pay_total);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertrpivoucherpaymentAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));

//        $bank_coa = $this->coa_bank->fetchRow("trano_type = '{$form['trans']}'");
        //Khusus untuk pembayaran petty cash. Dapatkan coa kode dari yg dipilih oleh User
        $coaPC = $this->getRequest()->getParam('coaPC');

        if ($coaPC == '')
        {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                array(
                    "type" => $form['trans']
                )
            );
        }
        else
        {
            $bank_coa['data'] = array(QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coaPC)));
        }
        if ($bank_coa === false)
        {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }

        $form['pay-value'] = str_replace(",","",$form['pay-value']);
        $form['rateidr'] = str_replace(",","",$form['rateidr']);
        $form['voc-val'] = str_replace(",","",$form['voc-val']);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($form['trans']);

//        $lastTrans = $counter->getLastTrans($form['trans']);
//        $last = abs($lastTrans['urut']);
//        $last = $last + 1;
//        $trano = $lastTrans['tra_no'] .'-'. $last;
//
//        $where = "id=".$lastTrans['id'];
//        $counter->update(array("urut" => $last),$where);

        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['rateidr'];
        else
            $rateidr = 0;

        $rpiH = new Default_Models_RequestPaymentInvoiceH();
        $rpiD = new Default_Models_RequestPaymentInvoice();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');


        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
            "valuta" => "USD"
        ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];

        foreach($list as $k => $v)
        {
            $rpi = $rpiH->fetchRow("trano='{$v['ref_number']}'")->toArray();
            $rpid = $rpiD->fetchAll("trano='{$v['ref_number']}'")->toArray();

            foreach($rpid as $key => $val)
            {
                $total = $val['qty']*$val['harga'];
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "urut" => $val['urut'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "sat_kode" => $val['uom'],
                    'coa_kode' => $val['coa_kode'],
                    'coa_nama' => $val['coa_nama'],
                    'sat_kode' => $val['sat_kode'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $uid,
                    'invoice_no' => $rpi['invoice_no'],
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'jenis_document' => 'RPI',
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'ket' => $form['notes'],
                    'doc_trano' => $v['ref_number'],
                    'stspayment' =>'Y',
//                    'vatradio' => $jsonEtc[0]['vatradio'],
//                    'ppn' => $ppn_d,
//                    'ppnwht' => $ppnwht_d,
                    'gtotal' => (floatval($val['qty']) * floatval($val['harga'])),
                    'pola_bayar' => $form['pay-type'],
                    'tgl_jtt' => $date
                );
                    $this->paymentRPId->insert($arrayInsert);
            }
            $arrayInsert = array (
                'trano' => $trano,
                'voc_trano' => $form['voc-number'],
                'tgl' => $tgl,
                'prj_kode' => $rpi['prj_kode'],
                'prj_nama' => $rpi['prj_nama'],
                'sit_kode' => $rpi['sit_kode'],
                'sit_nama' => $rpi['sit_nama'],
                'uid' => $uid,
                'invoice_no' => $rpi['invoice_no'],
                'total' => $rpi['total'],
                'total_bayar' => $v['total_payment'],
                'jenis_document' => 'RPI',
                'val_kode' => $rpi['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $form['notes'],
                'doc_trano' => $v['ref_number'],
                'stspayment' =>'Y',
//                'vatradio' => $jsonEtc[0]['vatradio'],
//                'ppn' => $ppn,
//                'ppnwht' => $ppnwht,
                'gtotal' => $rpi['total'],
                'pola_bayar' => $form['pay-type'],
                'tgl_jtt' => $date
            );
            $this->paymentRPI->insert($arrayInsert);
        }

        //insert jurnal bank

        foreach($list as $key2 => $val2)
        {
//            $coa_kode = '2-1100';
            if ($val2['valuta'] == 'IDR')
            {
                $coa_kode = '2-1110';
                $coa_kode2 = '';
            }
            elseif($val2['valuta'] == 'USD')
            {
                $coa_kode = '2-1121';
                $coa_kode2 = '2-1122';
            }
            $coa = $this->coa->fetchRow("coa_kode = '$coa_kode'");

            $insertcip = array(
                "trano" => $form['voc-number'],
                "ref_number" => $val2['ref_number'],
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $coa['coa_kode'],
                "coa_nama" => $coa['coa_nama'],
                "debit" => $val2['total_payment'],
                "credit" => 0,
                "val_kode" => $val2['valuta'],
                'rateidr' => $currentRateidr,
                "type" => 'BPV'
            );

            $this->jurnal_bank->insert($insertcip);
            if ($coa_kode2 != '')
            {
                $coa = $this->coa->fetchRow("coa_kode = '$coa_kode2'");
                $insertcip = array(
                    "trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coa['coa_kode'],
                    "coa_nama" => $coa['coa_nama'],
                    "debit" => (floatval($val2['total_payment']) * $currentRateidr),
                    "credit" => 0,
                    "val_kode" => $val2['valuta'],
                    'rateidr' => $currentRateidr,
                    "type" => 'BPV'
                );

                $this->jurnal_bank->insert($insertcip);
            }

            foreach($bank_coa['data'] as $k3 => $v3)
            {
                $totalInsert = $val2['total_payment'];
                if ($val2['valuta'] != 'IDR')
                {
                    if (strpos($v3['coa_nama'],'Exchange') !== false)
                        $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                }

                $insertbank = array(
                    "trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $v3['coa_kode'],
                    "coa_nama" => $v3['coa_nama'],
                    "credit" => $totalInsert,
                    "debit" => 0,
                    "val_kode" => $val2['valuta'],
                    'rateidr' => $currentRateidr,
                    "type" => 'BPV'
                );
                $this->jurnal_bank->insert($insertbank);
            }

        }

        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertarfvoucherpaymentAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        //Khusus untuk pembayaran petty cash. Dapatkan coa kode dari yg dipilih oleh User
        $coaPC = $this->getRequest()->getParam('coaPC');

        if ($coaPC == '')
        {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                array(
                    "type" => $form['trans']
                )
            );
        }
        else
        {
            $bank_coa['data'] = array(QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coaPC)));
        }

        if ($bank_coa === false)
        {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }

        $form['pay-value'] = str_replace(",","",$form['pay-value']);
        $form['rateidr'] = str_replace(",","",$form['rateidr']);
        $form['voc-val'] = str_replace(",","",$form['voc-val']);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($form['trans']);

//        $lastTrans = $counter->getLastTrans($form['trans']);
//        $last = abs($lastTrans['urut']);
//        $last = $last + 1;
//        $trano = $lastTrans['tra_no'] .'-'. $last;
//
//        $where = "id=".$lastTrans['id'];
//        $counter->update(array("urut" => $last),$where);

        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['rateidr'];
        else
            $rateidr = 0;

        $arfH = new Default_Models_AdvanceRequestFormH();
        $arfD = new Default_Models_AdvanceRequestFormD();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');

        foreach($list as $k => $v)
        {
            $arf = $arfH->fetchRow("trano='{$v['ref_number']}'")->toArray();
            $arfd = $arfD->fetchAll("trano='{$v['ref_number']}'")->toArray();

            //Insert ke Accounting Bank Payment
//            $arrayBankPayment = array(
//                "trano" => $trano,
//                "ref_number" => $v['ref_number'],
//                "tgl" => date("Y-m-d H:i:s"),
//                "uid" => $this->session->userName
//            );

//            $coaModel = new Finance_Models_MasterCoa();
//
//            //Debit, Account Payable
//            if ($arf['val_kode'] == 'IDR')
//                $coa = $this->COA['AP_IDR'];
//            elseif ($arf['val_kode'] == 'USD')
//                $coa = $this->COA['AP_USD'];
//
//            $coas = $coaModel->fetchRow("coa_kode = '$coa'");
//
//            $arrayBankPayment['coa_kode'] = $coa;
//            $arrayBankPayment['coa_nama'] = $coas['coa_nama'];
//            $arrayBankPayment['debit'] = $v['total_payment'];
//            $arrayBankPayment['credit'] = '';
//
//            $this->accBankPayment->insert($arrayBankPayment);
//
//            //Kredit, COA bank
//            $coabank = $this->accBankPayment->fetchRow("trano_type = '{$form['trans']}'");

            foreach($arfd as $key => $val)
            {
                $total = $val['qty']*$val['harga'];
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "urut" => $val['urut'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "sat_kode" => $val['uom'],
                    'coa_kode' => $val['coa_kode'],
                    'coa_nama' => $val['coa_nama'],
                    'sat_kode' => $val['sat_kode'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $uid,
                    'requester' => $arf['requester'],
                    'pic' => $arf['pic'],
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'jenis_document' => 'ARF',
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'ket' => $form['notes'],
                    'doc_trano' => $v['ref_number'],
                    'stspayment' =>'Y',
//                    'vatradio' => $jsonEtc[0]['vatradio'],
//                    'ppn' => $ppn_d,
//                    'ppnwht' => $ppnwht_d,
                    'gtotal' => (floatval($val['qty']) * floatval($val['harga'])),
//                    'pola_bayar' => $form['pay-type'],
//                    'tgl_jtt' => $date
                );
                $this->paymentARFd->insert($arrayInsert);

//                if ($arf['bt'] == 'Y')
//                {
                    $tglAkhir = new DateTime('now');
                    $waktu = 13; //Waktu aging buat ARF, tgl dibuatnya payment dihitung...
                    $tglAkhir->add(new DateInterval("P" . $waktu . "D"));
                    $arrayInsert = array(
                        'trano' => $trano,
                        'tgl' => $tgl,
                        'tgl_akhir' => $tglAkhir->format('Y-m-d H:i:s'),
                        'voc_trano' => $form['voc-number'],
                        "prj_kode" => $val['prj_kode'],
                        "sit_kode" => $val['sit_kode'],
                        "workid" => $val['workid'],
                        "workname" => $val['workname'],
                        "kode_brg" => $val['kode_brg'],
                        "nama_brg" => $val['nama_brg'],
                        'sat_kode' => $val['uom'],
                        'qty' => $val['qty'],
                        'harga' => $val['harga'],
                        'uid' => $val['requester'],
                        'total' => (floatval($val['qty']) * floatval($val['harga'])),
                        'total_bayar' => $v['total_payment'],
                        'val_kode' => $val['val_kode'],
                        'rateidr' => $rateidr,
                        'arf_no' => $v['ref_number']
                    );
                    $this->FINANCE->ArfAging->insert($arrayInsert);
//                }

            }
            $arrayInsert = array (
                'trano' => $trano,
                'voc_trano' => $form['voc-number'],
                'tgl' => $tgl,
                'prj_kode' => $arf['prj_kode'],
                'prj_nama' => $arf['prj_nama'],
                'sit_kode' => $arf['sit_kode'],
                'sit_nama' => $arf['sit_nama'],
                'uid' => $uid,
                'requester' => $arf['requester'],
                'pic' => $arf['pic'],
                'total' => $arf['total'],
                'total_bayar' => $v['total_payment'],
                'jenis_document' => 'ARF',
                'val_kode' => $arf['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $form['notes'],
                'doc_trano' => $v['ref_number'],
                'stspayment' =>'Y',
//                'vatradio' => $jsonEtc[0]['vatradio'],
//                'ppn' => $ppn,
//                'ppnwht' => $ppnwht,
                'gtotal' => $arf['total'],
//                'pola_bayar' => $form['pay-type'],
//                'tgl_jtt' => $date
            );
            $this->paymentARF->insert($arrayInsert);
        }

        //insert jurnal bank

        foreach($list as $key2 => $val2)
        {
            $cip_coa = $this->coa->fetchRow("coa_kode = '{$val2['coa_kode']}'");

            $insertcip = array(
                "trano" => $form['voc-number'],
                "ref_number" => $val2['ref_number'],
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $cip_coa['coa_kode'],
                "coa_nama" => $cip_coa['coa_nama'],
                "debit" => $val2['total_payment'],
                'val_kode' => $val2['valuta'],
                "credit" => 0,
                "type" => 'BPV'
            );

            $this->jurnal_bank->insert($insertcip);

            foreach($bank_coa['data'] as $k3 => $v3)
            {

                $totalInsert = $val2['total_payment'];
                if ($val2['valuta'] != 'IDR')
                {
                    if (strpos($v3['coa_nama'],'Exchange') !== false)
                        $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                }

                $insertbank = array(
                    "trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $v3['coa_kode'],
                    "coa_nama" => $v3['coa_nama'],
                    "credit" => $totalInsert,
                    'val_kode' => $val2['valuta'],
                    "debit" => 0,
                    "type" => 'BPV'
                );
                $this->jurnal_bank->insert($insertbank);
            }
        }

        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdatearfvoucherpaymentAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $trano = $this->getRequest()->getParam('trano');

        $form['pay-value'] = str_replace(",","",$form['pay-value']);

        foreach ($list as $k => $v)
        {

            $arrayUpdate = array(
                "total_bayar" => $v['total_payment'],
                "ket" => $form['notes']
            );

            $this->paymentARF->update($arrayUpdate,"trano = '$trano' AND doc_trano = '{$v['ref_number']}'");
            $this->paymentARFd->update($arrayUpdate,"trano = '$trano' AND doc_trano = '{$v['ref_number']}'");
        }


        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdaterpivoucherpaymentAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $trano = $this->getRequest()->getParam('trano');

        $form['pay-value'] = str_replace(",","",$form['pay-value']);

        foreach ($list as $k => $v)
        {

            $arrayUpdate = array(
                "total_bayar" => $v['total_payment'],
                "ket" => $form['notes'],
                "pola_bayar" => $form['pay-type'],
                "tgl_jtt" => date('Y-m-d',strtotime($form['date']))
            );

            $this->paymentRPI->update($arrayUpdate,"trano = '$trano' AND doc_trano = '{$v['ref_number']}'");
            $this->paymentRPId->update($arrayUpdate,"trano = '$trano' AND doc_trano = '{$v['ref_number']}'");
        }


        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    private function getLastPayment($trano='')
    {
        $data = $this->voucher->fetchAll("trano = '$trano'")->toArray();

        if ($data[0]['item_type'] == 'RPI')
        {
            $pay = $this->paymentRPI->fetchAll("voc_trano = '$trano'");
            if (!$pay)
            {
                foreach($data as $k => $v)
                {
                    $temp = $this->paymentRPI->fetchAll("doc_trano = '{$v['ref_number']}'");
                    if ($temp)
                    {
                        $temp = $temp->toArray();
                        foreach($temp as $k2 => $v2)
                        {
                            $pay[] = $v2;
                        }
                    }
                }
            }
            else
            {
                $pay = $pay->toArray();
//                $pay = $pay[0];
            }
        }
        elseif ($data[0]['item_type'] == 'ARF')
        {
            $pay = $this->paymentARF->fetchAll("voc_trano = '$trano'");
            if (!$pay)
            {
                foreach($data as $k => $v)
                {
                    $temp = $this->paymentARF->fetchAll("doc_trano = '{$v['ref_number']}'");
                    if ($temp)
                    {
                        $temp = $temp->toArray();
                        foreach($temp as $k2 => $v2)
                        {
                            $pay[] = $v2;
                        }
                    }
                }
            }
            else
            {
                $pay = $pay->toArray();
//                $pay = $pay[0];
            }
        }
        elseif ($data[0]['item_type'] == 'REM')
        {
            $sql = " SELECT *,total as total_bayar FROM finance_payment_reimbursement where voc_trano = '$trano'";
            $fetch = $this->db->query($sql);
            $pay = $fetch->fetchAll();
            if (!$pay)
            {
                foreach($data as $k => $v)
                {
                    $sql = " SELECT *,total as total_bayar FROM finance_payment_reimbursement where rem_no = '{$v['ref_number']}'";
                    $fetch = $this->db->query($sql);
                    $temp = $fetch->fetchAll();
                    if ($temp)
                    {
//                        $temp = $temp->toArray();
                        foreach($temp as $k2 => $v2)
                        {
                            $pay[] = $v2;
                        }
                    }
                }
            }
            else
            {
//                $pay = $pay->toArray();
//                $pay = $pay[0];
            }
        }
        if (!$pay)
            $pay = array();
        return $pay;
    }

    public function getpaymenthistoryAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $return['data'] = $this->getLastPayment($trano);

        $ldap = new Default_Models_Ldap();
        foreach($return['data'] as $k => $v)
        {
            $uid = $v['uid'];
            if ($username[$uid] == '')
            {
                $othername = $ldap->getAccount($uid);
                $name = $othername['displayname'][0];
                $username[$uid] = $name;
            }

            $return['data'][$k]['username'] = $username[$uid];
        }
        
        echo Zend_Json::encode($return);

    }

    public function editpaymentvoucherarfAction()
    {
        $trano = $this->getRequest()->getParam("trano");

        $dataEdit = $this->paymentARF->fetchRow("trano = '$trano'");

        if ($dataEdit)
            $dataEdit = $dataEdit->toArray();

        $this->view->type = 'ARF';
        $this->view->data = $dataEdit;
        $this->view->trano = $trano;
    }

    public function editpaymentvoucherrpiAction()
    {
        $trano = $this->getRequest()->getParam("trano");

        $dataEdit = $this->paymentRPI->fetchRow("trano = '$trano'");

        if ($dataEdit)
            $dataEdit = $dataEdit->toArray();

        $this->view->type = 'RPI';
        $this->view->data = $dataEdit;
        $this->view->trano = $trano;
    }

    public function checktransactiontypeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $func = $this->getRequest()->getParam('callbackFunc');

        if ($type != 'PC')
        {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
            array(
                "type" => $type
            )
        );
        }
        else
        {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaDetail('1-1100');
        }
        if ($bank_coa === false)
        {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }

        $return = array(
            "success" => true,
            "data" => $bank_coa
        );

        echo Zend_Json::encode($return);
    }
}
?>
