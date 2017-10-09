<?php
/**
 * GUID生成器
 */
if (!function_exists('createGuid')) {
    function createGuid()
    {
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            $charid = strtoupper(md5(uniqid(mt_rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
            return $uuid;
        }
    }
}

/**
 * 生成随机大写MD5
 */
if (!function_exists('randomMd5')) {
    function randomMd5()
    {
        return strtoupper(md5(uniqid(mt_rand(), true)));
    }
}

/**
 * 生成随机指定位数数字
 */
if (!function_exists('randomNum')) {
    function randomNum($len)
    {
        $str = str_shuffle('0123456789');
        $random = '';

        for ($i = 0; $i < $len; $i++) {
            $random .= $str[mt_rand(0, 9)];
        }

        return $random;
    }
}

/**
 * 生成随机指定位数数字加字母
 */
if (!function_exists('randomAlphaNum')) {
    function randomAlphaNum($len)
    {
        $str = str_shuffle('0123456789qwertyuipasdfghjkzxcvbnm');
        $random = '';

        for ($i = 0; $i < $len; $i++) {
            $random .= $str[mt_rand(0, 33)];
        }

        return $random;
    }
}

/**
 * 生成随机指定位数汉字
 */
if (!function_exists('randomHanzi')) {
    function randomHanzi($len, $encoding = 'utf8')
    {
        $random = '';

        if ($encoding == 'utf8') {
            for ($i = 0; $i < $len; $i++) {
                $random .= '&#'.rand(19968, 40869).';';
            }

            return mb_convert_encoding($random, "UTF-8", "HTML-ENTITIES");
        } else if ($encoding == 'gbk') {
            for ($i = 0; $i < $len; $i++) {
                $random .= chr(rand(0xB0,0xF7)).chr(rand(0xA1,0xFE));
            }
        }

        return $random;
    }
}

if (!function_exists('encrypt256')) {
    /**
     * 加密, 支持数组字符串
     * @param $value
     * @param $password
     * @return string
     */
    function encrypt256($value, $password)
    {
        $value = json_encode($value);
        $key = substr(sha1($password), 0, 16);
        $iv = openssl_random_pseudo_bytes(16);
        $value = openssl_encrypt($value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if (!$value) {
            return false;
        }

        return base64_encode(substr($iv, 0, 8)
            . $value
            . substr($iv, 8, 8));
    }
}

if (!function_exists('decrypt256')) {
    /**
     * 解密
     * @param $value
     * @param $password
     * @return mixed
     */
    function decrypt256($value, $password)
    {
        $value = base64_decode($value);
        $key = substr(sha1($password), 0, 16);
        $iv = substr($value, 0, 8) . substr($value, -8, 8);
        $value = substr($value, 8, -8);
        $value = openssl_decrypt($value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if (!$value) {
            return false;
        }

        return json_decode($value, true);
    }
}

if (!function_exists('http')) {
    /**
     * Curl请求
     * @param $url string 地址
     * @param $method string HTTP请求方法
     * @param $postfields array 请求参数
     * @return mixed
     */
    function http($url, $method, $postfields = NULL, $format='json')
    {
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ci, CURLOPT_TIMEOUT, 60);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_HEADER, FALSE);
        switch ($method) {
            case 'PUT':
                curl_setopt($ci, CURLOPT_SAFE_UPLOAD, false);
                curl_setopt($ci, CURLOPT_PUT, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
            case 'POST':
                curl_setopt($ci, CURLOPT_SAFE_UPLOAD, true);
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($postfields)) {
                    $url = "{$url}?{$postfields}";
                }
                break;
            case 'GET':
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_URL, $url . '?' . http_build_query($postfields));
                }
                break;
        }
        $response = curl_exec($ci);
        if (curl_errno($ci)) {
            return false;
        }
        curl_close($ci);

	if ($format == 'json') {
	    $response = json_decode($response, true);
	}
        return $response;
    }
}

/**
 * 按拼音排序
 * 转换成GBK是因为GBK是按拼音排序的
 */
if (!function_exists('sortByPinyin')) {
    function sortByPinyin(&$arr) {
        uasort($arr, function($a, $b){
            $a = iconv('UTF-8', 'GBK//IGNORE', $a);
            $b = iconv('UTF-8', 'GBK//IGNORE', $b);
            return $a > $b ? 1 : -1;
        });
    }
}
