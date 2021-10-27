<?php 
/* 
Created @ Mar 30, 2010 8:35:27 AM
 */

class MenuController extends Zend_Controller_Action
{
    private $db;
    private $session;
    
    private $stat;    
    private $hid;
    private $menu;
    private $submenu;
    private $userrole;
    private $workflow;
    private $site;

    private $stats = true;
    private $stats2 = false;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->menu = new Default_Models_Menu();
        $this->submenu = new Default_Models_SubMenu();
        $this->userrole = new Admin_Model_Userrole();
        $this->workflow = new Admin_Model_Workflow();
        $this->site = new Default_Models_MasterSite();
    }

	private function setChecked(&$item,$key,$menus)
    {
	    if ($key == 'id' && !is_string($menus[0]) && isset($menus[0]))
		{
			foreach ($menus[0] as $keys => $vals)
			{
				if ($vals['menu_id'] == $item)
				{
					$this->stats = false;
					break;
				}
			}
		}
		if ($key == 'checked' )
		{
			if ($this->stats == false)
			{
				$item = $this->stats;
				$this->stats = true;
			}
			else
				$item = true;
		}
    }

    private function setCheckedSub(&$item,$key,$menus)
    {
	    if ($key == 'id' && !is_string($menus[0]) && isset($menus[0]))
		{
			foreach ($menus[0] as $keys => $vals)
			{
				if ($vals['submenu_id'] == $item)
				{
					$this->stats = false;
				}
			}
		}
		if ($key == 'checked' )
		{
			if ($this->stats == false)
			{
				$item = $this->stats;
				$this->stats = true;
			}
			else
            {
				$item = true;
            }
		}
    }


	private function setHidden(&$item,$key,$menus)
    {
        if ($key == 'id' && !is_string($menus[0]) && isset($menus[0]))
		{
                foreach ($menus[0] as $keys => $vals)
                {
                    if ($vals['menu_id'] == $item)
                    {
                        $this->stats2 = true;
                        break;
                    }
                }
		}
		if ($key == 'hidden' )
		{
			if ($this->stats2 == true)
			{
				$item = $this->stats2;
				$this->stats2 = false;
			}
			else
				$item = false;
		}
    }

    private function setHidden2(&$item,$key,&$menus)
    {
        if ($menus[0] != '' && $menus[0] != null && $menus != null)
        {
            if ($key == 'id')
            {
                foreach ($menus[0] as $keys => $vals)
                {
                    if ($vals['menu_id'] == $item)
                    {
                        $menus[1] = true;
                        break;
                    }
                }
            }
            if ($key == 'hidden' )
            {
                if ($menus[1] == true)
                {
                    $item = $menus[1];
                    $menus[1] = 'false';
                }
                else
                    $item = false;
            }
        }
    }

    private function countHidden(&$item,$key,&$data)
    {
   		if ($key == 'hidden' )
		{
			$data[0]++;
			if ($item == 1)
			{
				$data[1]++;
			}
		}
    }
    
    public function getmenuAction()
    {
        $request = $this->getRequest();

        $moduleName = $request->getParam('modulename');
        $userID = $request->getParam('id_user');
        $checked = $request->getParam('checked');
        $is_sub = (boolean)$request->getParam('submenu');

        if (!$is_sub)
            $menu = $this->menu->getMenu();
        else
            $menu = $this->submenu->getSubMenu();
        //Allow menu for all user
        $allowedMenu = array('home');

        $userrole = new Admin_Model_Userrole();
        if ($userID != '')
        {
            if (!$is_sub)
        	    $userMenu = $userrole->getMenuByUserID($userID);
            else
        	    $userMenu = $userrole->getSubMenuByUserID($userID);

        }
        if ($checked)
        { //echo "<pre>";var_dump($menu);echo "</pre>";
        	$getMenu = array();
        	$stat=true;
            if (!$is_sub)
        	    array_walk_recursive($menu, array(&$this,'setChecked'),array($userMenu,$stat));
            else
                array_walk_recursive($menu, array(&$this,'setCheckedSub'),array($userMenu,$stat));
        	foreach ($menu as $key => $val)
        	{
        		if (!in_array($key,array('home','admin')))
        		$getMenu[] = array('text' => $key,'children' => $menu[$key]);
        	}
        }
        else 
        {					

        	$userID = $this->session->idUser;
        	$userMenu = $userrole->getMenuByUserID($userID);
        	
        	$myproject = $this->userrole->getCurrentProject($userID);
        	
        	if (count($myproject) > 0)
        	{
        		$project = array();
        		$site = array();
        		
        		foreach($myproject as $key => $val)
        		{
        			if ($val['prj_kode'] == '')
        			{
        				$workflow = $this->workflow->getWorkflowByUserID($userID,'','',true,' GROUP BY w.prj_kode');
        			}
        			else
        			{
        				$sites = $this->site->fetchAll("prj_kode='" . $val['prj_kode'] . "'");
        				if ($sites)
        				{
        					foreach($sites as $keys => $vals)
        					{
        						$site[] = array(
                                                  'text' => $vals['sit_kode'] . " " .$vals['sit_nama'],
                                                  'leaf' => true,
        										  'cls' => 'site-qdc',
                                                  'id' => 'myproject-' . $val['prj_kode'] . '-' . $vals['sit_kode'],
                                                  'link' => '/home/dashboard/prj_kode/'. $val['prj_kode'] . '/sit_kode/' . $vals['sit_kode'],
                                                  'checked' => '',
                                                  'hidden' => ''
                                                  );
        					}
        				}
        				$project[] = array(
        									'text' => $val['prj_kode'],
        									'cls' => 'project-qdc',
									        'id' => 'home-myproject-'. $val['prj_kode'],
        									'link' => '/home/dashboard/prj_kode/'. $val['prj_kode'],
        									'children' => $site
        									);
                        $project2[] = array(
        									'text' => $val['prj_kode'],
        									'cls' => 'project-qdc',
									        'id' => 'projectmanagement-diary-'. $val['prj_kode'],
        									'link' => '/projectmanagement/diary/diary/prj_kode/'. $val['prj_kode'],
        									'leaf' => true,
                                            'checked' => '',
                                            'hidden' => ''
        									);
        			}
        			$site = array();
        		}
        		$project2[] = array(
        									'text' => 'No Project',
        									'cls' => 'project-qdc',
									        'id' => 'projectmanagement-diary-no-project',
        									'link' => '/projectmanagement/diary/diary/prj_kode/invalid',
        									'leaf' => true,
                                            'checked' => '',
                                            'hidden' => ''
        									);
        		$menu['home'][] = array(
        							'text' => 'My Project',
    								'children' => $project
        							);
                $menu['projectmanagement'][] = array(
        							'text' => 'CME Daily Site Diary',
    								'children' => $project2
        							);
        	}
        	
        	if (count($userMenu) > 0)
        	{
        		if (intval($this->session->privilege) < 300)
        		{    			
	        		unset($menu['admin']);
        		}
        		$getMenu = array(
        						array(
        							'text' => 'Reports',
    								'children' => array (
        											
        										)),
        						array(
        							'text' => 'Transactions',
    								'children' => array (
        											
        										)
        							)	
        				);
                //Bypass menu for allowed Menus
                foreach($userMenu as $k => $v)
                {
                    foreach($allowedMenu as $k2 => $v2)
                    {
                        if (strpos($v['menu_id'],$v2) !== false)
                        {
                            unset($userMenu[$k]);
                        }
                    }

                }

        		array_walk_recursive($menu, array(&$this,'setHidden'),array($userMenu,$hid));
        		foreach ($menu as $key => $val)
        		{
        			foreach ($val as $key2 => $val2)
        			{
        				if (is_array($val2['children']) && count($val2['children']) > 0)
        				{
        					foreach ($val2 as $key3 => $val3)
        					{
        						if (is_array($val3))
        						{
        							foreach ($val3 as $key4 => $val4)
        							{
		        						if($val2['text'] == "Reports")
		        						{
		        							$jumChild = 0;
		        							$jumHidden = 0;
		        							$param = array($jumChild,$jumHidden);
        									array_walk_recursive($val4, array(&$this,'countHidden'),&$param);
        									if ($param[1] < $param[0])
		        								$getMenu[0]['children'][] = $val4;
		        						}
		        						elseif($val2['text'] == "Transactions")
		        						{
		        							$jumChild = 0;
		        							$jumHidden = 0;
		        							$param = array($jumChild,$jumHidden);
        									array_walk_recursive($val4, array(&$this,'countHidden'),&$param);
        									if ($param[1] < $param[0])
		        								$getMenu[1]['children'][] = $val4;
		        						}
		        						else 
		        						{
		        							$notFound = 0;
		        							foreach ($getMenu as $keyMenu => $valMenu)
		        							{
		        								$teks = $val2['text'];//if ($teks == 'Document') var_dump($getMenu);
		        								if ($getMenu[$keyMenu]['text'] == $teks)
		        								{
		        									$getMenu[$keyMenu]['children'][] = $val4;
		        									break;
		        								}
		        								else
		        								{
		        									$notFound++;
		        								}
		        							}
		        							
		        							if ($notFound == count($getMenu))
		        							{
		        								$getMenu[] =  array(
							        							'text' => $val2['text'],
							    								'children' => array($val4)
							        							);			
	        								}
	        						
		        							
		        						}
        							}
        						}
        					}
        				}
        			}
        		}
//        		echo "<pre>";var_dump($getMenu);die;echo "</pre>";
        	}
        	else
        	{
		        switch ($moduleName)
		        {
		        	
	        	case '':
	        	case 'procurement':
	        		$getMenu = $menu['procurement'];	
	        	break;
	        	case 'projectmanagement':
	        		$getMenu = $menu['projectmanagement'];
	        	break;
	        	case 'admin':
	        		if ($this->session->privilege >= 300)
	        		{    			
		        		$getMenu = $menu['admin'];
	        		}
	        	break;
	        	case 'home':
	        		$getMenu = $menu['home'];
	        	break;
	        	case 'sales':
	        		$getMenu = $menu['sales'];
	        	break;
	        	case 'logistic':
	        		$getMenu = $menu['logistic'];
	        	break;
	        	case 'projectstaff':
	        		$getMenu = $menu['projectstaff'];
	        	break;
	        	case 'hr':
	        		$getMenu = $menu['hr'];
	        	break;
				case 'finance':
	        		$getMenu = $menu['finance'];
                break;
		        }
        	}
        }
        $this->view->outputName = json_encode($getMenu);
    }

    public function getmenu2Action()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $moduleName = $request->getParam('modulename');
        $userID = $request->getParam('id_user');
        $checked = $request->getParam('checked');
        $is_sub = (boolean)$request->getParam('submenu');

        if (!$is_sub)
            $menu = $this->menu->getMenu();
        else
            $menu = $this->submenu->getSubMenu();
        //Allow menu for all user
        $allowedMenu = array('home');

        $userrole = new Admin_Model_Userrole();
        if ($userID != '')
        {
            if (!$is_sub)
        	    $userMenu = $userrole->getMenuByUserID($userID);
            else
        	    $userMenu = $userrole->getSubMenuByUserID($userID);

        }
        if ($checked)
        {
        	$getMenu = array();
        	$stat=true;
            if (!$is_sub)
        	    array_walk_recursive($menu, array(&$this,'setChecked'),array($userMenu,$stat));
            else
                array_walk_recursive($menu, array(&$this,'setCheckedSub'),array($userMenu,$stat));
        	foreach ($menu as $key => $val)
        	{
        		if (!in_array($key,array('home','admin')))
        		$getMenu[] = array('text' => $key,'children' => $menu[$key]);
        	}
        }
        else
        {

        	$userID = $this->session->idUser;
        	$userMenu = $userrole->getMenuByUserID($userID);
        	$myproject = $this->userrole->getCurrentProject($userID);

        	if (count($myproject) > 0)
        	{
        		$project = array();
        		$site = array();

        		foreach($myproject as $key => $val)
        		{
        			if ($val['prj_kode'] == '')
        			{
        				$workflow = $this->workflow->getWorkflowByUserID($userID,'','',true,' GROUP BY w.prj_kode');
        			}
        			else
        			{
        				$sites = $this->site->fetchAll("prj_kode='" . $val['prj_kode'] . "'");
        				if ($sites)
        				{
        					foreach($sites as $keys => $vals)
        					{
        						$site[] = array(
                                                  'text' => $vals['sit_kode'] . " " .$vals['sit_nama'],
                                                  'leaf' => true,
        										  'cls' => 'site-qdc',
                                                  'id' => 'myproject-' . $val['prj_kode'] . '-' . $vals['sit_kode'],
                                                  'link' => '/home/dashboard/prj_kode/'. $val['prj_kode'] . '/sit_kode/' . $vals['sit_kode'],
                                                  'checked' => '',
                                                  'hidden' => ''
                                                  );
        					}
        				}
        				$project[] = array(
        									'text' => $val['prj_kode'],
        									'cls' => 'project-qdc',
									        'id' => 'home-myproject-'. $val['prj_kode'],
        									'link' => '/home/dashboard/prj_kode/'. $val['prj_kode'],
        									'children' => $site
        									);
                        $project2[] = array(
        									'text' => $val['prj_kode'],
        									'cls' => 'project-qdc',
									        'id' => 'projectmanagement-diary-'. $val['prj_kode'],
        									'link' => '/projectmanagement/diary/diary/prj_kode/'. $val['prj_kode'],
        									'leaf' => true,
                                            'checked' => '',
                                            'hidden' => ''
        									);
        			}
        			$site = array();
        		}
        		$project2[] = array(
        									'text' => 'No Project',
        									'cls' => 'project-qdc',
									        'id' => 'projectmanagement-diary-no-project',
        									'link' => '/projectmanagement/diary/diary/prj_kode/invalid',
        									'leaf' => true,
                                            'checked' => '',
                                            'hidden' => ''
        									);
        		$menu['home'][] = array(
        							'text' => 'My Project',
    								'children' => $project
        							);
                $menu['projectmanagement'][] = array(
        							'text' => 'CME Daily Site Diary',
    								'children' => $project2
        							);
        	}

        	if (count($userMenu) > 0)
        	{
        		if ($this->session->privilege < 300)
        		{
	        		unset($menu['admin']);
        		}
        		$getMenu = array(
        						array(
        							'text' => 'Reports',
    								'children' => array (

        										)),
        						array(
        							'text' => 'Transactions',
    								'children' => array (

        										)
        							)
        				);
                //Bypass menu for allowed Menus
                foreach($userMenu as $k => $v)
                {
                    foreach($allowedMenu as $k2 => $v2)
                    {
                        if (strpos($v['menu_id'],$v2) !== false)
                        {
                            unset($userMenu[$k]);
                        }
                    }

                }
                var_dump($userMenu);die;
        		array_walk_recursive($menu, array(&$this,'setHidden2'),array($userMenu,$hid));echo "after";
var_dump($menu);die;
        		foreach ($menu as $key => $val)
        		{
        			foreach ($val as $key2 => $val2)
        			{
        				if (is_array($val2['children']) && count($val2['children']) > 0)
        				{
        					foreach ($val2 as $key3 => $val3)
        					{
        						if (is_array($val3))
        						{
        							foreach ($val3 as $key4 => $val4)
        							{
		        						if($val2['text'] == "Reports")
		        						{
		        							$jumChild = 0;
		        							$jumHidden = 0;
		        							$param = array($jumChild,$jumHidden);
        									array_walk_recursive($val4, array(&$this,'countHidden'),&$param);
        									if ($param[1] < $param[0])
		        								$getMenu[0]['children'][] = $val4;
		        						}
		        						elseif($val2['text'] == "Transactions")
		        						{
		        							$jumChild = 0;
		        							$jumHidden = 0;
		        							$param = array($jumChild,$jumHidden);
        									array_walk_recursive($val4, array(&$this,'countHidden'),&$param);
        									if ($param[1] < $param[0])
		        								$getMenu[1]['children'][] = $val4;
		        						}
		        						else
		        						{
		        							$notFound = 0;
		        							foreach ($getMenu as $keyMenu => $valMenu)
		        							{
		        								$teks = $val2['text'];//if ($teks == 'Document') var_dump($getMenu);
		        								if ($getMenu[$keyMenu]['text'] == $teks)
		        								{
		        									$getMenu[$keyMenu]['children'][] = $val4;
		        									break;
		        								}
		        								else
		        								{
		        									$notFound++;
		        								}
		        							}

		        							if ($notFound == count($getMenu))
		        							{
		        								$getMenu[] =  array(
							        							'text' => $val2['text'],
							    								'children' => array($val4)
							        							);
	        								}


		        						}
        							}
        						}
        					}
        				}
        			}
        		}
//        		echo "<pre>";var_dump($getMenu);die;echo "</pre>";
        	}
        	else
        	{
		        switch ($moduleName)
		        {

	        	case '':
	        	case 'procurement':
	        		$getMenu = $menu['procurement'];
	        	break;
	        	case 'projectmanagement':
	        		$getMenu = $menu['projectmanagement'];
	        	break;
	        	case 'admin':
	        		if ($this->session->privilege >= 300)
	        		{
		        		$getMenu = $menu['admin'];
	        		}
	        	break;
	        	case 'home':
	        		$getMenu = $menu['home'];
	        	break;
	        	case 'sales':
	        		$getMenu = $menu['sales'];
	        	break;
	        	case 'logistic':
	        		$getMenu = $menu['logistic'];
	        	break;
	        	case 'projectstaff':
	        		$getMenu = $menu['projectstaff'];
	        	break;
	        	case 'hr':
	        		$getMenu = $menu['hr'];
	        	break;
				case 'finance':
	        		$getMenu = $menu['finance'];
		        	break;
		        }
        	}
        }

        $this->view->outputName = json_encode($getMenu);
    }
}


?>
