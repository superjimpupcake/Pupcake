<?php
/**
 * The async plugin, help pupcake make asynchronous request
 */
namespace Pupcake\Plugin\Async;

use Pupcake;

class Main extends Pupcake\Plugin
{
  public function load($config = array())
  {
    $app = $this->getAppInstance();
    $app->method("sendAsyncRequest", array($this, "sendAsyncRequest"));  
  }

  /**
   * make asynchronous request
   * credit: http://www.php.net/manual/en/function.fsockopen.php#101872
   */
  public function sendAsyncRequest(
    $request_type = 'GET',             /* HTTP Request Method (GET and POST supported) */
    $url,                      /* Full url (example: http://example.com/test */
    $data = array(),           /* HTTP GET or POST Data ie. array('var1' => 'val1', 'var2' => 'val2') */
    $timeout = 3               /* Request timeout */
  )
  {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $port = 80;

    $schema = "";

    //find the protocol
    $schema_pattern = substr($url, 0, 4);

    if($schema_pattern != "http"){
      if($url[0] == "/"){
        $url[0] = "";
        $url =trim($url);
      }
      $server_protocol_comps = explode("/", strtolower($_SERVER['SERVER_PROTOCOL']));
      $schema = $server_protocol_comps[0];
      if($schema == "https"){
        $port = 443;
      }
    }    

    $host = $_SERVER['HTTP_HOST'];
    $url = $schema.":/".$host."/".$url;

    $url_parts = parse_url($url);

    if(isset($url_parts['path'])){
      $uri = str_replace($host."/", "", $url_parts['path']);
    }

    //if the host has custom port number, override the default one
    if(isset($url_parts['port'])){
      $port = $url_parts['port'];
    }

    $ret = '';
    $request_type = strtoupper($request_type); //allow using lowercase in request type

    $getdata = array();
    $postdata = array();

    if($request_type == 'GET'){
      $getdata = $data;
    }
    else if($request_type == 'POST'){
      $postdata = $data;
    }

    $cookie = $_COOKIE; //preserve the cookie data

    $cookie_str = '';
    $getdata_str = count($getdata) ? '?' : '';
    $postdata_str = '';

    foreach ($getdata as $k => $v)
      $getdata_str .= urlencode($k) .'='. urlencode($v) . '&';

    foreach ($postdata as $k => $v)
      $postdata_str .= urlencode($k) .'='. urlencode($v) .'&';

    foreach ($cookie as $k => $v)
      $cookie_str .= urlencode($k) .'='. urlencode($v) .'; ';

    $crlf = "\r\n";
    $req = $request_type .' '. $uri . $getdata_str .' HTTP/1.1' . $crlf;
    $req .= 'Host: '. $host . $crlf;
    $req .= 'User-Agent: '.$user_agent . $crlf;
    $req .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' . $crlf;
    $req .= 'Accept-Language: en-us,en;q=0.5' . $crlf;
    $req .= 'Accept-Encoding: deflate' . $crlf;
    $req .= 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7' . $crlf;

    if (!empty($cookie_str))
      $req .= 'Cookie: '. substr($cookie_str, 0, -2) . $crlf;

    if ($request_type == 'POST' && !empty($postdata_str))
    {
      $postdata_str = substr($postdata_str, 0, -1);
      $req .= 'Content-Type: application/x-www-form-urlencoded' . $crlf;
      $req .= 'Content-Length: '. strlen($postdata_str) . $crlf . $crlf;
      $req .= $postdata_str;
    }
    else $req .= $crlf;

    if (($fp = @fsockopen($host, $port, $errno, $errstr)) == false)
      return "Error $errno: $errstr\n";

    stream_set_timeout($fp, 0, $timeout * 1000);

    fputs($fp, $req);
    while ($line = fgets($fp)) $ret .= $line;
    fclose($fp);
  }

}
