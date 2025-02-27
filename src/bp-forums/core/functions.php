<?php

/**
 * Forums Core Functions
 *
 * @package BuddyBoss\Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Versions ******************************************************************/

/**
 * Output the Forums version
 *
 * @since bbPress (r3468)
 * @uses bbp_get_version() To get the Forums version
 */
function bbp_version() {
	echo bbp_get_version();
}
	/**
	 * Return the Forums version
	 *
	 * @since bbPress (r3468)
	 * @retrun string The Forums version
	 */
function bbp_get_version() {
	return bbpress()->version;
}

/**
 * Output the Forums database version
 *
 * @since bbPress (r3468)
 * @uses bbp_get_version() To get the Forums version
 */
function bbp_db_version() {
	echo bbp_get_db_version();
}
	/**
	 * Return the Forums database version
	 *
	 * @since bbPress (r3468)
	 * @retrun string The Forums version
	 */
function bbp_get_db_version() {
	return bbpress()->db_version;
}

/**
 * Output the Forums database version directly from the database
 *
 * @since bbPress (r3468)
 * @uses bbp_get_version() To get the current Forums version
 */
function bbp_db_version_raw() {
	echo bbp_get_db_version_raw();
}
	/**
	 * Return the Forums database version directly from the database
	 *
	 * @since bbPress (r3468)
	 * @retrun string The current Forums version
	 */
function bbp_get_db_version_raw() {
	return get_option( '_bbp_db_version', '' );
}

/** Post Meta *****************************************************************/

/**
 * Update a posts forum meta ID
 *
 * @since bbPress (r3181)
 *
 * @param int $post_id The post to update
 * @param int $forum_id The forum
 */
function bbp_update_forum_id( $post_id, $forum_id ) {

	// Allow the forum ID to be updated 'just in time' before save
	$forum_id = apply_filters( 'bbp_update_forum_id', $forum_id, $post_id );

	// Update the post meta forum ID
	update_post_meta( $post_id, '_bbp_forum_id', (int) $forum_id );
}

/**
 * Update a posts topic meta ID
 *
 * @since bbPress (r3181)
 *
 * @param int $post_id The post to update
 * @param int $forum_id The forum
 */
function bbp_update_topic_id( $post_id, $topic_id ) {

	// Allow the topic ID to be updated 'just in time' before save
	$topic_id = apply_filters( 'bbp_update_topic_id', $topic_id, $post_id );

	// Update the post meta topic ID
	update_post_meta( $post_id, '_bbp_topic_id', (int) $topic_id );
}

/**
 * Update a posts reply meta ID
 *
 * @since bbPress (r3181)
 *
 * @param int $post_id The post to update
 * @param int $forum_id The forum
 */
function bbp_update_reply_id( $post_id, $reply_id ) {

	// Allow the reply ID to be updated 'just in time' before save
	$reply_id = apply_filters( 'bbp_update_reply_id', $reply_id, $post_id );

	// Update the post meta reply ID
	update_post_meta( $post_id, '_bbp_reply_id', (int) $reply_id );
}

/** Views *********************************************************************/

/**
 * Get the registered views
 *
 * Does nothing much other than return the {@link $bbp->views} variable
 *
 * @since bbPress (r2789)
 *
 * @return array Views
 */
function bbp_get_views() {
	return bbpress()->views;
}

/**
 * Register a Forums view
 *
 * @todo Implement feeds - See {@link http://trac.bbpress.org/ticket/1422}
 *
 * @since bbPress (r2789)
 *
 * @param string $view View name
 * @param string $title View title
 * @param mixed  $query_args {@link bbp_has_topics()} arguments.
 * @param bool   $feed Have a feed for the view? Defaults to true. NOT IMPLEMENTED
 * @param string $capability Capability that the current user must have
 * @uses sanitize_title() To sanitize the view name
 * @uses esc_html() To sanitize the view title
 * @return array The just registered (but processed) view
 */
