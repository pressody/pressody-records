import { html, i18n } from '../utils/index.js';
import Releases from './releases.js';
import PackageRequiredPackages from './package-required-packages.js';
import PackageAuthors from './package-authors.js';
import PackageKeywords from './package-keywords.js';

const { __ } = i18n;

function PackageTable( props ) {
	const {
		authors,
		composer,
		description,
		name,
		homepage,
		keywords,
		releases,
		requiredPackages,
		replacedPackages,
		slug,
		type,
		visibility,
		editLink,
		ltType,
	} = props;

	let className = 'pressody_records-package widefat ' + 'pd-type__' + ltType;

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
					<th>${ __( 'Homepage', 'pressody_records' ) }</th>
					<td><a href="${ homepage }" target="_blank" rel="noopener noreferer">${ homepage }</a></td>
				</tr>
				<tr>
					<th>${ __( 'Authors', 'pressody_records' ) }</th>
					<td className="package-authors__list">
						<${ PackageAuthors } authors=${ authors } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Releases', 'pressody_records' ) }</th>
					<td className="pressody_records-releases">
						<${ Releases } releases=${ releases } name=${ name } composer=${ composer } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Required Packages', 'pressody_records' ) }</th>
					<td className="pressody_records-required-packages">
						<${ PackageRequiredPackages } requiredPackages=${ requiredPackages } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Replaced Packages', 'pressody_records' ) }</th>
					<td className="pressody_records-required-packages pressody_records-replaced-packages">
						<${ PackageRequiredPackages } requiredPackages=${ replacedPackages } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Keywords', 'pressody_records' ) }</th>
					<td className="package-keywords__list">
						<${ PackageKeywords } keywords=${ keywords } />
					</td>
				</tr>
				<tr>
					<th>${ __( 'Package Type', 'pressody_records' ) }</th>
					<td><code>${ composer.type }</code></td>
				</tr>
			</tbody>
		</table>
	`;
};

export default PackageTable;
