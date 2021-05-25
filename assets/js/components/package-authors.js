import { element, html } from '../utils/index.js';

const { Fragment } = element;

function PackageAuthors( props ) {
	const {
		authors,
		composer,
		description,
		name,
		homepage,
		releases,
		requiredPackages,
		type,
		visibility,
	} = props;

	const authorLinks = authors.map( ( author, index ) => {

		let className = 'package-author';

		return html`
			<a
				key=${ author.name }
				className=${ className }
				href=${ author.homepage ? author.homepage : '#' }
				target="_blank"
				rel="noopener noreferer"
			>
				${ author.name }
			</a>
		`;
	} );

	return html`
		<${ Fragment }>
			${ authorLinks }
		</${ Fragment }
	`;
}

export default PackageAuthors;
