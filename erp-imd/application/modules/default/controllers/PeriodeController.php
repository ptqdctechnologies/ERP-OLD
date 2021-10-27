<?php

class PeriodeController extends Zend_Controller_Action
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

        $listType = $request->getParam('bydeptkode');


        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'perkode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $project = new Default_Models_MasterSite();

        if ($listType != '')
        {
            $sql = "SELECT SQL_CALC_FOUND_ROWS *
                    FROM master_periode_budget
                    WHERE
                        dep_kode='$listType'
                    ORDER BY
                        sit_kode ASC
                    ";
            $fetch = $this->db->query($sql);
            $return['posts'] = $fetch->fetchAll();
        }
        else
        {
            //$return['posts'] = $project->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM master_periode_budget ORDER BY $sort $dir LIMIT $offset,$limit";
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

    public function listbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');
        $listType = $request->getParam('byPjr_Kode');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'perkode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if ($listType != '')
        {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS perkode,per_nama,dep_kode FROM master_periode_budget WHERE dep_kode LIKE \'%' . $listType . '%\' AND ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        }
        else
        {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS perkode,per_nama,dep_kode FROM master_periode_budget WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        }

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

    public function listbydeptAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $depKode = $request->getParam('dep_kode');
        $perNama = $request->getParam('per_nama');
        $perKode = $request->getParam('perkode');

        if ($perKode != '')
        {
        	$fieldName = 'perkode';
        	$value = $perKode;
        }
        else
        {
        	$fieldName = 'per_nama';
        	$value = $perNama;
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'perkode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS perkode,per_nama,dep_kode FROM master_periode_budget WHERE ' . $fieldName . ' LIKE \'%' . $value . '%\' AND prj_kode =\'' . $depKode . '\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
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
