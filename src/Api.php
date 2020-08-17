<?php

namespace Taobao;

/*
 * 淘宝开放平台公共基础类
 * 文档地址: https://open.taobao.com/api.htm
 * @Author: lovefc 
 * @Date: 2020-08-12 10:38:21
 * @Last Modified by: lovefc
 * @Last Modified time: 2020-08-12 10:38:21
 */

class Api
{
    public $client_id; // 编号id

    public $client_secret; // 应用密钥

    public $backurl; // 回调地址

    public $data_type; // 接口返回数据格式

    public $taobao_token_file; // token授权后的json存放文件，会在实例化类的时候自动解析

    public $access_token; // token

    public $refresh_token; // 刷新token   

    public $expires_in; // token刷新时间

    public $api_url; // api接口

    // 构造函数
    public function __construct($config)
    {
        if ($config) {
            $this->configuration($config, $token_json);
            $this->restToken();
        }
        if (!empty($token_json) && is_array($token_json)) {
            $this->arrToken($token_json);
        }
        $this->api_url = 'https://eco.taobao.com/router/rest';	
    }

    // 解析配置
    public function configuration($config, $token_json)
    {
        $this->client_id = isset($config['client_id']) ? $config['client_id'] : '';
        $this->client_secret = isset($config['client_secret']) ? $config['client_secret'] : '';
        $this->backurl = isset($config['backurl']) ? $config['backurl'] : '';
        $this->data_type = isset($config['data_type']) ? strtoupper($config['data_type']) : 'JSON';
        $this->taobao_token_file = isset($config['taobao_token_file']) ? $config['taobao_token_file'] : '';
    }

    // token转数组
    public function arrToken($token_json)
    {
        $config = json_decode($token_json, true);
        $this->expires_in = isset($config['expires_in']) ? $config['expires_in'] : 0;
        $this->access_token = isset($config['access_token']) ? $config['access_token'] : '';
        $this->refresh_token = isset($config['refresh_token']) ? $config['refresh_token'] : '';
    }

    // 获取解析token
    public function restToken()
    {
        if (is_file($this->taobao_token_file) && empty($this->access_token)) {
            $token_json = file_get_contents($this->taobao_token_file);
            $this->arrToken($token_json);
        }
    }

    // 保存token
    public function saveToken($str)
    {
        $arr = json_decode($str, true);
        $token = isset($arr['access_token']) ? $arr['access_token'] : false;
        if ($token) {
            file_put_contents($this->taobao_token_file, $str);
            $this->restToken();
            return true;
        }
        return false;
    }

    // 提交请求
    public function post($url, $data = '', $head = 'application/json')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:{$head};charset=utf-8;"));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在       
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $output = curl_exec($ch);
        if ($output === false) {
            $this->error('接口出错'.curl_error($ch));
        }
        curl_close($ch);
        return $output;
    }

    //生成登录链接
    public function getHref()
    {
        $query = 'https://oauth.taobao.com/authorize?response_type=code&client_id=' . $this->client_id . '&redirect_uri=' . urlencode($this->backurl) . '&state=1212';
        if ($this->isMobile() != true) {
            $url = $query . '&view=web'; // pc端
        } else {
            $url = $query . '&view=wap'; // 手机端
        }
        return $url;
    }
	
    // 生成链接
    public function creQuery($data)
    {
        $arr = array(
            'app_key' => $this->client_id,
            'timestamp' => date('Y-m-d H:i:s', time()), //时间戳，格式为yyyy-MM-dd HH:mm:ss，时区为GMT+8，例如：2015-01-01              12:00:00。淘宝API服务端允许客户端请求最大时间误差为10分钟
            'format' => $this->data_type, //响应格式。默认为xml格式，可选值：xml，json。
            'v' => '2.0', //API协议版本，可选值：2.0
            'sign_method' => 'md5',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->backurl,
            'state' => '1212',
            'view' => 'web'
        );
        $data += $arr;
        $sign = $this->creSign($data);
        $data['sign'] = $sign;
        return $data;
    }
	
    // 组合提交
    public function submit($data)
    {
        $data = $this->creQuery($data);
        $api = $this->api_url .'?'. http_build_query($data, null, '&');
        return $this->post($api);
    }

    // 魔术方法，自动判断接口权限并执行函数
    public function __call($method, $args)
    {
        $name =  str_replace('_', '.', $method);
        return $this->runTaobaoApi($name, $args);
    }

    // 执行api
    public function runTaobaoApi($name, $data = '')
    {
        $query = array(
            'method' => $name,
        );
        if (!empty($data)) {
            $query = $query + $data[0];
        }
        return $this->submit($query);
    }
	
	// 获取token
    public function getToken($code)
    {
		$datas = array('code' => $code);
        $data = $this->taobao_top_auth_token_create($datas);
        $data = json_decode($data, true);
        $data = isset($data['top_auth_token_create_response']['token_result'])?$data['top_auth_token_create_response']['token_result']:null;
		if($data){
		   return $data;
		}
		return false;
    }
	
    // 刷新token,注意re_expires_in为0的话,不能进行刷新
	// 刷新失败参考:https://open.taobao.com/help?spm=a219a.7386797.0.0.5def669aHmPByd&source=search&docId=1811&docType=14
    public function getNewToken()
    {
		$datas = array('refresh_token' => $this->refresh_token);
        $data = $this->taobao_top_auth_token_refresh($datas);
        $data = json_decode($data, true);
        $data = isset($data['top_auth_token_refresh_response']['token_result'])?$data['top_auth_token_refresh_response']['token_result']:null;
		if($data){
		   $this->saveToken($data);
		   return true;
		}
		return false;
    }	

    //签名算法
    public function creSign($params_list)
    {
        ksort($params_list);
        $param_link = null;
        foreach ($params_list as $key => $value) {
            $param_link .= $key . $value;
        }
        $param_link = $this->client_secret . $param_link . $this->client_secret;
        $param_link = utf8_encode($param_link);
        $md5_secret = strtoupper(md5($param_link));
        return $md5_secret;
    }

    // 判断是否手机访问
    public function isMobile()
    {
        $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
        $mobile_browser = '0';
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
            $mobile_browser++;
        if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false))
            $mobile_browser++;
        if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
            $mobile_browser++;
        if (isset($_SERVER['HTTP_PROFILE']))
            $mobile_browser++;
        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
            'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
            'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
            'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
            'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
            'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
            'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
            'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
            'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-'
        );
        if (in_array($mobile_ua, $mobile_agents))
            $mobile_browser++;
        if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
            $mobile_browser++;
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
            $mobile_browser = 0;
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
            $mobile_browser++;
        if ($mobile_browser > 0)
            return true;
        else
            return false;
    }

    //打印错误
    public function error($error,$error_code = 1)
    {
       $error = array(
	      'error_msg' => $error,
	      'error_code' => $error_code

       );		
       die(json_encode($error));
    }
}
