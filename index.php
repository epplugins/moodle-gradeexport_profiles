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
 * Main file to profiles gradebook export
 *
 * @package     gradeexport_profiles
 * @copyright   2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->dirroot.'/grade/export/profiles/grade_export_profiles_form.php');

$id = required_param('id', PARAM_INT); // It's course id.

$PAGE->set_url('/grade/export/profiles/index.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new \moodle_exception('invalidcourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/profiles:view', $context);

if (substr($CFG->version, 0, 8) > 20221128) {
    $actionbar = new \core_grades\output\export_action_bar($context, null, 'profiles');
    print_grade_page_head($COURSE->id, 'export', 'profiles',
        get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_profiles'),
        false, false, true, null, null, null, $actionbar);
    export_verify_grades($COURSE->id);
} else {
    print_grade_page_head($COURSE->id, 'export', 'profiles', get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_profiles'));
    export_verify_grades($COURSE->id);
}

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeexport/profiles:publish', $context);
}

$actionurl = new moodle_url('/grade/export/profiles/export.php');
$formoptions = array(
    'publishing' => true,
    'simpleui' => true,
    'multipledisplaytypes' => true
);

$mform = new grade_export_profiles_form($actionurl, $formoptions);

$groupmode    = groups_get_course_groupmode($course);   // Groups are being used.
$currentgroup = groups_get_course_group($course, true);

if (($groupmode == SEPARATEGROUPS) &&
        (!$currentgroup) &&
        (!has_capability('moodle/site:accessallgroups', $context))) {
    echo $OUTPUT->heading(get_string("notingroup"));
    echo $OUTPUT->footer();
    die;
}

groups_print_course_menu($course, 'index.php?id='.$id);
echo '<div class="clearer"></div>';

$mform->display();

$PAGE->requires->js_call_amd('gradeexport_profiles/selectprofile', 'init');

$profilesrecords = $DB->get_records('gradeexport_profiles', array(
    'userid' => $USER->id,
    'courseid' => $COURSE->id
));

echo $OUTPUT->footer();
