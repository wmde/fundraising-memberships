<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipApplicationTokens;
use WMDE\Fundraising\MembershipContext\DataAccess\IncentiveFinder;
use WMDE\Fundraising\MembershipContext\Domain\Event\MembershipCreatedEvent;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\EventEmitter;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipConfirmationMailer;
use WMDE\Fundraising\MembershipContext\Infrastructure\PaymentServiceFactory;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\EventEmitterSpy;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FixedApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\InMemoryApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\TemplateBasedMailerSpy;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\TestIncentiveFinder;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationPiwikTracker;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationTracker;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipPolicyValidator;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipUseCase;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipApplicationValidator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\SuccessResponse;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipUseCase
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipResponse
 * @covers \WMDE\Fundraising\MembershipContext\Infrastructure\MembershipConfirmationMailer
 */
class ApplyForMembershipUseCaseTest extends TestCase {

	private const FIRST_APPLICATION_ID = 1;
	private const ACCESS_TOKEN = 'Gimmeh all the access';
	private const UPDATE_TOKEN = 'Lemme change all the stuff';
	private const PAYMENT_PROVIDER_URL = 'https://paypal.example.com/';

	private TemplateBasedMailerSpy $mailerSpy;

	public function setUp(): void {
		$this->mailerSpy = new TemplateBasedMailerSpy( $this );
	}

	private function newMailerWithTemplateMailerSpy(): MembershipConfirmationMailer {
		$getPaymentUseCaseMock = $this->createMock( GetPaymentUseCase::class );
		$testData = [
			'amountInEuroCents' => 1000,
			'paymentType' => 'BEZ',
			'interval' => 3
		];
		$getPaymentUseCaseMock->method( 'getPaymentDataArray' )->willReturn( $testData );
		return new MembershipConfirmationMailer( $this->mailerSpy, $getPaymentUseCaseMock );
	}

	private function newSucceedingValidator(): MembershipApplicationValidator {
		$validator = $this->getMockBuilder( MembershipApplicationValidator::class )
			->disableOriginalConstructor()->getMock();

		$validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( new ApplicationValidationResult() );

		return $validator;
	}

	private function newSucceedingCreatePaymentUseCase(): CreatePaymentUseCase {
		$useCaseMock = $this->createMock( CreatePaymentUseCase::class );

		$successResponse = new SuccessResponse(
			ValidMembershipApplication::PAYMENT_ID,
			$this->createStub( PaymentProviderURLGenerator::class ),
			true
		);

		$useCaseMock->method( 'createPayment' )->willReturn( $successResponse );
		return $useCaseMock;
	}

	private function newSucceedingUnconfirmedCreatePaymentUseCase(): CreatePaymentUseCase {
		$useCaseMock = $this->createMock( CreatePaymentUseCase::class );

		$successResponse = new SuccessResponse(
			ValidMembershipApplication::PAYMENT_ID,
			$this->createStub( PaymentProviderURLGenerator::class ),
			false
		);

		$useCaseMock->method( 'createPayment' )->willReturn( $successResponse );
		return $useCaseMock;
	}

	private function newFailingCreatePaymentUseCase(): CreatePaymentUseCase {
		$useCaseMock = $this->createMock( CreatePaymentUseCase::class );
		$responseMock = new FailureResponse( "the payment was not successfull for some reason" );
		$useCaseMock->method( 'createPayment' )->willReturn( $responseMock );
		return $useCaseMock;
	}

	public function testGivenValidRequest_applicationSucceeds(): void {
		$response = $this->makeUseCase()->applyForMembership( $this->newValidRequest() );
		$this->assertTrue( $response->isSuccessful() );
	}

	public function testGivenFailingPaymentInRequest_applicationIsNotSuccessful(): void {
		$useCase = $this->makeUseCase( createPaymentUseCase: $this->newFailingCreatePaymentUseCase() );
		$response = $useCase->applyForMembership( $this->newValidRequest() );
		$this->assertFalse( $response->isSuccessful() );
	}

	private function newTokenFetcher(): ApplicationTokenFetcher {
		return new FixedApplicationTokenFetcher( new MembershipApplicationTokens(
			self::ACCESS_TOKEN,
			self::UPDATE_TOKEN
		) );
	}

