<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF')) // If we are outside SMF and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');
	
global $smcFunc;	
	
if (!array_key_exists('db_add_column', $smcFunc))
	db_extend('packages');

if (empty($modSettings['topicsolved_highlight_col1']))
{
	$new_settings = array(
		'topicsolved_highlight_col1' => '#eefee5',
		'topicsolved_highlight_col2' => '#eafedd',
	);

	foreach ($new_settings as $key => $value)
		updateSettings(array($key => $value));
}

$smcFunc['db_add_column']('{db_prefix}topics', array('name' => 'solved', 'type' => 'tinyint', 'size' => 3, 'default' => 0, 'unsigned' => true));	
	
$installed = $smcFunc['db_list_columns']('{db_prefix}topics');

add_integration_function('integrate_mod_buttons', 'add_topicsolved_button', true);
add_integration_function('integrate_admin_include', '$sourcedir/SolveTopic-Admin.php', true);
add_integration_function('integrate_modify_modifications', 'add_ts_settings_menu', true);
add_integration_function('integrate_admin_areas', 'add_ts_adminmenu', true);
add_integration_function('integrate_load_permissions', 'add_ts_permissions', true);

if (SMF == 'SSI')
{
	if (in_array('solved', $installed))
		echo 'Database edits completed!';
	else
		echo 'Database edits failed!';
}
	
?>