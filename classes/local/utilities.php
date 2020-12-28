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
 * Local lib - main library of static functions.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_notetaker\local;

use core_tag_tag;
use DOMDocument;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/tag/lib.php');
require_once($CFG->dirroot . '/mod/notetaker/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/accesslib.php');

class utilities {

    /**
     * Return the editor options for a note entry
     *
     * @param  stdClass $course  course object
     * @param  stdClass $context context object
     * @return array array containing the editor options
     */
    public static function get_editor_options($course, $context) {

        $editoroptions = [
        'subdirs' => 0,
        'maxbytes' => $course->maxbytes,
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'changeformat' => 0,
        'context' => $context,
        'noclean' => 0,
        'trusttext' => 0,
        'autosave' => false,
        'enable_filemanagement' => true
        ];

        return array($editoroptions);
    }

    /**
     * Return the value of publicposts from mod instance settings.
     * If allowed posts can be public to other course participants.
     *
     * @param  stdClass $notetakerid  notetaker object
     * @return int 1 for allow, or 0 for not allowed
     */
    public static function get_publicposts_value($notetakerid) {
        global $DB;

        $publicposts = $DB->get_record('notetaker', ['id' => $notetakerid]);
        $publicposts = $publicposts->publicposts;

        return $publicposts;
    }

    /**
     * Gets the notes associated with a module instance from the database.
     *
     * Converts time to human readable format.
     * @param $cmid ID of the module instance.
     * @param $context current context.
     */
    public static function get_notes($cm, $context, $userid, $allowpublicposts, string $search = null, $hassiteconfig, $hasviewall) {
        global $DB;

        $params = [];
        $notetakerid = $cm->instance;

        if (empty($search)) {
            if ($hassiteconfig || $hasviewall) {
                $results = $DB->get_records('notetaker_notes', ['notetakerid' => $notetakerid]);
            } else if ($allowpublicposts != 1) { // If it is 0, public posts is set to No at instance level.
                // User can only see own notes.
                $results = $DB->get_records('notetaker_notes', ['notetakerid' => $notetakerid, 'userid' => $userid]);
            } else {
                // User sees all own private notes plus all public notes made by anybody.
                $sql = "SELECT *
                        FROM {notetaker_notes}
                        WHERE notetakerid = :notetakerid AND (publicpost = 1 OR userid = :userid)";
                $params = [
                    'userid' => $userid,
                    'notetakerid' => $notetakerid
                ];
                $results = $DB->get_records_sql($sql, $params);
            }

        } else {
            $likename = $DB->sql_like('name', ':name', false);
            $name = '%'.$DB->sql_like_escape($search).'%';
            $params = ['userid' => $userid, 'notetakerid' => $notetakerid, 'name' => $name];

            if ($hassiteconfig) {
                $select = $likename . 'AND notetakerid = :notetakerid';
                $results = $DB->get_records_select('notetaker_notes', $select, $params);

            } else if ($allowpublicposts != 1) { // If it is 0, public posts is set to No at instance level.
                // User can only see own notes.
                $select = $likename . 'AND userid = :userid AND notetakerid = :notetakerid';
                $results = $DB->get_records_select('notetaker_notes', $select, $params);
            } else {
                // User sees all own private notes plus all public notes made by anybody.
                $sql = "SELECT *
                        FROM {notetaker_notes}
                        WHERE notetakerid = :notetakerid AND (publicpost = 1 OR userid = :userid)
                        AND $likename";
                $results = $DB->get_records_sql($sql, $params);
            }
        }

        foreach ($results as $result) {
            if ($result->timemodified != null) {
                $result->timemodified = userdate($result->timemodified, '%d %B %Y');
            } else {
                $result->timecreated = userdate($result->timecreated, '%d %B %Y');
            }

            // Process urls and convert card text to teaser length (150 characters).
            if (isset($result->notefield)) {
                $result->notefield = file_rewrite_pluginfile_urls($result->notefield, 'pluginfile.php', $context->id, 'mod_notetaker', 'notefield', $result->id);

                // Extract content from the editor to display in the card.
                $str = $result->notefield;
                $htmldom = new DOMDocument;
                libxml_use_internal_errors(true); // Required for HTML5.
                $htmldom->loadHTML($str);
                libxml_clear_errors(); // Required for HTML5.

                // Extract the images.
                $imagetags = $htmldom->getElementsByTagName('img');
                $extractedimages = [];
                foreach ($imagetags as $imagetag) {
                    $imgsrc = $imagetag->getAttribute('src');
                    $extractedimages[] = $imgsrc;
                }
                $result->extractedimages = $extractedimages;
                $result->imagecount = count($extractedimages);

                // Extract the text.
                $ptags = $htmldom->getElementsByTagName('p'); // Problem here is that Atto sometimes saves text in divs and not p tags.
                $cardtext = "";
                foreach ($ptags as $ptag) {
                    $cardtext = $cardtext . $ptag->textContent . " ";
                    $cardtext = strlen($cardtext) > 150 ? substr($cardtext, 0, 147).'...' : $cardtext;
                    $cardtext = format_text($cardtext, FORMAT_HTML);
                }
                $result->cardtext = $cardtext;
            }

            // Get note author.
            $author = $result->userid;
            list($sql, $params) = $DB->get_in_or_equal($author);
            $authorname = $DB->get_record_select('user', 'id ' . $sql, $params);
            $result->author = $authorname->firstname . " " . $authorname->lastname;

            // Get the tags for this notetaker instance.
            $result->tags = core_tag_tag::get_item_tags_array('mod_notetaker', 'notetaker_notes', $result->id);
        }
        return $results;
    }

