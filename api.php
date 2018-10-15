<?php
  error_reporting(0);
  define('HERE' , dirname(__FILE__));
  require_once('config.php');
  require_once('util.php');
  require_once('class/passage.php');
  require_once('class/user.php');
  require_once('class/comm.php');
  
  //$DB->debug=true;
  
  header('Content-Type:text/js;charset=utf-8');
  if(isset($_REQUEST['callback'])){
    $JSONP_FUNC=str_replace(' ' ,'',$_REQUEST['callback']);
    $JSONP_FUNC=str_replace(';' ,'',$JSONP_FUNC);
    $JSONP_FUNC=str_replace(')' ,'',$JSONP_FUNC);
    $JSONP_FUNC=str_replace('(' ,'',$JSONP_FUNC);
    $JSONP=true;
  }else{
    $JSONP=false;
  }
  
  $PA=new Passage();
  
  switch(@$_GET['mode']){
    case 'gettoken':
    
      $t='';
      for($i=0;$i<30;$i++)$t.=rand(0,9);
      $_SESSION['lr_tmp_token']=$t;
      echo $t;
    
    break;
    case 'search':

      echo json_encode($PA->search($_REQUEST['kw']));

    break;
    case 'report':
    
      checkVcode() or die('-1');
      //isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->report($_REQUEST['text']) ? '1' : '0';
    
    break;
    case 'delReport':
      
      if($_SESSION['lr_sumode']!=true)die('-3');
      checkToken() or die('-1');
      
      if(isset($_GET['id']))
        echo $PA->delReport(intval($_GET['id'])) ? '1' : '0';
      else
        echo '0';
      
    break;
    case 'getReport':
      
      checkToken() or die('-1');
      echo json_encode(
        $PA->getReport(isset($_GET['max']) ? intval($_GET['max']) : 0x7fffffff)
      );
      
    break;
    case 'delete':

      checkToken() or die('-1');
      echo $PA->del($_GET['id']) ? '1' : '0';

    break;
    case 'sendMsg':
    
      checkVcode() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $USER->sendMsg($_REQUEST['to'],$_REQUEST['text']) ? '1' : '0';
    
    break;
    case 'getMsg':
    
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo json_encode($USER->getMsg(isset($_GET['max']) ? intval($_GET['max']) : 0x7fffffff));
    
    break;
    case 'getAllMsg':
    
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo json_encode($USER->getAllMsg(isset($_GET['max']) ? intval($_GET['max']) : 0x7fffffff));
    
    break;
    case 'logout':
    
      checkToken() or die('-1');
      $USER->logout();
      echo "1";
    
    break;
    case 'login':
    
      checkVcode() or die('-1');
      echo $USER->loginByName() ? '1' : '0';
    
    break;
    case 'fork':
    
      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->fork($_REQUEST['id']);
      
    break;
    case 'upload':
    
      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->upload($_REQUEST['id']);
      
    break;
    case 'send':
    
      checkVcode() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->send($_POST['title'],$_POST['text'],$_POST['script'],0,$_REQUEST['lock']);
    
    break;
    case 'edit':
    
      checkVcode() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->edit($_GET['id'],$_POST['title'],$_POST['text']) ? '1' : '0';
    
    break;
    
    case 'setScript':
    
      checkVcode() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->setScript($_GET['id'],$_POST['script']) ? '1' : '0';
    
    break;
    case 'lock':
    
      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->lock($_GET['id'],$_GET['v']) ? '1' : '0';
    
    break;
    case "saveGame":
      
      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->savegame($_GET['id'],$_REQUEST['arg']) ? '1' : '0';
      
    break;
    case 'getSave':
      
      isset($_SESSION["lr_uinfo"]) or die('-2');
      if(isset($_GET['maxt']))
        echo json_encode($PA->getAllSave($_GET['maxt']));
      else
        echo json_encode($PA->getAllSave());
      
    break;
    case 'delSave':
    
      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->delSave($_GET['id'],$_GET['v']) ? '1' : '0';
    
    break;
    case 'score':
    
      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->score($_GET['id'],$_GET['v']) ? '1' : '0';
    
    break;
    case 'watch':
    
      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->watch($_GET['id'],$_GET['v']) ? '1' : '0';
    
    break;
    case 'watchUser':

      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      if($_GET['v']=='1')
        $r=$USER->watch(intval($_GET['id']));
      else
        $r=$USER->unwatch(intval($_GET['id']));
      echo $r ? '1' :'0';

    break;
    case 'hide':
    
      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->hide($_GET['id'],$_GET['v']) ? '1' : '0';
    
    break;
    case 'delap':
    
      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $PA->delAllPassage($_GET['id']) ? '1' : '0';
    
    break;
    case 'changePwd':
    
      checkVcode() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $USER->changepwd() ? '1' : '0';
    
    break;
    case 'setDescription':
    
      checkVcode() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      echo $USER->setDescription($_POST['descpt']) ? '1' : '0';
    
    break;
    case 'getScript':
      
      if($JSONP)echo $JSONP_FUNC,'(';
      echo json_encode($PA->getScript($_GET['id']));
      if($JSONP)echo ')';
      
    break;
    case 'getChild':
      
      if($JSONP)echo $JSONP_FUNC,'(';
      if(isset($_GET['maxc']))
        echo json_encode($PA->getChild($_GET['id'],$_GET['maxc']));
      else
        echo json_encode($PA->getChild($_GET['id']));
      if($JSONP)echo ')';
    
    break;
    case 'getFullPassage':
      
      if($JSONP)echo $JSONP_FUNC,'(';
      echo json_encode($PA->getPassage(intval($_GET['id'])));
      if($JSONP)echo ')';
      
    break;
    case 'getUserPassage':
      
      if($JSONP)echo $JSONP_FUNC,'(';
      if(isset($_GET['maxc']))
        echo json_encode($PA->getUserPassage($_GET['id'] ? $_GET['id'] : $_SESSION["lr_uid"],$_GET['maxc']));
      else
        echo json_encode($PA->getUserPassage($_GET['id'] ? $_GET['id'] : $_SESSION["lr_uid"]));
      if($JSONP)echo ')';
      
    break;
    case 'getActive' :
      
      echo json_encode($PA->getActive());
      
    break;
    case 'getUserActive' :
      
      echo json_encode($USER->getActive());
      
    break;
    case 'reg':
      
      checkVcode() or die('-1');
      if(!isset($_REQUEST['mail']))die('-3');
      if(!isset($_REQUEST['pwd'])) die('-3');
      if(!isset($_REQUEST['name']))die('-3');
      echo $USER->regbymail($_REQUEST['name'],$_REQUEST['pwd'],$_REQUEST['mail']);
      
    break;
    case 'regact':
      
      if(!isset($_REQUEST['code']))    die('-3');
      echo inputcode($_REQUEST['code']) ? '1' : '0';
      
    break;
    case 'getcommun':
      
      $CM=new Comm();
      $CM->setpid(isset($_GET['id']) ? intval($_GET['id']) : 0);
      if($JSONP)echo $JSONP_FUNC,'(';
      echo json_encode($CM->get(isset($_GET['max']) ? intval($_GET['max']) : 0x7fffffff));
      if($JSONP)echo ')';
      
    break;
    case 'sendcommun':
      
      checkVcode() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      $CM=new Comm();
      $CM->setpid(isset($_GET['id']) ? intval($_GET['id']) : 0);
      echo $CM->send($_REQUEST['c']) ? '1' : '0';
      
    break;
    case 'sendCoin':
      
      checkVcode() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      $CM=new Comm();
      $CM->setpid(isset($_GET['id']) ? intval($_GET['id']) : 0);
      echo $CM->sendCoin($_REQUEST['n']) ? '1' : '0';
      
    break;
    case 'delcommun':
      
      checkToken() or die('-1');
      isset($_SESSION["lr_uinfo"]) or die('-2');
      isset($_GET["id"])           or die('-2');
      $CM=new Comm();
      echo $CM->del($_GET['id']) ? '1' : '0';
      
    break;
    case 'su':
      checkVcode()             or die('-1');
      isset($_REQUEST['pwd'])  or die('-2');
      if(md5($_REQUEST['pwd'])==$CONFIG['supwd']){
        $_SESSION['lr_sumode']=true;
        die('1');
      }else{
        die('0');
      }
    break;
  }
?>
