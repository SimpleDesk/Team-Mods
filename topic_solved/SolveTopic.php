<?php
/**********************************************************************************
* SolveTopic.php                                                                                                                                                                              *
***********************************************************************************
* Topic Solved mod for SMF 2.0, by SimpleDesk - www.simpledesk.net                                                                            *
**********************************************************************************/

if (!defined('SMF'))
	die('Hacking attempt...');

/*	This file has but one purpose - Solving and unsolving topics.

	void SolveTopic()
		- solves a topic, toggles between solved/not solved.
		- requires the solve_own or solve_any permission.
		- logs the action to the moderator log.
		- returns to the topic after it is done.
		- accessed via ?action=solve.

*/

/**
 *	The heavy lifting for solving a topic, all requests go through here and are validated and logged (if enabled).
 *
 *	@param string &$actionArray The master list of actions from index.php
 *
 *	@since 1.0
*/
function SolveTopic()
{
	global $topic, $user_info, $sourcedir, $board, $smcFunc, $modSettings;

	// See if its enabled in this board.
	$solve_boards = array();
	$boardsettings = array_keys($modSettings);
	foreach($boardsettings as $setting) {
		if(substr($setting, 0, 18) == 'topicsolved_board_' && $modSettings[$setting] == 1)
			$solve_boards[] = str_replace('topicsolved_board_','',$setting);
	}	
	if(!in_array($board,$solve_boards))
		fatal_lang_error('topicsolved_not_enabled', false);		
	
	// You need a topic to solve.
	if (empty($topic))
		fatal_lang_error('not_a_topic', false);

	checkSession('get');

	// Get the topic owner.
	$request = $smcFunc['db_query']('', '
		SELECT id_member_started, solved, id_first_msg
		FROM {db_prefix}topics
		WHERE id_topic = {int:current_topic}
		LIMIT 1',
		array(
			'current_topic' => $topic,
		)
	);
	list ($starter, $solved, $firstmsg) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Youcansolvez?
	$user_solve = !allowedTo('solve_topic_any');
	if ($user_solve && $starter == $user_info['id'])
		isAllowedTo('solve_topic_own');
	else
		isAllowedTo('solve_topic_any');

	// Mark the topic solved in the database. Simple enough.
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}topics
		SET solved = {int:solved_status}
		WHERE id_topic = {int:current_topic}',
		array(
			'current_topic' => $topic,
			'solved_status' => empty($solved) ? 1 : 0,
		)
	);

	// Also change the message icon.
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}messages
		SET icon = {string:icon}
		WHERE id_msg = {int:message}',
		array(
			'message' => $firstmsg,
			'icon' => empty($solved) ? 'solved' : 'xx',
		)
	);	
	
	// Log this solve. if enabled.
	if(!empty($modSettings['enable_solved_log']))
		logAction(empty($solved) ? 'solve' : 'unsolve', array('topic' => $topic, 'board' => $board, 'member' => $starter),'solve');

	// Let's go back home.
	redirectexit('topic=' . $topic . '.' . $_REQUEST['start'] . (WIRELESS ? ';moderate' : ''));
}

/**
 *	Adds solveTopic to the Action Array.
 *
 *	@param string &$actionArray The master list of actions from index.php
 *
 *	@since 1.1
*/
function integrate_actions_solveTopic(&$actionArray)
{
	$actionArray['solve'] = array('SolveTopic.php', 'SolveTopic');
}

