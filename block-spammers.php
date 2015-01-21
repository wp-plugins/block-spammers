<?php
/**
Plugin Name: Block Spammers
Plugin URI: https://github.com/sander85/block-spammers
Description: Block spammers from submitting comments, by IPs or by bad words.
Author: Sander Lepik
Version: 0.2
Text Domain: wbs
Domain Path: /languages/
Author URI: https://sander85.eu
License: CC0
*/

/*
Block Spammers by Sander Lepik
To the extent possible under law, the person who associated CC0 with
Block Spammers has waived all copyright and related or neighboring
rights to Block Spammers.
You should have received a copy of the CC0 legalcode along with this
work. If not, see <http://creativecommons.org/publicdomain/zero/1.0/>.
*/

defined('ABSPATH') or die("No script kiddies please!");

add_filter('preprocess_comment', 'wbs_process_comment', 1);
add_action('init', 'wbs_load_textdomain');


// Initialize translations
function wbs_load_textdomain()
{
	load_plugin_textdomain('wbs', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

function wbs_get_ip_address()
{
	return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
}

function wbs_get_user_agent()
{
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;
}

function wbs_process_comment($commentdata)
{
	$wbs_options = get_option('wbs_options');
	global $wpdb;

	// Are we going to block some spam?
	if(isset($wbs_options['wbs-spam-ips-blocked']) || isset($wbs_options['wbs-manual-blocking-textarea']) || isset($wbs_options['wbs-block-bad-words']))
	{
		$wbs_banned_ips = array();

		// Are we going to block IPs of spam comments
		if(isset($wbs_options['wbs-spam-ips-blocked']) && $wbs_options['wbs-spam-ips-blocked'])
		{
			// Get IPs of spam comments
			$spam_ips = $wpdb->get_results("SELECT DISTINCT comment_author_IP FROM $wpdb->comments WHERE comment_approved='spam'");

			foreach($spam_ips as $sip)
			{
				$wbs_banned_ips[] = $sip->comment_author_IP;
			}
		}

		// Block manually added IPs
		if(isset($wbs_options['wbs-manual-blocking-textarea']) && $wbs_options['wbs-manual-blocking-textarea'] != '')
		{
			$manually_added_ips = array_map('trim', explode("\n", $wbs_options['wbs-manual-blocking-textarea']));

			foreach($manually_added_ips as $mip)
			{
				// Strip wildcards
				$mip = explode('.*', $mip);
				$mip = $mip['0'];
				$wbs_banned_ips[] = $mip;
			}
		}

		// Block comments that contain blacklisted words
		if(isset($wbs_options['wbs-block-bad-words']) && $wbs_options['wbs-block-bad-words'])
		{
			if(wp_blacklist_check($commentdata['comment_author'], $commentdata['comment_author_email'], $commentdata['comment_author_url'], $commentdata['comment_content'], wbs_get_ip_address(), wbs_get_user_agent()))
			{
				$wbs_banned_ips[] = wbs_get_ip_address();

				// If requested, then add those IPs into blacklist
				if(isset($wbs_options['wbs-add-ips-to-blacklist']) && $wbs_options['wbs-add-ips-to-blacklist'])
				{
					$manually_added_ips = array_map('trim', explode("\n", $wbs_options['wbs-manual-blocking-textarea']));
					$manually_added_ips[] = wbs_get_ip_address();
					$manually_added_ips = array_unique($manually_added_ips);
					natsort($manually_added_ips);
					$wbs_options['wbs-manual-blocking-textarea'] = implode("\n", $manually_added_ips);
					update_option('wbs_options', $wbs_options);
				}
			}
		}

		foreach($wbs_banned_ips as $ip)
		{
			if(substr(wbs_get_ip_address(), 0, strlen($ip)) == $ip)
			{
				// Get the message
				$message = isset($wbs_options['wbs-message-to-spammers']) ? $wbs_options['wbs-message-to-spammers'] : __('You are banned from commenting!', 'wbs');

				wp_die($message);
			}
		}
	}

	return $commentdata;
}

include 'block-spammers-admin.php';

?>
