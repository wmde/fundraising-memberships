<?php

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\RestoreMembershipApplication;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\FakeApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\MembershipApplicationEventLoggerSpy;
use WMDE\Fundraising\MembershipContext\UseCases\RestoreMembershipApplication\RestoreMembershipApplicationUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\CancelPaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\SuccessResponse;

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

	public function testOnRestoreNonExistentMembershipApplication_actionFails(): void {
		$useCase = $this->newUseCase();

		$response = $useCase->restoreApplication( 999, self::AUTH_USER_NAME );

		$this->assertFalse( $response->isSuccess() );
	}

	public function testOnRestoreMembershipApplication_actionSucceeds(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$this->assertTrue( $response->isSuccess() );
	}

	public function testOnRestoreMembershipApplication_removesCancelledStatus(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application );

		$useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$this->assertNotNull( $this->applicationRepository->getUnexportedMembershipApplicationById( 1 ) );
		$this->assertFalse( $this->applicationRepository->getUnexportedMembershipApplicationById( 1 )->isCancelled() );
	}

	public function testOnRestoreMembershipApplication_returnsMembershipApplicationId(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$this->assertSame( 1, $response->getMembershipApplicationId() );
	}

	public function testOnRestoreOnNonCancelledMembershipApplication_actionFails(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$this->assertFalse( $response->isSuccess() );
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

	public function testOnRestoreMembershipApplication_andPaymentIsNotCompleted_membershipISNotConfirmed(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application, $this->givenSucceedingCancelPaymentUseCase( paymentIsCompleted: false ) );
		$useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$this->assertNotNull( $this->applicationRepository->getUnexportedMembershipApplicationById( 1 ) );
		$this->assertFalse( $this->applicationRepository->getUnexportedMembershipApplicationById( 1 )->isConfirmed() );
	}

	public function testOnRestoreMembershipApplication_andPaymentIsCompleted_confirmsMembership(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application, $this->givenSucceedingCancelPaymentUseCase( paymentIsCompleted: true ) );
		$useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );

		$this->assertNotNull( $this->applicationRepository->getUnexportedMembershipApplicationById( 1 ) );
		$this->assertTrue( $this->applicationRepository->getUnexportedMembershipApplicationById( 1 )->isConfirmed() );
	}

	public function testWhenPaymentRestorationFails_restorationFails(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application, $this->givenFailingCancelPaymentUseCase() );

		$response = $useCase->restoreApplication( $application->getId(), self::AUTH_USER_NAME );
		$this->assertFalse( $response->isSuccess() );
	}

	private function newUseCase( ?MembershipApplication $application = null, ?CancelPaymentUseCase $cancelPaymentUseCase = null ): RestoreMembershipApplicationUseCase {
		if ( $application !== null ) {
			$this->applicationRepository->storeApplication( $application );
		}

		return new RestoreMembershipApplicationUseCase(
			$this->applicationRepository,
			$this->membershipApplicationEventLogger,
			$cancelPaymentUseCase ?? $this->givenSucceedingCancelPaymentUseCase()
		);
	}

	private function givenSucceedingCancelPaymentUseCase( bool $paymentIsCompleted = false ): CancelPaymentUseCase {
		$useCase = $this->createMock( CancelPaymentUseCase::class );
		$useCase->method( 'restorePayment' )
			->willReturn( new SuccessResponse( $paymentIsCompleted ) );
		return $useCase;
	}

	private function givenFailingCancelPaymentUseCase(): CancelPaymentUseCase {
		$useCase = $this->createMock( CancelPaymentUseCase::class );
		$useCase->method( 'restorePayment' )
			->willReturn( new FailureResponse( 'This payment is already cancelled' ) );
		return $useCase;
	}
}
