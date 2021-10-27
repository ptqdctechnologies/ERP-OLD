<?php

class QDC_Adapter_Excel {

    private $phpexcel, $uploadPath, $savePath;
    private $objPHPExcel;

    private $header_default_border,$header_style_header;
    private $cellFormat = '';

    public function __construct($params = '')
    {
        $this->phpexcel = Zend_Registry::get('phpexcel');

        include_once($this->phpexcel."PHPExcel.php");
        require_once $this->phpexcel.'PHPExcel/IOFactory.php';
        require_once $this->phpexcel.'PHPExcel/Writer/Excel5.php';
        require_once $this->phpexcel.'PHPExcel/Writer/Excel2007.php';

        if ($params != '')
        {
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }

        $this->uploadPath = Zend_Registry::get('uploadPath');
        $this->savePath = $this->uploadPath;

        if ($this->fileName == '')
            return array("success" => false, "msg" => "No Filename define");

        $this->objPHPExcel = new PHPExcel();

        $this->header_default_border = array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => array('rgb'=>'1006A3')
        );
        $this->header_style_header = array(
            'borders' => array(
                'bottom' => $this->header_default_border,
                'left' => $this->header_default_border,
                'top' => $this->header_default_border,
                'right' => $this->header_default_border,
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb'=>'E1E0F7'),
            ),
            'font' => array(
                'bold' => true,
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            )
        );

