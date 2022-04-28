<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\RefactoringException;

class MailTemplateValueBuilder {

	public function buildValuesForTemplate( MembershipApplication $application ): array {
		// phpcs:disable Squiz.PHP.NonExecutableCode.Unreachable
		throw new RefactoringException( 'The ApplyForMembershipUseCase needs to pass the Payment type' );
		$incentives = [];
		/* @var Incentive $incentive */
		foreach ( $application->getIncentives() as $incentive ) {
			$incentives[] = $incentive->getName();
		}
		return [
			'membershipType' => $application->getType(),
			'membershipFee' => $application->getPayment()->getAmount()->getEuroString(),
			'paymentIntervalInMonths' => $application->getPayment()->getInterval()->value,
			'paymentType' => $application->getPayment()->getPaymentMethod()->getId(),
			'salutation' => $application->getApplicant()->getName()->getSalutation(),
			'title' => $application->getApplicant()->getName()->getTitle(),
			'lastName' => $application->getApplicant()->getName()->getLastName(),
			'firstName' => $application->getApplicant()->getName()->getFirstName(),
			'hasReceiptEnabled' => $application->getDonationReceipt(),
			'incentives' => $incentives
		];
	}

}
