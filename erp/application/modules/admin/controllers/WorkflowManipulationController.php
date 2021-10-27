<?php

class Admin_WorkflowManipulationController extends Zend_Controller_Action
{
    private $ADMIN;
    private $db;
    public function init()
    {
        $this->db = Zend_Registry::get("db");
        $this->ADMIN = QDC_Model_Admin::init(array(
            "Masterlogin",
            "WorkflowItemType",
            "Workflowitem",
            "Workflowstructure",
            "Workflow",
            "Masterrole",
            "Workflowtrans",
        ));
    }

    public function removePersonAction()
    {

    }

    public function doRemovePersonAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = ($this->_getParam("json") != '') ? Zend_Json::decode($this->_getParam("json")) : false;
        $uid = $this->_getParam("uid");
        $workflow_type_id = $this->_getParam("workflow_type_id");

        $userTarget = $this->db->fetchRow($this->db->select()->from(array($this->ADMIN->Masterlogin->__name()))->where("uid=?",$uid));
        $userIDTarget = $userTarget['id'];
        $uidTarget = $userTarget['uid'];

        $arrayLog = array();
        $return = array(
            "success" => false
        );

        if (!$json)
        {
            $arrayProses = array(
                array(
                "workflow_item_type_id" => $workflow_type_id,
                "prj_kode" => ''
            ));
        }
        else
            $arrayProses = $json;

