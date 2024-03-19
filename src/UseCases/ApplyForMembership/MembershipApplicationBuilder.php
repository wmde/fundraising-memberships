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
			$request->membershipType,
			$this->newApplicant( $request ),
			$paymentId,
			$request->optsIntoDonationReceipt
		);
		$this->addIncentives( $application, $request );
		return $application;
	}

	private function newApplicant( ApplyForMembershipRequest $request ): Applicant {
		return new Applicant(
			$this->newPersonName( $request ),
			$this->newAddress( $request ),
			new EmailAddress( $request->applicantEmailAddress ),
			new PhoneNumber( $request->applicantPhoneNumber ),
			( $request->applicantDateOfBirth === '' ) ? null : new DateTime( $request->applicantDateOfBirth )
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
			$request->applicantSalutation,
			$request->applicantTitle,
			$request->applicantFirstName,
			$request->applicantLastName
		);
	}

	private function newCompanyPersonName( ApplyForMembershipRequest $request ): ApplicantName {
		return ApplicantName::newCompanyName( $request->applicantCompanyName );
	}

	private function newAddress( ApplyForMembershipRequest $request ): ApplicantAddress {
		return new ApplicantAddress(
			streetAddress: $request->applicantStreetAddress,
			postalCode: $request->applicantPostalCode,
			city: $request->applicantCity,
			countryCode: $request->applicantCountryCode
		);
	}

	private function addIncentives( MembershipApplication $application, ApplyForMembershipRequest $request ): void {
		foreach ( $request->incentives as $incentiveName ) {
			$foundIncentive = $this->incentiveFinder->findIncentiveByName( $incentiveName );
			if ( $foundIncentive === null ) {
				throw new UnknownIncentive( sprintf( 'Incentive "%s" not found', $incentiveName ) );
			}
			$application->addIncentive( $foundIncentive );
		}
	}

}
