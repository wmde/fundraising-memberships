<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Gedmo\Timestampable\TimestampableListener;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\Authorization\RandomMembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipApplicationPrePersistSubscriber;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MembershipContextFactory implements ServiceProviderInterface {

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

	/**
	 * @var Container
	 */
	private $pimple;

	private $addDoctrineSubscribers = true;

	public function __construct( array $config, Configuration $doctrineConfig ) {
		$this->config = $config;
		$this->doctrineConfig = $doctrineConfig;

		$this->pimple = $this->newPimple();
	}

	private function newPimple(): Container {
		$pimple = new Container();
		$this->register( $pimple );
		return $pimple;
	}

	public function register( Container $container ): void {
		$container['dbal_connection'] = function() {
			return DriverManager::getConnection( $this->config['db'] );
		};

		$container['entity_manager'] = function() {
			AnnotationRegistry::registerLoader( 'class_exists' );

			$this->doctrineConfig->setMetadataDriverImpl( $this->newMappingDriver() );

			$eventManager = $this->setupEventSubscribers(
				array_merge( $this->newEventSubscribers(), $this->newDoctrineEventSubscribers() )
			);

			return EntityManager::create( $this->getConnection(), $this->getDoctrineConfig(), $eventManager );
		};

		$container['fundraising.membership.application.token_generator'] = function() {
			return new RandomMembershipTokenGenerator(
				$this->config['token-length'],
				new \DateInterval( $this->config['token-validity-timestamp'] )
			);
		};

		$container['fundraising.membership.application.authorizer.class'] = DoctrineApplicationAuthorizer::class;
		// @todo Consider if the tokens should not better be method parameters (see ApplicationAuthorizer interface)
		$container['fundraising.membership.application.authorizer.update_token'] = null;
		$container['fundraising.membership.application.authorizer.access_token'] = null;

		$container['fundraising.membership.application.authorizer'] = $container->factory( function ( Container $container ): ApplicationAuthorizer {
			return new $container['fundraising.membership.application.authorizer.class'](
				$container['entity_manager'],
				$container['fundraising.membership.application.authorizer.update_token'],
				$container['fundraising.membership.application.authorizer.access_token']
			);
		} );
	}

	public function newMappingDriver(): MappingDriver {
		// We're only calling this for the side effect of adding Mapping/Driver/DoctrineAnnotations.php
		// to the AnnotationRegistry. When AnnotationRegistry is deprecated with Doctrine Annotations 2.0,
		// instantiate AnnotationReader directly instead.
		return $this->doctrineConfig->newDefaultAnnotationDriver( self::ENTITY_PATHS, false );
	}

	public function getConnection(): Connection {
		return $this->pimple['dbal_connection'];
	}

	public function getEntityManager(): EntityManager {
		return $this->pimple['entity_manager'];
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
		return $this->pimple['fundraising.membership.application.token_generator'];
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
