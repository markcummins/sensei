/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useLayoutEffect, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../../shared/query-string-router';
import { H } from '../../shared/components/section';
import BigScreen from './big-screen';
import SmallScreen from './small-screen';

/**
 * Theme step for Setup Wizard.
 */
const Theme = () => {
	const { goTo } = useQueryStringRouter();
	const [ isBigScreen, setIsBigScreen ] = useState( false );
	const responsiveAreaRef = useRef();

	useLayoutEffect( () => {
		const screenSizeCheck = () => {
			setIsBigScreen( window.innerWidth >= 600 );
		};

		// Check screen size on load.
		screenSizeCheck();

		const { defaultView } = responsiveAreaRef.current.ownerDocument;
		defaultView.addEventListener( 'resize', screenSizeCheck );

		return () => {
			defaultView.removeEventListener( 'resize', screenSizeCheck );
		};
	}, [] );

	const goToNextStep = () => {
		goTo( 'tracking' );
	};

	return (
		<>
			<div className="sensei-setup-wizard__content sensei-setup-wizard__content--large">
				<div className="sensei-setup-wizard__title">
					<H className="sensei-setup-wizard__step-title">
						{ __( 'Get new Sensei theme', 'sensei-lms' ) }
					</H>
					<p>
						{ __(
							"The new Sensei theme it's build from ground up with Learning Mode in mind to optimize your full site so that everything works smootly together.",
							'sensei-lms'
						) }
					</p>
				</div>

				<div className="sensei-setup-wizard__actions sensei-setup-wizard__actions--full-width">
					<div className="sensei-setup-wizard__theme-actions">
						<button
							className="sensei-setup-wizard__button sensei-setup-wizard__button--primary"
							onClick={ goToNextStep }
						>
							{ __(
								'Install the new Sensei theme',
								'sensei-lms'
							) }
						</button>

						<button
							className="sensei-setup-wizard__button sensei-setup-wizard__button--secondary sensei-setup-wizard__button--only-medium"
							onClick={ goToNextStep }
						>
							{ __( 'Explore the theme', 'sensei-lms' ) }
						</button>
					</div>

					<div className="sensei-setup-wizard__action-skip">
						<button
							className="sensei-setup-wizard__button sensei-setup-wizard__button--link"
							onClick={ goToNextStep }
						>
							{ __( 'Skip theme selection', 'sensei-lms' ) }
						</button>
					</div>
				</div>
			</div>

			<div ref={ responsiveAreaRef }>
				{ isBigScreen ? <BigScreen /> : <SmallScreen /> }
			</div>
		</>
	);
};

export default Theme;
