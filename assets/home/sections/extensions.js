/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Col, Grid } from '../grid';
import Card from '../card';
import Section from '../section';
import { useSelect } from '@wordpress/data';
import { EXTENSIONS_STORE } from '../../extensions/store';
import { Spinner } from '@wordpress/components';

/**
 * Extensions section component.
 */
const Extensions = () => {
	const { extensions, isExtensionsLoading } = useSelect( ( select ) => {
		const store = select( EXTENSIONS_STORE );

		return {
			isExtensionsLoading: ! store.hasFinishedResolution(
				'getExtensions'
			),
			extensions: store
				.getExtensions()
				.filter(
					( extension ) => extension.product_slug !== 'sensei-pro'
				),
		};
	}, [] );

	if ( isExtensionsLoading ) {
		return (
			<div className="sensei-home__loader">
				<Spinner />
			</div>
		);
	}

	if ( 0 === extensions.length ) {
		return null;
	}

	return (
		<Section title={ __( 'Extensions', 'sensei-lms' ) }>
			<Grid>
				{ extensions.map( ( extension ) => (
					<Col
						key={ extension.product_slug }
						as="section"
						className="sensei-extensions__card-wrapper"
						cols={ 4 }
					>
						<Card { ...extension } />
					</Col>
				) ) }
			</Grid>
		</Section>
	);
};

export default Extensions;
