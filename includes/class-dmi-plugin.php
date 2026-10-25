<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMI_Plugin {
	public function run() {
		$admin = new DMI_Admin();
		$admin->register();

		$display_manager = new DMI_Display_Manager();
		$display_manager->register();
	}
}