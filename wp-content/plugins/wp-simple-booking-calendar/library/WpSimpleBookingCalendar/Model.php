<?php
/**
 * @package WP Simple Booking Calendar
 *
 * Copyright (c) 2011 WP Simple Booking Calendar
 */

/**
 * WP Simple Booking Calendar model class
 */
class WpSimpleBookingCalendar_Model
{
	/**
	 * The lookup key used to locate the options record in the wp_options table
	 */
	const OPTIONS_KEY = 'wp-simple-booking-calendar-options';
	
	/**
	 * Options array
	 * @var array
	 */
	protected $_options = array();
	
	/**
	 * Performs initializion of the options structure
	 */
	public function __construct()
	{
		$options = get_option(self::OPTIONS_KEY);
		
		if (false === $options)
		{
			$options = array();
		}
		
		$this->_options = $options;
	}
	
	/**
	 * Updates the option identified by $name with the value provided in $value
	 * @param string $name The option name
	 * @param mixed $value The option value
	 * @return WpSimpleBookingCalendar_Model
	 */
	public function setOption($name, $value)
	{
		$this->_options[$name] = $value;
		return $this;
	}
	
	/**
	 * Returns a value of the option identified by $name
	 * @param string $name The option name
	 * @return mixed|null
	 */
	public function getOption($name)
	{
		$value = array_key_exists($name, $this->_options) ? $this->_options[$name] : null;
		return $value;
	}
	
	/**
	 * Saves the internal options data to the wp_options table
	 * @return boolean
	 */
	public function save()
	{
		return update_option(self::OPTIONS_KEY, $this->_options);
	}
	
    
    /**
	 * Return calendar data
	 * @return mixed
	 */
	public function getCalendar()
	{
		$calendar = $this->getOption('calendars');
        if (isset($calendar[1])) {
            $calendar = $calendar[1];
        }
		return @(array_key_exists('calendarName', $calendar) ? $calendar : null);
	}
	
	
	/**
	 * Insert new calendar
	 * @param array $data
	 * @return boolean
	 */
	public function insertCalendar($data)
	{
	    $oldCalendar = $this->getCalendar();
        if ($oldCalendar != null || isset($oldCalendar['calendarName']))
        {
            return false;
        }
        
		$newCalendar = array(
			'calendarName' => $data['calendarName'],
			'calendarJson' => $data['calendarJson'],
			'dateCreated' => time(),
			'dateModified' => time()
		);
		
        $newCalendar = array(
            1 => $newCalendar
        );
		return $this->setOption('calendars', $newCalendar)->save();
	}
	
	/**
	 * Update calendar
	 * @param array $data
	 * @return boolean
	 */
	public function updateCalendar($data)
	{
		$oldCalendar = $this->getCalendar();
		
		$updatedCalendar = array(
			'calendarName' => $data['calendarName'],
			'calendarJson' => $data['calendarJson'],
			'dateModified' => time()
		);
        
		if ($oldCalendar == null || !isset($oldCalendar['calendarName']))
		{
			return $this->insertCalendar($data);
		}
        
        $newData = array_merge($oldCalendar, $updatedCalendar);

        return $this->setOption('calendars', array(1 => $newData))->save();

	}
	
	/**
	 * Delete calendar
	 * @return void
	 */
	public function deleteCalendar()
	{
		return $this->setOption('calendars', null)->save();
	}
}