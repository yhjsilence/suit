<?php
ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
set_time_limit(0);// 通过set_time_limit(0)可以让程序无限制的执行下去
require '../mysql/selectHezuquDataToSuit.php';

$hezuqu = selectHezuquDataToSuit::getInstance();
$houses = $hezuqu->getHousesFromHezuqu();
foreach ($houses as $house){
    $user = $hezuqu->getUserInfoById($house['userId']);
    $images = $hezuqu->getHouseImagesById($house['id']);
    $hezuqu->transAction($house,$images,$user);
}
echo "OK!";