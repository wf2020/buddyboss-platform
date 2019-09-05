<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;


/**
 * Remove BuddyPress Follow init hook action
 *
 * Support BuddyPress Follow
 */
remove_action( 'bp_include', 'bp_follow_init' );

/**
 * Remove message of BuddyPress Groups Export & Import
 *
 * Support BuddyPress Groups Export & Import
 */
remove_action( 'plugins_loaded', 'bpgei_plugin_init' );

/**
 * Fire to add support for third party plugin
 *
 * @since BuddyBoss 1.1.9
 */
function bp_helper_plugins_loaded_callback() {

	global $bp_plugins;

	/**
	 * Include plugin when plugin is activated
	 *
	 * Support for LearnDash & bbPress Integration
	 */
	if ( in_array( 'learndash-bbpress/learndash-bbpress.php', $bp_plugins ) ) {


            /**
             * Remove bbPress Integration admin init hook action
             *
             * Support bbPress Integration
             */
            remove_action( 'admin_init', 'wdm_activation_dependency_check' );

            if ( empty( bp_is_active( 'forums' ) ) || empty( in_array( 'sfwd-lms/sfwd_lms.php', $bp_plugins ) ) ) {
                deactivate_plugins( 'learndash-bbpress/learndash-bbpress.php' );

                add_action( 'admin_notices', 'bp_core_learndash_bbpress_notices' );
                add_action( 'network_admin_notices', 'bp_core_learndash_bbpress_notices' );
            }

	}

	/**
	 * Include plugin when plugin is activated
	 *
	 * Support Rank Math SEO
	 */
	if ( in_array( 'seo-by-rank-math/rank-math.php', $bp_plugins ) && ! is_admin() ) {
		require( buddypress()->plugin_dir . '/bp-core/compatibility/bp-rankmath-plugin-helpers.php' );
	}

	/**
	 * Include plugin when plugin is activated
	 *
	 * Support Co-Authors Plus
	 */
	if ( in_array( 'co-authors-plus/co-authors-plus.php', $bp_plugins ) ) {
		add_filter( 'bp_search_settings_post_type_taxonomies', 'bp_core_remove_authors_taxonomy_for_co_authors_plus',100 ,2 );
	}
}

add_action( 'init', 'bp_helper_plugins_loaded_callback', 1000 );

/**
 * On BuddyPress update
 *
 * @since BuddyBoss 1.0.9
 */
function bp_core_update_group_fields_id_in_db() {

	if ( is_multisite() ) {
		global $wpdb;
		$bp_prefix = bp_core_get_table_prefix();

		$table_name = $bp_prefix . 'bp_xprofile_fields';

		if ( empty( bp_xprofile_firstname_field_id( 0, false ) ) ) {
			//first name fields update
			$firstname = bp_get_option( 'bp-xprofile-firstname-field-name' );
			$results   = $wpdb->get_results( "SELECT id FROM {$table_name} WHERE name = '{$firstname}' AND can_delete = 0" );
			$count     = 0;
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$id = absint( $result->id );
					if ( empty( $count ) && ! empty( $id ) ) {
						add_site_option( 'bp-xprofile-firstname-field-id', $id );
						$count ++;
					} else {
						$wpdb->delete( $table_name, array( 'id' => $id ) );
					}
				}
			}
		}

		if ( empty( bp_xprofile_lastname_field_id( 0, false ) ) ) {
			//last name fields update
			$lastname = bp_get_option( 'bp-xprofile-lastname-field-name' );
			$results  = $wpdb->get_results( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE name = '{$lastname}' AND can_delete = 0" );
			$count    = 0;
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$id = absint( $result->id );
					if ( empty( $count ) && ! empty( $id ) ) {
						add_site_option( 'bp-xprofile-lastname-field-id', $id );
						$count ++;
					} else {
						$wpdb->delete( $table_name, array( 'id' => $id ) );
					}
				}
			}
		}

		if ( empty( bp_xprofile_nickname_field_id( true, false ) ) ) {
			//nick name fields update
			$nickname = bp_get_option( 'bp-xprofile-nickname-field-name' );
			$results  = $wpdb->get_results( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE name = '{$nickname}' AND can_delete = 0" );
			$count    = 0;
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$id = absint( $result->id );
					if ( empty( $count ) && ! empty( $id ) ) {
						add_site_option( 'bp-xprofile-nickname-field-id', $id );
						$count ++;
					} else {
						$wpdb->delete( $table_name, array( 'id' => $id ) );
					}
				}
			}
		}

		add_site_option( 'bp-xprofile-field-ids-updated', 1 );
	}
}

