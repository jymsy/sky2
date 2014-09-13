<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style type="text/css">

.pQp{
	width:100%;
	text-align:center;
	/*position:fixed;*/
	bottom:0;
}
* html .pQp{
	position:absolute;
}
.pQp *{
	margin:0;
	padding:0;
	border:none;
}
#pQp{
	margin:0 auto;
	width:85%;
	min-width:960px;
	background-color:#222;
	border:12px solid #000;
	border-bottom:none;
	font-family:"Lucida Grande", Tahoma, Arial, sans-serif;
	-webkit-border-top-left-radius:15px;
	-webkit-border-top-right-radius:15px;
	-moz-border-radius-topleft:15px;
	-moz-border-radius-topright:15px;
}
#pQp .pqp-box h3{
	font-weight:normal;
	line-height:200px;
	padding:0 15px;
	color:#fff;
}
.pQp, .pQp td{
	color:#444;
}

/* ----- IDS ----- */

#pqp-metrics{
	background:#000;
	width:100%;
}
#pqp-console, #pqp-speed, #pqp-queries, #pqp-memory, #pqp-files{
/* 	background:url(../images/overlay.gif); */
	border-top:1px solid #ccc;
	height:200px;
	overflow:auto;
}

/* ----- Colors ----- */

.pQp .green{color:#588E13 !important;}
.pQp .blue{color:#3769A0 !important;}
.pQp .purple{color:#953FA1 !important;}
.pQp .orange{color:#D28C00 !important;}
.pQp .red{color:#B72F09 !important;}


.pQp .green, .pQp .blue, .pQp .purple, .pQp .orange, .pQp .orange, .pQp .red{ background:transparent !important;}

/* ----- Logic ----- */

#pQp, #pqp-console, #pqp-speed, #pqp-queries, #pqp-memory, #pqp-files{
	display:none;
}
.pQp .console, .pQp .speed, .pQp .queries, .pQp .memory, .pQp .files{
	display:block !important;
}
.pQp .console #pqp-console, .pQp .speed #pqp-speed, .pQp .queries #pqp-queries, 
.pQp .memory #pqp-memory, .pQp .files #pqp-files{
	display:block;
}
.console td.green, .speed td.blue, .queries td.purple, .memory td.orange, .files td.red{
	background:#222 !important;
	border-bottom:6px solid #fff !important;
	cursor:default !important;
}

.tallDetails #pQp .pqp-box{
	height:500px;
}
.tallDetails #pQp .pqp-box h3{
	line-height:500px;
}
.hideDetails #pQp .pqp-box{
	display:none !important;
}
.hideDetails #pqp-footer{
	border-top:1px dotted #444;
}
.hideDetails #pQp #pqp-metrics td{
	height:50px;
	background:#000 !important;
	border-bottom:none !important;
	cursor:default !important;
}
.hideDetails #pQp var{
	font-size:18px;
	margin:0 0 2px 0;
}
.hideDetails #pQp h4{
	font-size:10px;
}
.hideDetails .heightToggle{
	visibility:hidden;
}

/* ----- Metrics ----- */

#pqp-metrics td{
	height:80px;
	width:20%;
	text-align:center;
	cursor:pointer;
	border:1px solid #000;
	border-bottom:6px solid #444;
	-webkit-border-top-left-radius:10px;
	-moz-border-radius-topleft:10px;
	-webkit-border-top-right-radius:10px;
	-moz-border-radius-topright:10px;
}
#pqp-metrics td:hover{
	background:#222;
	border-bottom:6px solid #777;
}
#pqp-metrics .green{
	border-left:none;
}
#pqp-metrics .red{
	border-right:none;
}

#pqp-metrics h4{
	text-shadow:#000 1px 1px 1px;
}
.side var{
	text-shadow:#444 1px 1px 1px;
}

.pQp var{
	font-size:23px;
	font-weight:bold;
	font-style:normal;
	margin:0 0 3px 0;
	display:block;
}
.pQp h4{
	font-size:12px;
	color:#fff;
	margin:0 0 4px 0;
}

