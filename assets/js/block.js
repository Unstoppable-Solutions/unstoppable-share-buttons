/**
 * Unstoppable Share Buttons - editor block.
 */
( function ( blocks, element, blockEditor, components, i18n, serverSideRender ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var data = window.ushbBlockData || { networks: [], defaults: {} };
	var InspectorControls = blockEditor.InspectorControls;
	var ServerSideRender = serverSideRender;

	function optionList( pairs ) {
		return pairs.map( function ( pair ) {
			return { value: pair[ 0 ], label: pair[ 1 ] };
		} );
	}

	blocks.registerBlockType( 'unstoppable/share-buttons', {
		edit: function ( props ) {
			var attributes = props.attributes;

			function update( key ) {
				return function ( value ) {
					var next = {};
					next[ key ] = value;
					props.setAttributes( next );
				};
			}

			var selected = attributes.networks
				? attributes.networks.split( ',' ).filter( Boolean )
				: ( data.defaults.networks || '' ).split( ',' ).filter( Boolean );

			var checkboxes = ( data.networks || [] ).map( function ( network ) {
				return el( components.CheckboxControl, {
					key: network.value,
					label: network.label,
					checked: selected.indexOf( network.value ) !== -1,
					onChange: function ( checked ) {
						var next = selected.slice();

						if ( checked ) {
							if ( next.indexOf( network.value ) === -1 ) {
								next.push( network.value );
							}
						} else {
							next = next.filter( function ( item ) {
								return item !== network.value;
							} );
						}

						props.setAttributes( { networks: next.join( ',' ) } );
					}
				} );
			} );

			return el(
				element.Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						components.PanelBody,
						{ title: __( 'Content', 'unstoppable-share-buttons' ), initialOpen: true },
						el( components.TextControl, {
							label: __( 'Heading', 'unstoppable-share-buttons' ),
							value: attributes.heading,
							onChange: update( 'heading' )
						} ),
						el( components.SelectControl, {
							label: __( 'Button content', 'unstoppable-share-buttons' ),
							value: attributes.labels || data.defaults.labels,
							options: optionList( [
								[ 'icon', __( 'Icon only', 'unstoppable-share-buttons' ) ],
								[ 'label', __( 'Name only', 'unstoppable-share-buttons' ) ],
								[ 'both', __( 'Icon and name', 'unstoppable-share-buttons' ) ]
							] ),
							onChange: update( 'labels' )
						} ),
						el( components.ToggleControl, {
							label: __( 'Show share counts', 'unstoppable-share-buttons' ),
							checked: !! attributes.counts,
							onChange: update( 'counts' )
						} )
					),
					el(
						components.PanelBody,
						{ title: __( 'Style', 'unstoppable-share-buttons' ), initialOpen: false },
						el( components.SelectControl, {
							label: __( 'Shape', 'unstoppable-share-buttons' ),
							value: attributes.shape || data.defaults.shape,
							options: optionList( [
								[ 'square', __( 'Square', 'unstoppable-share-buttons' ) ],
								[ 'rounded', __( 'Rounded', 'unstoppable-share-buttons' ) ],
								[ 'pill', __( 'Pill', 'unstoppable-share-buttons' ) ],
								[ 'circle', __( 'Circle', 'unstoppable-share-buttons' ) ],
								[ 'ribbon', __( 'Ribbon', 'unstoppable-share-buttons' ) ],
								[ 'drop', __( 'Drop', 'unstoppable-share-buttons' ) ]
							] ),
							onChange: update( 'shape' )
						} ),
						el( components.SelectControl, {
							label: __( 'Fill', 'unstoppable-share-buttons' ),
							value: attributes.style || data.defaults.style,
							options: optionList( [
								[ 'filled', __( 'Filled', 'unstoppable-share-buttons' ) ],
								[ 'outline', __( 'Outline', 'unstoppable-share-buttons' ) ],
								[ 'minimal', __( 'Minimal', 'unstoppable-share-buttons' ) ],
								[ 'dashed', __( 'Dashed', 'unstoppable-share-buttons' ) ],
								[ 'glass', __( 'Glass', 'unstoppable-share-buttons' ) ],
								[ 'gradient', __( 'Gradient', 'unstoppable-share-buttons' ) ]
							] ),
							onChange: update( 'style' )
						} ),
						el( components.SelectControl, {
							label: __( 'Alignment', 'unstoppable-share-buttons' ),
							value: attributes.align || data.defaults.align,
							options: optionList( [
								[ 'left', __( 'Left', 'unstoppable-share-buttons' ) ],
								[ 'center', __( 'Center', 'unstoppable-share-buttons' ) ],
								[ 'right', __( 'Right', 'unstoppable-share-buttons' ) ]
							] ),
							onChange: update( 'align' )
						} )
					),
					el(
						components.PanelBody,
						{ title: __( 'Networks', 'unstoppable-share-buttons' ), initialOpen: false },
						checkboxes
					)
				),
				el( ServerSideRender, {
					block: 'unstoppable/share-buttons',
					attributes: attributes
				} )
			);
		},
		save: function () {
			// Rendered in PHP so saved posts always reflect current settings.
			return null;
		}
	} );
}(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n,
	window.wp.serverSideRender
) );
