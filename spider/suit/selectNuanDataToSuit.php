<?php
ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
set_time_limit(0);// 通过set_time_limit(0)可以让程序无限制的执行下去
require '../zuber.php';
require '../mysql/selectNuanDataToSuit.php';

$i = 1;

while(true){
    $zuber = new zuber();
    $zuber->setNuanUrl($i);
    $zuber->setNuanHeader();
    var_dump($zuber->zuberCurl());exit();
    $houses = $zuber->zuberCurl()['rooms'];
    if(empty($houses)){
        echo 'Finished:total page '.($i-1);
        break;
    }
    foreach ($houses as $house){
        if(array_key_exists('name',$house['author']) && array_key_exists('price',$house) && !empty($house['description'])){
            selectNuanDataToSuit::getInstance()->transAction($house);
        } else{
            continue;
        }
    }
    echo 'Page:'.$i.'<br>';
    $i++;
}
