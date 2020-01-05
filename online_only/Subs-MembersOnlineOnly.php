<?php

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *	Recreates member groups for the board index to only show online groups..
 *
 *
 *	@since 1.1
*/
function integrate_mark_read_button_OOG()
{
	global $settings, $context, $scripturl;

	if (!empty($settings['show_group_key']))
	{
		$context['membergroups'] = array();
		foreach ($context['online_groups'] as $group)
		{
			if ($group['color'] != '' && $group['hidden'] == 0 && $group['id'] != 3) // so, has a colour, is visible and isn't Moderator
				$context['membergroups'][] = '<a href="' . $scripturl . '?action=groups;sa=members;group=' . $group['id'] . '" style="color: ' . $group['color'] . '">' . $group['name'] . '</a>';
		}
	}
}