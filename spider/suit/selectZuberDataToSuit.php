<?php
ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
set_time_limit(0);// 通过set_time_limit(0)可以让程序无限制的执行下去
require '../mysql/selectZuberDataToSuit.php';
require '../zuber.php';

$zuber = selectZuberDataToSuit::getInstance();
$i = 0;
//$web = new zuber();
//$web->setUrl(time() - $i);
//$web->setHeader();
//$res = $web->zuberCurl()['result']['items'];
//var_dump($res);exit();
while (true){
    $web = new zuber();
    $web->setUrl(time() - $i);
    $web->setHeader();
    $res = $web->zuberCurl()['result']['items'];
    if(empty($res)){
        echo "Finished";
        break;
    }
    foreach ($res as $data){
        $phone = $zuber->findRightData($data['room']);
        if(!empty($phone)){
            $zuber->transAction($data['room'],$data['user'],$phone);
        }
    }
    $i += 300;
    echo "Page:".($i/300).'<br>';
}