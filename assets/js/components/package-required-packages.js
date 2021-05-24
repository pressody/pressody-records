import { components, element, html } from '../utils/index.js';

const { Button } = components;
const { Fragment } = element;

function PackageRequiredPackages(props ) {
	const {
		author,
		composer,
		description,
		name,
		homepage,
		releases,
		requiredPackages,
		type,
	} = props;

	const requiredButtons = requiredPackages.map( ( requiredPackage, index ) => {

		let className = 'button pixelgradelt_records-required-package';

		return html`
			<${ Button }
				key=${ requiredPackage.displayName }
				className=${ className }
				href=${ requiredPackage.editLink }
			>
				${ requiredPackage.displayName }
			</${ Button }>
			${ ' ' }
		`;
	} );

	return html`
		<${ Fragment }>
			${ requiredButtons }
		</${ Fragment }
	`;
}

export default PackageRequiredPackages;
