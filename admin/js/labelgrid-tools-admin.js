jQuery(document)
	.ready(
		function($) {
			'use strict';
			const { __, _x, _n, sprintf } = wp.i18n;

			var LG_Import = {

				init: function() {
					this.submit();
					this.dismiss_message();
				},

				submit: function() {

					var self = this;

					$(document.body)
						.on(
							'submit',
							'#lgt_sync',
							function(e) {
								e.preventDefault();

								var submitButton = $(this)
									.find(
										'input[type="submit"]');

								if (!submitButton
									.hasClass('button-disabled')) {

									var data = $(this)
										.serialize();

									submitButton
										.addClass('button-disabled');

									$('#delete_artists').attr(
										"disabled", true);
									$('#delete_releases').attr(
										"disabled", true);
									$('#delete_genres').attr(
										"disabled", true);
									$('#delete_labels').attr(
										"disabled", true);
									$('#delete_artist_category')
										.attr("disabled",
											true);
									$('#delete_release_tag')
										.attr("disabled",
											true);
									$('#delete_artist_tag')
										.attr("disabled",
											true);
									$('#force_sync_releases')
										.attr("disabled",
											true);

									$(this)
										.find(
											'.notice-wrap')
										.remove();
									$(this)
										.append(
											'<div class="notice-wrap"><div class="info">'+__('DO NOT CLOSE THIS WINDOW UNTIL THE UPDATE IS COMPLETE', 'labelgrid-tools')+'</div><div class="task-name">'+__('Initializing', 'labelgrid-tools')+'</div><span class="spinner is-active"></span><div class="lg-progress"><div></div></div></div>');

									// start the process
									self.process_step(1, data,
										null, self,
										true);

								}

							});
				},

				process_step: function(step, data, actions, self,
					first) {

					$.ajax(
						{
							type: 'POST',
							url: ajaxurl,
							data: {
								form: data,
								action: 'lgt_sync',
								step: step,
								actions: actions,
								firstAction: first
							},
							dataType: "json",
							success: function(response) {

								// We need to get the actual
								// in progress form, not all
								// forms on the page
								var export_form = $(
									'#lgt_sync').find(
										'.lg-progress')
									.parent().parent();
								var notice_wrap = export_form
									.find('.notice-wrap');

								if ('done' == response.data.step
									|| response.error) {

									if (response.error) {
										export_form
											.find(
												'.button-disabled')
											.removeClass(
												'button-disabled');
										$('#delete_artists')
											.attr(
												"disabled",
												false);
										$(
											'#delete_releases')
											.attr(
												"disabled",
												false);
										$('#delete_genres')
											.attr(
												"disabled",
												false);
										$('#delete_labels')
											.attr(
												"disabled",
												false);
										$(
											'#delete_artist_category')
											.attr(
												"disabled",
												false);
										$(
											'#delete_release_tag')
											.attr(
												"disabled",
												false);
										$(
											'#delete_artist_tag')
											.attr(
												"disabled",
												false);
										$(
											'#force_sync_releases')
											.attr(
												"disabled",
												false);
										var error_message = response.message;
										notice_wrap
											.html('<div class="updated error"><p>'
												+ error_message
												+ '</p></div>');

									} else {

										if ($
											.isArray(response.data.actions)) {
											$(
												'.lg-progress div')
												.animate(
													{
														width: '0%',
													},
													0,
													function() {
														// Animation
														// complete.
													});

											$('.task-name')
												.text(
													'Processing '
													+ response.data.actions[0]);

											LG_Import
												.process_step(
													1,
													data,
													response.data.actions,
													self,
													null);
										} else {
											export_form
												.find(
													'.button-disabled')
												.removeClass(
													'button-disabled');
											$(
												'#delete_artists')
												.attr(
													"disabled",
													false);
											$(
												'#delete_releases')
												.attr(
													"disabled",
													false);
											$(
												'#delete_genres')
												.attr(
													"disabled",
													false);
											$(
												'#delete_labels')
												.attr(
													"disabled",
													false);
											$(
												'#delete_artist_category')
												.attr(
													"disabled",
													false);
											$(
												'#delete_release_tag')
												.attr(
													"disabled",
													false);
											$(
												'#delete_artist_tag')
												.attr(
													"disabled",
													false);
											$(
												'#force_sync_releases')
												.attr(
													"disabled",
													false);
											var success_message = response.data.message;
											notice_wrap
												.html('<div id="lg-batch-success" class="updated notice is-dismissible"><p>'
													+ success_message
													+ ' <span class="notice-dismiss"></span></p></div>');
										}
									}

								} else {

									$('.lg-progress div')
										.animate(
											{
												width: response.data.percentage
													+ '%',
											},
											50,
											function() {
												// Animation
												// complete.
											});
									$('.task-name')
										.text(
											'Processing '
											+ response.data.actions[0]);
									LG_Import
										.process_step(
											parseInt(response.data.step),
											data,
											actions,
											self,
											null);
								}

							}
						}).fail(
							function(response) {
								if (window.console
									&& window.console.log) {
									console.log(response);
								}
							});

				},

				dismiss_message: function() {
					$(document.body).on(
						'click',
						'#lg-batch-success .notice-dismiss',
						function() {
							$('#lg-batch-success').parent()
								.slideUp('fast');
						});
				}

			};
			LG_Import.init();

			jQuery(".lg-log-filter-cat, .lg-level-filter-cat").change(function() {
				var catFilter = $(this).val();
				document.location.href = 'admin.php?page=labelgrid-tools-system-logs' + catFilter;
			});

			jQuery(".lg-gate-filter").change(function() {
				var catFilter = $(this).val();
				document.location.href = 'admin.php?page=labelgrid-tools-gate-entries' + catFilter;
			});

			jQuery(".lg-type-filter").change(function() {
				var catFilter = $(this).val();
				document.location.href = 'admin.php?page=labelgrid-tools-gate-entries' + catFilter;
			});



		});