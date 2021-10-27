<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/16/11
 * Time: 2:50 PM
 * To change this template use File | Settings | File Templates.
 */
 
class projectmanagement_EngineerworkController extends Zend_Controller_Action
{

    private $engineerwork;

    public function init()
    {
        $this->engineerwork = new Default_Models_MasterWork ();
    }

    public function engineerworkAction ()
    {
        
    }

    public function viewengineerworkAction ()
    {
        
    }

    public function getviewengineerworkAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $text = $this->_getParam('search');
        $option = $this->_getParam('option');
        $search = null;

        if ($text == "" && $option == 1)
        {
            $search = null;
        }else if ($text != null && $option == 1)
        {
            $search = "workid like '%$text%' ";
        }else if ($text != null && $option == 2)
        {
            $search = "workname like '%$text%' ";
        }else
        {
            $search = null;
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $workdata = $this->engineerwork->fetchAll ($search,array($sort . " " . $dir),$limit,$offset)->toArray ();
        $return['data'] = $workdata;
        $return['total'] = $this->engineerwork->fetchAll()->count();

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addengineerworkAction ()
    {
        
    }

    public function getaddengineerworkAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $workid = $this->_getParam('workid');
        $workname = $this->_getParam('workname');

        $where = "workid = '$workid' or workname = '$workname' ";

        $cekwork = $this->engineerwork->fetchRow($where);

        if ($cekwork)
        {
            $return = array("success" => false, "pesan" => "Sorry, Engineer Work Exist");
        }else
        {
            $insertWork = array(
                "workid" => $workid,
                "workname" => $workname
            );

            $this->engineerwork->insert($insertWork);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function editengineerworkAction ()
    {
        
    }

    public function geteditengineerworkAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $workid = $this->_getParam('workid');
        $workname = $this->_getParam('workname');
        $id = $this->_getParam('id');


        if ($id == null || $id == "")
        {
            $return = array("success" => false, "pesan" => "Sorry, you must select from grid ");
        }else
        {
            $updateengineerWork = array(
            "workid" => $workid,
            "workname" => $workname,
            );

            $this->engineerwork->update($updateengineerWork,"id = '$id' ");

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

}