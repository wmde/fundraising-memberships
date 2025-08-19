<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChange;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChangeState;

class FeeChanges {
	public const string UUID_1 = '07ddc43d-e184-46b3-b4ad-5550ef0f9450';
	public const string UUID_2 = 'cfee7df4-8252-4142-8c2c-f67692fd6af3';
	public const string UUID_3 = '7afcba26-44c0-4a56-8654-334f6a821464';
	public const string UUID_4 = 'e76569bf-f73b-4a7c-bb40-bcb5a3ba6abc';
	public const int PAYMENT_ID = 1;
	public const int EXTERNAL_MEMBER_ID = 12345678;
	public const int AMOUNT = 4200;
	public const int SUGGESTED_AMOUNT = 6400;
	public const int INTERVAL = 12;
	public const string PAYMENT_TYPE = 'FCH';
	public const string EXPORT_DATE = '2025-04-03 1:02:00';

	public static function newNewFeeChange( string $uuid ): FeeChange {
		return new FeeChange(
			$uuid,
			self::PAYMENT_ID,
			self::EXTERNAL_MEMBER_ID,
			self::AMOUNT,
			self::SUGGESTED_AMOUNT,
			self::INTERVAL,
			FeeChangeState::NEW,
			null
		);
	}

	public static function newFilledFeeChange( string $uuid ): FeeChange {
		return new FeeChange(
			$uuid,
			self::PAYMENT_ID,
			self::EXTERNAL_MEMBER_ID,
			self::AMOUNT,
			self::SUGGESTED_AMOUNT,
			self::INTERVAL,
			FeeChangeState::FILLED,
			null
		);
	}

	public static function newExportedFeeChange( string $uuid ): FeeChange {
		return new FeeChange(
			$uuid,
			self::PAYMENT_ID,
			self::EXTERNAL_MEMBER_ID,
			self::AMOUNT,
			self::SUGGESTED_AMOUNT,
			self::INTERVAL,
			FeeChangeState::EXPORTED,
			new \DateTime( self::EXPORT_DATE )
		);
	}
}
