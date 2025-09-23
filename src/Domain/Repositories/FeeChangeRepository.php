<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChange;

interface FeeChangeRepository {
	public function feeChangeExists( string $uuid ): bool;

	public function getFeeChange( string $uuid ): FeeChange;

	public function storeFeeChange( FeeChange $feeChange ): void;
}
