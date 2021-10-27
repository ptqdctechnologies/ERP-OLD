<?php


class Extjs4_BarcodeController extends Zend_Controller_Action
{
    private $LOGISTIC;

    public function init(){
        $model = array(
            "MasterFixedAsset"
        );

        $this->LOGISTIC = QDC_Model_Logistic::init($model);
    }

    public function getBarcodeAction()
    {
        $this->view->textEncode = $this->_getParam("text");
    }
}