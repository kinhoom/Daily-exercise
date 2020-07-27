<?php
    //方法1
    private function asyncInte($urls){
        foreach($urls as $url){
            $socket=stream_socket_client("test.91lianwo.com:80",$errno,$errstr,86400);
            stream_set_blocking($socket,0);
            $rs=fwrite($socket,"GET /{$url} HTTP/1.1\r\nHost: test.91lianwo.com\r\nConnect: keep-alive\r\n\r\n");// 记录数组  注意 如果批量添加大量数据则 Connect: keep-alive
            //dump($socket);exit;
            $this->sockets[(int)$socket]=$socket;
        }
    }
    //方法2
        private function asyncInteTrans($host, $url, $port=80, $conn_timeout=30, $rw_timeout=86400)    {
        $errno = '';
        $errstr = '';
        $fp = fsockopen($host, $port, $errno, $errstr, $conn_timeout);
        if(!$fp) {
            echo "Server error:$errstr($errno)";
            return false;
        }
        stream_set_timeout($fp, $rw_timeout);
        stream_set_blocking($fp, 0);
        $rq = "GET {$url} HTTP/1.1\r\n";
        $rq .= "Host: {$host}\r\n";
        $rq .= "Connect: close\r\n\r\n";    //  注意 如果批量添加大量数据则 Connect: keep-alive
        fwrite($fp,$rq);
        return $fp;


    }
    private function asyncFetch(&$fp) {
        if($fp === false) return false;
        if(feof($fp)){
            fclose($fp);
            $fp = false;
            return $fp;
        }
        return fread($fp, 10000);
    }
    
    //sockets异步请求入口
        public function asyncUpdateInteTrans(){
$t1 = microtime(true);
        /*
        $fp = $this->asyncInte(['/sale/totalInteTrans','/sale/totalInteTrans1']);
        while(count($this->sockets)>0){
            $read=$this->sockets;
            $write=$e=[];// 等待数据可读
            //dump(stream_select($read,$write,$e,10));
            if(stream_select($read,$write,$e,10))
            {// 循环读数据
                foreach($read as $socket)
                {// 这里是服务端返回的数据，需要的话可以循环读
                    echo fread($socket,8192)."\n";// 数据读取完毕关闭链接，并删除链接
                    echo '<br>';
                    echo '<br>';
                    echo '<br>';
                    echo '<br>';
                    fclose($socket);
                    unset($this->sockets[(int)$socket]);
                }
            }

        }
        */
        $fp1 = $this->asyncInteTrans('test.91lianwo.com','/sale/totalInteTrans');
        $fp2 = $this->asyncInteTrans('test.91lianwo.com','/sale/totalInteTrans1');
//dump($fp2);
        $r1 = $this->asyncFetch($fp1);
//var_dump($fp1);exit;
        $r2 = $this->asyncFetch($fp2);
        echo $r1;
        echo $r2;
$t2 = microtime(true);
echo '耗时'.round($t2-$t1,3).'秒';
    }
    
    
    
        //获取总数据
    private function getInteData($flg){
        $now = time();
        $now = 1595813984;
        //取出所有任务时间>=当前时间的uid,level_id,task_expire
        $data = Db::table("wo_integral_task_record")->where([['task_expire','>=',$now],['level_id','<>','9']])->field('uid,level_id,task_expire')->limit(10)->select();
        foreach($data as $v){
            //获取距离过期还剩几天
            $durTime = $v['task_expire'] - $now;
            $durDay = floor($durTime / 86400);

            //创建相同uid与level day映射
            $this->releaseU[$v['uid']][] = ['level'=>$v['level_id'],'day'=>(int)$durDay];
        }
        $part = (int)(count($this->releaseU)/2);
        $arr2 = array_slice($this->releaseU,0,$part,true);
        $arr1 = array_slice($this->releaseU,$part,null,true);
        if($flg == 1)
            return $arr2;
        else
            return $arr1;

    }
    //后半段数据
    public function totalInteTrans1(){
        $arr = $this->getInteData(2);
        $levelInteMap = ['1'=>'0.4','2'=>'4','3'=>'21','4'=>'42','5'=>'210'];
        $this->updateIntegral($arr,$levelInteMap);
        echo 'part2';
    }
    //前半段数据
    public function totalInteTrans(){
        $arr = $this->getInteData(1);
        $levelInteMap = ['1'=>'0.4','2'=>'4','3'=>'21','4'=>'42','5'=>'210'];
        $this->updateIntegral($arr,$levelInteMap);
        echo 'part1';
    }
    //更新数据
        protected function updateIntegral($releaseU,$levelInteMap){
        foreach($releaseU as $uid=>$usr){
            //计算需要增加的积分总和
            $inteSum = 0;
            foreach($usr as $k=>$v){
                $carry = $levelInteMap[$v['level']] * $v['day'];
                $inteSum += $carry;
            }
            //更新integral字段
            Db::table('wo_integral_member')->where('uid','=',$uid)->setInc('integral',$inteSum);

        }
        return true;
    }
