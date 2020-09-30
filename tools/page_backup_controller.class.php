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
 * @package moodlecore
 * @subpackage backup-controller
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class implementing the controller of any backup process for pages
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

class page_backup_controller extends backup_controller {

    /**
     * Constructor for the backup controller class.
     *
     * @param int $type Type of the backup; ignored as it is a different backup than usual
     * @param int $id The ID of the item to backup; e.g the page id
     * @param int $format The backup format to use; Most likely backup::FORMAT_MOODLE
     * @param bool $interactive Whether this backup will require user interaction; backup::INTERACTIVE_YES or INTERACTIVE_NO
     * @param int $mode One of backup::MODE_GENERAL, MODE_IMPORT, MODE_SAMESITE, MODE_HUB, MODE_AUTOMATED
     * @param int $userid The id of the user making the backup
     * @param bool $releasesession Should release the session? backup::RELEASESESSION_YES or backup::RELEASESESSION_NO
     */
    public function __construct($type, $id, $format, $interactive, $mode, $userid, $releasesession = backup::RELEASESESSION_NO) {
        $this->type = $type;
        $this->id   = $id;
        $this->courseid = SITEID; // Hack so security checks passes.
        $this->format = $format;
        $this->interactive = $interactive;
        $this->mode = $mode;
        $this->userid = $userid;
        $this->releasesession = $releasesession;

        // Apply some defaults.
        $this->operation = backup::OPERATION_BACKUP;
        $this->executiontime = 0;
        $this->checksum = '';

        // Set execution based on backup mode.
        if ($mode == backup::MODE_ASYNC || $mode == backup::MODE_COPY) {
            $this->execution = backup::EXECUTION_DELAYED;
        } else {
            $this->execution = backup::EXECUTION_INMEDIATE;
        }

        // Apply current backup version and release if necessary.
        backup_controller_dbops::apply_version_and_release();

        // Check format and type are correct.
        backup_check::check_format_and_type($this->format, $this->type);

        // Check id is correct.
        $this->check_id($this->id);

        // Check user is correct.
        backup_check::check_user($this->userid);

        // Calculate unique $backupid.
        $this->calculate_backupid();

        // Default logger chain (based on interactive/execution).
        $this->logger = backup_factory::get_logger_chain($this->interactive, $this->execution, $this->backupid);

        // By default there is no progress reporter. Interfaces that wish to
        // display progress must set it.
        $this->progress = new \core\progress\none();

        // Instantiate the output_controller singleton and active it if interactive and immediate.
        $oc = output_controller::get_instance();
        if ($this->interactive == backup::INTERACTIVE_YES && $this->execution == backup::EXECUTION_INMEDIATE) {
            $oc->set_active(true);
        }

        $this->log('instantiating backup controller', backup::LOG_INFO, $this->backupid);

        // Default destination chain (based on type/mode/execution).
        $this->destination = backup_factory::get_destination_chain($this->type, $this->id, $this->mode, $this->execution);

        // Set initial status.
        $this->set_status(backup::STATUS_CREATED);

        // Load plan (based on type/format).
        $this->load_plan();

        // Apply all default settings (based on type/format/mode).
        $this->apply_defaults();

        // Perform all initial security checks and apply (2nd param) them to settings automatically.
        backup_check::check_security($this, true);

        // Set status based on interactivity.
        if ($this->interactive == backup::INTERACTIVE_YES) {
            $this->set_status(backup::STATUS_SETTING_UI);
        } else {
            $this->set_status(backup::STATUS_AWAITING);
        }
    }

    /**
     * Check identifier
     *
     * @param $id
     * @return bool
     * @throws dml_exception
     */
    public function check_id($id) {
        global $DB;
        if (!$DB->record_exists('local_mcms_page', array('id' => $id))) {
            throw new backup_controller_exception('backup_check_module_not_exists', $id);
        }

        return true;
    }

    protected function load_plan() {
        $this->log('loading controller plan', backup::LOG_DEBUG);
        $this->plan = new page_backup_plan($this);
        $this->plan->build(); // Build plan for this controller
        $this->set_status(backup::STATUS_PLANNED);
    }

}


