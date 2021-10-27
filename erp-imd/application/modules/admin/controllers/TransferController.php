<?php

class Admin_TransferController extends Zend_Controller_Action
{
    public function praboq3ToBoq3Action()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");
        $budget = new Default_Models_Budget();
        $budget->transferTempBOQ3($trano);
    }

    public function afeToBoq3Action()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");
        $cboq3h = new Default_Models_MasterCboq3H();
        $afeh = new ProjectManagement_Models_AFEh();

        $isSwitching = $afeh->checkSwitching($trano);
        if (!$isSwitching)
            $cboq3h->transferAFEtoBOQ3($trano);
        else
            $cboq3h->transferAFESwitchingtoBOQ3($trano);
        $addBoq2 = new Default_Models_MasterAddco();
        $addBoq2->transferAddRevenue($trano);
    }
}
?>