<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\Domain\Model\Application;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CancelMembershipApplicationUseCase {

	private $authorizer;
	private $repository;
	private $mailer;

	public function __construct( ApplicationAuthorizer $authorizer,
		ApplicationRepository $repository, TemplateMailerInterface $mailer ) {
		$this->authorizer = $authorizer;
		$this->repository = $repository;
		$this->mailer = $mailer;
	}

	public function cancelApplication( CancellationRequest $request ): CancellationResponse {
		if ( !$this->authorizer->canModifyApplication( $request->getApplicationId() ) ) {
			return $this->newFailureResponse( $request );
		}

		$application = $this->getApplicationById( $request->getApplicationId() );

		if ( $application === null ) {
			return $this->newFailureResponse( $request );
		}

		if ( !$application->isCancelled() ) {
			$application->cancel();
			try {
				$this->repository->storeApplication( $application );
			}
			catch ( StoreMembershipApplicationException $ex ) {
				return $this->newFailureResponse( $request );
			}
			$this->sendConfirmationEmail( $application );
		}

		return $this->newSuccessResponse( $request );
	}

	private function getApplicationById( int $id ): ?Application {
		try {
			return $this->repository->getApplicationById( $id );
		}
		catch ( GetMembershipApplicationException $ex ) {
			return null;
		}
	}

	private function newFailureResponse( CancellationRequest $request ): CancellationResponse {
		return new CancellationResponse( $request->getApplicationId(), CancellationResponse::IS_FAILURE );
	}

	private function newSuccessResponse( CancellationRequest $request ): CancellationResponse {
		return new CancellationResponse( $request->getApplicationId(), CancellationResponse::IS_SUCCESS );
	}

	private function sendConfirmationEmail( Application $application ): void {
		$this->mailer->sendMail(
			$application->getApplicant()->getEmailAddress(),
			$this->getConfirmationMailTemplateArguments( $application )
		);
	}

	private function getConfirmationMailTemplateArguments( Application $application ): array {
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
