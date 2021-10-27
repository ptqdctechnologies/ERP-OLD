<?php

class Mc_ProjectController extends Zend_Controller_Action
{
    private $ADMIN, $uid, $DEFAULT;

    public function init()
    {
        /* Initialize action controller here */
        $this->uid = QDC_User_Session::factory()->getCurrentUID();
        $model = array(
            "Userrole"
        );
        $this->ADMIN = QDC_Model_Admin::init($model);
        $model = array(
            "MasterRoleSite"
        );
        $this->DEFAULT = QDC_Model_Default::init($model);
    }

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $hashOnly = $this->_getParam("hash");
//        $return['data'] = $this->ADMIN->Userrole->listMyProject($this->uid);
        $return['data'] = $this->DEFAULT->MasterRoleSite->getAllProject($this->uid);

        $tmp = array();
        foreach($return['data'] as $k => $v)
        {
            $tmp[] = $v['prj_kode'];
        }

        $hash = md5(implode(",",$tmp));
        $return['hash'] = $hash;

        if ($hashOnly)
            unset($return['data']);

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function cocotAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $url = 'https://android.googleapis.com/gcm/send';
        $serverApiKey = "AIzaSyDoJFq4Xex6TvukkmkUut9hmAflL9g-GmM";
//        $reg = "APA91bFHrVf7GLZOJOndBkH_boZC7v9sgm_wuBXcSzg4JRlgOi-ZMKGYmfQp__wityEg8_La8yZhdHqQbx1UCJxHsHbDshpPuwyJJh2xZxB8B0vE4g7_r3TqUu3RnPdpPc9Bkcj0LJrP";
        $reg = "APA91bFdUyYBsQHR3paO9_zaT8WIQDOZk3qHiN17itKGh6oe0GnGgSlb6bSbR2gcAveXT59GpDm1JJkJ9QOTZhS98BSaeyfhE1JhGR0rDWKRzxl8ExdbA-pQP0KaYPOrlTqI25GS0kV_";

        $data = array(
            'registration_ids' => array($reg),
            'data' => array(
                "message" => "halo",
                "msgcnt" => "asdf")
        );


//        $data = array(
//            "event" => "message",
//        );

        print(json_encode($data));

        $client = new Zend_Http_Client($url);
        $client->setMethod('POST');
        $client->setHeaders(array("Content-Type" => "application/json", "Authorization" => "key=" . $serverApiKey));
        $client->setRawData(json_encode($data));
        $request = $client->request('POST');
        $body = $request->getBody();
        $headers = $request->getHeaders();
        print("<xmp>");
        var_dump($body);
        var_dump($headers);
    }

}