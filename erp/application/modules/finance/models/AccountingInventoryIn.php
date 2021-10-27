<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AccountingInventoryIn extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_inventory_in';

    protected $db;
    protected $const;
    protected $jurnalinventory;
//    private $creditInventoryOut = '1-3001';
//    private $debitInventoryOut = '5-1070';

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
        $this->jurnalinventory = new Finance_Models_MasterJurnalInventory();
    }

    public function __name(){
        return $this->_name;
    }

    public function insertJurnal($params=array())
    {
        if ($params != '')
            foreach($params as $k => $v)
            {
                $temp = $k;
                ${"$temp"} = $v;
            }

        $m = new Default_Models_MasterCounter();
        $trano  = $m->setNewTrans("IJ");
        $rateidr = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
        $rateidr = $rateidr['rateidr'];
        $arrayInsert = array(
            "trano" => $trano,
            "ref_number" => $ref_number,
            "prj_kode" => $prj_kode,
            "sit_kode" => $sit_kode,
            "tgl" => date("Y-m-d H:i:s"),
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "val_kode" => $val_kode,
            "rateidr" => $rateidr
        );

//        if ($val_kode != 'IDR') {
//            $total = $total * $rateidr;
//        }
        
//        var_dump($gdg_kode_from,$gdg_kode_to);die;
        //COA
        $coa_jurnal = $this->getCoaJurnalInventory($gdg_kode_from, $gdg_kode_to);
        
        //Debit

        $coadetail = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa_jurnal['coa_debit']));
        $arrayInsert['debit'] = $total;
        $arrayInsert['credit'] = 0;
        $arrayInsert['coa_kode'] = $coadetail['coa_kode'];
        $arrayInsert['coa_nama'] = $coadetail['coa_nama'];

        $this->insert($arrayInsert);

        //Credit

        $coadetail = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa_jurnal['coa_credit']));
        $arrayInsert['credit'] = $total;
        $arrayInsert['debit'] = 0;
        $arrayInsert['coa_kode'] = $coadetail['coa_kode'];
        $arrayInsert['coa_nama'] = $coadetail['coa_nama'];

        $this->insert($arrayInsert);
    }

    private function getCoaJurnalInventory($gdg_from, $gdg_to) {

        $coa = $this->jurnalinventory->fetchRow("gdg_kode_from = '$gdg_from' AND gdg_kode_to ='$gdg_to'");
        return $coa;
    }

}