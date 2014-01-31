<?php
/*
Plugin Name: Broken Site Checker
Plugin URI: http://maintainn.com
Description: Checks for and disables inaccessible sites in a multisite install
Author: Ryan Duff
Version: 1.0.0
License: GPL version 2 or any later version
Author URI: http://maintainn.com
Text Domain: maintainn-broken-site-checker
Domain Path: /lang
*/

add_action( 'plugins_loaded', array ( MaintainnBrokenSiteChecker::get_instance(), 'plugin_setup' ) );

class MaintainnBrokenSiteChecker {

	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;


	/**
	 * URL to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_url = '';


	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_path = '';


	/**
	 * Holds admin screen hook.
	 *
	 * @type string
	 */
	public $admin_screen_hook = '';


	/**
	 * Access this plugin’s working instance
	 *
	 * @since   1.0.0
	 * @return  object of this class
	 */
	public static function get_instance() {

		NULL === self::$instance and self::$instance = new self;

		return self::$instance;

	}


	/**
	 * Used for plugin setup and hooks
	 *
	 * @since   1.0.0
	 * @return  void
	 */
	public function plugin_setup() {

		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );
		$this->load_language( 'maintainn-broken-site-checker' );

		add_action( 'network_admin_menu', array( $this, 'broken_site_checker_menu' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		add_action( 'wp_ajax_maintainn_get_blog_ids', array( $this, 'get_blog_ids' ) );
		add_action( 'wp_ajax_maintainn_check_broken_site', array( $this, 'check_site' ) );

	}


	/**
	 * Constructor. Intentionally left empty and public.
	 *
	 * @see plugin_setup()
	 * @since 1.0.0
	 */
	public function __construct() {
	}


	/**
	 * Loads translation file.
	 *
	 * Accessible to other classes to load different language files (admin and
	 * front-end for example).
	 *
	 * @param   string $domain
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_language( $domain ) {

		load_plugin_textdomain( $domain, false, $this->plugin_path . 'languages' );

	}


	/**
	 * Add 'Broken Site Checker' menu page under Settings menu
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function broken_site_checker_menu() {

		$this->admin_screen_hook = add_submenu_page( 'sites.php', __( 'Broken Site Checker', 'maintainn-broken-site-checker' ), __( 'Broken Site Checker', 'maintainn-broken-site-checker' ), 'manage-sites', 'broken-site-checker', array( $this, 'broken_site_checker_page' ) );

	}


	/**
	 * Display Broken Site Checker admin page
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function broken_site_checker_page() {

		echo '<div class="wrap">';
			echo '<h2>' . __( 'Broken Site Checker', 'maintainn-broken-site-checker' ) . '</h2>';
			echo '<div>' . __( 'This process will check your multisite install for sites that are no longer accessible. If a site is found to be inaccessible it will be marked as archived.', 'maintainn-broken-site-checker' ) . '</div>';
			echo '<a id="broken_site_checker_submit" class="button" style="margin:20px auto;" />' . __( 'Check for Broken Sites!', 'maintainn-broken-site-checker' ) . '</a>';

			echo '<div class="sites-checked-header" style="display:none;">';
				echo '<h3>' . __( 'Sites Checked:', 'maintainn-broken-site-checker' ) . '</h3>';
				echo '<p>' . __( 'Please be patient, this may take a while depending on how many sites are in your multisite network.', 'maintainn-broken-site-checker' ) . '</p>';
				echo '<hr />';
			echo '</div><!-- /.sites-checked-header -->';

			echo '<div id="sites-checked">';
			echo '</div><!-- /#sites-checked -->';

			echo '<h3 id="sites-checked-finished" style="display:none;">' . __( 'Finished Checking Sites!', 'maintainn-broken-site-checker' ) . '</h3>';

		echo '</div><!-- /.wrap -->';

	}


	/**
	 * Registers admin scripts
	 *
	 * @since  1.0.0
	 * @param  string  $hook The page hook string we're loading
	 * @return void
	 */
	public function admin_scripts( $hook ) {

		// If we're not on our admin screen, don't load js
		if( $hook != $this->admin_screen_hook )
			return;

		wp_enqueue_script( 'maintainn-broken-site-checker', $this->plugin_url . 'js/broken-site-checker.js', array( 'jquery' ), '1.0.0', true );

		// Pass blog ids along
		$blogs = $this->get_blog_ids();
		wp_localize_script( 'maintainn-broken-site-checker', 'site_ids', $blogs );

	}


	/**
	 * Gets all active blog ids from multisite
	 *
	 * @since  1.0.0
	 * @return array  Returns an array of blog IDs or empty array if this isn't multisite
	 */
	public function get_blog_ids() {

		global $wpdb;

		$blogs = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' AND spam = '0' AND deleted = '0' AND archived = '0' ORDER BY registered ASC" );

		return $blogs;

	}


	/**
	 * Pings a URL to see if a site is alive and archives it if not
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function check_site() {

		$site_id = absint( $_REQUEST['site_id'] );

		// Exit if we don't have a $site_id
		if ( 0 === $site_id )
			return;

		// Switch to this blog so we can get the url
		switch_to_blog( $site_id );
		$siteurl = site_url();
		restore_current_blog();

		// Attempt to get a response from the URL
		$response = wp_remote_get( $siteurl, array( 'timeout' => 120, 'httpversion' => '1.1' ) );

		// Set our retult message to return
		$result = '<li>' . __( 'Site ID', 'maintainn-broken-site-checker' ) . ' ' . $site_id . ': ' . $siteurl . ' - <span style="color:green;">' . __( 'Good', 'maintainn-broken-site-checker' ) . '</span></li>';

		// If we get an error, the site is unavailable. Lets archive it.
		if ( is_wp_error( $response ) ) {

			// Change the archive status
			update_archived( $site_id, 1 );

			// Add site option so know it was archived automatically
			switch_to_blog( $site_id );
			update_option( 'broken_site_checker_auto_archived', 1 );
			restore_current_blog();

			// Update result message to indicate failure
			$result = '<li>Site ID ' . $site_id . ': ' . $siteurl . ' - <span style="color:red;">' . __( 'Could not reach site. Site archived.', 'maintainn-broken-site-checker' ) . '</span></li>';

		}

		// Send back json response
		echo json_encode( $result );

		// End here
		die();

	}


}
