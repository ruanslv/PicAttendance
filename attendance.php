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

$tag_url = new moodle_url('/mod/attendance/tagging.php', array('sessid' => $id, 'sesskey' => sesskey()));
$groupimgs = $DB->get_records('attendance_session_images', array('sessionid' => $id));

$faceimgrec = false;
foreach ($groupimgs as $groupimg) {
    $faceimg = $DB->get_record('attendance_images', array('studentid' => $USER->id, 'groupimg' => $groupimg->groupimg, 'tag' => 1));
    if ($faceimg != false) {
        $faceimgrec = $faceimg;
        break;
    }
}
$tag = 0;
// Imagem ja tageada foi identificada
if ($faceimgrec != false) {
    $tag = 1;
} else {
    foreach ($groupimgs as $groupimg) {
        $faceimg = $DB->get_record('attendance_images', array('studentid' => $USER->id, 'groupimg' => $groupimg->groupimg));
        if ($faceimg != false) {
            $faceimgrec = $faceimg;
            break;
        }
    }
}

// Nenhuma face foi detectada
if ($faceimgrec == false && !empty($groupimgs)) {
    redirect($tag_url);
} 
// Create the form.
if ($faceimgrec != false) {
    $mform = new mod_attendance_student_attendance_form(null,
        array('course' => $course, 'cm' => $cm, 'modcontext' => $PAGE->context,
        'session' => $attforsession, 'attendance' => $att, 'faceimg' => $faceimgrec->faceimg, 'tag' => $tag));
} else {
    $mform = new mod_attendance_student_attendance_form(null,
        array('course' => $course, 'cm' => $cm, 'modcontext' => $PAGE->context,
        'session' => $attforsession, 'attendance' => $att, 'tag' => $tag));
}

if ($mform->is_cancelled()) {
    // The user cancelled the form, so redirect them to the view page.
    $url = new moodle_url('/mod/attendance/view.php', array('id' => $cm->id));
    redirect($url);
} else if ($fromform = $mform->get_data()) {
    if (!empty($fromform->changebutton)) {
        $faceimgrec->studentid = 0;
        $faceimgrec->tag = 0;
        $faceimgrec->approved = 0;
        $success = $DB->update_record('attendance_images', $faceimgrec);
        $refresh = new moodle_url('/mod/attendance/attendance.php', array('sessid' => $id, 'sesskey' => sesskey()));
        redirect($refresh);
    } else {
        $success = $att->take_from_student($fromform->sessid, $USER->id);
        $faceimgrec->approved = 1;
        $DB->update_record('attendance_images', $faceimgrec);
        $url = new moodle_url('/mod/attendance/view.php', array('id' => $cm->id));
        if ($success) {
        // Redirect back to the view page for the block.
        redirect($url);
        } else {
            print_error ('attendance_already_submitted', 'mod_attendance', $url);
        }
    }
    
    // The form did not validate correctly so we will set it to display the data they submitted.
    // $mform->set_data($fromform);
}

$PAGE->set_url($att->url_sessions());
$PAGE->set_title($course->shortname. ": ".$att->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);
$PAGE->navbar->add($att->name);

$output = $PAGE->get_renderer('mod_attendance');
echo $output->header();
$mform->display();
echo $output->footer();
