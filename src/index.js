import { createRoot } from '@wordpress/element';
import App from './App';
import './styles/admin.scss';

const root = document.getElementById( 'dmi-admin-app' );

if ( root ) {
	createRoot( root ).render( <App /> );
}