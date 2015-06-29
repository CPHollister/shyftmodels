<?php
/**
 * @package WP Simple Booking Calendar
 *
 * Copyright (c) 2011 WP Simple Booking Calendar
 */

/**
 * WP Simple Booking Calendar widget class
 */
class WpSimpleBookingCalendar_Widget extends WP_Widget
{
	/**
	 * List of supported parameters
	 * @var array
	 */
	protected $_supportedParams = array('id', 'title');
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$widgetOptions = array('classname' => 'widget-sbc', 'description' => __('Use this widget to add booking calendar to the sidebar', 'sbc'));
		parent::__construct(false, __('WP Simple Booking Calendar', 'sbc'), $widgetOptions);
	}
	
	/**
	 * Merge user defined arguments into defaults array
	 * @param string|array $args Value to merge with defaults
	 * @return array Merged user defined values with defaults
	 */
	protected function _parseArgs($args)
	{
		$defaults = array('title' => 'yes');
		foreach ($this->_supportedParams as $key)
		{
			$defaults[$key] = (isset($this->_defaults[$key]) ? $this->_defaults[$key] : '');
		}
		
		return wp_parse_args((array) $args, $defaults);
	}
	
	/**
	 * Processes widget options to be saved
	 * @param array $newInstance New settings for this instance as input by the user via form()
	 * @param array $oldInstance Old settings for this instance
	 * @return array Settings to save or bool false to cancel saving
	 */
	public function update($newInstance, $oldInstance)
	{
		$instance = $oldInstance;
		$newInstance = $this->_parseArgs($newInstance);
		
		foreach ($this->_supportedParams as $name)
		{
			$instance[$name] = strip_tags($newInstance[$name]);
		}
		
		return $instance;
	}
	
	/**
	 * Outputs the options form on admin
	 * @param array $instance Current settings
	 * @return void
	 */
	public function form($instance)
	{
		$view = new WpSimpleBookingCalendar_View();
		$model = new WpSimpleBookingCalendar_Model();
		
		$view->setTemplate('widget/form')
			->assign('widget', $this)
			->assign('settings', $this->_parseArgs($instance))
			->assign('calendar', $model->getCalendar())
			->render();
	}
	
	/**
	 * Outputs the content of the widget
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 * @return string The content of the widget
	 */
	public function widget($args, $instance)
	{
		$settings = array();
		foreach ($this->_supportedParams as $name)
		{
			if (!empty($instance[$name]))
			{
				$settings[$name] = apply_filters('widget_' . $name, $instance[$name]);
			}
		}
		
		$settings = WpSimpleBookingCalendar_Shortcode::validateAttributes($settings);
		
		// View setup
		$view = new WpSimpleBookingCalendar_View();
		$model = new WpSimpleBookingCalendar_Model();
		
		$view->setTemplate('widget/widget')
			->assign('widget', $this)
			->assign('showTitle', $settings['title'])
			->assign('calendar', $model->getCalendar())
			->assign('args', $args)
			->render();
	}
}