        $this->remark_style = array(
            'borders' => array(
                'bottom' => $this->header_default_border,
                'left' => $this->header_default_border,
                'top' => $this->header_default_border,
                'right' => $this->header_default_border,
            ),
            'font' => array(
                'bold' => true,
            )
        );

    }

    public static function factory($params=array())
    {
        return new self($params);
    }

    public function setCellFormat($cell=array())
    {
        foreach($cell as $k => $v)
        {
            $this->cellFormat[$k] = $v;
        }

        return $this;
    }

    public function write($data=array(), $remark=array(), $useFooter = true, $useHeader = true)
    {
        if (count($data) == 0)
            return array("success" => false);

        $this->objPHPExcel->setActiveSheetIndex(0);
        $rows = count($data);
        $cols = count($data[0]);

//        $this->objPHPExcel->getDefaultStyle()
//            ->getNumberFormat()
//            ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        $row = 1;
        if ($useHeader)
        {
            $columns = $this->getColumnName($data);

    //        $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($col,$row)->getFont()->setBold(true);

            $col = 0;
            foreach($columns as $k => $v)
            {
                $this->objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($col)->setAutoSize(true);
                $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($col,$row)->applyFromArray($this->header_style_header);
                $this->objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($col,$row,$v,PHPExcel_Cell_DataType::TYPE_STRING);
                $col++;
            }

            $row++;
        }
        $row = $this->insertRow($row,$data);

        //Apply remark & datetime stamp

        //apply remark
        if (count($remark) > 0)
        {
            $row = $row + 2;
            foreach($remark as $k => $v)
            {
                $this->objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(0,$row,5,$row);
                if ($v)
                {
                    $this->objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0,$row, $v);
                    $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(0,$row)->applyFromArray($this->remark_style);
                    $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(1,$row)->applyFromArray($this->remark_style);
                    $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(2,$row)->applyFromArray($this->remark_style);
                    $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(3,$row)->applyFromArray($this->remark_style);
                    $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(4,$row)->applyFromArray($this->remark_style);
                    $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(5,$row)->applyFromArray($this->remark_style);
                    $row++;
                }
            }
        }

        if ($useFooter)
        {
            $row++;
            $datetime = "Printed By " . QDC_User_Ldap::factory(array("uid" => QDC_User_Session::factory()->getCurrentUID()))->getName() .  " @ " . date("d M Y H:i:s");
            $this->objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(0,$row,4,$row);
            $this->objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0,$row, $datetime);
            $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(0,$row)->applyFromArray($this->remark_style);
            $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(0,$row)->applyFromArray($this->remark_style);
            $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(1,$row)->applyFromArray($this->remark_style);
            $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(2,$row)->applyFromArray($this->remark_style);
            $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(3,$row)->applyFromArray($this->remark_style);
            $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(4,$row)->applyFromArray($this->remark_style);
        }

        return $this;
    }

    public function toExcel5()
    {
        $objWriter = new PHPExcel_Writer_Excel5($this->objPHPExcel);
        $objWriter->save($this->uploadPath . $this->fileName . ".xls");

        return $this;
    }


    public function toExcel5Stream()
    {
        $objWriter = new PHPExcel_Writer_Excel5($this->objPHPExcel);

        //Lets the streaming begin...
        ob_end_clean();

        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $this->fileName . ".xls" .'"');
        ob_end_clean();

        $objWriter->save('php://output');

        $this->objPHPExcel->disconnectWorksheets();

    }

    private function loadFile($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }
        $this->objPHPExcel = PHPExcel_IOFactory::load($filePath);

        return $this;
    }

    public function read($startRow=1,$userArray=array())
    {
        $cek = $this->loadFile($this->fileName);
        if (!$cek)
            return false;

        $rows = 1;$cols = 1;$indeks=0;
        $result = array();
        foreach ($this->objPHPExcel->getActiveSheet()->getRowIterator() as $row)
        {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell)
            {
                $cellValue = $cell->getValue();
                $cellValue = str_replace("\r","",$cellValue);
                $cellValue = str_replace("\n","",$cellValue);
                $cellValue = str_replace("\t","",$cellValue);
                $cellValue = str_replace("\""," inch",$cellValue);
                $cellValue = str_replace("'","",$cellValue);
                if ($rows >= $startRow)
                {
                    if (count($userArray) > 0)
                    {
                        if ($userArray[$cols])
                        {
                            $colNama = $userArray[$cols];
                            $result[$indeks][$colNama] = $cellValue;
                        }
                    }
                    else
                    {
                        $result[$indeks][$cols] = $cellValue;
                    }
                }
                $cols++;
            }
            $cols = 1;
            if ($rows >= $startRow)
                $indeks++;
            $rows++;
        }

        return $result;
    }

    private function getColumnName($data=array())
    {
        $column = array();
        foreach($data as $k => $v)
        {
            foreach($v as $k2 => $v2)
            {
                $value = str_replace("_",' ',ucwords($k2));
                $column[] = $value;
            }

            break;
        }

        return $column;
    }

    public function appendToFile($filename='',$data = array())
    {
        $this->fileName = $filename;
        $cek = $this->loadFile($this->uploadPath . $filename . ".xls");
        if (!$cek)
            return false;

        $rows = $this->objPHPExcel->getActiveSheet()->getHighestRow()+1;

        $this->insertRow($rows,$data);
        $this->toExcel5();
    }

    public function insertRow($row=1,$data=array())
    {
        foreach($data as $k => $v)
        {
            $col = 0;
            foreach($v as $k2 => $v2)
            {

                $value = (string)$v2;
                $this->getCellStyle($value,$col,$row);
                $value = strip_tags($value);

                $cellStyle = $this->cellFormat[$col];
                if ($cellStyle)
                {
                    if ($cellStyle['cell_type'] == 'numeric')
                    {
                        switch($cellStyle['cell_operation'])
                        {
                            case 'setFormatCode':
                                $exec = $cellStyle['var'];
                                $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($col,$row)->getNumberFormat()->setFormatCode($exec);
                                break;
                        }

                    }
                    $this->objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col,$row, $value);
                }
                else
                    //Ubah format cell ke STRING, contoh kasus : value = 0101, menjadi 101 kalau tidak memakai setCellValueExplicit
                    $this->objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($col,$row,$value,PHPExcel_Cell_DataType::TYPE_STRING);

                $col++;
            }
            $row++;
        }

        return $row;
    }

    public function getCellStyle($value,$col,$row)
    {
        if (strpos($value,"<b>") !== false)
        {
            $this->objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($col,$row)->applyFromArray(array(
                'font' => array(
                    'bold' => true,
                )
            ));
        }
    }
}
?>