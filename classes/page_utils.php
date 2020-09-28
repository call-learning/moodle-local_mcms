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
 * Moodle Mini CMS utility class.
 *
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mcms;

defined('MOODLE_INTERNAL') || die();

/**
 * Class page
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_utils {
    /**
     * Name of the file area for images
     */
    const PLUGIN_FILE_AREA_IMAGE = 'image';
    /**
     * Name of the current plugin for file manager
     */
    const PLUGIN_FILE_COMPONENT = 'local_mcms';

    /**
     * Name of the page layout
     */
    const PAGE_LAYOUT_NAME = 'mcmslayout';
    /**
     * Get all available templates for pages
     *
     * @return array
     * @throws \coding_exception
     */
    public static function get_template_styles_for_mcms() {
        global $OUTPUT;
        $styles = [
            'default' => get_string('pagestyle:default', 'local_mcms'),
            'cta' => get_string('pagestyle:cta', 'local_mcms'),
        ];
        if (method_exists($OUTPUT, 'get_mustache_template_finder')) {
            $templatesfinder = $OUTPUT->get_mustache_template_finder();
            $templatedirs = $templatesfinder->get_template_directories_for_component('local_mcms');
            foreach ($templatedirs as $dir) {
                $prefix = $dir . '/mcmspage_style';
                foreach (glob($prefix . '*.mustache') as $style) {
                    $filename = basename($style);
                    $basestyle = substr($filename, strlen('mcmspage_style_'), -strlen('.mustache'));
                    if (!isset($styles[$basestyle])) {
                        $styles[$basestyle] = $basestyle;
                    }
                }
            }
        }
        return $styles;
    }

    /**
     * Get associated images
     *
     * @param int $pageid
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_page_images_urls($pageid) {
        $contextsystemid = \context_system::instance()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextsystemid,
            \local_mcms\page_utils::PLUGIN_FILE_COMPONENT,
            \local_mcms\page_utils::PLUGIN_FILE_AREA_IMAGE,
            $pageid);
        $imagesurls = [];
        foreach ($files as $image) {
            if ($image->is_valid_image()) {
                $imagesurls[] = \moodle_url::make_pluginfile_url(
                    $contextsystemid,
                    \local_mcms\page_utils::PLUGIN_FILE_COMPONENT,
                    \local_mcms\page_utils::PLUGIN_FILE_AREA_IMAGE,
                    $pageid,
                    $image->get_filepath(),
                    $image->get_filename()
                );
            }
        }
        return $imagesurls;
    }
}