/* ----- Main ----- */

.pQp .main{
	width:80%;
}
*+html .pQp .main{
	width:78%;
}
* html .pQp .main{
	width:77%;
}
.pQp .main td{
	padding:7px 15px;
	text-align:left;
	background:#151515;
	border-left:1px solid #333;
	border-right:1px solid #333;
	border-bottom:1px dotted #323232;
	color:#FFF;
}
.pQp .main td, pre{
	font-family:Monaco, "Consolas", "Lucida Console", "Courier New", monospace;
	font-size:11px;
}
.pQp .main td.alt{
	background:#111;
}
.pQp .main tr.alt td{
	background:#2E2E2E;
	border-top:1px dotted #4E4E4E;
}
.pQp .main tr.alt td.alt{
	background:#333;
}
.pQp .main td b{
	float:right;
	font-weight:normal;
	color:#E6F387;
}
.pQp .main td:hover{
	background:#2E2E2E;
}

/* ----- Side ----- */

.pQp .side{
	float:left;
	width:20%;
	background:#000;
	color:#fff;
	-webkit-border-bottom-left-radius:30px;
	-moz-border-radius-bottomleft:30px;
	text-align:center;
}
.pQp .side td{
	padding:10px 0 5px 0;
/* 	background:url(../images/side.png) repeat-y right; */
}
.pQp .side var{
	color:#fff;
	font-size:15px;
	text-align: center;
}
.pQp .side h4{
	font-weight:normal;
	color:#F4FCCA;
	font-size:11px;
	text-align: center;
}

/* ----- Console ----- */

#pqp-console .side td{
	padding:12px 0;
}
#pqp-console .side td.alt1{
	background:#588E13;
	width:51%;
}
#pqp-console .side td.alt2{
	background-color:#B72F09;
}
#pqp-console .side td.alt3{
	background:#D28C00;
	border-bottom:1px solid #9C6800;
	border-left:1px solid #9C6800;
	-webkit-border-bottom-left-radius:30px;
	-moz-border-radius-bottomleft:30px;
}
#pqp-console .side td.alt4{
	background-color:#3769A0;
	border-bottom:1px solid #274B74;
}

#pqp-console .main table{
	width:100%;
}
#pqp-console td div{
	width:100%;
	overflow:hidden;
}
#pqp-console td.type{
	font-family:"Lucida Grande", Tahoma, Arial, sans-serif;
	text-align:center;
	text-transform: uppercase;
	font-size:9px;
	padding-top:9px;
	color:#F4FCCA;
	vertical-align:top;
	width:40px;
}
.pQp .log-log td.type{
	background:#47740D !important;
}
.pQp .log-error td.type{
	background:#9B2700 !important;
}
.pQp .log-memory td.type{
	background:#D28C00 !important;
}
.pQp .log-speed td.type{
	background:#2B5481 !important;
}

.pQp .log-log pre{
	color:#999;
}
.pQp .log-log td:hover pre{
	color:#fff;
}

.pQp .log-memory em, .pQp .log-speed em{
	float:left;
	font-style:normal;
	display:block;
	color:#fff;
}
.pQp .log-memory pre, .pQp .log-speed pre{
	float:right;
	white-space: normal;
	display:block;
	color:#FFFD70;
}

/* ----- Speed ----- */

#pqp-speed .side td{
	padding:12px 0;
}
#pqp-speed .side{
	background-color:#3769A0;
}
#pqp-speed .side td.alt{
	background-color:#2B5481;
	border-bottom:1px solid #1E3C5C;
	border-left:1px solid #1E3C5C;
	-webkit-border-bottom-left-radius:30px;
	-moz-border-radius-bottomleft:30px;
}

/* ----- Queries ----- */

#pqp-queries .side{
	background-color:#953FA1;
	border-bottom:1px solid #662A6E;
	border-left:1px solid #662A6E;
}
#pqp-queries .side td.alt{
	background-color:#7B3384;
}
#pqp-queries .main b{
	float:none;
}
#pqp-queries .main em{
	display:block;
	padding:2px 0 0 0;
	font-style:normal;
	color:#aaa;
}

