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

require_once(__DIR__ . '/../../../../config.php');

global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');
require_login();
require_capability('local/mcms:managepages', context_system::instance());
admin_externalpage_setup('managepage');

// Get filter parameters.
$filtervalues = [];
foreach (\local_mcms\page_list::get_filter_definition() as $filtername => $filterdef) {
    $filtervalues[$filtername] = optional_param($filtername, $filterdef->default, $filterdef->type);
}

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 100, PARAM_INT);

// Override pagetype to show blocks properly.
$header = get_string('page:list', 'local_mcms');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl =
    new moodle_url($CFG->wwwroot . '/local/mcms/admin/page/list.php', $filtervalues + ['page' => $page, 'perpage' => $perpage]);

$PAGE->set_url($pageurl);
$buttonadd = new single_button(new moodle_url($CFG->wwwroot . '/local/mcms/admin/page/add.php'), get_string('add'));
$PAGE->set_button($OUTPUT->render($buttonadd));
$mform = new local_mcms\page_list_filter_form(null, $filtervalues);
$mform->set_data($filtervalues);

$filterdata = null;
if ($mform->get_data()) {
    $filterdata = $mform->get_data($pageurl, $page, $perpage, $filterdata);
}
$pagelist = new local_mcms\page_list_renderable();

$renderer = $PAGE->get_renderer('local_mcms');
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('page:list', 'local_mcms'), 3);

echo $mform->render();
echo $renderer->render($pagelist);
echo $OUTPUT->footer();
