<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage settings forms on the LifterLMS Integrations Settings Page
 */
class LLMS_BOS_Settings {

	/**
	 * Constructor
	 */
	public function __construct() { 

		add_filter( 'lifterlms_integrations_settings_badge_OS', array( $this, 'integration_settings' ) );
		add_action( 'lifterlms_settings_save_integrations', array( $this, 'save' ), 10 );

	}

	/**
	 * This function adds the appropriate content to the array that makes up the settings page.
	 *
	 * @param    $content
	 * @return   array
	 */
	public function integration_settings( $content ) {

		$content[] = array(
			'type' => 'sectionstart',
			'id' => 'lifterlms_bos_options',
			'class' =>'top'
		);

		$content[] = array(
			'title' => __( 'BadgeOS Settings', LIFTERLMS_BOS_TEXT_DOMAIN ),
			'type' => 'title',
			'desc' => '',
			'id' => 'lifterlms_bos_options'
		);

		$content[] = array(
			'desc' 		=> __( 'Use BadgeOS to award badges on LifterLMS activities', LIFTERLMS_BOS_TEXT_DOMAIN ),
			'default'	=> 'no',
			'id' 		=> 'llms_integration_badge_OS_enabled',
			'type' 		=> 'checkbox',
			'title'     => __( 'Enable / Disable', LIFTERLMS_BOS_TEXT_DOMAIN ),
        );
        
       
	
		$options = array('No','quiz score if passed','quiz score anyway');

		$content[] = array(
			'class'     => 'llms-select2',
			'desc' 		=> '<br>' . __( 'If enabled it will award LifterLMS Quiz Points as BadgeOS points.', LIFTERLMS_BOS_TEXT_DOMAIN ),
			'default'	=> 'No',
			'id' 		=> 'lifterlms_badge_OS_quiz_points_enable',
			'options'   => $options,
			'type' 		=> 'select',
			'title'     => __( 'LifterLMS Quiz Score as BadgeOS Points:', LIFTERLMS_BOS_TEXT_DOMAIN ),
		);

		$content[] = array(
			'desc' 		=>'<br>' . __('This option will multiple this score with the quiz completed score and will add to the BadgeOS points', LIFTERLMS_BOS_TEXT_DOMAIN ),
			'default'	=> 1,
			'id' 		=> 'lifterlms_badge_OS_quiz_points',
			'type' 		=> 'number',
			'title'     => __( 'Quiz Score Multiplier:', LIFTERLMS_BOS_TEXT_DOMAIN ),
        );

		$content[] = array(
			'type' => 'sectionend',
			'id' => 'lifterlms_bos_options'
		);

		return $content;

	}

	/**
	 * Flush rewrite rules when saving settings
	 * 
	 * @return   void
	 */
	public function save() {

		$integration = LLMS()->integrations()->get_integration( 'badge_OS' );
		
		if ( $integration && $integration->is_available() ) {
			flush_rewrite_rules();
		}
		
	}

}

return new LLMS_BOS_Settings();