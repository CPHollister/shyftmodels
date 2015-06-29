/*
 * @package WP Simple Booking Calendar
 *
 * Copyright (c) 2011 WP Simple Booking Calendar
 */

jQuery(function($) {
	
	$('div.sbc-calendar-wrapper').each(function() {
		var $calendar = $(this);
		
		function getCurrentMonth() {
			return $('.sbc-navigation select[name="sbcMonth"]', $calendar).val();
		}
		
		function getCurrentYear() {
			return $('.sbc-navigation select[name="sbcYear"]', $calendar).val();
		}
		
		function ajaxCalendarUpdate(operation) {
			var ajaxUrl = $('form', $calendar).attr('action');
			var data = {
				action: 'calendarNavigation',
				operation: operation,
				month: getCurrentMonth(),
				year: getCurrentYear(),
				id: $('form input[name="sbcId"]', $calendar).val()
			};
			$('div.sbc-loader', $calendar).addClass('sbc-loader-visible');
			$.post(ajaxUrl, data, function(response) {
				$calendar.find('#sbc-calendar').replaceWith(response);
				$('.sbc-navigation select', $calendar).bind('change', changeMonthOrYear);
			});
		}
		
		// Prev/next month
		$($calendar).on('click','a.sbc-prev-month, a.sbc-next-month', function(event) {
			event.preventDefault();
			ajaxCalendarUpdate($(this).is('.sbc-prev-month') ? 'prevMonth' : 'nextMonth');
		});
		
		// Custom month/year
		function changeMonthOrYear () {
			ajaxCalendarUpdate('date');
		}
		$('.sbc-navigation select', $calendar).bind('change', changeMonthOrYear);
	});
	
});