<?php
// This sidebar is on the list of MPs pages

global $MEMBER;

    $SEARCHURL = new URL("search");
	$this->block_start(array('id'=>'mpsearch', 'title'=>"Search by name (including former MLAs)"));
	?>

	<div class="mpsearchbox">
		<form action="<?php echo $SEARCHURL->generate(); ?>" method="get">
		<p>
    		<input name="s" size="24" maxlength="200">
    		<input type="submit" class="submit" value="GO">
    		<input type="hidden" class="section" value="ni">
		</p>
		</form>
	</div>

<?php
	$this->block_end();
?>
