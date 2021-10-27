<?php

class Zend_View_Helper_PagingDefault extends Zend_View_Helper_Abstract {

    public function pagingDefault($pageUrl='',$totalResult=0,$limit=0,$current=1,$currentPage=1,$params='')
    {
		$this->view->pageUrl = $pageUrl;
        $this->view->totalResult = $totalResult;
        $this->view->current = $current;
        $this->view->limit = $limit;
        $this->view->currentPage = $currentPage;
        $this->view->totalPage = ceil($totalResult / $limit);
        $this->view->param = $params;
        return $this->view->render("paging-default.phtml");
    }
}
?>
