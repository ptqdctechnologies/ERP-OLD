<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 3/6/12
 * Time: 3:08 PM
 * To change this template use File | Settings | File Templates.
 */
class Sales_Models_TemporaryTransferBudget extends Zend_Db_Table_Abstract {

    protected $_name = 'temp_transfer_budget';
    protected $_primary = 'trano';
    protected $db;

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
    }

    public function __name() {
        return $this->_name;
    }

    public function transferBudgetFinal($trano) {

        $dataTemporary = $this->fetchRow("trano = '$trano'")->toArray();

        /* store procedure params */
        $projectTo = $dataTemporary['prj_kode'];
        $siteTo = $dataTemporary['sit_kode'];
        $workidTo = $dataTemporary['workid'];
        $kodebrgTo = $dataTemporary['kode_brg'];
        $arfno = $dataTemporary['ref_number'];
        $trano_type = $dataTemporary['trano_type'];
        /* store procedure params */

        /* Call store procedure */
        $query = $this->db->prepare("call transfer_budget_arf"
                . "(\"$projectTo\","
                . "\"$siteTo\","
                . "\"$workidTo\","
                . "\"$kodebrgTo\","
                . "\"$arfno\","
                . "\"$trano_type\""
                . ")");
        if ($query->execute())
            return true;
        else
            return false;

        /* Call store procedure */
    }

}
