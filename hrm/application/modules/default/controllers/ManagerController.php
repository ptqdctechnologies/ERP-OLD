<?php
class ManagerController extends Zend_Controller_Action
{

    private $db;
    private $uid;
    private $ldapdir;

    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');
        $this->uid = new Default_Models_MasterManager();
        $this->ldapdir = new Default_Models_Ldap();
    }

    public function indexAction()
    {
        // action body
    }

    public function listAction()
    {
         $this->_helper->viewRenderer->setNoRender();

         //for sales, IT, etc (non-project)..
         $nonproject = $this->getRequest()->getParam('nonproject');

        if ($nonproject) 
            $return = $this->uid->getManagerNonProject();
        else
            $return = $this->uid->getUid();
         foreach ($return as $key => $val)
         {
             if ($val['uid'] == '')
             {
                 unset($return[$key]);
                 continue;
             }
             $account = $this->ldapdir->getAccount($val['uid']);
             $return[$key]['nama'] = $account['displayname'][0];
         }
         $hasil['posts'] = $return;
         $hasil['count'] = count($return);
         Zend_Loader::loadClass('Zend_Json');
         $json = Zend_Json::encode($hasil);
         echo $json;
    }
    
    public function listbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'mgr_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        $sql = 'SELECT SQL_CALC_FOUND_ROWS mgr_kode,mgr_nama FROM master_manager WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function dblclickAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $return = $this->uid->getUidByParams($columnName,$columnValue);

        foreach ($return as $key => $val)
         {
             if ($val['uid'] == '')
             {
                 unset($return[$key]);
                 continue;
             }
             $account = $this->ldapdir->getAccount($val['uid']);
             $return[$key]['nama'] = $account['displayname'][0];
         }

        $hasil['posts'] = $return;
         $hasil['count'] = count($return);
         Zend_Loader::loadClass('Zend_Json');
         $json = Zend_Json::encode($hasil);



        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}
?>