<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

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
			( $request->getApplicantDateOfBirth() === '' ) ? null : new \DateTime( $request->getApplicantDateOfBirth() )
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
		$personName = ApplicantName::newPrivatePersonName();
		$personName->setFirstName( $request->getApplicantFirstName() );
		$personName->setLastName( $request->getApplicantLastName() );
		$personName->setSalutation( $request->getApplicantSalutation() );
		$personName->setTitle( $request->getApplicantTitle() );
		return $personName->freeze()->assertNoNullFields();
	}

	private function newCompanyPersonName( ApplyForMembershipRequest $request ): ApplicantName {
		$personName = ApplicantName::newCompanyName();
		$personName->setCompanyName( $request->getApplicantCompanyName() );
		return $personName->freeze()->assertNoNullFields();
	}

	private function newAddress( ApplyForMembershipRequest $request ): ApplicantAddress {
		$address = new ApplicantAddress();

		$address->setCity( $request->getApplicantCity() );
		$address->setCountryCode( $request->getApplicantCountryCode() );
		$address->setPostalCode( $request->getApplicantPostalCode() );
		$address->setStreetAddress( $request->getApplicantStreetAddress() );

		return $address->freeze()->assertNoNullFields();
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
