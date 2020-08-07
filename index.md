## Notetaker module

A simple notetaker activity plugin for Moodle that allows a student to create notes within a course.

If the notetaker instance is set to allow public notes, the student can choose whether their notes should be private and only visible to themselves, or to make them public and share them with other course participants.

Teachers can manage all notes including those set to private.

Each note can be tagged. Notes can be searched by name.

| Notetaker overview page  |  
|:-------------------------:|
|![image](https://user-images.githubusercontent.com/26649166/89666872-06ae3280-d8d3-11ea-9866-27cb0debe960.png) |

## Installation

Follow the general [installing plugins](https://docs.moodle.org/39/en/Installing_plugins) documentation. 

## Usage

### Activity configuration

1. Select 'Notetaker' from the activity chooser. 
2. In _Edit settings > General > Description_, text entered here will display at the top of the main notetaker page.
3. In _Edit settings > Appearance > Allow public notes_, choose Yes if you wish to give students the option to make their notes public. 
4. Save and display.

### Adding notes

1. On the course page, click on the notetaker activity.
2. Click on the _Add note_ button.
3. Enter note title and note content (required) and tags (optional).
4. If _Allow public notes_ was set to Yes during activity configuration, _Make note public_ will be available. If set to Yes, the note will be visible to all course participants, otherwise the note will be private.
5. Save changes.

| Add note            |  
|:-------------------------:|
|![image](https://user-images.githubusercontent.com/26649166/89674681-75de5380-d8e0-11ea-81ab-09fba44c79b4.png) |

### Managing notes

1. On the course page, click on the notetaker activity. 
2. Displayed here are previously added notes. If public notes are permitted, students can view all of their own private notes plus all public notes made by anybody. Teachers see an overview of all notes irrespective of privacy setting. 
3. Notes can be searched by name.
4. To manage a note, click _View_ on the note card on the notetaker overview page. At the bottom of the note, the student can _Edit_ or _Delete_ their own notes only. Teachers can manage all notes. 

Search notes            |  Manage note
:-------------------------:|:-------------------------:
![image](https://user-images.githubusercontent.com/26649166/89668480-b5537280-d8d5-11ea-9ebd-9229e2fbbaee.png)  |  ![image](https://user-images.githubusercontent.com/26649166/89668758-33b01480-d8d6-11ea-8724-c88056d71711.png)

### Video demo

<a href="https://youtu.be/tYqt--dvqRI" target="_blank"><img src="https://user-images.githubusercontent.com/26649166/89675974-b6d76780-d8e2-11ea-9cb6-452bad42ce3e.png" 
alt="" width="470" height="240" border="10" /></a>



## Further information

[![Build Status](https://travis-ci.org/jlb224/notetaker.svg?branch=master)](https://travis-ci.org/jlb224/notetaker)

Report a bug, or suggest an improvement: <https://github.com/jlb224/moodle-mod_notetaker/issues>

## Contact details

Any questions or suggested improvements to: Jo Beaver <bvredesign@gmail.com>

## License

2020 Jo Beaver <bvredesign@gmail.com>

This program is free software: you can redistribute it and/or modify it underthe terms of the GNU General Public License as published by the Free SoftwareFoundation, either version 3 of the License, or (at your option) any laterversion.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR APARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along withthis program.  If not, see <http://www.gnu.org/licenses/.>
