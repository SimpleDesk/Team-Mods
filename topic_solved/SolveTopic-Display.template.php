<?php

if (!defined('SMF'))
	die('Hacking attempt...');

function template_topicsolved_header_above()
{
	global $txt;

	echo '<br /><div class="information">', $txt['topicsolved_is_solved'], '</div>';
}

function template_topicsolved_header_below()
{

}

// This is called from the hook in the display template. Since that's the case I didn't think it was inappropriate to put it here...
function add_topicsolved_button(&$mod_buttons)
{
	global $context, $scripturl;
	$mod_buttons = array_merge(array(
		'solve' => array('test' => 'can_solve', 'text' => empty($context['is_solved']) ? 'solve_topic' : 'unsolve_topic', 'image' => empty($context['is_solved']) ? 'solve.gif' : 'unsolve.gif', 'lang' => true, 'url' => $scripturl . '?action=solve;topic=' . $context['current_topic'] . '.' . $context['start'] . ';' . $context['session_var'] . '=' . $context['session_id']),
	), $mod_buttons);
}

?>