	private function newValidRequest(): ApplyForMembershipRequest {
		$request = new ApplyForMembershipRequest();

		$request->setMembershipType( ValidMembershipApplication::MEMBERSHIP_TYPE );
		$request->setApplicantCompanyName( '' );
		$request->setMembershipType( ValidMembershipApplication::MEMBERSHIP_TYPE );
		$request->setApplicantSalutation( ValidMembershipApplication::APPLICANT_SALUTATION );
		$request->setApplicantTitle( ValidMembershipApplication::APPLICANT_TITLE );
		$request->setApplicantFirstName( ValidMembershipApplication::APPLICANT_FIRST_NAME );
		$request->setApplicantLastName( ValidMembershipApplication::APPLICANT_LAST_NAME );
		$request->setApplicantStreetAddress( ValidMembershipApplication::APPLICANT_STREET_ADDRESS );
		$request->setApplicantPostalCode( ValidMembershipApplication::APPLICANT_POSTAL_CODE );
		$request->setApplicantCity( ValidMembershipApplication::APPLICANT_CITY );
		$request->setApplicantCountryCode( ValidMembershipApplication::APPLICANT_COUNTRY_CODE );
		$request->setApplicantEmailAddress( ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS );
		$request->setApplicantPhoneNumber( ValidMembershipApplication::APPLICANT_PHONE_NUMBER );
		$request->setApplicantDateOfBirth( ValidMembershipApplication::APPLICANT_DATE_OF_BIRTH );

		$request->setTrackingInfo( $this->newTrackingInfo() );
		$request->setPiwikTrackingString( 'foo/bar' );

		$request->setOptsIntoDonationReceipt( true );

		$request->setPaymentCreationRequest( ValidMembershipApplication::newPaymentCreationRequest() );

		return $request->assertNoNullFields();
	}

	private function newTrackingInfo(): MembershipApplicationTrackingInfo {
		return new MembershipApplicationTrackingInfo(
			ValidMembershipApplication::TEMPLATE_CAMPAIGN,
			ValidMembershipApplication::TEMPLATE_NAME
		);
	}

	public function testGivenValidRequest_applicationGetsPersisted(): void {
		$repository = $this->makeMembershipRepositoryStub();
		$this->makeUseCase( repository: $repository )->applyForMembership( $this->newValidRequest() );

		$expectedApplication = ValidMembershipApplication::newDomainEntity();
		$expectedApplication->confirm();
		$expectedApplication->assignId( self::FIRST_APPLICATION_ID );

		$application = $repository->getApplicationById( $expectedApplication->getId() );
		$this->assertNotNull( $application );

		$this->assertEquals( $expectedApplication, $application );
	}

	public function testGivenValidRequestWithImmediateConfirmation_confirmationEmailIsSend(): void {
		$mailer = $this->newMailerWithTemplateMailerSpy();
		$this->makeUseCase( mailNotifier: $mailer )->applyForMembership( $this->newValidRequest() );

		$this->mailerSpy->expectToBeCalledOnceWith(
			new EmailAddress( ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS ),
			[
				'membershipType' => 'sustaining',
				'membershipFee' => '10.00',
				'paymentIntervalInMonths' => 3,
				'salutation' => 'Herr',
				'title' => '',
				'lastName' => 'The Great',
				'firstName' => 'Potato',
				'paymentType' => 'BEZ',
				'hasReceiptEnabled' => true,
				'incentives' => []
			]
		);
	}

	public function testGivenValidRequest_tokenIsGeneratedAndReturned(): void {
		$response = $this->makeUseCase()->applyForMembership( $this->newValidRequest() );

		$this->assertSame( self::ACCESS_TOKEN, $response->getAccessToken() );
		$this->assertSame( self::UPDATE_TOKEN, $response->getUpdateToken() );
	}

	public function testWhenValidationFails_failureResultIsReturned(): void {
		$response = $this->makeUseCase( validator: $this->newFailingValidator() )->applyForMembership( $this->newValidRequest() );

		$this->assertFalse( $response->isSuccessful() );
	}

	private function newFailingValidator(): MembershipApplicationValidator {
		$validator = $this->getMockBuilder( MembershipApplicationValidator::class )
			->disableOriginalConstructor()->getMock();

		$validator->expects( $this->any() )
			->method( 'validate' )
			->willReturn( $this->newInvalidValidationResult() );

		return $validator;
	}

	private function newInvalidValidationResult(): ApplicationValidationResult {
		$invalidResult = $this->createMock( ApplicationValidationResult::class );

		$invalidResult->expects( $this->any() )
			->method( 'isSuccessful' )
			->willReturn( false );

		return $invalidResult;
	}

	public function testGivenValidRequest_moderationIsNotNeeded(): void {
		$response = $this->makeUseCase()->applyForMembership( $this->newValidRequest() );

		$this->assertFalse( $response->getMembershipApplication()->needsModeration() );
	}

	public function testGivenFailingPolicyValidator_moderationIsNeeded(): void {
		$response = $this->makeUseCase( policyValidator: $this->newFailingPolicyValidator() )->applyForMembership( $this->newValidRequest() );
		$this->assertTrue( $response->getMembershipApplication()->needsModeration() );
	}

	private function newSucceedingPolicyValidator(): ApplyForMembershipPolicyValidator {
		$policyValidator = $this->getMockBuilder( ApplyForMembershipPolicyValidator::class )
			->disableOriginalConstructor()->getMock();
		$policyValidator->method( 'needsModeration' )->willReturn( false );
		return $policyValidator;
	}

	private function newFailingPolicyValidator(): ApplyForMembershipPolicyValidator {
		$policyValidator = $this->getMockBuilder( ApplyForMembershipPolicyValidator::class )
			->disableOriginalConstructor()->getMock();
		$policyValidator->method( 'needsModeration' )->willReturn( true );
		return $policyValidator;
	}

