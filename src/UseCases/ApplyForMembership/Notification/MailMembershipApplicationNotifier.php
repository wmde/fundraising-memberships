<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification;

use WMDE\EmailAddress\EmailAddress;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

class MailMembershipApplicationNotifier implements MembershipNotifier {

	public function __construct(
		private readonly TemplateMailerInterface $confirmationMailer,
		private readonly TemplateMailerInterface $adminMailer,
		private readonly GetPaymentUseCase $paymentRetriever,
		private readonly string $adminEmailAddress
	) {
	}

	public function sendConfirmationFor( MembershipApplication $application ): void {
		$recipientAddress = $application->getApplicant()->getEmailAddress();
		$this->confirmationMailer->sendMail(
			$recipientAddress,
			$this->getTemplateArguments( $application )
		);
	}

	private function getTemplateArguments( MembershipApplication $application ): ApplyForMembershipTemplateArguments {
		$paymentValues = $this->paymentRetriever->getPaymentDataArray( $application->getPaymentId() );

		$incentives = [];
		/* @var Incentive $incentive */
		foreach ( $application->getIncentives() as $incentive ) {
			$incentives[] = $incentive->getName();
		}
		return new ApplyForMembershipTemplateArguments(
			id: $application->getId(),
			membershipType: $application->getType(),
			membershipFee: Euro::newFromCents( intval( $paymentValues['amount'] ) )->getEuroString(),
			membershipFeeInCents: intval( $paymentValues['amount'] ),
			paymentIntervalInMonths: intval( $paymentValues['interval'] ),
			paymentType: strval( $paymentValues['paymentType'] ),
			salutation: $application->getApplicant()->getName()->salutation,
			title: $application->getApplicant()->getName()->title,
			lastName: $application->getApplicant()->getName()->lastName,
			firstName: $application->getApplicant()->getName()->firstName,
			hasReceiptEnabled: $application->getDonationReceipt() ?? false,
			incentives: $incentives,
			moderationFlags: $this->getModerationFlags( ...$application->getModerationReasons() ),
		);
	}

	/**
	 * Ignores source field of the moderation reason because it won't get passed to users
	 * @param ModerationReason ...$getModerationReasons
	 * @return array<string,boolean>
	 */
	private function getModerationFlags( ModerationReason ...$getModerationReasons ): array {
		$result = [];
		foreach ( $getModerationReasons as $reason ) {
			$reasonName = $reason->getModerationIdentifier()->name;
			$result[$reasonName] = true;
		}
		return $result;
	}

	public function sendModerationNotificationToAdmin( MembershipApplication $application ): void {
		$importantReasons = array_filter(
			$application->getModerationReasons(),
			fn ( $moderationReason ) => $moderationReason->getModerationIdentifier() === ModerationIdentifier::MEMBERSHIP_FEE_TOO_HIGH
		);
		if ( count( $importantReasons ) === 0 ) {
			return;
		}
		$this->adminMailer->sendMail(
			new EmailAddress( $this->adminEmailAddress ),
			$this->getTemplateArguments( $application )
		);
	}
}
