<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/13/11
 * Time: 9:28 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_BanktransactionController extends Zend_Controller_Action {

    private $bankin;
    private $bankout;
    private $counter;
    private $db;
    private $rate_idr;
    private $adjustingjournal;

    public function init() {
        $this->bankin = new Finance_Models_BankReceiveMoney();
        $this->bankout = new Finance_Models_BankSpendMoney();
        $this->counter = new Default_Models_MasterCounter();
        $this->session = new Zend_Session_Namespace('login');

        $this->adjustingjournal = new Finance_Models_AdjustingJournal();

        $this->db = Zend_Registry::get("db");
        $exhangeRate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
        $this->rate_idr = $exhangeRate['rateidr'];
    }

    public function menuAction() {
        
    }

    public function insertbankinAction() {
        $this->view->rateidr = $this->rate_idr;
    }

    public function doinsertbankinAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bankindata = Zend_Json::decode($this->getRequest()->getParam('bankindata'));
        $tgl = ($this->_getParam("tgl") == '') ? date('Y-m-d H:i:s') : date("Y-m-d", strtotime($this->_getParam("tgl")));

        $type = $this->_getParam("bank_type");

        $trano = $this->counter->setNewTrans($type);
        $uid = $this->session->userName;
        $rateidr = 0;

        foreach ($bankindata as $key => $val) {
            if ($val['val_kode'] != 'IDR')
                $rateidr = $val['rateidr'];

            $insertbankin = array(
                "trano" => $trano,
                "ref_number" => $val['ref_number'],
                "ref_number_2" => $val['ref_number_2'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $val['coa_kode'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "job_number" => $val['job_number'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit']),
                "rateidr" => floatval($rateidr),
                "status_doc_rpc" => ($val['status_doc_rpc'] == '1' ? '0' : '1')
            );

            $this->bankin->insert($insertbankin);
        }

        $this->getResponse()->setBody(Zend_Json::encode(array(
                    "success" => true,
                    "trano" => $trano
        )));
    }

    public function insertbankoutAction() {
        $this->view->rateidr = $this->rate_idr;
    }

    public function doinsertbankoutAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bankoutdata = Zend_Json::decode($this->getRequest()->getParam('bankoutdata'));
        $tgl = ($this->_getParam("tgl") == '') ? date('Y-m-d H:i:s') : date("Y-m-d", strtotime($this->_getParam("tgl")));
//        $trano = $this->counter->setNewTrans('BSM');
        $type = $this->_getParam("bank_type");

        $trano = $this->counter->setNewTrans($type);
        $uid = $this->session->userName;
        $rateidr = 0;

        foreach ($bankoutdata as $key => $val) {
            if ($val['val_kode'] != 'IDR')
                $rateidr = $val['rateidr'];

            $insertbankout = array(
                "trano" => $trano,
                "ref_number" => $val['ref_number'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $val['coa_kode'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "job_number" => $val['job_number'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit']),
                "rateidr" => floatval($rateidr)
            );

            $this->bankout->insert($insertbankout);
        }

        $this->getResponse()->setBody(Zend_Json::encode(array(
                    "success" => true,
                    "trano" => $trano
        )));
    }

    public function doinsertbankchargesAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bankchargesdata = Zend_Json::decode($this->getRequest()->getParam('bankchargesdata'));
        $tgl = ($this->_getParam("tgl") == '') ? date('Y-m-d H:i:s') : date("Y-m-d", strtotime($this->_getParam("tgl")));

        $trans = $this->_getParam("trans");

        $trano = $this->counter->setNewTrans($trans);
        if ($trano == "-0")
            $trano = $trans . '-' . date('ym/') . date('Hi');

        $uid = $this->session->userName;
        $rateidr = 0;

        foreach ($bankchargesdata as $key => $val) {
            if ($val['val_kode'] != 'IDR')
                $rateidr = $val['rateidr'];

            $insertbankcharges = array(
                "trano" => $trano,
                "ref_number" => $val['ref_number'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $val['coa_kode'],
                "job_number" => $val['job_number'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit']),
                "item_type" => "BCH",
                "rateidr" => floatval($rateidr)
            );

            $this->bankout->insert($insertbankcharges);
        }

        $this->getResponse()->setBody(Zend_Json::encode(array(
                    "success" => true,
                    "trano" => $trano
        )));
    }

    public function editBankinAction() {
        $this->view->rateidr = $this->rate_idr;
    }

    public function editBankchargesAction() {
        $this->view->rateidr = $this->rate_idr;
    }

    public function getJurnalDataAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->_getParam("trano");
        $type = $this->_getParam("type");

        $select = $this->db->select()
                ->where("trano LIKE '%$trano%'")
                ->order(array("id ASC"));

        if ($type == 'bank_in') {
            $select = $select
                    ->from(array($this->bankin->__name()));
        } elseif ($type == 'bank_out') {
            $select = $select
                    ->from(array($this->bankout->__name()));
        }

        $data = $this->db->fetchAll($select);
        if ($data) {
            foreach ($data as $key => $value) {
                $data[$key]['status_doc_rpc'] = ($value['status_doc_rpc'] == '0' ? '1' : '2');
            }
        }

        $tgl = $data[0]['tgl'];
        $result = array(
            "success" => true,
            'data' => $data,
            'tgl' => date("d M Y", strtotime($tgl)),
            "type" => $type
        );

        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doUpdateBankinAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bankindata = Zend_Json::decode($this->getRequest()->getParam('bankindata'));
        $trano = $this->_getParam("trano");
        $tgl = ($this->_getParam("tgl") != '') ? date("Y-m-d", strtotime($this->_getParam("tgl"))) : date('Y-m-d H:i:s');

        $uid = $this->session->userName;
        $rateidr = 0;

        $before = $this->bankin->fetchAll("trano = '$trano'");
        if (!$before)
            $this->getResponse()->setBody("{success: false, msg: 'Journal not found'}");
        else {
            $log['bankin-detail-before'] = $before->toArray();
            $this->bankin->delete("trano = '$trano'");
            foreach ($bankindata as $key => $val) {
                if ($val['val_kode'] != 'IDR')
                    $rateidr = $val['rateidr'];

                $insertbankin = array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "ref_number_2" => $val['ref_number_2'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $val['coa_kode'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "job_number" => $val['job_number'],
                    "coa_nama" => $val['coa_nama'],
                    "val_kode" => $val['val_kode'],
                    "debit" => floatval($val['debit']),
                    "credit" => floatval($val['credit']),
                    "rateidr" => floatval($rateidr),
                    "status_doc_rpc" => ($val['status_doc_rpc'] == '1' ? '0' : '1')
                );

                $this->bankin->insert($insertbankin);
            }
            $log2 = $this->bankin->fetchAll("trano = '$trano'")->toArray();

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
        }
    }

    public function editBankoutAction() {
        $this->view->rateidr = $this->rate_idr;
    }

    public function doUpdateBankoutAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bankoutdata = Zend_Json::decode($this->getRequest()->getParam('bankoutdata'));
        $trano = $this->_getParam("trano");
        $tgl = ($this->_getParam("tgl") != '') ? date("Y-m-d", strtotime($this->_getParam("tgl"))) : date('Y-m-d H:i:s');

        $uid = $this->session->userName;
        $rateidr = 0;
        $before = $this->bankout->fetchAll("trano = '$trano'");
        if (!$before)
            $this->getResponse()->setBody("{success: false, msg: 'Journal not found'}");
        else {
            $log['bankout-detail-before'] = $before->toArray();
            $this->bankout->delete("trano = '$trano'");
            foreach ($bankoutdata as $key => $val) {
                if ($val['val_kode'] != 'IDR')
                    $rateidr = $val['rateidr'];


                $insertbankin = array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $val['coa_kode'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "job_number" => $val['job_number'],
                    "coa_nama" => $val['coa_nama'],
                    "val_kode" => $val['val_kode'],
                    "debit" => floatval($val['debit']),
                    "credit" => floatval($val['credit']),
                    "rateidr" => floatval($rateidr)
                );

                $this->bankout->insert($insertbankin);
            }
            $log2 = $this->bankout->fetchAll("trano = '$trano'")->toArray();

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
        }
    }

    public function doUpdateBankchargesAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bankchargesdata = Zend_Json::decode($this->getRequest()->getParam('bankchargesdata'));
        $trano = $this->_getParam("trano");
        $tgl = ($this->_getParam("tgl") != '') ? date("Y-m-d", strtotime($this->_getParam("tgl"))) : date('Y-m-d H:i:s');

        $uid = $this->session->userName;
        $rateidr = 0;
        $before = $this->bankout->fetchAll("trano = '$trano'");
        if (!$before)
            $this->getResponse()->setBody("{success: false, msg: 'Journal not found'}");
        else {
            $log['bankcharges-detail-before'] = $before->toArray();
            $this->bankout->delete("trano = '$trano'");
            foreach ($bankchargesdata as $key => $val) {
                if ($val['val_kode'] != 'IDR')
                    $rateidr = $val['rateidr'];

                $insertbankcharges = array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $val['coa_kode'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "job_number" => $val['job_number'],
                    "coa_nama" => $val['coa_nama'],
                    "val_kode" => $val['val_kode'],
                    "debit" => floatval($val['debit']),
                    "credit" => floatval($val['credit']),
                    "item_type" => "BCH",
                    "rateidr" => floatval($rateidr)
                );

                $this->bankout->insert($insertbankcharges);
            }
            $log2 = $this->bankout->fetchAll("trano = '$trano'")->toArray();

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
        }
    }

    public function insertbankchargesAction() {
        $this->view->rateidr = $this->rate_idr;
    }

    public function vapbankchargesAction() {
        
    }

    public function getlistverifyAction() {

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $start = $this->getRequest()->getParam('start');
        $end = $this->getRequest()->getParam('end');
        $trano = str_replace('_', "/", $this->getRequest()->getParam('trano'));

        $jurnal = $this->getRequest()->getParam('jurnal');

        if ($start != '' && $end != '') {
            $startdate = date('Y-m-d', strtotime($start));
            $enddate = date('Y-m-d', strtotime($end));
        } else {
            $startdate = date("Y-m-d", strtotime("-7 days"));
            $enddate = date('Y-m-d');
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        $column = array();
        $column[] = new Zend_Db_Expr("*");
        $column["trans"] = new Zend_Db_Expr("SUBSTR(trano,1,3)");


        if (!$jurnal)
            $column["total"] = new Zend_Db_Expr("SUM(debit)");


        $select = $this->db->select()
                ->from(
                        array($this->bankout->__name()), $column)
                ->limit($limit, $offset);
        ;



        if (!$jurnal)
            $select = $select->group(array("trano"));

        $select = $select->where("item_type = 'BCH' AND stspost = 0 ");

//        var_dump($trano);
        if ($trano != '') {
            $select = $select->order(array("id"));
            $select = $select->where("trano LIKE ?", "%{$trano}%");
        } else {
            $select = $select->order(array("tgl", "trano"));
        }

        $select = $select->where("(" . $this->db->quoteInto("tgl >= ?", $startdate) . " AND " . $this->db->quoteInto("tgl <= ?", $enddate) . ")");


        $selectSql = (string) $select;
        $data['data'] = $this->db->fetchAll($selectSql);


        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function dopostbankchargesAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();


        $jsonData = $this->_getParam("jsonData");
        $json = Zend_Json::decode($jsonData);
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        foreach ($json as $key => $val) {
            $stspost = $val['stspost'];

            $trano = $val['trano'];
            if ($stspost == true) {
                $stspost = 1;
            }

            $updatestatus = array(
                "stspost" => $stspost,
                "uidpost" => $uid,
                "tglpost" => new Zend_Db_Expr("NOW()")
            );

            $this->bankout->update($updatestatus, "trano = '$trano'");
        }

        $json = "{success: true}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function checkpostbankAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");

        $select = $this->db->select()
                ->from(
                        array($this->bankout->__name()), new Zend_Db_Expr("COUNT(*) cek")
                )
                ->order(array("id", "tgl"));
        ;
        $select = $select->where("item_type = 'BCH' AND stspost = 1 ");
        if ($trano != '')
            $select = $select->where("trano = ?", "$trano");

        $selectSql = (string) $select;
        $data = $this->db->fetchRow($selectSql);

        if ($data['cek'] > 0)
            $return = array("success" => true);
        else
            $return = array("success" => false);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function checkdatetransactionAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $date = $this->getRequest()->getParam("date");
        $date = date("Y-m-d", strtotime($date));

        $periode = new Finance_Models_MasterPeriode();
        $check = $periode->checkdatetransaction($date);

        if ($check == 0)
            $return = array("success" => true);
        else
            $return = array("success" => false);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getTranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $ref_number = $this->getRequest()->getParam("ref_number");
        $jenis = $this->getRequest()->getParam("type");
        $order = $this->getRequest()->getParam("order_by");
        $jenisJurnal = ($this->getRequest()->getParam("jurnal_type") != '') ? Zend_Json::decode($this->getRequest()->getParam("jurnal_type")) : '';

        $offset = ($_POST["start"] != '') ? $_POST["start"] : 0;
        $limit = ($_POST["limit"] != '') ? $_POST["limit"] : 30;

        $jenisJurnal = $jenisJurnal[0];
        switch ($jenisJurnal) {
            case 'bank_in':
                $select = $this->db->select()
                        ->from(array($this->bankin->__name()), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS trano"),
                    "tgl",
                    "ref_number",
                    "debit" => "SUM(debit)",
                    "credit" => "SUM(credit)",
                ));
                break;
            case 'bank_out':
                $select = $this->db->select()
                        ->from(array($this->bankout->__name()), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS trano"),
                    "tgl",
                    "ref_number",
                    "debit" => "SUM(debit)",
                    "credit" => "SUM(credit)",
                ));
                break;
        }

        if ($trano)
            $select = $select->where("trano LIKE '%$trano%'");
        if ($ref_number)
            $select = $select->where("ref_number LIKE '%$ref_number%'");

        $select = $select->group(array("trano", "ref_number"));

        if ($order)
            $select = $select->order(array($order));

        $jurnal = $this->db->fetchAll($select);
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $return = array("success" => true, "data" => $jurnal, "count" => $count);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getrpcnumberAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->counter->setNewTrans('RPC');
        if ($trano)
            $succes = true;
        else {
            $succes = false;
            $msg = "Error!";
        }
        $this->getResponse()->setBody(Zend_Json::encode(array(
                    "success" => $succes,
                    "trano" => $trano,
                    "msg" => $msg
        )));
    }

    public function getRpcNumberFromJournalAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");

        $rpc = $this->adjustingjournal->fetchRow("type ='JS' and ref_number ='$trano' and ref_number2 is not null");
        if ($rpc) {
            $succes = true;
            $rpc = $rpc->toArray();
            $rpc_num = $rpc['ref_number2'];
        } else {
            $succes = false;
            $msg = "Error!";
        }
        $this->getResponse()->setBody(Zend_Json::encode(array(
                    "success" => $succes,
                    "rpc" => $rpc_num,
                    "msg" => $msg
        )));
    }

    public function checkRpcFromAsfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $asfc = new Default_Models_AdvanceSettlementFormCancel();

        $cek = $asfc->fetchAll("trano = '$trano'");

        if ($cek) {
            $cek = $cek->toArray();
             foreach ($cek as $key => $val) {
                $total += $val['qty'] * $val['harga'];                
            }
            
            if ($total > 0)
                $succes = true;
            else
                $succes = false;
        } else {
            $succes = false;
            $msg = "Error!";
        }

        $this->getResponse()->setBody(Zend_Json::encode(array(
                    "success" => $succes,
                    "msg" => $msg
        )));
    }

}
