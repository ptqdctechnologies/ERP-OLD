<?php

class ProjectManagement_Models_TemporaryOverHeadBOQ3h extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_praboq3hnonproject';
    private $db;
     protected $_primary = 'trano';

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function transferTempOHBOQ3($trano)
    {
        $counter = new Default_Models_MasterCounter();

        $ohbudgeth = new ProjectManagement_Models_NonProjectBOQ3h();
        $ohbudgetd = new ProjectManagement_Models_NonProjectBOQ3();

        $sql = "select * from transengineer_praboq3dnonproject where trano = '$trano'";
        $fetch = $this->db->query ($sql);
        if ($fetch)
        {
            $hasil = $fetch->fetchAll();

            $lastTrans = $counter->getLastTrans('BGO');
            $lastTrans['urut'] = $lastTrans['urut'] + 1;
            $newtrano = 'BGO-' . $lastTrans['urut'];
            $counter->update(array("urut" => $lastTrans['urut']),"id=".$lastTrans['id']);
            foreach($hasil as $key => $val)
            {
                unset($hasil[$key]['id']);
                $hasil[$key]['trano'] = $newtrano;
                $ohbudgetd->insert($hasil[$key]);
            }
            $sql = "DELETE FROM transengineer_praboq3dnonproject WHERE trano = '$trano'";
            $fetch = $this->db->query($sql);

            $sql = "SELECT * FROM transengineer_praboq3hnonproject WHERE trano = '$trano'";
            $fetch = $this->db->query($sql);
            if ($fetch)
            {
                $hasil = $fetch->fetch();
                unset($hasil['id']);
                $hasil['trano'] = $newtrano;
                $hasil['user'] = $hasil['petugas'];
                unset($hasil['uid']);
                $ohbudgeth->insert($hasil);
                $sql = "DELETE FROM transengineer_praboq3hnonproject WHERE trano = '$trano'";
                $fetch = $this->db->query($sql);
            }

        }

    }

    public function __name()
    {
        return $this->_name;
    }
}
    


?>
