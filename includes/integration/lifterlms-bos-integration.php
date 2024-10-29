<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class LifterLMS_BOS_Integration
 */
class LifterLMS_BOS_Integration {

    /**
	 * BadgeOS lifterlms Triggers
	 *
	 * @var array
	 */
	public $triggers = array();

	/**
	 * Actions to forward for splitting an action up
	 *
	 * @var array
	 */
	public $actions = array();


    /**
     * LifterLMS_BOS_Integration constructor.
     */
    public function __construct() {

        /**
         * lifterlms Action Hooks
         */
		$this->triggers = array(
            'lifterlms_created_person' => __( 'Create a new account', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'llms_user_enrolled_in_course' => __( 'Enroll in a course', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'lifterlms_course_completed' => __( 'Complete a course', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'badgeos_lifterlms_course_completed_cat' => __( 'Complete a course from category', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'badgeos_lifterlms_course_completed_difficulty' => __( 'Complete a course from difficulty', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'badgeos_lifterlms_course_completed_tag' => __( 'Complete a course from tag', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'lifterlms_section_completed' => __( 'Complete a section', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'lifterlms_lesson_completed' => __( 'Complete a lesson', LIFTERLMS_BOS_TEXT_DOMAIN ),
			'lifterlms_quiz_completed' => __( 'Complete a quiz', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'lifterlms_course_track_completed' => __( 'Complete a track', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'lifterlms_quiz_passed' => __( 'Passed in a quiz attempt', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'lifterlms_quiz_failed' => __( 'Failed in a quiz attempt', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'badgeos_lifterlms_quiz_completed_specific' => __( 'Get a minimum percent/grade in a quiz', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'badgeos_lifterlms_course_purchased' => __( 'Purchase a course', LIFTERLMS_BOS_TEXT_DOMAIN ),
			'badgeos_lifterlms_membership_purchased' => __( 'Purchase a membership', LIFTERLMS_BOS_TEXT_DOMAIN ),
			'lifterlms_access_plan_purchased' => __( 'Purchase access plan', LIFTERLMS_BOS_TEXT_DOMAIN ),
			'llms_user_added_to_membership_level' => __( 'Added to the membership level', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'badgeos_lifterlms_user_added_to_membership_level_cat' => __( 'Added to the membership level from category', LIFTERLMS_BOS_TEXT_DOMAIN ),
            'badgeos_lifterlms_user_added_to_membership_level_tag' => __( 'Added to the membership level from tag', LIFTERLMS_BOS_TEXT_DOMAIN ),
		);

		/**
         * Actions that we need split up
         */
		$this->actions = array(
			'lifterlms_quiz_completed' =>  'badgeos_lifterlms_quiz_completed_specific',
			'lifterlms_product_purchased' => array(
				'actions' => array(
					'badgeos_lifterlms_membership_purchased',
					'badgeos_lifterlms_course_purchased',
				)
			),
			'llms_user_added_to_membership_level' => array(
				'actions' => array(
					'badgeos_lifterlms_user_added_to_membership_level_tag',
					'badgeos_lifterlms_user_added_to_membership_level_cat',
				)
			),
			'lifterlms_course_completed' => array(
				'actions' => array(
					'badgeos_lifterlms_course_completed_tag',
					'badgeos_lifterlms_course_completed_cat',
					'badgeos_lifterlms_course_completed_difficulty'
				)
			)
        );
        
        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 11 );

    }

    /**
     * include files if plugin meets requirements
     */
	public function plugins_loaded() {


		if ( $this->meets_requirements() ) {

			if( file_exists( LLMS_PLUGIN_DIR . 'includes/class.llms.quiz.data.php' ) ) {
				require_once ( LLMS_PLUGIN_DIR . 'includes/class.llms.quiz.data.php' );
			}

			if( file_exists( LLMS_PLUGIN_DIR . 'includes/class-llms-grades.php' ) ) {
				require_once ( LLMS_PLUGIN_DIR . 'includes/class-llms-grades.php' );
			}

			if( file_exists( LLMS_PLUGIN_DIR . 'includes/models/model.llms.quiz.attempt.php' ) ) {
				require_once ( LLMS_PLUGIN_DIR . 'includes/models/model.llms.quiz.attempt.php' );
			}

			if( file_exists( LLMS_PLUGIN_DIR . 'includes/models/model.llms.student.quizzes.php' ) ) {
				require_once ( LLMS_PLUGIN_DIR . 'includes/models/model.llms.student.quizzes.php' );
			}

			if( file_exists( LLMS_PLUGIN_DIR . 'includes/models/model.llms.product.php' ) ) {
				require_once ( LLMS_PLUGIN_DIR . 'includes/models/model.llms.product.php' );
			}

			if( file_exists( LLMS_PLUGIN_DIR . 'includes/models/model.llms.access.plan.php' ) ) {
				require_once ( LLMS_PLUGIN_DIR . 'includes/models/model.llms.access.plan.php' );
			}

			if( 'yes' === get_option( 'llms_integration_badge_OS_enabled', 'no' ) ) {
				
				if( file_exists( LIFTERLMS_BOS_INCLUDES_DIR . 'integration/rules-engine.php' ) ) {
					require_once ( LIFTERLMS_BOS_INCLUDES_DIR . 'integration/rules-engine.php' );
				}
	
				if( file_exists( LIFTERLMS_BOS_INCLUDES_DIR . 'integration/steps-ui.php' ) ) {
					require_once ( LIFTERLMS_BOS_INCLUDES_DIR . 'integration/steps-ui.php' );
				}
				
			}

			if( file_exists( LIFTERLMS_BOS_INCLUDES_DIR . 'integration/lifterlms-bos-settings.php' ) ) {
				require_once ( LIFTERLMS_BOS_INCLUDES_DIR . 'integration/lifterlms-bos-settings.php' );
			}

			if( file_exists( LIFTERLMS_BOS_INCLUDES_DIR . 'integration/lifterlms-bos-allow-integration.php' ) ) {
				require_once ( LIFTERLMS_BOS_INCLUDES_DIR . 'integration/lifterlms-bos-allow-integration.php' );
			}

			$this->action_forwarding();
		}

    }
    
    /**
     * Check if BadgeOS is available
     *
     * @return bool
     */
	public static function meets_requirements() {

		if ( !class_exists( 'BadgeOS' ) || !function_exists( 'badgeos_get_user_earned_achievement_types' ) ) {

			return false;
		} elseif ( !class_exists( 'LifterLMS' ) ) {

			return false;
		}

		return true;
	}

    /**
     * Forward WP actions into a new set of actions
     */
	public function action_forwarding() {
		foreach ( $this->actions as $action => $args ) {
			$priority = 10;
			$accepted_args = 20;

			if ( is_array( $args ) ) {
				if ( isset( $args[ 'priority' ] ) ) {
					$priority = $args[ 'priority' ];
				}

				if ( isset( $args[ 'accepted_args' ] ) ) {
					$accepted_args = $args[ 'accepted_args' ];
				}
			}

			add_action( $action, array( $this, 'action_forward' ), $priority, $accepted_args );
		}
	}

    /**
     * Forward a specific WP action into a new set of actions
     *
     * @return mixed|null
     */
	public function action_forward() {
		$action = current_filter();
		$args = func_get_args();
		$action_args = array();

		if ( isset( $this->actions[ $action ] ) ) {
			if ( is_array( $this->actions[ $action ] )
				 && isset( $this->actions[ $action ][ 'actions' ] ) && is_array( $this->actions[ $action ][ 'actions' ] )
				 && !empty( $this->actions[ $action ][ 'actions' ] ) ) {
				foreach ( $this->actions[ $action ][ 'actions' ] as $new_action ) {
			
					$action_args = $args;

					array_unshift( $action_args, $new_action );

					call_user_func_array( 'do_action', $action_args );
				}

				return null;
			} elseif ( is_string( $this->actions[ $action ] ) ) {
				$action =  $this->actions[ $action ];
			}
		}
		array_unshift( $args, $action );

		return call_user_func_array( 'do_action', $args );
	}
}

/**
 * Initiate plugin main class
 */
$GLOBALS['badgeos_lifterlms'] = new LifterLMS_BOS_Integration();
