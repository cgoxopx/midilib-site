<?php
  defined("HERE") or die("list error");
  require_once(HERE.'/class/db.php');
  class dbCount{
      public $lockerName;
      public $name;
      public $keyName;
      public function __construct($name){
          $this->name=$name;
          $this->lockerName='count_locker_'.$name;
          $this->keyName='count_'.$name;
      }
      public function get(){
          $res=storage_read($this->keyName);
          if(!$res)
              return 0;
          else
              return intval($res);
      }
      public function update($i){
          storage_put($this->keyName,intval($i));
      }
      public function incr($n){
          $this->lock();
          $l=$this->get();
          $this->update($l+$n);
          $this->unlock();
          return $l;
      }
      public function lock(){
          locker_lock($this->lockerName);
      }
      public function unlock(){
          locker_unlock($this->lockerName);
      }
  }
  function dbCount_getId($name){
      $c=new dbCount($name);
      while(1){
        $nid=$c->incr(1);
        if($nid!=0)
          return $nid;
      }
  }
  function dbCount_get($name){
      $c=new dbCount($name);
      return $c->get();
  }
  function dbCount_incr($name,$n){
      $c=new dbCount($name);
      return $c->incr(intval($n));
  }
  function dbCount_del($name){
      $lockerName='count_locker_'.$name;
      $keyName='count_'.$name;
      locker_lock($lockerName);
      storage_del($keyName);
      locker_unlock($lockerName);
  }
