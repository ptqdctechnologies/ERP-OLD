<?php

class Default_Models_DownloadedFiles extends Zend_Db_Table_Abstract {

    protected $_name = 'downloaded_files_log';
    protected $_primary = 'id';

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function getFilesWithFilter($where) {

        if (!$where)
            $where = null;

        $files = $this->fetchAll($where)->toArray();
        
        return $files;
        
    }

}

?>