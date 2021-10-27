<?php

class Default_Models_MasterCboq3H extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_kboq3h';
    protected $_primary = 'trano';

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function transferAFEtoBOQ3($afeTrano='')
    {
        $this->session = new Zend_Session_Namespace('login');
        $afes = new ProjectManagement_Models_AFESaving();
        $afe = new ProjectManagement_Models_AFE();
        $counter = new Default_Models_MasterCounter();
        $boq3 = new Default_Models_MasterBoq3();
        $boq3h = new Default_Models_MasterBoq3H();
        $cboq3 = new Default_Models_MasterCboq3();

        $jsonData = $afe->fetchAll("trano = '$afeTrano'");
        $jsonDatas = $afes->fetchAll("trano = '$afeTrano'");

        $trano = $counter->setNewTrans("CBOQ3");

        if (!$jsonData && !$jsonDatas)
        {
            return false;
        }

        $tmp = array();
        if ($jsonData)
        {
            $jsonData = $jsonData->toArray();
            foreach ($jsonData as $k => $v)
            {
                $tmp[] = $v;
            }
        }
        if ($jsonDatas)
        {
            $jsonDatas = $jsonDatas->toArray();
            foreach ($jsonDatas as $k => $v)
            {
                $tmp[] = $v;
            }
        }

        $jsonData = $tmp;

        $totals = 0;$totalsUSD = 0;
        $urut = 1;$total = 0;
        $totalUSD = 0;
        foreach($jsonData as $key => $val)
        {
            if ($val['val_kode'] == 'IDR')
                $total = $val['qtybaru']*$val['hargabaru'];
            else
                $totalUSD = $val['qtybaru']*$val['hargabaru'];

		    $masterBarang = new Default_Models_MasterBarang();
            $kode = $val['kode_brg'];
            $barang = $masterBarang->fetchRow("kode_brg = '$kode'");
            if ($barang)
            {
                if ($barang['stspmeal'] == 'Y' )
                {
                    $pmeal = 'Y';
                }
                else
                {
                    $pmeal = 'N';
                }
            }
            $arrayInsert = array(
				"trano" => $trano,
				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"qty" => $val['qtybaru'],
				"harga" => $val['hargabaru'],
                "stspmeal" => $pmeal,
				"total" => $total,
				"ket" => $val['ket'],
                "cfs_kode" => $val['cfs_kode'],
                "cfs_nama" => $val['cfs_nama'],
				"petugas" => $this->session->userName,
				"val_kode" => $val['val_kode'],
				"afe_no" => $val['trano'],
                "rateidr" => $val['rateidr'],

			);
            $urut++;
            $totals = $totals + $total;
            $totalsUSD = $totalsUSD + $totalUSD;
            $cboq3->insert($arrayInsert);
        }

        foreach($jsonData as $key => $val)
        {
            $total = $val['qtybaru']*$val['hargabaru'];
            $masterBarang = new Default_Models_MasterBarang();
            $kode = $val['kode_brg'];
            $barang = $masterBarang->fetchRow("kode_brg = '$kode'");
            if ($barang)
            {
                if ($barang['stspmeal'] == 'Y' )
                {
                    $pmeal = 'Y';
                }
                else
                {
                    $pmeal = 'N';
                }
            }
            $arrayInsert = array(

				"tgl" => date('Y-m-d'),
				"urut" => $urut,
				"prj_kode" => $val['prj_kode'],
				"prj_nama" => $val['prj_nama'],
				"sit_kode" => $val['sit_kode'],
				"sit_nama" => $val['sit_nama'],
				"workid" => $val['workid'],
				"workname" => $val['workname'],
				"kode_brg" => $val['kode_brg'],
				"nama_brg" => $val['nama_brg'],
				"qty" => $val['qtybaru'],
				"harga" => $val['hargabaru'],
                "stspmeal" => $pmeal,
				"total" => $total,
				"ket" => $val['ket'],
                "cfs_kode" => $val['cfs_kode'],
                "cfs_nama" => $val['cfs_nama'],
				"petugas" => $this->session->userName,
                "tranorev" => $trano,
                "rev" => 'Y',
				"val_kode" => $val['val_kode'],
                "rateidr" => $val['rateidr'],

			);
            $urut++;
            $boq3->insert($arrayInsert);
        }

            $project = new Default_Models_MasterProject();

            $cusKode = $project->getProjectAndCustomer($jsonData[0]['prj_kode']);
            $cusKode = $cusKode[0]['cus_kode'];

        	$arrayInsert = array (
            	"trano" => $trano,
				"tgl" => date('Y-m-d'),

				"prj_kode" => $jsonData[0]['prj_kode'],
				"prj_nama" => $jsonData[0]['prj_nama'],
				"sit_kode" => $jsonData[0]['sit_kode'],
				"sit_nama" => $jsonData[0]['sit_nama'],
				"ket" => $jsonData[0]['ket'],
                "cus_kode" => $cusKode,

				"total" => $totals,
                "user" => $this->session->userName,
                "tglinput" => date('Y-m-d'),
                "jam" => date('H:i:s'),
				"totalusd" => $totalsUSD,
                "customercontract" => $jsonData[0]['customercontract'],

				"afe_no" => $jsonData[0]['trano'],
                "rateidr" => $jsonData[0]['rateidr']

            );
            $this->insert($arrayInsert);
        
            $prjKode = $jsonData[0]['prj_kode'];
            $sitKode = $jsonData[0]['sit_kode'];

            $budget = new Default_Models_Budget();
            $boq2 = $budget->getBoq2("summary-current",$prjKode,$sitKode);
            $boq3 = $budget->getBoq3("summary-current",$prjKode,$sitKode);

            $utility = Zend_Controller_Action_HelperBroker::getStaticHelper('utility');
            $rate = $utility->getExchangeRate();

            $boq2curr = floatval($boq2['totalCurrentIDR']) + floatval($boq2['totalCurrentUSD']);
            $boq2curr2 = floatval($boq2['totalCurrentIDR']) + (floatval($boq2['totalCurrentHargaUSD']) * floatval($rate));
            $boq3curr = floatval($boq3['totalIDR']) + floatval($boq3['totalUSD']);
            $boq3curr2 = floatval($boq3['totalIDR']) + (floatval($boq3['totalHargaUSD']) * floatval($rate));


            if ($boq2curr > 0)
            {
                $gm = (((floatval($boq2curr) - floatval($boq3curr)) / floatval($boq2curr))) * 100;
                $gm2 = (((floatval($boq2curr2) - floatval($boq3curr2)) / floatval($boq2curr2))) * 100;
            }
            else
                $gm = 0;
            $logs = array(
                "nama" => "CURRENT_GM_FROM_CBOQ3",
                "tgl" => date('Y-m-d H:i:s'),
                "uid" => $this->session->userName,
                "prj_kode" => $prjKode,
                "sit_kode" => $sitKode,
                "data" => Zend_Json::encode(array(
                    "current_gm" => $gm,
                    "current_gm_with_rate" => $gm2,
                    "rateidr" => $rate,
                    "trano" => $trano
                ))
            );
            $log = new Default_Models_Log();
            $log->insert($logs);
    }

    public function transferAFESwitchingtoBOQ3($afeTrano='')
    {
        $this->session = new Zend_Session_Namespace('login');
        $afes = new ProjectManagement_Models_AFESaving();
        $afe = new ProjectManagement_Models_AFE();
        $afeh = new ProjectManagement_Models_AFEh();
        $counter = new Default_Models_MasterCounter();
        $boq3 = new Default_Models_MasterBoq3();
        $boq3h = new Default_Models_MasterBoq3H();
        $cboq3 = new Default_Models_MasterCboq3();

        $jsonData = $afe->fetchAll("trano = '$afeTrano'");
        $jsonDatas = $afes->fetchAll("trano = '$afeTrano'");

        $trano = $counter->setNewTrans("CBOQ3");

        if (!$jsonData && !$jsonDatas)
        {
            return false;
        }

        $tmp = array();
        if ($jsonDatas)
        {
            $jsonDatas = $jsonDatas->toArray();
            foreach ($jsonDatas as $k => $v)
            {
                $tmp[] = $v;
            }
        }

        if ($jsonData)
        {
            $jsonData = $jsonData->toArray();
            foreach ($jsonData as $k => $v)
            {
                $tmp[] = $v;
            }
        }

        $jsonData = $tmp;

        $totals = 0;$totalsUSD = 0;
        $urut = 1;$total = 0;
        $totalUSD = 0;
        foreach($jsonData as $key => $val)
        {
            if ($val['val_kode'] == 'IDR')
                $total = $val['qtybaru']*$val['hargabaru'];
            else
                $totalUSD = $val['qtybaru']*$val['hargabaru'];

            $masterBarang = new Default_Models_MasterBarang();
            $kode = $val['kode_brg'];
            $barang = $masterBarang->fetchRow("kode_brg = '$kode'");
            if ($barang)
            {
                if ($barang['stspmeal'] == 'Y' )
                {
                    $pmeal = 'Y';
                }
                else
                {
                    $pmeal = 'N';
                }
            }
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qtybaru'],
                "harga" => $val['hargabaru'],
                "stspmeal" => $pmeal,
                "total" => $total,
                "ket" => $val['ket'],
                "cfs_kode" => $val['cfs_kode'],
                "cfs_nama" => $val['cfs_nama'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "afe_no" => $val['trano'],
                "rateidr" => $val['rateidr'],

            );
            $urut++;
            $totals = $totals + $total;
            $totalsUSD = $totalsUSD + $totalUSD;
            $cboq3->insert($arrayInsert);
        }
        $urut = 1;
        foreach($jsonData as $key => $val)
        {
            $total = $val['qtybaru']*$val['hargabaru'];
            $masterBarang = new Default_Models_MasterBarang();
            $kode = $val['kode_brg'];
            $barang = $masterBarang->fetchRow("kode_brg = '$kode'");
            if ($barang)
            {
                if ($barang['stspmeal'] == 'Y' )
                {
                    $pmeal = 'Y';
                }
                else
                {
                    $pmeal = 'N';
                }
            }
            $arrayInsert = array(

                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qtybaru'],
                "harga" => $val['hargabaru'],
                "stspmeal" => $pmeal,
                "total" => $total,
                "ket" => $val['ket'],
                "cfs_kode" => $val['cfs_kode'],
                "cfs_nama" => $val['cfs_nama'],
                "petugas" => $this->session->userName,
                "tranorev" => $trano,
                "rev" => 'Y',
                "val_kode" => $val['val_kode'],
                "rateidr" => $val['rateidr'],

            );
            $urut++;
            $boq3->insert($arrayInsert);
        }

        $project = new Default_Models_MasterProject();

        $cusKode = $project->getProjectAndCustomer($jsonData[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];

        $arrayInsert = array (
            "trano" => $trano,
            "tgl" => date('Y-m-d'),

            "prj_kode" => $jsonData[0]['prj_kode'],
            "prj_nama" => $jsonData[0]['prj_nama'],
            "sit_kode" => $jsonData[0]['sit_kode'],
            "sit_nama" => $jsonData[0]['sit_nama'],
            "ket" => $jsonData[0]['ket'],
            "cus_kode" => $cusKode,

            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "totalusd" => $totalsUSD,
            "customercontract" => $jsonData[0]['customercontract'],

            "afe_no" => $jsonData[0]['trano'],
            "rateidr" => $jsonData[0]['rateidr']

        );
        $this->insert($arrayInsert);

        $prjKode = $jsonData[0]['prj_kode'];
        $sitKode = $jsonData[0]['sit_kode'];

        $budget = new Default_Models_Budget();
        $boq2 = $budget->getBoq2("summary-current",$prjKode,$sitKode);
        $boq3 = $budget->getBoq3("summary-current",$prjKode,$sitKode);

        $utility = Zend_Controller_Action_HelperBroker::getStaticHelper('utility');
        $rate = $utility->getExchangeRate();
        $cekAfe = $afeh->fetchRow("trano = '$trano'");
        if ($cekAfe)
        {
            $rateAfe = $cekAfe['rateidr'];
        }
        else
            $rateAfe = $rate;

        $boq2curr = floatval($boq2['totalCurrentIDR']) + floatval($boq2['totalCurrentUSD']);
        $boq2curr2 = floatval($boq2['totalCurrentIDR']) + (floatval($boq2['totalCurrentHargaUSD']) * floatval($rate));
        $boq3curr = floatval($boq3['totalIDR']) + floatval($boq3['totalUSD']);
        $boq3curr2 = floatval($boq3['totalIDR']) + (floatval($boq3['totalHargaUSD']) * floatval($rate));


        if ($boq2curr > 0)
        {
            $gm = (((floatval($boq2curr) - floatval($boq3curr)) / floatval($boq2curr))) * 100;
            $gm2 = (((floatval($boq2curr2) - floatval($boq3curr2)) / floatval($boq2curr2))) * 100;
        }
        else
            $gm = 0;

        $boq2curr2 = floatval($boq2['totalCurrentIDR']) + (floatval($boq2['totalCurrentHargaUSD']) * floatval($rateAfe));
        $boq3curr2 = floatval($boq3['totalIDR']) + (floatval($boq3['totalHargaUSD']) * floatval($rateAfe));


        if ($boq2curr > 0)
        {
            $gm3 = (((floatval($boq2curr2) - floatval($boq3curr2)) / floatval($boq2curr2))) * 100;
        }

        $logs = array(
            "nama" => "CURRENT_GM_FROM_CBOQ3",
            "tgl" => date('Y-m-d H:i:s'),
            "uid" => $this->session->userName,
            "prj_kode" => $prjKode,
            "sit_kode" => $sitKode,
            "data" => Zend_Json::encode(array(
                "current_gm" => $gm,
                "current_gm_with_rate" => $gm2,
                "current_gm_with_rate_afe" => $gm3,
                "rateidr" => $rate,
                "rateidr_afe" => $rateAfe,
                "trano" => $trano
            ))
        );
        $log = new Default_Models_Log();
        $log->insert($logs);
    }

}

?>