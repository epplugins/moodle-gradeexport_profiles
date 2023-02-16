# Releases

Functionality and some description of each release.

## [0.2] - 2023-02-16

### Functionality

- Export as ods, excel and plan text file.
- save/load/remove profiles where the user stores which grade items are exported, and what are the options for exporting.
- Shows ```new item``` when a new grade item was added after saving the profile.
- Remembers last state, even if it was not saved as a persistent profile.

### Description

- It's a fork of ```gradeexport_ods```.
- The code needs some cleaning. There are pieces inherited from the ```gradeexport_ods``` plugin that could be safely removed.
- javascript code was added to handle changes in the checkboxes and such, and it may seem cumbersome. This had to be done this way to preserve compatibility with the classes for exporting.
