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

use logstore_trax\src\utils;

/**
 * CMI5 launcher with proxy control.
 *
 * @package    mod_traxlaunch
 * @copyright  2021 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmi5_proxy_launcher extends cmi5_launcher {

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

        $this->token = utils::uuid();
        $this->endpoint = $CFG->wwwroot . "/mod/traxlaunch/proxy/endpoint.php?objectid=$activity->id&objecttable=traxlaunch&objecttype=mod&token=$this->token&api=";

        return true;
    }

}
