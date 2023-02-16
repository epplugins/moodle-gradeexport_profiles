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
 * Handles changes when selecting profiles.
 *
 * @module     gradeexport_profiles/selectprofile
 * @copyright  2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    const selectp = document.getElementById("id_selectprofile");
    const checkboxes = document.querySelectorAll('[id^="id_itemids"]');
    const changesgrp = document.getElementById("fgroup_id_changesgroup");
    const savegrp = document.getElementById("fgroup_id_savegroup");
    savegrp.style.display = "none";
    changesgrp.style.display = "none";

    // If any of the checkboxes is clicked, a message is displayed.
    checkboxes.forEach(
        (currentValue) => {
            currentValue.addEventListener('click',
            function() {
                handleClickToNew(selectp, changesgrp, savegrp);
            });
        });
    document.getElementById("fgroup_id_radioformat").addEventListener('change', function() {
            handleClickToNew(selectp, changesgrp, savegrp);
        });
    document.getElementById("id_export_feedback").addEventListener('click', function() {
            handleClickToNew(selectp, changesgrp, savegrp);
        });
    document.getElementById("id_export_onlyactive").addEventListener('click', function() {
            handleClickToNew(selectp, changesgrp, savegrp);
        });
    document.getElementById("fgroup_id_displaytypes").addEventListener('click', function() {
            handleClickToNew(selectp, changesgrp, savegrp);
        });
    document.getElementById("id_decimals").addEventListener('change', function() {
            handleClickToNew(selectp, changesgrp, savegrp);
        });
    document.getElementById("fgroup_id_separator").addEventListener('change', function() {
            handleClickToNew(selectp, changesgrp, savegrp);
        });

    selectp.addEventListener('change', function() {
            handleSelectionChange(selectp, checkboxes, savegrp);
        });
};

/**
 * Callback for when the selection has changed.
 *
 * @param {Element} selectp
 * @param {NodeList} checkboxes
 * @param {Element} savegrp
 */
const handleSelectionChange = (selectp, checkboxes, savegrp) => {
    if (selectp.value == 'c') {
        savegrp.style.display = "none";
        checkboxes.forEach(
            (currentValue) => {
                currentValue.checked = true;
            }
            );
    } else if (selectp.value == 'd') {
        savegrp.style.display = "none";
        checkboxes.forEach(
            (currentValue) => {
                currentValue.checked = false;
            }
            );
    } else if (selectp.value == 'a') {
        savegrp.style.display = "";
    } else {
        document.getElementsByClassName("mform")[0].submit();
    }
};

/**
 * Callback for when any item is clicked.
 *
 * @param {Element} selectp
 * @param {Element} changesgrp
 * @param {Element} savegrp
 */
const handleClickToNew = (selectp, changesgrp, savegrp) => {
    changesgrp.style.display = "";
    const bcd = ["b", "c", "d"];

    if (bcd.includes(selectp.value)) {
        selectp.value = "a";
        savegrp.style.display = "";
    }
};