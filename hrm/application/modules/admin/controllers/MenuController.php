<?php

class Admin_MenuController extends Zend_Controller_Action
{
	private $session;
	private $request;
	private $json;
	private $userRole;
	private $roleType;
	
	private $menu;
	private $submenu;
	private $db;
	
	public function init()
	{
		Zend_Loader::loadClass('Zend_Json');
		$this->session = new Zend_Session_Namespace('login');
		$this->sessionID = Zend_Session::getId();
		
		$this->request = $this->getRequest();
		$this->json = $this->request->getParam('posts');	

		$this->userRole = new Admin_Model_Userrole();
		$this->roleType = new Admin_Model_Masterroletype();
		$this->menu = new Default_Models_Menu();
		$this->submenu = new Default_Models_SubMenu();
		
		$this->db = Zend_Registry::get('db');
		
	}
	
	public function showAction()
	{
		
	}
	

	public function viewAction()
	{
		
	}

    public function viewsubmenuAction()
	{

	}

	public function listuserByRoleAction()
	{
		
	}
	
	public function listmenuAction()
	{
		
	}
	
	public function submitmenuprivilegeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$userID = $this->getRequest()->getParam("id_user");
		
		$type = $this->getRequest()->getParam("type");
		
		if (substr($this->json,0,1) != '[')
			{
				$this->json = "[" . $this->json . "]";
			}
		$jsonData = Zend_Json::decode($this->json);
		
		if ($type == '')
		{	
			if (count($jsonData) > 0)
			{
				$this->menu->delete("user_id=". $userID);
				if ($jsonData[0]['delete-all'] == '')
				{		
					foreach ($jsonData as $key => $val)
					{
						$val['user_id'] = $userID;
						$this->menu->insert($val);
					}
				}
			}
		}
		else
		{
			$role = $this->getRequest()->getParam('role');
			if ($role == '')
			{
				echo '{success:false,message:\'Invalid Role\'}';
				die();
			}
			$menu = $this->menu->getMenu($role);
			$id = array();
			$isLeaf = false;
			$param = array($isLeaf,$id);
			array_walk_recursive($menu, array(&$this,'setMenuByRole'),&$param);
			$userID = $this->getRequest()->getParam('id_user');
			$this->menu->delete("user_id=". $userID);
			foreach($param[1] as $key => $val)
			{
				$insertArray['user_id'] = $userID;
				$insertArray['menu_id'] = $val;
				$insertArray['status'] = 1;
				$this->menu->insert($insertArray);
			}
		}
		echo '{success:true}';
	}

    public function submitsubmenuprivilegeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$userID = $this->getRequest()->getParam("id_user");

		$type = $this->getRequest()->getParam("type");

		if (substr($this->json,0,1) != '[')
			{
				$this->json = "[" . $this->json . "]";
			}
		$jsonData = Zend_Json::decode($this->json);

        foreach($jsonData as $k => $v)
        {
            $subs = $v['menu_id'];
            unset($jsonData[$k]['menu_id']);
            $jsonData[$k]['submenu_id'] = $subs;
        }

		if ($type == '')
		{
			if (count($jsonData) > 0)
			{
				$this->submenu->delete("user_id=". $userID);
				if ($jsonData[0]['delete-all'] == '')
				{
					foreach ($jsonData as $key => $val)
					{
						$val['user_id'] = $userID;
						$this->submenu->insert($val);
					}
				}
			}
		}
		else
		{
			$role = $this->getRequest()->getParam('role');
			if ($role == '')
			{
				echo '{success:false,message:\'Invalid Role\'}';
				die();
			}
			$menu = $this->submenu->getSubMenu($role);
			$id = array();
			$isLeaf = false;
			$param = array($isLeaf,$id);
			array_walk_recursive($menu, array(&$this,'setMenuByRole'),&$param);
			$userID = $this->getRequest()->getParam('id_user');
			$this->submenu->delete("user_id=". $userID);
			foreach($param[1] as $key => $val)
			{
				$insertArray['user_id'] = $userID;
				$insertArray['submenu_id'] = $val;
				$insertArray['status'] = 1;
				$this->submenu->insert($insertArray);
			}
		}
		echo '{success:true}';
	}

	private function setMenuByRole(&$item,$key,&$data)
	{
	
		if ($key == 'leaf' && $item)
		{
			$data[0] = true;
		}
    	if ($key == 'id' && $data[0])
		{
			$data[1][] = $item;
			$data[0] = false;
		}
    }
	
	
}
?>