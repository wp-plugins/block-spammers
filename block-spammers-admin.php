<?php
/*
Block Spammers by Sander Lepik
To the extent possible under law, the person who associated CC0 with
Block Spammers has waived all copyright and related or neighboring
rights to Block Spammers.
You should have received a copy of the CC0 legalcode along with this
work. If not, see <http://creativecommons.org/publicdomain/zero/1.0/>.
*/

class WBSSettings
{
	// Holds the values to be used in the fields callbacks
	private $options;
	private $blacklisted_words;

	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_plugin_page'));
		add_action('admin_init', array($this, 'page_init'));
		add_action('delete_comment', array($this, 'wbs_add_ip_to_blacklist'));
		add_action('admin_enqueue_scripts', array($this, 'wbs_load_scripts'));
	}

	// Add options page
	public function add_plugin_page()
	{
		global $wbs_settings_page;
		$wbs_settings_page = add_options_page(
			__('Block Spammers', 'wbs'),
			__('Block Spammers', 'wbs'),
			'moderate_comments',
			'wbs-admin',
			array($this, 'create_admin_page')
		);
	}

	// Options page callback
	public function create_admin_page()
	{
		// Set class property
		$this->options = get_option('wbs_options');
		$this->blacklisted_words = get_option('blacklist_keys');
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php echo __('Block Spammers', 'wbs'); ?></h2>
			<form method="post" action="options.php">
			<?php
				// Hidden settings
				settings_fields('wbs_options_group');
				do_settings_sections('wbs-admin');
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	// Scripts callback
	public function wbs_load_scripts($hook)
	{
		global $wbs_settings_page;

		if($hook != $wbs_settings_page)
		{
			return;
		}

		wp_enqueue_script('wbs-main-js', plugin_dir_url(__FILE__) . 'js/main.js');
	}

	// Register and add settings
	public function page_init()
	{
		register_setting(
			'wbs_options_group',
			'wbs_options',
			array($this, 'sanitize')
		);

		add_settings_section(
			'wbs-general-settings',
			__('General settings', 'wbs'),
			array(),
			'wbs-admin'
		);

		add_settings_section(
			'wbs-manual-blocking',
			__('Manual blocking', 'wbs'),
			array($this, 'print_manual_blocking_info'),
			'wbs-admin'
		);

		add_settings_field(
			'wbs-spam-ips-blocked',
			__('Block IPs of spam comments', 'wbs'),
			array($this, 'blocked_spam_ips_callback'),
			'wbs-admin',
			'wbs-general-settings'
		);

		add_settings_field(
			'wbs-block-bad-words',
			__('Block bad words', 'wbs'),
			array($this, 'block_bad_words_callback'),
			'wbs-admin',
			'wbs-general-settings'
		);

		add_settings_field(
			'wbs-add-to-blacklist-during-deleting',
			__('Deleting spam', 'wbs'),
			array($this, 'wbs_add_to_blacklist_during_deleting_callback'),
			'wbs-admin',
			'wbs-general-settings'
		);

		add_settings_field(
			'wbs-message-to-spammers',
			__('Message for spammers', 'wbs'),
			array($this, 'message_to_spammers_callback'),
			'wbs-admin',
			'wbs-general-settings'
		);

		add_settings_field(
			'wbs-manual-blocking-textarea',
			__('Block IPs', 'wbs'),
			array($this, 'wbs_manual_blocking_callback'),
			'wbs-admin',
			'wbs-manual-blocking'
		);

		add_settings_field(
			'wbs-blacklist-keys',
			__('Bad words', 'wbs'),
			array($this, 'wbs_blacklist_keys_callback'),
			'wbs-admin',
			'wbs-manual-blocking'
		);
	}

	// Sanitize each setting field as needed
	public function sanitize($input)
	{
		$new_input = array();

		if(isset($input['wbs-spam-ips-blocked']) && $input['wbs-spam-ips-blocked'])
		{
			$new_input['wbs-spam-ips-blocked'] = $input['wbs-spam-ips-blocked'];
		}

		if(isset($input['wbs-block-bad-words']) && $input['wbs-block-bad-words'])
		{
			$new_input['wbs-block-bad-words'] = $input['wbs-block-bad-words'];
		}

		if(isset($input['wbs-add-ips-to-blacklist']) && $input['wbs-add-ips-to-blacklist'])
		{
			$new_input['wbs-add-ips-to-blacklist'] = $input['wbs-add-ips-to-blacklist'];
		}

		if(isset($input['wbs-add-to-blacklist-during-deleting']) && $input['wbs-add-to-blacklist-during-deleting'])
		{
			$new_input['wbs-add-to-blacklist-during-deleting'] = $input['wbs-add-to-blacklist-during-deleting'];
		}

		if(isset($input['wbs-message-to-spammers']))
		{
			$new_input['wbs-message-to-spammers'] = sanitize_text_field($input['wbs-message-to-spammers']);
		}

		if(isset($input['wbs-manual-blocking-textarea']))
		{
			// Sort the list first
			$ips_to_sort = array_unique(array_map('trim', explode("\n", $input['wbs-manual-blocking-textarea'])));
			natsort($ips_to_sort);

			$new_input['wbs-manual-blocking-textarea'] = implode("\n", array_map('sanitize_text_field', $ips_to_sort));
		}

		// Sanitize and update blacklisted words
		if(isset($input['wbs-block-bad-words-textarea']))
		{
			$bad_words = implode("\n", array_unique(array_filter(array_map('trim', explode("\n", $input['wbs-block-bad-words-textarea'])))));
			update_option('blacklist_keys', $bad_words);
		}

		return $new_input;
	}

	// Manual blocking info
	public function print_manual_blocking_info()
	{
		print __('In this section you can block spammers manually.', 'wbs');
	}

	// Checkbox for blocking IPs of spam comments
	public function blocked_spam_ips_callback()
	{
		printf('<fieldset><label><input type="checkbox" name="wbs_options[wbs-spam-ips-blocked]" value="1" %s> %s</label></fieldset>', checked(isset($this->options['wbs-spam-ips-blocked']), true, false), __('Block comments from IPs that have posted spam before<br />(IPs will be obtained from comments that are marked as spam)', 'wbs'));
	}

	// Checkbox for blocking comments that contain bad words
	public function block_bad_words_callback()
	{
		printf('<fieldset><label><input type="checkbox" id="wbs-block-bad-words" onchange="wbs_bad_words_checkbox();" name="wbs_options[wbs-block-bad-words]" value="1" %s> %s</label>%s', checked(isset($this->options['wbs-block-bad-words']), true, false), __('Block comments that contain blacklisted words', 'wbs'), /* Hide blacklisted words if needed */ isset($this->options['wbs-block-bad-words']) ? '' : '<script type="text/javascript">jQuery(document).ready(function() { hide_bad_words(); });</script>');

		// Checkbox to add IPs of such comments into blacklist
		printf('<br /><label%s><input type="checkbox" id="wbs-add-ips-to-blacklist" name="wbs_options[wbs-add-ips-to-blacklist]"%s value="1" %s> %s</label></fieldset>', isset($this->options['wbs-block-bad-words']) ? '' : ' style="color:#666"', isset($this->options['wbs-block-bad-words']) ? '' : ' disabled=""', checked(isset($this->options['wbs-add-ips-to-blacklist']), true, false), __('Add IPs of those comments into the blacklist', 'wbs'));
	}

	// Checkbox for adding IPs of spam into blacklist during deleting
	public function wbs_add_to_blacklist_during_deleting_callback()
	{
		printf('<fieldset><label><input type="checkbox" name="wbs_options[wbs-add-to-blacklist-during-deleting]" value="1" %s> %s</label></fieldset>', checked(isset($this->options['wbs-add-to-blacklist-during-deleting']), true, false), __('When deleting spam, add IPs of spam comments into the blacklist', 'wbs'));
	}

	// Message for spammers
	public function message_to_spammers_callback()
	{
		printf('<input type="text" name="wbs_options[wbs-message-to-spammers]" class="regular-text" value="%s">', isset($this->options['wbs-message-to-spammers']) ? $this->options['wbs-message-to-spammers'] : __('You are banned from commenting!', 'wbs'));
	}

	// Textarea for manual blocking patterns
	public function wbs_manual_blocking_callback()
	{
		printf('<fieldset><p><label for="wbs-manual-blocking-textarea">%s<br /><strong>&raquo;</strong> 192.168.1.100<br /><strong>&raquo;</strong> 192.168.1.*<br /><strong>&raquo;</strong> 192.168.*.*</p></label></p><textarea id="wbs-manual-blocking-textarea" name="wbs_options[wbs-manual-blocking-textarea]" cols="50" rows="10" class="large-text code">%s</textarea></fieldset>', __('Use * for wildcards and start each entry on a new line.<br /><p>Examples:', 'wbs'), isset($this->options['wbs-manual-blocking-textarea']) ? esc_textarea($this->options['wbs-manual-blocking-textarea']) : '');
	}

	// Textarea for blacklisted words
	public function wbs_blacklist_keys_callback()
	{
		printf('<fieldset><p><label for="wbs-blacklist-keys">%s %s</label></p><textarea id="wbs-blacklist-keys" name="wbs_options[wbs-block-bad-words-textarea]" cols="50" rows="10" class="large-text code">%s</textarea></fieldset>', __('When a comment contains any of these words in its content, name, URL, e-mail, or IP, it will be marked as spam. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.'), __('This is the same option as ', 'wbs') . '<a href="options-discussion.php">' . __('Discussion Settings') . ' -> ' . __('Comment Blacklist') . '</a>.', isset($this->blacklisted_words) ? esc_textarea($this->blacklisted_words) : '');
	}

	// Add IPs of spam comments into blacklist
	public function wbs_add_ip_to_blacklist($comment_id)
	{
		$wbs_options = get_option('wbs_options');
		if(isset($wbs_options['wbs-add-to-blacklist-during-deleting']))
		{
			global $wpdb;
			$spam_ip = $wpdb->get_row("SELECT comment_author_IP FROM $wpdb->comments WHERE comment_approved='spam' AND comment_ID=$comment_id");
			if($spam_ip != null)
			{
				$blocked_ips = array_map('trim', explode("\n", $wbs_options['wbs-manual-blocking-textarea']));
				$blocked_ips[] = $spam_ip->comment_author_IP;
				$blocked_ips = array_unique($blocked_ips);
        	                natsort($blocked_ips);
				$wbs_options['wbs-manual-blocking-textarea'] = implode("\n", $blocked_ips);
				update_option('wbs_options', $wbs_options);
			}
		}
	}
}

if(is_admin())
{
	$wbs_settings_page = new WBSSettings();
}
?>
