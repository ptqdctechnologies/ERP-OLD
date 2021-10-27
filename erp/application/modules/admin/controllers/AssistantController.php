<?php
/**
 * Created by PhpStorm.
 * User: bherly
 * Date: Jan 7, 2011
 * Time: 11:25:59 AM
 * To change this template use File | Settings | File Templates.
 */
class Admin_AssistantController extends Zend_Controller_Action
{
    private $db;
    private $session;
    private $const;
    private $error;
    private $pa;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
        $this->session = new Zend_Session_Namespace('login');
		$this->error = $this->_helper->getHelper('error');

        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Models_Workflow();
        $this->pa = new Admin_Model_PersonalAssistant();
    }

    public function addAction()
    {
        $this->_helper->viewRenderer->setNoRender();
		$uid = $this->getRequest()->getParam('id');
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));

        $insertArray['uid'] = $uid;
        $insertArray['uid_manager'] = $jsonData['uid_manager'];
        if ($jsonData['active'] == 'on')
            $insertArray['active'] = 1;
        else
            $insertArray['active'] = 0;

        $lastId = $this->pa->insert($insertArray);
        $insertArray['id'] = $lastId;
//        $ldapdir = new Default_Models_Ldap();
//        $account = $ldapdir->getAccount($jsonData['uid_manager']);
//        $insertArray['display_name'] = $account['displayname'][0];
        $insertArray['display_name'] = QDC_User_Ldap::factory(array("uid" => $jsonData['uid_manager']))->getName();
        $return = array('success' => true,'message' => 'Created user Role','posts' => $insertArray);
		$json = Zend_Json::encode($return);
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateAction()
    {
        $this->_helper->viewRenderer->setNoRender();
		$uid = $this->getRequest()->getParam('id');
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));

        $insertArray['uid'] = $uid;
        $insertArray['uid_manager'] = $jsonData['uid_manager'];
        if ($jsonData['active'] == 'on')
            $insertArray['active'] = 1;
        else
            $insertArray['active'] = 0;

        $this->pa->update($insertArray,"uid = '$uid' AND id = " .$jsonData['id']);

//        $ldapdir = new Default_Models_Ldap();
//        $account = $ldapdir->getAccount($jsonData['uid_manager']);
//        $insertArray['display_name'] = $account['displayname'][0];
        $insertArray['display_name'] = QDC_User_Ldap::factory(array("uid" => $jsonData['uid_manager']))->getName();
        $return = array('success' => true,'message' => 'Updated user Role','posts' => $insertArray);
		$json = Zend_Json::encode($return);
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function deleteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
		$uid = $this->getRequest()->getParam('id');
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));

        $this->pa->delete("uid = '$uid' AND id = " .$jsonData['id']);

        $return = array('success' => true,'message' => 'Deleted user Role','posts' => array());
		$json = Zend_Json::encode($return);
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showAction()
    {

    }

    public function assistantAction()
    {
        
    }

    public function listmanagerAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $sql = "
                SELECT ml.uid as uid_manager, mrt.display_name FROM
                    master_login ml
                LEFT JOIN
                    master_role mr
                ON
                    ml.id = mr.id_user
                LEFT JOIN
                    master_role_type mrt
                ON
                    mr.id_role = mrt.id
                WHERE
                (
                    mrt.role_name = 'BOD'
                OR
                    mrt.display_name = 'Project Manager'
                )
                AND
                    mrt.display_name != 'Executive Assistant'
                GROUP BY ml.uid
        ";

        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        if($hasil)
        {
            $ldapdir = new Default_Models_Ldap();
            foreach ($hasil as $key => $val)
            {
//                $account = $ldapdir->getAccount($val['uid_manager']);
//                $hasil[$key]['display_name'] = $account['displayname'][0] . " (" . $val['display_name'] . ")";
                $hasil[$key]['display_name'] = QDC_User_Ldap::factory(array("uid" => $val['uid_manager']))->getName() . " (" . $val['display_name'] . ")";
            }
        }
        $return['posts'] = $hasil;
        $return['count'] = count($hasil);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listassistantAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->getRequest()->getParam('id');
        $sql = "
                SELECT * FROM
                    personal_assistant
                WHERE
                    uid = '$uid'
        ";

        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        if($hasil)
        {
            $ldapdir = new Default_Models_Ldap();
            foreach ($hasil as $key => $val)
            {
//                $account = $ldapdir->getAccount($val['uid_manager']);
//                $hasil[$key]['display_name'] = $account['displayname'][0];
                $hasil[$key]['display_name'] = QDC_User_Ldap::factory(array("uid" => $val['uid_manager']))->getName();
                $hasil[$key]['id'] = $val['id'];
            }
        }
        $return['posts'] = $hasil;
        $return['count'] = count($hasil);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
?>
 
