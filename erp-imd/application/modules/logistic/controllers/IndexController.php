<?php

class Logistic_IndexController extends Zend_Controller_Action
{

    private $userName;

    public function init()
    {
        /* Initialize action controller here */
        $session = new Zend_Session_Namespace('login');


    }

    public function indexAction()
    {
    	
    }

    
     public function menuAction()
    {
    
    }
     
public function getchartAction()
   {
       $this->_helper->viewRenderer->setNoRender();
       $request = $this->getRequest();

       $chartType = $request->getParam('data');
       if (isset($chartType))
       {
           $sql = "SELECT SUM(a.total) AS total,a.tgl FROM procurement_poh a WHERE a.prj_kode='$chartType' GROUP BY CONCAT(a.tgl);";

           $fetch = $this->db->query($sql);
            $return = $fetch->fetchAll();
            $data = array();
            error_reporting(0);
            for ($i = 0;$i<count($return);$i++)
            {
                foreach ($return[$i] as $key => $value)
                {
                    $nama_key = $return[$i]['tgl'];
                    $data[$nama_key]= ceil($return[$i]['total']);
                }
            }

            error_reporting(E_ALL ^ E_NOTICE);
            $xml= $this->leadHelper->buildChart($data,'Detail Nilai Project','Tgl','Nilai Project');
       }
       else
       {
           $sql = "SELECT SUM(a.total) AS total,a.prj_kode,b.prj_nama FROM procurement_poh a LEFT JOIN master_project b on a.prj_kode = b.prj_kode GROUP BY prj_kode;";

           $fetch = $this->db->query($sql);
            $return = $fetch->fetchAll();
            $data = array();
            error_reporting(0);
            for ($i = 0;$i<count($return);$i++)
            {
                foreach ($return[$i] as $key => $value)
                {
                    $nama_key = $return[$i]['prj_kode'] . "-" . $return[$i]['prj_nama'];
                    $data[$nama_key]= ceil($return[$i]['total']);
                }
            }

            error_reporting(E_ALL ^ E_NOTICE);
            $xml= $this->leadHelper->buildChart($data,'Total Nilai Project','Project Name','Nilai Project','JavaScript:showDrillDown',true);
       }
        $this->getResponse()->setHeader('Content-Type', 'text/xml');
        $this->getResponse()->setBody($xml);

     
   }

    public function pohAction()
    {
        
    }
}

