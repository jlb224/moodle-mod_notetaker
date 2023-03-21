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
 * Privacy implementation for mod_notetaker.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_notetaker\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
    // This plugin stores personal data.
    \core_privacy\local\metadata\provider,
    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider,
    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider {

    /**
     * Return the fields that contain personal data.
     *
     * @param collection $collection The collection used to store the metadata
     * @return collection The updated collection of metadata items
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'notetaker_notes',
        [
            'id' => 'privacy:metadata:notetaker_notes:id',
            'name' => 'privacy:metadata:notetaker_notes:name',
            'notefield' => 'privacy:metadata:notetaker_notes:notefield',
            'timecreated' => 'privacy:metadata:notetaker_notes:timecreated',
            'timemodified' => 'privacy:metadata:notetaker_notes:timemodified',
            'userid' => 'privacy:metadata:notetaker_notes:userid',
            'publicpost' => 'privacy:metadata:notetaker_notes:publicpost'
        ],
            'privacy:metadata:notetaker_notes'
        );

        $collection->add_subsystem_link('core_tag', [], 'privacy:metadata:core_tag');

        return $collection;
    }

    private static $modid;
    private static function get_modid() {
        global $DB;
        if (self::$modid === null) {
            self::$modid = $DB->get_field('modules', 'id', ['name' => 'notetaker']);
        }
        return self::$modid;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The userid
     * @return contextlist The list of contexts containing user info for the user
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $modid = self::get_modid();
        if (!$modid) {
            return $contextlist;
        }

        // Notes created by user.
        $sql = "SELECT c.id
                FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel AND cm.module = :modid
                JOIN {notetaker} nt ON nt.id = cm.instance
                JOIN {notetaker_notes} nn ON nn.notetakerid = nt.id
                WHERE nn.userid = :userid";

        $params = [
            'modid' => $modid,
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination
     *
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $modid = self::get_modid();
        if (!$modid) {
            return; // Notetaker module not installed.
        }

        $params = [
            'modid' => $modid,
            'contextlevel' => CONTEXT_MODULE,
            'contextid'    => $context->id,
        ];

        // Find users with note entries.
        $sql = "SELECT nn.userid
                FROM {notetaker_notes} nn
                JOIN {notetaker} nt ON nn.notetakerid
                JOIN {course_modules} cm ON cm.instance = nt.id AND cm.module = :modid
                JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                WHERE ctx.id = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist.
     *
     * User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT nn.id as noteid,
                       cm.id AS cmid,
                       nn.name,
                       nn.notefield,
                       nn.timecreated,
                       nn.timemodified,
                       nn.userid,
                       nn.publicpost
                FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid
                JOIN {notetaker} nt ON nt.id = cm.instance
                JOIN {notetaker_notes} nn ON nn.notetakerid = nt.id

                WHERE c.id $contextsql
                AND (nn.userid = 0 OR nn.userid = :userid)

                ORDER BY cm.id, nn.id";

        $params = [
            'userid' => $user->id,
            'contextlevel' => CONTEXT_MODULE
        ] + $contextparams;

        $lastcmid = null;
        $itemdata = [];

        $items = $DB->get_recordset_sql($sql, $params);

        foreach ($items as $item) {

            if ($lastcmid !== $item->cmid) {
                if ($itemdata) {
                    self::export_notetaker_data_for_user($itemdata, $lastcmid, $user);
                }
                $itemdata = [];
                $lastcmid = $item->cmid;
            }

            // Export associated tags.
            $name = format_string($item->name);
            $context = \context_module::instance($lastcmid);
            $path = array_merge([get_string('name', 'mod_notetaker'), $name . " ({$item->noteid})"]);
            \core_tag\privacy\provider::export_item_tags($user->id, $context, $path, 'mod_notetaker', 'notetaker_notes', $item->noteid, $item->userid != $user->id);

            $itemdata[] = (object)[
                'name'       => $item->name,
                'notefield'    => $item->notefield,
                'timecreated'   => \core_privacy\local\request\transform::datetime($item->timecreated),
                'timemodified'  => \core_privacy\local\request\transform::datetime($item->timemodified)
            ];
        }

        $items->close();
        if ($itemdata) {
            self::export_notetaker_data_for_user($itemdata, $lastcmid, $user);
        }
    }

    /**
     * Export the supplied personal data for a single notetaker activity, along with any generic data or area files.
     *
     * @param array $items The data to export for the notetaker
     * @param \context_module $context The context of the notetaker
     * @param int $cmid
     * @param \stdClass $user the user record
     */
    protected static function export_notetaker_data_for_user(array $items, int $cmid, \stdClass $user) {
        // Fetch the generic module data for the notetaker.
        $context = \context_module::instance($cmid);
        $contextdata = helper::get_context_data($context, $user);

         // Merge with notetaker data and write it.
         $contextdata = (object)array_merge((array)$contextdata, ['items' => $items]);
         writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context) {
            return;
        }

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        if (!$cm = get_coursemodule_from_id('notetaker', $context->instanceid)) {
            return;
        }

        // Delete tags.
        \core_tag\privacy\provider::delete_item_tags($context, 'mod_notetaker', 'notetaker_notes');

        // Delete user related notes.
        $itemids = $DB->get_fieldset_select('notetaker_notes', 'id', 'notetakerid = ?', [$cm->instance]);
        if ($itemids) {
            $DB->delete_records_select('notetaker_notes', 'notetakerid = ? AND userid <> 0', [$cm->instance]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);

            $entries = $DB->get_records('notetaker_notes', ['notetakerid' => $instanceid, 'userid' => $userid],
                    '', 'id');

            if (!$entries) {
                continue;
            }

            list($insql, $inparams) = $DB->get_in_or_equal(array_keys($entries), SQL_PARAMS_NAMED);

            // Delete user tags related to this notetaker.
            \core_tag\privacy\provider::delete_item_tags_select($context, 'mod_notetaker', 'notetaker_notes', $insql, $inparams);

            // Delete all user related notes.
            $itemids = $DB->get_fieldset_select('notetaker_notes', 'id', 'notetakerid = ?', [$instanceid]);
            if ($itemids) {
                $params = ['instanceid' => $instanceid, 'userid' => $userid];
                $DB->delete_records_select('notetaker_notes', 'notetakerid = :instanceid AND userid = :userid', $params);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
        list($userinsql, $userinparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $noteswhere = "notetakerid = :instanceid AND userid {$userinsql}";
        $userinstanceparams = $userinparams + ['instanceid' => $instanceid];
        $notesobject = $DB->get_recordset_select('notetaker_notes', $noteswhere, $userinstanceparams, 'id', 'id');
        $notes = [];

        foreach ($notesobject as $note) {
            $notes[] = $note->id;
        }

        $notesobject->close();

        if (!$notes) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($notes, SQL_PARAMS_NAMED);

        // Delete user tags related to user notes.
        \core_tag\privacy\provider::delete_item_tags_select($context, 'mod_notetaker', 'notetaker_notes', $insql, $inparams);

        // Delete all user related notes.
        $deletewhere = "notetakerid = :instanceid AND userid {$userinsql}";
        $DB->delete_records_select('notetaker_notes', $deletewhere, $userinstanceparams);

    }

}