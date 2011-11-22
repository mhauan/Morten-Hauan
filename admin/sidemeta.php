<?php
if (get_post_status($post_id) == "publish") {
	global $post;
	$core = new PageOrderEditor;
	$cats = $core->get_pages();
	?>
	<script type="text/javascript">
    //<![CDATA[
        CURPOSTID = "<?php echo $post->ID; ?>";
    //]]>
    </script>
    
	<div class="fremside_meta_control">
    <?php if(get_post_meta($post->ID, "_PageOrder0", true) == "") : ?>
        <p><input type="button" class="button-primary pageorder-force-button" id="catid0" value="Flytt til fremside"/></p>
    <?php endif; ?>
    <?php
	foreach($cats as $cat) {
		$meta_name = "_PageOrder" . $cat->catid;
		if(in_category($cat->catid) && $cat->catid && get_post_meta($post->ID, $meta_name, true) == "") {
			?>
            <p><input type="button" class="button pageorder-force-button" id="catid<?php echo $cat->catid; ?>" value="Flytt til <?php echo get_cat_name($cat->catid); ?>"/></p>
            <?php
		}
	}
	?>
	</div>
	<?php

}
else {
	echo "<h4>Du må publisere innlegget før du kan velge status</h4>";
}
?>