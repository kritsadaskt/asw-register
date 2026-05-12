/**
 * ASW Register – Front-end form JS.
 *
 * Responsibilities:
 *  1. Read UTM parameters from URL query string and document.cookie.
 *  2. Populate hidden fields before submission.
 *  3. Submit via fetch() to admin-ajax.php.
 *  4. Show success / error message or redirect.
 */
( function () {
	'use strict';

	/* ── UTM reading helpers ───────────────────────────────── */

	/**
	 * Parse URL search params from the current page URL.
	 *
	 * @returns {URLSearchParams}
	 */
	function getSearchParams() {
		return new URLSearchParams( window.location.search );
	}

	/**
	 * Read a cookie by name.
	 *
	 * @param {string} name
	 * @returns {string}
	 */
	function getCookie( name ) {
		var match = document.cookie.match( new RegExp( '(?:^|;\\s*)' + name.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[1] ) : '';
	}

	/**
	 * Resolve UTM value: URL param > HandL lowercase cookie > HandL_-prefixed cookie.
	 *
	 * @param {string} param Cookie/GET param name (e.g. "utm_source", "gclid").
	 * @returns {string}
	 */
	function resolveUtm( param ) {
		var params = getSearchParams();

		// 1. Current-page URL query string (most specific).
		if ( params.has( param ) ) {
			return params.get( param );
		}

		// 2. HandL v2 free: lowercase cookie.
		var cookieVal = getCookie( param );
		if ( cookieVal ) {
			return cookieVal;
		}

		// 3. HandL Pro: "HandL_"-prefixed cookie.
		var handlVal = getCookie( 'HandL_' + param );
		if ( handlVal ) {
			return handlVal;
		}

		return '';
	}

	/**
	 * UTM field map: POST field name → cookie/GET param name.
	 * Matches ASW_Reg_UTM_Reader::$utm_keys on the server.
	 */
	var UTM_MAP = {
		utm_field_utm_source:         'utm_source',
		utm_field_utm_medium:         'utm_medium',
		utm_field_utm_campaign:       'utm_campaign',
		utm_field_utm_term:           'utm_term',
		utm_field_utm_content:        'utm_content',
		utm_field_gclid:              'gclid',
		utm_field_handl_landing_page: 'handl_landing_page',
		utm_field_handl_original_ref: 'handl_original_ref',
	};

	/**
	 * Populate hidden UTM + page_url fields inside a form element.
	 *
	 * @param {HTMLFormElement} formEl
	 */
	function populateHiddenFields( formEl ) {
		for ( var fieldName in UTM_MAP ) {
			if ( UTM_MAP.hasOwnProperty( fieldName ) ) {
				var input = formEl.querySelector( 'input[name="' + fieldName + '"]' );
				if ( input ) {
					input.value = resolveUtm( UTM_MAP[fieldName] );
				}
			}
		}

		var pageUrlInput = formEl.querySelector( 'input[name="page_url"]' );
		if ( pageUrlInput ) {
			pageUrlInput.value = window.location.href;
		}
	}

	/* ── Form submission ───────────────────────────────────── */

	/**
	 * Show a message inside the form wrap.
	 *
	 * @param {HTMLElement} wrap     The .asw-reg-form-wrap element.
	 * @param {string}      message  HTML message.
	 * @param {string}      type     'success' | 'error'
	 */
	function showMessage( wrap, message, type ) {
		var msgEl = wrap.querySelector( '.asw-reg-messages' );
		if ( ! msgEl ) return;
		var tone = type === 'success'
			? 'aswr:bg-emerald-50 aswr:border-emerald-200 aswr:text-emerald-900'
			: 'aswr:bg-red-50 aswr:border-red-200 aswr:text-red-900';
		msgEl.innerHTML = '<div class="aswr:rounded-xl aswr:border aswr:px-4 aswr:py-3 aswr:text-sm aswr:leading-relaxed ' + tone + '">' + message + '</div>';
		msgEl.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
	}

	/**
	 * Clear messages.
	 *
	 * @param {HTMLElement} wrap
	 */
	function clearMessages( wrap ) {
		var msgEl = wrap.querySelector( '.asw-reg-messages' );
		if ( msgEl ) msgEl.innerHTML = '';
	}

	/**
	 * Set loading state on the submit button.
	 *
	 * @param {HTMLFormElement} formEl
	 * @param {boolean}         loading
	 */
	function setLoading( formEl, loading ) {
		var btn     = formEl.querySelector( '.asw-reg-btn--submit' );
		var spinner = formEl.querySelector( '.asw-reg-spinner' );

		if ( btn ) {
			btn.disabled = loading;
		}
		if ( spinner ) {
			spinner.hidden = ! loading;
			spinner.setAttribute( 'aria-hidden', String( ! loading ) );
		}
	}

	/**
	 * Handle form submit.
	 *
	 * @param {Event} event
	 */
	function handleSubmit( event ) {
		event.preventDefault();

		var formEl   = event.currentTarget;
		var wrap     = formEl.closest( '.asw-reg-form-wrap' );
		var formId   = formEl.dataset.formId;
		var nonce    = formEl.dataset.nonce;
		var ajaxUrl  = ( window.aswRegister && window.aswRegister.ajax_url ) ? window.aswRegister.ajax_url : '/wp-admin/admin-ajax.php';

		clearMessages( wrap );
		populateHiddenFields( formEl );

		// Native HTML5 validation.
		if ( ! formEl.checkValidity() ) {
			formEl.reportValidity();
			return;
		}

		setLoading( formEl, true );

		var formData = new FormData( formEl );
		formData.set( 'action',  'asw_reg_submit' );
		formData.set( 'form_id', formId );
		formData.set( 'nonce',   nonce );

		fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        formData,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				setLoading( formEl, false );

				if ( data.success ) {
					var redirectUrl = data.data && data.data.redirect_url ? data.data.redirect_url : '';

					if ( redirectUrl ) {
						window.location.href = redirectUrl;
						return;
					}

					var message = ( data.data && data.data.message ) ? data.data.message : 'Thank you!';
					showMessage( wrap, message, 'success' );
					formEl.reset();
				} else {
					var errMsg = ( data.data && data.data.message ) ? data.data.message : 'Something went wrong. Please try again.';
					showMessage( wrap, errMsg, 'error' );
				}
			} )
			.catch( function () {
				setLoading( formEl, false );
				showMessage( wrap, 'A network error occurred. Please try again.', 'error' );
			} );
	}

	/* ── Initialise ────────────────────────────────────────── */

	function init() {
		var forms = document.querySelectorAll( '.asw-reg-form' );
		forms.forEach( function ( formEl ) {
			// Pre-populate UTMs immediately so they're captured even before submit.
			populateHiddenFields( formEl );
			formEl.addEventListener( 'submit', handleSubmit );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
