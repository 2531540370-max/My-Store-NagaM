<?php
require_once __DIR__ . '/../src/config/STMPConfig.php';
$email = "letuonglnkt@gmail.com";
$token = 123456;
sendResetEmail($email, $token);