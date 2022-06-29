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

	private TextPolicyValidator $textPolicyValidator;
	/**
	 * @var string[]
	 */
	private array $emailAddressBlacklist;
	private ModerationResult $result;

	public function __construct( TextPolicyValidator $textPolicyValidator, array $emailAddressBlacklist = [] ) {
		$this->textPolicyValidator = $textPolicyValidator;
		$this->emailAddressBlacklist = $emailAddressBlacklist;
	}

	public function moderateMembershipApplicationRequest( MembershipApplication $application ): ModerationResult {
		$this->result = new ModerationResult();

		$this->getAmountViolations( $application );
		$this->getBadWordViolations( $application );
		return $this->result;
	}

	private function getAmountViolations( MembershipApplication $application ): void {
		if ( $this->yearlyAmountExceedsLimit( $application ) ) {
			$this->result->addModerationReason(
				new ModerationReason(
					ModerationIdentifier::MEMBERSHIP_FEE_TOO_HIGH,
					ApplicationValidationResult::SOURCE_PAYMENT_AMOUNT
				)
			);
		}
	}

	public function isAutoDeleted( MembershipApplication $application ): bool {
		foreach ( $this->emailAddressBlacklist as $blacklistEntry ) {
			if ( preg_match( $blacklistEntry, $application->getApplicant()->getEmailAddress()->getFullAddress() ) ) {
				return true;
			}
		}

		return false;
	}

	private function yearlyAmountExceedsLimit( MembershipApplication $application ): bool {
		return $application->getPayment()->getYearlyAmount()->getEuroFloat()
			> self::YEARLY_PAYMENT_MODERATION_THRESHOLD_IN_EURO;
	}

	private function getBadWordViolations( MembershipApplication $application ): void {
		$applicant = $application->getApplicant();
		$this->getPolicyViolationsForField( $applicant->getName()->getFirstName(), ApplicationValidationResult::SOURCE_APPLICANT_FIRST_NAME );
		$this->getPolicyViolationsForField( $applicant->getName()->getLastName(), ApplicationValidationResult::SOURCE_APPLICANT_LAST_NAME );
		$this->getPolicyViolationsForField( $applicant->getName()->getCompanyName(), ApplicationValidationResult::SOURCE_APPLICANT_COMPANY );
		$this->getPolicyViolationsForField( $applicant->getPhysicalAddress()->getCity(), ApplicationValidationResult::SOURCE_APPLICANT_CITY );
		$this->getPolicyViolationsForField( $applicant->getPhysicalAddress()->getStreetAddress(), ApplicationValidationResult::SOURCE_APPLICANT_STREET_ADDRESS );
	}

	private function getPolicyViolationsForField( string $fieldContent, string $fieldName ): void {
		if ( $fieldContent === '' ) {
			return;
		}
		if ( $this->textPolicyValidator->textIsHarmless( $fieldContent ) ) {
			return;
		}
		$this->result->addModerationReason( new ModerationReason( ModerationIdentifier::ADDRESS_CONTENT_VIOLATION, $fieldName ) );
	}
}
