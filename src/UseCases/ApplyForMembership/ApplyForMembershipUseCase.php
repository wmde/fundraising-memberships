<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipApplicationTokens;
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
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;

class ApplyForMembershipUseCase {

	public function __construct(
		private ApplicationRepository $repository,
		private MembershipIdGenerator $idGenerator,
		private ApplicationTokenFetcher $tokenFetcher,
		private MembershipNotifier $notifier,
		private MembershipApplicationValidator $validator,
		private ModerationService $policyValidator,
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

		$paymentCreationRequest = $request->getPaymentCreationRequest();
		$applicantType = $request->isCompanyApplication() ? ApplicantType::COMPANY_APPLICANT : ApplicantType::PERSON_APPLICANT;
		$paymentCreationRequest->setDomainSpecificPaymentValidator( $this->paymentServiceFactory->newPaymentValidator( $applicantType ) );

		$paymentCreationResponse = $this->paymentServiceFactory->getCreatePaymentUseCase()->createPayment( $request->getPaymentCreationRequest() );
		if ( $paymentCreationResponse instanceof FailureResponse ) {
			$paymentViolations = new ApplicationValidationResult(
				[ ApplicationValidationResult::SOURCE_PAYMENT => $paymentCreationResponse->errorMessage ]
			);
			return ApplyForMembershipResponse::newFailureResponse( $paymentViolations );
		}

		$application = $this->newApplicationFromRequest( $request, $paymentCreationResponse->paymentId );

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

		if ( $this->policyValidator->isAutoDeleted( $application ) ) {
			$application->cancel();
		}

		// TODO: handle exceptions
		$this->repository->storeApplication( $application );

		$this->eventEmitter->emit( new MembershipCreatedEvent( $application->getId(), $application->getApplicant() ) );

		// TODO: handle exceptions
		$this->membershipApplicationTracker->trackApplication( $application->getId(), $request->getTrackingInfo() );

		// TODO: handle exceptions
		$this->piwikTracker->trackApplication( $application->getId(), $request->getPiwikTrackingString() );

		if ( $application->isConfirmed() ) {
			$this->notifier->sendConfirmationFor( $application );
		}

		// The notifier checks if a notification is really needed (e.g. fee too high)
		$this->notifier->sendModerationNotificationToAdmin( $application );

		// TODO: handle exceptions
		$tokens = $this->tokenFetcher->getTokens( $application->getId() );

		return ApplyForMembershipResponse::newSuccessResponse(
			$tokens->getAccessToken(),
			$tokens->getUpdateToken(),
			$application,
			$this->generatePaymentProviderUrl(
				$paymentCreationResponse->paymentProviderURLGenerator,
				$application,
				$tokens
			)
		);
	}

	private function generatePaymentProviderUrl( PaymentProviderURLGenerator $paymentProviderURLGenerator, MembershipApplication $application, MembershipApplicationTokens $tokens ): string {
		$name = $application->getApplicant()->getName();
		return $paymentProviderURLGenerator->generateURL(
			new RequestContext(
				$application->getId(),
				$this->generatePayPalInvoiceId( $application ),
				$tokens->getUpdateToken(),
				$tokens->getAccessToken(),
				$name->getFirstName(),
				$name->getLastName(),
			)
		);
	}

	private function newApplicationFromRequest( ApplyForMembershipRequest $request, int $paymentId ): MembershipApplication {
		return ( new MembershipApplicationBuilder( $this->incentiveFinder ) )->newApplicationFromRequest(
			$request,
			$this->idGenerator->generateNewMembershipId(),
			$paymentId
		);
	}

	// TODO remove prepending the M in the FunFunFactory

	/**
	 * We use the membership primary key as the InvoiceId because they're unique
	 * But we prepend a letter to make sure they don't clash with donations
	 *
	 * @param MembershipApplication $application
	 *
	 * @return string
	 */
	private function generatePayPalInvoiceId( MembershipApplication $application ): string {
		return 'M' . $application->getId();
	}

}
