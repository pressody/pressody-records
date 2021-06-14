import { html, i18n } from '../utils/index.js';
import Releases from './releases.js';
import PackageRequiredPackages from './package-required-packages.js';
import PackageAuthors from './package-authors.js';

const { __ } = i18n;

function PackageTable( props ) {
	const {
		authors,
		composer,
		description,
		name,
		homepage,
		releases,
		requiredPackages,
		replacedPackages,
		slug,
		type,
		visibility,
		editLink,
		ltType,
	} = props;

	let className = 'pixelgradelt_records-package widefat ' + 'lt-type__' + ltType;

	return html`
		<table className="${ className }">
			<thead>
				<tr>
					<th colSpan="2">${ composer.name } ${ 'public' !== visibility ? '(' + visibility[0].toUpperCase() + visibility.slice(1) + ')' : '' } <a className="edit-package" href=${ editLink }>Edit ${ ltType.toLowerCase() }</a></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colSpan="2">${ description }</td>
				</tr>
				<tr>
					<th>${ __( 'Homepage', 'pixelgradelt_records' ) }</th>
					<td><a href="${ homepage }" target="_blank" rel="noopener noreferer">${ homepage }</a></td>
				</tr>
				<tr>
					<th>${ __( 'Authors', 'pixelgradelt_records' ) }</th>
					<td className="package-authors__list">
						<${ PackageAuthors } authors=${ authors } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Releases', 'pixelgradelt_records' ) }</th>
					<td className="pixelgradelt_records-releases">
						<${ Releases } releases=${ releases } name=${ name } composer=${ composer } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Required Packages', 'pixelgradelt_records' ) }</th>
					<td className="pixelgradelt_records-required-packages">
						<${ PackageRequiredPackages } requiredPackages=${ requiredPackages } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Replaced Packages', 'pixelgradelt_records' ) }</th>
					<td className="pixelgradelt_records-required-packages pixelgradelt_records-replaced-packages">
						<${ PackageRequiredPackages } requiredPackages=${ replacedPackages } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Package Type', 'pixelgradelt_records' ) }</th>
					<td><code>${ composer.type }</code></td>
				</tr>
			</tbody>
		</table>
	`;
};

export default PackageTable;
