<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\LegacyConverters;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\LegacyToDomainConverter;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\LegacyToDomainConverter
 */
class LegacyToDomainConverterTest extends TestCase {
	public function testGivenDoctrineApplicationWithModerationAndCancelled_domainEntityHasFlags(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_CANCELED + DoctrineApplication::STATUS_MODERATION );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->needsModeration() );
		$this->assertTrue( $application->isCancelled() );
	}

	public function testGivenDoctrineApplicationWithModerationFlag_domainEntityHasFlag(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_MODERATION );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->needsModeration() );
		$this->assertFalse( $application->isCancelled() );
	}

	public function testGivenDoctrineApplicationWithCancelledFlag_domainEntityHasFlag(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_CANCELED );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertFalse( $application->needsModeration() );
		$this->assertTrue( $application->isCancelled() );
	}

	public function testGivenDoctrineApplication_domainEntityHasCorrectPaymentId(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertEquals( $doctrineApplication->getPaymentId(), $application->getPaymentId() );
	}

	public function testGivenCompanyDoctrineApplication_setsCompanyFieldsInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineCompanyEntity();

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->getApplicant()->isCompany() );
		$this->assertEquals( $doctrineApplication->getCompany(), $application->getApplicant()->getName()->getCompanyName() );
		$this->assertEquals( $doctrineApplication->getApplicantSalutation(), $application->getApplicant()->getName()->getSalutation() );
	}

	public function testGivenPersonDoctrineApplication_setsPersonFieldsInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->getApplicant()->isPrivatePerson() );
		$this->assertEquals( $doctrineApplication->getApplicantFirstName(), $application->getApplicant()->getName()->getFirstName() );
		$this->assertEquals( $doctrineApplication->getApplicantLastName(), $application->getApplicant()->getName()->getLastName() );
		$this->assertEquals( $doctrineApplication->getApplicantSalutation(), $application->getApplicant()->getName()->getSalutation() );
		$this->assertEquals( $doctrineApplication->getApplicantTitle(), $application->getApplicant()->getName()->getTitle() );
	}

	public function testGivenDoctrineApplicationThatNeedsModeration_setsNeedsModerationInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_MODERATION );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->needsModeration() );
	}

	public function testGivenConfirmedDoctrineApplication_setsConfirmedInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_CONFIRMED );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->isConfirmed() );
	}

	public function testGivenCancelledDoctrineApplication_setsCancelledInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_CANCELED );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->isCancelled() );
	}

	public function testGivenExportedDoctrineApplication_setsExportedInDomain(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setExport( new \DateTime() );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertTrue( $application->isExported() );
	}

	public function testDoctrineApplicationWithIncentives_setsIncentivesInDomain(): void {
		$incentives = [ new Incentive( 'gold' ) ];

		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setIncentives( new ArrayCollection( $incentives ) );

		$converter = new LegacyToDomainConverter();
		$application = $converter->createFromLegacyObject( $doctrineApplication );

		$this->assertEquals( $incentives, iterator_to_array( $application->getIncentives() ) );
	}
}
