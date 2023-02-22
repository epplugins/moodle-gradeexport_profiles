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
 * Defines various library functions.
 *
 * @package   gradeexport_profiles
 * @copyright 2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get the grades and states for the current profile.
 *
 * @param int $profileid
 * @return array
 */
function gradeexport_profiles_get_grades($profileid) {
    global $DB, $COURSE, $USER;

    $profilegrades = array();

    $record = $DB->get_record('gradeexport_profiles', array(
        'id'       => $profileid,
    ));
    if ($record != null && $record->courseid == $COURSE->id && $record->userid == $USER->id) {
        $gradesresult = $DB->get_records('gradeexport_profiles_grds', array(
        'profileid' => $profileid
        ));
        foreach ($gradesresult as $grade) {
            $profilegrades[$grade->gradeid] = $grade->state;
        }
    }
    return $profilegrades;
}

/**
 * Get options and states for the current profile.
 *
 * @param int $profileid
 * @return array
 */
function gradeexport_profiles_get_options($profileid) {
    global $DB, $COURSE, $USER;

    $profileopts = array();

    $record = $DB->get_record('gradeexport_profiles', array(
        'id'       => $profileid,
    ));
    if ($record != null && $record->courseid == $COURSE->id && $record->userid == $USER->id) {
        $optionsresult = $DB->get_records('gradeexport_profiles_opt', array('profileid' => $profileid));
        // $profileopts = array();
        foreach ($optionsresult as $option) {
            $profileopts[$option->opt] = $option->value;
        }
        // return $profileopts;
    // } else {
        // return null;
    }
    return $profileopts;
}

/**
 * Get the id of the last used profile.
 *
 * @return int
 */
function gradeexport_profiles_get_id_last() {
    global $USER, $COURSE, $DB;

    return $DB->get_field('gradeexport_profiles', 'id', array(
        'userid' => $USER->id,
        'courseid' => $COURSE->id,
        'last' => 1)
        );
}

/**
 * Get the id of a profile.
 *
 * @param string $profilename
 * @return int
 */
function gradeexport_profiles_get_profileid($profilename) {
    global $USER, $COURSE, $DB;
    return $DB->get_field_sql(
        'SELECT id
        FROM {gradeexport_profiles}
        WHERE userid = ?
        AND courseid = ?
        AND profilename = ?',
        array($USER->id, $COURSE->id, $profilename)
        );
}

/**
 * Get the name of a profile.
 *
 * @param int $profileid
 * @return string
 */
function gradeexport_profiles_get_profilename($profileid) {
    global $USER, $COURSE, $DB;

    $record = $DB->get_record('gradeexport_profiles', array(
        'id'       => $profileid,
    ));
    if ($record->courseid == $COURSE->id && $record->userid == $USER->id) {
        return $DB->get_field('gradeexport_profiles', 'profilename', array(
            'userid' => $USER->id,
            'courseid' => $COURSE->id,
            'id' => $profileid)
        );
    } else {
        return null;
    }
}

/**
 * Builds the array of options for element select.
 *
 * a,b,c,d are always present.
 * The profiles created by the user will be added using their ids as values.
 *
 * @return array
 */
function gradeexport_profiles_populate() {
    global $USER, $COURSE, $DB;

    $profilesrecords = $DB->get_records('gradeexport_profiles', array(
        'userid' => $USER->id,
        'courseid' => $COURSE->id
    ));

    if (count($profilesrecords) == 0) {
        $profiles = array(
            'e' => '',
            'a' => get_string('newprofile', 'gradeexport_profiles'),
            'c' => get_string('selectall'),
            'd' => get_string('selectnone', 'gradeexport_profiles'),
        );
    } else {
        $profiles = array(
            'a' => get_string('newprofile', 'gradeexport_profiles'),
            'b' => get_string('laststate', 'gradeexport_profiles'),
            'c' => get_string('selectall'),
            'd' => get_string('selectnone', 'gradeexport_profiles'),
        );
        foreach ($profilesrecords as $prof) {
            if ($prof->profilename !== 'Last State') {
                $profiles[$prof->id] = $prof->profilename;
            }
        }
    }

    return $profiles;
}

/**
 * Sets last = 1 to the profile.
 *
 * @param int $profileid
 */
