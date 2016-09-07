# Gibbon Module: Data Admin
Provides extended import and export functionality for migrating data. Also adds a database snapshot function.

Features
========

* A multi-part import form for improved control over incoming data
* Performs a dry-run before importing to see the anticipated results
* Handles relational fields (eg: transforms usernames into gibbonPersonID on import)
* Filters and validates many types of data (dates, emails, urls, roles, etc)
* Assign columns to database values ad-hoc and can skip non-required columns
* Choose to Update & Insert, Update Only or Insert Only when importing
* Create a database snapshot before importing to rollback changes if needed
* Keeps import logs tracking the user who made them and the results of the import
* Remebers the column order from the last import to speed up future imports
* Choose to export an excel file with all importable columns pre-filled
* Imports defined with a flexible YML syntax

Installation & Support
======================

Installation instructions:

1. Backup your database and installation files.
2. Download and unzip the latest version of the module 
3. Copy the module into your Gibbon modules folder
3. Login to your Gibbon installation and go to Admin > Manage Modules and press the Install icon

For support contact sandra.kuipers [at] tis.edu.mo 


In Development
==============

* Handle Excel file-types
* Custom user-defined imports (advanced use only)
* Record Admin: view record totals, spot missing/orphaned records