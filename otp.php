<?php
/*
Plugin Name: One-Time Password
Plugin URI: http://blog.bokhorst.biz/2200/computers-en-internet/wordpress-plugin-one-time-password/
Description: One-Time Password System conforming to <a href="http://tools.ietf.org/html/rfc2289">RFC 2289</a> to protect your weblog in less trustworthy environments, like internet cafés.
Version: 1.3
Author: Marcel Bokhorst
Author URI: http://blog.bokhorst.biz/
*/

/*
	Copyright 2009  Marcel Bokhorst

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
	Acknowledgments

	PHP One-Time Passwords class by Tomas Mrozek
	See http://sourceforge.net/projects/php-otp/ for details
	This class is publised under the GNU Lesser General Public License version 3 or later

	jqPrint by tanathos
	See http://plugins.jquery.com/project/jqPrint
	This jQuery plugin is publised under the GNU General Public License and MIT License
*/

#error_reporting(E_ALL);

// Check PHP version
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
	die('One-Time Password requires at least PHP 5.0.0');
}

// Include OTP class
require_once('include/class.otp.php');

// Get challenge
if (isset($_GET['otp_action']) && $_GET['otp_action'] == 'challenge') {
	@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
	try {
		// Get data
		global $wpdb;
		$otp_user = $wpdb->escape(trim($_GET['otp_user']));
		$otp_table = $wpdb->prefix . 'otp';
		$otp_row = $wpdb->get_row("SELECT seed, algorithm, sequence FROM " . $otp_table . " WHERE user='" . $otp_user . "'");

		// Create challenge
		if ($otp_row != null && $otp_row->sequence >= 0) {
			$otp_class = new otp();
			echo $otp_class->createChallenge($otp_row->seed, $otp_row->sequence, $otp_row->algorithm);
		}
	}
	catch (Exception $e) {
		echo $e->getMessage();
	}
	exit;
}

// Get seed
if (isset($_GET['otp_action']) && $_GET['otp_action'] == 'seed') {
	@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
	$otp_class = new otp();
	echo $otp_class->generateSeed();
	exit;
}

// Handle plugin activation
if (!function_exists('otp_activate')) {
	function otp_activate() {
		global $wpdb;

		// Check if table exists
		$otp_table = $wpdb->prefix . 'otp';
		if ($wpdb->get_var("SHOW TABLES LIKE '" . $otp_table . "'") != $otp_table) {
			// Create table
			$sql = "CREATE TABLE " . $otp_table . " (
				user VARCHAR(60) NOT NULL,
				seed VARCHAR(60) NOT NULL,
				algorithm VARCHAR(60) NOT NULL,
				sequence INT(11) NOT NULL,
				hash VARCHAR(60) NOT NULL,
				generated DATETIME NOT NULL,
				last_login DATETIME NULL,
				UNIQUE KEY user (user)
			);";
			if ($wpdb->query($sql) === false)
				$wpdb->print_error();

			// Store database version
			update_option('otp_dbver', 1);
		}

		// Update table definition
		else if (get_option('otp_dbver') < 1) {
			$sql = "ALTER TABLE " . $otp_table . " CHANGE COLUMN time generated DATETIME NOT NULL;";
			if ($wpdb->query($sql) === false)
				$wpdb->print_error();
			$sql = "ALTER TABLE " . $otp_table . " ADD COLUMN last_login DATETIME NULL;";
			if ($wpdb->query($sql) === false)
				$wpdb->print_error();
		}
	}
}

// Handle plugin deactivation
function otp_deactivate() {
	// Cleanup if requested
	if (get_option('otp_cleanup')) {
		global $wpdb;

		// Check if table exists
		$otp_table = $wpdb->prefix . 'otp';
		if ($wpdb->get_var("SHOW TABLES LIKE '" . $otp_table . "'") == $otp_table) {
			// Delete table
			$sql = "DROP TABLE " . $otp_table . ";";
			if ($wpdb->query($sql) === false)
				$wpdb->print_error();
		}

		// Delete options
		delete_option('otp_dbver');
		delete_option('otp_cleanup');
	}
}

