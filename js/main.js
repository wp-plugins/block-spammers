/*
 Block Spammers by Sander Lepik
 To the extent possible under law, the person who associated CC0 with
 Block Spammers has waived all copyright and related or neighboring
 rights to Block Spammers.
 You should have received a copy of the CC0 legalcode along with this
 work. If not, see <http://creativecommons.org/publicdomain/zero/1.0/>.
*/

// Hide or show some options
function wbs_bad_words_checkbox()
{
	if(jQuery('#wbs-block-bad-words').prop('checked'))
	{
		jQuery('#wbs-blacklist-keys').closest('tr').show();
		jQuery('#wbs-add-ips-to-blacklist').prop('disabled', false);
		jQuery('#wbs-add-ips-to-blacklist').closest('label').css('color', 'inherit');
	}
	else
	{
		jQuery('#wbs-blacklist-keys').closest('tr').hide();
		jQuery('#wbs-add-ips-to-blacklist').prop('checked', false);
		jQuery('#wbs-add-ips-to-blacklist').prop('disabled', true);
		jQuery('#wbs-add-ips-to-blacklist').closest('label').css('color', '#666');
	}
}

// Hide bad words textarea if bad words aren't blocked
function hide_bad_words()
{
	jQuery('#wbs-blacklist-keys').closest('tr').hide();
}
