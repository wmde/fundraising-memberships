<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Moderation;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest;
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

	public function moderateMembershipApplicationRequest( ApplyForMembershipRequest $request ): ModerationResult {
		$this->result = new ModerationResult();

		// TODO add moderation reasons to the result

		// $this->getAmountViolations( $request );
		//		$this->getBadWordViolations( $request );
		return $this->result;
	}

	public function needsModeration( MembershipApplication $application ): bool {
		return $this->yearlyAmountExceedsLimit( $application ) ||
			$this->addressContainsBadWords( $application );
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

	private function addressContainsBadWords( MembershipApplication $application ): bool {
		$applicant = $application->getApplicant();
		$harmless = $this->textPolicyValidator->textIsHarmless( $applicant->getName()->getFirstName() ) &&
			$this->textPolicyValidator->textIsHarmless( $applicant->getName()->getLastName() ) &&
			$this->textPolicyValidator->textIsHarmless( $applicant->getName()->getCompanyName() ) &&
			$this->textPolicyValidator->textIsHarmless( $applicant->getPhysicalAddress()->getCity() ) &&
			$this->textPolicyValidator->textIsHarmless( $applicant->getPhysicalAddress()->getStreetAddress() );
		return !$harmless;
	}
}
