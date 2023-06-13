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
 * Export options for Profiles
 *
 * @package     gradeexport_profiles
 * @copyright   2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->dirroot.'/grade/export/ods/grade_export_ods.php');
require_once($CFG->dirroot.'/grade/export/xls/grade_export_xls.php');
require_once($CFG->dirroot.'/grade/export/txt/grade_export_txt.php');
require_once($CFG->dirroot.'/grade/export/xml/grade_export_xml.php');
require_once('grade_export_profiles_form.php');

$id = required_param('id', PARAM_INT); // Course id.
$PAGE->set_url('/grade/export/profiles/export.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new \moodle_exception('invalidcourseid');
}

require_login($course);
$context = context_course::instance($id);
$groupid = groups_get_course_group($course, true);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/profiles:view', $context);

// We need to call this method here before any output otherwise the menu won't display.
// If you use this method without this check, will break the direct grade exporting (without publishing).
$key = optional_param('key', '', PARAM_RAW);
if (!empty($CFG->gradepublishing) && !empty($key)) {
    $actionbar = new \core_grades\output\export_publish_action_bar($context, 'ods');
    print_grade_page_head($COURSE->id, 'export', 'ods',
        get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_profiles'),
        false, false, true, null, null, null, $actionbar);
}

if (groups_get_course_groupmode($COURSE) == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
    if (!groups_is_member($groupid, $USER->id)) {
        throw new \moodle_exception('cannotaccessgroup', 'grades');
    }
}

$params = array(
    'publishing' => true,
    'simpleui' => true,
    'multipledisplaytypes' => true
);
$mform = new grade_export_profiles_form(null, $params);
$data = $mform->get_data();

// Building an array for saving options in the database.
// We can't group these options in the form like the grades in itemids
// inside $data because it would break compatibility with exporting
// functions like grade_export_ods.
$profileoptions = array(
    "fileformat" => $data->fileformat,
    "feedback" => $data->export_feedback,
    "onlyactive" => $data->export_onlyactive,
    "real" => $data->display['real'],
    "percentage" => $data->display['percentage'],
    "letter" => $data->display['letter'],
    "decimals" => $data->decimals,
    "separator" => grade_export_profiles_form::SEPARATOR[$data->separator],
);

if (!empty($data->savebutton)) {
    require_sesskey();
    gradeexport_profiles_save_profile($data->itemids, $profileoptions, 1, $data->selectprofile, '');
    $url = new moodle_url('/grade/export/profiles/index.php', array('id' => $id));
    redirect($url);
} else if (!empty($data->sendbutton)) {
    require_sesskey();
    gradeexport_profiles_save_profile($data->itemids, $profileoptions, 1, '', $data->nameinput);
    $url = new moodle_url('/grade/export/profiles/index.php', array('id' => $id));
    redirect($url);
} else if (!empty($data->removebutton)) {
    // Display confirmation prompt.
    echo $OUTPUT->header();
    echo $OUTPUT->box_start('noticebox');
    $buttoncontinue = new single_button(new moodle_url('/grade/export/profiles/delete.php',
                        array('id' => $id, 'profileid' => $data->selectprofile, 'sesskey' => sesskey())), get_string('delete'));
    $buttoncancel = new single_button(new moodle_url('/grade/export/profiles/index.php',
                        array('id' => $id)), get_string('cancel'));
    echo $OUTPUT->confirm(get_string('confirmdelete', 'gradeexport_profiles',
        gradeexport_profiles_get_profilename($data->selectprofile)), $buttoncontinue, $buttoncancel);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
} else if (!empty($data->submitbutton)) {
    require_sesskey();
    if (in_array($data->selectprofile, array('a', 'b', 'c', 'd', 'e'))) {
        $last = 1;
    } else {
        $last = 0;
        gradeexport_profiles_set_last($data->selectprofile);
    }

    gradeexport_profiles_save_profile($data->itemids, $profileoptions, $last, '', "Last State");

    if ($data->fileformat == 0) {
        $export = new grade_export_ods($course, $groupid, $data);
    } else if ($data->fileformat == 1) {
        $export = new grade_export_xls($course, $groupid, $data);
    } else if ($data->fileformat == 2) {
        $export = new grade_export_txt($course, $groupid, $data);
    }

    // If the gradepublishing is enabled and user key is selected print the grade publishing link.
    if (!empty($CFG->gradepublishing) && !empty($key)) {
        groups_print_course_menu($course, 'index.php?id='.$id);
        echo $export->get_grade_publishing_url();
        echo $OUTPUT->footer();
    } else {
        $event = \gradeexport_profiles\event\grade_exported::create(array('context' => $context));
        $event->trigger();
        $export->print_grades();
    }
} else {
    require_sesskey();
    if ($data->selectprofile == 'b') {
        $profileid = gradeexport_profiles_get_profileid("Last State");
    } else {
        $profileid = $data->selectprofile;
    }
    gradeexport_profiles_set_last($profileid);
    $url = new moodle_url('/grade/export/profiles/index.php', array('id' => $id));
    redirect($url);
}
