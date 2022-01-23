/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - http://codecanyon.net/licenses/standard
 */

if (typeof refreshBtnText === 'undefined') {
	var refreshBtnText = 'Refresh';
}

preventPageLoadingInIframe();

$(document).ready(function () {
	/* Confirm Actions Links */
	$(document).on('click', '.confirm-simple-action', function(e) {
		e.preventDefault(); /* prevents the submit or reload */
		
		try {
			let showCancelInfo = false;
			if (isAdminPanel) {
				if (isDemoDomain()) {
					return false;
				}
				showCancelInfo = true;
			}
			
			confirmSimpleAction(this, showCancelInfo);
		} catch (e) {
			jsAlert(e, 'error', false);
		}
	});
});

/**
 * Prevent the page to load in IFRAME by redirecting it to the top-level window
 */
function preventPageLoadingInIframe() {
	try {
		if (window.top.location !== window.location) {
			window.top.location.replace(siteUrl);
		}
	} catch (e) {
		console.error(e);
	}
}

/**
 * Open Login Modal
 */
function openLoginModal() {
	let quickLoginEl = document.getElementById('quickLogin');
	
	if (typeof(quickLoginEl) !== 'undefined' && quickLoginEl !== null) {
		let loginModal = new bootstrap.Modal(quickLoginEl, {});
		loginModal.show();
	}
}

/**
 * Create cookie
 * @param name
 * @param value
 * @param days
 */
