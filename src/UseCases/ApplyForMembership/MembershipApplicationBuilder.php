<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use DateTime;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\DataAccess\Exception\UnknownIncentive;
use WMDE\Fundraising\MembershipContext\DataAccess\IncentiveFinder;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\PhoneNumber;

class MembershipApplicationBuilder {

	public function __construct(
		private readonly IncentiveFinder $incentiveFinder,
	) {
	}

	public function newApplicationFromRequest( ApplyForMembershipRequest $request, int $membershipId, int $paymentId ): MembershipApplication {
		$application = new MembershipApplication(
			$membershipId,
			$request->getMembershipType(),
			$this->newApplicant( $request ),
			$paymentId,
			$request->getOptsIntoDonationReceipt()
		);
		$this->addIncentives( $application, $request );
		return $application;
	}

	private function newApplicant( ApplyForMembershipRequest $request ): Applicant {
		return new Applicant(
			$this->newPersonName( $request ),
			$this->newAddress( $request ),
			new EmailAddress( $request->getApplicantEmailAddress() ),
			new PhoneNumber( $request->getApplicantPhoneNumber() ),
			( $request->getApplicantDateOfBirth() === '' ) ? null : new DateTime( $request->getApplicantDateOfBirth() )
		);
	}

	private function newPersonName( ApplyForMembershipRequest $request ): ApplicantName {
		if ( $request->isCompanyApplication() ) {
			return $this->newCompanyPersonName( $request );
		} else {
			return $this->newPrivatePersonName( $request );
		}
	}

	private function newPrivatePersonName( ApplyForMembershipRequest $request ): ApplicantName {
		return ApplicantName::newPrivatePersonName(
			$request->getApplicantSalutation(),
			$request->getApplicantTitle(),
			$request->getApplicantFirstName(),
			$request->getApplicantLastName()
		);
	}

	private function newCompanyPersonName( ApplyForMembershipRequest $request ): ApplicantName {
		return ApplicantName::newCompanyName( $request->getApplicantCompanyName() );
	}

	private function newAddress( ApplyForMembershipRequest $request ): ApplicantAddress {
		return new ApplicantAddress(
			streetAddress: $request->getApplicantStreetAddress(),
			postalCode: $request->getApplicantPostalCode(),
			city: $request->getApplicantCity(),
			countryCode: $request->getApplicantCountryCode()
		);
	}

	private function addIncentives( MembershipApplication $application, ApplyForMembershipRequest $request ): void {
		foreach ( $request->getIncentives() as $incentiveName ) {
			$foundIncentive = $this->incentiveFinder->findIncentiveByName( $incentiveName );
			if ( $foundIncentive === null ) {
				throw new UnknownIncentive( sprintf( 'Incentive "%s" not found', $incentiveName ) );
			}
			$application->addIncentive( $foundIncentive );
		}
	}

}