// Handle initialize
function otp_init() {
	// Only load styles and scripts when necessary
	if (is_admin() || strpos($_SERVER['REQUEST_URI'], 'wp-login')) {
		// I18n
		$plugin_dir = basename(dirname(__FILE__));
		load_plugin_textdomain('one-time-password', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );

		// Enqueue style sheet
		if (function_exists('wp_register_style')) {
			$plugin_url = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));
			wp_register_style('otp_style', $plugin_url . '/otp.css');
			if (function_exists('wp_enqueue_style'))
				wp_enqueue_style('otp_style');
		}

		// Enqueue scripts
		if (function_exists('wp_enqueue_script')) {
			wp_enqueue_script('jquery');
			if (is_admin())
				wp_enqueue_script('printElement', '/' . PLUGINDIR . '/one-time-password/js/jquery.jqprint.js');
		}
	}
}

// Modify login head
function otp_login_head() {
	// Print styles and scripts not called on login page
	if (function_exists('wp_print_styles'))
		wp_print_styles();
	if (function_exists('wp_print_scripts'))
		wp_print_scripts();
}

// Modify login form
function otp_login_form() {
?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Create element for challenge
			$('#user_pass').parent().parent().after($('<p id="otp_challenge">'));

			// Hide challenge when username changes
			$('#user_login').keyup(function() {
				$('#otp_challenge').hide();
			});

			// Show challenge when password gets focus
			$('#user_pass').focus(function() {
				otp_challenge = $('#otp_challenge');
				otp_challenge.text('<?php _e('Wait', 'one-time-password'); ?>');
				otp_challenge.show();
				$.ajax({
					url: '',
					type: 'GET',
					data: {otp_action: 'challenge', otp_user: $('#user_login').val()},
					dataType: 'text',
					cache: false,
					success: function(result) {
						if (result == '')
							otp_challenge.hide();
						else {
							parts = result.split(' ');
							parts[1] = '<span id="otp_sequence">' + parts[1] + '</span>';
							otp_challenge.html(parts.join(' '));
						}
					},
					error: function(x, stat, e) {
						otp_challenge.text('Error ' + x.status);
					}
				});
			});
		});
	</script>
<?php
}

// Authenticate using OTP
function otp_authenticate($user) {
	try {
		// Get data
		global $wpdb;
		$otp_user = $wpdb->escape(trim($_POST['log']));
		$otp_table = $wpdb->prefix . 'otp';
		$otp_row = $wpdb->get_row('SELECT seed, algorithm, sequence, hash FROM ' . $otp_table . " WHERE user='" . $otp_user . "'");

		// Check data
		if ($otp_row != null && $otp_row->sequence >= 0) {
			// Check password
			$otp_class = new otp();
			$otp_data = $otp_class->initializeOtp($otp_row->hash, $otp_row->seed, $otp_row->sequence, $otp_row->algorithm);
			$otp_auth = $otp_class->authAgainstHexOtp($_POST['pwd'], $otp_data['previous_hex_otp'], 'previous', $otp_row->sequence, $otp_row->algorithm);
			if ($otp_auth['result']) {
				// Update data
				$next_seq = $otp_row->sequence - 1;
				$query = "UPDATE " . $otp_table . " SET sequence=" . $next_seq . ", hash='" . $otp_auth['otp']['previous_hex_otp'] . "', last_login=now();";
				if ($wpdb->query($query) === false)
					$wpdb->print_error();

				// Athenticate user
				return new WP_User($otp_user);
			}
		}

		// Fallback to other handlers
		return null;
	}

	// Fail-safe
	catch (Exception $e) {
		return null;
	}
}

// Register options page
function otp_admin_menu() {
	if (function_exists('add_options_page'))
		add_options_page(__('One-Time Password Administration', 'one-time-password'), __('One-Time Password', 'one-time-password'), 0, __FILE__, 'otp_administration');
}

