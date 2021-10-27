<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/26/12
 * Time: 9:28 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_JurnalinventoryController extends Zend_Controller_Action
{
    private $db;
    private $jurnalinventory;
    private $gudang;

    public function init()
    {
        $this->jurnalinventory = new Finance_Models_MasterJurnalInventory();
        $this->gudang = new Logistic_Models_MasterGudang();
    }

    public function menuAction ()
    {

    }

    public function insertjurnalinventoryAction ()
    {

    }

    public function doinsertjurnalinventoryAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $gdgkode_from = $this->getRequest()->getParam('gdg_kode_from');
        $gdgkode_to = $this->getRequest()->getParam('gdg_kode_to');
        $nama = $this->getRequest()->getParam('nama');
        $coa_debit = $this->getRequest()->getParam('coa_debit');
        $coa_credit = $this->getRequest()->getParam('coa_credit');
        $type = $this->getRequest()->getParam('type');

        $insertjurnalinventory = array(
            "gdg_kode_from" => $gdgkode_from,
            "gdg_kode_to" => $gdgkode_to,
            "nama" => $nama,
            "coa_debit" => $coa_debit,
            "coa_credit" => $coa_credit,
            "type" => $type
        );

        $this->jurnalinventory->insert($insertjurnalinventory);


        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewjurnalinventoryAction ()
    {

    }

    public function getjurnalinventoryAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $textsearch = $this->getRequest()->getParam('search');
        $option = $this->getRequest()->getParam('option');
   
        $search = "";

        if ($textsearch == "" || $textsearch == null)
        {
            $search = null;
        }else if ($textsearch != null && $option != null)
        {
            $search = " $option like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->jurnalinventory->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        foreach ($data['data'] as $k =>$v){
              $tempgdg_from = $v['gdg_kode_from'];
              $tempgdg_to = $v['gdg_kode_to'];
              $gdg_nama_to = 'Site';
              
              if($tempgdg_to != 'S'){
                  $gdg_to =  $this->gudang->fetchRow("gdg_kode = '$tempgdg_to'");
                  $gdg_nama_to =  $gdg_to['gdg_nama'];
             }
                       
             if($tempgdg_from =='Supp'){
                  $gdg_nama_from = 'Supplier';                                
              }else if($tempgdg_from == 'S'){              
                  $gdg_nama_from = 'Site';
              }else{
                 $gdg_from =  $this->gudang->fetchRow("gdg_kode = '$tempgdg_from'");
                 $gdg_nama_from = $gdg_from['gdg_nama']; 
              }
              
              $data['data'][$k]['gdg_nama_from'] = $gdg_nama_from;
              $data['data'][$k]['gdg_nama_to'] = $gdg_nama_to;
        }
        
        $data['total'] = $this->jurnalinventory->fetchAll()->count();


        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editjurnalinventoryAction ()
    {
        
        $id = $this->getRequest()->getParam('id');

        $jurnalinventory = $this->jurnalinventory->fetchRow("id = '$id'")->toArray();
       
              $tempgdg_from = $jurnalinventory['gdg_kode_from'];
              $tempgdg_to = $jurnalinventory['gdg_kode_to'];
              $gdg_from =  $this->gudang->fetchRow("gdg_kode = '$tempgdg_from'");
              $gdg_to =  $this->gudang->fetchRow("gdg_kode = '$tempgdg_to'");
              
              $jurnalinventory['gdg_nama_from'] = $gdg_from['gdg_nama'];
              $jurnalinventory['gdg_nama_to'] = $gdg_to['gdg_nama'];
       
        
        
        $this->view->jurnalinventory = $jurnalinventory;
    }

    public function doupdatejurnalinventoryAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $gdgkode_from = $this->getRequest()->getParam('gdg_kode_from');
        $gdgkode_to = $this->getRequest()->getParam('gdg_kode_to');
        $nama = $this->getRequest()->getParam('nama');
        $coa_debit = $this->getRequest()->getParam('coa_debit');
        $coa_credit = $this->getRequest()->getParam('coa_credit');        
        $type = $this->getRequest()->getParam('type');
        $id = $this->getRequest()->getParam('id');
        

        $updatejurnalinventory = array(
            "gdg_kode_from" => $gdgkode_from,
            "gdg_kode_to" => $gdgkode_to,
            "nama" => $nama,
            "coa_debit" => $coa_debit,
            "coa_credit" => $coa_credit,
            "type" => $type
        );

        $this->jurnalinventory->update($updatejurnalinventory,"id = '$id'");

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }
    public function deletejurnalinventoryAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');        

        $this->jurnalinventory->delete("id = '$id'");

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

}