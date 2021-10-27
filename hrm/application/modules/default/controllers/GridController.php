<?php
/*Function Name: Grid Controller
 * Kegunaan : 
 * - Pembentuk header column di GridPanel
 * - Mapping field GridPanel dengan data JSON
 * - Set URL JSON untuk store GridPanel
 */

class GridController extends Zend_Controller_Action
{
    private $leadHelper;
    private $db;
    private $domain;

    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap'); //Init bootstrap
        $this->db = $bootstrap->getResource('connection'); //deklarasi koneksi db, untuk fetch data tanpa Zend_Db_Table
        $this->domain = $bootstrap->getResource('mainUrl'); //deklarasi domain / URL
        $this->leadHelper = $this->_helper->getHelper('grids'); //deklarasi helper untuk keperluan GridPanel di Ext JS

        $session = new Zend_Session_Namespace('login');
    }

    public function columnheaderAction()
    {

         $this->initView();
         $request = $this->getRequest();

         $listType = $request->getParam('type');
        switch($listType)
            {
                case 'poh':
                     $metadata = $this->db->describeTable('procurement_poh');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trano'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Tanggal'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'tgl'","isDate" => true),
                                      "tglpr" => array("header" => "'Tanggal PR'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'tglpr'","isDate" => true),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'prj_kode'")
                     );
                break;
                case 'pod':
                     $metadata = $this->db->describeTable('procurement_pod');
                     $columnNames = array_keys($metadata);
                     $colView = array("urut" => array("header" => "'Urut'" ,"width" => 50, "sortable" => "true", "dataIndex" => "'urut'"),
                                      "trano" => array("header" => "'Trano'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "kode_brg" => array("header" => "'Kode Barang'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'kode_brg'"),
                                      "nama_brg" => array("header" => "'Nama Barang'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'nama_brg'"),
                                      "qty" => array("header" => "'Qty'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'qty'"),
                                      "harga" => array("header" => "'Harga'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'harga'"),
                                      "total" => array("header" => "'Total'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'total'")
                     );
                break;
                case 'pr_list2':
                     $metadata = $this->db->describeTable('procurement_prd');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trano'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Date'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'tgl'","isDate" => true),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "prj_nama" => array("header" => "'Project Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'prj_nama'"),
                                      "sit_kode" => array("header" => "'Site Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "sit_nama" => array("header" => "'Site Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'sit_nama'"),
                                      "kode_brg" => array("header" => "'Product Id'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'kode_brg'"),
                                      "nama_brg" => array("header" => "'Description'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'nama_brg'"),
                                      "qty" => array("header" => "'Qty'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'qty'")
                     );
                break;
                case 'pr_list':
                     $metadata = $this->db->describeTable('procurement_prh');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trano'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Tanggal'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'tgl'","isDate" => true),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'prj_kode'")
                     );
                break;  
                case 'project_list':
                     $metadata = $this->db->describeTable('master_project');
                     $columnNames = array_keys($metadata);
                     $colView = array("Prj_Kode" => array("header" => "'Project Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'Prj_Kode'"),
                                      "Prj_Nama" => array("header" => "'Project Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'Prj_Nama'")
                     );
                break;
                case 'site_list':
                     $metadata = $this->db->describeTable('master_site');
                     $columnNames = array_keys($metadata);
                     $colView = array("sit_kode" => array("header" => "'Site Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "sit_nama" => array("header" => "'Site Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'sit_nama'")
                     );
                break;
                case 'work_list':
                case 'workpr_list':
                     $metadata = $this->db->describeTable('masterengineer_work');
                     $columnNames = array_keys($metadata);
                     $colView = array("sit_kode" => array("header" => "'Site Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "workid" => array("header" => "'Work Id'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'workid'"),
                                      "workname" => array("header" => "'Work Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'workname'")
                    );
                break;                
                case 'customer_list':
                     $metadata = $this->db->describeTable('master_customer');
                     $columnNames = array_keys($metadata);
                     $colView = array("cus_kode" => array("header" => "'Customer Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'cus_kode'"),
                                      "cus_nama" => array("header" => "'Customer Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'cus_nama'")
                     );
                break;
                case 'barang_list':
                     $metadata = $this->db->describeTable('master_barang_project_2009');
                     $columnNames = array_keys($metadata);
                     $colView = array("kode_brg" => array("header" => "'Product Id'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'kode_brg'"),
                                      "nama_brg" => array("header" => "'Product Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'nama_brg'")
                     );
                break;
                case 'satuan_list':
                     $metadata = $this->db->describeTable('master_satuan');
                     $columnNames = array_keys($metadata);
                     $colView = array("sat_kode" => array("header" => "'UOM Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'sat_kode'"),
                                      "sat_nama" => array("header" => "'UOM Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'sat_nama'")
                     );
                break;
                case 'valuta_list':
                     $metadata = $this->db->describeTable('master_valuta');
                     $columnNames = array_keys($metadata);
                     $colView = array("val_kode" => array("header" => "'Currency Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'val_kode'"),
                                      "val_nama" => array("header" => "'Currency Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'val_nama'")
                     );
                break;
                case 'suplier_list':
                     $metadata = $this->db->describeTable('master_suplier');
                     $columnNames = array_keys($metadata);
                     $colView = array("sup_kode" => array("header" => "'Supplier Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'sup_kode'"),
                                      "sup_nama" => array("header" => "'Supplier Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'sup_nama'")
                     );
                break;
                case 'trano_list':
                     $metadata = $this->db->describeTable('procurement_rpih');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trans No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Date'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'tgl'"),
                                      "po_no" => array("header" => "'PO No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'po_no'"),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "prj_nama" => array("header" => "'Project Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'prj_nama'"),
                                      "sit_kode" => array("header" => "'Site Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "sit_nama" => array("header" => "'Site Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sit_nama'"),
                                      "sup_kode" => array("header" => "'Supplier Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'sup_kode'"),
                                      "sup_nama" => array("header" => "'Supplier Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sup_nama'"),
                                      "total" => array("header" => "'Total RPI'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'total'"),
                                      "totalpo" => array("header" => "'Total PO'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'totalpo'")
                         );
                break;
                case 'trano2_list':
                     $metadata = $this->db->describeTable('procurement_prh');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trans No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Date'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'tgl'"),
                                      "po_no" => array("header" => "'PO No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'po_no'"),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "prj_nama" => array("header" => "'Project Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'prj_nama'"),
                                      "sit_kode" => array("header" => "'Site Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "sit_nama" => array("header" => "'Site Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sit_nama'"),
                                      "sup_kode" => array("header" => "'Supplier Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'sup_kode'"),
                                      "sup_nama" => array("header" => "'Supplier Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sup_nama'"),
                                      "total" => array("header" => "'Total RPI'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'total'"),
                                      "totalpo" => array("header" => "'Total PO'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'totalpo'")
                         );
                break;
                case 'trano3_list':
                     $metadata = $this->db->describeTable('procurement_poh');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trans No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Date'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'tgl'"),
                                      "pr_no" => array("header" => "'PR No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'pr_no'"),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "prj_nama" => array("header" => "'Project Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'prj_nama'"),
                                      "sit_kode" => array("header" => "'Site Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "sit_nama" => array("header" => "'Site Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sit_nama'"),
                                      "sup_kode" => array("header" => "'Supplier Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'sup_kode'"),
                                      "sup_nama" => array("header" => "'Supplier Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sup_nama'")                                 
                         );
                break;
                case 'trano4_list':
                     $metadata = $this->db->describeTable('procurement_arfh');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trans No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Date'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'tgl'"),
                                      "pr_no" => array("header" => "'PR No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'po_no'"),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "prj_nama" => array("header" => "'Project Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'prj_nama'"),
                                      "sit_kode" => array("header" => "'Site Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "sit_nama" => array("header" => "'Site Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sit_nama'"),
                                      "sup_kode" => array("header" => "'Supplier Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'sup_kode'"),
                                      "sup_nama" => array("header" => "'Supplier Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sup_nama'")
                         );
                break;
                case 'trano5_list':
                     $metadata = $this->db->describeTable('procurement_asfh');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trans No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Date'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'tgl'"),
                                      "pr_no" => array("header" => "'PR No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'po_no'"),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "prj_nama" => array("header" => "'Project Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'prj_nama'"),
                                      "sit_kode" => array("header" => "'Site Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "sit_nama" => array("header" => "'Site Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sit_nama'"),
                                      "sup_kode" => array("header" => "'Supplier Code'" ,"width" => 100, "sortable" => "true", "dataIndex" => "'sup_kode'"),
                                      "sup_nama" => array("header" => "'Supplier Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sup_nama'")
                         );
                break;
                case 'trano6_list':
                     $metadata = $this->db->describeTable('procurement_pointh');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trans No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Date'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'tgl'"),                                     
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "prj_nama" => array("header" => "'Project Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'prj_nama'"),
                                      "sit_kode" => array("header" => "'Site Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "sit_nama" => array("header" => "'Site Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sit_nama'")                                   
                         );
                break;
                case 'trano7_list':
                     $metadata = $this->db->describeTable('procurement_mdoh');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trans No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Date'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'tgl'"),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "prj_nama" => array("header" => "'Project Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'prj_nama'"),
                                      "sit_kode" => array("header" => "'Site Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "sit_nama" => array("header" => "'Site Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sit_nama'")
                         );
                break;
                case 'trano8_list':
                     $metadata = $this->db->describeTable('procurement_whoh');
                     $columnNames = array_keys($metadata);
                     $colView = array("trano" => array("header" => "'Trans No'" ,"width" => 125, "sortable" => "true", "dataIndex" => "'trano'"),
                                      "tgl" => array("header" => "'Date'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'tgl'"),
                                      "prj_kode" => array("header" => "'Project Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'prj_kode'"),
                                      "prj_nama" => array("header" => "'Project Name'" ,"width" => 200, "sortable" => "true", "dataIndex" => "'prj_nama'"),
                                      "sit_kode" => array("header" => "'Site Code'" ,"width" => 75, "sortable" => "true", "dataIndex" => "'sit_kode'"),
                                      "sit_nama" => array("header" => "'Site Name'" ,"width" => 150, "sortable" => "true", "dataIndex" => "'sit_nama'")
                         );
                break;
            }
            
         $columnView = $this->leadHelper->viewColumn($colView,$columnNames);
         $this->view->columnHeader = $columnView;
    }

    public function geturljsonAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');
        $param = $request->getParam('param');
        $noParam = $request->getParam('noParam');
        $columnName = $request->getParam('columnName');

        $additionalColumnName = $request->getParam('additionalColumnName');
        $additionalColumnData = $request->getParam('additionalColumnData');


        switch($listType)
        {
            case 'poh':
                $retUrl = '/procurement/procurement/list/type/poh';
            break;
            case 'pod':
                $retUrl = '/procurement/procurement/list/type/pod/' . $columnName . '/' . $param;
            break;
            case 'pr_list2':
                $retUrl = '/prd/list';
            break;
            case 'pr_list':
                $retUrl = '/prh/list';
            break;
            case 'project_list':
                $retUrl = '/project/list';
            break;
            case 'site_list':
                $retUrl = '/site/list';
            break;
            case 'work_list':
                $retUrl = '/work/list';
            break;
            case 'workpr_list':
                $retUrl = '/work/listworkpr';
            break;
            case 'customer_list':
                $retUrl = '/customer/list';
            break;
            case 'barang_list':
                $retUrl = '/barang/list';
            break;
            case 'satuan_list':
                $retUrl = '/satuan/list';
            break;
            case 'valuta_list':
                $retUrl = '/valuta/list';
            break;
            case 'suplier_list':
                $retUrl = '/suplier/listfilter';
            break;
            case 'trano_list':
                $retUrl = '/rpih/list';
            break;
            case 'trano2_list':
                $retUrl = '/prh/list/type/P';
            break;
            case 'trano3_list':
                $retUrl = '/poh/list/';
            break;
            case 'trano4_list':
                $retUrl = '/arfh/list/type/P/';
            break;
            case 'trano5_list':
                $retUrl = '/asfh/list';
            break;
            case 'trano6_list':
                $retUrl = '/pointh/list';
            break;
            case 'trano7_list':
                $retUrl = '/mdoh/list';
            break;
            case 'trano8_list':
                $retUrl = '/whoh/list';
            break;



            case 'project_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/project/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/project/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/project/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'site_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/site/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/site/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/site/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'work_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/work/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/work/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/work/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'workpr_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/work/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/work/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/work/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'po_list_param':
                if (isset($additionalColumnName))
                {
                    $retUrl = '\'/procurement/listByParams/name/' . $columnName . '/data/\' + field.getValue() + \'/' . $additionalColumnName . '/\' + data + \'/joinToPod/1\';' ;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/procurement/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/procurement/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
             case 'pr_list_param2':
                 if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/prd/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/prd/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/prd/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'pr_list_param':
                 if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/procurement/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/prh/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/prh/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;            
            case 'customer_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/customer/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/customer/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/customer/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'barang_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/barang/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/barang/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/barang/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'satuan_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/satuan/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/satuan/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/satuan/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'valuta_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/valuta/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/valuta/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/valuta/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;            
           case 'suplier_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/suplier/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/suplier/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/suplier/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'trano_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/rpih/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/rpih/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/rpih/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'trano2_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/prh/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/prh/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/prh/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'trano3_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/poh/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/poh/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/poh/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'trano4_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/arfh/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/arfh/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/arfh/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'trano5_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/asfh/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/asfh/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/asfh/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'trano6_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/pointh/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/pointh/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/pointh/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'trano7_list_param':
                if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/mdoh/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/mdoh/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/mdoh/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
            case 'trano8_list_param':
            if (isset($additionalColumnData) && isset($additionalColumnName))
                {
                    $retUrl = '/whoh/listByParams/name/' . $columnName . '/data/' . $param . '/' . $additionalColumnName . '/' . $additionalColumnData;
                }
                else
                {
                    if ($noParam)
                        $retUrl = '/whoh/listByParams/name/' . $columnName . '/data/';
                    else
                        $retUrl = '/whoh/listByParams/name/' . $columnName . '/data/' . $param ;
                }
            break;
        }

        echo $retUrl;
    }

    public function mappingfieldAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');
        switch($listType)
        {
            case 'poh':
                $metadata = $this->db->describeTable('procurement_poh');
                $colView = array(
                              "prj_kode",
                              "trano" ,
                              "tgl" => array("isDate" => true),
                              "tglpr" => array("isDate" => true)
                );
            break;
            case 'pod':
                $metadata = $this->db->describeTable('procurement_pod');
                $colView = array(
                              "urut",
                              "trano" ,
                              "kode_brg",
                              "nama_brg",
                              "qty",
                              "harga",
                              "total",
                );
            break;
            case 'pr_list2':
                $metadata = $this->db->describeTable('procurement_prd');
                $colView = array(
                              "prj_kode",
                              "prj_nama",
                              "trano" ,
                              "tgl" => array("isDate" => true),
                              "sit_kode",
                              "sit_nama",
                              "kode_brg",
                              "nama_brg",
                              "qty"
                );
           break;
           case 'pr_list':
                $metadata = $this->db->describeTable('procurement_prh');
                $colView = array(
                              "prj_kode",
                              "trano" ,
                              "tgl" => array("isDate" => true)
                );           
            break;
            case 'project_list':
                $metadata = $this->db->describeTable('master_project');
                $colView = array(
                              "Prj_Kode",
                              "Prj_Nama"
                );
            break;
            case 'site_list':
                $metadata = $this->db->describeTable('master_site');
                $colView = array(
                              "sit_kode",
                              "prj_kode",
                              "sit_nama"
                );
            break;
            case 'work_list':
                $metadata = $this->db->describeTable('masterengineer_work');
                $colView = array(
                              "workid",
                              "workname"
                );
            break;
            case 'workpr_list':
                $metadata = $this->db->describeTable('masterengineer_work');
                $colView = array(
                              "workid",
                              "workname"
                );
            break;
            case 'customer_list':
                $metadata = $this->db->describeTable('master_customer');
                $colView = array(
                              "cus_kode",
                              "cus_nama"
                );
            break;
            case 'barang_list':
                $metadata = $this->db->describeTable('master_barang_project_2009');
                $colView = array(
                              "kode_brg",
                              "nama_brg",
                			  "harga_beli",
                			  "val_kode",
                			  "sat_kode"
                );
            break;
            case 'satuan_list':
                $metadata = $this->db->describeTable('master_satuan');
                $colView = array(
                              "sat_kode",
                              "sat_nama"
                );
            break;
               case 'valuta_list':
                $metadata = $this->db->describeTable('master_valuta');
                $colView = array(
                              "val_kode",
                              "val_nama"
                );
            break;
            case 'suplier_list':
                $metadata = $this->db->describeTable('master_suplier');
                $colView = array(
                              "sup_kode",
                              "sup_nama"
                );
            break;
            case 'trano_list':
                $metadata = $this->db->describeTable('procurement_rpih');
                $colView = array(
                              "trano",
                              "tgl",
                              "po_no",
                              "prj_kode",
                              "prj_nama",
                              "sit_kode",
                              "sit_nama",
                              "sup_kode",
                              "sup_nama",
                              "total",
                              "totalpo"
                );
            break;
            case 'trano2_list':
                $metadata = $this->db->describeTable('procurement_prh');
                $colView = array(
                              "trano",
                              "tgl",                             
                              "prj_kode",
                              "prj_nama",
                              "sit_kode",
                              "sit_nama",
                              "sup_kode",
                              "sup_nama",
                              "total"                             
                );
            break;
            case 'trano3_list':
                $metadata = $this->db->describeTable('procurement_poh');
                $colView = array(
                              "trano",
                              "tgl",
                              "pr_no",
                              "prj_kode",
                              "prj_nama",
                              "sit_kode",
                              "sit_nama",
                              "sup_kode",
                              "sup_nama"
                   
                );
            break;
            case 'trano4_list':
                $metadata = $this->db->describeTable('procurement_arfh');
                $colView = array(
                              "trano",
                              "tgl",
                              "prj_kode",
                              "prj_nama",
                              "sit_kode",
                              "sit_nama",
                              "sup_kode",
                              "sup_nama"

                );
            break;
            case 'trano5_list':
                $metadata = $this->db->describeTable('procurement_asfh');
                $colView = array(
                              "trano",
                              "tgl",
                              "prj_kode",
                              "prj_nama",
                              "sit_kode",
                              "sit_nama",
                              "sup_kode",
                              "sup_nama"

                );
            break;
            case 'trano6_list':
                $metadata = $this->db->describeTable('procurement_pointh');
                $colView = array(
                              "trano",
                              "tgl",                           
                              "prj_kode",
                              "prj_nama",
                              "sit_kode",
                              "sit_nama"
                );
            break;
            case 'trano7_list':
                $metadata = $this->db->describeTable('procurement_pointh');
                $colView = array(
                              "trano",
                              "tgl",
                              "prj_kode",
                              "prj_nama",
                              "sit_kode",
                              "sit_nama"
                );
            break;
            case 'trano8_list':
                $metadata = $this->db->describeTable('procurement_whoh');
                $colView = array(
                              "trano",
                              "tgl",
                              "prj_kode",
                              "prj_nama",
                              "sit_kode",
                              "sit_nama"
                );
            break;
        }

        $columnNames = array_keys($metadata);        
        $mapping = $this->leadHelper->mapping($colView,$columnNames);

        echo $mapping;
    }

    public function getprimarykeyAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');
