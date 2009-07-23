<?php
/*
Plugin Name: One-Time Password
Plugin URI: http://blog.bokhorst.biz/2200/computers-en-internet/wordpress-plugin-one-time-password/
Description: One-Time Password System conforming to <a href="http://tools.ietf.org/html/rfc2289">RFC 2289</a> to protect your weblog in less trustworthy environments, like internet cafés.
Version: 0.1
Author: Marcel Bokhorst
Author URI: http://blog.bokhorst.biz/
*/

/*  Copyright 2009  Marcel Bokhorst

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
	PHP One-Time Passwords class by Tomas Mrozek
	See http://sourceforge.net/projects/php-otp/ for details
	This class is publised under the GNU Lesser General Public License version 3 or later

	jqPrint by tanathos
	See http://plugins.jquery.com/project/jqPrint
	This jQuery plugin is publised under the GNU General Public License and MIT License
*/

/*
	ToDo:
	- ajax new seed
	- error handling ajax get challenge
	- ajax error messages (validators)
*/

#error_reporting(E_ALL);

require_once('include/class.otp.php');

// Get challenge
if (isset($_GET['otp_user'])) {
	try {
		global $wpdb;
		$otp_user = $wpdb->escape($_GET['otp_user']);
		$otp_table = $wpdb->prefix . 'otp';
		$otp_row = $wpdb->get_row("SELECT seed, algorithm, sequence FROM " . $otp_table . " WHERE user='" . $otp_user . "'");
		if ($otp_row != null && $otp_row->sequence >= 0) {
			$otp_class = new otp();
			echo $otp_class->createChallenge($otp_row->seed, $otp_row->sequence, $otp_row->algorithm);
		}
		else
			echo '';
	}
	catch (Exception $e) {
		echo $e->getMessage();
	}
	exit;
}

// Handle plugin activation
if (!function_exists('otp_activate')) {
	function otp_activate()
	{
		// Create table
		global $wpdb;
		$otp_table = $wpdb->prefix . 'otp';
		if ($wpdb->get_var("SHOW TABLES LIKE '" . $otp_table . "'") != $otp_table) {
			$sql = "CREATE TABLE " . $otp_table . " (
				user VARCHAR(60) NOT NULL,
				seed VARCHAR(60) NOT NULL,
				algorithm VARCHAR(60) NOT NULL,
				sequence INT(11) NOT NULL,
				hash VARCHAR(60) NOT NULL,
				time DATETIME NOT NULL,
				UNIQUE KEY user (user)
			);";
			$wpdb->query($sql);
		}

		// Store database version
		update_option('otp_dbver', 0);
	}
}

// Handle initialize
function otp_init() {
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

// Modify login head
function otp_login_head() {
	if (function_exists('wp_print_styles'))
		wp_print_styles();
	if (function_exists('wp_print_scripts'))
		wp_print_scripts();
}

// Modify login form
function otp_login_form() {
	$plugin_url = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));
