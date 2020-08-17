<?php

/*
 * 淘宝开放平台授权演示
 * @Author: lovefc 
 * @Date: 2020-08-17 09:36:21
 */ 

define('PATH', dirname(__FILE__));

/**
 1. 淘宝的api都要收费的
 2. 授权后的过期时间很长,有的要一年
 3. 刷新token,注意re_expires_in为0的话,不能进行刷新
    刷新失败参考:https://open.taobao.com/help?spm=a219a.7386797.0.0.5def669aHmPByd&source=search&docId=1811&docType=14
**/

// 加载文件
require PATH . '/src/Api.php';

$config = array(
    'client_id' => 'xx', //client_id
	'client_secret' => 'xxxx', //client_secret
	'backurl' => 'xxxxxx', //回调地址
	'data_type' => 'json', // 返回数据格式
	'taobao_token_file' => __DIR__ .'/taobao_token.txt'
);

$taobao = new Taobao\Api($config);

$code = isset($_GET['code']) ? $_GET['code'] : 0 ;

if (!empty($code)) {
    // 获取到access_token
    $token = $taobao->getToken($code);
    echo $token;
    // 调用这个方法，将会保存token到你设置的文件。
    $taobao->saveToken($token);
}

$autourl = $taobao->getHref();

echo "<a href='{$autourl}'>登录授权</a>";
