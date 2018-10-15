<?php
  error_reporting(0);
  define('HERE',dirname(__FILE__));
  require_once('config.php');
  require_once('util.php');
  require_once('class/user.php');
  require_once('class/passage.php');
  
  switch(@$_GET['mode']){
    case 'login':
      $S->display('user_login.html');
    break;
    case 'reg':
      $S->display('user_reg.html');
    break;
    case 'setting':
      if(!isset($_SESSION["lr_uinfo"])){
        $S->display('user_error.html');
        break;
      }
      $S->display('user_setting.html');
    break;
    default:

      if(!isset($_GET['uid'])){
        if(isset($_SESSION["lr_uid"])){
          $uid=intval($_SESSION["lr_uid"]);
        }else{
          $S->display('user_nofound.html');
          break;
        }
      }else{
        $uid=intval($_GET['uid']);
      }
      if(!is_array($tu=$USER->getuser($uid))){
        $S->display('user_nofound.html');
        break;
      }
      $S->assign('TheUser',$tu);

      if(isset($_SESSION["lr_uid"])){
        $S->assign('Loged',true);
        $S->assign('Watched',$USER->getwatch($uid));
      }
      
      $PA=new Passage();
      $S->assign('UserPassage',$PA->getUserPassage($uid));
      
      $S->display('user_index.html');
      
    break;
  }
?>