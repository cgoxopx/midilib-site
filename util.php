<?php
  defined("HERE") or die();
  session_start();
  ob_start();
  header('Content-Type:text/html;charset=utf-8');
  require_once(HERE.'/smarty/Smarty.class.php');
  require_once(HERE.'/hook/config.php');
  require_once(HERE.'/class/user.php');
  
  $S = new Smarty;
  $S->force_compile  = true;
  //$S->debugging      = true;
  $S->caching        = true;
  $S->cache_lifetime = 120;
  switch(@$_COOKIE['lr_temp']){
    default:
      $S->template_dir=HERE."/temp/def";
    break;
  }
  $S->compile_dir =HERE."/cache";
  $S->cache_dir   =HERE."/cache";
  
  @$S->assign('UserInfo',$_SESSION["lr_uinfo"]);
  @$S->assign('MyUid'   ,intval($_SESSION["lr_uid"]));
  @$S->assign('Config'  ,$CONFIG);
  if(isset($_SESSION["lr_uid"])){
    @$S->assign('HaveMsg',$USER->haveMsg());
  }

  $addrFault=$USER->checkAddr();
  if($addrFault)
    $_SESSION['addrFault']=$addrFault;


  function checkVcode(){
    if($_SESSION['lr_vcode']==null)return false;
    if($_SESSION['lr_vcode']!=$_REQUEST['vcode']){
      $_SESSION['lr_vcode']=null;
      return false;
    }else{
      $_SESSION['lr_vcode']=null;
      return true;
    }
  }
  
  function checkToken(){
    if($_SESSION['lr_tmp_token']==null)return false;
    if($_SESSION['lr_tmp_token']!=$_REQUEST['tmp_token']){
      $_SESSION['lr_tmp_token']=null;
      return false;
    }else{
      $_SESSION['lr_tmp_token']=null;
      return true;
    }
  }
  
  function inputcode($code){
    global $USER;
    global $DB;
    $arr=explode(':',$code);
    if(!isset($arr[0]))return false;
    switch($arr[0]){
      case 'new':
        return $USER->regact($arr[1],$arr[2]);
      break;
    }
  }
  function getTime(){
    return date('Y.m.d h:i:s a');
  }
  function titleFilter($s){
  	 $str=htmlspecialchars($s);
  	 $str=str_replace('"' ,'&#'.'34;',$str);
  	 $str=str_replace("'" ,'&#'.'39;',$str);
  	 return $str;
  }
  function passageFilter($s){
  	 $str=htmlspecialchars($s);
  	 $str=str_replace('"' ,'&#'.'34;',$str);
  	 $str=str_replace("'" ,'&#'.'39;',$str);
  	 $str=str_replace("\n","<br>\n" ,$str);
  	 return $str;
  }
?>
