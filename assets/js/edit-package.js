import { data, element, html } from './utils/index.js';
import PackagePreview from './components/package-preview.js';
import './data/packages.js';

const { useDispatch, useSelect } = data;
const { Fragment, render } = element;

// noinspection JSUnresolvedVariable,JSHint
const { editedPostId } = _pixelgradeltRecordsEditPackageData;

function App( props ) {
	const { postId } = props;

	const {
		setPostId,
	} = useDispatch( 'pixelgradelt_records/packages' );

	setPostId( postId );

	const packages = useSelect( ( select ) => {
		return select( 'pixelgradelt_records/packages' ).getPackages();
	} );

	return html`
		<${ Fragment }>
			<${ PackagePreview }
				packages=${ packages }
				postId=${ postId }
			/>
		</${ Fragment }>
	`;
}

render(
	html`<${ App } postId=${ editedPostId } />`,
	document.getElementById( 'pixelgradelt_records-package-preview' )
);
