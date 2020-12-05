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

namespace local_mcms\output\menu;

use local_mcms\menu\menu;
use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Class renderer for menu
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Returns the menu if one has been set
     *
     * A custom menu can be configured by browsing to
     *    Settings: Mini CMS Page Management > General settings
     * and then configuring the root menu config setting as described.
     *
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function mcms_menu() {
        $definitions = get_config('local_mcms', 'rootmenuitems');
        $mcmsmenu = new menu($definitions, current_language());
        return $this->render_menu($mcmsmenu);
    }

    /**
     * We want to show the custom menus as a list of links in the footer on small screens.
     * Just return the menu object exported so we can render it differently.
     */
    public function mcms_menu_menu_flat() {
        $definitions = get_config('local_mcms', 'rootmenuitems');
        $mcmsmenu = new menu($definitions, current_language());
        return $mcmsmenu->export_for_template($this);
    }

    /**
     * Renders a custom menu object for the MCMS plugin
     *
     * The custom menu this method produces makes use of the YUI3 menunav widget
     * and requires very specific html elements and classes.
     *
     * @param menu $menu
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function render_menu(menu $menu) {
        $content = '';
        foreach ($menu->get_children() as $item) {
            $context = $item->export_for_template($this);
            $content .= $this->render_from_template('core/custom_menu_item', $context);
        }

        return $content;
    }

}