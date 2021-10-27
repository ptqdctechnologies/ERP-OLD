<?php

class Admin_MenuController extends Zend_Controller_Action
{
	private $session;
	private $request;
	private $json;
	private $userRole;
	private $roleType;
	
	private $menu;
	private $menu_priv;
	private $submenu;
	private $db;
	
	public function init()
	{
		Zend_Loader::loadClass('Zend_Json');
		$this->session = new Zend_Session_Namespace('login');
		$this->sessionID = Zend_Session::getId();
		
		$this->request = $this->getRequest();
		$this->json = $this->request->getParam('posts');	

		$this->userRole = new Admin_Models_Userrole();
		$this->roleType = new Admin_Models_Masterroletype();
		$this->menu = new Default_Models_Menu();
        $this->menu_priv = new Default_Models_MenuPrivilege();
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
		$roleID = $this->getRequest()->getParam("id_role");
		$byRole = ($this->getRequest()->getParam("by_role") == 'true') ? true : false;

		$type = $this->getRequest()->getParam("type");
		$module = $this->getRequest()->getParam("module_name");
        $jsonData = Zend_Json::decode($this->json);

        $userrole = new Admin_Models_Userrole();

        if ($jsonData[0]['delete_all'])
        {
            if ($jsonData[0]['delete_module'])
            {
                $select = $this->db->select()
                    ->from(array($this->menu->__name()))
                    ->where("module_name=?",$jsonData[0]['delete_module'])
                    ->where("leaf = 'true'");

                $m = $this->db->fetchAll($select);
                foreach($m as $k => $v)
                {
                    $link = $v['id_name'];
                    if (!$byRole)
                        $this->menu_priv->delete("menu_id = '$link' AND user_id = $userID");
                    else
                    {
                        $select2 = $this->db->select()
                            ->from(array($userrole->__name()))
                            ->where("id_role = ?",$roleID)
                            ->group(array("id_user"));

                        $users = $this->db->fetchAll($select2);
                        foreach($users as $k2 => $v2)
                        {
                            $userID = $v2['id_user'];
                            $this->menu_priv->delete("menu_id = '$link' AND user_id = $userID");
                        }
                    }
                }
            }
            else
            {
                if (!$byRole)
                    $this->menu_priv->delete("user_id = $userID");
                else
                {
                    $select2 = $this->db->select()
                        ->from(array($userrole->__name()))
                        ->where("id_role = ?",$roleID)
                        ->group(array("id_user"));

                    $users = $this->db->fetchAll($select2);
                    foreach($users as $k2 => $v2)
                    {
                        $userID = $v2['id_user'];
                        $this->menu_priv->delete("user_id = $userID");
                    }
                }
            }
        }
        else if ($jsonData[0]['disable_all'])
        {
            if ($jsonData[0]['disable_module'])
            {
                $select = $this->db->select()
                    ->from(array($this->menu->__name()))
                    ->where("module_name=?",$jsonData[0]['disable_module'])
                    ->where("leaf = 'true'");

                $m = $this->db->fetchAll($select);
                foreach($m as $k => $v)
                {
                    $link = $v['id_name'];
                    if (!$byRole)
                    {
                        $this->menu_priv->delete("menu_id = '$link' AND user_id = $userID");

                        $this->menu_priv->insert(array(
                            "menu_id" => $link,
                            "user_id" => $userID,
                            "status" => 1
                        ));
                    }
                    else
                    {
                        $select2 = $this->db->select()
                            ->from(array($userrole->__name()))
                            ->where("id_role = ?",$roleID)
                            ->group(array("id_user"));

                        $users = $this->db->fetchAll($select2);
                        foreach($users as $k2 => $v2)
                        {
                            $userID = $v2['id_user'];
                            $this->menu_priv->delete("menu_id = '$link' AND user_id = $userID");
                            $this->menu_priv->insert(array(
                                "menu_id" => $link,
                                "user_id" => $userID,
                                "status" => 1
                            ));
                        }
                    }
                }
            }
        }
        else
        {
            if ($module)
            {
                $select = $this->db->select()
                    ->from(array($this->menu->__name()))
                    ->where("module_name=?",$module)
                    ->where("leaf = 'true'");

                $m = $this->db->fetchAll($select);
                foreach($m as $k => $v)
                {
                    $link = $v['id_name'];
                    if (!$byRole)
                        $this->menu_priv->delete("menu_id = '$link' AND user_id = $userID");
                    else
                    {
                        $select2 = $this->db->select()
                            ->from(array($userrole->__name()))
                            ->where("id_role = ?",$roleID)
                            ->group(array("id_user"));

                        $users = $this->db->fetchAll($select2);
                        foreach($users as $k2 => $v2)
                        {
                            $userID = $v2['id_user'];
                            $this->menu_priv->delete("menu_id = '$link' AND user_id = $userID");
                        }
                    }
                }
            }
            foreach($jsonData as $k => $v)
            {
                if ($v['status'] == 0)
                {
                    if (!$byRole)
                    {
                        $this->menu_priv->insert(array(
                            "menu_id" => $v['menu_id'],
                            "user_id" => $userID,
                            "status" => 1
                        ));
                    }
                    else
                    {
                        $select2 = $this->db->select()
                            ->from(array($userrole->__name()))
                            ->where("id_role = ?",$roleID)
                            ->group(array("id_user"));

                        $users = $this->db->fetchAll($select2);
                        foreach($users as $k2 => $v2)
                        {
                            $userID = $v2['id_user'];
                            $this->menu_priv->insert(array(
                                "menu_id" => $v['menu_id'],
                                "user_id" => $userID,
                                "status" => 1
                            ));
                        }
                    }
                }
            }
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode(array(
            "success" => true
        )));

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

    public function menuManagerAction()
    {

    }

    public function folderAddAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $text = $this->_getParam("text");
        $id = $this->_getParam("menu_id");
        $idParent = $this->_getParam("id_parent");
        $type = $this->_getParam("type");
        $module = $this->_getParam("module_name");

        $m = new Default_Models_Menu();

        $select = $this->db->select()
            ->from(array($m->__name()))
            ->where("id_name = '$id'");

        $cek = $this->db->fetchRow($select);
        if ($cek)
        {
            $data['success'] = false;
            $data['msg'] = 'Menu ID exist!';
        }
        else
        {
            $data['success'] = true;
            $m->insert(array(
                "module_name" => $module,
                "text" => $text,
                "id_parent" => $idParent,
                "id_name" => $id,
                "type" => $type
            ));
        }
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($data));
    }


    public function menuAddAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $text = $this->_getParam("text");
        $id = $this->_getParam("menu_id");
        $idParent = $this->_getParam("id_parent");
        $type = $this->_getParam("type");
        $link = $this->_getParam("link");
        $module = $this->_getParam("module_name");

        $m = new Default_Models_Menu();

        $select = $this->db->select()
            ->from(array($m->__name()))
            ->where("id_name = '$id'");

        $cek = $this->db->fetchRow($select);
        if ($cek)
        {
            $data['success'] = false;
            $data['msg'] = 'Menu ID exist!';
        }
        else
        {
            $data['success'] = true;
            $m->insert(array(
                "module_name" => $module,
                "text" => $text,
                "id_parent" => $idParent,
                "id_name" => $id,
                "type" => $type,
                "link" => $link,
                "leaf" => 'true'
            ));
        }
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($data));
    }

    public function menuDeleteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->_getParam("id");

        $m = new Default_Models_Menu();

        $select = $this->db->select()
            ->from(array($m->__name()))
            ->where("id = $id");

        $cek = $this->db->fetchRow($select);
        if (!$cek)
        {
            $data['success'] = false;
            $data['msg'] = 'Menu not exist!';
        }
        else
        {
            $data['success'] = true;
            $m->delete("id=$id");
        }
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($data));
    }

    public function addPrivilegeByRoleAction()
    {

    }
}
?>