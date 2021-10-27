<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/8/11
 * Time: 10:49 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Procurement_ArfrevisiController extends Zend_Controller_Action
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
    private $purchase;
    private $purchaseH;
    private $rpi;
    private $rpiH;
    private $asf;
    private $asfc;
    private $asfD;
    private $asfH;
    private $trans;
    private $arfh;
    private $arfd;
    private $pmeal;
    private $pmealH;
    private $error;
    private $upload;
    private $files;
    private $budget;
    private $quantity;
    private $reimbursH;
    private $reimbursD;
    private $log;
    private $arfrevH;
    private $arfrevD;
    private $log_trans;
    private $creTrans;

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
        $this->arfh = new Default_Models_AdvanceRequestFormH();
        $this->arfd = new Default_Models_AdvanceRequestFormD();
        $this->reimbursH = new Default_Models_ReimbursH();
        $this->reimbursD = new Default_Models_ReimbursD();
        $this->procurement = new Default_Models_ProcurementRequest();
        $this->procurementH = new Default_Models_ProcurementRequestH();
        $this->purchase = new Default_Models_ProcurementPod();
        $this->purchaseH = new Default_Models_ProcurementPoh();
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->rpi = new Default_Models_RequestPaymentInvoice();
        $this->rpiH = new Default_Models_RequestPaymentInvoiceH();
        $this->asf = new Default_Models_AdvanceSettlementForm();
        $this->asfc = new Default_Models_AdvanceSettlementFormCancel();
        $this->asfD = new Default_Models_AdvanceSettlementFormD();
        $this->asfH = new Default_Models_AdvanceSettlementFormH();
        $this->pmeal = new Default_Models_PieceMeal();
        $this->pmealH = new Default_Models_PieceMealH();
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->trans = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Model_Workflow();
        $this->files = new Default_Models_Files();
