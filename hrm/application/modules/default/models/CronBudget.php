<?php

class Default_Models_CronBudget extends Zend_Db_Table_Abstract
{
    protected $_name = 'cron_budget';
    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
    }

    public function save($data,$prjKode,$sitKode)
    {
        $key = "prj_kode = '$prjKode' AND sit_kode = '$sitKode'";
        $cek = $this->fetchRow($key);
        if (is_array($data))
        {
            $data = Zend_Json::encode($data);
        }
        if ($cek)
        {
            $update = array(
                "data" => $data,
                "tgl" => date("Y-m-d H:i:s")
            );
            $this->update($update,$key);
        }
        else
        {
            $insert = array(
                "data" => $data,
                "prj_kode" => $prjKode,
                "sit_kode" => $sitKode,
                "tgl" => date("Y-m-d H:i:s")
            );
            $this->insert($insert);
        }
    }

    public function load($prjKode,$sitKode)
    {
        $key = "prj_kode = '$prjKode' AND sit_kode = '$sitKode'";
        $cek = $this->fetchRow($key);
        if ($cek)
        {
            $cek = $cek->toArray();
            $cek = $cek['data'];
            if ($cek != '')
            {
                $tmp = Zend_Json::decode($cek);
                if ($tmp != '')
                    $cek = $tmp;
            }
            return $cek;
        }
        else
        {
            return false;
        }
    }

    public function test($prjKode,$sitKode)
    {
        $key = "prj_kode = '$prjKode' AND sit_kode = '$sitKode'";
        $cek = $this->fetchRow($key);
        if ($cek)
            return true;
        else
            return false;
    }

    public function purgeAll()
    {
        $sql = "DELETE FROM {$this->_name}";
        $this->db->query($sql);
    }

    public function getProjectList($prjKode='',$limit=100,$offset=0)
    {
        $select = $this->db->select()
                ->from(array('cr' => $this->_name),
                    array(
                        'prj_kode AS Prj_Kode'
                        ))
                ->joinLeft(array('prj' => 'master_project'),
                    'cr.prj_kode = prj.Prj_Kode',
                         array(
                             'prj.Prj_Nama'
                             ))
                ->group('cr.prj_kode')
                ->limit($limit,$offset)
                ->order('cr.prj_kode DESC');

        if($prjKode){
            $select = $select
                    ->where('cr.prj_kode LIKE ?',"%$prjKode%");
        }

        $result = $this->db->fetchAll($select);

        return $result;
    }
}