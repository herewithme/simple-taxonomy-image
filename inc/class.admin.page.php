<?php
class STI_Admin_Page {

	function __construct() {
		add_action( 'admin_menu', array( &$this, 'settingsMenu' ) );
		add_action( 'admin_init', array( &$this, 'registerSettings' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'registerScripts' ) );
		add_action( 'wp_ajax_migrateFromTaxonomyImage', array( &$this, 'a_migration' ) );
	}
	
	function registerScripts( $hook = '' ) {
		if( $hook != 'settings_page_simple-taxonomy-image' )
			return false;
		
		wp_enqueue_script( 'migrate-script', STI_URL.'/ressources/js/migrate.js', array( 'jquery' ), STI_VERSION, true );
		wp_enqueue_style( 'migrate-css', STI_URL.'/ressources/css/admin-migrate.css' );
	}
	
	/**
	 * Admin Menu.
	 *
	 * Create the admin menu link for the settings page.
	 *
	 * @access    private
	 * @since     0.7
	 */
	function settingsMenu() {
		add_options_page(
			esc_html__( 'Simple Taxonomy Images', 'sti' ), /* HTML <title> tag. */
			esc_html__( 'Simple Taxonomy Images', 'sti' ), /* Link text in admin menu. */
			'manage_options',
			STI_OPTIONS_NAME,
			array( &$this, 'settingsPage' )
		);
	}

	/**
	 * Register settings with WordPress.
	 *
	 * This plugin will store to sets of settings in the
	 * options table. The first is named 'taxonomy_image_plugin'
	 * and stores the associations between terms and images. The
	 * keys in this array represent the term_taxonomy_id of the
	 * term while the value represents the ID of the image
	 * attachment.
	 *
	 * The second setting is used to store everything else. As of
	 * version 0.7 it has one key named 'taxonomies' whichi is a
	 * flat array consisting of taxonomy names representing a
	 * black-list of registered taxonomies. These taxonomies will
	 * NOT be given an image UI.
	 *
	 * @access    private
	 */
	function registerSettings() {
		register_setting(
			'sti_plugin',
			'sti_plugin',
			array( &$this, 'SanitizeAssociations' )
			);

		register_setting(
			STI_OPTIONS_NAME,
			STI_OPTIONS_NAME,
			array( &$this, 'settingsSanitize' )
			);

		add_settings_section(
			STI_OPTIONS_NAME,
			esc_html__( 'Settings', 'sti' ),
			'__return_false',
			STI_OPTIONS_NAME
		);

		add_settings_field(
			'sti',
			esc_html__( 'Taxonomies', 'sti' ),
			array( &$this, 'controlTaxonomies' ),
			STI_OPTIONS_NAME,
			STI_OPTIONS_NAME
		);
		
		add_settings_field(
			'sti_migrate',
			esc_html__( 'Migration', 'sti' ),
			array( &$this, 'migrateFromTaxo' ),
			STI_OPTIONS_NAME,
			STI_OPTIONS_NAME
		);
	}

	function controlTaxonomies() {
		$settings = get_option( STI_OPTIONS_NAME );
		$taxonomies = get_taxonomies( array( 'show_ui' => true ), 'objects' );
		foreach ( (array) $taxonomies as $taxonomy ) {
			if ( ! isset( $taxonomy->name ) ) {
				continue;
			}
			if ( ! isset( $taxonomy->label ) ) {
				continue;
			}
			if ( ! isset( $taxonomy->show_ui ) || empty( $taxonomy->show_ui ) ) {
				continue;
			}
			$id = 'sti-' . $taxonomy->name;
			$checked = '';
			if ( isset( $settings['taxonomies'] ) && in_array( $taxonomy->name, (array) $settings['taxonomies'] ) ) {
				$checked = ' checked="checked"';
			}
			echo "\n" . '<p><label for="' . esc_attr( $id ) . '">';
			echo '<input' . $checked . ' id="' . esc_attr( $id ) . '" type="checkbox" name="'.STI_OPTIONS_NAME.'[taxonomies][]" value="' . esc_attr( $taxonomy->name ) . '">';
			echo ' ' . esc_html( $taxonomy->label ) . '</label></p>';
		}
	}
	
	function migrateFromTaxo( ) {
		echo '<input type="button" class="button-secondary migrate_taxonomy_image" value="'.esc_attr__( 'Migrate from taxonomy image', 'sti' ).'" /><span class="result"></span>';
		echo '<p class="description"><b>'.__( 'All the associations will be overwritten.', 'sti' ).'</b></p>';
	}
	
	/**
	 * Sanitize Settings.
	 *
	 * This function is responsible for ensuring that
	 * all values within the 'taxonomy_image_plugin_settings'
	 * options are of the appropriate type.
	 *
	 * @param     array     Unknown.
	 * @return    array     Multi-dimensional array of sanitized settings.
	 *
	 * @access    private
	 * @since     0.7
	 */
	function settingsSanitize( $dirty ) {
		$clean = array();
		if ( isset( $dirty['taxonomies'] ) ) {
			$taxonomies = get_taxonomies();
			foreach ( (array) $dirty['taxonomies'] as $taxonomy ) {
				if ( in_array( $taxonomy, $taxonomies ) ) {
					$clean['taxonomies'][] = $taxonomy;
				}
			}
		}

		/* translators: Notice displayed on the custom administration page. */
		$message = __( 'Image support for taxonomies successfully updated', 'sti' );
		if ( empty( $clean ) ) {
			/* translators: Notice displayed on the custom administration page. */
			$message = __( 'Image support has been disabled for all taxonomies.', 'sti' );
		}

		add_settings_error( STI_OPTIONS_NAME, 'taxonomies_updated', esc_html( $message ), 'updated' );

		return $clean;
	}

	/**
	 * Settings Page Template.
	 *
	 * This function in conjunction with others usei the WordPress
	 * Settings API to create a settings page where users can adjust
	 * the behaviour of this plugin. Please see the following functions
	 * for more insight on the output generated by this function:
	 *
	 * taxonomy_image_plugin_control_taxonomies()
	 *
	 * @access    private
	 * @since     0.7
	 */
	function settingsPage() {
		print "\n" . '<div class="wrap">';
		screen_icon();

		/* translators: Heading of the custom administration page. */
		print "\n" . '<h2>' . esc_html__( 'Taxonomy Images Plugin Settings', 'sti' ) . '</h2>';
		print "\n" . '<div id="taxonomy-images">';
		print "\n" . '<form action="options.php" method="post">';

		settings_fields( STI_OPTIONS_NAME );
		do_settings_sections( STI_OPTIONS_NAME );

		/* translators: Button on the custom administration page. */
		print "\n" . '<div class="button-holder"><input name="submit" class="button-primary" type="submit" value="' . esc_attr__( 'Save Changes', 'sti' ) . '" /></div>';
		print "\n" . '</div></form></div>';
	}
	
	function a_migration() {
		if( !$ttoptions = get_option( 'taxonomy_image_plugin' ) ) {
			STI_Admin::jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'No options founded', 'sti' )
			) );
		}

		foreach( $ttoptions as $tt => $attachId ) {
			update_term_taxonomy_meta( $tt, 'term_taxo_image', $attachId );
		}

		STI_Admin::jsonResponse( array(
				'status'=> 'good',
				'why'	=> esc_html__( 'Migration successfull !', 'sti' )
			) );
		die();
	}
}
?>