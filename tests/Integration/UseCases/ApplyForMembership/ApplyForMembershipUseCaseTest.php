<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizer;
use WMDE\Fundraising\MembershipContext\DataAccess\IncentiveFinder;
use WMDE\Fundraising\MembershipContext\Domain\Event\MembershipCreatedEvent;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipIdGenerator;
use WMDE\Fundraising\MembershipContext\EventEmitter;
use WMDE\Fundraising\MembershipContext\Infrastructure\PaymentServiceFactory;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\EventEmitterSpy;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\InMemoryApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\InMemoryMembershipIdGenerator;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\TemplateBasedMailerSpy;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\TemplateMailerStub;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\TestIncentiveFinder;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationPiwikTracker;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationTracker;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipUseCase;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipApplicationValidator;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Moderation\ModerationResult;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Moderation\ModerationService;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification\MailMembershipApplicationNotifier;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification\MembershipNotifier;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\SuccessResponse;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipUseCase
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipResponse
 */
class ApplyForMembershipUseCaseTest extends TestCase {

	/**
	 * We prepare the id generator to always return the same id, so we can find the application in the database
	 */
	private const MEMBERSHIP_APPLICATION_ID = 55;
	private const PAYMENT_PROVIDER_URL = 'https://paypal.example.com/';

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
			'',
			true
		);

		$useCaseMock->method( 'createPayment' )->willReturn( $successResponse );
		return $useCaseMock;
	}

	private function newSucceedingUnconfirmedCreatePaymentUseCase(): CreatePaymentUseCase {
		$useCaseMock = $this->createMock( CreatePaymentUseCase::class );

		$successResponse = new SuccessResponse(
			ValidMembershipApplication::PAYMENT_ID,
			'',
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

		$request->setPaymentParameters( ValidMembershipApplication::newPaymentParameters() );

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

		$result = $this->makeUseCase( repository: $repository )->applyForMembership( $this->newValidRequest() );

		$expectedApplication = ValidMembershipApplication::newDomainEntity( self::MEMBERSHIP_APPLICATION_ID );
		$expectedApplication->confirm();
		$application = $repository->getUnexportedMembershipApplicationById( $expectedApplication->getId() );
		$this->assertNotNull( $application );
		$this->assertEquals( $expectedApplication, $application );
	}

	public function testGivenValidRequest_confirmationEmailIsSent(): void {
		$notifier = $this->createMock( MembershipNotifier::class );
		$notifier->expects( $this->once() )->method( 'sendConfirmationFor' );

		$this->makeUseCase( mailNotifier: $notifier )
			->applyForMembership( $this->newValidRequest() );
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

		$this->assertNotNull( $response->getMembershipApplication() );
		$this->assertFalse( $response->getMembershipApplication()->isMarkedForModeration() );
	}

	public function testGivenFailingPolicyValidator_moderationIsNeeded(): void {
		$response = $this->makeUseCase( policyValidator: $this->getFailingPolicyValidatorMock() )->applyForMembership( $this->newValidRequest() );
		$this->assertNotNull( $response->getMembershipApplication() );
		$this->assertTrue( $response->getMembershipApplication()->isMarkedForModeration() );
	}

	private function getSucceedingPolicyValidatorMock(): ModerationService {
		$validator = $this->createStub( ModerationService::class );
		$validator->method( 'moderateMembershipApplicationRequest' )->willReturn( new ModerationResult() );
		return $validator;
	}

	private function getFailingPolicyValidatorMock(): ModerationService {
		$validator = $this->createStub( ModerationService::class );
		$moderationResult = new ModerationResult();
		$moderationResult->addModerationReason( new ModerationReason( ModerationIdentifier::MEMBERSHIP_FEE_TOO_HIGH ) );
		$validator->method( 'moderateMembershipApplicationRequest' )->willReturn( $moderationResult );
		return $validator;
	}

	public function testWhenApplicationIsUnconfirmed_confirmationEmailIsNotSent(): void {
		$mailerSpy = new TemplateBasedMailerSpy( $this );
		$notifier = new MailMembershipApplicationNotifier( $mailerSpy, new TemplateMailerStub(), $this->makePaymentRetriever(), 'mitglieder@wikimedia.de' );
		$useCase = $this->makeUseCase(
			mailNotifier: $notifier,
			createPaymentUseCase: $this->newSucceedingUnconfirmedCreatePaymentUseCase(),
		);

		$useCase->applyForMembership( $this->newValidRequest() );

		$mailerSpy->assertWasNeverCalled();
	}

	public function testWhenUsingForbiddenEmailAddress_confirmationEmailIsNotSent(): void {
		$mailerSpy = new TemplateBasedMailerSpy( $this );
		$repository = $this->makeMembershipRepositoryStub();
		$notifier = new MailMembershipApplicationNotifier( $mailerSpy, new TemplateMailerStub(), $this->makePaymentRetriever(), 'mitglieder@wikimedia.de' );
		$useCase = $this->makeUseCase(
			repository: $repository,
			mailNotifier: $notifier,
			policyValidator: $this->newPolicyValidatorWithEmailModeration(),
			createPaymentUseCase: $this->newSucceedingUnconfirmedCreatePaymentUseCase(),
		);

		$useCase->applyForMembership( $this->newValidRequest() );

		$mailerSpy->assertWasNeverCalled();
	}

	public function testGivenDonationReceiptOptOutRequest_applicationHoldsThisValue(): void {
		$repository = $this->makeMembershipRepositoryStub();
		$request = $this->newValidRequest();
		$request->setOptsIntoDonationReceipt( false );
		$this->makeUseCase( repository: $repository )->applyForMembership( $request );

		$application = $repository->getUnexportedMembershipApplicationById( self::MEMBERSHIP_APPLICATION_ID );
		$this->assertNotNull( $application );
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

	public function testSuccessResponseContainsGeneratedUrlFromPaymentUseCase(): void {
		$useCase = $this->makeUseCase(
			createPaymentUseCase: $this->makeSuccessfulPaymentServiceWithUrl()
		);

		$response = $useCase->applyForMembership( $this->newValidRequest() );

		$this->assertSame( self::PAYMENT_PROVIDER_URL, $response->getPaymentCompletionUrl() );
	}

	private function makeSuccessfulPaymentServiceWithUrl(): CreatePaymentUseCase {
		$paymentService = $this->createStub( CreatePaymentUseCase::class );
		$paymentService->method( 'createPayment' )->willReturn( new SuccessResponse(
			1,
			self::PAYMENT_PROVIDER_URL,
			true
		) );
		return $paymentService;
	}

	private function makeUseCase(
		?ApplicationRepository $repository = null,
		?MembershipIdGenerator $membershipIdGenerator = null,
		?MembershipAuthorizer $membershipAuthorizer = null,
		?MembershipNotifier $mailNotifier = null,
		?MembershipApplicationValidator $validator = null,
		?ModerationService $policyValidator = null,
		?ApplicationTracker $membershipApplicationTracker = null,
		?ApplicationPiwikTracker $piwikTracker = null,
		?EventEmitter $eventEmitter = null,
		?IncentiveFinder $incentiveFinder = null,
		?CreatePaymentUseCase $createPaymentUseCase = null
	): ApplyForMembershipUseCase {
		return new ApplyForMembershipUseCase(
			$repository ?? $this->makeMembershipRepositoryStub(),
			$membershipIdGenerator ?? new InMemoryMembershipIdGenerator( self::MEMBERSHIP_APPLICATION_ID ),
			$membershipAuthorizer ?? $this->makeMembershipAuthorizer(),
			$mailNotifier ?? $this->makeMailNotifier(),
			$validator ?? $this->newSucceedingValidator(),
			$policyValidator ?? $this->getSucceedingPolicyValidatorMock(),
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

	private function newPolicyValidatorWithEmailModeration(): ModerationService {
		$policyValidator = $this->createStub( ModerationService::class );
		$moderationResult = new ModerationResult();
		$moderationResult->addModerationReason( new ModerationReason( ModerationIdentifier::EMAIL_BLOCKED ) );
		$policyValidator->method( 'moderateMembershipApplicationRequest' )->willReturn( $moderationResult );
		return $policyValidator;
	}

	private function makeMailNotifier(): MembershipNotifier {
		return $this->createMock( MembershipNotifier::class );
	}

	private function makePaymentRetriever(): GetPaymentUseCase {
		$mock = $this->createMock( GetPaymentUseCase::class );
		$mock->method( 'getPaymentDataArray' )
			->willReturn( [
				'amount' => 1000,
				'interval' => ValidMembershipApplication::PAYMENT_PERIOD_IN_MONTHS,
				'paymentType' => ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT
			] );
		return $mock;
	}

	private function makeMembershipAuthorizer( ?URLAuthenticator $authenticator = null ): MembershipAuthorizer {
		$authorizer = $this->createStub( MembershipAuthorizer::class );
		$authorizer->method( 'authorizeMembershipAccess' )->willReturn( $authenticator ?? $this->makeUrlAuthenticator() );
		return $authorizer;
	}

	private function makeUrlAuthenticator(): URLAuthenticator {
		$authenticator = $this->createStub( URLAuthenticator::class );
		$authenticator->method( 'addAuthenticationTokensToApplicationUrl' )->willReturnArgument( 0 );
		$authenticator->method( 'getAuthenticationTokensForPaymentProviderUrl' )->willReturn( [] );
		return $authenticator;
	}
}
