<?php
class FileDownload{
    private $_speed = 512;
    public function download($file,$name='',$reload=false){
	$fp = @fopen($file,'rb');
  	if($fp){
	     if($name==''){
		$name = basename($file);
	     }
	     $header_array = get_headers($file,true);
	     if(!$header_array){
		$file_size = filesize($file);
	     }else{
		$file_size = $header_array["Content-Length"];
	     }
	     $ranges = $this->getRange($file_size);
	     header("cache-control:public");
	     header("content-type:application/octet-stream");
             header('Content-Disposition: attachment; filename="' . $name . '"');
	     if($reload && $ranges!=null){
		   header('HTTP/1.1 206 Partial Content');
		   header('Accept-Ranges:bytes');
		   header(sprintf('content-length:%u',$ranges['end']-$ranges['start'])); 
		   header(sprintf('content-range:bytes %s-%s/%s', $ranges['start'], $ranges['end'], $file_size));
		   fseek($fp,sprintf('%u',$ranges['start']));
	     }else{
             	   header('HTTP/1.1 200 OK');
             	   header('content-length:'.$file_size);
             }
	     while(!feof($fp)){
		echo fread($fp,round($this->_speed*1024,0));
		ob_flush();
	     }
	     ($fp!=null) && fclose($fp);
        }else{
	     return '';
	}
    }
    public function setSpeed($speed){
	if(is_numeric($speed) && $speed>16 && $speed<4096){
	    $this->_speed = $speed;
	}
    }
    private function getRange($file_size){
	if(isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])){
	     $range = $_SERVER['HTTP_RANGE'];
             $range = preg_replace('/[\s|,].*/', '', $range);
             $range = explode('-', substr($range, 6)); 
	     $range = array_combine(array('start','end'),$range);
	      if(empty($range['start'])){ 
		$range['start'] = 0; 
	      } 
	      if(empty($range['end'])){ 
		$range['end'] = $file_size; 
	      } 
	     file_put_contents('range.log',json_encode($range),FILE_APPEND);
	     return $range;
	}
	return null;
    }
}
$f=new FileDownload();
$f->download('http://down.golaravel.com/laravel/laravel-master.zip','',true);
