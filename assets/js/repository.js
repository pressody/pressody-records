import { data, element, html } from './utils/index.js';
import Repository from './components/repository.js';
import './data/packages.js';

const { useSelect } = data;
const { Fragment, render } = element;

// noinspection JSUnresolvedVariable,JSHint
const { addNewPackageUrl } = _pixelgradeltRecordsRepositoryData;

function App() {
	const packages = useSelect( select => select( 'pixelgradelt_records/packages' ).getPackages() );

	return html`
		<${ Fragment }>
			<${ Repository }
				packages=${ packages }
				addNewPackageUrl=${ addNewPackageUrl }
			/>
		</${ Fragment }>
	`;
}

render(
	html`<${ App } />`,
	document.getElementById( 'pixelgradelt_records-repository-container' )
);
