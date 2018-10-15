<?php
  defined("HERE") or die("user error");
  require_once(HERE.'/class/db.php');
  //require_once(HERE.'/class/sendmail.php');
  class User{
    public $tbln;
    public $tblr;
    public $tblm;
    public $loged;
    public function __construct(){
      $this->loged    =false;
    }
    public function getActive(){
      if(!isset($_SESSION["lr_uid"]))
        return false;
      
      $uid=intval($_SESSION["lr_uid"]);
      $m=intval($max);
      
      $d=new dbDlist('user_watch_list_'.$uid.$uid);
      
      $d->seekRoot();
      
      $res=array();
      while(1){
          if($d->id){
              $fid=intval($d->id);
              $lastId=intval(storage_read('user_lastPostId_'.$fid));
              $uname=storage_read('user_name_'.$fid);
              
              if($uname){
                  $arr['from']      =$uid;
                  $arr['to']        =$fid;
                  $arr['uname']    =$uname;
                  $arr['id']        =$fid;
                  $arr['auTime']   =intval(storage_read('user_auTime_'.$fid));
                  $arr['lastPost']  =storage_read('user_lastPost_'.$fid);
                  $arr['lastPostId']=$lastId;
                  $arr['postNum']  =dbCount_get('user_postNum_'.$fid);
                  
                  $res[]=$arr;
              }
          }
          if(!$d->seekNext())
              break;
      }
      
      return $res;
    }
    public function getwatch($to){
      global $CONFIG;
      global $DB;
      if(!isset($_SESSION["lr_uid"]))
        return false;
      $uid=intval($_SESSION["lr_uid"]);
      $tuid=intval($to);
      if(storage_read('user_watch_'.$uid.'_'.$tuid))
          return true;
      else
          return false;
    }
    public function user_exist($uid){
      if(storage_read('user_name_'.intval($uid)))
        return true;
      else
        return false;
    }
    public function unwatch($to){
      global $CONFIG;
      global $DB;
      if(!isset($_SESSION["lr_uid"]))
        return false;
      $uid=intval($_SESSION["lr_uid"]);
      $tuid=intval($to);
      locker_lock('user_watch_'.$uid.'_'.$tuid);
        if(storage_read('user_watch_'.$uid.'_'.$tuid)){
          storage_del('user_watch_'.$uid.'_'.$tuid);
          dbDlist_remove('user_watch_list_'.$uid , $tuid);
        }
      locker_unlock('user_watch_'.$uid.'_'.$tuid);
      return true;
    }
    public function watch($to){
      global $CONFIG;
      global $DB;
      if(!isset($_SESSION["lr_uid"]))
        return false;
      $uid=intval($_SESSION["lr_uid"]);
      $tuid=intval($to);
      if(!$this->user_exist($tuid))
        return false;
      locker_lock('user_watch_'.$uid.'_'.$tuid);
        if(!storage_read('user_watch_'.$uid.'_'.$tuid)){
          storage_put('user_watch_'.$uid.'_'.$tuid , '1');
          dbDlist_push('user_watch_list_'.$uid , $tuid);
        }
      locker_unlock('user_watch_'.$uid.'_'.$tuid);
      return true;
    }
    public function setLastPost($pid,$txt){
      global $CONFIG;
      global $DB;
      if(!isset($_SESSION["lr_uid"]))
        return false;
      $uid=intval($_SESSION["lr_uid"]);
      if(!$this->user_exist($uid))
        return false;
      $id =intval($pid);
      $t  =$txt;
      $tm =time();
      dbCount_incr('user_postNum_'.$uid , -1);
      storage_put('user_lastPost_'.$uid , $t);
      storage_put('user_lastPostId_'.$uid , $id);
      storage_put('user_auTime_'.$uid , $tm);
      return (($DB->affect())>0);
    }
    public function postNumDec(){
      global $CONFIG;
      global $DB;
      if(!isset($_SESSION["lr_uid"]))
        return false;
      $uid=intval($_SESSION["lr_uid"]);
      if(!$this->user_exist($uid))
        return false;
      dbCount_incr('user_postNum_'.$uid , -1);
      return true;
    }
    public function sendMsg($to,$text){
      global $CONFIG;
      global $DB;
      if(!isset($_SESSION["lr_uid"]))
        return false;
      
      $uid=intval($_SESSION["lr_uid"]);
      $fto=intval($to);
      if(!$this->user_exist($fto))
          return false;
      
      $t='from:'.$_SESSION["lr_uname"].'<br>'.htmlspecialchars($text);
      $mid=dbCount_getId('user_msg_id');
      
      storage_put('user_msg_'.$mid , 
          json_encode(
              array(
                  'from' => $uid,
                  'to'   => $fto,
                  'text' => $t,
                  'time' => getTime()
              )
          )
      );
      dbDlist_push('user_msg_all_'.$fto , $mid);
      dbDlist_push('user_msg_unread_'.$fto , $mid);
      return true;
    }
    public function getAllMsg($max=0x7fffffff){
      if(!isset($_SESSION["lr_uid"]))
        return false;
      
      $uid=intval($_SESSION["lr_uid"]);
      $m=intval($max);
      
      $d=new dbDlist('user_msg_all_'.$uid);
      
      if($m==intval(0x7fffffff))
          $d->seekRoot();
      else
          $d->seek($m);
      
      $res=array();
      for($i=0;$i<20;$i++){
          if($d->id){
              if($cont=storage_read('user_msg_'.intval($d->id))){
                  $arr=json_decode($cont,true);
                  $arr['id']=intval($d->id);
                  $res[]=$arr;
              }
          }
          if(!$d->seekNext())
              break;
      }
      
      return $res;
    }
    public function getMsg($max=0x7fffffff){
      if(!isset($_SESSION["lr_uid"]))
        return false;
      
      $uid=intval($_SESSION["lr_uid"]);
      $m=intval($max);
      
      $d=new dbDlist('user_msg_unread_'.$uid);
      
      if($m==intval(0x7fffffff))
          $d->seekRoot();
      else
          $d->seek($m);
      
      $res=array();
      $ids=array();
      
      for($i=0;$i<20;$i++){
          if($d->id){
              if($cont=storage_read('user_msg_'.intval($d->id))){
                  $arr=json_decode($cont,true);
                  $arr['id']=intval($d->id);
                  $res[]=$arr;
                  $ids[]=intval($d->id);
              }
          }
          if(!$d->seekNext())
              break;
      }
      foreach($ids as $id){
          dbDlist_remove('user_msg_unread_'.$uid , $id);
      }
      
      return $res;
    }
    public function haveMsg(){
      if(!isset($_SESSION["lr_uid"]))
        return false;
      $uid=intval($_SESSION["lr_uid"]);
      $d=new dbDlist('user_msg_unread_'.$uid);
      $d->seekRoot();
      if($d->id)
          if(storage_read('user_msg_'.intval($d->id)))
              return true;
      return false;
    }
    public function getSendTime(){
      if(!isset($_SESSION["lr_uid"]))
        return -1;
      $uid=intval($_SESSION["lr_uid"]);
      if(!$this->user_exist($uid))
        return -1;
      $uid=intval($_SESSION["lr_uid"]);
      return intval(storage_read('user_sendTime_'.$uid));
    }
    public function canSend(){
      global $CONFIG;
      $t=$this->getSendTime();
      if($t==-1)return false;
      return (time()-$t)>$CONFIG['sendTime'];
    }
    public function checkSend(){
      global $CONFIG;
      global $DB;
      if(!isset($_SESSION["lr_uid"]))
        return false;
      $uid=intval($_SESSION["lr_uid"]);
      $len=intval($CONFIG['sendTime']);
      $tm =time();
      $ntm=time()+$len;
      if(!$this->user_exist($uid))
        return false;
      $stm=intval(storage_read('user_sendTime_'.$uid));
      if($stm==-1)
        return false;
      if($stm>$tm)
        return false;
      storage_put('user_sendTime_'.$uid , $ntm);
      $_SESSION["lr_uinfo"]['sendTime']=$ntm;
      return true;
    }
    public function updateSendTime(){
      global $CONFIG;
      global $DB;
      if(!isset($_SESSION["lr_uid"]))
        return false;
      $uid=intval($_SESSION["lr_uid"]);
      if(!$this->user_exist($uid))
        return false;
      $tm=time()+intval($CONFIG['sendTime']);
      storage_put('user_sendTime_'.$uid , $tm);
      return true;
    }
    public function banUser($uid,$tmlen){
      global $DB;
      if(!isset($uid))
        return false;
      $uid=intval($uid);
      if(!$this->user_exist($uid))
        return false;
      if($tmlen==-1)
        $tm=-1;//ban forever
      else
        $tm=time()+$tmlen;
      storage_put('user_sendTime_'.$uid , $tm);
      return true;
    }
    public function sendCoin($id,$num){
      if(!isset($_SESSION["lr_uid"]))     return false;
      if(intval($_SESSION["lr_uid"])==$id)return false;
      if($num<=0)                         return false;
      if(!is_array($this->getuser($id)))  return false;
      if(!$this->costMyCoin(-$num))       return false;
      
      global $HOOK_CONF;
      if(isset($HOOK_CONF['user']['sendCoin']))
        $HOOK_CONF['user']['sendCoin']($id,$num);
      
      return $this->costUserCoin($id,$num);
    }
    public function costMyCoin($num){
      $uid=intval($_SESSION["lr_uid"]);
      if($uid==0)return false;
      $n=intval($num);
      if($this->costUserCoin($uid,$n)){
        $_SESSION["lr_uinfo"]['coin']+=$n;
        return true;
      }else
        return false;
    }
    public function costUserCoin($id,$num){
      locker_lock('user_coin_'.intval($id));
      $ret=$this->costUserCoin_unsafe($id,$num);
      locker_unlock('user_coin_'.intval($id));
      return $ret;
    }
    public function costUserCoin_unsafe($id,$num){
      global $DB;
      global $HOOK_CONF;
      
      if(isset($HOOK_CONF['user']['costUserCoin']))
        $HOOK_CONF['user']['costUserCoin']($id,$num);
      
      $fid=intval($id);
      $n  =intval($num);
      if(!$this->user_exist($fid))
        return false;
      
      if($n>0){
        dbCount_incr('user_coin_'.$fid , $n);
        return true;
      }else
      if($n<0){
        $c=dbCount_get('user_coin_'.$fid);
        if(($c+$n)<0)
          return false;
        dbCount_incr('user_coin_'.$fid , $n);
        return true;
      }else
        return false;
    }
    public function setDescription($txt){
      global $DB;
      global $HOOK_CONF;
      if(isset($HOOK_CONF['user']['setDescription']))
        $HOOK_CONF['user']['setDescription']($txt);
      
      $uid=intval($_SESSION["lr_uid"]);
      if($uid==0)
        return false;
      
      if(!$this->user_exist($uid))
        return false;
      
      $t=htmlspecialchars($txt);
      
      storage_put('user_descpt_'.$uid , $t);
      
      return true;
    }
    public function actsend($id){
      $fid=intval($id);
      if($this->user_exist($fid))
        storage_put('user_send_'.$fid , '1');
    }
    public function getuser($id){
      $fid=intval($id);
      
      $unmae=storage_read('user_name_'.$fid);
      if(!$uname)
        return false;
      
      $res['id']       =$fid;
      $res['uname']   =$uname;
      $res['mail']     =storage_read('user_mail_'.$fid);                  //varchar(64) unique,
      $res['descpt']   =storage_read('user_descpt_'.$fid);               //text,
      $res['coin']     =dbCount_get('user_coin_'.$fid);                 //int    default 0,
      $res['admin']   =intval(storage_read('user_admin_'.$fid));       //int(1) default 0,
      $res['send']     =intval(storage_read('user_send_'.$fid));        //int(1) default 0,
      $res['commun']  =intval(storage_read('user_commun_'.$fid));     //int(1) default 1,
      $res['sendTime']=intval(storage_read('user_sendTime_'.$fid));   //bigint default 0,
      $res['auTime']   =intval(storage_read('user_auTime_'.$fid));     //bigint default 0,
      $res['lastPost']  =storage_read('user_lastPost_'.$fid);            //varchar(512) COLLATE utf8_bin DEFAULT NULL
      $res['lastPostId']=intval(storage_read('user_lastPostId_'.$fid)); //int(11) DEFAULT NULL
      $res['postNum']  =dbCount_get('user_postNum_'.$fid);            //int(10) unsigned NOT NULL DEFAULT '0'
      
      global $HOOK_CONF;
      if(isset($HOOK_CONF['user']['getuser']))
        $HOOK_CONF['user']['getuser']($id,$res);
      
      return $res;
    }
    public function regbymail($uname,$pwd,$mail){
      global $DB;
      $n=$uname;
      $p=hash("sha256",$pwd);
      $m=$mail;
      if(!filter_var($mail,FILTER_VALIDATE_EMAIL))
        return 0;
      $t=$this->gettoken();
      
      if(storage_read('user_name_to_id_'.$n))
         return 0;
      
      if(storage_read('user_mail_to_id_'.$m))
         return 0;
      
      $id=dbCount_getId('user_reg_id');
      
      storage_put('user_reg_token_'.$t ,
          json_encode(
              array(
                  'uname' => $n ,
                  'pwd' => $p ,
                  'mail' => $m
              )
          )
      ,3600);
      
      $actcode="new:{$id}:{$t}";
      
      return $actcode;

      //$res=sendmail($mail,'register',"
      //  actcode:
      //    {$actcode}
      //");
      
      //if($res)
      //   return true;
      //else 
      //  return false;
    }
    public function regact($id,$token){
      global $DB;
      $fid=intval($id);
      $key='user_reg_token_'.$token;
      
      $res=storage_read($key);
      if(!$res)
        return false;
      
      storage_del($key);
      $rr=json_decode($res , true);
      
      return $this->reg($rr['uname'],$rr['pwd'],$rr['mail']);
    }
    public function gettoken(){
      $t='';
      for($i=0;$i<127;$i++){
        $t.=rand(0,9);
      }
      return $t;
    }
    public function changepwd(){
      global $DB;
      global $HOOK_CONF;
      if(isset($HOOK_CONF['user']['changepwd']))
        $HOOK_CONF['user']['changepwd']();
      
      $o=$_REQUEST['op'];
      $n=$_REQUEST['np'];
      $fo1 =md5($o);
      $fo2 =hash("sha256",$o);
      $fn  =hash("sha256",$n);
      $fid =intval($_SESSION["lr_uid"]);
      
      $dbpwd=storage_read('user_pwd_'.$fid);
      
      if(!$dbpwd)
          return false;
      
      if(!($dbpwd==$fo1 || $dbpwd==$fo2))
          return false;
      
      storage_put('user_pwd_'.$fid , $fn);
      
      return true;
    }
    public function reg($uname,$pwd,$mail){
      global $DB;
      global $HOOK_CONF;
      if(isset($HOOK_CONF['user']['register']))
        $HOOK_CONF['user']['register']($uname,$pwd,$mail);
      
      $unm =htmlspecialchars($uname);
      $upw =hash("sha256",$pwd);
      if(strlen($unm)>64)
        return false;
      $t=$this->gettoken();
      
      
      $nti='user_name_to_id_'.$unm;
      if(storage_read($nti))
        return false;
      
      $mti='user_mail_to_id_'.$mail;
      if(storage_read($mti))
        return false;
      
      $uid=dbCount_getId('user_id');
      
      storage_put($nti , $uid);
      storage_put($mti , $uid);
      
      storage_put('user_name_'.$uid , $unm);
      storage_put('user_pwd_'.$uid , $upw);
      storage_put('user_token_'.$uid , $t);
      storage_put('user_mail_'.$uid , $mail);
      
      setcookie('lr_uid',strval($uid),time()+100*24*3600);
      setcookie('lr_pwd',$t          ,time()+100*24*3600);
      
      return true;
    }
    public function login(){
      $uid=intval($_COOKIE['lr_uid']);
      $pwd=$_COOKIE['lr_pwd'];
      
      if($uid==0)
          return false;
      
      $dbtoken=storage_read('user_token_'.$uid);
      $dbpwd=storage_read('user_pwd_'.$uid);
      
      if(!$dbpwd)
          return false;
      
      if($dbtoken===$pwd && $dbtoken){
        
      }else
      if(($dbpwd===md5($pwd)) || ($dbpwd===hash("sha256",$pwd))){
        $t=$this->gettoken();
        storage_put('user_token_'.$uid , $t);
        setcookie('lr_uid',"{$uid}",time()+100*24*3600);
        setcookie('lr_pwd',$t      ,time()+100*24*3600);
      }else{
        $this->delCookie();
        return false;
      }
      
      $_SESSION["lr_uinfo"] =$this->getuser($uid);
      $_SESSION["lr_uid"]   =$uid;
      $_SESSION["lr_uname"] =storage_read('user_name_'.$uid);
      
      return true;
    }
    public function loginByName(){
      global $HOOK_CONF;
      if(isset($HOOK_CONF['user']['login']))
        $HOOK_CONF['user']['login']();
      
      $uid=$_REQUEST['uid'];
      $pwd=$_REQUEST['pwd'];
      $fid=htmlspecialchars($uid);
      $uid=intval(storage_read('user_name_to_id_'.$fid));
      
      if($uid==0)
        return false;
      
      $dbtoken=storage_read('user_token_'.$uid);
      $dbpwd=storage_read('user_pwd_'.$uid);
      
      if(!$dbpwd)
          return false;
      
      if($dbtoken===$pwd && $dbtoken){
        
      }else
      if(($dbpwd===md5($pwd)) || ($dbpwd===hash("sha256",$pwd))){
        $t=$this->gettoken();
        storage_put('user_token_'.$uid , $t);
        setcookie('lr_uid',"{$uid}",time()+100*24*3600);
        setcookie('lr_pwd',$t      ,time()+100*24*3600);
      }else{
        $this->delCookie();
        return false;
      }
      
      $_SESSION["lr_uinfo"] =$this->getuser($uid);
      $_SESSION["lr_uid"]   =$uid;
      $_SESSION["lr_uname"] =storage_read('user_name_'.$uid);
      
      return true;
    }
    public function delCookie(){
      setcookie('lr_uid',"",time()-1);
      setcookie('lr_pwd',"",time()-1);
    }
    public function logout(){
      if(isset($_SESSION["lr_uid"])){
        $fid=intval($_SESSION["lr_uid"]);
        if($fid!=0){
          $t=$this->gettoken();
          storage_put('user_token_'.$fid , $t);//change token to kick user out
        }
      }
      //delete cookie
      setcookie('lr_uid',"",time()-1);
      setcookie('lr_pwd',"",time()-1);
      //delete session
      $_SESSION["lr_uinfo"] =null;
      $_SESSION["lr_uid"]   =null;
      $_SESSION["lr_uname"] =null;
      
    }
    public function autologin(){
      if($this->loged){
        return true;
      }
      $this->loged=true;
      if(!isset($_COOKIE['lr_uid']))return false;
      if(!isset($_COOKIE['lr_pwd']))return false;
      if(isset($_SESSION["lr_uinfo"])){
        if(isset($_SESSION["lr_updateTime"])){
          if(abs(time()-intval($_SESSION["lr_updateTime"]))<300)
            return true;
        }
        $_SESSION["lr_updateTime"]=time();
        $fid=intval($_SESSION["lr_uid"]);
        if(is_array(
          $u=$this->getuser($fid)
        )){
          $_SESSION["lr_uinfo"] =$u;
          $_SESSION["lr_uname"] =storage_read('user_name_'.$fid);
        }
        return true;
      }else
        return $this->login();
    }
    public function getLastAddr(){
      $uid=intval($_SESSION["lr_uid"]);
      if($uid==0)
        return null;
      $addr=storage_read('user_addr_'.$uid);
      if($addr)
        return $addr;
      else
        return null;
    }
    public function getAddr(){
      if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && 
          $_SERVER['HTTP_X_FORWARDED_FOR'] && 
          strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], 'unknown')
      ){
          $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }else
      if(isset($_SERVER['HTTP_CLIENT_IP']) &&
          $_SERVER['HTTP_CLIENT_IP'] &&
          strcasecmp($_SERVER['HTTP_CLIENT_IP'], 'unknown')
      ){
          $realip = $_SERVER['HTTP_CLIENT_IP'];
      }else{
          $realip = $_SERVER['REMOTE_ADDR'];
      }
      $realip=explode(',',$realip)[0];
      return $realip;
    }
    public function updateAddr(){
      global $DB;
      $uid=intval($_SESSION["lr_uid"]);
      if($uid==0)
        return null;
      
      storage_put('user_addr_'.$uid , $this->getAddr());
      
      return true;
    }
    public function checkAddr(){
      $uid=intval($_SESSION["lr_uid"]);
      if($uid==0)
        return null;
      $key="usersafe_{$uid}";

      $addr=$this->getAddr();
      if($addr===getCache($key))
        return null;
      else{
        if($buf=getCache($key)){
          setCache($key,$addr);
          $this->updateAddr();
          return $buf;
        }else{
          $buf=$this->getLastAddr();
          if($buf===$addr)
            return null;
          else{
            setCache($key,$addr);
            $this->updateAddr();
            return $buf;
          }
        }
      }
    }
  }
  $USER=new User();
  $USER->autologin();

