<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\DataAccess\IncentiveFinder;
use WMDE\Fundraising\MembershipContext\Domain\Event\MembershipCreatedEvent;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\EventEmitter;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationPiwikTracker;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationTracker;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Moderation\ModerationService;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentMethod;
use WMDE\Fundraising\PaymentContext\Domain\PaymentDelayCalculator;

/**
 * @license GPL-2.0-or-later
 */
class ApplyForMembershipUseCase {

	private ApplicationRepository $repository;
	private ApplicationTokenFetcher $tokenFetcher;
	private TemplateMailerInterface $mailer;
	private MembershipApplicationValidator $validator;
	private ModerationService $policyValidator;
	/**
	 * @var ApplicationTracker
	 * @deprecated See https://phabricator.wikimedia.org/T197112
	 */
	private ApplicationTracker $membershipApplicationTracker;
	private ApplicationPiwikTracker $piwikTracker;
	private PaymentDelayCalculator $paymentDelayCalculator;
	private EventEmitter $eventEmitter;
	private IncentiveFinder $incentiveFinder;

	public function __construct( ApplicationRepository $repository,
		ApplicationTokenFetcher $tokenFetcher,
		TemplateMailerInterface $mailer,
		MembershipApplicationValidator $validator,
		ModerationService $policyValidator,
		ApplicationTracker $tracker,
		ApplicationPiwikTracker $piwikTracker,
		PaymentDelayCalculator $paymentDelayCalculator,
		EventEmitter $eventEmitter,
		IncentiveFinder $incentiveFinder ) {
		$this->repository = $repository;
		$this->tokenFetcher = $tokenFetcher;
		$this->mailer = $mailer;
		$this->validator = $validator;
		$this->policyValidator = $policyValidator;
		$this->membershipApplicationTracker = $tracker;
		$this->piwikTracker = $piwikTracker;
		$this->paymentDelayCalculator = $paymentDelayCalculator;
		$this->eventEmitter = $eventEmitter;
		$this->incentiveFinder = $incentiveFinder;
	}

	public function applyForMembership( ApplyForMembershipRequest $request ): ApplyForMembershipResponse {
		$validationResult = $this->validator->validate( $request );
		if ( !$validationResult->isSuccessful() ) {
			// TODO: return failures (note that we have infrastructure failures that are not ConstraintViolations)
			return ApplyForMembershipResponse::newFailureResponse( $validationResult );
		}

		$application = $this->newApplicationFromRequest( $request );

		$moderationResult = $this->policyValidator->moderateMembershipApplicationRequest( $application );

		if ( $moderationResult->needsModeration() ) {
			$application->markForModeration( ...$moderationResult->getViolations() );
		}

		if ( $this->policyValidator->isAutoDeleted( $application ) ) {
			$application->cancel();
		}

		$application->notifyOfFirstPaymentDate( $this->paymentDelayCalculator->calculateFirstPaymentDate()->format( 'Y-m-d' ) );

		// TODO: handle exceptions
		$this->repository->storeApplication( $application );

		$this->eventEmitter->emit( new MembershipCreatedEvent( $application->getId(), $application->getApplicant() ) );

		// TODO: handle exceptions
		$this->membershipApplicationTracker->trackApplication( $application->getId(), $request->getTrackingInfo() );

		// TODO: handle exceptions
		$this->piwikTracker->trackApplication( $application->getId(), $request->getPiwikTrackingString() );

		if ( $this->isAutoConfirmed( $application ) ) {
			$this->sendConfirmationEmail( $application );
		}

		// TODO: handle exceptions
		$tokens = $this->tokenFetcher->getTokens( $application->getId() );

		return ApplyForMembershipResponse::newSuccessResponse(
			$tokens->getAccessToken(),
			$tokens->getUpdateToken(),
			$application
		);
	}

	private function newApplicationFromRequest( ApplyForMembershipRequest $request ): MembershipApplication {
		return ( new MembershipApplicationBuilder( $this->incentiveFinder ) )->newApplicationFromRequest( $request );
	}

	private function sendConfirmationEmail( MembershipApplication $application ): void {
		$this->mailer->sendMail(
			$application->getApplicant()->getEmailAddress(),
			( new MailTemplateValueBuilder() )->buildValuesForTemplate( $application )
		);
	}

	public function isAutoConfirmed( MembershipApplication $application ): bool {
		return $application->getPayment()->getPaymentMethod()->getId() === PaymentMethod::DIRECT_DEBIT;
	}
}
