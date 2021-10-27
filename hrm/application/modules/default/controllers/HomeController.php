<?php
class HomeController extends Zend_Controller_Action
{
	
	
    private $const;
	private $workflow;
	private $workflowClass;
	private $session;
	private $budget;
	private $quantity;
    private $db;
	private $workflowTrans;
	private $mail;
	private $trans;
	private $error;
    private $miscWorkid;
    private $database;
    
	public function init()
	{
		$this->const = Zend_Registry::get('constant');
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
    	$this->workflow = $this->_helper->getHelper('workflow');
//		$this->budget = $this->_helper->getHelper('budget');
        $this->budget = new Default_Models_Budget();
		$this->quantity = $this->_helper->getHelper('quantity');
		$this->trans = $this->_helper->getHelper('transaction');
		$this->mail = $this->_helper->getHelper('mail');
		$this->error = $this->_helper->getHelper('error');
		$this->session = new Zend_Session_Namespace('login');
        $this->work = new Default_Models_MasterWork();
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Model_Workflow();
        $this->miscWorkid = Zend_Registry::get('misc');
         $this->database = Zend_Registry::get('db');
	}
	
    public function indexAction() 
    {
        // TODO Auto-generated {0}::indexAction() default action
    }
    
    public function projectAction()
    {
        $this->budget = $this->_helper->getHelper('budget');
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $all = $request->getParam('all');
        if (!$all)
            $result = $this->budget->getBudgetProject(false,$prjKode,$sitKode);
        else
            $result = $this->budget->getBudgetProject(true);
        
       $this->view->result = $result;

    }
    
    public function myprojectsAction(){

    	$request = $this->getRequest();
    	$uid = $request->getParam('uid');
    	    	
    	$this->ldap = $this->_helper->getHelper('ldap');
    	
    	$account = $this->ldap->getAccount($uid);    	
    	$accountImage = $this->ldap->getAccountImage($uid);
    	
    	$this->view->account = $account;
    	$this->view->accountImage = $accountImage;
    	
    }

    public function documenttoprocessAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
    	$request = $this->getRequest();
    	$userID = $request->getParam('userid');
        $count = $request->getParam('count');
        $pa = $request->getParam('pa');
        $searchID = $request->getParam('id');
        $searchType = $request->getParam('type');

        if ($pa == '')
            $pa = false;

        if ($count == '')
            $count = false;

        //Bypass unutk Personal Assistant
        if ($pa)
        {

            $isPA = false;
            $pa = new Admin_Model_PersonalAssistant();
            $myUid = $this->session->userName;
            $fetchPa = $pa->fetchAll("uid = '$myUid'");
            $isPA = true;
            foreach($fetchPa as $key => $val)
            {
                $managerUid = $val['uid_manager'];
                $sql = "
                    SELECT id FROM
                        master_login
                    WHERE
                        uid = '$managerUid'
                ";
                $fetch = $this->db->query($sql);
                $fetch = $fetch->fetch();
                $managerID = $fetch['id'];
                $temps = $this->workflow->getAllMyWorkflow($managerID);
                if (count($myWorkflow) > 0)
                {
                    foreach ($temps as $key2 => $val2)
                    {
                        array_push($myWorkflow,$val2);
                    }
                }
                else
                    $myWorkflow = $this->workflow->getAllMyWorkflow($managerID);
            }
        }
        else
        {
            if ($searchType == '')
                $myWorkflow = $this->workflow->getAllMyWorkflow($userID);
            else
                $myWorkflow = $this->workflow->getAllMyWorkflow($userID,$searchType);

        }
    	$result = array();
    	$jumlah = 0;
    	$indeks = $offset;
//        if ($count)
//        {
//            $result = $this->workflow->countDocumentToProcessNew($this->session->userName);
//        }
//        else
//        {
            foreach ($myWorkflow as $key => $val)
            {
                if (!$count)
                    $temp = $this->workflow->getDocumentToProcess($val);
                else
                    $temp = $this->workflow->countDocumentToProcess($val);

                if ($temp)
                {
                        foreach ($temp as $key2 => $val2)
                        {
                            if (!$count)
                            {
                                $jumlah++;
    //	    					if ($indeks >= $offset && $indeks < ($offset + $limit))
    //	    					{

                                    if ($searchID != '')
                                    {
                                        if (strpos($val2['item_id'],$searchID))
                                            $result[] = $val2;
                                    }
                                    elseif ($searchType != '')
                                    {
                                        if ($val2['type']['name'] == $searchType)
                                            $result[] = $val2;
                                    }
                                    else
                                        $result[] = $val2;

    //	    					}
                                $indeks++;
                            }
                            else
                            {
                                $result[] = $val2;
                            }
                    }
                }
            }
//        }
    	if  ($count)
        {
            $jumlah = count($result);
    		$return = array('success' => true,'count' => $jumlah);
        }
    	else
    		$return = array('success' => true,'count' => $jumlah,"posts" => $result);
		$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function documenttoprocessnewAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
    	$request = $this->getRequest();
    	$userID = $request->getParam('userid');
        $count = $request->getParam('count');
        $pa = $request->getParam('pa');
        $searchID = $request->getParam('id');
        $searchType = $request->getParam('type');
        $manager_uid = $request->getParam('manager_uid');

        $sql = "
            SELECT uid FROM
                master_login
            WHERE
                id = $userID
        ";

        if ($searchType == 'ARF With BT')
        {
            $ARF_BT = true;
            $searchType = 'ARF';
        }

        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetch();

        $uid = $fetch['uid'];

        if ($pa == '')
            $pa = false;

        if ($count == '')
            $count = false;

        $ldap = new Default_Models_Ldap();
        //Bypass unutk Personal Assistant
        if ($pa)
        {
            $pa = new Admin_Model_PersonalAssistant();
            $myUid = $this->session->userName;
            $fetchPa = $pa->fetchAll("uid = '$myUid'");

            if ($fetchPa)
                $fetchPa->toArray();
            $isPA = true;
            if ($count)
                $docs = 0;
            else
            {
                $docs['posts'] = array();
                $docs['count'] = 0;
            }
            $manager = array();
            foreach($fetchPa as $key => $val)
            {
                $uid = $val['uid_manager'];
                $acc = $ldap->getAccount($uid);
                $manager[$uid] = $acc['displayname'][0];

                $temps = $this->workflowClass->getDocument($uid,$searchType,$searchID,$count,$offset,$limit);
                if ($count)
                {
                    $docs += $temps;
                }
                else
                {
                    foreach ($temps['posts'] as $key2 => $val2)
                    {
                        if($manager_uid != '')
                        {
                            if ($val2['uid_next'] == $manager_uid)
                            {
                                array_push($docs['posts'],$val2);
                                $docs['count']++;
                            }
                        }
                        else
                        {
                            array_push($docs['posts'],$val2);
                            $docs['count']++;
                        }
                    }
                }
            }
        }
        else
        {
            $docs = $this->workflowClass->getDocument($uid,$searchType,$searchID,$count,$offset,$limit);
        }
            $result = array();
            if (!$count)
            {
                foreach($docs['posts'] as $key => $val)
                {
                    $approve = $val['approve'];
                    if ($approve ==  $this->const['DOCUMENT_SUBMIT'])
                        $approve = 'SUBMITTED';
                    elseif ($approve ==  $this->const['DOCUMENT_RESUBMIT'])
                        $approve = 'RE-SUBMITTED';
                    elseif ($approve ==  $this->const['DOCUMENT_APPROVE'])
                        $approve = 'APPROVED';
                    elseif ($approve ==  $this->const['DOCUMENT_FINAL'])
                        $approve = 'FINAL APPROVAL';
                    elseif ($approve ==  $this->const['DOCUMENT_EXECUTE'])
                        $approve = 'EXECUTED';
                    elseif ($approve ==  $this->const['DOCUMENT_REJECT'])
                        $approve = 'REJECTED';

                    if ($val['uid'] != 'SYSTEM')
                    {
                        $otherUserName = $ldap->getAccount($val['uid']);
                        $usernamePrev = $otherUserName['displayname'][0];
                    }
                    else
                    {
                        $usernamePrev = 'SYSTEM ADMINISTRATOR';
                    }
                    $comment = str_replace("\n","",$val['comment']);
                    $comment = str_replace("\r","",$comment);
                    $comment = str_replace("\"","",$comment);
                    $comment = str_replace("'","",$comment);

                    $isBT = false;
                    if ($val['item_type'] == 'ARF' || $val['item_type'] == 'ARFO')
                    {
                        $arf = new Default_Models_AdvanceRequestFormD();
                        $trano = $val['item_id'];
                        $cek = $arf->fetchRow("LCASE(nama_brg) LIKE '%business trip%' AND trano = '$trano'");
                        if ($cek)
                        {
                            $isBT = true;
                        }
                    }

                    if ($val['item_type'] == 'RINV')
                    {
                        $rinv = new Finance_Models_RequestInvoice();
                        $trano = $val['item_id'];
                        $cek = $rinv->fetchRow("trano = '$trano'");
                        if ($cek)
                        {
                            $comment = "Customer Order No: " . $cek['co_no'];
                        }
                    }

                    if ($val['approve'] != $this->const['DOCUMENT_REJECT'])
                    {
                        if ($isPA)
                        {
                            $mUid = $val['uid_next'];
                            $manager_name = $manager[$mUid];
                        }
                        $result[] = array(
                            "id" => $val['workflow_trans_id'],
                            "item_id" => $val['item_id'],
                            "type" => $val['item_type'],
                            "prev" => $val['uid_prev'],
                            "uid" => $val['uid'],
                            "user_next" => $val['uid_next'],
                            "username_prev" => $usernamePrev,
                            "manager_name" => $manager_name,
                            "date" => $val['date'],
                            "approve" => $approve,
                            "comment" => $comment,
                            "prj_kode" => $val['prj_kode'],
//                            "signature" => $val['signature'],
                            "is_bt" => $isBT
                        );
                    }
                    else
                    {
                        $result[] = array(
                            "id" => $val['workflow_trans_id'],
                            "item_id" => $val['item_id'],
                            "type" => $val['item_type'],
                            "prev" => $val['uid_prev'],
                            "user_next" => $val['uid_next'],
                            "username_prev" => $usernamePrev,
                            "date" => $val['date'],
                            "approve" => $approve,
                            "comment" => $comment,
//                            "signature" => $val['signature'],
                            "prj_kode" => $val['prj_kode'],
                            "reject" => true
                        );
                    }

                }

                if ($ARF_BT)
                {
                    $resultNew = array();
                    foreach ($result as $k => $v)
                    {
                        if ($v['is_bt'] == true)
                            $resultNew[] = $v;
                    }

                    unset($result);
                    $result = $resultNew;
                }

                $jumlah = $docs['count'];
                $return = array('success' => true,'count' => $jumlah,"posts" => $result);
            }
            else
            {
                $return = array('success' => true,'count' => $docs);
            }


//        }
    	
		$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewdocumentAction()
    {
    	$process = $this->getRequest()->getParam("processdocument");
        if ($process)
            $this->view->processDocument = true;
        $process = $this->getRequest()->getParam("assistant");
        if ($process)
            $this->view->assistant = true;
    }

    public function showmydocumentAction()
    {
    	
    }

    public function showmydocumentworkflowAction()
    {

    }

    public function getdocumenturlAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$item_id = $this->getRequest()->getParam("id");
    	$type = $this->workflowTrans->getDocumentTypeByTrano($item_id);
    	$uid = $this->session->userName;

        if ($type)
    	{
            $docs = $this->workflowTrans->fetchRow("item_id='$item_id' AND uid='$uid'");
            if ($docs)
                $id = $docs['workflow_trans_id'];
            else
            {
                $msg = $this->error->getErrorMsg(304);
                echo "{success: false, msg: '$msg'}";
                die;
            }
            switch($type['name'])
            {
                case 'PR':
                    $url = "/procurement/procurement/apppr/approve/$id";
                    break;
                case 'PO':
                    $url = "/procurement/procurement/apppo/approve/$id";
                    break;
                case 'RPI':
                    $url = "/procurement/procurement/apprpi/approve/$id";
                    break;
                case 'ARF':
                    $url = "/procurement/procurement/apparf/approve/$id";
                    break;
                case 'ASF':
                    $url = "/procurement/procurement/appasf/approve/$id";
                    break;
                case 'PBOQ3':
                    $url = "/procurement/procurement/apppmeal/approve/$id";
                    break;
                case 'AFE':
                    $url = "/procurement/procurement/appafe/approve/$id";
                    break;
                case 'DOR':
                    $url = "/logistic/logistic/appdor/approve/$id";
                    break;
                case 'SUPP':
                    $url = "/logistic/logistic/appsupp/approve/$id";
                    break;
                case 'ICAN':
                    $url = "/logistic/logistic/appican/approve/$id";
                    break;
                case 'ILOV':
                    $url = "/logistic/logistic/appilov/approve/$id";
                    break;
            }

    		$return['url'] = $url;
    		$return['success'] = true;
    	}
    	else
    	{
    		$return['success'] = false;
    	}

    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getdocumenttypeAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$id = $this->getRequest()->getParam("id");
    	$type = $this->workflowTrans->getDocumentType($id);

        if ($type['name'] == 'ARF' || $type['name'] == 'ARFO')
        {
            $where1 = "workflow_trans_id = $id";

            $workflow = $this->workflowTrans->fetchRow($where1);

            $arftrano = $workflow['item_id'];

            $query = "SELECT trano,statrevisi,tipe FROM procurement_arfh WHERE trano = '$arftrano' AND statrevisi = 1";
            $fetch = $this->db->query($query);
            $data = $fetch->fetch();

            if ($data)
            {
                if ($data['tipe'] == 'P')
                {
                    $type['name'] = "ARF_REVISI";
                }else if ($data['tipe'] == 'O')
                {
                    $type['name'] = 'ARF_REVISI_OH';
                }else if ($data['tipe'] == 'S')
                {
                    $type['name'] = 'ARF_REVISI_S';
                }


            }
        }

    	if ($type)
    	{
    		$return['docs'] = $type;
    		$return['success'] = true;
    	}
    	else
    	{
    		$return['success'] = false;
    	}
    	
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
	public function showprocessdocumentAction()
    {
    	$type = $this->getRequest()->getParam("type");
    	if ($type != '')
    	{
    		$this->view->isType = true;
    		$this->view->type = $type;
    	}
    	
    	$this->view->userId = $this->session->idUser;
    }

    public function showassistantdocumentAction()
    {
    	$type = $this->getRequest()->getParam("type");
    	if ($type != '')
    	{
    		$this->view->isType = true;
    		$this->view->type = $type;
    	}

    	$this->view->userId = $this->session->idUser;
    }
    
    public function getmydocumentAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$uid = $this->session->userName;
    	$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $node = $this->getRequest()->getParam('node');

        if (strpos($node,"xnode") !== false)
        {
            $sql = "SELECT prj_kode
                    FROM workflow_trans
                    WHERE
                        uid = '$uid'
                    AND
                        (approve = 100 OR approve = 150)
                    AND prj_kode is not null
                    AND workflow_item_id is not null
                    AND workflow_structure_id is not null    
                    GROUP BY prj_kode
                    ORDER  BY prj_kode ASC";
            $fetch = $this->db->query($sql);
            $fetch = $fetch->fetchAll();
            if ($fetch)
            {
                $pros = $fetch;
                $id = 1;
                foreach ($pros as $key => $val)
                {
                    $result[] = array(
                        "id" => $id,
                        "isDocs" => false,
                        "isPerson" => false,
                        "prj_kode" => $val['prj_kode'],
                        "username" => "",
                        "date" => "",
                        "comment" => "",
                        "approve" => array(
                                "approve" => false,
                                "reject" => false,
                                "waiting" => false,
                                "msg" => ""
                            ),
                        "leaf" => false
                    );
                    $id++;
                }
            }
        }
        else
        {
            $id = $this->getRequest()->getParam('item_id');
            if ($id != '')
                $where = " AND item_id LIKE '%$id%'";
            $isDocs = $this->getRequest()->getParam('isDocs');
            if ($isDocs == 'false')
            {
                $prj_kode = $this->getRequest()->getParam('prj_kode');
                if ($prj_kode != '')
                    $where .= " AND prj_kode LIKE '%$prj_kode%'";
                $ldap = new Default_Models_Ldap();

                $sql = "SELECT * FROM
                            (SELECT *
                            FROM workflow_trans
                            WHERE
                                uid = '$uid'
                            AND
                                (approve = 100 OR approve = 150)
                            $where
                            ORDER BY date DESC
                            ) a
                            GROUP BY a.item_id
                            ORDER  BY a.item_id DESC
                        ";
                $fetch = $this->db->query($sql);
                $fetch = $fetch->fetchAll();
                if ($fetch)
                {
                    $docs = $fetch;
                    $result = array();
                    $uid = $this->session->userName;
                    $myname = $ldap->getAccount($uid);
                    foreach ($docs as $key => $val)
                    {
                        $trano = $val['item_id'];
                        $sql = "SELECT *
                                FROM workflow_trans
                                WHERE
                                    item_id = '$trano'
                                ORDER BY date DESC LIMIT 1";
                        $fetch2 = $this->db->query($sql);
                        $fetch2 = $fetch2->fetch();
                        $msg = "";
                        if ($fetch2)
                        {
                            $othername = $ldap->getAccount($fetch2['uid']);
                            $name = $othername['displayname'][0];
                            if ($fetch2['approve'] == $this->const['DOCUMENT_SUBMIT'] || $fetch2['approve'] == $this->const['DOCUMENT_RESUBMIT'] || $fetch2['approve'] == $this->const['DOCUMENT_APPROVE'])
                            {
                                $msg = 'WAITING FOR APPROVAL ' . $name;
                            }
                            elseif ($fetch2['approve'] == $this->const['DOCUMENT_FINAL'] || $fetch2['approve'] == $this->const['DOCUMENT_EXECUTE'])
                            {
                                $msg = 'FINAL APPROVAL/EXECUTED BY ' . $name;
                            }
                            elseif ($fetch2['approve'] == $this->const['DOCUMENT_REJECT'])
                            {
                                $msg = 'REJECTED BY ' . $name;
                            }
                        }
                        $result[] = array(
                            "id" => 'item-'.md5(date('d-m-y-H-i-s-u') . rand(0,1000)),
                            "trano" => $val['item_id'],
                            "workflow_item_id" => $val['workflow_item_id'],
                            "workflow_structure_id" => $val['workflow_structure_id'],
                            "workflow_id" => $val['workflow_id'],
                            "cls" => 'trano',
                            "isDocs" => true,
                            "date" => "",
                            "comment" => "",
                            "isPerson" => false,
                            "prj_kode" => "&nbsp;",
                            "approve" => array(
                                    "approve" => false,
                                    "reject" => false,
                                    "waiting" => false,
                                    "msg" => $msg
                                ),
                            "leaf" => false
                        );
                        $id++;
                    }
                }
            }
            else
            {
                $item_id = trim($this->getRequest()->getParam('trano'));
                $workflow_item_id = $this->getRequest()->getParam('workflow_item_id');
                $myworkflow_id = $this->getRequest()->getParam('workflow_id');
                $workflow_structure_id = $this->getRequest()->getParam('workflow_structure_id');

                $ldap = new Default_Models_Ldap();
                $sql = "SELECT *
                        FROM workflow_trans
                        WHERE
                            uid = '$uid'
                        AND
                            (approve = 100 OR approve = 150)
                        AND
                            item_id = '$item_id'
                        AND
                            workflow_item_id = $workflow_item_id
                        AND
                            workflow_id = $myworkflow_id
                        ORDER  BY date DESC LIMIT 1";
                $fetch = $this->db->query($sql);
                $fetch = $fetch->fetch();

                if ($fetch)
                {
                    $othername = $ldap->getAccount($uid);
                    $name = $othername['displayname'][0];
                    $mail = $othername['mail'][0];
                    if ($fetch['approve'] == $this->const['DOCUMENT_SUBMIT'])
                    {
                        $approve = 'SUBMITTED';
                    }
                    elseif ($fetch['approve'] == $this->const['DOCUMENT_RESUBMIT'])
                    {
                        $approve = 'RE-SUBMITTED';
                    }
                    $approve = array(
                        "approve" => true,
                        "reject" => false,
                        "waiting" => false,
                        "msg" => $approve
                    );
                    $result[] = array(
                        "id" => 'docs-'.md5(date('d-m-y-H-i-s-u') . rand(0,1000)),
                        "trano" => $fetch['item_id'],
                        "workflow_item_id" => $fetch['workflow_item_id'],
                        "workflow_structure_id" => $fetch['workflow_structure_id'],
                        "workflow_id" => $fetch['workflow_id'],
                        "cls" => 'starter',
                        "isPerson" => true,
                        "isDocs" => true,
                        "prj_kode" => "&nbsp;",
                        "leaf" => true,
                        "username" => $name,
                        "mail" => $mail,
                        "comment" => "",
                        "date" => date('d-m-Y H:i:s',strtotime($fetch['date'])),
                        "approve" => $approve,
                        "start" => true,
                        "end" => false
                    );
                }
                $lastApprove = 'SUBMITTED';
                if ($item_id != '' && $workflow_item_id != '' && $workflow_structure_id != '' && $myworkflow_id != '')
                {
                    $myworkflow = $this->workflowClass->fetchRow("workflow_id=$myworkflow_id AND workflow_item_id = $workflow_item_id");
                    $end = $myworkflow['is_end'];
                    $next = $myworkflow['next'];
                    $current = $myworkflow['workflow_structure_id'];
                    $id = 1;    
                    while ($end != 1)
                    {
                        $fetch2 = $this->workflowClass->fetchRow("workflow_structure_id = $next AND prev = $current  AND workflow_item_id = $workflow_item_id");
                        if ($fetch2)
                        {
                            $current = $fetch2['workflow_structure_id'];
                            $workflow_id = $fetch2['workflow_id'];
                            $next = $fetch2['next'];
                            $othername = $ldap->getAccount($fetch2['uid']);
                            $name = $othername['displayname'][0];
                            $docs2= $this->workflowTrans->fetchAll("workflow_id=$workflow_id AND item_id='$item_id' AND workflow_item_id = $workflow_item_id AND uid = '" . $fetch2['uid'] . "'",array ('date DESC'), 1, 0);
                            if (count($docs2) > 0)
                            {
                                foreach ($docs2 as $key2 => $val2)
                                {
                                    $stat = '';
                                    $approve = $val2['approve'];
                                    $othername = $ldap->getAccount($val2['uid']);
                                    $name = $othername['displayname'][0];
                                    $mail = $othername['mail'][0];
                                    if ($approve == $this->const['DOCUMENT_APPROVE'])
                                    {
                                        $approve = 'APPROVED';
                                        $lastDate = new DateTime($val2['date']);
                                        $lastApprove = $approve;
                                        $approval = true;
                                        $reject = false;
                                        $waiting = false;
                                    }
                                    elseif ($approve == $this->const['DOCUMENT_RESUBMIT'])
                                    {
                                        $approve = 'RE-SUBMITTED';
                                        $approval = true;
                                        $waiting = false;
                                        $reject = false;
                                    }
                                    elseif ($approve == $this->const['DOCUMENT_REJECT'])
                                    {
                                        $approve = 'REJECTED';
                                        $stat = 'Document has been Rejected!';
                                        $approval = false;
                                        $reject = true;
                                        $waiting = false;
                                    }
                                    elseif ($approve == $this->const['DOCUMENT_FINAL'])
                                    {
                                        $approve = 'FINAL APPROVAL';
                                        $lastApprove = $approve;
                                        $approval = true;
                                        $reject = false;
                                        $waiting = false;
                                    }
                                    elseif ($approve == $this->const['DOCUMENT_EXECUTE'])
                                    {
                                        $approve = 'EXECUTED';
                                        $approval = true;
                                        $reject = false;
                                        $waiting = false;
                                    }
                                    $comment = $val2['comment'];
                                    if (strpos($comment,"\r") !== false)
                                        $comment = str_replace("\r"," ",$comment);
                                    if (strpos($comment,"\n") !== false)
                                        $comment = str_replace("\n"," ",$comment);
                                    if (strpos($comment,"\"") !== false)
                                        $comment = str_replace("\"","",$comment);
                                    $date = $val2['date'];
                                    if ($fetch2['is_end'] == 1)
                                    {
                                        $icon = "ender";
                                        $isEnd = true;
                                    }
                                    else
                                    {
                                        $icon = "next";
                                        $isEnd = false;
                                    }
                                    $approve = array(
                                        "approve" => $approval,
                                        "reject" => $reject,
                                        "waiting" => $waiting,
                                        "msg" => $approve
                                    );
                                    $result[] = array(
                                        "id" => 'docs-'.md5(date('d-m-y-H-i-s-u') . rand(0,1000)),
                                        "trano" => $val2['item_id'],
                                        "workflow_item_id" => $val2['workflow_item_id'],
                                        "workflow_structure_id" => $val2['workflow_structure_id'],
                                        "workflow_id" => $val2['workflow_id'],
                                        "cls" => $icon,
                                        "isPerson" => true,
                                        "isDocs" => true,
                                        "prj_kode" => "&nbsp;",
                                        "leaf" => true,
                                        "username" => $name,
                                        "mail" => $mail,
                                        "date" => date('d-m-Y H:i:s',strtotime($date)),
                                        "comment" => $comment,
                                        "invalid" => $stat,
                                        "approve" => $approve,
                                        "start" => false,
                                        "end" => $isEnd
                                    );
                                }
                            }
                            else
                            {
                                $name = $this->workflow->getUserByRoleId($fetch2['master_role_id']);
                                $mail = $this->workflow->getUserByRoleId($fetch2['master_role_id'],true);
                                if ($val['date'] != '')
                                {

                                    $lastDate = new DateTime(date('d-m-Y H:i:s',strtotime($val['date'])));
                                    $nowDate = new DateTime(date('d-m-Y H:i:s'));
                                    $diff = $lastDate->diff($nowDate);
                                    if ($lastApprove == 'SUBMITTED')
                                        $stat = "Has been " . $diff->format('%d days') . " since submitted.";
                                    else if ($lastApprove == 'APPROVED')
                                        $stat = "Has been " . $diff->format('%d days') . " since last approval.";
                                    else if ($lastApprove == 'FINAL APPROVED')
                                        $stat = "Has been " . $diff->format('%d days') . " since last final approval.";
                                }
                                if ($fetch2['is_end'] == 1)
                                    {
                                        $icon = "ender";
                                        $isEnd = true;
                                    }
                                    else
                                    {
                                        $icon = "next";
                                        $isEnd = false;
                                    }
                                $approve = array(
                                        "approve" => false,
                                        "reject" => false,
                                        "waiting" => true,
                                        "msg" => 'WAITING FOR APPROVAL'
                                );
                                $result[] = array(
                                        "id" => 'nodocs-' . md5(date('d-m-y-H-i-s-u') . rand(0,1000)),
                                        "workflow_item_id" => $fetch2['workflow_item_id'],
                                        "workflow_structure_id" => $fetch2['workflow_structure_id'],
                                        "workflow_id" => $fetch2['workflow_id'],
                                        "trano" => $fetch['item_id'],
                                        "isPerson" => true,
                                        "isDocs" => true,
                                        "cls" => $icon,
                                        "leaf" => true,
                                        "item_id" => $item_id,
                                        "prj_kode" => "&nbsp;",
                                        "username" => $name,
                                        "mail" => $mail['mail'][0],
                                        "date" => '0000-00-00 00:00:00',
                                        "comment" => '',
                                        "approve" => $approve,
                                        "invalid" => $stat,
                                        "start" => false,
                                        "end" => $isEnd
                                    );
                                $id++;
                            }
                            $end = $fetch2['is_end'];
                        }
                        else
                        {
                            $end = 1;
                        }
                    }
                }
//                var_dump($result);die;
            }    
        }
    //    			$myworkflow_id = $val['workflow_id'];
    //                if ($myworkflow_id == '')
    //                    continue;
    //    			$myworkflow = $this->workflowClass->fetchRow("workflow_id=$myworkflow_id");
    //    			$end = $myworkflow['is_end'];
    //    			$next = $myworkflow['next'];
    //    			$current = $myworkflow['workflow_structure_id'];
    //    			$item_id = $val['item_id'];
    //    			$type = $this->workflowTrans->getDocumentType($val['workflow_trans_id']);
    //
    //       			$typeSearch = $this->getRequest()->getParam('type');
    //       			if ($typeSearch != '')
    //       			{
    //       				if ($type['workflow_item_type_id'] != $typeSearch)
    //       					continue;
    //       			}
    //    			$result[] = array(
    //    				"id" => $id,
    //    				"type" => $type['name'],
    //    				"item_id" => $val['item_id'],
    //    				"username" => $myname['displayname'][0],
    //    				"date" => date('d-m-Y H:i:s',strtotime($val['date'])),
    //    				"comment" => $val['comment'],
    //    				"approve" => 'SUBMITTED',
    //    				"start" => true,
    //    				"end" => false
    //    			);
    //    			$lastApprove = 'SUBMITTED';
    //    			$id++;
    //    			$lastDate = '';
    //                if ($next == '')
    //                {
    //                    continue;
    //                }
    //    			while ($end != 1)
    //    			{
    //    				$fetch2 = $this->workflowClass->fetchRow("workflow_structure_id = $next AND prev = $current");
    //    				if ($fetch2)
    //    				{
    //    					$current = $fetch2['workflow_structure_id'];
    //    					$workflow_id = $fetch2['workflow_id'];
    //    					$next = $fetch2['next'];
    //    					$docs2= $this->workflowTrans->fetchAll("workflow_id=$workflow_id AND item_id='$item_id'",array ($sort . " " . 'ASC'));
    //    					if (count($docs2) > 0)
    //    					{
    //    						foreach ($docs2 as $key2 => $val2)
    //    						{
    //                                $stat = '';
    //    							$approve = $val2['approve'];
    //                                $othername = $ldap->getAccount($val2['uid']);
    //    							$name = $othername['displayname'][0];
    //    							$mail = $othername['mail'][0];
    //                                if ($approve == $this->const['DOCUMENT_APPROVE'])
    //    							{
    //    								$approve = 'APPROVED';
    //    								$lastDate = new DateTime($val2['date']);
    //    								$lastApprove = $approve;
    //    							}
    //                                elseif ($approve == $this->const['DOCUMENT_RESUBMIT'])
    //    							{
    //    								$approve = 'RE-SUBMITTED';
    //    							}
    //    							elseif ($approve == $this->const['DOCUMENT_REJECT'])
    //    							{
    //    								$approve = 'REJECTED';
    //    								$stat = 'Document has been Rejected!';
    //    							}
    //    							elseif ($approve == $this->const['DOCUMENT_FINAL'])
    //                                {
    //    								$approve = 'FINAL APPROVAL';
    //                                    $lastApprove = $approve;
    //                                }
    //                                elseif ($approve == $this->const['DOCUMENT_EXECUTE'])
    //    								$approve = 'EXECUTED';
    //    							$comment = $val2['comment'];
    //    							$date = $val2['date'];
    //    							if ($fetch2['is_end'] == 1)
    //    								$isEnd = true;
    //    							else
    //    								$isEnd = false;
    //    							$result[] = array(
    //    								"id" => $id,
    //    								"item_id" => $item_id,
    //    								"username" => $name,
    //    								"mail" => $mail,
    //    								"date" => date('d-m-Y H:i:s',strtotime($date)),
    //    								"comment" => $comment,
    //    								"invalid" => $stat,
    //    								"approve" => $approve,
    //    								"start" => false,
    //    								"end" => $isEnd
    //    							);
    //    							$id++;
    //    						}
    //    					}
    //    					else
    //    					{
    //                            $name = $this->workflow->getUserByRoleId($fetch2['master_role_id']);
    //                            $mail = $this->workflow->getUserByRoleId($fetch2['master_role_id'],true);
    //                            if ($val['date'] != '')
    //    						{
    //
    //    						    $lastDate = new DateTime(date('d-m-Y H:i:s',strtotime($val['date'])));
    //	    						$nowDate = new DateTime(date('d-m-Y H:i:s'));
    //	    						$diff = $lastDate->diff($nowDate);
    //	    						if ($lastApprove == 'SUBMITTED')
    //	    							$stat = "Has been " . $diff->format('%d days') . " since submitted.";
    //	    						else if ($lastApprove == 'APPROVED')
    //	    							$stat = "Has been " . $diff->format('%d days') . " since last approval.";
    //	    						else if ($lastApprove == 'FINAL APPROVED')
    //	    							$stat = "Has been " . $diff->format('%d days') . " since last final approval.";
    //    						}
    //    						if ($fetch2['is_end'] == 1)
    //    							$isEnd = true;
    //    						else
    //    							$isEnd = false;
    //    						$result[] = array(
    //    								"id" => $id,
    //    								"item_id" => $item_id,
    //    								"username" => $name,
    //    								"mail" => $mail['mail'][0],
    //    								"date" => '0000-00-00 00:00:00',
    //    								"comment" => '',
    //    								"approve" => 'WAITING FOR APPROVAL',
    //    								"invalid" => $stat,
    //    								"start" => false,
    //    								"end" => $isEnd
    //    							);
    //    						$id++;
    //    					}
    //    					$end = $fetch2['is_end'];
    //    				}
    //    				else
    //    				{
    //    					$end = 1;
    //    				}
    //    			}
//                }

