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

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());

if ($noteid != 0) {
    $entry = $DB->get_record('notetaker_notes', ['modid' => $cm->id, 'id' => $noteid]);
} else {
    // New entry.
    if ($hassiteconfig || has_capability('mod/notetaker:write', $context)) {
        $entry = new stdClass();
        $entry->id = null;
    }
}

list($editoroptions) = addnote_lib::get_editor_options($course, $context);

// Prepare the notecontent editor.
$entry = file_prepare_standard_editor($entry, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notetaker_notes', $entry->id);
$entry->notefieldformat = FORMAT_HTML;
$entry->cmid = $cm->id;

// Create form and set initial data.
$mform = new addnote_form (null, [
    'id' => $cm->id,
    'editoroptions'=> $editoroptions
    ]
);

if ($mform->is_cancelled()) {
    if ($noteid != 0) {
        redirect("viewnote.php?id=$cm->id&note=$noteid");
    } else {
        redirect("view.php?id=$cm->id");
    }

} else if ($fromform = $mform->get_data()) { // Are we getting data from the form?

    if (empty($noteid)){ // Is noteid 0 or null?
    $fromform->notefield = $fromform->notefield_editor['text'];
    $fromform->notefieldformat = $fromform->notefield_editor['format'];
    $fromform->modid = $cm->id;
    $fromform->timecreated = time();
    $isnewnote = true;

} else {
    $isnewnote = false; // Why is this being skipped?
}
    $fromform->notefield_editor = '';
    $fromform->notefieldformat = FORMAT_HTML;

    if ($isnewnote) {
        // Add new entry.
        $fromform->id = $DB->insert_record('notetaker_notes', $fromform);
    } else {
        // Update existing entry.
        $DB->update_record('notetaker_notes', $fromform);
    }

    // Save and relink embedded images and save attachments.
    if (!empty($fromform->notefield_editor)) {
        $fromform = file_postupdate_standard_editor($fromform, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notetaker_notes', $fromform->id);
    }

    // Store the updated values.
    $DB->update_record('notetaker_notes', $fromform);

    // Refetch complete entry.
    $fromform = $DB->get_record('notetaker_notes', array('id' => $fromform->id));

    if (core_tag_tag::is_enabled('mod_notetaker', 'notetaker_notes') && isset($fromform->tags)) {
        core_tag_tag::set_item_tags('mod_notetaker', 'notetaker_notes', $fromform->id, $context, $fromform->tags);
    }
    redirect("viewnote.php?id=$cm->id&note=$fromform->id");
}

if (!empty($noteid)) {
    $PAGE->navbar->add(get_string('edit'));

 // Save and relink embedded images and save attachments.
    if (!empty($fromform->notefield_editor)) {
        $fromform = file_postupdate_standard_editor($fromform, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notetaker_notes', $fromform->id);
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string(get_string('addnote', 'mod_notetaker')));

$fromform = new StdClass();
$fromform->tags = core_tag_tag::get_item_tags_array('mod_notetaker', 'notetaker_notes', $noteid);

$mform->set_data($entry);

$mform->display();

echo $OUTPUT->footer();
