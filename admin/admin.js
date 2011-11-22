jQuery(function() {
	jQuery('.main').hide();
	jQuery('.right').hide();
	// PageLoadFunction
		var send = {
			action: 'page_order_ajax',
			hook: 'init_pages',
		};
		jQuery.post(ajaxurl, send, function(r) {
			jQuery('.kategorier').html(r);
			jQuery('.right').fadeIn();
			var numberpost = 150;
			var data = {
				numberpost: numberpost
			}
			var send = {
				action: 'page_order_ajax',
				hook: 'load_sortable',
				data: data
			};
			jQuery.post(ajaxurl, send, function(r) {
				jQuery('.sorteringsomradet').html(r);
				jQuery('.main').fadeIn(function () {
					finish();
				});
				CoreFunctions();
			});
		});
});
function CoreFunctions() { //Core fuctions that needs to reload
	UpdateSorting();
	jQuery('.kategori-name-arrow').click(function() { // Åpne settings panel
		jQuery(this).parent().toggleClass('closed');
		jQuery(this).parent().parent().children('.kategori-settings').slideToggle('fast')
	});
	jQuery('.close-category').click(function() { // Åpne settings panel
		jQuery(this).parent().parent().parent().children('.kategori-name').toggleClass('closed');
		jQuery(this).parent().parent().parent().children('.kategori-settings').slideToggle('fast')
	});
	
	jQuery('.kategori-name-arrow-left').click(function() { // Last ny kategori
		start();
		var catid = jQuery(this).attr('id').replace('sort', '');
		var numberpost = jQuery('.numpost' + catid).val();
		var data = {
			catid: catid,
			numberpost: numberpost
		}
		var send = {
			action: 'page_order_ajax',
			hook: 'load_sortable',
			data: data
		};
		jQuery('.sorteringsomradet').hide();
		jQuery.post(ajaxurl, send, function(r) {
			jQuery('.sorteringsomradet').html(r);
			jQuery('.sorteringsomradet').fadeIn(function () {
				UpdateSorting();
				finish();
			});
		});
	});
	jQuery('.postorder-save-button').click(function() { // Lagre side
		start();
		var id = jQuery(this).attr('id').replace('save', '');
		var numpost = jQuery('.numpost' + id).val();
		var data = {
			id: id,
			numpost: numpost
		}
		var send = {
			action: 'page_order_ajax',
			hook: 'update_page',
			data: data
		};
		jQuery('.sorteringsomradet').hide();
		jQuery.post(ajaxurl, send, function(r) {
			var numberpost = jQuery('.numpost' + id).val();
			var data = {
				catid: id,
				numberpost: numberpost
			}
			var send = {
				action: 'page_order_ajax',
				hook: 'load_sortable',
				data: data
			};
			jQuery.post(ajaxurl, send, function(r) {
				jQuery('.sorteringsomradet').html(r);
				jQuery('.sorteringsomradet').fadeIn(function () {
					UpdateSorting();
					finish();
				});
			});
		});
	});
	
}
function UpdateSorting() {
	jQuery('.left-name h3').html('Rekkefølge, ' + CURCATNAME).fadeIn();
	jQuery("#fremside").sortable();
	jQuery( "#fremside" ).bind( "sortstart", function(event, ui) {
			jQuery('.listnumber').hide();
	});
	jQuery("#fremside").bind( "sortupdate", function(event, ui) {
		start();
		var IDs = [];
		var itemnum = 1;
		jQuery('ul#fremside li').attr('class',
			function(i, c){
			return c.replace(/\ item\S+/g, '');
		});
		jQuery("ul#fremside li").each(function() {
			IDs.push(jQuery(this).attr('id').replace('id', ''));
			jQuery(this).addClass('item' + itemnum);
			jQuery(this).children('.listnumber').html(itemnum).fadeIn();
			itemnum++;
		});
		var data = {
			ids: IDs,
			catid: CURCATID
		}
		var send = {
			action: 'page_order_ajax',
			hook: 'sort_sortables',
			data: data
		};
		jQuery.post(ajaxurl, send, function(r) {
			finish();
		});
	});
}
function start() {
	jQuery('.loading').fadeIn();
}
function finish() {
	jQuery('.loading').fadeOut();
}
function deletepost(id) {
	start();
	jQuery('li#id' + id).css('background', '#BC0B0B').css('color', '#FFF').css('text-shadow', 'none');
	jQuery('li#id' + id).slideUp(function() {
		jQuery('li#id' + id).remove();
		var IDs = [];
		var itemnum = 1;
		jQuery('ul#fremside li').attr('class',
			function(i, c){
			return c.replace(/\ item\S+/g, '');
		});
		jQuery("ul#fremside li").each(function() {
			IDs.push(jQuery(this).attr('id').replace('id', ''));
			jQuery(this).addClass('item' + itemnum);
			jQuery(this).children('.listnumber').html(itemnum).fadeIn();
			itemnum++;
		});
		var data = {
			id: id,
			ids: IDs,
			catid: CURCATID,
			numberpost: CURNUMPOST
		}
		var send = {
			action: 'page_order_ajax',
			hook: 'delete_sortable',
			data: data
		};
		jQuery.post(ajaxurl, send, function(r) {
			var data = {
				catid: CURCATID,
				numberpost: CURNUMPOST
			}
			var send = {
				action: 'page_order_ajax',
				hook: 'load_sortable',
				data: data
			};
			jQuery.post(ajaxurl, send, function(r) {
				jQuery('.sorteringsomradet').html(r);
				jQuery('.sorteringsomradet').fadeIn(function () {
					UpdateSorting();
					finish();
				});
			});
			finish();
		});
	});
}