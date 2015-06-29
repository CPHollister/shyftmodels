<?php
/**
 * @package WP Simple Booking Calendar
 *
 * Copyright (c) 2010 WP Simple Booking Calendar
 */

/**
 * WP Simple Booking Calendar ajax class
 */
class WpSimpleBookingCalendar_Ajax
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		add_action('wp_ajax_calendarNavigation', array($this, 'calendarNavigation'));
		add_action('wp_ajax_nopriv_calendarNavigation', array($this, 'calendarNavigation'));
	}
	
	/**
	 * Ajax navigation (next/prev month)
	 * @return void
	 */
	public function calendarNavigation()
	{
		$currentTimestamp = current_time('timestamp');
		$currentYear = (int) gmdate('Y', $currentTimestamp);
		$currentMonth = (int) gmdate('m', $currentTimestamp);
		
		// Prepare variables
		$inputMonth = filter_input(INPUT_POST, 'month', FILTER_VALIDATE_INT, array('options' => array(
			'default' => $currentMonth,
			'min_range' => 1,
			'max_range' => 12
		)));
		$inputYear = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT, array('options' => array(
			'default' => $currentYear,
			'min_range' => $currentYear,
			'max_range' => $currentYear + 15
		)));
        
		
		$operation = filter_input(INPUT_POST, 'operation', FILTER_SANITIZE_STRING);
        
        if (in_array($operation, array('nextMonth', 'prevMonth')))
		{
			// Additional validation
			if ($operation == 'nextMonth' && $inputMonth == 12 && $inputYear >= $currentYear + 15)
			{
				$inputYear == $currentYear + 14;
			}
			if ($operation == 'prevMonth' && $inputMonth == 1 && $inputYear <= $currentYear)
			{
				$inputYear = $currentYear + 1;
			}
			
			$newTimestamp = strtotime(($operation == 'nextMonth' ? '+' : '-') . '1 month',
				gmmktime(1, 1, 1, $inputMonth, 1, $inputYear));
		}
		else
		{
			$newTimestamp = gmmktime(null, null, null, $inputMonth, 1, $inputYear);
		}
		
		// Prepare calendar data
		$calendarData = filter_input(INPUT_POST, 'calendarData');
        
        
		if (!empty($calendarData))
		{
			$includeCalendarEditor = true;
		}
		else
		{
			$includeCalendarEditor = false;
			$model = new WpSimpleBookingCalendar_Model();
			$calendar = $model->getCalendar();
			$calendarData = (isset($calendar['calendarJson']) ? $calendar['calendarJson'] : null);
		}
		
		// Disable cache
		header('Cache-Control: no-cache', true);
		header('Pragma: no-cache', true);
        
        
		// Render calendar
		$view = new WpSimpleBookingCalendar_View();
		$view->setTemplate('calendar/calendar')
			->assign('timestamp', $newTimestamp)
			->assign('calendarData', json_decode($calendarData))
			->assign('includeCalendarEditor', $includeCalendarEditor)
			->render();
		
		exit;
	}
}