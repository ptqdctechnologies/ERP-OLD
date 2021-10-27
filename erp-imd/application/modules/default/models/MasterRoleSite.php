<?php
class Default_Models_MasterRoleSite extends Zend_Db_Table_Abstract
{

    private $db;
    protected $_name = 'master_role_site';
    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
    }

    public function __name()
    {
        return $this->_name;
    }

    public function getAllProject($uid)
    {
        $project = new Default_Models_MasterProject();
        $select = $this->db->select()
            ->from(array($this->_name),array(
                "prj_kode",
                "prj_nama"
            ))
            ->where($this->db->quoteInto("uid_pic = ?",$uid))
            ->where("active = 1")
            ->group("prj_kode")
            ->order(array("prj_kode ASC"));

        $select2 = $this->db->select()
            ->from(array("a" => $select))
            ->joinLeft(array("b" => $project->__name()),
                "a.prj_kode = b.Prj_Kode",
                array(
                    "sts_close" => "stsclose"
                )
            );

        $data = $this->db->fetchAll($select2);

        return $data;
    }

    public function getAllSite($uid,$prjKode='')
    {
        $site = new Default_Models_MasterSite();
        $select = $this->db->select()
            ->from(array($this->_name),array(
            "prj_kode",
            "sit_kode",
            "sit_nama"
        ))
            ->where($this->db->quoteInto("uid_pic = ?",$uid))
            ->where($this->db->quoteInto("prj_kode = ?",$prjKode))
            ->where("active = 1")
            ->order(array("sit_kode ASC"));

        $select2 = $this->db->select()
            ->from(array("a" => $select))
            ->joinLeft(array("b" => $site->__name()),
            "a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode",
            array(
                "sts_close" => "stsclose"
            )
        );

        $data = $this->db->fetchAll($select2);

        return $data;
    }

    public function getAll($uidPic='', $offset=0, $limit=50,$query='')
    {
        $select = $this->db->select()
            ->from(array($this->_name),
                new Zend_Db_Expr('SQL_CALC_FOUND_ROWS *')
            )
            ->where($this->db->quoteInto("uid_pic = ?",$uidPic))
            ->order(array("prj_kode ASC","sit_kode ASC"))
            ->limit($limit,$offset);

        if ($query)
        {
            $select = $select->where($query);
        }

        $result['posts'] = $this->db->fetchAll($select);

        foreach($result['posts'] as $k => $v)
        {
            $result['posts'][$k]['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
        }

        $result['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        return $result;
    }

    public function cekExist($prjKode='',$sitKode='',$uid='')
    {
        $cek = $this->fetchRow("prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND uid_pic = '$uid'");
        if ($cek)
        {
            return true;
        }
        return false;
    }
}