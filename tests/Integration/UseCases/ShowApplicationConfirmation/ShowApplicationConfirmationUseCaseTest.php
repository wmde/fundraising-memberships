<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\ShowApplicationConfirmation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\FailingAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\FakeMembershipRepository;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\SucceedingAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation\ShowAppConfirmationRequest;
use WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation\ShowApplicationConfirmationUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

#[CoversClass( ShowApplicationConfirmationUseCase::class )]
class ShowApplicationConfirmationUseCaseTest extends TestCase {

	private const APPLICATION_ID = 42;
	private const PAYMENT_DATA = [
		'anything' => 'can',
		'go' => 'here',
	];

	private FakeShowApplicationConfirmationPresenter $presenter;

	private MembershipAuthorizationChecker|SucceedingAuthorizationChecker $authorizer;

	private FakeMembershipRepository $repository;

	public function setUp(): void {
		$this->presenter = new FakeShowApplicationConfirmationPresenter();
		$this->authorizer = new SucceedingAuthorizationChecker();
		$this->repository = new FakeMembershipRepository();

		$this->repository->storeApplication( ValidMembershipApplication::newDomainEntity( self::APPLICATION_ID ) );
	}

	private function invokeUseCaseWithCorrectRequestModel(): void {
		$request = new ShowAppConfirmationRequest( self::APPLICATION_ID );
		$this->newUseCase()->showConfirmation( $request );
	}

	private function newUseCase(): ShowApplicationConfirmationUseCase {
		$getPaymentUseCase = $this->createStub( GetPaymentUseCase::class );
		$getPaymentUseCase->method( 'getPaymentDataArray' )->willReturn( self::PAYMENT_DATA );

		return new ShowApplicationConfirmationUseCase(
			$this->presenter,
			$this->authorizer,
			$this->repository,
			$getPaymentUseCase
		);
	}

	public function testHappyPath_successResponseWithApplicationIsReturned(): void {
		$this->invokeUseCaseWithCorrectRequestModel();

		$this->assertNotNull( $this->presenter->getShownApplication() );
		$this->assertSame( self::APPLICATION_ID, $this->presenter->getShownApplication()->getId() );

		$this->assertSame(
			self::PAYMENT_DATA,
			$this->presenter->getShownPaymentData()
		);
	}

	public function testWhenRepositoryThrowsAnonymizedException_anonymizedMessageIsPresented(): void {
		$this->repository->throwAnonymizedOnRead();

		$this->invokeUseCaseWithCorrectRequestModel();

		$this->assertTrue( $this->presenter->anonymizedResponseWasShown() );
	}

	public function testWhenAuthorizerReturnsFalse_accessViolationIsPresented(): void {
		$this->authorizer = new FailingAuthorizationChecker();

		$this->invokeUseCaseWithCorrectRequestModel();

		$this->assertTrue( $this->presenter->accessViolationWasShown() );
	}

	public function testWhenRepositoryThrowsException_technicalErrorIsPresented(): void {
		$this->repository->throwOnRead();

		$this->invokeUseCaseWithCorrectRequestModel();

		$this->assertSame( 'A database error occurred', $this->presenter->getShownTechnicalError() );
	}

}
