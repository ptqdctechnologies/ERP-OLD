<?php

class Admin_Models_Workflowtrans extends Zend_Db_Table_Abstract {

    protected $_name = 'workflow_trans';
    protected $db;
    protected $const;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name() {
        return $this->_name;
    }

    public function getDocumentStatus($workflowID = '', $itemID = '', $uid = '') {
        $result = $this->fetchRow('workflow_id = ' . $workflowID . ' AND item_id = \'' . $itemID . '\'');
        return $result['approve'];
    }

    public function getDocumentType($workflowTransID = '') {
        $sql = "SELECT wt.approve,wt.comment,wt.item_id,wt.uid,wit.name,wit.workflow_item_type_id FROM
					workflow_trans wt
				LEFT JOIN
					workflow_item wi
				ON wi.workflow_item_id = wt.workflow_item_id
				LEFT JOIN
					workflow_item_type wit
				ON wit.workflow_item_type_id = wi.workflow_item_type_id
				WHERE 
					wt.workflow_trans_id = $workflowTransID";
        $fetch = $this->db->query($sql);
        $result = $fetch->fetch();
        if ($result) {
            $comment = str_replace("\n", "", $result['comment']);
            $comment = str_replace("\r", "", $comment);
            $comment = str_replace("\"", "", $comment);
            $comment = str_replace("'", "", $comment);
            $result['comment'] = $comment;
        }
        return $result;
    }

    public function getDocumentTypeByTrano($trano = '') {
        $sql = "SELECT wt.approve,wt.comment,wt.item_id,wt.uid,wit.name,wit.workflow_item_type_id FROM
					workflow_trans wt
				LEFT JOIN
					workflow_item wi
				ON wi.workflow_item_id = wt.workflow_item_id
				LEFT JOIN
					workflow_item_type wit
				ON wit.workflow_item_type_id = wi.workflow_item_type_id
				WHERE
					wt.item_id = '$trano'
                LIMIT 1";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetch();
        if ($result) {
            $comment = str_replace("\n", "", $result['comment']);
            $comment = str_replace("\r", "", $comment);
            $comment = str_replace("\"", "", $comment);
            $comment = str_replace("'", "", $comment);
            $result['comment'] = $comment;
        }
        return $result;
    }

    public function getDocumentSubmitter($trano) {
        $sql = "SELECT * FROM workflow_trans
                WHERE
                    item_id = '$trano'
                AND
                    approve IN (100,150)
                ORDER BY date DESC LIMIT 1";
        $fetch = $this->db->query($sql);
        if ($fetch) {
            $result = $fetch->fetch();

            $comment = str_replace("\n", "", $result['comment']);
            $comment = str_replace("\r", "", $comment);
            $comment = str_replace("\"", "", $comment);
            $comment = str_replace("'", "", $comment);
            $result['comment'] = $comment;
        }
        return $result;
    }

