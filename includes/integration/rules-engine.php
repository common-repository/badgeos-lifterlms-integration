<?php
/**
 * Custom Rules
 *
 * @package BadgeOS LifterLMS Pro
 * @author WooNinjas
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://wooninjas.com
 */

/**
 * Load up our lifterlms triggers so we can add actions to them
 */
function badgeos_lifterlms_load_triggers() {

	/**
     * Grab our lifterlms triggers
     */
	$lifterlms_triggers = $GLOBALS[ 'badgeos_lifterlms' ]->triggers;

	if ( !empty( $lifterlms_triggers ) ) {
		foreach ( $lifterlms_triggers as $trigger => $trigger_label ) {
			
			if ( is_array( $trigger_label ) ) {
				$triggers = $trigger_label;

				foreach ( $triggers as $trigger_hook => $trigger_name ) {
					add_action( $trigger_hook, 'badgeos_lifterlms_trigger_event', 0, 20 );
				}
			} else {
				add_action( $trigger, 'badgeos_lifterlms_trigger_event', 0, 20 );
			}
		}
	}
}

add_action( 'init', 'badgeos_lifterlms_load_triggers', 0 );

/**
 * Handle each of our lifterlms triggers
 */
function badgeos_lifterlms_trigger_event() {

    /**
     * Setup all our important variables
     */
	global $blog_id, $wpdb;

	/**
     * Setup args
     */
	$args = func_get_args();

	$userID = null;

	if ( is_array( $args ) && isset( $args[ 'user' ] ) ) {
		if ( is_object( $args[ 'user' ] ) ) {
			$userID = (int) $args[ 'user' ]->ID;
		} else {
			$userID = (int) $args[ 'user' ];
		}
	}

	if(empty($userID)) {
        list($userID, $object_id) = $args;
    }

    if( empty($userID) ) {
        $userID = get_current_user_id();
    }

	/**
     * Grab the current trigger
     */
	$this_trigger = current_filter();

	if ( empty( $userID ) ) {
		return;
	}

	$user_data = get_user_by( 'id', $userID );

	if ( empty( $user_data ) ) {
		return;
	}

	/**
     * Now determine if any badges are earned based on this trigger event 
     */

	$triggered_achievements = $wpdb->get_results( $wpdb->prepare( "SELECT pm.post_id, p.post_type FROM $wpdb->postmeta as pm inner join $wpdb->posts as p on( pm.post_id = p.ID ) WHERE p.post_status = 'publish' and pm.meta_key = '_badgeos_lifterlms_trigger' AND pm.meta_value = %s", $this_trigger) );

	$GLOBALS['badgeos']->achievement_types[] =  'step';
	
	if( count( $triggered_achievements ) > 0 ) {
		/**
		 * Update hook count for this user
		 */
		$new_count = badgeos_update_user_trigger_count( $userID, $this_trigger, $blog_id );

		/**
		 * Mark the count in the log entry
		 */
		badgeos_post_log_entry( null, $userID, null, sprintf( __( '%1$s triggered %2$s (%3$dx)', LIFTERLMS_BOS_TEXT_DOMAIN ), $user_data->user_login, $this_trigger, $new_count ) );
    }

	foreach ( $triggered_achievements as $achievement ) {

	    $parents = badgeos_get_achievements( array( 'parent_of' => $achievement->post_id ) );
	    if( count( $parents ) > 0 ) {
            if( $parents[0]->post_status == 'publish' ) {
				badgeos_maybe_award_achievement_to_user( $achievement->post_id, $userID, $this_trigger, $blog_id, $args );
			}
		}

		//Rank
        $rank = $achievement;
        $parent_id = badgeos_get_parent_id( $rank->post_id );
        if( absint($parent_id) > 0) {
            $new_count = badgeos_ranks_update_user_trigger_count( $rank->post_id, $parent_id,$userID, $this_trigger, $blog_id, $args );
            badgeos_maybe_award_rank( $rank->post_id,$parent_id,$userID, $this_trigger, $blog_id, $args );
        }

		//Point
        $point = $achievement;
        $parent_id = badgeos_get_parent_id( $point->post_id );
        if( absint($parent_id) > 0) {
            if($point->post_type == 'point_award') {
                $new_count = badgeos_points_update_user_trigger_count($point->post_id, $parent_id, $userID, $this_trigger, $blog_id, 'Award', $args);
                badgeos_maybe_award_points_to_user($point->post_id, $parent_id, $userID, $this_trigger, $blog_id, $args);
            } else if($point->post_type == 'point_deduct') {
                $new_count = badgeos_points_update_user_trigger_count($point->post_id, $parent_id, $userID, $this_trigger, $blog_id, 'Deduct', $args);
                badgeos_maybe_deduct_points_to_user($point->post_id, $parent_id, $userID, $this_trigger, $blog_id, $args);
            }
        }

	}
}

