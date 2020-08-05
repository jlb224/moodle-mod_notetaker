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
 * @package mod_notetaker
 * @subpackage backup-moodle2
 * @copyright 2020 Jo Beaver
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the backup steps that will be used by the backup_notetaker_activity_task.
 */

/**
 * Define the complete notetaker structure for backup, with file and id annotations
 */
class backup_notetaker_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $notetaker = new backup_nested_element('notetaker', array('id'), array(
            'name', 'course', 'intro', 'introformat', 'timecreated', 'timemodified', 'publicposts'));

        $notes = new backup_nested_element('notes');

        $note = new backup_nested_element('note', array('id'), array(
            'name', 'notefield', 'notefieldformat', 'timecreated', 'timemodified',
            'userid', 'publicpost', 'notetakerid'));

        $tags = new backup_nested_element('notetags');
        $tag = new backup_nested_element('tag', array('id'), array('itemid', 'rawname'));

        // Build the tree.
        $notetaker->add_child($notes);
        $notes->add_child($note);

        $notetaker->add_child($tags);
        $tags->add_child($tag);

        // Define sources.
        $notetaker->set_source_table('notetaker', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $note->set_source_table('notetaker_notes', array('notetakerid' => backup::VAR_PARENTID));

            if (core_tag_tag::is_enabled('mod_notetaker', 'notetaker_notes')) {
                $tag->set_source_sql('SELECT t.id, ti.itemid, t.rawname
                                        FROM {tag} t
                                        JOIN {tag_instance} ti ON ti.tagid = t.id
                                       WHERE ti.itemtype = ?
                                         AND ti.component = ?
                                         AND ti.contextid = ?', array(
                    backup_helper::is_sqlparam('notetaker_notes'),
                    backup_helper::is_sqlparam('mod_notetaker'),
                    backup::VAR_CONTEXTID));
            }
        }

        // Define id annotations.
        $note->annotate_ids('user', 'userid');

        // Define file annotations.
        $notetaker->annotate_files('mod_notetaker', 'intro', null); // This file area does not have an itemid.
        $notetaker->annotate_files('mod_notetaker', 'notefield', null); // This file area does not have an itemid.

        // Return the root element (notetaker), wrapped into standard activity structure.
        return $this->prepare_activity_structure($notetaker);

    }
}