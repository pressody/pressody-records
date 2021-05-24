import { components, html, i18n } from '../utils/index.js';

const { Button, TextControl } = components;
const { __, sprintf } = i18n;

const selectField = ( e ) => e.nativeEvent.target.select();

function  ReleaseActions( props ) {
	const {
		composerName,
		name,
		url,
		version,
	} = props;

	const requireValue = `"${ composerName }": "${ version }"`;
	const cliCommandValue = `composer require ${ composerName }:${ version }`;

	/* translators: %s: version number */
	const buttonText = __( 'Download %s', 'pixelgradelt_records' );

	/* translators: %s: <code>composer.json</code> */
	const copyPasteText = __( 'Copy and paste into %s', 'pixelgradelt_records' );

	return html`
		<div className="pixelgradelt_records-release-actions">
			<table>
				<tbody>
					<tr>
						<th scope="row">
							<label htmlFor="pixelgradelt_records-release-action-download-url-${ composerName }">${ __( 'Download URL', 'pixelgradelt_records' ) }</label>
						</th>
						<td>
							<${ TextControl }
								value=${ url }
								readOnly="readonly"
								id="pixelgradelt_records-release-action-download-url-${ composerName }"
								onClick=${ selectField }
							/>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label htmlFor="pixelgradelt_records-release-action-require-${ composerName }">${ __( 'Require', 'pixelgradelt_records' ) }</label>
						</th>
						<td>
							<${ TextControl }
								value=${ requireValue }
								readOnly="readonly"
								id="pixelgradelt_records-release-action-require-${ composerName }"
								onClick=${ selectField }
							/>
							<span className="description">
								<em>${ sprintf( copyPasteText, '<code>composer.json</code>' ) }</em>
							</span>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label htmlFor="pixelgradelt_records-release-action-cli-${ composerName }">${ __( 'CLI Command', 'pixelgradelt_records' ) }</label>
						</th>
						<td>
							<${ TextControl }
								value=${ cliCommandValue }
								readOnly="readonly"
								id="pixelgradelt_records-release-action-cli-${ composerName }"
								onClick=${ selectField }
							/>
						</td>
					</tr>
					<tr>
						<td colSpan="2">
							<${ Button }
								href=${ url }
								isPrimary
							>
								${ sprintf( buttonText, version ) }
							</${ Button }>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	`;
}

export default ReleaseActions;
