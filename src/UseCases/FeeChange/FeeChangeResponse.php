<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\FeeChange;

class FeeChangeResponse {

	/**
	 * @param bool $success
	 * @param array<string,string> $validationResult Source name and error message pairs.
	 */
	public function __construct(
		public readonly bool $success,
		public readonly array $validationResult = []
	) {
	}
}
