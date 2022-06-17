import { components, html, i18n } from '../utils/index.js';
import PackageTable from './package-table.js';

const { Placeholder } = components;
const { __ } = i18n;

function PackagePlaceholder( props ) {
	return html`
		<${ Placeholder }
			label=${ __( 'No package details', 'pressody_records' ) }
			instructions=${ __( 'Probably you need to do some configuring first. Go on.. don\'t be shy..', 'pressody_records' ) }
		>
		</${ Placeholder }>
	`;
}

function PackagePreview( props ) {
	if ( ! props.packages.length ) {
		return html`
			<${ PackagePlaceholder } />
		`;
	}

	return props.packages.map( ( item, index ) =>
		html`<${ PackageTable } key=${ item.name } ...${ item } />`
	);
}

export default PackagePreview;
