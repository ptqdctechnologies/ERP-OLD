<?php

class ProjectManagement_TimesheetController extends Zend_Controller_Action {

    private $session;
    private $const;
    private $timesheet;
    private $workflowTrans;
    private $workflowClass;
    private $request;
    private $json;
    private $sessionID;
    private $workflow;
    private $project;
    private $error;
    private $log;
    private $util;
    private $db;

    public function init() {

        $this->error = $this->_helper->getHelper('error');
        $this->const = Zend_Registry::get('constant');
        $this->db = Zend_Registry::get('db');
        Zend_Loader::loadClass('Zend_Json');
        $this->session = new Zend_Session_Namespace('login');
        $this->sessionID = Zend_Session::getId();

        $this->request = $this->getRequest();
        $this->json = $this->request->getParam('posts');
        $this->util = $this->_helper->getHelper('utility');

        $this->log = new Admin_Models_Logtransaction();
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Models_Workflow();
        $this->project = new Default_Models_MasterProject();
        $this->timesheet = new ProjectManagement_Models_Timesheet();
        $this->workflow = $this->_helper->getHelper('workflow');
    }

    public function timesheetAction() {
        
    }

    private function lastTimesheet($uid, $month) {
        $month = (integer) $month;
        $result = $this->timesheet->getLastTimesheet($uid, $month);
        $ldap = new Default_Models_Ldap();
        $person = array();
        $result2 = array();
        foreach ($result as $key => &$val) {
//            if ($val['approve'] == 100 || $val['approve'] == 200)
//                $cid = 2;
//            else if ($val['approve'] == 400)
//                $cid = 3;
//            else
//                $cid = 4;
            if ($val['is_submit'] == '1')
                $cid = 2;
            else
                $cid = 4;
            $title = $val['prj_kode'];
            if ($val['sit_kode'] != '')
                $title .= '-' . $val['sit_kode'];

            if ($val['allday'] == "1")
                $ad = true;
//            if ($val['approve'] != '' && $val['is_submit'] == '1')
//            if ($val['is_submit'] == '1')
            $app = true;
            $behalfof = '';
            $behalfofname = '';
            if ($val['behalfof'] != '') {
                $behalfof = $val['behalfof'];
                if ($person[$behalfof]) {
                    $behalfofname = $person[$behalfof];
                } else {
//                    $account = $ldap->getAccount($behalfof);
//                    $behalfofname = $account['displayname'][0];
                    $behalfofname = QDC_User_Ldap::factory(array("uid" => $behalfof))->getName();
                    $person[$behalfof] = $behalfofname;
                }
            }
            $result2[] = array(
                "id" => $key + 1,
                "recid" => $val['id'],
                "title" => $title,
                "start" => $val['start'],
                "end" => $val['end'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "cid" => $cid,
                "notes" => $val['notes'],
                "ad" => $ad,
                "behalfof" => $behalfof,
                "behalfofname" => $behalfofname,
                "is_prev" => $app
            );
        }

        return $result2;
    }

    public function getlasttimesheetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $month = $this->getRequest()->getParam("month");
        $result2 = $this->lastTimesheet($this->session->userName, $month);
        if ($result2 == '')
            $result2 = array();
        Zend_Loader::loadClass('Zend_Json');
        $return = array(
            "success" => true,
            "evts" => $result2
        );
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addtimesheetAction() {
        $month = (integer) date("m");
        $result2 = $this->lastTimesheet($this->session->userName, $month);

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($result2);

        $this->view->json = $jsonData;

        $now = date("d M Y");

        $arrayTgl = array();
        $jumLimit = 33;
        $min = date(' d M Y', strtotime('-' . ($jumLimit) . ' day', strtotime($now)));
        for ($i = 0; $i <= 33; $i++) {

            $limit = strtotime('-' . ($jumLimit - $i) . ' day', strtotime($now));
            $cek = date("D", $limit);
            if ($cek == 'Sun') {
                if ($i == 0) {
                    $newlimit = strtotime('-1 day', $limit);
                    $arrayTgl[] = date('d M Y', $newlimit);
                    $min = date('d M Y', $newlimit);
                } else {
                    $newlimit = strtotime('-' . ($jumLimit + 1) . ' day', strtotime($now));
                    $arrayTgl[] = date('d M Y', $newlimit);
                    $min = date('d M Y', $newlimit);
                }
            } else {
                $arrayTgl[] = date('d M Y', $limit);
            }
        }


        $this->view->curDate = $now;
        $this->view->minDate = $min;
        $this->view->limitDate = $arrayTgl;
    }

    public function apptimesheetAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        $draft = $this->request->getParam('draft');
        if ($draft == '')
            $draft = false;
        else
            $draft = true;
        $fromdraft = $this->request->getParam('fromdraft');
        if ($fromdraft == '')
            $fromdraft = false;
        else
            $fromdraft = true;
        $result2 = array();
        $this->view->draft = $draft;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/TIMESHEET';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $ldap = new Default_Models_Ldap();
        $approve = $this->getRequest()->getParam("approve");
        if ($approve == '') {

            $json = $this->request->getParam('posts');
            $trano = $this->getRequest()->getParam("trano");
            $etc = $this->getRequest()->getParam("etc");
            Zend_Loader::loadClass('Zend_Json');
            $data = Zend_Json::decode($json);
            if ($draft) {
                $etc = Zend_Json::decode($etc);
                $trano = $etc[0]['trano'];
            }
            $result = array();
            $sum = 0;
            if ($fromdraft == '') {
                foreach ($data as $key => &$val) {
                    $count = 0;
                    if ($val['RecordId'] != '')
                        $result2[$key]['id'] = $val['RecordId'];
                    $result2[$key]['start'] = date('Y-m-d H:i:s', strtotime($val['StartDate']));
                    $result2[$key]['end'] = date('Y-m-d H:i:s', strtotime($val['EndDate']));
                    $result2[$key]['prj_kode'] = $val['ProjectCode'];
                    $result2[$key]['sit_kode'] = $val['SiteCode'];
                    $result2[$key]['notes'] = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", urldecode($val['Notes']));
                    $result2[$key]['allday'] = $val['IsAllDay'];
                    $result2[$key]['behalfof'] = $val['BehalfOf'];
                    $result2[$key]['recid'] = $val['RecordId'];

                    $prj_kode = $val['ProjectCode'];
                    $start = new DateTime($val['StartDate']);
                    $end = new DateTime($val['EndDate']);

                    $a = $start->format('Y-m-d');
                    $b = $end->format('Y-m-d');

                    if ($a != $b) {
                        $interval = new DateInterval('P1D');
                        $period = new DatePeriod($start, $interval, $end);

                        foreach ($period as $dt) {
                            $cek = $dt->format("Y-m-d") . " 17:00:00";
                            $diff = new DateTime($cek);
                            $int = $diff->diff($dt);
                            $count1 = $int->format('%h');
                            if ($val['IsAllDay'] == true) {
                                $count1 = 8;
                            } else {
                                if ($count1 > 8)
                                    $count1 = 8;
                            }
                            $count += $count1;
                        }
                    }
                    else {
                        $diff = $end->diff($start);
                        $count = $diff->format('%h');
                        if ($val['IsAllDay'] == true) {
                            $count = 8;
                        } else {
                            $c = $end->format('H:i:s');
                            $d = $start->format('H:i:s');

                            if ($d == '08:00:00' && $c == '17:00:00') {
                                $count = 8;
                            } else {
                                $e = new DateTime($a . " 12:00:00");
                                $f = new DateTime($b . " 17:00:00");
                                if ($start < $e && $end >= $f) {
                                    $count--;
                                }
                            }
                        }
                    }

                    $result2[$key]['hour'] = $count;
                    $result2[$key]['uid'] = $this->session->userName;

                    $sum += $count;
                    if ($val['Uid'] != '' && $this->view->uidSubmit == '') {
//                        $account = $ldap->getAccount($val['Uid']);
//                        $this->view->uidSubmit = $account['displayname'][0];
                        $this->view->uidSubmit = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
                    }
                    if ($val['BehalfOf'] != '' && $this->view->uidBehalf == '') {
//                        $account = $ldap->getAccount($val['BehalfOf']);
//                        $this->view->uidBehalf = $account['displayname'][0];
                        $this->view->uidBehalf = QDC_User_Ldap::factory(array("uid" => $val['behalfOf']))->getName();
                    }
                    if ($val['CreateDate'] != '' && $this->view->dateSubmit == '') {
                        $this->view->dateSubmit = date('d-m-Y H:i:s');
                    }

                    $uid = $this->sesiion->userName;
                    if ($val['BehalfOf'] != '')
                        $uid = $val['BehalfOf'];
                    if (count($result[$uid]['project'][$prj_kode]) > 0) {
                        $result[$uid]['project'][$prj_kode]['hour'] += $count;
                    } else {
                        $fetch = $this->project->fetchRow("Prj_Kode = '$prj_kode'");
                        $prj_nama = $fetch['Prj_Nama'];
                        $result[$uid]['project'][$prj_kode] = array(
                            'prj_kode' => $prj_kode,
                            'prj_nama' => $prj_nama,
                            'hour' => floatval($count)
                        );
                        if ($val['BehalfOf'] != '') {
                            $ldapdir = new Default_Models_Ldap();
//                            $account = $ldapdir->getAccount($val['BehalfOf']);
//                            $result[$uid]['name'] = $account['displayname'][0];
                            $result[$uid]['name'] = QDC_User_Ldap::factory(array("uid" => $val['behalfOf']))->getName();
                        }
                    }
                }
            } else {
                $uid = $this->session->userName;
                $allDraft = $this->getRequest()->getParam("alldraft");
                if ($allDraft) {
                    $fetch = $this->timesheet->fetchAll("uid = '$uid' AND is_submit = 0", array("prj_kode ASC"));
                    if ($fetch) {
                        $data = $fetch->toArray();
                    }
                }

                foreach ($data as $key => $val) {
                    $prjKode = $val['prj_kode'];
                    $count = $val['hour'];
                    $sum += $count;
                    if (count($result[$prjKode]) > 0) {
                        $result[$prjKode]['hour'] += $count;
                    } else {
                        $fetch = $this->project->fetchRow("Prj_Kode = '$prjKode'");
                        $prj_nama = $fetch['Prj_Nama'];
                        $result[$prjKode] = array(
                            'prj_kode' => $prjKode,
                            'prj_nama' => $prj_nama,
                            'hour' => floatval($count)
                        );
                    }
                }
                $result2 = $data;
            }
            if ($this->view->uidSubmit == '') {
//                $account = $ldap->getAccount($this->session->userName);
//                $this->view->uidSubmit = $account['displayname'][0];
                $this->view->uidSubmit = QDC_User_Session::factory()->getCurrentName();
            }
            if ($this->view->dateSubmit == '') {
                $this->view->dateSubmit = date('d-m-Y H:i:s');
            }
            $this->view->data = $result;
            $this->view->jsonResult = Zend_Json::encode($result2);
            $this->view->jsonEtc = $etc;
            $this->view->sum = $sum;

            $this->view->trano = $trano;

            if ($from == 'edit') {
                $this->view->edit = true;
            } else {
                $this->view->draft = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            $this->view->transID = $approve;
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user) {
                    $id = $docs['workflow_trans_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];
                    $prjKode = $docs['prj_kode'];
                    $timesheet = $this->timesheet->fetchAll("trano = '$approve' AND prj_kode = '$prjKode'");
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    if ($timesheet) {
                        $timesheet = $timesheet->toArray();
                        $result = array();
                        $sum = 0;
                        foreach ($timesheet as $key => &$val) {
                            $count = 0;
                            $result2[$key]['start'] = date('Y-m-d H:i:s', strtotime($val['start']));
                            $result2[$key]['end'] = date('Y-m-d H:i:s', strtotime($val['end']));
                            $result2[$key]['prj_kode'] = $val['prj_kode'];
                            $result2[$key]['sit_kode'] = $val['sit_kode'];
                            $result2[$key]['notes'] = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $val['notes']);
                            $result2[$key]['allday'] = $val['allday'];
                            $result2[$key]['hour'] = $val['hour'];
                            $result2[$key]['uid'] = $val['uid'];

                            if ($val['uid'] != '' && $this->view->uidSubmit == '') {
//                                $account = $ldap->getAccount($val['uid']);
//                                $this->view->uidSubmit = $account['displayname'][0];
                                $this->view->uidSubmit = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
                            }
                            if ($val['date'] != '' && $this->view->dateSubmit == '') {
                                $this->view->dateSubmit = date('d-m-Y H:i:s', strtotime($val['date']));
                            }

                            $count = $val['hour'];
                            $sum += $count;
                            $uid = $val['uid'];
                            $prj_kode = $val['prj_kode'];
                            if ($val['behalfof'] != '')
                                $uid = $val['behalfof'];
                            if (count($result[$uid]['project'][$prj_kode]) > 0) {
                                $result[$uid][$prj_kode]['project']['hour'] += $count;
                            } else {
                                $fetch = $this->project->fetchRow("Prj_Kode = '$prj_kode'");
                                $prj_nama = $fetch['Prj_Nama'];
                                $result[$uid]['project'][$prj_kode] = array(
                                    'prj_kode' => $prj_kode,
                                    'prj_nama' => $prj_nama,
                                    'hour' => floatval($count)
                                );
                                if ($val['behalfof'] != '') {
                                    $ldapdir = new Default_Models_Ldap();
//                                    $account = $ldapdir->getAccount($val['behalfof']);
//                                    $result[$uid]['name'] = $account['displayname'][0];
                                    $result[$uid]['name'] = QDC_User_Ldap::factory(array("uid" => $val['behalfOf']))->getName();
                                }
                            }
                        }

                        $this->view->data = $result;
                        $this->view->sum = $sum;
                        $this->view->jsonResult = Zend_Json::encode($result2);

                        $this->view->jsonResult = str_replace('\n', "", $this->view->jsonResult);
                        $this->view->jsonResult = str_replace('\r', "", $this->view->jsonResult);
                        $this->view->prjKode = $prjKode;
                    }

                    $allReject = $this->workflow->getAllReject($approve);
                    $lastReject = $this->workflow->getLastReject($approve);
                    $this->view->lastReject = $lastReject;
                    $this->view->allReject = $allReject;
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->trano = $approve;
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function inserttimesheetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
          
        //activity log
        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();
        $activityFile=array();
        
        $json = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($json);
        $jsonEtc = Zend_Json::decode($etc);
         $sitKode = $val['sit_kode'];

        //Cek kalo sudah ada trano yg diinsert ke workflow_trans
        $tranoExist = $this->getRequest()->getParam('trano');
        $counter = new Default_Models_MasterCounter();

//        $counter->update(array("urut" => $last),$where);
        $uid_next = $this->getRequest()->getParam('uid_next');
        $workflowItem = $this->getRequest()->getParam('workflow_item_id');
        $workflowItemtype = $this->getRequest()->getParam('workflow_item_type_id');
        $workflowId = $this->getRequest()->getParam('workflow_id');
        $t = array();
        $projek = array();
        $workError = array();
        $gagal = array();
        foreach ($jsonData as $k => $v) {
            $prjKode = $v['prj_kode'];
            $t[$prjKode][] = $v;
        }
        if ($tranoExist != '' && $tranoExist != 'null' && $tranoExist != null)
            $trano = $tranoExist;
        foreach ($t as $k => $v) {
            $cek = $this->workflowTrans->fetchRow("prj_kode = '$k' AND item_id = '$trano' AND approve=100");
            if ($cek) {
                unset($t[$k]);
                continue;
            }
            $items['prj_kode'] = $k;
//           $items['next'] = $this->getRequest()->getParam('next');
            $items['uid_next'] = $uid_next;
            $items['workflow_item_id'] = $workflowItem;
            $items['workflow_item_type_id'] = $workflowItemtype;
            $items['workflow_id'] = $workflowId;
            if (count($projek[$k]) == 0) {
                $projek[$k]['prj_kode'] = $k;
                $projek[$k]['uid_next'] = $uid_next;
                $projek[$k]['workflow_item_id'] = $workflowItem;
                $projek[$k]['workflow_item_type_id'] = $workflowItemtype;
                $projek[$k]['workflow_id'] = $workflowId;
            } else {
                $items['uid_next'] = $projek[$k]['uid_next'];
                $items['workflow_item_id'] = $projek[$k]['workflow_item_id'];
                $items['workflow_item_type_id'] = $projek[$k]['workflow_item_type_id'];
                $items['workflow_id'] = $projek[$k]['workflow_id'];
            }
            $params = array(
                "workflowType" => "TSHEET",
                "paramArray" => '',
                "approve" => $this->const['DOCUMENT_SUBMIT'],
                "items" => $items,
                "prjKode" => $items['prj_kode'],
                "generic" => true,
                "revisi" => false,
                "returnException" => true
            );
            try {
                if ($trano != '') {
                    $params['lastTrano'] = $trano;
                }
                $trano = $this->workflow->setWorkflowTransNew($params);
            } catch (Exception $e) {
                $gagal[$k] = $k;
                $workError['msg'][] = "<b>Project: $k</b>, " . $e->getMessage();
                continue;
            }


//           $result = $this->workflow->setWorkflowTrans($trano,'TSHEET', '', $this->const['DOCUMENT_SUBMIT'],$items,'',true);

            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//           if (is_numeric($result))
//           {
//                if ($result == 300)
//                    $msg = "You are not assigned to this workflow Or Document Receiver Role is not in this Project! Please contact IT Support!";
//                else
//                    $msg = $this->error->getErrorMsg($result);
//                $workError$gagal[$k] = $k;
//               ['msg'][] = "<b>Project: $k</b>, " . $msg;
//                continue;
////                $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
////                return false;
//           }
//           elseif (is_array($result) && count($result) > 0)
//           {
//
//               $hasil = Zend_Json::encode($result);
//               $this->getResponse()->setBody("{success: true, user:$hasil, prjKode: '$k'}");
//               return false;
//           }
            $uid_next = '';
            $workflowId = '';
            $workflowItem = '';
            $workflowItemtype = '';
            foreach ($v as $key2 => $val2) {
                $existID = $v[$key2]['id'];
                if ($existID != '') {
                    $cek = $this->timesheet->fetchRow("id = $existID");
                    if ($cek) {
                        $v[$key2]['trano'] = $trano;
                        $v[$key2]['date'] = date('Y-m-d H:i:s');
                        $v[$key2]['is_submit'] = '1';
                        unset($v[$key2]['id']);
                        unset($v[$key2]['recid']);
                        $this->timesheet->update($v[$key2], "id = $existID");
//                       $this->updateLastTrano($where,$last);
                    }
                } else {
                    $prj_kode = $v[$key2]['prj_kode'];
                    if ($prj_kode == $k) {
                        if ($gagal[$prj_kode] == '') {
                            $v[$key2]['trano'] = $trano;
                            $v[$key2]['is_submit'] = 1;
                        } else {
                            $v[$key2]['is_submit'] = 0;
                            unset($v[$key2]['trano']);
                        }
                        unset($v[$key2]['id']);
                        unset($v[$key2]['recid']);
                        $v[$key2]['date'] = date('Y-m-d H:i:s');
                        $this->timesheet->insert($v[$key2]);
                          $activityHead['projectmanagement_timesheet'][0]=$v[$key2];
//                       $this->updateLastTrano($where,$last);
                    }
                }
            };
        }
               
                
         $activityLog = array(
            "menu_name" => "Create Timesheet",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $items['prj_kode'],
            "sit_kode" => $val['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        
        if (count($workError) == 0)
            $this->getResponse()->setBody("{success: true, number : '$trano'}");
        else {
            $jsonError = Zend_Json::encode($workError);
            $jumProjek = count($t);

            if (count($workError) == $jumProjek) {
                $this->getResponse()->setBody("{success: true, allfailed: true, error: '$jsonError'}");
            } else {
         
                $this->getResponse()->setBody("{success: true, number : '$trano', error: '$jsonError'}");
            }
        }
    }

    public function detailtimesheetAction() {
        $trano = $this->getRequest()->getParam("trano");
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $uid_next = $this->getRequest()->getParam("uid_next");
        $uid = $this->getRequest()->getParam("uid");
        $behalfof = $this->getRequest()->getParam("behalfof");
        $approval = $this->getRequest()->getParam("approval");

        $where = array();
        if ($trano)
            $where[] = "trano = '$prjKode'";
        if ($prjKode)
            $where[] = "prj_kode = '$prjKode'";

        $search = implode(" AND ", $where);
        $ldapdir = new Default_Models_Ldap();

        if (!$approval) {
            $data = $this->timesheet->fetchAll($search);
            $data = $data->toArray();
        } else {
            $sql = "SELECT * FROM ( SELECT * FROM workflow_trans
                WHERE
                    uid_next = '$uid_next'
                AND prj_kode = '$prjKode'
                AND item_type = 'TSHEET'
                AND approve NOT IN (300,400)
                AND final = 0
                ORDER BY date DESC ) a
                GROUP BY a.item_id ORDER BY a.item_id ASC
            ";
            $fetch = $this->db->query($sql);
            $hasil = $fetch->fetchAll();

            $data = array();
            if ($hasil) {
                foreach ($hasil as $k => $v) {
                    $trano = $v['item_id'];
                    $search = "trano = '$trano' AND uid = '$uid' AND prj_kode = '$prjKode' AND uid = '$uid'";
                    if ($behalfof)
                        $search .= " AND behalfof = '$behalfof'";
                    else
                        $search .= " AND (behalfof = '' OR behalfof is null)";

                    $fetch = $this->timesheet->fetchAll($search);
                    if ($fetch) {
                        $fetch = $fetch->toArray();
                        foreach ($fetch as $k2 => $v2) {
//                            $account = $ldapdir->getAccount($v2['behalfof']);
//                            $v2['behalfof'] = $account['displayname'][0];
                            $v2['behalfof'] = QDC_User_Ldap::factory(array("uid" => $v2['behalfof']))->getName();

                            $data[] = $v2;
                        }
                    }
                }
//                $account = $ldapdir->getAccount($uid);
//                $this->view->name = $account['displayname'][0];
                $this->view->name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
            }
        }
        if ($data) {
            $site = new Default_Models_MasterSite();
            foreach ($data as $key => &$val) {
                $data[$key]['sit_nama'] = '';
                if ($val['sit_kode'] != '') {
                    $sitKode = $val['sit_kode'];
                    $sites = $site->fetchRow("prj_kode = '$prjKode' AND sit_kode = '$sitKode'");
                    if ($sites) {
                        $data[$key]['sit_nama'] = $sites['sit_nama'];
                    }
                }
                $val['start'] = date('d M Y H:i:s', strtotime($val['start']));
                $val['end'] = date('d M Y H:i:s', strtotime($val['end']));
            }
            $this->view->result = $data;
        }
    }

    public function edittimesheetAction() {
        $trano = $this->getRequest()->getParam("trano");
        $prjKode = $this->getRequest()->getParam("prj_kode");

        if ($prjKode != '')
            $where = " AND prj_kode = '$prjKode'";
        $ts = $this->timesheet->fetchAll("trano = '$trano' $where");

        if ($ts) {
            $ts = $ts->toArray();
            $result = array();
            $lastKey = 1;
            foreach ($ts as $key => &$val) {
                $title = $val['prj_kode'];
                if ($val['sit_kode'] != '')
                    $title .= '-' . $val['sit_kode'];

                if ($val['allday'] == "1")
                    $ad = true;
                $result[] = array(
                    "id" => $lastKey,
                    "title" => $title,
                    "start" => $val['start'],
                    "end" => $val['end'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "cid" => 1,
                    "notes" => $val['notes'],
                    "ad" => $ad,
                    "is_prev" => false
                );
                $lastKey++;
            }
//            $start = $this->timesheet->fetchRow("trano = '$trano' $where",array("start ASC"),1,0);
            $start = $this->timesheet->fetchRow("trano = '$trano' $where", array("start ASC"));
            $start = $start['start'];
            $month = (integer) date("m", strtotime($start));

            $result2 = $this->timesheet->getLastTimesheet($this->session->userName, $month);
            foreach ($result2 as $key => &$val) {
                if ($val['trano'] == $trano) {
                    if ($prjKode != '') {
                        if ($val['prj_kode'] == $prjKode)
                            continue;
                    } else
                        continue;
                }
                $cid = 2;
                $title = $val['prj_kode'];
                if ($val['sit_kode'] != '')
                    $title .= '-' . $val['sit_kode'];

                if ($val['allday'] == "1")
                    $ad = true;

                $app = true;
                $result[] = array(
                    "id" => $lastKey,
                    "recid" => $val['id'],
                    "title" => $title,
                    "start" => $val['start'],
                    "end" => $val['end'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "cid" => $cid,
                    "notes" => $val['notes'],
                    "ad" => $ad,
                    "is_prev" => $app
                );
                $lastKey++;
            }
        } else
            $ts = array();

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($result);
        $this->view->trano = $trano;
        $this->view->prjKode = $prjKode;
        $this->view->json = $jsonData;
        $this->view->start = $start;
    }

    public function listAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $trano = $this->getRequest()->getParam("trano");
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $all = $this->getRequest()->getParam("all");
        $draft = $this->getRequest()->getParam("draft");
        $final = $this->getRequest()->getParam("final");

        if ($draft == 'false')
            $draft = false;
        elseif ($draft == '')
            $draft = true;
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if ($trano != '')
            $where = "trano LIKE '%$trano%'";
        else if ($prjKode != '')
            $where = "prj_kode LIKE '%$prjKode%'";

        if (!$draft) {
            if ($where)
                $where .= " AND is_submit=1";
            else
                $where = "is_submit=1";
        }
        else {
            if ($where)
                $where .= " AND is_submit=0";
            else
                $where = "is_submit=0";
        }

        if (!$all) {
            if ($where)
                $where .= " AND uid = '" . $this->session->userName . "'";
            else
                $where = "uid = '" . $this->session->userName . "'";
        }

        if ($final) {
            if ($where)
                $where .= " AND is_final_approve = 1";
            else
                $where = "is_final_approve = 1";
        }
//        $return['posts'] = $this->timesheet->fetchAll($where,array($sort . " " . $dir),$limit,$offset)->toArray();
        $dbSelect = $this->timesheet->select()->from(
                        $this->timesheet
                )
                ->where($where)
                ->group(array('trano', 'prj_kode'))
                ->order(array($sort . ' ' . $dir))
                ->limit($limit, $offset);

        $return['posts'] = $this->db->fetchAll($dbSelect);
        $dbSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $dbSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $return['count'] = count($this->db->fetchAll($dbSelect));
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatetimesheetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $json = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($json);
        $jsonEtc = Zend_Json::decode($etc);
        $trano = $jsonEtc[0]['trano'];
        $prjKode = $jsonEtc[0]['prj_kode'];


        //Group by per project dulu...
        $arrayUpdate = array();
        foreach ($jsonData as $k => $v) {
            $prj_kode = $v['prj_kode'];
            $arrayUpdate[$prj_kode] = $v;
        }

        foreach ($arrayUpdate as $k => $v) {
            $items = $v;
//           $items['next'] = $this->getRequest()->getParam('next');
            $items['uid_next'] = $this->getRequest()->getParam('uid_next');
            $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
            $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
            $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

            $result = $this->workflow->setWorkflowTrans($trano, 'TSHEET', '', $this->const['DOCUMENT_RESUBMIT'], $items, '', true);
        }
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        } elseif (is_array($result) && count($result) > 0) {

            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }



        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        $before['timesheet'] = $this->timesheet->fetchAll("trano = '$trano' AND prj_kode = '$prjKode'")->toArray();
        $before = Zend_Json::encode($before);

        $this->timesheet->delete("trano = '$trano' AND prj_kode = '$prjKode'");
        foreach ($jsonData as $key => $val) {
            $jsonData[$key]['trano'] = $trano;
            $jsonData[$key]['date'] = date('Y-m-d H:i:s');
            unset($jsonData[$key]['recid']);
            $this->timesheet->insert($jsonData[$key]);
        }

        $after['timesheet'] = $jsonData;
        $after = Zend_Json::encode($after);

        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "EDIT",
            "data_before" => $before,
            "data_after" => $after,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);
        $this->getResponse()->setBody("{success: true}");
    }

    public function savetimesheetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $json = $this->getRequest()->getParam("posts");
        $jsonData = Zend_Json::decode($json);

        foreach ($jsonData as $k => $v) {
            $prjKode = $v['prj_kode'];
            $t[$prjKode][] = $v;
        }
        foreach ($t as $k => $v) {
            foreach ($v as $key2 => $val2) {
//                $v[$key2]['trano'] = $trano;
                $existID = $v[$key2]['recid'];
                if ($existID != '') {
                    $cek = $this->timesheet->fetchRow("id = $existID");
                    if ($cek) {
                        $v[$key2]['date_update'] = date('Y-m-d H:i:s');
                        unset($v[$key2]['recid']);
                        $this->timesheet->update($v[$key2], "id = $existID");
                    }
                } else {
                    unset($v[$key2]['recid']);
                    $v[$key2]['date'] = date('Y-m-d H:i:s');
                    $v[$key2]['is_submit'] = 0;
                    $this->timesheet->insert($v[$key2]);
                }
            }
        }
        $this->getResponse()->setBody("{success: true}");
    }

    public function timesheetdraftAction() {
        
    }

    public function timesheetsubmittedAction() {
        
    }

    public function getdrafttimesheetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->session->userName;


        $searchFields = $this->getRequest()->getParam("fields");
        $searchQuery = $this->getRequest()->getParam("query");

        if ($searchFields != '' && $searchQuery != '') {
            $searchFields = Zend_Json::decode($searchFields);
            $where = $this->util->buildSearchQuery($searchFields, $searchQuery);
        }

        if ($where != '')
            $where = " AND ($where)";
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $fetch = $this->timesheet->fetchAll("uid = '$uid' AND is_submit = 0 $where", array($sort . " " . $dir), $limit, $offset);
        if ($fetch) {
            $ldap = new Default_Models_Ldap();
            $result['posts'] = $fetch->toArray();
            foreach ($result['posts'] as $key => $val) {
                if ($val['behalfof'] != '') {
//                    $uname = $ldap->getAccount($val['behalfof']);
//                    $result['posts'][$key]['behalfof'] = $uname['displayname'][0];
                    $result['posts'][$key]['behalfof'] = QDC_User_Ldap::factory(array("uid" => $val['behalfof']))->getName();
                }
            }
            $result['count'] = $fetch->count();
        }

        $json = Zend_Json::encode($result);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getsubmittimesheetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->session->userName;


        $searchFields = $this->getRequest()->getParam("fields");
        $searchQuery = $this->getRequest()->getParam("query");

        if ($searchFields != '' && $searchQuery != '') {
            $searchFields = Zend_Json::decode($searchFields);
            $where = $this->util->buildSearchQuery($searchFields, $searchQuery);
        }

        if ($where != '')
            $where = " AND ($where)";
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $fetch = $this->timesheet->fetchAll("uid = '$uid' AND is_submit = 1 $where", array($sort . " " . $dir), $limit, $offset);
        if ($fetch) {
            $ldap = new Default_Models_Ldap();
            $result['posts'] = $fetch->toArray();
            foreach ($result['posts'] as $key => $val) {
                $trano = $val['trano'];
                $prjKode = $val['prj_kode'];
                $prj = new Default_Models_MasterProject();
                $prjNama = $prj->fetchRow("Prj_Kode = '$prjKode'");
                if ($prjNama)
                    $result['posts'][$key]['prj_nama'] = $prjNama['Prj_Nama'];
                $sitKode = $val['sit_kode'];
                if ($sitKode) {
                    $sit = new Default_Models_MasterSite();
                    $sitNama = $sit->fetchRow("prj_kode = '$prjKode' AND sit_kode = '$sitKode'");
                    if ($sitNama)
                        $result['posts'][$key]['sit_nama'] = $sitNama['sit_nama'];
                } else
                    $result['posts'][$key]['sit_nama'] = '';
//                $stat = $this->workflow->getDocumentLastStatus($trano,$prjKode);
                $stat = $this->workflow->getDocumentLastStatusAllGeneric($trano, $prjKode);
                if ($stat) {
                    $uidApp = $stat['uid'];
//                    $nameApp = $ldap->getAccount($uidApp);
//                    $result['posts'][$key]['status'] = $this->workflow->getApprovalText($stat['approve']) . " By " . $nameApp['displayname'][0];
                    $result['posts'][$key]['status'] = $this->workflow->getApprovalText($stat['approve']) . " By " . QDC_User_Ldap::factory(array("uid" => $uidApp))->getName();
                }
                if ($val['behalfof'] != '') {
//                    $uname = $ldap->getAccount($val['behalfof']);
//                    $result['posts'][$key]['behalfof'] = $uname['displayname'][0];
                    $result['posts'][$key]['behalfof'] = QDC_User_Ldap::factory(array("uid" => $val['behalfof']))->getName();
                }
                $docs = $this->workflowTrans->fetchRow("item_id = '$trano'");
                if ($docs) {
                    $result['posts'][$key]['workflow_item_id'] = $docs['workflow_item_id'];
                }
            }
            $result['count'] = $this->timesheet->fetchAll("uid = '$uid' AND is_submit = 1 $where")->count();
        }

        $json = Zend_Json::encode($result);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function edittimesheetdraftAction() {
        $uid = $this->session->userName;

        $result = array();
        $start = $this->timesheet->fetchRow("is_submit=0 AND uid = '$uid'", array("date DESC"), 1, 0);
        $start = $start['start'];
        $month = (integer) date("m", strtotime($start));

        $result2 = $this->timesheet->fetchAll("is_submit=0 AND (MONTH(start) = $month OR MONTH(end) = $month) AND uid = '$uid'");
        $lastKey = 0;
        foreach ($result2 as $key => $val) {
            $cid = 1;
            $title = $val['prj_kode'];
            if ($val['sit_kode'] != '')
                $title .= '-' . $val['sit_kode'];

            if ($val['allday'] == "1")
                $ad = true;

            $app = false;
            $result[] = array(
                "id" => $lastKey,
                "recid" => $val['id'],
                "title" => $title,
                "start" => $val['start'],
                "end" => $val['end'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "cid" => $cid,
                "notes" => $val['notes'],
                "ad" => $ad,
                "is_prev" => $app
            );
            $lastKey++;
        }
        //get timesheet already submitted
        $result2 = $this->timesheet->fetchAll("is_submit=1 AND (MONTH(start) = $month OR MONTH(end) = $month) AND uid = '$uid'");
        foreach ($result2 as $key => $val) {
            $cid = 2;
            $title = $val['prj_kode'];
            if ($val['sit_kode'] != '')
                $title .= '-' . $val['sit_kode'];

            if ($val['allday'] == "1")
                $ad = true;

            $app = true;
            $result[] = array(
                "id" => $lastKey,
                "recid" => $val['id'],
                "title" => $title,
                "start" => $val['start'],
                "end" => $val['end'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "cid" => $cid,
                "notes" => $val['notes'],
                "ad" => $ad,
                "is_prev" => $app
            );
            $lastKey++;
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($result);

        $this->view->json = $jsonData;
        $this->view->start = $start;
    }

    private function updateLastTrano($where, $last) {
        $counter = new Default_Models_MasterCounter();
        $cekTrano = $counter->fetchRow($where . " AND urut=$last");
        if (!$cekTrano) {
            $counter->update(array("urut" => $last), $where);
        }
    }

    public function showworkflowAction() {
        
    }

    public function alltimesheetAction() {
        $periodeHR = new HumanResource_Model_SetPeriode();
        $fetch = $periodeHR->fetchRow("periode_act = 1");
        if ($fetch) {
            $this->view->periodeText = $fetch['tahun'] . " - " . $fetch['periode'];
            $this->view->periodeId = $fetch['id'];
        } else
            $this->view->periodeAlert = 'Timesheet Periode has not inputed yet! Go to Menu Human Resource -> Transaction -> TImesheet Periode,  for set new periode.';
        $this->view->from = $this->getRequest()->getParam("from");
    }

    public function detailtimesheet2Action() {
        $uid = $this->getRequest()->getParam("uid");
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $tgl1 = $this->getRequest()->getParam("tgl1");
        $tgl2 = $this->getRequest()->getParam("tgl2");
        $type = $this->getRequest()->getParam("type");

        if ($uid)
            $where = " uid = '$uid'";
        if ($prjKode) {
            if ($where)
                $where .= " AND prj_kode = '$prjKode'";
            else
                $where = "prj_kode = '$prjKode'";
        }
        if ($tgl1 && $tgl2) {
            $tgl1 = date("Y-m-d H:i:s", strtotime($tgl1));
            $tgl2 = date("Y-m-d H:i:s", strtotime($tgl2));
            if ($where)
                $where .= " AND (start BETWEEN '$tgl1' AND '$tgl2') AND (end BETWEEN '$tgl1' AND '$tgl2')";
            else
                $where = "(start BETWEEN '$tgl1' AND '$tgl2') AND (end BETWEEN '$tgl1' AND '$tgl2')";
        }
        $ret = array();

        if ($type == 'final')
            $where .= " AND is_final_approve = 1";
        else
            $where .= " AND is_submit = 1";
        $data = $this->timesheet->fetchAll($where, array("uid ASC", "prj_kode ASC"));
        if ($data) {
            $data = $data->toArray();
            $ldapdir = new Default_Models_Ldap();
            foreach ($data as $key => &$val) {
                $uid = $val['uid'];
                if ($ret[$uid]['name'] == '') {
//                    $account = $ldapdir->getAccount($uid);
//                    $ret[$uid]['name'] = $account['displayname'][0];
                    $ret[$uid]['name'] = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                }
                $prjKode = $val['prj_kode'];
                if ($val['behalfof'] != '')
                    $uid = $val['behalfof'];
                $ret[$uid]['project'][$prjKode] += intval($val['hour']);
            }

            $this->view->result = $ret;
        }
    }

    public function detailtimesheet3Action() {
        $uid = $this->getRequest()->getParam("uid");
        $prjKode = $this->getRequest()->getParam("prj_kode");
//        $tgl1 = $this->getRequest()->getParam("tgl1"));
//        $tgl2 = $this->getRequest()->getParam("tgl2"));
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $periode = $this->getRequest()->getParam("periode");
        $export = ($this->_getParam("export") != '') ? true : false;

        if ($uid) {
            $where[] = "(uid = '$uid' OR behalfof = '$uid')";
        }
        if ($prjKode) {
            $where[] = "prj_kode = '$prjKode'";
        }
        if ($periode) {
            $periodeHR = new HumanResource_Model_SetPeriode();
            $fetch = $periodeHR->fetchRow("id = $periode");
            $totalHour = $fetch['jumlah_jam_bulan'];
            $timestamp = mktime(0, 0, 0, intval($fetch['periode']), 1, floatval(date("Y")));
            $month = date("F", $timestamp);
            $year = $fetch['tahun'] . " - " . $month;
            $tglStart = $fetch['tgl_aw'];
            $tglEnd = $fetch['tglak'];
        }
//        $ret = array();
//
        if ($type == 'final')
            $where[] = "is_final_approve = 1";
        else
            $where[] = "is_submit = 1";

        if ($where) {
            $where = " AND " . implode(" AND ", $where);
        }
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS `my_timesheet` ;
            CREATE TEMPORARY TABLE `my_timesheet`
            SELECT
                *
            FROM
                projectmanagement_timesheet
            WHERE
                (
                    (`start` BETWEEN '$tglStart' AND '$tglEnd')
                    OR
                    (`end` BETWEEN '$tglStart' AND '$tglEnd')
                )
                $where;

            ALTER TABLE `my_timesheet`
            ADD INDEX `idx1` (`start` ASC),
            ADD INDEX `idx2` (`end` ASC);
            ";
        $this->db->query($sql);

        $sql2 = "
            DROP TEMPORARY TABLE IF EXISTS `hour` ;
            CREATE TEMPORARY TABLE `hour`
            SELECT
                uid,sum(hour) as total
            FROM
                projectmanagement_timesheet
            WHERE
                (
                    (`start` BETWEEN '$tglStart' AND '$tglEnd')
                    OR
                    (`end` BETWEEN '$tglStart' AND '$tglEnd')
                )
                $where group by uid;

            ALTER TABLE `my_timesheet`
            ADD INDEX `idx3` (`start` ASC),
            ADD INDEX `idx4` (`end` ASC);
            ";
        $this->db->query($sql2);

        $sql = "SELECT SUM(hour) as sum_hour FROM `my_timesheet`;";
        $sumHour = $this->db->fetchOne($sql);
        if ($sumHour) {
            $sql = "
            SELECT
                sum(hour) as hour,
                (select total from hour where uid = z.uid) as totalhour1,
                uid,
                (SELECT Prj_Kode FROM master_project WHERE prj_kode = z.prj_kode) AS prj_kode,
                (SELECT Prj_Nama FROM master_project WHERE prj_kode = z.prj_kode) AS prj_nama,
                (
                    SELECT npk FROM master_login WHERE uid = z.uid
                ) as npk,
                0 as persen,
                0 as persen2
            FROM `my_timesheet` z
            GROUP BY
                uid,prj_kode;
            ";

            $fetch = $this->db->query($sql);
            $hasil = $fetch->fetchAll();
            if ($hasil) {
                foreach ($hasil as $k => $v) {
                    $uid = $v['uid'];
                    $hasil[$k]['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();

                    $hasil[$k]['persen'] = ($v['hour'] / $v['totalhour1']) * 100;
                    $hasil[$k]['persen2'] = ($v['hour'] / 176) * 100;
                }
                $this->view->result = $hasil;
                $this->view->tgl1 = date("d M Y", strtotime($tglStart));
                $this->view->tgl2 = date("d M Y", strtotime($tglEnd));
            }
        }


        if (!$export) {
            $this->view->result = $hasil;
            $this->view->tgl1 = date("d M Y", strtotime($tglStart));
            $this->view->tgl2 = date("d M Y", strtotime($tglEnd));
            $this->view->totalHour = $totalHour;
            $this->view->periode = $year;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            foreach ($hasil as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "NPK" => $v['npk'],
                    "Name" => $v['name'],
                    "Project" => $v['prj_kode'] . '-' . $v['prj_nama'],
                    "Hour" => $v['hour'],
                    "Total Hour Person" => $v['totalhour1'],
                    "Total Hour HRD" => $totalHour,
                    "Percentage Person (%)" => $v['persen'],
                    "Percentage HRD (%)" => $v['persen2']
                );
                $no++;
            }


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Timesheet Report " . $year
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function apptimesheetallAction() {
        $uidSearch = $this->getRequest()->getParam("uid");

        $ldapdir = new Default_Models_Ldap();
//        $account = $ldapdir->getAccount($uidSearch);
//        $this->view->name = $account['displayname'][0];
        $this->view->name = QDC_User_Ldap::factory(array("uid" => $uidSearch))->getName();

        $uidNext = $this->session->userName;
        $this->view->uidNext = $uidNext;
        $this->view->userID = $this->session->idUser;
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS TIMESHEET;
            CREATE TEMPORARY TABLE TIMESHEET
            SELECT * FROM ( SELECT * FROM workflow_trans
                WHERE
                    uid_next = '$uidNext'
                AND item_type = 'TSHEET'
                AND approve NOT IN (300,400)
                -- AND final = 0
                ORDER BY date DESC ) a
                GROUP BY a.item_id,a.prj_kode ORDER BY a.item_id ASC;

            DROP TEMPORARY TABLE IF EXISTS TIMESHEET2;
            CREATE TEMPORARY TABLE TIMESHEET2
            SELECT * FROM ( SELECT * FROM workflow_trans
                WHERE
                    uid = '$uidSearch'
                AND item_type = 'TSHEET'
                AND approve IN (100,150)
                -- AND final = 0
                ORDER BY date DESC ) a
                GROUP BY a.item_id,a.prj_kode ORDER BY a.item_id ASC;

            DROP TEMPORARY TABLE IF EXISTS TIMESHEET3;
            CREATE TEMPORARY TABLE TIMESHEET3
            SELECT a.* FROM
                TIMESHEET a LEFT JOIN TIMESHEET2 b
            ON
                a.item_id = b.item_id
                AND a.workflow_item_id = b.workflow_item_id
                AND a.prj_kode = b.prj_kode
            WHERE
                b.item_id IS NOT NULL;

            DROP TEMPORARY TABLE IF EXISTS TIMESHEET4;
            CREATE TEMPORARY TABLE TIMESHEET4
            SELECT * FROM ( SELECT * FROM workflow_trans
                WHERE
                item_type = 'TSHEET'
                AND final = 0
                ORDER BY date DESC ) a
                GROUP BY a.item_id,a.prj_kode ORDER BY a.item_id ASC;

            DROP TEMPORARY TABLE IF EXISTS TIMESHEET5;
            CREATE TEMPORARY TABLE TIMESHEET5
            SELECT a.* FROM
                TIMESHEET3 a LEFT JOIN TIMESHEET4 b
            ON
                a.item_id = b.item_id
                AND a.workflow_item_id = b.workflow_item_id
                AND a.prj_kode = b.prj_kode
            WHERE
                    b.item_id IS NOT NULL
                AND
                    a.date >= b.date
                AND
                    b.approve NOT IN (300,400);
        ";
        $fetch = $this->db->query($sql);

        $sql = "SELECT * FROM TIMESHEET5;";
        $fetch = $this->db->prepare($sql);
        $fetch->execute();
        $hasil = $fetch->fetchAll();

        if ($hasil) {
            $ret = array();
            foreach ($hasil as $k => $v) {
                $trano = $v['item_id'];
                $prj_kode = $v['prj_kode'];
                $tsheet = $this->timesheet->fetchAll("trano = '$trano' AND uid = '$uidSearch' AND prj_kode = '$prj_kode'");
                if ($tsheet) {
                    $tsheet = $tsheet->toArray();
                    foreach ($tsheet as $key => &$val) {
                        $uid = $uidSearch;
                        $prjKode = $val['prj_kode'];
                        if ($val['behalfof'] != '') {
                            $uid = $val['behalfof'];
//                            $account = $ldapdir->getAccount($uid);
//                            $behalf = $account['displayname'][0];
                            $behalf = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                            $ret[$uid][$prjKode]['behalfof'] = $uidSearch;
                            $ret[$uid][$prjKode]['ket'] = " (On behalf of <b>$behalf<b>)";
                        }
                        $ret[$uid][$prjKode]['hour'] += intval($val['hour']);
                        if (count($ret[$uid][$prjKode]['trano']) > 0) {
                            if (!in_array($trano, $ret[$uid][$prjKode]['trano']))
                                $ret[$uid][$prjKode]['trano'][] = $trano;
                        } else
                            $ret[$uid][$prjKode]['trano'][] = $trano;
                        if (count($ret[$uid][$prjKode]['trans_id']) > 0) {
                            if (!in_array($trano, $ret[$uid][$prjKode]['trans_id']))
                                $ret[$uid][$prjKode]['trans_id'][] = $v['workflow_trans_id'];
                        } else
                            $ret[$uid][$prjKode]['trans_id'][] = $v['workflow_trans_id'];
                    }
                }
                $this->view->result = $ret;
                $this->view->prjKode = $prjKode;
            }
        }
    }

    public function alldetailtimesheetAction() {
        $this->view->from = $this->getRequest()->getParam("from");
    }

    public function detailtimesheet4Action() {
        $uid = rawurldecode($this->getRequest()->getParam("uid"));
        $prjKode = rawurldecode($this->getRequest()->getParam("prj_kode"));
        $tgl1 = rawurldecode($this->getRequest()->getParam("tgl1"));
        $tgl2 = rawurldecode($this->getRequest()->getParam("tgl2"));
        $type = rawurldecode($this->getRequest()->getParam("type"));
        $from = rawurldecode($this->getRequest()->getParam("from"));

        $current = $this->getRequest()->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->getRequest()->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->getRequest()->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * 50;

        if ($uid)
            $where = " uid = '$uid'";
        if ($prjKode) {
            if ($where)
                $where .= " AND prj_kode = '$prjKode'";
            else
                $where = "prj_kode = '$prjKode'";
        }
        if ($tgl1 && $tgl2) {
            $tgl1 = date("Y-m-d H:i:s", strtotime($tgl1));
            $tgl2 = date("Y-m-d H:i:s", strtotime($tgl2));
            if ($where)
                $where .= " AND (start BETWEEN '$tgl1' AND '$tgl2') AND (end BETWEEN '$tgl1' AND '$tgl2')";
            else
                $where = "(start BETWEEN '$tgl1' AND '$tgl2') AND (end BETWEEN '$tgl1' AND '$tgl2')";
        }
        $ret = array();

        if ($type == 'final')
            $where .= " AND is_final_approve = 1";
        else
            $where .= " AND is_submit = 1";

        $fetch = $this->timesheet->fetchAll($where, array("uid ASC", "start DESC"), 50, $offset);
        $fetch2 = $this->timesheet->fetchAll($where)->count();
        $ldapdir = new Default_Models_Ldap();
        $prj = new Default_Models_MasterProject();
        $sit = new Default_Models_MasterSite();
        $users = new Default_Models_MasterUser();

        if ($fetch) {
            $hasil = $fetch->toArray();
            $person = array();
            $npk = array();
            $prjs = array();
            $sits = array();
            foreach ($hasil as $k => $v) {
                $uid = $v['uid'];
                if ($v['behalfof'] != '')
                    $uid = $v['behalfof'];

                $user = $users->getUserInfo($uid);
                if ($npk[$uid] == '')
                    $npk[$uid] = $user['npk'];
                $hasil[$k]['npk'] = $user['npk'];

                if ($person[$uid] == '') {
//                    $account = $ldapdir->getAccount($uid);
//                    $person[$uid] = $account['displayname'][0];
                    $person[$uid] = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                }
                $hasil[$k]['name'] = $person[$uid];
                $hasil[$k]['start'] = date("d-m-Y H:i:s", strtotime($hasil[$k]['start']));
                $hasil[$k]['end'] = date("d-m-Y H:i:s", strtotime($hasil[$k]['end']));

                $prjKode = $v['prj_kode'];
                $sitKode = $v['sit_kode'];

                if ($prjs[$prjKode] == '') {
                    $prjNama = $prj->fetchRow("Prj_Kode = '$prjKode'");
                    if ($prjNama)
                        $prjs[$prjKode] = $prjNama['Prj_Nama'];
                }
                $hasil[$k]['prj_nama'] = $prjs[$prjKode];
                if ($sits[$prjKode . "-" . $sitKode] == '') {
                    if ($sitKode) {
                        $sitNama = $sit->fetchRow("prj_kode = '$prjKode' AND sit_kode = '$sitKode'");
                        if ($sitNama)
                            $sits[$prjKode . "-" . $sitKode] = $sitNama['sit_nama'];
                    }
                }
                $hasil[$k]['sit_nama'] = $sits[$prjKode . "-" . $sitKode];
            }

            $this->view->offset = $offset;
            $this->view->result = $hasil;
            $this->view->limitPerPage = 50;
            $this->view->totalResult = $fetch2;
            $this->view->current = $current;
            $this->view->currentPage = $currentPage;
            $this->view->requested = $requested;
            $this->view->pageUrl = $this->view->url();
        }
    }

}

?>