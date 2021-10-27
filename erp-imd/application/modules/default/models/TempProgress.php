<?php

class Default_Models_TempProgress extends Zend_Db_Table_Abstract
{
    protected $_name = 'temp_progress';

    protected $db;
    protected $const;
    private $budget;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
        $this->budget = new Default_Models_Budget();
    }

    public function getProgress($prjKode='',$sitKode='',$force=false)
    {

        $today = new DateTime(date("Y-m-d H:i:s"));
        $expire = new DateTime(date("Y-m-d H:i:s"));
        $expire->add(new DateInterval("PT4H"));
        $create = $today->format("Y-m-d H:i:s");
        $update = $today->format("Y-m-d H:i:s");
        $expire = $expire->format("Y-m-d H:i:s");

        Zend_Loader::loadClass('Zend_Json');
        if ($sitKode != '')
            $where = " AND sit_kode = '$sitKode'";
        $sql = "SELECT * FROM temp_progress WHERE prj_kode = '$prjKode' $where;";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetch();
        $exist = false;
        if ($fetch)
        {
            $id = $fetch['id'];
            $updating = intval($fetch['is_updating']);
            $expiretime = new DateTime($fetch['expire_time']);
            if (($updating == 0 || $force) && ($expiretime < $today))
            {
                try
                {
                    $this->db->beginTransaction();
                    $this->update(array("is_updating" => 1),"id = $id");
                    $this->db->commit();
                }
                 catch(Exception $e)
                {
                    $error_result = Array();
                    $message = $e->getMessage();
                    $code = $e->getCode();
                    $error_result[0] = $message;
                    $error_result[1] = $code;

                    var_dump($error_result);die;
                }
                $exist = true;
            }
            else
            {
                $fetch['data'] = Zend_Json::decode($fetch['data']);
                return $fetch;
            }
        }
        //BEGIN TRANSACTION ON DATABASE CONNECTION
        try
        {
            $result['boq3'] = $this->budget->getBoq3All('summary-all',$prjKode,$sitKode,0,0,true);
            $result['boq2'] = $this->budget->getBoq2All('summary-current',$prjKode,$sitKode);
            $result['arf'] = $this->budget->getArfd('summary',$prjKode,$sitKode);
            $result['asf'] = $this->budget->getAsfdd('summary',$prjKode,$sitKode);
            $result['asfc'] = $this->budget->getAsfddCancel('summary',$prjKode,$sitKode);
            $result['dor'] = $this->budget->getDor('summary',$prjKode,$sitKode);
            $result['do'] = $this->budget->getDo('summary',$prjKode,$sitKode);
            $result['po'] = $this->budget->getPod('summary',$prjKode,$sitKode);
            $result['rpi'] = $this->budget->getRpid('summary',$prjKode,$sitKode);
            $result['paymentrpi'] = $this->budget->getPaymentRPId('summary',$prjKode,$sitKode);
            $result['paymentasf'] = $this->budget->getPaymentAsfdd('summary',$prjKode,$sitKode);
            $result['piecemeal'] = $this->budget->getPiecemeal('summary',$prjKode,$sitKode);

            $LeftOver = $this->budget->getLeftOver('summary', $prjKode,$sitKode);
            $Cancel = $this->budget->getCancel('summary', $prjKode,$sitKode);

            $result['materialreturn'] = array(
                "totalIDR" => floatval($LeftOver['totalIDR']) + floatval($Cancel['totalIDR']),
                "totalHargaUSD" => floatval($LeftOver['totalHargaUSD']) + floatval($Cancel['totalHargaUSD'])
            );

            $result['cost'] = array(
                "totalIDR" => (floatval($result['asf']['totalIDR']) + floatval($result['rpi']['totalIDR']) + floatval($result['do']['totalIDR']) + floatval($result['piecemeal']['totalPieceMeal'])) - floatval($result['materialreturn']['totalIDR']),
                "totalHargaUSD" => (floatval($result['asf']['totalHargaUSD']) + floatval($result['rpi']['totalHargaUSD']) + floatval($result['do']['totalHargaUSD'])) - floatval($result['materialreturn']['totalHargaUSD'])
            );

            $result['costinprocess'] = array(
                "totalIDR" => floatval($result['po']['totalIDR']) + floatval($result['arf']['totalIDR']) ,
                "totalHargaUSD" => floatval($result['po']['totalHargaUSD']) + floatval($result['arf']['totalHargaUSD'])
            );
            $json = Zend_Json::encode($result);

            if ($exist)
            {
                $this->db->beginTransaction();
                $arrayInsert = array(
                    "data" => $json,
                    "update_time" => $update,
                    "expire_time" => $expire,
                    "is_updating" => 0
                );
                $this->update($arrayInsert,"id = $id");
                $this->db->commit();
            }
            else
            {
                $this->db->beginTransaction();
                //SET QUERY YOU WISH TO EXECUTE
                $arrayInsert = array(
                    "prj_kode" => $prjKode,
                    "sit_kode" => $sitKode,
                    "data" => $json,
                    "creation_time" => $create,
                    "update_time" => $update,
                    "expire_time" => $expire
                );
                $this->insert($arrayInsert);
                $query = $this->db->commit();
            }
            //COMMIT QUERY TO DATABASE
            $arrayInsert['data'] = $result;
            return $arrayInsert;
        }
        catch(Exception $e)
        {
            $this->db->rollBack();
            //ROLLBACK IF TRANSACTION FAILS

            $error_result = Array();
            $message = $e->getMessage();
            $code = $e->getCode();
            $error_result[0] = $message;
            $error_result[1] = $code;

            var_dump($e);die;
        }
    }

}
?>