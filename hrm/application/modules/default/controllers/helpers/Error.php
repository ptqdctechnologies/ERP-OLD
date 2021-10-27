<?php
/*
	Created @ Mar 28, 2010 9:37:35 PM
 */

class Zend_Controller_Action_Helper_Error extends
                Zend_Controller_Action_Helper_Abstract
{

    private $db;

    //1xx : general error
    //2xx : database error, transaction not exist, dll
    //3xx : workflow error, not authorized, dll
    //4xx : ....

    private $error_definition = array (
        100 => "General Error, Please contact IT Support!",
        200 => "Database Error, Please contact IT Support!",
        201 => "No such Transaction Number exist!",
        300 => "You are not assigned to this workflow!<br><br>Please contact IT Support!",
        301 => "Edit is Prohibited, This document is <b><font color=#ff0000>not rejected</font></b> yet!",
        302 => "You are <b><font color=#ff0000>not allowed</font></b> to Edit & Re-Submit this item!<br><br>Please contact IT Support!",
        303 => "You are <b><font color=#ff0000>not allowed</font></b> to Print out this item!<br><br>This Document still on Process (No Final Approval or Executed yet).",
        304 => "You are <b><font color=#ff0000>not allowed</font></b> to Review this item!<br>Reason: Not Assigned to Workflow for this Document.<br><br>Please contact IT Support!",
        305 => "Resubmit is Prohibited, This document <b><font color=#ff0000>already final approval / executed</font></b>!",
        306 => "Please submit this document into workflow!<br>Reason: Document is <b><font color=#ff0000>not in workflow yet</font></b>.",
        307 => "You are <b><font color=#ff0000>not allowed</font></b> to create transaction from this document!<br><br>This Document still on Process (No Final Approval or Executed yet).",
        308 => "You are <b><font color=#ff0000>not allowed</font></b> to Submit this item!<br>Your Next Person Not Registered in Workflow.<br>Please contact IT Support!",
    );

    function  __construct() {
        $this->db = Zend_Registry::get('db');
    }

    function getErrorMsg($error_kode)
    {
        if (array_key_exists($error_kode, $this->error_definition))
        {
            return $this->error_definition[$error_kode];
        }
        else
            return $this->error_definition[100];
    }
}
?>