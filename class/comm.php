<?php
  defined("HERE") or die("comm error");
  require_once(HERE.'/class/db.php');
  require_once(HERE.'/class/user.php');
  require_once(HERE.'/class/passage.php');
  class Comm{
    private $pid;
    public function setpid($id){
      $this->pid=intval($id);
    }
    public function __construct(){
      global $CONFIG;
    }
    public function get($max=0x7fffffff){
      $m=intval($max);

      $d=new dbDlist('comm_posts_'.intval($this->pid));

      if($m==intval(0x7fffffff))
          $d->seekRoot();
      else
          $d->seek($m);
      
      $res=array();

      for($i=0;$i<20;$i++){

          if($d->id){

              if($cont=storage_read('comm_post_'.intval($d->id))){

                  $arr=json_decode($cont,true);
                  $arr['id']=intval($d->id);
                  $arr['pid']=intval(storage_read('comm_post_pid_'.intval($d->id)));
                  $arr['uid']=intval(storage_read('comm_post_uid_'.intval($d->id)));
                  $res[]=$arr;
              }

          }

          if(!$d->seekNext())
              break;

      }
      
      
      global $HOOK_CONF;
      if(isset($HOOK_CONF['comm']['get']))
        $HOOK_CONF['comm']['get']($max,$res,$this->pid);
      
      return $res;
    }
    public function del($id){
      global $DB;
      
      global $HOOK_CONF;
      if(isset($HOOK_CONF['comm']['del']))
        $HOOK_CONF['comm']['del']($id,$this->pid);
      
      $fid=intval($id);
      $fuid =intval($_SESSION["lr_uid"]);
      $puid=intval(storage_read('comm_post_uid_'.$fid));
      $pid=intval(storage_read('comm_post_pid_'.$fid));
      
      if($fuid==0 || $pid==0 || $puid==0)
          return false;
      
      if(
         $_SESSION["lr_uinfo"]['admin']!=1 && 
         $fuid!=$puid
      )
          return false;
      
      dbDlist_remove('comm_posts_'.$pid , $fid);
      storage_del('comm_post_'.$fid);
      storage_del('comm_post_uid_'.$fid);
      storage_del('comm_post_pid_'.$fid);
      
      return true;
    }
    public function send($c,$ht=false,$chkacl=true){
      global $HOOK_CONF;
      if(isset($HOOK_CONF['comm']['send']))
        $HOOK_CONF['comm']['send']($c,$ht,$chkacl,$this->pid);
      
      if(intval($_SESSION["lr_uid"])==0)return false;
      
      if($chkacl)
        if(@$_SESSION["lr_uinfo"]['commun']!=1)return false;
      
      if(strlen($c)<15)return false;
      if($ht)
        $fc=$c;
      else
        $fc=htmlspecialchars($c);
      $unm  =$_SESSION["lr_uname"];
      $fuid =intval($_SESSION["lr_uid"]);
      
      $fid=intval($this->pid);
      
      $pstr=json_encode(
          array(
              'time'=>getTime(),
              'uname'=>$unm,
              'cont'=>$fc
          )
      );
      $nid=dbCount_getId('comm_id');
      
      dbDlist_push('comm_posts_'.$fid , $nid);
      
      storage_put('comm_post_'.$nid , $pstr);
      storage_put('comm_post_uid_'.$nid , $fuid);
      storage_put('comm_post_pid_'.$nid , $fid);
      
      return true;
    }
    public function sendCoin($num){
      global $USER;
      global $HOOK_CONF;
      if(isset($HOOK_CONF['comm']['sendCoin']))
        $HOOK_CONF['comm']['sendCoin']($num,$this->pid);
      
      $PA=new Passage();
      $n=intval($num);
      $uif=$PA->getPassage($this->pid);
      if(!is_array($uif))                 return false;
      $uid=intval($uif['uid']);
      if($uid==0)                         return false;
      if(!($USER->sendCoin($uid,$n)))     return false;
      $this->send("<span style='color:#f99;'>赠送硬币{$n}</span>",true,false);
      return true;
    }
  }
