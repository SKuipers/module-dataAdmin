# Gibbon Module: Data Admin
Provides extended import and export functionality for migrating data.

Features
========

* Importing:
  - Multi-part import form for improved control over incoming data
  - Assign columns to database values ad-hoc and can skip non-required columns
  - Performs a dry-run before importing to see the anticipated results
  - Filters and validates many types of data (dates, emails, urls, roles, etc)
  - Handles relational fields (eg: transforms usernames into gibbonPersonID on import)
  - Remebers the column order from the last import to speed up future imports

* Exporting:
  - Export the structure of a table with all importable columns pre-filled
  - Export an entire table of data (beta)
  - Exports include ID fields which can be synced when re-importing

* File Types:
  - Comma-Separated Values (.csv)
  - Office Open XML (.xlsx) Excel 2007 and above
  - BIFF 5-8 (.xls) Excel 95 and above
  - Open Document Format/OASIS (.ods)
  - SpreadsheetML (.xml) Excel 2003 (import only)

* Database & Logging:
  - Create a database snapshot before importing to rollback changes if needed
  - Records page helps identify duplicate and orphaned rows
  - Keeps import logs tracking the user who made them and the results of the import

* Custom Imports _(advanced)_:
   - Imports defined with a flexible .yml syntax (modify or write your own)
   - Set a custom import directory in settings


Installation & Support
======================

Installation instructions:

1. Backup your database and installation files.
2. Download and unzip the latest version of the module
3. Copy the module into your Gibbon modules folder
3. Login to your Gibbon installation and go to Admin > Manage Modules and press the Install icon

For support contact sandra.kuipers [at] tis.edu.mo


Change Log
==============

All notable changes to this project will be documented in the [CHANGELOG](CHANGELOG.md) file.