<?php
class CronController extends Zend_Controller_Action
{
    private $budget;
    private $db;
    private $scurve;
    private $finance;
    private $cronBudget;
    private $utility;
    private $startBOQ3;
    private $endBOQ3;
    private $budgetCFS;
    private $ganttMod;
    private $budgt;
    private $cost;
    private $progress;
    
    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->scurve = $this->_helper->getHelper('scurve');
        $this->budget = new Default_Models_Budget();
        $this->budgetCFS = new Default_Models_BudgetCFS();
        $this->progress = new ProjectManagement_Models_ProjectProgress();
        $this->ganttMod = new Extjs4_Models_Gantt();
        $this->finance = new Finance_Models_InvoiceDetail();
        $this->budgt = $this->_helper->getHelper('Budget');
        $this->cronBudget = new Default_Models_CronBudget();
        $this->utility = Zend_Controller_Action_HelperBroker::getStaticHelper('utility');
        $this->cost = new Default_Models_Cost();
    }

    private function cekTglBOQ3($tgl='',$type='start')
    {
        if ($type == 'start')
        {
            if ($this->startBOQ3 == '')
                $this->startBOQ3 = new DateTime($tgl);
            else
            {
                $tmp = new DateTime($tgl);
                if ($tmp < $this->startBOQ3)
                {
                    $this->startBOQ3 = clone $tmp;
                }
            }
        }
        else
        {
            if ($this->endBOQ3 == '')
                $this->endBOQ3 = new DateTime($tgl);
            else
            {
                $tmp = new DateTime($tgl);
                if ($tmp > $this->endBOQ3)
                {
                    $this->endBOQ3 = clone $tmp;
                }
            }
        }
    }

    private function getTglMIP($prjKode='',$sitKode='')
    {
        $tableName = 'tgl_mip';
        $columns = array(
            array(
                "column_name" => "start_date" ,
                "type" =>  "DATETIME"
            ),
            array(
                "column_name" => "end_date" ,
                "type" =>  "DATETIME"
            ),
            array(
                "column_name" => "item_type",
                "type" => "VARCHAR(100)"
            ),
        );

        //PO
        $poh = new Default_Models_ProcurementPoh();
        $po = new Default_Models_ProcurementPod();

        $select = $this->db->select()
            ->from(array($po->__name()),array(
                "trano"
            ))
            ->group(array("trano"));

        if ($prjKode)
            $select = $select->where("prj_kode=?",$prjKode);
        if ($sitKode)
            $select = $select->where("sit_kode=?",$sitKode);

        $select = $this->db->select()
            ->from(array("a" => $select),array())
            ->joinLeft(
                array("b" => $poh->__name()),
                "a.trano = b.trano",
                array(
                "start_date" => new Zend_Db_Expr("MIN(tgl)"),
                "end_date" => new Zend_Db_Expr("MAX(tgl)"),
                "item_type" => new Zend_Db_Expr("'PO'")
            ));
        $data = $this->db->fetchAll($select);
        QDC_Adapter_TempTable::factory(array(
            "tableName" => $tableName,
            "data" => $data,
            "cols" => $columns
        ))->append();

        //ARF
        $arfh = new Default_Models_ProcurementArfh();
        $arf = new Default_Models_AdvanceRequestFormD();
        $select = $this->db->select()
            ->from(array($arf->__name()),array(
                "trano"
            ))
            ->group(array("trano"));

        if ($prjKode)
            $select = $select->where("prj_kode=?",$prjKode);
        if ($sitKode)
            $select = $select->where("sit_kode=?",$sitKode);

        $select = $this->db->select()
            ->from(array("a" => $select),array())
            ->joinLeft(
                array("b" => $arfh->__name()),
                "a.trano = b.trano",
                array(
                    "start_date" => new Zend_Db_Expr("MIN(tgl)"),
                    "end_date" => new Zend_Db_Expr("MAX(tgl)"),
                    "item_type" => new Zend_Db_Expr("'PO'")
                ));
        $data = $this->db->fetchAll($select);
        QDC_Adapter_TempTable::factory(array(
            "tableName" => $tableName,
            "data" => $data,
            "cols" => $columns
        ))->append();

        $select = $this->db->select()
            ->from(array($tableName),array(
                "start_date" => new Zend_Db_Expr("MIN(start_date)"),
                "end_date" => new Zend_Db_Expr("MAX(end_date)")
            ));

        $tgls = $this->db->fetchRow($select);
        if (!$tgls)
            return false;
        $startMIP = new DateTime($tgls['start_date']);
        $endMIP = new DateTime($tgls['end_date']);
//
//        $sql = "
//            SELECT
//                MIN(tgl) AS start_date,
//                MAX(tgl) AS end_date
//            FROM
//                procurement_pod
//            WHERE
//                prj_kode = '$prjKode'
//            AND
//                sit_kode = '$sitKode'
//        ";
//        $fetch = $this->db->query($sql);
//        $po = $fetch->fetch();
//
//        //ARF
//        $sql = "
//            SELECT
//                MIN(tgl) AS start_date,
//                MAX(tgl) AS end_date
//            FROM
//                procurement_arfd
//            WHERE
//                prj_kode = '$prjKode'
//            AND
//                sit_kode = '$sitKode'
//        ";
//        $fetch = $this->db->query($sql);
//        $arf = $fetch->fetch();
//
//        $poStart = new DateTime($po['start_date']);
//        $arfStart = new DateTime($arf['start_date']);
//        $poEnd = new DateTime($po['end_date']);
//        $arfEnd = new DateTime($arf['end_date']);
//
//        if ($poStart < $arfStart)
//            $startMIP = clone $poStart;
//        if ($arfStart < $poStart)
//            $startMIP = clone $arfStart;
//        if ($poEnd > $arfEnd)
//            $endMIP = clone $poEnd;
//        if ($arfEnd > $poEnd)
//            $endMIP = clone $arfEnd;
//
//        if ($arfStart == $poStart)
//            $startMIP = clone $poStart;
//        if ($arfEnd == $poEnd)
//            $endMIP = clone $poEnd;


        QDC_Adapter_TempTable::factory(array(
            "tableName" => 'tgl_mip'
        ))->dropTable();

        return array("start_date" => $startMIP,"end_date" => $endMIP);
    }

    private function getTglActualCost($prjKode='',$sitKode='')
    {
        $tableName = 'tgl_actual_cost';
        $columns = array(
            array(
                "column_name" => "start_date" ,
                "type" =>  "DATETIME"
            ),
            array(
                "column_name" => "end_date" ,
                "type" =>  "DATETIME"
            ),
            array(
                "column_name" => "item_type",
                "type" => "VARCHAR(100)"
            ),
        );

        //RPI
        $rpih = new Default_Models_RequestPaymentInvoiceH();
        $rpi = new Default_Models_RequestPaymentInvoice();

        $select = $this->db->select()
            ->from(array($rpi->__name()),array(
                "trano"
            ))
            ->group(array("trano"));

        if ($prjKode)
            $select = $select->where("prj_kode=?",$prjKode);
        if ($sitKode)
            $select = $select->where("sit_kode=?",$sitKode);

        $select = $this->db->select()
            ->from(array("a" => $select),array())
            ->joinLeft(
                array("b" => $rpih->__name()),
                "a.trano = b.trano",
                array(
                    "start_date" => new Zend_Db_Expr("MIN(tgl)"),
                    "end_date" => new Zend_Db_Expr("MAX(tgl)"),
                    "item_type" => new Zend_Db_Expr("'PO'")
                ));
        $data = $this->db->fetchAll($select);
        QDC_Adapter_TempTable::factory(array(
            "tableName" => $tableName,
            "data" => $data,
            "cols" => $columns
        ))->append();

        //ASF
        $asfh = new Default_Models_AdvanceSettlementFormH();
        $asf = new Default_Models_AdvanceSettlementFormD();
        $select = $this->db->select()
            ->from(array($asf->__name()),array(
                "trano"
            ))
            ->group(array("trano"));

        if ($prjKode)
            $select = $select->where("prj_kode=?",$prjKode);
        if ($sitKode)
            $select = $select->where("sit_kode=?",$sitKode);

        $select = $this->db->select()
            ->from(array("a" => $select),array())
            ->joinLeft(
                array("b" => $asfh->__name()),
                "a.trano = b.trano",
                array(
                    "start_date" => new Zend_Db_Expr("MIN(tgl)"),
                    "end_date" => new Zend_Db_Expr("MAX(tgl)"),
                    "item_type" => new Zend_Db_Expr("'PO'")
                ));
        $data = $this->db->fetchAll($select);
        QDC_Adapter_TempTable::factory(array(
            "tableName" => $tableName,
            "data" => $data,
            "cols" => $columns
        ))->append();

        $select = $this->db->select()
            ->from(array($tableName),array(
                "start_date" => new Zend_Db_Expr("MIN(start_date)"),
                "end_date" => new Zend_Db_Expr("MAX(end_date)")
            ));

        $tgls = $this->db->fetchRow($select);
        if (!$tgls)
            return false;
        $startMIP = new DateTime($tgls['start_date']);
        $endMIP = new DateTime($tgls['end_date']);

        QDC_Adapter_TempTable::factory(array(
            "tableName" => 'tgl_actual_cost'
        ))->dropTable();

        return array("start_date" => $startMIP,"end_date" => $endMIP);
    }

    //INVOICE
    private function getTglActualInvoice($prjKode='',$sitKode='')
    {
        $tableName = 'tgl_actual_invoice';
        $columns = array(
            array(
                "column_name" => "start_date" ,
                "type" =>  "DATETIME"
            ),
            array(
                "column_name" => "end_date" ,
                "type" =>  "DATETIME"
            )//,
            /*array(
                "column_name" => "item_type",
                "type" => "VARCHAR(100)"
            ),*/
        );

        $invoiceh = new Finance_Models_Invoice();
        $invoice = new Finance_Models_InvoiceDetail();

        $select = $this->db->select()
            ->from(array($invoice->__name()),array(
                "trano"
            ))
            ->group(array("trano"));

        if ($prjKode)
            $select = $select->where("prj_kode=?",$prjKode);
        if ($sitKode)
            $select = $select->where("sit_kode=?",$sitKode);

        $select = $this->db->select()
            ->from(array("a" => $select),array())
            ->joinLeft(
                array("b" => $invoiceh->__name()),
                "a.trano = b.trano",
                array(
                    "start_date" => new Zend_Db_Expr("MIN(tgl)"),
                    "end_date" => new Zend_Db_Expr("MAX(tgl)")
                ));
        $data = $this->db->fetchAll($select);
        QDC_Adapter_TempTable::factory(array(
            "tableName" => $tableName,
            "data" => $data,
            "cols" => $columns
        ))->append();

        $select = $this->db->select()
            ->from(array($tableName),array(
                "start_date" => new Zend_Db_Expr("MIN(start_date)"),
                "end_date" => new Zend_Db_Expr("MAX(end_date)")
            ));

        $tgls = $this->db->fetchRow($select);
        if (!$tgls)
            return false;
        $startInvoice = new DateTime($tgls['start_date']);
        $endInvoice = new DateTime($tgls['end_date']);

        QDC_Adapter_TempTable::factory(array(
            "tableName" => 'tgl_actual_invoice'
        ))->dropTable();

        return array("start_date" => $startInvoice,"end_date" => $endInvoice);
    }

    public function insertCronBudgetAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        //Update kode_brg yang kosong pada workid misc
        $this->cronBudget->updateKodeBrgMisc();
        
        //Update stspmeal=N pada workid misc
        $this->cronBudget->updatePeaceMealMisc();
        
        //Update rateidr BOQ3
        $this->cronBudget->setBoq3IDRrate();
        
        //Update rateidr BOQ2
        $this->cronBudget->setBoq2IDRrate();
        
        //Update rateidr KBOQ2
        $this->cronBudget->setKBoq2IDRrate();
        
        //Update Nama Supplier erpdb.procurement_rpid
        $this->cronBudget->setSupplierRPI();
        
        //Update Kode Barang Yang Masih Kosong pada tabel erpdb.procurement_brfd
        $this->cronBudget->setKodeBrgBrf();
        
        // Insert Data Table cron_budget
        $this->insertTableCronBudget();
        
        //SET val_kode='IDR' untuk BRFP
        $this->cronBudget->setIDRforBRFP();
        
        // SET harga DO sama dengan harga DOR
        $this->cronBudget->setHargDO();

    }
    
    public function insertTableCronBudget()
    {
        $WEEK = 7;
        
        $now = new DateTime('now');
        $currentDate = (array)$now;
        
        //Get Latest Exchange Rate
        $rateidr = $rateIDR = $this->budgetCFS->getLatestRate($currentDate['date'],'USD');
        
        //Bersihkan Data Table cron_budget
        $this->cronBudget->cleanAll();
        
        //Get semua project
        $projects = $this->ganttMod->getAllPojectSchedule();

        //loop setiap project
        foreach($projects AS $index => $value)
        {
            $array = array();
            
            $array['START_DATE_BOQ3'] = $value['start_date'] ;
            $array['END_DATE_BOQ3'] = $value['end_date'] ;
            $array['PRJ_KODE'] = $value['prj_kode'] ;
            $array['PRJ_STATUS'] = $value['isclosed'] ;
            $array['SIT_KODE'] = $value['sit_kode'] ;
            
            //Inisialisasi Tanggal Start & End Project dan Now
            $start_date = new DateTime($value['start_date']);
            $end_date = new DateTime($value['end_date']);
            
            $tglclose = $value['isclosed']==1 ? new DateTime($value['tglclose']) : $now;

            // Hitung Week Interval Projek
            $dueInterval = $start_date->diff($end_date);
            $dueWeekInterval = ceil($dueInterval->days/$WEEK);
            $array['DUE_INTERVAL'] = $dueWeekInterval;
            
            $weekCloseInterval = $start_date->diff($tglclose);
            $weekClose = ceil($weekCloseInterval->days/$WEEK);
            $array['CLOSED_INTERVAL'] = $weekClose;
            
            //Get Max Date Invoice
            $invoiceTgl= $this->finance->getMaxDateInvoice($value['prj_kode'], $value['sit_kode']);
            $invoiceMaxDate = new DateTime($invoiceTgl['max_date']);
            $plus5Month = new DateInterval('P5M');

            //Get Total Invoice
            $invoice = $this->finance->getTotalInvoice($value['prj_kode'], $value['sit_kode']);
 
            //Get Total BOQ2 Current
            $boq2Current = $this->budget->getBoq2Current($value['prj_kode'], $value['sit_kode'],$currentDate['date'],$rateidr);
            
            //Interval Diambil Dari Invoice, Jika Invoice < CO,end_date = now, ELSE, invoice_date + 1 Bulan
            $Interval = ($invoice['total'] <= $boq2Current['total'] ) ? $start_date->diff($now) : $start_date->diff($invoiceMaxDate->add($plus5Month));
            $weekInterval = ceil($Interval->days/$WEEK);
            
            //Tarik Data BOQ3 Current
            $current_boq3 = $this->budget->getBOQ3Current($value['prj_kode'], $value['sit_kode']);
            
            //Tarik Data Recorded Cost
            $recorded_cost = $this->cost->recordedCostPerDate($value['prj_kode'], $value['sit_kode'],$value['start_date'],$currentDate['date']);
            
            //Tarik Data Recorded Cost
            $committed_cost = $this->cost->committedCostPerDate($value['prj_kode'], $value['sit_kode'],$value['start_date'],$currentDate['date']);
            
            //Get Current Budget
            $boq3_total = $this->budgt->getCurrentBudget($current_boq3, $recorded_cost,$rateidr);
            $array['BOQ3_TOTAL'] = $boq3_total ; 
            $boq3_per_week = ($array['BOQ3_TOTAL']/$weekClose);
            $cum_boq3 = 0;
            
            //Total Invoice Per Tanggal
            $invoicePerDate = $this->finance->getTotalInvoicePerItem($value['prj_kode'], $value['sit_kode']);
            $invoiceTotal =array();
            $cum_invoice = 0;
            
            if(count($invoicePerDate)>0)
            {
                foreach($invoicePerDate as $index => $data)
                {
                    $invoiceDate = new DateTime($data['tgl']);
                    $invoiceInterval = $start_date->diff($invoiceDate);
                    $invoiceWeek = ceil($invoiceInterval->days/$WEEK);
                    $invoiceTotal[$invoiceWeek]+= $data['total'];
                }
            }

            //Total Recorded Cost Per Tanggal
            $recorded_cost_total = array();
            $cum_recorded_cost = 0;
            
            if(count($recorded_cost)>0)
            {
                foreach($recorded_cost as $index2 => $data2)
                {
                    $recorded_cost_Date = new DateTime($data2['tgl']);
                    $recorded_cost_Interval = $start_date->diff($recorded_cost_Date);
                    $recorded_cost_Week = ceil($recorded_cost_Interval->days/$WEEK);
                    $recorded_cost_total[$recorded_cost_Week] += $data2['total'];
                }
            }
            
            //Total Committed Cost Per Tanggal
            $committed_cost_total = array();
            $cum_committed_cost = 0;
            
            if(count($committed_cost)>0)
            {
                foreach($committed_cost as $index3 => $data3)
                {
                    $committed_cost_Date = new DateTime($data3['tgl']);
                    $committed_cost_Interval = $start_date->diff($committed_cost_Date);
                    $committed_cost_Week = ceil($committed_cost_Interval->days/$WEEK);
                    $committed_cost_total[$committed_cost_Week] += $data3['total'];
                }
            }
            
            //Project Progress
            $project_progress =$this->progress->getSiteProgressPerDate($value['prj_kode'], $value['sit_kode']);
            
            $prj_progress = array();

             if(count($project_progress)>0)
            {
                foreach($project_progress as $index3 => $data3)
                {
                    $progress_Date = new DateTime($data3['tgl']);
                    $progress_Interval = $start_date->diff($progress_Date);
                    $progress_Week = ceil($progress_Interval->days/$WEEK);
                    $prj_progress[$progress_Week]= $data3['progress'];
                }
            }

            for($w=0;$w<=$weekInterval;$w++)
            {
                $array['BASECOST_SCURVE'][$w]['BOQ3']= ($cum_boq3/$array['BOQ3_TOTAL'])*100;
                $array['BASECOST_SCURVE'][$w]['DUE']=$w > $dueWeekInterval?($cum_boq3/$array['BOQ3_TOTAL'])*100:0;
                $cum_boq3 += $boq3_per_week;
                if($w >=$weekClose){$cum_boq3=$array['BOQ3_TOTAL'];}
                
                $cum_invoice +=$invoiceTotal[$w];
                $array['BASECOST_SCURVE'][$w]['INVOICE'] =($cum_invoice/$array['BOQ3_TOTAL'])*100;
                
                $cum_committed_cost +=$committed_cost_total[$w];
                $array['BASECOST_SCURVE'][$w]['MIP']=($cum_committed_cost/$array['BOQ3_TOTAL'])*100;

                $cum_recorded_cost +=$recorded_cost_total[$w];
                $array['BASECOST_SCURVE'][$w]['ACTUAL']=($cum_recorded_cost/$array['BOQ3_TOTAL'])*100;
                
                $progress = ($prj_progress[$w]== null || $prj_progress[$w]=='') ? 0 : $prj_progress[$w];
                
                $array['BASECOST_SCURVE'][$w]['PROGRESS']= ($progress==0 && $array['BASECOST_SCURVE'][$w-1]['PROGRESS'] !=0) ? floatval($array['BASECOST_SCURVE'][$w-1]['PROGRESS']): floatval($progress);
                
                $array['BASECOST_SCURVE'][$w]['week']=$w;

            }

            $this->cronBudget->save($array,$value['prj_kode'],$value['sit_kode']);
        }
        
    }

    public function getbudgetperworkidAction()
    {
        $WEEK = 7;

        $this->_helper->viewRenderer->setNoRender();

        $prj_kode = $this->getRequest()->getParam("prj_kode");
        $all = $this->getRequest()->getParam("all");
        $limit = $this->getRequest()->getParam("limit");
        $offset = $this->getRequest()->getParam("offset");

        $ganttMod = new Extjs4_Models_Gantt();
        $ganttdMod = new Extjs4_Models_Ganttd();

        if ($all == '' && $prj_kode != '')
            $sql= "SELECT prj_kode,sit_kode FROM projectmanagement_gantt_task WHERE prj_kode = '$prj_kode' GROUP BY prj_kode,sit_kode";
        elseif ($all != '' && $prj_kode == '')
            $sql= "SELECT prj_kode,sit_kode FROM projectmanagement_gantt_task GROUP BY prj_kode,sit_kode";
        else
            return false;

        if ($limit != '' && $offset != '')
        {
            $sql = "SELECT * FROM ($sql) a LIMIT $offset,$limit";
        }

        $cektask = $this->db->query($sql);
        $cektask = $cektask->fetchAll();

        if (!$cektask)
            return false;

        if ($limit == '' && $offset == '')
        {
            if ($all != '')
                $this->cronBudget->purgeAll();
        }
        foreach($cektask as $k => $v)
        {
            //BOQ3
            $workBoq3 = array();
            $array = array();
            $accWeek= array();
            $sit_kode = $v['sit_kode'];
            $prj_kode = $v['prj_kode'];

            $boq3 = $this->budget->getBoq3('all-current',$prj_kode,$sit_kode);

            $boq3Total = 0;
            if (count($boq3) == 0)
                continue;

            foreach($boq3 as $k2 => $v2)
            {
                $workid = $v2['workid'];
                if ($v2['val_kode'] == 'IDR')
                    $tots = floatval($v2['hargaIDR']) * floatval($v2['qty']);
                else
                    $tots = floatval($v2['totalUSD']);
                $workBoq3[$workid]['total'] += $tots;
                $workBoq3[$workid]['name'] = $v2['workname'];
                if ($workBoq3[$workid]['start_date'] == '')
                {
                    $workBoq3[$workid]['start_date'] = $v2['tgl'];
                    $this->cekTglBOQ3($v2['tgl'],'start');
                }
                else
                {
                    $cek = new DateTime($workBoq3[$workid]['start_date']);
                    $cek2 = new DateTime($v2['tgl']);

                    if ($cek2 < $cek)
                        $workBoq3[$workid]['start_date'] = $v2['tgl'];

                    $this->cekTglBOQ3($v2['tgl'],'start');
                }
                if ($workBoq3[$workid]['end_date'] == '')
                {
                    $workBoq3[$workid]['end_date'] = $v2['tgl'];

                    $this->cekTglBOQ3($v2['tgl'],'end');
                }
                else
                {
                    $cek = new DateTime($workBoq3[$workid]['end_date']);
                    $cek2 = new DateTime($v2['tgl']);

                    if ($cek2 > $cek)
                        $workBoq3[$workid]['end_date'] = $v2['tgl'];

                    $this->cekTglBOQ3($v2['tgl'],'end');
                }
                $boq3Total += $tots;
            }

            $gantt = $ganttMod->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode'");
            $ganttd = array();
            foreach($workBoq3 as $k2 => $v2)
            {
                $ganttds = $ganttdMod->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND workid = '$k2'");
                if ($ganttds)
                {
                    $ganttds = $ganttds->toArray();
                    $ganttds['total'] = $v2['total'];

                    $start = new DateTime($ganttds['start_date']);
                    $end = new DateTime($ganttds['end_date']);

                    $workBoq3[$k2]['start_date'] = $ganttds['start_date'];
                    $workBoq3[$k2]['end_date'] = $ganttds['end_date'];

                    $oldStart = new DateTime($workBoq3[$k2]['start_date']);
                    $oldEnd = new DateTime($workBoq3[$k2]['end_date']);

                    if ($oldStart < $start)
                    {
                        $start = clone $oldStart;
                        $ganttds['start_date'] = $start->format("Y-m-d");
                    }

                    if ($oldEnd > $end)
                    {
                        $end = clone $oldEnd;
                        $ganttds['end_date'] = $end->format("Y-m-d");
                    }

//                    $days = $this->utility->dates_diff($start->format("Y-m-d"),$end->format("Y-m-d"));
                    $diff = $start->diff($end);
                    $days = intval($diff->days);
                    if ($days == 0)
                    {
                        $days = 7;
                        $a = clone $start;
                        $ganttds['end_date'] = $a->add(new DateInterval("P6D"))->format("Y-m-d");
                    }
                    $this->cekTglBOQ3($ganttds['start_date'],'start');

                    $this->cekTglBOQ3($ganttds['end_date'],'end');

                }
                else
                {
                    $start = new DateTime($v2['start_date']);
                    $end = new DateTime($v2['end_date']);
                    $days = $this->utility->dates_diff($workBoq3[$k2]['start_date'],$workBoq3[$k2]['end_date']);
                    if ($days == 0)
                    {
                        $days = 7;
                        $a = clone $start;
                        $v2['end_date'] = $a->add(new DateInterval("P6D"))->format("Y-m-d");
                    }

                    $ganttds = array(
                        "total" => $v2['total'],
                        "prj_kode" => $prj_kode,
                        "sit_kode" => $sit_kode,
                        "start_date" => $v2['start_date'],
                        "end_date" => $v2['end_date'],
                        "workid" => $k2,
                        "workname" => $v2['name'],
                        "name" => $k2 . "-" . $v2['name'],
                        "boq3_add" => true
                    );
                    $this->cekTglBOQ3($ganttds['start_date'],'start');

                    $this->cekTglBOQ3($ganttds['end_date'],'end');

                }
                $weeks = ceil($days / $WEEK);
                $weeks2 = $days / $WEEK;

                $ganttds['ori_start_date'] = $start->format("Y-m-d");
                $ganttds['week'] = $weeks;
                $ganttds['days'] = $days;
                $ganttds['avg'] = (floatval($v2['total']) / $boq3Total) * 100;
//                $ganttds['avg_week'] = $ganttds['avg'] / $weeks2;
                $ganttds['avg_week'] = $ganttds['avg'] / $weeks;
                $ganttds['avg_day'] = $ganttds['avg'] / $days;

                $workBoq3[$k2]['week'] = $ganttds['week'];
                $workBoq3[$k2]['week_no_ceil'] = $weeks2;
                $workBoq3[$k2]['days'] = $ganttds['days'];
                $workBoq3[$k2]['avg'] = $ganttds['avg'];
                $workBoq3[$k2]['avg_week'] = $ganttds['avg_week'];
                $workBoq3[$k2]['avg_day'] = $ganttds['avg_day'];

                $ganttd[] = $ganttds;
            }
//            $startGantt = new DateTime($gantt['start_date']);
            $startGantt = $this->startBOQ3;
            $startBatas = clone $startGantt;
            $startBatas2 = clone $startGantt;
//            $endGantt = new DateTime($gantt['end_date']);
            $endGantt = $this->endBOQ3;
            $endTmp = clone $endGantt;
//            $diff = $startG->diff($endG);
//            $totalDays = intval($diff->format('%a'));
//            $totalDays = $this->utility->dates_diff($gantt['start_date'],$gantt['end_date']);
//            $totalDays = $this->utility->dates_diff($this->startBOQ3->format("Y-m-d"),$this->endBOQ3->format("Y-m-d"));
            $diff = $this->startBOQ3->diff($this->endBOQ3);
            $totalDays = intval($diff->days);
            $totalWeeks = ceil($totalDays / $WEEK);

            //penentuan posisi workid dalam rentang waktu gantt
            $startPos = $this->startBOQ3->format("Y-m-d");
            for ($i=1;$i<=$totalDays;$i++)
            {
                foreach($workBoq3 as $k4 => $v4)
                {
                    $posWeek = ceil($i / $WEEK);
                    if ($v4['start_date'] == $startPos)
                    {
                        $workBoq3[$k4]['start_week'] = $posWeek;
                        $workBoq3[$k4]['end_week'] = $workBoq3[$k4]['start_week'] + $workBoq3[$k4]['week'];
                    }
                }

                $startPos = date("Y-m-d",strtotime("+1 days", strtotime($startPos)));
            }//if ($prj_kode=="CM") var_dump($workBoq3);

            //ambil nilai akumulatif per minggu untuk tiap workid
//            $accWeek = array();
//            for ($i=1;$i<=$totalWeeks;$i++)
//            {
//                foreach($workBoq3 as $k4 => $v4)
//                {
//                    if ($v4['start_week'] <= $i && $v4['end_week'] > $i)
//                    {
//                        $accWeek[$i][$k4] = ((floatval($v4['avg_week']) * floatval($boq3Total)) / 100) + floatval($accWeek[$i][$k4]);
//                    }
//                }
//            }
//
//            $array['BOQ3_ACCUMULATIVE'] = $accWeek;

            $params = array(
                "totalWeeks" => $totalWeeks,
                "WEEK" => $WEEK,
                "startBatas" => $startBatas,
                "startBatas2" => $startBatas2,
                "gantt" => $gantt,
                "ganttd" => $ganttd
            );
            $array['BOQ3_SCURVE'] = $this->scurve->generateScurveBase($params);
//            var_dump($array);die;
            $array['BOQ3_DATA'] = $ganttd;
            $tglMIP = $this->getTglMIP($prj_kode,$sit_kode);
            $startMIP = $tglMIP['start_date'];
            $endMIP = $tglMIP['end_date'];
            if ($startMIP == '' || $endMIP == '')
            {
                if ($startMIP != '')
                {
                    $endMIP = clone $startMIP;
                    $endMIP->add(new DateInterval("P6D"));
                }
                else
                {
                    $array['COMMITTED_SCURVE'] = array();
                    $array['COMMITTED_SCURVE'][0]['week'] = 0;
                    $array['COMMITTED_SCURVE'][0]['persen'] = 0;
                    $diffweekStartMIP = 0;
                    $diffweekEndMIP = 0;
                }

            }
            else
            {
                if ($startMIP->format("Y-m-d") == $endMIP->format("Y-m-d"))
                {
                    $totalDaysMIP = 7;
                    $totalWeeksMIP = 1;
                }
                else
                {
//                    $totalDaysMIP = $this->utility->dates_diff($startMIP->format("Y-m-d"),$endMIP->format("Y-m-d"));
                    $diff = $startMIP->diff($endMIP);
                    $totalDaysMIP = intval($diff->days);
                    $totalWeeksMIP = ceil($totalDaysMIP / $WEEK);
                }
                $MIPs = array();
                $MIPs2 = array();

                for ($w=1;$w<=$totalWeeksMIP;$w++)
                {
                    if ($w == 1)
                    {
                        $startBatas = clone $startMIP;
                        $startBatas->add(new DateInterval("P" . ($WEEK - 1) .  "D"));
                        $startBatasItem = clone $startMIP;
                    }
                    else
                    {
                        $startBatasItem = clone $startBatas;
                        $startBatasItem->add(new DateInterval("P1D"));
                        $startBatas->add(new DateInterval("P" . ($WEEK) .  "D"));
                    }

                    foreach($workBoq3 as $k2 => $v2)
                    {

                        //PO
                        $sql = "
                                SELECT
                                SUM(z.total) as total
                                FROM
                                (
                                    SELECT
                                        (IF (b.val_kode = 'IDR',b.qty*b.harga,b.qty*b.harga*b.rateidr)) AS total
                                    FROM
                                    (
                                        SELECT trano FROM procurement_poh
                                        WHERE prj_kode = '$prj_kode'
                                        AND (tgl BETWEEN '" . $startBatasItem->format("Y-m-d") .  "' AND '" . $startBatas->format("Y-m-d") . "')
                                    ) a
                                    LEFT JOIN procurement_pod b
                                    ON a.trano = b.trano
                                    WHERE
                                    b.sit_kode = '$sit_kode'
                                    AND b.workid = '$k2'
                                ) z
                        ";
                        $fetch = $this->db->query($sql);
                        $po = $fetch->fetch();

                        //ARF
                        $sql = "
                                SELECT
                                    SUM(z.total) as total
                                FROM
                                (
                                    SELECT
                                        (IF (b.val_kode = 'IDR',b.qty*b.harga,b.qty*b.harga*b.rateidr)) AS total
                                    FROM
                                    (
                                        SELECT trano FROM  procurement_arfh
                                        WHERE prj_kode = '$prj_kode'
                                        AND sit_kode = '$sit_kode'
                                        AND (tgl BETWEEN '" . $startBatasItem->format("Y-m-d") .  "' AND '" . $startBatas->format("Y-m-d") . "')

                                    ) a LEFT JOIN procurement_arfd b ON a.trano = b.trano
                                    WHERE
                                    b.workid = '$k2'
                                ) z
                        ";
                        $fetch = $this->db->query($sql);
                        $arf = $fetch->fetch();

                        $MIP = floatval($po['total']) + (floatval($arf['total']) - floatval($arf['total_asf']));

                        if (count($MIPs2) == 0)
                        {
                            $MIPs2[$k2] =$MIP;
                            $MIPs[$k2][] = $MIP;
                        }
                        else
                        {
                            $lastMIPs = $MIPs2[$k2];
                            $MIPs[$k2][] = $MIP + $lastMIPs;
                            $MIPs2[$k2] = $MIP + $lastMIPs;

                        }
                        $persenMIP = ($MIP / $boq3Total) * 100;
                        $tots += $persenMIP;
                    }
                }

                $persenMIP = array();
                $array['COMMITTED_SCURVE'] = array();
                $array['COMMITTED_SCURVE'][0]['week'] = 0;
                $array['COMMITTED_SCURVE'][0]['persen'] = 0;
                $jumNow = 1;
                for ($w=1;$w<=$totalWeeksMIP;$w++)
                {
                    $tots = 0;
                    foreach($MIPs as $k => $v)
                    {
                        $tots += $MIPs[$k][$w-1];
                    }
                    $totMIP = ($tots / $boq3Total ) * 100;

                    if ($totMIP == 0)
                    {
                        continue;
                    }

                    $array['COMMITTED_SCURVE'][$jumNow]['persen'] = $totMIP;
                    $array['COMMITTED_SCURVE'][$jumNow]['week'] = $w;
                    $jumNow++;
                }

//                $diff1 = $this->utility->dates_diff($this->startBOQ3->format("Y-m-d"),$startMIP->format("Y-m-d"));
                $diff = $this->startBOQ3->diff($startMIP);
                $diff1 = intval($diff->days);
                if ($this->endBOQ3 < $endMIP)
                {
//                    $diff2 = $this->utility->dates_diff($this->endBOQ3->format("Y-m-d"),$endMIP->format("Y-m-d"));
                    $diff = $this->endBOQ3->diff($endMIP);
                    $diff2 = intval($diff->days);
                }
                else
                    $diff2 = 0;

                $diffweekStartMIP = ceil($diff1 / $WEEK);
                if ($diffweekStartMIP == 1)
                    $diffweekStartMIP = 0;
                $diffweekEndMIP = ceil($diff2 / $WEEK);
            }

            $jumArray = count($array['BOQ3_SCURVE']);
            $jumArray2 = count($array['COMMITTED_SCURVE']);

            $array['DIFF_WEEK_START_BOQ3_TO_MIP'] = $diffweekStartMIP;
            $array['DIFF_WEEK_END_BOQ3_TO_MIP'] = $diffweekEndMIP;

            if ($diffweekStartMIP > 0)
                $totArray = $jumArray + $diffweekEndMIP;
            else
            {
                if ($jumArray <= $jumArray2)
                    $totArray = $jumArray2;
                else
                    $totArray = $jumArray;
            }
            if ($diffweekStartMIP > 0)
            {
                $arrayTmp = array();
                $j=1;
                for($i = 0;$i<$totArray;$i++)
                {
                    if ($i <= ($diffweekStartMIP))
                    {
                        $arrayTmp['COMMITTED_SCURVE'][$i]['persen'] = 0;
                    }
                    else
                    {
                        if ($array['COMMITTED_SCURVE'][$j]['persen'] != '')
                        {
                            $arrayTmp['COMMITTED_SCURVE'][$i]['persen'] = $array['COMMITTED_SCURVE'][$j]['persen'];
                            $j++;
                        }
                    }
                    if ($arrayTmp['COMMITTED_SCURVE'][$i]['persen'] != '')
                        $arrayTmp['COMMITTED_SCURVE'][$i]['week'] = $i;
                }
                $array['COMMITTED_SCURVE'] = $arrayTmp['COMMITTED_SCURVE'];
            }

            $scurveGabung = array();
            $arrayTmp = array();
            for($i = 0;$i<$totArray;$i++)
            {
                unset($arrayTmp['MIP']);
                if ($array['COMMITTED_SCURVE'][$i] != null)
                {
                    $arrayTmp['MIP'] = $array['COMMITTED_SCURVE'][$i]['persen'];
                }

                if ($array['BOQ3_SCURVE'][$i] != '')
                {
                    $accTmp = array();
                    $jumWork = 1;
                    $arrayTmp['BOQ3'] = $array['BOQ3_SCURVE'][$i]['persen'];
                    $works = new Default_Models_MasterWork();
                    foreach($workBoq3 as $k3 => $v3)
                    {
                        if($i == 0)
                            continue;
                        if (($i) >= $v3['start_week'] && ($i) <= $v3['end_week'])
                        {
                            $workid = $works->fetchRow("workid = '$k3'");
                            if ($workid)
                                $name = " - " .$workid['workname'];
                            $accWeek[$i][$k3] += ((floatval($v3['avg_week']) * floatval($boq3Total)) / 100) + floatval($accWeek[$i-1][$k3]);
                            $accTmp['data' . $jumWork] = $k3 .  $name;
                            $accTmp['acc' . $jumWork] = "IDR " . number_format($accWeek[$i][$k3],2);
                            $jumWork++;
                        }
                    }
                    $arrayTmp = array_merge($arrayTmp,$accTmp);
                }
                $arrayTmp['week'] = $i;
                $scurveGabung[] = $arrayTmp;

            }

            $array['START_DATE_BOQ3'] = $this->startBOQ3->format('Y-m-d');
            $array['END_DATE_BOQ3'] = $this->endBOQ3->format('Y-m-d');
            $array['BASECOST_SCURVE'] = $scurveGabung;

            //Get project progress
            $projectProgress = new ProjectManagement_Models_ProjectProgress();
            $progress = $projectProgress->fetchAll("prj_kode = '{$prj_kode}' AND sit_kode = '{$sit_kode}'",array("tgl_progress ASC"));
            foreach($progress as $k2 => $v2)
            {
//                $diff = $this->utility->dates_diff($this->startBOQ3->format("Y-m-d"),$v['tgl_progress']);
                $dateTglProgress = new DateTime($v['tgl_progress']);
                $diffTmp = $this->startBOQ3->diff($dateTglProgress);
                $diff = intval($diffTmp->days);
                $weekDiff = intval($diff / $WEEK);

                $array['BASECOST_SCURVE'][$weekDiff]['PROGRESS'] = floatval($v2['progress']);
            }

            $tmp = '';
            foreach($array['BASECOST_SCURVE'] as $k2 => $v2)
            {
                if ($v2['PROGRESS'] == '')
                {
                    if ($tmp == '')
                        $array['BASECOST_SCURVE'][$k2]['PROGRESS'] = 0;
                    else
                        $array['BASECOST_SCURVE'][$k2]['PROGRESS'] = floatval($tmp);
                }
                else
                    $tmp = $v2['PROGRESS'];
            }

            $countArray = max(array_keys($array['BASECOST_SCURVE']));
            for($i=0;$i<$countArray;$i++)
            {
                if(count($array[$i]) == 0)
                {
                    $array['BASECOST_SCURVE'][$i]['PROGRESS'] = 0;
                }
            }

            ksort($array['BASECOST_SCURVE']);

            $tmp = array();
            foreach($array['BASECOST_SCURVE'] as $k2 => $v2)
            {
                $tmp[] = $v2;
            }
            $array['BASECOST_SCURVE'] = $tmp;

            $this->cronBudget->save($array,$prj_kode,$sit_kode);

            $this->startBOQ3 = '';
            $this->endBOQ3 = '';
        }
    }

    public function setExchangeRateAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $rate = $this->_getParam("rate");
        $curr = $this->_getParam("currency");
        $source = $this->_getParam("source");

        $e = new Default_Models_ExchangeRate();
