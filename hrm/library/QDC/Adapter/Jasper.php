<?php

class QDC_Adapter_Jasper
{

    public $reportPath;
    public $zendConfig ;
    public $databaseConfig;
    //Jasper stuff
    public $compileManager;
    public $fillManager;
    public $exporterPDF;
    public $exporterParams;
    public $exporterXLS;
    public $exporterXLSParams;
    public $javaOutputStream;
    public $dbConn;

    public function __construct($required=array())
    {

        $this->zendConfig = QDC_Adapter_Main::init()->zendConfig;
        $this->databaseConfig = QDC_Adapter_Main::init()->databaseConfig;

        $this->reportPath = $this->zendConfig->jasper->reportPath;
        $this->javaBridge = $this->zendConfig->jasper->javaBridge;

        include_once($this->javaBridge."java/Java.inc");
        $this->javaArrayList = new Java("java.util.ArrayList");
        $this->compileManager = new JavaClass("net.sf.jasperreports.engine.JasperCompileManager");
        $this->fillManager = new JavaClass("net.sf.jasperreports.engine.JasperFillManager");
        $this->exporterPDF = new java("net.sf.jasperreports.engine.export.JRPdfExporter");
        $this->exporterParams = java("net.sf.jasperreports.engine.JRExporterParameter");
        $this->exporterXLS = new java("net.sf.jasperreports.engine.export.JExcelApiExporter");
        $this->exporterXLSParams = java("net.sf.jasperreports.engine.export.JRXlsExporterParameter");
        $this->javaOutputStream = new java("java.io.ByteArrayOutputStream");

        $this->dbConn = new Java("org.altic.jasperReports.JdbcConnection");
        $this->dbConn->setDriver("com.mysql.jdbc.Driver");
        $this->dbConn->setConnectString("jdbc:mysql://" . $this->databaseConfig->host . "/" . $this->databaseConfig->dbname);
        $this->dbConn->setUser($this->databaseConfig->username);
        $this->dbConn->setPassword($this->databaseConfig->password);
    }

    public static function init($requiredArray = array())
    {

        return new self($requiredArray);
    }

}

?>