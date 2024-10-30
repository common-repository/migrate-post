<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wrap list-domain-section">
<div class="heading-domain-wrap">
	<div class="left-part-head"><h1>Migrate Post Domains</h1></div>
</div>
<form id="domains-filter" method="get">
<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
<?php $domainlist_tbl->display() ?>
</form>
</div>