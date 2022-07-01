<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Gedmo\Timestampable\TimestampableListener;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\MembershipContextFactory;

class TestMembershipContextFactory {

	private array $config;
	private Configuration $doctrineConfig;
	private ?Connection $connection;
	private MembershipContextFactory $factory;
	private ?EntityManager $entityManager;

	public function __construct( array $config ) {
		$this->config = $config;
		$this->doctrineConfig = ORMSetup::createXMLMetadataConfiguration( [
			MembershipContextFactory::DOCTRINE_CLASS_MAPPING_DIRECTORY,
			MembershipContextFactory::DOMAIN_CLASS_MAPPING_DIRECTORY
		] );
		$this->factory = new MembershipContextFactory( $config );
		$this->entityManager = null;
		$this->connection = null;
	}

	public function getConnection(): Connection {
		if ( $this->connection === null ) {
			$this->connection = DriverManager::getConnection( $this->config['db'] );
			$this->factory->registerCustomTypes( $this->connection );
		}
		return $this->connection;
	}

	public function getEntityManager(): EntityManager {
		if ( $this->entityManager === null ) {
			$this->entityManager = $this->newEntityManager( $this->factory->newEventSubscribers() );
		}
		return $this->entityManager;
	}

	private function newEntityManager( array $eventSubscribers = [] ): EntityManager {
		$this->doctrineConfig->setMetadataDriverImpl( $this->factory->newMappingDriver() );

		$entityManager = EntityManager::create( $this->getConnection(), $this->doctrineConfig );

		$this->setupEventSubscribers( $entityManager->getEventManager(), $eventSubscribers );

		return $entityManager;
	}

	private function setupEventSubscribers( EventManager $eventManager, array $eventSubscribers ): void {
		foreach ( $eventSubscribers as $eventSubscriber ) {
			$eventManager->addEventSubscriber( $eventSubscriber );
		}
	}

	public function setTokenGenerator( MembershipTokenGenerator $tokenGenerator ) {
		$this->factory->setTokenGenerator( $tokenGenerator );
	}

	public function newSchemaCreator(): SchemaCreator {
		return new SchemaCreator( $this->newEntityManager( [
			TimestampableListener::class => $this->newTimestampableListener()
		] ) );
	}

	private function newTimestampableListener(): TimestampableListener {
		$timestampableListener = new TimestampableListener;
		$timestampableListener->setAnnotationReader( new AnnotationReader() );
		return $timestampableListener;
	}
}
