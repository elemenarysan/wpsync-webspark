<?php

/**
 * Plugin Name: wpsync-webspark
 * Description: Загрузка товаров
 * Plugin URI:  https://github.com/elemenarysan/wpsync-webspark
 * Author URI:  https://github.com/elemenarysan/wpsync-webspark
 * Author:      Александр
 * Version:     1.0
 *
 * Text Domain: ru_RU
 * Domain Path: .
 * Requires at least: 5.6
 * Requires PHP: 7.2
 */


register_activation_hook( __FILE__, array( 'WpsyncWebspark', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'WpsyncWebspark', 'plugin_deactivation' ) );
register_uninstall_hook( __FILE__, array( 'WpsyncWebspark', 'plugin_uninstall' ) );

define( 'WpsyncWebspark__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
require_once( WpsyncWebspark__PLUGIN_DIR . 'WpsyncWebspark.php' );


WpsyncWebspark::$hookBeginName = get_plugin_page_hookname('WpsyncWebsparkBeginImport', 'woocommerce' );

	if ( ! empty( WpsyncWebspark::$hookBeginName ) ) {
		add_action( WpsyncWebspark::$hookBeginName, array( 'WpsyncWebspark', 'beginImport' ) );
	}

add_action('admin_menu', 'register_my_custom_submenu_page');
function register_my_custom_submenu_page() {
    WpsyncWebspark::addMenu();
}

include_once(ABSPATH . 'wp-includes/pluggable.php');
if( current_user_can('manage_options')){
    add_action( 'admin_post_import-start', array( 'WpsyncWebspark', 'importStartAction' ) );
    add_action( 'admin_post_import-stop', array( 'WpsyncWebspark', 'importStopAction' ) );
    add_action( 'admin_post_import-log', array( 'WpsyncWebspark', 'importLogAction' ) );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

    class ProductsPluginWPCLI {

        public function __construct() {

        }

        public function importStart() {
                WP_CLI::success( 'Begining Import');
                WpsyncWebspark::beginImport();
                WP_CLI::success( 'Import Complete');
        }

        public function importStop() {
                WP_CLI::success( 'Stoping Import');
                WpsyncWebspark::stopImport();
        }

        public function importCheck() {
            $pid = WpsyncWebspark::pidFileCheck();
            if($pid){
                WP_CLI::success('Процесс запущен с pid '.$pid);
            } else {
                WP_CLI::success('Процесс не запущен');
            }
        }
    }

    WP_CLI::add_command( 'products', 'ProductsPluginWPCLI' );
}
