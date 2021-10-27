<?php

/**
 * Created by JetBrains PhpStorm.
 * User: bherly
 * Date: 03/28/12
 * Time: 9:32 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_JurnalController extends Zend_Controller_Action {

    private $FINANCE;
    private $DEFAULT;
    private $db;

    public function init() {
        $this->db = Zend_Registry::get('db');
        $models = array(
            "MasterCoa",
            "MasterCoaBank",
            "AccountingCloseAR",
            "AccountingCloseAP",
            "AccountingJurnalBank",
            "AdjustingJournal",
            "BankPaymentVoucher",
            "AccountingInventoryIn",
            "AccountingInventoryOut",
            "AccountingInventoryReturn",
        );

        $this->FINANCE = QDC_Model_Finance::init($models);

        $models = array(
            "MasterPt"
        );

        $this->DEFAULT = QDC_Model_Default::init($models);
    }

    public function getjurnalarAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $ref_number = $this->getRequest()->getParam("ref_number");

        $coaAR = $this->FINANCE->AccountingCloseAR->getjurnalar($trano, $ref_number);

        $return = array("success" => true, "data" => $coaAR);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getjurnalbankAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $ref_number = $this->getRequest()->getParam("ref_number");
        $type = $this->getRequest()->getParam("type");

        $coaAR = $this->FINANCE->AccountingJurnalBank->getjurnalbank($trano, $ref_number, $type);

        $return = array("success" => true, "data" => $coaAR);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function cekJurnalAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $jurnal = $this->getRequest()->getParam("jurnal");

        try {
            $jurnal = Zend_Json::decode($jurnal);
        } catch (Zend_Json_Exception $e) {
            $err = $e->getMessage();
            if ($err == 'Decoding failed: Syntax error')
                $msg = "JSON Journal not valid";
            else
                $msg = "JSON Error, $err";

            echo Zend_Json::encode(array(
                "success" => false,
                "msg" => $msg
            ));
            exit(1);
        }

        //data validation
        $result = QDC_Finance_Jurnal::factory(array("jurnal" => $jurnal, "json" => true))->cekBalance();
        if ($result) {
            $err = array(
                "success" => true
            );
            echo Zend_Json::encode($err);
        }
    }

    public function saveJurnalAsFileAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $json = $this->getRequest()->getParam("json");

        if ($json) {
            $result = QDC_Adapter_File::factory()->makeFileFromJson($json);
        } else {
            $result = array(
                "success" => false,
                "msg" => "No Data provided"
            );
        }
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function loadJurnalFromFileAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $file = $this->getRequest()->getParam("filename");

        if ($file) {
            $isi = QDC_Adapter_File::factory()->read(Zend_Registry::get('uploadPath') . 'files/' . $file);
            if ($isi) {
                $result = array(
                    "success" => true,
                    "data" => $isi
                );
            }
        } else {
            $result = array(
                "success" => false,
                "msg" => "No Data provided"
            );
        }
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function printApAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->_getParam("trano");
        $apNumber = $this->_getParam("ap_number");
        $useBank = ($this->_getParam("jurnal_bank") == '') ? false : true;
        $type = ($this->_getParam("type")) ? $this->_getParam("type") : 'pdf';

        if ($trano) {
            $ap = $this->FINANCE->AccountingCloseAP->fetchAll("trano = '$trano' AND deleted=0");
            if ($ap)
                $ap = $ap->toArray();
            else {
                echo "No Data Found";
                die;
            }

            $bank = $this->FINANCE->AccountingJurnalBank->fetchAll("trano = '$trano' OR voc_trano = '$trano' AND deleted=0");
            if ($bank) {
                $bank = $bank->toArray();
            }

            $bpv = $this->FINANCE->BankPaymentVoucher->fetchAll("trano = '$trano' AND deleted=0");
            if ($bpv) {
                $bpv = $bpv->toArray();
                foreach ($bpv as $k => $v) {
                    $tranoPPN = $v['trano_ppn'];
                    if ($tranoPPN != '') {
                        $apPPN = $this->FINANCE->AccountingCloseAP->fetchAll("trano = '$tranoPPN' AND deleted=0");
                        if ($apPPN) {
                            $apPPN = $apPPN->toArray();
                            $ap = QDC_Common_Array::factory()->merge(array($ap, $apPPN));
                        }
                        $bankPPN = $this->FINANCE->AccountingJurnalBank->fetchAll("(trano = '$tranoPPN' OR voc_trano = '$tranoPPN') AND deleted=0");
                        if ($bankPPN) {
                            $bankPPN = $bankPPN->toArray();
                            if ($bank)
                                $bank = QDC_Common_Array::factory()->merge(array($bank, $bankPPN));
                            else
                                $bank = $bankPPN;
                        }
                    }
                    $tranoWHT = $v['trano_wht'];
                    if ($tranoWHT != '') {
                        $apWHT = $this->FINANCE->AccountingCloseAP->fetchAll("trano = '$tranoWHT' AND deleted=0");
                        if ($apWHT) {
                            $apWHT = $apWHT->toArray();
                            $ap = QDC_Common_Array::factory()->merge(array($ap, $apWHT));
                        }
                        $bankWHT = $this->FINANCE->AccountingJurnalBank->fetchAll("(trano = '$tranoWHT' OR voc_trano = '$tranoWHT') AND deleted=0");
                        if ($bankWHT) {
                            $bankWHT = $bankWHT->toArray();
                            if ($bank)
                                $bank = QDC_Common_Array::factory()->merge(array($bank, $bankWHT));
                            else
                                $bank = $bankWHT;
                        }
                    }
                }
            }
        }
        if ($apNumber) {
            $ap = $this->FINANCE->AccountingCloseAP->fetchAll("ref_number_accounting = '$apNumber' AND deleted=0");
            if ($ap)
                $ap = $ap->toArray();
            else {
                echo "No Data Found";
                die;
            }
            $trano = $apNumber;
        }

        $arrayData = array();

        foreach ($ap as $k => $v) {
            $arrayData[] = array(
                "trano" => ($v['ref_number_accounting'] == '') ? $v['trano'] : $v['ref_number_accounting'],
                "tgl" => date("d M Y", strtotime($v['tgl'])),
                "debit" => floatval($v['debit']),
                "credit" => floatval($v['credit']),
                "coa_kode" => $v['coa_kode'],
                "coa_nama" => $v['coa_nama'],
                "job_number" => $v['job_number'],
                "ref_number" => $v['ref_number']
            );
        }


        if ($bank && $useBank) {
            foreach ($bank as $k => $v) {
                $arrayData[] = array(
                    "trano" => $v['trano'],
                    "tgl" => date("d M Y", strtotime($v['tgl'])),
                    "debit" => floatval($v['debit']),
                    "credit" => floatval($v['credit']),
                    "coa_kode" => $v['coa_kode'],
                    "coa_nama" => $v['coa_nama'],
                    "job_number" => $v['job_number'],
                    "ref_number" => $v['ref_number']
                );
            }
        }

        $signature = $this->_helper->getHelper('token')->generateDocumentSignature();

        $pt = $this->DEFAULT->MasterPt->fetchRow()->toArray();
        $params = array(
            "nama" => $pt['nama'],
            "alamat1" => $pt['alamat1'],
            "alamat2" => $pt['alamat2'],
            "title" => "AP Journal",
            "sub_title" => $trano, //. ($bpvPPN != '') ? " + Tax" : '',
            "date" => date("d M Y"),
            "time" => date("H:i:s A"),
            "signature" => $signature
        );

        QDC_Jasper_Report::factory(
                array(
                    "reportType" => $type,
                    "arrayData" => $arrayData,
                    "arrayParams" => $params,
                    "fileName" => "jurnal.jrxml",
                    "outputName" => 'AP Journal ' . $trano,
                    "dataSource" => 'NoDataSource'
                )
        )->generate();
    }
    
    public function cekJurnalApAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");

        $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingCloseAP->__name()))
                ->where("trano = '$trano'");               
        $jurnal = $this->db->fetchAll($select);
       
         if ($jurnal) {
            $result = array(
                "success" => true
            );
        } else
            $result = array(
                "success" => false
            );
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function cekRefnumberApAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");

        $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingCloseAP->__name()))
                ->where("trano = '$trano'")
                ->where("ref_number_accounting IS NOT NULL")
                ->where("ref_number_accounting != ''");
        $ap = $this->db->fetchAll($select);
        if ($ap) {
            $result = array(
                "success" => true
            );
        } else
            $result = array(
                "success" => false
            );

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function updateApJournalRefnumberAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $refNumber = $this->getRequest()->getParam("ref_number");
        $refNumberAcc = $this->getRequest()->getParam("ref_number_accounting");

        $cek = $this->FINANCE->AccountingCloseAP->fetchAll("ref_number_accounting = '$refNumberAcc' ")->toArray();

        if (!$cek) {
            $ap = $this->FINANCE->AccountingCloseAP->fetchAll("trano = '$trano' AND ref_number = '$refNumber'")->toArray();
            
            if ($ap) {

                $arrayInsert = array(
                    "ref_number_accounting" => $refNumberAcc,
                    "tgl_ref_number_accounting" => date("Y-m-d H:i:s")
                );

                $this->FINANCE->AccountingCloseAP->update($arrayInsert, "trano = '$trano' AND ref_number = '$refNumber'");

                $bpv = $this->FINANCE->BankPaymentVoucher->fetchRow("trano = '$trano'");
                if ($bpv) {
                    if ($bpv['trano_ppn'] != '') {
                        $tranoPPN = $bpv['trano_ppn'];
                        $this->FINANCE->AccountingCloseAP->update($arrayInsert, "trano = '$tranoPPN' AND ref_number = '$refNumber'");
                    }
                }

                $result = array(
                    "success" => true
                );
            } else
                $result = array(
                    "success" => false,
                    "msg" => "AP Journal not found"
                );
        } else
            $result = array(
                "success" => false,
                "msg" => "AP number already exists. Please use another AP number!"
            );



        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function generateApNumberAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $counter = new Default_Models_MasterCounter();
        $tranoAP = $counter->setNewTrans('AP');

        if ($tranoAP) {
            $result = array(
                "success" => true,
                "trano" => $tranoAP
            );
        } else
            $result = array(
                "success" => false,
            );

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function getTranoJurnalAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $ref_number = $this->getRequest()->getParam("ref_number");
        $myob_ref_number = $this->getRequest()->getParam("myob_ref_number");
        $jenis = $this->getRequest()->getParam("type");
        $jenisJurnal = ($this->getRequest()->getParam("jurnal_type") != '') ? Zend_Json::decode($this->getRequest()->getParam("jurnal_type")) : array();

        $offset = ($_POST["start"] != '') ? $_POST["start"] : 0;
        $limit = ($_POST["limit"] != '') ? $_POST["limit"] : 30;

        $jurnal = array();
        if (in_array($jenis, array('JS', 'SJ', 'ADJ', 'JV', 'ACJ'))) {
            $select = $this->db->select()
                    ->from(array($this->FINANCE->AdjustingJournal->__name()), array(
                        new Zend_Db_Expr("SQL_CALC_FOUND_ROWS trano"),
                        "ref_number",
                        "ref_number_myob",
                        "tgl",
                        "debit" => new Zend_Db_Expr("SUM(debit)"),
                        "credit" => new Zend_Db_Expr("SUM(credit)"),
                    ))
                    ->where("type  = '$jenis'")
                    ->group(array("trano", "ref_number"))
                    ->order(array("ref_number ASC"))
                    ->limit($limit, $offset);

            if ($trano)
                $select = $select->where("trano LIKE '%$trano%'");
            if ($ref_number)
                $select = $select->where("ref_number LIKE '%$ref_number%'");
            if ($myob_ref_number)
                $select = $select->where("myob_ref_number LIKE '%$myob_ref_number%'");

            $jurnal = $this->db->fetchAll($select);
            $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
        }
        else {
            $where = array();
            if ($trano)
                $where[] = "trano LIKE '%$trano%'";
            if ($ref_number)
                $where[] = "ref_number LIKE '%$ref_number%'";

            if (count($where) > 0)
                $where = implode(" AND ", $where);

            $result = QDC_Finance_Jurnal::factory()->getAllJurnalWithLimit($where, $offset, $limit, 'tgl DESC', 'trano,ref_number', $jenisJurnal,true);
           
            $jurnal = $result['jurnal'];
            $count = $result['count'];
        }

        $return = array("success" => true, "data" => $jurnal, "count" => $count);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function cekAuthDateChangerAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $username = $this->getRequest()->getParam("username");
        $password = $this->getRequest()->getParam("password");

        $auth = Zend_Auth::getInstance();

        $config = new Zend_Config_Ini('../application/configs/application.ini', getenv('APPLICATION_ENV'));
        $option = $config->ldap->toArray();

        $adapter = new Zend_Auth_Adapter_Ldap($option, $username, $password);

        $authResult = $auth->authenticate($adapter);

        $found = false;
        $result['success'] = false;
        if ($authResult->isValid()) {
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

            $result['auth'] = $found;
            if ($result['auth'] == false) {
                $result['msg'] = 'Sorry, You dont have credential to Edit Transaction Date';
            }
        } else
            $result['msg'] = 'Username or Password is not valid';

        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getInventoryJournalTranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $ref_number = $this->getRequest()->getParam("ref_number");
        $jenis = $this->getRequest()->getParam("type");
        $order = $this->getRequest()->getParam("order_by");
        $jenisJurnal = ($this->getRequest()->getParam("jurnal_type") != '') ? Zend_Json::decode($this->getRequest()->getParam("jurnal_type")) : '';

        $offset = ($_POST["start"] != '') ? $_POST["start"] : 0;
        $limit = ($_POST["limit"] != '') ? $_POST["limit"] : 30;

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS `inventory_jurnal` ;
            CREATE TEMPORARY TABLE `inventory_jurnal` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `trano` varchar(255) NULL,
                `tgl` DATETIME NULL,
                `ref_number` varchar(255) NULL,
                `debit` DECIMAL(22,2) NULL,
                `credit` DECIMAL(22,2) NULL,
                `jenis_jurnal` VARCHAR(50) NULL,
                PRIMARY KEY (`id`)
            );
        ";
        $this->db->query($sql);

        $sql = "
            ALTER TABLE `inventory_jurnal`
            ADD INDEX `idx1`(`trano`),
            ADD INDEX `idx5`(`ref_number`),
            ADD INDEX `idx6`(`tgl`),
            ADD INDEX `idx7`(`jenis_jurnal`);
        ";
        $this->db->query($sql);

        foreach ($jenisJurnal as $k => $v) {

            switch ($v) {
                case 'inventory_in':
                    $select = $this->db->select()
                            ->from(array($this->FINANCE->AccountingInventoryIn->__name()), array(
                        "id" => new Zend_Db_Expr("null"),
                        "trano",
                        "tgl",
                        "ref_number",
                        "debit" => "SUM(debit)",
                        "credit" => "SUM(credit)",
                        "jenis_jurnal" => new Zend_Db_Expr("'inventory_in'")
                    ));
                    break;
                case 'inventory_out':
                    $select = $this->db->select()
                            ->from(array($this->FINANCE->AccountingInventoryOut->__name()), array(
                        "id" => new Zend_Db_Expr("null"),
                        "trano",
                        "tgl",
                        "ref_number",
                        "debit" => "SUM(debit)",
                        "credit" => "SUM(credit)",
                        "jenis_jurnal" => new Zend_Db_Expr("'inventory_out'")
                    ));
                    break;
                case 'inventory_return':
                    $select = $this->db->select()
                            ->from(array($this->FINANCE->AccountingInventoryReturn->__name()), array(
                        "id" => new Zend_Db_Expr("null"),
                        "trano",
                        "tgl",
                        "ref_number",
                        "debit" => "SUM(debit)",
                        "credit" => "SUM(credit)",
                        "jenis_jurnal" => new Zend_Db_Expr("'inventory_return'")
                    ));
                    break;
            }

            $select = $select->group(array("trano", "ref_number"));
            $sql = "INSERT INTO inventory_jurnal (" . (STRING) $select . ")";
            $this->db->query($sql);
        }

        $select = $this->db->select()
                ->from(array("inventory_jurnal"), array(
            new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
        ));

        if ($trano)
            $select = $select->where("trano LIKE '%$trano%'");
        if ($ref_number)
            $select = $select->where("ref_number LIKE '%$ref_number%'");

        if ($order)
            $select = $select->order(array($order));

        $select = $select->limit($limit, $offset);

        $jurnal = $this->db->fetchAll($select);
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $return = array("success" => true, "data" => $jurnal, "count" => $count);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getArJournalTranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $ref_number = $this->getRequest()->getParam("ref_number");
        $jenis = $this->getRequest()->getParam("type");
        $order = $this->getRequest()->getParam("order_by");
        $jenisJurnal = ($this->getRequest()->getParam("jurnal_type") != '') ? Zend_Json::decode($this->getRequest()->getParam("jurnal_type")) : '';

        $offset = ($_POST["start"] != '') ? $_POST["start"] : 0;
        $limit = ($_POST["limit"] != '') ? $_POST["limit"] : 30;

        $select = $this->db->select()
                ->from(array("accounting_close_ar"), array(
            "trano",
            "ref_number",
            "tgl",
            "debit" => "SUM(debit)",
            "credit" => "SUM(credit)"
        ));

        if ($jenisJurnal) {
            $jenisJurnal = $jenisJurnal[0];
            switch ($jenisJurnal) {
                case 'AR-INV':
                case 'DEBITNOTE':
                case 'CANCEL-INV':
                    $select = $select->where("type=?", $jenisJurnal);
                    break;
                case 'PAYMENT-AR-INV':
                    $select = $this->db->select()
                            ->from(array("accounting_jurnal_bank"), array(
                                "trano",
                                "ref_number",
                                "tgl",
                                "debit" => "SUM(debit)",
                                "credit" => "SUM(credit)"
                            ))
                            ->where("type='AR-INV' AND deleted=0");
                    break;
            }
        }

        if ($trano)
            $select = $select->where("trano LIKE '%$trano%'");
        if ($ref_number)
            $select = $select->where("ref_number LIKE '%$ref_number%'");

        $select = $select->group(array("trano"));

        $sub = clone $select;
        $select = $this->db->select()
                ->from(array("a" => $sub), array(
            new Zend_Db_Expr("SQL_CALC_FOUND_ROWS a.*")
        ));
        $select = $select->order(array($order));
        $select = $select->limit($limit, $offset);

        $jurnal = $this->db->fetchAll($select);
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $return = array("success" => true, "data" => $jurnal, "count" => $count);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getJurnalApTranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam("trano");
        $ref_number = $this->getRequest()->getParam("ref_number");

        $coaAP= $this->FINANCE->AccountingCloseAP->getJurnalAp($trano, $ref_number);

        $tgl = date("d M Y",strtotime($coaAP[0]['tgl']));
        $APNumber = $coaAP[0]['ref_number_accounting'];
        $rateidr = $coaAP[0]['rateidr'];
        $return = array("success" => true, "data" => $coaAP, "tgl" => $tgl, "ap_number" => $APNumber, "rateidr" => $rateidr);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getJurnalBankTranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = str_replace('_', "/", $this->_getParam("trano"));
        $ref_number = $this->getRequest()->getParam("ref_number");

        $coaBank= $this->FINANCE->AccountingJurnalBank->getjurnalbank($trano, $ref_number);
        $masterBankCoa = new Finance_Models_MasterCoaBank();
        $paymentRPIModel = new Finance_Models_PaymentRPI();
        $dataPayment = $paymentRPIModel->fetchAll("trano = '$trano'");
        $newData = array();
        $paramJournal = array();
        if ($dataPayment) {

            $tranoType = substr($coaBank['0']['ref_number'], 0, 3);
            if ($tranoType == 'RPI') {
                $tglJurnal = $coaBank[0]['tgl'];
                $trano = $coaBank[0]['trano'];
                $ref_number = $coaBank[0]['ref_number'];
                $uid = $coaBank[0]['uid'];
                $sts_close = $coaBank[0]['sts_close'];
        $rateidr = $coaBank[0]['rateidr'];
                $type = $coaBank[0]['type'];
                $memo = $coaBank[0]['memo'];
                $voc_trano = $coaBank[0]['voc_trano'];

                /* Ambil data payment */
                $dataPayment = $dataPayment->toArray();
                $rpiNum = $dataPayment[0]['doc_trano'];
                $bankCode = substr($dataPayment['0']['trano'], 0, 3);
                $valuta_coabank_transaksi = $masterBankCoa->fetchRow("trano_type = '$bankCode'");

                $valuta = $dataPayment[0]['val_kode'];
                $ratePayment = $dataPayment[0]['rateidr'];
                /* ------------------ */

                /* Ambil data RPI */
                $rpiModel = new Default_Models_RequestPaymentInvoiceH();
                $dataRPI = $rpiModel->fetchRow("trano = '$rpiNum'")->toArray();

                $rateRPI = $dataRPI['rateidr'];
                $paramJournal['raterpi'] = $rateRPI;
                /* ------------------ */


                /* define master coa 
                 * define master coa bank
                 */
                $coaModel = new Default_Models_MasterCoa();
                $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                        array(
                            "type" => $bankCode,
                            "val_kode" => $valuta_coabank_transaksi['val_kode']
                        )
                );

                $paramJournal['bank_coa'] = $bank_coa;
                
                $rateBackup = 0;
                $coa_kode_bank = $valuta_coabank_transaksi['coa_kode'];
                foreach ($coaBank as $key => $val) {
                    if ($val['coa_kode'] == $bank_coa['data'][0]['coa_kode']) {
                        $rateBackup = $val['rateidr'];
                        break;
                    }
                }

                $ratePayment = ($ratePayment != 0 ? $ratePayment : $rateBackup);

                foreach ($dataPayment as $key => $val) {
                    /*
                     * ----------------------- jurnal COA khusus AP
                     */
                    if ($valuta != 'IDR') {
                        $coaAP = QDC_Finance_Coa::factory()->getCoaAPUSD();
                        $coa_kodeAP = $coaAP[0];
                        $coa_kodeAPEx = $coaAP[1];
                    } else {
                        $coaAP = QDC_Finance_Coa::factory()->getCoaAPIDR();
                        $coa_kodeAP = $coaAP[0];
                    }
                    $coa = $coaModel->fetchRow("coa_kode = '$coa_kodeAP'");
                    $paramJournal['coaAP'] = $coa['coa_kode'];
                    $paramJournal['coaAPName'] = $coa['coa_nama'];

                    //nilai payment original
                    $insertJurnal = array(
                        "trano" => $trano,
                        "voc_trano" => $voc_trano,
                        "ref_number" => $ref_number,
                        "prj_kode" => $val['prj_kode'],
                        "sit_kode" => $val['sit_kode'],
                        "tgl" => $tglJurnal,
                        "uid" => $uid,
                        "coa_kode" => $coa['coa_kode'],
                        "coa_nama" => $coa['coa_nama'],
                        "debit" => $val['total_bayar'],
                        "credit" => 0,
                        "val_kode" => $val['val_kode'],
                        'rateidr' => $rateRPI,
                        "type" => $type,
                        "memo" => $memo,
                        "sts_close" => $sts_close
                    );
                    $newData[] = $insertJurnal;

                    //nilai payment exchange
                    if ($coa_kodeAPEx != '') {
                        $coa = $coaModel->fetchRow("coa_kode = '$coa_kodeAPEx'");
                        $paramJournal['coaAPEx'] = $coa['coa_kode'];
                        $paramJournal['coaAPExName'] = $coa['coa_nama'];
                        $insertJurnal = array(
                            "trano" => $trano,
                            "voc_trano" => $voc_trano,
                            "ref_number" => $ref_number,
                            "prj_kode" => $val['prj_kode'],
                            "sit_kode" => $val['sit_kode'],
                            "tgl" => $tglJurnal,
                            "uid" => $uid,
                            "coa_kode" => $coa['coa_kode'],
                            "coa_nama" => $coa['coa_nama'],
                            "debit" => (floatval($val['total_bayar']) * $rateRPI) - floatval($val['total_bayar']),
                            "credit" => 0,
                            "val_kode" => $val['val_kode'],
                            'rateidr' => $rateRPI,
                            "type" => $type,
                            "memo" => $memo,
                            "sts_close" => $sts_close
                        );
                        $newData[] = $insertJurnal;
                    }


                    /*
                     * ----------------------- jurnal COA khusus AP
                     */
                    $totalPayConversion = 0;
                    foreach ($bank_coa['data'] as $key2 => $val2) {
                        if ($valuta_coabank_transaksi['val_kode'] == 'IDR' && $valuta != 'IDR') {
                            $totalInsert = floatval($val['total_bayar'] * $ratePayment);
                            $totalPayConversion = $totalInsert;
                        } else {
                            $totalInsert = $val['total_bayar'];
                        }

                        if ($valuta != 'IDR' && $valuta_coabank_transaksi['val_kode'] != 'IDR') {
                            if (strpos($val2['coa_nama'], 'Exchange') !== false) {
                                $totalInsert = (floatval($val['total_bayar']) * $ratePayment) - $val['total_bayar'];
                                $totalPayConversion = $totalInsert + $val['total_bayar'];
                            }
                        }

                        $insertbank = array(
                            "trano" => $trano,
                            "voc_trano" => $voc_trano,
                            "ref_number" => $ref_number,
                            "prj_kode" => $val['prj_kode'],
                            "sit_kode" => $val['sit_kode'],
                            "tgl" => $tglJurnal,
                            "uid" => $uid,
                            "coa_kode" => $val2['coa_kode'],
                            "coa_nama" => $val2['coa_nama'],
                            "credit" => $totalInsert,
                            "debit" => 0,
                            "val_kode" => $valuta,
                            'rateidr' => $ratePayment,
                            "type" => $type,
                            "memo" => $memo,
                            "sts_close" => $sts_close
                        );
                        $newData[] = $insertbank;
                    }
                    if ($coa_kodeAPEx != '') {
                        $totalOri = $val['total_bayar'] * $rateRPI;
                        $totalPayment = ($totalPayConversion ? $totalPayConversion : ($totalInsert * $ratePayment));
                        $selisih = $totalPayment - $totalOri;

                        $coa = $coaModel->fetchRow("coa_nama like '%Exchange%Rate%Diff%'");

                        $paramJournal['coaGain'] = $coa['coa_kode'];
                        $paramJournal['coaGainName'] = $coa['coa_nama'];

                        if ($selisih > 0) {
                            $insertgain = array(
                                "trano" => $trano,
                                "voc_trano" => $voc_trano,
                                "ref_number" => $ref_number,
                                "prj_kode" => $val['prj_kode'],
                                "sit_kode" => $val['sit_kode'],
                                "tgl" => $tglJurnal,
                                "uid" => $uid,
                                "coa_kode" => $coa['coa_kode'],
                                "coa_nama" => $coa['coa_nama'],
                                "debit" => $selisih,
                                "credit" => 0,
                                "val_kode" => $valuta,
                                'rateidr' => $ratePayment,
                                "type" => $type,
                                "memo" => $memo,
                                "sts_close" => $sts_close
                            );
                        } else {
                            $selisih = -1 * $selisih;
                            $insertgain = array(
                                "trano" => $trano,
                                "voc_trano" => $voc_trano,
                                "ref_number" => $ref_number,
                                "prj_kode" => $val['prj_kode'],
                                "sit_kode" => $val['sit_kode'],
                                "tgl" => $tglJurnal,
                                "uid" => $uid,
                                "coa_kode" => $coa['coa_kode'],
                                "coa_nama" => $coa['coa_nama'],
                                "credit" => $selisih,
                                "debit" => 0,
                                "val_kode" => $valuta,
                                "rateidr" => $ratePayment,
                                "type" => $type,
                                "memo" => $memo,
                                "sts_close" => $sts_close
                            );
                        }
                        if ($selisih != 0)
                            $newData[] = $insertgain;
                    }
                }
            } else
                $newData = $coaBank;
        }


        $tgl = date("d M Y H:i:s", strtotime($coaBank[0]['tgl']));
//        $rateidr = $coaBank[0]['rateidr'];
        $return = array(
            "success" => true,
            "data" => $newData,
            "tgl" => $tgl,
            "rateidr" => $ratePayment,
            "payment" => $dataPayment,
            "paramJournal" => $paramJournal,
            "tranoType" => $tranoType
        );
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}

?>