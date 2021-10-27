<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/1/11
 * Time: 11:05 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_RequestInvoice extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_request_invoice';

    protected $_primary = 'trano';
    protected $db;
    private $workflow;
    private $const;

    public function getPrimaryKey ()
    {
        return $this->_primary;
    }

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
		$this->workflow = Zend_Controller_Action_HelperBroker::getStaticHelper('workflow');
        $this->const = Zend_Registry::get('constant');
    }

    public function viewallrequest ($offset=0,$limit=0,$dir='DESC',$sort='trano',$search,$all=true,$textsearch='',$check,$checkInvoiced)
    {

        if ($search != "")
        {
            if ($check == '')
                $where = "WHERE $search";
            else
                $where = " AND " . $search;
        }

        if ($all)
        {
            if ($check != '')
                $query = "SELECT SQL_CALC_FOUND_ROWS a.trano,a.tgl,a.cus_kode,a.prj_kode,a.sit_kode,a.prj_nama,a.sit_nama,(COALESCE(a.total,0)) as total
                    FROM " . $this->_name . " a
                    LEFT JOIN workflow_trans b
                    ON a.trano = b.item_id
                    WHERE b.final = 1 $where
                    GROUP BY a.trano
                    ORDER BY $sort $dir LIMIT $offset,$limit";
            else
                $query = "SELECT SQL_CALC_FOUND_ROWS trano,tgl,cus_kode,prj_kode,sit_kode,prj_nama,sit_nama,(COALESCE(total,0)) as total FROM " . $this->_name . " $where
                    ORDER BY $sort $dir LIMIT $offset,$limit";

        }    else
            $query = "SELECT *,(SELECT SUM(total) FROM finance_invoiced WHERE riv_no = '$textsearch' GROUP BY riv_no) AS totalInvoice FROM " . $this->_name . " $where";

        $fetch = $this->db->query ($query);
        if ($all)
        {
            $datas = $fetch->fetchAll ();
            $data['total'] = $this->db->fetchOne ('SELECT FOUND_ROWS()');

            $data['data'] = array();

            if($check != '')
            {
                $i = 0;
                foreach($datas as $k => $v)
                {
                    $trano = $v['trano'];
//                    $stat = $this->workflow->getDocumentLastStatus($trano);

//                    if ($stat == $this->const['DOCUMENT_FINAL'])
//                    {
                        if ($checkInvoiced != '')
                        {
                            $tranos = $v['trano'];
                            $fetch2 = $this->db->query ("SELECT SUM(total) AS totalInvoice FROM finance_invoiced WHERE riv_no = '$tranos' GROUP BY riv_no ");
                            $fetch2 = $fetch2->fetch();
                            if ($fetch2)
                            {
                                if ($fetch2['totalInvoice'] == '')
                                {
                                    $v['invoiced'] = false;
                                    $v['totalInvoice'] = 0;
                                }
                                else
                                {
                                    $v['invoiced'] = true;
                                    $v['totalInvoice'] = $fetch2['totalInvoice'];
                                }
                            }
                        }
                        $data['data'][] = $v;
                        $i++;
//                    }
                }
//                $data['total'] = $i;
            }
            else
            {
                $data['data'] = $datas;
            }

        }
        else
        {
            $data['data'] = $fetch->fetch();
            $ldapdir = new Default_Models_Ldap();
            $account = $ldapdir->getAccount($data['data']['uid']);
            $data['data']['uid_request'] = $account['displayname'][0] ;
            if ($data['data']['totalInvoice'] == '')
                $data['data']['totalInvoice'] = 0;

            $customer = new Default_Models_MasterCustomer();
            $cusKode = $data['data']['cus_kode'];
            $cust = $customer->fetchRow("cus_kode = '$cusKode'");
            $data['data']['alamatpajak'] = $cust['alamatpajak'];
            $data['data']['npwp'] = $cust['npwp'];
        }
        return $data;
    }

    public function getIvoiceAndPayment($trano='',$prjKode='',$sitKode='')
    {
        if ($sitKode!='')
            $where = " AND sit_kode = '$sitKode'";
        $sql = "SELECT
                  SUM(a.totalIDR) as totalInvoiceIDR,
                  IF(a.val_kode = 'IDR',SUM(a.totalPayment),0) as totalPaymentIDR,
                  SUM(a.totalUSD) as totalInvoiceUSD,
                  IF(a.val_kode != 'IDR',SUM(a.totalPayment),0) as totalPaymentUSD
                FROM
                  (SELECT
                    i.totalIDR,
                    i.totalUSD,
                    COALESCE(p.total,0) as totalPayment,
                    COALESCE(p.val_kode,'IDR') as val_kode
                  FROM
                      (SELECT
                        trano,
                        IF(val_kode = 'IDR',SUM(total),0) as totalIDR,
                        IF(val_kode != 'IDR',SUM(total),0) as totalUSD,
                        prj_kode,
                        sit_kode,
                        val_kode
                      FROM finance_invoiced
                      WHERE
                        riv_no = '$trano'
                        AND prj_kode = '$prjKode'
                        $where
                      GROUP BY trano) i
                  LEFT JOIN finance_payment_invoice p
                  ON i.trano = p.inv_no
                  AND i.prj_kode = p.prj_kode
                  AND i.sit_kode = p.sit_kode
                  AND i.val_kode = p.val_kode
                  ) a
        ";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetch();

        return $fetch;
    }

}