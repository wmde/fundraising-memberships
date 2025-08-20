<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\FeeChange;

interface ShowFeeChangePresenter {
	public function showFeeChangeForm( int $externalMemberId, int $currentAmountInCents, int $suggestedAmountInCents, int $currentInterval ): void;

	public function showFeeChangeError(): void;

	public function showFeeChangeAlreadyFilled(): void;
}
