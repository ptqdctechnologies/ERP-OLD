<?php
class Admin_Models_WorkflowGenericOverride extends Zend_Db_Table_Abstract
{
    protected $_name ='workflow_generic_override';
    protected $db;

	public function __construct()
	{
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');;
    }

    public function __name()
    {
        return $this->_name;
    }

    public function getOverride($itemType='',$workflowItem='',$prjKode='',$priority=1)
    {
        $select = $this->db->select()
            ->from(array($this->_name))
            ->order(array("priority ASC"))
            ->where("item_type = '$itemType'");
//        if ($workflowItem)
//            $select = $select->where("workflow_item_id = $workflowItem");
//        if ($itemType)
//
//        if ($prjKode)
//            $select = $select->where("prj_kode = '$prjKode'");


        $data = $this->db->fetchRow($select);

        if ($data)
        {
            $roleBased = ($data['role_based'] == 1) ? true : false;
            $data = Zend_Json::decode($data['uid']);
        }
        else
            $data = array();


        return array("data" => $data, "role_based" => $roleBased);
    }
}
?>