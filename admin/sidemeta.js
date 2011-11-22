jQuery(function() {
	jQuery('.pageorder-force-button').click(function() {
			var button = jQuery(this);
			var data = {
				catid: button.attr('id').replace('catid', ''),
				id: CURPOSTID
			}
			var send = {
				action: 'page_order_ajax',
				hook: 'force_sortable',
				data: data
			};
			jQuery.post(ajaxurl, send, function(r) {
				button.fadeOut();
			});
	});
});