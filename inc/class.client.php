<?php
class STI_Client{

	function __construct() {
		add_filter( 'get_terms', array( &$this, 'addTermsImage' ), 9, 3 );
		add_filter( 'wp_get_object_terms', array( &$this, 'addTermsImage' ), 9, 3 );
		add_filter( 'get_term', array( &$this, 'addTermImage' ), 9);
	}

	function addTermsImage( $terms, $taxonomies, $args ) {
		$taxonomies = get_option( STI_OPTIONS_NAME );
		
		if( $args['fields'] == 'all' ) {
			foreach( $terms as &$term ) {
				if( !in_array( &$term->taxonomy ,$taxonomies['taxonomies'] ) )
					continue;
				$term->term_taxo_image = get_term_taxonomy_meta( $term->term_taxonomy_id, 'term_taxo_image', true ) ;
			}
		}
		return $terms;
	}

	function addTermImage( $term ) {
		$taxonomies = get_option( STI_OPTIONS_NAME );
		
		if( in_array( $term->taxonomy ,$taxonomies['taxonomies'] ) )
			$term->term_taxo_image = get_term_taxonomy_meta( $term->term_taxonomy_id, 'term_taxo_image', true ) ;
		
		return $term;
	}
}
?>