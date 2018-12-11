CHANGELOG
=========

## [1.3.07] - 2018-12-11
### Added
- Handle importing and exporting relational values that are CSV (eg: All Roles)

## [1.3.06] - 2018-09-26
### Added
- Added application/octet-stream as a valid MIME type
- Fixed missing Find Usernames action in manifest.php
- Made the All Roles field required for all user imports

## [1.3.05] - 2018-06-11
### Added
- Added a tool to help find usernames from a spreadsheet with only names

## [1.3.04] - 2018-06-06
### Fixed
- Generated passwords for user imports are output on the final step

## [1.3.03] - 2018-05-24
### Fixed
- Restored missing skip column and custom value options post-ooification
- Improve database validation for non-existent tables

## [1.3.02] - 2018-05-04
### Added
- Updated database connections for compatability with v16 database Connection interface

## [1.3.01] - 2018-03-07
### Added
- New import type for importing parent login codes from the Meet The Teacher booking system

## [1.3.00] - 2018-02-13
### Added
- Gibbon v16 requirement: autoloader changes
- Added composer and required Symfony\Component\Yaml library
- This version is not backwards compatible with v15 or earlier

## [1.2.03] - 2017-10-16
### Fixed
- Fix v14 Form class compatibility in Combine Similar Fields

## [1.2.02] - 2017-10-16
### Added
- Gibbon v14 requirement: new Form classes
- Data Tool to help Combine Similar Fields in User, Staff and Family data
- Import types for External Assessment
### Fixed
- Updated namespaces and autoloader in module classes

## [1.2.0] - 2017-03-06
### Added
- Gibbon v13 requirement: updated importer class dependancy
- Improved internationalization: better handling of multi-byte strings
- Detects and generates an error for fields that shouldn't have spaces
- Enum field errors will display a set/subset of expected options
### Fixed
- WAMP and Windows support: now handles backslashes in directory path
- Fixed filetype detection if file extension was uppercase

## [1.1.0] - 2016-12-06
### Headlines
- Added support for importing Office Open XML (.xlsx) Excel 2007 and above
- Added support for importing BIFF 5-8 (.xls) Excel 95 and above
- Added support for importing SpreadsheetML (.xml) Excel 2003
- Added support for importing Open Document Format/OASIS (.ods)
### Significant Changes
- Export to Excel now handles relational data (eg: gives the unique name of courses, rather than the database ID)
- Importing can require additional user permission based on the type of data (optional)
- Added Manage Records page: View row counts & export whole tables
- Added import types: Markbook, Medical forms, Timetable classes, Student Notes, Special days
### Tweaks & Bug Fixes
- Adjusted the Import & Export list to group by Module
- Added custom imports folder path in Settings
- Added snapshots folder path in Settings
- Added default export file type in Settings
- Settings page checks PHPExcel compatability

## [1.0.0] - 2016-11-07
### Initial release

[Unreleased]: https://github.com/SKuipers/module-dataAdmin/compare/v1.2.03...HEAD
[1.2.03]: https://github.com/SKuipers/module-dataAdmin/compare/v1.2.02...v1.2.03
[1.2.02]: https://github.com/SKuipers/module-dataAdmin/compare/v1.2...v1.2.02
[1.2.0]: https://github.com/SKuipers/module-dataAdmin/compare/v1.1...v1.2
[1.1.0]: https://github.com/SKuipers/module-dataAdmin/compare/v1.0...v1.1
[1.0.0]: https://github.com/SKuipers/module-dataAdmin/releases/tag/v1.0
