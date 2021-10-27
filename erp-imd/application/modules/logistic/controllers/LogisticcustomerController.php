<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/8/11
 * Time: 2:54 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_LogisticcustomerController extends Zend_Controller_Action
{

    private $customer;
    private $session;

    public function init ()
    {
        $this->customer = new Logistic_Model_LogisticCustomer();
         $this->session = new Zend_Session_Namespace('login');
    }

    public function customerAction ()
    {
        
    }

    public function viewcustomerAction ()
    {
        
    }

    public function getcustomerAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $search = $this->getRequest()->getParam('search');
        if(!$search)
            $search = null;
        else
            $search = "cus_nama like '%$search%' ";

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $customerdata = $this->customer->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        $return['data'] = $customerdata;
        $return['total'] = $this->customer->fetchAll()->count();

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function addcustomerAction ()
    {
        
    }

  public function getaddcustomerAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();
        
        $cus_kode = $this->_getParam('cus_kode');
        $cus_name = $this->_getParam('cus_name');
        $cus_add = $this->_getParam('cus_add');
        $cus_phone = $this->_getParam('cus_phone');
        $cus_fax = $this->_getParam('cus_fax');
        $cus_email = $this->_getParam('cus_email');
        $cus_desc = $this->_getParam('cus_desc');
        $cus_tax_add = $this->getRequest()->getParam('cus_tax_add');
        $cus_npwp = $this->getRequest()->getParam('cus_npwp');

        //$where = "cus_kode = '$cus_kode' OR cus_nama = '$cus_name' ";
        $where = "cus_kode = '$cus_kode'";

        $cekcustomer = $this->customer->fetchRow ($where);

        if ($cekcustomer)
        {
            $return = array("success" => false, "pesan" => "Sorry, Customer Exist");
        }else
        {
            $inserCustomer = array(
                "cus_kode" => $cus_kode,
                "cus_nama" => $cus_name,
                "alamat" => $cus_add,
                "tlp" => $cus_phone,
                "fax" => $cus_fax,
                "ket" => $cus_desc,
                "email" => $cus_email,
                "alamatpajak" => $cus_tax_add,
                "npwp" => $cus_npwp
            );

            $this->customer->insert($inserCustomer);
            $activityHead['master_customer'][0]=$inserCustomer;

            $return = array("success" => true);

        }

        $json = Zend_Json::encode($return);
        
         $activityLog = array(
            "menu_name" => "Create Customer",
            "trano" => $cus_kode,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        
        
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }
    public function editcustomerAction ()
    {
        $this->_helper->layout->disableLayout();

        $cus_kode = $this->_getParam('cus_kode');

        $where = "cus_kode = '$cus_kode'";

        $datacustomer = $this->customer->fetchRow($where);
        $this->view->tampil = $datacustomer;

    }

  public function geteditcustomerAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $cus_kode = $this->_getParam('cus_kode');
        $cus_name = $this->_getParam('cus_name');
        $cus_add = $this->_getParam('cus_add');
        $cus_phone = $this->_getParam('cus_phone');
        $cus_fax = $this->_getParam('cus_fax');
        $cus_email = $this->_getParam('cus_email');
        $cus_desc = $this->_getParam('cus_desc');
        $cus_tax_add = $this->getRequest()->getParam('cus_tax_add');
        $cus_npwp = $this->getRequest()->getParam('cus_npwp');
        $cus_id = $this->_getParam('id');

        // fetch data before
        $old = $this->customer->fetchAll("id = '$cus_id'")->toArray();
        $log['customer-detail-before'] = $old;
        
        $updateCustomer = array(
            "cus_kode" => $cus_kode,
            "cus_nama" => $cus_name,
            "alamat" => $cus_add,
            "tlp" => $cus_phone,
            "fax" => $cus_fax,
            "ket" => $cus_desc,
            "email" => $cus_email,
            "alamatpajak" => $cus_tax_add,
            "npwp" => $cus_npwp
        );

        $this->customer->update($updateCustomer,"id = '$cus_id'");

        // fetch data after
        $new = $this->customer->fetchAll("id = '$cus_id'")->toArray();
        $log2['customer-detail-after'] = $new;
        
        $return = array("success" => true);
        
            //Log Transaction
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $cus_id,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }


    public function getCustomerDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $cusKode = $this->_getParam("cus_kode");

        $data = $this->customer->fetchRow("cus_kode = '$cusKode'");
        if ($data)
        {
            $data = $data->toArray();
            $return = array(
                "success" => true,
                "data" => $data
            );

        }
        else
            $return = array(
                "success" => false,
                "msg" => "Customer not found"
            );

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateCustomerDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $field = $this->_getParam("field");
        $value = $this->_getParam("value");
        $cusKode = $this->_getParam("cus_kode");

        $data = $this->customer->fetchRow("cus_kode = '$cusKode'");
        if ($data)
        {

            $arrayInsert = array(
                $field => $value
            );

            $this->customer->update($arrayInsert,"cus_kode = '$cusKode'");
            $data = $this->customer->fetchRow("cus_kode = '$cusKode'");
            $return = array(
                "success" => true,
                "data" => $data->toArray()
            );

        }
        else
            $return = array(
                "success" => false,
                "msg" => "Customer not found"
            );

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }
}