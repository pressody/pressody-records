import { data, dataControls } from '../utils/index.js';

const { dispatch, registerStore, select } = data;
const { apiFetch, controls } = dataControls;

const STORE_KEY = 'pixelgradelt_records/packages';

const DEFAULT_STATE = {
	packages: [],
	postId: null,
};

const packageExists = ( slug, type ) => {
	const packages = select( STORE_KEY ).getPackages();

	return !! packages.filter( item => slug === item.slug && type === item.type ).length;
};

const compareByName = ( a, b ) => {
	if ( a.name < b.name ) {
		return -1;
	}

	if ( a.name > b.name ) {
		return 1;
	}

	return 0;
};

function setPackages( packages ) {
	return {
		type: 'SET_PACKAGES',
		packages: packages.sort( compareByName )
	};
}

function setPostId( postId ) {
	return {
		type: 'SET_POST_ID',
		postId: postId,
	};
}

function* getPackages() {
	const postId = select( STORE_KEY ).getPostId();
	const packages = yield apiFetch( { path: `/pixelgradelt_records/v1/packages?post=${ postId }` } );
	dispatch( STORE_KEY ).setPackages( packages.sort( compareByName ) );
}

const store = {
	reducer( state = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'SET_PACKAGES' :
				return {
					...state,
					packages: action.packages,
				};

			case 'SET_POST_ID' :
				return {
					...state,
					postId: action.postId,
				};
		}

		return state;
	},
	actions: {
		setPackages,
		setPostId,
	},
	selectors: {
		getPackages( state ) {
			return state.packages || [];
		},
		getPostId( state ) {
			return state.postId || null;
		},
	},
	resolvers: {
		getPackages,
	},
	controls,
};

registerStore( STORE_KEY, store );