<?php


class projectmanagement_ProjectcloseController extends Zend_Controller_Action
{

    private $site;
    private $projectclose;
    private $db;


    public function init()
    {
        $this->site = new Default_Models_MasterSite ();
        $this->projectclose = new ProjectManagement_Models_Projectclose ();
        $this->db = Zend_Registry::get("db");
    }

    public function projectAction ()
    {
        
    }

    public function viewprojectAction ()
    {
        
    }

    public function getviewprojectAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $query =    "select prj_nama,mp.prj_kode from master_site ms
                    left join master_project mp on ms.prj_kode = mp.prj_kode
                    where ms.stsclose = 0 group by prj_kode order by prj_kode desc";

        $fetch = $this->db->query($query);
        $projectdata = $fetch->fetchAll();

//        $projectdata = $this->projectclose->fetchAll()->toArray ();

        $return['data'] = $projectdata;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getviewsiteAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();


        $prj_kode = $this->_getParam('prj_kode');

        $where = "prj_kode = '$prj_kode' and stsclose = '0'";

        $sitedata = $this->site->fetchAll($where)->toArray ();

        $return['data'] = $sitedata;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function updateprojectAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $sit_kode = Zend_Json::decode($this->_getParam('sit_kode'));
        $prj_kode = $this->_getParam('prj_kode');
        $semuasite = $this->_getParam('semuasite');

         $update_stsclose = array(
                "stsclose" => 1
            );

        if ($semuasite == "false")
        {
            foreach ($sit_kode as $k => $v)//untuk insert atau update data yang berulang atau banyak
            {
                $this->site->update($update_stsclose,"prj_kode = '$prj_kode' AND sit_kode = '$v' ");
            }

        }else
        {
            $this->projectclose->update($update_stsclose,"prj_kode = '$prj_kode'");
            $this->site->update($update_stsclose,"prj_kode = '$prj_kode' ");

        }

         $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function openprojectAction ()
    {
        
    }

    public function getcloseprojectlistAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $query =    "select prj_nama,mp.prj_kode from master_site ms
                    left join master_project mp on ms.prj_kode = mp.prj_kode
                    where ms.stsclose = 1 group by prj_kode order by prj_kode desc";

        $fetch = $this->db->query($query);
        $projectdata = $fetch->fetchAll();

//        $projectdata = $this->projectclose->fetchAll()->toArray ();

        $return['data'] = $projectdata;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getclosesitelistAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();


        $prj_kode = $this->_getParam('prj_kode');

        $where = "prj_kode = '$prj_kode' and stsclose = '1'";

        $sitedata = $this->site->fetchAll($where)->toArray ();

        $return['data'] = $sitedata;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateopenprojectAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $sit_kode = Zend_Json::decode($this->_getParam('sit_kode'));
        $prj_kode = $this->_getParam('prj_kode');
        $semuasite = $this->_getParam('semuasite');

         $update_stsclose = array(
                "stsclose" => 0
            );

        if ($semuasite == "false")
        {
            foreach ($sit_kode as $k => $v)//untuk insert atau update data yang berulang atau banyak
            {
                $this->site->update($update_stsclose,"prj_kode = '$prj_kode' AND sit_kode = '$v' ");
            }

        }else
        {
            $this->projectclose->update($update_stsclose,"prj_kode = '$prj_kode'");
            $this->site->update($update_stsclose,"prj_kode = '$prj_kode' ");

        }

         $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

}

