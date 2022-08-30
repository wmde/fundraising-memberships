<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\MembershipApplicationData;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;

class DomainToLegacyConverter {
	public function convert( DoctrineApplication $doctrineApplication, MembershipApplication $application, LegacyPaymentData $paymentData, array $existingModerationReasons ): void {
		$doctrineApplication->setId( $application->getId() );
		$doctrineApplication->setMembershipType( $application->getType() );
		$doctrineApplication->setPaymentId( $application->getPaymentId() );

		$this->setApplicantFields( $doctrineApplication, $application->getApplicant() );
		$this->setPaymentFields( $doctrineApplication, $paymentData );
		$doctrineApplication->setIncentives( $this->convertIncentives( $application->getIncentives() ) );
		$doctrineApplication->setDonationReceipt( $application->getDonationReceipt() );
		$doctrineApplication->setModerationReasons(
			...$this->mergeModerationReasons( $existingModerationReasons,
			$application->getModerationReasons() )
		);

		$doctrineStatus = $this->getDoctrineStatus( $application );
		$this->preserveDoctrineStatus( $doctrineApplication, $doctrineStatus );
		$doctrineApplication->setStatus( $doctrineStatus );
	}

	/**
	 * @param ModerationReason[] $existingModerationReasons
	 * @param ModerationReason[] $moderationReasonsFromApplication
	 * @return ModerationReason[]
	 */
	private function mergeModerationReasons( array $existingModerationReasons, array $moderationReasonsFromApplication ): array {
		$resultArray = [];
		foreach ( $moderationReasonsFromApplication as $moderationReason ) {
			$resultArray[(string)$moderationReason] = $moderationReason;
		}
		foreach ( $existingModerationReasons as $moderationReason ) {
			$id = (string)$moderationReason;
			if ( isset( $resultArray[$id] ) ) {
				$resultArray[$id] = $moderationReason;
			}
		}
		return array_values( $resultArray );
	}

	private function convertIncentives( \Traversable $incentives ): Collection {
		$incentiveCollection = new ArrayCollection();
		foreach ( $incentives as $incentive ) {
			$incentiveCollection->add( $incentive );
		}
		return $incentiveCollection;
	}

	private function setApplicantFields( DoctrineApplication $application, Applicant $applicant ): void {
		$application->setApplicantFirstName( $applicant->getName()->getFirstName() );
		$application->setApplicantLastName( $applicant->getName()->getLastName() );
		$application->setApplicantSalutation( $applicant->getName()->getSalutation() );
		$application->setApplicantTitle( $applicant->getName()->getTitle() );
		$application->setCompany( $applicant->getName()->getCompanyName() );

		$application->setApplicantDateOfBirth( $applicant->getDateOfBirth() );

		$application->setApplicantEmailAddress( $applicant->getEmailAddress()->getFullAddress() );
		$application->setApplicantPhoneNumber( $applicant->getPhoneNumber()->__toString() );

		$address = $applicant->getPhysicalAddress();

		$application->setCity( $address->getCity() );
		$application->setCountry( $address->getCountryCode() );
		$application->setPostcode( $address->getPostalCode() );
		$application->setAddress( $address->getStreetAddress() );
	}

	private function setPaymentFields( DoctrineApplication $application, LegacyPaymentData $paymentdata ): void {
		$application->setPaymentIntervalInMonths( $paymentdata->intervalInMonths );
		$application->setPaymentAmount( Euro::newFromCents( $paymentdata->amountInEuroCents )->getEuros() );
		if ( !empty( $paymentdata->paymentSpecificValues['iban'] ) ) {
			$this->setBankDataFields( $application, $paymentdata->paymentSpecificValues );
		} else {
			$application->encodeAndSetData(
				array_merge(
					$application->getDecodedData(),
					$paymentdata->paymentSpecificValues
				)
			);
		}
	}

	private function setBankDataFields( DoctrineApplication $application, array $paymentSpecificValues ): void {
		$application->setPaymentBankAccount( $paymentSpecificValues['konto'] ?? '' );
		$application->setPaymentBankCode( $paymentSpecificValues['blz'] ?? '' );
		$application->setPaymentBankName( $paymentSpecificValues['bankname'] ?? '' );
		$application->setPaymentBic( $paymentSpecificValues['bic'] ?? '' );
		$application->setPaymentIban( $paymentSpecificValues['iban'] ?? '' );
	}

	private function getDoctrineStatus( MembershipApplication $application ): int {
		if ( $application->isMarkedForModeration() && $application->isCancelled() ) {
			return DoctrineApplication::STATUS_CANCELLED_MODERATION;
		}

		if ( $application->isMarkedForModeration() ) {
			return DoctrineApplication::STATUS_MODERATION;
		}

		if ( $application->isCancelled() ) {
			return DoctrineApplication::STATUS_CANCELED;
		}

		if ( $application->isConfirmed() ) {
			return DoctrineApplication::STATUS_CONFIRMED;
		}

		return DoctrineApplication::STATUS_NEUTRAL;
	}

	private function preserveDoctrineStatus( DoctrineApplication $doctrineApplication, int $doctrineStatus ): void {
		if ( $doctrineStatus < DoctrineApplication::STATUS_CONFIRMED ) {
			$doctrineApplication->modifyDataObject( static function ( MembershipApplicationData $data ): void {
				$data->setPreservedStatus( DoctrineApplication::STATUS_CONFIRMED );
			} );
		}
	}
}
