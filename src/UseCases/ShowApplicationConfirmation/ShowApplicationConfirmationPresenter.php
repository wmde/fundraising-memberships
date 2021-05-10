<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface ShowApplicationConfirmationPresenter {

	public function presentConfirmation( MembershipApplication $application, string $updateToken ): void;

	public function presentApplicationWasAnonymized(): void;

	public function presentAccessViolation(): void;

	public function presentTechnicalError( string $message ): void;

}
