/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

jQuery(function($){

    $("select.columnOrder").on('change', function(){

        var currentSelection = $(this).val();
        var textBox = $(this).parent().parent().find('input.columnText');

        textBox.prop("readonly", currentSelection != columnDataCustom );
        textBox.prop("disabled", currentSelection != columnDataCustom );

        if ( currentSelection == columnDataFunction ) {
            textBox.val("*generated*");
        } else if ( currentSelection == columnDataCustom ) {
            textBox.val("");
        } else if ( currentSelection == columnDataSkip ) {
            textBox.val("*skipped*");
        } else if ( currentSelection >= 0 ) {
            if ( currentSelection in csvFirstLine ) {
                textBox.val(csvFirstLine[ currentSelection ] );
            } else {
                textBox.val("");
            }
        }
    });
    $("select.columnOrder").change();

	$("#ignoreErrors").click(function() {
		if ($(this).is(':checked')) {
			$(this).val( 1 );
			$("#submitStep3").prop("disabled", false).prop("type", "submit").prop("value", "Submit");
		} else {
			$(this).val( 0 );
			$("#submitStep3").prop("disabled", true).prop("value", "Cannot Continue");
		}
	});
}); 
