<?php
/*筛选拼团用户 alex kinhoom*/
namespace app\uapi\controller\v1;

use think\Controller;
use think\Db;
use think\Request;

class PinUser extends Controller { 
    protected $redis;
    public function __construct(){
	// die('2020年9月7日-2020年9月8日系统维护');
	$this->redis = redis();
	$this->redis->select(8);
    }
    //建团 废弃
    public function createGroup1(Request $request){
$a = Db::connect([
    // 数据库类型
    'type'        => 'mysql',
    // 服务器地址
    'hostname'    => '127.0.0.1',
    // 数据库名
    'database'    => 'lianwo_shop',
    // 数据库用户名
    'username'    => 'root',
    // 数据库密码
    'password'    => 'jbLR8mzLY2zG2pNE',
    // 数据库连接端口
    'hostport'    => '3306',
    // 数据库连接参数
]);
	    //$set = $a->query('select * from lianwo_shop.user_level a join test_hy_lw.wo_user b on a.uid=b.uid where a.uid=19');
           //$set = $a->query("select b.pin_weight,a.pid from lianwo_shop.user_level a join test_hy_lw.wo_user b on a.uid=b.uid where a.uid=19 ");
$res3 = Db::query("select count(*) as count from lianwo_shop.user_level a join test_hy_lw.wo_user b on a.uid=b.uid where a.pid=294 and b.pin_weight !=0");
	dump($res3);exit;
	    $pinWallB = Db::table('wo_user')->where(['uid'=>38])->value('pin_wallet_b');
dump((float)$pinWallB);
exit;
	
            $params = $request->post();
                $params['begin_dt'] = $params['begin_dt'];
                $params['end_dt'] = $params['end_dt'];
            $params['create_time'] = $params['update_time'] = time();
            $data = Db::name('pin_periods')->insert($params);
            $pid = Db::name('pin_periods')->getLastInsID();  //alex kinhoom 
$this->sendMessToWorker($pid,$params['begin_dt'],$params['end_dt']);
	exit;
	$arr = [1,2,3];
		    $this->redis->sAdd("pintuanfilter:2221",...$arr);
exit;
	if ($request->isPost()) {
	   $param = $request->post();
	   
	   echo  Db::execute("insert into wo_pin_tuan(status,create_time,update_time) values({$param['pid']},1,1)");
exit;

	    $param = $request->post();

	    //商品id
	    $gid = $param['gid'];
	    //团人数
	    $limitNum = $param['limit_num'];
	    //设置中奖人数
	    $winnerNum = $param['winner_num'];
	    //拼团总期数
	    $periodsAll = $param['periods_all'];
	    //单个期数持续时间
	    $periodsDuration = $param['periods_duration'];
	    

	    $now = time();
	    //拼团开始时间
	    $beginAt = $param['begin_dt'];
	    $endAt = (int)$beginAt + (int)$periodsDuration;
	    //插入团表
	    $sql = "insert into wo_pin_tuan(status,limit_num,winner_num,create_time) values(1,{$limitNum},{$winnerNum},{$now})";
	    Db::startTrans();
	    $res = Db::execute($sql);
	    if(!$res) { 
		Db::rollback();
	    	return api(400,'建团失败');
	    }
	    $tid = Db::name('pin_tuan')->getLastInsID();
	    //插入团推进表
	    $sql1 = "insert into wo_pin_periods(tid,gid,periods_all,periods_now,periods_duration,begin_dt,end_dt,create_time) values({$tid},{$gid},{$periodsAll},1,{$periodsDuration},{$beginAt},{$endAt},{$now})";
	   $res1 =  Db::execute($sql1);
	   if(!$res1) {
		Db::rollback();
		return api(400,'建团失败');
	   }
	   //推送数据 status:1 创建团
	   if($resf = $this->sendMessToWorker($tid,$beginAt,$endAt,$periodsDuration,1)) {
	       Db::commit();
	       return api(200,$resf); 	
	   } else {
		Db::rollback();
		return api(400,'建团失败');
	   } 
	     

	}

    }
    private function sendMessToWorker(int $pid,int $beginAt,int $endAt) {
        $client = stream_socket_client('tcp://127.0.0.1:5678', $errno, $errmsg, 1);
        if(!$client) return false;
        $data = ['pid'=>$pid,'begin_dt'=>$beginAt,'end_dt'=>$endAt];
        if(FALSE === fwrite($client, json_encode($data)."\n")) {
            return false;
        }
        return fread($client,11);
    }

    
    //申请参团
    //add pin user to buffer(redis)
    
