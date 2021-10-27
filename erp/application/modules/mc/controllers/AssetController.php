<?php

class Mc_AssetController extends Zend_Controller_Action
{

    private $LOGISTIC, $DEFAULT, $asset_status;
    public function init()
    {
        /* Initialize action controller here */

        $model = array(
            "MasterFixedAsset",
            "FixedAssetStatus"
        );
        $this->LOGISTIC = QDC_Model_Logistic::init($model);

        $this->asset_status = array(
            array("name" => "IN USE"),
            array("name" => "STORAGE"),
            array("name" => "SERVICE"),
            array("name" => "CALIBRATE"),
        );
    }

    public function getAssetStatusAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $token = $this->getRequest()->getParam('asset_token');
        $hashOnly = $this->getRequest()->getParam('hash');

        $result = $this->LOGISTIC->FixedAssetStatus->getAssetStatus($token);

        $tmp = array();
        foreach($result as $k => $v)
        {
            if (!$hashOnly)
            {
                $result[$k]['username'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                if ($v['uid_pic'] != '')
                    $result[$k]['username_pic'] = QDC_User_Ldap::factory(array("uid" => $v['uid_pic']))->getName();
                else
                    $result[$k]['username_pic'] = '';
            }
            else
                unset($result[$k]);
            unset($result[$k]['id']);
            $tmp[] = $v['id'];
        }

        $hash = md5(implode(",",$tmp));

        $return = array('success' => true,'data' => $result, 'hash' => $hash);
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }


    public function addStatusAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $form = $this->getRequest()->getParams();

        $token = $form['asset_token'];
        if ($this->LOGISTIC->MasterFixedAsset->isExist($token))
        {
            $lastStatus = $this->LOGISTIC->FixedAssetStatus->getAssetStatus($token,1);
            if($lastStatus['status'] == $form['status_code'])
            {
                $return = array('success' => false,'msg' => 'Status terakhir dari Asset ini adalah : ' . $lastStatus['status'] . ', Harap pilih status yang lain.');
            }
            else
            {
                $arrayInsert = array(
                    "token" => $token,
                    "code" => $form['asset_code'],
                    "tgl" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "uid_pic" => $form['pic'],
                    "status" => $form['status_code'],
                    "condition" => $form['condition'],
                    "sup_kode" => $form['sup_kode'],
                    "ket" => $form['asset_ket']
                );

                $this->LOGISTIC->FixedAssetStatus->insert($arrayInsert);
                $return = array('success' => true);
            }
        }

        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}
?>