function bbp_register_view( $view, $title, $query_args = '', $feed = true, $capability = '' ) {

	// Bail if user does not have capability
	if ( ! empty( $capability ) && ! current_user_can( $capability ) ) {
		return false;
	}

	$bbp   = bbpress();
	$view  = sanitize_title( $view );
	$title = esc_html( $title );

	if ( empty( $view ) || empty( $title ) ) {
		return false;
	}

	$query_args = bbp_parse_args( $query_args, '', 'register_view' );

	// Set show_stickies to false if it wasn't supplied
	if ( ! isset( $query_args['show_stickies'] ) ) {
		$query_args['show_stickies'] = false;
	}

	$bbp->views[ $view ] = array(
		'title' => $title,
		'query' => $query_args,
		'feed'  => $feed,
	);

	return $bbp->views[ $view ];
}

/**
 * Deregister a Forums view
 *
 * @since bbPress (r2789)
 *
 * @param string $view View name
 * @uses sanitize_title() To sanitize the view name
 * @return bool False if the view doesn't exist, true on success
 */
function bbp_deregister_view( $view ) {
	$bbp  = bbpress();
	$view = sanitize_title( $view );

	if ( ! isset( $bbp->views[ $view ] ) ) {
		return false;
	}

	unset( $bbp->views[ $view ] );

	return true;
}

/**
 * Run the view's query
 *
 * @since bbPress (r2789)
 *
 * @param string $view Optional. View id
 * @param mixed  $new_args New arguments. See {@link bbp_has_topics()}
 * @uses bbp_get_view_id() To get the view id
 * @uses bbp_get_view_query_args() To get the view query args
 * @uses sanitize_title() To sanitize the view name
 * @uses bbp_has_topics() To make the topics query
 * @return bool False if the view doesn't exist, otherwise if topics are there
 */
function bbp_view_query( $view = '', $new_args = '' ) {

	$view = bbp_get_view_id( $view );
	if ( empty( $view ) ) {
		return false;
	}

	$query_args = bbp_get_view_query_args( $view );

	if ( ! empty( $new_args ) ) {
		$new_args   = bbp_parse_args( $new_args, '', 'view_query' );
		$query_args = array_merge( $query_args, $new_args );
	}

	return bbp_has_topics( $query_args );
}

/**
 * Return the view's query arguments
 *
 * @since bbPress (r2789)
 *
 * @param string $view View name
 * @uses bbp_get_view_id() To get the view id
 * @return array Query arguments
 */
function bbp_get_view_query_args( $view ) {
	$view   = bbp_get_view_id( $view );
	$retval = ! empty( $view ) ? bbpress()->views[ $view ]['query'] : false;

	return apply_filters( 'bbp_get_view_query_args', $retval, $view );
}

/** Errors ********************************************************************/

/**
 * Adds an error message to later be output in the theme
 *
 * @since bbPress (r3381)
 *
 * @see WP_Error()
 * @uses WP_Error::add();
 *
 * @param string $code Unique code for the error message
 * @param string $message Translated error message
 * @param string $data Any additional data passed with the error message
 */
function bbp_add_error( $code = '', $message = '', $data = '' ) {
	bbpress()->errors->add( $code, $message, $data );
}

/**
 * Check if error messages exist in queue
 *
 * @since bbPress (r3381)
 *
 * @see WP_Error()
 *
 * @uses is_wp_error()
 * @usese WP_Error::get_error_codes()
 */
function bbp_has_errors() {
	$has_errors = bbpress()->errors->get_error_codes() ? true : false;

	return apply_filters( 'bbp_has_errors', $has_errors, bbpress()->errors );
}

/** Mentions ******************************************************************/

/**
 * Set the pattern used for matching usernames for mentions.
 *
 * Moved into its own function to allow filtering of the regex pattern
 * anywhere mentions might be used.
 *
 * @since bbPress (r4997)
 * @deprecated 2.6.0 bbp_make_clickable()
 *
 * @return string Pattern to match usernames with
 */
function bbp_find_mentions_pattern() {
	return apply_filters( 'bbp_find_mentions_pattern', '/[@]+([A-Za-z0-9-_\.@]+)\b/' );
}