class page_backup_plan extends backup_plan {
    public function build() {
        global $DB;

        // Add the root task, responsible for storing global settings
        // and some init tasks
        $this->add_task(new backup_root_task('root_task'));

        // Get all block from the given page.

        // Get the context of the course
        $contextid = context_system::instance()->id;

        // Get all the block instances for the given page id.
        $instances = $DB->get_records(
            'block_instances',
            array('parentcontextid' => $contextid, 'pagetypepattern'=>'mcmspage','subpagepattern'=>$this->controller->get_id()),
            '', 'id');
        foreach ($instances as $instance) {
            $this->add_task(backup_factory::get_backup_block_task($this->controller->get_format(), $instance->id));
        }

        $this->add_task(new backup_mcmspage_task($this->controller->get_id(), $this));
        // Add the final task, responsible for outputting
        // all the global xml files (groups, users,
        // gradebook, questions, roles, files...) and
        // the main moodle_backup.xml file
        // and perform other various final actions.
        $this->add_task(new backup_final_task('final_task'));
        $this->built = true;
    }
}

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
 * @package     core_backup
 * @subpackage  moodle2
 * @category    backup
 * @copyright   2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class backup_mcmspage_task
 */
class backup_mcmspage_task extends backup_task {

    protected $pageid;

    /**
     * Constructor - instantiates one object of this class
     */
    public function __construct($pageid, $plan=null) {
        global $DB;

        // Check pageid exists.
        if (!$page = $DB->get_record('local_mcms_page', array('id' => $pageid))) {
            throw new backup_task_exception('block_mcmspage_task_instance_not_found', $pageid);
        }

        $this->pageid = $pageid;
        parent::__construct('local_mcms', $plan);
    }

    public function get_pageid() {
        return $this->pageid;
    }

    /**
     * Block tasks have their own directory to write files
     */
    public function get_taskbasepath() {
        $basepath = $this->get_basepath();
        $basepath .= '/mcmspage/'.$this->pageid;
        return $basepath;
    }

    /**
     * Create all the steps that will be part of this task
     */
    public function build() {

        // Create the block directory.
        $this->add_step(new create_taskbasepath_directory('create_mcmspage_directory'));

        // Create the block.xml common file (instance + positions)
        $this->add_step(new backup_page_instance_structure_step('page_commons', 'page.xml'));

        // Generate the roles file (optionally role assignments and always role overrides)
        $this->add_step(new backup_roles_structure_step('page_roles', 'roles.xml'));

        // At the end, mark it as built
        $this->built = true;
    }

    // Protected API starts here

    /**
     * Define the common setting that any backup block will have
     */
    protected function define_settings() {
    }

    /**
     * Define one array() of fileareas that each block controls
     */
    public function get_fileareas() {
        return array('images');
    }

    /**
     * Code the transformations to perform in the block in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        return $content;
    }
}

/**
 * structure step in charge of constructing the block.xml file for one
 * given block (instance and positions). If the block has custom DB structure
 * that will go to a separate file (different step defined in block class)
 */
class backup_page_instance_structure_step extends backup_structure_step {

    protected function define_structure() {
        global $DB;

        // Define each element separated

        $block = new backup_nested_element('page', array('id', 'contextid', 'version'), array(
            'blockname', 'parentcontextid', 'showinsubcontexts', 'pagetypepattern',
            'subpagepattern', 'defaultregion', 'defaultweight', 'configdata',
            'timecreated', 'timemodified'));

        $positions = new backup_nested_element('block_positions');

        $position = new backup_nested_element('block_position', array('id'), array(
            'contextid', 'pagetype', 'subpage', 'visible',
            'region', 'weight'));

        // Build the tree

        $block->add_child($positions);
        $positions->add_child($position);

        // Transform configdata information if needed (process links and friends)
        $blockrec = $DB->get_record('block_instances', array('id' => $this->task->get_blockid()));
        if ($attrstotransform = $this->task->get_configdata_encoded_attributes()) {
            $configdata = (array)unserialize(base64_decode($blockrec->configdata));
            foreach ($configdata as $attribute => $value) {
                if (in_array($attribute, $attrstotransform)) {
                    $configdata[$attribute] = $this->contenttransformer->process($value);
                }
            }
            $blockrec->configdata = base64_encode(serialize((object)$configdata));
        }
        $blockrec->contextid = $this->task->get_contextid();
        // Get the version of the block
        $blockrec->version = get_config('block_'.$this->task->get_blockname(), 'version');

        // Define sources

        $block->set_source_array(array($blockrec));

        $position->set_source_table('block_positions', array('blockinstanceid' => backup::VAR_PARENTID));

        // File anotations (for fileareas specified on each block)
        foreach ($this->task->get_fileareas() as $filearea) {
            $block->annotate_files('block_' . $this->task->get_blockname(), $filearea, null);
        }

        // Return the root element (block)
        return $block;
    }
}
