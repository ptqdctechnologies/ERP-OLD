<?php

class FinanceController extends Zend_Controller_Action {

    private $db;
    private $uid;
    private $ldapdir;

    public function init() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');
        $this->uid = new Default_Models_MasterFinance();
        $this->ldapdir = new Default_Models_Ldap();
    }

    public function indexAction() {
        // action body
    }

    public function listAction() {
        $this->_helper->viewRenderer->setNoRender();

        //for sales
        $return = $this->uid->getFinance();
        $data=array();
        $index=0;
        foreach ($return as $key => $val) {
            if ($val['uid'] == '') {

                continue;
            }else{
                
                $nama = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
                if(strrpos($nama,'(DELETED ON LDAP)') <= 0){
                    $data[$index]['nama'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
                    $data[$index]['uid'] = $val['uid'];
                    $index++;
                    
                }else{
                    
                    continue;
                    
                }
            
             
            }
        }

        $hasil['posts'] = $data;
        $hasil['count'] = count($data);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($hasil);
        echo $json;
    }

    public function listbyparamsAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'mgr_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        $sql = 'SELECT SQL_CALC_FOUND_ROWS mgr_kode,mgr_nama FROM master_manager WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;
        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function dblclickAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $return = $this->uid->getUidByParams($columnName, $columnValue);

        foreach ($return as $key => $val) {
            if ($val['uid'] == '') {
                unset($return[$key]);
                continue;
            }

            $return[$key]['nama'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
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