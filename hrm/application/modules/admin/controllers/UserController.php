<?php
/* 
Created @ Mar 30, 2010 10:24:38 AM
 */

class Admin_UserController extends Zend_Controller_Action
{

   private $db;
   private $session;
   private $masterLogin;

   public function init()
   {
       $this->db = Zend_Registry::get('db');
       $this->session = new Zend_Session_Namespace('login');
       $this->masterLogin = new Admin_Model_Masterlogin();
   }

  
   public function listAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 50;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'uid';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
       $sql = "SELECT
                SQL_CALC_FOUND_ROWS
                id,
                uid,
                name,
                npk,
                id_privilege as is_admin
               FROM master_login
               ORDER BY $sort $dir
               LIMIT $offset,$limit";
       $fetch = $this->db->query($sql);
       $result = $fetch->fetchAll();

       $ldapdir = new Default_Models_Ldap();
       $userLoginLog = new Default_Models_UserLoginLog();
       foreach($result as $key => $val)
       {
           $account = $ldapdir->getAccount($val['uid']);
           $result[$key]['name'] = $account['displayname'][0];
           if ($val['is_admin'] == '0')
               $result[$key]['is_admin'] = 0;
           else
               $result[$key]['is_admin'] = 1;
           $result[$key]['last_login'] = $userLoginLog->getLastLoginByUid($val['uid']);
       }
       $return['posts'] = $result;
       $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
       Zend_Loader::loadClass('Zend_Json');
       $json = Zend_Json::encode($return);
       //result encoded in JSON

       $this->getResponse()->setHeader('Content-Type', 'text/javascript');
       $this->getResponse()->setBody($json);
   }

	public function listbyparamAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       $request = $this->getRequest();
       $data = mysql_escape_string($request->getParam('data'));
       $sql = "SELECT
                id,
                master_login,
                name,
                npk
               FROM master_login
               WHERE master_login LIKE '%$data%'
               ORDER BY id";
       $fetch = $this->db->query($sql);
       $return['posts'] = $fetch->fetchAll();
       $return['count'] = count($return['posts']);
       Zend_Loader::loadClass('Zend_Json');
       $json = Zend_Json::encode($return);
       //result encoded in JSON

       $this->getResponse()->setHeader('Content-Type', 'text/javascript');
       $this->getResponse()->setBody($json);
   }
   
   public function getAction()
   {
       $request = $this->getRequest();

       $id = $request->getParam('id');
       $this->_helper->viewRenderer->setNoRender();
       $sql = "SELECT
                id,
                uid,
                name,
                npk,
                id_privilege
               FROM master_login
               WHERE
                    id = " . $id .  "
               ORDER BY id";
       $fetch = $this->db->query($sql);
       $return['posts'] = $fetch->fetch();
       $return['count'] = count($return['posts']);
       Zend_Loader::loadClass('Zend_Json');
       $json = Zend_Json::encode($return);
       //result encoded in JSON

       $this->getResponse()->setHeader('Content-Type', 'text/javascript');
       $this->getResponse()->setBody($json);
   }

   public function viewAction()
   {
       
   }
   public function updateAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       $username = $_POST['uid'];
       $name = $_POST['name'];
       $npk = $_POST['npk'];
       $isAdmin = $_POST['is_admin'];

       if ($isAdmin == 'on')
            $isAdmin = 500;
       else
            $isAdmin = '';
       $id = $this->getRequest()->getParam('id');
       $sql = "UPDATE master_login 
       			SET 
       				uid='$username',
       				npk='$npk',
       				id_privilege = $isAdmin
                WHERE id=$id";
       $fetch = $this->db->query($sql);
       echo "{success:true}";
   }
	
   public function addAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       $username = $_POST['uid'];
       $name = $_POST['name'];
       $npk = $_POST['npk'];
       $isAdmin = $_POST['is_admin'];

       if ($isAdmin == 'on')
            $isAdmin = 500;
       else
            $isAdmin = '';
       $sql = "SELECT 
                uid
               FROM master_login
               WHERE
                    uid = '" . $username . "'";
       $fetch = $this->db->query($sql);
       $cek = $fetch->fetch();
       if ($cek['uid'] != '')
       {
       		echo '{success: false, errors: { reason: \'Username is Exists, Try another one..\' }}';
       }
       else
       {
	       $arrayInsert = array(
               "uid" => $username,
               "master_login" => $username,
               "Pass" => '',
               "Name" => $name,
               "npk" => $npk,
               "id_privilege" => $isAdmin,
           );
           $this->masterLogin->insert($arrayInsert);
	       echo "{success:true}";
       }
   }
   
	public function deleteAction()
   {
       $request = $this->getRequest();

       $id = $request->getParam('id');
       $this->_helper->viewRenderer->setNoRender();
       $sql = "SELECT
                master_login
               FROM master_login
               WHERE
                    id = " . $id .  "
               ORDER BY id";
       $fetch = $this->db->query($sql);
       $cek = $fetch->fetch();
   	   if ($cek['master_login'] == '')
       {
       		echo '{success: false, errors: { reason: \'User is not Exists!\' }}';
       }
       else
       {
	       $sql = "DELETE FROM master_login WHERE id=$id";
	       $fetch = $this->db->query($sql);
	       echo "{success:true}";
       }
   }

}

?>
