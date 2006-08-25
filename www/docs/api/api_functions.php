<?

function api_output($arr) {
	$output = get_http_var('output');
	api_header($output);
	if ($output == 'xml') {
		$out = api_output_xml($arr);
	} elseif ($output == 'php') {
		$out = api_output_php($arr);
	} else { # JS
		$out = api_output_js($arr);
		$callback = get_http_var('callback');
		if (preg_match('#^[A-Za-z0-9._[\]]+$#', $callback)) {
			$out = "$callback($out)";
		}
	}
	print $out;
}

function api_header($o) {
	if ($o == 'xml') {
		$type = 'text/xml';
	} elseif ($o == 'php') {
		$type = 'text/php';
	} else {
		$type = 'text/javascript';
	}
	$type = 'text/plain';
	header("Content-Type: $type; charset=iso-8859-1");
}

function api_error($e) {
	api_output(array('twfy'=>array('error' => $e)));
}

function api_output_php($arr) {
	$out = serialize($arr);
	if (get_http_var('verbose')) $out = str_replace(';', ";\n", $out);
	return $out;
}

function api_output_xml($v, $k=null) {
	$verbose = get_http_var('verbose') ? "\n" : '';
	if (is_array($v)) {
		if (count($v) && array_keys($v) === range(0, count($v)-1)) {
			return join("</$k>$verbose<$k>", array_map('api_output_xml', $v));
		}
		$out = '';
		foreach ($v as $k => $vv) {
			$out .= "<$k>";
			$out .= api_output_xml($vv, $k);
		        $out .= "</$k>$verbose";
		}
		return $out;
	} else {
		return htmlspecialchars($v);
	}
}

function api_output_js($v, $level=0) {
	$verbose = get_http_var('verbose') ? "\n" : '';
	if (is_array($v)) {
		# PHP arrays are both JS arrays and objects
		if (count($v) && array_keys($v) === range(0, count($v)-1))
			return '[' . join(",$verbose" , array_map('api_output_js', $v)) . ']';
		$out = '{' . $verbose;
		$b = false;
		foreach ($v as $k => $vv) {
			if ($b) $out .= ",$verbose";
			if ($verbose) {
				$out .= str_repeat(' ', ($level+1)*2);
				$out .= '"' . $k . '" : ';
			} else {
				$out .= '"' . $k . '":';
			}
			$out .= api_output_js($vv, $level+1);
			$b = true;
		}
		if ($verbose) $out .= "\n" . str_repeat(' ', $level*2);
		$out .= '}';
		return $out;
	} elseif (is_null($v)) {
		return "null";
	} elseif (is_string($v)) {
		return '"' . str_replace(
			array("\\",'"',"\n","\t","\r"),
			array("\\\\",'\"','\n','\t','\r'), $v) . '"';
	} elseif (is_bool($v)) {
		return $v ? 'true' : 'false';
	} elseif (is_int($v) || is_float($v)) {
		return $v;
	}
}

function api_call_user_func_or_error($function, $params, $error, $type) {
	if (function_exists($function))
		call_user_func_array($function, $params);
	elseif ($type == 'api')
		api_error($error);
	else
		print "<p style='color:#cc0000'>$error</p>";
}

?>
