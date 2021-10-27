<?php

class Mc_ProgressController extends Zend_Controller_Action
{

    private $PROJECT, $DEFAULT;
    public function init()
    {
        /* Initialize action controller here */

        $model = array(
            "ProjectProgress"
        );
        $this->PROJECT = QDC_Model_ProjectManagement::init($model);

        $model = array(
            "MasterProject",
            "MasterSite",
            "MasterCounter",
            "Files"
        );
        $this->DEFAULT = QDC_Model_Default::init($model);
    }
    public function insertprogressAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $prj_kode = $this->_getParam("prj_kode");
        $sit_kode = $this->_getParam("sit_kode");
        $lat = $this->_getParam("latitude");
        $long = $this->_getParam("longitude");
        $accuracy = $this->_getParam("accuracy");
        $bulk = $this->_getParam("bulk");
        $photo = $this->_getParam("photo");
        $success = false;
        if(!$bulk)
        {
            $trano = $this->DEFAULT->MasterCounter->setNewTrans('PRPG');
            $arrayInsert = array (
                "prj_kode" => $prj_kode,
                "prj_nama" => $this->DEFAULT->MasterProject->getProjectName($prj_kode),
                "sit_kode" => $sit_kode,
                "sit_nama" => $this->DEFAULT->MasterSite->GetSiteName($prj_kode,$sit_kode),
                "progress" => $this->_getParam("progress"),
                "tgl_progress" => date('Y-m-d'),
                "ket" => $this->_getParam("ket"),
                "date" => date('Y-m-d H:i:s'),
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "lat" => $lat,
                "lon" => $long,
                "accuracy" => $accuracy,
                "trano" => $trano
            );

            $id = $this->PROJECT->ProjectProgress->insert($arrayInsert);
            if ($id)
                $success = true;

            if ($photo)
            {
                $simpanGambar = QDC_Adapter_File::factory()->makeFileFromBase64($photo);
                if ($simpanGambar['success'])
                {
                    $arrayInsert = array (
                        "trano" => $trano,
                        "prj_kode" => '',
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => QDC_User_Session::factory()->getCurrentUID(),
                        "filename" => $simpanGambar['filename'],
                        "savename" => $simpanGambar['savename']
                    );
                    $this->DEFAULT->Files->insert($arrayInsert);
                }
            }

        }
        else
        {
            $data = $this->_getParam("data");
            if ($data != '')
            {
                $data = Zend_Json::decode($data);
                foreach($data as $k => $v)
                {
                    $trano = $this->DEFAULT->MasterCounter->setNewTrans('PRPG');
                    $arrayInsert = array (
                        "prj_kode" => $v['prj_kode'],
                        "prj_nama" => $this->DEFAULT->MasterProject->getProjectName($v['prj_kode']),
                        "sit_kode" => $v['sit_kode'],
                        "sit_nama" => $this->DEFAULT->MasterSite->GetSiteName($v['prj_kode'],$v['sit_kode']),
                        "progress" => $v['progress'],
                        "tgl_progress" => $v['tgl'],
                        "ket" => $v['ket'],
                        "date" => date('Y-m-d H:i:s'),
                        "uid" => QDC_User_Session::factory()->getCurrentUID(),
                        "lat" => $v['latitude'],
                        "lon" => $v['longitude'],
                        "accuracy" => $v['accuracy'],
                        "trano" => $trano
                    );

                    $id = $this->PROJECT->ProjectProgress->insert($arrayInsert);

                    if ($v['photo'])
                    {
                        $simpanGambar = QDC_Adapter_File::factory()->makeFileFromBase64($v['photo']);
                        if ($simpanGambar['success'])
                        {
                            $arrayInsert = array (
                                "trano" => $trano,
                                "prj_kode" => '',
                                "date" => date("Y-m-d H:i:s"),
                                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                                "filename" => $simpanGambar['filename'],
                                "savename" => $simpanGambar['savename']
                            );
                            $this->DEFAULT->Files->insert($arrayInsert);
                        }
                    }
                }
                $success = true;
            }
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody("{success: $success}");
    }

    public function getsiteprogressAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');

        $result = $this->PROJECT->ProjectProgress->getSiteProgress($prj_kode,$sit_kode);

        $tmp = array();
        foreach($result as $k => $v)
        {
            unset($result[$k]['id']);
            $tmp[] = $v['id'];
        }

        $hash = md5(implode(",",$tmp));

        $return = array('success' => true,'data' => $result, 'hash' => $hash);
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}
?>