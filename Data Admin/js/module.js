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

	$(document).ready(function () {
		$(".columnOrder").each( columnSampleData );
	});

	$(".columnOrder").on('change', columnSampleData );


	function columnSampleData(){

		var textBox = $(this).parent().find(".columnText");

		textBox.attr("readonly", $(this).val() != columnDataCustom );
		textBox.attr("disabled", $(this).val() != columnDataCustom );


		if ( $(this).val() == columnDataFunction ) {
			textBox.attr("value", $(this).find("option:selected").data("function")+"()" );
		}
		else if ( $(this).val() == columnDataCustom ) {
			textBox.attr("value", "" );
		}
		else if ( $(this).val() == columnDataSkip ) {
			textBox.attr("value", "*skipped*" );
		}
		else if ( $(this).val() >= 0 ) {

			if ( $(this).val() in csvFirstLine ) {
				textBox.attr("value", csvFirstLine[ $(this).val() ] );
			}
			else {
				textBox.attr("value", "" );
			}
		}

	}

	$("#ignoreErrors").click( function() {
		if ( $(this).is(':checked') ) {
			$(this).val( 1 );
			$( "#submitStep3" ).prop( "disabled", false);
			$( "#submitStep3" ).prop( "value", "Submit");
		} else {
			$(this).val( 0 );
			$( "#submitStep3" ).prop( "disabled", true);
			$( "#submitStep3" ).prop( "value", "Cannot Continue");
		}
	});
}); 