/**
 * Award lifterlms Quiz Points as Badge Points.
 *
 * @param $quizdata
 * @param $user
 */
function badgeos_lifterlms_award_quiz_points_as_badge_points( $user_id, $quiz_id ) {

	$total_points = 0;
	if( get_option( 'lifterlms_badge_OS_quiz_points_enable' , 'No' ) == 0 ) {
        return;
	}
	
	$quizez = new LLMS_Student_Quizzes( $user_id );
	$attempt = $quizez->get_last_attempt( $quiz_id  );
	foreach ( $attempt->get_question_objects() as $attempt_question  ) {
			$total_points = $total_points + intval( $attempt_question->get( 'earned' ) );
		}

    if( get_option( 'lifterlms_badge_OS_quiz_points_enable', 'No' ) == 1  && !in_array( $attempt->get( 'status' ), array( 'pass' ) ) ) {
        return;
	}
	$quiz_score_multiplier = intval( get_option( 'lifterlms_badge_OS_quiz_points', 1) ) ? intval( get_option( 'lifterlms_badge_OS_quiz_points', 1) ) : 1;

	/**
     * Get Quiz Total Points & Current USER ID
     */
 
	if( $quiz_score_multiplier > 0 )
		$total_points = $total_points * $quiz_score_multiplier;

	/**
     * Award lifterlms Quiz Points to User
     */
	if( intval( $total_points ) > 0 )
		badgeos_update_users_points( $user_id, $total_points );
}

add_action( 'lifterlms_quiz_completed', 'badgeos_lifterlms_award_quiz_points_as_badge_points', 10, 2 );

/**
 * Check if user deserves a lifterlms trigger step
 *
 * @param $return
 * @param $user_id
 * @param $achievement_id
 * @param string $this_trigger
 * @param int $site_id
 * @param array $args
 * @return bool
 */
function badgeos_lifterlms_user_deserves_lifterlms_step( $return, $user_id, $achievement_id, $this_trigger = '', $site_id = 1, $args = array() ) {

    /**
     * If we're not dealing with a step, bail here
     */

    $post_type = get_post_type( $achievement_id );
	if ( 'step' !=  $post_type ) {

	    //TODO: Investigate why below 2 types inserted in achievements table, when same LifterLMS trigger is assigned to achievements and points
	    if( in_array( $post_type, array( 'point_award', 'point_type' ) ) ) {
		    $return = false;
        }

	    return $return;
	}

	/**
     * Grab our step requirements
     */
	$requirements = badgeos_get_step_requirements( $achievement_id );

	/**
     * If the step is triggered by lifterlms actions...
     */
	if ( 'lifterlms_trigger' == $requirements[ 'trigger_type' ] ) {

	    /**
         * Do not pass go until we say you can
         */
		$return = false;

		/**
         * Unsupported trigger
         */
		if ( ! isset( $GLOBALS[ 'badgeos_lifterlms' ]->triggers[ $this_trigger ] ) ) {
			return $return;
		}

        $lifterlms_triggered = is_lifter_lms_trigger($requirements, $args);

		/**
         * lifterlms requirements met
         */
		if ( $lifterlms_triggered ) {

			$parent_achievement = badgeos_get_parent_of_achievement( $achievement_id );
			$parent_id = $parent_achievement->ID;

			$user_crossed_max_allowed_earnings = badgeos_achievement_user_exceeded_max_earnings( $user_id, $parent_id );
			if ( ! $user_crossed_max_allowed_earnings ) {
				$minimum_activity_count = absint( get_post_meta( $achievement_id, '_badgeos_count', true ) );
				if( ! isset( $minimum_activity_count ) || empty( $minimum_activity_count ) )
					$minimum_activity_count = 1;

				$count_step_trigger = $requirements["lifterlms_trigger"];
				$activities = badgeos_get_user_trigger_count( $user_id, $count_step_trigger );
				$relevant_count = absint( $activities );

				$achievements = badgeos_get_user_achievements(
					array(
						'user_id' => absint( $user_id ),
						'achievement_id' => $achievement_id
					)
				);

				$total_achievments = count( $achievements );
				$used_points = intval( $minimum_activity_count ) * intval( $total_achievments );
				$remainder = intval( $relevant_count ) - $used_points;

				$return  = 0;
				if ( absint( $remainder ) >= $minimum_activity_count )
					$return  = $remainder;

				return $return;
			} else {

				$return = 0;
			}

		}
	}

	return $return;
}

