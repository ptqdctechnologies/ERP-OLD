<?php

class Default_Models_MasterCounter extends Zend_Db_Table_Abstract {

    protected $_name = 'master_countertransaksi';
    protected $_primary = 'id';
    protected $db;
    private $transType = array();

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');

        //Fetch from db
        $t = new Default_Models_MasterTranoType();
        $this->transType = $t->getAll();


//        $this->transType = array(
//            'PR'=>"PRF", //procurement request
//            'ARF'=>"ARF01", //ARF, Advanced Request Form
//            'RPI'=>"RPI01", //RPI, Request for Payment Invoice
//            'BOQ3'=>"BOQ3", //BOQ3, Budget Project
//            'PO'=>"PO01", //PO, Procurement Order
//            'DOR'=>"DOR", //DOR, Delivery Order Request
//            'DO'=>"DO01", //DO, Delivery Order
//            'iCAN'=>"I-Can01", //iCAN, Material Cancel (Wrong Spec Material)
//            'iLOV'=>"I-Lov01", //iLov, Material Left Over (Left Over from usage)
//            'iSUP'=>"I-Sup01", //iSupp, Material Input from Supplier to Warehouse
//            'BOQ2'=>"BOQ2", //BOQ2, Customer Order / CO
//            'ABOQ2'=>"ABOQ2", //ABOQ2, Additional Customer Order
//            'ASF'=>"ASF01", //ASF, Advanced Settlement Form
//            'PBOQ3'=>"PBQ110", //PBOQ3, Piece Meal
//            'CBOQ3'=>"CBOQ3", //CBOQ3, Correction of BOQ3, AFE final approved convert to BOQ3
//            'AFE'=>"AFE", //AFE, Approval of Expenditure, Revision for BOQ3
//            'AFEN'=>"AFEN",
//            'CBOQ3N'=>"CBOQ3N",
//            'CBOQ3N2'=>"CBOQ3N2",
//            'PC'=>"PC01", //Finance code transaction, payment, etc
//            'SER'=>"SER01", //Finance code transaction, payment, etc
//            'SEU'=>"SEU01", //Finance code transaction, payment, etc
//            'SOR'=>"SOR01", //Finance code transaction, payment, etc
//            'SOU'=>"SOU01", //Finance code transaction, payment, etc
//            'RPC'=>"RPC01", //Finance code transaction, payment, etc
//            'BER'=>"BER", //Finance code transaction, payment, etc
//            'BOR'=>"BOR", //Finance code transaction, payment, etc
//            'PER'=>"PER01", //Finance code transaction, payment, etc
//            'PEU'=>"PEU01", //Finance code transaction, payment, etc
//            'POU'=>"POU01", //Finance code transaction, payment, etc
//            'POR'=>"POR01", //Finance code transaction, payment, etc
//            'PRABOQ3'=>"PRABOQ3", //PRABOQ3, temporary BOQ3
//            'REM'=>"REM01", //REM, Reimbursable of Expenditure
//            'BGO'=>"BGO",//Budget Overhead (Non Project, HRDO, ITO, etc)
//            'PRO'=>"PR02", //PRO, PR of Overhead (Non Project, HRDO, ITO, etc)
//            'POO'=>"PO02", //PO, PO of Overhead (Non Project, HRDO, ITO, etc)
//            'ARFO'=>"ARF02", //ARFO, ARF of Overhead (Non Project, HRDO, ITO, etc)
//            'ASFO'=>"ASF02", //ASFO, ASF of Overhead (Non Project, HRDO, ITO, etc)
//            'RPIO'=>"RPI02",  //RPIO, RPI of Overhead (Non Project, HRDO, ITO, etc)
//            'PRABOQ2'=>"PRABOQ2",
//            'TSHEET'=>"TSHEET",//Timesheet
//            'PRAOHP'=>"PRAOHP01",
//            'OHP'=>"OHP01",
//            'PRABGO'=>"PRABGO",//Pra Budget Overhead
//            'DN01'=>"DN01",//DN, Debit Note
//            'RINV'=>"RINV",//RINV, Request Invoice
//            'INV'=>"INV",//INV, Invoice to Customer
//            'BPV'=>"BPV",//BPV, Bank Payment Voucher
//            'PRACO'=>"PRACO",//PRACO, Temporary CO
//            'REGCO'=>"REGCO",//REGCO, Register new Customer Order
//            'APRACO'=>"APRACO", //APRACO, Additional PRACO
//            'RBE'=>"RBE", //Finance code transaction, payment, etc
//            'RBO'=>"RBO", //Finance code transaction, payment, etc
//            'RPE'=>"RPE", //Finance code transaction, payment, etc
//            'RPU'=>"RPU", //Finance code transaction, payment, etc
//            'RPO'=>"RPO", //Finance code transaction, payment, etc
//            'RPD'=>"RPD", //Finance code transaction, payment, etc
//            'REU'=>"REU", //Finance code transaction, payment, etc
//            'ROU'=>"ROU", //Finance code transaction, payment, etc
//            'RER'=>"RER", //Finance code transaction, payment, etc
//            'ROR'=>"ROR", //Finance code transaction, payment, etc
//            'BRM'=>"BRM", //Finance code transaction, payment, etc
//            'BSM'=>"BSM", //Finance code transaction, payment, etc
//            'PRM'=>"PRM", //Finance code transaction, payment, etc
//            'PSM'=>"PSM", //Finance code transaction, payment, etc
//            'ADJ'=>"ADJ", //Adjustment Journal for Accounting
//            'CIP'=>"CIP",  //Finance code transaction, payment, etc
//            'NWS'=>"NWS", //Finance code transaction, payment, etc
//            'REP'=>"REP", //Finance code transaction, payment, etc
//            'CNP'=>'CNP', //Finance code transaction, payment, etc
//            'JS'=> 'JS', //settlement journal
//            'SJ'=> 'SJ', //sales journal
//            'JV'=> 'JV', //voucher journal
//            'CI'=> 'CI', //cancel invoice
//            'SAL' => 'SAL01', // salary
//            'FA' => 'FA', // fixed asset transaction
//            'FIX' => 'FIX', // depreciation fixed asset transaction
//            'PRPG' => 'PRPG', // project progress transaction
//            'SITE' => 'SITE', // update site code and name log transaction
//            'ARFP' => 'ARFP', //ARF Pulsa
//            'ASFP' => 'ASFP', //ASF Pulsa
//            'ACJ' => 'ACJ', //Accrual Journal
//            'BRF' => 'BRF', //Business Trip Request
//            'BRFP' => 'BRFP', //Business Trip Request Payment
//            'BSF' => 'BSF', //Business Trip Settlement
//            'BCA' => 'BCA', //Business Trip Settlement
//            'IJ' => 'IJ', //Inventory Incoming Journal (barang masuk ke gudang)
//            'OJ' => 'OJ', //Inventory Outcoming Journal (barang keluar dari gudang)
//            'COR'=>'COR', //Finance code transaction, payment, etc
//            'NOR'=>'NOR', //Finance code transaction, payment, etc
//        );
    }

    private function getTransType($type = '') {
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

    public function getLastTrans($type = '') {
        $query = $this->getTransType($type);
        $thn = date('Y');
        $sql = "SELECT * FROM master_countertransaksi WHERE tra_no='$query' AND tahun = '$thn' ORDER BY tra_no LIMIT 1";
        $fetch = $this->db->query($sql);
        $last = $fetch->fetch();

        if (!$last) {
            $urutTahun = substr($thn, -2);
            $urut = $urutTahun . "000000";
            $thn = date('Y');
            $sql = "INSERT INTO " . $this->_name . " (tra_no,urut,bulan,tahun) VALUES ('$query',$urut,'1','$thn')";
            $lastID = $this->db->query($sql);
            unset($last);
            $last['tra_no'] = $query;
            $last['id'] = $lastID;
            $last['urut'] = $urut;
            $last['bulan'] = 1;
            $last['tahun'] = $thn;
        }

        return $last;
    }

    public function setNewTrans($type = '') {
        $tranoPrefix = $this->getTransType($type);
        $thn = date('Y');

        $lastInfo = $this->getLastInfo($tranoPrefix);
        
        if ($lastInfo['statusfinance'] == 1) {
            $this->cekFinanceTrans($tranoPrefix);
            $bln = date('n');
            $sql = "select generate_finance_trano_sequence('$tranoPrefix','$thn','$bln') AS trano";
        } else if ($lastInfo['statusfinance'] == 2) {
            $this->cekFinanceTrans($tranoPrefix);
            $bln = date('n');
            $sql = "select generate_accountingAP_trano_sequence('$tranoPrefix','$thn','$bln') AS trano";
        } else if ($lastInfo['statusfinance'] == 3) {
            $this->cekFinanceTrans($tranoPrefix);
            $bln = date('n');
            $sql = "select generate_accountingRPC_trano_sequence('$tranoPrefix','$thn') AS trano";
        } else {  
            $this->cekTahun($tranoPrefix, $lastInfo);
            $sql = "select generate_trano_sequence('$tranoPrefix','$thn') AS trano";
        }
        $fetch = $this->db->query($sql);
        $result = $fetch->fetch();

        return $result['trano'];
    }

    public function getTransTypeFlip($trano) {
        if (!$trano)
            return false;

        $tmp = explode('-', $trano);
        $type = array_flip($this->transType);

        return $type[$tmp[0]];
    }

    public function cekFinanceTrans($tranoPrefix) {
        $cek = $this->cekFinanceTransExist($tranoPrefix);
        if ($cek['statusfinance'] == 1)
            return true;
        else
        if ($cek['statusfinance'] == 2)
            return true;

        return false;
    }

    private function cekFinanceTransExist($tranoPrefix) {
        $thn = date('Y');
        $bln = date('n');

        $cek = $this->fetchRow("tra_no = '$tranoPrefix' AND bulan = '$bln' AND tahun = '$thn'");
        if (!$cek) {
            $select = $this->db->select()
                    ->from(array($this->_name), array("statuspayment", "statusfinance", "name"))
                    ->where("tra_no = '$tranoPrefix'")
                    ->order(array("id DESC"))
                    ->limit(1, 0);

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
        } else
            return $cek;
    }

    public function cekTahun($tranoPrefix, $lastInfo, $urut = '') {
        $thn = date('Y');
        $bln = date('n');

        $cek = $this->fetchRow("tra_no = '$tranoPrefix' AND tahun = '$thn'");
        if (!$cek) {
            if ($urut == '')
                $urut = (date('y') . "000000");

            $arrayInsert = array(
                "tra_no" => $tranoPrefix,
                "bulan" => $bln,
                "tahun" => $thn,
                "urut" => $urut,
                "statuspayment" => $lastInfo['statuspayment'],
                "statusfinance" => $lastInfo['statusfinance'],
                "name" => $lastInfo['name']
            );

            $this->insert($arrayInsert);
        }
    }

    public function getLastInfo($tranoPrefix) {
        $select = $this->db->select()
                ->from(array($this->_name))
                ->where("tra_no = '$tranoPrefix'")
                ->order(array("id DESC"))
                ->limit(1, 0);

        $lastInfo = $this->db->fetchRow($select);

        return $lastInfo;
    }

    public function __name() {
        return $this->_name;
    }

}

?>