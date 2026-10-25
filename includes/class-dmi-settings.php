<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMI_Settings {
	const OPTION_NAME = 'dmi_settings';

	public static function defaults() {
		return array(
			'enabled'      => true,
			'mode'         => 'development',
			'customMode'   => '',
			'message'      => 'This website is currently under development.',
			'displayType'  => 'admin_bar',
			'adminBar'     => array(
				'icon'            => 'admin-tools',
				'backgroundColor' => '#d63638',
				'textColor'       => '#ffffff',
			),
			'watermark'    => array(
				'position'            => 'center',
				'backendDisplay'      => 'watermark',
				'adminBarBackgroundColor' => '#d63638',
				'adminBarTextColor'   => '#ffffff',
				'opacity'             => 0.15,
				'rotation'            => -20,
				'fontSize'            => 48,
				'textColor'           => '#d63638',
				'background'          => '',
			),
			'border' => array(
				'position'       => 'bottom',
				'primaryColor'   => '#d63638',
				'secondaryColor' => '#ffffff',
				'height'         => 8,
				'patternSize'    => 20,
			),
		);
	}

	public static function activate() {
		if ( false === get_option( self::OPTION_NAME ) ) {
			add_option( self::OPTION_NAME, self::defaults() );
		}
	}

	public static function deactivate() {}

	public static function get() {
		return self::sanitize( get_option( self::OPTION_NAME, array() ) );
	}

	public static function register() {
		register_setting(
			'dmi_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'object',
				'default'           => self::defaults(),
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
	}

	public static function register_rest_api() {
		register_rest_route(
			'development-mode-indicator/v1',
			'/settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'rest_get_settings' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'development-mode-indicator/v1',
			'/settings',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'rest_update_settings' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'settings' => array(
						'required'          => true,
						'type'              => 'object',
						'sanitize_callback' => array( __CLASS__, 'sanitize' ),
					),
				),
			)
		);
	}

	public static function rest_get_settings() {
		return rest_ensure_response( self::get() );
	}

	public static function rest_update_settings( WP_REST_Request $request ) {
		$settings = self::sanitize( $request->get_param( 'settings' ) );
		update_option( self::OPTION_NAME, $settings );

		return rest_ensure_response( $settings );
	}

	public static function schema() {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'enabled'      => array( 'type' => 'boolean' ),
				'mode'         => array( 'type' => 'string' ),
				'customMode'   => array( 'type' => 'string' ),
				'message'      => array( 'type' => 'string' ),
				'displayType'  => array( 'type' => 'string' ),
				'adminBar'     => array(
					'type'       => 'object',
					'properties' => array(
						'icon'            => array( 'type' => 'string' ),
						'backgroundColor' => array( 'type' => 'string' ),
						'textColor'       => array( 'type' => 'string' ),
					),
				),
				'watermark'    => array(
					'type'       => 'object',
					'properties' => array(
						'position'                => array( 'type' => 'string' ),
						'backendDisplay'          => array( 'type' => 'string' ),
						'adminBarBackgroundColor' => array( 'type' => 'string' ),
						'adminBarTextColor'       => array( 'type' => 'string' ),
						'showAdminBar'            => array( 'type' => 'boolean' ),
						'opacity'                 => array( 'type' => 'number' ),
						'rotation'                => array( 'type' => 'integer' ),
						'fontSize'                => array( 'type' => 'integer' ),
						'textColor'               => array( 'type' => 'string' ),
						'background'              => array( 'type' => 'string' ),
					),
				),
				'border' => array(
					'type'       => 'object',
					'properties' => array(
						'position'       => array( 'type' => 'string' ),
						'primaryColor'   => array( 'type' => 'string' ),
						'secondaryColor' => array( 'type' => 'string' ),
						'height'         => array( 'type' => 'integer' ),
						'patternSize'    => array( 'type' => 'integer' ),
					),
				),
			),
		);
	}

	public static function sanitize( $value ) {
		$defaults = self::defaults();
		$value    = is_array( $value ) ? $value : array();
		$modes    = array( 'development', 'staging', 'testing', 'qa', 'maintenance', 'custom' );
		$types    = array( 'admin_bar', 'watermark', 'border', 'bottom_border' );
		$positions = array( 'center', 'bottom-right', 'bottom-left', 'top-right' );

		$settings                = $defaults;
		$settings['enabled']     = ! empty( $value['enabled'] );
		$settings['mode']        = in_array( $value['mode'] ?? '', $modes, true ) ? $value['mode'] : $defaults['mode'];
		$settings['customMode']  = sanitize_text_field( $value['customMode'] ?? '' );
		$settings['message']     = sanitize_textarea_field( $value['message'] ?? $defaults['message'] );
		$normalized_display_type = $value['displayType'] ?? $defaults['displayType'];
		if ( 'bottom_border' === $normalized_display_type ) {
			$normalized_display_type = 'border';
		}
		$settings['displayType'] = in_array( $normalized_display_type, $types, true ) ? $normalized_display_type : $defaults['displayType'];

		$admin_bar = is_array( $value['adminBar'] ?? null ) ? $value['adminBar'] : array();
		$settings['adminBar']['icon']            = sanitize_html_class( $admin_bar['icon'] ?? $defaults['adminBar']['icon'] );
		$settings['adminBar']['backgroundColor'] = sanitize_hex_color( $admin_bar['backgroundColor'] ?? '' ) ?: $defaults['adminBar']['backgroundColor'];
		$settings['adminBar']['textColor']       = sanitize_hex_color( $admin_bar['textColor'] ?? '' ) ?: $defaults['adminBar']['textColor'];

		$watermark = is_array( $value['watermark'] ?? null ) ? $value['watermark'] : array();
		$settings['watermark']['position']   = in_array( $watermark['position'] ?? '', $positions, true ) ? $watermark['position'] : $defaults['watermark']['position'];
		$backend_displays = array( 'watermark', 'admin_bar' );
		$legacy_backend_display = ! empty( $watermark['showAdminBar'] ) ? 'admin_bar' : $defaults['watermark']['backendDisplay'];
		$settings['watermark']['backendDisplay'] = in_array( $watermark['backendDisplay'] ?? '', $backend_displays, true ) ? $watermark['backendDisplay'] : $legacy_backend_display;
		$settings['watermark']['adminBarBackgroundColor'] = sanitize_hex_color( $watermark['adminBarBackgroundColor'] ?? '' ) ?: $defaults['watermark']['adminBarBackgroundColor'];
		$settings['watermark']['adminBarTextColor'] = sanitize_hex_color( $watermark['adminBarTextColor'] ?? '' ) ?: $defaults['watermark']['adminBarTextColor'];
		$settings['watermark']['opacity']    = min( 1, max( 0.01, (float) ( $watermark['opacity'] ?? $defaults['watermark']['opacity'] ) ) );
		$settings['watermark']['rotation']   = min( 45, max( -45, (int) ( $watermark['rotation'] ?? $defaults['watermark']['rotation'] ) ) );
		$settings['watermark']['fontSize']   = min( 120, max( 16, (int) ( $watermark['fontSize'] ?? $defaults['watermark']['fontSize'] ) ) );
		$settings['watermark']['textColor']  = sanitize_hex_color( $watermark['textColor'] ?? '' ) ?: $defaults['watermark']['textColor'];
		$settings['watermark']['background'] = sanitize_hex_color( $watermark['background'] ?? '' ) ?: '';

		$border = is_array( $value['border'] ?? null ) ? $value['border'] : array();
		$border_positions = array( 'top', 'left', 'right', 'bottom' );
		$settings['border']['position']       = in_array( $border['position'] ?? '', $border_positions, true ) ? $border['position'] : $defaults['border']['position'];
		$settings['border']['primaryColor']   = sanitize_hex_color( $border['primaryColor'] ?? '' ) ?: $defaults['border']['primaryColor'];
		$settings['border']['secondaryColor'] = sanitize_hex_color( $border['secondaryColor'] ?? '' ) ?: $defaults['border']['secondaryColor'];
		$settings['border']['height']         = min( 32, max( 4, (int) ( $border['height'] ?? $defaults['border']['height'] ) ) );
		$settings['border']['patternSize']    = min( 80, max( 8, (int) ( $border['patternSize'] ?? $defaults['border']['patternSize'] ) ) );

		return $settings;
	}

	public static function mode_label( $settings ) {
		$labels = array(
			'development' => __( 'Development', 'development-mode-indicator' ),
			'staging'     => __( 'Staging', 'development-mode-indicator' ),
			'testing'     => __( 'Testing', 'development-mode-indicator' ),
			'qa'          => __( 'QA', 'development-mode-indicator' ),
			'maintenance' => __( 'Maintenance', 'development-mode-indicator' ),
		);

		return 'custom' === $settings['mode'] ? ( $settings['customMode'] ?: __( 'Custom', 'development-mode-indicator' ) ) : $labels[ $settings['mode'] ];
	}
}