<?php
require '../zuber.php';

$zuber = new zuber();
$zuber->setHeader();
var_dump($zuber->zuberCurl());