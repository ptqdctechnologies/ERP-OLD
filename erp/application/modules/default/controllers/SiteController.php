<?php

class SiteController extends Zend_Controller_Action
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

        $listType = $request->getParam('byPrj_Kode');
        $showAll = $request->getParam('all');
        $showNonOverhead = $request->getParam('nooverhead');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sit_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $project = new Default_Models_MasterSite();
        
        if ($listType != '')
        {
            if ($showAll == '')
                $where = " AND stsclose=0";
            if ($showNonOverhead != '')
                $where .=  " AND stsoverhead = 'N'";
            $sql = "SELECT SQL_CALC_FOUND_ROWS *
                    FROM master_site
                    WHERE
                        prj_kode='$listType' $where
                    ORDER BY
                        sit_kode ASC
                    ";
            $fetch = $this->db->query($sql);
            $return['posts'] = $fetch->fetchAll();
        }
        else
        {
            if ($showAll == '')
                $where = " WHERE stsclose=0";
            if ($showNonOverhead != '')
            {
                if ($where)
                    $where .=  " AND stsoverhead = 'N'";
                else
                    $where =  "WHERE stsoverhead = 'N'";
            }
            //$return['posts'] = $project->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM master_site $where ORDER BY $sort $dir LIMIT $offset,$limit";
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
        $listType = $request->getParam('byPrj_Kode');
        $showAll = $request->getParam('all');
        $showNonOverhead = $request->getParam('nooverhead');
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sit_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        if ($showAll == '')
            //$where = " AND stsclose=0";
        if ($showNonOverhead != '')
            $where .=  " AND stsoverhead = 'N'";
        if ($listType != '')
        {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS sit_kode,sit_nama,prj_kode FROM master_site WHERE prj_kode LIKE \'%' . $listType . '%\' AND ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ' . $where . ' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        }
        else
        {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS sit_kode,sit_nama,prj_kode FROM master_site WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ' . $where . ' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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
    
    public function listbyparamsnewAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');
        $listType = $request->getParam('byPrj_Kode');
        $showAll = $request->getParam('all');
        $showNonOverhead = $request->getParam('nooverhead');
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sit_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        if ($showAll == '')
            //$where = " AND stsclose=0";
        if ($showNonOverhead != '')
            $where .=  " AND stsoverhead = 'N'";
        if ($listType != '')
        {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS sit_kode,sit_nama,prj_kode FROM master_site WHERE prj_kode LIKE \'%' . $listType . '%\' AND ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ' . $where . ' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        }
        else
        {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS sit_kode,sit_nama,prj_kode FROM master_site WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ' . $where . ' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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
    
    public function listbyprojectAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitNama = $request->getParam('sit_nama');
        $sitKode = $request->getParam('sit_kode');
        $showAll = $request->getParam('all');

        $type = $request->getParam('type');
        
        if ($sitKode != '')
        {
        	$fieldName = 'sit_kode';
        	$value = $sitKode;
        }
        else
        {
        	$fieldName = 'sit_nama';
        	$value = $sitNama;
        }
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sit_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        if ($showAll == '')
            //$where = " AND stsclose=0";

        if ($type)
        {
           $sql = 'SELECT SQL_CALC_FOUND_ROWS sit_kode,sit_nama,prj_kode FROM master_site WHERE ' . $fieldName . ' LIKE \'%' . $value . '%\' AND prj_kode =\'' . $prjKode . '\' AND type = O ' . $where . ' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        }
        else
            $sql = 'SELECT SQL_CALC_FOUND_ROWS sit_kode,sit_nama,prj_kode FROM master_site WHERE ' . $fieldName . ' LIKE \'%' . $value . '%\' AND prj_kode =\'' . $prjKode . '\' ' . $where . ' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
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

    public function listoverheadAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('byPrj_Kode');
        $showAll = $request->getParam('all');


        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sit_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $project = new Default_Models_MasterSite();

        if ($listType != '')
        {
            if ($showAll == '')
                $where = " AND stsclose=0";
            $sql = "SELECT SQL_CALC_FOUND_ROWS *
                    FROM master_site
                    WHERE
                        prj_kode='$listType'
                    AND
                        stsoverhead = 'Y'
                    $where
                    ORDER BY
                        sit_kode ASC
                    ";
            $fetch = $this->db->query($sql);
            $return['posts'] = $fetch->fetchAll();
        }
        else
        {
            if ($showAll == '')
                $where = " AND stsclose=0";
            //$return['posts'] = $project->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM master_site WHERE stsoverhead = 'Y' $where ORDER BY $sort $dir LIMIT $offset,$limit";
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

    public function listbyparamsoverheadAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');
        $listType = $request->getParam('byPjr_Kode');
        $showAll = $request->getParam('all');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sit_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        if ($showAll == '')
            $where = " AND stsclose=0";
        if ($listType != '')
        {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS sit_kode,sit_nama,prj_kode FROM master_site WHERE stsoverhead = \'Y\' AND prj_kode LIKE \'%' . $listType . '%\' AND ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ' . $where . ' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        }
        else
        {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS sit_kode,sit_nama,prj_kode FROM master_site WHERE stsoverhead = \'Y\' AND ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ' . $where . ' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

}

?>