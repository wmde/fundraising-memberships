<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

/**
 * Create a Doctrine DBAL Connection for the data migration scripts, re-using migration config
 */
class ConnectionFactory {
	private const CONFIG_FILE = "migrations-db.php";

	public static function getConnection(): Connection {
		if ( !file_exists( self::CONFIG_FILE ) ) {
			printf( "Database configuration file '%s' not found in %s\n", self::CONFIG_FILE, getcwd() );
			die( 1 );
		}
		$config = include self::CONFIG_FILE;
		if ( empty( $config ) ) {
			printf( "Database configuration file '%s' did not contain configuration data\n", self::CONFIG_FILE );
			die( 1 );
		}

		try {
			$conn = DriverManager::getConnection( $config );
		} catch ( Exception $e ) {
			echo $e->getMessage() . "\n";
			die( 1 );
		}
		return $conn;
	}
}
