<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\FunValidators\Validators\TextPolicyValidator;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class ApplyForMembershipPolicyValidator {

	private const YEARLY_PAYMENT_MODERATION_THRESHOLD_IN_EURO = 1000;

	private $textPolicyValidator;
	private $emailAddressBlacklist;

	public function __construct( TextPolicyValidator $textPolicyValidator, array $emailAddressBlacklist = [] ) {
		$this->textPolicyValidator = $textPolicyValidator;
		$this->emailAddressBlacklist = $emailAddressBlacklist;
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
