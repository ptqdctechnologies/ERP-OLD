<?php

   class Default_Models_MasterCboq3 extends Zend_Db_Table_Abstract
   {
   protected $_name = 'transengineer_kboq3d';

   protected $_primary = 'trano';


   public function getPrimaryKey()
   {
       return $this->_primary;
   }
   }

?>
