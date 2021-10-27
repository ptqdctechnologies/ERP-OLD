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
    private $cost;
    
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
        $this->cost = new Default_Models_Cost();
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
        $tmp = array();
        foreach($data as $k => $v)
        {
            $prj = $v['prj_kode'];
            $sit = $v['sit_kode'];
            $workid = $v['workid'];
            $kode = $v['kode_brg'];
            $totalRequest = floatval($v['qty']) * floatval($v['price']);
            if (QDC_Transaction_Product::factory(array("workid" => $workid))->isMsc())
            {
                $tmp[$prj][$sit][$workid] += $totalRequest;
            }
            else
                $tmp[$prj][$sit][$workid][$kode] += $totalRequest;
        }

        foreach($data as $k => $v)
        {
            $prj = $v['prj_kode'];
            $sit = $v['sit_kode'];
            $workid = $v['workid'];
            $kode = $v['kode_brg'];
            if (QDC_Transaction_Product::factory(array("workid" => $workid))->isMsc())
            {
                if ($tmp[$prj][$sit][$workid] != '')
                    $totalRequest = $tmp[$prj][$sit][$workid];
                else
                    $totalRequest = floatval($v['qty']) * floatval($v['price']);
            }
            else
            {
                if ($tmp[$prj][$sit][$workid][$kode] != '')
                    $totalRequest = $tmp[$prj][$sit][$workid][$kode];
                else
                    $totalRequest = floatval($v['qty']) * floatval($v['price']);
            }
//            $totalRequest = floatval($v['qty']) * floatval($v['price']);
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

                $totalCost = $this->cost->committedCostTotal($v['prj_kode'],$v['sit_kode'],$v['workid'],$v['kode_brg']);
                $balance = $boq3Total - floatval($totalCost['amount']);
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
                            "totalCost" => floatval($totalCost['amount']),
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
            $tmp = $error;
            $i =0;
            unset($error);
            foreach($tmp as $k => $v)
            {
                $error[$i] = $v;
                $i++;
            }
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