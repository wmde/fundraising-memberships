<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use Psr\Log\NullLogger;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\DomainToLegacyConverter;
use WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\LegacyToDomainConverter;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationAnonymizedException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

/**
 * @license GPL-2.0-or-later
 */
class DoctrineApplicationRepository implements ApplicationRepository {

	private DoctrineApplicationTable $table;
	private GetPaymentUseCase $getPaymentUseCase;

	public function __construct( EntityManager $entityManager, GetPaymentUseCase $getPaymentUseCase ) {
		$this->table = new DoctrineApplicationTable( $entityManager, new NullLogger() );
		$this->getPaymentUseCase = $getPaymentUseCase;
	}

	public function storeApplication( MembershipApplication $application ): void {
		if ( $application->hasId() ) {
			$this->updateApplication( $application );
		} else {
			$this->insertApplication( $application );
		}
	}

	private function insertApplication( MembershipApplication $application ): void {
		$doctrineApplication = new DoctrineApplication();
		$this->updateDoctrineApplication( $doctrineApplication, $application );
		$this->table->persistApplication( $doctrineApplication );

		$application->assignId( $doctrineApplication->getId() );
	}

	private function updateApplication( MembershipApplication $application ): void {
		try {
			$this->table->modifyApplication(
				$application->getId(),
				function ( DoctrineApplication $doctrineApplication ) use ( $application ) {
					$this->updateDoctrineApplication( $doctrineApplication, $application );
				}
			);
		}
		catch ( GetMembershipApplicationException | StoreMembershipApplicationException $ex ) {
			throw new StoreMembershipApplicationException( null, $ex );
		}
	}

	private function updateDoctrineApplication( DoctrineApplication $doctrineApplication, MembershipApplication $application ): void {
		$converter = new DomainToLegacyConverter();
		$converter->convert(
			$doctrineApplication,
			$application,
			$this->getPaymentUseCase->getLegacyPaymentDataObject( $application->getPaymentId() )
		);
	}

	/**
	 * @param int $id
	 *
	 * @return MembershipApplication|null
	 * @throws GetMembershipApplicationException
	 */
	public function getApplicationById( int $id ): ?MembershipApplication {
		$application = $this->table->getApplicationOrNullById( $id );

		if ( $application === null ) {
			return null;
		}

		if ( $application->getBackup() !== null ) {
			throw new ApplicationAnonymizedException();
		}

		$converter = new LegacyToDomainConverter();
		return $converter->createFromLegacyObject( $application );
	}
}
