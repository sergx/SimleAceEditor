<?php
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
    $contents = fread($handle, filesize($default_folder.$filename));
    fclose($handle);
    return $contents;
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
        default:
          $ce->returnError("Line ".__LINE__.": Пустое поле action");
          break;
      }
    }
 
 


















?>