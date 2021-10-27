<?php
class Zend_Controller_Action_Helper_Scurve extends
                Zend_Controller_Action_Helper_Abstract
{

    private $WEEK;
    private $gantt;
    private $ganttd;
    private $db;
    private $utility;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->gantt = new Extjs4_Models_Gantt();
        $this->ganttd = new Extjs4_Models_Ganttd();
        $this->utility = Zend_Controller_Action_HelperBroker::getStaticHelper('utility');
        $this->WEEK = 7; //7 hari kerja...
    }

    function generateScurveBase($params = array())
    {

        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        $data = array();
        for($i=1;$i<=$totalWeeks;$i++)
        {
            $week = $i * $this->WEEK;

            if ($i > 1)
            {
                $startBatas2 = clone $startBatas;
                $startBaruPerItem = clone $startBatas;
                $startBatas->add(new DateInterval("P" . ($this->WEEK) .  "D"));
            }
            else
                $startBatas->add(new DateInterval("P" . ($this->WEEK - 1) .  "D"));

            foreach($ganttd as $k => $v)
            {

//                if ($ganttd[$k]['count'] == '')
//                {
                    $start = new DateTime($v['start_date']);
//                }
//                else
//                {
//                    if ($startBaruPerItem == $startBatas2 )
//                    {
//                        $start = $startBaruPerItem->add(new DateInterval("P1D"));
//                        echo " startBaru: " . $start->format("Y-m-d");
//                    }
//                    else
//                    {
//                        echo " start2: " . $start->format("Y-m-d") . " perItem: " . $startBaruPerItem->format("Y-m-d") . " batas2: " . $startBatas2->format("Y-m-d");
//                    }
//                }
                $end = new DateTime($v['end_date']);
                $startBefore = clone $start;
//                $selisih = intval($start->diff($startTmp)->format("%a"));
                $selisih = $this->utility->dates_diff($startBefore->format("Y-m-d"),$startBatas->format("Y-m-d"));
                
                if ($selisih > 0)
                    $selisihWeek = ceil($selisih / $this->WEEK);
                else
                    $selisihWeek = 0;

                if ($start <= $startBatas && $start <= $end)
                {
                    if ($startBatas > $end)
                    {
                        if ($start != $startBatas)
                        {
                            if ($start == $end)
                                $selisih = 1;
                            else
                                $selisih = $this->utility->dates_diff($start->format("Y-m-d"),$end->format("Y-m-d"));

                        }
                    }
                    else
                    {
                        if ($start != $startBatas)
                            $selisih = $this->utility->dates_diff($start->format("Y-m-d"),$startBatas->format("Y-m-d"));
                        elseif ($start == $startBatas)
                            $selisih = 1;
                    }
                    $ganttd[$k]['count'] += 1;
//                    if ($v['workid'] == '2100')
//                    echo "- {$v['workid']} week: " . $ganttd[$k]['count'] . " selisihWeek: $selisihWeek selisih: $selisih"  . " start: " . $start->format("d-m-Y") . " batas: " . $startBatas->format("d-m-Y") . " akhir: " . $end->format("d-m-Y") . "<br>";
                    $a = $ganttd[$k]['itungDays'];
                    $ganttd[$k]['itungDays'] += $selisih;
//                    echo " $selisih before: $a after: {$ganttd[$k]['itungDays']} <br>";

                    $data[$i] += $selisih * $v['avg_day'];

                    $startBatasTmp = clone $startBatas;
                    $ganttd[$k]['start_date'] = $startBatasTmp->add(new DateInterval('P1D'))->format("Y-m-d");
                }
//                echo $v['prj_kode'] . "-" . $v['sit_kode'] . " " . $v['workid'] . " " . $start->format("d M Y") . " " . $startBatas->format("d M Y") . " " . $end->format("d M Y") . " " . $ganttd[$k]['itungDays'] . "<br>";

//                $start->add(new DateInterval("P" . (($selisihWeek * ($this->WEEK)) . "D")));
            }
            if ($i > 1)
                $data[$i] += $data[$i - 1];
        }//var_dump($ganttd);
        $array[] = array(
            "week" => 0,
            "persen" => 0
        );
        $count = 0;
        foreach($data as $k =>$v)
        {
            $count++;
            if ($diffweek != '' && $count < $diffweek)
            {
                $array[] = array(
                    "week" => $count,
                    "persen" => 0
                );
                continue;
            }
            $array[] = array(
                "week" => $count,
                "persen" => floatval(number_format($v,2,'.',''))
            );
        }

        return $array;
    }

    function getGantt($params=array())
    {
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        if ($trano != '')
        {
            $search = "trano = '$trano'";
            $search2 = "boq_no = '$trano'";
        }
        elseif ($prj_kode != '')
        {
            $search = "prj_kode = '$prj_kode'";
            if ($sit_kode != '')
                $search .= " AND sit_kode = '$sit_kode'";
        }
        $gantt = $this->gantt->fetchRow($search2);
        $ganttd = $this->ganttd->fetchAll($search2);

        if ($gantt)
            $gantt = $gantt->toArray();
        else
            die;
        if ($ganttd)
            $ganttd = $ganttd->toArray();

        $sql = "
          SELECT
            a.totalIDR+a.totalUSD AS total
          FROM (
            SELECT
              SUM(IF(val_kode='IDR',qty*harga,0)) as totalIDR,
              SUM(IF(val_kode='USD',qty*harga*rateidr,0)) as totalUSD
            FROM transengineer_praboq3d
            WHERE
              $search
          ) a
        ";

        $fetch = $this->db->query($sql);
        $total = $fetch->fetch();
        $gTotal = floatval($total['total']);
        $accum = 0;

        foreach($ganttd as $k => $v)
        {
            $sql = "
              SELECT
                a.totalIDR+a.totalUSD AS total
              FROM (
                SELECT
                  SUM(IF(val_kode='IDR',qty*harga,0)) as totalIDR,
                  SUM(IF(val_kode='USD',qty*harga*rateidr,0)) as totalUSD
                FROM transengineer_praboq3d
                WHERE
                  $search
                AND workid = '{$v['workid']}'
              ) a
            ";

            $fetch = $this->db->query($sql);
            $total = $fetch->fetch();

            $start = new DateTime($v['start_date']);
            $end = new DateTime($v['end_date']);
//            $diff = $start->diff($end);
//            $days = intval($diff->format('%a'));
            $days = $this->utility->dates_diff($v['start_date'],$v['end_date']);
            $weeks = ceil($days / $this->WEEK);
            $weeks2 = $days / $this->WEEK;

            $ganttd[$k]['week'] = $weeks;
            $ganttd[$k]['total'] = floatval($total['total']);
            $ganttd[$k]['days'] = $days;
            $ganttd[$k]['avg'] = (floatval($total['total']) / $gTotal) * 100;
            $ganttd[$k]['avg_week'] = $ganttd[$k]['avg'] / $weeks2;
            $ganttd[$k]['avg_day'] = $ganttd[$k]['avg'] / $days;
        }

        return array(
            "gantt" => $gantt,
            "ganttd" => $ganttd
        );
    }
}
?>