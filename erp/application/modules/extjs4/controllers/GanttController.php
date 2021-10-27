<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pushm0v
 * Date: 10/6/11
 * Time: 2:33 PM
 * To change this template use File | Settings | File Templates.
 */

class Extjs4_GanttController extends Zend_Controller_Action
{

    private $db;
    private $memcache;
    private $cacheID;
    private $cacheIDEdit;
    private $cacheIDBudget;
    private $session;

    private  $gantt;
    private  $ganttd;
    private  $depend;
    private  $praboq3;
    private  $praboq3d;
    private  $tempGantt;

    private $scurve;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->memcache = Zend_Registry::get('MemcacheGantt');
		$this->session = new Zend_Session_Namespace('login');
        $prjKode = $this->getRequest()->getParam("prjKode");
        $sitKode = $this->getRequest()->getParam("sitKode");
        $trano = $this->getRequest()->getParam("trano");
        $this->cacheID = "GANTT_" . $this->session->userName . "_" . $prjKode . "_" . $sitKode;
        $this->cacheIDEdit = "GANTT_EDIT_" . $this->session->userName . "_" . md5($trano);
        $this->cacheIDBudget = "CURVE_BUDGET_" . $this->session->userName . "_" . $prjKode . "_" . $sitKode;

        $this->gantt = new Extjs4_Models_Gantt();
        $this->ganttd = new Extjs4_Models_Ganttd();
        $this->depend = new Extjs4_Models_Dependency();
		$this->praboq3d = new ProjectManagement_Models_TemporaryBOQ3();
		$this->praboq3 = new ProjectManagement_Models_TemporaryBOQ3h();
        $this->tempGantt = new Extjs4_Models_TempGantt();
        $this->scurve = $this->_helper->getHelper('scurve');
    }

    public function indexAction()
    {
        // action body
//        $json = $this->getRequest()->getParam("json");
//        $jsonDepend = $this->getRequest()->getParam("jsonDepend");
//
//        $jsons = array(
//            "task" => $json,
//            "depend" => $jsonDepend
//        );

//        if (!$this->memcache->test($this->cacheID))
//        {
//            $this->memcache->save($jsons,$this->cacheID);
//        }
//        else
//        {
//            $this->memcache->remove($this->cacheID);
//            $this->memcache->save($jsons,$this->cacheID);
//        }
    }

    public function addgantAction()
    {
        $json = $this->getRequest()->getParam("json");
        $jsonDepend = $this->getRequest()->getParam("jsonDepend");
        $prjKode = $this->getRequest()->getParam("prjKode");
        $sitKode = $this->getRequest()->getParam("sitKode");
        $this->view->json = $json;
        $this->view->jsonDepend = $jsonDepend;
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
    }

    public function updatetaskAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        //Non ZF...
//        $this->memcache->save(json_decode(file_get_contents('php://input')),'test');
        $tmp = $this->getRequest()->getRawBody();
        $arrays = Zend_Json::decode($this->getRequest()->getRawBody());

        $trano = $this->getRequest()->getParam("trano");
        if ($trano != '')
            $cache = $this->cacheIDEdit;
        else
            $cache = $this->cacheID;

        $depend = array();
//        if ($this->memcache->test($cache))
        if ($this->tempGantt->test($cache))
        {
//            $myArray = $this->memcache->load($cache);
            $myArray = $this->tempGantt->load($cache);
            $depend = $myArray['depend'];
            $myArray = Zend_Json::decode($myArray['task']);

            foreach($arrays as $k => $v)
            {
                if ($v['parentId'] != '')
                {
                    foreach($myArray as $k2 => $v2)
                    {
                        if (count($v2['children']) > 0)
                        {
                            foreach($v2['children'] as $k3 => $v3)
                            {
                                if ($v['Id'] == $v3['Id'])
                                {
                                    $v['EndDate'] = date("Y-m-d",strtotime($v['EndDate']));
                                    $v['StartDate'] = date("Y-m-d",strtotime($v['StartDate']));
                                    $myArray[$k2]['children'][$k3] = $v;
                                }
                            }
                        }
                    }
                }
                else
                {
                    foreach($myArray as $k2 => $v2)
                    {
                        if ($v['Id'] == $v2['Id'])
                        {
                            if (count($v2['children']) > 0)
                            {
                                $tmp = $v2['children'];
                                $v['EndDate'] = date("Y-m-d",strtotime($v['EndDate']));
                                $v['StartDate'] = date("Y-m-d",strtotime($v['StartDate']));
                                $myArray[$k2]= $v;
                                $myArray[$k2]['children'] = $tmp;
                            }
                            else
                            {
                                $v['EndDate'] = date("Y-m-d",strtotime($v['EndDate']));
                                $v['StartDate'] = date("Y-m-d",strtotime($v['StartDate']));
                                $myArray[$k2]= $v;
                            }
                        }
                    }
                }

            }
            $arrays = $myArray;
        }
        $json['task'] = Zend_Json::encode($arrays);
        $json['depend'] = $depend;
