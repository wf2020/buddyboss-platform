<?php
/**
 * BuddyBoss - Users Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php

switch ( bp_current_action() ) :

	// Home/Media
	case 'media':

		if ( bp_is_group_media() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) :
		    bp_get_template_part( 'media/add-media' );
		endif;

		bp_nouveau_group_hook( 'before', 'media_content' );

		?>
		<div id="media-stream" class="media" data-bp-list="media">

			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-media-loading' ); ?></div>

		</div><!-- .media -->
		<?php

		bp_nouveau_group_hook( 'after', 'media_content' );

		break;

	// Any other
	default:
		bp_get_template_part( 'groups/single/plugins' );
		break;
endswitch;
