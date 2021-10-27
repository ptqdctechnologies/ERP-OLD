<?php
class Sales_Models_AddPraco extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_addpraco';

    protected $_primary = 'trano';
        protected $db;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

        public function __construct()
    {
                parent::__construct($this->_option);
                $this->db = Zend_Registry::get('db');
    }

    public function transferTempAPRACO($trano)
    {
        $counter = new Default_Models_MasterCounter();
		$addco = new Default_Models_MasterAddco();
		$boq2 = new Default_Models_MasterBoq2();
        $logtrans = new Admin_Model_Logtransaction();

        $fetch = $this->fetchAll("trano = '$trano'");
        if ($fetch)
        {
            $hasil = $fetch->toArray();

            $status = $hasil[0]['statusco'];

            if ($status == 'estimate')
            {
                $statusestimatetpraco = 1;
            }else
            {
                $statusestimatetpraco = 0;
            }

            $statusestimate = '';

            foreach($hasil as $key => $val)
            {
                $prj_kode = $val['prj_kode'];
                $sit_kode = $val['sit_kode'];
                $type = $val['type'];

//                $cekboq2  = $boq2->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode'","trano DESC, tgl DESC, jam DESC");
//
//                $lastAddco = $addco->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode'","trano DESC, tgl DESC, jam DESC");
//
//                if ($lastAddco)
//                {
//                    $lastAddco = $lastAddco->toArray();
//                    $totaltotal = $lastAddco['totaltotal'];
//                    $totaltotalusd = $lastAddco['totaltotalusd'];
//                }

                $newTrano = $counter->setNewTrans("ABOQ2");

                $insertboq2 = array (
                    "trano" => $newTrano,
                    "praco" => $val['trano'],
                    "tgl" => date('Y-m-d'),
                    "tglinput" => date('Y-m-d'),
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "ket" => $val['ket'],
                    "petugas" => $val['user'],
                    "cus_kode" => $val['cus_kode'],
                    "totaltambah" => $val['total'],
                    "user" => $val['user'],
                    "totaltambahusd" => $val['totalusd'],
                    "pocustomer" => $val['pocustomer'],
                    "statusestimate" => $statusestimatetpraco,
                    "jam" => date('H:i:s'),
                    "rateidr" => $val['rateidr'],
                    "type" => $val['type']
                );

                $addco->insert($insertboq2);

//                }

            }
        }
    }
}

?>