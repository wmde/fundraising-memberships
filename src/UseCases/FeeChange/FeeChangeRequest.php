<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\FeeChange;

class FeeChangeRequest {
	public function __construct(
		public readonly string $uuid,
		public readonly string $memberName,
		public readonly int $amountInEuroCents,
		public readonly string $paymentType,
		public readonly string $iban = '',
		public readonly string $bic = '',
	) {
	}
}
