<?php
require_once __DIR__ . '/autoload.php';

use Qiniu\Auth;

$accessKey = 'XulIPTJpj8wiZZQM3GR38-1Rbj9B8K26A2u3FaTg';
$secretKey = 'Mu4DuU9uR4oeCH5CGDeN91IzScm3hKfHuh0OfDK8';
$auth = new Auth($accessKey, $secretKey);

$bucket = 'xuqing';
$upToken = $auth->uploadToken($bucket);


echo json_encode(array('uptoken'=>$upToken));
