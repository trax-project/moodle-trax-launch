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

namespace mod_traxlaunch\src\proxy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/traxlaunch/locallib.php');

use logstore_trax\src\proxy\profile as base_profile;

/**
 * Proxy launch profile.
 *
 * @package    mod_traxlaunch
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile extends base_profile {

    /**
     * Transform a statement (hook).
     *
     * @param \stdClass $statement Statement to transform
     * @return void
     */
    protected function transform(&$statement) {
        global $DB;
        $courseentry = $this->activities->get_or_create_db_entry($this->course->id, 'course');
        $xapimodule = $this->activities->get('traxlaunch', $this->activity->id, false, 'module', 'traxlaunch', 'mod_traxlaunch');
        $status = $DB->get_record('traxlaunch_status', ['userid' => $this->userid, 'activityid' => $this->activity->id, 'attempt' => 1], '*', MUST_EXIST);

        // Add extensions.
        if (!isset($statement->context->extensions)) {
            $statement->context->extensions = new stdClass();
        }

        // Add sessionid.
        $extiri = 'https://w3id.org/xapi/cmi5/context/extensions/sessionid';
        if (!isset($statement->context->extensions->$extiri)) {
            $statement->context->extensions->$extiri = $status->token;
        }

        // Add attempt.
        $extiri = 'http://id.tincanapi.com/extension/attempt-id';
        if (!isset($statement->context->extensions->$extiri)) {
            $statement->context->extensions->$extiri = 1;
        }

        // Add registration.
        if (!isset($statement->context->registration)) {
            $statement->context->registration = $courseentry->uuid;
        }

        // Remove the content activity from grouping.
        foreach ($statement->context->contextActivities->grouping as $grouping) {
            if ($grouping->id == $xapimodule['id'] . '/content') {
                unset($statement->context->contextActivities->grouping);
            }
        }

        // Then...
        if ($statement->object->id == $xapimodule['id'] . '/content') {
            $this->transform_content_statement($statement, $xapimodule);
            $this->update_user_status($statement, $status);
        } else {
            $this->transform_internal_statement($statement, $xapimodule);
        }
    }

    /**
     * Transform a content statement.
     *
     * @param \stdClass $statement Statement to transform
     * @param array $xapimodule
     * @return void
     */
    protected function transform_content_statement(&$statement, $xapimodule) {

        // Replace the content activity.
        $statement->object = $this->content_activity($xapimodule);

        // Add the course module as the parent.
        $statement->context->contextActivities->parent = [$xapimodule];
    }

    /**
     * Transform an internal statement.
     *
     * @param \stdClass $statement Statement to transform
     * @param array $xapimodule
     * @return void
     */
    protected function transform_internal_statement(&$statement, $xapimodule) {

        $parent = is_array($statement->context->contextActivities->parent)
            ? $statement->context->contextActivities->parent[0]
            : $statement->context->contextActivities->parent;

            // Add the content activity...
        if ($parent->id == $xapimodule['id'] . '/content') {

            // As parent.
            $statement->context->contextActivities->parent = [$this->content_activity($xapimodule, false)];
        } else {

            // In grouping.
            $statement->context->contextActivities->grouping[] = $this->content_activity($xapimodule, false);
        }

        // Add the course module in grouping.
        $statement->context->contextActivities->grouping[] = $xapimodule;
    }

    /**
     * Get the xAPI content activity.
     *
     * @param array $xapimodule Module activity
     * @param bool $fulldef
     * @return array
     */
    protected function content_activity($xapimodule, $fulldef = true) {
        $res = [
            'objectType' => 'Activity',
            'id' => $xapimodule['id'] . '/content',
            'definition' => [
                'type' => $this->activities->types->type('xsco', 'mod_traxlaunch')
            ]
        ];
        if ($fulldef) {
            $res['definition']['extensions'] = [
                'http://vocab.xapi.fr/extensions/standard' => 'xapi'
            ];
        }
        return $res;
    }

    /**
     * Update the user status.
     *
     * @param stdClass $statement
     * @param stdClass $status
     * @return void
     */
    protected function update_user_status($statement, $status) {
        global $DB;

        $newstatus = clone $status;

        // Completion.
        if ($statement->verb->id == $this->verbs->iri('completed') 
            || $statement->verb->id == $this->verbs->iri('passed')
            || $statement->verb->id == $this->verbs->iri('failed')
            || (
                    isset($statement->result)
                    && isset($statement->result->completion) 
                    && $statement->result->completion
                )
            ) {
            $newstatus->completion = true;
        }

        // Passed.
        if ($statement->verb->id == $this->verbs->iri('passed') || (
            isset($statement->result)
            && isset($statement->result->success) 
            && $statement->result->success
        )) {
            $newstatus->success = true;
        }

        // Failed.
        if ($statement->verb->id == $this->verbs->iri('failed') || (
            isset($statement->result)
            && isset($statement->result->success) 
            && !$statement->result->success
        )) {
            $newstatus->success = false;
        }

        // Score.
        if (isset($statement->result) && isset($statement->result->score)) {
            $newstatus->score_min = isset($statement->result->score->min) ? $statement->result->score->min : 0;
            $newstatus->score_max = isset($statement->result->score->max) ? $statement->result->score->max : 100;
            if (isset($statement->result->score->raw)) {
                $newstatus->score_raw = $statement->result->score->raw;
            } else if (isset($statement->result->score->scaled)) {
                $newstatus->score_raw = intval($statement->result->score->min 
                    + ($statement->result->score->scaled * ($statement->result->score->max - $statement->result->score->min)));
            } else {
                unset($newstatus->score_min);
                unset($newstatus->score_max);
            }
        }

        // Update status.
        $newstatus->last_access = time();
        $DB->update_record('traxlaunch_status', $newstatus);

        // Trigger events.
        if ($newstatus->success != $status->success) {
            if ($newstatus->success) {
                traxlaunch_tigger_module_event('course_module_passed', $this->activity, $this->course, $this->cm, $this->context, $this->userid, $newstatus);
            } else {
                traxlaunch_tigger_module_event('course_module_failed', $this->activity, $this->course, $this->cm, $this->context, $this->userid, $newstatus);
            }
        } else if ($newstatus->completion && !$status->completion) {
            traxlaunch_tigger_module_event('course_module_completed', $this->activity, $this->course, $this->cm, $this->context, $this->userid, $newstatus);
        }
    }

}
