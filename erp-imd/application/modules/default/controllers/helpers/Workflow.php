<?php

class Zend_Controller_Action_Helper_Workflow extends
Zend_Controller_Action_Helper_Abstract {

    private $db;
    private $const;
    private $model;
    private $token;
    private $error;
    private $workflowtrans;
    private $workflow;
    private $workflowGeneric;
    private $masterrole;
    private $ldap;
    private $uid;
    private $session;

    function __construct() {
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
        $this->model = Zend_Controller_Action_HelperBroker::getStaticHelper('model');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->error = Zend_Controller_Action_HelperBroker::getStaticHelper('error');
        $this->workflowtrans = new Admin_Models_Workflowtrans();
        $this->workflow = new Admin_Models_Workflow();
        $this->workflowGeneric = new Admin_Models_Workflowgeneric();
        $this->workflowItem = new Admin_Models_Workflowitem();
        $this->masterrole = new Admin_Models_Masterrole();
        $this->session = new Zend_Session_Namespace('login');
        $this->ldap = new Default_Models_Ldap();
        $this->uid = $this->session->idUser;
    }

    function cekMyWorkflowIsExist($workflowType = '', $paramArray = '') {
        $myWorkflow = $this->getMyWorkflow($workflowType, $paramArray);
        if (!$myWorkflow) {
            return 300;
        } else
            return true;
    }

    function setWorkflowTrans($itemID = '', $workflowType = '', $paramArray = '', $approve = '', $items = '', $prjKode = '', $generic = false, $revisi = false) {
        if ($itemID == '' || $workflowType == '')
            return 201;

        $itemID = trim($itemID);
        $noProject = array();
        $myClass = $this->model->getModelClass($workflowType);
        if ($myClass) {
            $insertArray = array();
            $i = 0;
            $primaryKey = $myClass->getPrimaryKey();

            $id = $itemID;
            if ($approve != $this->const['DOCUMENT_SUBMIT']) {
                $result = $myClass->fetchRow($this->db->quoteInto($primaryKey . ' = ?', $id));
                if ($items != '') {
                    $result = $result->toArray();
                    foreach ($items as $key => $val) {
                        $result[$key] = $val;
                    }
                }
            } else {
                if ($items != '')
                    $result = $items;
                else
                    $result = true;

                if (!$generic)
                    $addQuery = ' AND is_start = 1';
                else
                    $addQuery = ' AND is_start = 1';
//                    $addQuery = ' AND level = 0';
            }
            if ($result) {

                $insertArray['item_id'] = $id;
                $insertArray['uid'] = $this->session->userName;
                $sign = $this->token->getDocumentSignature();
                $insertArray['signature'] = $sign['signature'];
                $insertArray['date'] = $sign['date'];
                $insertArray['ip'] = $_SERVER["REMOTE_ADDR"];
                $insertArray['computer_name'] = gethostbyaddr($insertArray['ip']);
                $insertArray['browser'] = $_SERVER["HTTP_USER_AGENT"];

                //For generic workflow
                if ($generic) {
                    $myWorkflow = $this->workflowGeneric->getWorkflowByUserID($this->uid, $workflowType, $addQuery);
                } else {
                    $myWorkflow = $this->workflow->getWorkflowByUserID($this->uid, $workflowType, $paramArray, true, $addQuery . ' ORDER BY w.prj_kode DESC');
                    //var_dump($myWorkflow);die;
                }
                if (!$myWorkflow) {
                    return 300;
                }
                if (is_array($myWorkflow[0])) {
                    $found = false;
                    //Untuk simpan workflow > 1...
                    $tmpVal = array();
                    if (!$generic) {
                        foreach ($myWorkflow as $key => $val) {
                            if ($result['prj_kode'] != '' && $val['prj_kode'] != '') {
                                if (strtolower($val['prj_kode']) === strtolower($result['prj_kode'])) {
                                    if ($result['workflow_id'] != '' && $result['workflow_item_id'] != '' && $result['workflow_structure_id'] != '' && $result['uid_next'] != '') {
                                        if ($result['workflow_id'] == $val['workflow_id'] && $result['workflow_item_id'] == $val['workflow_item_id'] && $result['workflow_structure_id'] == $val['workflow_structure_id'] && ($result['uid_next'] == $val['uid_next'] || $result['next'] == $val['next'] )) {
                                            $found = true;
                                            $tmpVal[] = $val;
                                            break;
                                        }
                                    } else {
                                        $found = true;
                                        $tmpVal[] = $val;
                                    }
                                }
                            } else {
                                $found = true;
                                $tmpVal[] = $val;
                            }
                        }
                    } else {
                        foreach ($myWorkflow as $key => $val) {
                            if ($result['prj_kode'] != '' && $val['prj_kode'] != '') {
                                if (strtolower($val['prj_kode']) === strtolower($result['prj_kode'])) {
                                    if ($result['workflow_item_id'] != '' && $result['workflow_item_type_id'] != '') {
                                        if ($result['workflow_item_id'] == $val['workflow_item_id'] && $result['workflow_item_type_id'] == $val['workflow_item_type_id']) {
                                            $found = true;
                                            $tmpVal[] = $val;
                                            break;
                                        }
                                    } else {
                                        $found = true;
                                        $tmpVal[] = $val;
                                    }
                                }
                            } else {
                                if ($result['uid_dest'] != '') {
                                    $uidDest = $result['uid_dest'];
                                    if ($val['is_end'] != 1) {
                                        $nextLevel = $val['level'] + 1;
                                        $workflowItemId = $val['workflow_item_id'];
                                        $workflowItemTypeId = $val['workflow_item_type_id'];
                                        $fetch = $this->workflowGeneric->fetchAll("level = $nextLevel AND workflow_item_id = $workflowItemId AND workflow_item_type_id = $workflowItemTypeId");
                                        if ($fetch) {
                                            $fetch = $fetch->toArray();
                                            foreach ($fetch as $k2 => $v2) {
                                                $roleId = $v2['role_id'];
                                                $cek = $this->masterrole->cekUserInRole(array(
                                                    "roleID" => $roleId,
                                                    "userID" => $result['uid_dest']
                                                ));
                                                if ($cek) {
                                                    $found = true;
                                                    $noProject[$uidDest] = $v2;
                                                    if ($noProject[$uidDest]['role_id'] == $roleId)
                                                        $tmpVal[] = $val;
                                                }
                                            }
                                        }
                                    }
                                }
                                else {
                                    $found = true;
                                    $tmpVal[] = $val;
                                }
                            }
                        }
                    }
                    if (!$found)
                        return 300;
                    else {
                        unset($myWorkflow);
                        $myWorkflow = $tmpVal;
                    }
                }
                $oldResult = $result;
                if (count($myWorkflow) > 1) {
                    $i = 1;
                    $result = array();
                    foreach ($myWorkflow as $key => $val) {
                        if ($approve == $this->const['DOCUMENT_RESUBMIT']) {
                            //                             if ($this->getDocumentLastStatus($id) != $this->const['DOCUMENT_REJECT'])
                            //                                 return 301;
                            if ($myWorkflow[$key]['is_start'] == 0) {
                                unset($myWorkflow[$key]);
                                continue;
                            }
                        }
                        if (!$generic) {
                            $myWorkflow[$key]['id'] = $i;
                            $myNext = $val['next'];
                            $myCur = $val['workflow_structure_id'];
                            $myWorkflow_item = $val['workflow_item_id'];
                            $myNext = $this->workflow->fetchRow("workflow_structure_id=$myNext AND prev=$myCur AND workflow_item_id=$myWorkflow_item");
                            if (!$myNext)
                                continue;
                            $myNext = $myNext->toArray();
                            $workflowItem = $this->workflowItem->fetchRow("workflow_item_id=$myWorkflow_item");
                            $workflowItemName = $workflowItem['name'];
                            $nextRole = $this->masterrole->getRoleFromRoleId($myNext['master_role_id']);
//                            $nextUser = $this->ldap->getAccount($myNext['uid']);
//                            $myWorkflow[$key]['name'] = $nextUser['displayname'][0];
                            $myWorkflow[$key]['name'] = QDC_User_Ldap::factory(array("uid" => $myNext['uid']))->getName();
                            ;
                            $myWorkflow[$key]['role_name'] = $nextRole['display_name'];
                            $myWorkflow[$key]['prj_kode'] = $val['prj_kode'];
                            $myWorkflow[$key]['uid_next'] = $myNext['uid'];
                            $myWorkflow[$key]['workflow_item_name'] = $workflowItemName;
                            $result[] = $myWorkflow[$key];

                            $i++;
                        }
                        else {
                            if ($val['is_end'] != 1) {
                                if ($uidDest == '' && count($noProject) == 0) {
                                    $nextLevel = $val['level'] + 1;
                                    $workflowItemId = $val['workflow_item_id'];
                                    $workflowItemTypeId = $val['workflow_item_type_id'];
                                    $fetch = $this->workflowGeneric->fetchAll("level = $nextLevel AND workflow_item_id = $workflowItemId AND workflow_item_type_id = $workflowItemTypeId");
                                    if ($fetch) {
                                        $fetch = $fetch->toArray();
                                        foreach ($fetch as $k2 => $v2) {
                                            $roleId = $v2['role_id'];
                                            $prj_kode = $v2['prj_kode'];
                                            $users = $this->masterrole->getUserFromRoleAndProject($roleId, $prj_kode);
                                            foreach ($users as $k => $v) {
                                                $arrays = array();
//                                                $nextUser = $this->ldap->getAccount($v['uid']);
                                                $arrays['id'] = $i;
//                                                $arrays['name'] = $nextUser['displayname'][0];
                                                $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                                                $arrays['role_name'] = $v2['role_name'];
                                                $arrays['prj_kode'] = $val['prj_kode'];
                                                $arrays['uid_next'] = $v['uid'];
                                                $arrays['workflow_item_id'] = $workflowItemId;
                                                $arrays['workflow_item_type_id'] = $workflowItemTypeId;
                                                $arrays['workflow_item_name'] = $v2['workflow_item_name'];
                                                $arrays['trano'] = $itemID;
                                                $result[] = $arrays;
                                                $i++;
                                            }
                                        }
                                    }
                                } else {
//                                    $nextUser = $this->ldap->getAccount($uidDest);
                                    foreach ($noProject as $k3 => $v3) {
                                        $roles = $this->masterrole->getRoleFromRoleId($v3['role_id']);
                                        $arrays['id'] = $i;
//                                        $arrays['name'] = $nextUser['displayname'][0];
                                        $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $uidDest))->getName();
                                        $arrays['role_name'] = $roles['role_name'];
                                        $arrays['prj_kode'] = 'NOPROJECT';
                                        $arrays['uid_next'] = $k3;
                                        $arrays['workflow_item_id'] = $workflowItemId;
                                        $arrays['workflow_item_type_id'] = $workflowItemTypeId;
                                        $arrays['workflow_item_name'] = $v3['workflow_item_name'];
                                        $arrays['trano'] = $itemID;
                                        $result[] = $arrays;
                                        $i++;
                                    }
                                }
                            }
                        }
                    }
                    if (count($result) == 0) {
                        return 302;
                    } else
                        return $result;
                }
                elseif (count($myWorkflow) == 1) {
                    $tmp = $myWorkflow[0];
                    unset($myWorkflow);
                    $myWorkflow = $tmp;
                }

                $insertArray['approve'] = $approve;
                if (!$generic) {
                    $myNext = $myWorkflow['next'];
                    $insertArray['uid_next'] = $myWorkflow['uid_next'];
                    $insertArray['uid_prev'] = $myWorkflow['uid_prev'];
                    $myCur = $myWorkflow['workflow_structure_id'];
                    $myWorkflow_item = $myWorkflow['workflow_item_id'];
                    if ($workflowType == '') {
                        $sql = "SELECT wit.name FROM workflow_item w LEFT JOIN workflow_item_type wit ON w.workflow_item_type_id = wit.workflow_item_type_id WHERE w.workflow_item_id = " . $myWorkflow_item;
                        $fetch = $this->db->query($sql);
                        $fetch = $fetch->fetch();

                        $workflowType = $fetch['name'];
                    }
                } else {
                    $result = array();
                    $workflowItemId = $myWorkflow['workflow_item_id'];
                    $workflowItemTypeId = $myWorkflow['workflow_item_type_id'];
                    if ($myWorkflow['is_end'] != 1) {
                        if ($oldResult['uid_next'] != "") {
                            $insertArray['uid_next'] = $oldResult['uid_next'];
                        } else {
                            if ($uidDest == '' && count($noProject) == 0) {
                                $nextLevel = $myWorkflow['level'] + 1;

                                $fetch = $this->workflowGeneric->fetchAll("level = $nextLevel AND workflow_item_id = $workflowItemId AND workflow_item_type_id = $workflowItemTypeId");
                                if ($fetch) {
                                    $fetch = $fetch->toArray();
                                    if (count($fetch) > 1) {
                                        $i = 1;
                                        foreach ($fetch as $k2 => $v2) {
                                            $roleId = $v2['role_id'];
                                            $users = $this->masterrole->getUserFromRoleAndProject($roleId, $myWorkflow['prj_kode']);
//                                            $users = $this->masterrole->getUserFromRoleAndProject($roleId);
                                            foreach ($users as $k => $v) {
                                                $arrays = array();
//                                                $nextUser = $this->ldap->getAccount($v['uid']);
                                                $arrays['id'] = $i;
//                                                $arrays['name'] = $nextUser['displayname'][0];
                                                $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                                                $arrays['role_name'] = $v2['role_name'];
                                                $arrays['prj_kode'] = $v2['prj_kode'];
                                                $arrays['uid_next'] = $v['uid'];
                                                $arrays['workflow_item_id'] = $workflowItemId;
                                                $arrays['workflow_item_type_id'] = $workflowItemTypeId;
                                                $arrays['workflow_item_name'] = $v2['workflow_item_name'];
                                                $arrays['trano'] = $itemID;
                                                $result[] = $arrays;
                                                $i++;
                                            }
                                        }
                                        return $result;
                                    } else {
                                        $roleId = $fetch[0]['role_id'];
                                        $users = $this->masterrole->getUserFromRoleAndProject($roleId, $fetch[0]['prj_kode']);
//                                        $users = $this->masterrole->getUserFromRoleAndProject($roleId);
                                        if (count($users) > 1) {
                                            foreach ($users as $k => $v) {
                                                $arrays = array();
//                                                $nextUser = $this->ldap->getAccount($v['uid']);
                                                $arrays['id'] = $i;
//                                                $arrays['name'] = $nextUser['displayname'][0];
                                                $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                                                $arrays['role_name'] = $fetch[0]['role_name'];
                                                $arrays['prj_kode'] = $fetch[0]['prj_kode'];
                                                $arrays['uid_next'] = $v['uid'];
                                                $arrays['workflow_item_id'] = $workflowItemId;
                                                $arrays['workflow_item_type_id'] = $workflowItemTypeId;
                                                $arrays['workflow_item_name'] = $myWorkflow['workflow_item_name'];
                                                $arrays['trano'] = $itemID;
                                                $result[] = $arrays;
                                                $i++;
                                            }
                                            return $result;
                                        } else {
                                            $insertArray['uid_next'] = $users[0]['uid'];
                                        }
                                    }
                                }
                            } else {
                                if (count($noProject) > 1) {
//                                    $nextUser = $this->ldap->getAccount($uidDest);
                                    foreach ($noProject as $k3 => $v3) {
                                        $roles = $this->masterrole->getRoleFromRoleId($v3['role_id']);
                                        $arrays['id'] = $i;
//                                        $arrays['name'] = $nextUser['displayname'][0];
                                        $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $uidDest))->getName();
                                        $arrays['role_name'] = $roles['role_name'];
                                        $arrays['prj_kode'] = 'NOPROJECT';
                                        $arrays['uid_next'] = $k3;
                                        $arrays['workflow_item_name'] = $v3['workflow_item_name'];
                                        $arrays['trano'] = $itemID;
                                        $result[] = $arrays;
                                        $i++;
                                    }
                                    return $result;
                                } else {
                                    $insertArray['uid_next'] = $uidDest;
                                    $insertArray['prj_kode'] = 'NOPROJECT-' . $workflowType;
                                }
                            }
                        }
                        $submitter = $this->workflowtrans->getDocumentSubmitter($itemID);
                        if ($submitter) {
                            $insertArray['uid_prev'] = $submitter['uid'];
                        }
                    }
                }
                $insertArray['item_type'] = $workflowType;
                if ($approve == $this->const['DOCUMENT_RESUBMIT']) {

                    if ($generic)
                        $prjKodeGeneric = $myWorkflow['prj_kode'];
                    if ($myWorkflow['is_start'] == 0)
                        return 302;
                    $statusDocs = $this->getDocumentLastStatus($id, $prjKodeGeneric);
                    if ($statusDocs == '') {
                        $insertArray['approve'] = $this->const['DOCUMENT_SUBMIT'];
                    } else {

                        if ($this->getDocumentLastStatus($id, $prjKodeGeneric) == $this->const['DOCUMENT_FINAL'] || $this->getDocumentLastStatus($id, $prjKodeGeneric) == $this->const['DOCUMENT_EXECUTE'])
                            return 305;
                        if ($this->getDocumentLastStatus($id, $prjKodeGeneric) != $this->const['DOCUMENT_REJECT'])
                            return 301;
                    }
                }

                if (!$generic) {
                    if ($approve == $this->const['DOCUMENT_APPROVE']) {
                        if ($myNext != 0) {
                            $nextWorkflow = $this->workflow->fetchRow("workflow_structure_id=$myNext AND prev=$myCur AND workflow_item_id=$myWorkflow_item");
                            if ($nextWorkflow->toArray()) {
                                if ($nextWorkflow['is_end'] == 1) {
                                    if ($nextWorkflow['is_executor'] == 1)
                                        $insertArray['approve'] = $this->const['DOCUMENT_FINAL'];
                                    else
                                        $insertArray['approve'] = $this->const['DOCUMENT_APPROVE'];
                                }
                            }
                        }
                        elseif ($myNext == 0) {
                            if ($myWorkflow['is_executor'] == 1)
                                $insertArray['approve'] = $this->const['DOCUMENT_EXECUTE'];
                            elseif ($myWorkflow['is_final'] == 1)
                                $insertArray['approve'] = $this->const['DOCUMENT_FINAL'];
                        }
                    }
                    $insertArray['workflow_structure_id'] = $myWorkflow['workflow_structure_id'];
                    $insertArray['prj_kode'] = $myWorkflow['prj_kode'];
                }
                else {
                    $insertArray['generic'] = 1;
                    if ($insertArray['prj_kode'] == '')
                        $insertArray['prj_kode'] = $myWorkflow['prj_kode'];
                }
                $insertArray['workflow_item_id'] = $myWorkflow['workflow_item_id'];
                $insertArray['workflow_id'] = $myWorkflow['workflow_id'];
                $result = $this->workflowtrans->insert($insertArray);
                if (!$result) {
                    return false;
                } else {
                    $this->workflowtrans->updateStatusApprove($myClass, $insertArray['item_type'], $insertArray['item_id'], $insertArray['approve']);
                }
                return true;
            } else {
                return 201;
            }
        }
    }

    function convertFormat($dataArray = '', $primaryKey = '') {
        $result = array();
        foreach ($dataArray as $key => $val) {
            if (is_array($val)) {
                if ($val[$primaryKey] != '') {
                    $result[]['id'] = $val[$primaryKey];
                }
            } else {
                if ($key == $primaryKey)
                    $result[0]['id'] = $val;
            }
        }

        return $result;
    }

    function getMyWorkflow($workflowType = '', $paramArray = '') {
        return $this->workflow->getWorkflowByUserID($this->uid, $workflowType, $paramArray);
    }

    function getAllMyWorkflow($uid = '', $typeWork = '') {
        if ($typeWork == '')
            return $this->workflow->getWorkflowByUserID($uid, '', '', true);
        else
            return $this->workflow->getWorkflowByUserID($uid, $typeWork, '', true);
    }

    function getMyDocumentStatus($itemID = '', $workflowType = '', $paramArray = '') {
        $myWorkflow = $this->getMyWorkflow($workflowType, $paramArray);
        $workflowID = $myWorkflow['workflow_id'];
        $uid = $this->uid;
        if ($workflowID != '')
            $result = $this->workflowtrans->getDocumentStatus($workflowID, $itemID, $uid);
        else
            $result = '';
        return $result;
    }

    function getDocumentToProcess($arrayWorkflow = '') {
        $fetch = $arrayWorkflow;
        $result = array();
        if ($fetch) {
            $idDocs = 1;
            $myUid = $fetch['uid'];
            $myWorkflowid = $fetch['workflow_id'];
            $myWorkflow_structure_id = $fetch['workflow_structure_id'];
            $prev = $fetch['prev'];
            $next = $fetch['next'];
            $prevUid = $fetch['uid_prev'];
            $nextUid = $fetch['uid_next'];
            $workflow_item_id = $fetch['workflow_item_id'];
            if ($workflow_item_id == '' || $myWorkflow_structure_id == '')
                return;
            $fetchs = $this->db->query("SELECT * FROM workflow WHERE next = $myWorkflow_structure_id AND workflow_structure_id=$prev AND workflow_item_id=$workflow_item_id AND uid = '$prevUid'");
            $fetchs = $fetchs->fetchAll();
            if ($fetchs) {
                foreach ($fetchs as $key => $fetch2) {
//    			    $otherUserName = $this->ldap->getAccount($prevUid);
                    $otherWorkflow_id = $fetch2['workflow_id'];
                    $otherWorkflow_structure_id = $fetch2['workflow_structure_id'];
                    $sql = "SELECT * FROM (SELECT * FROM workflow_trans WHERE  workflow_item_id = $workflow_item_id AND workflow_structure_id=$prev AND uid = '$prevUid' ORDER BY date DESC) a GROUP BY a.item_id";
                    $select = $this->db->query($sql);
                    $docs = $select->fetchAll();
                    if (count($docs) > 0) {
                        foreach ($docs as $key2 => $val2) {
                            $approve = $val2['approve'];
                            if ($approve != $this->const['DOCUMENT_REJECT'] && $approve != $this->const['DOCUMENT_FINAL'] && $approve != $this->const['DOCUMENT_EXECUTE']) {
                                $id_trans = $val2['workflow_trans_id'];
                                $type = $this->workflowtrans->getDocumentType($id_trans);
                                $item_id = $val2['item_id'];
                                $date = $val2['date'];
                                $cek = $this->getDocumentLastStatusByApproval($item_id);
                                if ($cek == $this->const['DOCUMENT_FINAL'] || $cek == $this->const['DOCUMENT_EXECUTE']) {
                                    continue;
                                }
                                $cek = $this->getDocumentLastStatusAll($item_id);
                                if ($cek) {
                                    $lastDate = strtotime($cek['date']);
                                    $prevDate = strtotime($val2['date']);

                                    if ($lastDate > $prevDate)
                                        continue;
                                }
                                //Bypass document reject & di resubmit lagi lsg ke yang reject terakhir...
//                            $cekLast = $this->getDocumentLastStatus($item_id);
//                            if ($cekLast == $this->const['DOCUMENT_RESUBMIT'])
//                            {
//                                if (!$this->isDocumentRejected($item_id,$myUid))
//                                    continue;
//                            }

                                $comment = str_replace("\n", "", $val2['comment']);
                                $comment = str_replace("\r", "", $comment);
                                $comment = str_replace("\"", "", $comment);
                                $comment = str_replace("'", "", $comment);
                                $sign = $val2['signature'];
                                if ($approve == $this->const['DOCUMENT_SUBMIT'])
                                    $approve = 'SUBMITTED';
                                elseif ($approve == $this->const['DOCUMENT_RESUBMIT'])
                                    $approve = 'RE-SUBMITTED';
                                elseif ($approve == $this->const['DOCUMENT_APPROVE'])
                                    $approve = 'APPROVED';
                                elseif ($approve == $this->const['DOCUMENT_FINAL'])
                                    $approve = 'FINAL APPROVAL';
                                elseif ($approve == $this->const['DOCUMENT_EXECUTE'])
                                    $approve = 'EXECUTED';
                                $myDocs = $this->workflowtrans->fetchAll("workflow_structure_id = $myWorkflow_structure_id AND item_id='$item_id' AND uid='$myUid'", array("date DESC"))->toArray();
                                if (count($myDocs) == 0) {
                                    $result[$item_id] = array(
                                        "id" => $id_trans,
                                        "item_id" => $item_id,
                                        "type" => $type,
                                        "prev" => $otherWorkflow_structure_id,
                                        "user_prev" => $prevUid,
//	    							"username_prev" => $otherUserName['displayname'][0],
                                        "username_prev" => QDC_User_Ldap::factory(array("uid" => $prevUid))->getName(),
                                        "date" => $date,
                                        "approve" => $approve,
                                        "comment" => $comment,
                                        "signature" => $sign
                                    );
                                } else {
                                    $dateAkhir = strtotime($myDocs[0]['date']);
                                    $dateAwal = strtotime($date);
                                    if ($dateAwal > $dateAkhir) {
                                        $result[$item_id] = array(
                                            "id" => $id_trans,
                                            "item_id" => $item_id,
                                            "type" => $type,
                                            "prev" => $otherWorkflow_structure_id,
                                            "user_next" => $prevUid,
//		    							"username_prev" => $otherUserName['displayname'][0],
                                            "username_prev" => QDC_User_Ldap::factory(array("uid" => $prevUid))->getName(),
                                            "date" => $date,
                                            "approve" => $approve,
                                            "comment" => $comment,
                                            "signature" => $sign
                                        );
                                    }
                                }
                            }
                            $idDocs++;
                        }
                    }
                }
            }

            //Get document yang direject...
//            $sql = "SELECT * FROM (SELECT * FROM workflow_trans WHERE  workflow_item_id = $workflow_item_id AND workflow_structure_id=$myWorkflow_structure_id AND uid = '$myUid' AND approve = 300 ORDER BY date DESC) a GROUP BY a.item_id";
//            $select = $this->db->query($sql);
//            $docs = $select->fetchAll();
//            if (count($docs) > 0)
//            {
//                foreach ($docs as $key2 => $val2)
//                {
//                    $item_id = $val2['item_id'];
//                    $date = $val2['date'];
//                    $cekLast = $this->getDocumentLastStatus($item_id);
//                    if ($cekLast == $this->const['DOCUMENT_RESUBMIT'])
//                    {
//                        if ($this->getDocumentLastStatusByUser($item_id,$myUid) == $this->const['DOCUMENT_REJECT'])
//                        {
//                            $getStart = $this->getDocumentStarterByUser($item_id,$workflow_item_id);
//                            $id_trans = $getStart['workflow_trans_id'];
//                            $otherWorkflow_structure_id = $getStart['workflow_structure_id'];
//                            $prevUid = $getStart['uid'];
//                            $date = $getStart['date'];
//                            $sign = $getStart['signature'];
//                            $approve = 'RE-SUBMITTED';
//                            $otherUserName = $this->ldap->getAccount($prevUid);
//                            $type = $this->workflowtrans->getDocumentType($id_trans);
//                            $result[$item_id] = array(
//                                "id" => $id_trans,
//                                "item_id" => $item_id,
//                                "type" => $type,
//                                "prev" => $otherWorkflow_structure_id,
//                                "user_prev" => $prevUid,
//                                "username_prev" => $otherUserName['displayname'][0],
//                                "date" => $date,
//                                "approve" => $approve,
//                                "comment" => "* RESUBMITTED FROM YOUR LAST REJECT *",
//                                "signature" => $sign
//                            );
//                            $idDocs++;
//                        }
//                    }
//                }
//            }

            if ($next != 0 && $fetch['is_start'] == 1) {
                $sql = "SELECT * FROM (SELECT * FROM workflow_trans WHERE  workflow_item_id = $workflow_item_id AND approve = 300 ORDER BY date DESC) a GROUP BY a.item_id";
                $select = $this->db->query($sql);
                $docs = $select->fetchAll();
                if (count($docs) > 0) {
                    foreach ($docs as $key2 => $val2) {
                        $approve = $val2['approve'];
                        if ($approve == $this->const['DOCUMENT_REJECT']) {
                            $item_id = $val2['item_id'];
                            $date = $val2['date'];
                            $comment = str_replace("\n", "", $val2['comment']);
                            $comment = str_replace("\r", "", $comment);
                            $comment = str_replace("\"", "", $comment);
                            $comment = str_replace("'", "", $comment);
                            $sign = $val2['signature'];
                            $cekDocs = $this->workflowtrans->fetchAll("workflow_item_id = $workflow_item_id AND workflow_structure_id=$myWorkflow_structure_id AND item_id='$item_id' AND date <= '$date' AND uid='$myUid' AND (approve=100 OR approve=150)", array("date ASC"));
                            $cekDocs = $cekDocs->toArray();
                            if (!$cekDocs) {
                                //Bypass untuk perubahan workflow, dimana starter belum melakukan transaksi (submit/resubmit) dokumen.
                                //Jika document di Reject akan kembali ke starter yang baru
                                //Cek jumlah starter
                                $sql = "SELECT COUNT(*) AS jumlah FROM workflow WHERE  workflow_item_id = $workflow_item_id AND is_start = 1";
                                $select = $this->db->query($sql);
                                $jumlah = $select->fetch();

                                $byPass = false;
                                //Hanya berlaku kalau starter berjumlah 1 orang...
                                if ($jumlah['jumlah'] == 1) {
                                    $byPass = true;
                                } else
                                    continue;
                                //End of Bypass
                            }

//                            $otherUserName = $this->ldap->getAccount($val2['uid']);
                            $otherWorkflow_structure_id = $val2['workflow_structure_id'];
                            $id_trans = $val2['workflow_trans_id'];
                            $type = $this->workflowtrans->getDocumentType($id_trans);
                            $prevUid = $myUid;

                            $myDocs = $this->workflowtrans->fetchAll("workflow_item_id = $workflow_item_id AND workflow_structure_id=$myWorkflow_structure_id AND item_id='$item_id' AND date >= '$date' AND uid='$myUid' ", array("date DESC"));
                            $myDocs = $myDocs->toArray();
                            if (count($myDocs) > 0) {
                                if ($myDocs[0]['approve'] == $this->const['DOCUMENT_APPROVE'] || $myDocs[0]['approve'] == $this->const['DOCUMENT_FINAL'] || $myDocs[0]['approve'] == $this->const['DOCUMENT_RESUBMIT']) {
                                    continue;
                                }
                            }
                            $approve = "REJECTED";

                            $result[$item_id] = array(
                                "id" => $id_trans,
                                "item_id" => $item_id,
                                "type" => $type,
                                "prev" => $otherWorkflow_structure_id,
                                "user_prev" => $prevUid,
//                                "username_prev" => $otherUserName['displayname'][0],
                                "username_prev" => QDC_User_Ldap::factory(array("uid" => $val2['uid']))->getName(),
                                "date" => $date,
                                "approve" => $approve,
                                "comment" => $comment,
                                "signature" => $sign,
                                "reject" => true
                            );
                            $idDocs++;
                        }
                    }
                }
//                $fetch2 = $this->workflow->fetchRow("workflow_structure_id=$next AND workflow_item_id=$workflow_item_id AND prev=$myWorkflow_structure_id");
//	    		if ($fetch2)
//	    		{
//
//	    			$prev_uid = $this->masterrole->getUserFromRoleId($fetch2['master_role_id']);
//	    			$otherUserName = $this->ldap->getAccount($prev_uid);
//	    			$otherWorkflow_id = $fetch2['workflow_id'];
//	    			$otherWorkflow_structure_id = $fetch2['workflow_structure_id'];
//                    $sql = "SELECT * FROM (SELECT * FROM workflow_trans WHERE workflow_structure_id=$next AND workflow_item_id = $workflow_item_id AND uid = '$nextUid' ORDER BY date DESC) a GROUP BY a.item_id";
//                    $select = $this->db->query($sql);
//	    			$docs = $select->fetchAll();
//	    			if (count($docs) > 0)
//	    			{
//	    				foreach ($docs as $key2 => $val2)
//	    				{
//	    					$approve = $val2['approve'];
//	    					if ($approve ==  $this->const['DOCUMENT_REJECT'])
//	    					{
//
//                                $id_trans = $val2['workflow_trans_id'];
//		    					$type = $this->workflowtrans->getDocumentType($id_trans);
//		    					$item_id = $val2['item_id'];
//		    					$date = $val2['date'];
//		    					$comment = $val2['comment'];
//		    					$sign = $val2['signature'];
//                                $myDocs = $this->workflowtrans->fetchAll("workflow_structure_id=$myWorkflow_structure_id AND item_id='$item_id' AND date >= '$date' AND uid='$myUid' ",array("date DESC"));
//
//                                if (count($myDocs->toArray()) > 0)
//                                {
//	    							if ($myDocs[0]['approve'] == $this->const['DOCUMENT_APPROVE'] || $myDocs[0]['approve'] == $this->const['DOCUMENT_FINAL'] || $myDocs[0]['approve'] == $this->const['DOCUMENT_RESUBMIT'])
//	    							{
//	    								continue;
//	    							}
//	    						}
//		    					$approve = "REJECTED";
//
//		    					$result[$item_id] = array(
//	    							"id" => $id_trans,
//	    							"item_id" => $item_id,
//	    							"type" => $type,
//	    							"prev" => $otherWorkflow_structure_id,
//	    							"user_prev" => $prevUid,
//	    							"username_prev" => $otherUserName['displayname'][0],
//	    							"date" => $date,
//	    							"approve" => $approve,
//	    							"comment" => $comment,
//	    							"signature" => $sign,
//		    						"reject" => true
//	    						);
//                                $idDocs++;
//	    					}
//	    				}
//	    			}
//	    		}
            }
        }
        return $result;
    }

    function countDocumentToProcessNew($uid = '') {
        $result = array();
        $idDocs = 1;
        $myUid = $uid;
        if ($uid == '')
            return;
        $fetchs = $this->db->query("SELECT * FROM ( SELECT * FROM workflow_trans a WHERE a.uid_next = '$myUid' AND (approve != 400 AND approve != 500) ORDER BY a.date DESC) b GROUP BY b.item_id ");

        if ($fetchs) {
            $docs = $fetchs->fetchAll();
            if (count($docs) > 0) {
                foreach ($docs as $key2 => $val2) {
                    $approve = $val2['approve'];
                    if ($approve != $this->const['DOCUMENT_REJECT'] && $approve != $this->const['DOCUMENT_FINAL'] && $approve != $this->const['DOCUMENT_EXECUTE']) {
                        $id_trans = $val2['workflow_trans_id'];
                        $type = $this->workflowtrans->getDocumentType($id_trans);
                        $item_id = $val2['item_id'];
                        $cek = $this->getDocumentLastStatusByApproval($item_id);
                        if ($cek == $this->const['DOCUMENT_FINAL'] || $cek == $this->const['DOCUMENT_EXECUTE']) {
                            continue;
                        }
                        $cek = $this->getDocumentLastStatusAll($item_id);
                        if ($cek) {
                            $lastDate = strtotime($cek['date']);
                            $prevDate = strtotime($val2['date']);
                            if ($lastDate > $prevDate)
                                continue;
                        }
                        //Bypass document reject & di resubmit lagi lsg ke yang reject terakhir...
//                                $cekLast = $this->getDocumentLastStatus($item_id);
//                                if ($cekLast == $this->const['DOCUMENT_RESUBMIT'])
//                                {
//                                    if (!$this->isDocumentRejected($item_id,$myUid))
//                                        continue;
//                                }

                        $date = $val2['date'];
                        $comment = $val2['comment'];
                        $sign = $val2['signature'];
                        $myWorkflow_structure_id = $val2['workflow_structure_id'];
                        $prevUid = $val2['uid_prev'];
                        if ($approve == $this->const['DOCUMENT_SUBMIT'])
                            $approve = 'SUBMITTED';
                        elseif ($approve == $this->const['DOCUMENT_RESUBMIT'])
                            $approve = 'RE-SUBMITTED';
                        elseif ($approve == $this->const['DOCUMENT_APPROVE'])
                            $approve = 'APPROVED';
                        elseif ($approve == $this->const['DOCUMENT_FINAL'])
                            $approve = 'FINAL APPROVAL';
                        elseif ($approve == $this->const['DOCUMENT_EXECUTE'])
                            $approve = 'EXECUTED';

                        $myDocs = $this->workflowtrans->fetchAll("item_id='$item_id' AND uid='$myUid'", array("date DESC"))->toArray();
                        if (count($myDocs) == 0) {
                            $result[$item_id] = array(
                                "id" => $idDocs,
                                "item_id" => $item_id,
                                "approve" => $approve,
                                "uid" => $prevUid
                            );
                        } else {
                            $dateAkhir = strtotime($myDocs[0]['date']);
                            $dateAwal = strtotime($date);
                            if ($dateAwal > $dateAkhir) {
                                $result[$item_id] = array(
                                    "id" => $idDocs,
                                    "item_id" => $item_id,
                                    "approve" => $approve,
                                    "uid" => $prevUid
                                );
                            }
                        }
                    } elseif ($approve == $this->const['DOCUMENT_REJECT']) {
                        $item_id = $val2['item_id'];
                        $date = $val2['date'];
                        $comment = $val2['comment'];
                        $sign = $val2['signature'];
                        $workflow_item_id = $val2['workflow_item_id'];
                        $cekDocs = $this->workflowtrans->fetchAll("item_id='$item_id' AND workflow_item_id = $workflow_item_id AND uid='$myUid' AND date <= '$date'  AND (approve=100 OR approve=150)", array("date ASC"));
                        $cekDocs = $cekDocs->toArray();
                        if (!$cekDocs) {
                            //Bypass untuk perubahan workflow, dimana starter belum melakukan transaksi (submit/resubmit) dokumen.
                            //Jika document di Reject akan kembali ke starter yang baru
                            //Cek jumlah starter
                            $sql = "SELECT COUNT(*) AS jumlah FROM workflow WHERE  workflow_item_id = $workflow_item_id AND is_start = 1";
                            $select = $this->db->query($sql);
                            $jumlah = $select->fetch();

                            $byPass = false;
                            //Hanya berlaku kalau starter berjumlah 1 orang...
                            if ($jumlah['jumlah'] == 1) {
                                $byPass = true;
                            } else
                                continue;
                            //End of Bypass
                        }
                        $myWork = $this->workflow->fetchAll("workflow_item_id = $workflow_item_id AND uid='$myUid'");

                        if ($myWork) {
                            $otherUserName = $this->ldap->getAccount($val2['uid']);
                            $otherWorkflow_structure_id = $val2['workflow_structure_id'];
                            $id_trans = $val2['workflow_trans_id'];
                            $type = $this->workflowtrans->getDocumentType($id_trans);
                            foreach ($myWork as $key3 => $val3) {
                                $myWorkflow_structure_id = $val3['workflow_structure_id'];
                                $myDocs = $this->workflowtrans->fetchAll("item_id='$item_id' AND workflow_item_id = $workflow_item_id  AND uid='$myUid' AND workflow_structure_id = $myWorkflow_structure_id AND date >= '$date' ", array("date DESC"));

                                if (count($myDocs->toArray()) > 0) {
                                    if ($myDocs[0]['approve'] == $this->const['DOCUMENT_APPROVE'] || $myDocs[0]['approve'] == $this->const['DOCUMENT_FINAL'] || $myDocs[0]['approve'] == $this->const['DOCUMENT_RESUBMIT']) {
                                        continue;
                                    }
                                }
                                $approve = "REJECTED";

                                $result[$item_id] = array(
                                    "id" => $idDocs,
                                    "item_id" => $item_id,
                                    "approve" => $approve,
                                    "uid" => $val2['uid']
                                );
                            }
                        }
                    }
                    $idDocs++;
                }
            }
        }
        return $result;
    }

    function countDocumentToProcess($arrayWorkflow = '') {

//Workflow logic lama... sebelum penambahan uid_next & uid_prev di workflow_trans

        $fetch = $arrayWorkflow;
        $result = array();
        if ($fetch) {
            $idDocs = 1;
            $myUid = $fetch['uid'];
            $myWorkflowid = $fetch['workflow_id'];
            $myWorkflow_structure_id = $fetch['workflow_structure_id'];
            $prev = $fetch['prev'];
            $next = $fetch['next'];
            $prevUid = $fetch['uid_prev'];
            $nextUid = $fetch['uid_next'];
            $workflow_item_id = $fetch['workflow_item_id'];
            if ($workflow_item_id == '' || $myWorkflow_structure_id == '')
                return;
            $fetchs = $this->db->query("SELECT * FROM workflow WHERE next = $myWorkflow_structure_id AND workflow_structure_id=$prev AND workflow_item_id=$workflow_item_id AND uid = '$prevUid'");
            $fetchs = $fetchs->fetchAll();
            if ($fetchs) {
                foreach ($fetchs as $key => $fetch2) {
                    $otherWorkflow_id = $fetch2['workflow_id'];
                    $sql = "SELECT * FROM (SELECT * FROM workflow_trans WHERE  workflow_item_id = $workflow_item_id AND workflow_structure_id=$prev AND uid = '$prevUid' ORDER BY date DESC) a GROUP BY a.item_id";
                    $select = $this->db->query($sql);
                    $docs = $select->fetchAll();
                    if (count($docs) > 0) {
                        foreach ($docs as $key2 => $val2) {
                            $approve = $val2['approve'];
                            if ($approve != $this->const['DOCUMENT_REJECT'] && $approve != $this->const['DOCUMENT_FINAL'] && $approve != $this->const['DOCUMENT_EXECUTE']) {
                                $id_trans = $val2['workflow_trans_id'];
                                $type = $this->workflowtrans->getDocumentType($id_trans);
                                $item_id = $val2['item_id'];
                                $cek = $this->getDocumentLastStatusByApproval($item_id);
                                if ($cek == $this->const['DOCUMENT_FINAL'] || $cek == $this->const['DOCUMENT_EXECUTE']) {
                                    continue;
                                }
                                $cek = $this->getDocumentLastStatusAll($item_id);
                                if ($cek) {
                                    $lastDate = strtotime($cek['date']);
                                    $prevDate = strtotime($val2['date']);
                                    if ($lastDate > $prevDate)
                                        continue;
                                }
                                //Bypass document reject & di resubmit lagi lsg ke yang reject terakhir...
//                                $cekLast = $this->getDocumentLastStatus($item_id);
//                                if ($cekLast == $this->const['DOCUMENT_RESUBMIT'])
//                                {
//                                    if (!$this->isDocumentRejected($item_id,$myUid))
//                                        continue;
//                                }

                                $date = $val2['date'];
                                $comment = $val2['comment'];
                                $sign = $val2['signature'];
                                if ($approve == $this->const['DOCUMENT_SUBMIT'])
                                    $approve = 'SUBMITTED';
                                elseif ($approve == $this->const['DOCUMENT_RESUBMIT'])
                                    $approve = 'RE-SUBMITTED';
                                elseif ($approve == $this->const['DOCUMENT_APPROVE'])
                                    $approve = 'APPROVED';
                                elseif ($approve == $this->const['DOCUMENT_FINAL'])
                                    $approve = 'FINAL APPROVAL';
                                elseif ($approve == $this->const['DOCUMENT_EXECUTE'])
                                    $approve = 'EXECUTED';

                                $myDocs = $this->workflowtrans->fetchAll("workflow_structure_id = $myWorkflow_structure_id AND item_id='$item_id' AND uid='$myUid'", array("date DESC"))->toArray();
                                if (count($myDocs) == 0) {
                                    $result[$item_id] = array(
                                        "id" => $idDocs,
                                        "item_id" => $item_id,
                                        "approve" => $approve,
                                        "uid" => $prevUid
                                    );
                                } else {
                                    $dateAkhir = strtotime($myDocs[0]['date']);
                                    $dateAwal = strtotime($date);
                                    if ($dateAwal > $dateAkhir) {
                                        $result[$item_id] = array(
                                            "id" => $idDocs,
                                            "item_id" => $item_id,
                                            "approve" => $approve,
                                            "uid" => $prevUid
                                        );
                                    }
                                }
                            }
                            $idDocs++;
                        }
                    }
                }
            }

            //Get document yang direject...
//            $sql = "SELECT * FROM (SELECT * FROM workflow_trans WHERE  workflow_item_id = $workflow_item_id AND workflow_structure_id=$myWorkflow_structure_id AND uid = '$myUid' AND approve = 300 ORDER BY date DESC) a GROUP BY a.item_id";
//            $select = $this->db->query($sql);
//            $docs = $select->fetchAll();
//            if (count($docs) > 0)
//            {
//                foreach ($docs as $key2 => $val2)
//                {
//                    $item_id = $val2['item_id'];
//                    $date = $val2['date'];
//                    $cekLast = $this->getDocumentLastStatus($item_id);
//                    if ($cekLast == $this->const['DOCUMENT_RESUBMIT'])
//                    {
//                        if ($this->getDocumentLastStatusByUser($item_id,$myUid) == $this->const['DOCUMENT_REJECT'])
//                        {
//                            $getStart = $this->getDocumentStarterByUser($item_id,$workflow_item_id);
//                            $approve = "REJECTED";
//                            $result[$item_id] = array(
//	    							"id" => $idDocs,
//	    							"item_id" => $item_id,
//                                    "approve" => $approve,
//                                    "uid" => $getStart['uid']
//	    						);
//                            $idDocs++;
//                        }
//                    }
//                }
//            }

            if ($next != 0 && $fetch['is_start'] == 1) {

                $sql = "SELECT * FROM (SELECT * FROM workflow_trans WHERE  workflow_item_id = $workflow_item_id AND approve = 300 ORDER BY date DESC) a GROUP BY a.item_id";
                $select = $this->db->query($sql);
                $docs = $select->fetchAll();
                if (count($docs) > 0) {
                    foreach ($docs as $key2 => $val2) {
                        $approve = $val2['approve'];
                        if ($approve == $this->const['DOCUMENT_REJECT']) {
                            $item_id = $val2['item_id'];
                            $date = $val2['date'];
                            $comment = $val2['comment'];
                            $sign = $val2['signature'];
                            $cekDocs = $this->workflowtrans->fetchAll("workflow_item_id = $workflow_item_id AND workflow_structure_id=$myWorkflow_structure_id AND item_id='$item_id' AND date <= '$date' AND uid='$myUid' AND (approve=100 OR approve=150)", array("date ASC"));
                            $cekDocs = $cekDocs->toArray();
                            if (!$cekDocs) {
                                //Bypass untuk perubahan workflow, dimana starter belum melakukan transaksi (submit/resubmit) dokumen.
                                //Jika document di Reject akan kembali ke starter yang baru
                                //Cek jumlah starter
                                $sql = "SELECT COUNT(*) AS jumlah FROM workflow WHERE  workflow_item_id = $workflow_item_id AND is_start = 1";
                                $select = $this->db->query($sql);
                                $jumlah = $select->fetch();

                                $byPass = false;
                                //Hanya berlaku kalau starter berjumlah 1 orang...
                                if ($jumlah['jumlah'] == 1) {
                                    $byPass = true;
                                } else
                                    continue;
                                //End of Bypass
                            }
                            $otherUserName = $this->ldap->getAccount($val2['uid']);
                            $otherWorkflow_structure_id = $val2['workflow_structure_id'];
                            $id_trans = $val2['workflow_trans_id'];
                            $type = $this->workflowtrans->getDocumentType($id_trans);
                            $prevUid = $myUid;

                            $myDocs = $this->workflowtrans->fetchAll("workflow_item_id = $workflow_item_id AND workflow_structure_id=$myWorkflow_structure_id AND item_id='$item_id' AND date >= '$date' AND uid='$myUid' ", array("date DESC"));

                            if (count($myDocs->toArray()) > 0) {
                                if ($myDocs[0]['approve'] == $this->const['DOCUMENT_APPROVE'] || $myDocs[0]['approve'] == $this->const['DOCUMENT_FINAL'] || $myDocs[0]['approve'] == $this->const['DOCUMENT_RESUBMIT']) {
                                    continue;
                                }
                            }
                            $approve = "REJECTED";

                            $result[$item_id] = array(
                                "id" => $idDocs,
                                "item_id" => $item_id,
                                "approve" => $approve,
                                "uid" => $nextUid
                            );
                            $idDocs++;
                        }
                    }
                }
//	    		$fetch2 = $this->db->query("SELECT * FROM workflow WHERE prev = $myWorkflow_structure_id AND workflow_structure_id=$next AND workflow_item_id=$workflow_item_id AND uid_prev = '$myUid'");
//                $fetch2 = $fetch2->fetchAll();
//                if ($fetch2)
//	    		{
//	    			$otherWorkflow_id = $fetch2['workflow_id'];
//                    $sql = "SELECT * FROM (SELECT * FROM workflow_trans WHERE workflow_structure_id=$next AND workflow_item_id = $workflow_item_id AND uid = '$nextUid' ORDER BY date DESC) a GROUP BY a.item_id";
//	    			$select = $this->db->query($sql);
//                    $docs = $select->fetchAll();
//	    			if (count($docs) > 0)
//	    			{
//	    				foreach ($docs as $key2 => $val2)
//	    				{
//	    					$approve = $val2['approve'];
//	    					if ($approve ==  $this->const['DOCUMENT_REJECT'])
//	    					{
//	    						$id_trans = $val2['workflow_trans_id'];
//		    					$type = $this->workflowtrans->getDocumentType($id_trans);
//		    					$item_id = $val2['item_id'];
//		    					$date = $val2['date'];
//		    					$comment = $val2['comment'];
//		    					$sign = $val2['signature'];
//	    						$myDocs = $this->workflowtrans->fetchAll("workflow_structure_id=$myWorkflow_structure_id AND item_id='$item_id' AND date >= '$date' AND uid='$myUid'",array("date DESC"));
//
//                                if (count($myDocs->toArray()) > 0)
//                                {
//	    							if ($myDocs[0]['approve'] == $this->const['DOCUMENT_APPROVE'] || $myDocs[0]['approve'] == $this->const['DOCUMENT_RESUBMIT'])
//	    							{
//	    								continue;
//	    							}
//	    						}
//		    					$approve = "REJECTED";
//
//		    					$result[$item_id] = array(
//	    							"id" => $idDocs,
//	    							"item_id" => $item_id,
//                                    "approve" => $approve,
//                                    "uid" => $nextUid
//	    						);
//                                $idDocs++;
//	    					}
//	    				}
//	    			}
//	    		}
            }
        }
        return $result;
    }

    function convertDocumentFromWorkflow($workflowTransID = '') {
        $result = $this->workflowtrans->getDocumentType($workflowTransID);
        $workflowType = $result['name'];
        $myClass = $this->model->getModelClass($workflowType);
        $primaryKey = $myClass->getPrimaryKey();
        $docs = $myClass->fetchRow($primaryKey . " = '" . $result['item_id'] . "'")->toArray();
        $docs['doc_type'] = $workflowType;
        return $docs;
    }

    function getDocumentType($workflowTransID = '') {
        $result = $this->workflowtrans->getDocumentType($workflowTransID);
        $workflowType = $result['name'];

        return $workflowType;
    }

    function getDocumentFlow($workflowTransID = '') {
        $result = $this->workflowtrans->fetchRow("workflow_trans_id = $workflowTransID");
        if ($result) {
            $workflowID = $result['workflow_id'];
            $result2 = $this->workflow->fetchRow("workflow_id = $workflowID");
            if ($result2) {
                $workflowItemID = $result2['workflow_item_id'];
                $result3 = $this->workflow->getWorkflowByItemId($workflowItemID);
                if ($result3) {
                    $start = 0;
                    $end = 0;
                    $next = 0;
                    $prev = 0;
                    $flow = array();
                    foreach ($result3 as $key => $val) {
                        if ($val['is_start'] == 1) {
                            $next = $val['next'];
                            $flow[] = $val['master_role_id'];
                        }
                        if ($next > 0 && $next == $val['master_role_id']) {

                            $actionQuery = $this->masterrole->select()
                                    ->setIntegrityCheck(false) // allows joins
                                    ->from($this->masterrole)
                                    ->join('master_login', 'master_login.id = master_role.id_user')
                                    ->where('master_role.id = ' . $val['master_role_id']);
                            $joinedRowset = $this->masterrole->fetchAll($actionQuery);
                            $flow[] = $val['master_role_id'];
                            $next = $val['next'];
                        }
                    }
                    return $flow;
                }
            }
        }
        return false;
    }

    function getUserByRoleId($roleID = '', $all = false) {
        $result = $this->masterrole->fetchRow("id = $roleID");
        if ($result) {
            $uid = $result['id_user'];
            $sql = $this->db->select()
                    ->from(array('ml' => 'master_login'))
                    ->where("ml.id = $uid");
            $fetch = $this->db->query($sql);
            $user = $fetch->fetch();
//			$userInfo = $this->ldap->getAccount($user['uid']);

            if (!$all)
//			    return $userInfo['displayname'][0];
                return QDC_User_Ldap::factory(array("uid" => $user['uid']))->getName();
            else
                return $userInfo;
        }
    }

    function getUserCreByRoleId($roleID = '') {
        $result = $this->masterrole->fetchRow("id = $roleID");
        if ($result) {
            $uid = $result['id_user'];
            $sql = $this->db->select()
                    ->from(array('ml' => 'master_login'))
                    ->where("ml.id = $uid");
            $fetch = $this->db->query($sql);
            $user = $fetch->fetch();


            return $user;
        }
    }

    function getUserCreByWorkflowItemId($ID = '') {
        $result = $this->workflow->fetchRow("workflow_id = $ID");
        if ($result) {
            $roleID = $result['master_role_id'];
            $result2 = $this->masterrole->fetchRow("id = $roleID");
            if ($result2) {
                $role_type_id = $result2['id_role'];
                $sql = $this->db->select()
                        ->from(array('mrt' => 'master_role_type'))
                        ->where("mrt.id = $role_type_id");
                $fetch = $this->db->query($sql);
                $role_type = $fetch->fetch();

                return $role_type;
            }
        }
    }

    public function checkWorkflowInDocs($docsID = '', $userID = '') {
//    	$docs = $this->db->fetchOne($this->db->select()
//								->from(array('w'=> 'workflow'),
//									array('workflow_item_id'))
//								->joinLeft(array('wt'=>'workflow_trans'),
//								          'w.workflow_id = wt.workflow_id')
//								->where("wt.workflow_trans_id=$docsID"));

        if ($userID == '')
            return false;
        $docs = $this->workflowtrans->fetchRow("workflow_trans_id =" . $docsID)->toArray();
        $workflow_item_id = $docs['workflow_item_id'];
        $generic = $docs['generic'];
        if ($generic == '0') {
            $sql = "SELECT
                        w.workflow_item_id
                    FROM
                        workflow w
                    LEFT JOIN
                        master_role mr
                    ON mr.id = w.master_role_id
                    LEFT JOIN
                        master_login ml
                    ON mr.id_user = ml.id
                    WHERE
                        ml.id = $userID
                    AND
                        w.workflow_item_id = $workflow_item_id";
            /*$sql = "SELECT
                        w.workflow_item_id
                    FROM
                        workflow_structure w
                    LEFT JOIN
                        master_role mr
                    ON mr.id = w.master_role_id
                    LEFT JOIN
                        master_login ml
                    ON mr.id_user = ml.id
                    WHERE
                        ml.id = $userID
                    AND
                        w.workflow_item_id = $workflow_item_id";*/
            
        } else {
            $sql = "SELECT
                        wg.workflow_item_id
                    FROM
                        workflow_generic wg
                    LEFT JOIN
                        master_role mr
                    ON mr.id_role = wg.role_id
                    LEFT JOIN master_login m
                    ON m.id = mr.id_user
                    WHERE
                        m.id = $userID
                    AND
                        wg.workflow_item_id = $workflow_item_id";
        }
        $fetch = $this->db->query($sql);
        $user = $fetch->fetchAll();
        if (count($user) > 0)
            return $user[0]['workflow_item_id'];
        else
            return false;
    }

    public function getDocumentLastStatusAll($docsID = '') {
        $sql = "SELECT
                    *
                FROM
                    workflow_trans
                WHERE item_id='$docsID'
                ORDER BY date DESC
                LIMIT 1";
        $fetch = $this->db->query($sql);
        $stat = $fetch->fetch();

        return $stat;
    }

    public function getDocumentLastStatusAllGeneric($docsID = '', $prjKode = '') {
        if ($prjKode != '')
            $where = " AND prj_kode = '$prjKode'";
        $sql = "
                SELECT
                *
                FROM
                    workflow_trans
                WHERE item_id='$docsID'
                  $where
                AND generic = 1
                ORDER BY date DESC
                LIMIT 1";
        $fetch = $this->db->query($sql);
        $stat = $fetch->fetch();

        return $stat;
    }

    public function getDocumentLastStatus($docsID = '', $prjKode = '') {
        if ($prjKode != '')
            $where = " AND prj_kode = '$prjKode'";
        $sql = "SELECT
                    approve
                FROM
                    workflow_trans
                WHERE item_id='$docsID' $where
                ORDER BY date DESC
                LIMIT 1";
        $fetch = $this->db->query($sql);
        $stat = $fetch->fetch();

        return $stat['approve'];
    }

    public function getDocumentLastStatusByApproval($docsID = '') {
        $sql = "SELECT
                    max(approve) as approval
                FROM
                    workflow_trans
                WHERE item_id='$docsID'
                ";
        $fetch = $this->db->query($sql);
        $stat = $fetch->fetch();

        return $stat['approval'];
    }

    public function getAllApproval($docsID = '', $prjKode = '') {
        if ($prjKode != '')
            $where = " AND prj_kode = '$prjKode'";
        $user = '';

        $wTrans = new Admin_Models_Workflowtrans();

        $select = $this->db->select()
                ->from(array($wTrans->__name()))
                ->where("item_id = ?", $docsID)
                ->order(array("date ASC"));

        if ($prjKode)
            $select = $select->where("prj_kode = ?", $prjKode);

        $user = $this->db->fetchAll($select);

//        $sql = "
//                    SELECT
//                        wt.date,ml.uid,mrt.display_name,wt.signature, wt.comment, wt.approve
//                    FROM
//                        workflow_trans wt
//                    LEFT JOIN
//                        workflow w
//                    ON
//                        wt.workflow_id = w.workflow_id
//                    LEFT JOIN
//                        master_role mr
//                    ON
//                        mr.id = w.master_role_id
//                    LEFT JOIN
//                        master_role_type mrt
//                    ON
//                        mr.id_role = mrt.id
//                    LEFT JOIN
//                        master_login ml
//                    ON
//                        mr.id_user = ml.id
//                    WHERE
//                        wt.item_id = '$docsID' $where
//                    ORDER BY wt.date ASC
//
//				";
//		$fetch = $this->db->query($sql);
//		$user = $fetch->fetchAll();
        //attachment files section
        $count_files = 0;
        $files_model = new Default_Models_Files();
        $trano_data = $files_model->fetchAll("trano = '$docsID'");
        if ($trano_data->toArray()) {
            $trano_data = $trano_data->toArray();
            $count_files = count($trano_data);
        }

        //end section

        $downloaded_files_model = new Default_Models_DownloadedFiles();

        $ws = new Admin_Models_Workflowstructure();
        $mr = new Admin_Models_Masterrole();

        foreach ($user as $key => &$val) {
            $seenFile = '';
            $countDownloadFiles = 0;

            $username = $val['uid'];
            $file_downloaded = $downloaded_files_model->fetchRow("trano = '$docsID' and user = '$username'");
            if ($file_downloaded) {
                $file_downloaded = $file_downloaded->toArray();
                $FilesArray = Zend_Json::decode($file_downloaded['filename']);
                $countDownloadFiles = count($FilesArray);
            }

            $masterRole = $ws->fetchRow("id = " . $val['workflow_structure_id']);
            if ($masterRole) {
                $masterRole = $masterRole->toArray();
                $mrId = $masterRole['master_role_id'];

                $role_name = $mr->getRoleFromRoleId($mrId);
            }

            if ($count_files && $countDownloadFiles == $count_files)
                $seenFile = ')&nbsp;&nbsp;(&nbsp;<a class="tooltipDocs" href="#"><img src="/images/icons/fam/report_magnify.png"/><span class="blue">This User has seen all attachment files</span></a>&nbsp;';

            $user[$key]['display_name'] = $role_name['display_name'] . $seenFile;

            $user[$key]['displayname'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
            if ($val['comment'] == '')
                $val['comment'] = "&nbsp;";
            $val['comment'] = str_replace("\r", " ", $val['comment']);
            $val['comment'] = str_replace("\n", " ", $val['comment']);
            if ($val['approve'] == $this->const['DOCUMENT_SUBMIT'])
                $val['approve'] = 'SUBMITTED';
            if ($val['approve'] == $this->const['DOCUMENT_RESUBMIT'])
                $val['approve'] = 'RE-SUBMITTED';
            if ($val['approve'] == $this->const['DOCUMENT_APPROVE'])
                $val['approve'] = '<font color="green">APPROVED</font>';
            if ($val['approve'] == $this->const['DOCUMENT_REJECT'])
                $val['approve'] = '<font color="red">REJECTED</font>';
            if ($val['approve'] == $this->const['DOCUMENT_FINAL'])
                $val['approve'] = '<font color="blue">FINAL APPROVAL</font>';
            if ($val['approve'] == $this->const['DOCUMENT_EXECUTE'])
                $val['approve'] = '<font color="blue">EXCUTED</font>';
        }

        return $user;
    }

    public function getAllReject($docsID = '') {
        $user = '';
        $sql = "SELECT * FROM (
                    SELECT
                        wt.date,ml.uid,mrt.display_name,wt.signature,wt.comment
                    FROM
                        workflow_trans wt
                    LEFT JOIN
                        workflow w
                    ON
                        wt.workflow_id = w.workflow_id
                    LEFT JOIN
                        master_role mr
                    ON
                        mr.id = w.master_role_id
                    LEFT JOIN
                        master_role_type mrt
                    ON
                        mr.id_role = mrt.id
                    LEFT JOIN
                        master_login ml
                    ON
                        mr.id_user = ml.id
                    WHERE
                        wt.item_id = '$docsID'
                    AND wt.approve = 300
                    ORDER BY wt.date DESC
                ) a group by a.uid ORDER BY a.date
				";
        $fetch = $this->db->query($sql);
        $user = $fetch->fetchAll();

        foreach ($user as $key => $val) {
//            $userInfo = $this->ldap->getAccount($val['uid']);
            $user[$key]['comment'] = str_replace("\r", "", $user[$key]['comment']);
            $user[$key]['comment'] = str_replace("\n", "", $user[$key]['comment']);
//			$user[$key]['displayname'] =  $userInfo['displayname'][0];
            $user[$key]['displayname'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
        }

        return $user;
    }

    public function getLastReject($docsID = '') {
        $user = '';
        $sql = "
                    SELECT
                        wt.date,ml.uid,mrt.display_name,wt.signature,wt.comment
                    FROM
                        workflow_trans wt
                    LEFT JOIN
                        workflow w
                    ON
                        wt.workflow_id = w.workflow_id
                    LEFT JOIN
                        master_role mr
                    ON
                        mr.id = w.master_role_id
                    LEFT JOIN
                        master_role_type mrt
                    ON
                        mr.id_role = mrt.id
                    LEFT JOIN
                        master_login ml
                    ON
                        mr.id_user = ml.id
                    WHERE
                        wt.item_id = '$docsID'
                    AND wt.approve = 300
                    ORDER BY wt.date DESC LIMIT 1
				";
        $fetch = $this->db->query($sql);
        $user = $fetch->fetch();

        if ($user) {
//            $userInfo = $this->ldap->getAccount($user['uid']);
            $user['comment'] = str_replace("\r", "", $user['comment']);
            $user['comment'] = str_replace("\n", "", $user['comment']);
//			$user['displayname'] =  $userInfo['displayname'][0];
            $user['displayname'] = QDC_User_Ldap::factory(array("uid" => $user['uid']))->getName();
        }

        return $user;
    }

    public function isDocumentRejected($docsID = '', $uid = '') {
        if ($uid != '')
            $where = " AND uid='$uid'";
        $sql = "SELECT
                    approve
                FROM
                    workflow_trans
                WHERE item_id='$docsID'
                $where
                ORDER BY date DESC
                LIMIT 1";
        $fetch = $this->db->query($sql);
        $stat = $fetch->fetch();

        if ($stat['approve'] == $this->const['DOCUMENT_REJECT']) {
            return true;
        } else
            return false;
    }

    public function getDocumentLastStatusByUser($docsID = '', $uid = '') {
        $sql = "SELECT
                    approve
                FROM
                    workflow_trans
                WHERE
                    item_id = '$docsID'
                AND
                    uid = '$uid'
                ORDER BY date DESC
                LIMIT 1";
        $fetch = $this->db->query($sql);
        $stat = $fetch->fetch();

        return $stat['approve'];
    }

    public function getDocumentStarterByUser($docsID = '', $workflowItemID = '') {
        $sql = "SELECT
                    *
                FROM
                    workflow_trans
                WHERE
                    item_id = '$docsID'
                AND
                    workflow_item_id = $workflowItemID
                AND
                    approve IN (100,150)
                ORDER BY date ASC
                LIMIT 1";
        $fetch = $this->db->query($sql);
        $stat = $fetch->fetch();

        return $stat;
    }

    public function isDocumentInWorkflow($DocsId = '') {
        $sql = "SELECT * FROM workflow_trans
                WHERE item_id = '$DocsId'";
        $fetch = $this->db->query($sql);

        $hasil = $fetch->fetch();
        if (!$hasil)
            return false;
        else
            return true;
    }

    public function isDocumentValidToPrint($trano = '') {
        if ($trano == '')
            return false;
        if (!$this->isDocumentInWorkflow($trano))
            return true;
//        $cekStatus = $this->getDocumentLastStatus($trano);
        $cekStatus = $this->getDocumentLastStatusByApproval($trano);
        if ($cekStatus == $this->const['DOCUMENT_FINAL'] || $cekStatus == $this->const['DOCUMENT_EXECUTE'] || $this->session->userName == 'kurnia')
            return true;
        else
            return $this->error->getErrorMsg(303);
    }

    public function isDocumentFinal($trano = '') {
        if ($trano == '')
            return false;
        if (!$this->isDocumentInWorkflow($trano))
            return true;
//        $cekStatus = $this->getDocumentLastStatus($trano);
        $cekStatus = $this->getDocumentLastStatusByApproval($trano);
        if ($cekStatus == $this->const['DOCUMENT_FINAL'] || $cekStatus == $this->const['DOCUMENT_EXECUTE'])
            return true;
        else
            return $this->error->getErrorMsg(307);
    }

    public function isDocumentValidToSubmit($trano = '') {
        if ($trano == '')
            return false;
        if (!$this->isDocumentInWorkflow($trano))
//            return $this->error->getErrorMsg(306);
            return true;
//        $cekStatus = $this->getDocumentLastStatus($trano);
        $cekStatus = $this->getDocumentLastStatusByApproval($trano);
        if ($cekStatus == $this->const['DOCUMENT_FINAL'] || $cekStatus == $this->const['DOCUMENT_EXECUTE'])
            return true;
        else
            return $this->error->getErrorMsg(307);
    }

    public function getApprovalText($approval = '') {
        if ($approval == $this->const['DOCUMENT_SUBMIT'])
            return 'SUBMITTED';
        if ($approval == $this->const['DOCUMENT_RESUBMIT'])
            return 'RE-SUBMITTED';
        if ($approval == $this->const['DOCUMENT_APPROVE'])
            return '<font color="green">APPROVED</font>';
        if ($approval == $this->const['DOCUMENT_REJECT'])
            return '<font color="red">REJECTED</font>';
        if ($approval == $this->const['DOCUMENT_FINAL'])
            return '<font color="blue">FINAL APPROVAL</font>';
        if ($approval == $this->const['DOCUMENT_EXECUTE'])
            return '<font color="blue">EXCUTED</font>';
    }

    function setWorkflowTransNew($params = array()) {
        //Default params
        //$itemID='',$workflowType='',$paramArray='',$approve='',$items='',$prjKode='',$generic=false,$revisi=false
        
        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }
         


        $insertArray = array();
        $i = 0;

        if ($items != '')
            $result = $items;
        else
            $result = true;

        if (!$generic)
            $addQuery = ' AND is_start = 1';
        else
            $addQuery = ' AND is_start = 1';
  
        if ($approve != $this->const['DOCUMENT_SUBMIT']) {
            $id = $itemID;
            if (!$skipClassCheck) {
                $myClass = $this->model->getModelClass($workflowType);
                var_dump($myClass);
                if ($myClass) {
                    $addQuery = '';
                    $primaryKey = $myClass->getPrimaryKey();
                    var_dump($primaryKey);
                    $result = $myClass->fetchRow($this->db->quoteInto($primaryKey . ' = ?', $id));
                    var_dump($result);
                    if ($items != '') {
                        $result = $result->toArray();
                        foreach ($items as $key => $val) {
                            $result[$key] = $val;
                        }
                    }
                }
            }
        }
              
        $myClass = $this->model->getModelClass($workflowType);
  
        $params['addQuery'] = $addQuery;
        $myWorkflow = $this->checkWorkflow($params);

        if (is_array($myWorkflow[0])) {
            $found = false;
            //Untuk simpan workflow > 1...
            $tmpVal = array();
            if (!$generic) {
                foreach ($myWorkflow as $key => $val) {
                    if ($result['prj_kode'] != '' && $val['prj_kode'] != '') {
                        if (strtolower($val['prj_kode']) === strtolower($result['prj_kode'])) {
                            if ($result['workflow_id'] != '' && $result['workflow_item_id'] != '' && $result['workflow_structure_id'] != '' && $result['uid_next'] != '') {
                                if ($result['workflow_id'] == $val['workflow_id'] && $result['workflow_item_id'] == $val['workflow_item_id'] && $result['workflow_structure_id'] == $val['workflow_structure_id'] && ($result['uid_next'] == $val['uid_next'] || $result['next'] == $val['next'] )) {
                                    $found = true;
                                    $tmpVal[] = $val;
                                    break;
                                }
                            } else {
                                $found = true;
                                $tmpVal[] = $val;
                            }
                        }
                    } else {
                        $found = true;
                        $tmpVal[] = $val;
                    }
                }
            } else {
                foreach ($myWorkflow as $key => $val) {
                    if ($result['prj_kode'] != '' && $val['prj_kode'] != '') {
                        if (strtolower($val['prj_kode']) === strtolower($result['prj_kode'])) {
                            if (($result['workflow_item_id'] != '' && $result['workflow_item_type_id'] != '') || $result['workflow_id'] != '') {
                                if (($result['workflow_item_id'] == $val['workflow_item_id'] && $result['workflow_item_type_id'] == $val['workflow_item_type_id']) || $result['workflow_id'] == $val['workflow_id']) {
                                    foreach ($myWorkflow as $key2 => $val2) {
                                        if ($result['prj_kode'] != '' && $val2['prj_kode'] != '') {
                                            if (strtolower($val2['prj_kode']) === strtolower($result['prj_kode'])) {
                                                if ($result['workflow_item_id'] == $val2['workflow_item_id'] && $result['workflow_item_type_id'] == $val2['workflow_item_type_id'] && $result['workflow_id'] == $val2['workflow_id']) {
                                                    $tmpVal2[] = $val2;
                                                }
                                            }
                                        }
                                    }

                                    $found = true;
                                    $tmpVal[] = $val;
                                    break;
                                }
                            } else {
                                $found = true;
                                $tmpVal[] = $val;
                            }
                        }
                    } else {
                        if ($result['uid_dest'] != '') {
                            $uidDest = $result['uid_dest'];
                            if ($val['is_end'] != 1) {
                                $nextLevel = $val['level'] + 1;
                                $workflowItemId = $val['workflow_item_id'];
                                $workflowItemTypeId = $val['workflow_item_type_id'];
                                $fetch = $this->workflowGeneric->fetchAll("level = $nextLevel AND workflow_item_id = $workflowItemId AND workflow_item_type_id = $workflowItemTypeId");
                                if ($fetch) {
                                    $fetch = $fetch->toArray();
                                    foreach ($fetch as $k2 => $v2) {
                                        $roleId = $v2['role_id'];
                                        $cek = $this->masterrole->cekUserInRole(array(
                                            "roleID" => $roleId,
                                            "userID" => $result['uid_dest']
                                        ));
                                        if ($cek) {
                                            $found = true;
                                            $noProject[$uidDest] = $v2;
                                            if ($noProject[$uidDest]['role_id'] == $roleId)
                                                $tmpVal[] = $val;
                                        }
                                    }
                                }
                            }
                        }
                        else {
                            $found = true;
                            $tmpVal[] = $val;
                        }
                    }
                }
            }
            if (!$found)
                $this->raiseException(300, $returnException);
            else {
                unset($myWorkflow);
                if ($tmpVal2)
                    $myWorkflow = $tmpVal2;
                else
                    $myWorkflow = $tmpVal;
            }
        }

        //Tambah variabel ke $Params
        $params['uidDest'] = $uidDest;
        $params['noProject'] = $noProject;
        if ($lastTrano != '')
            $params['itemID'] = $lastTrano;
        else
            $params['itemID'] = $id;
        $oldResult = $result;
        if (count($myWorkflow) > 1) {
            //Filter workflow jika lebih dari 1 workflow
            //Result: Workflow yang akan dipilih oleh User
            $ret = $this->filterWorkflow($myWorkflow, $params);
            if ($returnException) {
                return $ret;
            }
        } elseif (count($myWorkflow) == 1) {
            $tmp = $myWorkflow[0];
            unset($myWorkflow);
            $myWorkflow = $tmp;
        }

        $insertArray['approve'] = $approve;
        if (!$generic) {
            $myNext = $myWorkflow['next'];
            $insertArray['uid_next'] = $myWorkflow['uid_next'];
            $insertArray['uid_prev'] = $myWorkflow['uid_prev'];
            $myCur = $myWorkflow['workflow_structure_id'];
            $myWorkflow_item = $myWorkflow['workflow_item_id'];
            if ($workflowType == '') {
                $sql = "SELECT wit.name FROM workflow_item w LEFT JOIN workflow_item_type wit ON w.workflow_item_type_id = wit.workflow_item_type_id WHERE w.workflow_item_id = " . $myWorkflow_item;
                $fetch = $this->db->query($sql);
                $fetch = $fetch->fetch();

                $workflowType = $fetch['name'];
            }

            //Tambah variabel ke $Params
            $params['myNext'] = $myNext;
            $params['myCur'] = $myCur;
            $params['myWorkflow_item'] = $myWorkflow_item;
            $params['workflowType'] = $workflowType;
        } else {
            $result = array();
            $workflowItemId = $myWorkflow['workflow_item_id'];
            $workflowItemTypeId = $myWorkflow['workflow_item_type_id'];
            $workflowItemName = $myWorkflow['workflow_item_name'];

            //Tambah variabel ke $Params
            $params['workflowItemId'] = $workflowItemId;
            $params['workflowItemTypeId'] = $workflowItemTypeId;
            if ($myWorkflow['is_end'] != 1) {
                if ($oldResult['uid_next'] != "") {
                    $insertArray['uid_next'] = $oldResult['uid_next'];
                } else {
                    if ($uidDest == '' && count($noProject) == 0) {
                        $nextLevel = $myWorkflow['level'] + 1;
                        $fetch = $this->workflowGeneric->fetchAll("level = $nextLevel AND workflow_item_id = $workflowItemId AND workflow_item_type_id = $workflowItemTypeId");
                        if ($fetch) {
                            $fetch = $fetch->toArray();
                            $bypass = false;
                            //Check if use Override
                            if ($useOverride) {
                                if (count($fetch) == 1) {
                                    $wOveride = new Admin_Models_WorkflowGenericOverride();
                                    $over = $wOveride->getOverride($workflowType, $workflowItemId, $prjKode);

                                    $roleBased = ($over['role_based'] == 1) ? true : false;
                                    $override = $over['data'];
                                    if ($roleBased) {
                                        foreach ($override as $keyOver => $valOver) {
                                            $roleIdOver = $valOver['role_id'];
                                            if ($fetch[0]['role_id'] == $roleIdOver) {
                                                $oNext = $valOver;
                                                //Override selanjutnya lebih dari 1 user
                                                if ($oNext['user'] != '') {
                                                    if ($oNext['user'] != 'ALL') {
                                                        foreach ($oNext['user'] as $keyNextOverride => $valNextOverride) {
                                                            if ($valNextOverride['project'] != '') {
                                                                foreach ($valNextOverride['project'] as $keyNextOverride2 => $valNextOverride2) {
                                                                    if ($valNextOverride2['prj_kode'] == $prjKode) {
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
                                                            $insertArray['uid_next'] = $tmp['uid'];
                                                            $bypass = true;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if (!$bypass) {
                                $params['prjKode'] = $myWorkflow['prj_kode'];
                                $params['workflowItemName'] = $workflowItemName;
                                $params['workflowId'] = $myWorkflow['workflow_id'];
                                if (count($fetch) > 1) {
                                    try {
                                        $this->raiseOptionUser($fetch, $params);
                                    } catch (Exception $e) {
                                        $arrayJson = Zend_Json::decode($e->getMesssage());
                                        return $arrayJson;
                                    }
                                } else {
                                    $params['myCurrentWorkflow'] = $myWorkflow;
                                    if ($this->raiseOptionuser2($fetch, $params) == false)
                                        $this->raiseException(308, $returnException);

                                    $insertArray['uid_next'] = $this->raiseOptionuser2($fetch, $params);
                                }
                            }
                        }
                    }
                    else {
                        if (count($noProject) > 1) {
                            $this->raiseOptionuser3($noProject, $params);
                        } else {
                            $insertArray['uid_next'] = $uidDest;
                            $insertArray['prj_kode'] = 'NOPROJECT-' . $workflowType;
                        }
                    }
                }
                if ($approve == $this->const['DOCUMENT_RESUBMIT']) {
                    $submitter = $this->workflowtrans->getDocumentSubmitter($itemID);
                    if ($submitter) {
                        $insertArray['uid_prev'] = $submitter['uid'];
                    }
                } else {
                    $insertArray['uid_prev'] = $this->session->userName;
                }
            }
        }

        $insertArray['item_type'] = $workflowType;
        if ($approve == $this->const['DOCUMENT_RESUBMIT']) {

            if ($generic)
                $prjKodeGeneric = $myWorkflow['prj_kode'];
            if ($myWorkflow['is_start'] == 0)
                $this->raiseException(302, $returnException);
            $statusDocs = $this->getDocumentLastStatus($id, $prjKodeGeneric);
            if ($statusDocs == '') {
                $insertArray['approve'] = $this->const['DOCUMENT_SUBMIT'];
            } else {

                if (($this->getDocumentLastStatus($id, $prjKodeGeneric) == $this->const['DOCUMENT_FINAL'] && !$revisi) || ($this->getDocumentLastStatus($id, $prjKodeGeneric) == $this->const['DOCUMENT_EXECUTE'] && !$revisi))
                    $this->raiseException(305, $returnException);
                if (($this->getDocumentLastStatus($id, $prjKodeGeneric) != $this->const['DOCUMENT_REJECT'] && !$revisi))
                    $this->raiseException(301, $returnException);
            }
        }

        if (!$generic) {
            if ($approve == $this->const['DOCUMENT_APPROVE']) {
                if ($myNext != 0) {
                    $nextWorkflow = $this->workflow->fetchRow("workflow_structure_id=$myNext AND prev=$myCur AND workflow_item_id=$myWorkflow_item");
                    if ($nextWorkflow->toArray()) {
                        if ($nextWorkflow['is_end'] == 1) {
                            if ($nextWorkflow['is_executor'] == 1)
                                $insertArray['approve'] = $this->const['DOCUMENT_FINAL'];
                            else
                                $insertArray['approve'] = $this->const['DOCUMENT_APPROVE'];
                        }
                    }
                }
                elseif ($myNext == 0) {
                    if ($myWorkflow['is_executor'] == 1)
                        $insertArray['approve'] = $this->const['DOCUMENT_EXECUTE'];
                    elseif ($myWorkflow['is_final'] == 1)
                        $insertArray['approve'] = $this->const['DOCUMENT_FINAL'];
                }
            }
            $insertArray['workflow_structure_id'] = $myWorkflow['workflow_structure_id'];
            $insertArray['prj_kode'] = $myWorkflow['prj_kode'];
        }
        else {
            $insertArray['generic'] = 1;
            if ($insertArray['prj_kode'] == '')
                $insertArray['prj_kode'] = $myWorkflow['prj_kode'];
        }
        $insertArray['workflow_item_id'] = $myWorkflow['workflow_item_id'];
        $insertArray['workflow_id'] = $myWorkflow['workflow_id'];

        //End of InsertArray untuk insert ke database (workflow_trans)
        //Sampai point ini, user sudah valid & document bisa diproses ke workflow
        //Generate Trano baru untuk document khusus Submit
        
        if ($approve == $this->const['DOCUMENT_SUBMIT'] && $lastTrano == '') {
            $counter = new Default_Models_MasterCounter();
            $id = $counter->setNewTrans($workflowType); //Trano baru
        } elseif ($lastTrano != '')
            $id = $lastTrano;

        $insertArray['item_id'] = $id;
        $insertArray['uid'] = $this->session->userName;
        $sign = $this->token->getDocumentSignature();
        $insertArray['signature'] = $sign['signature'];
        $insertArray['date'] = $sign['date'];
        $insertArray['ip'] = $_SERVER["REMOTE_ADDR"];
        $insertArray['computer_name'] = gethostbyaddr($insertArray['ip']);
        $insertArray['browser'] = $_SERVER["HTTP_USER_AGENT"];

        if ($params['captionId'] != '')
            $insertArray['caption_id'] = $params['captionId'];
        if ($params['comment'] != '')
            $insertArray['comment'] = $params['comment'];

        $result = $this->workflowtrans->insert($insertArray);
        if ($result)
            $this->workflowtrans->updateStatusApprove($myClass, $insertArray['item_type'], $insertArray['item_id'], $insertArray['approve']);

        return $id;
    }

    public function checkWorkflow($params = array()) {
        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }
        //For generic workflow
        if ($generic) {
            $myWorkflow = $this->workflowGeneric->getWorkflowByUserID($this->uid, $workflowType, $addQuery);
        } else {
            $myWorkflow = $this->workflow->getWorkflowByUserID($this->uid, $workflowType, $paramArray, true, $addQuery . ' ORDER BY w.prj_kode DESC');
        }

        if (!$myWorkflow) {
            $this->raiseException(300, $returnException);
        }

        return $myWorkflow;
    }

    //Filter workflow jika masih terdapat lebih dari 1 workflow
    //Result : array/JSON dari masing2 workflow yang akan dipilih oleh User
    protected function filterWorkflow($myWorkflow = array(), $params = array()) {
        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }
        $i = 1;
        $result = array();
        foreach ($myWorkflow as $key => $val) {
            if ($approve == $this->const['DOCUMENT_RESUBMIT']) {
//                             if ($this->getDocumentLastStatus($id) != $this->const['DOCUMENT_REJECT'])
//                                 return 301;
                if ($myWorkflow[$key]['is_start'] == 0) {
                    unset($myWorkflow[$key]);
                    continue;
                }
            }
            if (!$generic) {
                $myWorkflow[$key]['id'] = $i;
                $myNext = $val['next'];
                $myCur = $val['workflow_structure_id'];
                $myWorkflow_item = $val['workflow_item_id'];
                $myNext = $this->workflow->fetchRow("workflow_structure_id=$myNext AND prev=$myCur AND workflow_item_id=$myWorkflow_item");
                if ($myNext) {
                    $myNext = $myNext->toArray();
                    $workflowItem = $this->workflowItem->fetchRow("workflow_item_id=$myWorkflow_item");
                    $workflowItemName = $workflowItem['name'];
                    $nextRole = $this->masterrole->getRoleFromRoleId($myNext['master_role_id']);
//                    $nextUser = $this->ldap->getAccount($myNext['uid']);
//                    $myWorkflow[$key]['name'] = $nextUser['displayname'][0];
                    $myWorkflow[$key]['name'] = QDC_User_Ldap::factory(array("uid" => $myNext['uid']))->getName();
                    $myWorkflow[$key]['role_name'] = $nextRole['display_name'];
                    $myWorkflow[$key]['prj_kode'] = $val['prj_kode'];
                    $myWorkflow[$key]['uid_next'] = $myNext['uid'];
                    $myWorkflow[$key]['workflow_item_name'] = $workflowItemName;
                    $result[] = $myWorkflow[$key];

                    $i++;
                }
            } else {
                if ($val['is_end'] != 1) {
                    if ($uidDest == '' && count($noProject) == 0) {
                        $nextLevel = $val['level'] + 1;
                        $workflowItemId = $val['workflow_item_id'];
                        $workflowItemTypeId = $val['workflow_item_type_id'];
                        $workflowId = $val['workflow_id'];
                        $fetch = $this->workflowGeneric->fetchAll("level = $nextLevel AND workflow_item_id = $workflowItemId AND workflow_item_type_id = $workflowItemTypeId");
                        if ($fetch) {
                            $fetch = $fetch->toArray();
                            foreach ($fetch as $k2 => $v2) {
                                $roleId = $v2['role_id'];
                                $prj_kode = $v2['prj_kode'];
                                $users = $this->masterrole->getUserFromRoleAndProject($roleId, $prj_kode);
                                foreach ($users as $k => $v) {
                                    $arrays = array();
//                                    $nextUser = $this->ldap->getAccount($v['uid']);
                                    $arrays['id'] = $i;
//                                    $arrays['name'] = $nextUser['displayname'][0];
                                    $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                                    $arrays['role_name'] = $v2['role_name'];
                                    $arrays['prj_kode'] = $val['prj_kode'];
                                    $arrays['uid_next'] = $v['uid'];
                                    $arrays['workflow_id'] = $workflowId;
                                    $arrays['workflow_item_id'] = $workflowItemId;
                                    $arrays['workflow_item_type_id'] = $workflowItemTypeId;
                                    $arrays['workflow_item_name'] = $v2['workflow_item_name'];
                                    $arrays['trano'] = $itemID;
                                    $result[] = $arrays;
                                    $i++;
                                }
                            }
                        }
                    } else {
//                        $nextUser = $this->ldap->getAccount($uidDest);
                        foreach ($noProject as $k3 => $v3) {
                            $roles = $this->masterrole->getRoleFromRoleId($v3['role_id']);
                            $arrays['id'] = $i;
//                            $arrays['name'] = $nextUser['displayname'][0];
                            $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $uidDest))->getName();
                            $arrays['role_name'] = $roles['role_name'];
                            $arrays['prj_kode'] = 'NOPROJECT';
                            $arrays['uid_next'] = $k3;
                            $arrays['workflow_item_id'] = $workflowItemId;
                            $arrays['workflow_item_type_id'] = $workflowItemTypeId;
                            $arrays['workflow_item_name'] = $v3['workflow_item_name'];
                            $arrays['trano'] = $itemID;
                            $result[] = $arrays;
                            $i++;
                        }
                    }
                }
            }
        }
        if (count($result) == 0) {
            try {
                $this->raiseException(308, $returnException);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
//                return array(
//                    "code" => 308,
//                    "msg" => $e->getMessage()
//                );
            }
        } else {
            $hasil = Zend_Json::encode($result);
            echo "{success: true, user:$hasil, prjKode: \"$prjKode\"}";
            die;
//            try
//            {
//                $this->raiseOption($result,$returnException);
//            }
//            catch (Exception $e)
//            {
//                return $result;
//            }
        }
    }

    //Raise exception buat tampilkan JSON atau return array berisikan deskripsi Error
    //Lihat Helper Bootstrap.php (Main/utama) dan Error.php untuk jenis kode dan string message dari masing2 error
    protected function raiseException($errorCode = 0, $return = false, $params = array()) {
        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }
        $msg = $this->error->getErrorMsg($errorCode);

        if ($errorCode == 308) {
//            $msg = str_replace("{ROLE_NAME}",$role_name,$msg);
        }

        if (!$return) {
            echo "{success: false, msg:\"$msg\"}";
            die;
        }

        throw new Exception($msg);
    }

    protected function raiseOption($workflows = array(), $return = false) {
        $hasil = Zend_Json::encode($workflows);
        $msg = "{success: true, user:$hasil}";
        if (!$return) {
            echo $msg;
            die;
        }

        throw new Exception($msg);
    }

    protected function raiseOptionUser($fetch = array(), $params = array(), $return = false) {
        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }
        $i = 1;
        foreach ($fetch as $k2 => $v2) {
            $roleId = $v2['role_id'];
            $users = $this->masterrole->getUserFromRoleAndProject($roleId, $prjKode);
//                                            $users = $this->masterrole->getUserFromRoleAndProject($roleId);
            foreach ($users as $k => $v) {
                $arrays = array();
//                $nextUser = $this->ldap->getAccount($v['uid']);
                $arrays['id'] = $i;
//                $arrays['name'] = $nextUser['displayname'][0];
                $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                $arrays['role_name'] = $v2['role_name'];
                $arrays['prj_kode'] = $v2['prj_kode'];
                $arrays['uid_next'] = $v['uid'];
                $arrays['workflow_id'] = $workflowId;
                $arrays['workflow_item_id'] = $workflowItemId;
                $arrays['workflow_item_type_id'] = $workflowItemTypeId;
                $arrays['workflow_item_name'] = $v2['workflow_item_name'];
                $arrays['trano'] = $itemID;
                $result[] = $arrays;
                $i++;
            }
        }
        $hasil = Zend_Json::encode($result);
        if (!$return) {

            echo "{success: true, user:$hasil, prjKode: \"$prjKode\"}";
            die;
        }

        throw new Exception($hasil);
    }

    protected function raiseOptionUser2($fetch = array(), $params = array(), $return = false) {
        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }
        $i = 1;
        $roleId = $fetch[0]['role_id'];
        $users = $this->masterrole->getUserFromRoleAndProject($roleId, $fetch[0]['prj_kode']);
        if (count($users) > 1) {
            $found = false;
            if ($useOverride) {
                $wOveride = new Admin_Models_WorkflowGenericOverride();
                $override = $wOveride->getOverride($workflowType, $workflowItemId, $fetch[0]['prj_kode']);
                $myLevel = $myCurrentWorkflow['level'];

                $oCurrent = $override[$myLevel];
                //Override lebih dari 1 user
                if ($oCurrent['user'] != '') {
                    foreach ($oCurrent['user'] as $k2 => $v2) {
                        if ($v2['uid'] == $uidApproval) {
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
                            foreach ($oNext['user'] as $k2 => $v2) {
                                if ($v2['project'] != '') {
                                    foreach ($v2['project'] as $k3 => $v3) {
                                        if ($v3['prj_kode'] == $fetch[0]['prj_kode']) {
                                            $tmp = $v2;
                                            break;
                                        }
                                    }
                                } else {
                                    $tmp = $oNext;
                                    break;
                                }
                            }
                        } else {
                            if ($oNext['project'] != '') {
                                foreach ($oNext['project'] as $k2 => $v2) {
                                    if ($v2['prj_kode'] == $fetch[0]['prj_kode']) {
                                        $tmp = $oNext;
                                        break;
                                    }
                                }
                            } else
                                $tmp = $oNext;
                        }


                        if ($tmp != '') {
                            foreach ($users as $k2 => $v2) {
                                if ($v2['uid'] == $tmp['uid']) {
                                    $myWork = $v2;
                                    $found = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if ($found) {
                return $myWork['uid'];
            }

            foreach ($users as $k => $v) {
                $arrays = array();
//                $nextUser = $this->ldap->getAccount($v['uid']);
                $arrays['id'] = $i;
//                $arrays['name'] = $nextUser['displayname'][0];
                $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                $arrays['role_name'] = $fetch[0]['role_name'];
                $arrays['prj_kode'] = $fetch[0]['prj_kode'];
                $arrays['uid_next'] = $v['uid'];
                $arrays['workflow_id'] = $workflowId;
                $arrays['workflow_item_id'] = $workflowItemId;
                $arrays['workflow_item_type_id'] = $workflowItemTypeId;
                $arrays['workflow_item_name'] = $workflowItemName;
                ;
                $arrays['trano'] = $itemID;
                $result[] = $arrays;
                $i++;
            }
            if (!$return) {
                $hasil = Zend_Json::encode($result);
                echo "{success: true, user:$hasil, prjKode: \"{$fetch[0]['prj_kode']}\"}";
                die;
            }
        } else {
            if ($users == '')
                return false;
            else
                return $users[0]['uid'];
        }
    }

    protected function raiseOptionUser3($noProject = array(), $params = array(), $return = false) {
        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }
        $i = 1;
//        $nextUser = $this->ldap->getAccount($uidDest);
        foreach ($noProject as $k3 => $v3) {
            $roles = $this->masterrole->getRoleFromRoleId($v3['role_id']);
            $arrays['id'] = $i;
//            $arrays['name'] = $nextUser['displayname'][0];
            $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $uidDest))->getName();
            $arrays['role_name'] = $roles['role_name'];
            $arrays['prj_kode'] = 'NOPROJECT';
            $arrays['uid_next'] = $k3;
            $arrays['workflow_item_name'] = $v3['workflow_item_name'];
            $arrays['trano'] = $itemID;
            $result[] = $arrays;
            $i++;
        }
        if (!$return) {
            $hasil = Zend_Json::encode($result);
            echo "{success: true, user:$hasil}";
            die;
        }
    }

    function checkWorkflowTrans($params = array()) {
        //Default params
        //$itemID='',$workflowType='',$paramArray='',$approve='',$items='',$prjKode='',$generic=false,$revisi=false

        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }

        $insertArray = array();
        $i = 0;

        if ($items != '')
            $result = $items;
        else
            $result = true;

        if (!$generic)
            $addQuery = ' AND is_start = 1';
        else
            $addQuery = ' AND is_start = 1';

        $params['addQuery'] = $addQuery;
        try {
            $myWorkflow = $this->checkWorkflow($params);
        } catch (Exception $e) {
            if ($returnException) {
                return $e->getMessage();
            } else
                return false;
        }

        $found = false;
        if (is_array($myWorkflow[0])) {
            //Untuk simpan workflow > 1...
            $tmpVal = array();
            if (!$generic) {
                foreach ($myWorkflow as $key => $val) {
                    if ($result['prj_kode'] != '' && $val['prj_kode'] != '') {
                        if (strtolower($val['prj_kode']) === strtolower($result['prj_kode'])) {
                            if ($result['workflow_id'] != '' && $result['workflow_item_id'] != '' && $result['workflow_structure_id'] != '' && $result['uid_next'] != '') {
                                if ($result['workflow_id'] == $val['workflow_id'] && $result['workflow_item_id'] == $val['workflow_item_id'] && $result['workflow_structure_id'] == $val['workflow_structure_id'] && ($result['uid_next'] == $val['uid_next'] || $result['next'] == $val['next'] )) {
                                    $found = true;
                                    $tmpVal[] = $val;
                                    break;
                                }
                            } else {
                                $found = true;
                                $tmpVal[] = $val;
                            }
                        }
                    } else {
                        $found = true;
                        $tmpVal[] = $val;
                    }
                }
            } else {
                foreach ($myWorkflow as $key => $val) {
                    if ($result['prj_kode'] != '' && $val['prj_kode'] != '') {
                        if (strtolower($val['prj_kode']) === strtolower($result['prj_kode'])) {
                            if (($result['workflow_item_id'] != '' && $result['workflow_item_type_id'] != '') || $result['workflow_id'] != '') {
                                if (($result['workflow_item_id'] == $val['workflow_item_id'] && $result['workflow_item_type_id'] == $val['workflow_item_type_id']) || $result['workflow_id'] == $val['workflow_id']) {
                                    $found = true;
                                    $tmpVal[] = $val;
                                    break;
                                }
                            } else {
                                $found = true;
                                $tmpVal[] = $val;
                            }
                        }
                    } else {
                        if ($result['uid_dest'] != '') {
                            $uidDest = $result['uid_dest'];
                            if ($val['is_end'] != 1) {
                                $nextLevel = $val['level'] + 1;
                                $workflowItemId = $val['workflow_item_id'];
                                $workflowItemTypeId = $val['workflow_item_type_id'];
                                $fetch = $this->workflowGeneric->fetchAll("level = $nextLevel AND workflow_item_id = $workflowItemId AND workflow_item_type_id = $workflowItemTypeId");
                                if ($fetch) {
                                    $fetch = $fetch->toArray();
                                    foreach ($fetch as $k2 => $v2) {
                                        $roleId = $v2['role_id'];
                                        $cek = $this->masterrole->cekUserInRole(array(
                                            "roleID" => $roleId,
                                            "userID" => $result['uid_dest']
                                        ));
                                        if ($cek) {
                                            $found = true;
                                            $noProject[$uidDest] = $v2;
                                            if ($noProject[$uidDest]['role_id'] == $roleId)
                                                $tmpVal[] = $val;
                                        }
                                    }
                                }
                            }
                        }
                        else {
                            $found = true;
                            $tmpVal[] = $val;
                        }
                    }
                }
            }
            if (!$found) {
                $this->raiseException(300, $returnException);
            } else {
                unset($myWorkflow);
                $myWorkflow = $tmpVal;
            }
        }

        if (!$found) {
            try {
                $this->raiseException(300, $returnException);
            } catch (Exception $e) {
                if ($returnException) {
                    return $e->getMessage();
                } else
                    return false;
            }
        }

        return true;
    }

    public function getLastRejectGeneric($docsID = '') {
        $user = '';
        $sql = "
                    SELECT
                        wt.date,
                        wt.uid,
                        wg.role_name,
                        wt.signature,
                        wt.comment
                    FROM
                        workflow_trans wt
                    LEFT JOIN
                        workflow_generic wg
                    ON
                        wt.workflow_id = wg.workflow_id
                    AND
                        wt.workflow_item_id = wg.workflow_item_id
                    WHERE
                        wt.item_id = '$docsID'
                    AND wt.approve = 300
                    ORDER BY wt.date DESC LIMIT 1
				";
        $fetch = $this->db->query($sql);
        $user = $fetch->fetch();

        if ($user) {
            $user['comment'] = str_replace("\r", "", $user['comment']);
            $user['comment'] = str_replace("\n", "", $user['comment']);
            $user['displayname'] = QDC_User_Ldap::factory(array("uid" => $user['uid']))->getName();
        }

        return $user;
    }

    public function getAllApprovalGeneric($docsID = '', $prjKode = '') {
        if ($prjKode != '')
            $where = " AND wt.prj_kode = '$prjKode'";
        $user = '';
        $sql = "
                    SELECT
                        wt.date,
                        wt.uid,
                        wg.role_name,
                        wt.signature,
                        wt.comment,
                        wt.approve
                    FROM
                        workflow_trans wt
                    LEFT JOIN
                        workflow_generic wg
                    ON
                        wt.workflow_id = wg.workflow_id
                    AND
                        wt.workflow_item_id = wg.workflow_item_id
                    WHERE
                        wt.item_id = '$docsID' $where
                    ORDER BY wt.date ASC

				";
        $fetch = $this->db->query($sql);
        $user = $fetch->fetchAll();

        //attachment files section
        $count_files = 0;
        $files_model = new Default_Models_Files();
        $trano_data = $files_model->fetchAll("trano = '$docsID'");
        if ($trano_data->toArray()) {
            $trano_data = $trano_data->toArray();
            $count_files = count($trano_data);
        }

        //end section

        $downloaded_files_model = new Default_Models_DownloadedFiles();

        foreach ($user as $key => &$val) {

            $seenFile = '';
            $countDownloadFiles = 0;

            $username = $val['uid'];
            $file_downloaded = $downloaded_files_model->fetchRow("trano = '$docsID' and user = '$username'");
            if ($file_downloaded) {
                $file_downloaded = $file_downloaded->toArray();
                $FilesArray = Zend_Json::decode($file_downloaded['filename']);
                $countDownloadFiles = count($FilesArray);
            }

            if ($count_files && $countDownloadFiles == $count_files)
                $seenFile = ')&nbsp;&nbsp;(&nbsp;<a class="tooltipDocs" href="#"><img src="/images/icons/fam/report_magnify.png"/><span class="blue">This User has seen all attachment files</span></a>&nbsp;';

            $user[$key]['role_name'] .= $seenFile;
            $user[$key]['display_name'] = $user[$key]['role_name'];
            $user[$key]['displayname'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
            if ($val['comment'] == '')
                $val['comment'] = "&nbsp;";
            $val['comment'] = str_replace("\r", " ", $val['comment']);
            $val['comment'] = str_replace("\n", " ", $val['comment']);
            if ($val['approve'] == $this->const['DOCUMENT_SUBMIT'])
                $val['approve'] = 'SUBMITTED';
            if ($val['approve'] == $this->const['DOCUMENT_RESUBMIT'])
                $val['approve'] = 'RE-SUBMITTED';
            if ($val['approve'] == $this->const['DOCUMENT_APPROVE'])
                $val['approve'] = '<font color="green">APPROVED</font>';
            if ($val['approve'] == $this->const['DOCUMENT_REJECT'])
                $val['approve'] = '<font color="red">REJECTED</font>';
            if ($val['approve'] == $this->const['DOCUMENT_FINAL'])
                $val['approve'] = '<font color="blue">FINAL APPROVAL</font>';
            if ($val['approve'] == $this->const['DOCUMENT_EXECUTE'])
                $val['approve'] = '<font color="blue">EXCUTED</font>';
        }

        return $user;
    }

    public function filterWorkflowGenericOverride($params = array(), $override = array()) {
        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }
        $i = 1;
        $roleId = $fetch[0]['role_id'];
        $users = $this->masterrole->getUserFromRoleAndProject($roleId, $fetch[0]['prj_kode']);
        if (count($users) > 1) {
            foreach ($users as $k => $v) {
                $arrays = array();
//                $nextUser = $this->ldap->getAccount($v['uid']);
                $arrays['id'] = $i;
//                $arrays['name'] = $nextUser['displayname'][0];
                $arrays['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                $arrays['role_name'] = $fetch[0]['role_name'];
                $arrays['prj_kode'] = $fetch[0]['prj_kode'];
                $arrays['uid_next'] = $v['uid'];
                $arrays['workflow_id'] = $workflowId;
                $arrays['workflow_item_id'] = $workflowItemId;
                $arrays['workflow_item_type_id'] = $workflowItemTypeId;
                $arrays['workflow_item_name'] = $workflowItemName;
                ;
                $arrays['trano'] = $itemID;
                $result[] = $arrays;
                $i++;
            }
            if (!$return) {
                $hasil = Zend_Json::encode($result);
                echo "{success: true, user:$hasil, prjKode: \"{$fetch[0]['prj_kode']}\"}";
                die;
            }
        }
    }

    public function getAllRejectNew($trano = '') {
        $select = $this->db->select()
                ->from(array($this->workflowtrans->__name()))
                ->where("approve = 300")
                ->order(array("date desc"));

        $subselect = $this->db->select()
                ->from(array("a" => $select))
                ->group(array("uid"))
                ->order(array("date desc"));

        $data = $this->db->fetchAll($subselect);

        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
            }
        }
        return $data;
    }

}

?>
