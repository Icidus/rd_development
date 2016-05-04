<?php
/*******************************************************************************
from https://github.com/valums/file-uploader
http://valums.com/ajax-upload/

File uploader component is licensed under GNU GPL 2 or later and GNU LGPL 2 or later.
© 2010 Andrew Valums

Acquired April 30, 2012 

Modified by Troy Hurteau, NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

This file is part of NCSU's distribution of ReservesDirect. 

ReservesDirect is distributed in the hope that it will be useful,
but is distributed "AS IS" and WITHOUT ANY WARRANTY, without even the
implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE, and without any warranty as to non-infringement of any third
party's rights.

This version of ReservesDirect, distributed by NCSU, is located at:
http://code.google.com/p/reservesdirect-ncsu/
*******************************************************************************/

require_once(APPLICATION_PATH . '/classes/Queue/Encoding.php');

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }
    function getMethod()
    {
    	return 'xhr';
    }  
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
    function getMethod()
    {
    	return 'iframe';
    }  
}

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;
    protected $_clientFilename = '';
    protected $_targetFilename = '';
    protected $_fileExtension = '';

    function __construct(array $allowedExtensions = array(), $sizeLimit = null)
    { 
    	$maxSizeLimit = $this->calculateMaxSize();
    	if (is_null($sizeLimit)) {
    		$sizeLimit = $maxSizeLimit;
    	}  else {
    		$sizeLimit = $this->_toBytes($sizeLimit);
    		if ($sizeLimit > $maxSizeLimit) {
    			throw new Exception("Server error. Upload size limit ({$sizeLimit}) is set higher than the server is configured to handle({$maxSizeLimit}).");
    		}
    	}
    	
        $allowedExtensions = array_map('strtolower', $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
	public function calculateMaxSize(){
		$maxPost = $this->_toBytes(ini_get('post_max_size'));
		$memoryLimit = $this->_toBytes(ini_get('memory_limit'));
		$maxUpload = $this->_toBytes(ini_get('upload_max_filesize'));
		return min($maxPost, $memoryLimit, $maxUpload);
	}
	
	protected function _toBytes($value)
	{
	    if (is_numeric( $value )) {
	        return $value;
	    } else {
	        $valueLength = strlen($value);
	        $qty = intval(substr($value, 0, $valueLength - 1));
	        $unit = strtolower(substr($value, $valueLength - 1));
	        switch ($unit) {
	            case 'k':
	                return $qty * 1024;
	                break;
	            case 'm':
	                return $qty * 1048576;
	            case 'g':
	                return $qty * 1073741824;
	        }
	        return intval($qty);
	    }
	}
	
	protected function _fromBytes($value)
	{
	    if (!is_numeric( $value )) {
	        return $value;
	    } else if ($value >= 1073741824) {
	    	return round($value / 1073741824, 2) . 'G';
	    } else if ($value >= 1048576) {
	    	return round($value / 1048576, 2) . 'M';
	    } else if ($value >= 1024) {
	    	return round($value / 1024, 2) . 'k';
	    } else {
	    	return $value;	
	    }
	}
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE, $targetFilename = '')
    {
        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. The upload directory isn't writable.");
        }
        
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => 'The provided file is empty.');
        }
        
        if ($size > $this->sizeLimit) {
        	$humanFileSize = $this->_fromBytes($this->sizeLimit);
            return array('error' => "The provided file is too large, please select a file that is {$humanFileSize} or smaller.", 'sizeLimit' => $this->sizeLimit);
        }
        
        $pathinfo = pathinfo($this->file->getName());
        $this->_clientFilename = $pathinfo['filename'];
        $this->_targetFilename = ('' != trim($targetFilename) ? trim($targetFilename) : $this->_clientFilename);
        $this->_fileExtension = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($this->_fileExtension), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }
        
        if(!$replaceOldFile){
            // don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $this->_targetFilename . '.' . $this->_fileExtension)) {
                $this->_targetFilename .= rand(10, 99);
            }
        }
        
        if ($this->file->save($uploadDirectory . $this->_targetFilename . '.' . $this->_fileExtension)){
            return array('success'=>true, 'method'=>$this->file->getMethod());
        } else {
            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
        
    }
    
    public function getTargetFilename(){
    	return (
    		'' != $this->_targetFilename
    		? $this->_targetFilename . '.' . $this->_fileExtension
    		: ''
    	);
    }
    
    public function getSourceFilename(){
    	return (
    		'' != $this->_clientFilename
    		? $this->_clientFilename . '.' . $this->_fileExtension
    		: ''
    	);
    }
    
}

class FileUploader
{
    protected static $_uploadPath = null;
	
    protected static function _init()
    {
    	if (is_null(self::$_uploadPath)){
    		self::$_uploadPath = Rd_Registry::get('root:videoUploadPath');
    	}
    }
    
	public static function init($param = array())
    {
    	self::_init();
    	if (is_array($param) && count($param) > 0 && '' != trim($param[0])) {
    		$method = array_shift($param);
    		if (method_exists(__CLASS__, $method)) {
    			return self::$method($param);
    		} else {
    			return array('error' => "Unsuported method {$method}");
    		}
    	}
    	// list of valid extensions, ex. array("jpeg", "xml", "bmp")
		$allowedExtensions = array();
		// max file size in bytes
		$sizeLimit = 10 * 1024 * 1024; //#TODO maybe get the real size limit instead...
		try{
        	$uploader = new qqFileUploader();
        	$userName = Account_Rd::getName();
        	$userName = '' != trim($userName) ? $userName : '_servicekey';
        	$date = date('Y-m-d');
			$result = $uploader->handleUpload(self::$_uploadPath, false, $userName . '_' . $date);
			$destination = $uploader->getTargetFilename();
			if ('' != $destination) {
				$result['destinationFilename'] = $destination;
				$hash = Queue_Encoding::createEntry($destination, $uploader->getSourceFilename());
				$result['fileReferenceHash'] = $hash;
			} else {
				//gotta clean this up if is didn't go into the queue...
			}
			// to pass data through iframe you will need to encode all entities
			return htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        } catch (Exception $e) {
        	 return array('error' => $e->getMessage());
        }
    }
    
    public static function delete($param)
    {
    	self::_init();
    	if (is_array($param) && count($param) > 0 && '' != trim($param[0])) {
    		$key = array_shift($param);
    		try {
    			$result = Queue_Encoding::delete($key);
    			if ($result) {
    				return array('success' => true, 'result'=> $result);
    			} else {
    				return array('success' => false, 'error' => 'Unable to remove the requested file.');
    			}
    		} catch (Exception $e) {
    			return array('error' => $e->getMessage());
    		}
    		
    	}
    	else return array('error' => "No file key specified.");
    	
    }
}

