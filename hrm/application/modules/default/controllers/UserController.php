<?php
class UserController extends Zend_Controller_Action
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
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'name';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $user = new Default_Models_MasterUser();

        $return = $user->all(null,$sort,$dir, $limit, $offset);

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

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
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'master_login';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

//        $sql = 'SELECT SQL_CALC_FOUND_ROWS id,uid,master_login,Name FROM master_login WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
//
//        $fetch = $this->db->query($sql);
//        $return['posts'] = $fetch->fetchAll();
//        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        $user = new Default_Models_MasterUser();

        $return = $user->all($columnName . ' LIKE \'%' . $columnValue . '%\'' ,$sort,$dir, $limit, $offset);
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
?>