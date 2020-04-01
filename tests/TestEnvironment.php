<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Tools\Setup;
use WMDE\Fundraising\MembershipContext\MembershipContextFactory;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TestEnvironment {

	private array $config;
	private Configuration $doctrineConfig;
	private ?MembershipContextFactory $factory = null;

	private function __construct( array $config, Configuration $doctrineConfig ) {
		$this->config = $config;
		$this->doctrineConfig = $doctrineConfig;
	}

	public static function newInstance(): self {

		$environment = new self(
			[
				'db' => [
					'driver' => 'pdo_sqlite',
					'memory' => true,
				],
				'token-length' => 16,
				'token-validity-timestamp' => 'PT4H',
			],
			Setup::createConfiguration( true )
		);

		$environment->install();

		return $environment;
	}

	private function install(): void {
		$schemaCreator = new SchemaCreator( $this->getFactory()->getEntityManager() );

		try {
			$schemaCreator->dropSchema();
		}
		catch ( \Exception $ex ) {
		}

		$schemaCreator->createSchema();
	}

	public function getFactory(): MembershipContextFactory {
		if ( $this->factory === null ) {
			$this->factory = new MembershipContextFactory(
				$this->config,
				$this->doctrineConfig
			);
		}

		return $this->factory;
	}

}