//        $this->memcache->save($json,$cache);
        $this->tempGantt->save($json,$cache);

        echo $tmp;
    }

    public function createdependAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        //Non ZF...
//        $this->memcache->save(json_decode(file_get_contents('php://input')),'test');
        $arrays = Zend_Json::decode($this->getRequest()->getRawBody());

        $trano = $this->getRequest()->getParam("trano");
        if ($trano == '')
        {
            $tmp = $arrays;
//            if ($this->memcache->test($this->cacheID))
            if ($this->tempGantt->test($this->cacheID))
            {
//                $myArray = $this->memcache->load($this->cacheID);
                $myArray = $this->tempGantt->load($this->cacheID);
                foreach ($arrays as $k => $v)
                {
                    unset($arrays[$k]['Id']);
                    unset($arrays[$k]['Lag']);
                }
                $myArray['depend'] = Zend_Json::encode($arrays);
                $this->tempGantt->save($myArray,$this->cacheID);
//                $this->memcache->save($myArray,$this->cacheID);
            }
        }
        else
        {
            $tmp = $arrays;
//            if ($this->memcache->test($this->cacheIDEdit))
            if ($this->tempGantt->test($this->cacheIDEdit))
            {
//                $myArray = $this->memcache->load($this->cacheIDEdit);
                $myArray = $this->tempGantt->load($this->cacheIDEdit);
                $myArray['depend'] = Zend_Json::decode($myArray['depend']);
                foreach($arrays as $k => $v)
                {
                    unset($v['Id']);
                    unset($v['Lag']);
                    $myArray['depend'][] = $v;
                }

                $myArray['depend'] = Zend_Json::encode($myArray['depend']);
                $this->tempGantt->save($myArray,$this->cacheIDEdit);
//                $this->memcache->save($myArray,$this->cacheIDEdit);
            }
        }
        echo Zend_Json::encode($tmp);
    }

    public function deletedependAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        //Non ZF...
//        $this->memcache->save(json_decode(file_get_contents('php://input')),'test');
        $arrays = Zend_Json::decode($this->getRequest()->getRawBody());

        $trano = $this->getRequest()->getParam("trano");
        
        $tmp = $arrays;
