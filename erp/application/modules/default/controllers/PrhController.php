<?php

class PrhController extends Zend_Controller_Action
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
        $prj_kode = $request->getParam('prj_kode');
        
        $cekWorkflow = $request->getParam("cekworkflow");

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        if ($columnName != '')
            $where = " AND tipe = '$columnName'";
        
        if ($prj_kode != '')
            $where .= " AND prj_kode='$prj_kode'";
        
        if ($cekWorkflow)
        	$sql = 'SELECT SQL_CALC_FOUND_ROWS a.trano,tgl,a.prj_kode,a.prj_nama,a.sit_kode,a.sit_nama,a.budgettype FROM procurement_prh a LEFT JOIN workflow_trans b ON a.trano = b.item_id WHERE b.approve IN (300,400) OR b.approve is null GROUP BY a.trano ORDER BY a.tgl DESC ,' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        else
        	$sql = "SELECT SQL_CALC_FOUND_ROWS trano,tgl,prj_kode,prj_nama,sit_kode,sit_nama,budgettype FROM procurement_prh WHERE approve !=300  $where  ORDER BY tgl DESC,$sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();

        $projects = new Default_Models_MasterProject();
        $sites = new Default_Models_MasterSite();
        foreach($return['posts'] as $k => $v)
        {
            $return['posts'][$k]['tgl'] = date("d M Y",strtotime($v['tgl']));
            $return['posts'][$k]['prj_nama'] = $projects->getProjectName($v['prj_kode']);
            $return['posts'][$k]['sit_nama'] = $sites->getSiteName($v['prj_kode'],$v['sit_kode']);
        }

        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function rejectedAction(){
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $trano = $request->getParam('trano');
        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        
        if($trano !='' || $trano !=null){
            $trano = " AND trano LIKE '%$trano%'";
        }
        
        if($type !='' || $type !=null){
            $type = " AND tipe ='$type'";
        }
        
        if($prj_kode !='' || $prj_kode !=null){
            $prj_kode = " AND prj_kode LIKE '%$prj_kode%'";
        }
        
        if($sit_kode !='' || $sit_kode !=null){
            $sit_kode = " AND sit_kode LIKE '%$sit_kode%'";
        }
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,prj_kode,prj_nama,sit_kode,sit_nama 
                FROM procurement_prh 
                WHERE approve=300 and revisi is null $trano $type $prj_kode $sit_kode OR  approve=300 and revisi='' $trano $type $prj_kode $sit_kode ORDER BY id DESC LIMIT $offset,$limit";
        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        
        //the posts
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
        $tipe = $request->getParam('type');
        $prj_kode = $request->getParam('prj_kode');

        if($tipe != '')
            $where = " AND tipe = '$tipe'";
        
        if($prj_kode !='')
            $where .= " AND prj_kode = '$prj_kode' AND approve !=300";
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        $cekWorkflow = $this->_getParam("cekworkflow");
        if ($cekWorkflow)
            $sql = "SELECT SQL_CALC_FOUND_ROWS a.trano,tgl,a.prj_kode,a.prj_nama,a.sit_kode,a.sit_nama,a.budgettype FROM procurement_prh a LEFT JOIN workflow_trans b ON a.trano = b.item_id WHERE (b.approve IN (300,400) OR b.approve is null) AND (a.$columnName LIKE '%" . $columnValue . "%' $where) GROUP BY a.trano ORDER BY a.tgl DESC , $sort $dir LIMIT $offset , $limit";
        else
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_prh WHERE $columnName LIKE '%" . $columnValue . "%' $where ORDER BY $sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();

        $projects = new Default_Models_MasterProject();
        $sites = new Default_Models_MasterSite();
        foreach($return['posts'] as $k => $v)
        {
            $return['posts'][$k]['tgl'] = date("d M Y",strtotime($v['tgl']));
            $return['posts'][$k]['prj_nama'] = $projects->getProjectName($v['prj_kode']);
            $return['posts'][$k]['sit_nama'] = $sites->getSiteName($v['prj_kode'],$v['sit_kode']);
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