?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Create element for challenge
			$('#user_pass').parent().parent().after($('<p id="otp_challenge" class="otp_challenge">'));

			// Hide challenge when user changes
			$('#user_login').keyup(function() {
				$('#otp_challenge').hide();
			});

			// Show challenge when password gets focus
			$('#user_pass').focus(function() {
				otp_challenge = $('#otp_challenge');
				otp_challenge.text('<?php _e('Wait'); ?>');
				otp_challenge.show();
				$.ajax({
					url: '<?php echo $plugin_url . '/opt.php';  ?>',
					type: 'GET',
					data: 'otp_user=' + $('#user_login').val(),
					dataType: 'text',
					cache: false,
					success: function(result) {
						if (result == '')
							otp_challenge.hide();
						else
							otp_challenge.text(result);
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
function otp_authenticate($user)
{
	// Get data
	global $wpdb;
	$otp_user = $wpdb->escape($_POST['log']);
	$otp_table = $wpdb->prefix . 'otp';
	$otp_row = $wpdb->get_row('SELECT seed, algorithm, sequence, hash FROM ' . $otp_table . " WHERE user='" . $otp_user . "'");
	if ($otp_row != null) {
		// Check OTP
		$otp_class = new otp();
		$otp_data = $otp_class->initializeOtp($otp_row->hash, $otp_row->seed, $otp_row->sequence, $otp_row->algorithm);
		$otp_auth = $otp_class->authAgainstHexOtp($_POST['pwd'], $otp_data['previous_hex_otp'], 'previous', $otp_row->sequence, $otp_row->algorithm);
		if ($otp_auth['result']) {
			// Update data
			$wpdb->update(
				$otp_table,
				array('sequence' => $otp_row->sequence - 1, 'hash' => $otp_auth['otp']['previous_hex_otp']),
				array('user' => $otp_user),
				array('%d', '%s'),
				array('%d'));
			// Athenticate user
			return new WP_User($otp_user);
		}
	}

	// Fallback to other handlers
	return null;
}

// Register options page
function otp_admin_menu() {
	add_options_page(__('One-Time Password Administration'), __('One-Time Password'), 8, __FILE__, 'otp_options');
}

// Handle option page
function otp_options() {
	// Check wordpress version
	global $wp_version;
	if (version_compare($wp_version, '2.8') < 0) {
		echo '<span class="otp_message">'. __('This plugin requires at least WordPress 2.8') . '</span><br />';
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
	echo '<div class="wrap" id="content">';
	echo '<h2>' . __('One-Time Password Administration') . '</h2>';

	// Render generate form
?>
	<h3><?php _e('Generate one-time password list') ?></h3>
	<form method="post" action="<?php echo add_query_arg('action', 'generate'); ?>">

	<?php wp_nonce_field('otp-generate'); ?>

	<table class="form-table">

	<tr valign="top">
	<th scope="row"><?php _e('Pass-phrase: ') ?></th>
	<td><input type="password" name="otp_pwd" />
	<span class="otp_hint"><?php _e('At least 10 characters') ?></span></td>
	</tr>

	<tr valign="top">
	<th scope="row"><?php _e('Confirm pass-phrase: ') ?></th>
	<td><input type="password" name="otp_pwd_verify" /></td>
	</tr>

	<tr valign="top">
	<th scope="row"><?php _e('Seed: ') ?></th>
	<td><input type="text" name="otp_seed" value="<?php echo $otp_class->generateSeed(); ?>" />
	<span class="otp_hint"><?php _e('Only alphanumeric characters') ?></span></td>
	</tr>

	<tr valign="top">
	<th scope="row"><?php _e('Algorithm: ') ?></th>
	<td><select name="otp_algorithm">
<?php
		$aa = $otp_class->getAvailableAlgorithms();
		for ($i = 0; $i < count($aa); $i++) {
			$sel = '';
			if ($i + 1 == count($aa))
				$sel = ' selected="selected"';
			echo '<option value="' . $aa[$i] . '"' . $sel . '>' . $aa[$i] .'</option>';
		}
?>
	</select></td>
	</tr>

	</table>

	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Generate') ?>" />
	</p>

	</form>
<?php
	
	// Handle generate action
	if ($_REQUEST['action'] == 'generate') {
		// Security check
		check_admin_referer('otp-generate');

		// Get parameters
		$otp_pwd = $_POST['otp_pwd'];
		$otp_verify = $_POST['otp_pwd_verify'];
		$otp_count = 50;
		$otp_seed = $_POST['otp_seed'];
		$otp_algorithm = $_POST['otp_algorithm'];

		// Check parameters
		$param_ok = true;
		if (!$otp_class->isValidPassPhrase($otp_pwd) || $otp_pwd != $wpdb->escape($otp_pwd)) {
			$param_ok = false;
			echo '<span class="otp_message">' . __('Invalid pass-phrase') . '</span><br />';
		}
		if ($otp_pwd != $otp_verify) {
				$param_ok = false;
				echo '<span class="otp_message">' . __('Pass-phrases do not match') . '</span><br />';
		}
		if (!$otp_class->isValidSeed($otp_seed) || $otp_seed != $wpdb->escape($otp_seed)) {
			$param_ok = false;
			echo '<span class="otp_message">' . __('Invalid seed') . '</span><br />';
		}
		if (!in_array($otp_algorithm, $otp_class->getAvailableAlgorithms())) {
			$param_ok = false;
			echo '<span class="otp_message">' . __('Invalid algorithm') . '</span><br />';
		}

		if ($param_ok) {
			// Generate password list
			$otp_list = $otp_class->generateOtpList($otp_pwd, $otp_seed, null, $otp_count + 1, $otp_algorithm);

			// Delete old data
			$sql = "DELETE FROM " . $otp_table . " WHERE user='" . $otp_user . "'";
			$wpdb->query($sql);

			// Store new data
			$sql = "INSERT INTO " . $otp_table .
				" (user, seed, algorithm, sequence, hash, time) VALUES (" .
				"'" . $otp_user . "', " .
				"'" . $otp_seed . "', " .
				"'" . $otp_algorithm . "', " .
				$otp_list[1]['sequence'] . ", " .
				"'" . $otp_list[0]['hex_otp'] . "',  " .
				"now());";
			$wpdb->query($sql);

			// Render password list
			echo '<h3>' . __('One-time password list') . '</h3>';
			echo '<form id="otp_form_print" method="post" action="#">';
			echo '<div id="otp_table">';

			echo '<table class="otp_legend">';
			echo '<tr valign="top"><th scope="row">' . __('Seed: ') . '</th><td>' . $otp_seed . '</td></tr>';
			echo '<tr valign="top"><th scope="row">' . __('Algorithm: ') . '</th><td>' . $otp_algorithm . '</td></tr>';
			echo '</table>';
			
			echo '<table id="otp_list">';
			echo '<th>' . __('Seq') . '</th><th>' . __('Hex') . '</th><th>' . __('Words') . '</th>';
			echo '<th>' . __('Seq') . '</th><th>' . __('Hex') . '</th><th>' . __('Words') . '</th>';
			for ($i = 1; $i <= $otp_count / 2; $i++) {
				echo '<tr><td>' . $otp_list[$i]['sequence'] . '</td>';
				echo '<td>' . $otp_list[$i]['hex_otp'] . '</td>';
				echo '<td>' . $otp_list[$i]['words_otp'] . '</td>';

				echo '<td>' . $otp_list[$i + $otp_count / 2]['sequence'] . '</td>';
				echo '<td>' . $otp_list[$i + $otp_count / 2]['hex_otp'] . '</td>';
				echo '<td>' . $otp_list[$i + $otp_count / 2]['words_otp'] . '</td></tr>';
			}
			echo '</table>';

			echo '</div>';

			echo '<p class="submit">';
			echo '<input type="submit" class="button-primary" value="' . __('Print') .'" />';
			echo '</p>';
			echo '</form>';
		}
	}

	// Handle revoke action
	if ($_REQUEST['action'] == 'revoke') {
		// Security check
		check_admin_referer('otp-revoke');

		// Get parameters
		$otp_revoke = $_POST['otp_revoke'];

		// Revoke
		if ($otp_revoke) {
			// Delete old data
			$sql = "DELETE FROM " . $otp_table . " WHERE user='" . $otp_user . "'";
			$wpdb->query($sql);
		}
	}

	// Check for existing data
	$otp_row = $wpdb->get_row("SELECT seed, algorithm, sequence, hash FROM " . $otp_table . " WHERE user='" . $otp_user . "'");
	if ($otp_row != null) {
		// Render revoke form
?>
		<h3><?php _e('Revoke one-time password list') ?></h3>
		<form method="post" action="<?php echo add_query_arg('action', 'revoke'); ?>">

		<?php wp_nonce_field('otp-revoke'); ?>

		<table class="form-table">

		<tr valign="top">
		<th scope="row"><?php _e('Seed: ') ?></th>
		<td><?php echo $otp_row->seed; ?></td>
		</tr>

		<tr valign="top">
		<th scope="row">&nbsp;</th>
		<td><input type="checkbox" name="otp_revoke"><?php _e('I am sure') ?></td>
		</tr>

		</table>

		<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Revoke') ?>" />
		</p>

		</form>
<?php
	}

	// Check revoke
	if ($_REQUEST['action'] == 'revoke')
		if (!$_POST['otp_revoke'])
			echo '<span class="otp_message">' . __('Select "I am sure" to revoke') . '</span><br />';

	// Output footer
	echo '</div>';

	// Output jQuery for printing
?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#otp_form_print').submit(function() {
				$("#otp_table").jqprint();
				return false;
			});
		});
	</script>
<?php
}

// Register activation hook
if (function_exists('register_activation_hook'))
	register_activation_hook( __FILE__, 'otp_activate' );

// Register actions
if (function_exists('add_action')) {
	add_action('init', 'otp_init');
	add_action('login_head', 'otp_login_head');
	add_action('login_form', 'otp_login_form');
	add_action('admin_menu', 'otp_admin_menu');
}

// Register filters
if (function_exists('add_filter')) {
	add_filter('authenticate', 'otp_authenticate', 10);
	// 20 wp_authenticate_username_password
	// 30 wp_authenticate_cookie
}

?>