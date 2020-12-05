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

$string['enablemcms'] = 'Enable Moodle Mini CMS';
$string['enablemcms_help'] = 'Moodle Mini CMS allow to pages that are made of blocks. Blocks can be customized at will.';
$string['managepages'] = 'Manage pages';
$string['mcms:editpage'] = 'Edit page';
$string['mcms:managepages'] = 'Manage page';
$string['mcmsgeneralsettings'] = 'General Settings';
$string['pagemanagement'] = 'Mini CMS Page management';
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

// Column label and Add/edit Label.

$string['page:title'] = 'Page title';
$string['page:title_help'] = 'Page title is the main title for the page';
$string['page:shortname'] = 'Page Shortname';
$string['page:shortname_help'] = 'Page Shortname as a unique identifier for the page and displayed in breadcrumbs';
$string['page:idnumber'] = 'Page ID (pretty url)';
$string['page:idnumber_help'] = 'This is a unique identifier for a page, in the form of a nice URL. For example my-page.'
    . 'The view url will be /local/mcms/index.php?p=my-page';
$string['page:description'] = 'Page Description';
$string['page:description_help'] = 'Page Description is the page description displayed at the top of the page';
$string['page:image'] = 'Page Image';
$string['page:image_help'] = 'Page Image as hero image';

$string['page:roles'] = 'Page Roles';
$string['page:roles_help'] = 'Page Roles that are allowed to view the page';
$string['page:parent'] = 'Page Parent';
$string['page:parent_help'] = 'Page Parent (not used for now)';
$string['page:style'] = 'Page Style';
$string['page:style_help'] = 'Page Style as defined by theme templates (mcmspage_style_xxx.mustache)';
$string['page:ctalink'] = 'Page CTA link';
$string['page:ctalink_help'] = 'Page CTA link';
$string['page:image'] = 'Page Image';
$string['page:image_help'] = 'Page Main Image (hero)';

$string['page:parentmenu'] = 'Page Parent Menu (top menu above)';
$string['page:parentmenu_help'] =
    'Page parent menu so it will be attached to the menu defined in the plugin rootmenuitems settings.';

$string['page:menusortorder'] = 'Page Menu sortorder';
$string['page:menusortorder_help'] = 'Page menu sortorder. Leave it to 0 for usual page sortorder';

$string['page:usermodified'] = 'User Modified';
$string['page:timecreated'] = 'Time created';
$string['page:timemodified'] = 'Time modified';
$string['page:actions'] = 'Actions';

// Add/Edit form.
$string['page:add'] = 'Add page';
$string['page:edit'] = 'Edit page';
$string['page:delete'] = 'Delete page';
$string['page:list'] = 'All pages';

// Events.
$string['pageadded'] = 'Page Added';
$string['pagemodified'] = 'Page Modified';
$string['pagedeleted'] = 'Page Deleted';
$string['pageinfoupdated'] = 'Page Info Updated';

// Actions.
$string['pageaction:view'] = 'View page';
$string['pageaction:edit'] = 'Edit page';
$string['pageaction:delete'] = 'Delete page';

// Styles.

$string['pagestyle:default'] = 'Default Style';
$string['pagestyle:cta'] = 'CALL To Action Style';

$string['pluginname'] = 'Moodle Mini CMS';

$string['rootmenuitems'] = 'Root menu items';

$string['rootmenuitems_help'] =
    'A set of items that will be displayed as root menus in the page. Enter each menu item on a new line' .
    'with format: menu text, a unique id, a link URL (optional, not for a top menu item with sub-items),a ' .
    'language code or comma-separated list of codes (optional, for displaying the line to' .
    'users of the specified language only) and  a comma separated list of roles (shortnames) separated by pipe characters. ' .
    'Lines starting with a hyphen will appear as menu items in' .
    'the previous top level menu and ### makes a divider. For example:
<pre>
Courses
-All courses|allcourse|/course/
-Course search|coursesearch|/course/search.php
-###
-FAQ|faq|https://someurl.xyz/faq
-Les cours des inscrits|lescoursdesinscrits|https://someurl.xyz/pmf||es|student,teacher
Mobile app|mobileapp|https://someurl.xyz/app
</pre>';