/**
 * Searches through the content to locate usernames, designated by an @ sign.
 *
 * @since bbPress (r4323)
 * @deprecated 2.6.0 bbp_make_clickable()
 *
 * @param string $content The content
 * @return bool|array $usernames Existing usernames. False if no matches.
 */
function bbp_find_mentions( $content = '' ) {
	$pattern = bbp_find_mentions_pattern();
	preg_match_all( $pattern, $content, $usernames );
	$usernames = array_unique( array_filter( $usernames[1] ) );

	// Bail if no usernames
	if ( empty( $usernames ) ) {
		$usernames = false;
	}

	return apply_filters( 'bbp_find_mentions', $usernames, $pattern, $content );
}

/**
 * Finds and links @-mentioned users in the content
 *
 * @since bbPress (r4323)
 * @deprecated 2.6.0 bbp_make_clickable()
 *
 * @uses bbp_find_mentions() To get usernames in content areas
 * @return string $content Content filtered for mentions
 */
function bbp_mention_filter( $content = '' ) {

	// Get Usernames and bail if none exist
	$usernames = bbp_find_mentions( $content );
	if ( empty( $usernames ) ) {
		return $content;
	}

	// Loop through usernames and link to profiles
	foreach ( (array) $usernames as $username ) {

		// Skip if username does not exist or user is not active
		$user = get_user_by( 'slug', $username );
		if ( empty( $user->ID ) || bbp_is_user_inactive( $user->ID ) ) {
			continue;
		}

		// Replace name in content
		$content = preg_replace( '/(@' . $username . '\b)/', sprintf( '<a href="%1$s" rel="nofollow">@%2$s</a>', bbp_get_user_profile_url( $user->ID ), $username ), $content );
	}

	// Return modified content
	return $content;
}

/** Post Statuses *************************************************************/

/**
 * Return the public post status ID
 *
 * @since bbPress (r3504)
 *
 * @return string
 */
function bbp_get_public_status_id() {
	return bbpress()->public_status_id;
}

/**
 * Return the pending post status ID
 *
 * @since bbPress (r3581)
 *
 * @return string
 */
function bbp_get_pending_status_id() {
	return bbpress()->pending_status_id;
}

/**
 * Return the private post status ID
 *
 * @since bbPress (r3504)
 *
 * @return string
 */
function bbp_get_private_status_id() {
	return bbpress()->private_status_id;
}

/**
 * Return the hidden post status ID
 *
 * @since bbPress (r3504)
 *
 * @return string
 */
function bbp_get_hidden_status_id() {
	return bbpress()->hidden_status_id;
}

/**
 * Return the closed post status ID
 *
 * @since bbPress (r3504)
 *
 * @return string
 */
function bbp_get_closed_status_id() {
	return bbpress()->closed_status_id;
}

/**
 * Return the spam post status ID
 *
 * @since bbPress (r3504)
 *
 * @return string
 */
function bbp_get_spam_status_id() {
	return bbpress()->spam_status_id;
}

/**
 * Return the trash post status ID
 *
 * @since bbPress (r3504)
 *
 * @return string
 */
function bbp_get_trash_status_id() {
	return bbpress()->trash_status_id;
}

/**
 * Return the orphan post status ID
 *
 * @since bbPress (r3504)
 *
 * @return string
 */
function bbp_get_orphan_status_id() {
	return bbpress()->orphan_status_id;
}

/** Rewrite IDs ***************************************************************/

/**
 * Return the unique ID for user profile rewrite rules
 *
 * @since bbPress (r3762)
 * @return string
 */
function bbp_get_user_rewrite_id() {
	return bbpress()->user_id;
}

/**
 * Return the unique ID for all edit rewrite rules (forum|topic|reply|tag|user)
 *
 * @since bbPress (r3762)
 * @return string
 */
function bbp_get_edit_rewrite_id() {
	return bbpress()->edit_id;
}

/**
 * Return the unique ID for all search rewrite rules
 *
 * @since bbPress (r4579)
 *
 * @return string
 */
