<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle Mini CMS utility.
 *
 * Provide the ability to manage site pages through blocks.
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_mcms\page;

require_once(__DIR__ . '/../../config.php');
$pageid = required_param('id', PARAM_INT);
$edit = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off

$page = new page($pageid);
if (!$page) {
    print_error('nomatchingpage', 'local_mcms');
}
$pagetitle = $page->get('title');
$context = context_system::instance();
$header = "$SITE->shortname: $pagetitle";
$PAGE->set_blocks_editing_capability('local/mcms:editpage');

// Start setting up the page.
$params = array('id' => $pageid);
$PAGE->set_context($context);
$PAGE->set_url('/local/mcms/index.php', $params);
$PAGE->set_pagelayout('standard');
$PAGE->set_pagetype('mcmspage');
$PAGE->blocks->add_region('content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($header);
$PAGE->set_subpage($pageid);

// Toggle the editing state and switches.
if ($PAGE->user_allowed_editing()) {
    if ($edit !== null) {             // Editing state was specified.
        $USER->editing = $edit;       // Change editing state.
    }
    if (!empty($USER->editing)) {
        $edit = 1;
    } else {
        $edit = 0;
    }
    // Add button for editing page.
    $params['edit'] = !$edit;
    $url = new moodle_url("$CFG->wwwroot/local/mcms/index.php", $params);
    $editactionstring = !$edit ? get_string('turneditingon') : get_string('turneditingoff');
    $editbutton = $OUTPUT->single_button($url, $editactionstring);


    $url = new moodle_url("$CFG->wwwroot/local/mcms/admin/page/list.php", $params);
    $pagelist = $OUTPUT->single_button($url, get_string('page:list', 'local_mcms'));

    $PAGE->set_button($pagelist . $editbutton);
} else {
    $USER->editing = $edit = 0;
}
$PAGE->blocks->set_default_region('content');
echo $OUTPUT->header();

echo $OUTPUT->custom_block_region('content');

echo $OUTPUT->footer();

// Trigger the page has been viewed event.
$eventparams = array('context' => $context, 'objectid' => $page->get('id'));
$event = local_mcms\event\page_viewed::create($eventparams);
$event->trigger();