	public function testWhenApplicationIsUnconfirmed_confirmationEmailIsNotSent(): void {
		$useCase = $this->makeUseCase(
			createPaymentUseCase: $this->newSucceedingUnconfirmedCreatePaymentUseCase()
		);
		$useCase->applyForMembership( $this->newValidRequest() );
		$this->assertCount( 0, $this->mailerSpy->getSendMailCalls() );
	}

	private function newAutoDeletingPolicyValidator(): ApplyForMembershipPolicyValidator {
		$policyValidator = $this->getMockBuilder( ApplyForMembershipPolicyValidator::class )
			->disableOriginalConstructor()->getMock();
		$policyValidator->method( 'isAutoDeleted' )->willReturn( true );
		return $policyValidator;
	}

	public function testWhenUsingForbiddenEmailAddress_applicationIsCancelledAutomatically(): void {
		$repository = $this->makeMembershipRepositoryStub();
		$this->makeUseCase(
			repository: $repository,
			policyValidator: $this->newAutoDeletingPolicyValidator()
		)->applyForMembership( $this->newValidRequest() );
		$this->assertTrue( $repository->getApplicationById( 1 )->isCancelled() );
	}

	public function testGivenDonationReceiptOptOutRequest_applicationHoldsThisValue(): void {
		$repository = $this->makeMembershipRepositoryStub();
		$request = $this->newValidRequest();
		$request->setOptsIntoDonationReceipt( false );
		$this->makeUseCase( repository: $repository )->applyForMembership( $request );

		$application = $repository->getApplicationById( self::FIRST_APPLICATION_ID );
		$this->assertFalse( $application->getDonationReceipt() );
	}

	public function testUseCaseEmitsDomainEvent(): void {
		$eventEmitter = new EventEmitterSpy();
		$request = $this->newValidRequest();

		$this->makeUseCase( eventEmitter: $eventEmitter )->applyForMembership( $request );

		$events = $eventEmitter->getEvents();
		$this->assertCount( 1, $events );
		$this->assertInstanceOf( MembershipCreatedEvent::class, $events[0] );
		$this->assertTrue( $events[0]->getApplicant()->isPrivatePerson() );
		$this->assertGreaterThan( 0, $events[0]->getMembershipId() );
	}

	public function testSuccessResponseContainsGeneratedUrl(): void {
		$urlGeneratorStub = $this->createStub( PaymentProviderURLGenerator::class );
		$urlGeneratorStub->method( 'generateURL' )->willReturn( self::PAYMENT_PROVIDER_URL );
		$useCase = $this->makeUseCase(
			createPaymentUseCase: $this->makeSuccessfulPaymentServiceWithUrlGenerator( $urlGeneratorStub )
		);

		$response = $useCase->applyForMembership( $this->newValidRequest() );

		$this->assertSame( self::PAYMENT_PROVIDER_URL, $response->getPaymentProviderRedirectUrl() );
	}

	private function makeSuccessfulPaymentServiceWithUrlGenerator( PaymentProviderURLGenerator $urlGeneratorStub ): CreatePaymentUseCase {
		$paymentService = $this->createStub( CreatePaymentUseCase::class );
		$paymentService->method( 'createPayment' )->willReturn( new SuccessResponse(
			1,
			$urlGeneratorStub,
			true
		) );
		return $paymentService;
	}

	private function makeUseCase(
		?ApplicationRepository $repository = null,
		?ApplicationTokenFetcher $tokenFetcher = null,
		?MembershipConfirmationMailer $mailNotifier = null,
		?MembershipApplicationValidator $validator = null,
		?ApplyForMembershipPolicyValidator $policyValidator = null,
		?ApplicationTracker $membershipApplicationTracker = null,
		?ApplicationPiwikTracker $piwikTracker = null,
		?EventEmitter $eventEmitter = null,
		?IncentiveFinder $incentiveFinder = null,
		?CreatePaymentUseCase $createPaymentUseCase = null
	): ApplyForMembershipUseCase {
		return new ApplyForMembershipUseCase(
			$repository ?? $this->makeMembershipRepositoryStub(),
			$tokenFetcher ?? $this->newTokenFetcher(),
			$mailNotifier ?? $this->makeMailNotifier(),
			$validator ?? $this->newSucceedingValidator(),
			$policyValidator ?? $this->newSucceedingPolicyValidator(),
			$membershipApplicationTracker ?? $this->createMock( ApplicationTracker::class ),
			$piwikTracker ?? $this->createMock( ApplicationPiwikTracker::class ),
			$eventEmitter ?? $this->createMock( EventEmitter::class ),
			$incentiveFinder ?? new TestIncentiveFinder( [ new Incentive( 'I AM INCENTIVE' ) ] ),
			new PaymentServiceFactory(
				$createPaymentUseCase ?? $this->newSucceedingCreatePaymentUseCase(),
				[ PaymentType::DirectDebit ]
			)
		);
	}

	private function makeMembershipRepositoryStub(): ApplicationRepository {
		return new InMemoryApplicationRepository();
	}

	private function makeMailNotifier(): MembershipConfirmationMailer {
		return $this->createMock( MembershipConfirmationMailer::class );
	}
}
