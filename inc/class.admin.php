<?php
class STI_Admin{

	function __construct() {
		add_filter( 'attachment_fields_to_edit', 				array( &$this, 'addModalButton' ), 20, 2 );
		add_filter( 'admin_init', 								array( &$this, 'dynmanicHooks' ) );
		
		add_action( 'admin_print_scripts-media-upload-popup', 	array( &$this, 'mediaUploadPopupJs' ) );
		add_action( 'admin_print_scripts-edit-tags.php', 		array( &$this,'editTagsJs' ) );
		
		add_action( 'admin_print_styles-edit-tags.php', 		array( &$this, 'addCss' ) );
		add_action( 'admin_print_styles-media-upload-popup', 	array( &$this, 'addCss' ) );
		
		add_action( 'wp_ajax_sti_create_association', 			array( &$this, 'createAssociation' ) );
		add_action( 'wp_ajax_sti_remove_association', 			array( &$this, 'removeAssociation' ) );
	}
	
	function mediaUploadPopupJs() {
		
		wp_enqueue_script(
			'sti-media-upload-popup',
			STI_URL.'/ressources/js/media-upload-popup.js',
			array( 'jquery', 'thickbox' ),
			STI_VERSION
		);

		wp_localize_script( 'sti-media-upload-popup', 'TaxonomyImagesModal', array (
			'termBefore'	=> esc_html__( '&#8220;', 'sti' ),
			'termAfter'		=> esc_html__( '&#8221;', 'sti' ),
			'associating'	=> esc_html__( 'Associating &#8230;', 'sti' ),
			'success'		=> esc_html__( 'Successfully Associated', 'sti' ),
			'removing'		=> esc_html__( 'Removing &#8230;', 'sti' ),
			'removed'		=> esc_html__( 'Successfully Removed', 'sti' )
		) );
	}
	
	function editTagsJs() {
		
		wp_enqueue_script(
			'sti-edit-tags',
			STI_URL.'/ressources/js/edit-tags.js',
			array( 'jquery', 'thickbox' ),
			STI_VERSION
		);
		wp_localize_script( 'sti-edit-tags', 'taxonomyImagesPlugin', array (
			'nonce'		=> wp_create_nonce( 'sti-remove-association' ),
			'img_src'	=> STI_URL.'/ressources/css/default.png',
			'tt_id'		=> 0,
			'image_id'	=> 0,
		) );
	}

	function addCss() {
		wp_enqueue_style(
			'sti-edit-tags',
			STI_URL.'/ressources/css/admin.css',
			array(),
			STI_VERSION,
			'screen'
		);
		wp_enqueue_style( 'thickbox' );
	}

	function addModalButton( $fields, $post ) {
		if ( isset( $fields['image-size'] ) && isset( $post->ID ) ) {
			$image_id = (int) $post->ID;
	
			$o = '<div class="sti-modal-control" id="' . esc_attr( 'sti-modal-control-' . $image_id ) . '">';

				$o.= '<span class="button button-secondary create-association">' . sprintf( esc_html__( 'Associate with %1$s', 'sti' ), '<span class="term-name">' . esc_html__( 'this term', 'sti' ) . '</span>' ) . '</span>';

				$o.= '<span class="remove-association">' . sprintf( esc_html__( 'Remove association with %1$s', 'sti' ), '<span class="term-name">' . esc_html__( 'this term', 'sti' ) . '</span>' ) . '</span>';

				$o.= '<input class="sti-button-image-id" name="' . esc_attr( 'taxonomy-image-button-image-id-' . $image_id ) . '" type="hidden" value="' . esc_attr( $image_id ) . '" />';

				$o.= '<input class="sti-button-nonce-create" name="' . esc_attr( 'sti-button-nonce-create-' . $image_id ) . '" type="hidden" value="' . esc_attr( wp_create_nonce( 'sti-create-association' ) ) . '" />';

				$o.= '<input class="sti-button-nonce-remove" name="' . esc_attr( 'sti-button-nonce-remove-' . $image_id ) . '" type="hidden" value="' . esc_attr( wp_create_nonce( 'sti-remove-association' ) ) . '" />';

			$o.= '</div>';

			$fields['image-size']['extra_rows']['sti-button']['html'] = $o;
		}
		return $fields;
	}

	function dynmanicHooks() {
		// Get the taxonomies
		$taxonomies = get_option( STI_OPTIONS_NAME );
		
		// If no selected don't add the hooks
		if( !isset( $taxonomies['taxonomies'] ) || empty( $taxonomies['taxonomies'] ) )
			return false;
		
		foreach ( $taxonomies['taxonomies'] as $taxonomy ) {
			add_filter( 'manage_' . $taxonomy . '_custom_column', 	array( &$this, 'taxonomyRows' ), 15, 3 );
			add_filter( 'manage_edit-' . $taxonomy . '_columns', 	array( &$this, 'taxonomyColumns' ) );
			add_action( $taxonomy . '_edit_form_fields', 			array( &$this, 'editTagForm' ), 10, 2 );
		}
	}

