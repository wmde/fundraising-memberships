<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineIncentiveFinder;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\DoctrineIncentiveFinder
 */
class DoctrineIncentiveFinderTest extends TestCase {

	private const MISSING_INCENTIVE = 'Diamond-studded fountain pen';
	private const EXPECTED_INCENTIVE = 'Eternal Gratitude';

	private EntityManager $entityManager;

	protected function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getEntityManager();
	}

	public function testEmptyIncentiveTableReturnsNoIncentive(): void {
		$finder = new DoctrineIncentiveFinder( $this->entityManager );

		$incentive = $finder->findIncentiveByName( self::MISSING_INCENTIVE );

		$this->assertNull( $incentive );
	}

	public function testGivenNonMatchingName_finderReturnsNoIncentive(): void {
		$this->insertExpectedIncentive();
		$finder = new DoctrineIncentiveFinder( $this->entityManager );

		$incentive = $finder->findIncentiveByName( self::MISSING_INCENTIVE );

		$this->assertNull( $incentive );
	}

	public function testGivenMatchingName_finderReturnsIncentive(): void {
		$this->insertExpectedIncentive();
		$finder = new DoctrineIncentiveFinder( $this->entityManager );

		$incentive = $finder->findIncentiveByName( self::EXPECTED_INCENTIVE );

		$this->assertNotNull( $incentive );
		$this->assertSame( self::EXPECTED_INCENTIVE, $incentive->getName() );
	}

	private function insertExpectedIncentive(): void {
		$this->entityManager->persist( new Incentive( self::EXPECTED_INCENTIVE ) );
		$this->entityManager->flush();
	}

}
