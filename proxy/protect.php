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
 * LRS proxy.
 *
 * @package    mod_traxlaunch
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Allow CORS requests.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Experience-API-Version');
header('Access-Control-Allow-Credentials: true');

// Stop preflight.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    die;
}

require_once('../../../config.php');
require_once($CFG->dirroot . '/mod/traxlaunch/locallib.php');

// Check token.
$token = required_param('token', PARAM_RAW); 
if (!$TOKEN_USERID = traxlaunch_check_token($token)) {
    http_response_code(401);
    die;
}



