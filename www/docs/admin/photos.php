<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/commentreportlist.php';
include_once INCLUDESPATH . 'easyparliament/searchengine.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
include_once INCLUDESPATH . 'easyparliament/people.php';

$this_page = 'admin_photos';

$db = new ParlDB;

$PAGE->page_start();
$PAGE->stripe_start();

#$q = $db->query('SELECT COUNT(*) AS c FROM alerts');
#$total = $q->field(0, 'c');
#$tabledata = array (
#	'header' => array('Stat', 'Number'),
#	'rows' => $rows
#);
#$PAGE->display_table($tabledata);

if (DEVSITE) {
    $out = '';
    if (get_http_var('submit_photo')) {
        $out = submit_photo();
    } else {
        $out = display_form();
    }
    print $out;
} else {
    ?><p>You can't upload files on a live site, as it doesn't have permissions
    to commit to CVS. Please go to one of the staging sites.</p>
    <?
}

function submit_photo() {
    $dir = "../images";
    $pid = intval(get_http_var('pid'));
    $errors = array();

    if (!array_key_exists('photo', $_FILES))
        array_push($errors, 'Not got the photo.');
    elseif ($_FILES['photo']['error'] > 0)
        array_push($errors, 'There was an error uploading the photo.');
    elseif (!is_uploaded_file($_FILES['photo']['tmp_name']))
        array_push($errors, 'Did not get an uploaded file.');
    else {
        $tmp_name = $_FILES['photo']['tmp_name'];

        $image = imagick_readimage($tmp_name);
        if (!$image)
            array_push($errors, 'Failed to read image from uploaded file');
	    $imageS = imagick_clonehandle($image);
        if (!imagick_scale($image, 118, 118, false))
            array_push($errors, 'Scaling large failed');
        if (!imagick_scale($imageS, 59, 59, false))
            array_push($errors, 'Scaling small failed');
	    if (!imagick_writeimage($image, "$dir/mpsL/$pid.jpeg"))
            array_push($errors, "Saving to $dir/mpsL/$pid.jpeg failed");
	    if (!imagick_writeimage($imageS, "$dir/mps/$pid.jpeg"))
            array_push($errors, "Saving to $dir/mps/$pid.jpeg failed");
	if (!$errors) {
        	print "<pre>";
	        chdir("$dir/mpsL");
        	passthru("cvs -Q add -kb $pid.jpeg 2>&1");
	        chdir("../mps");
        	passthru("cvs -Q add -kb $pid.jpeg 2>&1");
	        chdir("../");
	        passthru('cvs -Q commit -m "Photo update from admin web photo upload interface." mpsL mps 2>&1');
	        print "</pre>";
	}
    }

    if ($errors)
        return display_form($errors);
    return "<p><em>Photo uploaded and resized for pid $pid</em> &mdash; check how it looks <a href=\"/mp?p=$pid\">on their page</a></p>" . display_form();
}

function display_form($errors = array()) {
    global $db; 

    $out = '';
    if ($errors) {
        $out .= '<ul id="error"><li>' . join('</li><li>', $errors) . '</li></ul>';
    }
    $out .= <<<EOF
<p>Photos are automatically added in CVS and committed. Because of this,
only use this interface on a development version of the site which is
in a CVS checkout (francis.theyworkforyou.com or similar). Then deploy
to the live site.
</p>
<form method="post" action="photos.php" enctype="multipart/form-data">
<div class="row">
<span class="label"><label for="form_pid">Person:</label></span>
<span class="formw"><select id="form_pid" name="pid"></span>
EOF;

    $query = 'SELECT house, person_id, title, first_name, last_name, constituency, party
        FROM member
        WHERE house>0 GROUP by person_id
        ORDER BY house, last_name, first_name
	';
    $q = $db->query($query);

    $houses = array(1 => 'MP', 'Lord', 'MLA', 'MSP');

    for ($i=0; $i<$q->rows(); $i++) {
        $p_id = $q->field($i, 'person_id');
        $house = $q->field($i, 'house');
        $desc = $q->field($i, 'last_name') . ', ' . $q->field($i, 'title') . ' ' . $q->field($i, 'first_name') .
                " " . $houses[$house];
	if ($q->field($i, 'party')) $desc .= ' (' . $q->field($i, 'party') . ')';
	$desc .= ', ' . $q->field($i, 'constituency');

        list($dummy, $sz) = find_rep_image($p_id);
        if ($sz == 'L') {
            $desc .= ' [has large photo]';
        } elseif ($sz == 'S') {
            $desc .= ' [has small photo]';
        } else {
            $desc .= ' [no photo]';
        }
	    $out .= '<option value="'.$p_id.'">'.$desc.'</option>' . "\n";
    }

    $out .= <<<EOF
</select></span>
</div>
<div class="row">
    <span class="label"><label for="form_photo">Photo:</label></span>
    <span class="formw"><input type="file" name="photo" id="form_photo" size="50"></span>
</div>
<div class="row">
    <span class="label">&nbsp;</span>
    <span class="formw"><input type="submit" name="submit_photo" value="Upload photo"></span>
</div>
</form>

<p style="clear:both; margin-top: 3em"><a href="/images/mps/photo-status.php">List MPs without photos</a></p>
EOF;
    return $out;
}

$menu = $PAGE->admin_menu();
$PAGE->stripe_end(array(
	array(
		'type'		=> 'html',
		'content'	=> $menu
	)
));

$PAGE->page_end();

?>