function bbp_get_search_rewrite_id() {
	return bbpress()->search_id;
}

/**
 * Return the unique ID for user topics rewrite rules
 *
 * @since bbPress (r4321)
 * @return string
 */
function bbp_get_user_topics_rewrite_id() {
	return bbpress()->tops_id;
}

/**
 * Return the unique ID for user replies rewrite rules
 *
 * @since bbPress (r4321)
 * @return string
 */
function bbp_get_user_replies_rewrite_id() {
	return bbpress()->reps_id;
}

/**
 * Return the unique ID for user caps rewrite rules
 *
 * @since bbPress (r4181)
 * @return string
 */
function bbp_get_user_favorites_rewrite_id() {
	return bbpress()->favs_id;
}

/**
 * Return the unique ID for user caps rewrite rules
 *
 * @since bbPress (r4181)
 * @return string
 */
function bbp_get_user_subscriptions_rewrite_id() {
	return bbpress()->subs_id;
}

/**
 * Return the unique ID for topic view rewrite rules
 *
 * @since bbPress (r3762)
 * @return string
 */
function bbp_get_view_rewrite_id() {
	return bbpress()->view_id;
}

/** Rewrite Extras ************************************************************/

/**
 * Get the id used for paginated requests
 *
 * @since bbPress (r4926)
 * @return string
 */
function bbp_get_paged_rewrite_id() {
	return bbpress()->paged_id;
}

/**
 * Get the slug used for paginated requests
 *
 * @since bbPress (r4926)
 * @global object $wp_rewrite The WP_Rewrite object
 * @return string
 */
function bbp_get_paged_slug() {
	global $wp_rewrite;
	return $wp_rewrite->pagination_base;
}

/**
 * Return the rewrite rules class being used to interact with URLs.
 *
 * This function is abstracted to avoid global touches to the primary rewrite
 * rules class. bbPress supports WordPress's `$wp_rewrite` by default, but can
 * be filtered to support other configurations if needed.
 *
 * @since 2.5.8 bbPress (r5814)
 *
 * @return object
 */
function bbp_rewrite() {
	return bbp_get_global_object(
		'wp_rewrite',
		'WP_Rewrite',
		(object) array(
			'root'            => '',
			'pagination_base' => 'page',
		)
	);
}

/**
 * Remove the first-page from a pagination links result set, ensuring that it
 * points to the canonical first page URL.
 *
 * This is a bit of an SEO hack, to guarantee that the first page in a loop will
 * never have pagination appended to the end of it, regardless of what the other
 * functions have decided for us.
 *
 * @since 2.6.0 bbPress (r6678)
 *
 * @param string $pagination_links The HTML links used for pagination.
 *
 * @return string
 */
function bbp_make_first_page_canonical( $pagination_links = '' ) {

	// Default value.
	$retval = $pagination_links;

	// Remove first page from pagination.
	if ( ! empty( $pagination_links ) ) {
		$retval = bbp_use_pretty_urls()
			? str_replace( bbp_get_paged_slug() . '/1/', '', $pagination_links )
			: preg_replace( '/&#038;paged=1(?=[^0-9])/m', '', $pagination_links );
	}

	// Filter & return.
	return apply_filters( 'bbp_make_first_page_canonical', $retval, $pagination_links );
}

/**
 * A convenient wrapper for common calls to paginate_links(), complete with
 * support for parameters that aren't used internally by bbPress.
 *
 * @since 2.6.0 bbPress (r6679)
 *
 * @param array $args Array of arguments.
 *
 * @return string
 */
