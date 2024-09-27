<?php
/**
 * A2Z Alphabetical Archive Links Background Activation Handler
 *
 * @package     NVWD\A2ZAAL
 * @since       2.0.0
 * @author      nvwd
 * @link        http://nvwebdev.com/
 * @license     GPL-2.0+
 */

namespace NVWD\A2ZAAL;


class A2ZAAL_Post_Type_Background_Process extends \WP_Background_Process {

	/**
	 * @var string prefix for batch job name
	 */
	protected $prefix = 'a2zaal';

	/**
	 * @var string  used to create a unique identifier for this background process
	 */
	protected $action = 'post_type_activation';

	/**
	 * @var int number of rows to process for each batch job iteration
	 */
	protected $activation_process_count;

	/**
	 * A2ZAAL_Post_Type_Background_Process constructor.
	 */
	public function __construct() {

		parent::__construct();

		$this->init();

	}

	/**
	 * set class variables and add action/filter hooks for processing post type activation
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @return void
	 */
	protected function init() {
		/**
		 * Filters how many records to process per each batch job
		 *
		 * @since: 2.0.0
		 *
		 * @param   int     number of records to process per each batch job
		 */
		$this->activation_process_count = \apply_filters( 'a2zaal_activation_process_count', 50 );

		add_action( 'a2zaal_setup_background_processes', array( $this, 'process_background_setup_request' ) );

		add_action( 'a2zaal_deactivation', array( $this, 'remove_background_processing' ) );

		// TODO: create action for heartbeat API to report post types processing
		add_action( 'heartbeat_received', array( $this, 'maybe_handle_heartbeat' ), 10, 2 );

	}

	/**
	 * handle each batch job request
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @param   array   $data   post type and the database offset to start this iteration
	 *
	 * @return  bool|array      false to remove the batch job, $data to push it back onto the queue
	 */
	protected function task( $data ) {

		$this->process_activated_cpt_posts( $data['post_type'], $data['offset'] );

		return false;

	}

	/**
	 * create batch jobs to process the activated post type
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @param   array   $post_types     activated post type(s)
	 *
	 * @return  void
	 */
	public function process_background_setup_request( $post_types = array() ) {

		if ( empty( $post_types ) ) {
			error_log( 'data was missing' );
			return;
		}

		$processing_count = array();

		foreach ( $post_types AS $post_type ) {

			$post_count = wp_count_posts( $post_type )->publish;

			if ( 0 == $post_count ) {
				continue;
			}

			$current_offset = 0;
			$processing_count['post_type'][$post_type] = 0;

			while ( $current_offset <= $post_count ) {
				$data = array(
					'post_type'      => $post_type,
					'offset'         => $current_offset,
					'num_to_process' => $this->activation_process_count
				);

				$this->push_to_queue( $data );

				$current_offset += $this->activation_process_count;
				$processing_count['post_type'][$post_type]++;
			}
		}

		update_option( 'a2zaal_processing_counts', $processing_count, true );

		$this->save()->dispatch();
	}

	/**
	 * Process the specified number of records for an activated post type
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @param   string  $post_type    the post type being activated
	 * @param   int     $offset       the starting point in the records for this iteration
	 *
	 * @return void
	 */
	function process_activated_cpt_posts( $post_type, $offset ) {
		$get_posts_args = array(
			'post_type' => $post_type,
			'no_found_rows' => true,
			'update_post_meta_cache' => false,
			'posts_per_page' => $this->activation_process_count,
			'offset' => $offset
		);

		$posts = \get_posts( $get_posts_args );

		foreach ( $posts AS $post ) {
			namespace\add_post_a2zaal_info( $post );
		}

		$processing_count = get_option( 'a2zaal_processing_counts' );
		$processing_count['post_type'][$post_type]--;

		if ( 1 > $processing_count['post_type'][$post_type] ) {
			unset( $processing_count['post_type'][$post_type] );
		}

		update_option( 'a2zaal_processing_counts', $processing_count );

		return;
	}

	/**
	 * remove all background processing if/when deactivated
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @return void
	 */
	function remove_background_processing() {
		$this->cancel_process();
		delete_option( 'a2zaal_processing_counts' );
	}

	/**
	 * Respond to the Heartbeat API if there are any background processes running
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @param   array   $response   array of responses sent back to the heartbeat process
	 * @param   array   $data       data sent to the server by the heartbeat
	 *
	 * @return  array               $response is returned
	 */
	function maybe_handle_heartbeat( $response, $data ) {

		if ( empty( $data['a2zaal_background_processing_check'] ) ) {
			return $response;
		}

		// get post_types that are being processed
		$a2zaal_background_processing = \get_option( 'a2zaal_processing_counts', array() );

		if ( empty( $a2zaal_background_processing ) || empty( $a2zaal_background_processing['post_type'] ) ) {
			if ( empty( $response['heartbeat_interval'] ) ) {
				$response['heartbeat_interval'] = 'slow';
			}
			return $response;
		}

		$response['a2zaal_background_processing_response'] = array();

		// get total and processed counts
		foreach ( $a2zaal_background_processing['post_type'] AS $post_type => $process_count ) {
			if ( 0 < $process_count ) {
				$response['a2zaal_background_processing_response']['post_type'][] = $post_type;
			}
		}

		$response['heartbeat_interval'] = 'fast';
		// return status text for each post type being processed
		return $response;
	}
}

new namespace\A2ZAAL_Post_Type_Background_Process();
