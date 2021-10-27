<?php

class Admin_WorkflowController extends Zend_Controller_Action
{
	
	private $session;
	private $request;
	private $json;
	private $workflow;
	private $workflowTrans;
	private $workflowItem;
	private $workflowStructure;
	private $workflowGeneric;
	private $workflowItemType;
    private $masterRole;
	private $const;
	private $token;
	private $mail;
	private $utility;
	private $db;
	
	public function init()
	{
		Zend_Loader::loadClass('Zend_Json');
		$this->session = new Zend_Session_Namespace('login');
		$this->sessionID = Zend_Session::getId();
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->workflowHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('workflow');
        $this->mail = Zend_Controller_Action_HelperBroker::getStaticHelper('mail');
        $this->utility = Zend_Controller_Action_HelperBroker::getStaticHelper('utility');
		
        $this->const = Zend_Registry::get('constant');
        $this->db = Zend_Registry::get('db');
		$this->request = $this->getRequest();
		$this->json = $this->request->getParam('posts');	
		
		$this->workflow = new Admin_Model_Workflow();
		$this->workflowTrans = new Admin_Models_Workflowtrans();
		$this->workflowStructure = new Admin_Model_Workflowstructure();
		$this->workflowGeneric = new Admin_Model_Workflowgeneric();
		$this->workflowItem = new Admin_Model_Workflowitem();
		$this->workflowItemType = new Admin_Model_WorkflowItemType();
        $this->masterRole = new Admin_Model_Masterrole();
		
	}
	
	public function workflowitemAction()
	{
		$this->view->userID = $this->session->idUser;
	}

    public function workflowitemgenericAction()
	{
		$this->view->userID = $this->session->idUser;
	}
	
	public function showworkflowitemAction()
	{
		$this->view->userID = $this->session->idUser;
        $goto = $this->request->getParam('goto');
        if ($goto != '')
        {
            $this->view->goto;
        }
	}
	
	public function workflowtypeAction()
	{
		$this->view->userID = $this->session->idUser;
	}
	
	
	public function listworkflowitemAction()
	{
		$this->_helper->viewRenderer->setNoRender();

        $txtsearch = $this->getRequest()->getParam("search");
        $option = $this->getRequest()->getParam("option");
        $search = null;

        if ($txtsearch == "" || $txtsearch == null)
        {
            $search = '';
        }else if ($txtsearch != null && $option == 1)
        {
            $search = "w.name like '%$txtsearch%'";
        }else if ($txtsearch != null && $option == 2)
        {
            $search = "wt.name like '%$txtsearch%'";
        }else if ($txtsearch != null && $option == 3)
        {
            $search = "w.prj_kode like '%$txtsearch%' ";
        }


        $userID = $this->request->getParam('userid');
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'w.name';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
		$generic = $this->getRequest()->getParam("generic");

        if ($generic)
           $generic = true;
        else
            $generic = false;

        if ($prjKode == '' && $sitKode == '')
        {
            $return['posts'] = $this->workflowItem->listWorkflowItem($sort,$dir,$limit,$offset,$generic,$search);
        }
        else
        {
             $return['posts'] = $this->workflowItem->listWorkflowItem($sort,$dir,$limit,$offset,$generic,$search);
        }
        if (!$generic)
            $return['count'] =$this->workflowItem->fetchAll()->count();
        else
        {
            $sql = $this->db->select()
                    ->from(array('w' => 'workflow_item'))
                    ->where("w.generic = 1");
//                    ->group(array("w.workflow_item_type_id","w.prj_kode"));
            $result = $this->db->fetchAll($sql);
            $return['count'] = count($result);
        }
        
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function listworkflowitemtypeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$type = $this->getRequest()->getParam("type");
		$generic = $this->getRequest()->getParam("generic");
        if ($generic)
            $where = "generic = 1";
        if ($type != 'all')
        {
            $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
            $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
            $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'name';
            $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

            
            $return['posts'] = $this->workflowItemType->fetchAll($where,array($sort . ' ' . $dir),$limit,$offset)->toArray();
        }
        else
            $return['posts'] = $this->workflowItemType->fetchAll($where)->toArray();


        $from = $this->getRequest()->getParam("from");
        if ($from = 'showprocessdocument')
        {
            $return['posts'][] = array(
                "name" => "ARF With BT",
                "workflow_item_type_id" => 999,
                "generic" => 0
            );
        }

        $return['count'] = $this->workflowItemType->fetchAll($where)->count();
		$json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
		
	}
	
