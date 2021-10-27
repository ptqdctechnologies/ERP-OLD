<?php

class Finance_EbankingController extends Zend_Controller_Action {

    public $FINANCE,$db;

    public function init()
    {
        $models = array(
            "EbankingBulk",
            "EbankingBICode",
        );

        $this->FINANCE = QDC_Model_Finance::init($models);

        $this->db = Zend_Registry::get("db");
    }

    public function saveBulkAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $trano = $this->_getParam("trano");
        $ref_number = $this->_getParam("ref_number");
        $val_kode = $this->_getParam("val_kode");
        $total = $this->_getParam("total");
        $item_type = $this->_getParam("item_type");
        $bulk_id = $this->_getParam("bulk_id");

        $uid = QDC_User_Session::factory()->getCurrentUID();

        //cek bulk id exist or not
        if (!$bulk_id)
        {
            $bulk_id = $this->FINANCE->EbankingBulk->getBulkID();
        }

        $this->FINANCE->EbankingBulk->insert(array(
            "trano" => $trano,
            "ref_number" => $ref_number,
            "val_kode" => $val_kode,
            "total" => $total,
            "item_type" => $item_type,
            "bulk_id" => $bulk_id,
            "uid" => $uid,
            "tgl" => date("Y-m-d H:i:s")
        ));

        $json = Zend_Json::encode(array(
            "success" => true,
            "bulk_id" => $bulk_id
        ));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    private function insertToBulk($params=array())
    {
        if ($params != '') {
            foreach ($params as $k => $v) {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }
    }

    public function importBiCodeAction()
    {

    }

    public function doImportBiCodeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $result = QDC_Adapter_File::factory()->upload($_FILES,'file-path');
        if ($result)
        {
            $indeks = array(
                2 => "clearing_code",
                3 => "rtgs_code",
                4 => "bank_name",
                5 => "bank_branch",
                6 => "bank_city",
                7 => "location_code",
                8 => "location_bank",
            );
            $data = QDC_Adapter_Excel::factory(array("fileName" => $result['save_file']))->read(3,$indeks);
            if ($data)
            {
                if (file_exists($result['save_file']))
                {
                    unlink($result['save_file']);
                }

                $invalidData = array();$invalid = false;$newData = array();
                foreach($data as $k => $v)
                {
                    $total = $this->checkColumnBICode($v,1,true);
                    if ($total == count($indeks))
                        continue;
                    elseif ($total < count($indeks) && $total > 0)
//                    if ($v['clearing_code'] == "" || $v['bank_name'] == "")
                    {
                        $tmp = $data[$k];
                        $tmp['row'] = $k+1;
                        $invalidData[] = $tmp;
                        $invalid = true;
                        continue;
                    }

                    $newData[] = $data[$k];
                }
            }
            $result = array(
                "success" => true,
                "data" => $newData,
                "invalid_data" => $invalidData,
                "invalid" => $invalid
            );
        }
        else
            $result = array(
                "success" => false,
                "msg" => "Error on uploading Your file"
            );

        echo Zend_Json::encode($result);
    }

    public function ebankingAction()
    {

    }

    public function saveBiCodeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = $this->_getParam("json");
        if (!$json)
            return false;

        $data =Zend_Json::decode($json);

        foreach($data as $k => $v)
        {
            //Cek dulu sudah exist atau belum
            $select = $this->db->select()
                ->from(array($this->FINANCE->EbankingBICode->__name()))
                ->where("clearing_code=?",$v['clearing_code']);
            $cek = $this->db->fetchRow($select);
            if ($cek)
            {
                $tmp = $data[$k];
                unset($tmp['clearing_code']);
                $this->FINANCE->EbankingBICode->update($tmp,"id=".$cek['id']);
            }
            else
            {
                $tmp = $data[$k];
                $tmp['uid'] = QDC_User_Session::factory()->getCurrentUID();
                $tmp['tgl'] = date("Y-m-d H:i:s");
                $this->FINANCE->EbankingBICode->insert($tmp);
            }
        }

