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

namespace local_mcms\output\pageheader;

use moodle_exception;
use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Class renderer
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render page header
     *
     * @param pageheader $pageheader
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_pageheader(pageheader $pageheader) {
        try {
            return $this->render_from_template("local_mcms/mcmspage_style_{$pageheader->currentstyle}", $pageheader->pagecontext);
        } catch (moodle_exception $e) {
            return $this->render_from_template("local_mcms/mcmspage_style_default", $pageheader->pagecontext);
        }
    }
}
