<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipApplicationTokens;
use WMDE\Fundraising\MembershipContext\DataAccess\IncentiveFinder;
use WMDE\Fundraising\MembershipContext\Domain\Event\MembershipCreatedEvent;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\EventEmitter;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipNotifier;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationPiwikTracker;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationTracker;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;

class ApplyForMembershipUseCase {

	public function __construct(
		private ApplicationRepository $repository,
		private ApplicationTokenFetcher $tokenFetcher,
		private MembershipNotifier $mailNotifier,
		private MembershipApplicationValidator $validator,
		private ApplyForMembershipPolicyValidator $policyValidator,
		/**
		 * @var ApplicationTracker
		 * @deprecated See https://phabricator.wikimedia.org/T197112
		 */
		private ApplicationTracker $membershipApplicationTracker,
		private ApplicationPiwikTracker $piwikTracker,
		private EventEmitter $eventEmitter,
		private IncentiveFinder $incentiveFinder,
		private CreatePaymentUseCase $createPaymentUseCase ) {
	}

	public function applyForMembership( ApplyForMembershipRequest $request ): ApplyForMembershipResponse {
		$validationResult = $this->validator->validate( $request );
		if ( !$validationResult->isSuccessful() ) {
			// TODO: return failures (note that we have infrastructure failures that are not ConstraintViolations)
			return ApplyForMembershipResponse::newFailureResponse( $validationResult );
		}

		$paymentCreationRequest = $request->getPaymentCreationRequest();
		$applicantType = $request->isCompanyApplication() ? ApplicantType::COMPANY_APPLICANT : ApplicantType::PERSON_APPLICANT;
		$paymentCreationRequest->setDomainSpecificPaymentValidator( new MembershipPaymentValidator( $applicantType ) );

		$paymentCreationResponse = $this->createPaymentUseCase->createPayment( $request->getPaymentCreationRequest() );
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

		$membershipFee = $request->getPaymentCreationRequest()->amountInEuroCents;
		$paymentInterval = $request->getPaymentCreationRequest()->interval;
		if ( $this->policyValidator->needsModeration( $application, $membershipFee, $paymentInterval ) ) {
			$application->markForModeration();
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
			$this->mailNotifier->sendMailFor( $application );
		}

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
