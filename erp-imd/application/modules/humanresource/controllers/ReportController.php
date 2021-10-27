<?php
class HumanResource_ReportController extends Zend_Controller_Action
{
    private $db;
    private $session;
    private $projectHelper;
    private $masterWork;
    private $masterBarang;
    private $workflow;
    private $workflowTrans;
    private $const;
    private $error;


    public function init()
    {
        $this->db = Zend_Registry::get('db');
        Zend_Loader::loadClass('Zend_Json');
        $this->session = new Zend_Session_Namespace('login');
        $this->projectHelper = $this->_helper->getHelper('project');
        $this->masterWork = new Default_Models_MasterWork();
        $this->masterBarang = new Default_Models_MasterBarang();
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->const = Zend_Registry::get('constant');
        $this->error = $this->_helper->getHelper('error');
    }

    public function showtimesheetsummaryAction()
    {
        
    }

    public function timesheetsummaryAction()
    {

    }

    public function showsalaryAction()
    {

    }

    public function salarylistAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = "SELECT SQL_CALC_FOUND_ROWS trano FROM procurement_salh GROUP BY trano ORDER BY $sort $dir LIMIT $offset, $limit";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function summarysalaryAction()
    {
        $sald = new HumanResource_Models_SalaryD();
        $sal = new HumanResource_Models_SalaryH();
        $trano = $this->getRequest()->getParam("trano");

        $fetch = $sald->fetchAll("trano='$trano'",array("prj_kode ASC"));
        $fetch2 = $sal->fetchRow("trano='$trano'");
        if ($fetch)
        {
            $hasil = array();
            $this->view->tgl = date("d M Y",strtotime($fetch2['tgl']));
            $fetch = $fetch->toArray();
            foreach($fetch as $k => $v)
            {
                $prjKode = $v['prj_kode'];

                if ($hasil[$prjKode] == '')
                {
                    $hasil[$prjKode] = array(
                        "trano" => $v['trano'],
                        "tgl" => $v['tgl'],
                        "prj_kode" => $v['prj_kode'],
                        "prj_nama" => $v['prj_nama'],
                        "total" => (float)$v['total']
                    );
                }
                else
                {
                    $hasil[$prjKode]['total'] += (float)$v['total'];
                }
            }
            $this->view->result = $hasil;
            $this->view->trano = $trano;
        }
    }

}
?>