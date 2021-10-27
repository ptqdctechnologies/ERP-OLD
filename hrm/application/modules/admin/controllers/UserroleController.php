<?php

class Admin_UserroleController extends Zend_Controller_Action
{
	private $session;
	private $request;
	private $json;
	private $userRole;
	private $roleType;
    private $role;
	private $db;
	
	public function init()
	{
		Zend_Loader::loadClass('Zend_Json');
		$this->session = new Zend_Session_Namespace('login');
		$this->sessionID = Zend_Session::getId();
		
		$this->request = $this->getRequest();
		$this->json = $this->request->getParam('posts');	
		$this->json = str_replace("\\","",$this->json);
		$this->workflowItem = new Admin_Model_Workflowitem();
		$this->workflowItemType = new Admin_Model_WorkflowItemType();
		$this->userRole = new Admin_Model_Userrole();
		$this->roleType = new Admin_Model_Masterroletype();
		$this->role = new Admin_Model_Masterrole();
		$this->db = Zend_Registry::get('db');
		
	}
	
	public function showAction()
	{
		
	}
	

	public function viewAction()
	{
		
	}
	
	public function listAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		
		$roleName = $this->request->getParam('role_name');
		$group = $this->request->getParam('groupbyuser');
		$roleID = $this->request->getParam('role_id');
		
		if (!isset($roleName))
			$roleName = 'project';
		if (isset($roleID))
			$roleName = '';
		if (isset($group))
			$group = ' GROUP BY mr.id_user ';
		$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'mr.id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
		$return = $this->userRole->getRoleType($roleName,$sort,$dir,$limit,$offset,'','',$roleID,$group);
		$json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function listroletypeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		
		$name = $this->request->getParam('name');
		$status = $this->request->getParam('status');
		
		if ($status != '')
		{
			if ($name != '')
				$status = " AND active=$status";
			else
				$status = "WHERE active=$status";
		}
		
		if ($name == '')
		{
			$return['posts'] =  $this->roleType->getAll();
		}
		else
		{
			$where = $this->db->quoteInto('WHERE display_name LIKE ?', '%' . $name . '%');
			$return['posts'] = $this->roleType->getAll($where . $status);
			
		}
		$json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function listuserAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		
		
		$name = $this->request->getParam('name');
		
		$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'uid';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
		
		$return = $this->userRole->getAllUser($name,$sort,$dir,$limit,$offset);
		$json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function listuserroleAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		
		
		$userID = $this->request->getParam('id');
		if ($userID == '')
			return;
		$return = $this->userRole->getUserRoleByUserID($userID);
		$json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function adduserroleAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$userID = $this->request->getParam('id');
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($this->json);
		$jsonData['id_user'] = $userID;
		$insertArray = $this->userRole->createUserRole($jsonData,'');
		$return = array('success' => true,'message' => 'Created user Role','posts' => $insertArray);
		$json = Zend_Json::encode($return);
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function updateuserroleAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
        $userID = $request->getParam('id');
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($request->getParam('posts'));
		$updateArray = $this->userRole->updateUserRole($jsonData,'');
		$return = array('success' => true,'message' => 'Updated User Role','posts' => $updateArray);
		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function deleteuserroleAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($request->getParam('posts'));
		$result = $this->userRole->deleteUserRole($jsonData);
//		if ($result)
			$return = array('success' => true,'message' => 'Deleted User Role','posts' => array());
//		else
//			$return = array('false' => true,'message' => 'You dont have Delete privilege!','posts' => array());
		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function viewroletypeAction()
	{
		
	}
	
	public function addroletypeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($this->json);
		
		$result = $this->roleType->fetchRow('id='.$jsonData['id_role']);
		
		if($result != '' || isset($result) )
		{
			$return = array('success' => false,'message' => 'ID Role is exists!');
		}
		else
		{
			if ($jsonData['active'] == 'on')
				$jsonData['active'] = 1;
			else
				$jsonData['active'] = 0;
			$jsonData['id'] = $jsonData['id_role'];
			unset ($jsonData['id_role']);
			$lastID = $this->roleType->insert($jsonData);
			$jsonData['id_role'] = $lastID;
			unset ($jsonData['id']);
			$return = array('success' => true,'message' => 'Created Role Type','posts' => $jsonData);
		}
		$json = Zend_Json::encode($return);
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function updateroletypeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($this->json);
		$idRole = $jsonData['id_role'];
		unset($jsonData['id_role']);
		$this->roleType->update($jsonData,'id='.$idRole);
		$jsonData['id_role'] = $idRole;
		$return = array('success' => true,'message' => 'Updated Role Type','posts' => $jsonData);
		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function deleteroletypeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($this->json);
		$idRole = $jsonData;
		$this->roleType->delete('id='.$idRole);
		$return = array('success' => true,'message' => 'Deleted User Role','posts' => array());

		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}

    public function getmyteamAction()
	{
		$this->_helper->viewRenderer->setNoRender();
        $myRoleId = $this->getRequest()->getParam('id');
        $prjKode = $this->getRequest()->getParam('prj_kode');

        if ($prjKode != 'invalid')
        {
            $result = $this->role->getMyTeamByProject($myRoleId,$prjKode);

            if ($result)
            {
                foreach($result as $key => $val)
                {
                    $id_role = $val['id_role'];
                    $tmp = array();
                    $type = $this->roleType->fetchAll("id = $id_role")->toArray();
                    foreach ($type as $key2 => $val2)
                    {
                        $tmp[] = $val2['display_name'];
                    }
                    $roleName = implode(',',$tmp);
                    $result[$key]['role_name'] = $roleName;
                    $ldapdir = new Default_Models_Ldap();
                    $account = $ldapdir->getAccount($val['uid']);
                    $result[$key]['name'] = $account['displayname'][0];
                    $result[$key]['user_id'] = $result[$key]['id'];
                    $result[$key]['id'] = $key + 1;
                }
            }
        }
        else
        {
            //Untuk diary yang gak ada project code/site code.. fetching dari email.
            $sql = "SELECT * FROM
                        projectmanagement_diary
                    WHERE
                        prj_kode is null
                        AND
                        sit_kode is null
                        AND
                        (tgl_sent != '' OR uniqueID != '')
                    GROUP BY uid
                    ";
            $fetch = $this->db->query($sql);
            $hasil = $fetch->fetchAll();

            if ($hasil)
            {
                foreach ($hasil as $key => $val)
                {
                    $ldapdir = new Default_Models_Ldap();
                    $account = $ldapdir->getAccount($val['uid']);
                    $result[$key]['name'] = $account['displayname'][0];
                    $result[$key]['user_id'] = $val['uid'];
                    $result[$key]['id'] = $key + 1;
                }
            }
        }
        $return['posts'] = $result;
        $return['count'] = count($result);
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
        
    }
}

?>