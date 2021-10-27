<?php

class Default_Models_SubMenu extends Zend_Db_Table_Abstract
{
	private $db;
	
    protected $_name = 'submenu_privilege';

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }
    
    public function getSubMenu($module='')
    {
    	$menu['procurement'] = array(
        						
        					array('text' => 'Transactions',
    								'children' => array (
                                                                                               array(
        												'text' => 'Procurement Request',
												        'id' => 'procurement-trans-pr',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'pr_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'pr_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Overhead',
                                                                'leaf' => true,
                                                                'id' => 'pr_edit_overhead_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Sales',
                                                                'leaf' => true,
                                                                'id' => 'pr_edit_sales_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                      ),
                                                                                                array(
        												'text' => 'Purchase Order',
												        'id' => 'procurement-trans-po',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'po_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Create Overhead',
                                                                'leaf' => true,
                                                                'id' => 'po_add_overhead_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Create Sales',
                                                                'leaf' => true,
                                                                'id' => 'po_add_sales_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'po_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Overhead',
                                                                'leaf' => true,
                                                                'id' => 'po_edit_overhead_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Sales',
                                                                'leaf' => true,
                                                                'id' => 'po_edit_sales_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                     ),
                                                                                                array(
        												'text' => 'Request Payment for Invoice',
												        'id' => 'procurement-trans-rpi',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'rpi_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Create Overhead',
                                                                'leaf' => true,
                                                                'id' => 'rpi_add_overhead_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Create Sales',
                                                                'leaf' => true,
                                                                'id' => 'rpi_add_sales_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'rpi_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Overhead',
                                                                'leaf' => true,
                                                                'id' => 'rpi_edit_overhead_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Sales',
                                                                'leaf' => true,
                                                                'id' => 'rpi_edit_sales_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Payment',
                                                                'leaf' => true,
                                                                'id' => 'rpi_payment_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Payment',
                                                                'leaf' => true,
                                                                'id' => 'rpi_edit_payment_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                     ),
                                                                                                array(
                                                        'text' => 'Advance Request Form',
                                                        'id' => 'procurement-trans-arf',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'rpi_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'rpi_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Overhead',
                                                                'leaf' => true,
                                                                'id' => 'rpi_edit_overhead_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Sales',
                                                                'leaf' => true,
                                                                'id' => 'rpi_edit_sales_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Payment',
                                                                'leaf' => true,
                                                                'id' => 'rpi_payment_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Payment',
                                                                'leaf' => true,
                                                                'id' => 'rpi_edit_payment_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                     ),
                                                                                                array(
        												'text' => 'Advance Settlement Form',
												        'id' => 'procurement-trans-asf',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'asf_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Create Overhead',
                                                                'leaf' => true,
                                                                'id' => 'asf_add_overhead_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Create Sales',
                                                                'leaf' => true,
                                                                'id' => 'asf_add_sales_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'asf_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Overhead',
                                                                'leaf' => true,
                                                                'id' => 'asf_edit_overhead_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Sales',
                                                                'leaf' => true,
                                                                'id' => 'asf_edit_sales_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Settled',
                                                                'leaf' => true,
                                                                'id' => 'asf_settled_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Settled',
                                                                'leaf' => true,
                                                                'id' => 'asf_edit_settled_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Settled Cancel',
                                                                'leaf' => true,
                                                                'id' => 'asf_settledcancel_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit Settled Cancel',
                                                                'leaf' => true,
                                                                'id' => 'asf_edit_settledcancel_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                            )
                                                                                                     ),
                                                        array(
        												'text' => 'Progress Piece Meal',
												        'id' => 'procurement-trans-pmeal',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'pmeal_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'pmeal_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                     ),

                                                        array(
        												'text' => 'Reimbursable Expenditure',
												        'id' => 'procurement-trans-reimburs',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'reimburs_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'reimburs_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                     )
                                                        )


                                                     )
        				  
        			);
        
        $menu['projectmanagement'] = array(
        						
        						array('text' => 'Transactions',
    								'children' => array (
				        						array(
				        							'text' => 'Budget',
				    								'children' => array (
				        											array(
				        												'text' => 'Project Budget',
																        'id' => 'projectmanagement-trans-projectbudget',
                                                                        'children' => array(
                                                                            array(
                                                                                'text' => 'Create Project',
                                                                                'leaf' => true,
                                                                                'id' => 'budget_add_project_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            ),
                                                                            array(
                                                                                'text' => 'Create Site',
                                                                                'leaf' => true,
                                                                                'id' => 'budget_add_site_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            ),
                                                                            array(
                                                                                'text' => 'Create',
                                                                                'leaf' => true,
                                                                                'id' => 'budget_add_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            ),
                                                                            array(
                                                                                'text' => 'Create Overhead',
                                                                                'leaf' => true,
                                                                                'id' => 'budget_add_overhead_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            ),
                                                                            array(
                                                                                'text' => 'Edit',
                                                                                'leaf' => true,
                                                                                'id' => 'budget_edit_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            )
                                                                        )
				        												),
                                                                    array(
                                                                    'text' => 'Approval For Expenditure',
                                                                    'id' => 'projectmanagement-trans-afe',
                                                                    'children' => array(
                                                                        array(
                                                                            'text' => 'Create',
                                                                            'leaf' => true,
                                                                            'id' => 'afe_add_button',
                                                                            'checked' => '',
                                                                            'hidden' => ''
                                                                        ),
                                                                        array(
                                                                            'text' => 'Edit',
                                                                            'leaf' => true,
                                                                            'id' => 'afe_edit_button',
                                                                            'checked' => '',
                                                                            'hidden' => ''
                                                                        )
                                                                    )
                                                                     ),
                                                                    array(
				        												'text' => 'Transfer From AFE To Budget',
																        'id' => 'projectmanagement-trans-projectcboq3',
                                                                        'children' => array(
                                                                            array(
                                                                                'text' => 'Create',
                                                                                'leaf' => true,
                                                                                'id' => 'cboq_add_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            )
                                                                        )
                                                                    ),
                                                                    array(
				        												'text' => 'Register Customer PO',
																        'id' => 'projectmanagement-trans-projectpraco',
                                                                        'children' => array(
                                                                            array(
                                                                                'text' => 'Create',
                                                                                'leaf' => true,
                                                                                'id' => 'praco_add_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            ),
                                                                            array(
                                                                                'text' => 'Edit',
                                                                                'leaf' => true,
                                                                                'id' => 'praco_edit_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            ),
                                                                            array(
                                                                                'text' => 'Pending List',
                                                                                'leaf' => true,
                                                                                'id' => 'praco_pending_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            )
                                                                        )
                                                                    )
				        										)
				        							),
//				        						array(
//				        							'text' => 'Timesheet',
//				    								'children' => array (
				        											array(
				        												'text' => 'Timesheet',
																        'id' => 'projectmanagement-trans-timesheet',
                                                                        'children' => array(
                                                                            array(
                                                                                'text' => 'Create',
                                                                                'leaf' => true,
                                                                                'id' => 'timesheet_add_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            ),
                                                                            array(
                                                                                'text' => 'Create Draft',
                                                                                'leaf' => true,
                                                                                'id' => 'timesheet_add_draft_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            ),
                                                                            array(
                                                                                'text' => 'Edit',
                                                                                'leaf' => true,
                                                                                'id' => 'timesheet_edit_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            )
                                                                        )
//				        												)
//				        										)
				        							),
                                                    array(
                                                        'text' => 'Project Progress',
                                                        'id' => 'projectmanagement-trans-progress',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'progress_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'progress_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                    ),array(
                                                        'text' => 'Master Engineer Work',
                                                        'id' => 'projectmanagement-engineer-work',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'masterwork_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'masterwork_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                    )
	        								)
        							)
        					);			
//        $menu['admin'] = array(
//        						array(
//        							'text' => 'Transaction',
//    								'children' => array (
////                                                    array(
////        												'text' => 'Master Supplier',
////												        'leaf' => true,
////												        'id' => 'admin-trans-supp',
////        												'link' => '/logistic/logistic/supplier',
////                                                        'checked' => '',
////                                                        'hidden' => ''
////                                                    ),
//        											array(
//        												'text' => 'AFE Non-Workflow',
//												        'id' => 'admin-afe-nonworkflow'
//        												),
//                                                    array(
//        												'text' => 'Transfer From AFE to Budget Non-Workflow',
//												        'id' => 'admin-cboq3-nonworkflow'
//        												),
//                                                    array(
//                                                        'text' => 'Project Budget Input',
//                                                        'id' => 'admin-trans-projectbudget'
//                                                        )
//        										)
//        							),
//
//                                array(
//        							'text' => 'Workflow',
//    								'children' => array (
//        											array(
//        												'text' => 'Workflow',
//												        'id' => 'admin-workflow'
//        												),
//                                                    array(
//        												'text' => 'Document',
//												        'id' => 'admin-document'
//                                                        )
//        										)
//        							),
//        						array(
//        							'text' => 'User',
//    								'children' => array (
//        											array(
//        												'text' => 'User Management',
//												        'id' => 'admin-user'
//        												),
//        											array(
//        												'text' => 'User Role',
//												        'id' => 'admin-userrole'
//        												),
//        											array(
//        												'text' => 'Personal Assistant',
//												        'id' => 'admin-pa'
//        												)
//        										)
//        							),
//        						array(
//        							'text' => 'Menu',
//    								'children' => array (
//        											array(
//        												'text' => 'Menu Management',
//												        'id' => 'admin-menu'
//        												)
//        										)
//        							)
//        					);
        $menu['sales'] = array(
        						array(
        							'text' => 'Transactions',
    								'children' => array (
        											array(
        												'text' => 'Customer Order',
												        'id' => 'sales-trans-co',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Input',
                                                                'leaf' => true,
                                                                'id' => 'co_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Additional',
                                                                'leaf' => true,
                                                                'id' => 'co_additional_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
        												)
        											
        										)
        							)	
        					); 			
        $menu['logistic'] = array(
        						array(
        							'text' => 'Transactions',
    								'children' => array (
        										array(
        												'text' => 'Delivery Order Request',
												        'id' => 'delivery-trans-dor',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'dor_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'dor_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
        												
                                                                                                      ),
                                                array(
        												'text' => 'Delivery Order',
												        'id' => 'delivery-trans-do',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'do_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'do_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
        												
                                                                                                      ),
                                                array(
        												'text' => 'Material Return',
												        'id' => 'delivery-trans-ret',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'return_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'return_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
        												
                                                                                                      ),
                                                array(
        												'text' => 'Material Cancel',
												        'id' => 'delivery-trans-can',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'cancel_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'cancel_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
        												
                                                                                                      ),
                                                array(
        												'text' => 'i-Supp',
												        'id' => 'delivery-trans-isupp',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'isupp_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'isupp_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                      ),

                                                array(
        												'text' => 'Master Supplier',
												        'id' => 'delivery-trans-supp',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'supplier_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'supplier_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                      ),
                                                array(
        												'text' => 'Master List Material',
												        'id' => 'delivery-trans-mat',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'material_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'material_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                      ),
                                                array(
        												'text' => 'Master Satuan',
												        'id' => 'delivery-trans-sat',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'satuan_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'satuan_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                      ),
                                                array(
        												'text' => 'Master Customer',
												        'id' => 'delivery-trans-cus',
                                                        'children' => array(
                                                            array(
                                                                'text' => 'Create',
                                                                'leaf' => true,
                                                                'id' => 'customer_add_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            ),
                                                            array(
                                                                'text' => 'Edit',
                                                                'leaf' => true,
                                                                'id' => 'customer_edit_button',
                                                                'checked' => '',
                                                                'hidden' => ''
                                                            )
                                                        )
                                                                                                      ),
        										)
        							)	
        					);		
//        $menu['projectstaff'] = array(
//        						array(
//        							'text' => 'Transactions',
//    								'children' => array (
//
//        										)
//        							)
//        					);
        $menu['hr'] = array(
        						array(
        							'text' => 'Transactions',
    								'children' => array (
        											array(
                                                                        'text' => 'Overhead Project',
                                                                        'id' => 'hr-ohp',
                                                                        'children' => array(
                                                                            array(
                                                                                'text' => 'Create',
                                                                                'leaf' => true,
                                                                                'id' => 'ohp_add_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            ),
                                                                            array(
                                                                                'text' => 'Edit',
                                                                                'leaf' => true,
                                                                                'id' => 'ohp_edit_button',
                                                                                'checked' => '',
                                                                                'hidden' => ''
                                                                            )
                                                                        )
                                                                        )
        										)
        							)	
        					);	
//        $menu['finance'] = array(
//
//        						array(
//        							'text' => 'Transactions',
//    								'children' => array (
//
//        										)
//        							)
//        					);
        if ($module == '')					
        	return $menu;				
        else
        {
        	unset($menu[$module]);
        	return $menu;		
        }
    }
    
}
?>
