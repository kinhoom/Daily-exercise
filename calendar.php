<?php
date_default_timezone_set('Asia/Shanghai');
//echo date('j');
$year = empty($_REQUEST['y']) ? date('Y') : intval($_REQUEST['y']);
$month= empty($_REQUEST['m']) ? date('n') : intval($_REQUEST['m']);
$day = date('t',mktime(0,0,0,$month,date('j'),$year));
$firstmday = date("w",mktime(0,0,0,$month,1,$year));
echo $firstmday;
$weekarr=["星期日","星期一","星期二","星期三","星期四","星期五","星期六"];
echo "<center>";
echo "<h1>{$year}年{$month}月</h1>";
echo "<table width='600' border='1' >";
echo "<tr>";
for($i=0;$i<=6;$i++){
	echo "<th>{$weekarr[$i]}</th>";
}
echo "</tr>";
echo "<tr>";
$ed = 1;
while($ed <= $day){
	//echo $ed."<br>";
	echo "<tr>";
	for($i=0;$i<7;$i++){
		if($ed <= $day && ($firstmday<=$i || $ed!=1)){
			echo "<td>{$ed}</td>";
			$ed++;
		}else{
			echo "<td></td>";
		}
	}
	echo "</tr>";
	
}
echo "</tr>";
echo "</table>";
$prey = $nexty = $year;
$prem = $nextm = $month;
if($prem <=1 ){
	$prem = 12;
	$prey--;
}else{
	$prem--;
}
if($nextm >= 12){
	$nextm = 1;
	$nexty++;
}else{
	$nextm++;
}
echo "<h3><a href='index.php?y={$prey}&m={$prem}'>上月</a>&nbsp;&nbsp;";
echo "<a href='index.php?y={$nexty}&m={$nextm}'>下月</a></h3>";
echo "</center>";
$a=4;
