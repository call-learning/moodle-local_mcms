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
 * CLI script allowing to backup page and attached blocks
 *
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
global $CFG;
require_once($CFG->libdir.'/clilib.php');

$usage = "Backup a given page

Usage:
    # php backup.php --pageid=1

Options:
    -h --help                   Print this help.
    --pageid=<value>            Page identifier (database identifier)
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'pageid' => null,
    'destination' => '/tmp/'
], [
    'h' => 'help'
]);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL.'  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}

// Do we need to store backup somewhere else?
$dir = rtrim($options['destination'], '/');
if (empty($dir) || !file_exists($dir) || !is_dir($dir) || !is_writable($dir)) {
    cli_error(get_string('directoryerror', 'local_mcms'));
}

if ($options['pageid']) {
    global $USER;
    $adminuser = get_admin();
    $pageid = $options['pageid'];
    require_once($CFG->dirroot.'/local/mcms/tools/pages_backup_controller.class.php');

    $bc = new pages_backup_controller(backup::INTERACTIVE_YES, backup::MODE_GENERAL, $adminuser->id);
    $format = $bc->get_format();
    $type = $bc->get_type();
    $id = $bc->get_id();
    $users = $bc->get_plan()->get_setting('users')->get_value();
    $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
    $filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
    $bc->get_plan()->get_setting('filename')->set_value($filename);

    $bc->finish_ui();
    $bc->execute_plan();
    $results = $bc->get_results();
    $file = $results['backup_destination']; // May be empty if file already moved to target location.

    // Do we need to store backup somewhere else?
    if ($file) {
        if ($file->copy_content_to($dir.'/'.$filename)) {
            $file->delete();
        } else {
            mtrace(get_string('directoryerror', 'tool_brcli'));
        }
    }
    $bc->destroy();
}
