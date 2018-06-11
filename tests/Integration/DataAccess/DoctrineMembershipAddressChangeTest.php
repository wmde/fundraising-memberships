<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;
use WMDE\Fundraising\Entities\MembershipApplication;

/**
 * @covers WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipApplicationPrePersistSubscriber
 */
class DoctrineMembershipAddressChangeTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var EntityManager
	 */
	private $entityManager;

	public function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getFactory()->getEntityManager();
	}

	/**
	 * @slowThreshold 400
	 */
	public function testWhenApplicationIsCreated_addressChangeUuidIsStored(): void {
		$application = new MembershipApplication();
		$this->assertNull( $application->getAddressChange() );
		$this->entityManager->persist( $application );
		$this->entityManager->flush();
		$this->assertNotNull( $application->getAddressChange() );

		/** @var MembershipApplication $persistedApplication */
		$persistedApplication = $this->entityManager->find( MembershipApplication::class, 1 );
		$this->assertSame(
			$application->getAddressChange()->getCurrentIdentifier(),
			$persistedApplication->getAddressChange()->getCurrentIdentifier()
		);
	}

	/**
	 * @slowThreshold 400
	 */
	public function testWhenAddressIsUpdated_addressChangeUuidIsUpdated(): void {
		$application = new MembershipApplication();

		$this->entityManager->persist( $application );
		$this->entityManager->flush();

		$oldId = $application->getAddressChange()->getCurrentIdentifier();

		/** @var MembershipApplication $persistedApplication */
		$persistedApplication = $this->entityManager->find( MembershipApplication::class, 1 );
		$persistedApplication->getAddressChange()->updateAddressIdentifier();

		$this->assertNotSame( $oldId, $persistedApplication->getAddressChange()->getCurrentIdentifier() );
		$this->assertSame( $oldId, $persistedApplication->getAddressChange()->getPreviousIdentifier() );
	}
}
