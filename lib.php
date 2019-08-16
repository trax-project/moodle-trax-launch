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
 * Trax Launch for Moodle.
 *
 * @package    mod_traxlaunch
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return the list if Moodle features this module supports.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function traxlaunch_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return false;
        case FEATURE_PLAGIARISM:
            return false;
        case FEATURE_COMMENT:
            return true;
        default:
            return null;
    }
} 

/**
 * Add traxlaunch instance.
 * 
 * @param stdClass $data
 * @param mod_traxlaunch_mod_form $mform
 * @return int new traxlaunch instance id
 */
function traxlaunch_add_instance($data, $mform = null) {
    global $DB;

    // Set data.
    $data->timemodified = time();

    // Record it.
    $data->id = $DB->insert_record('traxlaunch', $data);
    return $data->id;
}

/**
 * Update traxlaunch instance.
 * 
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function traxlaunch_update_instance($data, $mform) {
    global $DB;

    // Set data.
    $data->timemodified = time();
    $data->id = $data->instance;

    // Record it.
    $DB->update_record('traxlaunch', $data);
    return true;
}

/**
 * Delete traxlaunch instance.
 * 
 * @param int $id
 * @return bool true
 */
function traxlaunch_delete_instance($id) {
    global $DB;

    // Check existence.
    if (!$traxlaunch = $DB->get_record('traxlaunch', array('id'=>$id))) {
        return false;
    }

    // Delete it.
    $DB->delete_records('traxlaunch', array('id'=> $traxlaunch->id));
    return true;
}

/**
 * Returns all other caps used in module.
 * 
 * @return array
 */
function traxlaunch_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * 
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function traxlaunch_reset_userdata($data) {
    return array();
}

