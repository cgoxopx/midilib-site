<?php
  defined("HERE") or die("list error");
  require_once(HERE.'/class/db.php');
  class dbDlist{
      public $name;//name for list
      public $lockerName;
      public $last=null;
      public $next=null;
      public $id=null;
      public function __construct($name){
          $this->name=$name;
          $this->lockerName='dlist_locker_'.$name;
      }
      public function exist($id){
          if($this->getBeforId($id))return true;
          if($this->getAfterId($id))return true;
          return false;
      }
      public function lock(){
          locker_lock($this->lockerName);
      }
      public function reset(){
          $this->id=null;
          $this->last=null;
          $this->next=null;
      }
      public function unlock(){
          locker_unlock($this->lockerName);
      }
      public function getKeyBefor($id){
          return ('dlist_'.($this->name).'_before_val_'.intval($id));
      }
      public function getKeyAfter($id){
          return ('dlist_'.($this->name).'_after_val_'.intval($id));
      }
      public function getBeforId($id){
          $res=storage_read($this->getKeyBefor($id));
          return ($res ? intval($res) : null);
      }
      public function getAfterId($id){
          $res=storage_read($this->getKeyAfter($id));
          return ($res ? intval($res) : null);
      }
      public function setBeforId($id,$n){
          storage_put($this->getKeyBefor($id),intval($n));
      }
      public function setAfterId($id,$n){
          storage_put($this->getKeyAfter($id),intval($n));
      }
      public function getRootId(){
          $res=storage_read('dlist_'.($this->name).'_root');
          return ($res ? intval($res) : null);
      }
      public function setRootId($id){
          storage_put('dlist_'.($this->name).'_root',intval($id));
      }
      public function seek($id){
          $this->id=intval($id);
          $this->last=$this->getBeforId($id);
          $this->next=$this->getAfterId($id);
      }
      public function seekRoot(){
          $rid=$this->getRootId();
          if(!$rid)
              return false;
          $this->seek($rid);
          return true;
      }
      public function seekLast(){
          if($this->last==null)
              return false;
          $this->seek($this->last);
          return true;
      }
      public function seekNext(){
          if($this->next==null)
              return false;
          $this->seek($this->next);
          return true;
      }
      public function erase(){
          if($this->id==null)//doesn't seek any object
              return false;
          if($this->last){ //has last ptr means this object isn't in the begin of the list
              if($this->next){ //has next ptr means isn't in the end of the list
                  $this->setAfterId($this->last,$this->next);
              }else{
                  //in the end,set last object as end
                  storage_del($this->getKeyAfter($this->last));
              }
          }else{
              //doesn't have last ptr means the object is in the begining of the list
              //set root ptr
              if($this->next)
                  $this->setRootId($this->next);
              else
                  storage_del('dlist_'.($this->name).'_root');
          }
          if($this->next){
              if($this->last){
                  $this->setBeforId($this->next,$this->last);
              }else{
                  storage_del($this->getKeyBefor($this->next));
              }
          }
          //the list doesn't have ending pointer so it needn't to set ending ptr
          storage_del($this->getKeyBefor($this->id));
          storage_del($this->getKeyAfter($this->id));
          $this->reset();
          return true;
      }
      public function erase_safe($id){
          $this->lock();
          $this->seek($id);
          $res=$this->erase();
          $this->unlock();
          return $res;
      }
      public function pushFront($id){
          if($this->exist($id))
              return false;
          $rid=$this->getRootId();
          if($rid){
              $this->setAfterId($id,$rid);
              $this->setBeforId($rid,$id);
              $this->setRootId($id);
          }else{
              $this->setRootId($id);
          }
          return true;
      }
      public function pushFront_safe($id){
          $this->lock();
          $res=$this->pushFront($id);
          $this->unlock();
          return $res;
      }
      public function clear($cb=null,$arg=null){
          $this->seekRoot();
          while(1){
              if($this->id){
                  storage_del($this->getKeyBefor($this->id));
                  storage_del($this->getKeyAfter($this->id));
                  if($cb){
                      $cb($this->id,$arg);
                  }
              }else
                  break;
              if(!$this->seekNext())
                  break;
          }
          storage_del('dlist_'.($this->name).'_root');
      }
      public function clear_safe($cb=null,$arg=null){
          $this->lock();
          $res=$this->clear($cb=null,$arg=null);
          $this->unlock();
          return $res;
      }
  }
  function dbDlist_push($name,$id){
      $d=new dbDlist($name);
      return $d->pushFront_safe($id);
  }
  function dbDlist_remove($name,$id){
      $d=new dbDlist($name);
      return $d->erase_safe($id);
  }
  function dbDlist_clear($name,$cb=null,$arg=null){
      $d=new dbDlist($name);
      return $d->erase_clear($cb,$arg);
  }
