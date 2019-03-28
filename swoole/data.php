<?php
// error_reporting(E_ERROR | E_WARNING | E_PARSE);

$server = new \swoole_server("127.0.0.1", 8088, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

$server->set(array(
    "worker_num"=>1,
));

$server->on('connect', function ($serv, $fd){ });

$server->on('receive', function ($serv, $fd, $from_id, $data){ });

$server->on('close', function ($serv, $fd){ });

// 在交互进程中放入一个数据。
$server->BaseProcess = "我是基本 进程 xxxx.";

$server->MasterToManager = '';

$server->ManagerToWorker = '';

// 为了便于阅读，以下回调方法按照被起调的顺序组织
// 1. 首先启动Master进程
$server->on("start", function (\swoole_server $server){
    echo "On master start.".PHP_EOL;
    // 先打印在交互进程写入的数据
    echo "server->BaseProcess = ".$server->BaseProcess.PHP_EOL;
    // 修改交互进程中写入的数据
    $server->BaseProcess = "我被【master】改了.";
    // 在Master进程中写入一些数据，以传递给Manager进程。
    $server->MasterToManager = "你好【manger】，我是【master】.";
    echo PHP_EOL;
});

// 2. Master进程拉起Manager进程
$server->on('ManagerStart', function (\swoole_server $server){
    echo "On manager start.".PHP_EOL;
    // 打印，然后修改交互进程中写入的数据
    echo "server->BaseProcess = ".$server->BaseProcess.PHP_EOL;
    $server->BaseProcess = "我被【manger】改了.";
    // 打印，然后修改在Master进程中写入的数据
    echo "server->MasterToManager = ".$server->MasterToManager.PHP_EOL;
    $server->MasterToManager = "【manager】修改了 【master】发给 【manager】的消息";

    // 写入传递给Worker进程的数据
    $server->ManagerToWorker = "你好【worker】，我是【manager】.";

    echo PHP_EOL;
});

// 3. Manager进程拉起Worker进程
$server->on('WorkerStart', function (\swoole_server $server, $worker_id){
    echo "Worker start".PHP_EOL;
    // 打印在交互进程写入，然后在Master进程，又在Manager进程被修改的数据
    echo "server->BaseProcess = ".$server->BaseProcess.PHP_EOL;

    // 打印，并修改Master写入给Manager的数据
    echo "server->MasterToManager = ".$server->MasterToManager.PHP_EOL;
    $server->MasterToManager = "This value has changed in worker.";

    // 打印，并修改Manager传递给Worker进程的数据
    echo "server->ManagerToWorker = ".$server->ManagerToWorker.PHP_EOL;
    $server->ManagerToWorker = "【worker】 修改了 【manager】发给 【woker】的消息.";

    echo PHP_EOL;    
});

// 4. 正常结束Server的时候，首先结束Worker进程
$server->on('WorkerStop', function(\swoole_server $server, $worker_id){
    echo "Worker stop".PHP_EOL;
    // 分别打印之前的数据
    echo "server->ManagerToWorker = ".$server->ManagerToWorker.PHP_EOL;
    echo "server->MasterToManager = ".$server->MasterToManager.PHP_EOL;
    echo "server->BaseProcess = ".$server->BaseProcess.PHP_EOL;
    echo PHP_EOL;  
});

// 5. 紧接着结束Manager进程
$server->on('ManagerStop', function (\swoole_server $server){
    echo "Manager stop.".PHP_EOL;
    // 分别打印之前的数据
    echo "server->ManagerToWorker = ".$server->ManagerToWorker.PHP_EOL;
    echo "server->MasterToManager = ".$server->MasterToManager.PHP_EOL;
    echo "server->BaseProcess = ".$server->BaseProcess.PHP_EOL;
    echo PHP_EOL;  
});

// 6. 最后回收Master进程
$server->on('shutdown', function (\swoole_server $server){
    echo "Master shutdown.".PHP_EOL;
    // 分别打印之前的数据
    echo "server->ManagerToWorker = ".$server->ManagerToWorker.PHP_EOL;
    echo "server->MasterToManager = ".$server->MasterToManager.PHP_EOL;
    echo "server->BaseProcess = ".$server->BaseProcess.PHP_EOL;
    echo PHP_EOL;  
});

$server->start();