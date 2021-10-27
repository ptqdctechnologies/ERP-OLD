<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/21/11
 * Time: 8:36 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_AdjustingjournalController extends Zend_Controller_Action
{
    private $adjustingjournal;
    private $counter;
    private $db;

    public function init()
    {
        $this->adjustingjournal = new Finance_Models_AdjustingJournal();
        $this->counter = new Default_Models_MasterCounter();
        $this->session = new Zend_Session_Namespace('login');
        $this->db = Zend_Registry::get('db');
    }

    public function menuAction ()
    {
        
    }

    public function insertjournalAction ()
    {
        
    }

    public function doinsertadjustingjournalAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $adjustingjournaldata = Zend_Json::decode($this->getRequest()->getParam('adjustingjournaldata'));
        $jurnal = Zend_Json::decode($this->getRequest()->getParam('jsonJurnal'));
        $type = $adjustingjournaldata[0]['type'];

//        var_dump($adjustingjournaldata,$jurnal);die;

        $trano = $this->counter->setNewTrans($type);
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        foreach($adjustingjournaldata as $key => $val)
        {
            $insertadjustingjournal = array(
                "trano" => $trano,
                "type" => $type,
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "ref_number" => $val['ref_number'],
                "tgl" => $tgl,
                "uid" => $uid,
                "ket" => $val['ket'],
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "rateidr" => $val['rate'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit'])
            );

            $this->adjustingjournal->insert($insertadjustingjournal);
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function getgeneraljurnalAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $start = $this->getRequest()->getParam('startdate');
        $end = $this->getRequest()->getParam('enddate');
        $ref_number = $this->getRequest()->getParam('ref_number');

//        var_dump($type,$startdate,$enddate,$ref_number);die;

        if ($start != '' && $end != '')
        {
            $startdate = date('Y-m-d',strtotime($start));
            $enddate = date('Y-m-d',strtotime($end));
        }

        $search = '';

        if ($type == '')
        {
            $search = null;
        }else{
            $search = "WHERE type = '$type'";
        }

        if ($start != '' && $end != '')
        {
            if ($search == null)
            {
                $search = " WHERE DATE(tgl) between '$startdate' AND '$enddate'";
            }else{
                $search .= "AND DATE(tgl) between '$startdate' AND '$enddate'";
            }
        }

        if ($ref_number != '')
        {
            if ($search == null)
            {
                $search = "WHERE ref_number like '%$ref_number%' ";
            }else{
                $search .= "AND ref_number like '%$ref_number%'";
            }
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM accounting_journal $search ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();

//        $data['data'] = $this->adjustingjournal->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
//        $data['total'] = $this->adjustingjournal->fetchAll()->count();
        $data['data'] = $fetch;
        $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    
}