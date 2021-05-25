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
		slug,
		type,
		visibility,
	} = props;

	return html`
		<table className="pixelgradelt_records-package widefat">
			<thead>
				<tr>
					<th colSpan="2">${ composer.name } ${ 'public' !== visibility ? '(' + visibility[0].toUpperCase() + visibility.slice(1) + ')' : '' }</th>
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
						<${ PackageAuthors } authors=${ authors } ...${ props } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Releases', 'pixelgradelt_records' ) }</th>
					<td className="pixelgradelt_records-releases">
						<${ Releases } releases=${ releases } ...${ props } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Required Packages', 'pixelgradelt_records' ) }</th>
					<td className="pixelgradelt_records-required-packages">
						<${ PackageRequiredPackages } requiredPackages=${ requiredPackages } ...${ props } />
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
