import { element, html, i18n } from '../utils/index.js';

const { Fragment } = element;

const { __ } = i18n;

function PackageKeywords( props ) {
	const {
		keywords,
	} = props;

	return html`
		<${ Fragment }>
			${ keywords.length ? keywords.join(', ') : __( 'None', 'pixelgradelt_records' ) }
		</${ Fragment }
	`;
}

export default PackageKeywords;
