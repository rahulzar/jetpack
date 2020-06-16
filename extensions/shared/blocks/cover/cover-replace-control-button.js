
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
import { useBlockEditContext } from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CoverMediaContext } from './components';
import { isUpgradable, isVideoFile } from "./utils";

export default createHigherOrderComponent( MediaReplaceFlow => props => {
	const { name } = useBlockEditContext();
	const preUploadFile = useRef();
	if ( ! isUpgradable( name ) ) {
		return <MediaReplaceFlow { ...props } />;
	}

	const { createNotice } = props;
	return (
		<CoverMediaContext.Consumer>
			{ ( { onFilesUpload } ) => (
				<MediaReplaceFlow
					{ ...props }
					onFilesUpload={ ( files ) => {
						preUploadFile.current = files?.length ? files[ 0 ] : null;
						onFilesUpload( files );
					} }
					createNotice={ ( status, msg, options ) => {
						// Detect video file from callback and reference instance.
						if ( isVideoFile( preUploadFile.current ) ) {
							return null;
						}

						// Try to pick file type from notice message.
						// Unstable. Not reliable. Fallback.
						if ( isVideoFile( msg.split( ':' )[ 0 ] ) ) {
							return null;
						}
						createNotice( status, msg, options );
					} }
				/>
			) }
		</CoverMediaContext.Consumer>
	);
}, 'JetpackCoverMediaReplaceFlow' );
