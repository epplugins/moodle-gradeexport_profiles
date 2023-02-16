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
 * Event observer.
 *
 * @package     gradeexport_profiles
 * @copyright   2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_profiles;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer.
 *
 * @package     gradeexport_profiles
 * @copyright   2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Removes all traces of a course that was deleted .
     *
     * @param \core\event\course_deleted $event
     */
    public static function delete_course(\core\event\course_deleted $event) {
        global $DB;

        $DB->delete_records_select('gradeexport_profiles_opt',
            "profileid IN (SELECT id FROM {gradeexport_profiles} WHERE courseid = $event->courseid)");

        $DB->delete_records_select('gradeexport_profiles_grds',
            "profileid IN (SELECT id FROM {gradeexport_profiles} WHERE courseid = $event->courseid)");

        $DB->delete_records('gradeexport_profiles', array('courseid' => $event->courseid));
    }
}
