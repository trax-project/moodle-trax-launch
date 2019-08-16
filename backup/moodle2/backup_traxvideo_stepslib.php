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

defined('MOODLE_INTERNAL') || die;

/**
 * Trax Launch for Moodle.
 *
 * @package    mod_traxlaunch
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_traxlaunch_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure.
     */
    protected function define_structure() {

        // Define each element separated.
        $traxlaunch = new backup_nested_element('traxlaunch', array('id'), array(
            'name', 'intro', 'introformat', 'launchurl', 'launchprotocol', 'timemodified'));

        // Define sources.
        $traxlaunch->set_source_table('traxlaunch', array('id' => backup::VAR_ACTIVITYID));

        // Define file annotations
        $traxlaunch->annotate_files('mod_traxlaunch', 'intro', null); // This file area hasn't itemid

        // Return the root element (traxlaunch), wrapped into standard activity structure.
        return $this->prepare_activity_structure($traxlaunch);

    }
}
