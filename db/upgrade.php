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
 * Local plugin mcms - Upgrade plugin tasks
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for this plugin
 *
 * @param int $oldversion the version we are upgrading from
 * @return void
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_local_mcms_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2020092303) {

        // Define field parentmenu to be added to local_mcms_page.
        $table = new xmldb_table('local_mcms_page');
        $field = new xmldb_field('parentmenu', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'ctalink');

        // Conditionally launch add field parentmenu.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field menusortorder to be added to local_mcms_page.
        $table = new xmldb_table('local_mcms_page');
        $field = new xmldb_field('menusortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'parentmenu');

        // Conditionally launch add field menusortorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Mcms savepoint reached.
        upgrade_plugin_savepoint(true, 2020092303, 'local', 'mcms');
    }
    return true;
}