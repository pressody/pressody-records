import { components, element, html, i18n } from '../utils/index.js';
import ReleaseActions from './release-actions.js';

const { Button } = components;
const { Fragment, useState } = element;

const { __ } = i18n;

const defaultRelease = {
	url: '',
	version: '',
};

function Releases( props ) {
	const {
		composer,
		name,
		releases,
	} = props;

	const [ selectedRelease, setSelectedRelease ] = useState( defaultRelease );

	const clearSelectedRelease = () => setSelectedRelease( defaultRelease );

	const { version: selectedVersion } = selectedRelease;

	const releaseButtons = releases.map( ( release, index ) => {
		const isSelected = selectedVersion === release.version;

		let className = 'button pixelgradelt_records-release';
		if ( isSelected ) {
			className += ' active';
		}

		const onClick = () => {
			if ( selectedVersion === release.version ) {
				clearSelectedRelease();
			} else {
				setSelectedRelease( release );
			}
		};

		return html`
			<${ Button }
				key=${ release.version }
				className=${ className }
				aria-expanded=${ isSelected }
				onClick=${ onClick }
			>
				${ release.version }
			</${ Button }>
			${ ' ' }
		`;
	} );

	const releaseActions = '' !== selectedVersion && html`
		<${ ReleaseActions }
			name=${ name }
			composerName=${ composer.name }
			...${ selectedRelease }
		/>
	`;

	return html`
		<${ Fragment }>
			${ releaseButtons.length ? releaseButtons : __( 'None', 'pixelgradelt_records' ) }
			${ releaseActions }
		</${ Fragment }
	`;
}

export default Releases;
