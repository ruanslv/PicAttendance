# PicAttendance - Moodle plugin

Final year project developed by Thiago Caetano and Ruan Silva at University of Campinas, Brazil.

This module was developed based on "Attendance" (https://github.com/danmarsden/moodle-mod_attendance) and it may be distributed under the terms of the General Public License.

# Description

PicAttendance works as the Attendance plugin with the additional functionality of taking the attendance of students using photos from the class. Using OpenCV, the plugin detects the faces present in the photo and recognizes the students in class based on a pre-populated database. There is also a tagging system so that students can identify themselves in the photos when the system does not recognize them. In order to avoid frauds, the instructor needs to approve tags that were manually added by the students.

# Installation

The installation procedure is the standard for Moodle modules.

Quick-step guide (copied from "Attendance"):
Create folder /mod/attendance.
Extract files from folder inside archive to created folder.
Visit page Home ► Site administration ► Notifications to complete installation.

You also need to install the following extension: https://github.com/thiagocaetano/PicAttendance-PHP-Extension