function createCookie(name, value, days) {
	let expires;
	
	if (days) {
		let date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		expires = "; expires=" + date.toGMTString();
	} else {
		expires = "";
	}
	document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

/**
 * Read cookie
 * @param name
 * @returns {*}
 */
function readCookie(name) {
	let nameEQ = encodeURIComponent(name) + "=";
	let ca = document.cookie.split(';');
	
	for (let i = 0; i < ca.length; i++) {
		let c = ca[i];
		while (c.charAt(0) === ' ') {
			c = c.substring(1, c.length);
		}
		if (c.indexOf(nameEQ) === 0) {
			return decodeURIComponent(c.substring(nameEQ.length, c.length));
		}
	}
	
	return null;
}

/**
 * Delete cookie
 * @param name
 */
function eraseCookie(name) {
	createCookie(name, "", -1);
}

/**
 * Redirect URL
 * @param url
 */
function redirect(url) {
	window.location.replace(url);
	window.location.href = url;
}

/**
 * Raw URL encode
 * @param str
 * @returns {string}
 */
function rawurlencode(str) {
	str = (str + '').toString();
	
	return encodeURIComponent(str)
		.replace(/!/g, '%21')
		.replace(/'/g, '%27')
		.replace(/\(/g, '%28')
		.replace(/\)/g, '%29')
		.replace(/\*/g, '%2A');
}

/**
 * Check if a string is empty or null
 * @param str
 * @returns {boolean}
 */
function isEmptyValue(str) {
	return (!str || 0 === str.length);
}

/**
 * Check if a string is blank or null
 * @param str
 * @returns {boolean}
 */
function isBlankValue(str) {
	return (!str || /^\s*$/.test(str));
}

/**
 * Check if value is a string
 *
 * @param text
 * @returns {boolean}
 */
function isString(text) {
	if (typeof text !== 'undefined') {
		if (typeof text === 'string' || text instanceof String) {
			if (text !== '') {
				return true;
			}
		}
	}
	
	return false;
}

/**
 * Convert a string to lowercase
 *
 * @param text
 * @returns {string}
 */
function strToLower(text) {
	if (isString(text)) {
		text = text.toLowerCase();
	}
	
	return text;
}

/**
 * Convert a string to uppercase
 *
 * @param text
 * @returns {string}
 */
function strToUpper(text) {
	if (isString(text)) {
		text = text.toUpperCase();
	}
	
	return text;
}

/**
 * sleep() version in JS
 * https://stackoverflow.com/a/39914235
 *
 * Usage:
 * await sleep(2000);
 * or
 * sleep(2000).then(() => {
 *     // Do something after the sleep!
 * });
 *
 * @param ms
 * @returns {Promise<unknown>}
 */
function sleep(ms) {
	return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Confirm Simple Action (Links or forms without AJAX)
 * Usage: Add 'confirm-simple-action' in the element class attribute
 *
 * @param clickedEl
 * @param showCancelInfo
 * @param cancelInfoAutoDismiss
 * @returns {boolean}
 */
function confirmSimpleAction(clickedEl, showCancelInfo=true, cancelInfoAutoDismiss=true) {
	if (typeof Swal === 'undefined') {
		return false;
	}
	
	Swal.fire({
		text: langLayout.confirm.message.question,
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: langLayout.confirm.button.yes,
		cancelButtonText: langLayout.confirm.button.no
	}).then((result) => {
		if (result.isConfirmed) {
			
			try {
				if ($(clickedEl).is('a')) {
					let actionUrl = $(clickedEl).attr('href');
					if (actionUrl !== 'undefined') {
						console.log(actionUrl);
						redirect(actionUrl);
					}
				} else {
					let actionForm = $(clickedEl).parents('form:first');
					$(actionForm).submit();
				}
			} catch (e) {
				console.log(e);
			}
			
		} else if (result.dismiss === Swal.DismissReason.cancel) {
			if (showCancelInfo === true) {
				jsAlert(langLayout.confirm.message.cancel, 'info', cancelInfoAutoDismiss);
			}
		}
	});
	
	return false;
}

/**
 * Show JS Alert Messages (Swal)
 *
 * @param message
 * @param type
 * @param cancelAlertAutoDismiss
 * @param reloadPageIfConfirmed
 */
function jsAlert(message, type='info', cancelAlertAutoDismiss=true, reloadPageIfConfirmed=false) {
	if (typeof Swal === 'undefined') {
		return false;
	}
	
	let alertParams = {
		html: message,
		icon: type,
		position: 'center'
	};
	
	if (cancelAlertAutoDismiss === true) {
		alertParams.showCancelButton = false;
		alertParams.showConfirmButton = false;
		alertParams.timer = 3000;
	} else {
		alertParams.showCancelButton = true;
		if (reloadPageIfConfirmed === true) {
			alertParams.confirmButtonText = refreshBtnText;
		} else {
			alertParams.confirmButtonText = langLayout.confirm.button.ok;
			alertParams.cancelButtonText = langLayout.confirm.button.cancel;
		}
	}
	
	let alertObj = Swal.fire(alertParams);
	
	if (reloadPageIfConfirmed === true) {
		alertObj.then((result) => {
			if (result.isConfirmed) {
				/* Reload Page */
				/* JS 1.1 - Does not create a history entry */
				window.location.replace(window.location.pathname + window.location.search + window.location.hash);
				
				/* JS 1.0 - Creates a history entry */
				window.location.href = window.location.pathname + window.location.search + window.location.hash;
			}
		});
	}
}

/**
 * Show JS Alert Messages (PNotify)
 *
 * @param message
 * @param type
 * @param icon
 * @returns {boolean}
 */
function pnAlert(message, type='notice', icon=null) {
	if (typeof PNotify === 'undefined') {
		return false;
	}
	
	let alertParams = {
		text: message,
		type: type
	};
	
	if (icon !== null) {
		alertParams.icon = icon;
	}
	
	new PNotify.alert(alertParams);
}

/**
 * Get jQuery AJAX Error Message
 *
 * @param xhr
 * @returns {null|*}
 */
function getJqueryAjaxError(xhr) {
	/* Show an alert with the result */
	/* console.log(xhr); */
	
	let message;
	
	if (typeof xhr.responseText !== 'undefined') {
		message = xhr.responseText;
	}
	
	if (typeof xhr.responseJSON !== 'undefined' && typeof xhr.responseJSON.message !== 'undefined') {
		message = xhr.responseJSON.message;
	}
	
	if (typeof message === 'undefined') {
		return null;
	}
	
	return message;
}

/**
 * Check user is on demo domain
 * @returns {boolean}
 */
function isDemoDomain() {
	try {
		if (demoMode) {
			jsAlert(demoMessage, 'error');
			
			return true;
		}
	} catch (e) {
		jsAlert(e, 'error', false);
		
		return true;
	}
	
	return false;
}