        foreach($arrayProses as $key => $val)
        {
            $workflow_type_id = $val['workflow_item_type_id'];
            $prj_kode = $val['prj_kode'];

            $select = $this->db->select()
                ->from(array($this->ADMIN->Workflowitem->__name()))
                ->where("workflow_item_type_id=?",$workflow_type_id);

            if ($prj_kode)
                $select = $select->where("prj_kode = ?",$prj_kode);

            //Get all workflow_item_id
            $w = $this->db->fetchAll($select);

            foreach($w as $k => $v)
            {
                $wItemId = $v['workflow_item_id'];
                $prjKode = $v['prj_kode'];

                $select = $this->db->select()
                    ->from(array($this->ADMIN->Masterrole->__name()))
                    ->where("id_user=?",$userIDTarget)
                    ->where("prj_kode=?",$prjKode);

                $select = $this->db->select()
                    ->from(array("a" => $select))
                    ->joinLeft(array("b" => $this->ADMIN->Workflowstructure->__name()),"a.id = b.master_role_id")
                    ->where("b.workflow_item_id=?",$wItemId);

                $w2 = $this->db->fetchAll($select);

                if ($w2)
                {
                    foreach($w2 as $k2 => $v2)
                    {
                        //Cek jumlah level structure
                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflowstructure->__name()),array(
                                "total_level" => new Zend_Db_Expr("MAX(level)+1")
                            ))
                            ->where("workflow_item_id=?",$wItemId);
                        $cek = $this->db->fetchOne($select);

                        if ($cek <= 2)
                        {
                            $arrayLog[] = "Structure for $prjKode, workflow item id : $wItemId cannot be modified, reason : MAX level only $cek";
                            continue;
                        }

                        $wStructId = $v2['id'];
                        $level = $v2['level'];
                        $prevLevel = $level-1;

                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflowstructure->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("level > ?",$level)
                            ->order(array("level ASC"));
                        $l = $this->db->fetchAll($select);

                        if ($l)
                        {
                            foreach($l as $k3 => $v3)
                            {
                                if ($v2['master_role_id'] != $v3['master_role_id'])
                                {
                                    $wStructIdNext = $v3['id'];
                                    $cLevel = $v3['level'] - 1;
                                    $arrayLog[] = "Updating structure $prjKode : " . $v3['level'] . " > " . $cLevel;
                                    $this->ADMIN->Workflowstructure->update(array(
                                        "level" => $cLevel
                                    ),"workflow_item_id = $wItemId and id = $wStructIdNext");
                                }
                            }
                        }

                        //Delete structure
                        $this->ADMIN->Workflowstructure->delete("workflow_item_id = $wItemId and id = $wStructId");
                        $arrayLog[] = "Deleting structure $prjKode";

                        //WOrkflow Route

                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflow->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("uid_next=?",$uidTarget);

                        $wNext = $this->db->fetchAll($select);
                        foreach($wNext as $k3 => $v3)
                        {
                            $wfId = $v3['workflow_id'];
                            $wsId = $v3['workflow_structure_id'];
                            //Get current level structure
                            $select = $this->db->select()
                                ->from(array($this->ADMIN->Workflowstructure->__name()))
                                ->where("workflow_item_id=?",$wItemId)
                                ->where("id=?",$wsId);
                            $currWs = $this->db->fetchRow($select);
                            if ($currWs)
                            {
                                $cLevel = $currWs['level'] + 1;
                                $select = $this->db->select()
                                    ->from(array($this->ADMIN->Workflowstructure->__name()),array(
                                        "id",
                                        "master_role_id"
                                    ))
                                    ->where("workflow_item_id=?",$wItemId)
                                    ->where("level=?",$cLevel);

                                $select = $this->db->select()
                                    ->from(array("a" => $select))
                                    ->joinLeft(array("b" => $this->ADMIN->Masterrole->__name()),"a.master_role_id = b.id",array(
                                        "id_user"
                                    ));

                                $select = $this->db->select()
                                    ->from(array("c" => $select))
                                    ->joinLeft(array("d" => $this->ADMIN->Masterlogin->__name()),"c.id_user = d.id",array(
                                        "uid"
                                    ));

                                $nextWs = $this->db->fetchRow($select);
                                if ($nextWs)
                                {
                                    $arrayLog[] = "Updating workflow next $prjKode";
                                    $this->ADMIN->Workflow->update(array(
                                        "next" => $nextWs['id'],
                                        "uid_next" => $nextWs['uid']
                                    ),"workflow_id=$wfId");
                                }
                                else
                                {
                                    $arrayLog[] = "Workflow not updating, reason : cannot find workflow_next : {$nextWs['id']}, workflow_next_uid : {$nextWs['uid']}";
                                }
                            }
                            else
                            {
                                $arrayLog[] = "Cannot find Structure for workflow_item_id : $wItemId, structure_id : $wsId";
                            }
                        }

                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflow->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("uid_prev=?",$uidTarget);

                        $wNext = $this->db->fetchAll($select);
                        foreach($wNext as $k3 => $v3)
                        {
                            $wfId = $v3['workflow_id'];
                            $wsId = $v3['workflow_structure_id'];
                            //Get current level structure
                            $select = $this->db->select()
                                ->from(array($this->ADMIN->Workflowstructure->__name()))
                                ->where("workflow_item_id=?",$wItemId)
                                ->where("id=?",$wsId);
                            $currWs = $this->db->fetchRow($select);
                            if ($currWs)
                            {
                                $cLevel = $currWs['level'] - 1;
                                $select = $this->db->select()
                                    ->from(array($this->ADMIN->Workflowstructure->__name()),array(
                                        "id",
                                        "master_role_id"
                                    ))
                                    ->where("workflow_item_id=?",$wItemId)
                                    ->where("level=?",$cLevel);

                                $select = $this->db->select()
                                    ->from(array("a" => $select))
                                    ->joinLeft(array("b" => $this->ADMIN->Masterrole->__name()),"a.master_role_id = b.id",array(
                                        "id_user"
                                    ));

                                $select = $this->db->select()
                                    ->from(array("c" => $select))
                                    ->joinLeft(array("d" => $this->ADMIN->Masterlogin->__name()),"c.id_user = d.id",array(
                                        "uid"
                                    ));

                                $nextWs = $this->db->fetchRow($select);
                                if ($nextWs)
                                {
                                    $arrayLog[] = "Updating workflow prev $prjKode";
                                    $this->ADMIN->Workflow->update(array(
                                        "prev" => $nextWs['id'],
                                        "uid_prev" => $nextWs['uid']
                                    ),"workflow_id=$wfId");
                                }
                                else
                                {
                                    $arrayLog[] = "Workflow not updating, reason : cannot find workflow_prev : {$nextWs['id']}, workflow_prev_uid : {$nextWs['uid']}";
                                }
                            }
                            else
                            {
                                $arrayLog[] = "Cannot find Structure for workflow_item_id : $wItemId, structure_id : $wsId";
                            }
                        }

                        $this->ADMIN->Workflow->delete("workflow_item_id = $wItemId AND uid= '$uidTarget'");
                        $arrayLog[] = "Deleting workflow $prjKode";

                        //WOrkflow Transaction

                        //Delete workflow yg lagi di handle oleh target...
                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflowtrans->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("final=0")
                            ->order(array("date DESC"));
                        $select = $this->db->select()
                            ->from(array("a" => $select))
                            ->group(array("item_id"));
                        $select = $this->db->select()
                            ->from(array("b" => $select))
                            ->where("uid = ?",$uidTarget);
                        $wNext = $this->db->fetchAll($select);

                        if ($wNext)
                        {
                            foreach($wNext as $k3 => $v3)
                            {
                                $trano = $v3['item_id'];
                                $this->ADMIN->Workflowtrans->delete("workflow_trans_id = {$v3['workflow_trans_id']}");
                                $arrayLog[] = "Deleting workflow trans current $prjKode $trano";
                            }
                        }

                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflowtrans->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("final=0")
                            ->order(array("date DESC"));
                        $select = $this->db->select()
                            ->from(array("a" => $select))
                            ->group(array("item_id"));
                        $select = $this->db->select()
                            ->from(array("b" => $select))
                            ->where("uid_next = ?",$uidTarget);

                        $wNext = $this->db->fetchAll($select);

                        if ($wNext)
                        {
                            foreach($wNext as $k3 => $v3)
                            {
                                $trano = $v3['item_id'];
                                $ws = $v3['workflow_structure_id'];
                                $wid = $v3['workflow_trans_id'];
                                $select = $this->db->select()
                                    ->from(array($this->ADMIN->Workflowstructure->__name()))
                                    ->where("workflow_item_id=?",$wItemId)
                                    ->where("id=?",$ws);

                                $currWs = $this->db->fetchRow($select);
                                if ($currWs)
                                {
                                    $cLevel = $currWs['level'] + 1;
                                    $select = $this->db->select()
                                        ->from(array($this->ADMIN->Workflowstructure->__name()),array(
                                            "id",
                                            "master_role_id"
                                        ))
                                        ->where("workflow_item_id=?",$wItemId)
                                        ->where("level=?",$cLevel);

                                    $select = $this->db->select()
                                        ->from(array("a" => $select))
                                        ->joinLeft(array("b" => $this->ADMIN->Masterrole->__name()),"a.master_role_id = b.id",array(
                                            "id_user"
                                        ));

                                    $select = $this->db->select()
                                        ->from(array("c" => $select))
                                        ->joinLeft(array("d" => $this->ADMIN->Masterlogin->__name()),"c.id_user = d.id",array(
                                            "uid"
                                        ));
                                    $nextWs = $this->db->fetchRow($select);
                                    if ($nextWs)
                                    {
                                        $this->ADMIN->Workflowtrans->update(array(
                                            "uid_next" => $nextWs['uid']
                                        ),"workflow_trans_id = $wid");
                                        $arrayLog[] = "Updating workflow trans next $prjKode $trano";
                                    }
                                }
                            }
                        }

                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflowtrans->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("final=0")
                            ->order(array("date DESC"));
                        $select = $this->db->select()
                            ->from(array("a" => $select))
                            ->group(array("item_id"));
                        $select = $this->db->select()
                            ->from(array("b" => $select))
                            ->where("uid_prev = ?",$uidTarget);
                        $wNext = $this->db->fetchAll($select);

                        if ($wNext)
                        {
                            foreach($wNext as $k3 => $v3)
                            {
                                $trano = $v3['item_id'];
                                $ws = $v3['workflow_structure_id'];
                                $wid = $v3['workflow_trans_id'];
                                $select = $this->db->select()
                                    ->from(array($this->ADMIN->Workflowstructure->__name()))
                                    ->where("workflow_item_id=?",$wItemId)
                                    ->where("id=?",$ws);
                                $currWs = $this->db->fetchRow($select);
                                if ($currWs)
                                {
                                    $cLevel = $currWs['level'] - 1;
                                    $select = $this->db->select()
                                        ->from(array($this->ADMIN->Workflowstructure->__name()),array(
                                            "id",
                                            "master_role_id"
                                        ))
                                        ->where("workflow_item_id=?",$wItemId)
                                        ->where("level=?",$cLevel);

                                    $select = $this->db->select()
                                        ->from(array("a" => $select))
                                        ->joinLeft(array("b" => $this->ADMIN->Masterrole->__name()),"a.master_role_id = b.id",array(
                                            "id_user"
                                        ));

                                    $select = $this->db->select()
                                        ->from(array("c" => $select))
                                        ->joinLeft(array("d" => $this->ADMIN->Masterlogin->__name()),"c.id_user = d.id",array(
                                            "uid"
                                        ));
                                    $nextWs = $this->db->fetchRow($select);
                                    if ($nextWs)
                                    {
                                        $this->ADMIN->Workflowtrans->update(array(
                                            "uid_prev" => $nextWs['uid']
                                        ),"workflow_trans_id = $wid");
                                        $arrayLog[] = "Updating workflow trans next $prjKode $trano";
                                    }
                                }
                            }
                        }
                    }

                    $return['success'] = true;
                }
                else
                {
                    $arrayLog[] = "Username not Exist on the workflow : <b>" . $v['name'] . " $prjKode</b>";
                }
            }
        }
        if (count($arrayLog)>0)
            $return['log'] = implode("<br>",$arrayLog);

        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function switchPersonAction()
    {

    }

    public function doSwitchPersonAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = ($this->_getParam("json") != '') ? Zend_Json::decode($this->_getParam("json")) : false;
        $uid = $this->_getParam("uid");
        $uidR = $this->_getParam("uid_replace");
        $roleR = $this->_getParam("id_role_replace");
        $workflow_type_id = $this->_getParam("workflow_type_id");

        $userTarget = $this->db->fetchRow($this->db->select()->from(array($this->ADMIN->Masterlogin->__name()))->where("uid=?",$uid));
        $userIDTarget = $userTarget['id'];
        $uidTarget = $userTarget['uid'];

        $userReplace = $this->db->fetchRow($this->db->select()->from(array($this->ADMIN->Masterlogin->__name()))->where("uid=?",$uidR));
        $userIDReplace = $userReplace['id'];
        $uidReplace = $userReplace['uid'];


        $arrayLog = array();
        $return = array(
            "success" => false
        );

        if (!$json)
        {
            $arrayProses = array(
                array(
                    "workflow_item_type_id" => $workflow_type_id,
                    "prj_kode" => ''
                ));
        }
        else
            $arrayProses = $json;

        foreach($arrayProses as $key => $val)
        {
            $workflow_type_id = $val['workflow_item_type_id'];
            $prj_kode = $val['prj_kode'];

            $select = $this->db->select()
                ->from(array($this->ADMIN->Workflowitem->__name()))
                ->where("workflow_item_type_id=?",$workflow_type_id);

            if ($prj_kode)
                $select = $select->where("prj_kode = ?",$prj_kode);

            if ($val['workflow_item_id'])
                $select = $select->where("workflow_item_id = ?",$val['workflow_item_id']);

            //Get all workflow_item_id
            $w = $this->db->fetchAll($select);

            foreach($w as $k => $v)
            {
                $wItemId = $v['workflow_item_id'];
                $prjKode = $v['prj_kode'];

                $select = $this->db->select()
                    ->from(array($this->ADMIN->Masterrole->__name()))
                    ->where("id_user=?",$userIDReplace)
                    ->where("id_role=?",$roleR)
                    ->where("prj_kode=?",$prjKode);

                $roles = $this->db->fetchRow($select);
                //Create new Role if not exist
                if (!$roles)
                {
                    $roleIdR = $this->ADMIN->Masterrole->insert(array(
                        "id_user" => $userIDReplace,
                        "prj_kode" => $prjKode,
                        "id_role" => $roleR,
                        "active" => 1
                    ));
                }
                else
                {
                    $roleIdR = $roles['id'];
                }

                $select = $this->db->select()
                    ->from(array($this->ADMIN->Masterrole->__name()))
                    ->where("id_user=?",$userIDTarget)
                    ->where("prj_kode=?",$prjKode);

                $select = $this->db->select()
                    ->from(array("a" => $select))
                    ->joinLeft(array("b" => $this->ADMIN->Workflowstructure->__name()),"a.id = b.master_role_id")
                    ->where("b.workflow_item_id=?",$wItemId);

                $w2 = $this->db->fetchAll($select);

                if ($w2)
                {
                    foreach($w2 as $k2 => $v2)
                    {
                        $wStructId = $v2['id'];
                        $wStructRoleId = $v2['master_role_id'];
                        $level = $v2['level'];

                        //cek Replace ada di structure yg sama atau gak
                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflowstructure->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("master_role_id=?",$roleIdR);

                        $cek = $this->db->fetchRow($select);

                        //Ada dalam structure yg sama...
                        if ($cek)
                        {
                            $arrayLog[] = "Cannot replace structure $prjKode, reason : Replacement Exist on same structure... ";
                            continue;
                        }

                        $arrayLog[] = "Updating structure $prjKode : level " . $level . " role $wStructRoleId > " . $roleIdR;
                        $this->ADMIN->Workflowstructure->update(array(
                            "master_role_id" => $roleIdR
                        ),"workflow_item_id = $wItemId and id = $wStructId");

                        //WOrkflow Route

                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflow->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("uid_next=?",$uidTarget);

                        $wNext = $this->db->fetchAll($select);
                        if ($wNext)
                        {
                            foreach($wNext as $k3 => $v3)
                            {
                                $wfId = $v3['workflow_id'];
                                $wsId = $v3['workflow_structure_id'];

                                $arrayLog[] = "Updating workflow next $prjKode";
                                $this->ADMIN->Workflow->update(array(
                                    "uid_next" => $uidReplace
                                ),"workflow_id = $wfId");
                            }
                        }
                        else
                        {
                            $arrayLog[] = "Cannot Updating workflow next $prjKode, Reason : Workflow next not exist";
                        }

                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflow->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("uid_prev=?",$uidTarget);

                        $wNext = $this->db->fetchAll($select);
                        if ($wNext)
                        {
                            foreach($wNext as $k3 => $v3)
                            {
                                $wfId = $v3['workflow_id'];
                                $wsId = $v3['workflow_structure_id'];

                                $arrayLog[] = "Updating workflow prev $prjKode";
                                $this->ADMIN->Workflow->update(array(
                                    "uid_prev" => $uidReplace
                                ),"workflow_id = $wfId");
                            }
                        }
                        else
                        {
                            $arrayLog[] = "Cannot Updating workflow prev $prjKode, Reason : Workflow prev not exist";
                        }


                        $this->ADMIN->Workflow->update(array(
                            "uid" => $uidReplace,
                            "master_role_id" => $roleIdR
                        ),
                            "workflow_item_id = $wItemId AND uid= '$uidTarget'"
                        );
                        $arrayLog[] = "Updating workflow $prjKode";

                        //WOrkflow Transaction

                        //Update workflow yg lagi di handle oleh target...
                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflowtrans->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("final=0")
                            ->order(array("date DESC"));
                        $select = $this->db->select()
                            ->from(array("a" => $select))
                            ->group(array("item_id"));
                        $select = $this->db->select()
                            ->from(array("b" => $select))
                            ->where("uid = ?",$uidTarget);
                        $wNext = $this->db->fetchAll($select);

                        if ($wNext)
                        {
                            foreach($wNext as $k3 => $v3)
                            {
                                $trano = $v3['item_id'];
                                $this->ADMIN->Workflowtrans->update(
                                    array(
                                        "uid" => $uidReplace,
                                    ),
                                    "workflow_trans_id = {$v3['workflow_trans_id']}"
                                );
                                $arrayLog[] = "Updating workflow trans current $prjKode $trano";
                            }
                        }

                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflowtrans->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("final=0")
                            ->order(array("date DESC"));
                        $select = $this->db->select()
                            ->from(array("a" => $select))
                            ->group(array("item_id"));
                        $select = $this->db->select()
                            ->from(array("b" => $select))
                            ->where("uid_next = ?",$uidTarget);

                        $wNext = $this->db->fetchAll($select);

                        if ($wNext)
                        {
                            foreach($wNext as $k3 => $v3)
                            {
                                $trano = $v3['item_id'];
                                $this->ADMIN->Workflowtrans->update(
                                    array(
                                        "uid_next" => $uidReplace,
                                    ),
                                    "workflow_trans_id = {$v3['workflow_trans_id']}"
                                );
                                $arrayLog[] = "Updating workflow trans next $prjKode $trano";
                            }
                        }

                        $select = $this->db->select()
                            ->from(array($this->ADMIN->Workflowtrans->__name()))
                            ->where("workflow_item_id=?",$wItemId)
                            ->where("final=0")
                            ->order(array("date DESC"));
                        $select = $this->db->select()
                            ->from(array("a" => $select))
                            ->group(array("item_id"));
                        $select = $this->db->select()
                            ->from(array("b" => $select))
                            ->where("uid_prev = ?",$uidTarget);
                        $wNext = $this->db->fetchAll($select);

                        if ($wNext)
                        {
                            foreach($wNext as $k3 => $v3)
                            {
                                $trano = $v3['item_id'];
                                $this->ADMIN->Workflowtrans->update(
                                    array(
                                        "uid_prev" => $uidReplace,
                                    ),
                                    "workflow_trans_id = {$v3['workflow_trans_id']}"
                                );
                                $arrayLog[] = "Updating workflow trans prev $prjKode $trano";
                            }
                        }
                    }

                    $return['success'] = true;
                }
                else
                {
                    $arrayLog[] = "Username not Exist on the workflow : <b>" . $v['name'] . " $prjKode</b>";
                }
            }
        }
        if (count($arrayLog)>0)
            $return['log'] = implode("<br>",$arrayLog);

        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

}

?>