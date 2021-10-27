<?php
class ProjectManagement_DiaryController extends Zend_Controller_Action
{
    private $db;
    private $session;
    private $upload;
    private $projectHelper;
    private $util;
    private $barang;
	private $mail;
	private $budget;
	private $userRole;
	private $roleType;
    private $diary;
    private $diaryFile;
    private $user;
    private $masterRole;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->upload = $this->_helper->getHelper('uploadfile');
		$this->mail = $this->_helper->getHelper('mail');
		$this->budget = $this->_helper->getHelper('budget');
		$this->userRole = new Admin_Model_Userrole();
		$this->roleType = new Admin_Model_Masterroletype();
        $this->user = new Default_Models_MasterUser();
        $this->phpexcel = $this->_helper->getHelper('phpexcel');
        $this->projectHelper = $this->_helper->getHelper('project');
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->barang = new Default_Models_MasterBarang();
        $this->diary = new ProjectManagement_Models_ProjectDiary();
        $this->diaryFile = new ProjectManagement_Models_ProjectDiaryFile();
        $this->masterRole = new Admin_Model_Masterrole();
    }

    public function diaryAction()
    {
        $prjKode = $this->getRequest()->getParam('prj_kode');
        $prjNama = $this->projectHelper->getProjectDetail($prjKode);
        $this->view->prjKode = $prjKode;
        $this->view->prjNama = $prjNama['Prj_Nama'];
        $this->view->myId = $this->session->idUser;
        
    }

    public function createAction()
    {
        $this->_helper->viewRenderer->setNoRender();

//        if ($_FILES['file-path']['size'] != '' && $_FILES['file-path']['size'] > 5000 )
        if ($_FILES['file-path']['size'] != '' && $_FILES['file-path']['size'] > 1000000 )
        {
            $return = array('success' => false, 'msg' => 'File size is too big! Please upload no more than 1 MB (Megabytes)..');
            Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($return);
            echo $json;
            exit();
        }

        $arrayInsert['aktifitas'] = $this->getRequest()->getParam('aktifitas');
        $arrayInsert['aktifitas_type'] = $this->getRequest()->getParam('aktifitas_type');
        $arrayInsert['hambatan'] = $this->getRequest()->getParam('hambatan');
        $arrayInsert['hambatan_type'] = $this->getRequest()->getParam('hambatan_type');
        $arrayInsert['prj_kode'] = $this->getRequest()->getParam('prj_kode');
        $arrayInsert['sit_kode'] = $this->getRequest()->getParam('sit_kode');
        $prj = $this->projectHelper->getProjectDetail($arrayInsert['prj_kode']);
        $arrayInsert['prj_nama'] = $prj['Prj_Nama'];
        $sit = $this->projectHelper->getSiteDetail($arrayInsert['prj_kode'],$arrayInsert['sit_kode']);
        $arrayInsert['sit_nama'] =$sit['sit_nama'];

        $arrayInsert['uid'] = $this->session->userName;
        $arrayInsert['tgl'] = date('Y-m-d H:i:s');
        $lastInsertId = $this->diary->insert($arrayInsert);

        $prjKode = $arrayInsert['prj_kode'];
        $sitKode = $arrayInsert['sit_kode'];

        if ($_FILES['file-path']['name'] != '' && $_FILES['file-path']['size'] != '')
        {
            $result = $this->upload->uploadFile($_FILES,'file-path',true, $prjKode . "/" . $sitKode);
            if ($result)
            {
                $savePath = Zend_Registry::get('uploadPath') . $prjKode . "/" . $sitKode;
                $myFiles = $savePath . "/" . $result['save_name'];
                if (file_exists($myFiles))
                {

                    $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                    $mime = finfo_file($finfo, $myFiles);
                    finfo_close($finfo);
                    if (strpos($mime,"image/jpg") !== false || strpos($mime,"image/jpeg") !== false)
                    {
                        $exif = exif_read_data($myFiles);
                        if ($exif['DateTimeOriginal'] != '')
                            $tglOri = date('d-m-Y H:i:s',strtotime($exif['DateTimeOriginal']));
                    }
                }

                $arrayInsert2['diary_id'] = $lastInsertId;
                $arrayInsert2['uid'] = $this->session->userName;
                $arrayInsert2['tgl'] = date('Y-m-d H:i:s');
                $arrayInsert2['tgl_ori'] = $tglOri;
                $arrayInsert2['savename'] = $result['save_name'];
                $arrayInsert2['filename'] = $result['origin_name'];
                $this->diaryFile->insert($arrayInsert2);
                $return = array('success' => true);
            }
            else
                $return = array('success' => false, 'msg' => 'Error while uploading Your file, Please contact Administrator or Support Team.');
        }
        else
        {
            $return = array('success' => true);
        }
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON
        echo $json;
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        $this->getResponse()->setBody($json);

    }

    public function getmydiaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');

        $user_id = $this->getRequest()->getParam('id_user');
        $all = $this->getRequest()->getParam('all');

        if ($prj_kode != 'invalid')
        {
            if ($user_id != '')
            {
                $user = $this->user->fetchRow("id=$user_id");
                if ($user)
                {
                    $uid = $user['uid'];
                }
            }else
                $uid = $this->session->userName;

            if (!$all)
            {
                $where = "uid = '$uid'";
                if ($prj_kode != '')
                    $where .= " AND prj_kode = '$prj_kode'";
                if ($sit_kode != '')
                    $where .= " AND sit_kode = '$sit_kode'";
            }
            else
            {
                if ($prj_kode != '')
                    $where = "prj_kode = '$prj_kode'";
                if ($sit_kode != '')
                    $where .= " AND sit_kode = '$sit_kode'";
            }

        }
        else
        {
            //Untuk diary yang gak ada project code/site code.. fetching dari email.
            if ($user_id != '')
            {
               $uid = $user_id;
            }else
                $uid = $this->session->userName;

            if (!$all)
            {
                $where = "uid = '$uid'";
            }
                $where .= " AND prj_kode is null AND uniqueID != '' ";
        }
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