    /**
     * Deletes a note from the database.
     *
     * @param $notetakerid ID of the module instance.
     * @param $noteid ID of the note.
     */
    public static function delete($notetakerid, $noteid) {
        global $DB;
        $DB->delete_records('notetaker_notes', ['notetakerid' => $notetakerid, 'id' => $noteid]);
    }

    /**
     * Gets the description from the notetaker instance.
     *
     * @param stdClass $course course object
     * @param stdClass $notetaker notetaker object
     * @param stdClass $context context object
     */
    public static function get_notetaker_desc ($course, $notetaker, $context) {
        global $DB;

        $intro = $DB->get_field('notetaker', 'intro', ['course' => $course->id, 'id' => $notetaker->id]);
        $intro = file_rewrite_pluginfile_urls($intro, 'pluginfile.php', $context->id, 'mod_notetaker', 'intro', null);
        $intro = format_text($intro);

        return $intro;
    }
}

/**
     * Returns notetaker notes tagged with a specified tag.
     *
     * This is a callback used by the tag area mod_notetaker/notetaker_notes to search for notetaker notes
     * tagged with a specific tag.
     *
     * @param core_tag_tag $tag
     * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
     *             are displayed on the page and the per-page limit may be bigger
     * @param int $fromctx context id where the link was displayed, may be used by callbacks
     *            to display items in the same context first
     * @param int $ctx context id where to search for records
     * @param bool $rec search in subcontexts as well
     * @param int $page 0-based number of page being displayed
     * @return \core_tag\output\tagindex
     */
    function mod_notetaker_get_tagged_notes($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0) {
        global $OUTPUT;
        $perpage = $exclusivemode ? 20 : 5;

        // Build the SQL query.
        $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
        $query = "SELECT nn.id, nn.name, nn.notetakerid, nn.userid,
                        cm.id AS cmid, c.id AS courseid, c.shortname, c.fullname, $ctxselect
                    FROM {notetaker_notes} nn
                    JOIN {notetaker} n ON n.id = nn.notetakerid
                    JOIN {modules} m ON m.name='notetaker'
                    JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = n.id
                    JOIN {tag_instance} tt ON nn.id = tt.itemid
                    JOIN {course} c ON cm.course = c.id
                    JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :coursemodulecontextlevel
                WHERE tt.itemtype = :itemtype AND tt.tagid = :tagid AND tt.component = :component
                    AND cm.deletioninprogress = 0
                    AND nn.id %ITEMFILTER% AND c.id %COURSEFILTER%";

        $params = array('itemtype' => 'notetaker_notes', 'tagid' => $tag->id, 'component' => 'mod_notetaker',
            'coursemodulecontextlevel' => CONTEXT_MODULE);

        if ($ctx) {
            $context = $ctx ? context::instance_by_id($ctx) : context_system::instance();
            $query .= $rec ? ' AND (ctx.id = :contextid OR ctx.path LIKE :path)' : ' AND ctx.id = :contextid';
            $params['contextid'] = $context->id;
            $params['path'] = $context->path.'/%';
        }

        $query .= " ORDER BY ";
        if ($fromctx) {
            // In order-clause specify that modules from inside "fromctx" context should be returned first.
            $fromcontext = context::instance_by_id($fromctx);
            $query .= ' (CASE WHEN ctx.id = :fromcontextid OR ctx.path LIKE :frompath THEN 0 ELSE 1 END),';
            $params['fromcontextid'] = $fromcontext->id;
            $params['frompath'] = $fromcontext->path.'/%';
        }
        $query .= ' c.sortorder, cm.id, nn.id';

        $totalpages = $page + 1;

        // Use core_tag_index_builder to build and filter the list of items.
        $builder = new core_tag_index_builder('mod_notetaker', 'notetaker_notes', $query, $params, $page * $perpage, $perpage + 1);
        while ($item = $builder->has_item_that_needs_access_check()) {
            context_helper::preload_from_record($item);
            $courseid = $item->courseid;
            if (!$builder->can_access_course($courseid)) {
                $builder->set_accessible($item, false);
                continue;
            }
            $modinfo = get_fast_modinfo($builder->get_course($courseid));
            // Set accessibility of this item and all other items in the same course.
            $builder->walk(function ($taggeditem) use ($courseid, $modinfo, $builder) {
                if ($taggeditem->courseid == $courseid) {
                    $accessible = false;
                    if (($cm = $modinfo->get_cm($taggeditem->cmid)) && $cm->uservisible) {
                        if ($taggeditem->userid == $USER->id) {
                            $accessible = true;
                        }
                    }
                    $builder->set_accessible($taggeditem, $accessible);
                }
            });
        }

        $items = $builder->get_items();
        if (count($items) > $perpage) {
            $totalpages = $page + 2; // We don't need exact page count, just indicate that the next page exists.
            array_pop($items);
        }

        // Build the display contents.
        if ($items) {
            $tagfeed = new core_tag\output\tagfeed();
            foreach ($items as $item) {
                context_helper::preload_from_record($item);
                $modinfo = get_fast_modinfo($item->courseid);
                $cm = $modinfo->get_cm($item->cmid);
                $pageurl = new moodle_url('/mod/notetaker/viewnote.php', array('note' => $item->id, 'cmid' => 'cm.id'));
                $pagename = format_string($item->name, true, array('context' => context_module::instance($item->cmid)));
                $pagename = html_writer::link($pageurl, $pagename);
                $courseurl = course_get_url($item->courseid, $cm->sectionnum);
                $cmname = html_writer::link($cm->url, $cm->get_formatted_name());
                $coursename = format_string($item->fullname, true, array('context' => context_course::instance($item->courseid)));
                $coursename = html_writer::link($courseurl, $coursename);
                $icon = html_writer::link($pageurl, html_writer::empty_tag('img', array('src' => $cm->get_icon_url())));
                $tagfeed->add($icon, $pagename, $cmname.'<br>'.$coursename);
            }

            $content = $OUTPUT->render_from_template('core_tag/tagfeed',
                    $tagfeed->export_for_template($OUTPUT));

            return new core_tag\output\tagindex($tag, 'mod_notetaker', 'notetaker_notes', $content,
                    $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
        }
    }