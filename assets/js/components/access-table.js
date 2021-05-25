import { components, html, i18n } from '../utils/index.js';
import ApiKeyForm from './api-key-form.js';
import { moreVertical } from './icons.js';

const { DropdownMenu, TextControl } = components;
const { __ } = i18n;

const selectField = ( e ) => e.nativeEvent.target.select();

function AccessTable( props ) {
	const {
		apiKeys,
		onCreateApiKey,
		onRevokeApiKey,
	} = props;

	let body = html`<tr><td colSpan=6>${ __( 'Add an API Key to access the PixelgradeLT Records repository.', 'pixelgradelt_records' ) }</td></tr>`;

	if ( apiKeys.length ) {
		body = apiKeys.map( ( item, index ) => {
			return html`
				<${ AccessTableRow }
					key=${ item.token }
					onRevokeApiKey=${ onRevokeApiKey }
					...${ item }
				/>
			`;
		} );
	}

	return html`
		<table className="pixelgradelt_records-api-key-table widefat">
			<thead>
				<tr>
					<th>${ __( 'Name', 'pixelgradelt_records' ) }</th>
					<th className="column-user">${ __( 'User', 'pixelgradelt_records' ) }</th>
					<th>${ __( 'API Key', 'pixelgradelt_records' ) }</th>
					<th>${ __( 'Last Used', 'pixelgradelt_records' ) }</th>
					<th>${ __( 'Created', 'pixelgradelt_records' ) }</th>
					<th></th>

				</tr>
			</thead>
			<tbody>${ body }</tbody>
			<tfoot>
				<tr>
					<td colSpan="6" className="pixelgradelt_records-api-key-form">
						<${ ApiKeyForm }
							onSubmit=${ onCreateApiKey }
						/>
					</td>
				</tr>
			</tfoot>
		</table>
	`;
};

function AccessTableRow( props ) {
	const {
		created,
		last_used,
		name,
		token,
		user,
		user_login,
		onRevokeApiKey,
	} = props;

	return html `
		<tr key="${ token }">
			<th scope="row">${ name }</th>
			<td className="column-user">${ user_login }</td>
			<td className="column-token">
				<${ TextControl }
					className="regular-text"
					value=${ token }
					readOnly
					onClick=${ selectField }
				/>
			</td>
			<td className="column-last-used">${ last_used || '—' }</td>
			<td className="column-created">${ created }</td>
			<td className="column-actions">
				<${ DropdownMenu }
					label=${ __( 'Toggle dropdown', 'pixelgradelt_records' ) }
					icon=${ moreVertical }
					controls=${ [
						{
							title: __( 'Revoke', 'pixelgradelt_records' ),
							onClick: () => { onRevokeApiKey( token, user ) }
						}
					] }
				/>
			</td>
		</tr>
	`;
};

export default AccessTable;
