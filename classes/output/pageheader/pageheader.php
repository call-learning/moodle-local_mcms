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

use context_system;
use local_mcms\page;
use local_mcms\page_utils;
use renderable;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Page header
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pageheader implements renderable {
    /**
     * @var stdClass|null current context for the page
     */
    public $pagecontext = null;
    /**
     * @var mixed|string|null current style for the page
     */
    public $currentstyle = 'default';

    /**
     * Pageheader constructor.
     *
     * @param page $page
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(page $page) {
        if ($page->get('style')) {
            $this->currentstyle = $page->get('style');
        }
        $this->pagecontext = new stdClass();
        $this->pagecontext = $page->to_record();
        $this->pagecontext->imagesurl = [];
        $text =
            file_rewrite_pluginfile_urls($this->pagecontext->description,
                'pluginfile.php',
                context_system::instance()->id,
                page_utils::PLUGIN_FILE_COMPONENT,
                page_utils::PLUGIN_FILE_AREA_IMAGE,
                $page->get('id'));

        $this->pagecontext->descriptionhtml = format_text($text, $page->get('descriptionformat'));

        foreach (page_utils::get_page_images_urls($page->get('id')) as $url) {
            $this->pagecontext->imagesurl[] = $url->out();
        }
    }
}