/* ----- Memory ----- */

#pqp-memory .side td{
	padding:12px 0;
}
#pqp-memory .side{
	background-color:#C48200;
}
#pqp-memory .side td.alt{
	background-color:#AC7200;
	border-bottom:1px solid #865900;
	border-left:1px solid #865900;
	-webkit-border-bottom-left-radius:30px;
	-moz-border-radius-bottomleft:30px;
}

/* ----- Files ----- */

#pqp-files .side{
	background-color:#B72F09;
	border-bottom:1px solid #7C1F00;
	border-left:1px solid #7C1F00;
}
#pqp-files .side td.alt{
	background-color:#9B2700;
}

/* ----- Footer ----- */

#pqp-footer{
	width:100%;
	background:#000;
	font-size:11px;
	border-top:1px solid #ccc;
}
#pqp-footer td{
	padding:0 !important;
	border:none !important;
}
#pqp-footer strong{
	color:#fff;
}
#pqp-footer a{
	color:#999;
	padding:5px 10px;
	text-decoration:none;
}
#pqp-footer .credit{
	width:20%;
	text-align:left;
}
#pqp-footer .actions{
	width:80%;
	text-align:right;
}
#pqp-footer .actions a{
	float:right;
	width:auto;
}
#pqp-footer a:hover, #pqp-footer a:hover strong, #pqp-footer a:hover b{
	background:#fff;
	color:blue !important;
	text-decoration:underline;
}
#pqp-footer a:active, #pqp-footer a:active strong, #pqp-footer a:active b{
	background:#ECF488;
	color:green !important;
}
</style>
</head>

<script type="text/javascript">
var PQP_DETAILS = true;
var PQP_HEIGHT = "short";

addEvent(window, 'load', function(){
    document.getElementById("pqp-container").style.display = "block";
});

function changeTab(tab){
    var pQp = document.getElementById('pQp');
    hideAllTabs();
    addClassName(pQp, tab, true);
}

function hideAllTabs(){
    var pQp = document.getElementById('pQp');
    removeClassName(pQp, 'console');
    removeClassName(pQp, 'speed');
    removeClassName(pQp, 'queries');
    removeClassName(pQp, 'memory');
    removeClassName(pQp, 'files');
}

function toggleDetails(){
    var container = document.getElementById('pqp-container');
    
    if (PQP_DETAILS) {
        addClassName(container, 'hideDetails', true);
        PQP_DETAILS = false;
    }
    else {
        removeClassName(container, 'hideDetails');
        PQP_DETAILS = true;
    }
}

function toggleHeight(){
    var container = document.getElementById('pqp-container');
    
    if (PQP_HEIGHT == "short") {
        addClassName(container, 'tallDetails', true);
        PQP_HEIGHT = "tall";
    }
    else {
        removeClassName(container, 'tallDetails');
        PQP_HEIGHT = "short";
    }
}


//http://www.bigbold.com/snippets/posts/show/2630
function addClassName(objElement, strClass, blnMayAlreadyExist){
    if (objElement.className) {
        var arrList = objElement.className.split(' ');
        if (blnMayAlreadyExist) {
            var strClassUpper = strClass.toUpperCase();
            for (var i = 0; i < arrList.length; i++) {
                if (arrList[i].toUpperCase() == strClassUpper) {
                    arrList.splice(i, 1);
                    i--;
                }
            }
        }
        arrList[arrList.length] = strClass;
        objElement.className = arrList.join(' ');
    }
    else {
        objElement.className = strClass;
    }
}

//http://www.bigbold.com/snippets/posts/show/2630
function removeClassName(objElement, strClass){
    if (objElement.className) {
        var arrList = objElement.className.split(' ');
        var strClassUpper = strClass.toUpperCase();
        for (var i = 0; i < arrList.length; i++) {
            if (arrList[i].toUpperCase() == strClassUpper) {
                arrList.splice(i, 1);
                i--;
            }
        }
        objElement.className = arrList.join(' ');
    }
}

