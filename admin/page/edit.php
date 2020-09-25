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


use local_mcms\event\page_updated;
use local_mcms\page;
use local_mcms\page_exception;

require_once(__DIR__ . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once('add_edit_form.php');

admin_externalpage_setup('managepage');
require_login();
$id = required_param('id', PARAM_INT);

// Override pagetype to show blocks properly.
$header = get_string('page:edit', 'local_mcms');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/mcms/admin/page/edit.php');
$PAGE->set_url($pageurl);
// Navbar.
$listpageurl = new moodle_url($CFG->wwwroot . '/local/mcms/admin/page/list.php');
$PAGE->navbar->add(get_string('page:list', 'local_mcms'), new moodle_url($listpageurl));
$PAGE->navbar->add($header, null);

$page = new page($id);

$pagedata = (array) $page;
$mform = new add_edit_form(null, $pagedata);
$mform->set_data($pagedata);

$listpageurl = new moodle_url($CFG->wwwroot . '/local/mcms/admin/page/list.php');
if ($mform->is_cancelled()) {
    redirect($listpageurl);
}

echo $OUTPUT->header();
if ($data = $mform->get_data()) {
    $pagedata = clone $data;
    unset($pagedata->pageroles);
    $page = new page($pagedata->id, $pagedata);
    $page->update();
    $action = get_string('pageinfoupdated', 'local_mcms');
    $eventparams = array('objectid' => $page->id, 'context' => context_system::instance(), 'other' => array(
        'actions' => $action
    ));
    $event = page_updated::create($eventparams);
    $event->trigger();
    echo $OUTPUT->notification($action, 'notifysuccess');
    echo $OUTPUT->single_button($listpageurl, get_string('continue'));
} else {
    $mform->display();
}
echo $OUTPUT->footer();