function gradeexport_profiles_set_last($profileid) {
    global $DB, $COURSE, $USER;

    $record = $DB->get_record('gradeexport_profiles', array(
        'id'       => $profileid,
    ));
    if ($record != null && $record->courseid == $COURSE->id && $record->userid == $USER->id) {
        // TODO: add transactions.
        $oldprofileid = gradeexport_profiles_get_id_last();
        $record = new stdClass;
        $record->id = $oldprofileid;
        $record->last = 0;
        $DB->update_record('gradeexport_profiles', $record);
        $record = new stdClass;
        $record->id = $profileid;
        $record->last = 1;
        $DB->update_record('gradeexport_profiles', $record);
    }
}

/**
 * Sets last = 0 to the current last profile.
 *
 * To be used before updating last = 1 to a different profile.
 */
function gradeexport_profiles_unset_last() {
    global $DB;
    $oldprofileid = gradeexport_profiles_get_id_last();
    $record = new stdClass;
    $record->id = $oldprofileid;
    $record->last = 0;
    $DB->update_record('gradeexport_profiles', $record);
}

/**
 * Saves a profile.
 *
 * @param array $items
 * @param array $options
 * @param int $last
 * @param int $profileid
 * @param string $profilename
 */
function gradeexport_profiles_save_profile($items, $options, $last, $profileid = '', $profilename = '') {
    global $USER, $COURSE, $DB;

    if (!empty($profileid)) {
        $record = $DB->get_record('gradeexport_profiles', array(
            'id'       => $profileid,
        ));
        if ($record != null && $record->courseid == $COURSE->id && $record->userid == $USER->id) {
            if ($last == 1) {
                gradeexport_profiles_unset_last();
            }
            $record = new stdClass;
            $record->id = $profileid;
            $record->last = $last;
            $DB->update_record('gradeexport_profiles', $record);
        }
    }

    if (!empty($profilename)) {
        $profileid = gradeexport_profiles_get_profileid($profilename);

        $record = $DB->get_record('gradeexport_profiles', array(
            'id'       => $profileid,
        ));
        if ($record != null && $record->courseid == $COURSE->id && $record->userid == $USER->id) {
            if ($last == 1) {
                gradeexport_profiles_unset_last();
            }
            $record = new stdClass;
            $record->id = $profileid;
            $record->last = $last;
            $DB->update_record('gradeexport_profiles', $record);
        } else {
            if ($last == 1) {
                gradeexport_profiles_unset_last();
            }
            $record = new stdClass;
            $record->userid = $USER->id;
            $record->courseid = $COURSE->id;
            $record->profilename = $profilename;
            $record->last = $last;
            $profileid = $DB->insert_record('gradeexport_profiles', $record);
        }
    }

    $record = $DB->get_record('gradeexport_profiles', array(
        'id'       => $profileid,
    ));
    if ($record != null && $record->courseid == $COURSE->id && $record->userid == $USER->id) {
        $entries = array();
        foreach ($items as $key => $item) {
            $entry = array(
                'profileid' => $profileid,
                'gradeid' => $key,
                'state' => $item
            );
            $entries[] = $entry;
        }
        $DB->delete_records('gradeexport_profiles_grds', array('profileid' => $profileid));

        $DB->insert_records('gradeexport_profiles_grds', $entries);

        $entries = array();
        foreach ($options as $key => $value) {
            $entry = array(
                'profileid' => $profileid,
                'opt' => $key,
                'value' => $value
            );
            $entries[] = $entry;
        }
        $DB->delete_records('gradeexport_profiles_opt', array('profileid' => $profileid));

        $DB->insert_records('gradeexport_profiles_opt', $entries);
    }

    return $profileid;

}

/**
 * Delete a profile.
 *
 * @param int $profileid
 */
function gradeexport_profiles_delete_profile($profileid) {
    global $DB, $COURSE, $USER;

    $record = $DB->get_record('gradeexport_profiles', array(
        'id' => $profileid,
    ));
    if ($record != null && $record->profilename != "Last State" &&
        $record->courseid == $COURSE->id && $record->userid == $USER->id) {
        if ($profileid == gradeexport_profiles_get_id_last()) {
            gradeexport_profiles_set_last(gradeexport_profiles_get_profileid("Last State"));
        }

        $DB->delete_records('gradeexport_profiles', array('id' => $profileid));
        $DB->delete_records('gradeexport_profiles_grds', array('profileid' => $profileid));
        $DB->delete_records('gradeexport_profiles_opt', array('profileid' => $profileid));
    }
}
