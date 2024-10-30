jQuery(document).ready(function($) {
	'use strict';
	const { __, _x, _n, sprintf } = wp.i18n;

	var sopen = 0;
	var lgt_gate_user_id = '';
	var lgt_collect_email_force = null;
	var y_offsetWhenScrollDisabled = 0;
	var lgt_gate_id_clean = null;
	var lgt_is_mobile = null;
	var lgt_mobile_actions = null;

	var loadedga = null;
	var loadedfbq = null;
	
	if (typeof ga === 'function') loadedga = true;
	if (typeof fbq === 'function') loadedfbq = true;

	var callbacks = {
		get_accounts_part: function(lgt_mobile_actions, service_run) {

			// CREATE REMOTE SESSION
			open_session()
				.then(
					function() {
						var html = '';
						
						if (lgt_gate_services.length) {

							$.each(lgt_gate_services, function(id, service) {
								html += '<div class="register_services"><a href="#" class="" id="' + service + '_login_wizard"><div class="gateimage ' + service + '"></div>' + __('Login with', 'labelgrid-tools') + ' <span class="gateservice">' + service + '</span></a></div>';
							});

							var message = __('Please connect your accounts to the following Services to proceed:', 'labelgrid-tools');

							if (lgt_gate_min_accounts != 'all') {
								html += '<div class="notelimit">' + __('You need to connect minimum', 'labelgrid-tools') + ' ' + lgt_gate_min_accounts + ' ' + __('accounts', 'labelgrid-tools') + '.</div>'
							}

							// class registered todo
							$('#modalwindow-freedownload #popdescription').html(message);
							$('#modalwindow-freedownload #content').html(html);

							if ($("#modalwindow-freedownload #btn-gate-continue")) $("#modalwindow-freedownload #btn-gate-continue").remove();

							var test = $('<button/>',
								{
									text: lgt_download_text,
									class: 'btn btn-primary',
									id: 'btn-gate-continue-2',
									click: function() { return_download(); },
									disabled: true
								});

							$(".buttonP").append(test).end();


						}
						else {
							alert('ERROR: No Services Specified');
						}

						$('#modalwindow-freedownload #spotify_login_wizard').click(function(e) {
							e.preventDefault();
							login('spotify');
						});

						$('#modalwindow-freedownload #soundcloud_login_wizard').click(function(e) {
							e.preventDefault();
							login('soundcloud');
						});

						$('#modalwindow-freedownload #twitter_login_wizard').click(function(e) {
							e.preventDefault();
							login('twitter');
						});

						$('#modalwindow-freedownload #youtube_login_wizard').click(function(e) {
							e.preventDefault();
							login('youtube');
						});
						
						if(lgt_mobile_actions){
						
						lgt_mobile_actions.forEach(function(entry) {
							$('#modalwindow-freedownload #' + entry + '_login_wizard').addClass('registered');
							checkAndAdd(service_run, entry);
						});
						
						

						if (arraysEqual(service_run, lgt_gate_services) === true) {
							$('button#btn-gate-continue-2.btn.btn-primary').prop("disabled", false);
						}
						}
					},
					function(reason) {
						console.error('Something went wrong', reason);
					});

		},
		get_accounts_presave: function() {

			// CREATE REMOTE SESSION
			open_session(true)
				.then(
					function() {

						var html = '';

						html += '<div class="register_services"><a href="#" class="" id="spotify_login_wizard"><div class="gateimage spotify"></div>' + __('Login with', 'labelgrid-tools') + ' <span class="gateservice">Spotify</span></a></div>';

						var message = __('Please connect your accounts to the following Services to proceed:', 'labelgrid-tools');

						// class registered todo
						$('#modalwindow-presave #popdescription').html(message);
						$('#modalwindow-presave #content').html(html);

						$("#modalwindow-presave .notelimit").remove();

						if ($("#modalwindow-presave #btn-gate-continue")) $("#modalwindow-presave #btn-gate-continue").remove();

						$('#modalwindow-presave #spotify_login_wizard').click(function(e) {
							e.preventDefault();
							login_presave('spotify');
						});
					},
					function(reason) {
						console.error('Something went wrong', reason);
					});

		},
		get_playlist_save: function(presave_playlists) {
			if ($("#modalwindow-presave #btn-gate-continue")) $("#modalwindow-presave #btn-gate-continue").remove();
			$("#modalwindow-presave .notelimit").remove();

			if (loadedga) ga(getGa('send'), { hitType: 'event', eventCategory: 'presave', eventAction: 'saved', eventLabel: 'Presave - Saved', eventValue: lgt_gate_id_clean });
			if (loadedfbq) fbq('trackCustom', 'PresaveActionSaved', { id: lgt_gate_id_clean });

			addPresaveTask('track_album_save');

			// Reset URL
			var currentUrl = window.location.href;
			var beforeQueryString = currentUrl.split("?")[0];
			window.history.replaceState({}, document.title, beforeQueryString);

			if (typeof presave_playlists === 'object' && presave_playlists !== null) {
				var data = presave_playlists;
			}
			else {
				var data = JSON.parse(presave_playlists);
			}

			$('#modalwindow-presave #popdescription').html(__('<strong>Release correctly Pre-Saved!<br>We will save it in your library on', 'labelgrid-tools') + ' ' + lgt_release_date + '.</strong><br><br>' + __('You can optionally add the track/s to one of your playlists:', 'labelgrid-tools'));

			var data_filtered = [];
			$.each(data.playlists, function(index, chunk) {

				if (chunk.owner.id == data.user || chunk.owner.collaborative == true)
					data_filtered.push(chunk);
			});
			data.items = data_filtered;

			var templateSourcep = document.getElementById('lgt_playlist_template_spotify').innerHTML,
				templatep = Handlebars.compile(templateSourcep);

			var html = templatep(data);

			$('#modalwindow-presave #content').html(html);

			fluidDialog();


			$('#playlistdata').on('click', '.playlistadd', function(e) {
				e.preventDefault();
				if ($(this).data("playlistid") && lgt_release_upc) {

					if (loadedga) ga(getGa('send'), { hitType: 'event', eventCategory: 'presave', eventAction: 'playlist_save', eventLabel: 'Presave - Playlist', eventValue: lgt_gate_id_clean });
					if (loadedfbq) fbq('trackCustom', 'PresaveActionSavedInPlaylist', { id: lgt_gate_id_clean });

					addPresaveTask('add_to_playlist', $(this).data("playlistid"));

				}

			});

		}
	};



	function open_dialog(name) {

		$(name).dialog({
			draggable: false,
			autoOpen: true,
			resizable: false,
			modal: true,
			width: '600', // overcomes width:'auto' and maxWidth bug
			maxWidth: 600,
			height: 'auto',
			maxHeight: '100%',
			fluid: true, // new option
			dialogClass: 'smediapopup',
			create: function(event, ui) {
				sopen = 1;
				disableScrollOnBody();
			},
			close: function(event, ui) {
				sopen = 0;
				enableScrollOnBody();
			},
			open: function() {
				var $formTitle = $("p.title", this);
				$formTitle.remove();
				$(this).prev().find(".ui-dialog-title").html('<a href="#" id="lg-gate-help-ico">?</a><div id="lg-gate-help-dialog" title="LabelGrid Gate Help">' + __('LabelGrid Gate allows you to Pre-save / Download / Follow links in exchange of social media actions.<br><br>The App will connect with your social accounts and will perform only the predefined actions listed in the Gate Description.<br><br>No further actions are performed.', 'labelgrid-tools') + '</div>');

				var dialog = $("#lg-gate-help-dialog").dialog({
					autoOpen: false,
					height: 400,
					width: 350,
					modal: true,
					buttons: {
						Close: function() {
							dialog.dialog("close");
						}
					},
					close: function() { }
				});

				$("#lg-gate-help-ico").button().on("click", function() {
					dialog.dialog("open");
				});


			}
		});

		$(".smediapopup *").blur();

	}

	function email_collection(windowid, function_name, e) {


		lgt_collect_email_force = lgt_collect_email.replace("yes-", "");
		$(windowid + ' #popdescription').html(__('Please provide your e-mail to proceed:', 'labelgrid-tools') + ' (' + lgt_collect_email_force + ')');

		var html = '<input type="email" name="lggate-email" class="lggate-email" id="lggate-email" placeholder="' + __('Insert your e-mail', 'labelgrid-tools') + '">';

		$(windowid + ' #content').html(html);


		$(windowid + ' #lggate-email').keydown(function(e) {
			if (e.keyCode == 13) {
				e.preventDefault();
				return false;
			}
		});


		$(windowid + " #btn-gate-continue").remove();

		var buttonn = $('<button/>',
			{
				text: 'Continue',
				class: 'btn btn-primary',
				id: 'btn-gate-continue-2',
				click: function() {


					if (lgt_collect_email == 'yes-obligatory') {
						html += ' required ';
					}

					if (
						(lgt_collect_email == 'yes-obligatory' &&
							(!$('#lggate-email').val() ||
								($('#lggate-email').val() && !isEmail($('#lggate-email').val()))
							) || ($('#lggate-email').val() && !isEmail($('#lggate-email').val()))
						)
					) alert(__('Please insert a valid email.', 'labelgrid-tools'))
					else {

						var data_session = {
							'email': $('#lggate-email').val(),
							'gate_id': lgt_gate_id
						};

						$.ajax({
							url: lgt_install_url + '/wp-json/lgt-gate-api/v1/save-email',
							data: data_session,
							type: 'POST',
							success: function(data) {
							}
						});

						$(windowid + " #btn-gate-continue-2").remove();

						callbacks[function_name]();
					}

				},
				disabled: false
			});

		$(".buttonP").append(buttonn).end();

	}

	function isEmail(email) {
		var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		return regex.test(email);
	}





	function lg_set_session(hash) {

		lgt_gate_user_id = hash;

	}

	function open_session(is_presave = false) {
		return new Promise(
			function(resolve, reject) {
				$.ajax({
					url: lgt_install_url + '/wp-json/lgt-gate-api/v1/check-session',
					type: 'GET',
					success: function(response) {


						lg_set_session(response.session);


						var data_session = {
							'user_id': lgt_gate_user_id,
							'gate_id': lgt_gate_id,
							'is_presave': is_presave,
						};

						$.ajax({
							url: lgt_install_url + '/wp-json/lgt-gate-api/v1/add-session',
							data: data_session,
							type: 'POST',
							success: function(data) {
								resolve();
							}
						});


					}
				});

				setTimeout(() => reject(new Error()), 5000);
			});
	}




	function return_download() {
		if (loadedga) ga(getGa('send'), { hitType: 'event', eventCategory: 'gate', eventAction: 'download', eventLabel: 'Gate - Download', eventValue: lgt_gate_id });

		if (loadedfbq) fbq('trackCustom', 'GateConfirmed', { id: lgt_gate_id });

		if (lgt_gate_download_type == 'register_contest') {
			$.ajax({
				url: lgt_install_url + "/lgtools-downloadgate/" + lgt_gate_user_id + "/" + lgt_gate_id + "/",
				type: 'GET',
				success: function(data) {
					alert('Contest correctly entered');
				}
			});

		}
		else window.open(lgt_install_url + "/lgtools-downloadgate/" + lgt_gate_user_id + "/" + lgt_gate_id + "/", "_self");
	}


	function disableScrollOnBody() {
		y_offsetWhenScrollDisabled = $(window).scrollTop();
		$('body').addClass('scrollDisabled').css('margin-top', -y_offsetWhenScrollDisabled);
	}

	function enableScrollOnBody() {
		$('body').removeClass('scrollDisabled').css('margin-top', 0);
		$(window).scrollTop(y_offsetWhenScrollDisabled);
	}


	function fluidDialog() {
		var $visible = $(".ui-dialog:visible");
		// each open dialog
		$visible.each(function() {
			var $this = $(this);
			var dialog = $this.find(".ui-dialog-content").data("ui-dialog");
			// if fluid option == true
			if (dialog.options.fluid) {
				var wWidth = $(window).width();
				// check window width against dialog width
				if (wWidth < (parseInt(dialog.options.maxWidth) + 50)) {
					// keep dialog from filling entire screen
					$this.css("max-width", "90%");
				} else {
					// fix maxWidth bug
					$this.css("max-width", dialog.options.maxWidth + "px");
				}
				// reposition dialog
				dialog.option("position", dialog.options.position);
			}
		});
		disableScrollOnBody();
	}

	function httpGet(theUrl) {
		if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
			var xmlhttp = new XMLHttpRequest();
		}
		else {// code for IE6, IE5
			var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				return xmlhttp.responseText;
			}
		}
		xmlhttp.open("GET", theUrl, false);
		xmlhttp.withCredentials = true;
		xmlhttp.send();
		return xmlhttp.responseText;
	}

	function returnForm(body, url, data) {
		var form = document.createElement("form");
		form.method = "POST";
		form.id = "openwindowform";
		form.action = url;
		form.style.display = "none";

		for (var key in data) {
			var input = document.createElement("input");
			input.type = "hidden";
			input.name = key;
			input.value = data[key];
			form.appendChild(input);
		}
		body.appendChild(form);
		form.submit();
		body.removeChild(form);
	}

	function checkAndAdd(arr, name) {

		var found = arr.some(function(el) {
			return el === name;
		});
		if (!found) { arr.push(name); }
	}

	function login(service) {

		var actions_filtered = function(lgt_gate_actions) {
			var filtered;
			var selected_index;
			for (var i = 0; i < lgt_gate_actions.length; i++) {
				if (lgt_gate_actions[i]._type == service) {
					selected_index = i;
				}
			}
			return selected_index;
		};

		var data_actions = JSON.stringify(lgt_gate_actions[actions_filtered(lgt_gate_actions)].actions);

		var url = 'https://gate.labelgrid.com/gate/service/' + service + '/auth';
		
		
		var mylistener = function(event) {
			var hash = JSON.parse(event.data);
			$('#modalwindow-freedownload #' + hash.type + '_login_wizard').addClass('registered');

			checkAndAdd(service_run, hash.type);
			if (arraysEqual(service_run, lgt_gate_services) === true) {
				$('button#btn-gate-continue-2.btn.btn-primary').prop("disabled", false);
			}
			window.removeEventListener("message", mylistener, false);
		}
		window.addEventListener("message", mylistener, false);
		

		var width = 450,
			height = 730,
			left = (screen.width / 2) - (width / 2),
			top = (screen.height / 2) - (height / 2);

		if (lgt_is_mobile == "1") {
			var breturn = document.body;
		}
		else {
			var win = window.open("", "Title", 'menubar=no,location=no,resizable=no,scrollbars=no,status=no, width=' + width + ', height=' + height + ', top=' + top + ', left=' + left);
			var breturn = win.document.body;
		}

		returnForm(breturn, url, {
			service: service,
			actions: data_actions,
			user_id: lgt_gate_user_id,
			gate_id: lgt_gate_id,
			referer: window.location.href,
			is_mobile: lgt_is_mobile,
			source: window.location.protocol + '//' + window.location.hostname
		});




	}

	function login_presave(service) {

		var data_actions = JSON.stringify(lgt_presave_actions);

		var url = 'https://gate.labelgrid.com/gate/service/spotify/auth';


		var mylistener = function(event) {
			callbacks['get_playlist_save'](event.data);
			window.removeEventListener("message", mylistener, false);
		}
		window.addEventListener("message", mylistener, false);

		var width = 450,
			height = 730,
			left = (screen.width / 2) - (width / 2),
			top = (screen.height / 2) - (height / 2);

		if (lgt_is_mobile == "1") {
			var breturn = document.body;
		}
		else {
			var win = window.open("", "Title", 'menubar=no,location=no,resizable=no,scrollbars=no,status=no, width=' + width + ', height=' + height + ', top=' + top + ', left=' + left);
			var breturn = win.document.body;
		}

		returnForm(breturn, url, {
			service: service,
			actions: data_actions,
			user_id: lgt_gate_user_id,
			gate_id: lgt_gate_id,
			release_date: lgt_release_date,
			referer: window.location.href,
			is_mobile: lgt_is_mobile,
			source: window.location.protocol + '//' + window.location.hostname
		});

	}


	function addPresaveTask(action, playlist_id = null) {


		var data_session = {
			'task_action': action,
			'release_id': lgt_gate_id,
			'release_upc': lgt_release_upc,
			'release_date': lgt_release_date,
			'playlist_id': playlist_id
		};

		$.ajax({
			url: lgt_install_url + '/wp-json/lgt-gate-api/v1/add-presave',
			data: data_session,
			type: 'POST',
			success: function(response) {
				if (response.message == 'ok' && playlist_id != null) alert(__('Track/s will be added to the selected playlist on ', 'labelgrid-tools') + lgt_release_date);
				else if (playlist_id != null) alert(__('Error scheduling the pre-save. Please contact an Administrator.', 'labelgrid-tools'));
			}
		});

	}


	function arraysEqual(arr1, arr2) {
		arr1.sort();
		arr2.sort();
		if (lgt_gate_min_accounts == 'all') {
			if (arr1.length !== arr2.length)
				return false;
			for (var i = arr1.length; i--;) {
				if (arr1[i] !== arr2[i])
					return false;
			}
		}
		else {
			if (arr1.length < lgt_gate_min_accounts)
				return false;
		}
		return true;
	}


	function lg_setupMobileVars() {

		return $.ajax({
			url: lgt_install_url + '/wp-json/lgt-gate-api/v1/mobile-check',
			type: 'GET'
		});
	}

	function lg_setupMobileVarsActions() {

		var actions = null;
		var queryString = window.location.search;
		var urlParams = new URLSearchParams(queryString);
		var type = urlParams.get('type')

		if (type) {
			var actions = type.split("|");
		}

		return actions;
	}

	function lg_setupMobileVarsPlaylists() {

		var data_session = {
			'release_id': lgt_gate_id,
		};

		$.ajax({
			url: lgt_install_url + '/wp-json/lgt-gate-api/v1/get-playlists',
			data: data_session,
			type: 'POST',
			success: function(data) {
				//console.log(data);
				callbacks['get_playlist_save'](data);
			}
		});

	}


	function isJson(str) {
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}




	if ($('#modalwindow-freedownload').length) {

		// on window resize run function
		$(window).resize(function() {
			if (sopen == 1) {
				fluidDialog();
			}
		});

		// catch dialog if opened within a viewport smaller than the dialog
		// width
		$(document).on("dialogopen", ".ui-dialog", function(event, ui) {
			fluidDialog();
		});


		var service_run = [];
		$('#lgt-freedownload-link').click(function(e) {
			e.preventDefault();

			if (loadedga) ga(getGa('send'), { hitType: 'event', eventCategory: 'gate', eventAction: 'start', eventLabel: 'Gate - Start', eventValue: lgt_gate_id });

			if (loadedfbq) fbq('trackCustom', 'GateStart', { id: lgt_gate_id });

			open_dialog("#modalwindow-freedownload");
		});

		$('#modalwindow-freedownload #btn-gate-continue').click(function(e) {
			e.preventDefault();

			if (lgt_collect_email == 'yes-obligatory' || lgt_collect_email == 'yes-optional') {

				email_collection('#modalwindow-freedownload', 'get_accounts_part', e);

			}
			else {
				callbacks['get_accounts_part'](null,null);
			}
		});


		$.when(lg_setupMobileVars(), lg_setupMobileVarsActions()).done(function(m1, m2) {
			lgt_is_mobile  = m1[0];
			lgt_mobile_actions = m2;

			//console.log(lgt_is_mobile);
			//console.log(lgt_mobile_actions);


			if (lgt_is_mobile == "1" && $.isArray(lgt_mobile_actions)) {
				open_dialog("#modalwindow-freedownload");
				callbacks['get_accounts_part'](lgt_mobile_actions, service_run);
			}

		});



	};

	if ($('#modalwindow-presave').length) {

		// on window resize run function
		$(window).resize(function() {
			if (sopen == 1) {
				fluidDialog();
			}
		});

		// catch dialog if opened within a viewport smaller than the dialog
		// width
		$(document).on("dialogopen", ".ui-dialog", function(event, ui) {
			fluidDialog();
		});

		//Presave

		lgt_gate_id_clean = lgt_gate_id.toString().replace('p', '');
		$('#lgt-presave-link').click(function(e) {
			e.preventDefault();

			if (loadedga) ga(getGa('send'), { hitType: 'event', eventCategory: 'presave', eventAction: 'start', eventLabel: 'PreSave - Star', eventValue: lgt_gate_id_clean });

			if (loadedfbq) fbq('trackCustom', 'PresaveStart', { id: lgt_gate_id_clean });


			open_dialog("#modalwindow-presave");
		});




		$('#modalwindow-presave #btn-gate-continue').click(function(e) {

			e.preventDefault();

			if (lgt_collect_email == 'yes-obligatory' || lgt_collect_email == 'yes-optional') {
				$("#modalwindow-presave .notelimit").remove();
				email_collection('#modalwindow-presave', 'get_accounts_presave', e);

			}
			else {
				callbacks['get_accounts_presave']();
			}
		});


		$.when(lg_setupMobileVars(), lg_setupMobileVarsActions()).done(function(m1, m2) {
			lgt_is_mobile = m1[0];
			lgt_mobile_actions = m2;
			//console.log(lgt_is_mobile);
			//console.log(lgt_mobile_actions);


			if (lgt_is_mobile == "1" && $.isArray(lgt_mobile_actions)) {

				open_dialog("#modalwindow-presave");

				// todo check if spotify
				//var mobile_actions = JSON.parse(lgt_mobile_actions);

				lg_setupMobileVarsPlaylists();


			}

		});


	};


});