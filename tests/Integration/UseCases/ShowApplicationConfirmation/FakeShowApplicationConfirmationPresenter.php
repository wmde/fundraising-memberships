<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\ShowApplicationConfirmation;

use RuntimeException;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipTracking;
use WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation\ShowApplicationConfirmationPresenter;

class FakeShowApplicationConfirmationPresenter implements ShowApplicationConfirmationPresenter {

	private ?MembershipApplication $application = null;
	/**
	 * @var array<string, mixed>
	 */
	private array $paymentData;
	private MembershipTracking $tracking;
	private bool $anonymizedResponseWasShown = false;
	private bool $accessViolationWasShown = false;
	private string $shownTechnicalError;

	public function presentConfirmation( MembershipApplication $application, array $paymentData, MembershipTracking $tracking ): void {
		if ( $this->application !== null ) {
			throw new RuntimeException( 'Presenter should only be invoked once' );
		}

		$this->application = $application;
		$this->paymentData = $paymentData;
		$this->tracking = $tracking;
	}

	public function getShownApplication(): ?MembershipApplication {
		return $this->application;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getShownPaymentData(): array {
		return $this->paymentData;
	}

	public function getShownTracking(): string {
		return $this->tracking->__toString();
	}

	public function anonymizedResponseWasShown(): bool {
		return $this->anonymizedResponseWasShown;
	}

	public function presentAccessViolation(): void {
		$this->accessViolationWasShown = true;
	}

	public function accessViolationWasShown(): bool {
		return $this->accessViolationWasShown;
	}

	public function presentTechnicalError( string $message ): void {
		$this->shownTechnicalError = $message;
	}

	public function getShownTechnicalError(): string {
		return $this->shownTechnicalError;
	}

}
