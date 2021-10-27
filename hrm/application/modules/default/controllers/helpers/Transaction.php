<?php
/* 
	Created @ Mei 06, 2010 by Haryadi
 */
	
class Zend_Controller_Action_Helper_Transaction extends
                Zend_Controller_Action_Helper_Abstract
{
	
    private $db;
	private $project;
    function  __construct() {
        $this->db = Zend_Registry::get('db');
        $this->project = Zend_Controller_Action_HelperBroker::getStaticHelper('project');
    }    


    function getArfsummary()
    { 
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

    function getArfDetail()
    {
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

    function getArfasf($prjKode='')
    {    
            $sp = $this->db->prepare("call sp_arfhasfd('$prjKode','')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();
        
        return $result;
    }

    function getPorpi($prjKode='',$sitKode='',$supKode='',$trano='',$param='')
    {    
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
            $where2 = "
                b.prj_kode = '$prjKode'
              AND
                b.sit_kode = '$sitKode'";
        if ($supKode != '')
        {
            if ($where != '')
            {
                $where .= " AND a.sup_kode = '$supKode'";
                $where2 .= " AND b.sup_kode = '$supKode'";
            }
            else
            {
                $where = " a.sup_kode = '$supKode'";
                $where2 = " b.sup_kode = '$supKode'";
            }
        }
        if ($trano != '')
        {
            if ($where != '')
            {
                $where .= " AND a.trano = '$trano'";
                $where2 .= " AND b.po_no = '$trano'";

            }
            else
            {
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


     function getOutprpodetail($prjKode='',$sitKode='')
    {
            $sp = $this->db->prepare("call outprpo('$prjKode','$sitKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();

        return $result;
    }

   function getMdimdo($prjKode='',$sitKode='')
    {
            $sp = $this->db->prepare("call sp_mdimdo('$prjKode','$sitKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();
            
//        var_dump($result);

        return $result;

     }

    function getdortodo($prjKode='',$sitKode='',$group=true)
    {
        if ($group == 'false')
            $group = false;
        if ($group)
        {
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
                        a.qty AS qty_dor
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
            foreach ($fetch as $k => $v)
            {
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
        else
        {
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
                a.qty AS qty_dor,
                COALESCE(b.qty,0) AS qty_do,
                (COALESCE(a.qty,0) - COALESCE(b.qty,0)) as balance,
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

    function getMdodo($prjKode='',$sitKode='')
    {
            $sp = $this->db->prepare("call sp_mdodo('$prjKode','$sitKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();

//        var_dump($result);
            
        return $result;

     }


    function getWhreturn($prjKode='',$sitKode='')
    {

            //$sp = $this->db->prepare("call sp_whreturn('$prjKode','$sitKode','$stgl1','$stgl2')");
            $sp = $this->db->prepare("call sp_whreturn('$prjKode','$sitKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();

        	return $result;
    }

    function getWhbringback($prjKode='',$sitKode='')
    {
            $sp = $this->db->prepare("call sp_whbringback('$prjKode','$sitKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();
			
            return $result;
    }
    
    function getWhsupplier($prjKode='',$sitKode='',$supKode='',$tgl='',$param='')
    {
            $sp = $this->db->prepare("call sp_whsupplier('$prjKode','$sitKode','$supKode','$tgl','$param')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();

        
        return $result;
    }

        function getWhsupplierprj($prjKode='')
    {
            $sp = $this->db->prepare("call sp_whsupplierprj('$prjKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();

        return $result;
    }

    function getDetailpr($prjKode='',$sitKode='')
    {

            $query=$this->db->prepare("call sp_pr('$prjKode','$sitKode','')");
					$query->execute();
					$workid = $query->fetchAll();
					$query->closeCursor();
					$hasil = array();
					$query=$this->db->prepare("call sp_pr('$prjKode','$sitKode','summary')");
					$query->execute();
                    $data = $query->fetchAll();
					$query->closeCursor();
					for($j=0;$j<count($workid);$j++)
                    {
                    	$workid_cari = $workid[$j]['workid'];
                    	$workid_result = $this->project->getWorkDetail($workid_cari);
                    	$hasil[$workid_cari]['workname'] = $workid_result['workname'];
						$indeks = 0;
                    	foreach ($data as $key => $key2)
                    	{
                    		if ($data[$key]['workid'] == $workid_cari)
                    		{
                    			$hasil[$workid_cari][$indeks] = $data[$key];
                    			$indeks++;
                    		}
                    	}
                    }

        //var_dump($result);
        return $hasil;
    }

    function getOutprpo($prjKode='',$sitKode='')
    {
            $sp = $this->db->prepare("call outprpo('$prjKode','$sitKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            if ($result)
            {
                $id = 1;
                foreach($result as $k => $v)
                {
                    $result[$k]['id'] = $id;
                    $id++;
                }
            }
            $sp->closeCursor();

        
        return $result;

     }
    function getOutprpoprj($prjKode='')
    {
            $sp = $this->db->prepare("call outprpoprj('$prjKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();

            return $result;

     }

    function getMdi($prjKode='',$sitKode='')
    {
            $sp = $this->db->prepare("call sp_mdi('$prjKode','$sitKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();
        return $result;

     }

    function getDor($prjKode='',$sitKode='')
    {
            $sp = $this->db->prepare("call sp_dor('$prjKode','$sitKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();
        return $result;

     }

    function getDo($prjKode='',$sitKode='')
    {
            $sp = $this->db->prepare("call sp_do('$prjKode','$sitKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();
        return $result;

     }


     function getprpodet($prjKode='',$sitKode='')
    {
            $sp = $this->db->prepare("call outprpo('$prjKode','$sitKode')");
            $sp->execute();
            $result = $sp->fetchAll();
            $sp->closeCursor();

        return $result;
    }


    function getWorkPr($prj_kode='',$sit_kode='')
    {
        $request = $this->getRequest();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $wpr = new Default_Models_WorkPr();

        $return['posts'] = $wpr->setWorkPr($prj_kode,$sit_kode);
        $return['count'] = count($return['posts']);

        return $return;
    }
    
    function isPRExecuted($trano='')
    {
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

    function getPODetail($trano='')
    {
    	$poh = new Default_Models_ProcurementPoh();
    	$fetch = $poh->fetchRow("trano = '$trano'");
        if ($fetch)
	        return $fetch->toArray();
	    else
	    	return '';
    }

    function getRPIDetail($trano='')
    {
    	$rpih = new Default_Models_RequestPaymentInvoiceH();
    	$fetch = $rpih->fetchRow("trano = '$trano'");
        if ($fetch)
	        return $fetch->toArray();
	    else
	    	return '';
    }

    function getRPIdDetail($trano='')
    {
    	$rpid = new Default_Models_RequestPaymentInvoice();
    	$fetch = $rpid->getDetailForPayment("trano = '$trano'");
        if ($fetch)
	        return $fetch->toArray();
	    else
	    	return '';
    }

    function getASFDetail($trano='')
    {
    	$asfh = new Default_Models_AdvanceSettlementFormH();
    	$fetch = $asfh->fetchRow("trano = '$trano'");
        if ($fetch)
	        return $fetch->toArray();
	    else
	    	return '';
    }

    function getASFSettleDetail($trano='')
    {

        $sql = "SELECT SUM(COALESCE(harga,0)*COALESCE(qty,0)) AS total FROM procurement_asfdd WHERE trano = '$trano'";
    	$fetch = $this->db->query($sql);
        if ($fetch)
	        return $fetch->fetchAll();
	    else
	    	return '';
    }

    function getASFCancelDetail($trano='')
    {

        $sql = "SELECT SUM(COALESCE(harga,0)*COALESCE(qty,0)) AS total FROM procurement_asfddcancel WHERE trano = '$trano'";
    	$fetch = $this->db->query($sql);
        if ($fetch)
	        return $fetch->fetchAll();
	    else
	    	return '';
    }

     function getARFDetails($trano='')
    {
    	$arfh = new Default_Models_AdvanceRequestFormH();
    	$fetch = $arfh->fetchRow("trano = '$trano'");
        if ($fetch)
	        return $fetch->toArray();
	    else
	    	return '';
    }

    function getSupplierDetail($sup_kode)
    {
    	$sup = new Default_Models_MasterSuplier();
    	$fetch = $sup->fetchRow("sup_kode = '$sup_kode'");
        if ($fetch)
	        return $fetch->toArray();
	    else
	    	return '';
    }

    function getPoSummary($prj_kode='',$sit_kode='',$sup_kode='',$tgl1='',$tgl2='')
    {
//        if ($sit_kode != '')
//        {
//            $query = "AND
//                             a.sit_kode = '$sit_kode' ";
//        }
        if ($prj_kode != '')
    		$where = "a.prj_kode = '$prj_kode'";
    	if ($sit_kode != '')
    		$where .= " AND a.sit_kode = '$sit_kode'";
    	if ($sup_kode != '')
        {
            if ($where == '')
    		    $where = "b.sup_kode = '$sup_kode'";
            else
                $where .= " AND b.sup_kode = '$sup_kode'";
        }

        if ($tgl1 != '')
        {
            if ($tgl2 != '')
            {
                if ($where == '')
                    $where = "b.tgl BETWEEN '$tgl1' AND '$tgl2' ";
                else
                    $where .= " AND (b.tgl BETWEEN '$tgl1' AND '$tgl2')";
            }
            else
            {
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

    public function getRPIPayment($docTrano,$exclude='')
    {
         if ($exclude != '')
            $query .= " AND trano !='$exclude'";

        $sql = "SELECT * FROM finance_payment_rpi WHERE doc_trano='$docTrano' $query ORDER BY tgl DESC";

        $fetch = $this->db->query($sql);
        if ($fetch)
	        return $fetch->fetchAll();
	    else
	    	return '';
    }

    public function getARFPayment($docTrano,$exclude='')
    {
        if ($exclude != '')
            $query .= " AND trano !='$exclude'";

        $sql = "SELECT * FROM finance_payment_arf WHERE doc_trano='$docTrano' $query ORDER BY tgl DESC";

        $fetch = $this->db->query($sql);
        if ($fetch)
	        return $fetch->fetchAll();
	    else
	    	return '';
    }

    public function getSumRPIPayment($trano)
    {
        $sql = "SELECT COALESCE(SUM(total_bayar),0)as total_bayar,val_kode FROM finance_payment_rpi WHERE doc_trano='$trano' GROUP BY doc_trano";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

     public function getASFPayment($docTrano,$exclude='')
    {
         if ($exclude != '')
            $query .= " AND trano !='$exclude'";

        $sql = "SELECT * FROM finance_settled WHERE doc_trano='$docTrano' $query ORDER BY tgl DESC";

        $fetch = $this->db->query($sql);
        if ($fetch)
	        return $fetch->fetchAll();
	    else
	    	return '';
    }

    public function getASFCancelPayment($docTrano,$exclude='')
    {
         if ($exclude != '')
            $query .= " AND trano !='$exclude'";

        $sql = "SELECT * FROM finance_settledcancel WHERE doc_trano='$docTrano' $query ORDER BY tgl DESC";

        $fetch = $this->db->query($sql);
        if ($fetch)
	        return $fetch->fetchAll();
	    else
	    	return '';
    }

    public function getSumASFPayment($trano)
    {
        $sql = "SELECT COALESCE(SUM(total_bayar),0)as total_bayar,val_kode FROM finance_settled WHERE doc_trano='$trano' GROUP BY trano";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

     public function getSumASFCancelPayment($trano)
    {
        $sql = "SELECT COALESCE(SUM(total_bayar),0)as total_bayar,val_kode FROM finance_settledcancel WHERE doc_trano='$trano' GROUP BY trano";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    public function getSumARFPayment($trano)
    {
        $sql = "SELECT COALESCE(SUM(total_bayar),0)as total_bayar,val_kode FROM finance_payment_arf WHERE doc_trano='$trano' GROUP BY doc_trano";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    public function getBudgetTypeFromPR($trano)
    {
        $sql = "SELECT COALESCE(budgettype,'Project') As budgettype FROM procurement_prh WHERE trano='$trano' ";

        $fetch = $this->db->query($sql)->fetch();
        return $fetch['budgettype'];
    }

    public function getSupplierName($gdgKode)
    {
        $sql = "SELECT gdg_nama FROM master_gudang WHERE gdg_kode='$gdgKode'";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    public function getPohDetail($trano)
    {
        $sql = "SELECT * FROM procurement_poh WHERE trano='$trano'";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    function getPOCustomer($prjKode,$sitKode)
    {
    	$sql = "SELECT pocustomer, total, totalusd FROM transengineer_boq2h WHERE
    				prj_kode='$prjKode' AND sit_kode='$sitKode'";
    	$fetch = $this->db->query($sql);
        return $fetch->fetch();


    }

    function getKboq2($prjKode,$sitKode)
    {
        $sql = "SELECT COALESCE(a.total,0) AS total, COALESCE(a.totalusd,0) AS totalusd, SUM(COALESCE(b.totaltambah,0)) AS totaltambah, SUM(COALESCE(b.totaltambahusd,0)) AS totaltambahusd
                    FROM transengineer_boq2h a LEFT JOIN transengineer_kboq2h b
                    ON a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode
                    WHERE
    				a.prj_kode='$prjKode' AND a.sit_kode='$sitKode'";
    	$fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    function getAlamatSup($supKode)
    {
        $sql = "SELECT alamat2, alamat, tlp, fax
                    FROM master_suplier
                    WHERE
    				sup_kode='$supKode'";
    	$fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    function getSiteOverhead($sitKode,$prjKode)
    {
        $sql = "SELECT stsoverhead
                FROM master_site
                WHERE
                sit_kode='$sitKode' AND prj_kode = '$prjKode'";

        $fetch = $this->db->query($sql);
        return $fetch->fetch();
    }

    function getPICName($data)
    {
         $query = "SELECT * FROM master_login WHERE uid = '$data'";
         $fetch = $this->db->query($query);
         return $fetch->fetch();
    }

    function getManagerName($arfno)
    {
        $sql = "SELECT CONVERT(request, SIGNED INTEGER) AS value, request FROM procurement_arfh where trano = '$arfno'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $request = $return['request'];

        if ($return['value'] == 0)
        {

            $query = "SELECT * FROM master_login WHERE uid = '$request'";
            $ambil = $this->db->query($query);
            $hasil = $ambil->fetch();

            $result = $hasil['Name'];


        }
        else{

            $query = "SELECT * FROM master_manager WHERE mgr_kode = '$request'";
            $ambil = $this->db->query($query);
            $hasil = $ambil->fetch();

            $result = $hasil['mgr_nama'];

        }

        return $result;
    }

    function cekPayment($type,$trano)
    {
        switch($type)
        {
            case 'PO':
                $query = "SELECT trano FROM procurement_rpih where po_no = '$trano'";
                 $ambil = $this->db->query($query);
                 $hasil = $ambil->fetchAll();

                foreach ($hasil as $key => $val)
                {
                $rpi_no = $hasil[$key]['trano'];


                $query2 = "SELECT stspayment FROM finance_payment_rpi where doc_trano = '$rpi_no' ";
                $ambil2 = $this->db->query($query2);
                $hasil2 = $ambil2->fetch();
                $result = $hasil2['stspayment'];
                  
                }
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

    function cekAfe($trano)
    {
        $sql = "SELECT afe_no From transengineer_kboq3h WHERE afe_no = '$trano' GROUP BY afe_no";
        $ambil = $this->db->query($sql);
        $hasil = $ambil->fetch();

        if($hasil['afe_no'] != '')
            return $hasil='exist';
        else
            return $hasil='';

    }

    function cekASF($trano,$type)
    {
        switch ($type)
        {
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

        if($hasil['trano'] != '')
            return $hasil='exist';
        else
            return $hasil='';

    }

}

?>