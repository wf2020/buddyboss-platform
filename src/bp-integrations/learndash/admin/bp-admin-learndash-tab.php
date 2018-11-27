<?php

class BP_Learndash_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $groups_sync_option_key = 'learndash_settings_buddypress_groups_sync';
	protected $groups_report_option_key = 'learndash_settings_buddypress_groups_report';
	protected $current_section;

	public function initialize() {
		$this->intro_template  = buddypress()->integration_dir . '/learndash/admin/templates/tab-intro.php';
		$this->groups_sync_options = get_option( $this->groups_sync_option_key ) ?: [];
		$this->groups_report_options = get_option( $this->groups_report_option_key ) ?: [];

		add_action( 'admin_footer', [ $this, 'add_sync_tool_scripts' ], 20 );
	}

	public function settings_save() {
		if ( isset( $_POST[ $this->groups_sync_option_key ] ) ) {
			bp_update_option( $this->groups_sync_option_key, $_POST[ $this->groups_sync_option_key ] );
		}

		if ( isset( $_POST[ $this->groups_report_option_key ] ) ) {
			bp_update_option( $this->groups_report_option_key, $_POST[ $this->groups_report_option_key ] );
		}
	}

	public function register_fields() {
		/**
		 * Group Sync Options
		 */
		$this->current_section = 'groups_sync';

		$this->add_section(
			'ld-groups-sync',
			__( 'Groups Sync Global Settings', 'buddyboss' ),
			[ $this, 'learndash_groups_sync_description' ]
		);

		// On LearnDash Group Created...

		$this->add_checkbox_field(
			'auto_create_bp_group',
			__('Generate BuddyBoss Group', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Automatically generate and associate a BuddyBoss group upon LearnDash group creation. Uncheck this to create and associate the group manually. This is a global setting and can be overwritten on individual group.', 'buddyboss' )
			]
		);

		$this->add_select_field(
			'auto_bp_group_privacy',
			__('Generated BuddyBoss Group Privacy', 'buddyboss'),
			[
				'input_options' => [
		            'public'  => __( 'Public', 'learndash' ),
		            'private' => __( 'Private', 'learndash' ),
		            'hidden'  => __( 'Hidden', 'learndash' )
		        ],
		        'input_default' => 'private',
				'input_description' => __( 'When a BuddyBoss group is generated, set the group privacy to...', 'buddyboss' )
			]
		);

		$this->add_select_field(
			'auto_bp_group_privacy',
			__('Generated BuddyBoss Group Privacy', 'buddyboss'),
			[
				'input_options' => [
		            'public'  => __( 'Public', 'learndash' ),
		            'private' => __( 'Private', 'learndash' ),
		            'hidden'  => __( 'Hidden', 'learndash' )
		        ],
		        'input_default' => 'private',
				'input_description' => __( 'When a BuddyBoss group is generated, set the group privacy to...', 'buddyboss' )
			]
		);

		$this->add_select_field(
			'auto_bp_group_invite_status',
			__('Generated BuddyBoss Group Invite Status', 'buddyboss'),
			[
				'input_options' => [
	                'members' => __('All group members', 'learndash'),
	                'mods'    => __('Group admins and mods only', 'learndash'),
	                'admins'  => __('Group admins only', 'learndash')
		        ],
		        'input_default' => 'mods',
				'input_description' => __( 'When a BuddyBoss group is generated, set the group invite status to...', 'buddyboss' )
			]
		);

		// On LearnDash Group User Changed...

		$this->add_checkbox_field(
			'auto_sync_leaders',
			__('Sync LearnDash Group Leaders', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_default' => 1,
				'input_description' => __( 'Automatically sync LearnDash group leaders to BuddyBoss group admins/mods when LearnDash group is saved. Uncheck this to associate the BuddyBoss group admins/mods manually. This is a global setting and can be overwritten on individual group.', 'buddyboss' )
			]
		);

		$this->add_select_field(
			'auto_sync_leaders_role',
			__('LearnDash Group Leaders Role', 'buddyboss'),
			[
				'input_options' => [
	                'admin' => __('Administrator', 'learndash'),
	                'mod'   => __('Moderator', 'learndash'),
		        ],
		        'input_default' => 'admin',
				'input_description' => __( 'When a LearnDash leaders is synced, their role in BuddyBoss group should be...', 'buddyboss' )
			]
		);

		$this->add_checkbox_field(
			'auto_sync_students',
			__('Sync LearnDash Group Students', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_default' => 1,
				'input_description' => __( 'Automatically sync LearnDash group students to BuddyBoss group members when LearnDash group is saved. Uncheck this to associate the BuddyBoss group members manually. This is a global setting and can be overwritten on individual group.', 'buddyboss' )
			]
		);

		// On LearnDash Group Deleted...

		$this->add_checkbox_field(
			'auto_delete_bp_group',
			__('Delete BuddyBoss Group', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Automatically delete the associated BuddyBoss group upon LearnDash group deletion. Uncheck this to delete the group manually.', 'buddyboss' )
			]
		);

		/**
		 * Group Sync Options
		 */
		$this->current_section = 'groups_report';

		$this->add_section(
			'ld-groups-report',
			__( 'Groups Report Global Settings', 'buddyboss' ),
			[ $this, 'learndash_groups_report_description' ]
		);

		$this->add_checkbox_field(
			'enable_group_reports',
			__('Group Reports', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Enable BuddyBoss Group Report for LearnDash.', 'buddyboss' )
			]
		);

		$this->add_field(
			'report_access',
			__('Group Reports', 'buddyboss'),
			[ $this, 'output_report_access_setting' ]
		);
	}

	public function form_html() {
		if ( $this->required_plugin && ! is_plugin_active( $this->required_plugin ) ) {
			if ( is_file ( $this->intro_template ) ) {
				require $this->intro_template;
			}

			return;
		}

		parent::form_html();

		require bp_learndash_path('groups-sync/templates/admin/learndash-settings-tools.php');
	}

	public function learndash_groups_sync_description() {
		echo wpautop(
			__( 'Some description about groups sync', 'buddyboss' )
		);
	}

	public function learndash_groups_report_description() {
		echo wpautop(
			__( 'Some description about groups report', 'buddyboss' )
		);
	}

	public function output_report_access_setting() {
		$input_field = 'report_access';
		$input_value = $this->get_input_value( $input_field, [] );
		$input_name = $this->get_input_name( $input_field );
		$input_options = [
			'admin'     => __( 'Admin', 'buddyboss' ),
			'moderator' => __( 'Moderators', 'buddyboss' ),
			'member'    => __( 'Members', 'buddyboss' )
		];

        foreach ($input_options as $key => $value) {
        	$checked = in_array( $key, $input_value )? 'checked' : '';
        	printf( '
        		<p>
	        		<label>
	        			<input type="checkbox" name="%s[]" value="%s" %s>%s</option>
	        		</label>
	        	</p>
        	', $input_name, $key, $checked, $value );
        }

		echo $this->render_input_description(__('When a BuddyBoss group is generated, set the group privacy to...', 'buddyboss'));
	}

	public function add_sync_tool_scripts() {
		printf(
			'<script type="text/javascript" src="%s"></script>',
			add_query_arg(
				'ver',
				filemtime(bp_learndash_path('groups-sync/assets/js/admin/ld_bp_groups_sync-settings.js')),
				bp_learndash_url('groups-sync/assets/js/admin/ld_bp_groups_sync-settings.js')
			)
		);
	}

	protected function get_input_name( $name ) {
		$option_key = "{$this->current_section}_option_key";
		return "{$this->$option_key}[{$name}]";
	}

	protected function get_input_value( $key, $default = '' ) {
		$options = "{$this->current_section}_options";
		return isset($this->$options[$key])? $this->$options[$key] : $default;
	}
}

