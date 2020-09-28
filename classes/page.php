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

namespace local_mcms;

defined('MOODLE_INTERNAL') || die();

/**
 * Class page
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page extends \core\persistent {
    const TABLE = 'local_mcms_page';

    public function __construct($id = 0, \stdClass $record = null) {
        global $CFG;
        $clonedrecord = $record;
        if ($record) {
            $clonedrecord = clone $record;
            if (isset($record->pageroles)) {
                unset($clonedrecord->pageroles);
            }
            if (isset($record->image_filemanager)) {
                unset($clonedrecord->image_filemanager);
            }
            if (isset($record->description) && (is_array($record->description))) {
                $clonedrecord->descriptionformat = $record->description['format'];
                $clonedrecord->description = $record->description['text'];
            }
        }
        parent::__construct($id, $clonedrecord);
    }

    /**
     * Get a page by its ID Number
     *
     * @param $idnumber
     * @return static
     * @throws \dml_exception
     */
    public static function get_record_by_idnumber($idnumber) {
        global $DB;
        $record  = $DB->get_record(self::TABLE, array('idnumber' => $idnumber));
        $persistents = new static(0, $record);
        return $persistents;
    }
    /**
     * Usual properties definition for a persistent
     *
     * @return array|array[]
     */
    protected static function define_properties() {
        return array(
            'title' => array(
                'type' => PARAM_TEXT,
                'default' => ''
            ),
            'shortname' => array(
                'type' => PARAM_TEXT,
                'default' => ''
            ),
            'idnumber' => array(
                'type' => PARAM_ALPHANUMEXT,
                'default' => ''
            ),
            'description' => array(
                'type' => PARAM_CLEANHTML,
                'default' => ''
            ),
            'descriptionformat' => array(
                'type' => PARAM_INT,
                'default' => ''
            ),
            'parent' => array(
                'type' => PARAM_INT,
                'default' => ''
            ),
            'style' => array(
                'type' => PARAM_ALPHANUMEXT,
                'default' => ''
            ),
            'ctalink' => array(
                'type' => PARAM_URL,
                'default' => ''
            ),
        );
    }

    /**
     * Get the page associated roles
     * This is the static version and avoid building a page object to get this information.
     *
     * @param $pageid
     * @return page_role[]
     */
    public static function get_page_roles($pageid) {
        $roles = page_role::get_records(['pageid' => $pageid]);
        return $roles;
    }

    /**
     * Get current page associated roles
     *
     * @return page_role[]
     * @throws \coding_exception
     */
    public function get_associated_roles() {
        return static::get_page_roles($this->get('id'));
    }

    /**
     * Update associated role
     *
     * @param $rolesid
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public function update_associated_roles($rolesid) {
        $this->delete_associated_roles();
        foreach ($rolesid as $rid) {
            $role = new page_role(0, (object) ['pageid' => $this->get('id'), 'roleid' => $rid]);
            $role->create();
        }
    }

    public function delete_associated_roles() {
        $roles = page_role::get_records(['pageid' => $this->get('id')]);
        foreach ($roles as $r) {
            $r->delete();
        }
    }

}