        $json = Zend_Json::encode(array(
            "success" => true
        ));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getBankListAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $bankName = $this->_getParam("bank_name");
        $bankCity = $this->_getParam("bank_city");
        $bankBranch = $this->_getParam("bank_branch");

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        $select = $this->db->select()
            ->from(array($this->FINANCE->EbankingBICode->__name()),
                array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                )
            )
            ->order(array("bank_name ASC","bank_branch ASC"))
            ->limit($limit,$offset);

        if($bankName)
            $select = $select->where("bank_name LIKE '%$bankName%'");
        if($bankBranch)
            $select = $select->where("bank_branch LIKE '%$bankBranch%'");
        if($bankCity)
            $select = $select->where("bank_city LIKE '%$bankCity%'");

        $data = $this->db->fetchAll($select);
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $json = Zend_Json::encode(array(
            "success" => true,
            "data" => $data,
            "count" => $count
        ));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }


    public function exportAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $type = $this->_getParam("type");
        $json = ($this->_getParam("json")) ? Zend_Json::decode($this->_getParam("json")) : array();

        if (count($json) == 0)
            return false;

        $newData = array();

        $bulk_id = $this->FINANCE->EbankingBulk->getBulkID();
        $filename = "ebanking_permata_" . date("YmdHisu") . "_" . rand(0,100);

        foreach($json as $k => $v)
        {
            $this->FINANCE->EbankingBulk->insert(array(
                "trano" => $v['trano'],
                "ref_number" => $v['ref_number'],
                "item_type" => $v['item_type'],
                "val_kode" => $v['val_kode'],
                "total" => round($v['total'],0,PHP_ROUND_HALF_UP),
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date("Y-m-d H:i:s"),
                "bulk_id" => $bulk_id,
                "bank_name" => $v['bank_name'],
                "bank_address" => $v['bank_address'],
                "bank_city" => $v['bank_city'],
                "bank_bi_code" => $v['bank_bi_code'],
                "bank_no" => $v['bank_no'],
                "ket" => $v['remark_1'],
                "ket2" => $v['remark_2'],
                "email" => '',
                "status_resident" => 0,
                "status_citizen" => 1,
                "bank_branch" => $v['bank_branch'],
                "npk" => '',
                "customer_ref_number" => $v['ref_number'],
                "effective_date" => date("Y-m-d"),
                "filename" => $filename,
            ));
        }

        $newArray = $this->mergePayment($json);
        foreach($newArray as $k => $v)
        {
            $bankNo = $v['bank_no'];
            if (strlen($bankNo) > 12 || substr($bankNo,0,1) == "0")
                $bankNo = "'" . $bankNo;

            /*
             * 1 bank_name
             * 2 bank_address
             * 3 bank_city
             * 4 bi_code *
             * 5 recipient_name
             * 6 account_no *
             * 7 currency
             * 8 nominal * GAK BOLEH ADA COMMA (.), HARUS BULET
             * 9 remark_1
             * 10 remark_2
             * 11 email
             * 12 transaction_type *
             * 13 resident_status
             * 14 citizen_status
             * 15 bank_branch
             * 16 npk
             * 17 customer_ref_number
             * 18 effective_date
             * 19 sms_notif
             * 20 fax_notif
             *
             *  * mandatory
             *
             * */
            $newData[] = array(
                ($v['bank_name'] == null) ? "" : $v['bank_name'], //1
                ($v['bank_address'] == null) ? "": $v['bank_address'], //2
                $v['bank_city'],//3
                $v['bank_bi_code'],//4
                $v['bank_account_name'],//5
                $bankNo,//6
                $v['val_kode'],//7
                round($v['total'],0,PHP_ROUND_HALF_UP),//8
                $v['remark_1'],//9
                $v['remark_2'],//10
                '',//11
                $v['bank_transaction_type'],//12
                0,//13
                1,//14
                $v['bank_branch'],//15
                '',//16
                $v['ref_number'],//17
                '',//18
                '',//19
                ''//20
            );
        }

        if ($type == 'XLS')
        {
            QDC_Adapter_Excel::factory(array(
                "fileName" => $filename
            ))
//            ->setCellFormat(array(
//                6 => array(
//                    "cell_type" => "numeric",
//                    "cell_operation" => "setFormatCode",
//                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
//                ),
//                7 => array(
//                    "cell_type" => "numeric",
//                    "cell_operation" => "setFormatCode",
//                    "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
//                )
//            ))
                ->write($newData, null, false,false)->toExcel5Stream();
        }
        elseif ($type == 'CSV')
        {
            $savePath = Zend_Registry::get('uploadPath');
            $file = $savePath . $filename . ".csv";
            $fp = fopen($file, 'w');
            foreach($newData as $k => $v)
            {
                fputcsv($fp,$v);
            }
            fclose($fp);
            header('Pragma: public');
            header('Expires: 0');
            header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false);
            header("Content-Type: application/csv");
            header('Content-Disposition: attachment; filename="'.$filename.'.csv"; modification-date="'.date('r').'";');
            header('Content-Description: File Transfer');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($file));

            ob_clean();
            flush();
            ignore_user_abort(true);
            readfile($file);

//            $csv = new QDC_Adapter_Csv();
//            $csv->data = $newData;
//            echo APPLICATION_PATH . "../public/docs/" .$filename.".csv";die;
//            $csv->save(APPLICATION_PATH . "../public/docs/" .$filename.".csv");
//            $csv->output($filename.".csv",null,null,",");
        }
    }

    public function paymentAction()
    {

    }

    private function checkColumnBICode($array, $minCol = 0, $returnCount = false)
    {
        if ($minCol == 0)
            $count = count($array);
        else
            $count = $minCol;
        $total = 0;
        foreach($array as $k => $v)
        {
            if ($v == '')
                $total++;
        }

        if ($total >= $count )
        {
            if (!$returnCount)
                return false;
            else
                return $total;
        }

        if (!$returnCount)
            return true;
        else
            return $total;
    }

    private function mergePayment($array=array())
    {
        $newArray = array();
        foreach($array as $k => $v)
        {
            $id = $v['bank_bi_code'] . $v['bank_no'] . $v['bank_transaction_type'];
            if ($newArray[$id] == '')
                $newArray[$id] = $array[$k];
            else
            {
                $newArray[$id]['total'] += $v['total'];
                $newArray[$id]['ref_number'] = '';
                $newArray[$id]['trano'] = '';
            }
        }

        return $newArray;
    }

    public function checkEbankingBulkAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $trano = $this->_getParam("trano");
        $item_type = $this->_getParam("item_type");

        $success = false;
        $cek = $this->FINANCE->EbankingBulk->isExist($trano,$item_type);
        if ($cek)
        {
            $success = !$success;
            $cek['uid'] = QDC_User_Ldap::factory(array("uid" => $cek['uid']))->getName();
            $cek['tgl'] = date("d M Y H:i",strtotime($cek['tgl']));
        }

        $json = Zend_Json::encode(array("success" => $success, "data" => $cek));

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}

?>