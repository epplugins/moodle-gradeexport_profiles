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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once('lib.php');

/**
 * Grade export profiles form definition.
 *
 * @package    gradeexport_profiles
 * @copyright  2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_export_profiles_form extends moodleform {

    /** @var array Values in the radio button group for separator in text file format. */
    public const SEPARATOR = array(
        'tab' => 0,
        'comma' => 1,
        'colon' => 2,
        'semicolon' => 3
    );

    /**
     * Define the form - called by parent constructor.
     */
    public function definition() {
        global $CFG, $COURSE, $USER, $DB;

        $mform =& $this->_form;
        if (isset($this->_customdata)) {  // Hardcoding plugin names here is hacky.
            $features = $this->_customdata;
        } else {
            $features = array();
        }

        $mform->disable_form_change_checker();

        if (empty($features['simpleui'])) {
            debugging('Grade export plugin needs updating to support one step exports.', DEBUG_DEVELOPER);
        }

        $selectarray = array();
        $profiles = gradeexport_profiles_populate();
        $selectarray[] = $mform->createElement('select',
         'selectprofile', get_string('selectprofile', 'gradeexport_profiles'), $profiles);
        $selectarray[] = $mform->createElement('submit', 'savebutton', get_string('save'));
        $selectarray[] = $mform->createElement('submit', 'removebutton', get_string('remove'));
        $mform->addGroup($selectarray, 'selectgroup', '', array(' '), false);
        $profileid = gradeexport_profiles_get_id_last();
        if (array_key_exists($profileid, $profiles)) {
            $mform->setDefault('selectprofile', $profileid);
        } else if (array_key_exists('b', $profiles)) {
            $mform->setDefault('selectprofile', 'b');
        } else {
            $mform->setDefault('selectprofile', 'e');
        }
        $mform->disabledIf('savebutton', 'selectprofile', 'in', array('a', 'b', 'c', 'd', 'e'));
        $mform->addHelpButton('selectgroup', 'savebutton', 'gradeexport_profiles');
        $mform->disabledIf('removebutton', 'selectprofile', 'in', array('a', 'b', 'c', 'd', 'e'));

        $changesarray = array();
        $changesarray[] = $mform->createElement('static', 'changesnotsaved', ' ', ' ');
        $changesarray[] = $mform->createElement('static', 'changesnotsaved',
                                                get_string('changesnotsaved', 'gradeexport_profiles'),
                                                get_string('changesnotsaved', 'gradeexport_profiles'));
        $mform->addGroup($changesarray, 'changesgroup', '', array(' '), false);

        $savearray = array();
        $attributes = array('size' => '25',
                            'placeholder' => get_string('entername', 'gradeexport_profiles'),
                            'maxlength' => '20');
        $savearray[] = $mform->createElement('text', 'nameinput', '', $attributes);
        $mform->setType('nameinput', PARAM_TEXT);
        $savearray[] = $mform->createElement('submit', 'sendbutton', get_string('savenew', 'gradeexport_profiles'));
        $mform->addGroup($savearray, 'savegroup', '', array(' '), false);

        $mform->addElement('header', 'gradeitems', get_string('gradeitemsinc', 'grades'));
        $mform->setExpanded('gradeitems', true);

        if (!empty($features['idnumberrequired'])) {
            $mform->addElement('static', 'idnumberwarning', get_string('useridnumberwarning', 'grades'));
        }

        $switch = grade_get_setting($COURSE->id, 'aggregationposition', $CFG->grade_aggregationposition);

        // Grab the grade_seq for this course.
        $gseq = new grade_seq($COURSE->id, $switch);
        $profilegrades = gradeexport_profiles_get_grades($profileid);

        if ($gradeitems = $gseq->items) {
            $canviewhidden = has_capability('moodle/grade:viewhidden', context_course::instance($COURSE->id));

            foreach ($gradeitems as $gradeitem) {
                // Is the grade_item hidden? If so, can the user see hidden grade_items?
                if ($gradeitem->is_hidden() && !$canviewhidden) {
                    continue;
                }

                $isnew = ' ';

                if (!empty($features['idnumberrequired']) && empty($gradeitem->idnumber)) {
                    $mform->addElement('checkbox', 'itemids['.$gradeitem->id.']',
                            $gradeitem->get_name(), get_string('noidnumber', 'grades'));
                    $mform->hardFreeze('itemids['.$gradeitem->id.']');
                } else {
                    if (array_key_exists($gradeitem->id, $profilegrades)) {
                        $state = $profilegrades[$gradeitem->id];
                    } else {
                        $state = 1;
                        if (count($profilegrades) > 0) {
                            $isnew = '  '.get_string('newitem', 'gradeexport_profiles');
                        }
                    }

                    $mform->addElement('advcheckbox', 'itemids['.$gradeitem->id.']',
                            $gradeitem->get_name(), $isnew, array('group' => 1));
                    $mform->setDefault('itemids['.$gradeitem->id.']', $state);
                }
            }

        }

        $mform->addElement('header', 'options', get_string('exportformatoptions', 'grades'));
        if (!empty($features['simpleui'])) {
            $mform->setExpanded('options', true);
        }

        $profileopts = gradeexport_profiles_get_options($profileid);
        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'fileformat', '', 'ODS', 0);
        $radioarray[] = $mform->createElement('radio', 'fileformat', '', 'Excel', 1);
        $radioarray[] = $mform->createElement('radio', 'fileformat', '', get_string('text', 'gradeexport_profiles'), 2);
        $mform->addGroup($radioarray, 'radioformat', '', array(' '), false);
        if (array_key_exists('fileformat', $profileopts)) {
            $value = $profileopts['fileformat'];
        } else {
            $value = 0;
        }
        $mform->setDefault('fileformat', $value);

        $mform->addElement('advcheckbox', 'export_feedback', get_string('exportfeedback', 'grades'));
        if (array_key_exists('feedback', $profileopts)) {
            $exportfeedback = $profileopts['feedback'];
        } else {
            $exportfeedback = isset($CFG->grade_export_exportfeedback) ? $CFG->grade_export_exportfeedback : 0;
        }
        $mform->setDefault('export_feedback', $exportfeedback);
        $coursecontext = context_course::instance($COURSE->id);
        if (has_capability('moodle/course:viewsuspendedusers', $coursecontext)) {
            $mform->addElement('advcheckbox', 'export_onlyactive', get_string('exportonlyactive', 'grades'));
            $mform->setType('export_onlyactive', PARAM_BOOL);
            if (array_key_exists('onlyactive', $profileopts)) {
                $value = $profileopts['onlyactive'];
            } else {
                $value = 1;
            }
            $mform->setDefault('export_onlyactive', $value);
            $mform->addHelpButton('export_onlyactive', 'exportonlyactive', 'grades');
        } else {
            $mform->addElement('hidden', 'export_onlyactive', 1);
            $mform->setType('export_onlyactive', PARAM_BOOL);
            $mform->setConstant('export_onlyactive', 1);
        }

        if (empty($features['simpleui'])) {
            $options = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
            $mform->addElement('select', 'previewrows', get_string('previewrows', 'grades'), $options);
        }

        if (!empty($features['updategradesonly'])) {
            $mform->addElement('advcheckbox', 'updatedgradesonly', get_string('updatedgradesonly', 'grades'));
        }
        // Selections for decimal points and format, MDL-11667, defaults to site settings, if set
        // $default_gradedisplaytype = $CFG->grade_export_displaytype; .
        $options = array(GRADE_DISPLAY_TYPE_REAL       => get_string('real', 'grades'),
                         GRADE_DISPLAY_TYPE_PERCENTAGE => get_string('percentage', 'grades'),
                         GRADE_DISPLAY_TYPE_LETTER     => get_string('letter', 'grades'));

        if ($features['multipledisplaytypes']) {
            /*
             * Using advcheckbox because we need the grade display type (name) as key and grade display type (constant) as value.
             * The method format_column_name requires the lang file string and the format_grade method requires the constant.
             */
            $checkboxes = array();
            $checkboxes[] = $mform->createElement('advcheckbox',
                'display[real]', null, get_string('real', 'grades'), null, array(0, GRADE_DISPLAY_TYPE_REAL));
            $checkboxes[] = $mform->createElement('advcheckbox',
                'display[percentage]', null, get_string('percentage', 'grades'), null, array(0, GRADE_DISPLAY_TYPE_PERCENTAGE));
            $checkboxes[] = $mform->createElement('advcheckbox',
                'display[letter]', null, get_string('letter', 'grades'), null, array(0, GRADE_DISPLAY_TYPE_LETTER));
            $mform->addGroup($checkboxes, 'displaytypes', get_string('gradeexportdisplaytypes', 'grades'), ' ', false);

            if (array_key_exists('real', $profileopts)) {
                $value = $profileopts['real'];
            } else {
                $value = $CFG->grade_export_displaytype == GRADE_DISPLAY_TYPE_REAL;
            }
            $mform->setDefault('display[real]', $value);
            if (array_key_exists('percentage', $profileopts)) {
                $value = $profileopts['percentage'];
            } else {
                $value = $CFG->grade_export_displaytype == GRADE_DISPLAY_TYPE_PERCENTAGE;
            }
            $mform->setDefault('display[percentage]', $value);
            if (array_key_exists('letter', $profileopts)) {
                $value = $profileopts['letter'];
            } else {
                $value = $CFG->grade_export_displaytype == GRADE_DISPLAY_TYPE_LETTER;
            }
            $mform->setDefault('display[letter]', $value);
        } else {
            // Only used by XML grade export format.
            $mform->addElement('select', 'display', get_string('gradeexportdisplaytype', 'grades'), $options);
            $mform->setDefault('display', $CFG->grade_export_displaytype);
        }

        $options = array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5);
        $mform->addElement('select', 'decimals', get_string('gradeexportdecimalpoints', 'grades'), $options);
        if (array_key_exists('decimals', $profileopts)) {
            $value = $profileopts['decimals'];
        } else {
            $value = $CFG->grade_export_decimalpoints;
        }
        $mform->setDefault('decimals', $value);
        $mform->disabledIf('decimals', 'display', 'eq', GRADE_DISPLAY_TYPE_LETTER);

        $radio = array();
        $radio[] = $mform->createElement('radio', 'separator', null, get_string('septab', 'grades'), 'tab');
        $radio[] = $mform->createElement('radio', 'separator', null, get_string('sepcomma', 'grades'), 'comma');
        $radio[] = $mform->createElement('radio', 'separator', null, get_string('sepcolon', 'grades'), 'colon');
        $radio[] = $mform->createElement('radio', 'separator', null, get_string('sepsemicolon', 'grades'), 'semicolon');
        $mform->addGroup($radio, 'separator', get_string('separator', 'grades'), ' ', false);

        if (array_key_exists('separator', $profileopts)) {
            $sepkey = array_search($profileopts['separator'], self::SEPARATOR);
        } else {
            $sepkey = 'comma';
        }
        $mform->setDefault('separator', $sepkey);
        $mform->hideIf('separator', 'fileformat', 'neq', 2);

        if (!empty($CFG->gradepublishing) && !empty($features['publishing'])) {
            $mform->addElement('header', 'publishing', get_string('publishingoptions', 'grades'));
            if (!empty($features['simpleui'])) {
                $mform->setExpanded('publishing', false);
            }

            $options = array(get_string('nopublish', 'grades'), get_string('createnewkey', 'userkey'));
            $keys = $DB->get_records_select('user_private_key', "script='grade/export' AND instance=? AND userid=?",
                            array($COURSE->id, $USER->id));
            if ($keys) {
                foreach ($keys as $key) {
                    $options[$key->value] = $key->value; // TODO: add more details - ip restriction, valid until ??
                }
            }
            $mform->addElement('select', 'key', get_string('userkey', 'userkey'), $options);
            $mform->addHelpButton('key', 'userkey', 'userkey');
            $mform->addElement('static', 'keymanagerlink', get_string('keymanager', 'userkey'),
                    '<a href="'.$CFG->wwwroot.'/grade/export/keymanager.php?id='.$COURSE->id.'">'
                    .get_string('keymanager', 'userkey').'</a>');

            $mform->addElement('text', 'iprestriction', get_string('keyiprestriction', 'userkey'), array('size' => 80));
            $mform->addHelpButton('iprestriction', 'keyiprestriction', 'userkey');
            $mform->setDefault('iprestriction', getremoteaddr()); // Own IP - just in case somebody does not know what user key is.
            $mform->setType('iprestriction', PARAM_RAW_TRIMMED);

            $mform->addElement('date_time_selector', 'validuntil',
                                get_string('keyvaliduntil', 'userkey'),
                                array('optional' => true));
            $mform->addHelpButton('validuntil', 'keyvaliduntil', 'userkey');
            $mform->setDefault('validuntil', time() + 3600 * 24 * 7);
            // Only 1 week default duration - just in case somebody does not know what user key is .
            $mform->setType('validuntil', PARAM_INT);

            $mform->disabledIf('iprestriction', 'key', 'noteq', 1);
            $mform->disabledIf('validuntil', 'key', 'noteq', 1);
        }

        $mform->addElement('hidden', 'id', $COURSE->id);
        $mform->setType('id', PARAM_INT);
        $submitstring = get_string('download');
        if (empty($features['simpleui'])) {
            $submitstring = get_string('submit');
        } else if (!empty($CFG->gradepublishing)) {
            $submitstring = get_string('export', 'grades');
        }

        $this->add_action_buttons(false, $submitstring);
    }

    /**
     * Overrides the mform get_data method.
     *
     * Created to force a value since the validation method does not work with multiple checkbox.
     *
     * @return stdClass form data object.
     */
    public function get_data() {
        global $CFG;
        $data = parent::get_data();
        if ($data && $this->_customdata['multipledisplaytypes']) {
            if (count(array_filter($data->display)) == 0) {
                // Ensure that a value was selected as the export plugins expect at least one value.
                if ($CFG->grade_export_displaytype == GRADE_DISPLAY_TYPE_LETTER) {
                    $data->display['letter'] = GRADE_DISPLAY_TYPE_LETTER;
                } else if ($CFG->grade_export_displaytype == GRADE_DISPLAY_TYPE_PERCENTAGE) {
                    $data->display['percentage'] = GRADE_DISPLAY_TYPE_PERCENTAGE;
                } else {
                    $data->display['real'] = GRADE_DISPLAY_TYPE_REAL;
                }
            }
        }
        return $data;
    }
}
