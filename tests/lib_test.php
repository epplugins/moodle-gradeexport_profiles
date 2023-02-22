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
 * Unit tests for gradeexport_profiles/lib.php.
 *
 * @package   gradeexport_profiles
 * @category  test
 * @copyright 2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_profiles;
use \stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/grade/export/profiles/lib.php');

/**
 * Unit tests for gradeexport_profiles/lib.php.
 *
 * @package   gradeexport_profiles
 * @category  test
 * @copyright 2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {

    /**
     * Only 1 profile with last == 1 for the user/course combination,
     * and it's the correct profile.
     *
     * @covers ::gradeexport_profiles_set_last
     */
    public function test_set_last() {
        global $PAGE, $DB;

        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $teacher1 = $this->getDataGenerator()->create_user(['username' => 'teacher1']);
        $teacher2 = $this->getDataGenerator()->create_user(['username' => 'teacher2']);

        /*
         * Enrol teachers.
         */

        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($teacher2->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($teacher1->id, $course2->id, $teacherrole->id);

        // Fill the tables. First profile to include is "Last State".
        // No need for options or grades.

        // New user: teacher 1 - course1 .
        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid1 = $DB->insert_record('gradeexport_profiles', $record);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid2 = $DB->insert_record('gradeexport_profiles', $record);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 2";
        $record->last = 0;
        $profileid3 = $DB->insert_record('gradeexport_profiles', $record);

        // New user: teacher 2 - course1 .
        $record = new stdClass();
        $record->userid = $teacher2->id;
        $record->courseid = $course1->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid4 = $DB->insert_record('gradeexport_profiles', $record);

        $record = new stdClass();
        $record->userid = $teacher2->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid5 = $DB->insert_record('gradeexport_profiles', $record);

        // New user: teacher 1 - course2 .
        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course2->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid6 = $DB->insert_record('gradeexport_profiles', $record);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course2->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid7 = $DB->insert_record('gradeexport_profiles', $record);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course2->id;
        $record->profilename = "profile 2";
        $record->last = 0;
        $profileid8 = $DB->insert_record('gradeexport_profiles', $record);

        // Set last to a profile that was not the last one.
        $this->setUser($teacher1->id);
        $PAGE->set_course($course1);

        gradeexport_profiles_set_last($profileid2);
        $profilesrecords = $DB->get_records('gradeexport_profiles', array(
            'userid' => $teacher1->id,
            'courseid' => $course1->id,
            'last' => 1,
        ));
        // Only 1 row last == 1 for this course-teacher.
        $this->assertEquals(1, count($profilesrecords));
        // And it is the correct profile.
        $this->assertEquals($profileid2, $profilesrecords[$profileid2]->id);

        // Should not be able to modify a profile of a different course.
        gradeexport_profiles_set_last($profileid7);
        $profilesrecord = $DB->get_record('gradeexport_profiles', array(
            'id' => $profileid7,
        ));
        // It was last = 0 for this profile.
        $this->assertEquals(0, ($profilesrecord->last));
        // Verify there is still only one profile as last = 1.
        $profilesrecords = $DB->get_records('gradeexport_profiles', array(
            'userid' => $teacher1->id,
            'courseid' => $course2->id,
            'last' => 1,
        ));
        $this->assertEquals(1, count($profilesrecords));

        // Should not be able to modify a profile of a different user.
        gradeexport_profiles_set_last($profileid5);
        $profilesrecord = $DB->get_record('gradeexport_profiles', array(
            'id' => $profileid5,
        ));
        // It was last = 0 for this profile.
        $this->assertEquals(0, ($profilesrecord->last));
        // Verify there is still only one profile as last = 1.
        $profilesrecords = $DB->get_records('gradeexport_profiles', array(
            'userid' => $teacher1->id,
            'courseid' => $course1->id,
            'last' => 1,
        ));
        $this->assertEquals(1, count($profilesrecords));

        // Clean the tables.
        $DB->delete_records('gradeexport_profiles', null);
    }

    /**
     * Test if the correct grades are retrieved.
     *
     * @covers ::gradeexport_profiles_get_grades
     */
    public function test_get_grades() {
        global $PAGE, $DB;

        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $teacher1 = $this->getDataGenerator()->create_user(['username' => 'teacher1']);
        $teacher2 = $this->getDataGenerator()->create_user(['username' => 'teacher2']);

        /*
         * Enrol teachers.
         */

        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($teacher2->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($teacher1->id, $course2->id, $teacherrole->id);

        // Fill the tables. First profile to include is "Last State".
        // No need for options.

        // New user: teacher 1 - course1.
        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid1 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid1, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid1, 'gradeid' => 2, 'state' => 0),
            array('profileid' => $profileid1, 'gradeid' => 3, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid2 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid2, 'gradeid' => 1, 'state' => 0),
            array('profileid' => $profileid2, 'gradeid' => 2, 'state' => 1),
            array('profileid' => $profileid2, 'gradeid' => 3, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 2";
        $record->last = 0;
        $profileid3 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid3, 'gradeid' => 1, 'state' => 0),
            array('profileid' => $profileid3, 'gradeid' => 2, 'state' => 0),
            array('profileid' => $profileid3, 'gradeid' => 3, 'state' => 1),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);

        // New user: teacher 2 - course1 .
        $record = new stdClass();
        $record->userid = $teacher2->id;
        $record->courseid = $course1->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid4 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid4, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid4, 'gradeid' => 2, 'state' => 1),
            array('profileid' => $profileid4, 'gradeid' => 3, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);

        $record = new stdClass();
        $record->userid = $teacher2->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid5 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid5, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid5, 'gradeid' => 2, 'state' => 0),
            array('profileid' => $profileid5, 'gradeid' => 3, 'state' => 1),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);

        // New user: teacher 1 - course2 .
        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course2->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid6 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid6, 'gradeid' => 4, 'state' => 1),
            array('profileid' => $profileid6, 'gradeid' => 5, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course2->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid7 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid7, 'gradeid' => 4, 'state' => 0),
            array('profileid' => $profileid7, 'gradeid' => 5, 'state' => 1),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course2->id;
        $record->profilename = "profile 2";
        $record->last = 0;
        $profileid8 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid8, 'gradeid' => 4, 'state' => 1),
            array('profileid' => $profileid8, 'gradeid' => 5, 'state' => 1),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);

        $this->setUser($teacher1->id);
        $PAGE->set_course($course1);

        // Own profile, correct course.
        $profilegrades = gradeexport_profiles_get_grades($profileid1);
        $expectedgrades = array(
            1 => '1',
            2 => '0',
            3 => '0',
        );
        sort($profilegrades);
        sort($expectedgrades);
        $this->assertTrue($profilegrades == $expectedgrades);

        // Own profile, wrong course.
        $profilegrades = gradeexport_profiles_get_grades($profileid6);
        $expectedgrades = array();
        $this->assertTrue($profilegrades == $expectedgrades);

        // Nonexisting profile.
        $profilegrades = gradeexport_profiles_get_grades(10101010103);
        $expectedgrades = array();
        $this->assertTrue($profilegrades == $expectedgrades);

        // Different user's profile.
        $profilegrades = gradeexport_profiles_get_grades($profileid4);
        $expectedgrades = array();
        $this->assertTrue($profilegrades == $expectedgrades);

    }

    /**
     * Test if the correct options are retrieved.
     *
     * @covers ::gradeexport_profiles_get_options
     */
    public function test_get_options() {
        global $PAGE, $DB;

        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $teacher1 = $this->getDataGenerator()->create_user(['username' => 'teacher1']);
        $teacher2 = $this->getDataGenerator()->create_user(['username' => 'teacher2']);

        /*
         * Enrol teachers.
         */

        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($teacher2->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($teacher1->id, $course2->id, $teacherrole->id);

        // Fill the tables. First profile to include is "Last State".
        // No need for grades.

        // New user: teacher 1 - course1 .
        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid1 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid1, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid1, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid1, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid1, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid1, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid1, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid1, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid1, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid2 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid2, 'opt' => 'fileformat', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid2, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid2, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid2, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 2";
        $record->last = 0;
        $profileid3 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid3, 'opt' => 'fileformat', 'value' => 1),
            array('profileid' => $profileid3, 'opt' => 'feedback', 'value' => 0),
            array('profileid' => $profileid3, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid3, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid3, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid3, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid3, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid3, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        // New user: teacher 2 - course1 .
        $record = new stdClass();
        $record->userid = $teacher2->id;
        $record->courseid = $course1->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid4 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid4, 'opt' => 'fileformat', 'value' => 1),
            array('profileid' => $profileid4, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid4, 'opt' => 'onlyactive', 'value' => 0),
            array('profileid' => $profileid4, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid4, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid4, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid4, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid4, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $record = new stdClass();
        $record->userid = $teacher2->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid5 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid5, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'feedback', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid5, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid5, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid5, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        // New user: teacher 1 - course2 .
        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course2->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid6 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid6, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid6, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid6, 'opt' => 'letter', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid6, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $this->setUser($teacher1->id);
        $PAGE->set_course($course1);

        // Own profile, correct course.
        $profileopts = gradeexport_profiles_get_options($profileid1);
        $expectedopts = array(
            'fileformat' => 0,
            'feedback' => 1,
            'onlyactive' => 1,
            'real' => 1,
            'percentage' => 0,
            'letter' => 0,
            'decimals' => 2,
            'separator' => 1,
        );
        sort($profileopts);
        sort($expectedopts);
        $this->assertTrue($profileopts == $expectedopts);

        // Own profile, wrong course.
        $profileopts = gradeexport_profiles_get_options($profileid6);
        $expectedopts = array();
        $this->assertTrue($profileopts == $expectedopts);

        // Nonexisting profile.
        $profileopts = gradeexport_profiles_get_options(10101010104);
        $expectedopts = array();
        $this->assertTrue($profileopts == $expectedopts);

        // Different user's profile.
        $profileopts = gradeexport_profiles_get_options($profileid4);
        $expectedopts = array();
        $this->assertTrue($profileopts == $expectedopts);

    }

    /**
     * Check the db tables after saving.
     *
     * @covers ::gradeexport_profiles_save_profile
     */
    public function test_save_profile() {
        global $PAGE, $DB, $USER, $COURSE;

        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $teacher1 = $this->getDataGenerator()->create_user(['username' => 'teacher1']);
        $teacher2 = $this->getDataGenerator()->create_user(['username' => 'teacher2']);

        /*
         * Enrol teachers.
         */

        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($teacher2->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($teacher1->id, $course2->id, $teacherrole->id);

        // Fill the tables. First profile to include is "Last State".
        // New user: teacher 1 - course1 .
        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid1 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid1, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid1, 'gradeid' => 2, 'state' => 0),
            array('profileid' => $profileid1, 'gradeid' => 3, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid1, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid1, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid1, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid1, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid1, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid1, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid1, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid1, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid2 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid2, 'gradeid' => 1, 'state' => 0),
            array('profileid' => $profileid2, 'gradeid' => 2, 'state' => 1),
            array('profileid' => $profileid2, 'gradeid' => 3, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid2, 'opt' => 'fileformat', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid2, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid2, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid2, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        // New user: teacher 2 - course1 .
        $record = new stdClass();
        $record->userid = $teacher2->id;
        $record->courseid = $course1->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid4 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid4, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid4, 'gradeid' => 2, 'state' => 1),
            array('profileid' => $profileid4, 'gradeid' => 3, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid4, 'opt' => 'fileformat', 'value' => 1),
            array('profileid' => $profileid4, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid4, 'opt' => 'onlyactive', 'value' => 0),
            array('profileid' => $profileid4, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid4, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid4, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid4, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid4, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $record = new stdClass();
        $record->userid = $teacher2->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid5 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid5, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid5, 'gradeid' => 2, 'state' => 0),
            array('profileid' => $profileid5, 'gradeid' => 3, 'state' => 1),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid5, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'feedback', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid5, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid5, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid5, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        // New user: teacher 1 - course2 .
        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course2->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid6 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid6, 'gradeid' => 4, 'state' => 1),
            array('profileid' => $profileid6, 'gradeid' => 5, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid6, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid6, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid6, 'opt' => 'letter', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid6, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $this->setUser($teacher1->id);
        $PAGE->set_course($course1);

        // Saving a new profile.
        $items = array(
            1 => 1,
            2 => 1,
            3 => 0,
        );
        $options = array(
            'fileformat' => 0,
            'feedback' => 0,
            'onlyactive' => 1,
            'real' => 1,
            'percentage' => 1,
            'letter' => 0,
            'decimals' => 2,
            'separator' => 1,
        );
        $profilename = 'A new prof';
        $last = 1;
        $profileid = gradeexport_profiles_save_profile($items, $options, $last, '', $profilename);
        $records = $DB->get_records('gradeexport_profiles', array(
            'id' => $profileid,
        ));
        $record = $records[$profileid];
        $expected = new stdClass();
        $expected->id = $profileid;
        $expected->userid = $USER->id;
        $expected->courseid = $COURSE->id;
        $expected->profilename = 'A new prof';
        $expected->last = 1;
        $this->assertTrue($expected == $record);

        $records = $DB->get_records('gradeexport_profiles_grds', array(
            'profileid' => $profileid,
        ));
        $entries = array();
        foreach ($records as $key => $record) {
            $entry = array(
                'profileid' => $record->profileid,
                'gradeid' => $record->gradeid,
                'state' => $record->state,
            );
            $entries[] = $entry;
        }
        $expected = array(
            array('profileid' => $profileid, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid, 'gradeid' => 2, 'state' => 1),
            array('profileid' => $profileid, 'gradeid' => 3, 'state' => 0),
        );
        // We are assuming that $records is sorted the same way as $expected .
        $this->assertTrue($expected == $entries);

        $records = $DB->get_records('gradeexport_profiles_opt', array(
            'profileid' => $profileid,
        ));
        $entries = array();
        foreach ($records as $key => $record) {
            $entry = array(
                'profileid' => $record->profileid,
                'opt' => $record->opt,
                'value' => $record->value,
            );
            $entries[] = $entry;
        }
        $expected = array(
            array('profileid' => $profileid, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid, 'opt' => 'feedback', 'value' => 0),
            array('profileid' => $profileid, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'percentage', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid, 'opt' => 'separator', 'value' => 1),
        );
        // We are assuming that $records is sorted the same way as $expected .
        $this->assertTrue($expected == $entries);

        // Profilename already exists: update existing profile instead of creating a new one.
        $items = array(
            1 => 1,
            2 => 1,
            3 => 1,  // This one is changed.
        );
        $options = array(
            'fileformat' => 1,  // This one is changed.
            'feedback' => 0,
            'onlyactive' => 1,
            'real' => 1,
            'percentage' => 1,
            'letter' => 0,
            'decimals' => 2,
            'separator' => 1,
        );
        $profilename = 'A new prof';
        $last = 0;
        $countp = $DB->count_records('gradeexport_profiles', array());
        $countg = $DB->count_records('gradeexport_profiles_grds', array());
        $counto = $DB->count_records('gradeexport_profiles_opt', array());
        $profileid = gradeexport_profiles_save_profile($items, $options, $last, '', $profilename);
        $count = $DB->count_records('gradeexport_profiles', array());
        $this->assertEquals($countp, $count);  // No new profile.
        $count = $DB->count_records('gradeexport_profiles_grds', array());
        $this->assertEquals($countg, $count);
        $count = $DB->count_records('gradeexport_profiles_opt', array());
        $this->assertEquals($counto, $count);
        $records = $DB->get_records('gradeexport_profiles', array(
            'id' => $profileid,
        ));
        $record = $records[$profileid];
        $expected = new stdClass();
        $expected->id = $profileid;
        $expected->userid = $USER->id;
        $expected->courseid = $COURSE->id;
        $expected->profilename = 'A new prof';
        $expected->last = 0;
        $this->assertTrue($expected == $record);

        $records = $DB->get_records('gradeexport_profiles_grds', array(
            'profileid' => $profileid,
        ));
        $entries = array();
        foreach ($records as $key => $record) {
            $entry = array(
                'profileid' => $record->profileid,
                'gradeid' => $record->gradeid,
                'state' => $record->state,
            );
            $entries[] = $entry;
        }
        $expected = array(
            array('profileid' => $profileid, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid, 'gradeid' => 2, 'state' => 1),
            array('profileid' => $profileid, 'gradeid' => 3, 'state' => 1),
        );
        // We are assuming that $records is sorted the same way as $expected .
        $this->assertTrue($expected == $entries);

        $records = $DB->get_records('gradeexport_profiles_opt', array(
            'profileid' => $profileid,
        ));
        $entries = array();
        foreach ($records as $key => $record) {
            $entry = array(
                'profileid' => $record->profileid,
                'opt' => $record->opt,
                'value' => $record->value,
            );
            $entries[] = $entry;
        }
        $expected = array(
            array('profileid' => $profileid, 'opt' => 'fileformat', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'feedback', 'value' => 0),
            array('profileid' => $profileid, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'percentage', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid, 'opt' => 'separator', 'value' => 1),
        );
        // We are assuming that $records is sorted the same way as $expected .
        $this->assertTrue($expected == $entries);

        // Profileid already exists: update existing profile instead of creating a new one.
        $items = array(
            1 => 1,
            2 => 0,  // This one is changed.
            3 => 1,
        );
        $options = array(
            'fileformat' => 1,
            'feedback' => 1,  // This one is changed.
            'onlyactive' => 1,
            'real' => 1,
            'percentage' => 1,
            'letter' => 0,
            'decimals' => 2,
            'separator' => 1,
        );
        $last = 0;
        $countp = $DB->count_records('gradeexport_profiles', array());
        $countg = $DB->count_records('gradeexport_profiles_grds', array());
        $counto = $DB->count_records('gradeexport_profiles_opt', array());

        $profileid = gradeexport_profiles_save_profile($items, $options, $last, $profileid, '');
        $count = $DB->count_records('gradeexport_profiles', array());
        $this->assertEquals($countp, $count);  // No new profile.
        $count = $DB->count_records('gradeexport_profiles_grds', array());
        $this->assertEquals($countg, $count);
        $count = $DB->count_records('gradeexport_profiles_opt', array());
        $this->assertEquals($counto, $count);
        $records = $DB->get_records('gradeexport_profiles', array(
            'id' => $profileid,
        ));
        $record = $records[$profileid];
        $expected = new stdClass();
        $expected->id = $profileid;
        $expected->userid = $USER->id;
        $expected->courseid = $COURSE->id;
        $expected->profilename = 'A new prof';
        $expected->last = 0;
        $this->assertTrue($expected == $record);

        $records = $DB->get_records('gradeexport_profiles_grds', array(
            'profileid' => $profileid,
        ));
        $entries = array();
        foreach ($records as $key => $record) {
            $entry = array(
                'profileid' => $record->profileid,
                'gradeid' => $record->gradeid,
                'state' => $record->state,
            );
            $entries[] = $entry;
        }
        $expected = array(
            array('profileid' => $profileid, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid, 'gradeid' => 2, 'state' => 0),
            array('profileid' => $profileid, 'gradeid' => 3, 'state' => 1),
        );
        // We are assuming that $records is sorted the same way as $expected .
        $this->assertTrue($expected == $entries);

        $records = $DB->get_records('gradeexport_profiles_opt', array(
            'profileid' => $profileid,
        ));
        $entries = array();
        foreach ($records as $key => $record) {
            $entry = array(
                'profileid' => $record->profileid,
                'opt' => $record->opt,
                'value' => $record->value,
            );
            $entries[] = $entry;
        }
        $expected = array(
            array('profileid' => $profileid, 'opt' => 'fileformat', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'percentage', 'value' => 1),
            array('profileid' => $profileid, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid, 'opt' => 'separator', 'value' => 1),
        );
        // We are assuming that $records is sorted the same way as $expected .
        $this->assertTrue($expected == $entries);

        // Should not be able to modifiy data of different user.
        $items = array(
            1 => 0,
            2 => 1,
            3 => 0,
        );
        $options = array(
            'fileformat' => 1,
            'feedback' => 1,
            'onlyactive' => 1,
            'real' => 1,
            'percentage' => 1,
            'letter' => 0,
            'decimals' => 2,
            'separator' => 1,
        );
        $last = 0;
        $profileid = gradeexport_profiles_save_profile($items, $options, $last, $profileid5, '');
        $records = $DB->get_records('gradeexport_profiles', array(
            'id' => $profileid5,
        ));
        $record = $records[$profileid5];
        $expected = new stdClass();
        $expected->id = $profileid5;
        $expected->userid = $teacher2->id;
        $expected->courseid = $course1->id;
        $expected->profilename = 'profile 1';
        $expected->last = 0;
        $this->assertTrue($expected == $record);

        $records = $DB->get_records('gradeexport_profiles_grds', array(
            'profileid' => $profileid5,
        ));
        $entries = array();
        foreach ($records as $key => $record) {
            $entry = array(
                'profileid' => $record->profileid,
                'gradeid' => $record->gradeid,
                'state' => $record->state,
            );
            $entries[] = $entry;
        }
        $expected = array(
            array('profileid' => $profileid5, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid5, 'gradeid' => 2, 'state' => 0),
            array('profileid' => $profileid5, 'gradeid' => 3, 'state' => 1),
        );
        // We are assuming that $records is sorted the same way as $expected .
        $this->assertTrue($expected == $entries);

        $records = $DB->get_records('gradeexport_profiles_opt', array(
            'profileid' => $profileid5,
        ));
        $entries = array();
        foreach ($records as $key => $record) {
            $entry = array(
                'profileid' => $record->profileid,
                'opt' => $record->opt,
                'value' => $record->value,
            );
            $entries[] = $entry;
        }
        $expected = array(
            array('profileid' => $profileid5, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'feedback', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid5, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid5, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid5, 'opt' => 'separator', 'value' => 1),
        );
        // We are assuming that $records is sorted the same way as $expected .
        $this->assertTrue($expected == $entries);

    }


    /**
     * Check the db tables after deleting.
     *
     * @covers ::gradeexport_profiles_delete_profile
     */
    public function test_delete_profile() {
        global $PAGE, $DB;

        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $teacher1 = $this->getDataGenerator()->create_user(['username' => 'teacher1']);
        $teacher2 = $this->getDataGenerator()->create_user(['username' => 'teacher2']);

        /*
         * Enrol teachers.
         */

        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($teacher2->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($teacher1->id, $course2->id, $teacherrole->id);

        // Fill the tables. First profile to include is "Last State".

        // New user: teacher 1 - course1 .
        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid1 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid1, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid1, 'gradeid' => 2, 'state' => 0),
            array('profileid' => $profileid1, 'gradeid' => 3, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid1, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid1, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid1, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid1, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid1, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid1, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid1, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid1, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid2 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid2, 'gradeid' => 1, 'state' => 0),
            array('profileid' => $profileid2, 'gradeid' => 2, 'state' => 1),
            array('profileid' => $profileid2, 'gradeid' => 3, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid2, 'opt' => 'fileformat', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid2, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid2, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid2, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid2, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 2";
        $record->last = 0;
        $profileid3 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid3, 'gradeid' => 1, 'state' => 0),
            array('profileid' => $profileid3, 'gradeid' => 2, 'state' => 0),
            array('profileid' => $profileid3, 'gradeid' => 3, 'state' => 1),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid3, 'opt' => 'fileformat', 'value' => 1),
            array('profileid' => $profileid3, 'opt' => 'feedback', 'value' => 0),
            array('profileid' => $profileid3, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid3, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid3, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid3, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid3, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid3, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        // New user: teacher 2 - course1 .
        $record = new stdClass();
        $record->userid = $teacher2->id;
        $record->courseid = $course1->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid4 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid4, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid4, 'gradeid' => 2, 'state' => 1),
            array('profileid' => $profileid4, 'gradeid' => 3, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid4, 'opt' => 'fileformat', 'value' => 1),
            array('profileid' => $profileid4, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid4, 'opt' => 'onlyactive', 'value' => 0),
            array('profileid' => $profileid4, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid4, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid4, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid4, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid4, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $record = new stdClass();
        $record->userid = $teacher2->id;
        $record->courseid = $course1->id;
        $record->profilename = "profile 1";
        $record->last = 0;
        $profileid5 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid5, 'gradeid' => 1, 'state' => 1),
            array('profileid' => $profileid5, 'gradeid' => 2, 'state' => 0),
            array('profileid' => $profileid5, 'gradeid' => 3, 'state' => 1),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid5, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'feedback', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid5, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid5, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'letter', 'value' => 0),
            array('profileid' => $profileid5, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid5, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        // New user: teacher 1 - course2 .
        $record = new stdClass();
        $record->userid = $teacher1->id;
        $record->courseid = $course2->id;
        $record->profilename = "Last State";
        $record->last = 1;
        $profileid6 = $DB->insert_record('gradeexport_profiles', $record);
        $entries = array(
            array('profileid' => $profileid6, 'gradeid' => 4, 'state' => 1),
            array('profileid' => $profileid6, 'gradeid' => 5, 'state' => 0),
        );
        $DB->insert_records('gradeexport_profiles_grds', $entries);
        $entries = array(
            array('profileid' => $profileid6, 'opt' => 'fileformat', 'value' => 0),
            array('profileid' => $profileid6, 'opt' => 'feedback', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'onlyactive', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'real', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'percentage', 'value' => 0),
            array('profileid' => $profileid6, 'opt' => 'letter', 'value' => 1),
            array('profileid' => $profileid6, 'opt' => 'decimals', 'value' => 2),
            array('profileid' => $profileid6, 'opt' => 'separator', 'value' => 1),
        );
        $DB->insert_records('gradeexport_profiles_opt', $entries);

        $this->setUser($teacher1->id);
        $PAGE->set_course($course1);

        // Verify all rows for profileid are deleted.
        gradeexport_profiles_delete_profile($profileid2);
        $count = $DB->count_records('gradeexport_profiles', array('id' => $profileid2));
        $this->assertEquals(0, $count);
        $count = $DB->count_records('gradeexport_profiles_grds', array('profileid' => $profileid2));
        $this->assertEquals(0, $count);
        $count = $DB->count_records('gradeexport_profiles_opt', array('profileid' => $profileid2));
        $this->assertEquals(0, $count);

        // We can't delete "Last State".
        gradeexport_profiles_delete_profile($profileid1);
        $count = $DB->count_records('gradeexport_profiles', array('id' => $profileid1));
        $this->assertEquals(1, $count);
        $count = $DB->count_records('gradeexport_profiles_grds', array('profileid' => $profileid1));
        $this->assertEquals(3, $count);
        $count = $DB->count_records('gradeexport_profiles_opt', array('profileid' => $profileid1));
        $this->assertEquals(8, $count);

        // Should not be able to modifiy data of different course.
        gradeexport_profiles_delete_profile($profileid6);
        $count = $DB->count_records('gradeexport_profiles', array('id' => $profileid6));
        $this->assertEquals(1, $count);
        $count = $DB->count_records('gradeexport_profiles_grds', array('profileid' => $profileid6));
        $this->assertEquals(2, $count);
        $count = $DB->count_records('gradeexport_profiles_opt', array('profileid' => $profileid6));
        $this->assertEquals(8, $count);

        // Should not be able to modifiy data of different user.
        gradeexport_profiles_delete_profile($profileid5);
        $count = $DB->count_records('gradeexport_profiles', array('id' => $profileid5));
        $this->assertEquals(1, $count);
        $count = $DB->count_records('gradeexport_profiles_grds', array('profileid' => $profileid5));
        $this->assertEquals(3, $count);
        $count = $DB->count_records('gradeexport_profiles_opt', array('profileid' => $profileid5));
        $this->assertEquals(8, $count);

    }

}
