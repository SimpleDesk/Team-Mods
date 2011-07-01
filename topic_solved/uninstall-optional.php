<?php
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF')) // If we are outside SMF and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot uninstall - please verify you put this file in the same place as SMF\'s SSI.php.');

// OK, so we're cleaning up everything in the DB. The new columnw will have been taken care of already.

// 1. Clean up the settings table.
$remove = array('enable_solved');
foreach ($modSettings as $variable => $value)
	if (strpos($variable, 'topicsolved') === 0)
		$remove[] = $variable;

$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings WHERE variable IN ({array_string:vars})',
	array(
		'vars' => $remove
	)
);

// 2. Clean up permissions.
$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}board_permissions
	WHERE permission LIKE {string:perm}',
	array(
		'perm' => 'solve_topic%',
	)
);

// 3. Clean up the moderation log.
$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}log_actions
	WHERE id_log = {int:topic_solved_log}',
	array(
		'topic_solved_log' => 4,
	)
);

// 4. Reset topic solved icons to their normal state.
$smcFunc['db_query']('', '
	UPDATE {db_prefix}messages
	SET icon = {string:xx}
	WHERE icon = {string:solved}',
	array(
		'xx' => 'xx', // for the bog standard icon
		'solved' => 'solved', // for our icon
	)
);

if (SMF == 'SSI')
{
	if (in_array('solved', $installed))
		echo 'All topic-solved activity has been removed!';
	else
		echo 'Database edits failed!';
}
?>