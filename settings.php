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
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $mcmsmanagement = new admin_category(
        'mcmspagemanagement',
        get_string('pagemanagement', 'local_mcms')
    );

    // General settings.
    $pagedesc = get_string('mcmsgeneralsettings', 'local_mcms');
    $generalsettingspage = new admin_settingpage('mcmsgeneral',
        $pagedesc,
        ['local/mcms:managepages'],
        empty($CFG->enablemcms));

    $generalsettingspage->add(
        new admin_setting_configtextarea('local_mcms/rootmenuitems', new lang_string('rootmenuitems', 'local_mcms'),
            new lang_string('rootmenuitems_help', 'local_mcms'), '', PARAM_RAW, '50', '10')
    );
    $mcmsmanagement->add('mcmspagemanagement', $generalsettingspage);

    // Page Management.
    $pagedesc = get_string('managepages', 'local_mcms');
    $pageurl = new moodle_url($CFG->wwwroot . '/local/mcms/admin/page/list.php');

    $mcmsmanagement->add('mcmspagemanagement',
        new admin_externalpage(
            'managepage',
            $pagedesc,
            $pageurl,
            ['local/mcms:managepages'],
            empty($CFG->enablemcms)
        )
    );

    if (!empty($CFG->enablemcms)) {
        $ADMIN->add('root', $mcmsmanagement);
    }

    // Create a global Advanced Feature Toggle.
    $optionalsubsystems = $ADMIN->locate('optionalsubsystems');
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablemcms',
            new lang_string('enablemcms', 'local_mcms'),
            new lang_string('enablemcms_help', 'local_mcms'),
            1)
    );
}
