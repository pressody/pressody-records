/**
 * External dependencies.
 */
import { registerFieldType } from '@carbon-fields/core';

/**
 * Internal dependencies.
 */
import './style.scss';
import UploadZipField from './main';

registerFieldType( 'upload_zip', UploadZipField );
