<?php

class Procurement_ArfPulsaController extends Zend_Controller_Action
{
    private $DEFAULT;
    private $ADMIN;
    private $PROC;
    private $const;
    private $quantity;
    private $workflow;
    private $db;

    public function init()
    {
        $this->ADMIN = QDC_Model_Admin::init(array(
            "KodePulsa",
            "Workflowtrans"
        ));
        $this->DEFAULT = QDC_Model_Default::init(array(
            "MasterSite",
            "Budget",
            "MasterProject",
            "MasterWork",
            "MasterBarang",
            "Files",
            "MasterUser"
        ));
        $this->PROC = QDC_Model_Procurement::init(array(
            "Procurementarfh",
            "Procurementarfd"
        ));

        $this->db = Zend_Registry::get("db");
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->const = Zend_Registry::get('constant');
    }

    public function addAction()
    {

    }

    public function generateAction()
    {

    }

    public function getItemPulsaAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $select = $this->db->select()
            ->from(array($this->ADMIN->KodePulsa->__name()))
            ->order(array("nama_brg ASC"));

        $items = $this->db->fetchAll($select);
        if ($items)
        {
            $array['posts'] = $items;
            $array['count'] = count($items);
        }
        $json = Zend_Json::encode($array);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function generateExcelPulsaAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj = $this->_getParam("prj");
        $item = $this->_getParam("item");

        if (!$prj || !$item)
        {
            echo "{success: false}";
            die;
        }

        $prj = Zend_Json::decode($prj);
        $item = Zend_Json::decode($item);

        $kode_pulsa = array();
        foreach($item as $k => $v)
        {
            $kode_pulsa[] = $v['kode'];
        }