//        $e = new Default_CronExchangeRate();

        $e->insert(array(
            "val_kode" => $curr,
            "rateidr" => $rate,
            "tgl" => new Zend_Db_Expr("NOW()"),
            "source" => $source
        ));
    }

    public function errorLogAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $log = $this->_getParam("log");
        $title = ($this->_getParam("title") == "") ? "ERP Error" : $this->_getParam("title");
        $uid = $this->_getParam("uid");
        $email = ($this->_getParam("email") == 'true') ? true : false;

        if ($email)
        {
            $email = QDC_User_Ldap::factory(array("uid" => $uid))->getEmail();
            $name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();

            $err = QDC_Adapter_Mail::factory(array(
                "sender" => "qdcerp-no-reply@qdc.co.id",
                "subject" => $title,
                "recipient" => $email,
                "msgText" => "Dear " . $name . "," . "\n\n" .
                    "This is Error Log generated from ERP, please see detail below.\n\n" .
                    $log .
                    "\n\n",
                "html" => "Dear " . $name . "," . "<br><br>" .
                    "This is Error Log generated from ERP, please see detail below.<br><br>" .
                    $log .
                    "<br><br>",
                "noAuth" => true,
                "useHtml" => true,
                "useTemplate" => false
            ))->send();
        }
    }
}
?>