add_action( 'xprofile_admin_group_action', 'bp_core_update_group_fields_id_in_db', 100 );

/**
 * Remove the Author Taxonomies as that is added by Co-Authors Plus which is not used full.
 *
 * Support Co-Authors Plus
 *
 * @since 1.1.7
 *
 * @param array $taxonomies Taxonomies which are registered for the requested object or object type
 * @param array $post_type Post type
 *
 * @return array Return the names or objects of the taxonomies which are registered for the requested object or object type
 */
function bp_core_remove_authors_taxonomy_for_co_authors_plus( $taxonomies = array(), $post_type ) {

	delete_blog_option( bp_get_root_blog_id(), "bp_search_{$post_type}_tax_author" );
	return array_diff( $taxonomies, array( 'author' ) );
}

/**
 * Include plugin when plugin is activated
 *
 * Support Google Captcha Pro
 *
 * @since BuddyBoss 1.1.9
 */
function bp_core_add_support_for_google_captcha_pro( $section_notice, $section_slug ) {

	// check for BuddyPress plugin
	if ( 'buddypress' === $section_slug ) {
		$section_notice = '';
	}

	// check for bbPress plugin
	if ( 'bbpress' === $section_slug ) {
		$section_notice = '';
		if ( empty( bp_is_active( 'forums' ) ) ) {
			$section_notice = sprintf(
				'<a href="%s">%s</a>',
				bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), 'admin.php' ) ),
				__( 'Activate Forum Discussions Component', 'buddyboss' )
			);
		}
	}

	return $section_notice;

}

add_filter( 'gglcptch_section_notice', 'bp_core_add_support_for_google_captcha_pro', 100, 2 );


/**
 * Include plugin when plugin is activated
 *
 * Support LearnDash & bbPress Integration
 *
 * @since BuddyBoss 1.1.9
 */
function bp_core_learndash_bbpress_notices() {
    global $bp_plugins;

    if ( empty( bp_is_active( 'forums' ) ) ) {
        $links = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), 'admin.php' ) );

        $text = sprintf( '<a href="%s">%s</a>', $links, __( 'Forum Discussions', 'buddyboss' ) );
        $activate = sprintf( '<a href="%s">%s</a>', $links, __( 'activate', 'buddyboss' ) );
        ?>
        <div id="message" class="error notice">
            <p><strong><?php esc_html_e( 'LearnDash & bbPress Integration is deactivated.', 'buddyboss' ); ?></strong></p>

	        <?php if ( empty( in_array( 'sfwd-lms/sfwd_lms.php', $bp_plugins ) ) ) {
		        ?>
		        <p><?php printf( esc_html__( 'The LearnDash & bbPress Integration plugin can\'t work if LearnDash LMS plugin & %s component is deactivated. Please activate LearnDash LMS plugin & %s component.', 'buddyboss' ), $text, $text, $activate ); ?></p>
		        <?php
	        } else {
		        ?>
		        <p><?php printf( esc_html__( 'The LearnDash & bbPress Integration plugin can\'t work while %s component is deactivated. Please %s the component first before activating LearnDash & bbPress Integration.', 'buddyboss' ), $text, $activate ); ?></p><?php
	        } ?>
        </div>
        <?php
	}
}