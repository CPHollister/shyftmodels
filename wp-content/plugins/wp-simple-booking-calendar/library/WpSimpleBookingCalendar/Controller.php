<?php
/**
 * @package WP Simple Booking Calendar
 *
 * Copyright (c) 2011 WP Simple Booking Calendar
 */

/**
 * WP Simple Booking Calendar controller class
 */
class WpSimpleBookingCalendar_Controller
{
	/**
	 * Controller hook
	 */
	const HOOK = 'wp-simple-booking-calendar';
	
	/**
	 * The list of supported actions
	 * @var array
	 */
	protected static $_supportedActions = array('index', 'add', 'edit', 'delete');
	
	/**
	 * Controller name
	 * @var string
	 */
	protected $_displayName;
	
	/**
	 * View object
	 * @var WpSimpleBookingCalendar_View
	 */
	protected $_view = null;
	
	/**
	 * Model object
	 * @var WpSimpleBookingCalendar_Model
	 */
	protected $_model = null;
	
	/**
	 * Model class
	 * @var string
	 */
	protected $_modelClass;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_view = new WpSimpleBookingCalendar_View();
		$this->_model = new WpSimpleBookingCalendar_Model();
		
		$adminPage = add_menu_page(__('WP Simple Booking Calendar', 'sbc'), __('WP Simple Booking Calendar', 'sbc'), 'manage_options', self::HOOK, array($this, 'render'));
		add_action('admin_print_scripts-' . $adminPage, array($this, 'enqueueAdminScripts'));
		add_action('admin_print_styles-' . $adminPage, array($this, 'enqueueAdminStyles'));
	}
	
	/**
	 * Enqueues admin javascripts
	 * @return void
	 */
	public function enqueueAdminScripts()
	{
		wp_enqueue_script('postbox');
		wp_enqueue_script('dashboard');
		wp_enqueue_script('jquery-ui-draggable');
		wp_enqueue_script('jquery-ui-droppable');
		wp_enqueue_script('json2');
		wp_enqueue_script('jquery.form', SBC_DIR_URL . 'js/jquery.form.min.js', array('jquery'));
		wp_enqueue_script('jquery.validate', SBC_DIR_URL . 'js/jquery.validate.min.js', array('jquery.form'));
		wp_enqueue_script(self::HOOK . '-controller', SBC_DIR_URL . 'js/sbc-controller.js', array('json2', 'jquery.validate'));
	}
	
	/**
	 * Enqueues admin CSS files
	 * @return void
	 */
	public function enqueueAdminStyles()
	{
		wp_enqueue_style(self::HOOK, SBC_DIR_URL . 'css/sbc.css');
		wp_enqueue_style(self::HOOK . '-controller', SBC_DIR_URL . 'css/sbc-controller.css');
	}
	
	/**
	 * Returns controller url
	 * @return string
	 */
	public function getControllerUrl()
	{
		return esc_url(admin_url('admin.php?page=' . self::HOOK));
	}
	
	/**
	 * Validates and returns current controller action
	 * @return string
	 */
	public function getCurrentAction()
	{
		$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
		if (!in_array($action, self::$_supportedActions))
		{
			$action = self::$_supportedActions[0];
		}
		return $action;
	}
	
	/**
	 * Renders controller action
	 * @return void
	 */
	public function render()
	{
		$actionMethod = $this->getCurrentAction() . 'Action';
		$this->$actionMethod();
	}
	
	/**
	 * Prepares form data for indexAction & editAction
	 * @return array
	 */
	protected function _processFormData()
	{
		$calendarName = filter_input(INPUT_POST, 'calendarName', FILTER_SANITIZE_STRING);
		
		// Filter calendar data
		$calendarData = json_decode(filter_input(INPUT_POST, 'calendarData'), true);
		if (is_array($calendarData))
		{
			ksort($calendarData);
			
			// Process each year
			foreach ($calendarData as $year => $months)
			{
				ksort($calendarData[$year]);
				
				// Process each month
				if (is_array($months))
				{
					foreach ($months as $month => $days)
					{
						ksort($calendarData[$year][$month]);
						
						// Process each day
						if (is_array($days))
						{
							foreach ($days as $day => $bookingStatus)
							{
								if (!in_array($bookingStatus, array('booked', 'changeover')))
								{
									unset($calendarData[$year][$month][$day]);
								}
							}
						}
						
						if (empty($calendarData[$year][$month]))
						{
							unset($calendarData[$year][$month]);
						}
					}
				}
				
				if (empty($calendarData[$year]))
				{
					unset($calendarData[$year]);
				}
			}
		}
		else
		{
			$calendarData = array();
		}
		
		return array (
			'calendarName' => $calendarName,
			'calendarJson' => json_encode($calendarData)
		);
	}
	
	/**
	 * Generate Nonce to protect users from CSRF attacks
	 * 
	 * @param string $action Name of the action
	 * @param integer $id Optional identifier
	 * @return string
	 */
	protected function _generateNonceAction($action, $id = 0) {
		$pieces = array(self::HOOK, $action);
		if ($id !== 0) {
			array_push($pieces, $id);
		}
		return implode('-', $pieces);
	}
	
	/**
	 * Action: list of calendars
	 * @return void
	 */
	public function indexAction()
	{
		$searchQuery = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
		$addControllerUrl = wp_nonce_url( $this->getControllerUrl() . '&action=add', $this->_generateNonceAction('add') );
		$editControllerUrl = wp_nonce_url( $this->getControllerUrl() . '&action=edit', $this->_generateNonceAction('edit') );
		$deleteControllerUrl = wp_nonce_url( $this->getControllerUrl() . '&action=delete', $this->_generateNonceAction('delete') );
		
		$this->_view->setTemplate('controller/index')
			->assign('addControllerUrl', $addControllerUrl)
			->assign('editControllerUrl', $editControllerUrl)
			->assign('deleteControllerUrl', $deleteControllerUrl)
			->assign('calendar', $this->_model->getCalendar())
			->assign('dateFormat', get_option('date_format'))
			->assign('timeFormat', get_option('time_format'))
			->assign('searchQuery', $searchQuery)
			->render();
	}
	
	/**
	 * Action: new calendar
	 * @return void
	 */
	public function addAction()
	{
		$formData = $this->_processFormData();
		$nonceAction = $this->_generateNonceAction('add');
		check_admin_referer( $nonceAction );
		
		if (!empty($_POST['_wpnonce']))
		{
			if (wp_verify_nonce( $_POST['_wpnonce'], $nonceAction) && $this->_model->insertCalendar($formData))
			{
				$this->_view->messageHelper(__('Calendar Added', 'sbc'));
				$this->indexAction();
				return;
			}
			else
			{
				$this->_view->messageHelper(__('Failed to add new calendar', 'sbc'));
			}
		}
		
		$this->_view->setTemplate('controller/edit')
			->assign('controllerUrl', $this->getControllerUrl())
			->assign('calendarName', $formData['calendarName'])
			->assign('calendarData', json_decode($formData['calendarJson']))
			->assign('actionName', __('Add New Calendar', 'sbc'))
			->assign('nonceAction', $nonceAction)
			->render();
	}
	
	/**
	 * Action: edit calendar
	 * @return void
	 */
	public function editAction()
	{
		$nonceAction = $this->_generateNonceAction('edit');
		check_admin_referer( $nonceAction );
		
		if (!empty($_POST['_wpnonce']))
		{
			$formData = $this->_processFormData();
			if (wp_verify_nonce( $_POST['_wpnonce'], $nonceAction) && $this->_model->updateCalendar($formData))
			{
				$this->_view->messageHelper(__('Calendar Updated', 'sbc'));
				$this->indexAction();
				return;
			}
			else
			{
				$this->_view->messageHelper(__('Failed to update calendar', 'sbc'));
			}
		}
		else
		{
			$formData = $this->_model->getCalendar();
			if (!$formData)
			{
				$this->_view->messageHelper(__('No calendar found', 'sbc'));
				$this->indexAction();
				return;
			}
		}
		
		$this->_view->setTemplate('controller/edit')
			->assign('controllerUrl', $this->getControllerUrl())
			->assign('calendarName', $formData['calendarName'])
			->assign('calendarData', json_decode($formData['calendarJson']))
			->assign('actionName', __('Edit Calendar', 'sbc'))
			->assign('nonceAction', $nonceAction)
			->render();
	}
	
	/**
	 * Action: delete calendar
	 * @return void
	 */
	public function deleteAction()
	{
		$nonceAction = $this->_generateNonceAction('delete');
		check_admin_referer( $nonceAction );
		
		if (!isset($_GET['_wpnonce']) || !wp_verify_nonce( $_GET['_wpnonce'], $nonceAction) || !$this->_model->getCalendar())
		{
			$message = __('No calendar found', 'sbc');
		}
		elseif ($this->_model->deleteCalendar())
		{
			$message = __('Calendar Removed', 'sbc');
		}
		else
		{
			$message = __('Unable to delete calendar', 'sbc');
		}
		
		$this->_view->messageHelper($message);
		$this->indexAction();
	}
}