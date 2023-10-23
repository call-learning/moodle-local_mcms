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

use moodle_url;
use pix_icon;
use popup_action;
use stdClass;
use table_sql;

/**
 * Page list
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_list extends table_sql {

    /** @var array list of user fullname shown in report */
    private $userfullnames = [];

    /** @var stdClass filters parameters */
    private $filterparams;

    /**
     * Sets up the page_table parameters.
     *
     * @param string $uniqueid unique id of form.
     * @param stdClass $filterparams (optional) filter params.
     * @throws \coding_exception
     * @see \local_mcms\page_list_filter_form::get_filter_definition() for filter definition
     */
    public function __construct($uniqueid, $filterparams = null) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'generaltable generalbox table-sm');
        $this->filterparams = $filterparams;
        // Add course column if logs are displayed for site.
        $cols = [];
        $headers = [];
        $this->define_columns(array_merge($cols, ['title',
            'image',
            'shortname',
            'roles',
            'idnumber',
            'usermodified',
            'timecreated',
            'timemodified',
            'actions', ]));
        $this->define_headers(array_merge($headers, [
                get_string('page:title', 'local_mcms'),
                get_string('page:image', 'local_mcms'),
                get_string('page:shortname', 'local_mcms'),
                get_string('page:roles', 'local_mcms'),
                get_string('page:idnumber', 'local_mcms'),
                get_string('page:usermodified', 'local_mcms'),
                get_string('page:timecreated', 'local_mcms'),
                get_string('page:timemodified', 'local_mcms'),
                get_string('page:actions', 'local_mcms'),
            ]
        ));
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
    }

    /**
     * Gets the user full name
     *
     * This function is useful because, in the unlikely case that the user is
     * not already loaded in $this->userfullname it will fetch it from db.
     *
     * @param int $userid
     * @return false|\lang_string|mixed|string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function get_user_fullname($userid) {
        global $DB;

        if (empty($userid)) {
            return false;
        }

        if (!empty($this->userfullnames[$userid])) {
            return $this->userfullnames[$userid];
        }

        // We already looked for the user and it does not exist.
        if (isset($this->userfullnames[$userid]) && $this->userfullnames[$userid] === false) {
            return false;
        }

        // If we reach that point new users logs have been generated since the last users db query.
        list($usql, $uparams) = $DB->get_in_or_equal($userid);
        $sql = "SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql;
        if (!$user = $DB->get_record_sql($sql, $uparams)) {
            $this->userfullnames[$userid] = false;
            return false;
        }

        $this->userfullnames[$userid] = fullname($user);
        return $this->userfullnames[$userid];
    }

    /**
     * Get time as a user friendly display
     *
     * @param int $time
     * @return string
     * @throws \coding_exception
     */
    protected function get_time($time) {
        if (empty($this->download)) {
            $dateformat = get_string('strftimedatetime', 'core_langconfig');
        } else {
            $dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
        }
        return userdate($time, $dateformat);
    }

    /**
     * Generate the image column.
     *
     * @param stdClass $page page data
     * @return string HTML for the time column
     */
    public function col_image($page) {
        $imagesurls = page_utils::get_page_images_urls($page->id);
        $imageshtml = '';
        foreach ($imagesurls as $src) {
            $imageshtml .= \html_writer::img($src, 'page-image', ['class' => 'img-thumbnail']);
        }
        return $imageshtml;
    }

    /**
     * Generate the timecreated column.
     *
     * @param stdClass $page page data
     * @return string HTML for the time column
     */
    public function col_timecreated($page) {
        return $this->get_time($page->timecreated);
    }

    /**
     * Generate the role column.
     *
     * @param stdClass $page page data
     * @return string HTML for the time column
     */
    public function col_roles($page) {
        global $DB;
        $roleshtml = '';

        $associatedroles = page::get_page_roles($page->id);
        foreach ($associatedroles as $r) {
            $role = $DB->get_record('role', ['id' => $r->get('roleid')]);
            $roleshtml .= \html_writer::div(role_get_name($role), 'badge badge-primary');
        }
        return $roleshtml;
    }

    /**
     * Generate the timemodified column.
     *
     * @param stdClass $page page data
     * @return string HTML for the time column
     */
    public function col_timemodified($page) {
        return $this->get_time($page->timemodified);
    }

    /**
     * Generate the usermodified column.
     *
     * @param stdClass $page page data
     * @return string HTML for the username column
     */
    public function col_usermodified($page) {
        // Add username who did the action.
        return $this->get_user_fullname($page->usermodified);
    }

    /**
     * Generate the title column.
     *
     * @param stdClass $page page data
     * @return string HTML for the description column
     */
    public function col_title($page) {
        return $page->title;
    }

    /**
     * Generate the shortname column.
     *
     * @param stdClass $page page data
     * @return string HTML for the description column
     */
    public function col_shortname($page) {
        return $page->shortname;
    }

    /**
     * Generate the idnumber column.
     *
     * @param stdClass $page page data
     * @return string HTML for the description column
     */
    public function col_idnumber($page) {
        return $page->idnumber;
    }

    /**
     * Format the actions cell.
     *
     * @param stdClass $row
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function col_actions($row) {
        global $OUTPUT;

        $actions = [];

        $baseurl = '/local/mcms/admin/page';

        $actionsdef = [
            'edit' => (object) [
                'icon' => 't/edit',
                'url' => new moodle_url($baseurl . '/edit.php', ['id' => $row->id]),
            ],
            'view' => (object) [
                'icon' => 'e/search',
                'url' => new moodle_url('/local/mcms/index.php', ['id' => $row->id]),
            ],
            'delete' => (object) [
                'icon' => 't/delete',
                'url' => new moodle_url($baseurl . '/delete.php', ['id' => $row->id]),
            ],
        ];
        foreach ($actionsdef as $k => $a) {
            $popupaction = empty($a->popup) ? null :
                new popup_action('click', $a->url);
            $actions[] = $OUTPUT->action_icon(
                $a->url,
                new pix_icon($a->icon, get_string('pageaction:' . $k, 'local_mcms')),
                $popupaction
            );
        }

        return implode('&nbsp;', $actions);
    }

    /**
     * Build a search query for the given field
     *
     * @param string $fieldname
     * @param array $joins
     * @param array $params
     */
    protected function build_search_filter_like($fieldname, &$joins, &$params) {
        if (!empty($this->filterparams->$fieldname)) {
            global $DB;
            $joins[] = $DB->sql_like($fieldname, ':' . $fieldname, false, false);
            $params[$fieldname] = '%' . $DB->sql_like_escape($this->filterparams->$fieldname) . '%';
        }
    }

    /**
     * Query the database. Store results in the object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     * @throws \dml_exception
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $joins = ['1=1'];
        $params = [];

        $orderby = 'title ASC';
        if ($this->filterparams) {
            $this->build_search_filter_like('title', $joins, $params);
            $this->build_search_filter_like('shortname', $joins, $params);
            $this->build_search_filter_like('idnumber', $joins, $params);
            $orderby = !empty($this->filterparams->orderby) ? $this->filterparams->orderby : $orderby;
        }
        $selector = implode(' AND ', $joins);

        if (!$this->is_downloading()) {
            $total = $DB->count_records_select('local_mcms_page', $selector, $params);
            $this->pagesize($pagesize, $total);
        } else {
            $this->pageable(false);
        }

        // Get all matching data.
        $this->rawdata = $DB->get_recordset_select('local_mcms_page',
            $selector,
            $params,
            $orderby,
            '*',
            $this->get_page_start(),
            $this->get_page_size());

        // Set initial bars.
        if ($useinitialsbar && !$this->is_downloading()) {
            $this->initialbars($total > $pagesize);
        }
    }
}
