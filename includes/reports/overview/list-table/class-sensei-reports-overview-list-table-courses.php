<?php
/**
 * File containing the Sensei_Reports_Overview_List_Table_Courses class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Courses overview list table class.
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_List_Table_Courses extends Sensei_Reports_Overview_List_Table_Abstract {
	/**
	 * Sensei grading related services.
	 *
	 * @var Sensei_Grading
	 */
	private $grading;

	/**
	 * Sensei course related services.
	 *
	 * @var Sensei_Course
	 */
	private $course;

	/**
	 * Constructor
	 *
	 * @param Sensei_Grading                                  $grading Sensei grading related services.
	 * @param Sensei_Course                                   $course Sensei course related services.
	 * @param Sensei_Reports_Overview_Data_Provider_Interface $data_provider Report data provider.
	 */
	public function __construct( Sensei_Grading $grading, Sensei_Course $course, Sensei_Reports_Overview_Data_Provider_Interface $data_provider ) {
		// Load Parent token into constructor.
		parent::__construct( 'courses', $data_provider );

		$this->grading = $grading;
		$this->course  = $course;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array The array of columns to use with the table
	 */
	public function get_columns() {
		if ( $this->columns ) {
			return $this->columns;
		}

		$total_completions      = Sensei_Utils::sensei_check_for_activity(
			array(
				'type'   => 'sensei_course_status',
				'status' => 'complete',
			)
		);
		$total_average_progress = $this->get_total_average_progress();

		$columns = array(
			'title'              => sprintf(
			// translators: Placeholder value is the number of courses.
				__( 'Course (%d)', 'sensei-lms' ),
				esc_html( $this->total_items )
			),
			'last_activity'      => __( 'Last Activity', 'sensei-lms' ),
			'completions'        => sprintf(
			// translators: Placeholder value is the number of completed courses.
				__( 'Completed (%d)', 'sensei-lms' ),
				esc_html( $total_completions )
			),
			'average_progress'   => sprintf(
			// translators: Placeholder vale is the total average progress for all courses.
				__( 'Average Progress (%s)', 'sensei-lms' ),
				esc_html( sprintf( '%d%%', $total_average_progress ) )
			),
			'average_percent'    => sprintf(
			// translators: Placeholder value is the average grade of all courses.
				__( 'Average Grade (%s%%)', 'sensei-lms' ),
				esc_html( ceil( $this->grading->get_courses_average_grade() ) )
			),
			'days_to_completion' => sprintf(
			// translators: Placeholder value is average days to completion.
				__( 'Days to Completion (%d)', 'sensei-lms' ),
				ceil( $this->course->get_days_to_completion_total() )
			),
		);

		// Backwards compatible filter name, moving forward should have single filter name.
		$columns = apply_filters( 'sensei_analysis_overview_courses_columns', $columns, $this );
		$columns = apply_filters( 'sensei_analysis_overview_columns', $columns, $this );

		$this->columns = $columns;

		return $this->columns;
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array The array of columns to use with the table
	 */
	public function get_sortable_columns() {
		$columns = array(
			'title'       => array( 'title', false ),
			'completions' => array( 'count_of_completions', false ),
		);

		// Backwards compatible filter name, moving forward should have single filter name.
		$columns = apply_filters( 'sensei_analysis_overview_courses_columns_sortable', $columns, $this );
		$columns = apply_filters( 'sensei_analysis_overview_columns_sortable', $columns, $this );

		return $columns;
	}

	/**
	 * Generates the overall array for a single item in the display
	 *
	 * @param object $item The current item.
	 *
	 * @return array Report row data.
	 * @throws Exception If date-time conversion fails.
	 */
	protected function get_row_data( $item ) {
		// Last Activity.
		$last_activity_date = __( 'N/A', 'sensei-lms' );
		$lessons            = $this->course->course_lessons( $item->ID, 'any', 'ids' );

		if ( 0 < count( $lessons ) ) {
			$last_activity_date = $this->get_last_activity_date( array( 'post__in' => $lessons ) );
		}

		// Get Course Completions.
		$course_args        = array(
			'post_id' => $item->ID,
			'type'    => 'sensei_course_status',
			'status'  => 'complete',
		);
		$course_completions = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_completions', $course_args, $item ) );

		// Average Grade will be N/A if the course has no lessons or quizzes, if none of the lessons
		// have a status of 'graded', 'passed' or 'failed', or if none of the quizzes have grades.
		$average_grade = __( 'N/A', 'sensei-lms' );

		// Get grades only if the course has lessons and quizzes.
		if ( ! empty( $lessons ) && $this->course->course_quizzes( $item->ID, true ) ) {
			$grade_args = array(
				'post__in' => $lessons,
				'type'     => 'sensei_lesson_status',
				'status'   => array( 'graded', 'passed', 'failed' ),
				'meta_key' => 'grade', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			);

			$percent_count = Sensei_Utils::sensei_check_for_activity( apply_filters( 'sensei_analysis_course_percentage', $grade_args, $item ), false );
			$percent_total = $this->grading::get_course_users_grades_sum( $item->ID );

			if ( $percent_count > 0 && $percent_total >= 0 ) {
				$average_grade = Sensei_Utils::quotient_as_absolute_rounded_number( $percent_total, $percent_count, 2 ) . '%';
			}
		}

		// Properties `count_of_completions` and `days_to_completion` where added to items in
		// `Sensei_Analysis_Overview_List_Table::add_days_to_completion_to_courses_queries`.
		// We made it due to improve performance of the report. Don't try to access these properties outside.
		$average_completion_days = $item->count_of_completions > 0 ? ceil( $item->days_to_completion / $item->count_of_completions ) : __( 'N/A', 'sensei-lms' );

		// Output course data.
		/** This filter is documented in wp-includes/post-template.php */
		$course_title = apply_filters( 'the_title', $item->post_title, $item->ID ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		if ( ! $this->csv_output ) {
			$url = add_query_arg(
				array(
					'page'      => $this->page_slug,
					'course_id' => $item->ID,
					'post_type' => $this->post_type,
				),
				admin_url( 'edit.php' )
			);

			$course_title = '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . $course_title . '</a></strong>';
		}

		$average_course_progress = $this->get_average_progress_for_courses_table( $item->ID );

		$column_data = apply_filters(
			'sensei_analysis_overview_column_data',
			array(
				'title'              => $course_title,
				'last_activity'      => $last_activity_date,
				'completions'        => $course_completions,
				'average_progress'   => $average_course_progress,
				'average_percent'    => $average_grade,
				'days_to_completion' => $average_completion_days,
			),
			$item,
			$this
		);

		$escaped_column_data = array();

		foreach ( $column_data as $key => $data ) {
			$escaped_column_data[ $key ] = wp_kses_post( $data );
		}

		return $escaped_column_data;
	}

	/**
	 * Get the date on which the last lesson was marked complete.
	 *
	 * @param array $args Array of arguments to pass to the comments query.
	 *
	 * @return string The last activity date, or N/A if none.
	 *
	 * @throws Exception If date-time conversion fails.
	 */
	private function get_last_activity_date( array $args ): string {
		$default_args  = array(
			'number' => 1,
			'type'   => 'sensei_lesson_status',
			'status' => [ 'complete', 'passed', 'graded' ],
		);
		$args          = wp_parse_args( $args, $default_args );
		$last_activity = Sensei_Utils::sensei_check_for_activity( $args, true );

		if ( ! $last_activity ) {
			return __( 'N/A', 'sensei-lms' );
		}

		// Return the full date when doing a CSV export.
		if ( $this->csv_output ) {
			return $last_activity->comment_date_gmt;
		}

		$timezone           = new DateTimeZone( 'GMT' );
		$now                = new DateTime( 'now', $timezone );
		$last_activity_date = new DateTime( $last_activity->comment_date_gmt, $timezone );
		$diff_in_days       = $now->diff( $last_activity_date )->days;

		// Show a human-readable date if activity is within 6 days.
		if ( $diff_in_days < 7 ) {
			return sprintf(
			/* translators: Time difference between two dates. %s: Number of seconds/minutes/etc. */
				__( '%s ago', 'sensei-lms' ),
				human_time_diff( strtotime( $last_activity->comment_date_gmt ) )
			);
		}

		return wp_date( get_option( 'date_format' ), $last_activity_date->getTimestamp(), $timezone );
	}

	/**
	 * Calculate average lesson progress per student for course.
	 *
	 * @since 4.3.0
	 *
	 * @param int $course_id Id of the course for which average progress is calculated.
	 *
	 * @return string The average progress for the course, or N/A if none.
	 */
	private function get_average_progress_for_courses_table( $course_id ) {
		// Fetch learners in course.
		$course_args = array(
			'post_id' => $course_id,
			'type'    => 'sensei_course_status',
			'status'  => array( 'in-progress', 'complete' ),
		);

		$course_students_count = Sensei_Utils::sensei_check_for_activity( $course_args );

		// Get all course lessons.
		$lessons        = Sensei()->course->course_lessons( $course_id, 'publish', 'ids' );
		$course_lessons = is_array( $lessons ) ? $lessons : array( $lessons );
		$total_lessons  = count( $course_lessons );

		// Get all completed lessons.
		$lesson_args     = array(
			'post__in' => $course_lessons,
			'type'     => 'sensei_lesson_status',
			'status'   => array( 'graded', 'ungraded', 'passed', 'failed', 'complete' ),
			'count'    => true,
		);
		$completed_count = (int) Sensei_Utils::sensei_check_for_activity( $lesson_args );
		// Calculate average progress.
		$average_course_progress = __( 'N/A', 'sensei-lms' );
		if ( $course_students_count && $total_lessons ) {
			// Average course progress is calculated based on lessons completed for the course
			// divided by the total possible lessons completed.
			$average_course_progress_value = $completed_count / ( $course_students_count * $total_lessons ) * 100;
			$average_course_progress       = esc_html(
				sprintf( '%d%%', round( $average_course_progress_value ) )
			);
		}
		return $average_course_progress;
	}

	/**
	 * The text for the search button.
	 *
	 * @return string
	 */
	public function search_button() {
		return __( 'Search Courses', 'sensei-lms' );
	}

	/**
	 * Return additional filters for current report.
	 *
	 * @return array
	 */
	protected function get_additional_filters(): array {
		return [
			'last_activity_date_from' => $this->get_start_date_and_time(),
			'last_activity_date_to'   => $this->get_end_date_and_time(),
		];
	}

	/**
	 * Get total average progress value for courses.
	 *
	 * @since  4.5.0
	 *
	 * @return float total average progress value for all the courses.
	 */
	private function get_total_average_progress(): float {
		$courses_ids = $this->get_all_courses_ids();
		if ( empty( $courses_ids ) ) {
			return false;
		}
		$lessons_count_per_courses = $this->get_lessons_in_courses( $courses_ids );
		$lessons_completions       = $this->get_lessons_completions();
		$student_count_per_courses = $this->get_students_count_in_courses( $courses_ids );
		$total_average_progress    = 0;

		foreach ( $courses_ids as $course_id ) {
			if ( ! isset( $lessons_count_per_courses[ $course_id ] ) || ! isset( $student_count_per_courses[ $course_id ] ) ) {
				continue;
			}
			// Get lessons in the course.
			$lessons = $lessons_count_per_courses[ $course_id ]->lessons;
			$lessons = array_map( 'intval', explode( ',', $lessons ) );
			if ( empty( $lessons ) ) {
				continue;
			}

			// Get students count.
			$students_count = $student_count_per_courses[ $course_id ]->students_count;
			if ( ! $students_count ) {
				continue;
			}

			// Get all completed lessons for all the students.
			$completed_count = array_reduce(
				$lessons,
				function ( $carry, $lesson ) use ( $lessons_completions ) {
					if ( ! isset( $lessons_completions[ $lesson ] ) ) {
						return $carry;
					}
					$carry += $lessons_completions[ $lesson ]->completion_count;
					return $carry;
				},
				0
			);

			// Calculate average progress for a course.
			$course_average_progress = $completed_count / ( $students_count * count( $lessons ) ) * 100;

			// Add value to the total average progress.
			$total_average_progress += $course_average_progress;
		}
		// Divide total value to get average total value for average progress for courses.
		$average_total_average_progress = ceil( $total_average_progress / count( $courses_ids ) );
		return $average_total_average_progress;
	}

	/**
	 * Get all lessons completions.
	 *
	 * @since  4.5.0
	 *
	 * @return array lessons completions.
	 */
	private function get_lessons_completions(): array {

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		return $wpdb->get_results(
			"SELECT wcom.comment_post_id lesson_id, COUNT(*) completion_count
						FROM {$wpdb->comments} wcom
						WHERE wcom.comment_approved IN ('graded', 'ungraded', 'passed', 'failed','complete')
						AND comment_type IN ('sensei_lesson_status')
						AND wcom.comment_post_ID IN
						(
						SELECT wpm.post_id lesson_id from {$wpdb->posts} wpc
						JOIN {$wpdb->postmeta} wpm on wpm.meta_value = wpc.id
						WHERE wpm.meta_key = '_lesson_course'
						AND wpc.post_status in ('publish','private')
						)
						GROUP BY wcom.comment_post_id",
			'OBJECT_K'
		);
	}

	/**
	 * Get all courses ids no pagination.
	 *
	 * @since  4.5.0
	 *
	 * @return array course ids array.
	 */
	private function get_all_courses_ids(): array {
		// Get all courses ids.
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ID',
		);

		$courses_query = new WP_Query( $args );

		return array_column( $courses_query->posts, 'ID' );
	}

	/**
	 * Get lessons grouped by courses.
	 *
	 * @since  4.5.0
	 *
	 * @param array $courses_ids The list of courses ids.
	 * @return array lessons count in courses.
	 */
	private function get_lessons_in_courses( $courses_ids ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		return $wpdb->get_results(
		// phpcs:ignore
			$wpdb->prepare(
				"SELECT pm.meta_value as course_id, GROUP_CONCAT(pm.post_id) as lessons
				FROM {$wpdb->postmeta} pm
				WHERE pm.meta_value IN (%1s)" // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- no need for quoting.
				. " AND pm.meta_key = '_lesson_course'
				GROUP BY pm.meta_value",
				implode( ',', $courses_ids )
			),
			'OBJECT_K'
		);
	}

	/**
	 * Get students count by courses.
	 *
	 * @since  4.5.0
	 *
	 * @param array $courses_ids The array of courses ids.
	 * @return array students in courses.
	 */
	private function get_students_count_in_courses( array $courses_ids ): array {

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.comment_post_ID as course_id, count(c.comment_post_ID) as students_count
					FROM {$wpdb->comments} c
					WHERE c.comment_post_ID IN (%1s)" // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- no quoting.
				. " AND c.comment_type = 'sensei_course_status'
					AND c.comment_approved IN ( 'in-progress', 'complete' )
					GROUP BY c.comment_post_ID",
				implode( ',', $courses_ids )
			),
			'OBJECT_K'
		);
	}
}
