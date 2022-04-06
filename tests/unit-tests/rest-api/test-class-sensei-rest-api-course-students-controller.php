<?php

/**
 * Test for Sensei_REST_API_Course_Students_Controller.
 *
 * @covers Sensei_REST_API_Course_Students_Controller
 */
class Sensei_REST_API_Course_Students_Controller_Test extends WP_Test_REST_TestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_Course_Enrolment_Test_Helpers;
	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	/**
	 * Sensei post factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Test specific setup.
	 */
	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		$this->factory = new Sensei_Factory();

		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();
	}

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testAddUsersToCourses_RequestGiven_ReturnsSuccessfulResponse() {
		/* Arrange. */
		$student_ids = $this->factory->user->create_many( 2 );
		$course_ids  = $this->factory->course->create_many( 2 );

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => $student_ids,
					'course_ids'  => $course_ids,
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertSame( 200, $response->get_status() );
	}

	public function testAddUsersToCourses_RequestGiven_EnrolsUserForCourse() {
		/* Arrange. */
		$student_id = $this->factory->user->create();
		$course_id  = $this->factory->course->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $student_id ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		$this->server->dispatch( $request );

		/* Assert. */
		$enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$this->assertTrue( $enrolment->is_enrolled( $student_id ) );
	}

	public function testAddUsersToCourses_UserNotFoundGiven_ReturnsSuccessfulResponse() {
		/* Arrange. */
		$course_id = $this->factory->course->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ 999 ],
					'course_ids'  => [ $course_id ],
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertSame( 200, $response->get_status() );
	}

	public function testAddUsersToCourses_UserWithInsufficientPermissions_ReturnsForbiddenResponse() {
		/* Arrange. */
		$student_ids = $this->factory->user->create_many( 2 );
		$course_ids  = $this->factory->course->create_many( 2 );

		$this->login_as_student();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => $student_ids,
					'course_ids'  => $course_ids,
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertSame( 403, $response->get_status() );
	}

	public function testAddUsersToCourses_CourseNotFoundGiven_ReturnsForbiddenResponse() {
		/* Arrange. */
		$student_id = $this->factory->user->create();

		$this->login_as_admin();

		/* Act. */
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/course-students/batch' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'student_ids' => [ $student_id ],
					'course_ids'  => [ 999 ],
				]
			)
		);
		$response = $this->server->dispatch( $request );

		/* Assert. */
		$this->assertSame( 403, $response->get_status() );
	}
}
