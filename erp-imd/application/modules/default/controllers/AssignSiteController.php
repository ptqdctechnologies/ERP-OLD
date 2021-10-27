<?php

class AssignSiteController extends Zend_Controller_Action {
    /**
     * The default action - show the home page
     */
    private $db;
    private $DEFAULT;
    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $models = array(
            "MasterRoleSite"
        );
        $this->DEFAULT = QDC_Model_Default::init($models);
    }

    public function indexAction()
    {

    }

    public function detailSiteAction()
    {
        $uid = $this->_getParam("uid");
        $this->view->name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
        $this->view->uid = $uid;
    }

    public function getUserSiteAction()
    {
        $uid = $this->_getParam("uid");
        $prjKode = $this->_getParam("prj_kode");
        $sitKode = $this->_getParam("sit_kode");
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 50;
        $this->_helper->viewRenderer->setNoRender();

        if($prjKode)
        {
            $where = "prj_kode = '$prjKode'";
            if ($sitKode)
            {
                $where .= " AND sit_kode = '$sitKode'";
            }
        }

        $data = $this->DEFAULT->MasterRoleSite->getAll($uid,$offset,$limit,$where);

        $return['posts'] = $data['posts'];
        $return['count'] = $data['count'];
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function addUserSiteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $form = $this->_getAllParams();

        $prj_kode = $this->_getParam("prj_kode");
        $prj_nama = $this->_getParam("prj_nama");
        $sit_kode = $this->_getParam("sit_kode");
        $sit_nama = $this->_getParam("sit_nama");
        $uid = $this->_getParam("uid");

        $cek = $this->DEFAULT->MasterRoleSite->cekExist($prj_kode,$sit_kode,$uid);
        if ($cek)
        {
            echo "{success: false, msg: 'This User has been already assigned to Project $prj_kode and Site $sit_kode'}";
            return false;
        }

        $insert = array(
            "prj_kode" => $prj_kode,
            "sit_kode" => $sit_kode,
            "prj_nama" => $prj_nama,
            "sit_nama" => $sit_nama,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "uid_pic" => $uid,
            "date_created" => new Zend_Db_Expr("NOW()"),
            "date_modified" => new Zend_Db_Expr("NOW()"),
        );

        $this->DEFAULT->MasterRoleSite->insert($insert);

        echo "{success: true}";

    }

    public function updateUserSiteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $form = $this->_getAllParams();

        $prj_kode = $this->_getParam("prj_kode");
        $prj_nama = $this->_getParam("prj_nama");
        $sit_kode = $this->_getParam("sit_kode");
        $sit_nama = $this->_getParam("sit_nama");
        $active = $this->_getParam("active");
        ($active == 'on') ? $active = 1 : $active = 0;
        $uid = $this->_getParam("uid");
        $id = $this->_getParam("id");

        $cek = $this->DEFAULT->MasterRoleSite->fetchRow("id = $id");
        if ($cek)
        {
            if ($prj_kode != $cek['prj_kode'] || $sit_kode != $cek['sit_kode'])
            {
                $cek2 = $this->DEFAULT->MasterRoleSite->cekExist($prj_kode,$sit_kode,$uid);
                if ($cek2)
                {
                    echo "{success: false, msg: 'This User has been already assigned to Project $prj_kode and Site $sit_kode'}";
                    return false;
                }
            }
        }
        else
        {
            echo "{success: false, msg: 'Invalid Record.";
            return false;
        }

        $insert = array(
            "prj_kode" => $prj_kode,
            "sit_kode" => $sit_kode,
            "prj_nama" => $prj_nama,
            "sit_nama" => $sit_nama,
            "active" => $active,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "uid_pic" => $uid,
            "date_modified" => new Zend_Db_Expr("NOW()"),
        );

        $this->DEFAULT->MasterRoleSite->update($insert,"id = $id");

        echo "{success: true}";

    }
}