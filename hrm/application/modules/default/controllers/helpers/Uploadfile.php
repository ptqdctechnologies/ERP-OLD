<?php
class Zend_Controller_Action_Helper_Uploadfile extends
                Zend_Controller_Action_Helper_Abstract
{
	private $db;
	private $fileType; 
	
	private $size;
	private $originName;
	private $type;
	private $diskName;
	private $savePath;
	private $session;
	
	private $upload;
	
    function  __construct() {
    	$this->session = new Zend_Session_Namespace('login');
        
        $this->db = Zend_Registry::get('db');
        $this->fileType = Zend_Registry::get('validFile');
		$this->savePath = Zend_Registry::get('uploadPath');
    }    
    
    function cekValidFile($type)
    {
//    	if (in_array($type,$this->fileType))
//	    	return $type;
//	    else
//	    	return false;
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
    
    function uploadFile($file,$keyName,$saveOriginName=false,$suffixPath='',$prefixName='')
    {
        $split = explode(".",$file[$keyName]['name']);
        $last = count($split) - 1;
        $ext = $split[$last];
        unset($split[$last]);
        $nama = implode(".",$split);
    	$this->originName = strtolower(preg_replace('/\W/',"_",$nama) . "." . $ext) ;
//    	$this->type = $file[$keyName]['type'];
        $this->type = strtoupper($ext);
    	$this->size = $file[$keyName]['size'];

//        $uid_uploader = str_replace(" ","_",$this->session->userName);

        if ($this->originName == '' || $this->size == '' || strlen($this->originName) == 0)
            return false;

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

    		if (move_uploaded_file($file[$keyName]['tmp_name'],$this->savePath . $suffixPath . $newName ))
    		{
    			$return['id_user'] = $this->session->userName;
    			$return['type'] = $this->type;
    			$return['size'] = $this->size;
    			$return['origin_name'] = $this->originName;
    			$return['save_name'] = $newName;
    			$return['upload_date'] = date('Y-m-d H:i:s');
//    			$return['id_file'] = $this->upload->insert($return);
    			$return['save_file'] = $this->savePath . $suffixPath .  $newName ;
    			return $return;
    		}
    		else
    			return false;
    	}
    	else
    	{
    		return false;
    	}
    }
}