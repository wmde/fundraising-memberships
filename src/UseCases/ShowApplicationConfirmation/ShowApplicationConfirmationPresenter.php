<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\MembershipContext\UseCases\ShowApplicationConfirmation;

/**
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface ShowApplicationConfirmationPresenter {

	// TODO: replace w/ presentConfirmation, presentAccessDenied, etc
	public function presentResponseModel( ShowApplicationConfirmationResponse $response ): void;

	public function presentApplicationWasPurged(): void;

	public function presentAccessViolation(): void;

}