<?php

/* Instantiate the class. */
global $cpt_onomies_admin_settings;
$cpt_onomies_admin_settings = new CPT_ONOMIES_ADMIN_SETTINGS();

/**
 * Holds the functions needed for the admin settings page.
 *
 * @since 1.0
 */
class CPT_ONOMIES_ADMIN_SETTINGS {
	
	public $options_page;

	/**
	 * Adds WordPress hooks (actions and filters).
	 *
	 * This function is only run in the admin.
	 *
	 * @since 1.0
	 */
	public function CPT_ONOMIES_ADMIN_SETTINGS() { $this->__construct(); }
	public function __construct() {
		if ( is_admin() ) {
			
			// adds a settings link to the plugins page
			add_filter( 'plugin_action_links', array( &$this, 'add_plugin_action_links' ), 10, 2 );
		
			// register user settings
			add_action( 'admin_init', array( &$this, 'register_user_settings' ) );
			
			// add plugin options page
			add_action( 'admin_menu', array( &$this, 'add_plugin_options_page' ) );
			add_action( 'admin_head-settings_page_'.CPT_ONOMIES_OPTIONS_PAGE, array( &$this, 'add_plugin_options_page_meta_boxes' ) );
			add_action( 'admin_init', array( &$this, 'save_plugin_options_page' ) );
					
			// add styles and scripts for plugin options page
			add_action( 'admin_print_styles-settings_page_'.CPT_ONOMIES_OPTIONS_PAGE, array( &$this, 'add_plugin_options_styles' ) );
			add_action( 'admin_print_scripts-settings_page_'.CPT_ONOMIES_OPTIONS_PAGE, array( &$this, 'add_plugin_options_scripts' ) );
			
			// ajax functions for plugin options pages
			add_action( 'wp_ajax_custom_post_type_onomy_validate_if_post_type_exists', array( &$this, 'validate_plugin_options_if_post_type_exists' ) );
			add_action( 'wp_ajax_custom_post_type_onomy_update_edit_custom_post_type_closed_edit_tables', array( &$this, 'update_plugin_options_edit_custom_post_type_closed_edit_tables' ) );
					
		}
	}
	
	/**
	 * Adds a settings link to plugins page.
	 * 
	 * @since 1.0
	 * @param $links - the links info already created by WordPress
	 * @param $file - the plugin's main file
	 * @return array - the links info after it has been filtered	 
	 */
	public function add_plugin_action_links( $links, $file ) {
		if ( $file == 'cpt-onomies/cpt-onomies.php' )
			$links['settings'] = '<a href="options-general.php?page=' . CPT_ONOMIES_OPTIONS_PAGE . '" title="' . sprintf( esc_attr__( 'Visit the %s settings page', CPT_ONOMIES_TEXTDOMAIN ), CPT_ONOMIES_PLUGIN_SHORT_NAME ) . '">' . __( 'Settings' , CPT_ONOMIES_TEXTDOMAIN ) . '</a>';
		return $links;
	}
	
	/**
	 * Registers user's plugin settings.
	 *
	 * This function is invoked by the action 'admin_init'.
	 *
	 * @since 1.0
	 */
	public function register_user_settings() {
		register_setting( CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types', CPT_ONOMIES_UNDERSCORE . '_custom_post_types', array( &$this, 'validate_plugin_options_custom_post_types' ) );
		register_setting( CPT_ONOMIES_OPTIONS_PAGE . '-other-custom-post-types', CPT_ONOMIES_UNDERSCORE . '_other_custom_post_types', array( &$this, 'validate_plugin_options_other_custom_post_types' ) );
	}
	
	/**
	 * This function allows the settings page to detect if we
	 * are editing a custom post type, and whether that post type is
	 * 'new' or an 'other' post type.
	 * 
	 * We can't just check for post types that exist because 
	 * we allow the user to 'deactivate' post types so we need to 
	 * check the settings.
	 *
	 * @since 1.1
	 * @uses $cpt_onomies_manager
	 * @return array of 'new', 'edit' and 'other' info
	 */	
	private function detect_custom_post_type_new_edit_other() {
		global $cpt_onomies_manager;
				
		// figuring out if it's new is pretty simple
		$new = ( isset( $_REQUEST[ 'edit' ] ) && strtolower( $_REQUEST[ 'edit' ] ) == 'new' ) ? true : false;
		
		// if its not new, then check to see if the name exists in the settings
		$edit = ( !$new && isset( $_REQUEST[ 'edit' ] ) && ( ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( strtolower( $_REQUEST[ 'edit' ] ), $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) || ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) && array_key_exists( strtolower( $_REQUEST[ 'edit' ] ), $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) ) || ( post_type_exists( strtolower( $_REQUEST[ 'edit' ] ) ) ) ) ) ? strtolower( $_REQUEST[ 'edit' ] ) : false;
				
