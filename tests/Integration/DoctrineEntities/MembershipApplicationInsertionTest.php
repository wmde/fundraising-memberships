<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DoctrineEntities;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication
 */
class MembershipApplicationInsertionTest extends TestCase {

	private const DUMMY_PAYMENT_ID = 1;

	public function testNewMembershipApplicationCanBeInserted(): void {
		$entityManager = TestEnvironment::newInstance()->getEntityManager();
		$membership = new MembershipApplication();
		$membership->setPaymentId( self::DUMMY_PAYMENT_ID );
		$entityManager->persist( $membership );
		$entityManager->flush();

		$count = $entityManager->createQueryBuilder()
			->select( 'COUNT(r.id)' )
			->from( MembershipApplication::class, 'r' )
			->getQuery()
			->getSingleScalarResult();

		$this->assertSame( 1, $count );
	}

}
