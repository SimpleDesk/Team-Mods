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

	// If this is 2.1, we don't check admin_features
	if (function_exists('loadCacheAccelerator'))
		$admin_areas['maintenance']['areas']['logs']['subsections']['solvelog'] = array($txt['modlog_solve_log'], 'moderate_forum', 'enabled' => !empty($modSettings['enable_solved_log']), 'url' => $scripturl . '?action=moderate;area=solvelog');
	else
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
		array('check', 'enable_solved_log', 'disabled' => function_exists('loadCacheAccelerator') ? false : !in_array('ml', $context['admin_features'])),
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

/**
 *	Adds Topic Solved to our Moderation Log.
 *
 *	@param string &$listOptions The list options for our moderation log.
 *	@param string &$moderation_menu_name Normally empty, but we fill it here with our new menu link.
 *
 *	@since 2.0
*/
function integrate_viewModLog_solveTopic(&$listOptions, &$moderation_menu_name)
{
	global $context, $modSettings, $scripturl, $txt, $settings, $smcFunc;

	// Topic solved log
	$context['log_type'] = isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'solvelog' ? 4 : $context['log_type'];
	
	// Make sure the solve log is enabled.
	if ($context['log_type'] == 4 && empty($modSettings['enable_solved_log']))
		redirectexit('action=moderate');
	elseif ($context['log_type'] != 4)
		return false;

	// At this point, we are certain to be on the solved topic section.
	$context['page_title'] = $txt['modlog_solve_log'];
	$context['url_start'] = '?action=moderate;area=solvelog;type=4';

	// Fix up the tab data.
	$moderation_menu_name = array(
			'title' => $txt['modlog_solve_log'],
			'help' => 'solve_log',
			'description' => $txt['modlog_solve_log_desc']
	);

	// Fix up the list options.
	$listOptions['title'] = '<a href="' . $scripturl . '?action=helpadmin;help=solve_log_help" onclick="return reqWin(this.href);" class="help"><img src="' . $settings['images_url'] . '/helptopics.gif" alt="' . $txt['help'] . '" align="top" /></a> ' . $txt['modlog_solve_log'];
	$listOptions['additional_rows'][0]['value'] = '
					' . $txt['modlog_search'] . ' (' . $txt['modlog_by'] . ': ' . $context['search']['label'] . '):
					<input type="text" name="search" size="18" value="' . $smcFunc['htmlspecialchars']($context['search']['string']) . '">
					<input type="submit" name="is_search" value="' . $txt['modlog_go'] . '" class="button" style="float:none">
					' . ($context['can_delete'] ? '&nbsp;
					<input type="submit" name="remove_Solved" value="' . $txt['modlog_remove'] . '" data-confirm="' . $txt['modlog_remove_selected_confirm'] . '" class="button you_sure">
					<input type="submit" name="removeall_Solved" value="' . $txt['modlog_removeall'] . '" data-confirm="' . $txt['modlog_remove_all_confirm'] . '" class="button you_sure">' : '');

	$listOptions['no_items_label'] = $txt['modlog_solve_log_no_entries_found'];

	// List Urls.
	$listOptions['base_href'] = $scripturl . $context['url_start'] . (!empty($context['search_params']) ? ';params=' . $context['search_params'] : '');
	$listOptions['form']['href'] = $scripturl . $context['url_start'];

	// List Paramters.
	$listOptions['get_items']['params'][2] = $context['log_type'];
	$listOptions['get_count']['params'][2] = $context['log_type'];

	// Hold on, we have deletions to handle!
	// Handle deletion...
	if (isset($_POST['removeall_Solved']) && $context['can_delete'])
	{
		checkSession();
		validateToken('mod-ml');

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}log_actions
			WHERE id_log = {int:moderate_log}',
			array(
				'moderate_log' => $context['log_type'],
			)
		);

		logAction('clearlog_' . $context['log_type'], array(), $context['log_type']);
	}
	elseif (!empty($_POST['remove_Solved']) && isset($_POST['delete']) && $context['can_delete'])
	{
		checkSession();
		validateToken('mod-ml');

		// No sneaky removing the 'cleared the log' entries.
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}log_actions
			WHERE id_log = {int:moderate_log}
				AND id_action IN ({array_string:delete_actions})
				AND action NOT LIKE {string:clearlog}',
			array(
				'delete_actions' => array_unique($_POST['delete']),
				'moderate_log' => $context['log_type'],
				'clearlog' => 'clearlog_%',
			)
		);
	}
}

/**
 *	Adds Topic Solved to our Moderation Menu.
 *
 *	@param string &$menuData The menu data.
 *
 *	@since 1.1
*/
function integrate_moderate_areas_solveTopic(&$menuData)
{
	global $modSettings, $txt, $scripturl;

	$menuData['logs']['areas']['solvelog'] = array(
		'enabled' => !empty($modSettings['enable_solved_log']),
		'label' => $txt['modlog_solve_log'],
		'file' => 'Modlog.php',
		'function' => 'ViewModlog',
		'custom_url' => $scripturl . '?action=moderate;area=solvelog',
	);
}

?>