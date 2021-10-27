<?php

class Default_Models_Menu extends Zend_Db_Table_Abstract
{
	private $db;
	
    protected $_name = 'menu';

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }
    
    public function getMenu($module='')
    {
    	$menu['procurement'] = array(
            array(
                'text' => 'Reports',
                'children' => array (
                   array(
                    'text' => 'Budget',
                    'id' => 'procurement-budget',
                    'children' => array (
                         array(
                                'text' => 'Project Budget',
                                'leaf' => true,
                                'id' => 'procurement-budget-projectbudget',
                                'link' => '/default/report/showbudget',
                                'checked' => '',
                                'hidden' => ''
                                ),
                          array(
                                'text' => 'Detail Project Budget',
                                'leaf' => true,
                                'id' => 'procurement-budget-detailprojectbudget',
                                'link' => '/default/report/showdetailprojectbudget',
                                'checked' => '',
                                'hidden' => ''
                                ),
                          array(
                                'text' => 'Progress Project Budget',
                                'leaf' => true,
                                'id' => 'procurement-budget-progressprojectbudget',
                                'link' => '/default/report/showcompareboq',
                                'checked' => '',
                                'hidden' => ''
                                )
                        )
                    ),
                    array(
                        'text' => 'Procurement Request',
                        'id' => 'procurement-pr',
                        'children' => array (
                            array(
                                'text' => 'PR Summary',
                                'leaf' => true,
                                'id' => 'procurement-pr-prsummary',
                                'link' => '/default/report/showprsummary',
                                'checked' => '',
                                'hidden' => ''
                            ),
                            array(
                                'text' => 'PR Detail',
                                'leaf' => true,
                                'id' => 'procurement-pr-prdetail',
                                'link' => '/default/report/showprdetail',
                                'checked' => '',
                                'hidden' => ''
                            ),
                            array(
                                 'text' => 'PR to PO',
                                'leaf' => true,
                                 'id' => 'procurement-prtopodetail',
                                 'link' => '/default/report/showoutprpodet',
                                'checked' => '',
                                'hidden' => ''
                            ),
                            array(
                                 'text' => 'Outstanding PR to PO',
                                'leaf' => true,
                                 'id' => 'procurement-pr-prtopo',
                                 'link' => '/default/report/showoutprpo',
                                'checked' => '',
                                'hidden' => ''
                                  )
                        )
                    ),
                    array(
                        'text' => 'Purchase Order & Request Payment Invoice',
                        'id' => 'procurement-po',
                        'children' => array (
                              array(
                                    'text' => 'PO Summary',
                                    'leaf' => true,
                                    'id' => 'procurement-po-posummary',
                                    'link' => '/default/report/showposummary',
                                    'checked' => '',
                                    'hidden' => ''
                                  ),
                              array(
                                    'text' => 'PO Detail',
                                    'leaf' => true,
                                    'id' => 'procurement-po-podetail',
                                    'link' => '/default/report/showpodetail',
                                    'checked' => '',
                                    'hidden' => ''
                                  ),
                              array(
                                    'text' => 'RPI Summary',
                                    'leaf' => true,
                                    'id' => 'procurement-po-rpisummary',
                                    'link' => '/default/report/showrpisummary',
                                    'checked' => '',
                                    'hidden' => ''
                                  ),
                               array(
                                    'text' => 'RPI Detail',
                                    'leaf' => true,
                                    'id' => 'procurement-po-rpidetail',
                                    'link' => '/default/report/showrpidetail',
                                    'checked' => '',
                                    'hidden' => ''
                                  ),
                               array(
                                     'text' => 'PO to RPI',
                                     'leaf' => true,
                                     'id' => 'procurement-po-outporpi',
                                     'link' => '/default/report/showporpisummary',
                                    'checked' => '',
                                    'hidden' => ''
                                   ),
                               array(
                                     'text' => 'Outstanding PO to RPI',
                                     'leaf' => true,
                                     'id' => 'procurement-po-outporpisum',
                                     'link' => '/default/report/showoutporpi',
                                    'checked' => '',
                                    'checked' => '',
                                    'hidden' => ''
                                   ),
                                array(
                                     'text' => 'PO Tax Summary',
                                     'leaf' => true,
                                     'id' => 'procurement-po-poppn',
                                     'link' => '/default/report/showpoppn',
                                    'checked' => '',
                                    'hidden' => ''
                                   )
                        )
                    ),
                    array(
                        'text' => 'Advance Request Form & Advance Settlement Form',
                        'id' => 'procurement-arfasf',
                        'children' => array (
                              array(
                                    'text' => 'ARF Summary',
                                    'leaf' => true,
                                    'id' => 'procurement-arfasf-arfsummary',
                                    'link' => '/default/report/showarfsummary',
                                    'checked' => '',
                                    'hidden' => ''
                                  ),
                               array(
                                    'text' => 'ARF Detail',
                                    'leaf' => true,
                                    'id' => 'procurement-arfasf-arfdetail',
                                    'link' => '/default/report/showarfdetail',
                                    'checked' => '',
                                    'hidden' => ''
                                  ),
                            array(
                                'text' => 'ARF Aging',
                                'leaf' => true,
                                'id' => 'procurement-arfasf-arfaging',
                                'link' => '/default/report/show-arf-aging',
                                'checked' => '',
                                'hidden' => ''
                            ),
                               array(
                                    'text' => 'ASF Summary',
                                    'leaf' => true,
                                    'id' => 'procurement-arfasf-asfsummary',
                                    'link' => '/default/report/showasfsummary',
                                    'checked' => '',
                                    'hidden' => ''
                                  ),
                                array(
                                    'text' => 'ASF Detail',
                                    'leaf' => true,
                                    'id' => 'procurement-arfasf-asfdetail',
                                    'link' => '/default/report/showasfdetail',
                                    'checked' => '',
                                    'hidden' => ''
                                  ),
                                array(
                                     'text' => 'ARF to ASF',
                                     'leaf' => true,
                                     'id' => 'procurement-arfasf-arfasf',
                                     'link' => '/default/report/showarfasf',
                                    'checked' => '',
                                    'hidden' => ''
                                      )
                         )
                    ),
                    array(
                        'text' => 'Delivery Order',
                        'id' => 'procurement-do',
                        'children' => array (
                            array(
                                'text' => 'DO Request Summary',
                                'leaf' => true,
                                'id' => 'procurement-do-dorequestsummary',
                                'link' => '/default/report/showdor',
                                'checked' => '',
                                'hidden' => ''
                            ),
                            array(
                                'text' => 'DO Request Detail',
                                'leaf' => true,
                                'id' => 'procurement-do-dorequestdetail',
                                'link' => '/default/report/showmdidetail',
                                'checked' => '',
                                'hidden' => ''
                            ),
                            array(
                                'text' => 'DO Summary',
                                'leaf' => true,
                                'id' => 'procurement-do-dosummary',
                                'link' => '/default/report/showdosummary',
                                'checked' => '',
                                'hidden' => ''
                            ),
                            array(
                                'text' => 'DO Detail',
                                'leaf' => true,
                                'id' => 'procurement-do-doformdetail',
                                'link' => '/default/report/showdodetail',
                                'checked' => '',
                                'hidden' => ''
                            ),
//                            array(
//                                'text' => 'DO Form Detail',
//                                'leaf' => true,
//                                'id' => 'procurement-do-doformdetail',
//                                'link' => '/default/report/showdodetail',
//                                'checked' => '',
//                                'hidden' => ''
//                              ),
                            array(
                                'text' => 'DO Request to DO',
                                'leaf' => true,
                                'id' => 'procurement-do-dorequesttodo',
                                'link' => '/default/report/showmdimdo',
                                'checked' => '',
                                'hidden' => ''
                            ),
                            array(
                                'text' => 'DO to DO Form',
                                'leaf' => true,
                                'id' => 'procurement-do-dotodo',
                                'link' => '/default/report/showmdodo',
                                'checked' => '',
                                'hidden' => ''
                            ),
                            array(
                                'text' => 'Outstanding DO Request to DO',
                                'leaf' => true,
                                'id' => 'procurement-do-outdorequesttodo',
                                'link' => '/default/report/showoutmdimdo',
                                'checked' => '',
                                'hidden' => ''
                            ),
                            array(
                                'text' => 'Outstanding DO to DO Form',
                                'leaf' => true,
                                'id' => 'procurement-do-outdotodoform',
                                'link' => '/default/report/showoutmdodo',
                                'checked' => '',
                                'hidden' => ''
                            )

                        )

                    ),
                    array(
                        'text' => 'Material Receive',
                        'id' => 'procurement-materialreceive',
                        'children' => array (
                                                                                                            array(
                                                                                                                     'text' => 'Material From Supplier',
                                                                                                                     'leaf' => true,
                                                                                                                     'id' => 'procurement-materialreceive-supplier',
                                                                                                                     'link' => '/default/report/showwhsupplier',
                                                                                                                    'checked' => '',
                                                                                                                    'hidden' => ''
                                                                                                                  ),
                                                                                                             array(
                                                                                                                     'text' => 'Material Return',
                                                                                                                     'leaf' => true,
                                                                                                                     'id' => 'procurement-materialreceive-whreturn',
                                                                                                                     'link' => '/default/report/showwhreturn',
                                                                                                                    'checked' => '',
                                                                                                                    'hidden' => ''
                                                                                                                   ),
                                                                                                             array(
                                                                                                                     'text' => 'Material Cancel',
                                                                                                                     'leaf' => true,
                                                                                                                     'id' => 'procurement-materialreceive-cancel',
                                                                                                                     'link' => '/default/report/showwhbringback',
                                                                                                                    'checked' => '',
                                                                                                                    'hidden' => ''
                                                                                                                  )
                                                                                        )
                                                                                    ),array(
                                                                                          'text' => 'Reimbursement',
                                                                                          'id' => 'reimbursement',
                                                                                          'children' => array(
                                                                                                            array(
                                                                                                                'text' => 'Reimbursement Debit Note',
                                                                                                                'leaf' => true,
                                                                                                                'id' => 'reimbursement-debit-note',
                                                                                                                'hidden' => '',
                                                                                                                'checked' => '',
                                                                                                                'link' => '/finance/paymentreimbursement/reportreimbursement'
                                                                                                            ),
                                                                                                            array(
                                                                                                                'text' => 'Outstanding Reimbursement Debit note report',
                                                                                                                'leaf' => true,
                                                                                                                'id' => 'Outstanding reimbursement-debit-note',
                                                                                                                'hidden' => '',
                                                                                                                'checked' => '',
                                                                                                                'link' => '/finance/paymentreimbursement/outstandingreportreimbursement'
                                                                                                            )
                                                                                          )
                    )

            )
        ),
        					array('text' => 'Transactions',
    								'children' => array (
                                                                                               array(
        												'text' => 'Procurement Request',
												        'leaf' => true,
												        'id' => 'procurement-trans-pr',
        												'link' => '/procurement/procurement/pr',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
                                                                                                array(
        												'text' => 'Purchase Order',
												        'leaf' => true,
												        'id' => 'procurement-trans-po',
        												'link' => '/procurement/procurement/po',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                     ),
                                                                                                array(
        												'text' => 'Request Payment for Invoice',
												        'leaf' => true,
												        'id' => 'procurement-trans-rpi',
        												'link' => '/procurement/procurement/rpi',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                     ),
                                                                                                array(
                                                        'text' => 'Advance Request Form',
                                                        'leaf' => true,
                                                        'id' => 'procurement-trans-arf',
                                                        'link' => '/procurement/procurement/arf',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                     ),
                                                                                                array(
        												'text' => 'Advance Settlement Form',
												        'leaf' => true,
												        'id' => 'procurement-trans-asf',
        												'link' => '/procurement/procurement/asf',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                     ),
                                                        array(
        												'text' => 'Progress Piece Meal',
												        'leaf' => true,
												        'id' => 'procurement-trans-pmeal',
        												'link' => '/procurement/procurement/pmeal',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                     ),

                                                        array(
        												'text' => 'Reimbursable Expenditure',
												        'leaf' => true,
												        'id' => 'procurement-trans-reimburs',
        												'link' => '/procurement/procurement/reimburs',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                     ),
                                                        array(
        												'text' => 'Update Request Price',
												        'leaf' => true,
												        'id' => 'procurement-update-request-price',
        												'link' => '/sales/requestprice/updatemenu',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                     ),
//                                                        array(
//                                                            'text' => 'Entertainment Request Form',
//                                                            'leaf' => true,
//                                                            'id' => 'procurement-entertainment-request-form',
//                                                            'link' => '/procurement/entertainment-request',
//                                                            'checked' => '',
//                                                            'hidden' => ''
//                                                        ),
                                                        array(
                                                            'text' => 'Bussiness Trip Request Form',
                                                            'leaf' => true,
                                                            'id' => 'procurement-bt-request-form',
                                                            'link' => '/procurement/bt-request',
                                                            'checked' => '',
                                                            'hidden' => ''
                                                        ),
                                                        array(
                                                            'text' => 'Bussiness Trip Settlement Form',
                                                            'leaf' => true,
                                                            'id' => 'procurement-bt-settlement-form',
                                                            'link' => '/procurement/bt-settlement',
                                                            'checked' => '',
                                                            'hidden' => ''
                                                        )
                                                        )


                                                     )
        				  
        			);
        
        $menu['projectmanagement'] = array(
        						array(
        							'text' => 'Reports',
    								'children' => array (
        										    array(
                                                        'text' => 'General Report',
                                                        'leaf' => true,
                                                        'id' => 'projectmanagement-general-report',
                                                        'link' => '/projectmanagement/report/showgeneral',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                    ),
                                                    array(
                                                        'text' => 'General Report (Overhead)',
                                                        'leaf' => true,
                                                        'id' => 'projectmanagement-general-report-overhead',
                                                        'link' => '/projectmanagement/report/showgeneraloverhead',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                    ),
                                                    array(
                                                        'text' => 'CFS Report',
                                                        'leaf' => true,
                                                        'id' => 'projectmanagement-cfs-report',
                                                        'link' => '/projectmanagement/report/showcfs',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                    ),
                                                    array(
                                                        'text' => 'S-Curve Report',
                                                        'leaf' => true,
                                                        'id' => 'projectmanagement-scurve-report',
                                                        'link' => '/projectmanagement/report/showscurve',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                    )
        										)
        							),
        						array('text' => 'Transactions',
    								'children' => array (
				        						array(
				        							'text' => 'Budget',
				    								'children' => array (
				        											array(
				        												'text' => 'Project Budget',
																        'leaf' => true,
																        'id' => 'projectmanagement-trans-projectbudget',
				        												'link' => '/projectmanagement/budget/showcreateboq3',
                                                        				'checked' => '',
                                                        				'hidden' => ''
				        												),
                                                                    array(
                                                                    'text' => 'Approval For Expenditure',
                                                                    'leaf' => true,
                                                                    'id' => 'projectmanagement-trans-afe',
                                                                    'link' => '/projectmanagement/afe/afe',
                                                                    'checked' => '',
                                                                    'hidden' => ''
                                                                     ),
//                                                                    array(
//				        												'text' => 'Transfer From AFE To Budget',
//																        'leaf' => true,
//																        'id' => 'projectmanagement-trans-projectcboq3',
//				        												'link' => '/projectmanagement/budget/cboq3',
//                                                        				'checked' => '',
//                                                        				'hidden' => ''
//                                                                    ),
                                                                    array(
				        												'text' => 'Register Customer PO',
																        'leaf' => true,
																        'id' => 'projectmanagement-trans-projectpraco',
				        												'link' => '/projectmanagement/budget/praco',
                                                        				'checked' => '',
                                                        				'hidden' => ''
                                                                    )
				        										)
				        							),array(
                                                        'text' => 'Close Existing Project/Site',
                                                        'leaf' => true,
                                                        'id' => 'projectmanagement-project-close',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/projectmanagement/projectclose/project'
                                                    ),
//				        						array(
//				        							'text' => 'Timesheet',
//				    								'children' => array (
				        											array(
				        												'text' => 'Timesheet',
																        'leaf' => true,
																        'id' => 'projectmanagement-trans-timesheet',
				        												'link' => '/projectmanagement/timesheet/timesheet',
                                                        				'checked' => '',
                                                        				'hidden' => ''
//				        												)
//				        										)
				        							),
                                                    array(
                                                        'text' => 'Project Progress',
                                                        'leaf' => true,
                                                        'id' => 'projectmanagement-trans-progress',
                                                        'link' => '/projectmanagement/progress/progress',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                    ),array(
                                                        'text' => 'Master Engineer Work',
                                                        'leaf' => true,
                                                        'id' => 'projectmanagement-engineer-work',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/projectmanagement/engineerwork/engineerwork'
                                                    )
	        								)
        							)
        					);			
        $menu['admin'] = array(
        						array(
        							'text' => 'Transactions',
    								'children' => array (
//                                                    array(
//        												'text' => 'Master Supplier',
//												        'leaf' => true,
//												        'id' => 'admin-trans-supp',
//        												'link' => '/logistic/logistic/supplier',
//                                                        'checked' => '',
//                                                        'hidden' => ''
//                                                    ),
        											array(
        												'text' => 'AFE Non-Workflow',
												        'leaf' => true,
												        'id' => 'admin-afe-nonworkflow',
        												'link' => '/admin/afe/afe',
        												'checked' => '',
                                                        'hidden' => ''
        												),
                                                    array(
        												'text' => 'Transfer From AFE to Budget Non-Workflow',
												        'leaf' => true,
												        'id' => 'admin-cboq3-nonworkflow',
        												'link' => '/admin/budget/cboq3',
        												'checked' => '',
                                                        'hidden' => ''
        												),
                                                    array(
                                                        'text' => 'Project Budget Input',
                                                        'leaf' => true,
                                                        'id' => 'admin-trans-projectbudget',
                                                        'link' => '/admin/budget/showcreateboq3',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                        )
        										)
        							),

                                array(
        							'text' => 'Workflow',
    								'children' => array (
                                        array(
                                            'text' => 'Workflow',
                                            'leaf' => true,
                                            'id' => 'admin-workflow',
                                            'link' => '/admin/workflow/workflow',
                                            'checked' => '',
                                            'hidden' => ''
                                            ),
                                        array(
                                            'text' => 'Document',
                                            'leaf' => true,
                                            'id' => 'admin-document',
                                            'link' => '/admin/document/document',
                                            'checked' => '',
                                            'hidden' => ''
                                            ),
                                        array(
                                            'text' => 'Notification',
                                            'leaf' => true,
                                            'id' => 'admin-notification',
                                            'link' => '/admin/notification/index',
                                            'checked' => '',
                                            'hidden' => ''
                                        ),
                                        array(
                                            'text' => 'Nominal Limit',
                                            'leaf' => true,
                                            'id' => 'admin-nominal-limit',
                                            'link' => '/admin/nominal/index',
                                            'checked' => '',
                                            'hidden' => ''
                                        )
        										)
        							),
        						array(
        							'text' => 'User',
    								'children' => array (
        											array(
        												'text' => 'User Management',
												        'leaf' => true,
												        'id' => 'admin-user',
        												'link' => '/admin/user/view',
        												'checked' => '',
                                                        'hidden' => ''
        												),
        											array(
        												'text' => 'User Role',
												        'leaf' => true,
												        'id' => 'admin-userrole',
        												'link' => '/admin/userrole/show',
        												'checked' => '',
                                                        'hidden' => ''
        												),
        											array(
        												'text' => 'Personal Assistant',
												        'leaf' => true,
												        'id' => 'admin-pa',
        												'link' => '/admin/assistant/assistant',
        												'checked' => '',
                                                        'hidden' => ''
        												)
        										)
        							),
        						array(
        							'text' => 'Menu',
    								'children' => array (
        											array(
        												'text' => 'Menu Management',
												        'leaf' => true,
												        'id' => 'admin-menu',
        												'link' => '/admin/menu/show',
        												'checked' => '',
                                                        'hidden' => ''
        												)
        										)
        							),
                                array(
                                    'text' => 'Setting',
                                    'children' => array(
                                                    array(
                                                        'text' => 'Pulsa',
                                                        'leaf' => true,
												        'id' => 'admin-setting',
        												'link' => '/admin/document/pulsa',
        												'checked' => '',
                                                        'hidden' => ''
                                                    ),array(
                                                        'text' => 'News',
                                                        'leaf' => true,
												        'id' => 'admin-news',
        												'link' => '/admin/news/menu',
        												'checked' => '',
                                                        'hidden' => ''
                                                    )
                                    )
                                )
        					);
        $menu['home'] = array(
            array(
                'text' => 'Assign Site to My Team',
                'leaf' => true,
                'id' => 'home-assignsite',
                'link' => '/default/assign-site',
                'checked' => '',
                'hidden' => ''
            ),
            array(
                'text' => 'Project Dashboard',
                'leaf' => true,
                'id' => 'home-projectdashboard',
                'link' => '/default/projectdashboard/index',
                'checked' => '',
                'hidden' => ''
            ),
            array(
                'text' => 'Document',
                'children' => array (
                                array(
                                    'text' => 'My Document',
                                    'leaf' => true,
                                    'id' => 'home-mydocument',
                                    'link' => '/home/viewdocument',
                                    'checked' => '',
                                    'hidden' => ''
                                    ),
                                array(
                                    'text' => 'Check Document',
                                    'leaf' => true,
                                    'id' => 'home-checkdocument',
                                    'link' => '/home/checkdocument',
                                    'checked' => '',
                                    'hidden' => ''
                                    )
                            )
            )
        );
        $menu['sales'] = array(
        						array(
        							'text' => 'Reports',
    								'children' => array (
        											array(
        												'text' => 'Customer Order Report',
												        'leaf' => true,
												        'id' => 'sales-co-report',
        												'link' => '/sales/report/reportco',
                                                        'checked' => '',
                                                        'hidden' => ''
        												),
                                                    array(
        												'text' => 'Request Price Report',
												        'leaf' => true,
												        'id' => 'sales-request-price-report',
        												'link' => '/sales/requestprice/reportmenu',
                                                        'checked' => '',
                                                        'hidden' => ''
        												)
        										)
        							),
        						array(
        							'text' => 'Transactions',
    								'children' => array (
        											array(
        												'text' => 'Customer Order',
												        'leaf' => true,
												        'id' => 'sales-trans-co',
        												'link' => '/sales/sales/co',
                                                        'checked' => '',
                                                        'hidden' => ''
        												),
                                                    array(
        												'text' => 'Request Price',
												        'leaf' => true,
												        'id' => 'sales-request-price',
        												'link' => '/sales/requestprice/menu',
                                                        'checked' => '',
                                                        'hidden' => ''
        												)
        											
        										)
        							)
        					); 			
        $menu['logistic'] = array(
        						array(
        							'text' => 'Reports',
    								'children' => array (
                                        array(
                                            'text' => 'Closing Inventory Report',
                                            'leaf' => true,
                                            'id' => 'logistic-closing-inventory-report',
                                            'link' => '/logistic/report/inventory',
                                            'checked' => '',
                                            'hidden' => ''
                                        ),
                                        array(
                                            'text' => 'Current Inventory Report',
                                            'leaf' => true,
                                            'id' => 'logistic-current-inventory-report',
                                            'link' => '/logistic/report/current-inventory',
                                            'checked' => '',
                                            'hidden' => ''
                                        ),
                                        array(
                                            'text' => 'Fixed Asset Transaction Report',
                                            'leaf' => true,
                                            'id' => 'fixed-asset-transaction-report',
                                            'link' => '/logistic/report/assetreportmenu',
                                            'checked' => '',
                                            'hidden' => ''
                                        ),
                                        array(
                                            'text' => 'Stock Card',
                                            'leaf' => true,
                                            'id' => 'logistic-stock-card-report',
                                            'link' => '/logistic/report/cardstock',
                                            'checked' => '',
                                            'hidden' => ''
                                        ),
                                    )
                                ),
        						array(
        							'text' => 'Transactions',
    								'children' => array (
        										array(
        												'text' => 'Delivery Order Request',
												        'leaf' => true,
												        'id' => 'delivery-trans-dor',
        												'link' => '/logistic/logistic/dor',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
                                                array(
        												'text' => 'Delivery Order',
												        'leaf' => true,
												        'id' => 'delivery-trans-do',
        												'link' => '/logistic/logistic/do',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
                                                array(
        												'text' => 'Material Return',
												        'leaf' => true,
												        'id' => 'delivery-trans-ret',
        												'link' => '/logistic/logistic/ilov',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
                                                array(
        												'text' => 'Material Cancel',
												        'leaf' => true,
												        'id' => 'delivery-trans-can',
        												'link' => '/logistic/logistic/ican',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
                                                array(
        												'text' => 'i-Supp',
												        'leaf' => true,
												        'id' => 'delivery-trans-isupp',
        												'link' => '/logistic/logistic/isupp',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),

                                                array(
        												'text' => 'Master Supplier',
												        'leaf' => true,
												        'id' => 'delivery-trans-supp',
        												'link' => '/logistic/logistic/supplier',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
                                                array(
        												'text' => 'Master Type Supplier',
												        'leaf' => true,
												        'id' => 'delivery-trans-typesupp',
        												'link' => '/logistic/logistictypesupplier/typesupplier',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
                                                array(
        												'text' => 'Master Specialist Supplier',
												        'leaf' => true,
												        'id' => 'delivery-trans-specsupp',
        												'link' => '/logistic/logisticspecsupplier/specsupplier',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
//                                                array(
//        												'text' => 'Master List Material',
//												        'leaf' => true,
//												        'id' => 'delivery-trans-mat',
//        												'link' => '/logistic/logistic/listmaterial',
//                                                        'checked' => '',
//                                                        'hidden' => ''
//                                                                                                      ),
                                                array(
        												'text' => 'Master Unit Of Measurement',
												        'leaf' => true,
												        'id' => 'delivery-trans-sat',
        												'link' => '/logistic/logisticsatuan/satuan',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
                                                array(
        												'text' => 'Master Customer',
												        'leaf' => true,
												        'id' => 'delivery-trans-cus',
        												'link' => '/logistic/logisticcustomer/customer',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
                                                array(
        												'text' => 'Master List',
												        'leaf' => true,
												        'id' => 'master-material',
        												'link' => '/logistic/logisticbarang/menu',
                                                        'checked' => '',
                                                        'hidden' => ''
                                                                                                      ),
                                                array(
                                                    'text' => 'Closing',
                                                    'leaf' => true,
                                                    'id' => 'logistic-closing-inventory',
                                                    'link' => '/logistic/inventory/closingmenu',
                                                    'checked' => '',
                                                    'hidden' => ''
                                                ),
                                                array(
                                                    'text' => 'Fixed Assets',
                                                    'leaf' => true,
                                                    'id' => 'fixed-asset',
                                                    'link' => '/logistic/fixedasset/menu',
                                                    'checked' => '',
                                                    'hidden' => ''
                                                ),
        										)
        							)	
        					);		
        $menu['projectstaff'] = array(
        						array(
        							'text' => 'Reports',
    								'children' => array (
                                        array(
                                            'text' => 'Master List',
                                            'leaf' => true,
                                            'id' => 'projectstaff-listmastermaterial',
                                            'link' => '/default/report/showbarang',
                                            'checked' => '',
                                            'hidden' => ''
                                        ),
                                        array(
                                            'text' => 'Master List (New Project)',
                                            'leaf' => true,
                                            'id' => 'projectstaff-listmastermaterial-newproject',
                                            'link' => '/logistic/report/master-list-new-project',
                                            'checked' => '',
                                            'hidden' => ''
                                        )
                                    )
                                ),
        						array(
        							'text' => 'Transactions',
    								'children' => array (
        											
        										)
        							)	
        					);	
        $menu['hr'] = array(
        						array(
        							'text' => 'Reports',
    								'children' => array (
        											array(
                                                                        'text' => 'Attendance List',
                                                                        'leaf' => true,
                                                                        'id' => 'hr-attendancelist',
                                                                        'link' => '/humanresource/humanresource/attendancelist',
        																'checked' => '',
                                                        				'hidden' => ''
                                                                        ),
                                                     array(
                                                                        'text' => 'Salary Summary',
                                                                        'leaf' => true,
                                                                        'id' => 'hr-summarysalary',
                                                                        'link' => '/humanresource/report/showsalary',
        																'checked' => '',
                                                        				'hidden' => ''
                                                                        ),
                                                     array(
                                                                        'text' => 'Timesheet Summary',
                                                                        'leaf' => true,
                                                                        'id' => 'hr-summarytimesheet',
                                                                        'link' => '/projectmanagement/timesheet/alltimesheet/from/hr',
        																'checked' => '',
                                                        				'hidden' => ''
                                                                        ),
                                                     array(
                                                                        'text' => 'Timesheet Detail',
                                                                        'leaf' => true,
                                                                        'id' => 'hr-detailtimesheet',
                                                                        'link' => '/projectmanagement/timesheet/alldetailtimesheet/from/hr',
        																'checked' => '',
                                                        				'hidden' => ''
                                                                        )
        										)
        							),
        						array(
        							'text' => 'Transactions',
    								'children' => array (
        											array(
                                                                        'text' => 'Overhead Project',
                                                                        'leaf' => true,
                                                                        'id' => 'hr-ohp',
                                                                        'link' => '/humanresource/humanresource/ohp',
        																'checked' => '',
                                                        				'hidden' => ''
                                                                        ),
                                                    array(
                                                                        'text' => 'Timesheet Periode',
                                                                        'leaf' => true,
                                                                        'id' => 'timesheet-periode',
                                                                        'link' => '/humanresource/timesheet/timesheetperiode',
        																'checked' => '',
                                                        				'hidden' => ''
                                                                        )
        										)
        							)	
        					);	
        $menu['finance'] = array(
        						array(
        							'text' => 'Reports',
    								'children' => array (
                                            array (
                                                'text' => 'CO to Invoice',
                                                'leaf' => true,
                                                'id' => 'finance-report-cotoinvoice',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/co-to-invoice'
                                            ),
                                            array (
                                                    'text' => 'Invoice',
                                                    'leaf' => true,
                                                    'id' => 'finance-report-invoice',
                                                    'checked' => '',
                                                    'hidden' => '',
                                                    'link' => '/finance/invoice/reportinvoice'
                                            ),array (
                                                    'text' => 'Invoice Summary',
                                                    'leaf' => true,
                                                    'id' => 'finance-report-invoice-summary',
                                                    'checked' => '',
                                                    'hidden' => '',
                                                    'link' => '/finance/report/showinvoicesummary'
                                            ),array(
                                                    'text' => 'Bank Payment Voucher Report',
                                                    'leaf' => true,
                                                    'id' => 'finance-bank-payment-voucher-report',
                                                    'checked' => '',
                                                    'hidden' => '',
                                                    'link' => '/finance/report/bankpaymentvoucher'
                                            ),array(
                                                    'text' => 'Payment Report',
                                                    'leaf' => true,
                                                    'id' => 'finance-payment-report',
                                                    'checked' => '',
                                                    'hidden' => '',
                                                    'link' => '/finance/report/payment'
                                            ),
                                            array(
                                                'text' => 'Trial Balance',
                                                'leaf' => true,
                                                'id' => 'finance-trial-balance-sheet',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/trial-balancesheet'

                                            ),
//                                            array(
//                                                    'text' => 'Trial Balance',
//                                                    'leaf' => true,
//                                                    'id' => 'finance-trial-balance-sheet',
//                                                    'checked' => '',
//                                                    'hidden' => '',
//                                                    'link' => '/finance/report/trial-balancesheet'
//                                            ),
                                            array(
                                                'text' => 'Balance Sheet',
                                                'leaf' => true,
                                                'id' => 'finance-balance-sheet',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/balancesheet'
                                            ),array(
                                                'text' => 'Profit & Loss',
                                                'leaf' => true,
                                                'id' => 'finance-profit-loss',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/profitloss'
                                            )
                                            ,array(
                                                    'text' => 'Profit & Loss Preview',
                                                    'leaf' => true,
                                                    'id' => 'finance-profit-loss-preview',
                                                    'checked' => '',
                                                    'hidden' => '',
                                                    'link' => '/finance/report/profit-loss-preview'
                                            ),array(
                                                    'text' => 'Tax Recon Report',
                                                    'leaf' => true,
                                                    'id' => 'finance-tax-recon-report',
                                                    'checked' => '',
                                                    'hidden' => '',
                                                    'link' => '/finance/report/taxrecon'
                                            ),
//                                            array(
//                                                'text' => 'ARF Aging',
//                                                'leaf' => true,
//                                                'id' => 'finance-arf-aging-report',
//                                                'checked' => '',
//                                                'hidden' => '',
//                                                'link' => '/finance/report/arfaging'
//                                            ),
                                            array(
                                                'text' => 'General Journal Report',
                                                'leaf' => true,
                                                'id' => 'finance-general-journal-report',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/generaljournal'

                                            ),
                                            array(
                                                    'text' => 'Journal Report',
                                                    'leaf' => true,
                                                    'id' => 'finance-journal-report',
                                                    'checked' => '',
                                                    'hidden' => '',
                                                    'link' => '/finance/report/journal'

                                            ),
                                            array(
                                                'text' => 'Detail Journal Report',
                                                'leaf' => true,
                                                'id' => 'finance-detail-journal-report',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/detail-journal'

                                            ),
                                            array(
                                                'text' => 'General Ledger Detail Report',
                                                'leaf' => true,
                                                'id' => 'finance-general-ledger-detail-report',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/general-ledger-detail'

                                            ),
                                            array(
                                                    'text' => 'Kas Bank Report',
                                                    'leaf' => true,
                                                    'id' => 'kas-bank-report',
                                                    'checked' => '',
                                                    'hidden' => '',
                                                    'link' => '/finance/report/kas-bank'

                                            ),array(
                                                    'text' => 'Depreciation Fixed Asset Report',
                                                    'leaf' => true,
                                                    'id' => 'deprectiaon-fixed-asset-report',
                                                    'checked' => '',
                                                    'hidden' => '',
                                                    'link' => '/finance/report/depreciationasset'
                                            ),array(
                                                'text' => 'ARF Aging',
                                                'leaf' => true,
                                                'id' => 'finance-arf-aging-report',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/arf-aging'
                                            ),array(
                                                'text' => 'RPI Aging',
                                                'leaf' => true,
                                                'id' => 'finance-rpi-aging-report',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/rpi-aging'
                                            ),array(
                                                'text' => 'REM Aging',
                                                'leaf' => true,
                                                'id' => 'finance-rem-aging-report',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/rem-aging'
                                            ),array(
                                                'text' => 'Debit Note Aged Receivables',
                                                'leaf' => true,
                                                'id' => 'debit-note-aging-report',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/agingreport'
                                            ),array(
                                                'text' => 'Invoice Aging',
                                                'leaf' => true,
                                                'id' => 'finance-invoice-aging-report',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/invoice-aging'
                                            ),array(
                                                'text' => 'AP Report',
                                                'leaf' => true,
                                                'id' => 'finance-ap-report',
                                                'checked' => '',
                                                'hidden' => '',
                                                'link' => '/finance/report/ap'
                                            )
                                    )
                                ),
        						array(
        							'text' => 'Transactions',
    								'children' => array (
                                                    array (
                                                        'text' => 'Master Periode',
                                                        'leaf' => true,
                                                        'id' => 'finance-master-periode',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/periode/financeperiode'
                                                    ),
                                                    array (
                                                        'text' => 'Master Bank',
                                                        'leaf' => true,
                                                        'id' => 'finance-master-bank',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/bank/bankmenu'
                                                    ),
                                                    array (
                                                        'text' => 'Master COA',
                                                        'leaf' => true,
                                                        'id' => 'finance-master-coa',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/coa/coamenu'
                                                    ),
                                                    array (
                                                        'text' => 'Master COA - Bank',
                                                        'leaf' => true,
                                                        'id' => 'finance-master-coa-bank',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/coa/coabank'
                                                    ),
                                                    array(
                                                        'text' => 'Cancel PO',
                                                        'leaf' => true,
                                                        'id' => 'cancel-po',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/cancelpo/cancelpomenu'
                                                    ),
                                                    array(
                                                        'text' => 'Invoice',
                                                        'leaf' => true,
                                                        'id' => 'finance-invoice',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/invoice/invoice'
                                                    ),
                                                    array(
                                                        'text' => 'Cancel Invoice',
                                                        'leaf' => true,
                                                        'id' => 'finance-cancel-invoice',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/cancelinvoice/menu'
                                                    ),
                                                    array(
                                                        'text' => 'Bank Payment Voucher',
                                                        'leaf' => true,
                                                        'id' => 'finance-bank-payment-voucher',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/bankpaymentvoucher/paymentvoucher'
                                                    ),
                                                    array(
                                                        'text' => 'Closing',
                                                        'leaf' => true,
                                                        'id' => 'finance-posting-validate',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/postingvalidate/validatemenu'
                                                    ),
                                                    array(
                                                        'text' => 'Bank Transaction',
                                                        'leaf' => true,
                                                        'id' => 'finance-bank-transaction',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/banktransaction/menu'
                                                    ),
                                                    array(
                                                        'text' => 'Petty Cash',
                                                        'leaf' => true,
                                                        'id' => 'finance-petty-cash',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/pettycash/menu'
                                                    ),
                                                    array(
                                                        'text' => 'General Journal',
                                                        'leaf' => true,
                                                        'id' => 'finance-general-journal',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/adjustingjournal/menu'
                                                    ),
                                                    array(
                                                        'text' => 'Layout Balance Sheet',
                                                        'leaf' => true,
                                                        'id' => 'finance-layout-balance-sheet',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/balancesheet/menulayout'
                                                    ),
                                                    array(
                                                        'text' => 'Layout Profit and Loss',
                                                        'leaf' => true,
                                                        'id' => 'finance-layout-profit-loss',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/profitloss/menulayout'
                                                    ),
                                                    array(
                                                        'text' => 'Depreciation Fixed Asset',
                                                        'leaf' => true,
                                                        'id' => 'depreciation',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/depreciationasset/menu'
                                                    ),
                                                    array(
                                                        'text' => 'Master Kategori Fixed Asset',
                                                        'leaf' => true,
                                                        'id' => 'master-kategori-fixed-asset',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/kategoriasset/menu'
                                                    ),array(
                                                        'text' => 'AP',
                                                        'leaf' => true,
                                                        'id' => 'finance-ap',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/ap'
                                                    ),
                                                    array(
                                                        'text' => 'Markup Limit For DOR',
                                                        'leaf' => true,
                                                        'id' => 'finance-markup-dor',
                                                        'checked' => '',
                                                        'hidden' => '',
                                                        'link' => '/finance/markup/markup'
                                                    )
        										)
        							)	
        					);		
        if ($module == '')					
        	return $menu;				
        else
        {
        	unset($menu[$module]);
        	return $menu;		
        }
    }

    public function __name()
    {
        return $this->_name;
    }
    
}
?>