    public function preAddUser(Request $request) {
	if ($request->isPost()) {
	    $param = $request->param;
	    // $param['id'] = 64;
	    $uid = $param['uid']; //用户id
	    $gid = Db::name('pin_periods')->where('id',$param['id'])->value('gid');
	    $price = Db::name('pin_product')->where('id',$gid)->value('price');
	    //查看用户本金够不
	    /* 待开启 */
	    $pinWallB = Db::table('wo_user')->where(['uid'=>$uid])->value('pin_wallet_b');
	    if((float)$pinWallB < ($price * 0.9)) return api(400,'拼团本金不足,预约失败');
	    $isAuth = Db::name('integral_member')->where(['uid'=>$uid])->value('is_auth');
	    if($isAuth != 1) return api(400,'你未认证,参团失败'); 
	    /* 待开启 end */
	    
	    //查看用户豆子够不够 待定
	    /* 待开启 /
	    $uInte = Db::table('wo_integral_member')->where(['uid'=>$uid])->value('integral');
	    if (!$uInte) return api(400,'积分异常,提交申请失败');
	    $uInte < X  return api(400,'积分不够,提交申请失败');  
	    */
	
	
	    
	    
	    $pid = $param['id']; //期id
	    //查看本期是否加锁
	    if($this->redis->get("tuanlock:{$pid}") == 1){
		return api(400,'本期已不允许加人');
	    }
	    $nowP = time();
	    //查看要参的团是否逾期
	    $resPeriod = Db::table('wo_pin_periods')->where(['id'=>$pid])->field('begin_dt,end_dt,t_num')->find();
	    //本团限制人数
	    $limitNum = $resPeriod['t_num'];
	    if($resPeriod['begin_dt'] > $nowP or $resPeriod['end_dt'] < $nowP) return api(400,'申请的团不存在,或未到开放时间');
	    //根据uid 查出权重
	    $pinWeight = Db::table('wo_user')->where(['uid'=>$uid])->value('pin_weight');

	    //一个uid只能参加一个团
	    if($this->redis->sismember('tuanset',$uid)) {
		return api(400,'申请提交失败,你已参加本团或其他团');
	    }
	    $this->redis->sadd('tuanset',$uid);
	
	    //[tid=>[[uid1,pinweight1],[uid2,pinweight2]]] 传入redis 集合
	    $res = $this->redis->sAdd("pintuan:{$pid}","{$uid}:{$pinWeight}");
	    if($res) {
		//够的话，integral 扣  ，+ integral3 里面。 待定
		/* 待开启
	    	Db::startTrans();
		$res1 = Db::table('wo_integral_member')->where(['uid'=>$uid])->setDec('integral',X);
	 	$res2 = Db::table('wo_integral_member')->where(['uid'=>$uid])->setInc('integral3',X);
		if(!$res1 or !$res2) {
		    Db::rollback();
		    $this->redis->srem('tuanset',$uid);
		    return api(400,'申请参团提交失败,积分问题');
		    	    
		}
		
		*/
		//冻结豆子
		/* 待开启 */
		//昨日开始
		$starttime = mktime(0,0,0,date('m'),date('d')-1,date('Y'));
		//昨日结束
		$endtime = mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
		$avgPriceY = $this->avgPrice($starttime,$endtime);
		Db::startTrans();
		if($avgPriceY > 0.5) {
		    $inte = ceil(($price * 10 / 100) / $avgPriceY);
	    	$uInte = Db::table('wo_integral_member')->where(['uid'=>$uid])->value('integral');
	    	if (!$uInte) {
				$this->redis->srem('tuanset',$uid);
				$this->redis->srem("pintuan:{$pid}","{$uid}:{$pinWeight}");
				return api(400,'积分异常,提交申请失败');
		    }
	    	if ($uInte < $inte) {
				$this->redis->srem('tuanset',$uid);
				$this->redis->srem("pintuan:{$pid}","{$uid}:{$pinWeight}");
		        return api(400,'积分不够,提交申请失败');  
		    }
		    $res = Db::execute("update wo_integral_member set integral=integral-{$inte},integral4=integral4+{$inte} where uid = {$uid}");
		    if(!$res) {
				Db::rollback();
				$this->redis->srem('tuanset',$uid);
				$this->redis->srem("pintuan:{$pid}","{$uid}:{$pinWeight}");
				return api(400,'申请参团提交失败');
		    }
		
		} else {
		    $inte = ceil(($price * 10 / 100) / 0.5);
		    
		    // dump($inte);die;
		    $res = Db::execute("update wo_integral_member set integral=integral-{$inte},integral4=integral4+{$inte} where uid = {$uid}");
		    if(!$res) {
				Db::rollback();
				$this->redis->srem('tuanset',$uid);
				$this->redis->srem("pintuan:{$pid}","{$uid}:{$pinWeight}");
				return api(400,'申请参团提交失败');
		    }
		}
		Db::commit();
		/* 待开启end */
		return api(200,'申请参团提交成功');
	    } else {
		$this->redis->srem('tuanset',$uid);
	//	$this->redis->decrby("tuannum:{$pid}",1);
		return api(400,'申请参团提交失败');
	    }

        } else {
	    return api(400,'参数错误');
	}
    }
    //取消预约
    public function cancelPreAdd(Request $request) { 
	if ($request->isPost()) {
            $param = $request->param;
            $uid = $param['uid']; //用户id
            $pid = $param['pid']; //期id

	    if(!$this->redis->sismember('tuanset',$uid)) return api(400,'你没有预约参加任何团');
	    if(!$this->redis->srem('tuanset',$uid)) return api(400,'取消预约失败');
	    //根据uid 查出权重
	    $pinWeight = Db::table('wo_user')->where(['uid'=>$uid])->value('pin_weight');
	    if(!$this->redis->srem("pintuan:{$pid}","{$uid}:{$pinWeight}")) {
		$this->redis->sadd('tuanset',$uid);
		return api(400,'取消预约失败');
	    } else {
		//解冻豆子
		/*待开启 */
		$sql  = "update wo_integral_member a inner join (select uid,integral4 from wo_integral_member where uid = {$uid}) b  on a.uid = b.uid set a.integral = a.integral + b.integral4,a.integral4=0" ;
		$res = Db::execute($sql);
		if(!$res) { 
		    $this->redis->sadd('tuanset',$uid);
		    $this->redis->sadd("pintuan:{$pid}","{$uid}:{$pinWeight}");
		    return api(400,'取消预约失败');
		}
		/*待开启 end */
		
		//$res = Db::execute("update wo_integral_member set integral=integral-{$inte},integral3=integral3+{$inte} where uid = {$uid}");
			
		return api(200,'取消预约成功');
	    }
	    	    
	} else {
	    return api(400,'参数错误');
	}
		

    }
    // filter user of group        end_dt 之前 5min触发
    public function filterUser(Request $request){
	//--if ($request->isPost()) {	
	   
	    //$param = $request->post();
	    $param = $request->get();
	
	    $pid = $param['pid']; //期id
	    //给本期加锁
	    $this->redis->setex("tuanlock:{$pid}",303,1); //303s 后过期
	    //获取pid 对应每个团限制人数
	    $limitNum = Db::table('wo_pin_periods')->where(['id'=>$pid])->value('t_num');
	    //获取tid 对应团期数id
	    //$tuanPid = Db::table('wo_pin_tuan')->where(['id'=>$tid])->value('pid');
	    //获取 buffer 中pid对应的用户集合
	    $tuanMemberProte = $this->redis->sMembers("pintuan:{$pid}");	    
	    $tuanMember = $this->redis->sMembers("pintuan:{$pid}");	    
	    //准备从buffer 中删除的uid
	    $preFilterTuanMem = [];
	    foreach($tuanMember  as $v){
		$preFilterTuanMem[] = explode(':',$v)[0];
	    }

	    //初始化过滤后人员 @array  expl:[0:'1:0',1:'2:0']
	    //计算需要沉淀的人数,先按照团的40%进行沉淀
/*---
	    if(count($tuanMember) >= $limitNum && count($tuanMember) < ($limitNum * (1 + 0.4))){
	    	$realTuanNum = 1;
	    }else if(count($tuanMember) >= ($limitNum * (1 + 0.4))){
	    	$realTuanNum = floor((count($tuanMember) - ($limitNum * 0.4)) / 100);
	    }else {
		$realTuanNum = 1;
	    }
---*/
	    //预约人数-需要沉淀的人数=实际参与筛选的人数
	    // $realNum = count($tuanMember) - $sedimentNum;
	    // //团数=实际参与筛选的人数 / 每个团的人数
	    // $realTuanNum = floor($realNum / $limitNum);
	    
	    //期数限制人数 = 团数 * 每个团人数
	    $realTuanNum =1;
	    $realLimitNum = $realTuanNum * $limitNum;
	    $realLimitNum = 10;
	    	    
	    $filterMem = [];
            //团员等于团限制人数
	    //if(count($tuanMember) == $realLimitNum) {  //团员==期数限制人数
	    if(1 == 1) {  //团员==期数限制人数
		$filterMem = $tuanMember;		
	    } else if(count($tuanMember) < $realLimitNum) { //团员<期数限制人数
	
exit;
		foreach($tuanMember as $tuan) {
		    $uid = explode(':',$tuan)[0];
		    $this->redis->srem("tuanset",$uid);
		    $sqlxxx  = "update wo_integral_member a inner join (select uid,integral4 from wo_integral_member where uid = {$uid}) b  on a.uid = b.uid set a.integral = a.integral + b.integral4,a.integral4=0" ;
		    Db::execute($sqlxxx);
	        }



		echo 601;
		exit;
		//return api(400,'申请人数太少,不成团');
	    } else if(count($tuanMember) > $realLimitNum) { //团员>期数限制人数
exit;
	    	//递归筛选用户 example:   1:0 2:0 
	        //$weight = 0;
	        $weight = -98;
		$this->recurFilterUser($tuanMember,$filterMem,$weight,$realLimitNum);
            }
	    //团映射 [[],[],[]]
            $tuanHash = [];
	    //给每个团分配人员,时间复杂度为内层循环$filterMem数组将其遍历一次所耗费的时间，评估:较好
	    for($i = 0;$i < $realTuanNum;$i++) {
		for($j = $i; $j < count($filterMem); $j = $j + $realTuanNum) {
		    $tuanHash[$i][] = $filterMem[$j];
		}
	    }
	    //change $filterMem = [0:'1:0',1:'2:0'] to [1]
	    //处理过滤后人员 ，批量更新weight并批插wo_pin_join_member
	    $i = 0;
	    $j = 0;
	    $batchSqlT = '';
	    $batchSqlW = '';
	    $batchSqlJ = '';
	    $pintuanFilterArr = [];
	    $pintuanFilterArr1 = []; // '1 2 3 '
	    $pintuanFilterArr2 = []; // '1 2 3 '
	    $createTime = time();
	    $batchSqlW .= 'UPDATE `wo_user` a JOIN (';
	    $batchSqlJ .= 'INSERT INTO `wo_pin_join_member` (uid,tid,pid,status,create_time) values ';
	    $batchSqlT .= 'insert into `wo_pin_tuan`(pid,create_time) values ';
	    $failUser = [];
	    //先插wo_pin_tuan表
	    
	    foreach($tuanHash as $tuan) {
		if($i == 0) {
		    $batchSqlT .= " ({$pid},{$createTime})";
		} else {
		    $batchSqlT .= ",({$pid},{$createTime})";
		}
		$i++;
	    }
	    $resT = Db::execute($batchSqlT);
	    if($resT) {
		$resTId = Db::table('wo_pin_tuan')->where(['pid'=>$pid])->column('id');
		if(!$resTId) {
		    echo 611;
		    exit;
		}	
		$k = 0;
		foreach($tuanHash as $tuan) {
		    $m = 0;
		    foreach($tuan as $v) {
			if($m == 0 and $k == 0){
		    	    $batchSqlW .= "select ".explode(':',$v)[0]." as uid, '".(explode(':',$v)[1] + 1)."' as pin_weight ";
		            $batchSqlJ .= " ( ".explode(':',$v)[0].",{$resTId[$k]},{$pid},0,{$createTime} )";
			} else {
		    	    $batchSqlW .= "UNION select ".explode(':',$v)[0]." as uid, '".(explode(':',$v)[1] + 1)."' as pin_weight "; 
		    	    $batchSqlJ .= ",( ".explode(':',$v)[0].",{$resTId[$k]},{$pid},0,{$createTime} )";
			}
			$pintuanFilterArr[] = explode(':',$v)[0];
			//↓['id:tid','id:tid']
			$pintuanFilterArr1[] = explode(':',$v)[0].":{$resTId[$k]}";
			if(explode(':',$v)[1] + 1 == 0){
		            $pintuanFilterArr2[] = explode(':',$v)[0].":".(explode(':',$v)[1] + 2);
			} else {
			    $pintuanFilterArr2[] = explode(':',$v)[0].":".(explode(':',$v)[1] + 1);
			}
			$m++;
		    } 
		    $k++;
		}
	    } else {
		echo 622;
		exit;
	    }
	
	    $batchSqlW .= ') b USING(uid) SET a.pin_weight=b.pin_weight ';
	
	    Db::startTrans();
	    $resW = Db::execute($batchSqlW);    
	    $resJ = Db::execute($batchSqlJ);
	    if($resW and $resJ) {
	        //删除buffer中pid对应的集合,tuanset中的用户
	    	//-if($this->redis->del("pintuan:{$pid}")){
		    //$this->redis->del("tuannum:{$pid}");
		 //-   $this->redis->srem('tuanset',...$preFilterTuanMem);

		    $this->redis->sadd("pintuanfilter1:{$pid}",...$pintuanFilterArr2);
		    //筛出的pid入集合
		    if($this->redis->sAdd("pintuanfilter:{$pid}",...$pintuanFilterArr1)) {
	    		foreach($preFilterTuanMem as $perUser){
			    if(!in_array($perUser,$pintuanFilterArr)){
				$failUser[] = $perUser;
			    }
			}
			/*待开启 未入团用户解冻 */
			if(count($failUser) > 0) {
			$batchSqlFail = '';
			$batchSqlFail .= "update wo_integral_member a inner join ( select uid,integral4 from wo_integral_member where uid in(";
			
			$i1 = 0;
			foreach($failUser as $failu){
			    if($i1 ==0){
				$batchSqlFail .= "{$failu}";
			    } else {
				$batchSqlFail .= ",{$failu}";
			    }
			    $i1++;
			}
			$batchSqlFail .= ")) b on a.uid=b.uid set a.integral = a.integral+b.integral4,a.integral4 = 0";
			$resF = Db::execute($batchSqlFail);
			if($resF === false) {

			    Db::rollback();
	//-		    $this->redis->sadd('tuanset',...$preFilterTuanMem);
	//-		    $this->redis->sadd("pintuan:{$pid}",...$tuanMemberProte);
			    $this->redis->del("pintuanfilter:{$pid}");
			    echo 633;
			    exit;
			}
			}
			/* 待开启 end*/
			Db::commit();
			//return api(200,'参团成功');
			
			echo "200:{$pid}";
			exit;
		    } else {
		//-	$this->redis->sadd('tuanset',...$preFilterTuanMem);
		//-	$this->redis->sadd("pintuan:{$pid}",...$tuanMemberProte);
		    	Db::rollback();
		        //return api(400,'参团失败');
			echo 644;
			exit;
		    }
		//-} else {
		//-    Db::rollback();
		//-    echo 600;
		//-    exit;
		    //return api(400,'参团失败');
		//-}
	    } else {
		Db::rollback();
		echo 655;
		exit;
		//return api(400,'参团失败');
	    }
	    
//--	} else {
//--	    echo 400;
//--	    exit;
	    //return api(400,'参数错误');
//--	}

    }
    //handle winner 中奖逻辑  end_dt 触发
    public function winner(Request $request){
//--	if ($request->isPost()) {	
	    //$param = $request->post();		
	    $param = $request->get();
	    $pid = $param['pid'];
	//    $this->deepReward($pid);
	//    $this->guanjiaReward($pid);
	    //获取本团对应中奖人数
//exit;
	    //$winnerNum = Db::table('wo_pin_tuan')->where(['id'=>$tid])->value('winner_num');
	    //获取中奖奖励reward
	    $reward = Db::table('wo_pin_periods')->where(['id'=>$pid])->value('reward');
	    if(!$reward) {
			echo 600;
			exit;
	    }
	    $tid = Db::name('pin_tuan')->where('pid',$pid)->column('id');
	    $uids = [];
	    foreach ($tid as $k=>$v) {
	    	$uid = Db::name('pin_join_member')->alias('a')
	    		->join('wo_user b','a.uid = b.uid','left')
	    		->order('b.pin_weight desc')
			->where('a.tid',$v)
	    		->limit(2)
	    		->column('a.uid');
	    	$uids = array_merge($uids,$uid);
	    	
	    }
	    //获奖人数设定为2人
	    $winnerNum = 2;
	    //if(!$winnerNum) return api(400,'获取中奖人数失败');
	    $midArr = $this->redis->sMembers("pintuanfilter:{$pid}");

	    $midArr1 = $this->redis->sMembers("pintuanfilter1:{$pid}");
		
	    $kanArr1 = [];
	    //[id:weight,id:weight,id:weight]
	    foreach($midArr1 as $v){
		$kanArr1[explode(":",$v)[0]] = explode(":",$v)[1];
	    }



	    //↓[1:[],2:[]]
	    $resultArr = [];
	    for($i = 0; $i < count($midArr); $i++) {
		$team = explode(':',$midArr[$i])[1];
		$resultArr[$team][] = explode(':',$midArr[$i])[0];
	    }
	    //$winnerHArr = [];
	    $loserArr = [];
	    //[中奖者,中奖者]
	    $winnerArr = $uids;
	    
	 //   $rfFunc = function ($v1) use(&$v) {
  //              return $v[$v1];
  //          };
	 //   foreach($resultArr as $v) {
		// $kanArr1[$v];
		// 	$random_keys = array_rand($v,2);
		// 	$winnerHArr[] = array_map($rfFunc,$random_keys);		
	 //   }
	 //   foreach($winnerHArr as $winners) {
		// foreach($winners as $winner) {
		//     $winnerArr[] = $winner;
		// }
	 //   }
	    //选出loser
	    foreach($resultArr as $uids) {
		foreach($uids as $uid) {
		    if(!in_array($uid,$winnerArr)) $loserArr[] = $uid;   	
		}
	    }
	    //处理loser	 loser分成
	    //update wo_integral_member a INNER join  (select uid,integral3 from wo_integral_member where uid in(554,555,556)) b on a.uid=b.uid set a.integral = a.integral+b.integral3,a.integral3 = 0 ;
	    Db::startTrans();
/*    待开启 */
	    $timeNow = time();
	    $batchSqlLoser1 = ''; 
	    $batchSqlLoser1 .= "update wo_integral_member a inner join ( select uid,integral4 from wo_integral_member where uid in("; 
	    $batchSqlLoser2 = '';
	    $batchSqlLoser2 .= 'insert into wo_pin_price_detail(uid,quota,type,detail,create_time) values '; 
	    $batchSqlLoser3 = '';
	    $batchSqlLoser3 = "update wo_user set pin_wallet_s = pin_wallet_s+{$reward} where uid in(";
	    $batchSqlLoser4 = '';
	    $batchSqlLoser4 .= "update wo_pin_join_member set status = 2,update_time = {$timeNow} where pid={$pid} and uid in(";
	    $i = 0;
	    foreach($loserArr as $v){
		if($i == 0) {
		    $batchSqlLoser1 .= "{$v}";
		    $batchSqlLoser2 .= "({$v},{$reward},1,'参团奖励',{$timeNow})";
		    $batchSqlLoser3 .= "{$v}";
		    $batchSqlLoser4 .= "{$v}";
		} else {
		    $batchSqlLoser1 .= ",{$v}";
		    $batchSqlLoser2 .= ",({$v},{$reward},1,'参团奖励',{$timeNow})";
		    $batchSqlLoser3 .= ",{$v}";
		    $batchSqlLoser4 .= ",{$v}";
		}
		$i++;
	    }
	    $batchSqlLoser1 .= ")) b on a.uid=b.uid set a.integral = a.integral+b.integral4,a.integral4 = 0";
	    $batchSqlLoser3 .= ")";
	    $batchSqlLoser4 .= ')';
	    $sqlL1 = Db::execute($batchSqlLoser1);
	    $sqlL2 = Db::execute($batchSqlLoser2);
	    $sqlL3 = Db::execute($batchSqlLoser3);
	    $sqlL4 = Db::execute($batchSqlLoser4);
/* 待开启 end   */
			    
	    if(!$winnerArr) {
		echo 400;
		exit;
		//return api(400,'获取中奖者失败');
	    }

	    //处理中奖者，中奖者weight=-weight,中奖者账户-1000,给其余用户返分成
	    $this->redis->sadd("tuanwinner:{$pid}",...$winnerArr);
	    //pin_weight = -pin_weight

	    foreach($winnerArr as $v1) {
		    //$sql1 = "update wo_user set pin_weight = -pin_weight,pin_wallet_b=pin_wallet_b-900 where uid=".$v1; 待开启
		    $sql1 = "update wo_user set pin_weight = -98 where uid=".$v1;
		    $sql2 = "update wo_pin_join_member set status = 1,update_time ={$timeNow} where pid={$pid} and  uid=".$v1; //----------notice
		    $integral4 = Db::table('wo_integral_member')->where(['uid'=>$v1])->value('integral4');  //待开启
		    $sql3 = "insert into wo_integral_billing_detail(uid,inte,td,create_time) values({$v1},'{$integral4}',62,{$timeNow})";  //待开启
		    $sql4  = "update wo_integral_member set integral4 = 0 where uid=".$v1;  //待开启
		    $sql5 = "insert into wo_pin_price_detail(uid,quota,type,detail,create_time) values({$v1},900,0,'中奖消耗',{$timeNow})"; //待开启
		    $sql6 = "update wo_user set pin_wallet_b = pin_wallet_b-900 where uid={$v1}"; 
	    	    $res1 = Db::execute($sql1);
	    	    $res2 = Db::execute($sql2);
	    	    $res3 = Db::execute($sql3);  //待开启
		    $res4 = Db::execute($sql4);  //待开启
	    	    $res5 = Db::execute($sql5);  //待开启
	    	    $res6 = Db::execute($sql6);  //待开启
		//todo: 中奖用户逻辑以及其余用户返现
		
	    }
	    Db::commit();
	    $this->redis->del("tuanset");
	    $this->guanjiaReward($pid);
	    $this->deepReward($pid);
	    echo 200;
	    exit;
	    //return api(200,'中奖者选出');
//--	} else {
//--	    echo 400;
//--	    exit;
	    //return api(400,'参数错误');
//--	}

    }
    public function deepReward($pid) {
            //查商品价格
            $price = Db::query("select b.price from wo_pin_periods a join wo_pin_product b on a.gid = b.id  where a.id = {$pid}");
            if(!$price) {
                echo 600;
                exit;
            }
            $price = $price[0]['price'];	    
            $lianwoShop = Db::connect([
                'type'        => 'mysql',
                'hostname'    => '127.0.0.1',
                'database'    => 'lianwo_shop',
                'username'    => 'root',
                'password'    => 'jbLR8mzLY2zG2pNE',
                'hostport'    => '3306',
            ]);
	    $total = (int)$price * 2 * 10 / 100;
	    $winner = $this->redis->sMembers("tuanwinner:{$pid}");
	    $res1 = $lianwoShop->query("select pid from user_level where uid = {$winner[0]}");
	    $res2 = $lianwoShop->query("select pid from user_level where uid = {$winner[1]}");
	    
	    if (!$res1 or !$res2) {  echo 600;exit;}
	    $resAll = [$res1,$res2];
	    for($i=0;$i<count($resAll);$i++){
	    if(0 == $resAll[$i][0]['pid']) {
	        echo 201;
	        exit;	
	    } else {
	       
			//select * from lianwo_shop.user_level a join test_hy_lw.wo_user b on a.uid=b.uid where 
                $rewardL1 = floor(floor((int)$total * 35 / 100) / 2);
/*
                $batchSql1 = '';
                $batchSql1 .= "update wo_user set pin_wallet_s = pin_wallet_s+{$perRewardL1} where uid in(";
                $batchSqli1 = '';
                $batchSqli1 .= "insert into wo_pin_price_detail(uid,quota,type,detail,create_time) values ";
*/
	        $timenow = time();
	       
		$res2 = $lianwoShop->query("select b.pin_weight,a.pid from lianwo_shop.user_level a join hy_lianwo.wo_user b on a.uid=b.uid where a.uid={$resAll[$i][0]['pid']} ");
			
		//$res2 = Db::execute("select pin_weight from wo_user where uid = {$res1[0]}");
		if(!$res2) { echo 600;exit; }
		$is = 2;
		if($res2[0]['pin_weight'] != 0) {
		    //可以领取奖励
	    	    Db::startTrans();
		    $sqlr1 = Db::execute("update wo_user set pin_wallet_s = pin_wallet_s+{$rewardL1} where uid ={$resAll[$i][0]['pid']} ");
		    $sqlr2 = Db::execute("insert into wo_pin_price_detail(uid,quota,type,detail,create_time) values({$resAll[$i][0]['pid']},{$rewardL1},1,'深度分红',{$timenow})");
		    if(!$sqlr1 or !$sqlr2) {
		        Db::rollback();
		        echo 602;
		        exit;
		    }
	    	    Db::commit();
		    $this->recurDeepReward($res2[0]['pid'],$is,$lianwoShop,$total);
		} else {
		    $this->recurDeepReward($res2[0]['pid'],$is,$lianwoShop,$total);
		}

	    }
	    }

	    echo 200;
	    exit;
           

    }
    //递归深度奖励
    protected function recurDeepReward(int $uid,int $i,$lianwoShop,int $total){
	//递归基 $i = 10
	if($i >= 10) return ;
	if($i==2) $reward = floor($total * 20 / 100);
	if($i==3) $reward = floor($total * 15 / 100);
	if($i==4) $reward = floor($total * 10 / 100);
	if($i==5) $reward = floor($total * 5 / 100);
	if($i>5) $reward = floor($total * 3 / 100);
	if($uid != 0) {
	    $res = $lianwoShop->query("select b.pin_weight,a.pid from lianwo_shop.user_level a join hy_lianwo.wo_user b on a.uid=b.uid where a.uid={$uid} ");
	    if($res[0]['pin_weight'] == 0) {

	    	$this->recurDeepReward($res[0]['pid'],++$i,$lianwoShop,$total);
	    } else {
               $res2 = $lianwoShop->query("select count(*) as count from lianwo_shop.user_level a join hy_lianwo.wo_user b on a.uid=b.uid where a.pid={$uid} and b.pin_weight !=0");                       
	       $timenow = time();
               if($res2[0]['count'] >= $i) {
                   //uid可以领取奖励
	    	   Db::startTrans();
                   $sql1 = Db::execute("update wo_user set pin_wallet_s = pin_wallet_s+{$reward} where uid ={$uid} ");
                   $sql2 = Db::execute("insert into wo_pin_price_detail(uid,quota,type,detail,create_time) values({$uid},{$reward},1,'中奖者深度分红',{$timenow})");
		   if(!$sql1 or !$sql2) {
			Db::rollback();
			echo 603;
			return;
		   } else {
			Db::commit();
		   }
                   $this->recurDeepReward($res[0]['pid'],++$i,$lianwoShop,$total); 
               }
		    
	    }
	} else {
	    echo 201;	
	    return ;
	}
    }
    //管家分红
    public function guanjiaReward($pid){
	    //查商品价格
	    $price = Db::query("select b.price from wo_pin_periods a join wo_pin_product b on a.gid = b.id  where a.id = {$pid}");
	    if(!$price) {
		echo 600;
		return ;
	    }
	    $price = (float)$price[0]['price'];
	    //获取pid对应的用户集合
	    $setM = $this->redis->sMembers("pintuanfilter1:{$pid}");
	    $lianwoShop = Db::connect([
    		'type'        => 'mysql',
    		'hostname'    => '127.0.0.1',
    		'database'    => 'lianwo_shop',
    		'username'    => 'root',
    		'password'    => 'jbLR8mzLY2zG2pNE',
    		'hostport'    => '3306',
	    ]);
	    if(!$setM) {
		echo 600;
		return ;
	    }
	    $setMArr = [];
	    foreach($setM as $v) {
		$setMArr[] = explode(':',$v)[0];
	    }
	    //$setMArr = [19,47,87,294];
	    $sql1 ='';
	    $sql1 .= "select uid,level from user_level where uid in (";
	    $i = 0;
	    foreach($setMArr as $v) {
		if($i==0) {
		    $sql1 .= "{$v}";
		} else {
		    $sql1 .= ",{$v}";
		}
		$i++;
	    }
	    $sql1 .= ')';
	    //[[],[],[]]
	    $res1 = $lianwoShop->query($sql1);
	    $userLevel1 = [];
	    $userLevel2 = [];
	    $userLevel3 = [];
	    $userLevel4 = [];
	    foreach($res1 as $user) {
		switch($user['level']) {
		    case 1:
		    	$userLevel1[] = $user['uid'];
			break;
		    case 2:
			$userLevel2[] = $user['uid'];
			break;
		    case 3:
			$userLevel3[] = $user['uid'];
			break;
		    case 4:
			$userLevel4[] = $user['uid'];
			break;
		}
	    }
	    $total = $price * 2 * 10 / 100;
	    $timenow = time();
	    Db::startTrans();
	    if(count($userLevel1) > 0) {
		$totalRewardL1 = $total * 10 / 100;
		$perRewardL1 = floor($totalRewardL1 / ceil((count($userLevel1) * 1)));
		$batchSql1 = '';
		$batchSql1 .= "update wo_user set pin_wallet_s = pin_wallet_s+{$perRewardL1} where uid in(";
		$batchSqli1 = '';
		$batchSqli1 .= "insert into wo_pin_price_detail(uid,quota,type,detail,create_time) values ";
		$i=0;
		foreach($userLevel1 as $v) {
		    if($i == 0) {
			$batchSql1 .= "{$v}";	
			$batchSqli1 .= "({$v},{$perRewardL1},1,'一星级管家分红',{$timenow})";
		    } else {
			$batchSql1 .= ",{$v}";
			$batchSqli1 .= ",({$v},{$perRewardL1},1,'一星级管家分红',{$timenow})";
		    }
		    $i++;
		}
		$batchSql1 .= ')';
		$res1 = Db::execute($batchSql1);
		$resi1 = Db::execute($batchSqli1);
		if(!$res1 or !$resi1) {
		     Db::rollback();
		     echo 600;
		     return ;
		} 
	    }
	    if(count($userLevel2) > 0) {
		$totalRewardL2 = $total * 20 / 100;
		$perRewardL2 = floor($totalRewardL2 / ceil((count($userLevel2) * 1)));
		$batchSql2 = '';
		$batchSql2 .= "update wo_user set pin_wallet_s = pin_wallet_s+{$perRewardL2} where uid in(";
		$batchSqli2 = '';
		$batchSqli2 .= "insert into wo_pin_price_detail(uid,quota,type,detail,create_time) values ";
		$j=0;
		foreach($userLevel2 as $v) {
		    if($j == 0) {
			$batchSql2 .= "{$v}";	
			$batchSqli2 .= "({$v},{$perRewardL2},1,'二星级管家分红',{$timenow})";
		    } else {
			$batchSql2 .= ",{$v}";
			$batchSqli2 .= ",({$v},{$perRewardL2},1,'二星级管家分红',{$timenow})";
		    }
		    $j++;
		}
		$batchSql2 .= ')';
		$res2 = Db::execute($batchSql2);
		$resi2 = Db::execute($batchSqli2);
		if(!$res2 or !$resi2) {
		     Db::rollback();
		     echo 600;
		     return ;
		} 
	    }
	    if(count($userLevel3) > 0) {
		$totalRewardL3 = $total * 30 / 100;
		$perRewardL3 = floor($totalRewardL3 / ceil((count($userLevel3) * 1.5)));
		$batchSql3 = '';
		$batchSql3 .= "update wo_user set pin_wallet_s = pin_wallet_s+{$perRewardL3} where uid in(";
		$batchSqli3 = '';
		$batchSqli3 .= "insert into wo_pin_price_detail(uid,quota,type,detail,create_time) values ";
		$k=0;
		foreach($userLevel3 as $v) {
		    if($k == 0) {
			$batchSql3 .= "{$v}";	
			$batchSqli3 .= "({$v},{$perRewardL3},1,'三星级管家分红',{$timenow})";
		    } else {
			$batchSql3 .= ",{$v}";
			$batchSqli3 .= ",({$v},{$perRewardL3},1,'三星级管家分红',{$timenow})";
		    }
		    $k++;
		}
		$batchSql3 .= ')';
		$res3 = Db::execute($batchSql3);
		$resi3 = Db::execute($batchSqli3);
		if(!$res3 or !$resi3) {
		     Db::rollback();
		     echo 600;
		     return ;
		} 
	    }
/*
	    if(count($userLevel4) > 0) {
		$totalRewardL4 = $total * 40 / 100;
		$perRewardL4 = floor($totalRewardL4 / count($userLevel4));
		$batchSql4 = '';
		$batchSql4 .= "update wo_user set pin_wallet_s = pin_wallet_s+{$perRewardL4} where uid in(";
		$batchSqli4 = '';
		$batchSqli4 .= "insert into wo_pin_price_detail(uid,quota,type,detail,create_time) values ";
		$l=0;
		foreach($userLevel4 as $user) {
		    if($l == 0) {
			$batchSql4 .= "{$v}";	
			$batchSqli4 .= "({$v},{$perRewardL4},1,'星级管家分红',{$timenow})";
		    } else {
			$batchSql4 .= ",{$v}";
			$batchSqli4 .= ",({$v},{$perRewardL4},1,'星级管家分红',{$timenow})";
		    }
		    $l++;
		}
		$batchSql4 .= ')';
		$res4 = Db::execute($batchSql4);
		$resi4 = Db::execute($batchSqli4);
		if(!$res4 or !$resi4) {
		     Db::rollback();
		     echo 600;
		     exit;
		} 
	    }
*/
	    Db::commit();
	    echo 200;
	    return ;
		

    }
    //筛选集合用户
    protected function recurFilterUser(array $tuanMember,array &$filterMem,int $weight,int $surNum) {
	//递归基 surnum <= 0
	if($surNum <= 0) return $filterMem;
	$partArr = [];
		//dump($tuanMember);
	foreach($tuanMember as $k=>$v) { 
	    if(explode(":",$v)[1] == $weight) {
		$partArr[] = $v;
	    } 
	}     
	
	//dump($partArr);
	if(count($partArr) == $surNum) { 
	    $filterMem = array_merge($filterMem,$partArr);
	} else if(count($partArr) > $surNum) {
	    $randomKeys = array_rand($partArr,$surNum);  //随机返回限>制人数下的成员
	    if(!is_array($randomKeys)) $randomKeys = [$randomKeys];
	    $rfFunc = function ($v) use(&$partArr) {
		return $partArr[$v];
	    };

	    $filterMem = array_merge($filterMem,array_map($rfFunc,$randomKeys));
	} else if(count($partArr) < $surNum) {
	    //求出剩余人数  
	    $surNum = (int)$surNum - count($partArr);
	    $filterMem = array_merge($filterMem,$partArr);
	    $this->recurFilterUser($tuanMember,$filterMem,++$weight,$surNum);

	} 
    }
    

