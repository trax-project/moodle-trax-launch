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

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/traxlaunch/locallib.php');

// Params.
$id = required_param('id', PARAM_INT); 

// Objects.
$cm = get_coursemodule_from_id('traxlaunch', $id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", array('id' => $cm->course), '*', MUST_EXIST);
$activity = $DB->get_record("traxlaunch", array('id' => $cm->instance), '*', MUST_EXIST);
$status = $DB->get_record("traxlaunch_status", array('activityid' => $activity->id, 'userid' => $USER->id, 'attempt' => 1));

// Permissions.
require_course_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/traxlaunch:view', $context);

// Events.
traxlaunch_tigger_module_event('course_module_viewed', $activity, $course, $cm, $context);

// Page setup.
$url = new moodle_url('/mod/traxlaunch/view.php', array('id' => $id));
$PAGE->set_url($url);

// Content header.
$title = format_string($activity->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Content.
$intro = format_string($activity->intro);
echo '<p>' . $intro . '</p>';

// Status.
if ($status) {

    // Status.
    if (isset($status->success)) {
        $statuslabel = $status->success ? 'passed' : 'failed';
    } else {
        $statuslabel = $status->completion ? 'completed' : 'incomplete';
    }

    // Score.
    $score = get_string('noscore', 'traxlaunch');
    if (isset($status->score_raw)) {
        $score = $status->score_raw . '/' . $status->score_max;
    }

    // Display.
    echo "
        <div class='status $statuslabel'>
            <p><strong>" . get_string('status', 'traxlaunch') . ": </strong> $statuslabel </p>
            <p><strong>" . get_string('score', 'traxlaunch') . ": </strong> $score </p>";

    // Buttons.
    $launchurl = new moodle_url('/mod/traxlaunch/launch.php', array('id' => $id));
    echo '<a href="' . $launchurl . '" class="btn btn-primary btn-lg mt-3">' . get_string('launch', 'traxlaunch') . '</a>';

    echo "
        </div>";
} else {

    // Buttons.
    $launchurl = new moodle_url('/mod/traxlaunch/launch.php', array('id' => $id));
    echo '<a href="' . $launchurl . '" class="btn btn-primary btn-lg mt-3">' . get_string('launch', 'traxlaunch') . '</a>';
}


// Content close.
echo $OUTPUT->footer();

