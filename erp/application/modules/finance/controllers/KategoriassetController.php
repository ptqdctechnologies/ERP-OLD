<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/26/12
 * Time: 9:28 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_KategoriassetController extends Zend_Controller_Action
{
    private $db;
    private $kategori;

    public function init()
    {
        $this->kategori = new Finance_Models_MasterKategoriAsset();
    }

    public function menuAction ()
    {

    }

    public function insertkategoriassetAction ()
    {

    }

    public function doinsertkategoriassetAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $code = $this->getRequest()->getParam('code');
        $name = $this->getRequest()->getParam('name');
        $coa_debit = $this->getRequest()->getParam('coa_debit');
        $coa_credit = $this->getRequest()->getParam('coa_credit');

        $insertkategori = array(
            "kode_ktfa" => $code,
            "nama" => $name,
            "coa_debit" => $coa_debit,
            "coa_credit" => $coa_credit
        );

        $this->kategori->insert($insertkategori);


        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewkategoriassetAction ()
    {

    }

    public function getkategoriassetAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $textsearch = $this->getRequest()->getParam('search');
        $option = $this->getRequest()->getParam('option');

        $search = "";

        if ($textsearch == "" || $textsearch == null)
        {
            $search = null;
        }else if ($textsearch != null && $option != null)
        {
            $search = " $option like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->kategori->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->kategori->fetchAll()->count();

//        $return = array("success" => true);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editkategoriassetAction ()
    {
        $kode = $this->getRequest()->getParam('kode');

        $kategori = $this->kategori->fetchRow("kode_ktfa = '$kode'");

        $this->view->kategori = $kategori;
    }

    public function doupdatekategoriassetAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $code = $this->getRequest()->getParam('code');
        $name = $this->getRequest()->getParam('name');
        $coa_debit = $this->getRequest()->getParam('coa_debit');
        $coa_credit = $this->getRequest()->getParam('coa_credit');

        $updatekategori = array(
            "nama" => $name,
            "coa_debit" => $coa_debit,
            "coa_credit" => $coa_credit
        );

        $this->kategori->update($updatekategori,"kode_ktfa= '$code'");

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

}