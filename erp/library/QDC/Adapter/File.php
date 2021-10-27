<?php

class QDC_Adapter_File {

    private $invalidFileExt;
    private $uploadPath, $fileType, $savePath;

    private $size;
    private $originName;
    private $type;
    public function __construct($params = '')
    {
        if ($params != '')
        {
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }
        $this->uploadPath = Zend_Registry::get('uploadPath');
        $this->fileType = Zend_Registry::get('validFile');
        $this->savePath = $this->uploadPath;
    }

    public static function factory($params=array())
    {
        return new self($params);
    }

    public function makeFileFromBase64($base64='')
    {
        if (!$base64)
            return array("success" => false);

        $img = str_replace('data:image/jpeg;base64,', '', $base64);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $filename = date('d-m-Y_His') . "_" . md5(date('mdYHisu'). mt_rand(0, 255)) . '.jpg';
        $file = $this->uploadPath .'/files/'. $filename;
        $success = file_put_contents($file, $data);

        if ($success !== false)
        {
            return array(
                "success" => true,
                "filename" => $filename,
                "savename" => $filename,
            );
        }
        return array("success" => false);

    }

    public function makeFileFromJson($json='')
    {
        $filename = md5(QDC_User_Session::factory()->getCurrentUID()) . '.json';
        $file = $this->uploadPath . $filename;
        $success = file_put_contents($file, $json);

        if ($success !== false)
        {
            return array(
                "success" => true,
                "filename" => $filename,
                "savename" => $filename,
            );
        }
        return array("success" => false);
    }


    function cekValidFile($type)
    {
        if (array_key_exists($type,$this->fileType))
            return $type;
        else
            return false;
    }

    function getFileType()
    {
        return $this->type;
    }

    function getFileSize()
    {
        return $this->size;
    }

    function getSavePath()
    {
        return $this->savePath;
    }

    function getFileName()
    {
        return $this->originName;
    }

    public function upload($file,$keyName,$saveOriginName=false,$suffixPath='',$prefixName='')
    {
        $split = explode(".",$file[$keyName]['name']);
        $last = count($split) - 1;
        $ext = $split[$last];
        unset($split[$last]);
        $nama = implode(".",$split);
        $this->originName = strtolower(preg_replace('/\W/',"_",$nama) . "." . $ext) ;
        $this->type = strtoupper($ext);
        $this->size = $file[$keyName]['size'];


        if ($this->originName == '' || $this->size == '' || strlen($this->originName) == 0)
        {
            echo "{success: false, msg: 'File size is NULL'}";
            die;
        }

        if ($file[$keyName]['error'] === UPLOAD_ERR_INI_SIZE)
        {
            echo "{success: false, msg: 'Error while uploading Your file, Your file is too large. Maximum File size : " . ini_get('upload_max_filesize') . "'";
            die;
        }

        if ($this->cekValidFile($this->type))
        {
            mt_srand((double)microtime()*1000000);
            $split = explode(".",$this->originName);
            $last = count($split) - 1;
            $ext = $split[$last];

            if (!$saveOriginName)
                $newName = md5($this->originName . date('mdYHisu').mt_rand(0, 255)) . "." . $ext;
            else
                $newName = date('d-m-Y_His') . "_" . $this->originName;

            if ($suffixPath != '')
            {
                if (substr($suffixPath,-1,1) == "/")
                    $suffixPath = substr($suffixPath,0,-1);

                $dirSplit = explode("/",$suffixPath);
                $tmpDir = '';
                $indexFile = "index.html";
                foreach ($dirSplit as $key => $val)
                {
                    $tmpDir .= "/" . $val;
                    if (!file_exists($this->savePath .  $tmpDir))
                        mkdir($this->savePath . $tmpDir);

                    if (!file_exists($this->savePath .  $tmpDir . "/" . $indexFile))
                    {
                        $indexFileHandle = fopen($this->savePath .  $tmpDir . "/" . $indexFile, 'w');
                        fclose($indexFileHandle);
                    }
                }
                $suffixPath = $tmpDir . "/";

            }
            else
                $this->savePath = $this->uploadPath;

            if (move_uploaded_file($file[$keyName]['tmp_name'],$this->savePath . $suffixPath . $newName ))
            {
                $return['origin_name'] = $this->originName;
                $return['save_name'] = $newName;
                $return['upload_date'] = date('Y-m-d H:i:s');
                $return['save_file'] = $this->savePath . $suffixPath .  $newName ;
                return $return;
            }
            else
            {
                echo "{success: false, msg: \"Error saat mengupload File.}";
                die;
            }
        }
        else
        {
            $list = implode(", ",array_keys($this->fileType));
            echo "{success: false, msg: 'File cannot be upload!<br>Allowed file extension : <b>$list</b>'}";
            die;
        }
    }

    public function read($file)
    {
        if (file_exists($file))
        {
            $fh = fopen($file, 'r') or die("can't open file");
            $isi = fread($fh, filesize($file));
            fclose($fh);

            return $isi;
        }

        return '';
    }

    public function download($filename,$renameFile='',$delete=false)
    {
        $file = $this->uploadPath . $filename;

        if (!file_exists($file))
            return false;

        $ext = $this->getFileExtension($file);
        switch ($ext) {
            case "pdf": $ctype="application/pdf"; break;
            case "exe": $ctype="application/octet-stream"; break;
            case "zip": $ctype="application/zip"; break;
            case "docx":
            case "doc": $ctype="application/msword"; break;
            case "xlsx":
            case "xls": $ctype="application/vnd.ms-excel"; break;
            case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
            case "gif": $ctype="image/gif"; break;
            case "png": $ctype="image/png"; break;
            case "jpeg":
            case "jpg": $ctype="image/jpg"; break;
            default: $ctype="application/force-download";
        }

        $newName = basename($file);
        if ($renameFile)
            $newName = $renameFile;

        if(ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');

        header('Pragma: public');
        header('Expires: 0');
        header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false);
        header("Content-Type: $ctype");
        header('Content-Disposition: attachment; filename='.$newName);
        header('Content-Description: File Transfer');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($file));

        $fh = fopen($file, 'r');

        // And pass it through to the browser
        fpassthru($fh);
    }

    public function getRandomFilename($ext='')
    {
        $filename = date('d-m-Y_His') . "_" . md5(date('mdYHisu'). mt_rand(0, 255)) . (($ext != '') ? "." . $ext : '');

        return $filename;
    }

    public function getFileExtension($filename='')
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        return $ext;
    }
}
?>