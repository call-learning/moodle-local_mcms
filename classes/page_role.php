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

namespace local_mcms;

defined('MOODLE_INTERNAL') || die();

/**
 * Class page
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_role extends \core\persistent {
    /**
     * Related table
     */
    const TABLE = 'local_mcms_page_roles';

    /**
     * Usual properties definition for a persistent
     *
     * @return array|array[]
     */
    protected static function define_properties() {
        return array(
            'pageid' => array(
                'type' => PARAM_INT,
                'default' => ''
            ),
            'roleid' => array(
                'type' => PARAM_INT,
                'default' => ''
            ),
        );
    }

    /**
     * Get all roles names
     *
     * @return array associative array of roles
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_all_roles_names() {
        global $DB;
        $options = $DB->get_records('role');
        $roles = [];
        foreach ($options as $p) {
            $roles[$p->id] = role_get_name($p);
        }
        return $roles;
    }
}

