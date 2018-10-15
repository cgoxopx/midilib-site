<?php
  defined("HERE") or die("passage error");
  require_once(HERE.'/class/db.php');
  require_once(HERE.'/class/count.php');
  require_once(HERE.'/class/list.php');
  require_once(HERE.'/class/fulltext.php');
  require_once(HERE.'/class/user.php');
  class Passage{
    /*
     keys:
         report:
             common:
                 'passage_report_text_$id
             list:
                 'passage_reports
             count:
                 'passage_report_autoi
         passage:
             common:
                 'passage_post_uid_$id
                 'passage_post_root_$id
    */
    public function report($text){
        $id=dbCount_getId('passage_report_autoi');
        storage_put('passage_report_text_'.$id,$text);
        dbDlist_push('passage_reports',$id);
    }
    public function getReport($max=0x7fffffff){
      $m=intval($max);
      $d=new dbDlist('passage_reports');
      if($m==intval(0x7fffffff))
          $d->seekRoot();
      else
          $d->seek($m);
      
      $res=array();
      for($i=0;$i<20;$i++){
          if($d->id){
              if($cont=storage_read('passage_report_text_'.intval($d->id))){
                  $res[]=array(
                      'id' => $d->id,
                      'cont' => $cont
                  );
              }
          }
          if(!$d->seekNext())
              break;
      }
      
      return $res;
    }
    public function delReport($id){
      $fid=intval($id);
      storage_del('passage_report_text_'.$fid)
      return dbDlist_remove('passage_reports',$fid);
    }
    
    public function del($id){
      global $DB;
      global $USER;
      $fid=intval($id);

      if(intval($_SESSION["lr_uid"])==0)return false;
      
      $res=storage_read('passage_post_uid_'.$fid);
      if($res==null || $res==false)
          return false;
      $puid=intval($res);
      
      $res=storage_read('passage_post_root_'.$fid);
      if($res==null || $res==false)
          return false;
      $rid=intval($res);
      
      $res=storage_read('passage_post_parent_'.$fid);
      if($res==null || $res==false)
          return false;
      $pid=intval($res);

      if(
         $_SESSION["lr_uinfo"]['admin']!=1 && 
         intval($_SESSION["lr_uid"])!=$puid
      )
        return false;

      if($pid==0){
        storage_del('passage_root_'.$rid);
        storage_del('passage_root_uid_'.$rid);
        storage_del('passage_root_id_'.$rid);
        dbDlist_remove('passage_post_root_list',$rid);
        $USER->postNumDec();
      }else{
        dbCount_incr('passage_post_childNum_'.$pid , -1);//从父节点删除
        dbDlist_remove('passage_post_children_list_'.$pid , $fid);
        storage_del('passage_post_user_'.$pid.'_'.$puid);
      }
            
      dbDlist_remove('passage_post_root_children_'.$rid , $fid);
      dbDlist_remove('passage_user_posts_'.$puid , $fid);
      
      dbCount_del('passage_post_childNum_'.$fid);
      dbCount_del('passage_post_score_'.$fid);
      
      storage_del('passage_post_'.$fid);
      storage_del('passage_post_parent_'.$fid);
      storage_del('passage_post_passage_'.$fid);
      storage_del('passage_post_title_'.$fid);
      storage_del('passage_post_script_'.$fid);
      storage_del('passage_post_path_'.$fid);
      storage_del('passage_post_root_'.$fid);
      storage_del('passage_post_uid_'.$fid);
      storage_del('passage_post_hide_'.$fid);
      storage_del('passage_post_lock_'.$fid);
      storage_del('passage_post_notation_'.$fid);
      fulltext_del($fid);
      
      return true;
    }
    public function edit($iid,$title,$text){
      global $DB;
      
      global $HOOK_CONF;

      if(isset($HOOK_CONF['passage']['editTxt']))
        $HOOK_CONF['passage']['editTxt']($iid,$title,$text);
      
      if(strlen($title)>512)return false;
      
      if($title==null)return false;
      if($text ==null)return false;
      if(strlen($title)<5)  return false;
      if(strlen($text) <5)  return false;
      
      $id=intval($iid);
      if($id==0)      return false;
      $uid=intval($_SESSION["lr_uid"]);
      if($uid==0)
          return false;
      $ti=titleFilter  ($title);
      $te=passageFilter($text );
      
      $res=storage_read('passage_post_uid_'.$id);
      if($res==null || $res==false)
          return false;
      $puid=intval($res);
      
      if($_SESSION["lr_uinfo"]['admin']==1){
          
      }else{
          if($uid!=$puid)
              return false;
      }
      
      storage_put('passage_post_'.$id ,
          json_encode(
              array(
                  'title'=>$ti,
                  'editor'=>$unm,
                  'edited'=>1
              )
          )
      );
      storage_put('passage_post_title_'.$id,$ti);
      storage_put('passage_post_passage_'.$id,$te);
      
      return true;
    }
    public function setScript($iid,$script){
      global $DB;
      
      global $HOOK_CONF;
      if(isset($HOOK_CONF['passage']['editScr']))
        $HOOK_CONF['passage']['editScr']($iid,$script);
      
      if($script==null)return false;
      $id=intval($iid);
      if($id==0)       return false;
      $uid=intval($_SESSION["lr_uid"]);
      
      $sc=$script;
      
      $res=storage_read('passage_post_uid_'.$id);
      if($res==null || $res==false)
          return false;
      $puid=intval($res);
      
      if($_SESSION["lr_uinfo"]['admin']==1){
          storage_put('passage_post_script_'.$id,$sc);
      }else{
          if($uid!=$puid)
              return false;
          storage_put('passage_post_script_'.$id,$sc);
      }
      
      return true;
    }
    public function delAllPassage($iid){
      if($_SESSION["lr_uinfo"]['admin']!=1)
        return false;
      $id=intval($iid);
      
      storage_del('passage_root_'.$id);
      storage_del('passage_root_uid_'.$id);
      storage_del('passage_root_id_'.$id);
      dbDlist_remove('passage_post_root_list',$id);
        
      dbDlist_clear('passage_post_root_children_'.$id , function($id,$arg){
          $fid=intval($id);
          if($fid==0)
              return;
          $res=storage_read('passage_post_uid_'.$fid);
          if($res){
              $uid=intval($res);
              dbDlist_remove('passage_user_posts_'.$uid , $fid);
              $pid=intval(storage_read('passage_post_parent_'.$fid));
              storage_del('passage_post_user_'.$pid.'_'.$uid);
          }
          dbDlist_clear('passage_post_children_list_'.$fid);
          
          dbCount_del('passage_post_childNum_'.$fid);
          dbCount_del('passage_post_score_'.$fid);
          
          storage_del('passage_post_'.$fid);
          storage_del('passage_post_parent_'.$fid);
          storage_del('passage_post_passage_'.$fid);
          storage_del('passage_post_title_'.$fid);
          storage_del('passage_post_script_'.$fid);
          storage_del('passage_post_path_'.$fid);
          storage_del('passage_post_root_'.$fid);
          storage_del('passage_post_uid_'.$fid);
          storage_del('passage_post_hide_'.$fid);
          storage_del('passage_post_lock_'.$fid);
          storage_del('passage_post_notation_'.$fid);
          fulltext_del($fid);
      });
      return true;
    }
    public function index2id($id){
      $fid=intval($id);
      return intval(storage_read('passage_post_root_id_'.$fid));
    }
    public function getShowing($str){
      $len=mb_strlen($str, 'utf-8');
      if($len<32)
        return htmlspecialchars($str);
      else
        return htmlspecialchars(mb_substr($str,0,29,'utf-8').'...');
    }
    public function search($sstr){
      $kw='';
      $kws=explode('|',$sstr);
      if(count($kws)>4)
        return null;
      foreach($kws as $str){
        $arr=explode(' ',$str);
        $len=count($arr);
        if($len<7 || $len>32)
          return null;
        for($i=0;$i<$len;$i++){
          $arr[$i]=intval($arr[$i]);
        }
        for($i=0;$i<$len-3;$i++){
          for($j=0;$j<4;$j++){
            $kw.=$arr[$i+$j];
            $kw.='_';
          }
          $kw.=' ';
        }
      }
      $kw=str_replace('-','u',$kw);
      $arr=fulltext_search($kw);
      $res=array();
      foreach($arr as $item){
          $fid=intval($item);
          if(!$fid)
              continue;
          
          $itemuid=intval(storage_read('passage_post_uid_'.$fid));
          if(!$itemuid)
              continue;
          
          $res[]=array(
              'id'      => $fid ,
              'uid'     => $itemuid ,
              'pid'     => intval(storage_read('passage_post_parent_'.$fid)) ,
              'rid'     => intval(storage_read('passage_post_root_'.$fid)) ,
              'uname'  => storage_read('user_name_'.$itemuid) ,
              'title'   => storage_read('passage_post_title_'.$fid) ,
              'path'    => storage_read('passage_post_path_'.$fid)
          );
      }
      
      return $res;
    }
    public function isMidiFile($path){
      $file = fopen($path, "rb");
      $head = fread($file, 4);
      fclose($file);
      if($head=='MThd')
        return true;
      else
        return false;
    }
    public function upload($id){
      if(!isset($_FILES['file']))
        return "0";
      //if($_FILES['file']['type']!="audio/mid")
      //  return "-5";
      if(!$this->isMidiFile($_FILES["file"]["tmp_name"]))
        return -5;
      if($_FILES["file"]["error"] > 0)
        return "-4";
      if($_FILES["file"]["size"] > 4*1024*1024)
        return "-3";
      if($_FILES["file"]["size"] < 64)
        return "-2";
      $md5 = md5_file($_FILES["file"]["tmp_name"]);
      
      if(($erc=$this->setPath($id,$md5))==0){
        $path=HERE."/upload/" . $md5 .".mid";
        if(!file_exists($path)){
          move_uploaded_file(
            $_FILES["file"]["tmp_name"],
            $path
          );
          exec("cd upload && midi-index {$md5}.mid {$md5}.txt");
        }

        $file = fopen("upload/{$md5}.txt", "r");
        $notation = fread($file, 65535);
        fclose($file);

        if($notation){

          $notation=str_replace('-','u',$notation);
          storage_put('passage_post_notation_'.intval($id) , $notation);
          fulltext_put(intval($id) , $notation);
        }
        return "1";
      }else
        return $erc;
    }
    public function setPath($id,$path){
      global $DB;
      global $USER;
      $uid=intval($_SESSION["lr_uid"]);
      if($uid==0)
        return -1;
      $pid=intval($id);
      
      $r=storage_read('passage_post_title_'.$pid);
      if($r==null || $r==false)
         return -6;

      storage_put('passage_post_path_'.$fid , $path);
      return 0;
    }
    public function fork($pid,$lo='0'){
      global $DB;
      global $USER;
      if(!($USER->checkSend()))return -3;
      if(@$_SESSION["lr_uinfo"]['send']!=1)return -2;
      
      $id   =intval($pid);
      $fuid =intval($_SESSION["lr_uid"]);
      $tm   =time();
      $unm  =$_SESSION["lr_uname"];

      if(storage_read('passage_post_user_'.$id.'_'.$fuid))
        return -8;
      
      if(!storage_read('passage_post_'.$id))
        return -7;
        
      if(
        storage_read('passage_post_lock_'.$id)==1 && 
        intval(storage_read('passage_post_uid_'.$id))!=$fuid)
        return -6;
      
      $ti   =storage_read('passage_post_title_'.$id);
      $te   =storage_read('passage_post_passage_'.$id);
      $path =storage_read('passage_post_path_'.$id);
      $rid  =storage_read('passage_post_root_'.$id);
      $fs   =storage_read('passage_post_script_'.$id);
      
      $nid =dbCount_getId('passage_post_id');
      
      dbDlist_push('passage_post_root_children_'.$rid , $nid);
      dbDlist_push('passage_user_posts_'.$fuid , $nid);
      dbDlist_push('passage_post_children_list_'.$id , $nid);
      
      storage_put('passage_post_'.$nid , json_encode(
          array(
              'title'=>$ti,
              'editor'=>$unm
          )
      ));
      
      storage_put('passage_post_parent_'.$nid , '0');
      storage_put('passage_post_passage_'.$nid , $te);
      storage_put('passage_post_title_'.$nid , $ti);
      storage_put('passage_post_script_'.$nid , $fs);
      storage_put('passage_post_path_'.$nid , $path);
      storage_put('passage_post_root_'.$nid , $rid);
      storage_put('passage_post_uid_'.$nid , $fuid);
      storage_put('passage_post_user_'.$id.'_'.$fuid , '1');
      storage_put('passage_post_hide_'.$nid , '0');
      storage_put('passage_post_lock_'.$nid , (($lo=='1') ? '1' : '0'));
      storage_put('passage_post_notation_'.$nid , ' ');
      
      dbCount_incr('passage_post_childNum_'.$pid , -1);
      
      $resid=$nid;
      return "1:{$resid}";
    }
    public function send($title,$text,$script,$pid=0,$lo='0'){
      global $DB;
      global $USER;
      if(!($USER->checkSend()))return -3;
      if(strlen($title)>512)return -4;
      if(strlen($title)<5)  return -5;
      if(strlen($text) <5)  return -5;
      
      if(@$_SESSION["lr_uinfo"]['send']!=1)return -2;
      
      global $HOOK_CONF;
      if(isset($HOOK_CONF['passage']['send']))
        $HOOK_CONF['passage']['send']($title,$text,$script,$pid,$lo);
      
      $ti   =titleFilter  ($title);
      $te   =passageFilter($text );
      $id   =intval($pid);
      $tm   =time();
      $unm  =$_SESSION["lr_uname"];
      $fs   =$script;
      $fuid =intval($_SESSION["lr_uid"]);
      $shing=$this->getShowing($text);
      $path ='';
      $nid  =dbCount_getId('passage_post_id');
      $rid   =dbCount_getId('passage_root_id');
      
      $index_str=json_encode(
          array(
              'title'=>$ti,
              'showing'=>$shing,
              'time'=>$tm,
              'editor'=>$unm
          )
      );
      
      storage_put('passage_root_'.$rid , $index_str);
      storage_put('passage_root_uid_'.$rid , $fuid);
      storage_put('passage_root_id_'.$rid , $nid);
      
      dbDlist_push('passage_post_root_list' , $rid);
      dbDlist_push('passage_post_root_children_'.$rid , $nid);
      dbDlist_push('passage_user_posts_'.$fuid , $nid);
      
      $h=($lo=='1') ? '1' : '0';
      
      storage_put('passage_post_'.$nid , json_encode(
          array(
              'title'=>$ti,
              'editor'=>$unm
          )
      ));
      
      storage_put('passage_post_parent_'.$nid , '0');
      storage_put('passage_post_passage_'.$nid , $te);
      storage_put('passage_post_title_'.$nid , $ti);
      storage_put('passage_post_script_'.$nid , $fs);
      storage_put('passage_post_path_'.$nid , $path);
      storage_put('passage_post_root_'.$nid , $rid);
      storage_put('passage_post_uid_'.$nid , $fuid);
      storage_put('passage_post_hide_'.$nid , '0');
      storage_put('passage_post_lock_'.$nid , $h);
      storage_put('passage_post_notation_'.$nid , ' ');
      
      $resid=$nid;
      $USER->setLastPost($resid,$title);
      return "1:{$resid}";
    }
    public function hide($id,$v){
      global $DB;
      global $HOOK_CONF;
      if(isset($HOOK_CONF['passage']['hide']))
          $HOOK_CONF['passage']['hide']($id,$v);
      
      $uid=intval(storage_put('passage_post_uid_'.intval($id)));
      if($uid==0)
          return false;
      if(!($_SESSION["lr_uinfo"]['admin'] || intval($_SESSION["lr_uid"])==$uid))
          return false;
      
      storage_put('passage_post_hide_'.intval($id) , ($v==1 ? 1 : 0));
      
      return true;
    }
    public function lock($id,$v){
      global $DB;
      
      $uid=intval(storage_put('passage_post_uid_'.intval($id)));
      if($uid==0)
          return false;
      if(!($_SESSION["lr_uinfo"]['admin'] || intval($_SESSION["lr_uid"])==$uid))
          return false;
      
      storage_put('passage_post_lock_'.intval($id) , ($v==1 ? 1 : 0));
      
      return true;
    }
    public function score($id,$m){
      
      if($_SESSION["lr_uinfo"]==null)return false;
      
      $fid=intval($id);
      $uid=intval($_SESSION["lr_uid"]);
      
      locker_lock('passage_post_score_'.$fid);
      
      $havesco=(intval(storage_read('passage_post_score_'.$fid.'_'.$uid))==1);
      
      if($m){
          if(storage_read('passage_post_'.$fid)){
              if(!$havesco){
                  storage_put('passage_post_score_'.$fid.'_'.$uid , '1');
                  dbCount_incr('passage_post_score_'.$fid , 1);
              }
          }
      }else{
          if($havesco){
              storage_put('passage_post_score_'.$fid.'_'.$uid , '0');
              dbCount_incr('passage_post_score_'.$fid , -1);
          }
      }
      
      locker_unlock('passage_post_score_'.$fid);
      
      return true;
    }
    public function watch($id,$m){
      
      if($_SESSION["lr_uinfo"]==null)return false;
      
      $fid=intval($id);
      $uid=intval($_SESSION["lr_uid"]);
      
      locker_lock('passage_post_watch_'.$fid);
      locker_lock('passage_post_watch_user_'.$uid);
      
      $havew=(intval(storage_read('passage_post_watch_'.$fid.'_'.$uid))==1);
      
      if($m){
          if(storage_read('passage_post_'.$fid)){
              if(!$havew){
                  storage_put('passage_post_watch_'.$fid.'_'.$uid , '1');
                  dbCount_incr('passage_post_watch_'.$fid , 1);
                  dbDlist_push('passage_post_watch_'.$uid , $fid);
              }
          }
      }else{
          if($havew){
              storage_put('passage_post_watch_'.$fid.'_'.$uid , '0');
              dbCount_incr('passage_post_watch_'.$fid , -1);
              dbDlist_remove('passage_post_watch_'.$uid , $fid);
          }
      }
      
      locker_unlock('passage_post_watch_user_'.$uid);
      locker_unlock('passage_post_watch_'.$fid);
      
      return true;
    }
    public function getPassage($id){
      $fid=intval($id);
      if($fid==0)
          return null;
      if(!storage_read('passage_post_'.$fid))
          return null;
      
      $res=json_decode(storage_read('passage_post_'.$fid) , true);
      $res['unmae']=$res['editor'];
      $res['id']=$fid;
      $res['uid']=storage_read('passage_post_uid_'.$fid);
      $res['pid']=storage_read('passage_post_parent_'.$fid);
      $res['rid']=storage_read('passage_post_root_'.$fid);
      $res['title']=storage_read('passage_post_title_'.$fid);
      $res['cont']=storage_read('passage_post_passage_'.$fid);
      $res['script']=storage_read('passage_post_script_'.$fid);
      $res['path']=storage_read('passage_post_path_'.$fid);
      $res['score']=dbCount_get('passage_post_score_'.$fid);
      $res['hide']=intval(storage_read('passage_post_hide_'.$fid));
      $res['lock']=intval(storage_read('passage_post_lock_'.$fid));
      
      global $HOOK_CONF;
      
      if(isset($HOOK_CONF['passage']['read']))
        $HOOK_CONF['passage']['read']($id,$res);
      
      return $res;
    }
    public function getStatus($id){
      $fid=intval($id);
      $uid=intval($_SESSION["lr_uid"]);
      
      $r=intval(storage_read('passage_post_watch_'.$fid.'_'.$uid));
      if($r==1)
        $res['watch']=1;
      else
        $res['watch']=0;
      
      $r=intval(storage_read('passage_post_score_'.$fid.'_'.$uid));
      if($r==1)
        $res['score']=1;
      else
        $res['score']=0;
        
      return $res;
    }
    public function getAll($max=0x7fffffff){
      $m=intval($max);
      $d=new dbDlist('passage_post_root_list');
      if($m==intval(0x7fffffff))
          $d->seekRoot();
      else
          $d->seek($m);
      
      $res=array();
      for($i=0;$i<21;$i++){
          if($d->id){
              if($cont=storage_read('passage_root_'.intval($d->id))){
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
    public function getChild($id,$max=0x7fffffff){
      $m=intval($max);
      $d=new dbDlist('passage_post_children_list_'.$intval($id));
      if($m==intval(0x7fffffff))
          $d->seekRoot();
      else
          $d->seek($m);
      
      $res=array();
      for($i=0;$i<60;$i++){
          if($d->id){
              if($cont=storage_read('passage_post_'.intval($d->id))){
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
    public function getUserPassage($id,$max=0x7fffffff){
      $m=intval($max);
      $d=new dbDlist('passage_user_posts_'.$intval($id));
      if($m==intval(0x7fffffff))
          $d->seekRoot();
      else
          $d->seek($m);
      
      $res=array();
      for($i=0;$i<60;$i++){
          if($d->id){
              if($cont=storage_read('passage_post_'.intval($d->id))){
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
    public function getWatch($uid,$max=0x7fffffff){
      $m=intval($max);
      $d=new dbDlist('passage_post_watch_'.$intval($uid));
      if($m==intval(0x7fffffff))
          $d->seekRoot();
      else
          $d->seek($m);
      
      $res=array();
      for($i=0;$i<60;$i++){
          if($d->id){
              if($cont=storage_read('passage_post_'.intval($d->id))){
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
    public function getScript($id) {
      return storage_read('passage_post_script_'.intval($id));
    }
  }
