<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\CancelMembershipApplication;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogger;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\FailingAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\FakeMembershipRepository;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\MembershipApplicationEventLoggerSpy;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\SucceedingAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancellationRequest;
use WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancellationResponse;
use WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancelMembershipApplicationUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\CancelPaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\SuccessResponse;

#[CoversClass( CancelMembershipApplicationUseCase::class )]
#[CoversClass( CancellationResponse::class )]
class CancelMembershipApplicationUseCaseTest extends TestCase {

	private const ID_OF_NON_EXISTING_APPLICATION = 1337;
	private const AUTH_USER_NAME = "Pintman Paddy Losty";

	public function testGivenIdOfUnknownDonation_cancellationIsNotSuccessful(): void {
		$useCase = $this->givenUseCase();

		$response = $this->whenCancelApplicationRequestIsSent( $useCase, self::ID_OF_NON_EXISTING_APPLICATION );

		$this->assertFalse( $response->isSuccess() );
	}

	public function testFailureResponseContainsApplicationId(): void {
		$useCase = $this->givenUseCase();

		$response = $this->whenCancelApplicationRequestIsSent( $useCase, self::ID_OF_NON_EXISTING_APPLICATION );

		$this->assertSame(
			self::ID_OF_NON_EXISTING_APPLICATION,
			$response->getMembershipApplicationId()
		);
	}

	public function testGivenIdOfCancellableApplication_cancellationIsSuccessful(): void {
		[ $repository, $application ] = $this->givenStoredCancelableApplication();
		$useCase = $this->givenUseCase( repository: $repository );

		$response = $this->whenCancelApplicationRequestIsSent( $useCase, $application->getId() );

		$this->assertTrue( $response->isSuccess() );
		$this->assertSame( $application->getId(), $response->getMembershipApplicationId() );
	}

	public function testWhenAuthorizationFails_cancellationFails(): void {
		[ $repository, $application ] = $this->givenStoredCancelableApplication();
		$useCase = $this->givenUseCase( authorizer: new FailingAuthorizationChecker(), repository: $repository );

		$response = $this->whenCancelApplicationRequestIsSent( $useCase, $application->getId() );

		$this->assertFalse( $response->isSuccess() );
	}

	public function testWhenPaymentCancellationFails_cancellationFails(): void {
		[ $repository, $application ] = $this->givenStoredCancelableApplication();
		$cancelPaymentUseCase = $this->givenFailingCancelPaymentUseCase();
		$useCase = $this->givenUseCase( repository: $repository, cancelPaymentUseCase: $cancelPaymentUseCase );

		$response = $this->whenCancelApplicationRequestIsSent( $useCase, $application->getId() );

		$this->assertFalse( $response->isSuccess() );
	}

	public function testWhenSaveFails_cancellationFails(): void {
		[ $repository, $application ] = $this->givenStoredCancelableApplication();
		$repository->throwOnWrite();
		$useCase = $this->givenUseCase( repository: $repository );

		$this->expectException( StoreMembershipApplicationException::class );

		$this->whenCancelApplicationRequestIsSent( $useCase, $application->getId() );
	}

	public function testWhenGivenAuthorizedUser_logsUserName(): void {
		[ $repository, $application ] = $this->givenStoredCancelableApplication();
		$logger = $this->givenEventLoggerSpy();
		$useCase = $this->givenUseCase( repository: $repository, logger: $logger );

		$this->whenCancelApplicationRequestIsSentByAdmin( $useCase, $application->getId() );

		$this->expectLoggerToBeCalledOnceWithMessage(
			$logger,
			sprintf( CancelMembershipApplicationUseCase::LOG_MESSAGE_ADMIN_STATUS_CHANGE, self::AUTH_USER_NAME )
		);
	}

	public function testWhenGivenUnauthorizedUser_logsFrontEnd(): void {
		[ $repository, $application ] = $this->givenStoredCancelableApplication();
		$logger = $this->givenEventLoggerSpy();

		$this->givenUseCase( repository: $repository, logger: $logger )->cancelApplication(
			new CancellationRequest( $application->getId() )
		);

		$this->expectLoggerToBeCalledOnceWithMessage(
			$logger,
			CancelMembershipApplicationUseCase::LOG_MESSAGE_FRONTEND_STATUS_CHANGE
		);
	}

	private function givenUseCase(
		?MembershipAuthorizationChecker $authorizer = null,
		?MembershipRepository $repository = null,
		?MembershipApplicationEventLogger $logger = null,
		?CancelPaymentUseCase $cancelPaymentUseCase = null
	): CancelMembershipApplicationUseCase {
		return new CancelMembershipApplicationUseCase(
			$authorizer ?? new SucceedingAuthorizationChecker(),
			$repository ?? new FakeMembershipRepository(),
			$logger ?? $this->createStub( MembershipApplicationEventLogger::class ),
			$cancelPaymentUseCase ?? $this->givenSucceedingCancelPaymentUseCase()
		);
	}

	private function whenCancelApplicationRequestIsSent( CancelMembershipApplicationUseCase $useCase, int $applicationId ): CancellationResponse {
		return $useCase->cancelApplication( new CancellationRequest( $applicationId ) );
	}

	private function whenCancelApplicationRequestIsSentByAdmin( CancelMembershipApplicationUseCase $useCase, int $applicationId ): void {
		$useCase->cancelApplication( new CancellationRequest( $applicationId, self::AUTH_USER_NAME ) );
	}

	/**
	 * @return array{0:FakeMembershipRepository,1:MembershipApplication}
	 */
	private function givenStoredCancelableApplication(): array {
		$repository = new FakeMembershipRepository();
		$application = ValidMembershipApplication::newDomainEntity();
		$repository->storeApplication( $application );
		return [ $repository, $application ];
	}

	private function givenEventLoggerSpy(): MembershipApplicationEventLoggerSpy {
		return new MembershipApplicationEventLoggerSpy();
	}

	private function givenSucceedingCancelPaymentUseCase(): CancelPaymentUseCase {
		$useCase = $this->createMock( CancelPaymentUseCase::class );
		$useCase->method( 'cancelPayment' )
			->willReturn( new SuccessResponse( true ) );
		return $useCase;
	}

	private function givenFailingCancelPaymentUseCase(): CancelPaymentUseCase {
		$useCase = $this->createMock( CancelPaymentUseCase::class );
		$useCase->method( 'cancelPayment' )
			->willReturn( new FailureResponse( 'This payment is already cancelled' ) );
		return $useCase;
	}

	private function expectLoggerToBeCalledOnceWithMessage( MembershipApplicationEventLoggerSpy $logger, string $message ): void {
		$logs = $logger->getLogs();
		$this->assertCount( 1, $logs );
		$this->assertContains( $message, $logs );
	}

}
