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
 * Adds a new note to the instance of mod_notetaker.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver <myemail@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_notetaker\form\addnote_form;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir.'/formslib.php');

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('notetaker', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$notetaker = $DB->get_record('notetaker', array('id' => $cm->instance), '*', MUST_EXIST);
$noteid  = optional_param('note', 0, PARAM_INT);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$url = new moodle_url('/mod/notetaker/addnote.php', ['id' => $cm->id]);

$PAGE->set_url($url);
$PAGE->set_title(format_string($notetaker->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$editoroptions = [
    'subdirs'=>0,
    'maxbytes'=>$course->maxbytes,
    'maxfiles'=>EDITOR_UNLIMITED_FILES,
    'changeformat'=>0,
    'context'=>$context,
    'noclean'=>0,
    'trusttext'=>0,
    'enable_filemanagement' => true
];

$record = new stdClass;

// Get previous note from the database.
if ($noteid != 0) {
    $record = $DB->get_record('notetaker_notes', ['modid' => $cm->id, 'id' => $noteid]);
    // Prepare the notecontent editor.
    $record = file_prepare_standard_editor($record, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notefield', $record->id);
    // $record->notefield = format_text($record->notefield, FORMAT_HTML); TODO Make editor content be format html
    $record->tags = core_tag_tag::get_item_tags_array('mod_notetaker', 'notetaker_notes', $cmid);
} else {
    $record = file_prepare_standard_editor($record, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notefield', null);
}

// Create form and set initial data.
$mform = new addnote_form (null, [
    'id' => $cm->id,
    'editoroptions'=> $editoroptions
    ]
);

$mform->set_data($record);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/notetaker/view.php', ['id' => $cm->id]));

} else if ($fromform = $mform->get_data()) {
    $fromform->notefield = $fromform->notefield_editor['text'];
    $fromform->noteformat = $fromform->notefield_editor['format'];
    $fromform->modid = $cm->id;
    $fromform->timecreated = time();

    if (core_tag_tag::is_enabled('mod_notetaker', 'notetaker_notes') && isset($fromform->tags)) {
        core_tag_tag::set_item_tags('mod_notetaker', 'notetaker_notes', $fromform->id, $context, $fromform->tags);
    }

    if ($noteid != 0){
        $recordid = $DB->update_record('notetaker_notes', $fromform);
    }  else {
        $recordid = $DB->insert_record('notetaker_notes', $fromform);
    }

    redirect(new moodle_url('/mod/notetaker/view.php', ['id' => $cm->id]), get_string('success'), 5);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string(get_string('addnote', 'mod_notetaker')));

$mform->display();

echo $OUTPUT->footer();