    //            foreach ($result as $key => $val)
    //            {
    //                if (strpos($val['comment'],"\r") !== false)
    //                    $result[$key]['comment'] = str_replace("\r"," ",$result[$key]['comment']);
    //                if (strpos($val['comment'],"\n") !== false)
    //                    $result[$key]['comment'] = str_replace("\n"," ",$result[$key]['comment']);
    //                if (strpos($val['comment'],"\"") !== false)
    //                    $result[$key]['comment'] = str_replace("\"","",$result[$key]['comment']);
    //            }

    //    		$return['posts'] = $result;
    //	    	$return['count'] = count($result);
    //	    	$json = Zend_Json::encode($return);
    
	    	$json = Zend_Json::encode($result);
	        //result encoded in JSON
			$json = str_replace("\\","",$json);
	        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
	        $this->getResponse()->setBody($json);
    	
    }

    public function getmydocumentinworkflowAction()
    {
//    	$this->_helper->viewRenderer->setNoRender();
//    	$uid = $this->session->userName;
//    	$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
//        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
//        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'date';
//        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';
//
//        $id = $this->getRequest()->getParam('id');
//        if ($id != '')
//        	$where = " AND item_id LIKE '%$id%'";
//    	$ldap = new Default_Models_Ldap();
//
////
//        $id_user = $this->session->idUser;
//        //Ambil semua workflow yg aktif untuk uid user...
//        $whichWorkflow = $this->workflow->getAllMyWorkflow($id_user);
//        $result = array();
//        if ($whichWorkflow)
//        {
//            $countDocs = 0;
//            $docsResult = array();
//            foreach($whichWorkflow as $key3 => $val3)
//            {
//                //Cari yg submit document pertama kali...
////                $starters = $this->workflowClass->fetchAll(
////                                $this->workflowClass->select()->from(
////                                    $this->workflowClass
////                                )
////                                ->where("is_start = 1 AND is_end = 0 AND workflow_item_id = " . $val3['workflow_item_id'])
////                           );
//                $sql = "SELECT * FROM workflow WHERE is_start = 1 AND is_end = 0 AND workflow_item_id = " . $val3['workflow_item_id'];
//                $starters = $this->db->query($sql);
//                $starters = $starters->fetchAll();
//                if ($starters)
//                {
//                    foreach ($starters as $key => $starter)
//                    {
//                        $uidStart = $starter['uid'];
//                        //Ambil semua dokumen bedasarkan uid & workflow_id user yg submit
//                            $where2 = " AND workflow_item_id = " . $val3['workflow_item_id'];
//                        $fetch = $this->workflowTrans->fetchAll(
//                          $this->workflowTrans->select()->from(
//                                $this->workflowTrans
//                            )
//                            ->where($this->db->quoteInto("uid = ? $where $where2",$uidStart))
//                            ->group('item_id')
//                            ,array($sort . ' ' . $dir, 'approve DESC'), $limit, $offset
//                        )->toArray();
//                        if ($fetch)
//                        {
//                            $countDocs += count($fetch);
//                        $docs = $fetch;
//                        $myname = $ldap->getAccount($uidStart);
//                        $id = 1;
//                        foreach ($docs as $key2 => $val)
//                        {
//                            $myworkflow_id = $val['workflow_id'];
//                            if ($myworkflow_id == '')
//                                continue;
//                            $myworkflow = $this->workflowClass->fetchRow("workflow_id=$myworkflow_id AND workflow_item_id = " . $val3['workflow_item_id']);
//
//                            $end = $myworkflow['is_end'];
//                            $next = $myworkflow['next'];
//                            $current = $myworkflow['workflow_structure_id'];
//                            if ($next == '' || $current == '')
//                                continue;
//                            $item_id = $val['item_id'];
//                            $type = $this->workflowTrans->getDocumentType($val['workflow_trans_id']);
//                            $typeSearch = $this->getRequest()->getParam('type');
//                            if ($typeSearch != '')
//                            {
//                                if ($type['workflow_item_type_id'] != $typeSearch)
//                                    continue;
//                            }
//                            if ($type['name'] == '')
//                            {
//                                $sql = "SELECT b.name FROM workflow_item a LEFT JOIN workflow_item_type b ON a.workflow_item_type_id = b.workflow_item_type_id WHERE a.workflow_item_id =". $val3['workflow_item_id'];
//                                $fetch = $this->db->query($sql);
//                                $itemType = $fetch->fetch();
//                                $type['name'] = $itemType['name'];
//                            }
//                            $docsResult[$val['item_id']] = 1;
//                            $result[] = array(
//                                "id" => $id,
//                                "type" => $type['name'],
//                                "item_id" => $val['item_id'],
//                                "username" => $myname['displayname'][0],
//                                "date" => date('d-m-Y H:i:s',strtotime($val['date'])),
//                                "comment" => $val['comment'],
//                                "approve" => 'SUBMITTED',
//                                "start" => true,
//                                "end" => false
//                            );
//                            $lastApprove = 'SUBMITTED';
//                            $id++;
//                            $lastDate = '';
//                            while ($end != 1)
//                            {
//                                $fetch2 = $this->workflowClass->fetchRow("workflow_structure_id = $next AND prev = $current");
//                                if ($fetch2)
//                                {
//                                    $current = $fetch2['workflow_structure_id'];
//                                    $workflow_id = $fetch2['workflow_id'];
//                                    $next = $fetch2['next'];
//                                    $docs2= $this->workflowTrans->fetchAll("workflow_id=$workflow_id AND item_id='$item_id'",array ($sort . " " . 'ASC'));
//                                    if (count($docs2) > 0)
//                                    {
//                                        $countDocs += count($docs2);
//                                        foreach ($docs2 as $key2 => $val2)
//                                        {
//                                            $stat = '';
//                                            $name = $ldap->getAccount($val2['uid']);
//    							            $mail = $name['mail'][0];
//                                            $name = $name['displayname'][0];
//                                            $approve = $val2['approve'];
//                                            if ($approve == $this->const['DOCUMENT_APPROVE'])
//                                            {
//                                                $approve = 'APPROVED';
//                                                $lastDate = new DateTime($val2['date']);
//                                                $lastApprove = $approve;
//                                            }
//                                            elseif ($approve == $this->const['DOCUMENT_RESUBMIT'])
//                                            {
//                                                $approve = 'RE-SUBMITTED';
//                                            }
//                                            elseif ($approve == $this->const['DOCUMENT_REJECT'])
//                                            {
//                                                $approve = 'REJECTED';
//                                                $stat = 'Document has been Rejected!';
//                                            }
//                                            elseif ($approve == $this->const['DOCUMENT_FINAL'])
//                                            {
//                                                $approve = 'FINAL APPROVAL';
//                                                $lastApprove = $approve;
//                                            }
//                                            elseif ($approve == $this->const['DOCUMENT_EXECUTE'])
//                                                $approve = 'EXECUTED';
//                                            $comment = $val2['comment'];
//                                            $date = $val2['date'];
//                                            if ($fetch2['is_end'] == 1)
//                                                $isEnd = true;
//                                            else
//                                                $isEnd = false;
//
//                                            if ($val2['item_type'] == '')
//                                            {
//                                                $sql = "SELECT b.name FROM workflow_item a LEFT JOIN workflow_item_type b ON a.workflow_item_type_id = b.workflow_item_type_id WHERE a.workflow_item_id =". $val2['workflow_item_id'];
//                                                $fetch = $this->db->query($sql);
//                                                $itemType = $fetch->fetch();
//                                                $type['name'] = $itemType['name'];
//                                            }
//                                            else
//                                                $type['name'] = $val2['item_type'];
//
//                                            $docsResult[$item_id] = 1;
//                                            $result[] = array(
//                                                "id" => $id,
//                                                "type" => $type['name'],
//                                                "item_id" => $item_id,
//                                                "username" => $name,
//                                                "mail" => $mail,
//                                                "date" => date('d-m-Y H:i:s',strtotime($date)),
//                                                "comment" => $comment,
//                                                "invalid" => $stat,
//                                                "approve" => $approve,
//                                                "start" => false,
//                                                "end" => $isEnd
//                                            );
//                                            $id++;
//                                        }
//                                    }
//                                    else
//                                    {
//                                        $name = $this->workflow->getUserByRoleId($fetch2['master_role_id']);
//                                        $mail = $this->workflow->getUserByRoleId($fetch2['master_role_id'],true);
//                                        $lastDate = new DateTime($val['date']);
//                                        if ($lastDate != '')
//                                        {
//                                            $nowDate = new DateTime(date('d-m-Y H:i:s'));
//                                            $diff = $lastDate->diff($nowDate);
//                                            if ($lastApprove == 'SUBMITTED')
//                                                $stat = "Has been " . $diff->format('%d days') . " since submitted.";
//                                            else if ($lastApprove == 'APPROVED')
//                                                $stat = "Has been " . $diff->format('%d days') . " since last approval.";
//                                            else if ($lastApprove == 'FINAL APPROVED')
//                                                $stat = "Has been " . $diff->format('%d days') . " since last final approval.";
//                                        }
//                                        if ($fetch2['is_end'] == 1)
//                                            $isEnd = true;
//                                        else
//                                            $isEnd = false;
//                                        $countDocs += count($docs2);
//                                        $docsResult[$item_id] = 1;
//                                        $result[] = array(
//                                                "id" => $id,
//                                                "type" => $type['name'],
//                                                "item_id" => $item_id,
//                                                "username" => $name,
//                                                "mail" => $mail['mail'][0],
//                                                "date" => '0000-00-00 00:00:00',
//                                                "comment" => '',
//                                                "approve" => 'WAITING FOR APPROVAL',
//                                                "invalid" => $stat,
//                                                "start" => false,
//                                                "end" => $isEnd
//                                            );
//                                        $id++;
//                                    }
//                                    $end = $fetch2['is_end'];
//                                }
//                                else
//                                {
//                                    $end = 1;
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
////    	$fetch = $this->workflowTrans->fetchAll($this->db->quoteInto("uid = ? AND approve = 100 $where",$uid),array ($sort . " " . $dir), $limit, $offset);
//
//            foreach ($result as $key => $val)
//            {
//                if (strpos($val['comment'],"\r") !== false)
//                    $result[$key]['comment'] = str_replace("\r"," ",$result[$key]['comment']);
//                if (strpos($val['comment'],"\n") !== false)
//                    $result[$key]['comment'] = str_replace("\n"," ",$result[$key]['comment']);
//                if (strpos($val['comment'],"\"") !== false)
//                    $result[$key]['comment'] = str_replace("\"","",$result[$key]['comment']);
//            }

        $this->_helper->viewRenderer->setNoRender();
    	$uid = $this->session->userName;
    	$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $node = $this->getRequest()->getParam('node');

        if (strpos($node,"xnode") !== false)
        {
            $sql = "SELECT prj_kode
                    FROM workflow
                    WHERE
                        uid = '$uid'
                    AND prj_kode is not null
                    AND workflow_item_id is not null
                    AND workflow_structure_id is not null
                    GROUP BY prj_kode
                    ORDER  BY prj_kode ASC";
            $fetch = $this->db->query($sql);
            $fetch = $fetch->fetchAll();
            if ($fetch)
            {
                $pros = $fetch;
                $id = 1;
                foreach ($pros as $key => $val)
                {
                    $result[] = array(
                        "id" => $id,
                        "isDocs" => false,
                        "isPerson" => false,
                        "prj_kode" => $val['prj_kode'],
                        "username" => "",
                        "date" => "",
                        "comment" => "",
                        "approve" => array(
                                "approve" => false,
                                "reject" => false,
                                "waiting" => false,
                                "msg" => ""
                            ),
                        "leaf" => false
                    );
                    $id++;
                }
            }
        }
        else
        {
            $id = $this->getRequest()->getParam('item_id');
            if ($id != '')
                $where = " AND item_id LIKE '%$id%'";
            $isDocs = $this->getRequest()->getParam('isDocs');
            if ($isDocs == 'false')
            {
                $prj_kode = $this->getRequest()->getParam('prj_kode');
                if ($prj_kode != '')
                    $where .= " AND prj_kode LIKE '%$prj_kode%'";
                $ldap = new Default_Models_Ldap();

                $sql = "
                            SELECT
                                DISTINCT
                                workflow_item_id,
                                workflow_structure_id,
                                master_role_id,
                                uid,
                                is_start,
                                is_end,
                                prev,
                                next,
                                prj_kode,
                                uid_next,
                                uid_prev
                            FROM workflow
                            WHERE
                                uid = '$uid'
                            AND
                                is_start = 0
                            $where
                            GROUP BY workflow_item_id
                        ";
                $fetch = $this->db->query($sql);
                $fetch = $fetch->fetchAll();
                if ($fetch)
                {
                    foreach($fetch as $key => $val)
                    {
                        $myWorkflow_item_id = $val['workflow_item_id'];
                        $sql = "
                                SELECT b.*
                                FROM workflow a
                                LEFT JOIN workflow_trans b
                                ON
                                        b.uid = a.uid
                                    AND
                                        a.workflow_item_id = b.workflow_item_id
                                WHERE
                                    a.workflow_item_id = $myWorkflow_item_id
                                AND
                                    a.is_start = 1
                                AND
                                    b.item_id is not null
                                GROUP BY b.item_id
                                ORDER BY b.date DESC    
                            ";
                        $fetch2 = $this->db->query($sql);
                        $fetch2 = $fetch2->fetchAll();

                        if ($fetch2)
                        {
                            foreach($fetch2 as $key2 => $val2)
                            {
                                $ceks = $this->workflow->getDocumentLastStatusAll($val2['item_id']);
                                if ($ceks)
                                {
                                    $cek = $ceks['approve'];
                                    $othername = $ldap->getAccount($ceks['uid']);
                                    $name = $othername['displayname'][0];
                                    if ($cek == $this->const['DOCUMENT_SUBMIT'] || $cek == $this->const['DOCUMENT_RESUBMIT'] || $cek == $this->const['DOCUMENT_APPROVE'])
                                    {
                                        $sql = "SELECT uid_next FROM workflow where uid = '" . $ceks['uid'] . "' AND workflow_item_id = " . $ceks['workflow_item_id'] . " AND workflow_structure_id = " . $ceks['workflow_structure_id'] . " AND workflow_id = " . $ceks['workflow_id'];   
                                        $fetch3 = $this->db->query($sql);
                                        $next = $fetch3->fetch();

                                        $othername = $ldap->getAccount($next['uid_next']);
                                        $name = $othername['displayname'][0];

                                        $msg = 'WAITING FOR APPROVAL ' . $name;
                                    }
                                    elseif ($cek == $this->const['DOCUMENT_FINAL'] || $cek == $this->const['DOCUMENT_EXECUTE'])
                                    {
                                        $msg = 'FINAL APPROVAL/EXECUTED BY ' . $name;
                                    }
                                    elseif ($cek == $this->const['DOCUMENT_REJECT'])
                                    {
                                        $msg = 'REJECTED BY ' . $name;
                                    }
                                }

                                $result[] = array(
                                    "id" => 'item-'.md5(date('d-m-y-H-i-s-u') . rand(0,1000)),
                                    "trano" => $val2['item_id'],
                                    "workflow_item_id" => $val2['workflow_item_id'],
                                    "workflow_structure_id" => $val2['workflow_structure_id'],
                                    "workflow_id" => $val2['workflow_id'],
                                    "uid" => $val2['uid'],
                                    "cls" => 'trano',
                                    "isDocs" => true,
                                    "date" => "",
                                    "comment" => "",
                                    "isPerson" => false,
                                    "prj_kode" => "&nbsp;",
                                    "approve" => array(
                                            "approve" => false,
                                            "reject" => false,
                                            "waiting" => false,
                                            "msg" => $msg
                                        ),
                                    "leaf" => false
                                );
                            }

                        }


                    }
                }
            }
            else
            {
                $item_id = trim($this->getRequest()->getParam('trano'));
                $workflow_item_id = $this->getRequest()->getParam('workflow_item_id');
                $startWorkflow_id = $this->getRequest()->getParam('workflow_id');
                $workflow_structure_id = $this->getRequest()->getParam('workflow_structure_id');
                $uidStart = $this->getRequest()->getParam('uid');

                $ldap = new Default_Models_Ldap();
                $sql = "SELECT *
                        FROM workflow_trans
                        WHERE
                            uid = '$uidStart'
                        AND
                            (approve = 100 OR approve = 150)
                        AND
                            item_id = '$item_id'
                        AND
                            workflow_item_id = $workflow_item_id
                        AND
                            workflow_id = $startWorkflow_id
                        ORDER  BY date DESC LIMIT 1";
                $fetch = $this->db->query($sql);
                $fetch = $fetch->fetch();

                if ($fetch)
                {
                    $docsDate = $fetch['date'];
                    $othername = $ldap->getAccount($uidStart);
                    $name = $othername['displayname'][0];
                    $mail = $othername['mail'][0];
                    if ($fetch['approve'] == $this->const['DOCUMENT_SUBMIT'])
                    {
                        $approve = 'SUBMITTED';
                    }
                    elseif ($fetch['approve'] == $this->const['DOCUMENT_RESUBMIT'])
                    {
                        $approve = 'RE-SUBMITTED';
                    }
                    $lastApprove = $approve;
                    $approve = array(
                        "approve" => true,
                        "reject" => false,
                        "waiting" => false,
                        "msg" => $approve
                    );
                    $result[] = array(
                        "id" => 'docs-'.md5(date('d-m-y-H-i-s-u') . rand(0,1000)),
                        "trano" => $fetch['item_id'],
                        "workflow_item_id" => $fetch['workflow_item_id'],
                        "workflow_structure_id" => $fetch['workflow_structure_id'],
                        "workflow_id" => $fetch['workflow_id'],
                        "cls" => 'starter',
                        "isPerson" => true,
                        "isDocs" => true,
                        "prj_kode" => "&nbsp;",
                        "leaf" => true,
                        "username" => $name,
                        "mail" => $mail,
                        "comment" => "",
                        "date" => date('d-m-Y H:i:s',strtotime($fetch['date'])),
                        "approve" => $approve,
                        "start" => true,
                        "end" => false
                    );
                }
                if ($item_id != '' && $workflow_item_id != '' && $workflow_structure_id != '' && $startWorkflow_id != '')
                {
                    $myworkflow = $this->workflowClass->fetchRow("workflow_id=$startWorkflow_id AND workflow_item_id = $workflow_item_id");
                    $end = $myworkflow['is_end'];
                    $next = $myworkflow['next'];
                    $current = $myworkflow['workflow_structure_id'];
                    $id = 1;
                    while ($end != 1)
                    {
                        $fetch2 = $this->workflowClass->fetchRow("workflow_structure_id = $next AND prev = $current  AND workflow_item_id = $workflow_item_id");
                        if ($fetch2)
                        {
                            $current = $fetch2['workflow_structure_id'];
                            $workflow_id = $fetch2['workflow_id'];
                            $next = $fetch2['next'];
                            $othername = $ldap->getAccount($fetch2['uid']);
                            $name = $othername['displayname'][0];
                            $docs2= $this->workflowTrans->fetchAll("workflow_id=$workflow_id AND item_id='$item_id' AND workflow_item_id = $workflow_item_id AND uid = '" . $fetch2['uid'] . "'",array ('date DESC'), 1, 0);
                            if (count($docs2) > 0 || $lastApprove == $this->const['DOCUMENT_SUBMIT'])
                            {
                                foreach ($docs2 as $key2 => $val2)
                                {
                                    $stat = '';
                                    $approve = $val2['approve'];
                                    $othername = $ldap->getAccount($val2['uid']);
                                    $name = $othername['displayname'][0];
                                    $mail = $othername['mail'][0];
                                    if ($approve == $this->const['DOCUMENT_APPROVE'])
                                    {
                                        $approve = 'APPROVED';
                                        $lastDate = new DateTime($val2['date']);
                                        $lastApprove = $approve;
                                        $approval = true;
                                        $reject = false;
                                        $waiting = false;
                                    }
                                    elseif ($approve == $this->const['DOCUMENT_RESUBMIT'])
                                    {
                                        $approve = 'RE-SUBMITTED';
                                        $approval = true;
                                        $waiting = false;
                                        $reject = false;
                                    }
                                    elseif ($approve == $this->const['DOCUMENT_REJECT'])
                                    {
                                        $approve = 'REJECTED';
                                        $stat = 'Document has been Rejected!';
                                        $approval = false;
                                        $reject = true;
                                        $waiting = false;
                                    }
                                    elseif ($approve == $this->const['DOCUMENT_FINAL'])
                                    {
                                        $approve = 'FINAL APPROVAL';
                                        $lastApprove = $approve;
                                        $approval = true;
                                        $reject = false;
                                        $waiting = false;
                                    }
                                    elseif ($approve == $this->const['DOCUMENT_EXECUTE'])
                                    {
                                        $approve = 'EXECUTED';
                                        $approval = true;
                                        $reject = false;
                                        $waiting = false;
                                    }
                                    $comment = $val2['comment'];
                                    $comment = str_replace("\r"," ",$comment);
                                    $comment = str_replace("\n"," ",$comment);
                                    $comment = str_replace("\"","",$comment);
                                    $date = $val2['date'];
                                    if ($fetch2['is_end'] == 1)
                                    {
                                        $icon = "ender";
                                        $isEnd = true;
                                    }
                                    else
                                    {
                                        $icon = "next";
                                        $isEnd = false;
                                    }
                                    $approve = array(
                                        "approve" => $approval,
                                        "reject" => $reject,
                                        "waiting" => $waiting,
                                        "msg" => $approve
                                    );
                                    $result[] = array(
                                        "id" => 'docs-'.md5(date('d-m-y-H-i-s-u') . rand(0,1000)),
                                        "trano" => $val2['item_id'],
                                        "workflow_item_id" => $val2['workflow_item_id'],
                                        "workflow_structure_id" => $val2['workflow_structure_id'],
                                        "workflow_id" => $val2['workflow_id'],
                                        "cls" => $icon,
                                        "isPerson" => true,
                                        "isDocs" => true,
                                        "prj_kode" => "&nbsp;",
                                        "leaf" => true,
                                        "username" => $name,
                                        "mail" => $mail,
                                        "date" => date('d-m-Y H:i:s',strtotime($date)),
                                        "comment" => $comment,
                                        "invalid" => $stat,
                                        "approve" => $approve,
                                        "start" => false,
                                        "end" => $isEnd
                                    );
                                }
                            }
                            else
                            {
                                $name = $this->workflow->getUserByRoleId($fetch2['master_role_id']);
                                $mail = $this->workflow->getUserByRoleId($fetch2['master_role_id'],true);
                                if ($docsDate != '')
                                {

                                    $lastDate = new DateTime(date('d-m-Y H:i:s',strtotime($docsDate)));
                                    $nowDate = new DateTime(date('d-m-Y H:i:s'));
                                    $diff = $lastDate->diff($nowDate);
                                    if ($lastApprove == 'SUBMITTED')
                                        $stat = "Has been " . $diff->format('%d days') . " since submitted.";
                                    else if ($lastApprove == 'APPROVED')
                                        $stat = "Has been " . $diff->format('%d days') . " since last approval.";
                                    else if ($lastApprove == 'RE-SUBMITTED')
                                        $stat = "Has been " . $diff->format('%d days') . " since re-submitted.";
                                    else if ($lastApprove == 'FINAL APPROVED')
                                        $stat = "Has been " . $diff->format('%d days') . " since last final approval.";
                                }
                                if ($fetch2['is_end'] == 1)
                                    {
                                        $icon = "ender";
                                        $isEnd = true;
                                    }
                                    else
                                    {
                                        $icon = "next";
                                        $isEnd = false;
                                    }
                                $approve = array(
                                        "approve" => false,
                                        "reject" => false,
                                        "waiting" => true,
                                        "msg" => 'WAITING FOR APPROVAL'
                                );
                                $result[] = array(
                                        "id" => 'nodocs-' . md5(date('d-m-y-H-i-s-u') . rand(0,1000)),
                                        "workflow_item_id" => $fetch2['workflow_item_id'],
                                        "workflow_structure_id" => $fetch2['workflow_structure_id'],
                                        "workflow_id" => $fetch2['workflow_id'],
                                        "trano" => $fetch['item_id'],
                                        "isPerson" => true,
                                        "isDocs" => true,
                                        "cls" => $icon,
                                        "leaf" => true,
                                        "item_id" => $item_id,
                                        "prj_kode" => "&nbsp;",
                                        "username" => $name,
                                        "mail" => $mail['mail'][0],
                                        "date" => '0000-00-00 00:00:00',
                                        "comment" => $stat,
                                        "approve" => $approve,
                                        "invalid" => $stat,
                                        "start" => false,
                                        "end" => $isEnd
                                    );
                                $id++;
                            }
                            $end = $fetch2['is_end'];
                        }
                        else
                        {
                            $end = 1;
                        }
                    }
                }
           }
        }
            $json = Zend_Json::encode($result);
	        //result encoded in JSON
			$json = str_replace("\\","",$json);
	        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
	        $this->getResponse()->setBody($json);
    }

    
    public function listprocessdocumentAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$request = $this->getRequest();

        $userID = $request->getParam('userid');
        $myWorkflow = $this->workflow->getAllMyWorkflow($userID);
    	$result = array();
    	$docs = array();
    	$jumlah = 0;
    	
    	
    	foreach ($myWorkflow as $key => $val)
    	{
    		$temp = $this->workflow->getDocumentToProcess($val['workflow_id']);
    		if ($temp)
    		{
    			if (is_array($temp))
    			{
    				foreach ($temp as $keytemp => $valTemp)
    				{
//    					$tempdocs = $this->workflow->convertDocumentFromWorkflow($valTemp['workflow_trans_id']);
						$valTemp['doc_type'] = $this->workflow->getDocumentType($valTemp['workflow_trans_id']);
						$workflowDocs = $this->workflow->getDocumentFlow($valTemp['workflow_trans_id']);
						if ($workflowDocs)	
						{
							foreach ($workflowDocs as $key => $val)
							{
								$workflowDocs[$key] = $this->workflow->getUserByRoleId($val);
							}
							$valTemp['workflow_doc'] = $workflowDocs;
						}
    					$docs[] = $valTemp;
    				}
    			}
    		}
    	}
    	$return['posts'] = $docs;
    	$return['count'] = count($docs);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    	
    }
    
    public function dashboardAction()
    {
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	
    	$this->view->sitKode = $sitKode;
    	$this->view->prjKode = $prjKode;
    			
    }
    
    public function getboq3summarybyoneAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	$workid = $this->getRequest()->getParam("workid");
    	$kodeBrg = $this->getRequest()->getParam("kode_brg");
    	$trano = $this->getRequest()->getParam("trano");
    	
    	if ($prjKode != '')
    	{
	    	$boq3 = $this->budget->getBoq3ByOne($prjKode,$sitKode,$workid,$kodeBrg);
    		$boq3['uom'] = $this->quantity->getUOMByProductID($boq3['kode_brg']);
    		$boq3['nama_brg'] = str_replace("\"","'",$boq3['nama_brg']);

            foreach($boq3 as $key => $val)
            {                   
                    $boq3[$key] = str_replace("\"\"","",$boq3[$key]);
                    $boq3[$key] = str_replace("\""," inch",$boq3[$key]);
                    $boq3[$key] = str_replace("'"," inch",$boq3[$key]);
            }

			if ($boq3['val_kode'] == 'IDR')
			{
    			$boq3['price'] = $boq3['hargaIDR'];
    			$boq3['totalPrice'] = $boq3['totalIDR'];
			}
    		else
    		{
    			$boq3['price'] = $boq3['hargaUSD'];
 	   			$boq3['totalPrice'] = $boq3['totalHargaUSD'];
    		}
    		
    		$newQtyPR = 0;
    		$newPriceIDRPR = 0;
    		$newPriceUSDPR = 0;
    		$pr = $this->quantity->getPrQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);
    		if ($pr != '')
    		{
    			if ($trano != '')
    			{
    				$isPO = $this->trans->isPRExecuted($trano);
    				$thisPR = $this->quantity->getPrQuantityByTrano($trano);
    				if ($isPO == '')
    				{
    					$newQtyPR = $thisPR['qty'];
		    			$newPriceIDRPR = $thisPR['totalIDR'];
		    			$newPriceUSDPR = $thisPR['totalHargaUSD'];
    				}
    			}
    			$boq3['totalPR'] = $pr['qty'] - $newQtyPR;
    			if ($boq3['val_kode'] == 'IDR')
    				$boq3['totalPricePR'] = $pr['totalIDR'] - $newPriceIDRPR;
    			else
    				$boq3['totalPricePR'] = $pr['totalHargaUSD'] - $newPriceUSDPR;
    			$boq3['balancePR'] = $boq3['qty'] - $pr['qty'] - $newQtyPR;
    		}
    		else
    		{
    			$boq3['totalPR'] = 0;
    			$boq3['balancePR'] = 0;
    			$boq3['totalPricePR'] = 0;
    		}
            if(in_array($boq3['workid'],$this->miscWorkid))
            {

                $pod = $this->quantity->getPoQuantity($prjKode,$sitKode,$boq3['workid']);
                $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$boq3['workid']);
                $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$boq3['workid']);
                
                if ($pod != '' )
                {
                        $boq3['totalqtyPOD'] = $pod['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $boq3['totalPOD'] = $pod['totalIDR'];
                        else
                            $boq3['totalPOD'] = $pod['totalHargaUSD'];
                }
                else
                {
                        $boq3['totalqtyPOD'] = 0;
                        $boq3['totalPOD'] = 0;
                }
                if ($arf != '' )
                {
                        $boq3['totalqtyARF'] = $arf['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $boq3['totalInARF'] = $arf['totalIDR'];
                        else
                            $boq3['totalInARF'] = $arf['totalHargaUSD'];
                }
                else
                {
                        $boq3['totalqtyARF'] = 0;
                        $boq3['totalARF'] = 0;
                }
                if ($asfcancel != '' )
                {
                        $boq3['totalqtyASFCancel'] = $asfcancel['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $boq3['totalASFCancel'] = $asfcancel['totalIDR'];
                        else
                            $boq3['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                }
                else
                {
                        $boq3['totalqtyASFCancel'] = 0;
                        $boq3['totalASFCancel'] = 0;
                }

//                            $totalpoarf = ($boq3[$key]['totalPOD'] +  $boq3[$key]['totalInARF'] - $boq3[$key]['totalASFCancel']);
                $boq3['totalPriceMIP'] = $boq3['totalPOD'] +  $boq3['totalInARF'] - $boq3['totalASFCancel'];
                if ($boq3['totalPricePR'] < $boq3['totalPriceMIP'])
                        $boq3['totalPricePR'] = $boq3['totalPriceMIP'];

            }
            else
            {
                $newPriceIDRPR = 0;
                $newPriceUSDPR = 0;
                $pod = $this->quantity->getPoQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);
                $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);
                $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);

                if ($pod != '' )
                {
                        $boq3['totalqtyPOD'] = $pod['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $boq3['totalPOD'] = $pod['totalIDR'];
                        else
                            $boq3['totalPOD'] = $pod['totalHargaUSD'];
                }
                else
                {
                        $boq3['totalqtyPOD'] = 0;
                        $boq3['totalPOD'] = 0;
                }
                if ($arf != '' )
                {
                        $boq3['totalqtyARF'] = $arf['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $boq3['totalInARF'] = $arf['totalIDR'];
                        else
                            $boq3['totalInARF'] = $arf['totalHargaUSD'];
                }
                else
                {
                        $boq3['totalqtyARF'] = 0;
                        $boq3['totalARF'] = 0;
                }
                if ($asfcancel != '' )
                {
                        $boq3['totalqtyASFCancel'] = $asfcancel['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $boq3['totalASFCancel'] = $asfcancel['totalIDR'];
                        else
                            $boq3['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                }
                else
                {
                        $boq3['totalqtyASFCancel'] = 0;
                        $boq3['totalASFCancel'] = 0;
                }
                $boq3['totalPriceMIP'] = $boq3['totalPOD'] + $boq3['totalInARF'] - $boq3['totalASFCancel'];
                if ($boq3['totalPriceMIP'] > 0)
                {
//                                $boq3[$key]['totalPricePR'] = $boq3[$key]['totalPricePR'] + $boq3[$key]['totalPriceMIP'];
                    $mipQty = $boq3['qty'] * (1 - (($boq3['totalPrice'] - $boq3['totalPriceMIP']) / $boq3['totalPrice']));
                    if ($boq3['totalPR'] < $mipQty)
                    {
                        $boq3['totalPR'] = $mipQty;
                        if ($boq3['totalPricePR'] < $boq3['totalPriceMIP'])
                        $boq3['totalPricePR'] = $boq3['totalPriceMIP'];
                    }
                }
            }
    	}
    	
    	$return['posts'] = $boq3;
    	$return['count'] = 1;
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    	
    }

    public function getboq3summaryAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	$sumPR = $this->getRequest()->getParam("sumpr");
    	$trano = $this->getRequest()->getParam("trano");
    	
    	$isPR = $this->getRequest()->getParam("pr");
    	$current = 0;

    	if ($prjKode != '')
    	{
	    	$boq3 = $this->budget->getBoq3('all-current',$prjKode,$sitKode);
	    	$i = 1;
	    	$limit = count($boq3);
	    	if ($isPR)
	    	{
	    		$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
		        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
	    	}
	    	else    		
		    	$offset = 0;
		    	
		    $result = array();
	    	foreach($boq3 as $key => $val)
	    	{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $boq3[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $boq3[$key][$key2] = str_replace("\""," inch",$boq3[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $boq3[$key][$key2] = str_replace("'"," inch",$boq3[$key][$key2]);
                }

	    		$boq3[$key]['id'] = $i;
	    		$boq3[$key]['uom'] = $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']);
	    		$boq3[$key]['nama_brg'] = str_replace("\"","'",$boq3[$key]['nama_brg']);
				if ($boq3[$key]['val_kode'] == 'IDR')
				{
	    			$boq3[$key]['price'] = $boq3[$key]['hargaIDR'];
	    			$boq3[$key]['totalPrice'] = $boq3[$key]['totalIDR'];
				}
	    		else
	    		{
	    			$boq3[$key]['price'] = $boq3[$key]['hargaUSD'];
	 	   			$boq3[$key]['totalPrice'] = $boq3[$key]['totalHargaUSD'];
	    		}
	    		if ($current < ($limit + $offset) && $current >= $offset)
	    		{
		    		if ($sumPR)
		    		{
                        if(in_array($boq3[$key]['workid'],$this->miscWorkid))
                        {

                            $pod = $this->quantity->getPoQuantity($prjKode,$sitKode,$boq3[$key]['workid']);
                            $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$boq3[$key]['workid']);
                            $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$boq3[$key]['workid']);
                            $pr = $this->quantity->getPrQuantity($prjKode,$sitKode,$boq3[$key]['workid']);
                            if ($pr != '')
                            {
                                if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['totalPricePR'] = $pr['totalIDR'];
                                else
                                    $boq3[$key]['totalPricePR'] = $pr['totalHargaUSD'];
                            }
                            else
                                $boq3[$key]['totalPricePR'] = 0;
                            if ($pod != '' )
                            {
                                    $boq3[$key]['totalqtyPOD'] = $pod['qty'];
                                    if ($boq3[$key]['val_kode'] == 'IDR')
                                        $boq3[$key]['totalPOD'] = $pod['totalIDR'];
                                    else
                                        $boq3[$key]['totalPOD'] = $pod['totalHargaUSD'];
                            }
                            else
                            {
                                    $boq3[$key]['totalqtyPOD'] = 0;
                                    $boq3[$key]['totalPOD'] = 0;
                            }
                            if ($arf != '' )
                            {
                                    $boq3[$key]['totalqtyARF'] = $arf['qty'];
                                    if ($boq3[$key]['val_kode'] == 'IDR')
                                        $boq3[$key]['totalInARF'] = $arf['totalIDR'];
                                    else
                                        $boq3[$key]['totalInARF'] = $arf['totalHargaUSD'];
                            }
                            else
                            {
                                    $boq3[$key]['totalqtyARF'] = 0;
                                    $boq3[$key]['totalARF'] = 0;
                            }
                            if ($asfcancel != '' )
                            {
                                    $boq3[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                                    if ($boq3[$key]['val_kode'] == 'IDR')
                                        $boq3[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                                    else
                                        $boq3[$key]['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                            }
                            else
                            {
                                    $boq3[$key]['totalqtyASFCancel'] = 0;
                                    $boq3[$key]['totalASFCancel'] = 0;
                            }

//                            $totalpoarf = ($boq3[$key]['totalPOD'] +  $boq3[$key]['totalInARF'] - $boq3[$key]['totalASFCancel']);
                            $boq3[$key]['totalPriceMIP'] = $boq3[$key]['totalPOD'] +  $boq3[$key]['totalInARF'] - $boq3[$key]['totalASFCancel'];
                            if ($boq3[$key]['totalPricePR'] < $boq3[$key]['totalPriceMIP'])
                                    $boq3[$key]['totalPricePR'] = $boq3[$key]['totalPriceMIP'];
                            
                        }
                        else
                        {
                            $newQtyPR = 0;
                            $newPriceIDRPR = 0;
                            $newPriceUSDPR = 0;
                            $pod = $this->quantity->getPoQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                            $pr = $this->quantity->getPrQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                            $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                            $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                            if ($pr != '')
                            {
                                if ($trano != '')
                                {
                                    $newPR = $this->quantity->getPrQuantityByTrano($trano);
                                    $isPO = $this->trans->isPRExecuted($trano);
                                    if ($isPO == '')
                                    {
                                        $newQtyPR = $newPR['qty'];
                                        $newPriceIDRPR = $newPR['totalIDR'];
                                        $newPriceUSDPR = $newPR['totalHargaUSD'];
                                    }
                                }
                                $boq3[$key]['totalPR'] = $pr['qty'] - $newQtyPR;
                                if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['totalPricePR'] = $pr['totalIDR'] - $newPriceIDRPR;
                                else
                                    $boq3[$key]['totalPricePR'] = $pr['totalHargaUSD'] - $newPriceUSDPR;
                                $boq3[$key]['balancePR'] = $boq3[$key]['qty'] - $boq3[$key]['totalPR'];
                            }
                            else
                            {
                                $boq3[$key]['totalPR'] = 0;
                                $boq3[$key]['balancePR'] = 0;
                                $boq3[$key]['totalPricePR'] = 0;
                            }
                            if ($pod != '' )
                            {
                                    $boq3[$key]['totalqtyPOD'] = $pod['qty'];
                                    if ($boq3[$key]['val_kode'] == 'IDR')
                                        $boq3[$key]['totalPOD'] = $pod['totalIDR'];
                                    else
                                        $boq3[$key]['totalPOD'] = $pod['totalHargaUSD'];
                            }
                            else
                            {
                                    $boq3[$key]['totalqtyPOD'] = 0;
                                    $boq3[$key]['totalPOD'] = 0;
                            }
                            if ($arf != '' )
                            {
                                    $boq3[$key]['totalqtyARF'] = $arf['qty'];
                                    if ($boq3[$key]['val_kode'] == 'IDR')
                                        $boq3[$key]['totalInARF'] = $arf['totalIDR'];
                                    else
                                        $boq3[$key]['totalInARF'] = $arf['totalHargaUSD'];
                            }
                            else
                            {
                                    $boq3[$key]['totalqtyARF'] = 0;
                                    $boq3[$key]['totalARF'] = 0;
                            }
                            if ($asfcancel != '' )
                            {
                                    $boq3[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                                    if ($boq3[$key]['val_kode'] == 'IDR')
                                        $boq3[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                                    else
                                        $boq3[$key]['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                            }
                            else
                            {
                                    $boq3[$key]['totalqtyASFCancel'] = 0;
                                    $boq3[$key]['totalASFCancel'] = 0;
                            }
                            $boq3[$key]['totalPriceMIP'] = $boq3[$key]['totalPOD'] + $boq3[$key]['totalInARF'] - $boq3[$key]['totalASFCancel'];
                            if ($boq3[$key]['totalPriceMIP'] > 0)
                            {
//                                $boq3[$key]['totalPricePR'] = $boq3[$key]['totalPricePR'] + $boq3[$key]['totalPriceMIP'];
                                $mipQty = $boq3[$key]['qty'] * (1 - (($boq3[$key]['totalPrice'] - $boq3[$key]['totalPriceMIP']) / $boq3[$key]['totalPrice']));
                                if ($boq3[$key]['totalPR'] < $mipQty)
                                {
                                    $boq3[$key]['totalPR'] = $mipQty;
                                    if ($boq3[$key]['totalPricePR'] < $boq3[$key]['totalPriceMIP'])
                                    $boq3[$key]['totalPricePR'] = $boq3[$key]['totalPriceMIP'];
                                }
                            }
		    		    }
                    }
		 
		    		$result[] = $boq3[$key];
	    		}
		    	$current++;
	    		$i++;
	    	}
    	}
    	$return['posts'] = $result;
    	$return['count'] = count($boq3);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getbudgetsummaryAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	
    	$trano = $this->getRequest()->getParam("trano");

    	$current = 0;

    	if ($prjKode != '')
    	{
	    	$budget = $this->budget->getBudgetOverhead($prjKode,$sitKode);
           
	    	$i = 1;
	    	$limit = count($budget);

	    		$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
		        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;


		    $result = array();
	    	foreach($budget as $key => $val)
	    	{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $budget[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $budget[$key][$key2] = str_replace("\""," inch",$budget[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $budget[$key][$key2] = str_replace("'"," inch",$budget[$key][$key2]);
                }

	    		$budget[$key]['id'] = $i;

                if ($budget[$key]['val_kode'] == 'IDR')
				{

	    			$budget[$key]['totalPrice'] = $budget[$key]['totalIDR'];
				}
	    		else
	    		{

	 	   			$budget[$key]['totalPrice'] = $budget[$key]['totalHargaUSD'];
	    		}
	    		    	    
	    		if ($current < ($limit + $offset) && $current >= $offset)
	    		{
		    			$pr = $this->quantity->getPrOverheadQuantity($prjKode,$sitKode,$budget[$key]['budgetid']);
                        if ($pr != '')
                        {

                            $budget[$key]['totalPRraw'] = $pr['qty'];

                            $budget[$key]['totalPricePRraw'] = $pr['total'];
                           
                        }

                        else
	    				{
	    					$budget[$key]['totalPRraw'] = 0;
	    					
	    					$budget[$key]['totalPricePRraw'] = 0;
	    				}

                        $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$budget[$key]['budgetid']);
                        if ($arf != '')
                        {

                            $budget[$key]['totalARF'] = $arf['qty'];

                            if ($budget[$key]['val_kode'] == 'IDR')
                            {

                                $budget[$key]['totalPriceARF'] = $arf['totalIDR'];
                            }
                            else
                            {

                                $budget[$key]['totalPriceARF'] = $arf['totalHargaUSD'];
                            }
                            
                        }

                        else
	    				{
	    					$budget[$key]['totalARF'] = 0;

	    					$budget[$key]['totalPriceARF'] = 0;
	    				}
                    $totalQtyPRARF = $budget[$key]['totalPRraw']+ $budget[$key]['totalARF'];
                    $totalPricePRARF = $budget[$key]['totalPricePRraw']+ $budget[$key]['totalPriceARF'];

                    $budget[$key]['totalPR'] = $totalQtyPRARF;
                    $budget[$key]['totalPricePR'] = $totalPricePRARF;
                        
		    		$result[] = $budget[$key];
	    		}

		    	$current++;
	    		$i++;
	    	}
    	}
    	$return['posts'] = $result;
    	$return['count'] = count($budget);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getboq3summaryforlogisticAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	

    	$paging = $this->getRequest()->getParam("paging");
    	$current = 0;

    	if ($prjKode != '')
    	{
	    	$boq3 = $this->budget->getBoq3('all-current',$prjKode,$sitKode);
	    	$i = 1;
	    	$limit = count($boq3);
	    	if ($paging)
	    	{
	    		$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
		        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
	    	}
	    	else
		    	$offset = 0;

		    $result = array();
	    	foreach($boq3 as $key => $val)
	    	{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $boq3[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $boq3[$key][$key2] = str_replace("\""," inch",$boq3[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $boq3[$key][$key2] = str_replace("'"," inch",$boq3[$key][$key2]);
                }

	    		$boq3[$key]['id'] = $i;
	    		$boq3[$key]['uom'] = $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']);
	    		$boq3[$key]['nama_brg'] = str_replace("\"","'",$boq3[$key]['nama_brg']);

		    		$result[] = $boq3[$key];
            }
		    	$current++;
	    		$i++;
        }
    	
    	$return['posts'] = $result;
    	$return['count'] = count($boq3);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function  getboq3summaryforafeAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	$valKode = $this->getRequest()->getParam("val_kode");


    	$paging = $this->getRequest()->getParam("paging");
    	$current = 0;

    	if ($prjKode != '')
    	{
	    	$boq3 = $this->budget->getBoq3('all-current',$prjKode,$sitKode);
	    	$i = 1;
	    	$limit = count($boq3);
	    	if ($paging)
	    	{
	    		$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
		        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
	    	}
	    	else
		    	$offset = 0;

		    $result = array();
            $jumHapus = 0;
	    	foreach($boq3 as $key => $val)
	    	{
                if ($valKode != '')
                {
                    if ($boq3[$key]['val_kode'] != $valKode)
                    {
                        $jumHapus++;
                        continue;
                    }
                }
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $boq3[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $boq3[$key][$key2] = str_replace("\""," inch",$boq3[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $boq3[$key][$key2] = str_replace("'"," inch",$boq3[$key][$key2]);
                }

	    		$boq3[$key]['id'] = $i;
	    		$boq3[$key]['uom'] = $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']);
                $cusorder = $this->trans->getPOCustomer($prjKode,$sitKode);
                $boq3[$key]['pocustomer'] = $cusorder['pocustomer'];
                if($boq3[$key]['val_kode'] == 'IDR')
                    $boq3[$key]['totalpocustomer'] = intval($cusorder['total']);
                else
                    $boq3[$key]['totalpocustomer'] = intval($cusorder['totalusd']);
                
	    		$boq3[$key]['nama_brg'] = str_replace("\"","'",$boq3[$key]['nama_brg']);

                if ($current < ($limit + $offset) && $current >= $offset)
	    		{
                $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                if ($po == '')
                {
                    $po['qty'] = 0;
                    $po['totalIDR'] = 0;
                    $po['totalHargaUSD'] = 0;
                }
                $asfdd = $this->quantity->getAsfddQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                 if ($asfdd == '')
                {
                    $asfdd['qty'] = 0;
                    $asfdd['totalIDR'] = 0;
                    $asfdd['totalHargaUSD'] = 0;
                }
                $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                if ($arf == '')
                {
                    $arf['qty'] = 0;
                    $arf['totalIDR'] = 0;
                    $arf['totalHargaUSD'] = 0;
                }
                $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                 if ($asfcancel == '')
                {
                    $asfcancel['qty'] = 0;
                    $asfcancel['totalIDR'] = 0;
                    $asfcancel['totalHargaUSD'] = 0;
                }
                $pr = $this->quantity->getPrQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                 if ($pr == '')
                {
                    $pr['qty'] = 0;
                    $pr['totalIDR'] = 0;
                    $pr['totalHargaUSD'] = 0;
                }
                 $ican = $this->quantity->getIcanQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                 if ($ican == '')
                {
                    $ican['qty'] = 0;
                    $ican['totalIDR'] = 0;
                    $ican['totalHargaUSD'] = 0;
                }

                $pmeal = $this->quantity->getPmealQuantity($prjKode,$sitKode,$boq3[$key]['kode_brg']);
                 if ($pmeal == '')
                {
                    $pmeal['qty'] = 0;
                    
                }
                    
//                $do= $this->quantity->getDoQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
//                if ($do == '')
//                {
//                    $do['qty'] = 0;
//
//                }

                $boq3[$key]['totalPurchase'] = floatval($po['qty']) + floatval($asfdd['qty']);
                $boq3[$key]['totalPR'] =  floatval($pmeal['qty']) + floatval($pr['qty']) + (floatval($arf['qty'])-floatval($asfcancel['qty']) - floatval($ican['qty']));
		    	if ($boq3[$key]['val_kode'] == 'IDR')
                {
                    $boq3[$key]['price'] = $boq3[$key]['hargaIDR'];
                    $boq3[$key]['totalPricePurchase'] = floatval($po['totalIDR']) + floatval($asfdd['totalIDR']);
                    $boq3[$key]['totalPricePR'] = floatval($pr['totalIDR'])+(floatval($arf['totalIDR'])-floatval($asfcancel['totalIDR']));
                }
                else
                {
                    $boq3[$key]['price'] = $boq3[$key]['hargaUSD'];
                    $boq3[$key]['totalPricePurchase'] = floatval($po['totalHargaUSD']) + floatval($asfdd['totalHargaUSD']);
                    $boq3[$key]['totalPricePR'] = floatval($pr['totalHargaUSD'])+(floatval($arf['totalHargaUSD'])-floatval($asfcancel['totalHargaUSD']));
                }
                    $result[] = $boq3[$key];
                }
                $current++;
                $i++;
            }

        }

    	$return['posts'] = $result;
    	$return['count'] = count($boq3) - $jumHapus;
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getboq3summaryforafebyoneAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	$workid = $this->getRequest()->getParam("workid");
    	$kodeBrg = $this->getRequest()->getParam("kode_brg");
    	$valKode = $this->getRequest()->getParam("val_kode");

        $boq3 = $this->budget->getBoq3ByOne($prjKode,$sitKode,$workid,$kodeBrg);
        if (!$boq3)
        {
            $return['posts'] = array();
            $return['count'] = 0;
            $json = Zend_Json::encode($return);
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
            return;
        }
            $i = 1;
            foreach($boq3 as $key => $val)
	    	{
                if ($valKode != '')
                {
                    if ($boq3['val_kode'] != $valKode)
                        continue;
                }
                    if ($val == "\"\"")
                        $boq3[$key] = '';
                    if (strpos($val,"\"")!== false)
                        $boq3[$key] = str_replace("\""," inch",$boq3[$key]);
                    if (strpos($val,"'")!== false)
                        $boq3[$key] = str_replace("'"," inch",$boq3[$key]);

            }
	    		$boq3['id'] = $i;
	    		$boq3['uom'] = $this->quantity->getUOMByProductID($boq3['kode_brg']);
                $cusorder = $this->trans->getPOCustomer($prjKode,$sitKode);
                $boq3['pocustomer'] = $cusorder['pocustomer'];
                if($boq3['val_kode'] == 'IDR')
                    $boq3['totalpocustomer'] = $cusorder['total'];
                else
                    $boq3['totalpocustomer'] = $cusorder['totalusd'];

	    		$boq3['nama_brg'] = str_replace("\"","'",$boq3['nama_brg']);
                $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);
                if ($po == '')
                {
                    $po['qty'] = 0;
                    $po['totalIDR'] = 0;
                    $po['totalUSD'] = 0;
                }
                $asfdd = $this->quantity->getAsfddQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);
                 if ($asfdd == '')
                {
                    $asfdd['qty'] = 0;
                    $asfdd['totalIDR'] = 0;
                    $asfdd['totalUSD'] = 0;
                }
                $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);
                if ($arf == '')
                {
                    $arf['qty'] = 0;
                    $arf['totalIDR'] = 0;
                    $arf['totalUSD'] = 0;
                }
                $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);
                 if ($asfcancel == '')
                {
                    $asfcancel['qty'] = 0;
                    $asfcancel['totalIDR'] = 0;
                    $asfcancel['totalUSD'] = 0;
                }
                $pr = $this->quantity->getPrQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);
                 if ($pr == '')
                {
                    $pr['qty'] = 0;
                    $pr['totalIDR'] = 0;
                    $pr['totalUSD'] = 0;
                }

                $boq3['totalPurchase'] = intval($po['qty']) + intval($asfdd['qty']);
                $boq3['totalPR'] = intval($pr['qty']) + (intval($arf['qty'])-intval($asfcancel['qty']));
		    	if ($boq3['val_kode'] == 'IDR')
                {
                    $boq3['price'] = $boq3['hargaIDR'];
                    $boq3['totalPricePurchase'] = intval($po['totalIDR']) + intval($asfdd['totalIDR']);
                    $boq3['totalPricePR'] = intval($pr['totalIDR'])+(intval($arf['totalIDR'])-intval($asfcancel['totalIDR']));
                }
                else
                {
                    $boq3['price'] = $boq3['hargaUSD'];
                    $boq3['totalPricePurchase'] = intval($po['totalUSD']) + intval($asfdd['totalUSD']);
                    $boq3['totalPricePR'] = intval($pr['totalUSD'])+(intval($arf['totalUSD'])-intval($asfcancel['totalUSD']));
                }
                $result[] = $boq3;


    	$return['posts'] = $result;
    	$return['count'] = count($boq3);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
//        var_dump($json);die;
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

     public function getboq3summaryforpmealAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");


    	$paging = $this->getRequest()->getParam("paging");
    	$current = 0;

    	if ($prjKode != '')
    	{
	    	$boq3 = $this->budget->getBoq3('all-pmeal',$prjKode,$sitKode);

	    	$i = 1;
	    	$limit = count($boq3);
	    	if ($paging)
	    	{
	    		$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
		        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
	    	}
	    	else
		    	$offset = 0;

		    $result = array();
	    	foreach($boq3 as $key => $val)
	    	{
                foreach($val as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $boq3[$key][$key2] = '';
                    if (strpos($val2,"\"")!== false)
                        $boq3[$key][$key2] = str_replace("\""," inch",$boq3[$key][$key2]);
                    if (strpos($val2,"'")!== false)
                        $boq3[$key][$key2] = str_replace("'"," inch",$boq3[$key][$key2]);
                }

	    		$boq3[$key]['id'] = $i;
	    		$boq3[$key]['uom'] = $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']);
	    		$boq3[$key]['nama_brg'] = str_replace("\"","'",$boq3[$key]['nama_brg']);
                if ($boq3[$key]['val_kode'] == 'IDR')
				{
	    			$boq3[$key]['price'] = $boq3[$key]['hargaIDR'];
	    			$boq3[$key]['totalPrice'] = $boq3[$key]['totalIDR'];
				}
	    		else
	    		{
	    			$boq3[$key]['price'] = $boq3[$key]['hargaUSD'];
	 	   			$boq3[$key]['totalPrice'] = $boq3[$key]['totalHargaUSD'];
	    		}
                
				$pmeal = $this->quantity->getBoq3PmealQuantity($boq3[$key]['trano'],$prjKode,$sitKode,$boq3[$key]['kode_brg']);
                if ($pmeal != '')
                {
                    $boq3[$key]['totalPmeal'] = $pmeal['qty'];
                    $boq3[$key]['totalPricePmeal'] = $pmeal['totalHarga'];
                    $boq3[$key]['balancePmeal'] = $boq3[$key]['qty'] - $pmeal['qty'];
                }
                else
                {
                    $boq3[$key]['totalPmeal'] = 0;
                    $boq3[$key]['balancePmeal'] = 0;
                    $boq3[$key]['totalPricePmeal'] = 0;
                }


		    		$result[] = $boq3[$key];
                    $i++;
            }
		    	$current++;

        }

    	$return['posts'] = $result;
    	$return['count'] = count($boq3);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getboq3summaryforpmealbyoneAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
        $kodeBrg = $this->getRequest()->getParam("kode_brg");
        $trano = $this->getRequest()->getParam("trano");

    	if ($prjKode != '')
    	{
	    	$boq3 = $this->budget->getBoq3PmealByOne($prjKode,$sitKode,$kodeBrg);
    		$boq3['uom'] = $this->quantity->getUOMByProductID($boq3['kode_brg']);
    		$boq3['nama_brg'] = str_replace("\"","",$boq3['nama_brg']);
			if ($boq3['val_kode'] == 'IDR')
			{
    			$boq3['price'] = $boq3['hargaIDR'];
    			$boq3['totalPrice'] = $boq3['totalIDR'];
			}
    		else
    		{
    			$boq3['price'] = $boq3['hargaUSD'];
 	   			$boq3['totalPrice'] = $boq3['totalHargaUSD'];
    		}

    		$pmeal = $this->quantity->getBoq3PmealQuantity($boq3['trano'],$prjKode,$sitKode,$boq3['kode_brg']);
    		if ($pmeal != '')
                {
                    $boq3['totalPmeal'] = $pmeal['qty'];
                    $boq3['totalPricePmeal'] = $pmeal['totalHarga'];
                    $boq3['balancePmeal'] = $boq3['qty'] - $pmeal['qty'];
                }
                else
                {
                    $boq3['totalPmeal'] = 0;
                    $boq3['balancePmeal'] = 0;
                    $boq3['totalPricePmeal'] = 0;
                }

    	}

    	$return['posts'] = $boq3;
    	$return['count'] = 1;
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getboq3arfsummarybyoneAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	$workid = $this->getRequest()->getParam("workid");
    	$kodeBrg = $this->getRequest()->getParam("kode_brg");


        $boq3 = $this->budget->getBoq3ByOne($prjKode,$sitKode,$workid,$kodeBrg);
        if (!$boq3)
        {
            $return['posts'] = array();
            $return['count'] = 0;
            $json = Zend_Json::encode($return);
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
            return;
        }
            $i = 1;
            foreach($boq3 as $key => $val)
	    	{
                
                    if ($val == "\"\"")
                        $boq3[$key] = '';
                    if (strpos($val,"\"")!== false)
                        $boq3[$key] = str_replace("\""," inch",$boq3[$key]);
                    if (strpos($val,"'")!== false)
                        $boq3[$key] = str_replace("'"," inch",$boq3[$key]);

            }
	    		$boq3['id'] = $i;
	    		$boq3['uom'] = $this->quantity->getUOMByProductID($boq3['kode_brg']);
                if ($boq3['val_kode'] == 'IDR')
				{
	    			$boq3['price'] = $boq3['hargaIDR'];
	    			$boq3['totalPrice'] = $boq3['totalIDR'];
				}
	    		else
	    		{
	    			$boq3['price'] = $boq3['hargaUSD'];
	 	   			$boq3['totalPrice'] = $boq3['totalUSD'];
	    		}
                $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);
                $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);

                $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$boq3['workid'],$boq3['kode_brg']);
//                var_dump($po);die;
                if ($po != '' )
                {
                        $boq3['totalqtyPO'] = $po['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $boq3['totalPO'] = $po['totalIDR'];
                        else
                            $boq3['totalPO'] = $po['totalUSD'];
                }
                else
                {
                        $boq3['totalqtyPO'] = 0;
                        $boq3[$key]['totalPO'] = 0;
                }
                if ($arf != '' )
                {
                        $boq3['totalqtyARF'] = $arf['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $boq3['totalARF'] = $arf['totalIDR'];
                        else
                            $boq3['totalARF'] = $arf['totalUSD'];
                }
                else
                {
                        $boq3['totalqtyARF'] = 0;
                        $boq3['totalARF'] = 0;
                }

                if ($asfcancel != '' )
                {
                        $boq3['totalqtyASFCancel'] = $asfcancel['qty'];
                        if ($boq3['val_kode'] == 'IDR')
                            $boq3['totalASFCancel'] = $asfcancel['totalIDR'];
                        else
                            $boq3['totalASFCancel'] = $asfcancel['totalUSD'];
                }
                else
                {
                        $boq3['totalqtyASFCancel'] = 0;
                        $boq3['totalASFCancel'] = 0;
                }
                $totalpoarfasfc = ((intval($boq3['totalPO']) +  intval($boq3['totalARF']) ) -  intval($boq3['totalASFCancel']) ) ;
                $boq3['totalPoArfAsfc'] = intval($totalpoarfasfc);
                $result[] = $boq3;


    	$return['posts'] = $result;
    	$return['count'] = count($boq3);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
//        var_dump($json);die;
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }






    public function getdorsummaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $columnValue = $this->getRequest()->getParam('data');

       $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_prd WHERE trano LIKE \'%' . $columnValue . '%\' ORDER BY trano ASC';
       $fetch = $this->db->query($sql);
       $return = $fetch->fetchAll();

        foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
                if (strpos($val2,"\"")!== false)
                    $return[$key][$key2] = str_replace("\""," inch",$return[$key][$key2]);
                if (strpos($val2,"'")!== false)
                    $return[$key][$key2] = str_replace("'"," inch",$return[$key][$key2]);
            }
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\"","'",$return[$key]['nama_brg']);
//        	if ($return[$key]['val_kode'] == 'IDR')
//				{
//	    			$return[$key]['price'] = $return[$key]['hargaIDR'];
//	    			$return[$key]['totalPrice'] = $return[$key]['totalIDR'];
//				}
//	    		else
//	    		{
//	    			$return[$key]['price'] = $return[$key]['hargaUSD'];
//	 	   			$return[$key]['totalPrice'] = $return[$key]['totalUSD'];
//	    		}

            $dor = $this->quantity->getDorQuantity($return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg'],$columnValue);
            if ($dor != '')
            {
                    $return[$key]['totalDOR'] = $dor['qty'];
                    $return[$key]['balanceDOR'] = $return[$key]['qty'] - $dor['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                        $return[$key]['totalPriceDOR'] = $dor['totalIDR'];
                    else
                        $return[$key]['totalPriceDOR'] = $dor['totalUSD'];
            }
            else
            {
                    $return[$key]['totalDOR'] = 0;
                    $return[$key]['balanceDOR'] = 0;
                    $return[$key]['totalPriceDOR'] = 0;
            }
            $result[] = $return[$key];

        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
            $json = Zend_Json::encode($hasil);

            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
    }

    public function getdordetailAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $workid = $this->getRequest()->getParam('workid');
        $kode_brg = $this->getRequest()->getParam('kode_brg');
        $trano = $this->getRequest()->getParam('trano');

       $sql = "SELECT * FROM procurement_prd WHERE trano = '$trano' AND prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND workid = '$workid' AND kode_brg = '$kode_brg'";
       $fetch = $this->db->query($sql);
       $return = $fetch->fetch();

        foreach($return as $key => $value)
        {

            if ($value == "\"\"")
            {
                $return[$key]= '';
            }
            $return['uom'] = $this->quantity->getUOMByProductID($return['kode_brg']);

            if (strpos( $return['nama_brg'],"\"")!== false)
                 $return['nama_brg'] = str_replace("\""," inch", $return['nama_brg']);
            if (strpos($return['nama_brg'],"'")!== false)
                 $return['nama_brg'] = str_replace("'"," inch", $return['nama_brg']);
//            $return['nama_brg'] = str_replace("\"","'",$return['nama_brg']);

            $dor = $this->quantity->getDorQuantity($return['prj_kode'],$return['sit_kode'],$return['workid'],$return['kode_brg'],$return['trano']);
            if ($dor != '')
            {
                    $return['totalDOR'] = $dor['qty'];
                    $return['balanceDOR'] = $return['qty'] - $dor['qty'];
            }
            else
            {
                    $return['totalDOR'] = 0;
                    $return['balanceDOR'] = 0;
            }

        }
            $return['success'] = true;
            $json = Zend_Json::encode($return);

            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
    }

    public function getdosummaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $columnValue = $this->getRequest()->getParam('data');

       $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_pointd WHERE trano LIKE \'%' . $columnValue . '%\' ORDER BY trano ASC';
       $fetch = $this->db->query($sql);
       $return = $fetch->fetchAll();

        foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == '""')
                {
                    $return[$key][$key2] = '';
                }
                $return[$key][$key2] = str_replace('"',"inch",$return[$key][$key2]);
                $return[$key][$key2] = str_replace("'","inch",$return[$key][$key2]);
            }
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
//            $return[$key]['nama_brg'] = str_replace("\"","'",$return[$key]['nama_brg']);

            $dor = $this->quantity->getDoQuantity($return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg'],$return[$key]['trano']);
            if ($dor != '')
            {
                    $return[$key]['totalDO'] = $dor['qty'];
            }
            else
            {
                    $return[$key]['totalDO'] = 0;
            }
            $result[] = $return[$key];

        }

        $sql = 'SELECT * FROM procurement_pointh WHERE trano LIKE \'%' . $columnValue . '%\'';
        $fetch = $this->db->query($sql);
        $header = $fetch->fetch();
        if (!$header)
            $header = '';
        else
        {
            foreach($header as $key => $val)
            {
                if ($val == "\"\"")
                {
                    $header[$key] = '';
                }
                $header[$key] = str_replace('"',"inch",$header[$key]);
                $header[$key] = str_replace("'","inch",$header[$key]);
            }
        }
        $hasil['posts'] = $result;
        $hasil['count'] = count($return);
        $hasil['header'] = $header;
        $json = Zend_Json::encode($hasil);

        //result encoded in JSON
        $json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getdordeliverAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('trano');
        $sql = 'SELECT * FROM procurement_pointh WHERE trano LIKE \'%' . $trano . '%\'';
        $fetch = $this->db->query($sql);
        $header = $fetch->fetch();
        if (!$header)
            $header = '';
        else
        {
            foreach($header as $key => $val)
            {
                if ($val == '""')
                {
                    $header[$key] = '';
                }
                $header[$key] = str_replace('"',"inch",$header[$key]);
                $header[$key] = str_replace("'","inch",$header[$key]);
            }
        }
        $hasil['posts'] = $header;
        $json = Zend_Json::encode($hasil);

        //result encoded in JSON
        $json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }    


    public function getdodetailAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $workid = $this->getRequest()->getParam('workid');
        $kode_brg = $this->getRequest()->getParam('kode_brg');
        $trano = $this->getRequest()->getParam('trano');

       $sql = "SELECT * FROM procurement_pointd WHERE trano = '$trano' AND prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND workid = '$workid' AND kode_brg = '$kode_brg'";
       $fetch = $this->db->query($sql);
       $return = $fetch->fetch();

        foreach($return as $key => $value)
        {

            if ($value == "\"\"")
            {
                $return[$key]= '';
            }
            $return['uom'] = $this->quantity->getUOMByProductID($return['kode_brg']);
            $return['nama_brg'] = str_replace("\""," inch",$return['nama_brg']);
            $return['nama_brg'] = str_replace("'"," inch",$return['nama_brg']);

            $dor = $this->quantity->getDoQuantity($return['prj_kode'],$return['sit_kode'],$return['workid'],$return['kode_brg'],$return['trano']);
            if ($dor != '')
            {
                    $return['totalDO'] = $dor['qty'];
                    $return['balanceDO'] = $return['qty'] - $dor['qty'];
            }
            else
            {
                    $return['totalDO'] = 0;
                    $return['balanceDO'] = 0;
            }

        }
            $return['success'] = true;
            $json = Zend_Json::encode($return);

            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
    }

    public function getprsummaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $columnValue = $this->getRequest()->getParam('data');
        
       $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_prd WHERE trano LIKE \'%' . $columnValue . '%\' ORDER BY trano ASC';
       $fetch = $this->db->query($sql);
       $return = $fetch->fetchAll();

        foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);
             $return[$key]['budgettype'] = $this->trans->getBudgetTypeFromPR($return[$key]['trano']);
            $return[$key]['jumlah'] = floatval($return[$key]['qty']) * floatval($return[$key]['harga']);
//        	if ($return[$key]['val_kode'] == 'IDR')
//				{
//	    			$return[$key]['price'] = $return[$key]['hargaIDR'];
//	    			$return[$key]['totalPrice'] = $return[$key]['totalIDR'];
//				}
//	    		else
//	    		{
//	    			$return[$key]['price'] = $return[$key]['hargaUSD'];
//	 	   			$return[$key]['totalPrice'] = $return[$key]['totalUSD'];
//	    		}

            if(in_array($return[$key]['workid'],$this->miscWorkid))
            {
                $boq3 = $this->budget->getBoq3ByOne($return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid']);
                if($boq3)
                {
                    if ($return[$key]['val_kode'] == 'IDR')
                    {

                        $return[$key]['jumlah'] = $boq3['totalIDR'];
                    }
                    else
                    {
                        $return[$key]['jumlah'] = $boq3['totalHargaUSD'];
                    }
                }

                $pod = $this->quantity->getPoQuantity($return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid']);
                $arf = $this->quantity->getArfQuantity($return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid']);
                $asfcancel = $this->quantity->getAsfcancelQuantity($return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid']);
                if ($pod != '' )
                {
                        $return[$key]['totalqtyPOD'] = $pod['qty'];
                        if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalPOD'] = $pod['totalIDR'];
                        else
                            $return[$key]['totalPOD'] = $pod['totalHargaUSD'];
                }
                else
                {
                        $return[$key]['totalqtyPOD'] = 0;
                        $return[$key]['totalPOD'] = 0;
                }
                if ($arf != '' )
                {
                        $return[$key]['totalqtyARF'] = $arf['qty'];
                        if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalInARF'] = $arf['totalIDR'];
                        else
                            $return[$key]['totalInARF'] = $arf['totalHargaUSD'];
                }
                else
                {
                        $return[$key]['totalqtyARF'] = 0;
                        $return[$key]['totalARF'] = 0;
                }
                if ($asfcancel != '' )
                {
                        $return[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                        if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                        else
                            $return[$key]['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                }
                else
                {
                        $boq3[$key]['totalqtyASFCancel'] = 0;
                        $boq3[$key]['totalASFCancel'] = 0;
                }

                $totalpoarf = ($return[$key]['totalPOD'] +  $return[$key]['totalInARF'] - $return[$key]['totalASFCancel']);
                $return[$key]['totalPricePO'] = $totalpoarf;
        
            }
            else
            {
                $po = $this->quantity->getPRPOQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
                if ($po != '' )

                {
                        $return[$key]['totalPO'] = $po['qty'];
                        if ($return[$key]['val_kode'] == 'IDR')
                                $return[$key]['totalPricePO'] = $po['totalIDR'];
                        else
                                $return[$key]['totalPricePO'] = $po['totalHargaUSD'];
                        $return[$key]['balancePO'] = $return[$key]['qty'] - $po['qty'];
                }
                else
                {
                        $return[$key]['totalPO'] = 0;
                        $return[$key]['balancePO'] = 0;
                        $return[$key]['totalPricePO'] = 0;
                }
            }
                                 
            $result[] = $return[$key];
            
        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
            $json = Zend_Json::encode($hasil);
            
            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
    }

	public function getposummaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $columnValue = $this->getRequest()->getParam('data');
        
       if ($columnValue == '')
       	return '';
        
       $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_pod WHERE trano LIKE \'%' . $columnValue . '%\' ORDER BY trano ASC';
       $fetch = $this->db->query($sql);
       $return = $fetch->fetchAll();
       
       $id = 1;
        foreach($return as $key => $value)
        {
        	$return[$key]['id'] = $id;

            //harga == hargaspl, qty == qtyspl, total == totalspl
            if ($return[$key]['hargaspl'] != '')
                $return[$key]['harga'] = $return[$key]['hargaspl'];
            if ($return[$key]['qtyspl'] != '')
                $return[$key]['qty'] = $return[$key]['qtyspl'];
        	$id++;
            foreach($value as $key2 => $val2)
            {
               if (strpos($val2,'""') !== false)
                   $return[$key][$key2] = str_replace("\"\"","",$return[$key][$key2]);
               if (strpos($val2,'"') !== false)
                   $return[$key][$key2] = str_replace("\""," inch",$return[$key][$key2]);
               if (strpos($val2, "\r") !== false)
                   $return[$key][$key2] = str_replace("\r","",$return[$key][$key2]);
               if (strpos($val2, "\n") !== false)
                   $return[$key][$key2] = str_replace("\n","",$return[$key][$key2]);
               if (strpos($val2,"'") !== false)
                   $return[$key][$key2] = str_replace("'","",$return[$key][$key2]);
            }
            if ($value['tglpr'] != '' || $value['tglpr'] != "\"\"")
        	{
                    $return[$key]['tglpr'] = date('d-m-Y',strtotime($value['tglpr']));
            }
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
        	
    		$return[$key]['price'] = $return[$key]['harga'];
    		$return[$key]['totalPrice'] = $return[$key]['harga'] * $return[$key]['qty'];
	    	
            $rpi = $this->quantity->getPoRPIQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg'],$return[$key]['pr_no']);
            if ($rpi != '')
            {
                    $return[$key]['totalRPI'] = $rpi['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalPriceRPI'] = $rpi['totalIDR'];
                    else
                            $return[$key]['totalPriceRPI'] = $rpi['totalHargaUSD'];
                    $return[$key]['balanceRPI'] = $return[$key]['qty'] - $rpi['qty'];
            }
            else
            {
                    $return[$key]['totalRPI'] = 0;
                    $return[$key]['balanceRPI'] = 0;
                    $return[$key]['totalPriceRPI'] = 0;
            }
            $result[] = $return[$key];
            
        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
            $json = Zend_Json::encode($hasil);
            
            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
    }

    public function getrpisummaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $columnValue = $this->getRequest()->getParam('data');

       if ($columnValue == '')
       	return '';

       $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_rpid WHERE trano LIKE \'%' . $columnValue . '%\' ORDER BY trano ASC';
       $fetch = $this->db->query($sql);
       $return = $fetch->fetchAll();

       $id = 1;
        foreach($return as $key => $value)
        {
        	$return[$key]['id'] = $id;
        	$id++;
            foreach($value as $key2 => $val2)
            {
               if (strpos($val2,'""') !== false)
                   $return[$key][$key2] = str_replace("\"\"","",$return[$key][$key2]);
               if (strpos($val2,'"') !== false)
                   $return[$key][$key2] = str_replace("\""," inch",$return[$key][$key2]);
               if (strpos($val2, "\r") !== false)
                   $return[$key][$key2] = str_replace("\r","",$return[$key][$key2]);
               if (strpos($val2, "\n") !== false)
                   $return[$key][$key2] = str_replace("\n","",$return[$key][$key2]);
               if (strpos($val2,"'") !== false)
                   $return[$key][$key2] = str_replace("'","",$return[$key][$key2]);
            }
            if ($value['tglpr'] != '' || $value['tglpr'] != "\"\"")
        	{
                    $return[$key]['tglpr'] = date('d-m-Y',strtotime($value['tglpr']));
            }
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);

    		$return[$key]['price'] = $return[$key]['harga'];
    		$return[$key]['totalPrice'] = $return[$key]['harga'] * $return[$key]['qty'];

            $result[] = $return[$key];

        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
            $json = Zend_Json::encode($hasil);

            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
    }

    public function getpoisuppsummaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $columnValue = $this->getRequest()->getParam('data');
        
       if ($columnValue == '')
       	return '';

       $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_pod WHERE trano LIKE \'%' . $columnValue . '%\' ORDER BY trano ASC';
       $fetch = $this->db->query($sql);
       $return = $fetch->fetchAll();

       $id = 1;
        foreach($return as $key => $value)
        {
        	$return[$key]['id'] = $id;
        	$id++;
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            if ($value['tglpr'] != '' || $value['tglpr'] != "\"\"")
        	{
                    $return[$key]['tglpr'] = date('d-m-Y',strtotime($value['tglpr']));
            }
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);

    		$return[$key]['price'] = $return[$key]['harga'];
    		$return[$key]['totalPrice'] = $return[$key]['harga'] * $return[$key]['qty'];

            $isupp = $this->quantity->getPoIsuppQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
           
            if ($isupp != '')
            {
                    $return[$key]['totalISUPP'] = $isupp['qty'];
                    $return[$key]['totalPriceISUPP'] = $isupp['totalHarga'];                   
                    $return[$key]['balanceISUPP'] = $return[$key]['qty'] - $isupp['qty'];
            }
            else
            {
                    $return[$key]['totalISUPP'] = 0;
                    $return[$key]['balanceISUPP'] = 0;
                    $return[$key]['totalPriceISUPP'] = 0;
            }

            $poh = $this->trans->getPohDetail($return[$key]['trano']);
            if($poh != '')
            {
                $return[$key]['statusppn'] = $poh['statusppn'];
            }

            $result[] = $return[$key];

        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
            $json = Zend_Json::encode($hasil);

            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
    }
    
    public function getbarangdetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	$workid = $this->getRequest()->getParam("workid");
    	$brgKode = $this->getRequest()->getParam("kode_brg");
    	$trano = $this->getRequest()->getParam("trano");
        $prNo = $this->getRequest()->getParam("pr_no");
    	
    	$type = $this->getRequest()->getParam("type");
    	$result = array();
    	if ($type != '')
    	{
	    		switch ($type)
	    		{
	    			case 'PR' :
                        $pr = $this->quantity->getPrQuantity($prjKode,$sitKode,$workid,$brgKode);
                        if ($pr == '')
                        {
                            $pr['qty'] = 0;
                            $pr['totalIDR'] = 0;
                            $pr['totalUSD'] = 0;
                        }
                        $pr2 = $this->quantity->getPrQuantityByTrano($trano);
                        if ($pr2 == '')
                        {
                            $pr2['qty'] = 0;
                            $pr2['totalIDR'] = 0;
                            $pr2['totalUSD'] = 0;

                            $selisihPr['qty'] = $pr['qty'] - $pr2['qty'];
                            $selisihPr['totalIDR'] = $pr['totalIDR'] - $pr2['totalIDR'];
                            $selisihPr['totalUSD'] = $pr['totalUSD'] - $pr2['totalUSD'];
                        }
                        $po = $this->quantity->getPoQuantityByPrno($trano,$prjKode,$sitKode,$workid,$brgKode);
                        if ($po == '')
                        {
                            $po['qty'] = 0;
                            $po['totalIDR'] = 0;
                            $po['totalHargaUSD'] = 0;
                        }
                        $return = array(
                            'success' => true,
                            'type' => 'PR',
                            'qty' => $pr['qty'],
                            'totalIDR' => number_format($pr['totalIDR']),
                            'totalUSD' => number_format($pr['totalHargaUSD']),
                            'qtyPO' => $po['qty'],
                            'totalPOIDR' => number_format($po['totalIDR']),
                            'totalPOUSD' => number_format($po['totalHargaUSD']),
                            'totalPOIDR' => number_format($po['totalIDR']),
                            'totalPOUSD' => number_format($po['totalHargaUSD']),
                            'qtySelisih' => $selisihPr['qty'],
                            'totalSelisihIDR' => number_format($selisihPr['totalIDR']),
                            'totalSelisihUSD' => number_format($selisihPr['totalHargaUSD']),
                        );
			    	break;
                    case 'ARF' :
                        $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid,$brgKode);
                        if ($arf == '')
                        {
                            $arf['qty'] = 0;
                            $arf['totalIDR'] = 0;
                            $arf['totalUSD'] = 0;
                        }
                        $return = array('success' => true,'type' => 'ARF','qty' => $arf['qty'],'totalIDR' => number_format($arf['totalIDR']),'totalUSD' => number_format($arf['totalHargaUSD']));
                    break;
	    			case 'RPI' :
	    				if ($trano != '')
	    				{
	    					$rpi = $this->quantity->getPoRpiQuantity($trano,$prjKode,$sitKode,$workid,$brgKode,$prNo);
					    	if ($rpi == '')
                            {
                                $rpi['qty'] = 0;
                                $rpi['totalIDR'] = 0;
                                $rpi['totalUSD'] = 0;
                            }
                            $return = array('success' => true,'type' => 'RPI-Po_no','qty' => $rpi['qty'],'totalIDR' => $rpi['totalIDR'],'totalUSD' => $rpi['totalHargaUSD']);
    					 }
			    		else
			    		{
                            $rpi = $this->quantity->getRpiQuantity($prjKode,$sitKode,$workid,$brgKode);
                            if ($rpi == '')
                            {
                                $rpi['qty'] = 0;
                                $rpi['totalIDR'] = 0;
                                $rpi['totalUSD'] = 0;
                            }
                            $return = array('success' => true,'type' => 'RPI','qty' => $rpi['qty'],'totalIDR' => number_format($rpi['totalIDR']),'totalUSD' => number_format($rpi['totalHargaUSD']));
                        }
    				break;
	    		}
    		
    	}	
    	else 
    	{
            $pr = $this->quantity->getPrQuantity($prjKode,$sitKode,$workid,$brgKode);
            if ($pr != '')
                $result[] = array('type' => 'PR','qty' => $pr['qty'],'totalIDR' => number_format($pr['totalIDR']),'totalUSD' => number_format($pr['totalHargaUSD']));
            $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$workid,$brgKode);
            if ($po != '')
                $result[] = array('type' => 'PO','qty' => $po['qty'],'totalIDR' => number_format($po['totalIDR']),'totalUSD' => number_format($po['totalHargaUSD']));

            $rpi = $this->quantity->getRpiQuantity($prjKode,$sitKode,$workid,$brgKode);
            if ($rpi != '')
                $result[] = array('type' => 'RPI','qty' => $rpi['qty'],'totalIDR' => number_format($rpi['totalIDR']),'totalUSD' => number_format($rpi['totalHargaUSD']));

            $mdi = $this->quantity->getMdiQuantity($prjKode,$sitKode,$workid,$brgKode);
            if ($mdi != '')
                $result[] = array('type' => 'DO Request','qty' => $mdi['qty'],'totalIDR' => '0','totalUSD' => '0');

            $mdo = $this->quantity->getMdoQuantity($prjKode,$sitKode,$workid,$brgKode);
            if ($mdo != '')
                $result[] = array('type' => 'DO','qty' => $mdo['qty'],'totalIDR' => '0','totalUSD' => '0');
	    	$return['posts'] = $result;
	    	$return['count'] = count($result);
    	}
	    $json = Zend_Json::encode($return);
//        var_dump($result);die();
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function getpodetailAction()
    {	
    	$this->_helper->viewRenderer->setNoRender();
    	$trano = $this->getRequest()->getParam("trano");
        $lastRPI = $this->getRequest()->getParam("lastrpi");
    	
    	$return = $this->trans->getPODetail($trano);
    	$supplier = $this->trans->getSupplierDetail($return['sup_kode']);
    	
    	$bank = $supplier['reknamabank'] . "\n" . $supplier['rekbank'] . "\n" . $supplier['namabank'];



    	$po = $this->quantity->getPoRPIQuantity($trano);

    	if ($po != '')
    		$hasil = array('qty' => $po['qty'],'totalIDR' => $po['totalIDR'],'totalUSD' => $po['totalHargaUSD']);
    	else
            $hasil = array('qty' => 0,'totalIDR' => 0,'totalUSD' => 0);

        if($lastRPI)
        {
            $rpi = $this->quantity->getLastRPI($lastRPI);
             
            if ($rpi != '')
            {
                $hasil['totalIDR'] = $hasil['totalIDR']-$rpi['totalIDR'];
                $hasil['totalUSD'] = $hasil['totalUSD']-$rpi['totalHargaUSD'];
            }
            else
            {
                $hasil['totalIDR'] = $hasil['totalIDR']-0;
                $hasil['totalUSD'] = $hasil['totalUSD']-0;
            }
        }

    	foreach ($return as $key => $val)
    	{
    		if ($val == '""')
    			$return[$key] = '';
    	}
    	$return['RPIinvoice'] = $hasil;
    	$return['bank'] = $bank;

//         var_dump($return);die;
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
//		$json = str_replace("\"\"","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getrpidetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$trano = $this->getRequest()->getParam("trano");
        $lastPaid = $this->getRequest()->getParam("lastpaid");

    	$return = $this->trans->getRPIDetail($trano);
        $payment = $this->trans->getSumRPIPayment($trano);
//        $detail = $this->trans->getRPIdDetail($trano);
        if ($payment['total_bayar'] == '')
            $payment['total_bayar'] = 0;
        if ($payment['val_kode'] == '')
            $payment['val_kode'] = ' ';

    	foreach ($return as $key => $val)
    	{
    		if ($val == '""')
    			$return[$key] = '';
    	}

//        foreach ($detail as $key => $val)
//    	{
//    		if ($val == '""')
//    			$detail[$key] = '';
//    	}

        if($lastPaid)
        {
           $return['RPIPayment'] = $payment['total_bayar'] - $lastPaid;
           $return['payment_balance'] = $return['total'] - $return['RPIPayment'];
        }
        else
        {
            $return['RPIPayment'] = $payment['total_bayar'];
            $return['payment_balance'] = $return['total'] - $payment['total_bayar'];
        }

        $return['RPIPayment_val_kode'] = $payment['val_kode'];
    	$json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getrpihistoryAction()
    {	
    	$this->_helper->viewRenderer->setNoRender();
    	$trano = $this->getRequest()->getParam("trano");
        $rpino = $this->getRequest()->getParam("rpino");
    	
    	$po = $this->quantity->getPoRPIQuantityByRPINo($trano,$rpino);
       
    	$id = 1;
    	foreach($po as $key => $val)
    	{
    		if ($val['totalIDR'] != '' && $val['totalIDR'] > 0)
    			$total = "IDR " . number_format($val['totalIDR']);
    		else
    			$total = "USD " . number_format($val['totalHargaUSD']);
    		$hasil[] = array('id' => $id,'trano' => $val['trano'],'tgl' => $val['tgl'],'qty' => $val['qty'],'totalIDR' => $val['totalIDR'],'totalUSD' => $val['totalHargaUSD'],'total' => $total);
    		$id++;
    	}	
    	$return['posts'] = $hasil;
    	$return['count'] = count($hasil);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);	
    }	

    public function getrpipayedhistoryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
    	$doc_trano = $this->getRequest()->getParam("doc_trano");
        $trano = $this->getRequest()->getParam("exclude");

    	$po = $this->trans->getRPIPayment($doc_trano,$trano);
        
    	$id = 1;
    	foreach($po as $key => $val)
    	{
    		$hasil[] = array('id' => $id,'trano' => $val['trano'],'doc_trano' => $val['doc_trano'],'tgl' => date('Y-m-d',strtotime($val['tgl'])),'total_bayar' => $val['total_bayar'],'val_kode' => $val['val_kode']);
    		$id++;
    	}
    	$return['posts'] = $hasil;
    	$return['count'] = count($hasil);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);	
    }

    public function getarfpayedhistoryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
    	$doc_trano = $this->getRequest()->getParam("doc_trano");
        $trano = $this->getRequest()->getParam("exclude");

    	$arf = $this->trans->getARFPayment($doc_trano,$trano);
    	$id = 1;
    	foreach($arf as $key => $val)
    	{
    		$hasil[] = array('id' => $id,'trano' => $val['trano'],'doc_trano' => $val['doc_trano'],'tgl' => date('Y-m-d',strtotime($val['tgl'])),'total_bayar' => $val['total_bayar'],'val_kode' => $val['val_kode']);
    		$id++;
    	}
    	$return['posts'] = $hasil;
    	$return['count'] = count($hasil);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getasfdetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$trano = $this->getRequest()->getParam("trano");
        $lastPaid = $this->getRequest()->getParam("lastpaid");

    	$return = $this->trans->getASFDetail($trano);
        $payment = $this->trans->getSumASFPayment($trano);
        $settle = $this->trans->getASFSettleDetail($trano);
        
        if ($payment['total_bayar'] == '')
            $payment['total_bayar'] = 0;
        if ($payment['val_kode'] == '')
            $payment['val_kode'] = ' ';

    	foreach ($return as $key => $val)
    	{
    		if ($val == '""')
    			$return[$key] = '';
    	}

        $return['totalsettle'] = $settle[0]['total'];
        
        if($lastPaid)
        {
           $return['ASFPayment'] = $payment['total_bayar'] - $lastPaid;
           $return['payment_balance'] = $return['totalsettle'] - $return['ASFPayment'];
        }
        else
        {
            $return['ASFPayment'] = $payment['total_bayar'];
            $return['payment_balance'] = $return['totalsettle'] - $payment['total_bayar'];
        }

        $return['ASFPayment_val_kode'] = $payment['val_kode'];
    	$json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getasfcanceldetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$trano = $this->getRequest()->getParam("trano");
        $lastPaid = $this->getRequest()->getParam("lastpaid");

    	$return = $this->trans->getASFDetail($trano);
        $payment = $this->trans->getSumASFPayment($trano);
        $settle = $this->trans->getASFCancelDetail($trano);

        if ($payment['total_bayar'] == '')
            $payment['total_bayar'] = 0;
        if ($payment['val_kode'] == '')
            $payment['val_kode'] = ' ';

    	foreach ($return as $key => $val)
    	{
    		if ($val == '""')
    			$return[$key] = '';
    	}

        $return['totalsettle'] = $settle[0]['total'];

        if($lastPaid)
        {
           $return['ASFPayment'] = $payment['total_bayar'] - $lastPaid;
           $return['payment_balance'] = $return['totalsettle'] - $return['ASFPayment'];
        }
        else
        {
            $return['ASFPayment'] = $payment['total_bayar'];
            $return['payment_balance'] = $return['totalsettle'] - $payment['total_bayar'];
        }

        $return['ASFPayment_val_kode'] = $payment['val_kode'];
    	$json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getasfpayedhistoryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
    	$docTrano = $this->getRequest()->getParam("trano");
        $trano = $this->getRequest()->getParam("exclude");

    	$po = $this->trans->getASFPayment($docTrano,$trano);
    	$id = 1;
    	foreach($po as $key => $val)
    	{
    		$hasil[] = array('id' => $id,'trano' => $val['trano'],'doc_trano' => $val['doc_trano'],'tgl' => date('Y-m-d',strtotime($val['tgl'])),'total_bayar' => $val['total_bayar'],'val_kode' => $val['val_kode'], 'requester' => $val['requester']);
    		$id++;
    	}
    	$return['posts'] = $hasil;
    	$return['count'] = count($hasil);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getasfcancelpayedhistoryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
    	$docTrano = $this->getRequest()->getParam("trano");
        $trano = $this->getRequest()->getParam("exclude");

    	$po = $this->trans->getASFCancelPayment($docTrano,$trano);
    	$id = 1;
    	foreach($po as $key => $val)
    	{
    		$hasil[] = array('id' => $id,'trano' => $val['trano'],'doc_trano' => $val['doc_trano'],'tgl' => date('Y-m-d',strtotime($val['tgl'])),'total_bayar' => $val['total_bayar'],'val_kode' => $val['val_kode'], 'requester' => $val['requester']);
    		$id++;
    	}
    	$return['posts'] = $hasil;
    	$return['count'] = count($hasil);
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getarfdetailAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$trano = $this->getRequest()->getParam("trano");
        $lastPaid = $this->getRequest()->getParam("lastpaid");

    	$return = $this->trans->getARFDetails($trano);
        $payment = $this->trans->getSumARFPayment($trano);
        if ($payment['total_bayar'] == '')
            $payment['total_bayar'] = 0;
        if ($payment['val_kode'] == '')
            $payment['val_kode'] = ' ';

    	foreach ($return as $key => $val)
    	{
    		if ($val == '""')
    			$return[$key] = '';
    	}

        if($lastPaid)
        {
           $return['ARFPayment'] = $payment['total_bayar'] - $lastPaid;
           $return['payment_balance'] = $return['total'] - $return['ARFPayment'];
        }
        else
        {
        $return['ARFPayment'] = $payment['total_bayar'];
        $return['payment_balance'] = $return['total'] - $payment['total_bayar'];
        }
        $return['ARFPayment_val_kode'] = $payment['val_kode'];
    	$json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function whoamiAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$return = array();
    	$return['user']['name'] = $this->session->name;
        $return['user']['uid'] = $this->session->idUser;
        $return['user']['id'] = $this->session->idUser;
        $return['user']['username'] = $this->session->userName;
        $return['user']['privilege'] = $this->session->privilege;
        $return['user']['role'] = $this->session->role;
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function getprfrompoAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$columnValue = $this->getRequest()->getParam('data');
    	$sql = "SELECT 
    			SQL_CALC_FOUND_ROWS  (a.trano) AS po_number, (a.tgl) AS po_tgl, (a.urut) AS po_urut, a.prj_kode, a.prj_nama, a.sit_kode, a.sit_nama, a.workid, a.workname, a.kode_brg, a.nama_brg, (a.qty) AS po_qty, (a.harga) AS po_price, (a.qty * a.harga) AS po_total, (a.myob) AS po_netact, (a.qtyspl) AS qtysupp, (a.hargaspl) AS pricesupp, (a.qtyspl * a.hargaspl) AS totalsupp,
    								(b.trano) AS pr_number, (b.tgl) AS pr_tgl, (b.urut) AS pr_urut, (b.qty) AS pr_qty, (b.harga) AS pr_price, (b.qty * b.harga) AS pr_total, (b.val_kode) AS val_kode, (b.myob) AS myob, (a.ket) AS po_ket
    			FROM procurement_pod a LEFT JOIN procurement_prd b 
    			ON a.pr_no = b.trano AND a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode AND a.kode_brg = b.kode_brg AND a.workid = b.workid 
    			WHERE a.trano ='$columnValue'
    			GROUP BY b.trano,b.prj_kode,b.sit_kode,b.workid,b.kode_brg,b.id";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();
       	$i = 1;
         foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            $return[$key]['id'] = $i;
            $i++;
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);
//        	if ($return[$key]['val_kode'] == 'IDR')
//				{
//	    			$return[$key]['price'] = $return[$key]['hargaIDR'];
//	    			$return[$key]['totalPrice'] = $return[$key]['totalIDR'];
//				}
//	    		else
//	    		{
//	    			$return[$key]['price'] = $return[$key]['hargaUSD'];
//	 	   			$return[$key]['totalPrice'] = $return[$key]['totalUSD'];
//	    		}

            $po = $this->quantity->getPRPOQuantity($return[$key]['pr_number'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
            if ($po != '' )
            {
                    $return[$key]['totalPO'] = $po['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalPricePO'] = $po['totalIDR'];
                    else
                            $return[$key]['totalPricePO'] = $po['totalHargaUSD'];
                    $return[$key]['balancePO'] = $return[$key]['qty'] - $po['qty'];
            }
            else
            {
                    $return[$key]['totalPO'] = 0;
                    $return[$key]['balancePO'] = 0;
                    $return[$key]['totalPricePO'] = 0;
            }
            
        	$rpi = $this->quantity->getPoRPIQuantity($return[$key]['po_number'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg'],$return[$key]['pr_number']);
            if ($rpi != '' )
            {
                    $return[$key]['totalRPI'] = $rpi['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalPriceRPI'] = $rpi['totalIDR'];
                    else
                            $return[$key]['totalPriceRPI'] = $rpi['totalHargaUSD'];
                    $return[$key]['balanceRPI'] = $return[$key]['qty'] - $rpi['qty'];
            }
            else
            {
                    $return[$key]['totalRPI'] = 0;
                    $return[$key]['balanceRPI'] = 0;
                    $return[$key]['totalPriceRPI'] = 0;
            }
            $result[] = $return[$key];
           
        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
             Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($hasil);
       
       
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }


//    public function getprfromarfAction()
//    {
//    	$this->_helper->viewRenderer->setNoRender();
//    	$columnValue = $this->getRequest()->getParam('data');
//    	$sql = "SELECT
//    			SQL_CALC_FOUND_ROWS  (a.trano) AS po_number, (a.tgl) AS po_tgl, (a.urut) AS po_urut, a.prj_kode, a.prj_nama, a.sit_kode, a.sit_nama, a.workid, a.workname, a.kode_brg, a.nama_brg, (a.qty) AS po_qty, (a.harga) AS po_price, (a.total) AS po_total, (a.myob) AS po_netact,
//    								(b.trano) AS pr_number, (b.tgl) AS pr_tgl, (b.urut) AS pr_urut, (b.qty) AS pr_qty, (b.harga) AS pr_price, (b.jumlah) AS pr_total
//    			FROM procurement_pod a LEFT JOIN procurement_prd b
//    			ON a.pr_no = b.trano AND a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode AND a.kode_brg = b.kode_brg AND a.workid = b.workid
//    			WHERE a.trano ='$columnValue'";
//        $fetch = $this->db->query($sql);
//        $return = $fetch->fetchAll();
//       	$i = 1;
//         foreach($return as $key => $value)
//        {
//            foreach($value as $key2 => $val2)
//            {
//                if ($val2 == "\"\"")
//                {
//                    $return[$key][$key2] = '';
//                }
//            }
//            $return[$key]['id'] = $i;
//            $i++;
//            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
//            $return[$key]['nama_brg'] = str_replace("\"","'",$return[$key]['nama_brg']);
////        	if ($return[$key]['val_kode'] == 'IDR')
////				{
////	    			$return[$key]['price'] = $return[$key]['hargaIDR'];
////	    			$return[$key]['totalPrice'] = $return[$key]['totalIDR'];
////				}
////	    		else
////	    		{
////	    			$return[$key]['price'] = $return[$key]['hargaUSD'];
////	 	   			$return[$key]['totalPrice'] = $return[$key]['totalUSD'];
////	    		}
//
//            $po = $this->quantity->getPRPOQuantity($return[$key]['pr_number'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
//            if ($po != '' )
//            {
//                    $return[$key]['totalPO'] = $po['qty'];
//                    if ($return[$key]['val_kode'] == 'IDR')
//                            $return[$key]['totalPricePO'] = $po['totalIDR'];
//                    else
//                            $return[$key]['totalPricePO'] = $po['totalUSD'];
//                    $return[$key]['balancePO'] = $return[$key]['qty'] - $po['qty'];
//            }
//            else
//            {
//                    $return[$key]['totalPO'] = 0;
//                    $return[$key]['balancePO'] = 0;
//                    $return[$key]['totalPricePO'] = 0;
//            }
//
//        	$rpi = $this->quantity->getPoRPIQuantity($return[$key]['po_number'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
//            if ($rpi != '' )
//            {
//                    $return[$key]['totalRPI'] = $rpi['qty'];
//                    if ($return[$key]['val_kode'] == 'IDR')
//                            $return[$key]['totalPriceRPI'] = $rpi['totalIDR'];
//                    else
//                            $return[$key]['totalPriceRPI'] = $rpi['totalUSD'];
//                    $return[$key]['balanceRPI'] = $return[$key]['qty'] - $rpi['qty'];
//            }
//            else
//            {
//                    $return[$key]['totalRPI'] = 0;
//                    $return[$key]['balanceRPI'] = 0;
//                    $return[$key]['totalPriceRPI'] = 0;
//            }
//            $result[] = $return[$key];
//
//        }
//            $hasil['posts'] = $result;
//            $hasil['count'] = count($return);
//             Zend_Loader::loadClass('Zend_Json');
//            $json = Zend_Json::encode($hasil);
//
//
//        //result encoded in JSON
//
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        $this->getResponse()->setBody($json);
//    }







    public function getprfromdorAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$columnValue = $this->getRequest()->getParam('data');
    	$sql = "SELECT
    			SQL_CALC_FOUND_ROWS  (a.trano) AS do_number, (a.tgl) AS do_tgl, (a.urut) AS do_urut, a.prj_kode, a.prj_nama, a.sit_kode, a.sit_nama, a.workid, a.workname, a.kode_brg, a.nama_brg, (a.qty) AS qty, (a.ket) as ket,
    								(b.trano) AS pr_number, (b.tgl) AS pr_tgl, (b.urut) AS pr_urut, (b.qty) AS pr_qty
    			FROM procurement_pointd a LEFT JOIN procurement_prd b
    			ON a.pr_no = b.trano AND a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode AND a.kode_brg = b.kode_brg AND a.workid = b.workid
    			WHERE a.trano ='$columnValue'";

        $fetch = $this->db->query($sql);
        
        $return = $fetch->fetchAll();

       	$i = 1;
         foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            $return[$key]['id'] = $i;
            $i++;
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);

            $do = $this->quantity->getPRDORQuantity($return[$key]['pr_number'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
            if ($do != '' )
            {
                    $return[$key]['totalDOR'] = $return[$key]['pr_qty'] - $do['qty'];
                    $return[$key]['balanceDOR'] = $return[$key]['pr_qty'] - $do['qty'];
            }
            else
            {
                    $return[$key]['totalDOR'] = 0;
                    $return[$key]['balanceDOR'] = 0;
            }

            $result[] = $return[$key];

        }
            $hasil['posts'] = $result;

            $hasil['count'] = count($return);
             Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($hasil);



        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getarfsummaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $columnValue = $this->getRequest()->getParam('data');

       $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_arfd WHERE trano LIKE \'%' . $columnValue . '%\' ORDER BY trano ASC';
       $fetch = $this->db->query($sql);
       $return = $fetch->fetchAll();
        $id = 1;

        foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            $return[$key]['id'] = $id;
        	$id++;
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);

//        	if ($return[$key]['val_kode'] == 'IDR')
//				{
//	    			$return[$key]['price'] = $return[$key]['hargaIDR'];
//	    			$return[$key]['totalPrice'] = $return[$key]['totalIDR'];
//				}
//	    		else
//	    		{
//	    			$return[$key]['price'] = $return[$key]['hargaUSD'];
//	 	   			$return[$key]['totalPrice'] = $return[$key]['totalUSD'];
//	    		}

            $asf = $this->quantity->getArfAsfQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
            if ($asf != '' )

            {
                    $return[$key]['totalASF'] = $asf['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalPriceASF'] = $asf['totalIDR'];
                    else
                            $return[$key]['totalPriceASF'] = $asf['totalUSD'];
//                    $return[$key]['balancePO'] = $return[$key]['qty'] - $po['qty'];
            }
            else
            {
                    $return[$key]['totalASF'] = 0;
//                    $return[$key]['balancePO'] = 0;
                    $return[$key]['totalPriceASF'] = 0;
            }

            $asfcancel = $this->quantity->getArfAsfcancelQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
            if ($asfcancel != '' )

            {
                    $return[$key]['totalASFCancel'] = $asfcancel['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalPriceASFCancel'] = $asfcancel['totalIDR'];
                    else
                            $return[$key]['totalPriceASFCancel'] = $asfcancel['totalUSD'];
//                    $return[$key]['balancePO'] = $return[$key]['qty'] - $po['qty'];
            }
            else
            {
                    $return[$key]['totalASFCancel'] = 0;
//                    $return[$key]['balancePO'] = 0;
                    $return[$key]['totalPriceASFCancel'] = 0;
            }

            $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);
           
            if ($arfh != '')
                $return[$key]['totalInArfh'] = $arfh['total'];
            else
                $return[$key]['totalInArfh'] = 0;

            $result[] = $return[$key];

        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
            $json = Zend_Json::encode($hasil);

            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
    }

    public function getasfddsummaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $columnValue = $this->getRequest()->getParam('data');

       $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_asfdd WHERE trano LIKE \'%' . $columnValue . '%\' ORDER BY trano ASC';
       $fetch = $this->db->query($sql);
       $return = $fetch->fetchAll();
        $id = 1;

        foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            $return[$key]['id'] = $id;
        	$id++;
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);

//        	if ($return[$key]['val_kode'] == 'IDR')
//				{
//	    			$return[$key]['price'] = $return[$key]['hargaIDR'];
//	    			$return[$key]['totalPrice'] = $return[$key]['totalIDR'];
//				}
//	    		else
//	    		{
//	    			$return[$key]['price'] = $return[$key]['hargaUSD'];
//	 	   			$return[$key]['totalPrice'] = $return[$key]['totalUSD'];
//	    		}

            $result[] = $return[$key];

        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
            $json = Zend_Json::encode($hasil);

            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
    }

    public function getasfddcancelsummaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $columnValue = $this->getRequest()->getParam('data');

       $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_asfddcancel WHERE trano LIKE \'%' . $columnValue . '%\' ORDER BY trano ASC';
       $fetch = $this->db->query($sql);
       $return = $fetch->fetchAll();
        $id = 1;

        foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            $return[$key]['id'] = $id;
        	$id++;
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);

