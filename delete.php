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
 * Delete a profile.
 *
 * @package     gradeexport_profiles
 * @copyright   2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/grade/export/profiles/lib.php');

$profileid = optional_param('profileid', null, PARAM_INT);

$id                = required_param('id', PARAM_INT); // Course id.
$PAGE->set_url('/grade/export/profiles/delete.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new \moodle_exception('invalidcourseid');
}

require_login($course);
require_sesskey();

// Check if the profileid belongs to the current user.
$profilesrecords = $DB->get_records('gradeexport_profiles', array(
    'userid' => $USER->id,
    'id' => $profileid,
));
if (count($profilesrecords) > 0) {
    gradeexport_profiles_delete_profile($profileid);
}

$url = new moodle_url('/grade/export/profiles/index.php', array('id' => $id));
redirect($url);
