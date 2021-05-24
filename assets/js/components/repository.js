import { components, html, i18n } from '../utils/index.js';
import PackageTable from './package-table.js';

const { Button, Placeholder } = components;
const { __ } = i18n;

function RepositoryPlaceholder( props ) {
	return html`
		<${ Placeholder }
			label=${ __( 'Add Packages', 'pixelgradelt_records' ) }
			instructions=${ __( 'Plugins and themes need to be configured as Pixelgrade LT packages to make them available as Composer packages. Packages in your repository will be available for you to install/deploy with Composer.', 'pixelgradelt_records' ) }
		>
			<${ Button }
				isPrimary
				href= ${ props.addNewPackageUrl }
			>
				${ __( 'Add Package', 'pixelgradelt_records' ) }
			</${ Button }>
		</${ Placeholder }>
	`;
}

function Repository( props ) {
	if ( ! props.packages.length ) {
		return html`
			<${ RepositoryPlaceholder } addNewPackageUrl=${ props.addNewPackageUrl } />
		`;
	}

	return props.packages.map( ( item, index ) =>
		html`<${ PackageTable } key=${ item.slug } ...${ item } />`
	);
}

export default Repository;
