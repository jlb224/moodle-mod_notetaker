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
 * @copyright   2020 Jo Beaver
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_notetaker\form\addnote_form;
use mod_notetaker\lib\local;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir.'/formslib.php');

$cmid = optional_param('cmid', 0, PARAM_INT); // Course module id.
$noteid = optional_param('note', 0, PARAM_INT); // Note id.

$cm = get_coursemodule_from_id('notetaker', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$notetaker = $DB->get_record('notetaker', array('id' => $cm->instance), '*', MUST_EXIST);

/*
* Same form is used to add and edit note.
* On add, note id is 0 until inserted in DB. On edit, we need the id from the post.
*/
if (!empty($_POST['id'])) {
    $noteid = (int) $_POST['id'];
} else {
    $noteid = optional_param('note', 0, PARAM_INT);
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$url = new moodle_url('/mod/notetaker/addnote.php', ['cmid' => $cmid]);

$PAGE->set_url($url);
$PAGE->set_title(format_string($notetaker->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());

list($editoroptions) = local::get_editor_options($course, $context);

if ($noteid != 0) {
    $entry = $DB->get_record('notetaker_notes', ['notetakerid' => $cm->instance, 'id' => $noteid]);

    // Prepare the notefield editor.
    $entry = file_prepare_standard_editor($entry, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notefield', $entry->id);

    $entry->tags = core_tag_tag::get_item_tags_array('mod_notetaker', 'notetaker_notes', $noteid);

} else {
    // New entry.
    if ($hassiteconfig || has_capability('mod/notetaker:addnote', $context)) {
        $entry = new stdClass();
        $entry->id = null;
    }
}

$entry->cmid = $cmid;
$entry->notefieldformat = FORMAT_HTML;

// See if publicposts are enabled for this instance.
$publicposts = local::get_publicposts_value($notetaker->id);

// Create form and set initial data.
$mform = new addnote_form (null, [
    'cmid' => $cmid,
    'editoroptions' => $editoroptions,
    'id' => $noteid,
    'publicposts' => $publicposts
    ]
);

if ($mform->is_cancelled()) {
    if ($noteid != 0) {
        redirect("viewnote.php?cmid=$cmid&note=$noteid");
    } else if (empty($noteid)) {
        redirect("view.php?id=$cmid");
    }

} else if ($fromform = $mform->get_data()) {
    $fromform->notefield = $fromform->notefield_editor['text'];
    $fromform->notefieldformat = $fromform->notefield_editor['format'];
    $fromform->userid = $USER->id;
    $fromform->notetakerid = $notetaker->id;

    if ($fromform->id != 0) { // If it is existing note.
        $isnewnote = false;
        $fromform->id = $noteid;
        $fromform->timemodified = time();
        $DB->update_record('notetaker_notes', $fromform);
    } else {
        $isnewnote = true;
        $fromform->timecreated = time();
        $fromform->id = $DB->insert_record('notetaker_notes', $fromform);
    }

    // Save and relink embedded images.
    if (!empty($fromform->notefield_editor)) {
        $fromform = file_postupdate_standard_editor($fromform, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notefield', $fromform->id);
        $DB->update_record('notetaker_notes', $fromform);
    }

    if (core_tag_tag::is_enabled('mod_notetaker', 'notetaker_notes') && isset($fromform->tags)) {
        core_tag_tag::set_item_tags('mod_notetaker', 'notetaker_notes', $fromform->id, $context, $fromform->tags);
    }

    redirect("viewnote.php?cmid=$cmid&note=$fromform->id");
}

if (!empty($noteid)) {
    $PAGE->navbar->add(get_string('edit'));

}

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string(get_string('addnote', 'mod_notetaker')));

$mform->set_data($entry);

$mform->display();

echo $OUTPUT->footer();