//        	if ($return[$key]['val_kode'] == 'IDR')
//				{
//	    			$return[$key]['price'] = $return[$key]['hargaIDR'];
//	    			$return[$key]['totalPrice'] = $return[$key]['totalIDR'];
//				}
//	    		else
//	    		{
//	    			$return[$key]['price'] = $return[$key]['hargaUSD'];
//	 	   			$return[$key]['totalPrice'] = $return[$key]['totalUSD'];
//	    		}

            $result[] = $return[$key];

        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
            $json = Zend_Json::encode($hasil);

            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
    }

    public function getprsummarybyoneAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	$workid = $this->getRequest()->getParam("workid");
    	$kodeBrg = $this->getRequest()->getParam("kode_brg");
    	$trano = $this->getRequest()->getParam("trano");

        $sql = "SELECT * FROM procurement_prd WHERE trano = '$trano' AND prj_kode = '$prjKode' AND sit_kode = '$sitKode'  AND workid = '$workid' AND kode_brg = '$kodeBrg'";
        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetch();
        if ($hasil)
        {
            foreach ($hasil as $key => $val)
            {
                $hasil[$key] = str_replace("\"\"","",$val);
                $hasil[$key] = str_replace("\""," inch",$hasil[$key]);
                $hasil[$key] = str_replace("'","",$hasil[$key]);
            }
        }
        $json = Zend_Json::encode($hasil);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
        
    }

    public function getposummarybyoneAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$prjKode = $this->getRequest()->getParam("prj_kode");
    	$sitKode = $this->getRequest()->getParam("sit_kode");
    	$workid = $this->getRequest()->getParam("workid");
    	$kodeBrg = $this->getRequest()->getParam("kode_brg");
    	$trano = $this->getRequest()->getParam("trano");

    	if ($prjKode != '')
    	{
	    	$po = $this->quantity->getPoRPIQuantity($trano,$prjKode,$sitKode,$workid,$kodeBrg);
    		$po['uom'] = $this->quantity->getUOMByProductID($po['kode_brg']);
            foreach($po as $key => $val2)
            {
               if (strpos($val2,'""') !== false)
                   $po[$key] = str_replace("\"\"","",$po[$key]);
               if (strpos($val2,'"') !== false)
                   $po[$key] = str_replace("\""," inch",$po[$key]);
               if (strpos($val2, "\r") !== false)
                   $po[$key] = str_replace("\r","",$po[$key]);
               if (strpos($val2, "\n") !== false)
                   $po[$key] = str_replace("\n","",$po[$key]);
               if (strpos($val2,"'") !== false)
                   $po[$key] = str_replace("'","",$po[$key]);
            }
			if ($po['val_kode'] == 'IDR')
			{
    			$po['price'] = $po['hargaIDR'];
    			$po['totalPrice'] = $po['totalIDR'];
			}
    		else
    		{
    			$po['price'] = $po['hargaUSD'];
 	   			$po['totalPrice'] = $po['totalUSD'];
    		}

    		$newQtyPR = 0;
    		$newPriceIDRPR = 0;
    		$newPriceUSDPR = 0;
    		$rpi = $this->quantity->getPoRPIQuantity($po['trano'],$prjKode,$sitKode,$po['workid'],$po['kode_brg']);
    		if ($rpi != '')
    		{
    			if ($trano != '')
    			{
    				$isPO = $this->trans->isPRExecuted($trano);
    				$thisPR = $this->quantity->getPrQuantityByTrano($trano);
    				if ($isPO == '')
    				{
    					$newQtyPR = $thisPR['qty'];
		    			$newPriceIDRPR = $thisPR['totalIDR'];
		    			$newPriceUSDPR = $thisPR['totalUSD'];
    				}
    			}
    			$boq3['totalPR'] = $rpi['qty'] - $newQtyPR;
    			if ($boq3['val_kode'] == 'IDR')
    				$boq3['totalPricePR'] = $rpi['totalIDR'] - $newPriceIDRPR;
    			else
    				$boq3['totalPricePR'] = $rpi['totalUSD'] - $newPriceUSDPR;
    		    	$boq3['balancePR'] = $boq3['qty'] - $rpi['qty'] - $newQtyPR;
    		}
    		else
    		{
    			$boq3['totalPR'] = 0;
    			$boq3['balancePR'] = 0;
    			$boq3['totalPricePR'] = 0;
    		}

    	}

    	$return['posts'] = $boq3;
    	$return['count'] = 1;
    	$json = Zend_Json::encode($return);
        //result encoded in JSON
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getarffromasfAction()
    {
       $this->_helper->viewRenderer->setNoRender();
    	$columnValue = $this->getRequest()->getParam('data');
    	$sql = "SELECT
    			SQL_CALC_FOUND_ROWS  (a.trano) AS asf_number, (a.tgl) AS asf_tgl, (a.urut) AS asf_urut, (a.total) AS asf_total,
    								(b.trano) AS arf_number, (b.tgl) AS arf_tgl, (b.urut) AS arf_urut, (b.qty) AS arf_qty, (b.harga) AS arf_price, (b.total) AS arf_total, b.prj_kode, b.prj_nama, b.sit_kode, b.sit_nama, b.workid, b.workname, b.kode_brg, b.nama_brg
    			FROM procurement_asfd a RIGHT JOIN procurement_arfd b
    			ON a.arf_no = b.trano AND a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode
    			WHERE a.trano ='$columnValue'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();
       	$i = 1;

         foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            $return[$key]['id'] = $i;
            $i++;
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);

            $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);

            if ($arfh != '')
                $return[$key]['totalInArfh'] = $arfh['total'];
            else
                $return[$key]['totalInArfh'] = 0;

            $result[] = $return[$key];

        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
             Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($hasil);

        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json); 
    }

    public function getasfddlistAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$columnValue = $this->getRequest()->getParam('data');
    	$sql = "SELECT
    			*
    			FROM procurement_asfdd
    			WHERE trano ='$columnValue'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();

         foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);

             $asf = $this->quantity->getArfAsfQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
            if ($asf != '' )

            {
                    $return[$key]['totalASF'] = $asf['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalPriceASF'] = $asf['totalIDR'];
                    else
                            $return[$key]['totalPriceASF'] = $asf['totalUSD'];
//                    $return[$key]['balancePO'] = $return[$key]['qty'] - $po['qty'];
            }
            else
            {
                    $return[$key]['totalASF'] = 0;
//                    $return[$key]['balancePO'] = 0;
                    $return[$key]['totalPriceASF'] = 0;
            }

            $asfcancel = $this->quantity->getArfAsfcancelQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
            if ($asfcancel != '' )
            {
                    $return[$key]['totalASFCancel'] = $asfcancel['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalPriceASFCancel'] = $asfcancel['totalIDR'];
                    else
                            $return[$key]['totalPriceASFCancel'] = $asfcancel['totalUSD'];
//                    $return[$key]['balancePO'] = $return[$key]['qty'] - $po['qty'];
            }
            else
            {
                    $return[$key]['totalASFCancel'] = 0;
//                    $return[$key]['balancePO'] = 0;
                    $return[$key]['totalPriceASFCancel'] = 0;
            }

            $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);

            if ($arfh != '')
                $return[$key]['totalInArfh'] = $arfh['total'];
            else
                $return[$key]['totalInArfh'] = 0;


            $result[] = $return[$key];

        }

            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
             Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($hasil);


        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getasfddcancellistAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$columnValue = $this->getRequest()->getParam('data');
    	$sql = "SELECT
    			*
    			FROM procurement_asfdd
    			WHERE trano ='$columnValue'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();

         foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);

             $asf = $this->quantity->getArfAsfQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
            if ($asf != '' )

            {
                    $return[$key]['totalASF'] = $asf['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalPriceASF'] = $asf['totalIDR'];
                    else
                            $return[$key]['totalPriceASF'] = $asf['totalUSD'];
//                    $return[$key]['balancePO'] = $return[$key]['qty'] - $po['qty'];
            }
            else
            {
                    $return[$key]['totalASF'] = 0;
//                    $return[$key]['balancePO'] = 0;
                    $return[$key]['totalPriceASF'] = 0;
            }

            $asfcancel = $this->quantity->getArfAsfcancelQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
            if ($asfcancel != '' )

            {
                    $return[$key]['totalASFCancel'] = $asfcancel['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $return[$key]['totalPriceASFCancel'] = $asfcancel['totalIDR'];
                    else
                            $return[$key]['totalPriceASFCancel'] = $asfcancel['totalUSD'];
//                    $return[$key]['balancePO'] = $return[$key]['qty'] - $po['qty'];
            }
            else
            {
                    $return[$key]['totalASFCancel'] = 0;
//                    $return[$key]['balancePO'] = 0;
                    $return[$key]['totalPriceASFCancel'] = 0;
            }

            $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);

            if ($arfh != '')
                $return[$key]['totalInArfh'] = $arfh['total'];
            else
                $return[$key]['totalInArfh'] = 0;


            $result[] = $return[$key];

        }

            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
             Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($hasil);


        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }


        public function getboq3arfsummaryAction()
        {
            $this->_helper->viewRenderer->setNoRender();
            $prjKode = $this->getRequest()->getParam("prj_kode");
    	    $sitKode = $this->getRequest()->getParam("sit_kode");
            $paging = $this->getRequest()->getParam("arf");
    	    $current = 0;

            $boq3 = $this->budget->getBoq3('all-current',$prjKode,$sitKode);

            $i = 1;
            $limit = count($boq3);
            if ($paging)
            {
                $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
                $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
            }
            else
                $offset = 0;

            $result = array();

           $id=1;
            foreach($boq3 as $key => $value)
            {
//                $return[$key]['id'] = $id;
//                $id++;
                foreach($value as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                    {
                        $boq3[$key][$key2] = '';
                    }
                }
                $boq3[$key]['id'] = $id;$id++;
                $boq3[$key]['uom'] = $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']);
                $boq3[$key]['nama_brg'] = str_replace("\""," inch",$boq3[$key]['nama_brg']);
                $boq3[$key]['nama_brg'] = str_replace("'"," inch",$boq3[$key]['nama_brg']);
           
                if ($boq3[$key]['val_kode'] == 'IDR')
				{
	    			$boq3[$key]['price'] = $boq3[$key]['hargaIDR'];
	    			$boq3[$key]['totalPrice'] = $boq3[$key]['totalIDR'];
				}
	    		else
	    		{
	    			$boq3[$key]['price'] = $boq3[$key]['hargaUSD'];
	 	   			$boq3[$key]['totalPrice'] = $boq3[$key]['totalHargaUSD'];
	    		}

                if ($current < ($limit + $offset) && $current >= $offset)
	    		{

                $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                $sal = $this->quantity->getSalQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
//                $reimburs = $this->quantity->getReimbursementQuantity($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg']);
                
//                var_dump($po);die;
                if ($po != '' )
                {
                        $boq3[$key]['totalqtyPO'] = $po['qty'];
                        if ($boq3[$key]['val_kode'] == 'IDR')
                            $boq3[$key]['totalPO'] = $po['totalIDR'];
                        else
                            $boq3[$key]['totalPO'] = $po['totalHargaUSD'];
                }
                else
                {
                        $boq3[$key]['totalqtyPO'] = 0;
                        $boq3[$key]['totalPO'] = 0;
                }
                    
                if ($arf != '' )
                {
                        $boq3[$key]['totalqtyARF'] = $arf['qty'];
                        if ($boq3[$key]['val_kode'] == 'IDR')
                            $boq3[$key]['totalInARF'] = $arf['totalIDR'];
                        else
                            $boq3[$key]['totalInARF'] = $arf['totalHargaUSD'];
                }
                else
                {
                        $boq3[$key]['totalqtyARF'] = 0;
                        $boq3[$key]['totalInARF'] = 0;
                }
                
                if ($asfcancel != '' )
                {
                        $boq3[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                        if ($boq3[$key]['val_kode'] == 'IDR')
                            $boq3[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                        else
                            $boq3[$key]['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                }
                else
                {
                        $boq3[$key]['totalqtyASFCancel'] = 0;
                        $boq3[$key]['totalASFCancel'] = 0;
                }

                if ($sal != '' )
                {
                        $boq3[$key]['totalqtySal'] = $sal['qty'];
                            $boq3[$key]['totalSal'] = $sal['totalIDR'];
                }
                else
                {
                        $boq3[$key]['totalqtySal'] = 0;
                        $boq3[$key]['totalSal'] = 0;
                }


                $totalpoarfasfc = (($boq3[$key]['totalPO'] +  $boq3[$key]['totalInARF'] +  $boq3[$key]['totalSal']) -  $boq3[$key]['totalASFCancel'] ) ;

                $boq3[$key]['totalPoArfAsfc'] = $totalpoarfasfc;
                $result[] = $boq3[$key];
                }
		    	$current++;
	    		$i++;

            }
                $hasil['posts'] = $result;
                $hasil['count'] = count($boq3);
                $json = Zend_Json::encode($hasil);

                //result encoded in JSON
                $json = str_replace("\\","",$json);
                $this->getResponse()->setHeader('Content-Type', 'text/javascript');
                $this->getResponse()->setBody($json);
        }

    public function getbudgetarfsummaryAction()
        {
            $this->_helper->viewRenderer->setNoRender();
            $prjKode = $this->getRequest()->getParam("prj_kode");
    	    $sitKode = $this->getRequest()->getParam("sit_kode");
            $paging = $this->getRequest()->getParam("arf");
    	    $current = 0;

            $budget = $this->budget->getBudgetOverhead($prjKode,$sitKode);

            $i = 1;
            $limit = count($budget);
            if ($paging)
            {
                $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
                $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
            }
            else
                $offset = 0;

            $result = array();

           $id=1;
            foreach($budget as $key => $value)
            {
//                $return[$key]['id'] = $id;
//                $id++;
                foreach($value as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                    {
                        $budget[$key][$key2] = '';
                    }
                }
                $budget[$key]['id'] = $id;$id++;
//                $budget[$key]['uom'] = $this->quantity->getUOMByProductID($budget[$key]['kode_brg']);
//                $budget[$key]['nama_brg'] = str_replace("\""," inch",$budget[$key]['nama_brg']);
//                $budget[$key]['nama_brg'] = str_replace("'"," inch",$budget[$key]['nama_brg']);
                $budget[$key]['totalPrice'] = $budget[$key]['total'];

                if ($budget[$key]['val_kode'] == 'IDR')
				{

	    			$budget[$key]['totalPrice'] = $budget[$key]['totalIDR'];
				}
	    		else
	    		{

	 	   			$budget[$key]['totalPrice'] = $budget[$key]['totalHargaUSD'];
	    		}

                if ($current < ($limit + $offset) && $current >= $offset)
	    		{

                $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$budget[$key]['budgetid']);
                $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$budget[$key]['budgetid']);
                $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$budget[$key]['budgetid']);
//                $reimburs = $this->quantity->getReimbursementQuantity($prjKode,$sitKode,$budget[$key]['budgetid']);
                $pr = $this->quantity->getPrOverheadQuantity($prjKode,$sitKode,$budget[$key]['budgetid']);
                if ($pr != '')
                {

                    $budget[$key]['totalqtyPR'] = $pr['qty'];

                    $budget[$key]['totalPricePR'] = $pr['total'];

                }

                else
                {
                    $budget[$key]['totalqtyPR'] = 0;

                    $budget[$key]['totalPricePR'] = 0;
                }

//                var_dump($po);die;
                if ($po != '' )
                {
                        $budget[$key]['totalqtyPO'] = $po['qty'];
                        if ($budget[$key]['val_kode'] == 'IDR')
                            $budget[$key]['totalPO'] = $po['totalIDR'];
                        else
                            $budget[$key]['totalPO'] = $po['totalHargaUSD'];
                }
                else
                {
                        $budget[$key]['totalqtyPO'] = 0;
                        $budget[$key]['totalPO'] = 0;
                }

                if ($arf != '' )
                {
                        $budget[$key]['totalqtyARF'] = $arf['qty'];
                        if ($budget[$key]['val_kode'] == 'IDR')
                            $budget[$key]['totalInARF'] = $arf['totalIDR'];
                        else
                            $budget[$key]['totalInARF'] = $arf['totalHargaUSD'];
                }
                else
                {
                        $budget[$key]['totalqtyARF'] = 0;
                        $budget[$key]['totalInARF'] = 0;
                }

                if ($asfcancel != '' )
                {
                        $budget[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                        if ($budget[$key]['val_kode'] == 'IDR')
                            $budget[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                        else
                            $budget[$key]['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                }
                else
                {
                        $budget[$key]['totalqtyASFCancel'] = 0;
                        $budget[$key]['totalASFCancel'] = 0;
                }

//                if ($reimburs != '' )
//                {
//                        $budget[$key]['totalqtyReimburs'] = $reimburs['qty'];
//                        if ($budget[$key]['val_kode'] == 'IDR')
//                            $budget[$key]['totalReimburs'] = $reimburs['totalIDR'];
//                        else
//                            $budget[$key]['totalReimburs'] = $reimburs['totalHargaUSD'];
//                }
//                else
//                {
//                        $budget[$key]['totalqtyReimburs'] = 0;
//                        $budget[$key]['totalReimburs'] = 0;
//                }

//                $totalpoarfasfc = (($budget[$key]['totalPO'] +  $budget[$key]['totalInARF'] + $budget[$key]['totalReimburs'] + $budget[$key]['totalPricePR']) -  $budget[$key]['totalASFCancel'] ) ;
//                $totalpoarfasfc = (($budget[$key]['totalPO'] +  $budget[$key]['totalInARF']  + $budget[$key]['totalPricePR']) -  $budget[$key]['totalASFCancel'] ) ;
                $totalpoarfasfc = (($budget[$key]['totalPO'] +  $budget[$key]['totalInARF']) -  $budget[$key]['totalASFCancel'] ) ;
                $budget[$key]['totalPoArfAsfc'] = $totalpoarfasfc;
                $result[] = $budget[$key];
                }
		    	$current++;
	    		$i++;

            }
                $hasil['posts'] = $result;
                $hasil['count'] = count($budget);
                $json = Zend_Json::encode($hasil);

                //result encoded in JSON
                $json = str_replace("\\","",$json);
                $this->getResponse()->setHeader('Content-Type', 'text/javascript');
                $this->getResponse()->setBody($json);
        }

    public function getboq3arf2kodebrgAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $workid = $this->getRequest()->getParam("workid");
        $kodeBrg = $this->getRequest()->getParam("kode_brg");
        $isPR = $this->getRequest()->getParam("pr");
        
        $current = 0;

        $boq3 = $this->budget->getBoq3ByOne($prjKode,$sitKode,$workid,$kodeBrg);
        $i = 1;
        $limit = count($boq3);
        if ($isPR)
        {
            $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
            $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        }
        else
            $offset = 0;

        $result = array();

       $id=1;

            $boq3['id'] = $id;$id++;
            $boq3['uom'] = $this->quantity->getUOMByProductID($kodeBrg);
            $boq3['nama_brg'] = str_replace("\""," inch",$boq3['nama_brg']);
             $boq3['nama_brg'] = str_replace("'"," inch",$boq3['nama_brg']);
            $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$workid,$kodeBrg);
            $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid,$kodeBrg);
            $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$workid,$kodeBrg);



        if (($po != '') )
        {
            $hasil['success'] = true;
        }
        if (($arf != '') )
        {
            $hasil['success'] = true;
        }
        if (($asfcancel != '') )
        {
            $hasil['success'] = true;
        }
            if ($po != '' )
            {
                    $boq3['totalqtyPO'] = $po['qty'];
                    if ($boq3['val_kode'] == 'IDR')
                        $boq3['totalPO'] = $po['totalIDR'];
                    else
                        $boq3['totalPO'] = $po['totalUSD'];
            }
            else
            {
                    $boq3['totalqtyPO'] = 0;
                    $boq3['totalPO'] = 0;
            }
            if ($arf != '' )
            {
                    $boq3['totalqtyARF'] = $arf['qty'];
                    if ($boq3['val_kode'] == 'IDR')
                        $boq3['totalARF'] = $arf['totalIDR'];
                    else
                        $boq3['totalARF'] = $arf['totalUSD'];
            }
            else
            {
                    $boq3['totalqtyARF'] = 0;
                    $boq3['totalARF'] = 0;
            }

            if ($asfcancel != '' )
            {
                    $boq3['totalqtyASFCancel'] = $asfcancel['qty'];
                    if ($boq3['val_kode'] == 'IDR')
                        $boq3['totalASFCancel'] = $asfcancel['totalIDR'];
                    else
                        $boq3['totalASFCancel'] = $asfcancel['totalUSD'];
            }
            else
            {
                    $boq3['totalqtyASFCancel'] = 0;
                    $boq3['totalASFCancel'] = 0;
            }
            $totalpoarfasfc = (($boq3['totalPO'] +  $boq3['totalARF'] ) -  $boq3['totalASFCancel'] ) ;
            $boq3['totalPoArfAsfc'] = $totalpoarfasfc;
            $result[] = $boq3;

            $hasil['posts'] = $result;
            $hasil['count'] = count($boq3);
            $json = Zend_Json::encode($hasil);

            //result encoded in JSON
            $json = str_replace("\\","",$json);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);

    }


    public function getmastermanagerAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $asfno = $this->getRequest()->getParam("asfno");
        $sql = "SELECT CONVERT(petugas, SIGNED INTEGER) AS value, petugas FROM procurement_asfh where trano = '$asfno'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $petugas = $return['petugas'];

        if ($return['value'] == 0)
        {

            $query = "SELECT * FROM master_login WHERE uid = '$petugas'";
            $ambil = $this->db->query($query);
            $hasil = $ambil->fetch();

            $result = $hasil['Name'];

        }
        else{

            $query = "SELECT * FROM master_manager WHERE mgr_kode = '$petugas'";
            $ambil = $this->db->query($query);
            $hasil = $ambil->fetch();

            $result = $hasil['mgr_nama'];
       
        }

            Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($result);

           //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getmastermanagerforarfpaymentAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $arfno = $this->getRequest()->getParam("arfno");
        $sql = "SELECT CONVERT(request, SIGNED INTEGER) AS value, request FROM procurement_arfh where trano = '$arfno'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $petugas = $return['request'];

        if ($return['value'] == 0)
        {

            $query = "SELECT * FROM master_login WHERE uid = '$petugas'";
            $ambil = $this->db->query($query);
            $hasil = $ambil->fetch();

            $result = $hasil['Name'];

        }
        else{

            $query = "SELECT * FROM master_manager WHERE mgr_kode = '$petugas'";
            $ambil = $this->db->query($query);
            $hasil = $ambil->fetch();

            $result = $hasil['mgr_nama'];

        }

            Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($result);

           //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getmastervalutaAction()
        {
            $this->_helper->viewRenderer->setNoRender();
            $valuta = $this->getRequest()->getParam("val_kode");
          
            $sql = "SELECT * FROM master_valuta WHERE val_kode = '$valuta'";

            $fetch = $this->db->query($sql);
            $return = $fetch->fetch();

            $valutanama = $return['val_nama'];


                Zend_Loader::loadClass('Zend_Json');
                $json = Zend_Json::encode($valutanama);

               //result encoded in JSON

            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);

        }

    public function getmasterlogin2Action()
            {
                $this->_helper->viewRenderer->setNoRender();
                $Xrequest = $this->getRequest()->getParam("uid");
                $sql = "SELECT * FROM master_login WHERE uid = '$Xrequest'";

                $fetch = $this->db->query($sql);
                $return = $fetch->fetch();

                $Name = $return['Name'];


                    Zend_Loader::loadClass('Zend_Json');
                    $json = Zend_Json::encode($Name);

                   //result encoded in JSON

                $this->getResponse()->setHeader('Content-Type', 'text/javascript');
                $this->getResponse()->setBody($json);

            }

    public function getmasterlogin3Action()
            {
                $this->_helper->viewRenderer->setNoRender();
                $Xrequest = $this->getRequest()->getParam("master_login");
                $sql = "SELECT * FROM master_login WHERE master_login = '$Xrequest'";
                $fetch = $this->db->query($sql);
                $return = $fetch->fetch();

                $Name = $return['Name'];


                    Zend_Loader::loadClass('Zend_Json');
                    $json = Zend_Json::encode($Name);

                   //result encoded in JSON

                $this->getResponse()->setHeader('Content-Type', 'text/javascript');
                $this->getResponse()->setBody($json);

            }





    public function getmasterloginAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $type = $this->getRequest()->getParam("type");
        $data = $this->getRequest()->getParam("data");
        
