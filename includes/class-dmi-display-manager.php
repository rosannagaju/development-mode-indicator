<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMI_Display_Manager {
	public function register() {
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_overlay' ), 99 );
		add_action( 'admin_footer', array( $this, 'render_overlay' ), 99 );
	}

	public function admin_bar( $admin_bar ) {
		$settings = DMI_Settings::get();
		$show_admin_bar = 'admin_bar' === $settings['displayType'] || ( is_admin() && 'watermark' === $settings['displayType'] && 'admin_bar' === $settings['watermark']['backendDisplay'] );
		if ( ! $settings['enabled'] || ! $show_admin_bar || ! is_admin_bar_showing() ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'dmi-admin-bar',
				'title' => sprintf(
					'<span class="dmi-admin-bar__icon dashicons dashicons-%1$s" aria-hidden="true"></span><span class="dmi-admin-bar__label">%2$s</span>',
					esc_attr( $settings['adminBar']['icon'] ),
					esc_html( DMI_Settings::mode_label( $settings ) )
				),
				'meta'  => array( 'title' => esc_attr( $settings['message'] ) ),
			)
		);
	}

	public function enqueue_assets() {
		$settings = DMI_Settings::get();
		if ( ! $settings['enabled'] ) {
			return;
		}

		wp_enqueue_style( 'dmi-runtime', DMI_URL . 'assets/runtime.css', array( 'dashicons' ), DMI_VERSION );
		wp_add_inline_style( 'dmi-runtime', $this->inline_css( $settings ) );
	}

	public function render_overlay() {
		$settings = DMI_Settings::get();
		if ( ! $settings['enabled'] || 'admin_bar' === $settings['displayType'] ) {
			return;
		}

		$label   = DMI_Settings::mode_label( $settings );
		$message = $settings['message'];
		if ( 'watermark' === $settings['displayType'] ) {
			if ( is_admin() && 'admin_bar' === $settings['watermark']['backendDisplay'] ) {
				return;
			}
			$watermark_style = sprintf(
				'--dmi-watermark-opacity:%1$s;--dmi-watermark-size:%2$spx;--dmi-watermark-rotation:%3$sdeg;',
				esc_attr( $settings['watermark']['opacity'] ),
				absint( $settings['watermark']['fontSize'] ),
				(int) $settings['watermark']['rotation']
			);
			echo '<div class="dmi-watermark" style="' . esc_attr( $watermark_style ) . '" role="status"><strong>' . esc_html( $label ) . '</strong><span>' . esc_html( $message ) . '</span></div>';
		} elseif ( 'border' === $settings['displayType'] || 'bottom_border' === $settings['displayType'] ) {
			echo '<div class="dmi-bottom-border dmi-bottom-border--' . esc_attr( $settings['border']['position'] ) . '" role="status" tabindex="0"><span class="dmi-bottom-border__message">' . esc_html( $message ) . '</span><span class="screen-reader-text">' . esc_html( $label . ': ' . $message ) . '</span></div>';
		}
	}

	private function inline_css( $settings ) {
		$watermark = $settings['watermark'];
		$border    = $settings['border'];
		$background = $watermark['background'] ? $watermark['background'] : 'transparent';
		$admin_bar_background = is_admin() && 'watermark' === $settings['displayType'] && 'admin_bar' === $watermark['backendDisplay'] ? $watermark['adminBarBackgroundColor'] : $settings['adminBar']['backgroundColor'];
		$admin_bar_text_color = is_admin() && 'watermark' === $settings['displayType'] && 'admin_bar' === $watermark['backendDisplay'] ? $watermark['adminBarTextColor'] : $settings['adminBar']['textColor'];

		return ':root{--dmi-admin-bg:' . esc_attr( $admin_bar_background ) . ';--dmi-admin-text:' . esc_attr( $admin_bar_text_color ) . ';--dmi-watermark-color:' . esc_attr( $watermark['textColor'] ) . ';--dmi-watermark-opacity:' . esc_attr( $watermark['opacity'] ) . ';--dmi-watermark-size:' . absint( $watermark['fontSize'] ) . 'px;--dmi-watermark-rotation:' . (int) $watermark['rotation'] . 'deg;--dmi-watermark-bg:' . esc_attr( $background ) . ';--dmi-border-primary:' . esc_attr( $border['primaryColor'] ) . ';--dmi-border-secondary:' . esc_attr( $border['secondaryColor'] ) . ';--dmi-border-height:' . absint( $border['height'] ) . 'px;--dmi-border-pattern:' . absint( $border['patternSize'] ) . 'px;}' . $this->position_css( $watermark['position'] );
	}

	private function position_css( $position ) {
		$positions = array(
			'center'       => 'top:50%;left:50%;transform:translate(-50%,-50%) rotate(var(--dmi-watermark-rotation));',
			'bottom-right' => 'right:24px;bottom:40px;transform:rotate(var(--dmi-watermark-rotation));',
			'bottom-left'  => 'left:24px;bottom:40px;transform:rotate(var(--dmi-watermark-rotation));',
			'top-right'    => 'right:24px;top:80px;transform:rotate(var(--dmi-watermark-rotation));',
		);

		return '.dmi-watermark{' . ( $positions[ $position ] ?? $positions['center'] ) . '}';
	}
}