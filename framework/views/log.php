<!-- start log messages -->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<table class="skyLog" width="100%" cellpadding="2" style="border-spacing:1px;font:11px Verdana, Arial, Helvetica, sans-serif;background:#EEEEEE;color:#666666;">
	<tr>
		<th style="background:black;color:white;" colspan="5">
			应用日志
		</th>
	</tr>
	<tr style="background-color: #ccc;">
	    <th style="width:120px">时间戳</th>
		<th>级别</th>
		<th>分类</th>
		<th>消息</th>
	</tr>
<?php
$colors=array(
// 	\Sky\logging\Logger::LEVEL_PROFILE=>'#DFFFE0',
	\Sky\logging\Logger::LEVEL_INFO=>'#FFFFDF',
	\Sky\logging\Logger::LEVEL_WARNING=>'#FFDFE5',
	\Sky\logging\Logger::LEVEL_ERROR=>'#FFC0CB',
);
foreach($data as $index=>$log)
{
	$color=($index%2)?'#F5F5F5':'#FFFFFF';
	if(isset($colors[$log[1]]))
		$color=$colors[$log[1]];
	$message='<pre>'.\Sky\help\Html::encode(wordwrap($log[0])).'</pre>';
	$time=date('H:i:s.',$log[3]).sprintf('%06d',(int)(($log[3]-(int)$log[3])*1000000));

	echo <<<EOD
	<tr style="background:{$color}">
		<td align="center">{$time}</td>
		<td>{$log[1]}</td>
		<td>{$log[2]}</td>
		<td>{$message}</td>
	</tr>
EOD;
}
?>
</table>
<!-- end of log messages -->