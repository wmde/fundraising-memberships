<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Moderation;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult;
use WMDE\FunValidators\Validators\TextPolicyValidator;

/**
 * This class is for checking if a membership application needs moderation.
 * It will be applied **after** the use case has created the application.
 *
 * Moderation reasons can be either membership fees that are too high (but still plausible) or text policy violations in
 * the email or postal address fields ("bad words", according to a deny- and allow-list).
 *
 * For forbidden email addresses, we immediately delete the membership application.
 */
class ModerationService {

	private const YEARLY_PAYMENT_MODERATION_THRESHOLD_IN_EURO = 1000;
	private const MONTHS_PER_YEAR = 12;

	private TextPolicyValidator $textPolicyValidator;
	/**
	 * @var string[]
	 */
	private array $emailAddressBlocklist;
	private ModerationResult $result;

	public function __construct( TextPolicyValidator $textPolicyValidator, array $emailAddressBlocklist = [] ) {
		$this->textPolicyValidator = $textPolicyValidator;
		$this->emailAddressBlocklist = $emailAddressBlocklist;
	}

	public function moderateMembershipApplicationRequest( MembershipApplication $application, int $amountInCents, int $interval ): ModerationResult {
		$this->result = new ModerationResult();

		$this->moderateAmountViolations( $amountInCents, $interval );
		$this->moderateBadWordViolations( $application );
		$this->moderateEmailBlockListViolations( $application );
		return $this->result;
	}

	private function moderateAmountViolations( int $amountInCents, int $interval ): void {
		if ( $this->yearlyAmountExceedsLimit( $amountInCents, $interval ) ) {
			$this->result->addModerationReason(
				new ModerationReason(
					ModerationIdentifier::MEMBERSHIP_FEE_TOO_HIGH,
					ApplicationValidationResult::SOURCE_PAYMENT_AMOUNT
				)
			);
		}
	}

	private function yearlyAmountExceedsLimit( int $amountInEuroCents, int $interval ): bool {
		$yearlyAmount = self::MONTHS_PER_YEAR / $interval * ( $amountInEuroCents / 100 );
		return $yearlyAmount > self::YEARLY_PAYMENT_MODERATION_THRESHOLD_IN_EURO;
	}

	public function isAutoDeleted( MembershipApplication $application ): bool {
		foreach ( $this->emailAddressBlocklist as $blocklistEntry ) {
			if ( preg_match( $blocklistEntry, $application->getApplicant()->getEmailAddress()->getFullAddress() ) ) {
				return true;
			}
		}

		return false;
	}

	private function moderateBadWordViolations( MembershipApplication $application ): void {
		$applicant = $application->getApplicant();
		$this->moderatePolicyViolationsForField( $applicant->getName()->getFirstName(), ApplicationValidationResult::SOURCE_APPLICANT_FIRST_NAME );
		$this->moderatePolicyViolationsForField( $applicant->getName()->getLastName(), ApplicationValidationResult::SOURCE_APPLICANT_LAST_NAME );
		$this->moderatePolicyViolationsForField( $applicant->getName()->getCompanyName(), ApplicationValidationResult::SOURCE_APPLICANT_COMPANY );
		$this->moderatePolicyViolationsForField( $applicant->getPhysicalAddress()->getCity(), ApplicationValidationResult::SOURCE_APPLICANT_CITY );
		$this->moderatePolicyViolationsForField( $applicant->getPhysicalAddress()->getStreetAddress(), ApplicationValidationResult::SOURCE_APPLICANT_STREET_ADDRESS );
	}

	private function moderatePolicyViolationsForField( string $fieldContent, string $fieldName ): void {
		if ( $fieldContent === '' ) {
			return;
		}
		if ( $this->textPolicyValidator->textIsHarmless( $fieldContent ) ) {
			return;
		}
		$this->result->addModerationReason( new ModerationReason( ModerationIdentifier::ADDRESS_CONTENT_VIOLATION, $fieldName ) );
	}

	private function moderateEmailBlockListViolations( MembershipApplication $application ): void {
		if ( in_array( $application->getApplicant()->getEmailAddress()->getFullAddress(), $this->emailAddressBlocklist ) ) {
			$this->result->addModerationReason( new ModerationReason(
				ModerationIdentifier::EMAIL_BLOCKED,
				ApplicationValidationResult::SOURCE_APPLICANT_EMAIL
			) );
		}
	}
}
