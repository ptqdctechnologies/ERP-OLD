<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/27/11
 * Time: 9:55 AM
 * To change this template use File | Settings | File Templates.
 */
class Logistic_LogisticbarangController extends Zend_Controller_Action {

    private $material;
    private $bisnis;
    private $subbisnis;
    private $work;
    private $typebarang;
    private $brand;
    private $uom;
    private $boq3d;
    private $kboq3d;
    private $praboq3d;
    private $prd;
    private $pod;
    private $ripd;
    private $arfd;
    private $asfd;
    private $asfdcancel;
    private $remd;
    private $bpvd;
    private $pay_arfd;
    private $pay_rpid;
    private $pay_remd;
    private $dnd;
    private $pay_dnd;
    private $whod;
    private $pointd;
    private $whbringbackd;
    private $whreturnd;
    private $whsupplierd;
    private $afed;
    private $afesaving;
    private $dboq;
    private $piecemeal;
    private $praohpd;
    private $sald;
    private $session;
    private $db;
    private $FINANCE;
    private $workflow;
    private $const;
    private $workflowTrans;

    public function init() {
        $this->material = new Default_Models_MasterBarang();
        $this->bisnis = new Logistic_Model_MasterBisnis();
        $this->subbisnis = new Logistic_Model_MasterSubBisnis();
        $this->work = new Logistic_Model_MasterWork();
        $this->typebarang = new Logistic_Model_MasterTypeBarang();
        $this->brand = new Logistic_Model_MasterBrand();
        $this->uom = new Logistic_Model_LogisticSatuan();
        $this->boq3d = new Default_Models_MasterBoq3();
        $this->kboq3d = new Default_Models_MasterCboq3();
        $this->praboq3d = new ProjectManagement_Models_TemporaryBOQ3();
        $this->prd = new Default_Models_ProcurementRequest();
        $this->pod = new Default_Models_PurchaseOrder();
        $this->ripd = new Default_Models_RequestPaymentInvoice();
        $this->arfd = new Default_Models_AdvanceRequestFormD();
        $this->asfd = new Default_Models_AdvanceSettlementForm();
        $this->asfdcancel = new Default_Models_AdvanceSettlementFormCancel();
        $this->remd = new Default_Models_ReimbursD();
        $this->bpvd = new Finance_Models_BankPaymentVoucherD();
        $this->pay_arfd = new Finance_Models_PaymentARFD();
        $this->pay_rpid = new Finance_Models_PaymentRPID();
        $this->pay_remd = new Finance_Models_PaymentReimbursD();
        $this->dnd = new Finance_Models_NDreimbursDetail();
        $this->pay_dnd = new Finance_Models_paymentNDreimbursD();
        $this->whod = new Logistic_Models_LogisticDod();
        $this->pointd = new Logistic_Models_LogisticDord();
        $this->whbringbackd = new Logistic_Models_LogisticMaterialCancel();
        $this->whreturnd = new Logistic_Models_LogisticMaterialReturn();
        $this->whsupplierd = new Logistic_Models_LogisticInputSupplier();
        $this->afed = new ProjectManagement_Models_AFE();
        $this->afesaving = new ProjectManagement_Models_AFESaving();
        $this->piecemeal = new Default_Models_PieceMeal();
        $this->dboq = new Default_Models_Dboq();
        $this->praohpd = new HumanResource_Models_TemporaryOHP();
        $this->sald = new HumanResource_Models_SalaryD();
        $this->session = new Zend_Session_Namespace('login');
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
        $this->workflowTrans = new Admin_Models_Workflowtrans();

        $this->workflow = $this->_helper->getHelper('workflow');

        $models = array(
            "MasterMarkup"
        );

        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function menuAction() {
        
    }

    public function appupdatebarangAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/UHB';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
        if ($docs) {
            $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
            if ($user || $show) {
                $id = $docs['workflow_trans_id'];
                $approve = $docs['item_id'];
                $statApprove = $docs['approve'];
                if ($statApprove == $this->const['DOCUMENT_REJECT'])
                    $this->view->reject = true;
                
                $userApp = $this->workflow->getAllApprovalGeneric($approve);
                    $this->view->user_approval = $userApp;

                $temporarymodel = new Logistic_Models_LogisticTemporaryBarang();
                $data = $temporarymodel->fetchRow("trano = '$approve'");
                if ($data->toArray()) {
                    $data = $data->toArray();
//                    var_dump($data);die;
                    $this->view->kode_brg = $data['kode_brg'];
                    $this->view->nama_brg = $data['nama_brg'];
                    $this->view->harga = $data['harga_baru'];
                }
                $this->view->trano = $approve;
                $this->view->uid = $this->session->userName;
                $this->view->userID = $this->session->idUser;
                $this->view->docsID = $id;
            }
        }
    }

