<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/21/11
 * Time: 8:36 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_AdjustingjournalController extends Zend_Controller_Action {

    private $adjustingjournal;
    private $jenisJurnal = array(
        'ADJ' => 'Adjusting Journal',
        'JV' => 'Voucher Journal',
        'SJ' => 'Sales Journal',
        'JS' => 'Settlement Journal'
    );
    private $counter;
    private $db;

    public function init() {
        $this->adjustingjournal = new Finance_Models_AdjustingJournal();
        $this->counter = new Default_Models_MasterCounter();
        $this->session = new Zend_Session_Namespace('login');
        $this->db = Zend_Registry::get('db');
    }

    public function menuAction() {
        
    }

    public function insertjournalAction() {
        
    }

    public function doinsertadjustingjournalAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $jurnal = Zend_Json::decode($this->getRequest()->getParam('jsonJurnal'));

        $trano = $this->counter->setNewTrans($jurnal[0]['tipe_jurnal']);
        $uid = $this->session->userName;
        $tgl = ($this->_getParam("tgl") == '') ? date('Y-m-d H:i:s') : date("Y-m-d", strtotime($this->_getParam("tgl")));

        foreach ($jurnal as $key => $val) {
            $insertadjustingjournal = array(
                "trano" => $trano,
                "type" => $val['tipe_jurnal'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "ref_number" => $val['ref_number'],
                "ref_number2" => $val['ref_number2'],
                "ref_number_myob" => $val['ref_number3'],
                "tgl" => $tgl,
                "tgl_input" => date("Y-m-d H:i:s"),
                "uid" => $uid,
                "ket" => $val['ket'],
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "rateidr" => $val['rateidr'],
                "job_number" => $val['job_number'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit']),
               "status_doc_cip" => ($val['status_doc_cip'] == '1' ? '0' : '1')
            );

            $this->adjustingjournal->insert($insertadjustingjournal);
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function getgeneraljurnalAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $start = $this->getRequest()->getParam('startdate');
        $end = $this->getRequest()->getParam('enddate');
        $ref_number = $this->getRequest()->getParam('ref_number');

//        var_dump($type,$startdate,$enddate,$ref_number);die;

        if ($start != '' && $end != '') {
            $startdate = date('Y-m-d', strtotime($start));
            $enddate = date('Y-m-d', strtotime($end));
        }

        $search = '';

        if ($type == '') {
            $search = null;
        } else {
            $search = "WHERE type = '$type'";
        }

        if ($start != '' && $end != '') {
            if ($search == null) {
                $search = " WHERE DATE(tgl) between '$startdate' AND '$enddate'";
            } else {
                $search .= "AND DATE(tgl) between '$startdate' AND '$enddate'";
            }
        }

        if ($ref_number != '') {
            if ($search == null) {
                $search = "WHERE ref_number like '%$ref_number%' ";
            } else {
                $search .= "AND ref_number like '%$ref_number%'";
            }
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM accounting_journal $search ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();

//        $data['data'] = $this->adjustingjournal->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
//        $data['total'] = $this->adjustingjournal->fetchAll()->count();
        $data['data'] = $fetch;
        $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editJournalAction() {
        
    }

    public function getGeneralJurnalTranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        $trano = $this->_getParam("trano");
        $ref_number = $this->_getParam("ref_number");
        $noUid = ($this->_getParam("no_uid") != 'true') ? false : true;
        $order = ($this->_getParam("order_by") == '') ? "tgl DESC" : $this->_getParam("order_by");

        $select = $this->db->select()
                ->from(array($this->adjustingjournal->__name()), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS trano"),
                    "tgl",
                    "uid",
                    "debit" => "SUM(debit)",
                    "credit" => "SUM(credit)",
                    "ref_number"
                ))
                ->order(array($order))
                ->group(array("trano"))
                ->limit($limit, $offset);

        if ($trano) {
            $select = $select->where("trano LIKE '%$trano%'");
        }

        if ($ref_number) {
            $select = $select->where("ref_number LIKE '%$ref_number%'");
        }

        $select = $select->where("stsclose = 0");

        $data = $this->db->fetchAll($select);
        $counter = new Default_Models_MasterCounter();
        foreach ($data as $k => $v) {
            $tranoData = $v['trano'];
            $type = $counter->getTransTypeFlip($tranoData);
            $data[$k]['tgl'] = date("d M Y", strtotime($v['tgl']));
            if ($type) {
                $data[$k]['type'] = $type;
                $data[$k]['name_type'] = $this->jenisJurnal[$type];
            }
            if (!$noUid)
                $data[$k]['person'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
        }

        $result['data'] = $data;
        $result['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        $result['count'] = $result['total'];

        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getGeneralJurnalDataAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->_getParam("trano");

        $select = $this->db->select()
                ->from(array($this->adjustingjournal->__name()))
                ->where("trano LIKE '%$trano%'")
                ->order(array("id ASC"));

        $data = $this->db->fetchAll($select);
        $counter = new Default_Models_MasterCounter();
        foreach ($data as $k => $v) {
//            if ($v['stsclose'] == 1)
//            {
//                $invalid = true;
//                break;
//            }
            $data[$k]['tipe_jurnal'] = $v['type'];
            $data[$k]['status_doc_cip'] = ($v['status_doc_cip'] == '0' ? '1' : '2');
        }

        $type = $counter->getTransTypeFlip($trano);

//        if ($invalid)
//            $result = array(
//                "success" => false,
//                "msg" => "This Transaction has been closed"
//            );
//        else
//        {
        $tgl = $data[0]['tgl'];
        $result = array(
            "success" => true,
            'data' => $data,
            'tgl' => date("d M Y", strtotime($tgl)),
//                "type" => $this->jenisJurnal[$type]
            "type" => $type
        );
//        }

        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doUpdateGeneralJournalAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $jurnal = Zend_Json::decode($this->getRequest()->getParam('jsonJurnal'));

        $trano = $this->_getParam('trano');
        $newTgl = $this->_getParam('tgl');

        $uid = $this->session->userName;

        $before = $this->adjustingjournal->fetchAll("trano = '$trano'");
        if (!$before)
            $this->getResponse()->setBody("{success: false, msg: 'Journal not found'}");
        else {
//            if ($before[0]['stsclose'] == 1)
//            {
//                $this->getResponse()->setBody("{success: false, msg: 'This journal has been closed'}");
//            }
//            else
//            {
            $tgl = $before[0]['tgl'];

            if (date("Y-m-d", strtotime($tgl)) != date("Y-m-d", strtotime($newTgl))) {
                $tgl = date("Y-m-d", strtotime($newTgl));
            }

            if (date("Y", strtotime($newTgl)) == '1970' || $tgl == '') {
                $this->getResponse()->setBody(Zend_Json::encode(array(
                            "success" => false,
                            "msg" => "Date is not valid, please contact IT support. Debug : " . $tgl . " -> " . $newTgl
                )));
                die;
            }

            $log['generaljournal-detail-before'] = $before->toArray();
            $this->adjustingjournal->delete("trano = '$trano'");
            foreach ($jurnal as $key => $val) {
                $insertadjustingjournal = array(
                    "trano" => $trano,
                    "type" => $val['tipe_jurnal'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "ref_number" => $val['ref_number'],
                    "ref_number2" => $val['ref_number2'],
                    "ref_number_myob" => $val['ref_number3'],
                    "tgl" => $tgl,
                    "tgl_input" => date("Y-m-d H:i:s"),
                    "uid" => $uid,
                    "ket" => $val['ket'],
                    "coa_kode" => $val['coa_kode'],
                    "coa_nama" => $val['coa_nama'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "job_number" => $val['job_number'],
                    "debit" => floatval($val['debit']),
                    "credit" => floatval($val['credit']),
                    "status_doc_cip" => ($val['status_doc_cip'] == '1' ? '0' : '1')
                );

                $this->adjustingjournal->insert($insertadjustingjournal);
            }

            $after = $this->adjustingjournal->fetchAll("trano = '$trano'");
            $log2['generaljournal-detail-after'] = $after->toArray();
            $arrayLog = array(
                "trano" => $trano,
                "uid" => $this->session->userName,
                "tgl" => date('Y-m-d H:i:s'),
                "prj_kode" => '',
                "sit_kode" => '',
                "action" => "UPDATE",
                "data_before" => Zend_Json::encode($log),
                "data_after" => Zend_Json::encode($log2),
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $log = new Admin_Models_Logtransaction();
            $log->insert($arrayLog);
            $this->getResponse()->setBody("{success: true}");
//            }
        }
    }

}
