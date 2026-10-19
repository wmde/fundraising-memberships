<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Output\OutputInterface;
use WMDE\Clock\Clock;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\LegacyToDomainConverter;
use WMDE\Fundraising\MembershipContext\Domain\MembershipAnonymizer;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentAnonymizer;

class DoctrineMembershipAnonymizer implements MembershipAnonymizer {

	private const int BATCH_SIZE = 20;

	public function __construct(
		private readonly MembershipRepository $membershipRepository,
		private readonly EntityManager $entityManager,
		private readonly PaymentAnonymizer $paymentAnonymizer,
		private readonly Clock $clock,
		private readonly \DateInterval $gracePeriod,
		private readonly OutputInterface $output
	) {
	}

	public function anonymizeAll(): int {
		$cutoffDate = $this->clock->now()->sub( $this->gracePeriod );

		$queryBuilder = $this->entityManager->createQueryBuilder();
		$queryBuilder->select( 'm' )
			->from( MembershipApplication::class, 'm' )
			->andWhere( 'm.isScrubbed = 0' )
			->andWhere( $queryBuilder->expr()->orX(
				$queryBuilder->expr()->isNotNull( 'm.export' ),
				$queryBuilder->expr()->in( 'm.status', [
					strval( MembershipApplication::STATUS_CANCELED ),
					strval( MembershipApplication::STATUS_CANCELLED_MODERATION )
				] ),
				$queryBuilder->expr()->lte( 'm.creationTime', ':cutoffDate' )
			) )
			->setParameter( 'cutoffDate', $cutoffDate, Types::DATETIME_IMMUTABLE );

		$count = 0;
		$paymentIds = [];
		/** @var iterable<MembershipApplication> $memberships */
		$memberships = $queryBuilder->getQuery()->toIterable();
		$converter = new LegacyToDomainConverter();

		foreach ( $memberships as $doctrineMembership ) {
			try {
				$membership = $converter->createFromLegacyObject( $doctrineMembership );
				$membership->scrubPersonalData( $cutoffDate );
				$this->membershipRepository->storeApplication( $membership );
				$paymentIds[] = $membership->getPaymentId();
				$count++;
			} catch ( \Exception $e ) {
				$this->output->writeln( "Failed to anonymize membership id: {$doctrineMembership->getId()}" );
				$this->output->writeln( $e->getMessage() );
			}

			if ( $count % self::BATCH_SIZE === 0 ) {
				$this->entityManager->flush();
				$this->entityManager->clear();
			}
		}

		$this->paymentAnonymizer->anonymizeWithIds( ...$paymentIds );

		return $count;
	}

	public function anonymizeWithIds( int ...$membershipIds ): void {
		$cutoffDate = $this->clock->now()->sub( $this->gracePeriod );

		$counter = 0;
		$paymentIds = [];
		foreach ( $membershipIds as $id ) {
			$membership = $this->membershipRepository->getMembershipApplicationById( $id );

			if ( $membership === null ) {
				$this->output->writeln( "Failed to find membership id: {$id}" );
				continue;
			}

			try {
				$membership->scrubPersonalData( $cutoffDate );
				$this->membershipRepository->storeApplication( $membership );
				$paymentIds[] = $membership->getPaymentId();

				$counter++;
				if ( $counter % self::BATCH_SIZE === 0 ) {
					$this->entityManager->flush();
					$this->entityManager->clear();
				}
			} catch ( \Exception $e ) {
				$this->output->writeln( "Failed to anonymize membership id: {$id}" );
				$this->output->writeln( $e->getMessage() );
			}
		}

		$this->paymentAnonymizer->anonymizeWithIds( ...$paymentIds );
	}
}
