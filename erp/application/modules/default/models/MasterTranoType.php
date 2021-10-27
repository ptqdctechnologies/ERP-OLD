<?php

 class Default_Models_MasterTranoType extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_trano_type';
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

     public function getAll()
     {
         $select = $this->select()
             ->from(array($this->_name),array(
                 "type",
                 "trano_prefix"
             ))
             ->order("type");

         $data = $this->db->fetchAll($select);
         $newData = array();
         foreach($data as $k => $v)
         {
             $a = $v['type'];
             $b = $v['trano_prefix'];
             $newData[$a] = $b;
         }

         return $newData;
     }

}
?>
