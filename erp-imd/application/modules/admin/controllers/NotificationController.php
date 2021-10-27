<?php

class Admin_NotificationController extends Zend_Controller_Action
{

    private $db;
    private $DEFAULT;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->DEFAULT = QDC_Model_Default::init(array(
            "UserNotification"
        ));
    }

    public function indexAction()
    {

    }

    public function managerAction()
    {

    }

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $select = $this->db->select()
            ->from(array($this->DEFAULT->UserNotification->__name()),array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
            ))
            ->limit($limit,$offset)
            ->order(array("uid ASC","prj_kode ASC"));

        $data = $this->db->fetchAll($select);
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        if ($data)
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                if ($v['prj_kode'] == '')
                    $data[$k]['prj_kode'] = 'ALL';
            }
        }

        $return = array("posts" => $data,"count"=>$count);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function deleteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $return = array("success" => false);

        $id = $this->_getParam("id");

        $select = $this->db->select()
            ->from(array($this->DEFAULT->UserNotification->__name()))
            ->where("id=?",$id);

        $data = $this->db->fetchRow($select);
        if ($data)
        {
            $this->DEFAULT->UserNotification->delete($this->db->quoteInto("id=?",$id));
            $return['success'] = true;
        }
        else
            $return['msg'] = "Item not found";

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function createAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $return = array("success" => true);

        $json = ($this->_getParam("json") != '') ? Zend_Json::decode($this->_getParam("json")) : array();
        $prjKode = $this->_getParam("prj_kode");
        $type = $this->_getParam("type");
        $itemType = $this->_getParam("item_type");

        foreach($json as $k => $v)
        {
            $select = $this->db->select()
                ->from(array($this->DEFAULT->UserNotification->__name()))
                ->where("uid=?",$v['uid'])
                ->where("prj_kode=?",$prjKode)
                ->where("type_notification=?",$type)
                ->where("item_type=?",$itemType);

            $data = $this->db->fetchRow($select);
            if (!$data)
            {
                $this->DEFAULT->UserNotification->insert(array(
                    "uid" => $v['uid'],
                    "prj_kode" => $prjKode,
                    "item_type" => $itemType,
                    "type_notification" => $type,
                    "active" => 1
                ));
            }
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function tesAction()
    {
//        $this->_helper->viewRenderer->setNoRender();
//        $notif = new Default_Models_UserNotification();
//        $notif->sendEmailNotificationFinalApproval('ARF','ARF01-13001062','Q000103');
    }
}
?>