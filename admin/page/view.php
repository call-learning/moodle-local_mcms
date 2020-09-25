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
use local_mcms\page_exception;


require_once(__DIR__ . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('managepage');
require_login();
$id = required_param('id', PARAM_INT);
$page = new page($id);

// Override pagetype to show blocks properly.
$header = get_string('page:view', 'local_mcms', $page->shortname);
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/mcms/admin/page/view.php');
$PAGE->set_url($pageurl);
// Navbar.
$listpageurl = new moodle_url($CFG->wwwroot . '/local/mcms/admin/page/list.php');
$PAGE->navbar->add(get_string('page:list', 'local_mcms'), new moodle_url($listpageurl));
$PAGE->navbar->add($header, null);

$page->load_data();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pageviewtitle', 'local_mcms', $page->fullname), 3);
$table = new html_table();
$table->attributes['class'] = 'generaltable boxaligncenter flexible-wrap';
$uenames = array_map(function($ue) {
    return $ue->fullname;
}, $page->get_page_ues());
$arrayheader = array(get_string('competencies', 'local_mcms'),
    get_string('competencyfullname', 'local_mcms'));
$arrayheader = array_merge($arrayheader, $uenames);
$table->head = $arrayheader;

$competencies = $page->get_page_competencies();
foreach ($competencies as $comp) {

    $cells = array(new html_table_cell($comp->shortname), new html_table_cell($comp->fullname));
    foreach ($page->get_page_ues() as $ue) {
        $values = array();
        foreach ($page->get_values_for_ue_and_competency($ue->id, $comp->id) as $compsue) {
            $values[] = page::comptype_to_string($compsue->type) . ':' . $compsue->value;
        }
        $cells[] = new html_table_cell(html_writer::alist($values));
    }
    $table->data[] = new html_table_row($cells);
}
echo html_writer::table($table);

echo $OUTPUT->footer();
