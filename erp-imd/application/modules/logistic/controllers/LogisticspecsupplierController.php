<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/11/11
 * Time: 9:25 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_LogisticspecsupplierController extends Zend_Controller_Action
{
    private $specialistsupp;
    private $session;

    public function init()
    {
        $this->specialistsupp = new Logistic_Model_LogisticSpecialistSupplier();
        $this->session = new Zend_Session_Namespace('login');
    }

    public  function specsupplierAction ()
    {
        
    }

    public function addspecsupplierAction ()
    {
        
    }

    public function doinsertspecsuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();

        $spec = $this->getRequest()->getParam('supp-spec');
        $specname = $this->getRequest()->getParam('supp-spec-name');

        $where = "subjenisupplier = '$spec' AND subjenissupliernama = '$specname'";

        $cek = $this->specialistsupp->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry, Specialist Supplier has ready to use");
        }else{

            $insert = array(
                "subjenisupplier" => $spec,
                "subjenissupliernama" => $specname
            );

            $this->specialistsupp->insert($insert);
            $activityHead['master_jenissupliersub'][0]=$insert;

             $return = array("success" => true);
        }
        
        $json = Zend_Json::encode($return);
        
        $activityLog = array(
            "menu_name" => "Create Master Specialist Supplier",
            "trano" => $spec,
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

    public function getspecsuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data['data'] = $this->specialistsupp->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->specialistsupp->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listspecsupplierAction ()
    {
        
    }

    public function editspecsupplierAction ()
    {
        
    }

    public function doupdatespecsuppAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');
        $spec = $this->getRequest()->getParam('supp-spec');
        $specname = $this->getRequest()->getParam('supp-spec-name');

        $old = $this->specialistsupp->fetchAll("id = '$id'")->toArray();
        $log['specialistsupp-detail-before'] = $old;
        
        $update = array(
            "subjenisupplier" => $spec,
            "subjenissupliernama" => $specname
        );

        $this->specialistsupp->update($update,"id = '$id'" );
        
        $new = $this->specialistsupp->fetchAll("id = '$id'")->toArray();
        $log2['specialistsupp-detail-after'] = $new;

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