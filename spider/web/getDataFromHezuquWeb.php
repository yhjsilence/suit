<?php
/**
 * 获取合租趣数据添加到临时库
 */
ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
set_time_limit(0);// 通过set_time_limit(0)可以让程序无限制的执行下去

require '../zuber.php';
require '../mysql/addHezuquDataToMysql.php';

$i = 1;
while(true){
    $zuber = new zuber();
    $zuber->setHezuquUrl($i);
    $data = $zuber->zuberCurl();
    if(empty($data['data']['houses'])){
        echo 'Finished:total page '.($i-1).'<br>';
        break;
    }
    foreach ($data['data']['houses'] as $house){
        addHezuquDataToMysql::getInstance()->transAction($house);
    }
    echo 'Page:'.$i.' OK !'.'<br>';
    $i++;
}


