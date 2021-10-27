<?php
class Procurement_ProcurementpurchaseorderController extends Zend_Controller_Action
{
    private $db;
    private $xml;
    private $po;
    private $poH;
    private $barang;
    private $quantity;
    private $budget;
    
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->xml = $this->_helper->getHelper('xml');
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->procurement = new Default_Models_ProcurementPod();
        $this->procurementH = new Default_Models_ProcurementPoh();
        $this->barang = new Default_Models_MasterBarang();
        $this->budget = new Default_Models_Budget();
    }


    public function cekcostAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = $this->getRequest()->getParam("posts");
        $precision = ini_get('precision') + 2;
        //COST = ARF + PO

        $data = Zend_Json::decode($json);
        $budget = new Default_Models_Budget();

        $error = array();$i = 0;
        foreach($data as $k => $v)
        {
            $totalRequest = floatval($v['qty']) * floatval($v['price']);
            $cek = $budget->getBoq3ByOne($v['prj_kode'],$v['sit_kode'],$v['workid'],$v['kode_brg']);
            if ($cek)
            {
                if ($v['val_kode'] == 'IDR')
                {
                    $boq3Total = floatval($cek['qty']) * floatval($cek['hargaIDR']);
                }
                else
                {
                    $boq3Total = floatval($cek['qty']) * floatval($cek['hargaUSD']);
                }
                //jangan pernah pake float untuk compare value, gunakan bccomp
//                if ($prTotal > $total)

                if (bccomp($totalRequest,$boq3Total) > 0)
                {
                    $error[$i] = array(
                        "kode_brg" => $cek['kode_brg'],
                        "workid" => $cek['workid'],
                        "workname" => $cek['workname'],
                        "nama_brg" => $cek['nama_brg'],
                        "total" => $totalRequest,
                        "totalBOQ3" => $boq3Total,
                        "val_kode" => $cek['val_kode'],
                        "msg" => "Greater Than Current Budget (BOQ3)"
                    );
                }
                $pod = $this->quantity->getPoQuantity($v['prj_kode'],$v['sit_kode'],$v['workid'],$v['kode_brg']);
                $arf = $this->quantity->getArfQuantity($v['prj_kode'],$v['sit_kode'],$v['workid'],$v['kode_brg']);
                $asfcancel = $this->quantity->getAsfcancelQuantity($v['prj_kode'],$v['sit_kode'],$v['workid'],$v['kode_brg']);
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
                $balance = $boq3Total - $totalCost;
                if (bccomp($totalRequest,$balance) > 0)
                {
                    if ($error[$i] != '')
                    {
                        $error[$i]['totalCost'] = $totalCost;
                        $error[$i]['msg'] = "Greater than Allowed Cost";
                    }
                    else
                    {
                        $error[$i] = array(
                            "kode_brg" => $cek['kode_brg'],
                            "workid" => $cek['workid'],
                            "workname" => $cek['workname'],
                            "nama_brg" => $cek['nama_brg'],
                            "total" => $totalRequest,
                            "totalBOQ3" => $boq3Total,
                            "totalCost" => $totalCost,
                            "val_kode" => $cek['val_kode'],
                            "msg" => "Greater than Allowed Cost"
                        );
                    }
                }
                $i++;
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