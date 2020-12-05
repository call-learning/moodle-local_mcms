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
use local_mcms\form\add_edit_form;
use local_mcms\page;
use local_mcms\page_utils;

require_once(__DIR__ . '/../../../../config.php');

global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');
$contextsystem = context_system::instance();
require_login();
require_capability('local/mcms:managepages', $contextsystem);
admin_externalpage_setup('managepage');

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

$pagedata = [];
$pagedata['persistent'] = $page;

// Get associated roles.
$pagedata['pageroles'] = array_map(function($r) {
    return $r->get('roleid');
}, $page->get_associated_roles());

// Get previously loaded image.
$imagedraftitemid = file_get_submitted_draft_itemid('image_filemanager');
file_prepare_draft_area($imagedraftitemid,
    $contextsystem->id,
    page_utils::PLUGIN_FILE_COMPONENT,
    page_utils::PLUGIN_FILE_AREA_IMAGE,
    $id,
    add_edit_form::get_images_options());
$pagedata['image_filemanager'] = $imagedraftitemid;

$mform = new add_edit_form(null, $pagedata);

// Get standard editor files.

$draftideditor = file_get_submitted_draft_itemid('description');
$currendescription = file_prepare_draft_area($draftideditor,
    context_system::instance()->id,
    page_utils::PLUGIN_FILE_COMPONENT,
    page_utils::PLUGIN_FILE_AREA_DESCRIPTION,
    $page->get('id'),
    add_edit_form::get_description_editor_options(),
    $page->get('description')
);
$pagedata = $page->to_record();
$pagedata->description = [
    'text' => $currendescription,
    'format' => $page->get('descriptionformat'),
    'itemid' => $draftideditor
];
$mform->set_data($pagedata);

$listpageurl = new moodle_url($CFG->wwwroot . '/local/mcms/admin/page/list.php');
if ($mform->is_cancelled()) {
    redirect($listpageurl);
}
$errornotification = '';
if ($data = $mform->get_data()) {
    try {
        $returnurl = page_utils::page_add_edit($data, false);
        redirect($listpageurl,
            get_string('pageinfoupdated', 'local_mcms'),
            null,
            $messagetype = \core\output\notification::NOTIFY_SUCCESS);
    } catch (moodle_exception $e) {/**/
        $errornotification = $OUTPUT->notification($e->getMessage(), 'notifyfailure');
    }
}
echo $OUTPUT->header();
echo $errornotification;
$mform->display();
echo $OUTPUT->footer();
