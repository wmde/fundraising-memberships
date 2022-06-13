<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\FunValidators\Validators\TextPolicyValidator;

/**
 * @license GPL-2.0-or-later
 */
class ApplyForMembershipPolicyValidator {

	private const YEARLY_PAYMENT_MODERATION_THRESHOLD_IN_EURO = 1000;
	private const MONTHS_PER_YEAR = 12;

	private TextPolicyValidator $textPolicyValidator;
	private array $emailAddressBlacklist;

	public function __construct( TextPolicyValidator $textPolicyValidator, array $emailAddressBlacklist = [], ) {
		$this->textPolicyValidator = $textPolicyValidator;
		$this->emailAddressBlacklist = $emailAddressBlacklist;
	}

	public function needsModeration( MembershipApplication $application, int $amountInEuroCents, int $interval ): bool {
		return $this->yearlyAmountExceedsLimit( $amountInEuroCents, $interval ) ||
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

	private function yearlyAmountExceedsLimit( int $amountInEuroCents, int $interval ): bool {
		$yearlyAmount = self::MONTHS_PER_YEAR / $interval * ( $amountInEuroCents / 100 );
		return $yearlyAmount > self::YEARLY_PAYMENT_MODERATION_THRESHOLD_IN_EURO;
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
