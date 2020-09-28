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

require_once(__DIR__ . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once('add_edit_form.php');
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
$imagedraftitemid = file_get_submitted_draft_itemid('image_filemanager');
file_prepare_draft_area($imagedraftitemid,
    $contextsystem->id,
    \local_mcms\page_utils::PLUGIN_FILE_COMPONENT,
    \local_mcms\page_utils::PLUGIN_FILE_AREA_IMAGE,
    $id,
    add_edit_form::get_images_options());
$pagedata['image_filemanager'] = $imagedraftitemid;

$mform = new add_edit_form(null, $pagedata);

$listpageurl = new moodle_url($CFG->wwwroot . '/local/mcms/admin/page/list.php');
if ($mform->is_cancelled()) {
    redirect($listpageurl);
}
$errornotification = '';
if ($data = $mform->get_data()) {
    try {
        $page = new page($id, $data);
        $page->update();
        $page->update_associated_roles($data->pageroles);
        $data = file_postupdate_standard_filemanager($data,
            'image',
            $mform->get_images_options(),
            $contextsystem,
            \local_mcms\page_utils::PLUGIN_FILE_COMPONENT,
            \local_mcms\page_utils::PLUGIN_FILE_AREA_IMAGE,
            $id);
        $action = get_string('pageinfoupdated', 'local_mcms');
        $eventparams = array('objectid' => $page->get('id'),
            'context' => context_system::instance(),
            'other' => array(
                'actions' => $action
            ));
        $event = page_updated::create($eventparams);
        $event->trigger();
        /* @var core_renderer $OUTPUT */
        redirect($listpageurl,
            get_string('pageinfoupdated', 'local_mcms'),
            null,
            $messagetype = \core\output\notification::NOTIFY_SUCCESS);
    } catch (moodle_exception $e) {
        $errornotification = $OUTPUT->notification($e->getMessage(), 'notifyfailure');
    }
}
echo $OUTPUT->header();
echo $errornotification;
$mform->display();
echo $OUTPUT->footer();
