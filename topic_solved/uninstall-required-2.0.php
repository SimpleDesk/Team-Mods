<?php
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF')) // If we are outside SMF and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot uninstall - please verify you put this file in the same place as SMF\'s SSI.php.');

// We need to clean out things like hooks, and we must do this on an uninstall regardless of anything else.

remove_integration_function('integrate_mod_buttons', 'add_topicsolved_button');
remove_integration_function('integrate_admin_include', '$sourcedir/SolveTopic-Admin.php');
remove_integration_function('integrate_modify_modifications', 'add_ts_settings_menu');
remove_integration_function('integrate_admin_areas', 'add_ts_adminmenu');
remove_integration_function('integrate_load_permissions', 'add_ts_permissions');

if (SMF == 'SSI')
{
	if (in_array('solved', $installed))
		echo 'Basic database changes edits removed!';
	else
		echo 'Database edits failed!';
}
?>