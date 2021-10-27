<?php

class LogRejectController extends Zend_Controller_Action
{
    private $db;

    public function init()
    {
        $this->db = Zend_Registry::get("db");
    }

    public function getDateAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->_getParam("trano");

        $logR = new Default_Models_LogTransactionReject();
        $select = $this->db->select()
            ->from(array($logR->__name()),array(
                "id",
                "tgl",
                "uid_reject"
            ))
            ->where("trano = '$trano'")
            ->order(array("tgl DESC"));
        $counter = new Default_Models_MasterCounter();
        $itemType = $counter->getTransTypeFlip($trano);

        $arrayItemTypeSpec = array("PO","POO");

        $data = $this->db->fetchAll($select);
        $arrayData = array();
        if ($data)
        {
            $no = 1;
            foreach($data as $k => $v)
            {
                $reconfigure = false;
                if (in_array($itemType,$arrayItemTypeSpec) !== false)
                {
                    $reconfigure = true;
                }
                $arrayData[] = array(
                    "id" => $v['id'],
                    "name" => $no . " - " . date("d-m-Y H:i"),
                    "uid_reject" => QDC_User_Ldap::factory(array("uid" => $v['uid_reject']))->getName(),
                    "reconfigure" => $reconfigure,
                    "item_type" => $itemType
                );
            }
        }
        $json = Zend_Json::encode(array("data" => $arrayData));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->_getParam("trano");
        $id = $this->_getParam("id_log");

        $logR = new Default_Models_LogTransactionReject();
        $select = $this->db->select()
            ->from(array($logR->__name()))
            ->where("trano = '$trano'")
            ->where("id = '$id'");
        $data = $this->db->fetchRow($select);
        $arrayData = array();
        if ($data)
        {
            $counter = new Default_Models_MasterCounter();
            $itemType = $counter->getTransTypeFlip($trano);
            $arrayData = $this->transformData($itemType,Zend_Json::decode($data['data']));
        }
        $json = Zend_Json::encode(array("data" => $arrayData));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    private function getDataFromTransaction($type,$trano)
    {
        $arrayData = array();
        switch($type)
        {
            case 'PR':
            case 'PRO':
                $model = new Default_Models_ProcurementRequest();
                $data = $model->fetchALl("trano = '$trano'");
                if ($data)
                {
                    $arrayData = $data->toArray();
                    $arrayData = $this->transformData($type,$arrayData);
                }
                break;
            case 'PO':
            case 'POO':
                $model = new Default_Models_ProcurementPod();
                $data = $model->fetchALl("trano = '$trano'");
                if ($data)
                {
                    $arrayData = $data->toArray();
                    $arrayData = $this->transformData($type,$arrayData);
                }
            break;
        }

        return $arrayData;
    }

    private function transformData($type,$arrayData=array())
    {
        $newArrayData = array();
        switch($type)
        {
            case 'PR':
            case 'PRO':
                foreach($arrayData as $k => $v)
                {
                    $newArrayData[] = array(
                        "prj_kode" => $v['prj_kode'],
                        "sit_kode" => $v['sit_kode'],
                        "prj_nama" => $v['prj_nama'],
                        "sit_nama" => $v['sit_nama'],
                        "workid" => $v['workid'],
                        "workname" => $v['workname'],
                        "kode_brg" => $v['kode_brg'],
                        "nama_brg" => $v['nama_brg'],
                        "val_kode" => $v['val_kode'],
                        "qty" => $v['qty'],
                        "harga" => $v['harga'],
                        "total" => (floatval($v['qty']) * $v['harga']),
                    );
                }
            break;
            case 'PO':
            case 'POO':
                foreach($arrayData as $k => $v)
                {
                    $newArrayData[] = array(
                        "prj_kode" => $v['prj_kode'],
                        "sit_kode" => $v['sit_kode'],
                        "prj_nama" => $v['prj_nama'],
                        "sit_nama" => $v['sit_nama'],
                        "workid" => $v['workid'],
                        "workname" => $v['workname'],
                        "kode_brg" => $v['kode_brg'],
                        "nama_brg" => $v['nama_brg'],
                        "val_kode" => $v['val_kode'],
                        "qty" => $v['qty'],
                        "qtysupp" => $v['qtyspl'],
                        "harga" => $v['harga'],
                        "hargasupp" => $v['hargaspl'],
                        "total" => (floatval($v['qty']) * $v['harga']),
                        "totalsupp" => (floatval($v['qtyspl']) * $v['hargaspl']),
                    );
                }
                break;
        }

        return $newArrayData;
    }

