<?php

class Default_Models_MasterCounter extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_countertransaksi';

    protected $_primary = 'id';
	protected $db;
    private $transType = array();

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');

        $this->transType = array(
            'PR'=>"PRF",
            'ARF'=>"ARF01",
            'RPI'=>"RPI01",
            'BOQ3'=>"BOQ3",
            'PO'=>"PO01",
            'DOR'=>"DOR",
            'DO'=>"DO01",
            'iCAN'=>"I-Can01",
            'iLOV'=>"I-Lov01",
            'iSUP'=>"I-Sup01",
            'BOQ2'=>"BOQ2",
            'ABOQ2'=>"ABOQ2",
            'ASF'=>"ASF01",
            'PBOQ3'=>"PBQ110",
            'CBOQ3'=>"CBOQ3",
            'AFE'=>"AFE",
            'AFEN'=>"AFEN",
            'CBOQ3N'=>"CBOQ3N",
            'CBOQ3N2'=>"CBOQ3N2",
            'PC'=>"PC01",
            'SER'=>"SER01",
            'SEU'=>"SEU01",
            'SOR'=>"SOR01",
            'SOU'=>"SOU01",
            'RPC'=>"RPC01",
            'BER'=>"BER",
            'BOR'=>"BOR",
            'PER'=>"PER01",
            'PEU'=>"PEU01",
            'POU'=>"POU01",
            'POR'=>"POR01",
            'PRABOQ3'=>"PRABOQ3",
            'REM'=>"REM01",
            'BGO'=>"BGO",//Budget Overhead
            'PRO'=>"PR02",
            'POO'=>"PO02",
            'ARFO'=>"ARF02",
            'ASFO'=>"ASF02",
            'RPIO'=>"RPI02",
            'PRABOQ2'=>"PRABOQ2",
            'TSHEET'=>"TSHEET",//Timesheet]
            'PRAOHP'=>"PRAOHP01",
            'OHP'=>"OHP01",
            'PRABGO'=>"PRABGO",//Pra Budget Overhead
            'DN01'=>"DN01",
            'RINV'=>"RINV",
            'INV'=>"INV",
            'BPV'=>"BPV",
            'PRACO'=>"PRACO",
            'REGCO'=>"REGCO",
            'APRACO'=>"APRACO",
            'RBE'=>"RBE",
            'RBO'=>"RBO",
            'RPE'=>"RPE",
            'RPU'=>"RPU",
            'RPO'=>"RPO",
            'RPD'=>"RPD",
            'REU'=>"REU",
            'ROU'=>"ROU",
            'RER'=>"RER",
            'ROR'=>"ROR",
            'BRM'=>"BRM",
            'BSM'=>"BSM",
            'PRM'=>"PRM",
            'PSM'=>"PSM",
            'ADJ'=>"ADJ",
            'CIP'=>"CIP",
            'NWS'=>"NWS",
            'REP'=>"REP",
            'CNP'=>'CNP',
            'JS'=> 'JS', //settlement journal
            'SJ'=> 'SJ', //sales journal
            'JV'=> 'JV' //voucher journal
        );
    }

    private function getTransType($type='')
    {
//        switch ($type)
//    	{
//    		case 'PR':
//    				$query = "PRF";
//                    break;
//            case 'ARF':
//    			$query = "ARF01";
//                break;
//    		case 'RPI':
//    				$query = "RPI01";
//    			break;
//    		case 'BOQ3':
//					$query = "BOQ3";
// 	   			break;
//    		case 'PO':
//    				$query = "PO01";
//    			break;
//    		case 'DOR':
//    				$query = "DOR";
//    			break;
//            case 'DO':
//    				$query = "DO01";
//    			break;
//            case 'iCAN':
//    				$query = "I-Can01";
//    			break;
//            case 'iLOV':
//    				$query = "I-Lov01";
//    			break;
//            case 'iSUP':
//    				$query = "I-Sup01";
//    			break;
//    		case 'BOQ2':
//    			$query = "BOQ2";
//        		break;
//    		case 'ABOQ2':
//    			$query = "ABOQ2";
//                break;
//            case 'ASF':
//    			$query = "ASF01";
//                break;
//            case 'PBOQ3':
//    			$query = "PBQ110";
//                break;
//            case 'CBOQ3':
//    			$query = "CBOQ3";
//                break;
//            case 'AFE':
//    			$query = "AFE";
//                break;
//            case 'AFEN':
//    			$query = "AFEN";
//                break;
//            case 'CBOQ3N':
//    			$query = "CBOQ3N";
//                break;
//            case 'CBOQ3N2':
//    			$query = "CBOQ3N2";
//                break;
//            case 'PC':
//    			$query = "PC01";
//                break;
//            case 'SER':
//    			$query = "SER01";
//                break;
//            case 'SEU':
//    			$query = "SEU01";
//                break;
//            case 'SOR':
//    			$query = "SOR01";
//                break;
//            case 'SOU':
//    			$query = "SOU01";
//                break;
//            case 'RPC':
//    			$query = "RPC01";
//                break;
//            case 'BER':
//    			$query = "BER";
//                break;
//            case 'BOR':
//    			$query = "BOR";
//                break;
//            case 'PER':
//    			$query = "PER01";
//                break;
//            case 'PEU':
//    			$query = "PEU01";
//                break;
//            case 'POU':
//    			$query = "POU01";
//                break;
//            case 'POR':
//    			$query = "POR01";
//                break;
//            case 'PRABOQ3':
//    			$query = "PRABOQ3";
//                break;
//            case 'REM':
//    			$query = "REM01";
//                break;
//            case 'BGO': //Budget Overhead
//    			$query = "BGO";
//                break;
//            case 'PRO':
//    			$query = "PR02";
//                break;
//            case 'POO':
//    			$query = "PO02";
//                break;
//            case 'ARFO':
//    			$query = "ARF02";
//                break;
//            case 'ASFO':
//    			$query = "ASF02";
//                break;
//            case 'RPIO':
//    			$query = "RPI02";
//                break;
//            case 'PRABOQ2':
//    			$query = "PRABOQ2";
//                break;
//            case 'TSHEET': //Timesheet
//    			$query = "TSHEET";
//                break;
//            case 'PRAOHP':
//    			$query = "PRAOHP01";
//                break;
//            case 'OHP':
//    			$query = "OHP01";
//                break;
//            case 'PRABGO': //Pra Budget Overhead
//    			$query = "PRABGO";
//                break;
//            case 'DN01':
//    			$query = "DN01";
//                break;
//            case 'RINV': //Request Invoice
//    			$query = "RINV";
//                break;
//            case 'INV': //Invoice
//    			$query = "INV";
//                break;
//            case 'BPV': //Bank Payment Voucher
//    			$query = "BPV";
//                break;
//            case 'PRACO': //Pra CO
//    			$query = "PRACO";
//                break;
//            case 'REGCO': //Register CO
//    			$query = "REGCO";
//                break;
//            case 'APRACO': //Additional Pra CO
//    			$query = "APRACO";
//                break;
//            case 'RBE':
//    			$query = "RBE";
//                break;
//            case 'RBO':
//    			$query = "RBO";
//                break;
//            case 'RPE':
//    			$query = "RPE";
//                break;
//            case 'RPU':
//    			$query = "RPU";
//                break;
//            case 'RPO':
//    			$query = "RPO";
//                break;
//            case 'RPD':
//    			$query = "RPD";
//                break;
//            case 'REU':
//    			$query = "REU";
//                break;
//            case 'ROU':
//    			$query = "ROU";
//                break;
//            case 'RER':
//    			$query = "RER";
//                break;
//            case 'ROR':
//    			$query = "ROR";
//                break;
//            case 'BRM':
//    			$query = "BRM";
//                break;
//            case 'BSM':
//    			$query = "BSM";
//                break;
//            case 'PRM':
//    			$query = "PRM";
//                break;
//            case 'PSM':
//    			$query = "PSM";
//                break;
//            case 'ADJ': //Adjusting Journal
//    			$query = "ADJ";
//                break;
//            case 'CIP': //CIP Journal
//    			$query = "CIP";
//                break;
//            case 'NWS': //News
//    			$query = "NWS";
//                break;
//            case 'REP':
//    			$query = "REP";
//                break;
//            case 'CNP': //Construction Project Journal
//                $query = 'CNP';
//                break;
//    	}

        $query = $this->transType[$type];

        return $query;
    }

    public function getLastTrans($type='')
    {
        $query = $this->getTransType($type);
    	$thn = date('Y');
    	$sql = "SELECT * FROM master_countertransaksi WHERE tra_no='$query' AND tahun = '$thn' ORDER BY tra_no LIMIT 1";
    	$fetch = $this->db->query($sql);
    	$last = $fetch->fetch();

        if (!$last)
        {
            $urutTahun = substr($thn,-2);
            $urut = $urutTahun . "000000";
            $thn = date('Y');
            $sql = "INSERT INTO " . $this->_name . " (tra_no,urut,bulan,tahun) VALUES ('$query',$urut,'1','$thn')";
            $lastID = $this->db->query($sql);

            $last['tra_no'] = $query;
            $last['id'] = $lastID;
            $last['urut'] = $urut;
            $last['bulan'] = 1;
            $last['tahun'] = $thn;
        }

    	return $last;
    }
    

    public function setNewTrans($type='')
    {
        $tranoPrefix = $this->getTransType($type);
    	$thn = date('Y');

        if (!$this->cekFinanceTrans($tranoPrefix))
            $sql = "select generate_trano_sequence('$tranoPrefix','$thn') AS trano";
        else
        {
            $bln = date('n');
            $sql = "select generate_finance_trano_sequence('$tranoPrefix','$thn','$bln') AS trano";
        }
        $fetch = $this->db->query($sql);
        $result = $fetch->fetch();

        return $result['trano'];
    }

    public function getTransTypeFlip($trano)
    {
        if (!$trano)
            return false;

        $tmp = explode('-',$trano);
        $type = array_flip($this->transType);

        return $type[$tmp[0]];
    }

    public function cekFinanceTrans($tranoPrefix)
    {
        $cek = $this->cekFinanceTransExist($tranoPrefix);
        if ($cek['statusfinance'] == 1)
            return true;

        return false;

    }

    private function cekFinanceTransExist($tranoPrefix)
    {
        $thn = date('Y');
        $bln = date('n');

        $cek = $this->fetchRow("tra_no = '$tranoPrefix' AND bulan = '$bln' AND tahun = '$thn'");
        if (!$cek)
        {
            $select = $this->db->select()
                ->from(array($this->_name),array("statuspayment","statusfinance","name"))
                ->where("tra_no = '$tranoPrefix'")
                ->order(array("id DESC"))
                ->limit(1,0);

            $lastInfo = $this->db->fetchRow($select);

            $arrayInsert = array(
                "tra_no" => $tranoPrefix,
                "bulan" => $bln,
                "tahun" => $thn,
                "urut" => 0,
                "statuspayment" => $lastInfo['statuspayment'],
                "statusfinance" => $lastInfo['statusfinance'],
                "name" => $lastInfo['name']
            );

            $this->insert($arrayInsert);

            return $arrayInsert;
        }
        else
            return $cek;
    }
}
?>