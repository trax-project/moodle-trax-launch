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

require('../../config.php');

// Params.
$courseid = required_param('id', PARAM_INT);

// Objects.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

// Permissions.
require_course_login($course, true);

// Trigger instances list viewed event.
$context = context_course::instance($course->id);
$event = \mod_traxlaunch\event\course_module_instance_list_viewed::create(['context' => $context]);
$event->add_record_snapshot('course', $course);
$event->trigger();

// Some strings.
$strtraxlaunch = get_string('modulename', 'traxlaunch');
$strtraxlaunchs = get_string('modulenameplural', 'traxlaunch');
$strname = get_string('name');
$strintro = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

// Page setup.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/traxlaunch/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname . ': ' . $strtraxlaunchs);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strtraxlaunchs);

// Content.

echo $OUTPUT->header();
echo $OUTPUT->heading($strtraxlaunchs);
if (!$traxlaunchs = get_all_instances_in_course('traxlaunch', $course)) {
    notice(get_string('thereareno', 'moodle', $strtraxlaunchs), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($traxlaunchs as $traxlaunch) {
    $cm = $modinfo->cms[$traxlaunch->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($traxlaunch->section !== $currentsection) {
            if ($traxlaunch->section) {
                $printsection = get_section_name($course, $traxlaunch->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $traxlaunch->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($traxlaunch->timemodified)."</span>";
    }

    $class = $traxlaunch->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed

    $table->data[] = array (
        $printsection,
        "<a $class href=\"view.php?id=$cm->id\">".format_string($traxlaunch->name)."</a>",
        format_module_intro('traxlaunch', $traxlaunch, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
