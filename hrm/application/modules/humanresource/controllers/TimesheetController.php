<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/22/11
 * Time: 8:47 AM
 * To change this template use File | Settings | File Templates.
 */

class Humanresource_TimesheetController extends Zend_Controller_Action
{

    private $setperiode;
    
    public function init()
    {
        $this->setperiode = new HumanResource_Model_SetPeriode();
    }

    public function timesheetperiodeAction ()
    {
        
    }

    public function setperiodeAction()
    {
        $this->view->year = date("Y");
    }

    public function insertperiodeAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $year = $this->getRequest()->getParam('year');
        $month = $this->getRequest()->getParam('month');
        $startdate = $this->getRequest()->getParam('startdt');
        $enddate = $this->getRequest()->getParam('enddt');
        $hour = $this->getRequest()->getParam('hour');

        $month = date('n',strtotime($month));

        $where = "tahun = '$year' AND periode = '$month'  ";

        $cekperiode = $this->setperiode->fetchRow ($where);
        
        if($cekperiode)
        {
            $return = array("success" => false, "pesan" => "Sorry, Periode has been exist");
        }else
        {
            $updateperiode = array(
                "periode_act" => 0
            );

            $this->setperiode->update($updateperiode,null);

            $insertperiode = array(
                "tahun" => $year,
                "periode" => $month,
                "tgl_aw" => date('Y-m-d',strtotime($startdate)),
                "tglak" => date('Y-m-d',strtotime($enddate)),
                "periode_act" => 1,
                "jumlah_jam_bulan" => $hour
            );

            $this->setperiode->insert($insertperiode);


            $return = array ("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function editperiodeAction ()
    {
        
    }

    public function viewperiodeAction ()
    {

    }

    public function getviewperiodeAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 5;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';


        $periodedata = $this->setperiode->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();

        foreach ($periodedata as $key => $val)
        {
            $timestamp = mktime(0, 0, 0, intval($val['periode']),1,floatval(date("Y")));
            $periodedata[$key]['periode'] = date("F",$timestamp);
            if ($val['periode_act'] == 0)
                $periodedata[$key]['periode_act'] = 'NOT ACTIVE';
            else
                $periodedata[$key]['periode_act'] = 'ACTIVE';
        }

        $return['data'] = $periodedata;
        $return['total'] = $this->setperiode->fetchAll()->count();

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);


    }

    public function editAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');
        $year = $this->getRequest()->getParam('tahun');
        $month = $this->getRequest()->getParam('periode');
        $startdate = $this->getRequest()->getParam('tgl_aw');
        $enddate = $this->getRequest()->getParam('tglak');
        $hour = $this->getRequest()->getParam('jumlah_jam_bulan');

        $where = "id = '$id'";

        $month = date('n',strtotime($month));

        $update = array(

            "tahun" => $year,
            "periode" => $month,
            "tgl_aw" => date('Y-m-d',strtotime($startdate)),
            "tglak" => date('Y-m-d',strtotime($enddate)),
            "jumlah_jam_bulan" => $hour

        );

        $this->setperiode->update($update,$where);

        $return = array ("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

//        $month = date('n',strtotime($month));
//
//        $where = "tahun = '$year' AND periode = '$month'  ";
//
//        $cekperiode = $this->setperiode->fetchRow ($where);
//
//        if($cekperiode)
//        {
//            $return = array("success" => false, "pesan" => "Sorry, Periode has been exist");
//        }



    }


}
 
