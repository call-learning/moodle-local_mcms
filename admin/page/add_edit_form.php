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

use local_mcms\menu\menu;
use local_mcms\page;
use local_mcms\page_role;
use local_mcms\page_utils;

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

    protected static $foreignfields = array('pageroles','image_filemanager');

    /**
     * The form definition.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title', get_string('page:title', 'local_mcms'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required');

        $mform->addHelpButton('title', 'page:title', 'local_mcms');
        $mform->addElement('text', 'shortname', get_string('page:shortname', 'local_mcms'));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', get_string('required'), 'required');
        $mform->addHelpButton('shortname', 'page:shortname', 'local_mcms');

        $mform->addElement('text', 'idnumber', get_string('page:idnumber', 'local_mcms'));
        $mform->setType('idnumber', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('idnumber', 'page:idnumber', 'local_mcms');

        $mform->addElement('editor', 'description', get_string('page:description', 'local_mcms'), null);
        $mform->setType('description', PARAM_CLEANHTML);
        $mform->addHelpButton('description', 'page:description', 'local_mcms');

        $imageoptions = $this->get_images_options();

        $mform->addElement('filemanager', 'image_filemanager', get_string('page:image','local_mcms'), null, $imageoptions);
        $mform->addHelpButton('image_filemanager', 'page:image', 'local_mcms');

        $styles = page_utils::get_template_styles_for_mcms();
        $mform->addElement('select', 'style', get_string('page:style', 'local_mcms'), $styles);
        $mform->addHelpButton('style', 'page:style', 'local_mcms');

        $mform->addElement('url', 'ctalink', get_string('page:ctalink', 'local_mcms'), $styles);
        $mform->addHelpButton('ctalink', 'page:ctalink', 'local_mcms');

        $allpages = page::get_records();
        $parents[0] = get_string('top');
        foreach ($allpages as $p) {
            $parents[$p->get('id')] = $p->get('title');
        }
        $mform->addElement('select', 'parent', get_string('page:parent', 'local_mcms'), $parents);
        $mform->addHelpButton('parent', 'page:parent', 'local_mcms');

        $roles = page_role::get_all_roles_names();
        $mform->addElement('searchableselector',
            'pageroles',
            get_string('page:roles', 'local_mcms'),
            $roles,
            array('multiple' => true));
        $mform->setType('pageroles', PARAM_RAW);
        $mform->addHelpButton('pageroles', 'page:roles', 'local_mcms');

        // This is to link the page to a top menu.
        $menus = menu::get_all_identifiable_menus();
        $mform->addElement('select', 'parentmenu', get_string('page:parentmenu', 'local_mcms'), $menus);
        $mform->addHelpButton('parentmenu', 'page:parentmenu', 'local_mcms');

        $styles = page_utils::get_template_styles_for_mcms();
        $mform->addElement('text', 'menusortorder', get_string('page:menusortorder', 'local_mcms'));
        $mform->addHelpButton('menusortorder', 'page:menusortorder', 'local_mcms');
        $mform->setType('menusortorder', PARAM_INT);

        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Get the default data.
     *
     * This is the data that is prepopulated in the form at it loads, we automatically
     * fetch all the properties of the persistent however some needs to be converted
     * to map the form structure.
     *
     * Extend this class if you need to add more conversion.
     *
     * @return stdClass
     */
    protected function get_default_data() {
        $data = parent::get_default_data();
        if ( !empty($this->_customdata['pageroles']))  {
            $data->pageroles = $this->_customdata['pageroles'];
        }
        if ( !empty($this->_customdata['image_filemanager']))  {
            $data->image_filemanager = $this->_customdata['image_filemanager'];
        }
        return $data;
    }

    /**
     * Get image options
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_images_options() {
        global $CFG;
        return array(
            'maxfiles' => 1,
            'maxbytes' => $CFG->maxbytes,
            'subdirs' => 0,
            'accepted_types' => 'web_image',
            'context' => context_system::instance()
        );
    }
}