<?php
$list=array(array('uid'=>67810,'pid'=>294,'score'=>2,'mobile'=>18853675721),array('uid'=>294,'pid'=>0,'score'=>11,'mobile'=>15261911853),array('uid'=>44444,'pid'=>67810,'score'=>444,'mobile'=>18853675722),array('uid'=>44445,'pid'=>67810,'score'=>4445,'mobile'=>18853675723),array('uid'=>555555,'pid'=>44444,'score'=>444555,'mobile'=>18853675723),array('uid'=>5555556,'pid'=>44443,'score'=>4445565,'mobile'=>18853675793));
class User{
	protected $list = [];
	protected $bids = []; //相同pid的uid集合，数组
	protected $users = []; 
	//制造用户映射表 
	public function __construct($list = null){
		$this->list = $list;
	}
	public function execute(){
	    foreach($this->users as $v){
		$teamCount = 0;
	    	foreach($v->drn as $k=>$v1){
		    //var_dump($v->drn);exit;
		    $this->statisticsTeamCount($v1,$teamCount);
		}
		$v->tm_cnt = $teamCount;
		var_dump($v->tm_cnt);  //递归计算总团队人数 done!
	    }
	}
	protected function statisticsTeamCount($u,&$teamCount){
	    $teamCount += 1;
	    foreach($u->drn as $v){
		$this->statisticsTeamCount($v,$teamCount);
	    }
	    return ;
	}
	public function reindexUser(){
	    if($this->list) {
		foreach($this->list as $v) {
		    echo "<pre>";
		    $this->bids[$v['pid']][] = $v['uid']; 
		    $this->users[$v['uid']] = new userDetail(
			$v['uid'],
			$v['pid'],
			$v['score'],
			$v['mobile']
		    );
		    //var_dump($this->users);break;
		    //var_dump($this->bids); 
		}
		//var_dump($this->users);
		foreach($this->users as $v){
			//var_dump($v->pid);break;
		    if($v->uid > 0 and $v->pid > 0 and isset($this->users[$v->pid])){
			$drn = $this->users[$v->pid]->drn;
			$drn[$v->uid] = $v;
			$this->users[$v->pid]->drn = $drn;
			//↑done
			if(isset($this->bids[$v->uid])) {
			    //echo $v->uid;
			    $trn = $this->users[$v->pid]->trn;
			    //var_dump($this->bids[$v->pid]);exit;
			    foreach($this->bids[$v->pid] as $v1){
				$trn[$v1] = $this->users[$v1];
			    }
			    $this->users[$v->pid]->trn = $trn;
			}
			
		    }
		}
		return $this;
	    }
	}	
}
class userDetail{
	protected $trn = [];
	protected $drn = [];
	protected $level = 0;
	protected $uid = 0;
	protected $pid = 0;
	protected $score = 0;
	protected $mobile = 0;
	protected $tm_cnt = 0;
	
	public function __construct($uid,$pid,$score,$mobile){
	    $this->uid = $uid;
	    $this->pid = $pid;
	    $this->score = $score;	
	    $this->mobile = $mobile;	
	}
	public function __get($name){
	    if (isset($this->{$name})){
		return $this->{$name};
	    }
	}
	public function __set($name,$value){
	    if (isset($this->{$name})){
		$this->{$name} = $value;
	    }
	}
}
$user = new User($list);
$user->reindexUser();
$user->execute();
