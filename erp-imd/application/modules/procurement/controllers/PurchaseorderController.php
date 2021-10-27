<?php
class Procurement_PurchaseorderController extends Zend_Controller_Action
{
    private $db;
    private $log;
    private $DEFAULT;

    public function init()
    {
        $this->DEFAULT = QDC_Model_Default::init(array(
            "ProcurementPod",
            "ProcurementPoh",
        ));

        $this->db = Zend_Registry::get("db");
        $this->log = new Admin_Models_Logtransaction();
    }

    public function lastlogAction()
    {
        $trano = $this->getRequest()->getParam("trano");
        $cek = $this->log->fetchRow("trano = '$trano'",array("tgl DESC"),1,0);
        if ($cek)
        {
            $cek = $cek->toArray();
            $ldap = new Default_Models_Ldap();

            $dataBefore = Zend_Json::decode($cek['data_before']);
            $this->view->result = $dataBefore['po-detail-before'];
//            $acc = $ldap->getAccount($cek['uid']);
//            $cek['name'] = $acc['displayname'][0];
            $cek['name'] = QDC_User_Ldap::factory(array("uid" => $cek['uid']))->getName();
            $this->view->ket = $cek;
        }
    }

    public function getDetailAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->_getParam("trano");
        $return = array();
        $return['success'] = false;
        $select = $this->db->select()
            ->from(array($this->DEFAULT->ProcurementPod->__name()),array(
                "total" => ("SUM(qtyspl*hargaspl)"),
                "trano",
                "val_kode"
            ))
            ->where("trano=?",$trano)
            ->group(array("trano"));

        $data = $this->db->fetchRow($select);

        if ($data)
        {
            $select = $this->db->select()
                ->from(array($this->DEFAULT->ProcurementPoh->__name()),array(
                    "prj_kode",
                    "sit_kode",
                    "prj_nama",
                    "sit_nama",
                ))
                ->where("trano=?",$trano)
                ->group(array("trano"));

            $h = $this->db->fetchRow($select);

            $data = QDC_Document_Model::factory()->sanitize($data);
            $data['prj_kode'] = $h['prj_kode'];
            $data['prj_nama'] = $h['prj_nama'];
            $return['data'] = $data;
            $return['success'] = true;
        }

        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}
?>