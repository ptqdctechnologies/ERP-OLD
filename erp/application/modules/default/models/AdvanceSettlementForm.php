<?php

class Default_Models_AdvanceSettlementForm extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_asfdd';

    protected $db;
    protected $const;
    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name()
    {
        return $this->_name;
    }

    public function getasfSum($prj_kode='',$sit_kode='')
    {
            if ($sit_kode != '')
                    $where = "AND a.sit_kode = '$sit_kode'";
        $sql = "
                  SELECT
                        p.trano,
                        DATE_FORMAT(p.tgl, '%m/%d/%Y') as tgl,
                        p.prj_kode,
                        p.prj_nama,
                        p.sit_kode,
                        p.sit_nama,
                        p.ket,
                        p.requestv,
                        p.balance,
                        SUM(p.total) as total,
                        (CASE 
                        WHEN p.approve = 100 THEN 'Submit'
                        WHEN p.approve = 150 THEN 'Resubmit'
                        WHEN p.approve = 200 THEN 'On Approval'
                        WHEN p.approve = 300 THEN 'Reject'
                        WHEN p.approve = 400 THEN 'Final Approve'
                        ElSE 0 END) AS status,
                        p.pc_nama
                FROM(
                        SELECT
                                a.trano,
                                a.tgl,
                                a.prj_kode,
                                a.prj_nama,
                                a.sit_kode,
                                a.sit_nama,  
                                a.totalasf as requestv,
                                (o.total -a.requestv) as balance,
                                a.arf_no,
                                z.ket,  
                                a.approve,
                                o.total,
                                (SELECT uid FROM master_login WHERE id = z.petugas) as pc_nama
                        FROM
                                procurement_asfd a
                        LEFT JOIN
                                procurement_asfh z
                        ON
                               a.trano = z.trano
                        left JOIN
                               procurement_arfh o
                        ON
                               o.trano = a.arf_no
                        WHERE
                                a.prj_kode = '$prj_kode'
                                and a.trano  LIKE 'asf%'
                                $where
                                ) p                  
                        GROUP BY p.trano
                        ORDER BY p.trano";
        
                $fetch = $this->db->query($sql);
                $result = $fetch->fetchAll();

                return $result;
    }

//    public function getasfDetail($trano='')
//    {
//        $sql = "
//                SELECT
//                        trano,
//                        DATE_FORMAT(tgl, '%m/%d/%Y') as tgl,
//                        prj_kode,
//                        prj_nama,
//                        sit_kode,
//                        sit_nama,
//                        workid,
//                        workname,
//                        kode_brg,
//                        nama_brg,
//                        qty,
//                        harga,
//                        total,
//                        val_kode
//                FROM
//                        procurement_asfdd
//                WHERE
//                        trano = '$trano'
//                ORDER BY
//                        trano ";
//
//                $fetch = $this->db->query($sql);
//                $result = $fetch->fetchAll();
//
//                return $result;
//    }

   public function getasfDetailByArfNo($ArfTrano='',$prjKode='',$sitKode = '')
    {
            if ($prjKode != '')
                    $query = " AND prj_kode ='$prjKode' ";
            if ($sitKode != '')
                    $query .= " AND sit_kode ='$sitKode' ";
            $sql = "SELECT
                                a.trano,
                                a.tgl,
                                a.prj_kode,
                                a.prj_nama,
                                a.sit_kode,
                                a.sit_nama,
                                a.ket,
                                a.total,
                                a.val_kode,
                                a.arf_no,
                                (SELECT uid FROM master_login WHERE id=a.petugas) as pc_nama
                        FROM
                                        procurement_asfh a
                                       WHERE
                                                       a.arf_no='$ArfTrano'
                                                       $query";
            $fetch = $this->db->query($sql);
                $result = $fetch->fetchAll();
                return $result;
    }
    
    public function getasfDetail($trano='',$single = false)
    {
            if (!$single)
            {
                $sql = "
                SELECT
                        p.trano,
                        DATE_FORMAT(p.tgl, '%m/%d/%Y') as tgl,
                        p.prj_kode,
                        p.prj_nama,
                        p.sit_kode,
                        p.sit_nama,
                        p.ket,
                        p.workid,
                        p.workname,
                        p.kode_brg,
                        p.nama_brg,
                        p.val_kode,
                        p.arf_no,
                        p.qty,
                        p.harga,
                        SUM(p.total) as total,
                        p.pc_nama
                FROM(
                        SELECT
                                a.trano,
                                a.tgl,
                                a.prj_kode,
                                a.prj_nama,
                                a.sit_kode,
                                a.sit_nama,
                                a.ket,
                                b.workid,
                                b.workname,
                                b.kode_brg,
                                b.nama_brg,
                                b.qty,
                                b.harga,
                                b.qty * b.harga as total,
                                b.val_kode,
                                b.trano as arf_no,
                                (SELECT uid FROM master_login WHERE id=a.petugas) as pc_nama
                        FROM
                                procurement_asfh a
                        INNER JOIN
                                procurement_arfd b
                        ON
                                b.trano = CAST('a.arf_no' AS CHAR CHARACTER SET utf8)
                        WHERE
                                a.trano = '$trano'
                                ) p                  
                        GROUP BY p.trano
                        ORDER BY p.trano";
                        $fetch = $this->db->query($sql);
                        $result = $fetch->fetchAll();
            }
            else
            {
                    $sql = "SELECT
                                *
                       FROM
                                procurement_asfdd a
                        WHERE
                                a.trano = '$trano'";
                        $fetch = $this->db->query($sql);
                        $result = $fetch->fetchAll();
            }

                return $result;
    }



    public function totalSummary($trano='')
    {
        $select = $this->select()
            ->from(array($this->_name),array(
                "SUM(qty*harga) AS total"
            )
        )
            ->where("arf_no = '$trano'")
            ->group(array("trano"));
        $data = $this->fetchRow($select);
        if ($data)
        {
            $data = $data['total'];
        }
        else
            $data = 0;

        return $data;
    }

}

?>