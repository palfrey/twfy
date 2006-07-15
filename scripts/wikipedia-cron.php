<?

contributions('194.60.38.10');
contributions('194.203.158.97');
$dir = '../www/docs/wikipedia/cache/';

function contributions($ip) {
	global $dir;

	$file = fetch("/w/index.php?title=Special:Contributions&limit=100&target=$ip");
	$fp = fopen($dir . $ip, 'w');
	fwrite($fp, $file['body']);
	fclose($fp);
	preg_match_all('#<li>(.*?) \(<a[^>]*>hist</a>\) \(<a href="(.*?title=(.*?)&.*?oldid=(.*?))"[^>]*>diff</a>\)  <a[^>]*>(.*?)</a>  .*?</li>#', $file['body'], $m, PREG_SET_ORDER);
	foreach ($m as $row) {
		$cache = $dir . html_entity_decode("$row[3].$row[4]");
		if (!is_file($cache)) {
			$file = fetch(html_entity_decode($row[2]));
			$fp = fopen($cache, 'w');
			fwrite($fp, $file['body']);
			fclose($fp);
		}
	}
}

function fetch($url) {
    $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.4) Gecko/20060508 Firefox/1.5.0.4';
    $host = 'en.wikipedia.org';
    $fp = fsockopen($host, 80, $errno, $errstr, 10);
    if (!$fp) {
            print "$errstr ($errno)\n";
            return '';
        }
    $out = "GET $url HTTP/1.1\r\n";
    $out .= "Host: $host\r\n";
    $out .= "User-Agent: $ua\r\n";
    $out .= "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9;text/plain;q=0.8,image/png,*/*;q=0.5\r\n";
    $out .= "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n";
    $out .= "Connection: Close\r\n";
    $out .= "\r\n";
    fwrite($fp, $out);
    while (!feof($fp)) {
            $response .= fgets($fp, 1024);
        }
    fclose($fp);
    preg_match('/^(.*?)\r\n\r\n(.*)$/s', $response, $m);
    $header = $m[1];
    $body = $m[2];
    return array('header'=>$header, 'body'=>$body);
}

?>
