<?php

// В этом файле можно выполнить те функции, которые недоступные пока что в редакторе:
// - Создание нового файла
// - Перенос файла или папки
// ATTENTION! Это реально исполняемые команды, и они выполняются без проверки. Поэтому - очень аккуратно!

// HOW TO USE IT:
//  Помещаем в массив $action_list то, что планируем выполнить, отркрываем страницу и жмем на кнопку. Функция будет выполнена.

  $action_list = [
    'createFile("","Кириллица, привет!.php");',
    'moveFile("Кириллица, привет!.php","../Кириллица, привет!.php");'
    ];
  
  function createFile($foldername = "", $filename){
    $filepath = $foldername.$filename;
    if(!file_exists($filepath)){
      file_put_contents($filepath, "");
      echo "<p><b>".__FUNCTION__."!</b> Файл <u>".$filepath."</u> Создан!</p>";
    }else{
      echo "<p><b>Ошибка ".__FUNCTION__."!</b> Файл <u>".$filepath."</u> уже существует. ON LINE ".__LINE__."</p>";
    }
  }
  
  function moveFile($oldFileName, $newFileName){
    $NEWpathinfo = pathinfo($newFileName);
    if(strlen($NEWpathinfo['dirname']) AND !is_dir($NEWpathinfo['dirname'])){
      mkdir($NEWpathinfo['dirname'], 0777, true);
    }
    if(file_exists($newFileName)){
      echo "<p><b>Ошибка ".__FUNCTION__."!</b> Файл <u>".$newFileName."</u> уже существует. Не желательно его было бы перезаписывать. ON LINE ".__LINE__."</p>";
      return false;
    }
    
    if(file_exists($oldFileName)){
      if(rename($oldFileName,$newFileName)){
        echo "<p>файл <u>".$oldFileName."</u> перемещен в <b>".$newFileName."</b>. ON LINE ".__LINE__."</p>";
      }else{
        echo "<p><b>Ошибка ".__FUNCTION__."!</b>. ON LINE ".__LINE__."</p>";
      }
    }else{
      echo "<p><b>Ошибка ".__FUNCTION__."!</b> Файл <u>".$oldFileName."</u> не найден. ON LINE ".__LINE__."</p>";
    }
  }
  

  
  echo "<p>Выполнить функцию:</p>";
  echo "<ol>";
  foreach($action_list as $v){
    echo "<li><a href='".$_SERVER['PHP_SELF']."?foo=".$v."' style='text-decoration:none;'>".$v."</a></li>";
  }
  echo "</ol>";
  
  if(in_array($_GET['foo'], $action_list)){
    eval($_GET['foo']);
  }else{
    echo "Неизвестная функция <b>".$_GET['foo']."</b>. Не Хакер ли ты часом? ммМ!?...";
  }
  