<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Gedmo\Timestampable\TimestampableListener;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\Authorization\RandomMembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipApplicationPrePersistSubscriber;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MembershipContextFactory {

	/**
	 * Use this constant for MappingDriverChain::addDriver
	 */
	public const DOCTRINE_ADDITIONAL_ENTITIES = [
		'WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities' => __DIR__ . '/DoctrineEntities'
	];

	public const ENTITY_PATHS = [
		__DIR__ . '/DataAccess/DoctrineEntities/'
	];

	private array $config;
	private Configuration $doctrineConfig;

	private $addDoctrineSubscribers = true;

	private ?Connection $connection;
	private ?EntityManager $entityManager;
	private ?RandomMembershipTokenGenerator $tokenGenerator;

	public function __construct( array $config, Configuration $doctrineConfig ) {
		$this->config = $config;
		$this->doctrineConfig = $doctrineConfig;

		$this->connection = null;
		$this->entityManager = null;
		$this->tokenGenerator = null;
	}

	public function newMappingDriver(): MappingDriver {
		// We're only calling this for the side effect of adding Mapping/Driver/DoctrineAnnotations.php
		// to the AnnotationRegistry. When AnnotationRegistry is deprecated with Doctrine Annotations 2.0,
		// instantiate AnnotationReader directly instead.
		return $this->doctrineConfig->newDefaultAnnotationDriver( self::ENTITY_PATHS, false );
	}

	public function getConnection(): Connection {
		if( $this->connection === null ) {
			$this->connection = DriverManager::getConnection( $this->config['db'] );
		}

		return $this->connection;
	}

	public function getEntityManager(): EntityManager {
		if( $this->entityManager === null ) {
			AnnotationRegistry::registerLoader( 'class_exists' );

			$this->doctrineConfig->setMetadataDriverImpl( $this->newMappingDriver() );

			$eventManager = $this->setupEventSubscribers(
				array_merge( $this->newEventSubscribers(), $this->newDoctrineEventSubscribers() )
			);

			$this->entityManager = EntityManager::create( $this->getConnection(), $this->getDoctrineConfig(), $eventManager );
		}

		return $this->entityManager;
	}

	private function getDoctrineConfig(): Configuration {
		return $this->doctrineConfig;
	}

	/**
	 * @return EventSubscriber[]
	 */
	public function newDoctrineEventSubscribers(): array {
		if ( !$this->addDoctrineSubscribers ) {
			return [];
		}
		return array_merge(
			$this->newEventSubscribers(),
			[
				DoctrineMembershipApplicationPrePersistSubscriber::class => $this->newDoctrineMembershipPrePersistSubscriber()
			]
		);
	}

	private function newDoctrineMembershipPrePersistSubscriber(): DoctrineMembershipApplicationPrePersistSubscriber {
		$tokenGenerator = $this->getTokenGenerator();
		return new DoctrineMembershipApplicationPrePersistSubscriber(
			$tokenGenerator,
			$tokenGenerator
		);
	}

	public function getTokenGenerator(): MembershipTokenGenerator {
		if( $this->tokenGenerator === null ) {
			$this->tokenGenerator = new RandomMembershipTokenGenerator(
				$this->config['token-length'],
				new \DateInterval( $this->config['token-validity-timestamp'] )
			);
		}

		return $this->tokenGenerator;
	}

	public function disableDoctrineSubscribers(): void {
		$this->addDoctrineSubscribers = false;
	}

	private function setupEventSubscribers( array $eventSubscribers ): EventManager {
		$eventManager = $this->getConnection()->getEventManager();
		foreach ( $eventSubscribers as $eventSubscriber ) {
			$eventManager->addEventSubscriber( $eventSubscriber );
		}
		return $eventManager;
	}

	public function newEventSubscribers(): array {
		$timestampableListener = new TimestampableListener();
		$timestampableListener->setAnnotationReader( new AnnotationReader() );
		return [
			TimestampableListener::class => $timestampableListener
		];
	}

}
