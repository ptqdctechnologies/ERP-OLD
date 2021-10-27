<?php

class QDC_Document_Model {

    public $item_type, $trano, $db, $useHash=false;
    public $ADMIN;

    public function __construct($params = '')
    {
        if ($params != '')
        {
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }

        $this->db = Zend_Registry::get("db");
        $this->ADMIN = QDC_Model_Admin::init(array(
            "Workflowtrans"
        ));
    }

    public static function factory($params=array())
    {
        return new self($params);
    }

    public function getApprovalForm()
    {

        $wtrans = $this->ADMIN->Workflowtrans->fetchRow($this->db->quoteInto("item_id = ?",$this->trano),array("date DESC"));
        if (!$wtrans)
            return false;
        $wtrans = $wtrans->toArray();
        $wTransID = $wtrans['workflow_trans_id'];
        $url = $this->getUrlApprovalForm() . $wTransID . "/show/true" . "/external_token/" . QDC_User_Token::factory()->encodeToken($wTransID);

        if ($this->useHash)
        {
            $newUrl = QDC_Common_Url::factory(array("url" => $url))->hash();
            $url = "/default/get-app/data/hash/" . $newUrl;
        }

        return 'http://' . $_SERVER['SERVER_NAME'] . $url;

    }

    public function getUrlApprovalForm()
    {
        $url = '';
        switch($this->item_type)
        {
            case 'PR':
                $url= '/procurement/procurement/apppr/approve/';
                break;
            case 'PRO':
                $url= '/procurement/procurement/appprbudget/approve/';
                break;
            case 'PO':
                $url= '/procurement/procurement/apppo/approve/';
                break;
            case 'POO':
                $url= '/procurement/procurement/apppobudget/approve/';
                break;
            case 'REM':
                $url= '/procurement/procurement/appreimburs/approve/';
                break;
            case 'ARF':
                $url = '/procurement/procurement/apparf/approve/';
                break;
            case 'ARFO':
                $url = '/procurement/procurement/apparfbudget/approve/';
                break;
            case 'ARFP':
                $url= '/procurement/arf-pulsa/app-arf-pulsa/approve/';
                break;
            case 'RPI':
                $url = '/procurement/procurement/apprpi/approve/';
                break;
            case 'RPIO':
                $url = '/procurement/procurement/apprpibudget/approve/';
                break;
            case 'ASF':
                $url = '/procurement/procurement/appasf/approve/';
                break;
            case 'ASFO':
                $url = '/procurement/procurement/appasfbudget/approve/';
                break;
            case 'ASFP':
                $url= '/procurement/asf-pulsa/app-asf-pulsa/approve/';
                break;
            case 'PMEAL':
            case 'PBOQ3':
                $url = '/procurement/procurement/apppmeal/approve/';
                break;
            case 'DOR':
                $url= '/logistic/logistic/appdor/approve/';
                break;
            case 'iLOV':
                $url = '/logistic/logistic/appilov/approve/';
                break;
            case 'iCAN':
                $url = '/logistic/logistic/appican/approve/';
                break;
            case 'iSUPP':
                $url = '/logistic/logistic/appisupp/approve/';
                break;
            case 'SUPP':
                $url = '/logistic/logistic/appsupp/approve/';
                break;
            case 'AFE':
                $url = '/projectmanagement/afe/appafe/approve/';
                break;
            case 'PRABOQ3':
                $url = '/projectmanagement/budget/apppraboq/approve/';
                break;
            case 'PRABGO':
                $url= '/projectmanagement/budget/apptemporaryohbudget/approve/';
                break;
            case 'RINV':
                $url= '/finance/invoice/apprequestinvoice/approve/';
                break;
            case 'PRACO':
                $url= '/sales/sales/appco/approve/';
                break;
            case 'APRACO':
                $url= '/sales/sales/appaddco/approve/';
                break;
            case 'BRF':
                $url= '/procurement/bt-request/app/approve/';
                break;
            case 'BRFP':
                $url= '/procurement/bt-request/app/payment/true/approve/';
                break;
            case 'BSF':
                $url= '/procurement/bt-settlement/app/approve/';
                break;
        }

        return $url;
    }

