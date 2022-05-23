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
			$doctrineApplication->getId(),
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

		if ( $doctrineApplication->needsModeration() ) {
			$application->markForModeration();
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
			$personName->setSalutation( $application->getApplicantSalutation() );
			$personName->setTitle( $application->getApplicantTitle() );
		} else {
			$personName = ApplicantName::newCompanyName();
			$personName->setCompanyName( $application->getCompany() );
			$personName->setSalutation( $application->getApplicantSalutation() );
		}

		return $personName->freeze()->assertNoNullFields();
	}

	private function newAddress( DoctrineApplication $application ): ApplicantAddress {
		$address = new ApplicantAddress();

		$address->setCity( $application->getCity() );
		$address->setCountryCode( $application->getCountry() );
		$address->setPostalCode( $application->getPostcode() );
		$address->setStreetAddress( $application->getAddress() );

		return $address->freeze()->assertNoNullFields();
	}
}
