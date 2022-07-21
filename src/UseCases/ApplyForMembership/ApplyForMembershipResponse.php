<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;

class ApplyForMembershipResponse {

	private ApplicationValidationResult $validationResult;

	private ?string $accessToken;
	private ?string $updateToken;
	private ?MembershipApplication $application;
	private ?string $paymentProviderRedirectUrl = null;

	public static function newSuccessResponse(
			string $accessToken,
			string $updateToken,
			MembershipApplication $application,
			?string $paymentProviderRedirectUrl ): self {
		$response = new self( new ApplicationValidationResult() );
		$response->accessToken = $accessToken;
		$response->updateToken = $updateToken;
		$response->application = $application;
		$response->paymentProviderRedirectUrl = $paymentProviderRedirectUrl;
		return $response;
	}

	public static function newFailureResponse( ApplicationValidationResult $validationResult ): self {
		return new self( $validationResult );
	}

	private function __construct( ApplicationValidationResult $validationResult ) {
		$this->validationResult = $validationResult;
	}

	public function isSuccessful(): bool {
		return $this->validationResult->isSuccessful();
	}

	public function getAccessToken(): string {
		if ( !$this->isSuccessful() ) {
			throw new \RuntimeException( 'The result only has an access token when successful' );
		}

		return $this->accessToken;
	}

	public function getUpdateToken(): string {
		if ( !$this->isSuccessful() ) {
			throw new \RuntimeException( 'The result only has an update token when successful' );
		}

		return $this->updateToken;
	}

	/**
	 * WARNING: we're returning the domain object to not have to create a  more verbose response model.
	 * Keep in mind that you should not use domain logic in the presenter, or put presentation helpers
	 * in the domain object!
	 *
	 * @return MembershipApplication
	 */
	public function getMembershipApplication(): MembershipApplication {
		if ( !$this->isSuccessful() ) {
			throw new \RuntimeException( 'The result only has a membership application object when successful' );
		}

		return $this->application;
	}

	public function getValidationResult(): ApplicationValidationResult {
		return $this->validationResult;
	}

	public function getPaymentProviderRedirectUrl(): ?string {
		return $this->paymentProviderRedirectUrl;
	}
}
