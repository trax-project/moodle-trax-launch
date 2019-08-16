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

// Plugin strings.
$string['modulename'] = 'Trax Launch';
$string['modulename_help'] = "This plugin let's you add remote xAPI content into your Moodle courses.";
$string['modulenameplural'] = 'Trax Launch';
$string['pluginadministration'] = 'Trax Launch administration';
$string['pluginname'] = 'Trax Launch';
$string['page-mod-traxlaunch-x'] = 'Any Trax Launch page';
$string['page-mod-traxlaunch-view'] = 'Trax Launch main page';

// Permissions.
$string['traxlaunch:addinstance'] = 'Add a new Trax Launch activity';
$string['traxlaunch:view'] = 'View Trax Launch activity';

// Settings.
$string['max_session_time'] = 'Max Session Time (minutes)';
$string['max_session_time_help'] = 'When a token is used, it will expire after the choosen max session time.';

// Mod Form.
$string['launchurl'] = 'Launch URL';
$string['launchurl_help'] = 'Enter the URL of the xAPI content you want to launch.';
$string['launchprotocol'] = 'Launch Protocol';
$string['launchprotocol_help'] = 'Select a protocol your xAPI content is compatible with.';

// Protocols.
$string['tincan'] = 'Tin Can (testing only)';
$string['tincan_proxy'] = 'Tin Can with LRS proxy';
$string['cmi5'] = 'CMI5 (coming soon)';

// View.
$string['launch'] = 'Launch Content';
$string['launched'] = "
    Your content should be opened in a new tab or window.
    <br>If not, check your browser settings and disable the pop-up windows blocker.
    <br>Please, don't try to open this content in multiple windows at the same time.
";
$string['failed_launched'] = "
    Sorry, the content launch failed for an unknown technical reason.
    <br>You should contact the administrator to report this issue.
";
$string['launch_retry'] = 'Retry to launch content';
$string['display_status'] = 'Display Status';
$string['back_to_course'] = 'Back to course';
$string['status'] = 'Status';
$string['score'] = 'Score';
$string['noscore'] = 'no score';

// Privacy metadata.
$string['privacy:metadata'] = 'This plugin does not store any personal data.
    However, some events are sent to the Trax Logs plugin.
    Refer to Trax Logs data privacy policy.';

// Events.
$string['event:content_launched'] = 'xAPI content launched';
$string['event:course_module_passed'] = 'Course module passed';
$string['event:course_module_failed'] = 'Course module failed';
$string['event:course_module_completed'] = 'Course module completed';
