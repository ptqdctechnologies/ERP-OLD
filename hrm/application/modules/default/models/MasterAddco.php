<?php

class Default_Models_MasterAddco extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_kboq2h';

    protected $_primary = 'trano';
    protected $_sit_kode;
    protected $_sit_nama;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function transferAddRevenue($afeNo = '')
    {
        $afeh = new ProjectManagement_Models_AFEh();
        $files = new Default_Models_Files();
        $counter = new Default_Models_MasterCounter();
        $cek = $afeh->fetchRow("trano = '$afeNo'");
        $statusestimate = 0;
        if ($cek)
        {
            $addRev = floatval($cek['addrevenue']);
            if ($addRev == 0)
                return true;
            $val_kode = $cek['val_kode'];

            $totalIDR = 0;$totalUSD = 0;

            if ($val_kode == 'IDR')
            {
                $totalIDR = $addRev;
            }
            else
                $totalUSD = $addRev;

            $cekFile = $files->fetchRow("trano = '$afeNo'");
            if ($cekFile)
            {
                $statusestimate = 1;
            }

            $newTrano = $counter->setNewTrans("ABOQ2");

            $arrayInsert = array(
                "trano" => $newTrano,
                "praco" => $afeNo,
                "tgl" => date('Y-m-d'),
                "tglinput" => date('Y-m-d'),
                "prj_kode" => $cek['prj_kode'],
                "prj_nama" => $cek['prj_nama'],
                "sit_kode" => $cek['sit_kode'],
                "sit_nama" => $cek['sit_nama'],
                "ket" => "Additional Revenue from AFE",
                "petugas" => $cek['user'],
                "cus_kode" => $cek['cus_kode'],
                "totaltambah" => $totalIDR,
                "user" => $cek['user'],
                "totaltambahusd" => $totalUSD,
//                "pocustomer" => $val['pocustomer'],
                "statusestimate" => $statusestimate,
                "jam" => date('H:i:s'),
                "rateidr" => $cek['rateidr']
            );

            $this->insert($arrayInsert);
        }
    }
}

?>
