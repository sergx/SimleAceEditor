<?php


/*
define('MODX_API_MODE', true);
require $_SERVER['DOCUMENT_ROOT'].'/index.php';

// Включаем обработку ошибок
$modx->getService('error','error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');



if(empty($_SESSION['modx.user.contextTokens']['mgr'])){
  $modx->sendErrorPage();
}
*/
require_once 'auth.php';


 class codeEditorACE {
  
  public function fileCasheFix($file /* string */){
    if(file_exists($file)){
      return $file."?".md5_file($file);
    }else{
      return $file;
    }
  }
  
  public function sortFile($array){
    function mySort($a, $b){
      return ($a < $b) ? -1 : 1;
    }
    
    
    return $result;
  }
  
  public function getFileSize($string){
    $units = array(
        'gb' => array('size' => 1073741824, 'label' => 'Gb'),
        'mb' => array('size' => 1048576,    'label' => 'Mb'),
        'kb' => array('size' => 1024,       'label' => 'Kb'),
        'b'  => array('size' => 0,          'label' => 'b')
    );
    $size = filesize($string);
    $unit = (isset($unit) && isset($units[$unit])) ? $unit : false;
     
    if ($size > 0) {
        if ($unit === false) {
            foreach ($units as $key => $properties) {
                if ($size >= $properties['size']) {
                    $unit = $key;
                    break;
                }
            }
        }
        if ($unit != 'b')
            $size = $size / $units[$unit]['size'];
    }
    else {
        if ($unit === false) $unit = 'b';
    }
    return round($size, 1) . ' ' . $units[$unit]['label'];
  }
  
  public function fileList($folder = false){
    $file_list = array();
    $default_folder = $_SERVER['DOCUMENT_ROOT'].'/';
    if(!$folder){
      $folder = $default_folder;
    }else{
      $folder = $default_folder.$folder;
    }
    if(!is_dir($folder)){
      $folder = $default_folder;
    }
    $files = scandir($folder);
    foreach($files as $file){
      if(!in_array($file, array(".",".."))){
        $ta = pathinfo(substr($folder.$file, strlen($default_folder)));
        if($ta['dirname'] === "."){
          $ta['dirname'] = "";
        }else{
          $ta['dirname'] .= "/";
        }
        $ta['is_dir'] = is_dir($folder.$file);
        
        if(!$ta['is_dir']){
          $ta['filesize'] = $this->getFileSize($folder.$file);
        }
        
        $file_list[] = $ta;
      }
    }
    
    function mySort($a, $b){
      if($a['is_dir'] && $b['is_dir']){
        // Если оба - папки
        return strcasecmp ( $a['basename'] , $b['basename'] );
      }
      if($a['is_dir'] && !$b['is_dir']){
        return -1;
      }
      if(!$a['is_dir'] && $b['is_dir']){
        return 1;
      }
      if(!$a['is_dir'] && !$b['is_dir']){
        // Если оба - файлы
        return strcasecmp ( $a['basename'] , $b['basename'] );
      }
    }
    
    usort($file_list, "mySort");
    
    //return print_r($file_list, true);
    return json_encode($file_list,JSON_UNESCAPED_UNICODE);
  }
  
  public function getFile($filename = false){
    $default_folder = $_SERVER['DOCUMENT_ROOT'].'/';
    $handle = fopen($default_folder.$filename, "r");
    
    $filesize = filesize($default_folder.$filename);
    if($filesize){
      $contents = fread($handle, filesize($default_folder.$filename));
    }else{
      $contents = fread($handle, 1);
    }
    fclose($handle);
    return $contents;
  }
  
  public function saveFile($input_data){
    $fwrite_info = array('error' => array());
    
    if($input_data['content'] === false || !strlen($input_data['oldfilename']) || !strlen($input_data['filename'])){
      $fwrite_info['error'][] = "Input data is wrong";
    }else{
      $default_folder = $_SERVER['DOCUMENT_ROOT'].'/';
      /*
      if(!is_dir($default_folder."sae/")){
        mkdir($default_folder."sae/", 0777, true);
      }
      */
      if($input_data['oldfilename'] != $input_data['filename']){
        // file was renenamed
        //unlink($default_folder.$input_data['oldfilename']);
        if(!file_exists($default_folder.$input_data['filename'])){
          $delete_oldfile = true;
          rename($default_folder.$input_data['oldfilename'], $default_folder.$input_data['filename']);
        }else{
          $fwrite_info['error'][] = "Can not be renamed. File ".$input_data['filename']." alredy exists. Saved to ".$input_data['oldfilename'];
          $input_data['filename'] = $input_data['oldfilename'];
        }
        
      }
        
      $fp = fopen($default_folder.$input_data['filename'], "w");
      
      $fwriten = fwrite($fp, $input_data['content']);
      
      $fwrite_info = array_merge($fwrite_info, array(
        'fwriten' => $fwriten,
        'strlen' => strlen($input_data['content']),
        'status' => $fwrite_info['strlen'] === $fwrite_info['fwriten'] ? true : false,
        ));
      fclose($fp);

    }
    
    return json_encode($fwrite_info);
    
    
/*
function fwrite_stream($fp, $string) {
    for ($written = 0; $written < strlen($string); $written += $fwrite) {
        $fwrite = fwrite($fp, substr($string, $written));
        if ($fwrite === false) {
            return $written;
        }
    }
    return $written;
}
*/
    
  }
  
  public function deleteFile($input_data){
    $return = array('error' => array());
    $default_folder = $_SERVER['DOCUMENT_ROOT'].'/';
    
    if($input_data['is_dir'] || empty($input_data['basename'])){
      $return['error'][] = "Wrong input";
      $return['error'][] = $input_data;
    }elseif(!file_exists($default_folder.$input_data['dirname'].$input_data['basename'])){
      $return['error'][] = "file ".$input_data['dirname'].$input_data['basename']." dose not exists.";
    }else{
      if(!unlink($default_folder.$input_data['dirname'].$input_data['basename'])){
        $return['error'][] = "Errow while deleting file";
      }
    }
    return json_encode($return);
  }
  
  public function returnError($string = "Ошибка.."){
    echo json_encode(array("error" => $string),JSON_UNESCAPED_UNICODE);
    die;
  }
}
 
 // Как это должно быть чтобы быть REST API
 
 $ce = new codeEditorACE();
 
  $input_data = json_decode(file_get_contents('php://input'),true);

  // $input_data = {"action":"actionName","data":"someData"}

    if(!empty($input_data)){
      switch($input_data['action']){
        case "getFolder":
          if(empty($input_data['data'])){
            $ce->returnError("Line ".__LINE__.": Пустое поле data");
          }
          echo $ce->fileList($input_data['data']);
        break;
        case "getFile":
          if(empty($input_data['data'])){
            $ce->returnError("Line ".__LINE__.": Пустое поле data");
          }
          echo $ce->getFile($input_data['data']);
        break;
        case "saveFile":
          if(empty($input_data['data'])){
            $ce->returnError("Line ".__LINE__.": Пустое поле data");
          }
          echo $ce->saveFile($input_data['data']);
        break;
        case "deleteFile":
          if(empty($input_data['data']['pathinfo'])){
            $ce->returnError("Line ".__LINE__.": Пустое поле data");
          }
          echo $ce->deleteFile($input_data['data']['pathinfo']);
        break;
        default:
          $ce->returnError("Line ".__LINE__.": Пустое поле action");
          break;
      }
    }
 
 


















?>