<?php
class Sales_Models_Praco extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_praco';

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

    public function transferTempPRACO($trano)
    {
        $counter = new Default_Models_MasterCounter();
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

                $cekboq2 = $boq2->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND type = '$type' " );

                if ($cekboq2)
                {
                    $log['boq2-before'] = $cekboq2->toArray();

                    $trano = $cekboq2['trano'];
                    $total = $cekboq2['total'];
                    $totalusd = $cekboq2['totalusd'];

                    $statusestimateditabel = $cekboq2['statusestimate'];

                    if($statusestimateditabel == 1 && $statusestimatetpraco == 0)
                    {
                        //update estimate to origin
                        $updateboq2 = array(
                            "praco" => $val['trano'],
                            "tgl" => date('Y-m-d'),
                            "total" => $val['total'],
                            "total2" => $total,
                            "totalusd" => $val['totalusd'],
                            "totalusd2" => $totalusd,
                            "tglinput" => date('Y-m-d'),
                            "jam" => date('H:i:s'),
                            "pocustomer" => $val['pocustomer'],
                            "ket" => $val['ket'],
                            "user" => $val['user'],
                            "petugas" => $val['user'],
                            "statusestimate" => $statusestimatetpraco,
                        );

                        $boq2->update($updateboq2,"prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND type = '$type'");

                        $log2['boq2-after'] = $boq2->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND type = '$type'")->toArray();

                        $jsonLog = Zend_Json::encode($log);
                        $jsonLog2 = Zend_Json::encode($log2);

                        $arrayLog = array (
                              "trano" => $trano,
                              "uid" => $val['user'],
                              "tgl" => date('Y-m-d H:i:s'),
                              "prj_kode" => $prj_kode,
                              "sit_kode" => $sit_kode,
                              "action" => "UPDATE",
                              "data_before" => $jsonLog,
                              "data_after" => $jsonLog2,
                              "ip" => $_SERVER["REMOTE_ADDR"],
                              "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
                        );

                        $logtrans->insert($arrayLog);
                    }else if($statusestimateditabel == 0 && $statusestimatetpraco == 0){
                        $newTrano = $counter->setNewTrans("BOQ2");

                        $insertboq2 = array (
                            "trano" => $newTrano,
                            "praco" => $val['trano'],
                            "tgl" => date('Y-m-d'),
                            "prj_kode" => $val['prj_kode'],
                            "prj_nama" => $val['prj_nama'],
                            "sit_kode" => $val['sit_kode'],
                            "sit_nama" => $val['sit_nama'],
                            "ket" => $val['ket'],
                            "petugas" => $val['user'],
                            "cus_kode" => $val['cus_kode'],
                            "total" => $val['total'],
                            "user" => $val['user'],
                            "totalusd" => $val['totalusd'],
                            "pocustomer" => $val['pocustomer'],
                            "statusestimate" => $statusestimatetpraco,
                            "tglinput" => date('Y-m-d'),
                            "jam" => date('H:i:s'),
                            "rateidr" => $val['rateidr'],
                            "type" => $val['type']
                        );

                        $boq2->insert($insertboq2);
                    }
                }
                else
                {
                    //insert boq2

                    $newTrano = $counter->setNewTrans("BOQ2");

                    $insertboq2 = array (
                        "trano" => $newTrano,
                        "praco" => $val['trano'],
                        "tgl" => date('Y-m-d'),
                        "prj_kode" => $val['prj_kode'],
                        "prj_nama" => $val['prj_nama'],
                        "sit_kode" => $val['sit_kode'],
                        "sit_nama" => $val['sit_nama'],
                        "ket" => $val['ket'],
                        "petugas" => $val['user'],
                        "cus_kode" => $val['cus_kode'],
                        "total" => $val['total'],
                        "user" => $val['user'],
                        "totalusd" => $val['totalusd'],
                        "pocustomer" => $val['pocustomer'],
                        "statusestimate" => $statusestimatetpraco,
                        "tglinput" => date('Y-m-d'),
                        "jam" => date('H:i:s'),
                        "rateidr" => $val['rateidr'],
                        "type" => $val['type']
                );

                $boq2->insert($insertboq2);

                }

            }
        }

//        $fetch = $this->fetchAll("trano = '$trano'");
//        if ($fetch)
//        {
//            $hasil = $fetch->toArray();
//
//            $newTrano = $counter->setNewTrans("BOQ2");
//
//            $statusestimate = '';
//
//            foreach ($hasil as $key => $val)
//            {
//                if ($val['statusco'] == 'estimate')
//                {
//                    $statusestimate = 1;
//                }else
//                {
//                    $statusestimate = 0;
//                }
//
//                $insertboq2 = array (
//                    "trano" => $newTrano,
//                    "praco" => $val['trano'],
//                    "tgl" => date('Y-m-d'),
//                    "prj_kode" => $val['prj_kode'],
//                    "prj_nama" => $val['prj_nama'],
//                    "sit_kode" => $val['sit_kode'],
//                    "sit_nama" => $val['sit_nama'],
//                    "ket" => $val['ket'],
//                    "petugas" => $val['user'],
//                    "cus_kode" => $val['cus_kode'],
//                    "total" => $val['total'],
//                    "user" => $val['user'],
//                    "totalusd" => $val['totalusd'],
//                    "pocustomer" => $val['pocustomer'],
//                    "statusestimate" => $statusestimate,
//                    "tglinput" => date('Y-m-d'),
//                    "jam" => date('H:i:s'),
//                    "rateidr" => $val['rateidr']
//
//                );
//
//                $boq2->insert($insertboq2);
//
//            }
////            $boq2->insert($hasil);
////            $this->delete("trano = '$trano'");
//
//        }

    }
}

?>