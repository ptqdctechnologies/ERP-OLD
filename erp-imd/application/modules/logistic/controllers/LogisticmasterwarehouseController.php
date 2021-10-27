<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/8/11
 * Time: 2:54 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_LogisticmasterwarehouseController extends Zend_Controller_Action
{

    private $warehouse;
    private $session;

    public function init ()
    {
        $this->warehouse = new Logistic_Models_MasterGudang();
        $this->session = new Zend_Session_Namespace('login');
    }

    public function warehouseAction ()
    {
        
    }

    public function viewwarehouseAction ()
    {
        
    }

    public function getwarehouseAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $search = $this->getRequest()->getParam('search');
        if(!$search)
            $search = null;
        else
            $search = "gdg_nama like '%$search%' ";

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $warehousedata = $this->warehouse->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        $return['data'] = $warehousedata;
        foreach ($return['data'] as $key => $val) {

            foreach ($val as $key2 => $val2) {                                
                if ($val2 == '""')
                    $return['data'][$key][$key2] = '';
                if ($val2 == null)
                    $return['data'][$key][$key2] = '';
            }
        }
        
        $return['total'] = $this->warehouse->fetchAll()->count();

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function addwarehouseAction ()
    {
        
    }

    public function getaddwarehouseAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();

        $gdg_kode = $this->_getParam('gdg_kode');
        $gdg_nama = $this->_getParam('gdg_nama');
        $alamat = $this->_getParam('alamat1');
        $ket = $this->_getParam('ket');
        $stsactive = $this->_getParam('stsactive');
        $sts_temporary = $this->_getParam('sts_temporary');

        $where = "gdg_kode = '$gdg_kode' OR gdg_nama = '$gdg_nama' ";

        $cekwarehouse = $this->warehouse->fetchRow ($where);

        if ($cekwarehouse)
        {
            $return = array("success" => false, "pesan" => "Sorry, Warehouse Exist");
        }else
        {
            $insertWarehouse = array(
                "gdg_kode" => $gdg_kode,
                "gdg_nama" => $gdg_nama,
                "alamat1" => $alamat,
                "ket" => $ket,
                "stsactive" => $stsactive,
                "sts_temporary" => $sts_temporary
            );

            $this->warehouse->insert($insertWarehouse);
            $activityHead['master_gudang'][0]=$insertWarehouse;

            $return = array("success" => true);

        }

        $json = Zend_Json::encode($return);
        
         $activityLog = array(
            "menu_name" => "Create Master Warehouse",
            "trano" => $gdg_kode,
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
        
        
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function editwarehouseAction ()
    {
        $this->_helper->layout->disableLayout();

        $gdg_kode = $this->_getParam('gdg_kode');

        $where = "gdg_kode = '$gdg_kode'";

        $datawarehouse = $this->warehouse->fetchRow($where);
        $this->view->tampil = $datawarehouse;

    }

    public function geteditwarehouseAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $gdg_kode = $this->_getParam('gdg_kode');
        $gdg_nama = $this->_getParam('gdg_nama');
        $alamat = $this->_getParam('alamat1');
        $ket = $this->_getParam('ket');
        $stsactive = $this->_getParam('stsactive');
        $sts_temporary = $this->_getParam('sts_temporary');
        $gdg_id = $this->_getParam('id');

        $old = $this->warehouse->fetchAll("id = '$gdg_kode'")->toArray();
        $log['warehouse-detail-before'] = $old;
        $updateWarehouse = array(
            "gdg_kode" => $gdg_kode,
            "gdg_nama" => $gdg_nama,
            "alamat1" => $alamat,
            "ket" => $ket,
            "stsactive" => $stsactive,
            "sts_temporary" => $sts_temporary,
        );

        $this->warehouse->update($updateWarehouse,"id = '$gdg_id'");
        
        $new = $this->warehouse->fetchAll("id = '$gdg_kode'")->toArray();
        $log2['warehouse-detail-after'] = $new;

        $return = array("success" => true);
        
         //Log Transaction
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $gdg_kode,
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
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getWarehouseDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $gdg_kode = $this->_getParam("gdg_kode");

        $data = $this->warehouse->fetchRow("gdg_kode = '$gdg_kode'");
        if ($data)
        {
            $data = $data->toArray();
            $return = array(
                "success" => true,
                "data" => $data
            );

        }
        else
            $return = array(
                "success" => false,
                "msg" => "Warehouse not found"
            );

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateWarehouseDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $field = $this->_getParam("field");
        $value = $this->_getParam("value");
        $gdg_kode = $this->_getParam("gdg_kode");
        var_dump($value);

        $data = $this->warehouse->fetchRow("gdg_kode = '$gdg_kode'");
        if ($data)
        {

            $arrayInsert = array(
                $field => $value
            );

            $this->warehouse->update($arrayInsert,"gdg_kode = '$gdg_kode'");
            $data = $this->warehouse->fetchRow("gdg_kode = '$gdg_kode'");
            $return = array(
                "success" => true,
                "data" => $data->toArray()
            );

        }
        else
            $return = array(
                "success" => false,
                "msg" => "Warehouse not found"
            );

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }
}