// Handle option page
function otp_administration() {
	// Check minimal wordpress version
	global $wp_version;
	if (version_compare($wp_version, '2.8') < 0) {
		echo '<span class="otp_message">'. __('This plugin requires at least WordPress 2.8', 'one-time-password') . '</span><br />';
		return;
	}

	// Reference database
	global $wpdb;
	$otp_table = $wpdb->prefix . 'otp';

	// Instantiate OTP
	$otp_class = new otp();

	// Get current user
	global $current_user;
	get_currentuserinfo();
	$otp_user = $wpdb->escape($current_user->user_login);

	// Output header
	echo '<div class="wrap">';
	echo '<h2>' . __('One-Time Password Administration', 'one-time-password') . '</h2>';

	// Render generate form
?>
	<h3><?php _e('Generate One-Time Password list', 'one-time-password') ?></h3>
	<form method="post" action="<?php echo remove_query_arg('updated', add_query_arg('otp_action', 'generate')); ?>">

	<?php wp_nonce_field('otp-generate'); ?>

	<table class="form-table">

	<tr><th scope="row"><?php _e('Pass-phrase:', 'one-time-password') ?></th>
	<td><input type="password" name="otp_pwd" />
	<span class="otp_hint"><?php _e('At least 10 characters', 'one-time-password') ?></span></td></tr>

	<tr><th scope="row"><?php _e('Confirm pass-phrase:', 'one-time-password') ?></th>
	<td><input type="password" name="otp_pwd_verify" /></td></tr>

	<tr><th scope="row"><?php _e('Seed:', 'one-time-password') ?></th>
	<td><input type="text" name="otp_seed" id="otp_seed" value="<?php echo $otp_class->generateSeed(); ?>" />
	<span class="otp_hint"><?php _e('Only alphanumeric characters', 'one-time-password') ?></span></td></tr>

	<tr><th scope="row" />
	<td><a id="otp_reseed" href="#"><?php _e('New', 'one-time-password') ?></a></td></tr>

	<tr><th scope="row"><?php _e('Algorithm:', 'one-time-password') ?></th>
	<td><select name="otp_algorithm">
<?php
		// List available algorithms
		$aa = $otp_class->getAvailableAlgorithms();
		for ($i = 0; $i < count($aa); $i++) {
			$sel = '';
			// Select md5 by default
			if ($aa[$i] == 'md5')
				$sel = ' selected="selected"';
			echo '<option value="' . $aa[$i] . '"' . $sel . '>' . $aa[$i] .'</option>';
		}
?>
	</select></td></tr>

	<tr><th scope="row"><?php _e('Count:', 'one-time-password') ?></th>
	<td><input type="text" name="otp_count" value="50" id="otp_count" /></td></tr>

	</table>

	<p><em><?php _e('The current One-Time Password list will be revoked automatically', 'one-time-password') ?></em></p>
	<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Generate', 'one-time-password') ?>" /></p>
	</form>
<?php
	
	// Handle generate action
	if ($_REQUEST['otp_action'] == 'generate') {
		// Security check
		check_admin_referer('otp-generate');

		// Get parameters
		$otp_pwd = $_POST['otp_pwd'];
		$otp_verify = $_POST['otp_pwd_verify'];
		$otp_count = intval($_POST['otp_count']);
		$otp_seed = $_POST['otp_seed'];
		$otp_algorithm = $_POST['otp_algorithm'];

		// Check pass-phrase
		$param_ok = true;
		if (!$otp_class->isValidPassPhrase($otp_pwd) || $otp_pwd != $wpdb->escape($otp_pwd)) {
			$param_ok = false;
			echo '<span class="otp_message">' . __('Invalid pass-phrase', 'one-time-password') . '</span><br />';
		}
		// Verify pass-phrase
		if ($otp_pwd != $otp_verify) {
				$param_ok = false;
				echo '<span class="otp_message">' . __('Pass-phrases do not match', 'one-time-password') . '</span><br />';
		}
		// Check seed
		if (!$otp_class->isValidSeed($otp_seed) || $otp_seed != $wpdb->escape($otp_seed)) {
			$param_ok = false;
			echo '<span class="otp_message">' . __('Invalid seed', 'one-time-password') . '</span><br />';
		}
		// Check algorithm
		if (!in_array($otp_algorithm, $otp_class->getAvailableAlgorithms())) {
			$param_ok = false;
			echo '<span class="otp_message">' . __('Invalid algorithm', 'one-time-password') . '</span><br />';
		}
		// Check count
		if ($otp_count <= 0 || $otp_count > 1000) {
			$param_ok = false;
			echo '<span class="otp_message">' . __('Invalid count', 'one-time-password') . '</span><br />';
		}

		if ($param_ok) {
			// Generate password list
			$otp_list = $otp_class->generateOtpList($otp_pwd, $otp_seed, null, $otp_count + 1, $otp_algorithm);

			// Delete old data
			$sql = "DELETE FROM " . $otp_table . " WHERE user='" . $otp_user . "'";
			if ($wpdb->query($sql) === false)
				$wpdb->print_error();

			// Store new data
			$sql = "INSERT INTO " . $otp_table .
				" (user, seed, algorithm, sequence, hash, generated) VALUES (" .
				"'" . $otp_user . "', " .
				"'" . $otp_seed . "', " .
				"'" . $otp_algorithm . "', " .
				$otp_list[1]['sequence'] . ", " .
				"'" . $otp_list[0]['hex_otp'] . "',  " .
				"now());";
			if ($wpdb->query($sql) === false)
				$wpdb->print_error();

			// Get generation time
			$otp_time = $wpdb->get_var("SELECT generated FROM " . $otp_table . " WHERE user='" . $otp_user . "'");			

			// Render password list / print form
?>
			<h3><?php _e('One-Time Password list', 'one-time-password') ?></h3>
			<form id="otp_form_print" method="post" action="#">
			<div id="otp_table">

			<table class="otp_legend">
			<tr><th scope="row"><?php _e('User:', 'one-time-password') ?></th><td><?php echo $otp_user; ?></td></tr>
			<tr><th scope="row"><?php _e('Seed:', 'one-time-password') ?></th><td><?php echo $otp_seed; ?></td></tr>
			<tr><th scope="row"><?php _e('Algorithm:', 'one-time-password') ?></th><td><?php echo $otp_algorithm; ?></td></tr>
			<tr><th scope="row"><?php _e('Generated:', 'one-time-password') ?></th><td><?php echo $otp_time; ?></td></tr>
			</table>
			
			<table id="otp_list">
			<th><?php _e('Seq', 'one-time-password') ?></th>
			<th><?php _e('Hex', 'one-time-password') ?></th>
			<th><?php _e('Words', 'one-time-password') ?></th>
			<th><?php _e('Seq', 'one-time-password') ?></th>
			<th><?php _e('Hex', 'one-time-password') ?></th>
			<th><?php _e('Words', 'one-time-password') ?></th>
<?php
			// Print passwords
			for ($i = 1; $i <= $otp_count / 2; $i++) {
				echo '<tr><td>' . $otp_list[$i]['sequence'] . '</td>';
				echo '<td>' . $otp_list[$i]['hex_otp'] . '</td>';
				echo '<td>' . $otp_list[$i]['words_otp'] . '</td>';

				echo '<td>' . $otp_list[$i + $otp_count / 2]['sequence'] . '</td>';
				echo '<td>' . $otp_list[$i + $otp_count / 2]['hex_otp'] . '</td>';
				echo '<td>' . $otp_list[$i + $otp_count / 2]['words_otp'] . '</td></tr>';
			}
?>
			</table>

			</div>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Print', 'one-time-password') ?>" /></p>
			</form>
<?php
		}
	}

	// Handle revoke action
	if ($_REQUEST['otp_action'] == 'revoke') {
		// Security check
		check_admin_referer('otp-revoke');

		// Get parameters
		$otp_revoke = $_POST['otp_revoke'];

		// Revoke
		if ($otp_revoke) {
			// Delete data
			$sql = "DELETE FROM " . $otp_table . " WHERE user='" . $otp_user . "'";
			if ($wpdb->query($sql) === false)
				$wpdb->print_error();
		}
	}

	// Check for existing data
	$otp_row = $wpdb->get_row("SELECT seed, algorithm, sequence, generated, last_login FROM " . $otp_table . " WHERE user='" . $otp_user . "'");
	if ($otp_row != null) {
		// Render revoke form
?>
		<h3><?php _e('Revoke One-Time Password list', 'one-time-password') ?></h3>
		<form method="post" action="<?php echo remove_query_arg('updated', add_query_arg('otp_action', 'revoke')); ?>">

		<?php wp_nonce_field('otp-revoke'); ?>

		<table class="form-table">

		<tr><th scope="row"><?php _e('Seed:', 'one-time-password') ?></th>
		<td><?php echo $otp_row->seed; ?></td></tr>

		<tr><th scope="row"><?php _e('Algorithm:', 'one-time-password') ?></th>
		<td><?php echo $otp_row->algorithm; ?></td></tr>

		<tr><th scope="row"><?php _e('Sequence:', 'one-time-password') ?></th>
		<td><?php echo $otp_row->sequence; ?></td></tr>

		<tr><th scope="row"><?php _e('Generated:', 'one-time-password') ?></th>
		<td><?php echo $otp_row->generated; ?></td></tr>

		<tr><th scope="row"><?php _e('Last login:', 'one-time-password') ?></th>
		<td><?php echo $otp_row->last_login; ?></td></tr>

		<tr><th scope="row"><?php _e('I am sure:', 'one-time-password') ?></th>
		<td><input type="checkbox" name="otp_revoke" /></td></tr>

		</table>

		<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Revoke', 'one-time-password') ?>" /></p>
		</form>
<?php
	}

	// Check revoke
	if ($_REQUEST['otp_action'] == 'revoke')
		if (!$_POST['otp_revoke'])
			echo '<span class="otp_message">' . __('Select "I am sure" to revoke', 'one-time-password') . '</span><br />';

	// Render settings form
	if (current_user_can('manage_options')) {
		$otp_cleanup = get_option('otp_cleanup') ? "checked" : "unchecked";
?>
		<h3><?php _e('Settings One-Time Password', 'one-time-password') ?></h3>
		<form method="post" action="<?php echo add_query_arg('otp_action', 'settings', admin_url('options.php')); ?>">
		<?php wp_nonce_field('update-options', '_wpnonce', false); ?>
		<input type="hidden" name="_wp_http_referer" value="/wp-admin/options-general.php?page=one-time-password%2Fotp.php&amp;otp_action=settings" />

		<table class="form-table">

		<tr><th scope="row"><?php _e('Delete data on deactivation:', 'one-time-password') ?></th>
		<td><input type="checkbox" name="otp_cleanup" <?php echo $otp_cleanup; ?> /></td></tr>
		 
		</table>

		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="otp_cleanup" />

		<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save', 'one-time-password') ?>" /></p>
		</form>
<?php
	}

	// Output footer
	echo '</div>';

	// Output admin jQuery
	$plugin_url = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));
