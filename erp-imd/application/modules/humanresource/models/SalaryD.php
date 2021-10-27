<?php
class HumanResource_Models_SalaryD extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_sald';
    private $db;
     protected $_primary = 'trano';

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
    
    public function getSalarySummaryForCFS($prjKode='')
    {
        $gaji = $this->fetchAll("prj_kode='$prjKode'");
        if ($gaji)
        {
            $gaji = $gaji->toArray();
            $result = array();
            foreach($gaji as $k => $v)
            {
                $sitKode = $v['sit_kode'];
                $result[$sitKode] += floatval($v['harga']);
            }

            return $result;
        }
        else
            return '';
    }

    public function getSalarySummaryForCFSv2($prjKode='',$sit_Kode='',$startDate='',$endDate='')
    {
        $result = array();
        
         //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND tgl BETWEEN '$startDate' AND '$endDate' ";
        }
        
        if($sit_Kode!='' || $sit_Kode!=null)
            $sit_Kode = " AND sit_kode='$sit_Kode'";
        
        $sql0 = "SELECT (qty * harga) AS total, tgl, sit_kode FROM $this->_name WHERE prj_kode='$prjKode' $sit_Kode $sqlDate AND nama_brg='Salaries'
                    ORDER BY tgl DESC ;";
        $fetch0 = $this->db->query($sql0);
        $gaji= $fetch0->fetchAll();

        //$gaji = $this->fetchAll("prj_kode='$prjKode' $sitKode ","tgl DESC");
        //$gaji = $gaji->toArray();
        
        $tgl=explode('-',$gaji[0]['tgl']);
            
        foreach($gaji as $k => $v)
        {
            $sitKode = $v['sit_kode'];
            $result[$sitKode]['current'] += floatval($v['total']);
            if(strtotime($v['tgl'])< strtotime($tgl[0].'-'.$tgl[1].'-01'))
            {$result[$sitKode]['previous']+= floatval($v['total']);}
        }

        $sql = "SELECT (qty * harga) AS total, tgl, sit_kode FROM imderpdb.procurement_asfdd WHERE prj_kode='$prjKode' $sit_Kode $sqlDate AND nama_brg='Salaries'
                    ORDER BY tgl DESC ;";
        $fetch = $this->db->query($sql);
        $row = $fetch->fetchAll();
        
        $tgl2 = explode('-',$row[0]['tgl']);
        
        foreach ($row as $index => $v)
        {
                $sitKode = $v['sit_kode'];
                $result[$sitKode]['current'] += floatval($v['total']);
                if(strtotime($v['tgl'])< strtotime($tgl2[0].'-'.$tgl2[1].'-01'))
                {$result[$sitKode]['previous']+= floatval($v['total']);}
        }
   
        return $result;
    }
}
?>