<?php

class Procurement_Models_BusinessTripPayment extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_brfd_payment';

    protected $_primary = 'trano';
    protected $db;

    private $select;

    public function getPrimaryKey ()
    {
        return $this->_primary;
    }

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name()
    {
        return $this->_name;
    }

    public function getCost($params=array())
    {
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        $select = $this->db->select()
            ->from(array($this->_name),array(
                "prj_kode",
                "sit_kode",
                "workid",
                "kode_brg",
                "totalIDR" => new Zend_Db_Expr("(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END)"),
                "totalHargaUSD" => new Zend_Db_Expr("(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END)"),
            ));

        if ($prjKode)
            $select = $select->where("prj_kode = '$prjKode'");
        if ($sitKode)
            $select = $select->where("sit_kode = '$sitKode'");
        if ($workid)
            $select = $select->where("workid = '$workid'");
        if ($kodeBrg)
            $select = $select->where("kode_brg = '$kodeBrg'");
        if ($trano)
            $select = $select->where("trano = '$trano'");
        if ($exclude_trano)
            $select = $select->where("trano != '$exclude_trano'");

        $data = $this->db->fetchAll($select);
        if ($returnORM)
        {
            $this->select = $select;
            return $this;
        }

        return $data;
    }

    public function getCostSummary($params=array())
    {
        if (!$this->select)
            $this->select = $this->getCost($params);

        $select = $this->db->select()
            ->from(array("a" => $this->select),array(
                "totalIDR" => "COALESCE(SUM(a.totalIDR),0)",
                "totalUSD" => "COALESCE(SUM(a.totalHargaUSD),0)"
            ));

        $data = $this->db->fetchAll($select);
        if ($returnORM)
        {
            $this->select = select;
            return $this;
        }

        return $data;
    }

    public function newPayment($trano='', $sequence=1)
    {
        $brfd = new Procurement_Models_BusinessTripDetail();
        $cek = $brfd->fetchAll("trano = '$trano'");
        if ($cek)
        {
            if ($this->checkPayment($trano,$sequence))
                return false;

            return $this->insertPayment($trano,$sequence);
        }
        else
            return false;
    }

    public function checkPayment($trano='', $sequence=1)
    {
        $cekPayment = $this->fetchRow("trano_ref = '$trano' AND sequence = $sequence");
        if ($cekPayment)
            return true;
        else
            return false;
    }

    public function insertPayment($trano='', $sequence=1)
    {
        $counter = new Default_Models_MasterCounter();
        $brfd = new Procurement_Models_BusinessTripDetail();
        $arfh = new Procurement_Models_Procurementarfh();
        $arfd = new Procurement_Models_Procurementarfd();
        $data = $brfd->fetchRow("trano = '$trano' and sequence = $sequence");
        if (!$data)
            return false;

        $paymentTrano = $counter->setNewTrans('BRFP'); //Trano baru untuk ARF

        $arrayInsert = array(
            "trano" => $paymentTrano,
            "trano_ref" => $trano,
            "arf_no" => $paymentTrano,
            "tgl" => date("Y-m-d"),
            "sequence" => $sequence,
            "prj_kode" => $data['prj_kode'],
            "prj_nama" => $data['prj_nama'],
            "sit_kode" => $data['sit_kode'],
            "sit_nama" => $data['sit_nama'],
            "workid" => $data['workid'],
            "workname" => $data['workname'],
            "kode_brg" => $data['kode_brg'],
            "nama_brg" => $data['nama_brg'],
            "qty" => $data['qty'],
            "harga" => $data['harga'],
            "total" => $data['total'],
            "requester" => $data['requester'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "val_kode" => $data['val_kode'],
        );
        $this->insert($arrayInsert);

        //Insert into ARF
        $arrayInsert = array(
            "trano" => $paymentTrano,
            "trano_ref" => $trano,
            "tgl" => date("Y-m-d"),
            "prj_kode" => $data['prj_kode'],
            "prj_nama" => $data['prj_nama'],
            "sit_kode" => $data['sit_kode'],
            "sit_nama" => $data['sit_nama'],
            "petugas" => $data['uid'],
            "total" => $data['total'],
            "request" => $data['requester'],
            "user" => $data['uid'],
            "tglinput" => date("Y-m-d"),
            "jam" => date("H:i:s"),
            "val_kode" => $data['val_kode'],
            "bt_sequence" => $sequence,
            "bt" => 'Y',
            "ketin" => "BRF Payment Sequence No. " . $sequence . ". This ARF is auto generated from Business Trip Request (BRF) transaction, please refer to Trano : " . $trano . " for further detail."
        );
        $arfh->insert($arrayInsert);

        $arrayInsert = array(
            "trano" => $paymentTrano,
            "trano_ref" => $trano,
            "tgl" => date("Y-m-d"),
            "prj_kode" => $data['prj_kode'],
            "prj_nama" => $data['prj_nama'],
            "sit_kode" => $data['sit_kode'],
            "sit_nama" => $data['sit_nama'],
            "workid" => $data['workid'],
            "workname" => $data['workname'],
            "kode_brg" => $data['kode_brg'],
            "nama_brg" => $data['nama_brg'],
            "qty" => $data['qty'],
            "harga" => $data['harga'],
            "total" => $data['total'],
            "petugas" => $data['uid'],
            "val_kode" => $data['val_kode'],
            "requester" => $data['requester'],
            "urut" => 1,
            "bt_sequence" => $sequence,
            "bt" => 'Y',
            "ket" => "BRF Payment Sequence No. " . $sequence
        );
        $arfd->insert($arrayInsert);

        return $paymentTrano;
    }

    public function editRequest($json='')
    {
        if (!$json)
            return false;

        $json = Zend_Json::decode($json);
        $brd = new Procurement_Models_BusinessTripDetail();
        $brh = new Procurement_Models_BusinessTripHeader();

        $log['brf-payment-header-before'] = array();
        $log['brf-payment-detail-before'] = array();
        $log2['brf-payment-header-after'] = array();
        $log2['brf-payment-detail-after'] = array();

        $trano = $json[0]['trano'];
        $prev = $brh->fetchRow(
            $brd->select()
            ->where("trano = ?",$trano)
        );
        if ($prev)
        {
            $prev = $prev->toArray();
            array_push($log['brf-payment-header-before'],$prev);
            array_push($log2['brf-payment-detail-after'],$prev);
        }
        foreach($json as $k => $v)
        {
            $where = $brd->select()
                ->where("trano = ?",$v['trano'])
                ->where("prj_kode = ?",$v['prj_kode'])
                ->where("sit_kode = ?",$v['sit_kode'])
                ->where("workid = ?",$v['workid'])
                ->where("kode_brg = ?",$v['kode_brg'])
                ->where("sequence = ?",$v['sequence']);
            $prev = $brd->fetchRow($where);

            $arrayInsert = array(
                "harga" => $v['harga'],
                "total" => $v['total']
            );

            if ($prev)
            {
                $prev = $prev->toArray();
                if ($prev['is_edited'] == 'N')
                {
                    $arrayInsert['original_qty'] = $prev['qty'];
                    $arrayInsert['original_harga'] = $prev['harga'];
                    $arrayInsert['original_total'] = $prev['total'];
                    $arrayInsert['is_edited'] = 'Y';
                    array_push($log['brf-payment-detail-before'],$prev);
                }
            }

            $brd->update($arrayInsert,"trano = '{$v['trano']}' AND " .
                "prj_kode = '{$v['prj_kode']}' AND " .
                "sit_kode = '{$v['sit_kode']}' AND " .
                "workid = '{$v['workid']}' AND " .
                "kode_brg = '{$v['kode_brg']}' AND " .
                "sequence = '{$v['sequence']}'");

            $next = $brd->fetchRow($where);
            if ($next)
            {
                $next = $next->toArray();
                array_push($log2['brf-payment-detail-after'],$next);
            }
        }

        $logs = new Admin_Models_Logtransaction();
        $logs->saveLog($trano,"UPDATE",$log,$log2);

        return true;
    }
}