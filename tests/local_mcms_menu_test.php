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

namespace local_mcms;

use advanced_testcase;
use context_system;
use context_user;
use local_mcms\menu\menu;
use local_mcms_test_base;
use stdClass;

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
     * Maximum number of pages to create for testing.
     */
    const MAX_PAGE = 3;
    /**
     * Sample page definition
     */
    const SAMPLE_PAGE = [
        'title' => 'Titre test',
        'shortname' => 'Titre Test',
        'idnumber' => 'pageidnumber1',
        'description' => '<p dir="ltr" style="text-align: left;">
            <img src="@@PLUGINFILE@@/pexels-ella-olsson-1640777%20%284%29.jpg" alt="" width="1280" height="853" role="presentation"
             class="img-responsive atto_image_button_text-bottom"><br></p><p dir="ltr" style="text-align: left;"><br></p><p
             dir="ltr" style="text-align: left;">dfqsfgdqsggsq</p><p dir="ltr" style="text-align: left;"><br></p><p dir="ltr"
                          style="text-align: left;">Test</p>',
        'descriptionformat' => '1',
        'parent' => '0',
        'style' => 'default',
        'ctalink' => '/course/index.php',
        'menusortorder' => '-1',
        'timecreated' => '1606753532',
        'timemodified' => '1606758229',
        'usermodified' => '2',
    ];
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
     *
     * @covers \local_mcms\menu\menu::__construct
     */
    public function test_menu_without_pages() {
        $menu = new menu();
        $this->assertNotNull($menu);
        $this->assertCount(0, $menu->get_children());
        $this->assertEquals('root', $menu->get_text());
    }

    /**
     * Test that menu can be imported by definition
     *
     * @covers \local_mcms\menu\menu::__construct
     */
    public function test_menu_with_definition() {
        $menu = new menu(self::MENU_DEFINITION);
        $this->assertNotNull($menu);
        $this->assertCount(3, $menu->get_children());
        $this->assertCount(3, $menu->get_children()[0]->get_children());
        $this->assertEquals('Second level first item', $menu->get_children()[0]->get_children()[0]->get_text());
        $this->assertEquals('First level first item', $menu->get_children()[0]->get_text());
        $this->assertEquals(
            'http://www.moodle.com/partners/',
            $menu->get_children()[0]->get_children()[0]->get_url()->out()
        );
        $this->assertEquals('http://www.moodle.com/', $menu->get_children()[0]->get_url()->out());

    }

    /**
     * Test that menu in another language are ignored
     *
     * @covers \local_mcms\menu\menu::__construct
     */
    public function test_menu_with_definition_language() {
        $menu = new menu(self::MENU_DEFINITION, 'fr');
        $this->assertNotNull($menu);
        $this->assertCount(2, $menu->get_children()); // Here we should filter out everything that is marked for another
        // language.
        $this->assertCount(2, $menu->get_children()[0]->get_children());
        $this->assertEquals('Second level second item', $menu->get_children()[0]->get_children()[0]->get_text());
    }

    /**
     * Test adding page in menu
     *
     * @covers \page::create_page
     * @covers \local_mcms\menu\menu::get_children
     */
    public function test_menu_with_page_as_manager() {
        global $DB;
        $usercontext = context_user::instance($this->manager->id);
        $this->setUser($this->manager);

        $pagedef = (object) self::SAMPLE_PAGE;
        $pagedef->parentmenu = menu::PAGE_MENU_TOP;
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        $this->create_page(
            $usercontext,
            $pagedef,
            $rolesid,
            ['pexels-simon-migaj-747964.jpg' => 'illustration.jpg'],
            ['pexels-simon-migaj-747964.jpg' => 'image1.jpg']
        );

        $this->setUser($this->manager);
        $menu = new menu();
        $this->assertNotNull($menu);
        // Site Administration menu has been added automatically.
        $this->assertCount(2, $menu->get_children());
        $this->assertEquals('pageidnumber1', $menu->get_children()[0]->get_uniqueid());
    }

    /**
     * Test adding page in menu
     *
     * @covers \page::create_page
     * @covers \local_mcms\menu\menu::get_children
     */
    public function test_menu_with_page_as_user() {
        global $DB;
        $usercontext = context_user::instance($this->manager->id);
        $this->setUser($this->manager);

        $pagedef = (object) self::SAMPLE_PAGE;
        $pagedef->parentmenu = menu::PAGE_MENU_TOP;
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        $this->create_page(
            $usercontext,
            $pagedef,
            $rolesid,
            ['pexels-simon-migaj-747964.jpg' => 'illustration.jpg'],
            ['pexels-simon-migaj-747964.jpg' => 'image1.jpg']
        );
        $this->setUser($this->student);
        $menu = new menu();
        $this->assertNotNull($menu);
        $this->assertCount(0, $menu->get_children());
    }

    /**
     * Test sortorder when different from insertion order
     *
     * @covers \local_mcms\menu\menu::get_children
     */
    public function test_menu_with_page_sortorder() {
        global $DB;
        $usercontext = context_user::instance($this->manager->id);
        $this->setUser($this->manager);

        $pagemenusortorder = [2, 1, 0];
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        for ($pageindex = 0; $pageindex < self::MAX_PAGE; $pageindex++) {
            $pagedef = (object) self::SAMPLE_PAGE;
            $pagedef->parentmenu = menu::PAGE_MENU_TOP;
            $pagedef->idnumber = 'pageidnumber' . ($pageindex + 1);
            $pagedef->menusortorder = $pagemenusortorder[$pageindex];
            $this->create_page(
                $usercontext,
                $pagedef,
                $rolesid,
                ['pexels-simon-migaj-747964.jpg' => 'illustration.jpg'],
                ['pexels-simon-migaj-747964.jpg' => 'image1.jpg']
            );
        }
        $this->setUser($this->manager);
        $menu = new menu();
        $this->assertNotNull($menu);
        // Site Administration menu has been added automatically.
        $this->assertCount(4, $menu->get_children());
        $uniqueids = array_map(function ($item) {
            return $item->get_uniqueid();
        }, $menu->get_children());
        $this->assertEquals([
            'pageidnumber2', // Page 2 was inserted after but should appear first.
            'pageidnumber1', // Page 2 was inserted first but should appear second.
            'pageidnumber3', // Page 3 was inserted last and appear last as the sort order was null.
            'administrationsite', // Last is site administration.
        ],
            array_values($uniqueids)
        );
    }

    /**
     * Test adding page in submenu (existing defined menu)
     *
     * @covers \local_mcms\menu\menu::get_children
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
            $this->create_page(
                $usercontext,
                $pagedef,
                $rolesid,
                ['pexels-simon-migaj-747964.jpg' => 'illustration.jpg'],
                ['pexels-simon-migaj-747964.jpg' => 'image1.jpg']
            );
        }
        $this->setUser($this->manager);
        $menu = new menu(self::MENU_DEFINITION);
        $this->assertNotNull($menu);
        // Site Administration menu has been added automatically.
        $this->assertCount(5, $menu->get_children());
        $this->assertEquals('pageidnumber1', $menu->get_children()[3]->get_uniqueid());
        // Page 1 should be at the end of the list.
        $this->assertEquals('pageidnumber2', $menu->get_children()[1]->get_children()[0]->get_uniqueid());
        // Page 2 should be under firstlevelfr as the first child.

    }

    /**
     * Test adding page in submenu (existing defined menu)
     *
     * @covers \local_mcms\menu\menu::get_children
     */
    public function test_page_in_submenu_parentid() {
        global $DB;
        $usercontext = context_user::instance($this->manager->id);
        $this->setUser($this->manager);
        $pageparentmenu = ['top', 'firstlevelfr', 'pageidnumber2'];
        $pagedef = (object) self::SAMPLE_PAGE;
        $pagedef->parent = 0;
        $pagedef->parentmenu = menu::PAGE_MENU_TOP;
        $pagedef->idnumber = 'page-top';
        $pagedef->menusortorder = 1;
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        $this->create_page(
            $usercontext,
            $pagedef,
            $rolesid,
            ['pexels-simon-migaj-747964.jpg' => 'illustration.jpg'],
            ['pexels-simon-migaj-747964.jpg' => 'image1.jpg']
        );
        $parentpage = page::get_record(['idnumber' => 'page-top']);
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        for ($pageindex = 0; $pageindex < self::MAX_PAGE; $pageindex++) {
            $pagedef = (object) self::SAMPLE_PAGE;
            $pagedef->parent = $parentpage->get('id');
            $pagedef->idnumber = 'pageidnumber' . ($pageindex + 1);
            $pagedef->menusortorder = 50 + $pageindex;
            $this->create_page(
                $usercontext,
                $pagedef,
                $rolesid,
                ['pexels-simon-migaj-747964.jpg' => 'illustration.jpg'],
                ['pexels-simon-migaj-747964.jpg' => 'image1.jpg']
            );
        }
        $this->setUser($this->manager);
        $menu = new menu(self::MENU_DEFINITION);
        $this->assertNotNull($menu);
        // Site Administration menu has been added automatically.
        $this->assertCount(5, $menu->get_children());
        $this->assertEquals('page-top', $menu->get_children()[1]->get_uniqueid());
        // Page top should be in the middle of the list.
        $this->assertEquals(
            'pageidnumber1',
            $menu->get_children()[1]->get_children()[0]->get_uniqueid()
        ); // Page top should be in the middle.
        $this->assertEquals('pageidnumber2', $menu->get_children()[1]->get_children()[1]->get_uniqueid());
        // Page 2 should be under firstlevelfr as the first child.

    }

    /**
     * Get page in submenu
     *
     * @covers \local_mcms\menu\menu::get_children
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
        $this->create_page(
            $usercontext,
            $pagedef,
            $rolesid,
            ['pexels-simon-migaj-747964.jpg' => 'illustration.jpg'],
            ['pexels-simon-migaj-747964.jpg' => 'image1.jpg']
        );

        $this->setUser($this->manager);
        $menu = new menu(self::MENU_DEFINITION);
        $this->assertNotNull($menu);
        // Site Administration menu has been added automatically.
        $this->assertCount(4, $menu->get_children());
        $this->assertEquals(
            'pageidnumber1',
            $menu->get_children()[0]->get_children()[0]->get_children()[0]->get_uniqueid()
        );
    }

    /**
     * Get page in submenu of a parent page
     *
     * @covers \local_mcms\menu\menu::get_children
     */
    public function test_children_of_another_page() {
        global $DB;
        $usercontext = context_user::instance($this->manager->id);
        $this->setUser($this->manager);

        $pagedef = (object) self::SAMPLE_PAGE;
        $pagedef->parentmenu = menu::PAGE_MENU_TOP;
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        $this->create_page(
            $usercontext,
            $pagedef,
            $rolesid,
            ['pexels-simon-migaj-747964.jpg' => 'illustration.jpg'],
            ['pexels-simon-migaj-747964.jpg' => 'image1.jpg']
        );

        $pagedef = (object) self::SAMPLE_PAGE;
        $parentpage = page::get_record(['idnumber' => 'pageidnumber1']);
        $pagedef->parent = $parentpage->get('id');
        $pagedef->parentmenu = 'none';
        $pagedef->idnumber = 'pageidnumber2';
        $this->create_page(
            $usercontext,
            $pagedef,
            $rolesid,
            ['pexels-simon-migaj-747964.jpg' => 'illustration.jpg'],
            ['pexels-simon-migaj-747964.jpg' => 'image1.jpg']
        );

        $this->setUser($this->manager);
        $menu = new menu(self::MENU_DEFINITION);
        $this->assertNotNull($menu);
        // Site Administration menu has been added automatically.
        $this->assertCount(5, $menu->get_children());
        $this->assertEquals(
            'pageidnumber1',
            $menu->get_children()[0]->get_uniqueid()
        );
        $this->assertEquals(
            'pageidnumber2',
            $menu->get_children()[0]->get_children()[0]->get_uniqueid()
        );
    }

    /**
     * Setup
     *
     */
    public function setUp(): void {
        global $DB;
        global $PAGE;
        $PAGE->set_context(context_system::instance());
        $PAGE->set_url(new \moodle_url('/'));
        $this->resetAfterTest();
        $rolessnid = $DB->get_records_menu('role', null, $sort = '', $fields = 'shortname,id');

        $systemcontext = context_system::instance();
        $this->manager = $this->getDataGenerator()->create_user();
        $this->student = $this->getDataGenerator()->create_user();
        role_assign($rolessnid['manager'], $this->manager->id, $systemcontext->id);
        role_assign($rolessnid['student'], $this->student->id, $systemcontext->id);
    }
}
