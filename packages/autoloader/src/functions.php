<?php
/* HEADER */ // phpcs:ignore

/**
 * Used for autoloading jetpack packages.
 *
 * @param string $class_name Class Name to load.
 *
 * @return Boolean Whether the class_name was found in the classmap.
 */
function autoloader( $class_name ) {
	global $jetpack_autoloader_loader;
	if ( ! isset( $jetpack_autoloader_loader ) ) {
		return false;
	}

	$file = $jetpack_autoloader_loader->find_class_file( $class_name );
	if ( ! isset( $file ) ) {
		return false;
	}

	require_once $file;
	return true;
}

/**
 * Finds the latest installed autoloader. If this is the latest autoloader, sets
 * up the classmap and filemap.
 */
function set_up_autoloader() {
	global $jetpack_autoloader_latest_version;
	global $jetpack_autoloader_loader;

	require_once __DIR__ . '/class-plugins-handler.php';
	require_once __DIR__ . '/class-version-selector.php';
	require_once __DIR__ . '/class-autoloader-locator.php';
	require_once __DIR__ . '/class-autoloader-handler.php';

	$plugins_handler    = new Plugins_Handler();
	$version_selector   = new Version_Selector();
	$autoloader_handler = new Autoloader_Handler(
		$plugins_handler->get_current_plugin_path(),
		$plugins_handler->get_all_active_plugins_paths(),
		new Autoloader_Locator( $version_selector ),
		$version_selector
	);

	if ( $autoloader_handler->should_autoloader_reset() ) {
		/*
		 * The autoloader must be reset when an activating plugin that was
		 * previously unknown is detected.
		 */
		$jetpack_autoloader_latest_version = null;
		$jetpack_autoloader_loader         = null;
	}

	if ( ! $autoloader_handler->is_latest_autoloader() || isset( $jetpack_autoloader_loader ) ) {
		return;
	}

	require_once __DIR__ . '/class-manifest-handler.php';
	require_once __DIR__ . '/class-version-loader.php';

	$jetpack_autoloader_loader = $autoloader_handler->build_autoloader();
	$autoloader_handler->update_autoloader_chain();
	add_filter( 'upgrader_post_install', __NAMESPACE__ . '\reset_maps_after_update', 0, 3 );

	// Now that the autoloader is ready we can load the files in the filemap safely.
	$jetpack_autoloader_loader->load_filemap();
}

/**
 * Resets the autoloader after a plugin update.
 *
 * @param bool  $response   Installation response.
 * @param array $hook_extra Extra arguments passed to hooked filters.
 * @param array $result     Installation result data.
 *
 * @return bool The passed in $response param.
 */
function reset_maps_after_update( $response, $hook_extra, $result ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	global $jetpack_autoloader_loader;

	if ( isset( $hook_extra['plugin'] ) ) {
		/*
		 * $hook_extra['plugin'] is the path to the plugin file relative to the plugins directory:
		 * https://core.trac.wordpress.org/browser/tags/5.4/src/wp-admin/includes/class-wp-upgrader.php#L701
		 */
		$plugin = $hook_extra['plugin'];

		if ( false === strpos( $plugin, '/', 1 ) ) {
			// Single-file plugins don't use packages, so bail.
			return $response;
		}

		if ( ! is_plugin_active( $plugin ) ) {
			// The updated plugin isn't active, so bail.
			return $response;
		}

		/*
		 * $plugin is the path to the plugin file relative to the plugins directory.
		 */
		$plugin_dir  = str_replace( '\\', '/', WP_PLUGIN_DIR );
		$plugin_path = trailingslashit( $plugin_dir ) . trailingslashit( explode( '/', $plugin )[0] );

		if ( is_readable( $plugin_path . 'vendor/jetpack-autoloader/autoload_functions.php' ) ) {
			// The plugin has a >=v2.2 autoloader, so reset the classmap.
			$jetpack_autoloader_loader = array();

			set_up_autoloader();
		}
	}

	return $response;
}
