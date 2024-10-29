<?php
defined( 'ABSPATH' ) || exit;

/**
 * LifterLMS BadgeOS Integration Class
 */
class LifterLMS_BOS_Allow_Integration extends LLMS_Abstract_Integration {

	public $id = 'badge_OS';
	public $title = '';
	protected $priority = 5; 

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->title = __( 'BadgeOS', LIFTERLMS_BOS_TEXT_DOMAIN );
		$this->description = sprintf( __( 'Restrict triggering BadgeOS badges on LifterLMS activities', LIFTERLMS_BOS_TEXT_DOMAIN ), '<a href="https://lifterlms.com/docs/lifterlms-and-bbpress/" target="_blank">', '</a>' );
		
	}
	
	/**
	 * Integration Configuration
	 */
	public function configure() {
	
		$this->title = __( 'BadgeOS Options', LIFTERLMS_BOS_TEXT_DOMAIN );
		$this->description = sprintf( __( 'Restrict triggering BadgeOS badges on LifterLMS activities', 'lifterlms' ), '<a href="https://lifterlms.com/docs/lifterlms-and-bbpress/" target="_blank">', '</a>' );
		
	}
	
}

new LifterLMS_BOS_Allow_Integration();