<?php

class Default_Models_TempActivity extends Zend_Db_Table_Abstract
{
    protected $_name = 'temp_activity';

    protected $db;
    protected $const;
    private $workflowTrans;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
    }

    public function getActivity($prjKode='',$sitKode='',$force=false)
    {

        $today = new DateTime(date("Y-m-d H:i:s"));
        $expire = new DateTime(date("Y-m-d H:i:s"));
        $expire->add(new DateInterval("PT4H"));
        $create = $today->format("Y-m-d H:i:s");
        $update = $today->format("Y-m-d H:i:s");
        $expire = $expire->format("Y-m-d H:i:s");

        Zend_Loader::loadClass('Zend_Json');
        if ($sitKode != '')
            $where = " AND sit_kode = '$sitKode'";
        $sql = "SELECT * FROM temp_activity WHERE prj_kode = '$prjKode' $where;";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetch();
        $exist = false;
        if ($fetch)
        {
            $id = $fetch['id'];
            $updating = intval($fetch['is_updating']);
            $expiretime = new DateTime($fetch['expire_time']);
            if (($updating == 0 || $force) && ($expiretime < $today))
            {
                try
                {
                    $this->db->beginTransaction();
                    $this->update(array("is_updating" => 1),"id = $id");
                    $this->db->commit();
                }
                 catch(Exception $e)
                {
                    $error_result = Array();
                    $message = $e->getMessage();
                    $code = $e->getCode();
                    $error_result[0] = $message;
                    $error_result[1] = $code;

                    var_dump($error_result);die;
                }
                $exist = true;
            }
            else
            {
                $fetch['data'] = Zend_Json::decode($fetch['data']);
                return $fetch;
            }
        }
        //BEGIN TRANSACTION ON DATABASE CONNECTION
        try
        {
            $sql = "
            CREATE TEMPORARY TABLE temp_submit
            SELECT * FROM (
                SELECT * FROM
                    workflow_trans
                WHERE prj_kode = '$prjKode'
                AND approve IN( 100, 150)
                ORDER BY date asc
            ) a GROUP BY a.item_id,a.uid";
            $this->db->query($sql);

            $sql = "
                SELECT uid,COUNT(item_type) as jumlah, item_type FROM
                    temp_submit
                WHERE prj_kode = '$prjKode'
                GROUP BY item_type,uid";
            $fetch = $this->db->query($sql);
            $fetch = $fetch->fetchAll();

            $ldapdir = new Default_Models_Ldap();
            $hasil = array();
            foreach($fetch as $key => $val)
            {
                $account = $ldapdir->getAccount($val['uid']);
                $name = $account['displayname'][0];
                $hasil[$val['item_type']][] = array (
                    "uid" => $val['uid'],
                    "name" => $name,
                    "jumlah" => $val['jumlah']
                );
            }
            $result['totalactivity'] = $hasil;

            $sql = "
                SELECT uid, item_type FROM
                    temp_submit
                WHERE prj_kode = '$prjKode'
                GROUP BY item_type,uid";
            $fetch = $this->db->query($sql);
            $fetch = $fetch->fetchAll();
            $hasil = array();
            foreach($fetch as $key => $val)
            {
                $uid = $val['uid'];
                $type = $val['item_type'];
                $sql = "
                    SELECT * FROM
                        temp_submit
                    WHERE prj_kode = '$prjKode' AND uid = '$uid' AND item_type = '$type' ORDER BY date ASC";
                $fetch2 = $this->db->query($sql);
                $fetch2 = $fetch2->fetchAll();

                foreach($fetch2 as $key2 => $val2)
                {
                    $hasil[$uid][$type][] = array (
                        "trano" => $val2['item_id'],
                        "tgl" => $val2['date'],
                        "final" => $val2['final']
                    );
                }
            }
            $result['detailactivity'] = $hasil;

            $json = Zend_Json::encode($result);

            if ($exist)
            {
                $this->db->beginTransaction();
                $arrayInsert = array(
                    "data" => $json,
                    "update_time" => $update,
                    "expire_time" => $expire,
                    "is_updating" => 0
                );
                $this->update($arrayInsert,"id = $id");
                $this->db->commit();
            }
            else
            {
                $this->db->beginTransaction();
                //SET QUERY YOU WISH TO EXECUTE
                $arrayInsert = array(
                    "prj_kode" => $prjKode,
                    "sit_kode" => $sitKode,
                    "data" => $json,
                    "creation_time" => $create,
                    "update_time" => $update,
                    "expire_time" => $expire
                );
                $this->insert($arrayInsert);
                $query = $this->db->commit();
            }
            //COMMIT QUERY TO DATABASE
            $arrayInsert['data'] = $result;
            return $arrayInsert;
        }
        catch(Exception $e)
        {
            $this->db->rollBack();
            //ROLLBACK IF TRANSACTION FAILS

            $error_result = Array();
            $message = $e->getMessage();
            $code = $e->getCode();
            $error_result[0] = $message;
            $error_result[1] = $code;

            var_dump($e);die;
        }
    }

}
?>