<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/8/11
 * Time: 1:35 PM
 * To change this template use File | Settings | File Templates.
 */


class Procurement_Models_EntertainmentRequestH extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_erfh';

    protected $_primary = 'trano';
    protected $_trano;
    protected $_prj_kode;
    protected $_prj_nama;
    protected $_work_id;
    protected $_workname;
    protected $_tgl;

    private $tipe;

    protected $db;
    protected $const;

    public function __construct($tipe='P')
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');

        $this->tipe = $tipe;
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
    
    public function ViewErfFinalApprove ($offset=0,$limit=0,$dir='ASC',$sort='AH.trano',$search,$type)
    {
        $query =    "select SQL_CALC_FOUND_ROWS AH.trano,AH.prj_kode,AH.prj_nama,AH.sit_kode,AH.sit_nama,AH.tipe
                    from (procurement_erfh AH left join workflow_trans WT on AH.trano = WT.item_id)
                    left join procurement_asfh SH on AH.trano = SH.arf_no
                    where AH.tipe = '$type' $search and WT.final = 1 and WT.item_id is not null and SH.trano is null
                    group by AH.trano order by $sort $dir LIMIT $offset,$limit";

        $sql = "CREATE TEMPORARY TABLE erffinal
                SELECT SQL_CALC_FOUND_ROWS AH.trano,AH.prj_kode,AH.prj_nama,AH.sit_kode,AH.sit_nama,AH.tipe
                FROM (procurement_erfh AH left join workflow_trans WT on AH.trano = WT.item_id)
                WHERE AH.tipe = '$type' and WT.final = 1 $search and WT.item_id is not null;
        ";
//        $fetch = $this->db->query($sql);
        $sql = "SELECT AH.* FROM erffinal AH LEFT JOIN procurement_esfh SH on AH.trano = SH.erf_no
                WHERE SH.trano is null;
        ";

        $fetch = $this->db->query($query);
        $data['posts'] = $fetch->fetchAll();
        $data['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        return $data;
    }

    public function fetchAll($where=null,$order=null,$limit=null,$offset=null)
    {
        if ($this->tipe != 'P')
        {
            if ($where)
                $where .= "AND tipe = '$this->tipe'";
            else
                $where = "tipe = '$this->tipe'";
            return parent::fetchAll($where,$order,$limit,$offset);
        }
        else
            return parent::fetchAll($where,$order,$limit,$offset);
    }

    public function getPoPpn($tgl1='',$tgl2='')
    {
	    $sql = "SELECT
                        trano,
		        DATE_FORMAT(tgl,'%m/%d/%Y') as tgl_trans,
			prj_kode,
			prj_nama,
		 FROM
			procurement_erfh
		 WHERE
		 	tgl
		 BETWEEN
			'$tgl1'
		 AND
			'$tgl2' ";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }
    
    public function __name()
    {
        return $this->_name;
    }
}
