<?php

global $searchstring;

# XXX If you use a prefix and go to More options ,it doesn't fill the boxes. Need to factor out
# search options into some sort of object that can always return either the parts of the query
# or the long string to actually be used.
$filter_ss = $searchstring;
$from = get_http_var('from');
$to = get_http_var('to');
if (preg_match('#\s*([0-9/.-]*)\.\.([0-9/.-]*)#', $filter_ss, $m)) {
	$from = $m[1];
	$to = $m[2];
	$filter_ss =  preg_replace('#\s*([0-9/.-]*)\.\.([0-9/.-]*)#', '', $filter_ss);
}
$section = get_http_var('section');
if (preg_match('#\s*section:([a-z]*)#', $filter_ss, $m)) {
	$section = $m[1];
	$filter_ss = preg_replace("#section:$section#", '', $filter_ss);
}

$this->block_start(array( 'title' => "Filtering your results"));

?>
<form method="get" action="/search/">
<input type="hidden" name="s" value="<?=$searchstring?>">

<ul>

<li><label for="from">Date range:</label>
<input type="text" id="from" name="from" value="<?=$from?>" size="15">
 to <input type="text" name="to" value="<?=$to?>" size="15">
 <div class="help">
 You can give a <strong>start date, an end date, or both</strong>, to restrict results to a
 particular date range; a missing end date implies the current date, a missing start date
 implies the oldest date we have in the system. Dates can be entered in any format you wish, <strong>e.g.
 &ldquo;3rd March 2007&rdquo; or &ldquo;17/10/1989&rdquo;</strong>.
 </div>

<li>
 <label for="section">Section:</label>
 <select id="section" name="section">
 <option value="">Any
 <optgroup label="UK Parliament">
 <option value="uk">All
 <option value="debates">House of Commons debates
 <option value="whall">Westminster Hall debates
 <option value="lords">House of Lords debates
 <option value="wrans">Written answers
 <option value="wms">Written ministerial statements
 <option value="standing">Public Bill Committees
 </optgroup>
 <optgroup label="Northern Ireland Assembly">
 <option value="ni">Debates
 </optgroup>

 <optgroup label="Scottish Parliament">
 <option value="scotland">All
 <option value="sp">Debates
 <option value="spwrans">Written answers
 </optgroup>
 </select>
 <div class="help">
 Restrict results to a particular parliament or assembly that we cover (e.g. the
 Scottish Parliament), or a particular type of data within an institution, such
 as Commons Written Answers.
 </div>

<li><label for="column">Column:</label>
 <input type="text" id="column" name="column" value="" size="10">
 <div class="help">
 If you know the actual column number in Hansard you are interested in (perhaps you&rsquo;re looking up a paper
 reference), you can restrict results to that.
 </div>

</ul>

<p align="right"><input type="submit" value="Go"></p>

</form>

<?

$this->block_end();

