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

// Permissions.
require_course_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/traxlaunch:view', $context);

// Page setup.
$url = new moodle_url('/mod/traxlaunch/launch.php', array('id' => $id));
$PAGE->set_url($url);

// Content header.
$title = format_string($activity->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Launch script.
$launchurl = traxlaunch_launch_prepare($activity, $cm, $course);
if ($launchurl) {

    ?>
    <script>window.open("<?php echo $launchurl ?>");</script>
    <?php

    // Events.
    traxlaunch_tigger_module_event('content_launched', $activity, $course, $cm, $context);

    // Content.
    echo '<p>' . get_string('launched', 'traxlaunch') . '</p>';

} else {

    // Content.
    echo '<p>' . get_string('failed_launched', 'traxlaunch') . '</p>';
}

// Back to activity button.
$activityurl = new moodle_url('/mod/traxlaunch/view.php', array('id' => $id));
echo '<a href="' . $activityurl . '" class="btn btn-primary btn-lg mt-3">' . get_string('display_status', 'traxlaunch') . '</a>';

// Back to course button.
$courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
echo '<a href="' . $courseurl . '" class="btn btn-primary btn-lg mt-3 ml-2">' . get_string('back_to_course', 'traxlaunch') . '</a>';

// Content close.
echo $OUTPUT->footer();