        //计算均价
        protected function avgPrice($start,$end){
            $sale = Db::name('integral_sale')->field('inte,price')->where([['status','=',2],['create_time','between',[$start,$end]]])->select();
            $total = 0;
            $inte = 0;
            foreach ($sale as $val) {
                $inte += $val['inte'];
                $total += $val['price'] * $val['inte'];
            }
                // dump($total);die;

            if($inte == 0){
            	return 0;
            }else{
                return round(($total / $inte) ,2);
            }
        }
        public function testPreAddUser($uid,$pid) {
	// if ($this->reqiwst->isPost()) {
	    // $param = $request->param;
	    // $uid = $param['uid']; //用户id
	    // //查看用户本金够不
	    // /* 待开启
	    // $pinWallB = Db::table('wo_user')->where(['uid'=>$uid])->value('pin_wallet_b');
	    // if((float)$pinWallB < 90) return api(400,'拼团本金不足,预约失败');
	    // */
	    
	    // //查看用户豆子够不够 待定
	    // /* 待开启
	    // $uInte = Db::table('wo_integral_member')->where(['uid'=>$uid])->value('integral');
	    // if (!$uInte) return api(400,'积分异常,提交申请失败');
	    // $uInte < X  return api(400,'积分不够,提交申请失败');  
	    // */
	
	
	    
	    
	    // $pid = $param['id']; //期id
	    $nowP = time();
	    //查看要参的团是否逾期
	    $resPeriod = Db::table('wo_pin_periods')->where(['id'=>$pid])->field('begin_dt,end_dt,t_num')->find();
	    //本团限制人数
	    $limitNum = $resPeriod['t_num'];
	    if($resPeriod['begin_dt'] > $nowP or $resPeriod['end_dt'] < $nowP) return api(400,'申请的团不存在,或未到开放时间');
	    //根据uid 查出权重
	    $pinWeight = Db::table('wo_user')->where(['uid'=>$uid])->value('pin_weight');

	    //一个uid只能参加一个团
	    if($this->redis->sismember('tuanset',$uid)) {
		return api(400,'申请提交失败,你已参加本团或其他团');
	    }
	    $this->redis->sadd('tuanset',$uid);
	
	    //[tid=>[[uid1,pinweight1],[uid2,pinweight2]]] 传入redis 集合
	    $res = $this->redis->sAdd("pintuan:{$pid}","{$uid}:{$pinWeight}");
	    if($res) {
		//够的话，integral 扣  ，+ integral3 里面。 待定
		/* 待开启
	    	Db::startTrans();
		$res1 = Db::table('wo_integral_member')->where(['uid'=>$uid])->setDec('integral',X);
	 	$res2 = Db::table('wo_integral_member')->where(['uid'=>$uid])->setInc('integral3',X);
		if(!$res1 or !$res2) {
		    Db::rollback();
		    $this->redis->srem('tuanset',$uid);
		    return api(400,'申请参团提交失败,积分问题');
		    	    
		}
		
		*/
		//冻结豆子
		/* 待开启
		//昨日开始
		$starttime = mktime(0,0,0,date('m'),date('d')-1,date('Y'));
		//昨日结束
		$endtime = mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
		$avgPriceY = $this->avgPrice($starttime,$endtime);
		Db::startTrans();
		if((int)$avgPriceY > 0.5) {
		    $inte = ceil(($avgPriceY * 10 / 100) * 0.5);
		    
		    $res = Db::execute("update wo_integral_member set integral=integral-{$inte},integral3=integral3+{$inte} where uid = {$uid}");
		    if(!$res) {
			Db::rollback();
			$this->redis->srem('tuanset',$uid);
			return api(400,'申请参团提交失败');
		    }
		
		} else {
		    $inte = ceil((0.5 * 10 / 100) * 0.5);
		    $res = Db::execute("update wo_integral_member set integral=integral-{$inte},integral3=integral3+{$inte} where uid = {$uid}");
		    if(!$res) {
			Db::rollback();
			$this->redis->srem('tuanset',$uid);
			return api(400,'申请参团提交失败');
		    }
		}
		Db::commit();
		*/
		// return api(200,'申请参团提交成功');
	    } else {
		$this->redis->srem('tuanset',$uid);
	//	$this->redis->decrby("tuannum:{$pid}",1);
		return api(400,'申请参团提交失败');
	    }

 //       } else {
	//     return api(400,'参数错误');
	// }
    }
    public function testPinTuan()
    {
	$this->sendMessToWorker2(999999,1599449187,1599476352);
exit;
//$mems = $this->redis->smembers("pintuan:75");
//dump(count($mems));exit;
	$this->deepReward(74);
exit;
    	$uids = Db::name('user')->where('uid','>',10000)->limit(100000)->column('uid');
    	$randomKeys = array_rand($uids,320);  //随机返回限>制人数下的成员
		// $uids = [67810,187081,187080];
	    foreach ($randomKeys as $v){
	    	$this->testPreAddUser($uids[$v],2);
	    }
	    echo "success";
	    
    	
    }
    public function test1(Request $request){
	$param = $request->get();
	echo "200:11";
	exit;
    }
    public function test2(Request $request){
	$param = $request->get();
	echo "200:22";
	exit;
    }
    public function test3(Request $request){
	$param = $request->get();
	echo "200:33";
	exit;
    }
    public function createGroup(Request $request){ 
    	
	// $this->redis->setex("tuanlock:3",3,1);
	
	// dump($this->redis->get('tuanlock:3') == 1);
    	$this->sendMessToWorker2(2,1599120000,1599146400);
    	
    }
    private function sendMessToWorker2(int $pid,int $beginAt,int $endAt) {
        $client = stream_socket_client('tcp://127.0.0.1:5678', $errno, $errmsg, 1);
        if(!$client) return false;
        $data = ['pid'=>$pid,'begin_dt'=>$beginAt,'end_dt'=>$endAt];
        if(FALSE === fwrite($client, json_encode($data)."\n")) {
            return false;
        }
        
        return fread($client,11);
    }
}
