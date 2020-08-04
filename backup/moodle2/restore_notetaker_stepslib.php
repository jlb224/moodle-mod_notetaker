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
 * @subpackage restore-moodle2
 * @copyright 2020 Jo Beaver
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one choice activity
 */
class restore_notetaker_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('notetaker', '/activity/notetaker');
        $paths[] = new restore_path_element('notetaker_notes', '/activity/notetaker/notes/note');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_notetaker($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timeopen = $this->apply_date_offset($data->timecreated);
        $data->timeclose = $this->apply_date_offset($data->timemodified);

        // Insert the notetaker record.
        $newitemid = $DB->insert_record('notetaker', $data);
        $this->set_mapping('notetaker', $oldid, $newitemid);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_notetaker_notes($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->notetakerid = $this->get_new_parentid('notetaker');
        if ($data->userid > 0) {
            $data->userid = $this->get_mappingid('user', $data->userid);
        }

        $newitemid = $DB->insert_record('notetaker_notes', $data);
        $this->set_mapping('notetaker_notes', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add notetaker related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_notetaker', 'intro', null);
        $this->add_related_files('mod_notetaker', 'notefield', null);
    }
}