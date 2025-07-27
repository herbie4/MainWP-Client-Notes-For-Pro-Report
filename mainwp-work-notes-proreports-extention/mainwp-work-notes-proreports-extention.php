<?php
/**
 * Plugin Name:       MainWP Client Notes Pro Report Extension
 * Description:       This adds client notes to your pro report
 * Tested up to:      6.8.2
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Version:           1.2.5-beta
 * Author:            reallyusefulplugins.com
 * Author URI:        https://reallyusefulplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mainwp-client-notes-pro-reports-extention
 * Website:           https://reallyusefulplugins.com
 */

include_once 'mainwp-work-notes.php';
class MainWP_Client_Notes_Proreport_Extension {



	public function __construct() {
		//add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );
        add_action( 'admin_init', array( &$this, 'admin_init' ) );
				// load textdomain
				add_action( 'plugins_loaded', array( &$this,'load_textdomain'));
	}

	// Load plugin textdomain
	// ---------------
	public function load_textdomain() {
		load_plugin_textdomain( 'mainwp-client-notes-pro-reports-extention', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	// AJAX handler to load the Work Notes form in the sitetab
public function load_work_notes_form() {
    $site_id = intval($_POST['site_id']);

    // Fetch existing notes
    $notes_key = 'mainwp_work_notes_' . $site_id;
    $notes = get_option($notes_key, array());

    // Render the form and notes inside the sitetab
    ?>
    <div class="ui form">
        <h3 class="ui dividing header">Work Notes</h3>
        <form id="work-note-form">
            <input type="hidden" name="site_id" value="<?php echo esc_attr($site_id); ?>">
            <label for="work_notes_date">Work Date:</label>
            <input type="date" name="work_notes_date" value="" required>
            <label for="work_notes_content">Work Content:</label>
            <textarea name="work_notes_content" required></textarea>
            <button type="submit" class="ui button green">Save Note</button>
        </form>

        <h3>Existing Work Notes</h3>
        <table class="ui celled table">
            <thead><tr><th>Date</th><th>Content</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (!empty($notes)) : ?>
                    <?php foreach ($notes as $index => $note) : ?>
                        <tr>
                            <td><?php echo esc_html($note['date']); ?></td>
                            <td><?php echo esc_html($note['content']); ?></td>
                            <td>
                                <a href="#" class="edit-note" data-noteid="<?php echo esc_attr($index); ?>">Edit</a>
                                <a href="#" class="delete-note" data-noteid="<?php echo esc_attr($index); ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="3">No work notes found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    wp_die();  // End AJAX request
}



    public function admin_init() {
    	register_setting('mainwp_client_notes_proreport_options_group', 'mainwp_client_notes_proreport_allow_prerelease');
	}


	public function managesites_subpage( $subPage ) {
    $subPage[] = array(
        'title'       => 'Work Notes',
        'slug'        => 'ClientNotesProReport',
        'sitetab'     => true, // Correctly tells MainWP to load it in the sitetab
        'menu_hidden' => true, // Hides it from the regular main menu
        'callback'    => array( static::class, 'renderPage' ),
    );
    return $subPage;
}

	/*
	* Create your extension page
	*/
	public static function renderPage() {
    ?>
    <div class="ui segment">
        <div class="inside">
            <form method="post" action="options.php">
                <?php
                settings_fields('mainwp_client_notes_proreport_options_group');
                ?>
                <h3><?php _e('Update Preferences', 'mainwp-client-notes-pro-reports-extention'); ?></h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Enable Pre-Releases', 'mainwp-client-notes-pro-reports-extention'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mainwp_client_notes_proreport_allow_prerelease" value="yes" <?php checked(get_option('mainwp_client_notes_proreport_allow_prerelease'), 'yes'); ?> />
                                <?php _e('Allow updates from pre-release versions', 'mainwp-client-notes-pro-reports-extention'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
    <?php
}


    /**
	 * Method settings().
	 */
	public function render_site_page_settings() {
		do_action( 'mainwp_pageheader_sites', 'ClientNotesProReport' );
		$this->render_site_tasks_tabs();
		do_action( 'mainwp_pagefooter_sites', 'ClientNotesProReport' );
	}


}




/*
* Activator Class is used for extension activation and deactivation
*/

class MainWP_Client_Pro_Report_Notes_Activator {

	protected $mainwpClientNotesProReportActivated = false;
	protected $childEnabled                  = false;
	protected $childKey                      = false;
	protected $childFile;
	protected $plugin_handle = 'mainwp-work-notes-proreports-extention';


	public function __construct() {
		$this->childFile = __FILE__;
		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );

		// This filter will return true if the main plugin is activated
		$this->mainwpClientNotesProReportActivated = apply_filters( 'mainwp_activated_check', false );

		if ( $this->mainwpClientNotesProReportActivated !== false ) {
			$this->activate_this_plugin();
		} else {
			// Because sometimes our main plugin is activated after the extension plugin is activated we also have a second step,
			// listening to the 'mainwp_activated' action. This action is triggered by MainWP after initialisation.
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
	}

	function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin'   => __FILE__,
			'api'      => $this->plugin_handle,
			'mainwp'   => true,
			'callback' => array( &$this, 'settings' ),
			'sitetab'  => true,  // This makes sure it renders in the right-hand side
		);
		return $pArray;
	}

	function settings() {
		// The "mainwp_pageheader_extensions" action is used to render the tabs on the Extensions screen.
		// It's used together with mainwp_pagefooter_extensions and mainwp_getextensions
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		if ( $this->childEnabled ) {
			MainWP_Client_Notes_Proreport_Extension::renderPage();
		} else {
			?>
			<div class="mainwp_info-box-yellow"><?php _e( 'The Extension has to be enabled to change the settings.' ); ?></div>
															<?php
		}
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	// The function "activate_this_plugin" is called when the main is initialized.
	function activate_this_plugin() {
		// Checking if the MainWP plugin is enabled. This filter will return true if the main plugin is activated.
		$this->mainwpClientNotesProReportActivated = apply_filters( 'mainwp_activated_check', $this->mainwpClientNotesProReportActivated );

		// The 'mainwp_extension_enabled_check' hook. If the plugin is not enabled this will return false,
		// if the plugin is enabled, an array will be returned containing a key.
		// This key is used for some data requests to our main
		$this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );

		$this->childKey = $this->childEnabled['key'];

		new MainWP_Client_Notes_Proreport_Extension();
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpClientNotesProReportActivated == false ) {
			echo '<div class="error"><p>MainWP Client Notes Extension ' . __( 'requires ' ) . '<a href="http://mainwp.com/" target="_blank">MainWP</a>' . __( ' Plugin to be activated in order to work. Please install and activate' ) . '<a href="http://mainwp.com/" target="_blank">MainWP</a> ' . __( 'first.' ) . '</p></div>';
		}
	}

