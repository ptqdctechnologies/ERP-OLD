<?php

class DohController extends Zend_Controller_Action
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
        // action body
    }

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,\'%m/%d/%Y\') as tgl,prj_kode,prj_nama,sit_kode,sit_nama,mdi_no FROM procurement_whoh  ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();

       // var_dump($return['posts']);die();
        foreach ($return['posts'] as $key => $val)
        {
            foreach ($val as $key2 => $val2)
            {
                if ($val2 == '""')
                    $return['posts'][$key][$key2] = '';
            }
        }
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
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

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $groupby = $request->getParam('groupby');
        if ($site)
        	$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_whod WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' GROUP BY trano,prj_kode, sit_nama ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        elseif ($groupby)
        	$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_whod WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' GROUP BY trano,prj_kode ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        else
        	$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_whod WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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