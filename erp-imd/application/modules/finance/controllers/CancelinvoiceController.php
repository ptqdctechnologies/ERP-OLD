<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 4/26/12
 * Time: 10:06 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_CancelinvoiceController extends Zend_Controller_Action
{
    private $db;
    private $cancel_inv;
    private $jurnal_inv;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->cancel_inv = new Finance_Models_AccountingCancelInvoice();
        $this->counter = new Default_Models_MasterCounter();
        $this->jurnal_inv = new Finance_Models_AccountingCloseAR();
    }

    public function menuAction ()
    {

    }

    public function insertcancelinvoiceAction ()
    {
        
    }

    public function getinvoiceAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch != null || $textsearch != '')
        {
            $search = " AND $option LIKE '%$textsearch%'";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'a.tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $query =  "SELECT SQL_CALC_FOUND_ROWS a.trano,DATE_FORMAT(a.tgl,'%d %M %Y') as tgl,a.riv_no,a.prj_kode,a.prj_nama,a.sit_kode,a.sit_nama,
                  a.total,a.cus_kode,a.val_kode,a.uid,a.paymentterm,a.rateidr,b.cus_nama FROM finance_invoice a LEFT JOIN master_customer b
                  ON  a.cus_kode = b.cus_kode LEFT JOIN finance_payment_invoice pi ON a.trano = pi.inv_no LEFT JOIN finance_invoice_ci ci ON a.trano = ci.invoice_no
                  WHERE pi.inv_no IS NULL AND ci.invoice_no IS NULL $search ORDER BY $sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query($query);
        $hasil = $fetch->fetchAll();

        $data['data'] = $hasil;
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertcancelinvoiceAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $inv_number = $this->getRequest()->getParam('inv_number');
        $req_inv_number = $this->getRequest()->getParam('req_inv_number');
        $userinput = $this->getRequest()->getParam('uid');
        $cus_kode = $this->getRequest()->getParam('cus_kode');
        $cus_nama = $this->getRequest()->getParam('cus_nama');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $prj_nama = $this->getRequest()->getParam('prj_nama');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $sit_nama = $this->getRequest()->getParam('sit_nama');
        $desc = $this->getRequest()->getParam('desc');
        $val_kode = $this->getRequest()->getParam('val_kode1');
        $inv_value = $this->getRequest()->getParam('inv_value');
        $cancel_value = $this->getRequest()->getParam('cancel_value');
        $reason = $this->getRequest()->getParam('cancel_reason');
        $rateidr = $this->getRequest()->getParam('rateidr');
        
        $jsonjurnal = Zend_Json::decode($this->getRequest()->getParam('jsonjurnal'));

        $date = date("Y-m-d H:i:s");
        $uid = $this->session->userName;
        $type = 'CI';
        $trano = $this->counter->setNewTrans($type);

        $insert_cancel_inv = array(
            "trano" => $trano,
            "invoice_no" => $inv_number,
            "cancel_date" => $date,
            "riv_no" => $req_inv_number,
            "prj_kode" => $prj_kode,
            "prj_nama" => $prj_nama,
            "sit_kode" => $sit_kode,
            "sit_nama" => $sit_nama,
            "ket" => $desc,
            "total_invoice" => floatval(str_replace(",","",$inv_value)),
            "total_cancel" => floatval(str_replace(",","",$cancel_value)),
            "cus_kode" => $cus_kode,
            "cus_nama" => $cus_nama,
            "val_kode" => $val_kode,
            "uid" => $uid,
            "reason" => $reason,
            "rateidr" => floatval(str_replace(",","",$rateidr)),
        );

        $this->cancel_inv->insert($insert_cancel_inv);

        foreach($jsonjurnal as $key => $val)
        {
            $insert_cancel_jurnal = array(
                "trano" => $trano,
                "ref_number" => $val['trano'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "tgl" => $date,
                "uid" => $uid,
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama'],
                "debit" => $val['debit'],
                "credit" => $val['credit'],
                "val_kode" => $val['val_kode'],
                "rateidr" => floatval(str_replace(",","",$rateidr)),
                "type" => 'CANCEL-INV'
            );

            $this->jurnal_inv->insert($insert_cancel_jurnal);

        }

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getjurnalinvoiceAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $inv_number  = $this->getRequest()->getParam('inv_number');

        $data = $this->jurnal_inv->fetchAll("trano = '$inv_number'")->toArray();

        foreach ($data as $k => $val)
        {
            if ($val['debit'] == '0.00')
            {
                $data[$k]['debit'] = $val['credit'];
                $data[$k]['credit'] = '0.00';
            }

            if ($val['credit'] == '0.00')
            {
                $data[$k]['credit'] = $val['debit'];
                $data[$k]['debit'] = '0.00';
            }
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewcancelinvoiceAction ()
    {

    }

    public function getviewcancelinvAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $txtsearch = $this->getRequest()->getParam('search');

        if ($txtsearch != null || $txtsearch != '')
        {
            $search = "$option like '%$txtsearch%' ";
        }else{
            $search == null;
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'cancel_date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data['data'] = $this->cancel_inv->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();

        $data['total'] = $this->cancel_inv->fetchAll()->count();

//        $query =  "SELECT SQL_CALC_FOUND_ROWS * FROM finance_invoice_nc";
//        $fetch = $this->db->query($query);
//        $hasil = $fetch->fetchAll();
//
//        $data['data'] = $hasil;
//        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

}

