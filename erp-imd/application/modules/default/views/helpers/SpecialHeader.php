<?php

class Zend_View_Helper_SpecialHeader extends Zend_View_Helper_Abstract {



    public function specialHeader($menu = '')
    {
        switch($menu)
        {
            case 'procurement':
                $menuArray = array("windowForm");
            break;
            case 'budgetbyperproject':
                $menuArray = array("windowForm");
            break;
            case 'procurement-chart':
                $menuArray = array("windowForm2");
            break;
            default:
                //$menuArray = array("start", "absolute", "accordion", "anchor", "border", "cardTabs", "cardWizard", "column", "fit", "form", "table", "vbox", "hbox","rowLayout", "centerLayout","absoluteForm", "tabsNestedLayouts");
                $menuArray = array("start");
                break;
        }
         $cekserver = Zend_Registry::get('servertest');

         if ($cekserver == 1)
         {
         $file = APPLICATION_PATH ."/revision.txt";
         $fh = fopen($file, 'r') or die("can't open file");
         $isi = fread($fh, filesize($file));
         fclose($fh);
         

         $isi = str_replace("\n", "", $isi);
         $isi = str_replace("\r", "", $isi);
         $isi = str_replace("\t", "", $isi);
         $isi = str_replace("Exported revision","Server Test Rev :", $isi);
        $this->view->read = $isi;
         }
         
        $this->view->menuArray = $menuArray;
        $this->session = new Zend_Session_Namespace('login');
        $this->view->role =  $this->session->role;
        $this->view->privilege =  $this->session->privilege;
		$this->view->userID = $this->session->idUser;
        $this->view->myId = $this->session->userName;
		$userrole = new Admin_Models_Userrole();
		$userMenu = $userrole->getMenuByUserID($this->session->idUser);
		if (count($userMenu) > 0)
			$this->view->managed = true;

        //untuk cek dokumen Personal Assistant
        $pa = new Admin_Model_PersonalAssistant();
        $myUid = $this->session->userName;
        $fetchPa = $pa->fetchAll("uid = '$myUid'")->toArray();
        if ($fetchPa)
            $this->view->isPA = true;
        else
            $this->view->isPA = false;

        //Untuk submenu
//        $submenu = New Default_Models_SubMenu();
        $userMenu = $userrole->getSubMenuByUserID($this->session->idUser);
        $arraySubmenu = array();
        foreach($userMenu as $k => $v)
        {
            $arraySubmenu[] = $v['submenu_id'];
        }

        $this->view->notAllowedSubmenu = implode(",",$arraySubmenu);

        return $this->view->render("specheader.phtml");
    }
}


?>
