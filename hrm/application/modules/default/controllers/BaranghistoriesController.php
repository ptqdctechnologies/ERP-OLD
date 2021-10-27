<?php

class BaranghistoriesController extends Zend_Controller_Action
{
    private $db;

    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');
    }

    public function indexAction()
    {

    }

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');
        $code = $request->getParam('code');
        $name = $request->getParam('name');
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 50;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $customer = new Default_Models_BarangHistories();

        $return['posts'] = $customer->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $return['count'] = $customer->fetchAll()->count();

        if ($code != '')
        {
            $return = $customer->getAllDistinct($code,'',$offset,$limit);
        }
        if ($name != '')
        {
            $return = $customer->getAllDistinct('',$name,$offset,$limit);
        }

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 3;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'brg_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS tra_no,tgl,brg_kode,brg_nama,val_kode,sat_kode,harga,sup_kode,sup_nama FROM transmaster_tmaterial_2009 WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
//        $sql = 'SELECT DISTINCT(tra_no,tgl),brg_kode,brg_nama,val_kode,sat_kode,harga,sup_kode,sup_nama FROM transmaster_tmaterial_2009 WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }


  }


?>