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
 * @package moodlecore
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

        // The notetaker notes.
        $notes = new backup_nested_element('notes');
        $note = new backup_nested_element('note', array('id'), array(
            'name', 'notefield', 'notefieldformat', 'timecreated', 'timemodified',
            'userid', 'publicpost'));

        // Build the tree.
        $notetaker->add_child($notes);
        $notes->add_child($note);

        // Define sources.
        $notetaker->set_source_table('notetaker', array('id' => backup::VAR_ACTIVITYID));

        if ($userinfo) {
            $note->set_source_table('notetaker_notes', array('notetakerid' => backup::VAR_PARENTID));
        } else {
            $note->set_source_sql('SELECT * FROM {notetaker_notes} WHERE userid = 0 AND notetakerid = ?', array(backup::VAR_PARENTID));
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