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

$(document).ready(function () {
	
	/* Save the Post */
	$('.make-favorite').click(function () {
		if (isLogged !== true) {
			openLoginModal();
			
			return false;
		}
		
		savePost(this);
	});
	
	/* Save the Search */
	$('#saveSearch').click(function () {
		if (isLogged !== true) {
			openLoginModal();
			
			return false;
		}
		
		saveSearch(this);
	});
	
});

/**
 * Save Ad
 * @param elmt
 * @returns {boolean}
 */
function savePost(elmt) {
	var postId = $(elmt).attr('id');
	
	let url = siteUrl + '/ajax/save/post';
	
	let ajax = $.ajax({
		method: 'POST',
		url: url,
		data: {
			'postId': postId,
			'_token': $('input[name=_token]').val()
		}
	});
	ajax.done(function (xhr) {
		/* console.log(xhr); */
		if (typeof xhr.isLogged === 'undefined') {
			return false;
		}
		
		if (xhr.isLogged !== true) {
			openLoginModal();
			
			return false;
		}
		
		/* Logged Users - Notification */
		if (xhr.status == 1) {
			if ($(elmt).hasClass('btn')) {
				$('#' + xhr.postId).removeClass('btn-default').addClass('btn-success');
			} else {
				$(elmt).html('<i class="fas fa-bookmark" data-bs-toggle="tooltip" title="' + lang.labelSavePostRemove + '"></i>');
			}
			
			jsAlert(lang.confirmationSavePost, 'success');
		} else {
			if ($(elmt).hasClass('btn')) {
				$('#' + xhr.postId).removeClass('btn-success').addClass('btn-default');
			} else {
				$(elmt).html('<i class="far fa-bookmark" data-bs-toggle="tooltip" title="' + lang.labelSavePostSave + '"></i>');
			}
			
			jsAlert(lang.confirmationRemoveSavePost, 'success');
		}
		
		return false;
	});
	ajax.fail(function (xhr, textStatus, errorThrown) {
		let message = getJqueryAjaxError(xhr);
		if (message !== null) {
			jsAlert(message, 'error', false);
		}
	});
	
	return false;
}

/**
 * Save Search
 * @param elmt
 * @returns {boolean}
 */
function saveSearch(elmt) {
	var searchUrl = $(elmt).attr('name');
	var countPosts = $(elmt).attr('count');
	
	let url = siteUrl + '/ajax/save/search';
	
	let ajax = $.ajax({
		method: 'POST',
		url: url,
		data: {
			'url': searchUrl,
			'countPosts': countPosts,
			'_token': $('input[name=_token]').val()
		}
	});
	ajax.done(function (xhr) {
		/* console.log(xhr); */
		if (typeof xhr.isLogged === 'undefined') {
			return false;
		}
		
		if (xhr.isLogged !== true) {
			openLoginModal();
			
			return false;
		}
		
		/* Logged Users - Notification */
		let message = lang.confirmationRemoveSaveSearch;
		if (xhr.status === 1) {
			message = lang.confirmationSaveSearch;
		}
		
		jsAlert(message, 'success');
		
		return false;
	});
	ajax.fail(function (xhr, textStatus, errorThrown) {
		let message = getJqueryAjaxError(xhr);
		if (message !== null) {
			jsAlert(message, 'error', false);
		}
	});
	
	return false;
}
