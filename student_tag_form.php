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

require_once($CFG->libdir.'/formslib.php');

class mod_attendance_student_tag_form extends moodleform {
    public function definition() {
        global $CFG, $USER, $DB;

        $mform  =& $this->_form;

        $course = $this->_customdata['course'];
        $cm = $this->_customdata['cm'];
        $modcontext = $this->_customdata['modcontext'];
        $attforsession = $this->_customdata['session'];
        $attblock = $this->_customdata['attendance'];

        if (!empty($attforsession)) {
            $mform->addElement('hidden', 'sessid', null);
            $mform->setType('sessid', PARAM_INT);
            $mform->setConstant('sessid', $attforsession->id);
    
            $mform->addElement('hidden', 'sesskey', null);
            $mform->setType('sesskey', PARAM_INT);
            $mform->setConstant('sesskey', sesskey());
            
            // Set a title as the date and time of the session.
            $sesstiontitle = userdate($attforsession->sessdate, get_string('strftimedate')).' '
                .userdate($attforsession->sessdate, get_string('strftimehm', 'mod_attendance'));

            $mform->addElement('header', 'session', $sesstiontitle);
        } else {
            $mform->addElement('hidden', 'id', null);
            $mform->setType('id', PARAM_INT);
            $mform->setConstant('id', $cm->id);
        }

        $coursecontext = context_course::instance($course->id);
        $contextmodule = context_module::instance($cm->id);
        
        /*
        $fs = get_file_storage();
        $files = $fs->get_area_files($coursecontext->id, 'mod_attendance', 'myarea', 0);
        foreach ($files as $file) {
            // $f is an instance of stored_file
            echo $file->get_filename();
            echo "<br>";
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
            echo "<img src=\"$url\"/>";
        }*/
        
        if ($this->_customdata['radio']) {
            $records = $DB->get_records('attendance_images', array('tag' => 1, 'approved' => 0));
            foreach ($records as $record) {
                $studentid = $record->studentid;
                $face2approve = $record->faceimg;
                $personalrecs = $DB->get_records('attendance_images', array('studentid' => $studentid, 'approved' => 1));
                $group = array();
                foreach ($personalrecs as $personalrec) {
                    $url = moodle_url::make_pluginfile_url($coursecontext->id, 'mod_attendance', 'myarea', 0, '/', $personalrec->faceimg);
                    $group[] =& $mform->createElement('html', "<img src=\"$url\" width=\"80\" height=\"80\" />");
                    //$group[] =& $mform->createElement('radio', 'yes', '', $personalrec->faceimg, $record->id, array());
                }
                $mform->addGroup($group, 'oldpics', '', ' ', false);
                $group = array();
                $url = moodle_url::make_pluginfile_url($coursecontext->id, 'mod_attendance', 'myarea', 0, '/', $face2approve);
                $group[] =& $mform->createElement('html', "<img src=\"$url\" width=\"80\" height=\"80\" />");
                $group[] =& $mform->createElement('radio', 'yes', '', 'Yes', $record->id, array());
                $group[] =& $mform->createElement('radio', 'no', '', 'No', $record->id, array());
                $mform->addGroup($group, 'newpic', '', ' ', false);
            }
             /*
            foreach ($statuses as $status) {
                $radioarray[] =& $mform->createElement('radio', 'status', '', $status->description, $status->id, array());
            }
            // Add the radio buttons as a control with the user's name in front.
            $mform->addGroup($radioarray, 'statusarray', $USER->firstname.' '.$USER->lastname.':', array(''), false);
            $mform->addGroup($radioarray, 'statusarray', $USER->firstname.' '.$USER->lastname.':', array(''), false);\
            $mform->addRule('statusarray', get_string('attendancenotset', 'attendance'), 'required', '', 'client', false, false);*/
            if (!empty($records)) {
                $this->add_action_buttons(false);
            } else {
                // Add 3o param get_string('descriptionofexercise', 'exercise', $COURSE->students)
                $mform->addElement('static', '', 'No approvals pending', '');
            }
        } else {
            // Create check buttons
            $group_images = $DB->get_records('attendance_session_images', array('sessionid' => '0'));
            
            foreach ($group_images as $groupimgrec) {
                $groupimg = $groupimgrec->groupimg;
                // Testar flags??
                $face_images = $DB->get_records('attendance_images', array('groupimg' => $groupimg, 'approved' => 0));
                foreach ($face_images as $faceimgrec) {
                    $faceimg = $faceimgrec->faceimg;
                    $group = array();
                    $url = moodle_url::make_pluginfile_url($coursecontext->id, 'mod_attendance', 'myarea', 0, '/', $faceimg);
                    $group[] =& $mform->createElement('html', "<img src=\"$url\" width=\"80\" height=\"80\" />");
                    $group[] =& $mform->createElement('checkbox', $faceimg, 'LOL2');
                    $mform->addGroup($group, 'ratinggroup', '', ' ', false);
                }
            }
            $this->add_action_buttons();
        }
    }
}