function bbp_paginate_links( $args = array() ) {

	// Maybe add view-all args.
	$add_args = empty( $args['add_args'] ) && bbp_get_view_all()
		? array( 'view' => 'all' )
		: false;

	// Pagination settings with filter.
	$r = bbp_parse_args(
		$args,
		array(

			// Used by callers.
			'base'               => '',
			'total'              => 1,
			'current'            => bbp_get_paged(),
			'prev_next'          => true,
			'prev_text'          => is_rtl() ? '&rarr;' : '&larr;',
			'next_text'          => is_rtl() ? '&larr;' : '&rarr;',
			'mid_size'           => 1,
			'end_size'           => 3,
			'add_args'           => $add_args,

			// Unused by callers.
			'show_all'           => false,
			'type'               => 'plain',
			'format'             => '',
			'add_fragment'       => '',
			'before_page_number' => '',
			'after_page_number'  => '',
		),
		'paginate_links'
	);

	// Return paginated links.
	return bbp_make_first_page_canonical( paginate_links( $r ) );
}

/**
 * Get the `$wp_query` global without needing to declare it everywhere
 *
 * @since 2.6.0 bbPress (r6582)
 *
 * @return WP_Roles
 */
function bbp_get_wp_query() {
	return bbp_get_global_object( 'wp_query', 'WP_Query' );
}

/**
 * Lookup and return a global variable
 *
 * @since 2.5.8 bbPress (r5814)
 *
 * @param  string $name     Name of global variable.
 * @param  string $type     Type of variable to check with `is_a()`.
 * @param  mixed  $default  Default value to return if no global found.
 *
 * @return mixed   Verified object if valid, Default or null if invalid
 */
function bbp_get_global_object( $name = '', $type = '', $default = null ) {

	// If no name passed.
	if ( empty( $name ) ) {
		$retval = $default;

		// If no global exists.
	} elseif ( ! isset( $GLOBALS[ $name ] ) ) {
		$retval = $default;

		// If not the correct type of global.
	} elseif ( ! empty( $type ) && ! is_a( $GLOBALS[ $name ], $type ) ) {
		$retval = $default;

		// Global variable exists.
	} else {
		$retval = $GLOBALS[ $name ];
	}

	// Filter & return.
	return apply_filters( 'bbp_get_global_object', $retval, $name, $type, $default );
}

/**
 * Return the database class being used to interface with the environment.
 *
 * This function is abstracted to avoid global touches to the primary database
 * class. bbPress supports WordPress's `$wpdb` global by default, and can be
 * filtered to support other configurations if needed.
 *
 * @since 2.5.8 bbPress (r5814)
 * @since BuddyBoss 2.3.90
 *
 * @return object
 */
function bbp_db() {
	return bbp_get_global_object( 'wpdb', 'WPDB' );
}

/**
 * Is the environment using pretty URLs?
 *
 * @since 2.5.8 bbPress (r5814)
 *
 * @global object $wp_rewrite The WP_Rewrite object
 *
 * @return bool
 */
function bbp_use_pretty_urls() {

	// Default.
	$retval  = false;
	$rewrite = bbp_rewrite();

	// Use $wp_rewrite->using_permalinks() if available.
	if ( method_exists( $rewrite, 'using_permalinks' ) ) {
		$retval = $rewrite->using_permalinks();
	}

	// Filter & return.
	return apply_filters( 'bbp_pretty_urls', $retval );
}

/**
 * Delete a blogs rewrite rules, so that they are automatically rebuilt on
 * the subsequent page load.
 *
 * @since bbPress (r4198)
 */
function bbp_delete_rewrite_rules() {
	delete_option( 'rewrite_rules' );
}

/** Requests ******************************************************************/

/**
 * Return true|false if this is a POST request
 *
 * @since bbPress (r4790)
 * @return bool
 */
function bbp_is_post_request() {
	return (bool) ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}

/**
 * Return true|false if this is a GET request
 *
 * @since bbPress (r4790)
 * @return bool
 */