	function taxonomyRows( $row, $column_name, $term_id ) {
		if ( 'sti-images' === $column_name ) {
			global $taxonomy;
			return $row . $this->controlImage( $term_id, $taxonomy );
		}
		return $row;
	}

	function editTagForm( $term, $taxonomy ) {
		$taxonomy = get_taxonomy( $taxonomy );
		$name = __( 'term', 'sti' );
		if ( isset( $taxonomy->labels->singular_name ) ) {
			$name = strtolower( $taxonomy->labels->singular_name );
		}
		?>
		<tr class="form-field hide-if-no-js">
			<th scope="row" valign="top"><label for="description"><?php print esc_html__( 'Image', 'sti' ) ?></label></th>
			<td>
				<?php echo $this->controlImage( $term->term_id, $taxonomy->name ); ?>
				<div class="clear"></div>
				<span class="description"><?php printf( esc_html__( 'Associate an image from your media library to this %1$s.', 'sti' ), esc_html( $name ) ); ?></span>
			</td>
		</tr>
		<?php
	}

	function taxonomyColumns( $original_columns ) {
		$new_columns = $original_columns;
		array_splice( $new_columns, 1 );
		$new_columns['sti-images'] = esc_html__( 'Image', 'sti' );
		return array_merge( $new_columns, $original_columns );
	}

	function controlImage( $term_id, $taxonomy ) {
		$o = "";
		$term = get_term( $term_id, $taxonomy );

		$tt_id = 0;
		if ( isset( $term->term_taxonomy_id ) ) {
			$tt_id = (int) $term->term_taxonomy_id;
		}

		$taxonomy = get_taxonomy( $taxonomy );

		$name = esc_html__( 'term', 'sti' );
		if ( isset( $taxonomy->labels->singular_name ) ) {
			$name = strtolower( $taxonomy->labels->singular_name );
		}

		$hide = ' hide';

		$term = get_term( $term_id, $taxonomy->name );
		$img = $this->getImageSrc( $term->term_taxo_image );

		$o .= "\n" . '<div id="' . esc_attr( 'sti-control-' . $tt_id ) . '" class="taxonomy-image-control hide-if-no-js">';
			$o.= "\n" . '<a class="thickbox sti-thumbnail" href="' . esc_url( admin_url( 'media-upload.php' ) . '?type=image&tab=library&post_id=0&TB_iframe=true' ) . '" title="' . esc_attr( sprintf( __( 'Associate an image with the %1$s named &#8220;%2$s&#8221;.', 'sti' ), $name, $term->name ) ) . '"><img id="' . esc_attr( 'taxonomy_image_plugin_' . $tt_id ) . '" src="' . esc_url( $img ) . '" alt="" /></a>';
			$o.= "\n" . '<a class="control upload thickbox" href="' . esc_url( admin_url( 'media-upload.php' ) . '?type=image&tab=type&post_id=0&TB_iframe=true' ) . '" title="' . esc_attr( sprintf( __( 'Upload a new image for this %s.', 'sti' ), $name ) ) . '">' . esc_html__( 'Upload.', 'sti' ) . '</a>';
			$o.= "\n" . '<a class="control remove' . $hide . '" href="#" id="' . esc_attr( 'remove-' . $tt_id ) . '" rel="' . esc_attr( $tt_id ) . '" title="' . esc_attr( sprintf( __( 'Remove image from this %s.', 'sti' ), $name ) ) . '">' . esc_html__( 'Delete', 'sti' ) . '</a>';
			$o.= "\n" . '<input type="hidden" class="tt_id" name="' . esc_attr( 'tt_id-' . $tt_id ) . '" value="' . esc_attr( $tt_id ) . '" />';

			$o.= "\n" . '<input type="hidden" class="image_id" name="' . esc_attr( 'image_id-' . $tt_id ) . '" value="' . esc_attr( $term->term_taxo_image ) . '" />';

			if ( isset( $term->name ) && isset( $term->slug ) )
				$o.= "\n" . '<input type="hidden" class="term_name" name="' . esc_attr( 'term_name-' . $term->slug ) . '" value="' . esc_attr( $term->name ) . '" />';
		$o.= "\n" . '</div>';

		return $o;
	}