//        if ($this->memcache->test($this->cacheIDEdit))
        if ($this->tempGantt->test($this->cacheIDEdit))
        {
            $myArray = $this->tempGantt->load($this->cacheIDEdit);
//            $myArray = $this->memcache->load($this->cacheIDEdit);
            $depend = Zend_Json::decode($myArray['depend']);

            foreach ($arrays as $k => $v)
            {
                $id = $v['Id'];
                foreach ($depend as $k2 => $v2)
                {
                    if ($v2['gantt_id'] == $id)
                    {
                        unset($depend[$k2]);
                    }
                }
            }

            $depend = Zend_Json::encode($depend);
            $myArray['depend'] = $depend;
//            $this->memcache->save($myArray,$this->cacheIDEdit);
            $this->tempGantt->save($myArray,$this->cacheIDEdit);
        }

        echo "{success: true}";
    }

    public function readAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam("type");
        $trano = $this->getRequest()->getParam("trano");

        if ($trano == '')
        {
//            if ($this->memcache->test($this->cacheID))
//            {
//                $json = $this->memcache->load($this->cacheID);
            if ($this->tempGantt->test($this->cacheID))
            {
                $json = $this->tempGantt->load($this->cacheID);
                if ($type == 'task')
                {
                    echo $json['task'];
                }
                else
                {
                    echo $json['depend'];
                }
            }
        }
        else
        {
            if ($type == 'task')
            {
                $gantt = $this->gantt->fetchRow("boq_no = '$trano'");
                $depend = $this->depend->fetchAll("boq_no = '$trano'");

                $arrayGantt = array();
                $arrayGanttd = array();
                if (!$gantt)
                {
                    $boq3 = $this->praboq3d->fetchAll("trano = '$trano'");
                    if ($boq3)
                    {
                        $boq3 = $boq3->toArray();
                        $workid = array();
                        $id = 100;
                        foreach($boq3 as $k => $v)
                        {
                            $work = $v['workid'];
                            $workname = $v['workname'];
                            $workid[$work] = array(
                                "Name" => $work . " - " . $workname,
                                "StartDate" => date("Y-m-d"),
                                "EndDate" => date("Y-m-d",strtotime("+1 days",strtotime(date("Y-m-d")))),
                                "Priority" => 1,
                                "Responsible" => "",
                                "ParentId" => 1,
                                "leaf" => true,
                                "PercentDone" => 0,
                                "Id" => $id
                            );
                            $id++;
                        }

                        foreach($workid as $k => $v)
                        {
                            $arrayGanttd[] = $v;
                        }

                        $boq3h = $this->praboq3->fetchRow("trano = '$trano'");
                        $name = "Planning for {$boq3h['prj_kode']} - {$boq3h['sit_kode']}";

                        $arrayGantt[] = array(
                            "StartDate" => date("Y-m-d"),
                            "EndDate" => date("Y-m-d",strtotime("+1 days",strtotime(date("Y-m-d")))),
                            "Id" => 1,
                            "Name" => $name,
                            "Priority" => 1,
                            "Responsible" => "",
                            "expanded" => true,
                            "PercentDone" => 0,
                            "children" => $arrayGanttd
                        );

                    }
                }
                else
                {
                    $ganttd = $this->ganttd->fetchAll("boq_no = '$trano' AND parent_id = {$gantt['gantt_id']}");
                    if ($ganttd)
                    {
                        $ganttd = $ganttd->toArray();
                        foreach($ganttd as $k2 => $v2)
                        {
                            $arrayGanttd[] = array(
                                "StartDate" => $v2['start_date'],
                                "EndDate" => $v2['end_date'],
                                "Id" => $v2['gantt_id'],
                                "ParentId" => $v2['parent_id'],
                                "Name" => $v2['name'],
                                "Priority" => 1,
                                "Responsible" => "",
                                "leaf" => true,
                                "PercentDone" => $v2['percent_done']
                            );
                        }
                    }
                    $arrayGantt[] = array(
                        "StartDate" => $gantt['start_date'],
                        "EndDate" => $gantt['end_date'],
                        "Id" => $gantt['gantt_id'],
                        "Name" => $gantt['name'],
                        "Priority" => 1,
                        "Responsible" => "",
                        "expanded" => true,
                        "PercentDone" => $gantt['percent_done'],
                        "children" => $arrayGanttd
                    );
                }
                $arrayGantt = Zend_Json::encode($arrayGantt);
//                if (!$this->memcache->test($this->cacheIDEdit))
//                    $this->memcache->save(array("task" => $arrayGantt),$this->cacheIDEdit);
                if (!$this->tempGantt->test($this->cacheIDEdit))
                    $this->tempGantt->save(array("task" => $arrayGantt),$this->cacheIDEdit);
                else
                {
//                    $load = $this->memcache->load($this->cacheIDEdit);
                    $load = $this->tempGantt->load($this->cacheIDEdit);
                    $load['task'] = $arrayGantt;
//                    $this->memcache->save($load,$this->cacheIDEdit);
                    $this->tempGantt->save($load,$this->cacheIDEdit);
                }
                echo $arrayGantt;
            }
            else
            {
                $depends = $this->depend->fetchAll("boq_no = '$trano'");
                $arrayDepend = array();
                foreach ($depends as $k => $v)
                {
                    $arrayDepend[] = array(
                        "Id" => $v['id'],
                        "From" => $v['from'],
                        "To" => $v['to'],
                        "Type" => $v['type']
                    );
                }
                $arrayDepend = Zend_Json::encode($arrayDepend);

//                if (!$this->memcache->test($this->cacheIDEdit))
//                    $this->memcache->save(array("depend" => $arrayDepend),$this->cacheIDEdit);
                if (!$this->tempGantt->test($this->cacheIDEdit))
                    $this->tempGantt->save(array("depend" => $arrayDepend),$this->cacheIDEdit);
                else
                {
//                    $load = $this->memcache->load($this->cacheIDEdit);
                    $load = $this->tempGantt->load($this->cacheIDEdit);
                    $load['depend'] = $arrayDepend;
//                    $this->memcache->save($load,$this->cacheIDEdit);
                    $this->tempGantt->save($load,$this->cacheIDEdit);
                }
                echo $arrayDepend;
            }
        }
    }

    public function getAction()
    {
//        $this->_helper->viewRenderer->setNoRender();
//        $this->_helper->layout->disableLayout();
//
//        $j = $this->memcache->load($this->cacheID);
//        var_dump(Zend_Json::decode($j['task']));
//        var_dump(Zend_Json::decode($j['depend']));
    }

    public function scurvebaseAction()
    {
        $WEEK = 7;
        $trano = $this->getRequest()->getParam("trano");
        $edit = $this->getRequest()->getParam("edit");
        $submit = $this->getRequest()->getParam("submit");
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");

        $utility = $this->_helper->getHelper("utility");

        if ($edit == '' && $submit == '')
        {

            $ret = $this->scurve->getGantt(array("trano" => $trano));

            $gantt = $ret['gantt'];
            $ganttd = $ret['ganttd'];

            $this->view->startDate =date("d M Y",strtotime($gantt['start_date']));
            $startG = new DateTime($gantt['start_date']);
            $startTmp = clone $startG;
            $startTmp2 = clone $startG;
            $endG = new DateTime($gantt['end_date']);
            $endTmp = clone $endG;
            $diff = $startG->diff($endG);
//            $totalDays = intval($diff->format('%a'));
            $totalDays = $utility->dates_diff($gantt['start_date'],$gantt['end_date']);
            $totalWeeks = ceil($totalDays / $WEEK);

            $params = array(
                "totalWeeks" => $totalWeeks,
                "WEEK" => $WEEK,
                "startBatas" => $startTmp,
                "startBatas2" => $startTmp2,
                "gantt" => $gantt,
                "ganttd" => $ganttd
            );
            $this->view->fromDatabase = true;
            $this->view->trano = $trano;
        }
        elseif ($edit == 'true' || $submit == 'true')
        {
            if ($submit == '')
                $cacheIDSubmit = "GANTT_SUBMIT_" . $this->session->userName . "_" . md5($trano);
            else
                $cacheIDSubmit = "GANTT_SUBMIT_" . $this->session->userName . "_" . md5($prjKode . "_" . $sitKode);
//            if ($this->memcache->test($cacheIDSubmit))
            if ($this->tempGantt->test($cacheIDSubmit))
            {
//                $load = $this->memcache->load($cacheIDSubmit);
                $load = $this->tempGantt->load($cacheIDSubmit);
                $data = Zend_Json::decode($load['data']);
                $gTotal = 0;
                foreach($data as $k => $v)
                {
                    if ($v['val_kode'] == 'IDR')
                        $tots = floatval($v['qty']) * floatval($v['harga']);
                    else
                        $tots = floatval($v['qty']) * floatval($v['harga']) * floatval($v['rateidr']);
                    $gTotal += $tots;
                }

                foreach($load['taskd'] as $k => $v)
                {
                    $workid = $v['workid'];
                    $tots = 0;
                    foreach($data as $k2 => $v2)
                    {
                        if ($v2['workid'] == $workid)
                        {
                            if ($v2['val_kode'] == 'IDR')
                                $tots += floatval($v2['qty']) * floatval($v2['harga']);
                            else
                                $tots += floatval($v2['qty']) * floatval($v2['harga']) * floatval($v2['rateidr']);
                        }
                    }
                    $start = new DateTime($v['start_date']);
                    $end = new DateTime($v['end_date']);
                    $diff = $start->diff($end);
//                    $days = intval($diff->format('%a'));
                    $days = $utility->dates_diff($v['start_date'],$v['end_date']);
                    $weeks = ceil($days / $WEEK);
                    $weeks2 = $days / $WEEK;

                    $load['taskd'][$k]['week'] = $weeks;
                    $load['taskd'][$k]['total'] = floatval($tots);
                    $load['taskd'][$k]['days'] = $days;
                    $load['taskd'][$k]['avg'] = (floatval($tots) / $gTotal) * 100;
                    $load['taskd'][$k]['avg_week'] = $load['taskd'][$k]['avg'] / $weeks2;
                    $load['taskd'][$k]['avg_day'] = $load['taskd'][$k]['avg'] / $days;
                }
                $this->view->startDate =date("d M Y",strtotime($load['task']['start_date']));
                $startG = new DateTime($load['task']['start_date']);
                $startTmp = clone $startG;
                $startTmp2 = clone $startG;
                $endG = new DateTime($load['task']['end_date']);
                $endTmp = clone $endG;
                $diff = $startG->diff($endG);
//                $totalDays = intval($diff->format('%a'));

                $totalDays = $utility->dates_diff($load['task']['start_date'],$load['task']['end_date']);
                $totalWeeks = ceil($totalDays / $WEEK);

                $params = array(
                    "totalWeeks" => $totalWeeks,
                    "WEEK" => $WEEK,
                    "startBatas" => $startTmp,
                    "startBatas2" => $startTmp2,
                    "gantt" => $load['task'],
                    "ganttd" => $load['taskd']
                );
                $this->view->fromDatabase = false;

                $this->view->noButton = true;
            }
        }
        $array = $this->scurve->generateScurveBase($params);

        $this->view->json = Zend_Json::encode($array);
    }

    public function savebudgetforscurveAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $budget = $this->getRequest()->getParam("budget");
        $budget = Zend_Json::decode($budget);

        $this->memcache->save($budget,$this->cacheIDBudget);

        echo "{success:true}";
    }

    public function editgantAction()
    {
        $trano = $this->getRequest()->getParam("trano");
        $gantt = $this->gantt->fetchRow("boq_no = '$trano'");

        if (!$gantt)
        {
            $gantt['start_date'] = date("Y-m-d");
        }

        $this->view->startDate = $gantt['start_date'];
        $this->view->trano = $trano;
    }

    public function previewganttAction()
    {
        $fromDatabase = $this->getRequest()->getParam("fromdatabase");
        $trano = $this->getRequest()->getParam("trano");
        $arrayGantt = array();
        $arrayGanttd = array();
        if ($fromDatabase == '1')
        {
            $gantt = $this->gantt->fetchRow("boq_no = '$trano'");
            $ganttd = $this->ganttd->fetchAll("boq_no = '$trano' AND parent_id = {$gantt['gantt_id']}");
            if ($ganttd)
            {
                $ganttd = $ganttd->toArray();
                foreach($ganttd as $k2 => $v2)
                {
                    $arrayGanttd[] = array(
                        "StartDate" => $v2['start_date'],
                        "EndDate" => $v2['end_date'],
                        "Id" => $v2['gantt_id'],
                        "ParentId" => $v2['parent_id'],
                        "Name" => $v2['name'],
                        "Priority" => 1,
                        "Responsible" => "",
                        "leaf" => true,
                        "PercentDone" => $v2['percent_done']
                    );
                }
            }
            $arrayGantt[] = array(
                "StartDate" => $gantt['start_date'],
                "EndDate" => $gantt['end_date'],
                "Id" => $gantt['gantt_id'],
                "Name" => $gantt['name'],
                "Priority" => 1,
                "Responsible" => "",
                "expanded" => true,
                "PercentDone" => $gantt['percent_done'],
                "children" => $arrayGanttd
            );
            $depends = $this->depend->fetchAll("boq_no = '$trano'");
            $arrayDepend = array();
            foreach ($depends as $k => $v)
            {
                $arrayDepend[] = array(
                    "Id" => $v['id'],
                    "From" => $v['from'],
                    "To" => $v['to'],
                    "Type" => $v['type']
                );
            }

            $this->view->startDate = $gantt['start_date'];
        }
        else
        {
            $cacheIDSubmit = "GANTT_SUBMIT_" . $this->session->userName . "_" . md5($trano);
        }

        $arrayGantt = Zend_Json::encode($arrayGantt);
        $arrayDepend = Zend_Json::encode($arrayDepend);

        $this->view->jsonTask = $arrayGantt;
        $this->view->jsonDepend = $arrayDepend;
    }

    public function scurvecostAction()
    {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $plainLayout = ($this->getRequest()->getParam("plain") == 'true') ? true: false;
        $notitle = $this->getRequest()->getParam("notitle");
        $height = $this->getRequest()->getParam("height");
        $width = $this->getRequest()->getParam("width");
        $notitle = (isset($notitle)) ? true : false;
        $height = (isset($height)) ? intval($height) : 600;
        $width = (isset($width)) ? intval($width) : 900;

        $cronBudget = new Default_Models_CronBudget();

        $this->view->notitle = $notitle;
        $this->view->height = $height;
        $this->view->width = $width;

        $cek = $cronBudget->test($prjKode,$sitKode);
        if ($cek)
        {
            $select = $this->db->select()
                ->from(array($cronBudget->__name()));
            if ($prjKode)
                $select = $select->where("prj_kode=?",$prjKode);
            if ($sitKode)
            {
                $select = $select->where("sit_kode=?",$sitKode);
            }
            else
            {
                $select = $select->where("sit_kode = '' OR sit_kode IS NULL");
            }
            $crons = $this->db->fetchRow($select);
            $select = $this->db->select()
                ->from(array($this->ganttd->__name()));
            if ($prjKode)
                $select = $select->where("prj_kode=?",$prjKode);
            if ($sitKode)
                $select = $select->where("sit_kode=?",$sitKode);

            $works = $this->db->fetchAll($select);
            if ($works)
            {
                $count = count($works);
                $field = array();
                $field2 = array();
                for($i=1;$i<=$count;$i++)
                {
                    $field[] = "'data$i'";
                    $field2[] = "'acc$i'";
                }

                if (count($field) > 0)
                {
                    $this->view->fields = "," . implode(",",$field) . "," . implode(",",$field2);
                    $this->view->count = count($field);
                }


            }
            $data = $cronBudget->load($prjKode,$sitKode);
            $this->view->startDate = $data['START_DATE_BOQ3'];
            $this->view->endDate = $data['END_DATE_BOQ3'];
            $this->view->currentDate = $crons['tgl'];
            $this->view->json = Zend_Json::encode($data['BASECOST_SCURVE']);
        }
    }

