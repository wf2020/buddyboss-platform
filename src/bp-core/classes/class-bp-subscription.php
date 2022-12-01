<?php
/**
 * Subscription class
 *
 * @package BuddyBoss\Subscription
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Subscription' ) ) {

	/**
	 * BuddyBoss Subscription object.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	class BP_Subscription {

		/**
		 * ID of the subscriptions.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @var int
		 */
		public $id;

		/**
		 * User ID.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @var int
		 */
		public $user_id;

		/**
		 * Subscription type.
		 *
		 * Core statuses are 'forum', 'topic'.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @var string
		 */
		public $type;

		/**
		 * ID of forum/topic.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @var int
		 */
		public $item_id;

		/**
		 * ID of parent forum.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @var int
		 */
		public $secondary_item_id;

		/**
		 * Date the subscription was created.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @var string
		 */
		public $date_recorded;

		/**
		 * Name of subscription table.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @var string
		 */
		public $tbl;

		/**
		 * Raw arguments passed to the constructor.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @var array
		 */
		public $args;

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int|null $id   Optional. If the ID of an existing subscriptions is provided,
		 *                       the object will be pre-populated with info about that subscriptions.
		 */
		public function __construct( $id = null ) {
			$this->tbl = self::get_subscription_tbl();

			if ( ! empty( $id ) ) {
				$this->id = (int) $id;
				$this->populate();
			}
		}

		/**
		 * Set up data about the current subscriptions.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function populate() {
			global $wpdb;

			// Check cache for subscription data.
			$subscription = wp_cache_get( $this->id, 'bb_subscriptions' );

			// Cache missed, so query the DB.
			if ( false === $subscription ) {
				$subscription = $wpdb->get_row( $wpdb->prepare( "SELECT s.* FROM {$this->tbl} s WHERE s.id = %d", $this->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				wp_cache_set( $this->id, $subscription, 'bb_subscriptions' );
			}

			// No subscription found so set the ID and bail.
			if ( empty( $subscription ) || is_wp_error( $subscription ) ) {
				$this->id = 0;
				return;
			}

			/**
			 * Pre validate the subscription before fetch.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param boolean $validate     Whether to check the subscriptions is valid or not.
			 * @param object  $subscription Subscription object.
			 */
			$validate = apply_filters( 'bb_subscriptions_pre_validate', true, $subscription );

			if ( empty( $validate ) ) {
				$this->id = 0;
				return;
			}

			// Subscription found so setup the object variables.
			$this->id                = (int) $subscription->id;
			$this->user_id           = (int) $subscription->user_id;
			$this->type              = $subscription->type;
			$this->item_id           = (int) $subscription->item_id;
			$this->secondary_item_id = (int) $subscription->secondary_item_id;
			$this->date_recorded     = $subscription->date_recorded;
		}

		/**
		 * Save the current subscription to the database.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True on success, false on failure.
		 */
		public function save() {
			global $wpdb;

			$bp = buddypress();

			$this->user_id           = apply_filters( 'bb_subscriptions_user_id_before_save', $this->user_id, $this->id );
			$this->type              = apply_filters( 'bb_subscriptions_type_before_save', $this->type, $this->id );
			$this->item_id           = apply_filters( 'bb_subscriptions_item_id_before_save', $this->item_id, $this->id );
			$this->secondary_item_id = apply_filters( 'bb_subscriptions_secondary_item_id_before_save', $this->secondary_item_id, $this->id );
			$this->date_recorded     = apply_filters( 'bb_subscriptions_date_recorded_before_save', $this->date_recorded, $this->id );

			/**
			 * Fires before the current subscription item gets saved.
			 *
			 * Please use this hook to filter the properties above. Each part will be passed in.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param BP_Subscription $this Current instance of the subscription item being saved. Passed by reference.
			 */
			do_action_ref_array( 'bb_subscriptions_before_save', array( &$this ) );

			// Subscription need at least user ID, Type and Item ID.
			if ( empty( $this->user_id ) || empty( $this->type ) || empty( $this->item_id ) ) {
				return false;
			}

			if ( ! empty( $this->id ) ) {
				$sql = $wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"UPDATE {$this->tbl} SET
					user_id = %d,
					type = %s,
					item_id = %d,
					secondary_item_id = %d,
					date_recorded = %s
				WHERE
					id = %d
				",
					$this->user_id,
					$this->type,
					$this->item_id,
					$this->secondary_item_id,
					$this->date_recorded,
					$this->id
				);
			} else {
				$sql = $wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"INSERT INTO {$this->tbl} (
					user_id,
					type,
					item_id,
					secondary_item_id,
					date_recorded
				) VALUES (
					%d, %s, %d, %d, %s
				)",
					$this->user_id,
					$this->type,
					$this->item_id,
					$this->secondary_item_id,
					$this->date_recorded
				);
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( false === $wpdb->query( $sql ) ) {
				return false;
			}

			if ( empty( $this->id ) ) {
				$this->id = $wpdb->insert_id;
			}

			/**
			 * Fires after the current subscription item has been saved.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param BP_Subscription $this Current instance of the subscription item that was saved. Passed by reference.
			 */
			do_action_ref_array( 'bb_subscriptions_after_save', array( &$this ) );

			wp_cache_delete( $this->id, 'bb_subscriptions' );

			return true;
		}

		/**
		 * Delete the current subscription.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True on success, false on failure.
		 */
		public function delete() {
			global $wpdb;

			/**
			 * Fires before the deletion of a subscriptions.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param BP_Subscription $this Current instance of the subscription item being deleted. Passed by reference.
			 * @param array           $id   ID of subscription.
			 */
			do_action_ref_array( 'bb_subscriptions_delete_subscription', array( &$this, $this->id ) );

			wp_cache_delete( $this->id, 'bb_subscriptions' );

			// Finally, remove the subscription entry from the DB.
			if ( ! $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tbl} WHERE id = %d", $this->id ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
				return false;
			}

			return true;
		}

		/**
		 * Magic getter.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $key Property name.
		 * @return mixed
		 */
		public function __get( $key ) {
			return $this->{$key} ?? null;
		}

		/**
		 * Magic issetter.
		 *
		 * Used to maintain backward compatibility for properties that are now
		 * accessible only via magic method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $key Property name.
		 * @return bool
		 */
		public function __isset( $key ) {
			return isset( $this->{$key} );
		}

		/**
		 * Magic setter.
		 *
		 * Used to maintain backward compatibility for properties that are now
		 * accessible only via magic method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $key   Property name.
		 * @param mixed  $value Property value.
		 */
		public function __set( $key, $value ) {
			$this->{$key} = $value;
		}

		/** Static Methods ****************************************************/
		/**
		 * Query for subscriptions.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args {
		 *     Array of parameters. All items are optional.
		 *
		 *     @type array|string $type               Optional. Array or comma-separated list of subscription types.
		 *                                            'Forum', 'topic', 'group', 'activity', 'activity_comment'.
		 *                                            Default: null.
		 *     @type int          $user_id            Optional. If provided, results will be limited to subscriptions.
		 *                                            Default: Current user ID.
		 *     @type int          $item_id            Optional. If provided, results will be limited to subscriptions.
		 *                                            Default: null.
		 *     @type int          $secondary_item_id  Optional. If provided, results will be limited to subscriptions.
		 *                                            Default: null.
		 *      @type string      $order_by           Optional. Property to sort by. 'date_recorded', 'item_id',
		 *                                            'secondary_item_id', 'user_id', 'id', 'type'
		 *                                            'total_subscription_count', 'random', 'include'.
		 *                                            Default: 'date_recorded'.
		 *     @type string       $order              Optional. Sort order. 'ASC' or 'DESC'. Default: 'DESC'.
		 *     @type int          $per_page           Optional. Number of items to return per page of results.
		 *                                            Default: null (no limit).
		 *     @type int          $page               Optional. Page offset of results to return.
		 *     @type array|string $include            Optional. Array or comma-separated list of subscription IDs.
		 *                                            Results will exclude the listed subscriptions. Default: false.
		 *     @type array|string $exclude            Optional. Array or comma-separated list of subscription IDs.
		 *                                            Results will exclude the listed subscriptions. Default: false.
		 *     @type string       $fields             Which fields to return. Specify 'ids' to fetch a list of IDs.
		 *                                            Default: 'all' (return BP_Subscription objects).
		 *     @type bool         $no_count           Optional. Fetch total count of all subscriptions matching non-
		 *                                            paginated query params when it false.
		 *                                            Default: true.
		 *     @type bool         $no_cache           Optional. Fetch the fresh result instead of cache when it true.
		 *                                            Default: false.
		 * }
		 * @return array {
		 *     @type array $subscriptions Array of subscription objects returned by the
		 *                                paginated query. (IDs only if `fields` is set to `ids`.)
		 *     @type int   $total         Total count of all subscriptions matching non-
		 *                                paginated query params.
		 * }
		 */
		public static function get( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'type'              => array(),
				'user_id'           => bp_loggedin_user_id(),
				'item_id'           => 0,
				'secondary_item_id' => 0,
				'order_by'          => 'date_recorded',
				'order'             => 'DESC',
				'per_page'          => null,
				'page'              => null,
				'include'           => false,
				'exclude'           => false,
				'fields'            => 'all',
				'no_count'          => true,
				'no_cache'          => false,
			);

			$r = bp_parse_args( $args, $defaults, 'bb_subscriptions_subscription_get' );

			$subscription_tbl = self::get_subscription_tbl();

			$results = array();
			$sql     = array(
				'select'     => 'SELECT DISTINCT s.id',
				'from'       => $subscription_tbl . ' s',
				'where'      => '',
				'order_by'   => '',
				'pagination' => '',
			);

			$where_conditions = array();

			if ( ! empty( $r['type'] ) ) {
				if ( ! is_array( $r['type'] ) ) {
					$r['type'] = preg_split( '/[\s,]+/', $r['type'] );
				}
				$r['type']                = array_map( 'sanitize_title', $r['type'] );
				$type_in                  = "'" . implode( "','", $r['type'] ) . "'";
				$where_conditions['type'] = "s.type IN ({$type_in})";
			}

			if ( ! empty( $r['user_id'] ) ) {
				$where_conditions['user_id'] = $wpdb->prepare( 's.user_id = %d', $r['user_id'] );
			}

			if ( ! empty( $r['item_id'] ) ) {
				$where_conditions['item_id'] = $wpdb->prepare( 's.item_id = %d', $r['item_id'] );
			}

			if ( ! empty( $r['secondary_item_id'] ) ) {
				$where_conditions['secondary_item_id'] = $wpdb->prepare( 's.secondary_item_id = %d', $r['secondary_item_id'] );
			}

			if ( ! empty( $r['include'] ) ) {
				$include                     = implode( ',', wp_parse_id_list( $r['include'] ) );
				$where_conditions['include'] = "s.id IN ({$include})";
			}

			if ( ! empty( $r['exclude'] ) ) {
				$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
				$where_conditions['exclude'] = "s.id NOT IN ({$exclude})";
			}

			/* Order/orderby ********************************************/
			$order           = bp_esc_sql_order( $r['order'] );
			$order_by         = $r['order_by'];
			$sql['order_by'] = "ORDER BY {$order_by} {$order}";

			// Random order is a special case.
			if ( 'rand()' === $order_by ) {
				$sql['order_by'] = 'ORDER BY rand()';
			} elseif ( ! empty( $r['include'] ) && 'in' === $order_by ) { // Support order by fields for generally.
				$field_data     = implode( ',', array_map( 'absint', $r['include'] ) );
				$sql['order_by'] = "ORDER BY FIELD(s.id, {$field_data})";
			}

			if ( ! empty( $r['per_page'] ) && ! empty( $r['page'] ) && -1 !== $r['per_page'] ) {
				$sql['pagination'] = $wpdb->prepare( 'LIMIT %d, %d', intval( ( $r['page'] - 1 ) * $r['per_page'] ), intval( $r['per_page'] ) );
			}

			/**
			 * Filters the Where SQL statement.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param array $r                Array of parsed arguments for the get method.
			 * @param array $where_conditions Where conditions SQL statement.
			 */
			$where_conditions = apply_filters( 'bb_subscriptions_get_where_conditions', $where_conditions, $r );

			$where = '';
			if ( ! empty( $where_conditions ) ) {
				$sql['where'] = implode( ' AND ', $where_conditions );
				$where        = "WHERE {$sql['where']}";
			}

			/**
			 * Filters the From SQL statement.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param array $r    Array of parsed arguments for the get method.
			 * @param string $sql From SQL statement.
			 */
			$sql['from'] = apply_filters( 'bb_subscriptions_get_join_sql', $sql['from'], $r );

			$paged_subscriptions_sql = "{$sql['select']} FROM {$sql['from']} {$where} {$sql['order_by']} {$sql['pagination']}";
			/**
			 * Filters the pagination SQL statement.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param string $value Concatenated SQL statement.
			 * @param array  $sql   Array of SQL parts before concatenation.
			 * @param array  $r     Array of parsed arguments for the get method.
			 */
			$paged_subscriptions_sql = apply_filters( 'bb_subscriptions_get_paged_subscriptions_sql', $paged_subscriptions_sql, $sql, $r );

			$cached = bp_core_get_incremented_cache( $paged_subscriptions_sql, 'bb_subscriptions' );
			if ( false === $cached || $r['no_cache'] ) {
				$paged_subscription_ids = $wpdb->get_col( $paged_subscriptions_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				bp_core_set_incremented_cache( $paged_subscriptions_sql, 'bb_subscriptions', $paged_subscription_ids );
			} else {
				$paged_subscription_ids = $cached;
			}

			if ( 'ids' === $r['fields'] ) {
				// We only want the IDs.
				$paged_subscriptions = array_map( 'intval', $paged_subscription_ids );
			} else {
				$uncached_subscription_ids = bp_get_non_cached_ids( $paged_subscription_ids, 'bb_subscriptions' );
				if ( $uncached_subscription_ids ) {
					$subscription_ids_sql      = implode( ',', array_map( 'intval', $uncached_subscription_ids ) );
					$subscription_data_objects = $wpdb->get_results( "SELECT s.* FROM {$subscription_tbl} s WHERE s.id IN ({$subscription_ids_sql})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					foreach ( $subscription_data_objects as $subscription_data_object ) {
						wp_cache_set( $subscription_data_object->id, $subscription_data_object, 'bb_subscriptions' );
					}
				}

				$paged_subscriptions = array();
				foreach ( $paged_subscription_ids as $paged_subscription_id ) {
					$paged_subscriptions[] = new BP_Subscription( $paged_subscription_id );
				}
			}
			// Set in response array.
			$results['subscriptions'] = $paged_subscriptions;

			// If no_count is false then will get total subscription counts.
			if ( ! $r['no_count'] ) {
				// Find the total number of subscriptions in the results set.
				$total_subscriptions_sql = "SELECT COUNT(DISTINCT s.id) FROM {$sql['from']} $where";

				/**
				 * Filters the SQL used to retrieve total subscriptions results.
				 *
				 * @since BuddyBoss [BBVERSION]
				 *
				 * @param string $t_sql     Concatenated SQL statement used for retrieving total subscriptions results.
				 * @param array  $total_sql Array of SQL parts for the query.
				 * @param array  $r         Array of parsed arguments for the get method.
				 */
				$total_subscriptions_sql = apply_filters( 'bb_subscriptions_get_total_subscriptions_sql', $total_subscriptions_sql, $sql, $r );

				$cached = bp_core_get_incremented_cache( $total_subscriptions_sql, 'bb_subscriptions' );
				if ( false === $cached || $r['no_cache'] ) {
					$total_subscriptions = (int) $wpdb->get_var( $total_subscriptions_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					bp_core_set_incremented_cache( $total_subscriptions_sql, 'bb_subscriptions', array( $total_subscriptions ) );
				} else {
					$total_subscriptions = (int) $cached[0];
				}

				// Set in response array.
				$results['total'] = $total_subscriptions;
			}

			return $results;
		}

		/**
		 * Get database table name for subscription.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return string.
		 */
		public static function get_subscription_tbl() {
			global $wpdb;
			return "{$wpdb->prefix}bb_notifications_subscriptions";
		}
	}
}
