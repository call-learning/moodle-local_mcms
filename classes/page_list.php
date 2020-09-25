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

defined('MOODLE_INTERNAL') || die;

use moodle_url;
use pix_icon;
use popup_action;
use stdClass;
use table_sql;

/**
 * Table log class for displaying logs.
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_list extends table_sql {

    /** @var array list of user fullnames shown in report */
    private $userfullnames = array();

    /** @var stdClass filters parameters */
    private $filterparams;

    /**
     * Sets up the page_table parameters.
     *
     * @param string $uniqueid unique id of form.
     * @param stdClass $filterparams (optional) filter params.
     * @throws \coding_exception
     * @see page_list::get_filter_definition() for filter definition
     */
    public function __construct($uniqueid, $filterparams = null) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'generaltable generalbox table-sm');
        $this->filterparams = $filterparams;
        // Add course column if logs are displayed for site.
        $cols = array();
        $headers = array();
        $this->define_columns(array_merge($cols, array('fullname',
            'shortname',
            'idnumber',
            'usermodified',
            'timecreated',
            'timemodified',
            'actions')));
        $this->define_headers(array_merge($headers, array(
                get_string('fullname'),
                get_string('shortname'),
                get_string('idnumber'),
                get_string('usermodified'),
                get_string('timecreated'),
                get_string('timemodified'),
                get_string('actions')
            )
        ));
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
    }

    /**
     * Gets the user full name
     *
     * This function is useful because, in the unlikely case that the user is
     * not already loaded in $this->userfullnames it will fetch it from db.
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
        if ($this->userfullnames[$userid] === false) {
            return false;
        }

        // If we reach that point new users logs have been generated since the last users db query.
        list($usql, $uparams) = $DB->get_in_or_equal($userid);
        $sql = "SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql;
        if (!$user = $DB->get_records_sql($sql, $uparams)) {
            return false;
        }

        $this->userfullnames[$userid] = fullname($user);
        return $this->userfullnames[$userid];
    }

    protected function get_time($time) {
        if (empty($this->download)) {
            $dateformat = get_string('strftimedatetime', 'core_langconfig');
        } else {
            $dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
        }
        return userdate($time, $dateformat);
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
     * Generate the fullname column.
     *
     * @param stdClass $page page data
     * @return string HTML for the description column
     */
    public function col_fullname($page) {
        return $page->fullname;
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

        $baseurl = '/local/mcms/admin/';

        $actionsdef = [
            'add' => (object) [
                'icon' => 't/add',
                'url' => new moodle_url($baseurl . '/add.php'),
            ],
            'view' => (object) [
                'icon' => 'e/search',
                'url' => new moodle_url($baseurl . '/view.php', ['pageid' => $row->id]),
                'popup' => true
            ],
            'delete' => (object) [
                'icon' => 't/delete',
                'url' => new moodle_url($baseurl . '/delete.php'),
                'popup' => true
            ]
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
    protected function build_search_filter_like($fieldname, $joins, $params) {
        if (!empty($this->filterparams->$fieldname)) {
            global $DB;
            $joins[] = $DB->sql_like($fieldname, ':' . $fieldname, false, false);
            $params[$fieldname] = '%' . $DB->sql_like_escape($this->filterparams->$fieldname) . '%';
        }
    }

    /**
     * Query the reader. Store results in the object for use by build_table.
     *
     *
     *     - string fullname:  fullname
     *     - string shortname:  shortname
     *     - string idnumber:  idnumber
     *     - int roleid: role id to filter
     *     - int userid: user id
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     * @throws \dml_exception
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $joins = array();
        $params = array();

        $orderby = 'fullname ASC';
        if ($this->filterparams) {
            $this->build_search_filter_like('fullname', $joins, $params);
            $this->build_search_filter_like('shortname', $joins, $params);
            $this->build_search_filter_like('idnumber', $joins, $params);
            $orderby = !empty($this->filterparams->orderby) ? $this->filterparams->orderby : $orderby;
        }
        $selector = implode(' AND ', $joins);

        if (!$this->is_downloading()) {
            $total = $DB->count_records_select('local_mcms', $selector, $params);
            $this->pagesize($pagesize, $total);
        } else {
            $this->pageable(false);
        }

        // Get the users and course data.
        $this->rawdata = $DB->get_recordset_select('local_mcms', $selector, $params,
            $orderby, $this->get_page_start(), $this->get_page_size());

        // Set initial bars.
        if ($useinitialsbar && !$this->is_downloading()) {
            $this->initialbars($total > $pagesize);
        }
    }

    /**
     * Return filter defintion
     *
     * @return array
     */
    public static function get_filter_definition() {
        return array(
            'shortname' => (object) ['type' => PARAM_TEXT, 'default' => ''],
            'fullname' => (object) ['type' => PARAM_TEXT, 'default' => ''],
            'idnumber' => (object) ['type' => PARAM_TEXT, 'default' => ''],
            'rolename' => (object) ['type' => PARAM_TEXT, 'default' => ''],
            'usermodified' => (object) ['type' => PARAM_TEXT, 'default' => ''],
            'orderby' => (object) ['type' => PARAM_TEXT, 'default' => 'fullname ASC'],
        );
    }
}
