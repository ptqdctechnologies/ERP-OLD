<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bherly
 * Date: 1/16/12
 * Time: 10:05 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_PeriodeController extends Zend_Controller_Action
{

    private $periode;
    private $session;
    private $log;

    public function init()
    {
        $this->periode = new Finance_Models_MasterPeriode();
        $this->session = new Zend_Session_Namespace('login');
        $this->log = new Admin_Models_Logtransaction();
    }

    public function financeperiodeAction ()
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
        $perkode = $this->getRequest()->getParam('perkode');

        $month = date('m',strtotime($month));

        $where = "perkode = '$perkode'  ";

        $cekperiode = $this->periode->fetchRow ($where);

        if($cekperiode)
        {
            $return = array("success" => false, "pesan" => "Sorry, Periode Code has been exist");
        }else
        {
            $return = array();
            $updateperiode = array(
                "aktif" => 0
            );

            $this->periode->update($updateperiode,null);

            $insertperiode = array(
                "tahun" => $year,
                "perkode" => $perkode,
                "bulan" => $month,
                "tgl_awal" => date('Y-m-d',strtotime($startdate)),
                "tgl_akhir" => date('Y-m-d',strtotime($enddate)),
                "aktif" => 1,
                "tahun" => $year,
                "uid" => $this->session->userName,
                "tgl" => date("Y-m-d H:i:s")
            );

            $this->periode->insert($insertperiode);


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
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';


        $periodedata = $this->periode->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();

        foreach ($periodedata as $key => $val)
        {
            $periodedata[$key]['is_aktif'] = $val['aktif'];
            if ($val['aktif'] == 0)
                $periodedata[$key]['aktif'] = 'NOT ACTIVE';
            else
                $periodedata[$key]['aktif'] = 'ACTIVE';
        }

        $return['data'] = $periodedata;
        $return['total'] = $this->periode->fetchAll()->count();

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
        $perkode = $this->getRequest()->getParam('perkode');
        $startdate = $this->getRequest()->getParam('tgl_awal');
        $enddate = $this->getRequest()->getParam('tgl_akhir');
        $month = $this->getRequest()->getParam('bulan');

        $where = "id = '$id'";

        $month = date('n',strtotime($month));

        $cek = $this->periode->fetchRow("id = $id");
        if ($cek)
        {
            $oldPerkode = $cek['perkode'];
            if ($oldPerkode != $perkode)
            {
                $ceks = $this->periode->fetchRow("perkode = '$perkode'");
                if ($ceks)
                {
                    $return = array("success" => false, "pesan" => "Sorry, Periode Code has been exist");
                    $json = Zend_Json::encode($return);
                    $this->getResponse()->setHeader('Content-Type','text/javascript');
                    $this->getResponse()->setBody($json);
                    return false;
                }
            }
            $ceks = $cek->toArray();
            unset($ceks['id']);
            $log['financeperiode-before'] = $ceks;
        }

        $update = array(
            "perkode" => $perkode,
            "tahun" => $year,
            "bulan" => $month,
            "tgl_awal" => date('Y-m-d',strtotime($startdate)),
            "tgl_akhir" => date('Y-m-d',strtotime($enddate)),
            "bulan" => $month,
            "uid" => $this->session->userName,
            "tgl" => date("Y-m-d H:i:s")
        );

        $log2['financeperiode-after'] = $update;

        $this->periode->update($update,$where);

        $return = array ("success" => true);

        //Log Transaction
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array (
              "trano" => 'FINANCE-PERIODE-' . $perkode,
              "uid" => $this->session->userName,
              "tgl" => date('Y-m-d H:i:s'),
              "action" => "UPDATE",
              "data_before" => $jsonLog,
              "data_after" => $jsonLog2,
              "ip" => $_SERVER["REMOTE_ADDR"],
              "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function openPeriodeAction()
    {

    }

    public function doOpenPeriodeAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');

        $success = false;
        $cek = $this->periode->fetchRow("id = $id");
        if ($cek)
        {
            $perkode = $cek['perkode'];
            $start = date("Y-m-d",strtotime($cek['tgl_awal']));
            $end = date("Y-m-d",strtotime($cek['tgl_akhir']));

            $allJurnal = QDC_Finance_Jurnal::factory()->getAllModel();

            $FINANCE = QDC_Model_Finance::init($allJurnal);

            $arrayUpdate = array(
                "stsclose" => 0
            );
            $where = "tgl BETWEEN '$start 00:00:00' AND '$end 23:59:59' AND stsclose = 1";
            foreach($allJurnal as $k => $v)
            {
                $FINANCE->{$v}->update($arrayUpdate,$where);
            }

            $this->periode->update(array(
                "aktif" => 0
            ),null);

            $this->periode->update(array(
                "stsclose" => 0,
                "aktif" => 1
            ),"id = $id");

            //Log Transaction
            $arrayLog = array (
                "trano" => 'FINANCE-OPEN-PERIODE-' . $perkode,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date('Y-m-d H:i:s'),
                "data_before" => array($cek),
                "action" => "UPDATE",
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);
            $success = true;
        }

        $return = array ("success" => $success);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }
}