    public function viewmaterialAction() {
        $this->view->new_project = ($this->_getParam("new_project") == 'true') ? true : false;
        $this->view->disable_edit = ($this->_getParam("disable_edit") == 'true') ? true : false;
    }

    public function insertmaterialAction() {
        $this->view->new_project = ($this->_getParam("new_project") == 'true') ? true : false;
    }

    public function getbisnisAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->bisnis->fetchAll(null, array($sort . " " . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->bisnis->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getsubbisnisAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->subbisnis->fetchAll(null, array($sort . " " . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->subbisnis->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getworkAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project)
            $where = "is_old = 'N'";
//        else
//            $where = "is_old = 'Y'";
        $data['data'] = $this->work->fetchAll($where, array($sort . " " . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->work->fetchAll($where)->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function gettypebarangAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project)
            $where = "is_old = 'N'";
//        else
//            $where = "is_old = 'Y'";
        $data['data'] = $this->typebarang->fetchAll($where, array($sort . " " . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->typebarang->fetchAll($where)->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getbrandAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $query = $this->getRequest()->getParam('query');

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else {
            $search = "$option LIKE '%$textsearch%'";
        }
        if ($query != '') {
            $search = "mrk_nama like '%$query%' ";
        }


        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project)
            $where = "is_old = 'N'";
//        else
//            $where = "is_old = 'Y'";

        if ($search)
            $search = $search . " AND " . $where;
        else
            $search = $where;

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->brand->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->brand->fetchAll($search)->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getuomAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else {
            $search = "$option LIKE '%$textsearch%'";
        }

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project)
            $where = "is_old = 'N'";
//        else
//            $where = "is_old = 'Y'";

        if ($search)
            $search = $search . " AND " . $where;
        else
            $search = $where;

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->uom->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->uom->fetchAll($search)->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertmaterialAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();

//        $nama_bisnis = $this->getRequest()->getParam('nama_bisnis');
//        $kode_bisnis = $this->getRequest()->getParam('kode_bisnis');
//        $nama_subbisnis = $this->getRequest()->getParam('nama_subbisnis');
//        $kode_subbisnis = $this->getRequest()->getParam('kode_subbisnis');
        $nama_work = $this->getRequest()->getParam('nama_work');
        $kode_work = $this->getRequest()->getParam('kode_work');
        $nama_type = $this->getRequest()->getParam('nama_type');
        $kode_type = $this->getRequest()->getParam('kode_type');
        $nama_brand = $this->getRequest()->getParam('nama_brand');
        $kode_brand = $this->getRequest()->getParam('kode_brand');
        $nama_material = $this->getRequest()->getParam('nama_material');
        $uom = $this->getRequest()->getParam('uom');
        $piece_meal = $this->getRequest()->getParam('piece_meal');
        $valuta = $this->getRequest()->getParam('val_kode');
        $rateidr = $this->getRequest()->getParam('rateidr');

        if ($piece_meal == 'YES')
            $piece_meal = 'Y';
        else
            $piece_meal = 'N';
        $piece_meal_price = $this->getRequest()->getParam('piece_meal_price');
        $price = $this->getRequest()->getParam('price');

        $nama_material = preg_replace("~[\r\nØ]~", "", $nama_material);

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;

        if ($new_project) {

            $sql = "SELECT max(kode_brg)as id FROM master_barang_project_2009 where kode_brg LIKE '$kode_work$kode_type%%%%-$kode_brand' and is_old='N'";

            $lastid = $this->db->query($sql);
            $lastid = $lastid->fetch();
            $counter = substr($lastid['id'], 2, 4);

            if ($lastid['id'] !== null) {
                $kode_barang = $kode_work . $kode_type . $counter + 1 . "-" . $kode_brand;
            } else {
                $kode_barang = $kode_work . $kode_type . '0001' . "-" . $kode_brand;
            }

//          Cek apakah sudah ada di database...
            $select = $this->db->select()
                    ->from(array($this->material->__name()))
                    ->where("kode_brg = ?", $kode_barang)
                    ->where("is_old = ?", 'N');

            $cek = $this->db->fetchRow($select);
            if ($cek) {
                $return = array("success" => false, "msg" => "Material Code exist : <b>" . $kode_produk . "</b>");
            } else {

                $insertmaterial = array(
                    "kode_brg" => $kode_barang,
                    "nama_brg" => $nama_material,
                    "harga_borong" => floatval($price),
                    "sat_kode" => $uom,
                    "master_merk" => $nama_brand,
                    "mrk_kode" => $kode_brand,
                    "uid" => $this->session->userName,
                    "date" => date("Y-m-d H:i:s"),
                    "stspmeal" => $piece_meal,
                    "rateidr" => $rateidr,
                    "val_kode" => $valuta,
//                    "is_old" => ($new_project) ? 'N' : 'Y'
                    "is_old" => 'N' 
                );

                $this->material->insert($insertmaterial);
                $activityHead['master_barang_project_2009'][0]=$insertmaterial;
                
                $return = array("success" => true, "kode_brg" => $kode_barang);
            }
        } else {
            $sql = "SELECT * FROM ( SELECT * FROM master_barang_project_2009 WHERE LENGTH(kode_brg) = 8) a ORDER BY SUBSTRING(a.kode_brg,3) DESC LIMIT 1";
            $fetch = $this->db->query($sql);

            $fetch = $fetch->fetch();

            if ($fetch) {
                $kodelama = $fetch['kode_brg'];
                $lastUrut = intval(substr($kodelama, 2)) + 1;
            } else {
                $lastUrut = 1;
            }


            if ($lastUrut < 10) {
                $urut = '00000' . $lastUrut;
            } else if ($lastUrut < 100) {
                $urut = '0000' . $lastUrut;
            } else if ($lastUrut < 1000) {
                $urut = '000' . $lastUrut;
            } else if ($lastUrut < 10000) {
                $urut = '00' . $lastUrut;
            } else if ($lastUrut < 100000) {
                $urut = '0' . $lastUrut;
            } else {
                $urut = $lastUrut;
            }

//        $updateurut = array(
//            "urut" => $lastUrut
//        );
//        $this->typebarang->update($updateurut,"kode_type = '$kode_type'");

            $kode_barang = $kode_work . $kode_type . $urut;

            $insertmaterial = array(
                "kode_brg" => $kode_barang,
                "nama_brg" => $nama_material,
                "harga_borong" => floatval($price),
                "sat_kode" => $uom,
                "master_merk" => $nama_brand,
                "mrk_kode" => $kode_brand,
                "uid" => $this->session->userName,
                "date" => date("Y-m-d H:i:s"),
                "stspmeal" => $piece_meal,
                "val_kode" => $valuta,
                "rateidr" => $rateidr,
//                "is_old" => ($new_project) ? 'N' : 'Y'
                "is_old" => 'N'
            );

            $this->material->insert($insertmaterial);
            $activityHead['master_barang_project_2009'][0]=$insertmaterial;
            $return = array("success" => true, "kode_brg" => $kode_barang);
        }



        $json = Zend_Json::encode($return);
        
        if ($new_project) {

           $activityLog = array(
            "menu_name" => "Create New Material(New Project)",
            "trano" => $kode_barang,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
    
        } else {
                 $activityLog = array(
            "menu_name" => "Create New Material",
            "trano" => $kode_barang,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        }
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertbisnisAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        $bisnis_code = $this->getRequest()->getParam('bisnis_code');
        $bisnis_name = $this->getRequest()->getParam('bisnis_name');

        $cekcode = $this->bisnis->fetchRow("kode_bisnis = '$bisnis_code' ");
        $cekname = $this->bisnis->fetchRow("nama_bisnis = '$bisnis_name' ");

        if ($cekcode) {
            $return = array("success" => false, "pesan" => "Sorry, Bisnis Code exists");
        } else if ($cekname) {
            $return = array("success" => false, "pesan" => "Sorry, Bisnis Name exists");
        } else {

            $new_project = ($this->_getParam("new_project") == 'true') ? true : false;

            $insert = array(
                "kode_bisnis" => $bisnis_code,
                "nama_bisnis" => $bisnis_name
            );

            $this->bisnis->insert($insert);

            //        var_dump($bisnis_code,$bisnis_name);die;

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertsubbisnisAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $subbisnis_code = $this->getRequest()->getParam('sub_bisnis_code');
        $subbisnis_name = $this->getRequest()->getParam('sub_bisnis_name');

        $cekcode = $this->subbisnis->fetchRow("kode_bisnis_sub = '$subbisnis_code' ");
        $cekname = $this->subbisnis->fetchRow("nama_bisnis = '$subbisnis_name' ");

        if ($cekcode) {
            $return = array("success" => false, "pesan" => "Sorry, Sub Bisnis Code exists");
        } else if ($cekname) {
            $return = array("success" => false, "pesan" => "Sorry, Sub Bisnis Name exists");
        } else {

            $insert = array(
                "kode_bisnis_sub" => $subbisnis_code,
                "nama_bisnis" => $subbisnis_name
            );

            $this->subbisnis->insert($insert);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertworkAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();
        
        $work_code = $this->getRequest()->getParam('work_code');
        $work_name = $this->getRequest()->getParam('work_name');

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project)
            $where = " AND is_old = 'N'";
//        else
//            $where = " AND is_old = 'Y'";
        $cekcode = $this->work->fetchRow("kode_pekerjaan = '$work_code'" . $where);
        $cekname = $this->work->fetchRow("nama_pekerjaan = '$work_name'" . $where);

        if ($cekcode) {
            $return = array("success" => false, "pesan" => "Sorry, Work Code exists");
        } else if ($cekname) {
            $return = array("success" => false, "pesan" => "Sorry, Work Name exists");
        } else {

            $insert = array(
                "kode_pekerjaan" => $work_code,
                "nama_pekerjaan" => $work_name,
                "is_old" => 'N'
//                "is_old" => ($new_project) ? 'N' : 'Y'
            );

            $this->work->insert($insert);
             $activityHead['master_pekerjaan'][0]=$insert;

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        
        if ($new_project){ 
        $activityLog = array(
            "menu_name" => "Create New Work List(New Project)",
            "trano" => $work_code,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        }else{
             $activityLog = array(
            "menu_name" => "Create New Work List",
            "trano" => $work_code,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        }
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinserttypematerialAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();

        $type_material_code = $this->getRequest()->getParam('type_material_code');
        $type_material_name = $this->getRequest()->getParam('type_material_name');

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project)
            $where = " AND is_old = 'N'";
//        else
//            $where = " AND is_old = 'Y'";
        $cekcode = $this->typebarang->fetchRow("kode_type = '$type_material_code'" . $where);
        $cekname = $this->typebarang->fetchRow("nama_type = '$type_material_name'" . $where);

        if ($cekcode) {
            $return = array("success" => false, "pesan" => "Sorry, Type Material Code exists");
        } else if ($cekname) {
            $return = array("success" => false, "pesan" => "Sorry, Type Material Name exists");
        } else {
            $insert = array(
                "kode_type" => $type_material_code,
                "nama_type" => $type_material_name,
//                "is_old" => ($new_project) ? 'N' : 'Y'
                "is_old" => 'N'
            );

            $this->typebarang->insert($insert);
             $activityHead['master_type_barang'][0]=$insert;

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        
          if ($new_project) {

           $activityLog = array(
            "menu_name" => "Create New Type Material List(New Project)",
            "trano" => $type_material_code,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
    
        } else {
                 $activityLog = array(
            "menu_name" => "Create New Type Material List",
            "trano" => $type_material_code,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        }
        
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

   public function doinsertbrandAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();
        
        $brand_code = $this->getRequest()->getParam('brand-code');
        $brand_name = $this->getRequest()->getParam('brand-name');

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project) {
            $where = " AND is_old = 'N'";
            $brand_code = $this->addEmptyChar($brand_code);
//        } else
//            $where = " AND is_old = 'Y'";
        $cekcode = $this->brand->fetchRow("mrk_kode = '$brand_code'" . $where);
        $cekname = $this->brand->fetchRow("mrk_nama = '$brand_name'" . $where);

        if ($cekcode) {
            $return = array("success" => false, "pesan" => "Sorry, Brand Code exists : <b>" . $brand_code . "</b>");
        } else if ($cekname) {
            $return = array("success" => false, "pesan" => "Sorry, Brand Name exists : <b>" . $brand_code . "</b>");
        } else {
            $insert = array(
                "mrk_kode" => $brand_code,
                "mrk_nama" => $brand_name,
//                "is_old" => ($new_project) ? 'N' : 'Y'
                "is_old" => 'N'
            );

            $this->brand->insert($insert);
              $activityHead['master_merk'][0]=$insert;

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        
          
        if ($new_project) {

           $activityLog = array(
            "menu_name" => "Create New Brand List(New Project)",
            "trano" => $brand_code,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
    
        } else {
                 $activityLog = array(
            "menu_name" => "Create New Brand List",
            "trano" => $brand_code,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        }
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editmaterialAction() {
        $kode_brg = $this->getRequest()->getParam('kode_brg');

        $materialdata = $this->material->fetchRow("kode_brg = '$kode_brg'");

//        var_dump($materialdata);die;

        $type = '';

//        $hargaborong = floatval($materialdata['harga_borong']);
//        if($hargaborong != 0.00)
//        {
//            $materialdata[0]['pm_status'] = 'YES';
//        }else{
//            $materialdata[0]['pm_status'] = 'NO';
//        }
//        var_dump($materialdata);die;

        if (strlen($materialdata['kode_brg']) == 8) {
            $kode = $materialdata['kode_brg'];
//            $kode_bisnis = substr($kode,0,1);
//            $kode_subbisnis = substr($kode,1,1);
            $kode_work = substr($kode, 0, 1);
            $kode_type = substr($kode, 1, 1);
//            $panjang = strlen($kode) - 8;
//            $kode_brand = substr($kode,8,$panjang);
//            $bisnisdata = $this->bisnis->fetchRow("kode_bisnis = $kode_bisnis");
//            $subbisnisdata = $this->subbisnis->fetchRow("kode_bisnis_sub = $kode_subbisnis");
            $workdata = $this->work->fetchRow("kode_pekerjaan = '$kode_work'");
            $typedata = $this->typebarang->fetchRow("kode_type = '$kode_type'");
            $branddata = $this->brand->fetchRow("mrk_kode = '$kode_brand'");

            $type = 'new';
        }

        $stspmeal = $materialdata['stspmeal'];
        if ($stspmeal == 'Y') {
            $stspmeal = 'YES';
        } else {
            $stspmeal = 'NO';
        }

        $brand = $materialdata['master_merk'];
        if ($brand == null || $brand == '""') {
            $brand = '';
        }

        $valuta = $materialdata['val_kode'];
        if ($valuta == null || $valuta == '""') {
            $valuta = '';
        }

        $this->view->material = $materialdata;
        $this->view->kode_brg = $kode_brg;
//        $this->view->bisnis = $bisnisdata;
//        $this->view->subbisnis = $subbisnisdata;
        $this->view->work = $workdata;
        $this->view->type = $typedata;
        $this->view->brand = $brand;
        $this->view->typematerial = $type;
        $this->view->stspmeal = $stspmeal;
        $this->view->price = $materialdata['harga_borong'];
        $this->view->val_kode = $valuta;

//        var_dump($bisnisdata);die;
    }

    public function doupdatematerialAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $nama_material = $this->getRequest()->getParam('nama_brg');
        $kode_brg = $this->getRequest()->getParam('kode_brg');
        $uom = $this->getRequest()->getParam('uom');
//        $hargaavg = $this->getRequest()->getParam('hargaavg');
        $rateidr = $this->getRequest()->getParam('rateidr');
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        
        $old = $this->material->fetchAll("kode_brg = '$kode_brg'")->toArray();
        $log['barang-detail-before'] = $old;

        $updatematerial = array(
            "nama_brg" => $nama_material,
            "sat_kode" => $uom,
//            "hargaavg" => $hargaavg,
            "uid" => $this->session->userName,
            "date" => date("Y-m-d H:i:s"),
            "rateidr" => $rateidr
        );

        $this->material->update($updatematerial, "kode_brg = '$kode_brg'");
         
        $new = $this->material->fetchAll("kode_brg = '$kode_brg'")->toArray();
        $log2['barang-detail-after'] = $new;

        $return = array("success" => true);

        //Log Transaction
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $kode_brg,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewbisnisAction() {
        
    }

    public function doupdatebisnisAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $nama_bisnis = $this->getRequest()->getParam('bisnis-name');
        $kode_bisnis = $this->getRequest()->getParam('bisnis-code');
        $id_bisnis = $this->getRequest()->getParam('id');

        $where = "(kode_bisnis = '$kode_bisnis' OR nama_bisnis = '$nama_bisnis') AND id != '$id_bisnis' ";

        $cek = $this->bisnis->fetchRow($where);

        if ($cek) {
            $return = array("success" => false, "pesan" => "Sorry, Bisnis Code or Bisnis Name exists");
        } else {
            $update = array(
                "kode_bisnis" => $kode_bisnis,
                "nama_bisnis" => $nama_bisnis
            );

            $this->bisnis->update($update, "id = '$id_bisnis'");

            $return = array("success" => true);
        }



//        var_dump($nama_bisnis,$kode_bisnis,$id_bisnis);die;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewsubbisnisAction() {
        
    }

    public function doupdatesubbisnisAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $nama_subbisnis = $this->getRequest()->getParam('subbisnis-name');
        $kode_subbisnis = $this->getRequest()->getParam('subbisnis-code');
        $id_subbisnis = $this->getRequest()->getParam('id');

        $where = "(kode_bisnis_sub = '$kode_subbisnis' OR nama_bisnis = '$nama_subbisnis') AND id != '$id_subbisnis' ";

        $cek = $this->subbisnis->fetchRow($where);

        if ($cek) {
            $return = array("success" => false, "pesan" => "Sorry,Sub Bisnis Code or Sub Bisnis Name exists");
        } else {
            $update = array(
                "kode_bisnis_sub" => $kode_subbisnis,
                "nama_bisnis" => $nama_subbisnis
            );

            $this->subbisnis->update($update, "id = '$id_subbisnis'");

            $return = array("success" => true);
        }



//        var_dump($nama_bisnis,$kode_bisnis,$id_bisnis);die;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewworkAction() {
        $this->view->new_project = ($this->_getParam("new_project") == 'true') ? true : false;
    }

   public function doupdateworkAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
 
        $nama_work = $this->getRequest()->getParam('work-name');
        $kode_work = $this->getRequest()->getParam('work-code');
        $id_work = $this->getRequest()->getParam('id');

        
         
        
        $where = "(kode_pekerjaan = '$kode_work' OR nama_pekerjaan = '$nama_work') AND id != '$id_work' ";
           
        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project)
            $where .= " AND is_old = 'N'";
//        else
//            $where .= " AND is_old = 'Y'";
      

        $cek = $this->work->fetchRow($where);

 
         
        if ($cek) {
            $return = array("success" => false, "pesan" => "Sorry,Work Code or Work Name exists");
        } else {
         // fetch data before
          $old = $this->work->fetchAll("id = '$id_work'")->toArray();
        $log['work-detail-before'] = $old;
            $update = array(
                "kode_pekerjaan" => $kode_work,
                "nama_pekerjaan" => $nama_work
            );

            $this->work->update($update, "id = '$id_work'");
           
           // fetch data after
          $new = $this->work->fetchAll("id = '$id_work'")->toArray();
          $log2['work-detail-after'] = $new;
            
            $return = array("success" => true);  
            //Log Transaction
            $logs = new Admin_Models_Logtransaction();
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array(
                "trano" => $id_work,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date('Y-m-d H:i:s'),
                "action" => "UPDATE",
                "data_before" => $jsonLog,
                "data_after" => $jsonLog2,
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $logs->insert($arrayLog);

            }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewtypematerialAction() {
        $this->view->new_project = ($this->_getParam("new_project") == 'true') ? true : false;
    }

   public function doupdatetypeAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        

        
        $nama_type = $this->getRequest()->getParam('type-name');
        $kode_type = $this->getRequest()->getParam('type-code');
        $id_type = $this->getRequest()->getParam('id');

        $where = "(kode_type = '$kode_type' OR nama_type = '$nama_type') AND id != '$id_type' ";

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project)
            $where .= " AND is_old = 'N'";
//        else
//            $where .= " AND is_old = 'Y'";
            
       
        $cek = $this->typebarang->fetchRow($where);

        if ($cek) {
            $return = array("success" => false, "pesan" => "Sorry,Type Material Code or Type Material Name exists");
        } else {
                       // fetch data before
          $old = $this->typebarang->fetchAll("id = '$id_type'")->toArray();
        $log['type-detail-before'] = $old;
            $update = array(
                "kode_type" => $kode_type,
                "nama_type" => $nama_type
            );

            $this->typebarang->update($update, "id = '$id_type'");
            
             // fetch data after
          $new = $this->typebarang->fetchAll("id = '$id_type'")->toArray();
        $log2['type-detail-after'] = $new;
        
            $return = array("success" => true);
        }
        
//Log Transaction
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $kode_type,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);
        
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewbrandAction() {
        $this->view->new_project = ($this->_getParam("new_project") == 'true') ? true : false;
    }

   public function doupdatebrandAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        

        $nama_brand = $this->getRequest()->getParam('brand-name');
        $kode_brand = $this->getRequest()->getParam('brand-code');
        $id_brand = $this->getRequest()->getParam('id');
      
        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
        if ($new_project)
            $kode_brand = $this->addEmptyChar($kode_brand);

        $where = "(mrk_kode = '$kode_brand' OR mrk_nama = '$nama_brand') AND id != '$id_brand' ";

//        if ($new_project) {
            $where .= " AND is_old = 'N'";
//        } else
//            $where .= " AND is_old = 'Y'";

       
        $cek = $this->brand->fetchRow($where);

        if ($cek) {
            $return = array("success" => false, "pesan" => "Sorry,Brand Code or Brand Name exists");
        } else {
                             // fetch data before
        $old = $this->brand->fetchAll("id = '$id_brand'")->toArray();
        $log['brand-detail-before'] = $old;
        
            $update = array(
                "mrk_kode" => $kode_brand,
                "mrk_nama" => $nama_brand
            );

            $this->brand->update($update, "id = '$id_brand'");
                 // fetch data after
          $new = $this->brand->fetchAll("id = '$id_brand'")->toArray();
          $log2['brand-detail-after'] = $new;
           
          $return = array("success" => true);
            
            //Log Transaction
            $logs = new Admin_Models_Logtransaction();
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array(
                "trano" => $id_brand,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date('Y-m-d H:i:s'),
                "action" => "UPDATE",
                "data_before" => $jsonLog,
                "data_after" => $jsonLog2,
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $logs->insert($arrayLog);
        }
       
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getmaterialAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $nama_brg = $this->getRequest()->getParam('nama_brg');

//        var_dump($nama_brg);die;
        $nama_brg = str_replace(' ', '%', $nama_brg);
        $search = "nama_brg like '%$nama_brg%'";

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
        if ($new_project)
            $where = "is_old = 'N'";
        else
            $where = "is_old = 'Y'";

        if ($search)
            $search = $search . " AND " . $where;
        else
            $search = $where;

        $data['data'] = $this->material->fetchAll($search)->toArray();

        $json = Zend_Json::encode($data['data']);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function ceklastpriceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $kodeBrg = $this->getRequest()->getParam("kode_brg");
        $hargaBrg = $this->getRequest()->getParam("harga");
        $markup = ($this->getRequest()->getParam("markup") == 'true' ? true : false);
        $discount = $this->getRequest()->getParam("discount");
        $markuplimit = 0;
        $markupPriceOver = 0;
        $markupPriceOverDisc = 0;

        $data = $this->FINANCE->MasterMarkup->getmarkup();
        if ($data)
            $markuplimit = $data[0]['markup_limit'];

        $cek = $this->material->fetchRow("kode_brg = '$kodeBrg'");
        if ($cek) {
            $hargaAsli = $cek['hargaavg'];
            if ($markuplimit != 0)
                $markupPriceOver = $hargaAsli - ($hargaAsli * ($markuplimit / 100));
            if ($discount != 0)
                $markupPriceOverDisc = $hargaAsli - ($hargaAsli * $discount);

            if ($markup && bccomp($hargaAsli, $hargaBrg) <= 0) {
                $data = array("allowed" => true);
            } else if (!$markup && bccomp($hargaBrg, $markupPriceOverDisc) >= 0) {
                $data = array("allowed" => true);
            } else if ($markuplimit != 0 && $markup && (bccomp($hargaBrg, $markupPriceOver, 2) >= 0 )) {
                $data = array("allowed" => true);
            } else
                $data = array("allowed" => false, "msg" => "The Price in PR is lower than Master Material! Please Edit your PR/Budget!");
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    private function addEmptyChar($kode = '', $prefix = true, $lenFill = 4, $fill = '0') {
        $num = $lenFill - strlen($kode);
        for ($i = 0; $i < $num; $i++) {
            if ($prefix)
                $kode .= $fill;
            else
                $kode = $fill . $kode;
        }

        return $kode;
    }

    public function cekAuthPriceChangerAction() {
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
                    if ($v['role_name'] == 'Finance and Logistic' || $v['role_name'] == 'Commercial and Procurement') {
                        $found = true;
                        break;
                    }
                }
            }

            $result['auth'] = $found;
            if ($result['auth'] == false) {
                $result['msg'] = 'Sorry, You dont have credential to Edit Price';
            }
        } else
            $result['msg'] = 'Username or Password is not valid';

        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateHargaBarangAction() {
        $this->_helper->viewRenderer->setNoRender();

        $kode_brg = $this->_getParam("kode_brg");
        $nama_brg = $this->_getParam("nama_brg");
        $harga = $this->_getParam("harga");
        $uid = QDC_User_Session::factory()->getCurrentUID();
        $tgl = date('Y-m-d H:i:s');
        $default_model = new Default_Models_MasterProject;
        $data['prj_nama'] = $default_model->getProjectName("ACF");

        $items = $data;
        $items["prj_kode"] = $data['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $useOverride = ($this->_getParam("useOverride") == 'true') ? true : false;

        $params = array(
            "workflowType" => "UHB",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => "ACF",
            "generic" => true,
            "revisi" => false,
            "returnException" => false,
            "useOverride" => $useOverride
        );
        $trano = $this->workflow->setWorkflowTransNew($params);


        $arrayInsert = array(
            "harga_baru" => $harga,
            "kode_brg" => $kode_brg,
            "nama_brg" => $nama_brg,
            "uid" => $uid,
            "tgl" => $tgl,
            "trano" => $trano
        );

        $tempbarang_model = new Logistic_Models_LogisticTemporaryBarang();

        $tempbarang_model->insert($arrayInsert);



        $result = Zend_Json::encode(array("success" => true, "number" => $trano));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
}
    
    public function forceUpdatePriceAction() {
         $this->_helper->viewRenderer->setNoRender();
         $trano = $this->_getParam("trano");
         $price = $this->_getParam("price");
        
       
            $model_temporary = new Logistic_Models_LogisticTemporaryBarang();

            $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();

            $old = $model_temporary->fetchAll("trano = '$trano'")->toArray();
            $log['barang-detail-before'] = $old;

            $updatematerial = array(
                "harga_baru" => $price,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date('Y-m-d H:i:s'),
            );

            $model_temporary->update($updatematerial, "trano = '$trano'");

            $new = $model_temporary->fetchAll("trano = '$trano'")->toArray();
            $log2['barang-detail-after'] = $new;

            $return = array("success" => true);

            //Log Transaction
            $logs = new Admin_Models_Logtransaction();
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array(
                "trano" => $trano,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date('Y-m-d H:i:s'),
                "action" => "UPDATE",
                "data_before" => $jsonLog,
                "data_after" => $jsonLog2,
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $logs->insert($arrayLog);
            
            $return['success'] = true;
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    
    }

}
