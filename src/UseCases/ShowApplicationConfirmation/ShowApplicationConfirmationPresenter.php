<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;

interface ShowApplicationConfirmationPresenter {

	/**
	 * @param MembershipApplication $application
	 * @param array<string,mixed> $paymentData
	 * @param string $tracking
	 *
	 * @return void
	 */
	public function presentConfirmation( MembershipApplication $application, array $paymentData, string $tracking ): void;

	public function presentApplicationWasAnonymized(): void;

	public function presentAccessViolation(): void;

	public function presentTechnicalError( string $message ): void;

}
