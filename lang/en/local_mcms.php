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

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Moodle Mini CMS';

$string['pagemanagement'] = 'Mini CMS Page management';
$string['mcmsgeneralsettings'] = 'General Settings';
$string['managepages'] = 'Manage pages';
$string['enablemcms'] = 'Enable Moodle Mini CMS';
$string['enablemcms_help'] = 'Moodle Mini CMS allow to pages that are made of blocks. Blocks can be customized at will.';

$string['page:list'] = 'List Pages';

// Filter labels.
$string['pagefilter:idnumber'] = 'Unique Identifier (URL)';
$string['pagefilter:rolename'] = 'Role Name';
$string['pagefilter:orderby'] = 'Order By';
$string['pagefilter:title'] = 'Full Name';
$string['pagefilter:shortname'] = 'Short Name';
$string['pagefilter:usermodified'] = 'User Modified';
$string['pagefilter:timecreated'] = 'Time created';
$string['pagefilter:timemodified'] = 'Time modified';

$string['pagefilter:title:asc'] = 'Full Name (Ascending)';
$string['pagefilter:title:desc'] = 'Full Name (Descending)';
$string['pagefilter:timemodified:desc'] = 'Time Modified (Descending)';
$string['pagefilter:timemodified:asc'] = 'Time Modified (Ascending)';

// Column label.
$string['idnumber'] = 'Unique Identifier (URL)';
$string['rolename'] = 'Role Name';
$string['pagefilter:orderby'] = 'Order By';
$string['title'] = 'Full Name';
$string['shortname'] = 'Short Name';
$string['usermodified'] = 'User Modified';
$string['timecreated'] = 'Time created';
$string['timemodified'] = 'Time modified';
$string['actions'] = 'Actions';

// Add/Edit form.
$string['page:add'] = 'Add page';
$string['page:edit'] = 'Edit page';
$string['page:delete'] = 'Delete page';
$string['page:list'] = 'All pages';

$string['pagetitle'] = 'Page title';
$string['pageshortname'] = 'Page Shortname';
$string['pageidnumber'] = 'Page ID (pretty url)';
$string['pageroles'] = 'Page Roles';

// Events.
$string['pageadded'] = 'Page Added';
$string['pagemodified'] = 'Page Modified';
$string['pagedeleted'] = 'Page Deleted';

// Actions.
$string['pageaction:view'] = 'View page';
$string['pageaction:edit'] = 'Edit page';
$string['pageaction:delete'] = 'Delete page';
