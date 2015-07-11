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
 * Prints attendance info for particular user
 *
 * @package    mod
 * @subpackage attendance
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/student_attenance_form.php');

$pageparams = new att_sessions_page_params();

// Check that the required parameters are present.
$id = required_param('sessid', PARAM_INT);
$attendance_session_id = required_param('sessid', PARAM_INT);


$attforsession = $DB->get_record('attendance_sessions', array('id' => $id), '*', MUST_EXIST);
$attendance = $DB->get_record('attendance', array('id' => $attforsession->attendanceid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('attendance', $attendance->id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

// Require the user is logged in.
require_login($course, true, $cm);

$pageparams->sessionid = $id;
$att = new attendance($attendance, $cm, $course, $PAGE->context, $pageparams);

// Require that a session key is passed to this page.
require_sesskey();
$attendance_url = new moodle_url('/mod/attendance/attendance.php', array('sessid' => $id, 'sesskey' => sesskey(), 'id' => $cm->id));


$groupimgs = $DB->get_records('attendance_session_images', array('sessionid' => $id));

$faceimgrec = false;
foreach ($groupimgs as $groupimg) {
    $faceimg = $DB->get_record('attendance_images', array('studentid' => $USER->id, 'groupimg' => $groupimg->groupimg));
    if ($faceimg != false) {
        $faceimgrec = $faceimg;
        break;
    }
}
if ($faceimgrec != false) {
    redirect($attendance_url);
}

$url = new moodle_url('/mod/attendance/tagging.php', array());
$PAGE->set_url($url);
$PAGE->set_title("Tagging photos");
// use get_string
$PAGE->set_heading("Show us where you are:");
$PAGE->set_cacheable(true);
$PAGE->navbar->add("Tagging");
$dataobject = $DB->get_record('attendance_session_images', array('sessionid' => $id));
if (isset($_POST['action'])) {
  if ($_POST['action'] === 'has_tagged') {
    $rectangle = array();
    $rectangle['x'] = (int) $_POST['x'];
    $rectangle['y'] = (int) $_POST['y'];
    $rectangle['width'] = (int) $_POST['width'];
    $rectangle['height'] = (int) $_POST['height'];
    $records = $DB->get_records('attendance_images', array('groupimg' => $dataobject->groupimg));
    $best_match = null;
    $best_match_value = (float) 0;
    foreach ($records as $record) {
      $rectangle2 = array();
      $rectangle2['x'] = (int) $record->x;
      $rectangle2['y'] = (int) $record->y;
      $rectangle2['width'] = (int) $record->width;
      $rectangle2['height'] = (int) $record->length;
      $match_value = att_rectangle_intersection_over_union($rectangle, $rectangle2);
      if ($match_value > $best_match_value) {
        $best_match_value = $match_value;
        $best_match = $record->faceimg;
      }
    }
    if ($best_match === null) {
      $no_match = true;
    } else {
      $no_match = false;
    }
  }
  if ($_POST['action'] === 'crop_new_image' || $no_match) {
    $rectangle = array();
    $rectangle['x'] = (int) $_POST['x'];
    $rectangle['y'] = (int) $_POST['y'];
    $rectangle['width'] = (int) $_POST['width'];
    $rectangle['height'] = (int) $_POST['height'];
    // todo: cortar usando esse rectangle e C++
  }
  if ($_POST['action'] === 'use_best_match') {
    $faceimg = $DB->get_record('attendance_images', array('faceimg' => $_POST['hash']));
    $faceimg->studentid = $USER->id;
    $faceimg->tag = 1;
    $DB->update_record('attendance_images', $faceimg);
    redirect($attendance_url);
    // todo: usar imagem ja existente.  $_POST['hash'];
  }
}


$output = $PAGE->get_renderer('mod_attendance');
echo $output->header();
$coursecontext = context_course::instance($course->id);
$imageurl = moodle_url::make_pluginfile_url($coursecontext->id, 'mod_attendance', 'myarea', 0, '/', $dataobject->groupimg);
$tagging = new attendance_image_tagging_data($imageurl, new moodle_url('/mod/attendance/tagging.php', array('sessid' => $id, 'sesskey' => sesskey())));
if (isset($_POST['action'])) {
  if ($_POST['action'] === 'has_tagged' && !$no_match) {
    echo html_writer::start_tag('p');
    echo "Awesome! We have found a previously detected face that approximately matches your drawing. Is this your face? If it isn't, don't worry! We will use the square you've drawn for your tagging istead.";
    echo html_writer::end_tag('p');
    echo html_writer::start_tag('p');
    echo html_writer::empty_tag('img', array('src' => moodle_url::make_pluginfile_url($coursecontext->id, 'mod_attendance', 'myarea', 0, '/', $best_match)));
    echo html_writer::end_tag('p');
    echo html_writer::start_tag('form', array('action' => new moodle_url('/mod/attendance/tagging.php', array('sessid' => $id, 'sesskey' => sesskey())), 'method' => 'post'));
    echo html_writer::start_tag('button', array('type' => 'submit', 'name' => 'action', 'value' => 'use_best_match'));
    echo "Yes, this is me!";
    echo html_writer::end_tag('button');
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'hash', 'value' => $best_match));
    echo html_writer::end_tag('form');
    echo html_writer::start_tag('form', array('action' => new moodle_url('/mod/attendance/tagging.php', array('sessid' => $id, 'sesskey' => sesskey())), 'method' => 'post'));
    echo html_writer::start_tag('button', array('type' => 'submit', 'name' => 'action', 'value' => 'crop_new_image'));
    echo "No, use the square tag I've drawn!";
    echo html_writer::end_tag('button');
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'x', 'value' => $rectangle['x']));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'y', 'value' => $rectangle['y']));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'width', 'value' => $rectangle['width']));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'height', 'value' => $rectangle['height']));
    echo html_writer::end_tag('form');
    echo html_writer::start_tag('p');
    echo "Feel free to tag yourself again:";
    echo html_writer::end_tag('p');
  }
} else {
  echo "Please tag your face by drawing a square around it. Please be as precise as possible.";
}
//echo "ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ALKSLAKSLAKS:L ALK S:ALkS :Alsk sklhaj ksdhaksjdh ksjdh ";
echo $output->render($tagging);
echo $output->footer();