/*
 * Zendesk for WordPress
 *
 * The following is the javascript code, mostly for the dashboard
 * widgets, changing the views and viewing single tickets. The single
 * ticket request is an AJAX call handled by WordPress and the plugin
 * backend via the AJAX API. Comments to tickets javascripts are here
 * too, the logic is behind _ajax callbacks in main file.
 *
 */

// Fire upon document ready
jQuery(document).ready(function($) {

	// Use this for dialog boxes
	$('<div><div id="zendesk-dialog"><div id="zendesk-dialog-inner"><h1 id="zendesk-dialog-title">Dialog Title</h1><div id="zendesk-dialog-body"></div><div id="zendesk-dialog-footer"><a class="powered-by-zendesk" target="_blank" href="http://zendesk.com/?source=wordpress-plugin">powered by Zendesk</a></div><br class="clear" /></div></div>').appendTo('body').hide();
	$('<div><div id="zendesk-dialog-success-wrapper"><div id="zendesk-dialog-success"><img class="zendesk-success-logo" src="' + zendesk.plugin_url + '/images/zendesk-190.png" width="192" height="188" alt="Zendesk" /><p class="success-title">Success!</p><p>Ticket <a target="_blank" href="#" class="success-ticket-id">#2991</a> was created without any problems</p><a href="#" class="button success-close">Awesome! Close this window</a><br class="clear" /><div id="zendesk-dialog-footer"><a class="powered-by-zendesk" target="_blank" href="http://zendesk.com/?source=wordpress-plugin">powered by Zendesk</a></div><br class="clear" /></div></div></div>').appendTo('body').hide();

	// Change view sliders, live listening since views can be changed
	// dynamically.
	$('.zendesk-change-view').live('click', function() {
		$('.zendesk-tickets-widget-main').slideUp();
		$('.zendesk-tickets-widget-views').slideDown();
		return false;
	});

	// Cancel change view sliders.
	$('.zendesk-change-view-cancel').click(function() {
		$('.zendesk-tickets-widget-views').slideUp();
		$('.zendesk-tickets-widget-main').slideDown();
		return false;
	});

	// Single ticket view cancel slider.
	$('.zendesk-change-single-cancel').click(function() {
		$('.zendesk-tickets-widget-single').slideUp();
		$('.zendesk-tickets-widget-main').slideDown();
		return false;
	});

	// Alt class for table views.
	$(".zendesk-views-table tr:odd, .zendesk-tickets-table tr:odd").addClass("alt");

	// Change a view dynamically
	$('.zendesk-views-table a').click(function() {
		var view_id = $(this).attr('data-id');
		var clicked = this;

		var params = {
			'action': 'zendesk_get_view',
			'view_id': view_id
		};

		// Mark the clicked link as loading (adds a loading icon)
		$(clicked).addClass('zendesk-view-loading');

		// Fire the AJAX request, look for a response
		$.post(ajaxurl, params, function(response) {
			if (response.status == 200) {
				$(clicked).removeClass('zendesk-view-loading');
				$('.zendesk-tickets-widget-main').html(response.html).slideDown();

				// It's a new table, so we have to re-apply the alt class.
				$(".zendesk-tickets-table tr:odd").addClass("alt");
				$('.zendesk-tickets-widget-views').slideUp();
			}
		}, 'json');

		// Don't follow link, although (probably) valid.
		return false;
	});

	// When a ticket is requested, live listening since such links can
	// be generated via other AJAX calls.
	$('.zendesk-ticket-view').live('click', function() {
		var ticket_id = $(this).attr('data-id');

		// Get ready for an AJAX call
		var params = {
			'action': 'zendesk_view_ticket',
			'ticket_id': ticket_id
		};

		// Store the ticket id text and loader to restore afterwards.
		var tr = $(this).parents('tr');
		var ticket_id_text = $(tr).find('.zendesk-ticket-id-text');
		var loader = $(tr).find('.zendesk-loader');

		// Show the loader
		$(ticket_id_text).hide();
		$(loader).show();

		// Fire the POST request.
		$.post(ajaxurl, params, function(response) {

			// All good
			if (response.status == 200) {

				var ticket = response.ticket;
				var html = response.html;

				// Set the title and the HTML in the placeholders.
				$('#zendesk-ticket-title').text('#' + ticket.id);
				$('#zendesk-ticket-details-placeholder').html(html).autolink().mailto();

				// Show the single view, hide the main.
				$('.zendesk-tickets-widget-main').slideUp();
				$('.zendesk-tickets-widget-single').slideDown();
			}

			// Restore the loader status.
			$(ticket_id_text).show();
			$(loader).hide();

		}, 'json');

		// Prevents from browsing to the underlying link.
		return false;
	});

	// When the view comments link is clicked, live listening.
	$('.zendesk-view-comments').live('click', function() {
		var ticket_id = $(this).attr('data-id');
		var colorbox_open = false;

		// Create a "loading" colorbox
		$.colorbox({
			overlayClose: false,
			opacity: 0.6,
			initialWidth: '300px',
			initialHeight: '150px',
			onOpen: function() { colorbox_open = true; fix_flash(); },
			onCleanup: function() { colorbox_open = false; }
		});

		// Format the request
		var params = {
			'action': 'zendesk_view_comments',
			'ticket_id': ticket_id
		};

		// Launch the POST request and receive JSON.
		$.post(ajaxurl, params, function(response) {

			// Do nothing if the user closed the colorbox.
			if (!colorbox_open) return;

			if (response.status == 200) {
				// Set the Zendesk dialog contents
				$('#zendesk-dialog-body').html(response.html).autolink().mailto();
				$('#zendesk-dialog-title').text('Zendesk ticket comments thread');
			} else {
				// Set the error message
				$('#zendesk-dialog-body').html(create_notice(response.error));
				$('#zendesk-dialog-title').text('');
			}

			// Replace the "loading" colorbox with our dialog.
			$.colorbox({
					inline: true,
					href: "#zendesk-dialog",
					width: '680px',
					maxHeight: '80%',
					overlayClose: false
			}).resize();
		}, 'json');

		// Prevent further browsing.
		return false;
	});

	// Comments to tickets
	$('.zendesk-convert').click(function() {
		var comment_id = $(this).attr('data-id');
		var colorbox_open = false;

		// Create the "loading" box
		$.colorbox({
			initialWidth: '300px',
			initialHeight: '150px',
			overlayClose: false,
			opacity: 0.6,
			onOpen: function() { colorbox_open = true; fix_flash(); },
			onCleanup: function() { colorbox_open = false; }
		});

		// Format the AJAX request
		var params = {
			'action': 'zendesk_convert_to_ticket',
			'comment_id': comment_id
		};

		// Fire the AJAX request and wait for JSON
		$.post(ajaxurl, params, function(response) {

			// Do nothing if the user closed the colorbox.
			if (!colorbox_open) return;

			if (response.status == 200) {

				// Fill our dialog box with some contents.
				$('#zendesk-dialog-body').html(response.html).autolink().mailto();
				$('#zendesk-dialog-title').text('Convert this comment into a Zendesk ticket');

				// Replace the colorbox with our new dialog.
				$.colorbox({
						inline: true,
						href: "#zendesk-dialog",
						width: '680px',
						overlayClose: false
				});
			}
		}, 'json');

		// Prevent further browsing.
		return false;
	});

	// Comments to tickets, the actual POST
	$('.zendesk-comment-to-ticket-form').live('submit', function() {

		// Show the loader
		$(this).find('.zendesk-loader').show();
		$(this).find('.zendesk-submit').hide();

		// Gather the data
		var form = this;
		var comment_id = $(form).find('[name="zendesk-comment-id"]').val();
		var message = $(form).find('[name="zendesk-comment-reply"]').val();
		var comment_public = $(form).find('[name="zendesk-comment-public"]').attr('checked');
		var post_reply = $(form).find('[name="zendesk-post-reply"]').attr('checked');

		// Format the AJAX request
		var params = {
			'action': 'zendesk_convert_to_ticket_post',
			'comment_id': comment_id,
			'message': message,
			'comment_public': comment_public,
			'post_reply': post_reply
		};

		// Fire the POST request
		$.post(ajaxurl, params, function(response) {

			if (response.status == 200) {
				// Everything's fine, display the Success dialog.
				$('#zendesk-dialog-success .success-ticket-id').text('#' + response.ticket_id).attr('href', response.ticket_url);
				$.colorbox({
					inline: true,
					href: '#zendesk-dialog-success-wrapper',
					width: '680px'
				});

			} else {
				// An error has occured
				$(form).find('.zendesk-notices').html(create_notice(response.error));
				$(form).find('.zendesk-loader').hide();
				$(form).find('.zendesk-submit').show();
				$.colorbox.resize();

			}

		}, 'json');

		return false;
	});

	$('#zendesk-dialog-success .success-close').click(function() { $.colorbox.close(); return false; } );

	function create_notice(text) {
		return '<div class="zendesk-admin-notice zendesk-alert"><p>' + text + '</p></div>';

	}

});

