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
 * Page List filter form
 *
 * @package     local_mcms
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mcms;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Filter selection form
 *
 * @package     local_mcms
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_list_filter_form extends \moodleform {

    /**
     * The form definition.
     */
    public function definition() {
        $mform = $this->_form;

        foreach (\local_mcms\page_list::get_filter_definition() as $filtername => $filterdef) {
            $default = empty($this->_customdata[$filtername]) ? $filterdef->default : $this->_customdata[$filtername];
            switch ($filterdef->type) {
                default:
                    if (!empty($filterdef->choices)) {
                        $mform->addElement('select', $filtername, get_string('pagefilter:' . $filtername, 'local_mcms'), $filterdef->choices);
                        $mform->setType($filtername, $filterdef->type);
                    } else {
                        $mform->addElement('text', $filtername, get_string('pagefilter:' . $filtername, 'local_mcms'), $default);
                        $mform->setType($filtername, $filterdef->type);
                    }
            }
        }
        // Add parent page (for structure & menu).
        $buttonarray[] = &$mform->createElement('submit', 'search',
            get_string('search'));
        $buttonarray[] = $mform->createElement('reset', 'clear', get_string('clear'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}