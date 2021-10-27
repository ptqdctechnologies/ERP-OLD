<?php
class Zend_Controller_Action_Helper_Quantity extends
                Zend_Controller_Action_Helper_Abstract
{

    private $db;

    function  __construct() {
        $this->db = Zend_Registry::get('db');
        
    }    
    
    function getUOMByProductID($kode_brg='')
    {
    	$sql = "SELECT sat_kode FROM master_barang_project_2009 WHERE 
    				kode_brg='$kode_brg'";
    	$fetch = $this->db->query($sql);
        $result = $fetch->fetch();
        
        return $result['sat_kode'];
    }

    function getPriceByProductID($kode_brg='')
    {
    	$sql = "SELECT harga_beli FROM master_barang_project_2009 WHERE
    				kode_brg='$kode_brg'";
    	$fetch = $this->db->query($sql);
        $result = $fetch->fetch();

        return $result['harga_beli'];
    }
    
    function getBoq3Quantity($action='summary',$prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	switch ($action)
    	{
    		case 'all-ori':
				$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','$action')");
				$query->execute();
				$gTotal = $query->fetchAll();
				$query->closeCursor();
            break;
         	case 'all-current':
				$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','$action')");
				$query->execute();
				$gTotal = $query->fetchAll();
				$query->closeCursor();
    		break;
    	}
    	
    	//Filter by work_id Or kode_brg
    	
    	if ($workId != '' || $kodeBrg != '')
    	{
    		$totQty = 0;
    		$totHargaIDR = 0;
    		$totHargaUSD = 0;
    		foreach ($gTotal as $key => $val)
    		{
    			if ($val['workid'] == $workId)
    			{
    				if ($kodeBrg != '')
    				{
    					$totQty = $val['qty'];
    					$totHargaIDR = $val['totalIDR'];
    					$totHargaUSD = $val['totalUSD'];	
    				}
    				else
    				{
    					$totQty += $val['qty'];
    					$totHargaIDR += $val['totalIDR'];
    					$totHargaUSD += $val['totalUSD'];
    				}
    			}
    		}
    		return array('qty' => $totQty,'totalIDR' => $totHargaIDR,'totalUSD' => $totHargaUSD);
    	}
    	
    }
    
    function getDorQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='',$PRno='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '') // && ($workId != 1100 && $workId != 2100))
    		$query .= " AND kode_brg='$kodeBrg'";
        if ($PRno != '')
    		$query .= " AND pr_no='$PRno'";
    	$sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_pointd
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }

    function getDoQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='',$DORno='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '') // && ($workId != 1100 && $workId != 2100))
    		$query .= " AND kode_brg='$kodeBrg'";
        if ($DORno != '')
    		$query .= " AND mdi_no='$DORno'";
    	$sql = "SELECT
    						SUM(qty) AS qty
                        FROM procurement_whod
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['qty'] != '')
    		return $gTotal;
    	else
    		return '';
    }


    function getPoQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";
    	$sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_pod
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }

    function getArfQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";
    	$sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_arfd
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }


    function getAsfcancelQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";
    	$sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_asfddcancel
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }

    function getAsfddQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";
    	$sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_asfdd
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }

    function getReimbursementQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";
    	$sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_reimbursementd
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }

    function getPmealQuantity($prjKode='',$sitKode='',$kodeBrg='')
    {
    	if ($kodeBrg != '')
    		$query = " AND kode_brg='$kodeBrg'";
    	$sql = "SELECT
    						SUM(qty) AS qty

                        FROM boq_dboqpasang
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['qty'] != '')
    		return $gTotal;
    	else
    		return '';
    }

    function getPrQuantityByTrano($trano='')
    {
    	$sql = "SELECT 
    				qty,
    				harga,
    				(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                    (CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                    (CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                FROM procurement_prd
                WHERE
                	trano = '$trano'";
    	$fetch = $this->db->query($sql);
    	$gTotal = $fetch->fetch();
        
    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }
    
	function getPrQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";	
    	$sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_prd
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query
                            ";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        
    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    	
    }

    function getIcanQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";
    	$sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_whbringbackd
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query
                            ";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();

    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';

    }

    function getPrOverheadQuantity($prjKode='',$sitKode='',$budgetid='')
    {
        if ($budgetid != '')
    		$query .= " AND workid='$budgetid'";

        $sql = "SELECT
                    SUM(qty) AS qty,
                    SUM((harga*qty)) AS total
                FROM procurement_prd
                WHERE
                prj_kode = '$prjKode'
                AND sit_kode = '$sitKode'
                $query
                            ";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();

    	if ($gTotal['qty'] != '' && $gTotal['total'] != '')
    		return $gTotal;
    	else
    		return '';
    }

    function getPoQuantityByTrano($trano='')
    {
    	$sql = "SELECT
    				qty,
    				harga,
    				(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                    (CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD
                FROM procurement_pod
                WHERE
                	trano = '$trano'";
    	$fetch = $this->db->query($sql);
    	$gTotal = $fetch->fetch();
    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }
    function getPoQuantityByPrno($trano='',$prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
        $query = '';
        if ($prjKode != '')
            $query = " AND prj_kode = '$prjKode'";
        if ($sitKode != '')
            $query = " AND sit_kode = '$sitKode'";
        if ($workId != '')
    		$query .= " AND workid='$workId'";
    	if ($kodeBrg != '')
    		$query .= " AND kode_brg='$kodeBrg'";
    	$sql = "SELECT SUM(a.qty) AS qty, SUM(a.totalIDR) AS totalIDR, SUM(totalHargaUSD) AS totalHargaUSD FROM ( SELECT
    				qty,
    				harga,
    				(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                    (CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                    (CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                FROM procurement_pod
                WHERE
                	pr_no = '$trano'
                	$query
                ) a ";
    	$fetch = $this->db->query($sql);
    	$gTotal = $fetch->fetch();
    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }







	function getRpiQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";	
    	$sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_rpid
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query
                            ";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        
    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    	
    }
    
	function getMdiQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";	
    	$sql = "SELECT
    						SUM(qty) AS qty
                        FROM procurement_pointd
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query
                            ";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        
    	if ($gTotal['qty'] != '')
    		return $gTotal;
    	else
    		return '';
    	
    }
    
	function getMdoQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";	
    	$sql = "SELECT
    						SUM(qty) AS qty
                        FROM procurement_mdod
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query
                            ";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        
    	if ($gTotal['qty'] != '')
    		return $gTotal;
    	else
    		return '';
    	
    }

    function getPoSummary($trano='',$prjKode='',$sitKode='',$workid='',$kode_brg='',$prNo='')
    {
      if ($prjKode != '')
			$query = " AND prj_kode='$prjKode'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		if ($workid != '')
			$query .= " AND workid='$workid'";
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";
        if ($prNo != '')
			$query .= " AND pr_no='$prNo'";

        $sql = "SELECT
							*
                FROM
                	procurement_pod
               	WHERE
               		trano = '$trano' $query";
		$fetch = $this->db->query($sql);
		if ($fetch->rowCount() == 1)
        	$gTotal = $fetch->fetch();
        else
        	$gTotal = $fetch->fetchAll();

    	if ($gTotal['qty'] != '' && $gTotal['harga'] != '' && $gTotal['total'] != '')
    		return $gTotal;
    	else
    		return '';

    }
    
	function getPoRPIQuantity($trano='',$prjKode='',$sitKode='',$workid='',$kode_brg='',$prNo='')
	{
		if ($prjKode != '')
			$query = " AND prj_kode='$prjKode'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		if ($workid != '')
			$query .= " AND workid='$workid'";
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";
        if ($prNo != '')
			$query .= " AND pr_no='$prNo'";

			
		$sql = "SELECT 
							SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                FROM
                	procurement_rpid
               	WHERE 
               		po_no = '$trano' $query";
		$fetch = $this->db->query($sql);
		if ($fetch->rowCount() == 1)
        	$gTotal = $fetch->fetch();
        else
        	$gTotal = $fetch->fetchAll();
        	
    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
	}

    function getDetailPoRPIQuantity($exclude='',$trano='',$prjKode='',$sitKode='',$workid='',$kode_brg='',$prNo='')
	{
		if ($prjKode != '')
			$query = " AND prj_kode='$prjKode'";
        if ($exclude != '')
			$query = " AND trano != '$exclude'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		if ($workid != '')
			$query .= " AND workid='$workid'";
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";
        if ($prNo != '')
			$query .= " AND pr_no='$prNo'";

		$sql = "SELECT
					SUM(qty) as qty,
					SUM(COALESCE((harga * qty),0)) as total,
					val_kode
                FROM
                	procurement_rpid
               	WHERE
               		po_no = '$trano' $query GROUP BY trano ORDER BY tgl ASC";
		$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetchAll();

    	return $gTotal;
    	
	}
	
	function getPRPOQuantity($trano='',$prjKode='',$sitKode='',$workid='',$kode_brg='')
	{
		if ($prjKode != '')
			$query = " AND prj_kode='$prjKode'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		if ($workid != '')
			$query .= " AND workid='$workid'";
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";
			
		$sql = "SELECT 
							SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                FROM
                	procurement_pod
               	WHERE 
               		pr_no = '$trano' $query";
		$fetch = $this->db->query($sql);
		
		if ($fetch->rowCount() == 1)
        	$gTotal = $fetch->fetch();
        else
        	$gTotal = $fetch->fetchAll();
    	
    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
	}

    function getPoIsuppQuantity($trano='',$prjKode='',$sitKode='',$workid='',$kode_brg='')
	{
		if ($prjKode != '')
			$query = " AND prj_kode='$prjKode'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		if ($workid != '')
			$query .= " AND workid='$workid'";
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";

		$sql = "SELECT
                    SUM(qty) AS qty,
                    SUM((harga*qty)) AS totalHarga
                FROM
                	procurement_whsupplierd
               	WHERE
               		po_no = '$trano' $query";
		$fetch = $this->db->query($sql);

		if ($fetch->rowCount() == 1)
        	$gTotal = $fetch->fetch();
        else
        	$gTotal = $fetch->fetchAll();

    	if ($gTotal['qty'] != '' && ($gTotal['totalHarga'] != ''))
    		return $gTotal;
    	else
    		return '';


	}

	function getPRDORQuantity($trano='',$prjKode='',$sitKode='',$workid='',$kode_brg='')
	{
		if ($prjKode != '')
			$query = " AND prj_kode='$prjKode'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		if ($workid != '')
			$query .= " AND workid='$workid'";
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";

		$sql = "SELECT
			SUM(qty) AS qty
                FROM
                	procurement_pointd
               	WHERE
               		pr_no = '$trano' $query";
		$fetch = $this->db->query($sql);
		if ($fetch->rowCount() == 1)
        	$gTotal = $fetch->fetch();
        else
        	$gTotal = $fetch->fetchAll();

    	if ($gTotal['qty'] != '' )
    		return $gTotal;
    	else
    		return '';

	}


	function getPoRPIQuantityByDate($trano='',$rpiNo='')
	{
       if ($rpiNo != '')
        $query .= " AND trano !='$rpiNo'";

		$sql = "SELECT 
							trano,
							tgl,
							SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                FROM
                	procurement_rpid
               	WHERE 
               		po_no = '$trano' $query
                GROUP BY 
                	tgl";
		$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetchAll();
    	
    	return $gTotal;
    }

    function getPoRPIQuantityByRPINo($trano='',$rpiNo='')
	{
       if ($rpiNo != '')
        $query .= " AND trano !='$rpiNo'";

		$sql = "SELECT
							trano,
							tgl,
							SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                FROM
                	procurement_rpid
               	WHERE
               		po_no = '$trano' $query
                GROUP BY
                	trano";
		$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetchAll();

    	return $gTotal;
    }

    function getArfAsfQuantity($trano='',$prjKode='',$sitKode='',$workid='',$kode_brg='')
	{
		if ($prjKode != '')
			$query = " AND prj_kode='$prjKode'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		if ($workid != '')
			$query .= " AND workid='$workid'";
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";

		$sql = "SELECT
							SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                FROM
                	procurement_asfdd
               	WHERE
               		arf_no = '$trano' $query";
		$fetch = $this->db->query($sql);

		if ($fetch->rowCount() == 1)
        	$gTotal = $fetch->fetch();
        else
        	$gTotal = $fetch->fetchAll();

    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';


	}

function getArfAsfcancelQuantity($trano='',$prjKode='',$sitKode='',$workid='',$kode_brg='')
	{
		if ($prjKode != '')
			$query = " AND prj_kode='$prjKode'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		if ($workid != '')
			$query .= " AND workid='$workid'";
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";

		$sql = "SELECT
							SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                FROM
                	procurement_asfddcancel
               	WHERE
               		arf_no = '$trano' $query";
		$fetch = $this->db->query($sql);

		if ($fetch->rowCount() == 1)
        	$gTotal = $fetch->fetch();
        else
        	$gTotal = $fetch->fetchAll();

    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';


	}

    function getArfhTotal($trano='')
	{
		$sql = "SELECT
					total
                FROM
                	procurement_arfh
               	WHERE
               		trano = '$trano' ";
		$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();

        if ($gTotal['total'])
    	    return $gTotal;
        else
            return '';
    }

    function getAsfddList($trano='',$arf_no='',$prjKode='',$sitKode='',$workid='',$kode_brg='')
	{
        if ($arf_no != '')
			$query .= " AND arf_no='$arf_no'";
		if ($prjKode != '')
			$query .= " AND prj_kode='$prjKode'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		if ($workid != '')
			$query .= " AND workid='$workid'";
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";

        $sql = "SELECT
                    *
                FROM
                     procurement_asfdd
                WHERE
                    trano = '$trano' $query ";
        $fetch = $this->db->query($sql);

        if ($fetch->rowCount() >= 1)
        {
            if ($fetch->rowCount() == 1)
                $result = $fetch->fetchAll();
            elseif ($fetch->rowCount() > 1)
                $result = $fetch->fetchAll();

            return $result;
           
        }
        else
            return '';
    }

    function getAsfddCancelList($trano='',$arf_no='',$prjKode='',$sitKode='',$workid='',$kode_brg='')
	{
        if ($arf_no != '')
			$query .= " AND arf_no='$arf_no'";
		if ($prjKode != '')
			$query = " AND prj_kode='$prjKode'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		if ($workid != '')
			$query .= " AND workid='$workid'";
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";

        $sql = "SELECT
                    *
                FROM
                     procurement_asfddcancel
                WHERE
                    trano = '$trano' $query ";
        $fetch = $this->db->query($sql);
           
        if ($fetch->rowCount() >= 1)
        {
            if ($fetch->rowCount() == 1)
                $result = $fetch->fetchAll();
            elseif ($fetch->rowCount() > 1)
                $result = $fetch->fetchAll();
            return $result;

        }
        else
            return '';
    }

    function getBoq3PmealQuantity($trano='',$prjKode='',$sitKode='',$kode_brg='')
	{
		if ($prjKode != '')
			$query = " prj_kode='$prjKode'";
		if ($sitKode != '')
			$query .= " AND sit_kode='$sitKode'";
		
		if ($kode_brg != '')
			$query .= " AND kode_brg='$kode_brg'";

//		$sql = "SELECT
//							SUM(qty) AS qty,
//    						SUM((harga_borong*qty)) AS totalHarga
//
//                FROM
//                	boq_dboqpasang
//               	WHERE
//               		boqtran = '$trano' $query";

        $sql = "SELECT
                            SUM(qty) AS qty,
                            SUM((harga_borong*qty)) AS totalHarga

                FROM
                    boq_dboqpasang
                WHERE
                    $query";
        
		$fetch = $this->db->query($sql);

		if ($fetch->rowCount() == 1)
        	$gTotal = $fetch->fetch();
        else
        	$gTotal = $fetch->fetchAll();
    	if ($gTotal['qty'] != '' && $gTotal['totalHarga'] != '')
    		return $gTotal;
    	else
    		return '';


	}

    function getLastRPI($trano='')
    {
        $sql = "SELECT
                    SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                    SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                    SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                FROM
                    procurement_rpid
                WHERE
                    trano = '$trano'";

        $fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['totalIDR'] != '' && $gTotal['totalUSD'] != '')
            return $gTotal;
        else
            return '';
    }

    function getSalQuantity($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND kode_brg='$kodeBrg'";
    	$sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(harga*qty) AS totalIDR
                        FROM procurement_sald
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }

    function getPrQuantityLast($prjKode='',$sitKode='',$workId='',$kodeBrg='')
    {
    	if ($workId != '')
    		$query = " AND a.workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100))
    		$query .= " AND a.kode_brg='$kodeBrg'";
    	$sql = "SELECT
    						a.qty,
    						a.harga,
    						a.jumlah
                        FROM procurement_prd a
                        LEFT JOIN procurement_prh b
                        ON a.trano = b.trano
                        WHERE
                            a.prj_kode = '$prjKode'
                            AND a.sit_kode = '$sitKode'
                            $query
                        ORDER BY b.tgl DESC,b.trano DESC
                            ";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();

    	if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';

    }
}