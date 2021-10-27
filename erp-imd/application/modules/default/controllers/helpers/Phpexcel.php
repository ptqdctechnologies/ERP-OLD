<?php

class Zend_Controller_Action_Helper_Phpexcel extends
Zend_Controller_Action_Helper_Abstract {

    private $phpexcel;
    private $objPHPExcel;
    private $objWorksheet;
    private $masterWork;
    private $masterBarang;

    function __construct() {
        $this->session = new Zend_Session_Namespace('login');

        $this->db = Zend_Registry::get('db');
        $this->phpexcel = Zend_Registry::get('phpexcel');

        include_once($this->phpexcel . "PHPExcel.php");
        require_once $this->phpexcel . 'PHPExcel/IOFactory.php';

        $this->masterWork = new Default_Models_MasterWork();
        $this->masterBarang = new Default_Models_MasterBarang();
    }

    function loadFile($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        $this->objPHPExcel = PHPExcel_IOFactory::load($filePath);
        $this->objWorksheet = $this->objPHPExcel->getActiveSheet();
        return true;
    }

    function readBOQ3FilePerRow($filePath, $idFile) {
        $result = array();
        $ceks = array();
        $rows = 1;
        $cols = 1;
        $indeks = 0;
        $fetch = false;

        if ($this->loadFile($filePath)) {
            foreach ($this->objWorksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $cellValue = $cell->getValue();
                    $cellValue = str_replace("\r", "", $cellValue);
                    $cellValue = str_replace("\n", "", $cellValue);
                    $cellValue = str_replace("\t", "", $cellValue);
                    $cellValue = str_replace("\"", " inch", $cellValue);
                    $cellValue = str_replace("'", "", $cellValue);
                    if ($rows > 2) {
                        switch ($cols) {
                            case 1:
                                $result[$indeks]['workid'] = $cellValue;
                                if ($cellValue != '') {
                                    $work = $this->masterWork->fetchRow("workid = '$cellValue'");
                                    if ($work)
                                        $result[$indeks]['workname'] = $work['workname'];
                                    else
                                        $result[$indeks]['workname'] = 'NOT FOUND';
                                }
                                break;
                            case 3:
                                $workid = $result[$indeks]['workid'];
                                $kode_brg = $cellValue;
                                if (count($ceks) > 0) {
                                    if (in_array($workid . "-" . $kode_brg, $ceks))
                                        $result[$indeks]['duplicate'] = true;
                                    else {
                                        $ceks[] = $workid . "-" . $kode_brg;
                                        $result[$indeks]['duplicate'] = false;
                                    }
                                } else {
                                    $ceks[] = $workid . "-" . $kode_brg;
                                    $result[$indeks]['duplicate'] = false;
                                }
                                $result[$indeks]['kode_brg'] = $cellValue;
                                if ($cellValue != '') {
                                    $barang = $this->masterBarang->fetchRow("kode_brg = '$cellValue'");
                                    if ($barang) {
                                        $barang['nama_brg'] = str_replace("\r", "", $barang['nama_brg']);
                                        $barang['nama_brg'] = str_replace("\n", "", $barang['nama_brg']);
                                        $barang['nama_brg'] = str_replace("\t", "", $barang['nama_brg']);
                                        $barang['nama_brg'] = str_replace("\"", " inch", $barang['nama_brg']);
                                        $barang['nama_brg'] = str_replace("'", "", $barang['nama_brg']);
                                        $result[$indeks]['nama_brg'] = $barang['nama_brg'];
                                        if ($barang['stspmeal'] == 'Y') {
                                            $result[$indeks]['stspmeal'] = 'Y';
                                            $result[$indeks]['harga'] = $barang['harga_borong'];
                                        } else {
                                            $result[$indeks]['stspmeal'] = 'N';
                                        }
                                    } else {
                                        $result[$indeks]['nama_brg'] = 'NOT FOUND';
                                        $result[$indeks]['stspmeal'] = 'N';
                                    }
                                } else
                                    $result[$indeks]['stspmeal'] = 'N';

                                break;
                            case 7:$result[$indeks]['qty'] = $cellValue;
                                break;
                            case 8:
                                if ($result[$indeks]['stspmeal'] != 'Y')
                                    $result[$indeks]['harga'] = $cellValue;
                                break;
                            case 10:$result[$indeks]['val_kode'] = $cellValue;
                                break;
//    						case 11:$result[$indeks]['rateidr'] = $cellValue;
//    							break;
                            case 11:
                                $cellValue = preg_replace('/^ +| +$|( )+/', '', $cellValue);
                                $cellValue = preg_replace('/[^_a-zA-Z0-9-]+/', '', $cellValue);
                                $result[$indeks]['cfs_kode'] = $cellValue;
                                break;
                            case 12:$result[$indeks]['cfs_nama'] = $cellValue;
                                break;
//                            case 13:$result[$indeks]['trano'] = $cellValue;
//    							break;
//                            case 14:$result[$indeks]['tranorev'] = $cellValue;
//    							break;
                            case 13:$result[$indeks]['days'] = $cellValue;
                                break;
//                            case 14:$result[$indeks]['end_date'] = $cellValue;
//    							break;
                        }
                    }
                    $cols++;
                }
                $cols = 1;
                $cek = $this->cekKolom($result[$indeks]);
                if ($rows > 2 && ($cek >= 0 && $cek <= 5)) {
                    if ($result[$indeks]['duplicate'] == true) {
                        unset($result[$indeks]);
                        continue;
                    } else {
                        $result[$indeks]['id'] = $indeks + 1;
                        $result[$indeks]['total'] = $result[$indeks]['qty'] * $result[$indeks]['harga'];

                        if ($idFile != '')
                            $result[$indeks]['id_file'] = $idFile;

                        $indeks++;
                    }
                }
                else {
                    unset($result[$indeks]);
                }
                $rows++;
            }

            return $result;
        }
    }

    function readOhpFilePerRow($filePath, $idFile) {
        $result = array();
        $rows = 1;
        $cols = 1;
        $indeks = 0;
        $fetch = false;
        $projectHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('project');
        ;

        if ($this->loadFile($filePath)) {
            foreach ($this->objWorksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $cellValue = $cell->getValue();
                    $cellValue = str_replace("\r", "", $cellValue);
                    $cellValue = str_replace("\n", "", $cellValue);
                    $cellValue = str_replace("\t", "", $cellValue);
                    $cellValue = str_replace("\"", " inch", $cellValue);
                    $cellValue = str_replace("'", "", $cellValue);
                    if ($rows > 2) {
                        switch ($cols) {
                            case 1:
                                $result[$indeks]['prj_kode'] = $cellValue;
                                if ($cellValue != '') {
                                    $prjNama = $projectHelper->getProjectDetail($result[$indeks]['prj_kode']);
                                    if ($prjNama)
                                        $result[$indeks]['prj_nama'] = $prjNama['Prj_Nama'];
                                    else
                                        $result[$indeks]['prj_nama'] = 'NOT FOUND';
                                }
                                break;
                            case 3:
                                $result[$indeks]['sit_kode'] = $cellValue;
                                if ($cellValue != '') {
                                    $sitNama = $projectHelper->getSiteDetail($result[$indeks]['prj_kode'], $result[$indeks]['sit_kode']);
                                    if ($sitNama)
                                        $result[$indeks]['sit_nama'] = $sitNama['sit_nama'];
                                    else
                                        $result[$indeks]['sit_nama'] = 'NOT FOUND';
                                }
                                break;
                            case 5:
                                $result[$indeks]['workid'] = $cellValue;
                                if ($cellValue != '') {
                                    $work = $this->masterWork->fetchRow("workid = '$cellValue'");
                                    if ($work)
                                        $result[$indeks]['workname'] = $work['workname'];
                                    else
                                        $result[$indeks]['workname'] = 'NOT FOUND';
                                }
                                break;
                            case 7:
                                $result[$indeks]['kode_brg'] = $cellValue;
                                if ($cellValue != '') {
                                    $barang = $this->masterBarang->fetchRow("kode_brg = '$cellValue'");
                                    if ($barang) {
                                        $barang['nama_brg'] = str_replace("\r", "", $barang['nama_brg']);
                                        $barang['nama_brg'] = str_replace("\n", "", $barang['nama_brg']);
                                        $barang['nama_brg'] = str_replace("\t", "", $barang['nama_brg']);
                                        $barang['nama_brg'] = str_replace("\"", " inch", $barang['nama_brg']);
                                        $barang['nama_brg'] = str_replace("'", "", $barang['nama_brg']);
                                        $result[$indeks]['nama_brg'] = $barang['nama_brg'];
                                    } else {
                                        $result[$indeks]['nama_brg'] = 'NOT FOUND';
                                    }
                                }
                                break;

                            case 9 :
                                $result[$indeks]['total'] = $cellValue;
                                break;
                        }
                    }
                    $cols++;
                }

                $cols = 1;
                $cek = $this->cekKolom($result[$indeks]);
                if ($rows > 2 && ($cek >= 0 && $cek <= 3)) {
                    $result[$indeks]['id'] = $indeks + 1;

                    if ($idFile != '')
                        $result[$indeks]['id_file'] = $idFile;

                    $indeks++;
                }
                else {
                    unset($result[$indeks]);
                }
                $rows++;
            }
            return $result;
        }
    }

    function readBudgetFilePerRow($filePath, $idFile) {
        $result = array();
        $rows = 1;
        $cols = 1;
        $indeks = 0;
        $fetch = false;

        if ($this->loadFile($filePath)) {
            foreach ($this->objWorksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $cellValue = $cell->getValue();
                    $cellValue = str_replace("\r", "", $cellValue);
                    $cellValue = str_replace("\n", "", $cellValue);
                    $cellValue = str_replace("\t", "", $cellValue);
                    $cellValue = str_replace("\"", " inch", $cellValue);
                    $cellValue = str_replace("'", "", $cellValue);
                    if ($rows > 2) {
                        switch ($cols) {
                            case 1:
                                $result[$indeks]['budgetid'] = $cellValue;
//    							if ($cellValue != '')
//    							{
//	    							$work = $this->masterWork->fetchRow("workid = '$cellValue'");
//	    							if ($work)
//	    								$result[$indeks]['workname'] = $work['workname'];
//	    							else
//	    								$result[$indeks]['workname'] = 'NOT FOUND';
//    							}
                                break;
                            case 2:
                                $result[$indeks]['budgetname'] = $cellValue;
//    							if ($cellValue != '')
//    							{
//	    							$work = $this->masterWork->fetchRow("workid = '$cellValue'");
//	    							if ($work)
//	    								$result[$indeks]['workname'] = $work['workname'];
//	    							else
//	    								$result[$indeks]['workname'] = 'NOT FOUND';
//    							}
                                break;
                            case 3:
                                $result[$indeks]['total'] = $cellValue;
                                break;
                            case 4:$result[$indeks]['val_kode'] = $cellValue;
                                break;
                            case 5:$result[$indeks]['coa_kode'] = $cellValue;
                                break;
                            case 6:$result[$indeks]['coa_nama'] = $cellValue;
                                break;
                        }
                    }
                    $cols++;
                }
                $cols = 1;
                $cek = $this->cekKolom($result[$indeks]);
                if ($rows > 2 && ($cek >= 0 && $cek <= 3)) {
                    $result[$indeks]['id'] = $indeks + 1;

                    if ($idFile != '')
                        $result[$indeks]['id_file'] = $idFile;

                    $indeks++;
                }
                else {
                    unset($result[$indeks]);
                }
                $rows++;
            }
            return $result;
        }
    }

    private function cekKolom($data) {
        $jumlah = 0;
        if (count($data) > 0) {
            foreach ($data as $key => $val) {
                if (empty($data[$key]) || $data[$key] == '' || $data[$key] == NULL || $data[$key] == 'null')
                    $jumlah++;
            }
            return $jumlah;
        }
    }

    function readrequestprice($filePath, $idFile) {
        $result = array();
        $rows = 1;
        $cols = 1;
        $indeks = 0;
        $fetch = false;

        if ($this->loadFile($filePath)) {
            foreach ($this->objWorksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $cellValue = $cell->getValue();
                    $cellValue = str_replace("\r", "", $cellValue);
                    $cellValue = str_replace("\n", "", $cellValue);
                    $cellValue = str_replace("\t", "", $cellValue);
                    $cellValue = str_replace("\"", " inch", $cellValue);
                    $cellValue = str_replace("'", "", $cellValue);
                    if ($rows > 2) {
                        switch ($cols) {
                            case 1:
                                $result[$indeks]['nama_barang'] = $cellValue;
                                break;
                            case 2:
                                $result[$indeks]['spec'] = $cellValue;
                                break;
                        }
                    }
                    $cols++;
                }
                $cols = 1;
                $cek = $this->cekKolom($result[$indeks]);
                if ($rows > 2 && ($cek >= 0 && $cek <= 2)) {
                    $result[$indeks]['id'] = $indeks + 1;

                    if ($idFile != '')
                        $result[$indeks]['id_file'] = $idFile;

                    $indeks++;
                }
                else {
                    unset($result[$indeks]);
                }
                $rows++;
            }
            return $result;
        }
    }

    function readfixedasset($filePath, $idFile) {
        $result = array();
        $rows = 1;
        $cols = 1;
        $indeks = 0;
        $fetch = false;

        if ($this->loadFile($filePath)) {
            foreach ($this->objWorksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $cellValue = $cell->getValue();
                    $cellValue = str_replace("\r", "", $cellValue);
                    $cellValue = str_replace("\n", "", $cellValue);
                    $cellValue = str_replace("\t", "", $cellValue);
                    $cellValue = str_replace("\"", " inch", $cellValue);
                    $cellValue = str_replace("'", "", $cellValue);


                    if ($rows > 2) {
                        switch ($cols) {
                            case 1:
                                $result[$indeks]['new_code'] = $cellValue;
                                break;
                            case 2:
                                $result[$indeks]['clasification'] = $cellValue;
                                break;
                            case 3:
                                $result[$indeks]['code_part_old'] = $cellValue;
                                break;
                            case 4:
                                $result[$indeks]['marking_date'] = $cellValue;
                                break;
                            case 5:
                                $result[$indeks]['accessories'] = $cellValue;
                                break;
                            case 6:
                                $result[$indeks]['brand'] = $cellValue;
                                break;
                            case 7:
                                $result[$indeks]['type'] = $cellValue;
                                break;
                            case 8:
                                $result[$indeks]['serial_number'] = $cellValue;
                                break;
                            case 9:
                                $result[$indeks]['description'] = $cellValue;
                                break;
                            case 10:
                                $result[$indeks]['purchase_status'] = $cellValue;
                                break;
                            case 11:
                                $result[$indeks]['purchase_date'] = $cellValue;
                                break;
                            case 12:
                                $result[$indeks]['condition'] = $cellValue;
                                break;
                            case 13:
                                $result[$indeks]['valuta'] = $cellValue;
                                break;
                            case 14:
                                $result[$indeks]['purchase_price'] = $cellValue;
                                break;
                            case 15:
                                $result[$indeks]['depr_rate'] = $cellValue;
                                break;
//                        case 16:
//                            $result[$indeks]['depr_exp'] = $cellValue;
//                            break;
//                        case 17:
//                            $result[$indeks]['total_depr'] = $cellValue;
//                            break;
                            case 16:
                                $result[$indeks]['status_aktif'] = $cellValue;
                                break;
                            case 17:
                                $result[$indeks]['kode_kategori'] = $cellValue;
                                break;
                            case 18:
                                $result[$indeks]['coa_debit'] = $cellValue;
                                break;
                            case 19:
                                $result[$indeks]['coa_credit'] = $cellValue;
                                break;
                        }
                    }
                    $cols++;
                }
                $cols = 1;
                $cek = $this->cekKolom($result[$indeks]);
                if ($rows > 2 && ($cek >= 0 && $cek <= 10)) {
                    $result[$indeks]['id'] = $indeks + 1;

                    if ($idFile != '')
                        $result[$indeks]['id_file'] = $idFile;

                    $indeks++;
                }
                else {
                    unset($result[$indeks]);
                }
                $rows++;
            }

            return $result;
        }
    }

    function readasffile($filePath, $idFile, $type) {
        $result = array();
        $rows = 1;
        $cols = 1;
        $column = 1;
        $indeks = 0;
        $fetch = false;

        if ($this->loadFile($filePath)) {
            if ($type == 'operational') {
                foreach ($this->objWorksheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    foreach ($cellIterator as $cell) {
                        $cellValue = $cell->getValue();
                        $cellValue = str_replace("\r", "", $cellValue);
                        $cellValue = str_replace("\n", "", $cellValue);
                        $cellValue = str_replace("\t", "", $cellValue);
                        $cellValue = str_replace("\"", " inch", $cellValue);
                        $cellValue = str_replace("'", "", $cellValue);
                        if ($rows > 12) {
                            switch ($cols) {
                                case 1:
                                    $result[$indeks]['tgl'] = $cellValue;
                                    break;
                                case 2:
                                    $result[$indeks]['ket'] = $cellValue;
                                    break;
                                case 3:
                                    $result[$indeks]['value'] = $cellValue;
                                    break;
                                case 4:
                                    $result[$indeks]['type'] = $cellValue;
                                    break;
                            }
                        }
                        $cols++;
                    }
                    $cols = 1;
                    $cek = $this->cekKolom($result[$indeks]);
                    if ($rows > 12 && ($cek >= 0 && $cek <= 2)) {
                        $result[$indeks]['id'] = $indeks + 1;

                        if ($idFile != '')
                            $result[$indeks]['id_file'] = $idFile;

                        $indeks++;
                    }
                    else {
                        unset($result[$indeks]);
                    }
                    $rows++;
                }
            } else if ($type == 'vehicle') {
                $vehicle = '';
                $prjkode_array = array();
                $item = array();
                $divider = 0;
                $valid = true;
                foreach ($this->objWorksheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    foreach ($cellIterator as $cell) {
                        $cellValue = $cell->getValue();
                        $cellValue = str_replace("\r", "", $cellValue);
                        $cellValue = str_replace("\n", "", $cellValue);
                        $cellValue = str_replace("\t", "", $cellValue);
                        $cellValue = str_replace("\"", " inch", $cellValue);
                        $cellValue = str_replace("'", "", $cellValue);

                        if ($rows == 5) {
                            switch ($column) {
                                case 12:
                                    $item[0] = $cellValue;
                                    break;
                                case 13:
                                    $item[1] = $cellValue;
                                    break;
                                case 14:
                                    $item[2] = $cellValue;
                                    break;
                            }
                        }
                        $column++;

                        if ($rows > 5) {
                            switch ($cols) {
                                case 2:
                                    if (strpos($cellValue, "Total")!==false)
                                        $valid = false;
                                    $vehicle = $cellValue;
                                    break;
                                case 4:
                                    $prjkode_array = explode("/", $cellValue);
                                    $divider = count($prjkode_array);
                                case 12:
                                    $value[0] = floatval($cellValue / $divider);
                                    break;
                                case 13:
                                    $value[1] = floatval($cellValue / $divider);
                                    break;
                                case 14:
                                    $value[2] = floatval($cellValue / $divider);
                                    break;
                            }
                        }
                        $cols++;
                    }
                    $cols = 1;
                    $column = 1;
                    $cek = $this->cekKolom($result[$indeks]);
                    if ($rows > 5 && ($cek >= 0 && $cek <= 2)) {
                        if (!$valid)
                            break;
                        
                        $result[$indeks]['id'] = $indeks + 1;

                        if ($idFile != '')
                            $result[$indeks]['id_file'] = $idFile;
                        for ($i = 0; $i < $divider; $i++) {
                            for ($j = 0; $j < 3; $j++) {
                                $result[$indeks]['ket'] = $vehicle . '-' . $item[$j];
                                $result[$indeks]['value'] = $value[$j];
                                $result[$indeks]['job_number'] = $prjkode_array[$i] . '.000.0';
                                $indeks++;
                            }
                        }
                    } else {
                        unset($result[$indeks]);
                    }
                    $rows++;
                }
            }
            return $result;
        }
    }

}
