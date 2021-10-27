<?php
class CronController extends Zend_Controller_Action
{
    private $budget;
    private $db;
    private $scurve;
    private $cronBudget;
    private $utility;
    private $startBOQ3;
    private $endBOQ3;
    
    public function init()
    {
        $this->budget = new Default_Models_Budget();
        $this->db = Zend_Registry::get('db');
        $this->scurve = $this->_helper->getHelper('scurve');
        $this->cronBudget = new Default_Models_CronBudget();
        $this->utility = Zend_Controller_Action_HelperBroker::getStaticHelper('utility');
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
                    $this->startBOQ3 = clone $tmp;
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
                    $this->endBOQ3 = clone $tmp;
            }
        }
    }

    private function getTglMIP($prjKode='',$sitKode='')
    {
        //PO
        $sql = "
            SELECT
                MIN(tgl) AS start_date,
                MAX(tgl) AS end_date
            FROM
                procurement_pod
            WHERE
                prj_kode = '$prjKode'
            AND
                sit_kode = '$sitKode'
        ";
        $fetch = $this->db->query($sql);
        $po = $fetch->fetch();

        //ARF
        $sql = "
            SELECT
                MIN(tgl) AS start_date,
                MAX(tgl) AS end_date
            FROM
                procurement_arfd
            WHERE
                prj_kode = '$prjKode'
            AND
                sit_kode = '$sitKode'
        ";
        $fetch = $this->db->query($sql);
        $arf = $fetch->fetch();

        $poStart = new DateTime($po['start_date']);
        $arfStart = new DateTime($arf['start_date']);
        $poEnd = new DateTime($po['end_date']);
        $arfEnd = new DateTime($arf['end_date']);

        if ($poStart < $arfStart)
            $startMIP = clone $poStart;
        if ($arfStart < $poStart)
            $startMIP = clone $arfStart;
        if ($poEnd > $arfEnd)
            $endMIP = clone $poEnd;
        if ($arfEnd > $poEnd)
            $endMIP = clone $arfEnd;

        if ($arfStart == $poStart)
            $startMIP = clone $poStart;
        if ($arfEnd == $poEnd)
            $endMIP = clone $poEnd;

        return array("start_date" => $startMIP,"end_date" => $endMIP);
    }
    public function getbudgetperworkidAction()
    {
        $WEEK = 7;

        $this->_helper->viewRenderer->setNoRender();

        $prj_kode = $this->getRequest()->getParam("prj_kode");
        $all = $this->getRequest()->getParam("all");

        if ($all == '' && $prj_kode != '')
            $sql= "SELECT prj_kode,sit_kode FROM projectmanagement_gantt_task WHERE prj_kode = '$prj_kode' GROUP BY prj_kode,sit_kode";
        elseif ($all != '' && $prj_kode == '')
            $sql= "SELECT prj_kode,sit_kode FROM projectmanagement_gantt_task GROUP BY prj_kode,sit_kode";
        else
            return false;
        $cektask = $this->db->query($sql);
        $cektask = $cektask->fetchAll();

        if (!$cektask)
            return false;

        if ($all != '')
            $this->cronBudget->purgeAll();

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

            $ganttMod = new Extjs4_Models_Gantt();
            $ganttdMod = new Extjs4_Models_Ganttd();

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

                    $days = $this->utility->dates_diff($start->format("Y-m-d"),$end->format("Y-m-d"));
//                    $diff = $start->diff($end);
//                    $days = intval($diff->format('%a'));
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
            $totalDays = $this->utility->dates_diff($this->startBOQ3->format("Y-m-d"),$this->endBOQ3->format("Y-m-d"));
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
                    $totalDaysMIP = $this->utility->dates_diff($startMIP->format("Y-m-d"),$endMIP->format("Y-m-d"));
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

                $diff1 = $this->utility->dates_diff($this->startBOQ3->format("Y-m-d"),$startMIP->format("Y-m-d"));
                if ($this->endBOQ3 < $endMIP)
                    $diff2 = $this->utility->dates_diff($this->endBOQ3->format("Y-m-d"),$endMIP->format("Y-m-d"));
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
            foreach($progress as $k => $v)
            {
                $diff = $this->utility->dates_diff($this->startBOQ3->format("Y-m-d"),$v['tgl_progress']);
                $weekDiff = $diff / $WEEK;

                $array['BASECOST_SCURVE'][$weekDiff]['PROGRESS'] = floatval($v['progress']);
            }

            $tmp = '';
            foreach($array['BASECOST_SCURVE'] as $k => $v)
            {
                if ($v['PROGRESS'] == '')
                {
                    if ($tmp == '')
                        $array['BASECOST_SCURVE'][$k]['PROGRESS'] = 0;
                    else
                        $array['BASECOST_SCURVE'][$k]['PROGRESS'] = floatval($tmp);
                }
                else
                    $tmp = $v['PROGRESS'];
            }

            $this->cronBudget->save($array,$prj_kode,$sit_kode);

            $this->startBOQ3 = '';
            $this->endBOQ3 = '';
        }
    }
}
?>