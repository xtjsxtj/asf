<?php

/**
 * 判断是否为HTTP协议及HTTP协议数据包是否完整
 * @param int    $fd
 * @param string $http_string
 * @return string ""表示不完整的HTTP请求继续等下一个包;非空则为完整的HTTP请求
 */
function http_input($fd, $http_string)
{
    global $req_string;

    if ( !isset($req_string[$fd]) ) $req_string[$fd]='';
    $req_string[$fd] .= $http_string;
    $http_string=$req_string[$fd];

    // 查找\r\n\r\n
    if( strpos($http_string, "\r\n\r\n") === false ) return "";

    // POST请求还要读包体
    if ( strpos($http_string, "POST") !== false )
    {
        // 找Content-Length
        $match = array();
        if(preg_match("/\r\nContent-Length: ?(\d*)\r\n/", $http_string, $match))
        {
            $content_lenght = $match[1];

            // 看包体长度是否符合
            $tmp = explode("\r\n\r\n", $http_string, 2);
            $remain_length = $content_lenght - strlen($tmp[1]);
            if ( $remain_length > 0 ) return "";
        }
    }

    $req_string[$fd] = '';

    return $http_string;
}

/**
 * 解析http协议，设置$_POST  $_GET  $_COOKIE  $_REQUEST
 * @param string $http_string
 */
function http_start($http_string, $SERVER = array())
{
    // 初始化
    $_POST = $_GET = $_COOKIE = $_REQUEST = array();
    $GLOBALS['HTTP_RAW_POST_DATA'] = '';

    // 需要设置的变量名
    $_SERVER = array (
          'QUERY_STRING' => '',
          'REQUEST_METHOD' => '',
          'REQUEST_URI' => '',
          'SERVER_PROTOCOL' => 'HTTP/1.1',
          'GATEWAY_INTERFACE' => 'CGI/1.1',
          'SERVER_SOFTWARE' => 'workerman/2.1',
          'SERVER_NAME' => '',
          'HTTP_HOST' => '',
          'HTTP_USER_AGENT' => '',
          'HTTP_ACCEPT' => '',
          'HTTP_ACCEPT_LANGUAGE' => '',
          'HTTP_ACCEPT_ENCODING' => '',
          'HTTP_COOKIE' => '',
          'HTTP_CONNECTION' => '',
          'REQUEST_TIME' => 0,
          'SCRIPT_NAME' => '',//$SERVER传递
          'REMOTE_ADDR' => '',// $SERVER传递
          'REMOTE_PORT' => '0',// $SERVER传递
          'SERVER_ADDR' => '', // $SERVER传递
          'DOCUMENT_ROOT' => '',//$SERVER传递
          'SCRIPT_FILENAME' => '',// $SERVER传递
          'SERVER_PORT' => '80',
          'PHP_SELF' => '', // 设置成SCRIPT_NAME
       );

    // 将header分割成数组
    $header_data = explode("\r\n", $http_string);

    list($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['SERVER_PROTOCOL']) = explode(' ', $header_data[0]);
    // 需要解析$_POST
    if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        $tmp = explode("\r\n\r\n", $http_string, 2);
        parse_str($tmp[1], $_POST);

        // $GLOBALS['HTTP_RAW_POST_DATA']
        $GLOBALS['HTTP_RAW_POST_DATA'] = $tmp[1];
        unset($header_data[count($header_data) - 1]);
    }

    unset($header_data[0]);
    foreach($header_data as $content)
    {
        // \r\n\r\n
        if(empty($content))
        {
            continue;
        }
        list($key, $value) = explode(':', $content, 2);
        $key = strtolower($key);
        $value = trim($value);
        switch($key)
        {
            // HTTP_HOST
            case 'host':
                $_SERVER['HTTP_HOST'] = $value;
                $tmp = explode(':', $value);
                $_SERVER['SERVER_NAME'] = $tmp[0];
                if(isset($tmp[1]))
                {
                    $_SERVER['SERVER_PORT'] = $tmp[1];
                }
                break;
            // cookie
            case 'cookie':
                {
                    $_SERVER['HTTP_COOKIE'] = $value;
                    parse_str(str_replace('; ', '&', $_SERVER['HTTP_COOKIE']), $_COOKIE);
                }
                break;
            // user-agent
            case 'user-agent':
                $_SERVER['HTTP_USER_AGENT'] = $value;
                break;
            // accept
            case 'accept':
                $_SERVER['HTTP_ACCEPT'] = $value;
                break;
            // accept-language
            case 'accept-language':
                $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $value;
                break;
            // accept-encoding
            case 'accept-encoding':
                $_SERVER['HTTP_ACCEPT_ENCODING'] = $value;
                break;
            // connection
            case 'connection':
                $_SERVER['HTTP_CONNECTION'] = $value;
                break;
            case 'referer':
                $_SERVER['HTTP_REFERER'] = $value;
                break;
            case 'if-modified-since':
                $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $value;
                break;
            case 'if-none-match':
                $_SERVER['HTTP_IF_NONE_MATCH'] = $value;
                break;
        }
    }

    // 'REQUEST_TIME_FLOAT' => 1375774613.237,
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
    $_SERVER['REQUEST_TIME'] = intval($_SERVER['REQUEST_TIME_FLOAT']);

    // QUERY_STRING
    $_SERVER['QUERY_STRING'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

    // GET
    parse_str($_SERVER['QUERY_STRING'], $_GET);

    // REQUEST
    $_REQUEST = array_merge($_GET, $_POST);

    // 合并传递的值
    $_SERVER = array_merge($_SERVER, $SERVER);

    // PHP_SELF
    if($_SERVER['SCRIPT_NAME'] && !$_SERVER['PHP_SELF'])
    {
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    }
}

function http_end($headers, $content)
{
    // header
    $header='';
    foreach($headers as $item) $header .= $item."\r\n";
    $header .= "Connection: Keep-Alive\r\n";
    $header .= "Content-type: text/plain\r\n";
    $header .= "Server: WorkerMan/2.1\r\nContent-Length: ".strlen($content)."\r\n";
    $header .= "\r\n";

    // 整个http包
    return $header.$content;
}

