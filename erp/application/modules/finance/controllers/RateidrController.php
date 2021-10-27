<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/16/11
 * Time: 2:50 PM
 * To change this template use File | Settings | File Templates.
 */
 
class finance_RateidrController extends Zend_Controller_Action
{

    public function init()
    {
        $this->rate = new Finance_Models_MasterRate();
        $this->ratebysystem = new Finance_Models_MasterRate('exchange_rate');
    }

    public function rateidrAction ()
    {
        
    }

    public function viewAction ()
    {
        
    }

    public function getviewsystemrateAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $from = $this->_getParam('from');
        $to = $this->_getParam('to');
        $val_kode = $this->_getParam('val_kode');
        
        $search=null;
        if(isset($from) && isset($to) && isset($val_kode))
        {
            $search = " val_kode='$val_kode' AND DATE(tgl) BETWEEN '$from' AND '$to' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';
        
        $ratedata = $this->ratebysystem->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray ();
        $return['data'] = $ratedata;
        $return['total'] = count($ratedata);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function getviewfinancerateAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $from = $this->_getParam('from');
        $to = $this->_getParam('to');
        $val_kode = $this->_getParam('val_kode');
        
        $search=null;
        if(isset($from) && isset($to) && isset($val_kode))
        {
            $search = " val_kode='$val_kode' AND DATE(tgl) BETWEEN '$from' AND '$to' ";
        }
        

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $ratedata = $this->rate->fetchAll ($search,array($sort . " " . $dir),$limit,$offset)->toArray ();
        $return['data'] = $ratedata;
        $return['total'] = count($ratedata);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function createAction ()
    {
        
        
    }

    public function getaddrateAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $rate_date = $this->_getParam('rate_date');
        $rate_idr = $this->_getParam('rate_idr');
        $rate_currency = $this->_getParam('val-kode');
        
        $username = QDC_User_Session::factory()->getCurrentName();
        $where = "tgl like '%$rate_date%'";

        $cek = $this->rate->fetchRow($where);
        if($rate_date != date('Y-m-d'))
        {
             $return = array("success" => false, "pesan" => "You can only input current date.");        
        }
//        else if($cek)
//        {
//            $return = array("success" => false, "pesan" => "Sorry, Rate has been exists");
//        }
        else 
        {
            $insertRate = array(
                "val_kode" => $rate_currency,
                "tgl" => date("Y-m-d H:i:s"),
                "rateidr" => $rate_idr,
                "source" => $username       
            );

            $this->rate->insert($insertRate);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    

}