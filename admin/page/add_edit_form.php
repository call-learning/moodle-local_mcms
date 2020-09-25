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
 * page management add form
 *
 * @package     local_mcms
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
global $CFG;

/**
 * Add Form
 *
 * @package     local_mcms
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_edit_form extends core\form\persistent {

    /** @var string The fully qualified classname. */
    protected static $persistentclass = '\\local_mcms\\page';

    /** @var array Fields to remove when getting the final data. */
    protected static $fieldstoremove = array('submitbutton');

    protected static $foreignfields = array('pageroles');
    /**
     * The form definition.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $title = empty($this->_customdata['title']) ? '' : $this->_customdata['title'];
        $shortname = empty($this->_customdata['shortname']) ? '' : $this->_customdata['shortname'];
        $idnumber = empty($this->_customdata['idnumber']) ? '' : $this->_customdata['idnumber'];
        $pageroles = empty($this->_customdata['pageroles']) ? '' : $this->_customdata['pageroles'];
        $id = empty($this->_customdata['id']) ? '' : $this->_customdata['id'];
        if ($id) {
            $mform->addElement('hidden', 'id', $id);
            $mform->setType('id', PARAM_INT);
        }

        $mform->addElement('text', 'title', get_string('pagetitle', 'local_mcms'), $title);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required');

        $mform->addElement('text', 'shortname', get_string('pageshortname', 'local_mcms'), $shortname);
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', get_string('required'), 'required');

        $mform->addElement('text', 'idnumber', get_string('pageidnumber', 'local_mcms'), $idnumber);
        $mform->setType('idnumber', PARAM_ALPHANUMEXT);

        $options = $DB->get_records('role');
        $roles = [];
        foreach ($options as $p) {
            $roles[$p->id] = role_get_name($p);
        }
        $mform->addElement('searchableselector',
            'pageroles',
            get_string('pageroles', 'local_mcms'),
            $roles,
            array('multiple' => true));

        $mform->setDefault('pageroles', $pageroles);
        // Add parent page (for structure & menu).
        $this->add_action_buttons(true, get_string('save'));
    }
}