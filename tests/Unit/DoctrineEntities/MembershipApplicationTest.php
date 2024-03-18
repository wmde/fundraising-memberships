<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\DoctrineEntities;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\MembershipApplicationData;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\MembershipApplicationData
 */
class MembershipApplicationTest extends TestCase {

	public function testWhenSettingIdToAnInteger_getIdReturnsIt(): void {
		$application = new MembershipApplication();
		$application->setId( 1337 );

		$this->assertSame( 1337, $application->getId() );
	}

	public function testWhenSettingIdToNull_getIdReturnsNull(): void {
		$application = new MembershipApplication();
		$application->setId( 1337 );
		$application->setId( null );

		$this->assertNull( $application->getId() );
	}

	public function testWhenIdIsNotSet_getIdReturnsNull(): void {
		$application = new MembershipApplication();

		$this->assertNull( $application->getId() );
	}

	public function testGivenNoData_getDataObjectReturnsObjectWithNullValues(): void {
		$application = new MembershipApplication();

		$this->assertNull( $application->getDataObject()->getAccessToken() );
		$this->assertNull( $application->getDataObject()->getUpdateToken() );
		$this->assertNull( $application->getDataObject()->getPreservedStatus() );
	}

	public function testWhenProvidingData_setDataObjectSetsData(): void {
		$data = new MembershipApplicationData();
		$data->setAccessToken( 'foo' );
		$data->setUpdateToken( 'bar' );
		$data->setPreservedStatus( 1337 );

		$application = new MembershipApplication();
		$application->setDataObject( $data );

		$this->assertSame(
			[
				'token' => 'foo',
				'utoken' => 'bar',
				'old_status' => 1337,
			],
			$application->getDecodedData()
		);
	}

	public function testWhenProvidingNullData_setObjectDoesNotSetFields(): void {
		$application = new MembershipApplication();
		$application->setDataObject( new MembershipApplicationData() );

		$this->assertSame(
			[],
			$application->getDecodedData()
		);
	}

	public function testWhenDataAlreadyExists_setDataObjectRetainsAndUpdatesData(): void {
		$application = new MembershipApplication();
		$application->encodeAndSetData( [
			'nyan' => 'cat',
			'token' => 'wee',
			'pink' => 'fluffy',
		] );

		$data = new MembershipApplicationData();
		$data->setAccessToken( 'foo' );
		$data->setUpdateToken( 'bar' );

		$application->setDataObject( $data );

		$this->assertSame(
			[
				'nyan' => 'cat',
				'token' => 'foo',
				'pink' => 'fluffy',
				'utoken' => 'bar',
			],
			$application->getDecodedData()
		);
	}

	public function testWhenModifyingTheDataObject_modificationsAreReflected(): void {
		$application = new MembershipApplication();
		$application->encodeAndSetData( [
			'nyan' => 'cat',
			'token' => 'wee',
			'pink' => 'fluffy',
		] );

		$application->modifyDataObject( static function ( MembershipApplicationData $data ) {
			$data->setAccessToken( 'foo' );
			$data->setUpdateToken( 'bar' );
		} );

		$this->assertSame(
			[
				'nyan' => 'cat',
				'token' => 'foo',
				'pink' => 'fluffy',
				'utoken' => 'bar',
			],
			$application->getDecodedData()
		);
	}

	public function testStatusConstantsExist(): void {
		$this->assertNotNull( MembershipApplication::STATUS_MODERATION );
		$this->assertNotNull( MembershipApplication::STATUS_CANCELED );
		$this->assertNotNull( MembershipApplication::STATUS_CONFIRMED );
		$this->assertNotNull( MembershipApplication::STATUS_NEUTRAL );
	}

	public function testGivenModerationStatus_needsModerationReturnsTrue(): void {
		$application = new MembershipApplication();
		$application->setStatus( MembershipApplication::STATUS_MODERATION );

		$this->assertTrue( $application->needsModeration() );
	}

	public function testGivenDefaultStatus_needsModerationReturnsFalse(): void {
		$application = new MembershipApplication();

		$this->assertFalse( $application->needsModeration() );
	}

	public function testGivenModerationAndCancelledStatus_needsModerationReturnsTrue(): void {
		$application = new MembershipApplication();
		$application->setStatus(
			MembershipApplication::STATUS_MODERATION + MembershipApplication::STATUS_CANCELED
		);

		$this->assertTrue( $application->needsModeration() );
	}

	public function testGivenCancelledStatus_isCancelledReturnsTrue(): void {
		$application = new MembershipApplication();
		$application->setStatus( MembershipApplication::STATUS_CANCELED );

		$this->assertTrue( $application->isCancelled() );
	}

	public function testGivenDefaultStatus_isCancelledReturnsFalse(): void {
		$application = new MembershipApplication();

		$this->assertFalse( $application->isCancelled() );
	}

	public function testGivenModerationAndCancelledStatus_isCancelledReturnsTrue(): void {
		$application = new MembershipApplication();
		$application->setStatus(
			MembershipApplication::STATUS_MODERATION + MembershipApplication::STATUS_CANCELED
		);

		$this->assertTrue( $application->isCancelled() );
	}

	public function testGivenDefaultStatus_isDeletedReturnsFalse(): void {
		$application = new MembershipApplication();

		$this->assertFalse( $application->isCancelled() );
	}

	public function testDefaultDonationReceiptValue_isNull(): void {
		$application = new MembershipApplication();

		$this->assertNull( $application->getDonationReceipt() );
	}

	public function testSetDonationReceiptValue_canBeRetrieved(): void {
		$application = new MembershipApplication();
		$application->setDonationReceipt( false );

		$this->assertFalse( $application->getDonationReceipt() );
	}

}
