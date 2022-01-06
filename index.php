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
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_mcms\page;

require_once(__DIR__ . '/../../config.php');
global $SITE, $CFG, $PAGE, $USER, $OUTPUT;

$pageid = optional_param('id', null, PARAM_INT);
$pageidnumber = optional_param('p', null, PARAM_ALPHANUMEXT);
$edit = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off.

$page = null;
if (!$pageid && $pageidnumber) {
    if (!$pageidnumber) {
        throw new moodle_exception('pageoridnumbermssing', 'local_mcms');
    }
    $page = page::get_record_by_idnumber($pageidnumber);
    $pageid = $page->get('id');
} else {
    $page = new page($pageid);
}

if (!$page) {
    throw new moodle_exception('nomatchingpage', 'local_mcms');
}
$context = context_system::instance();

$canviewpage = true;
if (page::must_login($USER, $page)) {
    require_login();
    $canviewpage = page::can_view_page($USER, $page, $context);
}

// Check user can view the page.
if (!$canviewpage) {
    throw new moodle_exception('cannotviewpage', 'local_mcms');
}
$pagetitle = $page->get('title');
$header = "$SITE->shortname: $pagetitle";
$PAGE->set_blocks_editing_capability('local/mcms:editpage');

// Start setting up the page.
$params = array('id' => $pageid);
$PAGE->set_context($context);
$PAGE->set_url('/local/mcms/index.php', $params);
$layout = "standard";
if (key_exists(\local_mcms\page_utils::PAGE_LAYOUT_NAME, $PAGE->theme->layouts)) {
    $layout = \local_mcms\page_utils::PAGE_LAYOUT_NAME;
}
$PAGE->set_pagelayout($layout);
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

    $params = ['id' => $pageid];
    $url = new moodle_url("$CFG->wwwroot/local/mcms/admin/page/edit.php", $params);
    $edit = $OUTPUT->single_button($url, get_string('edit'));
    $PAGE->set_button($pagelist . $edit . $editbutton);
} else {
    $USER->editing = $edit = 0;
}
$PAGE->blocks->set_default_region('content');

$renderer = $PAGE->get_renderer('local_mcms');

echo $renderer->header();

// The theme does not take care of the layout, so we still display the header.
if ($layout != \local_mcms\page_utils::PAGE_LAYOUT_NAME) {
    $pageheaderenderable = new \local_mcms\output\pageheader\pageheader($page);
    $pageheaderrenderer = $PAGE->get_renderer('local_mcms', 'pageheader');

    echo $pageheaderrenderer->render($pageheaderenderable);
}

echo $renderer->custom_block_region('content');

echo $renderer->footer();

// Trigger the page has been viewed event.
$eventparams = array('context' => $context, 'objectid' => $pageid);
$event = local_mcms\event\page_viewed::create($eventparams);
$event->trigger();
