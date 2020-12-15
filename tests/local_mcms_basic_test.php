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
 * @package   block_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_mcms\page;
use local_mcms\page_utils;

defined('MOODLE_INTERNAL') || die();
require_once('lib.php');
/**
 * Basic Tests for LCMS pages
 *
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mcms_basic_test extends advanced_testcase {
    use mcms_test_base;

    public function test_page_create_retrieve() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = context_user::instance($user->id);
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        $this->create_page($usercontext, (object) self::SAMPLE_PAGE, $rolesid,
            array('pexels-simon-migaj-747964.jpg' => 'illustration.jpg'), array('pexels-simon-migaj-747964.jpg' => 'image1.jpg'));

        $this->assertEquals(1, page::count_records());
        $page = page::get_record(array('idnumber' => self::SAMPLE_PAGE['idnumber']));
        $pagedata = $page->to_record();
        unset($pagedata->timemodified);
        unset($pagedata->timecreated);
        unset($pagedata->usermodified);
        unset($pagedata->id);
        $this->assertEquals(self::SAMPLE_PAGE, (array) $pagedata);
    }

    public function test_page_access_page() {
        global $DB;
        $this->resetAfterTest();

        $pagecreatoruser = $this->getDataGenerator()->create_user();
        $this->setUser($pagecreatoruser);
        $usercontext = context_user::instance($pagecreatoruser->id);
        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'guest\',\'manager\') ');
        $this->create_page($usercontext, (object) self::SAMPLE_PAGE, $rolesid,
            array('pexels-simon-migaj-747964.jpg' => 'illustration.jpg'), array('pexels-simon-migaj-747964.jpg' => 'image1.jpg'));

        $rolessnid = $DB->get_records_menu('role', null, $sort = '', $fields = 'shortname,id');

        $systemcontext = context_system::instance();
        $manageruser = $this->getDataGenerator()->create_user();
        $studentuser = $this->getDataGenerator()->create_user();
        role_assign($rolessnid['manager'], $manageruser->id, $systemcontext->id);
        role_assign($rolessnid['student'], $studentuser->id, $systemcontext->id);

        $guestuser = guest_user();

        $page = page::get_record(array('idnumber' => self::SAMPLE_PAGE['idnumber']));

        $this->assertTrue(page::can_view_page($manageruser, $page, $systemcontext));
        $this->assertFalse(page::can_view_page($studentuser, $page, $systemcontext));
        $this->assertTrue(page::can_view_page($guestuser, $page, $systemcontext));

        $rolesid = $DB->get_fieldset_select('role', 'id', 'shortname IN(\'manager\') ');
        $page->update_associated_roles($rolesid);

        $this->assertTrue(page::can_view_page($manageruser, $page, $systemcontext));
        $this->assertFalse(page::can_view_page($studentuser, $page, $systemcontext));
        $this->assertFalse(page::can_view_page($guestuser, $page, $systemcontext));
    }
    // @codingStandardsIgnoreStart
    // phpcs:disable
    const SAMPLE_PAGE = array(
        'title' => 'Titre test',
        'shortname' => 'Titre Test',
        'idnumber' => 'prettyurl',
        'description' => '<p dir="ltr" style="text-align: left;"><img src="@@PLUGINFILE@@/pexels-simon-migaj-747964.jpg" alt="" width="1280" height="853" role="presentation" class="img-responsive atto_image_button_text-bottom"><br></p><p dir="ltr" style="text-align: left;"><br></p><p dir="ltr" style="text-align: left;">dfqsfgdqsggsq</p><p dir="ltr" style="text-align: left;"><br></p><p dir="ltr" style="text-align: left;">Test</p>',
        'descriptionformat' => '1',
        'parent' => '0',
        'style' => 'default',
        'ctalink' => '/course/index.php',
        'parentmenu' => 'none',
        'menusortorder' => '0'
    );
    // phpcs:enable
    // @codingStandardsIgnoreEnd
}