<?php
include_once(dirname(__FILE__) . '/../DomainModel/CasinoTable.php');

$redis = new Redis();
$redisIpAddr = '127.0.0.1';
$redisPort = 6379;
$redis->connect($redisIpAddr, $redisPort);
// use pconnect if already open

$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
$redis->setOption(Redis::OPT_PREFIX, 'cazito');

$casinoTable = new CasinoTable();
$casinoTable->id = 1;
$casinoTable->name = 'test';
$key = 'ct' + $casinoTable->id;
$redis->setex('ct' + $casinoTable->id, 300, $casinoTable);

$obj = $redis->get($key);
echo "Id of returned object is: " . $obj->id . "<br />";
echo "name of returned object is: " . $obj->name . "<br />";

$redis->delete($key);

?>
