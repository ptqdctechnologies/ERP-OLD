<?php

class Mc_UserController extends Zend_Controller_Action
{

    private $ADMIN, $DEFAULT;
    public function init()
    {
        /* Initialize action controller here */

        $model = array(
            "Masterrole"
        );
        $this->ADMIN = QDC_Model_Admin::init($model);

        $model = array(
            "MasterUser"
        );
        $this->DEFAULT = QDC_Model_Default::init($model);
    }

    public function getUserAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $hashOnly = $this->_getParam("hash");
        $cacheID = "MC_USER";

        if (QDC_Adapter_Memcache::factory(array("cacheID" => $cacheID))->test($cacheID))
        {
            $data = QDC_Adapter_Memcache::factory(array("cacheID" => $cacheID))->load();
            $hash = $data['hash'];
            $data = $data['data'];
        }
        else
        {
            $result = $this->DEFAULT->MasterUser->fetchAll("uid IS NOT NULL OR uid != ''", array("uid ASC"));
            if ($result)
                $result = $result->toArray();

            $tmp = array();
            $data = array();
            $i = 0;
            foreach($result as $k => $v)
            {
                if (!$hashOnly)
                {
                    $name = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                    if ($name == '')
                        continue;
                    $data[$i]['username'] = $name;
                    $roles = $this->ADMIN->Masterrole->getRoleFromUID($v['uid'],'mrt.display_name');
                    if ($roles)
                    {
                        $tmpRole = array();
                        foreach($roles as $k2 => $v2)
                        {
                            $tmpRole[] = $v2['display_name'];
                        }
                        $roles = implode(", ",$tmpRole);
                    }
                    else
                        $roles = '';
                    $data[$i]['role'] = $roles;
                    $data[$i]['uid'] = $v['uid'];
                }
                $tmp[] = $result[$k]['id'];
                $i++;
            }

            $hash = md5(implode(",",$tmp));

            if (!$hashOnly)
                QDC_Adapter_Memcache::factory(array("cacheID" => $cacheID, "data" => array("data" => $data,"hash" => $hash)))->save();
        }

        $return = array('success' => true,'data' => $data, 'hash' => $hash);
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}
?>