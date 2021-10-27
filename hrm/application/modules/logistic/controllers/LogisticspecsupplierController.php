<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/11/11
 * Time: 9:25 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_LogisticspecsupplierController extends Zend_Controller_Action
{
    private $specialistsupp;

    public function init()
    {
        $this->specialistsupp = new Logistic_Model_LogisticSpecialistSupplier();
    }

    public  function specsupplierAction ()
    {
        
    }

    public function addspecsupplierAction ()
    {
        
    }

    public function doinsertspecsuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $spec = $this->getRequest()->getParam('supp-spec');
        $specname = $this->getRequest()->getParam('supp-spec-name');

        $where = "subjenisupplier = '$spec' AND subjenissupliernama = '$specname'";

        $cek = $this->specialistsupp->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry, Specialist Supplier has ready to use");
        }else{

            $insert = array(
                "subjenisupplier" => $spec,
                "subjenissupliernama" => $specname
            );

            $this->specialistsupp->insert($insert);

             $return = array("success" => true);
        }
        
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getspecsuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data['data'] = $this->specialistsupp->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->specialistsupp->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listspecsupplierAction ()
    {
        
    }

    public function editspecsupplierAction ()
    {
        
    }

    public function doupdatespecsuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');
        $spec = $this->getRequest()->getParam('supp-spec');
        $specname = $this->getRequest()->getParam('supp-spec-name');

        $update = array(
            "subjenisupplier" => $spec,
            "subjenissupliernama" => $specname
        );

        $this->specialistsupp->update($update,"id = '$id'" );

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

}