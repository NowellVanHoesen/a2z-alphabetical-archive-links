/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { title, showCounts, selectedPostType } = attributes;
	const availablePostTypes = window.NVWDA2ZAAL;
	const testSettings = useSelect(
		( select ) => select( 'core' ).getSite()
	);
	console.log(testSettings);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'nvwd-a2zaal' ) }>
					<TextControl
						label={ __( 'Title', 'nvwd-a2zaal' ) }
						value={ title || '' }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					{ availablePostTypes !== undefined
						? <SelectControl
							label="Post Type"
							value={ selectedPostType }
							onChange={ ( value ) =>
								setAttributes( 	{ selectedPostType: value } )
							}>
								{ availablePostTypes?.map( ( postType ) => (
									<option key={ postType } value={ postType }>{ postType }</option>
								) ) }
						</SelectControl>
						: <p>There are no active Post Types. Please select a post type in the plugin settings.</p>
					}
					<ToggleControl
						checked={ !! showCounts }
						label={ __( 'Show Counts', 'nvwd-a2zaal' ) }
						onChange={ () =>
							setAttributes( { showCounts: ! showCounts } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				{ title && (
					<h2 className="widget-title">{ __( title, 'nvwd-a2zaal' ) }</h2>
				) }
				{ __( 'A2z Links – hello from the editor!', 'a2z-links' ) }
			</section>
		</>
	);
}
