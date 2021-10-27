<?php

Class QDC_Workflow_Transaction
{
    private $ADMIN;
    private $const;
    private $db;

    protected $approvalStatus;
    protected $approvalUid;

    public function __construct($params='')
    {
        $models = array(
            "Workflowtrans"
        );
        $this->ADMIN = QDC_Model_Admin::init($models);
        $this->const = Zend_Registry::get('constant');
        $this->db = Zend_Registry::get('db');
    }

    public static function factory($params=array())
    {
        return new self($params);
    }

    public function isDocumentInWorkflow($DocsId='')
    {
        $hasil = $this->ADMIN->Workflowtrans->fetchRow("item_id = '$DocsId'");
        if (!$hasil)
            return false;
        else
            return true;
    }

    public function getDocumentLastStatusByApproval($docsID='',$returnORM=false)
    {
        $select = $this->db->select()
            ->from(array($this->ADMIN->Workflowtrans->__name()),array("approve", "uid"))
            ->where("item_id = '$docsID'")
            ->order(array("date DESC"));

        $fetch = $this->db->fetchRow($select);

        if (!$returnORM)
            return $fetch['approve'];
        else
        {
            $this->approvalStatus = $fetch['approve'];
            $this->approvalUid = $fetch['uid'];
            return $this;
        }
    }

    public function getDocumentLastDateByApproval($docsID='',$returnORM=false)
    {
        $select = $this->db->select()
            ->from(array($this->ADMIN->Workflowtrans->__name()),array("approve", "uid","date"))
            ->where("item_id = '$docsID'")
            ->order(array("date DESC"));

        $fetch = $this->db->fetchRow($select);

        if (!$returnORM)
            return $fetch['date'];
    }
    
    public function getApprovalStatus($useName=false)
    {
        if ($this->approvalStatus)
        {
            if ($useName)
                $name = " : " . QDC_User_Ldap::factory(array("uid" => $this->approvalUid))->getName();
            else
                $name = '';
            switch($this->approvalStatus)
            {
                case $this->const['DOCUMENT_FINAL']:
                    return 'Final Approval' . $name;
                break;
                case $this->const['DOCUMENT_REJECT']:
                    return 'Rejected' . $name;
                break;
                case $this->const['DOCUMENT_APPROVE']:
                    return 'Approved' . $name;
                break;
                case $this->const['DOCUMENT_SUBMIT']:
                    return 'Submitted' . $name;
                break;
                case $this->const['DOCUMENT_RESUBMIT']:
                    return 'Re-Submitted' . $name;
                break;
            }
        }
    }

    public function isDocumentFinal($trano='')
    {
        if ($trano == '')
            return false;
        if (!$this->isDocumentInWorkflow($trano))
            return false;

        $cekStatus = $this->getDocumentLastStatusByApproval($trano);
        if ($cekStatus == $this->const['DOCUMENT_FINAL'] || $cekStatus == $this->const['DOCUMENT_EXECUTE'])
            return true;
        else
            return false;
    }

    public function isDocumentReject($trano='')
    {
        if ($trano == '')
            return false;
        if (!$this->isDocumentInWorkflow($trano))
            return true;

        $cekStatus = $this->getDocumentLastStatusByApproval($trano);
        if ($cekStatus == $this->const['DOCUMENT_REJECT'])
            return true;
        else
            return false;
    }
    
    public function isDocumentExpired($trano='')
    {
        if ($trano == '')
            return false;
        if (!$this->isDocumentInWorkflow($trano))
            return true;

        $today = date("Y-m-d H:i:s");
        $date=$this->getDocumentLastDateByApproval($trano);
        $time = ((abs(strtotime ($today) - strtotime ($date)))/(60*60*24));
        
        
        $cekStatus = $this->getDocumentLastStatusByApproval($trano);
        if (($cekStatus == $this->const['DOCUMENT_REJECT']) && $time > 90){
            return true;
        }else{
            return false;
        }
        
    }
}
?>
