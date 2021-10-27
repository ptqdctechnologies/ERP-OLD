<?php

class Mc_SupplierController extends Zend_Controller_Action
{

    private $DEFAULT;
    public function init()
    {
        /* Initialize action controller here */
        $model = array(
            "MasterSuplier"
        );
        $this->DEFAULT = QDC_Model_Default::init($model);
    }

    public function getSupplierAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $hashOnly = $this->_getParam("hash");
        $cacheID = "MC_SUPPLIER";

        $offset = $this->_getParam("start");
        $limit = $this->_getParam("limit");

        if (!$offset)
            $offset = 0;
        if (!$limit)
            $limit = 25;

        $result = $this->DEFAULT->MasterSuplier->getAll("aktif = 'Y'",  $offset,$limit);
        $count = $this->DEFAULT->MasterSuplier->fetchAll("aktif = 'Y'")->count();
        $return = array('success' => true,'data' => $result, 'total' => $count);
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}
?>