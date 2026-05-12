/**
 * ASW Register – Admin JS.
 *
 * Handles:
 *  - Tab switching on the form edit page.
 *  - Dynamic API header rows.
 *  - Shortcode copy-to-clipboard.
 *  - Delete confirmation.
 */
( function ( $ ) {
	'use strict';

	/* ── Tabs ──────────────────────────────────────────────── */

	function initTabs() {
		var $nav = $( '.asw-reg-tabs' );
		if ( ! $nav.length ) return;

		var $btns   = $nav.find( '.asw-reg-tab-btn' );
		var $panels = $( '.asw-reg-tab-panel' );

		function activateTab( tabId ) {
			$btns.removeClass( 'is-active' ).filter( '[data-tab="' + tabId + '"]' ).addClass( 'is-active' );
			$panels.removeClass( 'is-active' ).filter( '#asw-reg-tab-' + tabId ).addClass( 'is-active' );
			// Persist to sessionStorage so refresh keeps the active tab.
			try { sessionStorage.setItem( 'asw_reg_active_tab', tabId ); } catch (e) {}
			if ( tabId === 'general' ) {
				setTimeout( initInjectSelect2, 10 );
			}
		}

		$btns.on( 'click', function () {
			activateTab( $( this ).data( 'tab' ) );
		} );

		// Restore on load.
		var saved = '';
		try { saved = sessionStorage.getItem( 'asw_reg_active_tab' ) || ''; } catch (e) {}
		var initial = ( saved && $nav.find( '[data-tab="' + saved + '"]' ).length ) ? saved : $btns.first().data( 'tab' );
		activateTab( initial );
	}

	/* ── Inject page select (Select2, searchable) ───────────── */

	function initInjectSelect2() {
		var $el = $( '#inject_post_id.asw-reg-inject-select' );
		if ( ! $el.length || typeof $.fn.select2 !== 'function' ) {
			return;
		}
		if ( ! $( '#asw-reg-tab-general' ).hasClass( 'is-active' ) ) {
			return;
		}
		if ( $el.data( 'select2' ) ) {
			return;
		}
		$el.select2( {
			width: '36em',
			dropdownParent: $( 'body' ),
			minimumResultsForSearch: 0
		} );
	}

	/* ── API Headers ───────────────────────────────────────── */

	function initApiHeaders() {
		var $container = $( '#asw-reg-api-headers' );
		if ( ! $container.length ) return;

		// Template row.
		var rowHtml = [
			'<div class="asw-reg-header-row">',
			'<input type="text" name="api_header_key[]"   placeholder="Header name" class="regular-text">',
			'<input type="text" name="api_header_value[]" placeholder="Value" class="regular-text">',
			'<button type="button" class="button asw-reg-remove-header">&times;</button>',
			'</div>',
		].join( '' );

		$( '#asw-reg-add-header' ).on( 'click', function () {
			$container.append( rowHtml );
		} );

		$container.on( 'click', '.asw-reg-remove-header', function () {
			// Keep at least one row.
			if ( $container.find( '.asw-reg-header-row' ).length > 1 ) {
				$( this ).closest( '.asw-reg-header-row' ).remove();
			} else {
				$( this ).closest( '.asw-reg-header-row' ).find( 'input' ).val( '' );
			}
		} );
	}

	/* ── Shortcode copy ────────────────────────────────────── */

	function initShortcodeCopy() {
		$( document ).on( 'click', '.asw-reg-shortcode', function () {
			var text = $( this ).text();
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( text ).then( function () {
					showCopyToast();
				} );
			} else {
				// Fallback.
				var el = document.createElement( 'textarea' );
				el.value = text;
				document.body.appendChild( el );
				el.select();
				document.execCommand( 'copy' );
				document.body.removeChild( el );
				showCopyToast();
			}
		} );
	}

	function showCopyToast() {
		var $toast = $( '<div id="asw-reg-toast">Copied!</div>' ).css( {
			position:     'fixed',
			bottom:       '2rem',
			right:        '2rem',
			background:   '#2271b1',
			color:        '#fff',
			padding:      '.5rem 1rem',
			borderRadius: '4px',
			zIndex:       99999,
			fontSize:     '14px',
		} );
		$( 'body' ).append( $toast );
		setTimeout( function () { $toast.remove(); }, 1500 );
	}

	/* ── Delete confirmation ───────────────────────────────── */

	function initDeleteConfirm() {
		$( document ).on( 'click', '.asw-reg-confirm-delete', function ( e ) {
			if ( ! window.confirm( 'Are you sure you want to delete this item? This cannot be undone.' ) ) {
				e.preventDefault();
			}
		} );
	}

	/* ── Custom Fields (sortable, add/remove, auto-key, type toggle) ── */

	function initCustomFields() {
		var $list = $( '#asw-reg-fields-list' );
		if ( ! $list.length ) return;

		var $addBtn    = $( '#asw-reg-add-field' );
		var $orderInput = $( '#asw-reg-field-order' );
		var nextIndex  = parseInt( $addBtn.data( 'next-index' ) || 0, 10 );

		// ── Sortable ──
		$list.sortable( {
			handle:      '.asw-reg-drag-handle',
			axis:        'y',
			placeholder: 'asw-reg-sort-placeholder',
			forcePlaceholderSize: true,
		} );

		// ── Populate field_order on form submit ──
		$list.closest( 'form' ).on( 'submit', function () {
			var order = [];
			$list.find( 'tr' ).each( function () {
				var $tr = $( this );
				if ( $tr.data( 'field-type' ) === 'core' ) {
					order.push( $tr.data( 'field-key' ) );
				} else {
					var key = $tr.find( '.asw-reg-cf-key' ).val().trim();
					if ( key ) {
						order.push( key );
					}
				}
			} );
			$orderInput.val( order.join( ',' ) );
		} );

		// ── Add custom field row ──
		$addBtn.on( 'click', function () {
			var $tmpl = $( '#asw-reg-field-template' );
			var $row  = $tmpl.clone();

			$row.removeAttr( 'id' );
			// Replace __IDX__ placeholder with real index.
			$row.find( '[name]' ).each( function () {
				var name = $( this ).attr( 'name' ).replace( /__IDX__/g, nextIndex );
				$( this ).attr( 'name', name );
			} );
			// Clear values in the cloned row.
			$row.find( 'input[type="text"], input[type="email"]' ).val( '' );
			$row.find( 'input[type="checkbox"]' ).prop( 'checked', false );
			$row.find( 'textarea' ).val( '' );
			$row.find( 'select' ).prop( 'selectedIndex', 0 );
			$row.find( '.asw-reg-cf-options-cell' ).hide();

			$row.show();
			$list.append( $row );
			nextIndex++;
			$addBtn.data( 'next-index', nextIndex );
		} );

		// ── Remove custom field row ──
		$list.on( 'click', '.asw-reg-remove-field', function () {
			$( this ).closest( 'tr' ).remove();
		} );

		// ── Auto-key: slugify label into key field (new rows only) ──
		$list.on( 'input', '.asw-reg-cf-label', function () {
			var $label = $( this );
			var $row   = $label.closest( 'tr' );
			var $key   = $row.find( '.asw-reg-cf-key' );
			if ( $key.val() === '' ) {
				var slug = $label.val()
					.toLowerCase()
					.replace( /[^a-z0-9]+/g, '_' )
					.replace( /^_+|_+$/g, '' );
				$key.val( slug );
			}
		} );

		// ── Type toggle: show/hide options textarea ──
		$list.on( 'change', '.asw-reg-cf-type', function () {
			var type    = $( this ).val();
			var $row    = $( this ).closest( 'tr' );
			var $optCell = $row.find( '.asw-reg-cf-options-cell' );
			if ( type === 'select' || type === 'radio' || type === 'checkbox' ) {
				$optCell.show();
			} else {
				$optCell.hide();
			}
		} );
	}

	/* ── Boot ──────────────────────────────────────────────── */

	$( function () {
		initTabs();
		initApiHeaders();
		initShortcodeCopy();
		initDeleteConfirm();
		initCustomFields();
	} );

} )( jQuery );
