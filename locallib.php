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

use \logstore_trax\src\controller as trax_controller;
use \logstore_trax\src\utils;


/**
 * Supported protocols.
 */
define('TRAXLAUNCH_PROTOCOL_TINCAN', 0);
define('TRAXLAUNCH_PROTOCOL_TINCAN_PROXY', 1);
define('TRAXLAUNCH_PROTOCOL_CMI5', 2);


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
        //TRAXLAUNCH_PROTOCOL_CMI5 => get_string('cmi5', 'traxlaunch'),
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
    $function = 'traxlaunch_launch_prepare_' . [
        TRAXLAUNCH_PROTOCOL_TINCAN => 'tincan',
        TRAXLAUNCH_PROTOCOL_TINCAN_PROXY => 'tincan_proxy',
        TRAXLAUNCH_PROTOCOL_CMI5 => 'cmi5',
    ][$activity->launchprotocol];
    if (!function_exists($function)) return false;
    return $function($activity, $cm, $course);
}

/**
 * Prepare for launching and return the launch URL.
 * Tin Can implementation.
 * 
 * @param stdClass $activity
 * @param stdClass $cm
 * @param stdClass $course
 * @return string|false
 */
function traxlaunch_launch_prepare_tincan($activity, $cm, $course) {
    global $USER;
    $controller = new trax_controller();

    // Launch link.
    $courseentry = $controller->activities->get_or_create_db_entry($course->id, 'course');
    $xapimodule = $controller->activities->get('traxlaunch', $activity->id, false, 'module', 'traxlaunch', 'mod_traxlaunch');
    return (new moodle_url($activity->launchurl, [
        'endpoint' => get_config('logstore_trax', 'lrs_endpoint') . '/',
        'auth' => 'Basic ' . base64_encode(get_config('logstore_trax', 'lrs_username') . ':' . get_config('logstore_trax', 'lrs_password')),
        'actor' => json_encode($controller->actors->get('user', $USER->id)),
        'registration' => $courseentry->uuid,
        'activity_id' => $xapimodule['id'] . '/content',
    ]))->out(false);
}

/**
 * Prepare for launching and return the launch URL.
 * Tin Can Proxy implementation.
 * 
 * @param stdClass $activity
 * @param stdClass $cm
 * @param stdClass $course
 * @return string|false
 */
function traxlaunch_launch_prepare_tincan_proxy($activity, $cm, $course) {
    global $USER, $DB, $CFG;
    $controller = new trax_controller();

    // Database update.
    $record = $DB->get_record('traxlaunch_status', ['userid' => $USER->id, 'activityid' => $activity->id, 'attempt' => 1]);
    if (!$record) {
        $record = (object)[
            'userid' => $USER->id,
            'activityid' => $activity->id,
            'attempt' => 1,
            'token' => utils::uuid(),
            'session_start' => time(),
            'first_access' => time(),
            'last_access' => time(),
        ];
        $DB->insert_record('traxlaunch_status', $record);
    } else {
        $record->token = utils::uuid();
        $record->session_start = time();
        $DB->update_record('traxlaunch_status', $record);
    }

    // Launch link.
    $endpoint = $CFG->wwwroot . '/mod/traxlaunch/proxy/' . $record->token . '/';
    $courseentry = $controller->activities->get_or_create_db_entry($course->id, 'course');
    $xapimodule = $controller->activities->get('traxlaunch', $activity->id, false, 'module', 'traxlaunch', 'mod_traxlaunch');
    return (new moodle_url($activity->launchurl, [
        'endpoint' => $endpoint,
        'auth' => 'Basic ' . base64_encode(':'),
        'actor' => '{"mbox": "mailto:'.$activity->id.'@traxlaunch.mod"}',
        'registration' => $courseentry->uuid,
        'activity_id' => $xapimodule['id'] . '/content',
    ]))->out(false);
}

/**
 * Prepare for launching and return the launch URL.
 * CMI5 implementation.
 * 
 * @param stdClass $activity
 * @param stdClass $cm
 * @param stdClass $course
 * @return string|false
 */
function traxlaunch_launch_prepare_cmi5($activity, $cm, $course) {
    global $USER;
    $controller = new trax_controller();

    return false;

    // Database update.
    // ...

    // xAPI State update.
    // ...

    // Launch link.
    // ...
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



