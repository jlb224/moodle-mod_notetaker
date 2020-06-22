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
use mod_notetaker\lib\addnote_lib;
use mod_notetaker\lib\local;

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

if ($noteid) {
    // Do something.
    echo "Just a placeholder for now"; // TODO remove this.
} else {
    // New entry.
    require_capability('mod/notetaker:write', $context);

    $entry = new stdClass();
    $entry->id = null;
}

list($editoroptions) = addnote_lib::get_editor_options($course, $context);

$entry = file_prepare_standard_editor($entry, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notefield_notes', $noteid);
$entry->cmid = $cm->id;

// Create form and set initial data.
$mform = new addnote_form (null, [
    'id' => $cm->id,
    'editoroptions'=> $editoroptions
    ]
);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/notetaker/view.php', ['id' => $cm->id]));

} else if ($data = $mform->get_data()) {

    if (empty($entry->id)){
    $fromform->notefield = $fromform->notefield_editor['text'];
    $fromform->noteformat = $fromform->notefield_editor['format'];
    $fromform->modid = $cm->id;
    $fromform->timecreated = time();
    $isnewnote = true;
} else {
    $isnewnote = false;
}
    $entry->concept = trim($entry->concept);
    $entry->notefield_editor = '';          // Updated later.
    $entry->notefieldformat = FORMAT_HTML; // Updated later.
    $entry->notefieldtrust  = 0;           // Updated later.

    if ($isnewentry) {
        // Add new entry.
        $entry->id = $DB->insert_record('notetaker_notes', $entry);
    } else {
        // Update existing entry.
        $DB->update_record('notetaker_notes', $entry);
    }

    // Save and relink embedded images and save attachments.
    if (!empty($entry->notefield_editor)) {
        $entry = file_postupdate_standard_editor($entry, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notetaker_notes', $entry->id);
    }

    // Store the updated values.
    $DB->update_record('notetaker_notes', $entry);

    // Refetch complete entry.
    $entry = $DB->get_record('notetaker_notes', array('id' => $entry->id));

    return $entry;

    if (core_tag_tag::is_enabled('mod_notetaker', 'notetaker_notes') && isset($data->tags)) {
        core_tag_tag::set_item_tags('mod_notetaker', 'notetaker_notes', $data->id, $context, $data->tags);
    }
    redirect("viewnote.php?id=$cm->id&note=$entry->id");
}

if (!empty($noteid)) {
    $PAGE->navbar->add(get_string('edit'));

 // Save and relink embedded images and save attachments.
    if (!empty($entry->notefield_editor)) {
        $entry = file_postupdate_standard_editor($entry, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notetaker_notes',
            $entry->id);
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string(get_string('addnote', 'mod_notetaker')));

$data = new StdClass();
$data->tags = core_tag_tag::get_item_tags_array('mod_notetaker', 'notetaker_notes', $noteid);
$mform->set_data($data);

$mform->display();

echo $OUTPUT->footer();
