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
 * Define all the restore steps that will be used by the restore_notetaker_activity_task
 */

/**
 * Structure step to restore one notetaker activity
 */
class restore_notetaker_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('notetaker', '/activity/notetaker');

        if ($userinfo) {
            $paths[] = new restore_path_element('notetaker_note', '/activity/notetaker/notes/note');
            $paths[] = new restore_path_element('notetaker_note_tag', '/activity/notetaker/notetags/tag');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_notetaker($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Insert the notetaker record.
        $newitemid = $DB->insert_record('notetaker', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_notetaker_note($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->notetakerid = $this->get_new_parentid('notetaker');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->modid = $this->;

        // Insert the note.
        $newitemid = $DB->insert_record('notetaker_notes', $data);
        $this->set_mapping('notetaker_note', $oldid, $newitemid, true); // Childs and files by itemname.
    }

    protected function process_notetaker_note_tag($data) {
        $data = (object)$data;

        if (!core_tag_tag::is_enabled('mod_notetaker', 'notetaker_notes')) { // Tags disabled in server, nothing to process.
            return;
        }

        $tag = $data->rawname;
        if (!$itemid = $this->get_mappingid('notetaker_note', $data->itemid)) {
            // Some orphaned tag, we could not find the notetaker note for it - ignore.
            return;
        }

        $context = context_module::instance($this->task->get_moduleid());
        core_tag_tag::add_item_tag('mod_notetaker', 'notetaker_notes', $itemid, $context, $tag);
    }

    protected function after_execute() {
        global $DB;

        // Add notetaker related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_notetaker', 'intro', null);
        $this->add_related_files('mod_notetaker', 'notefield', 'notefield_note');

        /**
         * After everything has been restored, we go in and find the the matching instance in course_modules
         * and add the course_modules id to our table.
         */
        $courseid = $this->get_courseid();
        $moduleid = $DB->get_field('modules', 'id', array('name'=>'notetaker'));

        $cms = $DB->get_records('course_modules', array('course'=>$courseid, 'moduleid'=>$moduleid));

        foreach ($cms as $cm) {
            $notetaker = $DB->get_record('notetaker_notes', array('id'=>$cm->instance));
            $notetaker->modid = $cm->id;
            $DB->update_record('notetaker_notes', $notetaker);
        }
    }
}