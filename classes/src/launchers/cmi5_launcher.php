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
use logstore_trax\src\utils;
use moodle_url;

/**
 * CMI5 launcher without token management.
 *
 * @package    mod_traxlaunch
 * @copyright  2021 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmi5_launcher {

    /**
     * @var \logstore_trax\src\controller
     */
    protected $traxlogs;

    /**
     * @var string
     */
    protected $activityId;

    /**
     * @var string
     */
    protected $actor;

    /**
     * @var string
     */
    protected $registration;

    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $token;

    /**
     * Prepare for launching and return the launch URL.
     *
     * @param stdClass $activity
     * @param stdClass $cm
     * @param stdClass $course
     * @return string|false
     */
    public function launch_url($activity, $cm, $course) {

        if (!$this->init($activity, $cm, $course)) {
            return false;
        }

        // xAPI State update.
        if (!$this->update_state()) {
            return false;
        }
        
        // xAPI Agent Profile update.
        if (!$this->update_agent_profile()) {
            return false;
        }

        // Update internal launch status.
        $id = $this->update_internal_status($activity);

        // Return launch link.
        return (new moodle_url($activity->launchurl, [
            'endpoint' => $this->endpoint,
            'fetch' => $this->fetch_link($id),
            'registration' => $this->registration,
            'activityId' => $this->activityId,
            'actor' => json_encode($this->actor),
        ]))->out(false);
    }

    /**
     * Init the launcher properties.
     *
     * @param stdClass $activity
     * @param stdClass $cm
     * @param stdClass $course
     * @return bool
     */
    protected function init($activity, $cm, $course) {
        global $USER;
        
        $this->traxlogs = new trax_controller();

        $xapimodule = $this->traxlogs->activities->get('traxlaunch', $activity->id, false, 'module', 'traxlaunch', 'mod_traxlaunch');
        $this->activityId = $xapimodule['id'] . '/content';

        $courseentry = $this->traxlogs->activities->get_or_create_db_entry($course->id, 'course');
        $this->registration = $courseentry->uuid;

        $this->actor = $this->traxlogs->actors->get('user', $USER->id);

        $this->endpoint = get_config('logstore_trax', 'lrs_endpoint') . '/';
        $this->token = base64_encode(
            get_config('logstore_trax', 'lrs_username') . ':' . get_config('logstore_trax', 'lrs_password')
        );
        return true;
    }

    /**
     * @return bool
     */
    protected function update_state() {
        $params = [
            'activityId' => $this->activityId,
            'agent' => json_encode($this->actor),
            'registration' => $this->registration,
            'stateId' => 'LMS.LaunchData',
        ];
        $data = [
            'contextTemplate' => [
                'contextActivities' => [
                    'grouping' => [['id' => $this->activityId]]
                ],
                'extensions' => [
                    'https://w3id.org/xapi/cmi5/context/extensions/sessionid' => utils::uuid()
                ]
            ],
            'launchMode' => 'Normal',
            'masteryScore' => 0.75,
            'moveOn' => 'CompletedOrPassed',
        ];
        $response = $this->traxlogs->client()->states()->post($data, $params);
        return $response->code == 204;
    }

    /**
     * @return bool
     */
    protected function update_agent_profile() {
        $params = [
            'agent' => json_encode($this->actor),
            'profileId' => 'cmi5LearnerPreferences',
        ];
        $data = [
            'languagePreference' => 'en-US',
            'audioPreference' => 'on',
        ];
        $response = $this->traxlogs->client()->agentProfiles()->post($data, $params);
        return $response->code == 204;
    }

    /**
     * @param stdClass $activity
     * @return object
     */
    protected function update_internal_status($activity) {
        global $USER, $DB;
        // We currently support only a single attempt.
        $record = $DB->get_record('traxlaunch_status', ['userid' => $USER->id, 'activityid' => $activity->id, 'attempt' => 1]);
        if (!$record) {
            $record = (object)[
                'userid' => $USER->id,
                'activityid' => $activity->id,
                'attempt' => 1,
                'token' => $this->token,
                'session_start' => time(),
                'first_access' => time(),
            ];
            return $DB->insert_record('traxlaunch_status', $record);
        } else {
            $record->token = $this->token;
            $record->session_start = time();
            $DB->update_record('traxlaunch_status', $record);
            return $record->id;
        }
    }

    /**
     * @param stdClass $status
     * @return string
     */
    protected function fetch_link($id) {
        global $CFG;
        return (new moodle_url($CFG->wwwroot . '/mod/traxlaunch/cmi5/token.php', [
            'id' => $id,
        ]))->out(false);
    }
}
