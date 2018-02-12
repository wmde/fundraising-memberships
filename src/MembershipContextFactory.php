<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\Authorization\RandomMembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipApplicationPrePersistSubscriber;
use WMDE\Fundraising\Store\Factory as StoreFactory;
use WMDE\Fundraising\Store\Installer;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MembershipContextFactory implements ServiceProviderInterface {

	private $config;

	/**
	 * @var Container
	 */
	private $pimple;

	private $addDoctrineSubscribers = true;

	public function __construct( array $config ) {
		$this->config = $config;
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
			$entityManager = ( new StoreFactory( $this->getConnection(), $this->getVarPath() . '/doctrine_proxies' ) )
				->getEntityManager();
			if ( $this->addDoctrineSubscribers ) {
				$entityManager->getEventManager()->addEventSubscriber(
					$this->newDoctrineMembershipPrePersistSubscriber()
				);
			}

			return $entityManager;
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

	public function getConnection(): Connection {
		return $this->pimple['dbal_connection'];
	}

	public function getEntityManager(): EntityManager {
		return $this->pimple['entity_manager'];
	}

	public function newInstaller(): Installer {
		return ( new StoreFactory( $this->getConnection() ) )->newInstaller();
	}

	private function getVarPath(): string {
		return $this->config['var-path'];
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

}
