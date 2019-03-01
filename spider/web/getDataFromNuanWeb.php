<?php
require '../zuber.php';

$zuber = new zuber();
$zuber->setNuanUrl(1);
$zuber->setNuanHeader();
var_dump($zuber->zuberCurl());