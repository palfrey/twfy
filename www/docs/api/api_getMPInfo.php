<?

function api_getMPInfo_front() {
?>
<p><big>Fetch extra information for a particular MP.</big></p>

<h4>Arguments</h4>
<dl>
<dt>id</dt>
<dd>The person ID.</dd>
</dl>

<h4>Example Response</h4>

<?	
}

function api_getMPinfo_id($id) {
	$db = new ParlDB;
	$q = $db->query("select data_key, data_value from personinfo
		where person_id = '" . mysql_escape_string($id) . "'");
	if ($q->rows()) {
		$output = array();
		for ($i=0; $i<$q->rows(); $i++) {
			$output[$q->field($i, 'data_key')] = $q->field($i, 'data_value');
		}
		$q = $db->query("select * from memberinfo
			where member_id in (select member_id from member where person_id = '" . mysql_escape_string($id) . "')");
		if ($q->rows()) {
			for ($i=0; $i<$q->rows(); $i++) {
				$mid = $q->field($i, 'member_id');
				if (!isset($output[$mid])) $output[$mid] = array();
				$output[$mid][$q->field($i, 'data_key')] = $q->field($i, 'data_value');
			}
		}
		ksort($output);
		api_output($output);
	} else {
		api_error('Unknown person ID');
	}
}

?>