    public function getReconfigureAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $type = $this->_getParam("item_type");

        switch($type)
        {
            case 'PO':
            case 'POO':
                $colModel = "
                    [new Ext.grid.RowNumberer({width: 30}),
                    {header: 'Project Code', width: 90, dataIndex: 'prj_kode', sortable: true},
                    {header: 'Project Name', width: 120, dataIndex: 'prj_nama', sortable: true},
                    {header: 'Site Code', width: 70, dataIndex: 'sit_kode', sortable: true},
                    {header: 'Site Name', width: 120, dataIndex: 'sit_nama', sortable: true},
                    {header: 'Workid', width: 70, dataIndex: 'workid', sortable: true},
                    {header: 'Work Name', width: 120, dataIndex: 'workname', sortable: true},
                    {header: 'Product ID', width: 90, dataIndex: 'kode_brg', sortable: true},
                    {header: 'Name', width: 120, dataIndex: 'nama_brg', sortable: true},
                    {header: 'Curr.', width: 50, dataIndex: 'val_kode', sortable: true},
                    {header: 'Qty', width: 120, dataIndex: 'qty', sortable: true, align: 'right',renderer: function(v){
                        return Ext.util.Format.number(v, '?0,000.0000?');
                    }},
                    {header: 'Qty Supplier', width: 120, dataIndex: 'qtysupp', sortable: true, align: 'right',renderer: function(v){
                        return Ext.util.Format.number(v, '?0,000.0000?');
                    }},
                    {header: 'Price', width: 120, dataIndex: 'harga', sortable: true, align: 'right',renderer: function(v,p,r){
                        return Ext.util.Format.number(v, '?0,000.00?');
                    }},
                    {header: 'Price Supplier', width: 120, dataIndex: 'hargasupp', sortable: true, align: 'right',renderer: function(v,p,r){
                        return Ext.util.Format.number(v, '?0,000.00?');
                    }},
                    {header: 'Total', width: 120, dataIndex: 'total', sortable: true, align: 'right',renderer: function(v){
                        return Ext.util.Format.number(v, '?0,000.00?');
                    }},
                    {header: 'Total Supplier', width: 120, dataIndex: 'totalsupp', sortable: true, align: 'right',renderer: function(v){
                        return Ext.util.Format.number(v, '?0,000.00?');
                    }}]
                ";

                $store = "
                    [
                        {name: 'prj_kode'},
                        {name: 'sit_kode'},
                        {name: 'prj_nama'},
                        {name: 'sit_nama'},
                        {name: 'kode_brg', mapping: 'kode_brg'},
                        {name: 'nama_brg', mapping: 'nama_brg'},
                        {name: 'workid', mapping: 'workid'},
                        {name: 'workname', mapping: 'workname'},
                        {name: 'val_kode', mapping: 'val_kode'},
                        {name: 'qty', mapping: 'qty'},
                        {name: 'qtysupp'},
                        {name: 'harga', mapping: 'harga'},
                        {name: 'hargasupp'},
                        {name: 'total', mapping: 'total'},
                        {name: 'totalsupp'},
                        {name: 'id'}
                    ]
                ";
            break;
        }

        $json = Zend_Json::encode(array(success=> true,"COLMODELS" => $colModel,"STOREMODELS" => $store));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}

?>