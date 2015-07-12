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

class mod_attendance_student_attendance_form extends moodleform {
    public function definition() {
        global $CFG, $DB, $USER;

        $mform  =& $this->_form;

        $course = $this->_customdata['course'];
        $cm = $this->_customdata['cm'];
        $modcontext = $this->_customdata['modcontext'];
        $attforsession = $this->_customdata['session'];
        $attblock = $this->_customdata['attendance'];
        $faceimg = $this->_customdata['faceimg'];
        $tag = $this->_customdata['tag'];

        $statuses = $attblock->get_statuses();

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

        // If a session description is set display it.
        if (!empty($attforsession->description)) {
            $mform->addElement('html', $attforsession->description);
        }
        
        if (!empty($faceimg)) {
            //$group = array();
            $coursecontext = context_course::instance($course->id);
            $url = moodle_url::make_pluginfile_url($coursecontext->id, 'mod_attendance', 'myarea', 0, '/', $faceimg);
            //$group[] =& $mform->createElement('html', "<img src=\"$url\" />");
            //$mform->addGroup($group, 'ratinggroup', '', ' ', false);
            $mform->addElement('html', "<img src=\"$url\" />");
                    
            $change_string = 'Change photo';
            // Add get_string('savechanges')
            $mform->addElement('submit', 'changebutton', $change_string);
            
            if ($tag == 1) {
                $mform->addElement('static', '', 'Teacher approval pending.', '');
            } else {
                // add get_string
                $submit_string = 'Confirm';
                $this->add_action_buttons(true, $submit_string);
            }
        } else {
            $mform->addElement('static', '', 'No images added for this session.', '');
        }
    }
}