<?php

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\RestoreMembershipApplication;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FakeApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\MembershipApplicationEventLoggerSpy;
use WMDE\Fundraising\MembershipContext\UseCases\RestoreMembershipApplication\RestoreMembershipApplicationUseCase;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\RestoreMembershipApplication\RestoreMembershipApplicationUseCase
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\RestoreMembershipApplication\RestoreMembershipApplicationResponse
 */
class RestoreMembershipApplicationUseCaseTest extends TestCase {

	private const AUTH_USER_NAME = "Pintman Paddy Losty";

	private MembershipApplicationEventLoggerSpy $membershipApplicationEventLogger;
	private FakeApplicationRepository $applicationRepository;

	protected function setUp(): void {
		parent::setUp();
		$this->membershipApplicationEventLogger = new MembershipApplicationEventLoggerSpy();
		$this->applicationRepository = new FakeApplicationRepository();
	}

	public function testOnRestoreNonExistentMembershipApplication_actionFails() {
		$useCase = $this->newUseCase();

		$response = $useCase->restoreApplication( 999, self::AUTH_USER_NAME );

		$this->assertFalse( $response->restoreSucceeded() );
	}

	public function testOnRestoreMembershipApplication_actionSucceeds() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$this->assertTrue( $response->restoreSucceeded() );
	}

	public function testOnRestoreMembershipApplication_removesCancelledStatus() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application );

		$useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$this->assertFalse( $this->applicationRepository->getApplicationById( 1 )->isCancelled() );
	}

	public function testOnRestoreMembershipApplication_returnsMembershipApplicationId() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$this->assertSame( 1, $response->getMembershipApplication() );
	}

	public function testOnRestoreOnNonCancelledMembershipApplication_actionFails() {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$this->assertFalse( $response->restoreSucceeded() );
	}

	public function testOnRestoreMembershipApplication_adminUserNameIsWrittenInLogEntry(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application );
		$useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$logs = $this->membershipApplicationEventLogger->getLogs();
		$message = sprintf( RestoreMembershipApplicationUseCase::LOG_MESSAGE_MARKED_AS_RESTORED, self::AUTH_USER_NAME );

		$this->assertCount( 1, $logs );
		$this->assertContains( $message, $logs );
	}

	private function newUseCase( MembershipApplication ...$applications ): RestoreMembershipApplicationUseCase {
		foreach ( $applications as $application ) {
			$this->applicationRepository->storeApplication( $application );
		}

		return new RestoreMembershipApplicationUseCase(
			$this->applicationRepository,
			$this->membershipApplicationEventLogger
		);
	}
}
