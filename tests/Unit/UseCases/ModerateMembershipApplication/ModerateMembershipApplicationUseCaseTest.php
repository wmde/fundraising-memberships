<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ModerateMembershipApplication;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\FakeApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\InMemoryPaymentRepository;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\MembershipApplicationEventLoggerSpy;
use WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication\ModerateMembershipApplicationUseCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication\ModerateMembershipApplicationUseCase
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication\ModerateMembershipApplicationResponse
 */
class ModerateMembershipApplicationUseCaseTest extends TestCase {

	private const AUTH_USER_NAME = "Pintman Paddy Losty";

	private MembershipApplicationEventLoggerSpy $membershipApplicationEventLogger;
	private FakeApplicationRepository $applicationRepository;
	private PaymentRepository $paymentRepository;

	protected function setUp(): void {
		parent::setUp();
		$this->membershipApplicationEventLogger = new MembershipApplicationEventLoggerSpy();
		$this->applicationRepository = new FakeApplicationRepository();

		$this->paymentRepository = new InMemoryPaymentRepository( [
			ValidMembershipApplication::PAYMENT_ID => DirectDebitPayment::create(
				ValidMembershipApplication::PAYMENT_ID,
				Euro::newFromCents( 2500 ),
				PaymentInterval::Quarterly,
				new Iban( ValidMembershipApplication::PAYMENT_IBAN ),
				ValidMembershipApplication::PAYMENT_BIC
			)
		] );
	}

	public function testModerateNonExistentMembershipApplication_actionFails() {
		$useCase = $this->newUseCase();

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->isSuccess() );
	}

	public function testApproveNonExistentMembershipApplication_actionFails() {
		$useCase = $this->newUseCase();

		$response = $useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->isSuccess() );
	}

	public function testSetModerateOnMembershipApplication_actionSucceeds() {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertTrue( $response->isSuccess() );
	}

	public function testSetModerateOnMembershipApplication_setsModerated() {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertTrue( $response->isSuccess() );
	}

	public function testSetModerateOnMembershipApplication_returnsMembershipApplicationId() {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertSame( 1, $response->getMembershipApplicationId() );
	}

	public function testSetModerateOnMembershipApplicationThatIsMarkedForModeration_actionFails() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration( $this->makeGenericModerationReason() );
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->isSuccess() );
	}

	private function makeGenericModerationReason(): ModerationReason {
		return new ModerationReason( ModerationIdentifier::MANUALLY_FLAGGED_BY_ADMIN );
	}

	public function testSetModerateOnCancelledMembershipApplication_actionFails() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->isSuccess() );
	}

	public function testApproveMembershipApplication_removesModeratedStatus() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration( $this->makeGenericModerationReason() );
		$useCase = $this->newUseCase( $application );

		$useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $this->applicationRepository->getApplicationById( 1 )->isMarkedForModeration() );
	}

	public function testApproveOnApprovedMembershipApplication_actionFails() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->approve();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->isSuccess() );
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

	public function testGivenConfirmedPayment_onApproveConfirmsMembership(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration( $this->makeGenericModerationReason() );
		$useCase = $this->newUseCase( $application );

		$useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$storedApplication = $this->applicationRepository->getApplicationById( $application->getId() );
		$this->assertTrue( $storedApplication->isConfirmed() );
	}

	public function testGivenUnConfirmedPayment_onApproveLeavesMembershipUnconfirmed(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration( $this->makeGenericModerationReason() );
		$this->paymentRepository->storePayment( new PayPalPayment(
			ValidMembershipApplication::PAYMENT_ID,
			Euro::newFromCents( 2500 ),
			PaymentInterval::Quarterly
		) );
		$useCase = $this->newUseCase( $application );

		$useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$storedApplication = $this->applicationRepository->getApplicationById( $application->getId() );
		$this->assertFalse( $storedApplication->isConfirmed() );
	}

	public function testOnApproveMembershipApplication_adminUserNameIsWrittenAsLogEntry(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration( $this->makeGenericModerationReason() );
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
			$this->membershipApplicationEventLogger,
			$this->paymentRepository
		);
	}
}
