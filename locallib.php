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
 * Supported protocols.
 */
define('TRAXLAUNCH_PROTOCOL_TINCAN', 0);
define('TRAXLAUNCH_PROTOCOL_TINCAN_PROXY', 1);
define('TRAXLAUNCH_PROTOCOL_CMI5', 2);
define('TRAXLAUNCH_PROTOCOL_CMI5_TRAXLRS', 3);
define('TRAXLAUNCH_PROTOCOL_CMI5_PROXY', 4);


/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  string $eventname    event name
 * @param  stdClass $traxlaunch  traxlaunch object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 */
function traxlaunch_tigger_module_event($eventname, $traxlaunch, $course, $cm, $context, $userid = null, $other = []) {
    global $USER;

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $traxlaunch->id,
        'relateduserid' => isset($userid) ? $userid : $USER->id 
    );
    if (!empty($other)) {
        $params['other'] = $other;
    }
    $eventclass = '\mod_traxlaunch\event\\' . $eventname;
    $event = $eventclass::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('traxlaunch', $traxlaunch);
    $event->trigger();

    // Completion.
    if ($eventname == 'course_module_viewed') {
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);
    }
}

/**
 * Return the list of supported protocols.
 *
 * @return array
 */
function traxlaunch_launch_protocols() {
    return [
        TRAXLAUNCH_PROTOCOL_TINCAN => get_string('tincan', 'traxlaunch'),
        TRAXLAUNCH_PROTOCOL_TINCAN_PROXY => get_string('tincan_proxy', 'traxlaunch'),
        TRAXLAUNCH_PROTOCOL_CMI5 => get_string('cmi5', 'traxlaunch'),
        TRAXLAUNCH_PROTOCOL_CMI5_TRAXLRS => get_string('cmi5_traxlrs', 'traxlaunch'),
        TRAXLAUNCH_PROTOCOL_CMI5_PROXY => get_string('cmi5_proxy', 'traxlaunch'),
    ];
}

/**
 * Prepare for launching and return the launch URL.
 * 
 * @param stdClass $activity
 * @param stdClass $cm
 * @param stdClass $course
 * @return string|false
 */
function traxlaunch_launch_prepare($activity, $cm, $course) {
    $class = '\mod_traxlaunch\src\launchers\\' . [
        TRAXLAUNCH_PROTOCOL_TINCAN => 'tincan',
        TRAXLAUNCH_PROTOCOL_TINCAN_PROXY => 'tincan_proxy',
        TRAXLAUNCH_PROTOCOL_CMI5 => 'cmi5',
        TRAXLAUNCH_PROTOCOL_CMI5_TRAXLRS => 'cmi5_traxlrs',
        TRAXLAUNCH_PROTOCOL_CMI5_PROXY => 'cmi5_proxy',
    ][$activity->launchprotocol] . '_launcher';
    return (new $class)->launch_url($activity, $cm, $course);
}

/**
 * Check a token validity and return the matching user.
 * 
 * @param string $token
 * @return bool|int
 */
function traxlaunch_check_token($token) {
    global $DB;
    if (!$record = $DB->get_record('traxlaunch_status', ['token' => $token])) {
        return false;
    }
    if (time() - $record->session_start > get_config('traxlaunch', 'max_session_time') * 60) {
        return false;
    }
    return $record->userid;
}
