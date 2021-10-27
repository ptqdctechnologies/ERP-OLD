<?php

class Default_Models_MasterBoq3 extends Zend_Db_Table_Abstract
{
   protected $_name = 'transengineer_boq3d';

   protected $_primary = 'trano';
  

   public function getPrimaryKey()
   {
       return $this->_primary;
   }

}

?>