/**
 *	Adds Topic Solved to the Display Buttons.
 *
 *	@param string &$buttons The "normal" buttons, doesn't work.
 *
 *	@since 1.1
*/
function integrate_display_buttons_solveTopic(&$buttons)
{
	global $modSettings, $context, $board, $scripturl;

	// Can you solve this?
	$context['can_solve'] = allowedTo('solve_topic_any') || ($context['user']['started'] && allowedTo('solve_topic_own'));
		
	// Topic solved stuff. Is this one of THE boards?
	loadTemplate('SolveTopic-Display');
	$context['board_solve'] = !empty($modSettings['topicsolved_board_' . $board]);
	$context['can_solve'] &= $context['board_solve'];

	// Support SMF 2.0.
	if (!isset($context['topicinfo']['solved']) && isset($context['is_solved']))
		$context['topicinfo']['solved'] = $context['is_solved'];

	if (!empty($modSettings['topicsolved_display_notice']) && $context['topicinfo']['solved'] && $context['board_solve'])
		$context['template_layers'][] = 'topicsolved_header';

	$context['mod_buttons']['solve'] = array(
		'test' => 'can_solve',
		'text' => empty($context['topicinfo']['solved']) ? 'solve_topic' : 'unsolve_topic',
		'image' => empty($context['topicinfo']['solved']) ? 'solve.gif' : 'unsolve.gif',
		'lang' => true,
		'url' => $scripturl . '?action=solve;topic=' . $context['current_topic'] . '.' . $context['start'] . ';' . $context['session_var'] . '=' . $context['session_id']
	);
}

/**
 *	Adds Topic Solved column to the Display Query.
 *
 *	@param string &$topic_selects The selects to append to the query.
 *	@param string &$topic_tables The tables to append to the query.
 *	@param string &$topic_parameters The parameters to append to the query.
 *
 *	@since 1.1
*/
function integrate_display_topic_solveTopic(&$topic_selects, &$topic_tables, &$topic_parameters)
{
	$topic_selects[] = 't.solved';
}

/**
 *	Adds Topic Solved to our Actionn Log as its own section.
 *
 *	@param string &$log_types The log types.
 *
 *	@since 1.1
*/
function integrate_log_types_solveTopic(&$log_types)
{
	$log_types['solve'] = 4;
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

	$menuData['main']['areas']['solvelog'] = array(
		'enabled' => !empty($modSettings['enable_solved_log']),
		'label' => $txt['modlog_solve_log'],
		'file' => 'Modlog.php',
		'function' => 'ViewModlog',
		'custom_url' => $scripturl . '?action=moderate;area=modlog;sa=solvelog',
	);
}

/**
 *	Adds Topic Solved column to the Message Index Query.
 *
 *	@param string &$message_index_selects The selects to append to the query.
 *	@param string &$message_index_tables The tables to append to the query.
 *	@param string &$message_index_parameters The parameters to append to the query.
 *
 *	@since 1.1
*/
function integrate_message_index_solveTopic(&$message_index_selects, &$message_index_tables, &$message_index_parameters)
{
	$message_index_selects[] = 't.solved';
}

/**
 *	We don't actually add any buttons to the message index, instead we use this to loop through the topics context and set it up.
 *
 *	@param string &$buttons The "normal" buttons.
 *
 *	@since 1.1
*/
function integrate_messageindex_buttons_solveTopic(&$buttons)
{
	global $context, $modSettings, $board;

	// Is this board solvable?
	$context['board_solve'] = !empty($modSettings['topicsolved_board_' . $board]);

	// Loop through all topics and set is solved.
	foreach ($context['topics'] as $id_topic => $topic_data)
	{
		$context['topics'][$topic_data['id']]['is_solved'] = $context['board_solve'] && !empty($topic_data['solved']);

		// Is it solved?
		if ($context['topics'][$topic_data['id']]['is_solved'])
			$context['topics'][$topic_data['id']]['css_class'] = 'solvedbg';
	}

	// Do we have a custom CSS?
	if (!empty($modSettings['topicsolved_highlight']) && !empty($modSettings['topicsolved_highlight_col1']) && !empty($modSettings['topicsolved_highlight_col2']))
		addInlineCss('/* Topic solved */ .solvedbg:nth-of-type(even) { background:' . $modSettings['topicsolved_highlight_col1'] . '; } .solvedbg:nth-of-type(odd) { background:' . $modSettings['topicsolved_highlight_col2'] . '; }');
}

/**
 *	Add Topic solved to the stable icons list.
 *
 *	@since 1.1
*/
function integrate_pre_load_solveTopic()
{
	global $context;

	if (SMF == 'SSI')
		$context['stable_icons'][] = 'solved';
}

?>