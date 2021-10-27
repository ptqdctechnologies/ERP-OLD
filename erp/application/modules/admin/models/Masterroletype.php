<?php
class Admin_Models_Masterroletype extends Zend_Db_Table_Abstract
{
	protected $_name ='master_role_type';
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

	public function getAll($where='')
	{
		$sql = "SELECT id AS id_role,display_name,role_name,active
				FROM
					master_role_type
				$where	
				ORDER BY role_name ASC";
		
		$return = $this->db->fetchAll($sql);
		
		return $return;
	}
	
	public function getNameByID($id_role='')
	{
		$sql = "SELECT id AS id_role,display_name,role_name,active
				FROM
					master_role_type
				WHERE id=$id_role";
		
		$fetch = $this->db->query($sql);
    	$return = $fetch->fetch();
		return $return;
	}

    public function getName($id_role='')
    {
        return $this->db->fetchOne($this->db->select()
            ->from(array($this->_name),array("display_name"))
            ->where("id=?",$id_role));
    }

    public function getPerson($id='',$prj_kode='')
    {
        $mr = new Admin_Models_Masterrole();
        $ml = new Admin_Models_Masterlogin();
        $select = $this->db->select()
            ->from(array($mr->__name()),array("id_user"))
            ->where("id_role=?",$id)
            ->where("prj_kode=?",$prj_kode);

        $select = $this->db->select()
            ->from(array("a" => $select))
            ->joinLeft(array("b" => $ml->__name()),"a.id_user = b.id",array("uid"));

        return $this->db->fetchAll($select);
    }
}
?>