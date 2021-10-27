<?php

/*Function Name: Grid Controller
 * Kegunaan :
 * - Pembentuk header column di GridPanel
 * - Mapping field GridPanel dengan data JSON
 * - Set URL JSON untuk store GridPanel
 */


class TextfieldController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $this->initView();
    }
    
    //Use indexAction first before call this
    public function addcustom()
    {
        $request = $this->getRequest();

        $posXlabel = $request->getParam('posXlabel');
        $this->view->posXlabel = $posXlabel;
        $posYlabel = $request->getParam('posYlabel');
        $this->view->posYlabel = $posYlabel;
        $caption = $request->getParam('caption');
        $this->view->caption = $caption;
        $posXTxt = $request->getParam('posXTxt');
        $this->view->posXTxt = $posXTxt;
        $posYTxt = $request->getParam('posYTxt');
        $this->view->posYTxt = $posYTxt;
    }

}

?>
