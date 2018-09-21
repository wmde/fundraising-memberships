<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee;

class ValidateFeeResult {

	public const ERROR_TOO_LOW = 'error-too-low';
	public const ERROR_NOT_MONEY = 'error-not-money';

	private $errorCode;

	public static function newSuccessResponse(): self {
		return new self();
	}

	public static function newNotMoneyResponse(): self {
		return self::newErrorResponse( self::ERROR_NOT_MONEY );
	}

	public static function newTooLowResponse(): self {
		return self::newErrorResponse( self::ERROR_TOO_LOW );
	}

	private static function newErrorResponse( string $errorCode ): self {
		$result = new self();
		$result->errorCode = $errorCode;
		return $result;
	}

	private function __construct() {
	}

	public function isSuccessful(): bool {
		return $this->errorCode === null;
	}

	public function getErrorCode(): ?string {
		return $this->errorCode;
	}

}