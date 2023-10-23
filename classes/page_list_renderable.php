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
use renderable;

/**
 * Moodle Mini CMS utility.
 *
 * Provide the ability to manage site pages through blocks.
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_list_renderable implements renderable {
    /** @var int page number */
    public $page;

    /** @var int perpage records to show */
    public $perpage;

    /** @var \moodle_url url of report page */
    public $url;

    /** @var string order to sort */
    public $order;

    /** @var page_list page list */
    public $pagelist;

    /**
     * @var \stdClass filter
     *
     *  string title:  title
     *  string shortname:  shortname
     *  string idnumber:  idnumber
     *  int roleid: role id to filter
     *  int userid: user id
     *  string orderby : 'asc title'
     */
    public $filter;

    /**
     * Constructor
     *
     * @param null $filter
     * @param string $url
     * @param int $page
     * @param int $perpage
     * @throws \moodle_exception
     */
    public function __construct(
        $filter = null,
        $url = "",
        $page = 0,
        $perpage = 100
    ) {

        global $PAGE;

        // Use page url if empty.
        if (empty($url)) {
            $url = new moodle_url($PAGE->url);
        } else {
            $url = new moodle_url($url);
        }

        $this->page = $page;
        $this->perpage = $perpage;
        $this->url = $url;
        $this->filter = $filter ? $filter : null;
        $this->setup_table();
    }

    /**
     * Setup table log.
     */
    public function setup_table() {
        $this->pagelist = new page_list('page_list', $this->filter);
        $this->pagelist->define_baseurl($this->url);
        $this->pagelist->is_downloadable(true);
        $this->pagelist->show_download_buttons_at([TABLE_P_BOTTOM]);
    }

    /**
     * Download logs in specified format.
     */
    public function download() {
        $filename = 'page_list' . userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
        $this->pagelist->is_downloading('csv', $filename);
        $this->pagelist->out($this->perpage, false);
    }
}