add_filter( 'user_deserves_achievement', 'badgeos_lifterlms_user_deserves_lifterlms_step', 15, 6 );

/**
 * Check if user does not have the same rank step already, and is eligible for the step
 *
 * @param $return_val
 * @param $step_id
 * @param $rank_id
 * @param $user_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 *
 * @return bool
 */
function badgeos_lifterlms_user_deserves_rank_step($return_val, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args) {

    /**
     * If we're not dealing with a rank_requirement, bail here
     */
    if ( 'rank_requirement' != get_post_type( $step_id ) ) {
        return $return_val;
    }

    /**
     * Grab our step requirements
     */
    $requirements = badgeos_get_rank_req_step_requirements( $step_id );

    /**
     * If the step is triggered by lifterlms actions...
     */
    if ( 'lifterlms_trigger' == $requirements[ 'trigger_type' ] ) {

        /**
         * Do not pass go until we say you can
         */
        $return_val = false;

        /**
         * Unsupported trigger
         */
        if ( ! isset( $GLOBALS[ 'badgeos_lifterlms' ]->triggers[ $this_trigger ] ) ) {
            return $return_val;
        }

        $lifterlms_triggered = is_lifter_lms_trigger($requirements, $args);

        /**
         * LifterLMS requirements met
         */

        $return_val = $lifterlms_triggered;

    }

    return $return_val;
}

add_filter('badgeos_user_deserves_rank_step', 'badgeos_lifterlms_user_deserves_rank_step', 10, 7);

/**
 *
 * Check if user does not have the same rank already, and is eligible for the rank
 *
 * @param $completed
 * @param $step_id
 * @param $rank_id
 * @param $user_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 *
 * @return bool
 */
function badgeos_lifterlms_user_deserves_rank_award($completed, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args) {

    /**
     * If we're not dealing with a rank_requirement, bail here
     */
    if ( 'rank_requirement' != get_post_type( $step_id ) ) {
        return $completed;
    }

    /**
     * Get the requirement rank
     */
    $rank = badgeos_get_rank_requirement_rank( $step_id );

    /**
     * Get all requirements of this rank
     */
    $requirements = badgeos_get_rank_requirements( $rank_id );

    $completed = true;

    foreach( $requirements as $requirement ) {

        /**
         * Check if rank requirement has been earned
         */
        if( ! badgeos_get_user_ranks( array(
            'user_id' => $user_id,
            'rank_id' => $requirement->ID,
            'since' => strtotime( $rank->post_date ),
            'no_steps' => false
        ) ) ) {
            $completed = false;
            break;
        }
    }

    return $completed;
}

add_filter( 'badgeos_user_deserves_rank_award', 'badgeos_lifterlms_user_deserves_rank_award', 15, 7 );

/**
 *
 * Check if user is eligible for the points award
 *
 * @param $return_val
 * @param $step_id
 * @param $credit_parent_id
 * @param $user_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 *
 * @return bool
 */
