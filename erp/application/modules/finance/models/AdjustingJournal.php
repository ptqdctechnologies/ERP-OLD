<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/21/11
 * Time: 9:17 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AdjustingJournal extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_journal';

    protected $db;
    protected $const;

    private $jenisJurnal = array(
        'ADJ' => 'Adjusting Journal',
        'JV' => 'Voucher Journal',
        'SJ' => 'Sales Journal',
        'JS' => 'Settlement Journal',
        'ACJ' => 'Accrual Journal'
    );

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

    public function getJurnalType($type='')
    {
        return ($this->jenisJurnal[$type] == '') ? 'Journal' : $this->jenisJurnal[$type];
    }
}