//        $sql = "SELECT orangpic, orangfinance FROM procurement_asfh where trano = '$asfno'";
//        $fetch = $this->db->query($sql);
//        $return = $fetch->fetch();
//
//        $pic = $return['orangpic'];
//        $finance = $return['orangfinance'];

        if ($type == 'finance')
        {
            $query = "SELECT * FROM master_login WHERE uid = '$data'";
            $ambil = $this->db->query($query);
            $hasil = $ambil->fetch();

            $result = $hasil['Name'];
        }else{
            $query = "SELECT * FROM master_login WHERE uid = '$data'";
            $ambil = $this->db->query($query);
            $hasil = $ambil->fetch();

            $result = $hasil['Name'];
        }

            Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($result);

           //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getdataforeditasfAction()
    {
        $this->_helper->viewRenderer->setNoRender();
    	$asfno = $this->getRequest()->getParam('data');

        $sql = "SELECT a.* FROM procurement_arfd a LEFT JOIN procurement_asfd b ON a.trano = b.arf_no WHERE b.trano = '$asfno'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();   

        $id = 1;
        $i = 1;

        $hasil['arfd'] = array();
        $hasil['asfdd'] = array();
        $hasil['asfddcancel'] = array();

        foreach ($return as $key => $val)
        {
            $uom = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['uom'] = $uom;
            $asf = $this->quantity->getArfAsfQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
            if ($asf != '' )

                {
                    $asfqty = $asf['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $asftotal = $asf['totalIDR'];
                    else
                            $asftotal = $asf['totalUSD'];

                }
                else
                {
                    $asfqty = 0;
                    $asftotal = 0;
                }

            $asfcancel = $this->quantity->getArfAsfcancelQuantity($return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);
                if ($asfcancel != '' )

                {

                    $asfcancelqty = $asfcancel['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                            $asfcanceltotal = $asfcancel['totalIDR'];
                    else
                            $asfcanceltotal = $asfcancel['totalUSD'];

                }
                else
                {
                    
                    $asfcancelqty = 0;
                    $asfcanceltotal = 0;
                }

            $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);

                if ($arfh != '')
                    $inarfhtotal = $arfh['total'];
                else
                    $inarfhtotal = 0;


//var_dump( $return[$key]['totalASFCancel']);die();

            $return[$key]['id'] = $id;
                foreach ($return[$key] as $key2 => $val2)
                {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                }
            $return[$key]['price'] = $return[$key]['harga'];
            $return[$key]['totalPrice'] = $return[$key]['total'];
            unset($return[$key]['harga']);
            unset($return[$key]['total']);
            $return[$key]['totalASF'] = $asfqty;
            $return[$key]['totalPriceASF'] = $asftotal;
            $return[$key]['totalASFCancel'] = $asfcancelqty;
            $return[$key]['totalPriceASFCancel'] = $asfcanceltotal;
            $return[$key]['totalPriceInArfh'] = $inarfhtotal;

            $hasil['arfd'][] = $return[$key];


             $asfdd = $this->quantity->getAsfddList($asfno,$return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'], $return[$key]['kode_brg']);

            if ($asfdd != '')
            {

//                if (array_keys($asfdd) != 0)
//                {
                for($i = 0;$i < count($asfdd); $i++)
                    {
                    foreach ($asfdd[$i] as $key2 => $val2)
                    {
                        if ($val2 == "\"\"")
                            $asfdd[$i][$key2] = '';
                        if (strpos($val2,"\"")!== false)
                            $asfdd[$i][$key2] = str_replace("\""," inch",$asfdd[$i][$key2]);
                        if (strpos($val2,"'")!== false)
                            $asfdd[$i][$key2] = str_replace("'"," inch",$asfdd[$i][$key2]);

//                        foreach($val as $key2 => $val2)
//                        {
//                            if ($val2 == "\"\"")
//                                $asfdd[$key][$key2] = '';
//                            if (strpos($val2,"\"")!== false)
//                                $asfdd[$key][$key2] = str_replace("\""," inch",$asfdd[$key][$key2]);
//                            if (strpos($val2,"'")!== false)
//                                $asfdd[$key][$key2] = str_replace("'"," inch",$asfdd[$key][$key2]);
//                        }
                    }

                    $asfdd[$i]['id'] = $id;
                    $asfdd[$i]['uom'] = $uom;
                    $asfdd[$i]['price'] = $asfdd[$i]['harga'];
                    $asfdd[$i]['totalPrice'] = $asfdd[$i]['total'];
                    $asfdd[$i]['totalASF'] = $asfqty;
                    $asfdd[$i]['totalPriceASF'] = $asftotal;
                    $asfdd[$i]['totalASFCancel'] = $asfcancelqty;
                    $asfdd[$i]['totalPriceASFCancel'] = $asfcanceltotal;
                    $asfdd[$i]['totalPriceInArfh'] = $inarfhtotal;
                    unset($asfdd['harga']);
                    unset($asfdd['total']);
                    $hasil['asfdd'][] = $asfdd[$i];
                }
//                }
//                else
//                {
//                    foreach ($asfdd as $key2 => $val2)
//                    {
//                        if ($val2 == "\"\"")
//                            $asfdd[$key2][$val2] = '';
//                    }
//                    foreach ($asfdd as $key2 => $val2)
//                    {
//                        $val2['id'] = $id;
//                        $val2['uom'] = $uom;
//                        $val2['price'] = $val2['harga'];
//                        $val2['totalPrice'] = $val2['total'];
//                        $val2['totalASF'] = $asfqty;
//                        $val2['totalPriceASF'] = $asftotal;
//                        $val2['totalASFCancel'] = $asfcancelqty;
//                        $val2['totalPriceASFCancel'] = $asfcanceltotal;
//                        $val2['totalPriceInArfh'] = $inarfhtotal;
//
//
//                        $hasil['asfdd'][] = $val2;
//                    }
//                }

            }
             $asfddcancel = $this->quantity->getAsfddCancelList($asfno,$return[$key]['trano'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'], $return[$key]['kode_brg']);

                if ($asfddcancel != '')
                {
//                    if (array_keys($asfddcancel) != 0)
//                    {
                    for($i = 0;$i < count($asfddcancel); $i++)
                    {
                        foreach ($asfddcancel[$i] as $key2 => $val2)
                        {
                            if ($val2 == "\"\"")
                                $asfddcancel[$i][$key2] = '';
                            if (strpos($val2,"\"")!== false)
                                $asfddcancel[$i][$key2] = str_replace("\""," inch",$asfddcancel[$i][$key2]);
                            if (strpos($val2,"'")!== false)
                                $asfddcancel[$i][$key2] = str_replace("'"," inch",$asfddcancel[$i][$key2]);

//                            foreach($val as $key2 => $val2)
//                            {
//                            if ($val2 == "\"\"")
//                                $asfddcancel[$key][$key2] = '';
//                            if (strpos($val2,"\"")!== false)
//                                $asfddcancel[$key][$key2] = str_replace("\""," inch",$asfddcancel[$key][$key2]);
//                            if (strpos($val2,"'")!== false)
//                                $asfddcancel[$key][$key2] = str_replace("'"," inch",$asfddcancel[$key][$key2]);
//                            }
                        }

                        $asfddcancel[$i]['id'] = $id;
                        $asfddcancel[$i]['uom'] = $uom;
                        $asfddcancel[$i]['price'] = $asfddcancel[$i]['harga'];
                        $asfddcancel[$i]['totalPrice'] = $asfddcancel[$i]['total'];
                        $asfddcancel[$i]['totalASF'] = $asfqty;
                        $asfddcancel[$i]['totalPriceASF'] = $asftotal;
                        $asfddcancel[$i]['totalASFCancel'] = $asfcancelqty;
                        $asfddcancel[$i]['totalPriceASFCancel'] = $asfcanceltotal;
                        $asfddcancel[$i]['totalPriceInArfh'] = $inarfhtotal;
                        unset($asfddcancel['harga']);
                        unset($asfddcancel['total']);

                        $hasil['asfddcancel'][] = $asfddcancel[$i];
                    }
                      
//                    }
//                    else
//                    {
//                        foreach ($asfddcancel as $key2 => $val2)
//                        {
//                            if ($val2 == "\"\"")
//                                $asfddcancel[$key2][$val2] = '';
//                        }
//                        foreach ($asfddcancel as $key2 => $val2)
//                        {
//                        $val2['id'] = $id;
//                        $val2['uom'] = $uom;
//                        $val2['price'] = $val2['harga'];
//                        $val2['totalPrice'] = $val2['total'];
//                        $val2['totalASF'] = $asfqty;
//                        $val2['totalPriceASF'] = $asftotal;
//                        $val2['totalASFCancel'] = $asfcancelqty;
//                        $val2['totalPriceASFCancel'] = $asfcanceltotal;
//                        $val2['totalPriceInArfh'] = $inarfhtotal;
//
//                        $hasil['asfddcancel'][] = $val2;
//                        }
//                    }
                }
            $id++;
        }
       
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($hasil);
		$json = str_replace("\\","",$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
//        echo "<pre>"; var_dump($hasil); echo "</pre>";
    }

     public function getpofromisuppAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$columnValue = $this->getRequest()->getParam('data');
    	$sql = "SELECT
    			SQL_CALC_FOUND_ROWS  (a.trano) AS isupp_number, (a.tgl) AS isupp_tgl, (a.urut) AS isupp_urut, a.prj_kode, a.prj_nama, a.sit_kode, a.sit_nama, a.workid, a.workname, a.kode_brg, a.nama_brg, a.val_kode, (a.qty) AS isupp_qty, (a.harga) AS isupp_price, (a.total) AS isupp_total,
    								(b.trano) AS po_number, (b.tgl) AS po_tgl, (b.urut) AS po_urut, (b.qty) AS po_qty, (b.harga) AS po_price, (b.total) AS po_total, (b.pr_no) AS pr_number, (b.tglpr) AS pr_tgl, b.sup_kode, b.sup_nama, b.ket
    			FROM procurement_whsupplierd a LEFT JOIN procurement_pod b
    			ON a.po_no = b.trano AND a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode AND a.kode_brg = b.kode_brg AND a.workid = b.workid
    			WHERE a.trano ='$columnValue'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();
       	$i = 1;
         foreach($return as $key => $value)
        {
            foreach($value as $key2 => $val2)
            {
                if ($val2 == "\"\"")
                {
                    $return[$key][$key2] = '';
                }
            }
            $return[$key]['id'] = $i;
            $i++;
            $return[$key]['uom'] = $this->quantity->getUOMByProductID($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\""," inch",$return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'"," inch",$return[$key]['nama_brg']);

        	$isupp = $this->quantity->getPoIsuppQuantity($return[$key]['po_number'],$return[$key]['prj_kode'],$return[$key]['sit_kode'],$return[$key]['workid'],$return[$key]['kode_brg']);

            if ($isupp != '')
            {
                    $return[$key]['totalISUPP'] = $isupp['qty'];
                    $return[$key]['totalPriceISUPP'] = $isupp['totalHarga'];
                    $return[$key]['balanceISUPP'] = $return[$key]['qty'] - $isupp['qty'];
            }
            else
            {
                    $return[$key]['totalISUPP'] = 0;
                    $return[$key]['balanceISUPP'] = 0;
                    $return[$key]['totalPriceISUPP'] = 0;
            }

            $poh = $this->trans->getPohDetail($return[$key]['po_number']);
            if($poh != '')
            {
                $return[$key]['statusppn'] = $poh['statusppn'];
            }

            $result[] = $return[$key];

        }
            $hasil['posts'] = $result;
            $hasil['count'] = count($return);
             Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($hasil);


        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getdetailafeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
    	$trano = $this->getRequest()->getParam('trano');

        $sql = "SELECT * FROM transengineer_afed WHERE trano = '$trano' ";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();

        $sql2 = "SELECT * FROM transengineer_afeds WHERE trano = '$trano' ";
        $fetch2 = $this->db->query($sql2);
        $return2 = $fetch2->fetchAll();

        $result = array_merge($return, $return2);

        $hasil['posts'] = $result;
        $hasil['count'] = count($result);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($hasil);
    
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getroletypelistAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $sql = "SELECT id AS id_role,role_name FROM master_role_type WHERE active = 1 GROUP BY role_name ORDER BY role_name ASC";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();

        $hasil['posts'] = $return;
        $hasil['count'] = count($return);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($hasil);


        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    function cekArrayPool($item, $key)
    {
//        if ($key == 'trano' && )
    }

    public function searchpoolAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $poolPath = str_replace("/application","/",APPLICATION_PATH);
        $trano = $this->getRequest()->getParam('trano');
        $filePool =  $poolPath . "public/" . $this->getRequest()->getParam('pool');
        if (file_exists( $filePool))
        {
            $isi = file_get_contents($filePool);
            Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::decode($isi);
            foreach($json[posts] as $key => $val)
            {
                if (strpos(strtolower($val['trano']),strtolower($trano)) !== false)
                {
                    $return[] = $json[posts][$key];
                    continue;
                }
            }
            $json2['posts'] = $return;
            $json2['count'] = count($return);
            $hasil = Zend_Json::encode($json2);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($hasil);
        }
        
    }

    public function cekprintAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('trano');
        $cek = $this->workflow->isDocumentValidToPrint($trano);
        if ($cek !== true)
        {
            $hasil = "{success: false,msg: \"$cek\"}";
        }
        else
        {
            $hasil = "{success: true}";
        }
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($hasil);
    }

    public function cekfinalAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('trano');
        $cek = $this->workflow->isDocumentFinal($trano);

        if ($cek !== true)
        {
            $hasil = "{success: false,msg: \"$cek\"}";
        }
        else
        {
            $hasil = "{success: true}";
        }
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($hasil);
    }


    public function ceksubmitAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('trano');
        $cek = $this->workflow->isDocumentValidToSubmit($trano);
        if ($cek !== true)
        {
            $hasil = "{success: false,msg: \"$cek\"}";
        }
        else
        {
            $hasil = "{success: true}";
        }
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($hasil);
    }

    public function cekworkAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $sql = "SELECT w.*,wi.prj_kode AS prj_kode2,wi.sit_kode FROM
					workflow w
					LEFT JOIN
						master_role mr
					ON mr.id = w.master_role_id
					LEFT JOIN
						workflow_item wi
					ON wi.workflow_item_id = w.workflow_item_id
					LEFT JOIN
						workflow_item_type wit
					ON wit.workflow_item_type_id = wi.workflow_item_type_id
				";
		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

        foreach ($result as $key=>$val)
        {
            if ($val['master_role_id'] == '')
                continue;
            if ($val['prj_kode'] == '')
            {

                if ($val['prj_kode2'] != '')
                {
                    $prj_kode = $val['prj_kode2'];
                    $sql3 = "UPDATE workflow
                    SET prj_kode ='$prj_kode'
                    WHERE
                        workflow_id = " . $val['workflow_id'];
                    $fetch3 = $this->db->query($sql3);
                }
            }
            if ($val['uid'] == '')
            {
                 $sql2 = "SELECT ml.uid,ml.id FROM
					master_login ml
					LEFT JOIN
						master_role mr
					ON ml.id = mr.id_user
                    WHERE
                        mr.id = " . $val['master_role_id'];
                $fetch2 = $this->db->query($sql2);
                $result2 = $fetch2->fetch();
                if ($result2['uid'] != '')
                {
                    $uid = $result2['uid'];
                    $sql3 = "UPDATE workflow
                    SET uid ='$uid'
                    WHERE
                        workflow_id = " . $val['workflow_id'];
                    $fetch3 = $this->db->query($sql3);
                }
                else
                    echo "gak ada :" . $result2['id'] . "\n";
            }
            if ($val['uid_prev'] == '' && $val['prev'] != 0 && $val['is_start'] != 1)
            {
                $sql2 = "SELECT * FROM
					workflow_structure
                    WHERE
                        id = " . $val['prev'];
                $fetch2 = $this->db->query($sql2);
                $result2 = $fetch2->fetch();
                if ($result2['master_role_id'] == '')
                    continue;
                 $sql2 = "SELECT ml.uid,ml.id FROM
					master_login ml
					LEFT JOIN
						master_role mr
					ON ml.id = mr.id_user
                    WHERE
                        mr.id = " . $result2['master_role_id'];
                $fetch2 = $this->db->query($sql2);
                $result2 = $fetch2->fetch();
                if ($result2['uid'] != '')
                {
                    $uid = $result2['uid'];
                    $sql3 = "UPDATE workflow
                    SET uid_prev ='$uid'
                    WHERE
                        workflow_id = " . $val['workflow_id'];
                    $fetch3 = $this->db->query($sql3);
                }
                else
                    echo "gak ada :" . $result2['id'] . "\n";
            }
            if ($val['uid_next'] == '' && $val['next'] != 0 && $val['is_final'] != 1)
            {
                $sql2 = "SELECT * FROM
					workflow_structure
                    WHERE
                        id = " . $val['next'];
                $fetch2 = $this->db->query($sql2);
                $result2 = $fetch2->fetch();
                if ($result2['master_role_id'] == '')
                    continue;
                 $sql2 = "SELECT ml.uid,ml.id FROM
					master_login ml
					LEFT JOIN
						master_role mr
					ON ml.id = mr.id_user
                    WHERE
                        mr.id = " . $result2['master_role_id'];
                $fetch2 = $this->db->query($sql2);
                $result2 = $fetch2->fetch();
                if ($result2['uid'] != '')
                {
                    $uid = $result2['uid'];
                    $sql3 = "UPDATE workflow
                    SET uid_next ='$uid'
                    WHERE
                        workflow_id = " . $val['workflow_id'];
                    $fetch3 = $this->db->query($sql3);
                }
                else
                    echo "gak ada :" . $result2['id'] . "\n";
            }

            if ($val['prj_kode'] == '')
            {

                if ($val['prj_kode2'] != '')
                {
                    $prj_kode = $val['prj_kode2'];
                    $sql3 = "UPDATE workflow
                    SET prj_kode ='$prj_kode'
                    WHERE
                        workflow_id = " . $val['workflow_id'];
                    $fetch3 = $this->db->query($sql3);
                }
            }

            $sql2 = "SELECT * FROM workflow_trans WHERE workflow_id = " . $val['workflow_id'];
            $fetch2 = $this->db->query($sql2);
            $result2 = $fetch2->fetchAll();

            if ($result2)
            {
                foreach($result2 as $key3 => $val3)
                {
                    if ($val3['workflow_item_id'] == '' || $val3['workflow_item_id'] == 0 )
                    {
                        $item_id = $val['workflow_item_id'];
                        $sql3 = "UPDATE workflow_trans
                        SET workflow_item_id ='$item_id'
                        WHERE
                            workflow_trans_id = " . $val3['workflow_trans_id'];
                        $fetch3 = $this->db->query($sql3);
                    }
                    if ($val3['prj_kode'] == '')
                    {
                        $prj_kode = $val['prj_kode2'];
                        $sql3 = "UPDATE workflow_trans
                        SET prj_kode ='$prj_kode'
                        WHERE
                            workflow_trans_id = " . $val3['workflow_trans_id'];
                        $fetch3 = $this->db->query($sql3);
                    }
                }
            }

        }
    }

    public function cekpaymentAction()
    {
       $this->_helper->viewRenderer->setNoRender();
       $type = $this->getRequest()->getParam('type');
       $trano = $this->getRequest()->getParam('trano');

       $cek = $this->trans->cekPayment($type,$trano);

        if($cek == 'Y')
        {
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: false, msg:\"this transaction can not be edited because already paid\"}");
            
        }
        else
        {
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: true}");
        }
    }

    public function cekasfAction()
    {
       $this->_helper->viewRenderer->setNoRender();
       $type = $this->getRequest()->getParam('type');
       $trano = $this->getRequest()->getParam('trano');

       $cek = $this->trans->cekASF($trano,$type);

        if($cek != 'exist')
        {
            if ($type == 'settle')
            {
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: false, msg:\"this ASF Number doesn't have any Transaction For Closed Settlement<br><br>please use Return Settlement transaction\"}");
            }
            else if ($type == 'cancel')
            {
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: false, msg:\"this ASF Number doesn't have any Transaction For Return Settlement<br><br>please use Closed Settlement transaction\"}");
            }

        }
        else
        {
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: true}");
        }
    }

    public function getpricehistoryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $kodeBrg = $this->getRequest()->getParam('kode_brg');

        $sql = "SELECT DATE_FORMAT(tgl,'%Y-%m-%d') as tgl,kode_brg,nama_brg,prj_kode,prj_nama,sit_kode,sit_nama,harga,petugas,sup_kode,sup_nama,val_kode FROM procurement_pod where kode_brg = '$kodeBrg' order by tgl desc LIMIT 5";
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

    public function checkdocumentAction()
    {

    }

    public function getlistdocumentbytypeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $type = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $model = $this->_helper->getHelper('model');
        $class = $model->getModelClass($type);

        if ($class !== false)
        {
            $prikey = $class->getPrimaryKey();
            $sort = $prikey;
            if ($trano != '' || $prj_kode != '' || $sit_kode != '')
            {
                if ($trano)
                    $query = "$prikey LIKE '%$trano%'";
                if ($prj_kode)
                {
                    if ($query)
                        $query .= " AND prj_kode LIKE '%$prj_kode%'";
                    else
                        $query = "prj_kode LIKE '%$prj_kode%'";    
                }
                if ($sit_kode)
                {
                    if ($query)
                        $query .= " AND sit_kode LIKE '%$sit_kode%'";
                    else
                        $query = "sit_kode LIKE '%$sit_kode%'";       
                }
            }

            $data = $class->fetchAll($query, array($sort . ' ' . $dir), $limit, $offset)->toArray();
            if ($type == 'SUPP')
            {
                foreach($data as $key => $val)
                {
                    $tmp = $val['sup_kode'];
                    unset($data[$key]['sup_kode']);
                    $data[$key]['trano'] = $tmp;
                }
            }
            $return['posts'] = $data;
            $return['count'] = $class->fetchAll()->count();
        }
        else
        {
            $return['posts'] = array();
            $return['count'] = 0;
        }

        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showcheckdocumentAction()
    {
        $trano = $this->getRequest()->getParam('trano');
        $data = $this->workflowTrans->fetchAll("item_id = '$trano'", array("date ASC"))->toArray();
        $ldap = new Default_Models_Ldap();

        $this->view->lastTransId = $data[(count($data) -1)]['workflow_trans_id'];
        $this->view->type = $data[(count($data) -1)]['item_type'];
        $lastUid = $data[(count($data) -1)]['uid'];
        $lastWorkflow = $data[(count($data) -1)]['workflow_id'];
        $lastWorkflowid = $data[(count($data) -1)]['workflow_item_id'];
        $lastStructureid = $data[(count($data) -1)]['workflow_structure_id'];
        $lastApprove = $data[(count($data) -1)]['approve'];

        if ($lastWorkflow && $lastWorkflowid && $lastStructureid)
        {
            if ($lastApprove != $this->const['DOCUMENT_FINAL'] && $lastApprove != $this->const['DOCUMENT_EXECUTE'])
            {
                if ($lastApprove != $this->const['DOCUMENT_REJECT'])
                {
                    $last = $this->workflowClass->fetchRow("uid = '$lastUid' AND workflow_item_id = $lastWorkflowid");
                    if ($last)
                    {
                        $next = $last['uid_next'];
                        $othername = $ldap->getAccount($next);
                        $nextName = $othername['displayname'][0];

                        $this->view->msg = "Waiting approval from $nextName.";
                    }
                }
            }
        }
        foreach($data as $key => &$val)
        {
            $othername = $ldap->getAccount($val['uid']);
            $name = $othername['displayname'][0];
            switch ($val['approve'])
            {
                case $this->const['DOCUMENT_APPROVE']: $val['approve'] = '<b><font color="green">Approved</font></b>';
                    break;
                case $this->const['DOCUMENT_SUBMIT']: $val['approve'] = '<b>Submitted</b>';
                    break;
                case $this->const['DOCUMENT_RESUBMIT']: $val['approve'] = '<b>Resubmitted</b>';
                    break;
                case $this->const['DOCUMENT_REJECT']: $val['approve'] = '<b><font color="red">Rejected</font></b>';
                    break;
                case $this->const['DOCUMENT_FINAL']: $val['approve'] = '<b><font color="blue">Final Approval</font></b>';
                    break;
                case $this->const['DOCUMENT_EXECUTE']: $val['approve'] = '<b><font color="blue">Executed</font></b>';
                    break;
                default: $val['approve'] = '';
                    break;
            }
            $val['date'] = date("D, d/m/Y H:i:s",strtotime($val['date']));
            $data[$key]['name'] = $name;
            if ($data[$key]['comment'] == '')
                $data[$key]['comment'] = "&nbsp;";
            $data[$key]['comment'] = str_replace("\r","<br />",$val['comment']);
            $data[$key]['comment'] = str_replace("\n","<br />",$val['comment']);
        }

        $this->view->data = $data;
    }

    public function homepageAction()
    {
        // action body
        $uid = $this->session->userName ;
        $ldapdir = new Default_Models_Ldap();
        $account = $ldapdir->getAccount($uid);
        $accountImage = $ldapdir->getAccountImage($uid);
        $this->view->accountImage = $accountImage;
        $this->view->mail = $this->session->mail;
//        $glpi = new Admin_Models_Glpi();
//        $assetGlpi = $glpi->getAsset($uid);
//        $this->view->assetGlpi = $assetGlpi;

        $credential = new Admin_Model_CredentialTrans();
        $docs = $credential->getOutstandingDocs($uid);

        $this->view->docs = count($docs);

        $fetch = $this->workflowClass->fetchAll("uid = '$uid' AND is_start = 1");
        if ($fetch)
        {
            $this->view->isSubmitter = true;
        }

        $workflowGeneric = new Admin_Model_Workflowgeneric();
        $masterRole = new Admin_Model_Masterrole();
        $timesheet = new ProjectManagement_Models_Timesheet();

        $roles = $masterRole->getAllMyRole($this->session->idUser);
        $showTabs = false;
        if ($roles)
        {
            $retArray = array();
            $prjArray = array();
            foreach ($roles as $k => $v)
            {
                //Buat Tabs...
                $role = $v['display_name'];
                if ($role == 'Project Manager' || $role == 'Project Control Senior Officer' )
                {
                    $showTabs = true;
                }

                if ($role == 'Project Manager')
                {
                    $prjArray[] = "'" . $v['prj_kode'] . "'" ;
                }
            }
            $prjSearch = implode(",",$prjArray);
            if ($prjSearch)
            {
                $myWork = $workflowGeneric->getWorkflowByUserID($this->session->idUser,'TSHEET'," AND prj_kode IN ($prjSearch) AND level > 0 GROUP BY prj_kode");
                if ($myWork)
                {
                    foreach($myWork as $k => $v)
                    {
                        $prjKode = $v['prj_kode'];
                        $sum = $timesheet->getSummaryTimesheetPerProject($prjKode,true,false);
                        $retArray[] = array(
                            "prj_kode" => $prjKode,
                            "total" => $sum['total']
                        );
                    }

                    $json = Zend_Json::encode($retArray);
                    $this->view->timesheetTotal = $json;
                }
            }

            $this->view->showTabs = $showTabs;

            //Notif jumlah site yg telat progress na...
            $userID = $this->session->idUser;
            $userrole = new Admin_Model_Userrole();
            $sites = new Default_Models_MasterSite();
            $myproject = $userrole->getCurrentProject($userID,true);

            $cronBudget = new Default_Models_CronBudget();

            foreach($myproject as $k => $v)
            {
                $prjKode = $v['prj_kode'];
                $cek = $cronBudget->fetchRow("prj_kode = '$prjKode'");
                if ($cek)
                {
                    $jumSite += $sites->fetchAll("prj_kode = '$prjKode'")->count();
                    $scurveProj = $cronBudget->fetchAll("prj_kode = '$prjKode'")->toArray();
                    foreach($scurveProj as $k2 => $v2)
                    {
                        $jsonDecode = Zend_Json::decode($v2['data']);
                        $diffStart = $jsonDecode['DIFF_WEEK_START_BOQ3_TO_MIP'];
                        $diffEnd = $jsonDecode['DIFF_WEEK_END_BOQ3_TO_MIP'];
                        if ($diffStart > 0 && $diffEnd > 0)
                            $jumSiteTelat++;
                    }
                }
            }

            $this->view->jumSite = $jumSite;
            $this->view->jumSiteTelat = $jumSiteTelat;
        }


    }

    public function getmylastsubmitdocumentAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->session->userName;

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS myDocs;
            CREATE TEMPORARY TABLE myDocs
            SELECT * FROM ( SELECT * FROM workflow_trans WHERE uid = '$uid' AND approve IN (100,150) ORDER BY date DESC ) a GROUP BY a.item_id LIMIT 10;

            DROP TEMPORARY TABLE IF EXISTS myDocs2;
            CREATE TEMPORARY TABLE myDocs2
            SELECT b.*,a.uid as appUid, a.date as appDate FROM ( SELECT * FROM workflow_trans WHERE approve IN (400,500) GROUP BY item_id) a
            RIGHT JOIN myDocs b ON a.item_id = b.item_id AND a.workflow_item_id = b.workflow_item_id WHERE a.item_id is NOT null AND b.item_id is not null;

        ";
        $this->db->query($sql);
        $sql = "SELECT item_id,appDate,appUid FROM myDocs2;";
        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        $ldap = new Default_Models_Ldap();
        foreach($hasil as $key => &$val)
        {
            $other = $ldap->getAccount($val['appUid']);
            $name = $other['displayname'][0];
            $hasil[$key]['appName'] = $name;
            $val['appDate'] = date('d-m-Y',strtotime($val['appDate']));
        }

        $return['posts'] = $hasil;
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showdocssubmitAction()
    {
        $trano = $this->getRequest()->getParam('trano');
        $type = $this->getRequest()->getParam('type');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $order = $this->getRequest()->getParam('orderby');
        $final = $this->getRequest()->getParam('hidefinal');
        $reject = $this->getRequest()->getParam('hidereject');

        if ($order == '')
            $order = 'item_id';
        $ldap = new Default_Models_Ldap();

        $uid = $this->session->userName;

        $limit = 30;
        $current = $this->getRequest()->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->getRequest()->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;

        $offset = $current - 1;

//        if ($final != 'false' || $reject != 'false')
//        {
//            if ($trano != '')
//                $query = "AND item_id LIKE '%$trano%'";
//            if ($type != '')
//               $query .= " AND item_type = '$type'";
//            if ($prj_kode != '')
//               $query .= " AND prj_kode LIKE '%$prj_kode%'";
//
//            if ($final == 'true')
//                $query2 = '400,500';
//            if ($reject == 'true')
//            {
//                if ($query2 == '')
//                    $query2 = '300';
//                else
//                    $query2 .= ',300';
//            }
//            $sql = "
//            DROP TEMPORARY TABLE IF EXISTS myDocs;
//            CREATE TEMPORARY TABLE myDocs
//            SELECT * FROM ( SELECT * FROM workflow_trans WHERE uid = '$uid' AND approve IN (100,150) $query ORDER BY date DESC ) a GROUP BY a.item_id LIMIT 10;
//
//            DROP TEMPORARY TABLE IF EXISTS myDocs2;
//            CREATE TEMPORARY TABLE myDocs2
//            SELECT b.* FROM ( SELECT * FROM workflow_trans WHERE approve NOT IN ($query2) GROUP BY item_id) a
//            RIGHT JOIN myDocs b ON a.item_id = b.item_id AND a.workflow_item_id = b.workflow_item_id WHERE a.item_id is NOT null AND b.item_id is not null;
//
//            ";
//
//            $this->db->query($sql);
//            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM myDocs2 a ORDER BY a.$order DESC LIMIT $offset,$limit";
//        }
//        else
//        {
            if ($trano == '' && $type == '' && $prj_kode == '')
            {
                if ($order != 'date_approve' && $order != 'date_approve_final')
                    $sql = "
                    CREATE TEMPORARY TABLE my_submit
                    SELECT * FROM
                    (
                        SELECT * FROM workflow_trans
                        WHERE uid = '$uid'
                        AND approve IN (100,150)
                        ORDER BY date ASC
                    ) a
                    GROUP BY a.item_id
                    ORDER BY a.$order DESC
                    ";
                else
                {
                    if ($order == 'date_approval')
                        $in = "(200,400)";
                    else
                        $in = "(400)";
                    $sql = "
                    CREATE TEMPORARY TABLE my_submit
                    SELECT * FROM
                    (
                        SELECT z.*,y.date as dateapprove
                        FROM
                        (
                            SELECT * FROM workflow_trans
                            WHERE uid = '$uid'
                            AND approve IN (100,150) ORDER BY date ASC
                        ) z
                        LEFT JOIN workflow_trans y
                        ON z.item_id = y.item_id
                        WHERE y.approve IN $in
                        ORDER BY y.date desc
                    ) a
                    GROUP BY a.item_id
                    ORDER BY a.dateapprove DESC";

                }
            }
            else
            {
                if ($trano != '')
                    $query = "AND item_id LIKE '%$trano%'";
                if ($type != '')
                   $query .= " AND item_type = '$type'";
                if ($prj_kode != '')
                   $query .= " AND prj_kode LIKE '%$prj_kode%'";

                if ($order != 'date_approve' && $order != 'date_approve_final')
                    $sql = "
                    CREATE TEMPORARY TABLE my_submit
                    SELECT * FROM
                    (
                        SELECT * FROM workflow_trans
                        WHERE uid = '$uid'
                        AND approve IN (100,150)
                        $query
                        ORDER BY date ASC
                    ) a
                    GROUP BY a.item_id
                    ORDER BY a.$order DESC";
                else
                {
                    if ($order == 'date_approval')
                        $in = "(200,400)";
                    else
                        $in = "(400)";
                    $sql = "
                    CREATE TEMPORARY TABLE my_submit
                    SELECT * FROM
                    (
                        SELECT z.*,y.date as dateapprove FROM
                        (
                            SELECT * FROM workflow_trans
                            WHERE uid = '$uid'
                            AND approve IN (100,150)
                            $query
                            ORDER BY date ASC
                        ) z
                        LEFT JOIN workflow_trans y
                        ON z.item_id = y.item_id
                        WHERE y.approve IN $in
                        ORDER BY y.date DESC
                    ) a
                    GROUP BY a.item_id
                    ORDER BY a.dateapprove DESC";

                }
            }
//        }
        $fetch = $this->db->query($sql);

        if ($final != 'false')
        {
            $where[] = "b.approve NOT IN (400,500)";
        }
        if ($reject != 'false')
        {
            $where[] = "b.approve != 300";
        }

        if (count($where) > 0)
        {
            $kriteria = "WHERE " . implode(" AND ",$where);
        }

        $sql = "
            CREATE TEMPORARY TABLE my_submit_approval
              SELECT * FROM (
              SELECT b.* FROM my_submit a
              LEFT JOIN workflow_trans b
              ON a.item_id = b.item_id
              ORDER BY b.date DESC
            ) z
            GROUP BY z.item_id
            ";
        $fetch = $this->db->query($sql);

        $sql = "
            SELECT SQL_CALC_FOUND_ROWS a.*,b.approve AS lastApprove,b.date AS lastDate,b.uid AS uidApprove FROM my_submit a
            LEFT JOIN my_submit_approval b
            ON a.item_id = b.item_id
            $kriteria
            LIMIT $offset,$limit
            ";
        $fetch = $this->db->query($sql);

        if ($fetch)
        {
            $hasil = $fetch->fetchAll();
            $totalResult = $this->db->fetchOne('SELECT FOUND_ROWS()');
            $this->view->supplier = false;
            foreach ($hasil as $key => &$val)
            {
                $tranos = $val['item_id'];

                if ($type == 'POO' || $type == 'PO' || $type == 'RPI' || $type == 'RPIO' || $type == 'SUPP')
                {
                    $models = $this->_helper->getHelper('model');
                    $newModel = $models->getModelClass($type);
                    $PK = $newModel->getPrimaryKey();
                    $fetch = $newModel->fetchRow("$PK = '$tranos'");
                    if ($fetch)
                    {
                        $hasil[$key]['supplier'] = $fetch['sup_nama'];
                        $this->view->supplier = true;
                    }
                }
                $val['date'] = date('d M Y H:i:s',strtotime($val['date']));
                $val['comment'] = str_replace("\"",'',$val['comment']);
                $val['comment'] = str_replace("\n",'',$val['comment']);
                $val['comment'] = str_replace("\t",'',$val['comment']);
                $val['comment'] = str_replace("\r",'',$val['comment']);
                $val['comment'] = str_replace("'",'',$val['comment']);


                $lastApprove = $val['lastApprove'];
                $lastUid = $val['uidApprove'];
                $othername = $ldap->getAccount($lastUid);
                $nextName = $othername['displayname'][0];
                if ($lastApprove == $this->const['DOCUMENT_APPROVE'])
                {
                    $hasil[$key]['msg'] = "Waiting approval from $nextName.";
                }
                else if ($lastApprove == $this->const['DOCUMENT_FINAL'] || $lastApprove == $this->const['DOCUMENT_EXECUTE'])
                {
                        $hasil[$key]['msg'] = "Final Approval By $nextName.";
                }
                else if ($lastApprove == $this->const['DOCUMENT_REJECT'])
                {
                        $hasil[$key]['msg'] = "Rejected By $nextName.";
                }
            }
        }

//        if ($fetch)
//        {
//            $hasil = $fetch->fetchAll();
//            $totalResult = $this->db->fetchOne('SELECT FOUND_ROWS()');
//            $this->view->supplier = false;
//            foreach ($hasil as $key => &$val)
//            {
//                $tranos = $val['item_id'];
//
//                if ($type == 'POO' || $type == 'PO' || $type == 'RPI' || $type == 'RPIO' || $type == 'SUPP')
//                {
//                    $models = $this->_helper->getHelper('model');
//                    $newModel = $models->getModelClass($type);
//                    $PK = $newModel->getPrimaryKey();
//                    $fetch = $newModel->fetchRow("$PK = '$tranos'");
//                    if ($fetch)
//                    {
//                        $hasil[$key]['supplier'] = $fetch['sup_nama'];
//                        $this->view->supplier = true;
//                    }
//                }
//                $val['date'] = date('d M Y H:i:s',strtotime($val['date']));
//                $val['comment'] = str_replace("\"",'',$val['comment']);
//                $val['comment'] = str_replace("\n",'',$val['comment']);
//                $val['comment'] = str_replace("\t",'',$val['comment']);
//                $val['comment'] = str_replace("\r",'',$val['comment']);
//                $val['comment'] = str_replace("'",'',$val['comment']);
//                $sql = "SELECT * FROM workflow_trans WHERE item_id = '$tranos' ORDER BY date DESC LIMIT 1";
//                $fetch2 = $this->db->query($sql);
//                $fetch2 = $fetch2->fetch();
//
//                $lastApprove = $fetch2['approve'];
//                $lastUid = $fetch2['uid'];
//                if ($lastApprove == $this->const['DOCUMENT_APPROVE'])
//                {
//                        $othername = $ldap->getAccount($lastUid);
//                        $nextName = $othername['displayname'][0];
//
//                        $hasil[$key]['msg'] = "Waiting approval from $nextName.";
//                }
//                else if ($lastApprove == $this->const['DOCUMENT_FINAL'] || $lastApprove == $this->const['DOCUMENT_EXECUTE'])
//                {
//                    if ($final == 'false'|| $reject == '')
//                    {
//                        $next = $fetch2['uid'];
//                        $othername = $ldap->getAccount($next);
//                        $nextName = $othername['displayname'][0];
//
//                        $hasil[$key]['msg'] = "Final Approval By $nextName.";
//                    }
//                    else
//                    {
//                        unset($hasil[$key]);
//                        $totalResult -= 1;
//                    }
//                }
//                else if ($lastApprove == $this->const['DOCUMENT_REJECT'])
//                {
//                    if ($reject == 'false' || $reject == '')
//                    {
//                        $next = $fetch2['uid'];
//                        $othername = $ldap->getAccount($next);
//                        $nextName = $othername['displayname'][0];
//
//                        $hasil[$key]['msg'] = "Rejected By $nextName.";
//                    }
//                    else
//                    {
//                        unset($hasil[$key]);
//                        $totalResult -= 1;
//                    }
//                }
//            }
//        }

        $this->view->finalapp = $final;
        $this->view->rejectapp = $reject;
        $this->view->order = $order;
        $this->view->totalResult = $totalResult;
        $this->view->current = $current;
        $this->view->limit = $limit;
        $this->view->currentPage = $currentPage;
        $this->view->totalPage = ceil($totalResult / $limit);
        $this->view->result = $hasil;
        $this->view->trano = $trano;
        $this->view->type = $type;
        $this->view->prjKode = $prj_kode;
    }

    public function showoutstandingdocsAction()
    {
        $uid = $this->session->userName;
        $credential = new Admin_Model_CredentialTrans();
        $docs = $credential->getOutstandingDocs($uid);

//        foreach ($docs as $key => $val)
//        {
//            $data = Zend_Json::decode($val['data']);
//            $type = '';
//            $maks = count($data) - 1;
//            $i = 0;
//            while ($type == '' && $i <= $maks)
//            {
//                $type = $data[$i]['item_type'];
//                $i++;
//            }
//           $docs[$key]['item_type'] = $type;
//        }
        $this->view->result = $docs;
    }

    public function getboq2Action()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');

        $boq2 = new Default_Models_MasterBoq2();
        $fetch = $boq2->fetchRow("prj_kode='$prj_kode' AND sit_kode='$sit_kode'");
        if ($fetch)
        {
            $return['posts'] = array(
                "total" => $fetch['total'],
                "totalusd" => $fetch['totalusd'],
                "grandtotal" => (floatval($fetch['total']) + (floatval($fetch['totalusd']) * floatval($fetch['rateidr'])))
            );
            $return['success'] = true;
            Zend_Loader::loadClass('Zend_Json');
        }
        else
            $return['success'] = false;

        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getmymanagerAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $pa = new Admin_Model_PersonalAssistant();
        $myUid = $this->session->userName;
        $fetchPa = $pa->fetchAll("uid = '$myUid'");
        if ($fetchPa)
        {
            $fetchPa = $fetchPa->toArray();
            $ldap = new Default_Models_Ldap();
            $manager = array();
            foreach($fetchPa as $k => $v)
            {
                $acc = $ldap->getAccount($v['uid_manager']);
                $manager['posts'][] = array( "name" => $acc['displayname'][0],"manager_uid" => $v['uid_manager']);
            }

            $json = Zend_Json::encode($manager);

            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
        }
    }

}

