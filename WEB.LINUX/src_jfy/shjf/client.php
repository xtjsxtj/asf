<?php

function client_connect($address='172.16.18.164',$service_port=1055)
{
  //创建 TCP/IP socket
  global $socket;
  $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
  if ($socket < 0) {
    echo "socket创建失败原因: " . socket_strerror($socket) . "\n";
    return false;
  } else {
    //echo "OK，HE HE.\n";
  }

  $result = socket_connect($socket, $address, $service_port);
  if ($result < 0) {
    echo "SOCKET连接失败原因: ($result) " . socket_strerror($result) . "\n";
    return false;
  } else {
    echo "client_connect OK.\n";
  }
  
  return true;
}

function callno_get_tcp($callno)
{
  global $socket;
  //发送命令
  //echo "Send Command..........\n";
  $data = 'callno_get~' . $callno;
  $data = sprintf('%04d~%s', strlen($data)+5, $data);
  socket_write($socket, $data, strlen($data));
  //echo "Write OK.\n\n";

  //echo "Reading Result ...\n";
  $out = socket_read($socket, 4);
  $len = intval($out)-4;
  $out .= socket_read($socket, intval($out)-4);
  //echo($out . "\n");
  list($len,$code,$remain) = explode('~', $out, 3);
  if ( $code != '000' ) {
      echo $remain . "\n";
      return false;
  }
  list($result['vestss'],$result['permark'],$result['bossid'],$result['subpp']) = explode('~', $remain, 4);
  
  return $result;
}
