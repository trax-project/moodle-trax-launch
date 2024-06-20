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

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client as GuzzleClient;

/**
 * CMI5 launcher with with TRAX LRS token management.
 *
 * @package    mod_traxlaunch
 * @copyright  2021 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmi5_traxlrs_launcher extends cmi5_launcher {

    /**
     * Init the launcher properties.
     * 
     * @param stdClass $activity
     * @param stdClass $cm
     * @param stdClass $course
     * @return bool
     */
    protected function init($activity, $cm, $course) {
        global $CFG;
        parent::init($activity, $cm, $course);

        // Get the resource domain.
        list($scheme, $rest) = explode('://', $activity->launchurl);
        $domain = $scheme . '://' . explode('/', $rest)[0];

        // Call TRAX LRS token delivery service.
        $auth = 'Basic ' . base64_encode(
            get_config('logstore_trax', 'lrs_username') . ':' . get_config('logstore_trax', 'lrs_password')
        );

        // Get the xAPI endpoint and remove the last chunk ('std' with TRAX 2, 'xapi' with TRAX 3).
        $parts = explode('/', get_config('logstore_trax', 'lrs_endpoint'));
        $last = array_pop($parts);
        $tokenServiceEndpoint = implode('/', $parts) . '/cmi5/tokens';
        $traxVersion = $last == 'std' ? 2 : 3;

        try {
            if ($traxVersion == 2) {
                $response = (new GuzzleClient)->get($tokenServiceEndpoint, [
                    'headers' => [
                        'Authorization' => $auth,
                    ],
                    'query' => [
                        'activity_id' => $this->activityId,
                        'agent' => json_encode($this->actor),
                        'origin' => $domain
                    ],
                ]);
            } else {
                $response = (new GuzzleClient)->post($tokenServiceEndpoint, [
                    'headers' => [
                        'Authorization' => $auth,
                    ],
                    'query' => [
                        'activity_id' => $this->activityId,
                        'agent' => json_encode($this->actor),
                        'origin' => $domain
                    ],
                ]);
            }
            if (is_null($response) || $response->getStatusCode() != 200) {
                return false;
            }
        } catch (GuzzleException $e) {
            return false;
        }

        $content = json_decode($response->getBody());

        $this->endpoint = $content->endpoint;
        $this->token = $content->token;
        return true;
    }
}
