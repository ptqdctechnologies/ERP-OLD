<?php

class Admin_NominalController extends Zend_Controller_Action
{

    private $db;
    private $ADMIN;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->ADMIN = QDC_Model_Admin::init(array(
            "MasterBatasNilaiTransaksi"
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
            ->from(array($this->ADMIN->MasterBatasNilaiTransaksi->__name()),array(
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
            ->from(array($this->ADMIN->MasterBatasNilaiTransaksi->__name()))
            ->where("id=?",$id);

        $data = $this->db->fetchRow($select);
        if ($data)
        {
            $this->ADMIN->MasterBatasNilaiTransaksi->delete($this->db->quoteInto("id=?",$id));
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
        $itemType = $this->_getParam("item_type");
        $total = $this->_getParam("total_limit");

        $msg = '';
        foreach($json as $k => $v)
        {
            $select = $this->db->select()
                ->from(array($this->ADMIN->MasterBatasNilaiTransaksi->__name()))
                ->where("uid=?",$v['uid'])
                ->where("prj_kode=?",$prjKode)
                ->where("item_type=?",$itemType);

            $data = $this->db->fetchRow($select);
            if (!$data)
            {
                $this->ADMIN->MasterBatasNilaiTransaksi->insert(array(
                    "uid" => $v['uid'],
                    "prj_kode" => $prjKode,
                    "item_type" => $itemType,
                    "total_limit" => $total
                ));
            }
            else
            {
                $return['success'] = false;
                $msg .= "- Person : " . QDC_User_Ldap::factory(array("uid"=>$v['uid']))->getName() . "," . $prjKode . "," . $itemType . " Exist.<br>";
            }
        }

        if ($msg)
            $return['msg'] = $msg;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function tesAction()
    {
///        echo $e;
    }
}
?>