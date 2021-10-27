<?php

class Admin_Models_Workflow extends Zend_Db_Table_Abstract {

    protected $_name = 'workflow';
    protected $db;
    protected $const;
    protected $user;
    protected $memcacheWork;
    protected $brfStuff;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
        $this->user = new Admin_Model_User();
        $this->memcacheWork = Zend_Registry::get('MemcacheWorkflow');
        $this->brfStuff = array(
            "BRF", "BRFP", "BSF"
        );
    }

    public function isBrfStuff($brfstuff = '') {
        return in_array($brfstuff, $this->brfStuff);
    }

    public function addWorkflow($dataArray = '', $id_workflow = '') {
        //TODO
        //Find another way to update workflow
        $sql = "DELETE FROM workflow 
				WHERE
					workflow_item_id=$id_workflow";
        $this->db->query($sql);

        for ($i = 0; $i < count($dataArray); $i++) {
            if ($i == 0) {
                $next = $dataArray[$i + 1];
                $sql = "INSERT INTO workflow (workflow_item_id,is_start,is_end,next,master_role_id)
						VALUES
							($id_workflow,1,0,$next,$dataArray[$i])";
                $this->db->query($sql);
            } else {
                $end = 0;
                if ($i == (count($dataArray) - 1)) {
                    $prev = $dataArray[$i - 1];
                    $next = 0;
                    $end = 1;
                } else {
                    $prev = $dataArray[$i - 1];
                    $next = $dataArray[$i + 1];
                }
                $sql = "INSERT INTO workflow (workflow_item_id,is_end,next,prev,master_role_id)
						VALUES
							($id_workflow,$end,$next,$prev,$dataArray[$i])";

                $this->db->query($sql);
            }
        }
    }

    public function getWorkflowByItemId($workflow_item_id = '') {
        $sql = "SELECT w.*,ml.uid FROM 
					workflow w
					LEFT JOIN
						master_role mr
					ON mr.id = w.master_role_id
					LEFT JOIN 
						master_login ml
					ON ml.id = mr.id_user
				WHERE 
					w.workflow_item_id=$workflow_item_id
				ORDER BY w.workflow_id ASC";
        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function getWorkflowByUserID($userID = '', $workflowType = '', $paramArray = '', $all = false, $addQuery = '') {
        $user = $this->user->fetchRow("id=" . $userID)->toArray();
        $uid = $user['uid'];
        $criteria = '';

        $hashQuery = md5($addQuery);
        $cacheID = "WORKFLOW_$workflowItemType_$all_$hashQuery_$uid";

//        if (!$this->memcacheWork->test($cacheID))
//        {

        if ($workflowType != '') {
            $workflowType = " AND wit.name = '$workflowType'";
        }
        //		if ($paramArray != '')
        //		{
        //			$temp = array();
        //			foreach($paramArray as $key => $val)
        //			{
        //				if ($val != '' && isset($val))
        //				{
        //
    //					$val = "'$val'";
        //					$temp[] = "wi.$key = $val";
        //				}
        //			}
        //			$criteria .= ' AND ' . implode(' AND ',$temp);
        //		}
        //		$sql = "SELECT w.*,wi.prj_kode,wi.sit_kode FROM
        //					workflow w
        //					LEFT JOIN
        //						master_role mr
        //					ON mr.id = w.master_role_id
        //					LEFT JOIN
        //						workflow_item wi
        //					ON wi.workflow_item_id = w.workflow_item_id
        //					LEFT JOIN
        //						workflow_item_type wit
        //					ON wit.workflow_item_type_id = wi.workflow_item_type_id
        //				WHERE
        //					mr.id_user = $userID
        //					$criteria
        //				$addQuery";
        if ($workflowType == '') {
            $sql = "SELECT distinct
                            workflow_item_id,
                            is_start,
                            prev,
                            next,
                            is_end,
                            master_role_id,
                            workflow_structure_id,
                            is_final,
                            is_executor,
                            uid,
                            uid_prev,
                            uid_next,
                            prj_kode
                        FROM
                        workflow w
                        WHERE
                        w.uid = '$uid'
                        $addQuery";
        } else {
            $sql = "SELECT
                            w.*
                        FROM
                        workflow w
                        LEFT JOIN
                            workflow_item wi
                        ON wi.workflow_item_id = w.workflow_item_id
                        LEFT JOIN
                            workflow_item_type wit
                        ON wit.workflow_item_type_id = wi.workflow_item_type_id
                    WHERE
                        w.uid = '$uid'
                        $workflowType
                    $addQuery";
        }
        $fetch = $this->db->query($sql);
        if (!$all)
            $result = $fetch->fetch();
        else
            $result = $fetch->fetchAll();

//            $this->memcacheWork->save($result,$cacheID,array('WORKFLOW'));
//        }
//        else
//        {
//            $result = $this->memcacheWork->load($cacheID);
//        }
        return $result;
    }

    public function getSubmittedDoc($workflowID = '') {
        $sql = "SELECT w.* FROM 
					workflow w
				WHERE 
					w.workflow_id=$workflowID";
        $fetch = $this->db->query($sql);
        $result = $fetch->fetch();

        $prev = 0;
        $next = 0;
        $workflow_item_id = '';
        $master_role_id = '';

        $prev = $result['prev'];
        $next = $result['next'];
        $workflow_item_id = $result['workflow_item_id'];
        $master_role_id = $result['master_role_id'];

        $sql = "SELECT wt.* FROM 
					workflow w
				LEFT JOIN 
					workflow_trans wt
				ON w.workflow_id = wt.workflow_id
				WHERE 
					w.workflow_item_id=$workflow_item_id
				AND
					w.master_role_id = $prev
				AND w.next = $master_role_id
				AND wt.approve = " . $this->const['DOCUMENT_SUBMIT'];
        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function getDocument($params = array()) {

        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }

        if ($itemType != '')
            $query = " AND item_type LIKE '$itemType%'";
        if ($trano != '')
            $query .= " AND item_id LIKE '%$trano%'";
        if ($prj_kode != '')
            $query .= " AND prj_kode = '$prj_kode'";



        if ($count) {
            //count document to process
            //stored procedure parameters :
            // uid
            // other condition
            // generic option
            // flag count
            // limits, offset
            //non generic

            $query_sql = $this->db->prepare("call document_process(\"'$uid'\",\"$query\",\"non_generic\",1,\"\")");
            $fetch = $query_sql->execute();

            if ($fetch) {
                $hasil = $query_sql->fetch();
                $hasil = $hasil['jumlah'];
                $query_sql->closeCursor();
            } else
                $hasil = 0;

            //generic
            $query_sql = $this->db->prepare("call document_process(\"'$uid'\",\"$query\",\"generic\",1,\"\")");
            $fetch = $query_sql->execute();

            if ($fetch) {
                $hasilGeneric = $query_sql->fetchAll();
                $query_sql->closeCursor();
                $jumTimesheet = $this->filterTimesheet($hasilGeneric);
                $jumPulsa = $this->filterPulsa($hasilGeneric);
                $hasilGeneric = floatval($this->db->fetchOne('SELECT FOUND_ROWS()'));
                $hasilGeneric = $hasilGeneric - ($jumPulsa + $jumTimesheet);
                $hasil = floatval($hasilGeneric) + floatval($hasil) + floatval($jumPulsa) + floatval($jumTimesheet);
            } else
                $hasil = 0;
        } else {
            if ($offset !== '' && $limit !== '')
                $limits = " LIMIT $offset,$limit";

            $query_sql = $this->db->prepare("call document_process(\"'$uid'\",\"$query\",\"non_generic\",0,\"$limits\")");
            $fetch = $query_sql->execute();
            if ($fetch) {
                $hasil['posts'] = $query_sql->fetchAll();

                $query_sql->closeCursor();
                $hasil['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
            } else {
                $hasil['count'] = 0;
                $hasil['posts'] = '';
            }

            $query_sql = $this->db->prepare("call document_process(\"'$uid'\",\"$query\",\"generic\",0,\"$limits\")");
            $fetch = $query_sql->execute();


            $hasilGeneric = $query_sql->fetchAll();
            $filter = $this->filterTimesheet($hasilGeneric, false);
            foreach ($filter['result'] as $key => $val) {
                $hasil['posts'][] = $val;
            }
            $filter = $this->filterPulsa($filter['other'], false);
            foreach ($filter['result'] as $key => $val) {
                $hasil['posts'][] = $val;
            }

            foreach ($filter['reject'] as $key => $val) {
                $hasil['posts'][] = $val;
            }

            $filter = $this->filterPulsaSettle($filter['other'], false);
            foreach ($filter['result'] as $key => $val) {
                $hasil['posts'][] = $val;
            }

            foreach ($filter['reject'] as $key => $val) {
                $hasil['posts'][] = $val;
            }

            //Transaksi sisa selain dari Filter2 diatas...
            foreach ($filter['other'] as $key => $val) {
                $hasil['posts'][] = $val;
            }
            $query_sql->closeCursor();
            $hasilGeneric = floatval($this->db->fetchOne('SELECT FOUND_ROWS()'));
            $hasil['count'] = $hasil['count'] + $hasilGeneric;
        }

        return $hasil;
    }

    public function getWorkflowProjectByUserID($userID = '') {
        $sql = "
            SELECT prj_kode FROM workflow WHERE uid = '$userID' GROUP BY prj_kode
        ";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();

        return $fetch;
    }

    public function filterTimesheet($data = '', $count = true) {
        if ($data == '')
            return 0;

        $hasil = array();
        $hasilOther = array();
        $others = 0;

        if ($count) {
            $reject = 0;
            $others = 0;
            foreach ($data as $k => $v) {
                if ($v['item_type'] == 'TSHEET' && $v['approve'] == 300) {
                    $reject++;
                }
                if ($v['item_type'] == 'TSHEET' && $v['approve'] != 300) {
                    $trano = $v['item_id'];
                    $hasil[$trano] = $v;
                }
                if ($v['item_type'] != 'TSHEET' && !$this->isBrfStuff($v['item_type'])) {
                    $others++;
                }
            }

            $temp = array();
            $timesheet = new ProjectManagement_Models_Timesheet();
            foreach ($hasil as $k => $v) {
                $fetch = $timesheet->fetchRow("trano = '$k'");
                if ($fetch) {
                    $uid = $fetch['uid'];
                    $hasil[$k]['date'] = date("Y-m-d H:i:s");
                    $hasil[$k]['uid'] = $fetch['uid'];
                    $hasil[$k]['item_id'] = "TIMESHEET";
                    $hasil[$k]['comment'] = "My Timesheet";
                    $temp[$uid] = $hasil[$k];
                }
            }
            return count($temp) + $reject + $others;
        } else {
            $hasilRet = array();
            foreach ($data as $k => $v) {
                if ($v['item_type'] == 'TSHEET' && $v['approve'] == 300) {
                    $hasilRet[] = $v;
                }
                if ($v['item_type'] == 'TSHEET' && $v['approve'] != 300) {
                    $trano = $v['item_id'];
                    $hasil[$trano] = $v;
                }
                if ($v['item_type'] != 'TSHEET') {
                    $hasilOther[] = $v;
                }
            }
            $temp = array();
            $timesheet = new ProjectManagement_Models_Timesheet();
            foreach ($hasil as $k => $v) {
                $fetch = $timesheet->fetchRow("trano = '$k'");
                if ($fetch) {
                    $uid = $fetch['uid'];
                    $hasil[$k]['date'] = date("Y-m-d H:i:s");
                    $hasil[$k]['uid'] = $fetch['uid'];
                    $hasil[$k]['item_id'] = "TIMESHEET";
                    $hasil[$k]['comment'] = "My Timesheet";
                    $temp[$uid] = $hasil[$k];
                }
            }

            foreach ($temp as $k => $v) {
                $hasilRet[] = $v;
            }

            return array(
                "result" => $hasilRet,
                "other" => $hasilOther
            );
        }
    }

    public function filterPulsa($data = '', $count = true) {
        if ($data == '')
            return 0;

        $hasil = array();
        $hasilReject = array();
        $others = 0;

        if ($count) {
            $reject = 0;
            $others = 0;
            foreach ($data as $k => $v) {
                if ($v['item_type'] == 'ARFP' && $v['approve'] == 300) {
                    $reject++;
                }
                if ($v['item_type'] == 'ARFP' && $v['approve'] != 300) {
                    $item = $v['item_id'];
                    $hasil[$item] = $v;
                }
                if ($v['item_type'] != 'ARFP' && !$this->isBrfStuff($v['item_type'])) {
                    $others++;
                }
            }

            $temp = array();
            $arf = new Procurement_Models_Procurementarfh();
            foreach ($hasil as $k => $v) {
                $fetch = $arf->fetchRow("trano_ref = '$k'");
                if ($fetch) {
                    $ref = $v['caption_id'];
                    $hasil[$k]['date'] = date("Y-m-d H:i:s");
                    $hasil[$k]['uid'] = $fetch['user'];
                    $hasil[$k]['item_id'] = $v['caption_id'];
                    $hasil[$k]['comment'] = "";
                    $hasil[$k]['caption_id'] = $v['caption_id'];
                    $temp[$ref] = $hasil[$k];
                }
            }
            return count($temp) + $reject + $others;
        } else {
            $hasilRet = array();
            $hasilOther = array();
            $paramsRet = array();
            $paramsReject = array();
            foreach ($data as $k => $v) {
                if ($v['item_type'] == 'ARFP' && $v['approve'] == 300) {
                    $trano = $v['item_id'];
                    $hasilReject[$trano] = $v;
                }
                if ($v['item_type'] == 'ARFP' && $v['approve'] != 300) {
                    $trano = $v['item_id'];
                    $hasil[$trano] = $v;
                }
                if ($v['item_type'] != 'ARFP') {
                    $hasilOther[] = $v;
                }
            }
            $temp = array();
            $tempReject = array();
            $arf = new Procurement_Models_Procurementarfh();
            foreach ($hasil as $k => $v) {
                $fetch = $arf->fetchRow("trano_ref = '$k'");
                if ($fetch) {
                    $ref = md5($v['caption_id']);
                    $paramsRet[$ref][] = $v['workflow_trans_id'];
                    $hasil[$k]['date'] = date("Y-m-d H:i:s");
                    $hasil[$k]['uid'] = $fetch['user'];
//                    $hasil[$k]['comment'] = "";
                    $hasil[$k]['caption_id'] = $v['caption_id'];
                    $temp[$ref] = $hasil[$k];
                }
            }

            foreach ($hasilReject as $k => $v) {
                $fetch = $arf->fetchRow("trano_ref = '$k'");
                if ($fetch) {
                    $ref = md5($v['caption_id']);
                    $paramsReject[$ref][] = $v['workflow_trans_id'];
                    $hasilReject[$k]['date'] = date("Y-m-d H:i:s");
//                    $hasilReject[$k]['uid'] = $fetch['user'];
                    $hasilReject[$k]['comment'] = "";
                    $hasilReject[$k]['caption_id'] = $v['caption_id'];
                    $tempReject[$ref] = $hasilReject[$k];
                }
            }

            foreach ($temp as $k => $v) {
                $ref = $k;
                $v['params'] = $paramsRet[$ref];
                $hasilRet[] = $v;
            }

            $hasilReject = array();
            foreach ($tempReject as $k => $v) {
                $ref = $k;
                $v['params'] = $paramsReject[$ref];
                $hasilReject[] = $v;
            }

            return array(
                "result" => $hasilRet,
                "reject" => $hasilReject,
                "other" => $hasilOther
            );
        }
    }

    public function filterPulsaSettle($data = '', $count = true) {
        if ($data == '')
            return 0;

        $hasil = array();
        $hasilReject = array();
        $others = 0;

        if ($count) {
            $reject = 0;
            $others = 0;
            foreach ($data as $k => $v) {
                if ($v['item_type'] == 'ASFP' && $v['approve'] == 300) {
                    $reject++;
                }
                if ($v['item_type'] == 'ASFP' && $v['approve'] != 300) {
                    $item = $v['item_id'];
                    $hasil[$item] = $v;
                }
                if ($v['item_type'] != 'ASFP') {
                    $others++;
                }
            }

            $temp = array();
            $asf = new Default_Models_AdvanceSettlementFormH();
            foreach ($hasil as $k => $v) {
                $fetch = $asf->fetchRow("trano_ref = '$k'");
                if ($fetch) {
                    $ref = $v['caption_id'];
                    $hasil[$k]['date'] = date("Y-m-d H:i:s");
                    $hasil[$k]['uid'] = $fetch['user'];
                    $hasil[$k]['item_id'] = $v['caption_id'];
                    $hasil[$k]['comment'] = "";
                    $hasil[$k]['caption_id'] = $v['caption_id'];
                    $temp[$ref] = $hasil[$k];
                }
            }
            return count($temp) + $reject + $others;
        } else {
            $hasilRet = array();
            $hasilOther = array();
            $paramsRet = array();
            $paramsReject = array();
            foreach ($data as $k => $v) {
                if ($v['item_type'] == 'ASFP' && $v['approve'] == 300) {
                    $trano = $v['item_id'];
                    $hasilReject[$trano] = $v;
                }
                if ($v['item_type'] == 'ASFP' && $v['approve'] != 300) {
                    $trano = $v['item_id'];
                    $hasil[$trano] = $v;
                }
                if ($v['item_type'] != 'ASFP') {
                    $hasilOther[] = $v;
                }
            }
            $temp = array();
            $tempReject = array();
            $asf = new Default_Models_AdvanceSettlementFormH();
            foreach ($hasil as $k => $v) {
                $fetch = $asf->fetchRow("trano_ref = '$k'");
                if ($fetch) {
                    $ref = md5($v['caption_id']);
                    $paramsRet[$ref][] = $v['workflow_trans_id'];
                    $hasil[$k]['date'] = date("Y-m-d H:i:s");
                    $hasil[$k]['uid'] = $fetch['user'];
//                    $hasil[$k]['comment'] = "";
                    $hasil[$k]['caption_id'] = $v['caption_id'];
                    $temp[$ref] = $hasil[$k];
                }
            }

            foreach ($hasilReject as $k => $v) {
                $fetch = $asf->fetchRow("trano_ref = '$k'");
                if ($fetch) {
                    $ref = md5($v['caption_id']);
                    $paramsReject[$ref][] = $v['workflow_trans_id'];
                    $hasilReject[$k]['date'] = date("Y-m-d H:i:s");
//                    $hasilReject[$k]['uid'] = $fetch['user'];
                    $hasilReject[$k]['comment'] = "";
                    $hasilReject[$k]['caption_id'] = $v['caption_id'];
                    $tempReject[$ref] = $hasilReject[$k];
                }
            }

            foreach ($temp as $k => $v) {
                $ref = $k;
                $v['params'] = $paramsRet[$ref];
                $hasilRet[] = $v;
            }

            $hasilReject = array();
            foreach ($tempReject as $k => $v) {
                $ref = $k;
                $v['params'] = $paramsReject[$ref];
                $hasilReject[] = $v;
            }

            return array(
                "result" => $hasilRet,
                "reject" => $hasilReject,
                "other" => $hasilOther
            );
        }
    }

    public function isPersonEnd($uid = '', $workflowItemId = '', $prjKode = '', $uidPrev = '') {
        $cek = $this->getPersonWorkflow($uid, $workflowItemId, $prjKode, $uidPrev);
        if ($cek) {
            if ($cek['is_end'] == 1)
                return true;
        }

        return false;
    }

    public function getPersonWorkflow($uid = '', $workflowItemId = '', $prjKode = '', $uidPrev = '') {
        $select = $this->db->select()
                ->from(array($this->_name))
                ->where("uid=?", $uid);
        if ($workflowItemId)
            $select = $select->where("workflow_item_id=?", $workflowItemId);
        if ($prjKode)
            $select = $select->where("prj_kode=?", $prjKode);
        if ($uidPrev)
            $select = $select->where("uid_prev=?", $uidPrev);

        $cek = $this->db->fetchRow($select);
        if ($cek) {
            return $cek;
        }

        return false;
    }

    public function __name() {
        return $this->_name;
    }

}
