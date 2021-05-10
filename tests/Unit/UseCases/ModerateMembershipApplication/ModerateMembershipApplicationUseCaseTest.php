<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ModerateMembershipApplication;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FakeApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\MembershipApplicationEventLoggerSpy;
use WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication\ModerateMembershipApplicationUseCase;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication\ModerateMembershipApplicationUseCase
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication\ModerateMembershipApplicationResponse
 */
class ModerateMembershipApplicationUseCaseTest extends TestCase {

	private const AUTH_USER_NAME = "Pintman Paddy Losty";

	private MembershipApplicationEventLoggerSpy $membershipApplicationEventLogger;
	private FakeApplicationRepository $applicationRepository;

	protected function setUp(): void {
		parent::setUp();
		$this->membershipApplicationEventLogger = new MembershipApplicationEventLoggerSpy();
		$this->applicationRepository = new FakeApplicationRepository();
	}

	public function testModerateNonExistentMembershipApplication_actionFails() {
		$useCase = $this->newUseCase();

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->moderationChangeSucceeded() );
	}

	public function testApproveNonExistentMembershipApplication_actionFails() {
		$useCase = $this->newUseCase();

		$response = $useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->moderationChangeSucceeded() );
	}

	public function testSetModerateOnMembershipApplication_actionSucceeds() {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertTrue( $response->moderationChangeSucceeded() );
	}

	public function testSetModerateOnMembershipApplication_setsModerated() {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertTrue( $response->moderationChangeSucceeded() );
	}

	public function testSetModerateOnMembershipApplication_returnsMembershipApplicationId() {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertSame( 1, $response->getMembershipApplication() );
	}

	public function testSetModerateOnMembershipApplicationThatIsMarkedForModeration_actionFails() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->moderationChangeSucceeded() );
	}

	public function testSetModerateOnCancelledMembershipApplication_actionFails() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->moderationChangeSucceeded() );
	}

	public function testApproveMembershipApplication_removesModeratedStatus() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration();
		$useCase = $this->newUseCase( $application );

		$useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $this->applicationRepository->getApplicationById( 1 )->needsModeration() );
	}

	public function testApproveOnApprovedMembershipApplication_actionFails() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->approve();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->moderationChangeSucceeded() );
	}

	public function testOnModerateMembershipApplication_adminUserNameIsWrittenAsLogEntry(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );
		$logs = $this->membershipApplicationEventLogger->getLogs();

		$message = sprintf( ModerateMembershipApplicationUseCase::LOG_MESSAGE_MARKED_FOR_MODERATION, self::AUTH_USER_NAME );

		$this->assertCount( 1, $logs );
		$this->assertContains( $message, $logs );
	}

	public function testOnApproveMembershipApplication_adminUserNameIsWrittenAsLogEntry(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration();
		$useCase = $this->newUseCase( $application );

		$useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );
		$logs = $this->membershipApplicationEventLogger->getLogs();

		$message = sprintf( ModerateMembershipApplicationUseCase::LOG_MESSAGE_MARKED_AS_APPROVED, self::AUTH_USER_NAME );

		$this->assertCount( 1, $logs );
		$this->assertContains( $message, $logs );
	}

	private function newUseCase( MembershipApplication ...$applications ): ModerateMembershipApplicationUseCase {
		foreach ( $applications as $application ) {
			$this->applicationRepository->storeApplication( $application );
		}

		return new ModerateMembershipApplicationUseCase(
			$this->applicationRepository,
			$this->membershipApplicationEventLogger
		);
	}
}
