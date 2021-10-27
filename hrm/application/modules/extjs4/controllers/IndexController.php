<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pushm0v
 * Date: 10/6/11
 * Time: 2:33 PM
 * To change this template use File | Settings | File Templates.
 */

class Extjs4_IndexController extends Zend_Controller_Action
{

    private $userName;
    private $session;

    public function init()
    {
        /* Initialize action controller here */

    }

    public function indexAction()
    {
        // action body

    }

    public function createAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        var_dump($this->getRequest()->getParams());
    }
}
?>