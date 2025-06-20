Assign Submission Changes Plugin for Moodle
===========================================

This plugin provides a changelog for student submissions. You can see when a file was updated and 
the changed pages in PDF submissions.

License
-------

    Copyright (c) 2017 Hendrik Wuerz

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

Requirements
------------

* Tested with Moodle 3.4+
* Requires the plugin `local_changeloglib` [https://github.com/hwuerz/moodle-local_changeloglib](https://github.com/hwuerz/moodle-local_changeloglib) which depends on the command line tools [poppler-utils](https://wiki.ubuntuusers.de/poppler-utils/) and [diff](https://wiki.ubuntuusers.de/diff/)

Installation
--------

1. Install the dependency [local_changeloglib](https://github.com/hwuerz/moodle-local_changeloglib) with the required command line tools (See readme.md in the changeloglib repository.)
2. Clone the repo inside MOODLE_HOME/mod/assign/submission/changes
   ```bash
   cd path/to/moodle/home
   git clone https://github.com/hwuerz/moodle-assignsubmission_changes.git mod/assign/submission/changes
   ```
3. Browse to Site Administration -> Notifications and allow the database upgrades to execute
4. After installation define your admin settings to customize the plugin behaviour. See the chapter *Features* of this document for more information.

Tests
------

This plugin provides tests for the main features. To run them please follow the next steps:

1. Install PHPUnit on your system and configure moodle. See [https://docs.moodle.org/dev/PHPUnit](https://docs.moodle.org/dev/PHPUnit) for more information.
2. Install the plugin.
3. Run the tests
    ```bash
    cd /path/to/moodle/home
    php admin/tool/phpunit/cli/init.php
    vendor/bin/phpunit --group assignsubmission_changes
    ``` 

Features
--------

This plugin can generate a changelog for assignments. This changelog includes the timestamp and the old filename. Optionally a search for changed pages in PDF submissions can be performed.  The results are visible in the detail view of each submission. The overview displays the changes after the last grading. 

The features have to be enabled by the moodle administrator and a teacher must activate them is an assignment.

As a **moodle administrator** you can control whether the functionality should be available in your installation.
1. Go to `Site administration` -> `Plugins` -> `Activity modules` -> `Assignment` -> `Submission plugins` -> `Changes`
2. Set `Allow detection of changes in student submissions` to true if you want to enable the functionality in your installation. If this value is unchecked, no options will be displayed in assignments and no changelog is generated.
3. Set `Max filesize in MB for diff detection` to 20. This will allow the analysis of changed pages in PDF documents up to 20MB. If you set this value to zero, teachers can not enable the detection of differences in assignments. The feature requires the command line tools [poppler-utils](https://wiki.ubuntuusers.de/poppler-utils/) and [diff](https://wiki.ubuntuusers.de/diff/) as described in the [changeloglib Plugin](https://moodle.org/plugins/local_changeloglib)
4. Set `Enable Changelog by default` and `Enable Difference Detection by default` to true if you want to enable the changelog and the detection of changed pages in new assignments by default.

<img src="https://user-images.githubusercontent.com/9339300/34462711-30899f5c-ee4a-11e7-84dc-410127a03af4.png" width="400">

As a **teacher** you can activate the generation of a changelog and the detection of changed pages for an assignment in your course if the moodle admin has allowed it.
1. Go to `Edit settings` of an assignment or create a new one.
2. Scroll to `Submission types`.
3. Enable the submission type `Changes` if you want to create a changelog of uploads in this assignment.
4. Enable the checkbox `Auto detect diff` if the plugin should search for changed pages in new PDF submissions.

<img src="https://user-images.githubusercontent.com/9339300/31033672-46b678fe-a561-11e7-87f3-c9b9903d924a.png" width="400">

In the overview page of an assignment you can see a new column which indicates whether there are ungraded changes for a student. The detail view lists all updates in the assignment with date and filename. If you enabled the diff detection, the changed pages are included as well. 

<img src="https://user-images.githubusercontent.com/9339300/31033674-46b7f29c-a561-11e7-9c0a-6bb3d485c03e.png" width="400">

<img src="https://user-images.githubusercontent.com/9339300/31033673-46b7bd36-a561-11e7-8aef-5e7704a5219b.png" width="400">

As a **student** you can see the generated changelog for your submission. If there are mistakes in the auto generated entries, you can edit (but not delete) the data.

<img src="https://user-images.githubusercontent.com/9339300/31033675-46bca562-a561-11e7-8022-cfb7ba0a0042.png" width="400">
