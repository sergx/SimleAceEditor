<?php

session_start();
$key = substr(sha1("admin_session"),0,8);
if(isset($_GET['exit'])){
  unset($_SESSION[$key]);
}
require_once 'auth_.php';
$parol = $cqr_parol;
if(!empty($_POST)){
  if(!empty($_POST['name'])){
    $_SESSION[$key] = false;
  }else{
    if(sha1($_POST['parol']) === $parol){
      $_SESSION[$key] = true;
    }
  }
}
if(empty($_SESSION[$key])){
if(isset($_GET['enter'])){

?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
  <input type="text" name="login">
  <input type="password" name="parol">
  <input type="submit">
</form>
<?php
}
http_response_code(404);
exit(); }