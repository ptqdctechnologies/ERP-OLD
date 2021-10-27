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

    public function init ()
    {
        $this->customer = new Logistic_Model_LogisticCustomer();
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

        $cus_kode = $this->_getParam('cus_kode');
        $cus_name = $this->_getParam('cus_name');
        $cus_add = $this->_getParam('cus_add');
        $cus_phone = $this->_getParam('cus_phone');
        $cus_fax = $this->_getParam('cus_fax');
        $cus_email = $this->_getParam('cus_email');
        $cus_desc = $this->_getParam('cus_desc');
        $cus_tax_add = $this->getRequest()->getParam('cus_tax_add');
        $cus_npwp = $this->getRequest()->getParam('cus_npwp');

        $where = "cus_kode = '$cus_kode' OR cus_nama = '$cus_name' ";

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

            $return = array("success" => true);

        }

        $json = Zend_Json::encode($return);
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

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

}