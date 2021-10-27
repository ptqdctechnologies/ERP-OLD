<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 1/16/12
 * Time: 10:07 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Sales_ReportController extends Zend_Controller_Action
{
    private $boq2;
    private $kboq2;
    private $praco;
    private $addpraco;
    private $file;

    public function init()
    {
        $this->boq2 = new Default_Models_MasterBoq2();
        $this->kboq2 = new Default_Models_MasterAddco();
        $this->praco = new Sales_Models_Praco();
        $this->file = new Default_Models_Files();
        $this->addpraco = new Sales_Models_AddPraco();
    }

    public function reportcoAction ()
    {
        
    }

    public function viewreportcoAction ()
    {
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');

        $this->view->prj_kode = $prj_kode;
        $this->view->sit_kode = $sit_kode;
    }

    public function getboq2Action ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');

        $data['data'] = $this->boq2->fetchAll("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getkboq2Action ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');

        $data['data'] = $this->kboq2->fetchAll("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getcofileAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $praco = $this->getRequest()->getParam('praco');

        $pracodata = $this->praco->fetchRow("trano = '$praco'");

        $data['data'] = $this->file->fetchAll("trano = '{$pracodata['regis_no']}'")->toArray();

//        var_dump($data['data']);die;

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getaddcofileAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $addpraco = $this->getRequest()->getParam('praco');

        $addpracodata = $this->addpraco->fetchRow("trano = '$addpraco'");

//        var_dump($addpracodata);die;

        $data['data'] = $this->file->fetchAll("trano = '{$addpracodata['regis_no']}'")->toArray();

//        var_dump($data['data']);die;

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }
}