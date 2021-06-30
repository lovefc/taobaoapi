# 淘宝开放平台API类库

php封装的淘宝api类库，简单好用，配合文档食用，不用再去记函数名以及参数


### 安装

直接下载源码或者使用 composer 安装

````
{
    "require": {
        "lovefc/taobaoapi": "0.0.2"
    }		
}
````

### 食用方法

````

$config = array(
    'client_id' => 'xx', //client_id
	'client_secret' => 'xxxx', //client_secret
	'backurl' => 'xxxxxx', //回调地址
	'data_type' => 'json', // 返回数据格式
	'taobao_token_file' => __DIR__ .'/taobao_token.txt'
);

// 必须要授权后使用
$taobao = new Taobao\Api($config);

$datas = array(
    // session的值如果提示要传,就要传,它本质上就是你授权获取的token
    'session' => $taobao->access_token,
    'simplify'=>'true',
    'fields' => 'tid,orders,receiver_name,receiver_state,receiver_address,receiver_mobile,receiver_phone,shop_pick,buyer_nick,receiver_town,o2o_shop_name'
);

// 这里使用的函数就是淘宝的api接口名
// 参考 https://open.taobao.com/api.htm?docId=46&docType=2
// 注意把api接口名中的点号换成下划线即可，传参请参考文档
$data = $taobao->taobao_trades_sold_get($datas);

$data = json_decode($data, true);

print_r($data);


````

### 交流讨论

QQ群号：474310505

![avatar](/qq.png)
