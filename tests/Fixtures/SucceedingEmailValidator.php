<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use WMDE\FunValidators\DomainNameValidator;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\EmailValidator;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SucceedingEmailValidator extends EmailValidator {

	public function __construct() {
		parent::__construct(
			new class() implements DomainNameValidator {
				public function isValid( string $domain ): bool {
					return true;
				}
			}
		);
	}

	public function validate( string $emailAddress ): ValidationResult {	// @codingStandardsIgnoreLine
		return new ValidationResult();
	}

}
