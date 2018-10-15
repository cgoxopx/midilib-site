<?php
  defined("HERE") or die("db error");
  require_once(HERE.'/class/cache.php');
  $DB=new Memcache;
  if(!$DB->connect($CONFIG['db']['host'],$CONFIG['db']['port']))
      die('connect db fail');
  function storage_read($name){
      return $DB->get($CONFIG['db']['pre'].'data_'.$name);
  }
  function storage_put($name,$val,$tm=0){
      $DB->set(
          $CONFIG['db']['pre'].'data_'.$name , 
          $val,0,$tm
      );
  }
  function storage_del($name){
      $DB->delete($CONFIG['db']['pre'].'data_'.$name);
  }
  function locker_lock_noblock($name){
      return $DB->add(
          $CONFIG['db']['pre'].'lock_'.$name , '1' , 0 , 20
      );
  }
  function locker_unlock_noblock($name){
      $DB->delete($CONFIG['db']['pre'].'lock_'.$name);
  }
  function locker_lock($name){
      $btime=microtime(true);
      while(1){
          if(!locker_lock_noblock($name)()){
              if((microtime(true)-$btime)>20)
                  die('database time out');
          }else{
              return;
          }
      }
  }
  function locker_unlock($name){
      locker_unlock_noblock($name);
  }
