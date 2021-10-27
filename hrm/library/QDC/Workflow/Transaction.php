<?php

Class QDC_Workflow_Transaction
{
    private $ADMIN;
    private $const;
    private $db;

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

    public function getDocumentLastStatusByApproval($docsID='')
    {
        $select = $this->db->select()
            ->from(array($this->ADMIN->Workflowtrans->__name()),array("approval" => "MAX(approve)"))
            ->where("item_id = '$docsID'");

        $fetch = $this->db->fetchRow($select);

        return $fetch['approval'];
    }

    public function isDocumentFinal($trano='')
    {
        if ($trano == '')
            return false;
        if (!$this->isDocumentInWorkflow($trano))
            return true;

        $cekStatus = $this->getDocumentLastStatusByApproval($trano);
        if ($cekStatus == $this->const['DOCUMENT_FINAL'] || $cekStatus == $this->const['DOCUMENT_EXECUTE'])
            return true;
        else
            return false;
    }
}
?>