	public function addworkflowitemAction()
	{
		$prjKode = $this->request->getParam('prj_kode');
		$sitKode = $this->request->getParam('sit_kode');
		$this->_helper->viewRenderer->setNoRender();
		Zend_Loader::loadClass('Zend_Json');

        $generic = $this->request->getParam('generic');
        if (!$generic)
        {
            $jsonData = Zend_Json::decode($this->json);
            $jsonData['prj_kode'] = $prjKode;
            $insertArray = $this->workflowItem->createWorkflowItem($jsonData,'');
            $fetch = $this->workflowItemType->getByID($jsonData['workflow_item_type_id']);
            $insertArray['workflow_item_type_name'] = $fetch['name'];
            $return = array('success' => true,'message' => 'Created Workflow','posts' => $insertArray);
        }
        else
        {
            $posts = $this->request->getParam('posts');
            $etc = $this->request->getParam('etc');

            $posts = Zend_Json::decode($posts);
            $etc = Zend_Json::decode($etc);

            if (count($posts) > 0)
            {
                foreach($posts as $key => $val)
                {
                    $arrayInsert = array(
                        "workflow_item_type_id" => $etc[0]["workflow_item_type_id"],
                        "name" => $etc[0]["name"],
                        "description" => $etc[0]['desc'],
                        "prj_kode" => $val['Prj_Kode'],
                        "generic" => 1
                    );
                    $this->workflowItem->insert($arrayInsert);
                }
            }
            else
            {
                $arrayInsert = array(
                    "workflow_item_type_id" => $etc[0]["workflow_item_type_id"],
                    "name" => $etc[0]["name"],
                    "description" => $etc[0]['desc'],
                    "prj_kode" => '',
                    "generic" => 1
                );
                $this->workflowItem->insert($arrayInsert);
            }
            $return = array('success' => true,'message' => 'Created Workflow');
        }
        $json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function updateworkflowitemAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
        $userID = $request->getParam('userid');
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($request->getParam('posts'));
		$updateArray = $this->workflowItem->updateWorkflowItem($jsonData,'');
		$return = array('success' => true,'message' => 'Updated Workflow','posts' => $updateArray);
		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function deleteworkflowitemAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($request->getParam('posts'));
		$result = $this->workflowItem->deleteWorkflowItem($jsonData);
//		if ($result)
			$return = array('success' => true,'message' => 'Deleted Workflow','posts' => array());
//		else
//			$return = array('false' => true,'message' => 'You dont have Delete privilege!','posts' => array());
		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function addworkflowitemtypeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($this->json);

        if ($jsonData['generic'] == "on")
            $jsonData['generic'] = 1;
        else
            $jsonData['generic'] = 0;
		$id = $this->workflowItemType->insert($jsonData);
		$jsonData['workflow_item_type_id'] = $id;
		$return = array('success' => true,'message' => 'Created Workflow Type','posts' => $jsonData);
		$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function updateworkflowitemtypeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($request->getParam('posts'));
		$id = $jsonData['workflow_item_type_id'];
		unset($jsonData['workflow_item_type_id']);


        if ($jsonData['generic'] == "on")
            $jsonData['generic'] = 1;
        else
            $jsonData['generic'] = 0;
		$this->workflowItemType->update($jsonData,"workflow_item_type_id=$id");
		$jsonData['workflow_item_type_id'] = $id;
		$return = array('success' => true,'message' => 'Updated Workflow Type','posts' => $jsonData);
		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function deleteworkflowitemtypeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = $request->getParam('posts');
		$result = $this->workflowItemType->delete("workflow_item_type_id=$jsonData");
		$return = array('success' => true,'message' => 'Deleted Workflow Type');
		$json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	
	public function workflowprocessAction()
	{
		
	}
	
	public function addworkflowprocessAction()
	{
		$workflow_item_id = $this->request->getParam('workflow_item_id');
		
		
		$level = $this->workflowStructure->getTreeRouteGroup($workflow_item_id);
		if ($level)
		{
		
			$ldap = new Default_Models_Ldap();	
			$result = array();
			foreach ($level as $key => $val)
			{
				$people = $this->workflowStructure->getTreeRoute($workflow_item_id,$val['level']);
				foreach($people as $key2 => $val2)
				{
					if ($val2['uid'] != '')
					{
						$account = $ldap->getAccount($val2['uid']);
                        if ($account == null)
                        {
                            $sql = "SELECT Name FROM master_login WHERE uid='" . $val2['uid'] . "'";
                            $fetch = $this->db->query($sql);
                            $fetch = $fetch->fetch();
                            if ($fetch)
                            {
                                $name = $fetch['Name'];
                                $result[$key][$key2]['displayname'] = $name;
                            }
                        }
                        else
    						$result[$key][$key2]['displayname'] = $account['displayname'][0];
						$result[$key][$key2]['master_role_id'] = $people[$key2]['master_role_id'];
					}
				}
			}
		}
		
		$this->view->workflow_item_id = $workflow_item_id;	
		$this->view->dataWorkflow = $result;
	}
	
	public function listuserroleAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		
		$roleName = $this->request->getParam('role_name');
		$roleID = $this->request->getParam('role_id');
		
		if (!isset($roleName) && $roleID == "")
			$roleName = 'project';
		
		$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'mr.id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
		$userRole = new Admin_Model_Userrole();
        if ($roleName != "")
		    $return = $userRole->getRoleType($roleName,$sort,$dir,$limit,$offset);
		elseif ($roleID != "")
    		$return = $userRole->getRoleType("",$sort,$dir,$limit,$offset,'','',$roleID);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}

    private function insertWorkflowStructure($id_workflow='',$person='')
    {
		$workflowStruct = new Admin_Model_Workflowstructure();
        $i = 0;
        if ($person == '')
            return false;
        foreach($person as $key => $val)
        {
            foreach ($val as $key2 => $val2)
            {
                if ($val2 != '' && isset($val2))
                {
                    $dataArray["level"] = $i;
                    $dataArray["master_role_id"] = $val2;
                    $dataArray["workflow_item_id"] = $id_workflow;
                    $workflowStruct->insert($dataArray);
                }
            }
            $i++;
        }
    }
	
	public function submitworkflowprocessAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$id_workflow = $this->request->getParam('workflow_item_id');
		$id_person = $_POST['id_person'];
		$dataArray = array();
		$i = 0;
		$workflowStruct = new Admin_Model_Workflowstructure();
//		$workflowStruct->delete("workflow_item_id = $id_workflow");
        $sql = "SELECT MAX(level) as jumlah FROM workflow_structure WHERE workflow_item_id = $id_workflow";
        $fetch = $this->db->query($sql);
        $cekExist = $fetch->fetchAll();

