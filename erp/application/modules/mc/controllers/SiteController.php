<?php

class Mc_SiteController extends Zend_Controller_Action
{
    private $DEFAULT, $uid;

    public function init()
    {
        /* Initialize action controller here */
        $this->uid = QDC_User_Session::factory()->getCurrentUID();
        $model = array(
            "MasterSite",
            "MasterRoleSite"
        );
        $this->DEFAULT = QDC_Model_Default::init($model);
    }

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $hashOnly = $this->_getParam("hash");
        $prj_kode = $this->_getParam("prj_kode");
        $start = $this->_getParam("start");
        $limit = $this->_getParam("limit");

//        $offset = (isset($start)) ? $start : 0;
//        $limit = (isset($limit)) ? $limit : 30;

//        $data = $this->DEFAULT->MasterSite->getList($prj_kode);

        $data = $this->DEFAULT->MasterRoleSite->getAllSite($this->uid,$prj_kode);
        $return['count'] = count($data);
//        $paginator = Zend_Paginator::factory($data);
//        $paginator->setItemCountPerPage($limit);
//        $paginator->setCurrentPageNumber($offset);

//        $return['data'] = array();$i=0;
//        foreach($paginator as $item)
//        {
//            foreach($item as $k => $v)
//                $return['data'][$i][$k] = $v;
//
//            $i++;
//        }

        $return['data'] = $data;
        $tmp = array();
        foreach($data as $k => $v)
        {
            $tmp[] = $v['sit_kode'];
        }

        $hash = md5(implode(",",$tmp));
        $return['hash'] = $hash;

        if ($hashOnly)
            unset($return['data']);

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}