?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#otp_form_print').submit(function() {
				$('#otp_table').jqprint();
				return false;
			});
			$('#otp_reseed').click(function() {
				$.ajax({
					url: '<?php echo $plugin_url . '/opt.php';  ?>',
					type: 'GET',
					data: {otp_action: 'seed'},
					dataType: 'text',
					cache: false,
					success: function(result) {
						$('[name=otp_seed]').val(result);
					},
					error: function(x, stat, e) {
						$('[name=otp_seed]').val('Error ' + x.status);
					}
				});
				return false;
			});
			$('[name=otp_pwd]').focus();
		});
	</script>
<?php
}

function otp_admin_notices() {
	global $wpdb;
	global $current_user;

	// Do not display on otp admin page
	if (strpos($_SERVER['REQUEST_URI'], 'one-time-password') === false) {
		// Get current user
		get_currentuserinfo();
		$otp_user = $wpdb->escape($current_user->user_login);

		// Get current sequence
		$otp_table = $wpdb->prefix . 'otp';
		$otp_row = $wpdb->get_row("SELECT sequence FROM " . $otp_table . " WHERE user='" . $otp_user . "'");

		// Check password list
		if ($otp_row == null || $otp_row->sequence <= 0) {
			// Render notice
			$url = admin_url('options-general.php?page=one-time-password/otp.php');
			echo '<div id="otp_notice" class="error fade"><p><strong>' . __('One-Time Password list', 'one-time-password');
			echo ' <a href="' . $url . '">' . __('should be generated', 'one-time-password') . '</a></strong></p></div>';
		}
	}
}

// Register (de)activation hook
if (function_exists('register_activation_hook'))
	register_activation_hook(__FILE__, 'otp_activate');
if (function_exists('register_deactivation_hook'))
	register_deactivation_hook(__FILE__, 'otp_deactivate');

// Register actions
if (function_exists('add_action')) {
	add_action('init', 'otp_init');
	add_action('login_head', 'otp_login_head');
	add_action('login_form', 'otp_login_form');
	if (is_admin()) {
		add_action('admin_menu', 'otp_admin_menu');
		add_action('admin_notices', 'otp_admin_notices');
	}
}

// Register filters
if (function_exists('add_filter')) {
	add_filter('authenticate', 'otp_authenticate', 10);
	// 20 wp_authenticate_username_password
	// 30 wp_authenticate_cookie
}

// That's it!

?>