// Creates auto links ( modified: http://forum.jquery.com/topic/jquery-simple-autolink-and-highlight-12-1-2010 )
jQuery.fn.autolink = function () {
    return this.each( function(){
        var re = /((http|https|ftp):\/\/[\w?=&.\/-;#~%-]+(?![\w\s?&.\/;#~%"=-]*>))/g;
        jQuery(this).html( jQuery(this).html().replace(re, '<a target="_blank" href="$1">$1</a>' ));
    });
}

// Creates auto e-mails ( modified: http://forum.jquery.com/topic/jquery-simple-autolink-and-highlight-12-1-2010 )
jQuery.fn.mailto = function () {
    return this.each( function() {
        var re = /(([a-z0-9*._+]){1,}\@(([a-z0-9]+[-]?){1,}[a-z0-9]+\.){1,}(travel|museum|[a-z]{2,4})(?![\w\s?&.\/;#~%"=-]*>))/g
        jQuery(this).html( jQuery(this).html().replace( re, '<a href="mailto:$1">$1</a>' ));
    });
}

// Fixes wmode for Flash elements
var fixed_flash = false;
function fix_flash() {

	// Don't fix twice.
	if (fixed_flash) return;
	fixed_flash = true;

    // loop through every embed tag on the site
    var embeds = document.getElementsByTagName('embed');
    for(i=0; i<embeds.length; i++)  {
        embed = embeds[i];
        var new_embed;
        // everything but Firefox & Konqueror
        if(embed.outerHTML) {
            var html = embed.outerHTML;
            // replace an existing wmode parameter
            if(html.match(/wmode\s*=\s*('|")[a-zA-Z]+('|")/i))
                new_embed = html.replace(/wmode\s*=\s*('|")window('|")/i,"wmode='transparent'");
            // add a new wmode parameter
            else
                new_embed = html.replace(/<embed\s/i,"<embed wmode='transparent' ");
            // replace the old embed object with the fixed version
            embed.insertAdjacentHTML('beforeBegin',new_embed);
            embed.parentNode.removeChild(embed);
        } else {
            // cloneNode is buggy in some versions of Safari & Opera, but works fine in FF
            new_embed = embed.cloneNode(true);
            if(!new_embed.getAttribute('wmode') || new_embed.getAttribute('wmode').toLowerCase()=='window')
                new_embed.setAttribute('wmode','transparent');
            embed.parentNode.replaceChild(new_embed,embed);
        }
    }
    // loop through every object tag on the site
    var objects = document.getElementsByTagName('object');
    for(i=0; i<objects.length; i++) {
        object = objects[i];
        var new_object;
        // object is an IE specific tag so we can use outerHTML here
        if(object.outerHTML) {
            var html = object.outerHTML;
            // replace an existing wmode parameter
            if(html.match(/<param\s+name\s*=\s*('|")wmode('|")\s+value\s*=\s*('|")[a-zA-Z]+('|")\s*\/?\>/i))
                new_object = html.replace(/<param\s+name\s*=\s*('|")wmode('|")\s+value\s*=\s*('|")window('|")\s*\/?\>/i,"<param name='wmode' value='transparent' />");
            // add a new wmode parameter
            else
                new_object = html.replace(/<\/object\>/i,"<param name='wmode' value='transparent' />\n</object>");
            // loop through each of the param tags
            var children = object.childNodes;
            for(j=0; j<children.length; j++) {

                if(typeof children[j].getAttribute != 'undefined' && children[j].getAttribute('name').match(/flashvars/i)) {
                    new_object = new_object.replace(/<param\s+name\s*=\s*('|")flashvars('|")\s+value\s*=\s*('|")[^'"]*('|")\s*\/?\>/i,"<param name='flashvars' value='"+children[j].getAttribute('value')+"' />");
                }
            }
            // replace the old embed object with the fixed versiony
            object.insertAdjacentHTML('beforeBegin',new_object);
            object.parentNode.removeChild(object);
        }
    }
}
