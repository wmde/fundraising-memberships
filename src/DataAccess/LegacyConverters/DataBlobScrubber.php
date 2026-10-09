<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters;

use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;

class DataBlobScrubber {
	/**
	 * We keep anrede for measuring gender
	 */
	private const array ALLOWED_DATA_FIELDS = [
		'old_status',
		'log'
	];

	public static function scrubAllPersonalData( MembershipApplication $application ): void {
		$blobData = $application->getDecodedData();

		$allowedData = [];
		foreach ( self::ALLOWED_DATA_FIELDS as $field ) {
			if ( isset( $blobData[ $field ] ) ) {
				$allowedData[ $field ] = $blobData[ $field ];
			}
		}

		$application->encodeAndSetData( $allowedData );
	}
}
