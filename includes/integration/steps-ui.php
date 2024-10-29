<?php
/**
 * Custom Achievement Steps UI.
 *
 * @package BadgeOS lifterlms pro
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Update badgeos_get_step_requirements to include our custom requirements.
 *
 * @param $requirements
 * @param $step_id
 * @return mixed
 */
function badgeos_lifterlms_step_requirements( $requirements, $step_id ) {

	/**
     * Add our new requirements to the list
     */
	$requirements[ 'lifterlms_trigger' ] = get_post_meta( $step_id, '_badgeos_lifterlms_trigger', true );
	$requirements[ 'lifterlms_object_id' ] = (int) get_post_meta( $step_id, '_badgeos_lifterlms_object_id', true );
	$requirements[ 'lifterlms_object_arg1' ] = (int) get_post_meta( $step_id, '_badgeos_lifterlms_object_arg1', true );

	return $requirements;
}
add_filter( 'badgeos_get_step_requirements', 'badgeos_lifterlms_step_requirements', 10, 2 );
add_filter( 'badgeos_get_rank_req_step_requirements', 'badgeos_lifterlms_step_requirements', 10, 2 );
add_filter( 'badgeos_get_award_step_requirements', 'badgeos_lifterlms_step_requirements', 10, 2 );
add_filter( 'badgeos_get_deduct_step_requirements', 'badgeos_lifterlms_step_requirements', 10, 2 );

/**
 * Filter the BadgeOS Triggers selector with our own options.
 *
 * @param $triggers
 * @return mixed
 */
function badgeos_lifterlms_activity_triggers( $triggers ) {
	$triggers[ 'lifterlms_trigger' ] = __( 'LifterLMS Activity', LIFTERLMS_BOS_TEXT_DOMAIN );

	return $triggers;
}
add_filter( 'badgeos_activity_triggers', 'badgeos_lifterlms_activity_triggers' );
add_filter( 'badgeos_ranks_req_activity_triggers', 'badgeos_lifterlms_activity_triggers' );
add_filter( 'badgeos_award_points_activity_triggers', 'badgeos_lifterlms_activity_triggers' );
add_filter( 'badgeos_deduct_points_activity_triggers', 'badgeos_lifterlms_activity_triggers' );

/**
 * Add lifterlms Triggers selector to the Steps UI.
 *
 * @param $step_id
 * @param $post_id
 */
