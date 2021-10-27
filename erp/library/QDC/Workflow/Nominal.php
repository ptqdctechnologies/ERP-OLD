<?php

Class QDC_Workflow_Nominal
{
    private $ADMIN;
    private $const;
    private $db;

    protected $approvalStatus;
    protected $approvalUid;

    public function __construct($params='')
    {
        if ($params != '')
        {
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }
        $models = array(
            "Workflowtrans",
            "MasterBatasNilaiTransaksi"
        );
        $this->ADMIN = QDC_Model_Admin::init($models);
        $this->const = Zend_Registry::get('constant');
        $this->db = Zend_Registry::get('db');
    }

    public static function factory($params=array())
    {
        return new self($params);
    }

    public function checkLimit()
    {
        $limit = null;
        $select = $this->db->select()
            ->from(array($this->ADMIN->MasterBatasNilaiTransaksi->__name()))
            ->where("uid=?",$this->uid)
            ->where("item_type=?",$this->item_type);

        if ($this->prj_kode)
        {
            //Check first if any project exist...
            $select2 = $this->db->select()
                ->from(array($this->ADMIN->MasterBatasNilaiTransaksi->__name()))
                ->where("uid=?",$this->uid)
                ->where("item_type=?",$this->item_type)
                ->where("prj_kode='' OR prj_kode IS NULL");
            $cek = $this->db->fetchRow($select2);
            if ($cek)
            {
                $limit =  $cek['total_limit'];
            }
            else
                $select = $select->where("prj_kode=?",$this->prj_kode);
        }

        $data = $this->db->fetchRow($select);
        if ($data)
        {
            if ($limit == null)
                $limit = $data['total_limit'];

            return $limit;
        }

        return false;

    }

}
?>