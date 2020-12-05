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
use moodle_url;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Mini CMS menu item
 *
 * This class is used to represent one item within a menu that may or may
 * not have children.
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class menu_item implements renderable, templatable {

    /**
     * @var string The text to show for the item
     */
    protected $text;

    /**
     * @var string The uniqueid for the item
     */
    protected $uniqueid;

    /**
     * @var moodle_url The link to give the icon if it has no children
     */
    protected $url;

    /**
     * @var int A sort order for the item, not necessary if you order things in
     * the CFG var.
     */
    protected $sort;

    /**
     * @var menu_item A reference to the parent for this item or NULL if
     * it is a top level item
     */
    protected $parent;

    /**
     * @var array A array in which to store children this item has.
     */
    protected $children = array();

    /**
     * @var int A reference to the sort var of the last child that was added
     */
    protected $lastsort = 0;

    /**
     * Constructs the new custom menu item
     *
     * @param string $text
     * @param string $uniqueid A uniqueid to apply to this item [Optional]
     * @param moodle_url $url A moodle url to apply as the link for this item [Optional]
     * @param int $sort A sort or to use if we need to sort differently [Optional]
     * @param menu_item $parent A reference to the parent menu_item this child
     *        belongs to, only if the child has a parent. [Optional]
     */
    public function __construct($text, $uniqueid = null, moodle_url $url = null, $sort = null,
        menu_item $parent = null) {
        $this->text = $text;
        $this->uniqueid = $uniqueid;
        $this->url = $url;
        $this->sort = (int) $sort;
        $this->parent = $parent;
    }

    /**
     * Adds a custom menu item as a child of this node given its properties.
     *
     * @param string $text
     * @param string $uniqueid
     * @param moodle_url $url
     * @param int $sort
     * @return menu_item
     */
    public function add($text, $uniqueid = null, moodle_url $url = null, $sort = null) {
        $key = count($this->children);
        if (empty($sort)) {
            $sort = $this->lastsort + 1;
        }
        $this->children[$key] = new menu_item($text, $uniqueid, $url, $sort, $this);
        $this->lastsort = (int) $sort;
        return $this->children[$key];
    }

    /**
     * Removes a custom menu item that is a child or descendant to the current menu.
     *
     * Returns true if child was found and removed.
     *
     * @param menu_item $menuitem
     * @return bool
     */
    public function remove_child(menu_item $menuitem) {
        $removed = false;
        if (($key = array_search($menuitem, $this->children)) !== false) {
            unset($this->children[$key]);
            $this->children = array_values($this->children);
            $removed = true;
        } else {
            foreach ($this->children as $child) {
                if ($removed = $child->remove_child($menuitem)) {
                    break;
                }
            }
        }
        return $removed;
    }

    /**
     * Returns the text for this item
     *
     * @return string
     */
    public function get_text() {
        return $this->text;
    }

    /**
     * Returns the uniqueid for this item
     *
     * @return string
     */
    public function get_uniqueid() {
        return $this->uniqueid;
    }

    /**
     * Returns the url for this item
     *
     * @return moodle_url
     */
    public function get_url() {
        return $this->url;
    }

    /**
     * Sorts and returns the children for this item
     *
     * @return array
     */
    public function get_children() {
        $this->sort();
        return $this->children;
    }

    /**
     * Gets the sort order for this child
     *
     * @return int
     */
    public function get_sort_order() {
        return $this->sort;
    }

    /**
     * Gets the parent this child belong to
     *
     * @return menu_item
     */
    public function get_parent() {
        return $this->parent;
    }

    /**
     * Sorts the children this item has
     */
    public function sort() {
        usort($this->children, array('local_mcms\\menu\\menu', 'sort_custom_menu_items'));
    }

    /**
     * Returns true if this item has any children
     *
     * @return bool
     */
    public function has_children() {
        return (count($this->children) > 0);
    }

    /**
     * Sets the text for the node
     *
     * @param string $text
     */
    public function set_text($text) {
        $this->text = (string) $text;
    }

    /**
     * Sets the uniqueid for the node
     *
     * @param string $uniqueid
     */
    public function set_uniqueid($uniqueid) {
        $this->uniqueid = (string) $uniqueid;
    }

    /**
     * Sets the url for the node
     *
     * @param moodle_url $url
     */
    public function set_url(moodle_url $url) {
        $this->url = $url;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return \stdClass
     * @throws \coding_exception|\dml_exception
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;

        require_once($CFG->libdir . '/externallib.php');

        $syscontext = context_system::instance();

        $context = new \stdClass();
        $context->text = external_format_string($this->text, $syscontext->id);
        $context->url = $this->url ? $this->url->out() : null;
        $context->title = external_format_string($this->text, $syscontext->id);
        $context->sort = $this->sort;
        $context->children = array();
        if (preg_match("/^#+$/", $this->text)) {
            $context->divider = true;
        }
        $context->haschildren = !empty($this->children) && (count($this->children) > 0);
        foreach ($this->children as $child) {
            $child = $child->export_for_template($output);
            array_push($context->children, $child);
        }

        return $context;
    }
}
