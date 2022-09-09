/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';

const FeaturedLabel = ( { postId, children } ) => {
	const [ meta ] = useEntityProp( 'postType', 'course', 'meta', postId );
	const [ media ] = useEntityProp(
		'postType',
		'course',
		'featured_media',
		postId
	);
	const isFeatured = !! meta._course_featured;
	const hasImage = media > 0;

	return (
		<div className="featured-course-wrapper">
			{ isFeatured && hasImage && (
				<span className="course-list-featured-label">
					{ __( 'Featured', 'sensei-lms' ) }
				</span>
			) }
			{ children }
		</div>
	);
};

export default FeaturedLabel;
