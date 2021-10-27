<?php
/**
 * Created by JetBrains PhpStorm.
 * User: icha
 * Date: 14/12/16
 * Time: 10:49 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Procurement_PrrevisiController extends Zend_Controller_Action
{
    private $procurement;
    private $procurementH;
    private $db;
    private $request;
    private $json;
    private $util;
    private $token;
    private $session;
    private $workflow;
    private $workflowClass;
    private $workflowTrans;
    private $project;
    private $projectG;
    private $barang;
    private $const;
    private $trans;
    private $error;
    private $upload;
    private $files;
    private $budget;
    private $quantity;
    private $log;
    private $log_trans;
    private $creTrans;
    private $prrevH;

    public function init()
    {
         $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->const = Zend_Registry::get('constant');
        //$this->leadHelper = $this->_helper->getHelper('chart');
        $this->upload = $this->_helper->getHelper('uploadfile');
                $this->workflow = $this->_helper->getHelper('workflow');
        $this->session = new Zend_Session_Namespace('login');
                $this->error = $this->_helper->getHelper('error');
        $this->request = $this->getRequest();
        $this->json = $this->request->getParam('posts');
        if (isset($this->json))
        {
                //Fix unknown JSON format (Bugs on Firefox 3.6)
                $this->json = str_replace("\\","",$this->json);
                if (substr($this->json,0,1) != '[')
                {
                        $this->json = "[" . $this->json . "]";
                }
        }
        $this->procurement = new Default_Models_ProcurementRequest();
        $this->procurementH = new Default_Models_ProcurementRequestH();
        $this->purchase = new Default_Models_ProcurementPod();
        $this->purchaseH = new Default_Models_ProcurementPoh();
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->trans = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Models_Workflow();
        $this->files = new Default_Models_Files();
        $this->paymentArf = new Finance_Models_PaymentARF();
        $this->budget = new Default_Models_Budget();
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->log = new Admin_Models_Logtransaction();
        $this->log_trans = new Procurement_Model_Logtransaction();
        $this->creTrans = new Admin_Model_CredentialTrans();
        $this->prrevH = new Procurement_Models_Procurementprh();

    }


    
    public function getprfinalapproveAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data = $this->getRequest()->getParam("data");
        $type = $this->getRequest()->getParam("type");
        $name = $this->getRequest()->getParam("name");
        
        $search = "";

        if ($name == 'trano')
        {
            $search = " and trano like '%$data%'";
        }else if ($name == 'prj_kode')
        {
            $search = "and prj_kode like '%$data%'";
        }else if ($name == 'sit_kode')
        {
            $search = "and sit_kode like '%$data%'";
        }else
        {
            $search = "";
        }
        
        switch($type)
        {
            case 'P':
            $search .= " and tipe= 'P'";
            break;
            case 'O':
            $search .= " and tipe= 'O'";
            break;
            case 'S':
            $search .= " and tipe= 'S'";
            break;
        }
        

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data = $this->prrevH->ViewPrFinalApprove($offset,$limit,$dir,$sort,$search,$type);

//        $return['posts'] = $data;
//        $return['count'] = $this->arfrevH->fetchAll()->count();;

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

     public function prrevisiAction (){
      $isCancel = $this->getRequest()->getParam("returnback");
          
        if ($isCancel) {
            $this->view->pr = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file");
        }
        else
        {
        $trano = $this->getRequest()->getParam("trano");
        $prd = $this->procurement->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");
        
        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach ($prh as $key => $val) {
            if ($val == '""')
                $prh[$key] = '';
        }
        
        foreach ($prd as $key2 => $val2) {
            
                $prd[$key2]['net_act'] = $val2['cfs_kode'];
                
            $prd[$key2]['id'] = $val2['id'];
            $prd[$key2]['uom'] = $this->quantity->getUOMByProductID($val2['kode_brg']);
            $prd[$key2]['nama_brg'] = str_replace("\"", "'", $val2['nama_brg']);
                
            // Get Related PO
            $po = $this->cost->getPO($trano,$val2['workid'],$val2['kode_brg']);              
            $prd[$key2]['pototal'] = $po['totalUSD'] > 0 ? $po['totalUSD'] : $po['totalIDR'];
                
            // Get Related DOR Qty            
            $dor = $this->cost->getDOR($trano,$val2['workid'],$val2['kode_brg']);
            $prd[$key2]['dorqty'] = $dor['qty'];
            $prd[$key2]['dortotal'] = $dor['total'];
            
            $prd[$key2]['qty'] = $prd[$key2]['dorqty'] > 0 || $prd[$key2]['dortotal'] > 0 || $prd[$key2]['pototal'] > 0 ? $val2['qty'] : 0;
                
        }
        
        if ($prh['revisi'] == '' || $prh['revisi'] == '""') {
            $prh['revisi'] = 1;
        } else
            $prh['revisi'] = abs($prh['revisi']) + 1;
        
            $file = Zend_Json::encode($file);
            $prd = Zend_Json::encode($prd);
            $prh = Zend_Json::encode($prh);

            $this->view->pr = $prd;
            $this->view->etc = '['.$prh.']';
            $this->view->file = $file;
            
        }
        
        Zend_Loader::loadClass('Zend_Json');
        
    }
    
    public function getlogtransactionAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $data['data'] = $this->log_trans->ViewLogTransaction ($trano);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);


    }

    public function getlogproductlistbeforeAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $tgl = $this->getRequest()->getParam('tgl');

        $data['data'] = $this->log_trans->ViewLogProductListBefore ($trano,$tgl);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getlogproductlistafterAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $tgl = $this->getRequest()->getParam('tgl');

        $data['data'] = $this->log_trans->ViewLogProductListAfter ($trano,$tgl);

//        var_dump($data);die;

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }



}