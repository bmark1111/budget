<?php

$sUrl = 'https://api.sbox.eplat123456.com/sscript';

//include('../lib/main/curl.php');

$c = curl_init($sUrl);
curl_setopt($c, CURLOPT_VERBOSE, TRUE);

$sData = 'hello';

curl_setopt($c, CURLOPT_POST, TRUE);
curl_setopt($c, CURLOPT_POSTFIELDS, $sData);
curl_setopt($c, CURLOPT_TIMEOUT, 5);

$res = curl_exec($c);
var_export($res);
$sErr = curl_error($c);
$nErr = curl_errno($c);

echo 'Error: ' . $sErr . "\r\n";
echo 'Errno: ' . $nErr . "\r\n";
curl_close($c);
