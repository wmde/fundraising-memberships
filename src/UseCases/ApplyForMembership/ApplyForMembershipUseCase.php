<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizer;
use WMDE\Fundraising\MembershipContext\DataAccess\IncentiveFinder;
use WMDE\Fundraising\MembershipContext\Domain\Event\MembershipCreatedEvent;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipIdGenerator;
use WMDE\Fundraising\MembershipContext\EventEmitter;
use WMDE\Fundraising\MembershipContext\Infrastructure\PaymentServiceFactory;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationPiwikTracker;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationTracker;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Moderation\ModerationService;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification\MembershipNotifier;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest;

class ApplyForMembershipUseCase {

	public function __construct(
		private readonly ApplicationRepository $repository,
		private readonly MembershipIdGenerator $idGenerator,
		private readonly MembershipAuthorizer $authorizer,
		private readonly MembershipNotifier $notifier,
		private readonly MembershipApplicationValidator $validator,
		private readonly ModerationService $policyValidator,
		/**
		 * @var ApplicationTracker
		 * @deprecated See https://phabricator.wikimedia.org/T197112
		 */
		private ApplicationTracker $membershipApplicationTracker,
		private ApplicationPiwikTracker $piwikTracker,
		private EventEmitter $eventEmitter,
		private IncentiveFinder $incentiveFinder,
		private PaymentServiceFactory $paymentServiceFactory
	) {
	}

	public function applyForMembership( ApplyForMembershipRequest $request ): ApplyForMembershipResponse {
		$validationResult = $this->validator->validate( $request );
		if ( !$validationResult->isSuccessful() ) {
			// TODO: return failures (note that we have infrastructure failures that are not ConstraintViolations)
			return ApplyForMembershipResponse::newFailureResponse( $validationResult );
		}

		$membershipId = $this->idGenerator->generateNewMembershipId();

		$urlAuthenticator = $this->authorizer->authorizeMembershipAccess( $membershipId );
		$paymentCreationRequest = $this->newPaymentCreationRequest( $request, $membershipId, $urlAuthenticator );
		$paymentCreationResponse = $this->paymentServiceFactory->getCreatePaymentUseCase()->createPayment( $paymentCreationRequest );
		if ( $paymentCreationResponse instanceof FailureResponse ) {
			$paymentViolations = new ApplicationValidationResult(
				[ ApplicationValidationResult::SOURCE_PAYMENT => $paymentCreationResponse->errorMessage ]
			);
			return ApplyForMembershipResponse::newFailureResponse( $paymentViolations );
		}

		$application = $this->newApplicationFromRequest( $request, $membershipId, $paymentCreationResponse->paymentId );

		if ( $paymentCreationResponse->paymentComplete ) {
			$application->confirm();
		}

		$moderationResult = $this->policyValidator->moderateMembershipApplicationRequest(
			$application,
			$paymentCreationRequest->amountInEuroCents,
			$paymentCreationRequest->interval
		);
		if ( $moderationResult->needsModeration() ) {
			$application->markForModeration( ...$moderationResult->getViolations() );
		}

		// TODO: handle exceptions
		$this->repository->storeApplication( $application );

		$this->eventEmitter->emit( new MembershipCreatedEvent( $application->getId(), $application->getApplicant() ) );

		// TODO: handle exceptions
		$this->membershipApplicationTracker->trackApplication( $application->getId(), $request->getTrackingInfo() );

		// TODO: handle exceptions
		$this->piwikTracker->trackApplication( $application->getId(), $request->getPiwikTrackingString() );

		if ( $application->shouldSendConfirmationMail() ) {
			$this->notifier->sendConfirmationFor( $application );
		}

		// The notifier checks if a notification is really needed (e.g. fee too high)
		$this->notifier->sendModerationNotificationToAdmin( $application );

		return ApplyForMembershipResponse::newSuccessResponse(
			$application,
			$paymentCreationResponse->paymentCompletionUrl
		);
	}

	private function newApplicationFromRequest( ApplyForMembershipRequest $request, int $membershipId, int $paymentId, ): MembershipApplication {
		return ( new MembershipApplicationBuilder( $this->incentiveFinder ) )->newApplicationFromRequest(
			$request,
			$membershipId,
			$paymentId
		);
	}

	/**
	 * We use the membership primary key as the InvoiceId because they're unique
	 * But we prepend a letter to make sure they don't clash with donations
	 *
	 * @param int $membershipId
	 *
	 * @return string
	 */
	private function generateInvoiceId( int $membershipId ): string {
		return 'M' . $membershipId;
	}

	private function newPaymentCreationRequest( ApplyForMembershipRequest $request, int $id, URLAuthenticator $urlAuthenticator ): PaymentCreationRequest {
		$applicantType = $request->isCompanyApplication() ? ApplicantType::COMPANY_APPLICANT : ApplicantType::PERSON_APPLICANT;

		// When we implement PayPal for Memberships, we need a specification from SpuMi to know how to calculate the start time
		// Could be a fixed date (1st of last month of next quarter) or a relative date (1 month from now)
		$startTimeForRecurringPayment = null;

		$domainSpecificContext = new DomainSpecificContext(
			$id,
			$startTimeForRecurringPayment,
			$this->generateInvoiceId( $id ),
			$request->getApplicantFirstName(),
			$request->getApplicantLastName()
		);
		return PaymentCreationRequest::newFromParameters(
			$request->getPaymentParameters(),
			$this->paymentServiceFactory->newPaymentValidator( $applicantType ),
			$domainSpecificContext,
			$urlAuthenticator
		);
	}

}
