<?php
  error_reporting(0);
  define('HERE',dirname(__FILE__));
  require_once('config.php');
  
  //$CONFIG['db']['debug']=true;
  require_once('util.php');
  require_once('class/passage.php');
  require_once('class/comm.php');
  
  if($_SESSION['addrFault']){
    $S->assign('addrFault',$_SESSION['addrFault']);
    $_SESSION['addrFault']=null;
  }

  function insert_game_arg(){
    return htmlspecialchars($_GET['args']);
  }
  
  $PA=new Passage();
  switch(@$_GET['mode']){
    case 'send':
      $S->assign('Pid',intval($_GET['id']));
      $S->display('passage_send.html');
    break;
    case 'watch':
      
      $uid=isset($_GET["uid"]) ? $_GET['uid'] : $_SESSION["lr_uid"];
      
      if($uid==null){
        $S->display('passage_watch_error.html');
        break;
      }
      
      if(isset($_GET['max']))
        $n=$PA->getWatch($uid,$_GET['max']);
      else
        $n=$PA->getWatch($uid);
      
      if(count($n)>20){
        $S->assign('ChildHaveNextPage',1);
        array_pop($n);
      }else{
        $S->assign('ChildHaveNextPage',0);
      }
      
      $S->assign('ChildNextPage',intval($n[count($n)-1]['id']));
      $S->assign('Child',$n);
        
      $S->display('passage_watch.html');
    break;
    case 'getChild':
    
      if(isset($_GET['maxc']))
        $S->assign('Child',$n=$PA->getChild($_GET['id'],$_GET['maxc']));
      else
        $S->assign('Child',$n=$PA->getChild($_GET['id']));
      $S->assign('ChildNextPage',intval($n[count($n)-1]['score']));
      $S->display('passage_getchild.html');
      
    break;
    case 'read':
    
      if((!isset($_GET['id'])) || $_GET['id']==null){
        $S->display('passage_read_error.html');
        break;
      }
      
      if(isset($_GET['args'])) {
        $S->assign('Playing',1);
        @$S->assign('Args',htmlspecialchars($_GET['args']));
      }else {
        $S->assign('Playing',0);
      }
      
      if(isset($_SESSION["lr_uinfo"]))
        $S->assign('PStatus',$PA->getStatus($_GET['id']));
      
      $tp=$PA->getPassage($_GET['id']);
      
      if(!is_array($tp)){
        $S->display('passage_read_error.html');
        break;
      }
      $S->assign('Page',$tp);
      
      $scrcode=htmlspecialchars($tp['script']);
      //$scrcode=str_replace("\n",'<br>'  ,$scrcode);
      //$scrcode=str_replace(' ' ,'&nbsp;',$scrcode);
      $S->assign('PScriptCode',$scrcode);
      
      if($_SESSION["lr_uid"] && $tp['uid']==$_SESSION["lr_uid"])
        $S->assign('IsMyPsg',1);
      else
        $S->assign('IsMyPsg',0);
      
      if(isset($_GET['maxc']))
        $S->assign('Child',$n=$PA->getChild($_GET['id'],$_GET['maxc']));
      else
        $S->assign('Child',$n=$PA->getChild($_GET['id']));
      
      $S->assign('ChildNextPage',intval($n[count($n)-1]['score']));
      
      $S->display('passage_read.html');
    
    break;
    case 'readroot':
    
      if($_GET['id']==null){
        $S->display('passage_read_error.html');
        break;
      }
      
      $id=intval($PA->index2id($_GET['id']));
      
      if(intval($id)==0){
        $S->display('passage_read_error.html');
        break;
      }
      //header('location:?mode=read&id='.intval($id));
      //$S->assign('id',intval($id));
      //$S->display('passage_jump.html');
      
      if(isset($_GET['args'])) {
        $S->assign('Playing',1);
        @$S->assign('Args',htmlspecialchars($_GET['args']));
      }else {
        $S->assign('Playing',0);
      }
      
      if(isset($_SESSION["lr_uinfo"]))
        $S->assign('PStatus',$PA->getStatus($id));
      
      $tp=$PA->getPassage($id);
      
      if(!is_array($tp)){
        $S->display('passage_read_error.html');
        break;
      }
      $S->assign('Page',$tp);
      
      $scrcode=htmlspecialchars($tp['script']);
      //$scrcode=str_replace("\n",'<br>'  ,$scrcode);
      //$scrcode=str_replace(' ' ,'&nbsp;',$scrcode);
      $S->assign('PScriptCode',$scrcode);
      
      if($_SESSION["lr_uid"] && $tp['uid']==$_SESSION["lr_uid"])
        $S->assign('IsMyPsg',1);
      else
        $S->assign('IsMyPsg',0);
      
      if(isset($_GET['maxc']))
        $S->assign('Child',$n=$PA->getChild($id,$_GET['maxc']));
      else
        $S->assign('Child',$n=$PA->getChild($id));
      
      $S->assign('ChildNextPage',intval($n[count($n)-1]['score']));
      
      $S->display('passage_read.html');
      
    /*
      if(isset($_SESSION["lr_uinfo"]))
        $S->assign('PStatus',$PA->getStatus($id));
        
      $S->assign('Page',$PA->getPassage($id));
      
      
      if(isset($_GET['maxc']))
        $S->assign('Child',$n=$PA->getChild($id,$_GET['maxc']));
      else
        $S->assign('Child',$n=$PA->getChild($id));
      $S->assign('ChildNextPage',intval($n[count($n)-1]['score']));
      
      $S->display('passage_read.html');
    */
    break;
    default:
    
      if(isset($_GET['max']))
        $n=$PA->getAll($_GET['max']);
      else
        $n=$PA->getAll();
      if(count($n)>20){
        $S->assign('ChildHaveNextPage',1);
        array_pop($n);
      }else{
        $S->assign('ChildHaveNextPage',0);
      }
      $S->assign('ChildNextPage',intval($n[count($n)-1]['time']));
      $S->assign('Passages',$n);
      
      
      //print_r($n);
      
      $S->display('passage_index.html');
    
    break;
  }
?>