	public function getChildKey() {
		return $this->childKey;
	}

	public function getChildFile() {
		return $this->childFile;
	}
}

global $mainwpclientnotesproreportExtensionActivator;
$mainwpclientnotesproreportExtensionActivator = new MainWP_Client_Pro_Report_Notes_Activator();

// Define plugin constants
define('RUP_MAINWP_CLIENT_NOTES_VERSION', '1.2.5-beta');

// ──────────────────────────────────────────────────────────────────────────
//  Updater bootstrap (plugins_loaded priority 1):
// ──────────────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function() {
    // 1) Load our universal drop-in. Because that file begins with "namespace UUPD\V1;",
    //    both the class and the helper live under UUPD\V1.
    require_once __DIR__ . '/inc/updater.php';

    // 2) Build a single $updater_config array:
    $updater_config = [
        'plugin_file' => plugin_basename( __FILE__ ),             // e.g. "simply-static-export-notify/simply-static-export-notify.php"
        'slug'        => 'mainwp-client-notes-pro-reports-extention',           // must match your updater‐server slug
        'name'        => 'MainWP Client Notes Pro Report Extension',         // human‐readable plugin name
        'version'     => RUP_MAINWP_CLIENT_NOTES_VERSION, // same as the VERSION constant above
        'key'         => '',                 // your secret key for private updater
        'server'      => 'https://raw.githubusercontent.com/stingray82/MainWP-Client-Notes-For-Pro-Report/main/uupd/index.json',
    ];

    // 3) Call the helper in the UUPD\V1 namespace:
    \RUP\Updater\Updater_V1::register( $updater_config );
}, 20 );

add_filter('uupd/allow_prerelease/mainwp-client-notes-pro-reports-extention', function ($allow) {
    return get_option('mainwp_client_notes_proreport_allow_prerelease') === 'yes';
}, 5);