//        $this->budget = $this->_helper->getHelper('budget');
        $this->budget = new Default_Models_Budget();
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->log = new Admin_Model_Logtransaction();
        $this->arfrevH = new Procurement_Model_Procurementarfh();
        $this->arfrevD = new Procurement_Model_Procurementarfd();
        $this->log_trans = new Procurement_Model_Logtransaction();
        $this->creTrans = new Admin_Model_CredentialTrans();

    }

    public function arfrevisiAction ()
    {
         $this->view->uid = $this->session->userName;
         $this->view->nama = $this->session->name;

         $trano = $this->getRequest()->getParam("trano");
         $arfh = $this->arfh->fetchRow("trano = '$trano'");
         $arfd = $this->arfd->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
         $file = $this->files->fetchAll("trano = '$trano'");
         $doc_file = 'arfrevisi';

            if ($file)
                $file = $file->toArray();
            else
                $file = array();

          if ($arfh)
              $arfh = $arfh->toArray();
          $tmp = array();

         foreach($arfd as $key => $val)
         {
          foreach ($val as $key2 => $val2)
          {
              if ($val2 == '""')
                  $arfd[$key][$key2] = '';
          }
            $arfd[$key]['id'] = $key + 1;
            $kodeBrg = $val['kode_brg'];
            $workid = $val['workid'];
            $sitKode = $val['sit_kode'];
            $prjKode = $val['prj_kode'];

            $arfd[$key]['priceArf'] = $val['harga'];
            $arfd[$key]['totalARF'] = $val['total'];

        //        $arfd[$key]['trano'] = $arfd[$key]['pr_no'];


        //        if(!in_array($arfd[$key]['trano'],$tmp))
        //          $tmp['trano'] = $arfd[$key]['trano'];
        //        unset($arfd[$key]['pr_no']);
        //        unset($arfd[$key]['harga']);
        //        unset($arfd[$key]['total']);
             $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
             if ($barang)
             {
                 $arfd[$key]['uom'] = $barang['sat_kode'];
             }

             $boq3 = $this->budget->getBoq3ByOne($prjKode,$sitKode,$workid,$kodeBrg);
             if ($arfd[$key]['val_kode'] == 'IDR')
            {
        //            $arfd[$key]['priceBOQ3'] = $boq3['hargaIDR'];
                $arfd[$key]['totalBOQ3'] = $boq3['totalIDR'];
            }
            else
            {
        //            $arfd[$key]['priceBOQ3'] = $boq3['hargaUSD'];
                $arfd[$key]['totalBOQ3'] = $boq3['totalUSD'];
            }
            $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$workid,$kodeBrg);
            $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid,$kodeBrg);
            $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$workid,$kodeBrg);
        //        $reimburs = $this->quantity->getReimbursementQuantity($prjKode,$sitKode,$workid,$kodeBrg);
        //                var_dump($po);die;
            if ($po != '' )
            {
                    $arfd[$key]['totalqtyPO'] = $po['qty'];
                    if ($arfd[$key]['val_kode'] == 'IDR')
                        $arfd[$key]['totalPO'] = $po['totalIDR'];
                    else
                        $arfd[$key]['totalPO'] = $po['totalUSD'];
            }
            else
            {
                    $arfd[$key]['totalqtyPO'] = 0;
                    $arfd[$key]['totalPO'] = 0;
            }
            if ($arf != '' )
            {
                    $arfd[$key]['totalqtyARF'] = $arf['qty'];
                    if ($arfd[$key]['val_kode'] == 'IDR')
                        $arfd[$key]['totalInARF'] = $arf['totalIDR'];
                    else
                        $arfd[$key]['totalInARF'] = $arf['totalUSD'];
            }
            else
            {
                    $arfd[$key]['totalqtyARF'] = 0;
                    $arfd[$key]['totalARF'] = 0;
            }

            if ($asfcancel != '' )
            {
                    $arfd[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                    if ($arfd[$key]['val_kode'] == 'IDR')
                        $arfd[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                    else
                        $arfd[$key]['totalASFCancel'] = $asfcancel['totalUSD'];
            }
            else
            {
                    $arfd[$key]['totalqtyASFCancel'] = 0;
                    $arfd[$key]['totalASFCancel'] = 0;
            }

        //        if ($reimburs != '' )
        //                {
        //                        $arfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
        //                        if ($arfd[$key]['val_kode'] == 'IDR')
        //                            $arfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
        //                        else
        //                            $arfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
        //                }
        //                else
        //                {
        //                        $arfd[$key]['totalqtyReimburs'] = 0;
        //                        $arfd[$key]['totalReimburs'] = 0;
        //                }
            $totalpoarfasfc = (($arfd[$key]['totalPO'] +  $arfd[$key]['totalInARF']) -  $arfd[$key]['totalASFCancel'] ) ;
            $arfd[$key]['totalPoArfAsfc'] = $totalpoarfasfc;

         }
        //                 var_dump($arfd);die;

          foreach($arfh as $key => $val)
             {
              if ($val == '""')
                  $arfh[$key] = '';
          }
          $tmp2 = $arfh;
          unset($arfh);
          $arfh[0] = $tmp2;
             Zend_Loader::loadClass('Zend_Json');
             $jsonData = Zend_Json::encode($arfd);
             $jsonData2 = Zend_Json::encode($arfh);

             $isCancel = $this->getRequest()->getParam("returnback");
          if ($isCancel)
          {
              $this->view->cancel = true;
              $this->view->json = $this->getRequest()->getParam("posts");
              $this->view->jsonEtc = $this->getRequest()->getParam("etc");
          }
          else
         {
              $this->view->json = $jsonData;
              $this->view->jsonEtc = $jsonData2;
         }
          $this->view->prNo = $tmp;
             $this->view->trano = $trano;
             $this->view->tgl = date('d-m-Y',strtotime($arfh[0]['tgl']));
          $this->view->pr_no = $arfh[0]['pr_no'];
          $this->view->val_kode = $arfh[0]['val_kode'];
          $this->view->request = $arfh[0]['request'];
          $this->view->orangfinance = $arfh[0]['orangfinance'];
          $this->view->ket = $arfh[0]['ket'];
          $this->view->ketin = $arfh[0]['ketin'];
          $this->view->doc_file = $doc_file;

          Zend_Loader::loadClass('Zend_Json');
          $file = Zend_Json::encode($file);
          $this->view->file = $file;
    }

    public function apparfrevisiAction ()
    {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $doc_file = $this->getRequest()->getParam("doc_file");
        $this->view->doc_file = $doc_file;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/ARF';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");


        if ($approve == '')
        {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\","",$etc);

            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            foreach($jsonData as $k => $v)
            {
                $jsonData[$k]['cfs_kode'] = $v['net_act'];
                $jsonData[$k]['cfs_nama'] = $v['net_act'];
            }

         $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
         $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
         $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;
            $this->view->jsonFile = $files;
            $this->view->file = $file;

            if ($from == 'edit')
            {
                $this->view->edit = true;
            }

        }
        else
        {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

            if ($docs)
            {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
                if ($user  || $show)
                {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];

                 $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $arfd = $this->arfd->fetchAll("trano = '$approve'")->toArray();
                    $arfh = $this->arfh->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");

                    if ($arfd)
                    {
                        foreach($arfd as $key => $val)
                        {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang)
                            {
                                $arfd[$key]['uom'] = $barang['sat_kode'];
                            }
//                                $arfd[$key]['priceArf'] = $val['priceArf'];
                                $arfd[$key]['priceArf'] = $val['harga'];
                                $arfd[$key]['totalARF'] = $val['total'];
                                $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
                        }

                     $userApp = $this->workflow->getAllApproval($approve);
                     $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $arfh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $arfh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $arfh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $arfh['sit_nama'];
                        $jsonData2[0]['budgettype'] = $arfh['budgettype'];
                        $jsonData2[0]['valuta'] = $arfh['val_kode'];
                        $jsonData2[0]['mgr_kode'] = $arfh['request'];
                        $jsonData2[0]['pic_kode'] = $arfh['orangpic'];
                        $jsonData2[0]['ketin'] = $arfh['ketin'];

                        $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                        $jsonData2[0]['pic_nama'] = $picName['Name'];
                        $mgrName = $this->trans->getManagerName($approve);
                        $jsonData[0]['mgr_nama'] = $mgrName;

                        $cusKode = $this->project->getProjectAndCustomer($arfh['prj_kode']);
                     $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                     $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                     $jsonData2[0]['trano'] = $approve;

                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $arfd;
                        $this->view->file = $file;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                    }
                }
                else
                {
                    $this->view->approve = false;
                }
            }
            else
            {
                $this->view->approve = false;
            }
        }
    }

    public function arfrevisiohAction ()
    {
        $this->view->uid = $this->session->userName;
     $this->view->nama = $this->session->name;

     $trano = $this->getRequest()->getParam("trano");
     $arfh = $this->arfh->fetchRow("trano = '$trano'");
     $arfd = $this->arfd->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
     $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

      if ($arfh)
          $arfh = $arfh->toArray();
      $tmp = array();

     foreach($arfd as $key => $val)
     {
      foreach ($val as $key2 => $val2)
      {
          if ($val2 == '""')
              $arfd[$key][$key2] = '';
      }
        $arfd[$key]['id'] = $key + 1;
        $kodeBrg = $val['kode_brg'];
        $workid = $val['workid'];
        $sitKode = $val['sit_kode'];
        $prjKode = $val['prj_kode'];

        $arfd[$key]['priceArf'] = $val['harga'];
        $arfd[$key]['totalARF'] = $val['total'];
        $arfd[$key]['budgetid'] = $val['workid'];
        $arfd[$key]['budgetname'] = $val['workname'];

         $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
         if ($barang)
         {
             $arfd[$key]['uom'] = $barang['sat_kode'];
         }

         $boq3 = $this->budget->getBudgetOverhead($prjKode,$sitKode,$workid);

         if ($arfd[$key]['val_kode'] == 'IDR')
        {
            $arfd[$key]['totalBOQ3'] = $boq3[0]['totalIDR'];
        }
        else
        {
            $arfd[$key]['totalBOQ3'] = $boq3[0]['totalUSD'];
        }
        $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$workid);
        $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid);
        $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$workid);
        $reimburs = $this->quantity->getReimbursementQuantity($prjKode,$sitKode,$workid);

        if ($po != '' )
        {
                $arfd[$key]['totalqtyPO'] = $po['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $arfd[$key]['totalPO'] = $po['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyPO'] = 0;
                $arfd[$key]['totalPO'] = 0;
        }
        if ($arf != '' )
        {
                $arfd[$key]['totalqtyARF'] = $arf['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalInARF'] = $arf['totalIDR'];
                else
                    $arfd[$key]['totalInARF'] = $arf['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyARF'] = 0;
                $arfd[$key]['totalARF'] = 0;
        }

        if ($asfcancel != '' )
        {
                $arfd[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                else
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyASFCancel'] = 0;
                $arfd[$key]['totalASFCancel'] = 0;
        }

        if ($reimburs != '' )
                {
                        $arfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
                        if ($arfd[$key]['val_kode'] == 'IDR')
                            $arfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
                        else
                            $arfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
                }
                else
                {
                        $arfd[$key]['totalqtyReimburs'] = 0;
                        $arfd[$key]['totalReimburs'] = 0;
                }
        $totalpoarfasfc = (($arfd[$key]['totalPO'] +  $arfd[$key]['totalInARF']) -  $arfd[$key]['totalASFCancel'] ) ;
        $arfd[$key]['totalPoArfAsfc'] = $totalpoarfasfc;

     }

      foreach($arfh as $key => $val)
         {
          if ($val == '""')
              $arfh[$key] = '';
      }
      $tmp2 = $arfh;
      unset($arfh);
      $arfh[0] = $tmp2;
         Zend_Loader::loadClass('Zend_Json');
         $jsonData = Zend_Json::encode($arfd);
         $jsonData2 = Zend_Json::encode($arfh);

         $isCancel = $this->getRequest()->getParam("returnback");
      if ($isCancel)
      {
          $this->view->cancel = true;
          $this->view->json = $this->getRequest()->getParam("posts");
          $this->view->jsonEtc = $this->getRequest()->getParam("etc");
      }
      else
     {
          $this->view->json = $jsonData;
          $this->view->jsonEtc = $jsonData2;
     }

      $this->view->prNo = $tmp;
         $this->view->trano = $trano;
         $this->view->tgl = date('d-m-Y',strtotime($arfh[0]['tgl']));
      $this->view->pr_no = $arfh[0]['pr_no'];
      $this->view->val_kode = $arfh[0]['val_kode'];
      $this->view->request = $arfh[0]['request'];
      $this->view->orangfinance = $arfh[0]['orangfinance'];
      $this->view->ket = $arfh[0]['ket'];
      $this->view->ketin = $arfh[0]['ketin'];

      Zend_Loader::loadClass('Zend_Json');
      $file = Zend_Json::encode($file);
      $this->view->file = $file;
    }

    public function apparfrevisiohAction ()
    {
         $type = $this->getRequest()->getParam("type");
            $from = $this->getRequest()->getParam("from");
            $sales = $this->getRequest()->getParam("sales");
            $show = $this->getRequest()->getParam("show");
            $this->view->show = $show;

            if ($type != '')
                $this->view->urlBack = '/default/home/showprocessdocument/type/ARFO';
            else
                $this->view->urlBack = '/default/home/showprocessdocument';

            $approve = $this->getRequest()->getParam("approve");


            if ($approve == '')
            {
                $json = $this->getRequest()->getParam("posts");
                $etc = $this->getRequest()->getParam("etc");
                $files = $this->getRequest()->getParam("file");
                $etc = str_replace("\\","",$etc);
                Zend_Loader::loadClass('Zend_Json');
                $jsonData = Zend_Json::decode($json);
                $jsonData2 = Zend_Json::decode($etc);
                $file = Zend_Json::decode($files);

                foreach($jsonData as $key => $val)
   			    {
                    $jsonData[$key]['cfs_kode'] = $val['net_act'];
                    $jsonData[$key]['cfs_nama'] = $val['net_act'];
                    foreach($val as $key2 => $val2)
                    {
                        if ($val2 == "\"\"")
                            $jsonData[$key][$key2] = '';
                        if (strpos($val2,"\"")!== false)
                            $jsonData[$key][$key2] = str_replace("\""," inch",$jsonData[$key][$key2]);
                        if (strpos($val2,"'")!== false)
                            $jsonData[$key][$key2] = str_replace("'"," inch",$jsonData[$key][$key2]);
                    }
   			    }
//             $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
//             $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//             $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                $this->view->result = $jsonData;
                $this->view->etc = $jsonData2;
                $this->view->jsonResult = $json;
                $this->view->jsonFile = $files;
	       	    $this->view->file = $file;

                if ($from == 'edit')
                {
                    $this->view->edit = true;
                }

                if ($sales == 'true')
                {
                    $this->view->sales = true;
                }

            }
            else
            {
                $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

                if ($docs)
                {
                    $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
                    if ($user || $show)
                    {
                        $id = $docs['workflow_trans_id'];
                        $workflowId = $docs['workflow_id'];
                        $approve = $docs['item_id'];
                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $statApprove = $docs['approve'];

                     $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

                        if ($statApprove == $this->const['DOCUMENT_REJECT'])
                            $this->view->reject = true;

                        $potong = substr($approve,0,5);
                        if ($potong == 'ARF01')
                            $this->view->sales = true;

                        $arfd = $this->arfd->fetchAll("trano = '$approve'")->toArray();
                        $arfh = $this->arfh->fetchRow("trano = '$approve'");
                        $file = $this->files->fetchAll("trano = '$approve'");

                        if ($arfd)
                        {
                            foreach($arfd as $key => $val)
                            {
                                $kodeBrg = $val['kode_brg'];
                                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                                if ($barang)
                                {
                                    $arfd[$key]['uom'] = $barang['sat_kode'];
                                }
//                                $arfd[$key]['priceArf'] = $val['priceArf'];
                                    $arfd[$key]['priceArf'] = $val['harga'];
                                    $arfd[$key]['totalARF'] = $val['total'];
                                    $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
                            }

                         $userApp = $this->workflow->getAllApproval($approve);
                         $jsonData2[0]['user_approval'] = $userApp;
                            $jsonData2[0]['prj_kode'] = $arfh['prj_kode'];
                            $jsonData2[0]['prj_nama'] = $arfh['prj_nama'];
                            $jsonData2[0]['sit_kode'] = $arfh['sit_kode'];
                            $jsonData2[0]['sit_nama'] = $arfh['sit_nama'];
                            $jsonData2[0]['budgettype'] = $arfh['budgettype'];
                            $jsonData2[0]['valuta'] = $arfh['val_kode'];
                            $jsonData2[0]['mgr_kode'] = $arfh['request'];
                            $jsonData2[0]['pic_kode'] = $arfh['orangpic'];
                            $jsonData2[0]['ketin'] = $arfh['ketin'];

                            $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                            $jsonData2[0]['pic_nama'] = $picName['Name'];
                            $mgrName = $this->trans->getManagerName($approve);
                            $jsonData[0]['mgr_nama'] = $mgrName;

//                            $cusKode = $this->project->getProjectAndCustomer($arfh['prj_kode']);
//                         $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//                         $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                         $jsonData2[0]['trano'] = $approve;

                            $allReject = $this->workflow->getAllReject($approve);
                            $lastReject = $this->workflow->getLastReject($approve);
                            $this->view->lastReject = $lastReject;
                            $this->view->allReject = $allReject;
                            $this->view->etc = $jsonData2;
                            $this->view->result = $arfd;
                            $this->view->file = $file;
                            $this->view->trano = $approve;
                            $this->view->approve = true;
                            $this->view->uid = $this->session->userName;
                            $this->view->userID = $this->session->idUser;
                            $this->view->docsID = $id;
                        }
                    }
                    else
                    {
                        $this->view->approve = false;
                    }
                }
                else
                {
                    $this->view->approve = false;
                }
            }
    }

    public function arfrevisisalesAction ()
    {
        $this->view->uid = $this->session->userName;
     $this->view->nama = $this->session->name;

     $trano = $this->getRequest()->getParam("trano");
     $arfh = $this->arfh->fetchRow("trano = '$trano'");
     $arfd = $this->arfd->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
     $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

      if ($arfh)
          $arfh = $arfh->toArray();
      $tmp = array();

     foreach($arfd as $key => $val)
     {
      foreach ($val as $key2 => $val2)
      {
          if ($val2 == '""')
              $arfd[$key][$key2] = '';
      }
        $arfd[$key]['id'] = $key + 1;
        $kodeBrg = $val['kode_brg'];
        $workid = $val['workid'];
        $sitKode = $val['sit_kode'];
        $prjKode = $val['prj_kode'];

        $arfd[$key]['priceArf'] = $val['harga'];
        $arfd[$key]['totalARF'] = $val['total'];
        $arfd[$key]['budgetid'] = $val['workid'];
        $arfd[$key]['budgetname'] = $val['workname'];

         $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
         if ($barang)
         {
             $arfd[$key]['uom'] = $barang['sat_kode'];
         }

         $boq3 = $this->budget->getBoq3ByOne($prjKode,$sitKode,$workid,$kodeBrg);
         if ($arfd[$key]['val_kode'] == 'IDR')
        {
            $arfd[$key]['totalBOQ3'] = $boq3['totalIDR'];
        }
        else
        {
            $arfd[$key]['totalBOQ3'] = $boq3['totalUSD'];
        }
        $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$workid,$kodeBrg);
        $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid,$kodeBrg);
        $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$workid,$kodeBrg);
        $reimburs = $this->quantity->getReimbursementQuantity($prjKode,$sitKode,$workid,$kodeBrg);

        if ($po != '' )
        {
                $arfd[$key]['totalqtyPO'] = $po['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $arfd[$key]['totalPO'] = $po['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyPO'] = 0;
                $arfd[$key]['totalPO'] = 0;
        }
        if ($arf != '' )
        {
                $arfd[$key]['totalqtyARF'] = $arf['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalInARF'] = $arf['totalIDR'];
                else
                    $arfd[$key]['totalInARF'] = $arf['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyARF'] = 0;
                $arfd[$key]['totalARF'] = 0;
        }

        if ($asfcancel != '' )
        {
                $arfd[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                else
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalUSD'];
        }
        else
        {
                $arfd[$key]['totalqtyASFCancel'] = 0;
                $arfd[$key]['totalASFCancel'] = 0;
        }

        if ($reimburs != '' )
                {
                        $arfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
                        if ($arfd[$key]['val_kode'] == 'IDR')
                            $arfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
                        else
                            $arfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
                }
                else
                {
                        $arfd[$key]['totalqtyReimburs'] = 0;
                        $arfd[$key]['totalReimburs'] = 0;
                }
        $totalpoarfasfc = (($arfd[$key]['totalPO'] +  $arfd[$key]['totalInARF']) -  $arfd[$key]['totalASFCancel'] ) ;
        $arfd[$key]['totalPoArfAsfc'] = $totalpoarfasfc;

     }

      foreach($arfh as $key => $val)
         {
          if ($val == '""')
              $arfh[$key] = '';
      }
      $tmp2 = $arfh;
      unset($arfh);
      $arfh[0] = $tmp2;
         Zend_Loader::loadClass('Zend_Json');
         $jsonData = Zend_Json::encode($arfd);
         $jsonData2 = Zend_Json::encode($arfh);

         $isCancel = $this->getRequest()->getParam("returnback");
      if ($isCancel)
      {
          $this->view->cancel = true;
          $this->view->json = $this->getRequest()->getParam("posts");
          $this->view->jsonEtc = $this->getRequest()->getParam("etc");
      }
      else
     {
          $this->view->json = $jsonData;
          $this->view->jsonEtc = $jsonData2;
     }
      $this->view->prNo = $tmp;
         $this->view->trano = $trano;
         $this->view->tgl = date('d-m-Y',strtotime($arfh[0]['tgl']));
      $this->view->pr_no = $arfh[0]['pr_no'];
      $this->view->val_kode = $arfh[0]['val_kode'];
      $this->view->request = $arfh[0]['request'];
      $this->view->orangfinance = $arfh[0]['orangfinance'];
      $this->view->ket = $arfh[0]['ket'];

      Zend_Loader::loadClass('Zend_Json');
      $file = Zend_Json::encode($file);
      $this->view->file = $file;
    }

    public function apparfrevisisalesAction ()
    {
         $type = $this->getRequest()->getParam("type");
            $from = $this->getRequest()->getParam("from");
            $sales = $this->getRequest()->getParam("sales");
            $show = $this->getRequest()->getParam("show");
            $this->view->show = $show;

            if ($type != '')
                $this->view->urlBack = '/default/home/showprocessdocument/type/ARFO';
            else
                $this->view->urlBack = '/default/home/showprocessdocument';

            $approve = $this->getRequest()->getParam("approve");


            if ($approve == '')
            {
                $json = $this->getRequest()->getParam("posts");
                $etc = $this->getRequest()->getParam("etc");
                $files = $this->getRequest()->getParam("file");
                $etc = str_replace("\\","",$etc);
                Zend_Loader::loadClass('Zend_Json');
                $jsonData = Zend_Json::decode($json);
                $jsonData2 = Zend_Json::decode($etc);
                $file = Zend_Json::decode($files);

                foreach($jsonData as $key => $val)
   			    {
                    $jsonData[$key]['cfs_kode'] = $val['net_act'];
                    $jsonData[$key]['cfs_nama'] = $val['net_act'];
                    foreach($val as $key2 => $val2)
                    {
                        if ($val2 == "\"\"")
                            $jsonData[$key][$key2] = '';
                        if (strpos($val2,"\"")!== false)
                            $jsonData[$key][$key2] = str_replace("\""," inch",$jsonData[$key][$key2]);
                        if (strpos($val2,"'")!== false)
                            $jsonData[$key][$key2] = str_replace("'"," inch",$jsonData[$key][$key2]);
                    }
   			    }
//             $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
//             $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//             $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                $this->view->result = $jsonData;
                $this->view->etc = $jsonData2;
                $this->view->jsonResult = $json;
                $this->view->jsonFile = $files;
	       	    $this->view->file = $file;

                if ($from == 'edit')
                {
                    $this->view->edit = true;
                }

                if ($sales == 'true')
                {
                    $this->view->sales = true;
                }

            }
            else
            {
                $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

                if ($docs)
                {
                    $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
                    if ($user || $show)
                    {
                        $id = $docs['workflow_trans_id'];
                        $workflowId = $docs['workflow_id'];
                        $approve = $docs['item_id'];
                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $statApprove = $docs['approve'];

                     $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

                        if ($statApprove == $this->const['DOCUMENT_REJECT'])
                            $this->view->reject = true;

                        $potong = substr($approve,0,5);
                        if ($potong == 'ARF01')
                            $this->view->sales = true;

                        $arfd = $this->arfd->fetchAll("trano = '$approve'")->toArray();
                        $arfh = $this->arfh->fetchRow("trano = '$approve'");
                        $file = $this->files->fetchAll("trano = '$approve'");

                        if ($arfd)
                        {
                            foreach($arfd as $key => $val)
                            {
                                $kodeBrg = $val['kode_brg'];
                                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                                if ($barang)
                                {
                                    $arfd[$key]['uom'] = $barang['sat_kode'];
                                }
//                                $arfd[$key]['priceArf'] = $val['priceArf'];
                                    $arfd[$key]['priceArf'] = $val['harga'];
                                    $arfd[$key]['totalARF'] = $val['total'];
                                    $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
                            }

                         $userApp = $this->workflow->getAllApproval($approve);
                         $jsonData2[0]['user_approval'] = $userApp;
                            $jsonData2[0]['prj_kode'] = $arfh['prj_kode'];
                            $jsonData2[0]['prj_nama'] = $arfh['prj_nama'];
                            $jsonData2[0]['sit_kode'] = $arfh['sit_kode'];
                            $jsonData2[0]['sit_nama'] = $arfh['sit_nama'];
                            $jsonData2[0]['budgettype'] = $arfh['budgettype'];
                            $jsonData2[0]['valuta'] = $arfh['val_kode'];
                            $jsonData2[0]['mgr_kode'] = $arfh['request'];
                            $jsonData2[0]['pic_kode'] = $arfh['orangpic'];
                            $jsonData2[0]['ketin'] = $arfh['ketin'];

                            $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                            $jsonData2[0]['pic_nama'] = $picName['Name'];
                            $mgrName = $this->trans->getManagerName($approve);
                            $jsonData[0]['mgr_nama'] = $mgrName;

//                            $cusKode = $this->project->getProjectAndCustomer($arfh['prj_kode']);
//                         $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//                         $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                         $jsonData2[0]['trano'] = $approve;

                            $allReject = $this->workflow->getAllReject($approve);
                            $lastReject = $this->workflow->getLastReject($approve);
                            $this->view->lastReject = $lastReject;
                            $this->view->allReject = $allReject;
                            $this->view->etc = $jsonData2;
                            $this->view->result = $arfd;
                            $this->view->file = $file;
                            $this->view->trano = $approve;
                            $this->view->approve = true;
                            $this->view->uid = $this->session->userName;
                            $this->view->userID = $this->session->idUser;
                            $this->view->docsID = $id;
                        }
                    }
                    else
                    {
                        $this->view->approve = false;
                    }
                }
                else
                {
                    $this->view->approve = false;
                }
            }
    }

    public function getarffinalapproveAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data = $this->getRequest()->getParam("data");
        $type = $this->getRequest()->getParam("type");
        $name = $this->getRequest()->getParam("name");
        
        $search = "";

        if ($name == 'trano')
        {
            $search = " and AH.trano like '%$data%'";
        }else if ($name == 'prj_kode')
        {
            $search = "and AH.prj_kode like '%$data%'";
        }else if ($name == 'sit_kode')
        {
            $search = "and AH.sit_kode like '%$data%'";
        }else
        {
            $search = "";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'AH.trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data = $this->arfrevH->ViewArfFinalApprove($offset,$limit,$dir,$sort,$search,$type);

//        $return['posts'] = $data;
//        $return['count'] = $this->arfrevH->fetchAll()->count();;

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

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

    public function updatefinalarfAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
           $etc = str_replace("\\","",$etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano,'ARF','',$this->const['DOCUMENT_RESUBMIT'],$items,'',false);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');

        if (intval($result) == 305)
        {
            $myUid = $this->session->userName;

            $token = $this->token->getDocumentSignatureByUserID($myUid);
            $tgl = $token['date'];
            $sign = $token['signature'];
            $fetch = $this->workflowTrans->fetchAll("item_id = '$trano'");

            if($fetch)
            {
                $hasil = $fetch->toArray();
                $prjKode = $hasil[0]['prj_kode'];
                Zend_Loader::loadClass('Zend_Json');
                $json = Zend_Json::encode($hasil);

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => $tgl,
                    "prj_kode" => $prjKode,
                    "uid" => $myUid,
                    "uid_requestor" => $myUid,
                    "sign" => $sign,
                    "reason" => 'ARF REVISI',
                    "data" => $json,
                    "ip" => $_SERVER["REMOTE_ADDR"],
                    "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
                );
                $this->creTrans->insert($arrayInsert);
                $this->workflowTrans->delete("item_id = '$trano'");
                $this->workflow->setWorkflowTrans($trano,'ARF','',$this->const['DOCUMENT_SUBMIT'],$items,'',false);
                
            }
        }
        else if (is_array($result) && count($result) > 0)
        {
           $hasil = Zend_Json::encode($result);
           $this->getResponse()->setBody("{success: true, user:$hasil}");
           return false;
        }
        else
        {
            if (is_numeric($result))
            {
                $msg = $this->error->getErrorMsg($result);
                $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
                return false;
            }

        }

        $urut = 1;
        $log['arf-detail-before'] =  $this->arfd->fetchAll("trano = '$trano'")->toArray();
        $this->arfd->delete("trano = '$trano'");
        foreach($jsonData as $key => $val)
        {
            if ($val['val_kode'] == 'IDR')
                $harga = $val['hargaIDR'];
            else
                $harga = $val['hargaUSD'];

             $total = $val['qty'] * $val['priceArf'];
             $arrayInsert = array(
                 "trano" => $trano,
                 "tgl" => date('Y-m-d'),
                 "urut" => $urut,
                 "prj_kode" => $val['prj_kode'],
                 "prj_nama" => $val['prj_nama'],
                 "sit_kode" => $val['sit_kode'],
                 "sit_nama" => $val['sit_nama'],
                 "workid" => $val['workid'],
                 "workname" => $val['workname'],
                 "kode_brg" => $val['kode_brg'],
                 "nama_brg" => $val['nama_brg'],
                 "qty" => $val['qty'],
                 "harga" => $val['priceArf'],
                 "total" => $total,
                 "ket" => $val['ket'],
                 "requester" => $val['requester'],
                 "petugas" => $this->session->userName,
                 "val_kode" => $val['val_kode']
             );
                $urut++;
                $totals = $totals + $total;

             $this->arfd->insert($arrayInsert);
        }
        $log2['arf-detail-after'] =  $this->arfd->fetchAll("trano = '$trano'")->toArray();
        $arrayInsert = array(
            "namabank" => $jsonEtc[0]['bank'],
             "rekbank" => $jsonEtc[0]['bankaccountno'],
             "reknamabank" => $jsonEtc[0]['bankaccountname'],
//                 "val_kode" => $jsonEtc[0]['valuta'],
             "penerima" => $jsonEtc[0]['penerima'],
             "ketin" => $jsonEtc[0]['ketin'],
             "orangfinance" => $jsonEtc[0]['finance'],
             "request" => $jsonEtc[0]['mgr_kode'],
             "orangpic" => $jsonEtc[0]['pic_kode'],
             "total" => $totals,
             "user" => $this->session->userName,
             "tglinput" => date('Y-m-d'),
             "budgettype" => $jsonEtc[0]['budgettype'],
             "jam" =>date('H:i:s'),
            "statrevisi" => 1
        );

        $log['arf-header-before'] =  $this->arfh->fetchRow("trano = '$trano'")->toArray();
        $this->arfh->update($arrayInsert,"trano = '$trano'");
        $log2['arf-header-after'] =  $this->arfh->fetchRow("trano = '$trano'")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array (
              "trano" => $trano,
              "uid" => $this->session->userName,
              "tgl" => date('Y-m-d H:i:s'),
              "prj_kode" => $jsonEtc[0]['prj_kode'],
              "sit_kode" => $jsonEtc[0]['sit_kode'],
              "action" => "REVISI",
              "data_before" => $jsonLog,
              "data_after" => $jsonLog2,
              "ip" => $_SERVER["REMOTE_ADDR"],
              "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);
        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0)
        {
            foreach ($jsonFile as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }
        //buat update tabel arf


        $return = array ("success" => true, "number" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function updatefinalarfoverheadAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
           $etc = str_replace("\\","",$etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano,'ARFO','',$this->const['DOCUMENT_RESUBMIT'],$items,'',false);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');

        if (intval($result) == 305)
        {
            $myUid = $this->session->userName;

            $token = $this->token->getDocumentSignatureByUserID($myUid);
            $tgl = $token['date'];
            $sign = $token['signature'];
            $fetch = $this->workflowTrans->fetchAll("item_id = '$trano'");

            if($fetch)
            {
                $hasil = $fetch->toArray();
                $prjKode = $hasil[0]['prj_kode'];
                Zend_Loader::loadClass('Zend_Json');
                $json = Zend_Json::encode($hasil);

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => $tgl,
                    "prj_kode" => $prjKode,
                    "uid" => $myUid,
                    "uid_requestor" => $myUid,
                    "sign" => $sign,
                    "reason" => 'ARF REVISI',
                    "data" => $json,
                    "ip" => $_SERVER["REMOTE_ADDR"],
                    "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
                );
                $this->creTrans->insert($arrayInsert);
                $this->workflowTrans->delete("item_id = '$trano'");
                $this->workflow->setWorkflowTrans($trano,'ARFO','',$this->const['DOCUMENT_SUBMIT'],$items,'',false);
            }
        }
        else if (is_array($result) && count($result) > 0)
        {
           $hasil = Zend_Json::encode($result);
           $this->getResponse()->setBody("{success: true, user:$hasil}");
           return false;
        }
        else
        {
            if (is_numeric($result))
            {
                $msg = $this->error->getErrorMsg($result);
                $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
                return false;
            }
        }

        $urut = 1;
                $log['arf-detail-before'] =  $this->arfd->fetchAll("trano = '$trano'")->toArray();
                $this->arfd->delete("trano = '$trano'");
                foreach($jsonData as $key => $val)
                {
                    if ($val['val_kode'] == 'IDR')
                        $harga = $val['hargaIDR'];
                    else
                        $harga = $val['hargaUSD'];

                     $total = $val['qty'] * $val['priceArf'];
                     $arrayInsert = array(
                         "trano" => $trano,
                         "tgl" => date('Y-m-d'),
                         "urut" => $urut,
                         "prj_kode" => $val['prj_kode'],
                         "prj_nama" => $val['prj_nama'],
                         "sit_kode" => $val['sit_kode'],
                         "sit_nama" => $val['sit_nama'],
                         "workid" => $val['workid'],
                         "workname" => $val['workname'],
                         "kode_brg" => $val['kode_brg'],
                         "nama_brg" => $val['nama_brg'],
                         "qty" => $val['qty'],
                         "harga" => $val['priceArf'],
                         "total" => $total,
                         "ket" => $val['ket'],
                         "requester" => $val['requester'],
                         "petugas" => $this->session->userName,
                         "val_kode" => $val['val_kode'],
                         "cfs_kode" => $val['net_act'],
                         "tipe" => 'O'
                     );
                        $urut++;
                        $totals = $totals + $total;

                     $this->arfd->insert($arrayInsert);
                }
                $log2['arf-detail-after'] =  $this->arfd->fetchAll("trano = '$trano'")->toArray();
                $arrayInsert = array(
                    "namabank" => $jsonEtc[0]['bank'],
                     "rekbank" => $jsonEtc[0]['bankaccountno'],
                     "reknamabank" => $jsonEtc[0]['bankaccountname'],
    //                 "val_kode" => $jsonEtc[0]['valuta'],
                     "penerima" => $jsonEtc[0]['penerima'],
                     "ketin" => $jsonEtc[0]['ketin'],
                     "orangfinance" => $jsonEtc[0]['finance'],
                     "request" => $jsonEtc[0]['mgr_kode'],
                     "orangpic" => $jsonEtc[0]['pic_kode'],
                     "total" => $totals,
                     "user" => $this->session->userName,
                     "tglinput" => date('Y-m-d'),
                     "budgettype" => $jsonEtc[0]['budgettype'],
                     "jam" =>date('H:i:s'),
                    "statrevisi" => 1
                );

                $log['arf-header-before'] =  $this->arfh->fetchRow("trano = '$trano'")->toArray();
                $this->arfh->update($arrayInsert,"trano = '$trano'");
                $log2['arf-header-after'] =  $this->arfh->fetchRow("trano = '$trano'")->toArray();

                $jsonLog = Zend_Json::encode($log);
                $jsonLog2 = Zend_Json::encode($log2);
                $arrayLog = array (
                      "trano" => $trano,
                      "uid" => $this->session->userName,
                      "tgl" => date('Y-m-d H:i:s'),
                      "prj_kode" => $jsonEtc[0]['prj_kode'],
                      "sit_kode" => $jsonEtc[0]['sit_kode'],
                      "action" => "REVISI",
                      "data_before" => $jsonLog,
                      "data_after" => $jsonLog2,
                      "ip" => $_SERVER["REMOTE_ADDR"],
                      "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
                );
                $this->log->insert($arrayLog);
                $this->files->delete("trano = '$trano'");
                if (count($jsonFile) > 0)
                {
                    foreach ($jsonFile as $key => $val)
                    {
                        $arrayInsert = array (
                            "trano" => $trano,
                            "prj_kode" => $jsonEtc[0]['prj_kode'],
                            "date" => date("Y-m-d H:i:s"),
                            "uid" => $this->session->userName,
                            "filename" => $val['filename'],
                            "savename" => $val['savename']
                        );
                        $this->files->insert($arrayInsert);
                    }
                }


        $return = array ("success" => true, "number" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function updatefinalarfsalesAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
           $etc = str_replace("\\","",$etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano,'ARFO','',$this->const['DOCUMENT_RESUBMIT'],$items,'',false);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');

        if (intval($result) == 305)
        {
            $myUid = $this->session->userName;

            $token = $this->token->getDocumentSignatureByUserID($myUid);
            $tgl = $token['date'];
            $sign = $token['signature'];
            $fetch = $this->workflowTrans->fetchAll("item_id = '$trano'");

            if($fetch)
            {
                $hasil = $fetch->toArray();
                $prjKode = $hasil[0]['prj_kode'];
                Zend_Loader::loadClass('Zend_Json');
                $json = Zend_Json::encode($hasil);

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => $tgl,
                    "prj_kode" => $prjKode,
                    "uid" => $myUid,
                    "uid_requestor" => $myUid,
                    "sign" => $sign,
                    "reason" => 'ARF REVISI',
                    "data" => $json,
                    "ip" => $_SERVER["REMOTE_ADDR"],
                    "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
                );
                $this->creTrans->insert($arrayInsert);
                $this->workflowTrans->delete("item_id = '$trano'");
                $this->workflow->setWorkflowTrans($trano,'ARFO','',$this->const['DOCUMENT_SUBMIT'],$items,'',false);
            }
        }
        else if (is_array($result) && count($result) > 0)
        {
           $hasil = Zend_Json::encode($result);
           $this->getResponse()->setBody("{success: true, user:$hasil}");
           return false;
        }
        else
        {
            if (is_numeric($result))
            {
                $msg = $this->error->getErrorMsg($result);
                $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
                return false;
            }

//            $this->workflow->setWorkflowTrans($trano,'ARFO','',$this->const['DOCUMENT_RESUBMIT'],$items,'',false);
        }


        $urut = 1;
        $log['arf-detail-before'] =  $this->arfd->fetchAll("trano = '$trano'")->toArray();
        $this->arfd->delete("trano = '$trano'");
        foreach($jsonData as $key => $val)
        {
            if ($val['val_kode'] == 'IDR')
                $harga = $val['hargaIDR'];
            else
                $harga = $val['hargaUSD'];

             $total = $val['qty'] * $val['priceArf'];
             $arrayInsert = array(
                 "trano" => $trano,
                 "tgl" => date('Y-m-d'),
                 "urut" => $urut,
                 "prj_kode" => $val['prj_kode'],
                 "prj_nama" => $val['prj_nama'],
                 "sit_kode" => $val['sit_kode'],
                 "sit_nama" => $val['sit_nama'],
                 "workid" => $val['workid'],
                 "workname" => $val['workname'],
                 "kode_brg" => $val['kode_brg'],
                 "nama_brg" => $val['nama_brg'],
                 "qty" => $val['qty'],
                 "harga" => $val['priceArf'],
                 "total" => $total,
                 "ket" => $val['ket'],
                 "requester" => $val['requester'],
                 "petugas" => $this->session->userName,
                 "val_kode" => $val['val_kode'],
                 "cfs_kode" => $val['net_act'],
                 "tipe" => 'S'
             );
                $urut++;
                $totals = $totals + $total;

             $this->arfd->insert($arrayInsert);
        }
        $log2['arf-detail-after'] =  $this->arfd->fetchAll("trano = '$trano'")->toArray();
        $arrayInsert = array(
            "namabank" => $jsonEtc[0]['bank'],
             "rekbank" => $jsonEtc[0]['bankaccountno'],
             "reknamabank" => $jsonEtc[0]['bankaccountname'],
//                 "val_kode" => $jsonEtc[0]['valuta'],
             "penerima" => $jsonEtc[0]['penerima'],
             "ketin" => $jsonEtc[0]['ketin'],
             "orangfinance" => $jsonEtc[0]['finance'],
             "request" => $jsonEtc[0]['mgr_kode'],
             "orangpic" => $jsonEtc[0]['pic_kode'],
             "total" => $totals,
             "user" => $this->session->userName,
             "tglinput" => date('Y-m-d'),
             "budgettype" => $jsonEtc[0]['budgettype'],
             "jam" =>date('H:i:s'),
            "statrevisi" => 1
        );

        $log['arf-header-before'] =  $this->arfh->fetchRow("trano = '$trano'")->toArray();
        $this->arfh->update($arrayInsert,"trano = '$trano'");
        $log2['arf-header-after'] =  $this->arfh->fetchRow("trano = '$trano'")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array (
              "trano" => $trano,
              "uid" => $this->session->userName,
              "tgl" => date('Y-m-d H:i:s'),
              "prj_kode" => $jsonEtc[0]['prj_kode'],
              "sit_kode" => $jsonEtc[0]['sit_kode'],
              "action" => "REVISI",
              "data_before" => $jsonLog,
              "data_after" => $jsonLog2,
              "ip" => $_SERVER["REMOTE_ADDR"],
              "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);
        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0)
        {
            foreach ($jsonFile as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        //buat update tabel arf


        $return = array ("success" => true, "number" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }



}