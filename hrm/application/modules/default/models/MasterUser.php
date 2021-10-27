<?php
class Default_Models_MasterUser extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_login';
    private $db;
    protected $_primary = 'id';

    function  __construct() {
        $this->db = Zend_Registry::get('db');
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function getUserInfo($uid='')
    {
        $sql = "SELECT
                    id,
                    uid,
                    npk,
                    id_privilege as is_admin,
                    name
                FROM master_login
                WHERE uid = '$uid'";

        $fetch = $this->db->query($sql);
        if ($fetch)
        {
            return $fetch->fetch();
        }
    }

    public function all($where='',$sort='', $dir='', $limit='', $offset='')
    {
        $memcache = Zend_Registry::get('Memcache');
        $idHash = md5($where.$sort.$dir.$limit.$offset);
        $cacheID = "MASTER_USER_$idHash";

        if (!QDC_Adapter_Memcache::factory()->test($cacheID))
        {
//            $return['posts'] = $this->fetchAll($where, array($sort . ' ' . $dir), $limit, $offset)->toArray();
//            $return['count'] = $this->fetchAll()->count();
            if ($where)
            {
                $where = " WHERE $where";
            }

            if ($sort && $dir)
            {
                $sorter = "ORDER BY COALESCE($sort,'z') $dir";
            }

            if ($offset !='' && $limit != '')
            {
                $limiter = "LIMIT $offset,$limit";
            }
            $sql = "SELECT
                        SQL_CALC_FOUND_ROWS
                        id,
                        uid,
                        npk,
                        id_privilege as is_admin
                    FROM master_login
                    $where
                    $sorter
                    $limiter";

            $fetch = $this->db->query($sql);
            $hasil = $fetch->fetchAll();
            $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

            $ldapdir = new Default_Models_Ldap();
            $indeks=0;
            foreach ($hasil as $k => $v)
            {
                $account = $ldapdir->getAccount($v['uid']);
                if ($account['displayname'][0] != '')
                {
                    $return['posts'][$indeks]['name'] = $account['displayname'][0];
                    $return['posts'][$indeks]['uid'] = $v['uid'];
                    $return['posts'][$indeks]['id'] = $v['id'];
                    $return['posts'][$indeks]['npk'] = $v['npk'];
                    $indeks++;
                }
                else
                {
                    $return['posts'][$indeks]['name'] = $v['uid'];
                    $return['posts'][$indeks]['uid'] = $v['uid'];
                    $return['posts'][$indeks]['id'] = $v['id'];
                    $return['posts'][$indeks]['npk'] = $v['npk'];
                    $indeks++;
                }

            }
//            $memcache->save($return,$cacheID,array('TRANSACTION'));
            QDC_Adapter_Memcache::factory(array(
                "cacheID" => $cacheID,
                "data" => $return
            ))->save();
        }
        else
        {
//            $return = $memcache->load($cacheID);
            $return = QDC_Adapter_Memcache::factory(array(
                    "cacheID" => $cacheID
                ))->load();
        }

        return $return;
    }
}
?>