//		var_dump(get_include_path());die();
        switch($listType)
        {
            case 'poh':
                $Pk = new Default_Models_ProcurementPoh();
            break;
            case 'prh':
                $Pk = new Default_Models_ProcurementRequestH();
            break;
            case 'prd':
                $Pk = new Default_Models_ProcurementRequest();
            break;
            case 'project':
                $Pk = new Default_Models_MasterProject();
            break;
            case 'site':
                $Pk = new Default_Models_MasterSite();
            break;
            case 'work':
                $Pk = new Default_Models_MasterWork();
            break;
            case 'workpr':
                $Pk = new Default_Models_MasterWork();
            break;
            case 'customer':
                $Pk = new Default_Models_MasterCustomer();
            break;
            case 'barang':
                $Pk = new Default_Models_MasterBarang();
            break;
            case 'satuan':
                $Pk = new Default_Models_MasterSatuan();
            break;
            case 'valuta':
                $Pk = new Default_Models_MasterValuta();
            break;
            case 'suplier':
                $Pk = new Default_Models_MasterSuplier();
            break;
//            case 'arfh':
//                $Pk = new Default_Models_ProcurementArfh();
//            break;
            case 'rpih':
                $Pk = new Default_Models_RequestPaymentInvoiceH();
            break;
            case 'arfh':
                $Pk = new Default_Models_AdvanceRequestFormH();
            break;
            case 'asfh':
                $Pk = new Default_Models_AdvanceSettlementFormH();
            break;
            case 'pointh':
                $Pk = new Default_Models_MaterialDeliveryInstructionH();
            break;
            case 'mdoh':
                $Pk = new Default_Models_MaterialDeliveryOrderH();
            break;
            case 'whoh':
                $Pk = new Default_Models_MaterialDeliveryOrderH();
            break;
        }
   
        
        echo $Pk->getPrimaryKey();
    }

    public function addlistenerAction()
    {
        $request = $this->getRequest();

        $listenerName = $request->getParam('name');

        $this->view->listenerName = $listenerName;

        $columnName = $request->getParam('columnName');
        $controller = $request->getParam('controller');
        $additionalColumnName = $request->getParam('additionalColumnName');
        $additionalColumnData = $request->getParam('additionalColumnData');

        if (isset($additionalColumnData) && isset($additionalColumnName))
        {
            $newUrl = '\'/' . $controller . '/listByParams/name/' . $columnName . '/data/\' + field.getValue() + \'/' . $additionalColumnName . '/' . $additionalColumnData . '\';' ;
        }
        else
        {
            $newUrl = '\'/' . $controller . '/listByParams/name/' . $columnName . '/data/\' + field.getValue();' ;
        }
        $this->view->newUrl = $newUrl;
    }

    public function popupgridAction()
    {

    }
}

?>
