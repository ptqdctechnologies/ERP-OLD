<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/1/11
 * Time: 3:52 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_BankController extends Zend_Controller_Action
{
    private $bank;

    public function init ()
    {
        $this->bank = new Finance_Models_MasterBank();
    }

    public function bankmenuAction ()
    {
        
    }

    public function banklistAction ()
    {
        
    }

    public function getviewbanklistAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 10;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data['data'] = $this->bank->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->bank->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function forminsertbankAction ()
    {
        
    }

    public function insertbankAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bankname = $this->getRequest()->getParam('bank-name');
        $accnumber = $this->getRequest()->getParam('acc-number');
        $accname = $this->getRequest()->getParam('acc-name');
        $bankvaluta  = $this->getRequest()->getParam('valuta-bank');
        $bankbranch = $this->getRequest()->getParam('bank-branch');
        $bankadd = $this->getRequest()->getParam('bank-add');
        $bankcity = $this->getRequest()->getParam('bank-city');

        $insertbank = array(
            "bnk_nama" => $bankname,
            'bnk_norek' => $accnumber,
            'bnk_noreknama' => $accname,
            'val_kode' => $bankvaluta,
            'bnk_cabang' => $bankbranch,
            'bnk_alamat' => $bankadd,
            'bnk_kota' => $bankcity
        );

        $this->bank->insert($insertbank);

        $return = array('success' => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);


    }

    public function formeditbankAction ()
    {
        
    }

    public function editbankAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $bankID = $this->getRequest()->getParam('id');
        $bankname = $this->getRequest()->getParam('bnk_nama');
        $accnumber = $this->getRequest()->getParam('bnk_norek');
        $accname = $this->getRequest()->getParam('bnk_noreknama');
        $bankvaluta  = $this->getRequest()->getParam('val_kode');
        $bankbranch = $this->getRequest()->getParam('bnk_cabang');
        $bankadd = $this->getRequest()->getParam('bnk_alamat');
        $bankcity = $this->getRequest()->getParam('bnk_kota');

        $update = array(
            "bnk_nama" => $bankname,
            'bnk_norek' => $accnumber,
            'bnk_noreknama' => $accname,
            'val_kode' => $bankvaluta,
            'bnk_cabang' => $bankbranch,
            'bnk_alamat' => $bankadd,
            'bnk_kota' => $bankcity
        );

        $where = "id = '$bankID'";

        $this->bank->update($update,$where);

        $return = array('success' => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);


    }

}