//http://ejohn.org/projects/flexible-javascript-events/
function addEvent(obj, type, fn){
    if (obj.attachEvent) {
        obj["e" + type + fn] = fn;
        obj[type + fn] = function(){
            obj["e" + type + fn](window.event)
        };
        obj.attachEvent("on" + type, obj[type + fn]);
    }
    else {
        obj.addEventListener(type, fn, false);
    }
}

</script>
<div id="pqp-container" class="pQp" style="display:none">
<div id="pQp" class="console">
	<table id="pqp-metrics" cellspacing="0">
		<tr>
			<td class="green" onclick="changeTab('console');">
				<var><?php echo count($logs['console']);?></var>
				<h4>控制台</h4>
			</td>
			<td class="blue" onclick="changeTab('speed');">
				<var><?php echo $speedTotals['total']?></var>
				<h4>加载时间</h4>
			</td>
			<td class="purple" onclick="changeTab('queries');">
				<var><?php echo $queryTotals['count']; ?> Queries</var>
				<h4>数据库</h4>
			</td>
			<td class="orange" onclick="changeTab('memory');">
				<var><?php echo $memoryTotals['used']?></var>
				<h4>内存消耗</h4>
			</td>
			<td class="red" onclick="changeTab('files');">
				<var><?php echo count($files); ?> Files</var>
				<h4>加载文件</h4>
			</td>
		</tr>
	</table>
	<div id='pqp-console' class='pqp-box'>
		<?php if(count($logs['console']) == 0): ?>
			<h3>当前面板没有日志信息</h3>
		<?php else: ?>
			<table class='side' cellspacing='0'>
			<tr>
				<td class='alt1'><var><?php echo $logs['logCount'];?></var><h4>Logs</h4></td>
				<td class='alt2'><var><?php echo $logs['errorCount'];?></var> <h4>Errors</h4></td>
			</tr>
			<tr>
				<td class='alt3'><var><?php echo $logs['memoryCount'];?></var> <h4>Memory</h4></td>
				<td class='alt4'><var><?php echo $logs['speedCount'];?></var> <h4>Speed</h4></td>
			</tr>
			</table>
			<table class='main' cellspacing='0'>
				<?php foreach($logs['console'] as $i => $log):?>
					<tr class='log-<?php echo $log['type']; ?>'>
						<td class='type'><?php echo $log['type']; ?></td>
						<td class="<?php echo $i%2 == 0 ? '':'alt'; ?>">
							<?php if($log['type'] == 'log' || $log['type'] == 'error'): ?>
								<div><?php echo nl2br($log['data']); ?></div>
							<?php elseif($log['type'] == 'memory'): ?>
								<div><b><?php echo $log['data']; ?></b> <em><?php echo $log['dataType']; ?></em>: <?php echo nl2br($log['name']); ?> </div>
							<?php elseif($log['type'] == 'speed'): ?>
								<div><b><?php echo $log['data']; ?></b> <em><?php echo $log['name']; ?></em></div>
							<?php elseif($log['type'] == 'error'): ?>
								<div><em>Line <?php echo $log['line']; ?></em> : <?php echo $log['data']; ?> <pre><?php echo $log['file']; ?></pre></div>
							<?php endif; ?>
						</td>
						</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</div>

	<div id="pqp-speed" class="pqp-box">
		<table class='side' cellspacing='0'>
			<tr><td><var><?php echo $speedTotals['total']; ?></var><h4>加载时间</h4></td></tr>
			<tr><td class='alt'><var><?php echo $speedTotals['allowed']; ?> s</var> <h4>最大执行时间</h4></td></tr>
		</table>
		<?php if(count($profile) == 0): ?>
			<h3>当前面板没有日志信息</h3>
		<?php else: ?>

			<table class='main' cellspacing='0'>
			<?php foreach($profile as $i => $log):?>
					<tr class='log-speed'>
						<td class="<?php echo $i%2 == 0 ? '':'alt'; ?>"><b><?php echo $this->getReadableTime($log[4]/$log[1]); ?></b> <?php echo $log[0]; ?></td>
					</tr>
			<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</div>

	<div id='pqp-queries' class='pqp-box'>
		<table class='side' cellspacing='0'>
		<tr><td><var><?php echo $queryTotals['count']; ?></var><h4>总查询个数</h4></td></tr>
		<tr><td class='alt'><var><?php echo $this->getReadableTime($queryTotals['time']); ?></var> <h4>总时间</h4></td></tr>
		<tr><td><var><?php echo $queryTotals['duplicates']; ?></var> <h4>重复查询个数</h4></td></tr>
		</table>
		<?php if($queryTotals['count'] == 0): ?>
			<h3>当前面板没有日志信息</h3>
		<?php else: ?>

				<table class='main' cellspacing='0'>
					<?php foreach($queries as $i => $query):?>
						<tr>
							<td class="<?php echo $i%2 == 0 ? '':'alt'; ?>">
								<?php echo $query[0]; ?>
								<em>
									Count: <b><?php echo $query[1]; ?></b> &middot;
									Total: <b><?php echo $this->getReadableTime($query[4]); ?></b> &middot;
									Average: <b><?php echo $this->getReadableTime($query[4]/$query[1]); ?></b> &middot;
									Min: <b><?php echo $this->getReadableTime($query[2]); ?></b> &middot;
									Max: <b><?php echo $this->getReadableTime($query[3]); ?></b>
								</em>
							</td>
						</tr>
				<?php endforeach; ?>
				</table>
		<?php endif; ?>
	</div>

	<div id="pqp-memory" class="pqp-box">
		<table class='side' cellspacing='0'>
			<tr><td><var><?php echo $memoryTotals['used']; ?></var><h4>已用内存</h4></td></tr>
			<tr><td class='alt'><var><?php echo $memoryTotals['total']; ?></var> <h4>总内存</h4></td></tr>
		</table>
		<?php if($logs['memoryCount'] == 0): ?>
			<h3>当前面板没有日志信息</h3>
		<?php else: ?>

			<table class='main' cellspacing='0'>
			<?php foreach($memory as $i => $log):?>
					<tr class='log-<?php echo $log['type']; ?>'>
						<td class="<?php echo $i%2 == 0 ? '':'alt'; ?>"><b><?php echo $log['data']; ?></b> <em><?php echo $log['dataType']; ?></em>: <?php echo nl2br($log['name']); ?></td>
					</tr>
			<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</div>

	<div id='pqp-files' class='pqp-box'>
			<table class='side' cellspacing='0'>
				<tr><td><var><?php echo $fileTotals['count']; ?></var><h4>总文件数</h4></td></tr>
				<tr><td class='alt'><var><?php echo $fileTotals['size']; ?></var> <h4>总大小</h4></td></tr>
				<tr><td><var><?php echo $fileTotals['largest']; ?></var> <h4>最大文件</h4></td></tr>
			</table>
			<table class='main' cellspacing='0'>
				<?php foreach($files as $i => $file): ?>
					<tr><td class="<?php echo $i%2 == 0 ? '':'alt'; ?>"><b><?php echo $file['size'];?></b> <?php echo $file['name'];?></td></tr>
				<?php endforeach; ?>
			</table>
	</div>

	<table id="pqp-footer" cellspacing="0">
		<tr>
			<td class="credit">
				
				<strong>PHP</strong>
				<b class="green">Q</b><b class="blue">u</b><b class="purple">i</b><b class="orange">c</b><b class="red">k</b>
				Profiler</a></td>
			<td class="actions">
				<a href="#" onclick="toggleDetails();return false">详细信息</a>
				<a class="heightToggle" href="#" onclick="toggleHeight();return false">展开</a>
			</td>
		</tr>
	</table>
</div>
</div>