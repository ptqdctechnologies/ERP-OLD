<?php

class CoaController extends Zend_Controller_Action
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

        $listType = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'coa_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $coa = new Default_Models_MasterCoa();

        $return['posts'] = $coa->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $return['count'] = $coa->fetchAll()->count();

        foreach ($return['posts'] as $key => $val)
        {
            foreach ($val as $key2 => $val2)
            {
                $tmp = str_replace("\"","",$val2);
                $tmp = str_replace("'","",$tmp);
                $tmp = str_replace("\r","",$tmp);
                $tmp = str_replace("\n","",$tmp);
                $return['posts'][$key][$key2] = $tmp;
            }
        }

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

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
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'coa_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS coa_kode,coa_nama,tipe,hd FROM master_coa WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        foreach ($return['posts'] as $key => $val)
        {
            foreach ($val as $key2 => $val2)
            {
                $tmp = str_replace("\"","",$val2);
                $tmp = str_replace("'","",$tmp);
                $tmp = str_replace("\r","",$tmp);
                $tmp = str_replace("\n","",$tmp);
                $return['posts'][$key][$key2] = $tmp;
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

}

?>