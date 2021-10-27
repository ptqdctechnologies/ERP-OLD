<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/10/11
 * Time: 1:36 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_LogistictypesupplierController extends Zend_Controller_Action
{
    private $typesupplier;

    public function  init()
    {
        $this->typesupplier = new Logistic_Model_LogisticTypeSupplier();
    }

    public function typesupplierAction ()
    {
        
    }

    public function addtypesupplierAction ()
    {
        
    }

    public function listtypesupplierAction ()
    {
        
    }

    public  function doinserttypesuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('supp-type');
        $typename = $this->getRequest()->getParam('supp-type-name');

        $where = "jenisupplier = '$type' AND jenissupliernama = '$typename'";

        $cek = $this->typesupplier->fetchRow ($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry, Type Supplier has ready to use");
        }else{

            $insert = array(
                "jenisupplier" => $type,
                "jenissupliernama" => $typename
            );

            $this->typesupplier->insert($insert);

             $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public  function  gettypesuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data['data'] = $this->typesupplier->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->typesupplier->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function  edittypesupplierAction ()
    {
        
    }

    public  function  doupdatetypesuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');
        $type = $this->getRequest()->getParam('supp-type');
        $typename = $this->getRequest()->getParam('supp-type-name');

        $update = array(
            "jenisupplier" => $type,
            "jenissupliernama" => $typename
        );

        $this->typesupplier->update($update,"id = '$id'" );

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }


}