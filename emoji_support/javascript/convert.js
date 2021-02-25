EE.cp.emoji_support = {

	buttons: $('[name=convert]'),

	init: function() {
		EE.cp.emoji_support._init();
	},

	_init: function() {
		console.log("Binding button!");
		this._bindButton();
	},

	/**
	 * Bind the Backup Database button to fire off the AJAX request and do the
	 * DOM manipulations necessary
	 */
	_bindButton: function() {
		var that = this;

		this.buttons.on('click', function(event) {
			event.preventDefault();
			that._disableButton(true);
			that._sendAjaxRequest(0);
		});
	},

	/**
	 * Disables the Backup Database button either to a working state or an error state
	 *
	 * @param	boolean	work	Whether or not to put the button in a working state
	 */
	_disableButton: function(work) {
		this.buttons.attr('disabled', true)

		if (work) {
			this.buttons.addClass('work')
			this.buttons.text = this.buttons.data('work-text');
		} else {
			this.buttons.addClass('disable')
		}
	},

	/**
	 * Re-enables a button after it has been disabled
	 */
	_enableButton: function() {
		this.buttons.attr('disabled', false)
			.removeClass('work')
			.removeClass('disable')
	},

	/**
	 * Handles the network requests to the backup endpoint
	 *
	 * @param	integer	offset		Offset at which to continue the backup
	 */
	_sendAjaxRequest: function(offset) {

		var data = {offset: 0},
			request = new XMLHttpRequest(),
			that = this;

		if (offset !== undefined) {
			data = {
				offset: offset,
			};
		}

		// Make a query string of the JSON POST data
		data = Object.keys(data).map(function(key) {
			return encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
		}).join('&');

		request.open('POST', EE.emoji_support.endpoint, true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		request.setRequestHeader('X-CSRF-TOKEN', EE.CSRF_TOKEN);

		request.onload = function() {
			try {
				var response = JSON.parse(request.responseText);
			} catch(e) {
				that._presentError(e);
				return;
			}

			if (request.status >= 200 && request.status < 400) {

				if (response.status == undefined) {
					that._presentError(response);
					return;
				}

				if (response.status == 'error') {
					that._presentError(response.message);
					return;
				}

				// Finished? Redirect to success screen
				if (response.status == 'finished') {
					that._updateProgress(100);
					window.location = EE.emoji_support.base_url;
					return;
				}

				// Keep CP session alive for large backups by faking mousemoveevents
				var event = document.createEvent('HTMLEvents');
				event.initEvent('mousemove', true, false);
				document.dispatchEvent(event);

				// Still more to do, update progress and kick off another AJAX request
				that._updateProgress(that._getPercentageForResponse(response));
				that._sendAjaxRequest(response.offset);
			} else {
				if (response.status == 'error') {
					that._presentError(response.message);
					return;
				}

				that._presentError(response);
			}
		};

		request.onerror = function() {
			that._presentError(response);
		};

		request.send(data);
	},

	/**
	 * Gets overall percentage of backup that has been completed
	 *
	 * @param	object	response	Parsed JSON response from AJAX request to
	 *   backup endpoint
	 */
	_getPercentageForResponse: function(response) {
		var progress = 0,
			total_commands = EE.emoji_support.total_commands;

		progress = Math.round(parseInt(response.offset) / EE.emoji_support.total_commands * 100);

		return progress > 100 ? 100 : progress;
	},

	/**
	 * Updates the progress bar UI to a set percentage
	 *
	 * @param	integer	percentage	Whole number (eg. 68) percentage
	 */
	_updateProgress: function(percentage) {
		var progress_bar = document.querySelectorAll('.progress')[0];

		progress_bar.style.width = percentage+'%';
	},


	/**
	 * Presents our inline error alert with a custom message
	 *
	 * @param	string	text	Error message
	 */
	_presentError: function(text) {
		var alert = EE.emoji_support.ajax_fail_banner.replace('%body%', text),
			alert_div = document.createElement('div'),
			form = document.querySelectorAll('form')[0];

		alert_div.innerHTML = alert;

		form.insertBefore(alert_div, form.firstChild);

		this._enableButton();
		this._disableButton();
	}
}


if (document.readyState != 'loading') {
	EE.cp.emoji_support.init();
} else {
	document.addEventListener('DOMContentLoaded', EE.cp.emoji_support.init);
}