function badgeos_lifterlms_user_deserves_credit_award_cb ($return_val, $step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args) {
    /**
     * If we're not dealing with correct requirement type, bail here
     */
    if ( 'point_award' != get_post_type( $step_id ) ) {
        return $return_val;
    }

    /**
     * Grab our step requirements
     */
    $requirements = badgeos_get_award_step_requirements( $step_id );

    /**
     * If the step is triggered by lifterlms actions...
     */
    if ( 'lifterlms_trigger' == $requirements[ 'trigger_type' ] ) {

        /**
         * Do not pass go until we say you can
         */
        $return_val = false;

        /**
         * Unsupported trigger
         */
        if ( ! isset( $GLOBALS[ 'badgeos_lifterlms' ]->triggers[ $this_trigger ] ) ) {
            return $return_val;
        }

        $lifterlms_triggered = is_lifter_lms_trigger($requirements, $args);

        /**
         * LifterLMS requirements met
         */

        $return_val = $lifterlms_triggered;

    }

    return $return_val;
}

add_filter( 'badgeos_user_deserves_credit_award', 'badgeos_lifterlms_user_deserves_credit_award_cb', 10, 7 );

/**
 * Check if user is eligible for the points deduction
 *
 * @param $return_val
 * @param $step_id
 * @param $credit_parent_id
 * @param $user_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 *
 * @return bool
 */
function badgeos_lifterlms_user_deserves_credit_deduct_cb ($return_val, $step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args) {

    /**
     * If we're not dealing with correct requirement type, bail here
     */
    if ( 'point_deduct' != get_post_type( $step_id ) ) {
        return $return_val;
    }

    /**
     * Grab our step requirements
     */
    $requirements = badgeos_get_deduct_step_requirements( $step_id );

    /**
     * If the step is triggered by lifterlms actions...
     */
    if ( 'lifterlms_trigger' == $requirements[ 'trigger_type' ] ) {

        /**
         * Do not pass go until we say you can
         */
        $return_val = false;

        /**
         * Unsupported trigger
         */
        if ( ! isset( $GLOBALS[ 'badgeos_lifterlms' ]->triggers[ $this_trigger ] ) ) {
            return $return_val;
        }

        $lifterlms_triggered = is_lifter_lms_trigger($requirements, $args);

        /**
         * LifterLMS requirements met
         */

        $return_val = $lifterlms_triggered;

    }

    return $return_val;

}

add_filter( 'badgeos_user_deserves_credit_deduct', 'badgeos_lifterlms_user_deserves_credit_deduct_cb', 10, 7 );

/**
 * Check if a valid LifterLMS trigger found for the given requirements
 *
 * @param $requirements
 * @param $args
 * @return bool
 */
