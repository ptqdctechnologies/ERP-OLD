<?php

class FileController extends Zend_Controller_Action {

    private $db;
    private $savePath;
    private $session;
    private $util;
    private $file;

    public function init() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->session = new Zend_Session_Namespace('login');
        $this->savePath = Zend_Registry::get('uploadPath');
        $this->util = $this->_helper->getHelper('utility');
        $this->file = new Default_Models_Files();
    }

    public function downloadAction() {

        $this->_helper->viewRenderer->setNoRender();
        $type = $this->getRequest()->getParam("type");
        $filename = $this->getRequest()->getParam("filename");
        $path = $this->getRequest()->getParam("path");
        $delete = $this->getRequest()->getParam("delete");
        switch ($type) {
            case 'master_boq3':
                $file = $this->savePath . "/master_boq3.xls";
                break;
            case 'master_boq3_overhead':
                $file = $this->savePath . "/master_boq3_overhead.xls";
                break;
            case 'master_budget':
                $file = $this->savePath . "/master_overhead_budget.xls";
                break;
            case 'ohp':
                $file = $this->savePath . "/OPB.xls";
                break;
            case 'master_request_price':
                $file = $this->savePath . "/master_requestprice.xls";
                break;
            case 'fixed_asset':
                $file = $this->savePath . "/fixed_asset.xls";
                break;
            case 'master_arf_pulsa':
                $file = $this->savePath . "/master_arf_pulsa.xls";
                break;
            case 'asffiles':
                $file = $this->savePath . "/asf_sample.xls";
                break;
            case 'diary':
                $prj_kode = $this->getRequest()->getParam("prj_kode");
                $sit_kode = $this->getRequest()->getParam("sit_kode");
                if (($prj_kode == 'null' && $sit_kode == 'null') || ($prj_kode == null && $sit_kode == null)) {
                    $file = $this->savePath . "mail/" . $filename;
                } else
                    $file = $this->savePath . $prj_kode . "/" . $sit_kode . "/" . $filename;
                break;
            default:
                if ($path != '')
                    $file = $this->savePath . "/" . $path . "/" . $filename;
                else
                    $file = $this->savePath . "/" . $filename;
                break;
        }
        if (ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');
        if (file_exists($file)) {
            $path_parts = pathinfo($file);
            $ext = strtolower($path_parts["extension"]);
            switch ($ext) {
                case "pdf": $ctype = "application/pdf";
                    break;
                case "exe": $ctype = "application/octet-stream";
                    break;
                case "zip": $ctype = "application/zip";
                    break;
                case "docx":
                case "doc": $ctype = "application/msword";
                    break;
                case "xlsx":
                case "xls": $ctype = "application/vnd.ms-excel";
                    break;
                case "ppt": $ctype = "application/vnd.ms-powerpoint";
                    break;
                case "gif": $ctype = "image/gif";
                    break;
                case "png": $ctype = "image/png";
                    break;
                case "jpeg":
                case "jpg": $ctype = "image/jpg";
                    break;
                default: $ctype = "application/force-download";
            }


            //save file log
            $uid = QDC_User_Session::factory()->getCurrentUID();
            $downloaded_files_model = new Default_Models_DownloadedFiles();
            $trano_data = $this->file->fetchRow("savename = '$filename'");
            if ($trano_data)
                $trano = $trano_data['trano'];

            $file_downloaded = $downloaded_files_model->fetchRow("trano = '$trano' "
                    . "and user = '$uid'");

            $found = false;

            if ($file_downloaded) {
                $file_downloaded = $file_downloaded->toArray();
                $data_file = Zend_Json::decode($file_downloaded['filename']);

                foreach ($data_file as $key => $v) {
                    if ($v['filename'] == $filename) {
                        $fileExists = true;
                        break;
                    }
                }

                if (!$fileExists) {

                    $data = array();
                    $id = $file_downloaded['id'];
                    $jsonUid = Zend_Json::decode($file_downloaded['filename']);

                    foreach ($jsonUid as $key => $v) {
                        $data[] = $v;
                    }

                    $data[] = array(
                        'filename' => $filename
                    );


                    $insertdata = array(
                        "filename" => Zend_Json::encode($data)
                    );

                    $downloaded_files_model->update($insertdata, "id = $id");
                }
            } else {
                $data = array(
                    array('filename' => $filename
                    )
                );

                $uid_data_withFiles = array(
                    "trano" => $trano,
                    "date" => date('Y-m-d H:i:s'),
                    "user" => $uid,
                    "filename" => Zend_Json::encode($data)
                );

                $downloaded_files_model->insert($uid_data_withFiles);
            }

            header('Pragma: public');
            header('Expires: 0');
            header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false);
            header("Content-Type: $ctype");
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Description: File Transfer');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($file));

            ob_clean();
            flush();


            ignore_user_abort(true);
            readfile($file);
            if ($delete) {
                unlink($file);
            }
//            exit;
        } else {
            echo 'File not found..';
        }
    }

    public function previewAction() {
        $filename = $this->getRequest()->getParam("filename");
        $path = $this->getRequest()->getParam("path");
        $prj_kode = $this->getRequest()->getParam("prj_kode");
        $sit_kode = $this->getRequest()->getParam("sit_kode");
        $previewPath = Zend_Registry::get('previewPath');
        if (($prj_kode == 'null' && $sit_kode == 'null') || ($prj_kode == null && $sit_kode == null)) {
            $file = $previewPath . "mail/" . $filename;
            $savePath = Zend_Registry::get('uploadPath') . "mail";
        } else {
            $file = $previewPath . $prj_kode . "/" . $sit_kode . "/" . $filename;
            $savePath = Zend_Registry::get('uploadPath') . $prj_kode . "/" . $sit_kode;
        }
        if ($path != '')
            $savePath = Zend_Registry::get('uploadPath') . $path;

        $myFiles = $savePath . "/" . $filename;
        if (file_exists($myFiles)) {

            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            $mime = finfo_file($finfo, $myFiles);
            finfo_close($finfo);
            if (strpos($mime, "image/jpg") !== false || strpos($mime, "image/jpeg") !== false) {
                $exif = exif_read_data($myFiles);
                if ($exif['Make'] == '')
                    $exif['Make'] = 'Unknown';
                $this->view->Make = $exif['Make'];
                if ($exif['Model'] == '')
                    $exif['Model'] = 'Unknown';
                $this->view->Model = $exif['Model'];
                if ($exif['DateTimeOriginal'] == '')
                    $exif['DateTimeOriginal'] = 'Unknown';
                if (substr_count($exif['DateTimeOriginal'], ":") > 3) {
                    $ary = explode(" ", $exif['DateTimeOriginal']);
                    $ary[0] = str_replace(":", "-", $ary[0]);
                    $exif['DateTimeOriginal'] = $ary[0] . " " . $ary[1];
                }
                $this->view->DateTimeOriginal = date('d-m-Y H:i:s', strtotime($exif['DateTimeOriginal']));

                if ($exif['GPSLatitudeRef'] != '' && $exif['GPSLongitudeRef'] != '') {
                    $this->view->GpsLongDec = $this->util->getGpsDecimal($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
                    $this->view->GpsLatDec = $this->util->getGpsDecimal($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
                    $long = $this->util->getGps($exif["GPSLongitude"]);
                    $this->view->GpsLong = $long['degrees'] . "&deg " . $long['minutes'] . "' " . $long['seconds'] . "\" " . $exif['GPSLongitudeRef'];
                    $lat = $this->util->getGps($exif["GPSLatitude"]);
                    $this->view->GpsLat = $lat['degrees'] . "&deg " . $lat['minutes'] . "' " . $lat['seconds'] . "\" " . $exif['GPSLatitudeRef'];
                } else {
                    $this->view->GpsLongDec = '';
                    $this->view->GpsLatDec = '';
                    $this->view->GpsLat = 'Unknown';
                    $this->view->GpsLong = 'Unknown';
                }
            }
        }
        $this->view->fileName = $filename;
        $this->view->imageFile = $file;
    }

    public function listAction() {
        $this->_helper->viewRenderer->setNoRender();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $searchFields = $this->getRequest()->getParam("fields");
        $searchQuery = $this->getRequest()->getParam("query");

        if ($searchFields != '' && $searchQuery != '') {
            $searchFields = Zend_Json::decode($searchFields);
            $where = $this->util->buildSearchQuery($searchFields, $searchQuery);
        }

        $result = $this->file->fetchAll($where, array($sort . " " . $dir), $limit, $offset);

        if ($result) {
            $return['posts'] = $result->toArray();
            $return['count'] = $this->file->fetchAll($where)->count();
            $ldap = new Default_Models_Ldap();
            foreach ($return['posts'] as $k => $v) {
//                $name = $ldap->getAccount($v['uid']);
//                $return['posts'][$k]['name'] = $name['displayname'][0];
                $return['posts'][$k]['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                $return['posts'][$k]['date'] = date('d-m-Y H:i:s', strtotime($return['posts'][$k]['date']));
                $path = "files";
                $file = $this->savePath . "/" . $path . "/" . $v['savename'];
                if (file_exists($file)) {
                    $return['posts'][$k]['status'] = 1;
                } else
                    $return['posts'][$k]['status'] = 0;
            }
        }
        else {
            $return['posts'] = array();
            $return['count'] = 0;
        }

        $return = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($return);
    }

    public function deleteNewFileAction() {
        $this->_helper->viewRenderer->setNoRender();
        $name = $this->getRequest()->getParam("savename");
        $path = "files";
        $file = $this->savePath . "/" . $path . "/" . $name;
        $return['success'] = false;
        if (file_exists($file)) {
            unlink($file);
            $return['success'] = true;
        }

        $return = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($return);
    }

    public function deleteAction() {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->getRequest()->getParam("id");
        $force = $this->getRequest()->getParam("force");
        if ($force)
            $force = true;
        else
            $force = false;
        if ($this->session->privilege >= 300) {
            $result = $this->file->fetchRow("id = $id");
            if ($result) {
                $result = $result->toArray();
                $path = "files";
                $file = $this->savePath . "/" . $path . "/" . $result['savename'];
                if (file_exists($file)) {
                    unlink($file);
                    $return['success'] = true;
                } else {
                    if ($force)
                        $return['success'] = true;
                    else {
                        $return['success'] = false;
                        $return['msg'] = "File not found!";
                    }
                }
                $this->file->delete("id = $id");
            } else {
                $return['success'] = false;
                $return['msg'] = "File not found!";
            }
        } else {
            $return['success'] = false;
            $return['msg'] = "You are not allowed to delete this file!";
        }
        $return = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($return);
    }

    public function uploadAction() {
        $this->_helper->viewRenderer->setNoRender();
        $type = $this->getRequest()->getParam('type');

        $success = 'false';
        $msg = '';

        $result = QDC_Adapter_File::factory()->upload($_FILES, 'file-path', true, 'files');
        if (is_array($result)) {
            $savePath = Zend_Registry::get('uploadPath') . 'files';
            $myFiles = $savePath . "/" . $result['save_name'];

            $name = explode(".", $result['origin_name']);
            $fileName = $name[0];
            $fileName = preg_replace("/[^a-zA-Z0-9\s]/", "_", $fileName);
            $name[0] = $fileName;
            $newName = implode(".", $name);
            $return = array(
                'success' => true,
                'filename' => $newName,
                'path' => $myFiles,
                'savename' => $result['save_name']
            );
        } else
            $return = array('success' => false, 'msg' => 'Error while uploading Your file, Please contact Administrator or Support Team.');


        $json = Zend_Json::encode($return);
        echo $json;
    }

}
?>