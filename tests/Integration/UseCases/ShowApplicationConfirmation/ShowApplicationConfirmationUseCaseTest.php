<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\ShowApplicationConfirmation;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FailingAuthorizer;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FakeApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FixedApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\SucceedingAuthorizer;
use WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation\ShowAppConfirmationRequest;
use WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation\ShowApplicationConfirmationUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation\ShowApplicationConfirmationUseCase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ShowApplicationConfirmationUseCaseTest extends TestCase {

	private const APPLICATION_ID = 42;
	private const PAYMENT_DATA = [
		'anything' => 'can',
		'go' => 'here',
	];

	/**
	 * @var FakeShowApplicationConfirmationPresenter
	 */
	private $presenter;

	/**
	 * @var ApplicationAuthorizer
	 */
	private $authorizer;

	/**
	 * @var FakeApplicationRepository
	 */
	private $repository;

	/**
	 * @var FixedApplicationTokenFetcher
	 */
	private $tokenFetcher;

	public function setUp(): void {
		$this->presenter = new FakeShowApplicationConfirmationPresenter();
		$this->authorizer = new SucceedingAuthorizer();
		$this->repository = new FakeApplicationRepository();
		$this->tokenFetcher = FixedApplicationTokenFetcher::newWithDefaultTokens();

		$this->repository->storeApplication( $this->newApplication() );
	}

	private function newApplication(): MembershipApplication {
		$application = ValidMembershipApplication::newDomainEntity();

		$application->assignId( self::APPLICATION_ID );

		return $application;
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
			$this->tokenFetcher,
			$getPaymentUseCase
		);
	}

	public function testHappyPath_successResponseWithApplicationIsReturned(): void {
		$this->invokeUseCaseWithCorrectRequestModel();

		$this->assertSame(
			self::APPLICATION_ID,
			$this->presenter->getShownApplication()->getId()
		);

		$this->assertSame(
			self::PAYMENT_DATA,
			$this->presenter->getShownPaymentData()
		);

		$this->assertSame(
			FixedApplicationTokenFetcher::UPDATE_TOKEN,
			$this->presenter->getShownUpdateToken()
		);
	}

	public function testWhenRepositoryThrowsAnonymizedException_anonymizedMessageIsPresented(): void {
		$this->repository->throwAnonymizedOnRead();

		$this->invokeUseCaseWithCorrectRequestModel();

		$this->assertTrue( $this->presenter->anonymizedResponseWasShown() );
	}

	public function testWhenAuthorizerReturnsFalse_accessViolationIsPresented(): void {
		$this->authorizer = new FailingAuthorizer();

		$this->invokeUseCaseWithCorrectRequestModel();

		$this->assertTrue( $this->presenter->accessViolationWasShown() );
	}

	public function testWhenRepositoryThrowsException_technicalErrorIsPresented(): void {
		$this->repository->throwOnRead();

		$this->invokeUseCaseWithCorrectRequestModel();

		$this->assertSame( 'A database error occurred', $this->presenter->getShownTechnicalError() );
	}

}
