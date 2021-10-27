<?php

class Admin_TranoController extends Zend_Controller_Action
{
    private $db;
    private $DEFAULT;

    public function init()
    {
        $this->db = Zend_Registry::get("db");
        $this->DEFAULT = QDC_Model_Default::init(array(
            "MasterTranoType",
            "MasterCounter"
        ));
    }

    public function tranoMenuAction()
    {

    }

    public function tranoTypeAction()
    {

    }

    public function getTranoTypeAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $last = ($this->_getParam("last_trano") == '') ? false : true;

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;


        if ($last)
        {
            $select = $this->db->select()
                ->from(array($this->DEFAULT->MasterTranoType->__name()))
                ->limit($limit,$offset)
                ->order(array("type ASC"));
            $subselect = $this->db->select()
                ->from(array($this->DEFAULT->MasterCounter->__name()),array(
                    "id",
                    "urut",
                    "tra_no",
                    "bulan",
                    "tahun",
                    "statuspayment",
                    "statusfinance",
                    "name"
                ))
                ->order(array("id desc"));

            $s1 = $this->db->select()
                ->from(array("a" => $select))
                ->joinLeft(array("b" => $subselect),"a.trano_prefix = b.tra_no",array("id_counter" => "id","last_trano" => "urut","bulan","tahun","statuspayment","statusfinance","name","tra_no"));

            $s2 = $this->db->select()
                ->from(array("c" => $s1),array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS c.*")
                ))
                ->group(array("trano_prefix"));

            $select = $s2;
        }
        else
        {
            $select = $this->db->select()
                ->from(array($this->DEFAULT->MasterTranoType->__name()),array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                ))
                ->limit($limit,$offset)
                ->order(array("type ASC"));
        }


        $data = $this->db->fetchAll($select);
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $return = array("posts" => $data,"count"=>$count);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function deleteTranoTypeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $return = array("success" => false);

        $id = $this->_getParam("id");

        $select = $this->db->select()
            ->from(array($this->DEFAULT->MasterTranoType->__name()))
            ->where("id=?",$id);

        $data = $this->db->fetchRow($select);
        if ($data)
        {
            $this->DEFAULT->MasterTranoType->delete($this->db->quoteInto("id=?",$id));
            $return['success'] = true;
        }
        else
            $return['msg'] = "Item not found";

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function createTranoTypeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $return = array("success" => true);

        $type = $this->_getParam("type");
        $prefix = $this->_getParam("trano_prefix");
        $ket = $this->_getParam("ket");

        $select = $this->db->select()
            ->from(array($this->DEFAULT->MasterTranoType->__name()))
            ->where("type=?",$type)
            ->where("trano_prefix=?",$prefix);

        $data = $this->db->fetchRow($select);
        if (!$data)
        {
            $this->DEFAULT->MasterTranoType->insert(array(
                "type" => $type,
                "trano_prefix" => $prefix,
                "ket" => $ket
            ));
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function tranoNumberAction()
    {

    }

    public function createTranoNumberAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $return = array("success" => true);

        $id = $this->_getParam("id");
        $trano = $this->_getParam("trano_prefix");
        $urut = $this->_getParam("urut");
        $statuspayment = $this->_getParam("statuspayment");
        $statusfinance = $this->_getParam("statusfinance");
        $name = $this->_getParam("name");

        $this->DEFAULT->MasterCounter->cekTahun($trano,array("statuspayment" => $statuspayment, "statusfinance" => $statusfinance, "name" => $name),$urut);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateTranoNumberAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $return = array("success" => true);

        $id = $this->_getParam("id");
        $trano = $this->_getParam("trano_prefix");
        $urut = $this->_getParam("urut");
        $force = ($this->_getParam("force") == '') ? false : true;

        $select = $this->db->select()
            ->from(array($this->DEFAULT->MasterCounter->__name()))
            ->where("id=?",$id)
            ->where("tra_no=?",$trano);

        $data = $this->db->fetchRow($select);
        if ($data)
        {
            $last = $data['urut'];
            if (($last != $urut) && !$force)
            {
                $return = array("success" => false,"msg" => "Last Counter in Database is $last, Do You still want to Update Counter to $urut ?","counter_diff" => true);
            }
            else
            {
                $this->DEFAULT->MasterCounter->update(array(
                    "urut" => $urut
                ),"id=$id");
            }
        }
        else
        {
            $return = array("success" => false,"msg" => "Trano not found");
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}

?>