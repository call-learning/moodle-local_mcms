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
 * Basic Tests for LCMS pages
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_mcms\page_utils;

defined('MOODLE_INTERNAL') || die();
require_once('lib.php');

/**
 * Basic Tests for LCMS pages
 *
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mcms_menu_test extends advanced_testcase {
    use local_mcms_test_base;

    /**
     * @var stdClass|null $manager
     */
    protected $manager = null;
    /**
     * @var stdClass|null $student
     */
    protected $student = null;

    /**
     * Test basic menu constructor
     */
    public function test_menu_without_pages() {
        $menu = new \local_mcms\menu\menu();
        $this->assertNotNull($menu);
        $this->assertCount(0, $menu->get_children());
        $this->assertEquals('root', $menu->get_text());
    }

    /**
     * Test that menu can be imported by definition
     */
    public function test_menu_with_definition() {
        $menu = new \local_mcms\menu\menu(self::MENU_DEFINITION);
        $this->assertNotNull($menu);
        $this->assertCount(3, $menu->get_children());
        $this->assertCount(3, $menu->get_children()[0]->get_children());
        $this->assertEquals('Second level first item', $menu->get_children()[0]->get_children()[0]->get_text());
        $this->assertEquals('First level first item', $menu->get_children()[0]->get_text());
        $this->assertEquals('http://www.moodle.com/partners/',
            $menu->get_children()[0]->get_children()[0]->get_url()->out());
        $this->assertEquals('http://www.moodle.com/', $menu->get_children()[0]->get_url()->out());

    }

    /**
     * Test that menu in another language are ignored
     */
    public function test_menu_with_definition_language() {
        $menu = new \local_mcms\menu\menu(self::MENU_DEFINITION, 'fr');
        $this->assertNotNull($menu);
        $this->assertCount(2, $menu->get_children()); // Here we should filter out everything that is marked for another
        // language.
        $this->assertCount(2, $menu->get_children()[0]->get_children());
        $this->assertEquals('Second level second item', $menu->get_children()[0]->get_children()[0]->get_text());
    }

    /**
     * Test adding page in menu
     *
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function test_menu_with_page() {
        global $DB, $PAGE;
        $usercontext = context_user::instance($this->manager->id);
        $this->setUser($this->manager);

        $pagedef = (object) self::SAMPLE_PAGE;
        $pagedef->parentmenu = 'top';
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        $this->create_page($usercontext, $pagedef, $rolesid,
            array('pexels-simon-migaj-747964.jpg' => 'illustration.jpg'), array('pexels-simon-migaj-747964.jpg' => 'image1.jpg'));

        $this->setUser($this->manager);
        $menu = new \local_mcms\menu\menu();
        $this->assertNotNull($menu);
        $this->assertCount(1, $menu->get_children());
        $this->assertEquals('pageidnumber1', $menu->get_children()[0]->get_uniqueid());

        $this->setUser($this->student);
        $menu = new \local_mcms\menu\menu();
        $this->assertNotNull($menu);
        $this->assertCount(0, $menu->get_children());
    }

    /**
     * Maximum number of pages to create for testing.
     */
    const MAX_PAGE = 3;

    /**
     * Test sortorder when different from insertion order
     *
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function test_menu_with_page_sortorder() {
        global $DB;
        $usercontext = context_user::instance($this->manager->id);
        $this->setUser($this->manager);

        $pagemenusortorder = [2, 1, 0];
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        for ($pageindex = 0; $pageindex < self::MAX_PAGE; $pageindex++) {
            $pagedef = (object) self::SAMPLE_PAGE;
            $pagedef->parentmenu = 'top';
            $pagedef->idnumber = 'pageidnumber' . ($pageindex + 1);
            $pagedef->menusortorder = $pagemenusortorder[$pageindex];
            $this->create_page($usercontext, $pagedef, $rolesid,
                array('pexels-simon-migaj-747964.jpg' => 'illustration.jpg'),
                array('pexels-simon-migaj-747964.jpg' => 'image1.jpg'));
        }
        $this->setUser($this->manager);
        $menu = new \local_mcms\menu\menu();
        $this->assertNotNull($menu);
        $this->assertCount(3, $menu->get_children());
        $this->assertEquals('pageidnumber2', $menu->get_children()[0]->get_uniqueid());
        // Page 2 was inserted after but should appear first.
        $this->assertEquals('pageidnumber1', $menu->get_children()[1]->get_uniqueid());
        // Page 2 was inserted first but should appear second.
        $this->assertEquals('pageidnumber3', $menu->get_children()[2]->get_uniqueid());
        // Page 3 was inserted last and appear last as the sort order was null.

    }

    /**
     * Test adding page in submenu (existing defined menu)
     *
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function test_page_in_submenu() {
        global $DB;
        $usercontext = context_user::instance($this->manager->id);
        $this->setUser($this->manager);
        $pageparentmenu = ['top', 'firstlevelfr', 'pageidnumber2'];
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        for ($pageindex = 0; $pageindex < self::MAX_PAGE; $pageindex++) {
            $pagedef = (object) self::SAMPLE_PAGE;
            $pagedef->parentmenu = $pageparentmenu[$pageindex];
            $pagedef->idnumber = 'pageidnumber' . ($pageindex + 1);
            $pagedef->menusortorder = 50 + $pageindex;
            $this->create_page($usercontext, $pagedef, $rolesid,
                array('pexels-simon-migaj-747964.jpg' => 'illustration.jpg'),
                array('pexels-simon-migaj-747964.jpg' => 'image1.jpg'));
        }
        $this->setUser($this->manager);
        $menu = new \local_mcms\menu\menu(self::MENU_DEFINITION);
        $this->assertNotNull($menu);
        $this->assertCount(4, $menu->get_children());
        $this->assertEquals('pageidnumber1', $menu->get_children()[3]->get_uniqueid()); // Page 1 should be at the end
        // of the list.
        $this->assertEquals('pageidnumber2', $menu->get_children()[1]->get_children()[0]->get_uniqueid());
        // Page 2 should be under firstlevelfr as the first child.

    }

    /**
     * Get page in submenu
     *
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function test_page_in_submenu_with_defined_menu() {
        global $DB;
        $usercontext = context_user::instance($this->manager->id);
        $this->setUser($this->manager);

        $pagedef = (object) self::SAMPLE_PAGE;
        $pagedef->parentmenu = 'secondlevel';
        $pagedef->idnumber = 'pageidnumber1';
        $pagedef->menusortorder = 2;
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        $this->create_page($usercontext, $pagedef, $rolesid,
            array('pexels-simon-migaj-747964.jpg' => 'illustration.jpg'), array('pexels-simon-migaj-747964.jpg' => 'image1.jpg'));

        $this->setUser($this->manager);
        $menu = new \local_mcms\menu\menu(self::MENU_DEFINITION);
        $this->assertNotNull($menu);
        $this->assertCount(3, $menu->get_children());
        $this->assertEquals('pageidnumber1',
            $menu->get_children()[0]->get_children()[0]->get_children()[0]->get_uniqueid());
    }

    /**
     * Setup
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $rolessnid = $DB->get_records_menu('role', null, $sort = '', $fields = 'shortname,id');

        $systemcontext = context_system::instance();
        $this->manager = $this->getDataGenerator()->create_user();
        $this->student = $this->getDataGenerator()->create_user();
        role_assign($rolessnid['manager'], $this->manager->id, $systemcontext->id);
        role_assign($rolessnid['student'], $this->student->id, $systemcontext->id);
    }

    // @codingStandardsIgnoreStart
    // phpcs:disable
    /**
     * Sample page definition
     */
    const SAMPLE_PAGE = array(
        'title' => 'Titre test',
        'shortname' => 'Titre Test',
        'idnumber' => 'pageidnumber1',
        'description' => '<p dir="ltr" style="text-align: left;"><img src="@@PLUGINFILE@@/pexels-ella-olsson-1640777%20%284%29.jpg" alt="" width="1280" height="853" role="presentation" class="img-responsive atto_image_button_text-bottom"><br></p><p dir="ltr" style="text-align: left;"><br></p><p dir="ltr" style="text-align: left;">dfqsfgdqsggsq</p><p dir="ltr" style="text-align: left;"><br></p><p dir="ltr" style="text-align: left;">Test</p>',
        'descriptionformat' => '1',
        'parent' => '0',
        'style' => 'default',
        'ctalink' => '/course/index.php',
        'menusortorder' => '0',
        'timecreated' => '1606753532',
        'timemodified' => '1606758229',
        'usermodified' => '2',
    );

    /**
     * Sample menu defintion
     */
    const MENU_DEFINITION = <<<EOF
First level first item|firstlevel|http://www.moodle.com/
-Second level first item|secondlevel|http://www.moodle.com/partners/|en
-Second level second item|secondlevelseconditem|http://www.moodle.com/hq/
--Third level first item||http://www.moodle.com/jobs/
-Second level third item|http://www.moodle.com/development/
First level first item|firstlevelfr|http://www.moodle.com/|fr
First level first item|firstlevelen|http://www.moodle.com/|en
EOF;
    // phpcs:enable
    // @codingStandardsIgnoreEnd
}