function badgeos_lifterlms_step_lifterlms_trigger_select( $step_id, $post_id ) {

	/**
     * Setup our select input
     */
	echo '<select name="lifterlms_trigger" class="select-lifterlms-trigger">';
	echo '<option value="">' . __( 'Select a LifterLMS Trigger', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all of our lifterlms trigger groups
     */
	$current_trigger = get_post_meta( $step_id, '_badgeos_lifterlms_trigger', true );

	$lifterlms_triggers = $GLOBALS[ 'badgeos_lifterlms' ]->triggers;

	if ( !empty( $lifterlms_triggers ) ) {
		foreach ( $lifterlms_triggers as $trigger => $trigger_label ) {
			if ( is_array( $trigger_label ) ) {
				$optgroup_name = $trigger;
				$triggers = $trigger_label;

				echo '<optgroup label="' . esc_attr( $optgroup_name ) . '">';

				/**
                 * Loop through each trigger in the group
                 */
				foreach ( $triggers as $trigger_hook => $trigger_name ) {
					echo '<option' . selected( $current_trigger, $trigger_hook, false ) . ' value="' . esc_attr( $trigger_hook ) . '">' . esc_html( $trigger_name ) . '</option>';
				}
				echo '</optgroup>';
			} else {
				echo '<option' . selected( $current_trigger, $trigger, false ) . ' value="' . esc_attr( $trigger ) . '">' . esc_html( $trigger_label ) . '</option>';
			}
		}
	}

	echo '</select>';

}
add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_lifterlms_step_lifterlms_trigger_select', 10, 2 );
add_action( 'badgeos_rank_req_steps_ui_html_after_trigger_type', 'badgeos_lifterlms_step_lifterlms_trigger_select', 10, 2 );
add_action( 'badgeos_award_steps_ui_html_after_achievement_type', 'badgeos_lifterlms_step_lifterlms_trigger_select', 10, 2 );
add_action( 'badgeos_deduct_steps_ui_html_after_trigger_type', 'badgeos_lifterlms_step_lifterlms_trigger_select', 10, 2 );

/**
 * Add a BuddyPress group selector to the Steps UI.
 *
 * @param $step_id
 * @param $post_id
 */
function badgeos_lifterlms_step_etc_select( $step_id, $post_id ) {

	$current_trigger = get_post_meta( $step_id, '_badgeos_lifterlms_trigger', true );
	$current_object_id = (int) get_post_meta( $step_id, '_badgeos_lifterlms_object_id', true );
	$current_object_arg1 = (int) get_post_meta( $step_id, '_badgeos_lifterlms_object_arg1', true );

	/**
     * Quizes
     */
	echo '<select name="badgeos_lifterlms_quiz_id" class="select-quiz-id">';
	echo '<option value="">' . __( 'Any Quiz', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';
	$objects = get_posts( array(
		'post_type' => 'llms_quiz',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'lifterlms_quiz_completed', 'badgeos_lifterlms_quiz_completed_specific', 'lifterlms_quiz_passed' ,'lifterlms_quiz_failed' ) ) )
				$selected = selected( $current_object_id, $object->ID, false );

			echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * Grade input
     */
	$grade = 100;

	if ( in_array( $current_trigger, array( 'badgeos_lifterlms_quiz_completed_specific' ) ) )
		$grade = (int) $current_object_arg1;

	if ( empty( $grade ) )
		$grade = 100;

	echo '<span><input name="badgeos_lifterlms_quiz_grade" class="input-quiz-grade" type="text" value="' . $grade . '" size="3" maxlength="3" placeholder="100" />%</span>';

	/**
     * Section
     */
	echo '<select name="badgeos_lifterlms_section_id" class="select-section-id">';
	echo '<option value="">' . __( 'Any Section', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$sections = '';
	$sections = get_posts( array(
		'post_type' => 'section',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $sections ) ) {
		foreach ( $sections as $section ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'lifterlms_section_completed' ) ) )
				$selected = selected( $current_object_id, $section->ID, false );

			echo '<option' . $selected . ' value="' . $section->ID . '">' . esc_html( get_the_title( $section->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * Lessons
     */
	echo '<select name="badgeos_lifterlms_lesson_id" class="select-lesson-id">';
	echo '<option value="">' . __( 'Any Lesson', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';
	$objects = get_posts( array(
		'post_type' => 'lesson',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'lifterlms_lesson_completed' ) ) )
				$selected = selected( $current_object_id, $object->ID, false );
				
			echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * Courses
     */
	echo '<select name="badgeos_lifterlms_course_id" class="select-course-id">';
	echo '<option value="">' . __( 'Any Course', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';
	
	$objects = get_posts( array(
		'post_type' => 'course',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'lifterlms_course_completed' ,'llms_user_enrolled_in_course' ) ) )
				$selected = selected( $current_object_id, $object->ID, false );

			echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * Purchase Courses
     */
	echo '<select name="badgeos_lifterlms_purchased_course_id" class="select-purchased-course-id">';
	echo '<option value="">' . __( 'Purchase Any Course', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';
	
	$objects = get_posts( array(
		'post_type' => 'course',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';
			$product = new LLMS_Product( $object->ID );

			if ( in_array( $current_trigger, array( 'badgeos_lifterlms_course_purchased' ) ) )
				$selected = selected( $current_object_id, $object->ID, false );

			if ( ! $product->has_free_access_plan() )
			echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * Course Category
     */

	echo '<select name="badgeos_lifterlms_course_category_id" class="select-course-category-id">';
	echo '<option value="">' . __( 'Any Course Category', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';

	if( taxonomy_exists( 'course_cat' ) ) {
		$objects = get_terms( 'course_cat', array(
			'hide_empty' => false
		) );
	}

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'badgeos_lifterlms_course_completed_cat' ) ) )
				$selected = selected( $current_object_id, $object->term_id, false );

			echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * course Difficulty
     */
	echo '<select name="badgeos_lifterlms_course_difficulty_id" class="select-course-difficulty-id">';
	echo '<option value="">' . __( 'Any course Difficulty', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */

	$objects_post = '';


	if( taxonomy_exists( 'course_difficulty' ) ) {
		$objects_post = get_terms( 'course_difficulty', array(
			'hide_empty' => false
		) );
	}

	if ( !empty( $objects_post ) ) {
		foreach ( $objects_post as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'badgeos_lifterlms_course_completed_difficulty' ) ) )
				$selected = selected( $current_object_id, $object->term_id, false );

			echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * course tag
     */

	echo '<select name="badgeos_lifterlms_course_tag_id" class="select-course-tag-id">';
	echo '<option value="">' . __( 'Any Course Tag', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';

	if( taxonomy_exists( 'course_tag' ) ){
		$objects = get_terms( 'course_tag', array(
			'hide_empty' => false
		) );
	}

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'badgeos_lifterlms_course_completed_tag' ) ) )
				$selected = selected( $current_object_id, $object->term_id, false );

			echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * course track
     */

	echo '<select name="badgeos_lifterlms_course_track_id" class="select-course-track-id">';
	echo '<option value="">' . __( 'Any Course Track', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';

	if( taxonomy_exists( 'course_track' ) ) {
		$objects = get_terms( 'course_track', array(
			'hide_empty' => false
		) );
	}

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'lifterlms_course_track_completed' ) ) )
				$selected = selected( $current_object_id, $object->term_id, false );

			echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
		}
	}

	echo '</select>';


	/**
     * Membership cat
     */
	echo '<select name="badgeos_lifterlms_membership_cat_id" class="select-membership-cat-id">';
	echo '<option value="">' . __( 'Any Membership Category', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';
	if( taxonomy_exists( 'membership_cat' ) ) {
		$objects = get_terms( 'membership_cat', array(
			'hide_empty' => false
		) );
	}

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'badgeos_lifterlms_user_added_to_membership_level_cat' ) ) )
				$selected = selected( $current_object_id, $object->term_id, false );

			echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * Access Plan Purchased
     */
	echo '<select name="badgeos_lifterlms_access_plan_id" class="select-access-plan-id">';
	echo '<option value="">' . __( 'Any Access Plan', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';
	$objects = get_posts( array(
		'post_type' => 'llms_access_plan',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'lifterlms_access_plan_purchased' ) ) )
				$selected = selected( $current_object_id, $object->ID, false );

			echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * membership-tag
     */
	echo '<select name="badgeos_lifterlms_membership_tag_id" class="select-membership-tag-id">';
	echo '<option value="">' . __( 'Any Membership Tag', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';
	if( taxonomy_exists( 'membership_tag' ) ) {
		$objects = get_terms( 'membership_tag', array(
			'hide_empty' => false
		) );
	}

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'badgeos_lifterlms_user_added_to_membership_level_tag' ) ) )
				$selected = selected( $current_object_id, $object->term_id, false );
		

			echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * Added to the membership level
     */
	echo '<select name="badgeos_lifterlms_membership_id" class="select-membership-id">';
	echo '<option value="">' . __( 'Any Membership', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';
	$objects = get_posts( array(
		'post_type' => 'llms_membership',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';
			
			if ( in_array( $current_trigger, array( 'llms_user_added_to_membership_level' ) ) ) 
				$selected = selected( $current_object_id, $object->ID, false );
	
		 	echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	/**
     * Purchased membership level
     */
	echo '<select name="badgeos_lifterlms_purchased_membership_id" class="select-purchased-membership-id">';
	echo '<option value="">' . __( 'Purchase Any Membership', LIFTERLMS_BOS_TEXT_DOMAIN ) . '</option>';

	/**
     * Loop through all objects
     */
	$objects = '';
	$objects = get_posts( array(
		'post_type' => 'llms_membership',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';
			$product = new LLMS_Product( $object->ID );
			
			if ( in_array( $current_trigger, array( 'badgeos_lifterlms_membership_purchased' ) ) ) 
				$selected = selected( $current_object_id, $object->ID, false );

			if ( ! $product->has_free_access_plan() )
		 	echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

}
add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_lifterlms_step_etc_select', 10, 2 );
add_action( 'badgeos_rank_req_steps_ui_html_after_trigger_type', 'badgeos_lifterlms_step_etc_select', 10, 2 );
add_action( 'badgeos_award_steps_ui_html_after_achievement_type', 'badgeos_lifterlms_step_etc_select', 10, 2 );
add_action( 'badgeos_deduct_steps_ui_html_after_trigger_type', 'badgeos_lifterlms_step_etc_select', 10, 2 );

/**
 * AJAX Handler for saving all steps.
 *
 * @param $title
 * @param $step_id
 * @param $step_data
 * @return string|void
 */
function badgeos_lifterlms_save_step( $title, $step_id, $step_data ) {
 
	/**
     * If we're working on a lifterlms trigger
     */
	if ( 'lifterlms_trigger' == $step_data[ 'trigger_type' ] ) {

		/**
         * Update our lifterlms trigger post meta
         */
		update_post_meta( $step_id, '_badgeos_lifterlms_trigger', $step_data[ 'lifterlms_trigger' ] );

		/**
         * Rewrite the step title
         */
		$title = $step_data[ 'lifterlms_trigger_label' ];

		$object_id = 0;
		$object_arg1 = 0;

		/**
         * Quiz specific (pass)
         */
		if ( 'lifterlms_quiz_completed' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_quiz_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any quiz', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				$title = sprintf( __( 'Completed quiz "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		} elseif ( 'badgeos_lifterlms_quiz_completed_specific' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_quiz_id' ];
			$object_arg1 = (int) $step_data[ 'lifterlms_quiz_grade' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = sprintf( __( 'Completed any quiz with a score of %d or higher', LIFTERLMS_BOS_TEXT_DOMAIN ), $object_arg1 );
			} else {
				$title = sprintf( __( 'Completed quiz "%s" with a score of %d or higher', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ), $object_arg1 );
			}
		} elseif ( 'lifterlms_quiz_failed' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_quiz_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Failed any quiz', LIFTERLMS_BOS_TEXT_DOMAIN );
			}  else {
				$title = sprintf( __( 'Failed quiz "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		} elseif ( 'lifterlms_section_completed' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_section_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any section', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				$title = sprintf( __( 'Completed section "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		} elseif ( 'lifterlms_lesson_completed' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_lesson_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any lesson', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				$title = sprintf( __( 'Completed lesson "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		} elseif ( 'lifterlms_course_completed' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_course_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any course', LIFTERLMS_BOS_TEXT_DOMAIN );
			}  else {
				$title = sprintf( __( 'Completed course "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		} elseif ( 'badgeos_lifterlms_course_completed_tag' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_course_tag_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Completed course in any tag', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else { 
					if ( get_term( $object_id, 'course_tag' ) && taxonomy_exists( 'course_tag' ) ) {
                    $title = sprintf( __( 'Completed course in tag "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_term( $object_id, 'course_tag' )->name );
				}
			}
		} elseif ( 'badgeos_lifterlms_course_completed_cat' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_course_category_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Completed course in any category', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				if ( get_term( $object_id, 'course_cat' ) && taxonomy_exists( 'course_cat' )){
                    $title = sprintf( __( 'Completed course in category "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_term( $object_id, 'course_cat' )->name );
				}
			}
		} elseif ( 'badgeos_lifterlms_course_completed_difficulty' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_course_difficulty_id' ];
			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Completed course in any difficulty', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				if ( get_term( $object_id, 'course_difficulty' ) && taxonomy_exists( 'course_difficulty' ) ) {
					$title = sprintf( __( 'Completed course in difficulty "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_term( $object_id, 'course_difficulty' )->name );
				}
			}
		} elseif ( 'lifterlms_course_track_completed' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_course_track_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any course track', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				if ( get_term( $object_id, 'course_track' ) && taxonomy_exists( 'course_track' ) ) {
					$title = sprintf( __( 'Completed course track "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_term( $object_id, 'course_track' )->name );
				}
			}
		} elseif ( 'lifterlms_quiz_passed' == $step_data['lifterlms_trigger'] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_quiz_id' ];
			
			if ( empty( $object_id ) ) {
				$title = __( 'Passed Any Quiz', LIFTERLMS_BOS_TEXT_DOMAIN );
			}  else {
				$title = sprintf( __( 'Passed Quiz "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		}  elseif ( 'llms_user_enrolled_in_course' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_course_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Enrolled in any course', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				$title = sprintf( __( 'Enrolled in course "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		} elseif ( 'badgeos_lifterlms_course_purchased' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_purchased_course_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Purchased any course', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				$title = sprintf( __( 'Purchased course "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		} elseif ( 'badgeos_lifterlms_user_added_to_membership_level_tag' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_membership_tag_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Added to membership from Any Tag', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				if ( get_term( $object_id, 'membership_tag' ) && taxonomy_exists( 'membership_tag' ) ) {
					$title = sprintf( __( 'Added to membership from Tag "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_term( $object_id, 'membership_tag' )->name );
				}
			}
		} elseif ( 'badgeos_lifterlms_user_added_to_membership_level_cat' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_membership_cat_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Added to membership from Any Category', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				if ( get_term( $object_id, 'membership_cat' ) && taxonomy_exists( 'membership_cat' ) ) {
					$title = sprintf( __( 'Added to membership from Category "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_term( $object_id, 'membership_cat' )->name );
				}
			}
		} elseif ( 'badgeos_lifterlms_membership_purchased' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_purchased_membership_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Purchased any Membership', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				$title = sprintf( __( 'Purchased Membershipe "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		} elseif ( 'lifterlms_access_plan_purchased' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_access_plan_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Purchased any access plan', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				$title = sprintf( __( 'Purchased access plan "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		} elseif ( 'llms_user_added_to_membership_level' == $step_data[ 'lifterlms_trigger' ] ) {

		    /**
             * Get Object ID
             */
			$object_id = (int) $step_data[ 'lifterlms_membership_id' ];

			/**
             * Set new step title
             */
			if ( empty( $object_id ) ) {
				$title = __( 'Added to any membership', LIFTERLMS_BOS_TEXT_DOMAIN );
			} else {
				$title = sprintf( __( 'Added to membership "%s"', LIFTERLMS_BOS_TEXT_DOMAIN ), get_the_title( $object_id ) );
			}
		} elseif ( 'lifterlms_created_person' == $step_data[ 'lifterlms_trigger' ] ) {

			/**
             * Set new step title
             */
			$title = __( 'User Registers', LIFTERLMS_BOS_TEXT_DOMAIN );
		
		}

		/**
         * Store our Object ID in meta
         */
		update_post_meta( $step_id, '_badgeos_lifterlms_object_id', $object_id );
		update_post_meta( $step_id, '_badgeos_lifterlms_object_arg1', $object_arg1 );
	} else {
        delete_post_meta( $step_id, '_badgeos_lifterlms_trigger' );
        delete_post_meta( $step_id, '_badgeos_lifterlms_object_id' );
        delete_post_meta( $step_id, '_badgeos_lifterlms_object_arg1' );
    }

	return $title;
}
add_filter( 'badgeos_save_step', 'badgeos_lifterlms_save_step', 10, 3 );

/**
 * Include custom JS for the BadgeOS Steps UI.
 */
function badgeos_lifterlms_step_js() {
	?>
	<script type="text/javascript">
		jQuery( document ).ready( function ( $ ) { 

			var times = $( '.required-count' ).val();

            /**
             * Listen for our change to our trigger type selector
             */
			$( document ).on( 'change', '.select-trigger-type', function () {

				var trigger_type = $( this ); 

                /**
                 * Show our group selector if we're awarding based on a specific group
                 */
				if ( 'lifterlms_trigger' == trigger_type.val() ) {
					trigger_type.siblings( '.select-lifterlms-trigger' ).show().change();
					var trigger = $('.select-lifterlms-trigger').val();
					if ( 'badgeos_lifterlms_quiz_completed_specific'  == trigger ) {
						$('.input-quiz-grade').parent().show();
					}
				}  else {
					trigger_type.siblings( '.select-lifterlms-trigger' ).val('').hide().change();
					$( '.input-quiz-grade' ).parent().hide();
					var fields = ['quiz','lesson','section','membership-cat','membership-tag','course','quiz-pass','quiz-fail','course-category','course-tag','enrolled-course','course-difficulty','purchased-course','purchased-membership','course-track','access-plan','membership'];
					$( fields ).each( function( i,field ) {
						trigger_type.parent().siblings('.select-' + field + '-id').hide();
						console.log('.select-' + field + '-id');
					});
					$( '.required-count' ).val( times );
				}
			} );

            /**
             * Listen for our change to our trigger type selector
             */
			$( document ).on( 'change', '.select-lifterlms-trigger,' +
										'.select-quiz-id,' +
										'.select-section-id,' +
										'.select-lesson-id,' +
										'.select-course-id,' +
										'.select-course-tag-id,' + 
										'.select-course-track-id,' + 
										'.select-course-category-id,'+
										'.select-course-difficulty-id,'+
										'.select-quiz-fail-id,'+
										'.select-quiz-pass-id,'+
										'.select-course-category-id,' + 
										'.select-purchased-course-id,' + 
										'.select-purchased-membership-id,' +
										'.select-membership-id,' +
										'.select-membership-tag-id,' +
										'.select-membership-cat-id,' +
										'.select-enrolled-course-id', function () {
				badgeos_lifterlms_step_change( $( this ) , times);
			} );

            /**
             * Trigger a change so we properly show/hide our lifterlms menues
             */
			$( '.select-trigger-type' ).change();

            /**
             * Inject our custom step details into the update step action
             */
			$( document ).on( 'update_step_data', function ( event, step_details, step ) {
				step_details.lifterlms_trigger = $( '.select-lifterlms-trigger', step ).val();
				step_details.lifterlms_trigger_label = $( '.select-lifterlms-trigger option', step ).filter( ':selected' ).text();

				step_details.lifterlms_quiz_id = $( '.select-quiz-id', step ).val();
				step_details.lifterlms_quiz_grade = $( '.input-quiz-grade', step ).val();
				step_details.lifterlms_section_id = $( '.select-section-id', step ).val();
				step_details.lifterlms_lesson_id = $( '.select-lesson-id', step ).val();
				step_details.lifterlms_course_id = $( '.select-course-id', step ).val();
				step_details.lifterlms_course_category_id = $( '.select-course-category-id', step ).val();
				step_details.lifterlms_course_difficulty_id = $( '.select-course-difficulty-id', step ).val();
				step_details.lifterlms_course_tag_id = $( '.select-course-tag-id', step ).val();
				step_details.lifterlms_quiz_pass_id = $( '.select-quiz-pass-id', step ).val();
				step_details.lifterlms_quiz_fail_id = $( '.select-quiz-fail-id', step ).val();
				step_details.lifterlms_course_track_id = $( '.select-course-track-id', step ).val();
				step_details.lifterlms_membership_id = $( '.select-membership-id', step ).val();
				step_details.lifterlms_purchased_membership_id = $( '.select-purchased-membership-id', step ).val();
				step_details.lifterlms_purchased_course_id = $( '.select-purchased-course-id', step ).val();
				step_details.lifterlms_access_plan_id = $( '.select-access-plan-id', step ).val();
				step_details.lifterlms_membership_cat_id = $( '.select-membership-cat-id', step ).val();
				step_details.lifterlms_membership_tag_id = $( '.select-membership-tag-id', step ).val();
			} );

		} );

		function badgeos_lifterlms_step_change( $this , times) {

			var trigger_parent = $this.parent(),
				trigger_value = trigger_parent.find( '.select-lifterlms-trigger' ).val();
			var	trigger_parent_value = trigger_parent.find( '.select-trigger-type' ).val();

            /**
             * Quiz specific
             */
			trigger_parent.find( '.select-quiz-id' )
				.toggle(
					( 'lifterlms_quiz_completed' == trigger_value
					 || 'badgeos_lifterlms_quiz_completed_specific' == trigger_value
					 || 'lifterlms_quiz_passed' == trigger_value
					 || 'lifterlms_quiz_failed' == trigger_value )
				);
			
			/**
             * Quiz pass specific
             */
			trigger_parent.find( '.select-quiz-pass-id' )
				.toggle( 'lifterlms_quiz_passed' == trigger_value );

			/**
             * Purchased Membership specific
             */
			trigger_parent.find( '.select-purchased-membership-id' )
				.toggle( 'badgeos_lifterlms_membership_purchased' == trigger_value );

			/**
             * Purchased course specific
             */
			trigger_parent.find( '.select-purchased-course-id' )
				.toggle( 'badgeos_lifterlms_course_purchased' == trigger_value );

			/**
             * Membership tag
             */
			trigger_parent.find( '.select-membership-tag-id' )
				.toggle( 'badgeos_lifterlms_user_added_to_membership_level_tag' == trigger_value );

			/**
             * Membership cat
             */
			trigger_parent.find( '.select-membership-cat-id' )
				.toggle( 'badgeos_lifterlms_user_added_to_membership_level_cat' == trigger_value );
			
			/**
             * Quiz fail specific
             */
			trigger_parent.find( '.select-quiz-fail-id' )
				.toggle( 'lifterlms_quiz_failed' == trigger_value );

            /**
             * section specific
             */
			trigger_parent.find( '.select-section-id' )
				.toggle( 'lifterlms_section_completed' == trigger_value );

            /**
             * Lesson specific
             */
			trigger_parent.find( '.select-lesson-id' )
				.toggle( 'lifterlms_lesson_completed' == trigger_value );

            /**
             * Course specific
             */
			trigger_parent.find( '.select-course-id' )
				.toggle( 
					( 'lifterlms_course_completed' == trigger_value
					 || 'llms_user_enrolled_in_course' == trigger_value ) );

            /**
             * Course Category specific
             */
			trigger_parent.find( '.select-course-category-id' )
				.toggle( 'badgeos_lifterlms_course_completed_cat' == trigger_value );

            /**
             * Course Tag specific
             */
			trigger_parent.find( '.select-course-tag-id' )
				.toggle( 'badgeos_lifterlms_course_completed_tag' == trigger_value );


            /**
             * Course difficulty specific
             */
			trigger_parent.find( '.select-course-difficulty-id' )
				.toggle( 'badgeos_lifterlms_course_completed_difficulty' == trigger_value );

            /**
             * Membership level specific
             */
			trigger_parent.find( '.select-membership-id' )
				.toggle( 'llms_user_added_to_membership_level' == trigger_value  );

            /**
             * Quiz Grade specific
             */
			trigger_parent.find( '.input-quiz-grade' ).parent() // target parent span
				.toggle( 'badgeos_lifterlms_quiz_completed_specific' == trigger_value );


			/**
             * Course trackspecific
             */
			trigger_parent.find( '.select-course-track-id' )
				.toggle( 'lifterlms_course_track_completed' == trigger_value );
			
			/**
             * Access plan specific
             */
			trigger_parent.find( '.select-access-plan-id' )
				.toggle( 'lifterlms_access_plan_purchased' == trigger_value );
				
			if ( ( 'lifterlms_quiz_completed' == trigger_value && '' != trigger_parent.find( '.select-quiz-id' ).val() )
			|| ( 'badgeos_lifterlms_quiz_completed_specific' == trigger_value && '' != trigger_parent.find( '.select-quiz-id' ).val() )
			|| ( 'lifterlms_quiz_failed' == trigger_value && '' != trigger_parent.find( '.select-quiz-fail-id' ).val() )
			|| ( 'lifterlms_section_completed' == trigger_value && '' != trigger_parent.find( '.select-section-id' ).val() )
			|| ( 'lifterlms_lesson_completed' == trigger_value && '' != trigger_parent.find( '.select-lesson-id' ).val() )
			|| ( 'lifterlms_course_completed' == trigger_value && '' != trigger_parent.find( '.select-course-id' ).val() )
			|| ( 'badgeos_lifterlms_course_completed_tag' == trigger_value && '' != trigger_parent.find( '.select-course-tag-id' ).val())
			|| ( 'badgeos_lifterlms_course_completed_cat' == trigger_value && '' != trigger_parent.find( '.select-course-category-id' ).val())
			|| ( 'lifterlms_quiz_passed' == trigger_value && '' != trigger_parent.find( '.select-quiz-pass-id' ).val())    
			|| ( 'badgeos_lifterlms_course_completed_difficulty' == trigger_value && '' != trigger_parent.find( '.select-course-difficulty-id' ).val())
			|| ( 'llms_user_added_to_membership_level' == trigger_value && '' != trigger_parent.find( '.select-membership-id' ).val() )
			|| ( 'llms_user_enrolled_in_course' == trigger_value && '' != trigger_parent.find( '.select-course-id' ).val() )
			|| ( 'lifterlms_course_track_completed' == trigger_value && '' != trigger_parent.find( '.select-course-track-id' ).val() )
			|| ( 'badgeos_lifterlms_course_purchased' == trigger_value && '' != trigger_parent.find( '.select-purchased-course-id' ).val() )
			|| ( 'badgeos_lifterlms_membership_purchased' == trigger_value && '' != trigger_parent.find( '.select-purchased-membership-id' ).val() )
			|| ( 'badgeos_lifterlms_user_added_to_membership_level_tag' == trigger_value && '' != trigger_parent.find( '.select-membership-tag-id' ).val() )
			|| ( 'badgeos_lifterlms_user_added_to_membership_level_cat' == trigger_value && '' != trigger_parent.find( '.select-membership-cat-id' ).val() )
			|| ( 'lifterlms_access_plan_purchased' == trigger_value && '' != trigger_parent.find( '.select-access-plan-id' ).val() )	
			) 		
				{
				trigger_parent.find( '.required-count' ).val( '1' );
			} else {

				if ( trigger_parent_value != 'lifterlms_trigger' ) {

					trigger_parent.find('.required-count')
						.val(times);
				}
			}
		}
	</script>
<?php
}
add_action( 'admin_footer', 'badgeos_lifterlms_step_js' );