function is_lifter_lms_trigger($requirements, $args) {

    /**
     * lifterlms requirements not met yet
     */
    $lifterlms_triggered = false;

    /**
     * Set our main vars
     */
    $lifterlms_trigger = $requirements['lifterlms_trigger'];
    $object_id = $requirements['lifterlms_object_id'];

    /**
     * Extra arg handling for further expansion
     */
    $object_arg1 = null;

    if ( isset( $requirements['lifterlms_object_arg1'] ) )
        $object_arg1 = $requirements['lifterlms_object_arg1'];

    /**
     * Object-specific triggers
     */
    $lifterlms_object_triggers = array(
        'lifterlms_quiz_completed',
        'badgeos_lifterlms_quiz_completed_specific',
        'lifterlms_quiz_failed',
        'lifterlms_quiz_passed',
        'lifterlms_section_completed',
        'lifterlms_lesson_completed',
        'lifterlms_course_completed',
        'lifterlms_course_track_completed',
        'lifterlms_created_person'
    );

    /**
     * Enrollement triggers
     */
    $lifterlms_membership_triggers = array(
        'llms_user_added_to_membership_level',
        'llms_user_enrolled_in_course',
    );

    /**
     * purchase
     */
    $lifterlms_purchase_triggers = array(
        'lifterlms_access_plan_purchased',
        'badgeos_lifterlms_membership_purchased',
        'badgeos_lifterlms_course_purchased'
    );

    /**
     * Category-specific triggers
     */
    $lifterlms_category_triggers = array(
        'badgeos_lifterlms_course_completed_tag',
        'badgeos_lifterlms_course_completed_cat',
        'badgeos_lifterlms_course_completed_difficulty',
        'badgeos_lifterlms_user_added_to_membership_level_tag',
        'badgeos_lifterlms_user_added_to_membership_level_cat'
    );

    /**
     * Quiz-specific triggers
     */
    $lifterlms_quiz_triggers = array(
        'lifterlms_quiz_completed',
        'lifterlms_quiz_failed',
        'badgeos_lifterlms_quiz_completed_specific',
        'lifterlms_quiz_passed'
    );

    /**
     * Triggered object ID (used in these hooks, generally 2nd arg)
     */
    $triggered_object_id = 0;

    $arg_data = $args;

    if ( is_array( $arg_data ) ) {
        if ( isset( $arg_data[ 1 ] ) ) {
            $triggered_object_id = (int) $arg_data[ 1 ];
        }
    }

    /**
     * Use basic trigger logic if no object set
     */
    if ( empty( $object_id ) && !in_array( $lifterlms_trigger, $lifterlms_category_triggers ) && ! in_array( $lifterlms_trigger, $lifterlms_membership_triggers ) && ! in_array( $lifterlms_trigger, $lifterlms_purchase_triggers )  ) {
        $lifterlms_triggered = true;

    } elseif ( in_array( $lifterlms_trigger, $lifterlms_object_triggers ) && $triggered_object_id == $object_id ) {
        $lifterlms_triggered = true;

    } elseif ( in_array( $lifterlms_trigger, $lifterlms_purchase_triggers ) ) {
        $product = new LLMS_Product( $triggered_object_id );
        if ( $triggered_object_id == $object_id && ! $product->has_free_access_plan() ) {
            $lifterlms_triggered = true;
        } elseif ( intval( $object_id ) == 0 ) {
            $post_type = get_post_type( $triggered_object_id );
            $product = new LLMS_Product( $triggered_object_id );

            if ( $lifterlms_trigger == 'badgeos_lifterlms_course_purchased' && $post_type == 'course' && !$product->has_free_access_plan() ) {

                $lifterlms_triggered = true;
            } elseif ( $lifterlms_trigger == 'badgeos_lifterlms_membership_purchased' && $post_type == 'llms_membership' && !$product->has_free_access_plan() ) {

                $lifterlms_triggered = true;
            } elseif ( $lifterlms_trigger == 'lifterlms_access_plan_purchased' && $post_type == 'llms_access_plan' ) {

                $lifterlms_triggered = true;
            }
        }
    } elseif ( in_array( $lifterlms_trigger, $lifterlms_membership_triggers ) ) {
        $post_id = $args[1];

        if( empty($object_id) ) {
            $lifterlms_triggered = true;
        } else if( $object_id == $post_id ) {
            $lifterlms_triggered = true;
        }

    } elseif ( 'badgeos_lifterlms_user_added_to_membership_level_cat' == $lifterlms_trigger && has_term( $object_id, 'membership_cat', $triggered_object_id ) ) {
        $lifterlms_triggered = true;
    } elseif ( 'badgeos_lifterlms_user_added_to_membership_level_tag' == $lifterlms_trigger && has_term( $object_id, 'membership_tag', $triggered_object_id ) ) {
        $lifterlms_triggered = true;
    } elseif ( 'badgeos_lifterlms_course_completed_difficulty' == $lifterlms_trigger && has_term( $object_id, 'course_difficulty', $triggered_object_id ) ) {
        $lifterlms_triggered = true;
    } elseif ( 'badgeos_lifterlms_course_completed_tag' == $lifterlms_trigger && has_term( $object_id, 'course_tag', $triggered_object_id ) ) {
        $lifterlms_triggered = true;
    } elseif ( 'badgeos_lifterlms_course_completed_cat' == $lifterlms_trigger && has_term( $object_id, 'course_cat', $triggered_object_id ) ) {
        $lifterlms_triggered = true;
    }

    /**
     * Quiz triggers
     */
    if ( $lifterlms_triggered && in_array( $lifterlms_trigger, $lifterlms_quiz_triggers ) ) {

        /**
         * Check for fail
         */
        if ( 'badgeos_lifterlms_quiz_completed_specific' == $lifterlms_trigger ) {

            $quizez = new LLMS_Student_Quizzes(  $arg_data[ 0 ] );
            $attempt = $quizez->get_last_attempt( $triggered_object_id );
            $object_arg1 = (int) $object_arg1;
            $percentage = $attempt->get( 'grade' );
            if ( $percentage >= $object_arg1 ) {
                $lifterlms_triggered = true;
            }else{
                $lifterlms_triggered = false;
            }
        }
    }

    return $lifterlms_triggered;
}