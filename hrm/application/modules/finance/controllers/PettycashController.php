<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/16/11
 * Time: 3:05 PM
 * To change this template use File | Settings | File Templates.
 */

class Finance_PettycashController extends Zend_Controller_Action
{
    private $pettycashin;
    private $pettycashout;
    private $counter;

    public function init()
    {
        $this->pettycashin = new Finance_Models_PettyCashIn();
        $this->pettycashout = new Finance_Models_PettyCashOut();
        $this->counter = new Default_Models_MasterCounter();
        $this->session = new Zend_Session_Namespace('login');
    }

    public function menuAction()
    {
        
    }

    public function insertpettycashinAction ()
    {
        
    }

    public function doinsertpettycashinAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $pettycashindata = Zend_Json::decode($this->getRequest()->getParam('pettycashindata'));

        $trano = $this->counter->setNewTrans('PRM');
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        foreach($pettycashindata as $key => $val)
        {
            $insertpettycashin = array(
                "trano" => $trano,
                "ref_number" => $val['ref_number'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit'])
            );

            $this->pettycashin->insert($insertpettycashin);
        }

        $this->getResponse()->setBody("{success: true}");
    }

    public function insertpettycashoutAction ()
    {
        
    }

    public function doinsertpettycashoutAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $pettycashoutdata = Zend_Json::decode($this->getRequest()->getParam('pettycashoutdata'));

//        var_dump($pettycashoutdata);die;

        $trano = $this->counter->setNewTrans('PSM');
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        foreach($pettycashoutdata as $key => $val)
        {
            $insertpettycashout = array(
                "trano" => $trano,
                "ref_number" => $val['ref_number'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit'])
            );

            $this->pettycashout->insert($insertpettycashout);
        }

        $this->getResponse()->setBody("{success: true}");
    }

}