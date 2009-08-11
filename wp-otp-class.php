<?php

/*
	Support class One-Time Password WordPress Plugin
	by Marcel Bokhorst
*/

// Include OTP library
require_once('include/class.otp.php');

// Define constants
define('c_otp_action_arg', 'otp_action');
define('c_otp_action_challenge', 'challenge');
define('c_otp_action_seed', 'seed');
define('c_otp_action_allow', 'allow');
define('c_otp_action_generate', 'generate');
define('c_otp_action_revoke', 'revoke');
define('c_otp_action_settings', 'settings');

define('c_otp_user_arg', 'otp_user');
define('c_otp_table_name', 'otp');

define('c_otp_option_dbver', 'otp_dbver');
define('c_otp_option_strict', 'otp_strict');
define('c_otp_option_allow', 'otp_allow');
define('c_otp_option_cleanup', 'otp_cleanup');

define('c_otp_text_domain', 'one-time-password');
define('c_otp_session', 'otp_session');
define('c_otp_redirect', 'otp_redirect');
define('c_otp_qa_authorization', 'otp_authorization');

define('c_otp_meta_seed', 'otp_meta_seed');
define('c_otp_meta_algorithm', 'otp_meta_algorithm');
define('c_otp_meta_sequence', 'otp_meta_sequence');
define('c_otp_meta_hash', 'otp_meta_hash');
define('c_otp_meta_generated', 'otp_meta_generated');
define('c_otp_meta_last_login', 'otp_meta_last_login');

