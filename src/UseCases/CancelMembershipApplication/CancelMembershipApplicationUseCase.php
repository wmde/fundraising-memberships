<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogger;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\CancelPaymentUseCase;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CancelMembershipApplicationUseCase {

	public const LOG_MESSAGE_FRONTEND_STATUS_CHANGE = 'frontend: cancellation';
	public const LOG_MESSAGE_ADMIN_STATUS_CHANGE = 'cancelled by user: %s';

	public function __construct(
		private readonly ApplicationAuthorizer $authorizer,
		private readonly ApplicationRepository $repository,
		private readonly TemplateMailerInterface $mailer,
		private readonly MembershipApplicationEventLogger $membershipApplicationEventLogger,
		private readonly CancelPaymentUseCase $cancelPaymentUseCase
	) {
	}

	public function cancelApplication( CancellationRequest $request ): CancellationResponse {
		$membershipApplicationId = $request->getApplicationId();
		if ( !$this->authorizer->canModifyApplication( $membershipApplicationId ) ) {
			return CancellationResponse::newFailureResponse( $membershipApplicationId );
		}

		$application = $this->getApplicationById( $membershipApplicationId );

		if ( $application === null ) {
			return CancellationResponse::newFailureResponse( $membershipApplicationId );
		}

		if ( !$application->isCancelled() ) {
			$application->cancel();
			try {
				$this->repository->storeApplication( $application );
			}
			catch ( StoreMembershipApplicationException $ex ) {
				return CancellationResponse::newFailureResponse( $membershipApplicationId );
			}
			if ( !$request->initiatedByApplicant() ) {
				$this->sendConfirmationEmail( $application );
			}
		}

		$this->membershipApplicationEventLogger->log( $membershipApplicationId, $this->getLogMessage( $request ) );

		return CancellationResponse::newSuccessResponse( $membershipApplicationId );
	}

	public function getLogMessage( CancellationRequest $cancellationRequest ): string {
		if ( $cancellationRequest->initiatedByApplicant() ) {
			return sprintf( self::LOG_MESSAGE_ADMIN_STATUS_CHANGE, $cancellationRequest->getUserName() );
		}
		return self::LOG_MESSAGE_FRONTEND_STATUS_CHANGE;
	}

	private function getApplicationById( int $id ): ?MembershipApplication {
		try {
			return $this->repository->getApplicationById( $id );
		}
		catch ( GetMembershipApplicationException $ex ) {
			return null;
		}
	}

	private function sendConfirmationEmail( MembershipApplication $application ): void {
		$this->mailer->sendMail(
			$application->getApplicant()->getEmailAddress(),
			$this->getConfirmationMailTemplateArguments( $application )
		);
	}

	private function getConfirmationMailTemplateArguments( MembershipApplication $application ): array {
		return [
			'applicationId' => $application->getId(),
			'membershipApplicant' => [
				'salutation' => $application->getApplicant()->getName()->getSalutation(),
				'title' => $application->getApplicant()->getName()->getTitle(),
				'lastName' => $application->getApplicant()->getName()->getLastName()
			]
		];
	}

}
