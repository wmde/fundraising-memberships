<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication;

use WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\Domain\Event\MembershipUpdatedEvent;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\MembershipContext\EventEmitter;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification\MembershipNotifier;

class UpdateMembershipApplicationUseCase {

	public function __construct(
		private readonly MembershipAuthorizationChecker $authorizationService,
		private readonly UpdateMembershipApplicationValidator $updateMembershipApplicationValidator,
		private readonly MembershipRepository $membershipRepository,
		private readonly MembershipNotifier $membershipConfirmationMailer,
		private readonly EventEmitter $eventEmitter
	) {
	}

	public function updateMembershipApplication( UpdateMembershipApplicationRequest $updateMembershipRequest ): UpdateMembershipApplicationResponse {
		$application = $this->membershipRepository->getMembershipApplicationById(
			$updateMembershipRequest->getMembershipId()
		);

		if ( $application === null ) {
			return UpdateMembershipApplicationResponse::newFailureResponse(
				UpdateMembershipApplicationResponse::ERROR_MEMBERSHIP_APPLICATION_NOT_FOUND
			);
		}

		if ( $application->isCancelled() ) {
			return UpdateMembershipApplicationResponse::newFailureResponse(
				UpdateMembershipApplicationResponse::ERROR_MEMBERSHIP_APPLICATION_NOT_FOUND
			);
		}

		if ( $application->isExported() ) {
			return UpdateMembershipApplicationResponse::newFailureResponse(
				UpdateMembershipApplicationResponse::ERROR_MEMBERSHIP_APPLICATION_IS_EXPORTED
			);
		}

		$validationResult = $this->updateMembershipApplicationValidator->validateMembershipApplicationData( $updateMembershipRequest );

		if ( $validationResult->hasViolations() ) {
			return UpdateMembershipApplicationResponse::newFailureResponse(
				UpdateMembershipApplicationResponse::ERROR_VALIDATION_FAILED
			);
		}

		if ( !$this->authorizationService->canModifyMembership( $updateMembershipRequest->getMembershipId() ) ) {
			return UpdateMembershipApplicationResponse::newFailureResponse(
				UpdateMembershipApplicationResponse::ERROR_ACCESS_DENIED
			);
		}

		$currentApplicant = $application->getApplicant();

		$updatedApplicant = $this->getMembershipApplicantFromRequest(
			$updateMembershipRequest,
			$currentApplicant
		);

		$application->updateApplicant( $updatedApplicant );

		$this->membershipRepository->storeApplication( $application );

		$this->eventEmitter->emit( new MembershipUpdatedEvent( $application->getId(), $currentApplicant, $updatedApplicant ) );

		$this->membershipConfirmationMailer->sendConfirmationFor( $application );

		return UpdateMembershipApplicationResponse::newSuccessResponse();
	}

	private function getApplicantNameFromRequest( UpdateMembershipApplicationRequest $request ): ApplicantName {
		if ( $request->isCompanyApplication() ) {
			return ApplicantName::newCompanyName(
				$request->getCompanyName()
			);
		}

		return ApplicantName::newPrivatePersonName(
			$request->getSalutation(),
			$request->getTitle(),
			$request->getFirstName(),
			$request->getLastName()
		);
	}

	private function getApplicantAddressFromRequest(
		UpdateMembershipApplicationRequest $request
	): ApplicantAddress {
		return new ApplicantAddress(
			$request->getStreetAddress(),
			$request->getPostalCode(),
			$request->getCity(),
			$request->getCountryCode()
		);
	}

	private function getMembershipApplicantFromRequest(
		UpdateMembershipApplicationRequest $request,
		Applicant $currentApplicant
	): Applicant {
		return new Applicant(
			$this->getApplicantNameFromRequest( $request ),
			$this->getApplicantAddressFromRequest( $request ),
			$request->getEmailAddress(),
			$currentApplicant->getPhoneNumber(),
			$currentApplicant->getDateOfBirth()
		);
	}
}
