<?php
  define('HERE',dirname(__FILE__));
  require_once('config.php');
  require_once('util.php');
  require_once('class/passage.php');
  require_once('class/user.php');
  require_once('class/comm.php');
  
  if(!isset($_SESSION['lr_uid']))die();
  
  if($_SESSION["lr_uinfo"]['send']==1)die();
  
  $USER->actsend($_SESSION['lr_uid']);
?>