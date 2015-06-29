/*
 * @package WP Simple Booking Calendar
 *
 * Copyright (c) 2011 WP Simple Booking Calendar
 */

jQuery(function($) {
	
	var $container = $('#sbc-metabox');
	$container.find('p.submit input[type=button]').bind('click', function(event) {
		event.preventDefault();
		var attributes = '';
		$container.find('table.form-table td').find(':input').each(function() {
			var $this = $(this);
			
			var name = $this.attr('name');
			name = name.substring('sbcMetabox'.length + 1, name.length - 1);
			
			var value = $.trim($this.val().replace(/"/g, '{quot}').replace(/</g, '&lt;').replace(/>/g, '&gt;'));
			if (value != '' && value != 'default') {
				attributes += ' ' + name + '="' + value + '"';
			}
		});
		
		send_to_editor('[sbc' + attributes + '] ');
	});
	
});