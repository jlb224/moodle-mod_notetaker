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
 * Prints an instance of mod_notetaker.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_notetaker\local\utilities;
use mod_notetaker\form\searchnotes_form;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$cmid = required_param('id', PARAM_INT); // Course module id.
$notetakerid = optional_param('notetaker', 0, PARAM_INT); // Notetaker instance id.

if ($cmid) {
    $cm = get_coursemodule_from_id('notetaker', $cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $notetaker = $DB->get_record('notetaker', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($notetakerid) {
    $notetaker = $DB->get_record('notetaker', array('id' => $notetakerid), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $notetaker->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('notetaker', $notetaker->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_notetaker'));
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/notetaker:view', $context);

// Completion and trigger events.
notetaker_view($notetaker, $course, $cm, $context);

$url = new moodle_url('/mod/notetaker/view.php', array('id' => $cmid));

$PAGE->set_url($url);
$PAGE->set_title(format_string($notetaker->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/notetaker/styles.css');

// Create form and set initial data.
$mform = new searchnotes_form(null, [
    'id' => $cmid
    ]
);

$search = "";
if ($fromform = $mform->get_data()) {
    $search = $fromform->q;
}

// Get the notetaker description.
$intro = utilities::get_notetaker_desc($course, $notetaker, $context);

// Get notes.
$userid = $USER->id;
$allowpublicposts = $DB->get_field('notetaker', 'publicposts', ['course' => $course->id, 'id' => $notetaker->id]);
$hassiteconfig = has_capability('moodle/site:config', context_system::instance());
$hasviewall = has_capability('mod/notetaker:viewallnotes', $context);
$results = utilities::get_notes($cm, $context, $userid, $allowpublicposts, $search, $hassiteconfig, $hasviewall);

$PAGE->requires->js_call_amd('mod_notetaker/clearsearch', 'init');

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($notetaker->name));

ob_start();
$mform->display();
$mformhtml = ob_get_contents();
ob_end_clean();

$note = [];

foreach ($results as $result) {

    if ($result->timemodified != null) {
        $lastmodified = $result->timemodified;
    } else {
        $lastmodified = $result->timecreated;
    }

    $tags = [$result->tags];
    $ntags = [];
    foreach ($tags as $tag) {
        foreach ($tag as $key => $value) {
            $ntags[$key] = $value;
        }
    }

    $nimages = [$result->extractedimages];
    $imagesrc = [];
    $extracount = "";
    $lastitems = [];
    $i = 0;
    foreach ($nimages as $nimage) {
        foreach ($nimage as $key => $value) {
            if ($i < 3) {
                $imagesrc[$key] = $value;
                $i++;
            } else {
                break;
            }
        }
    }

    foreach ($imagesrc as $key => $value) {
        $lastitems[] = (object)["imageurl" => $value, "lastitem" => false];
    }

    if (count($imagesrc) > 2) {
        end($lastitems)->lastitem = true;
    }

    // Get the count of additional images.
    if ($result->imagecount > 3) {
        $extracount = '+'. ($result->imagecount - 3);
    }

    $note[] = [
        'cmid' => $cmid,
        'notetakerid' => $notetaker->id,
        'noteid' => $result->id,
        'name' => $result->name,
        'author' => $result->author,
        'lastmodified' => $lastmodified,
        'publicpost' => $result->publicpost,
        'tag' => array_values($ntags),
        'images' => $lastitems,
        'cardtext' => $result->cardtext,
        'extracount' => $extracount
    ];
}

$data = (object) [
    'cmid' => $cmid,
    'notetakerid' => $notetaker->id,
    'note' => array_values($note),
    'search_html' => $mformhtml,
    'intro' => $intro
];

echo $OUTPUT->render_from_template('mod_notetaker/view', $data);

echo $OUTPUT->footer();