// Define class
if (!class_exists('WPOneTimePassword')) {
	class WPOneTimePassword {
		// Class variables
		private $main_file = null;

		// Constructor
		function WPOneTimePassword() {
			$bt = debug_backtrace();
			$this->main_file = $bt[0]['file'];

			// Register (de)activation hook
			register_activation_hook($this->main_file, array(&$this, 'otp_activate'));
			register_deactivation_hook($this->main_file, array(&$this, 'otp_deactivate'));

			// Register actions
			add_action('init', array(&$this, 'otp_init'));
			add_action('login_head', array(&$this, 'otp_login_head'));
			add_action('login_form', array(&$this, 'otp_login_form'));
			add_action('wp_logout', array(&$this, 'otp_wp_logout'));
			if (is_admin()) {
				add_action('admin_menu', array(&$this, 'otp_admin_menu'));
				add_action('admin_head', array(&$this, 'otp_admin_head'));
				add_action('admin_footer', array(&$this, 'otp_admin_footer'));
			}

			// Register filters
			add_filter('authenticate', array(&$this, 'otp_authenticate'), 10);
			// 20 wp_authenticate_username_password
			// 30 wp_authenticate_cookie
			add_filter('wp_redirect', array(&$this, 'otp_redirect'));

			// Start session to register states
			session_start();
		}

		// Handle plugin activation
		function otp_activate() {
			global $wpdb;
			$dbver = get_option(c_otp_option_dbver);
			$otp_table = $wpdb->prefix . c_otp_table_name;

			// Update old table definition
			if ($dbver && $dbver < 1) {
				$sql = "ALTER TABLE " . $otp_table . " CHANGE COLUMN time generated DATETIME NOT NULL;";
				if ($wpdb->query($sql) === false)
					$wpdb->print_error();
				$sql = "ALTER TABLE " . $otp_table . " ADD COLUMN last_login DATETIME NULL;";
				if ($wpdb->query($sql) === false)
					$wpdb->print_error();
			}

			// Convert data from table to user meta data
			if ($dbver && $dbver < 2) {
				$rows = $wpdb->get_results("SELECT * FROM " . $otp_table);
				foreach ($rows as $row) {
					$user = new WP_User($row->user);
					update_usermeta($user->ID, c_otp_meta_seed, $row->seed);
					update_usermeta($user->ID, c_otp_meta_algorithm, $row->algorithm);
					update_usermeta($user->ID, c_otp_meta_sequence, $row->sequence + 1);
					update_usermeta($user->ID, c_otp_meta_hash, $row->hash);
					update_usermeta($user->ID, c_otp_meta_generated, $row->generated);
					update_usermeta($user->ID, c_otp_meta_last_login, $row->last_login);
				}

				$sql = "DROP TABLE " . $wpdb->prefix . c_otp_table_name;
				if ($wpdb->query($sql) === false)
					$wpdb->print_error();

				// Store database version
				update_option(c_otp_option_dbver, 2);
			}

			// Create default allowed list
			if (!get_option(c_otp_option_allow))
				add_option(c_otp_option_allow, $this->otp_get_allow_default());
		}

		// Handle plugin deactivation
		function otp_deactivate() {
			// Cleanup if requested
			if (get_option(c_otp_option_cleanup)) {
				// Delete data
				global $wpdb;
				$sql = "DELETE FROM " . $wpdb->usermeta;
				$sql .= " WHERE meta_key='" . c_otp_meta_seed . "'";
				$sql .= " OR meta_key='" . c_otp_meta_algorithm . "'";
				$sql .= " OR meta_key='" . c_otp_meta_sequence . "'";
				$sql .= " OR meta_key='" . c_otp_meta_hash . "'";
				$sql .= " OR meta_key='" . c_otp_meta_generated . "'";
				$sql .= " OR meta_key='" . c_otp_meta_last_login . "'";
				if ($wpdb->query($sql) === false)
					$wpdb->print_error();

				// Delete options
				delete_option(c_otp_option_dbver);
				delete_option(c_otp_option_strict);
				delete_option(c_otp_option_allow);
				delete_option(c_otp_option_cleanup);
			}
			$_SESSION[c_otp_session] = false;
		}

		// Handle redirect
		function otp_redirect($location) {
			$_SESSION[c_otp_redirect] = true;
			return $location;
		}

		// Handle initialize
		function otp_init() {
			// Check if redirect
			if (isset($_SESSION[c_otp_redirect]))
				unset($_SESSION[c_otp_redirect]);
			// Check if admin protection
			else if ($this->otp_is_otp_session()) {
				// Get uri to check
				$uri = $_SERVER['REQUEST_URI'];
				$question = strpos($uri, '?');
				if ($question !== false)
					$uri = substr($uri, 0, $question);
				$first = true;
				foreach (split('&', $_SERVER['QUERY_STRING']) as $qs) {
					$x = split('=', $qs);
					if ($x[0] != c_otp_qa_authorization && $x[0] != '') {
						$uri .= ($first ? '?' : '&') . $x[0] . '=*';
						$first = false;
					}
				}
				// Get allowed list
				$allow = explode("\n", get_option(c_otp_option_allow));
				for ($i = 0; $i < count($allow); $i++)
					$allow[$i] = trim($allow[$i]);

				// Check if allowed
				if(!in_array($uri, $allow)) {
					// Get current user
					global $current_user;
					get_currentuserinfo();

					// Check password
					$pwd = $_REQUEST[c_otp_qa_authorization];
					if ($this->otp_check_otp($current_user, $pwd) == null) {
						// Not authorized
						$msg = '<strong>' . __('Authorization failed') . '</strong>';
						$msg .= '<br /><br />Uri: ' . $uri;
						$msg .= '<br />User: ' . $current_user->user_login;
						$msg .= '<br />Password: ' . $pwd;
						$msg .= '<br />Challenge: ' . $this->otp_get_challenge($current_user);
						$msg .= '<br /><br /><a href="javascript:history.go(-1)">' . __('Go back') . '</a>';
						wp_die($msg);
					}
					// Authorized, redirect to cleaned uri
					else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
						$uri = $_SERVER['REQUEST_URI'];
						$question = strpos($uri, '?');
						if ($question !== false)
							$uri = substr($uri, 0, $question);
						$first = true;
						foreach (split('&', $_SERVER['QUERY_STRING']) as $qs) {
							$x = split('=', $qs);
							if ($x[0] != c_otp_qa_authorization) {
								$uri .= ($first ? '?' : '&') . $x[0] . '=' . $x[1];
								$first = false;
							}
						}
						wp_redirect($uri);
					}
				}
			}

			// Only load styles and scripts when necessary
			if (is_admin() || strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
				// I18n
				load_plugin_textdomain(c_otp_text_domain, false, basename(dirname($this->main_file)));

				// Enqueue style sheet
				$css_name = $this->otp_change_extension(basename($this->main_file), '.css');
				if (file_exists(TEMPLATEPATH . '/' . $css_name))
					$css_url = get_bloginfo('template_directory') . '/' . $css_name;
				else
					$css_url = WP_PLUGIN_URL . '/' . basename(dirname($this->main_file)) . '/' . $css_name;
				wp_register_style('otp_style', $css_url);
				wp_enqueue_style('otp_style');

				// Enqueue scripts
				wp_enqueue_script('jquery');
				if (is_admin()) {
					$plugin_dir = '/' . PLUGINDIR .  '/' . basename(dirname($this->main_file));
					wp_enqueue_script('jQuery-Plugin-jqPrint', $plugin_dir . '/js/jquery.jqprint.js');
					wp_enqueue_script('jQuery-Plugin-URL-Utils', $plugin_dir . '/js/jquery.ba-url.js');
					wp_enqueue_script('jQuery-Plugin-SimpleModal', $plugin_dir . '/js/jquery.simplemodal.js');
				}
			}
		}

		// Modify login head
		function otp_login_head() {
			// Print styles and scripts not called on login page
			wp_print_styles();
			wp_print_scripts();
		}

		// Modify login form
		function otp_login_form() {
?>
			<script type="text/javascript">
			/* <![CDATA[ */
				jQuery(document).ready(function($) {
					/* Create element for challenge */
					$('#user_pass').parent().parent().after($('<p id="otp_challenge">'));

					/* Hide challenge when username changes */
					$('#user_login').keyup(function() {
						$('#otp_challenge').hide();
					});

					/* Show challenge when password gets focus */
					$('#user_pass').focus(function() {
						var otp_challenge = $('#otp_challenge');
						otp_challenge.text('<?php _e('Wait', c_otp_text_domain); ?>');
						otp_challenge.show();
						$.ajax({
							type: 'GET',
							data: {
								<?php echo c_otp_action_arg; ?>: '<?php echo c_otp_action_challenge; ?>',
								<?php echo c_otp_user_arg; ?>: $('#user_login').val()
							},
							dataType: 'text',
							cache: false,
							success: function(result) {
								if (result == '')
									otp_challenge.hide();
								else {
									parts = result.split(' ');
									parts[1] = '<span id="otp_challenge_seq">' + parts[1] + '</span>';
									otp_challenge.html(parts.join(' '));
								}
							},
							error: function(x, stat, e) {
								otp_challenge.text('Error ' + x.status);
							}
						});
					});

					/* Hide challenge/error on login */
					$('#loginform').submit(function() {
						$('#otp_challenge').hide();
						return true;
					});
				});
			/* ]]> */
			</script>
<?php
		}

		// Modify admin header
		function otp_admin_head() {
			// Output form & jQuery if admin protection
			if ($this->otp_is_otp_session()) {
				global $current_user;
				get_currentuserinfo();
?>
				<div id="otp_auth_window">
				<a href="#" title="Close" class="modalCloseX simplemodal-close">x</a>
				<h2><?php _e('Authorize', c_otp_text_domain) ?></h2>
				<form id="otp_form_auth" method="post" action="#">
				<p><label><?php _e('Password:', c_otp_text_domain) ?><br /><input type="password" name="otp_auth_pwd" /></label></p>
				<p id="otp_challenge" />
				<p class="textright">
				<input type="submit" class="button-primary" value="<?php _e('Ok', c_otp_text_domain) ?>" />
				</p>
				</form>
				</div>

				<script type="text/javascript">
				/* <![CDATA[ */
					var otp_required = false;

					/* Helper get relative url / process query string */
					function otp_get_url(url) {
						if (url.indexOf('?') >= 0) {
							var qs = jQuery.queryString(url);
							for (k in qs) qs[k] = '*';
							url = jQuery.queryString(url, qs);
						}
						var baddr = window.location.protocol + '//' + window.location.hostname;
						if (url.indexOf(baddr) == 0)
							url = url.substring(baddr.length);
						return url;
					}

					/* Define array indexOf */
					if (!Array.prototype.indexOf) {
						Array.prototype.indexOf = function(elt /*, from*/) {
							var len = this.length;
							var from = Number(arguments[1]) || 0;
							from = (from < 0) ? Math.ceil(from) : Math.floor(from);
							if (from < 0)
								from += len;

							for (; from < len; from++) {
								if (from in this && this[from] === elt)
									return from;
							}
							return -1;
						};
					}

					jQuery(document).ready(function($) {
						var otp_elm;
						var otp_allow = new Array();
						/* Allow list as checked on the server */
<?php
						// Output allowed list
						$allow = explode("\n", get_option(c_otp_option_allow));
						for ($i = 0; $i < count($allow); $i++)
							echo 'otp_allow[' . $i . '] = "' . trim($allow[$i]) . '";' . PHP_EOL;
?>
						/* Submit authorize form */
						$('#otp_form_auth').submit(function() {
							var otp_pwd = $('[name=otp_auth_pwd]').val();
							$.modal.close();
							if (otp_elm.tagName == "FORM") {
								$(otp_elm).children(':first').before($('<input type="hidden" name="<?php echo c_otp_qa_authorization; ?>" value="' + otp_pwd + '" />'));
								$(otp_elm).submit();
							}
							else {
								otp_href = $(otp_elm).attr('href');
								window.location = $.queryString(otp_href, '<?php echo c_otp_qa_authorization; ?>=' + otp_pwd);
							}
							return false;
						});

						/* Submit other forms */
						$('form').not('#otp_form_auth').submit(function() {
							if (otp_required) {
								var act = $(this).attr('action');
								var aurl;
								if (act == '')
									aurl = window.location.href;
								else
									aurl = $('<a href="' + act + '" />')[0].href;
								var url = otp_get_url(aurl);
								var otp_auth = $('[name=<?php echo c_otp_qa_authorization; ?>]');
								if ($.isUrlInternal(url) == true && otp_allow.indexOf(url) < 0 && otp_auth.length == 0) {
									otp_elm = this;
									$('#otp_auth_window').modal();
									$('[name=otp_auth_pwd]').focus();
									return false;
								}
							}
							return true;
						});

						/* Click links, except otp ajax */
						$('a').not('#otp_seed_new,#otp_allow_default').click(function() {
							if (otp_required) {
								var url = otp_get_url(this.href);
								if ($.isUrlInternal(url) == true && otp_allow.indexOf(url) < 0) {
									otp_elm = this;
									$('#otp_auth_window').modal();
									$('[name=otp_auth_pwd]').focus();
									return false;
								}
							}
							return true;
						});

						/* Get challenge */
						$('[name=otp_auth_pwd]').focus(function() {
							var otp_challenge = $('#otp_challenge');
							otp_challenge.text('<?php _e('Wait', c_otp_text_domain); ?>');
							$.ajax({
								type: 'GET',
								data: {
									<?php echo c_otp_action_arg; ?>: '<?php echo c_otp_action_challenge; ?>',
									<?php echo c_otp_user_arg; ?>: '<?php echo $current_user->user_login; ?>'
								},
								dataType: 'text',
								cache: false,
								success: function(result) {
									if (result == '')
										otp_challenge.text('');
									else {
										parts = result.split(' ');
										parts[1] = '<span id="otp_challenge_seq">' + parts[1] + '</span>';
										otp_challenge.html(parts.join(' '));
									}
								},
								error: function(x, stat, e) {
									otp_challenge.text('Error ' + x.status);
								}
							});
						});
					});
				/* ]]> */
				</script>
<?php
			}
		}

		// Modify admin footer
		function otp_admin_footer() {
			// Get current user
			global $current_user;
			get_currentuserinfo();

			// Display if list should be generated
			$sequence = get_usermeta($current_user->ID, c_otp_meta_sequence);
			if ($sequence <= 0) {
				$url = admin_url('options-general.php?page=' . plugin_basename($this->main_file));
				echo '<div class="error fade otp_admin_notice"><p>' . __('One-Time Password list', c_otp_text_domain);
				echo ' <a href="' . $url . '">' . __('should be generated', c_otp_text_domain) . '</a></p></div>';
			}

			// Display if otp enabled
			if ($this->otp_is_otp_session()) {
?>
				<div class="error fade otp_admin_notice">
				<p><?php _e('Protected One-Time Password session', c_otp_text_domain); ?></p>
				</div>

				<script type="text/javascript">
				/* <![CDATA[ */
					otp_required = true;
				/* ]]> */
				</script>
<?php
			}
		}

		// Authenticate using OTP
		function otp_authenticate($user) {
			try {
				// Get data
				$user = new WP_User(sanitize_user($_POST['log']));
				$pwd = $_POST['pwd'];
				$otp_auth = $this->otp_check_otp($user, $pwd);
				if ($otp_auth != null)
					$_SESSION[c_otp_session] = true;
				return $otp_auth;
			}
			// Fail-safe
			catch (Exception $e) {
				return null;
			}
		}

		// Handle logout
		function otp_wp_logout() {
			$_SESSION[c_otp_session] = false;
		}

		// Register options page
		function otp_admin_menu() {
			if (function_exists('add_options_page'))
				add_options_page(
					__('One-Time Password Administration', c_otp_text_domain),
					__('One-Time Password', c_otp_text_domain),
					0,
					$this->main_file,
					array(&$this, 'otp_administration'));
		}

		// Handle option page
		function otp_administration() {
			// Instantiate OTP
			$otp_class = new otp();

			// Get current user
			global $current_user;
			get_currentuserinfo();

			echo '<div class="wrap">';

			// Render Info panel
			$this->otp_render_info_panel();

			// Render title
			echo '<div id="otp_admin_panel">';
			echo '<h2>' . __('One-Time Password Administration', c_otp_text_domain) . '</h2>';

			// Handle generate action
			if (isset($_REQUEST[c_otp_action_arg]) && $_REQUEST[c_otp_action_arg] == c_otp_action_generate) {
				// Security check
				check_admin_referer('otp-generate');

				// Get parameters
				$otp_pwd = $_POST['otp_pwd'];
				$otp_verify = $_POST['otp_pwd_verify'];
				$otp_count = intval($_POST['otp_count']);
				$otp_seed = $_POST['otp_seed'];
				$otp_algorithm = $_POST['otp_algorithm'];

				// Check parameters
				$otp_msg = array();

				// Check pass-phrase
				if ($_POST['otp_initialize']) {
					$otp_data = $otp_class->initializeOtp($otp_pwd, $otp_seed, $otp_count, $otp_algorithm);
					if (!$otp_data)
						$otp_msg[] = __('Invalid pass-phrase', c_otp_text_domain);
				}
				else {
					if (!$otp_class->isValidPassPhrase($otp_pwd))
						$otp_msg[] = __('Invalid pass-phrase', c_otp_text_domain);
				}
				// Verify pass-phrase
				if ($otp_pwd != $otp_verify)
					$otp_msg[] = __('Pass-phrases do not match', c_otp_text_domain);
				// Check seed
				if (!$otp_class->isValidSeed($otp_seed))
					$otp_msg[] = __('Invalid seed', c_otp_text_domain);
				if ($_POST['otp_initialize']) {
					$old_seed = get_usermeta($current_user->ID, c_otp_meta_seed);
					if ($old_seed && $otp_seed == $old_seed)
						$otp_msg[] = __('Invalid seed', c_otp_text_domain);
				}
				// Check algorithm
				if (!in_array($otp_algorithm, $otp_class->getAvailableAlgorithms()))
					$otp_msg[] = __('Invalid algorithm', c_otp_text_domain);
				// Check count
				if ($otp_count <= 0 || $otp_count > 1000)
					$otp_msg[] = __('Invalid count', c_otp_text_domain);

				if (count($otp_msg) == 0) {
					// Initialize password list
					if ($_POST['otp_initialize']) {
						$otp_seq = $otp_data['next_sequence'];
						$otp_hash = $otp_data['previous_hex_otp'];
					}
					// Generate password list
					else {
						$otp_list = $otp_class->generateOtpList($otp_pwd, $otp_seed, null, $otp_count + 1, $otp_algorithm);
						$otp_seq = $otp_list[1]['sequence'];
						$otp_hash = $otp_list[0]['hex_otp'];
					}

					// Store data
					update_usermeta($current_user->ID, c_otp_meta_seed, $otp_seed);
					update_usermeta($current_user->ID, c_otp_meta_algorithm, $otp_algorithm);
					update_usermeta($current_user->ID, c_otp_meta_sequence, $otp_seq + 1);
					update_usermeta($current_user->ID, c_otp_meta_hash, $otp_hash);
					update_usermeta($current_user->ID, c_otp_meta_generated, date('r'));
					delete_usermeta($current_user->ID, c_otp_meta_last_login);

					// Render password list / print form
					if (!$_POST['otp_initialize'])
						$this->otp_render_password_list($current_user, $otp_list);
				}
			}

			// Render generate form
			$this->otp_render_generate_form();

			if (count($otp_msg)) {
				// Display errors
				foreach ($otp_msg as $msg)
					echo '<span class="otp_admin_error">' . $msg . '</span><br />';
			}

			// Handle revoke action
			if (isset($_REQUEST[c_otp_action_arg]) && $_REQUEST[c_otp_action_arg] == c_otp_action_revoke) {
				// Security check
				check_admin_referer('otp-revoke');

				// Get parameters
				$otp_revoke = $_POST['otp_revoke'];

				// Revoke
				if ($otp_revoke) {
					// Delete data
					delete_usermeta($current_user->ID, c_otp_meta_seed);
					delete_usermeta($current_user->ID, c_otp_meta_algorithm);
					delete_usermeta($current_user->ID, c_otp_meta_sequence);
					delete_usermeta($current_user->ID, c_otp_meta_hash);
					delete_usermeta($current_user->ID, c_otp_meta_generated);
					delete_usermeta($current_user->ID, c_otp_meta_last_login);
				}
			}

			// Render revoke form
			$this->otp_render_revoke_form($current_user);

			// Check revoke parameters
			if (isset($_REQUEST[c_otp_action_arg]) && $_REQUEST[c_otp_action_arg] == c_otp_action_revoke)
				if (!$_POST['otp_revoke'])
					echo '<span class="otp_admin_error">' . __('Select "I am sure" to revoke', c_otp_text_domain) . '</span><br />';

			// Render settings form
			$this->otp_render_settings_form();

			// Output footer
			echo '</div></div>';

			// Output admin jQuery
			$this->otp_output_admin_query();
		}

		function otp_render_info_panel() {
?>
			<div id="otp_resources_panel">
			<h3><?php _e('Resources', c_otp_text_domain); ?></h3>
			<ul>
			<li><a href="http://wordpress.org/extend/plugins/one-time-password/other_notes/" target="_blank"><?php _e('Usage instructions', c_otp_text_domain); ?></a></li>
			<li><a href="http://wordpress.org/extend/plugins/one-time-password/faq/" target="_blank"><?php _e('Frequently asked questions', c_otp_text_domain); ?></a></li>
			<li><a href="http://blog.bokhorst.biz/2200/computers-en-internet/wordpress-plugin-one-time-password/" target="_blank"><?php _e('Support page', c_otp_text_domain); ?></a></li>
			<li><a href="http://blog.bokhorst.biz/about/" target="_blank"><?php _e('About the author', c_otp_text_domain); ?></a></li>
			</ul>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----	">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			</form>
			</div>
<?php
		}

		function otp_render_generate_form() {
			$otp_class = new otp();
?>
			<hr />
			<h3><?php _e('Generate One-Time Password list', c_otp_text_domain) ?></h3>
			<form method="post" action="<?php echo remove_query_arg('updated', add_query_arg(c_otp_action_arg, c_otp_action_generate)); ?>">

			<?php wp_nonce_field('otp-generate'); ?>

			<table class="form-table">

			<tr><th scope="row"><?php _e('Pass-phrase:', c_otp_text_domain) ?></th>
			<td><input type="password" name="otp_pwd" />
			<span class="otp_admin_hint"><?php _e('At least 10 characters', c_otp_text_domain) ?></span></td></tr>

			<tr><th scope="row"><?php _e('Confirm pass-phrase:', c_otp_text_domain) ?></th>
			<td><input type="password" name="otp_pwd_verify" /></td></tr>

			<tr><th scope="row"><?php _e('Pass-phrase is a One-Time Password:', c_otp_text_domain) ?></th>
			<td><input type="checkbox" name="otp_initialize"</td></tr>

			<tr><th scope="row"><?php _e('Count/sequence:', c_otp_text_domain) ?></th>
			<td><input type="text" name="otp_count" value="50" id="otp_admin_count" /></td></tr>

			<tr><th scope="row"><?php _e('Seed:', c_otp_text_domain) ?></th>
			<td><input type="text" name="otp_seed" id="otp_seed" value="<?php echo $otp_class->generateSeed(); ?>" />
			<span class="otp_admin_hint"><?php _e('Only alphanumeric characters', c_otp_text_domain) ?></span></td></tr>

			<tr><th scope="row" />
			<td><a id="otp_seed_new" href="#"><?php _e('New', c_otp_text_domain) ?></a></td></tr>

			<tr><th scope="row"><?php _e('Algorithm:', c_otp_text_domain) ?></th>
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

			</table>

			<p class="otp_admin_warning"><?php _e('Generate a One-Time Password list in a trustworthy environment only', c_otp_text_domain) ?></p>
			<p class="otp_admin_info"><?php _e('The current One-Time Password list will be revoked automatically', c_otp_text_domain) ?></p>
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Generate', c_otp_text_domain) ?>" /></p>
			</form>
<?php
		}

		function otp_render_revoke_form($user) {
			// Check for existing data
			$sequence = get_usermeta($user->ID, c_otp_meta_sequence);
			if ($sequence > 0) {
				// get data
				$sequence--;
				$seed = get_usermeta($user->ID, c_otp_meta_seed);
				$algorithm = get_usermeta($user->ID, c_otp_meta_algorithm);
				$hash = get_usermeta($user->ID, c_otp_meta_hash);
				$generated = get_usermeta($user->ID, c_otp_meta_generated);
				$last_login = get_usermeta($user->ID, c_otp_meta_last_login);
?>
				<hr />
				<h3><?php _e('Revoke One-Time Password list', c_otp_text_domain) ?></h3>
				<form method="post" action="<?php echo remove_query_arg('updated', add_query_arg(c_otp_action_arg, c_otp_action_revoke)); ?>">

				<?php wp_nonce_field('otp-revoke'); ?>

				<table class="form-table">

				<tr><th scope="row"><?php _e('Seed:', c_otp_text_domain) ?></th>
				<td><?php echo $seed; ?></td></tr>

				<tr><th scope="row"><?php _e('Algorithm:', c_otp_text_domain) ?></th>
				<td><?php echo $algorithm; ?></td></tr>

				<tr><th scope="row"><?php _e('Sequence:', c_otp_text_domain) ?></th>
				<td><?php echo $sequence; ?></td></tr>

				<tr><th scope="row"><?php _e('Generated:', c_otp_text_domain) ?></th>
				<td><?php echo $generated; ?></td></tr>

				<tr><th scope="row"><?php _e('Last login:', c_otp_text_domain) ?></th>
				<td><?php echo $last_login; ?></td></tr>

				<tr><th scope="row"><?php _e('I am sure:', c_otp_text_domain) ?></th>
				<td><input type="checkbox" name="otp_revoke" /></td></tr>

				</table>

				<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Revoke', c_otp_text_domain) ?>" /></p>
				</form>
<?php
			}
		}

		function otp_render_password_list($user, $otp_list) {
			// Check for existing data
			$sequence = get_usermeta($user->ID, c_otp_meta_sequence);
			if ($sequence > 0) {
				// get data
				$sequence--;
				$seed = get_usermeta($user->ID, c_otp_meta_seed);
				$algorithm = get_usermeta($user->ID, c_otp_meta_algorithm);
				$generated = get_usermeta($user->ID, c_otp_meta_generated);
?>
				<hr />
				<h3><?php _e('One-Time Password list', c_otp_text_domain) ?></h3>
				<form id="otp_list_print" method="post" action="#">
				<div id="otp_list_area">

				<table id="otp_list_header">
				<tr><th scope="row"><?php _e('User:', c_otp_text_domain) ?></th><td><?php echo $user->user_login; ?></td></tr>
				<tr><th scope="row"><?php _e('Seed:', c_otp_text_domain) ?></th><td><?php echo $seed; ?></td></tr>
				<tr><th scope="row"><?php _e('Algorithm:', c_otp_text_domain) ?></th><td><?php echo $algorithm; ?></td></tr>
				<tr><th scope="row"><?php _e('Generated:', c_otp_text_domain) ?></th><td><?php echo $generated; ?></td></tr>
				</table>

				<table id="otp_list_body">
				<th><?php _e('Seq', c_otp_text_domain) ?></th>
				<th><?php _e('Hex', c_otp_text_domain) ?></th>
				<th><?php _e('Words', c_otp_text_domain) ?></th>
				<th><?php _e('Seq', c_otp_text_domain) ?></th>
				<th><?php _e('Hex', c_otp_text_domain) ?></th>
				<th><?php _e('Words', c_otp_text_domain) ?></th>
	<?php
				// Print passwords
				$h = round((count($otp_list) - 1) / 2.0);
				for ($i = 1; $i <= $h; $i++) {
					echo '<tr><td>' . $otp_list[$i]['sequence'] . '</td>';
					echo '<td>' . $otp_list[$i]['hex_otp'] . '</td>';
					echo '<td>' . $otp_list[$i]['words_otp'] . '</td>';

					if ($i + $h < count($otp_list)) {
						echo '<td>' . $otp_list[$i + $h]['sequence'] . '</td>';
						echo '<td>' . $otp_list[$i + $h]['hex_otp'] . '</td>';
						echo '<td>' . $otp_list[$i + $h]['words_otp'] . '</td></tr>';
					}
					else {
						echo '<td>&nbsp;</td>';
						echo '<td>&nbsp;</td>';
						echo '<td>&nbsp;</td></tr>';
					}
				}
	?>
				</table>
				</div>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Print', c_otp_text_domain) ?>" /></p>
				</form>
<?php
			}
		}

		function otp_render_settings_form() {
			if (current_user_can('manage_options')) {
				$otp_strict = get_option(c_otp_option_strict) ? 'checked' : 'unchecked';
				$otp_cleanup = get_option(c_otp_option_cleanup) ? 'checked' : 'unchecked';

				$referer = admin_url('options-general.php?page=' . plugin_basename($this->main_file));
				$referer = add_query_arg(c_otp_action_arg, c_otp_action_settings);
?>
				<hr />
				<h3><?php _e('Settings One-Time Password', c_otp_text_domain) ?></h3>
				<form method="post" action="<?php echo add_query_arg(c_otp_action_arg, c_otp_action_settings, admin_url('options.php')); ?>">
				<?php wp_nonce_field('options-options', '_wpnonce', false); ?>
				<input type="hidden" name="_wp_http_referer" value="<?php echo $referer; ?>" />
				<input type="hidden" name="option_page" value="options" />

				<table class="form-table">

				<tr><th scope="row"><?php _e('Protect administrative actions:', c_otp_text_domain) ?></th>
				<td><input type="checkbox" name="<?php echo c_otp_option_strict; ?>" <?php echo $otp_strict; ?> /></td></tr>

				<tr><th scope="row"><?php _e('Do not protect:', c_otp_text_domain) ?></th>
				<td><textarea name="<?php echo c_otp_option_allow; ?>" rows="10" cols="50"><?php echo htmlspecialchars(get_option(c_otp_option_allow)); ?></textarea></td></tr>

				<tr><th scope="row" />
				<td><a id="otp_allow_default" href="#"><?php _e('Default', c_otp_text_domain) ?></a></td></tr>

				<tr><th scope="row"><?php _e('Delete data on deactivation:', c_otp_text_domain) ?></th>
				<td><input type="checkbox" name="<?php echo c_otp_option_cleanup; ?>" <?php echo $otp_cleanup; ?> /></td></tr>

				</table>

				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="<?php echo c_otp_option_strict . ',' . c_otp_option_allow . ',' . c_otp_option_cleanup; ?>" />

				<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save', c_otp_text_domain) ?>" /></p>
				</form>
				<hr />
<?php
			}
		}

		function otp_output_admin_query() {
?>
			<script type="text/javascript">
			//* <![CDATA[ */
				jQuery(document).ready(function($) {
					/* Print table */
					$('#otp_list_print').submit(function() {
						$('#otp_list_area').jqprint();
						return false;
					});

					/* New seed */
					$('#otp_seed_new').click(function() {
						otp_seed = $('[name=otp_seed]');
						otp_seed.val('<?php _e('Wait', c_otp_text_domain); ?>');
						$.ajax({
							type: 'GET',
							data: {<?php echo c_otp_action_arg; ?>: '<?php echo c_otp_action_seed; ?>'},
							dataType: 'text',
							cache: false,
							success: function(result) {
								otp_seed.val(result);
							},
							error: function(x, stat, e) {
								otp_seed.val('Error ' + x.status);
							}
						});
						return false;
					});

					/* Allow defaults */
					$('#otp_allow_default').click(function() {
						otp_allow = $('[name=<?php echo c_otp_option_allow; ?>]');
						otp_allow.val('<?php _e('Wait', c_otp_text_domain); ?>');
						$.ajax({
							type: 'GET',
							data: {<?php echo c_otp_action_arg; ?>: '<?php echo c_otp_action_allow; ?>'},
							dataType: 'text',
							cache: false,
							success: function(result) {
								otp_allow.val(result);
							}
						});
						return false;
					});

					/* Default focus */
					$('[name=otp_pwd]').focus();
				});
			/* ]]> */
			</script>
<?php
		}

		// Check for ajax calls
		function otp_check_ajax() {
			if (isset($_GET[c_otp_action_arg])) {
				// Get challenge
				if($_GET[c_otp_action_arg] == c_otp_action_challenge) {
					@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
					$user_name = sanitize_user($_GET[c_otp_user_arg]);
					global $wpdb;
					$sql = "SELECT * FROM " . $wpdb->users . " WHERE user_login='" . $user_name . "'";
					$user = $wpdb->get_row($wpdb->prepare($sql));
					echo ($user == null ? '' : WPOneTimePassword::otp_get_challenge($user));
					exit();
				}

				// Get seed
				else if ($_GET[c_otp_action_arg] == c_otp_action_seed) {
					@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
					$otp_class = new otp();
					echo $otp_class->generateSeed();
					exit();
				}

				// Get default allow list
				else if ($_GET[c_otp_action_arg] == c_otp_action_allow) {
					@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
					echo WPOneTimePassword::otp_get_allow_default();
					exit();
				}
			}
		}

		// Helper check environment
		function otp_check_prerequisites() {
			// Check PHP version
			if (version_compare(PHP_VERSION, '5.0.0', '<'))
				die('One-Time Password requires at least PHP 5.0.0');

			// Check WordPress version
			global $wp_version;
			if (version_compare($wp_version, '2.8') < 0)
				die('One-Time Password requires at least WordPress 2.8');

			// Check basic prerequisities
			WPOneTimePassword::otp_check_function('register_activation_hook');
			WPOneTimePassword::otp_check_function('register_deactivation_hook');
			WPOneTimePassword::otp_check_function('add_action');
			WPOneTimePassword::otp_check_function('add_filter');
			WPOneTimePassword::otp_check_function('wp_register_style');
			WPOneTimePassword::otp_check_function('wp_enqueue_style');
			WPOneTimePassword::otp_check_function('wp_enqueue_script');
			WPOneTimePassword::otp_check_function('wp_print_styles');
			WPOneTimePassword::otp_check_function('wp_print_scripts');
		}

		function otp_check_function($name) {
			if (!function_exists($name))
				die('Required WordPress function "' . $name . '" does not exist');
		}

		// Helper get allow defaults
		function otp_get_allow_default() {
			$allow = array();
			$allow[] = '/';					// Main
			$allow[] = '/wp-admin/';			// Dashboard
			$allow[] = '/wp-admin/index.php';		// Dashboard
			$allow[] = '/wp-admin/post-new.php';		// New post
			$allow[] = '/wp-admin/admin-ajax.php';		// Ajax
			$allow[] = '/wp-admin/index-extra.php?jax=*';	// RSS feeds
			$allow[] = '/wp-login.php';			// Login
			$allow[] = '/wp-login.php?action=*&_wpnonce=*';	// Logout
			return implode("\n", $allow);
		}

		// Helper check if otp session
		function otp_is_otp_session() {
			return (get_option(c_otp_option_strict) && isset($_SESSION[c_otp_session]) && $_SESSION[c_otp_session] == true);
		}

		// Helper get challenge
		function otp_get_challenge($user) {
			// Get/check sequence
			$sequence = get_usermeta($user->ID, c_otp_meta_sequence);
			if ($sequence > 0) {
				// Get data
				$sequence--;
				$seed = get_usermeta($user->ID, c_otp_meta_seed);
				$algorithm = get_usermeta($user->ID, c_otp_meta_algorithm);

				// Create challenge
				$otp_class = new otp();
				return $otp_class->createChallenge($seed, $sequence, $algorithm);
			}
			else
				return '';
		}

		// Helper check otp
		function otp_check_otp($user, $pwd) {
			// Get/check current sequence
			$sequence = get_usermeta($user->ID, c_otp_meta_sequence);
			if ($sequence > 0) {
				// Get data
				$sequence--;
				$seed = get_usermeta($user->ID, c_otp_meta_seed);
				$algorithm = get_usermeta($user->ID, c_otp_meta_algorithm);
				$hash = get_usermeta($user->ID, c_otp_meta_hash);

				// Check password
				$otp_class = new otp();
				$otp_data = $otp_class->initializeOtp($hash, $seed, $sequence, $algorithm);
				$otp_auth = $otp_class->authAgainstHexOtp($pwd, $otp_data['previous_hex_otp'], 'previous', $sequence, $algorithm);
				if ($otp_auth['result']) {
					// Update data
					$next_seq = $sequence - 1;
					update_usermeta($user->ID, c_otp_meta_sequence, $next_seq + 1);
					update_usermeta($user->ID, c_otp_meta_hash, $otp_auth['otp']['previous_hex_otp']);
					update_usermeta($user->ID, c_otp_meta_last_login, date('r'));

					// Athenticate user
					return $user;
				}
			}

			// Fallback to other handlers
			return null;
		}

		// Helper change file extension
		function otp_change_extension($filename, $new_extension) {
			return preg_replace('/\..+$/', $new_extension, $filename);
		}
	}
}

?>