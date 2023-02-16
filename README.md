# Profiles

[![es](https://img.shields.io/badge/lang-es-yellow.svg)](README-es.md)


It's a fork of ```gradeexport_ods``` that let's the user manage profiles to store user selections when exporting grades.

When a user exports grades, her selections of items and options are lost. The standard export plugins will present all grade items selected and a default selection of options each time.

This can be tiring when there are lots of activities in a course, so ```profiles``` provides the capability for storing user's selections.

It also lets the user pick the file format, avoiding the need to access a different plugin for each file format.

The output files are produced by Moodle's grade export functions. This means that using this plugin you get the same output than using the standard grade export menu.

## Functionality

- save/load/remove profiles that store the selections of grade items and options for exporting.
- shows ```new item``` when new grade items are added after saving the profile.
- supports exporting as ods (Open Document Spreadsheet), Excel and plain text files.

## Installing via uploaded ZIP file

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/grade/export/profiles

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
