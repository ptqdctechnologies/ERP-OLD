<?php

/**
 * Created by PhpStorm.
 * User: bherly
 * Date: Feb 10, 2011
 * Time: 3:24:24 PM
 * To change this template use File | Settings | File Templates.
 */
class Admin_DocumentController extends Zend_Controller_Action {

    private $const;
    private $model;
    private $workflow;
    private $workflowClass;
    private $session;
    private $quantity;
    private $db;
    private $workflowTrans;
    private $error;
    private $creTrans;
    private $log;
    private $file;
    private $material;
    private $pulsa;

    public function init() {
        $this->const = Zend_Registry::get('constant');
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->model = Zend_Controller_Action_HelperBroker::getStaticHelper('model');
        $this->error = $this->_helper->getHelper('error');
        $this->token = $this->_helper->getHelper('token');
        $this->session = new Zend_Session_Namespace('login');
        $this->work = new Default_Models_MasterWork();
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Models_Workflow();
        $this->creTrans = new Admin_Model_CredentialTrans();
        $this->log = new Admin_Models_Logtransaction();
        $this->file = new Default_Models_Files();
        $this->material = new Default_Models_MasterBarang();
        $this->pulsa = new Admin_Models_KodePulsa();
    }

    public function deletetransAction() {
        $trano = $this->getRequest()->getParam('trano');

        $hasil = $this->workflowTrans->fetchRow("item_id = '$trano' AND item_type is not null AND item_type != ''");

        if ($hasil)
            $itemType = $hasil['item_type'];

        switch ($itemType) {
            case 'PO':
                $sql = "SELECT trano FROM procurement_rpid WHERE deleted=0 AND po_no = '$trano' GROUP BY trano;";
                $fetch = $this->db->query($sql);
                $hasil = $fetch->fetchAll();
                if ($hasil) {
                    if (count($hasil) > 0) {
                        foreach ($hasil as $key => $val) {
                            $result['This document has RPI'][] = $val['trano'];
                            $rpi = $val['trano'];
                            $sql = "SELECT trano FROM finance_payment_rpi WHERE doc_trano = '$rpi'";
                            $fetch = $this->db->query($sql);
                            $hasil2 = $fetch->fetchAll();

                            if ($hasil2) {
                                foreach ($hasil2 as $key2 => $val2) {
                                    $result['This document has RPI that have been paid'][] = $val['trano'];
                                }
                            }
                        }
                    }
                }

                break;
            case 'PR':
                $sql = "SELECT trano,po_no FROM procurement_rpid WHERE deleted=0 AND pr_no = '$trano' GROUP BY trano;";
                $fetch = $this->db->query($sql);
                $hasil = $fetch->fetchAll();
                if ($hasil) {
                    if (count($hasil) > 0) {
                        foreach ($hasil as $key => $val) {
                            $result['This document has PO'][] = $val['po_no'];
                            $result['This document has RPI'][] = $val['trano'];
                            $rpi = $val['trano'];
                            $sql = "SELECT trano,tgl FROM finance_payment_rpi WHERE doc_trano = '$rpi'";
                            $fetch = $this->db->query($sql);
                            $hasil2 = $fetch->fetchAll();

                            if ($hasil2) {
                                foreach ($hasil2 as $key2 => $val2) {
                                    $result['This document has RPI that have been paid'][] = $val2['trano'] . " " . date('d-m-Y H:i:s', strtotime($val2['tgl']));
                                }
                            }
                        }
                    }
                }
                break;
            case 'ARF':
                $sql = "SELECT trano FROM procurement_asfdd WHERE arf_no = '$trano' GROUP BY trano;";
                $fetch = $this->db->query($sql);
                $hasil = $fetch->fetchAll();
                if ($hasil) {
                    if (count($hasil) > 0) {
                        foreach ($hasil as $key => $val) {
                            $result['This document has ASF'][] = $val['trano'];
                            $arf = $val['arf_no'];
                            $sql = "SELECT trano FROM finance_payment_arf WHERE doc_trano = '$trano'";
                            $fetch = $this->db->query($sql);
                            $hasil2 = $fetch->fetchAll();

                            if ($hasil2) {
                                foreach ($hasil2 as $key2 => $val2) {
                                    $result['This document has ARF that have been paid'][] = $val2['trano'];
                                }
                            }
                        }
                    }
                }
                break;
            case 'ASF':
                $sql = "SELECT trano FROM procurement_asfdd WHERE arf_no = '$trano' GROUP BY trano;";
                $fetch = $this->db->query($sql);
                $hasil = $fetch->fetchAll();
                if ($hasil) {
                    if (count($hasil) > 0) {
                        foreach ($hasil as $key => $val) {
                            $result['This document has ASF'][] = $val['trano'];
                            $arf = $val['arf_no'];
                            $sql = "SELECT trano FROM finance_payment_arf WHERE doc_trano = '$arf'";
                            $fetch = $this->db->query($sql);
                            $hasil2 = $fetch->fetchAll();

                            if ($hasil2) {
                                foreach ($hasil2 as $key2 => $val2) {
                                    $result['This document has ARF that have been paid'][] = $val2['trano'];
                                }
                            }
                        }
                    }
                }
                $sql = "SELECT trano FROM procurement_asfddcancel WHERE arf_no = '$trano' GROUP BY trano;";
                $fetch = $this->db->query($sql);
                $hasil = $fetch->fetchAll();
                if ($hasil) {
                    if (count($hasil) > 0) {
                        foreach ($hasil as $key => $val) {
                            $result['This document has ASF Cancel'][] = $val['trano'];
                        }
                    }
                }
                break;
            case 'RPI':
                $sql = "SELECT trano FROM finance_payment_rpi WHERE doc_trano = '$trano'";
                $fetch = $this->db->query($sql);
                $hasil2 = $fetch->fetchAll();

                if ($hasil2) {
                    foreach ($hasil2 as $key2 => $val2) {
                        $result['This document has RPI that have been paid'][] = $val2['trano'];
                    }
                }
                break;
        }
        $this->view->result = $result;
        $this->view->trano = $trano;

        if ($hasil) {
            if ($hasil['generic'] == 0)
                $this->view->userApp = $this->workflow->getAllApproval($trano);
            else
                $this->view->userApp = $this->workflow->getAllApprovalGeneric($trano);
        }
    }