        $data = array();
        foreach($prj as $k => $v)
        {
            $isOverhead = false;
            $prj_kode = $v['Prj_Kode'];
            $select = $this->db->select()
                ->from(array($this->DEFAULT->MasterProject->__name()))
                ->where("prj_kode = '$prj_kode'");
            $cekProject = $this->db->fetchRow($select);
            // Cek apabila project adalah Overhead
            if ($cekProject['type'] == 'O')
                $isOverhead = true;

            $select = $this->db->select()
                ->from(array($this->DEFAULT->MasterSite->__name()))
                ->where("stsclose = 0")
                ->where("prj_kode = '$prj_kode'")
                ->order(array("sit_kode ASC"));

            $sites = $this->db->fetchAll($select);
            if ($sites)
            {
                foreach($sites as $k2 => $v2)
                {
                    $sit_kode = $v2['sit_kode'];
                    if (!$isOverhead)
                    {
                        $boq3 = $this->DEFAULT->Budget->getBoq3ByKodeBrg($prj_kode,$sit_kode,$kode_pulsa);
                        if (!$boq3)
                            continue;

                        foreach($boq3 as $k3 => $v3)
                        {
                            $v3['prj_kode'] = $prj_kode;
                            $v3['sit_kode'] = $sit_kode;
                            $workid = $v3['workid'];
                            $kode_brg = $v3['kode_brg'];
                            $po = $this->quantity->getPoQuantity($prj_kode,$sit_kode,$workid,$kode_brg);
                            $arf = $this->quantity->getArfQuantity($prj_kode,$sit_kode,$workid,$kode_brg);
                            $asfcancel = $this->quantity->getAsfcancelQuantity($prj_kode,$sit_kode,$workid,$kode_brg);
                            $sal = $this->quantity->getSalQuantity($prj_kode,$sit_kode,$workid,$kode_brg);

                            $qtyCost = 0;
                            $IDRCost = 0;
                            $USDCost = 0;
                            if ($po != '' )
                            {
                                $qtyCost += floatval($po['qty']);
                                if ($v3['val_kode'] == 'IDR')
                                    $IDRCost += $po['totalIDR'];
                                else
                                    $USDCost += $po['totalHargaUSD'];
                            }

                            if ($arf != '' )
                            {
                                $qtyCost += $arf['qty'];
                                if ($v3['val_kode'] == 'IDR')
                                    $IDRCost += $arf['totalIDR'];
                                else
                                    $USDCost += $arf['totalHargaUSD'];
                            }

                            if ($asfcancel != '' )
                            {
                                $qtyCost -= $asfcancel['qty'];
                                if ($v3['val_kode'] == 'IDR')
                                    $IDRCost -= $asfcancel['totalIDR'];
                                else
                                    $USDCost -= $asfcancel['totalHargaUSD'];
                            }

                            if ($sal != '' )
                            {
                                $IDRCost += $sal['totalIDR'];
                            }

                            if ($v3['val_kode'] == 'IDR')
                            {
                                $v3['cost'] = $IDRCost;
                                $v3['balance'] = floatval($v3['qty'] * $v3['hargaIDR']) - $v3['cost'];

                            }
                            else
                            {
                                $v3['cost'] = $USDCost;
                                $v3['balance'] = floatval($v3['qty'] * $v3['hargaUSD']) - $v3['cost'];

                            }

                            $v3['qty_cost'] = $qtyCost;

                            if ($v3['balance'] <= 0)
                                continue;
                            $data[] = $v3;
                        }
                    }
                    else
                    {
                        $boq3Overhead = $this->DEFAULT->Budget->getBudgetOverhead($prj_kode,$sit_kode);
                        if (!$boq3Overhead)
                            continue;

                        foreach($boq3Overhead as $k => $boq3)
                        {
                            $boq3['prj_kode'] = $prj_kode;
                            $boq3['sit_kode'] = $sit_kode;
                            $workid = $boq3['budgetid'];
                            $boq3['workid'] = $workid;
                            $boq3['workname'] = $boq3['budgetname'];
                            $boq3['qty'] = 1;
                            if ($boq3['val_kode'] == 'IDR')
                            {
                                $boq3['harga'] = $boq3['totalHargaIDR'];
                                $boq3['hargaIDR'] = $boq3['totalHargaIDR'];
                            }
                            else
                            {
                                $boq3['harga'] = $boq3['totalHargaUSD'];
                                $boq3['hargaUSD'] = $boq3['totalHargaUSD'];
                            }
                            $po = $this->quantity->getPoQuantity($prj_kode,$sit_kode,$workid);
                            $arf = $this->quantity->getArfQuantity($prj_kode,$sit_kode,$workid);
                            $asfcancel = $this->quantity->getAsfcancelQuantity($prj_kode,$sit_kode,$workid);
                            $sal = $this->quantity->getSalQuantity($prj_kode,$sit_kode,$workid);

                            $qtyCost = 0;
                            $IDRCost = 0;
                            $USDCost = 0;
                            if ($po != '' )
                            {
                                $qtyCost += floatval($po['qty']);
                                if ($boq3['val_kode'] == 'IDR')
                                    $IDRCost += $po['totalIDR'];
                                else
                                    $USDCost += $po['totalHargaUSD'];
                            }

                            if ($arf != '' )
                            {
                                $qtyCost += $arf['qty'];
                                if ($boq3['val_kode'] == 'IDR')
                                    $IDRCost += $arf['totalIDR'];
                                else
                                    $USDCost += $arf['totalHargaUSD'];
                            }

                            if ($asfcancel != '' )
                            {
                                $qtyCost -= $asfcancel['qty'];
                                if ($boq3['val_kode'] == 'IDR')
                                    $IDRCost -= $asfcancel['totalIDR'];
                                else
                                    $USDCost -= $asfcancel['totalHargaUSD'];
                            }

                            if ($sal != '' )
                            {
                                $IDRCost += $sal['totalIDR'];
                            }

                            if ($boq3['val_kode'] == 'IDR')
                            {
                                $boq3['cost'] = $IDRCost;
                                $boq3['balance'] = floatval($boq3['hargaIDR']) - $boq3['cost'];
                            }
                            else
                            {
                                $boq3['cost'] = $USDCost;
                                $boq3['balance'] = floatval($boq3['hargaUSD']) - $boq3['cost'];
                            }

                            $boq3['qty_cost'] = $qtyCost;

                            if ($boq3['balance'] <= 0)
                                continue;

                            $data[] = $boq3;
                        }

                    }

                }
            }
        }
        if (count($data) > 0)
        {
            $newData = array();
            foreach($data as $k => $v)
            {
                $isOverhead = false;
                $prj_kode = $v['prj_kode'];
                $select = $this->db->select()
                    ->from(array($this->DEFAULT->MasterProject->__name()))
                    ->where("prj_kode = '$prj_kode'");
                $cekProject = $this->db->fetchRow($select);
                // Cek apabila project adalah Overhead
                if ($cekProject['type'] == 'O')
                    $isOverhead = true;

                if ($v['val_kode'] == 'IDR')
                    $budget = $v['hargaIDR'];
                else
                    $budget = $v['hargaUSD'];

                if (!$isOverhead)
                {
                    $kodeBrg = $v['kode_brg'];
                    $namaBrg = $v['nama_brg'];
                    $budget =  ($v['qty'] * $budget);
                }
                else
                {
                    $kodeBrg = '2001398';
                    $brg = $this->DEFAULT->MasterBarang->fetchRow("kode_brg = '$kodeBrg'");
                    if ($brg)
                    {
                        $namaBrg = $brg['nama_brg'];
                    }
                }
                $newData[] = array(
                    "Project Code" => $v['prj_kode'],
                    "Site Code" => (string)$v['sit_kode'],
                    "Workid" => $v['workid'],
                    "Product ID" => $kodeBrg,
                    "Product Name" => $namaBrg,
                    "Valuta" => $v['val_kode'],
//                    "Budget Price" => $budget,
                    "Budget Total" => $budget,
                    "Total Cost" => $v['cost'],
                    "Balance" => $v['balance'],
                    "Qty Request" => '',
                    "Price" => ''
                );

            }

            $filename = "checklist_ARF_pulsa" . "_" . date("dmYHis") . "_" .  rand(0,1000);
            QDC_Adapter_Excel::factory(array(
                "fileName" => $filename
            ))->write($newData,array(
                "Daftar Product/Item diatas adalah hasil dari pencarian Kode Pulsa yang terdaftar di QDC ERP",
                "Jika tidak terdapat Product/Item yang diinginkan, maka Product/Item tsb harus ditambahkan ke dalam system.",
                "Silahkan hubungi IT Support untuk keterangan lebih lanjut",
                "Data ini up to date sampai tanggal dan waktu yang tercetak dibawah."
            ))->toExcel5();

            $result = Zend_Json::encode(array("success" => true, "file" => "$filename.xls"));
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($result);
        }
        else
        {
            $result = Zend_Json::encode(array("success" => false, "msg" => "Sorry, Budget for Your projects is not sufficient or Not exist on BOQ3. Please try other Product ID or Project Code."));
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($result);
        }
    }
    public function uploadArfPulsaAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $result = QDC_Adapter_File::factory()->upload($_FILES,'file-path');
        if ($result)
        {
//            $indeks = array(
//                1 => "prj_kode",
//                2 => "sit_kode",
//                3 => "workid",
//                4 => "kode_brg",
//                5 => "val_kode",
//                6 => "qty",
//                7 => "harga",
//                8 => "manager",
//                9 => "requester"
//            );
            $indeks = array(
                1 => "prj_kode",
                2 => "sit_kode",
                3 => "workid",
                4 => "kode_brg",
                5 => "nama_brg",
                6 => "val_kode",
                7 => "budget_total",
                8 => "cost",
                9 => "balance",
                10 => "qty",
                11 => "harga",
                12 => "manager",
                13 => "requester",
            );
            $data = QDC_Adapter_Excel::factory(array("fileName" => $result['save_file']))->read(2,$indeks);
            if ($data)
            {
                if (file_exists($result['save_file']))
                {
                    unlink($result['save_file']);
                }
                $users = new Admin_Models_Masterlogin();
                $newdata = array();
                foreach($data as $k => $v)
                {
                    if ($v['prj_kode'] == '' || $v['sit_kode'] == '' || $v['workid'] == '' || $v['kode_brg'] == '' || ($v['qty'] == '' || $v['qty'] == 0) || ($v['harga'] == '' || $v['harga'] == 0))
                        continue;

                    $newdata[] = $data[$k];
                }

                $data = $newdata;
                foreach($data as $k => $v)
                {
                    if ($v['prj_kode'] == '' || $v['sit_kode'] == '' || $v['workid'] == '' || $v['kode_brg'] == '' || ($v['qty'] == '' || $v['qty'] == 0) || ($v['harga'] == '' || $v['harga'] == 0))
                        continue;
                    $isOverhead = false;
                    $data[$k]['prj_nama'] = $this->DEFAULT->MasterProject->getProjectName($v['prj_kode']);

                    $prj_kode = $v['prj_kode'];
                    $select = $this->db->select()
                        ->from(array($this->DEFAULT->MasterProject->__name()))
                        ->where("prj_kode = '$prj_kode'");
                    $cekProject = $this->db->fetchRow($select);
                    // Cek apabila project adalah Overhead
                    if ($cekProject['type'] == 'O')
                        $isOverhead = true;

                    $data[$k]['sit_nama'] = $this->DEFAULT->MasterSite->getSiteName($v['prj_kode'],$v['sit_kode']);

                    if (!$isOverhead)
                        $data[$k]['workname'] = $this->DEFAULT->MasterWork->getWorkname($v['workid']);
                    else
                        $data[$k]['workname'] = $data[$k]['sit_nama'];

                    $data[$k]['nama_brg'] = $this->DEFAULT->MasterBarang->getName($v['kode_brg']);
                    $data[$k]['total'] = $v['qty'] * $v['harga'];

                    if ($v['manager'] == '')
                        $data[$k]['manager'] = '';
                    else
                    {
                        if (!$users->isExist($data[$k]['manager']))
                            $data[$k]['manager'] = '';
                    }
                    if($v['requester'] == '')
                        $data[$k]['requester'] = '';
                    else
                    {
                        if (!$users->isExist($data[$k]['requester']))
                            $data[$k]['requester'] = '';
                    }

                    $prj_kode = $v['prj_kode'];
                    $sit_kode = $v['sit_kode'];
                    $kode_brg = $v['kode_brg'];
                    $workid = $v['workid'];

                    $isPulsa = $this->ADMIN->KodePulsa->isExist($kode_brg);
                    if (!$isPulsa)
                    {
                        $data[$k]['invalid'] = true;
                        $data[$k]['invalid_msg'] = "This item is not listed as Kode Pulsa, Please contact IT Support for detail information";
                        continue;
                    }

                    //Cek balance dari masing2 barang
                    if (!$isOverhead)
                    {
                        $boq3 = $this->DEFAULT->Budget->getBoq3ByOne($prj_kode,$sit_kode,$workid,$kode_brg);
                        if ($boq3)
                        {
                            $po = $this->quantity->getPoQuantity($prj_kode,$sit_kode,$workid,$kode_brg);
                            $arf = $this->quantity->getArfQuantity($prj_kode,$sit_kode,$workid,$kode_brg);
                            $asfcancel = $this->quantity->getAsfcancelQuantity($prj_kode,$sit_kode,$workid,$kode_brg);
                            $sal = $this->quantity->getSalQuantity($prj_kode,$sit_kode,$workid,$kode_brg);

                            $qtyCost = 0;
                            $IDRCost = 0;
                            $USDCost = 0;
                            if ($po != '' )
                            {
                                $qtyCost += floatval($po['qty']);
                                if ($boq3['val_kode'] == 'IDR')
                                    $IDRCost += $po['totalIDR'];
                                else
                                    $USDCost += $po['totalHargaUSD'];
                            }

                            if ($arf != '' )
                            {
                                $qtyCost += $arf['qty'];
                                if ($boq3['val_kode'] == 'IDR')
                                    $IDRCost += $arf['totalIDR'];
                                else
                                    $USDCost += $arf['totalHargaUSD'];
                            }

                            if ($asfcancel != '' )
                            {
                                $qtyCost -= $asfcancel['qty'];
                                if ($boq3['val_kode'] == 'IDR')
                                    $IDRCost -= $asfcancel['totalIDR'];
                                else
                                    $USDCost -= $asfcancel['totalHargaUSD'];
                            }

                            if ($sal != '' )
                            {
                                $IDRCost += $sal['totalIDR'];
                            }

                            if ($boq3['val_kode'] == 'IDR')
                            {
                                $boq3['cost'] = $IDRCost;
                                $boq3['balance'] = floatval($boq3['qty'] * $boq3['hargaIDR']) - $boq3['cost'];
                            }
                            else
                            {
                                $boq3['cost'] = $USDCost;
                                $boq3['balance'] = floatval($boq3['qty'] * $boq3['hargaUSD']) - $boq3['cost'];
                            }

                            $boq3['qty_cost'] = $qtyCost;

                            $request = $v['qty'] * $v['harga'];
                            $requestQty = $v['qty'];

    //                        if ($workid != 1100 && $workid != 2100 && $workid != 3100 && $workid != 4100 && $workid != 5100 && $workid != 6100)
    //                        {
    //                            $balanceQty = ($boq3['qty'] - $qtyCost);
    //                            if (bccomp($requestQty,$balanceQty) > 0)
    //                            {
    //                                $data[$k]['invalid'] = true;
    //                                $data[$k]['invalid_msg'] = "Your request for this Item is over budget, max allowed qty : " . number_format($balanceQty,4);
    //                                continue;
    //                            }
    //                        }

                            if (bccomp($request,$boq3['balance']) > 0)
                            {
                                $data[$k]['invalid'] = true;
                                $data[$k]['invalid_msg'] = "Your request for this Item is over budget, max allowed budget : " . $boq3['val_kode'] . " " . number_format($boq3['balance'],2);
                                continue;
                            }

                        }
                        else
                        {
                            $data[$k]['invalid'] = true;
                            $data[$k]['invalid_msg'] = "This Item is not listed on BOQ3 for $prj_kode - $sit_kode";
                        }
                    }
                    else
                    {
                        $boq3 = $this->DEFAULT->Budget->getBudgetOverhead($prj_kode,$sit_kode,$workid);
                        if ($boq3)
                        {
                            $boq3 = $boq3[0];
                            $po = $this->quantity->getPoQuantity($prj_kode,$sit_kode,$workid);
                            $arf = $this->quantity->getArfQuantity($prj_kode,$sit_kode,$workid);
                            $asfcancel = $this->quantity->getAsfcancelQuantity($prj_kode,$sit_kode,$workid);
                            $sal = $this->quantity->getSalQuantity($prj_kode,$sit_kode,$workid);

                            if ($boq3['val_kode'] == 'IDR')
                                $boq3['hargaIDR'] = $boq3['totalHargaIDR'];
                            else
                                $boq3['hargaUSD'] = $boq3['totalHargaUSD'];

                            $qtyCost = 0;
                            $IDRCost = 0;
                            $USDCost = 0;
                            if ($po != '' )
                            {
                                $qtyCost += floatval($po['qty']);
                                if ($boq3['val_kode'] == 'IDR')
                                    $IDRCost += $po['totalIDR'];
                                else
                                    $USDCost += $po['totalHargaUSD'];
                            }

                            if ($arf != '' )
                            {
                                $qtyCost += $arf['qty'];
                                if ($boq3['val_kode'] == 'IDR')
                                    $IDRCost += $arf['totalIDR'];
                                else
                                    $USDCost += $arf['totalHargaUSD'];
                            }

                            if ($asfcancel != '' )
                            {
                                $qtyCost -= $asfcancel['qty'];
                                if ($boq3['val_kode'] == 'IDR')
                                    $IDRCost -= $asfcancel['totalIDR'];
                                else
                                    $USDCost -= $asfcancel['totalHargaUSD'];
                            }

                            if ($sal != '' )
                            {
                                $IDRCost += $sal['totalIDR'];
                            }

                            if ($boq3['val_kode'] == 'IDR')
                            {
                                $boq3['cost'] = $IDRCost;
                                $boq3['balance'] = floatval($boq3['hargaIDR']) - $boq3['cost'];
                            }
                            else
                            {
                                $boq3['cost'] = $USDCost;
                                $boq3['balance'] = floatval($boq3['hargaUSD']) - $boq3['cost'];
                            }

                            $boq3['qty_cost'] = $qtyCost;

                            $request = $v['qty'] * $v['harga'];

                            if (bccomp($request,$boq3['balance']) > 0)
                            {
                                $data[$k]['invalid'] = true;
                                $data[$k]['invalid_msg'] = "Your request for this Item is over budget, max allowed budget : " . $boq3['val_kode'] . " " . number_format($boq3['balance'],2);
                                continue;
                            }

                        }
                        else
                        {
                            $data[$k]['invalid'] = true;
                            $data[$k]['invalid_msg'] = "This Item is not listed on BOQ3 for $prj_kode - $sit_kode";
                        }
                    }
                }

                $result = array(
                    "success" => true,
                    "data" => $data
                );
            }
        }
        else
            $result = array(
                "success" => false,
                "msg" => "Error on uploading Your file"
            );

        echo Zend_Json::encode($result);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function cekArfPulsaAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = $this->_getParam("json");

        if (!$json)
            return false;

        $data = Zend_Json::decode($json);
        foreach($data as $k => $v)
        {
            $row = $k + 1;
            $prj_kode = $v['prj_kode'];
            $sit_kode = $v['sit_kode'];
            $kode_brg = $v['kode_brg'];
            $workid = $v['workid'];

            $select = $this->db->select()
                ->from(array($this->DEFAULT->MasterProject->__name()))
                ->where("prj_kode = '$prj_kode'");
            $cekProject = $this->db->fetchRow($select);
            // Cek apabila project adalah Overhead
            if ($cekProject['type'] == 'O')
                $isOverhead = true;

            $tranoExclude = $v['trano'];

            if (!$isOverhead)
            {
                $isPulsa = $this->ADMIN->KodePulsa->isExist($kode_brg);
                if (!$isPulsa)
                {
                    $err = "This item : $kode_brg (Row : $row) is not listed as Kode Pulsa, Please contact IT Support for detail information";
                    continue;
                }
            }

            if (!$isOverhead)
            {

                $boq3 = $this->DEFAULT->Budget->getBoq3ByOne($prj_kode,$sit_kode,$workid,$kode_brg);
                if ($boq3)
                {
                    $po = $this->quantity->getPoQuantity($prj_kode,$sit_kode,$workid,$kode_brg);
                    $arf = $this->quantity->getArfQuantity($prj_kode,$sit_kode,$workid,$kode_brg,$tranoExclude);
                    $asfcancel = $this->quantity->getAsfcancelQuantity($prj_kode,$sit_kode,$workid,$kode_brg);
                    $sal = $this->quantity->getSalQuantity($prj_kode,$sit_kode,$workid,$kode_brg);

                    $qtyCost = 0;
                    $IDRCost = 0;
                    $USDCost = 0;
                    if ($po != '' )
                    {
                        $qtyCost += floatval($po['qty']);
                        if ($boq3['val_kode'] == 'IDR')
                            $IDRCost += $po['totalIDR'];
                        else
                            $USDCost += $po['totalHargaUSD'];
                    }

                    if ($arf != '' )
                    {
                        $qtyCost += $arf['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $IDRCost += $arf['totalIDR'];
                        else
                            $USDCost += $arf['totalHargaUSD'];
                    }

                    if ($asfcancel != '' )
                    {
                        $qtyCost -= $asfcancel['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $IDRCost -= $asfcancel['totalIDR'];
                        else
                            $USDCost -= $asfcancel['totalHargaUSD'];
                    }

                    if ($sal != '' )
                    {
                        $IDRCost += $sal['totalIDR'];
                    }

                    if ($boq3['val_kode'] == 'IDR')
                    {
                        $boq3['cost'] = $IDRCost;
                        $boq3['balance'] = floatval($boq3['qty'] * $boq3['hargaIDR']) - $boq3['cost'];
                    }
                    else
                    {
                        $boq3['cost'] = $USDCost;
                        $boq3['balance'] = floatval($boq3['qty'] * $boq3['hargaUSD']) - $boq3['cost'];
                    }

                    $cost = $boq3['cost'];

                    $request = $v['qty'] * $v['harga'];
                    $requestQty = $v['qty'];

                    $balanceQty = ($boq3['qty'] - $qtyCost);
                    $balance = $boq3['balance'];
    //                if ($workid != 1100 && $workid != 2100 && $workid != 3100 && $workid != 4100 && $workid != 5100 && $workid != 6100)
    //                {
    //                    if (bccomp($requestQty,$balanceQty) > 0)
    //                    {
    //                        $err =  "Your request for this Item is over budget, max allowed qty : " . number_format($balanceQty,4);
    //                        break;
    //                    }
    //                }

                    if (bccomp($request,$boq3['balance']) > 0)
                    {
                        $err = "Your request for this Item : $kode_brg is over budget, max allowed budget : " . $boq3['val_kode'] . " " . number_format($boq3['balance'],2);
                        break;
                    }
                }
                else
                {
                    $err = "This Item : $kode_brg is not listed on BOQ3 for $prj_kode - $sit_kode, Please revise current Excel Or Delete this item from list";
                    break;
                }
            }
            else
            {
                $boq3 = $this->DEFAULT->Budget->getBudgetOverhead($prj_kode,$sit_kode,$workid);
                if ($boq3)
                {
                    $boq3 = $boq3[0];
                    $po = $this->quantity->getPoQuantity($prj_kode,$sit_kode,$workid);
                    $arf = $this->quantity->getArfQuantity($prj_kode,$sit_kode,$workid,'',$tranoExclude);
                    $asfcancel = $this->quantity->getAsfcancelQuantity($prj_kode,$sit_kode,$workid);
                    $sal = $this->quantity->getSalQuantity($prj_kode,$sit_kode,$workid);

                    if ($boq3['val_kode'] == 'IDR')
                        $boq3['hargaIDR'] = $boq3['totalHargaIDR'];
                    else
                        $boq3['hargaUSD'] = $boq3['totalHargaUSD'];

                    $qtyCost = 0;
                    $IDRCost = 0;
                    $USDCost = 0;
                    if ($po != '' )
                    {
                        $qtyCost += floatval($po['qty']);
                        if ($boq3['val_kode'] == 'IDR')
                            $IDRCost += $po['totalIDR'];
                        else
                            $USDCost += $po['totalHargaUSD'];
                    }

                    if ($arf != '' )
                    {
                        $qtyCost += $arf['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $IDRCost += $arf['totalIDR'];
                        else
                            $USDCost += $arf['totalHargaUSD'];
                    }

                    if ($asfcancel != '' )
                    {
                        $qtyCost -= $asfcancel['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $IDRCost -= $asfcancel['totalIDR'];
                        else
                            $USDCost -= $asfcancel['totalHargaUSD'];
                    }

                    if ($sal != '' )
                    {
                        $IDRCost += $sal['totalIDR'];
                    }

                    if ($boq3['val_kode'] == 'IDR')
                    {
                        $boq3['cost'] = $IDRCost;
                        $boq3['balance'] = floatval($boq3['hargaIDR']) - $boq3['cost'];
                    }
                    else
                    {
                        $boq3['cost'] = $USDCost;
                        $boq3['balance'] = floatval($boq3['hargaUSD']) - $boq3['cost'];
                    }

                    $cost = $boq3['cost'];

                    $request = $v['qty'] * $v['harga'];
                    $requestQty = $v['qty'];

                    $balanceQty = ($boq3['qty'] - $qtyCost);
                    $balance = $boq3['balance'];

                    if (bccomp($request,$boq3['balance']) > 0)
                    {
                        $err = "Your request for this Item : $kode_brg is over budget, max allowed budget : " . $boq3['val_kode'] . " " . number_format($boq3['balance'],2);
                        break;
                    }
                }
                else
                {
                    $err = "This Item : $kode_brg is not listed on BOQ3 for $prj_kode - $sit_kode, Please revise current Excel Or Delete this item from list";
                    break;
                }
            }
        }

        if ($err != '')
            $result = array(
                "success" => false,
                "msg" => $err
            );
        else
            $result = array(
                "success" => true
            );

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function submitArfPulsaAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = $this->_getParam("json");
        $file = $this->_getParam("file");
        if (!$json)
            return false;

        $useOverride = ($this->_getParam("useOverride") != '') ? true : false;

        $data =Zend_Json::decode($json);
        if ($file)
            $file =Zend_Json::decode($file);

        $arrayError = array(); $arrayPrj = array();
        foreach($data as $k => $v)
        {
            $items = $v;
            $items["prj_kode"] = $v['prj_kode'];
            $items['next'] = $this->getRequest()->getParam('next');
            $items['uid_next'] = $this->getRequest()->getParam('uid_next');
            $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
            $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
            $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
            $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

            $prj = $v['prj_kode'];
            $site = $v['sit_kode'];
            $arrayPrj[$prj][$site] = '';
            $arrayTrano[$prj] = '';
            $params = array(
                "workflowType" => "ARFP",
                "paramArray" => '',
                "approve" => $this->const['DOCUMENT_SUBMIT'],
                "items" => $items,
                "prjKode" => $v['prj_kode'],
                "generic" => true,
                "revisi" => false,
                "returnException" => true,
                "uidApproval" => QDC_User_Session::factory()->getCurrentUID(),
                "addQuery" => ' AND is_start = 1'
            );
            try
            {
                $cek = $this->workflow->checkWorkflowTrans($params);
            }
            catch (Exception $e)
            {
                $cek = $e->getMessage();
            }
            if ($cek !== true)
            {
                $arrayError[] = "Workflow Error for Project <b>" . $v['prj_kode'] . "</b> :<br>" . $cek;
            }
        }

        if (count($arrayError) > 0)
        {
            $result = array(
                "success" => false,
                "msgArray" => $arrayError
            );
        }
        else
        {
            $arrayError = array();
            $lastTrano = '';
            $urut = 1;
            $captionID = $this->ADMIN->Workflowtrans->getCaptionID("ARF Pulsa " . date ("d M Y"));
            foreach($data as $k => $v)
            {
                $workflowError = false;
                $items = $v;
                $items["prj_kode"] = $v['prj_kode'];
                $items['next'] = $this->getRequest()->getParam('next');
                $items['uid_next'] = $this->getRequest()->getParam('uid_next');
                $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
                $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
                $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
                $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

                $prj = $v['prj_kode'];
                $site = $v['sit_kode'];
                $params = array(
                    "workflowType" => "ARFP",
                    "paramArray" => '',
                    "approve" => $this->const['DOCUMENT_SUBMIT'],
                    "items" => $items,
                    "prjKode" => $v['prj_kode'],
                    "generic" => true,
                    "revisi" => false,
                    "returnException" => true,
                    "captionId" => $captionID,
                    "useOverride" => $useOverride,
                    "uidApproval" => QDC_User_Session::factory()->getCurrentUID()
                );

                if ($arrayTrano[$prj] != '')
                {
                    $params['lastTrano'] = $arrayTrano[$prj];
                }

                try
                {
                    $trano = $this->workflow->setWorkflowTransNew($params);
                }
                catch (Exception $e)
                {
                    $arrayError[] = "Project : " . $prj . ": " . $e->getMessage();
                    $workflowError = true;
                }

                if (!$workflowError)
                {
                    if ($arrayTrano[$prj] == '')
                    {
                        $arrayTrano[$prj] = $trano;
                    }

                    //Insert ARF Pulsa to database
                    if ($arrayPrj[$prj][$site] == '')
                    {
                        $counter = new Default_Models_MasterCounter();
                        $arfTrano = $counter->setNewTrans('ARF'); //Trano baru untuk ARF

                        $arrayPrj[$prj][$site] = array(
                            "trano" => $arfTrano,
                            "trano_ref" => $arrayTrano[$prj],
                            "tgl" => date("Y-m-d"),
                            "prj_kode" => $v['prj_kode'],
                            "prj_nama" => $v['prj_nama'],
                            "sit_kode" => $v['sit_kode'],
                            "sit_nama" => $v['sit_nama'],
                            "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                            "total" => ($v['qty'] * $v['harga']),
                            "request" => $v['manager'],
                            "user" => QDC_User_Session::factory()->getCurrentUID(),
                            "tglinput" => date("Y-m-d"),
                            "jam" => date("H:i:s"),
                            "val_kode" => $v['val_kode'],
                        );
                    }
                    else
                    {
                        $arrayPrj[$prj][$site]['total'] += ($v['qty'] * $v['harga']);
                    }
                    $arrayInsert = array(
                        "trano" => $arrayPrj[$prj][$site]['trano'],
                        "trano_ref" => $arrayTrano[$prj],
                        "tgl" => date("Y-m-d"),
                        "prj_kode" => $v['prj_kode'],
                        "prj_nama" => $v['prj_nama'],
                        "sit_kode" => $v['sit_kode'],
                        "sit_nama" => $v['sit_nama'],
                        "workid" => $v['workid'],
                        "workname" => $v['workname'],
                        "kode_brg" => $v['kode_brg'],
                        "nama_brg" => $v['nama_brg'],
                        "qty" => $v['qty'],
                        "harga" => $v['harga'],
                        "total" => ($v['qty'] * $v['harga']),
                        "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                        "val_kode" => $v['val_kode'],
                        "requester" => $v['requester'],
                        "urut" => $urut
                    );

                    $this->PROC->Procurementarfd->insert($arrayInsert);
                }

            }

            if (count($arrayPrj > 0))
            {
                foreach($arrayPrj as $k => $v)
                {
                    foreach($v as $k2 => $v2)
                    {
                        if (is_array($v2))
                            $this->PROC->Procurementarfh->insert($v2);
                    }
                }

                if ($file)
                {
                    foreach ($file as $key => $val)
                    {
                        $arrayInsert = array (
                            "trano" => $captionID,
                            "prj_kode" => '',
                            "date" => date("Y-m-d H:i:s"),
                            "uid" => QDC_User_Session::factory()->getCurrentUID(),
                            "filename" => $val['filename'],
                            "savename" => $val['savename']
                        );
                        $this->DEFAULT->Files->insert($arrayInsert);
                    }
                }
            }



            $result = array(
                "success" => true,
                "number" => $captionID,
                "errorMsg" => $arrayError
            );
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function appArfPulsaAction()
    {
        $workflowID = $this->_getParam("approve");
        $show = $this->_getParam("show");
        $params = $this->_getParam("params");
        if ($params)
        {
            $params = Zend_Json::decode($params);
        }

        if ($show)
        {
            $cek = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id = $workflowID");
            if ($cek)
            {
                $captionID = $cek['caption_id'];
                $this->view->caption_id = $captionID;
                $select = $this->db->select()
                    ->from(array($this->ADMIN->Workflowtrans->__name()))
                    ->where("caption_id = '$captionID'")
                    ->group(array("item_id"));

                $data = $this->db->fetchAll($select);
                if ($data)
                {
                    foreach($data as $k => $v)
                    {
                        $trano = $v['item_id'];
                        $pulsa = $this->PROC->Procurementarfh->fetchRow("trano_ref = '$trano'",array("prj_kode ASC", "sit_kode ASC"));

                        if($pulsa)
                        {
                            $pulsa = $pulsa->toArray();
                            $prj_kode = $pulsa['prj_kode'];
                            $sit_kode = $pulsa['sit_kode'];
                            $dataPulsaDetail[$prj_kode]['header'] = $pulsa;
                            $dataPulsaDetail[$prj_kode]['item_id'] = $trano;
                        }

                        $pulsaDetail = $this->PROC->Procurementarfd->fetchAll("trano_ref = '$trano'",array("prj_kode ASC", "sit_kode ASC"));
                        if($pulsaDetail)
                            $pulsaDetail = $pulsaDetail->toArray();
                        foreach($pulsaDetail as $k2 => $v2)
                        {
                            $dataPulsaDetail[$prj_kode]['detail'][] = $v2;
                        }
                    }
                }
                $cekFile = $this->DEFAULT->Files->fetchAll("trano = '$captionID'");
                if ($cekFile)
                    $dataFile = $cekFile->toArray();

                $this->view->show = $show;
            }
        }
        else
        {
            if ($workflowID != '')
            {
                if (!$params)
                    $params[] = $workflowID;

                $allTrans = array();

                foreach($params as $k => $v)
                {
                    $cek = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id = $v");
                    if ($cek)
                    {
                        $docs = $cek->toArray();
                        $captionID = $docs['caption_id'];
                        $this->view->caption_id = $captionID;
                        $trano= $docs['item_id'];
                        $allTrans[] = array(
                            "trano" => $trano,
                            "trans_id" => $v
                        );
                        $statApprove = $docs['approve'];
                        $prjKode = $docs['prj_kode'];
                        if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        {
                            $this->view->reject = true;
                            $lastReject = $this->workflow->getLastRejectGeneric($trano);
                            $this->view->lastReject = $lastReject;
                        }

                        //Gather all ARF Pulsa
                        $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],QDC_User_Session::factory()->getCurrentID());
                        if ($user)
                        {
                            $pulsa = $this->PROC->Procurementarfh->fetchRow("trano_ref = '$trano'",array("prj_kode ASC", "sit_kode ASC"));

                            if($pulsa)
                            {
                                $pulsa = $pulsa->toArray();
                                $prj_kode = $pulsa['prj_kode'];
                                $sit_kode = $pulsa['sit_kode'];
                                $dataPulsaDetail[$prj_kode]['header'] = $pulsa;
                                $dataPulsaDetail[$prj_kode]['item_id'] = $trano;
                                $dataPulsaDetail[$prj_kode]['workflow_trans_id'] = $v;
                                $dataPulsaDetail[$prj_kode]['uid_next'] = $docs['uid_next'];
                            }

                            $pulsaDetail = $this->PROC->Procurementarfd->fetchAll("trano_ref = '$trano'",array("prj_kode ASC", "sit_kode ASC"));
                            if($pulsaDetail)
                                $pulsaDetail = $pulsaDetail->toArray();
                            foreach($pulsaDetail as $k2 => $v2)
                            {
                                $dataPulsaDetail[$prj_kode]['detail'][] = $v2;
                            }

                        }
                    }
                }

                $cekFile = $this->DEFAULT->Files->fetchAll("trano = '$captionID'");
                if ($cekFile)
                    $dataFile = $cekFile->toArray();
            }
        }


        if ($allTrans)
            $this->view->allTrans = Zend_Json::encode($allTrans);
        $this->view->dataPulsa = $dataPulsa;
        $this->view->dataPulsaDetail = $dataPulsaDetail;
        $this->view->dataFile = $dataFile;
        $this->view->userID = QDC_User_Session::factory()->getCurrentID();
        $this->view->uidNext = QDC_User_Session::factory()->getCurrentUID();
    }

    public function editAction()
    {
        $this->view->json = $this->_getParam("json");
        if ($this->_getParam("json"))
        {
            $file = array();
            $json = Zend_Json::decode($this->_getParam("json"));
            foreach($json as $key => $val)
            {
                $trano = $val['trano'];
                $wt = $this->ADMIN->Workflowtrans->fetchRow("item_id = '$trano'")->toArray();
                $captionID = $wt['caption_id'];
                $file = $this->DEFAULT->Files->fetchAll("trano = '$captionID'");
                $file = $file->toArray();
                break;
            }
            $this->view->file = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
        }
    }

    public function editGetDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = $this->_getParam("json");
        if (!$json)
            return false;

        $json = Zend_Json::decode($json);
        $data = array();$i = 0;
        foreach($json as $key => $val)
        {
            $trano = $val['trano'];

            $wt = $this->ADMIN->Workflowtrans->fetchRow("item_id = '$trano'")->toArray();
            $captionID = $wt['caption_id'];
            $arfh = $this->PROC->Procurementarfh->fetchRow("trano_ref = '$trano'")->toArray();
            $manager = $arfh['request'];
            $arfd = $this->PROC->Procurementarfd->fetchAll("trano_ref = '$trano'")->toArray();

            foreach($arfd as $k => $v)
            {
                $data[$i] = $v;
                $prj_kode = $v['prj_kode'];
                $sit_kode = $v['sit_kode'];
                $kode_brg = $v['kode_brg'];
                $workid = $v['workid'];

                $select = $this->db->select()
                    ->from(array($this->DEFAULT->MasterProject->__name()))
                    ->where("prj_kode = '$prj_kode'");
                $cekProject = $this->db->fetchRow($select);
                // Cek apabila project adalah Overhead
                if ($cekProject['type'] == 'O')
                    $isOverhead = true;

                $data[$i]['manager'] = $manager;
                $data[$i]['caption_id'] = $captionID;
                $data[$i]['item_id'] = $trano;

                $qtyItem = $v['qty'];
                $totalItem = ($v['qty'] * $v['harga']);

                if (!$isOverhead)
                {
                    $isPulsa = $this->ADMIN->KodePulsa->isExist($kode_brg);
                    if (!$isPulsa)
                    {
                        $data[$i]['invalid'] = true;
                        $data[$i]['invalid_msg'] = "This item is not listed as Kode Pulsa, Please contact IT Support for detail information";
                        continue;
                    }
                }

                //Cek balance dari masing2 barang
                if (!$isOverhead)
                {
                    $tranoExclude = $v['trano'];
                    $boq3 = $this->DEFAULT->Budget->getBoq3ByOne($prj_kode,$sit_kode,$workid,$kode_brg);
                    if ($boq3)
                    {
                        $po = $this->quantity->getPoQuantity($prj_kode,$sit_kode,$workid,$kode_brg);
                        $arf = $this->quantity->getArfQuantity($prj_kode,$sit_kode,$workid,$kode_brg,$tranoExclude);
                        $asfcancel = $this->quantity->getAsfcancelQuantity($prj_kode,$sit_kode,$workid,$kode_brg);
                        $sal = $this->quantity->getSalQuantity($prj_kode,$sit_kode,$workid,$kode_brg);

                        $qtyCost = 0;
                        $IDRCost = 0;
                        $USDCost = 0;
                        if ($po != '' )
                        {
                            $qtyCost += floatval($po['qty']);
                            if ($boq3['val_kode'] == 'IDR')
                                $IDRCost += $po['totalIDR'];
                            else
                                $USDCost += $po['totalHargaUSD'];
                        }

                        if ($arf != '' )
                        {
                            $qtyCost += $arf['qty'];
                            if ($boq3['val_kode'] == 'IDR')
                                $IDRCost += $arf['totalIDR'];
                            else
                                $USDCost += $arf['totalHargaUSD'];
                        }

                        if ($asfcancel != '' )
                        {
                            $qtyCost -= $asfcancel['qty'];
                            if ($boq3['val_kode'] == 'IDR')
                                $IDRCost -= $asfcancel['totalIDR'];
                            else
                                $USDCost -= $asfcancel['totalHargaUSD'];
                        }

                        if ($sal != '' )
                        {
                            $IDRCost += $sal['totalIDR'];
                        }

                        if ($boq3['val_kode'] == 'IDR')
                        {
                            $boq3['cost'] = $IDRCost;
                            $boq3['balance'] = floatval($boq3['qty'] * $boq3['hargaIDR']) - $boq3['cost'];
                        }
                        else
                        {
                            $boq3['cost'] = $USDCost;
                            $boq3['balance'] = floatval($boq3['qty'] * $boq3['hargaUSD']) - $boq3['cost'];
                        }

                        $boq3['qty_cost'] = $qtyCost;

                        // Kurangi dengan qty & harga current Item
                        $boq3['cost'] -= $totalItem;
                        $boq3['qty_cost'] -= $qtyCost;

                        $request = $v['qty'] * $v['harga'];
                        $requestQty = $v['qty'];

        //                        if ($workid != 1100 && $workid != 2100 && $workid != 3100 && $workid != 4100 && $workid != 5100 && $workid != 6100)
        //                        {
        //                            $balanceQty = ($boq3['qty'] - $qtyCost);
        //                            if (bccomp($requestQty,$balanceQty) > 0)
        //                            {
        //                                $data[$k]['invalid'] = true;
        //                                $data[$k]['invalid_msg'] = "Your request for this Item is over budget, max allowed qty : " . number_format($balanceQty,4);
        //                                continue;
        //                            }
        //                        }

                        if (bccomp($request,$boq3['balance']) > 0)
                        {
                            $data[$i]['invalid'] = true;
                            $data[$i]['invalid_msg'] = "Your request for this Item is over budget, max allowed budget : " . $boq3['val_kode'] . " " . number_format($boq3['balance'],2);
                            continue;
                        }

                    }
                    else
                    {
                        $data[$i]['invalid'] = true;
                        $data[$i]['invalid_msg'] = "This Item is not listed on BOQ3 for $prj_kode - $sit_kode";
                    }
                    $i++;
                }
                else
                {
                    $boq3 = $this->DEFAULT->Budget->getBudgetOverhead($prj_kode,$sit_kode,$workid);
                    if ($boq3)
                    {
                        $boq3 = $boq3[0];
                        $po = $this->quantity->getPoQuantity($prj_kode,$sit_kode,$workid);
                        $arf = $this->quantity->getArfQuantity($prj_kode,$sit_kode,$workid,'',$tranoExclude);
                        $asfcancel = $this->quantity->getAsfcancelQuantity($prj_kode,$sit_kode,$workid);
                        $sal = $this->quantity->getSalQuantity($prj_kode,$sit_kode,$workid);

                        if ($boq3['val_kode'] == 'IDR')
                        {
                            $boq3['harga'] = $boq3['totalHargaIDR'];
                            $boq3['hargaIDR'] = $boq3['totalHargaIDR'];
                        }
                        else
                        {
                            $boq3['harga'] = $boq3['totalHargaUSD'];
                            $boq3['hargaUSD'] = $boq3['totalHargaUSD'];
                        }

                        $qtyCost = 0;
                        $IDRCost = 0;
                        $USDCost = 0;
                        if ($po != '' )
                        {
                            $qtyCost += floatval($po['qty']);
                            if ($boq3['val_kode'] == 'IDR')
                                $IDRCost += $po['totalIDR'];
                            else
                                $USDCost += $po['totalHargaUSD'];
                        }

                        if ($arf != '' )
                        {
                            $qtyCost += $arf['qty'];
                            if ($boq3['val_kode'] == 'IDR')
                                $IDRCost += $arf['totalIDR'];
                            else
                                $USDCost += $arf['totalHargaUSD'];
                        }

                        if ($asfcancel != '' )
                        {
                            $qtyCost -= $asfcancel['qty'];
                            if ($boq3['val_kode'] == 'IDR')
                                $IDRCost -= $asfcancel['totalIDR'];
                            else
                                $USDCost -= $asfcancel['totalHargaUSD'];
                        }

                        if ($sal != '' )
                        {
                            $IDRCost += $sal['totalIDR'];
                        }

                        if ($boq3['val_kode'] == 'IDR')
                        {
                            $boq3['cost'] = $IDRCost;
                            $boq3['balance'] = floatval($boq3['hargaIDR']) - $boq3['cost'];
                        }
                        else
                        {
                            $boq3['cost'] = $USDCost;
                            $boq3['balance'] = floatval($boq3['hargaUSD']) - $boq3['cost'];
                        }

                        $boq3['qty_cost'] = $qtyCost;

                        // Kurangi dengan qty & harga current Item
                        $boq3['cost'] -= $totalItem;
                        $boq3['qty_cost'] -= $qtyCost;

                        $request = $v['qty'] * $v['harga'];
                        $requestQty = $v['qty'];

                        //                        if ($workid != 1100 && $workid != 2100 && $workid != 3100 && $workid != 4100 && $workid != 5100 && $workid != 6100)
                        //                        {
                        //                            $balanceQty = ($boq3['qty'] - $qtyCost);
                        //                            if (bccomp($requestQty,$balanceQty) > 0)
                        //                            {
                        //                                $data[$k]['invalid'] = true;
                        //                                $data[$k]['invalid_msg'] = "Your request for this Item is over budget, max allowed qty : " . number_format($balanceQty,4);
                        //                                continue;
                        //                            }
                        //                        }

                        if (bccomp($request,$boq3['balance']) > 0)
                        {
                            $data[$i]['invalid'] = true;
                            $data[$i]['invalid_msg'] = "Your request for this Item is over budget, max allowed budget : " . $boq3['val_kode'] . " " . number_format($boq3['balance'],2);
                            continue;
                        }

                    }
                    else
                    {
                        $data[$i]['invalid'] = true;
                        $data[$i]['invalid_msg'] = "This Item is not listed on BOQ3 for $prj_kode - $sit_kode";
                    }
                    $i++;
                }
            }
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(
            Zend_Json::encode(array(
                "data" => $data
            ))
        );
    }

    public function updateArfPulsaAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = $this->_getParam("json");
        $file = $this->_getParam("file");
        $comment = $this->_getParam("comment");

        $useOverride = ($this->_getParam("useOverride") != '') ? true : false;
        if (!$json)
            return false;

        $data =Zend_Json::decode($json);
        if ($file)
            $file =Zend_Json::decode($file);

        $arrayError = array(); $arrayPrj = array();
        foreach($data as $k => $v)
        {
            $itemID = $v['trano_ref'];
            $tranoAsli = $v['trano'];
            $items = $v;
            $items["prj_kode"] = $v['prj_kode'];
            $items['next'] = $this->getRequest()->getParam('next');
            $items['uid_next'] = $this->getRequest()->getParam('uid_next');
            $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
            $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
            $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
            $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

            $prj = $v['prj_kode'];
            $site = $v['sit_kode'];
            $arrayPrj[$prj][$site] = '';
            $arrayTrano[$prj] = '';
            $params = array(
                "workflowType" => "ARFP",
                "paramArray" => '',
                "approve" => $this->const['DOCUMENT_RESUBMIT'],
                "items" => $items,
                "prjKode" => $v['prj_kode'],
                "generic" => true,
                "revisi" => false,
                "returnException" => true,
                "useOverride" => $useOverride,
                "uidApproval" => QDC_User_Session::factory()->getCurrentUID(),
                "skipClassCheck" => true,
                "itemID" => $itemID
            );
            $cek = $this->workflow->checkWorkflowTrans($params);
            if ($cek !== true)
            {
                $arrayError[] = array(
                    "Workflow Error for Project <b>" . $v['prj_kode'] . "</b> :<br>" . $cek
                );
            }
        }

        if (count($arrayError) > 0)
        {
            $result = array(
                "success" => false,
                "msgArray" => $arrayError
            );
        }
        else
        {
            $lastTrano = '';
            $urut = 1;
            $log['arfpulsa-header-before'] = array();
            $log['arfpulsa-detail-before'] = array();
            $log2['arfpulsa-header-after'] = array();
            $log2['arfpulsa-detail-after'] = array();
            foreach($data as $k => $v)
            {
                $itemID = $v['trano_ref'];
                $tranoAsli = $v['trano'];
                $captionID = $v['caption_id'];
                $items = $v;
                $items["prj_kode"] = $v['prj_kode'];
                $items['next'] = $this->getRequest()->getParam('next');
                $items['uid_next'] = $this->getRequest()->getParam('uid_next');
                $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
                $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
                $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
                $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

                $prj = $v['prj_kode'];
                $site = $v['sit_kode'];
                $params = array(
                    "workflowType" => "ARFP",
                    "paramArray" => '',
                    "approve" => $this->const['DOCUMENT_RESUBMIT'],
                    "items" => $items,
                    "prjKode" => $v['prj_kode'],
                    "generic" => true,
                    "revisi" => false,
                    "returnException" => true,
                    "skipClassCheck" => true,
                    "captionId" => $v['caption_id'],
                    "useOverride" => $useOverride,
                    "uidApproval" => QDC_User_Session::factory()->getCurrentUID(),
                    "itemID" => $itemID,
                    "comment" => $comment
                );

                if ($arrayTrano[$prj] == '')
                {
                    $this->workflow->setWorkflowTransNew($params);
                }
                $arrayTrano[$prj] = $itemID;

                $cekArf = $this->PROC->Procurementarfh->fetchRow("trano = '$tranoAsli' AND trano_ref = '$itemID'");
                if ($cekArf)
                    $cekArf = $cekArf->toArray();
                array_push($log['arfpulsa-header-before'],$cekArf);

                $cekArf = $this->PROC->Procurementarfd->fetchAll("trano = '$tranoAsli' AND trano_ref = '$itemID'");
                if ($cekArf)
                    $cekArf = $cekArf->toArray();
                array_push($log['arfpulsa-detail-before'],$cekArf);

                $this->PROC->Procurementarfd->delete("trano = '$tranoAsli' AND trano_ref = '$itemID'");

                $arrayPrj[$prj][$site]['total'] += ($v['qty'] * $v['harga']);
                $arrayPrj[$prj][$site]['manager'] = $v['manager'];
                $arrayPrj[$prj][$site]['prj_kode'] = $v['prj_kode'];
                $arrayPrj[$prj][$site]['prj_nama'] = $v['sit_kode'];
                $arrayPrj[$prj][$site]['sit_kode'] = $v['sit_kode'];
                $arrayPrj[$prj][$site]['sit_nama'] = $v['sit_nama'];
                $arrayPrj[$prj][$site]['trano_ref'] = $itemID;
                $arrayPrj[$prj][$site]['trano'] = $tranoAsli;

                $arrayInsert = array(
                    "trano" => $tranoAsli,
                    "trano_ref" => $itemID,
                    "tgl" => date("Y-m-d"),
                    "prj_kode" => $v['prj_kode'],
                    "prj_nama" => $v['prj_nama'],
                    "sit_kode" => $v['sit_kode'],
                    "sit_nama" => $v['sit_nama'],
                    "workid" => $v['workid'],
                    "workname" => $v['workname'],
                    "kode_brg" => $v['kode_brg'],
                    "nama_brg" => $v['nama_brg'],
                    "qty" => $v['qty'],
                    "harga" => $v['harga'],
                    "total" => ($v['qty'] * $v['harga']),
                    "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                    "val_kode" => $v['val_kode'],
                    "requester" => $v['requester'],
                    "urut" => $urut
                );

                $this->PROC->Procurementarfd->insert($arrayInsert);

            }

            foreach($arrayPrj as $k => $v)
            {
                foreach($v as $k2 => $v2)
                {
                    $arrayInsert = array(
                        "prj_kode" => $v2['prj_kode'],
                        "prj_nama" => $v2['prj_nama'],
                        "sit_kode" => $v2['sit_kode'],
                        "sit_nama" => $v2['sit_nama'],
                        "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                        "total" => $v2['total'],
                        "request" => $v2['manager'],
                        "user" => QDC_User_Session::factory()->getCurrentUID()
                    );
                    $prj = $v2['prj_kode'];
                    $trano = $v2['trano'];
                    $tranoRef = $v2['trano_ref'];
                    $this->PROC->Procurementarfh->update($arrayInsert,"trano = '$trano' AND trano_ref = '$tranoRef' AND prj_kode = '$prj'");
                }
            }

            $cekArf = $this->PROC->Procurementarfh->fetchRow("trano = '$tranoAsli' AND trano_ref = '$itemID'");
            if ($cekArf)
                $cekArf = $cekArf->toArray();
            array_push($log2['arfpulsa-header-after'],$cekArf);

            $cekArf = $this->PROC->Procurementarfd->fetchAll("trano = '$tranoAsli' AND trano_ref = '$itemID'");
            if ($cekArf)
                $cekArf = $cekArf->toArray();
            array_push($log2['arfpulsa-detail-after'],$cekArf);

            $logs = new Admin_Models_Logtransaction();
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                "trano" => $captionID,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date('Y-m-d H:i:s'),
                "prj_kode" => '',
                "action" => "UPDATE",
                "data_before" => $jsonLog,
                "data_after" => $jsonLog2,
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $logs->insert($arrayLog);

            if ($file)
            {
                foreach ($file as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $captionID,
                        "prj_kode" => '',
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => QDC_User_Session::factory()->getCurrentUID(),
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    if ($val['status'] == 'new')
                        $this->DEFAULT->Files->insert($arrayInsert);
                }
            }

            $result = array(
                "success" => true,
                "number" => $captionID
            );
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function getTranoAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $trano = $this->_getParam("trano");
        if ($trano)
            $where = "a.trano LIKE '%$trano%'";

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        $subselect = $this->db->select()
            ->from(array($this->ADMIN->Workflowtrans->__name()),array(
                "caption_id",
                "item_id",
                "trans_id" => "workflow_trans_id"
            ))
            ->where("item_type = 'ARFP'")
            ->order(array("date DESC"))
            ->group(array("item_id"));

        $select = $this->db->select()
            ->from(array("a" => $this->PROC->Procurementarfh->__name()),array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS a.trano"),
                "trano",
                "trano_ref",
                "prj_kode",
                "sit_kode",
                "manager" => "request"
            ))
            ->where("a.trano_ref != '' AND a.trano_ref IS NOT NULL")
            ->joinLeft(array("b" => $subselect),"a.trano_ref=b.item_id")
            ->order(array("a.trano ASC"))
            ->limit($limit,$offset);

        if ($where)
            $select = $select->where($where);

        $data = $this->db->fetchAll($select);
        if ($data)
        {
            foreach($data as $k => $v)
            {
                $data[$k]['manager_name'] = QDC_User_Ldap::factory(array("uid" => $v['manager']))->getName();
            }
            $result['data'] = $data;
            $result['count'] = $this->db->fetchOne("SELECT FOUND_ROWS()");
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function getDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $captionID = $this->_getParam("trano");

        $data = $this->ADMIN->Workflowtrans->fetchAll("caption_id = '$captionID'");
        if (!$data)
        {
            $array = array(
                "success"  =>false,
                "msg" => "ARF Pulsa not found"
            );
        }
        else
        {
            $select = $this->db->select()
                ->from(array($this->ADMIN->Workflowtrans->__name()))
                ->where("caption_id = '$captionID'")
//                ->where("final = 1")
                ->group(array("item_id"))
                ->order(array("item_id"));

            $items = $this->db->fetchAll($select);
            if ($items)
            {
                $data = array();
                foreach($items as $k => $v)
                {
                    $itemID = $v['item_id'];
                    $cek = $this->PROC->Procurementarfh->fetchAll("trano_ref = '$itemID'");
                    if ($cek)
                    {
                        $cek = $cek->toArray();
                        $arfd = array();
                        foreach($cek as $k2 => $v2)
                        {
                            $hasil = $this->db->fetchAll(
                                $this->db->select()
                                    ->from(array($this->PROC->Procurementarfd->__name()),array(
                                        "id",
                                        "trano",
                                        "trano_ref",
                                        "prj_kode",
                                        "prj_nama",
                                        "sit_kode",
                                        "sit_nama",
                                        "workid",
                                        "workname",
                                        "kode_brg",
                                        "nama_brg",
                                        "qty",
                                        "harga",
                                        "total" => "(qty*harga)",
                                        "val_kode",
                                        "ket",
                                        "('$captionID') AS caption_id"
                                    ))
                                    ->where("trano = '{$v2['trano']}'")
                            );
                            if ($hasil)
                            {
                                $arfd = QDC_Common_Array::factory()->merge(array($arfd,$hasil));
                            }
                        }
                        if ($arfd)
                        {
                            foreach($arfd as $k2 => $v2)
                            {
                                $arfTrano = $v2['trano'];
                                $prjKode = $v2['prj_kode'];
                                $sitKode = $v2['sit_kode'];
                                $workid = $v2['workid'];
                                $kodeBrg = $v2['kode_brg'];
                                $qty = 0;$qtyc = 0;$total = 0;$totalc = 0;
                                $asf = $this->quantity->getArfAsfQuantity($arfTrano,$prjKode,$sitKode,$workid,$kodeBrg);
                                if ($asf)
                                {
                                    $qty = $asf['qty'];
                                    $total = $asf['totalHargaIDR'];
                                }
                                $asfc = $this->quantity->getArfAsfcancelQuantity($arfTrano,$prjKode,$sitKode,$workid,$kodeBrg);
                                if ($asfc)
                                {
                                    $qtyc = $asfc['qty'];
                                    $totalc = $asfc['totalHargaIDR'];
                                }

                                $total = $total + $totalc;
                                $arfd[$k2]['total_in_asf'] = $total;
                                if ($total > 0)
                                {
                                    $arfd[$k2]['progress'] = $total / ($v2['qty'] * $v2['harga']);
                                }
                                else
                                    $arfd[$k2]['progress'] = 0;

                                $arfd[$k2]['invalid'] = false;
                                if ($v['final'] == 0)
                                {
                                    $arfd[$k2]['invalid'] = true;
                                    $arfd[$k2]['invalid_msg'] = "This ARF is not Final Approved yet.";
                                }

                            }
                            $data = QDC_Common_Array::factory()->merge(array($data,$arfd));
                        }
                    }
                }

                $array['posts'] = $data;
                $array['count'] = count($data);
                $array['success'] = true;
            }
        }


        $json = Zend_Json::encode($array);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
?>