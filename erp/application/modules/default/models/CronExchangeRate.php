 <?php

class Default_Models_CronExchangeRate extends Zend_Db_Table_Abstract
{
     protected $_name = 'exchange_rate';

	protected $db;

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }
    
     public function __name()
    {
        return $this->_name;
    }
}
?>

