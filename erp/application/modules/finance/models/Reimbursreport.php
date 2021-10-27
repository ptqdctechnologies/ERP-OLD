<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/10/11
 * Time: 3:01 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_Reimbursreport extends Zend_Db_Table_Abstract
{
    private $db;
	private $project;
    function  __construct() {
        $this->db = Zend_Registry::get('db');
        $this->project = Zend_Controller_Action_HelperBroker::getStaticHelper('project');
    }

    function getReimbursReport ($prj_kode='',$sit_kode='')
    {
        $sp = $this->db->prepare("call sp_reimburs('$prj_kode','$sit_kode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();

        return $result;
    }
}