<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/16/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_CoaController extends Zend_Controller_Action
{
    private $coa;
    private $typecoa;
    private $coabank;
    private $FINANCE;
    private $db;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->coa = new Finance_Models_MasterCoa();
        $this->typecoa = new Finance_Models_MasterTypeCOA();
        $this->coabank = new Finance_Models_MasterCoaBank();
        $models = array(
            "MasterCoa",
            "MasterTypeCOA",
            "MasterCoaBank",
            "AccountingCloseAR"
        );

        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function coamenuAction ()
    {
        
    }

    public function coalistAction ()
    {
        
    }
    public function addcoabankAction ()
    {

    }
    public function getcoaAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 40;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'coa_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        $option = $this->getRequest()->getParam("option");
        $search = $this->getRequest()->getParam("search");
        $query = $this->getRequest()->getParam("query");

        if ($option != '' && $search != '')
            $where = "$option LIKE '%$search%'";

        if ($query != '')
        {
            $jsonQuery = Zend_Json::decode($query);
            $tmp = array();
            foreach ($jsonQuery as $k => $v)
            {
                foreach ($v as $k2 => $v2)
                {
                    $tmp[] = "$k2 = '$v2'";
                }
            }
            if ($where == '')
                $where = implode(" AND ", $tmp);
            else
                $where .= " AND " . implode(" AND ", $tmp);

        }

        $data['data'] = $this->coa->fetchAll($where,array($sort . " " . $dir),$limit,$offset)->toArray();

        $data['total'] = $this->coa->fetchAll($where)->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addcoaAction ()
    {
        
    }

    public function gettypecoaAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data['data'] = $this->typecoa->fetchAll()->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertcoaAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $coa_kode = $this->getRequest()->getParam('coa_kode');
        $coa_nama = $this->getRequest()->getParam('coa_nama');
        $coa_tipe = $this->getRequest()->getParam('coa_tipe');
        $dk = $this->getRequest()->getParam('dk');
        $hd = $this->getRequest()->getParam('hd');
        $level = $this->getRequest()->getParam('level');

        $where = "coa_kode = '$coa_kode'";

        $cek = $this->coa->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry, COA Code has been ready to use");
        }else{

            $insert = array(
                "coa_kode" => $coa_kode,
                "coa_nama" => $coa_nama,
                "tipe" => $coa_tipe,
                "dk" => $dk,
                "hd" => $hd,
                "level" => $level
            );

            $this->coa->insert($insert);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function editcoaAction ()
    {
        
    }

    public function doupdatecoaAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $coa_id = $this->getRequest()->getParam('coa_id');
        $coa_kode = $this->getRequest()->getParam('coa_kode');
        $coa_nama = $this->getRequest()->getParam('coa_nama');
        $coa_tipe = $this->getRequest()->getParam('coa_tipe');
        $dk = $this->getRequest()->getParam('dk');
        $hd = $this->getRequest()->getParam('hd');
        $level = $this->getRequest()->getParam('level');

        $where = "coa_kode = '$coa_kode' AND id != '$coa_id'";

        $cek = $this->coa->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry, COA Code has been ready to use");
        }else{

            $update = array(
                "coa_kode" => $coa_kode,
                "coa_nama" => $coa_nama,
                "tipe" => $coa_tipe,
                "dk" => $dk,
                "hd" => $hd,
                "level" => $level
            );

            $this->coa->update($update,"id = '$coa_id'");

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addtypecoaAction ()
    {
        
    }

    public function getcoatypeAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data['data'] = $this->typecoa->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $data['total'] = $this->typecoa->fetchAll()->count();


        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinserttypecoaAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('coa_type_name');

        $where = "tipe_nama = '$type'";

        $cek = $this->typecoa->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry, COA Type has been ready to use");
        }else{

            $insert = array(
                "tipe_nama" => $type
            );

            $this->typecoa->insert($insert);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function coabankAction()
    {
        
    }

    public function getcoabankAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 40;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->coabank->fetchAll(null,array($sort . " " . $dir),$limit,$offset)->toArray();
        $bank = new Finance_Models_MasterBank();

        foreach($data['data'] as $k => $v)
        {
            $idBank = $v['bank_id'];
            $banks = $bank->fetchRow("id = $idBank");
            $data['data'][$k]['bank_nama'] = $banks['bnk_nama'];
            $data['data'][$k]['bank_noreknama'] = $banks['bnk_noreknama'];
            $data['data'][$k]['bank_norek'] = $banks['bnk_norek'];
        }

        $data['total'] = $this->coabank->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function coabanklistAction()
    {
        
    }

    public function gettranotypeAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $type = $this->getRequest()->getParam("type");
        
        if($type=='R'){
            $sql = "SELECT tra_no as trano FROM master_countertransaksi WHERE statusreceiving = 1 GROUP by tra_no";
        }
        elseif($type='all')
        {
            $sql = "SELECT tra_no as trano FROM master_countertransaksi WHERE statusreceiving = 1 OR statuspayment2 = 1 GROUP by tra_no";
        }
        else{
             $sql = "SELECT tra_no as trano FROM master_countertransaksi WHERE statuspayment2 = 1 GROUP by tra_no";
        }
       

        $tranos = $this->db->query($sql);
        $tranos = $tranos->fetchAll();
        if ($tranos)
        {
            foreach($tranos as $k => $v)
            {
                $pos = strpos($v['trano'],'01');
                if ($pos !== false)
                {
                    $len = strlen($v['trano']);
                    $tranos[$k]['trano'] = substr($v['trano'],0,($len - 2));
                }
            }
        }
        $data['data'] = $tranos;
        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertcoabankAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

       $coa_kode = $this->getRequest()->getParam('coa_kode');
        $coa_nama = $this->getRequest()->getParam('coa_nama');
        $trano = $this->getRequest()->getParam('trano_type');
        $bank_id = $this->getRequest()->getParam('bank_id');
        $val_kode = $this->getRequest()->getParam('val_kode');
        $insert = array(
            "coa_kode" => $coa_kode,
            "coa_nama" => $coa_nama,
            "trano_type" => $trano,
            "bank_id" => $bank_id,
            "val_kode" => $val_kode
        );

        $this->coabank->insert($insert);
        
        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function doupdatecoabankAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $coa_kode = $this->getRequest()->getParam('coa_kode');
        $coa_nama = $this->getRequest()->getParam('coa_nama');
        $trano = $this->getRequest()->getParam('trano_type');
        $bank_id = $this->getRequest()->getParam('bank_id');
        $id = $this->getRequest()->getParam('id_edit');
        $insert = array(
            "coa_kode" => $coa_kode,
            "coa_nama" => $coa_nama,
            "trano_type" => $trano,
            "bank_id" => $bank_id
        );

        $this->coabank->update($insert,"id = $id");

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function editcoabankAction ()
    {

    }

    public function getcoabanktypeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $coas = QDC_Finance_Coa::factory()->getCoaBank(array("type" => $type));

        if ($coas !== false)
        {
            $return = array("success" => true, "data" => $coas);
        }
        else
            $return = array("success" => false, "msg" => "COA Code for this Transaction : $type is not Exist!");

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getcoatransactionAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $coa = $this->getRequest()->getParam("coa");
        $coa = Zend_Json::decode($coa);
        $addInfo = $this->getRequest()->getParam("addInfo");
        $addInfo = Zend_Json::decode($addInfo);

        $resultCoa = array();
        foreach($coa as $k => $v)
        {
            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $v['coa_kode']));
            if ($coas !== false)
            {
                if ($addInfo['tipe'] == 'DELETED')
                    $coas['tipe'] = '';
                $resultCoa[] = QDC_Common_Array::factory()->combine($coas,$v);
            }
        }

        if ($coas !== false)
        {
            $return = array("success" => true, "data" => $resultCoa);
        }
        else
            $return = array("success" => false, "msg" => "COA Code not Exist!");
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }


}