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
 * Displays a previously saved note.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_notetaker\lib\local;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$cmid = optional_param('cmid', 0, PARAM_INT); // Course module id.
$noteid = optional_param('note', 0, PARAM_INT); // Note id.

$cm = get_coursemodule_from_id('notetaker', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$notetaker = $DB->get_record('notetaker', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/notetaker/viewnote.php', ['cmid' => $cmid, 'note' => $noteid]);

$PAGE->set_url($url);
$PAGE->set_title(format_string($notetaker->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Delete note.
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($delete) {
    if (!$confirm) {
        echo $OUTPUT->header();
        $message = get_string('confirmdelete', 'mod_notetaker');
        $continue = '?delete='.$delete.'&cmid='.$cmid.'&note='.$noteid.'&confirm=1';
        // Print a message along with choices for continue / cancel.
        echo $OUTPUT->confirm($message, $continue, $url);
        echo $OUTPUT->footer();
    } else {
        local::delete($cmid, $noteid);
        redirect(new moodle_url('/mod/notetaker/view.php', ['id' => $cmid]), get_string('success'), 5);
    }
}

echo $OUTPUT->header();

// Get note record.
$result = $DB->get_record('notetaker_notes', ['notetakerid' => $notetaker->id, 'id' => $noteid]);
$messagetext = $result->notefield;
$messagetext = file_rewrite_pluginfile_urls($messagetext, 'pluginfile.php', $context->id, 'mod_notetaker', 'notefield', $result->id);

$result->noteowner = "";
if ($result->userid == $USER->id || has_capability('mod/notetaker:manageallnotes', $context)) {
    $result->noteowner = true;
} else {
    $result->noteowner = false;
}

$note = [];
$note[] = [
    'name' => $result->name,
    'notecontent' => $messagetext,
    'publicpost' => $result->publicpost
];

$data = (object) [
    'cmid' => $cmid,
    'noteid' => $noteid,
    'note' => array_values($note),
    'noteowner' => $result->noteowner
];

echo $OUTPUT->render_from_template('mod_notetaker/viewnote', $data);

echo $OUTPUT->footer();