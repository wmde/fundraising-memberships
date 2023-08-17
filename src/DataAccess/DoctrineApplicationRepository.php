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
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

/**
 * @license GPL-2.0-or-later
 */
class DoctrineApplicationRepository implements ApplicationRepository {

	private DoctrineApplicationTable $table;
	private GetPaymentUseCase $getPaymentUseCase;
	private ModerationReasonRepository $moderationReasonRepository;

	public function __construct( EntityManager $entityManager, GetPaymentUseCase $getPaymentUseCase, ModerationReasonRepository $moderationReasonRepository ) {
		$this->table = new DoctrineApplicationTable( $entityManager, new NullLogger() );
		$this->getPaymentUseCase = $getPaymentUseCase;
		$this->moderationReasonRepository = $moderationReasonRepository;
	}

	public function storeApplication( MembershipApplication $application ): void {
		// Doctrine will persist the moderation reasons that are not yet found in the database
		// and create relation entries to the membership application automatically
		$existingModerationReasons = $this->moderationReasonRepository->getModerationReasonsThatAreAlreadyPersisted(
			...$application->getModerationReasons()
		);

		$doctrineApplication = $this->table->getApplicationOrNullById( $application->getId() ) ?? new DoctrineApplication();
		$this->updateDoctrineApplication( $doctrineApplication, $application, $existingModerationReasons );
		$this->table->persistApplication( $doctrineApplication );
	}

	private function updateDoctrineApplication( DoctrineApplication $doctrineApplication, MembershipApplication $application, array $existingModerationReasons ): void {
		$converter = new DomainToLegacyConverter();
		$converter->convert(
			$doctrineApplication,
			$application,
			$this->getPaymentUseCase->getLegacyPaymentDataObject( $application->getPaymentId() ),
			$existingModerationReasons
		);
	}

	/**
	 * @param int $id
	 *
	 * @return MembershipApplication|null
	 * @throws GetMembershipApplicationException
	 */
	public function getUnexportedMembershipApplicationById( int $id ): ?MembershipApplication {
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
