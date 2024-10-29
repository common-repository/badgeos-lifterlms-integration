<?php
/**
 * Plugin Name: LifterLMS BadgeOS Integration
 * Plugin URI: https://wooninjas.com/
 * Description: The add-on integrates BadgeOS features with the Lifter LMS plugin. The add-on offers additional triggers for BadgeOS that are compatible with the Lifter LMS plugin.
 * Author: Wooninjas
 * Author URI: https://wooninjas.com/
 * Text Domain: lifter_lms_bos
 * Version: 1.1.0
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( !defined ( 'ABSPATH' ) ) exit;

register_activation_hook( __FILE__, ['LifterLMS_BadgeOS', 'activation' ] );
register_deactivation_hook( __FILE__, ['LifterLMS_BadgeOS', 'deactivation' ] );
define( 'LIFTERLMS_BOS_TEXT_DOMAIN', 'lifter_lms_bos' );

/**
 * Class LifterLMS_BadgeOS
 */
class LifterLMS_BadgeOS {
    const VERSION = '1.1.0';

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof LifterLMS_BadgeOS ) ) {
            self::$instance = new self;

            self::$instance->setup_constants();
            self::$instance->includes();
            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * Activation function hook
     *
     * @since 1.0
     * @return void
     */
    public static function activation() {

        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        update_option( 'lifterlms_bos_version', self::VERSION );
        $general_values = get_option( 'lifterlms_bos_options' );
        if( false == $general_values ) {

            $general_form_data = array();

            update_option('lifterlms_bos_options', $general_form_data);
        }
    }

    /**
     * Deactivation function hook
     *
     * @since 1.0
     * @return void
     */
    public static function deactivation() {
        delete_option( 'lifterlms_bos_options' );
    }

    /**
     * Upgrade function hook
     *
     * @since 1.0
     * @return void
     */
    public function upgrade() {
        if ( get_option ( 'lifterlms_bos_version' ) != self::VERSION ) {
        }
    }

    /**
     * Setup Constants
     */
    private function setup_constants() {

        // Directory
        define( 'LIFTERLMS_BOS_DIR', plugin_dir_path ( __FILE__ ) );
        define( 'LIFTERLMS_BOS_DIR_FILE', LIFTERLMS_BOS_DIR . basename ( __FILE__ ) );
        define( 'LIFTERLMS_BOS_INCLUDES_DIR', trailingslashit ( LIFTERLMS_BOS_DIR . 'includes' ) );
        define( 'LIFTERLMS_BOS_TEMPLATES_DIR', trailingslashit ( LIFTERLMS_BOS_DIR . 'templates' ) );
        define( 'LIFTERLMS_BOS_BASE_DIR', plugin_basename(__FILE__));

        // URLS
        define( 'LIFTERLMS_BOS_URL', trailingslashit ( plugins_url ( '', __FILE__ ) ) );
        define( 'LIFTERLMS_BOS_ASSETS_URL', trailingslashit ( LIFTERLMS_BOS_URL . 'assets' ) );
    }

    /**
     * Include Required Files
     */
    private function includes() {

        if( file_exists( LIFTERLMS_BOS_INCLUDES_DIR . 'integration/lifterlms-bos-integration.php' ) ) {
            require_once ( LIFTERLMS_BOS_INCLUDES_DIR . 'integration/lifterlms-bos-integration.php' );
        }

        if( file_exists( LIFTERLMS_BOS_INCLUDES_DIR . 'settings/options.php' ) ) {
            require_once ( LIFTERLMS_BOS_INCLUDES_DIR . 'settings/options.php' );
        }
    }

    private function hooks() {

        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_scripts' ], 11 );
        add_action( 'plugins_loaded', [ $this, 'upgrade' ] );

        add_filter( 'lifterlms_integrations', array( $this, 'register_integration' ), 10, 1 );
    }

    /**
     * Enqueue scripts on admin
     *
     * @param string $hook
     * @since 1.0
     */
    public function admin_enqueue_scripts( $hook ) {

        $screen = get_current_screen();

        // plugin's admin script
        wp_enqueue_script( 'llms-bos-admin-script', LIFTERLMS_BOS_ASSETS_URL . 'js/llms-bos-admin-script.js', [ 'jquery' ], self::VERSION, true );
        wp_enqueue_style( 'llms-bos-admin-style', LIFTERLMS_BOS_ASSETS_URL . 'css/llms-bos-admin-style.css', [ '' ], self::VERSION, null );
    }

    /**
	 * Register the integration with LifterLMS
     * 
	 * @param    array     $integrations
	 * @return   array
	 */
	public function register_integration( $integrations ) {
        $integrations[] = 'LifterLMS_BOS_Allow_Integration';
        
		return $integrations;
	}

    /**
     * Enqueue scripts on frontend
     *
     * @since 1.0
     */
    public function frontend_enqueue_scripts() {

        wp_enqueue_style( 'llms-bos-frontend-style', LIFTERLMS_BOS_ASSETS_URL . 'css/llms-bos-front-style.css' );

        wp_enqueue_script( 'llms-bos-front-script', LIFTERLMS_BOS_ASSETS_URL . 'js/llms-bos-front-script.js', array( 'jquery' ), self::VERSION, true );
    }
}

/**
 * Display admin notifications if dependency not found.
 */
function lifterlms_bos_ready() {
    if( !is_admin() ) {
        return;
    }

    if( !class_exists( 'BadgeOS' ) && !class_exists( 'LifterLMS' ) ) {
        deactivate_plugins ( plugin_basename ( __FILE__ ), true );
        $class = 'notice is-dismissible error';
        $message = __( 'LifterLMS BadgeOS add-on requires <a href="https://wordpress.org/plugins/lifterlms/" >LifterLMS</a> & <a href="https://wordpress.org/plugins/badgeos/" >BadgeOS</a>  plugins to be activated.', LIFTERLMS_BOS_TEXT_DOMAIN );
        printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
    } elseif ( !class_exists( 'BadgeOS' ) && class_exists( 'LifterLMS' ) ) {
        deactivate_plugins ( plugin_basename ( __FILE__ ), true );
        $class = 'notice is-dismissible error';
        $message = __( 'LifterLMS BadgeOS add-on requires <a href="https://wordpress.org/plugins/badgeos/" >BadgeOS</a> plugin to be activated.', LIFTERLMS_BOS_TEXT_DOMAIN );
        printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
    } elseif ( !class_exists( 'LifterLMS' ) && class_exists( 'BadgeOS' ) ) {
        deactivate_plugins ( plugin_basename ( __FILE__ ), true );
        $class = 'notice is-dismissible error';
        $message = __( 'LifterLMS BadgeOS add-on requires <a href="https://wordpress.org/plugins/lifterlms/" >LifterLMS</a> plugin to be activated.', LIFTERLMS_BOS_TEXT_DOMAIN );
        printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
    }
}

/**
 * @return LifterLMS_BadgeOS|bool
 */
function LifterLMS_BadgeOS() {
    if ( ! class_exists( 'BadgeOS' ) || ! class_exists( 'LifterLMS' ) ) {
        add_action( 'admin_notices', 'lifterlms_bos_ready' );
        return false;
    } else {
        return LifterLMS_BadgeOS::instance();
    }
}
add_action( 'plugins_loaded', 'LifterLMS_BadgeOS');
