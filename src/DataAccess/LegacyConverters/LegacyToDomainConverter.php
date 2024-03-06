<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters;

use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\PhoneNumber;

class LegacyToDomainConverter {
	public function createFromLegacyObject( DoctrineApplication $doctrineApplication ): MembershipApplication {
		$application = new MembershipApplication(
			is_int( $doctrineApplication->getId() ) ? $doctrineApplication->getId() : 0,
			$doctrineApplication->getMembershipType(),
			new Applicant(
				$this->newPersonName( $doctrineApplication ),
				$this->newAddress( $doctrineApplication ),
				new EmailAddress( $doctrineApplication->getApplicantEmailAddress() ),
				new PhoneNumber( $doctrineApplication->getApplicantPhoneNumber() ),
				$doctrineApplication->getApplicantDateOfBirth()
			),
			$doctrineApplication->getPaymentId(),
			$doctrineApplication->getDonationReceipt()
		);

		if ( !$doctrineApplication->getModerationReasons()->isEmpty() ) {
			$application->markForModeration( ...$doctrineApplication->getModerationReasons()->toArray() );
		}

		if ( $doctrineApplication->isCancelled() ) {
			$application->cancel();
		}

		if ( $doctrineApplication->isConfirmed() ) {
			$application->confirm();
		}

		if ( $doctrineApplication->getExport() != null ) {
			$application->setExported();
		}

		foreach ( $doctrineApplication->getIncentives() as $incentive ) {
			$application->addIncentive( $incentive );
		}
		return $application;
	}

	private function newPersonName( DoctrineApplication $application ): ApplicantName {
		if ( empty( $application->getCompany() ) ) {
			$personName = ApplicantName::newPrivatePersonName();
			$personName->setFirstName( $application->getApplicantFirstName() );
			$personName->setLastName( $application->getApplicantLastName() );
			$personName->setSalutation( is_string( $application->getApplicantSalutation() ) ? $application->getApplicantSalutation() : '' );
			$personName->setTitle( is_string( $application->getApplicantTitle() ) ? $application->getApplicantTitle() : '' );
		} else {
			$personName = ApplicantName::newCompanyName();
			$personName->setCompanyName( $application->getCompany() );
			$personName->setSalutation( is_string( $application->getApplicantSalutation() ) ? $application->getApplicantSalutation() : '' );
		}

		return $personName->freeze()->assertNoNullFields();
	}

	private function newAddress( DoctrineApplication $application ): ApplicantAddress {
		return new ApplicantAddress(
			streetAddress: $application->getAddress() ?? '',
			postalCode: $application->getPostcode() ?? '',
			city: $application->getCity() ?? '',
			countryCode: $application->getCountry()
		);
	}
}
