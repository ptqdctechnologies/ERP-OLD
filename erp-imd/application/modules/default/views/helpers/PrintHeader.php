<?php

class Zend_View_Helper_PrintHeader extends Zend_View_Helper_Abstract {

    public function printHeader($menu = '')
    {
        
        return $this->view->render("header.phtml");
    }

    public function printSpecialHeader($menu = '')
    {
        switch($menu)
        {
            case 'procurement':
                $menuArray = array("windowForm");
            break;
            case 'budgetbyperproject':
                $menuArray = array("windowForm");
            break;
            default:
                //$menuArray = array("start", "absolute", "accordion", "anchor", "border", "cardTabs", "cardWizard", "column", "fit", "form", "table", "vbox", "hbox","rowLayout", "centerLayout","absoluteForm", "tabsNestedLayouts");
                $menuArray = array("start");
                break;
        }
        $this->view->menuArray = $menuArray;
    }
}


?>
