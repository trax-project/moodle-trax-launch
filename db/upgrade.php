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

defined('MOODLE_INTERNAL') || die();

function xmldb_traxlaunch_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2018050802) {

        // The token index assumed the unicity of token values.
        // This is true with TinCan Proxy launcher (generated UUIDs),
        // but clearly not with Unsecured CMI5 (token based on main LRS credencials),
        // and not guaranteed with LRS specific integration such as TRAX LRS.
        
        // So we remove the token index and rebuild it without unicity constraint.
        
        $table = new xmldb_table('traxlaunch_status');
        $oldindex = new xmldb_index('token', XMLDB_INDEX_UNIQUE, ['token']);
        $newindex = new xmldb_index('token', XMLDB_INDEX_NOTUNIQUE, ['token']);

        if ($dbman->index_exists($table, $oldindex)) {
            $dbman->drop_index($table, $oldindex);
            $dbman->add_index($table, $newindex);
        }

        upgrade_mod_savepoint(true, 2018050802, 'traxlaunch');
    }

    return true;
}