    public function checkPersonInWorkflow($uid, $trano) {
        $select = $this->db->select()
                ->from(array($this->_name))
                ->where("item_id = '$trano'")
                ->group(array("workflow_item_id"));

        $wf = $this->db->fetchAll($select);

        $workflow_structure = QDC_Model_Admin::init(array("Workflowstructure", "Masterrole", "Masterlogin"));
        $found = false;

        foreach ($wf as $k => $v) {
            $wf_item_id = $v['workflow_item_id'];

            $select = $this->db->select()
                    ->from(array($workflow_structure->Masterlogin->__name()), array(
                        "id_user" => "id"
                    ))
                    ->where("uid = '$uid'");

            $select2 = $this->db->select()
                    ->from(array("b" => $select))
                    ->joinLeft(array("a" => $workflow_structure->Masterrole->__name()), "a.id_user = b.id_user", array(
                "id"
            ));

            $select3 = $this->db->select()
                    ->from(array("c" => $select2))
                    ->joinLeft(array("d" => $workflow_structure->Workflowstructure->__name()), "d.master_role_id = c.id")
                    ->where("d.workflow_item_id = $wf_item_id");

            $cek = $this->db->fetchAll($select3);
            if ($cek) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    public function getPersonInWorkflow($trano) {
        $select = $this->db->select()
                ->from(array($this->_name))
                ->where("item_id = '$trano'")
                ->group(array("workflow_item_id"));

        $wf = $this->db->fetchAll($select);

        $workflow_structure = QDC_Model_Admin::init(array("Workflowstructure", "Masterrole", "Masterlogin"));
        $found = false;

        $array = array();
        if ($wf[0]['generic'] == 1) {
            foreach ($wf as $k => $v) {
                $uid = $v['uid'];
                $array[$uid] = $uid;
            }
        } else {
            foreach ($wf as $k => $v) {
                $wf_item_id = $v['workflow_item_id'];

                $select = $this->db->select()
                        ->from(array($workflow_structure->Workflowstructure->__name()), array(
                            "master_role_id"
                        ))
                        ->where("workflow_item_id = $wf_item_id")
                        ->group(array("master_role_id"));

                $select2 = $this->db->select()
                        ->from(array("a" => $select))
                        ->joinLeft(array("b" => $workflow_structure->Masterrole->__name()), "b.id = a.master_role_id", array(
                            "id_user"
                        ))
                        ->group(array("id_user"));

                $select3 = $this->db->select()
                        ->from(array("c" => $select2))
                        ->joinLeft(array("d" => $workflow_structure->Masterlogin->__name()), "c.id_user = d.id", array(
                            "uid"
                        ))
                        ->group(array("uid"));

                $cek = $this->db->fetchAll($select3);
                if ($cek) {
                    foreach ($cek as $k2 => $v2) {
                        $uid = $v2['uid'];
                        $array[$uid] = $uid;
                    }
                }
            }
        }
        return $array;
    }

    public function getCaptionID($captionID = '') {
        $cek = $this->fetchRow("caption_id = '$captionID'");
        if ($cek) {
            $urut = 2;
            $notFound = true;
            while ($notFound) {
                $newCaptionID = $captionID . " " . $urut;
                $cek = $this->fetchRow("caption_id = '$newCaptionID'");
                if (!$cek) {
                    $notFound = false;
                }
                $urut++;
            }

            $captionID = $newCaptionID;
        }

        return $captionID;
    }

    public function getFinalApproval($trano = '') {
        $select = $this->db->select()
                ->from(array($this->_name))
                ->where("approve=400")
                ->where("item_id=?", $trano)
                ->order(array("date desc"));

        $data = $this->db->fetchRow($select);

        return $data;
    }

    public function updateStatusApprove($model, $type, $docs_id, $status) {

        switch ($type) {

             case 'BRF':
                if ($status == $this->const['DOCUMENT_FINAL']) {
                    $arf = new Procurement_Models_Procurementarfh();
                    $brfp = new Procurement_Models_BusinessTripPayment();

                    $arf->update(array("approve" => $status), "trano = '$docs_id'");
                    $model->update(array("approve" => $status), "trano = '$docs_id'");
                    $brfp->update(array("approve" => $status), "trano = '$docs_id'");
                } else{
                    $model->update(array("approve" => $status), "trano = '$docs_id'");
                }
                break;
            case 'BSF':
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            case 'PBOQ3':
                $model->update(array("approve" => $status), "notran = '$docs_id' ");
                break;
            case 'ASFP':
            case 'ARFP':
                $model->update(array("approve" => $status), "trano_ref = '$docs_id' ");
                break;
            case 'SUPP':
                $model->update(array("approve" => $status), "sup_kode = '$docs_id' ");
                break;
            case 'BRFP':
                $arf = new Procurement_Models_Procurementarfh();
                $arf->update(array("approve" => $status), "trano = '$docs_id' ");
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            case 'ARF':
            case 'ARFO':
                $arf = new Procurement_Models_Procurementarfh();
                $arfd = new Procurement_Models_Procurementarfd();
                $arf->update(array("approve" => $status), "trano = '$docs_id' AND approve !=400 ");
                $arfd->update(array("approve" => $status), "trano = '$docs_id' AND approve !=400 ");
                break;
            case 'ASF':
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            case 'PO':
            case 'POO':
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            case 'PR':
                $prh = new Procurement_Models_Procurementprh();
                $prh->update(array("approve" => $status), "trano = '$docs_id' ");
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            case 'PRO':
                $prh = new Procurement_Models_Procurementprh();
                $prh->update(array("approve" => $status), "trano = '$docs_id' ");
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            case 'DOR':
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            case 'RPI':
            case 'RPIO':
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            case 'iLOV':
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
//            case 'iCAN':
//                $model->update(array("approve" => $status), "trano = '$docs_id' ");
//                break;
            case 'REM':
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            case 'iSUP':
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            case 'AFE':
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
            default :
                $model->update(array("approve" => $status), "trano = '$docs_id' ");
                break;
        }
    }

}