//    public function getscurveprojectlistAction()
//    {
//        $this->_helper->viewRenderer->setNoRender();
//        $this->_helper->layout->disableLayout();
//
//        $cronBudget = new Default_Models_CronBudget();
//
//        $useRole = $this->getRequest()->getParam("userole");
//        $prjKodeSearch = $this->getRequest()->getParam("prj_kode");
//
//        if (!$useRole)
//            $useRole = true;
//        else
//            $useRole = false;
//
//        $ret['posts'] = array();
//        if ($useRole)
//        {
//            $userID = $this->session->idUser;
//            $userrole = new Admin_Models_Userrole();
//            $myproject = $userrole->getCurrentProject($userID,true);
//
//            foreach($myproject as $k => $v)
//            {
//                if (!$prjKodeSearch)
//                    $prjKode = $v['prj_kode'];
//                else
//                    $prjKode = $prjKodeSearch;
//                $cek = $cronBudget->fetchRow("prj_kode = '$prjKode'");
//                if ($cek)
//                {
//                    $ret['posts'][] = array(
//                        "prj_kode" => $prjKode,
//                        "prj_nama" => $v['prj_nama']
//                    );
//                }
//            }
//        }
//        else
//        {
//            $project = new Default_Models_MasterProject();
//
//            if ($prjKodeSearch)
//                $where = $prjKodeSearch;
//
//            $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
//            $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
//
//            $cek = $cronBudget->getProjectList($where,$limit,$offset);
//            if ($cek)
//            {
//                $ret['posts'] = $cek;
//            }
//        }
//
//        $json = Zend_Json::encode($ret);
//
//        echo $json;
//    }
    
    public function getscurveprojectlistAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        $project = new Default_Models_MasterProject();
        $uid = QDC_User_Session::factory()->getCurrentID();
        $cek = $project->getMyProject($uid);
        if ($cek)
        {
                $ret['posts'] = $cek;
        }
        $json = Zend_Json::encode($ret);

        echo $json;
        
    }

    public function getscurvesitelistAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $prjKode = $this->getRequest()->getParam("prj_kode");

        $query = $this->getRequest()->getParam('query');
        $useRole = $this->getRequest()->getParam("userole");
        $siteKodeSearch = $this->getRequest()->getParam("sit_kode");
        $siteNamaSearch = $this->getRequest()->getParam("sit_nama");

        if (!$useRole)
            $useRole = true;
        else
            $useRole = false;

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $userID = $this->session->idUser;
        $userrole = new Admin_Models_Userrole();

        $cronBudget = new Default_Models_CronBudget();
        $sites = new Default_Models_MasterSite();

        $ret = array();

        $select = $this->db->select()
            ->from(array($cronBudget->__name()))
            ->where("prj_kode = ?",$prjKode)
            ->where("sit_kode IS NOT NULL");


        if ($query != '' || $siteNamaSearch)
        {
            if ($siteNamaSearch)
                $query = $siteNamaSearch;
//            $search = " AND sit_nama like '%$query%' ";
        }

        if ($siteKodeSearch)
        {
            $select = $select->where("sit_kode LIKE '%$siteKodeSearch%'");
        }

        $cek = $this->db->fetchAll($select);

        foreach($cek as $k => $v)
        {
            $jsonDecode = Zend_Json::decode($v['data']);
            $diffStart = $jsonDecode['DIFF_WEEK_START_BOQ3_TO_MIP'];
            $diffEnd = $jsonDecode['DIFF_WEEK_END_BOQ3_TO_MIP'];
            $sitKode = $v['sit_kode'];
            $site = $sites->fetchRow("prj_kode = '$prjKode' AND sit_kode = '$sitKode'");
            $suffix = false;
            if ($diffStart > 0 && $diffEnd > 0)
                $sitNama = "<font color='#ff0000'>(LATE) " . $site['sit_nama'] . "</font>";
            else
                $sitNama = $site['sit_nama'];
            $ret['posts'][] = array(
                "sit_kode" => $sitKode,
                "sit_nama" => $sitNama,
                "sit_nama_asli" => $site['sit_nama']
            );
        }