//        $fetch = $this->diary->fetchAll(
//            $this->diary->select()->from(
//                $this->diary,array('aktifitas','aktifitas_type','uid','tgl')
//            ),
//            array($sort . ' ' . $dir), $limit, $offset
//        )->toArray();

        $fetch = $this->diary->fetchAll($where,array($sort . ' ' . $dir), $limit, $offset)->toArray();

        $ldapdir = new Default_Models_Ldap();
        foreach($fetch as $key=>$val)
        {
            $account = $ldapdir->getAccount($val['uid']);
            $fetch[$key]['name'] = $account['displayname'][0];
            if ($prj_kode == 'invalid')
            {
                $fetch[$key]['tgl'] = date('Y-m-d H:i:s',strtotime($fetch[$key]['tgl_sent']));
            }
            $id = $fetch[$key]['id'];
            $files = $this->diaryFile->fetchRow("diary_id=$id");
            if ($files)
            {
                $fetch[$key]['filename'] = $files['filename'];
                $fetch[$key]['savename'] = $files['savename'];
                if ($prj_kode == 'invalid')
                {
                    $savePath = Zend_Registry::get('uploadPath') . "mail";
                }
                else
                    $savePath = Zend_Registry::get('uploadPath') . $val['prj_kode'] . "/" . $val['sit_kode'];
                $myFiles = $savePath . "/" . $files['savename'];
                if (file_exists($myFiles))
                {

                    $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                    $mime = finfo_file($finfo, $myFiles);
                    finfo_close($finfo);
                    $fetch[$key]['isImage'] = false;
                    if (strpos($mime,"image") !== false)
                    {
                        $fetch[$key]['imageFile'] = $val['prj_kode'] . "/" . $val['sit_kode'] . "/" .$files['savename'];
                        $fetch[$key]['isImage'] = true;
                    }

                }
            }
            $fetch[$key]['id'] = $key+1;

            $fetch[$key]['hambatan_type'] = $this->convertHambatanType($fetch[$key]['hambatan_type']);
            $fetch[$key]['aktifitas_type'] = $this->convertAktifitasType($fetch[$key]['aktifitas_type']);
        }
        $return['posts'] = $fetch;
        $return['count'] = $this->diary->fetchAll($where, array($sort . ' ' . $dir), $limit, $offset)->count();


        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    function convertAktifitasType($aktifitasType)
    {
        switch($aktifitasType)
            {
                case 'civil_works': $type ='Civil Works';
                break;
                case 'tower_erection': $type ='Tower Erection';
                break;
                case 'metalwork': $type ='Metalwork';
                break;
                case 'cabin': $type= 'Cabin';
                break;
                case 'M_E': $type= 'M&E';
                break;
                case 'antenna_feeder': $type= 'Antenna & Feeder';
                break;
                case 'other': $type= 'Other';
                break;
            }
        return $type;
    }

    function convertHambatanType($hambatanType)
    {

        switch($hambatanType)
            {
                case 'client': $type ='Client';
                break;
                case 'weather': $type ='Weather';
                break;
                case 'equipment': $type ='Equipment';
                break;
                case 'plant': $type= 'Plant';
                break;
                case 'access': $type= 'Access';
                break;
                case 'material_delivery': $type= 'Material Delivery';
                break;
                case 'other': $type= 'Other';
                break;
            }
        return $type;
        
    }

}