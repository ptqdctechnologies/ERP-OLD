<?php

class ProjectManagement_AfeController extends Zend_Controller_Action {

    private $db;
    private $session;
    private $request;
    private $const;
    private $projectHelper;
    private $project;
    private $trans;
    private $util;
    private $barang;
    private $workflow;
    private $workflowTrans;
    private $workflowClass;
    private $error;
    private $afe;
    private $afeh;
    private $afes;
    private $budget;
    private $quantity;
    private $log;
    private $cost;
    private $files;
    private $budgt;

    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->request = $this->getRequest();
        $this->const = Zend_Registry::get('constant');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->budget = new Default_Models_Budget();
        $this->session = new Zend_Session_Namespace('login');
        $this->error = $this->_helper->getHelper('error');
        $this->projectHelper = $this->_helper->getHelper('project');
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->trans = $this->_helper->getHelper('transaction');
        $this->afe = new ProjectManagement_Models_AFE();
        $this->afes = new ProjectManagement_Models_AFESaving();
        $this->afeh = new ProjectManagement_Models_AFEh();
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Models_Workflow();
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->log = new Admin_Models_Logtransaction();
        $this->cost = new Default_Models_Cost();
        $this->files = new Default_Models_Files();
        $this->budgt = $this->_helper->getHelper('Budget');
    }

    public function indexAction() {
        
    }

    public function listAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');
        $isSwitching = $request->getParam('is_switching');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        switch ($listType) {

            case 'dorh':
                $logistic = new Default_Models_ProcurementRequestH();
                $return['posts'] = $procurement->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
                $return['count'] = $procurement->fetchAll()->count();
                break;
            case 'nodord':
                $sql = "SELECT * FROM procurement_pointd p order by tgl desc,trano desc limit 1";
                $fetch = $this->db->query($sql);
                $return['posts'] = $fetch->fetch();
                $return['count'] = 1;
                break;
            case 'dord':
                //$trano = $request->getParam('trano');
                //$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_prd WHERE trano='$trano' ORDER BY urut";

                $prj_kode = $request->getParam('prj_kode');
                $sit_kode = $request->getParam('sit_kode');
                $workid = $request->getParam('workid');
                $kode_brg = $request->getParam('kode_brg');

                //$sql = "call sp_boq3pr('$prj_kode','$sit_kode','$workid','$kode_brg')";
                //$fetch = $this->db->query($sql);
                //$return['posts'] = $fetch->fetchAll();
                //$return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
                //break;
                // call store procedure
                $sp = $this->db->prepare("call sp_boq3pr('$prj_kode','$sit_kode','$workid','$kode_brg')");
                $sp->execute();
                $return['posts'] = $sp->fetchAll();
                $return['count'] = count($return['posts']);
                $sp->closeCursor();

                break;
            case 'AFE':
                $where = '';
                if ($isSwitching)
                    $where = " AND a.is_switching = 1";
                else
                    $where = " AND a.is_switching = 0";
                $sql = "SELECT a.* FROM transengineer_afeh a LEFT JOIN transengineer_kboq3h b ON a.trano = b.afe_no WHERE b.afe_no IS NULL AND a.trano LIKE 'AFE-%' $where";
                $fetch = $this->db->query($sql);
                $return['posts'] = $fetch->fetchAll();
                $return['count'] = count($return['posts']);
//                $return['posts'] = $this->afeh->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
//                $return['count'] = $this->afeh->fetchAll()->count();
                break;
        }

        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listbyparamsAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');
        $joinToPod = $request->getParam('joinToPod');
        $isSwitching = $request->getParam('is_switching');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $where = '';
        if ($isSwitching)
            $where = " AND is_switching = 1";
        else
            $where = " AND is_switching = 0";

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_afeh WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' AND trano LIKE \'AFE-%\' ' . $where . ' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function afeAction() {
        
    }

    public function getboq3forafeAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $valKode = $this->getRequest()->getParam("val_kode");
        
        $boq3 = $this->budget->getBOQ3CurrentPerItem($prjKode, $sitKode,$valKode);
        $this->budget->createOnGoingAFE($prjKode);

        //$i = 1;
        $current = 0;
        $limit = count($boq3);
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
                
        $result = array();

        foreach ($boq3 as $key => $val) {
            
            foreach ($val as $key2 => $val2) {
                if ($val2 == "\"\"")
                    $boq3[$key][$key2] = '';
                if (strpos($val2, "\"") !== false)
                    $boq3[$key][$key2] = str_replace("\"", " inch", $boq3[$key][$key2]);
                if (strpos($val2, "'") !== false)
                    $boq3[$key][$key2] = str_replace("'", " inch", $boq3[$key][$key2]);
            }

            $boq3[$key]['id'] = $val['id'];
            $boq3[$key]['uom'] = $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']);
            $boq3[$key]['nama_brg'] = str_replace("\"", "'", $boq3[$key]['nama_brg']);
            $boq3[$key]['price'] = $val['harga'];
            
            $boq3[$key]['totalPrice'] = $val['val_kode']=='USD' ? $val['totalUSD'] : $val['totalIDR'];
            
            // Get All Related OCA
            $oca = $this->cost->totalOCA($prjKode, $sitKode,$val['workid'], $val['kode_brg']);
            $totalOCA= $val['val_kode']=='USD' ? $oca['totalUSD'] : $oca['totalIDR'];
            $boq3[$key]['totalOCA'] = $totalOCA;
            
            // Get All Requests of The Item PR + ARF - (Material Return + ASF Cancel)
            $requests=$this->cost->totalRequests($prjKode, $sitKode,$val['workid'], $val['kode_brg']);
            $totalRequest= $val['val_kode']=='USD' ? $requests['totalUSD'] : $requests['totalIDR'];
            
            $boq3[$key]['totalRequests']=$totalRequest;
            
            // Get On Going AFE
            $afe= $this->budget->getOnGoingAFE($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg'],$boq3[$key]['val_kode']);
            $boq3[$key]['tranoAFE']=$afe['trano']==null ? '' : $afe['trano'];
            $boq3[$key]['totalAFE']=$afe['total']==null ? 0 : $afe['total'];
            
            // Get PO
            $cusorder = $this->trans->getPOCustomer($prjKode, $sitKode);
            $boq3[$key]['pocustomer'] = $cusorder['pocustomer'];
            if ($boq3[$key]['val_kode'] == 'IDR')
                $boq3[$key]['totalpocustomer'] = intval($cusorder['total']);
            else
                $boq3[$key]['totalpocustomer'] = intval($cusorder['totalusd']);
            
            if ($current < ($limit + $offset) && $current >= $offset) {
              $result[] = $boq3[$key];
            }
            
            $current++;
            //$i++; 
        }

        $return['posts'] = $result;
        $return['count'] = count($boq3);
        $json = Zend_Json::encode($return);
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function oldaddafeAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
            $this->view->etc = $this->getRequest()->getParam("etc");
        }
    }
    
    public function addafeAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->jsonFile = $this->getRequest()->getParam("file");
        }
    }

    public function oldeditafeAction() {
        $trano = $this->getRequest()->getParam("trano");


        $afe = $this->afe->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $afes = $this->afes->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $afeh = $this->afeh->fetchRow("trano = '$trano'");

        if ($afe) {
            foreach ($afe as $key => $val) {
                $afe[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $prjKode = $val['prj_kode'];
                $sitKode = $val['sit_kode'];
                $workid = $val['workid'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");

                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $afe[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $afe[$key][$key2] = str_replace("\"", " inch", $afe[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $afe[$key][$key2] = str_replace("'", " inch", $afe[$key][$key2]);
                }

                if ($barang) {
                    $afe[$key]['uom'] = $barang['sat_kode'];
                }
                $cekExist = $this->budget->getBoq3ByOne($prjKode, $sitKode, $workid, $kodeBrg);

                if (!$cekExist) {
                    $afe[$key]['type'] = "new";
                } else
                    $afe[$key]['type'] = "additional";

                $pr = $this->quantity->getPrQuantityLast($prjKode, $sitKode, $workid, $kodeBrg);

                if ($pr != '') {
                    $harga = $pr['harga'];
                    $qty = $pr['qty'];
                } else {
                    $harga = floatval($val['harga']);
                    $qty = floatval($val['qty']);
                }
                $afe[$key]['qty'] = $val['qtybaru'];
                $afe[$key]['price'] = $val['hargabaru'];
                $afe[$key]['priceori'] = $harga;
                $afe[$key]['qtyori'] = $qty;
            }
        } else
            $afe = array();

        if ($afes) {
            foreach ($afes as $key => $val) {
                $afes[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $workid = $val['workid'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $afes[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $afes[$key][$key2] = str_replace("\"", " inch", $afes[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $afes[$key][$key2] = str_replace("'", " inch", $afes[$key][$key2]);
                }
                if ($barang) {
                    $afes[$key]['uom'] = $barang['sat_kode'];
                }

                $po = $this->quantity->getPoQuantity($prjKode, $sitKode, $workid, $kodeBrg);
                if ($po == '') {
                    $po['qty'] = 0;
                    $po['totalIDR'] = 0;
                    $po['totalUSD'] = 0;
                }
                $asfdd = $this->quantity->getAsfddQuantity($prjKode, $sitKode, $workid, $kodeBrg);
                if ($asfdd == '') {
                    $asfdd['qty'] = 0;
                    $asfdd['totalIDR'] = 0;
                    $asfdd['totalUSD'] = 0;
                }
                $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $workid, $kodeBrg);
                if ($arf == '') {
                    $arf['qty'] = 0;
                    $arf['totalIDR'] = 0;
                    $arf['totalUSD'] = 0;
                }
                $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $workid, $kodeBrg);
                if ($asfcancel == '') {
                    $asfcancel['qty'] = 0;
                    $asfcancel['totalIDR'] = 0;
                    $asfcancel['totalUSD'] = 0;
                }
                $pr = $this->quantity->getPrQuantity($prjKode, $sitKode, $workid, $kodeBrg);
                if ($pr == '') {
                    $pr['qty'] = 0;
                    $pr['totalIDR'] = 0;
                    $pr['totalUSD'] = 0;
                }
                $pmeal = $this->quantity->getPmealQuantity($prjKode, $sitKode, $kodeBrg);
                if ($pmeal == '') {
                    $pmeal['qty'] = 0;
                }

                $afes[$key]['totalPurchase'] = floatval($po['qty']) + floatval($asfdd['qty']);
                $afes[$key]['totalPR'] = floatval($pmeal['qty']) + floatval($pr['qty']) + (floatval($arf['qty']) - floatval($asfcancel['qty']));
                if ($afes[$key]['val_kode'] == 'IDR') {
                    $afes[$key]['totalPricePurchase'] = floatval($po['totalIDR']) + (floatval($arf['totalIDR']) - floatval($asfcancel['totalIDR']));
                    $afes[$key]['totalPricePR'] = floatval($pr['totalIDR']) + (floatval($arf['totalIDR']) - floatval($asfcancel['totalIDR']));
                } else {

                    $afes[$key]['totalPricePurchase'] = floatval($po['totalHargaUSD']) + (floatval($arf['totalHargaUSD']) - floatval($asfcancel['totalHargaUSD']));
                    $afes[$key]['totalPricePR'] = floatval($pr['totalHargaUSD']) + (floatval($arf['totalHargaUSD']) - floatval($asfcancel['totalHargaUSD']));
                }

                $afes[$key]['type'] = "saving";
                $afes[$key]['qty'] = $val['qtybaru'];
                $afes[$key]['price'] = $val['hargabaru'];
                $afes[$key]['priceori'] = $val['harga'];
                $afes[$key]['qtyori'] = $val['qty'];
            }
        } else {
            $afes = array();
        }

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($afe);
        $jsonData2 = Zend_Json::encode($afes);

        $isCancel = $this->getRequest()->getParam("returnback");

        if ($isCancel) {
            
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
            $this->view->etc = $this->getRequest()->getParam("etc");
            
        } else {
            
            $this->view->json2 = $jsonData2;
            $this->view->json = $jsonData;
            
        }

        $this->view->trano = $trano;
        $this->view->tgl = $afeh['tgl'];

        $this->view->ket = $afeh['ket'];
        $this->view->prjKode = $afeh['prj_kode'];
        $this->view->prjNama = $afeh['prj_nama'];
        $this->view->sitKode = $afeh['sit_kode'];
        $this->view->sitNama = $afeh['sit_nama'];
        $this->view->valKode = $afeh['val_kode'];
        $this->view->addRev = $afeh['addrevenue'];
        $this->view->pocustomer = $afeh['pocustomer'];
        $this->view->totalpocustomer = $afeh['totalpocustomer'];
        $this->view->rateidr = $afeh['rateidr'];
    }
    
    public function editafeAction() {
        
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");

        if ($isCancel) {
            
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->jsonFile = $this->getRequest()->getParam("file");
            
        } else {

            $afe = $this->afe->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
            $afes = $this->afes->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
            $afeh = $this->afeh->fetchRow("trano = '$trano'");
            $files = $this->files->fetchAll("trano = '$trano'")->toArray();
            $afesTemp = array();
            $afeTemp = array();
            $etcTemp= array();
            $fileTemp = array();

            if ($afe) {
                
                foreach ($afe as $key => $val) {
                
                    foreach ($val as $key2 => $val2) {
                        if ($val2 == "\"\"")
                            $afe[$key][$key2] = '';
                        if (strpos($val2, "\"") !== false)
                            $afe[$key][$key2] = str_replace("\"", " inch", $afe[$key][$key2]);
                        if (strpos($val2, "'") !== false)
                            $afe[$key][$key2] = str_replace("'", " inch", $afe[$key][$key2]);
                    }
                    
//                    $barang = $this->barang->fetchRow("kode_brg = '"+$val['kode_brg']+"'");
                    $kodeBrg = $val['kode_brg'];
                    $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                
                    $afeTemp[$key]['workid']=$val['workid'];
                    $afeTemp[$key]['workname']=$val['workname'];
                    $afeTemp[$key]['prj_kode']=$val['prj_kode'];
                    $afeTemp[$key]['sit_kode']=$val['sit_kode'];
                    $afeTemp[$key]['prj_nama']=$val['prj_nama'];
                    $afeTemp[$key]['sit_nama']=$val['sit_nama'];
                    $afeTemp[$key]['kode_brg']=$val['kode_brg'];
                    $afeTemp[$key]['nama_brg']=$val['nama_brg'];
                    $afeTemp[$key]['cfs_kode']=$val['cfs_kode'];
                    $afeTemp[$key]['cfs_nama']=$val['cfs_nama'];
                    $afeTemp[$key]['qty'] = $val['qtybaru'];
                    $afeTemp[$key]['price'] = $val['hargabaru'];
                    $afeTemp[$key]['totalPrice']=$val['jumlahbaru'];
                    $afeTemp[$key]['uom']=$barang['sat_kode'];
                    $afeTemp[$key]['val_kode']=$val['val_kode'];
                    $afeTemp[$key]['ket']=$val['ket'];
                    $afeTemp[$key]['type'] = 'additional';
                    $afeTemp[$key]['qtyori']=$val['qty'];
                    $afeTemp[$key]['priceori']=$val['harga'];
                    $afeTemp[$key]['pocustomer']=$val['pocustomer'];
                    $afeTemp[$key]['totalpocustomer']=$val['totalpocustomer'];
                    $afeTemp[$key]['id']=$val['id'];
         
                }

            } 

            if ($afes) {
                
                foreach ($afes as $key => $val) {
               
                    foreach ($val as $key2 => $val2) {
                        if ($val2 == "\"\"")
                            $afes[$key][$key2] = '';
                        if (strpos($val2, "\"") !== false)
                            $afes[$key][$key2] = str_replace("\"", " inch", $afes[$key][$key2]);
                        if (strpos($val2, "'") !== false)
                            $afes[$key][$key2] = str_replace("'", " inch", $afes[$key][$key2]);
                    }
                    
//                    $barang = $this->barang->fetchRow("kode_brg = '"+$val['kode_brg']+"'");
                    $kodeBrg = $val['kode_brg'];
                    $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                    
                    $afesTemp[$key]['workid']=$val['workid'];
                    $afesTemp[$key]['workname']=$val['workname'];
                    $afesTemp[$key]['prj_kode']=$val['prj_kode'];
                    $afesTemp[$key]['sit_kode']=$val['sit_kode'];
                    $afesTemp[$key]['prj_nama']=$val['prj_nama'];
                    $afesTemp[$key]['sit_nama']=$val['sit_nama'];
                    $afesTemp[$key]['kode_brg']=$val['kode_brg'];
                    $afesTemp[$key]['nama_brg']=$val['nama_brg'];
                    $afesTemp[$key]['cfs_kode']=$val['cfs_kode'];
                    $afesTemp[$key]['cfs_nama']=$val['cfs_nama'];
                    $afesTemp[$key]['qty'] = $val['qtybaru'];
                    $afesTemp[$key]['price'] = $val['hargabaru'];
                    $afesTemp[$key]['totalPrice']=$val['jumlahbaru'];
                    $afesTemp[$key]['uom']=$barang['sat_kode'];
                    $afesTemp[$key]['val_kode']=$val['val_kode'];
                    $afesTemp[$key]['ket']=$val['ket'];
                    $afesTemp[$key]['type'] = "saving";
                    $afesTemp[$key]['qtyori']=$val['qty'];
                    $afesTemp[$key]['priceori']=$val['harga'];
                    $afesTemp[$key]['pocustomer']=$val['pocustomer'];
                    $afesTemp[$key]['totalpocustomer']=$val['totalpocustomer'];
                    $afesTemp[$key]['id']=$val['id'];
                }

            }
            
            if($files){

                foreach ($files as $key => $val) {
                    
                    $fileTemp[$key ]['id']=$val['id'];
                    $fileTemp[$key ]['filename']=$val['filename'];
                    $fileTemp[$key ]['savename']=$val['savename'];
                    $fileTemp[$key ]['status']='';
                    $fileTemp[$key ]['path']=Zend_Registry::get('uploadPath');

                }
                
            }

            $etcTemp[0]['ket'] = $afeh['ket'];
            $etcTemp[0]['prj_kode'] = $afeh['prj_kode'];
            $etcTemp[0]['prj_nama'] = $afeh['prj_nama'];
            $etcTemp[0]['sit_kode'] = $afeh['sit_kode'];
            $etcTemp[0]['sit_nama'] = $afeh['sit_nama'];
            $etcTemp[0]['val_kode'] = $afeh['val_kode'];
            $etcTemp[0]['add_rev'] = $afeh['addrevenue'];
            $etcTemp[0]['pocustomer'] = $afeh['pocustomer'];
            $etcTemp[0]['totalpocustomer'] = $afeh['totalpocustomer'];
            $etcTemp[0]['rateidr'] = $afeh['rateidr'];
            $etcTemp[0]['trano'] = $trano;
            $etcTemp[0]['approve'] = $afeh['approve'];
        
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($afeTemp);
            $jsonData2 = Zend_Json::encode($afesTemp);
            $etc = Zend_Json::encode($etcTemp);
            $file= Zend_Json::encode($fileTemp);
            
            $this->view->json2 = $jsonData2;
            $this->view->json = $jsonData;
            $this->view->etc = $etc;
            $this->view->jsonFile = $file;
        }
  
    }

    private function progress($prjKode, $sitKode, $finalDate) {

        //Progress
        $progress = new ProjectManagement_Models_ProjectProgress();
        $site = $progress->getSiteProgressV2($prjKode, $sitKode, '1999-01-01', $finalDate);
        return $site['progress'];
    }

    private function recordedCost($recorded_cost) {
        
        foreach($recorded_cost AS $index => $data)
        {
            $cost['mip_currentIDR'] += $data['val_kode']=='IDR' ? $data['amount'] : 0;
            $cost['mip_currentHargaUSD'] += $data['val_kode'] =='USD' ? $data['amount'] : 0;
            $cost['mip_currentHargaUSDRate'] += $data['val_kode'] =='USD' ? $data['total'] : 0;

//            $cost['salary_IDR']+= $data['kategori']=='Salary' && $data['val_kode']=='IDR'? $data['amount']:0;
//            $cost['salary_USD'] += 0;
//            
//            $cost['rpi_approved_IDR'] += $data['kategori']=='RPI' && $data['val_kode']=='IDR'? $data['amount']:0;
//            $cost['rpi_approved_USD']  += $data['kategori']=='RPI' && $data['val_kode']=='USD'? $data['amount']:0;
//            
//            $cost['pmeal_IDR'] += $data['kategori']=='Piecemeal' && $data['val_kode']=='IDR'? $data['amount']:0;
//            $cost['pmeal_USD'] += 0;
//            
//            $cost['material_return_IDR'] += $data['kategori']=='Material Return' && $data['val_kode']=='IDR'? abs($data['amount']):0;
//            $cost['material_return_USD'] += $data['kategori']=='Material Return' && $data['val_kode']=='USD'? abs($data['amount']):0;
//            
//            $doIDR  += $data['kategori']=='DO-PO(DO)' && $data['val_kode']=='IDR'? $data['amount']:0;
//            $doUSD += $data['kategori']=='DO-PO(DO)' && $data['val_kode']=='USD'? $data['amount']:0;
//            
//            $poIDR += $data['kategori']=='DO-PO(PO)' && $data['val_kode']=='IDR'? $data['amount']:0;
//            $poUSD += $data['kategori']=='DO-PO(PO)' && $data['val_kode']=='USD'? $data['amount']:0;
//
//            $asfIDR  += $data['kategori']=='ASF/BSF' && $data['val_kode']=='IDR'? $data['amount']:0;
//            $asfUSD +=  $data['kategori']=='ASF/BSF' && $data['val_kode']=='USD'? $data['amount']:0;
//            
//            $asfcIDR  += $data['kategori']=='CANCELED ASF/BSF' && $data['val_kode']=='IDR'? $data['amount']:0;
//            $asfcUSD +=  $data['kategori']=='CANCELED ASF/BSF' && $data['val_kode']=='USD'? $data['amount']:0;
        }
        
//        $cost['fdopoIDR'] = $doIDR + $poIDR;
//        $cost['fdopoUSD'] = $doUSD + $poUSD;
//
//        $cost['asf_IDR']  = $asfIDR + $asfcIDR;
//        $cost['asf_USD'] =  $asfUSD + $asfcUSD;

        return $cost;
    }

    public function appafeAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $finalDate = $this->getRequest()->getParam("finalDate") == '' ? date('Y-m-d H:i:s') : $this->getRequest()->getParam("finalDate");
        $lastReject=array();

        $this->view->finalDate = $finalDate;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/AFE';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $trano  = $this->getRequest()->getParam("trano");
        $approve = $this->getRequest()->getParam("approve");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $json2 = $this->getRequest()->getParam("posts2");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $jsonData3 = Zend_Json::decode($json2);
            $file = Zend_Json::decode($files);

            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $cus_kode = $cusKode[0]['cus_kode'];
            $cus_nama = $cusKode[0]['cus_kode'];

            if ($jsonData2[0]['rateidr'] == '' || $jsonData2[0]['rateidr'] == 0) {
                $utility = $this->_helper->getHelper('utility');
                $jsonData2[0]['rateidr'] = $utility->getExchangeRate();
            }

            $jsonData2[0]["add_rev"] = intval($jsonData2[0]["add_rev"]);
            $jsonData2[0]["totalpocustomer"] = intval($jsonData2[0]["totalpocustomer"]);

            $stsoverhead = $this->trans->getSiteOverhead($jsonData2[0]['sit_kode'], $jsonData2[0]['prj_kode']);
            $stsoverhead = $stsoverhead['stsoverhead'];

            if ($stsoverhead == 'Y') {
                $this->view->stsoverhead = true;
            }

            $boq2ORI = $this->budget->getBoq2AFE($jsonData2[0]['prj_kode'], $jsonData2[0]['sit_kode']);
            $kboq2 = $this->budget->getKBoq2AFE($jsonData2[0]['prj_kode'], $jsonData2[0]['sit_kode'], $finalDate);

            $totalOriIDR = floatval($boq2ORI['totalIDR']);
            $totalCurrentIDR = $totalOriIDR + floatval($kboq2['totalIDR']);
            $totalOriUSD = floatval($boq2ORI['totalUSD']);
            $totalOriUSDinIDR = floatval($boq2ORI['totalUSDinIDR']);
            $totalCurrentUSD = floatval($totalOriUSD + $kboq2['totalUSD']);

            $boq3_ori = $this->budget->getBoq3AFE($jsonData2[0]['prj_kode'], $jsonData2[0]['sit_kode']);
            $boq3_current = $this->budget->getBOQ3Current($jsonData2[0]['prj_kode'], $jsonData2[0]['sit_kode'],$finalDate);

            $totalBoq3_oriIDR = floatval($boq3_ori['totalIDR']);
            $totalBoq3_currentIDR = floatval($boq3_current['totalIDR']);
            $totalBoq3_oriUSD = floatval($boq3_ori['totalUSD']);
            $totalBoq3_currentUSD = floatval($boq3_current['totalUSD']);
            $totalBoq3_oriUSDRate = floatval($boq3_ori['totalUSDRate']);

            //Tarik Data Recorded Cost
            $recorded_cost = $this->cost->recordedCostPerDate($jsonData2[0]['prj_kode'], $jsonData2[0]['sit_kode'],'1970-01-01',$finalDate);
                    
            //$mip = $this->budget->getMIP($afeh['prj_kode'],$afeh['sit_kode']);
            $mip = $this->recordedCost($recorded_cost);
            
            //Get Current Budget
            $currentBuget = $this->budgt->getCurrentBudget($boq3_current, $recorded_cost,$jsonData2[0]['rateidr']);
                    
            //$mip = $this->recordedCost($jsonData2[0]['prj_kode'], $jsonData2[0]['sit_kode'], $finalDate);
            $progress = $this->progress($jsonData2[0]['prj_kode'], $jsonData2[0]['sit_kode'], $finalDate) / 100;

            $afesIDR = 0;
            $afeIDR = 0;
            $afesUSD = 0;
            $afeUSD = 0;
            
            foreach ($jsonData as $index => $val) {
                if ($val['val_kode'] == 'IDR') {
                    $afeIDR +=($val['qty'] * $val['price'] - $val['qtyori'] * $val['priceori']);
                }

                if ($val['val_kode'] == 'USD') {
                    $afeUSD +=($val['qty'] * $val['price'] - $val['qtyori'] * $val['priceori']);
                }
            }

            foreach ($jsonData3 as $index => $val) {
                if ($val['val_kode'] == 'IDR') {
                    $afesIDR +=($val['qty'] * $val['price'] - $val['qtyori'] * $val['priceori']);
                }

                if ($val['val_kode'] == 'USD') {
                    $afesUSD +=($val['qty'] * $val['price'] - $val['qtyori'] * $val['priceori']);
                }
            }


            $jsonData2[0]['afeValIDR'] = $afesIDR + $afeIDR;
            $jsonData2[0]['afeValUSD'] = $afesUSD + $afeUSD;

            $jsonData2[0]['cus_kode'] = $cus_kode;
            $jsonData2[0]['cus_nama'] = $cus_nama;
            $jsonData2[0]['boq2_oriIDR'] = $totalOriIDR;
            $jsonData2[0]['boq2_currentIDR'] = $totalCurrentIDR;

            $jsonData2[0]['boq3_oriIDR'] = $totalBoq3_oriIDR;
            $jsonData2[0]['boq3_currentIDR'] = $totalBoq3_currentIDR;
            $jsonData2[0]['boq2_oriUSD'] = $totalOriUSD;
            $jsonData2[0]['boq2_oriUSDinIDR'] = $totalOriUSDinIDR;
            
            $jsonData2[0]['boq2_currentUSD'] = $totalCurrentUSD;
            $jsonData2[0]['boq3_oriUSD'] = $totalBoq3_oriUSD;
            $jsonData2[0]['boq3_currentUSD'] = $totalBoq3_currentUSD;
            $jsonData2[0]['boq3_oriUSDRate'] = $totalBoq3_oriUSDRate;
            $jsonData2[0]['current_budget'] = $currentBuget;

            $jsonData2[0]['mip'] = $mip;

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->result2 = $jsonData3;
            $this->view->jsonResult = $json;
            $this->view->jsonResult2 = $json2;
            $this->view->progress = $progress;
            $this->view->file = $file;
            $this->view->jsonFile = $files;
            $this->view->tglAfe = date("Y-m-d");

            if ($from == 'edit') {
                $trano = $jsonData2['trano'];
                $this->view->trano = $trano;
                $this->view->edit = true;
            }
        } else {

            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $finalDate = date('Y-m-d H:i:s');

                    $id = $docs['workflow_trans_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    if ($statApprove != $this->const['DOCUMENT_FINAL'])
                        $checkCurrent = true;
                    if ($statApprove == $this->const['DOCUMENT_FINAL'])
                        $finalDate = date("Y-m-d H:i:s", strtotime($docs['date']));
                 
                    $this->view->finalDate = $finalDate;

                    $afe = $this->afe->fetchAll("trano = '$approve'")->toArray();
                    $afes = $this->afes->fetchAll("trano = '$approve'")->toArray();
                    $afeh = $this->afeh->fetchRow("trano = '$approve'");

                    $file = $this->files->fetchAll("trano = '$approve'");

                    $budget = new Default_Models_Budget();
                    $warnMsg = "<br>Possibility : This AFE is not Up-to-date.";

                    $afeIDR = 0;
                    $afeUSD = 0;
                    
                    if ($afe) {
                        foreach ($afe as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $afe[$key]['uom'] = $barang['sat_kode'];
                            }

                            $afe[$key]['qty'] = floatval($val['qtybaru']);
                            $afe[$key]['price'] = floatval($val['hargabaru']);
                            $afe[$key]['priceori'] = floatval($val['harga']);
                            $afe[$key]['qtyori'] = floatval($val['qty']);

                            if ($val['val_kode'] == 'IDR') {
                                $afeIDR +=$val['qtybaru'] * $val['hargabaru'] - $val['qty'] * $val['harga'];
                            }

                            if ($val['val_kode'] == 'USD') {
                                $afeUSD +=$val['qtybaru'] * $val['hargabaru'] - $val['qty'] * $val['harga'];
                            }

                            $afe[$key]['invalid'] = false;

                            if ($checkCurrent) {
                                $current = $budget->getBoq3ByOne($val['prj_kode'], $val['sit_kode'], $val['workid'], $val['kode_brg']);

                                if ($current) {
                                    $qtyBaru = floatval($val['qtybaru']);
                                    $hargaBaru = floatval($val['hargabaru']);
                                    $totalBaru = $qtyBaru * $hargaBaru;


                                    $qtyCurrent = $current['qty'];
                                    if ($current['val_kode'] == 'IDR')
                                        $hargaCurrent = $current['hargaIDR'];
                                    else
                                        $hargaCurrent = $current['hargaUSD'];
                                    $totalCurrent = $qtyCurrent * $hargaCurrent;

                                    if ($budget->isWorkidMsc($val['workid'])) {
                                        if (bccomp($totalBaru, $totalCurrent, 2) < 0) {
                                            $afe[$key]['invalid'] = true;
                                            $afe[$key]['invalid_msg'] = "Total Request for item above is lower than Current BOQ3 ( " . $val['val_kode'] . " " . number_format($totalCurrent, 2) . " )";
                                        }
                                    } else {
                                        if (bccomp($qtyBaru, $qtyCurrent, 4) < 0) {
                                            $afe[$key]['invalid'] = true;
                                            $afe[$key]['invalid_msg'] = "Qty Request for item above is lower than Current BOQ3 ( " . number_format($qtyCurrent, 4) . " )";
                                        }
                                        if (bccomp($hargaBaru, $hargaCurrent, 2) < 0) {
                                            $afe[$key]['invalid'] = true;
                                            $afe[$key]['invalid_msg'] = "Price Request for item above is lower than Current BOQ3 ( " . $val['val_kode'] . " " . number_format($hargaCurrent, 2) . " )";
                                        }
                                    }

                                    if ($afe[$key]['invalid_msg'])
                                        $afe[$key]['invalid_msg'] .= $warnMsg . " AFE created date : " . date("d M Y", strtotime($afeh['tgl'])) . ", Current BOQ3 date : " . date("d M Y", strtotime($current['tgl']));
                                }
                            }
                        }
                    }
                    $afesIDR = 0;
                    $afesUSD = 0;
                    if ($afes) {
                        foreach ($afes as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $afes[$key]['uom'] = $barang['sat_kode'];
                            }

                            $afes[$key]['qty'] = floatval($val['qtybaru']);
                            $afes[$key]['price'] = floatval($val['hargabaru']);
                            $afes[$key]['priceori'] = floatval($val['harga']);
                            $afes[$key]['qtyori'] = floatval($val['qty']);

                            if ($val['val_kode'] == 'IDR') {
                                $afesIDR +=$val['qtybaru'] * $val['hargabaru'] - $val['qty'] * $val['harga'];
                            }

                            if ($val['val_kode'] == 'USD') {
                                $afesUSD +=$val['qtybaru'] * $val['hargabaru'] - $val['qty'] * $val['harga'];
                            }

                            $afes[$key]['invalid'] = false;

                            if ($checkCurrent) {
                                $current = $budget->getBoq3ByOne($val['prj_kode'], $val['sit_kode'], $val['workid'], $val['kode_brg']);

                                if ($current) {
                                    $qtyBaru = floatval($val['qtybaru']);
                                    $hargaBaru = floatval($val['hargabaru']);
                                    $totalBaru = $qtyBaru * $hargaBaru;


                                    $qtyCurrent = $current['qty'];
                                    if ($current['val_kode'] == 'IDR')
                                        $hargaCurrent = $current['hargaIDR'];
                                    else
                                        $hargaCurrent = $current['hargaUSD'];

                                    $totalCurrent = $qtyCurrent * $hargaCurrent;
                                    if ($budget->isWorkidMsc($val['workid'])) {
                                        if (bccomp($totalBaru, $totalCurrent, 2) > 0) {
                                            $afes[$key]['invalid'] = true;
                                            $afes[$key]['invalid_msg'] = "Total Request for item above is higher than Current BOQ3 ( " . $val['val_kode'] . " " . number_format($totalCurrent, 2) . " )";
                                        }
                                    } else {
                                        if (bccomp($qtyBaru, $qtyCurrent, 4) > 0) {
                                            $afes[$key]['invalid'] = true;
                                            $afes[$key]['invalid_msg'] = "Qty Request for item above is higher than Current BOQ3 ( " . number_format($qtyCurrent, 4) . " )";
                                        }
                                        if (bccomp($hargaBaru, $hargaCurrent, 2) > 0) {
                                            $afes[$key]['invalid'] = true;
                                            $afes[$key]['invalid_msg'] = "Price Request for item above is higher than Current BOQ3 ( " . $val['val_kode'] . " " . number_format($hargaCurrent, 2) . " )";
                                        }
                                    }
                                }

                                if ($afes[$key]['invalid_msg'])
                                    $afes[$key]['invalid_msg'] .= $warnMsg . " AFE created date : " . date("d M Y", strtotime($afeh['tgl'])) . ", Current BOQ3 date : " . date("d M Y", strtotime($current['tgl']));
                            }
                        }
                    }
                    $cusKode = $this->project->getProjectAndCustomer($afeh['prj_kode']);
                    $cus_kode = $cusKode[0]['cus_kode'];
                    $cus_nama = $cusKode[0]['cus_kode'];

                    $jsonData2[0]["add_rev"] = floatval($afeh['addrevenue']);
                    $jsonData2[0]["totalpocustomer"] = floatval($afeh['totalpocustomer']);

                    $stsoverhead = $this->trans->getSiteOverhead($afeh['sit_kode'], $afeh['prj_kode']);
                    $stsoverhead = $stsoverhead['stsoverhead'];

                    if ($stsoverhead == 'Y') {
                        $this->view->stsoverhead = true;
                    }


                    $boq2ORI = $this->budget->getBoq2AFE($afeh['prj_kode'], $afeh['sit_kode']);
                    $kboq2 = $this->budget->getKBoq2AFE($afeh['prj_kode'], $afeh['sit_kode'], $finalDate);

                    $totalOriIDR = floatval($boq2ORI['totalIDR']);
                    $totalCurrentIDR = $totalOriIDR + floatval($kboq2['totalIDR']);
                    $totalOriUSD = floatval($boq2ORI['totalUSD']);
                    $totalOriUSDinIDR = floatval($boq2ORI['totalUSDinIDR']);
                    $totalCurrentUSD = floatval($totalOriUSD + $kboq2['totalUSD']);
                    
                    $boq3_ori = $this->budget->getBoq3AFE($afeh['prj_kode'], $afeh['sit_kode']);
                    $boq3_current = $this->budget->getBOQ3Current($afeh['prj_kode'], $afeh['sit_kode'],$finalDate, $approve);
                    
                    $totalBoq3_oriIDR = floatval($boq3_ori['totalIDR']);
                    $totalBoq3_currentIDR = floatval($boq3_current['totalIDR']);// + $boq3_ori['totalIDR']);
                    $totalBoq3_oriUSD = floatval($boq3_ori['totalUSD']);
                    $totalBoq3_currentUSD = floatval($boq3_current['totalUSD']);// + $boq3_ori['totalUSD']);
                    $totalBoq3_oriUSDRate = floatval($boq3_ori['totalUSDRate']);
                    //Tarik Data Recorded Cost
                    $recorded_cost = $this->cost->recordedCostPerDate($afeh['prj_kode'], $afeh['sit_kode'],'1970-01-01',$finalDate);
                    //$mip = $this->budget->getMIP($afeh['prj_kode'],$afeh['sit_kode']);
                    $mip = $this->recordedCost($recorded_cost);
                    
                    //Get Current Budget
                    $currentBuget = $this->budgt->getCurrentBudget($boq3_current, $recorded_cost,$afeh['rateidr']);

                    $progress = $this->progress($afeh['prj_kode'], $afeh['sit_kode'], $finalDate) / 100;
                    
                    $jsonData2[0]['afeValIDR'] = $afesIDR + $afeIDR;
                    $jsonData2[0]['afeValUSD'] = $afesUSD + $afeUSD;

                    $jsonData2[0]['cus_kode'] = intval($cus_kode);
                    $jsonData2[0]['cus_nama'] = intval($cus_nama);

                    $jsonData2[0]['boq2_oriIDR'] = $totalOriIDR;
                    $jsonData2[0]['boq2_currentIDR'] = floatval($totalCurrentIDR);
                    $jsonData2[0]['boq3_oriIDR'] = floatval($totalBoq3_oriIDR);
                    $jsonData2[0]['boq3_currentIDR'] = floatval($totalBoq3_currentIDR);
                    
                    $jsonData2[0]['boq2_rateidr'] = floatval($boq2ORI['rateidr']);
                    $jsonData2[0]['boq3_rateidr'] = floatval($boq3_ori['rateidr']);
                    $jsonData2[0]['boq2_oriUSD'] = $totalOriUSD;
                    $jsonData2[0]['boq2_oriUSDinIDR'] = $totalOriUSDinIDR;
                    $jsonData2[0]['boq2_currentUSD'] = floatval($totalCurrentUSD);
                    $jsonData2[0]['boq3_oriUSD'] = floatval($totalBoq3_oriUSD);
                    $jsonData2[0]['boq3_currentUSD'] = floatval($totalBoq3_currentUSD);
                    $jsonData2[0]['boq3_oriUSDRate'] = floatval($totalBoq3_oriUSDRate);

                    $jsonData2[0]['mip'] = $mip;

                    $jsonData2[0]['prj_kode'] = $afeh['prj_kode'];
                    $jsonData2[0]['prj_nama'] = $afeh['prj_nama'];
                    $jsonData2[0]['sit_kode'] = $afeh['sit_kode'];
                    $jsonData2[0]['sit_nama'] = $afeh['sit_nama'];
                    $jsonData2[0]['ket'] = $afeh['ket'];
                    $jsonData2[0]['add_rev'] = $afeh['addrevenue'];
                    $jsonData2[0]['val_kode'] = $afeh['val_kode'];

                    $jsonData2[0]['pocustomer'] = $afeh['pocustomer'];
                    $jsonData2[0]['totalpocustomer'] = $afeh['totalpocustomer'];
                    $jsonData2[0]['user'] = $afeh['user'];
                    $jsonData2[0]['rateidr'] = $afeh['rateidr'];
                    $jsonData2[0]['current_budget'] = $currentBuget;
                    
                    $jsonData2[0]['trano'] = $approve;
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    
                    $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                    $lastReject[0]['date'] = $docs['date'];
                    $lastReject[0]['comment']= $docs['comment'];

//                    $allReject = $this->workflow->getAllReject($approve);
//                    $lastReject = $this->workflow->getLastReject($approve);
                    $this->view->lastReject = $lastReject;
//                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    $this->view->progress = $progress;
                    $this->view->result = $afe;
                    $this->view->result2 = $afes;
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->file = $file;
                    $this->view->tglAfe = $afeh['tgl'];
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
        
        $json = $this->getRequest()->getParam("posts");
        

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($json);
    }

     public function insertafeAction() {
        $this->_helper->viewRenderer->setNoRender();
        $activitylog2 = new Admin_Models_Activitylog();
        $comment = $this->_getParam("comment");
        
        Zend_Loader::loadClass('Zend_Json');
        $json = $this->getRequest()->getParam('posts');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
//       $jsonData = Zend_Json::decode($this->json);
        $jsonData = Zend_Json::decode($json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);
        
        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('AFE');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'AFE-' . $last;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano, 'AFE', '', $this->const['DOCUMENT_SUBMIT'], $items, '', false, false, $comment);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        } elseif (is_array($result) && count($result) > 0) {

            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }
        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);
        $urut = 1;
        $urut2 = 1;
           //activity log
         $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        
        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        if ($jsonData) {
            foreach ($jsonData as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "urut" => $urut,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "prj_nama" => $jsonEtc[0]['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "ket" => $val['ket'],
                    "val_kode" => $jsonEtc[0]['val_kode'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qtyori'],
                    "qtybaru" => $val['qty'],
                    "harga" => $val['priceori'],
                    "hargabaru" => $val['price'],
                    "totalpocustomer" => $val['totalpocustomer'],
                    "pocustomer" => $val['pocustomer'],
//				"petugas" => $this->session->userName,
                    "cfs_kode" => $val['cfs_kode'],
                    "cfs_nama" => $val['cfs_nama'],
                    "rateidr" => $jsonEtc[0]['rateidr']
                );
                $urut++;

                $this->afe->insert($arrayInsert);
                // detail
             $activityDetail['transengineer_afed'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
            }
        }


        if ($jsonData2) {
            foreach ($jsonData2 as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "urut" => $urut,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "prj_nama" => $jsonEtc[0]['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "ket" => $val['ket'],
                    "val_kode" => $jsonEtc[0]['val_kode'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qtyori'],
                    "qtybaru" => $val['qty'],
                    "harga" => $val['priceori'],
                    "hargabaru" => $val['price'],
                    "totalpocustomer" => $val['totalpocustomer'],
                    "pocustomer" => $val['pocustomer'],
//				"petugas" => $this->session->userName,
                    "cfs_kode" => $val['cfs_kode'],
                    "cfs_nama" => $val['cfs_nama'],
                    "rateidr" => $jsonEtc[0]['rateidr'],
                );
                $urut2++;
                $this->afes->insert($arrayInsert);
            }
        }
        if ($jsonEtc[0]['rateidr'] == '' || $jsonEtc[0]['rateidr'] == 0) {
            $utility = $this->_helper->getHelper('utility');
            $jsonEtc[0]['rateidr'] = $utility->getExchangeRate();
        }
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "val_kode" => $jsonEtc[0]['val_kode'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "totalpocustomer" => $jsonEtc[0]['totalpocustomer'],
            "pocustomer" => $jsonEtc[0]['pocustomer'],
            "addrevenue" => $jsonEtc[0]['add_rev'],
            "margin" => -1 * floatval($jsonEtc[0]['margin']),
            "margin_last" => floatval($jsonEtc[0]['margin2']),
            "rateidr" => $jsonEtc[0]['rateidr'],
                //"cus_kode" => $cusKode,
        );
        $this->afeh->insert($arrayInsert);
         //header
        $activityHead['transengineer_afeh'][0]=$arrayInsert;
        
        
          $activityCount=0;
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                $activityFile['files'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
         $activityLog = array(
            "menu_name" => "Create AFE",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
         $activitylog2->insert($activityLog);
        

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateafeAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $json = $this->getRequest()->getParam('posts');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        $trano = $jsonEtc[0]['trano'];
        $urut = 1;
        $urut2 = 1;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $result = $this->workflow->setWorkflowTrans($trano, 'AFE', '', $this->const['DOCUMENT_RESUBMIT'], $items, '', false, false, $comment);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        } elseif (is_array($result) && count($result) > 0) {

            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }
        $temp = array();
        if ($jsonData) {
            $log['afe-add-detail-before'] = array();
            $fetch = $this->afe->fetchAll("trano = '$trano'");
            if ($fetch) {
                $fetch = $fetch->toArray();
                $log['afe-add-detail-before'] = $fetch;
            }
            $this->afe->delete("trano = '$trano'");
            foreach ($jsonData as $key => $val) {

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "urut" => $urut,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "prj_nama" => $jsonEtc[0]['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "ket" => $val['ket'],
                    "val_kode" => $jsonEtc[0]['val_kode'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qtyori'],
                    "qtybaru" => $val['qty'],
                    "harga" => $val['priceori'],
                    "hargabaru" => $val['price'],
                    "totalpocustomer" => $val['totalpocustomer'],
                    "pocustomer" => $val['pocustomer'],
//				"petugas" => $this->session->userName,
                    "cfs_kode" => $val['cfs_kode'],
                    "cfs_nama" => $val['cfs_nama'],
                    "rateidr" => $jsonEtc[0]['rateidr']
                );

                //$log['afe-add-detail-before'][] = $arrayInsert;
                $this->afe->insert($arrayInsert);
            }
            $urut++;
            $log2['afe-add-detail-after'] = $fetch;
        }

        if ($jsonData2) {
            $log['afe-save-detail-before'] = array();
            $fetch = $this->afe->fetchAll("trano = '$trano'");
            if ($fetch) {
                $fetch = $fetch->toArray();
                $log['afe-save-detail-before'] = $fetch;
            }
            $this->afes->delete("trano = '$trano'");
            foreach ($jsonData2 as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "urut" => $urut,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "prj_nama" => $jsonEtc[0]['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "ket" => $val['ket'],
                    "val_kode" => $jsonEtc[0]['val_kode'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qtyori'],
                    "qtybaru" => $val['qty'],
                    "harga" => $val['priceori'],
                    "hargabaru" => $val['price'],
                    "totalpocustomer" => $val['totalpocustomer'],
                    "pocustomer" => $val['pocustomer'],
//				"petugas" => $this->session->userName,
                    "cfs_kode" => $val['cfs_kode'],
                    "cfs_nama" => $val['cfs_nama'],
                    "rateidr" => $jsonEtc[0]['rateidr']
                );

                //$log2['afe-save-detail-after'] = $arrayInsert;
                $this->afes->insert($arrayInsert);
            }
            $log2['afe-save-detail-after'] = $fetch;
        }
        if ($jsonEtc[0]['rateidr'] == '' || $jsonEtc[0]['rateidr'] == 0) {
            $utility = $this->_helper->getHelper('utility');
            $jsonEtc[0]['rateidr'] = $utility->getExchangeRate();
        }
        $result = $this->afeh->fetchRow("trano = '$trano'");
        if ($result) {
            $result = $result->toArray();
            $log['afe-header-before'] = $result;
        }
        $arrayInsert = array(
//            	"trano" => $trano,
//				"tgl" => date('Y-m-d'),
//
//				"prj_kode" => $jsonEtc[0]['prj_kode'],
//				"prj_nama" => $jsonEtc[0]['prj_nama'],
//				"sit_kode" => $jsonEtc[0]['sit_kode'],
//				"sit_nama" => $jsonEtc[0]['sit_nama'],

            "ket" => $jsonEtc[0]['ket'],
            "val_kode" => $jsonEtc[0]['val_kode'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => time('H:i:s'),
            "totalpocustomer" => $jsonEtc[0]['totalpocustomer'],
            "pocustomer" => $jsonEtc[0]['pocustomer'],
            "addrevenue" => $jsonEtc[0]['add_rev'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "margin" => -1 * floatval($jsonEtc[0]['margin']),
            "margin_last" => floatval($jsonEtc[0]['margin2']),
                //"cus_kode" => $cusKode,
        );
        $this->afeh->update($arrayInsert, "trano = '$trano'");

        $result = $this->afeh->fetchRow("trano = '$trano'")->toArray();
        $log2['afe-header-after'] = $result;

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
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
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function getxmlafeAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");
        $noData = $this->getRequest()->getParam("nodata");
        if (!$noData) {
            $prd = $this->procurement->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
            if ($prd) {
                foreach ($prd as $key => $val) {
                    foreach ($val as $key2 => $val2) {
                        if ($val2 == '""' || $val2 == '')
                            unset($prd[$key][$key2]);
                    }
                    $prd[$key]['id'] = $key + 1;
                    $kodeBrg = $val['kode_brg'];
                    $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                    if ($barang) {
                        $prd[$key]['uom'] = $barang['sat_kode'];
                    }
                    if ($val['val_kode'] == 'IDR')
                        $prd[$key]['hargaIDR'] = $val['harga'];
                    elseif ($val['val_kode'] == 'USD')
                        $prd[$key]['hargaUSD'] = $val['harga'];

                    $prd[$key]['net_act'] = $val['myob'];
                    $prd[$key]['fromBoq3'] = 1;
                }
            }
            $jsonData = $prd;
        }
        else {
            $json = $this->getRequest()->getParam("posts");
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
        }

        $xmlOutput = $this->xml->getXml($jsonData);
        $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf-8');
        $this->getResponse()->setBody($xmlOutput);
    }

    public function afeIdrUsdAction() {
        
    }

    public function editAfeIdrUsdAction() {
        $trano = $this->getRequest()->getParam("trano");
        $cek = $this->afeh->fetchRow("trano = '$trano'");
        $afeh = array();
        $afed = array();
        $afes = array();
        $result = array();
        $file = array();
        if ($cek) {
            if ($cek['is_switching'] == 1) {
                $afeh = $cek->toArray();
                $afed = $this->afe->fetchAll("trano = '$trano'");
                if ($afed)
                    $afed = $afed->toArray();
                $afes = $this->afes->fetchAll("trano = '$trano'");
                if ($afes)
                    $afes = $afes->toArray();
                $file = $this->files->fetchAll("trano = '$trano'");
                if ($file)
                    $file = $file->toArray();
                foreach ($afes as $k => $v) {
                    $result[$k]['prj_kode'] = $v['prj_kode'];
                    $result[$k]['sit_kode'] = $v['sit_kode'];
                    $result[$k]['prj_nama'] = $v['prj_nama'];
                    $result[$k]['sit_nama'] = $v['sit_nama'];
                    $result[$k]['kode_brg'] = $v['kode_brg'];
                    $result[$k]['nama_brg'] = $v['nama_brg'];
                    $result[$k]['cfs_kode'] = $v['cfs_kode'];
                    $result[$k]['cfs_nama'] = $v['cfs_nama'];
                    $barang = $this->barang->fetchRow("kode_brg = '{$v['kode_brg']}'");
                    if ($barang)
                        $result[$k]['uom'] = $barang['sat_kode'];
                    $result[$k]['workid'] = $v['workid'];
                    $result[$k]['workname'] = $v['workname'];
                    $result[$k]['qty'] = $v['qty'];
                    $result[$k]['price'] = $v['harga'];
                    $result[$k]['val_kode'] = $v['val_kode'];
                    $result[$k]['rateidr'] = $v['rateidr'];

                    foreach ($afed as $k2 => $v2) {
                        if ($v2['prj_kode'] == $v['prj_kode'] && $v2['sit_kode'] == $v['sit_kode'] && $v2['workid'] == $v['workid'] && $v2['kode_brg'] == $v['kode_brg']) {
                            $result[$k]['new_qty'] = $v2['qtybaru'];
                            $result[$k]['new_price'] = $v2['hargabaru'];
                            $result[$k]['new_val_kode'] = $v2['val_kode'];
                        }
                    }
                }
            }
        }

        $this->view->afeh = $afeh;
        $this->view->afe = Zend_Json::encode(array('posts' => $result, 'count' => count($result)));
        $this->view->file = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
    }

    public function checkTransactionExistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $kodeBrg = $this->getRequest()->getParam("kode_brg");
        $workid = $this->getRequest()->getParam("workid");

        $pod = $this->quantity->getPoQuantity($prjKode, $sitKode, $workid, $kodeBrg);
        $pr = $this->quantity->getPrQuantity($prjKode, $sitKode, $workid, $kodeBrg);
        $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $workid, $kodeBrg);
        $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $workid, $kodeBrg);
        $rpi = $this->quantity->getRpiQuantity($prjKode, $sitKode, $workid, $kodeBrg);
        $do = $this->quantity->getDoQuantity($prjKode, $sitKode, $workid, $kodeBrg);
        $dor = $this->quantity->getDorQuantity($prjKode, $sitKode, $workid, $kodeBrg);
        $rem = $this->quantity->getReimbursementQuantity($prjKode, $sitKode, $workid, $kodeBrg);

        $result['success'] = false;
        $errorMsg = array();
        if ($pod) {
            if ($pod['totalIDR'] != 0 && $pod['totalHargaUSD'] != 0)
                $errorMsg[] = "PO";
        }
        if ($pr) {
            if ($pr['totalIDR'] != 0 && $pr['totalHargaUSD'] != 0)
                $errorMsg[] = "PR";
        }
        if ($arf) {
            if ($arf['totalIDR'] != 0 && $arf['totalHargaUSD'] != 0)
                $errorMsg[] = "ARF";
        }
        if ($asfcancel) {
            if ($asfcancel['totalIDR'] != 0 && $asfcancel['totalHargaUSD'] != 0)
                $errorMsg[] = "ASF";
        }
        if ($rpi) {
            if ($rpi['totalIDR'] != 0 && $rpi['totalHargaUSD'] != 0)
                $errorMsg[] = "RPI";
        }
        if ($do)
            $errorMsg[] = "DO";
        if ($dor) {
            if ($dor['totalIDR'] != 0 && $dor['totalHargaUSD'] != 0)
                $errorMsg[] = "DOR";
        }
        if ($rem)
            $errorMsg[] = "REM";

        if (count($errorMsg) > 0) {
            $tmp = implode(",", $errorMsg);
            $result['msg'] = "This product ID has existing Transaction : $tmp. Therefore cannot be process for switching currency.";
        } else
            $result['success'] = true;

        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function appAfeIdrUsdAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $finalDate = $this->getRequest()->getParam("finalDate") == '' ? date('Y-m-d H:i:s') : $this->getRequest()->getParam("finalDate");
        $this->view->finalDate = $finalDate;
        $lastReject=array();


        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/AFE';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("json");
            $prjKode = $this->getRequest()->getParam("prj_kode");
            $sitKode = $this->getRequest()->getParam("sit_kode");
            $prjNama = $this->getRequest()->getParam("prj_nama");
            $sitNama = $this->getRequest()->getParam("sit_nama");
            $rateidr = $this->getRequest()->getParam("rateidr");
            $trano = $this->getRequest()->getParam("trano");
            $user = $this->getRequest()->getParam("user");
            $files = $this->getRequest()->getParam("file");

            $jsonData = Zend_Json::decode($json);
            if ($files)
                $file = Zend_Json::decode($files);

            $cusKode = $this->project->getProjectAndCustomer($prjKode);
            $cus_kode = $cusKode[0]['cus_kode'];
            $cus_nama = $cusKode[0]['cus_kode'];

//            $utility = $this->_helper->getHelper('utility');
//            $rateidr = $utility->getExchangeRate();

            $stsoverhead = $this->trans->getSiteOverhead($sitKode, $prjKode);
            $stsoverhead = $stsoverhead['stsoverhead'];

            if ($stsoverhead == 'Y') {
                $this->view->stsoverhead = true;
            }

            $boq2ORI = $this->budget->getBoq2AFE($prjKode, $sitKode);
            $kboq2 = $this->budget->getKBoq2AFE($prjKode, $sitKode, $finalDate);

            $totalOriIDR = floatval($boq2ORI['totalIDR']);
            $totalCurrentIDR = $totalOriIDR + floatval($kboq2['totalIDR']);
            $totalOriUSD = floatval($boq2ORI['totalUSD']);
            $totalOriUSDinIDR = floatval($boq2ORI['totalUSDinIDR']);
            $totalCurrentUSD = floatval($totalOriUSD + $kboq2['totalUSD']);

            $boq3_ori = $this->budget->getBoq3AFE($prjKode, $sitKode);
            $boq3_current = $this->budget->getBOQ3Current($prjKode, $sitKode,$finalDate);

            $totalBoq3_oriIDR = floatval($boq3_ori['totalIDR']);
            $totalBoq3_currentIDR = floatval($boq3_current['totalIDR']);
            $totalBoq3_oriUSD = floatval($boq3_ori['totalUSD']);
            $totalBoq3_currentUSD = floatval($boq3_current['totalUSD']);
            $totalBoq3_oriUSDRate = floatval($boq3_ori['totalUSDRate']);
            
            //Tarik Data Recorded Cost
            $recorded_cost = $this->cost->recordedCostPerDate($prjKode, $sitKode,'1970-01-01',$finalDate);
                    
            //$mip = $this->budget->getMIP($afeh['prj_kode'],$afeh['sit_kode']);
            $mip = $this->recordedCost($recorded_cost);
            
            //Get Current Budget
            $currentBuget = $this->budgt->getCurrentBudget($boq3_current, $recorded_cost,$rateidr);
            
            //$mip = $this->recordedCost($prjKode, $sitKode, $finalDate);
            $progress = $this->progress($prjKode, $sitKode, $finalDate)/ 100;


            $totalAFE = array();
            $totalIDRAfter = 0;
            $totalIDRBefore = 0;
            $totalUSDAfter = 0;
            $totalUSDBefore = 0;
            foreach ($jsonData as $index => $val) {
                $totalIDRBefore +=$val['val_kode'] == 'IDR' ? $val['qty'] * $val['price'] : 0;

                $totalUSDBefore +=$val['val_kode'] == 'USD' ? $val['qty'] * $val['price'] : 0;

                $totalIDRAfter +=$val['new_val_kode'] == 'IDR' ? $val['new_qty'] * $val['new_price'] : 0;

                $totalUSDAfter +=$val['new_val_kode'] == 'USD' ? $val['new_qty'] * $val['new_price'] : 0;
            }

            $totalAFE['totalIDR'] = $totalIDRAfter - $totalIDRBefore;
            $totalAFE['totalUSD'] = $totalUSDAfter - $totalUSDBefore;

            $jsonData2[0]['afeValIDR'] = $totalAFE['totalIDR'];
            $jsonData2[0]['afeValUSD'] = $totalAFE['totalUSD'];

            $jsonData2[0]['cus_kode'] = $cus_kode;
            $jsonData2[0]['cus_nama'] = $cus_nama;
            $jsonData2[0]['boq2_oriIDR'] = $totalOriIDR;
            $jsonData2[0]['boq2_currentIDR'] = $totalCurrentIDR;

            $jsonData2[0]['boq3_oriIDR'] = $totalBoq3_oriIDR;
            $jsonData2[0]['boq3_currentIDR'] = $totalBoq3_currentIDR;
            $jsonData2[0]['boq2_oriUSD'] = $totalOriUSD;
            $jsonData2[0]['boq2_oriUSDinIDR'] = $totalOriUSDinIDR;
            $jsonData2[0]['boq2_currentUSD'] = $totalCurrentUSD;
            $jsonData2[0]['boq3_oriUSDRate'] = $totalBoq3_oriUSDRate; 
            $jsonData2[0]['boq3_oriUSD'] = $totalBoq3_oriUSD;
            $jsonData2[0]['boq3_currentUSD'] = $totalBoq3_currentUSD;

            $jsonData2[0]['mip'] =$mip;
            $jsonData2[0]['prj_kode'] = $prjKode;
            $jsonData2[0]['sit_kode'] = $sitKode;
            $jsonData2[0]['prj_nama'] = $prjNama;
            $jsonData2[0]['sit_nama'] = $sitNama;
            $jsonData2[0]['rateidr'] = $rateidr;
            $jsonData2[0]['current_budget'] = $currentBuget;
            
            if ($trano)
                $jsonData2[0]['trano'] = $trano;
            if ($user)
                $jsonData2[0]['user'] = $user;

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->result2 = $jsonData3;
            $this->view->jsonResult = $json;
            $this->view->progress=$progress;
            $this->view->file = $file;
            $this->view->jsonFile = $files;
            $this->view->tglAfe = date('Y-m-d');

            if ($from == 'edit') {
                $this->view->edit = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $afe = $this->afe->fetchAll("trano = '$approve'")->toArray();
                    $afes = $this->afes->fetchAll("trano = '$approve'")->toArray();
                    $afeh = $this->afeh->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");
                    if ($statApprove == $this->const['DOCUMENT_FINAL'])
                        $finalDate = date("Y-m-d H:i:s", strtotime($docs['date']));
                    $this->view->finalDate = $finalDate;
                    
                    $result = array();
                    $indeks = 0;
                    if ($afe) {
                        foreach ($afe as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $uom = $barang['sat_kode'];
                            }

                            $result[$indeks] = array(
                                "prj_kode" => $val['prj_kode'],
                                "prj_nama" => $val['prj_nama'],
                                "sit_kode" => $val['sit_kode'],
                                "sit_nama" => $val['sit_nama'],
                                "cfs_kode" => $val['cfs_kode'],
                                "cfs_nama" => $val['cfs_nama'],
                                "workid" => $val['workid'],
                                "workname" => $val['workname'],
                                "kode_brg" => $val['kode_brg'],
                                "nama_brg" => $val['nama_brg'],
                                "new_qty" => $val['qtybaru'],
                                "new_price" => $val['hargabaru'],
                                "rateidr" => $val['rateidr'],
                                "uom" => $uom,
                                "new_val_kode" => $val['val_kode']
                            );

                            foreach ($afes as $key2 => $val2) {
                                if ($val2['prj_kode'] == $val['prj_kode'] && $val2['sit_kode'] == $val['sit_kode'] && $val2['workid'] == $val['workid'] && $val2['kode_brg'] == $val['kode_brg']) {
                                    $result[$indeks]['qty'] = $val2['qty'];
                                    $result[$indeks]['price'] = $val2['harga'];
                                    $result[$indeks]['val_kode'] = $val2['val_kode'];

                                    break;
                                }
                            }

                            $indeks++;
                        }
                    }

                    $cusKode = $this->project->getProjectAndCustomer($afeh['prj_kode']);
                    $cus_kode = $cusKode[0]['cus_kode'];
                    $cus_nama = $cusKode[0]['cus_kode'];

                    $jsonData2[0]["add_rev"] = floatval($afeh['addrevenue']);
                    $jsonData2[0]["totalpocustomer"] = floatval($afeh['totalpocustomer']);

                    $stsoverhead = $this->trans->getSiteOverhead($afeh['sit_kode'], $afeh['prj_kode']);
                    $stsoverhead = $stsoverhead['stsoverhead'];

                    if ($stsoverhead == 'Y') {
                        $this->view->stsoverhead = true;
                    }

                    $boq2ORI = $this->budget->getBoq2AFE($afeh['prj_kode'], $afeh['sit_kode']);
                    $kboq2 = $this->budget->getKBoq2AFE($afeh['prj_kode'], $afeh['sit_kode'], $finalDate);

                    $totalOriIDR = floatval($boq2ORI['totalIDR']);
                    $totalCurrentIDR = $totalOriIDR + floatval($kboq2['totalIDR']);
                    $totalOriUSD = floatval($boq2ORI['totalUSD']);
                    $totalOriUSDinIDR = floatval($boq2ORI['totalUSDinIDR']);
                    $totalCurrentUSD = floatval($totalOriUSD + $kboq2['totalUSD']);

                    $boq3_ori = $this->budget->getBoq3AFE($afeh['prj_kode'], $afeh['sit_kode']);
                    $boq3_current = $this->budget->getBOQ3Current($afeh['prj_kode'], $afeh['sit_kode'],$finalDate, $docs['item_id']);

                    $totalBoq3_oriIDR = floatval($boq3_ori['totalIDR']);
                    $totalBoq3_currentIDR = floatval($boq3_current['totalIDR']);
                    $totalBoq3_oriUSD = floatval($boq3_ori['totalUSD']);
                    $totalBoq3_oriUSDRate = floatval($boq3_ori['totalUSDRate']);
                    $totalBoq3_currentUSD = floatval($boq3_current['totalUSD']);

                    $totalAFE = $this->budget->getTotalAFE($docs['item_id']);
                    
                    //Tarik Data Recorded Cost
                    $recorded_cost = $this->cost->recordedCostPerDate($afeh['prj_kode'], $afeh['sit_kode'],'1970-01-01',$finalDate);
                    
                    //$mip = $this->budget->getMIP($afeh['prj_kode'],$afeh['sit_kode']);
                    $mip = $this->recordedCost($recorded_cost);
                    
                    //Get Current Budget
                    $currentBuget = $this->budgt->getCurrentBudget($boq3_current, $recorded_cost,$afeh['rateidr']);

                    //$mip = $this->recordedCost($afeh['prj_kode'], $afeh['sit_kode'], $finalDate);
                    $progress = $this->progress($afeh['prj_kode'], $afeh['sit_kode'], $finalDate)/ 100;

                    $jsonData2[0]['cus_kode'] = intval($cus_kode);
                    $jsonData2[0]['cus_nama'] = intval($cus_nama);

                    $jsonData2[0]['afeValIDR'] = $totalAFE['totalIDR'];
                    $jsonData2[0]['afeValUSD'] = $totalAFE['totalUSD'];

                    $jsonData2[0]['boq2_oriIDR'] = floatval($totalOriIDR);
                    $jsonData2[0]['boq2_currentIDR'] = floatval($totalCurrentIDR);
                    $jsonData2[0]['boq2_oriUSD'] = floatval($totalOriUSD);
                    $jsonData2[0]['boq2_oriUSDinIDR'] = floatval($totalOriUSDinIDR);
                    $jsonData2[0]['boq2_currentUSD'] = floatval($totalCurrentUSD);

                    $jsonData2[0]['boq3_oriIDR'] = floatval($totalBoq3_oriIDR);
                    $jsonData2[0]['boq3_currentIDR'] = floatval($totalBoq3_currentIDR);
                    $jsonData2[0]['boq3_oriUSD'] = floatval($totalBoq3_oriUSD);
                    $jsonData2[0]['boq3_oriUSDRate'] = $totalBoq3_oriUSDRate;
                    $jsonData2[0]['boq3_currentUSD'] = floatval($totalBoq3_currentUSD);

                    $jsonData2[0]['mip'] = $mip;
                    $jsonData2[0]['prj_kode'] = $afeh['prj_kode'];
                    $jsonData2[0]['prj_nama'] = $afeh['prj_nama'];
                    $jsonData2[0]['sit_kode'] = $afeh['sit_kode'];
                    $jsonData2[0]['sit_nama'] = $afeh['sit_nama'];
                    $jsonData2[0]['ket'] = $afeh['ket'];
                    $jsonData2[0]['add_rev'] = $afeh['addrevenue'];
                    $jsonData2[0]['val_kode'] = $afeh['val_kode'];
                    $jsonData2[0]['current_budget'] = $currentBuget;

                    $jsonData2[0]['pocustomer'] = $afeh['pocustomer'];
                    $jsonData2[0]['totalpocustomer'] = $afeh['totalpocustomer'];
                    $jsonData2[0]['user'] = $afeh['user'];
                    $jsonData2[0]['rateidr'] = $afeh['rateidr'];

                    $jsonData2[0]['trano'] = $approve;
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;

//                    $allReject = $this->workflow->getAllReject($approve);
//                    $lastReject = $this->workflow->getLastReject($approve);
                    $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                    $lastReject[0]['date'] = $docs['date'];
                    $lastReject[0]['comment']= $docs['comment'];
                    $this->view->lastReject = $lastReject;
//                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    $this->view->result = $result;
                    $this->view->approve = true;
                    $this->view->progress = $progress;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->trano = $approve;
                    $this->view->file = $file;
                    $this->view->tglAfe = $afeh['tgl'];
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }

        $json = $this->getRequest()->getParam("posts");


        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($json);
    }

    public function insertAfeIdrUsdAction() {
        $this->_helper->viewRenderer->setNoRender();
        $activitylog2 = new Admin_Models_Activitylog();
        
        Zend_Loader::loadClass('Zend_Json');
        $json = $this->getRequest()->getParam('posts');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);

        $jsonData = Zend_Json::decode($json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('AFE');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'AFE-' . $last;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano, 'AFE', '', $this->const['DOCUMENT_SUBMIT'], $items);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        } elseif (is_array($result) && count($result) > 0) {

            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }
        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);
        $urut = 1;
        $urut2 = 1;
              //activity log
         $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        if ($jsonEtc[0]['rateidr'] == '' || $jsonEtc[0]['rateidr'] == 0) {
            $utility = $this->_helper->getHelper('utility');
            $jsonEtc[0]['rateidr'] = $utility->getExchangeRate();
        }

        if ($jsonData) {
            foreach ($jsonData as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "urut" => $urut,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "prj_nama" => $jsonEtc[0]['prj_nama'],
                    "sit_kode" => $jsonEtc[0]['sit_kode'],
                    "sit_nama" => $jsonEtc[0]['sit_nama'],
                    "rateidr" => $jsonEtc[0]['rateidr'],
                    "ket" => $val['ket']
                );

                //Saving unutk item yang lama...

                $arrayInsert["val_kode"] = $val['val_kode'];
                $arrayInsert["workid"] = $val['workid'];
                $arrayInsert["workname"] = $val['workname'];
                $arrayInsert["kode_brg"] = $val['kode_brg'];
                $arrayInsert["nama_brg"] = $val['nama_brg'];
                $arrayInsert["qty"] = $val['qty'];
                $arrayInsert["qtybaru"] = 0;
                $arrayInsert["harga"] = $val['price'];
                $arrayInsert["hargabaru"] = 0;
                $arrayInsert["totalpocustomer"] = $val['totalpocustomer'];
                $arrayInsert["pocustomer"] = $val['pocustomer'];
                $arrayInsert["cfs_kode"] = $val['cfs_kode'];
                $arrayInsert["cfs_nama"] = $val['cfs_nama'];

                $urut++;

                $this->afes->insert($arrayInsert);
                   // detail
             $activityDetail['transengineer_afeds'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;

                //Add untuk item yang baru (dengan currency yang baru)

                $arrayInsert["val_kode"] = $val['new_val_kode'];
                $arrayInsert["workid"] = $val['workid'];
                $arrayInsert["workname"] = $val['workname'];
                $arrayInsert["kode_brg"] = $val['kode_brg'];
                $arrayInsert["nama_brg"] = $val['nama_brg'];
                $arrayInsert["qty"] = 0;
                $arrayInsert["qtybaru"] = $val['new_qty'];
                $arrayInsert["harga"] = 0;
                $arrayInsert["hargabaru"] = $val['new_price'];
                $arrayInsert["totalpocustomer"] = $val['totalpocustomer'];
                $arrayInsert["pocustomer"] = $val['pocustomer'];
                $arrayInsert["cfs_kode"] = $val['cfs_kode'];
                $arrayInsert["cfs_nama"] = $val['cfs_nama'];

                $urut++;

                $this->afe->insert($arrayInsert);
                         // detail
             $activityDetail['transengineer_afed'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
            }
        }

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
//            "val_kode" => $jsonEtc[0]['val_kode'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "totalpocustomer" => $jsonEtc[0]['totalpocustomer'],
            "pocustomer" => $jsonEtc[0]['pocustomer'],
            "addrevenue" => $jsonEtc[0]['add_rev'],
            "margin" => -1 * floatval($jsonEtc[0]['margin']),
            "margin_last" => floatval($jsonEtc[0]['margin2']),
            "rateidr" => $jsonEtc[0]['rateidr'],
            "is_switching" => 1
        );
        $this->afeh->insert($arrayInsert);
          //header
        $activityHead['transengineer_afeh'][0]=$arrayInsert;
        
            $activityCount=0;
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                    $activityFile['files'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
        $activityLog = array(
            "menu_name" => "Create AFE Switching Currency",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
         $activitylog2->insert($activityLog);
        

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateAfeIdrUsdAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $json = $this->getRequest()->getParam('posts');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);

        $jsonData = Zend_Json::decode($json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);
        $trano = $jsonEtc[0]['trano'];

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano, 'AFE', '', $this->const['DOCUMENT_RESUBMIT'], $items);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        } elseif (is_array($result) && count($result) > 0) {

            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }

        $urut = 1;
        $urut2 = 1;

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        if ($jsonEtc[0]['rateidr'] == '' || $jsonEtc[0]['rateidr'] == 0) {
            $utility = $this->_helper->getHelper('utility');
            $jsonEtc[0]['rateidr'] = $utility->getExchangeRate();
        }

        if ($jsonData) {
            $log['afe-switch-add-detail-before'] = array();
            $fetch = $this->afe->fetchAll("trano = '$trano'");
            if ($fetch) {
                $fetch = $fetch->toArray();
                $log['afe-switch-add-detail-before'] = $fetch;
            }
            $this->afe->delete("trano = '$trano'");

            $log['afe-switch-save-detail-before'] = array();
            $fetch = $this->afe->fetchAll("trano = '$trano'");
            if ($fetch) {
                $fetch = $fetch->toArray();
                $log['afe-switch-save-detail-before'] = $fetch;
            }
            $this->afes->delete("trano = '$trano'");

            foreach ($jsonData as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "urut" => $urut,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "prj_nama" => $jsonEtc[0]['prj_nama'],
                    "sit_kode" => $jsonEtc[0]['sit_kode'],
                    "sit_nama" => $jsonEtc[0]['sit_nama'],
                    "rateidr" => $jsonEtc[0]['rateidr'],
                    "ket" => $val['ket']
                );

                //Saving unutk item yang lama...

                $arrayInsert["val_kode"] = $val['val_kode'];
                $arrayInsert["workid"] = $val['workid'];
                $arrayInsert["workname"] = $val['workname'];
                $arrayInsert["kode_brg"] = $val['kode_brg'];
                $arrayInsert["nama_brg"] = $val['nama_brg'];
                $arrayInsert["qty"] = $val['qty'];
                $arrayInsert["qtybaru"] = 0;
                $arrayInsert["harga"] = $val['price'];
                $arrayInsert["hargabaru"] = 0;
                $arrayInsert["totalpocustomer"] = $val['totalpocustomer'];
                $arrayInsert["pocustomer"] = $val['pocustomer'];
                $arrayInsert["cfs_kode"] = $val['cfs_kode'];
                $arrayInsert["cfs_nama"] = $val['cfs_nama'];

                $urut++;

                $log['afe-switch-save-detail-after'][] = $arrayInsert;
                $this->afes->insert($arrayInsert);

                //Add untuk item yang baru (dengan currency yang baru)

                $arrayInsert["val_kode"] = $val['new_val_kode'];
                $arrayInsert["workid"] = $val['workid'];
                $arrayInsert["workname"] = $val['workname'];
                $arrayInsert["kode_brg"] = $val['kode_brg'];
                $arrayInsert["nama_brg"] = $val['nama_brg'];
                $arrayInsert["qty"] = 0;
                $arrayInsert["qtybaru"] = $val['new_qty'];
                $arrayInsert["harga"] = 0;
                $arrayInsert["hargabaru"] = $val['new_price'];
                $arrayInsert["totalpocustomer"] = $val['totalpocustomer'];
                $arrayInsert["pocustomer"] = $val['pocustomer'];
                $arrayInsert["cfs_kode"] = $val['cfs_kode'];
                $arrayInsert["cfs_nama"] = $val['cfs_nama'];

                $urut++;

                $log['afe-switch-add-detail-after'][] = $arrayInsert;
                $this->afe->insert($arrayInsert);
            }
        }
        $result = $this->afeh->fetchRow("trano = '$trano'");
        if ($result) {
            $result = $result->toArray();
            $log['afe-switch-header-before'] = $result;
        }
        $arrayInsert = array(
            "ket" => $jsonEtc[0]['ket'],
            "user" => $this->session->userName,
            "totalpocustomer" => $jsonEtc[0]['totalpocustomer'],
            "pocustomer" => $jsonEtc[0]['pocustomer'],
            "addrevenue" => $jsonEtc[0]['add_rev'],
            "margin" => -1 * floatval($jsonEtc[0]['margin']),
            "margin_last" => floatval($jsonEtc[0]['margin2']),
            "rateidr" => $jsonEtc[0]['rateidr'],
        );
        $this->afeh->insert($arrayInsert);
        $result = $this->afeh->fetchRow("trano = '$trano'");
        if ($result) {
            $result = $result->toArray();
            $log['afe-switch-header-after'] = $result;
        }
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
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

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function checkAfeSwitchingAction() {
        $this->_helper->viewRenderer->setNoRender();
        $workflow_id = $this->getRequest()->getParam("workflow_id");
        $cek = $this->workflowTrans->fetchRow("workflow_trans_id = $workflow_id");
        $success = false;
        if ($cek) {
            $trano = $cek['item_id'];
            $success = $this->afeh->checkSwitching($trano);
        }

        $json = array("success" => $success);
        $this->getResponse()->setBody(Zend_Json::encode($json));
    }

    public function afeNonProjectAction() {
        
    }

}

?>
