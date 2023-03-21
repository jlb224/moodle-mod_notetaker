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
 * Plugin strings are defined here.
 *
 * @package     mod_notetaker
 * @category    string
 * @copyright   2020 Jo Beaver
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = "Notetaker";
$string['modulename'] = "Notetaker";
$string['modulenameplural'] = "Notetakers";
$string['modulename_help'] = "A notetaker is a course resource where students can add notes. If the notetaker
 instance is set to allow public notes, the course participant can choose whether their notes should be private
 and only visible to themselves, or to make them public and share them with other course participants.";

// Plugin strings.
$string['addnote'] = "Add note";
$string['allowpublicposts'] = "Allow public notes";
$string['allowpublicposts_help'] = "If yes, the user can choose to make their notes visible to other participants";
$string['authoredby'] = "by";
$string['backtonotes'] = "Return to notes";
$string['cardtag'] = "Card tag";
$string['category'] = "Category";
$string['confirmdelete'] = "Are you sure you want to delete?";
$string['edit'] = "Edit";
$string['ispublicpost'] = "This note is public";
$string['isnotpublicpost'] = "This note is private";
$string['lastmodified'] = "Last modified: ";
$string['missingidandcmid'] = "Error: Missing ID and CMID.";
$string['name'] = "Name";
$string['nonewmodules'] = "No notes have been created";
$string['notecontent'] = "Note content";
$string['notetakerappearance'] = "Appearance";
$string['notetaker:addinstance'] = 'Add a notetaker instance';
$string['notetaker:addnote'] = 'Add a new note';
$string['notetaker:view'] = 'View notes';
$string['notetaker:viewallnotes'] = 'View all notes';
$string['notetakersreset'] = 'Notetakers have been reset';
$string['privacy:metadata:core_tag'] = 'Tags added to notes are stored using core_tag system.';
$string['privacy:metadata:notetaker_notes'] = 'Notes';
$string['privacy:metadata:notetaker_notes:id'] = 'The ID of the note';
$string['privacy:metadata:notetaker_notes:modid'] = 'The course module that contains the note the user added';
$string['privacy:metadata:notetaker_notes:name'] = 'The name of the note the user added';
$string['privacy:metadata:notetaker_notes:notefield'] = 'The content of the note the user added';
$string['privacy:metadata:notetaker_notes:publicpost'] = 'The visibility of the note the user added';
$string['privacy:metadata:notetaker_notes:timecreated'] = 'The timestamp indicating when the note was created by the user';
$string['privacy:metadata:notetaker_notes:timemodified'] = 'The timestamp indicating when the note was modified by the user';
$string['privacy:metadata:notetaker_notes:userid'] = 'The ID of the user that is adding the note';
$string['publicpost'] = "Make note public";
$string['publicpost_help'] = "If yes, the note will be visible to other participants in this course. If No, the note will only be visible to you.";
$string['pluginadministration'] = "Plugin administration";
$string['removeallnotetakertags'] = 'Remove all notetaker tags';
$string['removeallnotetakernotes'] = 'Delete all notes';
$string['removeallnotetakernotes_help'] = "If ticked, note tags will also be removed";
$string['tagarea_notetaker_notes'] = 'Notetaker notes';
$string['tagsdeleted'] = 'Notetaker tags have been deleted';
$string['view'] = "View";