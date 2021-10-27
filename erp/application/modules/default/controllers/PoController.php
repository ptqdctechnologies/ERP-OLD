<?php

/* 
	Created @ April 09, 2010 by Haryadi
 */

class PoController extends Zend_Controller_Action
{
	private $transaction;
	
	public function init()
	{
		 $this->transaction = $this->_helper->getHelper('transaction');
		
	}
	
    public function indexAction() 
    {
        // TODO Auto-generated {0}::indexAction() default action
    }
    
    public function podAction()
    {
      
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = $this->transaction->getPod($prjKode,$sitKode);
                
        $this->view->result = $result;

    }
    
    public function podtransAction()
    {
      
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $trano = $request->getParam('trano');

        $result = $this->transaction->getPodtrans($prjKode,$sitKode,$trano);
                
        $this->view->result = $result;
    }
    
}

?>
