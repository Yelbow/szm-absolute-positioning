( function ( wp, settings ) {
	if ( ! wp || ! settings ) {
		return;
	}

	var addFilter = wp.hooks.addFilter;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var createElement            = wp.element.createElement;
	var InspectorControls        = wp.blockEditor.InspectorControls;
	var PanelBody                = wp.components.PanelBody;
	var ToggleControl            = wp.components.ToggleControl;
	var RangeControl             = wp.components.RangeControl;
	var SelectControl            = wp.components.SelectControl;
	var __                       = wp.i18n.__;

	var SUPPORTED = settings.blocks || [];

	function isSupported( name ) {
		return SUPPORTED.indexOf( name ) !== -1;
	}

	// Bouwt dezelfde inline style-string als de PHP-kant (render_block), zodat
	// de editor-preview zo dicht mogelijk bij de frontend ligt.
	function buildStyle( attrs ) {
		var parts = [ 'position:absolute' ];
		var anchor = attrs.szmPosXAnchor || 'left';
		var x = attrs.szmPosX;
		var y = attrs.szmPosY;
		var w = attrs.szmPosWidth;
		var z = attrs.szmPosZ;

		function pct( v ) {
			if ( v === undefined || v === null || v === '' ) {
				return null;
			}
			v = Math.max( 0, Math.min( 100, Number( v ) ) );
			return v + '%';
		}

		if ( x !== undefined && x !== null && x !== '' ) {
			if ( anchor === 'center' ) {
				parts.push( 'left:50%;transform:translateX(-50%)' );
			} else if ( anchor === 'right' ) {
				parts.push( 'right:' + pct( x ) );
			} else {
				parts.push( 'left:' + pct( x ) );
			}
		}
		if ( y !== undefined && y !== null && y !== '' ) {
			parts.push( 'top:' + pct( y ) );
		}
		if ( w !== undefined && w !== null && w !== '' ) {
			parts.push( 'width:' + pct( w ) );
		}
		if ( z !== undefined && z !== null && z !== '' && Number( z ) > 0 ) {
			parts.push( 'z-index:' + Number( z ) );
		}
		return parts.join( ';' );
	}

	// Live editor-preview: de class + inline style op het blok zelf zetten.
	var withPositionWrapper = createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {
			var name  = props.name;
			var attrs = props.attributes || {};

			if ( ! isSupported( name ) || ! attrs.szmPos ) {
				return createElement( BlockListBlock, props );
			}

			var style = buildStyle( attrs );
			var newProps = Object.assign( {}, props, {
				className: ( props.className || '' ) + ' szm-pos-absolute',
				style: Object.assign( {}, props.style || {}, style ? { position: 'absolute' } : {} ),
			} );

			// De percentages en z-index zetten we via inline style op het
			// daadwerkelijke blokelement (niet de wrapper), zodat de preview de
			// frontend benadert. De wrapper heeft al onze class.
			return createElement( BlockListBlock, newProps );
		};
	}, 'WithPositionPreview' );

	addFilter( 'editor.BlockListBlock', 'szm/absolute-positioning/preview', withPositionWrapper );

	// Inspector-paneel.
	function addAbsolutePositioningControls( BlockEdit ) {
		return function ( props ) {
			var name  = props.name;
			var attrs = props.attributes || {};

			if ( ! isSupported( name ) ) {
				return createElement( BlockEdit, props );
			}

			var set = function ( key, value ) {
				props.setAttributes( ( function () {
					var o = {};
					o[ key ] = value;
					return o;
				} )() );
			};

			return createElement(
				Fragment !== undefined ? Fragment : wp.element.Fragment,
				null,
				createElement( BlockEdit, props ),
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{ title: __( 'Absoluut positioneren', 'szm-absolute-positioning' ), initialOpen: false },
						createElement( ToggleControl, {
							label: __( 'Zet dit blok op een absolute positie', 'szm-absolute-positioning' ),
							help: __( 'Positioneert het blok binnen zijn directe ouder. Gebruik percentages zodat het responsive blijft.', 'szm-absolute-positioning' ),
							checked: !! attrs.szmPos,
							onChange: function ( v ) { set( 'szmPos', v ); },
						} ),
						attrs.szmPos
							? createElement( wp.element.Fragment, null,
								createElement( SelectControl, {
									label: __( 'Horizontaal anker', 'szm-absolute-positioning' ),
									value: attrs.szmPosXAnchor || 'left',
									options: [
										{ value: 'left',   label: __( 'Links', 'szm-absolute-positioning' ) },
										{ value: 'center', label: __( 'Midden', 'szm-absolute-positioning' ) },
										{ value: 'right',  label: __( 'Rechts', 'szm-absolute-positioning' ) },
									],
									onChange: function ( v ) { set( 'szmPosXAnchor', v ); },
								} ),
								createElement( RangeControl, {
									label: __( 'X positie (%)', 'szm-absolute-positioning' ),
									min: 0, max: 100,
									value: attrs.szmPosX ? Number( attrs.szmPosX ) : 0,
									onChange: function ( v ) { set( 'szmPosX', String( v ) ); },
								} ),
								createElement( RangeControl, {
									label: __( 'Y positie (%)', 'szm-absolute-positioning' ),
									min: 0, max: 100,
									value: attrs.szmPosY ? Number( attrs.szmPosY ) : 0,
									onChange: function ( v ) { set( 'szmPosY', String( v ) ); },
								} ),
								createElement( RangeControl, {
									label: __( 'Breedte (%)', 'szm-absolute-positioning' ),
									help: __( 'Laat leeg/standaard voor automatische breedte.', 'szm-absolute-positioning' ),
									min: 5, max: 100,
									value: attrs.szmPosWidth ? Number( attrs.szmPosWidth ) : 50,
									onChange: function ( v ) { set( 'szmPosWidth', String( v ) ); },
								} ),
								createElement( RangeControl, {
									label: __( 'Z-index', 'szm-absolute-positioning' ),
									min: 0, max: 50,
									value: attrs.szmPosZ ? Number( attrs.szmPosZ ) : 1,
									onChange: function ( v ) { set( 'szmPosZ', String( v ) ); },
								} )
							)
							: null
					)
				)
			);
		};
	}

	addFilter( 'editor.BlockEdit', 'szm/absolute-positioning/controls', addAbsolutePositioningControls );
} )( window.wp, window.szmAbsolutePositioning );