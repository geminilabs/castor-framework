<?php defined( 'WPINC' ) || die;

global $wp_version;

if( version_compare( '5.6.0', phpversion(), '>=' )) {
	wp_die(
		__( 'You must be using PHP 5.6.0 or greater.', 'castor' ),
		__( 'Unsupported PHP version', 'castor' )
	);
}

if( version_compare( '4.7.0', $wp_version, '>=' )) {
	wp_die(
		__( 'You must be using WordPress 4.7.0 or greater.', 'castor' ),
		__( 'Unsupported WordPress version', 'castor' )
	);
}

if( is_customize_preview() && filter_input( INPUT_GET, 'theme' )) {
	wp_die(
		__( 'Theme must be activated prior to using the customizer.', 'castor' )
	);
}

\GeminiLabs\Castor\Application::getInstance()->init();