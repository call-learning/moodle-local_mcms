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
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mcms;

use context_system;
use local_mcms\event\page_added;
use local_mcms\event\page_updated;
use local_mcms\form\add_edit_form;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Class page
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
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
     * Name of the file area for images
     */
    const PLUGIN_FILE_AREA_DESCRIPTION = 'description';

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
        $styles = [
            'default' => get_string('pagestyle:default', 'local_mcms'),
            'cta' => get_string('pagestyle:cta', 'local_mcms'),
        ];
        $templatefinder = class_exists('\\theme_clboost\\output\\mustache_template_finder') ?
            new \theme_clboost\output\mustache_template_finder() : new \core\output\mustache_template_finder();

        $templatedirs = $templatefinder->get_template_directories_for_component('local_mcms');
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
        return $styles;
    }

    /**
     * Get associated images
     *
     * @param int $pageid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_page_images_urls($pageid) {
        $contextsystemid = context_system::instance()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextsystemid,
            self::PLUGIN_FILE_COMPONENT,
            self::PLUGIN_FILE_AREA_IMAGE,
            $pageid);
        $imagesurls = [];
        foreach ($files as $image) {
            if ($image->is_valid_image()) {
                $imagesurls[] = moodle_url::make_pluginfile_url(
                    $contextsystemid,
                    self::PLUGIN_FILE_COMPONENT,
                    self::PLUGIN_FILE_AREA_IMAGE,
                    $pageid,
                    $image->get_filepath(),
                    $image->get_filename()
                );
            }
        }
        return $imagesurls;
    }

    /**
     * Helper for page adding ($data is form data)
     *
     * @param \stdClass $data
     * @param bool $isnewpage
     * @return moodle_url
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function page_add_edit($data, $isnewpage = true) {
        global $CFG;
        $contextsystem = context_system::instance();
        $page = null;
        if ($isnewpage) {
            $page = new page(0, $data);
            $page->create();
        } else {
            $page = new page($data->id, $data);
            $page->update();
        }
        // Upload image from description if any.
        $draftideditor = file_get_submitted_draft_itemid('description');
        $description =
            file_save_draft_area_files(
                $draftideditor,
                $contextsystem->id,
                self::PLUGIN_FILE_COMPONENT,
                self::PLUGIN_FILE_AREA_DESCRIPTION,
                $page->get('id'),
                add_edit_form::get_description_editor_options(),
                $data->description,
                false);
        // Update description if we replaced internal images links.
        if ($page->get('description') != $description) {
            $page->set('description', $description);
            $page->save();
        }
        $page->update_associated_roles($data->pageroles);
        // Upload images.
        file_postupdate_standard_filemanager($data, 'image',
            add_edit_form::get_images_options(),
            $contextsystem,
            self::PLUGIN_FILE_COMPONENT,
            self::PLUGIN_FILE_AREA_IMAGE,
            $page->get('id'));

        if ($isnewpage) {
            $eventparams = array('objectid' => $page->get('id'), 'context' => context_system::instance());
            $event = page_added::create($eventparams);
            $event->trigger();
        } else {
            $action = get_string('pageinfoupdated', 'local_mcms');
            $eventparams = array('objectid' => $page->get('id'),
                'context' => context_system::instance(),
                'other' => array(
                    'actions' => $action
                ));
            $event = page_updated::create($eventparams);
            $event->trigger();
        }
        $viewurl = new moodle_url($CFG->wwwroot . '/local/mcms/index.php', ['id' => $page->get('id')]);
        return $viewurl;
    }
}
