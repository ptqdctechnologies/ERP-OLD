<?php

Class QDC_Jasper_Report
{

    public $config;
    public $arrayData;
    public $arrayParams;
    public $dataSource;

    private $javaArrayData;
    private $javaArrayList;
    private $fileName;
    private $arrayFileName;
    private $filePath;
    private $reportType;
    private $outputName;

    private $compiledReport;
    private $filledReport;

    //Untuk class Jasper
    public $JASPER;

    /**
     * @param string $params
     *
     * constructor class, $params tidak secara eksplisit dideklarasikan
     */
    public function __construct($params = '')
    {
        define('NO_DATA_SOURCE','NoDataSource');
        define('FORMAT_PDF','pdf');
        define('FORMAT_XLS','xls');

        if ($params != '')
        foreach($params as $k => $v)
        {
            $temp = $k;
            $this->{"$temp"} = $v;
        }
        $this->JASPER = $this->getConfig();
        $this->filePath = $this->JASPER->reportPath . $this->fileName;

    }

    public static function getConfig()
    {
        $reps = QDC_Adapter_Jasper::init();
        return $reps;
    }

    /**
     * @static
     * @param $params
     * @return QDC_Jasper_Report
     *
     * Method factory dipanggil apabila QDC_JASPER_REPORT di inisialisasi secara statik
     */
    public static function factory($params)
    {
        return new self($params);
    }

    /**
     * @param $arrayData
     * @return Java
     */
    private function arrayToJavaArrayList($arrayData)
    {
        //Hapus array collection terlebih dahulu..
        $this->JASPER->javaArrayList->clear();
        foreach ($arrayData as $k => $v)
        {
            $this->JASPER->javaArrayList->add($v); //masukkan ke array collection
        }

        return $this->JASPER->javaArrayList;
    }

    /**
     * @param string $compiledReport
     * @return mixed
     */
    private function filler($compiledReport='')
    {
        if ($this->dataSource == NO_DATA_SOURCE)
        {
            $data = $this->arrayToJavaArrayList($this->arrayData);
            $ds = new java("net.sf.jasperreports.engine.data.JRBeanCollectionDataSource",$data);
            $this->filledReport = $this->JASPER->fillManager->fillReport($compiledReport, $this->arrayParams,$ds);
        }
        else
        {
            $this->filledReport = $this->JASPER->fillManager->fillReport($compiledReport, $this->arrayParams,$this->JASPER->dbConn->getConnection());
        }

        return $this->filledReport;
    }

    /**
     * @param string $filePath
     * @return mixed
     */
    private function compiler($filePath='')
    {
        $this->compiledReport = $this->JASPER->compileManager->compileReport($filePath);

        return $this->compiledReport;
    }

    /**
     * @param string $filledReport
     * @param bool $printList
     */
    private function export($filledReport='',$printList=false)
    {

        set_time_limit(120);
        java_set_file_encoding("ISO-8859-1");
        $exParm = $this->JASPER->exporterParams;
        if ($this->reportType == FORMAT_PDF)
        {
            $exporter = $this->JASPER->exporterPDF;
            $format = FORMAT_PDF;
        }
        else
        {
            $exporter = $this->JASPER->exporterXLS;
            $exXlsParm = $this->JASPER->exporterXLSParams;
            $format = FORMAT_XLS;
        }

        if (!$printList)
            $exporter->setParameter($exParm->JASPER_PRINT, $filledReport);
        else
            $exporter->setParameter($exParm->JASPER_PRINT_LIST, $filledReport);

        $exporter->setParameter($exParm->OUTPUT_STREAM, $this->JASPER->javaOutputStream);

        if ($this->reportType == FORMAT_XLS)
        {
            $exporter->setParameter($exXlsParm->IS_ONE_PAGE_PER_SHEET, false);
            $exporter->setParameter($exXlsParm->IS_REMOVE_EMPTY_SPACE_BETWEEN_ROWS, true);
            $exporter->setParameter($exXlsParm->IS_DETECT_CELL_TYPE, true);
            $exporter->setParameter($exXlsParm->IS_WHITE_PAGE_BACKGROUND, false);
        }

        $exporter->exportReport();

        header("Content-Type: application/" . $format);
        header('Content-Transfer-Encoding: binary');
        header('Content-disposition: attachment; filename="'.$this->outputName.'.' . $format . '"');
        header('Pragma: no-cache');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        echo java_cast($this->JASPER->javaOutputStream->toByteArray(),"S");
    }

    public function generate()
    {
        try
        {
            $cmp = $this->compiler($this->filePath);
            $fill = $this->filler($cmp);
            $this->export($fill);
        }
        catch( JavaException $e)
        {
            echo $e;
            die();
        }
    }

    public function generateCombined()
    {
        try
        {
            $printList = new Java("java.util.ArrayList");
            foreach ($this->arrayMultiData as $k => $v)
            {
                $this->arrayData = $v['data'];
                $file = $this->JASPER->reportPath . $v['fileName'];
                $this->arrayParams = $v['arrayParams'];
                $this->dataSource = $v['dataSource'];

                $cmp = $this->compiler($file);
                $fill = $this->filler($cmp);
                $printList->add($fill);
            }
            $this->export($printList,true);
        }
        catch( JavaException $e)
        {
            echo $e;
            die();
        }
    }
}