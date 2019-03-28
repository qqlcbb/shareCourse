<?php
// 定时器

//创建Server对象，监听 127.0.0.1:9501端口
$serv = new swoole_server("127.0.0.1", 9501); 

// $serv->set(array(
//     'worker_num' => 1,    //worker process num
// ));

$serv->on('WorkerStart', function ($serv, $worker_id){
    $serv->tick(1000, function ($id) {
    	echo '我是定时器'. $id .PHP_EOL ;
    });
	$serv->after(500, function() {
	   	echo '我是xxx' .PHP_EOL;
	});	    
});

//监听连接进入事件
$serv->on('connect', function ($serv, $fd) {  
    echo "Client: Connect.\n";
});

//监听数据发送事件
$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    $serv->send($fd, "Server: ".$data);
});

//监听连接关闭事件
$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

//启动服务器
$serv->start(); 