        if ($cekExist)
        {
            $countData = count($id_person);
            if ($countData > intval($cekExist['jumlah']))
            {
                $workflowStruct->delete("workflow_item_id = $id_workflow");
                $this->insertWorkflowStructure($id_workflow,$id_person);
            }
            else
            {
                foreach($id_person as $key => $val)
                {
                    if ($val != '' && isset($val))
                    {

                        $sql = "SELECT master_role_id FROM workflow_structure WHERE level = $i AND workflow_item_id = $id_workflow";
                        $fetch = $this->db->query($sql);
                        $cek = $fetch->fetchAll();
                        if ($cek)
                        {
                            $cekArray = array();
                            $hasilCek = array();

                            foreach ($val as $key2 => $val2)
                            {
                                $cekArray[$key2]['master_role_id'] = $val2;
                            }
                            $hasilCek = $this->utility->array_compare($cekArray,$cek);
                            if ($hasilCek)
                            {
                               if (is_array($hasilCek[0]))
                               {
                                    $dataArray["level"] = $i;
                                    $dataArray["master_role_id"] = $val2;
                                    $dataArray["workflow_item_id"] = $id_workflow;
                                    $workflowStruct->insert($dataArray);
                               }
                               if (is_array($hasilCek[1]))
                               {
                                    foreach($hasilCek[1] as $key3 => $val3)
                                    {
                                        $del = $val3['master_role_id'];
                                        $workflowStruct->delete("level = $i AND workflow_item_id = $id_workflow AND master_role_id = $del");
                                    }
                               }
                            }
                        }
                    }


                    $i++;
                }
            }
		}
        else
        {
             $this->insertWorkflowStructure($id_workflow,$id_person);
        }
//		$workflow = new Admin_Model_Workflow();
//		$workflow->addWorkflow($dataArray,$id_workflow);
		$this->_helper->redirector('menu','index','default');
	}
	
	public function addworkflowrouteAction()
	{
		
	}
	
	public function listworkflowrouteAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$workflowStruct = new Admin_Model_Workflowstructure();
		$ldap = new Default_Models_Ldap();
		
		$id_workflow = $this->request->getParam('workflow_item_id');
		$isRouted = $this->request->getParam('routed');
		$node = $this->request->getParam('node');
		if ($node != 'src')
			return;
		$level = $workflowStruct->getTreeRouteGroup($id_workflow);
        if (!$isRouted)
		{
			$temp = array();
			$indeksArray = 0;
			foreach($level as $key => $val)
			{
				if ($key == (count($level)-1))
					continue;
				$result = $workflowStruct->getTreeRoute($id_workflow,$val['level']);
				if (count($result) >0)
				{
					foreach ($result as $key2 => $val2)
					{
                        $ret = '';
						$cek = $workflowStruct->cekInWorkflow($id_workflow,$val2['id'],$ret);
                        if ($cek)
                        {
                            if ($ret)
                            {
                                if ($ret['next'] != 0 && $ret['next'] != '')
                                {
                                    $cekStruct = $workflowStruct->fetchRow("id = " . $ret['next']);
                                    if ($cekStruct)
                                        continue;
                                }
                                if ($ret['prev'] != 0 && $ret['prev'] != '')
                                {
                                    $cekStruct = $workflowStruct->fetchRow("id = " . $ret['prev']);
                                    if ($cekStruct)
                                        continue;
                                }
                                $cek = false;
                            }
                        }
						if ($key == 0)
						{
							$temp[$indeksArray]['text'] = "Start";
							$temp[$indeksArray]['id'] = "start";
						}
						elseif ($key < (count($level)-1))
						{
							$temp[$indeksArray]['text'] = "Next-" . ($key);
							$temp[$indeksArray]['id'] = "next-" . ($key);
						}		
						$name = $ldap->getAccount($result[$key2]['uid']);
						if ($name == null)
                        {
                            $sql = "SELECT Name FROM master_login WHERE uid='" . $val2['uid'] . "'";
                            $fetch = $this->db->query($sql);
                            $fetch = $fetch->fetch();
                            if ($fetch)
                            {
                                $name = $fetch['Name'];
                                $temp2 = array("text" => $name, "id" => $val2['id'], "leaf" => true,"cls" => "workflow-lower","uid" => $result[$key2]['uid']);
                            }
                        }
                        else
						    $temp2 = array("text" => $name['displayname'][0], "id" => $val2['id'], "leaf" => true,"cls" => "workflow-lower","uid" => $result[$key2]['uid']);
						$temp[$indeksArray]['children'][] = $temp2;
					}
					if (!$cek)
						$indeksArray++;
				}
			}
			if (count($temp) == 1)
				{
					foreach ($temp as $indeks => $value)
					{
						$temporer = $temp[$indeks];
					}
					unset($temp);
					$temp = $temporer;
				}	
		}
		else
		{
			$temp = array();
			foreach($level as $key => $val)
			{
				if ($key == 0)
					continue;
				elseif ($key == count($level)-1)
				{
					$temp[$key-1]['text'] = "End";
					$temp[$key-1]['id'] = "end";
					$temp[$key-1]['allowDrop'] = false;
				}
				else
				{
					$temp[$key-1]['text'] = "Next-" . ($key);
					$temp[$key-1]['id'] = "next-" . ($key);
					$temp[$key-1]['allowDrop'] = false;
				}

				$result = $workflowStruct->getTreeRoute($id_workflow,$val['level']);
				foreach ($result as $key2 => $val2)
				{
                    $end = false;
					$name = $ldap->getAccount($result[$key2]['uid']);
                    if ($name == null)
                    {
                        $sql = "SELECT Name FROM master_login WHERE uid='" . $result[$key2]['uid'] . "'";
                        $fetch = $this->db->query($sql);
                        $fetch = $fetch->fetch();
                        if ($fetch)
                        {
                            $name = $fetch['Name'];
                        }
                    }
                    else
                        $name = $name['displayname'][0];
					$theWorkflow = $this->workflow->fetchRow("workflow_structure_id = " . $val2['id'] . " AND master_role_id = " . $val2['master_role_id'] . " AND workflow_item_id = " . $val2['workflow_item_id']);
					if ($theWorkflow)
                    {
                        if ($theWorkflow['is_end'] == 1)
                        {
                            $end = true;
                        }
                    }
                    else
                    {
                        if ($temp[$key-1]['text'] == "End")
                        {
                            $end = true;
                        }
                    }
//                    echo $temp[$key-1]['text'];
                    $child = $this->workflowStructure->getChildInWorkflow($id_workflow,$val2['id']);
					if (count($child)>0)
					{
						$children = array();
						foreach($child as $indeks => $value)
						{
							mt_srand((double)microtime()*1000000);
							$childId  = mt_rand(0, 255) . "-" .$value['workflow_structure_id'];
//							$childId  = $value['workflow_structure_id'];
							$childName = $ldap->getAccount($value['uid']);
                            if ($childName == null)
                            {
                                $sql = "SELECT Name FROM master_login WHERE uid='" . $value['uid'] . "'";
                                $fetch = $this->db->query($sql);
                                $fetch = $fetch->fetch();
                                if ($fetch)
                                {
                                    $childName = $fetch['Name'];
                                    $children[] = array("text" => $childName, "id" => $childId, "leaf" => true, "iconCls" => "workflow-lower-leaf","uid" => $value['uid']);
                                }
                            }
                            else
							    $children[] = array("text" => $childName['displayname'][0], "id" => $childId, "leaf" => true, "iconCls" => "workflow-lower-leaf","uid" => $value['uid']);
						}
                        if (!$end)
						    $temp2 = array("text" => $name, "id" => $val2['id'], "cls" => "workflow-upper","children" => $children,"uid" => $result[$key2]['uid']);
						else
                            $temp2 = array("text" => $name, "id" => $val2['id'], "cls" => "workflow-upper","children" => $children,"checked" => false, "qtip" => "Please check the checkbox if this person is the Executor for Document.","uid" => $result[$key2]['uid']);

                        $temp[$key-1]['children'][] = $temp2;
					}
					else
					{
                        if (!$end)
						    $temp2 = array("text" => $name, "id" => $val2['id'], "cls" => "workflow-upper","leaf" => false,"uid" => $result[$key2]['uid']);
                        else
                            $temp2 = array("text" => $name, "id" => $val2['id'], "cls" => "workflow-upper","leaf" => false,"checked" => false, "qtip" => "Please check the checkbox if this person is the Executor for Document.","uid" => $result[$key2]['uid']);
                        $temp[$key-1]['children'][] = $temp2;
					}
				}
					
			}
		}
		$json = Zend_Json::encode($temp);
		if (substr($json,0,1) != '[')
			$json = '[' . $json . ']';
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}
	
	public function savetreeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		
		$workflow_item_id = $this->request->getParam('workflow_item_id');
		$result = $this->workflow->fetchAll("workflow_item_id = $workflow_item_id");

        $isExist = false;

		if ($result)
        {
			$this->workflow->delete("workflow_item_id = $workflow_item_id");
            $isExist = true;
        }
		Zend_Loader::loadClass('Zend_Json');
		$jsonData = Zend_Json::decode($this->request->getParam('posts'));
		
		$isi = $jsonData['children'];
		if (count($jsonData['children']) > 0)
		{
			foreach ($isi as $key=> $val)
			{
				
				if ($key <= (count($jsonData['children']) -1))
				{
					if ($val['id'] == 'next-' . ($key+1) || $val['id'] == 'end')
					{
						foreach($val['children'] as $key2 => $val2)
						{
							if (count($val2['children']) > 0)
							{
								$idParent = $val2['id'];
                                $uidParent = $val2['uid'];
								foreach ($val2['children'] as $key3 => $val3)
								{
									$idChild = $val3['id'];
                                    $uidChild = $val3['uid'];
									if (strpos($idChild,"-"))
									{
										$split = explode("-",$idChild);
										$idChild = $split[1];
									}
									$result = $this->workflowStructure->fetchRow("id = $idChild")->toArray();
									if (!$result)
										continue;
									$insertArray = array();
                                    $role = $this->masterRole->fetchRow("id = " . $result['master_role_id'])->toArray();
									if ($result['level'] == 0)
									{
										$insertArray['workflow_item_id'] = $workflow_item_id;
										$insertArray['prev'] = 0;
										$insertArray['next'] = $idParent;
										$insertArray['uid_prev'] = '';
										$insertArray['uid_next'] = $uidParent;
                                        $insertArray['uid'] = $uidChild;
                                        $insertArray['is_start'] = 1;
										$insertArray['is_end'] = 0;
										$insertArray['workflow_structure_id'] = $idChild;
										$insertArray['master_role_id'] = $result['master_role_id'];
										$insertArray['prj_kode'] = $role['prj_kode'];
										$this->workflow->insert($insertArray);
									}
									else
									{
										$insertArray['workflow_item_id'] = $workflow_item_id;
										$cek = $this->workflow->fetchAll("next = $idChild")->toArray();
										if ($cek)
										{
											foreach ($cek as $indeks => $value)
											{
												$insertArray['prev'] = $value['workflow_structure_id'];
												$insertArray['next'] = $idParent;
												$insertArray['is_start'] = 0;
//												if ($val['id'] == 'end')
//													$insertArray['is_end'] = 1;
//												else
                                                $insertArray['is_end'] = 0;
                                                if ($value['uid'] == '')
                                                    $insertArray['uid_prev'] = '';
                                                else
                                                {
                                                    $rolePrev = $this->masterRole->getUserFromRoleId($value['master_role_id']);
                                                    $insertArray['uid_prev'] = $rolePrev['uid'];
                                                }
                                                $insertArray['uid_next'] = $uidParent;
                                                $insertArray['uid'] = $uidChild;
												$insertArray['workflow_structure_id'] = $idChild;
												$insertArray['master_role_id'] = $result['master_role_id'];
										        $insertArray['prj_kode'] = $role['prj_kode'];
										        $this->workflow->insert($insertArray);
											}
										}
										else
										{	
											$insertArray['prev'] = 0;
											$insertArray['next'] = $idParent;
                                            $insertArray['uid_prev'] = '';
                                            $insertArray['uid_next'] = $uidParent;
                                            $insertArray['uid'] = $uidChild;
											$insertArray['is_start'] = 0;
												if ($val['id'] == 'end')
													$insertArray['is_end'] = 1;
												else
													$insertArray['is_end'] = 0;
											$insertArray['workflow_structure_id'] = $idChild;
											$insertArray['master_role_id'] = $result['master_role_id'];
										        $insertArray['prj_kode'] = $role['prj_kode'];
										    $this->workflow->insert($insertArray);
										
										}
									}
								}
								if ($val['id'] == 'end')
								{
									foreach ($val2['children'] as $key3 => $val3)
									{
										$idChild = $val3['id'];
                                        $uidChild = $val3['uid'];
										if (strpos($idChild,"-"))
										{
											$split = explode("-",$idChild);
											$idChild = $split[1];
										}
										$result = $this->workflowStructure->fetchRow("id = $idChild")->toArray();
										$resultParent = $this->workflowStructure->fetchRow("id = $idParent")->toArray();
                                        $role = $this->masterRole->fetchRow("id = " . $resultParent['master_role_id'])->toArray();
										if (!$result || !$resultParent)
											continue;
										$insertArray = array();	
										$insertArray['workflow_item_id'] = $workflow_item_id;
										$insertArray['prev'] = $idChild;
										$insertArray['next'] = 0;
                                        $insertArray['uid_prev'] = $uidChild;
                                        $insertArray['uid_next'] = '';
                                        $insertArray['uid'] = $uidParent;
										$insertArray['prj_kode'] = $role['prj_kode'];
										$insertArray['is_start'] = 0;
										$insertArray['is_end'] = 1;
                                        if ($val2['checked'] == true || $val2['checked'] == "1")
                                        {
                                            $insertArray['is_executor'] = 1;
                                            $insertArray['is_final'] = 0;
                                        }
                                        else
                                        {
                                            $insertArray['is_executor'] = 0;
                                            $insertArray['is_final'] = 1;
                                        }
										$insertArray['workflow_structure_id'] = $idParent;
										$insertArray['master_role_id'] = $resultParent['master_role_id'];
										$this->workflow->insert($insertArray);
									}
								}
							}
						}
					}
				}
				
			}
		}

        //delete workflow from memcache
        $memcacheWork = Zend_Registry::get('MemcacheWorkflow');
        $memcacheWork->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            array('WORKFLOW')
        );

        if ($isExist)
        {
            $fetch = $this->db->query("SELECT a.* FROM workflow_trans a LEFT JOIN workflow b ON a.workflow_id = b.workflow_id WHERE b.workflow_id IS NULL AND a.workflow_item_id = $workflow_item_id");
            $cekTrans = $fetch->fetchAll();
            //Iterasi satu per satu transaksi workflow yang tidak ada workflow_id
            foreach ($cekTrans as $key => $val)
            {
                $uid = $val['uid'];
                $workflow_structure_id = $val['workflow_structure_id'];
                $workflow_trans_id = $val['workflow_trans_id'];
                if ($workflow_structure_id == '' || $workflow_item_id == '')
                {
                    continue;
                }
                $fetch = $this->db->query("SELECT * FROM workflow where uid = '$uid' AND workflow_item_id = $workflow_item_id LIMIT 1");
                $cekWorkNow = $fetch->fetch();
                if ($cekWorkNow)
                {
                    $fetch2 = $this->db->query("select * FROM workflow_trans where uid = '$uid' AND workflow_item_id = $workflow_item_id AND workflow_trans_id = $workflow_trans_id");
                    $fetch2 = $fetch2->fetch();

                    $newUidPrev = $cekWorkNow['uid_prev'];

                    if ($fetch2['approve'] != $this->const['DOCUMENT_REJECT'])
                    {
                        $newUidNext = $cekWorkNow['uid_next'];
                    }
                    else
                    {
                        $trano = $fetch2['item_id'];
                        $sql = "SELECT * FROM workflow_trans WHERE item_id = '$trano' AND approve in (100,150) ORDER BY date ASC LIMIT 1";
                        $fetch3 = $this->db->query($sql);
                        if ($fetch3)
                        {
                            $submit = $fetch3->fetch();
                            $next = $submit['uid'];
                            $newUidNext = $next;
                        }
                        else
                        {
                            $workid = $workflow_item_id;
                            $sql = "SELECT * FROM workflow WHERE workflow_item_id = $workid WHERE is_start = 1";
                            $fetch3 = $this->db->query($sql);
                            $submit = $fetch3->fetch();
                            $next = $submit['uid'];
                            $newUidNext = $next;
                        }
                    }
                    $newWorkflow_structure_id = $cekWorkNow['workflow_structure_id'];
                    $newWorkflow_id = $cekWorkNow['workflow_id'];
                    $this->db->query("UPDATE workflow_trans SET uid_next = '$newUidNext', uid_prev = '$newUidPrev', workflow_id = $newWorkflow_id, workflow_structure_id = $newWorkflow_structure_id where uid = '$uid' AND workflow_item_id = $workflow_item_id AND workflow_trans_id = $workflow_trans_id");
                }

            }
            echo "{success: true}";
        }
        else
            echo "{success: true}";
		
	}
	
	public function approveAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$docsID = $this->getRequest()->getParam("trans");
		$uid = $this->getRequest()->getParam("user");
		$userID = $this->getRequest()->getParam("user_id");
		$comment = $this->getRequest()->getParam("comment");
		$uid_next = $this->getRequest()->getParam('uid_next');
        $workflow_item_id = $this->getRequest()->getParam('workflow_item_id');
        $workflow_id = $this->getRequest()->getParam('workflow_id');
        $workflow_item_type_id = $this->getRequest()->getParam('workflow_item_type_id');
        if ($uid_next != '' && $workflow_id != '' && $workflow_item_id != '')
        {
            $record['uid_next'] = $uid_next;
            $record['workflow_id'] = $workflow_id;
            $record['workflow_item_id'] = $workflow_item_id;
            $record['workflow_item_type_id'] = $workflow_item_type_id;
        }

        $docType = $this->getRequest()->getParam('docType');
        switch ($docType)
        {
            case 'TSHEET':
		        $jsonTrano = $this->getRequest()->getParam("trans");
                $tranos = explode(",",$jsonTrano);
		        $jsonTrans = $this->getRequest()->getParam("trans_id");
                $transID = explode(",",$jsonTrans);
                if (count($tranos) > 0 && count($transID) > 0)
                {
                    $ret = array();
                    foreach($transID as $k => $v)
                    {
                        $doc = $this->workflowTrans->fetchRow("workflow_trans_id = $v");
                        if ($doc)
                        {
                            $trano = $doc['item_id'];
                            $prj_kode = $doc['prj_kode'];
                            $sql = "SELECT * FROM workflow_trans WHERE item_id = '$trano' AND prj_kode = '$prj_kode' ORDER BY date DESC LIMIT 1";
                            $fetch = $this->db->query($sql);
                            $cekDocs = $fetch->fetch();
                            if ($cekDocs['approve'] == $this->const['DOCUMENT_SUBMIT'] || $cekDocs['approve'] == $this->const['DOCUMENT_RESUBMIT'] || $cekDocs['approve'] == $this->const['DOCUMENT_APPROVE'])
                                $ret[$trano . "-" . $prj_kode] = $v;
                        }
                    }
                    $msg = array();
                    foreach($ret as $k => $v)
                    {
                        $docsID = $v;
        		        $rets = $this->setStatus($docsID,$uid,$userID,$comment,true,$record,true);
                        if (is_array($rets))
                        {
                            $rets['params']['alltrano'] = $jsonTrano;
                            $rets['params']['alltrans'] = implode(",",$ret);

                            $json = Zend_Json::encode($rets);
                            echo $json;
                            return false;
                        }
                        else
                        {
                            unset($ret[$k]);
                            $msg[] = $rets;
                        }
                    }
                    $hasil = implode(",",$msg);

                    if (strpos($hasil,"{success: true}") !== false)
                        echo "{success: true}";
                    elseif (strpos($hasil,"false") !== false)
                        echo "{success: false, msg: \"Error!\"}";
                }
            break;
            default:
		        $this->setStatus($docsID,$uid,$userID,$comment,true,$record);
            break;
        }
	}
	
	public function rejectAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$docsID = $this->getRequest()->getParam("trans");
		$uid = $this->getRequest()->getParam("user");
		$userID = $this->getRequest()->getParam("user_id");
		$comment = $this->getRequest()->getParam("comment");
		$uid_next = $this->getRequest()->getParam('uid_next');
        $workflow_item_id = $this->getRequest()->getParam('workflow_item_id');
        $workflow_id = $this->getRequest()->getParam('workflow_id');
        if ($uid_next != '' && $workflow_id != '' && $workflow_item_id != '')
        {
            $record['uid_next'] = $uid_next;
            $record['workflow_id'] = $workflow_id;
            $record['workflow_item_id'] = $workflow_item_id;
        }

        $docType = $this->getRequest()->getParam('docType');
        switch ($docType)
        {
            case 'TSHEET':
		        $jsonTrano = $this->getRequest()->getParam("trans");
                $tranos = explode(",",$jsonTrano);
		        $jsonTrans = $this->getRequest()->getParam("trans_id");
                $transID = explode(",",$jsonTrans);
                if (count($tranos) > 0 && count($transID) > 0)
                {
                    $ret = array();
                    foreach($transID as $k => $v)
                    {
                        $doc = $this->workflowTrans->fetchRow("workflow_trans_id = $v");
                        if ($doc)
                        {
                            $trano = $doc['item_id'];
                            $prj_kode = $doc['prj_kode'];
                            $sql = "SELECT * FROM workflow_trans WHERE item_id = '$trano' AND prj_kode = '$prj_kode' ORDER BY date DESC LIMIT 1";
                            $fetch = $this->db->query($sql);
                            $cekDocs = $fetch->fetch();
                            if ($cekDocs['approve'] == $this->const['DOCUMENT_SUBMIT'] || $cekDocs['approve'] == $this->const['DOCUMENT_RESUBMIT'] || $cekDocs['approve'] == $this->const['DOCUMENT_APPROVE'])
                                $ret[$trano . "-" . $prj_kode] = $v;
                        }
                    }
                    $msg = array();
                    foreach($ret as $k => $v)
                    {
                        $docsID = $v;
        		        $msg[] = $this->setStatus($docsID,$uid,$userID,$comment,false,$record,true);
                    }
                    $hasil = implode(",",$msg);

                    if (strpos($hasil,"{success: true}") !== false)
                        echo "{success: true}";
                    elseif (strpos($hasil,"false") !== false)
                        echo "{success: false, msg: \"Error!\"}";
                }
            break;
            default:
		        $this->setStatus($docsID,$uid,$userID,$comment,false,$record);
            break;
        }
	}
	
	protected function setStatus($docsID='',$uid='',$userID='',$comment='',$approval=true,$record='',$silent=false)
	{
		$msgs = array();
		$docs = $this->workflowTrans->fetchRow("workflow_trans_id=$docsID");
   			if ($docs)
   			{
                $generic = $docs['generic'];
                $generic = (boolean)$generic;

   				$submitter = $this->workflow->fetchRow("workflow_id=" . $docs['workflow_id']);
   				$user = $this->workflowHelper->checkWorkflowInDocs($docsID,$userID);
   				if ($user)
   				{
                    if (!$generic)
                    {
                        $lastStatus = $this->workflowHelper->getDocumentLastStatusAll($docs['item_id']);

                        if ($lastStatus['uid'] == $uid)
                        {
                            $tgl = date("d M Y H:i:s",strtotime($lastStatus['date']));
                            if ($lastStatus['approve'] == 200 || $lastStatus['approve'] == 400)
                            {
                                $msg = "{success: false, msg: 'You already approved this Document at $tgl !'}";
                            }
                            else if ($lastStatus['approve'] == 100 || $lastStatus['approve'] == 150)
                            {
                                $msg = "{success: false, msg: 'You already submitted this Document at $tgl !'}";
                            }
                            else if ($lastStatus['approve'] == 300)
                            {
                                $msg = "{success: false, msg: 'You already rejected this Document at $tgl !'}";
                            }
                            if ($silent)
                                return $msg;
                            else
                                return false;
                        }
                    }
                    else
                    {
                        $cekProject = $docs['prj_kode'];
                        $lastStatus = $this->workflowHelper->getDocumentLastStatusAllGeneric($docs['item_id'],$cekProject);

                        if ($lastStatus['uid'] == $uid)
                        {
                            $tgl = date("d M Y H:i:s",strtotime($lastStatus['date']));
                            if ($lastStatus['approve'] == 200 || $lastStatus['approve'] == 400)
                            {
                                $msg = "{success: false, msg: 'You already approved this Document at $tgl !'}";
                            }
                            else if ($lastStatus['approve'] == 100 || $lastStatus['approve'] == 150)
                            {
                                $msg = "{success: false, msg: 'You already submitted this Document at $tgl !'}";
                            }
                            else if ($lastStatus['approve'] == 300)
                            {
                                $msg = "{success: false, msg: 'You already rejected this Document at $tgl !'}";
                            }
                            if ($silent)
                                return $msg;
                            else
                                return false;
                        }
                    }

                    if (!$generic)
                    {
                        $myWork = $this->workflow->fetchRow("workflow_item_id=$user AND uid_prev='" . $submitter['uid'] . "'");
                        $insertArray['generic'] = 0;
                    }
                    else
                    {
                        if (!$approval)
                        {
                            $myWork = $this->workflowGeneric->getMyWorkflow($docs['workflow_id'],$docsID,true);
                            if ($myWork)
                                $myWork = $myWork->toArray();
                        }
                        else
                        {
                            $myWork = $this->workflowGeneric->getMyWorkflow($docs['workflow_id'],$docsID);
                            if ($myWork['person'] != '')
                            {
                                $found = false;
                                if ($record != '')
                                {
                                    foreach($myWork['person'] as $k => $v)
                                    {
                                        if($v['workflow_item_type_id'] == $record['workflow_item_type_id'] && $v['workflow_item_id'] == $record['workflow_item_id'] && $v['uid_next'] == $record['uid_next'])
                                        {
                                                unset($myWork['person']);
                                                $myWork = $v;
                                                $found = true;
                                                break;
                                        }

                                    }
                                }
                                if (!$found)
                                {
                                    $return['success'] = true;
                                    $return['prj_kode'] = $docs['prj_kode'];
                                    $return['user'] = $myWork['person'];

                                    if ($silent)
                                        return $return;
                                    else
                                    {
                                        $json = Zend_Json::encode($return);
                                        echo $json;
                                        return false;
                                    }
                                }
                            }
                        }
                        $insertArray['generic'] = 1;
                    }
   					$insertArray['item_id'] = $docs['item_id'];
                    $insertArray['uid_next'] = $myWork['uid_next'];
                    $insertArray['uid_prev'] = $myWork['uid_prev'];
                    $start = $this->workflowTrans->fetchRow("item_id='" . $docs['item_id'] . "' AND approve=100");
//                    if($start)
                    if ($start['item_type'] != '')
   					    $insertArray['item_type'] = $start['item_type'];
                    else
                    {
                        $sql = "SELECT b.name FROM workflow_item a LEFT JOIN workflow_item_type b ON a.workflow_item_type_id = b.workflow_item_type_id WHERE a.workflow_item_id = $user";
                        $fetch = $this->db->query($sql);
                        $hasil = $fetch->fetch();

                        if ($hasil)
                        {
                            $insertArray['item_type'] = $hasil['name'];
                        }
                    }

                    if ($approval)
                    {
                        if ($myWork['is_end'])
                            $insertArray['approve'] = $this->const['DOCUMENT_FINAL'];
                        else
                            $insertArray['approve'] = $this->const['DOCUMENT_APPROVE'];
                    }
                    else
                        $insertArray['approve'] = $this->const['DOCUMENT_REJECT'];

                    if ($insertArray['approve'] == $this->const['DOCUMENT_REJECT'])
                    {
                        $trano = $docs['item_id'];
                        $sql = "SELECT * FROM workflow_trans WHERE item_id = '$trano' AND approve in (100,150) ORDER BY date ASC LIMIT 1";
                        $fetch3 = $this->db->query($sql);
                        if ($fetch3)
                        {
                            $submit = $fetch3->fetch();
                            $next = $submit['uid'];
                            $insertArray['uid_next'] = $next;
                        }
                        else
                        {
                            if (!$generic)
                            {
                                $workid = $myWork['workflow_item_id'];
                                $sql = "SELECT * FROM workflow WHERE workflow_item_id = $workid AND is_start = 1";
                                $fetch3 = $this->db->query($sql);
                                $submit = $fetch3->fetch();
                                $next = $submit['uid'];
                                $insertArray['uid_next'] = $next;
                            }
                            else
                            {
                                $workid = $myWork['workflow_item_id'];
                                $workTypeId = $myWork['workflow_item_type_id'];
                                $prjKode = $myWork['prj_kode'];
                                $sql = "SELECT * FROM workflow_generic WHERE workflow_item_id = $workid AND is_start = 1 AND prj_kode = '$prjKode' AND level = 0";
                                $fetch3 = $this->db->query($sql);
                                $submit = $fetch3->fetch();
                                $user = $this->masterRole->getUserFromRoleAndProject($submit['role_id'],$prjKode);
                                if ($user)
                                {
                                    $next = $user[0]['uid'];
                                }
                                $insertArray['uid_next'] = $next;
                            }
                        }
                    }

                    if ($insertArray['item_type'] == 'AFE' && $insertArray['approve'] == $this->const['DOCUMENT_APPROVE'])
                    {
                        if ($this->session->userName == 'jonhar')
                        {
                            $trano = $docs['item_id'];
                            $afeh = new ProjectManagement_Models_AFEh();
                            $fetch = $afeh->fetchRow("trano = '$trano'");
                            if ($fetch)
                            {
                                if ($fetch['margin'] <= 5 && $fetch['margin_last'] <= 2)
                                {
                                    $insertArray['approve'] = $this->const['DOCUMENT_FINAL'];
                                }
                            }
                        }
                    }
					$insertArray['uid'] = $uid;
					$sign = $this->token->getDocumentSignature();
					$insertArray['signature'] = $sign['signature'];
			  		$insertArray['date'] = $sign['date'];
					$insertArray['ip'] = $_SERVER["REMOTE_ADDR"];
					$insertArray['comment'] = $comment;
			  		$insertArray['computer_name'] = gethostbyaddr($insertArray['ip']);
			  		$insertArray['browser'] = $_SERVER["HTTP_USER_AGENT"];
					$insertArray['workflow_id'] = $myWork['workflow_id'];
                    $insertArray['workflow_item_id'] = $myWork['workflow_item_id'];
                    $insertArray['workflow_structure_id'] = $myWork['workflow_structure_id'];
                    $insertArray['prj_kode'] = $myWork['prj_kode'];
					$result = $this->workflowTrans->insert($insertArray);

                    if ($insertArray['approve'] == $this->const['DOCUMENT_FINAL'])
                    {
                        if ($insertArray['item_type'] == '')
                        {
                            $sql = "SELECT b.name FROM workflow_item a LEFT JOIN workflow_item_type b ON a.workflow_item_type_id = b.workflow_item_type_id WHERE a.workflow_item_id = $user";
                            $fetch = $this->db->query($sql);
                            $hasil = $fetch->fetch();

                            if ($hasil)
                            {
                                $itemType = $hasil['name'];
                            }
                        }
                        else
                            $itemType = $insertArray['item_type'];
                        switch($itemType)
                        {
                            case 'AFE':
                                $cboq3h = new Default_Models_MasterCboq3H();
                                $cboq3h->transferAFEtoBOQ3($docs['item_id']);
                                $addBoq2 = new Default_Models_MasterAddco();
                                $addBoq2->transferAddRevenue($docs['item_id']);
                            break;
                            case 'PO':
                            case 'POO':
                                $po = new Default_Models_ProcurementPoh();
                                $po->changePODate($docs['item_id']);
                            break;
                        }
                    }

					if ($myWork['is_end'] && $insertArray['approve'] == $this->const['DOCUMENT_FINAL'])
					{
                        if ($insertArray['item_type'] == '')
                        {
                            $sql = "SELECT b.name FROM workflow_item a LEFT JOIN workflow_item_type b ON a.workflow_item_type_id = b.workflow_item_type_id WHERE a.workflow_item_id = $user";
                            $fetch = $this->db->query($sql);
                            $hasil = $fetch->fetch();

                            if ($hasil)
                            {
                                $itemType = $hasil['name'];
                            }
                        }
                        else
                            $itemType = $insertArray['item_type'];
                        switch($itemType)
                        {
                            case 'PRABOQ3':
                                $budget = new Default_Models_Budget();
                                $budget->transferTempBOQ3($docs['item_id']);
                            break;

                            case 'SUPP':
                                $suplier = new Default_Models_MasterSuplier();
                                $suplier->updateaktif($docs['item_id']);
                                break;

                            case 'PRABOQ2':
                                $praco = new Default_Models_Praco();
                                $praco->updateaktif($docs['item_id']);
                                break;

                            case 'PRAOHP':
                                $budget = $this->_helper->getHelper('budget');
                                $budget->transferTempOHP($docs['item_id']);
                            break;

                            case 'TSHEET':
                                $timesheet = new ProjectManagement_Models_Timesheet();
                                $timesheet->setFinalApprove($docs['item_id']);
                            break;

                            case 'PRABGO':
                                $praohbudget = new ProjectManagement_Models_TemporaryOverHeadBOQ3h();
                                $praohbudget->transferTempOHBOQ3($docs['item_id']);
                            break;

                            case 'PRACO':
                                $praco = new Sales_Models_Praco();
                                $praco->transferTempPRACO($docs['item_id']);
                                break;
                            case 'APRACO':
                                $praco = new Sales_Models_AddPraco();
                                $praco->transferTempAPRACO($docs['item_id']);
                                break;
                            case 'ASF':
                                $asf = new Default_Models_AdvanceSettlementFormH();
                                $asf->updateArfAging($docs['item_id']);
                                break;
                        }
					}

                    if ($insertArray['approve'] == $this->const['DOCUMENT_FINAL'])
                    {
                        $trano = $insertArray['item_id'];
                        $this->workflowTrans->update(array("final" => 1),"item_id = '$trano'");
                    }
					$msg =  "{success: true}";
   				}
   				else
   					$msg =  "{success: false, msg: 'Access Denied!'}";
   			}
   			else
   				$msg =  "{success: false, msg: 'No Such Document!'}";

        if ($silent)
            return $msg;
        else
            echo $msg;
	}

    public function progressAction()
	{
		$this->_helper->viewRenderer->setNoRender();
        for($i=0;$i<10000;$i++)
        {
            echo "{num: $i}";
        }
    }

    public function workflowgenericAction()
    {
        
    }

    public function workflowprocessgenericAction()
	{

	}

    public function workflowprocessgenericbyoneAction()
	{

	}

    public function addworkflowprocessgenericAction()
	{
//		$workflow_item_type_id = $this->request->getParam('workflow_item_type_id');
		$workflow_item_name = $this->request->getParam('workflow_item_name');
		$workflow_item_id = $this->request->getParam('workflow_item_id');
		$byone = $this->request->getParam('byone');

        if ($byone)
            $byone = true;
        else
            $byone = false;

        if (!$byone)
        {
//		$level = $this->workflowGeneric->getTreeRouteGroup($workflow_item_type_id);
            $level = $this->workflowGeneric->getTreeRouteGroupNew($workflow_item_name);
            if ($level)
            {
                $result = array();
                foreach ($level as $key => $val)
                {
    //				$people = $this->workflowGeneric->getTreeRoute($workflow_item_type_id,$val['level']);
                    $people = $this->workflowGeneric->getTreeRouteNew($workflow_item_name,$val['level']);
                    foreach($people as $key2 => $val2)
                    {
                        $result[$key][$key2]['role_name'] = $val2['role_name'];
                        $result[$key][$key2]['role_id'] = $val2['role_id'];

                    }
                }
            }

    //		$this->view->workflow_item_type_id = $workflow_item_type_id;
            $this->view->workflow_item_name = $workflow_item_name;
        }
        else
        {
            $level = $this->workflowGeneric->getTreeRouteGroupNewByOne($workflow_item_id);
            if ($level)
            {
                $result = array();
                foreach ($level as $key => $val)
                {
    //				$people = $this->workflowGeneric->getTreeRoute($workflow_item_type_id,$val['level']);
                    $people = $this->workflowGeneric->getTreeRouteNewByOne($workflow_item_id,$val['level']);
                    foreach($people as $key2 => $val2)
                    {
                        $result[$key][$key2]['role_name'] = $val2['role_name'];
                        $result[$key][$key2]['role_id'] = $val2['role_id'];

                    }
                }
            }

    //		$this->view->workflow_item_type_id = $workflow_item_type_id;
            $this->view->workflow_item_id = $workflow_item_id;
        }
        $this->view->byone = $byone;
		$this->view->dataWorkflow = $result;
	}

    public function listroleAction()
	{
		$this->_helper->viewRenderer->setNoRender();

		$roleName = $this->request->getParam('role_name');
		$roleID = $this->request->getParam('role_id');

        if ($roleName != '')
            $where = "role_name = '$roleName'";

        if ($where != '')
            $where .= " AND active = 1";
        else
            $where = "active = 1";
		$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

		$role = new Admin_Model_Masterroletype();
        $return['posts'] = $role->fetchAll($where,array($sort . " " . $dir),$limit,$offset)->toArray();
        $return['count'] = $role->fetchAll($where)->count();
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}

    public function submitworkflowprocessgenericAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$id_person = $this->getRequest()->getParam('id_person');
		$is_start = $_POST['is_start'];
        $byone = $this->getRequest()->getParam('byone');
        if ($byone)
            $byone = true;
        else
            $byone = false;
		$dataArray = array();
		$i = 0;

        if ($byone)
        {
            $id_workflow = $this->request->getParam('workflow_item_id');
            $sql = "SELECT COALESCE(MAX(level),0) as jumlah FROM workflow_generic WHERE workflow_item_id = $id_workflow";
        }
        else
        {
    		$id_workflow = $this->request->getParam('workflow_item_name');
            $sql = "SELECT COALESCE(MAX(level),0) as jumlah FROM workflow_generic WHERE workflow_item_name = '$id_workflow'";
        }
        $fetch = $this->db->query($sql);
        $cekExist = $fetch->fetch();

        if ($cekExist['jumlah'] > 0)
        {
            $countData = count($id_person);
            if ($countData > intval($cekExist['jumlah']))
            {
                $workflowStruct->delete("workflow_item_id = $id_workflow");
                $this->insertWorkflowStructure($id_workflow,$id_person);
            }
            else
            {
                foreach($id_person as $key => $val)
                {
                    if ($val != '' && isset($val))
                    {

                        $sql = "SELECT master_role_id FROM workflow_structure WHERE level = $i AND workflow_item_id = $id_workflow";
                        $fetch = $this->db->query($sql);
                        $cek = $fetch->fetchAll();
                        if ($cek)
                        {
                            $cekArray = array();
                            $hasilCek = array();

                            foreach ($val as $key2 => $val2)
                            {
                                $cekArray[$key2]['master_role_id'] = $val2;
                            }
                            $hasilCek = $this->utility->array_compare($cekArray,$cek);
                            if ($hasilCek)
                            {
                               if (is_array($hasilCek[0]))
                               {
                                    $dataArray["level"] = $i;
                                    $dataArray["master_role_id"] = $val2;
                                    $dataArray["workflow_item_id"] = $id_workflow;
                                    $workflowStruct->insert($dataArray);
                               }
                               if (is_array($hasilCek[1]))
                               {
                                    foreach($hasilCek[1] as $key3 => $val3)
                                    {
                                        $del = $val3['master_role_id'];
                                        $workflowStruct->delete("level = $i AND workflow_item_id = $id_workflow AND master_role_id = $del");
                                    }
                               }
                            }
                        }
                    }


                    $i++;
                }
            }
		}
        else
        {
            $i = 0;
            if ($id_person == '')
               return false;
            foreach($id_person as $key => $val)
            {
                foreach ($val as $key2 => $val2)
                {
                    if ($val2 != '' && isset($val2))
                    {
                        if (!$byone)
                            $sql = "SELECT * FROM workflow_item WHERE name = '$id_workflow'";
                        else
                            $sql = "SELECT * FROM workflow_item WHERE workflow_item_id = $id_workflow";

                        $fetch = $this->db->query($sql);
                        if ($fetch)
                        {
                            $fetch = $fetch->fetchAll();
                            foreach ($fetch as $k => $v)
                            {
                                if ($i == 0)
                                {
                                    $dataArray['is_start'] = 1;
                                    $dataArray['is_end'] = 0;
                                }
                                else
                                {
                                    if($is_start[$key][$key2] == 'on')
                                        $dataArray['is_start'] = 1;
                                    else
                                        $dataArray['is_start'] = 0;

                                    if ($i == (count($id_person) - 1))
                                        $dataArray['is_end'] = 1;
                                    else
                                        $dataArray['is_end'] = 0;
                                }

                                if ($dataArray['is_end'] == 1)
                                {
                                    if ($_POST['is_executor'] == 'on')
                                    {
                                        $dataArray['is_executor'] = 1;
                                    }
                                }
                                $dataArray["level"] = $i;
                                $dataArray["role_id"] = $val2;
                                $sql = "SELECT * FROM master_role_type WHERE id = $val2";
                                $fetch2 = $this->db->query($sql);
                                if ($fetch2)
                                {
                                    $fetch2 = $fetch2->fetch();
                                    $dataArray["role_name"] = $fetch2['display_name'];
                                }
//                                $dataArray["workflow_item_type_id"] = $id_workflow;
//                                $dataArray["workflow_item_id"] = $v['workflow_item_id'];
//                                $dataArray["workflow_item_name"] = $v['name'];
                                $dataArray["workflow_item_type_id"] = $v['workflow_item_type_id'];
                                $dataArray["workflow_item_id"] = $v['workflow_item_id'];
                                $dataArray["workflow_item_name"] = $v['name'];
                                $dataArray["prj_kode"] = $v['prj_kode'];
                                $this->workflowGeneric->insert($dataArray);
                            }
                        }
                    }
                }
                $i++;
            }
        }
		$this->_helper->redirector('menu','index','default');
	}

    public function listworkflowitemgenericAction()
	{
		$this->_helper->viewRenderer->setNoRender();

        $workflowItemID = $this->request->getParam('workflow_item_id');
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'w.name';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
		$generic = $this->getRequest()->getParam("generic");
		$byone = $this->getRequest()->getParam("byone");

        if ($byone)
            $byone = true;
        else
            $byone = false;

        if ($generic)
           $generic = true;
        else
            $generic = false;


		$search = $this->getRequest()->getParam("search");
		$option = $this->getRequest()->getParam("option");

        if ($search != '' && $option != '')
        {
            $search = " AND $option LIKE '%$search%'";
        }
        $return['posts'] = $this->workflowItem->listWorkflowItemGeneric($sort,$dir,$limit,$offset,$byone,$search);

        $sql = $this->db->select()
                ->from(array('w' => 'workflow_item'))
                ->where("w.generic = 1 $search")
                ->group(array("w.name"));
        
        if ($byone)
        $sql->reset( Zend_Db_Select::GROUP );

        $result = $this->db->fetchAll($sql);
        $return['count'] = count($result);
        


        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
	}

    public function showworkflowgenericrouteAction()
    {
		$workflow_item_id = $this->getRequest()->getParam("workflow_item_id");
        $cek = $this->workflowGeneric->fetchAll("workflow_item_id = $workflow_item_id");
        $array = array();
        $workflow_name = '';
        if ($cek)
        {
            $cek = $cek->toArray();
            $workflow_name = $cek[0]['workflow_item_name'];
            foreach($cek as $k => $v)
            {
                $level = $v['level'];
                $array[$level][] = $v['role_name'];
            }
        }
        $this->view->data = $array;
        $this->view->name = $workflow_name;
    }

    public function clearmemcacheAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        //delete workflow from memcache
        $memcacheWork = Zend_Registry::get('MemcacheWorkflow');
        $memcacheWork->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            array('WORKFLOW')
        );
        $memcacheWork->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            array('WORFKLOW')
        );
    }

}

?>