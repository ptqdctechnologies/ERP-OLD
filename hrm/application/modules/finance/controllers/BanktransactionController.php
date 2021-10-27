<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/13/11
 * Time: 9:28 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_BanktransactionController extends Zend_Controller_Action
{
    private $bankin;
    private $bankout;
    private $counter;

    public function init()
    {
        $this->bankin = new Finance_Models_BankReceiveMoney();
        $this->bankout = new Finance_Models_BankSpendMoney();
        $this->counter = new Default_Models_MasterCounter();
        $this->session = new Zend_Session_Namespace('login');
    }

    public function menuAction ()
    {
        
    }

    public function insertbankinAction ()
    {
        
    }

    public function doinsertbankinAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bankindata =  Zend_Json::decode($this->getRequest()->getParam('bankindata'));

        $trano = $this->counter->setNewTrans('BRM');
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        foreach($bankindata as $key => $val)
        {
            $insertbankin = array(
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

            $this->bankin->insert($insertbankin);
        }

        $this->getResponse()->setBody("{success: true}");

//        $json = Zend_Json::encode($files);
//        $this->getResponse()->setHeader('Content-Type','text/javascript');
//        $this->getResponse()->setBody($json);
    }

    public function insertbankoutAction ()
    {

    }

    public function doinsertbankoutAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bankoutdata =  Zend_Json::decode($this->getRequest()->getParam('bankoutdata'));

        $trano = $this->counter->setNewTrans('BSM');
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        foreach($bankoutdata as $key => $val)
        {
            $insertbankout = array(
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

            $this->bankout->insert($insertbankout);
        }

        $this->getResponse()->setBody("{success: true}");
    }
}