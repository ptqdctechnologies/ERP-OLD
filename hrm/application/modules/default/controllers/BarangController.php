<?php

class BarangController extends Zend_Controller_Action
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
        $isPmeal = $request->getParam('pmeal');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'kode_brg';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $customer = new Default_Models_MasterBarang();
        $history = new Default_Models_BarangHistories();


       if ($code != '')
        {
            $where = "kode_brg LIKE '%$code%'";
        }
       if ($name != '')
        {
            $where = "nama_brg LIKE '%$name%'";
        }

        if ($isPmeal == 'true')
        {
            if ($where != '')
                $where .= " AND stspmeal = 'Y'";
            else
                $where = "stspmeal = 'Y'";
        }

        $return['posts'] = $customer->fetchAll($where, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $return['count'] = $customer->fetchAll($where)->count();

    	foreach ($return['posts'] as $key => $val)
        {
        	$barang = $history->getLastPrice($val['kode_brg']);
        	$return['posts'][$key]['harga'] = number_format($barang['harga'],2);
        	$return['posts'][$key]['tgl'] = $barang['tgl'];
        	$return['posts'][$key]['val_kode'] = $barang['val_kode'];
        	if ($val['stspmeal'] == 'Y')
                $return['posts'][$key]['is_pmeal'] = true;
            else
                $return['posts'][$key]['is_pmeal'] = false;
            if ($return['posts'][$key]['tglupdate'] == '0000-00-00')
                $return['posts'][$key]['tglupdate'] = '';
        }        
                
        //the posts
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

        $kodeBrg = $request->getParam('kode_brg');
        $namaBrg = $request->getParam('nama_brg');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'kode_brg';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if ($columnName != '' && $columnValue != '')
            $sql = 'SELECT SQL_CALC_FOUND_ROWS kode_brg,nama_brg, sat_kode FROM master_barang_project_2009 WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        else
        {
            if ($kodeBrg != '')
                $query = "kode_brg LIKE '%$kodeBrg%'";
            if ($namaBrg != '')
            {
                if ($kodeBrg != '')
                    $query .= " AND nama_brg LIKE '%$namaBrg%'";
                else
                    $query = "nama_brg LIKE '%$namaBrg%'";
            }
            $sql = 'SELECT SQL_CALC_FOUND_ROWS kode_brg,nama_brg, sat_kode FROM master_barang_project_2009 WHERE ' . $query . ' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        
        }

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
   
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listtosAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tos_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS tos_kode, tos_nama FROM master_typeofsuply  ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function listtosbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'ktg_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS tos_kode,tos_nama FROM master_typeofsuply WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listkategoriAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'ktg_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS ktg_kode, ktg_nama FROM master_kategori  ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function listkategoribyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'ktg_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS ktg_kode,ktg_nama FROM master_kategori WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listsubkategoriAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('byKtg_Kode');


        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sktg_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if ($listType != '')
        {
            $sql = "SELECT SQL_CALC_FOUND_ROWS *
                    FROM master_kategorisub
                    WHERE
                        ktg_kode='$listType'
                    ORDER BY
                        sktg_kode ASC
                    ";
            $fetch = $this->db->query($sql);
            $return['posts'] = $fetch->fetchAll();
        }
        else
        {

            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM master_kategorisub ORDER BY '$sort' '$dir' LIMIT '$offset','$limit'";
            $fetch = $this->db->query($sql);
            $return['posts'] = $fetch->fetchAll();
        }
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listbykategoriAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $ktgKode = $request->getParam('ktg_kode');
        $sktgNama = $request->getParam('sktg_nama');
        $sktgKode = $request->getParam('sktg_kode');

        if ($sktgKode != '')
        {
        	$fieldName = 'sktg_kode';
        	$value = $sktgKode;
        }
        else
        {
        	$fieldName = 'sktg_nama';
        	$value = $sktgNama;
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sit_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS sktg_kode,sktg_nama,ktg_kode FROM master_kategorisub WHERE ' . $fieldName . ' LIKE \'%' . $value . '%\' AND prj_kode =\'' . $ktgKode . '\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
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

  }


?>