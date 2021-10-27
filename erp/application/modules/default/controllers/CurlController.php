<?php
class CurlController extends Zend_Controller_Action
{
    private $adapter;
    private $client;
    public function init()
    {
        $this->adapter = new Zend_Http_Client_Adapter_Curl();
        $this->client = new Zend_Http_Client();
    }

    public function getattendanceAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $email = $this->getRequest()->getParam("email");
//        $callback = $this->getRequest()->getParam("callback");

        $this->client->setHeaders('Host', 'qjktdoor.qdc.co.id');
        $this->client->setUri('http://qjktdoor.qdc.co.id/attendance.php');
        $this->client->setParameterPost(array(
//            "callback" => $callback,
            "email" => $email
        ));
        $response = $this->client->request('POST');

        echo $response->getBody();
    }
}
?>