<?php
class Procurement_ProcurementrequestController extends Zend_Controller_Action
{
    private $db;
    private $xml;
    private $procurement;
    private $procurementH;
    private $barang;
    private $quantity;
    private $budget;
    
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->xml = $this->_helper->getHelper('xml');
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->procurement = new Default_Models_ProcurementRequest();
        $this->procurementH = new Default_Models_ProcurementRequestH();
        $this->barang = new Default_Models_MasterBarang();
        $this->budget = new Default_Models_Budget();
    }

    public function getxmlprAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");
        $noData = $this->getRequest()->getParam("nodata");
   		if (!$noData)
        {
            $prd = $this->procurement->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
            if ($prd)
            {
                foreach($prd as $key => $val)
                {
                    foreach ($val as $key2 => $val2)
                    {
                        if ($val2 == '""' || $val2 == '')
                            unset($prd[$key][$key2]);
                    }
                    $prd[$key]['id'] = $key + 1;
                    $kodeBrg = $val['kode_brg'];
                    $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                    if ($barang)
                    {
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
        else
        {
            $json = $this->getRequest()->getParam("posts");
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
        }

       $xmlOutput = $this->xml->getXml($jsonData);
       $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf-8');
       $this->getResponse()->setBody($xmlOutput);
    }

    public function getxmlprbudgetAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");
        $noData = $this->getRequest()->getParam("nodata");
   		if (!$noData)
        {
            $prd = $this->procurement->fetchAll("trano = '$trano'",array("urut ASC"))->toArray();
            if ($prd)
            {
                foreach($prd as $key => $val)
                {
                    $prd[$key]['id'] = $key + 1;
                    $kodeBrg = $val['kode_brg'];
                    $workid = $val['workid'];
                    $sitKode = $val['sit_kode'];
                    $prjKode = $val['prj_kode'];

                    $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                    if ($barang)
                    {
                        $prd[$key]['uom'] = $barang['sat_kode'];
                    }
                    if ($val['val_kode'] == 'IDR')
                        $prd[$key]['hargaIDR'] = $val['harga'];
                    elseif ($val['val_kode'] == 'USD')
                        $prd[$key]['hargaUSD'] = $val['harga'];

                    $prd[$key]['dep_kode'] = $val['prj_kode'];
                    $prd[$key]['dep_nama'] = $val['prj_nama'];
                    $prd[$key]['per_kode'] = $val['sit_kode'];
                    $prd[$key]['per_nama'] = $val['sit_nama'];
                    $prd[$key]['budgetid'] = $val['workid'];
                    $prd[$key]['budgetname'] = $val['workname'];
                    $prd[$key]['totalPrice'] = $val['jumlah'];

                    $boq3 = $this->budget->getBudgetOverhead($prjKode,$sitKode,$workid);
                    if ($prd[$key]['val_kode'] == 'IDR')
                    {
                        $prd[$key]['totalPriceBudget'] = $boq3[0]['totalIDR'];
                    }
                    else
                    {
                        $prd[$key]['totalPriceBudget'] = $boq3[0]['totalHargaUSD'];
                    }

                    $pr = $this->quantity->getPrOverheadQuantity($prjKode,$sitKode,$workid);
                        if ($pr != '')
                        {

                            $prd[$key]['totalPRraw'] = $pr['qty'];

                            $prd[$key]['totalPricePRraw'] = $pr['total'];

                        }

                        else
                        {
                            $prd[$key]['totalPRraw'] = 0;

                            $prd[$key]['totalPricePRraw'] = 0;
                        }

                        $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid);
                        if ($arf != '')
                        {

                            $prd[$key]['totalARF'] = $arf['qty'];

                            if ($prd[$key]['val_kode'] == 'IDR')
                            {

                                $prd[$key]['totalPriceARF'] = $arf['totalIDR'];
                            }
                            else
                            {

                                $prd[$key]['totalPriceARF'] = $arf['totalUSD'];
                            }

                        }

                        else
                        {
                            $prd[$key]['totalARF'] = 0;

                            $prd[$key]['totalPriceARF'] = 0;
                        }
                        $totalQtyPRARF = $prd[$key]['totalPRraw']+ $prd[$key]['totalARF'];
                        $totalPricePRARF = $prd[$key]['totalPricePRraw']+ $prd[$key]['totalPriceARF'];

                        $prd[$key]['totalPR'] = $totalQtyPRARF;
                        $prd[$key]['totalPricePR'] = $totalPricePRARF;


                    $prd[$key]['net_act'] = $val['myob'];
                    $prd[$key]['fromBoq3'] = 1;
                }
           }
            $jsonData = $prd;
        }
        else
        {
            $json = $this->getRequest()->getParam("posts");
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
        }

       $xmlOutput = $this->xml->getXml($jsonData);
       $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf-8');
       $this->getResponse()->setBody($xmlOutput);
    }

    public function cekfrombudgetAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = $this->getRequest()->getParam("posts");
        $tipe = $this->getRequest()->getParam("type");

        $data = Zend_Json::decode($json);
        $budget = new Default_Models_Budget();

        $error = array();
        foreach($data as $k => $v)
        {
            $cek = $budget->getBoq3ByOne($v['prj_kode'],$v['sit_kode'],$v['workid'],$v['kode_brg']);
            if ($cek)
            {
                if ($v['val_kode'] == 'IDR')
                {
                    $total = floatval($cek['totalHargaIDR']);
                    $prRequested = floatval($v['qty']) * floatval($v['hargaIDR']);
                }
                else
                {
                    $total = floatval($cek['totalHargaUSD']);
                    $prRequested = floatval($v['qty']) * floatval($v['hargaUSD']);
                }
                $pod = $this->quantity->getPoQuantity($v['prj_kode'],$v['sit_kode'],$v['workid'],$v['kode_brg']);
                $arf = $this->quantity->getArfQuantity($v['prj_kode'],$v['sit_kode'],$v['workid'],$v['kode_brg']);
                $asfcancel = $this->quantity->getAsfcancelQuantity($v['prj_kode'],$v['sit_kode'],$v['workid'],$v['kode_brg']);
                $prd = $this->quantity->getPrQuantity($v['prj_kode'],$v['sit_kode'],$v['workid'],$v['kode_brg']);
                if ($pod != '' )
                {
                    if ($v['val_kode'] == 'IDR')
                        $totalPOD = $pod['totalIDR'];
                    else
                        $totalPOD = $pod['totalHargaUSD'];
                }
                else
                {
                    $totalPOD = 0;
                }
                if ($prd != '' )
                {
                    if ($v['val_kode'] == 'IDR')
                        $totalPRD = $prd['totalIDR'];
                    else
                        $totalPRD = $prd['totalHargaUSD'];
                }
                else
                {
                    $totalPOD = 0;
                }
                if ($arf != '' )
                {
                    if ($v['val_kode'] == 'IDR')
                        $totalARF = $arf['totalIDR'];
                    else
                        $totalARF = $arf['totalHargaUSD'];
                }
                else
                {
                    $totalARF = 0;
                }
                if ($asfcancel != '' )
                {
                    if ($v['val_kode'] == 'IDR')
                        $totalASF = $asfcancel['totalIDR'];
                    else
                        $totalASF = $asfcancel['totalHargaUSD'];
                }
                else
                {
                    $totalASF = 0;
                }

                $totalCost = ($totalPOD +  $totalARF - $totalASF);
                $balanceCost = $total - $totalCost;
                $balancePR = $total - $totalPRD;
                if (bccomp($balanceCost,0) <= 0)
                {
                    $error[] = array(
                        "kode_brg" => $cek['kode_brg'],
                        "workid" => $cek['workid'],
                        "workname" => $cek['workname'],
                        "nama_brg" => $cek['nama_brg'],
                        "totalPR" => $prRequested,
                        "total" => $total,
                        "totalCost" => $totalCost,
                        "totalInPR" => $totalPRD,
                        "qtyPR" => $v['qty'],
                        "qtyInPR" => $prd['qty'],
                        "qty" => $cek['qty'],
                        "val_kode" => $cek['val_kode'],
                        "msg" => "Total Cost is already reach limit!"
                    );
                    continue;
                }
                if (bccomp($balancePR,0) <= 0)
                {
                    $error[] = array(
                        "kode_brg" => $cek['kode_brg'],
                        "workid" => $cek['workid'],
                        "workname" => $cek['workname'],
                        "nama_brg" => $cek['nama_brg'],
                        "totalPR" => $prRequested,
                        "total" => $total,
                        "totalCost" => $totalCost,
                        "totalInPR" => $totalPRD,
                        "qtyPR" => $v['qty'],
                        "qtyInPR" => $prd['qty'],
                        "val_kode" => $cek['val_kode'],
                        "msg" => "Total PR is already reach limit!"
                    );
                    continue;
                }
                //jangan pernah pake float untuk compare value, gunakan bccomp
//                if ($prTotal > $total)
                if (bccomp($prRequested,$total) > 0)
                {
                    $error[] = array(
                        "kode_brg" => $cek['kode_brg'],
                        "workid" => $cek['workid'],
                        "workname" => $cek['workname'],
                        "nama_brg" => $cek['nama_brg'],
                        "totalPR" => $prRequested,
                        "total" => $total,
                        "totalCost" => $totalCost,
                        "totalInPR" => $totalPRD,
                        "qtyPR" => $v['qty'],
                        "qtyInPR" => $prd['qty'],
                        "val_kode" => $cek['val_kode'],
                        "msg" => "Total PR Requested is greater than Budget (BOQ3)!"
                    );
                    continue;
                }
            }
        }
        $success = true;
        if (count($error) > 0)
        {
            $success = false;
        }

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode(array(
                                      "success" => $success,
                                      "error" => $error
                                  ));
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}