	function createAssociation() {
		if ( ! isset( $_POST['tt_id'] ) ) {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'tt_id not sent', 'sti' ),
			) );
		}
	
		$tt_id = absint( $_POST['tt_id'] );
		if ( empty( $tt_id ) ) {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'tt_id is empty', 'sti' ),
			) );
		}
	
		if ( ! $this->checkPermissions( $tt_id ) ) {
			$this->jsonResponse( array(
				'status' => 'bad',
				'why'	=> esc_html__( 'You do not have the correct capability to manage this term', 'sti' ),
			) );
			die();
		}
	
		if ( ! isset( $_POST['wp_nonce'] ) ) {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'No nonce included.', 'sti' ),
			) );
			die();
		}
	
		if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'sti-create-association' ) ) {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'Nonce did not match', 'sti' ),
			) );
			die();
		}
	
		if ( ! isset( $_POST['attachment_id'] ) ) {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'Image id not sent', 'sti' )
			) );
			die();
		}
	
		$image_id = absint( $_POST['attachment_id'] );
		if ( empty( $image_id ) ) {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'Image id is not a positive integer', 'sti' )
			) );
			die();
		}
		
		if( update_term_taxonomy_meta( $tt_id, 'term_taxo_image', $image_id ) ){
			$this->jsonResponse( array(
				'status'=> 'good',
				'why'	=> esc_html__( 'Image successfully associated', 'sti' ),
				'attachment_thumb_src' => $this->getImageSrc( $image_id )
			) );
			die();
		}else {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'Association could not be created', 'sti' )
			) );
			die();
		}
	}

	function removeAssociation() {
		if ( ! isset( $_POST['tt_id'] ) ) {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'tt_id not sent', 'sti' ),
			) );
		}

		$tt_id = absint( $_POST['tt_id'] );
		if ( empty( $tt_id ) ) {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'tt_id is empty', 'sti' ),
			) );
		}

		if ( ! $this->checkPermissions( $tt_id ) ) {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'You do not have the correct capability to manage this term', 'sti' ),
			) );
		}

		if ( ! isset( $_POST['wp_nonce'] ) ) {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'No nonce included', 'sti' ),
			) );
		}

		if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'sti-remove-association') ) {
			$this->jsonResponse( array(
				'status' => 'bad',
				'why'    => esc_html__( 'Nonce did not match', 'sti' ),
			) );
		}

		$meta = get_term_taxonomy_meta( $tt_id, 'term_taxo_image' );
		if ( ! isset( $meta ) ) {
			$this->jsonResponse( array(
				'status'=> 'good',
				'why'	=> esc_html__( 'Nothing to remove', 'sti' )
			) );
		}

		if ( delete_term_taxonomy_meta( $tt_id, 'term_taxo_image' ) ) {
			$this->jsonResponse( array(
				'status'=> 'good',
				'why'	=> esc_html__( 'Association successfully removed', 'sti' )
			) );
		}
		else {
			$this->jsonResponse( array(
				'status'=> 'bad',
				'why'	=> esc_html__( 'Association could not be removed', 'sti' )
			) );
		}
		/* Don't know why, but something didn't work. */
		$this->jsonResponse();
	}

	function checkPermissions( $tt_id ) {
		
		$data = $this->getTermInfo( $tt_id );
		if ( ! isset( $data['taxonomy'] ) ) {
			return false;
		}

		$taxonomy = get_taxonomy( $data['taxonomy'] );
		if ( ! isset( $taxonomy->cap->edit_terms ) ) {
			return false;
		}

		return current_user_can( $taxonomy->cap->edit_terms );
	}

	function getTermInfo( $tt_id ) {
		
		static $cache = array();
		if ( isset( $cache[$tt_id] ) ) {
			return $cache[$tt_id];
		}
		
		global $wpdb;
		$data = $wpdb->get_results( $wpdb->prepare( "SELECT term_id, taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d LIMIT 1", $tt_id ) );
		if ( isset( $data[0]->term_id ) ) {
			$cache[$tt_id]['term_id'] = absint( $data[0]->term_id );
		}
		if ( isset( $data[0]->taxonomy ) ) {
			$cache[$tt_id]['taxonomy'] = sanitize_title_with_dashes( $data[0]->taxonomy );
		}
		if ( isset( $cache[$tt_id] ) ) {
			return $cache[$tt_id];
		}
		return array();
	}

	function getImageSrc( $att_id ) {
		// Get image infos
		$img = wp_get_attachment_image_src( $att_id );

		if( !isset( $img[0] ) || empty( $img ) )
			return STI_URL.'/ressources/css/default.png';
		else
			return $img[0];
	}

	
	function jsonResponse( $args ) {
		/* translators: An ajax request has failed for an unknown reason. */
		$response = wp_parse_args( $args, array(
			'status'=> 'bad',
			'why'	=> esc_html__( 'Unknown error encountered', 'sti' )
		) );
		header( 'Content-type: application/jsonrequest' );
		echo json_encode( $response );
		die();
	}
}
?>