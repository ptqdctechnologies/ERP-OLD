<?php

class Admin_WorkflowController extends Zend_Controller_Action {

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
    private $bypassNext = false;
    private $model;

    public function init() {
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

        $this->workflow = new Admin_Models_Workflow();
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowStructure = new Admin_Models_Workflowstructure();
        $this->workflowGeneric = new Admin_Models_Workflowgeneric();
        $this->workflowItem = new Admin_Models_Workflowitem();
        $this->workflowItemType = new Admin_Models_WorkflowItemType();
        $this->masterRole = new Admin_Models_Masterrole();
        $this->model = Zend_Controller_Action_HelperBroker::getStaticHelper('model');
    }

    public function workflowitemAction() {
        $this->view->userID = $this->session->idUser;
    }

    public function workflowitemgenericAction() {
        $this->view->userID = $this->session->idUser;
    }

    public function showworkflowitemAction() {
        $this->view->userID = $this->session->idUser;
        $goto = $this->request->getParam('goto');
        if ($goto != '') {
            $this->view->goto;
        }
    }

    public function workflowAction() {
        
    }

    public function workflowtypeAction() {
        $this->view->userID = $this->session->idUser;
    }

    public function listworkflowitemAction() {
        $this->_helper->viewRenderer->setNoRender();

        $txtsearch = $this->getRequest()->getParam("search");
        $option = $this->getRequest()->getParam("option");
        $search = null;

        if ($txtsearch == "" || $txtsearch == null) {
            $search = '';
        } else if ($txtsearch != null && $option == 1) {
            $search = "w.name like '%$txtsearch%'";
        } else if ($txtsearch != null && $option == 2) {
            $search = "wt.name like '%$txtsearch%'";
        } else if ($txtsearch != null && $option == 3) {
            $search = "w.prj_kode like '%$txtsearch%' ";
        }


        $userID = $this->request->getParam('userid');
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'w.name';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $generic = ($this->_getParam("generic") == "true") ? true : false;

//        if ($prjKode == '' && $sitKode == '')
//        {
        $ret = $this->workflowItem->listWorkflowItem($sort, $dir, $limit, $offset, $generic, $search);
        $return['posts'] = $ret['data'];
//        }
//        else
//        {
//             $return['posts'] = $this->workflowItem->listWorkflowItem($sort,$dir,$limit,$offset,$generic,$search);
//        }
//        if (!$generic)
        $return['count'] = $ret['count'];
//        else
//        {
//            $sql = $this->db->select()
//                    ->from(array('w' => 'workflow_item'))
//                    ->where("w.generic = 1");
////                    ->group(array("w.workflow_item_type_id","w.prj_kode"));
//            $result = $this->db->fetchAll($sql);
//            $return['count'] = count($result);
//        }

        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listworkflowitemtypeAction() {
        $this->_helper->viewRenderer->setNoRender();
        $type = $this->getRequest()->getParam("type");
        $generic = $this->getRequest()->getParam("generic");
        $all = ($this->getRequest()->getParam("all") != '') ? true : false;

        if ($generic)
            $where = "generic = 1";

        if ($type) {
            try {
                $type = Zend_Json::decode($type);
                $isJson = true;
            } catch (Zend_Exception $e) {
                if ($e->getMessage() == 'Decoding failed: Syntax error')
                    $isJson = false;
            }
        }

        if ($type != 'all') {
            $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
            $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
            $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'name';
            $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';


            $return['posts'] = $this->workflowItemType->fetchAll($where, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        } else
            $return['posts'] = $this->workflowItemType->fetchAll($where)->toArray();


        $from = $this->getRequest()->getParam("from");
        if ($from = 'showprocessdocument') {
            $return['posts'][] = array(
                "name" => "ARF With BT",
                "workflow_item_type_id" => 999,
                "generic" => 0
            );
        }

        if ($all) {
            $return['posts'][] = array(
                "name" => "ARF Pulsa",
                "workflow_item_type_id" => 1000,
                "generic" => 1
            );
        }

        if ($all) {
            $return['posts'][] = array(
                "name" => "ASF Pulsa",
                "workflow_item_type_id" => 1001,
                "generic" => 1
            );
        }
















        if ($isJson && $type) {
            $i = 0;
            $newArray = array();
            foreach ($return['posts'] as $k => $v) {
                $found = false;
                foreach ($type as $k2 => $v2) {
                    if ($v2 == $v['name']) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    $newArray[] = $v;
                }
            }

            $return['posts'] = $newArray;
        }


        $return['count'] = $this->workflowItemType->fetchAll($where)->count();
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addworkflowitemAction() {
        $prjKode = $this->request->getParam('prj_kode');
        $sitKode = $this->request->getParam('sit_kode');
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');

        $generic = $this->request->getParam('generic');
        if (!$generic) {
            $jsonData = Zend_Json::decode($this->json);
            $jsonData['prj_kode'] = $prjKode;
            $insertArray = $this->workflowItem->createWorkflowItem($jsonData, '');
            $fetch = $this->workflowItemType->getByID($jsonData['workflow_item_type_id']);
            $insertArray['workflow_item_type_name'] = $fetch['name'];
            $return = array('success' => true, 'message' => 'Created Workflow', 'posts' => $insertArray);
        } else {
            $posts = $this->request->getParam('posts');
            $etc = $this->request->getParam('etc');

            $posts = Zend_Json::decode($posts);
            $etc = Zend_Json::decode($etc);

            if (count($posts) > 0) {
                foreach ($posts as $key => $val) {
                    $arrayInsert = array(
                        "workflow_item_type_id" => $etc[0]["workflow_item_type_id"],
                        "name" => $etc[0]["name"],
                        "description" => $etc[0]['desc'],
                        "prj_kode" => $val['Prj_Kode'],
                        "generic" => 1
                    );
                    $this->workflowItem->insert($arrayInsert);
                }
            } else {
                $arrayInsert = array(
                    "workflow_item_type_id" => $etc[0]["workflow_item_type_id"],
                    "name" => $etc[0]["name"],
                    "description" => $etc[0]['desc'],
                    "prj_kode" => '',
                    "generic" => 1
                );
                $this->workflowItem->insert($arrayInsert);
            }
            $return = array('success' => true, 'message' => 'Created Workflow');
        }
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateworkflowitemAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $userID = $request->getParam('userid');
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($request->getParam('posts'));
        $updateArray = $this->workflowItem->updateWorkflowItem($jsonData, '');
        $return = array('success' => true, 'message' => 'Updated Workflow', 'posts' => $updateArray);
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function deleteworkflowitemAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($request->getParam('posts'));
        $result = $this->workflowItem->deleteWorkflowItem($jsonData);
//		if ($result)
        $return = array('success' => true, 'message' => 'Deleted Workflow', 'posts' => array());
//		else
//			$return = array('false' => true,'message' => 'You dont have Delete privilege!','posts' => array());
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addworkflowitemtypeAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($this->json);

        if ($jsonData['generic'] == "on")
            $jsonData['generic'] = 1;
        else
            $jsonData['generic'] = 0;
        $id = $this->workflowItemType->insert($jsonData);
        $jsonData['workflow_item_type_id'] = $id;
        $return = array('success' => true, 'message' => 'Created Workflow Type', 'posts' => $jsonData);
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateworkflowitemtypeAction() {
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
        $this->workflowItemType->update($jsonData, "workflow_item_type_id=$id");
        $jsonData['workflow_item_type_id'] = $id;
        $return = array('success' => true, 'message' => 'Updated Workflow Type', 'posts' => $jsonData);
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function deleteworkflowitemtypeAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = $request->getParam('posts');
        $result = $this->workflowItemType->delete("workflow_item_type_id=$jsonData");
        $return = array('success' => true, 'message' => 'Deleted Workflow Type');
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function workflowprocessAction() {
        
    }

    private function getWorkflowProcess($workflow_item_id) {
        $result = array();
        $ldap = new Default_Models_Ldap();
        $level = $this->workflowStructure->getTreeRouteGroup($workflow_item_id);
        if ($level) {
            foreach ($level as $key => $val) {
                $people = $this->workflowStructure->getTreeRoute($workflow_item_id, $val['level']);
                foreach ($people as $key2 => $val2) {
                    if ($val2['uid'] != '') {
//                        $account = $ldap->getAccount($val2['uid']);
//                        if ($account == null)
//                        {
//                            $sql = "SELECT Name FROM master_login WHERE uid='" . $val2['uid'] . "'";
//                            $fetch = $this->db->query($sql);
//                            $fetch = $fetch->fetch();
//                            if ($fetch)
//                            {
//                                $name = $fetch['Name'];
//                                $result[$key][$key2]['displayname'] = $name;
//                            }
//                        }
//                        else
//                            $result[$key][$key2]['displayname'] = $account['displayname'][0];
                        $result[$key][$key2]['displayname'] = QDC_User_Ldap::factory(array("uid" => $val2['uid']))->getName();
                        $result[$key][$key2]['master_role_id'] = $people[$key2]['master_role_id'];
                    }
                }
            }
        }
        return $result;
    }

    public function addworkflowprocessAction() {
        $workflow_item_id = $this->request->getParam('workflow_item_id');
        $this->view->prj_kode = $this->request->getParam('prj_kode');

        $this->view->workflow_item_id = $workflow_item_id;
        $this->view->dataWorkflow = $this->getWorkflowProcess($workflow_item_id);
    }

    public function showworkflowprocessAction() {
        $workflow_item_id = $this->request->getParam('workflow_item_id');

        $this->view->workflow_item_id = $workflow_item_id;
        $this->view->dataWorkflow = $this->getWorkflowProcess($workflow_item_id);
    }

    public function listuserroleAction() {
        $this->_helper->viewRenderer->setNoRender();

        $roleName = $this->request->getParam('role_name');
        $uid = $this->request->getParam('uid');
        $prj_kode = $this->request->getParam('prj_kode');
        $roleID = $this->request->getParam('role_id');

        if (!isset($roleName) && $roleID == "")
            $roleName = 'project';

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'mr.id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $userRole = new Admin_Models_Userrole();
        $roleType = new Admin_Models_Masterroletype();
        $login = new Admin_Models_Masterlogin();

        $select = $this->db->select()
                ->from(array("mr" => $userRole->__name()))
                ->joinLeft(array("mrt" => $roleType->__name()), "mr.id_role=mrt.id", array(
                    "display_name" => "mrt.display_name"
                ))
                ->joinLeft(array("ml" => $login->__name()), "mr.id_user=ml.id", array(
                    "fullname" => "ml.Name",
                    "uid"
                ))
                ->where("mr.active = 1");

        if ($roleName)
            $select = $select->where("mrt.role_name = ?", $roleName);
        if ($uid)
            $select = $select->where("(" . $this->db->quoteInto("ml.uid LIKE ?", "%{$uid}%") . " OR " . $this->db->quoteInto("ml.Name LIKE ?", "%{$uid}%") . ")");
        if ($prj_kode)
            $select = $select->where("mr.prj_kode LIKE ?", "%{$prj_kode}%");

        $subselect = $this->db->select()
                ->from(array("a" => $select), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS a.*")
                ))
                ->order(array("a.fullname ASC", "a.prj_kode DESC"))
                ->limit($limit, $offset);

        $data = $this->db->fetchAll($subselect);

        foreach ($data as $k => $v) {
            $name = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
            if (!$name) {
                continue;
            } else {
                $data[$k]['fullname'] = $name;
                $return['posts'][] = $data[$k];
            }
        }

        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
//        if ($roleName != "")
//		    $return = $userRole->getRoleType($roleName,$sort,$dir,$limit,$offset);
//		elseif ($roleID != "")
//    		$return = $userRole->getRoleType("",$sort,$dir,$limit,$offset,'','',$roleID);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    private function insertWorkflowStructure($id_workflow = '', $person = '') {
        $workflowStruct = new Admin_Models_Workflowstructure();
        $i = 0;
        if ($person == '')
            return false;
        foreach ($person as $key => $val) {
            foreach ($val as $key2 => $val2) {
                if ($val2 != '' && isset($val2)) {
                    $dataArray["level"] = $i;
                    $dataArray["master_role_id"] = $val2;
                    $dataArray["workflow_item_id"] = $id_workflow;
                    $workflowStruct->insert($dataArray);
                }
            }
            $i++;
        }
    }

    public function submitworkflowprocessAction() {
        $this->_helper->viewRenderer->setNoRender();
        $id_workflow = $this->request->getParam('workflow_item_id');
        $id_person = $_POST['id_person'];
        $dataArray = array();
        $i = 0;
        $workflowStruct = new Admin_Models_Workflowstructure();
//		$workflowStruct->delete("workflow_item_id = $id_workflow");
        $sql = "SELECT MAX(level) as jumlah FROM workflow_structure WHERE workflow_item_id = $id_workflow";
        $fetch = $this->db->query($sql);
        $cekExist = $fetch->fetchAll();

        if ($cekExist) {
            $countData = count($id_person);
            if ($countData > intval($cekExist['jumlah'])) {
                $workflowStruct->delete("workflow_item_id = $id_workflow");
                $this->insertWorkflowStructure($id_workflow, $id_person);
            } else {
                foreach ($id_person as $key => $val) {
                    if ($val != '' && isset($val)) {

                        $sql = "SELECT master_role_id FROM workflow_structure WHERE level = $i AND workflow_item_id = $id_workflow";
                        $fetch = $this->db->query($sql);
                        $cek = $fetch->fetchAll();
                        if ($cek) {
                            $cekArray = array();
                            $hasilCek = array();

                            foreach ($val as $key2 => $val2) {
                                $cekArray[$key2]['master_role_id'] = $val2;
                            }
                            $hasilCek = $this->utility->array_compare($cekArray, $cek);
                            if ($hasilCek) {
                                if (is_array($hasilCek[0])) {
                                    $dataArray["level"] = $i;
                                    $dataArray["master_role_id"] = $val2;
                                    $dataArray["workflow_item_id"] = $id_workflow;
                                    $workflowStruct->insert($dataArray);
                                }
                                if (is_array($hasilCek[1])) {
                                    foreach ($hasilCek[1] as $key3 => $val3) {
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
        } else {
            $this->insertWorkflowStructure($id_workflow, $id_person);
        }
//		$workflow = new Admin_Model_Workflow();
//		$workflow->addWorkflow($dataArray,$id_workflow);
        $this->_helper->redirector('menu', 'index', 'default');
    }

    public function addworkflowrouteAction() {
        
    }

    public function listworkflowrouteAction() {
        $this->_helper->viewRenderer->setNoRender();
        $workflowStruct = new Admin_Models_Workflowstructure();
        $ldap = new Default_Models_Ldap();

        $id_workflow = $this->request->getParam('workflow_item_id');
        $isRouted = $this->request->getParam('routed');
        $node = $this->request->getParam('node');
        if ($node != 'src' && $node != 'src2')
            return;
        $level = $workflowStruct->getTreeRouteGroup($id_workflow);
        if (!$isRouted) {
            $temp = array();
            $indeksArray = 0;
            foreach ($level as $key => $val) {
                if ($key == (count($level) - 1))
                    continue;
                $result = $workflowStruct->getTreeRoute($id_workflow, $val['level']);
                if (count($result) > 0) {
                    foreach ($result as $key2 => $val2) {
                        $ret = '';
                        $cek = $workflowStruct->cekInWorkflow($id_workflow, $val2['id'], $ret);
                        if ($cek) {
                            if ($ret) {
                                if ($ret['next'] != 0 && $ret['next'] != '') {
                                    $cekStruct = $workflowStruct->fetchRow("id = " . $ret['next']);
                                    if ($cekStruct)
                                        continue;
                                }
                                if ($ret['prev'] != 0 && $ret['prev'] != '') {
                                    $cekStruct = $workflowStruct->fetchRow("id = " . $ret['prev']);
                                    if ($cekStruct)
                                        continue;
                                }
                                $cek = false;
                            }
                        }
                        if ($key == 0) {
                            $temp[$indeksArray]['text'] = "Start";
                            $temp[$indeksArray]['id'] = "start";
                        } elseif ($key < (count($level) - 1)) {
                            $temp[$indeksArray]['text'] = "Next-" . ($key);
                            $temp[$indeksArray]['id'] = "next-" . ($key);
                        }
//						$name = $ldap->getAccount($result[$key2]['uid']);
//						if ($name == null)
//                        {
//                            $sql = "SELECT Name FROM master_login WHERE uid='" . $val2['uid'] . "'";
//                            $fetch = $this->db->query($sql);
//                            $fetch = $fetch->fetch();
//                            if ($fetch)
//                            {
//                                $name = $fetch['Name'];
//                                $temp2 = array("text" => $name, "id" => $val2['id'], "leaf" => true,"cls" => "workflow-lower","uid" => $result[$key2]['uid']);
//                            }
//                        }
//                        else
//						    $temp2 = array("text" => $name['displayname'][0], "id" => $val2['id'], "leaf" => true,"cls" => "workflow-lower","uid" => $result[$key2]['uid']);
                        $temp2 = array("text" => QDC_User_Ldap::factory(array("uid" => $result[$key2]['uid']))->getName(), "id" => $val2['id'], "leaf" => true, "cls" => "workflow-lower", "uid" => $result[$key2]['uid']);
                        $temp[$indeksArray]['children'][] = $temp2;
                    }
                    if (!$cek)
                        $indeksArray++;
                }
            }
            if (count($temp) == 1) {
                foreach ($temp as $indeks => $value) {
                    $temporer = $temp[$indeks];
                }
                unset($temp);
                $temp = $temporer;
            }
        } else {
            $temp = array();
            foreach ($level as $key => $val) {
                if ($key == 0)
                    continue;
                elseif ($key == count($level) - 1) {
                    $temp[$key - 1]['text'] = "End";
                    $temp[$key - 1]['id'] = "end";
                    $temp[$key - 1]['allowDrop'] = false;
                } else {
                    $temp[$key - 1]['text'] = "Next-" . ($key);
                    $temp[$key - 1]['id'] = "next-" . ($key);
                    $temp[$key - 1]['allowDrop'] = false;
                }

                $result = $workflowStruct->getTreeRoute($id_workflow, $val['level']);
                foreach ($result as $key2 => $val2) {
                    $end = false;
//					$name = $ldap->getAccount($result[$key2]['uid']);
//                    if ($name == null)
//                    {
//                        $sql = "SELECT Name FROM master_login WHERE uid='" . $result[$key2]['uid'] . "'";
//                        $fetch = $this->db->query($sql);
//                        $fetch = $fetch->fetch();
//                        if ($fetch)
//                        {
//                            $name = $fetch['Name'];
//                        }
//                    }
//                    else
//                    $name = $name['displayname'][0];
                    $name = QDC_User_Ldap::factory(array("uid" => $result[$key2]['uid']))->getName();
                    $theWorkflow = $this->workflow->fetchRow("workflow_structure_id = " . $val2['id'] . " AND master_role_id = " . $val2['master_role_id'] . " AND workflow_item_id = " . $val2['workflow_item_id']);
                    if ($theWorkflow) {
                        if ($theWorkflow['is_end'] == 1) {
                            $end = true;
                        }
                    } else {
                        if ($temp[$key - 1]['text'] == "End") {
                            $end = true;
                        }
                    }
//                    echo $temp[$key-1]['text'];
                    $child = $this->workflowStructure->getChildInWorkflow($id_workflow, $val2['id']);
                    if (count($child) > 0) {
                        $children = array();
                        foreach ($child as $indeks => $value) {
                            mt_srand((double) microtime() * 1000000);
                            $childId = mt_rand(0, 255) . "-" . $value['workflow_structure_id'];
//							$childId  = $value['workflow_structure_id'];
//							$childName = $ldap->getAccount($value['uid']);
//                            if ($childName == null)
//                            {
//                                $sql = "SELECT Name FROM master_login WHERE uid='" . $value['uid'] . "'";
//                                $fetch = $this->db->query($sql);
//                                $fetch = $fetch->fetch();
//                                if ($fetch)
//                                {
//                                    $childName = $fetch['Name'];
//                                    $children[] = array("text" => $childName, "id" => $childId, "leaf" => true, "iconCls" => "workflow-lower-leaf","uid" => $value['uid']);
//                                }
//                            }
//                            else
//							    $children[] = array("text" => $childName['displayname'][0], "id" => $childId, "leaf" => true, "iconCls" => "workflow-lower-leaf","uid" => $value['uid']);
                            $children[] = array("text" => QDC_User_Ldap::factory(array("uid" => $value['uid']))->getName(), "id" => $childId, "leaf" => true, "iconCls" => "workflow-lower-leaf", "uid" => $value['uid']);
                        }
                        if (!$end)
                            $temp2 = array("text" => $name, "id" => $val2['id'], "cls" => "workflow-upper", "children" => $children, "uid" => $result[$key2]['uid']);
                        else
                            $temp2 = array("text" => $name, "id" => $val2['id'], "cls" => "workflow-upper", "children" => $children, "checked" => false, "qtip" => "Please check the checkbox if this person is the Executor for Document.", "uid" => $result[$key2]['uid']);

                        $temp[$key - 1]['children'][] = $temp2;
                    }
                    else {
                        if (!$end)
                            $temp2 = array("text" => $name, "id" => $val2['id'], "cls" => "workflow-upper", "leaf" => false, "uid" => $result[$key2]['uid']);
                        else
                            $temp2 = array("text" => $name, "id" => $val2['id'], "cls" => "workflow-upper", "leaf" => false, "checked" => false, "qtip" => "Please check the checkbox if this person is the Executor for Document.", "uid" => $result[$key2]['uid']);
                        $temp[$key - 1]['children'][] = $temp2;
                    }
                }
            }
        }
        $json = Zend_Json::encode($temp);
        if (substr($json, 0, 1) != '[')
            $json = '[' . $json . ']';
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function savetreeAction() {
        $this->_helper->viewRenderer->setNoRender();

        $workflow_item_id = $this->request->getParam('workflow_item_id');
        $result = $this->workflow->fetchAll("workflow_item_id = $workflow_item_id");

        $isExist = false;

        if ($result) {
            $this->workflow->delete("workflow_item_id = $workflow_item_id");
            $isExist = true;
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($this->request->getParam('posts'));

        $isi = $jsonData['children'];
        if (count($jsonData['children']) > 0) {
            foreach ($isi as $key => $val) {

                if ($key <= (count($jsonData['children']) - 1)) {
                    if ($val['id'] == 'next-' . ($key + 1) || $val['id'] == 'end') {
                        foreach ($val['children'] as $key2 => $val2) {
                            if (count($val2['children']) > 0) {
                                $idParent = $val2['id'];
                                $uidParent = $val2['uid'];
                                foreach ($val2['children'] as $key3 => $val3) {
                                    $idChild = $val3['id'];
                                    $uidChild = $val3['uid'];
                                    if (strpos($idChild, "-")) {
                                        $split = explode("-", $idChild);
                                        $idChild = $split[1];
                                    }
                                    $result = $this->workflowStructure->fetchRow("id = $idChild")->toArray();
                                    if (!$result)
                                        continue;
                                    $insertArray = array();
                                    $role = $this->masterRole->fetchRow("id = " . $result['master_role_id'])->toArray();
                                    if ($result['level'] == 0) {
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
                                    } else {
                                        $insertArray['workflow_item_id'] = $workflow_item_id;
                                        $cek = $this->workflow->fetchAll("next = $idChild")->toArray();
                                        if ($cek) {
                                            foreach ($cek as $indeks => $value) {
                                                $insertArray['prev'] = $value['workflow_structure_id'];
                                                $insertArray['next'] = $idParent;
                                                $insertArray['is_start'] = 0;
//												if ($val['id'] == 'end')
//													$insertArray['is_end'] = 1;
//												else
                                                $insertArray['is_end'] = 0;
                                                if ($value['uid'] == '')
                                                    $insertArray['uid_prev'] = '';
                                                else {
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
                                        } else {
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
                                if ($val['id'] == 'end') {
                                    foreach ($val2['children'] as $key3 => $val3) {
                                        $idChild = $val3['id'];
                                        $uidChild = $val3['uid'];
                                        if (strpos($idChild, "-")) {
                                            $split = explode("-", $idChild);
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
                                        if ($val2['checked'] == true || $val2['checked'] == "1") {
                                            $insertArray['is_executor'] = 1;
                                            $insertArray['is_final'] = 0;
                                        } else {
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
                Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('WORKFLOW')
        );

        if ($isExist) {
            $fetch = $this->db->query("SELECT a.* FROM workflow_trans a LEFT JOIN workflow b ON a.workflow_id = b.workflow_id WHERE b.workflow_id IS NULL AND a.workflow_item_id = $workflow_item_id");
            $cekTrans = $fetch->fetchAll();
            //Iterasi satu per satu transaksi workflow yang tidak ada workflow_id
            foreach ($cekTrans as $key => $val) {
                $uid = $val['uid'];
                $workflow_structure_id = $val['workflow_structure_id'];
                $workflow_trans_id = $val['workflow_trans_id'];
                if ($workflow_structure_id == '' || $workflow_item_id == '') {
                    continue;
                }
                $fetch = $this->db->query("SELECT * FROM workflow where uid = '$uid' AND workflow_item_id = $workflow_item_id LIMIT 1");
                $cekWorkNow = $fetch->fetch();
                if ($cekWorkNow) {
                    $fetch2 = $this->db->query("select * FROM workflow_trans where uid = '$uid' AND workflow_item_id = $workflow_item_id AND workflow_trans_id = $workflow_trans_id");
                    $fetch2 = $fetch2->fetch();

                    $newUidPrev = $cekWorkNow['uid_prev'];

                    if ($fetch2['approve'] != $this->const['DOCUMENT_REJECT']) {
                        $newUidNext = $cekWorkNow['uid_next'];
                    } else {
                        $trano = $fetch2['item_id'];
                        $sql = "SELECT * FROM workflow_trans WHERE item_id = '$trano' AND approve in (100,150) ORDER BY date ASC LIMIT 1";
                        $fetch3 = $this->db->query($sql);
                        if ($fetch3) {
                            $submit = $fetch3->fetch();
                            $next = $submit['uid'];
                            $newUidNext = $next;
                        } else {
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
        } else
            echo "{success: true}";
    }

    public function approveAction() {
        $this->_helper->viewRenderer->setNoRender();
        $docsID = $this->getRequest()->getParam("trans");
        $uid = $this->getRequest()->getParam("user");
        $userID = $this->getRequest()->getParam("user_id");
        $comment = $this->getRequest()->getParam("comment");
        $uid_next = $this->getRequest()->getParam('uid_next');
        $workflow_item_id = $this->getRequest()->getParam('workflow_item_id');
        $workflow_id = $this->getRequest()->getParam('workflow_id');
        $workflow_item_type_id = $this->getRequest()->getParam('workflow_item_type_id');

        $this->bypassNext = ($this->_getParam("bypass_next") == 'true') ? true : false;

        $jsonData = $this->_getParam('json_data');

        // Multi trans
        $multiTrans = ($this->_getParam("multi") == 'true') ? true : false;
        // Use Workflow Generic Override
        $useOverride = ($this->_getParam("useOverride") == 'true') ? true : false;

        if ($uid_next != '' && $workflow_id != '' && $workflow_item_id != '') {
            $record['uid_next'] = $uid_next;
            $record['workflow_id'] = $workflow_id;
            $record['workflow_item_id'] = $workflow_item_id;
            $record['workflow_item_type_id'] = $workflow_item_type_id;
        }

        $docType = $this->getRequest()->getParam('docType');
        switch ($docType) {
            case 'TSHEET':
                $jsonTrano = $this->getRequest()->getParam("trans");
                $tranos = explode(",", $jsonTrano);
                $jsonTrans = $this->getRequest()->getParam("trans_id");
                $transID = explode(",", $jsonTrans);
                if (count($tranos) > 0 && count($transID) > 0) {
                    $ret = array();
                    foreach ($transID as $k => $v) {
                        $doc = $this->workflowTrans->fetchRow("workflow_trans_id = $v");
                        if ($doc) {
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
                    foreach ($ret as $k => $v) {
                        $docsID = $v;
                        $rets = $this->setStatus($docsID, $uid, $userID, $comment, true, $record, true);
                        if (is_array($rets)) {
                            if ($rets['success'] == false) {
                                $rets['params']['alltrano'] = $jsonTrano;

                                $rets['params']['alltrans'] = implode(",", $ret);

                                $json = Zend_Json::encode($rets);
                                echo $json;
                                return false;
                            } elseif ($rets['success'] == true && count($rets['user']) > 0) {
                                $rets['params'] = array();
                                $json = Zend_Json::encode($rets);
                                echo $json;
                                return false;
                            }
                            unset($ret[$k]);
                            $msg[] = Zend_Json::encode($rets);
                        } else {
                            unset($ret[$k]);
                            $msg[] = $rets;
                        }
                    }
                    $hasil = implode("<br>", $msg);

                    if (strpos($hasil, "true") !== false)
                        echo "{success: true}";
                    elseif (strpos($hasil, "false") !== false) {
                        if (strpos($hasil, "You already approved this Document") === false)
                            echo "{success: false, msg: '$hasil'}";
                        else
                            echo "{success: true}";
                    }
                }
                break;
            default:
                $msg = array();
                if (!$multiTrans) {
                    $rets = $this->setStatus($docsID, $uid, $userID, $comment, true, $record, true, $useOverride, $jsonData);
                    if (is_array($rets)) {
                        $json = Zend_Json::encode($rets);
                        echo $json;
                        return false;
                    } else {
                        $msg[] = $rets;
                    }
                } else {
                    $trans = $this->_getParam("json_trans");
                    if ($trans) {
                        $trans = Zend_Json::decode($trans);
                        foreach ($trans as $k => $v) {
                            $docsID = $v['trans_id'];
                            $rets = $this->setStatus($docsID, $uid, $userID, $comment, true, $record, true, $useOverride, $jsonData);
                            if (is_array($rets)) {
                                if ($rets['success'] == false) {
                                    $rets['params']['alltrans'] = implode(",", $trans);

                                    $json = Zend_Json::encode($rets);
                                    echo $json;
                                    return false;
                                } elseif ($rets['success'] == true && count($rets['user']) > 0) {
                                    $rets['params'] = array();
                                    $json = Zend_Json::encode($rets);
                                    echo $json;
                                    return false;
                                }
                                unset($trans[$k]);
                                $msg[] = Zend_Json::encode($rets);
                            } else {
                                unset($trans[$k]);
                                $msg[] = $rets;
                            }
                        }
                    }
                }
                $hasil = implode(",", $msg);

                if (strpos($hasil, "true") !== false)
                    echo "{success: true}";
                elseif (strpos($hasil, "false") !== false) {
                    if (strpos($hasil, "You already approved this Document") === false)
                        echo "{success: false, msg: '$hasil'}";
                    else
                        echo "{success: false, msg: '$hasil'}";
                }
                break;
        }
    }

    public function rejectAction() {
        $this->_helper->viewRenderer->setNoRender();
        $docsID = $this->getRequest()->getParam("trans");
        $uid = $this->getRequest()->getParam("user");
        $userID = $this->getRequest()->getParam("user_id");
        $comment = $this->getRequest()->getParam("comment");
        $uid_next = $this->getRequest()->getParam('uid_next');
        $workflow_item_id = $this->getRequest()->getParam('workflow_item_id');
        $workflow_id = $this->getRequest()->getParam('workflow_id');

        // Multi trans
        $multiTrans = ($this->_getParam("multi") != '') ? true : false;

        if ($uid_next != '' && $workflow_id != '' && $workflow_item_id != '') {
            $record['uid_next'] = $uid_next;
            $record['workflow_id'] = $workflow_id;
            $record['workflow_item_id'] = $workflow_item_id;
        }

        $docType = $this->getRequest()->getParam('docType');
        switch ($docType) {
            case 'TSHEET':
                $jsonTrano = $this->getRequest()->getParam("trans");
                $tranos = explode(",", $jsonTrano);
                $jsonTrans = $this->getRequest()->getParam("trans_id");
                $transID = explode(",", $jsonTrans);
                if (count($tranos) > 0 && count($transID) > 0) {
                    $ret = array();
                    foreach ($transID as $k => $v) {
                        $doc = $this->workflowTrans->fetchRow("workflow_trans_id = $v");
                        if ($doc) {
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
                    foreach ($ret as $k => $v) {
                        $docsID = $v;
                        $msg[] = $this->setStatus($docsID, $uid, $userID, $comment, false, $record, true);
                    }
                    $hasil = implode(",", $msg);

                    if (strpos($hasil, "true") !== false)
                        echo "{success: true}";
                    elseif (strpos($hasil, "false") !== false)
                        echo "{success: false, msg: \"Error!\"}";
                }
                break;
            default:
                if (!$multiTrans)
                    $this->setStatus($docsID, $uid, $userID, $comment, false, $record);
                else {
                    $trans = $this->_getParam("json_trans");
                    if ($trans) {
                        $trans = Zend_Json::decode($trans);
                        $msg = array();
                        foreach ($trans as $k => $v) {
                            $docsID = $v['trans_id'];
                            $rets = $this->setStatus($docsID, $uid, $userID, $comment, false, $record, true);
                            if (is_array($rets)) {
                                $rets['params']['alltrans'] = $trans;

                                $json = Zend_Json::encode($rets);
                                echo $json;
                                return false;
                            } else {
                                unset($trans[$k]);
                                $msg[] = $rets;
                            }
                        }
                        $hasil = implode(",", $msg);

                        if (strpos($hasil, "{success: true}") !== false)
                            echo "{success: true}";
                        elseif (strpos($hasil, "false") !== false)
                            echo "{success: false, msg: \"Error!\"}";
                    }
                }
                break;
        }

        echo "{success: true}";
    }

    protected function setStatus($docsID = '', $uid = '', $userID = '', $comment = '', $approval = true, $record = '', $silent = false, $useOverride = false, $jsonData = '') {
        $msgArray = array();
        $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$docsID");

        if ($docs) {
            $docs_array = $docs->toArray();
            $myClass = $this->model->getModelClass($docs_array['item_type']);

            $generic = $docs['generic'];
            $generic = (boolean) $generic;

            $captionID = $docs['caption_id'];
            $submitter = $this->workflow->fetchRow("workflow_id=" . $docs['workflow_id']);
            $user = $this->workflowHelper->checkWorkflowInDocs($docsID, $userID);
            if ($user) {
                if (!$generic) {
                    $lastStatus = $this->workflowHelper->getDocumentLastStatusAll($docs['item_id']);

                    if ($lastStatus['uid'] == $uid) {
                        $tgl = date("d M Y H:i:s", strtotime($lastStatus['date']));
                        if ($lastStatus['approve'] == 200 || $lastStatus['approve'] == 400) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already approved this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already approved this Document at $tgl !'}";
                        } else if ($lastStatus['approve'] == 100 || $lastStatus['approve'] == 150) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already submitted this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already submitted this Document at $tgl !'}";
                        } else if ($lastStatus['approve'] == 300) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already rejected this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already rejected this Document at $tgl !'}";
                        }
                        if ($silent)
                            return Zend_Json::encode($msgArray);
                        else
                            return false;
                    }
                }
                else {
                    $cekProject = $docs['prj_kode'];
                    $lastStatus = $this->workflowHelper->getDocumentLastStatusAllGeneric($docs['item_id'], $cekProject);

                    if ($lastStatus['uid'] == $uid) {
                        $tgl = date("d M Y H:i:s", strtotime($lastStatus['date']));
                        if ($lastStatus['approve'] == 200 || $lastStatus['approve'] == 400) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already approved this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already approved this Document at $tgl !'}";
                        } else if ($lastStatus['approve'] == 100 || $lastStatus['approve'] == 150) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already submitted this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already submitted this Document at $tgl !'}";
                        } else if ($lastStatus['approve'] == 300) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already rejected this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already rejected this Document at $tgl !'}";
                        }
                        if ($silent)
                            return Zend_Json::encode($msgArray);
                        else
                            return false;
                    }
                }

                if (!$generic) {
                    $myWork = $this->workflow->fetchRow("workflow_item_id=$user AND uid_prev='" . $submitter['uid'] . "'");
                    $insertArray['generic'] = 0;
                } else {
                    if (!$approval) {
                        $myWork = $this->workflowGeneric->getMyWorkflow($docs['workflow_id'], $docsID, true);
                        if ($myWork)
                            $myWork = $myWork->toArray();
                    }
                    else {
                        $myWork = $this->workflowGeneric->getMyWorkflow($docs['workflow_id'], $docsID);
                        if ($myWork == false) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "Approval error, Please contact IT Support.<br>Error : Workflow ID not found on workflow_generic table, workflow_trans_id : $docsID"
                            );

                            if ($silent)
                                return Zend_Json::encode($msgArray);
                            else
                                return false;
                        }
                        if ($myWork['person'] != '') {
                            //Filter UID, hilangkan UID approval dari list bila ada...
                            foreach ($myWork['person'] as $k => $v) {
                                if ($v['uid_next'] == QDC_User_Session::factory()->getCurrentUID())
                                    unset($myWork['person'][$k]);
                            }

                            $myWork['person'] = QDC_Common_Array::factory()->normalize($myWork['person']);

                            $found = false;
                            if ($record != '') {
                                foreach ($myWork['person'] as $k => $v) {
                                    if ($v['workflow_item_type_id'] == $record['workflow_item_type_id'] && $v['workflow_item_id'] == $record['workflow_item_id'] && $v['uid_next'] == $record['uid_next']) {
                                        unset($myWork['person']);
                                        $myWork = $v;
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            if (!$found) {
                                $return['success'] = true;
                                $return['prj_kode'] = $docs['prj_kode'];
                                $return['user'] = $myWork['person'];
                                if ($useOverride) {
                                    $wOveride = new Admin_Models_WorkflowGenericOverride();
                                    $over = $wOveride->getOverride($docs['item_type'], $docs['workflow_item_id'], $docs['prj_kode']);
                                    $myCurrentWorkflow = $this->workflowGeneric->getMyWorkflow($docs['workflow_id'], $docsID, true);
                                    $myLevel = $myCurrentWorkflow['level'];

                                    $override = $over['data'];
                                    $roleBased = ($over['role_based'] == 1) ? true : false;
                                    if ($roleBased) {
                                        $fetch = $myWork['person'];
                                        foreach ($override as $keyOver => $valOver) {
                                            $roleIdOver = $valOver['role_id'];
                                            foreach ($fetch as $keyFetch => $valFetch) {
                                                if ($valFetch['role_id'] == $roleIdOver) {
                                                    $oNext = $valOver;
                                                    //Override selanjutnya lebih dari 1 user
                                                    if ($oNext['user'] != '') {
                                                        if ($oNext['user'] != 'ALL') {
                                                            foreach ($oNext['user'] as $keyNextOverride => $valNextOverride) {
                                                                if ($valNextOverride['project'] != '') {
                                                                    foreach ($valNextOverride['project'] as $keyNextOverride2 => $valNextOverride2) {
                                                                        if ($valNextOverride2['prj_kode'] == $docs['prj_kode']) {
                                                                            $tmp = $valNextOverride;
                                                                            break;
                                                                        }
                                                                    }
                                                                } else {
                                                                    $tmp = $oNext;
                                                                    break;
                                                                }
                                                            }

                                                            //If found
                                                            if ($tmp) {
                                                                foreach ($myWork['person'] as $k2 => $v2) {
                                                                    if ($v2['uid_next'] == $tmp['uid']) {
                                                                        unset($myWork['person']);
                                                                        $myWork = $v2;
                                                                        $found = true;
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                                if ($found)
                                                    break;
                                            }
                                        }
                                    }
                                    else {
                                        $oCurrent = $override[$myLevel];
                                        //Override lebih dari 1 user
                                        if ($oCurrent['user'] != '') {
                                            foreach ($oCurrent['user'] as $k2 => $v2) {
                                                if ($v2['uid'] == $uid) {
                                                    $foundOverride = $v2;
                                                }
                                            }
                                        } else {
                                            $foundOverride = $oCurrent;
                                        }

                                        if ($foundOverride) {
                                            $oNext = $override[$myLevel + 1];
                                            $tmp = '';
                                            if ($oNext != '') {
                                                //Override selanjutnya lebih dari 1 user
                                                if ($oNext['user'] != '') {
                                                    if ($oNext['user'] != 'ALL') {
                                                        foreach ($oNext['user'] as $k2 => $v2) {
                                                            if ($v2['project'] != '') {
                                                                foreach ($v2['project'] as $k3 => $v3) {
                                                                    if ($v3['prj_kode'] == $docs['prj_kode']) {
                                                                        $tmp = $v2;
                                                                        break;
                                                                    }
                                                                }
                                                            } else {
                                                                $tmp = $oNext;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    if ($oNext['project'] != '') {
                                                        foreach ($oNext['project'] as $k2 => $v2) {
                                                            if ($v2['prj_kode'] == $docs['prj_kode']) {
                                                                $tmp = $oNext;
                                                                break;
                                                            }
                                                        }
                                                    } else
                                                        $tmp = $oNext;
                                                }


                                                if ($tmp != '') {
                                                    foreach ($myWork['person'] as $k2 => $v2) {
                                                        if ($v2['uid_next'] == $tmp['uid']) {
                                                            unset($myWork['person']);
                                                            $myWork = $v2;
                                                            $found = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                if (!$found) {
                                    if ($silent)
                                        return $return;
                                    else {
                                        $json = Zend_Json::encode($return);
                                        echo $json;
                                        return false;
                                    }
                                }
                            }
                        } elseif ($myWork['success'] === false) {
                            if ($silent)
                                return $myWork;
                            else {
                                $json = Zend_Json::encode($myWork);
                                echo $json;
                                return false;
                            }
                        }

//                            if ($useOverride && count($my))
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
                else {
                    $sql = "SELECT b.name FROM workflow_item a LEFT JOIN workflow_item_type b ON a.workflow_item_type_id = b.workflow_item_type_id WHERE a.workflow_item_id = $user";
                    $fetch = $this->db->query($sql);
                    $hasil = $fetch->fetch();

                    if ($hasil) {
                        $insertArray['item_type'] = $hasil['name'];
                    }
                }

                if ($approval) {
                    if ($myWork['is_end'])
                        $insertArray['approve'] = $this->const['DOCUMENT_FINAL'];
                    else
                        $insertArray['approve'] = $this->const['DOCUMENT_APPROVE'];
                } else
                    $insertArray['approve'] = $this->const['DOCUMENT_REJECT'];

                if ($insertArray['approve'] == $this->const['DOCUMENT_REJECT']) {
                    $trano = $docs['item_id'];
                    $sql = "SELECT * FROM workflow_trans WHERE item_id = '$trano' AND approve in (100,150) ORDER BY date ASC LIMIT 1";
                    $fetch3 = $this->db->query($sql);
                    if ($fetch3) {
                        $submit = $fetch3->fetch();
                        $next = $submit['uid'];
                        $insertArray['uid_next'] = $next;
                    } else {
                        if (!$generic) {
                            $workid = $myWork['workflow_item_id'];
                            $sql = "SELECT * FROM workflow WHERE workflow_item_id = $workid AND is_start = 1";
                            $fetch3 = $this->db->query($sql);
                            $submit = $fetch3->fetch();
                            $next = $submit['uid'];
                            $insertArray['uid_next'] = $next;
                        } else {
                            $workid = $myWork['workflow_item_id'];
                            $workTypeId = $myWork['workflow_item_type_id'];
                            $prjKode = $myWork['prj_kode'];
                            $sql = "SELECT * FROM workflow_generic WHERE workflow_item_id = $workid AND is_start = 1 AND prj_kode = '$prjKode' AND level = 0";
                            $fetch3 = $this->db->query($sql);
                            $submit = $fetch3->fetch();
                            $user = $this->masterRole->getUserFromRoleAndProject($submit['role_id'], $prjKode);
                            if ($user) {
                                $next = $user[0]['uid'];
                            }
                            $insertArray['uid_next'] = $next;
                        }
                    }
                }

                if ($insertArray['item_type'] == 'AFE' && $insertArray['approve'] == $this->const['DOCUMENT_APPROVE']) {
                    if ($this->session->userName == 'jonhar' || $this->session->userName == 'hasrul') {
                        $trano = $docs['item_id'];
                        $afeh = new ProjectManagement_Models_AFEh();
                        $fetch = $afeh->fetchRow("trano = '$trano'");
                        if ($fetch) {
                            if ((-1 * $fetch['margin']) > -5 && $fetch['margin_last'] > -2) {
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

                if ($captionID)
                    $insertArray['caption_id'] = $captionID;

                //check bypass..
                $useBypass = false;
                if ($this->bypassNext && ($insertArray['approve'] == $this->const['DOCUMENT_APPROVE'] || $insertArray['approve'] == $this->const['DOCUMENT_FINAL'])) {
                    //Cek nominal limit untuk transaksi
                    $limit = QDC_Workflow_Nominal::factory(array(
                                "uid" => $insertArray['uid_next'],
                                "item_type" => $insertArray['item_type'],
                                "prj_kode" => $myWork['prj_kode']
                            ))->checkLimit();
                    if ($limit !== false) {
                        //Ambil total nilai transaksi untuk di bypass..
                        $totalTrans = QDC_Document_Model::factory(array(
                                    "trano" => $insertArray['item_id'],
                                    "item_type" => $insertArray['item_type'],
                                ))->getTransactionTotal();

                        $isIDR = true;
                        //Cek valuta transaksi...
                        $valuta = QDC_Document_Model::factory(array(
                                    "trano" => $insertArray['item_id'],
                                    "item_type" => $docs['item_type'],
                                ))->getTable()->getTransactionCurrency();
                        if ($valuta != 'IDR') {
                            $rate = QDC_Document_Model::factory(array(
                                        "trano" => $insertArray['item_id'],
                                        "item_type" => $docs['item_type'],
                                    ))->getTable()->getTransactionCurrencyRate();

                            $totalTrans = $totalTrans * $rate;
                        }

                        if ($totalTrans !== false) {
                            if ($totalTrans <= $limit) {
                                $useBypass = true;
                                $isFinal = false;
                                $w = new Admin_Models_Workflow();
                                if (!$generic) {
                                    if ($w->isPersonEnd($insertArray['uid_next'], $myWork['workflow_item_id'], $insertArray['prj_kode'], $uid))
                                        $isFinal = true;
                                }

                                if ($isFinal)
                                    $insertArray['approve'] = $this->const['DOCUMENT_FINAL'];
                            }
                        }
                    }
                }

                $result = $this->workflowTrans->insert($insertArray);

                //updating approve status @transaction table
                $this->workflowTrans->updateStatusApprove($myClass, $insertArray['item_type'], $insertArray['item_id'], $insertArray['approve']);

                if ($this->bypassNext && $useBypass && ($insertArray['approve'] == $this->const['DOCUMENT_APPROVE'] || $insertArray['approve'] == $this->const['DOCUMENT_FINAL'])) {
                    if ($isFinal === false) {
                        $w = new Admin_Models_Workflow();

                        $nextPerson = $w->getPersonWorkflow($insertArray['uid_next'], $myWork['workflow_item_id'], $insertArray['prj_kode'], $uid);
                        if ($nextPerson !== false) {
                            $signNext = $this->token->getDocumentSignatureByUserID($insertArray['uid_next']);

                            //Interval 2 seconds for next approval..
                            $tglNow = new DateTime('NOW');
                            $tglNow->add(new DateInterval('PT2S'));
                            $tglNext = $tglNow->format("Y-m-d H:i:s");

                            $insertArrayNext = array(
                                'item_id' => $docs['item_id'],
                                'item_type' => $insertArray['item_type'],
                                'generic' => $insertArray['generic'],
                                'uid_next' => $nextPerson['uid_next'],
                                'uid_prev' => $uid,
                                "uid" => $insertArray['uid_next'],
                                "signature" => $signNext['signature'],
                                'date' => $tglNext,
                                'ip' => $_SERVER["REMOTE_ADDR"],
                                'comment' => "",
                                'computer_name' => gethostbyaddr($insertArray['ip']),
                                'browser' => $_SERVER["HTTP_USER_AGENT"],
                                'workflow_id' => $nextPerson['workflow_id'],
                                'workflow_item_id' => $nextPerson['workflow_item_id'],
                                'workflow_structure_id' => $nextPerson['workflow_structure_id'],
                                'prj_kode' => $nextPerson['prj_kode'],
                                'approve' => $this->const['DOCUMENT_APPROVE'],
                                'caption_id' => ($captionID) ? $captionID : null
                            );
                            $lastID = $this->workflowTrans->insert($insertArrayNext);
                            $this->workflowTrans->updateStatusApprove($myClass, $insertArrayNext['item_type'], $insertArrayNext['item_id'], $insertArrayNext['approve']);
                        }
                    } else {
                        $lastID = $result;
                    }

                    $l = new Admin_Models_LogBatasNilaiTransaksi();
                    $l->insert(array(
                        "uid_next" => $insertArray['uid_next'],
                        "uid" => $uid,
                        "item_type" => $insertArray['item_type'],
                        "prj_kode" => $myWork['prj_kode'],
                        "trano" => $insertArray['item_id'],
                        "tgl" => date("Y-m-d H:i:s"),
                        "workflow_item_id" => $myWork['workflow_item_id'],
                        "workflow_id_next" => $lastID,
                        "total_limit" => $limit,
                        "total" => $totalTrans
                    ));
                }

                if ($insertArray['approve'] == $this->const['DOCUMENT_FINAL']) {
                    if ($insertArray['item_type'] == '') {
                        $sql = "SELECT b.name FROM workflow_item a LEFT JOIN workflow_item_type b ON a.workflow_item_type_id = b.workflow_item_type_id WHERE a.workflow_item_id = $user";
                        $fetch = $this->db->query($sql);
                        $hasil = $fetch->fetch();

                        if ($hasil) {
                            $itemType = $hasil['name'];
                        }
                    } else
                        $itemType = $insertArray['item_type'];

                    $addMsg = $tranoPrint = '';
                    
                    switch ($itemType) {
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
                        case 'RPI':
                            $rpi = new Default_Models_RequestPaymentInvoice();
                            $tranoPrint = $rpi->transferJurnalAP($docs['item_id'], $itemType);
                            $addMsg = "BPV Number = " . $tranoPrint;
                            break;
                        case 'RPIO':
                            $rpi = new Default_Models_RequestPaymentInvoice();
                            $tranoPrint = $rpi->transferJurnalAP($docs['item_id'], $itemType);
                            $addMsg = "BPV Number = " . $tranoPrint;
                            break;
                        case 'ASF':
                            $asf = new Default_Models_AdvanceSettlementFormH();
                            $asf->updateArfAging($docs['item_id']);
                            break;
                        case 'AFE':
                            $cboq3h = new Default_Models_MasterCboq3H();
                            $afeh = new ProjectManagement_Models_AFEh();

                            $isSwitching = $afeh->checkSwitching($docs['item_id']);
                            if (!$isSwitching)
                                $cboq3h->transferAFEtoBOQ3($docs['item_id']);
                            else
                                $cboq3h->transferAFESwitchingtoBOQ3($docs['item_id']);
                            $addBoq2 = new Default_Models_MasterAddco();
                            $addBoq2->transferAddRevenue($docs['item_id']);
                            break;
                        case 'PO':
                        case 'POO':
                            $po = new Default_Models_ProcurementPoh();
                            $po->changePODate($docs['item_id']);

                            //Kirim notification message ke Submitter
                            // $notif = new Default_Models_UserNotification();
                            // $notif->sendNotificationFinalApproval($itemType,$docs['item_id'],"PO with trano : {$docs['item_id']} has been Final Approved.",1,$docs['item_id']);
                            break;
                        case 'BRF':
                            //Kirim notification message ke Submitter
//                                $notif = new Default_Models_UserNotification();
//                                $notif->sendNotificationFinalApproval($itemType,$docs['item_id'],"Business Trip Request with trano : {$docs['item_id']} has been Final Approved. Please click <a href=\"#\" onclick=\"BRFPayment('" . $docs['item_id'] . "');\"><b style=\"color:blue;\">Here</b></a> to fund Your 1st Payment.",0,$docs['item_id'], true);
//                            $brfPayment = new Procurement_Models_BusinessTripPayment();
//                            if ($jsonData) {
//                                $ret = $brfPayment->editRequest($jsonData);
//                            }
//                            $payTrano = $brfPayment->newPayment($docs['item_id']);
//                            if ($payTrano != false) {
//                                $addMsg = "Business Trip Request : " . $docs['item_id'] . " has been Final Approved, Please create Bank Payment Voucher (BPV) for trano : " . $payTrano;
////                                    $notif->sendNotificationFinalApproval($itemType,$docs['item_id'],"Business Trip Request with trano : {$docs['item_id']} has been Final Approved. Your payment number : $payTrano, please proceed to Finance Department.",0,$docs['item_id'], true);
//                                $msgArray['data'] = array(
//                                    "payment_trano" => $payTrano
//                                );
//                                $submitter = $this->workflowTrans->getDocumentSubmitter($docs['item_id']);
//                                $officer = $this->workflowTrans->getFinalApproval($docs['item_id']);
//                                $conv = new Default_Models_Conversation();
//                                $m = "Business Trip Request with trano : {$docs['item_id']} has been Final Approved. Your payment number : $payTrano, please proceed to Finance Department.";
//                                $conv->sendMessageFromSystem('SYSTEM', $submitter['uid'], $m, $docs['item_id'], $print, $trano_print);
//                                $conv->sendMessageFromSystem('SYSTEM', $officer['uid'], $m, $docs['item_id'], $print, $trano_print);
//                                $this->workflowTrans->updateStatusApprove($myClass, $insertArray['item_type'], $insertArray['item_id'], $insertArray['approve']);
//                            }
//                                $notif->sendNotificationFinalApproval($itemType,$docs['item_id'],"Business Trip Request with trano : {$docs['item_id']} has been Final Approved.",0,$docs['item_id'], true);
                            break;
                        case 'PPNREM':
//                                $ppnrem = new Finance_Models_PpnReimbursementD();
//                                $tranoPrint = $ppnrem->transferJurnalAP($docs['item_id'],$itemType);
//                                $addMsg = "BPV Number = " . $tranoPrint;
                            break;
                        case 'OCA':
                            $o = new Finance_Models_TemporaryCharging();
                            $tranoJurnal = $o->transferOverheadCost($docs['item_id']);
                            $addMsg = "Trano Spend Money = " . $tranoJurnal;
                            break;
                        case 'CRPI':
                            $o = new Finance_Models_AccountingCancelRPI();
                            $tranoJurnal = $o->docancelrpi($docs['item_id']);

                            break;
                        case 'UHB':
                            $uhb = new Logistic_Models_LogisticTemporaryBarang();
                            $trano = $uhb->doupdateharga($docs['item_id']);

                            $submitter = $this->workflowTrans->getDocumentSubmitter($docs['item_id']);
                            $officer = $this->workflowTrans->getFinalApproval($docs['item_id']);
                            $conv = new Default_Models_Conversation();
                            $m = "Your Product price has been updated!";
                            $conv->sendMessageFromSystem('SYSTEM', $submitter['uid'], $m, $docs['item_id'], $print, $trano_print);
                            $conv->sendMessageFromSystem('SYSTEM', $officer['uid'], $m, $docs['item_id'], $print, $trano_print);


                            break;
                        case 'iSUPO':
                        case 'iSUP':                            
                            $o = new Logistic_Models_LogisticInputSupplier();
                            $o->setAvgPrice($docs['item_id']);

                            break;
                        case 'TBOQ':                            
                            $o = new Sales_Models_TemporaryTransferBudget();
                            $o->transferBudgetFinal($docs['item_id']);
                               
                            //send 
                            //
                            $submitter = $this->workflowTrans->getDocumentSubmitter($docs['item_id']);
                            $officer = $this->workflowTrans->getFinalApproval($docs['item_id']);
                            $conv = new Default_Models_Conversation();
                            $m = "Transfer Budget transaction has been done! Please proceed to Finance Department for next execution process.";
                            $conv->sendMessageFromSystem('SYSTEM', $submitter['uid'], $m, $docs['item_id']);
                            $conv->sendMessageFromSystem('SYSTEM', $officer['uid'], $m, $docs['item_id']);
//
//                            
                            break;
                        case 'iSUPO':
                        case 'iSUP':                            
                            $o = new Logistic_Models_LogisticInputSupplier();
                            $o->setAvgPrice($docs['item_id']);

                            break;
                    }

                    //Send Email notification
                    $notif = new Default_Models_UserNotification();
                    $notif->sendEmailNotificationFinalApproval($docs['item_type'], $docs['item_id'], $docs['prj_kode'], $addMsg, $tranoPrint);
                }

                if ($insertArray['approve'] == $this->const['DOCUMENT_FINAL']) {
                    $trano = $insertArray['item_id'];
                    $this->workflowTrans->update(array("final" => 1), "item_id = '$trano'");
                }

                if ($insertArray['approve'] == $this->const['DOCUMENT_REJECT']) {
                    if ($insertArray['item_type'] == '') {
                        $sql = "SELECT b.name FROM workflow_item a LEFT JOIN workflow_item_type b ON a.workflow_item_type_id = b.workflow_item_type_id WHERE a.workflow_item_id = $user";
                        $fetch = $this->db->query($sql);
                        $hasil = $fetch->fetch();

                        if ($hasil) {
                            $itemType = $hasil['name'];
                        }
                    } else
                        $itemType = $insertArray['item_type'];
                    $tranoReject = $docs['item_id'];
                    $arrayData = array();
                    switch ($itemType) {
//                        case 'PR':
//                        case 'PRO':
//                            $model = new Default_Models_ProcurementRequest();
//                            $data = $model->fetchALl("trano = '$tranoReject'");
//                            if ($data) {
//                                $arrayData = $data->toArray();
//                            }
//                            $model->update(array(
//                                "qty" => 0
//                                    ), "trano = '$tranoReject'");
//                            break;
                        case 'PO':
                        case 'POO':
                            $model = new Default_Models_ProcurementPod();
                            $rpi = new Default_Models_RequestPaymentInvoice();
                            $data = $model->fetchAll("trano='$tranoReject'");

                            if ($data) {
                                $arrayData = $data->toArray();

                                //Cek apabila sudah ada rpi nya...
                                $select = $this->db->select()
                                        ->from(array($rpi->__name()))
                                        ->where("po_no = '$tranoReject'");
                                $cek = $this->db->fetchRow($select);
                                if (!$cek) {
                                    $model->update(array(
                                        "qty" => 0,
                                        "qtyspl" => 0,
                                            ), "trano = '$tranoReject'");
                                }
                            }
                            break;
//                            case 'RPIO':
//                            case 'RPI':
//                            $rpi = new Default_Models_RequestPaymentInvoice();
//                            $tranoPrint = $rpi->reversingJournalEntries($docs['item_id']);
//                            break;
                    }

                    if (count($arrayData) > 0) {
                        $json = Zend_Json::encode($arrayData);
                        $logTransReject = new Default_Models_LogTransactionReject();
                        $logTransReject->insert(
                                array(
                                    "trano" => $tranoReject,
                                    "tgl" => date("Y-m-d H:i:s"),
                                    "uid" => $insertArray['uid_next'],
                                    "uid_reject" => $uid,
                                    "data" => $json
                                )
                        );
                    }
                }

                $msgArray['success'] = true;
                $msgArray['approval'] = true;
            } else
                $msg = "{success: false, msg: 'Access Denied!'}";
        } else
            $msg = "{success: false, msg: 'No Such Document!'}";

        if ($silent)
//            return Zend_Json::encode($msgArray);
            return $msgArray;
        else
            echo $msg;
    }

    public function progressAction() {
        $this->_helper->viewRenderer->setNoRender();
        for ($i = 0; $i < 10000; $i++) {
            echo "{num: $i}";
        }
    }

    public function workflowgenericAction() {
        
    }

    public function workflowprocessgenericAction() {
        
    }

    public function workflowprocessgenericbyoneAction() {
        
    }

    public function addworkflowprocessgenericAction() {
//		$workflow_item_type_id = $this->request->getParam('workflow_item_type_id');
        $workflow_item_name = $this->request->getParam('workflow_item_name');
        $workflow_item_id = $this->request->getParam('workflow_item_id');
        $byone = $this->request->getParam('byone');

        if ($byone)
            $byone = true;
        else
            $byone = false;

        if (!$byone) {
//		$level = $this->workflowGeneric->getTreeRouteGroup($workflow_item_type_id);
            $level = $this->workflowGeneric->getTreeRouteGroupNew($workflow_item_name);
            if ($level) {
                $result = array();
                foreach ($level as $key => $val) {
                    //				$people = $this->workflowGeneric->getTreeRoute($workflow_item_type_id,$val['level']);
                    $people = $this->workflowGeneric->getTreeRouteNew($workflow_item_name, $val['level']);
                    foreach ($people as $key2 => $val2) {
                        $result[$key][$key2]['role_name'] = $val2['role_name'];
                        $result[$key][$key2]['role_id'] = $val2['role_id'];
                    }
                }
            }

            //		$this->view->workflow_item_type_id = $workflow_item_type_id;
            $this->view->workflow_item_name = $workflow_item_name;
        } else {
            $level = $this->workflowGeneric->getTreeRouteGroupNewByOne($workflow_item_id);
            if ($level) {
                $result = array();
                foreach ($level as $key => $val) {
                    //				$people = $this->workflowGeneric->getTreeRoute($workflow_item_type_id,$val['level']);
                    $people = $this->workflowGeneric->getTreeRouteNewByOne($workflow_item_id, $val['level']);
                    foreach ($people as $key2 => $val2) {
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

    public function listroleAction() {
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

        $role = new Admin_Models_Masterroletype();
        $return['posts'] = $role->fetchAll($where, array($sort . " " . $dir), $limit, $offset)->toArray();
        $return['count'] = $role->fetchAll($where)->count();
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function submitworkflowprocessgenericAction() {
        $workflowStruct = new Admin_Models_Workflowstructure();
        $this->_helper->viewRenderer->setNoRender();
        $id_person = $this->getRequest()->getParam('id_person');

        $id_person = Zend_Json::decode($id_person);

        $is_start = $_POST['is_start'];
        $byone = $this->getRequest()->getParam('byone');
        if ($byone)
            $byone = true;
        else
            $byone = false;
        $dataArray = array();
        $i = 0;

        if ($byone) {
            $id_workflow = $this->request->getParam('workflow_item_id');
            $sql = "SELECT COALESCE(MAX(level),0) as jumlah FROM workflow_generic WHERE workflow_item_id = $id_workflow";
        } else {
            $id_workflow = $this->request->getParam('workflow_item_name');
            $sql = "SELECT COALESCE(MAX(level),0) as jumlah FROM workflow_generic WHERE workflow_item_name = '$id_workflow'";
        }
        $fetch = $this->db->query($sql);
        $cekExist = $fetch->fetch();

        $errorDocumentMsg = array();

        $prj_kode = $this->workflowItem->getProject($id_workflow, 1);
        if ($cekExist['jumlah'] > 0) {
            //Get last document in workflow first...
            if ($byone) {
                $thePerson = $this->genericNormalizePerson($id_person);
                $select = $this->db->select()
                        ->from(array($this->workflowTrans->__name()), array(
                            "uid_next",
                        ))
                        ->where("workflow_item_id=?", $id_workflow)
                        ->where("final = 0")
                        ->where("generic = 1")
                        ->where("uid_next IS NOT NULL")
                        ->order(array("date DESC"));
                $select = $this->db->select()
                        ->from(array($select), array(
                            "total" => new Zend_Db_Expr("COUNT(*)"),
                            "uid_next"
                        ))
                        ->group(array("uid_next"));

                $data = $this->db->fetchAll($select);
                if ($data) {
                    foreach ($data as $key => $val) {
                        $personRole = $this->masterRole->getRoleUidProject($val['uid_next'], $prj_kode);
                        if ($personRole) {
                            foreach ($personRole as $k => $v) {
                                $res = QDC_Common_Array::factory()->searchSimple($thePerson, $v['id_role']);
                                if (!$res) {
                                    $mt = new Admin_Models_Masterroletype();
                                    $roleName = $mt->getNameByID($v['id_role']);
                                    $errorDocumentMsg[] = array(
                                        "msg" => wordwrap($val['total'] . " Documents still waiting Approval from " . QDC_User_Ldap::factory(array("uid" => $val['uid_next']))->getName() . ", Role : " . $roleName['display_name'], 40, "<br>"),
                                        "uid" => $val['uid_next'],
                                        "person" => QDC_User_Ldap::factory(array("uid" => $val['uid_next']))->getName(),
                                        "total" => $val['total']
                                    );
                                }
                            }
                        }
                    }
                }
                $this->workflowGeneric->delete("workflow_item_id = $id_workflow");
            }
        }

        $i = 0;
        if ($id_person == '')
            return false;
        foreach ($id_person as $key => $val) {
            foreach ($val as $key2 => $val2) {
                if ($val2 != '' && isset($val2)) {
                    if (!$byone)
                        $sql = "SELECT * FROM workflow_item WHERE name = '$id_workflow'";
                    else
                        $sql = "SELECT * FROM workflow_item WHERE workflow_item_id = $id_workflow";

                    $fetch = $this->db->query($sql);
                    if ($fetch) {
                        $fetch = $fetch->fetchAll();
                        foreach ($fetch as $k => $v) {
                            if ($i == 0) {
                                $dataArray['is_start'] = 1;
                                $dataArray['is_end'] = 0;
                            } else {
                                if ($is_start[$key][$key2] == 'on')
                                    $dataArray['is_start'] = 1;
                                else
                                    $dataArray['is_start'] = 0;

                                if ($i == (count($id_person) - 1))
                                    $dataArray['is_end'] = 1;
                                else
                                    $dataArray['is_end'] = 0;
                            }

                            if ($dataArray['is_end'] == 1) {
                                if ($_POST['is_executor'] == 'on') {
                                    $dataArray['is_executor'] = 1;
                                }
                            }
                            $dataArray["level"] = $i;
                            $dataArray["role_id"] = $val2;
                            $sql = "SELECT * FROM master_role_type WHERE id = $val2";
                            $fetch2 = $this->db->query($sql);
                            if ($fetch2) {
                                $fetch2 = $fetch2->fetch();
                                $dataArray["role_name"] = $fetch2['display_name'];
                            }

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


        if (count($errorDocumentMsg) > 0) {
            $thePerson = $this->genericNormalizePerson($id_person);
            $mt = new Admin_Models_Masterroletype();
            $persons = array();
            foreach ($thePerson as $v) {
                $uids = $mt->getPerson($v, $prj_kode);
                foreach ($uids as $k2 => $v2) {
                    if (QDC_User_Ldap::factory(array("uid" => $v2['uid']))->isExist()) {
                        $persons[] = array(
                            "uid" => $v2['uid'],
                            "name" => QDC_User_Ldap::factory(array("uid" => $v2['uid']))->getName(),
                            "role" => $mt->getName($v)
                        );
                    }
                }
            }

            $return = array(
                "success" => false,
                "error_document" => $errorDocumentMsg,
                "person_list" => $persons,
                "workflow_item_id" => $id_workflow
            );
        } else {
            $return = array(
                "success" => true
            );
        }
//		    $this->_helper->redirector('menu','index','default');

        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listworkflowitemgenericAction() {
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

        if ($search != '' && $option != '') {
            $search = " AND $option LIKE '%$search%'";
        }
        $return['posts'] = $this->workflowItem->listWorkflowItemGeneric($sort, $dir, $limit, $offset, $byone, $search);

        $sql = $this->db->select()
                ->from(array('w' => 'workflow_item'))
                ->where("w.generic = 1 $search")
                ->group(array("w.name"));

        if ($byone)
            $sql->reset(Zend_Db_Select::GROUP);

        $result = $this->db->fetchAll($sql);
        $return['count'] = count($result);



        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showworkflowgenericrouteAction() {
        $workflow_item_id = $this->getRequest()->getParam("workflow_item_id");
        $cek = $this->workflowGeneric->fetchAll("workflow_item_id = $workflow_item_id");
        $array = array();
        $workflow_name = '';
        if ($cek) {
            $cek = $cek->toArray();
            $workflow_name = $cek[0]['workflow_item_name'];
            foreach ($cek as $k => $v) {
                $level = $v['level'];
                $array[$level][] = $v['role_name'];
            }
        }
        $this->view->data = $array;
        $this->view->name = $workflow_name;
    }

    public function clearmemcacheAction() {
        $this->_helper->viewRenderer->setNoRender();
        //delete workflow from memcache
        $memcacheWork = Zend_Registry::get('MemcacheWorkflow');
        $memcacheWork->clean(
                Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('WORKFLOW')
        );
        $memcacheWork->clean(
                Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('WORFKLOW')
        );
    }

    public function switchPersonAction() {
        
    }

    public function cloneAction() {
        
    }

    public function doCloneAction() {

        $this->_helper->viewRenderer->setNoRender();

        $json = $this->request->getParam('json');
        $prj_kode_target = $this->request->getParam('prj_kode');

        if ($json)
            $json = Zend_Json::decode($json);

        $return['success'] = false;

        foreach ($json as $k => $v) {
            $workflowItemId = $v['workflow_item_id'];
            $workflowItemTypeId = $v['workflow_item_type_id'];
            $prj_kode = $v['prj_kode'];
            $name = $v['name'];
            $desc = $v['desc'];
            $sit_kode = $v['sit_kode'];

            $cek = $this->workflowItem->fetchRow("workflow_item_type_id = $workflowItemTypeId AND name = '$name' AND prj_kode = '$prj_kode_target'");
            if ($cek) {
                if (strpos(strtolower($name), "clone") === false)
                    $name .= " Clone";
            }

            //Insert new workflow Item to destination project code
            $wItem = $this->workflowItem->fetchRow("workflow_item_id = $workflowItemId")->toArray();
            $arrayInsert = $wItem;
            unset($arrayInsert['workflow_item_id']);
            $arrayInsert['name'] = $name;
            $arrayInsert['prj_kode'] = $prj_kode_target;
            $newWorkflowItemId = $this->workflowItem->insert($arrayInsert);

            $tmpWorkflow = array();

            if ($wItem['generic'] == 1) {

                $existing = $this->workflowGeneric->fetchAll("workflow_item_id = $workflowItemId");
                if ($existing) {
                    $existing = $existing->toArray();

                    foreach ($existing as $k2 => $v2) {
                        unset($v2['workflow_id']);
                        $v2['prj_kode'] = $prj_kode_target;
                        $v2['workflow_item_id'] = $newWorkflowItemId;

                        $this->workflowGeneric->insert($v2);
                    }
                    $return['success'] = true;
                }
            } else {
                //get the workflow structure
                $struct = $this->workflowStructure->fetchAll("workflow_item_id = $workflowItemId");
                if ($struct) {
                    $struct = $struct->toArray();
                    foreach ($struct as $k2 => $v2) {
                        $workflowStructId = $v2['id'];
                        $roleId = $v2['master_role_id'];
                        $level = $v2['level'];
                        //get existing role
                        $cekRole = $this->masterRole->fetchRow("id = $roleId");
                        if ($cekRole) {
                            $cekRole = $cekRole->toArray();
                            $idUser = $cekRole['id_user'];
                            $idRole = $cekRole['id_role'];
                            $login = new Admin_Models_Masterlogin();
                            $user = $login->fetchRow("id = $idUser");
                            if ($user)
                                $user = $user->toArray();
                            $uid = $user['uid'];

                            //check role at destination project
                            $cekRole = $this->masterRole->fetchRow("id_user = $idUser AND prj_kode = '$prj_kode_target'");
                            if ($cekRole) {
                                $cekRole = $cekRole->toArray();
                                $newRoleId = $cekRole['id'];
                            } else {
                                //Create new master role
                                $arrayInsert = array(
                                    "id_role" => $idRole,
                                    "id_user" => $idUser,
                                    "prj_kode" => $prj_kode_target,
                                    "active" => 1
                                );
                                $newRoleId = $this->masterRole->insert($arrayInsert);
                            }

                            //Insert new workflow structure
                            $arrayInsert = $v2;
                            $arrayInsert['id'] = null;
                            $arrayInsert['workflow_item_id'] = $newWorkflowItemId;
                            $arrayInsert['master_role_id'] = $newRoleId;
                            $newWorkflowStruct = $this->workflowStructure->insert($arrayInsert);

                            //get the workflow
                            $workflow = $this->workflow->fetchAll("workflow_item_id = $workflowItemId AND master_role_id = $roleId AND workflow_structure_id = $workflowStructId AND prj_kode = '$prj_kode'");
                            if ($workflow->toArray()) {
                                $workflow = $workflow->toArray();
                                foreach ($workflow as $key => $v) {
                                    $arrayInsert = $v;
                                    $tmp = $arrayInsert;
                                    unset($arrayInsert['workflow_id']);
                                    $arrayInsert['master_role_id'] = $newRoleId;
                                    $arrayInsert['prj_kode'] = $prj_kode_target;
                                    $arrayInsert['workflow_item_id'] = $newWorkflowItemId;
                                    $arrayInsert['workflow_structure_id'] = $newWorkflowStruct;
                                    $arrayInsert['prev'] = 0;
                                    $arrayInsert['next'] = 0;
                                    $newWorkId = $this->workflow->insert($arrayInsert);

                                    $tmpWorkflow[] = array(
                                        "id" => $newWorkId,
                                        "id_user" => $idUser,
                                        "level" => $level,
                                        "prev" => $tmp
                                    );
                                    $return['success'] = true;
                                }
                            }
                        }
                    }
                }

                //Loop for new workflow
                foreach ($tmpWorkflow as $k2 => $v2) {
                    $prev = $v2['prev'];
                    $id = $v2['id'];
                    $workflow = $this->workflow->fetchRow("workflow_id = " . $prev['workflow_id'])->toArray();
                    if ($workflow) {
                        $structPrev = $workflow['prev'];
                        if ($structPrev > 0) {
                            $struct = $this->workflowStructure->fetchRow("workflow_item_id = $workflowItemId AND id = $structPrev");
                            if ($struct) {
                                $struct = $struct->toArray();
                                $roleIdPrev = $struct['master_role_id'];
                                $userPrev = $this->masterRole->getUserFromRoleId($roleIdPrev);
                                $uidPrev = $userPrev['uid'];
                                $workflowCurrent = $this->workflow->fetchRow("workflow_item_id = $newWorkflowItemId AND uid = '$uidPrev'");
                                if (!$workflowCurrent)
                                    continue;
                                $workflowCurrent = $workflowCurrent->toArray();
                                $structPrevCurrent = $workflowCurrent['workflow_structure_id'];
                                $this->workflow->update(array(
                                    "prev" => $structPrevCurrent
                                        ), "workflow_id = $id");
                            }
                        }

                        $structNext = $workflow['next'];
                        if ($structNext > 0) {
                            $struct = $this->workflowStructure->fetchRow("workflow_item_id = $workflowItemId AND id = $structNext");
                            if ($struct) {
                                $struct = $struct->toArray();
                                $roleIdPrev = $struct['master_role_id'];
                                $userPrev = $this->masterRole->getUserFromRoleId($roleIdPrev);
                                $uidPrev = $userPrev['uid'];
                                $workflowCurrent = $this->workflow->fetchRow("workflow_item_id = $newWorkflowItemId AND uid = '$uidPrev'");
                                if (!$workflowCurrent)
                                    continue;
                                $workflowCurrent = $workflowCurrent->toArray();

                                $structPrevCurrent = $workflowCurrent['workflow_structure_id'];
                                $this->workflow->update(array(
                                    "next" => $structPrevCurrent
                                        ), "workflow_id = $id");
                            }
                        }
                    }
                }
            }
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getNextWorkflow($params = array()) {
        if ($params != '') {
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }
        }
        $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$docsID");
        if ($docs) {
            $generic = $docs['generic'];
            $generic = (boolean) $generic;

            $submitter = $this->workflow->fetchRow("workflow_id=" . $docs['workflow_id']);
            $user = $this->workflowHelper->checkWorkflowInDocs($docsID, $userID);
            if ($user) {
                if (!$generic) {
                    $lastStatus = $this->workflowHelper->getDocumentLastStatusAll($docs['item_id']);

                    if ($lastStatus['uid'] == $uid) {
                        $tgl = date("d M Y H:i:s", strtotime($lastStatus['date']));
                        if ($lastStatus['approve'] == 200 || $lastStatus['approve'] == 400) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already approved this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already approved this Document at $tgl !'}";
                        } else if ($lastStatus['approve'] == 100 || $lastStatus['approve'] == 150) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already submitted this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already submitted this Document at $tgl !'}";
                        } else if ($lastStatus['approve'] == 300) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already rejected this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already rejected this Document at $tgl !'}";
                        }
                        return false;
                    }
                } else {
                    $cekProject = $docs['prj_kode'];
                    $lastStatus = $this->workflowHelper->getDocumentLastStatusAllGeneric($docs['item_id'], $cekProject);

                    if ($lastStatus['uid'] == $uid) {
                        $tgl = date("d M Y H:i:s", strtotime($lastStatus['date']));
                        if ($lastStatus['approve'] == 200 || $lastStatus['approve'] == 400) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already approved this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already approved this Document at $tgl !'}";
                        } else if ($lastStatus['approve'] == 100 || $lastStatus['approve'] == 150) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already submitted this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already submitted this Document at $tgl !'}";
                        } else if ($lastStatus['approve'] == 300) {
                            $msgArray = array(
                                "success" => false,
                                "msg" => "You already rejected this Document at $tgl !"
                            );
//                                $msg = "{success: false, msg: 'You already rejected this Document at $tgl !'}";
                        }
                        return false;
                    }
                }

                if (!$generic) {
                    $myWork = $this->workflow->fetchRow("workflow_item_id=$user AND uid_prev='" . $submitter['uid'] . "'");
                } else {
                    if (!$approval) {
                        $myWork = $this->workflowGeneric->getMyWorkflow($docs['workflow_id'], $docsID, true);
                        if ($myWork)
                            $myWork = $myWork->toArray();
                    }
                    else {
                        $myWork = $this->workflowGeneric->getMyWorkflow($docs['workflow_id'], $docsID);
                        if ($myWork['person'] != '') {
                            //Filter UID, hilangkan UID approval dari list bila ada...
                            foreach ($myWork['person'] as $k => $v) {
                                if ($v['uid_next'] == QDC_User_Session::factory()->getCurrentUID())
                                    unset($myWork['person'][$k]);
                            }

                            $myWork['person'] = QDC_Common_Array::factory()->normalize($myWork['person']);

                            $found = false;
                            foreach ($myWork['person'] as $k => $v) {
                                if ($v['uid_next'] == $uidNext) {
                                    unset($myWork['person']);
                                    $myWork = $v;
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $return['success'] = true;
                                $return['prj_kode'] = $docs['prj_kode'];
                                $return['user'] = $myWork['person'];
                                if ($useOverride) {
                                    $wOveride = new Admin_Models_WorkflowGenericOverride();
                                    $override = $wOveride->getOverride($docs['item_type'], $docs['workflow_item_id'], $docs['prj_kode']);
                                    $myCurrentWorkflow = $this->workflowGeneric->getMyWorkflow($docs['workflow_id'], $docsID, true);
                                    $myLevel = $myCurrentWorkflow['level'];

                                    $oCurrent = $override[$myLevel];
                                    //Override lebih dari 1 user
                                    if ($oCurrent['user'] != '') {
                                        foreach ($oCurrent['user'] as $k2 => $v2) {
                                            if ($v2['uid'] == $uid) {
                                                $foundOverride = $v2;
                                            }
                                        }
                                    } else {
                                        $foundOverride = $oCurrent;
                                    }

                                    if ($foundOverride) {
                                        $oNext = $override[$myLevel + 1];
                                        $tmp = '';
                                        if ($oNext != '') {
                                            //Override selanjutnya lebih dari 1 user
                                            if ($oNext['user'] != '') {
                                                if ($oNext['user'] != 'ALL') {
                                                    foreach ($oNext['user'] as $k2 => $v2) {
                                                        if ($v2['project'] != '') {
                                                            foreach ($v2['project'] as $k3 => $v3) {
                                                                if ($v3['prj_kode'] == $docs['prj_kode']) {
                                                                    $tmp = $v2;
                                                                    break;
                                                                }
                                                            }
                                                        } else {
                                                            $tmp = $oNext;
                                                            break;
                                                        }
                                                    }
                                                }
                                            } else {
                                                if ($oNext['project'] != '') {
                                                    foreach ($oNext['project'] as $k2 => $v2) {
                                                        if ($v2['prj_kode'] == $docs['prj_kode']) {
                                                            $tmp = $oNext;
                                                            break;
                                                        }
                                                    }
                                                } else
                                                    $tmp = $oNext;
                                            }


                                            if ($tmp != '') {
                                                foreach ($myWork['person'] as $k2 => $v2) {
                                                    if ($v2['uid_next'] == $tmp['uid']) {
                                                        unset($myWork['person']);
                                                        $myWork = $v2;
                                                        $found = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                if (!$found) {
                                    return $return;
                                }
                            }
                        } elseif ($myWork['success'] === false) {
                            return $myWork;
                        }
                    }
                }

                return $myWork;
            }
        }

        return false;
    }

    public function checkNominalWorkflowAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->_getParam("trano");
        $prj_kode = $this->_getParam("prj_kode");
        $item_type = $this->_getParam("item_type");
        $wTransId = $this->_getParam("workflow_trans_id");
        $uid = ($this->_getParam("uid")) ? $this->_getParam("uid") : QDC_User_Session::factory()->getCurrentUID();

        $u = new Admin_Models_Masterlogin();

        $myNextWorkflow = $this->getNextWorkflow(array(
            "docsID" => $wTransId,
            "uid" => $uid,
            "userID" => $u->getUserId($uid),
            "approval" => true,
            "useOverride" => true
        ));

        $return = array();
        $return['success'] = false;

        if ($myNextWorkflow) {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$wTransId")->toArray();
            $trano = $docs['item_id'];
            //Cek nominal limit untuk transaksi
            $limit = QDC_Workflow_Nominal::factory(array(
                        "uid" => $myNextWorkflow['uid_next'],
                        "item_type" => $docs['item_type'],
                        "prj_kode" => $myNextWorkflow['prj_kode']
                    ))->checkLimit();
            if ($limit !== false) {
                //Ambil total nilai transaksi untuk di bypass..
                $totalTrans = QDC_Document_Model::factory(array(
                            "trano" => $trano,
                            "item_type" => $docs['item_type'],
                        ))->getTransactionTotal();

                $isIDR = true;
                //Cek valuta transaksi...
                $valuta = QDC_Document_Model::factory(array(
                            "trano" => $trano,
                            "item_type" => $docs['item_type'],
                        ))->getTable()->getTransactionCurrency();
                if ($valuta != 'IDR') {
                    $rate = QDC_Document_Model::factory(array(
                                "trano" => $trano,
                                "item_type" => $docs['item_type'],
                            ))->getTable()->getTransactionCurrencyRate();

                    $totalTransIDR = $totalTrans * $rate;
                    $isIDR = false;
                }

                if ($totalTrans !== false) {
                    if ($isIDR) {
                        if ($totalTrans <= $limit) {
                            $return['success'] = true;
                            $return['nominal'] = $limit;
                            $return['total'] = $totalTrans;
                            $return['currenct'] = 'IDR';
                            $return['uid_next'] = $myNextWorkflow['uid_next'];
                            $return['next_person'] = QDC_User_Ldap::factory(array("uid" => $myNextWorkflow['uid_next']))->getName();
                            $return['final'] = ($myNextWorkflow['is_end'] == 1) ? true : false;
                        }
                    } else {
                        if ($totalTransIDR <= $limit) {
                            $return['success'] = true;
                            $return['nominal'] = $limit;
                            $return['currency'] = $valuta;
                            $return['total'] = $totalTrans;
                            $return['total_in_idr'] = $totalTransIDR;
                            $return['uid_next'] = $myNextWorkflow['uid_next'];
                            $return['next_person'] = QDC_User_Ldap::factory(array("uid" => $myNextWorkflow['uid_next']))->getName();
                            $return['final'] = ($myNextWorkflow['is_end'] == 1) ? true : false;
                        }
                    }
                }
            }
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    private function genericNormalizePerson($persons = array()) {
        $tmp = array();
        foreach ($persons as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $tmp[$v2] = $v2;
            }
        }

        return $tmp;
    }

    public function assignDocumentWorkflowGenericAction() {
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->_getParam("uid");
        $uid_replace = $this->_getParam("uid_replace");
        $workflow_item_id = $this->_getParam("workflow_item_id");

        $select = $this->db->select()
                ->from(array($this->workflowTrans->__name()), array(
                    "workflow_trans_id",
                    "uid_next",
                    "item_id"
                ))
                ->where("workflow_item_id=?", $workflow_item_id)
                ->where("final = 0")
                ->where("generic = 1")
                ->where("uid_next IS NOT NULL")
                ->order(array("date DESC"));
        $select = $this->db->select()
                ->from(array($select))
                ->where("uid_next=?", $uid_replace)
                ->group(array("item_id"));

        $data = $this->db->fetchAll($select);

        foreach ($data as $k => $v) {
            $this->workflowTrans->update(array("uid_next" => $uid), "workflow_trans_id = " . $v['workflow_trans_id']);
        }

        $json = Zend_Json::encode(array("success" => true));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}

?>
