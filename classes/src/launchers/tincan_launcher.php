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
 * Proxy launch profile.
 *
 * @package    mod_traxlaunch
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_traxlaunch\src\launchers;

defined('MOODLE_INTERNAL') || die();

use logstore_trax\src\controller as trax_controller;
use moodle_url;

/**
 * TinCan launcher.
 *
 * @package    mod_traxlaunch
 * @copyright  2021 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tincan_launcher {

    /**
     * Prepare for launching and return the launch URL.
     * Tin Can implementation.
     * 
     * @param stdClass $activity
     * @param stdClass $cm
     * @param stdClass $course
     * @return string|false
     */
    public function launch_url($activity, $cm, $course) {
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
}
