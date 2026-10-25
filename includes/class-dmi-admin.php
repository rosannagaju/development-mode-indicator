<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMI_Admin {
	const PAGE_SLUG = 'development-mode-indicator';

	public function register() {
		add_action( 'init', array( 'DMI_Settings', 'register' ) );
		add_action( 'rest_api_init', array( 'DMI_Settings', 'register_rest_api' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function menu() {
		add_options_page(
			__( 'Development Mode', 'development-mode-indicator' ),
			__( 'Development Mode', 'development-mode-indicator' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'page' )
		);
	}

	public function assets( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		$asset_file = DMI_DIR . 'build/index.asset.php';
		$asset      = file_exists( $asset_file ) ? include $asset_file : array( 'dependencies' => array( 'wp-element' ), 'version' => DMI_VERSION );
		wp_enqueue_style( 'dmi-admin', DMI_URL . 'build/index.css', array( 'wp-components' ), $asset['version'] );
		wp_enqueue_script( 'dmi-admin', DMI_URL . 'build/index.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'dmi-admin', 'development-mode-indicator' );
	}

	public function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="dmi-admin-page" id="dmi-admin-app"></div>';
	}
}