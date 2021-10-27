<?php
/* 
Created @ Mar 23, 2010 8:45:40 AM
 */

class ChartController extends Zend_Controller_Action
{
    private $chart;
    private $db;

    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->leadHelper = $this->_helper->getHelper('chart');

        $session = new Zend_Session_Namespace('login');
    }

     public function getchartAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       $request = $this->getRequest();

       $chartType = $request->getParam('data');
       if ($chartType == 'budgetByProject')
       {
           $sql = "SELECT
                    ProjectCode,
                    ProjectName,
                    sites,
                    (poIDRHeader+arfIDRHeader) as poarf,
                    budgetIDRHeader,
                    (budgetIDRHeader-poIDRHeader-arfIDRHeader) as balance
                   FROM budget
                   WHERE budgetIDRHeader<>0";

            $fetch = $this->db->query($sql);
            $return = $fetch->fetchAll();
            $data = array();
            error_reporting(0);
            for ($i = 0;$i<count($return);$i++)
            {
                foreach ($return[$i] as $key => $value)
                {
                    $nama_key = $return[$i]['ProjectCode'] ;//. '-' . $return[$i]['ProjectName'];
                    $data['categories'][$i] = $nama_key;
//                    if ($key == 'poIDRHeader')
//                    {
//                        $data['PO'][$i] =  $return[$i]['poIDRHeader'];
//                    }
//                    elseif ($key == 'arfIDRHeader')
//                    {
//                        $data['ARF'][$i] =  $return[$i]['arfIDRHeader'];
//                    }
                    if ($key == 'poarf')
                    {
                        $data['PO+ARF'][$i] =  $return[$i]['poarf'];
                    }
                    elseif ($key == 'budgetIDRHeader')
                    {
                        $data['Budget'][$i] =  $return[$i]['budgetIDRHeader'];
                    }
//                    elseif ($key == 'balance')
//                    {
//                        $data['Balance'][$i] =  $return[$i]['balance'];
//                    }
                }
            }
            error_reporting(E_ALL ^ E_NOTICE);
            $graphArray = array('caption' => 'Budget by Project',
                                'xaxisname' => 'Project',
                                'yaxisname' => 'a',
                                'formatNumberScale' => '1',
                                'rotateNames' => '1',
                                'decimalPrecision' => '0',
                                'numberPrefix' => 'Rp.');
            $xml= $this->leadHelper->buildChart2($data,$graphArray);
       }
       else
       {
           
       }
        $this->getResponse()->setHeader('Content-Type', 'text/xml');
        $this->getResponse()->setBody($xml);


   }

}
?>
