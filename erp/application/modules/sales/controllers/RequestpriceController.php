<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 2/28/12
 * Time: 10:05 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Sales_RequestpriceController extends Zend_Controller_Action
{
    private $upload;
    private $phpexcel;
    private $session;
    private $db;
    private $counter;
    private $request_price;
    private $history;

    public function init()
    {
        $this->upload = $this->_helper->getHelper('uploadfile');
        $this->phpexcel = $this->_helper->getHelper('phpexcel');
        $this->session = new Zend_Session_Namespace('login');
        $this->db = Zend_Registry::get('db');
        $this->counter = new Default_Models_MasterCounter();
        $this->request_price = new Sales_Models_RequestPrice();
        $this->history = new Sales_Models_HistoryRequestPrice();
    }

    public function menuAction ()
    {
        
    }

    public function insertrequestpriceAction ()
    {
        
    }

    public function uploadrequestpriceAction ()
    {
        $this->_helper->viewRenderer->setNoRender();

		$result = $this->upload->uploadFile($_FILES,'file-path');

		if ($result)
		{
			Zend_Loader::loadClass('Zend_Json');
			$boq3 = $this->phpexcel->readrequestprice($result['save_file'],$result['id_file']);
            $json =  Zend_Json::encode($boq3);
			$fields = array(array("name" => "id","type" => "string"),array("name" => "nama_barang","type" => "string"),array("name" => "spec"));

			$fields =  Zend_Json::encode($fields);
			if (file_exists($result['save_file']))
			{
				unlink($result['save_file']);
			}
//			echo "{success:true}";
			echo "{success:true, file:\"" . $result['origin_name'] . "\", newfile:\"" . $result['origin_name'] . "\",RESULT:{\"posts\" : $json,fields:$fields }}";
		}
		else
			echo "{success:false}";
    }

    public function doinsertrequestpriceAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $requestdata = Zend_Json::decode($this->getRequest()->getParam('posts'));

        $type = 'REP';
        $trano = $this->counter->setNewTrans($type);
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        foreach($requestdata as $key => $val)
        {
            $insert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                'uid' => $uid,
                'nama_brg' => $val['nama_barang'],
                'spec' => $val['spec']
            );

            $this->request_price->insert($insert);
        }

        $json = "{success: true,number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatemenuAction()
    {
        
    }

    public function getrequestpriceAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $all = $this->getRequest()->getParam('all');
        $trano = $this->getRequest()->getParam('trano');

        if ($textsearch != null || $textsearch != '')
        {
            $search = "$option LIKE '%$textsearch%'";
        }

        if ($trano != null || $trano != '')
        {
            if ($search)
                $search .= " AND ";
            $search = $search . "trano = '$trano'";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 10;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        $select = $this->db->select()
                ->from(
                    array("sales_requestprice"),
                    array(
                        new Zend_Db_Expr("SQL_CALC_FOUND_ROWS id"),
                        "trano",
                        "uid",
                        "tgl",
                        "nama_brg",
                        "spec",
                        "uom",
                        "fraco",
//                        "sup_kode",
                        "sup_nama",
                        "alamat",
                        "master_kota",
                        "tlp",
                        "fax",
                        "email",
                        "val_kode",
                        "price",
                        "contact"
                    )
                )
               ->order(array($sort . ' ' . $dir));

        if ($search)
            $select = $select->where($search);
        if ($all == null)
        {
            $select = $select
               ->group(array("trano"))
               ->limit($limit,$offset);
        }

        $data['data'] = $this->db->fetchAll($select);
        $data['total'] = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewrequestAction ()
    {
        $trano = $this->getRequest()->getParam('trano');

        $this->view->trano = $trano;
    }

    public function editrequestAction ()
    {
        $id = $this->getRequest()->getParam('id');

        $request = $this->request_price->fetchRow("id = '$id'");

        $this->view->nama_brg = $request['nama_brg'];
        $this->view->spec = $request['spec'];
        $this->view->id = $id;
        $this->view->trano = $request['trano'];
        $this->view->uom = $request['uom'];
        $this->view->fraco = $request['fraco'];
//        $this->view->sup_kode = $request['sup_kode'];
        $this->view->sup_nama = $request['sup_nama'];
        $this->view->alamat = $request['alamat'];
        $this->view->master_kota = $request['master_kota'];
        $this->view->tlp = $request['tlp'];
        $this->view->fax = $request['fax'];
        $this->view->email = $request['email'];
        $this->view->val_kode = $request['val_kode'];
        $this->view->price = $request['price'];
        $this->view->contact = $request['contact'];

    }

    public function doupdaterequestpriceAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');
        $trano = $this->getRequest()->getParam('trano');
        $nama_brg = $this->getRequest()->getParam('nama_brg');
        $spec = $this->getRequest()->getParam('spec');
        $uom = $this->getRequest()->getParam('uom');
        $fraco = $this->getRequest()->getParam('fraco');
        $valuta = $this->getRequest()->getParam('valuta');
        $price = str_replace(",","",$this->getRequest()->getParam('price'));
//        $sup_kode = $this->getRequest()->getParam('text_sup_kode');
        $sup_nama = $this->getRequest()->getParam('sup_nama');
        $sup_add = $this->getRequest()->getParam('sup_add');
        $sup_city = $this->getRequest()->getParam('sup_city');
        $sup_phone = $this->getRequest()->getParam('sup_phone');
        $sup_fax = $this->getRequest()->getParam('sup_fax');
        $sup_email = $this->getRequest()->getParam('sup_email');
        $contact = $this->getRequest()->getParam('contact');
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        $update = array(

            "uom" => $uom,
            "fraco" => $fraco,
//            "sup_kode" => $sup_kode,
            "sup_nama" => $sup_nama,
            "alamat" => $sup_add,
            "tlp" => $sup_phone,
            "fax" => $sup_fax,
            "email" => $sup_email,
            "master_kota" => $sup_city,
            "val_kode" => $valuta,
            "price" => $price,
            "date_update" => $tgl,
            "uid_update" => $uid,
            "contact" => $contact
            
        );

        $this->request_price->update($update,"id = '$id'");

        $request = $this->request_price->fetchRow("id = '$id'");

        $inserthistory = array(
            "trano" => $trano,
            "id_request" => $id,
            "tgl" => $request['tgl'],
            "uid" => $request['uid'],
            "nama_brg" => $nama_brg,
            "spec" => $spec,
            "uom" => $uom,
            "fraco" => $fraco,
//            "sup_kode" => $sup_kode,
            "sup_nama" => $sup_nama,
            "alamat" => $sup_add,
            "tlp" => $sup_phone,
            "fax" => $sup_fax,
            "email" => $sup_email,
            "master_kota" => $sup_city,
            "val_kode" => $valuta,
            "price" => $price,
            "date_update" => $tgl,
            "uid_update" => $uid,
            "contact" => $contact
        );

        $this->history->insert($inserthistory);
        
//
//        var_dump($id,$trano);die;

        $data = array("success" => true);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function reportmenuAction ()
    {
        
    }

    public function viewreportrequestAction ()
    {
        $trano = $this->getRequest()->getParam('trano');

        $data = $this->request_price->fetchAll("trano = '$trano'")->toArray();

//        var_dump($data);die;

        $this->view->data = $data;
    }
}