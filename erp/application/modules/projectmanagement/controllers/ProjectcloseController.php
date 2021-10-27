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
                $log['projectclose-detail-before'][] = $this->site->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$v' ")->toArray();
                $this->site->update($update_stsclose,"prj_kode = '$prj_kode' AND sit_kode = '$v' ");
                $log2['projectclose-detail-after'][] = $this->site->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$v' ")->toArray();
            }

        }else
        {

            $log['projectclose-detail-before'][] = $this->projectclose->fetchRow("prj_kode = '$prj_kode'")->toArray();
            $this->projectclose->update($update_stsclose,"prj_kode = '$prj_kode'");
            $log2['projectclose-detail-after'][] = $this->projectclose->fetchRow("prj_kode = '$prj_kode'")->toArray();
            $log['projectclose-detail-before'][] = $this->site->fetchAll("prj_kode = '$prj_kode'")->toArray();
            $this->site->update($update_stsclose,"prj_kode = '$prj_kode' ");
            $log2['projectclose-detail-after'][] = $this->site->fetchAll("prj_kode = '$prj_kode'")->toArray();

        }

         $return = array("success" => true);

        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array (
            "trano" => 'CLOSE_PROJECT',
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $prj_kode,
            "action" => "CLOSE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);

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
                $log['projectclose-detail-before'][] = $this->site->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$v' ")->toArray();
                $this->site->update($update_stsclose,"prj_kode = '$prj_kode' AND sit_kode = '$v' ");
                $log2['projectclose-detail-after'][] = $this->site->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$v' ")->toArray();
            }

        }else
        {
            $log['projectclose-detail-before'][] = $this->projectclose->fetchRow("prj_kode = '$prj_kode'")->toArray();
            $this->projectclose->update($update_stsclose,"prj_kode = '$prj_kode'");
            $log2['projectclose-detail-after'][] = $this->projectclose->fetchRow("prj_kode = '$prj_kode'")->toArray();
            $log['projectclose-detail-before'][] = $this->site->fetchAll("prj_kode = '$prj_kode'")->toArray();
            $this->site->update($update_stsclose,"prj_kode = '$prj_kode' ");
            $log2['projectclose-detail-after'][] = $this->site->fetchAll("prj_kode = '$prj_kode'")->toArray();

        }

        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array (
            "trano" => 'CLOSE_PROJECT',
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $prj_kode,
            "action" => "CLOSE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);

         $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

}

