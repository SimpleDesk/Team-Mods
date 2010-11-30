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

// Mark a topic solved.
function SolveTopic()
{
	global $topic, $user_info, $sourcedir, $board, $smcFunc, $modSettings;

	// See if its enabled in this board.
	if (empty($modSettings['topicsolved_board_' . $board]))
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
	$user_solve = !allowedTo('solve_topic_any', $board);
	if ($user_solve && $starter == $user_info['id'])
		isAllowedTo('solve_own', $board);
	else
		isAllowedTo('solve_any', $board);

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

?>