		// we need to know if the custom post type was created by our plugin, or someone else
		$other = ( !$new && $edit && isset( $_REQUEST[ 'other' ] ) && ( post_type_exists( $edit ) ||  isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) && array_key_exists( $edit, $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) ) ) ? true : false;
					
		return array( 'new' => $new, 'edit' => $edit, 'other' => $other );
	}
	
	/**
	 * This function allows the settings page to detect if we
	 * are editing a custom post type, and whether that post type is
	 * 'new' or an 'other' post type.
	 * 
	 * We can't just check for post types that exist because 
	 * we allow the user to 'deactivate' post types so we need to 
	 * check the settings.
	 *
	 * @since 1.2
	 * @uses $cpt_onomies_manager
	 * @return array of 'inactive_cpt', 'is_registered_cpt', 'is_registered_cpt_onomy',
	 * 		'programmatic_cpt_onomy', 'should_be_cpt_onomy', 'attention_cpt' and 'attention_cpt_onomy'
	 */	
	private function detect_custom_post_type_message_variables( $post_type, $CPT, $other ) {
		global $cpt_onomies_manager;
		
		$inactive_cpt = isset( $CPT->deactivate ) ? true : false;
										
		$is_registered_cpt = ( post_type_exists( $post_type ) && ( ( !$other && $cpt_onomies_manager->is_registered_cpt( $post_type ) ) || ( $other && !$cpt_onomies_manager->is_registered_cpt( $post_type ) ) ) ) ? true : false;
										
		$is_registered_cpt_onomy = ( $is_registered_cpt && taxonomy_exists( $post_type ) && $cpt_onomies_manager->is_registered_cpt_onomy( $post_type ) ) ? true : false;
										
		$programmatic_cpt_onomy = ( $is_registered_cpt_onomy && !get_taxonomy( $post_type )->created_by_cpt_onomies ) ? true : false;
										
		$should_be_cpt_onomy = ( isset( $CPT->attach_to_post_type ) && !empty( $CPT->attach_to_post_type ) ) ? true : false;
										
		$attention_cpt = ( !$inactive_cpt && !$is_registered_cpt ) ? true : false;
										
		$attention_cpt_onomy = ( !$inactive_cpt && $should_be_cpt_onomy && ( $attention_cpt || !$is_registered_cpt_onomy ) ) ? true : false;
		
		return array(
			'inactive_cpt' => $inactive_cpt,
			'is_registered_cpt' => $is_registered_cpt,
			'is_registered_cpt_onomy' => $is_registered_cpt_onomy,
			'programmatic_cpt_onomy' => $programmatic_cpt_onomy,
			'should_be_cpt_onomy' => $should_be_cpt_onomy,
			'attention_cpt' => $attention_cpt,
			'attention_cpt_onomy' => $attention_cpt_onomy
			);
	}
	
	/**
	 * This ajax function is run on the "edit" custom post type page.
	 * It tells the script whether or not the post type name the 
	 * user is trying to enter already exists.
	 *
	 * It checks using the function post_type_exists() and looks for the post type
	 * in the user's settings. There's no need to check the "other" post types because
	 * these post types are tested by post_type_exists() while post types created by
	 * this plugin could be "deactivated" so we need to check the settings.
	 *
	 * This function is invoked by the action 'wp_ajax_custom_post_type_onomy_post_type_exists'.
	 *
	 * @since 1.0
	 */
	public function validate_plugin_options_if_post_type_exists() {
		global $cpt_onomies_manager;
		$original_custom_post_type_name = ( isset( $_POST[ 'original_custom_post_type_onomies_cpt_name' ] ) && !empty( $_POST[ 'original_custom_post_type_onomies_cpt_name' ] ) ) ? $_POST[ 'original_custom_post_type_onomies_cpt_name' ] : NULL;
		$custom_post_type_name = ( isset( $_POST[ 'custom_post_type_onomies_cpt_name' ] ) && !empty( $_POST[ 'custom_post_type_onomies_cpt_name' ] ) ) ? $_POST[ 'custom_post_type_onomies_cpt_name' ] : NULL;
		if ( !empty( $original_custom_post_type_name ) && !empty( $custom_post_type_name ) && $custom_post_type_name != $original_custom_post_type_name && ( post_type_exists( $custom_post_type_name ) || ( array_key_exists( $custom_post_type_name, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) ) )
			echo 'false';
		else if ( empty( $original_custom_post_type_name ) && !empty( $custom_post_type_name ) && ( post_type_exists( $custom_post_type_name ) || ( array_key_exists( $custom_post_type_name, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) ) )
			echo 'false';
		else
			echo 'true';
		die();
	}
	
	/**
	 * This ajax function is run on the "edit" custom post type page.
	 * It detects when the user has "opened" or "closed" an advanced
	 * edit table and updates the user_option accordingly.
	 *
	 * This function is invoked by the action 'wp_ajax_custom_post_type_onomy_update_edit_custom_post_type_closed_edit_tables'.
	 *
	 * @since 1.0
	 * @uses $user_ID
	 */
	public function update_plugin_options_edit_custom_post_type_closed_edit_tables() {
		global $user_ID;
		$edit_table = $_POST[ 'custom_post_type_onomies_edit_table' ];
		if ( !empty( $edit_table ) ) {
			$show = $_POST[ 'custom_post_type_onomies_edit_table_show' ];
			if ( $show == 'true' ) $show = true;
			else $show = false;			
			// get set option
			$option_name = CPT_ONOMIES_UNDERSCORE . '_show_edit_tables';
			$saved_option = get_user_option( $option_name, $user_ID );
			// we need to make sure its saved into the array
			if ( $show ) {
				if ( empty( $saved_option ) || ( !empty( $saved_option ) && !in_array( $edit_table, $saved_option ) ) )
					$saved_option[] = $edit_table;
			}
			// we need to make sure its removed from the array
			else if ( !empty( $saved_option ) && in_array( $edit_table, $saved_option ) ) {
				foreach( $saved_option as $key => $value ) {
					if ( $value == $edit_table )
						unset( $saved_option[ $key ] );
				}
			}
			update_user_option( $user_ID, $option_name, $saved_option, true );
		}
		die();
	}
		
	/**
	 * This function validates the 'custom_post_types' setting anytime update_option() is run.
	 * This includes saving the "edit" options page, when a plugin CPT is deleted on the options
	 * page and when a plugin CPT is activated (by link) on the options page.
	 *
	 * If saving the "edit" options page and a new custom post type is added,
	 * the function will edit the redirect to show new CPT.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 * @param array $custom_post_types - the custom post type setting that is being updated
	 * @return array - validated custom post type information
	 */
	public function validate_plugin_options_custom_post_types( $custom_post_types ) {
		global $cpt_onomies_manager;
		// make sure we're saving "edit" options page
		if ( current_user_can( 'manage_options' ) && isset( $_POST[ 'option_page' ] ) && $_POST[ 'option_page' ] == CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types' && isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'update' && !empty( $custom_post_types ) ) {
		
			// get saved info
			$saved_post_types = ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) ? $cpt_onomies_manager->user_settings[ 'custom_post_types' ] : array();
							
			// if set, will redirect settings page to show specified custom post type
			$redirect_cpt = NULL;
			
			foreach( $custom_post_types as $cpt_key => $cpt ) {
				
				// sanitize the data
				foreach( $cpt as $key => $data ) {
					if ( !is_array( $data ) )
						$cpt[ $key ] = strip_tags( $data );
				}
				
				// Maximum is 20 characters. Can only contain lowercase, alphanumeric characters and underscores
				$valid_name_preg_test = '/([^a-z0-9\_])/i';
				
				$original_name = ( isset( $cpt[ 'original_name' ] ) && !empty( $cpt[ 'original_name' ] ) && strlen( $cpt[ 'original_name' ] ) <= 20 && !preg_match( $valid_name_preg_test, $cpt[ 'original_name' ] ) ) ? strtolower( $cpt[ 'original_name' ] ) : NULL;
				$new_name = ( isset( $cpt[ 'name' ] ) && !empty( $cpt[ 'name' ] ) && strlen( $cpt[ 'name' ] ) <= 20 && !preg_match( $valid_name_preg_test, $cpt[ 'name' ] ) ) ? strtolower( $cpt[ 'name' ] ) : NULL;
				$label = ( isset( $cpt[ 'label' ] ) && !empty( $cpt[ 'label' ] ) ) ? $cpt[ 'label' ] : NULL;
				
				// if no valid name or label, why bother so remove the data
				if ( empty( $original_name ) && empty( $new_name ) && empty( $label ) ) {
					
					unset( $custom_post_types[ $cpt_key ] );
					$redirect_cpt = 'new';
					
					// add a settings error to let the user know it was a no go
					add_settings_error( CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types', CPT_ONOMIES_DASH . '-custom-post-types-error', __( 'You must provide a valid "Label" or "Name" for the custom post type to be saved.', CPT_ONOMIES_TEXTDOMAIN ), 'error' );
				
				}
				
				else {
			
					// remove names from info
					if ( isset( $cpt[ 'original_name' ] ) )
						unset( $cpt[ 'original_name' ] );
						
					// if no label, then add 'Posts'
					if ( !isset( $cpt[ 'label' ] ) || empty( $cpt[ 'label' ] ) )
						$cpt[ 'label' ] = 'Posts';
						
					// will be the name and key for storing data
					$store_name = NULL;
					
					// if no original name (new) and new name is empty OR already exists,
					// take the label and create a name
					if ( empty( $original_name ) && ( empty( $new_name ) || ( !empty( $new_name ) && array_key_exists( $new_name, $saved_post_types ) ) ) ) {
					
						// convert spaces to underscores first
						$made_up_orig = $made_up_name = substr( strtolower( preg_replace( $valid_name_preg_test, '', str_replace( ' ', '_', $cpt[ 'label' ] ) ) ), 0, 20 );
						$made_up_index = 1;
						while( post_type_exists( $made_up_name ) || array_key_exists( $made_up_name, $saved_post_types ) ) {
							$made_up_name = $made_up_orig . $made_up_index;
							$made_up_index++;				
						}
						
						$store_name = $made_up_name;
						
						// the following adds a settings error to let the user know we made up our own name
						
						// they included a name but it was invalid so we made one up
						if ( isset( $cpt[ 'name' ] ) && !empty( $cpt[ 'name' ] ) && empty( $new_name ) ) {
							add_settings_error( CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types', CPT_ONOMIES_DASH . '-custom-post-types-error', sprintf( __( 'The "name" you provided for your custom post type was invalid so %1$s just made one up. If %2$s doesn\'t work for you, then make sure you edit the name property below.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies', '"' . $store_name . '"' ), 'error' );
						}
						// the name was empty so we made one up
						else if ( empty( $new_name ) )
							add_settings_error( CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types', CPT_ONOMIES_DASH . '-custom-post-types-error', sprintf( __( 'You did not provide a "name" for your custom post type so %1$s just made one up. If %2$s doesn\'t work for you, then make sure you edit the name property below.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies', '"' . $store_name . '"' ), 'error' );
						// the name is already taken so we made one up
						else
							add_settings_error( CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types', CPT_ONOMIES_DASH . '-custom-post-types-error', sprintf( __( 'The "name" you provided for your custom post type was already taken so CPT-onomies just made one up. If %2$s doesn\'t work for you, then make sure you edit the name property below.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies', '"' . $store_name . '"' ), 'error' );
							
					}
					else {
						
						// if no original name (new) and new name exists then save under new name
						if ( empty( $original_name ) && !empty( $new_name ) )
							$store_name = $new_name;
							
						// if no new name and original name exists then save under original name
						else if ( empty( $new_name ) && !empty( $original_name ) ) 
							$store_name = $original_name;
							
						// if both original and new name exist and new is different from original
						// BUT new name already exists elsewhere
						else if ( !empty( $original_name ) && !empty( $new_name ) && $new_name != $original_name && array_key_exists( $new_name, $saved_post_types ) ) {
							
							// store under original name
							$store_name = $original_name;
							
							// let the user know why the change didn't stick
							add_settings_error( CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types', CPT_ONOMIES_DASH . '-custom-post-types-error', sprintf( __( 'The new "name" you provided for your custom post type was already taken so %s restored the original name.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies', '"' . $store_name . '"' ), 'error' );
														
						}
						
						// if both original and new name exist and new is different from original
						// then remove info with original name and save under new name
						else if ( !empty( $original_name ) && !empty( $new_name ) && $new_name != $original_name ) {
							
							// remove original name
							unset( $saved_post_types[ $original_name ] );
							
							$store_name = $new_name;
							
						}
						
						// no conflicts. save info under name new
						else
							$store_name = $new_name;
						
					}
										
					// clean up the capability type
					if ( isset( $cpt[ 'capability_type' ] ) && !empty( $cpt[ 'capability_type' ] ) ) {
						// can be separated by space or comma
						$cpt[ 'capability_type' ] = str_replace( ', ', ',', trim( $cpt[ 'capability_type' ] ) );
						$cpt[ 'capability_type' ] = str_replace( ' ', ',', trim( $cpt[ 'capability_type' ] ) );
						$cpt[ 'capability_type' ] = explode( ',', $cpt[ 'capability_type' ] );
						// only save as array if more than one capability type
						if ( count( $cpt[ 'capability_type' ] ) < 2 ) {
							if ( count( $cpt[ 'capability_type' ] ) == 1 )
								$cpt[ 'capability_type' ] = array_shift( $cpt[ 'capability_type' ] );
							else
								$cpt[ 'capability_type' ] = NULL; 
						}
					}
					
					// validating
					if ( isset( $cpt[ 'register_meta_box_cb' ] ) && !empty( $cpt[ 'register_meta_box_cb' ] ) )
						$cpt[ 'register_meta_box_cb' ] = preg_replace( '/([^a-z0-9\_])/i', '', $cpt[ 'register_meta_box_cb' ] );
					
					// must be numeric
					if ( isset( $cpt[ 'menu_position' ] ) && !empty( $cpt[ 'menu_position' ] ) && is_numeric( $cpt[ 'menu_position' ] ) )
						$cpt[ 'menu_position' ] = intval( $cpt[ 'menu_position' ] );
					else if ( isset( $cpt[ 'menu_position' ] ) && !empty( $cpt[ 'menu_position' ] ) )
						unset( $cpt[ 'menu_position' ] );
					
					// store data
					$cpt[ 'name' ] = $store_name;
					$saved_post_types[ $store_name ] = $cpt;
					
					// redirect
					$redirect_cpt = $store_name;
					
				}
				
			}
	
			// sort custom post types (alphabetically) by post type
			ksort( $saved_post_types );
			
			// change the referer URL to change cpt=new to cpt=[new cpt] so that redirect will show recently added cpt
			if ( isset( $redirect_cpt ) )
				$_REQUEST['_wp_http_referer'] = preg_replace( '/(\&edit\=([^\&]*))/i', '&edit='.$redirect_cpt, $_REQUEST['_wp_http_referer'] );
			
			return $saved_post_types;
			
		}
		return $custom_post_types;
	}
	
	/**
	 * This function validates the "other" custom post types setting anytime update_option() is run.
	 * This function is run on the options page.
	 *
	 * If the "other" custom post type no longer exists, it deletes the settings from the DB.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 * @param array $other_custom_post_types - the other custom post type setting that is being updated
	 * @return array - validated custom post type information
	 */
	public function validate_plugin_options_other_custom_post_types( $other_custom_post_types ) {
		global $cpt_onomies_manager;		
		// make sure we're saving edit page
		// we need these parameters because this function is called whenever update_option is called for our 'other_custom_post_types' option so we only want these tests run when the edit screen is saved
		if ( current_user_can( 'manage_options' ) && isset( $_POST[ 'option_page' ] ) && $_POST[ 'option_page' ] == CPT_ONOMIES_OPTIONS_PAGE . '-other-custom-post-types' && isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'update' ) {
			
			$saved_other_post_types = ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) ) ? $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] : array();
					
			// save information
			if ( !empty( $other_custom_post_types ) ) {
				foreach( $other_custom_post_types as $cpt_key => $cpt ) {
					$saved_other_post_types[ $cpt_key ] = $cpt;
				}
			}
			
			// post types that no longer exist are removed from the settings
			foreach( $saved_other_post_types as $cpt_key => $cpt ) {
				$post_type_exists = post_type_exists( $cpt_key );
				if ( !$post_type_exists || ( $post_type_exists && ( $cpt_onomies_manager->is_registered_cpt( $cpt_key ) ) ) )
					unset( $saved_other_post_types[ $cpt_key ] );
			}
				
			// sort custom post types (alphabetically) by post type
			ksort( $saved_other_post_types );
			
			return $saved_other_post_types;
			
		}
		return $other_custom_post_types;
	}
			
	/**
	 * Returns an object that contains the fields/properties
	 * for creating the admin table for creating/managing custom post types.
	 *
	 * This function is only invoked on the plugin's options page and is only
	 * available for users who have capability to 'manage_options'.
	 *
	 * As of version 1.2, you can customize yours settings by removing options
	 * and setting default property values using various filters.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 * @param string $post_type_being_edited - the custom post type that's being edited. NULL if creating a new custom post type.
	 * @return object - the custom post type properties
	 * @filters 'custom_post_type_onomies_attach_to_post_type_property_include_post_type' - $post_type_to_include, $post_type_being_edited
	 *		'custom_post_type_onomies_taxonomies_property_include_taxonomy' - $taxonomy, $post_type_being_edited
	 *		'custom_post_type_onomies_restrict_user_capabilities_property_include_user_role' - $user_role, $post_type_being_edited
	 *		'custom_post_type_onomies_supports_property_include_support' - $support, $post_type_being_edited
	 */
	public function get_plugin_options_page_cpt_properties( $post_type_being_edited = NULL ) {
		global $cpt_onomies_manager;
		if ( current_user_can( 'manage_options' ) ) {
		
			// gather post type data to use in 'attach_post_type' property
			$attach_to_post_type_data = array();
			foreach( get_post_types( array(), 'objects' ) as $cpt_key => $cpt ) {
				// do not include 'attachment', aka media, nav menu items or revisions
				// this filter allows you to remove particular post types from the list
				if ( !empty( $cpt_key ) && !in_array( $cpt_key, array( 'attachment', 'nav_menu_item', 'revision' ) ) && !empty( $cpt->labels->name )
					&& apply_filters( 'custom_post_type_onomies_attach_to_post_type_property_include_post_type', true, $cpt_key, $post_type_being_edited ) ) {
					$attach_to_post_type_data[ $cpt_key ] = (object) array(
						'label' => __( $cpt->labels->name, CPT_ONOMIES_TEXTDOMAIN )
					);
				}
			}
			
			// get deactivated post types created by plugin
			foreach( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] as $cpt_key => $cpt ) {
				if ( isset( $cpt[ 'deactivate' ] ) && $cpt[ 'deactivate' ] ) {
					if ( !array_key_exists( $cpt_key, $attach_to_post_type_data ) ) {
						$attach_to_post_type_data[ $cpt_key ] = (object) array(
							'label' => sprintf( __( $cpt[ 'label' ] . ' %1$sdeactivated%2$s', CPT_ONOMIES_TEXTDOMAIN ), '<span class="gray"><em>(', ')</em></span>' )
						);
					}
				}
			}
			
			// add post type names that are saved and no longer exist
			if ( $post_type_being_edited ) {
				$stored_attach_to_post_type = array();
				if ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( $post_type_being_edited, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $post_type_being_edited ][ 'attach_to_post_type' ] ) )
					$stored_attach_to_post_type = $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $post_type_being_edited ][ 'attach_to_post_type' ];
				else if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) && array_key_exists( $post_type_being_edited, $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) && isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type_being_edited ][ 'attach_to_post_type' ] ) )
					$stored_attach_to_post_type = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type_being_edited ][ 'attach_to_post_type' ];
				if ( !empty( $stored_attach_to_post_type ) ) {
					foreach( $stored_attach_to_post_type as $cpt_key ) {
						if ( !array_key_exists( $cpt_key, $attach_to_post_type_data ) ) {
							$attach_to_post_type_data[ $cpt_key ] = (object) array(
								'label' => sprintf( __( '%1$s %2$snot registered%3$s', CPT_ONOMIES_TEXTDOMAIN ), "'" . $cpt_key . "'", '<span class="gray"><em>(', ')</em></span>' )
							);
						}
					}
				}
			}
			
			// sort post types by key
			ksort( $attach_to_post_type_data );
			
			// gather taxonomy data to use in properties
			$taxonomy_data = array();
			foreach( get_taxonomies( array(), 'objects' ) as $value => $tax ) {
				// do not include link categories or nav menu stuff
				if ( !empty( $value ) && apply_filters( 'custom_post_type_onomies_taxonomies_property_include_taxonomy', true, $value, $post_type_being_edited ) && !in_array( $value, array( 'link_category', 'nav_menu' ) ) && !$cpt_onomies_manager->is_registered_cpt_onomy( $value ) && !empty( $tax->labels->name ) ) {
					$taxonomy_data[ $value ] = (object) array(
						'label' => __( $tax->labels->name, CPT_ONOMIES_TEXTDOMAIN )
					);
				}
			}
			
			// gather user data to use in properties
			$user_data = array();
			$wp_roles = new WP_Roles(); 
			foreach ( $wp_roles->role_names as $value => $label ) {
				if ( !empty( $value ) && !empty( $label ) && apply_filters( 'custom_post_type_onomies_restrict_user_capabilities_property_include_user_role', true, $value, $post_type_being_edited ) ) {
					$user_data[ $value ] = (object) array(
						'label' => __( $label, CPT_ONOMIES_TEXTDOMAIN )
					);
				}
			}
			
			// allow you to filter out supports
			$cpt_supports_data = array(
				'title' => (object) array(
					'label' => __( 'Title', CPT_ONOMIES_TEXTDOMAIN ),
					),
				'editor' => (object) array( // Content
					'label' => __( 'Editor', CPT_ONOMIES_TEXTDOMAIN )
					),
				'author' => (object) array(
					'label' => __( 'Author', CPT_ONOMIES_TEXTDOMAIN )
					),
				'thumbnail' => (object) array( // Featured Image) (current theme must also support post-thumbnails
					'label' => __( 'Thumbnail', CPT_ONOMIES_TEXTDOMAIN )
					),
				'excerpt' => (object) array(
					'label' => __( 'Excerpt', CPT_ONOMIES_TEXTDOMAIN )
					),
				'trackbacks' => (object) array(
					'label' => __( 'Trackbacks', CPT_ONOMIES_TEXTDOMAIN )
					),
				'custom-fields' => (object) array(
					'label' => __( 'Custom Fields', CPT_ONOMIES_TEXTDOMAIN )
					),
				'comments' => (object) array(
					'label' => __( 'Comments', CPT_ONOMIES_TEXTDOMAIN )
					),
				'revisions' => (object) array( // will store revisions
					'label' => __( 'Revisions', CPT_ONOMIES_TEXTDOMAIN )
					),
				'page-attributes' => (object) array( // template and menu order (hierarchical must be true)
					'label' => __( 'Page Attributes', CPT_ONOMIES_TEXTDOMAIN )
					),
				'post-formats' => (object) array(
					'label' => __( 'Post Formats', CPT_ONOMIES_TEXTDOMAIN )
					)
				);
			foreach( $cpt_supports_data as $support => $support_info ) {
				if ( !apply_filters( 'custom_post_type_onomies_supports_property_include_support', true, $support, $post_type_being_edited ) )
					unset( $cpt_supports_data[ $support ] );
			}
			
			// create and return properties
			return (object) array(
				'basic' => array(
					'label' => (object) array(
						'label' => __( 'Label', CPT_ONOMIES_TEXTDOMAIN ),
						'type' => 'text',
						'fieldid' => CPT_ONOMIES_DASH . '-custom-post-type-label',
						'validation' => 'required',
						'description' => __( 'A general, <strong>usually plural</strong>, descriptive name for the post type.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong><span class="red">' . __( 'This field is required.', CPT_ONOMIES_TEXTDOMAIN ) . '</span></strong>'
					),
					'name' => (object) array(
						'label' => __( 'Name', CPT_ONOMIES_TEXTDOMAIN ),
						'type' => 'text',
						'fieldid' => CPT_ONOMIES_DASH . '-custom-post-type-name',
						'validation' => 'required custom_post_type_onomies_validate_name custom_post_type_onomies_validate_name_characters',
						'description' => __( 'The name of the post type. This property is very important because it is used to reference the post type all throughout WordPress.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'This should contain only lowercase alphanumeric characters and underscores. Maximum is 20 characters.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> ' . __( 'Be careful about changing this field once it has been set and you have created posts because the posts will not convert to the new name.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong><span class="red">' . __( 'This field is required.', CPT_ONOMIES_TEXTDOMAIN ) . '</span></strong>'
					),
					'description' => (object) array(
						'label' => __( 'Description', CPT_ONOMIES_TEXTDOMAIN ),
						'type' => 'textarea',
						'description' => __( 'Feel free to include a description.', CPT_ONOMIES_TEXTDOMAIN )
					)
				),
				'cpt_as_taxonomy' => (object) array(
					'label' => sprintf( __( 'Register this Custom Post Type as a %s', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ),
					'type' => 'group',
					'data' => array(
						'attach_to_post_type' => (object) array(
							'label' => __( 'Attach to Post Types', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'checkbox',
							'description' => sprintf( __( 'This setting allows you to use your custom post type in the same manner as a taxonomy, using your post titles as the terms. This is what we call a "%1$s". You can attach this %2$s to to any post type and assign posts just as you would assign taxonomy terms.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomy' ) . ' <strong><span class="red">' . sprintf( __( 'A post type must be checked in order to register this custom post type as a %s.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</span></strong>',
							'data' => $attach_to_post_type_data
						),
						'has_cpt_onomy_archive' => (object) array(
							'label' => __( 'Has Archive Page', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'radio',
							'description' => sprintf( __( 'This setting allows you to enable archive pages for this %s. If enabled, you can customize the archive page slug below.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ),
							'default' => 1,
							'data' => array(
								'true' => (object) array(
									'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
								),
								'false' => (object) array(
									'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
								)
							)
						),
						'cpt_onomy_archive_slug' => (object) array(
							'label' => __( 'Archive Page Slug', CPT_ONOMIES_TEXTDOMAIN ), 
							'type' => 'text',
							'description' => sprintf( __( 'You can use the variables %1$s, %2$s and %3$s to customize your slug. %4$s, which is also the default archive page slug, translates to %5$s.', CPT_ONOMIES_TEXTDOMAIN ), '<strong>$post_type</strong>', '<strong>$term_slug</strong>', '<strong>$term_id</strong>', '<strong>$post_type/tax/$term_slug</strong>', '<em>http://www.yoursite.com/movies/tax/the-princess-bride</em>' )
						),
						'restrict_user_capabilities' => (object) array(
							'label' => __( 'Restrict User\'s Capability to Assign Term Relationships', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'checkbox',
							'description' => sprintf( __( 'This setting allows you to grant specific user roles the capability, or permission, to assign term relationships for this %s.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . ' <strong><span class="red">' . __( 'If no user roles are selected, then ALL user roles will have permission.', CPT_ONOMIES_TEXTDOMAIN ) . '</span></strong>',
							'default' => array( 'administrator', 'editor', 'author' ),
							'data' => $user_data					
						)
					)
				),
				'labels' => (object) array(
					'label' => __( 'Customize the Labels', CPT_ONOMIES_TEXTDOMAIN ),
					'type' => 'group',
					'advanced' => true,
					'data' => array(
						'singular_name' => (object) array(
							'label' => __( 'Singular Name', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'Name for one object of this post type. If not set, defaults to the value of the "Label" property.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'add_new' => (object) array(
							'label' => __( 'Add New', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used for "Add New" submenu item. If not set, the default is "Add New" for both hierarchical and non-hierarchical posts.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'add_new_item' => (object) array(
							'label' => __( 'Add New Item', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used for the "Add New" button. If not set, the default is "Add New Post" for non-hierarchical posts and "Add New Page" for hierarchical posts.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'edit_item' => (object) array(
							'label' => __( 'Edit Item', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used when editing an individual post. If not set, the default is "Edit Post" for non-hierarchical posts and "Edit Page" for hierarchical posts.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'new_item' => (object) array(
							'label' => __( 'New Item', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used when creating a new post. If not set, the default is "New Post" for non-hierarchical posts and "New Page" for hierarchical posts.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'all_items' => (object) array(
							'label' => __( 'All Items', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used for the "All Items" submenu item. If not set, defaults to the value of the "Label" property.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'view_item' => (object) array(
							'label' => __( 'View Item', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used when viewing an individual post. If not set, the default is "View Post" for non-hierarchical posts and "View Page" for hierarchical posts.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'search_items' => (object) array(
							'label' => __( 'Search Items', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used for the "Search Posts" button. If not set, the default is "Search Posts" for non-hierarchical posts and "Search Pages" for hierarchical posts.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'not_found' => (object) array(
							'label' => __( 'Not Found', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used when no posts are found. If not set, the default is "No posts found" for non-hierarchical posts and "No pages found" for hierarchical posts.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'not_found_in_trash' => (object) array(
							'label' => __( 'Not Found in Trash', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used when no posts are found in the trash. If not set, the default is "No posts found in Trash" for non-hierarchical posts and "No pages found in Trash" for hierarchical posts.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'parent_item_colon' => (object) array(
							'label' => __( 'Parent Item Colon', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used when displaying a post\'s parent. This string is not used on non-hierarchical posts. If post is hierarchical, and not set, the default is "Parent Page".', CPT_ONOMIES_TEXTDOMAIN )
						),
						'menu_name' => (object) array(
							'label' => __( 'Menu Name', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This label is used as the text for the menu item. If not set, defaults to the value of the "Label" property.', CPT_ONOMIES_TEXTDOMAIN )
						)
					)
				),
				'options' => (object) array(
					'label' => __( 'Advanced Options', CPT_ONOMIES_TEXTDOMAIN ),
					'type' => 'group',
					'advanced' => true,
					'data' => array(
						'public' => (object) array(
							'label' => __( 'Public', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'radio',
							'description' => __( 'This setting defines whether this post type is visible in the admin and front-end of your site. This property is a catchall and trickles down to define other properties ("Show UI", "Publicly Queryable", and "Exclude From Search") unless they are set individually. For complete customization, be sure to check the value of these other properties.', CPT_ONOMIES_TEXTDOMAIN ),
							'default' => 1,
							'data' => array(
								'true' => (object) array(
									'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
									),
								'false' => (object) array(
									'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
									)
							)
						),
						'hierarchical' => (object) array(
							'label' => __( 'Hierarchical', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'radio',
							'description' => __( 'This setting defines whether this post type is hierarchical, which allows a parent to be specified. In order to define a post\'s parent, the post type must support "Page Attributes".', CPT_ONOMIES_TEXTDOMAIN ),
							'default' => 0,
							'data' => array(
								'true' => (object) array(
									'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
								),
								'false' => (object) array(
									'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
								)
							)
						),
						'supports' => (object) array(
							'label' => __( 'Supports', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'checkbox',
							'description' => __( 'These settings let you register support for certain features. All features are directly associated with a functional area of the edit post screen.', CPT_ONOMIES_TEXTDOMAIN ),
							'default' => array( 'title', 'editor' ),
							'data' => $cpt_supports_data
						),
						'has_archive' => (object) array(
							'label' => __( 'Has Archive Page', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => sprintf( __( 'This setting allows you to define/enable an archives page for this post type. <strong>The default setting is true so leave the field blank if you want an archives page (which will tell WordPress to use the post type name as the slug)</strong> or enter your own customized archive slug. Type %s if you do not want an archives page.', CPT_ONOMIES_TEXTDOMAIN ), '<strong>false</strong>' )
						),
						'taxonomies' => (object) array(
							'label' => __( 'Taxonomies', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'checkbox',
							'description' => sprintf( __( 'This setting allows you to add support for pre-existing, registered %s taxonomies.', CPT_ONOMIES_TEXTDOMAIN ), '<strong>non-CPT-onomy</strong>' ),
							'data' => $taxonomy_data
						),
						'show_ui' => (object) array(
							'label' => __( 'Show UI', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'radio',
							'description' => __( 'This setting defines whether to show the administration screens for managing this post type.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'If not set, defaults to the value of the "Public" property.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>',
							'data' => array(
								'true' => (object) array(
									'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
									),
								'false' => (object) array(
									'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
									)
							)
						),
						'show_in_menu' => (object) array(
							'label' => __( 'Show in Admin Menu', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This setting allows you to customize the placement of this post type in the admin menu.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'Note that "Show UI" must be true.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> ' . __( 'If you think the menu item is fine where it is, leave this field blank.', CPT_ONOMIES_TEXTDOMAIN ) . sprintf( __( ' Type %1$s to remove from the menu, %2$s to display as a top-level menu, or enter the name of a top-level menu (i.e. %3$s or %4$s) to add this item to it\'s submenu.', CPT_ONOMIES_TEXTDOMAIN ), '<strong>false</strong>', '<strong>true</strong>', '<strong>tools.php</strong>', '<strong>edit.php?post_type=page</strong>' )
						),
						'menu_position' => (object) array(
							'label' => __( 'Admin Menu Position', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'validation' => 'digits',
							'description' => __( 'This setting defines the position in the menu order where the post type item should appear. If you think the menu item is fine where it is, leave this field blank. To move the menu item up or down, enter a custom menu position.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'If not set, post types are added below the "Comments" menu item.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> ' . __( 'Visit the "Help" tab for a list of suggested menu positions.', CPT_ONOMIES_TEXTDOMAIN )
						),
						'menu_icon' => (object) array(
							'label' => __( 'Menu Icon', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This setting defines the URL to the image you want to use as the menu icon for this post type in the admin menu.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'If not set, the menu will show the Posts icon.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>'
						),
						'show_in_nav_menus' => (object) array(
							'label' => __( 'Show in Nav Menus', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'radio',
							'description' => __( 'This setting enables posts of this type to appear for selection in the navigation menus.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'If not set, defaults to the value of the "Public" property.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>',
							'data' => array(
								'true' => (object) array(
									'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
									),
								'false' => (object) array(
									'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
									)
							)
						),
						'query_var' => (object) array(
							'label' => __( 'Query Var', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => sprintf( __( 'This setting defines the query variable used to search for posts of this type. Type %s to prevent queries or enter a custom query variable name.', CPT_ONOMIES_TEXTDOMAIN ), '<strong>false</strong>' ) . ' <strong>' . __( 'If not set, defaults to true and the variable will equal the name of the post type.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>'
						),
						'publicly_queryable' => (object) array(
							'label' => __( 'Publicly Queryable', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'radio',
							'description' => __( 'This setting defines whether queries for this post type can be performed on the front-end of your site.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'If not set, defaults to the value of the "Public" property.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>',
							'data' => array(
								'true' => (object) array(
									'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
									),
								'false' => (object) array(
									'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
									)
							)
						),
						'exclude_from_search' => (object) array(
							'label' => __( 'Exclude From Search', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'radio',
							'description' => __( 'This setting allows you to exclude posts with this post type from search results on your site.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'If not set, defaults to the OPPOSITE value of the "Public" property.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>',
							'data' => array(
								'true' => (object) array(
									'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
									),
								'false' => (object) array(
									'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
									)
							)
						),
						'register_meta_box_cb' => (object) array(
							'label' => __( 'Register Meta Box Callback', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This setting allows you to provide a callback function that will be called for setting up your post type\'s meta boxes.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'Enter the function\'s name only.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>'
						),
						'rewrite' => (object) array(
							'label' => __( 'Rewrite', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'group',
							'data' => array(
								'enable_rewrite' => (object) array(
									'label' => __( 'Enable Permalinks', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'radio',
									'description' => sprintf( __( 'This setting allows you to activate custom permalinks for this post type. If %1$s, WordPress will create permalinks and use the post type (or "Query Var", if set) as the slug. If %2$s, this post type will have no custom permalink structure.', CPT_ONOMIES_TEXTDOMAIN ), '<strong>true</strong>', '<strong>false</strong>' ),
									'default' => 1,
									'data' => array(
										'true' => (object) array(
											'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
										),
										'false' => (object) array(
											'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
										)
									)
								),
								'slug' => (object) array(
									'label' => __( 'Slug', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'If rewrite is enabled, you can customize your permalink rewrite even further by prepending posts with a custom slug.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'If not set, defaults to the post type.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>'
								),
								'with_front' => (object) array(
									'label' => __( 'With Front', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'radio',
									'description' => sprintf( __( 'This setting defines whether to allow permalinks to be prepended with the permalink front base. Example: If your permalink structure is /blog/, then your links will be: %1$s = \'/blog/news/\', %2$s = \'/news/\'.', CPT_ONOMIES_TEXTDOMAIN ), '<strong>true</strong>', '<strong>false</strong>' ),
									'default' => 1,
									'data' => array(
										'true' => (object) array(
											'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
										),
										'false' => (object) array(
											'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
										)
									)
								),
								'feeds' => (object) array(
									'label' => __( 'Feeds', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'radio',
									'description' => __( 'This setting defines whether this post type will have a feed for its posts.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( '"Has Archive Page" needs to be set to true for the feeds to work.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> ' . __( 'If not set, defaults to the value of the "Has Archive Page" property.', CPT_ONOMIES_TEXTDOMAIN ),
									'data' => array(
										'true' => (object) array(
											'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
										),
										'false' => (object) array(
											'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
										)
									)
								),
								'pages' => (object) array(
									'label' => __( 'Pages', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'radio',
									'description' => __( 'This setting defines whether this post type\'s archive pages should be paginated.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( '"Has Archive Page" needs to be set to true for the archive pages to work.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>',
									'default' => 1,
									'data' => array(
										'true' => (object) array(
											'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
										),
										'false' => (object) array(
											'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
										)
									)
								)
							)
						),
						'map_meta_cap' => (object) array(
							'label' => __( 'Map Meta Cap', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'radio',
							'description' => __( 'This setting defines whether to use the internal default meta capability handling.', CPT_ONOMIES_TEXTDOMAIN ),
							'default' => 1,
							'data' => array(
								'true' => (object) array(
									'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
								),
								'false' => (object) array(
									'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
								)
							)						
						),
						'capability_type' => (object) array(
							'label' => __( 'Capability Type', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This setting allows you to define a custom set of capabilities. This term will be used to build the read, edit, and delete capabilities. The "Capabilities" property below can be used to overwrite specific individual capabilities. If you want to pass multiple capability types to allow for alternative plurals, separate the types with a space or comma, e.g. \'story\', \'stories\'.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'If not set, the default is \'post\'.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>'
						),
						'capabilities' => (object) array(
							'label' => __( 'Capabilities', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'group',
							'data' => array(
								'read' => (object) array(
									'label' => __( 'Read', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether objects of this post type can be read by the user.', CPT_ONOMIES_TEXTDOMAIN )
								),
								'read_post' => (object) array(
									'label' => __( 'Read Post', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => ''
								),
								'read_private_posts' => (object) array(
									'label' => __( 'Read Private Posts', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether private objects of this post type can be read by the user.', CPT_ONOMIES_TEXTDOMAIN )
								),
								'edit_post' => (object) array(
									'label' => __( 'Edit Post', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => ''
								),
								'edit_posts' => (object) array(
									'label' => __( 'Edit Posts', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether objects of this post type can be edited by the user.', CPT_ONOMIES_TEXTDOMAIN )
								),
								'edit_others_posts' => (object) array(
									'label' => __( 'Edit Others Posts', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether objects of this type, owned by other users, can be edited by the user. If the post type does not support an author, then this will behave like edit_posts.', CPT_ONOMIES_TEXTDOMAIN )
								),
								'edit_private_posts' => (object) array(
									'label' => __( 'Edit Private Posts', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether private objects of this post type can be edited by the user.', CPT_ONOMIES_TEXTDOMAIN )
								),
								'edit_published_posts' => (object) array(
									'label' => __( 'Edit Published Posts', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether published objects of this post type can be edited by the user.', CPT_ONOMIES_TEXTDOMAIN )
								),
								'delete_post' => (object) array(
									'label' => __( 'Delete Post', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => ''
								),
								'delete_posts' => (object) array(
									'label' => __( 'Delete Posts', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether objects of this post type can be deleted by the user.', CPT_ONOMIES_TEXTDOMAIN )
								),
								'delete_private_posts' => (object) array(
									'label' => __( 'Delete Private Posts', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether private objects of this post type can be deleted by the user.', CPT_ONOMIES_TEXTDOMAIN )
								),
								'delete_others_posts' => (object) array(
									'label' => __( 'Delete Others Posts', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether objects, owned by other users, can be deleted by the user. If the post type does not support an author, then this will behave like delete_posts.', CPT_ONOMIES_TEXTDOMAIN )
								),
								'delete_published_posts' => (object) array(
									'label' => __( 'Delete Published Posts', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether published objects of this post type can be deleted by the user.', CPT_ONOMIES_TEXTDOMAIN )
								),
								'publish_posts' => (object) array(
									'label' => __( 'Publish Posts', CPT_ONOMIES_TEXTDOMAIN ),
									'type' => 'text',
									'description' => __( 'This capability controls whether this user can publish objects of this post type.', CPT_ONOMIES_TEXTDOMAIN )
								)
							)
						),
						'can_export' => (object) array(
							'label' => __( 'Can Export', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'radio',
							'description' => __( 'This setting defines whether users can export posts with this post type.', CPT_ONOMIES_TEXTDOMAIN ),
							'default' => 1,
							'data' => array(
								'true' => (object) array(
									'label' => __( 'True', CPT_ONOMIES_TEXTDOMAIN )
									),
								'false' => (object) array(
									'label' => __( 'False', CPT_ONOMIES_TEXTDOMAIN )
									)
							)
						),
						'permalink_epmask' => (object) array(
							'label' => __( 'Permalink Endpoint Bitmasks', CPT_ONOMIES_TEXTDOMAIN ),
							'type' => 'text',
							'description' => __( 'This setting defines the rewrite endpoint bitmask used for posts with this post type.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'If not set, defaults to EP_PERMALINK.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>'
						)
					)
				),
				'deactivate' => array(
					'deactivate' => (object) array(
						'label' => __( 'Deactivate', CPT_ONOMIES_TEXTDOMAIN ),
						'type' => 'checkbox',
						'description' => __( 'This setting allows you to deactive, or disable, your custom post type (and hide it from WordPress) while allowing you to save your settings for later use.', CPT_ONOMIES_TEXTDOMAIN ) . ' <strong>' . __( 'Deactivating your custom post type does not delete its posts.', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>',
						'data' => array( 
							'true' => (object) array(
								'label' => __( 'Deactivate this CPT but save my settings.', CPT_ONOMIES_TEXTDOMAIN )
							)
						)
					)
				)
			);
		}
	}
	
	/**
	 * Queues style sheet for plugin's option page.
	 *
	 * This function is invoked by the action 'admin_print_styles-settings_page_{plugin name}'.
	 *
	 * @since 1.0
	 */	
	public function add_plugin_options_styles() {
		wp_enqueue_style( CPT_ONOMIES_DASH . '-admin-options', CPT_ONOMIES_URL . 'css/admin-options.css' );
	}
	
	/**
	 * Queues scripts for plugin's option page.
	 *
	 * This function is invoked by the action 'admin_print_scripts-settings_page_{plugin name}'.
	 *
	 * @since 1.0
	 */	
	public function add_plugin_options_scripts() {
		// plugin scripts
		wp_enqueue_script( CPT_ONOMIES_DASH . '-admin-options', CPT_ONOMIES_URL . 'js/admin-options.js', array( 'jquery' ), '', true );
		wp_enqueue_script( CPT_ONOMIES_DASH . '-admin-options-validate', CPT_ONOMIES_URL . 'js/admin-options-validate.js', array( 'jquery', 'jquery-form-validation' ), '', true );
		// need this script for the metaboxes to work correctly
		wp_enqueue_script( 'post' );
		wp_enqueue_script( 'postbox' );
		// localize script for options page
		wp_localize_script( CPT_ONOMIES_DASH . '-admin-options', 'cpt_onomies_admin_options_L10n', array(
			'unsaved_message1' => __( 'It looks like you might have some unsaved changes.', CPT_ONOMIES_TEXTDOMAIN ),
			'unsaved_message2' => __( 'Are you sure you want to leave?', CPT_ONOMIES_TEXTDOMAIN ),
			'delete_message1' => __( 'Are you sure you want to delete this custom post type?', CPT_ONOMIES_TEXTDOMAIN ),
			'delete_message2' => __( 'There is NO undo and once you click "OK", all of your settings will be gone.', CPT_ONOMIES_TEXTDOMAIN ),
			'delete_message3' => __( 'Deleting your custom post type DOES NOT delete the actual posts.', CPT_ONOMIES_TEXTDOMAIN ),
			'delete_message4' => __( 'They\'ll be waiting for you if you decide to register this post type again.', CPT_ONOMIES_TEXTDOMAIN ),
			'delete_message5' => __( 'Just make sure you use the same name.', CPT_ONOMIES_TEXTDOMAIN ),
			'close_labels' => __( 'Close Labels', CPT_ONOMIES_TEXTDOMAIN ),
			'close_advanced_options' => __( 'Close Advanced Options', CPT_ONOMIES_TEXTDOMAIN ),
			'labels_message1' => __( 'Instead of sticking with the boring defaults, why don\'t you customize the labels used for your custom post type. They can really add a nice touch.', CPT_ONOMIES_TEXTDOMAIN ),
			'labels_message2' => __( 'Customize the Labels', CPT_ONOMIES_TEXTDOMAIN ),
			'advanced_options_message1' => __( 'You can make your custom post type as "advanced" as you like but, beware, some of these options can get tricky. Visit the "Help" tab if you get stuck.', CPT_ONOMIES_TEXTDOMAIN ),
			'advanced_options_message2' => __( 'Edit the Advanced Options', CPT_ONOMIES_TEXTDOMAIN ),
			'invalid_post_type_name' => __( 'Your post type name is invalid.', CPT_ONOMIES_TEXTDOMAIN ),
			'post_type_name_exists' => __( 'That post type name already exists. Please choose another name.', CPT_ONOMIES_TEXTDOMAIN )
			));
	}
		
	/**
	 * This functions adds the help tab to the top of the options page.
	 *
	 * Added support for help tab backwards compatability in version 1.0.3.
	 * 
	 * @since 1.0
	 */
	public function add_plugin_options_help_tab() {
		// backwards compatability
		if ( get_bloginfo( 'version' ) < 3.3 ) {
	
			$text = $this->get_plugin_options_help_tab_getting_started();
			$text .= $this->get_plugin_options_help_tab_managing_editing_your_cpts();
			$text .= $this->get_plugin_options_help_tab_assigning_cpt_onomy_terms();
			$text .= $this->get_plugin_options_help_tab_excluding_cpt_onomy_terms();
			$text .= $this->get_plugin_options_help_tab_custom_cpt_onomy_archive_pages();
			$text .= $this->get_plugin_options_help_tab_customizing_with_filters();
			$text .= $this->get_plugin_options_help_tab_troubleshooting();
    		add_contextual_help( $this->options_page, $text );
			
		}		
		else {
		
		    $screen = get_current_screen();
			 // only add help tab on my options page
		    if ( $screen->id != $this->options_page )
		        return;
			$screen->add_help_tab( array( 
		        'id'	=> CPT_ONOMIES_UNDERSCORE . '_help_getting_started',
		        'title'	=> __( 'Getting Started', CPT_ONOMIES_TEXTDOMAIN ),
		        'callback'	=> array( &$this, 'get_plugin_options_help_tab_getting_started' )
		    ));
			$screen->add_help_tab( array( 
		        'id'	=> CPT_ONOMIES_UNDERSCORE . '_help_managing_editing_your_cpts',
		        'title'	=> __( 'Managing/Editing Your Custom Post Types', CPT_ONOMIES_TEXTDOMAIN ),
		        'callback'	=> array( &$this, 'get_plugin_options_help_tab_managing_editing_your_cpts' )
		    ));
			$screen->add_help_tab( array( 
		        'id'	=> CPT_ONOMIES_UNDERSCORE . '_help_assigning_cpt_onomy_terms',
		        'title'	=> sprintf( __( 'Assigning %s Terms', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ),
		        'callback'	=> array( &$this, 'get_plugin_options_help_tab_assigning_cpt_onomy_terms' )
		    ));
			$screen->add_help_tab( array( 
		        'id'	=> CPT_ONOMIES_UNDERSCORE . '_help_excluding_cpt_onomy_terms',
		        'title'	=> sprintf( __( 'Excluding %s Terms', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ),
		        'callback'	=> array( &$this, 'get_plugin_options_help_tab_excluding_cpt_onomy_terms' )
		    ));
			$screen->add_help_tab( array( 
		        'id'	=> CPT_ONOMIES_UNDERSCORE . '_help_custom_cpt_onomy_archive_pages',
		        'title'	=> sprintf( __( 'Custom %s Archive Pages', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ),
		        'callback'	=> array( &$this, 'get_plugin_options_help_tab_custom_cpt_onomy_archive_pages' )
		    ));
			$screen->add_help_tab( array( 
		        'id'	=> CPT_ONOMIES_UNDERSCORE . '_help_filters',
		        'title'	=> __( 'Customizing With Filters', CPT_ONOMIES_TEXTDOMAIN ),
		        'callback'	=> array( &$this, 'get_plugin_options_help_tab_customizing_with_filters' )
		    ));
			$screen->add_help_tab( array( 
		        'id'	=> CPT_ONOMIES_UNDERSCORE . '_help_troubleshooting',
		        'title'	=> __( 'Troubleshooting', CPT_ONOMIES_TEXTDOMAIN ),
		        'callback'	=> array( &$this, 'get_plugin_options_help_tab_troubleshooting' )
		    ));
			
		}		
	}
	
	/**
	 * This function returns the content for the What Is A CPT-onomy "Help" tab on the options page.
	 *
	 * Added support for help tab backwards compatability in version 1.0.3.
	 *
	 * @since 1.0
	 */
	public function get_plugin_options_help_tab_getting_started() {
		$text = '<h3>' . sprintf( __( 'What Is A %s?', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h3>
        <p>' . sprintf( __( 'A %1$s is a taxonomy built from a custom post type, using post titles to assign taxonomy relationships just as you would assign taxonomy terms. You can use the %2$s admin to create your own custom post types or use post types created by themes or other plugins.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomies' ) . '</p>
        <p><strong>' . sprintf( __( 'Is %s an official WordPress term?', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</strong> ' . __( 'No. It\'s just a fun word I made up.', CPT_ONOMIES_TEXTDOMAIN ) . '</p>
        <p><strong>' . sprintf( __( 'Need custom post types but not (necessarily) %s?', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ) . '</strong> ' . sprintf( __( '%s offers an extensive custom post type manager, allowing you to create and completely customize your custom post types <strong>without touching one line of code!</strong>', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ) . '</p>
        <h4>' . __( 'How to Get Started', CPT_ONOMIES_TEXTDOMAIN ) . '</h4>
        <p>' . sprintf( __( 'You can\'t have a %1$s without a custom post type! %2$sAdd a new custom post type%3$s (or %4$suse custom post types created by themes or other plugins%5$s), register the custom post type as a %6$s (under "Register this Custom Post Type as a %7$s" on the edit screen) and %8$s will take care of the rest.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', '<a href="' . esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'edit' => 'new' ), admin_url( 'options-general.php' ) ) ) . '">', '</a>', '<a href="' . esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE ), admin_url( 'options-general.php' ) ) ) . '#cpt-onomies-other-custom-post-types">', '</a>', 'CPT-onomy', 'CPT-onomy', 'CPT-onomies' ) . '</p>
        <h4>' . sprintf( __( 'Why %s?', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ) . '</h4>
        <p>' . __( 'It doesn\'t take long to figure out that custom post types can be a pretty powerful tool for creating and managing numerous types of content. For example, you might use the custom post types "Movies" and "Actors" to build a movie database but what if you wanted to group your "movies" by its "actors"? You could create a custom "actors" taxonomy but then you would have to manage your list of actors in two places: your "actors" custom post type and your "actors" taxonomy. This can be a pretty big hassle, especially if you have an extensive custom post type.', CPT_ONOMIES_TEXTDOMAIN ) . '</p>
        <p><strong>' . sprintf( __( 'This is where %s steps in.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ) . '</strong> ' . sprintf( __( 'Register your custom post type, \'Actors\', as a %1$s and %2$s will build your \'actors\' taxonomy for you, using your actors\' post titles as the terms. Pretty cool, huh?', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomies' ) . '</p>
        <h4>' . sprintf( __( 'Using %s', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ) . '</h4>
        <p>' . sprintf( __( 'What\'s really great about %1$s is that they work just like any other taxonomy, allowing you to use WordPress taxonomy functions like %2$s, %3$s and %4$s to access the %5$s information you need. %6$s will also work with tax queries when using %7$s and includes a tag cloud widget for your sidebar.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies', '<a href="http://codex.wordpress.org/Function_Reference/get_terms" target="_blank">get_terms()</a>', '<a href="http://codex.wordpress.org/Function_Reference/get_the_terms" target="_blank">get_the_terms()</a>', '<a href="http://codex.wordpress.org/Function_Reference/wp_get_object_terms" target="_blank">wp_get_object_terms()</a>', 'CPT-onomy', 'CPT-onomies', '<a href="http://rachelcarden.com/cpt-onomies/documentation/The_Loop/" target="_blank">The Loop</a>' ) . '</p>
        <p><span class="description"><strong>' . __( 'Note:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> ' . sprintf( __( 'Unfortunately, not every taxonomy function can be used at this time. %1$sCheck out the %2$s documentation%3$s to see which WordPress taxonomy functions work and when you\'ll need to access the plugin\'s %4$s class.', CPT_ONOMIES_TEXTDOMAIN ), '<a href="http://rachelcarden.com/cpt-onomies/documentation/" target="_blank">', 'CPT-onomy', '</a>', 'CPT-onomy' ) . '</span></p>';
		// backwards compatability
		if ( get_bloginfo( 'version' ) < 3.3 )
			return $text;
		else
			echo $text;	
   	}
	
	/**
	 * This function returns the content for the Managing Your Custom Post Type "Help" tab on the options page.
	 *
	 * Added support for help tab backwards compatability in version 1.0.3.
	 *
	 * @since 1.0
	 */
	public function get_plugin_options_help_tab_managing_editing_your_cpts() {
		$text = '<h3>' . __( 'Managing/Editing Your Custom Post Types', CPT_ONOMIES_TEXTDOMAIN ) . '</h3>
        <p>' . sprintf( __( 'For the most part, managing your custom post types is fairly easy. However, there are a few settings that can either be confusing or complicated. If you can\'t find the answer below, refer to %1$sthe WordPress Codex%2$s, %3$sthe plugin\'s support forums%4$s, or %5$smy web site%6$s for help.', CPT_ONOMIES_TEXTDOMAIN ), '<a href="http://codex.wordpress.org/Function_Reference/register_post_type" target="_blank">', '</a>', '<a href="http://wordpress.org/support/plugin/cpt-onomies" target="_blank">', '</a>', '<a href="http://www.rachelcarden.com/cpt-onomies/" target="_blank">', '</a>' ) . '</p>
        <h4>' . __( 'Admin Menu Position (under Advanced Options)', CPT_ONOMIES_TEXTDOMAIN ) . '</h4>
        <p>' . __( 'If you would like to customize your custom post type\'s postion in the administration menu, all you have to do is enter a custom menu position. Use the table below as a quide.', CPT_ONOMIES_TEXTDOMAIN ) . '</p>
        <table class="menu_position" cellpadding="0" cellspacing="0" border="0">
        	<tr>
            	<td><strong>' . __( '5', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below Posts', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
                <td><strong>' . __( '65', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below Plugins', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
           	</tr>
            <tr>
            	<td><strong>' . __( '10', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below Media', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
                <td><strong>' . __( '70', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below Users', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
          	</tr>
            <tr>
            	<td><strong>' . __( '15', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below Links', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
                <td><strong>' . __( '75', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below Tools', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
          	</tr>
            <tr>
            	<td><strong>' . __( '20', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below Pages', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
                <td><strong>' . __( '80', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below Settings', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
          	</tr>
            <tr>
            	<td><strong>' . __( '25', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below comments', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
                <td><strong>' . __( '100', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below second separator', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
          	</tr>
            <tr>
            	<td colspan="2"><strong>' . __( '60', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'below first separator', CPT_ONOMIES_TEXTDOMAIN ) . '</td>
          	</tr>
      	</table>';		
		// backwards compatability
		if ( get_bloginfo( 'version' ) < 3.3 )
			return $text;
		else
			echo $text;
 	}
	
	/**
	 * This function returns the content for the Assigning CPT-onomy Terms "Help" tab on the options page.
	 *
	 * @since 1.1
	 */
	public function get_plugin_options_help_tab_assigning_cpt_onomy_terms() {
		$text = '<h3>' . sprintf( __( 'Assigning %s Terms', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h3>
        <p>' . sprintf( __( 'Assigning your %s terms is no different than assigning taxonomy terms. Meta boxes will be added to each "edit post" page, where applicable, so users who have the capability can select, or "assign", the desired terms.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</p>
        <h4>' . sprintf( __( 'There are three different formats for %s term selection', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h4>
        <ul>
        	<li><strong>' . __( 'a checklist', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . sprintf( __( 'which is the default for hierarchical %s', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ) . '</li>
        	<li><strong>' . __( 'an autocomplete box', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . sprintf( __( 'which is the default for non-hierarchical %s', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ) . '</li>
        	<li><strong>' . __( 'and a select dropdown', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> - ' . __( 'if you only want one term to be selected', CPT_ONOMIES_TEXTDOMAIN ) . '</li>
        </ul>
        <h4>' . __( 'If you want to change your term selection format', CPT_ONOMIES_TEXTDOMAIN ) . '</h4>
        <p>' . sprintf( __( 'If you have a hierarchical %1$s but want an autocomplete box instead of a checklist, or if you want to use the select dropdown, %2$s allows you to hook into the meta box (via a filter) and overwrite the default selection format.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomies' ) . '</p>
        <p>' . sprintf( __( 'The filter provides the name of the %1$s and the object\'s post type, allowing you to drill down your customization as far as you like. You can change the format for a select %2$s, or for a select post type, or you can change the format for a select %3$s but only when you\'re editing a select post type.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomy', 'CPT-onomy' ) . '</p>
        <p>' . sprintf( __( 'To filter the format, tweak the code below (to fit your %1$s) and add it to your %2$s file. In the %3$s declaration, the %4$s stands for your filter\'s priority (and can be adjusted) but the %5$s is required because this filter includes three parameters.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies', 'functions.php', 'add_filter()', '\'1\'', '\'3\'' ) . '</p>
        <p>' . sprintf( __( 'Return %1$s for the checklist, %2$s for the autocomplete box and %3$s for the select dropdown. <strong>Be sure to remember that filters must always return the value you\'re filtering!</strong>', CPT_ONOMIES_TEXTDOMAIN ), '<strong>\'checklist\'</strong>', '<strong>\'autocomplete\'</strong>', '<strong>\'dropdown\'</strong>' ) . '</p>
        <pre>&lt;?php<br />add_filter( \'custom_post_type_onomies_meta_box_format\', \'my_website_custom_post_type_onomies_meta_box_format\', 1, 3 );<br />function my_website_custom_post_type_onomies_meta_box_format( $format, $taxonomy, $post_type ) {<br />&#160;&#160;&#160;// ' . sprintf( __( 'when editing a post with the post type %1$s,<br />&#160;&#160;&#160;// we want to assign the %2$s %3$s terms with an autocomplete box', CPT_ONOMIES_TEXTDOMAIN ), '\'movies\'', '\'actors\'', 'CPT-onomy' ) . '<br />&#160;&#160;&#160;if ( $post_type == \'movies\' && $taxonomy == \'actors\' )<br />&#160;&#160;&#160;&#160;&#160;&#160;return \'autocomplete\';<br />&#160;&#160;&#160;// ' . sprintf( __( 'no matter the post type, we want to assign the %1$s %2$s terms with a select dropdown', CPT_ONOMIES_TEXTDOMAIN ), '\'actors\'', 'CPT-onomy' ) . '<br />&#160;&#160;&#160;elseif ( $taxonomy == \'actors\' )<br />&#160;&#160;&#160;&#160;&#160;&#160;return \'dropdown\';<br />&#160;&#160;&#160;// ' . sprintf( __( 'no matter the post type, we want to assign the %1$s %2$s terms with a checklist', CPT_ONOMIES_TEXTDOMAIN ), '\'directors\'', 'CPT-onomy' ) . '<br />&#160;&#160;&#160;elseif ( $taxonomy == \'directors\' )<br />&#160;&#160;&#160;&#160;&#160;&#160;return \'checklist\';<br />&#160;&#160;&#160;// WordPress filters must always return a value<br />&#160;&#160;&#160;return $format;<br />}<br />?&gt;</pre>';
		// backwards compatability
		if ( get_bloginfo( 'version' ) < 3.3 )
			return $text;
		else
			echo $text;
	}
	
	/**
	 * This function returns the content for the Excluding CPT-onomy Terms "Help" tab on the options page.
	 *
	 * @since 1.2
	 */
	public function get_plugin_options_help_tab_excluding_cpt_onomy_terms() {
		$text = '<h3>' . sprintf( __( 'Excluding %s Terms', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h3>
		<p>' . sprintf( __( 'As of version 1.2, %1$s allows you to exclude specific %2$s term(s) from being assigned by hooking into a filter. This filter, %3$s, is applied when the \'Assigning %4$s Terms\' meta box is printed on the "edit post" page, and therefore excludes the term(s) from being listed, and whenever <code>%5$s</code> is invoked, which ultimately keeps the term(s) from being assigned to a post.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies', 'CPT-onomy', '\'custom_post_type_onomies_assigning_cpt_onomy_terms_exclude_term_ids\'', 'CPT-onomy', '$cpt_onomy->wp_set_object_terms()' ) . '</p>
		<p>' . sprintf( __( 'This filter provides the name of the %1$s and the object\'s post type and post ID, allowing you to drill down your exclusions as far as you like by simply including the term ID(s) in the <code>%2$s</code> array.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', '$exclude_term_ids' ) . '</p>
		<p>' . sprintf( __( 'To exclude a term, tweak the code below (to fit your %1$s) and add it to your %2$s file. In the %3$s declaration, the %4$s stands for your filter\'s priority (and can be adjusted) but the %5$s is required because this filter includes four parameters. Be sure to remember that filters must always return the value you\'re filtering!', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies', 'functions.php', 'add_filter()', '\'1\'', '\'4\'' ) . '</p>
		<pre>&lt;?php<br />add_filter( \'custom_post_type_onomies_assigning_cpt_onomy_terms_exclude_term_ids\', \'my_website_custom_post_type_onomies_assigning_cpt_onomy_terms_exclude_term_ids\', 1, 4 );<br />function my_website_custom_post_type_onomies_assigning_cpt_onomy_terms_exclude_term_ids( $exclude_term_ids, $taxonomy, $post_type, $post_id ) {<br />&#160;&#160;&#160;switch ( $taxonomy ) {<br /><br />&#160;&#160;&#160;&#160;&#160;&#160;case \'actors\':<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;// ' . sprintf( __( 'only exclude this term if it\'s being assigned to a %1$s', CPT_ONOMIES_TEXTDOMAIN ), '\'movie\'' ) . '<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;if ( $post_type == \'movies\' )<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;$exclude_term_ids[] = 7720;<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;// ' . __( 'always exclude these terms', CPT_ONOMIES_TEXTDOMAIN ) . '<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;$exclude_term_ids = array_merge( $exclude_term_ids, array( 277, 316 ) );<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;break;<br /><br />&#160;&#160;&#160;&#160;&#160;&#160;case \'directors\':<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;// ' . __( 'only exclude this term if it\'s being assigned to the post with this ID', CPT_ONOMIES_TEXTDOMAIN ) . '<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;if ( $post_id == 7715 )<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;$exclude_term_ids[] = 286;<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;break;<br /><br />&#160;&#160;&#160;}<br />&#160;&#160;&#160;return $exclude_term_ids;<br />}<br />?&gt;</pre>';
		// backwards compatability
		if ( get_bloginfo( 'version' ) < 3.3 )
			return $text;
		else
			echo $text;
	}
	
	/**
	 * This function returns the content for the Custom CPT-onomy Archive Pages "Help" tab on the options page.
	 *
	 * @since 1.2
	 */
	public function get_plugin_options_help_tab_custom_cpt_onomy_archive_pages() {
	
		$text = '<h3>' . sprintf( __( 'Custom %s Archive Pages', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h3>
		<p>' . sprintf( __( 'As of version 1.2, %1$s has implemented a simple, built-in method of setting up custom %2$s archive pages that\'s as easy as adding a rewrite rule with a few parameters. I\'ve included a few samples that should help you get your feet wet.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies', 'CPT-onomy' ) . '</p>
		<p style="margin-bottom: 3px;"><strong>' . __( 'Just a few notes before you get started:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong></p>
		<ul style="margin-top:0;">
			<li>' . sprintf( __( 'The %s parameter is required to make all of this work.', CPT_ONOMIES_TEXTDOMAIN ), '\'cpt_onomy_archive=1\'' ) . '</li>
			<li>' . __( 'Be sure to flush your rewrite rules each time you edit them. Flush your rewrite rules by visiting Settings -> Permalinks and clicking "Save Changes".', CPT_ONOMIES_TEXTDOMAIN ) . '</li>
			<li>' . __( 'If you have multiple rewrite rules with the same base, like the first two examples below, the rule with the longer structure needs to go first.', CPT_ONOMIES_TEXTDOMAIN ) . '</li>
		</ul>
		<pre>&lt;?php<br />add_action( \'init\', \'my_website_add_rewrite_rule\' );<br />function my_website_add_rewrite_rule() {<br /><br />&#160;&#160;&#160;// ' . sprintf( __( 'Says that if the URL matches this rule, i.e. %1$s,%2$s then it should display the %3$s post type that are tagged with the first term (which should be%4$s from the %5$s %6$s) and the second term (which should be from the %7$s %8$s).', CPT_ONOMIES_TEXTDOMAIN ), 'http://mywebsite.com/movies/steven-spielberg/tom-hanks/', '<br />&#160;&#160;&#160;//', '\'movies\'', '<br />&#160;&#160;&#160;//', '\'directors\'', 'CPT-onomy', '\'actors\'', 'CPT-onomy' ) . '<br />&#160;&#160;&#160;add_rewrite_rule( \'^movies/([^/]*)/([^/]*)/?\', \'index.php?post_type=movies&directors=$matches[1]&actors=$matches[2]&cpt_onomy_archive=1\', \'top\' );<br /><br />&#160;&#160;&#160;// ' . sprintf( __( 'Says that if the URL matches this rule, i.e. %1$s,%2$s then it should display the %3$s post type that are tagged with the first term (which should%4$s be from the %5$s %6$s).', CPT_ONOMIES_TEXTDOMAIN ), 'http://mywebsite.com/movies/steven-spielberg/', '<br />&#160;&#160;&#160;//', '\'movies\'', '<br />&#160;&#160;&#160;//', '\'directors\'', 'CPT-onomy' ) . '<br />&#160;&#160;&#160;add_rewrite_rule( \'^movies/([^/]*)/?\', \'index.php?post_type=movies&directors=$matches[1]&cpt_onomy_archive=1\', \'top\' );<br /><br />&#160;&#160;&#160;// ' . sprintf( __( 'Says that if the URL matches this rule, i.e. %1$s,%2$s then it should display all post types that are tagged with the first term (which should be from the %3$s %4$s).', CPT_ONOMIES_TEXTDOMAIN ), 'http://mywebsite.com/directors/steven-spielberg/', '<br />&#160;&#160;&#160;//', '\'directors\'', 'CPT-onomy' ) . '<br />&#160;&#160;&#160;add_rewrite_rule( \'^directors/([^/]*)/?\', \'index.php?directors=$matches[1]&cpt_onomy_archive=1\', \'top\' );<br /><br />}<br />?&gt;</pre>';
		// backwards compatability
		if ( get_bloginfo( 'version' ) < 3.3 )
			return $text;
		else
			echo $text;
	}
	
	/**
	 * This function returns the content for the CPT-onomy Filters "Help" tab on the options page.
	 *
	 * @since 1.2
	 */
	 public function get_plugin_options_help_tab_customizing_with_filters() {
		$text = '<h3>' . __( 'Customizing With Filters', CPT_ONOMIES_TEXTDOMAIN ) . '</h3>
		<p>' . sprintf( __( 'The following are all custom filters available in %1$s. Filters are a great way to easily tweak and customize plugins and themes without having to worry about editing core code.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ) . '</p>
		<p style="margin-bottom: 3px;"><strong>' . __( 'Some need-to-knows:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong></p>
		<ul style="margin-top:0;">
			<li>' . sprintf( __( 'To use these filters, tweak the code below and add them to your %1$s file.', CPT_ONOMIES_TEXTDOMAIN ), 'functions.php' ) . '</li>
			<li>' . sprintf( __( 'If a filter accepts more than one argument, you have to declare the number of arguments in your %1$s declaration, along with the filter\'s priority.', CPT_ONOMIES_TEXTDOMAIN ), 'add_filter()' ) . '</li>
			<li>' . sprintf( __( 'For more information, %1$scheck out %2$s in the WordPress codex%3$s.', CPT_ONOMIES_TEXTDOMAIN ), '<a href="http://codex.wordpress.org/Function_Reference/add_filter" target="_blank">', 'add_filter()', '</a>' ) . '</li>
		</ul>
		<h4>' . sprintf( __( 'Format For Assigning Your %1$s Terms', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h4>
		<p>' . sprintf( __( 'See the <em>Assigning %1$s Terms</em> tab for more information.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</p>
		<h4>' . __( 'Excluding Terms From Being Assigned', CPT_ONOMIES_TEXTDOMAIN ) . '</h4>
		<p>' . sprintf( __( 'See the <em>Excluding %1$s Terms</em> tab for more information.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</p>
		<h4>' . sprintf( __( 'Removing \'Assign %1$s Terms\' Meta Boxes From Admin', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h4>
		<p>' . sprintf( __( 'Remove the \'Assigning %1$s Terms\' meta box from the admin "edit post" page by returning false to the following filter:', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</p>
		<pre>&lt;?php<br />add_filter( \'custom_post_type_onomies_add_cpt_onomy_admin_meta_box\', \'my_website_add_cpt_onomy_admin_meta_box\', 1, 3 );<br />function my_website_add_cpt_onomy_admin_meta_box( $add_cpt_onomy_meta_box, $taxonomy, $post_type ) {<br />&#160;&#160;&#160;if ( $taxonomy == \'actors\' )<br />&#160;&#160;&#160;&#160;&#160;&#160;return false;<br />&#160;&#160;&#160;return $add_cpt_onomy_meta_box;<br />}<br />?&gt;</pre>
		<h4>' . sprintf( __( 'Removing The %1$s Dropdown Filter From Admin', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h4>
		<p>' . sprintf( __( 'Remove the %1$s dropdown filter from the admin manage custom post type screen by returning false to the following filter:', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy\'s' ) . '</p>
		<pre>&lt;?php<br />add_filter( \'custom_post_type_onomies_add_cpt_onomy_admin_dropdown_filter\', \'my_website_add_cpt_onomy_admin_dropdown_filter\', 1, 3 );<br />function my_website_add_cpt_onomy_admin_dropdown_filter( $add_dropdown_filter, $taxonomy, $post_type ) {<br />&#160;&#160;&#160;if ( $taxonomy == \'directors\' && $post_type == \'movies\' )<br />&#160;&#160;&#160;&#160;&#160;&#160;return false;<br />&#160;&#160;&#160;return $add_dropdown_filter;<br />}<br />?&gt;</pre>		
		<h4>' . sprintf( __( 'Removing the %1$s Column (Or It\'s Sortability) From The Admin', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h4>
		<p>' . sprintf( __( 'Remove the %1$s column, and/or it\'s sortability, by returning false to the following filter(s):', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy\'s' )  . '</p>
		<p><strong>' . __( 'Removes the column altogether:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong></p>
		<pre>&lt;?php<br />add_filter( \'custom_post_type_onomies_add_cpt_onomy_admin_column\', \'my_website_add_cpt_onomy_admin_column\', 1, 3 );<br />function my_website_add_cpt_onomy_admin_column( $add_column, $taxonomy, $post_type ) {<br />&#160;&#160;&#160;if ( $post_type == \'movies\' && $taxonomy == \'actors\' )<br />&#160;&#160;&#160;&#160;&#160;&#160;return false;<br />&#160;&#160;&#160;return $add_column;<br />}<br />?&gt;</pre>
		<p><strong>' . __( 'Does not remove the column, just disables it from being sortable:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong></p>
		<pre>&lt;?php<br />add_filter( \'custom_post_type_onomies_add_cpt_onomy_admin_sortable_column\', \'my_website_add_cpt_onomy_admin_sortable_column\', 1, 3 );<br />function my_website_add_cpt_onomy_admin_sortable_column( $add_sortable_column, $taxonomy, $post_type ) {<br />&#160;&#160;&#160;if ( $post_type == \'movies\' && $taxonomy == \'actors\' )<br />&#160;&#160;&#160;&#160;&#160;&#160;return false;<br />&#160;&#160;&#160;return $add_sortable_column;<br />}<br />?&gt;</pre>		
		<h4>' . sprintf( __( 'Customize %1$s Settings And Setting Default Values', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h4>
		<p>' . sprintf( __( 'The rest of the filters are very helpful for customizing your %1$s settings. They allow you to exclude certain data from being selected and/or set default property values.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</p>
		<p><strong>' . sprintf( __( 'If you don\'t want a %1$s to be able to "attach" itself to a particular post type:', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</strong></p>
		<pre>&lt;?php<br />add_action( \'custom_post_type_onomies_attach_to_post_type_property_include_post_type\', \'my_website_attach_to_post_type_property_include_post_type\', 1, 3 );<br />function my_website_attach_to_post_type_property_include_post_type( $include_post_type, $post_type, $post_type_being_edited ) {<br />&#160;&#160;&#160;if ( $post_type_being_edited == \'actors\' && in_array( $post_type, array( \'actors\', \'directors\' ) ) )<br />&#160;&#160;&#160;&#160;&#160;&#160;return false;<br />&#160;&#160;&#160;return $include_post_type;<br />}<br />?&gt;</pre>
		<p><strong>' . sprintf( __( 'If you don\'t want a particular user role to be selected for a %1$s "Restrict User\'s Capability to Assign Term Relationships":', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy\'s' ) . '</strong> <em>(' . __( 'keep in mind that if no user roles are selected, then ALL user roles have permission', CPT_ONOMIES_TEXTDOMAIN ) . ')</em></p>
		<pre>&lt;?php<br />add_filter( \'custom_post_type_onomies_restrict_user_capabilities_property_include_user_role\', \'my_website_restrict_user_capabilities_property_include_user_role\', 1, 3 );<br />function my_website_restrict_user_capabilities_property_include_user_role( $include_user_role, $user_role, $post_type_being_edited ) {<br />&#160;&#160;&#160;if ( $user_role == \'editor\' )<br />&#160;&#160;&#160;&#160;&#160;&#160;return false;<br />&#160;&#160;&#160;return $include_user_role;<br />}<br />?&gt;</pre>
		<p><strong>' . __( 'If you don\'t want a particular "support" feature, i.e. "title", "editor", etc., to be selected for a custom post type:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong></p>
		<pre>&lt;?php<br />add_filter( \'custom_post_type_onomies_supports_property_include_support\', \'my_website_supports_property_include_support\', 1, 3 );<br />function my_website_supports_property_include_support( $include_support, $support, $post_type_being_edited ) {<br />&#160;&#160;&#160;if ( in_array( $support, array( \'comments\', \'thumbnail\' ) ) )<br />&#160;&#160;&#160;&#160;&#160;&#160;return false;<br />&#160;&#160;&#160;return $include_support;<br />}<br />?&gt;</pre>
		<p><strong>' . __( 'If you don\'t want a particular taxonomy to be selected for a custom post type:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong></p>
		<pre>&lt;?php<br />add_filter( \'custom_post_type_onomies_taxonomies_property_include_taxonomy\', \'my_website_taxonomies_property_include_taxonomy\', 1, 3 );<br />function my_website_taxonomies_property_include_taxonomy( $include_taxonomy, $taxonomy, $post_type_being_edited ) {<br />&#160;&#160;&#160;if ( $taxonomy == \'post_tag\' )<br />&#160;&#160;&#160;&#160;&#160;&#160;return false;<br />&#160;&#160;&#160;return $include_taxonomy;<br />}<br />?&gt;</pre>
		<p><strong>' . __( 'If you want to set a default value for a setting:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong></p>
		<pre>&lt;?php<br />add_filter( \'custom_post_type_onomies_default_property_value\', \'my_website_default_property_value\', 1, 3 );<br />function my_website_default_property_value( $default_value, $property_key, $property_parent_key ) {<br />&#160;&#160;&#160;switch ( $property_key ) {<br />&#160;&#160;&#160;&#160;&#160;&#160;case \'description\':<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;return \'What an awesome CPT-onomy.\';<br />&#160;&#160;&#160;&#160;&#160;&#160;case \'attach_to_post_type\':<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;return array( \'actors\', \'movies\' );<br />&#160;&#160;&#160;&#160;&#160;&#160;case \'restrict_user_capabilities\':<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;return \'author\';<br />&#160;&#160;&#160;&#160;&#160;&#160;case \'hierarchical\':<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;return true;<br />&#160;&#160;&#160;&#160;&#160;&#160;case \'has_cpt_onomy_archive\':<br />&#160;&#160;&#160;&#160;&#160;&#160;case \'enable_rewrite\':<br />&#160;&#160;&#160;&#160;&#160;&#160;case \'feeds\':<br />&#160;&#160;&#160;&#160;&#160;&#160;case \'can_export\':<br />&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;return false;<br />&#160;&#160;&#160;}<br />&#160;&#160;&#160;return $default_value;<Br />}<br />?&gt;</pre>';
		// backwards compatability
		if ( get_bloginfo( 'version' ) < 3.3 )
			return $text;
		else
			echo $text;
	}
	
	/**
	 * This function returns the content for the Troubleshooting "Help" tab on the options page.
	 *
	 * Added support for help tab backwards compatability in version 1.0.3.
	 *
	 * @since 1.0
	 */
	public function get_plugin_options_help_tab_troubleshooting() {
		$text = '<h3>' . __( 'Troubleshooting', CPT_ONOMIES_TEXTDOMAIN ) . '</h3>
        <p>' . sprintf( __( 'If you\'re having trouble, and can\'t find the answer below, %1$scheck the support forums%2$s or %3$svisit my web site%4$s. If your problem involves a custom post type setting, %5$sthe WordPress Codex%6$s might be able to help.', CPT_ONOMIES_TEXTDOMAIN ), '<a href="http://wordpress.org/support/plugin/cpt-onomies" target="_blank">', '</a>', '<a href="http://www.rachelcarden.com/cpt-onomies/" target="_blank">', '</a>', '<a href="http://codex.wordpress.org/Function_Reference/register_post_type" target="_blank">', '</a>' ) . '</p>
        <h4>' . sprintf( __( 'My custom post type and/or %s is not showing up', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h4>
        <p>' . sprintf( __( 'Make sure your custom post type has not been deactivated. If you are %1$sprogrammatically registering a %2$s%3$s, and it is not showing up, make sure your custom post type has been registered BEFORE you register it\'s namesake %4$s.', CPT_ONOMIES_TEXTDOMAIN ), '<a href="http://rachelcarden.com/cpt-onomies/documentation/register_cpt_onomy/" target="_blank">', 'CPT-onomy', '</a>', 'CPT-onomy' ) . '</p>
        <h4>' . sprintf( __( 'My custom post type and/or %s archive page is not working', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h4>
        <p>' . __( 'If archive pages are enabled but are not working correctly, or are receiving a 404 error, it\'s probably the result of a rewrite or permalink error. Here are a few suggestions to get things working:', CPT_ONOMIES_TEXTDOMAIN ) . '</p>
        <ul>
        	<li><strong>' . __( 'Double check "Has Archive Page"', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> ' . __( 'Make sure the archive pages are enabled.', CPT_ONOMIES_TEXTDOMAIN ) . '</li>
        	<li><strong>' . sprintf( __( 'Are pretty permalinks enabled?', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> ' . __( 'Archive pages will not work without pretty permalinks. Visit Settings %s Permalinks and make sure anything but "Default" is selected.', CPT_ONOMIES_TEXTDOMAIN ), '->' ) . '</li>
        	<li><strong>' . sprintf( __( 'Reset your rewrite rules:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong> ' . __( 'Whenever rewrite settings are changed, the rules need to be "flushed" to make sure everything is in working order. Flush your rewrite rules by visiting Settings %s Permalinks and clicking "Save Changes".', CPT_ONOMIES_TEXTDOMAIN ), '->' ) . '</li>
      	</ul>
      	<h4>' . __( 'I\'m not able to save my custom post type because the page keeps telling me "That post type name already exists."', CPT_ONOMIES_TEXTDOMAIN ) . '</h4>
      	<p>' . sprintf( __( 'This is a jQuery "bug" that only seems to plague a few. I\'ve noticed that this validation standstill will occur if you have any text printed outside the &lt;body&gt; element on your page. If that\'s not the case, and the problem still lingers after you\'ve upgraded to version 1.1, you can dequeue the validation script by placing the following code in your %s file:', CPT_ONOMIES_TEXTDOMAIN ), 'functions.php' ) . '</p>
      	<pre>&lt;?php<br />add_action( \'admin_head\', \'my_website_admin_head\' );<br />function my_website_admin_head() {<br />&#160;&#160;&#160;wp_dequeue_script( \'custom-post-type-onomies-admin-options-validate\' );<br />}<br />?&gt;</pre>
		<h4>' . __( 'I added support for "Thumbnail" to my custom post type, but the "Featured Image" box does not show up', CPT_ONOMIES_TEXTDOMAIN ) . '</h4>
		<p>' . __( 'You also have to add theme support for post thumbnails to your functions.php file:', CPT_ONOMIES_TEXTDOMAIN ) . '</p>
		<pre>&lt;?php add_theme_support( \'post-thumbnails\' ); ?&gt;</pre>		
		<h4>' . sprintf( __( 'When I try to retrieve %s AND taxonomy terms, the results are incorrect', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '</h4>
		<p>' . sprintf( __( 'The most important thing to understand is that %1$s information is stored differently than regular taxonomy information. And if you are using %2$s and taxonomies and are trying to retrieve both %3$s and taxonomy term information in the same request, i.e. in %4$s, there\'s a small chance WordPress might get a little confused. <strong>The easiest solution? When you are using %5$s or something similar, request %6$s or %7$s fields.</strong> If WordPress has to retrieve all of the term information, it eliminates the chance that a %8$s or taxonomy term will be overwritten and lost in the shuffle.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomies', 'CPT-onomy', '<a href="http://rachelcarden.com/cpt-onomies/documentation/wp_get_object_terms/" target="_blank">wp_get_object_terms()</a>', '<a href="http://rachelcarden.com/cpt-onomies/documentation/wp_get_object_terms/" target="_blank">wp_get_object_terms()</a>', '\'all\'', '\'all_with_object_id\'', 'CPT-onomy' ) . '</p>
		<h4>' . __( 'When I filter or query posts, the results are incorrect', CPT_ONOMIES_TEXTDOMAIN ) . '</h4>
		<p>' . sprintf( __( '%1$s bear the same name as their custom post type counterparts, i.e. if you have an "actors" custom post type, it\'s %2$s is also named "actors". With that said, pre-%3$s, you may have had a custom taxonomy named "actors" and, although that taxonomy is no longer registered, your old taxonomy\'s term information may still exist in your database. This is where WordPress gets a little confused because %4$s information is stored differently than regular taxonomies. To fix the problem, all you have to do is remove the old taxonomy information (by following the steps below). If that doesn\'t solve your problem, please %5$slet me know%6$s.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies', 'CPT-onomy', 'CPT-onomies', 'CPT-onomy', '<a href="http://rachelcarden.com/contact/" target="_blank">', '</a>' ) . '</p>
		<ul>
			<li><strong>' . __( 'If you do not have access to your database or wish to only deal with the WP admin:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>
				<ol>
					<li>' . sprintf( __( '"Unregister" your %1$s. You do not have to remove your custom post type, just the %2$s. Just make sure everything is unchecked under "Attach to Post Types".', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomy' ) . '</li>
					<li>' . sprintf( __( 'Open your %1$s file and %2$sregister your old taxonomy%3$s. It doesn\'t matter which post type you attach it to, you just need access to the taxonomy\'s "edit" page. For the sake of this tutorial, we\'ll pretend you\'ve attached it to "Posts".', CPT_ONOMIES_TEXTDOMAIN ), 'functions.php', '<a href="http://codex.wordpress.org/Function_Reference/register_taxonomy" target="_blank">', '</a>' ) . '</li>
					<li>' . __( 'Open the "Posts" submenu, and click your taxonomy. Select the checkbox at the top left of the terms table and do a "bulk action" to delete all of the terms.', CPT_ONOMIES_TEXTDOMAIN ) . '</li>
					<li>' . sprintf( __( 'Once you\'ve removed all of the terms, you can "unregister" your taxonomy by removing the %1$s code from your %2$s file and then re-register your %2$s. This should clear up any WordPress confusion.', CPT_ONOMIES_TEXTDOMAIN ), 'register_taxonomy()', 'CPT-onomy', 'functions.php' ) . '</li>
				</ol>
			</li>
			<li><strong>' . __( 'If you have access to your database:', CPT_ONOMIES_TEXTDOMAIN ) . '</strong>
				<ol>
					<li>' . sprintf( __( 'Find the %1$s table and take note of the %2$s and %3$s of all of the rows with your taxonomy, then delete these rows.', CPT_ONOMIES_TEXTDOMAIN ), '"term_taxonomy"', '"term_taxonomy_id"', '"term_id"' ) . '</li>
					<li>' . sprintf( __( 'Find the %1$s table and delete any of the rows that contain one of your noted %2$s.', CPT_ONOMIES_TEXTDOMAIN ), '"terms"', '"term_id"s' ) . '</li>
					<li>' . sprintf( __( 'Find the %1$s table and delete any of the rows that contain one of your noted %2$s.', CPT_ONOMIES_TEXTDOMAIN ), '"term_relationships"', '"term_taxonomy_id"s' ) . '</li>
				</ol>
			</li>
		</ul>';
		// backwards compatability
		if ( get_bloginfo( 'version' ) < 3.3 )
			return $text;
		else
			echo $text;
   	}
	
	/**
	 * This function takes care of a few actions on the options page.
	 * It activates and deletes custom post types.
	 *
	 * This function is invoked by the action 'admin_init'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 */
	public function save_plugin_options_page() {
		global $cpt_onomies_manager;
		if ( current_user_can( 'manage_options' ) && isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == CPT_ONOMIES_OPTIONS_PAGE && isset( $_REQUEST[ '_wpnonce' ] ) ) {
			
			// activate
			if ( isset( $_REQUEST[ 'activate' ] ) ) {
				$CPT = $_REQUEST[ 'activate' ];
				// verify nonce
				if ( wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'activate-cpt-' . $CPT ) ) {
					// change the activation settings
					if ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( $CPT, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) {
						
						// remove the setting
						unset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $CPT ][ 'deactivate' ] );
						// update database
						update_option( CPT_ONOMIES_UNDERSCORE . '_custom_post_types', $cpt_onomies_manager->user_settings[ 'custom_post_types' ] );
											
						// redirect
						wp_redirect( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'cptactivated' => $CPT ), admin_url( 'options-general.php' ) ) );
						exit();
					
					}			
				}
				else {
					// add error message
					wp_die( sprintf( __( 'Looks like there was an error and the custom post type was not activated. %1$sGo back to %2$s%3$s and try again.', CPT_ONOMIES_TEXTDOMAIN ), '<a href="' . add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE ), admin_url( 'options-general.php' ) ) . '">', 'CPT-onomies', '</a>' ) );
				}
			}
			
			// delete
			else if ( isset( $_REQUEST[ 'delete' ] ) ) {
				$CPT = $_REQUEST[ 'delete' ];
				// verify nonce
				if ( wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'delete-cpt-' . $CPT ) ) {	
					// delete CPT from settings
					if ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( $CPT, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) {
						
						// remove from settings
						unset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $CPT ] );
						// update database
						update_option( CPT_ONOMIES_UNDERSCORE . '_custom_post_types', $cpt_onomies_manager->user_settings[ 'custom_post_types' ] );
											
						// redirect
						wp_redirect( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'cptdeleted' => '1' ), admin_url( 'options-general.php' ) ) );
						exit();					
					}		
				}
				else {
					// add error message
					wp_die( sprintf( __( 'Looks like there was an error and the custom post type was not deleted. %1$sGo back to %2$s%3$s and try again.', CPT_ONOMIES_TEXTDOMAIN ), '<a href="' . add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE ), admin_url( 'options-general.php' ) ) . '">', 'CPT-onomies', '</a>' ) );
				}
				
			}
			
		}
	}
	
	/**
	 * Adds a settings/options page for the plugin to the WordPress admin menu, under 'Settings'.
	 *
	 * This function is invoked by the action 'admin_menu'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 */
	public function add_plugin_options_page() {
		global $cpt_onomies_manager;
		// add options page
		$this->options_page = add_options_page( __( CPT_ONOMIES_PLUGIN_NAME, CPT_ONOMIES_TEXTDOMAIN ), CPT_ONOMIES_PLUGIN_SHORT_NAME, 'manage_options', CPT_ONOMIES_OPTIONS_PAGE, array( &$this, 'print_plugin_options_page' ) );
		// adds the help tabs when the option page loads
		add_action( 'load-' . $this->options_page, array( &$this, 'add_plugin_options_help_tab' ) );
	}
	
	/**
	 * Adds the meta boxes to the CPT-onomies settings pages.
 	 *
	 * This function is invoked by the action 'admin_head-settings_page_'.CPT_ONOMIES_OPTIONS_PAGE.
	 *
	 * @since 1.1
	 * @uses $cpt_onomies_manager
	 */
	public function add_plugin_options_page_meta_boxes() {
		global $cpt_onomies_manager;
				
		// detect if we're editing a CPT AND whether its a new CPT or an "other" CPT
		// will create $new, $edit and $other		
		extract( $this->detect_custom_post_type_new_edit_other() );
		
		// About this Plugin
		add_meta_box( CPT_ONOMIES_DASH . '-about', __( 'About this Plugin', CPT_ONOMIES_TEXTDOMAIN ), array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'about' );
													
		// add meta boxes for options page
		// boxes just for the edit screen
		if ( $new || $edit ) {
			
			// Save	
			add_meta_box( CPT_ONOMIES_DASH . '-save-custom-post-type', __( 'Save Your Changes', CPT_ONOMIES_TEXTDOMAIN ), array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'save_custom_post_type' );
				
			// Delete Custom Post Type, if created by plugin
			if ( !$other )
				add_meta_box( CPT_ONOMIES_DASH . '-delete-custom-post-type', __( 'Delete this Custom Post Type', CPT_ONOMIES_TEXTDOMAIN ), array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'delete_custom_post_type' );
				
			// Edit Properties
			add_meta_box( CPT_ONOMIES_DASH . '-edit-custom-post-type', __( 'Edit Your Custom Post Type\'s Properties', CPT_ONOMIES_TEXTDOMAIN ), array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'normal', 'core', 'edit_custom_post_type' );
		
		}
		
		else {
		
			// Add A New Custom Post Type
			add_meta_box( CPT_ONOMIES_DASH . '-add-new-custom-post-type', __( 'Add A New Custom Post Type', CPT_ONOMIES_TEXTDOMAIN ), array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'add_new_custom_post_type' );
						
			// Manage Custom Post Types
			add_meta_box( CPT_ONOMIES_DASH . '-custom-post-types', __( 'Manage Your Custom Post Types', CPT_ONOMIES_TEXTDOMAIN ), array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'normal', 'core', 'custom_post_types' );			
			
			// Other Custom Post Types
			add_meta_box( CPT_ONOMIES_DASH . '-other-custom-post-types', __( 'Other Custom Post Types', CPT_ONOMIES_TEXTDOMAIN ), array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'normal', 'core', 'other_custom_post_types' );	
		
		}
		
		// we only want this box on the main page
		if ( !( $new || $edit ) )
			add_meta_box( CPT_ONOMIES_DASH . '-key', __( 'What the Icons Mean', CPT_ONOMIES_TEXTDOMAIN ), array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'key' );
		
		// Spread the Love and Any Questions
		add_meta_box( CPT_ONOMIES_DASH . '-promote', __( 'Spread the Love', CPT_ONOMIES_TEXTDOMAIN ), array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'promote' );
		add_meta_box( CPT_ONOMIES_DASH . '-support', __( 'Any Questions?', CPT_ONOMIES_TEXTDOMAIN ), array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'support' );
				
	}
	
	/**
	 * This function is invoked when the plugin's option page is added to output the content.
	 *
	 * Added support for submit button backwards compatability in version 1.0.3.
	 *
	 * This function is invoked by the action 'admin_menu'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 */
	public function print_plugin_options_page() {
		global $cpt_onomies_manager;
		if ( current_user_can( 'manage_options' ) ) {
			
			// detect if we're editing a CPT AND whether its a new CPT or an "other" CPT
			// will create $new, $edit and $other	
			extract( $this->detect_custom_post_type_new_edit_other() );
									
			// create the tabs
			$tabs = array();
			if ( $new || $edit ) {
				
				$tabs = array(
					'properties' => (object) array(
						'title' => __( 'Custom Post Type Properties', CPT_ONOMIES_TEXTDOMAIN ),
						'active' => true
					)
				);
				
			}
						
			?>
	    
		    <div id="custom-post-type-onomies" class="wrap">
		
				<?php screen_icon(); ?>
				
		       	<h2><?php _e( CPT_ONOMIES_PLUGIN_NAME, CPT_ONOMIES_TEXTDOMAIN ); ?></h2>
                
                <?php
				
				if ( $new || $edit ) {
					
					$label = '';
					if ( $new ) $label = __( 'Creating a New Custom Post Type', CPT_ONOMIES_TEXTDOMAIN );
					else {
							
						if ( $other )
							$label = __( get_post_type_object( $edit )->label, CPT_ONOMIES_TEXTDOMAIN );
						
						else
							$label = __( $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $edit ][ 'label' ], CPT_ONOMIES_TEXTDOMAIN );
							
						$label = __( 'Editing "' . $label . '"', CPT_ONOMIES_TEXTDOMAIN );
							
					}
										
					?><h3 class="nav-tab-wrapper"><?php
					
						if ( $label ) echo $label . '&nbsp;&nbsp;';
						
						// don't include tab name in URL, for now, considering there's only one tab
						foreach( $tabs as $tab_key => $this_tab ) {
							?><a href="<?php echo esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'edit' => ( $new ) ? 'new' : $edit, 'other' => ( $other ? '1' : NULL ) ), admin_url( 'options-general.php' ) ) ); ?>" class="nav-tab<?php if ( $this_tab->active ) echo ' nav-tab-active'; ?>"><?php echo $this_tab->title; ?></a><?php
						}					
					
						?><div class="etc">
						
							<a class="return" href="<?php echo esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE ), admin_url( 'options-general.php' ) ) ); ?>">&laquo; <?php printf( __( 'Back to %s', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ); ?></a>
							
							<a class="new" href="<?php echo esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'edit' => 'new' ), admin_url( 'options-general.php' ) ) ); ?>" title="<?php _e( 'Add a new custom post type', CPT_ONOMIES_TEXTDOMAIN ); ?>"><?php _e( 'Add a new custom post type', CPT_ONOMIES_TEXTDOMAIN ); ?></a>
							
						</div>
						
					</h3><?php
                                        
				}
				
				// add deleted message
				if ( $_REQUEST[ 'page' ] == CPT_ONOMIES_OPTIONS_PAGE && isset( $_REQUEST[ 'cptdeleted' ] ) ) {
					
					?><div id="message" class="updated"><p><?php _e( 'The custom post type was deleted.', CPT_ONOMIES_TEXTDOMAIN ) ; ?></p></div><?php
				
				}
				// add activated message
				else if ( $_REQUEST[ 'page' ] == CPT_ONOMIES_OPTIONS_PAGE && isset( $_REQUEST[ 'cptactivated' ] ) ) {
					
					$activated_cpt = strtolower( $_REQUEST[ 'cptactivated' ] );
					$label = ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( $activated_cpt, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) ? $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $activated_cpt ][ 'label' ] : false;
					
					?><div id="message" class="updated"><p><?php
						if ( $label )
							_e( 'The custom post type \'' . $label . '\' is now active.', CPT_ONOMIES_TEXTDOMAIN );
						else
							_e( 'The custom post type is now active.', CPT_ONOMIES_TEXTDOMAIN );
					?></p></div><?php
					
				}
				
				?>
                
                <div id="poststuff" class="metabox-holder has-right-sidebar">
                
                	<?php
                	$form_id = CPT_ONOMIES_DASH;
                	if ( $new || $edit ) $form_id .= '-edit-cpt';
                	?>
			
					<form id="<?php echo $form_id; ?>" method="post" action="options.php">
					
						<?php
						
						// Output nonce, action, and option_page fields
						if ( $new || $edit ) {
							if ( $other ) settings_fields( CPT_ONOMIES_OPTIONS_PAGE . '-other-custom-post-types' );
							else settings_fields( CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types' );
							wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
							wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
						}
						
						?>
						
						<div id="post-body">
							<div id="post-body-content">
							
								<?php
								
								do_meta_boxes( $this->options_page, 'normal', array() );
								do_meta_boxes( $this->options_page, 'advanced', array() );
													
								if ( $new || $edit )
									submit_button( __( 'Save Your Changes', CPT_ONOMIES_TEXTDOMAIN ), 'primary', 'save_changes', false, array( 'id' => CPT_ONOMIES_DASH . '-save-changes-bottom' ) );
									
								?>
								
							</div>
						</div>
                        
                        <div id="side-info-column" class="inner-sidebar">
							
							<?php do_meta_boxes( $this->options_page, 'side', array() ); ?>
						
						</div>
						
					</form>
					
				</div> <!-- #poststuff -->
                
	      	</div>
            
	    <?php }
	}
	
	/**
	 * This function is invoked when a meta box is added to plugin's option page.
	 * This 'callback' function prints the html for the meta box.
	 *
	 * This function is invoked by the action 'admin_init'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager, $user_ID
	 * @param array $post - information about the current post, which is empty because there is no current post on a settings page
	 * @param array $metabox - information about the metabox
	 */
	public function print_plugin_options_meta_box( $post, $metabox ) {
		global $cpt_onomies_manager, $user_ID;
		if ( current_user_can( 'manage_options' ) ) {
			switch( $metabox[ 'args' ] ) {
					
				//! Add New CPT Meta Box
				case 'add_new_custom_post_type':
					?><a class="add_new_cpt_onomy_custom_post_type" href="<?php echo esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'edit' => 'new' ), admin_url( 'options-general.php' ) ) ); ?>" title="<?php esc_attr_e( 'Add a new custom post type', CPT_ONOMIES_TEXTDOMAIN ); ?>"><?php _e( 'Add a new custom post type', CPT_ONOMIES_TEXTDOMAIN ); ?></a><?php
					break;
					
				//! About Meta Box
				case 'about':
					?><p><strong><a href="<?PHP echo CPT_ONOMIES_PLUGIN_DIRECTORY_URL; ?>" title="<?php esc_attr_e( CPT_ONOMIES_PLUGIN_NAME, CPT_ONOMIES_TEXTDOMAIN ); ?>" target="_blank"><?php _e( CPT_ONOMIES_PLUGIN_NAME, CPT_ONOMIES_TEXTDOMAIN ); ?></a></strong></p>
	                <p><strong><?php _e( 'Version', CPT_ONOMIES_TEXTDOMAIN ); ?>:</strong> <?php echo CPT_ONOMIES_VERSION; ?><br />
	                <strong><?php _e( 'Author', CPT_ONOMIES_TEXTDOMAIN ); ?>:</strong> <a href="http://www.rachelcarden.com" title="Rachel Carden" target="_blank">Rachel Carden</a></p><?php
					break;
					
				//! Key Meta Box
				case 'key':
					?><p class="inactive"><img src="<?php echo CPT_ONOMIES_URL; ?>images/inactive.png" /><span><?php printf( __( 'This %s is inactive.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT' ); ?></span></p>
                    <p class="attention"><img src="<?php echo CPT_ONOMIES_URL; ?>images/attention.png" /><span><?php printf( __( 'This %s is not registered.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT' ); ?></span></p>
                    <p class="working"><img src="<?php echo CPT_ONOMIES_URL; ?>images/working.png" /><span><?php printf( __( 'This %s is registered and working.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT' ); ?></span></p><?php
					break;
					
				//! Promote Meta Box
				case 'promote':
					?><p class="rating"><a href="<?php echo CPT_ONOMIES_PLUGIN_DIRECTORY_URL; ?>" title="<?php esc_attr_e( 'Give the plugin a good rating', CPT_ONOMIES_TEXTDOMAIN ); ?>" target="_blank"><img src="<?php echo CPT_ONOMIES_URL; ?>images/rating_star.png" /><span><?php _e( 'Give the plugin a good rating', CPT_ONOMIES_TEXTDOMAIN ); ?></span></a></p>
	                <p class="twitter"><a href="https://twitter.com/#!/bamadesigner" title="<?php printf( esc_attr__( '%s on Twitter', CPT_ONOMIES_TEXTDOMAIN ), 'bamadesigner' ); ?>" target="_blank"><img src="<?php echo CPT_ONOMIES_URL; ?>images/twitter_bird.png" /><span><?php _e( 'Follow me on Twitter', CPT_ONOMIES_TEXTDOMAIN ); ?></span></a></p>
                    <p class="donate"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=bamadesigner%40gmail%2ecom&lc=US&item_name=Rachel%20Carden%20%28CPT%2donomies%29&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" title="<?php esc_attr_e( 'Donate a few bucks to the plugin', CPT_ONOMIES_TEXTDOMAIN ); ?>" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" alt="<?php esc_attr_e( 'Donate', CPT_ONOMIES_TEXTDOMAIN ); ?>" /><span><?php _e( 'a few bucks', CPT_ONOMIES_TEXTDOMAIN ); ?></span></a></p><?php
					break;
					
				//! Support Meta Box
				case 'support':
					?><p><strong><?php _e( 'Need help?', CPT_ONOMIES_TEXTDOMAIN ); ?></strong> <?php _e( 'Here are a few options:', CPT_ONOMIES_TEXTDOMAIN ); ?></p>
                    <ol>
                    	<li><a class="<?php echo CPT_ONOMIES_UNDERSCORE; ?>_show_help_tab" href="#"><?php _e( 'The \'Help\' tab', CPT_ONOMIES_TEXTDOMAIN ); ?></a></li>
                        <li><a href="http://wordpress.org/support/plugin/cpt-onomies" title="<?php printf( esc_attr__( '%s support forums', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ); ?>" target="_blank"><?php printf( __( 'The %s support forums', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies\'' ); ?></a></li>
                        <li><a href="http://rachelcarden.com/cpt-onomies/" title="<?php esc_attr_e( 'Visit my web site', CPT_ONOMIES_TEXTDOMAIN ); ?>" target="_blank"><?php _e( 'My web site', CPT_ONOMIES_TEXTDOMAIN ); ?></a></li>
                   	</ol>
	                <p><?php printf( __( 'If you notice any bugs or problems with the plugin, %1$splease let me know%2$s.', CPT_ONOMIES_TEXTDOMAIN ), '<a href="http://rachelcarden.com/contact/" target="_blank">', '</a>' ); ?></p><?php
	                break;
	                
	            //! Manage CPT Meta Boxes
	            case 'custom_post_types':
				case 'other_custom_post_types':
					$other = ( $metabox[ 'args' ] == 'other_custom_post_types' ) ? true : false;
					
					if ( $other ) { ?>
                    	<p><?php printf( __( 'If you\'re using a theme, or another plugin, that creates a custom post type, you can still register these "other" custom post types as %s.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomies' ); ?> <span class="description"><?php _e( 'You cannot, however, manage the actual custom post type. Sorry, but that\'s up to the plugin and/or theme.', CPT_ONOMIES_TEXTDOMAIN ); ?></span></p>
                    <?php }
					else { ?>
                    	<p><?php _e( 'If you\'d like to create a custom post type, but don\'t want to mess with code, you\'ve come to the right place. Customize every setting, or just give us a name, and we\'ll take care of the rest.', CPT_ONOMIES_TEXTDOMAIN ); ?> <span class="description"><?php printf( __( 'For more information, like how to create a %s, visit the \'Help\' tab.</span>', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ); ?></p>
                  	<?php }
					
					// custom post types created by this plugin
					if ( !$other )
						$post_type_objects = $cpt_onomies_manager->user_settings[ 'custom_post_types' ];
					
					// get other (non-builtin) custom post types
					else {
						$post_type_objects = get_post_types( array( '_builtin' => false ), 'objects' );
						foreach( $post_type_objects as $post_type => $CPT ) {
							if ( $cpt_onomies_manager->is_registered_cpt( $post_type ) )
								unset( $post_type_objects[ $post_type ] );
							// gather the plugin settings
							else if ( is_array( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) && array_key_exists( $post_type, $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) ) {
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'attach_to_post_type' ] ) && !empty( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'attach_to_post_type' ] ) )
									$post_type_objects[ $post_type ]->attach_to_post_type = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'attach_to_post_type' ];
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'has_cpt_onomy_archive' ] ) && !empty( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'has_cpt_onomy_archive' ] ) )
									$post_type_objects[ $post_type ]->has_cpt_onomy_archive = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'has_cpt_onomy_archive' ];
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'restrict_user_capabilities' ] ) && !empty( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'restrict_user_capabilities' ] ) )
									$post_type_objects[ $post_type ]->restrict_user_capabilities = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'restrict_user_capabilities' ];
							}
						}
					}
					
					// print the table	
					?><table class="manage_custom_post_type_onomies<?php if ( $other ) echo ' other'; ?>" cellpadding="0" cellspacing="0" border="0">
                        <thead>
                            <tr valign="bottom">
                            	<th class="status"><?php _e( 'Status', CPT_ONOMIES_TEXTDOMAIN ); ?></th>
                                <th class="label"><?php _e( 'Label', CPT_ONOMIES_TEXTDOMAIN ); ?></th>
                                <th class="name"><?php _e( 'Name', CPT_ONOMIES_TEXTDOMAIN ); ?></th>
                                <th class="public"><?php _e( 'Public', CPT_ONOMIES_TEXTDOMAIN ); ?></th>
                                <th class="registered_custom_post_type_onomy"><?php _e( 'Registered', CPT_ONOMIES_TEXTDOMAIN ); ?><br />CPT-onomy?</th>
                                <th class="attached_to"><?php printf( __( '%s<br />Attached to', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ); ?></th>
                                <th class="ability"><?php _e( 'Ability to Assign Terms', CPT_ONOMIES_TEXTDOMAIN ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php if ( empty( $post_type_objects ) ) { ?>
                            	<tr valign="top">
                                	<td class="none" colspan="7"><?php
                                    	
										if ( $other ) _e( 'There are no "other" custom post types.', CPT_ONOMIES_TEXTDOMAIN );
										else _e( 'What are you waiting for? Custom post types are pretty awesome and you don\'t have to touch one line of code.', CPT_ONOMIES_TEXTDOMAIN );
										
									?></td>
                                </tr>
                            <?php }
							else {
								foreach( $post_type_objects as $post_type => $CPT ) {
									
	                                if ( !is_object( $CPT ) ) $CPT = (object) $CPT;
									if ( !empty( $post_type ) && ( !isset( $CPT->name ) || empty( $CPT->name ) ) ) $CPT->name = $post_type;
									else if ( empty( $post_type ) && isset( $CPT->name ) && !empty( $CPT->name ) ) $post_type = $CPT->name;
									
									// make sure post type and label exist
									if ( !empty( $post_type ) && !( !isset( $CPT->label ) || empty( $CPT->label ) ) ) {
				
										// detect if we're editing a CPT AND whether its a new CPT or an "other" CPT
										// will create $inactive_cpt, $is_registered_cpt, $is_registered_cpt_onomy,
										// $programmatic_cpt_onomy, $should_be_cpt_onomy, $attention_cpt
										// and $attention_cpt_onomy
										extract( $this->detect_custom_post_type_message_variables( $post_type, $CPT, $other ) );
										
										// check to see if attached post types exist
										$attach_to_post_type_not_exist = array();
										if ( !empty( $CPT->attach_to_post_type ) ) {
											foreach( $CPT->attach_to_post_type as $attached ) {
												if ( !post_type_exists( $attached ) )
													$attach_to_post_type_not_exist[] = $attached;
											}
										}
										
										$message = NULL;
										if ( $attention_cpt ) {
											$builtin = get_post_types( array( '_builtin' => true ), 'objects' );
											// builtin conflict
											if ( array_key_exists( $post_type, $builtin ) )
												$message = esc_attr__( 'The custom post type, \'' . $CPT->label . '\', is not registered because the built-in WordPress post type, \'' . $builtin[ $post_type ]->label . '\' is already registered under the name \'' . $post_type . '\'. Sorry, but WordPress wins on this one. You\'ll have to change the post type name if you want to get \'' . $CPT->label . '\' up and running.', CPT_ONOMIES_TEXTDOMAIN );
											// "other" conflict
											else
												$message = esc_attr__( 'The custom post type, \'' . $CPT->label . '\', is not registered because another custom post type with the same name already exists. This other custom post type is probably setup in your theme or another plugin. Check out the \'Other Custom Post Types\' section to see what else has been registered.', CPT_ONOMIES_TEXTDOMAIN );
										}										
										//else if ( $attention_cpt_onomy ) {
										else if ( !$is_registered_cpt_onomy && $should_be_cpt_onomy ) {
										
											if ( taxonomy_exists( $post_type ) )
												$message = sprintf( esc_attr__( 'This custom post type\'s %1$s is not registered because another taxonomy with the same name already exists. If you would like this %2$s to work, please remove the conflicting taxonomy.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomy' );
											else
												$message = sprintf( esc_attr__( 'This custom post type\'s %1$s is not registered because the post type(s) it is attached to is not active/registered. If you would like this %2$s to work, please activate/register said post type(s).', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomy' );
												
										}
										// this means this CPT-onomy is registered but not for ALL of its assigned custom post types
										else if ( $is_registered_cpt_onomy && $attach_to_post_type_not_exist && count( $attach_to_post_type_not_exist ) != count( $CPT->attach_to_post_type ) ) {
										
											if ( count( $attach_to_post_type_not_exist ) > 1 ) {
												$attach_to_post_type_not_exist_string = NULL;
												foreach( $attach_to_post_type_not_exist as $not_exist_index => $not_exist ) {
													if ( $not_exist_index == ( count( $attach_to_post_type_not_exist ) - 1 ) )
														$attach_to_post_type_not_exist_string .= ' and ';
													else if ( $not_exist_index > 0 )
														$attach_to_post_type_not_exist_string .= ', ';
													$attach_to_post_type_not_exist_string .= "'" . $not_exist . "'";
												}
												$message = sprintf( esc_attr__( 'This custom post type\'s %1$s is not attached to the %2$s custom post types because they are not active/registered. If you would like this %3$s to work, please activate/register said post type(s).', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', $attach_to_post_type_not_exist_string, 'CPT-onomy' );
												
											}
											else
												$message = sprintf( esc_attr__( 'This custom post type\'s %1$s is not attached to the \'%2$s\' custom post type because it is not active/registered. If you would like this %3$s to work, please activate/register said post type(s).', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', $attach_to_post_type_not_exist[0], 'CPT-onomy' );
													
										}
											
										?><tr valign="top"<?php if ( $inactive_cpt ) echo ' class="inactive"'; else if ( $attention_cpt ) echo ' class="attention"'; ?>>
											<td class="status">&nbsp;</td>
											<td class="label"><?php
												
												// edit url
												$edit_url = esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'edit' => $post_type, 'other' => ( $other ? '1' : NULL ) ), admin_url( 'options-general.php' ) ) );
												
												// activate url
												$activate_url = esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'activate' => $post_type, '_wpnonce' => wp_create_nonce( 'activate-cpt-' . $post_type ) ), admin_url( 'options-general.php' ) ), 'activate-cpt-' . $post_type );
												
												// delete url
												$delete_url = esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'delete' => $post_type, '_wpnonce' => wp_create_nonce( 'delete-cpt-' . $post_type ) ), admin_url( 'options-general.php' ) ), 'delete-cpt-' . $post_type );
												
												// view url
												$view_url = esc_url( add_query_arg( array( 'post_type' => $post_type ), admin_url( 'edit.php' ) ) );
												
												?><span class="label"><a href="<?php echo $edit_url; ?>"><?php _e( $CPT->label, CPT_ONOMIES_TEXTDOMAIN ); ?></a></span>
												<div class="row-actions">
												
													<span class="edit"><a href="<?php echo $edit_url; ?>" title="<?php esc_attr_e( 'Edit this custom post type\'s properties', CPT_ONOMIES_TEXTDOMAIN ); ?>"><?php _e( 'Edit', CPT_ONOMIES_TEXTDOMAIN ); ?></a></span><?php
																										
													if ( $inactive_cpt )
														echo ' | <a href="' . $activate_url . '" title="' . esc_attr__( 'Active this custom post type', CPT_ONOMIES_TEXTDOMAIN ) . '">' . sprintf( __( 'Activate this %s', CPT_ONOMIES_TEXTDOMAIN ), 'CPT' ) . '</a>';
														
													if ( !$other )
														echo ' | <span class="trash"><a class="submitdelete delete_cpt_onomy_custom_post_type" title="' . esc_attr__( 'Delete this custom post type', CPT_ONOMIES_TEXTDOMAIN ) . '" href="' . $delete_url . '">' . __( 'Delete', CPT_ONOMIES_TEXTDOMAIN ) . '</a></span>';
														
													if ( !( $attention_cpt || $inactive_cpt ) )
														echo ' | <span class="view"><a href="' . $view_url . '" title="' . esc_attr__( 'View posts', CPT_ONOMIES_TEXTDOMAIN ) . '">' . __( 'View posts', CPT_ONOMIES_TEXTDOMAIN ) . '</a></span>';
														
													if ( $attention_cpt )
														echo '<span class="attention"><a class="show_cpt_message" href="' . $edit_url . '" title="' . esc_attr__( 'Find out why this custom post type is not registered', CPT_ONOMIES_TEXTDOMAIN ) . '" alt="' . $message . '">' . __( 'Find out why this<br />CPT is not registered.', CPT_ONOMIES_TEXTDOMAIN ) . '</a></span>'; ?>
														
												</div>
												
											</td>
											<td class="name"><?php echo $post_type; ?></td>
											<td class="public"><?php if ( $CPT->public ) _e( 'Yes', CPT_ONOMIES_TEXTDOMAIN ); else _e( 'No', CPT_ONOMIES_TEXTDOMAIN ); ?></td>
											<td class="registered_custom_post_type_onomy<?php if ( $attention_cpt && $attention_cpt_onomy ) echo ' attention'; else if ( $attention_cpt_onomy ) echo ' error'; else if ( $is_registered_cpt_onomy ) echo ' working'; ?>"><?php
												
												if ( !$is_registered_cpt_onomy && $should_be_cpt_onomy ) {
												
													if ( $inactive_cpt )
														echo sprintf( __( 'No, because this %s is inactive.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT' ) . '<br /><a href="' . $activate_url . '" title="' . esc_attr__( 'Activate this custom post type', CPT_ONOMIES_TEXTDOMAIN ) . '">' . __( 'Activate this CPT', CPT_ONOMIES_TEXTDOMAIN ) . '</a>';
													else if ( $attention_cpt ) echo sprintf( __( 'No, because this %s is not registered.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT' ) . '<br /><a class="show_cpt_message" href="' . $edit_url . '" title="' . esc_attr__( 'Find out why this custom post type is not registered', CPT_ONOMIES_TEXTDOMAIN ) . '" alt="' . $message . '">' . __( 'Find out why', CPT_ONOMIES_TEXTDOMAIN ) . '</a>';
													else {
													
														if ( taxonomy_exists( $post_type ) )
															echo sprintf( __( 'This %s is not registered due to a taxonomy conflict.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '<br /><a class="show_cpt_message" href="' . $edit_url . '" title="' . sprintf( esc_attr__( 'Find out why this %s is not registered', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '" alt="' . $message . '">' . __( 'Find out why', CPT_ONOMIES_TEXTDOMAIN ) . '</a>';
														else
															echo sprintf( __( 'This %s is not registered due to a post type conflict.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '<br /><a class="show_cpt_message" href="' . $edit_url . '" title="' . sprintf( esc_attr__( 'Find out why this %s is not registered', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '" alt="' . $message . '">' . __( 'Find out why', CPT_ONOMIES_TEXTDOMAIN ) . '</a>';
															
													}
													
												}
												// this means this CPT-onomy is registered but not for ALL of its assigned custom post types
												else if ( $is_registered_cpt_onomy && $attach_to_post_type_not_exist && count( $attach_to_post_type_not_exist ) != count( $CPT->attach_to_post_type ) )
															echo sprintf( __( 'Yes, but there is a post type conflict.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '<br /><a class="show_cpt_message" href="' . $edit_url . '" title="' . sprintf( esc_attr__( 'Find out why this %s is not registered', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' ) . '" alt="' . $message . '">' . __( 'Find out why', CPT_ONOMIES_TEXTDOMAIN ) . '</a>';
												else if ( $is_registered_cpt_onomy && $programmatic_cpt_onomy ) {
													_e( 'Yes', CPT_ONOMIES_TEXTDOMAIN );
													echo '<br /><em><span class="gray notbold">' . sprintf( __( 'This %1$s is %2$sprogrammatically registered%3$s.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', '<a href="http://rachelcarden.com/cpt-onomies/documentation/register_cpt_onomy/" target="_blank">', '</a>' ) . '</span></em>';
												}
												else if ( $is_registered_cpt_onomy )
													_e( 'Yes', CPT_ONOMIES_TEXTDOMAIN );
												else _e( 'No', CPT_ONOMIES_TEXTDOMAIN );
												
											?></td>
											<td class="attached_to"><?php
											
												$text = NULL;
												if ( $is_registered_cpt_onomy ) {
													if ( ( $tax = get_taxonomy( $post_type ) ) && isset( $tax->object_type ) ) {
														foreach( $tax->object_type as $attached ) {
															if ( post_type_exists( $attached ) )
																$text .= '<a href="' . admin_url() . 'edit.php?post_type=' . $attached. '">' . __( get_post_type_object( $attached )->label, CPT_ONOMIES_TEXTDOMAIN ) . '</a><br />';
														
														}
													}
												}
												if ( empty( $text ) ) echo '&nbsp;';
												else echo $text;
												
											?></td>
											<td class="ability"><?php
											
												$text = NULL;
												if ( $is_registered_cpt_onomy ) {
													if ( $tax = get_taxonomy( $post_type ) ) {
													
														// get roles
														$wp_roles = new WP_Roles();
														if ( isset( $tax->restrict_user_capabilities ) && !empty( $tax->restrict_user_capabilities ) ) {
															foreach ( $wp_roles->role_names as $role => $name ) {
																if ( in_array( $role, $tax->restrict_user_capabilities ) )
																	$text .= __( $name, CPT_ONOMIES_TEXTDOMAIN ) . '<br />';
															}
														}
														// everyone with the capability can
														else
															$text = __( 'All user roles', CPT_ONOMIES_TEXTDOMAIN );
															
													}													
												}
												if ( empty( $text ) ) echo '&nbsp;';
												else echo $text;
												
											?></td>                            
										</tr><?php
										
									}
								}
							}
							if ( !$other ) {
								?><tr valign="top">
                                	<td class="add" colspan="6"><a href="<?php echo esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'edit' => 'new' ), admin_url( 'options-general.php' ) ) ); ?>"><?php _e( 'Add a new custom post type', CPT_ONOMIES_TEXTDOMAIN ); ?></a></td>
                                </tr><?php
                            }
                        ?></tbody>
                    </table><?php		
					break;
					
				//! Save CPT Meta Box
				case 'save_custom_post_type':
					submit_button( __( 'Save Your Changes', CPT_ONOMIES_TEXTDOMAIN ), 'primary', 'save_changes', false, array( 'id' => CPT_ONOMIES_DASH . '-save-changes' ) );
					break;
					
				//! Delete CPT Meta Box
				case 'delete_custom_post_type':
					$edit = $_REQUEST[ 'edit' ];
					$delete_url = esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'delete' => $edit, '_wpnonce' => wp_create_nonce( 'delete-cpt-' . $edit ) ), admin_url( 'options-general.php' ) ), 'delete-cpt-' . $edit );
					?>
                    <p><?php _e( 'Deleting your custom post type <strong>DOES NOT</strong> delete the actual posts. They\'ll be waiting for you if you decide to register this post type again. Just make sure you use the same name.', CPT_ONOMIES_TEXTDOMAIN ); ?></p>
                    <p><strong><?php _e( 'HOWEVER', CPT_ONOMIES_TEXTDOMAIN ); ?></strong>, <?php _e( 'there is no "undo" and, once you click "Delete", all of your settings will be gone.', CPT_ONOMIES_TEXTDOMAIN ); ?></p>
                    <a class="delete_cpt_onomy_custom_post_type" href="<?php echo $delete_url; ?>" title="<?php esc_attr_e( 'Delete this custom post type', CPT_ONOMIES_TEXTDOMAIN ); ?>"><?php _e( 'Delete this custom post type', CPT_ONOMIES_TEXTDOMAIN ); ?></a>
                    <?php
					break;
					
				//! Edit CPT Meta Box
				case 'edit_custom_post_type':
				
					// detect if we're editing a CPT AND whether its a new CPT or an "other" CPT
					// will create $new, $edit and $other
					extract( $this->detect_custom_post_type_new_edit_other() );
								
					$CPT = array();
					if ( $edit ) {
					
						if ( !$other )
							$CPT = (object) $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $edit ];
						
						// other post type
						else {
							$CPT = get_post_type_object( $edit );
							$CPT->other = true;
							if ( is_array( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) && array_key_exists( $edit, $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) ) {
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'attach_to_post_type' ] ) && !empty( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'attach_to_post_type' ] ) )
									$CPT->attach_to_post_type = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'attach_to_post_type' ];
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'has_cpt_onomy_archive' ] ) && !empty( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'has_cpt_onomy_archive' ] ) )
									$CPT->has_cpt_onomy_archive = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'has_cpt_onomy_archive' ];
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'cpt_onomy_archive_slug' ] ) && !empty( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'cpt_onomy_archive_slug' ] ) )
									$CPT->cpt_onomy_archive_slug = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'cpt_onomy_archive_slug' ];								
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'restrict_user_capabilities' ] ) && !empty( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'restrict_user_capabilities' ] ) )
									$CPT->restrict_user_capabilities = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'restrict_user_capabilities' ];
							}
						}
						
					}
					
					// check to see if attached post types exist
					$attach_to_post_type_not_exist = array();
					if ( !empty( $CPT->attach_to_post_type ) ) {
						foreach( $CPT->attach_to_post_type as $attached ) {
							if ( !post_type_exists( $attached ) )
								$attach_to_post_type_not_exist[] = $attached;
						}
					}
					
					// create the header label
					$label = '';
					if ( $new )
						$label = __( 'Creating a New Custom Post Type', CPT_ONOMIES_TEXTDOMAIN );
					else if ( $edit && $other )
						$label = __( get_post_type_object( $edit )->label, CPT_ONOMIES_TEXTDOMAIN );
					else if ( $edit )
						$label = __( $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $edit ][ 'label' ], CPT_ONOMIES_TEXTDOMAIN );
					
					$information = NULL;
					if ( $other )
						$information = sprintf( __( 'This custom post type is probably setup in your theme, or another plugin, but you can still register it for use as a %s. You cannot, however, manage the actual custom post type. Sorry, but that\'s up to the plugin and/or theme.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy' );
						
					?><div id="edit_custom_post_type_header"<?php if ( $information ) echo ' class="information"'; ?>>
						<span class="label"><?php echo $label; ?></span>
                   		<?php if ( $information ) { ?>
                    		<span class="information"><?php echo $information; ?></span>
                  		<?php } ?>
                    </div><?php
										
					// print errors
					if ( $edit ) {
					
						// detect if we're editing a CPT AND whether its a new CPT or an "other" CPT
						// will create $inactive_cpt, $is_registered_cpt, $is_registered_cpt_onomy,
						// $programmatic_cpt_onomy, $should_be_cpt_onomy, $attention_cpt
						// and $attention_cpt_onomy
						extract( $this->detect_custom_post_type_message_variables( $edit, $CPT, $other ) );
								                
		                ?><div class="edit_custom_post_type_message<?php if ( $inactive_cpt ) echo ' inactive'; else if ( $attention_cpt || $attention_cpt_onomy || ( $is_registered_cpt_onomy && $attach_to_post_type_not_exist && count( $attach_to_post_type_not_exist ) != count( $CPT->attach_to_post_type ) ) || ( $is_registered_cpt_onomy && $programmatic_cpt_onomy ) ) echo ' attention'; ?>"><?php
		                
		                	if ( $inactive_cpt )
								echo '<p>' . __( 'This custom post type is currently inactive.', CPT_ONOMIES_TEXTDOMAIN ) . '</p>';
							else if ( $attention_cpt ) {
								$builtin = get_post_types( array( '_builtin' => true ), 'objects' );
								echo '<p>';
								
									// builtin conflict
									if ( array_key_exists( $edit, $builtin ) )
										_e( 'This custom post type is not registered because the built-in WordPress post type, \'' . $builtin[ $edit ]->label . '\' is already registered under the name \'' . $edit . '\'. Sorry, but WordPress wins on this one. You\'ll have to change the post type name if you want to get \'' . $CPT->label . '\' up and running.', CPT_ONOMIES_TEXTDOMAIN );
									// other conflict
									else
										printf( __( 'This custom post type is not registered because another custom post type with the same name already exists. This other custom post type probably is setup in your theme or another plugin. %1$sCheck out the \'Other Custom Post Types\'%2$s to see what else has been registered.', CPT_ONOMIES_TEXTDOMAIN ), '<a href="' . esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE ), admin_url( 'options-general.php' ) ) ) . '#cpt-onomies-other-custom-post-types">', '</a>' );
							
								echo '</p>';
							}
							else if ( !$is_registered_cpt_onomy && $should_be_cpt_onomy ) {
							
								if ( taxonomy_exists( $edit ) )
									echo '<p>' . sprintf( __( 'This custom post type\'s %1$s is not registered because another taxonomy with the same name already exists. If you would like this %2$s to work, please remove the conflicting taxonomy.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomy' ) . '</p>';
								else
									echo '<p>' . sprintf( __( 'This custom post type\'s %1$s is not registered because the post type(s) it is attached to is not active/registered. If you would like this %2$s to work, please activate/register said post type(s).', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', 'CPT-onomy' ) . '</p>';
									
							}
							// this means this CPT-onomy is registered but not for ALL of its assigned custom post types
							else if ( $is_registered_cpt_onomy && $attach_to_post_type_not_exist && count( $attach_to_post_type_not_exist ) != count( $CPT->attach_to_post_type ) ) {
							
								if ( count( $attach_to_post_type_not_exist ) > 1 ) {
									$attach_to_post_type_not_exist_string = NULL;
									foreach( $attach_to_post_type_not_exist as $not_exist_index => $not_exist ) {
										if ( $not_exist_index == ( count( $attach_to_post_type_not_exist ) - 1 ) )
											$attach_to_post_type_not_exist_string .= ' and ';
										else if ( $not_exist_index > 0 )
											$attach_to_post_type_not_exist_string .= ', ';
										$attach_to_post_type_not_exist_string .= "'" . $not_exist . "'";
									}
									echo '<p>' . sprintf( __( 'This custom post type\'s %1$s is not attached to the %2$s custom post types because they are not active/registered. If you would like this %3$s to work, please activate/register said post types.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', $attach_to_post_type_not_exist_string, 'CPT-onomy' ) . '</p>';
									
								}
								else
									echo '<p>' . sprintf( __( 'This custom post type\'s %1$s is not attached to the \'%2$s\' custom post type because it is not active/registered. If you would like this %3$s to work, please activate/register said post type.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', $attach_to_post_type_not_exist[0], 'CPT-onomy' ) . '</p>';
										
							}
							else if ( $is_registered_cpt_onomy && $programmatic_cpt_onomy ) {
							
								echo '<p>' . sprintf( __( 'This custom post type is being programmatically registered as a %1$s, which overrides any settings defined below. %2$sCheck out the %3$s documentation%4$s to learn more.', CPT_ONOMIES_TEXTDOMAIN ), 'CPT-onomy', '<a href="http://rachelcarden.com/cpt-onomies/documentation/register_cpt_onomy/" target="_blank">', 'CPT-onomy', '</a>' ) . '</p>';
								
							}
							else
								echo '<p>' . __( 'This custom post type is registered and working.', CPT_ONOMIES_TEXTDOMAIN ) . '</p>';
								
						?></div>
                        
                  	<?php }
					
					// provide the original "name" for AJAX testing and back-end validation                    
					?><input type="hidden" id="<?php echo CPT_ONOMIES_DASH . '-custom-post-type-original-name'; ?>" name="<?php
						echo CPT_ONOMIES_UNDERSCORE . '_custom_post_types[';
							if ( $edit && !$other && !empty( $CPT ) ) echo $edit;
							else echo 'new_custom_post_type';
					echo '][original_name]'; ?>" value="<?php if ( $edit && !$other && !empty( $CPT ) ) echo $edit; ?>" /><?php
					
					// this allows each user to have a preference on whether to show the "advanced" tables
					$show_edit_tables = get_user_option( CPT_ONOMIES_UNDERSCORE . '_show_edit_tables', $user_ID );
					if ( !is_array( $show_edit_tables ) ) $show_edit_tables = array();
					
					// get the properties
					$cpt_properties = $this->get_plugin_options_page_cpt_properties( $edit && !empty( $edit ) ? $edit : NULL );
					
					foreach( $cpt_properties as $section => $properties ) {
						// they can only edit 'cpt_as_taxonomy'
						if ( !$other || ( $other && $section == 'cpt_as_taxonomy' ) ) {
						
							?><table class="edit_custom_post_type <?php echo $section; ?><?php if ( in_array( $section, $show_edit_tables ) ) echo ' show'; ?>" cellpadding="0" cellspacing="0" border="0">
								<tbody>
	                            	<?php if ( isset( $properties->type ) && $properties->type == 'group' && isset( $properties->data ) ) { ?>
	                                	<tr>
	                                    	<td class="label"><?php echo $properties->label; ?></td>
	                                        <td class="group<?php if ( isset( $properties->advanced ) ) echo ' advanced'; ?>">
	                                        	<table cellpadding="0" cellspacing="0" border="0">
													<?php foreach( $properties->data as $property_key => $property ) { ?>
														<tr>
															<td class="label"><?php echo $property->label; ?></td>
															<td class="field"><?php $this->print_plugin_options_edit_custom_post_type_field( $edit, $CPT, $property, $property_key ); ?></td>
														</tr>
													<?php } ?>
												</table>
	                                      	</td>
	                                  	</tr>
	                            	<?php }
									else {
										foreach( $properties as $property_key => $property ) { ?>
		                                	<tr>
												<td class="label"><?php echo $property->label; ?></td>
												<td class="field"><?php $this->print_plugin_options_edit_custom_post_type_field( $edit, $CPT, $property, $property_key ); ?></td>
											</tr>
	                               		<?php }
									} ?>
								</tbody>
							</table><?php
							
						}
					}
					break;
					
			}
		}
	}
	
	/**
	 * This function is invoked on the edit screen and prints the html for the form fields.
	 *
	 * You can set default values for all of the CPT-onomies settings by hooking into the
	 * 'custom_post_type_onomies_default_property_value' filter which passes two paramters:
	 * the $property_key and the $property_parent_key.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 * @param $cpt_key - the name for the custom post type we're editing
	 * @param $CPT - saved information for the custom post type we're editing
	 * @param object $property - info pulled from $this->get_plugin_options_page_cpt_properties() about this specific field
	 * @param string $property_key - name for property so information can be pulled from $property_info object.
	 * @param string $property_parent_key - allows for pulling property info from within an array.
	 * @filters 'custom_post_type_onomies_default_property_value' - $property_key, $property_parent_key
	 */
	public function print_plugin_options_edit_custom_post_type_field( $cpt_key, $CPT, $property, $property_key, $property_parent_key=NULL ) {
		global $cpt_onomies_manager;
		if ( current_user_can( 'manage_options' ) ) {
			
			$new = empty( $CPT ) ? true : false;
			$cpt_key = ( $new ) ? 'new_custom_post_type' : $cpt_key;
			
			// create field name
			$field_name = CPT_ONOMIES_UNDERSCORE . '_';
				if ( isset( $CPT->other ) ) $field_name .= 'other_';
			$field_name .= 'custom_post_types[' . $cpt_key . ']';			
			if ( isset( $property_parent_key ) ) $field_name .= '[' . $property_parent_key . ']';			
			$field_name .= '[' . $property_key . ']';
						
			switch( $property->type ) {
				
				case 'group':
					if ( isset( $property->data ) ) { ?>
                    	<table cellpadding="0" cellspacing="0" border="0">
                        	<?php foreach( $property->data as $subproperty_key => $subproperty ) { ?>
                            	<tr>
                                	<td class="label"><?php echo $subproperty->label; ?></td>
									<td class="field"><?php $this->print_plugin_options_edit_custom_post_type_field( $cpt_key, $CPT, $subproperty, $subproperty_key, $property_key ); ?></td>
                               	</tr>
                          	<?php } ?>
                      	</table>
                  	<?php }
					break;
					
				case 'text':
				case 'textarea':
				
					// get saved value
					$saved_property_value = NULL;
					if ( !$new ) {
						if ( isset( $property_parent_key ) && isset( $CPT->$property_parent_key ) ) {
							$property_parent = $CPT->$property_parent_key;
							if ( isset( $property_parent[ $property_key ] ) && !empty( $property_parent[ $property_key ] ) )
								$saved_property_value = $property_parent[ $property_key ];
						}
						else if ( isset( $CPT->$property_key ) ) $saved_property_value = $CPT->$property_key;
					}
					else {
						// allows you to set default values for the properties
						$saved_property_value = apply_filters( 'custom_post_type_onomies_default_property_value', isset( $property->default ) ? $property->default : NULL, $property_key, $property_parent_key );
					}
					
					if ( is_array( $saved_property_value ) && !empty( $saved_property_value ) ) $saved_property_value = esc_attr( strip_tags( implode( ', ', $saved_property_value ) ) );
					else if ( !empty( $saved_property_value ) ) $saved_property_value = esc_attr( strip_tags( $saved_property_value ) );
					
					// repairing 'read_private_post' bug, if necessary
					if ( $property_parent_key == 'capabilities' && $property_key == 'read_private_posts' && empty( $saved_property_value )
						&& isset( $CPT->capabilities ) && isset( $CPT->capabilities[ 'read_private_post' ] ) && !empty( $CPT->capabilities[ 'read_private_post' ] ) ) {
						$saved_property_value = $CPT->capabilities[ 'read_private_post' ];
					}
					
					if ( $property->type == 'text' ) { ?>
                    	
                        <input<?php if ( isset( $property->fieldid ) ) echo ' id="' . $property->fieldid . '"'; ?><?php if ( isset( $property->validation ) ) echo ' class="' . $property->validation . '"'; ?> type="text" name="<?php echo $field_name; ?>" value="<?php if ( !empty( $saved_property_value ) ) echo $saved_property_value; ?>"<?php if ( isset( $property->readonly ) && $property->readonly ) echo ' readonly="readonly"'; ?> />
					
					<?php }
					else if ( $property->type == 'textarea' ) { ?>
                    	
                        <textarea<?php if ( isset( $property->fieldid ) ) echo ' id="' . $property->fieldid . '"'; ?><?php if ( isset( $property->validation ) ) echo ' class="' . $property->validation . '"'; ?> name="<?php echo $field_name; ?>"><?php if ( !empty( $saved_property_value ) ) echo $saved_property_value; ?></textarea>
                   	
					<?php }
					
					if ( isset( $property->description ) && !empty( $property->description ) ) echo '<span class="description">' . $property->description . '</span>';
					
					break;
					
				case 'radio':
				case 'checkbox':
				
					if ( !empty( $property->data ) ) { ?>
									
						<table class="<?php echo $property->type; ?>" cellpadding="0" cellspacing="0" border="0">
							<?php
							
							$td = 1;
							$index = 1;
							foreach( $property->data as $data_name => $data ) {
							
								if ( $data_name == 'true' ) $data_name = 1;
								else if ( $data_name == 'false' ) $data_name = 0;
																	
								if ( $td == 1 ) echo '<tr>';
								
								// allows you to set default values for the properties			
								$default_value = apply_filters( 'custom_post_type_onomies_default_property_value', isset( $property->default ) ? $property->default : NULL, $property_key, $property_parent_key );
								// make sure value is clean
								if ( $property->type == 'checkbox' && isset( $default_value ) && !is_array( $default_value ) )
									$default_value = explode( ',', str_replace( ', ', ',', $default_value ) );
									
								$is_default = false;
								// if default value is an array
								if ( isset( $default_value ) && is_array( $default_value ) && in_array( $data_name, $default_value ) )
									$is_default = true;
								// if default value is not an array
								else if ( isset( $default_value ) && $data_name == $default_value )
									$is_default = true;
									
								$is_set = false;
								if ( !$new ) {
									if ( isset( $property_parent_key ) && isset( $CPT->$property_parent_key ) ) {
										$property_parent = $CPT->$property_parent_key;
										if ( isset( $property_parent[ $property_key ] ) && is_array( $property_parent[ $property_key ] ) && in_array( $data_name, $property_parent[ $property_key ] ) )
											$is_set = true;
										else if ( isset( $property_parent[ $property_key ] ) && $data_name == $property_parent[ $property_key ] )
											$is_set = true;
									}
									else if ( isset( $CPT->$property_key ) && is_array( $CPT->$property_key ) && in_array( $data_name, $CPT->$property_key ) )
										$is_set = true;
									else if ( isset( $CPT->$property_key ) && $data_name == $CPT->$property_key )
										$is_set = true;
									// If "other", check to make sure this particular post type has NO settings in the database before using the defaults
									// If "other" custom post type has no settings in the database, then its settings have not been "saved" and should therefore show the defaults
									else if ( isset( $CPT->other ) && !isset( $CPT->$property_key ) && $is_default ) {
										if ( empty( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) || empty( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $cpt_key ] ) )
											$is_set = true;
									}
								}
								// set the defaults
								else if ( $is_default )
									$is_set = true;
									
								?><td<?php if ( $index == count( $property->data ) && $td == 1 ) echo ' colspan="2"'; ?>><label><input<?php if ( isset( $property->validation ) ) echo ' class="' . $property->validation . '"'; ?> type="<?php echo $property->type; ?>" name="<?php echo $field_name; if ( $property->type == 'checkbox' ) { ?><?php if ( count( $property->data ) > 1 ) echo '[]'; ?><?php } ?>" value="<?php echo $data_name; ?>"<?php checked( $is_set, true ); ?> /><?php if ( $is_default ) { ?><strong><?php } echo $data->label; if ( $is_default ) { ?></strong><?php } ?></label></td><?php
								
								if ( $td == 1 )
									$td = 2;
								else if ( $td == 2 ) {
									$td = 1;
									echo '</tr>';
								}
								
								$index++;
									
							}
							
							if ( isset( $property->description ) && !empty( $property->description ) ) { ?>
                            	<tr><td<?php if ( count( $property->data ) > 1 ) echo ' colspan="2"'; ?>><span class="description"><?php echo $property->description; ?><?php
                                
									if ( $property->type == 'radio' && !isset( $property->default ) ) echo ' <span class="reset_property">Reset property</span>';
								
								?></span></td></tr>
							<?php }
								
							?>
											
						</table>
										
					<?php }
					break;
				
			}
			
		}
	}
	
}
		
?>