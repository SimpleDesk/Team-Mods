<?php

if (!defined('SMF'))
	die('Hacking attempt...');

function add_ts_settings_menu(&$subActions)
{
	$subActions['topicsolved'] = 'ModifyTopicSolvedSettings';
}

function add_ts_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	$permissionList['board']['solve_topic'] = array(true, 'topic', 'moderate', 'moderate');
}

function add_ts_adminmenu(&$admin_areas)
{
	global $txt, $context, $modSettings, $scripturl;
	$admin_areas['maintenance']['areas']['logs']['subsections']['solvelog'] = array($txt['modlog_solve_log'], 'moderate_forum', 'enabled' => !empty($modSettings['enable_solved_log']) && in_array('ml', $context['admin_features']), 'url' => $scripturl . '?action=moderate;area=modlog;sa=solvelog');
	$admin_areas['config']['areas']['modsettings']['subsections']['topicsolved'] = array($txt['topic_solved_title']);
}

function ModifyTopicSolvedSettings($return_config = false)
{
	global $txt, $scripturl, $context, $settings, $sc, $modSettings, $smcFunc;
	
	$query = $smcFunc['db_query']('', '
		SELECT id_board, id_cat, child_level, name FROM {db_prefix}boards ORDER BY board_order ASC
	');
	
	$config_vars = array();
	$last = -1;
	
	$config_vars = array(
		array('check', 'enable_solved_log', 'disabled' => !in_array('ml', $context['admin_features'])),
		array('check', 'topicsolved_highlight'),
		array('text', 'topicsolved_highlight_col1', 'size' => 10, 'disabled' => empty($modSettings['topicsolved_highlight'])),
		array('text', 'topicsolved_highlight_col2', 'size' => 10, 'disabled' => empty($modSettings['topicsolved_highlight'])),
		array('check', 'topicsolved_display_notice'),
		'',
		array('message', 'topicsolved_board_desc'),
	);

	while ($row = $smcFunc['db_fetch_assoc']($query)) {
		if ($row['id_cat'] != $last && $last != -1)
			$config_vars[] = '';

		$board_id = 'topicsolved_board_' . $row['id_board'];
		$txt[$board_id] = (($row['child_level'] > 0) ? str_repeat('&nbsp; &nbsp; ', $row['child_level']) : '') . $row['name'];
		$config_vars[] = array('check', $board_id);
		$last = $row['id_cat'];
	}
	
	$smcFunc['db_free_result']($query);	

	if ($return_config)
		return $config_vars;

	$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=topicsolved';
	$context['settings_title'] = $txt['topic_solved_title'];

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		$save_vars = $config_vars;
		saveDBSettings($save_vars);
		
		redirectexit('action=admin;area=modsettings;sa=topicsolved');
	}

	prepareDBSettingContext($config_vars);
}

?>