//        $cek = $cronBudget->fetchAll("prj_kode = '$prjKode'",array($sort . " " . $dir),$limit,$offset);
//        if ($cek)
//        {
//            if ($search != '')
//            {
//                $siteList = $sites->fetchAll("prj_kode = '$prjKode' $search");
//                $site = array();
//                foreach($siteList as $k => $v)
//                {
//                    $site[] = $v['sit_kode'];
//                }
//            }
//            foreach($cek as $k => $v)
//            {
//                if ($search != '')
//                {
//                    if (!in_array($v['sit_kode'],$site))
//                        continue;
//                }
//                $jsonDecode = Zend_Json::decode($v['data']);
//                $diffStart = $jsonDecode['DIFF_WEEK_START_BOQ3_TO_MIP'];
//                $diffEnd = $jsonDecode['DIFF_WEEK_END_BOQ3_TO_MIP'];
//                $sitKode = $v['sit_kode'];
//                $site = $sites->fetchRow("prj_kode = '$prjKode' AND sit_kode = '$sitKode'");
//                $suffix = false;
//                if ($diffStart > 0 && $diffEnd > 0)
//                    $sitNama = "<font color='#ff0000'>(LATE) " . $site['sit_nama'] . "</font>";
//                else
//                    $sitNama = $site['sit_nama'];
//                $ret['posts'][] = array(
//                    "sit_kode" => $sitKode,
//                    "sit_nama" => $sitNama,
//                    "sit_nama_asli" => $site['sit_nama']
//                );
//            }
//
//        }

        $json = Zend_Json::encode($ret);

        echo $json;
    }
}
?>