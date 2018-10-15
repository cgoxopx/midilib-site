<?php
  defined("HERE") or die("cache error");
  $MEMC;
  class Cache{
    public $result;
    public $cacheTime=300;
    public $useMemc=true;
    public function __construct(){
      if($this->useMemc){
        global $CONFIG;
        global $MEMC;
        if($MEMC!=null){
          return;
        }else{
          $MEMC = new Memcache;
          $res=$MEMC->connect(
            $CONFIG['cache']['host'],
            $CONFIG['cache']['port']
          );
          //echo 'connect memc';
          if(!$res)
            $this->useMemc=false;
        }
      }
    }
    public function getCache($key){
      global $MEMC;
      global $CONFIG;
      if(!$this->useMemc){
        //read file
        $path=HERE.'/cache/'.md5($key).'.data_cache.php';
        $df=file_get_contents($path);
        if($df==null)
          return false;
        //decode
        $half=explode('{"cache"}',$df);
        if(!is_array($half))
          return false;
        $ed=json_decode($half[1],true);
        if(!is_array($ed))
          return false;
        if(abs(time()-$ed['time'])>$this->cacheTime)
          return false;
        if($key!=$ed['key'])
          return false;
        $this->result=$ed['data'];
        return true;
      }else{
        $res=$MEMC->get($CONFIG['cache']['pre'].$key);
        //print_r($res);
        if($res==false)
          return false;
        else{
          $this->result=json_decode($res,true);
          return true;
        }
      }
    }
    public function writeCache($key,$data){
      global $MEMC;
      global $CONFIG;
      if(!$this->useMemc){
        $ed=array(
          'time'=>time(),
          'key' =>$key,
          'data'=>$data
        );
        $path=HERE.'/cache/'.md5($key).'.data_cache.php';
        file_put_contents($path,
            array(
            '<?php die();?>{"cache"}',
            json_encode($ed)
          )
        );
      }else{
        $status=$MEMC->set($CONFIG['cache']['pre'].$key,json_encode($data),0,$this->cacheTime);
        //print_r($status);
      }
    }
    public function runFunc($key,$func,$arg=null){
      if(!$func)return false;
      if(!$this->getCache($key)){
        $ret=$func($arg);
        $this->writeCache($key,$ret);
        return $ret;
      }else{
        return $this->result;
      }
    }
    public function del($key){
      global $MEMC;
      global $CONFIG;
      if(!$this->useMemc){
        //read file
        $path=HERE.'/cache/'.md5($key).'.data_cache.php';
        unlink($path);
      }else{
        $MEMC->delete($CONFIG['cache']['pre'].$key);
      }
    }
  }
  //$CACHE=new Cache();
  function getCache($key){
    $c=new Cache();
    $res=$c->getCache($key);
    if($res){
      return $c->result;
    }else
      return false;
  }
  function delCache($key){
    (new Cache())->del($key);
  }
  function setCache($key,$value,$tm=300){
    $c=new Cache();
    $c->catchTime=$tm;
    $c->writeCache($key,$value);
  }