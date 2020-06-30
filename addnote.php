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

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir.'/formslib.php');

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('notetaker', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$notetaker = $DB->get_record('notetaker', array('id' => $cm->instance), '*', MUST_EXIST);

if (!empty($_POST['id'])) {
    $noteid = (int) $_POST['id'];
} else {
    $noteid = optional_param('note', 0, PARAM_INT);
}
// TODO on Cancel of existing note the noteid is in the $_POST as id

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$url = new moodle_url('/mod/notetaker/addnote.php', ['cmid' => $cm->id]);

$PAGE->set_url($url);
$PAGE->set_title(format_string($notetaker->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());

list($editoroptions) = addnote_lib::get_editor_options($course, $context);



if ($noteid != 0) {
    $entry = $DB->get_record('notetaker_notes', ['modid' => $cm->id, 'id' => $noteid]);

   // Prepare the notefield editor.
    $draftitemid = file_get_submitted_draft_itemid('notefield');
    $entry = file_prepare_standard_editor($entry, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notefield', $entry->id);
    $entry->notefieldformat = FORMAT_HTML;
    // $currenttext = file_prepare_draft_area($draftitemid, $context->id, 'mod_notetaker', 'notefield', $entry->id);
    // $entry->notefield = array('text'=>$currenttext, 'format'=>$entry->notefieldformat, 'itemid'=>$draftitemid);
    // $entry->notefield = $draftitemid;
    $entry->tags = core_tag_tag::get_item_tags_array('mod_notetaker', 'notetaker_notes', $noteid);

} else {
    // New entry.
    if ($hassiteconfig || has_capability('mod/notetaker:write', $context)) {
        $entry = new stdClass();
        $entry->id = null;
    }
}

$entry->cmid = $cm->id;

// See if publicposts are enabled for this instance
$notetakerid = $notetaker->id;
$publicposts = addnote_lib::get_publicposts_value($notetakerid);

// Create form and set initial data.
$mform = new addnote_form (null, [
    'cmid' => $cm->id,
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
    $fromform->modid = $cm->id;
    $fromform->timecreated = time();

    if ($fromform->id != 0) { // If it is existing note.
        $isnewnote = false;
        $DB->update_record('notetaker_notes', $fromform);
    } else {
        $isnewnote = true;
        $fromform->id = $DB->insert_record('notetaker_notes', $fromform);
    }

    // Save and relink embedded images.
    if (!empty($fromform->notefield_editor)) {
        // $fromform->itemid = $fromform->notefield_editor['itemid'];
        $fromform = file_postupdate_standard_editor($fromform, 'notefield', $editoroptions, $context, 'mod_notetaker', 'notefield', $fromform->id);
        // $fromform->notefield = file_save_draft_area_files($draftitemid, $context->id, 'mod_notetaker', 'notefield', $fromform->id, $editoroptions, $fromform->notefield);
        $DB->update_record('notetaker_notes', $fromform);
    }

    if (core_tag_tag::is_enabled('mod_notetaker', 'notetaker_notes') && isset($fromform->tags)) {
        core_tag_tag::set_item_tags('mod_notetaker', 'notetaker_notes', $fromform->id, $context, $fromform->tags);
    }

    redirect("viewnote.php?cmid=$cm->id&note=$fromform->id");
}

if (!empty($noteid)) {
    $PAGE->navbar->add(get_string('edit'));

}

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string(get_string('addnote', 'mod_notetaker')));

$mform->set_data($entry);

$mform->display();

echo $OUTPUT->footer();