    public function getTransactionTable()
    {
        $t = '';
        switch($this->item_type)
        {
            case 'PR':
                $t = new Default_Models_ProcurementRequest();
                break;
            case 'PRO':
                $t = new Default_Models_ProcurementRequest();
                break;
            case 'PO':
                $t = new Default_Models_ProcurementPod();
                break;
            case 'POO':
                $t = new Default_Models_ProcurementPod();
                break;
            case 'REM':
                $t = new Default_Models_ReimbursD();
                break;
            case 'ARF':
                $t = new Procurement_Models_Procurementarfd();
                break;
            case 'ARFO':
                $t = new Procurement_Models_Procurementarfd();
                break;
            case 'ARFP':
                $t = new Procurement_Models_Procurementarfd();
                break;
            case 'RPI':
                $t = new Default_Models_RequestPaymentInvoice();
                break;
            case 'RPIO':
                $t = new Default_Models_RequestPaymentInvoice();
                break;
            case 'ASF':
                $t = new Default_Models_AdvanceSettlementFormD();
                break;
            case 'ASFO':
                $t = new Default_Models_AdvanceSettlementFormD();
                break;
            case 'ASFP':
                $t = new Default_Models_AdvanceSettlementFormD();
                break;
            case 'PMEAL':
            case 'PBOQ3':
                $t = new Default_Models_PieceMeal();
                break;
            case 'AFE':
                $t = new ProjectManagement_Models_AFE();
                break;
            case 'BRF':
                $t = new Procurement_Models_BusinessTripDetail();
                break;
            case 'BRFP':
                $t = new Procurement_Models_BusinessTripPayment();
                break;
            case 'BSF':
                $t = new Default_Models_AdvanceSettlementFormD();
                break;
        }

        return $t;
    }

    public function getTableInfo()
    {
        if ($this->table instanceof Zend_Db_Table_Abstract)
        {
            $i = $this->table->info();
            return $i['cols'];
        }

        return false;
    }

    public function getTransactionColumn()
    {
        if ($this->table_column)
        {
            switch($this->item_type)
            {
                case 'ASF':
                    return array(
                        'totalasf'
                    );
                break;
                case 'ASFO':
                    return array(
                        'totalasf'
                    );
                break;
                default:
                    return array(
                        "qty",
                        "harga"
                    );
                break;
            }
        }

        return false;
    }

    public function getTransactionCurrency()
    {
        if ($this->table_column)
        {
            switch($this->item_type)
            {
                default:
                    $s = $this->db->select()
                        ->from(array($this->table->__name()),array(
                            "val_kode"
                        ))
                        ->where("trano=?",$this->trano);
                    break;
            }

            if ($s)
            {
                return $this->db->fetchOne($s);
            }
        }

        return false;
    }

    public function getTransactionCurrencyRate()
    {
        if ($this->table_column)
        {
            switch($this->item_type)
            {

                case 'RPI':
                case 'RPIO':
                    $r = new Default_Models_RequestPaymentInvoiceH();
                    $s = $this->db->select()
                        ->from(array($r->__name()),array(
                            "rateidr"
                        ))
                        ->where("trano=?",$this->trano);
                    break;
                default:
                    $s = $this->db->select()
                        ->from(array($this->table->__name()),array(
                            "rateidr"
                        ))
                        ->where("trano=?",$this->trano);
                    break;
            }

            if ($s)
            {
                return $this->db->fetchOne($s);
            }
        }

        return false;
    }

    public function getTransactionTotal()
    {
        if (!$this->trano || !$this->item_type)
            return false;

        $this->table = $this->getTransactionTable();

        $this->table_column = $this->getTableInfo();
        $cols = $this->getTransactionColumn();
        if ($cols)
        {
            $sums = implode(" * ",$cols);
            $select = $this->db->select()
                ->from(array($this->table->__name()),array(
                    "total" => new Zend_Db_Expr("SUM($sums)")
                ))
                ->where("trano=?",$this->trano)
                ->group(array("trano"));

            $data = $this->db->fetchOne($select);

            return $data;
        }
        return false;
    }

    public function getTable()
    {
        $this->table = $this->getTransactionTable();
        $this->table_column = $this->getTableInfo();

        return $this;
    }

    public function sanitize($data=array())
    {
        foreach($data as $k => $v)
        {
            if (is_array($v))
            {
                foreach($v as $k2 => $v2)
                {
                    if ($v2 == '""')
                        $data[$k][$k2] = '';
                }
            }
            else
            {
                if ($v == '""')
                    $data[$k] = '';
            }
        }

        return $data;
    }
}

?>