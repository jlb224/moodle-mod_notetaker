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

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('notetaker', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$notetaker = $DB->get_record('notetaker', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$url = new moodle_url('/mod/notetaker/addnote.php', ['id' => $cm->id]);

$PAGE->set_url($url);
$PAGE->set_title(format_string($notetaker->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$editoroptions = [
    'subdirs'=>0,
    'maxbytes'=>90,
    'maxfiles'=>5,
    'changeformat'=>0,
    'context'=>null,
    'noclean'=>0,
    'trusttext'=>0,
    'enable_filemanagement' => true
];

$mform = new addnote_form(null, [
    'id' => $cm->id, 
    'editoroptions'=>$editoroptions
    ]
);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/notetaker/view.php', ['id' => $cm->id]));

} else if ($fromform = $mform->get_data()) {
    $fromform->notecontent = $fromform->notecontent_editor['text']; //TODO Column name should be notetext.
    $fromform->notecontentformat = $fromform->notecontent_editor['format']; // TODO add noteformat column to DB table.
    $fromform->notetakerid = $cm->id; // TODO change this. Column name should be modid.
    $fromform->timecreated = time();    
    $DB->insert_record('notetaker_notes', $fromform);

    // if ($fromform->id) {        
    //     $recordid = $fromform->id;
    //     // 
    // } else {   
    //     $fromform->timemodified = time();     
    //     $recordid = $DB->insert_record('notetaker_notes', $fromform);
    // }  

    redirect(new moodle_url('/mod/notetaker/view.php', ['id' => $cm->id]), get_string('success'), 5);
} 

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string(get_string('addnote', 'mod_notetaker')));

$mform->display();

echo $OUTPUT->footer();

