<?php

/*
  Created @ Mei 06, 2010 by Haryadi
 */

class Zend_Controller_Action_Helper_Transaction extends
Zend_Controller_Action_Helper_Abstract {

    private $db;
    private $project;

    function __construct() {
        $this->db = Zend_Registry::get('db');
        $this->project = Zend_Controller_Action_HelperBroker::getStaticHelper('project');
    }

    function getArfsummary() {
        $request = $this->getRequest();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $arf = new Default_Models_AdvanceRequestForm();

        $return['posts'] = $arf->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $return['count'] = $arf->fetchAll()->count();

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    function getArfDetail() {
        $request = $this->getRequest();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $arf = new Default_Models_AdvanceRequestForm();

        $return['posts'] = $arf->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $return['count'] = $arf->fetchAll()->count();

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    function getArfasf($prjKode = '', $sitKode = '') {

        if ($sitKode) {
            $where = " AND sit_kode = '$sitKode'";
            $wherea = " AND a.sit_kode = '$sitKode'";
        }
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS arfd;
            CREATE TEMPORARY TABLE arfd
            SELECT a.*, b.*
            FROM (
                SELECT trano, tgl, val_kode, prj_kode, prj_nama, sit_kode, sit_nama, workid, workname,
                kode_brg, nama_brg, SUM(qty), SUM(harga), SUm(qty * harga) as total, requester,
                'ARF' as tipetran
                FROM procurement_arfd
                WHERE prj_kode = '$prjKode' $where and trano not like 'brfp%'
                GROUP BY trano, kode_brg
                UNION
                SELECT trano, tgl, val_kode, prj_kode, prj_nama, sit_kode, sit_nama, workid, workname,
                kode_brg, nama_brg, SUM(qty), SUM(harga), SUm(qty * harga) as total, requester,
                'BRF' as tipetran
                FROM procurement_brfd
                WHERE prj_kode = '$prjKode' $where and trano not like 'brfp%'
                GROUP BY trano, kode_brg
            ) a INNER JOIN (
                    SELECT a.item_id, a.approve as stat_arf, COALESCE(b.statrevisi, 0) as stat_revarf FROM (
                            SELECT * FROM (
                                    SELECT approve, item_id
                                    FROM workflow_trans
                                    WHERE prj_kode = '$prjKode' AND item_type IN ('ARF', 'ARFO', 'BRF')
                                    ORDER BY item_type, item_id DESC, date DESC
                            ) a
                            GROUP BY item_id
                    ) a LEFT JOIN (
                            SELECT trano, statrevisi
                            FROM procurement_arfh
                            WHERE prj_kode = '$prjKode' $where
                    ) b ON (a.item_id = b.trano)
            ) b ON (a.trano = b.item_id)
            ORDER By tgl DESC;";
        $this->db->query($sql);
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS asfddcancel;
            CREATE TEMPORARY TABLE asfddcancel
            SELECT arf_no, trano, tgl, val_kode, prj_kode, sit_kode, workid, kode_brg, SUM(qty),
            SUM(harga), SUm(qty * harga) as total
            FROM procurement_asfddcancel
            WHERE prj_kode = '$prjKode' $where and trano not like 'OCA%'
            and arf_no not like 'BRFP%'
            GROUP BY trano, arf_no, kode_brg
            UNION
            SELECT b.trano_ref as arf_no, a.trano, a.tgl, a.val_kode, a.prj_kode, a.sit_kode, a.workid,
            a.kode_brg, SUM(a.qty), SUM(a.harga), SUM(a.qty * a.harga) as total
            FROM procurement_asfddcancel a
            INNER JOIN procurement_arfd b ON (a.arf_no = b.trano)
            WHERE a.prj_kode = '$prjKode' $wherea and a.trano not like 'OCA%'
            and a.arf_no like 'BRF%'
            GROUP BY a.trano, b.trano_ref, a.kode_brg;";
        $this->db->query($sql);
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS asfdd ;
            CREATE TEMPORARY TABLE asfdd
            SELECT a.*, b.approve as stat_asf, b.date as tglstat_asf
            FROM (
                SELECT arf_no, trano, tgl, val_kode, prj_kode, sit_kode, workid, kode_brg, SUM(qty),
                SUM(harga), SUm(qty * harga) as total, ket
                FROM procurement_asfdd
                WHERE prj_kode = '$prjKode' $where and trano not like 'OCA%'
                and arf_no not like 'BRFP%'
                GROUP BY trano, arf_no, kode_brg
                UNION
                SELECT b.trano_ref as arf_no, a.trano, a.tgl, a.val_kode, a.prj_kode, a.sit_kode, a.workid,
                a.kode_brg, SUM(a.qty), SUM(a.harga), SUm(a.qty * a.harga) as total, a.ket
                FROM procurement_asfdd a
                INNER JOIN procurement_arfd b ON (a.arf_no = b.trano)
                WHERE a.prj_kode = '$prjKode' $wherea and a.trano not like 'OCA%'
                and a.arf_no like 'BRF%'  
                GROUP BY a.trano, b.trano_ref, a.kode_brg
            ) a INNER JOIN (
                    SELECT * FROM (
                            SELECT approve, item_id, date
                    FROM workflow_trans
                    WHERE prj_kode = '$prjKode' AND item_type IN ('ASF', 'BSF', 'ASFO')
                    ORDER BY item_type, item_id DESC, date DESC
                    ) a
            GROUP BY item_id ) b ON (a.trano = b.item_id);";
        $this->db->query($sql);
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS arf_asf;
            CREATE TEMPORARY TABLE arf_asf
            SELECT a.trano as arf_num, a.tgl as tgl_arf, a.prj_kode, a.sit_kode, a.workid, a.kode_brg,
            a.nama_brg, a.val_kode, a.requester, COALESCE(b.trano,'-') as asf_num,
            COALESCE(b.tgl,'-' ) as tgl_asf, a.total as total_arf, a.stat_arf, a.stat_revarf,
            COALESCE(b.total,0) as total_asf, COALESCE(datediff(b.tgl,a.tgl),'-') as aging_arf_days,
            a.tipetran, b.stat_asf, COALESCE(b.tglstat_asf,'-' ) as tglstat_asf, ket as asf_desc
            FROM arfd a
            LEFT JOIN asfdd b ON (a.trano = b.arf_no and a.kode_brg = b.kode_brg);";
        $this->db->query($sql);
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS arfasf;
            CREATE TEMPORARY TABLE arfasf
            SELECT a.*, COALESCE(b.trano,'-') as asfcancel_num, COALESCE(b.total,0) as total_asfcancel
            FROM arf_asf a
            LEFT JOIN asfddcancel b ON (a.arf_num = b.arf_no and a.asf_num = b.trano and a.kode_brg = b.kode_brg)
            ORDER BY tipetran, arf_num DESC";
        $this->db->query($sql);
        
        
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS `bpv`;
                    CREATE TEMPORARY TABLE `bpv`
                    SELECT ref_number, SUM(COALESCE(total,0)) AS total_bpv
                    FROM erpdb.finance_payment_voucher
                    WHERE prj_kode = '$prjKode' $where  AND status_bpv_wht=0 AND deleted=0  AND item_type='ARF'
                    GROUP BY ref_number;";
        $this->db->query($sql);                


        $sql = "DROP TEMPORARY TABLE IF EXISTS `payment`;
                    CREATE TEMPORARY TABLE `payment`
                    SELECT doc_trano, SUM(COALESCE(total_bayar,0)) AS total_payment
                    FROM erpdb.finance_payment_arf
                    WHERE prj_kode = '$prjKode' $where
                    GROUP BY doc_trano;";
        $this->db->query($sql);    
        

        $sql = "DROP TEMPORARY TABLE IF EXISTS `arf`;
                        CREATE TEMPORARY TABLE `arf`
                        SELECT trano AS ref_number,val_kode, prj_kode, sit_kode,SUM(COALESCE(qty*harga,0)) AS trano_value
                        FROM erpdb.procurement_arfd
                        WHERE prj_kode = '$prjKode' $where  AND deleted=0 AND trano NOT LIKE '%P%'
                        GROUP BY trano;";
        $this->db->query($sql);    

        
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS `payment_arf`;
                        CREATE TEMPORARY TABLE `payment_arf`
                        SELECT arf.ref_number,arf.val_kode, COALESCE(bpv.total_bpv,0) AS total_bpv, COALESCE(payment.total_payment,0) AS total_payment,(COALESCE(bpv.total_bpv,0)-COALESCE(payment.total_payment,0)) as balance,arf.trano_value,arf.prj_kode,arf.sit_kode FROM arf
                        LEFT JOIN bpv ON (arf.ref_number = bpv.ref_number)
                        LEFT JOIN payment ON (arf.ref_number = payment.doc_trano);";
        $this->db->query($sql);    
        
        
        $sql = "SELECT arfasf.*, payment_arf.total_payment, (COALESCE(arfasf.total_arf,0)-COALESCE(payment_arf.total_payment,0)) as balance FROM arfasf
                LEFT JOIN payment_arf ON (arfasf.arf_num = payment_arf.ref_number);";
        $data = $this->db->query($sql);
        
        $result = $data->fetchAll();
        return $result;
    }
    
    function getPorpi($prjKode = '', $sitKode = '', $supKode = '', $trano = '', $param = '') {
//            $sp = $this->db->prepare("call sp_porpi('$prjKode','$sitKode','$param')");
//            $sp->execute();
//            $result = $sp->fetchAll();
//            $sp->closeCursor();

        $where = '';
        if ($prjKode != '' && $sitKode != '')
            $where = "
                a.prj_kode = '$prjKode'
              AND
                a.sit_kode = '$sitKode'";
        $where2 = " b.deleted=0 AND
                b.prj_kode = '$prjKode'
              AND
                b.sit_kode = '$sitKode'";
        if ($supKode != '') {
            if ($where != '') {
                $where .= " AND a.sup_kode = '$supKode'";
                $where2 .= " AND b.sup_kode = '$supKode'";
            } else {
                $where = " a.sup_kode = '$supKode'";
                $where2 = " b.sup_kode = '$supKode'";
            }
        }
        if ($trano != '') {
            if ($where != '') {
                $where .= " AND a.trano = '$trano'";
                $where2 .= " AND b.po_no = '$trano'";
            } else {
                $where = " a.trano = '$trano'";
                $where2 = " b.po_no = '$trano'";
            }
        }
        $sql = "
          DROP TEMPORARY TABLE IF EXISTS pod;
          CREATE TEMPORARY TABLE pod
          SELECT
            a.trano,
            a.tgl,
            a.kode_brg,
             (SELECT
                nama_brg
              FROM
                master_barang_project_2009
              WHERE
                kode_brg = a.kode_brg
              LIMIT 0,1) as nama_brg,
            a.workid,
            a.workname,
            a.val_kode,
            a.prj_kode,
            a.prj_nama,
            a.sit_kode,
            a.sit_nama,
            a.qty,
            a.harga,
            (CASE val_kode WHEN 'IDR' THEN (a.harga * a.qty) ElSE 0.00 END) AS totalPO_IDR,
            (CASE val_kode WHEN 'USD' THEN (a.harga * a.qty) ElSE 0.00 END) AS totalPO_USD,
            a.val_kode as val_kode_po,
            a.sup_nama
          FROM
            procurement_pod a
          WHERE
            $where; ";

        $this->db->query($sql);

        $sql = "
          DROP TEMPORARY TABLE IF EXISTS rpid;
          CREATE TEMPORARY TABLE rpid
          SELECT
            b.trano,
            b.tgl,
            b.po_no,
            b.kode_brg,
            b.workid,
            b.workname,
            b.nama_brg,
            b.val_kode,
            b.prj_kode ,
            b.sit_kode ,
            b.qty,
            b.harga,
            (CASE val_kode WHEN 'IDR' THEN (b.harga * b.qty) ElSE 0.00 END) AS totalRPI_IDR,
            (CASE val_kode WHEN 'USD' THEN (b.harga * b.qty) ElSE 0.00 END) AS totalRPI_USD,
            b.val_kode as val_kode_rpi,
            b.sup_kode
          FROM
            procurement_rpid b
          WHERE
            $where2
          ORDER BY
            tgl DESC,
            b.trano DESC;";

        $this->db->query($sql);

        $sql = "
        DROP TEMPORARY TABLE IF EXISTS porpi;
        CREATE TEMPORARY TABLE porpi
        SELECT
            a.trano as po_no,
            a.tgl as tgl_po,
            a.prj_kode,
            a.prj_nama,
            a.sit_kode,
            a.sit_nama,
            a.workid,
            a.workname,
            a.kode_brg,
            a.nama_brg,
            a.qty as qty_po,
            a.harga as harga_po,
            a.totalPO_IDR,
            a.totalPO_USD,
            a.val_kode as val_kode_po,
            a.sup_nama as sup_po,
            COALESCE(b.trano,' - ') as rpi_no,
            COALESCE(b.tgl,' - ' ) as tgl_rpi,
            COALESCE(b.qty,0) as qty_rpi,
            COALESCE(b.harga,0) as harga_rpi,
            COALESCE(b.totalRPI_IDR,0) as totalRPI_IDR,
            COALESCE(b.totalRPI_USD,0) as totalRPI_USD,
            COALESCE(a.totalPO_IDR,0) - COALESCE(b.totalRPI_IDR,0) as balanceIDR,
            COALESCE(a.totalPO_USD,0) - COALESCE(b.totalRPI_USD,0) as balanceUSD
        FROM
             pod a
        LEFT JOIN
            rpid b
        ON
            (a.workid = b.workid
            AND
              b.kode_brg = a.kode_brg
            AND
              a.prj_kode = b.prj_kode
            AND
              a.sit_kode = b.sit_kode
                    AND
              a.trano = b.po_no);
        ";

        $this->db->query($sql);

        if ($param == 'grandtotal-po')
            $sql = "
            SELECT
                SUM(total.sum_totalPO_IDR) as grandTotalPO_IDR,
                SUM(total.sum_totalPO_USD) as grandTotalPO_USD,
                SUM(total.totalRPI_IDR) as grandTotalRPI_IDR,
                SUM(total.totalRPI_USD) as grandTotalRPI_USD,
                SUM(total.balanceIDR) as grandTotalBalanceIDR,
                SUM(total.balanceUSD) as grandTotalBalanceUSD

            FROM
            (SELECT

                SUM(p.totalPO_IDR) as sum_totalPO_IDR,
                SUM(p.totalPO_USD) as sum_totalPO_USD,
                SUM(p.totalRPI_IDR) as totalRPI_IDR,
                SUM(p.totalRPI_USD) as totalRPI_USD,
                SUM(p.balanceIDR) as balanceIDR,
                SUM(p.balanceUSD) as balanceUSD
            FROM
                (SELECT DISTINCT
                        *
                    FROM
                        porpi
                    ORDER BY
                        po_no) p
                    GROUP BY
                        p.po_no ) total;
            ";
        else if ($param == 'summary-po')
            $sql = "
            SELECT
                p.prj_kode,
                p.prj_nama,
                p.sit_kode,
                p.sit_nama,
                p.po_no,
                p.tgl_po,
                p.sup_po,
                p.kode_brg,
                p.nama_brg,
                SUM(p.totalPO_IDR) as sum_totalPO_IDR,
                SUM(p.totalPO_USD) as sum_totalPO_USD,
                COUNT(p.po_no),
                p.rpi_no,
                p.tgl_rpi,
                SUM(p.totalRPI_IDR) as totalRPI_IDR,
                SUM(p.totalRPI_USD) as totalRPI_USD,
                SUM(p.balanceIDR) as balanceIDR,
                SUM(p.balanceUSD) as balanceUSD
            FROM
                (SELECT DISTINCT
                        *
                    FROM
                        porpi
                    ORDER BY
                        po_no) p
                    GROUP BY
                        p.po_no
                    ORDER BY
                        p.po_no,p.kode_brg,p.totalPO_IDR,p.totalPO_USD;
            ";
        else if ($param == 'detail-rpi')
            $sql = "
            SELECT
                p.*
            FROM
                porpi p
            ORDER BY
                p.po_no,p.kode_brg,p.tgl_rpi ASC,p.totalPO_IDR,p.totalPO_USD;
            ";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }
 
    
    
    //New Functin getPorpi
  function getPorpi2($prjKode = '', $sitKode = '', $supKode='', $trano='', $cod='') {
        
        if($prjKode != '' || $prjKode != null){
            $pk = " prj_kode = '$prjKode' ";
            if($sitKode != '' || $sitKode != null){
                $sk = " AND sit_kode = '$sitKode' ";
            } else {
                $sk = "";
            }
        } else {
            $pk = ''; $sk = "";
        }
        
        $qr = "";
        
        if($supKode != '' || $supKode != null){
            if($prjKode != '' || $prjKode != null){
                $sp = "AND sup_kode = '$supKode' ";
            } else {
                $sp = "sup_kode = '$supKode' ";
                $qr = "doc_trano IN (select trano_RPI from rpi_total)";
                $qpo = "doc_trano IN (select trano_PO from po_total)";
            }
        } else {
            $sp = '';
        }
        
        if($trano != '' || $trano != null){
            if(($prjKode != '' || $prjKode != null) || ($supKode != '' || $supKode != null)){
                $tr1 = " AND trano = '$trano' ";
                $tr2 = " AND po_no = '$trano' ";
            } else {
                $tr1 = "trano = '$trano' ";
                $tr2 = "po_no = '$trano' ";
                $qr = "doc_trano IN (select trano_RPI from rpi_total)";
                $qpo = "doc_trano IN (select trano_PO from po_total)";
            }
        } else {
            $tr1 = ""; $tr2 = "";
        }
        
        if($cod){
            $cd = " a.cod = 'Y'";
        } else {
            $cd = " a.cod = 'N'";
        }
        
        $sql1 ="DROP TEMPORARY TABLE IF EXISTS po_total; 
                CREATE TEMPORARY TABLE po_total
                SELECT 
                    a.*, b.cod 
                FROM (
                    SELECT 
                        trano as trano_PO, 
                        SUM(hargaspl * qtyspl) AS total_PO,
                        val_kode
                    FROM 
                        procurement_pod
                    WHERE
                        $pk $sk $sp $tr1
                    GROUP BY trano 
                ) a
                INNER JOIN procurement_poh b 
                    ON (a.trano_PO = b.trano);";
        $this->db->query($sql1);
                
        $sql2 ="DROP TEMPORARY TABLE IF EXISTS rpi_total;
                CREATE TEMPORARY TABLE rpi_total
                SELECT 
                    trano as trano_RPI, 
                    po_no as trano_PO,
                    SUM(harga * qty) AS total_RPI, 
                    SUM((harga * qty) + IFNULL(ppn,0) + IFNULL(total_grossup,0) - IFNULL(totalwht,0) - IFNULL(total_deduction,0)) 
                    as total_netRPI
                FROM 
                    procurement_rpid 
                WHERE
                    $pk $sk $sp $tr2
                GROUP BY trano;";
        $this->db->query($sql2);
            
        $sql3 ="DROP TEMPORARY TABLE IF EXISTS rpipayment_total; 
                CREATE TEMPORARY TABLE rpipayment_total 
                SELECT a.* 
                FROM (
                    SELECT 
                        trano as trano_PayRPI, 
                        doc_trano as trano_RPI, 
                        sum(total_bayar) as total_PayRPi, 
                        tgl as tgl_PayRPI, 
                        voc_trano as trano_VocRPI
                    FROM finance_payment_rpi 
                    WHERE 
                        $pk $sk $qr
                    GROUP BY trano
                ) a
                INNER JOIN (
                    SELECT 
                        trano
                    FROM finance_payment_voucher
                    WHERE 
                        item_type = 'RPI'
                    GROUP BY trano
                ) b 
                ON a.trano_VocRPI = b.trano;";
        $this->db->query($sql3);
        
        $sql4 ="DROP TEMPORARY TABLE IF EXISTS rpi_total2;
                CREATE TEMPORARY TABLE rpi_total2
                SELECT 
                    a.*, 
                    COALESCE(b.trano_PayRPI, '-') as trano_PayRPI,
                    COALESCE(b.total_PayRPI, 0) as total_PayRPI, 
                    COALESCE(b.tgl_PayRPI, '-') as tgl_PayRPI
                FROM 
                    rpi_total a 
                LEFT JOIN rpipayment_total b 
                    ON (a.trano_RPI = b.trano_RPI);";
        $this->db->query($sql4);
            
        $sql5 ="DROP TEMPORARY TABLE IF EXISTS rpi_total3;
                CREATE TEMPORARY TABLE rpi_total3
                SELECT 
                    a.*, 
                    COALESCE(b.trano_RPI, '-') as trano_RPI, 
                    COALESCE(b.total_RPI, 0) as total_RPI,
                    COALESCE(b.total_netRPI, 0) as total_netRPI, 
                    COALESCE(b.trano_PayRPI, '-') as trano_PayRPI, 
                    COALESCE(b.total_PayRPI, 0) as total_PayRPI,
                    COALESCE(b.tgl_PayRPI, '-') as tgl_PayRPI
                FROM 
                    po_total a 
                LEFT JOIN rpi_total2 b 
                    ON (a.trano_PO = b.trano_PO)
                WHERE
                    $cd
                ORDER BY trano_PO DESC, trano_RPI DESC;";
        $this->db->query($sql5);    
        
        $sql6 ="DROP TEMPORARY TABLE IF EXISTS popayment_total; 
                CREATE TEMPORARY TABLE popayment_total 
                    SELECT 
                        trano as trano_PayPO, 
                        doc_trano as trano_PO, 
                        sum(total_bayar) as total_PayPO
                    FROM finance_payment_po 
                    WHERE 
                         $pk $qpo
                    GROUP BY doc_trano;";
        $this->db->query($sql6);    
        
        $sql7 ="SELECT 
                    a.*, 
		    COALESCE(b.total_PayPO) as total_PayPO
                FROM 
                    rpi_total3 a 
                LEFT JOIN popayment_total b 
                    ON (a.trano_PO = b.trano_PO)
                ORDER BY a.trano_PO DESC, a.trano_RPI DESC;";
        $fetch = $this->db->query($sql7);        
        $datas = $fetch->fetchAll();
        
        return $datas;
    }
    
     function getPrdor($prjKode = '', $sitKode = '', $tranos='') {
         $p_kode = ($prjKode !=''|| $prjKode!=null)  ? " prj_kode='$prjKode' " : '';
         $s_sit = ($sitKode !=''|| $sitKode!=null)  ? " AND sit_kode='$sitKode' " : '';
         $t_rano = ($tranos !=''|| $tranos!=null)  ? "  b.pr_no='$tranos' " : '';
         $t_rano2 = ($tranos !=''|| $tranos!=null)  ? " a.trano='$tranos' " : '';
    
        
           $sql ="DROP TEMPORARY TABLE IF EXISTS pr_total;
                CREATE TEMPORARY TABLE pr_total
                SELECT a.trano, a.workid, a.workname, a.prj_kode, a.prj_nama, a.sit_kode, a.sit_nama,
                SUM(a.qty) AS qty_pr,
                a.kode_brg as kode_brg_pr
                FROM
                    procurement_prd a
                WHERE
                    $p_kode $s_sit $t_rano2
                GROUP BY
                    a.trano, a.kode_brg;";
           $this->db->query($sql);
          
            $sql1 =" DROP TEMPORARY TABLE IF EXISTS dor_total;
                CREATE TEMPORARY TABLE dor_total
                SELECT b.trano, b.pr_no, b.prj_kode, b.prj_nama, b.sit_kode, b.sit_nama,
                SUM(b.qty) AS qty_dor,
                b.kode_brg as kode_brg_dor
                FROM
                  procurement_pointd b
                WHERE
                  $p_kode $s_sit $t_rano
                GROUP BY
                  b.trano,b.pr_no,b.kode_brg;
                    ";
           $this->db->query($sql1);
          
           $sql2 = "DROP TEMPORARY TABLE IF EXISTS prdor;
                CREATE TEMPORARY TABLE prdor
                SELECT a.trano as pr_no, kode_brg_pr, qty_pr,
                COALESCE(b.trano,' - ') as dor_no,
                COALESCE(b.qty_dor,' - ') as qty_dor,
                COALESCE(b.kode_brg_dor,' - ') as kode_brg_dor
                FROM
                     pr_total a
                INNER JOIN
                    dor_total b
                ON
                    (a.trano = b.pr_no) and (a.kode_brg_pr = b.kode_brg_dor);
                ";
           $this->db->query($sql2);
        
           $sql3 = "SELECT * FROM prdor ORDER BY pr_no ASC";
        
        
        $fetch = $this->db->query($sql3);
        $datas = $fetch->fetchAll();
        return $datas;
    }


    function getOutPoRpi($prjKode = '', $sitKode = '', $startdate = '', $enddate = '') {

        $where = array();
        if ($prjKode) {
            $where[] = "prj_kode = '$prjKode'";

            if ($sitKode)
                $where [] = "sit_kode = '$sitKode'";
        }


        if ($startdate) {
            $tglAwal = date("Y-m-d 00:00:00", strtotime($startdate));
            if ($enddate) {
                $tglAkhir = date("Y-m-d 23:59:59", strtotime($enddate));
            } else
                $tglAkhir = date("Y-m-d 00:00:00", strtotime($startdate));

            $where[] = "tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        if ($where)
            $where = implode(" AND ", $where);

        if (!$where)
            $where = null;
        else
            $where = " WHERE $where";


        $fetch = $this->db->query("DROP TEMPORARY TABLE IF EXISTS po;");
        $fetch = $this->db->query("CREATE TEMPORARY TABLE po
                SELECT trano AS po_no,tgl AS tglpo,pr_no, prj_kode,prj_nama,
                sit_kode,sit_nama,workid,workname,kode_brg,nama_brg,
                statusppn,qtyspl,hargaspl, COALESCE(ppnspl,0)ppn, FORMAT(totalspl,2)total, SUM((CASE val_kode WHEN 'IDR' THEN (hargaspl * qtyspl) ELSE 0.00 END)) AS totalPO_IDR, SUM((CASE val_kode WHEN 'USD' THEN (hargaspl * qtyspl) ELSE 0.00 END)) AS totalPO_USD,
                val_kode,sup_kode,sup_nama
                FROM procurement_pod $where
                GROUP BY trano
                ORDER BY pr_no,trano;");


        $fetch = $this->db->query("drop temporary table if exists pofinal;");
        $fetch = $this->db->query("create temporary table pofinal
                select a.* from po a join workflow_trans b on a.po_no = b.item_id
                where b.final = 1 and b.item_type in ('PO','POO')group by item_id;");

        $fetch = $this->db->query("drop temporary table if exists rpi;");
        $fetch = $this->db->query("create temporary table rpi
                select trano as rpino,tgl as tglrpi,po_no,pr_no, prj_kode,prj_nama
                sit_kode,sit_nama,workid,workname,kode_brg,nama_brg,
                statusppn,qty,harga,coalesce(ppn,0)ppn,format(total,2)total,
                sum((CASE val_kode WHEN 'IDR' THEN (qty * harga) ElSE 0.00 END)) AS totalRPI_IDR,
                sum((CASE val_kode WHEN 'USD' THEN (qty * harga) ElSE 0.00 END)) AS totalRPI_USD,
                val_kode,sup_kode
                from procurement_rpid $where
                group by po_no
                order by trano;");

        $fetch = $this->db->query("drop temporary table if exists final;");
        $fetch = $this->db->query("create temporary table final
                select a.*,b.rpino,coalesce(b.totalRPI_IDR,0)totalRPI_IDR,coalesce(b.totalRPI_USD,0)totalRPI_USD,
                (coalesce(totalPO_IDR,0)-coalesce(totalRPI_IDR,0))balance,
                DATEDIFF(DATE(NOW()),tglpo)delay
                from pofinal a
                left join
                rpi b on (a.po_no = b.po_no) order by po_no;");

        $datas = $this->db->fetchAll("select * from final where rpino is null or balance > 0 order by tglpo,po_no,prj_kode;");

        return $datas;
    }

    function getOutprpodetail($prjKode = '', $sitKode = '') {
        $sp = $this->db->prepare("call outprpo('$prjKode','$sitKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();

        return $result;
    }

    function getMdimdo($prjKode = '', $sitKode = '') {
        $sp = $this->db->prepare("call sp_mdimdo('$prjKode','$sitKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();

//        var_dump($result);

        return $result;
    }

    function getdortodo($prjKode = '', $sitKode = '', $group = true) {
        if ($group == 'false')
            $group = false;
        if ($group) {
            $sql = "
                    SELECT
                        a.trano,
                        a.tgl,
                        a.kode_brg,
                        (
                          SELECT
                            nama_brg
                          FROM
                            master_barang_project_2009
                          WHERE
                            kode_brg = a.kode_brg
                          LIMIT 0,1
                        ) as nama_brg,
                        a.workid,
                        a.workname,
                        a.val_kode,
                        a.prj_kode,
                        a.prj_nama,
                        a.sit_kode,
                        a.sit_nama,
                        if(a.sts_internal = 0,a.qty,a.qty_int) AS qty_dor
                        FROM
                          procurement_pointd a
                        WHERE
                          a.prj_kode = '$prjKode'
                        AND
                          a.sit_kode = '$sitKode'
                        ORDER By a.trano,a.workid,a.kode_brg;
            ";
            $fetch = $this->db->query($sql);
            $fetch = $fetch->fetchAll();
            foreach ($fetch as $k => $v) {
                $sql = "SELECT SUM(qty) AS qty,tgl AS tgl_do FROM procurement_whod WHERE
                      mdi_no = '{$v['trano']}'
                    AND
                      kode_brg = '{$v['kode_brg']}'
                    AND
                      workid = '{$v['workid']}'
                    AND
                      prj_kode = '{$v['prj_kode']}'
                    AND
                      sit_kode = '{$v['sit_kode']}'
                ";
                $fetch2 = $this->db->query($sql);
                $fetch2 = $fetch2->fetch();
                if ($fetch2)
                    $qty_do = floatval($fetch2['qty']);
                else
                    $qty_do = 0;

                $balance = floatval($v['qty_dor']) - $qty_do;
                $fetch[$k]['qty_do'] = $qty_do;
                $fetch[$k]['tgl_do'] = $fetch2['tgl_do'];
                $fetch[$k]['balance'] = $balance;
                $fetch[$k]['dor_no'] = $v['trano'];
            }
        }
        else {
            $sql = "
              SELECT
                a.trano AS dor_no,
                a.tgl,
                a.kode_brg,
                (
                  SELECT
                    nama_brg
                  FROM
                    master_barang_project_2009
                  WHERE
                    kode_brg = a.kode_brg
                  LIMIT 0,1
                ) as nama_brg,
                a.workid,
                a.workname,
                a.val_kode,
                a.prj_kode,
                a.prj_nama,
                a.sit_kode,
                a.sit_nama,
                if(a.sts_internal = 0,a.qty,a.qty_int) AS qty_dor,
                COALESCE(b.qty,0) AS qty_do,
                (COALESCE(if(a.sts_internal = 0,a.qty,a.qty_int),0) - COALESCE(b.qty,0)) as balance,
                COALESCE(b.tgl,'-') AS tgl_do,
                b.trano AS do_no
                FROM
                  procurement_pointd a
                LEFT JOIN
                  procurement_whod b
                ON
                  a.trano = b.mdi_no
                AND
                  a.prj_kode = b.prj_kode
                AND
                  a.sit_kode = b.sit_kode
                AND
                  a.workid = b.workid
                AND
                  a.kode_brg = b.kode_brg
                WHERE
                  a.prj_kode = '$prjKode'
                AND
                  a.sit_kode = '$sitKode'
                ORDER By a.trano,a.workid,a.kode_brg;
            ";

            $fetch = $this->db->query($sql);
            $fetch = $fetch->fetchAll();
        }
        $result = $fetch;
//        $sql = "
//            DROP TEMPORARY TABLE IF EXISTS dod;
//            CREATE TEMPORARY TABLE dod
//                SELECT * FROM (
//                    SELECT
//                        b.trano,
//                        b.tgl,
//                        b.mdi_no,
//                        b.kode_brg,
//                        b.workid,
//                        b.workname,
//                        b.nama_brg,
//                        b.val_kode,
//                        b.prj_kode ,
//                        b.sit_kode ,
//                        b.qty,
//                        b.harga,
//                        b.total
//                    FROM
//                        procurement_whod b
//                    WHERE
//                        b.prj_kode = '$prjKode'
//                    AND
//                        sit_kode = '$sitKode'
//                    ORDER BY
//                     tgl DESC,
//                     b.trano DESC
//                )  a
//               GROUP BY a.trano,a.kode_brg,a.workid ;
//        ";
//        $this->db->query($sql);
//        $sql = "
//            DROP TEMPORARY TABLE IF EXISTS dortodo;
//            CREATE TEMPORARY TABLE dortodo
//              SELECT
//                a.prj_kode,
//                a.prj_nama,
//                a.sit_kode,
//                a.sit_nama,
//                a.workid,
//                a.workname,
//                a.kode_brg,
//                a.nama_brg,
//                a.trano as dor_no,
//                a.tgl as tgl_dor,
//                a.qty as qty_dor,
//                COALESCE(b.trano,' - ') as do_no,
//                COALESCE(b.tgl,' - ') as tgl_do,
//                COALESCE(b.qty,' 0 ') as qty_do,
//                (COALESCE(a.qty,0) - COALESCE(b.qty,0)) as balance
//              FROM
//                 dord a
//              LEFT JOIN
//                 dod b
//              ON
//                (a.workid = b.workid
//              AND
//                a.kode_brg = b.kode_brg
//              AND
//                a.prj_kode = b.prj_kode
//              AND
//                a.sit_kode = b.sit_kode
//              AND
//                a.trano = b.mdi_no);
//        ";
//        $this->db->query($sql);
//        $sql = "
//            SELECT * FROM dortodo ORDER BY dor_no,sit_kode;
//        ";
//        $fetch = $this->db->query($sql);
//        $result = $fetch->fetchAll();

        return $result;
    }

    function getMdodo($prjKode = '', $sitKode = '') {
        $sp = $this->db->prepare("call sp_mdodo('$prjKode','$sitKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();

//        var_dump($result);

        return $result;
    }

    function getWhreturn($prjKode = '', $sitKode = '', $trano = '') {
        if ($prjKode) {
            $prjKode = " prj_kode = '$prjKode' ";
            if ($trano)
                $trano = " and trano = '$trano' ";
        }

        if ($trano)
            $trano = " trano = '$trano' ";


        $sp = $this->db->prepare("call sp_whreturn(\"$prjKode\",'$sitKode',\"$trano\")");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();

        return $result;
    }

    function getWhbringback($prjKode = '', $sitKode = '', $trano = '') {
        if ($prjKode) {
            $prjKode = " prj_kode = '$prjKode' ";
            if ($trano)
                $trano = " and trano = '$trano' ";
        }
        if ($trano)
            $trano = " trano = '$trano' ";
        
        $sp = $this->db->prepare("call sp_whbringback(\"$prjKode\",'$sitKode',\"$trano\")");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();

        return $result;
    }

    function getWhsupplier($prjKode = '', $sitKode = '', $supKode = '', $tgl = '', $param = '', $trano = '') {
        if ($prjKode) {
            $prjKode = " prj_kode = '$prjKode' ";
            if ($trano)
                $trano = " and trano = '$trano' ";
        }

        if ($trano)
            $trano = " trano = '$trano' ";

        $sp = $this->db->prepare("call sp_whsupplier(\"$prjKode\",'$sitKode','$supKode','$tgl','$param',\"$trano\")");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();


        return $result;
    }

    function getWhsupplierprj($prjKode = '') {
        $sp = $this->db->prepare("call sp_whsupplierprj('$prjKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();

        return $result;
    }

    function getDetailpr($prjKode = '', $sitKode = '') {

        $query = $this->db->prepare("call sp_pr('$prjKode','$sitKode','')");
        $query->execute();
        $workid = $query->fetchAll();
        $query->closeCursor();
        $hasil = array();
        $query = $this->db->prepare("call sp_pr('$prjKode','$sitKode','summary')");
        $query->execute();
        $data = $query->fetchAll();
        $query->closeCursor();
        for ($j = 0; $j < count($workid); $j++) {
            $workid_cari = $workid[$j]['workid'];
            $workid_result = $this->project->getWorkDetail($workid_cari);
            $hasil[$workid_cari]['workname'] = $workid_result['workname'];
            $indeks = 0;
            foreach ($data as $key => $key2) {
                if ($data[$key]['workid'] == $workid_cari) {
                    $hasil[$workid_cari][$indeks] = $data[$key];
                    $indeks++;
                }
            }
        }

        //var_dump($result);
        return $hasil;
    }

    function getOutprpo($prjKode = '', $sitKode = '') {
        $sp = $this->db->prepare("call outprpo('$prjKode','$sitKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        if ($result) {
            $id = 1;
            foreach ($result as $k => $v) {
                $result[$k]['id'] = $id;
                $id++;
            }
        }
        $sp->closeCursor();


        return $result;
    }

    function getOutprpodetail2($prjKode = '', $sitKode = '') {
        $sp = $this->db->prepare("call outprpodetail('$prjKode','$sitKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        if ($result) {
            $id = 1;
            foreach ($result as $k => $v) {
                $result[$k]['id'] = $id;
                $id++;
            }
        }
        $sp->closeCursor();


        return $result;
    }

    function getOutprpoprj($prjKode = '') {
        $sp = $this->db->prepare("call outprpoprj('$prjKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();

        return $result;
    }

    function getOutprpoprjdetail($prjKode = '') {
        $sp = $this->db->prepare("call outprpoprjdetail('$prjKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();

        return $result;
    }

    function getMdi($prjKode = '', $sitKode = '') {
        $sp = $this->db->prepare("call sp_mdi('$prjKode','$sitKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();
        return $result;
    }

    function getDor($prjKode = '', $sitKode = '') {
        $sp = $this->db->prepare("call sp_dor('$prjKode','$sitKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();
        return $result;
    }

    function getDo($prjKode = '', $sitKode = '') {
        $sp = $this->db->prepare("call sp_do('$prjKode','$sitKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();
        return $result;
    }

    function getprpodet($prjKode = '', $sitKode = '') {
        $sp = $this->db->prepare("call outprpo('$prjKode','$sitKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        $sp->closeCursor();

        return $result;
    }

    function getWorkPr($prj_kode = '', $sit_kode = '') {
        $request = $this->getRequest();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $wpr = new Default_Models_WorkPr();

        $return['posts'] = $wpr->setWorkPr($prj_kode, $sit_kode);
        $return['count'] = count($return['posts']);

        return $return;
    }

    function isPRExecuted($trano = '') {
        $poh = new Default_Models_ProcurementPoh();
        $prh = new Default_Models_ProcurementRequestH();
        $fetch2 = $prh->fetchRow("trano = '$trano'");
        if (!$fetch2)
            return false;
        $fetch = $poh->fetchRow("pr_no = '$trano'");
        if ($fetch)
            return $fetch->toArray();
        else
            return '';
    }

    function getPODetail($trano = '') {
        $poh = new Default_Models_ProcurementPoh();
        $fetch = $poh->fetchRow("trano = '$trano'");
        if ($fetch)
            return $fetch->toArray();
        else
            return '';
    }

    function getRPIDetail($trano = '') {
        $rpih = new Default_Models_RequestPaymentInvoiceH();
        $fetch = $rpih->fetchRow("trano = '$trano'");
        if ($fetch)
            return $fetch->toArray();
        else
            return '';
    }

    function getRPIdDetail($trano = '') {
        $rpid = new Default_Models_RequestPaymentInvoice();
        $fetch = $rpid->getDetailForPayment("trano = '$trano'");
        if ($fetch)
            return $fetch->toArray();
        else
            return '';
    }

    function getASFDetail($trano = '') {
        $asfh = new Default_Models_AdvanceSettlementFormH();
        $fetch = $asfh->fetchRow("trano = '$trano'");
        if ($fetch)
            return $fetch->toArray();
        else
            return '';
    }

    function getASFSettleDetail($trano = '') {

        $sql = "SELECT SUM(COALESCE(harga,0)*COALESCE(qty,0)) AS total FROM procurement_asfdd WHERE trano = '$trano'";
        $fetch = $this->db->query($sql);
        if ($fetch)
            return $fetch->fetchAll();
        else
            return '';
    }

    function getASFCancelDetail($trano = '') {

        $sql = "SELECT SUM(COALESCE(harga,0)*COALESCE(qty,0)) AS total FROM procurement_asfddcancel WHERE trano = '$trano'";
        $fetch = $this->db->query($sql);
        if ($fetch)
            return $fetch->fetchAll();
        else
            return '';
    }

    function getARFDetails($trano = '') {
        $arfh = new Default_Models_AdvanceRequestFormH();
        $fetch = $arfh->fetchRow("trano = '$trano'");
        if ($fetch)
            return $fetch->toArray();
        else
            return '';
    }

    function getSupplierDetail($sup_kode) {
        $sup = new Default_Models_MasterSuplier();
        $fetch = $sup->fetchRow("sup_kode = '$sup_kode'");
        if ($fetch)
            return $fetch->toArray();
        else
            return '';
    }

    function getPoSummary($prj_kode = '', $sit_kode = '', $sup_kode = '', $tgl1 = '', $tgl2 = '') {
//        if ($sit_kode != '')
//        {
//            $query = "AND
//                             a.sit_kode = '$sit_kode' ";
//        }
        if ($prj_kode != '')
            $where = "a.prj_kode = '$prj_kode'";
        if ($sit_kode != '')
            $where .= " AND a.sit_kode = '$sit_kode'";
        if ($sup_kode != '') {
            if ($where == '')
                $where = "b.sup_kode = '$sup_kode'";
            else
                $where .= " AND b.sup_kode = '$sup_kode'";
        }

        if ($tgl1 != '') {
            if ($tgl2 != '') {
                if ($where == '')
                    $where = "b.tgl BETWEEN '$tgl1' AND '$tgl2' ";
                else
                    $where .= " AND (b.tgl BETWEEN '$tgl1' AND '$tgl2')";
            }
            else {
                if ($where == '')
                    $where = "b.tgl = '$tgl1'";
                else
                    $where .= " AND b.tgl = '$tgl1'";
            }
        }

        $sql = "SELECT
                    p.trano,
                    p.tgl,
                    p.prj_kode,
                    p.prj_nama,
                    p.sit_kode,
                    p.sit_nama,
                    p.workid,
                    p.workname,
                    p.val_kode,
                    SUM(p.totalIDR) as total_IDR,
                    SUM(p.totalUSD) as total_USD,
                    p.pc_nama,
                    p.rateidr,
                    SUM(p.totalUSD*p.rateidr) as totalUSD_IDR
                FROM(
                        SELECT
                            a.trano,
                            DATE_FORMAT(a.tgl, '%m/%d/%Y') as tgl,
                            a.prj_kode,
                            a.prj_nama,
                            a.sit_kode,
                            a.sit_nama,
                            a.workid,
                            a.workname,
                            a.val_kode,
                            a.qty,
                            a.harga,
                            b.rateidr,
                            (CASE a.val_kode WHEN 'IDR' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalIDR,
                            (CASE a.val_kode WHEN 'USD' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalUSD,
                            (SELECT uid FROM master_login WHERE master_login = b.user) as pc_nama
                        FROM
                            procurement_pod a
                        LEFT JOIN
                            procurement_poh b
                        ON
                            a.trano = b.trano
                        WHERE
                          $where
/*                            a.prj_kode = '$prj_kode'
                            $query*/
                        ) p
                GROUP BY p.trano
                ORDER BY p.trano";
        $fetch = $this->db->query($sql);
        if ($fetch)
            return $fetch->fetchAll();
        else
            return '';
    }

    public function getRPIPayment($docTrano, $exclude = '') {
        if ($exclude != '')
            $query .= " AND trano !='$exclude'";

        $sql = "SELECT * FROM finance_payment_rpi WHERE doc_trano='$docTrano' $query ORDER BY tgl DESC";

        $fetch = $this->db->query($sql);
        if ($fetch)
            return $fetch->fetchAll();
        else
            return '';
    }

    public function getARFPayment($docTrano, $exclude = '') {
        if ($exclude != '')
            $query .= " AND trano !='$exclude'";

        $sql = "SELECT * FROM finance_payment_arf WHERE doc_trano='$docTrano' $query ORDER BY tgl DESC";

        $fetch = $this->db->query($sql);
        if ($fetch)
            return $fetch->fetchAll();
        else
            return '';
    }

    public function getSumRPIPayment($trano) {
        $sql = "SELECT COALESCE(SUM(total_bayar),0)as total_bayar,val_kode FROM finance_payment_rpi WHERE doc_trano='$trano' GROUP BY doc_trano";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    public function getASFPayment($docTrano, $exclude = '') {
        if ($exclude != '')
            $query .= " AND trano !='$exclude'";

        $sql = "SELECT * FROM finance_settled WHERE doc_trano='$docTrano' $query ORDER BY tgl DESC";

        $fetch = $this->db->query($sql);
        if ($fetch)
            return $fetch->fetchAll();
        else
            return '';
    }

    public function getASFCancelPayment($docTrano, $exclude = '') {
        if ($exclude != '')
            $query .= " AND trano !='$exclude'";

        $sql = "SELECT * FROM finance_settledcancel WHERE doc_trano='$docTrano' $query ORDER BY tgl DESC";

        $fetch = $this->db->query($sql);
        if ($fetch)
            return $fetch->fetchAll();
        else
            return '';
    }

    public function getSumASFPayment($trano) {
        $sql = "SELECT COALESCE(SUM(total_bayar),0)as total_bayar,val_kode FROM finance_settled WHERE doc_trano='$trano' GROUP BY trano";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    public function getSumASFCancelPayment($trano) {
        $sql = "SELECT COALESCE(SUM(total_bayar),0)as total_bayar,val_kode FROM finance_settledcancel WHERE doc_trano='$trano' GROUP BY trano";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    public function getSumARFPayment($trano) {
        $sql = "SELECT COALESCE(SUM(total_bayar),0)as total_bayar,val_kode FROM finance_payment_arf WHERE doc_trano='$trano' GROUP BY doc_trano";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    public function getBudgetTypeFromPR($trano) {
        $sql = "SELECT COALESCE(budgettype,'Project') As budgettype FROM procurement_prh WHERE trano='$trano' ";

        $fetch = $this->db->query($sql)->fetch();
        return $fetch['budgettype'];
    }

    public function getSupplierName($gdgKode) {
        $sql = "SELECT gdg_nama FROM master_gudang WHERE gdg_kode='$gdgKode'";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    public function getPohDetail($trano) {
        $sql = "SELECT * FROM procurement_poh WHERE trano='$trano'";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    function getPOCustomer($prjKode, $sitKode) {
        $sql = "SELECT pocustomer, total, totalusd FROM transengineer_boq2h WHERE
                                    prj_kode='$prjKode' AND sit_kode='$sitKode'";
        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    function getKboq2($prjKode, $sitKode) {
        $sql = "SELECT COALESCE(a.total,0) AS total, COALESCE(a.totalusd,0) AS totalusd, SUM(COALESCE(b.totaltambah,0)) AS totaltambah, SUM(COALESCE(b.totaltambahusd,0)) AS totaltambahusd
                    FROM transengineer_boq2h a LEFT JOIN transengineer_kboq2h b
                    ON a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode
                    WHERE
                                    a.prj_kode='$prjKode' AND a.sit_kode='$sitKode'";
        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    function getAlamatSup($supKode) {
        $sql = "SELECT alamat2, alamat, tlp, fax
                    FROM master_suplier
                    WHERE
                                    sup_kode='$supKode'";
        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    function getSiteOverhead($sitKode, $prjKode) {
        $sql = "SELECT stsoverhead
                FROM master_site
                WHERE
                sit_kode='$sitKode' AND prj_kode = '$prjKode'";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    function getPICName($data) {
        $query = "SELECT * FROM master_login WHERE uid = '$data'";
        $fetch = $this->db->query($query);
        return $fetch->fetch();
    }

    function getManagerName($arfno) {
        $sql = "SELECT CONVERT(request, SIGNED INTEGER) AS value, request FROM procurement_arfh where trano = '$arfno'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $request = $return['request'];

        if ($return['value'] == 0) {

            $query = "SELECT * FROM master_login WHERE uid = '$request'";
            $ambil = $this->db->query($query);
            $hasil = $ambil->fetch();

            $result = $hasil['Name'];
        } else {

            $query = "SELECT * FROM master_manager WHERE mgr_kode = '$request'";
            $ambil = $this->db->query($query);
            $hasil = $ambil->fetch();

            $result = $hasil['mgr_nama'];
        }

        return $result;
    }

    function cekPayment($type, $trano) {
        switch ($type) {
            case 'PO':
                $query = "SELECT trano FROM procurement_rpih where po_no = '$trano' AND deleted=0";
                $ambil = $this->db->query($query);
                $hasil = $ambil->fetchAll();
                $totalbayar = 0;

                foreach ($hasil as $key => $val) {
                    $rpi_no = $val['trano'];

                    //foreach payment rpi, cek if stspayment y +total_bayar
                    $query2 = "SELECT total_bayar,stspayment FROM finance_payment_rpid where doc_trano = '$rpi_no' ";
                    $ambil2 = $this->db->query($query2);
                    $hasil2 = $ambil2->fetchAll();
                    foreach ($hasil2 as $k => $v) {
                        if ($v['stspayment'] == 'Y')
                            $totalbayar += $v['total_bayar'];
                    }
                }
                $result = $totalbayar;
                return $result;
                break;

            case 'RPI':
                $query2 = "SELECT stspayment FROM finance_payment_rpi where doc_trano = '$trano'";
                $ambil2 = $this->db->query($query2);
                $hasil2 = $ambil2->fetch();
                $result = $hasil2['stspayment'];
                return $result;
                break;

            case 'ARF':
                $query2 = "SELECT stspayment FROM finance_payment_arf where doc_trano = '$trano'";
                $ambil2 = $this->db->query($query2);
                $hasil2 = $ambil2->fetch();
                $result = $hasil2['stspayment'];
                return $result;
                break;

            case 'ASF':
                $query2 = "SELECT stspayment FROM finance_settled where doc_trano = '$trano'";
                $ambil2 = $this->db->query($query2);
                $hasil2 = $ambil2->fetch();
                $result = $hasil2['stspayment'];
                return $result;
                break;
        }
    }

    function cekAfe($trano) {
        $sql = "SELECT afe_no From transengineer_kboq3h WHERE afe_no = '$trano' GROUP BY afe_no";
        $ambil = $this->db->query($sql);
        $hasil = $ambil->fetch();

        if ($hasil['afe_no'] != '')
            return $hasil = 'exist';
        else
            return $hasil = '';
    }

    function cekASF($trano, $type) {
        switch ($type) {
            case 'settle' :
                $sql = "SELECT trano From procurement_asfdd WHERE trano = '$trano' GROUP BY trano";
                $ambil = $this->db->query($sql);
                $hasil = $ambil->fetch();
                break;

            case 'cancel' :
                $sql = "SELECT trano From procurement_asfddcancel WHERE trano = '$trano' GROUP BY trano";
                $ambil = $this->db->query($sql);
                $hasil = $ambil->fetch();
                break;
        }

        if ($hasil['trano'] != '')
            return $hasil = 'exist';
        else
            return $hasil = '';
    }
    
    function getoutprponew($prjKode = '', $sitKode = '') {
        $sp = $this->db->prepare("call outprponew('$prjKode','$sitKode')");
        $sp->execute();
        $result = $sp->fetchAll();
        if ($result) {
            $id = 1;
            foreach ($result as $k => $v) {
                $result[$k]['id'] = $id;
                $id++;
            }
        }
        $sp->closeCursor();
        return $result;
    }
    
    function getPorpirse($prjKode = '', $sitKode = '', $supKode='', $trano='', $cod='') {
        
        if($prjKode != '' || $prjKode != null){
            $pk = " prj_kode = '$prjKode' ";
            if($sitKode != '' || $sitKode != null){
                $sk = " AND sit_kode = '$sitKode' ";
            } else {
                $sk = "";
            }
        } else {
            $pk = ''; $sk = "";
        }
        
        $qr = "";
        
        if($supKode != '' || $supKode != null){
            if($prjKode != '' || $prjKode != null){
                $sp = "AND sup_kode = '$supKode' ";
            } else {
                $sp = "sup_kode = '$supKode' ";
                $qr = "doc_trano IN (select trano_RPI from rpi_total)";
            }
        } else {
            $sp = '';
        }
        
        if($trano != '' || $trano != null){
            if(($prjKode != '' || $prjKode != null) || ($supKode != '' || $supKode != null)){
                $tr1 = " AND trano = '$trano' ";
                $tr2 = " AND po_no = '$trano' ";
            } else {
                $tr1 = "trano = '$trano' ";
                $tr2 = "po_no = '$trano' ";
                $qr = "doc_trano IN (select trano_RPI from rpi_total)";
            }
        } else {
            $tr1 = ""; $tr2 = "";
        }
        
        if($cod){
            $cd = " a.cod = 'Y'";
        } else {
            $cd = " a.cod = 'N'";
        }
        
        $sql1 ="DROP TEMPORARY TABLE IF EXISTS po_total; 
                CREATE TEMPORARY TABLE po_total
                SELECT 
                    a.*, b.cod 
                FROM (
                    SELECT 
                        trano as trano_PO, 
                        SUM(hargaspl * qtyspl) AS total_PO,
                        val_kode
                    FROM 
                        procurement_pod
                    WHERE
                        $pk $sk $sp $tr1
                    GROUP BY trano 
                ) a
                INNER JOIN procurement_poh b 
                    ON (a.trano_PO = b.trano);";
        
        $this->db->query($sql1);
                
        $sql2 ="DROP TEMPORARY TABLE IF EXISTS rpi_total;
                CREATE TEMPORARY TABLE rpi_total
                SELECT 
                    trano as trano_RPI, 
                    po_no as trano_PO,
                    SUM(harga * qty) AS total_RPI, 
                    SUM((harga * qty) + IFNULL(ppn,0) - IFNULL(totalwht,0) - IFNULL(total_deduction,0)+ IFNULL(total_grossup,0)) 
                    as total_netRPI
                FROM 
                    procurement_rpid 
                WHERE
                    $pk $sk $sp $tr2
                GROUP BY trano;";
           
        $this->db->query($sql2);
            
        $sql3 ="DROP TEMPORARY TABLE IF EXISTS rpipayment_total; 
                CREATE TEMPORARY TABLE rpipayment_total 
                SELECT a.* 
                FROM (
                    SELECT 
                        trano as trano_PayRPI, 
                        doc_trano as trano_RPI, 
                        sum(total_bayar) as total_PayRPi, 
                        tgl as tgl_PayRPI, 
                        voc_trano as trano_VocRPI
                    FROM finance_payment_rpi 
                    WHERE 
                        $pk $sk $qr
                    GROUP BY trano
                ) a
                INNER JOIN (
                    SELECT 
                        trano
                    FROM finance_payment_voucher
                    WHERE 
                        item_type = 'RPI'
                    GROUP BY trano
                ) b 
                ON a.trano_VocRPI = b.trano;";
        $this->db->query($sql3);
        
        $sql4 ="DROP TEMPORARY TABLE IF EXISTS rpi_total2;
                CREATE TEMPORARY TABLE rpi_total2
                SELECT 
                    a.*, 
                    COALESCE(b.trano_PayRPI, '-') as trano_PayRPI,
                    COALESCE(b.total_PayRPI, 0) as total_PayRPI, 
                    COALESCE(b.tgl_PayRPI, '-') as tgl_PayRPI
                FROM 
                    rpi_total a 
                LEFT JOIN rpipayment_total b 
                    ON (a.trano_RPI = b.trano_RPI);";
            
        $this->db->query($sql4);
            
        $sql5 =" DROP TEMPORARY TABLE IF EXISTS rpi_total3;
                CREATE TEMPORARY TABLE rpi_total3
                SELECT 
                    a.*, 
                    COALESCE(b.trano_RPI, '-') as trano_RPI, 
                    COALESCE(b.total_RPI, 0) as total_RPI,
                    COALESCE(b.total_netRPI, 0) as total_netRPI, 
                    COALESCE(b.trano_PayRPI, '-') as trano_PayRPI, 
                    COALESCE(b.total_PayRPI, 0) as total_PayRPI,
                    COALESCE(b.tgl_PayRPI, '-') as tgl_PayRPI
                FROM 
                    po_total a 
                LEFT JOIN rpi_total2 b 
                    ON (a.trano_PO = b.trano_PO)
                WHERE
                    $cd
                ORDER BY trano_PO DESC, trano_RPI DESC;";
        $this->db->query($sql5);
       
        $sql6="SELECT a.*,b.item_id, b.lastApprove,b.uidApprove, b.uidNextApprove
                 FROM rpi_total3 a
                 LEFT JOIN 
                        (select  c.item_id, c.approve as lastApprove,c.uid as uidApprove, c.uid_next as uidNextApprove, c.final from
                            (SELECT * FROM erpdb.workflow_trans
                            Where $pk and item_type in ('PO', 'RPI')
                            ORDER BY date DESC) c
                        GROUP BY c.item_id) b
                ON a.trano_PO = b.item_id;";
        
        $this->db->query($sql6);
        
        $fetch = $this->db->query($sql6);     
        $datas = $fetch->fetchAll();
        return $datas;
        
    }
    
    function getArfasfrse($prjKode = '', $sitKode = '') {

        if ($sitKode) {
            $where = " AND sit_kode = '$sitKode'";
            $wherea = " AND a.sit_kode = '$sitKode'";
        }
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS arfd;
            CREATE TEMPORARY TABLE arfd
            SELECT a.*, b.*
            FROM ( 
                SELECT trano, tgl, val_kode, prj_kode, prj_nama, sit_kode, sit_nama, workid, workname, 
                kode_brg, nama_brg, SUM(qty), SUM(harga), SUm(qty * harga) as total, requester, 
                'ARF' as tipetran 
                FROM procurement_arfd 
                WHERE prj_kode = '$prjKode' $where and trano not like 'brfp%'
                GROUP BY trano, kode_brg 
                UNION 
                SELECT trano, tgl, val_kode, prj_kode, prj_nama, sit_kode, sit_nama, workid, workname, 
                kode_brg, nama_brg, SUM(qty), SUM(harga), SUm(qty * harga) as total, requester, 
                'BRF' as tipetran 
                FROM procurement_brfd 
                WHERE prj_kode = '$prjKode' $where and trano not like 'brfp%'
                GROUP BY trano, kode_brg 
            ) a INNER JOIN (
                    SELECT a.item_id, a.approve as stat_arf, COALESCE(b.statrevisi, 0) as stat_revarf FROM (
                            SELECT * FROM ( 
                                    SELECT approve, item_id
                                    FROM workflow_trans 
                                    WHERE prj_kode = '$prjKode' AND item_type IN ('ARF', 'ARFO', 'BRF') 
                                    ORDER BY item_type, item_id DESC, date DESC 
                            ) a 
                            GROUP BY item_id
                    ) a LEFT JOIN (
                            SELECT trano, statrevisi
                            FROM procurement_arfh
                            WHERE prj_kode = '$prjKode' $where 
                    ) b ON (a.item_id = b.trano)
            ) b ON (a.trano = b.item_id)
            ORDER By tgl DESC;";
        $this->db->query($sql);
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS asfddcancel;
            CREATE TEMPORARY TABLE asfddcancel
            SELECT arf_no, trano, tgl, val_kode, prj_kode, sit_kode, workid, kode_brg, SUM(qty), 
            SUM(harga), SUm(qty * harga) as total 
            FROM procurement_asfddcancel 
            WHERE prj_kode = '$prjKode' $where and trano not like 'OCA%' 
            and arf_no not like 'BRFP%'
            GROUP BY trano, arf_no, kode_brg 
            UNION 
            SELECT b.trano_ref as arf_no, a.trano, a.tgl, a.val_kode, a.prj_kode, a.sit_kode, a.workid, 
            a.kode_brg, SUM(a.qty), SUM(a.harga), SUM(a.qty * a.harga) as total 
            FROM procurement_asfddcancel a 
            INNER JOIN procurement_arfd b ON (a.arf_no = b.trano) 
            WHERE a.prj_kode = '$prjKode' $wherea and a.trano not like 'OCA%' 
            and a.arf_no like 'BRF%'
            GROUP BY a.trano, b.trano_ref, a.kode_brg;";
        $this->db->query($sql);
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS asfdd ;
            CREATE TEMPORARY TABLE asfdd
            SELECT a.*, b.approve as stat_asf, b.date as tglstat_asf 
            FROM ( 
                SELECT arf_no, trano, tgl, val_kode, prj_kode, sit_kode, workid, kode_brg, SUM(qty), 
                SUM(harga), SUm(qty * harga) as total 
                FROM procurement_asfdd 
                WHERE prj_kode = '$prjKode' $where and trano not like 'OCA%' 
                and arf_no not like 'BRFP%' 
                GROUP BY trano, arf_no, kode_brg 
                UNION 
                SELECT b.trano_ref as arf_no, a.trano, a.tgl, a.val_kode, a.prj_kode, a.sit_kode, a.workid, 
                a.kode_brg, SUM(a.qty), SUM(a.harga), SUm(a.qty * a.harga) as total 
                FROM procurement_asfdd a 
                INNER JOIN procurement_arfd b ON (a.arf_no = b.trano) 
                WHERE a.prj_kode = '$prjKode' $wherea and a.trano not like 'OCA%' 
                and a.arf_no like 'BRF%'  
                GROUP BY a.trano, b.trano_ref, a.kode_brg 
            ) a INNER JOIN ( 
                    SELECT * FROM ( 
                            SELECT approve, item_id, date 
                    FROM workflow_trans 
                    WHERE prj_kode = '$prjKode' AND item_type IN ('ASF', 'BSF', 'ASFO') 
                    ORDER BY item_type, item_id DESC, date DESC 
                    ) a 
            GROUP BY item_id ) b ON (a.trano = b.item_id);";
        $this->db->query($sql);
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS arfasf;
            CREATE TEMPORARY TABLE arfasf
            SELECT a.trano as arf_num, a.tgl as tgl_arf, a.prj_kode, a.sit_kode, a.workid, a.kode_brg, 
            a.nama_brg, a.val_kode, a.requester, COALESCE(b.trano,'-') as asf_num, 
            COALESCE(b.tgl,'-' ) as tgl_asf, a.total as total_arf, a.stat_arf, a.stat_revarf,
            COALESCE(b.total,0) as total_asf, COALESCE(datediff(b.tgl,a.tgl),'-') as aging_arf_days, 
            a.tipetran, b.stat_asf, COALESCE(b.tglstat_asf,'-' ) as tglstat_asf 
            FROM arfd a 
            LEFT JOIN asfdd b ON (a.trano = b.arf_no and a.kode_brg = b.kode_brg);";
        $this->db->query($sql);
        
        $sql = "DROP TEMPORARY TABLE IF EXISTS arfasf2;
                CREATE TEMPORARY TABLE arfasf2
                SELECT a.*, COALESCE(b.trano,'-') as asfcancel_num, COALESCE(b.total,0) as total_asfcancel 
                FROM arfasf a 
                LEFT JOIN asfddcancel b ON (a.arf_num = b.arf_no and a.asf_num = b.trano and a.kode_brg = b.kode_brg) 
                ORDER BY tipetran, arf_num DESC";
        $this->db->query($sql);
        
        
        $sql = "SELECT a.*,b.item_id, b.lastApprove,b.uidApprove, b.uidNextApprove
                 FROM arfasf2 a
                 LEFT JOIN 
                        (select  c.item_id, c.approve as lastApprove,c.uid as uidApprove, c.uid_next as uidNextApprove, c.final from
                            (SELECT * FROM erpdb.workflow_trans
                            Where prj_kode = '$prjKode' and item_type in ('ARF', 'ASF')
                            ORDER BY date DESC) c
                        GROUP BY c.item_id) b
                ON a.arf_num = b.item_id;";
        $data = $this->db->query($sql);

        $result = $data->fetchAll();
        return $result;
    }

}

?>
