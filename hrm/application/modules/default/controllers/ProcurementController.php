<?php

class ProcurementController extends Zend_Controller_Action
{
    private $procurement;
    private $db;
    
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->leadHelper = $this->_helper->getHelper('chart');

        $session = new Zend_Session_Namespace('login');
//        $loader = new Zend_Loader_PluginLoader();
//        $loader->addPrefixPath('Zend_View_Helper', 'Zend/View/Helper/')->addPrefixPath('Grids_Helper',
//                       'application/modules/grid/helpers');
//        /* Initialize action controller here */
//        $formTextClass = $loader->load('Grids');
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
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        switch($listType)
        {
            case 'poh':
                $procurement = new Default_Models_ProcurementPoh();
                $return['posts'] = $procurement->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
                $return['count'] = $procurement->fetchAll()->count();
            break;
            case 'pod':
                $trano = $request->getParam('trano');
                $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_pod WHERE trano='$trano' ORDER BY urut";
                $fetch = $this->db->query($sql);
                $return['posts'] = $fetch->fetchAll();
                $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
            break;

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
        $joinToPod = $request->getParam('joinToPod');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if ($joinToPod)
        {
            $prj_kode = $request->getParam('Prj_Kode');
            $sql = 'SELECT DISTINCT SQL_CALC_FOUND_ROWS a.trano,a.prj_kode,a.tgl,a.tglpr FROM procurement_poh a INNER JOIN procurement_pod b ON a.prj_kode = b.prj_kode WHERE b.' . $columnName . ' LIKE \'%' . $columnValue . '%\' AND b.Prj_Kode LIKE \'%' . $prj_kode . '%\' ORDER BY a.' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        }
        else
            $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_poh WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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
        
    public function chartAction()
    {
        $request = $this->getRequest();

//        $chartType = $request->getParam('type');
//        if ($chartType == 'byProject')
//        {
//            $this->render('chart');
//        }
    }
    
}

?>