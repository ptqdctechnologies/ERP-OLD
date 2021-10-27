<?php

/*Function Name: Grid Controller
 * Kegunaan :
 * - Pembentuk header column di GridPanel
 * - Mapping field GridPanel dengan data JSON
 * - Set URL JSON untuk store GridPanel
 */


class WindowController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $this->initView();
    }
    public function popupAction()
    {
        $this->initView();
        $request = $this->getRequest();

        $windowName = $request->getParam('name');
        $this->view->windowName = $windowName;
    }

}

?>