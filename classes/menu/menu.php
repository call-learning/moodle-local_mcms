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

namespace local_mcms\menu;

use context_system;
use local_mcms\page;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Mini CMS menu
 *
 * This class is used to operate the menu that can be rendered for the page.
 * It is strongly inspired from the custom menu feature, but will also add
 * submenu from pages.
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class menu extends menu_item {

    /**
     * @var string The language we should render for, null disables multilang support.
     */
    protected $currentlanguage = null;

    /**
     * Creates the custom menu
     *
     * @param string $definition the menu items definition in syntax required by {@link convert_text_to_menu_nodes()}
     * @param string $currentlanguage the current language code, null disables multilang support
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws moodle_exception
     */
    public function __construct($definition = '', $currentlanguage = null) {
        $this->currentlanguage = $currentlanguage;
        parent::__construct('root'); // Create virtual root element of the menu.
        // TODO cache the result of the definition and page parsing.
        if (!empty($definition)) {
            $this->override_children(self::convert_text_to_menu_nodes($definition, $currentlanguage));
        }
        $this->add_pages_as_children();
    }

    /**
     * Overrides the children of this custom menu. Useful when getting children
     *
     * @param array $children
     */
    public function override_children(array $children) {
        $this->children = array();
        foreach ($children as $child) {
            if ($child instanceof menu_item) {
                $this->children[] = $child;
            }
        }
    }

    /**
     * Converts a string into a structured array of custom_menu_items which can
     * then be added to a custom menu.
     *
     * Structure:
     *     text|uniqueid|url|angs|roles
     * The number of hyphens at the start determines the depth of the item. The
     * languages are optional, comma separated list of languages the line is for.
     * The roles are also optional and are a list of comma separated list of
     * roles shortnames.
     * Finally the uniqueid is only used in case you want to attach pages to it and
     * can be empty.
     *
     * Example structure:
     *     First level first item|firstlevel|http://www.moodle.com/
     *     -Second level first item|secondlevel|http://www.moodle.com/partners/
     *     -Second level second item|secondlevelseconditem|http://www.moodle.com/hq/
     *     --Third level first item||http://www.moodle.com/jobs/
     *     -Second level third item|http://www.moodle.com/development/
     *     First level second item|http://www.moodle.com/feedback/
     *     First level third item|firstlevelthirditem
     *     English only|http://moodle.com|en
     *     German only|http://moodle.de|de,de_du,de_kids
     *
     *
     * @param string $text the menu items definition
     * @param string $language the language code, null disables multilang support
     * @return array
     * @throws \dml_exception
     */
    public static function convert_text_to_menu_nodes($text, $language = null) {
        $alluserroles = get_user_roles(\context_system::instance());
        $alluserrolessn = array_map(function($r) {
            return $r->shortname;
        }, $alluserroles);
        $root = new menu();
        $lastitem = $root;
        $lastdepth = 0;
        $hiddenitems = array();
        $lines = explode("\n", $text);
        foreach ($lines as $linenumber => $line) {
            $line = trim($line);
            if (strlen($line) == 0) {
                continue;
            }
            // Parse item settings.
            $itemtext = null;
            $itemurl = null;
            $itemuniqueid = null;
            $itemvisible = true;
            $settings = explode('|', $line);
            foreach ($settings as $i => $setting) {
                $setting = trim($setting);
                if (!empty($setting)) {
                    switch ($i) {
                        case 0: // Menu text.
                            $itemtext = ltrim($setting, '-');
                            break;
                        case 1: // UniqueID.
                            $itemuniqueid = ltrim($setting, '-');
                            break;
                        case 2: // URL.

                            try {
                                if ($settings) {
                                    $itemurl = new moodle_url($setting);
                                } else {
                                    $itemurl = null; // We allow empty urls.
                                }
                            } catch (moodle_exception $exception) {
                                // We're not actually worried about this, we don't want to mess up the display
                                // just for a wrongly entered URL.
                                $itemurl = null;
                            }
                            break;
                        case 3: // Language.
                            if (!empty($language)) {
                                $itemlanguages = array_map('trim', explode(',', $setting));
                                $itemvisible &= in_array($language, $itemlanguages);
                            }
                            break;
                        case 4: // Role.
                            $itemroles = array_map('trim', explode(',', $setting));
                            if (!empty($itemroles)) {
                                $itemvisible &= empty(array_intersect($alluserrolessn, $itemroles));
                            }
                            break;
                    }
                }
            }
            // Get depth of new item.
            preg_match('/^(\-*)/', $line, $match);
            $itemdepth = strlen($match[1]) + 1;
            // Find parent item for new item.
            while (($lastdepth - $itemdepth) >= 0) {
                $lastitem = $lastitem->get_parent();
                $lastdepth--;
            }
            $lastitem = $lastitem->add($itemtext, $itemuniqueid, $itemurl, $linenumber + 1);
            $lastdepth++;
            if (!$itemvisible) {
                $hiddenitems[] = $lastitem;
            }
        }
        foreach ($hiddenitems as $item) {
            $item->parent->remove_child($item);
        }
        return $root->get_children();
    }

    /**
     * Add all pages as children
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws moodle_exception
     */
    protected function add_pages_as_children() {
        global $USER;
        $allpages = page::get_records();
        foreach ($allpages as $p) {
            if (page::can_view_page($USER, $p, context_system::instance())) {
                $this->add_page_in_menu($this, $p);
            }
        }
    }

    /**
     * Add page in given menu
     *
     * @param menu_item $item
     * @param page $p
     * @throws \coding_exception
     * @throws moodle_exception
     */
    protected function add_page_in_menu(menu_item $item, page $p) {
        $parentmenu = $p->get('parentmenu');
        if ($item->get_uniqueid() == $parentmenu ||
            ($item->text == 'root' && $item->parent == null && $parentmenu == 'top')) {
            $item->add(
                $p->get('shortname') ? $p->get('shortname') : $p->get('title'),
                $p->get('idnumber'),
                $p->get_url(),
                $p->get('menusortorder') ? $p->get('menusortorder') : null
            );
            // Then sort.
            $item->sort();
        } else {
            // If not check in children components.
            foreach ($item->get_children() as $subitem) {
                $this->add_page_in_menu($subitem, $p);
            }
        }
    }

    /**
     * Sorts two custom menu items
     *
     * This function is designed to be used with the usort method
     *     usort($this->children, array('custom_menu','sort_custom_menu_items'));
     *
     * @param menu_item $itema
     * @param menu_item $itemb
     * @return int
     */
    public static function sort_custom_menu_items(menu_item $itema, menu_item $itemb) {
        $itema = $itema->get_sort_order();
        $itemb = $itemb->get_sort_order();
        if ($itema == $itemb) {
            return 0;
        }
        return ($itema > $itemb) ? +1 : -1;
    }

    /**
     * Get identifiable menu items (i.e. any menu with unique id)
     *
     * @param menu_item $rootitem
     * @return array|mixed
     */
    protected function get_identifiable_menu_items(menu_item $rootitem) {
        $identifiableitems = [];
        foreach ($rootitem->get_children() as $child) {
            $identifiableitems += $this->get_identifiable_menu_items($child);
        }
        if ($rootitem->get_uniqueid()) {
            $identifiableitems[$rootitem->get_uniqueid()] = $rootitem->get_text();
        }
        return $identifiableitems;
    }

    /**
     * Get all menus that can be identified
     * @return array
     * @throws \dml_exception
     */
    public static function get_all_identifiable_menus() {
        $menuitems = ['none' => get_string('none'), 'top' => get_string('top')];
        $definition = get_config('local_mcms', 'rootmenuitems');
        if ($definition) {
            $menu = new menu($definition);
            foreach ($menu->children as $item) {
                $menuitems += $menu->get_identifiable_menu_items($item);
            }
        }
        return $menuitems;
    }
}
