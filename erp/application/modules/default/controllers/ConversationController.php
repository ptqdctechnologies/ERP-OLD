<?php

class ConversationController extends Zend_Controller_Action {

    private $db;
    private $session;
    private $conversation;

    public function init() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->session = new Zend_Session_Namespace('login');
        $this->workflow = new Admin_Models_Workflow();
        $this->conversation = new Default_Models_Conversation();
    }

   public function countmyconversationAction() {
        $this->_helper->viewRenderer->setNoRender();

        $myUid = $this->session->userName;
            $query = "SELECT SQL_CALC_FOUND_ROWS trano, uid_sender, uid_receiver  FROM conversation WHERE  uid_receiver = '$myUid'
                     AND `read` = 0 ORDER BY DATE DESC;";
            $fetch = $this->db->query($query);
            $hasil = $fetch->fetchAll();

            $return['count'] += floatval($this->db->fetchOne('SELECT FOUND_ROWS()'));
        if ($myUid == 'kiki') {
            $query = "SELECT SQL_CALC_FOUND_ROWS * FROM request_cancel WHERE stsproses = 0";
            $fetch = $this->db->query($query);
            $hasil = $fetch->fetchAll();

            $return['count'] += floatval($this->db->fetchOne('SELECT FOUND_ROWS()'));
        } else if ($myUid == 'jonhar') {
            $query = "SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_registerco WHERE stsproses = 0";
            $fetch = $this->db->query($query);
            $hasil = $fetch->fetchAll();

            $return['count'] += floatval($this->db->fetchOne('SELECT FOUND_ROWS()'));
        }  
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showdocumentAction() {
        $url = $this->getRequest()->getParam('url');
        if ($url != '') {
            $this->view->url = $url;
        }
    }

    public function getmyteamAction() {
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->getRequest()->getParam('id');
        $trano = $this->getRequest()->getParam('trano');

        if ($trano == '') {
            $sql = "SELECT workflow_item_id FROM
                        workflow
                    WHERE
                        uid = '$uid'
                    GROUP BY workflow_item_id
                    ";
            $fetch = $this->db->query($sql);
            $hasil = $fetch->fetchAll();

            $uidOther = array();

            foreach ($hasil as $key => $val) {
                $workflow_item_id = $val['workflow_item_id'];
                $sql = "SELECT uid FROM
                            workflow
                        WHERE
                            workflow_item_id = $workflow_item_id
                        and uid != '$uid'";
                $fetch = $this->db->query($sql);
                $cekUid = $fetch->fetchAll();
                if ($cekUid) {
                    foreach ($cekUid as $key2 => $val2) {
                        $uids = $val2['uid'];
                        if ($uids == '')
                            continue;
                        $uidOther[$uids] = $uids;
                    }
                }
            }
        }
        else {
            $sql = "SELECT uid,workflow_item_id,prj_kode FROM
                        workflow_trans
                    WHERE
                        item_id = '$trano'
                    AND
                        (approve = 100 OR approve = 150)
                    ORDER BY date ASC    
                    LIMIT 1
                    ";
            $fetch = $this->db->query($sql);
            $hasil = $fetch->fetch();
            if ($hasil) {
                $uid_start = $hasil['uid'];
                $prj_kode = $hasil['prj_kode'];
                $workflow_item_id = $hasil['workflow_item_id'];
            } else {
                echo "{posts: [], count: 0}";
                die;
            }
            $sql = "SELECT uid FROM
                        workflow
                    WHERE
                        workflow_item_id = $workflow_item_id
                    AND
                        uid != '$uid'
                    ORDER BY uid ASC";
            $fetch = $this->db->query($sql);
            $hasil = $fetch->fetchAll();

            foreach ($hasil as $key => $val) {
                $uids = $val['uid'];
                $uidOther[$uids] = $uids;
            }
        }
        $ldap = new Default_Models_Ldap();
        $result = array();
        if (count($uidOther) > 0) {
            $i = 1;
            asort($uidOther);
            foreach ($uidOther as $key => $val) {
//                $fromLdap = $ldap->getAccount($key);
                $sql = "SELECT c.display_name FROM
                            master_login a
                        LEFT JOIN
                            master_role b
                        ON
                            a.id = b.id_user
                        LEFT JOIN
                            master_role_type c
                        ON
                            c.id = b.id_role
                        WHERE
                            a.uid = '$key'
                        LIMIT 1";
                $fetch2 = $this->db->query($sql);
                $role = $fetch2->fetch();

                $roleName = $role['display_name'];
                if ($roleName == '')
                    $roleName = '&nbsp;';

                if ($key == $uid_start)
                    $is_start = 1;
                else
                    $is_start = 0;
                $result[] = array(
                    "id" => $i,
                    "uid" => $key,
//                    "name" => $fromLdap['displayname'][0],
                    "name" => QDC_User_Ldap::factory(array("uid" => $key))->getName(),
                    "role_name" => $roleName,
                    "prj_kode" => $prj_kode,
                    "is_start" => $is_start,
                    "workflow_item_id" => $workflow_item_id
                );
                $i++;
            }
        }

        $return['posts'] = $result;
        $return['count'] = count($result);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        echo $json;
    }

    public function getallmyconversationAction() {
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->getRequest()->getParam('id');

        if ($uid == '')
            $uid = $this->session->userName;

        $search = $this->_getParam("search");

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

//        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM
//                    conversation
//                WHERE
//                    uid_receiver = '$uid'
//                ORDER BY $sort $dir
//                LIMIT $offset,$limit;
//                ";

        $select = $this->db->select()
                ->from(array("conversation"), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                ))
                ->where("uid_receiver=?", $uid);

        if ($search)
            $select->where("message LIKE '%$search%'")
                    ->orWhere("trano LIKE '%$search%'")
                    ->orWhere("uid_sender LIKE '%$search%'");

        $select->order(array("$sort $dir"))
                ->limit($limit, $offset);

//        $fetch = $this->db->query($sql);
//        $hasil = $fetch->fetchAll();

        $hasil = $this->db->fetchAll($select);
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        $ldap = new Default_Models_Ldap();
        $counter = new Default_Models_MasterCounter();
        $arrayJurnal = array(
            "RPI",
            "BPV",
            "ARF"
        );
        foreach ($hasil as $key => &$val) {
            $cekRead = $this->conversation->fetchAll("id_reply = " . $val['id'] . " AND `read` = 0");
            if (count($cekRead) > 0) {
                $val['read'] = 0;
            }
            $val['message'] = str_replace('"', '', $val['message']);
            $val['message'] = str_replace("'", '', $val['message']);
            $val['message'] = str_replace("\r", '<br>', $val['message']);
            $val['message'] = str_replace("\n", '<br>', $val['message']);
            if ($val['uid_sender'] != 'SYSTEM' && $val['uid_sender'] != 'ADMIN')
                $name = QDC_User_Ldap::factory(array("uid" => $val['uid_sender']))->getName();
            else
                $name = $val['uid_sender'];

            if ($name != '' && $name != null)
                $hasil[$key]['name_sender'] = $name;
            else
                $hasil[$key]['name_sender'] = "";

            $hasil[$key]['cancel_po'] = false;
            $hasil[$key]['cancel_rpi'] = false;
            $hasil[$key]['regco'] = false;

            if (strpos($hasil[$key]['trano'], 'REGCO') !== false) {
                $hasil[$key]['assign_regco'] = true;

                //Cek apakah REGCO di reject oleh PM...
                $data = Zend_Json::decode($val['data']);
                if ($data != '') {
                    $hasil[$key]['reject_regco'] = $data['reject'];
                }
                //....
            } else
                $hasil[$key]['assign_regco'] = false;

            $type = $counter->getTransTypeFlip($hasil[$key]['trano_print']);
            if (in_array($type, $arrayJurnal)) {
                $hasil[$key]['print_jurnal'] = $hasil[$key]['trano_print'];
            }
        }

        $return['posts'] = $hasil;


        if ($uid == 'kiki') {
            $query = "SELECT SQL_CALC_FOUND_ROWS * FROM request_cancel WHERE stsproses = 0";
            $fetch = $this->db->query($query);
            $hasil2 = $fetch->fetchAll();

            foreach ($hasil2 as $k => $v) {
                $uid = $v['uid'];
//                $fromLdap = $ldap->getAccount($v['uid']);
//                $name = $fromLdap['displayname'][0];
                $name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                switch ($v['type']) {
                    case 'PO':
                        $ygmasuk = array(
                            "id" => md5(rand(10000, 100000) . date("d-m-y H:i:s")),
                            "date" => $v['date'],
                            "message" => $v['reason'],
                            "read" => $v['stsproses'],
                            "name_sender" => $name,
                            "trano" => $v['trano'],
                            "cancel_po" => true,
                            "id_cancel" => $v['id']
                        );
                        $return['posts'][] = $ygmasuk;
                        break;
                    case 'RPI':
                        $ygmasuk = array(
                            "id" => md5(rand(10000, 100000) . date("d-m-y H:i:s")),
                            "date" => $v['date'],
                            "message" => $v['reason'],
                            "read" => $v['stsproses'],
                            "name_sender" => $name,
                            "trano" => $v['trano'],
                            "cancel_rpi" => true,
                            "id_cancel" => $v['id']
                        );
                        $return['posts'][] = $ygmasuk;
                        break;
                }
            }
            $return['count'] += floatval($this->db->fetchOne('SELECT FOUND_ROWS()'));
        }

//        if ($uid == 'jonhar')
//        {
//            $query = "SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_registerco WHERE stsproses = 0";
//            $fetch = $this->db->query($query);
//            $hasil2 = $fetch->fetchAll();
//
//            foreach ($hasil2 as $k => $v)
//            {
//                $uid = $v['uid'];
//                $fromLdap = $ldap->getAccount($v['uid']);
//                $name = $fromLdap['displayname'][0];
//                $ygmasuk = array(
//                    "id" => md5(rand(10000,100000) . date("d-m-y H:i:s")),
//                    "date" => $v['tgl'],
//                    "message" => $v['ket'],
//                    "read" => $v['stsproses'],
//                    "name_sender" => $name,
//                    "trano" => $v['trano'],
//                    "regco" => true,
//                    "id_regco" => $v['id'],
//                    "cus_nama" => $v['cus_nama'],
//                    "pocustomer" => $v['pocustomer'],
//                    "totalidr" => number_format($v['total']),
//                    "totalusd" => number_format($v['totalusd']),
//                    "confirmation" => $v['confirmation']
//                );
//                $return['posts'][] = $ygmasuk;
//            }
//            $return['count'] += floatval($this->db->fetchOne('SELECT FOUND_ROWS()'));
//        }

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        echo $json;
    }

    public function getprevmessageAction() {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->getRequest()->getParam('id');

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM
                    conversation
                WHERE
                    id = $id
                OR
                    id_reply = $id
                ORDER BY date DESC
                ";
        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        $ldap = new Default_Models_Ldap();
        foreach ($hasil as $key => &$val) {
            $val['date'] = date("D, d/m/Y H:i:s", strtotime($val['date']));
//            $val['message'] = str_replace('"','',$val['message']);
//            $val['message'] = str_replace("'",'',$val['message']);
            $val['message'] = str_replace("\r", '<br>', $val['message']);
            $val['message'] = str_replace("\n", '<br>', $val['message']);
            $uid = $val['uid_sender'];
            if ($uid != 'SYSTEM' && $uid != 'ADMIN') {
                $name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
            } else
                $name = $uid;

            if ($name != '' && $name != null)
                $hasil[$key]['name_sender'] = $name;
            else
                $hasil[$key]['name_sender'] = "";
        }

        $return['message'] = $hasil;
        $return['success'] = true;
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        echo $json;
    }

    public function setreadAction() {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->getRequest()->getParam('id');
        $uid = $this->session->userName;

        $sql = "UPDATE
                    conversation
                SET
                    `read` = 1
                WHERE
                (
                    id = $id
                OR
                    id_reply = $id
                )
                AND
                    uid_receiver = '$uid'
                ORDER BY date DESC
                ";
        $fetch = $this->db->query($sql);
    }

    public function setreplyAction() {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->getRequest()->getParam('id');
        $msg = $this->getRequest()->getParam('message');
        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $uid_receiver = $this->getRequest()->getParam('uid_receiver');
        $workflow_item_id = $this->getRequest()->getParam('workflow_item_id');
        $uid = $this->session->userName;

        if ($id != 0) {
            $cek = $this->conversation->fetchRow("id = $id");
            if ($cek) {
                if ($cek['id_reply'] != 0 && $cek['id_reply'] != '')
                    $idReplyOri = $cek['id_reply'];
                else
                    $idReplyOri = $cek['id'];
                $id = $idReplyOri;
            }
        }

        $arrayInsert = array(
            "id_reply" => $id,
            "workflow_item_id" => $workflow_item_id,
            "uid_sender" => $uid,
            "uid_receiver" => $uid_receiver,
            "message" => $msg,
            "date" => date('Y-m-d H:i:s'),
            "trano" => $trano,
            "prj_kode" => $prj_kode
        );

        $this->conversation->insert($arrayInsert);
        echo "{success: true}";
    }

    public function getmessagefromteamAction() {
        $this->_helper->viewRenderer->setNoRender();
        $uid_sender = $this->getRequest()->getParam('id');
        $uid = $this->session->userName;

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM
                    conversation
                WHERE
                (
                    (uid_sender = '$uid_sender'
                AND
                    uid_receiver = '$uid')
                OR
                    (uid_receiver = '$uid_sender'
                AND
                    uid_sender = '$uid')
                )
                AND
                    (id_reply = 0 OR id_reply is null)
                ORDER BY $sort $dir
                LIMIT $offset,$limit
                ";
        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        $ldap = new Default_Models_Ldap();
        foreach ($hasil as $key => &$val) {
            $val['date'] = date("D, d/m/Y H:i:s", strtotime($val['date']));
            $val['message'] = str_replace('"', '', $val['message']);
            $val['message'] = str_replace("'", '', $val['message']);
            $val['message'] = str_replace("\r", '<br>', $val['message']);
            $val['message'] = str_replace("\n", '<br>', $val['message']);
//            $fromLdap = $ldap->getAccount($val['uid_sender']);
//            $name = $fromLdap['displayname'][0];
            $name = QDC_User_Ldap::factory(array("uid" => $val['uid_sender']))->getName();
            if ($name != '' && $name != null)
                $hasil[$key]['name_sender'] = $name;
            else
                $hasil[$key]['name_sender'] = "";
        }

        $return['posts'] = $hasil;
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        echo $json;
    }

    public function getmessagebytranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('trano');

        $uid = $this->session->userName;

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM
                    conversation
                WHERE
                    trano = '$trano'
                ORDER BY date DESC;
                ";
        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        $ldap = new Default_Models_Ldap();
        foreach ($hasil as $key => &$val) {
            $val['message'] = str_replace('"', '', $val['message']);
            $val['message'] = str_replace("'", '', $val['message']);
            $val['message'] = str_replace("\r", '<br>', $val['message']);
            $val['message'] = str_replace("\n", '<br>', $val['message']);
//            $fromLdap = $ldap->getAccount($val['uid_sender']);
//            $name = $fromLdap['displayname'][0];
            $name = QDC_User_Ldap::factory(array("uid" => $val['uid_sender']))->getName();
            if ($name != '' && $name != null)
                $hasil[$key]['name_sender'] = $name;
            else
                $hasil[$key]['name_sender'] = "";
        }

        $return['posts'] = $hasil;
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        echo $json;
    }

    public function setmessageAction() {
        $this->_helper->viewRenderer->setNoRender();
        $msg = $this->getRequest()->getParam('message');
        $trano = $this->getRequest()->getParam('trano');
        $id_reply = $this->getRequest()->getParam('id_reply');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $uid_receiver = $this->getRequest()->getParam('uid_receiver');
        $workflow_item_id = $this->getRequest()->getParam('workflow_item_id');
        $uid = $this->session->userName;

        if ($id_reply != 0) {
            $cek = $this->conversation->fetchRow("id = $id_reply");
            if ($cek) {
                if ($cek['id_reply'] != 0 && $cek['id_reply'] != '')
                    $idReplyOri = $cek['id_reply'];
                else
                    $idReplyOri = $cek['id'];
                $id_reply = $idReplyOri;
            }
        }

        $arrayInsert = array(
            "id_reply" => $id_reply,
            "workflow_item_id" => $workflow_item_id,
            "uid_sender" => $uid,
            "uid_receiver" => $uid_receiver,
            "message" => $msg,
            "date" => date('Y-m-d H:i:s'),
            "trano" => $trano,
            "prj_kode" => $prj_kode
        );

        $this->conversation->insert($arrayInsert);
        echo "{success: true}";
    }

    public function setReadAllAction() {
        $this->_helper->viewRenderer->setNoRender();
        $uid = QDC_User_Session::factory()->getCurrentUID();

        $this->conversation->update(array("read" => 1), "uid_receiver = '$uid'");
        echo "{success: true}";
    }

}

?>