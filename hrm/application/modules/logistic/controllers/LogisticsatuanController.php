<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/7/11
 * Time: 9:52 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_LogisticsatuanController extends Zend_Controller_Action
{
    private $satuan;


    public function init()
    {
        $this->satuan = new Logistic_Model_LogisticSatuan();
    }

    public function satuanAction()
    {

    }

    public function allsatuanAction ()
    {

    }

    public function getsatuanAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $search = $this->getRequest()->getParam('search');
        if (!$search)
            $search = null;
        else
            $search = "sat_nama like '%$search%'";

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 10;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sat_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $satuandata = $this->satuan->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        $return['data'] = $satuandata;
        $return['total'] = $this->satuan->fetchAll()->count();

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addsatuanAction ()
    {
        
    }

    public function getaddsatuanAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $sat_kode = $this->_getParam('sat_kode');
        $sat_name = $this->_getParam('sat_name');
        $sat_desc = $this->_getParam('sat_desc');

        $where = "sat_kode = '$sat_kode' OR sat_nama = '$sat_name' ";

        $cekSatuan = $this->satuan->fetchRow($where);

        if ($cekSatuan)
        {
            $return = array("success" => false, "pesan" => "Sorry, satuan has ready to use");
        }else
        {
            $insertSatuan = array(
                "sat_kode" => $sat_kode,
                "sat_nama" => $sat_name,
                "ket" => $sat_desc
            );

            $this->satuan->insert($insertSatuan);

            $return = array("success" => true);

        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);



    }

    public function editsatuanAction ()
    {
  
        $this->_helper->layout->disableLayout();

        $sat_kode = $this->_getParam('satkode');

        $where = "sat_kode = '$sat_kode'";

        $datasatuan = $this->satuan->fetchRow($where);
        $this->view->tampil = $datasatuan;

//        var_dump($datasatuan);die;


    }

    public function geteditsatuanAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $sat_kode = $this->_getParam('sat_kode');
        $sat_name = $this->_getParam('sat_name');
        $sat_desc = $this->_getParam('sat_desc');
        $sat_id = $this->_getParam('id');

        $UpdateSatuan = array(
            "sat_kode" => $sat_kode,
            "sat_nama" => $sat_name,
            "ket" => $sat_desc
        );

        $this->satuan->update($UpdateSatuan,"id = '$sat_id'");

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }


}