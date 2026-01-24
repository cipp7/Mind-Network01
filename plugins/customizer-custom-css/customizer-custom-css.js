/**
 * Live-update changes to the Custom CSS.
 */
( function( $ ) {
	wp.customize( 'wp_custom_css', function( value ) {
		value.bind( function( to ) {
			$( '#wp-custom-css' ).html( to );
		} );
	} );
} )( jQuery );