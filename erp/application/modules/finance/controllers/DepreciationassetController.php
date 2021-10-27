<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/2/12
 * Time: 9:34 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_DepreciationassetController extends Zend_Controller_Action
{
    private $FINANCE;
    private $periodeOpen;
    private $depre;
    private $asset;
    private $counter;
    private $session;
    private $db;
    private $saldo_coa;
    private $coa;

    public function init()
    {
        $this->FINANCE = QDC_Model_Finance::init(array(
            "AccountingCloseAP",
            "AccountingCloseAR",
            "AccountingJurnalBank",
            "AccountingSaldoCoa",
            "AccountingSaldoRL",
            "AdjustingJournal"
        ));

        $this->periodeOpen = QDC_Finance_Periode::factory(array("notClose" => true))->getCurrentPeriode();
        $this->depre = new Finance_Models_DepreciationFixedAsset();
        $this->asset = new Logistic_Models_MasterFixedAsset();
        $this->counter = new Default_Models_MasterCounter();
        $this->session = new Zend_Session_Namespace('login');
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->saldo_coa = new Finance_Models_AccountingSaldoCoa();
        $this->coa = new Finance_Models_MasterCoa();
    }

    public function menuAction ()
    {

    }

    public function insertdepreciationAction ()
    {
        if ($this->periodeOpen)
        {
            $this->view->year = $this->periodeOpen['tahun'];
            $this->view->perkode = $this->periodeOpen['perkode'];
            $this->view->month = date("F",strtotime($this->periodeOpen['tahun'] . "-" . $this->periodeOpen['bulan'] . "-01"));
            $this->view->tgl_awal = date("d M Y",strtotime($this->periodeOpen['tgl_awal']));
            $this->view->tgl_akhir = date("d M Y",strtotime($this->periodeOpen['tgl_akhir']));
        }
    }

    public function doinsertdepreciationAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $perkode = $this->getRequest()->getParam('perkode');
        $year = $this->getRequest()->getParam('year');
        $bulan = $this->getRequest()->getParam('month');

        $month = date("m",strtotime($bulan));

//        var_dump($month);

        $cekperiode = $this->depre->fetchRow("periode = '$perkode'");

        if ($cekperiode)
        {
            echo "{success: false, msg: 'Sorry, Depreciation this periode has been processed'}";

            return false;
        }

        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        $type = 'FIX';
        $trano = $this->counter->setNewTrans($type);

        $asset = $this->asset->fetchAll("status_aktif = 1")->toArray();

        foreach($asset as $k => $v){

            $insert = array(
                "trano" => $trano,
                "date" => $tgl,
                "uid" => $uid,
                "token" => $v['token'],
                "nilai" => $v['depr_exp'],
                "periode" => $perkode,
//                "bulan" => $month,
                "tahun" => $year,
                "ref_number" => $v['trano']
            );

            $this->depre->insert($insert);

            if ($v['total_depr'] == null || $v['total_depr'] == '' || $v['total_depr'] == '0.00'  )
            {
                $sql = "SELECT ref_number,SUM(nilai) as total FROM finance_depreciation_fixed_asset WHERE ref_number = '{$v['trano']}' GROUP BY ref_number";
                $fetch = $this->db->query($sql);
                $nilai_total = $fetch->fetch();
                $nilai_total = $nilai_total['total'];

                $updatetotal = array(
                    "total_depr" => $nilai_total
                );

            }else{
                $nilai_total = floatval($v['total_depr'] + $v['depr_exp']);

                $updatetotal = array(
                    "total_depr" => $nilai_total
                );

            }
            

            $this->asset->update($updatetotal,"trano = '{$v['trano']}'");

            if ($nilai_total >= $v['purchase_price'])
            {
                $update = array(
                    "status_aktif" => 0
                );

                $this->asset->update($update,"trano = '{$v['ref_number']}'");
            }

        }

        //insert closing fixed asset

        $query = "SELECT FD.periode,FD.tahun,MF.val_kode,sum(total_depr) as total,MK.coa_debit,MK.coa_credit
                FROM (finance_depreciation_fixed_asset FD
                LEFT JOIN master_fixed_asset MF ON FD.ref_number = MF.trano)
                LEFT JOIN master_kategori_fa MK ON MK.kode_ktfa = MF.kode_kategori
                WHERE FD.periode = '$perkode'
                GROUP BY MF.kode_kategori";
        $fetch = $this->db->query($query);
        $saldo = $fetch->fetchAll();

        foreach($saldo as $key => $val)
        {
            $debit = $val['coa_debit'];
            $credit = $val['coa_credit'];

            //untuk debit
            $totalSebelumnya =0;
            $saldo_coa_debit = $this->saldo_coa->fetchRow("coa_kode = '$debit' AND periode = '$month' AND tahun = '$year' ");
            if ($saldo_coa_debit)
            {
                $totalSebelumnya = $saldo_coa_debit['totaldebit'];
                $totalBaru = $val['total'] + $totalSebelumnya;
                $arrayUpdate = array(
                    "totaldebit" => $totalBaru,
                    "total" => $totalBaru
                );

                $this->saldo_coa->update($arrayUpdate,"coa_kode = '$debit' AND periode = '$month' AND tahun = '$year' ");
            }
            else
            {
                $coa = $this->coa->fetchRow("coa_kode = '$debit'");

                $arrayinsertdebit = array(
                    "coa_kode" => $debit,
                    "coa_nama" => $coa['coa_nama'],
                    "totaldebit" => $val['total'],
                    "totalkredit" => 0,
                    "val_kode" => $val['val_kode'],
                    "periode" => $month,
                    "tahun" => $val['tahun'],
                    "total" => $val['total']
                );

                $this->saldo_coa->insert($arrayinsertdebit);
            }

            //untuk credit
            $saldo_coa_credit = $this->saldo_coa->fetchRow("coa_kode = '$credit' AND periode = '$month' AND tahun = '$year' ");
            if ($saldo_coa_credit)
            {
                $totalSebelumnya = $saldo_coa_credit['totalkredit'];
                $totalBaru = $val['total'] + $totalSebelumnya;
                $arrayUpdate = array(
                    "totalkredit" => $totalBaru,
                    "total" => $totalBaru
                );

                $this->saldo_coa->update($arrayUpdate,"coa_kode = '$credit' AND periode = '$month' AND tahun = '$year' ");
            }
            else
            {
                $coa = $this->coa->fetchRow("coa_kode = '$credit'");

                $arrayinsertcredit = array(
                    "coa_kode" => $credit,
                    "coa_nama" => $coa['coa_nama'],
                    "totaldebit" => 0,
                    "totalkredit" => $val['total'],
                    "val_kode" => $val['val_kode'],
                    "periode" =>$month,
                    "tahun" => $val['tahun'],
                    "total" => $val['total']
                );

                $this->saldo_coa->insert($arrayinsertcredit);
            }



        }

//        var_dump($saldo);die;


        $return = array("success" => true,"trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getdepreciationAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $token = $this->getRequest()->getParam('token');

        if ($token != null || $token != '')
        {
            $where = "token = '$token'";
        }

        $depre = $this->depre->fetchAll($where);

        if ($depre != null){
            $data['data'] = $depre->toArray();
            $data['total'] = $depre->count();
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getperiodedepreciationAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $sql = "SELECT SQL_CALC_FOUND_ROWS DA.trano,DA.date,DA.uid,DA.token,sum(DA.nilai) as nilai,DA.periode,DA.tahun,MP.tgl_awal,
                MP.tgl_akhir,MP.bulan as periode_bulan,MP.tahun as periode_tahun,MP.uid as periode_uid,
                MP.tgl as periode_tgl FROM finance_depreciation_fixed_asset DA
                LEFT JOIN master_periode MP ON DA.periode = MP.perkode group by DA.periode";
        $fetch = $this->db->query($sql);
        $data['data'] = $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getdetailperiodeAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'DF.date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = "SELECT SQL_CALC_FOUND_ROWS DF.trano,DF.date,DF.uid,DF.token,DF.nilai,DF.periode,DF.tahun,MF.description
                FROM finance_depreciation_fixed_asset DF
                LEFT JOIN master_fixed_asset MF ON DF.token = MF.token
                WHERE DF.trano = '$trano'
                ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query($sql);
        $data['data'] = $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }
}