    public function documentAction() {
        
    }
    
    public function document2Action() {
        
    }

    public function commitdeleteAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $trano = $request->getParam('trano');
        $uid = $request->getParam('uid');
        $comment = $request->getParam('comment');
        $myUid = $this->session->userName;

        $ret = $this->deleteTrans($trano, $uid, $myUid, $comment);

        if ($ret) {
            echo "{success: true}";
        } else
            echo "{success: false,msg: \"No such transaction!\"}";
    }

    private function deleteTrans($trano = '', $uid = '', $myUid = '', $comment = '') {
        $token = $this->token->getDocumentSignatureByUserID($uid);
        if (!$token) {
            $token = $this->token->getDocumentSignatureByUserID($myUid);
        }
        $tgl = $token['date'];
        $sign = $token['signature'];

        $fetch = $this->workflowTrans->fetchAll("item_id = '$trano'");

        if (!$fetch)
            return false;

        $hasil = $fetch->toArray();
        $prjKode = $hasil[0]['prj_kode'];
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($hasil);
        $myClass = $this->model->getModelClass($hasil[0]['item_type']);

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => $tgl,
            "prj_kode" => $prjKode,
            "uid" => $myUid,
            "uid_requestor" => $uid,
            "sign" => $sign,
            "reason" => $comment,
            "data" => $json,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->creTrans->insert($arrayInsert);
        $this->workflowTrans->delete("item_id = '$trano'");

        //updating approve status @transaction table
        if ($myClass)
            $this->workflowTrans->updateStatusApprove($myClass, $hasil[0]['item_type'], $trano, 300);


        return true;
    }

    public function listAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $trano = $request->getParam('trano');

        if ($trano != '')
            $query = " AND item_id LIKE '%$trano%'";
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        $sql = "SELECT SQL_CALC_FOUND_ROWS item_type,item_id,prj_kode FROM workflow_trans WHERE item_id is not null AND item_id != '' $query GROUP BY item_id ORDER BY item_id LIMIT $offset,$limit";
        $fetch = $this->db->query($sql);

        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function loglistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $trano = $request->getParam('trano');

        if ($trano != '')
            $query = " WHERE trano LIKE '%$trano%'";
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        $sql = "SELECT SQL_CALC_FOUND_ROWS id,trano,prj_kode,uid,tgl FROM log_transaction $query ORDER BY trano,tgl ASC LIMIT $offset,$limit";
        $fetch = $this->db->query($sql);

        $ldap = new Default_Models_Ldap();

        $return['posts'] = $fetch->fetchAll();

        foreach ($return['posts'] as $k => $v) {
//            $account = $ldap->getAccount($v['uid']);
//            $return['posts'][$k]['uid'] = $account['displayname'][0];
            $return['posts'][$k]['uid'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
            $return['posts'][$k]['tgl'] = date("d-m-Y H:i:s", strtotime($return['posts'][$k]['tgl']));
        }

        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function filemanagerAction() {
        
    }

    private function filterColumn($array = '') {
        $allowed = array(
            "trano",
            "qty",
            "harga",
            "total",
            "gtotal",
            "totalpo",
            "jumlah",
            "totalasf",
            "tgl",
            "prj_kode",
            "sit_kode",
            "workid",
            "kode_brg",
            "nama_brg",
            "val_kode",
            "rateidr",
            "cfs_kode",
            "cfs_nama",
            "afe_no",
            "arf_no",
            "po_no",
            "pr_no",
            "requestv",
            "invoice_no",
            "ppn",
            "cus_kode",
            "coa_kode",
            "sup_kode",
            "namabank",
            "rekbank",
            "reknamabank"
        );

        if ($array == '')
            return '';

        $return = array();

        foreach ($array as $k => $v) {
            if (in_array(strtolower($k), $allowed)) {
                $return[$k] = $v;
            }
        }
        ksort($return);
        return $return;
    }

    public function logtransactionAction() {
        $id = $this->getRequest()->getParam('id');

        $fetch = $this->log->fetchRow("id = $id");
        if ($fetch) {
            $fetch = $fetch->toArray();

            $fetch['data_after'] = str_replace('\"\"', '', $fetch['data_after']);
            $dataAfter = Zend_Json::decode($fetch['data_after']);

            $return1 = array();
            $return2 = array();

            foreach ($dataAfter as $k => $v) {
                $k1 = str_replace("-", " ", $k);
                $k1 = strtoupper($k1);
                if (is_array($v[0])) {
                    foreach ($v as $k2 => $v2) {
//                        $return1[$k1][$k2] = $this->filterColumn($dataAfter[$k][$k2]);
                        $return1[$k1][$k2] = $dataAfter[$k][$k2];
                    }
                } else {
//                    $return1[$k1][0] = $this->filterColumn($v);
                    $return1[$k1][0] = $v;
                }
            }

            if ($fetch['action'] == 'UPDATE REMARKS PO' || $fetch['action'] == 'UPDATE' || $fetch['action'] == 'CANCEL' || $fetch['action'] == 'REVISI') {
                $fetch['data_before'] = str_replace('\"\"', '', $fetch['data_before']);
                $dataBefore = Zend_Json::decode($fetch['data_before']);
                foreach ($dataBefore as $k => $v) {
                    $k1 = str_replace("-", " ", $k);
                    $k1 = strtoupper($k1);
                    if (is_array($v[0])) {
                        foreach ($v as $k2 => $v2) {
//                            $return2[$k1][$k2] = $this->filterColumn($dataBefore[$k][$k2]);
                            $return2[$k1][$k2] = $dataBefore[$k][$k2];
                        }
                    } else {
//                        $return2[$k1][0] = $this->filterColumn($v);
                        $return2[$k1][0] = $v;
                    }
                }
            }
//            $ldap = new Default_Models_Ldap();
//            $account = $ldap->getAccount($fetch['uid']);
//            $this->view->userEdit = $account['displayname'][0];
            $this->view->userEdit = QDC_User_Ldap::factory(array("uid" => $fetch['uid']))->getName();
            $this->view->after = $return1;
            $this->view->before = $return2;

            $this->view->date = date('d M Y H:i:s', strtotime($fetch['tgl']));
            $this->view->action = $fetch['action'];
            $this->view->trano = $fetch['trano'];
        }
    }

    public function fileuploadAction() {
        
    }

    public function doinsertfileAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $filedata = Zend_Json::decode($this->getRequest()->getParam('file'));

        if (count($filedata) > 0) {
            foreach ($filedata as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $prj_kode,
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->file->insert($arrayInsert);
            }
        }

        $return = array('success' => true);

        $json = Zend_Json::encode($return);
        echo $json;
    }

    public function insertpulsaAction() {
//        $data = $this->pulsa->fetchAll()->toArray();
//        var_dump($data);die;
//        $kode = array();
//
//        foreach($data as $key => $val)
//        {
//            $kode[] = $val['kode'];
//
//        }
//
//        $hasil = implode(',',$kode);
//        var_dump($hasil);die;
//        $where = "kode_brg IN ($hasil) ";
//
//        $coba = $this->material->fetchAll($where)->toArray();
//        var_dump($coba);die;
    }

    public function getmaterialAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else {
            $search = "$option LIKE '%$textsearch%'";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->material->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->material->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertpulsaAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $kode_brg = $this->getRequest()->getParam('kode_brg');
        $nama_brg = $this->getRequest()->getParam('nama_brg');

        $cek = $this->pulsa->fetchRow("kode = '$kode_brg'");

        if ($cek) {
            $return = array("success" => false, "pesan" => "Sorry, Material Code exists");
        } else {
            $insert = array(
                'kode' => $kode_brg,
                'nama_brg' => $nama_brg
            );

            $this->pulsa->insert($insert);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function pulsaAction() {
        
    }

    public function viewpulsaAction() {
        
    }

    public function getpulsaAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 10;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->pulsa->fetchAll()->toArray(null, array($sort . " " . $dir), $limit, $offset);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function dodeletepulsaAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');

        $this->pulsa->delete("id = '$id'");

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function recoverWorkflowAction() {
        
    }

    public function getCredentialTransactionAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $cek = $this->creTrans->fetchAll("trano LIKE '%$trano%'", array("trano DESC", "tgl DESC"));
        if ($cek) {
            $cek = $cek->toArray();
            foreach ($cek as $k => $v) {
                $cek[$k]['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
            }
        }
        $return = array("success" => true, "data" => $cek);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doRecoverWorkflowAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $id = $this->getRequest()->getParam('id');

        $cek = $this->creTrans->fetchRow("trano LIKE '%$trano%' AND id = $id");

        $return = array("success" => false);
        if ($cek) {
            $cek = $cek->toArray();

            $json = Zend_Json::decode($cek['data']);
            $ret = $this->deleteTrans($trano, '', QDC_User_Session::factory()->getCurrentUID(), 'RECOVER WORKFLOW');
            if (!$ret)
                $return['msg'] = 'No Workflow Found.';
            else {
                foreach ($json as $k => $v) {
                    unset($v['workflow_trans_id']);
                    $this->workflowTrans->insert($v);
                }
                $return = array("success" => true);
            }
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function authUploadAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $item_type = $this->getRequest()->getParam('item_type');
        $username = $this->getRequest()->getParam('username');
        $password = $this->getRequest()->getParam('password');

        $auth = Zend_Auth::getInstance();

        $config = new Zend_Config_Ini('../application/configs/application.ini', getenv('APPLICATION_ENV'));
        $option = $config->ldap->toArray();

        $adapter = new Zend_Auth_Adapter_Ldap($option, $username, $password);

        $authResult = $auth->authenticate($adapter);

        $found = false;
        $result['success'] = false;
        if ($authResult->isValid()) {
            switch ($item_type) {
                case 'ASF':
                case 'BSF':
                case 'ASFO':
                    $roles = new Admin_Models_Userrole();
                    $myRoles = $roles->getRoleGrouped($username);

                    $result['success'] = true;

                    if ($myRoles) {
                        foreach ($myRoles as $k => $v) {
                            if ($v['role_name'] == 'Finance and Logistic' || $v['role_name'] == 'IT System') {
                                $found = true;
                                break;
                            }
                        }
                    }
                    break;
                case 'UBH':
                    $roles = new Admin_Models_Userrole();
                    $myRoles = $roles->getRoleGrouped($username);

                    $result['success'] = true;
                    if ($myRoles) {
                        foreach ($myRoles as $k => $v) {
                            if ($v['display_name'] == 'Logistics Supervisor' || $v['role_name'] == 'IT System') {
                                $found = true;
                                break;
                            }
                        }
                    }
                    break;
            }

            $result['auth'] = $found;
            if ($result['auth'] == false) {
                $result['success'] = false;
                $result['msg'] = 'Sorry, You dont have sufficient credential to perform this action';
            }
        } else
            $result['msg'] = 'Username or Password is not valid';

        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
?>

