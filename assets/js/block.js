/**
 * Social Feed — Gutenberg block (editor side).
 *
 * Registered without a build step: it uses the global `wp.*` packages that
 * WordPress already ships, so no bundler/JSX transform is required. The block
 * is server-rendered, so the editor only collects attributes and shows a
 * <ServerSideRender> preview of the real PHP output.
 */
( function ( blocks, element, blockEditor, components, serverSideRender, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var RangeControl = components.RangeControl;
	var TextControl = components.TextControl;
	var ServerSideRender = serverSideRender;

	blocks.registerBlockType( 'social-feed/feed', {
		title: __( 'Social Feed', 'social-feed' ),
		description: __( 'Display the latest posts from a social network.', 'social-feed' ),
		icon: 'share',
		category: 'widgets',
		attributes: {
			network: { type: 'string', default: 'instagram' },
			count: { type: 'number', default: 9 },
			columns: { type: 'number', default: 3 },
			title: { type: 'string', default: '' }
		},

		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var controls = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Feed settings', 'social-feed' ), initialOpen: true },
					el( SelectControl, {
						label: __( 'Network', 'social-feed' ),
						value: attributes.network,
						options: [
							{ label: 'Instagram', value: 'instagram' },
							{ label: 'LinkedIn', value: 'linkedin' }
						],
						onChange: function ( value ) {
							setAttributes( { network: value } );
						}
					} ),
					el( TextControl, {
						label: __( 'Title', 'social-feed' ),
						value: attributes.title,
						onChange: function ( value ) {
							setAttributes( { title: value } );
						}
					} ),
					el( RangeControl, {
						label: __( 'Number of posts', 'social-feed' ),
						value: attributes.count,
						min: 1,
						max: 30,
						onChange: function ( value ) {
							setAttributes( { count: value } );
						}
					} ),
					el( RangeControl, {
						label: __( 'Columns', 'social-feed' ),
						value: attributes.columns,
						min: 1,
						max: 6,
						onChange: function ( value ) {
							setAttributes( { columns: value } );
						}
					} )
				)
			);

			var preview = el( ServerSideRender, {
				block: 'social-feed/feed',
				attributes: attributes
			} );

			return el( 'div', props.blockProps || {}, controls, preview );
		},

		// Server-rendered block: nothing is saved to post content.
		save: function () {
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender,
	window.wp.i18n
);
