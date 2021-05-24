import { components, element, html } from '../utils/index.js';

const { Button } = components;
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
	} = props;

	const authorButtons = authors.map( ( author, index ) => {

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
			${ authorButtons }
		</${ Fragment }
	`;
}

export default PackageAuthors;
