<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipIdGenerator;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipId;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipIdGenerator;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipIdGenerator
 */
class DoctrineMembershipIdGeneratorTest extends TestCase {

	private EntityManager $entityManager;

	public function setUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$this->entityManager = $factory->getEntityManager();
	}

	public function testWhenMembershipIdTableIsEmpty_throwsException(): void {
		$this->expectException( \RuntimeException::class );

		$this->makeIdGenerator()->generateNewMembershipId();
	}

	public function testWhenGetNextId_getsNextId(): void {
		$this->whenMembershipIdIs( 4 );
		$this->assertEquals( 5, $this->makeIdGenerator()->generateNewMembershipId() );
	}

	private function makeIdGenerator(): MembershipIdGenerator {
		return new DoctrineMembershipIdGenerator( $this->entityManager );
	}

	private function whenMembershipIdIs( int $membershipId ): void {
		$this->entityManager->persist( new MembershipId( $membershipId ) );
		$this->entityManager->flush();
	}
}
