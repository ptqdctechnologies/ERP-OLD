<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/27/11
 * Time: 9:55 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_LogisticbarangController extends Zend_Controller_Action
{
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

    public function init()
    {
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
    }

    public function menuAction ()
    {
        
    }

    public function viewmaterialAction ()
    {
        
    }

    public function insertmaterialAction ()
    {
        
    }

    public function getbisnisAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->bisnis->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->bisnis->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getsubbisnisAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->subbisnis->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->subbisnis->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getworkAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->work->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->work->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function gettypebarangAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->typebarang->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->typebarang->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getbrandAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch == "" || $textsearch == null)
        {
            $search = null;
        }
        else
        {
            $search = "$option LIKE '%$textsearch%'";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->brand->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->brand->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getuomAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch == "" || $textsearch == null)
        {
            $search = null;
        }
        else
        {
            $search = "$option LIKE '%$textsearch%'";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->uom->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->uom->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertmaterialAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

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

        if ($piece_meal == 'YES')
            $piece_meal = 'Y';
        else
            $piece_meal = 'N';
        $piece_meal_price = $this->getRequest()->getParam('piece_meal_price');
        $price = $this->getRequest()->getParam('price');

//        $fetch = $this->typebarang->fetchRow("kode_type = '$kode_type'");
//        if ($fetch)
//        {
//            $lastUrut = $fetch['urut'] + 1;
//        }

        $sql = "SELECT * FROM ( SELECT * FROM master_barang_project_2009 WHERE LENGTH(kode_brg) = 8) a ORDER BY SUBSTRING(a.kode_brg,3) DESC LIMIT 1";
        $fetch = $this->db->query($sql);

        $fetch = $fetch->fetch();

        if ($fetch)
        {
            $kodelama = $fetch['kode_brg'];
            $lastUrut = intval(substr($kodelama,2)) + 1;
        }else{
            $lastUrut = 1;
        }


        if ($lastUrut < 10)
        {
            $urut = '00000' . $lastUrut;
        }else if ($lastUrut < 100)
        {
            $urut = '0000' . $lastUrut;
        }else if ($lastUrut < 1000)
        {
            $urut = '000' . $lastUrut;
        }else if ($lastUrut < 10000)
        {
            $urut = '00' . $lastUrut;
        }else if ($lastUrut < 100000)
        {
            $urut = '0' . $lastUrut;
        }else{
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
            "val_kode" => $valuta
        );

        $this->material->insert($insertmaterial);

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertbisnisAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bisnis_code = $this->getRequest()->getParam('bisnis_code');
        $bisnis_name = $this->getRequest()->getParam('bisnis_name');

        $cekcode = $this->bisnis->fetchRow("kode_bisnis = '$bisnis_code' ");
        $cekname = $this->bisnis->fetchRow("nama_bisnis = '$bisnis_name' ");

        if ($cekcode)
        {
            $return = array("success" => false, "pesan" => "Sorry, Bisnis Code exists");
        }else if ($cekname)
        {
            $return = array("success" => false, "pesan" => "Sorry, Bisnis Name exists");
        }else{
            $insert = array(
                "kode_bisnis" => $bisnis_code,
                "nama_bisnis" => $bisnis_name
            );

            $this->bisnis->insert($insert);

    //        var_dump($bisnis_code,$bisnis_name);die;

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertsubbisnisAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $subbisnis_code = $this->getRequest()->getParam('sub_bisnis_code');
        $subbisnis_name = $this->getRequest()->getParam('sub_bisnis_name');

        $cekcode = $this->subbisnis->fetchRow("kode_bisnis_sub = '$subbisnis_code' ");
        $cekname = $this->subbisnis->fetchRow("nama_bisnis = '$subbisnis_name' ");

        if ($cekcode)
        {
            $return = array("success" => false, "pesan" => "Sorry, Sub Bisnis Code exists");
        }else if ($cekname)
        {
            $return = array("success" => false, "pesan" => "Sorry, Sub Bisnis Name exists");
        }else{

            $insert = array(
                "kode_bisnis_sub" => $subbisnis_code,
                "nama_bisnis" => $subbisnis_name
            );

            $this->subbisnis->insert($insert);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertworkAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $work_code = $this->getRequest()->getParam('work_code');
        $work_name = $this->getRequest()->getParam('work_name');

        $cekcode = $this->work->fetchRow("kode_pekerjaan = '$work_code' ");
        $cekname = $this->work->fetchRow("nama_pekerjaan = '$work_name' ");

        if ($cekcode)
        {
            $return = array("success" => false, "pesan" => "Sorry, Work Code exists");
        }else if ($cekname)
        {
            $return = array("success" => false, "pesan" => "Sorry, Work Name exists");
        }else{

            $insert = array(
                "kode_pekerjaan" => $work_code,
                "nama_pekerjaan" => $work_name
            );

            $this->work->insert($insert);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinserttypematerialAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type_material_code = $this->getRequest()->getParam('type_material_code');
        $type_material_name = $this->getRequest()->getParam('type_material_name');

        $cekcode = $this->typebarang->fetchRow("kode_type = '$type_material_code' ");
        $cekname = $this->typebarang->fetchRow("nama_type = '$type_material_name' ");

        if ($cekcode)
        {
            $return = array("success" => false, "pesan" => "Sorry, Type Material Code exists");
        }else if ($cekname)
        {
            $return = array("success" => false, "pesan" => "Sorry, Type Material Name exists");
        }else{

            $insert = array(
                "kode_type" => $type_material_code,
                "nama_type" => $type_material_name
            );

            $this->typebarang->insert($insert);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertbrandAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $brand_code = $this->getRequest()->getParam('brand-code');
        $brand_name = $this->getRequest()->getParam('brand-name');

        $cekcode = $this->brand->fetchRow("mrk_kode = '$brand_code' ");
        $cekname = $this->brand->fetchRow("mrk_nama = '$brand_name' ");

        if ($cekcode)
        {
            $return = array("success" => false, "pesan" => "Sorry, Brand Code exists");
        }else if ($cekname)
        {
            $return = array("success" => false, "pesan" => "Sorry, Brand Name exists");
        }else{

            $insert = array(
                "mrk_kode" => $brand_code,
                "mrk_nama" => $brand_name
            );

            $this->brand->insert($insert);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editmaterialAction ()
    {
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

        if (strlen($materialdata['kode_brg']) == 8)
        {
            $kode = $materialdata['kode_brg'];
//            $kode_bisnis = substr($kode,0,1);
//            $kode_subbisnis = substr($kode,1,1);
            $kode_work = substr($kode,0,1);
            $kode_type = substr($kode,1,1);
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
        if ($stspmeal == 'Y')
        {
            $stspmeal = 'YES';
        }else{
            $stspmeal = 'NO';
        }

        $brand = $materialdata['master_merk'];
        if ($brand == null || $brand == '""')
        {
            $brand = '';
        }

        $valuta = $materialdata['val_kode'];
        if ($valuta == null || $valuta == '""')
        {
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

    public function doupdatematerialAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

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

        if ($piece_meal == 'YES')
            $piece_meal = 'Y';
        else
            $piece_meal = 'N';
        $piece_meal_price = $this->getRequest()->getParam('piece_meal_price');
        $price = $this->getRequest()->getParam('price');
        $typematerial = $this->getRequest()->getParam('typematerial');

        $old_kode_material = $this->getRequest()->getParam('old_material_code');

        if ($typematerial == 'new')
        {

            $updatematerial = array(
                "nama_brg" => $nama_material,
                "harga_borong" => floatval($price),
                "sat_kode" => $uom,
                "master_merk" => $nama_brand,
                "mrk_kode" => $kode_brand,
                "uid" => $this->session->userName,
                "date" => date("Y-m-d H:i:s"),
                "stspmeal" => $piece_meal,
                "val_kode" => $valuta
            );

            $this->material->update($updatematerial,"kode_brg = '$old_kode_material'");

        }else{


            $oldmaterialdata = $this->material->fetchRow("kode_brg = '$old_kode_material'");

            $old_kode_barang = $oldmaterialdata['kode_brg'];

            $ID = $oldmaterialdata['id'];

            $sql = "SELECT * FROM ( SELECT * FROM master_barang_project_2009 WHERE LENGTH(kode_brg) = 8) a ORDER BY SUBSTRING(a.kode_brg,3) DESC LIMIT 1";
            $fetch = $this->db->query($sql);

            $fetch = $fetch->fetch();

            if ($fetch)
            {
                $kodelama = $fetch['kode_brg'];
                $lastUrut = intval(substr($kodelama,2)) + 1;
            }else{
                $lastUrut = 1;
            }


            if ($lastUrut < 10)
            {
                $urut = '00000' . $lastUrut;
            }else if ($lastUrut < 100)
            {
                $urut = '0000' . $lastUrut;
            }else if ($lastUrut < 1000)
            {
                $urut = '000' . $lastUrut;
            }else if ($lastUrut < 10000)
            {
                $urut = '00' . $lastUrut;
            }else if ($lastUrut < 100000)
            {
                $urut = '0' . $lastUrut;
            }else{
                $urut = $lastUrut;
            }

//        $updateurut = array(
//            "urut" => $lastUrut
//        );

//        $this->typebarang->update($updateurut,"kode_type = '$kode_type'");

            $kode_barang = $kode_work . $kode_type . $urut;

    //        var_dump($kode_barang);die;

            $updatematerial = array(
                "kode_brg" => $kode_barang,
                "kode_brg_lama" => $oldmaterialdata['kode_brg'],
                "nama_brg" => $nama_material,
                "nama_brg_lama" => $oldmaterialdata['nama_brg'],
                "harga_borong" => floatval($price),
                "sat_kode" => $uom,
                "master_merk" => $nama_brand,
                "mrk_kode" => $kode_brand,
                "uid" => $this->session->userName,
                "date" => date("Y-m-d H:i:s"),
                "stspmeal" => $piece_meal,
                "val_kode" => $valuta
            );

            if ($oldmaterialdata['kode_brg_lama'] != '')
            {
                unset ($updatematerial['kode_brg_lama']);
                unset ($updatematerial['nama_brg_lama']);

            }

            $this->material->update($updatematerial,"id = '$ID'");

            //transaction update

            $update = array(
                "kode_brg" => $kode_barang,
                "nama_brg" => $nama_material
            );

            $updatepiecemeal = array(
                "kode_brg" => $kode_barang
            );

            $this->boq3d->update($update,"kode_brg = '$old_kode_barang'");
            $this->kboq3d->update($update,"kode_brg = '$old_kode_barang'");
            $this->praboq3d->update($update,"kode_brg = '$old_kode_barang'");
            $this->prd->update($update,"kode_brg = '$old_kode_barang'");
            $this->pod->update($update,"kode_brg = '$old_kode_barang'");
            $this->ripd->update($update,"kode_brg = '$old_kode_barang'");
            $this->arfd->update($update,"kode_brg = '$old_kode_barang'");
            $this->asfd->update($update,"kode_brg = '$old_kode_barang'");
            $this->asfdcancel->update($update,"kode_brg = '$old_kode_barang'");
            $this->remd->update($update,"kode_brg = '$old_kode_barang'");
            $this->dnd->update($update,"kode_brg = '$old_kode_barang'");
            $this->bpvd->update($update,"kode_brg = '$old_kode_barang'");
            $this->pay_arfd->update($update,"kode_brg = '$old_kode_barang'");
            $this->pay_rpid->update($update,"kode_brg = '$old_kode_barang'");
            $this->pay_remd->update($update,"kode_brg = '$old_kode_barang'");
            $this->pay_dnd->update($update,"kode_brg = '$old_kode_barang'");
            $this->whod->update($update,"kode_brg = '$old_kode_barang'");
            $this->pointd->update($update,"kode_brg = '$old_kode_barang'");
            $this->whbringbackd->update($update,"kode_brg = '$old_kode_barang'");
            $this->whreturnd->update($update,"kode_brg = '$old_kode_barang'");
            $this->whsupplierd->update($update,"kode_brg = '$old_kode_barang'");
            $this->afed->update($update,"kode_brg = '$old_kode_barang'");
            $this->afesaving->update($update,"kode_brg = '$old_kode_barang'");
            $this->praohpd->update($update,"kode_brg = '$old_kode_barang'");
            $this->sald->update($update,"kode_brg = '$old_kode_barang'");

            $this->piecemeal->update($updatepiecemeal,"kode_brg = '$old_kode_barang'");
            $this->dboq->update($updatepiecemeal,"kode_brg = '$old_kode_barang'");

        }

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewbisnisAction ()
    {
        
    }

    public function doupdatebisnisAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $nama_bisnis = $this->getRequest()->getParam('bisnis-name');
        $kode_bisnis = $this->getRequest()->getParam('bisnis-code');
        $id_bisnis = $this->getRequest()->getParam('id');

        $where = "(kode_bisnis = '$kode_bisnis' OR nama_bisnis = '$nama_bisnis') AND id != '$id_bisnis' ";

        $cek = $this->bisnis->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry, Bisnis Code or Bisnis Name exists");
        }else{
            $update = array(
                "kode_bisnis" => $kode_bisnis,
                "nama_bisnis" => $nama_bisnis
            );

            $this->bisnis->update($update,"id = '$id_bisnis'");

            $return = array("success" => true);
        }



//        var_dump($nama_bisnis,$kode_bisnis,$id_bisnis);die;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewsubbisnisAction ()
    {
        
    }

    public function doupdatesubbisnisAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $nama_subbisnis = $this->getRequest()->getParam('subbisnis-name');
        $kode_subbisnis = $this->getRequest()->getParam('subbisnis-code');
        $id_subbisnis = $this->getRequest()->getParam('id');

        $where = "(kode_bisnis_sub = '$kode_subbisnis' OR nama_bisnis = '$nama_subbisnis') AND id != '$id_subbisnis' ";

        $cek = $this->subbisnis->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry,Sub Bisnis Code or Sub Bisnis Name exists");
        }else{
            $update = array(
                "kode_bisnis_sub" => $kode_subbisnis,
                "nama_bisnis" => $nama_subbisnis
            );

            $this->subbisnis->update($update,"id = '$id_subbisnis'");

            $return = array("success" => true);
        }



//        var_dump($nama_bisnis,$kode_bisnis,$id_bisnis);die;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewworkAction ()
    {
        
    }

    public function doupdateworkAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $nama_work = $this->getRequest()->getParam('work-name');
        $kode_work = $this->getRequest()->getParam('work-code');
        $id_work = $this->getRequest()->getParam('id');

        $where = "(kode_pekerjaan = '$kode_work' OR nama_pekerjaan = '$nama_work') AND id != '$id_work' ";

        $cek = $this->work->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry,Work Code or Work Name exists");
        }else{
            $update = array(
                "kode_pekerjaan" => $kode_work,
                "nama_pekerjaan" => $nama_work
            );

            $this->work->update($update,"id = '$id_work'");

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewtypematerialAction ()
    {
        
    }

    public function doupdatetypeAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $nama_type = $this->getRequest()->getParam('type-name');
        $kode_type = $this->getRequest()->getParam('type-code');
        $id_type = $this->getRequest()->getParam('id');

        $where = "(kode_type = '$kode_type' OR nama_type = '$nama_type') AND id != '$id_type' ";

        $cek = $this->typebarang->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry,Type Material Code or Type Material Name exists");
        }else{
            $update = array(
                "kode_type" => $kode_type,
                "nama_type" => $nama_type
            );

            $this->typebarang->update($update,"id = '$id_type'");

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewbrandAction ()
    {
        
    }

    public function doupdatebrandAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $nama_brand = $this->getRequest()->getParam('brand-name');
        $kode_brand = $this->getRequest()->getParam('brand-code');
        $id_brand = $this->getRequest()->getParam('id');

        $where = "(mrk_kode = '$kode_brand' OR mrk_nama = '$nama_brand') AND id != '$id_brand' ";

        $cek = $this->brand->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry,Brand Code or Brand Name exists");
        }else{
            $update = array(
                "mrk_kode" => $kode_brand,
                "mrk_nama" => $nama_brand
            );

            $this->brand->update($update,"id = '$id_brand'");

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getmaterialAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $nama_brg = $this->getRequest()->getParam('nama_brg');

//        var_dump($nama_brg);die;
        $nama_brg = str_replace(' ','%',$nama_brg);
        $where = "nama_brg like '%$nama_brg%'";

        $data['data'] = $this->material->fetchAll($where)->toArray();

        $json = Zend_Json::encode($data['data']);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function ceklastpriceAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $kodeBrg = $this->getRequest()->getParam("kode_brg");
        $hargaBrg = $this->getRequest()->getParam("harga");

        $cek = $this->material->fetchRow("kode_brg = '$kodeBrg'");
        if ($cek)
        {
            $hargaAsli = $cek['hargaavg'];
            if (bccomp($hargaAsli,$hargaBrg) > 0)
            {
                $data = array("success" => false,"msg" => "The Price in PR is lower than Master Material! Please Edit your PR/Budget!");
            }
            else
                $data = array("success" => true);
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

}