function bbp_is_get_request() {
	return (bool) ( 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}

/**
 * Fix forums media
 *
 * @since BuddyBoss 1.2.3
 */
function bbp_fix_forums_media() {

	$forums_media_query = new WP_Query(
		array(
			'post_type'      => bbp_get_forum_post_type(),
			'fields'         => 'ids',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'bp_media_ids',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( ! empty( $forums_media_query->found_posts ) && ! empty( $forums_media_query->posts ) ) {

		foreach ( $forums_media_query->posts as $post_id ) {
			$media_ids = get_post_meta( $post_id, 'bp_media_ids', true );

			if ( ! empty( $media_ids ) ) {
				$media_ids = explode( ',', $media_ids );
				foreach ( $media_ids as $media_id ) {
					$media          = new BP_Media( $media_id );
					$media->privacy = 'forums';
					$media->save();
				}
			}
		}
	}
	wp_reset_postdata();

	$topics_media_query = new WP_Query(
		array(
			'post_type'      => bbp_get_topic_post_type(),
			'fields'         => 'ids',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'bp_media_ids',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( ! empty( $topics_media_query->found_posts ) && ! empty( $topics_media_query->posts ) ) {

		foreach ( $topics_media_query->posts as $post_id ) {
			$media_ids = get_post_meta( $post_id, 'bp_media_ids', true );

			if ( ! empty( $media_ids ) ) {
				$media_ids = explode( ',', $media_ids );
				foreach ( $media_ids as $media_id ) {
					$media          = new BP_Media( $media_id );
					$media->privacy = 'forums';
					$media->save();
				}
			}
		}
	}
	wp_reset_postdata();

	$reply_media_query = new WP_Query(
		array(
			'post_type'      => bbp_get_reply_post_type(),
			'fields'         => 'ids',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'bp_media_ids',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( ! empty( $reply_media_query->found_posts ) && ! empty( $reply_media_query->posts ) ) {

		foreach ( $reply_media_query->posts as $post_id ) {
			$media_ids = get_post_meta( $post_id, 'bp_media_ids', true );

			if ( ! empty( $media_ids ) ) {
				$media_ids = explode( ',', $media_ids );
				foreach ( $media_ids as $media_id ) {
					$media          = new BP_Media( $media_id );
					$media->privacy = 'forums';
					$media->save();
				}
			}
		}
	}
	wp_reset_postdata();
}

/**
 * Parse the WordPress core version number
 *
 * @since 2.6.0 bbPress (r6051)
 *
 * @global string $wp_version
 *
 * @return string $wp_version
 */
function bbp_get_major_wp_version() {
	global $wp_version;

	return (float) $wp_version;
}

/** Multisite *****************************************************************/

/**
 * Is this a large bbPress installation?
 *
 * @since 2.6.0 bbPress (r6242)
 *
 * @return bool True if more than 10000 users, false not
 */
function bbp_is_large_install() {

	// Multisite has a function specifically for this.
	$retval = function_exists( 'wp_is_large_network' )
		? wp_is_large_network( 'users' )
		: ( bbp_get_total_users() > 10000 );

	// Filter & return.
	return (bool) apply_filters( 'bbp_is_large_install', $retval );
}

/**
 * Switch to a site in a multisite installation.
 *
 * If not a multisite installation, no switching will occur.
 *
 * @since 2.6.0 bbPress (r6733)
 *
 * @param int $site_id Site ID.
 */
function bbp_switch_to_site( $site_id = 0 ) {

	// Switch to a specific site.
	if ( is_multisite() ) {
		switch_to_blog( $site_id );
	}
}

/**
 * Switch back to the original site in a multisite installation.
 *
 * If not a multisite installation, no switching will occur.
 *
 * @since 2.6.0 bbPress (r6733)
 */
function bbp_restore_current_site() {

	// Switch back to the original site.
	if ( is_multisite() ) {
		restore_current_blog();
	}
}

/** Interception **************************************************************/

/**
 * Generate a default intercept value.
 *
 * @since 2.6.0
 *
 * @staticvar mixed $rand Null by default, random string on first call
 *
 * @return string
 */
function bbp_default_intercept() {
	static $rand = null;

	// Generate a new random and unique string.
	if ( null === $rand ) {

		// If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
		$algo = function_exists( 'hash' )
			? 'sha256'
			: 'sha1';

		// Old WP installs may not have AUTH_SALT defined.
		$salt = defined( 'AUTH_SALT' ) && AUTH_SALT
			? AUTH_SALT
			: (string) wp_rand();

		// Create unique ID.
		$rand = hash_hmac( $algo, uniqid( $salt, true ), $salt );
	}

	// Return random string (from locally static variable).
	return $rand;
}

/**
 * Whether a value has been intercepted
 *
 * @since 2.6.0
 *
 * @param bool $value
 */
function bbp_is_intercepted( $value = '' ) {
	return ( bbp_default_intercept() !== $value );
}

/**
 * Allow interception of a method or function call.
 *
 * @since 2.6.0
 *
 * @param string $action Typically the name of the caller function.
 * @param array  $args   Typically the results of caller function func_get_args().
 *
 * @return mixed         Intercept results. Default bbp_default_intercept().
 */
function bbp_maybe_intercept( $action = '', $args = array() ) {

	// Backwards compatibility juggle.
	$hook = ( false === strpos( $action, 'pre_' ) )
		? "pre_{$action}"
		: $action;

	// Default value.
	$default = bbp_default_intercept();

	// Parse args.
	$r = bbp_parse_args( (array) $args, array(), 'maybe_intercept' );

	// Bail if no args.
	if ( empty( $r ) ) {
		return $default;
	}

	// Filter.
	$args     = array_merge( array( $hook ), $r );
	$filtered = call_user_func_array( 'apply_filters', $args );

	// Return filtered value, or default if not intercepted.
	return ( reset( $r ) === $filtered )
		? $default
		: $filtered;
}

/** Date/Time *****************************************************************/

/**
 * Get an empty datetime value.
 *
 * @since 2.6.6 bbPress (r7094)
 *
 * @return string
 */
function bbp_get_empty_datetime() {

	// Get the database version.
	$db_version = bbp_db()->db_version();

	// Default return value.
	$retval = '0000-00-00 00:00:00';

	// Filter & return.
	return (string) apply_filters( 'bbp_get_default_zero_date', $retval, $db_version );
}

/**
 * Get default forum image URL.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param string $size This parameter specifies whether you'd like the 'full' or 'thumb' avatar. Default: 'full'.
 *
 * @return string Return default forum image URL.
 */
function bb_get_forum_default_image( $size = 'full' ) {
	$filename = 'bb-default-forum.png';
	if ( 'full' !== $size ) {
		$filename = 'bb-default-forum-150.png';
	}
	/**
	 * Filters default forum image URL.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default forum image URL.
	 * @param string $size  This parameter specifies whether you'd like the 'full' or 'thumb' avatar.
	 */
	return apply_filters( 'bb_get_forum_default_image', esc_url( buddypress()->plugin_url . 'bp-core/images/' . $filename ), $size );
}

/**
 * Perform a safe, local redirect somewhere inside the current site.
 *
 * On some setups, passing the value of wp_get_referer() may result in an empty
 * value for $location, which results in an error on redirection. If $location
 * is empty, we can safely redirect back to the forum root. This might change
 * in a future version, possibly to the site root.
 *
 * @since 2.6.0 bbPress (r5658)
 * @since BuddyBoss 2.3.4
 *
 * @see   bbp_redirect_to_field()
 *
 * @param string $location The URL to redirect the user to.
 * @param int    $status   Optional. The numeric code to give in the redirect
 *                         headers. Default: 302.
 */
function bbp_redirect( $location = '', $status = 302 ) {

	// Prevent errors from empty $location.
	if ( empty( $location ) ) {
		$location = bbp_get_forums_url();
	}

	// Setup the safe redirect.
	wp_safe_redirect( $location, $status );

	// Exit so the redirect takes place immediately.
	exit();
}

/**
 * Function to check the forums favourite legacy is enabled or not.
 *
 * @since BuddyBoss 2.3.4
 *
 * @todo Legacy support will disable after certain version.
 *
 * @return bool True if forums favourite legacy is enabled otherwise false.
 */
function bb_forum_favourite_legacy_data_support() {
	return (bool) apply_filters( 'bb_forum_favourite_legacy_data_support', true );
}
