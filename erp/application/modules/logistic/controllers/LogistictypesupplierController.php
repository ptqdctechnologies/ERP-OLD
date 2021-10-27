<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/10/11
 * Time: 1:36 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_LogistictypesupplierController extends Zend_Controller_Action
{
    private $typesupplier;
    private $session;

    public function  init()
    {
        $this->typesupplier = new Logistic_Model_LogisticTypeSupplier();
        $this->session = new Zend_Session_Namespace('login');
    }

    public function typesupplierAction ()
    {
        
    }

    public function addtypesupplierAction ()
    {
        
    }

    public function listtypesupplierAction ()
    {
        
    }

    public  function doinserttypesuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();
        
        $type = $this->getRequest()->getParam('supp-type');
        $typename = $this->getRequest()->getParam('supp-type-name');

        $where = "jenisupplier = '$type' AND jenissupliernama = '$typename'";

        $cek = $this->typesupplier->fetchRow ($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry, Type Supplier has ready to use");
        }else{

            $insert = array(
                "jenisupplier" => $type,
                "jenissupliernama" => $typename
            );

            $this->typesupplier->insert($insert);
            $activityHead['master_jenissupplier'][0]=$insert;

             $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        
         $activityLog = array(
            "menu_name" => "Create Master Type Supplier",
            "trano" => $type,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        
        
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public  function  gettypesuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data['data'] = $this->typesupplier->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->typesupplier->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function  edittypesupplierAction ()
    {
        
    }

    public  function  doupdatetypesuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');
        $type = $this->getRequest()->getParam('supp-type');
        $typename = $this->getRequest()->getParam('supp-type-name');
        
        $old = $this->typesupplier->fetchAll("id = '$id'")->toArray();
        $log['typesupplier-detail-before'] = $old;
        
        $update = array(
            "jenisupplier" => $type,
            "jenissupliernama" => $typename
        );

        $this->typesupplier->update($update,"id = '$id'" );

        $new = $this->typesupplier->fetchAll("id = '$id'")->toArray();
        $log2['typesupplier-detail-after'] = $new;
        
        $return = array("success" => true);

        //Log Transaction
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $id,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "action" => "UPDATE",
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


}