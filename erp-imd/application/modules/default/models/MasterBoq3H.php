<?php

   class Default_Models_MasterBoq3H extends Zend_Db_Table_Abstract
   {
   protected $_name = 'transengineer_boq3h';

   protected $_primary = 'trano';


   public function getPrimaryKey()
   {
       return $this->_primary;
   }
   }

?>
