jQuery(document).ready(
		function($) {
			
			'use strict';
			const { __, _x, _n, sprintf } = wp.i18n;
			
			$(document.body).on('click', '.labelgrid-toolbar-update-catalog A',
					function() {
						alert(__('Catalog update started - Please do not refresh the page until the process ends.', 'labelgrid-tools'));
						$.ajax({
							type : 'POST',
							url : lgbar.ajaxurl,
							data : {
								action : 'lgt_sync_once',
							},
							dataType : "json",
							success : function(response) {
							
								if(response=='1') alert(__('Catalog was correctly updated', 'labelgrid-tools'));
								else if(response=='2') alert(__('ERROR: The first import is necessary to sync manually your catalog in LabelGrid Tools->Sync content.', 'labelgrid-tools'));
								else if(response=='0') alert(__('Sync is disabled in General Settings. Sync will be not executed.', 'labelgrid-tools'));
								else alert(__('Unknown error', 'labelgrid-tools'));

							}
						}).fail(function(response) {
							if (window.console && window.console.log) {
								console.log(response);
								alert(__('Unknown error 2', 'labelgrid-tools'));
							}
						});

					});

		});