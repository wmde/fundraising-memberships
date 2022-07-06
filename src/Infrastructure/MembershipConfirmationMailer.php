<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Infrastructure;

use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

class MembershipConfirmationMailer implements MembershipNotifier {

	public function __construct(
		private TemplateMailerInterface $mailer,
		private GetPaymentUseCase $getPaymentUseCase
	) {
	}

	public function sendMailFor( MembershipApplication $application ): void {
		$recipientAddress = $application->getApplicant()->getEmailAddress();
		$this->mailer->sendMail(
			$recipientAddress,
			$this->getTemplateValues( $application )
		);
	}

	private function getTemplateValues( MembershipApplication $application ): array {
		$paymentDataArray = $this->getPaymentUseCase->getPaymentDataArray( $application->getPaymentId() );

		$incentives = [];
		/* @var Incentive $incentive */
		foreach ( $application->getIncentives() as $incentive ) {
			$incentives[] = $incentive->getName();
		}
		return [
			'membershipType' => $application->getType(),
			'membershipFee' => Euro::newFromCents( $paymentDataArray['amount'] )->getEuroString(),
			'paymentIntervalInMonths' => $paymentDataArray['interval'],
			'paymentType' => $paymentDataArray['paymentType'],
			'salutation' => $application->getApplicant()->getName()->getSalutation(),
			'title' => $application->getApplicant()->getName()->getTitle(),
			'lastName' => $application->getApplicant()->getName()->getLastName(),
			'firstName' => $application->getApplicant()->getName()->getFirstName(),
			'hasReceiptEnabled' => $application->getDonationReceipt(),
			'incentives' => $incentives
		];
	}
}
