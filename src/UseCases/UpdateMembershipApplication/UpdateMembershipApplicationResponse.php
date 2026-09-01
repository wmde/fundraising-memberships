<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication;

class UpdateMembershipApplicationResponse {
	private string $errorMessage;
	public const ERROR_ACCESS_DENIED = 'membership_application_update_failure_access_denied';
	public const ERROR_MEMBERSHIP_APPLICATION_NOT_FOUND = 'membership_application_update_failure_not_found';
	public const ERROR_MEMBERSHIP_APPLICATION_IS_EXPORTED = 'membership_application_update_failure_exported';
	public const ERROR_VALIDATION_FAILED = 'membership_application_update_failure_validation_error';

	private function __construct( string $errorMessage = '' ) {
		$this->errorMessage = $errorMessage;
	}

	public static function newSuccessResponse(): self {
		return new self();
	}

	public static function newFailureResponse( string $errorMessage ): self {
		return new self( $errorMessage );
	}

	public function isSuccessful(): bool {
		return $this->errorMessage === '';
	}

	public function getErrorMessage(): string {
		return $this->errorMessage;
	}
}
