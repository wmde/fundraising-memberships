<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters;

use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\AnonymousEmailAddress;
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
				$this->newEmail( $doctrineApplication ),
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

		if ( $doctrineApplication->getBackup() != null ) {
			$application->setBackup();
		}

		foreach ( $doctrineApplication->getIncentives() as $incentive ) {
			$application->addIncentive( $incentive );
		}
		return $application;
	}

	private function newPersonName( DoctrineApplication $application ): ApplicantName {
		if ( empty( $application->getCompany() ) ) {
			return ApplicantName::newPrivatePersonName(
				$application->getApplicantSalutation() ?? '',
				$application->getApplicantTitle() ?? '',
				$application->getApplicantFirstName(),
				$application->getApplicantLastName()
			);
		} else {
			return ApplicantName::newCompanyName(
				$application->getCompany()
			);
		}
	}

	private function newAddress( DoctrineApplication $application ): ApplicantAddress {
		return new ApplicantAddress(
			streetAddress: $application->getAddress() ?? '',
			postalCode: $application->getPostcode() ?? '',
			city: $application->getCity() ?? '',
			countryCode: $application->getCountry()
		);
	}

	private function newEmail( DoctrineApplication $application ): EmailAddress {
		if ( $application->getExport() != null ) {
			return new AnonymousEmailAddress();
		}

		return new EmailAddress( $application->getApplicantEmailAddress() );
	}
}
